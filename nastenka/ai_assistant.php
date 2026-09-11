<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = db_connect();

$folder = $_GET['folder'] ?? '';
$msg_id = $_GET['msg'] ?? '';
$original_email_content = '';

if ($folder && $msg_id) {
    // Basic IMAP fetch for context
    $mail_host = MAIL_HOST;
    $mail_user = MAIL_USER;
    $mail_pass = MAIL_PASS;
    $imap_box = "{" . $mail_host . ":993/imap/ssl}" . $folder;
    $imap = @imap_open($imap_box, $mail_user, $mail_pass);
    if ($imap) {
        $header = imap_headerinfo($imap, $msg_id);
        $from = $header->fromaddress ?? '';
        $subject = $header->subject ?? '';
        $body = imap_fetchbody($imap, $msg_id, 1);
        $original_email_content = "OD: $from\nPREDMET: $subject\n\n" . substr(strip_tags($body), 0, 1000);
        imap_close($imap);
    }
}

$page_title = "AI Asistent";
include __DIR__ . '/partials/header.php';
?>

<style>
.ai-controls {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.ai-chat-card {
    height: calc(100vh - 200px); 
    min-height: 500px;
    display: flex; 
    flex-direction: column; 
    padding: 0; 
    overflow: hidden; 
    border: 1px solid var(--border);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.msg-toolbar {
    display: flex;
    gap: 12px;
    margin-top: 8px;
    padding-left: 4px;
    opacity: 0.6;
    transition: opacity 0.2s;
}
.msg-toolbar:hover { opacity: 1; }
.tool-icon {
    cursor: pointer;
    font-size: 1.1rem;
    color: var(--text-muted);
    transition: color 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
}
.tool-icon:hover { color: var(--accent-1); }
.tool-icon i { font-size: 1rem; }
</style>

<div class="container-fluid">
    <div class="ai-controls">
        <a href="email.php<?= ($folder ? '?folder='.urlencode($folder).($msg_id ? '&msg='.urlencode($msg_id) : '') : '') ?>" class="btn btn-secondary">
            <i class="ti ti-chevron-left"></i> Späť do e-mailov
        </a>
        <?php if($original_email_content): ?>
        <div style="margin-left: 1rem; padding: 6px 12px; background: rgba(255,255,255,0.05); border-radius: 8px; font-size: 0.8rem; color: var(--text-muted); border: 1px solid var(--border);">
            <i class="ti ti-mail"></i> Odpovedáte na: <strong><?= htmlspecialchars($from ?? 'e-mail') ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid-12">
        <div class="col-12">
            <div class="card ai-chat-card">
                <div id="chatHistory" style="flex: 1; overflow-y: auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; background: rgba(0,0,0,0.02);">
                    <!-- Messages will be appended here -->
                </div>

                <div class="card-footer" style="padding: 1.5rem; border-top: 1px solid var(--border); background: var(--bg-card);">
                    <div style="max-width: 900px; margin: 0 auto; width: 100%;">
                        <div style="display: flex; gap: 12px; align-items: flex-end; position: relative;">
                            <textarea id="aiInput" class="form-control" style="flex: 1; min-height: 50px; height: 50px; max-height: 250px; resize: none; font-size: 0.95rem; padding: 12px 20px; border-radius: 15px; background: rgba(0,0,0,0.2); border: 1px solid var(--border);" placeholder="Napíšte vašu požiadavku..."></textarea>
                            <button type="button" id="sendBtn" class="btn btn-primary" style="height: 50px; width: 50px; min-width: 50px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 15px; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);">
                                <i class="ti ti-send" style="font-size: 1.3rem;"></i>
                            </button>
                        </div>
                        <div id="aiLoading" style="display: none; margin-top: 12px; text-align: center;">
                            <div class="ai-loading" style="font-size: 0.85rem;"><i class="ti ti-loader"></i> <span>AI pripravuje odpoveď...</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let chatHistory = [];
    const originalEmailContext = <?= json_encode($original_email_content) ?>;
    const aiInput = document.getElementById('aiInput');
    const sendBtn = document.getElementById('sendBtn');
    const chatHistoryDiv = document.getElementById('chatHistory');
    const loadingDiv = document.getElementById('aiLoading');

    // Auto-resize
    aiInput.addEventListener('input', function() {
        this.style.height = '50px';
        let newHeight = this.scrollHeight;
        if (newHeight > 250) {
            this.style.height = '250px';
            this.style.overflowY = 'auto';
        } else {
            this.style.height = newHeight + 'px';
            this.style.overflowY = 'hidden';
        }
    });

    async function sendMessage() {
        const text = aiInput.value.trim();
        if (!text || sendBtn.disabled) return;

        sendBtn.disabled = true;
        appendMessage('user', text);
        
        aiInput.value = '';
        aiInput.style.height = '50px';
        loadingDiv.style.display = 'block';
        
        let contentToSend = text;
        if (chatHistory.length === 0 && originalEmailContext) {
            contentToSend = "Context: \n" + originalEmailContext + "\n\nUser request: " + text;
        }
        
        try {
            const response = await fetch('ajax_ai_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'general_chat',
                    content: contentToSend,
                    history: chatHistory
                })
            });
            
            const data = await response.json();
            if (data.success) {
                appendMessage('ai', data.result);
                chatHistory.push({ role: 'user', content: text });
                chatHistory.push({ role: 'assistant', content: data.result });
            } else {
                alert('AI Chyba: ' + data.error);
            }
        } catch (e) {
            alert('Chyba pri komunikácii s AI.');
        } finally {
            sendBtn.disabled = false;
            loadingDiv.style.display = 'none';
            scrollToBottom();
        }
    }

    function appendMessage(role, content) {
        const msgGroup = document.createElement('div');
        msgGroup.style = "display: flex; flex-direction: column; " + (role === 'user' ? "align-items: flex-end;" : "align-items: flex-start;");
        
        const msgDiv = document.createElement('div');
        if (role === 'user') {
            msgDiv.style = "max-width: 80%; background: var(--accent-1); color: white; padding: 1rem 1.25rem; border-radius: 18px 18px 0 18px; font-size: 0.95rem; line-height: 1.6; box-shadow: 0 4px 15px rgba(99,102,241,0.2);";
        } else {
            msgDiv.style = "max-width: 85%; background: var(--bg-card); border: 1px solid var(--border); padding: 1rem 1.25rem; border-radius: 18px 18px 18px 0; font-size: 0.95rem; line-height: 1.6; color: var(--text-primary); box-shadow: 0 2px 10px rgba(0,0,0,0.1);";
        }
        msgDiv.innerText = content;
        msgGroup.appendChild(msgDiv);

        if (role === 'ai') {
            const toolbar = document.createElement('div');
            toolbar.className = 'msg-toolbar';
            
            // Copy Icon
            const copyIcon = document.createElement('div');
            copyIcon.className = 'tool-icon';
            copyIcon.innerHTML = '<i class="ti ti-copy"></i> Kopírovať';
            copyIcon.onclick = function() {
                navigator.clipboard.writeText(content).then(() => {
                    copyIcon.innerHTML = '<i class="ti ti-check" style="color:var(--green)"></i> Skopírované';
                    setTimeout(() => { copyIcon.innerHTML = '<i class="ti ti-copy"></i> Kopírovať'; }, 2000);
                });
            };
            
            // Use Icon
            const useIcon = document.createElement('div');
            useIcon.className = 'tool-icon';
            useIcon.innerHTML = '<i class="ti ti-mail-fast"></i> Použiť v e-maile';
            useIcon.onclick = function() {
                navigator.clipboard.writeText(content);
                sessionStorage.setItem('ai_draft', content);
                const urlParams = new URLSearchParams(window.location.search);
                let returnUrl = 'email.php?compose=ai';
                if (urlParams.has('folder') && urlParams.has('msg')) {
                    returnUrl += '&folder=' + encodeURIComponent(urlParams.get('folder')) + '&msg=' + encodeURIComponent(urlParams.get('msg'));
                }
                window.location.href = returnUrl;
            };

            toolbar.appendChild(copyIcon);
            toolbar.appendChild(useIcon);
            msgGroup.appendChild(toolbar);
        }

        chatHistoryDiv.appendChild(msgGroup);
        scrollToBottom();
    }

    function scrollToBottom() {
        chatHistoryDiv.scrollTop = chatHistoryDiv.scrollHeight;
    }

    sendBtn.onclick = sendMessage;
    aiInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Initial greeting if no history
    appendMessage('ai', 'Dobrý deň! Som pripravený vám pomôcť s písaním e-mailov. Čo by ste potrebovali pripraviť?');
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
