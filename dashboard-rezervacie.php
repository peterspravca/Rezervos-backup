<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Zoznam rezervácií - ' . BRAND_NAME;
$currentPage = 'rezervacie';
require_once 'includes/dashboard-head.php';
?>

<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>

<div class="admin-main">
    <?php $headerTitle = 'Rezervácie'; $headerIcon = 'list_alt'; require_once 'includes/dashboard-topbar.php'; ?>

    <div class="admin-content">
        <div class="section">

            <!-- Confirmation Mode Card -->
            <div class="vueto-card" style="padding:16px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(176,128,66,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="material-symbols-outlined" style="color:var(--primary-color);font-size:22px;">how_to_reg</span>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:var(--text-primary);">Režim potvrdzovania rezervácií</div>
                        <div style="font-size:12.5px;color:var(--text-secondary);" id="conf-mode-desc">Načítavam...</div>
                    </div>
                </div>
                <!-- Segmentový prepínač -->
                <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:4px;display:flex;gap:0;flex-shrink:0;" role="group" aria-label="Režim potvrdzovania">
                    <button id="conf-btn-manual" onclick="setConfirmationMode('manual')" class="btn-secondary"
                        style="border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border:none !important;box-shadow:none !important;transition:background 0.18s,color 0.18s;">
                        <span class="material-symbols-outlined" style="font-size:16px;">pending_actions</span> Manuálne
                    </button>
                    <button id="conf-btn-auto" onclick="setConfirmationMode('auto')" class="btn-secondary"
                        style="border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border:none !important;box-shadow:none !important;transition:background 0.18s,color 0.18s;">
                        <span class="material-symbols-outlined" style="font-size:16px;">auto_awesome</span> Automaticky
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="vueto-card" style="padding:18px 22px;margin-bottom:20px;">
                <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;">
                    <div style="flex:1;min-width:140px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Dátum od</label>
                        <input type="date" id="filter-date-from" class="form-control" style="padding:9px 12px;font-size:13px;">
                    </div>
                    <div style="flex:1;min-width:140px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Dátum do</label>
                        <input type="date" id="filter-date-to" class="form-control" style="padding:9px 12px;font-size:13px;">
                    </div>
                    <div style="flex:1;min-width:150px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Stav</label>
                        <select id="filter-status" class="form-control" style="padding:9px 36px 9px 12px;font-size:13px;">
                            <option value="">Všetky stavy</option>
                            <option value="pending">Čaká na potvrdenie</option>
                            <option value="confirmed">Potvrdené</option>
                            <option value="completed">Dokončené</option>
                            <option value="cancelled">Zrušené</option>
                        </select>
                    </div>
                    <div style="flex:2;min-width:180px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Vyhľadávanie</label>
                        <input type="text" id="filter-search" class="form-control" placeholder="Meno zákazníka, e-mail, služba..." style="padding:9px 12px;font-size:13px;">
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;padding-bottom:0;">
                        <button class="btn-primary" onclick="applyFilters()" style="padding:9px 18px;font-size:13px;height:38px;">
                            <span class="material-symbols-outlined" style="font-size:16px;">search</span> Filtrovať
                        </button>
                        <button class="btn" onclick="resetFilters()" style="padding:9px 14px;font-size:13px;height:38px;" title="Resetovať filtre">
                            <span class="material-symbols-outlined" style="font-size:16px;">refresh</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div id="bookings-stats" style="display:none;margin-bottom:16px;padding:12px 16px;background:rgba(176,128,66,0.07);border:1px solid rgba(176,128,66,0.2);border-radius:12px;font-size:13px;color:var(--text-secondary);">
                Zobrazujem <strong id="stats-count" style="color:var(--text-primary);">0</strong> rezervácií
            </div>

            <!-- Bookings Table -->
            <div class="vueto-card">
                <div class="vueto-card-header">
                    <div>
                        <h2 class="section-header" style="margin:0 0 4px 0;">
                            <span class="material-symbols-outlined">format_list_bulleted</span> Zoznam rezervácií
                        </h2>
                        <p style="margin:0;font-size:13px;color:var(--text-secondary);">Kompletný prehľad všetkých rezervácií vo vašom salóne.</p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button class="btn-primary" onclick="exportBookings()" style="padding:8px 16px;font-size:13px;">
                            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">download</span> Export CSV
                        </button>
                        <a href="dashboard.php" class="btn" style="padding:8px 16px;font-size:13px;text-decoration:none;">
                            <span class="material-symbols-outlined" style="font-size:16px;">calendar_month</span> Kalendár
                        </a>
                    </div>
                </div>
                <div class="vueto-table-wrapper">
                    <table class="vueto-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Zákazník</th>
                                <th>Služba a Cena</th>
                                <th>Termín</th>
                                <th>Stav</th>
                                <th>Akcie</th>
                            </tr>
                        </thead>
                        <tbody id="bookings-tbody">
                            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary);">
                                <span class="material-symbols-outlined" style="font-size:32px;display:block;margin-bottom:8px;">hourglass_empty</span>
                                Načítavam rezervácie...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Booking Detail -->
