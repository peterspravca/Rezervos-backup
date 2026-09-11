<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php');
    exit;
}

if (($_SESSION['onboarding_completed'] ?? 1) == 0) {
    header('Location: onboarding.php');
    exit;
}

$showPendingModal = !empty($_SESSION['show_pending_modal']);
unset($_SESSION['show_pending_modal']);

$pageTitle = 'Kalendár - ' . BRAND_NAME;
$currentPage = 'kalendar';
require_once 'includes/dashboard-head.php';
?>
<?php if ($showPendingModal): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    infoModal(
        'Profil čaká na schválenie',
        'Váš profil prevádzky je vytvorený a momentálne čaká na schválenie administrátorom. Budeme vás kontaktovať e-mailom alebo telefonicky.'
    );
});
</script>
<?php endif; ?>
<!-- Calendar-specific scripts -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/sk.global.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
/* Compact FullCalendar cells */
.fc .fc-daygrid-day { min-height: 80px !important; }
.fc .fc-daygrid-day-frame { min-height: 80px !important; padding: 3px 4px !important; }
.fc .fc-daygrid-day-number { font-size: 14px !important; padding: 4px 6px !important; line-height: 1.4 !important; }
.fc .fc-daygrid-day-top { flex-direction: row !important; }
.fc-event { font-size: 12px !important; padding: 2px 5px !important; border-radius: 4px !important; }
.fc .fc-toolbar-title { font-size: 18px !important; }
.fc .fc-button { font-size: 13px !important; padding: 5px 12px !important; }
.fc .fc-col-header-cell-cushion { font-size: 13px !important; padding: 6px 0 !important; }
.fc .fc-daygrid-event { margin-top: 1px !important; }
/* Selected day highlight */
.fc-day-selected { background: rgba(176,128,66,0.08) !important; }

/* Day drawer */
#day-drawer {
    width: 0;
    min-width: 0;
    overflow: hidden;
    transition: width 0.22s cubic-bezier(0.4,0,0.2,1);
    flex-shrink: 0;
    background: var(--bg-color);
    border-left: 1px solid var(--border-color);
    border-radius: 0 12px 12px 0;
    display: flex;
    flex-direction: column;
}
#day-drawer.open { width: 290px; }

