<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
$pageTitle = 'Zabezpečenie - ' . BRAND_NAME;
$currentPage = 'zabezpecenie';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Zabezpečenie'; $headerIcon = 'shield'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">

<?php include 'components/security.php'; ?>

  </div>
</div>

<!-- PROMPT MODAL (pre overovací kód) -->
<div id="prompt-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:380px;width:90%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);text-align:center;">
    <span class="material-symbols-outlined" style="font-size:40px;color:var(--primary-color);">lock</span>
    <h3 style="margin:12px 0 8px 0;font-size:18px;font-weight:800;">Zadajte overovací kód</h3>
    <p style="font-size:13px;color:var(--text-secondary);margin:0 0 18px 0;">Kód bol odoslaný na váš e-mail.</p>
    <input type="text" id="prompt-input" placeholder="Zadajte 6-miestny kód..." maxlength="10" style="width:100%;padding:12px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--bg-color);color:var(--text-primary);font-size:16px;text-align:center;letter-spacing:4px;box-sizing:border-box;margin-bottom:14px;font-weight:700;">
    <div style="display:flex;gap:10px;">
      <button type="button" onclick="closePromptModal()" class="btn-secondary" style="flex:1;padding:11px;border-radius:10px;">Zrušiť</button>
      <button type="button" onclick="submitPromptModal()" class="btn-primary" style="flex:1;padding:11px;border-radius:10px;font-weight:700;">Potvrdiť</button>
    </div>
  </div>
</div>

