</div><!-- .main-content -->
</div><!-- .crm-layout -->

<!-- GLOBAL CRM MODAL -->
<div id="crm-modal-overlay" class="modal-overlay">
    <div class="modal-box" style="width: min(420px, 90vw);">
        <div class="modal-header">
            <h3 class="modal-title" id="crm-modal-title">Potvrdenie</h3>
            <button class="modal-close" onclick="closeCrmModal()">&times;</button>
        </div>
        <div id="crm-modal-body" style="margin-bottom:1.25rem; font-size:0.95rem; color:var(--text-secondary); line-height:1.5;"></div>
        <div id="crm-modal-prompt-container" style="display:none; margin-bottom:1.5rem;">
            <div class="password-toggle-wrapper">
                <input type="text" id="crm-modal-input" class="form-control with-toggle" placeholder="Vaša odpoveď..." autocomplete="off" style="background:#0f172a !important; color:white !important;">
                <button id="toggle-p-modal" class="password-toggle-btn" style="display:none;" onclick="togglePasswordVisibility('toggle-p-modal', 'crm-modal-input')">
                    <i class="ti ti-eye"></i>
                </button>
            </div>
        </div>
        <div id="crm-modal-footer" style="display:flex; justify-content:flex-end; gap:.75rem;">
            <button class="btn btn-secondary" onclick="closeCrmModal()">Zrušiť</button>
            <button class="btn btn-primary" id="crm-modal-confirm-btn">Potvrdiť</button>
        </div>
    </div>
</div>

<!-- BUG REPORT MODAL -->
<div id="bug-report-overlay" class="modal-overlay" style="backdrop-filter: blur(8px);">
    <div class="modal-box" style="width: min(850px, 95vw); background: rgba(17, 24, 39, 0.98); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; box-shadow: 0 25px 80px -12px rgba(0, 0, 0, 0.7); padding: 0; overflow: hidden;">
        <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 20px 30px; margin-bottom: 0;">
            <h3 class="modal-title" style="color: white; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="ti ti-bug" style="color: var(--orange); font-size: 1.4rem;"></i> Nahlásiť chybu
            </h3>
            <button class="modal-close" onclick="closeBugModal()">&times;</button>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;">
            <!-- LEFT COLUMN -->
            <div style="padding: 30px; border-right: 1px solid rgba(255,255,255,0.05);">
                <p style="margin-bottom: 2rem; color: #94a3b8; font-size: 0.95rem; line-height: 1.5;">Popíšte, čo nefunguje. Správa sa odošle tímu na preverenie.</p>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 8px; font-size: 0.85rem; font-weight: 600; color: #e2e8f0;">Čo sa stalo?</label>
                    <textarea id="bug-desc" class="form-control" rows="6" placeholder="Napr. keď kliknem na tlačidlo, nič sa nestane..." style="background: rgba(0,0,0,0.2) !important; border-color: rgba(255,255,255,0.1) !important; color: white !important; resize: none; border-radius: 12px; padding: 12px; font-size: 0.95rem;"></textarea>
                </div>

                <div style="background: rgba(255,255,255,0.02); border-radius: 12px; padding: 15px; display: flex; flex-direction: column; gap: 10px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 0.65rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Rýchly návod na snímku:</div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span style="background: #1e293b; color: #cbd5e1; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; border: 1px solid #334155; font-weight: 700;">Win + Shift + S</span>
                        <span style="color: #475569; font-size: 0.75rem;">&rarr;</span>
                        <span style="background: #1e293b; color: #cbd5e1; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; border: 1px solid #334155; font-weight: 700;">Ctrl + V</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div style="padding: 30px; background: rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: #e2e8f0;">Screenshot (voliteľné)</label>
                    <button class="btn btn-sm" onclick="takeAutoScreenshot()" id="bug-auto-snap-btn" style="background: rgba(139, 92, 246, 0.15); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.3); font-size: 0.75rem; border-radius: 8px; padding: 5px 12px;">
                        <i class="ti ti-camera"></i> Automatická snímka
                    </button>
                </div>
                
                <div id="bug-upload-area" onclick="document.getElementById('bug-screenshot').click()" style="border: 2px dashed rgba(255,255,255,0.1); border-radius: 16px; height: 210px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden; background: rgba(255,255,255,0.01);">
                    <div id="bug-preview-container" style="display: none; width: 100%; height: 100%;">
                        <img id="bug-preview-img" style="width: 100%; height: 100%; object-fit: contain; background: #000;">
                        <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem;">Zmeniť</div>
                    </div>
                    <div id="bug-upload-empty">
                        <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.03); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="ti ti-photo-plus" style="font-size: 1.8rem; color: #64748b;"></i>
                        </div>
                        <p style="font-size: 0.9rem; color: #94a3b8; margin-bottom: 5px;">Kliknite sem alebo vložte</p>
                        <p style="font-size: 0.75rem; color: #475569;">Podporované: JPG, PNG, WEBP</p>
                    </div>
                    <input type="file" id="bug-screenshot" accept="image/*" style="display: none;" onchange="handleBugScreenshot(this)">
                </div>

                <div style="margin-top: 2rem; font-size: 0.8rem; color: #475569; display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 10px;">
                    <i class="ti ti-info-circle" style="font-size: 1.1rem; color: #64748b;"></i>
                    <span>S hlásením sa priloží: stránka, verzia prehliadača a systémové info.</span>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div style="padding: 20px 30px; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.05); display: flex; gap: 12px; justify-content: flex-end;">
            <button class="btn btn-secondary" onclick="closeBugModal()" style="border-radius: 12px; padding: 10px 25px; min-width: 120px;">Zrušiť</button>
            <button class="btn btn-primary" id="bug-send-btn" onclick="submitBugReport()" style="background: linear-gradient(135deg, #f97316, #ea580c); border: none; border-radius: 12px; padding: 10px 40px; font-weight: 700; min-width: 150px; box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3);">
                Odoslať hlásenie
            </button>
        </div>
    </div>