@media (max-width: 768px) {
    #day-drawer {
        position: fixed !important;
        top: auto !important;
        bottom: -100vh;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        min-width: 0 !important;
        height: 85vh;
        border-left: none !important;
        border-top: 1px solid var(--border-color);
        border-radius: 16px 16px 0 0 !important;
        z-index: 1200;
        box-shadow: 0 -8px 32px rgba(0,0,0,0.18);
        transition: bottom 0.28s cubic-bezier(0.4,0,0.2,1) !important;
        overflow: hidden !important;
    }
    #day-drawer.open {
        width: 100% !important;
        bottom: 0 !important;
    }
}
.drawer-header { padding: 16px 16px 10px; border-bottom: 1px solid var(--border-color); flex-shrink: 0; display: flex; align-items: flex-start; }
.drawer-day-big { font-size: 38px; font-weight: 800; color: var(--primary-color); line-height: 1; }
.drawer-day-label { font-size: 13px; color: var(--text-secondary); margin-top: 3px; }
/* Occupancy bar */
.drawer-occ-wrap { padding: 10px 16px 8px; border-bottom: 1px solid var(--border-color); flex-shrink: 0; }
.drawer-occ-label { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-secondary); margin-bottom: 6px; }
.drawer-occ-bg { height: 6px; background: var(--border-color); border-radius: 3px; overflow: hidden; }
.drawer-occ-fill { height: 100%; background: var(--primary-color); border-radius: 3px; transition: width 0.4s ease; }
/* Timeline */
.drawer-timeline { flex: 1; overflow-y: auto; padding: 8px 0 4px; }
.tl-row { display: flex; align-items: flex-start; min-height: 34px; }
.tl-hour { width: 42px; flex-shrink: 0; font-size: 12px; color: var(--text-secondary); padding: 7px 0 0 14px; line-height: 1; }
.tl-line-wrap { width: 1px; background: var(--border-color); align-self: stretch; margin: 0 8px; flex-shrink: 0; }
.tl-content { flex: 1; padding: 2px 10px 2px 0; }
.tl-free-slot { height: 26px; border-radius: 4px; background: transparent; border: 1px dashed var(--border-color); display: flex; align-items: center; padding: 0 8px; margin-bottom: 2px; }
.tl-free-slot span { font-size: 12px; color: var(--text-secondary); }
.tl-booking-card {
    padding: 7px 9px; border-radius: 6px;
    border-left: 3px solid var(--primary-color);
    background: rgba(176,128,66,0.09);
    margin-bottom: 3px; cursor: pointer;
    transition: background 0.12s;
}
.tl-booking-card:hover { background: rgba(176,128,66,0.17); }
.tl-booking-card.confirmed { border-left-color: #10b981; background: rgba(16,185,129,0.08); }
.tl-booking-card.confirmed:hover { background: rgba(16,185,129,0.15); }
.tl-booking-card.cancelled { border-left-color: #9ca3af; background: rgba(156,163,175,0.07); opacity: 0.6; }
.tl-bk-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.tl-bk-info { font-size: 12px; color: var(--text-secondary); margin-top: 1px; }
.drawer-empty { text-align: center; padding: 24px 14px; color: var(--text-secondary); font-size: 13px; }
/* Last Minute banner */
.drawer-lm-banner {
    margin: 6px 12px 10px;
    padding: 11px 12px;
    border-radius: 8px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.3);
    flex-shrink: 0;
}
.drawer-lm-title { font-size: 13px; font-weight: 700; color: #065f46; display: flex; align-items: center; gap: 5px; margin-bottom: 4px; }
.drawer-lm-desc { font-size: 12px; color: #047857; margin-bottom: 8px; line-height: 1.4; }
.drawer-lm-btn { width: 100%; padding: 9px; background: #10b981; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: background 0.15s; }
.drawer-lm-btn:hover { background: #059669; }
/* Add booking button */
.drawer-add-btn {
    margin: 0 12px 12px;
    padding: 9px 12px;
    border: 1px dashed var(--border-color);
    border-radius: 8px;
    font-size: 13px;
    color: var(--text-secondary);
    cursor: pointer;
    text-align: center;
    background: none;
    font-family: inherit;
    width: calc(100% - 24px);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: all 0.15s;
    flex-shrink: 0;
}
.drawer-add-btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
</style>

<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>

<div class="admin-main">
    <?php $headerTitle = 'Kalendár (Rezervácie)'; $headerIcon = 'calendar_month'; require_once 'includes/dashboard-topbar.php'; ?>

    <div class="admin-content">
        <!-- Today Stats Panel -->
        <div style="padding: 16px 24px 0 24px;">
            <div class="today-stats-panel">
                <div class="today-stat-card">
                    <div class="today-stat-icon"><span class="material-symbols-outlined">calendar_today</span></div>
                    <div>
                        <div class="today-stat-label">Rezervácie dnes</div>
                        <div class="today-stat-value" id="stat-bookings-today">—</div>
                    </div>
                </div>
                <div class="today-stat-card">
                    <div class="today-stat-icon"><span class="material-symbols-outlined">schedule</span></div>
                    <div>
                        <div class="today-stat-label">Najbližší termín</div>
                        <div class="today-stat-value" id="stat-next-time">—</div>
                    </div>
                </div>
                <div class="today-stat-card">
                    <div class="today-stat-icon"><span class="material-symbols-outlined">payments</span></div>
                    <div>
                        <div class="today-stat-label">Tržba dnes</div>
                        <div class="today-stat-value" id="stat-revenue-today">—</div>
                    </div>
                </div>
                <div class="today-stat-card">
                    <div class="today-stat-icon"><span class="material-symbols-outlined">star</span></div>
                    <div>
                        <div class="today-stat-label">Nové hodnotenia</div>
                        <div class="today-stat-value" id="stat-new-reviews">—</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div id="sec-bookings" class="section active" style="width:100%;padding:12px 24px 20px 24px;box-sizing:border-box;">
            <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;box-shadow:var(--shadow-sm);width:100%;box-sizing:border-box;display:flex;overflow:hidden;">
                <!-- Calendar grid -->
                <div style="flex:1;min-width:0;padding:16px;">
                    <div id="calendar" style="width:100%;"></div>
                </div>
                <!-- Day detail drawer -->
                <div id="day-drawer">
                    <div class="drawer-header">
                        <div style="flex:1;">
                            <div class="drawer-day-big" id="drawer-day-num">—</div>
                            <div class="drawer-day-label" id="drawer-day-label">Vyberte deň</div>
                        </div>
                        <button onclick="closeDayDrawer()" style="background:none;border:none;cursor:pointer;padding:4px;color:var(--text-secondary);display:flex;align-items:center;flex-shrink:0;margin-top:2px;" title="Zavrieť">
                            <span class="material-symbols-outlined" style="font-size:18px;">close</span>
                        </button>
                    </div>
                    <!-- Occupancy bar -->
                    <div class="drawer-occ-wrap" id="drawer-occ-wrap" style="display:none;">
                        <div class="drawer-occ-label">
                            <span>Obsadenosť</span>
                            <span id="drawer-occ-text">0 h / 0 h</span>
                        </div>
                        <div class="drawer-occ-bg"><div class="drawer-occ-fill" id="drawer-occ-fill" style="width:0%"></div></div>
                    </div>
                    <!-- Timeline -->
                    <div class="drawer-timeline" id="drawer-timeline">
                        <div class="drawer-empty">Kliknite na deň v kalendári</div>
                    </div>
                    <!-- Last Minute banner (shown conditionally) -->
                    <div class="drawer-lm-banner" id="drawer-lm-banner" style="display:none;">
                        <div class="drawer-lm-title">
                            <span class="material-symbols-outlined" style="font-size:16px;color:#10b981;">bolt</span>
                            Last Minute dostupný
                        </div>
                        <div class="drawer-lm-desc" id="drawer-lm-desc">Máte voľné hodiny. Zverejnite Last Minute ponuku.</div>
                        <button class="drawer-lm-btn" onclick="document.location='dashboard-last-minute.php?date='+(g_selected_date||'')">Vytvoriť Last Minute</button>
                    </div>
                    <!-- Add button -->
                    <button type="button" class="drawer-add-btn" id="drawer-add-btn" onclick="openNewBookingFromDrawer()" style="display:none;">
                        <span class="material-symbols-outlined" style="font-size:15px;">add</span> Pridať rezerváciu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAB: Add booking -->
<button class="fab" onclick="openNewBookingModal()" title="Pridať manuálnu rezerváciu">
    <span class="material-symbols-outlined">add</span>
</button>

<!-- Modal: New Booking -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nová rezervácia</h2>
            <span class="material-symbols-outlined" style="cursor:pointer;" onclick="document.getElementById('bookingModal').classList.remove('active')">close</span>
        </div>
        <form id="manualBookingForm" onsubmit="addManualBooking(event)">
            <div class="form-group">
                <label>Služba</label>
                <select id="mb-service" required><option value="">Vyberte službu...</option></select>
            </div>
            <div class="form-group">
                <label>Meno zákazníka</label>
                <input type="text" id="mb-name" required placeholder="Napr. Ján Novák">
            </div>
            <div class="form-group">
                <label>Telefón</label>
                <input type="text" id="mb-phone" placeholder="Napr. +421 900 123 456">
            </div>
            <div class="form-group">
                <label>E-mail (voliteľné)</label>
                <input type="email" id="mb-email" placeholder="Napr. jan@novak.sk">
            </div>
            <div style="display:flex;gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Dátum</label>
                    <input type="date" id="mb-date" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Čas (začiatok)</label>
                    <input type="time" id="mb-time" required>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;margin-top:10px;">Uložiť rezerváciu</button>
        </form>
    </div>
</div>

<!-- Modal: Event Detail -->
<div id="eventModal" class="modal">
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h2>Detail rezervácie</h2>
            <button class="close-modal" onclick="closeEventModal()">&times;</button>
        </div>
        <div class="modal-body">
            <h3 id="ev-modal-title" style="margin-top:0;margin-bottom:15px;color:var(--text-primary);font-size:18px;white-space:pre-wrap;"></h3>
            <div id="ev-modal-customer"></div>
            <div class="form-group">
                <label>Stav rezervácie</label>
                <select id="ev-modal-status" class="form-control" onchange="if(this.value==='completed') document.getElementById('ev-modal-rating-section').style.display='block'; else document.getElementById('ev-modal-rating-section').style.display='none';">
                    <option value="pending">Čaká na potvrdenie</option>
                    <option value="pending_deposit">Čaká na úhradu zálohy</option>
                    <option value="confirmed">Potvrdená</option>
                    <option value="completed">Dokončená (Vybavená)</option>
                    <option value="cancelled">Zrušená</option>
                </select>
            </div>
            <div id="ev-modal-rating-section" style="display:none;background:var(--bg-color);padding:15px;border-radius:8px;border:1px solid var(--border-color);margin-top:20px;">
                <h4 style="margin-top:0;margin-bottom:10px;">Ohodnotiť zákazníka</h4>
                <p style="font-size:12px;color:var(--text-secondary);margin-bottom:10px;">Dostupné pre platené balíky. Pomáhate tak ostatným prevádzkam.</p>
                <select id="ev-rating-stars" class="form-control" style="margin-bottom:10px;">
                    <option value="">-- Vyberte hodnotenie --</option>
                    <option value="5">5 Hviezdičiek (Skvelý prístup)</option>
                    <option value="4">4 Hviezdičky (Dobrý prístup)</option>
                    <option value="3">3 Hviezdičky (Priemerný prístup)</option>
                    <option value="2">2 Hviezdičky (Meškanie/Problémy)</option>
                    <option value="1">1 Hviezdička (Neprišiel/Veľmi zlé)</option>
                </select>
                <textarea id="ev-rating-comment" class="form-control" rows="2" placeholder="Krátky komentár (voliteľné)"></textarea>
                <button class="btn btn-primary" style="margin-top:10px;width:100%;" onclick="submitCustomerRating()">Uložiť hodnotenie</button>
            </div>
            <button class="btn btn-primary" style="margin-top:20px;width:100%;" onclick="saveEventStatus()">Uložiť zmeny</button>
        </div>
    </div>
</div>

<!-- Modal: Unavailability -->
<div id="unavailabilityModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:2000;justify-content:center;align-items:center;">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header">
            <h2>Pridať nedostupnosť</h2>
            <button class="close-modal" onclick="closeUnavailabilityModal()">&times;</button>
        </div>
        <form id="unavailabilityForm" onsubmit="addUnavailability(event)">
            <div class="form-group">
                <label>Typ</label>
                <select id="unavail_type" class="form-control">
                    <option value="break">Prestávka</option>
                    <option value="holiday">Dovolenka</option>
                    <option value="other">Iné</option>
                </select>
            </div>
            <div class="form-group">
                <label>Od (dátum a čas)</label>
                <input type="datetime-local" id="unavail_start" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Do (dátum a čas)</label>
                <input type="datetime-local" id="unavail_end" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Poznámka</label>
                <input type="text" id="unavail_note" class="form-control" placeholder="Voliteľná poznámka...">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Uložiť nedostupnosť</button>
        </form>
    </div>
</div>

<div id="toast"></div>

<script>
// Utility funkcie (escapeHtml, showAppToast, notifikácie) sú v /assets/js/dashboard-common.js

// ---- Calendar ----
let calendar;
let g_all_events = [];   // all booking events stored globally for drawer
let g_selected_date = null;
let g_all_bookings = []; // raw booking data from API

const colorPalette = [
    { bg: '#dbeafe', text: '#1e3a8a' },
    { bg: '#fef3c7', text: '#92400e' },
    { bg: '#fce7f3', text: '#9d174d' },
    { bg: '#d1fae5', text: '#065f46' },
    { bg: '#e0e7ff', text: '#3730a3' },
    { bg: '#ffedd5', text: '#c2410c' }
];

let window_g_services = [];

async function loadStats() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_dashboard_stats');
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        if (data.success) {
            // Bookings limit tracker (if elements exist)
            if (data.limit < 999999) {
                const lt = document.getElementById('limit-tracker');
                if (lt) lt.style.display = 'block';
                const ltxt = document.getElementById('limit-text');
                if (ltxt) ltxt.innerText = data.used + ' / ' + data.limit;
            }
            // Today stats
            if (data.today_bookings !== undefined) {
                const el = document.getElementById('stat-bookings-today');
                if (el) el.innerText = data.today_bookings;
            }
            if (data.next_time !== undefined) {
                const el = document.getElementById('stat-next-time');
                if (el) el.innerText = data.next_time || '—';
            }
            if (data.today_revenue !== undefined) {
                const el = document.getElementById('stat-revenue-today');
                if (el) el.innerText = (parseFloat(data.today_revenue) || 0).toFixed(2) + ' €';
            }
            if (data.new_reviews !== undefined) {
                const el = document.getElementById('stat-new-reviews');
                if (el) el.innerText = data.new_reviews;
            }
        }
    } catch(err) { console.error('loadStats error', err); }
}

async function loadServices() {
    const fd = new FormData(); fd.append('action', 'get_services');
    let res = await fetch('api/business.php', { method: 'POST', body: fd });
    let data = await res.json();
    if (data.success) {
        window_g_services = data.data || [];
        let selectHtml = '<option value="">Vyberte službu...</option>';
        data.data.forEach(s => {
            selectHtml += `<option value="${s.id}">${s.name} (${parseFloat(s.price).toFixed(2)} €)</option>`;
        });
        const mbService = document.getElementById('mb-service');
        if (mbService) mbService.innerHTML = selectHtml;
    }
}

async function loadBookings() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    try {
        const fd = new FormData(); fd.append('action', 'get_bookings');
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let textRes = await res.text();
        let data = { success: false, data: [] };
        try { data = JSON.parse(textRes); } catch(jsonErr) { console.warn('JSON parse error:', textRes.substring(0, 100)); }

        let events = [];
        if (data.success && Array.isArray(data.data)) {
            g_all_bookings = data.data;
            data.data.forEach(b => {
                let dur = parseInt(b.service_duration) || 30;
                let start = b.booking_date + 'T' + (b.start_time || '09:00:00');
                let d = new Date(start);
                if (isNaN(d.getTime())) return;
                d.setMinutes(d.getMinutes() + dur);

                let colorIndex = (parseInt(b.service_id) || 0) % colorPalette.length;
                let colorPair = colorPalette[colorIndex] || { bg: '#e5e7eb', text: '#374151' };
                let bgColor = colorPair.bg;
                let textColor = colorPair.text;

                if (b.status === 'cancelled') { bgColor = '#f3f4f6'; textColor = '#9ca3af'; }
                if (b.status === 'unavailable') { bgColor = 'rgba(231,76,60,0.1)'; textColor = '#e74c3c'; }

                // Farba zamestnanca (ak je nastavená) sa použije ako okraj udalosti — farba pozadia zostáva podľa služby
                const empBorder = b.employee_color || bgColor;

                events.push({
                    id: b.id,
                    title: `${b.customer_name || 'Rezervácia'}\n${b.service_name || 'Služba'}`,
                    start: start,
                    end: d.toISOString(),
                    backgroundColor: bgColor,
                    borderColor: empBorder,
                    textColor: textColor,
                    extendedProps: {
                        booking_id: b.id,
                        status: b.status,
                        is_over_limit: b.is_over_limit,
                        phone: b.customer_phone,
                        customer_email: b.customer_email,
                        customer_name: b.customer_name,
                        customer_avatar: b.customer_avatar,
                        customer_verified: b.customer_verified,
                        customer_rating: b.client_rating,
                        start_time: b.start_time || '00:00',
                        service_name: b.service_name || '',
                        employee_name: b.employee_name || '',
                        employee_color: b.employee_color || ''
                    }
                });
                g_all_events = events;
            });
        }

        if (calendar) { try { calendar.destroy(); } catch(e) {} }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'sk',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            height: 'auto',
            allDaySlot: false,
            nowIndicator: true,
            scrollTime: new Date().toTimeString().slice(0, 8),
            events: events,
            eventContent: function(arg) {
                const props = arg.event.extendedProps;
                let ratingBadge = '';
                if (props.client_rating && props.client_rating > 0) {
                    ratingBadge = `<span style="color:#f59e0b;font-size:10px;">★${props.client_rating}</span>`;
                }
                const titleLines = arg.event.title.split('\n');
                return {
                    html: `<div style="padding:2px 4px;font-size:12px;font-weight:600;line-height:1.3;">
                        <div>${escapeHtml(titleLines[0] || '')} ${ratingBadge}</div>
                        ${titleLines[1] ? `<div style="font-size:11px;opacity:0.8;">${escapeHtml(titleLines[1])}</div>` : ''}
                    </div>`
                };
            },
            dateClick: function(info) {
                openDayDrawer(info.dateStr, info.dayEl);
            },
            eventClick: function(info) {
                info.jsEvent.stopPropagation();
                openDayDrawer(info.event.startStr.slice(0, 10), null);
                setTimeout(() => openEventModal(info.event), 120);
            }
        });
        calendar.render();

        // Load Slovak & Czech holidays in background
        (async () => {
            try {
                let year = new Date().getFullYear();
                let [resSK, resCZ] = await Promise.all([
                    fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/SK`),
                    fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/CZ`)
                ]);
                let dataSK = await resSK.json();
                let dataCZ = await resCZ.json();
                if (Array.isArray(dataSK)) dataSK.forEach(h => { if (calendar) calendar.addEvent({ title: 'SK: ' + h.localName, start: h.date, allDay: true, backgroundColor: '#fee2e2', borderColor: '#fca5a5', textColor: '#dc2626' }); });
                if (Array.isArray(dataCZ)) dataCZ.forEach(h => { if (calendar) calendar.addEvent({ title: 'CZ: ' + h.localName, start: h.date, allDay: true, backgroundColor: '#fee2e2', borderColor: '#fca5a5', textColor: '#dc2626' }); });
            } catch(e) {}
        })();

    } catch(err) { console.error('Chyba kalendára:', err); }
}

