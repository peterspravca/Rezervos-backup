<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('settings');
$pageTitle = 'Moja Prevádzka - ' . BRAND_NAME;
$currentPage = 'prevadzka';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Moja Prevádzka'; $headerIcon = 'business_center'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">

<?php if (empty($_SESSION['is_employee'])): ?>
    <div style="padding:25px 30px 0 30px;">
    <a href="dashboard-prevadzky.php" style="display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,rgba(176,128,66,0.14),rgba(176,128,66,0.05));border:1.5px solid rgba(176,128,66,0.35);border-radius:16px;padding:18px 22px;text-decoration:none;transition:all 0.2s;box-shadow:0 2px 10px rgba(176,128,66,0.08);">
        <div style="width:52px;height:52px;border-radius:14px;background:var(--primary-color);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(176,128,66,0.35);">
            <span class="material-symbols-outlined" style="font-size:26px;">store</span>
        </div>
        <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:8px;">
                <strong style="font-size:16px;color:var(--text-primary);">Moje prevádzky</strong>
                <span style="font-size:10px;font-weight:800;letter-spacing:0.03em;padding:2px 8px;border-radius:6px;background:var(--primary-color);color:#fff;text-transform:uppercase;">Nové</span>
            </div>
            <p style="margin:3px 0 0 0;font-size:13px;color:var(--text-secondary);">Vlastníte viac prevádzok? Prepojte účty a zobrazte si súhrnné tržby a rezervácie na jednom mieste.</p>
        </div>
        <span class="btn-primary" style="padding:10px 18px;font-size:13px;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;">
            Zobraziť <span class="material-symbols-outlined" style="font-size:17px;">arrow_forward</span>
        </span>
    </a>
    </div>
<?php endif; ?>

<?php include 'components/establishment.php'; ?>

  </div>
</div>

<style>
/* Toggle switch (needed for DPH toggle) */
.switch { position:relative;display:inline-block;width:50px;height:26px; }
.switch input { opacity:0;width:0;height:0; }
.slider { position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#ccc;transition:.3s;border-radius:26px; }
.slider:before { position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background-color:white;transition:.3s;border-radius:50%; }
input:checked + .slider { background-color:var(--primary-color); }
input:checked + .slider:before { transform:translateX(24px); }
/* Profile grid layout */
.profile-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:20px; }
.profile-grid .full-width { grid-column:1/-1; }
@media (max-width:768px){ .profile-grid { grid-template-columns:1fr; } }
/* Premium card */
.premium-card { background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;overflow:hidden; }
.premium-card-header { padding:18px 20px;border-bottom:1px solid var(--border-color);background:var(--bg-color); }
.premium-card-title { margin:0;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;color:var(--text-primary); }
.premium-card-body { padding:20px; }
/* Form groups */
.form-group { display:flex;flex-direction:column;gap:6px; }
.form-group label { font-size:13px;font-weight:600;color:var(--text-secondary); }
.form-group input, .form-group select, .form-group textarea {
  padding:11px 14px;border:1px solid var(--border-color);border-radius:10px;
  background:var(--input-bg,var(--bg-color));color:var(--text-primary);font-size:14px;
  width:100%;box-sizing:border-box;
}
.form-group input:focus, .form-group select:focus { outline:none;border-color:var(--primary-color); }
</style>

<script>
function showToast(msg, type='success') {
  if (typeof showAppToast==='function'){showAppToast(msg,type);return;}
  let c=document.getElementById('_tc');if(!c){c=document.createElement('div');c.id='_tc';c.style.cssText='position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';document.body.appendChild(c);}
  const t=document.createElement('div');t.style.cssText=`background:${type==='error'?'#ef4444':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;opacity:0;transform:translateY(-15px);transition:all 0.3s;`;
  t.innerText=msg;c.appendChild(t);setTimeout(()=>{t.style.opacity='1';t.style.transform='translateY(0)';},10);setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(-15px)';setTimeout(()=>t.remove(),300);},3500);
}

function val(id){ const el=document.getElementById(id); return el?el.value:''; }
function setVal(id, v){ const el=document.getElementById(id); if(el) el.value=v||''; }
function setCheck(id, v){ const el=document.getElementById(id); if(el) el.checked=!!v; }
function getCheck(id){ const el=document.getElementById(id); return el?el.checked:false; }