</div>

<style>
/* Responsive fix for bug modal */
@media (max-width: 768px) {
    #bug-report-overlay .modal-box { width: 95vw !important; max-height: 90vh; overflow-y: auto !important; }
    #bug-report-overlay .modal-box > div:nth-child(2) { grid-template-columns: 1fr !important; }
    #bug-report-overlay .modal-box div { border-right: none !important; }
}
</style>

<script>
function toggleSidebar() {
    const icon = document.querySelector('.menu-toggle i');
    if (window.innerWidth <= 900) {
        document.documentElement.classList.toggle('sidebar-open-mobile');
        if (icon) {
            if (document.documentElement.classList.contains('sidebar-open-mobile')) {
                icon.classList.replace('ti-menu-2', 'ti-x');
            } else {
                icon.classList.replace('ti-x', 'ti-menu-2');
            }
        }
    } else {
        document.documentElement.classList.toggle('sidebar-collapsed');
        localStorage.setItem('crm_sidebar_collapsed', document.documentElement.classList.contains('sidebar-collapsed'));
    }
}

function closeSidebarMobile() {
    document.documentElement.classList.remove('sidebar-open-mobile');
    const icon = document.querySelector('.menu-toggle i');
    if (icon) icon.classList.replace('ti-x', 'ti-menu-2');
}

/* Premium JS Tooltip System */
(function() {
    let tooltipEl = document.getElementById('crm-tooltip');
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'crm-tooltip';
        document.body.appendChild(tooltipEl);
    }

    document.addEventListener('mouseover', function(e) {
        const target = e.target.closest('[data-tooltip]');
        if (!target || !document.documentElement.classList.contains('sidebar-collapsed')) {
            tooltipEl.classList.remove('visible');
            return;
        }

        const text = target.getAttribute('data-tooltip');
        if (!text) return;

        tooltipEl.innerText = text;
        const rect = target.getBoundingClientRect();
        
        // Position to the right of the icon
        tooltipEl.style.top = (rect.top + rect.height / 2) + 'px';
        tooltipEl.style.left = rect.right + 'px';
        tooltipEl.classList.add('visible');
    });

    document.addEventListener('mouseout', function(e) {
        const target = e.target.closest('[data-tooltip]');
        if (target) {
            tooltipEl.classList.remove('visible');
        }
    });

    // Also hide on scroll or menu toggle
    window.addEventListener('scroll', () => tooltipEl.classList.remove('visible'));
    document.addEventListener('click', () => tooltipEl.classList.remove('visible'));
})();

/* CRM Modal Logic */
let crmModalActiveCallback = null;

function closeCrmModal() {
    document.getElementById('crm-modal-overlay').classList.remove('open');
    crmModalActiveCallback = null;
}