function openUnavailabilityModal() {
    document.getElementById('unavailabilityModal').style.display = 'flex';
}
function closeUnavailabilityModal() {
    document.getElementById('unavailabilityModal').style.display = 'none';
}

async function addUnavailability(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'add_unavailability');
    fd.append('type', document.getElementById('unavail_type').value);
    fd.append('start', document.getElementById('unavail_start').value);
    fd.append('end', document.getElementById('unavail_end').value);
    fd.append('note', document.getElementById('unavail_note').value);
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        if (data.success) {
            closeUnavailabilityModal();
            loadBookings();
            showAppToast('Nedostupnosť bola úspešne pridaná.', 'success');
        } else {
            showAppToast('Chyba: ' + (data.error || data.message), 'error');
        }
    } catch(e) {
        console.error(e);
        showAppToast('Chyba pri ukladaní nedostupnosti.', 'error');
    }
}

async function openNewBookingModal(dateVal = null, timeVal = null) {
    if (!window_g_services || window_g_services.length === 0) {
        await loadServices();
    }
    if (!window_g_services || window_g_services.length === 0) {
        showAppToast('Nemáte vytvorené žiadne služby. Najprv si pridajte služby v cenníku.', 'error');
        return;
    }
    if (dateVal) { const di = document.getElementById('mb-date'); if (di) di.value = dateVal; }
    if (timeVal) { const ti = document.getElementById('mb-time'); if (ti) ti.value = timeVal; }
    const modal = document.getElementById('bookingModal');
    if (modal) modal.classList.add('active');
}

