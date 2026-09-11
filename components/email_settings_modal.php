<!-- Email Settings Modal Component (Samostatný modul pre prepojenie schránky) -->
<div id="emailSettingsModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:10000; align-items:center; justify-content:center; backdrop-filter:blur(6px); padding:20px; box-sizing:border-box;">
    <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:16px; width:100%; max-width:640px; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0, 0, 0, 0.4); position:relative; color:var(--text-primary); padding:28px 30px;">
        <!-- Close Button -->
        <button type="button" onclick="document.getElementById('emailSettingsModal').style.display='none'" style="position:absolute; top:20px; right:20px; width:34px; height:34px; border-radius:8px; background:var(--bg-color); border:1px solid var(--border-color); cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--text-secondary); transition:all 0.2s ease;" onmouseover="this.style.color='var(--text-primary)'; this.style.borderColor='var(--primary-color)';" onmouseout="this.style.color='var(--text-secondary)'; this.style.borderColor='var(--border-color)';">
            <span class="material-symbols-outlined" style="font-size:20px;">close</span>
        </button>

        <!-- Header -->
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px; padding-right:40px;">
            <div style="width:46px; height:46px; border-radius:10px; background:rgba(176, 128, 66, 0.12); color:var(--primary-color); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:26px;">mail_lock</span>
            </div>
            <div>
                <h2 style="margin:0 0 3px 0; font-size:18px; font-weight:800; color:var(--text-primary);">Prepojiť existujúci e-mail</h2>
                <p style="margin:0; font-size:12.5px; color:var(--text-secondary); line-height:1.4;">Pripojte svoju vlastnú schránku (Gmail, Seznam, Zoznam, WebSupport, vlastnú doménu a pod.) pre odosielanie a prijímanie e-mailov.</p>
            </div>
        </div>

        <!-- Quick Presets -->
        <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:10px; padding:12px 14px; margin-bottom:16px;">
            <div style="font-size:11.5px; font-weight:700; color:var(--text-secondary); margin-bottom:8px; display:flex; align-items:center; gap:4px; text-transform:uppercase; letter-spacing:0.5px;">
                <span class="material-symbols-outlined" style="font-size:15px; color:var(--primary-color);">auto_fix_high</span> Rýchle predvoľby poskytovateľov:
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                <button type="button" onclick="applyEmailPreset('gmail')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                    <span style="color:#ea4335; font-weight:bold;">G</span> Gmail
                </button>
                <button type="button" onclick="applyEmailPreset('seznam')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                    <span style="color:#cc0000; font-weight:bold;">S</span> Seznam.cz
                </button>
                <button type="button" onclick="applyEmailPreset('websupport')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    WebSupport
                </button>
                <button type="button" onclick="applyEmailPreset('webglobe')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    Webglobe / USR
                </button>
                <button type="button" onclick="applyEmailPreset('zoznam')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    Zoznam.sk
                </button>
                <button type="button" onclick="applyEmailPreset('centrum')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    Centrum.sk
                </button>
                <button type="button" onclick="applyEmailPreset('azet')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    Azet.sk
                </button>
                <button type="button" onclick="applyEmailPreset('outlook')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    Outlook / 365
                </button>
                <button type="button" onclick="applyEmailPreset('custom')" class="btn-preset" style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:6px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    Vlastná doména
                </button>
            </div>
        </div>

        <!-- Dynamic Provider Hint -->
        <div id="email-provider-hint" style="display:none; background:rgba(176, 128, 66, 0.08); border-left:3px solid var(--primary-color); border-radius:6px; padding:10px 14px; font-size:12.5px; color:var(--text-primary); margin-bottom:16px; line-height:1.4;">
        </div>

        <!-- IMAP CARD -->
        <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; padding:16px 18px; margin-bottom:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color); font-size:18px;">inbox</span>
                <strong style="font-size:13.5px; color:var(--text-primary);">Prichádzajúca pošta (IMAP)</strong>
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">Váš prihlasovací e-mail *</label>
                    <input type="email" id="imap-user" placeholder="vas@salon.sk" oninput="autoDetectEmailProvider(this.value); syncEmailCredentials();" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">Heslo k e-mailu *</label>
                    <div style="position:relative;">
                        <input type="password" id="imap-pass" placeholder="Zadajte heslo schránky" oninput="syncEmailCredentials()" style="width:100%; box-sizing:border-box; padding:9px 36px 9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                        <span class="material-symbols-outlined" onclick="togglePassVisibility('imap-pass', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:18px; color:var(--text-secondary); cursor:pointer; user-select:none;">visibility_off</span>
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">IMAP Server</label>
                    <input type="text" id="imap-server" placeholder="napr. imap.gmail.com alebo mail.domena.sk" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">Port</label>
                    <input type="number" id="imap-port" value="993" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                </div>
            </div>
        </div>

        <!-- SYNC TOGGLE -->
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px; padding:0 4px;">
            <input type="checkbox" id="sync-smtp-creds" checked onchange="toggleSyncSmtp(this.checked)" style="width:16px; height:16px; accent-color:var(--primary-color); cursor:pointer;">
            <label for="sync-smtp-creds" style="font-size:12.5px; color:var(--text-primary); font-weight:600; cursor:pointer;">
                Použiť rovnaké prihlasovacie meno a heslo aj pre odosielanie (SMTP)
            </label>
        </div>

        <!-- SMTP CARD -->
        <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; padding:16px 18px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color); font-size:18px;">send</span>
                <strong style="font-size:13.5px; color:var(--text-primary);">Odchádzajúca pošta (SMTP)</strong>
            </div>
            
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px; margin-bottom:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">SMTP Server</label>
                    <input type="text" id="smtp-server" placeholder="napr. smtp.gmail.com alebo mail.domena.sk" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">Port</label>
                    <input type="number" id="smtp-port" value="465" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                </div>
            </div>

            <div id="smtp-custom-creds" style="display:none; grid-template-columns: 1fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">SMTP Prihlasovacie meno</label>
                    <input type="email" id="smtp-user" placeholder="vas@salon.sk" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px;">SMTP Heslo</label>
                    <input type="password" id="smtp-pass" placeholder="Zadajte heslo" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13px; outline:none;">
                </div>
            </div>
        </div>

        <!-- Security Note -->
        <div style="display:flex; align-items:center; gap:8px; font-size:11.5px; color:var(--text-secondary); margin-bottom:20px;">
            <span class="material-symbols-outlined" style="font-size:16px; color:#10b981;">lock</span>
            <span>Vaše prístupové údaje sú bezpečne šifrované v databáze pomocou algoritmu AES-256.</span>
        </div>

        <!-- Modal Footer Buttons -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <button type="button" id="btn-disconnect-email" onclick="disconnectEmailAccount()" class="btn" style="display:<?= (!empty($is_configured)) ? 'inline-flex' : 'none' ?>; align-items:center; gap:6px; padding:10px 16px; font-size:13px; border-radius:6px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.3); color:#ef4444; cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:16px;">link_off</span>
                    Odpojiť schránku
                </button>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="document.getElementById('emailSettingsModal').style.display='none'" class="btn" style="padding:10px 18px; font-size:13px; border-radius:6px; background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer;">
                    Zrušiť
                </button>
                <button type="button" id="btn-save-email-settings" onclick="saveEmailSettings()" class="btn-primary" style="padding:10px 22px; font-size:13px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">cloud_sync</span>
                    <span>Uložiť a pripojiť schránku</span>
                </button>
            </div>
        </div>
    </div>
</div>
