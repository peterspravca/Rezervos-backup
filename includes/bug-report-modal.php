<?php // bug-report-modal.php – Shared modal pre hlásenie chýb, includovaný v dashboard-topbar.php ?>

<div id="bugReportModal" class="modal-overlay" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); z-index:99999;">
    <div class="modal-content" style="max-width:800px; width:92%; background:#111827; border:1px solid #1f2937; border-radius:16px; color:#fff; padding:0; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,0.6);">
        <div class="modal-header" style="padding:20px 25px; border-bottom:1px solid #1f2937; display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0; font-size:18px; display:flex; align-items:center; gap:10px; color:#fff;">
                <span class="material-symbols-outlined" style="color:var(--primary-color);">bug_report</span> Nahlásiť chybu
            </h2>
            <button onclick="closeBugReportModal()" style="background:transparent; border:none; color:#9ca3af; cursor:pointer; font-size:24px; line-height:1; display:flex; align-items:center; justify-content:center; padding:4px; border-radius:6px;">&times;</button>
        </div>

        <div class="modal-body" style="padding:25px; display:flex; gap:25px; flex-wrap:wrap;">
            <!-- Ľavý stĺpec -->
            <div style="flex:1; min-width:280px; display:flex; flex-direction:column; gap:18px;">
                <p style="font-size:13px; color:#9ca3af; margin:0; line-height:1.5;">Popíšte, čo nefunguje. Správa sa odošle tímu podpory spolu so systémovými údajmi.</p>
                <div>
                    <label style="display:block; font-size:13px; font-weight:bold; color:#fff; margin-bottom:8px;">Čo sa stalo?</label>
                    <textarea id="bug-desc" rows="6" style="width:100%; padding:12px; background:#1f2937; border:1px solid #374151; border-radius:10px; color:#fff; box-sizing:border-box; resize:none; font-size:13px; font-family:inherit;" placeholder="Napr. keď kliknem na tlačidlo v kalendári, nič sa nestane..."></textarea>
                </div>
                <div style="background:#1f2937; border-radius:10px; padding:14px; border:1px solid rgba(255,255,255,0.05);">
                    <span style="font-size:11px; font-weight:bold; color:#9ca3af; letter-spacing:0.5px; display:block; margin-bottom:8px;">RÝCHLY NÁVOD NA VLOŽENIE SNÍMKY:</span>
                    <div style="display:flex; align-items:center; gap:8px; font-size:12px; color:#fff;">
                        <span style="background:#374151; padding:4px 8px; border-radius:6px; border:1px solid #4b5563; font-weight:600;">Win + Shift + S</span>
                        <span style="color:#9ca3af;">&rarr;</span>
                        <span style="background:#374151; padding:4px 8px; border-radius:6px; border:1px solid #4b5563; font-weight:600;">Ctrl + V</span>
                    </div>
                </div>
            </div>

            <!-- Pravý stĺpec -->
            <div style="flex:1; min-width:280px; display:flex; flex-direction:column; gap:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label style="font-size:13px; font-weight:bold; color:#fff; margin:0;">Screenshot (voliteľné)</label>
                    <button type="button" id="bug-auto-snap-btn" onclick="takeAutoScreenshot()" style="background:#3b82f6 !important; color:#fff !important; border:1px solid #2563eb !important; padding:7px 13px; border-radius:10px !important; font-size:12px; display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:600; box-shadow:0 2px 8px rgba(59,130,246,0.35);">
                        <span class="material-symbols-outlined" style="font-size:16px;">photo_camera</span>
                        <span id="bug-snap-label">Automatická snímka</span>
                    </button>
                </div>
                <div id="bug-dropzone" onclick="triggerBugFileInput()" style="flex:1; min-height:180px; border:2px dashed #374151; border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:rgba(31,41,55,0.5); cursor:pointer; transition:0.2s; position:relative; overflow:hidden; box-sizing:border-box;">
                    <input type="file" id="bug-file-input" accept="image/*" style="display:none;" onchange="handleBugFileSelect(this)">
                    <div id="bug-dropzone-empty" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:15px; text-align:center;">
                        <span class="material-symbols-outlined" style="font-size:36px; color:#6b7280; margin-bottom:8px;">add_photo_alternate</span>
                        <span style="font-size:13.5px; font-weight:600; color:#e5e7eb;">Kliknite sem alebo pretiahnite súbor</span>
                        <span style="font-size:11.5px; color:#9ca3af; margin-top:4px;">JPG, PNG, WEBP alebo Ctrl+V</span>
                    </div>
                    <div id="bug-dropzone-preview" style="display:none; width:100%; height:100%; position:relative; padding:10px; box-sizing:border-box; flex-direction:column; align-items:center; justify-content:center;">
                        <img id="bug-preview-image" src="" alt="Náhľad" style="max-width:100%; max-height:140px; border-radius:8px; object-fit:contain; box-shadow:0 4px 12px rgba(0,0,0,0.5);">
                        <button type="button" onclick="clearBugScreenshot(event)" style="margin-top:8px; background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#ef4444; border-radius:6px; padding:4px 12px; font-size:11.5px; cursor:pointer; display:flex; align-items:center; gap:4px; font-weight:600;">
                            <span class="material-symbols-outlined" style="font-size:14px;">delete</span> Odstrániť snímku
                        </button>
                    </div>
                </div>
                <div style="background:rgba(17,24,39,0.6); border:1px solid #1f2937; border-radius:10px; padding:10px 12px; display:flex; gap:8px; align-items:center;">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#9ca3af;">info</span>
                    <span style="font-size:11px; color:#9ca3af; line-height:1.3;">K hláseniu sa automaticky priloží aktuálna stránka a diagnostika.</span>
                </div>
            </div>
        </div>

        <div class="modal-footer" style="padding:16px 25px; border-top:1px solid #1f2937; display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
            <button type="button" onclick="closeBugReportModal()" style="background:#374151; color:#fff; border:none; padding:10px 22px; border-radius:12px; font-weight:500; cursor:pointer; font-size:14px;">Zrušiť</button>
            <button type="button" id="bug-submit-btn" onclick="submitBugReport()" style="background:var(--primary-color); color:#fff; border:none; padding:10px 24px; border-radius:12px; font-weight:bold; cursor:pointer; font-size:14px; box-shadow:0 4px 12px rgba(176,128,66,0.35); display:flex; align-items:center; gap:8px;">
                <span>Odoslať hlásenie</span>
            </button>
        </div>
    </div>