async function addManualBooking(e) {
    e.preventDefault();
    const sVal = document.getElementById('mb-service').value;
    if (!sVal) { showAppToast('Prosím vyberte službu zo zoznamu.', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'add_manual_booking');
    fd.append('service_id', sVal);
    fd.append('manual_name', document.getElementById('mb-name').value);
    fd.append('manual_phone', document.getElementById('mb-phone').value);
    fd.append('manual_email', document.getElementById('mb-email').value);
    fd.append('booking_date', document.getElementById('mb-date').value);
    fd.append('start_time', document.getElementById('mb-time').value);
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        if (data.success) {
            document.getElementById('bookingModal').classList.remove('active');
            document.getElementById('manualBookingForm').reset();
            loadBookings();
            loadStats();
            showAppToast('Rezervácia bola úspešne pridaná!', 'success');
        } else {
            showAppToast(data.message || 'Chyba pri pridávaní rezervácie.', 'error');
        }
    } catch(err) { showAppToast('Chyba pri ukladaní rezervácie.', 'error'); }
}

async function updateBooking(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_booking');
    fd.append('id', id);
    fd.append('status', status);
    try {
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.freed_slot) offerLastMinuteForFreedSlot(data.freed_slot);
    } catch (e) {}
    loadBookings();
    loadStats();
}

