<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
// Správu tímu (pridávanie/mazanie/oprávnenia iných) smie robiť len majiteľ alebo zamestnanec
// s 'settings'. Svoj VLASTNÝ rozvrh a dovolenky si ale smie pozrieť/upraviť každý zamestnanec —
// preto tu na rozdiel od requireEmployeePermission() nepresmerúvame preč, len obmedzíme UI nižšie.
$restrictedEmployeeView = !empty($_SESSION['is_employee']) && !employeeCan('settings');
$myEmployeeId = (int)($_SESSION['employee_id'] ?? 0);
$pageTitle = 'Tím a Zamestnanci - ' . BRAND_NAME;
$currentPage = 'tym';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Tím a Zamestnanci'; $headerIcon = 'group'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <?php include 'components/team.php'; ?>

    <?php if (!$restrictedEmployeeView): ?>
    <!-- Sviatky a zatvorenie celej prevádzky (nezávisí od výberu zamestnanca) -->
    <div class="vueto-card" style="margin-top:20px;">
      <div class="vueto-card-header">
        <div>
          <h2 class="section-header"><span class="material-symbols-outlined">event_busy</span> Sviatky a zatvorenie prevádzky</h2>
          <p class="section-desc">Dni, kedy je celá prevádzka zatvorená (štátne sviatky, dovolenka firmy) — nikto si na tieto dni nemôže nič rezervovať.</p>
        </div>
      </div>
      <div style="padding:16px 22px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;padding:16px;background:var(--bg-color);border-radius:12px;border:1px solid var(--border-color);margin-bottom:18px;">
          <div>
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Od</label>
            <input type="date" id="closure-from" style="padding:8px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-size:13px;">
          </div>
          <div>
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Do</label>
            <input type="date" id="closure-to" style="padding:8px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-size:13px;">
          </div>
          <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Dôvod (voliteľné)</label>
            <input type="text" id="closure-reason" name="closure-reason-text" autocomplete="off" placeholder="Napr. Vianoce, Dovolenka firmy" style="width:100%;box-sizing:border-box;padding:8px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-size:13px;">
          </div>
          <button class="btn-primary" onclick="addClosure()" style="padding:8px 16px;font-size:13px;white-space:nowrap;">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add</span> Pridať zatvorenie
          </button>
        </div>
        <div id="closure-list"></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Schedule & Unavailability Sections (hidden until employee selected) -->
<div id="schedule-section" style="display:none;padding:0 0 0 0;">
  <div class="vueto-card" style="margin-top:20px;">
    <div class="vueto-card-header">
      <div>
        <h2 class="section-header"><span class="material-symbols-outlined">schedule</span> Pracovné hodiny — <span id="schedule-emp-name" style="color:var(--primary-color);"></span></h2>
        <p class="section-desc">Nastavte dni a časy kedy zamestnanec pracuje.</p>
      </div>
      <button class="btn-primary" onclick="saveSchedule()" style="padding:8px 18px;font-size:13px;">
        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">save</span> Uložiť hodiny
      </button>
    </div>
    <div style="padding:16px 22px;" id="hours-grid">
      <p style="color:var(--text-secondary);">Načítavam...</p>
    </div>
  </div>
</div>

<div id="unavail-section" style="display:none;">
  <div class="vueto-card" style="margin-top:20px;">
    <div class="vueto-card-header">
      <div>
        <h2 class="section-header"><span class="material-symbols-outlined">event_busy</span> Dovolenky a PN</h2>
        <p class="section-desc">Blokované obdobia — zamestnanec nebude dostupný pre rezervácie.</p>
      </div>
    </div>
    <div style="padding:16px 22px;">
      <!-- Add form -->
      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;padding:16px;background:var(--bg-color);border-radius:12px;border:1px solid var(--border-color);margin-bottom:18px;">
        <div>
          <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Typ</label>
          <select id="unavail-type" style="padding:8px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-size:13px;">
            <option value="vacation">Dovolenka</option>
            <option value="pn">PN / Práceneschopnosť</option>
            <option value="other">Iné</option>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Od</label>
          <input type="date" id="unavail-from" style="padding:8px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-size:13px;">
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Do</label>
          <input type="date" id="unavail-to" style="padding:8px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-size:13px;">
        </div>
        <div style="flex:1;min-width:160px;">
          <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Poznámka (voliteľné)</label>
          <input type="text" id="unavail-note" placeholder="Napr. Letná dovolenka" style="width:100%;box-sizing:border-box;padding:8px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-size:13px;">
        </div>
        <button class="btn-primary" onclick="addUnavailability()" style="padding:8px 16px;font-size:13px;white-space:nowrap;">
          <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add</span> Pridať blok
        </button>
      </div>
      <!-- List -->
      <div id="unavail-list"></div>
    </div>
  </div>
</div>

