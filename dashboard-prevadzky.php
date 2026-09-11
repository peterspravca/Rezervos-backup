<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
if (!empty($_SESSION['is_employee'])) {
    header('Location: dashboard.php'); exit;
}
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
$pageTitle = 'Moje prevádzky - ' . BRAND_NAME;
$currentPage = 'prevadzky-skupina';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Moje prevádzky'; $headerIcon = 'store'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">

      <div class="vueto-card" style="margin-bottom:20px;">
        <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
          <div>
            <h2 class="section-header" style="margin:0;">
              <span class="material-symbols-outlined" style="color:var(--primary-color);">store</span>
              Súhrn naprieč prevádzkami
            </h2>
            <p class="section-desc" style="margin-top:5px;">Ak vlastníte viac prevádzok (samostatné účty), prepojte ich sem — uvidíte tržby a rezervácie všetkých na jednom mieste a rýchlo medzi nimi prepnete.</p>
          </div>
        </div>
        <div class="vueto-card-body" style="padding:24px 20px;">
          <p id="og-limit-info" style="margin:0 0 16px 0;font-size:12.5px;color:var(--text-secondary);"></p>
          <div id="og-summary-row" style="display:none;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;"></div>
          <div id="og-establishments-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            <div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-secondary);">Načítavam...</div>
          </div>
        </div>
      </div>

      <div class="vueto-card">
        <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
          <div>
            <h2 class="section-header" style="margin:0;">
              <span class="material-symbols-outlined" style="color:var(--primary-color);">link</span>
              Prepojiť ďalšiu prevádzku
            </h2>
            <p class="section-desc" style="margin-top:5px;">Zadajte prihlasovacie údaje druhého vášho firemného účtu (inej prevádzky). Prepojenie je obojstranné a dá sa kedykoľvek zrušiť.</p>
          </div>
        </div>
        <div class="vueto-card-body" style="padding:24px 20px;">
          <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="flex:1;min-width:220px;margin-bottom:0;">
              <label>E-mail druhého účtu</label>
              <input type="email" id="og-link-email" placeholder="druha-prevadzka@napr.sk">
            </div>
            <div class="form-group" style="flex:1;min-width:220px;margin-bottom:0;">
              <label>Heslo druhého účtu</label>
              <input type="password" id="og-link-password" placeholder="Heslo">
            </div>
            <button type="button" id="og-link-btn" class="btn-primary" onclick="ogLinkEstablishment()" style="padding:11px 20px;white-space:nowrap;">
              <span class="material-symbols-outlined" style="font-size:18px;">link</span> Prepojiť
            </button>
          </div>
          <p style="margin:10px 0 0 0;font-size:12px;color:var(--text-secondary);">Heslo slúži len na overenie, že daný účet naozaj vlastníte — Rezervos ho nikde neukladá ani nezobrazuje.</p>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