// ---- Automatická ponuka Last Minute pri uvoľnení termínu (zrušenie rezervácie) ----
async function offerLastMinuteForFreedSlot(slot) {
    const discountPct = 20;
    const discPrice = Math.round(slot.price * (1 - discountPct / 100) * 100) / 100;
    const dateFmt = new Date(slot.slot_date + 'T00:00:00').toLocaleDateString('sk-SK');
    const ok = await confirmModal(
        `Miesto sa uvoľnilo: ${slot.service_name} — ${dateFmt} o ${slot.slot_time}.\n\nVytvoriť Last Minute ponuku so zľavou ${discountPct}% (${discPrice.toFixed(2)} € namiesto ${slot.price.toFixed(2)} €) jedným klikom?`,
        { okText: `Vytvoriť (-${discountPct}%)`, cancelText: 'Nie, ďakujem' }
    );
    if (!ok) return;

    const fd = new FormData();
    fd.append('action', 'add_last_minute');
    fd.append('service_id', slot.service_id);
    fd.append('slot_date', slot.slot_date);
    fd.append('slot_time', slot.slot_time);
    fd.append('original_price', slot.price);
    fd.append('discounted_price', discPrice);
    fd.append('note', 'Automaticky ponúknuté po zrušení rezervácie');
    try {
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) showAppToast('Last Minute ponuka bola vytvorená! Zákazníci ju uvidia na vašom profile.', 'success');
        else showAppToast(data.message || 'Chyba pri vytváraní Last Minute ponuky.', 'error');
    } catch (e) {
        showAppToast('Chyba siete pri vytváraní Last Minute ponuky.', 'error');
    }
}