function crmConfirm(title, message, callback) {
    document.getElementById('crm-modal-title').innerText = title;
    document.getElementById('crm-modal-body').innerText = message;
    document.getElementById('crm-modal-prompt-container').style.display = 'none';
    document.getElementById('crm-modal-confirm-btn').innerText = 'Potvrdiť';
    document.getElementById('crm-modal-confirm-btn').className = 'btn btn-primary';
    document.querySelector('#crm-modal-footer .btn-secondary').style.display = 'inline-flex';
    crmModalActiveCallback = callback;
    document.getElementById('crm-modal-overlay').classList.add('open');
}

function crmAlert(title, message, callback) {
    document.getElementById('crm-modal-title').innerText = title;
    document.getElementById('crm-modal-body').innerText = message;
    document.getElementById('crm-modal-prompt-container').style.display = 'none';
    document.getElementById('crm-modal-confirm-btn').innerText = 'OK';
    document.getElementById('crm-modal-confirm-btn').className = 'btn btn-primary';
    document.querySelector('#crm-modal-footer .btn-secondary').style.display = 'none';
    crmModalActiveCallback = callback;
    document.getElementById('crm-modal-overlay').classList.add('open');
}

function crmPrompt(title, message, callback) {
    document.getElementById('crm-modal-title').innerText = title;
    document.getElementById('crm-modal-body').innerText = message;
    document.getElementById('crm-modal-prompt-container').style.display = 'block';
    document.getElementById('crm-modal-confirm-btn').innerText = 'Odoslať';
    document.getElementById('crm-modal-confirm-btn').className = 'btn btn-primary';
    document.querySelector('#crm-modal-footer .btn-secondary').style.display = 'inline-flex';
    const input = document.getElementById('crm-modal-input');
    input.value = '';
    input.type = 'text'; // Default to text
    document.getElementById('toggle-p-modal').style.display = 'none'; // Hide toggle by default
    crmModalActiveCallback = callback;
    document.getElementById('crm-modal-overlay').classList.add('open');
    setTimeout(() => input.focus(), 200);
}

document.getElementById('crm-modal-confirm-btn').onclick = function() {
    const promptContainer = document.getElementById('crm-modal-prompt-container');
    let val = true;
    if (promptContainer.style.display !== 'none') {
        val = document.getElementById('crm-modal-input').value;
    }
    if (crmModalActiveCallback) crmModalActiveCallback(val);
    closeCrmModal();
};

function togglePasswordVisibility(btnId, inputId) {
    const input = document.getElementById(inputId);
    const icon = document.querySelector(`#${btnId} i`);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('ti-eye', 'ti-eye-off');
    } else {
        input.type = 'password';
        icon.classList.replace('ti-eye-off', 'ti-eye');
    }
}

// Handle Enter key in prompt input
document.getElementById('crm-modal-input').onkeydown = function(e) {
    if (e.key === 'Enter') {
        document.getElementById('crm-modal-confirm-btn').click();
    }
};

// Bug Report Logic
let bugAutoBlob = null;

function openBugReport() {
    document.getElementById('bug-desc').value = '';
    document.getElementById('bug-screenshot').value = '';
    document.getElementById('bug-preview-container').style.display = 'none';
    document.getElementById('bug-upload-empty').style.display = 'block';
    document.getElementById('bug-report-overlay').classList.add('open');
    bugAutoBlob = null;
    setTimeout(() => document.getElementById('bug-desc').focus(), 300);
}

function closeBugModal() {
    document.getElementById('bug-report-overlay').classList.remove('open');
}

