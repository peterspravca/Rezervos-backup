<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'translator_helper.php';
require_once 'includes/branding.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Typy inzerátov v sekcii Inzercia (zhodné s dashboard-inzercia.php a api/classifieds.php)
$classified_types = [
    'all'     => ['label' => 'Všetky inzeráty',     'icon' => 'apps',       'desc' => 'Všetky inzeráty zo všetkých kategórií'],
    'work'    => ['label' => 'Práca a spolupráca', 'icon' => 'work',        'desc' => 'Ponuky práce a spolupráce v salónoch a štúdiách'],
    'rental'  => ['label' => 'Prenájom',            'icon' => 'storefront', 'desc' => 'Prenájom kresiel, miestností a priestorov'],
    'sale'    => ['label' => 'Predaj a bazár',      'icon' => 'sell',       'desc' => 'Predaj vybavenia, techniky a doplnkov'],
    'courses' => ['label' => 'Kurzy a školenia',    'icon' => 'school',     'desc' => 'Odborné kurzy a školenia'],
    'other'   => ['label' => 'Ostatné',             'icon' => 'category',   'desc' => 'Ostatné inzeráty'],
];

$active_type = trim($_GET['type'] ?? '');
if (!array_key_exists($active_type, $classified_types)) {
    $active_type = 'all';
}

$search_query = trim($_GET['q'] ?? '');
$city         = trim($_GET['city'] ?? '');