let currentEventBookingId = null;

function openEventModal(event) {
    const props = event.extendedProps;
    if (!props.booking_id) return;

    currentEventBookingId = props.booking_id;
    document.getElementById('ev-modal-title').innerText = event.title;
    document.getElementById('ev-modal-status').value = props.status;

    let custHtml = '';
    if (props.is_over_limit) {
        custHtml = `<div style="background:rgba(231,76,60,0.1);border:1px solid #e74c3c;padding:15px;border-radius:8px;margin-bottom:15px;text-align:center;">
            <span class="material-symbols-outlined" style="color:#e74c3c;font-size:40px;margin-bottom:10px;">lock</span>
            <h4 style="margin:0 0 10px 0;color:#e74c3c;">Limit rezervácií prekročený</h4>
            <p style="font-size:13px;color:var(--text-primary);margin:0;">Zákazník úspešne vytvoril rezerváciu, no prekročili ste mesačný limit. Zvýšte si balík pre zobrazenie údajov.</p>
        </div>`;
        document.getElementById('ev-modal-status').disabled = true;
        document.getElementById('ev-modal-rating-section').style.display = 'none';
    } else {
        document.getElementById('ev-modal-status').disabled = false;
        if (props.customer_name) {
            let avatar = props.customer_avatar ? props.customer_avatar : 'assets/img/default-avatar.png';
            let verified = props.customer_verified == 1 ? '<span class="material-symbols-outlined" style="color:#f5b041;font-size:16px;" title="Overený zákazník">verified</span>' : '';
            let rating = props.customer_rating ? `<span style="color:#f5b041;">★</span> ${props.customer_rating}/5` : 'Zatiaľ bez hodnotenia';
            custHtml = `<div style="display:flex;align-items:center;gap:15px;background:var(--bg-color);padding:10px;border-radius:8px;margin-bottom:15px;">
                <img src="${avatar}" style="width:50px;height:50px;border-radius:50%;object-fit:cover;">
                <div>
                    <strong>${escapeHtml(props.customer_name)} ${verified}</strong><br>
                    <span style="font-size:12px;color:var(--text-secondary);">${escapeHtml(props.phone || props.customer_email || 'Bez kontaktu')}</span><br>
                    <span style="font-size:13px;font-weight:500;">${rating}</span>
                </div>
            </div>`;
        }
        if (props.status === 'completed') {
            document.getElementById('ev-modal-rating-section').style.display = 'block';
        } else {
            document.getElementById('ev-modal-rating-section').style.display = 'none';
        }
    }
    document.getElementById('ev-modal-customer').innerHTML = custHtml;
    document.getElementById('eventModal').classList.add('active');
}