async function loadEstablishmentInfo() {
  try {
    const fd=new FormData(); fd.append('action','get_profile');
    const res=await fetch('api/business.php',{method:'POST',body:fd});
    const data=await res.json();
    if (!data.success) { showToast(data.error||'Chyba načítania','error'); return; }
    const p=data.profile||{};
    setVal('est-name', p.name);
    setVal('est-legal-name', p.legal_name);
    setVal('est-owner-name', p.owner_name);
    setVal('est-ico', p.ico);
    setVal('est-dic', p.dic);
    setVal('est-ic-dph', p.ic_dph);
    setCheck('est-is-vat-payer', p.is_vat_payer);
    setVal('est-address', p.address);
    setVal('est-city', p.city);
    setVal('est-phone', p.phone);
    setVal('est-billing-email', p.billing_email);
    setVal('est-deposit-iban', p.deposit_iban);
    setVal('est-capacity', p.capacity_per_slot);
    // amenities
    const amenities = p.amenities ? (typeof p.amenities==='string' ? JSON.parse(p.amenities) : p.amenities) : [];
    document.querySelectorAll('input[name="amenities[]"]').forEach(cb => {
      cb.checked = amenities.includes(cb.value);
    });
    // subscription info
    updateEstTierUI(p.subscription_tier, p.bookings_this_month, p.booking_limit, p.main_service_name);
  } catch(e) { showToast('Chyba pripojenia k serveru','error'); console.error(e); }
}

async function loadEmployeesPreview() {
  const empContainer = document.getElementById('est-employees-list');
  if (!empContainer) return;
  try {
    const fd = new FormData(); fd.append('action', 'get_team');
    const res = await fetch('api/team.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success || !data.team || data.team.length === 0) {
      empContainer.innerHTML = '<p style="font-size: 13px; color: var(--text-secondary); margin: 0; padding: 15px 0;">Zatiaľ nemáte pridaných žiadnych zamestnancov.</p>';
      return;
    }
    empContainer.innerHTML = data.team.map(emp => {
      const isOwner = (parseInt(emp.is_owner) === 1);
      const isActive = (parseInt(emp.is_active) === 1);
      const initial = emp.name ? escapeHtml(emp.name).charAt(0).toUpperCase() : '?';
      return `
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 10px; gap: 10px;">
          <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0;">
              ${emp.avatar_url ? `
                  <img src="${emp.avatar_url}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0;" alt="${escapeHtml(emp.name)}">
              ` : `
                  <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--primary-color); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">
                      ${initial}
                  </div>
              `}
              <div style="min-width: 0;">
                  <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                      <strong style="font-size: 13.5px; color: var(--text-primary);">${escapeHtml(emp.name)}</strong>
                      ${isOwner ? '<span style="font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: rgba(176, 128, 66, 0.15); color: var(--primary-color);">Majiteľ</span>' : ''}
                  </div>
                  <span style="font-size: 12px; color: var(--text-secondary); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(emp.title || (isOwner ? 'Majiteľ / Vlastník' : 'Zamestnanec'))}</span>
              </div>
          </div>
          <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
              <a href="dashboard-tym.php" class="btn-secondary" style="padding: 5px 12px; font-size: 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; text-decoration: none;">
                  <span class="material-symbols-outlined" style="font-size: 15px;">edit</span>
                  <span>Upraviť</span>
              </a>
              <span class="material-symbols-outlined" style="font-size: 18px; color: ${isActive ? '#10b981' : '#9ca3af'};" title="${isActive ? 'Aktívny' : 'Neaktívny'}">${isActive ? 'check_circle' : 'do_not_disturb_on'}</span>
          </div>
      </div>`;
    }).join('');
  } catch (e) {
    empContainer.innerHTML = '<p style="font-size: 13px; color: #ef4444; margin: 0;">Zoznam zamestnancov sa nepodarilo načítať.</p>';
    console.error(e);
  }
}

function updateEstTierUI(tier, used, limit, serviceName) {
  const badge = document.getElementById('est-tier-badge');
  const planName = document.getElementById('est-plan-name');
  const limitText = document.getElementById('est-limit-text');
  const limitBar = document.getElementById('est-limit-bar');
  const serviceEl = document.getElementById('est-main-service-name');
  if (badge) badge.textContent = (tier||'FREE').toUpperCase();
  if (planName) {
    const names = {free:'FREE – Základný',start:'START – Štandardný',pro:'PRO – Pokročilý',vip:'VIP – ELITE'};
    planName.textContent = names[tier]||'FREE – Základný';
  }
  // Limity rezervacii podla balika
  const tierLimits = {free: 150, start: 300, pro: 1500, vip: -1};
  used = parseInt(used)||0;
  limit = (limit !== undefined && limit !== null && limit !== '') ? parseInt(limit) : (tierLimits[tier] !== undefined ? tierLimits[tier] : 50);
  if (limitText) limitText.textContent = `${used} / ${limit==-1?'∞':limit}`;
  const pct = limit==-1 ? 0 : Math.min(100, Math.round(used/limit*100));
  if (limitBar) { limitBar.style.width=pct+'%'; limitBar.style.background=pct>85?'#ef4444':'var(--primary-color)'; }
  // feature locks
  const urlIcon = document.getElementById('icon-custom-url');
  const confirmIcon = document.getElementById('icon-auto-confirm');
  const urlLocked = (tier==='free');
  const confirmLocked = (tier==='free'||tier==='start');
  if (urlIcon) { urlIcon.textContent = urlLocked ? 'lock' : 'check_circle'; urlIcon.style.color = urlLocked ? 'var(--text-secondary)' : '#10b981'; }
  if (confirmIcon) { confirmIcon.textContent = confirmLocked ? 'lock' : 'check_circle'; confirmIcon.style.color = confirmLocked ? 'var(--text-secondary)' : '#10b981'; }
  if (serviceEl && serviceName) serviceEl.textContent = serviceName;
}

