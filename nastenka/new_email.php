<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = db_connect();
$user = current_user();

if (!function_exists('imap_open')) {
    die('PHP rozšírenie IMAP nie je nainštalované alebo povolené. Toto je vyžadované pre čítanie e-mailov.');
}

$mail_host = "mail.usr.sk";
$mail_user = "info@vueto.sk";
$mail_pass = "020225Pem@";

$flash_msg = '';
$flash_error = '';

// Ziskaj maily z kontaktov pre autocompletion
$contact_emails = $pdo->query("SELECT name, email FROM leads WHERE email IS NOT NULL AND email != '' GROUP BY email ORDER BY name")->fetchAll();

// Ziskaj dynamicky e-mail a telefon odosielatela z DB pre signaturu
$u_stm = $pdo->prepare("SELECT email, phone FROM crm_users WHERE id = ?");
$u_stm->execute([$user['id']]);
$u_det = $u_stm->fetch(PDO::FETCH_ASSOC) ?: [];
$dyn_email = (!empty($u_det['email'])) ? $u_det['email'] : 'info@vueto.sk';
$dyn_phone = (!empty($u_det['phone'])) ? $u_det['phone'] : '+421 950 400 203';

$signature = "\n\n--\nS pozdravom,\n" . htmlspecialchars($user['full_name'] ?? 'Užívateľ') . "\nTím VUETO\n📞 " . htmlspecialchars($dyn_phone) . "\n🌐 www.vueto.sk\n✉️ " . htmlspecialchars($dyn_email);

function send_smtp_email($to, $subject, $body, $host, $user, $pass, $attachments = []) {
    $boundary = md5(uniqid(time()));
    $headers = "From: VUETO <$user>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    $payload = "";
    if (empty($attachments)) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $lang_hint = "<div style='display:none; color:transparent; opacity:0; font-size:1px;'>Táto správa bola odoslaná zo systému VUETO CRM v slovenskom jazyku.</div>";
        $payload = "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'><meta http-equiv='Content-Language' content='sk'><meta name='language' content='Slovak'></head><body lang='sk'>" . $body . $lang_hint . "</body></html>\r\n";
    } else {
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $lang_hint = "<div style='display:none; color:transparent; opacity:0; font-size:1px;'>Táto správa bola odoslaná zo systému VUETO CRM v slovenskom jazyku.</div>";
        $payload .= "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'><meta http-equiv='Content-Language' content='sk'><meta name='language' content='Slovak'></head><body lang='sk'>" . $body . $lang_hint . "</body></html>\r\n\r\n";
        
        foreach ($attachments as $att) {
            $encoded_filename = "=?UTF-8?B?" . base64_encode($att['name']) . "?=";
            $payload .= "--$boundary\r\n";
            $payload .= "Content-Type: " . $att['type'] . "; name=\"$encoded_filename\"\r\n";
            $payload .= "Content-Transfer-Encoding: base64\r\n";
            $payload .= "Content-Disposition: attachment; filename=\"$encoded_filename\"\r\n\r\n";
            $payload .= chunk_split(base64_encode(file_get_contents($att['tmp_name']))) . "\r\n";
        }
        $payload .= "--$boundary--\r\n";
    }

    $full_message = $headers . "\r\n" . $payload;

    $sock = @fsockopen("ssl://" . $host, 465, $errno, $errstr, 10);
    if (!$sock) return "Chyba pripojenia k SMTP: $errstr";
    
    fgets($sock, 515);
    fwrite($sock, "EHLO vueto.sk\r\n");
    stream_set_timeout($sock, 1);
    while($line = fgets($sock, 515)) { if(substr($line, 3, 1) == ' ') break; }

    fwrite($sock, "AUTH LOGIN\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($user) . "\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($pass) . "\r\n");
    $auth = fgets($sock, 515);
    if (substr($auth, 0, 3) != '235') return "Chyba prihlásenia k SMTP.";

    fwrite($sock, "MAIL FROM:<$user>\r\n");
    fgets($sock, 515);
    fwrite($sock, "RCPT TO:<$to>\r\n");
    fgets($sock, 515);
    fwrite($sock, "DATA\r\n");
    fgets($sock, 515);

    $chunks = str_split($full_message . ".\r\n", 4096);
    foreach ($chunks as $c) fwrite($sock, $c);
    
    $res = fgets($sock, 515);
    fwrite($sock, "QUIT\r\n");
    fclose($sock);
    
    if (substr($res, 0, 3) != '250') return "Chyba odosielania: $res";

    // Uložit kópiu do uloženej pošty automatickou identifikáciou priečinka
    $imap_conn = @imap_open("{" . $host . ":993/imap/ssl}", $user, $pass);
    if ($imap_conn) {
        $boxes = imap_list($imap_conn, "{" . $host . ":993/imap/ssl}", "*");
        $sent_folder = "Sent"; // fallback
        if ($boxes) {
            foreach ($boxes as $b) {
                $name = str_replace("{" . $host . ":993/imap/ssl}", "", $b);
                if (stripos($name, 'sent') !== false || stripos($name, 'odoslan') !== false) {
                    $sent_folder = $name;
                    break;
                }
            }
            imap_append($imap_conn, "{" . $host . ":993/imap/ssl}$sent_folder", $full_message . "\r\n", "\\Seen");
        }
        imap_close($imap_conn);
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    csrf_check();
    $to = $_POST['to'];
    $subject = $_POST['subject'];
    $body = $_POST['message'];
    
    $attachments = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $attachments[] = [
                    'name' => $_FILES['attachments']['name'][$key],
                    'type' => $_FILES['attachments']['type'][$key] ?: 'application/octet-stream',
                    'tmp_name' => $tmp_name
                ];
            }
        }
    }
    
    $result = send_smtp_email($to, $subject, $body, $mail_host, $mail_user, $mail_pass, $attachments);
    if ($result === true) {
        header('Location: email.php?sent=1');
        exit;
    } else {
        $flash_error = $result;
    }
}