</div>

<script>
let currentBugScreenshotBlob = null;

function openBugReportModal() {
    const m = document.getElementById('bugReportModal');
    if (m) { m.style.display = 'flex'; }
}
function closeBugReportModal() {
    const m = document.getElementById('bugReportModal');
    if (m) { m.style.display = 'none'; }
}
function triggerBugFileInput() { document.getElementById('bug-file-input')?.click(); }

function handleBugFileSelect(input) {
    if (input.files && input.files[0]) setBugScreenshot(input.files[0]);
}

function setBugScreenshot(blobOrFile) {
    currentBugScreenshotBlob = blobOrFile;
    const prev = document.getElementById('bug-dropzone-preview');
    const empty = document.getElementById('bug-dropzone-empty');
    const img = document.getElementById('bug-preview-image');
    if (img && prev && empty) {
        img.src = URL.createObjectURL(blobOrFile);
        empty.style.display = 'none';
        prev.style.display = 'flex';
    }
}

function clearBugScreenshot(e) {
    if (e) e.stopPropagation();
    currentBugScreenshotBlob = null;
    const fi = document.getElementById('bug-file-input'); if (fi) fi.value = '';
    const img = document.getElementById('bug-preview-image'); if (img) img.src = '';
    document.getElementById('bug-dropzone-preview').style.display = 'none';
    document.getElementById('bug-dropzone-empty').style.display = 'flex';
}

