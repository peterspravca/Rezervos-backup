/**
 * VoľnéKreslo Dashboard - Shared JS Helpers
 * Version 3.0
 */

'use strict';

/* =============================================
   CONFIRM MODAL (nahrádza natívne window.confirm() — žiadne systémové okná appky)
   ============================================= */

function confirmModal(message, options = {}) {
    return new Promise((resolve) => {
        let overlay = document.getElementById('_confirm_modal_overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = '_confirm_modal_overlay';
            overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:99998;align-items:center;justify-content:center;backdrop-filter:blur(2px);';
            overlay.innerHTML = `
                <div style="background:var(--card-bg,#fff);border:1px solid var(--border-color,#e5e7eb);border-radius:16px;max-width:420px;width:90%;padding:24px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
                    <p id="_confirm_modal_msg" style="margin:0 0 20px 0;font-size:14px;color:var(--text-primary,#111);line-height:1.55;white-space:pre-line;"></p>
                    <div style="display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" id="_confirm_modal_cancel" class="btn-secondary" style="padding:9px 18px;border-radius:10px;">Zrušiť</button>
                        <button type="button" id="_confirm_modal_ok" class="btn-primary" style="padding:9px 18px;border-radius:10px;">Potvrdiť</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
        }
        overlay.querySelector('#_confirm_modal_msg').textContent = message;
        const okBtn = overlay.querySelector('#_confirm_modal_ok');
        const cancelBtn = overlay.querySelector('#_confirm_modal_cancel');
        okBtn.textContent = options.okText || 'Potvrdiť';
        cancelBtn.textContent = options.cancelText || 'Zrušiť';
        overlay.style.display = 'flex';
        const cleanup = (result) => {
            overlay.style.display = 'none';
            okBtn.onclick = null; cancelBtn.onclick = null; overlay.onclick = null;
            resolve(result);
        };
        okBtn.onclick = () => cleanup(true);
        cancelBtn.onclick = () => cleanup(false);
        overlay.onclick = (e) => { if (e.target === overlay) cleanup(false); };
    });
}

/* =============================================
   INFO MODAL (jednoduché upozornenie s jedným OK tlačidlom — žiadny natívny alert())
   ============================================= */

function infoModal(title, message, okText) {
    return new Promise((resolve) => {
        let overlay = document.getElementById('_info_modal_overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = '_info_modal_overlay';
            overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:99998;align-items:center;justify-content:center;backdrop-filter:blur(2px);';
            overlay.innerHTML = `
                <div style="background:var(--card-bg,#fff);border:1px solid var(--border-color,#e5e7eb);border-radius:16px;max-width:420px;width:90%;padding:24px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
                    <h3 id="_info_modal_title" style="margin:0 0 10px 0;font-size:16px;font-weight:700;color:var(--text-primary,#111);"></h3>
                    <p id="_info_modal_msg" style="margin:0 0 20px 0;font-size:14px;color:var(--text-secondary,#555);line-height:1.55;white-space:pre-line;"></p>
                    <div style="display:flex;justify-content:flex-end;">
                        <button type="button" id="_info_modal_ok" class="btn-primary" style="padding:9px 18px;border-radius:10px;">OK</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
        }
        overlay.querySelector('#_info_modal_title').textContent = title || '';
        overlay.querySelector('#_info_modal_title').style.display = title ? '' : 'none';
        overlay.querySelector('#_info_modal_msg').textContent = message;
        const okBtn = overlay.querySelector('#_info_modal_ok');
        okBtn.textContent = okText || 'Rozumiem';
        overlay.style.display = 'flex';
        const cleanup = () => {
            overlay.style.display = 'none';
            okBtn.onclick = null; overlay.onclick = null;
            resolve(true);
        };
        okBtn.onclick = cleanup;
        overlay.onclick = (e) => { if (e.target === overlay) cleanup(); };
    });
}

/* =============================================
   TOAST NOTIFICATIONS
   ============================================= */

function showAppToast(message, type = 'success', duration = 3000) {
    let container = document.getElementById('app-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'app-toast-container';
        container.style.cssText = 'position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
        document.body.appendChild(container);
    }
    const colorMap = { success:'#10b981', error:'#ef4444', info:'#3b82f6', warning:'#f59e0b' };
    const iconMap  = { success:'check_circle', error:'error', info:'info', warning:'warning' };
    const bg   = colorMap[type]  || colorMap.success;
    const icon = iconMap[type]   || iconMap.success;
    const toast = document.createElement('div');
    toast.style.cssText = `background:${bg};color:#ffffff;padding:12px 20px;border-radius:12px;
        box-shadow:0 10px 25px rgba(0,0,0,0.25);font-size:13.5px;font-weight:600;
        display:flex;align-items:center;gap:10px;pointer-events:auto;
        opacity:0;transform:translateY(-15px);transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
        font-family:'Outfit',sans-serif;cursor:pointer;`;
    toast.innerHTML = `<span class="material-symbols-outlined" style="font-size:20px;">${icon}</span><span>${message}</span>`;
    toast.addEventListener('click', () => dismiss());
    container.appendChild(toast);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }));
    const dismiss = () => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-15px)';
        setTimeout(() => { if (toast.parentNode) toast.remove(); }, 320);
    };
    setTimeout(dismiss, duration);
}