<style>
.color-swatch { width:24px;height:24px;border-radius:50%;cursor:pointer;border:2px solid transparent;box-sizing:border-box;transition:all 0.15s;flex-shrink:0;padding:0; }
.color-swatch:hover { transform:scale(1.15); }
.color-swatch.active { border-color:var(--text-primary);box-shadow:0 0 0 2px var(--card-bg),0 0 0 4px var(--swatch-color,transparent); }
.color-swatch.none-swatch { background:var(--bg-color) !important;border:1.5px dashed var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-secondary); }
.color-swatch.none-swatch .material-symbols-outlined { font-size:14px; }
</style>
<!-- Employee Modal -->
<div id="employee-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:1000;justify-content:center;align-items:center;overflow-y:auto;padding:20px;box-sizing:border-box;">
  <div style="background:var(--card-bg);border-radius:16px;padding:26px;max-width:500px;width:100%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.35);position:relative;margin:auto;">
    <h3 id="employee-modal-title" style="margin:0 0 20px 0;font-size:18px;display:flex;align-items:center;gap:8px;">
      <span class="material-symbols-outlined" style="color:var(--primary-color);">person_add</span><span>Pridať zamestnanca</span>
    </h3>
    <input type="hidden" id="emp-id">
    <input type="hidden" id="emp-is-owner" value="0">

    <!-- Avatar -->
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
      <div id="emp-avatar-preview-box" style="width:72px;height:72px;border-radius:50%;background:var(--input-bg);border:2px solid var(--border-color);display:flex;align-items:center;justify-content:center;overflow:hidden;background-size:cover;background-position:center;flex-shrink:0;">
        <span id="emp-avatar-initial" style="font-size:26px;font-weight:700;color:var(--primary-color);">?</span>
      </div>
      <div>
        <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;font-weight:600;background:var(--bg-color);color:var(--text-primary);">
          <span class="material-symbols-outlined" style="font-size:16px;">upload</span> Nahrať foto
          <input type="file" id="emp-avatar-file" accept="image/*" style="display:none;" onchange="previewEmployeeAvatar(this)">
        </label>
        <p style="margin:4px 0 0 0;font-size:12px;color:var(--text-secondary);">Max. 5 MB, JPG/PNG/WebP</p>
      </div>
    </div>

    <!-- Owner info box -->
    <div id="emp-owner-info-box" style="display:none;background:rgba(176,128,66,0.06);border:1px solid rgba(176,128,66,0.3);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:var(--text-secondary);">
      <strong style="color:var(--primary-color);">Profil majiteľa prevádzky</strong> – Pre prihlasovanie použite sekciu <a href="dashboard.php?sec=security" style="color:var(--primary-color);">Zabezpečenie</a>. Tu upravujte iba meno, pozíciu a foto.
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div class="form-group" style="grid-column:1/-1;"><label>Meno a priezvisko *</label><input type="text" id="emp-name" placeholder="Napr. Jana Kováčová"></div>
      <div class="form-group"><label>Pracovná pozícia</label><input type="text" id="emp-title" placeholder="Napr. Kaderníčka"></div>
      <div class="form-group"><label>Telefón</label><input type="tel" id="emp-phone" placeholder="+421 900 000 000"></div>
      <div class="form-group" style="grid-column:1/-1;">
        <label>Farba v kalendári</label>
        <input type="hidden" id="emp-color" value="">
        <div id="emp-color-group" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:4px;"></div>
      </div>
    </div>

    <div id="emp-credentials-box">
      <div class="form-group"><label>E-mail (login) *</label><input type="email" id="emp-email" placeholder="zamestnanec@salon.sk"></div>
      <div class="form-group">
        <label>Heslo <span id="emp-pwd-req-star" style="color:#ef4444;">*</span></label>
        <div style="position:relative;display:flex;align-items:center;">
          <input type="password" id="emp-password" placeholder="Min. 6 znakov" style="padding-right:80px;width:100%;box-sizing:border-box;">
          <div style="position:absolute;right:8px;display:flex;gap:4px;">
            <button type="button" onclick="toggleEmpPasswordVisibility()" class="btn-action" style="color:var(--text-secondary);" title="Zobraziť/skryť heslo"><span class="material-symbols-outlined" id="emp-pwd-eye" style="font-size:18px;">visibility</span></button>
            <button type="button" onclick="generateEmployeePassword()" class="btn-action" style="color:var(--primary-color);" title="Vygenerovať heslo"><span class="material-symbols-outlined" style="font-size:18px;">casino</span></button>
          </div>
        </div>
        <p id="emp-password-hint" style="display:none;margin:4px 0 0 0;font-size:12px;color:var(--text-secondary);">Nechajte prázdne pre zachovanie existujúceho hesla.</p>
      </div>
    </div>

    <div id="emp-active-group" class="form-group">
      <label>Stav</label>
      <select id="emp-active">
        <option value="1">Aktívny – prijíma rezervácie</option>
        <option value="0">Dočasne skrytý</option>
      </select>
    </div>

    <div id="emp-permissions-group" class="form-group">
      <label>Prístup do dashboardu</label>
      <p style="margin:0 0 8px 0;font-size:12px;color:var(--text-secondary);">Kalendár, rezervácie a klienti vidí zamestnanec vždy. Navyše mu môžete povoliť:</p>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13.5px;cursor:pointer;">
          <input type="checkbox" id="emp-perm-revenue" style="accent-color:var(--primary-color);width:16px;height:16px;">
          Vidieť tržby a peňaženku
        </label>
        <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13.5px;cursor:pointer;">
          <input type="checkbox" id="emp-perm-crm" style="accent-color:var(--primary-color);width:16px;height:16px;">
          Vidieť marketing, hodnotenia a inzerciu
        </label>
        <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13.5px;cursor:pointer;">
          <input type="checkbox" id="emp-perm-settings" style="accent-color:var(--primary-color);width:16px;height:16px;">
          Upravovať nastavenia, cenník a tím
        </label>
      </div>
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
      <button type="button" onclick="closeEmployeeModal()" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
      <button type="button" id="btn-save-emp" onclick="saveEmployee()" class="btn-primary" style="padding:10px 22px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
        <span class="material-symbols-outlined" style="font-size:18px;">save</span> Uložiť údaje
      </button>
    </div>
  </div>