<div id="booking-detail-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:2000;justify-content:center;align-items:center;">
    <div class="modal-content" style="max-width:480px;max-height:90vh;overflow-y:auto;">
        <div class="modal-header">
            <h2>Detail rezervácie</h2>
            <button class="close-modal" onclick="closeBookingDetail()">&times;</button>
        </div>
        <div class="modal-body" id="booking-detail-body">
            <!-- Filled by JS -->
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
// Utility + notifikácie sú v /assets/js/dashboard-common.js

// ---- Bookings ----
let allBookings = [];
let currentDetailId = null;

async function loadBookingsList() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_bookings');
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let text = await res.text();
        let data = { success: false, data: [] };
        try { data = JSON.parse(text); } catch(e) { console.error('JSON parse error', text.substring(0, 100)); }

        if (data.success && Array.isArray(data.data)) {
            allBookings = data.data.slice().reverse(); // newest first
            renderBookings(allBookings, data.is_over_limit);
        } else {
            document.getElementById('bookings-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-secondary);">Žiadne dáta.</td></tr>';
        }
    } catch(err) {
        console.error('Error loading bookings', err);
        document.getElementById('bookings-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#ef4444;">Chyba pri načítaní.</td></tr>';
    }
}

function renderBookings(bookings, is_over_limit) {
    const tbody = document.getElementById('bookings-tbody');
    const statsRow = document.getElementById('bookings-stats');
    const statsCount = document.getElementById('stats-count');

    if (statsRow) statsRow.style.display = 'block';
    if (statsCount) statsCount.innerText = bookings.length;

    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:32px;display:block;margin-bottom:8px;">event_busy</span>Žiadne rezervácie pre zvolené kritériá.</td></tr>';
        return;
    }

    let html = '';
    bookings.forEach(b => {
        let avatar = b.customer_avatar ? b.customer_avatar : 'assets/img/default-avatar.png';
        let verifiedBadge = b.customer_verified ? '<span class="material-symbols-outlined" style="color:#3498db;font-size:15px;" title="Overená karta">verified</span>' : '';
        let dateStr = b.booking_date ? new Date(b.booking_date + 'T00:00:00').toLocaleDateString('sk-SK') : '—';
        let nameLock = is_over_limit ? '<span class="material-symbols-outlined" style="font-size:13px;color:#e74c3c;">lock</span> ' : '';

        let statusBadge = '';
        if (b.status === 'pending') statusBadge = '<span class="vueto-badge yellow">Čaká</span>';
        else if (b.status === 'confirmed') statusBadge = '<span class="vueto-badge blue">Potvrdené</span>';
        else if (b.status === 'completed') statusBadge = '<span class="vueto-badge green">Dokončené</span>';
        else if (b.status === 'cancelled') statusBadge = '<span class="vueto-badge" style="background:rgba(231,76,60,0.1);color:#e74c3c;border:1px solid rgba(231,76,60,0.2);">Zrušené</span>';
        else statusBadge = `<span class="vueto-badge">${escapeHtml(b.status)}</span>`;

        html += `<tr>
            <td style="color:var(--text-secondary);font-size:13px;">#${b.id}</td>
            <td>
                <div style="display:flex;align-items:center;gap:12px;">
                    <img src="${escapeHtml(avatar)}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <div class="td-icon-text">
                        <strong>${nameLock}${escapeHtml(b.customer_name || 'Neznámy')} ${verifiedBadge}${b.customer_note ? ` <span class="material-symbols-outlined" style="font-size:15px;color:var(--primary-color);vertical-align:middle;" title="${escapeHtml(b.customer_note)}">sticky_note_2</span>` : ''}</strong>
                        <span><span class="material-symbols-outlined" style="font-size:14px;">mail</span>${escapeHtml(b.customer_email || '—')}</span>
                        ${b.customer_phone ? `<span><span class="material-symbols-outlined" style="font-size:14px;">call</span>${escapeHtml(b.customer_phone)}</span>` : ''}
                    </div>
                </div>
            </td>
            <td>
                <div class="td-icon-text">
                    <strong>${escapeHtml(b.service_name || '—')}</strong>
                    <span>${b.price ? parseFloat(b.price).toFixed(2) + ' €' : '—'} · ${b.service_duration || '?'} min</span>
                </div>
            </td>
            <td>
                <div class="td-icon-text">
                    <strong>${dateStr}</strong>
                    <span>${escapeHtml(b.start_time || '—')}${b.end_time ? ' – ' + escapeHtml(b.end_time) : ''}</span>
                </div>
            </td>
            <td>${statusBadge}</td>
            <td>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button class="btn-action" title="Detail" onclick="openBookingDetail(${b.id})">
                        <span class="material-symbols-outlined" style="color:var(--primary-color);">open_in_new</span>
                    </button>
                    ${b.status === 'pending' ? `
                    <button class="btn-action" title="Potvrdiť" onclick="changeStatus(${b.id},'confirmed')">
                        <span class="material-symbols-outlined" style="color:#10b981;">check_circle</span>
                    </button>
                    <button class="btn-action" title="Zrušiť" onclick="changeStatus(${b.id},'cancelled')">
                        <span class="material-symbols-outlined" style="color:#ef4444;">cancel</span>
                    </button>` : ''}
                    ${b.status === 'confirmed' ? `
                    <button class="btn-action" title="Dokončiť" onclick="changeStatus(${b.id},'completed')">
                        <span class="material-symbols-outlined" style="color:#3b82f6;">task_alt</span>
                    </button>` : ''}
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function applyFilters() {
    const dateFrom = document.getElementById('filter-date-from').value;
    const dateTo = document.getElementById('filter-date-to').value;
    const status = document.getElementById('filter-status').value;
    const search = document.getElementById('filter-search').value.trim().toLowerCase();

    let filtered = allBookings.filter(b => {
        if (dateFrom && b.booking_date < dateFrom) return false;
        if (dateTo && b.booking_date > dateTo) return false;
        if (status && b.status !== status) return false;
        if (search) {
            const haystack = [b.customer_name, b.customer_email, b.customer_phone, b.service_name].join(' ').toLowerCase();
            if (!haystack.includes(search)) return false;
        }
        return true;
    });

    renderBookings(filtered, false);
}

function resetFilters() {
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-search').value = '';
    renderBookings(allBookings, false);
}

function openBookingDetail(id) {
    const b = allBookings.find(x => x.id == id);
    if (!b) return;
    currentDetailId = id;

    let avatar = b.customer_avatar ? b.customer_avatar : 'assets/img/default-avatar.png';
    let dateStr = b.booking_date ? new Date(b.booking_date + 'T00:00:00').toLocaleDateString('sk-SK') : '—';

    let statusBadge = '';
    if (b.status === 'pending') statusBadge = '<span class="vueto-badge yellow">Čaká na potvrdenie</span>';
    else if (b.status === 'confirmed') statusBadge = '<span class="vueto-badge blue">Potvrdené</span>';
    else if (b.status === 'completed') statusBadge = '<span class="vueto-badge green">Dokončené</span>';
    else if (b.status === 'cancelled') statusBadge = '<span class="vueto-badge" style="background:rgba(231,76,60,0.1);color:#e74c3c;">Zrušené</span>';

    let html = `
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;padding:14px;background:var(--bg-color);border-radius:12px;border:1px solid var(--border-color);">
            <img src="${escapeHtml(avatar)}" style="width:54px;height:54px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <div>
                <strong style="font-size:16px;">${escapeHtml(b.customer_name || 'Neznámy')}</strong><br>
                <span style="font-size:13px;color:var(--text-secondary);">${escapeHtml(b.customer_email || '—')}</span><br>
                ${b.customer_phone ? `<span style="font-size:13px;color:var(--text-secondary);">${escapeHtml(b.customer_phone)}</span>` : ''}
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div style="background:var(--bg-color);border-radius:10px;padding:12px 14px;border:1px solid var(--border-color);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:4px;">Služba</div>
                <div style="font-size:14px;font-weight:600;">${escapeHtml(b.service_name || '—')}</div>
                <div style="font-size:12px;color:var(--text-secondary);">${b.price ? parseFloat(b.price).toFixed(2) + ' €' : '—'} · ${b.service_duration || '?'} min</div>
            </div>
            <div style="background:var(--bg-color);border-radius:10px;padding:12px 14px;border:1px solid var(--border-color);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:4px;">Termín</div>
                <div style="font-size:14px;font-weight:600;">${dateStr}</div>
                <div style="font-size:12px;color:var(--text-secondary);">${escapeHtml(b.start_time || '—')} – ${escapeHtml(b.end_time || '—')}</div>
            </div>
        </div>
        <div style="margin-bottom:16px;">${statusBadge}</div>
        ${b.customer_note ? `
        <div style="margin-bottom:16px;background:rgba(176,128,66,0.08);border:1px solid var(--primary-color);border-radius:10px;padding:12px 14px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--primary-color);margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                <span class="material-symbols-outlined" style="font-size:15px;">sticky_note_2</span> Poznámka zákazníka
            </div>
            <div style="font-size:13.5px;color:var(--text-primary);white-space:pre-wrap;">${escapeHtml(b.customer_note)}</div>
        </div>` : ''}
        <div class="form-group">
            <label>Zmeniť stav</label>
            <select id="detail-status-select" class="form-control">
                <option value="pending" ${b.status === 'pending' ? 'selected' : ''}>Čaká na potvrdenie</option>
                <option value="confirmed" ${b.status === 'confirmed' ? 'selected' : ''}>Potvrdené</option>
                <option value="completed" ${b.status === 'completed' ? 'selected' : ''}>Dokončené</option>
                <option value="cancelled" ${b.status === 'cancelled' ? 'selected' : ''}>Zrušené</option>
            </select>
        </div>
        <button class="btn-primary" style="width:100%;" onclick="saveDetailStatus()">Uložiť zmeny</button>
    `;

    document.getElementById('booking-detail-body').innerHTML = html;
    document.getElementById('booking-detail-modal').style.display = 'flex';
}

function closeBookingDetail() {
    document.getElementById('booking-detail-modal').style.display = 'none';
    currentDetailId = null;
}

async function saveDetailStatus() {
    if (!currentDetailId) return;
    const newStatus = document.getElementById('detail-status-select').value;
    await changeStatus(currentDetailId, newStatus, false);
    closeBookingDetail();
}

async function changeStatus(id, status, closeModal = true) {
    const fd = new FormData();
    fd.append('action', 'update_booking');
    fd.append('id', id);
    fd.append('status', status);
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        if (data.success) {
            const b = allBookings.find(x => x.id == id);
            if (b) b.status = status;
            showAppToast('Stav rezervácie bol zmenený.', 'success');
            if (closeModal) closeBookingDetail();
            // Re-render
            applyFilters();
        } else {
            showAppToast(data.message || 'Chyba pri zmene stavu.', 'error');
        }
    } catch(err) {
        showAppToast('Chyba siete.', 'error');
    }
}

function exportBookings() {
    const rows = [['#', 'Zákazník', 'Email', 'Telefón', 'Služba', 'Cena (€)', 'Dĺžka (min)', 'Dátum', 'Čas od', 'Čas do', 'Stav']];
    allBookings.forEach(b => {
        rows.push([b.id, b.customer_name || '', b.customer_email || '', b.customer_phone || '', b.service_name || '', b.price || '', b.service_duration || '', b.booking_date || '', b.start_time || '', b.end_time || '', b.status || '']);
    });
    const csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'rezervacie.csv'; a.click();
    URL.revokeObjectURL(url);
}

// ---- Confirmation Mode ----
let currentConfMode = 'manual';

async function loadConfirmationMode() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_confirmation_mode');
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) applyConfModeUI(data.mode);
    } catch(e) {}
}

function applyConfModeUI(mode) {
    currentConfMode = mode;
    const btnManual = document.getElementById('conf-btn-manual');
    const btnAuto   = document.getElementById('conf-btn-auto');
    const desc      = document.getElementById('conf-mode-desc');
    if (!btnManual || !btnAuto) return;

    const isAuto = (mode === 'auto');

    // Aktívny button — zlaté pozadie, biely text (inline style prekonáva btn-secondary !important)
    const activeStyle  = 'border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border:none !important;box-shadow:0 2px 8px rgba(176,128,66,0.3) !important;background:var(--primary-color) !important;color:#fff !important;transition:background 0.18s,color 0.18s;';
    const inactiveStyle = 'border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border:none !important;box-shadow:none !important;transition:background 0.18s,color 0.18s;';

    btnManual.style.cssText = !isAuto ? activeStyle : inactiveStyle;
    btnAuto.style.cssText   =  isAuto ? activeStyle : inactiveStyle;

    if (desc) desc.textContent = isAuto
        ? 'Každá nová rezervácia bude potvrdená automaticky.'
        : 'Každá nová rezervácia čaká na vaše manuálne schválenie.';
}

async function setConfirmationMode(mode) {
    if (mode === currentConfMode) return;
    try {
        const fd = new FormData();
        fd.append('action', 'save_confirmation_mode');
        fd.append('mode', mode);
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            applyConfModeUI(mode);
            showAppToast(mode === 'auto' ? 'Nastavené: Automatické potvrdzovanie' : 'Nastavené: Manuálne potvrdzovanie', 'success');
        }
    } catch(e) { showAppToast('Chyba pri ukladaní nastavenia.', 'error'); }
}

// ---- Init ----
document.addEventListener('DOMContentLoaded', function() {
    // Default filter: from 30 days ago to today
    const today = new Date();
    const from = new Date(); from.setDate(today.getDate() - 30);
    // Don't pre-set filters, load all
    loadConfirmationMode();
    loadBookingsList();
    // Notifikácie spravuje dashboard-common.js
});
</script>
</body>
</html>