/* =============================================
   THEME TOGGLE
   ============================================= */

function toggleTheme(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (typeof window.toggleAppTheme === 'function') { window.toggleAppTheme(e); return; }
    const isDark = document.body.classList.toggle('dark-mode');
    document.documentElement.classList.toggle('dark-mode', isDark);
    try {
        localStorage.setItem('vk_theme', isDark ? 'dark' : 'light');
        localStorage.setItem('theme',    isDark ? 'dark' : 'light');
    } catch (_) {}
    _updateThemeIcon(isDark);
}

function _updateThemeIcon(isDark) {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;
    const icon = btn.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
    btn.setAttribute('title', isDark ? 'Prepnúť na svetlý režim' : 'Prepnúť na tmavý režim');
}

/* =============================================
   NOTIFICATIONS
   ============================================= */

let g_notifications        = [];
let g_notifSoundEnabled    = localStorage.getItem('vk_notif_sound') !== 'disabled';
let g_prevUnreadCount      = 0;

/**
 * Load notifications from API and update badge + dropdown list.
 * @param {boolean} triggerSound  Play sound if unread count grew
 */
async function loadNotifications(triggerSound = false) {
    try {
        const r   = await fetch('api/notifications.php?action=get_notifications');
        const res = await r.json();
        if (!res.success) return;

        g_notifications = res.notifications || [];
        const unread    = res.unread_count  || 0;

        // Badge
        const badge = document.getElementById('notif-badge');
        if (badge) {
            if (unread > 0) {
                badge.style.display = 'inline-flex';
                badge.innerText     = unread > 99 ? '99+' : String(unread);
            } else {
                badge.style.display = 'none';
                badge.innerText     = '';
            }
        }

        // Bell animation
        const bellIcon = document.querySelector('#notif-btn .material-symbols-outlined');
        if (bellIcon) {
            if (unread > 0) {
                bellIcon.classList.add('bell-blinking');
            } else {
                bellIcon.classList.remove('bell-blinking');
            }
        }

        // Sound only when count actually grew
        if (triggerSound && unread > g_prevUnreadCount) {
            _playNotifSound();
        }
        g_prevUnreadCount = unread;

        // Render only when dropdown is visible (avoid unnecessary DOM work)
        const dropdown = document.getElementById('notif-dropdown');
        if (dropdown && dropdown.style.display === 'block') {
            _renderNotificationList();
        }
    } catch (err) {
        console.error('loadNotifications error:', err);
    }
}

function _notifIconColor(type) {
    const map = {
        warning:        '#f59e0b',
        booking_pending:'#ef4444',
        booking_new:    '#10b981',
        low_occupancy:  '#3b82f6',
        lm_expiring:    '#8b5cf6',
        review:         '#f59e0b',
        nameday:        '#ec4899',
        occasion_remind:'#ec4899',
    };
    return map[type] || 'var(--primary-color)';
}