</div>

<!-- Team Delete Confirm Modal -->
<div id="team-delete-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:1001;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:26px;max-width:400px;width:90%;border:1px solid var(--border-color);text-align:center;">
    <div style="width:50px;height:50px;border-radius:50%;background:rgba(239,68,68,0.12);color:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 14px auto;">
      <span class="material-symbols-outlined" style="font-size:26px;">person_remove</span>
    </div>
    <input type="hidden" id="team-delete-id">
    <p id="team-delete-msg" style="font-size:15px;color:var(--text-primary);margin:0 0 20px 0;line-height:1.5;font-weight:500;"></p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button type="button" onclick="closeTeamDeleteModal()" class="btn-secondary" style="padding:10px 22px;">Zrušiť</button>
      <button type="button" onclick="confirmDeleteEmployee()" class="btn-primary" style="padding:10px 22px;background:#ef4444 !important;border-color:#ef4444 !important;">Odstrániť</button>
    </div>
  </div>
</div>

<div class="app-toast-container" id="app-toast-container"></div>
<script>
let g_team = [];
const RESTRICTED_EMPLOYEE_VIEW = <?= $restrictedEmployeeView ? 'true' : 'false' ?>;
const MY_EMPLOYEE_ID = <?= $myEmployeeId ?>;
const EMP_COLORS = ['#ef4444','#f97316','#f59e0b','#10b981','#06b6d4','#3b82f6','#8b5cf6','#ec4899','#64748b'];