<style>
/* Toggle switch */
.switch { position:relative;display:inline-block;width:50px;height:26px; }
.switch input { opacity:0;width:0;height:0; }
.slider { position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#ccc;transition:.3s;border-radius:26px; }
.slider:before { position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background-color:white;transition:.3s;border-radius:50%; }
input:checked + .slider { background-color:var(--primary-color); }
input:checked + .slider:before { transform:translateX(24px); }
</style>

<script>
function showToast(msg, type='success') {
  if (typeof showAppToast==='function'){showAppToast(msg,type);return;}
  let c=document.getElementById('_tc');if(!c){c=document.createElement('div');c.id='_tc';c.style.cssText='position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';document.body.appendChild(c);}
  const t=document.createElement('div');t.style.cssText=`background:${type==='error'?'#ef4444':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;opacity:0;transform:translateY(-15px);transition:all 0.3s;`;
  t.innerText=msg;c.appendChild(t);setTimeout(()=>{t.style.opacity='1';t.style.transform='translateY(0)';},10);setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(-15px)';setTimeout(()=>t.remove(),300);},3500);
}

async function loadSecuritySettings() {
  try {
    const fd = new FormData(); fd.append('action','get_security_settings');
    let res = await fetch('api/profile_security.php', { method: 'POST', body: fd });
    let data = await res.json();
    if (data.success && data.settings) {
      const s = data.settings;
      const toggle2 = document.getElementById('toggle-2fa');
      if (toggle2) toggle2.checked = !!s.two_factor_enabled;
      const toggle3 = document.getElementById('toggle-3fa-questions');
      if (toggle3) toggle3.checked = !!s.two_factor_questions;
      if (document.getElementById('sq-q1')) document.getElementById('sq-q1').value = s.security_q1||'';
      if (document.getElementById('sq-a1')) document.getElementById('sq-a1').value = s.security_a1||'';
      if (document.getElementById('sq-q2')) document.getElementById('sq-q2').value = s.security_q2||'';
      if (document.getElementById('sq-a2')) document.getElementById('sq-a2').value = s.security_a2||'';
      if (document.getElementById('sq-q3')) document.getElementById('sq-q3').value = s.security_q3||'';
      if (document.getElementById('sq-a3')) document.getElementById('sq-a3').value = s.security_a3||'';
    }
  } catch(e) { console.error('Chyba načítania bezpečnostných nastavení:', e); }
}

async function toggle2FA(enabled) {
  const fd = new FormData(); fd.append('action','toggle_2fa'); fd.append('enabled', enabled?1:0);
  try {
    let res = await fetch('api/profile_security.php', { method:'POST', body:fd });
    let data = await res.json();
    if (data.success) showToast(data.message||'Nastavenie uložené.','success');
    else { showToast(data.error||'Chyba','error'); document.getElementById('toggle-2fa').checked = !enabled; }
  } catch(e) { showToast('Chyba siete','error'); document.getElementById('toggle-2fa').checked = !enabled; }
}

function toggleSecurityQuestionsSetup(show) {
  const box = document.getElementById('security-questions-setup');
  if (!box) return;
  if (show === undefined) {
    box.style.display = (box.style.display==='none'||!box.style.display) ? 'block' : 'none';
  } else {
    box.style.display = show ? 'block' : 'none';
  }
  if (box.style.display==='block') box.scrollIntoView({ behavior:'smooth', block:'nearest' });
}

async function toggle3FAQuestions(enabled) {
  const fd = new FormData(); fd.append('action','toggle_2fa_questions'); fd.append('enabled', enabled?1:0);
  try {
    let res = await fetch('api/profile_security.php', { method:'POST', body:fd });
    let data = await res.json();
    if (data.success) showToast(data.message||'Nastavenie uložené.','success');
    else { showToast(data.error||'Chyba','error'); document.getElementById('toggle-3fa-questions').checked = false; toggleSecurityQuestionsSetup(true); }
  } catch(e) { showToast('Chyba siete','error'); document.getElementById('toggle-3fa-questions').checked = !enabled; }
}

async function saveSecurityQuestions(e) {
  e.preventDefault();
  const btn = document.getElementById('save-questions-btn');
  const statusMsg = document.getElementById('questions-status-msg');
  const q1=document.getElementById('sq-q1').value.trim(), a1=document.getElementById('sq-a1').value.trim();
  const q2=document.getElementById('sq-q2').value.trim(), a2=document.getElementById('sq-a2').value.trim();
  const q3=document.getElementById('sq-q3').value.trim(), a3=document.getElementById('sq-a3').value.trim();
  if (!q1||!a1||!q2||!a2||!q3||!a3) { showToast('Všetky 3 otázky aj odpovede musia byť vyplnené.','error'); return; }
  const fd = new FormData();
  fd.append('action','save_security_questions');
  fd.append('q1',q1);fd.append('a1',a1);fd.append('q2',q2);fd.append('a2',a2);fd.append('q3',q3);fd.append('a3',a3);fd.append('auto_enable','1');
  if (btn) btn.disabled = true;
  try {
    let res = await fetch('api/profile_security.php', { method:'POST', body:fd });
    let data = await res.json();
    if (data.success) {
      showToast(data.message||'Bezpečnostné otázky uložené!','success');
      const toggle3 = document.getElementById('toggle-3fa-questions');
      if (toggle3) toggle3.checked = true;
      if (statusMsg) { statusMsg.textContent='Otázky sú aktívne uložené'; setTimeout(()=>{statusMsg.textContent='';},4000); }
      setTimeout(()=>{ toggleSecurityQuestionsSetup(false); }, 1200);
    } else showToast(data.error||'Chyba pri ukladaní otázok.','error');
  } catch(err) { showToast('Chyba pripojenia k serveru.','error'); }
  finally { if (btn) btn.disabled = false; }
}

let promptResolve = null;
function openPromptModal() {
  document.getElementById('prompt-input').value = '';
  document.getElementById('prompt-modal').style.display = 'flex';
  return new Promise((resolve) => { promptResolve = resolve; });
}
function closePromptModal() {
  document.getElementById('prompt-modal').style.display = 'none';
  if (promptResolve) promptResolve(null);
}
function submitPromptModal() {
  let val = document.getElementById('prompt-input').value;
  if (val.length >= 5) { document.getElementById('prompt-modal').style.display='none'; if (promptResolve) promptResolve(val); }
  else showToast('Zadajte platný kód','error');
}
document.getElementById('prompt-input')?.addEventListener('keydown', (e) => { if (e.key==='Enter') submitPromptModal(); });

async function changePassword(e) {
  e.preventDefault();
  const current = document.getElementById('current_password').value;
  const newp = document.getElementById('new_password').value;
  const conf = document.getElementById('confirm_password').value;
  if (newp !== conf) { showToast('Nové heslá sa nezhodujú','error'); return; }
  if (newp.length < 6) { showToast('Nové heslo musí mať aspoň 6 znakov','error'); return; }
  const fd_req = new FormData(); fd_req.append('action','request_password_change');
  let res_req = await fetch('api/profile_security.php', { method:'POST', body:fd_req });
  let data_req = await res_req.json();
  if (data_req.success) {
    let code = await openPromptModal();
    if (code) {
      const fd = new FormData();
      fd.append('action','change_password'); fd.append('current_password',current); fd.append('new_password',newp); fd.append('code',code);
      let res = await fetch('api/profile_security.php', { method:'POST', body:fd });
      let data = await res.json();
      if (data.success) { showToast('Heslo bolo úspešne zmenené','success'); e.target.reset(); }
      else showToast(data.error||'Chyba pri zmene hesla','error');
    }
  } else showToast(data_req.error||'Chyba pri vyžiadaní kódu','error');
}

document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.body.classList.contains('dark-mode');
  const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
  init();
});
function init() { loadSecuritySettings(); }
</script>
</body>
</html>