$page_title = "Nová správa";
include __DIR__ . '/partials/header.php';
?>
<!-- Quill Editor Assets -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<style>
    /* Quill Dark Theme Refinement */
    .ql-toolbar.ql-snow {
        border-color: var(--border) !important;
        background: rgba(255,255,255,0.02) !important;
        border-radius: 12px 12px 0 0 !important;
    }
    .ql-container.ql-snow {
        border-color: var(--border) !important;
        background: #0f1117 !important;
        border-radius: 0 0 12px 12px !important;
        font-family: inherit !important;
        font-size: 1rem !important;
    }
    .ql-editor {
        min-height: 300px !important;
        color: var(--text-primary) !important;
    }
    .ql-editor.ql-blank::before {
        color: rgba(255,255,255,0.3) !important;
        font-style: normal !important;
    }
    .ql-snow .ql-stroke { stroke: var(--text-secondary) !important; }
    .ql-snow .ql-fill { fill: var(--text-secondary) !important; }
    .ql-snow .ql-picker { color: var(--text-secondary) !important; }
    .ql-snow .ql-picker-options {
        background-color: #1a1d21 !important;
        border-color: var(--border) !important;
    }
</style>
<?php

// Sugescie kontaktov 
?>
<datalist id="contactEmails">
    <?php foreach($contact_emails as $ce): ?>
        <option value="<?= htmlspecialchars($ce['email']) ?>"><?= htmlspecialchars($ce['name']) ?></option>
    <?php endforeach; ?>
</datalist>