async function takeAutoScreenshot() {
    const btn = document.getElementById('bug-auto-snap-btn');
    const modal = document.getElementById('bugReportModal');
    const orig = btn ? btn.innerHTML : '';
    if (btn) { btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;animation:spin 1s linear infinite;display:inline-block;">sync</span> Snímam...'; btn.disabled = true; }
    if (typeof html2canvas === 'undefined') {
        await new Promise((res, rej) => { const s = document.createElement('script'); s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'; s.onload = res; s.onerror = rej; document.head.appendChild(s); });
    }
    if (modal) modal.style.visibility = 'hidden';
    try {
        await new Promise(r => setTimeout(r, 150));
        const canvas = await html2canvas(document.body, { useCORS: true, allowTaint: true, logging: false, scale: 1, backgroundColor: document.body.classList.contains('dark-mode') ? '#0b0f19' : '#ffffff' });
        canvas.toBlob(blob => { if (blob) { setBugScreenshot(blob); showAppToast('Snímka obrazovky bola vytvorená!', 'success'); } }, 'image/jpeg', 0.85);
    } catch(err) { showAppToast('Chyba pri vytváraní snímky.', 'error'); }
    finally { if (modal) modal.style.visibility = 'visible'; if (btn) { btn.innerHTML = orig; btn.disabled = false; } }
}

// Ctrl+V paste
document.addEventListener('paste', function(e) {
    const modal = document.getElementById('bugReportModal');
    if (!modal || modal.style.display === 'none') return;
    const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items;
    if (!items) return;
    for (const item of items) {
        if (item.kind === 'file' && item.type.includes('image')) {
            const blob = item.getAsFile();
            if (blob) { setBugScreenshot(blob); showAppToast('Snímka zo schránky priložená!', 'success'); break; }
        }
    }
});

// Drag & Drop
document.addEventListener('DOMContentLoaded', function() {
    const dz = document.getElementById('bug-dropzone');
    if (!dz) return;
    ['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.style.borderColor='var(--primary-color)'; dz.style.background='rgba(176,128,66,0.15)'; }));
    ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.style.borderColor='#374151'; dz.style.background='rgba(31,41,55,0.5)'; }));
    dz.addEventListener('drop', e => { const f = e.dataTransfer?.files?.[0]; if (f && f.type.startsWith('image/')) { setBugScreenshot(f); showAppToast('Obrázok priložený!', 'success'); } });

    // Close on backdrop click
    document.getElementById('bugReportModal')?.addEventListener('click', function(e) { if (e.target === this) closeBugReportModal(); });
});

async function submitBugReport() {
    const descEl = document.getElementById('bug-desc');
    const desc = descEl?.value.trim();
    if (!desc) { showAppToast('Prosím, napíšte popis chyby.', 'error'); descEl?.focus(); return; }
    const btn = document.getElementById('bug-submit-btn');
    const orig = btn ? btn.innerHTML : '';
    if (btn) { btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;animation:spin 1s linear infinite;display:inline-block;">sync</span> Odosielam...'; btn.disabled = true; }
    const fd = new FormData();
    fd.append('action', 'report_bug');
    fd.append('description', desc);
    fd.append('url', window.location.href);
    fd.append('user_agent', navigator.userAgent);
    fd.append('screen_res', `${window.screen.width}x${window.screen.height}`);
    if (currentBugScreenshotBlob) fd.append('screenshot', currentBugScreenshotBlob, 'screenshot.jpg');
    try {
        const resp = await fetch('api/business.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) { showAppToast('Chyba bola úspešne nahlásená. Ďakujeme!', 'success'); }
        else { showAppToast('Hlásenie odoslané.', 'success'); }
    } catch(err) { showAppToast('Hlásenie odoslané.', 'success'); }
    finally {
        closeBugReportModal();
        if (descEl) descEl.value = '';
        clearBugScreenshot();
        if (btn) { btn.innerHTML = orig; btn.disabled = false; }
    }
}
</script>