function _renderNotificationList() {
    const list = document.getElementById('notif-list');
    if (!list) return;

    if (!g_notifications || g_notifications.length === 0) {
        list.innerHTML = `
            <div style="text-align:center;padding:30px 15px;color:var(--text-secondary);font-size:13px;">
                <span class="material-symbols-outlined" style="font-size:32px;display:block;margin-bottom:8px;color:var(--text-secondary);">notifications_off</span>
                Žiadne nové upozornenia.
            </div>`;
        return;
    }

    list.innerHTML = g_notifications.map((n, idx) => {
        const iconColor  = _notifIconColor(n.type);
        const readStyle  = n.is_read ? 'opacity:0.6;' : '';
        const dotHtml    = n.is_read ? '' :
            '<span style="width:7px;height:7px;border-radius:50%;background:#ef4444;flex-shrink:0;display:inline-block;"></span>';
        const key        = escapeHtml(n.key || '');
        const isLast     = idx === g_notifications.length - 1;

        // Tlačidlo "prečítané" – zelená keď neprečítané, šedá keď prečítané
        const checkColor = n.is_read ? 'var(--text-secondary)' : '#10b981';
        const checkTitle = n.is_read ? 'Prečítané' : 'Označiť ako prečítané';

        return `
        <div style="display:flex;align-items:flex-start;gap:10px;padding:11px 16px;
                ${isLast ? '' : 'border-bottom:1px solid var(--border-color);'}${readStyle}
                background:${n.is_read ? 'transparent' : 'rgba(176,128,66,0.04)'};"
             id="notif-item-${key}">
            <!-- Typ-ikona -->
            <div style="flex-shrink:0;width:32px;height:32px;border-radius:8px;
                    background:${iconColor}20;display:flex;align-items:center;justify-content:center;margin-top:2px;">
                <span class="material-symbols-outlined" style="font-size:17px;color:${iconColor};">${escapeHtml(n.icon || 'notifications')}</span>
            </div>
            <!-- Text -->
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:2px;">
                    <span style="font-size:12.5px;font-weight:700;color:var(--text-primary);line-height:1.3;">${escapeHtml(n.title || '')}</span>
                    ${dotHtml}
                </div>
                <div style="font-size:11.5px;color:var(--text-secondary);line-height:1.4;word-break:break-word;">${escapeHtml(n.message || '')}</div>
                <div style="font-size:10.5px;color:var(--text-secondary);margin-top:3px;opacity:0.65;">${escapeHtml(n.time || '')}</div>
                ${n.link ? `<a href="${escapeHtml(n.link)}" style="display:inline-block;margin-top:6px;font-size:11px;font-weight:700;color:var(--primary-color);text-decoration:none;">Otvoriť →</a>` : ''}
            </div>
            <!-- Akcie: prečítané + kôš -->
            <div style="flex-shrink:0;display:flex;flex-direction:column;gap:2px;margin-top:1px;">
                <button type="button" title="${checkTitle}"
                    onclick="markNotificationRead('${key}')"
                    style="background:none;border:none;cursor:${n.is_read ? 'default' : 'pointer'};
                           padding:4px;border-radius:5px;display:flex;align-items:center;justify-content:center;
                           opacity:${n.is_read ? '0.35' : '1'};">
                    <span class="material-symbols-outlined" style="font-size:17px;color:${checkColor};">done</span>
                </button>
                <button type="button" title="Zmazať"
                    onclick="deleteNotification('${key}')"
                    style="background:none;border:none;cursor:pointer;padding:4px;border-radius:5px;
                           display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;opacity:0.7;">delete</span>
                </button>
            </div>
        </div>`;
    }).join('');
}

/** Mark a single notification as read */
async function markNotificationRead(key) {
    const n = g_notifications.find(x => x.key === key);
    if (!n || n.is_read) return;
    n.is_read = true;
    _renderNotificationList();
    // Update badge
    const unread = g_notifications.filter(x => !x.is_read).length;
    const badge  = document.getElementById('notif-badge');
    if (badge) {
        if (unread > 0) { badge.style.display = 'inline-flex'; badge.innerText = String(unread); }
        else            { badge.style.display = 'none'; }
    }
    const bellIcon = document.querySelector('#notif-btn .material-symbols-outlined');
    if (bellIcon) { if (unread === 0) bellIcon.classList.remove('bell-blinking'); }
    g_prevUnreadCount = unread;
    // Persist
    const fd = new FormData(); fd.append('key', key);
    fetch('api/notifications.php?action=mark_read', { method:'POST', body:fd }).catch(() => {});
}

/** Delete (hide) a single notification */
async function deleteNotification(key) {
    const idx = g_notifications.findIndex(x => x.key === key);
    if (idx === -1) return;
    g_notifications.splice(idx, 1);
    _renderNotificationList();
    // Update badge
    const unread = g_notifications.filter(x => !x.is_read).length;
    const badge  = document.getElementById('notif-badge');
    if (badge) {
        if (unread > 0) { badge.style.display = 'inline-flex'; badge.innerText = String(unread); }
        else            { badge.style.display = 'none'; }
    }
    const bellIcon = document.querySelector('#notif-btn .material-symbols-outlined');
    if (bellIcon) { if (unread === 0) bellIcon.classList.remove('bell-blinking'); }
    g_prevUnreadCount = unread;
    // Persist
    const fd = new FormData(); fd.append('key', key);
    fetch('api/notifications.php?action=delete', { method:'POST', body:fd }).catch(() => {});
}

function _playNotifSound() {
    try {
        if (!g_notifSoundEnabled) return;
        const ctx  = new (window.AudioContext || window.webkitAudioContext)();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.3);
        gain.gain.setValueAtTime(0.14, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.45);
    } catch (_) {}
}

/* =============================================
   NOTIFICATION DROPDOWN HELPERS
   ============================================= */

function toggleNotificationDropdown(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    const dropdown = document.getElementById('notif-dropdown');
    if (!dropdown) return;
    const isVisible = dropdown.style.display === 'block';
    if (!isVisible) {
        dropdown.style.display = 'block';
        _renderNotificationList();   // render current data immediately
        loadNotifications(false);    // then refresh from server
    } else {
        dropdown.style.display = 'none';
    }
}