function handleBugScreenshot(input) {
    if (input.files && input.files[0]) {
        bugAutoBlob = null; // Clear auto if manual selected
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('bug-preview-img').src = e.target.result;
            document.getElementById('bug-preview-container').style.display = 'block';
            document.getElementById('bug-upload-empty').style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// 📸 Handle Automatic Screenshot
async function takeAutoScreenshot() {
    const btn = document.getElementById('bug-auto-snap-btn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="ti ti-loader-2 rotate"></i> Snímam...';
    btn.disabled = true;
    
    // Hide modal for a clean shot
    document.getElementById('bug-report-overlay').style.opacity = '0';
    
    try {
        const canvas = await html2canvas(document.body, {
            useCORS: true,
            logging: false,
            backgroundColor: '#0f172a'
        });
        
        canvas.toBlob(blob => {
            bugAutoBlob = blob;
            const url = URL.createObjectURL(blob);
            document.getElementById('bug-preview-img').src = url;
            document.getElementById('bug-preview-container').style.display = 'block';
            document.getElementById('bug-upload-empty').style.display = 'none';
            document.getElementById('bug-screenshot').value = ''; // Clear file input
        }, 'image/jpeg', 0.8);
        
    } catch (e) {
        console.error('Screenshot error:', e);
        crmAlert('Chyba', 'Nepodarilo sa vytvoriť automatickú snímku.');
    } finally {
        document.getElementById('bug-report-overlay').style.opacity = '1';
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

// 📋 Listen for Paste event (Ctrl+V)
document.addEventListener('paste', function(e) {
    if (!document.getElementById('bug-report-overlay').classList.contains('open')) return;
    
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let index in items) {
        const item = items[index];
        if (item.kind === 'file' && item.type.includes('image')) {
            const blob = item.getAsFile();
            bugAutoBlob = blob; // Reuse same logic
            const url = URL.createObjectURL(blob);
            document.getElementById('bug-preview-img').src = url;
            document.getElementById('bug-preview-container').style.display = 'block';
            document.getElementById('bug-upload-empty').style.display = 'none';
            document.getElementById('bug-screenshot').value = ''; 
            break;
        }
    }
});

async function submitBugReport() {
    const desc = document.getElementById('bug-desc').value.trim();
    if (!desc) {
        document.getElementById('bug-desc').focus();
        return;
    }

    const btn = document.getElementById('bug-send-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="ti ti-loader-2 rotate"></i> Odosielam...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('description', desc);
    formData.append('url', window.location.href);
    formData.append('user_agent', navigator.userAgent);
    
    // Check if we have an auto-captured blob or a manually uploaded file
    if (bugAutoBlob) {
        formData.append('screenshot', bugAutoBlob, 'clipboard_or_auto.jpg');
    } else {
        const fileInput = document.getElementById('bug-screenshot');
        if (fileInput.files && fileInput.files[0]) {
            formData.append('screenshot', fileInput.files[0]);
        }
    }

    try {
        const resp = await fetch('ajax_bug_report.php', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            closeBugModal();
            crmAlert('🎉 Hlásenie odoslané', 'Ďakujeme za spätnú väzbu. Adminovi sme poslali notifikáciu.');
        } else {
            crmAlert('Chyba', 'Hlásenie sa nepodarilo odoslať: ' + (data.error || 'Neznáma chyba'));
        }
    } catch (e) {
        crmAlert('Chyba', 'Nastala chyba pri spojení so serverom.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Global interceptor for data-confirm
document.addEventListener('submit', function(e) {
    const confirmMsg = e.target.getAttribute('data-confirm');
    if (confirmMsg && !e.target.dataset.confirmed) {
        e.preventDefault();
        crmConfirm("Potvrdenie akcie", confirmMsg, (ok) => {
            if (ok) {
                e.target.dataset.confirmed = "true";
                e.target.submit();
            }
        });
    }
}, true);
</script>
 
<!-- ============================================================
     PRIVATE MESSENGER WIDGET
     ============================================================ -->
<div id="crm-chat-button" onclick="toggleChat()">
    <i data-lucide="message-square-more"></i>
    <span class="unread-badge" id="chat-global-unread" style="display: none;">0</span>
</div>

    <div id="crm-chat-window">
    <div class="chat-header">
        <div class="chat-header-actions">
            <button class="btn btn-secondary" onclick="toggleChat()" style="border-radius:12px; height: 38px; width: 38px; padding: 0; display: flex; align-items: center; justify-content: center;">✕</button>
            <button id="chat-multi-toggle" class="btn btn-secondary" onclick="toggleMultiMode()" title="Vybrať viacerých" style="border-radius:12px; height: 38px; width: 38px; padding: 0; display: flex; align-items: center; justify-content: center;"><i data-lucide="users-2"></i></button>
        </div>
        <h3 id="chat-window-title">Súkromné správy</h3>
    </div>

    <!-- View 1: Contacts List -->
    <div id="chat-view-contacts" class="chat-pane active">
        <div id="chat-multi-bar" class="chat-multi-select-bar" style="display:none; flex-direction:column; gap:8px;">
            <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                <span id="chat-selected-count" style="color:var(--accent-2);">Vybraní: 0</span>
                <div style="display:flex; gap:5px;">
                    <button class="btn btn-secondary" style="font-size:0.7rem;" onclick="selectAllContacts()">Všetci</button>
                    <button class="btn btn-secondary" style="font-size:0.7rem;" onclick="toggleMultiMode()">Zrušiť</button>
                </div>
            </div>
            <button class="btn btn-primary" onclick="startMultiChat()" style="width:100%;">Písať vybraným</button>
        </div>
        <div class="chat-contacts" id="chat-contacts-list">
            <div class="text-center text-muted p-2" style="font-size:0.8rem;">Načítavam kolegov...</div>
        </div>
    </div>

    <!-- View 2: Active Chat -->
    <div id="chat-view-messages" class="chat-pane">
        <div style="padding: 0.5rem 1rem; background: rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between;">
            <div style="display:flex; align-items:center; gap:10px;">
                <button class="chat-back-btn" onclick="showContacts()"><i class="ti ti-chevron-left" style="font-size:0.9rem;"></i> Späť</button>
                <span id="active-chat-name" style="font-weight: 700; font-size: 0.85rem; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Meno kolegu</span>
            </div>
            <button class="chat-header-btn" onclick="toggleAdvancedSettings()"><i class="ti ti-settings" style="font-size:1rem;"></i></button>
        </div>
        
        <div class="chat-messages" id="chat-messages-container">
            <!-- Messages here -->
        </div>

        <div id="chat-advanced-settings">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid rgba(255,255,255,0.05);">
                <span style="font-weight:600; font-size:0.75rem;"><i class="ti ti-settings"></i> Nastavenia správy</span>
                <button onclick="toggleAdvancedSettings()" style="background:none; border:none; color:white; font-size:1rem; cursor:pointer; padding:0 4px;">&times;</button>
            </div>
            <div class="chat-setting-group">
                <label>Maximálny počet videní</label>
                <div class="chat-counter">
                    <button onclick="changeMaxViews(-1)"><i class="ti ti-minus"></i></button>
                    <span id="chat-max-views-display">1</span>
                    <button onclick="changeMaxViews(1)"><i class="ti ti-plus"></i></button>
                </div>
                <input type="hidden" id="chat-max-views" value="1">
            </div>
            <div class="chat-setting-group">
                <label>Heslo pre odomknutie (voliteľné)</label>
                <div class="password-toggle-wrapper">
                    <input type="password" id="chat-safe-password" class="chat-setting-input with-toggle" placeholder="Zadaj heslo..." autocomplete="new-password" style="background:#0f172a !important; color:white !important;">
                    <button id="toggle-p-create" class="password-toggle-btn" onclick="togglePasswordVisibility('toggle-p-create', 'chat-safe-password')">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            </div>
            <div class="chat-setting-group" style="margin-top:15px; border-top:1px solid rgba(255,255,255,0.05); padding-top:10px;">
                <label class="safe-toggle" style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.75rem; color:rgba(255,255,255,0.6); font-weight:400;">🔒 Aktivovať SafeNote <br> (vymaže po prečítaní)</span>
                    <div style="display:flex; align-items:center;">
                        <input type="checkbox" id="chat-safe-mode">
                        <span class="slider"></span>
                    </div>
                </label>
            </div>
            <div style="text-align:right;">
                <button class="btn btn-secondary" onclick="toggleAdvancedSettings()" style="font-size:0.65rem;">Potvrdiť</button>
            </div>
        </div>

        <div class="chat-footer" id="chat-footer-el">
            <div class="chat-input-row" style="position:relative;">
                <button class="chat-header-btn" onclick="document.getElementById('chat-file-input').click()" title="Priložiť súbor" style="margin-right:5px; padding:6px; background:rgba(255,255,255,0.05); border-radius:8px;">
                    <i class="ti ti-paperclip"></i>
                </button>
                <input type="file" id="chat-file-input" style="display:none;" onchange="handleFileSelect(this)">
                <textarea class="chat-input" id="chat-msg-input" placeholder="Napíš správu..." rows="1"></textarea>
                <button class="chat-send-btn" onclick="sendMessage()">
                    <i class="ti ti-send"></i>
                </button>
            </div>
            <div id="chat-file-preview" style="display:none; padding:8px 12px; background:rgba(30,58,138,0.3); border-radius:12px; margin-top:8px; align-items:center; justify-content:space-between; font-size:0.75rem; color:var(--accent-2);">
                <span id="chat-file-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:80%; font-weight:600;"></span>
                <button onclick="clearFileSelection()" style="background:none; border:none; color:white; font-size:1rem; cursor:pointer; padding:0 4px;">&times;</button>
            </div>
            <div class="chat-actions">
            </div>
        </div>
    </div>
</div>

<script>
let activeChatId = null; // Can be a single ID or "multi"
let multiRecipientIds = new Set();
let allContactsCache = []; // To allow select all
let isMultiMode = false;
let chatRefreshInterval = null;
let lastMessageCount = 0;

function toggleChat() {
    const win = document.getElementById('crm-chat-window');
    win.classList.toggle('open');
    document.documentElement.classList.toggle('chat-open');
    if (win.classList.contains('open')) {
        loadContacts();
        startPolling();
    } else {
        stopPolling();
    }
}

function toggleMultiMode() {
    isMultiMode = !isMultiMode;
    const btn = document.getElementById('chat-multi-toggle');
    const bar = document.getElementById('chat-multi-bar');
    
    if (isMultiMode) {
        btn.classList.replace('btn-secondary', 'btn-primary');
        bar.style.display = 'flex';
    } else {
        btn.classList.replace('btn-primary', 'btn-secondary');
        bar.style.display = 'none';
        multiRecipientIds.clear();
        updateMultiCount();
    }
    loadContacts();
}

function selectAllContacts() {
    allContactsCache.forEach(c => multiRecipientIds.add(c.id));
    updateMultiCount();
    loadContacts();
}

function updateMultiCount() {
    document.getElementById('chat-selected-count').innerText = `Vybraní: ${multiRecipientIds.size}`;
}

function handleContactClick(id, name) {
    if (isMultiMode) {
        if (multiRecipientIds.has(id)) multiRecipientIds.delete(id);
        else multiRecipientIds.add(id);
        updateMultiCount();
        loadContacts();
    } else {
        openChat(id, name);
    }
}

function startMultiChat() {
    if (multiRecipientIds.size === 0) return crmAlert('Chyba', 'Vyber aspoň jedného kolegu zo zoznamu nižšie zakliknutím.');
    activeChatId = "multi";
    document.getElementById('active-chat-name').innerText = `${multiRecipientIds.size} kolegovia`;
    document.getElementById('chat-view-contacts').style.display = 'none';
    document.getElementById('chat-view-messages').style.display = 'flex';
    document.getElementById('chat-messages-container').innerHTML = '<div class="text-center text-muted py-4">Píšeš hromadnú správu. História sa nezobrazuje.</div>';
}

function showContacts() {
    activeChatId = null;
    document.getElementById('chat-view-messages').style.display = 'none';
    document.getElementById('chat-view-contacts').style.display = 'block';
    document.getElementById('chat-window-title').innerHTML = 'Súkromné správy <i class="ti ti-messages"></i>';
    loadContacts();
}

function toggleAdvancedSettings() {
    document.getElementById('chat-advanced-settings').classList.toggle('open');
}

function changeMaxViews(delta) {
    const input = document.getElementById('chat-max-views');
    const display = document.getElementById('chat-max-views-display');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
    display.innerText = val;
}

async function loadContacts() {
    try {
        const resp = await fetch('api_messages.php?action=fetch_contacts');
        const data = await resp.json();
        allContactsCache = data.contacts;
        const list = document.getElementById('chat-contacts-list');
        list.innerHTML = '';
        
        let totalUnread = 0;

        data.contacts.forEach(c => {
            totalUnread += parseInt(c.unread_count);
            const isSelected = multiRecipientIds.has(c.id);
            const item = document.createElement('div');
            item.className = `contact-item ${isSelected ? 'selected' : ''}`;
            item.onclick = () => handleContactClick(c.id, c.full_name);
            
            const initial = c.full_name.charAt(0).toUpperCase();
            item.innerHTML = `
                ${isMultiMode ? `<input type="checkbox" class="contact-real-checkbox" ${isSelected ? 'checked' : ''} style="margin-right:12px; width:18px; height:18px; pointer-events:none;">` : ''}
                <div class="contact-avatar">${initial}</div>
                <div class="contact-info">
                    <div class="contact-name">${c.full_name}</div>
                </div>
                ${c.unread_count > 0 ? `<div class="contact-unread">${c.unread_count}</div>` : ''}
            `;
            list.appendChild(item);
        });

        const badge = document.getElementById('chat-global-unread');
        badge.innerText = totalUnread;
        badge.style.display = totalUnread > 0 ? 'flex' : 'none';

    } catch (e) { console.error('Chat load error:', e); }
}

async function openChat(id, name) {
    activeChatId = id;
    document.getElementById('active-chat-name').innerText = name;
    document.getElementById('chat-view-contacts').style.display = 'none';
    document.getElementById('chat-view-messages').style.display = 'flex';
    document.getElementById('chat-messages-container').innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-loader animate-spin"></i> Načítavam...</div>';
    lastMessageCount = 0;
    loadMessages();
}

async function loadMessages(unlockPassword = '') {
    if (!activeChatId || activeChatId === "multi") return;
    try {
        let url = `api_messages.php?action=fetch_messages&contact_id=${activeChatId}`;
        if (unlockPassword) url += `&pwd_unlock=${encodeURIComponent(unlockPassword)}`;
        
        const resp = await fetch(url);
        const data = await resp.json();
        const container = document.getElementById('chat-messages-container');
        
        if (data.messages.length !== lastMessageCount || lastMessageCount === 0 || unlockPassword) {
            container.innerHTML = '';
            data.messages.forEach(m => {
                const isSent = parseInt(m.sender_id) !== parseInt(activeChatId);
                const div = document.createElement('div');
                div.className = `msg-bubble ${isSent ? 'sent' : 'received'} ${m.is_safe == 1 ? 'safe' : ''}`;
                
                if (m.is_locked) {
                    div.innerHTML = `
                        <div style="font-style:italic; opacity:0.8; margin-bottom:5px;"><i class="ti ti-lock"></i> Správa chránená heslom</div>
                        <button class="btn btn-sm btn-primary" style="padding:2px 8px; font-size:0.7rem;" onclick="promptUnlock()">Odomknúť</button>
                    `;
                } else if (m.message === '[REVEAL_REQUIRED]') {
                    div.innerHTML = `
                        <div style="font-style:italic; opacity:0.8; margin-bottom:5px;"><i class="ti ti-lock-open"></i> SafeNote (bez hesla)</div>
                        <button class="btn btn-sm btn-primary" style="padding:2px 8px; font-size:0.7rem;" onclick="revealSafeNote()">Zobraziť správu</button>
                    `;
                } else if (m.message === '[INCORRECT PASSWORD]') {
                    div.innerHTML = `<span style="color:var(--red);">Nesprávne heslo!</span> <button class="btn btn-sm" style="padding:0 4px;" onclick="promptUnlock()">Skúsiť znova</button>`;
                } else {
                    let content = `<div style="word-break:break-word;">${m.message}</div>`;
                    if (m.file_path) {
                        const isImg = /\.(jpg|jpeg|png|gif|webp)$/i.test(m.file_path);
                        if (isImg) {
                            content += `<div style="margin-top:10px;"><img src="${m.file_path}" style="max-width:100%; border-radius:12px; border:1px solid rgba(255,255,255,0.1); cursor:pointer;" onclick="window.open('${m.file_path}')"></div>`;
                        } else {
                            content += `<div style="margin-top:10px;"><a href="${m.file_path}" target="_blank" style="display:inline-flex; align-items:center; gap:8px; color:var(--accent-2); text-decoration:none; background:rgba(0,0,0,0.2); padding:6px 12px; border-radius:8px; font-size:0.75rem;"><i class="ti ti-download"></i> Príloha</a></div>`;
                        }
                    }
                    div.innerHTML = content;
                    
                    if (m.is_safe && !isSent) {
                        div.innerHTML += `<div style="font-size:0.6rem; opacity:0.5; margin-top:4px;">Pozreté ${m.view_count}/${m.max_views}x</div>`;
                    }
                }
                container.appendChild(div);
            });
            container.scrollTop = container.scrollHeight;
            lastMessageCount = data.messages.length;
        }
    } catch (e) { console.error('Msg load error:', e); }
}

function revealSafeNote() {
    loadMessages('REVEAL');
}

function promptUnlock() {
    crmPrompt("Odomknúť SafeNote", "Zadaj heslo k tejto správe:", (pwd) => {
        if (pwd) loadMessages(pwd);
    });
    const input = document.getElementById('crm-modal-input');
    input.type = 'password'; // Force password for unlock
    document.getElementById('toggle-p-modal').style.display = 'flex'; // Show eye for unlock
    // Reset icon to eye
    const icon = document.querySelector('#toggle-p-modal i');
    icon.classList.remove('ti-eye-off');
    icon.classList.add('ti-eye');
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('chat-file-name').innerHTML = '<i class="ti ti-paperclip"></i> ' + file.name;
        document.getElementById('chat-file-preview').style.display = 'flex';
    }
}

function clearFileSelection() {
    document.getElementById('chat-file-input').value = '';
    document.getElementById('chat-file-preview').style.display = 'none';
}

async function sendMessage() {
    const input = document.getElementById('chat-msg-input');
    const fileInput = document.getElementById('chat-file-input');
    const msg = input.value.trim();
    const hasFile = fileInput.files && fileInput.files[0];

    if (!msg && !hasFile) return;

    const isSafe = document.getElementById('chat-safe-mode').checked ? 1 : 0;
    const maxViews = document.getElementById('chat-max-views').value;
    const password = document.getElementById('chat-safe-password').value;

    const rids = activeChatId === "multi" ? Array.from(multiRecipientIds) : [activeChatId];
    if (rids.length === 0) return;

    const formData = new FormData();
    formData.append('recipient_ids', rids.join(','));
    formData.append('message', msg);
    formData.append('is_safe', isSafe);
    formData.append('max_views', maxViews);
    formData.append('password', password);
    formData.append('csrf_token', '<?= csrf_token() ?>');
    if (hasFile) formData.append('chat_file', fileInput.files[0]);

    // UI Reset
    const chatInputEl = document.getElementById('chat-msg-input');
    chatInputEl.value = '';
    chatInputEl.style.height = 'auto';
    document.getElementById('chat-safe-password').value = ''; 
    document.getElementById('chat-safe-mode').checked = false; // Auto-reset toggle
    clearFileSelection();

    try {
        await fetch('api_messages.php?action=send', { method: 'POST', body: formData });
        if (activeChatId !== "multi") {
            lastMessageCount = 0;
            loadMessages();
        } else {
            crmAlert('Hromadná správa', 'Správa bola odoslaná všetkým vybraným kolegom.');
            showContacts();
            toggleMultiMode(); // Turn off multi mode
        }
    } catch (e) { console.error('Send error:', e); }
}

function startPolling() {
    if (chatRefreshInterval) clearInterval(chatRefreshInterval);
    chatRefreshInterval = setInterval(() => {
        if (activeChatId && activeChatId !== "multi") loadMessages();
        loadContacts();
    }, 4000);
}

function stopPolling() {
    if (chatRefreshInterval) clearInterval(chatRefreshInterval);
    chatRefreshInterval = null;
}

// Global unread check (Fast)
loadContacts(); // Check immediately on load
setInterval(() => { if (!chatRefreshInterval) loadContacts(); }, 8000);

// Auto-expand textarea
const chatInputEl = document.getElementById('chat-msg-input');
chatInputEl.oninput = function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
};

// Enter key to send
chatInputEl.onkeydown = function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
};

// --- Mobile Keyboard Fix ---
if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', () => {
        const offset = window.innerHeight - window.visualViewport.height;
        const footer = document.getElementById('chat-footer-el');
        
        // Only apply manual padding offset for iOS devices where the layout viewport 
        // doesn't shrink automatically with the keyboard.
        if (offset > 50 && /iPhone|iPad|iPod/.test(navigator.userAgent)) {
            footer.style.paddingBottom = (offset + 10) + 'px';
        } else {
            footer.style.paddingBottom = ''; // Reset to CSS default
        }
        
        // Scroll to bottom after layout shift
        setTimeout(() => {
            const container = document.getElementById('chat-messages-container');
            if(container) container.scrollTop = container.scrollHeight;
        }, 100);
    });
}
</script>
<script>
function checkUnreadEmails() {
    fetch('api_email_count.php')
        .then(r => r.json())
        .then(data => {
            if (data && typeof data.count !== 'undefined') {
                const navLink = document.querySelector('a[href*="email.php"]');
                if (navLink) {
                    let badge = navLink.querySelector('.nav-badge');
                    if (data.count > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'nav-badge';
                            navLink.appendChild(badge);
                        }
                        badge.innerText = data.count;
                    } else if (badge) {
                        badge.remove();
                    }
                }
            }
        })
        .catch(e => console.error('Email count error:', e));
}
setTimeout(checkUnreadEmails, 1500);
setInterval(checkUnreadEmails, 60000);
</script>
    </script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
