<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once 'config.php';
require_once 'translator_helper.php';
require_once 'includes/content_translation_helper.php';
require_once 'includes/branding.php';

$welcome_subtitle = $translations['welcome_subtitle'] ?? 'Nájdite si svoj termín a rezervujte si ho online kedykoľvek a kdekoľvek.';
$page_title = strtoupper(BRAND_NAME) . ' - ' . rtrim($welcome_subtitle, '.');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="shortcut icon" href="/favicon.ico">
    <meta name="description" content="<?php echo htmlspecialchars($welcome_subtitle); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($welcome_subtitle); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo BRAND_SITE; ?>">
    <meta property="og:image" content="https://<?php echo BRAND_SITE; ?>/assets/img/ogimage.png">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Google Material Symbols (Tenký line-art štýl) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,300,0,0" />
    
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <!-- Naše CSS a JS -->
    <link rel="stylesheet" href="assets/css/variables.css?v=7">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <link rel="stylesheet" href="assets/css/site-footer.css?v=5">
    <script src="assets/js/theme.js?v=2.0"></script>

    <!-- Flatpickr (Kalendár) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/sk.js"></script>
    
    <script>
        // Načítanie Last Minute termínov
        async function loadPublicLastMinute() {
            try {
                let res = await fetch('api/public_last_minute.php');
                let data = await res.json();
                if (data.success && data.data.length > 0) {
                    document.getElementById('last-minute-container').style.display = 'block';
                    let html = '';
                    data.data.forEach(lm => {
                        let time = lm.slot_time.substring(0, 5);
                        let priceHtml = lm.discounted_price < lm.original_price ? 
                                        `<s style="color:var(--text-secondary); font-size:0.9em; margin-right: 5px;">${lm.original_price} €</s> <strong>${lm.discounted_price} €</strong>` : 
                                        `<strong>${lm.discounted_price} €</strong>`;
                        
                        let img = lm.image_url ? lm.image_url : 'assets/img/spa_massage.png';
                        
                        html += `
                        <a href="prevadzky.php?est=${lm.est_id}" class="listing-card">
                            <div class="listing-img-wrapper">
                                <img src="${img}" alt="${lm.service_name}">
                                <div style="position:absolute; top:15px; left:15px; background:#e74c3c; color:white; padding:5px 12px; border-radius:8px; font-weight:bold; font-size:14px; box-shadow: 0 4px 10px rgba(231,76,60,0.4);">
                                    Dnes ${time}
                                </div>
                                <button class="fav-btn" title="Uložiť" onclick="event.preventDefault();"><span class="material-symbols-outlined">star</span></button>
                            </div>
                            <div class="listing-content">
                                <h3 class="listing-title">${lm.service_name} ${lm.service_duration ? '('+lm.service_duration+' min)' : ''}</h3>
                                <p class="listing-salon">Prevádzka: ${lm.establishment_name} <span style="color:#f39c12;">★ 5.0</span></p>
                                <p class="listing-price" style="color:#e74c3c;">${priceHtml}</p>
                                <div class="listing-footer">
                                    <div><span class="material-symbols-outlined">location_on</span> ${lm.city}</div>
                                    <div style="color:#e74c3c; font-weight:600;"><span class="material-symbols-outlined">local_fire_department</span> Last Minute</div>
                                </div>
                            </div>
                        </a>`;
                    });
                    document.getElementById('last-minute-grid').innerHTML = html;
                }
            } catch (e) { console.error('Chyba Last Minute:', e); }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadPublicLastMinute();
        });
    </script>

    <style>
        /* Základné rozloženie */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .top-bar-left,
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Tlačidlá v hornom menu */
        .top-btn {
            height: 40px;
            min-height: 40px;
            max-height: 40px;
            box-sizing: border-box;
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
            padding: 0 16px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
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
        body.dark-mode .top-btn {
            height: 40px;
            border-color: var(--border-color);
        }
        .top-btn:hover {
            border-color: var(--primary-color);
            background-color: var(--input-bg);
        }
        body.dark-mode .top-btn:not(.logout-btn):hover {
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
            background-color: rgba(176, 128, 66, 0.12) !important;
        }
        .top-btn .material-symbols-outlined {
            font-size: 20px;
            font-weight: 300;
            line-height: 1;
        }
        
        .lang-btn {
            height: 40px;
            min-height: 40px;
            max-height: 40px;
            padding: 0 12px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
            box-sizing: border-box;
        }
        .lang-btn .material-symbols-outlined {
            font-size: 18px;
            line-height: 1;
        }

        /* Jazykový prepínač ako Dropdown */
        .lang-dropdown {
            position: relative;
            display: flex;
            align-items: center;
        }
        /* Neviditeľný mostík, aby myš pri prechode z tlačidla na menu "nespadla" */
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
            left: 0; /* Oprava: menu sa teraz zarovná pekne s ľavým okrajom tlačidla */
            top: 100%; /* Oprava medzery */
            margin-top: 5px;
            background-color: var(--card-bg);
            min-width: 140px;
            box-shadow: var(--shadow-md);
            border-radius: 10px;
            border: 1px solid var(--border-color);
            z-index: 100;
            overflow: hidden;
        }
        .lang-dropdown:hover .lang-menu {
            display: block;
        }
        .lang-menu a {
            color: var(--text-primary);
            padding: 10px 15px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .lang-menu a:hover {
            color: var(--primary-color);
            background-color: rgba(176, 128, 66, 0.12);
        }

        .theme-btn {
            background: none;
            width: 40px;
            min-width: 40px;
            max-width: 40px;
            height: 40px;
            min-height: 40px;
            max-height: 40px;
            box-sizing: border-box;
            color: var(--text-primary);
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .theme-btn .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
        }

        /* Hlavný obsah */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 10px 20px 20px 20px; /* Vrátené do normálu */
            max-width: 1000px;
            margin: 0 auto;
        }

        @keyframes floatLogo {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }

        .logo-container img {
            max-height: 80px;
            width: auto;
            margin-top: 0;
            margin-bottom: 8px;
            transition: filter 0.3s;
            animation: floatLogo 4s ease-in-out infinite;
        }
        
        /* Logo v dark mode */
        body.dark-mode .logo-container img {
            filter: invert(1) hue-rotate(180deg);
        }
        <?php if (BRAND_NAME === 'Rezervos'): ?>
        .logo-container img { filter: none !important; }
        .logo-light { display: block; }
        .logo-dark  { display: none; }
        body.dark-mode .logo-light { display: none; }
        body.dark-mode .logo-dark  { display: block; }
        <?php endif; ?>

        /* Zoskupenie Ikon a Sloganu */
        .brand-block {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 550px; /* Maximálna šírka celého bloku s čiarami */
            margin-bottom: 40px;
        }

        .icons-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 15px;
        }

        .icons-wrapper .line {
            flex-grow: 1;
            height: 1.5px;
            background-color: var(--primary-color); /* Zlatá farba */
            margin: 0 20px;
        }

        /* Ikony kategórií pod logom */
        .category-icons {
            display: flex;
            gap: 20px; /* Ikony bližšie pri sebe */
            color: var(--text-primary);
        }
        .category-icons .material-symbols-outlined {
            font-size: 30px; 
            font-weight: 200; 
            cursor: pointer;
            transition: transform 0.2s, color 0.2s;
        }
        .category-icons .material-symbols-outlined:hover {
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        body.dark-mode .category-icons .material-symbols-outlined:hover {
            color: var(--primary-color) !important;
        }

        .slogan {
            font-size: 0.90rem;
            font-weight: 500;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--text-primary);
            white-space: nowrap; 
            text-align: center;
        }

        /* Fresha-style Search Bar */
        .search-bar {
            display: flex;
            align-items: center;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 10px;
            box-shadow: var(--shadow-lg);
            width: 100%;
            border: 1px solid var(--border-color);
            position: relative;
        }

        .search-input-group {
            display: flex;
            align-items: center;
            flex: 1;
            padding: 0 20px;
            border-right: 1px solid var(--border-color);
            gap: 10px;
        }
        .search-input-group:last-of-type {
            border-right: none;
        }

        .search-input-group .material-symbols-outlined {
            color: var(--text-secondary);
            font-size: 20px;
        }

        .search-input-group input {
            border: none;
            background: transparent;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: inherit;
            width: 100%;
            outline: none;
        }
        .search-input-group input::placeholder {
            color: var(--text-secondary);
        }

        .search-btn {
            background-color: #0f172a;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 13px 32px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
            flex-shrink: 0;
            letter-spacing: 0.3px;
        }
        body.dark-mode .search-btn {
            background-color: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(176, 128, 66, 0.3);
        }

        /* Search Bar & Autocomplete styles */
        .search-bar {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 8px 10px 8px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 860px;
            margin: 0 auto;
            position: relative;
            z-index: 100;
            box-sizing: border-box;
            gap: 8px;
        }

        .search-input-group {
            display: flex;
            align-items: center;
            flex: 1;
            padding: 0 14px;
            border-right: 1px solid var(--border-color);
            position: relative;
        }

        .search-input-group:nth-child(2) {
            border-right: none;
            padding-right: 6px;
        }

        .search-input-group .material-symbols-outlined {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 22px;
            flex-shrink: 0;
        }

        .search-input-group input {
            border: none;
            background: transparent;
            width: 100%;
            padding: 10px 0;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-primary);
            outline: none;
            font-family: inherit;
        }

        .search-input-group input::placeholder {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .gps-btn {
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
            flex-shrink: 0;
            margin-left: 4px;
        }
        .gps-btn:hover {
            background: transparent;
            color: var(--primary-color);
            transform: scale(1.15);
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
            top: calc(100% + 14px);
            left: 0;
            right: 0;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.04);
            z-index: 1200;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            padding: 6px;
            box-sizing: border-box;
            animation: dropdownFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-6px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
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
            padding: 10px 14px;
            cursor: pointer;
            border-radius: 10px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
            font-weight: 600;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: left;
        }
        .autocomplete-suggestion:hover {
            background: rgba(176, 128, 66, 0.12);
            color: var(--primary-color);
            padding-left: 18px;
        }
        body.dark-mode .autocomplete-suggestion:hover {
            background: rgba(176, 128, 66, 0.22);
            color: var(--primary-color);
        }
        .autocomplete-suggestion .sug-icon {
            font-size: 20px;
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
            border-radius: 10px;
        }
        .autocomplete-suggestion.gps-sug-item:not(:last-child) {
            margin-bottom: 4px;
        }
        .autocomplete-suggestion.gps-sug-item:hover {
            background: rgba(176, 128, 66, 0.2);
            color: var(--primary-color);
            padding-left: 18px;
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
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .gps-sug-item .gps-sug-sub {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-secondary);
            opacity: 0.9;
        }

        .search-btn:hover {
            transform: scale(1.02);
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-wrap: nowrap;
            }
            .top-bar-left {
                order: -1;
                flex: 0 0 auto;
            }
            .top-bar-left .lang-dropdown {
                order: -1;
                z-index: 110;
            }
            .top-bar-right {
                order: 1;
                margin-left: auto;
                min-width: 0;
            }
            .search-bar {
                flex-direction: column;
                border-radius: 20px;
                padding: 16px;
                gap: 12px;
            }
            .search-input-group {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                padding: 10px 0;
                width: 100%;
                max-width: 100% !important;
            }
            .search-input-group:nth-child(3) {
                border-bottom: 1px solid var(--border-color);
            }
            .search-btn {
                width: 100%;
                margin-top: 6px;
            }
            .category-icons i {
                font-size: 32px;
            }
        }

    /* Hlavné Kategórie / Služby */
        .services-section {
            padding: 10px 20px 20px 20px;
            max-width: 1200px;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.25;
            color: var(--text-primary);
            margin: 0 0 20px 0;
            font-family: inherit;
        }
        .section-title .material-symbols-outlined { font-size: 22px !important; vertical-align: -0.18em !important; }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        /* Posledné prevádzky (kartičky prevzaté z prevadzky.php) — vlastné "home-" triedy, aby sa
           nebili s existujúcou .listing-card/.fav-btn/... definíciou nižšie v tomto súbore (sekcia
           "Zoznam voľných termínov"), ktorá by inak vďaka poradiu v CSS prepísala tieto štýly. */
        .home-establishments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
        .home-listing-card {
            background-color: var(--card-bg);
            border: 2px solid var(--border-color);
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
        .home-listing-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08); border-color: var(--primary-color); }
        .home-listing-img-wrapper { position: relative; width: 100%; height: 200px; background-color: var(--input-bg); overflow: hidden; }
        .home-listing-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
        .home-listing-card:hover .home-listing-img-wrapper img { transform: scale(1.05); }
        .home-fav-btn {
            position: absolute; top: 12px; right: 12px; width: 36px; height: 36px; border-radius: 50%;
            background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(8px); border: none; color: #ffffff;
            cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; z-index: 5;
        }
        .home-fav-btn:hover { transform: scale(1.1); background: rgba(0, 0, 0, 0.7); color: #ff4757; }
        .home-fav-btn .material-symbols-outlined { font-size: 20px; }
        .home-listing-content { padding: 16px; display: flex; flex-direction: column; flex: 1; }
        .home-listing-title { margin: 0 0 6px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-primary); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .home-listing-salon { margin: 0 0 14px 0; font-size: 0.88rem; color: var(--text-secondary); display: flex; align-items: center; gap: 4px; }
        .home-listing-salon .material-symbols-outlined { font-size: 16px; color: var(--primary-color); }
        .home-listing-bottom { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border-color); }
        .home-listing-price { font-size: 1rem; font-weight: 800; color: var(--text-primary); }
        .home-rating-badge { display: inline-flex; align-items: center; gap: 4px; background: rgba(243, 156, 18, 0.12); color: #f39c12; padding: 4px 8px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; }
        .home-rating-badge .material-symbols-outlined { font-size: 15px; font-variation-settings: 'FILL' 1; }

        /* Najnovšie inzeráty (kartičky prevzaté z inzercia.php) */
        .inz-public-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:24px; }
        .inz-listing-card {
            background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 14px;
            overflow: hidden; display: flex; flex-direction: column; transition: all 0.2s;
            text-decoration: none; color: inherit;
        }
        .inz-listing-card:hover { border-color: var(--primary-color); box-shadow: 0 4px 16px rgba(176,128,66,0.13); transform: translateY(-1px); }
        .inz-listing-card .card-body { padding: 16px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .inz-card-title { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text-primary); line-height: 1.35; }
        .inz-card-description { margin: 0; font-size: 0.95rem; color: var(--text-secondary); line-height: 1.5; }
        .inz-card-price { font-weight: 800; color: var(--primary-color); font-size: 1.05rem; }
        .inz-card-meta { display: flex; gap: 12px; flex-wrap: wrap; font-size: 0.88rem; line-height: 1.4; color: var(--text-secondary); margin-top: auto; }
        .inz-card-meta .material-symbols-outlined { font-size: 16px; vertical-align: -3px; }

        .service-card {
            background-color: var(--input-bg); /* Jemné pozadie karty */
            border: 2px solid var(--border-color); /* Výraznejší, stabilný obrys */
            border-radius: 12px;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            text-decoration: none;
            color: var(--text-primary);
            transition: transform 0.2s, background-color 0.2s, border-color 0.2s;
        }
        .service-card:hover {
            transform: translateY(-2px);
            background-color: var(--card-bg);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }
        .service-icon .material-symbols-outlined {
            font-size: 32px;
            color: var(--primary-color); /* Zlatá farba */
            font-weight: 300;
        }
        .service-text h3 {
            margin: 0 0 4px 0;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .service-text p {
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.4;
            color: var(--text-secondary);
        }

        @media (max-width: 900px) {
            .services-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .services-grid { grid-template-columns: 1fr; }
            .home-establishments-grid,
            .inz-public-grid { grid-template-columns: minmax(0, 1fr); gap: 18px; }
            .section-title { font-size: 1.25rem; }
            .home-listing-title { font-size: 1.1rem; }
            .home-listing-salon { font-size: 0.88rem; }
            .inz-card-title { font-size: 1.08rem; }
            .inz-card-description { font-size: 0.95rem; }
            .inz-card-meta { font-size: 0.86rem; gap: 9px; }
        }

    /* Zoznam voľných termínov (Listings) */
    .listings-section {
        padding: 10px 20px 80px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .listings-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .listing-card {
        background-color: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        text-decoration: none;
        color: var(--text-primary);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
    }
    .listing-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }
    .listing-img-wrapper {
        position: relative;
        height: 200px;
        width: 100%;
    }
    .listing-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .fav-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background-color: rgba(0, 0, 0, 0.4);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        backdrop-filter: blur(4px);
        transition: background 0.2s;
    }
    .fav-btn:hover {
        background-color: rgba(0, 0, 0, 0.7);
    }
    .fav-btn .material-symbols-outlined {
        font-size: 20px;
    }
    .listing-content {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .listing-title {
        font-size: 1.05rem;
        font-weight: 600;
        margin: 0 0 5px 0;
    }
    .listing-salon {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin: 0 0 10px 0;
    }
    .listing-price {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0 0 20px 0;
    }
    .listing-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    .listing-footer div {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .listing-footer .material-symbols-outlined {
        font-size: 16px;
    }
    
    @media (max-width: 900px) {
        .listings-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .listings-grid { grid-template-columns: 1fr; }
    }
    
    /* Pätička (Footer) — štýly sú v assets/css/site-footer.css (zdieľané s prevadzky.php/inzercia.php) */


    
    .logout-btn {
        color: #ef4444 !important;
    }
    .logout-btn:hover {
        background-color: rgba(239, 68, 68, 0.1) !important;
        border-color: var(--primary-color) !important;
    }

    
/* Ikonové tlačidlá v pravom hornom menu */
.top-bar-right .top-btn:not(.login-btn) {
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 12px;
}
</style>
</head>
<body>
<div class="top-bar">
    <div class="top-bar-left">
        <!-- Jazyk -->
        <div class="lang-dropdown">
            <button class="top-btn lang-btn" type="button">
                <span class="material-symbols-outlined">language</span>
                <span><?php echo strtoupper($lang); ?></span>
                <span class="material-symbols-outlined" style="font-size:16px;">expand_more</span>
            </button>
            <div class="lang-menu">
                <a href="?lang=sk" data-lang="sk">Slovenčina</a>
                <a href="?lang=cz" data-lang="cz">Čeština</a>
                <a href="?lang=en" data-lang="en">English</a>
                <a href="?lang=de" data-lang="de">Deutsch</a>
                <a href="?lang=pl" data-lang="pl">Polski</a>
                <a href="?lang=hu" data-lang="hu">Magyar</a>
                <a href="?lang=ua" data-lang="ua">Українська</a>
            </div>
        </div>
    </div>

    <?php $__onboardingPending = ($_SESSION['onboarding_completed'] ?? 1) == 0; ?>
    <div class="top-bar-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'business'): ?>
                    <?php if ($__onboardingPending): ?>
                    <a href="onboarding.php" class="top-btn" style="position:relative;" title="Dokončiť nastavenie">
<span class="material-symbols-outlined" style="font-size: 20px;">rocket_launch</span>
<span style="position:absolute;top:-3px;right:-3px;width:9px;height:9px;border-radius:50%;background:#ef4444;border:2px solid var(--card-bg,#fff);"></span>
</a>
                    <?php else: ?>
                    <a href="dashboard.php" class="top-btn" title="Moja Prevádzka">
<span class="material-symbols-outlined" style="font-size: 20px;">storefront</span>
</a>
                    <?php endif; ?>
                <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php" class="top-btn" title="Admin Panel">
<span class="material-symbols-outlined" style="font-size: 20px;">admin_panel_settings</span>
</a>
                <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
                    <?php if ($__onboardingPending): ?>
                    <a href="onboarding.php" class="top-btn" style="position:relative;" title="Dokončiť nastavenie">
<span class="material-symbols-outlined" style="font-size: 20px;">rocket_launch</span>
<span style="position:absolute;top:-3px;right:-3px;width:9px;height:9px;border-radius:50%;background:#ef4444;border:2px solid var(--card-bg,#fff);"></span>
</a>
                    <?php else: ?>
                    <a href="moj_profil.php" class="top-btn" title="Môj Profil">
<span class="material-symbols-outlined" style="font-size: 20px;">person</span>
</a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Tmavý režim -->
                <button id="theme-toggle" class="top-btn theme-btn" aria-label="Toggle Dark Mode" title="Tmavý režim" >
                    <span class="material-symbols-outlined" style="font-size: 20px;">dark_mode</span>
                </button>
                <a href="logout.php" class="top-btn logout-btn" title="Odhlásiť sa">
<span class="material-symbols-outlined" style="font-size: 20px;">logout</span>
</a>
            <?php else: ?>
                <!-- Tmavý režim -->
                <button id="theme-toggle" class="top-btn theme-btn" aria-label="Toggle Dark Mode" title="Tmavý režim" >
                    <span class="material-symbols-outlined" style="font-size: 20px;">dark_mode</span>
                </button>
                <a href="#" onclick="openAuthModal(); return false;" class="top-btn login-btn">
<span class="material-symbols-outlined" style="font-size: 20px;">login</span>
<?php echo $translations['login_btn'] ?? 'Prihlásiť sa'; ?>
</a>
            <?php endif; ?>

            
        </div>
    </div>

    <div class="hero">
        <div class="logo-container">
            <?php if (BRAND_NAME === 'Rezervos'): ?>
            <img src="/rezervoslogo.png"     alt="Rezervos" class="rezervos-logo logo-light">
            <img src="/rezervoslogodark.png" alt="Rezervos" class="rezervos-logo logo-dark">
            <?php else: ?>
            <img src="<?= BRAND_LOGO ?>" alt="<?= BRAND_NAME ?> Logo">
            <?php endif; ?>
        </div>

        <div class="brand-block">
            <div class="icons-wrapper">
                <div class="line"></div>
                <div class="category-icons">
                    <span class="material-symbols-outlined" title="Barber a Kaderníctvo">content_cut</span>
                    <span class="material-symbols-outlined" title="Kozmetika">face_retouching_natural</span>
                    <span class="material-symbols-outlined" title="Wellness">spa</span>
                    <span class="material-symbols-outlined" title="Dentálna hygiena">dentistry</span>
                    <span class="material-symbols-outlined" title="Masáže">massage</span>
                </div>
                <div class="line"></div>
            </div>

            <div class="slogan"><?php echo htmlspecialchars(t('VŠETKY SLUŽBY')); ?> &bull; <?php echo htmlspecialchars(t('JEDNO MIESTO')); ?></div>
        </div>

        <form method="GET" action="prevadzky.php" class="search-bar" id="heroSearchForm">
            <div class="search-input-group">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" id="searchService" placeholder="<?= htmlspecialchars(t('Akú službu hľadáte?')) ?>" autocomplete="off">
                <div class="autocomplete-suggestions" id="serviceSuggestions"></div>
            </div>
            
            <div class="search-input-group">
                <span class="material-symbols-outlined">location_on</span>
                <input type="text" name="city" id="searchLocation" placeholder="<?= htmlspecialchars(t('Mesto alebo PSČ')) ?>" autocomplete="off">
                <div class="autocomplete-suggestions" id="locationSuggestions"></div>
            </div>

            <button type="submit" class="search-btn"><?= t('Hľadať') ?></button>
        </form>
    </div>
</div>

<!-- Sekcia kategórií služieb -->
<div class="services-section">
    <h2 class="section-title"><span class="material-symbols-outlined" style="font-size:19px;vertical-align:-4px;color:var(--primary-color);">grid_view</span> <?php echo t('Všetky služby'); ?></h2>
    <div class="services-grid">
        <a href="prevadzky.php?category=Vlasy" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">face</span></div>
            <div class="service-text">
                <h3><?php echo t('Vlasy'); ?></h3>
                <p><?php echo t('Strihy, farbenie, styling'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Holičstvo a Barber" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">content_cut</span></div>
            <div class="service-text">
                <h3><?php echo t('Holičstvo a Barber'); ?></h3>
                <p><?php echo t('Úprava brady, klasické holenie'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Nechty" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">back_hand</span></div>
            <div class="service-text">
                <h3><?php echo t('Nechty'); ?></h3>
                <p><?php echo t('Manikúra, pedikúra, gél'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Starostlivosť o pleť" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">face_retouching_natural</span></div>
            <div class="service-text">
                <h3><?php echo t('Starostlivosť o pleť'); ?></h3>
                <p><?php echo t('Čistenie, peeling, masky'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Obočie a riasy" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">visibility</span></div>
            <div class="service-text">
                <h3><?php echo t('Obočie a riasy'); ?></h3>
                <p><?php echo t('Laminácia, farbenie'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Masáž" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">massage</span></div>
            <div class="service-text">
                <h3><?php echo t('Masáž'); ?></h3>
                <p><?php echo t('Relaxačná, thajská, športová'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Make-up" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">brush</span></div>
            <div class="service-text">
                <h3><?php echo t('Make-up'); ?></h3>
                <p><?php echo t('Večerný, svadobný, denný'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Wellness a kúpele" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">spa</span></div>
            <div class="service-text">
                <h3><?php echo t('Wellness a kúpele'); ?></h3>
                <p><?php echo t('Sauny, vírivky, relax'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Vrkoče a dredy" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">waves</span></div>
            <div class="service-text">
                <h3><?php echo t('Vrkoče a dredy'); ?></h3>
                <p><?php echo t('Zapletanie, africké vrkoče'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Tetovanie" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">ink_pen</span></div>
            <div class="service-text">
                <h3><?php echo t('Tetovanie'); ?></h3>
                <p><?php echo t('Tetovanie, permanentný make-up'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Lekárska estetika" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">medical_services</span></div>
            <div class="service-text">
                <h3><?php echo t('Lekárska estetika'); ?></h3>
                <p><?php echo t('Botox, výplne, plazma'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Depilácia a epilácia" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">eco</span></div>
            <div class="service-text">
                <h3><?php echo t('Depilácia a epilácia'); ?></h3>
                <p><?php echo t('Vosk, laser, cukrová pasta'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Domáce služby" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">home</span></div>
            <div class="service-text">
                <h3><?php echo t('Domáce služby'); ?></h3>
                <p><?php echo t('Služby priamo u vás doma'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Piercing" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">scatter_plot</span></div>
            <div class="service-text">
                <h3><?php echo t('Piercing'); ?></h3>
                <p><?php echo t('Uši, tvár, telo'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Služby pre miláčikov" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">pets</span></div>
            <div class="service-text">
                <h3><?php echo t('Služby pre miláčikov'); ?></h3>
                <p><?php echo t('Strihanie, úprava psov'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Zubné a ortodontické" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">dentistry</span></div>
            <div class="service-text">
                <h3><?php echo t('Zubné a ortodontické'); ?></h3>
                <p><?php echo t('Bielenie, hygiena, rovnátka'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Zdravie a kondícia" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">fitness_center</span></div>
            <div class="service-text">
                <h3><?php echo t('Zdravie a kondícia'); ?></h3>
                <p><?php echo t('Tréning, fyzioterapia'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Profesionálne služby" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">work</span></div>
            <div class="service-text">
                <h3><?php echo t('Profesionálne služby'); ?></h3>
                <p><?php echo t('Školenia, poradenstvo'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Solárium a opaľovanie" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">sunny</span></div>
            <div class="service-text">
                <h3><?php echo t('Solárium a opaľovanie'); ?></h3>
                <p><?php echo t('Solárium, nástreky'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Joga a Pilates" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">self_improvement</span></div>
            <div class="service-text">
                <h3><?php echo t('Joga a Pilates'); ?></h3>
                <p><?php echo t('Lekcie, kurzy'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Fyzioterapia" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">healing</span></div>
            <div class="service-text">
                <h3><?php echo t('Fyzioterapia'); ?></h3>
                <p><?php echo t('Rehabilitácia, naprávanie'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Osobní tréneri" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">directions_run</span></div>
            <div class="service-text">
                <h3><?php echo t('Osobní tréneri'); ?></h3>
                <p><?php echo t('Fitness, cvičenie na mieru'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Výživové poradenstvo" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">restaurant_menu</span></div>
            <div class="service-text">
                <h3><?php echo t('Výživové poradenstvo'); ?></h3>
                <p><?php echo t('Jedálničky, konzultácie'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Svadobné služby" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">celebration</span></div>
            <div class="service-text">
                <h3><?php echo t('Svadobné služby'); ?></h3>
                <p><?php echo t('Vlasy, vizáž, balíčky'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Alternatívna medicína" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">emoji_nature</span></div>
            <div class="service-text">
                <h3><?php echo t('Alternatívna medicína'); ?></h3>
                <p><?php echo t('Akupunktúra, bankovanie'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Psychológia a Terapia" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">psychology</span></div>
            <div class="service-text">
                <h3><?php echo t('Psychológia a Terapia'); ?></h3>
                <p><?php echo t('Psychológ, logopédia, koučing'); ?></p>
            </div>
        </a>
        <a href="prevadzky.php?category=Iné" class="service-card">
            <div class="service-icon"><span class="material-symbols-outlined">more_horiz</span></div>
            <div class="service-text">
                <h3><?php echo t('Iné'); ?></h3>
                <p><?php echo t('Ďalšie špeciálne služby'); ?></p>
            </div>
        </a>

    </div>
</div>

<?php
// Pomocná funkcia na obrázok prevádzky — rovnaká logika ako v prevadzky.php (get_establishment_image),
// zjednodušená (bez category_definitions), aby to fungovalo aj tu bez ďalšej závislosti.
function home_establishment_image($est) {
    if (!empty($est['image_url']) && $est['image_url'] !== 'assets/img/default_salon.jpg') {
        return htmlspecialchars($est['image_url']);
    }
    $cat = $est['category'] ?? '';
    $name = $est['name'] ?? '';
    if (stripos($cat, 'Barber') !== false || stripos($cat, 'Holi') !== false || stripos($name, 'Barber') !== false) return 'assets/img/barber_shop.png';
    if (stripos($cat, 'Necht') !== false || stripos($cat, 'Nails') !== false) return 'assets/img/cat_nails.png';
    if (stripos($cat, 'Masáž') !== false || stripos($cat, 'Spa') !== false || stripos($cat, 'Wellness') !== false) return 'assets/img/cat_wellness.png';
    if (stripos($cat, 'Pleť') !== false || stripos($cat, 'Kozmet') !== false || stripos($cat, 'Make-up') !== false) return 'assets/img/cat_makeup.png';
    if (stripos($cat, 'Tetov') !== false || stripos($cat, 'Tattoo') !== false) return 'assets/img/cat_tattoo.png';
    if (stripos($cat, 'Vrko') !== false || stripos($cat, 'Braids') !== false) return 'assets/img/cat_braids.png';
    return 'assets/img/cat_hair.png';
}

// Posledné 3 aktívne prevádzky (rovnaká logika ako prevadzky.php: min. cena služby, uložené hodnotenie)
$home_establishments = [];
$res = $conn->query("
    SELECT e.*, (SELECT MIN(price) FROM services WHERE establishment_id = e.id) as min_price
    FROM establishments e
    WHERE e.status = 'active'
    ORDER BY e.id DESC
    LIMIT 3
");
if ($res) { while ($row = $res->fetch_assoc()) { $home_establishments[] = $row; } }

// Posledné 3 aktívne inzeráty
$home_classifieds = [];
$res2 = $conn->query("
    SELECT * FROM classifieds
    WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY created_at DESC
    LIMIT 3
");
if ($res2) { while ($row = $res2->fetch_assoc()) { $home_classifieds[] = $row; } }
?>

<?php if (!empty($home_establishments)): ?>
<div class="services-section">
    <h2 class="section-title"><span class="material-symbols-outlined" style="font-size:19px;vertical-align:-4px;color:var(--primary-color);">storefront</span> <?php echo t('Posledné pridané prevádzky'); ?></h2>
    <div class="home-establishments-grid">
        <?php foreach ($home_establishments as $est): ?>
            <?php
            $img = home_establishment_image($est);
            $rating = number_format($est['rating'] ?? 5.0, 1);
            $est['name'] = ct_get($conn, 'establishment', $est['id'], 'name', $est['name']);
            ?>
            <a href="<?= !empty($est['custom_url']) ? '@' . rawurlencode($est['custom_url']) : 'profil.php?id=' . (int)$est['id'] ?>" class="home-listing-card">
                <div class="home-listing-img-wrapper">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($est['name']) ?>" onerror="this.onerror=null; this.src='assets/img/cat_hair.png';" loading="lazy">
                    <button class="home-fav-btn" title="Uložiť medzi obľúbené" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(this, <?= (int)$est['id'] ?>);">
                        <span class="material-symbols-outlined">favorite</span>
                    </button>
                </div>
                <div class="home-listing-content">
                    <h3 class="home-listing-title" title="<?= htmlspecialchars($est['name']) ?>"><?= htmlspecialchars($est['name']) ?></h3>
                    <p class="home-listing-salon">
                        <span class="material-symbols-outlined">location_on</span>
                        <span><?= htmlspecialchars($est['city'] ?? 'Bratislava') ?></span>
                    </p>
                    <div class="home-listing-bottom">
                        <span class="home-listing-price">
                            <?= (isset($est['min_price']) && $est['min_price'] > 0) ? 'od ' . number_format($est['min_price'], 2) . ' ' . $currency : 'Cena neuvedená' ?>
                        </span>
                        <div class="home-rating-badge">
                            <span class="material-symbols-outlined">star</span>
                            <span><?= $rating ?></span>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($home_classifieds)): ?>
<div class="services-section">
    <h2 class="section-title"><span class="material-symbols-outlined" style="font-size:19px;vertical-align:-4px;color:var(--primary-color);">sell</span> <?php echo t('Najnovšie inzeráty'); ?></h2>
    <div class="inz-public-grid">
        <?php foreach ($home_classifieds as $c): ?>
            <?php
            $imgs = json_decode($c['images'] ?? '', true) ?: [];
            $firstImg = $imgs[0] ?? '';
            $c['title'] = ct_get($conn, 'classified', $c['id'], 'title', $c['title']);
            $desc = ct_get($conn, 'classified', $c['id'], 'description', trim($c['description'] ?? ''));
            $descShort = mb_strlen($desc) > 110 ? mb_substr($desc, 0, 110) . '…' : $desc;
            ?>
            <a href="inzerat.php?id=<?= (int)$c['id'] ?>" class="inz-listing-card">
                <?php if ($firstImg): ?>
                    <div style="height:160px;overflow:hidden;background:var(--bg-color);position:relative;">
                        <img src="/<?= htmlspecialchars($firstImg) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy" onerror="this.parentElement.style.display='none';">
                        <button class="home-fav-btn" style="width:30px;height:30px;top:10px;right:10px;" title="Uložiť medzi obľúbené" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(this, <?= (int)$c['id'] ?>);">
                            <span class="material-symbols-outlined" style="font-size:16px;">star</span>
                        </button>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <h4 class="inz-card-title"><?= htmlspecialchars($c['title']) ?></h4>
                    <?php if ($descShort): ?><p class="inz-card-description"><?= htmlspecialchars($descShort) ?></p><?php endif; ?>
                    <?php if (!empty($c['price'])): ?><span class="inz-card-price"><?= number_format((float)$c['price'], 2) ?> €</span><?php endif; ?>
                    <div class="inz-card-meta">
                        <?php if (!empty($c['location'])): ?><span><span class="material-symbols-outlined">location_on</span> <?= htmlspecialchars($c['location']) ?></span><?php endif; ?>
                        <span><span class="material-symbols-outlined">calendar_today</span> <?= date('d.m.Y', strtotime($c['created_at'])) ?></span>
                        <span><span class="material-symbols-outlined">visibility</span> <?= (int)($c['views'] ?? 0) ?>×</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/site-footer.php'; ?>



<!-- MODAL PRE EKOLOGICKÚ STOPU -->
<div id="eco-info-modal" style="display: none; position: fixed; z-index: 10003; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; border-radius: 24px; width: 100%; max-width: 520px; box-shadow: 0 20px 50px rgba(0,0,0,0.4); position: relative; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-sizing: border-box;">
        <button onclick="closeEcoModal()" style="position: absolute; right: 18px; top: 18px; background: none; border: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 5px; border-radius: 50%; transition: background 0.2s;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='none'">
            <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 20px;">close</span>
        </button>
        
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: rgba(76, 175, 80, 0.1); border-radius: 50%; margin-bottom: 12px;">
                <span class="material-symbols-outlined notranslate" translate="no" style="color: #4CAF50; font-size: 32px; animation: pulseLeaf 3s infinite ease-in-out;">eco</span>
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


<?php include_once 'includes/auth_modal.php'; ?>

<script>
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
window.addEventListener('click', function(e) {
        const authModal = document.getElementById('auth-modal');
        if (e.target === authModal) {
            closeAuthModal();
        }
        const ecoModal = document.getElementById('eco-info-modal');
        if (e.target === ecoModal) {
            closeEcoModal();
        }
    });
    
    const catDescriptions = {
        'Všetky': 'Prehľad všetkých dostupných prevádzok a služieb',
        'Last Minute': 'Najnovšie zľavnené termíny na dnes a zajtra',
        'Vlasy': 'Strihy, farbenie, styling',
        'Holičstvo a Barber': 'Úprava brady, klasické holenie',
        'Nechty': 'Manikúra, pedikúra, gél',
        'Starostlivosť o pleť': 'Čistenie, peeling, masky',
        'Obočie a riasy': 'Laminácia, farbenie',
        'Masáž': 'Relaxačná, thajská, športová',
        'Make-up': 'Večerný, svadobný, denný',
        'Wellness a kúpele': 'Sauny, vírivky, relax',
        'Vrkoče a dredy': 'Zapletanie, africké vrkoče',
        'Tetovanie': 'Tetovanie, permanentný make-up',
        'Lekárska estetika': 'Botox, výplne, plazma',
        'Depilácia a epilácia': 'Vosk, laser, cukrová pasta',
        'Domáce služby': 'Služby priamo u vás doma',
        'Piercing': 'Uši, tvár, telo',
        'Služby pre miláčikov': 'Strihanie, úprava psov',
        'Zubné a ortodontické': 'Bielenie, hygiena, rovnátka',
        'Zdravie a kondícia': 'Tréning, fyzioterapia',
        'Profesionálne služby': 'Školenia, poradenstvo',
        'Solárium a opaľovanie': 'Solárium, nástreky',
        'Joga a Pilates': 'Lekcie, kurzy',
        'Fyzioterapia': 'Rehabilitácia, naprávanie',
        'Osobní tréneri': 'Fitness, cvičenie na mieru',
        'Výživové poradenstvo': 'Jedálničky, konzultácie',
        'Svadobné služby': 'Vlasy, vizáž, balíčky',
        'Alternatívna medicína': 'Akupunktúra, bankovanie',
        'Psychológia a Terapia': 'Psychológ, logopédia, koučing',
        'Iné': 'Ďalšie špeciálne služby'
    };

    function filterCategory(cat, e) {
        if (e) e.preventDefault();
        
        // Update active class
        const links = document.querySelectorAll('.cat-list a');
        links.forEach(link => link.classList.remove('active'));
        if (e && e.target.closest('a')) {
            e.target.closest('a').classList.add('active');
        } else if (!e) {
            const link = Array.from(links).find(a => a.textContent.trim().includes(cat));
            if (link) link.classList.add('active');
        }
        
        // Update Titles
        document.getElementById('resultsTitle').textContent = cat === 'Všetky' ? 'Všetky služby' : cat;
        document.getElementById('resultsSubtitle').textContent = catDescriptions[cat] ? '- ' + catDescriptions[cat] : '';

        // Filter cards
        let visibleCount = 0;
        const cards = document.querySelectorAll('.listing-card');
        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category');
            const isLM = card.getAttribute('data-is-last-minute');
            
            if (cat === 'Last Minute') {
                if (isLM === 'true') {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            } else if (cat === 'Všetky') {
                if (isLM === 'true') {
                    card.style.display = 'none'; // Iba v Last Minute
                } else {
                    card.style.display = 'flex';
                    visibleCount++;
                }
            } else {
                if (cardCat === cat && isLM !== 'true') {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            }
        });
        
        // Update Count
        const countSpan = document.getElementById('resultsCount');
        if (visibleCount === 1) countSpan.textContent = visibleCount + ' prevádzka';
        else if (visibleCount >= 2 && visibleCount <= 4) countSpan.textContent = visibleCount + ' prevádzky';
        else countSpan.textContent = visibleCount + ' prevádzok';

        // Close sidebar on mobile after selecting a category
        if (window.innerWidth <= 900) {
            const sidebar = document.getElementById('appSidebar');
            if (sidebar && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
            }
        }
    }
    // Auto-filter based on URL parameter
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        if (category) {
            const link = Array.from(document.querySelectorAll('.cat-list a')).find(a => a.textContent.trim() === category);
            if (link) {
                filterCategory(category, { target: link, preventDefault: () => {} });
            } else {
                filterCategory(category, null);
            }
        }
    });
</script>


<script>
    
    function previewAvatar(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
                document.getElementById('avatar-preview').style.display = 'block';
                document.getElementById('avatar-icon').style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    }
    
    async function loadProfileData() {
        try {
            const res = await fetch('api/profile.php?action=get_profile');
            const data = await res.json();
            if (data.success && data.profile) {
                const p = data.profile;
                document.getElementById('profile-name').value = p.full_name || '';
                document.getElementById('profile-phone').value = p.phone || '';
                document.getElementById('profile-whatsapp').value = p.whatsapp || '';
                
                if (p.avatar_url) {
                    document.getElementById('avatar-preview').src = p.avatar_url;
                    document.getElementById('avatar-preview').style.display = 'block';
                    document.getElementById('avatar-icon').style.display = 'none';
                }
                
                if (p.role === 'business' || p.role === 'admin') {
                    document.getElementById('business-fields').style.display = 'block';
                    document.getElementById('profile-company').value = p.company_name || '';
                    document.getElementById('profile-ico').value = p.ico || '';
                    document.getElementById('profile-dic').value = p.dic || '';
                    document.getElementById('profile-address').value = p.address || '';
                }
            }
        } catch(e) {
            console.error('Error loading profile');
        }
    }
    
    async function submitProfile(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-save-profile');
        const msgDiv = document.getElementById('profile-message');
        btn.innerHTML = 'Ukladám...';
        btn.disabled = true;
        
        const fd = new FormData();
        fd.append('action', 'update_profile');
        fd.append('full_name', document.getElementById('profile-name').value);
        fd.append('phone', document.getElementById('profile-phone').value);
        fd.append('whatsapp', document.getElementById('profile-whatsapp').value);
        fd.append('company_name', document.getElementById('profile-company').value || '');
        fd.append('ico', document.getElementById('profile-ico').value || '');
        fd.append('dic', document.getElementById('profile-dic').value || '');
        fd.append('address', document.getElementById('profile-address').value || '');
        
        const fileInput = document.getElementById('profile-avatar');
        if (fileInput.files[0]) {
            fd.append('avatar', fileInput.files[0]);
        }
        
        try {
            const res = await fetch('api/profile.php', { method: 'POST', body: fd });
            const data = await res.json();
            msgDiv.style.display = 'block';
            msgDiv.textContent = data.message;
            msgDiv.style.color = data.success ? '#2ecc71' : '#ff6b6b';
            if(data.success) {
                setTimeout(() => closeProfileModal(), 1500);
            }
        } catch(e) {
            msgDiv.style.display = 'block';
            msgDiv.textContent = 'Chyba spojenia.';
            msgDiv.style.color = '#ff6b6b';
        }
        btn.innerHTML = 'Uložiť zmeny';
        btn.disabled = false;
    }
</script>


<script>
function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const main = document.getElementById('appMain');
    
    if (window.innerWidth <= 900) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
    }
}

// Adjust on resize
window.addEventListener('resize', () => {
    const sidebar = document.getElementById('appSidebar');
    const main = document.getElementById('appMain');
    const mobileCloseBtn = document.getElementById('mobileCloseBtn');
    
    if (window.innerWidth <= 900) {
        sidebar.classList.remove('collapsed');
        main.classList.remove('expanded');
        mobileCloseBtn.style.display = 'block';
    } else {
        sidebar.classList.remove('mobile-open');
        mobileCloseBtn.style.display = 'none';
    }
});

// Run once on load
if (window.innerWidth <= 900) {
    document.getElementById('mobileCloseBtn').style.display = 'block';
}

// Načítanie Last Minute termínov
async function loadPublicLastMinute() {
    try {
        let res = await fetch('api/public_last_minute.php');
        let data = await res.json();
        if (data.success && data.data.length > 0) {
            let html = '';
            data.data.forEach(lm => {
                let time = lm.slot_time.substring(0, 5);
                let priceHtml = lm.discounted_price < lm.original_price ? 
                                `<s style="color:var(--text-secondary); font-size:0.9em; margin-right: 5px;">${lm.original_price} €</s> <strong>${lm.discounted_price} €</strong>` : 
                                `<strong>${lm.discounted_price} €</strong>`;
                
                let img = lm.image_url ? lm.image_url : 'assets/img/spa_massage.png';

                html += `
                <a href="prevadzky.php?est=${lm.est_id}" class="listing-card" data-category="${lm.service_category || ''}" data-is-last-minute="true">
                    <div class="listing-img-wrapper">
                        <img src="${img}" alt="${lm.service_name}">
                        <div style="position:absolute; top:15px; left:15px; background:#e74c3c; color:white; padding:5px 12px; border-radius:8px; font-weight:bold; font-size:14px; box-shadow: 0 4px 10px rgba(231,76,60,0.4);">
                            Dnes ${time}
                        </div>
                        <button class="fav-btn" title="Uložiť" onclick="event.preventDefault();"><span class="material-symbols-outlined">favorite</span></button>
                    </div>
                    <div class="listing-content">
                        <h3 class="listing-title">${lm.service_name} ${lm.service_duration ? '('+lm.service_duration+' min)' : ''}</h3>
                        <p class="listing-salon">Prevádzka: ${lm.establishment_name} <span style="color:#f39c12;">★ 5.0</span></p>
                        <p class="listing-price" style="color:#e74c3c;">${priceHtml}</p>
                        <div class="listing-footer">
                            <div><span class="material-symbols-outlined">location_on</span> ${lm.city}</div>
                            <div style="color:#e74c3c; font-weight:600;"><span class="material-symbols-outlined">local_fire_department</span> Last Minute</div>
                        </div>
                    </div>
                </a>`;
            });
            // Pridať do hlavného gridu na začiatok
            let grid = document.querySelector('.listings-grid');
            if (grid) {
                grid.insertAdjacentHTML('afterbegin', html);
            }
            
            // Aplikuj filter, aby sa karty schovali/zobrazili podľa aktuálnej kategórie
            const resultsTitle = document.getElementById('resultsTitle');
            if (resultsTitle && typeof filterCategory === 'function') {
                const currentCat = resultsTitle.textContent === 'Všetky služby' ? 'Všetky' : resultsTitle.textContent;
                filterCategory(currentCat, null);
            }
        }
    } catch (e) { console.error('Chyba Last Minute:', e); }
}

// --- INTELIGENTNÉ VYHĽADÁVANIE (Služby, Lokalita, Čas) ---
document.addEventListener("DOMContentLoaded", function() {
    loadPublicLastMinute();
    
    // Autocomplete & GPS Logic pre index.php
    const searchService = document.getElementById('searchService');
    const serviceSuggestions = document.getElementById('serviceSuggestions');
    const searchLocation = document.getElementById('searchLocation');
    const locationSuggestions = document.getElementById('locationSuggestions');
    const btnGps = document.getElementById('btnGps');
    
    let serviceTimeout = null;
    let locationTimeout = null;
    
    // Autocomplete Služby & Kategórie
    if (searchService && serviceSuggestions) {
        searchService.addEventListener('input', function() {
            clearTimeout(serviceTimeout);
            const val = this.value.trim();
            if (val.length < 2) {
                serviceSuggestions.style.display = 'none';
                return;
            }
            serviceTimeout = setTimeout(async () => {
                try {
                    const res = await fetch('api/search_services.php?q=' + encodeURIComponent(val));
                    const data = await res.json();
                    if (data.success && data.data) {
                        serviceSuggestions.innerHTML = '';
                        let hasResults = false;
                        
                        // Zobrazenie Kategórií
                        if (data.data['Kategórie'] && data.data['Kategórie'].length > 0) {
                            hasResults = true;
                            const grp = document.createElement('div');
                            grp.className = 'autocomplete-group';
                            grp.textContent = 'Kategórie';
                            serviceSuggestions.appendChild(grp);
                            
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
                                serviceSuggestions.appendChild(div);
                            });
                        }
                        
                        // Zobrazenie Služieb
                        if (data.data['Služby'] && data.data['Služby'].length > 0) {
                            hasResults = true;
                            const grp = document.createElement('div');
                            grp.className = 'autocomplete-group';
                            grp.textContent = 'Služby';
                            serviceSuggestions.appendChild(grp);
                            
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
                                    searchService.value = item.name;
                                    serviceSuggestions.style.display = 'none';
                                };
                                serviceSuggestions.appendChild(div);
                            });
                        }

                        // Zobrazenie Salónov
                        if (data.data['Salóny'] && data.data['Salóny'].length > 0) {
                            hasResults = true;
                            const grp = document.createElement('div');
                            grp.className = 'autocomplete-group';
                            grp.textContent = 'Salóny a Prevádzky';
                            serviceSuggestions.appendChild(grp);
                            
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
                                    searchService.value = item.name;
                                    serviceSuggestions.style.display = 'none';
                                };
                                serviceSuggestions.appendChild(div);
                            });
                        }
                        
                        serviceSuggestions.style.display = hasResults ? 'block' : 'none';
                    } else {
                        serviceSuggestions.style.display = 'none';
                    }
                } catch(e) { console.error(e); }
            }, 250);
        });
    }
    
    // Funkcia na zistenie GPS polohy a automatické vyplnenie
    function triggerIndexGps() {
        if (!navigator.geolocation) {
            if (locationSuggestions) {
                locationSuggestions.innerHTML = '<div class="autocomplete-suggestion" style="color: #ef4444;"><span class="material-symbols-outlined sug-icon" style="color: #ef4444;">error</span><span>Prehliadač nepodporuje GPS.</span></div>';
                locationSuggestions.style.display = 'block';
                setTimeout(() => { locationSuggestions.style.display = 'none'; }, 3000);
            }
            return;
        }

        if (btnGps) {
            btnGps.classList.add('loading');
            const icon = btnGps.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = 'sync';
        }

        if (locationSuggestions) {
            locationSuggestions.innerHTML = `
                <div class="autocomplete-suggestion gps-sug-item" style="cursor: default;">
                    <span class="material-symbols-outlined sug-icon" style="animation: gpsSpin 1s linear infinite;">sync</span>
                    <div class="gps-sug-details">
                        <span class="gps-sug-title">Zisťujem vašu polohu...</span>
                        <span class="gps-sug-sub">(Čaká sa na GPS súradnice)</span>
                    </div>
                </div>
            `;
            locationSuggestions.style.display = 'block';
        }

        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                try {
                    const res = await fetch(`api/search_locations.php?lat=${lat}&lon=${lon}`);
                    const data = await res.json();
                    if (data.success && data.data) {
                        searchLocation.value = data.data.city_name;
                        if (locationSuggestions) locationSuggestions.style.display = 'none';
                    } else if (locationSuggestions) {
                        locationSuggestions.innerHTML = '<div class="autocomplete-suggestion" style="color: #ef4444;"><span class="material-symbols-outlined sug-icon" style="color: #ef4444;">location_off</span><span>Nenašla sa žiadna obec v okolí.</span></div>';
                        locationSuggestions.style.display = 'block';
                        setTimeout(() => { locationSuggestions.style.display = 'none'; }, 3000);
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    if (btnGps) {
                        btnGps.classList.remove('loading');
                        const icon = btnGps.querySelector('.material-symbols-outlined');
                        if (icon) icon.textContent = 'my_location';
                    }
                }
            },
            (error) => {
                if (btnGps) {
                    btnGps.classList.remove('loading');
                    const icon = btnGps.querySelector('.material-symbols-outlined');
                    if (icon) icon.textContent = 'my_location';
                }
                if (locationSuggestions) {
                    locationSuggestions.innerHTML = '<div class="autocomplete-suggestion" style="color: #ef4444;"><span class="material-symbols-outlined sug-icon" style="color: #ef4444;">lock</span><span>Prístup k polohe bol zamietnutý.</span></div>';
                    locationSuggestions.style.display = 'block';
                    setTimeout(() => { locationSuggestions.style.display = 'none'; }, 3500);
                }
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    }

    function renderGpsDropdownItem() {
        if (!locationSuggestions) return;
        locationSuggestions.innerHTML = `
            <div class="autocomplete-suggestion gps-sug-item" id="gpsSuggestOption">
                <span class="material-symbols-outlined sug-icon">my_location</span>
                <div class="gps-sug-details">
                    <span class="gps-sug-title">Prevádzky v okolí</span>
                    <span class="gps-sug-sub">(Zistiť moju polohu)</span>
                </div>
            </div>
        `;
        const opt = document.getElementById('gpsSuggestOption');
        if (opt) {
            opt.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                triggerIndexGps();
            };
        }
    }

    // Autocomplete Lokalita & PSČ + Okamžitá ponuka "Zistiť moju polohu"
    if (searchLocation && locationSuggestions) {
        searchLocation.addEventListener('focus', function() {
            if (this.value.trim().length < 2) {
                renderGpsDropdownItem();
                locationSuggestions.style.display = 'block';
            }
        });

        searchLocation.addEventListener('click', function(e) {
            e.stopPropagation();
            if (this.value.trim().length < 2) {
                renderGpsDropdownItem();
                locationSuggestions.style.display = 'block';
            }
        });

        searchLocation.addEventListener('input', function() {
            clearTimeout(locationTimeout);
            const val = this.value.trim();
            if (val.length < 2) {
                renderGpsDropdownItem();
                locationSuggestions.style.display = 'block';
                return;
            }
            locationTimeout = setTimeout(async () => {
                try {
                    const res = await fetch('api/search_locations.php?q=' + encodeURIComponent(val));
                    const data = await res.json();
                    locationSuggestions.innerHTML = '';
                    
                    // Vždy na vrchu ponúkneme zistenie polohy
                    renderGpsDropdownItem();

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
                                searchLocation.value = item.city_name;
                                locationSuggestions.style.display = 'none';
                            };
                            locationSuggestions.appendChild(div);
                        });
                    }
                    locationSuggestions.style.display = 'block';
                } catch(e) { console.error(e); }
            }, 250);
        });
    }
    
    // Klik mimo zatvorí našeptávač
    document.addEventListener('click', function(e) {
        if (searchService && serviceSuggestions && !searchService.contains(e.target) && !serviceSuggestions.contains(e.target)) {
            serviceSuggestions.style.display = 'none';
        }
        if (searchLocation && locationSuggestions && !searchLocation.contains(e.target) && !locationSuggestions.contains(e.target) && (!btnGps || !btnGps.contains(e.target))) {
            locationSuggestions.style.display = 'none';
        }
    });
    
    // GPS Radar Lokalizácia (tlačidlo v inpute)
    if (btnGps && searchLocation) {
        btnGps.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            triggerIndexGps();
        });
    }
});

// Prepínač jazyka — zobrazí prekladací loader počas presmerovania (rovnaký princíp ako aveino)
(function () {
    const translatingText = {
        sk: 'Prekladám...', cz: 'Překládám...', en: 'Translating...',
        de: 'Übersetzen...', pl: 'Tłumaczenie...', hu: 'Fordítás...', ua: 'Переклад...'
    };
    const langLinks = document.querySelectorAll('.lang-menu a[data-lang]');
    if (!langLinks.length) return;

    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-family:inherit;opacity:0;visibility:hidden;transition:opacity 0.2s ease;';
    overlay.innerHTML = `
        <style>@keyframes langLoaderSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }</style>
        <div style="position:relative;width:70px;height:70px;margin-bottom:18px;display:flex;align-items:center;justify-content:center;">
            <div style="position:absolute;width:60px;height:60px;border:3px solid rgba(255,255,255,0.15);border-top:3px solid #b08042;border-radius:50%;animation:langLoaderSpin 1s linear infinite;"></div>
            <span class="material-symbols-outlined" style="font-size:28px;color:#b08042;">translate</span>
        </div>
        <div id="lang-loader-text" style="font-size:17px;font-weight:700;"></div>
    `;
    document.body.appendChild(overlay);

    langLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const lang = this.dataset.lang;
            document.getElementById('lang-loader-text').textContent = translatingText[lang] || 'Translating...';
            overlay.style.visibility = 'visible';
            overlay.style.opacity = '1';
            setTimeout(function () { window.location.href = link.href; }, 150);
        });
    });
})();
</script>
<?php include 'includes/ai_chat_modal.php'; ?>
</body>

</html>