function closeEventModal() {
    document.getElementById('eventModal').classList.remove('active');
}

async function saveEventStatus() {
    let newStatus = document.getElementById('ev-modal-status').value;
    if (currentEventBookingId) {
        await updateBooking(currentEventBookingId, newStatus);
        closeEventModal();
    }
}

async function submitCustomerRating() {
    let rating = document.getElementById('ev-rating-stars').value;
    let comment = document.getElementById('ev-rating-comment').value;
    if (!rating) return showAppToast('Vyberte počet hviezdičiek.', 'error');
    const fd = new FormData();
    fd.append('action', 'rate_customer');
    fd.append('booking_id', currentEventBookingId);
    fd.append('rating', rating);
    fd.append('comment', comment);
    let res = await fetch('api/business.php', { method: 'POST', body: fd });
    let data = await res.json();
    if (data.success) { showAppToast('Hodnotenie bolo úspešne uložené!', 'success'); closeEventModal(); }
    else { showAppToast(data.message || 'Chyba pri ukladaní hodnotenia.', 'error'); }
}

// ---- Day Drawer ----
const SK_DAYS   = ['Nedeľa','Pondelok','Utorok','Streda','Štvrtok','Piatok','Sobota'];
const SK_MONTHS = ['januára','februára','marca','apríla','mája','júna','júla','augusta','septembra','októbra','novembra','decembra'];
const OPEN_HOUR  = 8;   // otváracie hodiny (default, ideálne načítať z profilu)
const CLOSE_HOUR = 18;  // zatváracie hodiny

