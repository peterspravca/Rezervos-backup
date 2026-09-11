<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
$stmt = $conn->prepare("SELECT two_factor_enabled FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res_db = $stmt->get_result()->fetch_assoc();
$two_factor_enabled = !empty($res_db['two_factor_enabled']);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrácia - <?= BRAND_NAME ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <!-- Zdieľaný sidebar štýl (rovnaký ako Prevádzka a Správa inzerátov) — musí byť PRED vlastným <style>,
         aby lokálne pravidlá nižšie (tabuľky, karty...) zostali nadradené, ale sidebar bol pixel-identický. -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=3.4">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <script src="assets/js/sidebar-hover-fix.js?v=1.1" defer></script>
    <script src="assets/js/theme.js?v=2.0"></script>
    <script src="assets/js/dashboard-common.js?v=3.1" defer></script>
    <style>
        :root {
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --primary-color: #b08042;
            --primary-hover: #c49658;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            /* --sidebar-bg / --sidebar-hover úmyselne NEdefinované tu — sidebar teraz preberá
               farby zo zdieľaného assets/css/dashboard.css, aby bol vždy pixel-identický s Prevádzkou. */
        }

        body.dark-mode {
            --bg-color: #0d0f12;
            --card-bg: rgba(20, 24, 28, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #a0a5ab;
            --shadow-sm: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar — teraz úplne zdieľaný z assets/css/dashboard.css (rovnaký ako Prevádzka),
           žiadne vlastné pravidlá tu nemajú byť, aby sa vzhľad nikdy znova nerozišiel. */

        /* Main Content */
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .admin-header {
            height: 80px;
            flex-shrink: 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .admin-header-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            line-height: 30px;
            height: 30px;
            display: flex;
            align-items: center;
        }

        .admin-header-right {
            display: flex;
            align-items: center;
            gap: 15px;
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
            transition: background 0.3s;
        }
        .theme-btn:hover {
            background: var(--border-color);
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-content {
            flex: 1 1 0;
            min-height: 0;
            height: 0;
            padding: 30px;
            overflow-y: auto;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-sm);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(176, 128, 66, 0.1);
            color: var(--primary-color);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon span {
            font-size: 28px;
        }

        .stat-info h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-info p {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .admin-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }
        .admin-panel h2:first-child {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .admin-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .admin-panel-header h2 {
            margin: 0;
            font-size: 18px;
        }
        
        .data-card {
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
        .data-card:hover {
            border-color: var(--primary-color);
        }
        .data-info {
            flex: 1;
            min-width: 250px;
        }
        .data-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: var(--text-primary);
        }
        .data-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0 0 5px 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .data-detail {
            font-size: 14px;
            color: var(--text-primary);
            margin: 0;
        }
        .data-actions {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 15px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
        }

        td {
            font-size: 15px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: rgba(39, 174, 96, 0.15);
            color: #2ecc71;
        }
        
        .status-pending {
            background: rgba(243, 156, 18, 0.15);
            color: #f1c40f;
        }
        
        .status-blocked {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }

        .btn-action {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            transition: 0.2s;
            padding: 5px;
        }

        .btn-action:hover {
            color: var(--primary-color);
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }
        .modal-header h2 { margin: 0; font-size: 22px; color: var(--text-primary); }
        .close-modal { cursor: pointer; color: var(--text-secondary); transition: 0.3s; }
        .close-modal:hover { color: #fff; }
        .biz-detail { margin-bottom: 15px; }
        .biz-detail strong { color: var(--primary-color); display: block; margin-bottom: 5px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .biz-detail p { margin: 0; color: var(--text-primary); font-size: 16px; line-height: 1.5; }
        
        body.dark-mode .sidebar-logo {
            filter: invert(1) hue-rotate(180deg) !important;
        }
        .admin-search {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-primary);
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
            outline: none;
            transition: 0.3s;
            font-family: inherit;
        }
        .admin-search:focus {
            border-color: var(--primary-color);
        }
    
.admin-user-sidebar span.menu-text {
    opacity: 0;
    transition: opacity 0.2s;
    display: inline-block;
}
.admin-sidebar:hover .admin-user-sidebar span.menu-text {
    opacity: 1;
}

        /* Security switch */
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; }
        input:checked + .slider { background-color: var(--primary-color); }
        input:focus + .slider { box-shadow: 0 0 1px var(--primary-color); }
        input:checked + .slider:before { transform: translateX(24px); }
        .slider.round { border-radius: 34px; }
        .slider.round:before { border-radius: 50%; }
        
        #toast { visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 1000; left: 50%; bottom: 30px; transform: translateX(-50%); font-size: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        #toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
        @keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
        
    </style>
</head>
<body>

    <?php $active_nav = 'dashboard'; $spa_host = true; require_once 'includes/admin-sidebar.php'; ?>

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
<a href="index.php" class="theme-btn" style="display: flex; align-items: center; justify-content: center; text-decoration: none;" title="Späť na domovskú stránku">
<span class="material-symbols-outlined">home</span>
</a>
<button id="theme-toggle" class="theme-btn" aria-label="Toggle Dark Mode">
<span class="material-symbols-outlined">dark_mode</span>
</button>

</div>
</header>

        <div class="admin-content">
            
            <!-- Global Stats Grid (Visible on all tabs) -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">storefront</span></div>
                    <div class="stat-info">
                        <h3>Aktívne prevádzky</h3>
                        <p id="stat-active">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">group</span></div>
                    <div class="stat-info">
                        <h3>Registrovaní zákazníci</h3>
                        <p id="stat-customers">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">event_available</span></div>
                    <div class="stat-info">
                        <h3>Rezervácie (Dnes)</h3>
                        <p id="stat-bookings">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color: #f1c40f;"><span class="material-symbols-outlined">pending_actions</span></div>
                    <div class="stat-info">
                        <h3>Čakajú na schválenie</h3>
                        <p id="stat-pending">0</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Section -->
            <div id="sec-dashboard" class="section active">
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h2>Nové prevádzky čakajúce na schválenie</h2>
                    </div>
                    <div id="pending-container">
                        <p>Načítavam...</p>
                    </div>
                </div>
            </div>

            <!-- Users Section -->
            <div id="sec-users" class="section">
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h2>Všetci používatelia</h2>
                        <input type="text" id="search-users" class="admin-search" placeholder="Hľadať používateľa..." onkeyup="filterUsers()">
                    </div>
                    <div id="users-container">
                        <p>Načítavam...</p>
                    </div>
                </div>
            </div>

            <!-- Businesses Section -->
            <div id="sec-businesses" class="section">
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h2>Všetky prevádzky</h2>
                        <input type="text" id="search-businesses" class="admin-search" placeholder="Hľadať prevádzku..." onkeyup="filterBusinesses()">
                    </div>
                    <div id="all-businesses-container">
                        <p>Načítavam...</p>
                    </div>
                </div>
            </div>

            <div id="sec-inzercia" class="section">
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h2>Všetky inzeráty</h2>
                        <input type="text" id="search-inzercia" class="admin-search" placeholder="Hľadať inzerát..." onkeyup="filterClassifieds()">
                    </div>
                    <div id="all-classifieds-container">
                        <p>Načítavam...</p>
                    </div>
                </div>
            </div>
        <!-- Security Section -->
        <div id="sec-security" class="section">
            <div class="admin-panel" style="max-width: 600px;">
                <h2>Zabezpečenie účtu</h2>
                <div style="background: var(--bg-color); padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="display: block; font-size: 16px;">Dvojfázové overenie (2FA)</strong>
                        <span style="font-size: 13px; color: var(--text-secondary);">Zvýšte bezpečnosť prihlasovania vyžadovaním kódu z e-mailu.</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="toggle-2fa" <?= $two_factor_enabled ? 'checked' : '' ?> onchange="toggle2FA(this.checked)">
                        <span class="slider round"></span>
                    </label>
                </div>
                <h3 style="margin-top:30px;">Zmena hesla</h3>
                <form id="password-form" onsubmit="changePassword(event)">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block;margin-bottom:5px;">Súčasné heslo</label>
                        <input type="password" class="form-control" id="current_password" required style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block;margin-bottom:5px;">Nové heslo</label>
                        <input type="password" class="form-control" id="new_password" required minlength="6" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block;margin-bottom:5px;">Zopakujte nové heslo</label>
                        <input type="password" class="form-control" id="confirm_password" required minlength="6" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);">
                    </div>
                    <button type="submit" class="btn" style="background:var(--primary-color);color:#fff;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;">Zmeniť heslo</button>
                </form>
            </div>
        </div>

        </div>
    </main>

<script>
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
        } else if (sec === 'users') {
            document.getElementById('page-title').innerText = 'Používatelia';
            loadUsers();
        } else if (sec === 'businesses') {
            document.getElementById('page-title').innerText = 'Prevádzky';
            loadBusinesses();
        } else if (sec === 'inzercia') {
            document.getElementById('page-title').innerText = 'Správa inzerátov';
            loadAllClassifieds();
        } else if (sec === 'security') {
            document.getElementById('page-title').innerText = 'Zabezpečenie';
        }

        if (window.innerWidth <= 768 && typeof closeMobileSidebar === 'function') {
            closeMobileSidebar();
        }
    }

    let pendingBusinessesData = [];

    async function loadDashboard() {
        try {
            const fd = new FormData(); fd.append('action', 'get_stats');
            let res = await fetch('api/admin.php', { method: 'POST', body: fd });
            let data = await res.json();
            if (data.success) {
                document.getElementById('stat-active').innerText = data.stats.active_businesses;
                document.getElementById('stat-customers').innerText = data.stats.customers;
                document.getElementById('stat-bookings').innerText = data.stats.bookings_today;
                document.getElementById('stat-pending').innerText = data.stats.pending_businesses;
            }

            const fd2 = new FormData(); fd2.append('action', 'get_pending_businesses');
            let res2 = await fetch('api/admin.php', { method: 'POST', body: fd2 });
            let data2 = await res2.json();
            if (data2.success) {
                pendingBusinessesData = data2.data;
                let html = '';
                if (data2.data.length === 0) {
                    html = '<p style="color:var(--text-secondary);">Žiadne prevádzky nečakajú na schválenie.</p>';
                } else {
                    data2.data.forEach(b => {
                        html += `
                        <div class="data-card" data-search="${b.name.toLowerCase()} ${b.owner_name.toLowerCase()}">
                            <div class="data-info">
                                <h3 class="data-title">${b.name}</h3>
                                <div class="data-subtitle">
                                    <span class="material-symbols-outlined" style="font-size:16px;">person</span> ${b.owner_name}
                                </div>
                                <p class="data-detail">
                                    <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">calendar_today</span> 
                                    Registrované: ${b.created_at}
                                </p>
                            </div>
                            <div class="data-actions">
                                <span class="status-badge status-pending">Čaká na schválenie</span>
                                <div class="action-buttons">
                                    <button class="btn-action" title="Zobraziť detail" onclick="viewBusiness(${b.id})"><span class="material-symbols-outlined" style="color:var(--text-secondary);">visibility</span></button>
                                    <button class="btn-action" title="Schváliť" onclick="approveBusiness(${b.id})"><span class="material-symbols-outlined" style="color:#2ecc71;">check_circle</span></button>
                                    <button class="btn-action" title="Zamietnuť/Blokovať" onclick="rejectBusiness(${b.id})"><span class="material-symbols-outlined" style="color:#e74c3c;">block</span></button>
                                </div>
                            </div>
                        </div>`;
                    });
                }
                document.getElementById('pending-container').innerHTML = html;
            }
        } catch(e) { console.error(e); }
    }

    async function deleteUser(id) {
        customConfirm('Naozaj chcete vymazať tohto používateľa? Akcia je nevratná a zmaže aj všetky jeho dáta.', async () => {
            try {
                const fd = new FormData(); 
                fd.append('action', 'delete_user'); 
                fd.append('id', id);
                let res = await fetch('api/admin.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    loadUsers();
                } else {
                    customConfirm(data.error || 'Chyba pri mazaní');
                }
            } catch(e) { console.error(e); }
        });
    }

    function getTierBadgeHtml(tier) {
        tier = (tier || 'free').toLowerCase();
        if (tier === 'vip') {
            return `<span style="background: rgba(176, 128, 66, 0.15); color: #d4af37; border: 1px solid rgba(176, 128, 66, 0.35); font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 14px;">workspace_premium</span> VIP (ELITE)</span>`;
        } else if (tier === 'pro') {
            return `<span style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.35); font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 14px;">group</span> PRO</span>`;
        } else if (tier === 'start') {
            return `<span style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.35); font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 14px;">rocket_launch</span> START</span>`;
        } else {
            return `<span style="background: rgba(100, 116, 139, 0.15); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.3); font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 14px;">eco</span> FREE</span>`;
        }
    }

    async function loadUsers() {
        try {
            const fd = new FormData(); fd.append('action', 'get_users');
            let res = await fetch('api/admin.php', { method: 'POST', body: fd });
            let data = await res.json();
            if (data.success) {
                let html = '';
                if(data.data.length === 0) {
                    html = '<p style="color:var(--text-secondary);">Nenašli sa žiadni používatelia.</p>';
                } else {
                    data.data.forEach(u => {
                        let statusClass = u.status === 'active' ? 'status-active' : 'status-blocked';
                        let statusText = u.status === 'active' ? 'Aktívny' : 'Blokovaný';
                        let btnIcon = u.status === 'active' ? 'block' : 'lock_open_right';
                        let btnColor = u.status === 'active' ? '#e74c3c' : '#2ecc71';
                        let actionsHtml = '';
                        if (u.role === 'admin') {
                            actionsHtml = `
                                <div class="action-buttons">
                                    <button class="btn-action" title="Spravovať balík a peňaženku" onclick="openTierWalletModal(${u.establishment_id || 0}, ${u.id}, '${escapeJsString(u.full_name)}', '${u.subscription_tier || 'free'}', ${parseFloat(u.credit)||0}, ${parseInt(u.sms_credits)||0}, ${u.sponsored_expires_at ? "'" + u.sponsored_expires_at + "'" : 'null'})"><span class="material-symbols-outlined" style="color:var(--primary-color);">account_balance_wallet</span></button>
                                    <span style="color:var(--text-muted); font-size: 12px; font-weight: bold;">(Chránený účet)</span>
                                </div>
                            `;
                        } else {
                            actionsHtml = `
                                <div class="action-buttons">
                                    <button class="btn-action" title="Spravovať balík a peňaženku" onclick="openTierWalletModal(${u.establishment_id || 0}, ${u.id}, '${escapeJsString(u.full_name)}', '${u.subscription_tier || 'free'}', ${parseFloat(u.credit)||0}, ${parseInt(u.sms_credits)||0}, ${u.sponsored_expires_at ? "'" + u.sponsored_expires_at + "'" : 'null'})"><span class="material-symbols-outlined" style="color:var(--primary-color);">account_balance_wallet</span></button>
                                    <button class="btn-action" title="Zmeniť status" onclick="toggleUser(${u.id})"><span class="material-symbols-outlined" style="color:${btnColor};">${btnIcon}</span></button>
                                    <button class="btn-action" title="Vymazať" onclick="deleteUser(${u.id})"><span class="material-symbols-outlined" style="color:#e74c3c;">delete</span></button>
                                </div>
                            `;
                        }

                        let tierBadge = getTierBadgeHtml(u.subscription_tier);
                        let creditVal = (parseFloat(u.credit) || 0).toFixed(2);
                        let walletBadge = `<span style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 14px;">account_balance_wallet</span> ${creditVal} €</span>`;

                        html += `
                        <div class="data-card user-card" data-search="${u.full_name.toLowerCase()} ${u.email.toLowerCase()}">
                            <div class="data-info">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                                    <h3 class="data-title" style="margin: 0;">${u.full_name} <span style="color:var(--text-secondary); font-size:13px; font-weight:normal;">#${u.id}</span></h3>
                                    ${tierBadge}
                                    ${walletBadge}
                                </div>
                                <div class="data-subtitle">
                                    <span class="material-symbols-outlined" style="font-size:16px;">mail</span> ${u.email}
                                    ${u.establishment_name ? `<span style="margin:0 5px;">&bull;</span> <span class="material-symbols-outlined" style="font-size:16px;">storefront</span> ${u.establishment_name}` : ''}
                                </div>
                                <p class="data-detail">
                                    Rola: <strong>${u.role}</strong> &bull; SMS: <strong>${u.sms_credits || 0}</strong>
                                </p>
                            </div>
                            <div class="data-actions">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                                ${actionsHtml}
                            </div>
                        </div>`;
                    });
                }
                document.getElementById('users-container').innerHTML = html;
            }
        } catch(e) { console.error(e); }
    }

    let allBusinessesData = [];

    async function loadBusinesses() {
        try {
            const fd = new FormData(); fd.append('action', 'get_all_businesses');
            let res = await fetch('api/admin.php', { method: 'POST', body: fd });
            let data = await res.json();
            if (data.success) {
                allBusinessesData = data.data;
                let html = '';
                if (data.data.length === 0) {
                    html = '<p style="color:var(--text-secondary);">Zatiaľ nie sú zaregistrované žiadne prevádzky.</p>';
                } else {
                    data.data.forEach(b => {
                        let statusClass = 'status-pending';
                        let statusText = 'Čaká na schválenie';
                        let btnIcon = 'check_circle';
                        let btnColor = '#2ecc71';
                        let btnAction = `approveBusiness(${b.id}, true)`;
                        let btnTitle = 'Schváliť';

                        if (b.status === 'active') {
                            statusClass = 'status-active';
                            statusText = 'Aktívna';
                            btnIcon = 'block';
                            btnColor = '#e74c3c';
                            btnAction = `rejectBusiness(${b.id}, true)`;
                            btnTitle = 'Zablokovať';
                        } else if (b.status === 'blocked') {
                            statusClass = 'status-blocked';
                            statusText = 'Zablokovaná';
                            btnIcon = 'check_circle';
                            btnColor = '#2ecc71';
                            btnAction = `approveBusiness(${b.id}, true)`;
                            btnTitle = 'Odblokovať/Schváliť';
                        }

                        let tierBadge = getTierBadgeHtml(b.subscription_tier);
                        let creditVal = (parseFloat(b.credit) || 0).toFixed(2);
                        let walletBadge = `<span style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 14px;">account_balance_wallet</span> ${creditVal} €</span>`;

                        html += `
                        <div class="data-card biz-card" data-search="${b.name.toLowerCase()} ${b.owner_name.toLowerCase()} ${b.city ? b.city.toLowerCase() : ''}">
                            <div class="data-info">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                                    <h3 class="data-title" style="margin: 0;">${b.name}</h3>
                                    ${tierBadge}
                                    ${walletBadge}
                                </div>
                                <div class="data-subtitle">
                                    <span class="material-symbols-outlined" style="font-size:16px;">person</span> ${b.owner_name}
                                    <span style="margin:0 5px;">&bull;</span>
                                    <span class="material-symbols-outlined" style="font-size:16px;">location_on</span> ${b.city || '-'}
                                </div>
                                <p class="data-detail">
                                    <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">calendar_today</span> 
                                    Registrované: ${b.created_at} &bull; SMS: <strong>${b.sms_credits || 0}</strong>
                                </p>
                            </div>
                            <div class="data-actions">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                                <div class="action-buttons">
                                    <button class="btn-action" title="Spravovať balík a peňaženku" onclick="openTierWalletModal(${b.id}, ${b.user_id}, '${escapeJsString(b.name)}', '${b.subscription_tier || 'free'}', ${parseFloat(b.credit)||0}, ${parseInt(b.sms_credits)||0}, ${b.sponsored_expires_at ? "'" + b.sponsored_expires_at + "'" : 'null'})"><span class="material-symbols-outlined" style="color:var(--primary-color);">tune</span></button>
                                    <button class="btn-action" title="Zobraziť detail" onclick="viewBusiness(${b.id}, true)"><span class="material-symbols-outlined" style="color:var(--text-secondary);">visibility</span></button>
                                    <button class="btn-action" title="${btnTitle}" onclick="${btnAction}"><span class="material-symbols-outlined" style="color:${btnColor};">${btnIcon}</span></button>
                                    <button class="btn-action" title="Vymazať" onclick="deleteBusiness(${b.id})"><span class="material-symbols-outlined" style="color:#e74c3c;">delete</span></button>
                                </div>
                            </div>
                        </div>`;
                    });
                }
                document.getElementById('all-businesses-container').innerHTML = html;
            }
        } catch(e) { console.error(e); }
    }

    function escapeJsString(str) {
        if (!str) return '';
        return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    let confirmCallback = null;
    function customConfirm(msg, callback = null) {
        document.getElementById('confirm-msg').innerText = msg;
        confirmCallback = callback;
        document.getElementById('confirm-btn-cancel').style.display = callback ? 'inline-block' : 'none';
        document.getElementById('confirm-modal').style.display = 'flex';
    }

    function hideConfirm() {
        document.getElementById('confirm-modal').style.display = 'none';
        confirmCallback = null;
    }

    async function approveBusiness(id, fromAll = false) {
        customConfirm('Naozaj schváliť/odblokovať túto prevádzku?', async () => {
            const fd = new FormData(); fd.append('action', 'approve_business'); fd.append('id', id);
            await fetch('api/admin.php', { method: 'POST', body: fd });
            if (fromAll) loadBusinesses(); else loadDashboard();
        });
    }

    async function rejectBusiness(id, fromAll = false) {
        customConfirm('Naozaj zamietnuť/zablokovať túto prevádzku?', async () => {
            const fd = new FormData(); fd.append('action', 'reject_business'); fd.append('id', id);
            await fetch('api/admin.php', { method: 'POST', body: fd });
            if (fromAll) loadBusinesses(); else loadDashboard();
        });
    }

    async function deleteBusiness(id) {
        customConfirm('Naozaj vymazať túto prevádzku nenávratne?', async () => {
            const fd = new FormData(); fd.append('action', 'delete_business'); fd.append('id', id);
            await fetch('api/admin.php', { method: 'POST', body: fd });
            loadBusinesses();
            loadDashboard(); // Update stats
        });
    }

    function filterUsers() {
        let input = document.getElementById('search-users').value.toLowerCase();
        let cards = document.querySelectorAll('.user-card');
        cards.forEach(card => {
            let text = card.getAttribute('data-search');
            card.style.display = text.includes(input) ? '' : 'none';
        });
    }

    function filterBusinesses() {
        let input = document.getElementById('search-businesses').value.toLowerCase();
        let cards = document.querySelectorAll('.biz-card');
        cards.forEach(card => {
            let text = card.getAttribute('data-search');
            card.style.display = text.includes(input) ? '' : 'none';
        });
    }

    const classifiedTypeLabels = { work: 'Práca', rental: 'Prenájom', sale: 'Predaj', courses: 'Kurzy', other: 'Ostatné',
                                    people_seek: 'Práca', people_offer: 'Práca', equipment: 'Predaj', chair: 'Prenájom' };

    async function loadAllClassifieds() {
        const container = document.getElementById('all-classifieds-container');
        try {
            const fd = new FormData(); fd.append('action', 'get_all_classifieds');
            let res = await fetch('api/admin.php', { method: 'POST', body: fd });
            let data = await res.json();
            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<p style="color:var(--text-secondary);">Zatiaľ žiadne inzeráty.</p>';
                    return;
                }
                container.innerHTML = data.data.map(c => {
                    const active = parseInt(c.is_active) === 1;
                    const statusBadge = active
                        ? '<span style="font-size:11px;font-weight:700;background:rgba(16,185,129,0.12);color:#10b981;padding:2px 8px;border-radius:6px;">Aktívny</span>'
                        : '<span style="font-size:11px;font-weight:700;background:rgba(239,68,68,0.12);color:#ef4444;padding:2px 8px;border-radius:6px;">Neaktívny</span>';
                    const search = `${(c.title||'').toLowerCase()} ${(c.owner_name||'').toLowerCase()} ${(c.owner_email||'').toLowerCase()} ${(c.location||'').toLowerCase()}`;
                    return `
                    <div class="data-card" data-search="${escapeJsString(search)}">
                        <div class="data-info">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                                <h3 class="data-title" style="margin:0;">${escapeHtml(c.title || '—')} <span style="color:var(--text-secondary); font-size:13px; font-weight:normal;">#${c.id}</span></h3>
                                <span style="font-size:11px;font-weight:700;background:rgba(176,128,66,0.12);color:var(--primary-color);padding:2px 8px;border-radius:6px;">${classifiedTypeLabels[c.type] || c.type}</span>
                                ${statusBadge}
                            </div>
                            <div class="data-subtitle">
                                <span class="material-symbols-outlined" style="font-size:16px;">person</span> ${escapeHtml(c.owner_name || 'Neznámy')} (${escapeHtml(c.owner_email || '—')})
                                ${c.location ? `<span style="margin:0 5px;">&bull;</span> <span class="material-symbols-outlined" style="font-size:16px;">location_on</span> ${escapeHtml(c.location)}` : ''}
                                ${c.price ? `<span style="margin:0 5px;">&bull;</span> <strong>${parseFloat(c.price).toFixed(2)} €</strong>` : ''}
                            </div>
                        </div>
                        <div class="data-actions">
                            <button class="btn-action" title="Zmazať inzerát" onclick="deleteClassified(${c.id})"><span class="material-symbols-outlined" style="color:#e74c3c;">delete</span></button>
                        </div>
                    </div>`;
                }).join('');
            } else {
                container.innerHTML = '<p style="color:#e74c3c;">Chyba pri načítaní inzerátov.</p>';
            }
        } catch (e) {
            container.innerHTML = '<p style="color:#e74c3c;">Chyba pripojenia.</p>';
        }
    }

    function filterClassifieds() {
        let input = document.getElementById('search-inzercia').value.toLowerCase();
        let cards = document.querySelectorAll('#all-classifieds-container .data-card');
        cards.forEach(card => {
            let text = card.getAttribute('data-search');
            card.style.display = text.includes(input) ? '' : 'none';
        });
    }

    async function deleteClassified(id) {
        customConfirm('Naozaj natrvalo zmazať tento inzerát?', async () => {
            const fd = new FormData(); fd.append('action', 'delete_classified'); fd.append('id', id);
            let res = await fetch('api/admin.php', { method: 'POST', body: fd });
            let data = await res.json();
            if (data.success) {
                loadAllClassifieds();
            } else {
                customConfirm(data.error || 'Chyba pri mazaní inzerátu.');
            }
        });
    }

    async function toggleUser(id) {
        customConfirm('Naozaj zmeniť status tohto používateľa?', async () => {
            const fd = new FormData(); fd.append('action', 'toggle_user_status'); fd.append('id', id);
            await fetch('api/admin.php', { method: 'POST', body: fd });
            loadUsers();
        });
    }

    function viewBusiness(id, fromAll = false) {
        const list = fromAll ? allBusinessesData : pendingBusinessesData;
        const b = list.find(x => x.id == id);
        if(!b) return;
        document.getElementById('modal-biz-name').innerText = b.name || '-';
        document.getElementById('modal-biz-owner').innerText = b.owner_name || '-';
        document.getElementById('modal-biz-cat').innerText = b.category || '-';
        document.getElementById('modal-biz-address').innerText = (b.address ? b.address + ', ' : '') + (b.city || '-');
        document.getElementById('modal-biz-phone').innerText = b.phone || '-';
        document.getElementById('modal-biz-desc').innerText = b.description || '-';
        
        let ohHtml = '';
        if(b.opening_hours) {
            try {
                let oh = JSON.parse(b.opening_hours);
                const daysMap = {mon:'Po', tue:'Ut', wed:'St', thu:'Št', fri:'Pi', sat:'So', sun:'Ne'};
                for(let key in daysMap) {
                    let text = 'Zatvorené';
                    if(oh[key]) {
                        if(oh[key].open && oh[key].close) {
                            text = oh[key].open + ' - ' + oh[key].close;
                            if(oh[key].break_start && oh[key].break_end) {
                                text += ' (Prestávka: ' + oh[key].break_start + ' - ' + oh[key].break_end + ')';
                            }
                        }
                    }
                    ohHtml += '<div>' + daysMap[key] + ': ' + text + '</div>';
                }
            }catch(e){}
        }
        document.getElementById('modal-biz-oh').innerHTML = ohHtml || '-';
        
        document.getElementById('biz-modal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('biz-modal').style.display = 'none';
    }

    // --- TIER & WALLET MANAGEMENT MODAL LOGIC ---
    let g_currentCreditVal = 0;
    function openTierWalletModal(establishmentId, userId, name, currentTier, currentCredit, currentSms, sponsoredExpiresAt) {
        document.getElementById('tw-establishment-id').value = establishmentId || '';
        document.getElementById('tw-user-id').value = userId || '';
        document.getElementById('tw-target-name').innerText = name || 'Používateľ / Prevádzka';
        document.getElementById('tw-tier-select').value = (currentTier || 'free').toLowerCase();

        g_currentCreditVal = parseFloat(currentCredit) || 0;
        document.getElementById('tw-current-credit').innerText = g_currentCreditVal.toFixed(2);
        document.getElementById('tw-current-sms').innerText = parseInt(currentSms) || 0;
        updateSponsoredStatusUI(sponsoredExpiresAt || null);

        // Reset modes
        const radioAdd = document.querySelector('input[name="tw-credit-mode"][value="add"]');
        if (radioAdd) radioAdd.checked = true;
        document.getElementById('tw-credit-amount').value = '0.00';

        const radioSmsAdd = document.querySelector('input[name="tw-sms-mode"][value="add"]');
        if (radioSmsAdd) radioSmsAdd.checked = true;
        document.getElementById('tw-sms-amount').value = '0';

        updateCreditModeUI();
        document.getElementById('tier-wallet-modal').style.display = 'flex';
    }

    function updateSponsoredStatusUI(sponsoredExpiresAt) {
        const el = document.getElementById('tw-sponsored-status');
        if (!el) return;
        const isActive = sponsoredExpiresAt && new Date(sponsoredExpiresAt) > new Date();
        if (isActive) {
            const d = new Date(sponsoredExpiresAt);
            el.innerText = 'Aktívne do ' + d.toLocaleDateString('sk-SK');
            el.style.color = '#8b5cf6';
        } else {
            el.innerText = 'Neaktívne';
            el.style.color = 'var(--text-secondary)';
        }
    }

    async function grantSponsored(months) {
        const establishment_id = document.getElementById('tw-establishment-id').value;
        if (!establishment_id) { showToast('Prevádzka nebola nájdená.', 'error'); return; }
        const fd = new FormData();
        fd.append('action', 'update_business_tier_and_credit');
        fd.append('establishment_id', establishment_id);
        fd.append('tier', document.getElementById('tw-tier-select').value);
        fd.append('sponsored_grant_months', months);
        try {
            const res = await fetch('api/admin.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showToast('Sponzorované predĺžené o ' + (months === 12 ? '1 rok' : months + ' mesiac(e)') + '.', 'success');
                loadBusinesses(); loadUsers();
            } else {
                showToast(data.error || 'Chyba pri ukladaní.', 'error');
            }
        } catch(err) { showToast('Chyba pripojenia k serveru.', 'error'); }
    }

    async function revokeSponsored() {
        const establishment_id = document.getElementById('tw-establishment-id').value;
        if (!establishment_id) { showToast('Prevádzka nebola nájdená.', 'error'); return; }
        const fd = new FormData();
        fd.append('action', 'update_business_tier_and_credit');
        fd.append('establishment_id', establishment_id);
        fd.append('tier', document.getElementById('tw-tier-select').value);
        fd.append('sponsored_revoke', '1');
        try {
            const res = await fetch('api/admin.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showToast('Sponzorované bolo zrušené.', 'success');
                updateSponsoredStatusUI(null);
                loadBusinesses(); loadUsers();
            } else {
                showToast(data.error || 'Chyba pri ukladaní.', 'error');
            }
        } catch(err) { showToast('Chyba pripojenia k serveru.', 'error'); }
    }

    function closeTierWalletModal() {
        document.getElementById('tier-wallet-modal').style.display = 'none';
    }

    function updateCreditModeUI() {
        const mode = document.querySelector('input[name="tw-credit-mode"]:checked')?.value || 'add';
        const quickBtns = document.getElementById('tw-quick-credit-btns');
        if (quickBtns) {
            quickBtns.style.display = (mode === 'add') ? 'flex' : 'none';
        }
        const amtInput = document.getElementById('tw-credit-amount');
        if (mode === 'set' && amtInput.value === '0.00') {
            amtInput.value = g_currentCreditVal.toFixed(2);
        }
    }

    function setQuickCredit(amount) {
        const amtInput = document.getElementById('tw-credit-amount');
        let cur = parseFloat(amtInput.value) || 0;
        amtInput.value = (cur + amount).toFixed(2);
    }

    async function saveTierWallet(e) {
        e.preventDefault();
        const btn = document.getElementById('tw-submit-btn');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">hourglass_top</span> Ukladám...';

        try {
            const establishment_id = document.getElementById('tw-establishment-id').value;
            const user_id = document.getElementById('tw-user-id').value;
            const tier = document.getElementById('tw-tier-select').value;
            const credit_mode = document.querySelector('input[name="tw-credit-mode"]:checked')?.value || 'add';
            const credit_amount = parseFloat(document.getElementById('tw-credit-amount').value) || 0;
            const sms_mode = document.querySelector('input[name="tw-sms-mode"]:checked')?.value || 'add';
            const sms_amount = parseInt(document.getElementById('tw-sms-amount').value) || 0;

            const fd = new FormData();
            fd.append('action', 'update_business_tier_and_credit');
            fd.append('establishment_id', establishment_id);
            fd.append('user_id', user_id);
            fd.append('tier', tier);
            fd.append('credit_mode', credit_mode);
            fd.append('credit_amount', credit_amount);
            fd.append('sms_mode', sms_mode);
            fd.append('sms_amount', sms_amount);

            const res = await fetch('api/admin.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                showToast(data.message || 'Nastavenia boli úspešne uložené.', 'success');
                closeTierWalletModal();
                loadBusinesses();
                loadUsers();
            } else {
                showToast(data.error || 'Chyba pri ukladaní.', 'error');
            }
        } catch(err) {
            console.error(err);
            showToast('Chyba pripojenia k serveru.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('confirm-btn-cancel').addEventListener('click', hideConfirm);
        document.getElementById('confirm-btn-ok').addEventListener('click', () => {
            let cb = confirmCallback;
            hideConfirm();
            if (cb) cb();
        });
    });

    // Initialize — ak prídeme odkazom z inej admin stránky (napr. admin-promo-kody.php) s ?section=X,
    // otvoríme rovno danú sekciu namiesto vždy len Prehľadu.
    const initialSection = new URLSearchParams(window.location.search).get('section');
    if (initialSection && document.getElementById('sec-' + initialSection)) {
        showSection(initialSection);
    } else {
        loadDashboard();
    }

</script>
<script src="assets/js/theme.js?v=1.1">
        
</script>

<div class="modal-overlay" id="confirm-modal" style="z-index: 1001;">
    <div class="modal" style="max-width: 400px; padding: 20px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom: 15px;">
            <span class="material-symbols-outlined" style="font-size: 20px; color: var(--text-primary);">language</span>
            <span style="font-weight: 600; color: var(--text-primary);"><?= BRAND_SITE ?></span>
        </div>
        <p id="confirm-msg" style="margin: 0 0 25px 0; color: var(--text-primary); font-size: 15px;"></p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button id="confirm-btn-ok" style="background: #0066ff; color: #fff; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: inherit; transition: 0.2s;" onmouseover="this.style.background='#0052cc'" onmouseout="this.style.background='#0066ff'">OK</button>
            <button id="confirm-btn-cancel" style="background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: 500; font-family: inherit; transition: 0.2s;" onmouseover="this.style.background='rgba(128,128,128,0.1)'" onmouseout="this.style.background='transparent'">Zrušiť</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="biz-modal">
    <div class="modal">
        <div class="modal-header">
            <h2>Detail prevádzky</h2>
            <span class="material-symbols-outlined close-modal" onclick="closeModal()">close</span>
        </div>
        <div class="modal-body">
            <div class="biz-detail"><strong>Názov prevádzky</strong><p id="modal-biz-name"></p></div>
            <div class="biz-detail"><strong>Majiteľ</strong><p id="modal-biz-owner"></p></div>
            <div class="biz-detail"><strong>Kategória</strong><p id="modal-biz-cat"></p></div>
            <div class="biz-detail"><strong>Adresa</strong><p id="modal-biz-address"></p></div>
            <div class="biz-detail"><strong>Telefón</strong><p id="modal-biz-phone"></p></div>
            <div class="biz-detail"><strong>Otváracie hodiny</strong><p id="modal-biz-oh" style="font-size: 14px;"></p></div>
            <div class="biz-detail"><strong>Popis</strong><p id="modal-biz-desc" style="font-size: 14px;"></p></div>
        </div>
    </div>
</div>

<!-- TIER & WALLET MANAGEMENT MODAL -->
<div class="modal-overlay" id="tier-wallet-modal" style="z-index: 1002;">
    <div class="modal" style="max-width: 500px; padding: 25px;">
        <div class="modal-header" style="margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(176, 128, 66, 0.15); color: var(--primary-color); display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined" style="font-size: 22px;">workspace_premium</span>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px;">Správa profilu a peňaženky</h2>
                    <span id="tw-target-name" style="font-size: 13px; color: var(--text-secondary); font-weight: 600;"></span>
                </div>
            </div>
            <span class="material-symbols-outlined close-modal" onclick="closeTierWalletModal()">close</span>
        </div>
        
        <form id="tier-wallet-form" onsubmit="saveTierWallet(event)">
            <input type="hidden" id="tw-establishment-id">
            <input type="hidden" id="tw-user-id">
            
            <!-- 1. VÝBER PREDPLATNÉHO BALÍKA (TIER) -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom; color: var(--primary-color);">card_membership</span>
                    Predplatný balík (Profil)
                </label>
                <select id="tw-tier-select" style="width: 100%; padding: 12px 14px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--bg-color); color: var(--text-primary); font-size: 14px; font-weight: 600; outline: none; font-family: inherit;">
                    <option value="free">FREE (Základný profil, 150 rezervácií)</option>
                    <option value="start">START (Basic profil, 300 rezervácií, 1 zamestnanec)</option>
                    <option value="pro">PRO (Odporúčaný PRO profil, 1 500 rezervácií, Last Minute)</option>
                    <option value="vip">VIP / ELITE (Maximálny balík, Neobmedzené rezervácie & tím, VIP prednosť)</option>
                </select>
            </div>

            <!-- 2. KREDIT DO PEŇAŽENKY (NA TOPOVANIE A BALÍČKY) -->
            <div style="margin-bottom: 20px; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom; color: #10b981;">account_balance_wallet</span>
                        Peňaženka (Topovanie & Rozšírenia)
                    </label>
                    <span style="font-size: 12px; font-weight: 700; color: #10b981;">
                        Aktuálne: <span id="tw-current-credit">0.00</span> €
                    </span>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <label style="flex: 1; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg);">
                        <input type="radio" name="tw-credit-mode" value="add" checked onchange="updateCreditModeUI()">
                        <span>Pridať k zostatku (+)</span>
                    </label>
                    <label style="flex: 1; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg);">
                        <input type="radio" name="tw-credit-mode" value="set" onchange="updateCreditModeUI()">
                        <span>Nastaviť presnú sumu (=)</span>
                    </label>
                </div>

                <!-- Rýchle čiastky -->
                <div id="tw-quick-credit-btns" style="display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap;">
                    <button type="button" onclick="setQuickCredit(5)" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; color: var(--text-primary);">+5 €</button>
                    <button type="button" onclick="setQuickCredit(10)" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; color: var(--text-primary);">+10 €</button>
                    <button type="button" onclick="setQuickCredit(20)" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; color: var(--text-primary);">+20 €</button>
                    <button type="button" onclick="setQuickCredit(50)" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; color: var(--text-primary);">+50 €</button>
                    <button type="button" onclick="setQuickCredit(100)" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; color: var(--text-primary);">+100 €</button>
                </div>

                <div style="position: relative;">
                    <input type="number" step="0.01" id="tw-credit-amount" placeholder="0.00" value="0.00" style="width: 100%; padding: 10px 40px 10px 14px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 16px; font-weight: 800; box-sizing: border-box; outline: none; font-family: inherit;">
                    <span style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-secondary); font-size: 16px;">€</span>
                </div>
            </div>

            <!-- 3. SMS KREDITY -->
            <div style="margin-bottom: 24px; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom; color: #3b82f6;">sms</span>
                        SMS Kredity
                    </label>
                    <span style="font-size: 12px; font-weight: 700; color: #3b82f6;">
                        Aktuálne: <span id="tw-current-sms">0</span> SMS
                    </span>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <label style="flex: 1; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg);">
                        <input type="radio" name="tw-sms-mode" value="add" checked>
                        <span>Pridať (+)</span>
                    </label>
                    <label style="flex: 1; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg);">
                        <input type="radio" name="tw-sms-mode" value="set">
                        <span>Nastaviť (=)</span>
                    </label>
                </div>

                <input type="number" step="1" id="tw-sms-amount" placeholder="0" value="0" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 15px; font-weight: 700; box-sizing: border-box; outline: none; font-family: inherit;">
            </div>

            <!-- 4. SPONZOROVANÉ ZVÝRAZNENIE (ADMIN UDEĽUJE ZADARMO, NEZÁVISLE OD PLATBY) -->
            <div style="margin-bottom: 24px; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom; color: #8b5cf6;">handshake</span>
                        Sponzorované (partnerská akcia)
                    </label>
                    <span id="tw-sponsored-status" style="font-size: 12px; font-weight: 700; color: var(--text-secondary);">Neaktívne</span>
                </div>
                <p style="font-size: 11.5px; color: var(--text-secondary); margin: 0 0 10px 0;">Udelenie tu je nezávislé od platby z peňaženky — použite pre prevádzky zapojené do partnerskej akcie/zľavy cez Rezervos.</p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" onclick="grantSponsored(1)" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; color: var(--text-primary);">+1 mesiac</button>
                    <button type="button" onclick="grantSponsored(12)" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; color: var(--text-primary);">+1 rok</button>
                    <button type="button" onclick="revokeSponsored()" style="background: transparent; border: 1px solid rgba(239,68,68,0.4); padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; color: #ef4444;">Zrušiť</button>
                </div>
            </div>

            <!-- TLAČIDLÁ -->
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeTierWalletModal()" style="background: transparent; color: var(--text-secondary); border: 1px solid var(--border-color); padding: 10px 18px; border-radius: 10px; font-weight: 600; cursor: pointer;">Zrušiť</button>
                <button type="submit" id="tw-submit-btn" style="background: var(--primary-color); color: #fff; border: none; padding: 10px 22px; border-radius: 10px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(176, 128, 66, 0.35);">
                    <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                    <span>Uložiť zmeny</span>
                </button>
            </div>
        </form>
    </div>
</div>


    <div id="toast"></div>
    <!-- Prompt Modal -->
    <div id="prompt-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:var(--card-bg); padding:30px; border-radius:12px; width:100%; max-width:400px; text-align:center;">
            <h2>Dvojfázové overenie</h2>
            <p style="color:var(--text-secondary); margin-bottom:20px;">Na e-mail sme vám zaslali kód. Prosím, zadajte ho pre potvrdenie akcie.</p>
            <input type="text" id="prompt-input" class="form-control" style="width:100%; padding:12px; font-size:20px; text-align:center; letter-spacing:2px; margin-bottom:20px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary);">
            <div style="display:flex; gap:10px; justify-content:center;">
                <button class="btn" onclick="closePromptModal()" style="background:#e5e7eb; color:#374151; padding:10px 20px; border-radius:8px; border:none; cursor:pointer;">Zrušiť</button>
                <button class="btn" onclick="submitPromptModal()" style="background:var(--primary-color); color:#fff; padding:10px 20px; border-radius:8px; border:none; cursor:pointer;">Potvrdiť</button>
            </div>
        </div>
    </div>
        

<script>
    function showToast(msg, type='success') {
        let t = document.getElementById('toast');
        if(!t) return console.error(msg);
        t.innerText = msg;
        t.style.backgroundColor = (type === 'error') ? '#e74c3c' : '#2ecc71';
        t.className = 'show';
        setTimeout(() => { t.className = t.className.replace('show', ''); }, 3000);
    }

    async function toggle2FA(enabled) {
        const fd = new FormData();
        fd.append('action', 'toggle_2fa');
        fd.append('enabled', enabled ? 1 : 0);
        try {
            let res = await fetch('api/profile_security.php', { method: 'POST', body: fd });
            let data = await res.json();
            if(data.success) { showToast(data.message || 'Nastavenie bolo uložené.', 'success'); }
            else { showToast(data.error || 'Chyba', 'error'); document.getElementById('toggle-2fa').checked = !enabled; }
        } catch(e) { showToast('Chyba siete', 'error'); document.getElementById('toggle-2fa').checked = !enabled; }
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
        } else { showToast('Zadajte platný kód', 'error'); }
    }

    async function changePassword(e) {
        e.preventDefault();
        const current = document.getElementById('current_password').value;
        const newp = document.getElementById('new_password').value;
        const conf = document.getElementById('confirm_password').value;
        
        if (newp !== conf) { showToast('Nové heslá sa nezhodujú', 'error'); return; }
        if (newp.length < 6) { showToast('Nové heslo musí mať aspoň 6 znakov', 'error'); return; }
        
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
                } else { showToast(data.error || 'Chyba pri zmene hesla', 'error'); }
            }
        } else { showToast(data_req.error || 'Chyba pri vyžiadaní kódu', 'error'); }
    }
</script>

</body>
</html>
