<?php
// dashboard-head.php - Shared head for standalone dashboard pages
// Requires $pageTitle to be set before including; optionally $currentPage
if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php';
if (!isset($pageTitle)) $pageTitle = 'Dashboard - ' . BRAND_NAME;
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="/assets/js/theme.js?v=2.0"></script>
    <style>
        :root {
            --bg-color: #ffffff; --card-bg: #ffffff;
            --text-primary: #111827; --text-secondary: #6b7280;
            --border-color: #e5e7eb; --primary-color: #b08042; --primary-hover: #966c35;
            --sidebar-bg: #0f172a; --sidebar-hover: rgba(255,255,255,0.08);
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1),0 2px 4px -1px rgba(0,0,0,0.06);
            --input-bg: #f9fafb; --today-bg: rgba(176,128,66,0.06);
            /* Typografická škála */
            --fs-xs:   12px;
            --fs-sm:   13px;
            --fs-base: 14px;
            --fs-md:   16px;
            --fs-lg:   18px;
            --fs-xl:   20px;
        }
        body.dark-mode {
            --bg-color: #0b0f19; --card-bg: #111827;
            --text-primary: #ffffff; --text-secondary: #94a3b8;
            --border-color: rgba(255,255,255,0.08); --sidebar-bg: #090d16;
            --sidebar-hover: rgba(255,255,255,0.06);
            --shadow-sm: 0 4px 20px rgba(0,0,0,0.3); --shadow-md: 0 8px 30px rgba(0,0,0,0.5);
            --input-bg: #1e293b; --today-bg: rgba(176,128,66,0.08);
        }
        body { margin:0; padding:0; font-family: 'Outfit', sans-serif; font-size:var(--fs-base); line-height:1.5; background-color:var(--bg-color); color:var(--text-primary); display:flex; height:100vh; overflow:hidden; }
        .admin-sidebar { width:64px; background:var(--sidebar-bg); display:flex; flex-direction:column; padding:0; margin:0; z-index:100; box-shadow:4px 0 20px rgba(0,0,0,0.25); transition:width 0.25s cubic-bezier(0.4,0,0.2,1); overflow:hidden; height:100vh; box-sizing:border-box; flex-shrink:0; user-select:none; }
        .admin-sidebar:hover { width:250px; }
        .admin-logo { height:60px; padding:0; margin:0; white-space:nowrap; overflow:hidden; display:flex; align-items:center; justify-content:center; text-decoration:none; box-sizing:border-box; background:var(--sidebar-bg); border-bottom:1px solid rgba(255,255,255,0.08); flex-shrink:0; width:100%; }
        .sidebar-logo,.admin-logo img { height:34px; max-height:34px; width:34px; filter:brightness(0) invert(1); display:inline-block; vertical-align:middle; flex-shrink:0; object-fit:contain; transition:transform 0.2s ease; }
        .logo-text { display:none; opacity:0; width:0; transition:opacity 0.2s; font-size:var(--fs-xl); font-weight:700; letter-spacing:0.3px; line-height:1; }
        .admin-sidebar:hover .admin-logo { justify-content:flex-start; padding:0 15px; gap:11px; }
        .admin-sidebar:hover .logo-text { display:inline-flex; align-items:center; opacity:1; width:auto; margin:0; }
        .admin-menu { list-style:none; padding:16px 0 6px 0; margin:0; flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; display:flex; flex-direction:column; justify-content:flex-start; gap:3px; align-items:center; width:100%; box-sizing:border-box; }
        .admin-menu li { margin:0; padding:0; display:flex; justify-content:center; align-items:center; width:100%; }
        .admin-menu a,.logout-container a { display:flex !important; align-items:center !important; justify-content:center !important; color:#94a3b8; text-decoration:none; padding:0 !important; width:34px !important; height:34px !important; min-width:34px !important; max-width:34px !important; min-height:34px !important; max-height:34px !important; aspect-ratio:1/1 !important; box-sizing:border-box !important; margin:0 auto !important; border-radius:8px !important; transition:background-color 0.15s ease,color 0.15s ease,width 0.2s ease; font-size:15px; font-weight:600; cursor:pointer; }
        .admin-sidebar:hover .admin-menu,.admin-sidebar:hover .logout-container { align-items:stretch; }
        .admin-sidebar:hover .admin-menu a,.admin-sidebar:hover .logout-container a { width:calc(100% - 16px) !important; max-width:none !important; height:34px !important; min-height:34px !important; max-height:34px !important; aspect-ratio:auto !important; justify-content:flex-start !important; padding:0 12px !important; gap:12px !important; margin:0 8px !important; border-radius:8px !important; box-sizing:border-box !important; }
        .admin-menu a span.material-symbols-outlined,.logout-container a span.material-symbols-outlined { font-size:21px !important; min-width:21px !important; width:21px !important; height:21px !important; line-height:1 !important; display:flex !important; align-items:center !important; justify-content:center !important; flex-shrink:0 !important; }
        .admin-menu a span.menu-text,.logout-container a span.menu-text { display:none; white-space:nowrap; font-size:var(--fs-base); font-weight:600; letter-spacing:0.1px; }
        .admin-sidebar:hover .admin-menu a span.menu-text,.admin-sidebar:hover .logout-container a span.menu-text { display:inline-block; opacity:1; }
        .admin-menu a:hover,.logout-container a:hover { background:var(--sidebar-hover); color:#ffffff; }
        .admin-menu a.active,.logout-container a.active,.logout-container a.user-identity.active { background:linear-gradient(135deg,#b08042 0%,#966c35 100%) !important; color:#ffffff !important; box-shadow:0 3px 10px rgba(176,128,66,0.35) !important; font-weight:600; }
        .logout-container { padding:6px 0 8px 0; margin-top:auto; margin-bottom:0; white-space:nowrap; overflow:hidden; display:flex; flex-direction:column; align-items:center; gap:3px; border-top:1px solid rgba(255,255,255,0.08); box-sizing:border-box; flex-shrink:0; width:100%; }
        .logout-container a.logout-link:hover { background:rgba(239,68,68,0.18) !important; color:#ef4444 !important; }
        .admin-main { flex:1; display:flex; flex-direction:column; background:var(--bg-color); height:100vh; max-height:100vh; min-height:0; overflow:hidden; }
        .admin-header { height:60px; flex-shrink:0; background:var(--bg-color); padding:0 24px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); box-sizing:border-box; z-index:10; }
        .admin-header-title { margin:0; font-size:var(--fs-xl); font-weight:700; color:var(--text-primary); height:26px; line-height:26px; display:flex; align-items:center; gap:10px; }
        .admin-header-right { display:flex; align-items:center; gap:15px; }
        .theme-btn { background:none; border:1px solid var(--border-color); color:var(--text-primary); cursor:pointer; display:flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:8px; transition:all 0.2s ease; box-sizing:border-box; }
        .theme-btn:hover { border-color:var(--primary-color); color:var(--primary-color); }
        .admin-content { flex:1 1 0; height:0; display:block; overflow-y:auto; background:var(--bg-color); }
        .section { display:block; padding:25px 30px; box-sizing:border-box; width:100%; }
        .section>div,.section>form,.section .admin-panel,.section .vueto-card,.section .profile-grid { width:100%; max-width:100%; margin-left:0; margin-right:0; box-sizing:border-box; }
        .section-header { margin:0 0 5px 0 !important; font-size:var(--fs-xl) !important; display:flex; align-items:center; gap:10px; font-weight:700; color:var(--text-primary); }
        .section-header .material-symbols-outlined { color:var(--primary-color); font-size:24px; }
        .section-desc { font-size:var(--fs-sm); color:var(--text-secondary); margin-top:0; margin-bottom:25px; }
        .admin-panel { background:var(--card-bg); border-radius:14px; padding:25px; margin-bottom:25px; box-shadow:var(--shadow-sm); border:1px solid var(--border-color); }
        .vueto-card { background:var(--card-bg); border-radius:14px; margin-bottom:25px; box-shadow:var(--shadow-sm); border:1px solid var(--border-color); overflow:hidden; }
        .vueto-card-header { display:flex; justify-content:space-between; align-items:flex-start; padding:20px 25px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:10px; }
        .vueto-card-body { padding:20px 25px; }
        .vueto-table-wrapper { overflow-x:auto; }
        .vueto-table { width:100%; border-collapse:collapse; font-size:var(--fs-base); }
        .vueto-table th { padding:10px 16px; text-align:left; font-size:var(--fs-xs); font-weight:700; text-transform:uppercase; color:var(--text-secondary); border-bottom:1px solid var(--border-color); background:var(--bg-color); }
        .vueto-table td { padding:14px 16px; border-bottom:1px solid var(--border-color); color:var(--text-primary); vertical-align:middle; }
        .vueto-table tr:hover td { background:var(--today-bg); }
        .vueto-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:var(--fs-xs); font-weight:600; }
        .vueto-badge.green { background:rgba(16,185,129,0.12); color:#10b981; }
        .vueto-badge.yellow { background:rgba(245,158,11,0.12); color:#d97706; }
        .vueto-badge.blue { background:rgba(59,130,246,0.12); color:#3b82f6; }
        .td-icon-text { display:flex; flex-direction:column; gap:3px; font-size:var(--fs-sm); color:var(--text-secondary); }
        .td-icon-text strong { color:var(--text-primary); font-size:var(--fs-base); }
        .td-icon-text span { display:flex; align-items:center; gap:4px; }
        .data-card { background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; padding:20px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; transition:0.3s; }
        .data-card:hover { border-color:var(--primary-color); }
        .data-info { flex:1; min-width:250px; } .data-title { font-size:var(--fs-lg); font-weight:600; margin:0 0 5px 0; color:var(--text-primary); }
        .data-subtitle { font-size:var(--fs-base); color:var(--text-secondary); margin:0 0 5px 0; display:flex; align-items:center; gap:5px; }
        .data-actions { display:flex; flex-direction:row; align-items:center; gap:15px; }
        .btn-action { background:none; border:none; cursor:pointer; transition:0.2s; padding:5px; }
        .btn-action:hover { transform:scale(1.1); }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:500; color:var(--text-secondary); }
        .form-group input,.form-group textarea { width:100%; padding:12px 15px; border:1px solid var(--border-color); background:var(--input-bg); color:var(--text-primary); border-radius:8px; font-family:inherit; box-sizing:border-box; transition:border-color 0.3s; }
        .form-group select,select.vueto-input,select { width:100%; padding:12px 42px 12px 16px; border:1.5px solid var(--border-color); background-color:var(--input-bg); color:var(--text-primary); border-radius:10px; font-family:inherit; font-size:var(--fs-sm); font-weight:500; box-sizing:border-box; appearance:none; -webkit-appearance:none; -moz-appearance:none; background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23b08042'%3e%3cpath d='M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z'/%3e%3c/svg%3e"); background-repeat:no-repeat; background-position:right 14px center; background-size:22px 22px; cursor:pointer; transition:border-color 0.2s,box-shadow 0.2s; }
        body.dark-mode .form-group select,body.dark-mode select.vueto-input,body.dark-mode select { background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23d4a362'%3e%3cpath d='M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z'/%3e%3c/svg%3e"); }
        .form-group input[type="checkbox"] { width:auto; padding:0; cursor:pointer; accent-color:var(--primary-color); }
        .form-group input:focus,.form-group textarea:focus,.form-group select:focus { outline:none; border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(176,128,66,0.15); }
        .ac-item { padding:10px 15px; cursor:pointer; border-bottom:1px solid var(--border-color); color:#111827; }
        .ac-item:hover { background:var(--primary-color); color:#ffffff; }
        button,.btn,.btn-primary,.btn-secondary,.theme-btn { border-radius:8px; font-family:inherit; transition:all 0.2s ease; }
        .btn-primary { background:var(--primary-color) !important; color:#fff !important; border:1px solid var(--primary-color) !important; padding:10px 22px; border-radius:8px; font-size:var(--fs-base); font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 8px rgba(176,128,66,0.25); }
        .btn-primary:hover { background:var(--primary-hover) !important; border-color:var(--primary-hover) !important; transform:translateY(-1px); box-shadow:0 4px 12px rgba(176,128,66,0.35); }
        .btn-secondary,.btn { background:var(--card-bg) !important; color:var(--text-primary) !important; border:1px solid var(--border-color) !important; padding:10px 20px; border-radius:8px; font-size:var(--fs-base); font-weight:500; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; }
        .btn-secondary:hover,.btn:hover { background:var(--bg-color) !important; border-color:var(--primary-color) !important; color:var(--primary-color) !important; }
        .alert-warning { padding:15px; border-radius:12px; border:1px solid #f1c40f; margin-bottom:25px; }
        body.dark-mode .sidebar-logo { filter:invert(1) hue-rotate(180deg) !important; }
        @keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
        @keyframes slideIn { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
        @keyframes bell-ring {
            0%,100% { transform:rotate(0deg); }
            15%     { transform:rotate(16deg); }
            30%     { transform:rotate(-13deg); }
            45%     { transform:rotate(10deg); }
            60%     { transform:rotate(-6deg); }
            75%     { transform:rotate(3deg); }
        }
        .bell-blinking {
            animation: bell-ring 0.75s cubic-bezier(0.4,0,0.2,1) infinite;
            display: inline-block;
            transform-origin: top center;
        }
        .app-toast-container { position:fixed; bottom:24px; right:24px; z-index:99999; display:flex; flex-direction:column; gap:10px; pointer-events:none; }
        .app-toast { padding:12px 20px; border-radius:10px; font-size:var(--fs-base); font-weight:600; display:flex; align-items:center; gap:10px; animation:slideIn 0.3s ease; pointer-events:auto; box-shadow:0 4px 16px rgba(0,0,0,0.18); max-width:360px; }
        .app-toast.success { background:#10b981; color:#fff; }
        .app-toast.error { background:#ef4444; color:#fff; }
        .app-toast.info { background:#3b82f6; color:#fff; }
        .profile-grid { display:flex; flex-direction:column; gap:20px; }
        .premium-card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:14px; padding:22px; }
        .premium-card-header { display:flex; align-items:center; gap:10px; margin-bottom:18px; }
        .premium-card-title { font-size:var(--fs-md); font-weight:700; color:var(--text-primary); }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media (max-width:600px) { .form-row { grid-template-columns:1fr; } }
        /* Responzívna typografická škála — mobilné zariadenia */
        @media (max-width:768px) {
            :root {
                --fs-xs:   11px;
                --fs-sm:   12px;
                --fs-base: 13px;
                --fs-md:   15px;
                --fs-lg:   17px;
                --fs-xl:   18px;
            }
        }
        @media (max-height:740px) {
            .admin-logo { height:60px; } .sidebar-logo,.admin-logo img { max-height:30px; height:30px; width:30px; }
            .admin-menu { padding:14px 0 4px 0; gap:2px; } .admin-menu a,.logout-container a { height:34px; width:34px; font-size:13px; }
            .admin-menu a span.material-symbols-outlined,.logout-container a span.material-symbols-outlined { font-size:20px; width:21px; height:21px; }
            .logout-container { padding:4px 0; gap:2px; }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=3.4">
    <link rel="stylesheet" href="/assets/css/scrollbars.css?v=3">
</head>
<body>
<script src="/assets/js/dashboard-common.js?v=3.1" defer></script>
<script src="/assets/js/sidebar-hover-fix.js?v=1.1" defer></script>