function openDayDrawer(dateStr, dayEl) {
    // V týždennom/dennom zobrazení posiela FullCalendar dateStr aj s časom
    // (napr. "2026-09-04T14:00:00+02:00") — vezmeme si len samotný dátum,
    // inak by sme pridaním "T00:00:00" vytvorili neplatný reťazec (NaN dátum).
    dateStr = dateStr.slice(0, 10);
    g_selected_date = dateStr;
    const d = new Date(dateStr + 'T00:00:00');
    const drawer   = document.getElementById('day-drawer');
    const timeline = document.getElementById('drawer-timeline');
    const addBtn   = document.getElementById('drawer-add-btn');
    const occWrap  = document.getElementById('drawer-occ-wrap');
    const lmBanner = document.getElementById('drawer-lm-banner');

    document.getElementById('drawer-day-num').innerText = d.getDate();
    document.getElementById('drawer-day-label').innerText =
        SK_DAYS[d.getDay()] + ', ' + d.getDate() + '. ' + SK_MONTHS[d.getMonth()] + ' ' + d.getFullYear();

    // Highlight selected day
    document.querySelectorAll('.fc-day-selected').forEach(el => el.classList.remove('fc-day-selected'));
    if (dayEl) dayEl.classList.add('fc-day-selected');

    // Bookings for this day (from raw API data)
    const dayBookings = g_all_bookings.filter(b => b.booking_date === dateStr);
    const activeBookings = dayBookings.filter(b => b.status !== 'cancelled' && b.status !== 'unavailable');

    // --- Occupancy bar ---
    const totalMins  = (CLOSE_HOUR - OPEN_HOUR) * 60;
    let bookedMins   = 0;
    activeBookings.forEach(b => { bookedMins += parseInt(b.service_duration) || 60; });
    const bookedH    = (bookedMins / 60).toFixed(1).replace('.0','');
    const totalH     = (CLOSE_HOUR - OPEN_HOUR);
    const occPct     = Math.min(100, Math.round(bookedMins / totalMins * 100));
    const freeMins   = Math.max(0, totalMins - bookedMins);
    const freeH      = (freeMins / 60).toFixed(1).replace('.0','');

    occWrap.style.display = 'block';
    document.getElementById('drawer-occ-text').innerText = bookedH + ' h / ' + totalH + ' h';
    document.getElementById('drawer-occ-fill').style.width = occPct + '%';

    // --- Časové porovnanie ---
    const now      = new Date();
    const today    = new Date(); today.setHours(0,0,0,0);
    const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate() + 1);
    const dayTime  = d.getTime();
    const isToday    = dayTime === today.getTime();
    const isTomorrow = dayTime === tomorrow.getTime();
    const isPast     = dayTime < today.getTime();                // minulý dátum
    const nowH       = now.getHours() + now.getMinutes() / 60;  // aktuálna hodina s minútami

    // --- Timeline ---
    let html = '';
    let futureFreeMins = 0; // voľné minúty LEN v budúcich hodinách (pre LM banner dnes)
    for (let h = OPEN_HOUR; h < CLOSE_HOUR; h++) {
        const hStr = h.toString().padStart(2, '0') + ':00';
        const booksThisHour = dayBookings.filter(b => {
            const bh = parseInt((b.start_time || '00:00').split(':')[0]);
            return bh === h;
        });

        // Je táto hodina v minulosti? (dnes + hodina prešla, alebo celý deň v minulosti)
        const hourIsPast = isPast || (isToday && h < Math.ceil(nowH));

        html += `<div class="tl-row"${hourIsPast ? ' style="opacity:0.38;"' : ''}>
            <div class="tl-hour">${hStr}</div>
            <div class="tl-line-wrap"></div>
            <div class="tl-content">`;

        if (booksThisHour.length === 0) {
            if (hourIsPast) {
                html += `<div class="tl-free-slot" style="color:var(--text-secondary);font-style:italic;"><span>—</span></div>`;
            } else {
                html += `<div class="tl-free-slot"><span>voľné</span></div>`;
                futureFreeMins += 60;
            }
        } else {
            booksThisHour.forEach(b => {
                const dur   = parseInt(b.service_duration) || 60;
                const endH  = new Date('1970-01-01T' + (b.start_time || '00:00:00'));
                endH.setMinutes(endH.getMinutes() + dur);
                const endStr = endH.toTimeString().slice(0,5);
                const cardClass = b.status === 'confirmed' || b.status === 'completed' ? 'confirmed'
                    : (b.status === 'cancelled' ? 'cancelled' : '');
                html += `<div class="tl-booking-card ${cardClass}"
                    onclick="if(calendar){var e=calendar.getEventById('${b.id}');if(e)openEventModal(e);}">
                    <div class="tl-bk-name">${escapeHtml(b.customer_name || 'Rezervácia')}</div>
                    <div class="tl-bk-info">${(b.start_time||'').slice(0,5)}–${endStr} · ${escapeHtml(b.service_name||'')}</div>
                </div>`;
            });
        }
        html += `</div></div>`;
    }
    timeline.innerHTML = html || '<div class="drawer-empty">Žiadne hodiny</div>';

    // --- Last Minute banner ---
    // Dnes: počítame len budúce voľné hodiny; zajtra: celkové voľné hodiny
    const lmFreeMins = isToday ? futureFreeMins : freeMins;
    const lmFreeH    = (lmFreeMins / 60).toFixed(1).replace('.0','');
    const hasFreeSlots = lmFreeMins >= 60; // aspoň 1 budúca voľná hodina

    if ((isToday || isTomorrow) && hasFreeSlots && occPct < 90) {
        const whenLabel = isToday ? 'dnes' : 'zajtra';
        document.getElementById('drawer-lm-desc').innerText =
            'Máte ' + lmFreeH + ' h voľných hodín ' + whenLabel + '. Zverejnite Last Minute ponuku a zaplňte ich.';
        lmBanner.style.display = 'block';
    } else {
        lmBanner.style.display = 'none';
    }

    // Pridať rezerváciu — zakázať pre minulé dni
    if (addBtn) addBtn.style.display = isPast ? 'none' : 'flex';
    drawer.classList.add('open');
}

function closeDayDrawer() {
    document.getElementById('day-drawer').classList.remove('open');
    document.querySelectorAll('.fc-day-selected').forEach(el => el.classList.remove('fc-day-selected'));
    g_selected_date = null;
}

function openNewBookingFromDrawer() {
    openNewBookingModal(g_selected_date, null);
}

// ---- Init ----
document.addEventListener('DOMContentLoaded', function() {
    loadBookings();
    loadStats();
    loadServices();
    // Notifikácie spravuje dashboard-common.js (initDashboardPage)
    <?php if (!empty($_SESSION['promo_message'])): ?>
    showAppToast(<?php echo json_encode($_SESSION['promo_message']); ?>, 'success', 6000);
    <?php unset($_SESSION['promo_message']); ?>
    <?php endif; ?>
});
</script>
</body>
</html>