<div class="container-fluid new-email-container" style="padding-top: 0.5rem; padding-bottom: 2rem;">
            <!-- Actions removed and moved to header -->

            <?php if ($flash_error): ?>
                <div class="alert alert-danger" style="margin-bottom: 24px; border-radius: 12px; border-left: 5px solid #ef4444; background: rgba(239, 64, 64, 0.1); padding: 1rem 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="ti ti-alert-circle" style="font-size: 1.5rem;"></i>
                        <div style="font-weight: 600;"><?= htmlspecialchars($flash_error) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card email-composer-card" style="border-radius: 24px !important; border: 1px solid rgba(255,255,255,0.06); background: var(--bg-card); box-shadow: 0 15px 40px rgba(0,0,0,0.3); transition: transform 0.3s ease;">
                <form method="POST" enctype="multipart/form-data" id="composerForm" style="border-radius: 24px !important; overflow: hidden !important;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="send_email">

                    <div class="composer-body" style="padding: 1.5rem; border-radius: 24px 24px 0 0;">
                        <!-- To Field -->
                        <div class="composer-row" style="margin-bottom: 1.5rem; transition: all 0.2s;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:700; margin-bottom:10px; letter-spacing: 0.05em; line-height: 1;"><i data-lucide="user" style="width:14px; height:14px; color:var(--accent);"></i> <span>Komu</span></label>
                            <div style="position: relative;">
                                <input type="text" name="to" list="contactEmails" required placeholder="Meno klienta alebo e-mail" style="width:100%; background: var(--bg-base); border: 1px solid var(--border); border-radius: 12px; color:var(--text-primary); font-size:1rem; padding:12px 16px; outline:none; transition: all 0.2s;" onfocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 15px var(--accent-glow)'" onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="composer-row" style="margin-bottom: 1.5rem;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:700; margin-bottom:10px; letter-spacing: 0.05em; line-height: 1;"><i data-lucide="pencil" style="width:14px; height:14px; color:var(--accent);"></i> <span>Predmet</span></label>
                            <input type="text" name="subject" required placeholder="O čom je táto správa?" style="width:100%; background: var(--bg-base); border: 1px solid var(--border); border-radius: 12px; color:var(--text-primary); font-size:1rem; padding:12px 16px; outline:none; transition: all 0.2s;" onfocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 15px var(--accent-glow)'" onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
                        </div>

                        <!-- Message -->
                        <div class="composer-row" style="margin-bottom: 1rem; transition: all 0.2s;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:700; margin-bottom:12px; letter-spacing: 0.05em; line-height: 1;"><i data-lucide="message-square" style="width:14px; height:14px; color:var(--accent);"></i> <span>Správa</span></label>
                            
                            <div style="border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
                                <div class="ai-assistant-bar">
                                    <div class="ai-loading" id="aiLoading">
                                        <i class="ti ti-loader"></i> <span>AI premýšľa...</span>
                                    </div>
                                    <button type="button" class="btn-ai-minimal" onclick="runAI('generate_email')">
                                        <i data-lucide="sparkles" style="width:14px; height:14px; margin-top: -1px;"></i> Napísať z bodov
                                    </button>
                                    <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'profesionálny'})">
                                        <i data-lucide="briefcase" style="width:16px; height:16px;"></i> Profesionálne
                                    </button>
                                    <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'priateľský'})">
                                        <i data-lucide="smile" style="width:16px; height:16px;"></i> Priateľsky
                                    </button>
                                    <button type="button" class="btn-ai-minimal" onclick="runAI('fix_grammar')">
                                        <i data-lucide="check-circle" style="width:16px; height:16px;"></i> Gramatika
                                    </button>
                                </div>
                                <input type="hidden" name="message" id="hiddenMessage">
                                <div id="editor-container" style="min-height: 350px; border: none !important; border-radius: 0;"><?= nl2br($signature) ?></div>
                            </div>
                        </div>

                        <!-- Attachments List -->
                        <div id="attachments-container" style="display:flex; flex-wrap:wrap; gap:12px; margin-top:10px;"></div>
                    </div>

                    <div class="composer-footer" style="padding: 1.25rem; border-top: none; display: flex; align-items: center; justify-content: space-between; background: transparent; border-radius: 0 0 24px 24px;">
                        <div style="display: flex; align-items: center; gap: 15px; width: 100%;">
                            <label class="btn btn-secondary" style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0; border-radius:12px; padding: 0 1.25rem; height:38px; font-weight: 600; border: 1px solid var(--border); background: var(--bg-hover); transition: all 0.2s;">
                                <input type="file" name="attachments[]" multiple style="display:none !important;" onchange="handleNewEmailFiles(this)">
                                <i data-lucide="paperclip" style="width: 17px; height: 17px;"></i> <span>Priložiť súbory</span>
                            </label>
                            <span id="file-count-label" style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Žiadne prílohy</span>
                        </div>
                        
                        <div style="display: flex; gap: 15px;">
                            <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 1.5rem; border-radius: 12px; font-size: 0.88rem; font-weight: 700; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(115,103,240,0.2); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i data-lucide="send" style="width: 18px; height: 18px;"></i> Odoslať správu
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