async function ogLoadGroup() {
    const grid = document.getElementById('og-establishments-grid');
    const summaryRow = document.getElementById('og-summary-row');
    try {
        const fd = new FormData(); fd.append('action', 'get_group');
        const res = await fetch('api/owner_group.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) { grid.innerHTML = `<p style="color:#ef4444;grid-column:1/-1;">${escapeHtml(data.message || 'Chyba pri načítaní.')}</p>`; return; }

        const list = data.establishments || [];
        const allowed = data.allowed_branches || 1;
        const limitInfo = document.getElementById('og-limit-info');
        if (limitInfo) {
            limitInfo.innerHTML = allowed >= 999999
                ? 'Váš balík umožňuje neobmedzený počet prepojených prevádzok.'
                : `Váš balík umožňuje <strong>${allowed}</strong> ${allowed === 1 ? 'prevádzku' : 'prevádzky'} (aktuálne prepojené: ${list.length}). Viac získate dokúpením doplnku „Ďalšia pobočka” alebo prechodom na vyšší balík v <a href="dashboard-balik.php" style="color:var(--primary-color);">Môj Balík</a>.`;
        }
        if (data.is_grouped) {
            const totalRevenue = list.reduce((s, e) => s + parseFloat(e.revenue_month || 0), 0);
            const totalToday = list.reduce((s, e) => s + parseInt(e.bookings_today || 0), 0);
            const totalMonth = list.reduce((s, e) => s + parseInt(e.bookings_month || 0), 0);
            summaryRow.style.display = 'grid';
            summaryRow.innerHTML = `
                ${ogStatTile('storefront', list.length, 'Prevádzok v skupine')}
                ${ogStatTile('event', totalToday, 'Rezervácií dnes (spolu)')}
                ${ogStatTile('calendar_month', totalMonth, 'Rezervácií tento mesiac (spolu)')}
                ${ogStatTile('payments', totalRevenue.toFixed(2) + ' €', 'Tržby tento mesiac (spolu)')}
            `;
        } else {
            summaryRow.style.display = 'none';
        }

        if (!list.length) { grid.innerHTML = `<p style="color:var(--text-secondary);grid-column:1/-1;">Zatiaľ nemáte žiadnu prevádzku.</p>`; return; }

        grid.innerHTML = list.map(e => `
            <div style="background:var(--card-bg);border:1px solid ${e.is_current ? 'var(--primary-color)' : 'var(--border-color)'};border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:42px;height:42px;border-radius:10px;background:${e.image_url ? `url('${e.image_url}') center/cover` : 'var(--bg-color)'};flex-shrink:0;border:1px solid var(--border-color);"></div>
                    <div style="overflow:hidden;">
                        <strong style="font-size:14.5px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(e.name || 'Prevádzka')}</strong>
                        <span style="font-size:11.5px;color:var(--text-secondary);">${escapeHtml(e.email || '')}</span>
                    </div>
                    ${e.is_current ? `<span style="margin-left:auto;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:6px;background:rgba(176,128,66,0.12);color:var(--primary-color);white-space:nowrap;">Aktívna</span>` : ''}
                </div>
                <div style="display:flex;gap:8px;font-size:12.5px;color:var(--text-secondary);">
                    <div style="flex:1;background:var(--bg-color);border-radius:8px;padding:8px;text-align:center;"><strong style="display:block;color:var(--text-primary);font-size:15px;">${e.bookings_today}</strong>dnes</div>
                    <div style="flex:1;background:var(--bg-color);border-radius:8px;padding:8px;text-align:center;"><strong style="display:block;color:var(--text-primary);font-size:15px;">${e.bookings_month}</strong>tento mesiac</div>
                    <div style="flex:1;background:var(--bg-color);border-radius:8px;padding:8px;text-align:center;"><strong style="display:block;color:var(--primary-color);font-size:15px;">${parseFloat(e.revenue_month).toFixed(0)} €</strong>tržby</div>
                </div>
                <div style="display:flex;gap:8px;">
                    ${e.is_current ? '' : `<button type="button" class="btn-primary" style="flex:1;padding:8px;font-size:12.5px;" onclick="ogSwitchTo(${e.owner_user_id})"><span class="material-symbols-outlined" style="font-size:15px;">swap_horiz</span> Prepnúť sem</button>`}
                    ${(!e.is_current) ? `<button type="button" class="btn-secondary" style="padding:8px 10px;font-size:12.5px;color:#ef4444;" title="Odpojiť zo skupiny" onclick="ogUnlink(${e.owner_user_id}, '${escapeJs(e.name || '')}')"><span class="material-symbols-outlined" style="font-size:16px;">link_off</span></button>` : ''}
                </div>
            </div>
        `).join('');
    } catch (err) {
        console.error(err);
        grid.innerHTML = `<p style="color:#ef4444;grid-column:1/-1;">Chyba pripojenia.</p>`;
    }
}

function ogStatTile(icon, value, label) {
    return `<div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:12px;padding:16px;text-align:center;">
        <span class="material-symbols-outlined" style="color:var(--primary-color);font-size:22px;">${icon}</span>
        <div style="font-size:20px;font-weight:700;margin-top:6px;">${value}</div>
        <div style="font-size:12px;color:var(--text-secondary);">${label}</div>
    </div>`;
}

function escapeJs(str) { return String(str).replace(/'/g, "\\'"); }

async function ogLinkEstablishment() {
    const email = document.getElementById('og-link-email').value.trim();
    const password = document.getElementById('og-link-password').value;
    if (!email || !password) { showAppToast('Zadajte e-mail aj heslo druhého účtu.', 'error'); return; }
    const btn = document.getElementById('og-link-btn');
    btn.disabled = true;
    try {
        const fd = new FormData(); fd.append('action', 'link_establishment'); fd.append('email', email); fd.append('password', password);
        const res = await fetch('api/owner_group.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAppToast(data.message, 'success');
            document.getElementById('og-link-email').value = '';
            document.getElementById('og-link-password').value = '';
            ogLoadGroup();
        } else {
            showAppToast(data.message || 'Chyba pri prepájaní.', 'error', data.limit_reached ? 6000 : 3000);
        }
    } catch (err) {
        showAppToast('Chyba pripojenia.', 'error');
    }
    btn.disabled = false;
}

async function ogSwitchTo(targetUserId) {
    const fd = new FormData(); fd.append('action', 'switch_to'); fd.append('user_id', targetUserId);
    const res = await fetch('api/owner_group.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { window.location.href = 'dashboard.php'; }
    else showAppToast(data.message || 'Chyba pri prepínaní.', 'error');
}

async function ogUnlink(targetUserId, name) {
    const ok = await confirmModal(`Odpojiť "${name}" zo skupiny prevádzok? Prevádzka bude naďalej fungovať samostatne, len ju prestanete vidieť v tomto súhrne.`, { okText: 'Odpojiť', cancelText: 'Zrušiť' });
    if (!ok) return;
    const fd = new FormData(); fd.append('action', 'unlink_establishment'); fd.append('user_id', targetUserId);
    const res = await fetch('api/owner_group.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { showAppToast(data.message, 'success'); ogLoadGroup(); }
    else showAppToast(data.message || 'Chyba pri odpájaní.', 'error');
}

document.addEventListener('DOMContentLoaded', ogLoadGroup);
</script>
</body>
</html>