$page_title = 'Inzercia | ' . BRAND_SITE;
$page_desc  = 'Práca, prenájom kresiel, predaj vybavenia a kurzy pre salóny, kaderníkov a kozmetičky.';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Google Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,300,0,0" />
    
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <!-- Flatpickr (Modern Date Picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/sk.js"></script>
    
    <!-- System CSS and JS -->
    <link rel="stylesheet" href="assets/css/variables.css?v=7">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <link rel="stylesheet" href="assets/css/site-footer.css?v=5">
    <script src="assets/js/theme.js?v=2.0"></script>

    <style>
        /* Base page layout */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Logo v dark mode (invertované farby, aby čierna bola biela, ale zlatá ostala zlatá) */
        body.dark-mode .sidebar-logo img,
        body.dark-mode .brand-logo-link img,
        body.dark-mode .logo-container img {
            filter: invert(1) hue-rotate(180deg);
        }
        /* Rezervos SVG logo na prevadzky.php */
        .rezervos-pv-logo {
            filter: none !important;
            height: 34px !important;
            width: 34px !important;
            min-width: 34px !important;
            aspect-ratio: 1 / 1 !important;
            object-fit: cover !important;
            border-radius: 8px !important;
            max-width: 190px !important;
        }
        .rezervos-pv-light { display: inline-block; }
        .rezervos-pv-dark  { display: none; }
        body.dark-mode .rezervos-pv-light { display: none; }
        body.dark-mode .rezervos-pv-dark  { display: inline-block; }
        body.dark-mode .sidebar-logo img.rezervos-pv-logo { filter: none !important; }

        /* App Layout Container (Aveino style) */
        .app-layout {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }

        /* Full Height Left Sidebar (Aveino style) */
        .app-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            height: 100vh;
            background-color: var(--card-bg);
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
            box-shadow: none;
            box-sizing: border-box;
        }

        /* When Pinned: Sidebar is placed fixed on the left */
        .app-layout.sidebar-pinned .app-sidebar {
            transform: translateX(0);
        }

        /* When Hover Open (desktop unpinned): temporarily slides in */
        .app-sidebar.hover-open {
            transform: translateX(0);
            box-shadow: 10px 0 35px rgba(0, 0, 0, 0.18);
        }

        /* Mobile Open */
        .app-sidebar.mobile-open {
            transform: translateX(0);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.35);
        }

        /* Sidebar Header */
        .sidebar-header {
            height: 64px;
            padding: 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            flex-shrink: 0;
        }

        .sidebar-logo,
        .topbar-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            line-height: 1;
        }

        .sidebar-logo img,
        .topbar-logo img {
            height: 38px;
            width: 38px;
            object-fit: contain;
            transition: transform 0.2s ease, filter 0.3s ease;
        }
        .sidebar-logo img.rezervos-pv-logo,
        .topbar-logo img.rezervos-pv-logo {
            height: 34px !important;
            width: 34px !important;
            min-width: 34px !important;
            aspect-ratio: 1 / 1 !important;
            object-fit: cover !important;
            border-radius: 8px !important;
        }
        .sidebar-logo img.rezervos-pv-logo,
        body.dark-mode .sidebar-logo img.rezervos-pv-logo { filter: none !important; }
        .rezervos-pv-text-html { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; white-space: nowrap; }
        .rezervos-pv-text-html .sk { font-size: 16px; font-weight: 800; margin-left: 2px; }
        .rezervos-pv-light-text { display: inline; }
        .rezervos-pv-dark-text  { display: none; }
        body.dark-mode .rezervos-pv-light-text { display: none; }
        body.dark-mode .rezervos-pv-dark-text  { display: inline; }

        .sidebar-logo:hover img,
        .topbar-logo:hover img {
            transform: scale(1.05);
        }

        .logo-text {
            font-size: 1.32rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-primary);
            display: inline-flex;
            align-items: baseline;
            font-family: 'Outfit', sans-serif;
            user-select: none;
        }

        .logo-accent {
            color: var(--primary-color);
            font-weight: 800;
        }

        .logo-tld {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: -0.3px;
            opacity: 0.95;
        }

        /* Hide topbar logo when sidebar is pinned on desktop */
        .app-layout.sidebar-pinned .topbar-logo {
            display: none;
        }

        @media (max-width: 1024px) {
            .app-layout.sidebar-pinned .topbar-logo {
                display: inline-flex;
            }
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar-toggle:hover {
            background: var(--input-bg);
            color: var(--primary-color);
        }

        /* Sidebar Section Title */
        .sidebar-section-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            padding: 12px 22px 6px 22px;
        }

        /* Sidebar Scrollable Area */
        .sidebar-scroll-area {
            flex: 1;
            overflow-y: auto;
            padding: 0 10px 24px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        /* Category Navigation Item (Exact Aveino Style & Hover Animation) */
        .category-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 7px 12px;
            border-radius: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .category-nav-item .nav-icon {
            font-size: 20px;
            color: var(--text-secondary);
            transition: color 0.22s ease, transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-nav-item:hover {
            background-color: rgba(176, 128, 66, 0.12);
            color: var(--primary-color) !important;
            padding-left: 20px;
        }
        body.dark-mode .category-nav-item:hover {
            background-color: rgba(176, 128, 66, 0.22);
            color: var(--primary-color) !important;
        }
        .category-nav-item:hover .nav-icon {
            color: var(--primary-color) !important;
            transform: scale(1.08);
        }

        .category-nav-item.active {
            background-color: rgba(176, 128, 66, 0.14);
            color: var(--primary-color) !important;
            font-weight: 700;
            box-shadow: none;
        }
        body.dark-mode .category-nav-item.active {
            background-color: rgba(176, 128, 66, 0.22);
            color: var(--primary-color) !important;
        }
        .category-nav-item.active .nav-icon {
            color: var(--primary-color) !important;
        }

        /* Left edge hover trigger zone */
        #sidebar-hover-trigger {
            position: fixed;
            left: 0;
            top: 0;
            width: 16px;
            height: 100vh;
            z-index: 999;
            background: transparent;
        }

        /* Main Content Container with sidebar offset */
        .app-main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
            margin-left: 0;
            transition: margin-left 0.3s cubic-bezier(0.16, 1, 0.3, 1), width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
        }

        .app-layout.sidebar-pinned .app-main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
        }

        @media (max-width: 1024px) {
            .app-layout.sidebar-pinned .app-main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        /* Top Bar inside main container */
        .app-topbar {
            height: 64px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-color);
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Topbar Sidebar Toggle Button */
        #topbar-sidebar-toggle {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        #topbar-sidebar-toggle:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--input-bg);
        }

        /* Topbar Buttons */
        .top-btn {
            height: 40px;
            min-height: 40px;
            max-height: 40px;
            box-sizing: border-box;
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0 16px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.92rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            transition: all 0.2s;
            text-decoration: none;
            line-height: 1;
            margin: 0;
        }
        .top-btn:hover {
            border-color: var(--primary-color);
            background-color: var(--input-bg);
            color: var(--primary-color);
        }
        .top-btn .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
        }

        /* Icon-only Topbar Buttons (Presný zaoblený štvorec 40x40 ako sidebar toggle) */
        .topbar-left .top-btn,
        .topbar-right .top-btn:not(.login-btn),
        .theme-btn,
        .logout-btn,
        .top-btn.icon-only {
            width: 40px !important;
            min-width: 40px !important;
            max-width: 40px !important;
            padding: 0 !important;
            border-radius: 12px !important;
        }

        .login-btn {
            white-space: nowrap;
        }

        .lang-btn {
            padding: 0 12px;
            font-size: 0.85rem;
        }

        .lang-dropdown {
            position: relative;
            display: flex;
            align-items: center;
        }
        .lang-dropdown::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 100%;
            height: 15px;
            background: transparent;
        }
        .lang-menu {
            display: none;
            position: absolute;
            left: 0;
            top: 100%;
            margin-top: 6px;
            background-color: var(--card-bg);
            min-width: 140px;
            box-shadow: var(--shadow-md);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            z-index: 100;
            overflow: hidden;
        }
        .lang-dropdown:hover .lang-menu {
            display: block;
        }
        .lang-menu a {
            color: var(--text-primary);
            padding: 10px 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            transition: background 0.2s;
        }
        .lang-menu a:hover {
            background-color: var(--input-bg);
            color: var(--primary-color);
        }

        .theme-btn {
            width: 40px;
            min-width: 40px;
            max-width: 40px;
            padding: 0;
        }

        .logout-btn {
            color: #ef4444 !important;
        }
        .logout-btn:hover {
            background-color: rgba(239, 68, 68, 0.1) !important;
            border-color: #ef4444 !important;
        }

        /* Main Page Content Body */
        .page-body {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 18px 28px 60px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex: 1;
            box-sizing: border-box;
        }

        /* Filter Panel (Clean, no redundant outer box) */
        .filter-panel {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            width: 100%;
        }

        .filter-input-wrapper,
        .filter-location-wrapper,
        .filter-date-wrapper {
            position: relative;
            flex: 1.3;
            min-width: 180px;
        }

        .filter-date-wrapper {
            position: relative;
            flex: 1.1;
            min-width: 170px;
            cursor: pointer;
        }

        .filter-date-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 20px;
            pointer-events: auto;
            cursor: pointer;
            line-height: 1;
            z-index: 2;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .filter-date-wrapper:hover .input-icon {
            transform: translateY(-50%) scale(1.12);
        }

        .filter-date-input {
            cursor: pointer;
            background-color: var(--card-bg) !important;
        }

        .filter-input-wrapper .input-icon,
        .filter-location-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 20px;
            pointer-events: none;
            line-height: 1;
        }

        /* Flatpickr Premium Custom Theme (Kompaktný kalendár s bočným odsadením) */
        .flatpickr-calendar {
            background: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 16px !important;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.16), 0 2px 10px rgba(0, 0, 0, 0.05) !important;
            font-family: 'Outfit', sans-serif;
            padding: 10px 12px 12px 12px !important;
            width: 284px !important;
            height: auto !important;
            min-height: 0 !important;
            box-sizing: border-box !important;
            animation: dropdownFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .flatpickr-calendar::before,
        .flatpickr-calendar::after {
            display: none !important;
        }
        .flatpickr-months {
            padding: 0 2px 6px !important;
            align-items: center !important;
            position: relative !important;
        }
        .flatpickr-months .flatpickr-month {
            color: var(--text-primary) !important;
            fill: var(--text-primary) !important;
            height: 34px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .flatpickr-current-month {
            font-size: 1.02rem !important;
            font-weight: 700 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            position: static !important;
            width: auto !important;
            height: 34px !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-current-month .cur-month {
            font-size: 1.08rem !important;
            font-weight: 700 !important;
            color: var(--text-primary) !important;
            text-transform: capitalize !important;
            margin: 0 !important;
            padding: 0 !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            background: transparent !important;
            border: none !important;
            cursor: default !important;
        }
        .flatpickr-current-month .numInputWrapper {
            width: auto !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .flatpickr-current-month .numInputWrapper span.arrowUp,
        .flatpickr-current-month .numInputWrapper span.arrowDown {
            display: none !important;
        }
        .flatpickr-current-month input.cur-year,
        .flatpickr-current-month .numInputWrapper input {
            font-size: 0.85rem !important;
            font-weight: 400 !important;
            color: var(--text-secondary) !important;
            opacity: 0.65 !important;
            padding: 0 !important;
            margin: 0 !important;
            pointer-events: none !important;
            cursor: default !important;
            background: transparent !important;
            line-height: 1 !important;
            height: auto !important;
            border: none !important;
            width: 36px !important;
            text-align: left !important;
        }
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            fill: var(--text-secondary) !important;
            color: var(--text-secondary) !important;
            border-radius: 8px !important;
            padding: 4px !important;
            transition: all 0.2s ease !important;
            top: 2px !important;
            width: 28px !important;
            height: 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .flatpickr-months .flatpickr-prev-month:hover,
        .flatpickr-months .flatpickr-next-month:hover {
            color: var(--primary-color) !important;
            fill: var(--primary-color) !important;
            background: rgba(176, 128, 66, 0.12) !important;
        }
        .flatpickr-innerContainer,
        .flatpickr-rContainer,
        .flatpickr-days {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
        }
        .flatpickr-weekdays {
            height: 26px !important;
            display: flex !important;
            width: 100% !important;
            overflow: hidden !important;
        }
        .flatpickr-weekdaycontainer,
        .dayContainer {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: flex-start !important;
            padding: 0 !important;
            margin: 0 !important;
            outline: 0 !important;
            box-sizing: border-box !important;
        }
        span.flatpickr-weekday {
            color: var(--text-secondary) !important;
            font-weight: 700 !important;
            font-size: 0.74rem !important;
            width: 14.2857143% !important;
            min-width: 14.2857143% !important;
            max-width: 14.2857143% !important;
            flex: 0 0 14.2857143% !important;
            text-align: center !important;
            line-height: 26px !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .flatpickr-day {
            border-radius: 9px !important;
            font-weight: 600 !important;
            font-size: 0.88rem !important;
            color: var(--text-primary) !important;
            border: 1px solid transparent !important;
            width: 14.2857143% !important;
            min-width: 14.2857143% !important;
            max-width: 14.2857143% !important;
            flex: 0 0 14.2857143% !important;
            height: 35px !important;
            line-height: 35px !important;
            margin: 1px 0 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
            transition: all 0.15s ease !important;
        }
        .flatpickr-day:hover {
            background: rgba(176, 128, 66, 0.15) !important;
            color: var(--primary-color) !important;
            border-color: transparent !important;
        }
        .flatpickr-day.today {
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
        }
        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 12px rgba(176, 128, 66, 0.35) !important;
        }
        /* Skrytie dní iných mesiacov (prev drží odsadenie, next sa zbalí na 0) */
        .flatpickr-day.prevMonthDay {
            visibility: hidden !important;
            pointer-events: none !important;
        }
        .flatpickr-day.nextMonthDay {
            display: none !important;
        }

        /* Minulé a nedostupné dni v rámci aktuálneho mesiaca (zreteľné a odlíšené) */
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #94a3b8 !important;
            opacity: 1 !important;
            font-weight: 500 !important;
            cursor: not-allowed !important;
            background: transparent !important;
        }
        body.dark-mode .flatpickr-day.flatpickr-disabled,
        body.dark-mode .flatpickr-day.flatpickr-disabled:hover {
            color: #64748b !important;
            opacity: 1 !important;
        }

        .gps-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-secondary);
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease, transform 0.2s ease;
            padding: 0;
            z-index: 2;
        }
        .gps-btn:hover {
            background: transparent;
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.15);
        }
        .gps-btn .material-symbols-outlined {
            font-size: 21px;
            margin: 0;
            color: inherit;
        }
        .gps-btn.loading .material-symbols-outlined {
            color: var(--primary-color);
            animation: gpsSpin 1s linear infinite;
        }
        @keyframes gpsSpin {
            100% { transform: rotate(360deg); }
        }

        .autocomplete-suggestions {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.04);
            z-index: 1200;
            max-height: 290px;
            overflow-y: auto;
            display: none;
            padding: 6px;
            box-sizing: border-box;
            animation: dropdownFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .autocomplete-group {
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--text-secondary);
            text-transform: uppercase;
            padding: 8px 12px 4px;
            letter-spacing: 0.6px;
        }

        .autocomplete-suggestion {
            padding: 9px 12px;
            cursor: pointer;
            border-radius: 9px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.90rem;
            font-weight: 600;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: left;
        }
        .autocomplete-suggestion:hover {
            background: rgba(176, 128, 66, 0.12);
            color: var(--primary-color);
            padding-left: 16px;
        }
        body.dark-mode .autocomplete-suggestion:hover {
            background: rgba(176, 128, 66, 0.22);
            color: var(--primary-color);
        }
        .autocomplete-suggestion .sug-icon {
            font-size: 19px;
            color: var(--primary-color);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .autocomplete-suggestion .sug-text {
            flex: 1;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            gap: 8px;
        }
        .autocomplete-suggestion .sug-badge {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-secondary);
            background: var(--input-bg);
            padding: 2px 6px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            flex-shrink: 0;
        }

        /* GPS Suggestion Item v štýle Aveino */
        .autocomplete-suggestion.gps-sug-item {
            background: rgba(176, 128, 66, 0.1);
            color: var(--primary-color);
            padding: 10px 14px;
            border: 1px solid rgba(176, 128, 66, 0.2);
            border-radius: 9px;
        }
        .autocomplete-suggestion.gps-sug-item:not(:last-child) {
            margin-bottom: 4px;
        }
        .autocomplete-suggestion.gps-sug-item:hover {
            background: rgba(176, 128, 66, 0.2);
            color: var(--primary-color);
            padding-left: 16px;
        }
        body.dark-mode .autocomplete-suggestion.gps-sug-item {
            background: rgba(176, 128, 66, 0.16);
            border-color: rgba(176, 128, 66, 0.3);
        }
        body.dark-mode .autocomplete-suggestion.gps-sug-item:hover {
            background: rgba(176, 128, 66, 0.28);
        }
        .gps-sug-item .gps-sug-details {
            display: flex;
            flex-direction: column;
            text-align: left;
            line-height: 1.3;
        }
        .gps-sug-item .gps-sug-title {
            font-size: 0.90rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .gps-sug-item .gps-sug-sub {
            font-size: 0.76rem;
            font-weight: 500;
            color: var(--text-secondary);
            opacity: 0.9;
        }

        .filter-input {
            width: 100%;
            height: 44px;
            padding: 0 16px 0 44px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-primary);
            font-size: 0.92rem;
            font-weight: 500;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
            font-family: inherit;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .filter-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(176, 128, 66, 0.15);
        }

        /* Custom Sort Dropdown */
        .custom-dropdown {
            position: relative;
            min-width: 220px;
            max-width: 240px;
            user-select: none;
            flex-shrink: 0;
        }

        .custom-dropdown-trigger {
            width: 100%;
            height: 44px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-primary);
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
            outline: none;
        }

        .custom-dropdown:hover .custom-dropdown-trigger,
        .custom-dropdown.open .custom-dropdown-trigger {
            border-color: var(--primary-color);
        }

        .custom-dropdown.open .custom-dropdown-trigger {
            box-shadow: 0 0 0 3px rgba(176, 128, 66, 0.18);
        }

        .custom-dropdown-trigger .trigger-left {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .custom-dropdown-trigger .trigger-icon {
            font-size: 19px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-dropdown-trigger .dropdown-arrow {
            font-size: 20px;
            color: var(--text-secondary);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), color 0.2s;
            flex-shrink: 0;
        }

        .custom-dropdown.open .dropdown-arrow {
            transform: rotate(180deg);
            color: var(--primary-color);
        }

        .custom-dropdown-menu {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            width: 100%;
            min-width: 220px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 6px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.04);
            z-index: 1050;
            display: none;
            flex-direction: column;
            gap: 2px;
            box-sizing: border-box;
            animation: dropdownFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-6px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .custom-dropdown.open .custom-dropdown-menu {
            display: flex;
        }

        .custom-dropdown-item {
            padding: 9px 12px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.90rem;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            gap: 8px;
        }

        .custom-dropdown-item .item-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .custom-dropdown-item .item-icon {
            font-size: 18px;
            color: var(--text-secondary);
            transition: color 0.18s;
        }

        .custom-dropdown-item:hover {
            background-color: rgba(176, 128, 66, 0.12);
            color: var(--primary-color);
            padding-left: 15px;
        }

        body.dark-mode .custom-dropdown-item:hover {
            background-color: rgba(176, 128, 66, 0.22);
            color: var(--primary-color);
        }

        .custom-dropdown-item:hover .item-icon {
            color: var(--primary-color);
        }

        .custom-dropdown-item.active {
            background-color: rgba(176, 128, 66, 0.15);
            color: var(--primary-color);
            font-weight: 700;
        }

        body.dark-mode .custom-dropdown-item.active {
            background-color: rgba(176, 128, 66, 0.25);
            color: var(--primary-color);
        }

        .custom-dropdown-item.active .item-icon {
            color: var(--primary-color);
        }

        .custom-dropdown-item .check-icon {
            font-size: 17px;
            color: var(--primary-color);
            display: none;
        }

        .custom-dropdown-item.active .check-icon {
            display: inline-flex;
        }

        .btn-search {
            height: 44px;
            padding: 0 24px;
            border-radius: 12px;
            border: none;
            background: var(--primary-color);
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(176, 128, 66, 0.25);
            font-family: inherit;
        }
        .btn-search:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        /* Active filter tags */
        .active-filter-tags {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 4px;
            border-top: none;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .filter-tag a {
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .filter-tag a:hover {
            color: #ef4444;
        }

        /* Breadcrumbs and Headings */
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .breadcrumbs a {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .breadcrumbs a:hover {
            text-decoration: underline;
        }
        .breadcrumbs .sep {
            font-size: 16px;
            color: var(--text-secondary);
        }

        .page-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .page-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-title .material-symbols-outlined {
            font-size: 22px;
            color: var(--primary-color);
        }

        .results-badge {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        /* Establishments Grid (3 Columns) */
        .establishments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        /* Establishment Card */
        .listing-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            text-decoration: none;
            color: var(--text-primary);
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s, border-color 0.2s;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            position: relative;
        }

        .listing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-color);
        }

        .listing-img-wrapper {
            position: relative;
            width: 100%;
            height: 200px;
            background-color: var(--input-bg);
            overflow: hidden;
        }

        .listing-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .listing-card:hover .listing-img-wrapper img {
            transform: scale(1.05);
        }

        /* Favorite Button on Card */
        .fav-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(8px);
            border: none;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 5;
        }

        .fav-btn:hover {
            transform: scale(1.1);
            background: rgba(0, 0, 0, 0.7);
            color: #ff4757;
        }

        .fav-btn .material-symbols-outlined {
            font-size: 20px;
        }

        .listing-content {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .listing-title {
            margin: 0 0 6px 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .listing-salon {
            margin: 0 0 14px 0;
            font-size: 0.88rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .listing-salon .material-symbols-outlined {
            font-size: 16px;
            color: var(--primary-color);
        }

        .listing-bottom {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .listing-price {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(243, 156, 18, 0.12);
            color: #f39c12;
            padding: 4px 8px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .rating-badge .material-symbols-outlined {
            font-size: 15px;
            font-variation-settings: 'FILL' 1;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: 18px;
            border: 1px dashed var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .empty-state-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--input-bg);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .empty-state h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .empty-state p {
            margin: 0;
            color: var(--text-secondary);
            max-width: 400px;
            font-size: 0.95rem;
        }

        /* Backdrop for mobile drawer */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
            z-index: 998;
        }
        .sidebar-backdrop.show {
            display: block;
        }

        /* ========================================================
           BOTTOM PAGINATION & NAVIGATION BAR (Aveino style)
           ======================================================== */
        .bottom-pagination-bar {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 36px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            flex-wrap: wrap;
            gap: 16px;
        }

        .btn-scroll-top {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            transition: background 0.2s, color 0.2s;
            font-family: inherit;
        }
        .btn-scroll-top:hover {
            background: var(--input-bg);
            color: var(--primary-color);
        }
        .btn-scroll-top .material-symbols-outlined {
            font-size: 18px;
        }

        .btn-load-more-pill {
            background: linear-gradient(135deg, #c5975c 0%, var(--primary-color, #b08042) 100%);
            color: #ffffff !important;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 10px 24px;
            border-radius: 12px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(176, 128, 66, 0.25);
            transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-load-more-pill:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #b08042 0%, var(--primary-hover, #916630) 100%);
            box-shadow: 0 6px 20px rgba(176, 128, 66, 0.45);
        }

        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .page-num {
            min-width: 36px;
            height: 36px;
            padding: 0 8px;
            border-radius: 10px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .page-num:hover:not(.active):not(.disabled) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--card-bg);
        }
        .page-num.active {
            background: #1e293b;
            color: #ffffff !important;
            border-color: #1e293b;
            font-weight: 800;
        }
        body.dark-mode .page-num.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #ffffff !important;
        }
        .page-num.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        .page-dots {
            color: var(--text-secondary);
            font-weight: 700;
            padding: 0 4px;
        }

        @media (max-width: 768px) {
            .bottom-pagination-bar {
                flex-direction: column;
                justify-content: center;
                text-align: center;
            }
        }
        /* Pätička (Footer) — štýly sú v assets/css/site-footer.css (zdieľané s index.php/prevadzky.php) */


        /* Responsive Breakpoints */
        @media (max-width: 640px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-input-wrapper,
            .filter-location-wrapper,
            .filter-date-wrapper,
            .custom-dropdown,
            .btn-search {
                width: 100% !important;
                min-width: 100% !important;
            }
            .establishments-grid {
                grid-template-columns: 1fr;
            }
            .page-body {
                padding: 16px 16px 40px;
            }
        }
    </style>
</head>
<body>

    <!-- SCREEN-LEFT HOVER TRIGGER (Aveino style) -->
    <div id="sidebar-hover-trigger"></div>

    <!-- MOBILE SIDEBAR BACKDROP -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleMobileSidebar()"></div>

    <!-- APP LAYOUT (Sidebar Pinned by Default on Desktop) -->
    <div class="app-layout sidebar-pinned" id="app-layout">

        <!-- FULL HEIGHT LEFT SIDEBAR (Aveino style) -->
        <aside id="app-sidebar" class="app-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo" title="<?= BRAND_SITE ?>">
                    <?php if (BRAND_NAME === 'Rezervos'): ?>
                    <img src="/rezervoslogo.png"     alt="Rezervos" class="rezervos-pv-logo rezervos-pv-light">
                    <img src="/rezervoslogodark.png" alt="Rezervos" class="rezervos-pv-logo rezervos-pv-dark">
                    <span class="rezervos-pv-text-html rezervos-pv-light-text"><span style="color:#0f172a;">rezer</span><span style="color:#b08042;">vos</span></span>
                    <span class="rezervos-pv-text-html rezervos-pv-dark-text"><span style="color:#e2e8f0;">rezer</span><span style="color:#d4a050;">vos</span></span>
                    <?php else: ?>
                    <img src="<?= defined('BRAND_LOGO_DARK') ? BRAND_LOGO_DARK : BRAND_LOGO ?>" alt="<?= BRAND_NAME ?> Logo">
                    <span class="logo-text"><?= BRAND_LOGO_TEXT ?></span>
                    <?php endif; ?>
                </a>
                <button class="sidebar-toggle" id="sidebar-toggle" title="Pripnúť / Odopnúť bočný panel">
                    <span class="material-symbols-outlined" id="sidebar-toggle-icon" style="font-size: 20px;">push_pin</span>
                </button>
            </div>

            <div class="sidebar-section-title">Inzercia</div>

            <nav class="sidebar-scroll-area">
                <!-- Späť na vyhľadávanie prevádzok -->
                <a href="prevadzky.php" class="category-nav-item">
                    <span class="material-symbols-outlined nav-icon">arrow_back</span>
                    <span>Späť na Prevádzky</span>
                </a>

                <div style="height:1px;background:var(--border-color);margin:10px 16px;"></div>

                <!-- 5 typov inzerátov -->
                <?php foreach ($classified_types as $type_key => $type_data): ?>
                    <?php $is_active = ($active_type === $type_key); ?>
                    <a href="inzercia.php?type=<?php echo urlencode($type_key); ?>" class="category-nav-item <?php echo $is_active ? 'active' : ''; ?>" data-type="<?php echo htmlspecialchars($type_key); ?>">
                        <span class="material-symbols-outlined nav-icon"><?php echo $type_data['icon']; ?></span>
                        <span><?php echo htmlspecialchars($type_data['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="app-main-content">
            
            <!-- STICKY TOPBAR -->
            <header class="app-topbar">
                <div class="topbar-left">
                    <!-- Toggle sidebar icon button (dock_to_left / left_panel_close / left_panel_open) -->
                    <button id="topbar-sidebar-toggle" title="Zobraziť / Skryť bočný panel">
                        <span class="material-symbols-outlined" id="topbar-sidebar-icon" style="font-size: 22px;">left_panel_close</span>
                    </button>

                    <!-- Domov link -->
                    <a href="index.php" class="top-btn icon-only" title="Domov">
                        <span class="material-symbols-outlined">home</span>
                    </a>
                </div>

                <div class="topbar-right">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'business'): ?>
                            <a href="dashboard.php" class="top-btn" title="Moja Prevádzka">
                                <span class="material-symbols-outlined">storefront</span>
                            </a>
                        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin.php" class="top-btn" title="Admin Panel">
                                <span class="material-symbols-outlined">admin_panel_settings</span>
                            </a>
                        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
                            <a href="moj_profil.php" class="top-btn" title="Môj Profil">
                                <span class="material-symbols-outlined">person</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Tmavý režim -->
                        <button id="theme-toggle" class="top-btn theme-btn" aria-label="Toggle Dark Mode" title="Tmavý režim">
                            <span class="material-symbols-outlined">dark_mode</span>
                        </button>
                        <a href="logout.php" class="top-btn logout-btn" title="Odhlásiť sa">
                            <span class="material-symbols-outlined">logout</span>
                        </a>
                    <?php else: ?>
                        <!-- Tmavý režim -->
                        <button id="theme-toggle" class="top-btn theme-btn" aria-label="Toggle Dark Mode" title="Tmavý režim">
                            <span class="material-symbols-outlined">dark_mode</span>
                        </button>
                        <a href="#" onclick="openAuthModal(); return false;" class="top-btn login-btn">
                            <span class="material-symbols-outlined">login</span>
                            <span><?php echo $translations['login_btn'] ?? 'Prihlásiť sa'; ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- PAGE BODY -->
            <div class="page-body">

                <style>
                    .inz-type-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
                    .inz-type-tab {
                        display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:12px;
                        border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-secondary);
                        font-family:inherit; font-size:13.5px; font-weight:700; text-decoration:none; transition:all 0.15s;
                    }
                    .inz-type-tab:hover { border-color:var(--primary-color); color:var(--primary-color); }
                    .inz-type-tab.active { border-color:var(--primary-color); background:rgba(176,128,66,0.08); color:var(--primary-color); }
                    .inz-public-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:24px; }
                    @media (max-width: 768px) {
                        .inz-public-grid { grid-template-columns: 1fr; }
                    }
                    .inz-listing-card {
                        background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px;
                        overflow: hidden; display: flex; flex-direction: column; transition: all 0.2s;
                        text-decoration: none; color: inherit;
                    }
                    .inz-listing-card:hover {
                        border-color: var(--primary-color); box-shadow: 0 4px 16px rgba(176,128,66,0.13); transform: translateY(-1px);
                    }
                    .inz-listing-card .card-body { padding: 16px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
                </style>

                <!-- Search & Filter Panel -->
                <div class="filter-panel">
                    <form method="GET" action="inzercia.php" class="filter-form">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($active_type); ?>">

                        <div class="filter-input-wrapper">
                            <span class="material-symbols-outlined input-icon">search</span>
                            <input type="text"
                                   name="q"
                                   class="filter-input"
                                   placeholder="Aký inzerát hľadáte?"
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   autocomplete="off">
                        </div>

                        <div class="filter-location-wrapper">
                            <span class="material-symbols-outlined input-icon">location_on</span>
                            <input type="text"
                                   name="city"
                                   class="filter-input"
                                   placeholder="Mesto alebo PSČ"
                                   value="<?php echo htmlspecialchars($city); ?>"
                                   autocomplete="off">
                        </div>

                        <button type="submit" class="btn-search">
                            <span class="material-symbols-outlined">search</span>
                            <span>Hľadať</span>
                        </button>
                    </form>
                </div>

                <!-- Typové taby -->
                <div class="inz-type-tabs">
                    <?php foreach ($classified_types as $type_key => $type_data): ?>
                        <a href="inzercia.php?type=<?php echo urlencode($type_key); ?><?php echo !empty($search_query) ? '&q='.urlencode($search_query) : ''; ?><?php echo !empty($city) ? '&city='.urlencode($city) : ''; ?>" class="inz-type-tab <?php echo $active_type === $type_key ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined"><?php echo $type_data['icon']; ?></span>
                            <span><?php echo htmlspecialchars($type_data['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Breadcrumbs -->
                <div class="breadcrumbs">
                    <a href="index.php">
                        <span class="material-symbols-outlined" style="font-size: 16px;">home</span>
                        Domov
                    </a>
                    <span class="material-symbols-outlined sep">chevron_right</span>
                    <a href="inzercia.php">Inzercia</a>
                    <span class="material-symbols-outlined sep">chevron_right</span>
                    <span><?php echo htmlspecialchars($classified_types[$active_type]['label']); ?></span>
                </div>

                <!-- Title + CTA -->
                <div class="page-title-row">
                    <h1 class="page-title">
                        <span class="material-symbols-outlined">newspaper</span>
                        <span><?php echo htmlspecialchars($classified_types[$active_type]['label']); ?><span id="inz-count-suffix"></span></span>
                    </h1>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'business'): ?>
                        <a href="dashboard-inzercia.php" class="btn-search" style="text-decoration: none;">
                            <span class="material-symbols-outlined">add</span>
                            <span>Pridať inzerát</span>
                        </a>
                    <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin-inzercia.php" class="btn-search" style="text-decoration: none;">
                            <span class="material-symbols-outlined">add</span>
                            <span>Pridať inzerát</span>
                        </a>
                    <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'customer'): ?>
                        <a href="moj_profil-inzercia.php" class="btn-search" style="text-decoration: none;">
                            <span class="material-symbols-outlined">add</span>
                            <span>Pridať inzerát</span>
                        </a>
                    <?php else: ?>
                        <a href="#" onclick="openAuthModal(); return false;" class="btn-search" style="text-decoration: none;">
                            <span class="material-symbols-outlined">login</span>
                            <span>Prihlásiť sa a pridať inzerát</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Grid inzerátov (načítané cez api/classifieds.php) -->
                <div id="inz-public-grid" class="inz-public-grid">
                    <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-secondary);">Načítavam inzeráty...</div>
                </div>

            </div>

            <!-- SITE FOOTER -->
            <?php include 'includes/site-footer.php'; ?>
        </div>
    </div>

    <!-- MODAL PRE EKOLOGICKÚ STOPU -->
    <div id="eco-info-modal" style="display: none; position: fixed; z-index: 10003; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
        <div style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; border-radius: 24px; width: 100%; max-width: 520px; box-shadow: 0 20px 50px rgba(0,0,0,0.4); position: relative; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-sizing: border-box;">
            <button onclick="closeEcoModal()" style="position: absolute; right: 18px; top: 18px; background: none; border: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 5px; border-radius: 50%; transition: background 0.2s;">
                <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
            </button>
            
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: rgba(76, 175, 80, 0.1); border-radius: 50%; margin-bottom: 12px;">
                    <span class="material-symbols-outlined" style="color: #4CAF50; font-size: 32px;">eco</span>
                </div>
                <h3 style="margin: 0; font-size: 22px; font-weight: 800; color: var(--text-primary);">
                    Naša digitálna stopa
                </h3>
                <p style="color: #4CAF50; font-size: 13.5px; font-weight: 700; margin: 4px 0 0 0;">
                    Chránime našu planétu
                </p>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
                <div style="display: flex; gap: 14px; background: var(--input-bg); border: 1px solid var(--border-color); padding: 14px; border-radius: 16px;">
                    <div style="color: #4CAF50; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(76, 175, 80, 0.08); border-radius: 10px; flex-shrink: 0;">
                        <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 20px;">cloud</span>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: var(--text-primary);">Udržateľná technológia</h4>
                        <p style="margin: 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.4; font-weight: 500;">
                            Naša infraštruktúra je prevádzkovaná s ohľadom na životné prostredie. Využívame riešenia poháňané energiou z obnoviteľných zdrojov, čím minimalizujeme uhlíkovú stopu.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 14px; background: var(--input-bg); border: 1px solid var(--border-color); padding: 14px; border-radius: 16px;">
                    <div style="color: #4CAF50; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(76, 175, 80, 0.08); border-radius: 10px; flex-shrink: 0;">
                        <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 20px;">speed</span>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: var(--text-primary);">Vysoká optimalizácia dát</h4>
                        <p style="margin: 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.4; font-weight: 500;">
                            Pokročilá optimalizácia a kompresia nahraných obrázkov minimalizuje prenesené dáta. Menší dátový tok priamo šetrí energiu na vašom zariadení aj v prenosových sieťach.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 14px; background: var(--input-bg); border: 1px solid var(--border-color); padding: 14px; border-radius: 16px;">
                    <div style="color: #4CAF50; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(76, 175, 80, 0.08); border-radius: 10px; flex-shrink: 0;">
                        <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 20px;">dark_mode</span>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: var(--text-primary);">Úsporný Tmavý Režim</h4>
                        <p style="margin: 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.4; font-weight: 500;">
                            Predvolený tmavý režim (Tokyo Night) výrazne znižuje spotrebu energie OLED/AMOLED displejov na smartfónoch a notebookoch.
                        </p>
                    </div>
                </div>
            </div>
            
            <button onclick="closeEcoModal()" class="btn" style="width: 100%; height: 44px; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 12px; background: #4CAF50; color: #fff; box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 18px;">done</span> Rozumiem
            </button>
        </div>
    </div>

    <!-- Modals -->
    <?php include_once 'includes/auth_modal.php'; ?>
    <?php include_once 'includes/ai_chat_modal.php'; ?>

    <!-- Aveino Full Sidebar & Interaction JS -->
    <script>
        const appLayout = document.getElementById('app-layout');
        const appSidebar = document.getElementById('app-sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const topbarSidebarToggle = document.getElementById('topbar-sidebar-toggle');
        const sidebarToggleIcon = document.getElementById('sidebar-toggle-icon');
        const topbarSidebarIcon = document.getElementById('topbar-sidebar-icon');
        const hoverTrigger = document.getElementById('sidebar-hover-trigger');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        function isPinned() {
            return appLayout.classList.contains('sidebar-pinned');
        }

        function updateSidebarState(pinned) {
            if (pinned) {
                appLayout.classList.add('sidebar-pinned');
                appSidebar.classList.remove('hover-open');
                localStorage.setItem('sidebar_pinned', 'true');
                if (sidebarToggleIcon) {
                    sidebarToggleIcon.style.transform = 'rotate(0deg)';
                    sidebarToggleIcon.style.color = 'var(--primary-color)';
                }
                if (topbarSidebarIcon) {
                    topbarSidebarIcon.textContent = 'left_panel_close';
                }
            } else {
                appLayout.classList.remove('sidebar-pinned');
                localStorage.setItem('sidebar_pinned', 'false');
                if (sidebarToggleIcon) {
                    sidebarToggleIcon.style.transform = 'rotate(45deg)';
                    sidebarToggleIcon.style.color = 'var(--text-secondary)';
                }
                if (topbarSidebarIcon) {
                    topbarSidebarIcon.textContent = 'left_panel_open';
                }
            }
        }

        // Initialize state from localStorage and restore sidebar scroll
        document.addEventListener('DOMContentLoaded', () => {
            if (window.innerWidth > 1024) {
                const savedPinned = localStorage.getItem('sidebar_pinned');
                if (savedPinned === 'false') {
                    updateSidebarState(false);
                } else {
                    updateSidebarState(true);
                }
            } else {
                updateSidebarState(false);
            }

            // Restore sidebar scroll position
            const sidebarScrollArea = document.querySelector('.sidebar-scroll-area');
            if (sidebarScrollArea) {
                const savedScroll = sessionStorage.getItem('sidebar_scroll_pos');
                if (savedScroll !== null) {
                    sidebarScrollArea.scrollTop = parseInt(savedScroll, 10);
                } else {
                    const activeItem = sidebarScrollArea.querySelector('.category-nav-item.active');
                    if (activeItem) {
                        activeItem.scrollIntoView({ block: 'center', inline: 'nearest' });
                    }
                }

                sidebarScrollArea.addEventListener('scroll', () => {
                    sessionStorage.setItem('sidebar_scroll_pos', sidebarScrollArea.scrollTop);
                }, { passive: true });

                const navLinks = sidebarScrollArea.querySelectorAll('.category-nav-item');
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        sessionStorage.setItem('sidebar_scroll_pos', sidebarScrollArea.scrollTop);
                    });
                });

                window.addEventListener('beforeunload', () => {
                    sessionStorage.setItem('sidebar_scroll_pos', sidebarScrollArea.scrollTop);
                });
            }
        });

        // Pin button inside sidebar
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                updateSidebarState(!isPinned());
            });
        }

        // Topbar toggle button
        if (topbarSidebarToggle) {
            topbarSidebarToggle.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    toggleMobileSidebar();
                } else {
                    updateSidebarState(!isPinned());
                }
            });
        }

        // Mobile drawer toggle
        function toggleMobileSidebar() {
            const isOpen = appSidebar.classList.toggle('mobile-open');
            sidebarBackdrop.classList.toggle('show', isOpen);
            if (topbarSidebarIcon && window.innerWidth <= 1024) {
                topbarSidebarIcon.textContent = isOpen ? 'close' : 'left_panel_open';
            }
        }

        // Screen-left hover zone
        if (hoverTrigger && appSidebar) {
            hoverTrigger.addEventListener('mouseenter', () => {
                if (!isPinned() && window.innerWidth > 1024) {
                    appSidebar.classList.add('hover-open');
                }
            });

            appSidebar.addEventListener('mouseleave', () => {
                if (!isPinned() && window.innerWidth > 1024) {
                    appSidebar.classList.remove('hover-open');
                }
            });
        }

        // Window resize adjustments
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                appSidebar.classList.remove('mobile-open');
                sidebarBackdrop.classList.remove('show');
                const savedPinned = localStorage.getItem('sidebar_pinned');
                updateSidebarState(savedPinned !== 'false');
            } else {
                updateSidebarState(false);
            }
        });

        function openEcoModal() {
            document.getElementById('eco-info-modal').style.display = 'flex';
        }
        function closeEcoModal() {
            document.getElementById('eco-info-modal').style.display = 'none';
        }

        // Custom Sort Dropdown Handler
        const sortDropdown = document.getElementById('sort-dropdown');
        const sortTrigger = document.getElementById('sort-dropdown-trigger');
        const sortInput = document.getElementById('sort-input');

        if (sortDropdown && sortTrigger) {
            sortTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = sortDropdown.classList.toggle('open');
                sortTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            sortTrigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sortTrigger.click();
                } else if (e.key === 'Escape') {
                    sortDropdown.classList.remove('open');
                    sortTrigger.setAttribute('aria-expanded', 'false');
                }
            });

            const sortItems = sortDropdown.querySelectorAll('.custom-dropdown-item');
            sortItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const val = this.getAttribute('data-value');
                    sortInput.value = val;
                    sortDropdown.classList.remove('open');
                    sortTrigger.setAttribute('aria-expanded', 'false');
                    
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('sort', val);
                    currentUrl.searchParams.set('page', '1');
                    window.location.href = currentUrl.toString();
                });
            });

            document.addEventListener('click', (e) => {
                if (!sortDropdown.contains(e.target)) {
                    sortDropdown.classList.remove('open');
                    sortTrigger.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && sortDropdown.classList.contains('open')) {
                    sortDropdown.classList.remove('open');
                    sortTrigger.setAttribute('aria-expanded', 'false');
                }
            });
        }

        function toggleFavorite(btn, estId) {
            const icon = btn.querySelector('.material-symbols-outlined');
            if (icon.style.fontVariationSettings && icon.style.fontVariationSettings.includes("'FILL' 1")) {
                icon.style.fontVariationSettings = "'FILL' 0";
                btn.style.color = '#ffffff';
            } else {
                icon.style.fontVariationSettings = "'FILL' 1";
                btn.style.color = '#ff4757';
            }
        }

        // Autocomplete & GPS Logic pre prevadzky.php
        const filterSearchService = document.getElementById('filterSearchService');
        const filterServiceSuggestions = document.getElementById('filterServiceSuggestions');
        const filterSearchLocation = document.getElementById('filterSearchLocation');
        const filterLocationSuggestions = document.getElementById('filterLocationSuggestions');
        const filterBtnGps = document.getElementById('filterBtnGps');
        
        let filterServiceTimeout = null;
        let filterLocationTimeout = null;
        
        // Autocomplete Služby & Kategórie
        if (filterSearchService && filterServiceSuggestions) {
            filterSearchService.addEventListener('input', function() {
                clearTimeout(filterServiceTimeout);
                const val = this.value.trim();
                if (val.length < 2) {
                    filterServiceSuggestions.style.display = 'none';
                    return;
                }
                filterServiceTimeout = setTimeout(async () => {
                    try {
                        const res = await fetch('api/search_services.php?q=' + encodeURIComponent(val));
                        const data = await res.json();
                        if (data.success && data.data) {
                            filterServiceSuggestions.innerHTML = '';
                            let hasResults = false;
                            
                            // Zobrazenie Kategórií
                            if (data.data['Kategórie'] && data.data['Kategórie'].length > 0) {
                                hasResults = true;
                                const grp = document.createElement('div');
                                grp.className = 'autocomplete-group';
                                grp.textContent = 'Kategórie';
                                filterServiceSuggestions.appendChild(grp);
                                
                                data.data['Kategórie'].forEach(item => {
                                    const div = document.createElement('div');
                                    div.className = 'autocomplete-suggestion';
                                    div.innerHTML = `
                                        <span class="material-symbols-outlined sug-icon">${item.icon || 'category'}</span>
                                        <div class="sug-text">
                                            <span>${item.name}</span>
                                            <span class="sug-badge">Kategória</span>
                                        </div>
                                    `;
                                    div.onclick = () => {
                                        window.location.href = 'prevadzky.php?category=' + encodeURIComponent(item.name);
                                    };
                                    filterServiceSuggestions.appendChild(div);
                                });
                            }
                            
                            // Zobrazenie Služieb
                            if (data.data['Služby'] && data.data['Služby'].length > 0) {
                                hasResults = true;
                                const grp = document.createElement('div');
                                grp.className = 'autocomplete-group';
                                grp.textContent = 'Služby';
                                filterServiceSuggestions.appendChild(grp);
                                
                                data.data['Služby'].forEach(item => {
                                    const div = document.createElement('div');
                                    div.className = 'autocomplete-suggestion';
                                    div.innerHTML = `
                                        <span class="material-symbols-outlined sug-icon">${item.icon || 'room_service'}</span>
                                        <div class="sug-text">
                                            <span>${item.name}</span>
                                            <span class="sug-badge">Služba</span>
                                        </div>
                                    `;
                                    div.onclick = () => {
                                        filterSearchService.value = item.name;
                                        filterServiceSuggestions.style.display = 'none';
                                        filterSearchService.closest('form').submit();
                                    };
                                    filterServiceSuggestions.appendChild(div);
                                });
                            }

                            // Zobrazenie Salónov
                            if (data.data['Salóny'] && data.data['Salóny'].length > 0) {
                                hasResults = true;
                                const grp = document.createElement('div');
                                grp.className = 'autocomplete-group';
                                grp.textContent = 'Salóny a Prevádzky';
                                filterServiceSuggestions.appendChild(grp);
                                
                                data.data['Salóny'].forEach(item => {
                                    const div = document.createElement('div');
                                    div.className = 'autocomplete-suggestion';
                                    div.innerHTML = `
                                        <span class="material-symbols-outlined sug-icon">${item.icon || 'storefront'}</span>
                                        <div class="sug-text">
                                            <span>${item.name}</span>
                                            <span class="sug-badge">${item.city || 'Salón'}</span>
                                        </div>
                                    `;
                                    div.onclick = () => {
                                        filterSearchService.value = item.name;
                                        filterServiceSuggestions.style.display = 'none';
                                        filterSearchService.closest('form').submit();
                                    };
                                    filterServiceSuggestions.appendChild(div);
                                });
                            }
                            
                            filterServiceSuggestions.style.display = hasResults ? 'block' : 'none';
                        } else {
                            filterServiceSuggestions.style.display = 'none';
                        }
                    } catch(e) { console.error(e); }
                }, 250);
            });
        }
        
        // Funkcia na zistenie GPS polohy a automatické odoslanie filtra
        function triggerFilterGps() {
            if (!navigator.geolocation) {
                if (filterLocationSuggestions) {
                    filterLocationSuggestions.innerHTML = '<div class="autocomplete-suggestion" style="color: #ef4444;"><span class="material-symbols-outlined sug-icon" style="color: #ef4444;">error</span><span>Prehliadač nepodporuje GPS.</span></div>';
                    filterLocationSuggestions.style.display = 'block';
                    setTimeout(() => { filterLocationSuggestions.style.display = 'none'; }, 3000);
                }
                return;
            }

            if (filterBtnGps) {
                filterBtnGps.classList.add('loading');
                const icon = filterBtnGps.querySelector('.material-symbols-outlined');
                if (icon) icon.textContent = 'sync';
            }

            if (filterLocationSuggestions) {
                filterLocationSuggestions.innerHTML = `
                    <div class="autocomplete-suggestion gps-sug-item" style="cursor: default;">
                        <span class="material-symbols-outlined sug-icon" style="animation: gpsSpin 1s linear infinite;">sync</span>
                        <div class="gps-sug-details">
                            <span class="gps-sug-title">Zisťujem vašu polohu...</span>
                            <span class="gps-sug-sub">(Čaká sa na GPS súradnice)</span>
                        </div>
                    </div>
                `;
                filterLocationSuggestions.style.display = 'block';
            }

            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    const lat = pos.coords.latitude;
                    const lon = pos.coords.longitude;
                    try {
                        const res = await fetch(`api/search_locations.php?lat=${lat}&lon=${lon}`);
                        const data = await res.json();
                        if (data.success && data.data) {
                            filterSearchLocation.value = data.data.city_name;
                            if (filterLocationSuggestions) filterLocationSuggestions.style.display = 'none';
                            filterSearchLocation.closest('form').submit();
                        } else if (filterLocationSuggestions) {
                            filterLocationSuggestions.innerHTML = '<div class="autocomplete-suggestion" style="color: #ef4444;"><span class="material-symbols-outlined sug-icon" style="color: #ef4444;">location_off</span><span>Nenašla sa žiadna obec v okolí.</span></div>';
                            filterLocationSuggestions.style.display = 'block';
                            setTimeout(() => { filterLocationSuggestions.style.display = 'none'; }, 3000);
                        }
                    } catch (err) {
                        console.error(err);
                    } finally {
                        if (filterBtnGps) {
                            filterBtnGps.classList.remove('loading');
                            const icon = filterBtnGps.querySelector('.material-symbols-outlined');
                            if (icon) icon.textContent = 'my_location';
                        }
                    }
                },
                (error) => {
                    if (filterBtnGps) {
                        filterBtnGps.classList.remove('loading');
                        const icon = filterBtnGps.querySelector('.material-symbols-outlined');
                        if (icon) icon.textContent = 'my_location';
                    }
                    if (filterLocationSuggestions) {
                        filterLocationSuggestions.innerHTML = '<div class="autocomplete-suggestion" style="color: #ef4444;"><span class="material-symbols-outlined sug-icon" style="color: #ef4444;">lock</span><span>Prístup k polohe bol zamietnutý.</span></div>';
                        filterLocationSuggestions.style.display = 'block';
                        setTimeout(() => { filterLocationSuggestions.style.display = 'none'; }, 3500);
                    }
                },
                { timeout: 10000, enableHighAccuracy: true }
            );
        }

        function renderFilterGpsDropdownItem() {
            if (!filterLocationSuggestions) return;
            filterLocationSuggestions.innerHTML = `
                <div class="autocomplete-suggestion gps-sug-item" id="filterGpsSuggestOption">
                    <span class="material-symbols-outlined sug-icon">my_location</span>
                    <div class="gps-sug-details">
                        <span class="gps-sug-title">Prevádzky v okolí</span>
                        <span class="gps-sug-sub">(Zistiť moju polohu)</span>
                    </div>
                </div>
            `;
            const opt = document.getElementById('filterGpsSuggestOption');
            if (opt) {
                opt.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    triggerFilterGps();
                };
            }
        }

        // Autocomplete Lokalita & PSČ + Okamžitá ponuka "Zistiť moju polohu"
        if (filterSearchLocation && filterLocationSuggestions) {
            filterSearchLocation.addEventListener('focus', function() {
                if (this.value.trim().length < 2) {
                    renderFilterGpsDropdownItem();
                    filterLocationSuggestions.style.display = 'block';
                }
            });

            filterSearchLocation.addEventListener('click', function(e) {
                e.stopPropagation();
                if (this.value.trim().length < 2) {
                    renderFilterGpsDropdownItem();
                    filterLocationSuggestions.style.display = 'block';
                }
            });

            filterSearchLocation.addEventListener('input', function() {
                clearTimeout(filterLocationTimeout);
                const val = this.value.trim();
                if (val.length < 2) {
                    renderFilterGpsDropdownItem();
                    filterLocationSuggestions.style.display = 'block';
                    return;
                }
                filterLocationTimeout = setTimeout(async () => {
                    try {
                        const res = await fetch('api/search_locations.php?q=' + encodeURIComponent(val));
                        const data = await res.json();
                        filterLocationSuggestions.innerHTML = '';
                        
                        // Vždy na vrchu ponúkneme zistenie polohy
                        renderFilterGpsDropdownItem();

                        if (data.success && data.data && data.data.length > 0) {
                            data.data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'autocomplete-suggestion';
                                const countryTag = (item.country && item.country !== 'SK') ? ` [${item.country}]` : '';
                                const adminTag = item.admin_name ? ` (${item.admin_name})` : '';
                                div.innerHTML = `
                                    <span class="material-symbols-outlined sug-icon">location_on</span>
                                    <div class="sug-text">
                                        <span><strong>${item.city_name}</strong>${countryTag}, ${item.zip_code}${adminTag}</span>
                                    </div>
                                `;
                                div.onclick = (e) => {
                                    e.stopPropagation();
                                    filterSearchLocation.value = item.city_name;
                                    filterLocationSuggestions.style.display = 'none';
                                    filterSearchLocation.closest('form').submit();
                                };
                                filterLocationSuggestions.appendChild(div);
                            });
                        }
                        filterLocationSuggestions.style.display = 'block';
                    } catch(e) { console.error(e); }
                }, 250);
            });
        }
        
        // Klik mimo zatvorí dropdowny
        document.addEventListener('click', function(e) {
            if (filterSearchService && filterServiceSuggestions && !filterSearchService.contains(e.target) && !filterServiceSuggestions.contains(e.target)) {
                filterServiceSuggestions.style.display = 'none';
            }
            if (filterSearchLocation && filterLocationSuggestions && !filterSearchLocation.contains(e.target) && !filterLocationSuggestions.contains(e.target) && (!filterBtnGps || !filterBtnGps.contains(e.target))) {
                filterLocationSuggestions.style.display = 'none';
            }
        });
        
        // GPS Radar Lokalizácia
        if (filterBtnGps && filterSearchLocation) {
            filterBtnGps.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                triggerFilterGps();
            });
        }

        // Flatpickr initialization
        const filterSearchDate = document.getElementById('filterSearchDate');
        const filterDateIcon = document.getElementById('filterDateIcon');
        const filterDateWrapper = document.getElementById('filterDateWrapper');

        if (filterSearchDate && typeof flatpickr !== 'undefined') {
            const fp = flatpickr(filterSearchDate, {
                locale: "sk",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d. m. Y",
                altInputClass: "filter-input filter-date-input",
                allowInput: false,
                minDate: "today",
                disableMobile: "true",
                monthSelectorType: "static",
                showMonths: 1,
                defaultDate: null,
                onReady: function(selectedDates, dateStr, instance) {
                    if (instance.altInput) {
                        instance.altInput.placeholder = "Vyberte voľný termín";
                        instance.altInput.style.paddingLeft = "44px";
                    }
                }
            });

            if (filterDateIcon) {
                filterDateIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    fp.open();
                });
            }
            if (filterDateWrapper) {
                filterDateWrapper.addEventListener('click', function(e) {
                    fp.open();
                });
            }
        }

        window.addEventListener('click', function(e) {
            const ecoModal = document.getElementById('eco-info-modal');
            if (e.target === ecoModal) {
                closeEcoModal();
            }
        });
    </script>

    <!-- Verejný zoznam inzerátov (Inzercia) -->
    <script>
        const INZ_ACTIVE_TYPE = <?php echo json_encode($active_type); ?>;
        const INZ_SEARCH_QUERY = <?php echo json_encode($search_query); ?>;
        const INZ_SEARCH_CITY = <?php echo json_encode($city); ?>;

        function inzEscHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function inzFormatDate(str) {
            if (!str) return '';
            try { return new Date(str).toLocaleDateString('sk-SK'); } catch (e) { return str; }
        }

        function inzFirstImage(item) {
            const imgs = item.images_arr || [];
            if (!imgs.length) return '';
            return `<div style="height:160px;overflow:hidden;background:var(--bg-color);">
                <img src="/${imgs[0]}" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy" onerror="this.parentElement.style.display='none'">
            </div>`;
        }

        function inzEmptyState(icon, text) {
            return `<div style="grid-column:1/-1;text-align:center;padding:48px 20px;color:var(--text-secondary);">
                <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.35;">${icon}</span>
                <p style="margin:0;font-size:14px;">${text}</p>
            </div>`;
        }

        function inzRenderCard(type, i) {
            const imgHtml = inzFirstImage(i);
            const isBoosted = i.boosted_until && new Date(i.boosted_until) > new Date();
            let extra = '';

            if (type === 'work') {
                const isSeek = i.listing_type === 'seek';
                const badgeC = isSeek ? '#3b82f6' : '#10b981';
                const badgeT = isSeek ? 'Hľadám' : 'Ponúkam';
                extra = `<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                    <span style="background:${badgeC};color:#fff;font-size:10.5px;font-weight:800;padding:3px 9px;border-radius:6px;">${badgeT}</span>
                </div>
                ${i.specialization ? `<div style="font-size:12px;color:var(--primary-color);font-weight:600;">${inzEscHtml(i.specialization)}</div>` : ''}`;
            } else if (type === 'rental') {
                extra = `${i.price ? `<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} € ${i.price_unit ? '/ ' + inzEscHtml(i.price_unit) : ''}</strong>` : ''}
                ${i.available_from ? `<span style="font-size:12px;color:var(--text-secondary);display:block;">Dostupné od: ${i.available_from}</span>` : ''}`;
            } else if (type === 'sale') {
                extra = `<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    ${i.price ? `<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} €</strong>` : ''}
                    ${i.condition ? `<span style="background:rgba(176,128,66,0.1);color:var(--primary-color);font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;">${inzEscHtml(i.condition)}</span>` : ''}
                </div>`;
            } else if (type === 'courses') {
                extra = `<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    ${i.price ? `<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} €</strong>` : ''}
                    ${i.available_from ? `<span style="font-size:12px;color:var(--text-secondary);">${i.available_from}</span>` : ''}
                </div>`;
            } else if (type === 'other') {
                extra = i.price ? `<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} €</strong>` : '';
            }

            return `<a href="inzerat.php?id=${i.id}" class="inz-listing-card" style="${isBoosted ? 'border-color:var(--primary-color);box-shadow:0 4px 16px rgba(176,128,66,0.18);' : ''}">
                ${isBoosted ? `<div style="background:linear-gradient(90deg,var(--primary-color),#d4af37);color:#fff;font-size:11px;font-weight:800;padding:4px 12px;display:flex;align-items:center;gap:5px;"><span class="material-symbols-outlined" style="font-size:14px;">rocket_launch</span>TOP</div>` : ''}
                ${imgHtml}
                <div class="card-body">
                    <h4 style="margin:0;font-size:14.5px;font-weight:800;color:var(--text-primary);line-height:1.3;">${inzEscHtml(i.title)}</h4>
                    ${i.description ? `<p style="margin:0;font-size:12.5px;color:var(--text-secondary);line-height:1.4;">${inzEscHtml(i.description.substring(0,110))}${i.description.length > 110 ? '…' : ''}</p>` : ''}
                    ${extra}
                    <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:12px;color:var(--text-secondary);margin-top:auto;">
                        ${i.location ? `<span><span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">location_on</span> ${inzEscHtml(i.location)}</span>` : ''}
                        <span><span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">calendar_today</span> ${inzFormatDate(i.created_at)}</span>
                        <span><span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">visibility</span> ${parseInt(i.views || 0)}×</span>
                    </div>
                    ${(i.contact_email || i.email || i.phone) ? `<div style="font-size:12px;color:var(--primary-color);display:flex;gap:10px;flex-wrap:wrap;">
                        ${i.contact_email || i.email ? `<span>${inzEscHtml(i.contact_email || i.email)}</span>` : ''}
                        ${i.phone ? `<span>${inzEscHtml(i.phone)}</span>` : ''}
                    </div>` : ''}
                </div>
            </a>`;
        }

        async function loadInzPublicListings(type) {
            const grid = document.getElementById('inz-public-grid');
            if (!grid) return;
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-secondary);">Načítavam inzeráty...</div>';
            try {
                let url = 'api/classifieds.php?action=get_listings&type=' + encodeURIComponent(type);
                if (INZ_SEARCH_QUERY) url += '&q=' + encodeURIComponent(INZ_SEARCH_QUERY);
                if (INZ_SEARCH_CITY) url += '&city=' + encodeURIComponent(INZ_SEARCH_CITY);
                const res = await fetch(url);
                const data = await res.json();
                if (!data.success) {
                    grid.innerHTML = inzEmptyState('error', 'Inzeráty sa nepodarilo načítať.');
                    return;
                }
                const items = data.data || [];
                const countEl = document.getElementById('inz-count-suffix');
                if (countEl) countEl.textContent = ' (' + items.length + ')';
                if (!items.length) {
                    grid.innerHTML = inzEmptyState('newspaper', 'Zatiaľ žiadne inzeráty v tejto kategórii.');
                    return;
                }
                grid.innerHTML = items.map(i => inzRenderCard(type, i)).join('');
            } catch (e) {
                grid.innerHTML = inzEmptyState('wifi_off', 'Chyba pripojenia.');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadInzPublicListings(INZ_ACTIVE_TYPE);
        });
    </script>
</body>
</html>
