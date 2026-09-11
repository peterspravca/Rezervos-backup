<?php
$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
// Count new unassigned leads
$pdo2 = db_connect();
$new_leads = $pdo2->query("SELECT COUNT(*) FROM leads WHERE status='novy' AND (assigned_to IS NULL OR assigned_to = 0)")->fetchColumn();
// Count unread board items
$u_id2 = $user['id'];
$unread_board = $pdo2->query("SELECT COUNT(*) FROM crm_board b 
    WHERE b.user_id != $u_id2 
    AND NOT EXISTS (SELECT 1 FROM crm_board_confirmations c WHERE c.board_id = b.id AND c.user_id = $u_id2)")->fetchColumn();

// Count unread notifications
$unread_notifications_count = count_unread_notifications($u_id2);

$is_email_page = (basename($_SERVER['PHP_SELF']) === 'email.php');
?>
<!DOCTYPE html>
<html lang="sk" translate="no" class="<?= $is_email_page ? 'has-bottom-nav' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<meta name="google" content="notranslate">
<title><?= htmlspecialchars($page_title ?? 'CRM') ?> – VUETO CRM</title>
<link rel="apple-touch-icon" sizes="180x180" href="/nastenka/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/nastenka/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/nastenka/favicon/favicon-16x16.png">
<link rel="manifest" href="/nastenka/favicon/site.webmanifest">
<link rel="shortcut icon" href="/nastenka/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/nastenka/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<!-- Quill Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    if (localStorage.getItem('crm_sidebar_collapsed') === 'true') {
        document.documentElement.classList.add('sidebar-collapsed');
    }
</script>
</head>
<body>
<!-- Mobile Menu Toggle Button (Moved to body root for maximum z-index visibility) -->
<button class="menu-toggle show-mobile" onclick="toggleSidebar()" title="Zbaliť/Rozbaliť menu">
    <i data-lucide="menu"></i>
</button>

<!-- Mobile overlay -->
<div class="mobile-overlay" onclick="closeSidebarMobile()"></div>

<div class="crm-layout">

