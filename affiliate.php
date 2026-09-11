<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); exit;
}
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
require_once 'includes/affiliate_helper.php';
require_once 'includes/employee_permissions_helper.php';
affiliate_migrate($pdo);

// Zamestnanec zdieľa session (a teda affiliate účet) s majiteľom prevádzky — bez explicitne
// udeleného oprávnenia 'settings' sem nesmie.
if (!empty($_SESSION['is_employee']) && !employeeCan('settings')) {
    header('Location: dashboard.php'); exit;
}

$pageTitle = 'Affiliate program - ' . BRAND_NAME;
$currentPage = 'affiliate';
require_once 'includes/dashboard-head.php';

$referralBase = 'https://' . BRAND_SITE . '/?ref=';
?>
<?php
if ($_SESSION['user_role'] === 'business') {
    $currentPage = 'affiliate';
    echo '<div class="admin-sidebar">';
    require_once 'includes/sidebar.php';
    echo '</div>';
} else {
    $active_nav = 'affiliate'; $spa_host = false;
    require_once 'includes/customer-sidebar.php';
}
?>
<div class="admin-main">
    <?php $headerTitle = 'Affiliate program'; $headerIcon = 'handshake'; require_once 'includes/dashboard-topbar.php'; ?>
    <div class="admin-content">
        <div class="section">

            <div class="vueto-card" style="margin-bottom:22px;">
                <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
                    <h2 class="section-header" style="margin:0;">
                        <span class="material-symbols-outlined" style="color:var(--primary-color);">handshake</span>
                        Affiliate program
                    </h2>
                    <p class="section-desc" style="margin-top:5px;">Zarábajte províziu za privedenie novej platiacej prevádzky do <?= htmlspecialchars(BRAND_NAME) ?>.</p>
                </div>
            </div>

            <div class="vueto-card" id="status-none" style="display:none;margin-bottom:22px;">
                <div class="vueto-card-body" style="padding:22px 20px;">
                    <h3 style="margin-top:0;">Vaša odmena za predaj (prvý rok)</h3>
                    <div class="rate-grid" id="rate-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:18px 0;"></div>
                    <p style="font-size:13px;color:var(--text-secondary);">Odmena sa pripíše, keď si prevádzka, ktorú privediete, sama aktivuje platený <strong>ročný</strong> balík (START, PRO alebo VIP).</p>
                    <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:12px;padding:14px 16px;margin-top:14px;">
                        <strong style="font-size:13.5px;color:#10b981;display:flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:17px;">autorenew</span> Priebežný príjem</strong>
                        <p style="margin:6px 0 0 0;font-size:13px;color:var(--text-secondary);">Pokým sa o svoje prevádzky staráte a tie si balík rok čo rok obnovujú, dostávate navyše každý rok <strong id="renewal-pct-text">10 %</strong> z ceny ich ročného balíka — nie je to teda len jednorazová odmena.</p>
                    </div>
                    <p style="font-size:13px;color:var(--text-secondary);margin-top:14px;">Vyplatenie prebieha bankovým prevodom po dohode s administrátorom.</p>
                    <button type="button" class="btn-primary" style="padding:11px 22px;" onclick="applyAffiliate()">Chcem sa zapojiť</button>
                </div>
            </div>

            <div class="vueto-card" id="status-pending" style="display:none;margin-bottom:22px;">
                <div class="vueto-card-body" style="padding:22px 20px;">
                    <span class="status-badge status-pending"><span class="material-symbols-outlined" style="font-size:15px;">hourglass_top</span> Žiadosť sa posudzuje</span>
                    <p style="margin-top:14px;color:var(--text-secondary);">Vašu žiadosť sme prijali. Po schválení tu uvidíte svoj odporúčací odkaz a prehľad provízií.</p>
                </div>
            </div>

            <div class="vueto-card" id="status-rejected" style="display:none;margin-bottom:22px;">
                <div class="vueto-card-body" style="padding:22px 20px;">
                    <span class="status-badge status-rejected"><span class="material-symbols-outlined" style="font-size:15px;">block</span> Žiadosť zamietnutá</span>
                    <p style="margin-top:14px;color:var(--text-secondary);">Vašu žiadosť sme tentokrát nemohli schváliť. V prípade otázok nás kontaktujte cez Podporu.</p>
                </div>
            </div>

            <div id="status-approved" style="display:none;">
                <div class="vueto-card" style="margin-bottom:22px;">
                    <div class="vueto-card-body" style="padding:22px 20px;">
                        <h3 style="margin-top:0;">Váš odporúčací odkaz</h3>
                        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;">
                            <input type="text" id="referral-link" readonly style="flex:1;padding:10px 14px;border-radius:10px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:13px;font-family:monospace;">
                            <button type="button" class="btn-primary" style="padding:10px 18px;" onclick="copyReferralLink()">Kopírovať</button>
                        </div>
                        <p style="font-size:12.5px;color:var(--text-secondary);margin-top:8px;">Kód: <strong id="referral-code-text"></strong> — dá sa zadať aj ručne do poľa "odporúčací/promo kód" pri registrácii.</p>
                    </div>
                </div>
                <div class="vueto-card" style="margin-bottom:22px;">
                    <div class="vueto-card-body" style="padding:22px 20px;">
                        <h3 style="margin-top:0;font-size:14px;">Odmena za predaj (prvý rok)</h3>
                        <div class="rate-grid" id="rate-grid-2" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;"></div>
                        <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:12px;padding:14px 16px;margin-top:14px;">
                            <strong style="font-size:13.5px;color:#10b981;display:flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:17px;">autorenew</span> + <span id="renewal-pct-text-2">10 %</span> z ceny balíka každý ďalší rok, pokým sa prevádzka o svoj balík stará a naďalej ho ročne obnovuje</strong>
                        </div>
                    </div>
                </div>
                <div class="vueto-card">
                    <div class="vueto-card-body" style="padding:22px 20px;overflow-x:auto;">
                        <div style="display:flex;gap:20px;margin-bottom:18px;flex-wrap:wrap;">
                            <div><strong style="font-size:20px;color:#f59e0b;" id="total-pending">0,00 €</strong><div style="font-size:12px;color:var(--text-secondary);">Čaká na výplatu</div></div>
                            <div><strong style="font-size:20px;color:#10b981;" id="total-paid">0,00 €</strong><div style="font-size:12px;color:var(--text-secondary);">Vyplatené</div></div>
                        </div>
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead><tr style="text-align:left;color:var(--text-secondary);font-size:11.5px;text-transform:uppercase;"><th style="padding:10px 8px;">Dátum</th><th>Balík</th><th>Typ</th><th>Suma</th><th>Stav</th></tr></thead>
                            <tbody id="commissions-body"><tr><td colspan="4" style="color:var(--text-secondary);padding:10px 8px;">Zatiaľ žiadne provízie.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .rate-tile { background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; padding:16px; text-align:center; }
    .rate-tile strong { display:block; font-size:22px; color:var(--primary-color); }
    .rate-tile span { font-size:12px; color:var(--text-secondary); }
    .status-badge { display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:700; padding:5px 12px; border-radius:8px; }
    .status-pending { background:rgba(245,158,11,0.12); color:#f59e0b; }
    .status-approved { background:rgba(16,185,129,0.12); color:#10b981; }
    .status-rejected { background:rgba(239,68,68,0.12); color:#ef4444; }
</style>

<script>
const REFERRAL_BASE = <?= json_encode($referralBase) ?>;
const TIER_NAMES = { start: 'START', pro: 'PRO', vip: 'VIP' };

function renderRateGrid(elId, rates) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.innerHTML = Object.keys(rates).map(t => `
        <div class="rate-tile">
            <strong>${parseFloat(rates[t]).toFixed(0)} €</strong>
            <span>${TIER_NAMES[t] || t.toUpperCase()} (ročne)</span>
        </div>
    `).join('');
}

async function loadStatus() {
    try {
        const fd = new FormData(); fd.append('action', 'get_status');
        const res = await fetch('api/affiliate.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) return;

        ['status-none', 'status-pending', 'status-rejected', 'status-approved'].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });

        const pctEl1 = document.getElementById('renewal-pct-text'), pctEl2 = document.getElementById('renewal-pct-text-2');
        if (pctEl1) pctEl1.innerText = data.renewal_percent + ' %';
        if (pctEl2) pctEl2.innerText = data.renewal_percent + ' %';

        if (data.status === 'none') {
            document.getElementById('status-none').style.display = 'block';
            renderRateGrid('rate-grid', data.rates);
        } else if (data.status === 'pending') {
            document.getElementById('status-pending').style.display = 'block';
        } else if (data.status === 'rejected') {
            document.getElementById('status-rejected').style.display = 'block';
        } else if (data.status === 'approved') {
            document.getElementById('status-approved').style.display = 'block';
            renderRateGrid('rate-grid-2', data.rates);
            document.getElementById('referral-link').value = REFERRAL_BASE + data.code;
            document.getElementById('referral-code-text').innerText = data.code;
            document.getElementById('total-pending').innerText = data.totals.pending.toFixed(2) + ' €';
            document.getElementById('total-paid').innerText = data.totals.paid.toFixed(2) + ' €';
            const body = document.getElementById('commissions-body');
            if (data.commissions.length === 0) {
                body.innerHTML = '<tr><td colspan="5" style="color:var(--text-secondary);padding:10px 8px;">Zatiaľ žiadne provízie.</td></tr>';
            } else {
                body.innerHTML = data.commissions.map(c => `
                    <tr style="border-top:1px solid var(--border-color);">
                        <td style="padding:10px 8px;">${new Date(c.created_at).toLocaleDateString('sk-SK')}</td>
                        <td>${TIER_NAMES[c.tier] || c.tier}</td>
                        <td>${c.type === 'signup' ? 'Predaj' : 'Ročná odmena'}</td>
                        <td>${parseFloat(c.amount).toFixed(2)} €</td>
                        <td>${c.status === 'paid' ? '<span class="status-badge status-approved">Vyplatené</span>' : '<span class="status-badge status-pending">Čaká</span>'}</td>
                    </tr>
                `).join('');
            }
        }
    } catch (e) { console.error(e); }
}

async function applyAffiliate() {
    try {
        const fd = new FormData(); fd.append('action', 'apply');
        const res = await fetch('api/affiliate.php', { method: 'POST', body: fd });
        const data = await res.json();
        showAppToast(data.message, data.success ? 'success' : 'error');
        if (data.success) loadStatus();
    } catch (e) {
        showAppToast('Chyba pripojenia.', 'error');
    }
}

function copyReferralLink() {
    const val = document.getElementById('referral-link').value;
    navigator.clipboard.writeText(val).then(() => showAppToast('Odkaz skopírovaný.', 'success'))
        .catch(() => showAppToast('Kopírovanie zlyhalo.', 'error'));
}

document.addEventListener('DOMContentLoaded', loadStatus);
</script>
</body>
</html>