async function saveEstablishmentInfo(e) {
  e.preventDefault();
  const btn = e.submitter||document.querySelector('#establishmentForm button[type="submit"]');
  if (btn) { btn.disabled=true; btn.innerHTML='<span class="material-symbols-outlined">sync</span> Ukladám...'; }
  const amenities = [];
  document.querySelectorAll('input[name="amenities[]"]:checked').forEach(cb=>amenities.push(cb.value));
  const fd=new FormData();
  fd.append('action','update_establishment');
  fd.append('name', val('est-name'));
  fd.append('legal_name', val('est-legal-name'));
  fd.append('owner_name', val('est-owner-name'));
  fd.append('ico', val('est-ico'));
  fd.append('dic', val('est-dic'));
  fd.append('ic_dph', val('est-ic-dph'));
  fd.append('is_vat_payer', getCheck('est-is-vat-payer')?1:0);
  fd.append('address', val('est-address'));
  fd.append('city', val('est-city'));
  fd.append('phone', val('est-phone'));
  fd.append('billing_email', val('est-billing-email'));
  fd.append('deposit_iban', val('est-deposit-iban'));
  fd.append('capacity_per_slot', val('est-capacity'));
  fd.append('amenities', JSON.stringify(amenities));
  try {
    const res=await fetch('api/business.php',{method:'POST',body:fd});
    const data=await res.json();
    if (data.success) showToast(data.message||'Údaje boli uložené!','success');
    else showToast(data.error||'Chyba pri ukladaní.','error');
  } catch(err) { showToast('Chyba pripojenia k serveru.','error'); }
  finally {
    if (btn) { btn.disabled=false; btn.innerHTML='<span class="material-symbols-outlined">save</span><span>Uložiť údaje prevádzky</span>'; }
  }
}

async function fetchRegisterData() {
  const ico = val('est-ico-search').trim();
  if (!ico) { showToast('Zadajte IČO','error'); return; }
  const btn=document.querySelector('[onclick="fetchRegisterData()"]');
  if (btn) { btn.disabled=true; btn.innerHTML='<span class="material-symbols-outlined">sync</span> Načítavam...'; }
  try {
    const fd=new FormData(); fd.append('action','lookup_ico'); fd.append('ico',ico);
    const res=await fetch('api/business.php',{method:'POST',body:fd});
    const data=await res.json();
    if (data.success && data.company) {
      const c=data.company;
      if (c.legal_name) setVal('est-legal-name', c.legal_name);
      if (c.owner_name) setVal('est-owner-name', c.owner_name);
      if (c.ico) setVal('est-ico', c.ico);
      if (c.dic) setVal('est-dic', c.dic);
      if (c.ic_dph) { setVal('est-ic-dph', c.ic_dph); setCheck('est-is-vat-payer', true); }
      if (c.address) setVal('est-address', c.address);
      if (c.city) setVal('est-city', c.city);
      showToast('Firemné údaje boli načítané z registra!','success');
    } else {
      showToast(data.error||'IČO sa nenašlo v registri.','error');
    }
  } catch(err) { showToast('Chyba načítania z registra.','error'); }
  finally {
    if (btn) { btn.disabled=false; btn.innerHTML='<span class="material-symbols-outlined">search</span><span>Načítať</span>'; }
  }
}

// Redirect billing/team buttons (no showSection here)
function showSection(section) {
  if (section==='billing') window.location.href='dashboard-balik.php';
  else if (section==='team') window.location.href='dashboard-tym.php';
}

document.addEventListener('DOMContentLoaded', () => {
  const themeIcon = document.getElementById('theme-icon');
  if (themeIcon) {
    themeIcon.textContent = document.body.classList.contains('dark-mode') ? 'dark_mode' : 'light_mode';
  }
  init();
});
function init() { loadEstablishmentInfo(); loadEmployeesPreview(); }
</script>
</body>
</html>