<!-- SIDEBAR -->
<aside class="sidebar">
    <a href="/nastenka/index.php" class="sidebar-logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;">
        <img src="/nastenka/vueto_logo.png" alt="VUETO Logo" class="logo-img">
        <h2 class="nav-text">VUETO CRM</h2>
    </a>

    <div class="nav-section">
        <div class="nav-label">Hlavné</div>
        <a href="/nastenka/index.php" class="nav-item <?= ($current_page==='index.php' && empty($_GET['status'])) ? 'active':'' ?>">
            <span class="icon" data-tooltip="Dashboard"><i data-lucide="layout-dashboard"></i></span> <span class="nav-text">Dashboard</span>
            <?php if($new_leads > 0): ?>
            <span class="nav-badge"><?= $new_leads ?></span>
            <?php endif; ?>
        </a>
        <a href="/nastenka/board.php" class="nav-item <?= $current_page==='board.php'?'active':'' ?>">
            <span class="icon" data-tooltip="Tabuľa"><i data-lucide="pin"></i></span> <span class="nav-text">Tabuľa</span>
            <?php if($unread_board > 0): ?>
            <span class="nav-badge"><?= $unread_board ?></span>
            <?php endif; ?>
        </a>
        <a href="/nastenka/email.php" class="nav-item <?= $current_page==='email.php'?'active':'' ?>">
            <span class="icon" data-tooltip="E-mail"><i data-lucide="mail"></i></span> <span class="nav-text">E-mail</span>
        </a>
        <a href="/nastenka/calendar.php" class="nav-item <?= $current_page==='calendar.php'?'active':'' ?>">
            <span class="icon" data-tooltip="Kalendár"><i data-lucide="calendar"></i></span> <span class="nav-text">Kalendár</span>
        </a>
        <a href="/nastenka/orders.php" class="nav-item <?= $current_page==='orders.php'?'active':'' ?>">
            <span class="icon" data-tooltip="Zákazky"><i data-lucide="shopping-bag"></i></span> <span class="nav-text">Zákazky</span>
        </a>
        <?php if(is_admin()): ?>
        <a href="/nastenka/documents.php" class="nav-item <?= $current_page==='documents.php'?'active':'' ?>">
            <span class="icon" data-tooltip="Dokumenty"><i data-lucide="folders"></i></span> <span class="nav-text">Dokumenty</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="nav-section">
        <div class="nav-label">Kontakty</div>
        <a href="/nastenka/index.php?status=all" class="nav-item <?= ($current_page==='index.php' && (($_GET['status']??'')==='all' || (!isset($_GET['status']) && !empty($_GET['q'])))) ? 'active':'' ?>">
            <span class="icon" data-tooltip="Všetky kontakty"><i data-lucide="contact"></i></span> <span class="nav-text">Všetky kontakty</span>
        </a>
        <a href="/nastenka/index.php?status=novy" class="nav-item <?= ($current_page==='index.php' && ($_GET['status']??'')==='novy') ? 'active':'' ?>">
            <span class="icon" data-tooltip="Nové"><i data-lucide="sparkles"></i></span> <span class="nav-text">Nové</span>
        </a>
        <a href="/nastenka/index.php?status=kontaktovany" class="nav-item <?= ($current_page==='index.php' && ($_GET['status']??'')==='kontaktovany') ? 'active':'' ?>">
            <span class="icon" data-tooltip="Kontaktované"><i data-lucide="phone-outgoing"></i></span> <span class="nav-text">Kontaktované</span>
        </a>
        <a href="/nastenka/index.php?status=v_procese" class="nav-item <?= ($current_page==='index.php' && ($_GET['status']??'')==='v_procese') ? 'active':'' ?>">
            <span class="icon" data-tooltip="V procese"><i data-lucide="settings-2"></i></span> <span class="nav-text">V procese</span>
        </a>
        <a href="/nastenka/index.php?status=ukonceny" class="nav-item <?= ($current_page==='index.php' && ($_GET['status']??'')==='ukonceny') ? 'active':'' ?>">
            <span class="icon" data-tooltip="Ukončené"><i data-lucide="check-circle"></i></span> <span class="nav-text">Ukončené</span>
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Marketing</div>
        <a href="/nastenka/marketing.php" class="nav-item <?= $current_page==='marketing.php'?'active':'' ?>">
            <span class="icon" data-tooltip="Marketing"><i data-lucide="megaphone"></i></span> <span class="nav-text">Marketing</span>
        </a>
    </div>

    <?php if(is_admin()): ?>
    <div class="nav-section">
        <div class="nav-label">Administrácia</div>
        <a href="/nastenka/admin.php" class="nav-item <?= ($current_page==='admin.php' && (empty($_GET['tab']) || $_GET['tab']==='users')) ?'active':'' ?>">
            <span class="icon" data-tooltip="Používatelia"><i data-lucide="users"></i></span> <span class="nav-text">Používatelia</span>
        </a>
        <a href="/nastenka/admin.php?tab=settings" class="nav-item <?= ($current_page==='admin.php' && ($_GET['tab']??'')==='settings') ?'active':'' ?>">
            <span class="icon" data-tooltip="Nastavenia"><i data-lucide="sliders"></i></span> <span class="nav-text">Nastavenia</span>
        </a>
    </div>
    <?php endif; ?>

    <div class="nav-section">
        <div class="nav-label">Systém</div>
        <?php if(is_admin()): ?>
        <a href="/nastenka/admin.php?tab=bugs" class="nav-item <?= ($current_page==='admin.php' && ($_GET['tab']??'')==='bugs') ?'active':'' ?>">
            <span class="icon" data-tooltip="Nahlásené chyby"><i data-lucide="bug"></i></span> <span class="nav-text">Nahlásené chyby</span>
        </a>
        <?php else: ?>
        <a href="javascript:void(0)" onclick="openBugReport()" class="nav-item">
            <span class="icon" data-tooltip="Nahlásiť chybu"><i data-lucide="bug"></i></span> <span class="nav-text">Nahlásiť chybu</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-spacer" style="flex:1;"></div>
    <div class="sidebar-footer" style="padding-bottom: 1rem;">
        <a href="/nastenka/profile.php" class="user-pill" style="text-decoration:none; color:inherit;">
            <div class="user-avatar" data-tooltip="Môj profil"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
            <div class="user-info" style="min-width: 0;">
                <div class="user-name" style="word-break: break-word; line-height: 1.2; margin-bottom: 2px; color: var(--text-primary);"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-role" style="font-size: 0.75rem; color: var(--text-muted);"><?= $user['role']==='admin'?'Admin':'Kolega' ?></div>
            </div>
        </a>
    </div>
</aside>

