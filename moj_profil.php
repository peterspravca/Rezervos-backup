<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['customer', 'admin'])) {
    header("Location: index.php");
    exit;
}

require 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

if (($_SESSION['onboarding_completed'] ?? 1) == 0) {
    header('Location: onboarding.php');
    exit;
}

$showWelcomeModal = !empty($_SESSION['show_customer_welcome_modal']);
unset($_SESSION['show_customer_welcome_modal']);

$stmt = $conn->prepare("SELECT full_name, email, phone, whatsapp, avatar_url, two_factor_enabled, card_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$user_name = htmlspecialchars($res['full_name'] ?? '');
$user_email = htmlspecialchars($res['email'] ?? '');
$user_phone = htmlspecialchars($res['phone'] ?? '');
$user_whatsapp = htmlspecialchars($res['whatsapp'] ?? '');
$user_avatar = htmlspecialchars($res['avatar_url'] ?? 'img/default_avatar.png');
$two_factor_enabled = !empty($res['two_factor_enabled']);
$is_verified = !empty($res['card_verified']);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Môj profil - <?= BRAND_NAME ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/variables.css?v=7">
    <!-- Zdieľaný sidebar štýl (rovnaký ako Prevádzka) — musí byť PRED vlastným <style>, aby lokálne
         pravidlá nižšie zostali nadradené pre iný obsah, ale sidebar bol pixel-identický všade. -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=3.4">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <script src="assets/js/sidebar-hover-fix.js?v=1.1" defer></script>
    <script src="assets/js/theme.js?v=2.0"></script>
    <script src="assets/js/dashboard-common.js?v=3.1" defer></script>
    <?php if ($showWelcomeModal): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        infoModal(
            'Vitajte v Rezervos',
            'Vaša nástenka je pripravená. Doplňte si profilovú fotku a mrknite na Kreslo Hunter — upozorní vás na uvoľnené termíny u vašich obľúbených prevádzok.'
        );
    });
    </script>
    <?php endif; ?>

    <style>
        /* --sidebar-bg / --sidebar-hover úmyselne NEdefinované tu — preberajú sa zo zdieľaného
           assets/css/dashboard.css, aby bol sidebar vždy pixel-identický s Prevádzkou. */

        body {
            display: flex;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            height: 100vh;
            color: var(--text-primary);
            transition: background 0.3s, color 0.3s;
        }

        /* Sidebar — teraz úplne zdieľaný z assets/css/dashboard.css (rovnaký ako Prevádzka),
           žiadne vlastné pravidlá tu nemajú byť, aby sa vzhľad nikdy znova nerozišiel. */

        /* Fluid scaling for smaller laptop screens */
        @media (max-height: 740px) {
            .admin-logo { height: 60px; }
            .admin-logo img { max-height: 30px; height: 30px; width: 30px; }
            .admin-menu { padding: 14px 0 4px 0; gap: 2px; }
            .admin-menu a, .logout-container a { height: 34px; width: 38px; font-size: 13px; }
            .admin-menu a span.material-symbols-outlined, .logout-container a span.material-symbols-outlined { font-size: 20px; width: 22px; height: 22px; }
            .logout-container { padding: 4px 0; gap: 2px; }
            .admin-header { height: 60px; padding: 0 20px; }
        }

        /* Main content area */
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-color);
            height: 100vh;
        }

        .admin-header {
            height: 60px;
            background: var(--bg-color);
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            box-sizing: border-box;
            z-index: 10;
        }

        .admin-header-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            height: 26px;
            line-height: 26px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-content {
            flex: 1 1 0;
            height: 0;
            padding: 30px;
            overflow-y: auto;
        }
        
        .theme-btn {
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 50%;
            transition: 0.3s;
        }
        .theme-btn:hover {
            background: var(--card-bg);
        }

        .section {
            display: none;
            animation: fadeIn 0.4s;
        }
        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .admin-panel {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        .admin-panel h2:first-child {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid var(--border-color);
        }

        .stat-icon {
            background: rgba(176,128,66,0.1);
            color: var(--primary-color);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .stat-info p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-primary);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 14px;
            text-transform: uppercase;
        }

        tr:hover td {
            background: rgba(176,128,66,0.02);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-confirmed { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
        .status-pending { background: rgba(241, 196, 15, 0.1); color: #f1c40f; }
        .status-cancelled { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }

        /* Form styling */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg, #fff);
            color: var(--text-primary);
            font-family: inherit;
            box-sizing: border-box;
            transition: 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(176,128,66,0.1);
        }
        .form-control[readonly] {
            background: rgba(0,0,0,0.05);
            cursor: not-allowed;
        }
        body.dark-mode .form-control[readonly] {
            background: rgba(255,255,255,0.05);
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn:hover {
            background: #9a6b32;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        
        /* Favorites Grid */
        .fav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .fav-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
        }
        .fav-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .fav-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        .fav-content {
            padding: 20px;
        }
        .fav-content h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .fav-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        /* Booking Cards */
        .booking-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            transition: 0.3s;
        }
        .booking-card:hover {
            border-color: var(--primary-color);
        }
        .booking-info {
            flex: 1;
            min-width: 250px;
        }
        .booking-date {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .booking-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: var(--text-primary);
        }
        .booking-service {
            font-size: 15px;
            color: var(--text-primary);
            margin: 0;
        }
        .booking-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        
        /* Review Cards */
        .review-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .review-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        .review-stars {
            color: #f1c40f;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .review-text {
            color: var(--text-secondary);
            font-style: italic;
            margin: 0 0 10px 0;
            line-height: 1.5;
            background: rgba(0,0,0,0.02);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        body.dark-mode .review-text {
            background: rgba(255,255,255,0.02);
        }
        .review-date {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Switch (Toggle) Styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--primary-color);
        }
        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary-color);
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }

        body.dark-mode .sidebar-logo {
            filter: invert(1) hue-rotate(180deg) !important;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .fav-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body >

    <?php $active_nav = 'dashboard'; $spa_host = true; require_once 'includes/customer-sidebar.php'; ?>

    <main class="admin-main">
        <div id="mobile-sidebar-backdrop" class="mobile-sidebar-backdrop" onclick="closeMobileSidebar()"></div>
        <header class="admin-header" style="position:relative;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <button type="button" class="theme-btn mobile-menu-toggle" onclick="toggleMobileSidebar()" title="Menu" style="flex-shrink:0;">
                    <span class="material-symbols-outlined" id="mobile-menu-icon">left_panel_open</span>
                </button>
                <h1 class="admin-header-title" id="page-title" style="margin:0;">Prehľad</h1>
            </div>
            <div class="admin-header-right" style="display: flex; gap: 15px; align-items: center;">

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

                <a href="index.php" class="theme-btn" style="display: flex; align-items: center; justify-content: center; text-decoration: none;" title="Späť na domovskú stránku">
                    <span class="material-symbols-outlined">home</span>
                </a>
                <button id="theme-toggle" class="theme-btn" aria-label="Toggle Dark Mode">
                    <span class="material-symbols-outlined">dark_mode</span>
                </button>
            </div>
        </header>

        <div class="admin-content">
        <!-- Dashboard Section -->
        <div id="sec-dashboard" class="section active">
            
            <div class="welcome-card" style="background: var(--card-bg); padding: 25px; border-radius: 16px; margin-bottom: 30px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div>
                    <h2 style="margin: 0 0 5px 0; color: var(--text-primary); font-size: 24px; display: flex; align-items: center; gap: 8px;">Vitaj, <?= $user_name ?>! <span class="material-symbols-outlined" style="color: #d4af37;">waving_hand</span></h2>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 15px;">Dnes je <strong id="current-date-sk"></strong>. Prajeme ti úspešný deň a veľa skvelých termínov!</p>
                </div>
                <div class="calendar-icon" style="background: rgba(212,175,55,0.1); width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #d4af37;">
                    <span class="material-symbols-outlined" style="font-size: 30px;">calendar_month</span>
                </div>
            </div>
            <script>
                const dateOpts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('current-date-sk').innerText = new Date().toLocaleDateString('sk-SK', dateOpts);
            </script>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">event</span></div>
                    <div class="stat-info">
                        <h4>Nadchádzajúce termíny</h4>
                        <p id="stat-upcoming">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">history</span></div>
                    <div class="stat-info">
                        <h4>Absolvované termíny</h4>
                        <p id="stat-completed">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">favorite</span></div>
                    <div class="stat-info">
                        <h4>Obľúbené prevádzky</h4>
                        <p id="stat-favorites">0</p>
                    </div>
                </div>
            </div>

            <div class="admin-panel">
                <h2>Môj najbližší termín</h2>
                <div id="next-booking-container">
                    <p>Zatiaľ nemáš žiadny nadchádzajúci termín. <br><br> <a href="prevadzky.php" class="btn" style="display:inline-block; text-decoration:none;">Nájsť salón</a></p>
                </div>
            </div>
        </div>

        <!-- Bookings Section -->
        <div id="sec-bookings" class="section">
            <div class="admin-panel">
                <h2>Nadchádzajúce termíny</h2>
                <div id="upcoming-bookings-container">
                    <p>Načítavam...</p>
                </div>
            </div>

            <div class="admin-panel">
                <h2>História termínov</h2>
                <div id="past-bookings-container">
                    <p>Načítavam...</p>
                </div>
            </div>
        </div>

        <!-- Memberships Section -->
        <div id="sec-memberships" class="section">
            <div class="admin-panel">
                <h2>Moje permanentky a členstvá</h2>
                <div id="memberships-container">
                    <p>Načítavam...</p>
                </div>
            </div>
        </div>

        <!-- Newsletters Section -->
        <div id="sec-newsletters" class="section">
            <div class="admin-panel">
                <h2>Novinky od prevádzok</h2>
                <p style="font-size:13.5px;color:var(--text-secondary);margin:0 0 16px 0;">Tu si zapnete odber noviniek u prevádzok, kde ste už mali dokončenú návštevu. Prevádzku, u ktorej ste ešte neboli, môžete odoberať priamo na jej verejnej stránke.</p>
                <div id="newsletters-container">
                    <p>Načítavam...</p>
                </div>
            </div>
        </div>

        <!-- Gift Vouchers Section -->
        <div id="sec-giftvouchers" class="section">
            <div class="admin-panel">
                <h2>Moje darčekové poukazy</h2>
                <div id="giftvouchers-container">
                    <p>Načítavam...</p>
                </div>
            </div>
        </div>

        <!-- Loyalty Program Section -->
        <div id="sec-loyalty" class="section">
            <div class="admin-panel">
                <h2>Vernostný program</h2>
                <div id="loyalty-container">
                    <p>Načítavam...</p>
                </div>
            </div>
        </div>

        <!-- Favorites Section -->
        <div id="sec-favorites" class="section">
            <div class="admin-panel">
                <h2>Moje obľúbené prevádzky</h2>
                <div class="fav-grid" id="favorites-grid">
                    <p>Načítavam...</p>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div id="sec-reviews" class="section">
            <div class="admin-panel">
                <h2>Moje recenzie</h2>
                <div id="reviews-container">
                    <p>Zatiaľ si nepridal žiadnu recenziu.</p>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div id="sec-settings" class="section">
            <div class="admin-panel" style="max-width: 600px;">
                <h2>Osobné údaje</h2>
                
                <?php if ($is_verified): ?>
                    <div style="background: rgba(245, 176, 65, 0.1); border: 1px solid #f5b041; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                        <span class="material-symbols-outlined" style="color: #f5b041; font-size: 32px;">verified</span>
                        <div>
                            <h3 style="margin: 0; color: #f5b041;">Ste overený zákazník!</h3>
                            <p style="margin: 5px 0 0 0; font-size: 14px; color: var(--text-secondary);">Vďaka overeniu môžete robiť rezervácie bez nutnosti platiť zálohy.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:25px;">
                        <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:18px;display:flex;flex-direction:column;">
                            <div style="width:44px;height:44px;border-radius:8px;background:var(--input-bg);color:var(--primary-color);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                                <span class="material-symbols-outlined" style="font-size:24px;">verified_user</span>
                            </div>
                            <h3 style="margin:0 0 6px 0;font-size:15px;font-weight:800;color:var(--text-primary);">Overenie profilu</h3>
                            <p style="margin:0 0 14px 0;font-size:12.5px;color:var(--text-secondary);line-height:1.5;flex:1;">Prémiový overený odznak a rezervácie bez nutnosti skladať zálohy.</p>
                            <div style="font-size:18px;font-weight:800;color:var(--text-primary);margin-bottom:12px;">4,90 € <span style="font-size:11.5px;font-weight:600;color:var(--text-secondary);">/ jednorazovo</span></div>
                            <button type="button" class="btn" onclick="payVerificationFee()" style="width:100%;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">payments</span> Overiť profil
                            </button>
                        </div>
                        <div style="background:var(--card-bg);border:1.5px solid #f59e0b;border-radius:12px;padding:18px;display:flex;flex-direction:column;position:relative;">
                            <span style="position:absolute;top:14px;right:14px;font-size:10.5px;font-weight:800;color:#f59e0b;background:rgba(245,158,11,0.12);border-radius:8px;padding:3px 8px;text-transform:uppercase;letter-spacing:0.4px;">Najvýhodnejšie</span>
                            <div style="width:44px;height:44px;border-radius:8px;background:rgba(245,158,11,0.12);color:#f59e0b;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                                <span class="material-symbols-outlined" style="font-size:24px;">workspace_premium</span>
                            </div>
                            <h3 style="margin:0 0 6px 0;font-size:15px;font-weight:800;color:var(--text-primary);">VIP členstvo</h3>
                            <p style="margin:0 0 14px 0;font-size:12.5px;color:var(--text-secondary);line-height:1.5;flex:1;">Kreslo Hunter (echo Last Minute ponúk skôr ako ostatní), overený odznak a rezervácie bez záloh.</p>
                            <div style="font-size:18px;font-weight:800;color:var(--text-primary);margin-bottom:12px;">11,90 € <span style="font-size:11.5px;font-weight:600;color:var(--text-secondary);">/ rok</span></div>
                            <button type="button" class="btn-primary" onclick="showSection('hunter')" style="width:100%;border-radius:8px;background:#f59e0b;color:#11141a;font-weight:800;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">radar</span> Zobraziť ponuku
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
<form id="profile-form" onsubmit="event.preventDefault(); saveProfile();">
                    
                    <div class="form-group" style="text-align: center; margin-bottom: 25px;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--border-color); margin: 0 auto 15px auto; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center;" id="avatar-preview-container">
                            <?php if (!empty($res['avatar_url'])): ?>
                                <img id="avatar-preview" src="<?= htmlspecialchars($res['avatar_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span id="avatar-placeholder" class="material-symbols-outlined" style="font-size: 60px; color: var(--text-secondary);">person</span>
                                <img id="avatar-preview" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                            <?php endif; ?>
                            <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.5); padding: 5px 0; cursor: pointer; transition: 0.3s;" onclick="document.getElementById('profile-avatar').click();" onmouseover="this.style.background='rgba(0,0,0,0.7)'" onmouseout="this.style.background='rgba(0,0,0,0.5)'">
                                <span class="material-symbols-outlined" style="color: white; font-size: 18px;">photo_camera</span>
                            </div>
                        </div>
                        <input type="file" id="profile-avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                    </div>

                    <div class="form-group">
                        <label>Celé meno</label>
                        <input type="text" class="form-control" id="profile-name" value="<?= $user_name ?>">
                    </div>
                    <div class="form-group">
                        <label>E-mailová adresa (nedá sa zmeniť)</label>
                        <input type="email" class="form-control" value="<?= $user_email ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Telefónne číslo</label>
                        <input type="text" class="form-control" id="profile-phone" value="<?= $user_phone ?>" placeholder="+421 9xx xxx xxx">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp číslo</label>
                        <input type="text" class="form-control" id="profile-whatsapp" value="<?= $user_whatsapp ?>" placeholder="+421 9xx xxx xxx">
                    </div>
                    <div class="form-group">
                        <label>Dátum narodenia (nepovinné)</label>
                        <input type="date" class="form-control" id="profile-birthdate">
                        <p id="profile-birthdate-hint" style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Vďaka tomu vám prevádzky môžu poslať narodeninové prianie so zľavou. Zmeniť ho môžete len raz za rok.</p>
                    </div>
                    <div class="form-group">
                        <label>Pohlavie (nepovinné)</label>
                        <select class="form-control" id="profile-gender">
                            <option value="">Neuvedené</option>
                            <option value="female">Žena</option>
                            <option value="male">Muž</option>
                            <option value="other">Iné</option>
                        </select>
                    </div>

                    <button type="submit" class="btn">Uložiť zmeny</button>
                    <p id="profile-msg" style="margin-top: 10px;"></p>
                </form>
            </div>
        </div>

        <!-- Hunter Section (Pre Zákazníka) -->
        <div id="sec-hunter" class="section">
            <!-- HERO CARD (Matching dark luxury style) -->
            <div style="background: linear-gradient(135deg, #181c24 0%, #11141a 100%); border: 1.5px solid rgba(245, 158, 11, 0.35); border-radius: 16px; padding: 32px 30px; margin-bottom: 25px; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.35);">
                <div style="position: absolute; right: -40px; top: -40px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(245, 158, 11, 0.18) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;"></div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; position: relative; z-index: 2;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(245, 158, 11, 0.3);">
                                <span class="material-symbols-outlined" style="font-size: 28px;">radar</span>
                            </div>
                            <h2 style="margin: 0; font-size: 26px; font-weight: 850; letter-spacing: -0.5px; color: #ffffff; display: flex; align-items: center; gap: 10px;">
                                <span>Kreslo Hunter</span>
                                <span style="background: #f59e0b; color: #11141a; font-size: 11px; font-weight: 900; padding: 3px 9px; border-radius: 6px; letter-spacing: 0.8px; text-transform: uppercase;">PREMIUM</span>
                            </h2>
                        </div>
                        <p style="margin: 0 0 18px 0; font-size: 14.5px; color: #9ca3af; max-width: 680px; line-height: 1.5;">
                            Okamžitý odchyt voľných Last Minute termínov, uvoľnených kresiel a najlepších akcií vo vašom meste. Ušetrite desiatky eur mesačne a majte prístup k zľavám skôr ako ostatní.
                        </p>

                        <!-- Feature list -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 10px;">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #e5e7eb;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">check_circle</span>
                                <span>Okamžité Last Minute echo</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #e5e7eb;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">check_circle</span>
                                <span>Filtrovanie podľa mesta a služieb</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #e5e7eb;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">check_circle</span>
                                <span>E-mail & profilové notifikácie</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status badge -->
                    <div id="hunter-status-badge" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); padding: 14px 20px; border-radius: 12px; text-align: right; min-width: 200px;">
                        <span style="font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px;">Stav služby:</span>
                        <div id="hunter-active-text" style="font-size: 16px; font-weight: 800; color: #ef4444; margin-top: 2px;">Neaktívny</div>
                        <div id="hunter-expires-text" style="font-size: 12px; color: #9ca3af; margin-top: 2px;"></div>
                    </div>
                </div>
            </div>

            <!-- POROVNANIE: PREČO SI AKTIVOVAŤ HUNTERA -->
            <div class="admin-panel" style="margin-bottom: 25px; padding: 22px 24px;">
                <h4 style="margin: 0 0 14px 0; font-size: 16px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">help_outline</span>
                    <span>Prečo si aktivovať Kreslo Huntera?</span>
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                    <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #ef4444; font-weight: 800; font-size: 13.5px; margin-bottom: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">cancel</span>
                            <span>Bez Kreslo Huntera (Zadarmo)</span>
                        </div>
                        <p style="margin: 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.5;">
                            Last Minute ponuky <strong>nedostávate do e-mailu ani ako notifikácie</strong>. Môžete si ich iba pasívne prezerať na webe, kedy sú už najlepšie a najžiadanejšie termíny často obsadené inými zákazníkmi.
                        </p>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.06); border: 1.5px solid rgba(245, 158, 11, 0.35); border-radius: 10px; padding: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #f59e0b; font-weight: 800; font-size: 13.5px; margin-bottom: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">radar</span>
                            <span>S Kreslo Hunterom (0,90 € / mes.)</span>
                        </div>
                        <p style="margin: 0; font-size: 12.5px; color: var(--text-primary); line-height: 1.5;">
                            Hunter nonstop automaticky stráži uvoľnené termíny a <strong>okamžite vám posiela e-mailové echo v sekunde ich zverejnenia</strong>. Získate prednostný odchyt a rezervujete si zľavnený termín ako prvý!
                        </p>
                    </div>
                </div>
            </div>

            <!-- PRICING OPTIONS & ACTIVATION -->
            <div id="hunter-pricing-container" class="admin-panel" style="margin-bottom: 25px;">
                <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 800; color: var(--text-primary);">Aktivácia Kreslo Huntera</h3>
                <p style="margin: 0 0 20px 0; font-size: 13.5px; color: var(--text-secondary);">Vyberte si predplatné a získajte nepretržitý monitoring akcií:</p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <!-- Option 1: Monthly -->
                    <div style="background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h4 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--text-primary);">Mesačné predplatné</h4>
                                <span style="font-size: 11px; font-weight: 700; color: var(--text-secondary); background: var(--card-bg); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: 6px;">FLEXIBILNÉ</span>
                            </div>
                            <p style="font-size: 13px; color: var(--text-secondary); margin: 0 0 16px 0;">Symbolický poplatok za nepretržitý lov termínov bez viazanosti.</p>
                            
                            <div style="font-size: 32px; font-weight: 900; color: var(--text-primary); margin-bottom: 16px;">
                                0,90 € <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">/ mesiac</span>
                            </div>
                        </div>

                        <button type="button" onclick="activateHunter('monthly')" class="btn" style="width: 100%; border-radius: 8px; padding: 12px; font-weight: 700; font-size: 13.5px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">bolt</span>
                            <span>Aktivovať na mesiac (0,90 €)</span>
                        </button>
                    </div>

                    <!-- Option 2: Yearly (Recommended) -->
                    <div style="background: var(--card-bg); border: 2px solid #f59e0b; border-radius: 14px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 8px 25px rgba(245, 158, 11, 0.15);">
                        <div style="position: absolute; top: -12px; right: 20px; background: #f59e0b; color: #11141a; font-size: 10.5px; font-weight: 900; padding: 3px 10px; border-radius: 6px; letter-spacing: 0.5px;">
                            ODPORÚČAME • 2 MESIACE ZADARMO
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h4 style="margin: 0; font-size: 17px; font-weight: 800; color: #f59e0b;">Ročné predplatné (12 mesiacov)</h4>
                            </div>
                            <p style="font-size: 13px; color: var(--text-secondary); margin: 0 0 16px 0;">Získate celých <strong>12 mesiacov</strong> za cenu 10 mesiacov (9,00 €) – 2 mesiace máte zadarmo!</p>
                            
                            <div style="font-size: 32px; font-weight: 900; color: var(--text-primary); margin-bottom: 16px;">
                                9,00 € <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">/ rok</span>
                                <div style="font-size: 12px; font-weight: 700; color: #10b981; margin-top: 4px;">12 mesiacov za cenu 10 (ušetríte 1,80 €)</div>
                            </div>
                        </div>

                        <button type="button" onclick="activateHunter('yearly')" class="btn-primary" style="width: 100%; border-radius: 8px; padding: 12px; font-weight: 700; font-size: 13.5px; display: flex; align-items: center; justify-content: center; gap: 8px; background: #f59e0b; color: #11141a;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">star</span>
                            <span>Aktivovať na rok (9,00 €)</span>
                        </button>
                    </div>

                    <!-- Option 3: VIP KOMBO Balík (Hunter + Overený zákazník BEZ ZÁLOH) -->
                    <div style="background: linear-gradient(135deg, rgba(176, 128, 66, 0.08) 0%, rgba(245, 158, 11, 0.12) 100%); border: 2px solid var(--primary-color); border-radius: 14px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 8px 25px rgba(176, 128, 66, 0.2);">
                        <div style="position: absolute; top: -12px; right: 20px; background: linear-gradient(135deg, #b08042 0%, #d4a359 100%); color: #ffffff; font-size: 10.5px; font-weight: 900; padding: 3px 10px; border-radius: 6px; letter-spacing: 0.5px;">
                            VIP KOMBO BALÍK • VŠETKO V JEDNOM
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h4 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--primary-color); display: flex; align-items: center; gap: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">workspace_premium</span>
                                    <span>VIP Zákazník (Hunter + Bez záloh)</span>
                                </h4>
                            </div>
                            <p style="font-size: 13px; color: var(--text-secondary); margin: 0 0 12px 0;">Kreslo Hunter na <strong>12 mesiacov</strong> + trvalé overenie identity pre <strong>rezervácie 100% bez záloh</strong>.</p>
                            
                            <div style="font-size: 32px; font-weight: 900; color: var(--text-primary); margin-bottom: 14px;">
                                11,90 € <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">/ rok</span>
                                <div style="font-size: 12px; font-weight: 700; color: #10b981; margin-top: 4px;">Ušetríte 2,00 € (pôvodne 13,90 €)</div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; font-size: 12px; color: var(--text-primary);">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="material-symbols-outlined" style="color: #10b981; font-size: 16px;">check_circle</span>
                                    <span>12 mesiacov Kreslo Hunter nonstop odchyt</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="material-symbols-outlined" style="color: #10b981; font-size: 16px;">check_circle</span>
                                    <span>Rezervácie v salónoch <strong>bez záloh</strong></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="material-symbols-outlined" style="color: #10b981; font-size: 16px;">check_circle</span>
                                    <span>Zlatý odznak <strong>Overený zákazník</strong></span>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="activateHunter('combo_yearly')" class="btn" style="width: 100%; border-radius: 8px; padding: 12px; font-weight: 700; font-size: 13.5px; display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, #b08042 0%, #d4a359 100%); color: #ffffff;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">verified_user</span>
                            <span>Aktivovať VIP Balík (11,90 €)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- FILTRE A PREFERENCIE LOVU -->
            <div class="admin-panel" style="margin-bottom: 25px;">
                <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">tune</span>
                    <span>Nastavenie vášho revíru a filtrov</span>
                </h3>
                <p style="margin: 0 0 20px 0; font-size: 13.5px; color: var(--text-secondary);">Zvoľte si mesto a kategórie, na ktoré vás má Hunter ihneď upozorniť:</p>

                <form onsubmit="saveHunterFilters(event)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                        <div>
                            <label style="display:block; font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:6px;">Vaše mesto / lokalita</label>
                            <select id="hunter-city" style="width:100%; box-sizing:border-box; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13.5px; outline:none;">
                                <option value="">Všetky mestá (Celé Slovensko)</option>
                                <option value="Bratislava">Bratislava</option>
                                <option value="Košice">Košice</option>
                                <option value="Žilina">Žilina</option>
                                <option value="Prešov">Prešov</option>
                                <option value="Nitra">Nitra</option>
                                <option value="Banská Bystrica">Banská Bystrica</option>
                                <option value="Trnava">Trnava</option>
                                <option value="Trenčín">Trenčín</option>
                                <option value="Poprad">Poprad</option>
                                <option value="Martin">Martin</option>
                                <option value="Zvolen">Zvolen</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:6px;">Minimálna zľava</label>
                            <select id="hunter-min-discount" style="width:100%; box-sizing:border-box; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13.5px; outline:none;">
                                <option value="0">Všetky akcie a voľné kreslá (aj bez zľavy)</option>
                                <option value="10">Zľava aspoň 10% a viac</option>
                                <option value="20">Zľava aspoň 20% a viac</option>
                                <option value="30">Zľava aspoň 30% a viac</option>
                                <option value="50">Mega zľavy 50% a viac</option>
                            </select>
                        </div>
                    </div>

                    <!-- Kategórie -->
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:8px;">Sledované kategórie služieb</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;" id="hunter-categories-grid">
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="barber-a-kadernictvo" style="accent-color: var(--primary-color);"> Barber & Kaderníctvo
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="kozmetika-a-plet" style="accent-color: var(--primary-color);"> Kozmetika & Pleť
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="nechty-a-manikura" style="accent-color: var(--primary-color);"> Nechty & Manikúra
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="masaze-a-fyzioterapia" style="accent-color: var(--primary-color);"> Masáže & Fyzio
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="pedikura" style="accent-color: var(--primary-color);"> Pedikúra
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="tetovanie-a-piercing" style="accent-color: var(--primary-color);"> Tetovanie & Piercing
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="wellness-a-spa" style="accent-color: var(--primary-color);"> Wellness & Spa
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <input type="checkbox" name="hunter_cat[]" value="mihalnice-a-obocie" style="accent-color: var(--primary-color);"> Mihalnice & Obočie
                            </label>
                        </div>
                    </div>

                    <!-- E-mail notifikácia -->
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <input type="checkbox" id="hunter-notify-email" checked style="width: 17px; height: 17px; accent-color: var(--primary-color); cursor: pointer;">
                        <label for="hunter-notify-email" style="font-size: 13.5px; color: var(--text-primary); font-weight: 600; cursor: pointer;">
                            Zasielať e-mailové upozornenia ihneď pri objavení novej zľavy
                        </label>
                    </div>

                    <button type="submit" class="btn" style="border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                        <span>Uložiť filtre revíru</span>
                    </button>
                </form>
            </div>

            <!-- LIVE ÚLOVKY HUNTERA -->
            <div class="admin-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: #ef4444;">local_fire_department</span>
                        <span>Aktuálne ulovené akcie a voľné termíny</span>
                    </h3>
                    <a href="last_minute.php" class="btn" style="padding: 6px 14px; font-size: 12px; border-radius: 6px; text-decoration: none;">
                        Všetky Last Minute akcie &rarr;
                    </a>
                </div>

                <div id="hunter-catches-grid" class="fav-grid">
                    <p style="color: var(--text-secondary); padding: 20px 0;">Načítavam aktuálne úlovky...</p>
                </div>
            </div>
        </div>

        <!-- Security Section -->
        <?php include 'components/security.php'; ?>

        </div>
    </main>

    <!-- Review Modal -->
    <div id="review-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:var(--card-bg); padding:30px; border-radius:12px; width:100%; max-width:400px; text-align:center;">
            <h2 id="review-salon-name">Hodnotiť prevádzku</h2>
            <form onsubmit="event.preventDefault(); submitReview();">
                <input type="hidden" id="review-est-id">
                <input type="hidden" id="review-booking-id">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Hodnotenie (1-5 hviezdičiek)</label>
                    <div style="font-size:30px; color:#f1c40f; cursor:pointer;" id="star-rating">
                        <span class="material-symbols-outlined" onclick="setRating(1)">star</span>
                        <span class="material-symbols-outlined" onclick="setRating(2)">star</span>
                        <span class="material-symbols-outlined" onclick="setRating(3)">star</span>
                        <span class="material-symbols-outlined" onclick="setRating(4)">star</span>
                        <span class="material-symbols-outlined" onclick="setRating(5)">star</span>
                    </div>
                    <input type="hidden" id="review-rating" value="5">
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="font-size:13px;">Podrobné hodnotenie (voliteľné)</label>
                    <div style="display:grid;grid-template-columns:1fr auto;gap:6px 10px;align-items:center;font-size:13px;text-align:left;">
                        <span>Kvalita služby</span><select id="review-cat-kvalita" style="padding:4px 8px;border-radius:6px;"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>
                        <span>Komunikácia</span><select id="review-cat-komunikacia" style="padding:4px 8px;border-radius:6px;"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>
                        <span>Prístup personálu</span><select id="review-cat-pristup" style="padding:4px 8px;border-radius:6px;"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>
                        <span>Prostredie</span><select id="review-cat-prostredie" style="padding:4px 8px;border-radius:6px;"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Vaša recenzia (nepovinné)</label>
                    <textarea class="form-control" id="review-text" rows="4" placeholder="Ako ste boli spokojný/á?"></textarea>
                </div>
                <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                    <button type="button" class="btn" style="background:#555;" onclick="closeReviewModal()">Zrušiť</button>
                    <button type="submit" class="btn">Uložiť recenziu</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/theme.js?v=1.1"></script>
    <script>
        document.head.insertAdjacentHTML('beforeend', `
        <style>
            .switch input:checked + .slider { background-color: #2ecc71; }
            .switch input:checked + .slider span { transform: translateX(24px); }
        </style>`);