function buildEmpColorSwatches(selected) {
    const group = document.getElementById('emp-color-group'); if (!group) return;
    let html = `<button type="button" class="color-swatch none-swatch ${!selected?'active':''}" title="Bez farby" onclick="selectEmpColor(null,this)">
        <span class="material-symbols-outlined">block</span>
    </button>`;
    EMP_COLORS.forEach(c => {
        html += `<button type="button" class="color-swatch ${selected===c?'active':''}" style="background:${c};--swatch-color:${c};" title="${c}" onclick="selectEmpColor('${c}',this)"></button>`;
    });
    group.innerHTML = html;
}
function selectEmpColor(color, btn) {
    document.getElementById('emp-color').value = color || '';
    document.querySelectorAll('#emp-color-group .color-swatch').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// escapeHtml + showAppToast sú v /assets/js/dashboard-common.js
function escapeJs(str) { if (!str) return ''; return String(str).replace(/'/g,"\\'"); }

window.alert = function(msg) { showAppToast(String(msg), 'info'); };

async function loadTeam() {
    try {
        const grid = document.getElementById('team-grid');
        if (grid) grid.innerHTML = `<div style="text-align:center;padding:40px 20px;grid-column:1/-1;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:36px;animation:spin 1.5s linear infinite;">sync</span><p style="margin-top:10px;font-size:14px;">Načítavam zoznam zamestnancov...</p></div>`;
        let fd = new FormData(); fd.append('action', 'get_team');
        let r = await fetch('api/team.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) {
            g_team = res.team || [];
            let limit = res.limit, tier = res.tier || 'FREE';
            const countEl = document.getElementById('team-count'), maxEl = document.getElementById('team-max'), tierEl = document.getElementById('team-tier-badge');
            if (countEl) countEl.innerText = g_team.length;
            if (maxEl) maxEl.innerText = (limit > 9000 ? 'Neobmedzene' : limit);
            if (tierEl) tierEl.innerText = tier;
            let btn = document.getElementById('btn-add-employee');
            if (btn) {
                if (RESTRICTED_EMPLOYEE_VIEW) { btn.style.display = 'none'; }
                else if (g_team.length >= limit) { btn.style.opacity = '0.6'; btn.onclick = () => showAppToast(`Dosiahli ste limit tímu pre Váš balík (${limit}). Pre pridanie ďalších zamestnancov si navýšte balík.`, 'error'); }
                else { btn.style.opacity = '1'; btn.onclick = openEmployeeModal; }
            }
            let html = '';
            // Zamestnanec bez oprávnenia 'settings' vidí a spravuje len svoju vlastnú kartu
            // (svoj rozvrh a dovolenky), nie celý tím.
            const renderList = RESTRICTED_EMPLOYEE_VIEW ? g_team.filter(e => parseInt(e.id) === MY_EMPLOYEE_ID) : g_team;
            renderList.forEach(e => {
                const isOwner = (parseInt(e.is_owner) === 1);
                const isActive = (parseInt(e.is_active) === 1);
                const avatarBg = e.avatar_url ? `background-image:url('${e.avatar_url}');background-size:cover;background-position:center;` : `background:var(--primary-color);`;
                const initial = e.name ? e.name.charAt(0).toUpperCase() : '?';
                html += `<div style="background:var(--card-bg);border:1px solid ${isOwner ? 'rgba(176,128,66,0.4)' : 'var(--border-color)'};border-radius:16px;padding:22px 20px;display:flex;flex-direction:column;align-items:center;text-align:center;position:relative;box-shadow:0 4px 15px rgba(0,0,0,0.03);transition:all 0.25s ease;">
                    <div style="position:absolute;top:14px;left:14px;">
                        ${isOwner ? `<span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;background:rgba(176,128,66,0.15);color:var(--primary-color);display:inline-flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">verified_user</span> Majiteľ (Správca)</span>` : `<span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:6px;background:var(--bg-color);border:1px solid var(--border-color);color:var(--text-secondary);display:inline-flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">person</span> Zamestnanec</span>`}
                    </div>
                    <div style="position:absolute;top:14px;right:14px;">
                        <span style="width:10px;height:10px;border-radius:50%;display:inline-block;background:${isActive ? '#10b981' : '#9ca3af'};box-shadow:0 0 8px ${isActive ? 'rgba(16,185,129,0.4)' : 'transparent'};" title="${isActive ? 'Aktívny' : 'Dočasne skrytý'}"></span>
                    </div>
                    <div style="width:76px;height:76px;border-radius:50%;${avatarBg}color:white;display:flex;justify-content:center;align-items:center;font-size:28px;font-weight:700;margin-top:14px;margin-bottom:12px;border:3px solid ${e.color ? e.color : (isOwner ? 'var(--primary-color)' : 'var(--border-color)')};box-shadow:0 4px 12px rgba(0,0,0,0.08);overflow:hidden;">
                        ${e.avatar_url ? '' : initial}
                    </div>
                    <strong style="font-size:16.5px;color:var(--text-primary);margin-bottom:2px;">${escapeHtml(e.name)}</strong>
                    <span style="color:var(--primary-color);font-size:13px;font-weight:600;margin-bottom:12px;">${escapeHtml(e.title || (isOwner ? 'Majiteľ / Vlastník' : 'Člen tímu'))}</span>
                    <div style="width:100%;border-top:1px dashed var(--border-color);padding-top:10px;margin-bottom:16px;font-size:12.5px;color:var(--text-secondary);display:flex;flex-direction:column;gap:5px;text-align:left;">
                        ${e.email ? `<div style="display:flex;align-items:center;gap:6px;overflow:hidden;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--primary-color);">mail</span><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(e.email)}</span></div>` : ''}
                        ${e.phone ? `<div style="display:flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--primary-color);">call</span><span>${escapeHtml(e.phone)}</span></div>` : ''}
                    </div>
                    ${!isOwner ? `<div style="display:flex;gap:5px;flex-wrap:wrap;justify-content:center;margin-bottom:14px;">
                        ${parseInt(e.can_view_revenue) === 1 ? `<span style="font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;">Tržby</span>` : ''}
                        ${parseInt(e.can_view_crm) === 1 ? `<span style="font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:6px;background:rgba(59,130,246,0.12);color:#3b82f6;">CRM</span>` : ''}
                        ${parseInt(e.can_edit_settings) === 1 ? `<span style="font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:6px;background:rgba(176,128,66,0.12);color:var(--primary-color);">Nastavenia</span>` : ''}
                        ${!e.can_view_revenue && !e.can_view_crm && !e.can_edit_settings ? `<span style="font-size:10.5px;color:var(--text-secondary);">Len kalendár a klienti</span>` : ''}
                    </div>` : ''}
                    <div style="display:flex;gap:8px;width:100%;margin-top:auto;flex-wrap:wrap;">
                        ${RESTRICTED_EMPLOYEE_VIEW ? '' : (isOwner ? `<button type="button" onclick="editEmployee(${e.id})" class="btn-primary" style="flex:1;padding:8px 14px;font-size:13px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;"><span class="material-symbols-outlined" style="font-size:16px;">edit</span><span>Upraviť profil</span></button>` : `<button type="button" onclick="editEmployee(${e.id})" class="btn-secondary" style="flex:1;padding:8px 12px;font-size:12.5px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:15px;">edit</span><span>Upraviť</span></button><button type="button" onclick="openTeamDeleteModal(${e.id},'${escapeJs(e.name)}')" class="btn-secondary" style="padding:8px 12px;font-size:12.5px;border-radius:10px;color:#ef4444;display:inline-flex;align-items:center;justify-content:center;" title="Odstrániť"><span class="material-symbols-outlined" style="font-size:16px;">delete</span></button>`)}
                        <button type="button" onclick="openScheduleFor(${e.id},'${escapeJs(e.name)}')" class="btn-secondary" style="width:100%;padding:7px 12px;font-size:12.5px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:4px;margin-top:4px;border-color:var(--primary-color);color:var(--primary-color);" title="Pracovné hodiny a dovolenky">
                            <span class="material-symbols-outlined" style="font-size:15px;">schedule</span><span>Rozvrh a Dovolenky</span>
                        </button>
                    </div>
                </div>`;
            });
            if (renderList.length === 0) html = RESTRICTED_EMPLOYEE_VIEW ? '<p style="color:var(--text-secondary);grid-column:1/-1;text-align:center;padding:30px;">Váš profil zamestnanca sa nenašiel.</p>' : '<p style="color:var(--text-secondary);grid-column:1/-1;text-align:center;padding:30px;">Zatiaľ nemáte žiadnych zamestnancov.</p>';
            if (grid) grid.innerHTML = html;
        } else showAppToast(res.message || 'Chyba pri načítaní tímu', 'error');
    } catch(err) { console.error(err); showAppToast('Chyba komunikácie so serverom', 'error'); }
}

function openEmployeeModal() {
    document.getElementById('emp-id').value = ''; document.getElementById('emp-is-owner').value = '0';
    document.getElementById('emp-name').value = ''; document.getElementById('emp-title').value = '';
    document.getElementById('emp-phone').value = ''; document.getElementById('emp-email').value = '';
    document.getElementById('emp-password').value = ''; document.getElementById('emp-active').value = '1';
    document.getElementById('emp-perm-revenue').checked = false;
    document.getElementById('emp-perm-crm').checked = false;
    document.getElementById('emp-perm-settings').checked = false;
    document.getElementById('emp-permissions-group').style.display = 'block';
    buildEmpColorSwatches(null);
    const previewBox = document.getElementById('emp-avatar-preview-box'), initialEl = document.getElementById('emp-avatar-initial');
    if (previewBox) previewBox.style.backgroundImage = 'none'; if (initialEl) { initialEl.style.display = 'block'; initialEl.innerText = '?'; }
    const fileInput = document.getElementById('emp-avatar-file'); if (fileInput) fileInput.value = '';
    document.getElementById('employee-modal-title').innerHTML = `<span class="material-symbols-outlined" style="color:var(--primary-color);">person_add</span><span>Pridať zamestnanca</span>`;
    document.getElementById('emp-credentials-box').style.display = 'block';
    document.getElementById('emp-owner-info-box').style.display = 'none';
    document.getElementById('emp-pwd-req-star').style.display = 'inline';
    document.getElementById('emp-password-hint').style.display = 'none';
    document.getElementById('emp-active-group').style.display = 'block';
    document.getElementById('employee-modal').style.display = 'flex';
}

function closeEmployeeModal() { document.getElementById('employee-modal').style.display = 'none'; }

function editEmployee(id) {
    let emp = g_team.find(e => parseInt(e.id) === parseInt(id)); if (!emp) return;
    const isOwner = (parseInt(emp.is_owner) === 1);
    document.getElementById('emp-id').value = emp.id; document.getElementById('emp-is-owner').value = isOwner ? '1' : '0';
    document.getElementById('emp-name').value = emp.name || ''; document.getElementById('emp-title').value = emp.title || '';
    document.getElementById('emp-phone').value = emp.phone || ''; document.getElementById('emp-email').value = emp.email || '';
    document.getElementById('emp-password').value = ''; document.getElementById('emp-active').value = emp.is_active ? '1' : '0';
    document.getElementById('emp-perm-revenue').checked = parseInt(emp.can_view_revenue) === 1;
    document.getElementById('emp-perm-crm').checked = parseInt(emp.can_view_crm) === 1;
    document.getElementById('emp-perm-settings').checked = parseInt(emp.can_edit_settings) === 1;
    buildEmpColorSwatches(emp.color || null);
    const previewBox = document.getElementById('emp-avatar-preview-box'), initialEl = document.getElementById('emp-avatar-initial'), fileInput = document.getElementById('emp-avatar-file');
    if (fileInput) fileInput.value = '';
    if (emp.avatar_url) { if (previewBox) previewBox.style.backgroundImage = `url('${emp.avatar_url}')`; if (initialEl) initialEl.style.display = 'none'; }
    else { if (previewBox) previewBox.style.backgroundImage = 'none'; if (initialEl) { initialEl.style.display = 'block'; initialEl.innerText = emp.name ? emp.name.charAt(0).toUpperCase() : '?'; } }
    if (isOwner) {
        document.getElementById('employee-modal-title').innerHTML = `<span class="material-symbols-outlined" style="color:var(--primary-color);">verified_user</span><span>Upraviť profil majiteľa</span>`;
        document.getElementById('emp-credentials-box').style.display = 'none';
        document.getElementById('emp-owner-info-box').style.display = 'block';
        document.getElementById('emp-active-group').style.display = 'none';
        document.getElementById('emp-permissions-group').style.display = 'none';
    } else {
        document.getElementById('employee-modal-title').innerHTML = `<span class="material-symbols-outlined" style="color:var(--primary-color);">edit</span><span>Upraviť zamestnanca</span>`;
        document.getElementById('emp-credentials-box').style.display = 'block';
        document.getElementById('emp-owner-info-box').style.display = 'none';
        document.getElementById('emp-pwd-req-star').style.display = 'none';
        document.getElementById('emp-password-hint').style.display = 'block';
        document.getElementById('emp-active-group').style.display = 'block';
        document.getElementById('emp-permissions-group').style.display = 'block';
    }
    document.getElementById('employee-modal').style.display = 'flex';
}

function previewEmployeeAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { const pb = document.getElementById('emp-avatar-preview-box'), il = document.getElementById('emp-avatar-initial'); if (pb) pb.style.backgroundImage = `url('${e.target.result}')`; if (il) il.style.display = 'none'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function generateEmployeePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let pwd = '';
    for (let i = 0; i < 10; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    const input = document.getElementById('emp-password');
    input.value = pwd; input.type = 'text';
    const eye = document.getElementById('emp-pwd-eye'); if (eye) eye.innerText = 'visibility_off';
    showAppToast('Náhodné heslo vygenerované!', 'success');
}

function toggleEmpPasswordVisibility() {
    const input = document.getElementById('emp-password'), eye = document.getElementById('emp-pwd-eye');
    if (input.type === 'password') { input.type = 'text'; if (eye) eye.innerText = 'visibility_off'; }
    else { input.type = 'password'; if (eye) eye.innerText = 'visibility'; }
}

async function saveEmployee() {
    const btn = document.getElementById('btn-save-emp');
    const id = document.getElementById('emp-id').value, isOwner = document.getElementById('emp-is-owner').value === '1',
          name = document.getElementById('emp-name').value.trim(), title = document.getElementById('emp-title').value.trim(),
          phone = document.getElementById('emp-phone').value.trim(), email = document.getElementById('emp-email').value.trim(),
          password = document.getElementById('emp-password').value, isActive = document.getElementById('emp-active').value,
          avatarFile = document.getElementById('emp-avatar-file').files[0];
    if (!name) { showAppToast('Zadajte meno zamestnanca.', 'error'); return; }
    if (!isOwner) {
        if (!email) { showAppToast('Zadajte prihlasovací e-mail zamestnanca.', 'error'); return; }
        if (!id && (!password || password.length < 6)) { showAppToast('Heslo musí mať aspoň 6 znakov.', 'error'); return; }
    }
    let fd = new FormData();
    fd.append('action', id ? 'update_employee' : 'add_employee'); if (id) fd.append('id', id);
    fd.append('name', name); fd.append('title', title); fd.append('phone', phone); fd.append('email', email);
    fd.append('color', document.getElementById('emp-color').value);
    if (password) fd.append('password', password); fd.append('is_active', isActive);
    if (!isOwner) {
        if (document.getElementById('emp-perm-revenue').checked) fd.append('can_view_revenue', '1');
        if (document.getElementById('emp-perm-crm').checked) fd.append('can_view_crm', '1');
        if (document.getElementById('emp-perm-settings').checked) fd.append('can_edit_settings', '1');
    }
    if (avatarFile) fd.append('avatar', avatarFile);
    if (btn) { btn.disabled = true; btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px;animation:spin 1s linear infinite;">sync</span> Ukladám...`; }
    try {
        let r = await fetch('api/team.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) { closeEmployeeModal(); showAppToast(res.message || 'Zamestnanec bol úspešne uložený.', 'success'); loadTeam(); }
        else showAppToast(res.message || 'Chyba pri ukladaní zamestnanca.', 'error');
    } catch(e) { console.error(e); showAppToast('Chyba komunikácie so serverom.', 'error'); }
    finally { if (btn) { btn.disabled = false; btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px;">save</span> Uložiť údaje`; } }
}

function openTeamDeleteModal(id, name) {
    document.getElementById('team-delete-id').value = id;
    document.getElementById('team-delete-msg').innerText = `Naozaj si prajete odstrániť zamestnanca "${name}" z Vášho tímu?`;
    document.getElementById('team-delete-modal').style.display = 'flex';
}

function closeTeamDeleteModal() { document.getElementById('team-delete-modal').style.display = 'none'; }

async function confirmDeleteEmployee() {
    const id = document.getElementById('team-delete-id').value; if (!id) return;
    let fd = new FormData(); fd.append('action', 'delete_employee'); fd.append('id', id);
    try {
        let r = await fetch('api/team.php', { method: 'POST', body: fd }); let res = await r.json();
        closeTeamDeleteModal();
        if (res.success) { showAppToast(res.message || 'Zamestnanec bol úspešne odstránený.', 'success'); loadTeam(); }
        else showAppToast(res.message || 'Chyba pri odstraňovaní zamestnanca.', 'error');
    } catch(e) { console.error(e); showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

// ---- Pracovné hodiny ----
const DAY_NAMES = ['Pondelok','Utorok','Streda','Štvrtok','Piatok','Sobota','Nedeľa'];
let scheduleEmpId = null;

async function openScheduleFor(empId, empName) {
    scheduleEmpId = empId;
    document.getElementById('schedule-emp-name').textContent = empName;
    document.getElementById('schedule-section').style.display = 'block';
    document.getElementById('unavail-section').style.display = 'block';
    document.getElementById('schedule-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    await loadSchedule(empId);
    await loadUnavailabilities(empId);
}

async function loadSchedule(empId) {
    const fd = new FormData(); fd.append('action','get_employee_hours'); fd.append('employee_id', empId);
    const res = await fetch('api/team.php', { method:'POST', body:fd });
    const data = await res.json();
    if (!data.success) return;
    const grid = document.getElementById('hours-grid');
    grid.innerHTML = '';
    data.schedule.forEach(row => {
        const isOff = row.is_off == 1;
        const hasBreak = !!(row.break_start && row.break_end);
        grid.innerHTML += `<div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-color);flex-wrap:wrap;">
            <div style="width:90px;font-weight:600;font-size:14px;color:var(--text-primary);">${DAY_NAMES[row.day_of_week]}</div>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;color:var(--text-secondary);">
                <input type="checkbox" class="day-off-cb" data-day="${row.day_of_week}" ${isOff ? 'checked' : ''} onchange="toggleDayOff(this)" style="accent-color:var(--primary-color);width:15px;height:15px;"> Voľno
            </label>
            <div class="day-times" data-day="${row.day_of_week}" style="display:flex;align-items:center;gap:8px;${isOff ? 'opacity:0.3;pointer-events:none;' : ''}">
                <input type="time" class="day-start" data-day="${row.day_of_week}" value="${(row.start_time||'09:00').substring(0,5)}" style="padding:6px 10px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:13px;">
                <span style="color:var(--text-secondary);">–</span>
                <input type="time" class="day-end" data-day="${row.day_of_week}" value="${(row.end_time||'17:00').substring(0,5)}" style="padding:6px 10px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:13px;">
            </div>
            <div class="day-break-wrap" data-day="${row.day_of_week}" style="display:flex;align-items:center;gap:8px;${isOff ? 'opacity:0.3;pointer-events:none;' : ''}">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12.5px;color:var(--text-secondary);">
                    <input type="checkbox" class="day-break-cb" data-day="${row.day_of_week}" ${hasBreak ? 'checked' : ''} onchange="toggleDayBreak(this)" style="accent-color:var(--primary-color);width:14px;height:14px;"> Obedňajšia prestávka
                </label>
                <input type="time" class="day-break-start" data-day="${row.day_of_week}" value="${(row.break_start||'12:00').substring(0,5)}" style="padding:5px 8px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:12.5px;${hasBreak ? '' : 'display:none;'}">
                <span class="day-break-sep" data-day="${row.day_of_week}" style="color:var(--text-secondary);font-size:12.5px;${hasBreak ? '' : 'display:none;'}">–</span>
                <input type="time" class="day-break-end" data-day="${row.day_of_week}" value="${(row.break_end||'13:00').substring(0,5)}" style="padding:5px 8px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:12.5px;${hasBreak ? '' : 'display:none;'}">
            </div>
        </div>`;
    });
}

function toggleDayOff(cb) {
    const day = cb.getAttribute('data-day');
    const times = document.querySelector(`.day-times[data-day="${day}"]`);
    if (times) { times.style.opacity = cb.checked ? '0.3' : '1'; times.style.pointerEvents = cb.checked ? 'none' : ''; }
    const breakWrap = document.querySelector(`.day-break-wrap[data-day="${day}"]`);
    if (breakWrap) { breakWrap.style.opacity = cb.checked ? '0.3' : '1'; breakWrap.style.pointerEvents = cb.checked ? 'none' : ''; }
}

function toggleDayBreak(cb) {
    const day = cb.getAttribute('data-day');
    const display = cb.checked ? '' : 'none';
    document.querySelector(`.day-break-start[data-day="${day}"]`).style.display = display;
    document.querySelector(`.day-break-sep[data-day="${day}"]`).style.display = display;
    document.querySelector(`.day-break-end[data-day="${day}"]`).style.display = display;
}

async function saveSchedule() {
    if (!scheduleEmpId) return;
    const days = [];
    document.querySelectorAll('.day-off-cb').forEach(cb => {
        const d = cb.getAttribute('data-day');
        const breakCb = document.querySelector(`.day-break-cb[data-day="${d}"]`);
        days.push({
            day: d,
            off: cb.checked ? 1 : 0,
            start: document.querySelector(`.day-start[data-day="${d}"]`)?.value || '09:00',
            end: document.querySelector(`.day-end[data-day="${d}"]`)?.value || '17:00',
            break: breakCb?.checked ? 1 : 0,
            break_start: document.querySelector(`.day-break-start[data-day="${d}"]`)?.value || '12:00',
            break_end: document.querySelector(`.day-break-end[data-day="${d}"]`)?.value || '13:00'
        });
    });
    const fd = new FormData(); fd.append('action','save_employee_hours'); fd.append('employee_id', scheduleEmpId); fd.append('days', JSON.stringify(days));
    const res = await fetch('api/team.php', { method:'POST', body:fd });
    const data = await res.json();
    showAppToast(data.message || (data.success ? 'Uložené.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success && data.capacity_warning) {
        setTimeout(() => showAppToast(data.capacity_warning, 'warning'), 600);
    }
}

// ---- Dovolenky / PN ----
async function loadUnavailabilities(empId) {
    const fd = new FormData(); fd.append('action','get_employee_unavailabilities'); fd.append('employee_id', empId);
    const res = await fetch('api/team.php', { method:'POST', body:fd });
    const data = await res.json();
    const list = document.getElementById('unavail-list');
    if (!data.success || !data.items.length) { list.innerHTML = '<p style="color:var(--text-secondary);font-size:13px;">Žiadne bloky.</p>'; return; }
    const typeLabel = {
        vacation: '<span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;color:var(--primary-color);">beach_access</span> Dovolenka',
        pn: '<span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;color:var(--primary-color);">sick</span> PN',
        other: '<span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;color:var(--primary-color);">event_busy</span> Iné'
    };
    list.innerHTML = data.items.map(u => `
        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--bg-color);border-radius:10px;border:1px solid var(--border-color);margin-bottom:8px;">
            <span style="font-size:13px;font-weight:600;color:var(--text-primary);flex:1;">${typeLabel[u.type]||u.type}
                <span style="font-weight:400;color:var(--text-secondary);"> ${u.date_from} – ${u.date_to}${u.note ? ' · '+u.note : ''}</span>
            </span>
            <button onclick="deleteUnavail(${u.id})" style="background:none;border:none;cursor:pointer;color:#ef4444;display:flex;align-items:center;" title="Odstrániť">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
            </button>
        </div>`).join('');
}

async function addUnavailability() {
    if (!scheduleEmpId) return;
    const type = document.getElementById('unavail-type').value;
    const from = document.getElementById('unavail-from').value;
    const to = document.getElementById('unavail-to').value;
    const note = document.getElementById('unavail-note').value;
    if (!from || !to) { showAppToast('Vyplňte dátumy.', 'error'); return; }
    const fd = new FormData();
    fd.append('action','add_employee_unavailability'); fd.append('employee_id', scheduleEmpId);
    fd.append('type', type); fd.append('date_from', from); fd.append('date_to', to); fd.append('note', note);
    const res = await fetch('api/team.php', { method:'POST', body:fd });
    const data = await res.json();
    showAppToast(data.message || (data.success ? 'Pridané.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) { document.getElementById('unavail-from').value=''; document.getElementById('unavail-to').value=''; document.getElementById('unavail-note').value=''; loadUnavailabilities(scheduleEmpId); }
}

async function deleteUnavail(id) {
    const fd = new FormData(); fd.append('action','delete_employee_unavailability'); fd.append('id', id);
    const res = await fetch('api/team.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) loadUnavailabilities(scheduleEmpId);
}

// ---- Sviatky a zatvorenie prevádzky ----
async function loadClosures() {
    const fd = new FormData(); fd.append('action', 'get_closures');
    const res = await fetch('api/closures.php', { method: 'POST', body: fd });
    const data = await res.json();
    const list = document.getElementById('closure-list');
    if (!data.success || !data.items || data.items.length === 0) {
        list.innerHTML = '<p style="color:var(--text-secondary);font-size:13px;">Žiadne naplánované zatvorenia.</p>';
        return;
    }
    list.innerHTML = data.items.map(c => `
        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--bg-color);border-radius:10px;border:1px solid var(--border-color);margin-bottom:8px;">
            <span style="font-size:13px;font-weight:600;color:var(--text-primary);flex:1;">
                ${c.date_from} – ${c.date_to}${c.reason ? ' · ' + c.reason : ''}
            </span>
            <button onclick="deleteClosure(${c.id})" style="background:none;border:none;cursor:pointer;color:#ef4444;display:flex;align-items:center;" title="Odstrániť">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
            </button>
        </div>`).join('');
}

async function addClosure() {
    const from = document.getElementById('closure-from').value;
    const to = document.getElementById('closure-to').value;
    const reason = document.getElementById('closure-reason').value;
    if (!from || !to) { showAppToast('Vyplňte dátumy.', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'add_closure'); fd.append('date_from', from); fd.append('date_to', to); fd.append('reason', reason);
    const res = await fetch('api/closures.php', { method: 'POST', body: fd });
    const data = await res.json();
    showAppToast(data.message || (data.success ? 'Pridané.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) {
        document.getElementById('closure-from').value = '';
        document.getElementById('closure-to').value = '';
        document.getElementById('closure-reason').value = '';
        loadClosures();
    }
}

async function deleteClosure(id) {
    const fd = new FormData(); fd.append('action', 'delete_closure'); fd.append('id', id);
    const res = await fetch('api/closures.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) loadClosures();
}

document.addEventListener('DOMContentLoaded', () => { loadTeam(); loadClosures(); });
</script>
</body>
</html>