<!-- TOPBAR + MAIN -->
<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle-desktop" onclick="toggleSidebar()" title="Zbaliť/Rozbaliť menu">
            <i class="ti ti-menu-2"></i>
        </button>
    </div>

    <h1><?= htmlspecialchars($page_title ?? 'CRM') ?></h1>
    <div class="topbar-actions">
        <?php if ($current_page === 'new_email.php'): ?>
            <a href="email.php" class="btn btn-primary btn-icon" style="background: #6366f1 !important; border-color: #6366f1 !important; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;" title="Späť do schránky">
                <i class="ti ti-arrow-left"></i> <span class="hide-mobile" style="margin-left:8px;">Späť</span>
            </a>
        <?php endif; ?>

        <?php if ($current_page === 'board.php'): ?>
        <a href="javascript:void(0)" class="btn btn-primary btn-icon" onclick="toggleBoardForm()" title="Pridať nový odkaz">
            <i class="ti ti-plus"></i> <span class="hide-mobile" style="margin-left:8px;">Nový odkaz</span>
        </a>
        <?php elseif ($current_page === 'email.php' || $current_page === 'new_email.php'): ?>
        <?php if ($current_page !== 'new_email.php'): ?>
        <a href="new_email.php" class="btn btn-primary btn-icon" title="Nová správa">
            <i class="ti ti-plus"></i> <span class="hide-mobile" style="margin-left:8px;">Nová správa</span>
        </a>
        <?php endif; ?>
        <?php else: ?>
        <a href="/nastenka/new_contact.php" class="btn btn-primary btn-icon" title="Pridať kontakt ručne">
            <i class="ti ti-plus"></i> <span class="hide-mobile" style="margin-left:8px;">Nový kontakt</span>
        </a>
        <?php endif; ?>

        <!-- NOTIFICATIONS BELL -->
        <div class="notification-wrapper" style="position: relative;">
            <button class="btn btn-secondary btn-icon" onclick="toggleNotifications()" title="Upozornenia" style="position: relative; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-primary); border-radius: 12px; width: 42px; height: 42px;">
                <i class="ti ti-bell"></i>
                <?php if ($unread_notifications_count > 0): ?>
                    <span id="notif-badge" style="position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; font-size: 0.7rem; font-weight: 800; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 50%; border: 2px solid #111827; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);">
                        <?= $unread_notifications_count ?>
                    </span>
                <?php endif; ?>
            </button>
            <div id="notificationsDropdown" style="display: none; position: absolute; top: 120%; right: 0; width: 400px; background: #111827; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); z-index: 1000; overflow: hidden; backdrop-filter: blur(15px);">
                <div style="padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                    <h4 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: white;">Upozornenia</h4>
                    <button onclick="markAllNotificationsAsRead()" style="background: none; border: none; color: #818cf8; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                        <i class="ti ti-checks"></i> Označiť všetko
                    </button>
                </div>
                <div id="notif-list" style="max-height: 450px; overflow-y: auto;">
                    <div style="padding: 40px; text-align: center; color: #64748b; font-size: 0.95rem;">
                        <i class="ti ti-loader-2 rotate"></i> Načítavam...
                    </div>
                </div>
            </div>
        </div>

        <a href="/nastenka/logout.php" class="btn btn-danger btn-icon" title="Odhlásiť sa">
            <i class="ti ti-power"></i>
        </a>
    </div>
</div>

<script>
function toggleNotifications() {
    const d = document.getElementById('notificationsDropdown');
    const isVisible = d.style.display === 'block';
    
    // Hide all other dropdowns if any, here we just toggle
    if (isVisible) {
        d.style.display = 'none';
        document.removeEventListener('click', clickOutsideNotif);
    } else {
        d.style.display = 'block';
        loadNotificationsList();
        setTimeout(() => document.addEventListener('click', clickOutsideNotif), 10);
    }
}

function clickOutsideNotif(e) {
    if (!e.target.closest('.notification-wrapper')) {
        document.getElementById('notificationsDropdown').style.display = 'none';
        document.removeEventListener('click', clickOutsideNotif);
    }
}

function loadNotificationsList() {
    fetch('/nastenka/ajax_notifications.php?action=list')
        .then(res => res.text())
        .then(html => {
            document.getElementById('notif-list').innerHTML = html;
        });
}

function handleNotificationAction(id, action) {
    const fd = new FormData();
    fd.append('id', id);
    
    fetch('/nastenka/ajax_notifications.php?action=' + action, {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('notif-item-' + id);
            if (item) {
                item.style.opacity = '0';
                item.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    item.remove();
                    if (document.querySelectorAll('.notif-item').length === 0) {
                        loadNotificationsList();
                    }
                    // Refresh badge count if possible
                    updateBadgeCount();
                }, 300);
            }
        }
    });
}

function updateBadgeCount() {
    // Small hack to get fresh count, or just decrement if we know
    const badge = document.getElementById('notif-badge');
    if (badge) {
        let count = parseInt(badge.innerText);
        if (count > 0) {
            count--;
            if (count === 0) badge.style.display = 'none';
            else badge.innerText = count;
        }
    }
}

function markAllNotificationsAsRead() {
    fetch('/nastenka/ajax_notifications.php?action=mark_all_read', {method: 'POST'})
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('notif-badge');
                if (badge) badge.style.display = 'none';
                loadNotificationsList();
            }
        });
}
</script>

<div class="main-content" style="flex:1;">