<!-- Custom Elements / Tooltips -->
<style>
    .auto-expand { overflow: visible !important; }
    
    @media (max-width: 1024px) {
        .crm-layout {
            height: auto !important;
            min-height: 100vh !important;
            display: block !important;
        }
    }
    /* NUCLEAR OVERRIDE FOR TEXTAREA AND INPUTS */
    /* NUCLEAR OVERRIDE FOR TEXTAREA AND INPUTS */
    textarea, input:not([type="file"]) {
        background-color: #0f172a !important;
        background: #0f172a !important;
        color: #e2e8f0 !important;
        border: 1px solid rgba(255,255,255,0.08) !important;
        border-radius: 12px !important;
        padding: 0.75rem 1rem !important;
        font-family: inherit !important;
        width: 100% !important;
        display: block !important;
        overflow: visible !important;
        -webkit-appearance: none !important;
        box-shadow: none !important;
        font-size: 0.95rem !important;
    }
    textarea:focus, input:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.2) !important;
    }

    input { padding: 0.65rem 1rem !important; height: 38px !important; }

    /* AI Action Bar Styling (Minimalist style) */
    .ai-assistant-bar {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 10px 1.5rem;
        background: rgba(255, 255, 255, 0.02);
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }
    .btn-ai-minimal {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        transition: all 0.2s;
        font-size: 0.85rem;
        font-weight: 600;
        background: none;
        border: none;
        padding: 6px 0;
    }
    .btn-ai-minimal i, .btn-ai-minimal svg {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-ai-minimal:hover {
        color: #6366f1;
        transform: translateY(-1px);
    }
    .btn-ai-minimal i {
        font-size: 1rem;
        opacity: 0.8;
    }
    .ai-loading {
        display: none;
        align-items: center;
        gap: 8px;
        color: var(--accent-2);
        font-size: 0.85rem;
        font-weight: 600;
        margin-right: 10px;
    }
    .ai-loading i { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    /* Nuclear fix for white browser autofill backgrounds */
    input:-webkit-autofill,
    textarea:-webkit-autofill {
        -webkit-text-fill-color: #ffffff !important;
        -webkit-box-shadow: 0 0 0px 1000px #0f172a inset !important;
    }
    @media (max-width: 768px) {
        .new-email-container { padding-left: 10px !important; padding-right: 10px !important; }
        .email-composer-card { border-radius: 16px !important; margin: 0 -5px; overflow: visible !important; }
        .composer-body { padding: 1.25rem 1rem !important; overflow: visible !important; }
        .composer-footer { padding: 1.25rem 1rem !important; flex-direction: column; gap: 20px; align-items: stretch !important; }
        .composer-footer .btn-primary { width: 100%; justify-content: center; }
        
        .ql-container, .ql-editor { touch-action: auto !important; }
        
        /* Fix scrolling */
        body { overflow-y: auto !important; height: auto !important; position: relative !important; }
        .crm-layout { height: auto !important; min-height: 100vh !important; overflow: visible !important; }
    }
</style>

<script>
var quill = null;

async function runAI(action, extraParams = {}) {
    const loading = document.getElementById('aiLoading');
    if (!quill || !loading) return;

    const content = quill.getText().trim();
    if (content.length < 5 && action !== 'generate_email') {
        alert('Prosím, napíšte aspoň krátky text alebo body, aby AI malo z čoho vychádzať.');
        return;
    }

    loading.style.display = 'inline-flex';
    document.querySelectorAll('.btn-ai-minimal').forEach(b => b.disabled = true);
    
    try {
        const response = await fetch('ajax_ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                content: content,
                ...extraParams
            })
        });
        
        const data = await response.json();
        if (data.success) {
            quill.setText(data.result);
        } else {
            alert('AI Chyba: ' + data.error);
        }
    } catch (e) {
        alert('Chyba pri komunikácii s AI.');
    } finally {
        loading.style.display = 'none';
        document.querySelectorAll('.btn-ai-minimal').forEach(b => b.disabled = false);
    }
}

function handleNewEmailFiles(input) {
    const container = document.getElementById('attachments-container');
    const label = document.getElementById('file-count-label');
    container.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        label.textContent = `${input.files.length} vybrané`;
        Array.from(input.files).forEach(file => {
            const pill = document.createElement('div');
            pill.style.cssText = 'background: rgba(255,255,255,0.08); border: 1px solid var(--border); padding: 8px 16px; border-radius: 50px; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-primary); box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.2s; cursor: default;';
            pill.onmouseover = () => pill.style.transform = 'translateY(-2px)';
            pill.onmouseout = () => pill.style.transform = 'translateY(0)';
            pill.innerHTML = `<i class="ti ti-file" style="color:var(--accent); font-size: 1rem;"></i> <span>${file.name}</span>`;
            container.appendChild(pill);
        });
    } else {
        label.textContent = 'Žiadne prílohy';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill Editor
    var editorCont = document.getElementById('editor-container');
    if (editorCont) {
        quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            },
            placeholder: 'Tu napíšte vašu správu...'
        });
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Handle form submission
    var form = document.getElementById('composerForm');
    if (form) {
        form.onsubmit = function() {
            var messageInput = document.getElementById('hiddenMessage');
            messageInput.value = quill.root.innerHTML;
        };
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