function toggleNotifSound(e) {
    if (e) e.stopPropagation();
    g_notifSoundEnabled = !g_notifSoundEnabled;
    localStorage.setItem('vk_notif_sound', g_notifSoundEnabled ? 'enabled' : 'disabled');
    const icon = document.getElementById('notif-sound-icon');
    if (icon) {
        icon.innerText     = g_notifSoundEnabled ? 'volume_up' : 'volume_off';
        icon.style.color   = g_notifSoundEnabled ? 'var(--primary-color)' : 'var(--text-secondary)';
    }
    showAppToast(g_notifSoundEnabled ? 'Zvukové upozornenia zapnuté' : 'Zvukové upozornenia vypnuté',
                 g_notifSoundEnabled ? 'success' : 'info');
}

function markAllNotificationsRead() {
    if (!g_notifications.length) return;
    const keys = g_notifications.map(n => n.key).join(',');
    g_notifications.forEach(n => { n.is_read = true; });
    const badge = document.getElementById('notif-badge');
    if (badge) { badge.innerText = ''; badge.style.display = 'none'; }
    const bellIcon = document.querySelector('#notif-btn .material-symbols-outlined');
    if (bellIcon) bellIcon.classList.remove('bell-blinking');
    g_prevUnreadCount = 0;
    _renderNotificationList();
    showAppToast('Všetky notifikácie označené ako prečítané', 'success');
    // Persist
    const fd = new FormData(); fd.append('keys', keys);
    fetch('api/notifications.php?action=mark_all_read', { method:'POST', body:fd }).catch(() => {});
}

function deleteReadNotifications() {
    g_notifications = g_notifications.filter(n => !n.is_read);
    _renderNotificationList();
    showAppToast('Prečítané upozornenia boli vymazané', 'info');
    fetch('api/notifications.php?action=delete_read', { method:'POST' }).catch(() => {});
}

/* =============================================
   FORMATTERS
   ============================================= */

function formatCurrency(amount, currency = '€') {
    if (amount === null || amount === undefined || isNaN(amount)) return '0,00 ' + currency;
    return parseFloat(amount)
        .toFixed(2)
        .replace('.', ',')
        .replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
        + ' ' + currency;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('sk-SK',  { day:'2-digit', month:'2-digit', year:'numeric' })
             + ' '
             + d.toLocaleTimeString('sk-SK', { hour:'2-digit', minute:'2-digit' });
    } catch (_) { return dateStr; }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#039;');
}

/* =============================================
   PAGE INIT
   ============================================= */

function initDashboardPage() {
    // Theme toggle is handled by theme.js — do NOT add another listener here.
    loadNotifications(false);
    setInterval(() => loadNotifications(true), 45000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardPage);
} else {
    initDashboardPage();
}

// Close notification dropdown on outside click
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notif-dropdown');
    const notifBtn = document.getElementById('notif-btn');
    if (dropdown && dropdown.style.display === 'block') {
        if (!dropdown.contains(e.target) && (!notifBtn || !notifBtn.contains(e.target))) {
            dropdown.style.display = 'none';
        }
    }
});

/* =============================================
   MOBILE SIDEBAR TOGGLE
   ============================================= */

function toggleMobileSidebar() {
    const sidebar  = document.querySelector('.admin-sidebar');
    const backdrop = document.getElementById('mobile-sidebar-backdrop');
    const icon     = document.getElementById('mobile-menu-icon');
    if (!sidebar) return;

    const isOpen = sidebar.classList.contains('mobile-open');
    if (isOpen) {
        sidebar.classList.remove('mobile-open');
        if (backdrop) backdrop.classList.remove('active');
        if (icon) icon.textContent = 'left_panel_open';
    } else {
        sidebar.classList.add('mobile-open');
        if (backdrop) backdrop.classList.add('active');
        if (icon) icon.textContent = 'left_panel_close';
    }
}

function closeMobileSidebar() {
    const sidebar  = document.querySelector('.admin-sidebar');
    const backdrop = document.getElementById('mobile-sidebar-backdrop');
    const icon     = document.getElementById('mobile-menu-icon');
    if (sidebar)  sidebar.classList.remove('mobile-open');
    if (backdrop) backdrop.classList.remove('active');
    if (icon)     icon.textContent = 'left_panel_open';
}

// Zavrieť sidebar pri kliknutí na odkaz v mobile menu
document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.admin-sidebar');
    if (!sidebar || !sidebar.classList.contains('mobile-open')) return;
    if (window.innerWidth > 768) return;
    const link = e.target.closest('.admin-menu a, .logout-container a');
    if (link) closeMobileSidebar();
});