function showSection(sec) {
            document.querySelectorAll('.section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.admin-menu a').forEach(el => el.classList.remove('active'));
            
            const secEl = document.getElementById('sec-' + sec);
            if (secEl) secEl.classList.add('active');
            const navEl = document.getElementById('nav-' + sec);
            if (navEl) navEl.classList.add('active');

            const adminContent = document.querySelector('.admin-content');
            if (adminContent) adminContent.scrollTop = 0;

            if (sec === 'dashboard') {
                document.getElementById('page-title').innerText = 'Prehľad';
                loadDashboard();
            } else if (sec === 'bookings') {
                document.getElementById('page-title').innerText = 'Moje termíny';
                loadBookings();
            } else if (sec === 'favorites') {
                document.getElementById('page-title').innerText = 'Obľúbené prevádzky';
                loadFavorites();
            } else if (sec === 'reviews') {
                document.getElementById('page-title').innerText = 'Moje recenzie';
                loadReviews();
            } else if (sec === 'hunter') {
                document.getElementById('page-title').innerText = 'Kreslo Hunter (Deal & Last Minute)';
                loadHunterData();
            } else if (sec === 'settings') {
                document.getElementById('page-title').innerText = 'Nastavenia účtu';
                loadProfile();
            } else if (sec === 'security') {
                document.getElementById('page-title').innerText = 'Zabezpečenie';
                if (typeof loadSecuritySettings === 'function') loadSecuritySettings();
            } else if (sec === 'memberships') {
                document.getElementById('page-title').innerText = 'Moje permanentky';
                loadMemberships();
            } else if (sec === 'giftvouchers') {
                document.getElementById('page-title').innerText = 'Moje darčekové poukazy';
                loadGiftVouchersList();
            } else if (sec === 'loyalty') {
                document.getElementById('page-title').innerText = 'Vernostný program';
                loadLoyaltyProgress();
            } else if (sec === 'newsletters') {
                document.getElementById('page-title').innerText = 'Novinky od prevádzok';
                loadMyNewsletters();
            }

            // Auto-close menu on mobile after selection
            if (window.innerWidth <= 768 && typeof closeMobileSidebar === 'function') {
                closeMobileSidebar();
            }
        }

        function showToast(msg, type = 'success') {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.style.cssText = 'position: fixed; bottom: 25px; right: 25px; z-index: 100000; display: flex; flex-direction: column; gap: 10px; pointer-events: none;';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            const isErr = type === 'error';
            toast.style.cssText = `
                background: ${isErr ? '#ef4444' : '#10b981'};
                color: #ffffff;
                padding: 12px 20px;
                border-radius: 8px;
                font-size: 13.5px;
                font-weight: 600;
                box-shadow: 0 10px 25px rgba(0,0,0,0.25);
                display: flex;
                align-items: center;
                gap: 8px;
                pointer-events: auto;
                transition: all 0.3s ease;
                opacity: 0;
                transform: translateY(10px);
            `;
            toast.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px;">${isErr ? 'error' : 'check_circle'}</span> <span>${msg}</span>`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; }, 10);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        function confirmModal(message, options = {}) {
            return new Promise((resolve) => {
                let overlay = document.getElementById('_confirm_modal_overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = '_confirm_modal_overlay';
                    overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:100001;align-items:center;justify-content:center;backdrop-filter:blur(2px);';
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

        async function loadHunterData() {
            try {
                let res = await fetch('api/hunter.php?action=get_status');
                let data = await res.json();
                if (data.success) {
                    const activeBadge = document.getElementById('hunter-active-text');
                    const expiresText = document.getElementById('hunter-expires-text');
                    const pricingBox = document.getElementById('hunter-pricing-container');
                    
                    if (data.is_active) {
                        let planLabel = 'Mesačný';
                        if (data.plan_type === 'yearly') planLabel = 'Ročný (12 mesiacov)';
                        else if (data.plan_type === 'combo' || data.plan_type === 'combo_yearly') planLabel = 'VIP Balík (Hunter + Bez záloh)';

                        activeBadge.innerText = 'AKTÍVNY • ' + planLabel;
                        activeBadge.style.color = '#10b981';
                        expiresText.innerText = data.expires_at ? 'Platné do: ' + data.expires_at : '';
                        if (pricingBox) {
                            pricingBox.innerHTML = `
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                    <div>
                                        <h3 style="margin: 0 0 4px 0; font-size: 17px; font-weight: 800; color: #10b981; display: flex; align-items: center; gap: 8px;">
                                            <span class="material-symbols-outlined">verified</span>
                                            <span>Váš Kreslo Hunter je aktívny</span>
                                        </h3>
                                        <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">
                                            Typ predplatného: <strong>${planLabel}</strong> | Platnosť do: <strong>${data.expires_at || 'Neurčito'}</strong>
                                        </p>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="button" onclick="activateHunter('combo_yearly')" class="btn" style="background: #f59e0b; color: #11141a; font-size: 12.5px; padding: 9px 16px; border-radius: 6px; font-weight: 800;">
                                            Predĺžiť VIP Balík (+ bez záloh)
                                        </button>
                                    </div>
                                </div>
                            `;
                        }
                    } else {
                        activeBadge.innerText = 'Neaktívny';
                        activeBadge.style.color = '#ef4444';
                        expiresText.innerText = 'Zvoľte si predplatné nižšie';
                    }

                    // Populate filters
                    if (data.settings) {
                        if (document.getElementById('hunter-city')) {
                            document.getElementById('hunter-city').value = data.settings.city || '';
                        }
                        if (document.getElementById('hunter-min-discount')) {
                            document.getElementById('hunter-min-discount').value = data.settings.min_discount || 10;
                        }
                        if (document.getElementById('hunter-notify-email')) {
                            document.getElementById('hunter-notify-email').checked = data.settings.notify_email;
                        }
                        const cats = data.settings.categories || [];
                        document.querySelectorAll('input[name="hunter_cat[]"]').forEach(cb => {
                            cb.checked = cats.includes(cb.value);
                        });
                    }

                    // Render catches
                    const catchesGrid = document.getElementById('hunter-catches-grid');
                    if (catchesGrid) {
                        if (data.catches && data.catches.length > 0) {
                            let html = '';
                            data.catches.forEach(c => {
                                let img = c.avatar_url ? (c.avatar_url.startsWith('http') ? c.avatar_url : 'uploads/avatars/' + c.avatar_url) : '<?= ltrim(BRAND_LOGO, '/') ?>';
                                let discountBadge = c.discount_percent ? `<span style="background: #ef4444; color: #fff; font-size: 11px; font-weight: 800; padding: 2px 7px; border-radius: 6px;">-${c.discount_percent}%</span>` : '';
                                html += `
                                <div class="fav-card" style="border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: var(--card-bg);">
                                    <div style="position: relative; height: 120px; background: var(--bg-color);">
                                        <img src="${img}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='<?= ltrim(BRAND_LOGO, '/') ?>'">
                                        <div style="position: absolute; top: 10px; left: 10px; display: flex; gap: 6px;">
                                            <span style="background: rgba(0,0,0,0.75); color: #f59e0b; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px; display: flex; align-items: center; gap: 4px;">
                                                <span class="material-symbols-outlined" style="font-size: 13px;">radar</span> Ulovené
                                            </span>
                                            ${discountBadge}
                                        </div>
                                    </div>
                                    <div class="fav-content" style="padding: 16px;">
                                        <h4 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 800; color: var(--text-primary);">${c.establishment_name || c.title || 'Salón'}</h4>
                                        <p style="margin: 0 0 10px 0; font-size: 12.5px; color: var(--text-secondary);">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">location_on</span> ${c.city || 'Slovensko'}
                                        </p>
                                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.4;">
                                            ${c.description ? c.description.substring(0, 75) + '...' : (c.service_name || 'Last Minute termín')}
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div style="font-size: 16px; font-weight: 900; color: #10b981;">
                                                ${c.new_price ? c.new_price + ' €' : (c.price ? c.price + ' €' : 'Zľavnené')}
                                            </div>
                                            <a href="profil.php?slug=${c.slug || ''}" class="btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; text-decoration: none;">
                                                Rezervovať
                                            </a>
                                        </div>
                                    </div>
                                </div>`;
                            });
                            catchesGrid.innerHTML = html;
                        } else {
                            catchesGrid.innerHTML = `
                                <div style="grid-column: 1 / -1; padding: 30px; text-align: center; background: var(--bg-color); border: 1px dashed var(--border-color); border-radius: 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 36px; color: var(--text-secondary); margin-bottom: 8px;">radar</span>
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 14px;">Hunter aktuálne skenuje sieť salónov</div>
                                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 4px 0 0 0;">Nové uvoľnené Last Minute termíny sa tu zobrazia automaticky.</p>
                                </div>
                            `;
                        }
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function activateHunter(plan) {
            try {
                const fd = new FormData();
                fd.append('action', 'activate');
                fd.append('plan', plan);
                
                let res = await fetch('api/hunter.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Kreslo Hunter bol úspešne aktivovaný!', 'success');
                    loadHunterData();
                } else {
                    showToast(data.error || 'Chyba pri aktivácii.', 'error');
                }
            } catch(e) {
                showToast('Chyba spojenia so serverom.', 'error');
            }
        }

        async function saveHunterFilters(e) {
            e.preventDefault();
            const city = document.getElementById('hunter-city').value;
            const minDiscount = document.getElementById('hunter-min-discount').value;
            const notifyEmail = document.getElementById('hunter-notify-email').checked ? 1 : 0;
            const selectedCats = [];
            document.querySelectorAll('input[name="hunter_cat[]"]:checked').forEach(cb => {
                selectedCats.push(cb.value);
            });

            const fd = new FormData();
            fd.append('action', 'save_filters');
            fd.append('city', city);
            fd.append('min_discount', minDiscount);
            if (notifyEmail) fd.append('notify_email', '1');
            selectedCats.forEach(c => fd.append('categories[]', c));

            try {
                let res = await fetch('api/hunter.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Filtre revíru boli uložené!', 'success');
                } else {
                    showToast(data.error || 'Chyba pri ukladaní filtrov.', 'error');
                }
            } catch(e) {
                showToast('Chyba spojenia so serverom.', 'error');
            }
        }

        // --- API functions ---
        async function loadDashboard() {
            try {
                const fd = new FormData(); fd.append('action', 'get_dashboard_stats');
                let res = await fetch('api/customer.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    document.getElementById('stat-upcoming').innerText = data.stats.upcoming;
                    document.getElementById('stat-completed').innerText = data.stats.completed;
                    document.getElementById('stat-favorites').innerText = data.stats.favorites;
                    
                    let nb = document.getElementById('next-booking-container');
                    if (data.nearest) {
                        nb.innerHTML = `<p style="font-size:18px;"><strong>${data.nearest.salon_name}</strong><br>
                                        <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 20px;">calendar_today</span> 
                                        ${data.nearest.booking_date} o ${data.nearest.start_time.substring(0,5)}</p>`;
                    } else {
                        nb.innerHTML = `<p>Zatiaľ nemáš žiadny nadchádzajúci termín. <br><br> <a href="prevadzky.php" class="btn" style="display:inline-block; text-decoration:none;">Nájsť salón</a></p>`;
                    }
                }
            } catch(e) {}
        }

        async function loadBookings() {
            try {
                // Upcoming
                const fd1 = new FormData(); fd1.append('action', 'get_bookings'); fd1.append('type', 'upcoming');
                let res1 = await fetch('api/customer.php', { method: 'POST', body: fd1 });
                let data1 = await res1.json();
                let container1 = document.getElementById('upcoming-bookings-container');
                if (data1.success && data1.data.length > 0) {
                    let html = '';
                    data1.data.forEach(b => {
                        let statusClass = b.status === 'confirmed' ? 'status-confirmed' : 'status-pending';
                        let statusText = b.status === 'confirmed' ? 'Potvrdené' : 'Čaká na schválenie';
                        html += `
                        <div class="booking-card">
                            <div class="booking-info">
                                <div class="booking-date">
                                    <span class="material-symbols-outlined" style="font-size:16px;">calendar_month</span>
                                    ${b.booking_date} o ${b.start_time.substring(0,5)}
                                </div>
                                <h3 class="booking-title">${b.salon_name}</h3>
                                <p class="booking-service">${b.service_name} &bull; <strong>${b.price} €</strong></p>
                            </div>
                            <div class="booking-actions">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                                <button class="btn btn-danger" onclick="cancelBooking(${b.id})" style="padding: 8px 15px; font-size:13px;">
                                    <span class="material-symbols-outlined" style="font-size:14px; vertical-align:text-bottom;">cancel</span> Zrušiť
                                </button>
                            </div>
                        </div>`;
                    });
                    container1.innerHTML = html;
                } else {
                    container1.innerHTML = `<p style="color:var(--text-secondary);">Nemáš žiadne nadchádzajúce termíny.</p>`;
                }

                // Past
                const fd2 = new FormData(); fd2.append('action', 'get_bookings'); fd2.append('type', 'past');
                let res2 = await fetch('api/customer.php', { method: 'POST', body: fd2 });
                let data2 = await res2.json();
                let container2 = document.getElementById('past-bookings-container');
                if (data2.success && data2.data.length > 0) {
                    let html = '';
                    data2.data.forEach(b => {
                        let isCancelled = b.status === 'cancelled';
                        let statusClass = isCancelled ? 'status-cancelled' : 'status-confirmed';
                        let statusText = isCancelled ? 'Zrušené' : 'Absolvované';
                        let actionBtn = b.status === 'completed' ? `<button class="btn" style="padding: 8px 15px; font-size:13px;" onclick="openReviewModal(${b.id}, ${b.establishment_id}, '${b.salon_name}')"><span class="material-symbols-outlined" style="font-size:14px; vertical-align:text-bottom;">star</span> Hodnotiť</button>` : '';
                        
                        html += `
                        <div class="booking-card" style="opacity: ${isCancelled ? '0.7' : '1'};">
                            <div class="booking-info">
                                <div class="booking-date">
                                    <span class="material-symbols-outlined" style="font-size:16px;">history</span>
                                    ${b.booking_date}
                                </div>
                                <h3 class="booking-title">${b.salon_name}</h3>
                                <p class="booking-service">${b.service_name}</p>
                            </div>
                            <div class="booking-actions">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                                ${actionBtn}
                            </div>
                        </div>`;
                    });
                    container2.innerHTML = html;
                } else {
                    container2.innerHTML = `<p style="color:var(--text-secondary);">Zatiaľ si neabsolvoval žiadny termín.</p>`;
                }
            } catch(e) {}
        }
        
        async function cancelBooking(id) {
            if(await confirmModal("Naozaj chceš zrušiť tento termín?")) {
                const fd = new FormData(); fd.append('action', 'cancel_booking'); fd.append('booking_id', id);
                let res = await fetch('api/customer.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Potvrdzovací odkaz bol odoslaný na váš e-mail.', 'success');
                    loadBookings();
                } else {
                    showToast(data.message, 'error');
                }
            }
        }

        async function loadMyNewsletters() {
            const container = document.getElementById('newsletters-container');
            try {
                const res = await fetch('api/newsletter.php?action=get_my_subscriptions');
                const data = await res.json();
                if (!data.success || !data.establishments || data.establishments.length === 0) {
                    container.innerHTML = `<p style="color:var(--text-secondary);">Zatiaľ nemáte dokončenú návštevu u žiadnej prevádzky s touto funkciou.</p>`;
                    return;
                }
                container.innerHTML = data.establishments.map(e => `
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border-color);">
                        <span style="font-weight:600;">${e.name}</span>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <span style="font-size:12.5px;color:var(--text-secondary);">${e.confirmed == 1 ? 'Odoberáte' : 'Neodoberáte'}</span>
                            <input type="checkbox" ${e.confirmed == 1 ? 'checked' : ''} onchange="toggleMyNewsletter(${e.id}, this.checked)">
                        </label>
                    </div>
                `).join('');
            } catch (e) {
                container.innerHTML = `<p style="color:var(--text-secondary);">Chyba pri načítaní.</p>`;
            }
        }

        async function toggleMyNewsletter(establishmentId, subscribe) {
            const fd = new FormData();
            fd.append('action', 'toggle_my_subscription');
            fd.append('establishment_id', establishmentId);
            fd.append('subscribe', subscribe ? '1' : '0');
            try {
                const res = await fetch('api/newsletter.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(subscribe ? 'Odber zapnutý.' : 'Odber vypnutý.', 'success');
                } else {
                    showToast(data.error || 'Chyba pri ukladaní.', 'error');
                    loadMyNewsletters();
                }
            } catch (e) {
                showToast('Chyba pripojenia k serveru.', 'error');
                loadMyNewsletters();
            }
        }

        async function loadMemberships() {
            const container = document.getElementById('memberships-container');
            try {
                const fd = new FormData(); fd.append('action', 'list_my_memberships');
                const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.success || !data.memberships || data.memberships.length === 0) {
                    container.innerHTML = '<p>Zatiaľ nemáte žiadnu permanentku ani členstvo.</p>';
                    return;
                }
                const statusLabel = { pending_payment: 'Čaká na potvrdenie platby', active: 'Aktívna', expired: 'Vyčerpaná', cancelled: 'Zrušená' };
                const statusColor = { pending_payment: '#f59e0b', active: '#10b981', expired: '#9ca3af', cancelled: '#ef4444' };
                container.innerHTML = data.memberships.map(m => `
                    <div class="booking-card">
                        <div class="booking-info">
                            <div class="booking-date"><strong>${m.package_name}</strong> — ${m.establishment_name}</div>
                            <div style="font-size:12.5px;color:var(--text-secondary);margin-top:4px;">
                                <span style="font-weight:700;color:${statusColor[m.status]};">${statusLabel[m.status]}</span>
                                · využité ${m.visits_used}/${m.visits_total}${m.valid_until ? ' · platí do ' + m.valid_until : ''}
                            </div>
                        </div>
                    </div>`).join('');
            } catch (err) {
                container.innerHTML = '<p style="color:#ef4444;">Permanentky sa nepodarilo načítať.</p>';
            }
        }

        async function loadGiftVouchersList() {
            const container = document.getElementById('giftvouchers-container');
            try {
                const fd = new FormData(); fd.append('action', 'list_my_gift_vouchers');
                const res = await fetch('api/gift_vouchers.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.success || !data.gift_vouchers || data.gift_vouchers.length === 0) {
                    container.innerHTML = '<p>Zatiaľ ste nekúpili žiadny darčekový poukaz.</p>';
                    return;
                }
                const statusLabel = { pending_payment: 'Čaká na potvrdenie platby', active: 'Aktívny', redeemed: 'Vyčerpaný', expired: 'Expirovaný', cancelled: 'Zrušený' };
                const statusColor = { pending_payment: '#f59e0b', active: '#10b981', redeemed: '#9ca3af', expired: '#9ca3af', cancelled: '#ef4444' };
                container.innerHTML = data.gift_vouchers.map(v => `
                    <div class="booking-card">
                        <div class="booking-info">
                            <div class="booking-date"><strong>${v.establishment_name}</strong> — <code>${v.code}</code></div>
                            <div style="font-size:12.5px;color:var(--text-secondary);margin-top:4px;">
                                <span style="font-weight:700;color:${statusColor[v.status]};">${statusLabel[v.status]}</span>
                                · hodnota ${parseFloat(v.initial_value).toFixed(2)} € (zostáva ${parseFloat(v.remaining_value).toFixed(2)} €)${v.recipient_name ? ' · pre ' + v.recipient_name : ''}${v.valid_until ? ' · platí do ' + v.valid_until : ''}
                            </div>
                        </div>
                    </div>`).join('');
            } catch (err) {
                container.innerHTML = '<p style="color:#ef4444;">Darčekové poukazy sa nepodarilo načítať.</p>';
            }
        }

        async function loadLoyaltyProgress() {
            const container = document.getElementById('loyalty-container');
            try {
                const fd = new FormData(); fd.append('action', 'list_my_progress');
                const res = await fetch('api/loyalty.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.success || !data.progress || data.progress.length === 0) {
                    container.innerHTML = '<p>Zatiaľ nezbierate odmeny v žiadnej prevádzke. Navštívte prevádzku so zapnutým vernostným programom a odmeny sa začnú počítať automaticky.</p>';
                    return;
                }
                container.innerHTML = data.progress.map(p => {
                    const isSpend = p.reward_type === 'spend';
                    const current = isSpend ? parseFloat(p.spend_total) : parseInt(p.visits_count);
                    const threshold = parseFloat(p.reward_threshold);
                    const progressInCycle = current % threshold;
                    const pct = Math.min(100, Math.round((progressInCycle / threshold) * 100));
                    const unit = isSpend ? '€' : 'návštev';
                    return `<div class="booking-card">
                        <div class="booking-info" style="width:100%;">
                            <div class="booking-date"><strong>${p.establishment_name}</strong></div>
                            <div style="font-size:12.5px;color:var(--text-secondary);margin:6px 0;">${p.reward_description}</div>
                            <div style="background:var(--input-bg);border-radius:8px;height:8px;overflow:hidden;max-width:280px;">
                                <div style="background:var(--primary-color);height:100%;width:${pct}%;"></div>
                            </div>
                            <div style="font-size:11.5px;color:var(--text-secondary);margin-top:4px;">${progressInCycle.toFixed(isSpend?2:0)} / ${threshold} ${unit} do ďalšej odmeny${p.rewards_available > 0 ? ' · <strong style="color:#10b981;">' + p.rewards_available + '× odmena pripravená — vyzdvihnite si ju priamo v prevádzke!</strong>' : ''}</div>
                        </div>
                    </div>`;
                }).join('');
            } catch (err) {
                container.innerHTML = '<p style="color:#ef4444;">Vernostný program sa nepodarilo načítať.</p>';
            }
        }

        async function loadFavorites() {
            try {
                const fd = new FormData(); fd.append('action', 'get_favorites');
                let res = await fetch('api/customer.php', { method: 'POST', body: fd });
                let data = await res.json();
                let grid = document.getElementById('favorites-grid');
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(b => {
                        let img = b.image_url ? b.image_url : 'assets/img/placeholder.jpg';
                        html += `
                        <div class="fav-card">
                            <img src="${img}" class="fav-img">
                            <div class="fav-content">
                                <h3>${b.name}</h3>
                                <p style="margin: 0; color: var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle;">location_on</span> ${b.city}, ${b.address}</p>
                                <div class="fav-actions">
                                    <a href="business_page.php?id=${b.id}" class="btn" style="padding: 8px 15px; text-decoration:none;">Rezervovať</a>
                                    <button class="btn btn-danger" onclick="removeFavorite(${b.id})" style="padding: 8px;" title="Odstrániť"><span class="material-symbols-outlined">delete</span></button>
                                </div>
                            </div>
                        </div>`;
                    });
                    grid.innerHTML = html;
                } else {
                    grid.innerHTML = `<p>Nemáš uložené žiadne prevádzky.</p>`;
                }
            } catch(e) {}
        }
        
        async function removeFavorite(id) {
            const fd = new FormData(); fd.append('action', 'toggle_favorite'); fd.append('establishment_id', id);
            await fetch('api/customer.php', { method: 'POST', body: fd });
            loadFavorites();
        }

        async function loadReviews() {
            try {
                const fd = new FormData(); fd.append('action', 'get_reviews');
                let res = await fetch('api/customer.php', { method: 'POST', body: fd });
                let data = await res.json();
                let cont = document.getElementById('reviews-container');
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(r => {
                        let stars = '';
                        for(let i=0; i<r.rating; i++) stars += '<span class="material-symbols-outlined" style="font-size:18px;">star</span>';
                        for(let i=r.rating; i<5; i++) stars += '<span class="material-symbols-outlined" style="font-size:18px; color:var(--border-color);">star</span>';
                        
                        html += `
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <h3 class="review-title">${r.salon_name}</h3>
                                    <div class="review-stars">${stars}</div>
                                </div>
                                <div class="review-date">${r.created_at}</div>
                            </div>
                            <div class="review-text">"${r.review_text}"</div>
                        </div>`;
                    });
                    cont.innerHTML = html;
                } else {
                    cont.innerHTML = `<p>Zatiaľ si nepridal žiadnu recenziu.</p>`;
                }
            } catch(e) {}
        }
        
        function openReviewModal(bookingId, estId, salonName) {
            document.getElementById('review-booking-id').value = bookingId;
            document.getElementById('review-est-id').value = estId;
            document.getElementById('review-salon-name').innerText = "Hodnotiť " + salonName;
            document.getElementById('review-text').value = '';
            setRating(5);
            document.getElementById('review-modal').style.display = 'flex';
        }
        function closeReviewModal() {
            document.getElementById('review-modal').style.display = 'none';
        }
        function setRating(r) {
            document.getElementById('review-rating').value = r;
            let spans = document.getElementById('star-rating').querySelectorAll('span');
            spans.forEach((sp, idx) => {
                if(idx < r) sp.style.fontVariationSettings = "'FILL' 1"; // filled star
                else sp.style.fontVariationSettings = "'FILL' 0"; // empty star
            });
        }
        
        async function submitReview() {
            const fd = new FormData();
            fd.append('action', 'add_review');
            fd.append('booking_id', document.getElementById('review-booking-id').value);
            fd.append('establishment_id', document.getElementById('review-est-id').value);
            fd.append('rating', document.getElementById('review-rating').value);
            fd.append('review_text', document.getElementById('review-text').value);
            const categories = {};
            ['kvalita','komunikacia','pristup','prostredie'].forEach(cat => {
                const v = document.getElementById('review-cat-' + cat).value;
                if (v) categories[cat] = parseInt(v);
            });
            if (Object.keys(categories).length > 0) fd.append('category_ratings', JSON.stringify(categories));
            try {
                let res = await fetch('api/customer.php', { method: 'POST', body: fd });
                let data = await res.json();
                if(data.success) {
                    showToast('Ďakujeme za hodnotenie!');
                    closeReviewModal();
                    loadReviews();
                } else {
                    showToast(data.message || 'Chyba.', 'error');
                }
            } catch(e) {}
        }

        async function loadProfile() {
            // Fetch profile data (phone) from API
            try {
                const fd = new FormData();
                fd.append('action', 'get_profile');
                let res = await fetch('api/customer.php', { method: 'POST', body: fd });
                let data = await res.json();
                if(data.success && data.data) {
                    document.getElementById('profile-phone').value = data.data.phone || '';
                    const genderEl = document.getElementById('profile-gender');
                    if (genderEl) { genderEl.value = data.data.gender || ''; }
                    const bd = document.getElementById('profile-birthdate');
                    const bdHint = document.getElementById('profile-birthdate-hint');
                    if (bd) {
                        bd.value = data.data.birth_date || '';
                        if (data.data.birth_date_locked) {
                            bd.disabled = true;
                            if (bdHint) {
                                bdHint.textContent = 'Dátum narodenia je možné zmeniť len raz za rok — ďalšia zmena bude možná až ' + data.data.birth_date_locked_until.split('-').reverse().join('.') + '.';
                                bdHint.style.color = '#b45309';
                            }
                        } else {
                            bd.disabled = false;
                            if (bdHint && data.data.birth_date_grace_until) {
                                const graceTime = new Date(data.data.birth_date_grace_until.replace(' ', 'T')).toLocaleString('sk-SK');
                                bdHint.textContent = 'Práve uložený dátum môžete v prípade preklepu ešte opraviť do ' + graceTime + ', potom sa zamkne na rok.';
                                bdHint.style.color = '#b45309';
                            } else if (bdHint) {
                                bdHint.textContent = 'Vďaka tomu vám prevádzky môžu poslať narodeninové prianie so zľavou. Zmeniť ho môžete len raz za rok.';
                                bdHint.style.color = 'var(--text-secondary)';
                            }
                        }
                    }
                }
            } catch(e) {}
        }

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('avatar-preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    let placeholder = document.getElementById('avatar-placeholder');
                    if(placeholder) placeholder.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function saveProfile() {
            const name = document.getElementById('profile-name').value;
            const phone = document.getElementById('profile-phone').value;
            const whatsapp = document.getElementById('profile-whatsapp').value;
            const birthdate = document.getElementById('profile-birthdate')?.value || '';
            const gender = document.getElementById('profile-gender')?.value || '';
            const avatarFile = document.getElementById('profile-avatar').files[0];
            const msg = document.getElementById('profile-msg');

            const fd = new FormData();
            fd.append('action', 'update_profile');
            fd.append('full_name', name);
            fd.append('phone', phone);
            fd.append('whatsapp', whatsapp);
            fd.append('birth_date', birthdate);
            fd.append('gender', gender);
            if(avatarFile) fd.append('avatar', avatarFile);

            msg.style.color = "var(--text-secondary)";
            msg.innerText = "Ukladám...";

            try {
                let res = await fetch('api/customer.php', { method: 'POST', body: fd });
                let data = await res.json();
                if(data.success) {
                    msg.style.color = "#2ecc71";
                    msg.innerText = "Zmeny boli úspešne uložené.";
                    loadProfile();
                } else {
                    msg.style.color = "#e74c3c";
                    msg.innerText = data.message || "Chyba pri ukladaní.";
                }
            } catch(e) {
                msg.style.color = "#e74c3c";
                msg.innerText = "Nastala chyba na serveri.";
            }
        }

        // Initialize
        (function () {
            const params = new URLSearchParams(window.location.search);
            const wanted = params.get('section');
            const validSections = ['dashboard', 'bookings', 'hunter', 'favorites', 'reviews', 'settings', 'security', 'memberships', 'giftvouchers', 'loyalty', 'newsletters'];
            if (wanted && validSections.includes(wanted)) {
                showSection(wanted);
                // Vyčistíme URL, aby ?section= nezostal viditeľný a nekomplikoval prípadný refresh.
                history.replaceState(null, '', 'moj_profil.php');
            } else {
                loadDashboard();
            }
        })();
    </script>
    <!-- Custom Prompt Modal for 2FA Code -->
    <div id="prompt-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10002; justify-content:center; align-items:center;">
        <div style="background:var(--card-bg); padding:30px; border-radius:12px; width:100%; max-width:400px; text-align:center;">
            <h2 style="margin-top:0;">Potvrdenie zmeny hesla</h2>
            <p style="margin-bottom:20px; font-size:14px; color:var(--text-secondary);">Na Váš e-mail bol zaslaný 6-miestny overovací kód. Zadajte ho pre potvrdenie zmeny hesla:</p>
            <input type="text" id="prompt-input" class="form-control" placeholder="6-miestny kód" style="letter-spacing:5px; text-align:center; font-weight:bold; font-size:18px; margin-bottom:20px;" maxlength="6">
            <div style="display:flex; gap:10px; justify-content:center;">
                <button type="button" class="btn" style="background:#555;" onclick="closePromptModal()">Zrušiť</button>
                <button type="button" class="btn" style="background:#000; color:white;" onclick="submitPromptModal()">Potvrdiť</button>
            </div>
        </div>
    </div>

    <script>
        // Security API JS
        async function loadSecuritySettings() {
            try {
                const fd = new FormData();
                fd.append('action', 'get_security_settings');
                let res = await fetch('api/profile_security.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success && data.settings) {
                    const s = data.settings;
                    const toggle2 = document.getElementById('toggle-2fa');
                    if (toggle2) toggle2.checked = !!s.two_factor_enabled;

                    const toggle3 = document.getElementById('toggle-3fa-questions');
                    if (toggle3) toggle3.checked = !!s.two_factor_questions;

                    if (document.getElementById('sq-q1')) document.getElementById('sq-q1').value = s.security_q1 || '';
                    if (document.getElementById('sq-a1')) document.getElementById('sq-a1').value = s.security_a1 || '';
                    if (document.getElementById('sq-q2')) document.getElementById('sq-q2').value = s.security_q2 || '';
                    if (document.getElementById('sq-a2')) document.getElementById('sq-a2').value = s.security_a2 || '';
                    if (document.getElementById('sq-q3')) document.getElementById('sq-q3').value = s.security_q3 || '';
                    if (document.getElementById('sq-a3')) document.getElementById('sq-a3').value = s.security_a3 || '';
                }
            } catch(e) {
                console.error('Chyba načítania bezpečnostných nastavení:', e);
            }
        }

        async function toggle2FA(enabled) {
            const fd = new FormData();
            fd.append('action', 'toggle_2fa');
            fd.append('enabled', enabled ? 1 : 0);
            
            try {
                let res = await fetch('api/profile_security.php', { method: 'POST', body: fd });
                let data = await res.json();
                if(data.success) {
                    showToast(data.message || 'Nastavenie bolo uložené.', 'success');
                } else {
                    showToast(data.error || 'Chyba', 'error');
                    document.getElementById('toggle-2fa').checked = !enabled; // revert
                }
            } catch(e) { 
                showToast('Chyba siete', 'error'); 
                document.getElementById('toggle-2fa').checked = !enabled;
            }
        }

        function toggleSecurityQuestionsSetup(show) {
            const box = document.getElementById('security-questions-setup');
            if (!box) return;
            if (show === undefined) {
                box.style.display = (box.style.display === 'none' || !box.style.display) ? 'block' : 'none';
            } else {
                box.style.display = show ? 'block' : 'none';
            }
            if (box.style.display === 'block') {
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        async function toggle3FAQuestions(enabled) {
            const fd = new FormData();
            fd.append('action', 'toggle_2fa_questions');
            fd.append('enabled', enabled ? 1 : 0);
            try {
                let res = await fetch('api/profile_security.php', { method: 'POST', body: fd });
                let data = await res.json();
                if(data.success) { 
                    showToast(data.message || 'Nastavenie bolo uložené.', 'success'); 
                } else { 
                    showToast(data.error || 'Chyba', 'error'); 
                    document.getElementById('toggle-3fa-questions').checked = false;
                    toggleSecurityQuestionsSetup(true);
                }
            } catch(e) { 
                showToast('Chyba siete', 'error'); 
                document.getElementById('toggle-3fa-questions').checked = !enabled; 
            }
        }

        async function saveSecurityQuestions(e) {
            e.preventDefault();
            const btn = document.getElementById('save-questions-btn');
            const statusMsg = document.getElementById('questions-status-msg');
            
            const q1 = document.getElementById('sq-q1').value.trim();
            const a1 = document.getElementById('sq-a1').value.trim();
            const q2 = document.getElementById('sq-q2').value.trim();
            const a2 = document.getElementById('sq-a2').value.trim();
            const q3 = document.getElementById('sq-q3').value.trim();
            const a3 = document.getElementById('sq-a3').value.trim();

            if (!q1 || !a1 || !q2 || !a2 || !q3 || !a3) {
                showToast('Všetky 3 otázky aj odpovede musia byť vyplnené.', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'save_security_questions');
            fd.append('q1', q1);
            fd.append('a1', a1);
            fd.append('q2', q2);
            fd.append('a2', a2);
            fd.append('q3', q3);
            fd.append('a3', a3);
            fd.append('auto_enable', '1');

            if (btn) btn.disabled = true;

            try {
                let res = await fetch('api/profile_security.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Bezpečnostné otázky boli úspešne uložené!', 'success');
                    const toggle3 = document.getElementById('toggle-3fa-questions');
                    if (toggle3) toggle3.checked = true;
                    if (statusMsg) {
                        statusMsg.textContent = 'Otázky sú aktívne uložené';
                        setTimeout(() => { statusMsg.textContent = ''; }, 4000);
                    }
                    setTimeout(() => { toggleSecurityQuestionsSetup(false); }, 1200);
                } else {
                    showToast(data.error || 'Chyba pri ukladaní otázok.', 'error');
                }
            } catch(err) {
                showToast('Chyba pripojenia k serveru.', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
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
            if(val.length >= 5) {
                document.getElementById('prompt-modal').style.display = 'none';
                if(promptResolve) promptResolve(val);
            } else {
                showToast('Zadajte platný kód', 'error');
            }
        }

        async function changePassword(e) {
            e.preventDefault();
            const current = document.getElementById('current_password').value;
            const newp = document.getElementById('new_password').value;
            const conf = document.getElementById('confirm_password').value;
            
            if (newp !== conf) {
                showToast('Nové heslá sa nezhodujú', 'error');
                return;
            }
            if (newp.length < 6) {
                showToast('Nové heslo musí mať aspoň 6 znakov', 'error');
                return;
            }
            
            // Opýtame si 2FA kód (vygenerujeme a pošleme email)
            const fd_req = new FormData();
            fd_req.append('action', 'request_password_change');
            let res_req = await fetch('api/profile_security.php', { method: 'POST', body: fd_req });
            let data_req = await res_req.json();
            
            if (data_req.success) {
                let code = await openPromptModal();
                if (code) {
                    const fd = new FormData();
                    fd.append('action', 'change_password');
                    fd.append('current_password', current);
                    fd.append('new_password', newp);
                    fd.append('code', code);
                    
                    let res = await fetch('api/profile_security.php', { method: 'POST', body: fd });
                    let data = await res.json();
                    
                    if (data.success) {
                        showToast('Heslo bolo úspešne zmenené', 'success');
                        e.target.reset();
                    } else {
                        showToast(data.error || 'Chyba pri zmene hesla', 'error');
                    }
                }
            } else {
                showToast(data_req.error || 'Chyba pri vyžiadaní kódu', 'error');
            }
        }
    
        async function payVerificationFee() {
            if (await confirmModal('Budete presmerovaní na platobnú bránu pre úhradu 4,90 €.\n(Toto je ukážková simulácia platby)')) {
                // Simulácia API callu na uloženie card_verified
                const fd = new FormData();
                fd.append('action', 'simulate_payment');
                let res = await fetch('api/profile.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    showToast('Platba prebehla úspešne! Teraz ste overený zákazník.');
                    location.reload();
                } else {
                    showToast('Chyba pri platbe.', 'error');
                }
            }
        }
    </script>
</body>
</html>
