<?php
// dashboard-topbar.php – Zdieľaná hlavička pre všetky dashboard stránky
// Pred includovaním nastaviť: $headerTitle, $headerIcon
if (!isset($headerTitle)) $headerTitle = 'Dashboard';
if (!isset($headerIcon))  $headerIcon  = 'dashboard';
?>
<div id="mobile-sidebar-backdrop" class="mobile-sidebar-backdrop" onclick="closeMobileSidebar()"></div>

<header class="admin-header" style="position:relative;">
    <div style="display:flex;align-items:center;gap:10px;min-width:0;">
        <!-- Hamburger – len na mobile -->
        <button type="button" class="theme-btn mobile-menu-toggle" onclick="toggleMobileSidebar()" title="Menu"
                style="flex-shrink:0;">
            <span class="material-symbols-outlined" id="mobile-menu-icon">left_panel_open</span>
        </button>
        <h1 class="admin-header-title" style="margin:0;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <span class="material-symbols-outlined"><?= htmlspecialchars($headerIcon) ?></span>
            <?= htmlspecialchars($headerTitle) ?>
        </h1>
    </div>
    <div class="admin-header-right" style="display:flex;gap:12px;align-items:center;flex-shrink:0;">

        <!-- ZVONČEK NOTIFIKÁCIÍ -->
        <div style="position:relative;">
            <button type="button" id="notif-btn" onclick="toggleNotificationDropdown(event)" class="theme-btn"
                style="position:relative;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                title="Upozornenia a Notifikácie">
                <span class="material-symbols-outlined" style="pointer-events:none;">notifications</span>
                <span id="notif-badge" style="display:none;position:absolute;top:-2px;right:-2px;min-width:17px;
                    height:17px;border-radius:9px;background:#ef4444;color:#fff;font-size:10px;font-weight:800;
                    align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--card-bg);
                    box-sizing:border-box;pointer-events:none;"></span>
            </button>

            <!-- DROPDOWN -->
            <div id="notif-dropdown" onclick="event.stopPropagation()"
                style="display:none;position:absolute;top:48px;right:0;width:340px;background:var(--card-bg);
                    border:1px solid var(--border-color);border-radius:14px;
                    box-shadow:0 16px 36px rgba(0,0,0,0.25);z-index:9999;overflow:hidden;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 18px;
                    border-bottom:1px solid var(--border-color);background:var(--bg-color);">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:18px;color:var(--primary-color);">notifications_active</span>
                        <strong style="font-size:14px;">Upozornenia</strong>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <button type="button" onclick="toggleNotifSound(event)" id="notif-sound-btn"
                            class="theme-btn" style="padding:4px 6px;font-size:11px;border-radius:6px;" title="Zvuk notifikácií">
                            <span class="material-symbols-outlined" id="notif-sound-icon"
                                style="font-size:16px;color:var(--primary-color);">volume_up</span>
                        </button>
                        <button type="button" onclick="markAllNotificationsRead()"
                            style="background:none;border:none;font-size:11.5px;color:var(--primary-color);
                            font-weight:600;cursor:pointer;padding:4px 6px;border-radius:5px;">Prečítané</button>
                        <button type="button" onclick="deleteReadNotifications()"
                            style="background:none;border:none;font-size:11.5px;color:var(--text-secondary);
                            font-weight:600;cursor:pointer;padding:4px 6px;border-radius:5px;" title="Zmazať prečítané">
                            <span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;">delete_sweep</span>
                        </button>
                    </div>
                </div>
                <div id="notif-list" style="max-height:340px;overflow-y:auto;padding:8px 0;">
                    <div style="text-align:center;padding:30px 15px;color:var(--text-secondary);font-size:13px;">
                        <span class="material-symbols-outlined"
                            style="font-size:28px;color:var(--text-secondary);margin-bottom:6px;">notifications_off</span>
                        <p style="margin:0;">Žiadne nové upozornenia.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- DOMOV -->
        <a href="index.php" class="theme-btn"
            style="display:flex;align-items:center;justify-content:center;text-decoration:none;"
            title="Späť na domovskú stránku">
            <span class="material-symbols-outlined">home</span>
        </a>

        <!-- TMAVÝ REŽIM -->
        <button id="theme-toggle" class="theme-btn" aria-label="Toggle Dark Mode">
            <span class="material-symbols-outlined">dark_mode</span>
        </button>
    </div>
</header>

<?php require_once __DIR__ . '/bug-report-modal.php'; ?>
