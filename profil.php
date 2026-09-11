<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';


// Map of all categories with Material Symbols Outlined icons, image paths, and descriptions
$category_definitions = [
    'Last minute' => ['icon' => 'bolt', 'img' => 'assets/img/cat_hair.png', 'desc' => 'Zľavnené a voľné termíny na poslednú chvíľu'],
    'Vlasy' => ['icon' => 'face', 'img' => 'assets/img/cat_hair.png', 'desc' => 'Strihy, farbenie, styling a fúkaná'],
    'Holičstvo a Barber' => ['icon' => 'content_cut', 'img' => 'assets/img/barber_shop.png', 'desc' => 'Úprava brady, klasické holenie a strih'],
    'Kozmetika a make-up' => ['icon' => 'face_retouching_natural', 'img' => 'assets/img/cat_makeup.png', 'desc' => 'Čistenie pleti, líčenie a ošetrenia'],
    'Nechty' => ['icon' => 'back_hand', 'img' => 'assets/img/cat_nails.png', 'desc' => 'Manikúra, pedikúra a gélové nechty'],
    'Obočie a mihalnice' => ['icon' => 'visibility', 'img' => 'assets/img/cat_brows.png', 'desc' => 'Laminácia, farbenie a predlžovanie rias'],
    'Masáže a wellness' => ['icon' => 'spa', 'img' => 'assets/img/cat_wellness.png', 'desc' => 'Relaxačné, thajské a športové masáže'],
    'Vrkoče a dredy' => ['icon' => 'waves', 'img' => 'assets/img/cat_braids.png', 'desc' => 'Zapletanie copíkov, boxerské vrkoče a dredy'],
    'Tetovanie' => ['icon' => 'ink_pen', 'img' => 'assets/img/cat_tattoo.png', 'desc' => 'Umelecké tetovanie a permanentný make-up'],
    'Lekárska estetika' => ['icon' => 'medical_services', 'img' => 'assets/img/cat_medical.png', 'desc' => 'Botox, kyselina hyalurónová a lifting'],
    'Depilácia a epilácia' => ['icon' => 'eco', 'img' => 'assets/img/cat_depilation.png', 'desc' => 'Laserová epilácia, vosk a cukrová pasta'],
    'Domáce služby' => ['icon' => 'home', 'img' => 'assets/img/cat_home.png', 'desc' => 'Salónne a kozmetické služby priamo u vás'],
    'Piercing' => ['icon' => 'scatter_plot', 'img' => 'assets/img/cat_piercing.png', 'desc' => 'Prepichovanie uší, nosa a tela'],
    'Služby pre miláčikov' => ['icon' => 'pets', 'img' => 'assets/img/cat_pets.png', 'desc' => 'Strihanie, kúpanie a starostlivosť o zvieratá'],
    'Zubné a ortodontické' => ['icon' => 'dentistry', 'img' => 'assets/img/cat_dentistry.png', 'desc' => 'Dentálna hygiena, bielenie zubov a rovnátka'],
    'Zdravie a kondícia' => ['icon' => 'fitness_center', 'img' => 'assets/img/cat_fitness.png', 'desc' => 'Fitness centrá, tréningy a regenerácia'],
    'Profesionálne služby' => ['icon' => 'work', 'img' => 'assets/img/cat_proservices.png', 'desc' => 'Odborné konzultácie, kurzy a školenia'],
    'Solárium a opaľovanie' => ['icon' => 'sunny', 'img' => 'assets/img/cat_solarium.png', 'desc' => 'Vertikálne a horizontálne soláriá, samoopaľovanie'],
    'Joga a Pilates' => ['icon' => 'self_improvement', 'img' => 'assets/img/cat_yoga.png', 'desc' => 'Skupinové a individuálne lekcie jogy'],
    'Fyzioterapia' => ['icon' => 'healing', 'img' => 'assets/img/cat_physio.png', 'desc' => 'Rehabilitácia, manuálna terapia a masáže'],
    'Osobní tréneri' => ['icon' => 'directions_run', 'img' => 'assets/img/cat_trainer.png', 'desc' => 'Individuálne tréningové plány a koučing'],
    'Výživové poradenstvo' => ['icon' => 'restaurant_menu', 'img' => 'assets/img/cat_nutrition.png', 'desc' => 'Jedálničky na mieru a nutričné poradenstvo'],
    'Svadobné služby' => ['icon' => 'celebration', 'img' => 'assets/img/cat_wedding.png', 'desc' => 'Svadobné líčenie, účesy a kompletný styling'],
    'Alternatívna medicína' => ['icon' => 'emoji_nature', 'img' => 'assets/img/cat_altmedicine.png', 'desc' => 'Akupunktúra, bylinkárstvo a celostná terapia'],
    'Psychológia a Terapia' => ['icon' => 'psychology', 'img' => 'assets/img/cat_psychology.png', 'desc' => 'Psychologické poradenstvo, koučing a rozvoj'],
    'Iné' => ['icon' => 'more_horiz', 'img' => 'assets/img/cat_other.png', 'desc' => 'Ďalšie špecializované služby']
];


// Embed režim — zjednodušené zobrazenie bez bočného panelu a odkazov mimo appky, určené na vloženie
// cez <iframe> na vlastný web prevádzky (Fáza 4: Widget)
$embed = isset($_GET['embed']) && $_GET['embed'] === '1';

// Získanie ID alebo SLUG prevádzky
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : (isset($_GET['username']) ? trim($_GET['username']) : '');
$demo_tier = isset($_GET['tier']) ? trim($_GET['tier']) : '';

// Zachytenie UTM parametrov (napr. z odkazu na Facebooku/Instagrame) do session — priradí sa
// ku konkrétnemu zákazníkovi až pri jeho prvej rezervácii v api/book_appointment.php
if ($id > 0 && (!empty($_GET['utm_source']) || !empty($_GET['utm_medium']) || !empty($_GET['utm_campaign']))) {
    $_SESSION['utm_attribution'][$id] = [
        'source' => trim($_GET['utm_source'] ?? ''),
        'medium' => trim($_GET['utm_medium'] ?? ''),
        'campaign' => trim($_GET['utm_campaign'] ?? ''),
        'page' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/profil.php'
    ];
}

// Načítanie údajov z DB
$establishment = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT e.*, u.avatar_path as avatar_url, u.email as user_email, u.subscription_tier as u_subscription_tier FROM establishments e LEFT JOIN users u ON e.user_id = u.id WHERE e.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $establishment = $res->fetch_assoc();
    }
} elseif (!empty($slug)) {
    $clean_slug = ltrim($slug, '@');
    $slug_at = '@' . $clean_slug;
    $stmt = $conn->prepare("SELECT e.*, u.avatar_path as avatar_url, u.email as user_email, u.subscription_tier as u_subscription_tier FROM establishments e LEFT JOIN users u ON e.user_id = u.id WHERE e.custom_url = ? OR e.custom_url = ? OR u.public_id = ? OR e.id = ?");
    $slug_id = (int)$clean_slug;
    $stmt->bind_param("sssi", $clean_slug, $slug_at, $clean_slug, $slug_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $establishment = $res->fetch_assoc();
    }
}

// Fallback demo dáta
if (!$establishment) {
    if ($id == 2 || $slug == 'bodyart' || $demo_tier === 'pro') {
        $establishment = [
            'id' => 2,
            'name' => 'Piercing Studio BodyArt',
            'category' => 'Piercing',
            'city' => 'Bratislava',
            'address' => 'Gorkého 6, 811 01 Bratislava',
            'phone' => '+421 905 111 222',
            'description' => 'Špičkové štúdio pre profesionálny piercing v centre Bratislavy. Ponúkame bezpečné aplikácie piercingov v sterilnom medicínskom prostredí s titánovými šperkami najvyššej kvality. Príďte si oddýchnuť a zverte sa do rúk certifikovaných odborníkov.',
            'image_url' => 'assets/img/cat_piercing.png',
            'rating' => 4.95,
            'subscription_tier' => 'pro',
            'custom_url' => 'bodyart'
        ];
    } else {
        $establishment = [
            'id' => 1,
            'name' => 'Gentleman Barber & Kaderníctvo',
            'category' => 'Holičstvo a Barber',
            'city' => 'Žilina',
            'address' => 'Národná 14, 010 01 Žilina',
            'phone' => '+421 912 345 678',
            'description' => 'Tradičné pánske kaderníctvo a holičstvo v centre Žiliny. Klasické aj moderné strihy, úprava brady a precízne holenie britvou.',
            'image_url' => 'assets/img/barber_shop.png',
            'rating' => 4.8,
            'subscription_tier' => 'free',
            'custom_url' => 'marsoft'
        ];
    }
}

// Zistenie prihláseného používateľa
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT id, email, full_name, phone, role, avatar_url, is_verified FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    if ($u_res && $u_res->num_rows > 0) {
        $current_user = $u_res->fetch_assoc();
    }
}
$is_user_logged_in = !empty($current_user);
$is_user_verified = $is_user_logged_in && ((int)($current_user['is_verified'] ?? 0) === 1);

// Určenie Tier-u a prístupových oprávnení
$tierOrderMap = ['free' => 0, 'start' => 1, 'pro' => 2, 'vip' => 3];
$eTierVal = $establishment['subscription_tier'] ?? 'free';
$uTierVal = $establishment['u_subscription_tier'] ?? 'free';
$tier = (($tierOrderMap[$uTierVal] ?? 0) >= ($tierOrderMap[$eTierVal] ?? 0)) ? $uTierVal : $eTierVal;
if ($demo_tier) {
    $tier = $demo_tier;
}
$is_pro = in_array($tier, ['pro', 'vip']) || $id == 2 || $slug == 'bodyart';
$is_vip = ($tier === 'vip');
$is_start = in_array($tier, ['start', 'pro', 'vip']) || $id == 2 || $slug == 'bodyart';

// Načítanie služieb z DB
$services = [];
$s_stmt = $conn->prepare("SELECT s.*, sc.name as category_name FROM services s INNER JOIN service_categories sc ON sc.id = s.category_id WHERE (s.business_id = ? OR (s.establishment_id > 0 AND s.establishment_id = ?)) ORDER BY s.price ASC");
if ($s_stmt) {
    $s_business_id = (int)($establishment['user_id'] ?? 0);
    $s_stmt->bind_param("ii", $s_business_id, $establishment['id']);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result();
    while ($s = $s_res->fetch_assoc()) {
        $services[] = $s;
    }
}

// Ak nemá služby v DB, pridáme ukážkové
if (empty($services)) {
    if ($establishment['category'] === 'Piercing' || $is_pro) {
        $services = [
            ['id' => 101, 'name' => 'Prepichnutie lalôčika + titánový šperk', 'duration_minutes' => 20, 'price' => 25.00, 'description' => 'Aplikácia ihlou a zavedenie sterilného titánového šperku.'],
            ['id' => 102, 'name' => 'Piercing nosa (Nostril / Septum)', 'duration_minutes' => 30, 'price' => 35.00, 'description' => 'Profesionálny piercing nosového krídla alebo prepážky.'],
            ['id' => 103, 'name' => 'Piercing Helix / Tragus', 'duration_minutes' => 30, 'price' => 40.00, 'description' => 'Piercing chrupavky ucha s dezinfekčným ošetrením a návodom na starostlivosť.'],
            ['id' => 104, 'name' => 'Piercing pupku (Navel)', 'duration_minutes' => 35, 'price' => 45.00, 'description' => 'Klasický pupkový piercing s ozdobným kamienkom.']
        ];
    } else {
        $services = [
            ['id' => 201, 'name' => 'Klasický pánsky strih a styling', 'duration_minutes' => 30, 'price' => 18.00, 'description' => 'Strihanie nožnicami a strojčekom, umytie vlasov, finálny styling pomádou.'],
            ['id' => 202, 'name' => 'Kompletná úprava brady + horúci uterák', 'duration_minutes' => 20, 'price' => 12.00, 'description' => 'Formovanie brady, zaholenie kontúr britvou, ošetrenie olejom a balzamom.'],
            ['id' => 203, 'name' => 'Kombinácia: Strih vlasov & Úprava brady', 'duration_minutes' => 50, 'price' => 28.00, 'description' => 'Kompletný balík pre muža – nový strih, upravená brada a masáž hlavy.'],
            ['id' => 204, 'name' => 'Masáž hlavy a regeneračná kúra', 'duration_minutes' => 15, 'price' => 8.00, 'description' => 'Uvoľňujúca masáž pokožky hlavy s tonikom a výživným sérom.']
        ];
    }
}

// Realna fotogaleria prevadzky z business_gallery (rovnaka tabulka ako v dashboarde)
$business_gallery = [];
if (!empty($establishment['user_id'])) {
    $gal_stmt = $conn->prepare("SELECT media_type, media_url FROM business_gallery WHERE business_id = ? ORDER BY order_index ASC, id DESC");
    $gal_stmt->bind_param("i", $establishment['user_id']);
    $gal_stmt->execute();
    $gal_res = $gal_stmt->get_result();
    while ($g = $gal_res->fetch_assoc()) {
        $business_gallery[] = $g;
    }
}
// Zoznam unikatnych kategorii sluzieb pre filter (len ked ma zmysel - viac ako 1 kategoria)
$service_categories_present = [];
foreach ($services as $svc) {
    $catName = $svc['category_name'] ?? null;
    $catId = $svc['category_id'] ?? 0;
    if ($catName && !isset($service_categories_present[$catId])) {
        $service_categories_present[$catId] = $catName;
    }
}
// Hlavna kategoria prevadzky nech je v poradi filtrov hned za "Vsetko"
$mainCategoryName = trim(explode(',', $establishment['category'] ?? '')[0] ?? '');
if ($mainCategoryName) {
    $mainCatId = array_search($mainCategoryName, $service_categories_present, true);
    if ($mainCatId !== false) {
        $mainCatValue = $service_categories_present[$mainCatId];
        unset($service_categories_present[$mainCatId]);
        $service_categories_present = [$mainCatId => $mainCatValue] + $service_categories_present;
    }
}
$handle = !empty($establishment['custom_url']) ? '@' . ltrim($establishment['custom_url'], '@') : (!empty($establishment['slug']) ? '@' . $establishment['slug'] : '@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $establishment['name'])));
$img_src = !empty($establishment['avatar_url']) ? $establishment['avatar_url'] : (!empty($establishment['image_url']) ? $establishment['image_url'] : 'assets/img/cat_hair.png');
$mainCategorySlug = trim(explode(',', $establishment['category'] ?? '')[0] ?? '');
$categoryDefaultImg = $category_definitions[$mainCategorySlug]['img'] ?? 'assets/img/cat_hair.png';
$bg_cover = $is_pro ? (!empty($establishment['banner_url']) ? $establishment['banner_url'] : $categoryDefaultImg) : '';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($establishment['name']) ?> - <?= BRAND_NAME ?></title>

    <?php
    $og_url_path = !empty($establishment['custom_url']) ? '@' . rawurlencode($establishment['custom_url']) : 'profil.php?id=' . (int)($establishment['id'] ?? 0);
    $og_url = 'https://' . BRAND_SITE . '/' . $og_url_path;
    $og_desc = !empty($establishment['description']) ? mb_substr(trim($establishment['description']), 0, 160) : ('Rezervujte si termín online v ' . $establishment['name'] . ' cez ' . BRAND_NAME . '.');
    $og_image_url = 'https://' . BRAND_SITE . '/api/og_image.php?est_id=' . (int)($establishment['id'] ?? 0);
    ?>
    <meta name="description" content="<?= htmlspecialchars($og_desc) ?>">
    <meta property="og:type" content="business.business">
    <meta property="og:title" content="<?= htmlspecialchars($establishment['name']) ?> - <?= BRAND_NAME ?>">
    <meta property="og:description" content="<?= htmlspecialchars($og_desc) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($og_url) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image_url) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($establishment['name']) ?> - <?= BRAND_NAME ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($og_desc) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image_url) ?>">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    
    <!-- Flatpickr (Kalendár) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/sk.js"></script>
    <script src="assets/js/theme.js?v=2.0"></script>

    <style>
        :root {
            --primary-color: #b08042;
            --primary-hover: #906634;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #0F172A;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #f1f5f9;
            --font-main: 'Outfit', sans-serif;
            --font-heading: 'Outfit', sans-serif;
            --gold-gradient: var(--primary-color);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        html.dark-mode,
        body.dark-mode {
            --bg-color: #0F172A; /* Tmavá grafitová / Midnight navy */
            --bg-primary: #0F172A;
            --bg-secondary: #1e293b;
            --card-bg: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --input-bg: #0f172a;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.5);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.5), 0 2px 4px -2px rgb(0 0 0 / 0.5);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.5), 0 4px 6px -4px rgb(0 0 0 / 0.5);
        }

        /* Logo v dark mode (invertované farby pre čistý biely line-art so zlatým akcentom) */
        body.dark-mode .logo-link img,
        body.dark-mode .logo-img,
        html.dark-mode .logo-link img,
        html.dark-mode .logo-img {
            filter: invert(1) hue-rotate(180deg);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-main);
            background-color: var(--bg-color);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
            padding-bottom: 70px;
        }

        /* Top Navigation Bar */
        .topbar {
            max-height: 64px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
            overflow: hidden;
            transition: max-height 0.25s ease, border-color 0.25s ease;
        }
        .topbar.topbar-hidden {
            max-height: 0;
            border-bottom-color: transparent;
        }
        .topbar-left, .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topbar-more-menu { display: flex; align-items: center; gap: 12px; }
        .mobile-only-btn { display: none !important; }
        @media (max-width: 640px) {
            .mobile-only-btn { display: inline-flex !important; }
            .topbar-right { position: relative; }
            .topbar-more-menu {
                display: none;
                position: absolute;
                top: calc(100% + 8px);
                right: 0;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
                padding: 10px;
                width: auto;
                z-index: 2000;
            }
            .topbar-more-menu.open { display: flex; }
            .topbar-more-menu .top-btn {
                width: 40px !important;
                min-width: 40px !important;
                max-width: 40px !important;
                padding: 0 !important;
                justify-content: center;
                overflow: hidden;
            }
            .topbar-more-menu .top-btn > span:not(.material-symbols-outlined) {
                display: none;
            }
        }
        .logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .logo-img { height: 38px; width: auto; }
        
        .top-btn {
            height: 40px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .top-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .top-btn.icon-only { width: 40px; padding: 0; }

        /* Demo Switcher Pill */
        .demo-switcher {
            display: flex;
            align-items: center;
            background: rgba(176, 128, 66, 0.12);
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            padding: 4px 6px;
            gap: 4px;
        }
        .demo-switcher a {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        .demo-switcher a.active {
            background: var(--primary-color);
            color: #ffffff;
        }

        /* Container */
        .profile-container {
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 24px 20px 60px 20px;
            flex: 1;
        }

        /* PRO Cover Banner */
        .pro-cover-banner {
            width: 100%;
            height: 240px;
            border-radius: 24px;
            background-size: cover;
            background-position: center;
            position: relative;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: -60px;
        }
        .pro-cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.65) 100%);
        }
        .pro-cover-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(8px);
            color: #d4af37;
            border: 1px solid rgba(212, 175, 55, 0.4);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.80rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Profile Header Card */
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--shadow-md);
            position: relative;
            z-index: 10;
            margin-bottom: 24px;
        }
        .profile-card-top {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 20px;
        }
        .profile-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }
        .profile-avatar {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--shadow-md);
            background: var(--input-bg);
        }

        .profile-info { flex: 1; }
        .profile-title-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 6px;
        }
        .profile-name {
            font-family: var(--font-heading);
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        
        /* Badges */
        .badge-verified {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(34, 197, 94, 0.12);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        body.dark-mode .badge-verified { color: #4ade80; }

        .badge-handle {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(176, 128, 66, 0.14);
            color: var(--primary-color);
            border: 1px solid rgba(176, 128, 66, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.80rem;
            font-weight: 700;
        }
        .badge-free-id {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .profile-meta-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            color: var(--text-secondary);
            font-size: 0.88rem;
            font-weight: 500;
            margin-bottom: 14px;
        }
        .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .meta-item .material-symbols-outlined {
            font-size: 18px;
            color: var(--primary-color);
        }

        .profile-description {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* Profile Actions & Socials */
        .profile-actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .social-buttons {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .social-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .social-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn-book-primary {
            height: 44px;
            padding: 0 24px;
            border-radius: 12px;
            background: var(--gold-gradient);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(176, 128, 66, 0.3);
            transition: all 0.2s ease;
        }
        .btn-book-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(176, 128, 66, 0.4);
        }

        /* Spoločné značkové tlačidlá používané vo formulároch profilu */
        .btn-primary,
        .btn-secondary {
            -webkit-appearance: none;
            appearance: none;
            min-height: 40px;
            border-radius: 10px;
            padding: 0 18px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: var(--gold-gradient);
            color: #ffffff;
            border: 1px solid transparent;
            box-shadow: 0 3px 10px rgba(176, 128, 66, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 14px rgba(176, 128, 66, 0.35);
        }
        .btn-secondary {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1.5px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        .btn-secondary:hover {
            background: rgba(176, 128, 66, 0.08);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Free Promotion Claim Banner */
        .free-claim-banner {
            background: linear-gradient(135deg, rgba(176, 128, 66, 0.08) 0%, rgba(212, 175, 55, 0.15) 100%);
            border: 1px dashed var(--primary-color);
            border-radius: 18px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }
        .claim-text h4 { font-size: 0.95rem; font-weight: 700; color: var(--primary-color); margin-bottom: 2px; }
        .claim-text p { font-size: 0.82rem; color: var(--text-secondary); margin: 0; }
        .btn-upgrade {
            padding: 8px 16px;
            border-radius: 10px;
            background: var(--primary-color);
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        /* Layout Columns */
        .profile-grid-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
        }
        @media (max-width: 900px) {
            .profile-grid-layout { grid-template-columns: 1fr; }
            .profile-card-top { flex-direction: column; align-items: center; text-align: center; }
            .profile-meta-row { justify-content: center; }
            .profile-title-row { justify-content: center; }
            .profile-actions-bar { justify-content: center; }
            .social-buttons { justify-content: center; width: 100%; }
        }

        /* Section Cards */
        .section-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        .section-title {
            font-family: var(--font-heading);
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
        }
        .section-title .material-symbols-outlined { color: var(--primary-color); }

        /* Multi-Service Interactive Cards */
        .services-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 620px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .services-list::-webkit-scrollbar {
            display: none;
        }
        .service-row-card {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 18px;
            border-radius: 14px;
            border: 1.5px solid var(--border-color);
            background: var(--input-bg);
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }
        .service-row-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-sm);
            transform: translateX(3px);
        }
        .service-row-card.selected {
            border-color: var(--primary-color);
            background: rgba(176, 128, 66, 0.08);
            box-shadow: 0 4px 16px rgba(176, 128, 66, 0.15);
        }
        .service-left-flex {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }
        .service-checkbox {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--card-bg);
            color: transparent;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .service-row-card.selected .service-checkbox {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #ffffff;
        }
        .service-checkbox .material-symbols-outlined { font-size: 16px; font-weight: 800; }

        .service-main-info {
            min-width: 0;
        }
        .service-main-info h4 {
            font-size: 1.0rem;
            font-weight: 700;
            margin: 0 0 2px 0;
            color: var(--text-primary);
        }
        .service-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--text-secondary);
            min-width: 0;
        }
        .service-meta > span:first-child {
            flex-shrink: 0;
        }
        .svc-desc {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-top: 3px;
        }
        .service-pricing {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }
        .service-price {
            white-space: nowrap;
            font-family: var(--font-heading);
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        .btn-toggle-service {
            padding: 8px 16px;
            border-radius: 10px;
            background: var(--input-bg);
            color: var(--primary-color);
            border: 1.5px solid var(--primary-color);
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            pointer-events: none;
        }
        .service-row-card.selected .btn-toggle-service {
            background: var(--primary-color);
            color: #ffffff;
        }

        /* Sticky Bottom Booking Bar */
        .sticky-booking-bar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            top: auto;
            left: auto;
            width: 360px;
            max-width: calc(100vw - 40px);
            background: var(--card-bg);
            border: 1.5px solid var(--primary-color);
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
            padding: 18px 20px;
            display: none;
            flex-direction: column;
            z-index: 9999;
            animation: slideUpBarCompact 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUpBarCompact {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .sticky-bar-inner {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .sticky-summary {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        .sticky-count-pill {
            background: var(--primary-color);
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .sticky-details {
            display: flex;
            flex-direction: column;
        }
        .sticky-details-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-primary);
        }
        .sticky-details-sub {
            font-size: 0.82rem;
            color: var(--text-secondary);
        }
        .sticky-total-price {
            white-space: nowrap;
            font-family: var(--font-heading);
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-right: 12px;
        }

        /* Video Showcase */
        .video-box {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
        }
        .video-box iframe {
            position: absolute;
            top:0; left:0; width:100%; height:100%; border:none;
        }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
        }
        .gallery-thumb {
            width: 100%;
            height: 110px;
            border-radius: 12px;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .gallery-thumb:hover {
            transform: scale(1.04);
            box-shadow: var(--shadow-md);
        }

        /* Sidebar Info Widgets */
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.88rem;
        }
        .info-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(176, 128, 66, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-content strong { display: block; color: var(--text-primary); margin-bottom: 2px; }
        .info-content span { color: var(--text-secondary); }

        /* Jednofarebná elegantná mapa (Monochrome Map Embed) */
        .map-embed-wrapper {
            border-radius: 14px;
            overflow: hidden;
            height: 160px;
            border: 1px solid var(--border-color);
            margin-bottom: 12px;
            position: relative;
            background: var(--input-bg);
        }
        .map-embed-wrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
            filter: grayscale(100%) contrast(92%) opacity(0.88);
            transition: all 0.3s ease;
        }
        .map-embed-wrapper:hover iframe {
            filter: grayscale(20%) contrast(96%) opacity(1);
        }
        body.dark-mode .map-embed-wrapper iframe {
            filter: grayscale(100%) invert(92%) hue-rotate(180deg) contrast(90%) opacity(0.85);
        }
        body.dark-mode .map-embed-wrapper:hover iframe {
            filter: grayscale(30%) invert(92%) hue-rotate(180deg) contrast(95%) opacity(1);
        }

        /* Booking Wizard Modal */
        .booking-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(5px);
            z-index: 10005;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .share-icon-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 1.5px solid var(--border-color);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .share-icon-btn:hover {
            transform: translateY(-2px);
            background: currentColor;
        }
        .share-icon-btn:hover svg {
            fill: #fff;
        }
        .share-icon-btn:hover .material-symbols-outlined {
            color: #fff;
        }
        .booking-modal {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            width: 100%;
            max-width: 580px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Rezervacny modal - vystredene okno na desktope, cez cely displej na mobile */
        #bookingModalOverlay .booking-modal {
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        #bookingModalOverlay .modal-body {
            flex: 1;
            overflow-y: auto;
        }
        @media (max-width: 640px) {
            html.booking-modal-open,
            body.booking-modal-open {
                overflow: hidden !important;
                overscroll-behavior: none;
            }
            #bookingModalOverlay {
                width: 100vw;
                height: 100vh;
                height: 100dvh;
                padding: 0;
                align-items: stretch;
                overflow: hidden;
            }
            #bookingModalOverlay .booking-modal {
                max-width: 100%;
                width: 100%;
                height: 100vh;
                height: 100dvh;
                max-height: none;
                border-radius: 0;
                border: 0;
                box-shadow: none;
            }
            #bookingModalOverlay .modal-header {
                flex-shrink: 0;
            }
            #bookingModalOverlay .modal-body {
                min-height: 0;
                max-height: none;
                overflow-y: auto;
                overflow-x: hidden;
                overscroll-behavior: contain;
                padding-bottom: max(24px, calc(16px + env(safe-area-inset-bottom)));
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            #bookingModalOverlay .modal-body::-webkit-scrollbar {
                display: none;
            }
            #wizardServicesList {
                max-height: none !important;
                overflow: visible !important;
            }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-body {
            padding: 24px;
            max-height: 75vh;
            overflow-y: auto;
        }

        /* Krokovy wizard v rezervacnom formulari */
        .booking-steps-indicator { display: flex; align-items: flex-start; margin-bottom: 26px; padding: 0 2px; }
        .step-dot { display: flex; flex-direction: column; align-items: center; gap: 6px; flex-shrink: 0; }
        .step-dot .step-num {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--input-bg); border: 2px solid var(--border-color); color: var(--text-secondary);
            display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 800;
            transition: all 0.2s ease;
        }
        .step-dot.active .step-num { background: var(--primary-color); border-color: var(--primary-color); color: #ffffff; }
        .step-dot.done .step-num { background: #10b981; border-color: #10b981; color: #ffffff; }
        .step-dot .step-label { font-size: 0.66rem; color: var(--text-secondary); white-space: nowrap; }
        .step-dot.active .step-label { color: var(--text-primary); font-weight: 700; }
        .step-line { flex: 1; height: 2px; background: var(--border-color); margin: 17px 2px 0; min-width: 10px; }
        .step-nav { display: flex; justify-content: space-between; gap: 10px; margin-top: 20px; }
        .btn-step-back, .btn-step-next {
            padding: 11px 22px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; cursor: pointer;
        }
        .btn-step-back { background: var(--input-bg); border: 1.5px solid var(--border-color); color: var(--text-primary); }
        .btn-step-next { background: var(--gold-gradient); border: none; color: #ffffff; margin-left: auto; }
        .btn-toggle-more-options {
            display: flex; align-items: center; gap: 6px; background: none; border: none;
            color: var(--primary-color); font-size: 0.85rem; font-weight: 700; cursor: pointer; padding: 10px 0;
        }
        .wizard-service-card {
            display: flex; align-items: center; gap: 12px; padding: 12px 14px;
            border: 1.5px solid var(--border-color); border-radius: 12px; background: var(--input-bg);
            cursor: pointer; transition: all 0.15s ease;
        }
        .wizard-service-card:hover { border-color: var(--primary-color); }
        .wizard-service-card.selected { border-color: var(--primary-color); background: rgba(176, 128, 66, 0.08); }
        .wizard-service-checkbox {
            width: 20px; height: 20px; border-radius: 6px; border: 2px solid var(--border-color);
            display: flex; align-items: center; justify-content: center; color: transparent;
            background: var(--card-bg); flex-shrink: 0; transition: all 0.15s ease;
        }
        .wizard-service-checkbox .material-symbols-outlined { font-size: 14px; font-weight: 800; }
        .wizard-service-card.selected .wizard-service-checkbox {
            background: var(--primary-color); border-color: var(--primary-color); color: #ffffff;
        }

        /* Vyber pracovnika (karta so zamestnancom) */
        .employee-select-card {
            display: flex; align-items: center; gap: 14px; padding: 12px 16px;
            border: 1.5px solid var(--border-color); border-radius: 14px; background: var(--input-bg);
            cursor: pointer; transition: all 0.15s ease;
        }
        .employee-select-card:hover { border-color: var(--primary-color); }
        .employee-select-card.selected { border-color: var(--primary-color); background: rgba(176, 128, 66, 0.08); }
        .employee-radio {
            width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--border-color);
            flex-shrink: 0; position: relative; transition: all 0.15s ease;
        }
        .employee-select-card.selected .employee-radio { border-color: var(--primary-color); }
        .employee-select-card.selected .employee-radio::after {
            content: ''; position: absolute; inset: 3px; border-radius: 50%; background: var(--primary-color);
        }
        .employee-avatar {
            width: 44px; height: 44px; border-radius: 50%; background-size: cover; background-position: center;
            background-color: var(--card-bg); flex-shrink: 0;
        }
        .employee-avatar-generic {
            display: flex; align-items: center; justify-content: center; color: var(--text-secondary);
            border: 1.5px solid var(--border-color);
        }
        .employee-info { display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0; }
        .employee-info strong { font-size: 0.92rem; color: var(--text-primary); }
        .employee-title { font-size: 0.78rem; color: var(--text-secondary); }
        .employee-price { font-size: 0.95rem; font-weight: 700; color: var(--primary-color); white-space: nowrap; flex-shrink: 0; }

        /* Selected Services Summary Box */
        .selected-services-box {
            background: rgba(176, 128, 66, 0.08);
            border: 1.5px solid var(--primary-color);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .selected-services-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.76rem;
            font-weight: 800;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px dashed rgba(176, 128, 66, 0.3);
        }
        .selected-items-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 10px;
        }
        .selected-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.92rem;
            color: var(--text-primary);
        }
        .selected-item-row .svc-dur {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-left: 6px;
        }
        .selected-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.0rem;
            font-weight: 800;
            color: var(--text-primary);
            padding-top: 8px;
            border-top: 1px solid var(--border-color);
        }

        /* Customer Auth Card inside Modal */
        .customer-verified-card {
            background: rgba(34, 197, 94, 0.06);
            border: 1.5px solid rgba(34, 197, 94, 0.3);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .customer-avatar-small {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #16a34a;
            background: var(--input-bg);
            flex-shrink: 0;
        }
        .customer-details-col {
            flex: 1;
        }
        .customer-name-text {
            font-size: 1.0rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 2px;
        }
        .customer-email-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--text-secondary);
        }

        .customer-auth-required-box {
            background: rgba(176, 128, 66, 0.08);
            border: 1.5px dashed var(--primary-color);
            border-radius: 18px;
            padding: 22px;
            text-align: center;
            margin-bottom: 20px;
        }
        .customer-auth-required-box .lock-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: rgba(176, 128, 66, 0.15);
            color: var(--primary-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .customer-auth-required-box h4 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .customer-auth-required-box p {
            font-size: 0.86rem;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        /* Calendar Input Wrap with Custom Flatpickr */
        .calendar-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .calendar-input-wrap .cal-icon {
            position: absolute;
            left: 14px;
            color: var(--primary-color);
            font-size: 20px;
            pointer-events: none;
            z-index: 2;
        }
        .calendar-custom-input {
            width: 100%;
            height: 46px;
            padding: 0 16px 0 44px !important;
            border-radius: 12px !important;
            border: 1.5px solid var(--border-color) !important;
            background: var(--input-bg) !important;
            color: var(--text-primary) !important;
            font-family: inherit !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            outline: none !important;
            transition: all 0.2s ease !important;
        }
        .calendar-custom-input:focus, .calendar-custom-input:hover {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(176, 128, 66, 0.12) !important;
        }

        /* Time Slots Grid */
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
            gap: 8px;
            margin-top: 8px;
        }
        .time-slot-btn {
            padding: 9px 10px;
            border-radius: 10px;
            border: 1.5px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .time-slot-btn:hover, .time-slot-btn.selected {
            background: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        /* Toast notification */
        #toastNotice {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e1e1e;
            color: #ffffff;
            border: 1px solid #444;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 0.88rem;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            display: none;
            z-index: 10010;
        }

        /* ================= FLATPICKR PREMIUM THEME ================= */
        .flatpickr-calendar {
            background: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 16px !important;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.16), 0 2px 10px rgba(0, 0, 0, 0.05) !important;
            font-family: var(--font-main) !important;
            padding: 10px 12px 12px 12px !important;
            width: 290px !important;
            box-sizing: border-box !important;
            z-index: 10020 !important;
        }
        .flatpickr-calendar::before, .flatpickr-calendar::after { display: none !important; }
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
            background: transparent !important;
            border: none !important;
            cursor: default !important;
        }
        .flatpickr-current-month .numInputWrapper span.arrowUp,
        .flatpickr-current-month .numInputWrapper span.arrowDown { display: none !important; }
        .flatpickr-current-month input.cur-year {
            font-size: 0.85rem !important;
            font-weight: 400 !important;
            color: var(--text-secondary) !important;
            opacity: 0.65 !important;
            background: transparent !important;
            border: none !important;
            width: 36px !important;
            text-align: left !important;
            pointer-events: none !important;
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
        .flatpickr-innerContainer, .flatpickr-rContainer, .flatpickr-days {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
        }
        .flatpickr-weekdays {
            height: 26px !important;
            display: flex !important;
            width: 100% !important;
            overflow: hidden !important;
        }
        .flatpickr-weekdaycontainer, .dayContainer {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: flex-start !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }
        span.flatpickr-weekday {
            color: var(--text-secondary) !important;
            font-weight: 700 !important;
            font-size: 0.74rem !important;
            width: 14.2857143% !important;
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
            border: 1.5px solid transparent !important;
            width: 14.2857143% !important;
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
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 12px rgba(176, 128, 66, 0.35) !important;
        }
        .flatpickr-day.prevMonthDay { visibility: hidden !important; pointer-events: none !important; }
        .flatpickr-day.nextMonthDay { display: none !important; }
        .flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover {
            color: #94a3b8 !important;
            opacity: 1 !important;
            cursor: not-allowed !important;
            background: transparent !important;
        }
        body.dark-mode .flatpickr-day.flatpickr-disabled, body.dark-mode .flatpickr-day.flatpickr-disabled:hover {
            color: #64748b !important;
        }
    
/* Base page layout */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        input, textarea, select, button {
            max-width: 100%;
        }
        button {
            -webkit-appearance: none;
            appearance: none;
            font-family: inherit;
        }
        button:not([class]):not([style]) {
            min-height: 40px;
            padding: 0 16px;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--text-primary);
            font-weight: 700;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
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

        /* Rovnaký 2 px obrys kariet ako na ostatných stránkach. */
        [class~="card"],
        [class$="-card"],
        [class*="-card "] {
            border-width: 2px !important;
        }

        button,
        input,
        select,
        textarea,
        [class~="btn"],
        [class$="-btn"],
        [class*="-btn "],
        [class$="-button"],
        [class*="-button "] {
            border-width: 2px !important;
        }

        /* Logo v dark mode (invertované farby, aby čierna bola biela, ale zlatá ostala zlatá) */
        body.dark-mode .sidebar-logo img,
        body.dark-mode .brand-logo-link img,
        body.dark-mode .logo-container img {
            filter: invert(1) hue-rotate(180deg);
        }

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
            position: relative;
            z-index: 900;
            flex-shrink: 0;
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
        body.dark-mode .top-btn:not(.logout-btn):hover {
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
            background-color: rgba(176, 128, 66, 0.12) !important;
        }
        .top-btn .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
        }

        /* Icon-only Topbar Buttons (Presný zaoblený štvorec 40x40 ako sidebar toggle) */
        .topbar-left .top-btn,
        .theme-btn,
        .login-btn,
        .logout-btn,
        .top-btn.icon-only {
            width: 40px !important;
            min-width: 40px !important;
            max-width: 40px !important;
            padding: 0 !important;
            border-radius: 12px !important;
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

        
        
        /* Plavajuce tlacidlo "hore" */
        .floating-scroll-top {
            position: fixed;
            right: 20px;
            bottom: 90px;
            z-index: 1500;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--card-bg);
            border: 1.5px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            color: var(--text-primary);
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .floating-scroll-top.show { display: flex; }
        .floating-scroll-top:hover { border-color: var(--primary-color); color: var(--primary-color); }

        /* Plavajuce tlacidlo "Rezervovat" */
        .floating-reserve-btn {
            position: fixed;
            left: 50%;
            bottom: 20px;
            transform: translateX(-50%);
            z-index: 1500;
            background: var(--gold-gradient);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 14px 28px;
            font-weight: 700;
            font-size: 0.92rem;
            display: none;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-lg);
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
        }
        .floating-reserve-btn.show { display: flex; }
        @media (max-width: 640px) {
            .floating-reserve-btn {
                left: 16px;
                right: 16px;
                bottom: 16px;
                transform: none;
                justify-content: center;
            }
            .floating-scroll-top {
                right: 16px;
                bottom: 78px;
                width: 42px;
                height: 42px;
            }
        }

        /* Verejný profil na mobiloch */
        @media (max-width: 640px) {
            .app-topbar {
                padding: 0 12px;
            }

            .profile-container {
                padding: 14px 10px 90px;
            }

            .profile-grid-layout,
            .profile-main-col,
            .profile-side-col {
                width: 100%;
                min-width: 0;
            }

            .profile-card,
            .section-card {
                padding: 16px;
                border-radius: 16px;
            }

            .section-header {
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 8px 12px;
                margin-bottom: 14px;
            }

            .section-header > * {
                min-width: 0;
            }

            .section-title {
                max-width: 100%;
                font-size: 1.05rem;
                line-height: 1.3;
            }

            .services-list {
                padding-right: 0;
            }

            .service-row-card {
                gap: 8px;
                padding: 12px;
            }

            .service-row-card:hover {
                transform: none;
            }

            .service-left-flex {
                gap: 10px;
            }

            .service-main-info h4 {
                overflow-wrap: anywhere;
                font-size: 0.92rem;
            }

            .svc-desc {
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .service-price {
                font-size: 1.08rem;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .gallery-thumb {
                height: auto;
                aspect-ratio: 1 / 1;
            }

            .gift-voucher-card p {
                overflow-wrap: anywhere;
            }

            .gift-voucher-amounts {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .gift-voucher-amounts .gv-amount-btn {
                width: 100%;
                padding-inline: 8px !important;
            }

            .gift-voucher-amounts #gv-custom-amount {
                grid-column: 1 / -1;
                width: 100% !important;
            }

            .gift-voucher-buyer-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
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

</style>
</head>
<body class="<?= $embed ? 'embed-mode' : '' ?>">

<?php if ($embed): ?>
    <style>
        /* Widget/embed režim (Fáza 4) — bez bočného panela appky, čisto len profil a rezervácia */
        body.embed-mode { background: transparent; }
        .embed-mode #sidebar-hover-trigger,
        .embed-mode #sidebarBackdrop,
        .embed-mode #app-sidebar,
        .embed-mode #topbar-sidebar-toggle { display: none !important; }
    </style>
<?php endif; ?>

    <?php if (!$embed): ?>
    <!-- SCREEN-LEFT HOVER TRIGGER (Aveino style) -->
    <div id="sidebar-hover-trigger"></div>

    <!-- MOBILE SIDEBAR BACKDROP -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleMobileSidebar()"></div>

    <button type="button" id="floatingScrollTopBtn" class="floating-scroll-top" title="Späť hore" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <span class="material-symbols-outlined">arrow_upward</span>
    </button>
    <button type="button" id="floatingReserveBtn" class="floating-reserve-btn" onclick="openMultiBookingModal()">
        <span class="material-symbols-outlined">event_available</span>
        <span>Rezervovať termín</span>
    </button>
    <?php endif; ?>

    <!-- APP LAYOUT (Sidebar CLOSED by default) -->
    <div class="app-layout" id="app-layout">

        <?php if (!$embed): ?>
        <!-- FULL HEIGHT LEFT SIDEBAR (Aveino style) -->
        <aside id="app-sidebar" class="app-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo" title="<?= BRAND_SITE ?>">
                    <?php if (BRAND_NAME === 'Rezervos'): ?>
                    <img src="/rezervoslogo.png" alt="Rezervos">
                    <span class="logo-text"><?= BRAND_LOGO_TEXT ?></span>
                    <?php else: ?>
                    <img src="<?= ltrim(BRAND_LOGO, '/') ?>" alt="<?= BRAND_NAME ?> Logo">
                    <span class="logo-text">volne<span class="logo-accent">kreslo</span><span class="logo-tld">.sk</span></span>
                    <?php endif; ?>
                </a>
                <button class="sidebar-toggle" id="sidebar-toggle" title="Pripnúť / Odopnúť bočný panel">
                    <span class="material-symbols-outlined" id="sidebar-toggle-icon" style="font-size: 20px;">push_pin</span>
                </button>
            </div>

            <div class="sidebar-section-title">Kategórie</div>

            <nav class="sidebar-scroll-area">
                <!-- Všetky kategórie -->
                <a href="prevadzky.php" class="category-nav-item">
                    <span class="material-symbols-outlined nav-icon">grid_view</span>
                    <span>Všetky kategórie</span>
                </a>

                <!-- 27 Main Categories -->
                <?php foreach ($category_definitions as $cat_name => $cat_data): ?>
                    <?php
                    $is_active_cat = (isset($establishment['category']) && $establishment['category'] === $cat_name);
                    $cat_url = 'prevadzky.php?category=' . urlencode($cat_name);
                    ?>
                    <a href="<?php echo $cat_url; ?>" class="category-nav-item <?php echo $is_active_cat ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined nav-icon"><?php echo $cat_data['icon']; ?></span>
                        <span><?php echo htmlspecialchars($cat_name); ?></span>
                    </a>
                <?php endforeach; ?>

                <div style="height:1px;background:var(--border-color);margin:10px 16px;"></div>

                <a href="inzercia.php" class="category-nav-item">
                    <span class="material-symbols-outlined nav-icon">newspaper</span>
                    <span>Inzercia</span>
                </a>
            </nav>
        </aside>
        <?php endif; ?>

        <!-- MAIN CONTENT AREA -->
        <div class="app-main-content">

            <!-- STICKY TOPBAR -->
            <header class="app-topbar">
                <div class="topbar-left">
                    <!-- Toggle sidebar icon button -->
                    <button id="topbar-sidebar-toggle" title="Zobraziť / Skryť bočný panel">
                        <span class="material-symbols-outlined" id="topbar-sidebar-icon" style="font-size: 22px;">left_panel_open</span>
                    </button>

                    <!-- Domov link (v embed režime otvára nový tab, aby sa iframe na cudzom webe neprepísal) -->
                    <a href="index.php" class="top-btn icon-only" title="Domov" <?= $embed ? 'target="_blank"' : '' ?>>
                        <span class="material-symbols-outlined">home</span>
                    </a>
                </div>

                <div class="topbar-right">
                    <button id="topbar-more-toggle" class="top-btn icon-only mobile-only-btn" title="Viac možností" onclick="toggleTopbarMoreMenu()">
                        <span class="material-symbols-outlined">more_vert</span>
                    </button>
                    <div id="topbarMoreMenu" class="topbar-more-menu">
                    <?php $__onboardingPending = ($_SESSION['onboarding_completed'] ?? 1) == 0; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'business'): ?>
                            <?php if ($__onboardingPending): ?>
                            <a href="onboarding.php" class="top-btn icon-only" style="position:relative;" title="Dokončiť nastavenie" <?= $embed ? 'target="_blank"' : '' ?>>
                                <span class="material-symbols-outlined">rocket_launch</span>
                                <span style="position:absolute;top:-3px;right:-3px;width:9px;height:9px;border-radius:50%;background:#ef4444;border:2px solid var(--card-bg,#fff);"></span>
                            </a>
                            <?php else: ?>
                            <a href="dashboard.php" class="top-btn icon-only" title="Moja Prevádzka" <?= $embed ? 'target="_blank"' : '' ?>>
                                <span class="material-symbols-outlined">storefront</span>
                            </a>
                            <?php endif; ?>
                        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin.php" class="top-btn icon-only" title="Admin Panel" <?= $embed ? 'target="_blank"' : '' ?>>
                                <span class="material-symbols-outlined">admin_panel_settings</span>
                            </a>
                        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
                            <?php if ($__onboardingPending): ?>
                            <a href="onboarding.php" class="top-btn icon-only" style="position:relative;" title="Dokončiť nastavenie" <?= $embed ? 'target="_blank"' : '' ?>>
                                <span class="material-symbols-outlined">rocket_launch</span>
                                <span style="position:absolute;top:-3px;right:-3px;width:9px;height:9px;border-radius:50%;background:#ef4444;border:2px solid var(--card-bg,#fff);"></span>
                            </a>
                            <?php else: ?>
                            <a href="moj_profil.php" class="top-btn icon-only" title="Môj Profil" <?= $embed ? 'target="_blank"' : '' ?>>
                                <span class="material-symbols-outlined">person</span>
                            </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Tmavý režim -->
                        <button id="theme-toggle" class="top-btn theme-btn" aria-label="Toggle Dark Mode" title="Tmavý režim">
                            <span class="material-symbols-outlined">dark_mode</span>
                        </button>
                        <a href="logout.php" class="top-btn logout-btn" title="Odhlásiť sa" <?= $embed ? 'target="_blank"' : '' ?>>
                            <span class="material-symbols-outlined">logout</span>
                        </a>
                    <?php else: ?>
                        <!-- Tmavý režim -->
                        <button id="theme-toggle" class="top-btn theme-btn" aria-label="Toggle Dark Mode" title="Tmavý režim">
                            <span class="material-symbols-outlined">dark_mode</span>
                        </button>
                        <a href="#" onclick="openAuthModal(); return false;" class="top-btn login-btn" title="Prihlásiť sa" aria-label="Prihlásiť sa">
                            <span class="material-symbols-outlined">login</span>
                        </a>
                    <?php endif; ?>
                    </div>
                </div>
            </header>

    <main class="profile-container">

        <!-- PRO COVER BANNER (LEN PRE PRO VERZIU) -->
        <?php if ($is_pro): ?>
            <div class="pro-cover-banner" style="background-image: url('<?= htmlspecialchars($bg_cover) ?>');">
                <div class="pro-cover-overlay"></div>
            </div>
        <?php endif; ?>

        <!-- HLAVNÁ PROFILOVÁ KARTA -->
        <div class="profile-card" style="<?= !$is_pro ? 'margin-top: 10px;' : '' ?>">
            <div class="profile-card-top">
                <div class="profile-avatar-wrapper">
                    <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($establishment['name']) ?>" class="profile-avatar <?= $is_pro ? 'avatar-pro-ring' : '' ?>">
                </div>

                <div class="profile-info">
                    <div class="profile-title-row">
                        <h1 class="profile-name"><?= htmlspecialchars($establishment['name']) ?></h1>
                        
                        <?php if ($is_pro): ?>
                            <span class="badge-handle" title="Vlastná adresa profilu">
                                <?= htmlspecialchars($handle) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-free-id" title="Verejné ID prevádzky">
                                ID: #<?= htmlspecialchars($establishment['id']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="profile-meta-row">
                        <div class="meta-item">
                            <span class="material-symbols-outlined">category</span>
                            <span><?= htmlspecialchars($establishment['category']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="material-symbols-outlined">location_on</span>
                            <span><?= htmlspecialchars($establishment['address']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="material-symbols-outlined" style="color: #eab308;">star</span>
                            <strong style="color: var(--text-primary);"><?= number_format($establishment['rating'] ?? 5.0, 1) ?></strong>
                            <span>(112 hodnotení)</span>
                        </div>
                    </div>

                    <p class="profile-description">
                        <?= nl2br(htmlspecialchars($establishment['description'])) ?>
                    </p>
                </div>
            </div>

            <?php
            // Normalizácia sociálnych odkazov uložených v dashboarde (môžu byť zadané ako @handle, doména bez https:// alebo plné URL)
            $social_ig_url = null;
            if (!empty($establishment['social_ig'])) {
                $v = trim($establishment['social_ig']);
                $social_ig_url = str_starts_with($v, 'http') ? $v : ('https://instagram.com/' . ltrim($v, '@'));
            }
            $social_fb_url = null;
            if (!empty($establishment['social_fb'])) {
                $v = trim($establishment['social_fb']);
                $social_fb_url = str_starts_with($v, 'http') ? $v : ('https://' . $v);
            }
            $social_ws_url = null;
            if (!empty($establishment['social_ws'])) {
                $v = trim($establishment['social_ws']);
                $social_ws_url = str_starts_with($v, 'http') ? $v : ('https://wa.me/' . preg_replace('/[^0-9]/', '', $v));
            }
            $social_web_url = null;
            if (!empty($establishment['social_web'])) {
                $v = trim($establishment['social_web']);
                $social_web_url = str_starts_with($v, 'http') ? $v : ('https://' . $v);
            }
            $social_tiktok_url = null;
            if (!empty($establishment['social_tiktok'])) {
                $v = trim($establishment['social_tiktok']);
                $social_tiktok_url = str_starts_with($v, 'http') ? $v : ('https://tiktok.com/@' . ltrim($v, '@'));
            }
            $social_youtube_url = null;
            if (!empty($establishment['social_youtube'])) {
                $v = trim($establishment['social_youtube']);
                $social_youtube_url = str_starts_with($v, 'http') ? $v : ('https://' . $v);
            }
            $social_telegram_url = null;
            if (!empty($establishment['social_telegram'])) {
                $v = trim($establishment['social_telegram']);
                $social_telegram_url = str_starts_with($v, 'http') ? $v : ('https://t.me/' . ltrim($v, '@'));
            }
            $social_x_url = null;
            if (!empty($establishment['social_x'])) {
                $v = trim($establishment['social_x']);
                $social_x_url = str_starts_with($v, 'http') ? $v : ('https://x.com/' . ltrim($v, '@'));
            }
            $social_linkedin_url = null;
            if (!empty($establishment['social_linkedin'])) {
                $v = trim($establishment['social_linkedin']);
                $social_linkedin_url = str_starts_with($v, 'http') ? $v : ('https://' . $v);
            }
            ?>
            <!-- Spodný panel akcií -->
            <div class="profile-actions-bar">
                <div class="social-buttons">
                    <?php if ($is_pro): ?>
                        <?php if ($social_ig_url): ?>
                        <a href="<?= htmlspecialchars($social_ig_url) ?>" target="_blank" rel="noopener" class="social-btn" title="Instagram">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"><defs><linearGradient id="ig-grad-pub" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></linearGradient></defs><rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke="url(#ig-grad-pub)" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke="url(#ig-grad-pub)" stroke-width="2"/><circle cx="18" cy="6" r="1.2" fill="url(#ig-grad-pub)"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_fb_url): ?>
                        <a href="<?= htmlspecialchars($social_fb_url) ?>" target="_blank" rel="noopener" class="social-btn" title="Facebook">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="#1877F2"><path d="M22 12.06C22 6.51 17.52 2 12 2S2 6.51 2 12.06C2 17.06 5.66 21.21 10.44 22v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22C18.34 21.21 22 17.06 22 12.06z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_ws_url): ?>
                        <a href="<?= htmlspecialchars($social_ws_url) ?>" target="_blank" rel="noopener" class="social-btn" title="WhatsApp">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="#25D366"><path d="M12.03 2C6.51 2 2.02 6.48 2.02 12c0 1.99.58 3.85 1.58 5.41L2 22l4.72-1.55A9.96 9.96 0 0012.03 22C17.55 22 22 17.52 22 12S17.55 2 12.03 2zm5.8 14.19c-.25.7-1.44 1.34-1.99 1.4-.51.06-1.15.08-1.86-.12-.43-.12-.98-.31-1.69-.61-2.97-1.28-4.91-4.27-5.06-4.47-.15-.2-1.21-1.61-1.21-3.07s.76-2.17 1.03-2.47c.27-.29.6-.36.79-.36.2 0 .4 0 .57.01.18.01.43-.07.67.51.25.6.85 2.07.92 2.22.07.15.12.33.02.53-.1.2-.15.32-.3.49-.15.17-.31.38-.44.51-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.36 1.46.29.15.46.12.63-.07.17-.2.72-.84.92-1.13.2-.29.4-.24.66-.15.27.1 1.71.81 2 .96.29.15.49.22.56.34.07.13.07.72-.18 1.4z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_telegram_url): ?>
                        <a href="<?= htmlspecialchars($social_telegram_url) ?>" target="_blank" rel="noopener" class="social-btn" title="Telegram">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="#26A5E4"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8l-1.65 7.78c-.12.55-.45.69-.92.43l-2.55-1.88-1.23 1.18c-.14.14-.25.25-.51.25l.18-2.6 4.72-4.27c.21-.18-.04-.29-.32-.1l-5.84 3.68-2.51-.79c-.55-.17-.56-.55.11-.81l9.82-3.78c.46-.17.86.11.7.91z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_tiktok_url): ?>
                        <a href="<?= htmlspecialchars($social_tiktok_url) ?>" target="_blank" rel="noopener" class="social-btn" title="TikTok">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="var(--text-primary)"><path d="M16.6 5.82s.51.5 0 0A4.278 4.278 0 0115.54 3h-3.09v12.4a2.592 2.592 0 01-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 004.3 1.38V7.3s-1.88.09-3.24-1.48z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_youtube_url): ?>
                        <a href="<?= htmlspecialchars($social_youtube_url) ?>" target="_blank" rel="noopener" class="social-btn" title="YouTube">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="#FF0000"><path d="M23.5 6.19a3.02 3.02 0 00-2.12-2.14C19.51 3.5 12 3.5 12 3.5s-7.51 0-9.38.55A3.02 3.02 0 00.5 6.19 31.6 31.6 0 000 12a31.6 31.6 0 00.5 5.81 3.02 3.02 0 002.12 2.14C4.49 20.5 12 20.5 12 20.5s7.51 0 9.38-.55a3.02 3.02 0 002.12-2.14A31.6 31.6 0 0024 12a31.6 31.6 0 00-.5-5.81zM9.75 15.57V8.43L15.82 12l-6.07 3.57z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_x_url): ?>
                        <a href="<?= htmlspecialchars($social_x_url) ?>" target="_blank" rel="noopener" class="social-btn" title="X (Twitter)">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="var(--text-primary)"><path d="M18.9 2H22l-7.19 8.21L23.3 22h-6.62l-5.18-6.78L5.5 22H2.4l7.7-8.8L1 2h6.78l4.68 6.2L18.9 2zm-1.16 18h1.83L7.34 3.9H5.38L17.74 20z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_linkedin_url): ?>
                        <a href="<?= htmlspecialchars($social_linkedin_url) ?>" target="_blank" rel="noopener" class="social-btn" title="LinkedIn">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="#0A66C2"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.38-1.85 3.62 0 4.28 2.38 4.28 5.47v6.27zM5.34 7.43a2.06 2.06 0 110-4.12 2.06 2.06 0 010 4.12zM7.12 20.45H3.56V9h3.56v11.45z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($establishment['contact_email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($establishment['contact_email']) ?>" class="social-btn" title="Napísať e-mail">
                            <span class="material-symbols-outlined" style="font-size: 19px;">mail</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_web_url): ?>
                        <a href="<?= htmlspecialchars($social_web_url) ?>" target="_blank" rel="noopener" class="social-btn" title="Webstránka">
                            <span class="material-symbols-outlined" style="font-size: 19px;">language</span>
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <button type="button" class="top-btn" onclick="shareProfile()" title="Zdieľať profil">
                        <span class="material-symbols-outlined" style="font-size: 18px;">share</span>
                        <span>Zdieľať</span>
                    </button>
                    <a href="tel:<?= htmlspecialchars($establishment['phone']) ?>" class="top-btn" title="Zavolať">
                        <span class="material-symbols-outlined" style="font-size: 18px;">call</span>
                        <span><?= htmlspecialchars($establishment['phone']) ?></span>
                    </a>
                </div>

                <a href="#" class="btn-book-primary" onclick="openMultiBookingModal(); return false;">
                    <span class="material-symbols-outlined">event_available</span>
                    <span>Rezervovať termín</span>
                </a>
            </div>
        </div>

        <!-- FREE BANNER NA UPGRADE (LEN PRE FREE VERZIU) -->
        <?php if (!$is_pro): ?>
            <div class="free-claim-banner">
                <div class="claim-text">
                    <h4>Ste majiteľom tejto prevádzky?</h4>
                    <p>Získajte vlastnú prémiovú adresu <strong><?= BRAND_SITE ?>/<?= htmlspecialchars($handle) ?></strong>, video prezentáciu, bohatú fotogalériu a prednostné umiestnenie.</p>
                </div>
                <a href="profil.php?id=<?= $establishment['id'] ?>&tier=pro" class="btn-upgrade">Aktivovať PRO profil</a>
            </div>
        <?php endif; ?>

        <!-- DVOJSTĹPCOVÝ LAYOUT: SLUŽBY + SIDEBAR -->
        <div class="profile-grid-layout" id="cennik">
            
            <!-- ĽAVÝ STĹPEC: SLUŽBY, GALÉRIA & VIDEO -->
            <div class="profile-main-col">
                
                <!-- Sekcia služieb a cenníka (VIACNÁSOBNÝ VÝBER) -->
                <div class="section-card">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">
                                <span class="material-symbols-outlined">format_list_bulleted</span>
                                <span>Ponuka služieb a cenník</span>
                            </h2>
                            <p style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 4px;">
                                Kliknutím môžete vybrať aj viacero služieb naraz
                            </p>
                        </div>
                        <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">
                            <?= count($services) ?> dostupné služby
                        </span>
                    </div>

                    <?php if (count($service_categories_present) > 1): ?>
                    <div id="serviceCategoryFilters" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
                        <button type="button" class="svc-cat-filter-btn active" data-cat-id="0" onclick="filterServicesByCategory(0, this)">Všetko</button>
                        <?php foreach ($service_categories_present as $catId => $catName): ?>
                            <button type="button" class="svc-cat-filter-btn" data-cat-id="<?= (int)$catId ?>" onclick="filterServicesByCategory(<?= (int)$catId ?>, this)"><?= htmlspecialchars($catName) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <style>
                        .svc-cat-filter-btn { padding:8px 16px; border-radius:10px; border:1.5px solid var(--border-color); background:var(--card-bg); color:var(--text-secondary); font-size:13px; font-weight:700; cursor:pointer; transition:all 0.15s; }
                        .svc-cat-filter-btn.active { background:var(--primary-color); border-color:var(--primary-color); color:#fff; }
                    </style>
                    <?php endif; ?>
                    <div class="services-list" id="servicesList">
                        <?php foreach ($services as $svc): ?>
                            <div class="service-row-card"
                                 data-id="<?= (int)$svc['id'] ?>"
                                 data-name="<?= htmlspecialchars($svc['name']) ?>"
                                 data-price="<?= number_format($svc['price'], 2, '.', '') ?>"
                                 data-duration="<?= (int)$svc['duration_minutes'] ?>"
                                 data-category-id="<?= (int)($svc['category_id'] ?? 0) ?>"
                                 style="cursor: default;">

                                <div class="service-left-flex">
                                    <div class="service-main-info">
                                        <h4><?= htmlspecialchars($svc['name']) ?></h4>
                                        <div class="service-meta">
                                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                                <span class="material-symbols-outlined" style="font-size: 16px;">schedule</span>
                                                <?= (int)$svc['duration_minutes'] ?> min.
                                            </span>
                                        </div>
                                        <?php if (!empty($svc['description'])): ?>
                                            <div class="svc-desc"><?= htmlspecialchars($svc['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="service-pricing">
                                    <span class="service-price"><?= number_format($svc['price'], 2) ?> €</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- PRO Sekcia: Video prezentácia (Len pre PRO / VIP) -->
                <?php 
                $vfile_1 = $establishment['video_file_1'] ?? '';
                $vfile_2 = $establishment['video_file_2'] ?? '';
                $vyt_1 = $establishment['video_url_1'] ?? '';
                $vyt_2 = $establishment['video_url_2'] ?? '';
                
                if (!function_exists('getYoutubeEmbedUrl')) {
                    function getYoutubeEmbedUrl($url) {
                        if (empty($url)) return null;
                        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/', $url, $matches)) {
                            return 'https://www.youtube.com/embed/' . $matches[1];
                        }
                        return null;
                    }
                }
                $embed1 = getYoutubeEmbedUrl($vyt_1);
                $embed2 = getYoutubeEmbedUrl($vyt_2);

                $all_videos = [];
                if (!empty($vfile_1)) $all_videos[] = ['type' => 'file', 'src' => $vfile_1];
                if (!empty($vfile_2)) $all_videos[] = ['type' => 'file', 'src' => $vfile_2];
                if (!empty($embed1)) $all_videos[] = ['type' => 'youtube', 'src' => $embed1];
                if (!empty($embed2)) $all_videos[] = ['type' => 'youtube', 'src' => $embed2];
                ?>
                <?php if ($is_pro && !empty($all_videos) && ($establishment['show_videos'] ?? 1)): ?>
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <span class="material-symbols-outlined">play_circle</span>
                                <span>Video prezentácia salónu</span>
                            </h2>
                            <span class="badge-verified" style="font-size: 0.72rem;">PRO & VIP</span>
                        </div>
                        <div style="display: grid; grid-template-columns: <?= count($all_videos) > 1 ? 'repeat(auto-fit, minmax(280px, 1fr))' : '1fr' ?>; gap: 16px;">
                            <?php foreach ($all_videos as $idx => $vid): ?>
                                <div class="video-box" style="margin-bottom: 0; aspect-ratio: 16/9; max-height: 75vh; border-radius: 12px; overflow: hidden; background: #000;">
                                    <?php if ($vid['type'] === 'file'): ?>
                                        <video src="<?= htmlspecialchars($vid['src']) ?>" controls playsinline style="width: 100%; height: 100%; object-fit: contain;" onloadedmetadata="if(this.videoWidth&&this.videoHeight){this.closest('.video-box').style.aspectRatio=this.videoWidth+'/'+this.videoHeight;}"></video>
                                    <?php else: ?>
                                        <iframe src="<?= htmlspecialchars($vid['src']) ?>" title="Prezentácia salónu <?= $idx + 1 ?>" style="width: 100%; height: 100%; border: none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($is_pro && !empty($business_gallery) && ($establishment['show_gallery'] ?? 1)): ?>
                    <!-- PRO/VIP Sekcia: Galéria priestorov a prác (realne fotky z dashboardu) -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <span class="material-symbols-outlined">photo_library</span>
                                <span>Fotogaléria priestorov a portfólio</span>
                            </h2>
                            <span style="font-size: 0.82rem; color: var(--text-secondary);"><?= count($business_gallery) ?> fotografií</span>
                        </div>
                        <div class="gallery-grid">
                            <?php foreach ($business_gallery as $gidx => $g): ?>
                                <?php if ($g['media_type'] === 'video'): ?>
                                    <div class="gallery-thumb" style="display:flex; align-items:center; justify-content:center; background:#000;">
                                        <span class="material-symbols-outlined" style="color:#fff; font-size:32px;">smart_display</span>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($g['media_url']) ?>" alt="Foto <?= $gidx + 1 ?>" class="gallery-thumb" style="cursor:pointer;" onclick="openGalleryLightbox(<?= $gidx ?>)">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <script>
                    const galleryLightboxImages = [<?php
                        $imgUrls = [];
                        foreach ($business_gallery as $g) { if ($g['media_type'] !== 'video') $imgUrls[] = "'" . addslashes($g['media_url']) . "'"; }
                        echo implode(',', $imgUrls);
                    ?>];
                    let galleryLightboxIndex = 0;
                    function openGalleryLightbox(idx) {
                        galleryLightboxIndex = idx;
                        const modal = document.getElementById('gallery-lightbox');
                        if (modal) { modal.style.display = 'flex'; updateGalleryLightboxImage(); }
                    }
                    function closeGalleryLightbox() { const modal = document.getElementById('gallery-lightbox'); if (modal) modal.style.display = 'none'; }
                    function galleryLightboxNav(dir) {
                        const total = galleryLightboxImages.length;
                        galleryLightboxIndex = (galleryLightboxIndex + dir + total) % total;
                        updateGalleryLightboxImage();
                    }
                    function updateGalleryLightboxImage() {
                        const img = document.getElementById('gallery-lightbox-img');
                        const counter = document.getElementById('gallery-lightbox-counter');
                        if (img) img.src = galleryLightboxImages[galleryLightboxIndex];
                        if (counter) counter.textContent = (galleryLightboxIndex + 1) + ' / ' + galleryLightboxImages.length;
                    }
                    document.addEventListener('keydown', function(e) {
                        const modal = document.getElementById('gallery-lightbox');
                        if (!modal || modal.style.display !== 'flex') return;
                        if (e.key === 'Escape') closeGalleryLightbox();
                        else if (e.key === 'ArrowLeft') galleryLightboxNav(-1);
                        else if (e.key === 'ArrowRight') galleryLightboxNav(1);
                    });
                    </script>

                    <div id="gallery-lightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:5000; align-items:center; justify-content:center;" onclick="if(event.target===this) closeGalleryLightbox();">
                        <button type="button" onclick="closeGalleryLightbox()" style="position:absolute; top:20px; right:24px; background:transparent; border:none; color:#fff; cursor:pointer;">
                            <span class="material-symbols-outlined" style="font-size:34px;">close</span>
                        </button>
                        <button type="button" onclick="galleryLightboxNav(-1)" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.12); border:none; color:#fff; border-radius:50%; width:48px; height:48px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                            <span class="material-symbols-outlined" style="font-size:28px;">chevron_left</span>
                        </button>
                        <img id="gallery-lightbox-img" src="" alt="Foto galéria" style="max-width:88vw; max-height:82vh; border-radius:10px; object-fit:contain;">
                        <button type="button" onclick="galleryLightboxNav(1)" style="position:absolute; right:16px; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.12); border:none; color:#fff; border-radius:50%; width:48px; height:48px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                            <span class="material-symbols-outlined" style="font-size:28px;">chevron_right</span>
                        </button>
                        <div id="gallery-lightbox-counter" style="position:absolute; bottom:22px; left:50%; transform:translateX(-50%); color:#fff; font-size:13px; font-weight:600; background:rgba(255,255,255,0.12); padding:5px 14px; border-radius:20px;"></div>
                    </div>
                <?php endif; ?>

                <?php if ($establishment['show_packages'] ?? 1): ?>
                <!-- Balíčky a členstvá (permanentky) -->
                <div class="section-card" id="packagesSection" style="display:none;">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="material-symbols-outlined">card_membership</span>
                            <span>Balíčky a členstvá</span>
                        </h2>
                    </div>
                    <div id="packagesList" style="display:flex;flex-direction:column;gap:12px;"></div>
                </div>
                <?php endif; ?>

                <?php if ($establishment['show_gift_vouchers'] ?? 1): ?>
                <!-- Darčekové poukazy -->
                <div class="section-card gift-voucher-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="material-symbols-outlined">card_giftcard</span>
                            <span>Darčekové poukazy</span>
                        </h2>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0 0 14px 0;">Kúpte darčekový poukaz v hodnote, akú si vyberiete — obdarovaný ho môže minúť na ľubovoľnú službu v tejto prevádzke.</p>
                    <div class="gift-voucher-amounts" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
                        <button type="button" class="gv-amount-btn btn-secondary" onclick="selectGiftAmount(20, this)" style="padding:8px 16px;border-radius:10px;">20 €</button>
                        <button type="button" class="gv-amount-btn btn-secondary" onclick="selectGiftAmount(50, this)" style="padding:8px 16px;border-radius:10px;">50 €</button>
                        <button type="button" class="gv-amount-btn btn-secondary" onclick="selectGiftAmount(100, this)" style="padding:8px 16px;border-radius:10px;">100 €</button>
                        <input type="number" id="gv-custom-amount" placeholder="Vlastná suma €" min="5" max="1000" step="1" style="width:150px;padding:8px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);" oninput="selectGiftAmount(null, null)">
                    </div>
                    <div class="gift-voucher-buyer-grid" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px;margin-bottom:10px;">
                        <input type="text" id="gv-buyer-name" placeholder="Vaše meno" style="width:100%;box-sizing:border-box;padding:9px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);">
                        <input type="email" id="gv-buyer-email" placeholder="Váš e-mail" style="width:100%;box-sizing:border-box;padding:9px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);">
                    </div>
                    <input type="text" id="gv-recipient-name" placeholder="Meno obdarovaného (nepovinné)" style="width:100%;box-sizing:border-box;padding:9px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);margin-bottom:10px;">
                    <textarea id="gv-message" rows="2" placeholder="Odkaz k darčeku (nepovinné)" style="width:100%;box-sizing:border-box;padding:9px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);resize:vertical;margin-bottom:14px;"></textarea>
                    <button type="button" class="btn-book-primary" onclick="purchaseGiftVoucher()" style="width:100%;justify-content:center;">
                        <span class="material-symbols-outlined">card_giftcard</span>
                        <span>Kúpiť darčekový poukaz</span>
                    </button>
                </div>
                <?php endif; ?>
                <?php if ($is_pro && ($establishment['show_newsletter'] ?? 0)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="material-symbols-outlined">mail</span>
                            <span>Odoberajte novinky</span>
                        </h2>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0 0 14px 0;">Zanechajte nám svoj e-mail a budeme vás informovať o akciách a novinkách tejto prevádzky.</p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                        <input type="email" id="nl-email" placeholder="Váš e-mail" style="flex:1;min-width:200px;padding:9px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);">
                        <button type="button" class="btn-book-primary" onclick="subscribeNewsletter()">
                            <span class="material-symbols-outlined">send</span>
                            <span>Odoberať</span>
                        </button>
                    </div>
                    <label style="display:flex;align-items:flex-start;gap:8px;font-size:12px;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" id="nl-agree" style="margin-top:2px;">
                        <span>Súhlasím so spracovaním osobných údajov a obchodnými podmienkami.</span>
                    </label>
                </div>
                <?php endif; ?>
            </div>

            <!-- PRAVÝ STĹPEC: OTVÁRACIE HODINY, MAPA, KONTAKT -->
            <div class="profile-side-col">
                
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title" style="font-size: 1.05rem;">
                            <span class="material-symbols-outlined">schedule</span>
                            <span>Otváracie hodiny</span>
                        </h3>
                    </div>
                    <div class="info-list">
                        <div style="display: flex; justify-content: space-between; font-size: 0.88rem; padding: 4px 0;">
                            <span style="color: var(--text-secondary);">Pondelok - Piatok:</span>
                            <strong style="color: var(--text-primary);">08:00 - 18:00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.88rem; padding: 4px 0;">
                            <span style="color: var(--text-secondary);">Sobota:</span>
                            <strong style="color: var(--text-primary);">09:00 - 14:00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.88rem; padding: 4px 0;">
                            <span style="color: var(--text-secondary);">Nedeľa:</span>
                            <strong style="color: #ef4444;">Zatvorené</strong>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title" style="font-size: 1.05rem;">
                            <span class="material-symbols-outlined">location_on</span>
                            <span>Kde nás nájdete</span>
                        </h3>
                    </div>
                    <div class="info-list" style="margin-bottom: 16px;">
                        <div class="info-row">
                            <div class="info-icon"><span class="material-symbols-outlined">pin_drop</span></div>
                            <div class="info-content" style="flex: 1;">
                                <strong>Adresa prevádzky</strong>
                                <span><?= htmlspecialchars($establishment['address']) ?></span>
                            </div>
                        </div>
                        <a href="tel:<?= htmlspecialchars($establishment['phone']) ?>" class="info-row" style="text-decoration:none;color:inherit;">
                            <div class="info-icon"><span class="material-symbols-outlined">call</span></div>
                            <div class="info-content">
                                <strong>Telefón</strong>
                                <span><?= htmlspecialchars($establishment['phone']) ?></span>
                            </div>
                        </a>
                    </div>
                    
                    <!-- Rýchla mapa náhľad (Jednofarebná) -->
                    <div class="map-embed-wrapper">
                        <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q=<?= urlencode($establishment['address']) ?>&t=&z=14&ie=UTF8&iwloc=&output=embed"></iframe>
                    </div>

                    <!-- Tlačidlá navigácie (LEN PRE START A VYŠŠIE) -->
                    <?php if ($is_start): ?>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($establishment['address']) ?>" target="_blank" class="top-btn" style="width: 100%; height: 42px; background: var(--gold-gradient); color: #ffffff; border: none; font-weight: 700; gap: 8px; justify-content: center; box-shadow: 0 4px 12px rgba(176, 128, 66, 0.25);">
                                <span class="material-symbols-outlined" style="font-size: 19px;">directions</span>
                                <span>Navigovať</span>
                            </a>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <a href="https://waze.com/ul?q=<?= urlencode($establishment['address']) ?>&navigate=yes" target="_blank" class="top-btn" style="height: 38px; font-size: 0.82rem; justify-content: center;" title="Navigovať cez Waze">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">near_me</span>
                                    <span>Waze</span>
                                </a>
                                <a href="https://maps.apple.com/?daddr=<?= urlencode($establishment['address']) ?>" target="_blank" class="top-btn" style="height: 38px; font-size: 0.82rem; justify-content: center;" title="Navigovať cez Apple Mapy">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">map</span>
                                    <span>Apple Mapy</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- FREE Verzia Zámok -->
                        <div style="background: var(--input-bg); border: 1px dashed var(--border-color); border-radius: 12px; padding: 12px; text-align: center; color: var(--text-secondary);">
                            <span class="material-symbols-outlined" style="font-size: 24px; color: #cbd5e1; margin-bottom: 4px;">lock</span>
                            <div style="font-size: 0.82rem; font-weight: 600;">Navigácia je dostupná od balíka START</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 16px; font-size: 0.78rem; color: var(--text-secondary);">
                    <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(BRAND_SITE) ?></span>
                    <?php if ($is_pro): ?>
                    <span class="badge-verified" title="Overená prémiová prevádzka">
                        <span class="material-symbols-outlined" style="font-size: 16px;">verified</span> Overený partner
                    </span>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </main>
        </div><!-- /.app-main-content -->
    </div><!-- /.app-layout -->


    <!-- REZERVAČNÝ MODAL (LEN PRE OVERENÝCH PRIHLÁSENÝCH POUŽÍVATEĽOV) -->
    <!-- Modal: Kúpa balíčka/členstva -->
    <div class="booking-modal-overlay" id="packagePurchaseModalOverlay" style="display:none;">
        <div class="booking-modal" style="max-width: 420px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">card_membership</span>
                    <h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800;" id="packagePurchaseTitle">Kúpa balíčka</h3>
                </div>
                <button type="button" onclick="document.getElementById('packagePurchaseModalOverlay').style.display='none'" style="background: none; border: none; cursor: pointer; color: var(--text-secondary);">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <p id="packagePurchaseInstructions" style="font-size: 0.9rem; color: var(--text-secondary);"></p>
                <div id="packagePurchaseCodeBox" style="margin: 10px auto 0; padding: 10px 16px; background: rgba(176,128,66,0.1); border: 1px dashed var(--primary-color); border-radius: 10px; display: none;">
                    <span style="font-size:0.78rem; color: var(--text-secondary); display:block;">Kód poukazu</span>
                    <code id="packagePurchaseCode" style="font-size: 1.1rem; font-weight: 800; color: var(--primary-color);"></code>
                </div>
                <img id="packagePurchaseQr" src="" alt="QR kód na platbu" style="max-width: 220px; margin: 12px auto; display: none; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; background: #fff;">
            </div>
        </div>
    </div>

    <!-- Modal: Zdieľať profil -->
    <div class="booking-modal-overlay" id="shareModalOverlay" style="display:none;">
        <div class="booking-modal" style="max-width: 380px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">share</span>
                    <h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800;">Zdieľať: <?= htmlspecialchars($establishment['name']) ?></h3>
                </div>
                <button type="button" onclick="closeShareModal()" style="background: none; border: none; cursor: pointer; color: var(--text-secondary);">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; padding: 8px 0 4px;">
                    <button type="button" onclick="shareProfileTo('facebook')" class="share-icon-btn" style="color:#1877F2;border-color:#1877F2;" title="Facebook">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M22 12.06C22 6.51 17.52 2 12 2S2 6.51 2 12.06C2 17.06 5.66 21.21 10.44 22v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22C18.34 21.21 22 17.06 22 12.06z"/></svg>
                    </button>
                    <button type="button" onclick="shareProfileTo('whatsapp')" class="share-icon-btn" style="color:#25D366;border-color:#25D366;" title="WhatsApp">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12.03 2C6.51 2 2.02 6.48 2.02 12c0 1.99.58 3.85 1.58 5.41L2 22l4.72-1.55A9.96 9.96 0 0012.03 22C17.55 22 22 17.52 22 12S17.55 2 12.03 2zm5.8 14.19c-.25.7-1.44 1.34-1.99 1.4-.51.06-1.15.08-1.86-.12-.43-.12-.98-.31-1.69-.61-2.97-1.28-4.91-4.27-5.06-4.47-.15-.2-1.21-1.61-1.21-3.07s.76-2.17 1.03-2.47c.27-.29.6-.36.79-.36.2 0 .4 0 .57.01.18.01.43-.07.67.51.25.6.85 2.07.92 2.22.07.15.12.33.02.53-.1.2-.15.32-.3.49-.15.17-.31.38-.44.51-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.36 1.46.29.15.46.12.63-.07.17-.2.72-.84.92-1.13.2-.29.4-.24.66-.15.27.1 1.71.81 2 .96.29.15.49.22.56.34.07.13.07.72-.18 1.4z"/></svg>
                    </button>
                    <button type="button" onclick="shareProfileTo('x')" class="share-icon-btn" style="color:var(--text-primary);border-color:var(--border-color);" title="X (Twitter)">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18.9 2H22l-7.19 8.21L23.3 22h-6.62l-5.18-6.78L5.5 22H2.4l7.7-8.8L1 2h6.78l4.68 6.2L18.9 2zm-1.16 18h1.83L7.34 3.9H5.38L17.74 20z"/></svg>
                    </button>
                    <button type="button" onclick="shareProfileTo('linkedin')" class="share-icon-btn" style="color:#0A66C2;border-color:#0A66C2;" title="LinkedIn">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.38-1.85 3.62 0 4.28 2.38 4.28 5.47v6.27zM5.34 7.43a2.06 2.06 0 110-4.12 2.06 2.06 0 010 4.12zM7.12 20.45H3.56V9h3.56v11.45z"/></svg>
                    </button>
                    <button type="button" onclick="copyShareLink()" class="share-icon-btn" style="color:var(--primary-color);border-color:var(--primary-color);" title="Kopírovať odkaz">
                        <span class="material-symbols-outlined" style="font-size:22px;">link</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="booking-modal-overlay" id="bookingModalOverlay">
        <div class="booking-modal">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">calendar_add_on</span>
                    <h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800;">Rezervácia termínu</h3>
                </div>
                <button type="button" onclick="closeBookingModal()" style="background: none; border: none; cursor: pointer; color: var(--text-secondary);">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Zhrnutie vybraných služieb -->
                <div class="selected-services-box">
                    <div class="selected-services-head">
                        <span>Vybrané služby (<span id="modalSelectedCount">0</span>)</span>
                        <span id="modalTotalDuration">0 min.</span>
                    </div>
                    <div class="selected-items-list" id="modalSelectedList">
                        <!-- Generované cez JS -->
                    </div>
                    <div class="selected-total-row">
                        <span>Spolu k úhrade:</span>
                        <span style="color: var(--primary-color); font-size: 1.25rem;" id="modalTotalPrice">0.00 €</span>
                    </div>
                </div>

                <div class="booking-steps-indicator" id="bookingStepsIndicator">
                    <div class="step-dot active" data-step-dot="1"><span class="step-num">1</span><span class="step-label">Služba</span></div>
                    <div class="step-line"></div>
                    <div class="step-dot" data-step-dot="2"><span class="step-num">2</span><span class="step-label">Pracovník</span></div>
                    <div class="step-line"></div>
                    <div class="step-dot" data-step-dot="3"><span class="step-num">3</span><span class="step-label">Dátum</span></div>
                    <div class="step-line"></div>
                    <div class="step-dot" data-step-dot="4"><span class="step-num">4</span><span class="step-label">Čas</span></div>
                    <div class="step-line"></div>
                    <div class="step-dot" data-step-dot="5"><span class="step-num">5</span><span class="step-label">Kontrola</span></div>
                    <div class="step-line"></div>
                    <div class="step-dot" data-step-dot="6"><span class="step-num">6</span><span class="step-label">Potvrdenie</span></div>
                </div>
                <!-- 0. Krok: VÝBER SLUŽBY -->
                <div class="booking-step" data-step="1">
                    <label style="display: block; font-weight: 700; font-size: 0.90rem; margin-bottom: 4px; color: var(--text-primary);">
                        Vyberte službu:
                    </label>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0 0 10px 0;">Môžete vybrať aj viacero služieb naraz.</p>
                    <div id="wizardServicesList" style="display:flex; flex-direction:column; gap:8px; max-height:340px; overflow-y:auto; margin-bottom: 4px;">
                        <!-- Generovane cez JS z #servicesList, aby sa zoznam sluzieb neposielal 2x v HTML -->
                    </div>
                    <div class="step-nav">
                        <span></span>
                        <button type="button" class="btn-step-next" onclick="goToBookingStepFromServices()">Pokračovať →</button>
                    </div>
                </div>
                <!-- 1. Krok: VÝBER PRACOVNÍKA -->
                <div class="booking-step" data-step="2" style="margin-bottom: 20px; display:none;">
                    <label style="display: block; font-weight: 700; font-size: 0.90rem; margin-bottom: 8px; color: var(--text-primary);">
                        Vyberte pracovníka:
                    </label>
                    <div id="employeeSelectionContainer" style="display:flex;flex-direction:column;gap:10px;">
                        <span style="font-size:0.85rem;color:var(--text-secondary);">Načítavam zamestnancov...</span>
                    </div>
                    <div class="step-nav">
                        <button type="button" class="btn-step-back" onclick="goToBookingStep(1)">← Späť</button>
                        <button type="button" class="btn-step-next" onclick="goToBookingStep(3)">Pokračovať →</button>
                    </div>
                </div>

                <!-- 2. Krok: NÁŠ KALENDÁR (FLATPICKR) -->
                <div class="booking-step" data-step="3" style="margin-bottom: 20px; display:none;">
                    <label style="display: block; font-weight: 700; font-size: 0.90rem; margin-bottom: 8px; color: var(--text-primary);">
                        Vyberte dátum návštevy:
                    </label>
                    <div class="calendar-input-wrap" id="calendarInputWrap">
                        <span class="material-symbols-outlined cal-icon">calendar_month</span>
                        <input type="text" id="bookingFlatpickr" class="calendar-custom-input" placeholder="Vyberte voľný termín">
                    </div>
                    <div class="step-nav">
                        <button type="button" class="btn-step-back" onclick="goToBookingStep(2)">← Späť</button>
                        <button type="button" class="btn-step-next" onclick="goToBookingStep(4)">Pokračovať →</button>
                    </div>
                </div>

                <!-- 3. Krok: Výber času (dynamicky podľa reálnej dostupnosti) -->
                <div class="booking-step" data-step="4" style="margin-bottom: 20px; display:none;">
                    <label style="display: block; font-weight: 700; font-size: 0.90rem; margin-bottom: 8px; color: var(--text-primary);">
                        Vyberte voľný čas:
                    </label>
                    <div id="timeSlotsContainer" class="time-slots-grid">
                        <span style="font-size:0.85rem;color:var(--text-secondary);">Najprv vyberte dátum.</span>
                    </div>
                    <div class="step-nav">
                        <button type="button" class="btn-step-back" onclick="goToBookingStep(3)">← Späť</button>
                        <button type="button" class="btn-step-next" onclick="goToBookingStepFromTime()">Pokračovať →</button>
                    </div>
                </div>

                <!-- 3. Krok: ÚDAJE ZÁKAZNÍKA (LEN PRE PRIHLÁSENÝCH A OVERENÝCH) -->
                <div class="booking-step" data-step="5" style="margin-bottom: 24px; display:none;">
                    <label style="display: block; font-weight: 700; font-size: 0.90rem; margin-bottom: 8px; color: var(--text-primary);">
                        Údaje zákazníka a kontakt:
                    </label>

                    <?php if ($is_user_verified): ?>
                        <!-- Karta overeného zákazníka -->
                        <div class="customer-verified-card">
                            <img src="<?= htmlspecialchars(!empty($current_user['avatar_url']) ? $current_user['avatar_url'] : 'assets/img/cat_hair.png') ?>" alt="<?= htmlspecialchars($current_user['full_name']) ?>" class="customer-avatar-small">
                            <div class="customer-details-col">
                                <div class="customer-name-text"><?= htmlspecialchars($current_user['full_name']) ?></div>
                                <div class="customer-email-row">
                                    <span><?= htmlspecialchars($current_user['email']) ?></span>
                                    <span class="badge-verified" style="font-size: 0.72rem; padding: 2px 8px;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">verified</span> Overený e-mail
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">
                                Telefónne číslo (pre SMS potvrdenie a salón):
                            </label>
                            <input type="tel" id="custPhone" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" placeholder="+421 9XX XXX XXX" style="width: 100%; height: 44px; padding: 0 14px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--input-bg); color: var(--text-primary); font-family: inherit; font-size: 0.92rem; outline: none; font-weight: 600;">
                        </div>
                        <button type="button" class="btn-toggle-more-options" onclick="toggleMoreOptions()">
                            <span id="moreOptionsToggleIcon" class="material-symbols-outlined">expand_more</span>
                            <span>Viac možností (zľava, darčekový poukaz, opakovanie, poznámka)</span>
                        </button>
                        <div id="moreOptionsSection" style="display:none;">

                        <div style="margin-top: 12px;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">
                                Zľavový kód (voliteľné):
                            </label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="custVoucherCode" placeholder="Napr. LETO10" style="flex: 1; height: 40px; padding: 0 12px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--input-bg); color: var(--text-primary); font-family: inherit; font-size: 0.88rem; outline: none; font-weight: 600; text-transform: uppercase;">
                                <button type="button" onclick="applyVoucherCode()" class="btn-primary" style="padding: 0 16px; white-space: nowrap;">Uplatniť</button>
                            </div>
                            <div id="voucherFeedback" style="font-size: 0.8rem; margin-top: 6px; display: none;"></div>
                        </div>

                        <div style="margin-top: 12px;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">
                                Darčekový poukaz (voliteľné):
                            </label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="custGiftVoucherCode" placeholder="Napr. GV-A1B2C3D4" style="flex: 1; height: 40px; padding: 0 12px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--input-bg); color: var(--text-primary); font-family: inherit; font-size: 0.88rem; outline: none; font-weight: 600; text-transform: uppercase;">
                                <button type="button" onclick="applyGiftVoucherCode()" class="btn-primary" style="padding: 0 16px; white-space: nowrap;">Uplatniť</button>
                            </div>
                            <div id="giftVoucherFeedback" style="font-size: 0.8rem; margin-top: 6px; display: none;"></div>
                        </div>

                        <label style="display: flex; align-items: center; gap: 8px; margin-top: 14px; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); cursor: pointer;">
                            <input type="checkbox" id="custRecurringEnabled" onchange="toggleRecurringOptions(this.checked)">
                            <span>🔁 Opakovať túto rezerváciu</span>
                        </label>
                        <div id="recurringOptionsBox" style="display:none; margin-top: 10px; padding: 12px; background: var(--input-bg); border-radius: 10px;">
                            <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">Interval opakovania:</label>
                            <select id="custRecurringInterval" style="width: 100%; height: 38px; border-radius: 8px; border: 1.5px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); margin-bottom: 10px; padding: 0 10px;">
                                <option value="1">Každý týždeň</option>
                                <option value="2" selected>Každé 2 týždne</option>
                                <option value="3">Každé 3 týždne</option>
                                <option value="4">Každý mesiac (4 týždne)</option>
                            </select>
                            <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">Počet návštev (vrátane tejto):</label>
                            <input type="number" id="custRecurringOccurrences" value="4" min="2" max="52" style="width: 100%; height: 38px; border-radius: 8px; border: 1.5px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); padding: 0 10px;">
                        </div>

                        <div style="margin-top: 14px;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">
                                Poznámka pre prevádzku (voliteľné):
                            </label>
                            <textarea id="custNote" rows="2" maxlength="500" placeholder="Napr. alergia, dĺžka vlasov, špeciálna požiadavka..." style="width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--input-bg); color: var(--text-primary); font-family: inherit; font-size: 0.85rem; outline: none; resize: vertical;"></textarea>
                        </div>

                        <label style="display: flex; align-items: flex-start; gap: 8px; margin-top: 14px; font-size: 0.82rem; color: var(--text-secondary); cursor: pointer;">
                            <input type="checkbox" id="custMarketingConsent" style="margin-top: 2px;">
                            <span>Súhlasím so zasielaním ponúk a zliav e-mailom (napr. narodeninové zľavy, akcie) — kedykoľvek sa dá odhlásiť.</span>
                        </label>
                        </div>

                        <div class="step-nav">
                            <button type="button" class="btn-step-back" onclick="goToBookingStep(4)">← Späť</button>
                        </div>

                        <button type="button" id="btnSubmitBooking" class="btn-book-primary" style="width: 100%; height: 48px; justify-content: center; font-size: 1.0rem; margin-top: 16px; position: sticky; bottom: 0; box-shadow: 0 -8px 16px 4px var(--card-bg);" onclick="submitVerifiedBooking()">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span id="btnConfirmText">Potvrdiť rezerváciu</span>
                        </button>

                        <div id="bookingSuccessBox" style="display:none; margin-top: 16px; flex-direction: column; gap: 10px; align-items: center;">
                            <a id="addToCalendarLink" href="#" target="_blank" style="display:inline-flex; align-items:center; gap:6px; color: var(--primary-color); font-weight: 700; font-size: 0.88rem; text-decoration: none;">
                                <span class="material-symbols-outlined" style="font-size: 18px;">calendar_add_on</span>
                                Pridať do Google kalendára
                            </a>
                            <a id="addToOutlookLink" href="#" target="_blank" style="display:inline-flex; align-items:center; gap:6px; color: var(--primary-color); font-weight: 700; font-size: 0.88rem; text-decoration: none;">
                                <span class="material-symbols-outlined" style="font-size: 18px;">calendar_add_on</span>
                                Pridať do Outlook kalendára
                            </a>
                            <a id="addToIcsLink" href="#" style="display:inline-flex; align-items:center; gap:6px; color: var(--primary-color); font-weight: 700; font-size: 0.88rem; text-decoration: none;">
                                <span class="material-symbols-outlined" style="font-size: 18px;">calendar_add_on</span>
                                Pridať do Apple / iný kalendár (.ics)
                            </a>
                        </div>

                    <?php elseif ($is_user_logged_in && !$is_user_verified): ?>
                        <!-- Používateľ je prihlásený, ale nemá overený e-mail (6-miestny kód) -->
                        <div class="customer-auth-required-box" style="border-color: #f59e0b; background: rgba(245, 158, 11, 0.08);">
                            <div class="lock-icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.15);">
                                <span class="material-symbols-outlined" style="font-size: 28px;">mark_email_unread</span>
                            </div>
                            <h4>Overenie e-mailu je povinné</h4>
                            <p>Pre dokončenie rezervácie je potrebné zadať 6-miestny overovací kód odoslaný na váš e-mail <strong><?= htmlspecialchars($current_user['email']) ?></strong>.</p>
                            <button type="button" class="btn-book-primary" style="background: #f59e0b;" onclick="openAuthModal('verify')">
                                <span class="material-symbols-outlined">pin</span>
                                <span>Zadať 6-miestny overovací kód</span>
                            </button>
                        </div>

                    <?php else: ?>
                        <!-- Používateľ NIE JE prihlásený -->
                        <div class="customer-auth-required-box">
                            <div class="lock-icon">
                                <span class="material-symbols-outlined" style="font-size: 28px;">lock_person</span>
                            </div>
                            <h4>Pre rezerváciu sa musíte prihlásiť</h4>
                            <p>Aby mal salón k dispozícii vaše overené kontaktné údaje (e-mail overený 6-miestnym kódom, meno a telefón), prihláste sa alebo sa bezplatne zaregistrujte.</p>
                            <button type="button" class="btn-book-primary" style="width: auto; margin: 0 auto;" onclick="openAuthModal()">
                                <span class="material-symbols-outlined">login</span>
                                <span>Prihlásiť sa / Zaregistrovať ➔</span>
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notice -->
    <div id="toastNotice">Odkaz na profil bol skopírovaný!</div>

    <script>
        // Stav vybraných služieb (Multi-Service state)
        let selectedServices = [];
        let bookingFpInstance = null;
        let selectedTimeSlot = null;
        let selectedEmployeeId = 0; // 0 = Ktokoľvek
        let employeeServicePrices = {}; // { employeeId: { serviceId: {price, duration_minutes} } }
        // Cena/trvanie konkretneho zamestnanca pre danu sluzbu (bez ohladu na aktualne vybraneho)
        function getEmployeePrice(empId, svc) {
            const override = employeeServicePrices[empId] && employeeServicePrices[empId][svc.id];
            return (override && override.price !== null && override.price !== undefined) ? override.price : svc.price;
        }
        function getEmployeeDuration(empId, svc) {
            const override = employeeServicePrices[empId] && employeeServicePrices[empId][svc.id];
            return (override && override.duration_minutes !== null && override.duration_minutes !== undefined) ? override.duration_minutes : svc.duration;
        }
        // Pri "Ktokolvek volny" (id 0) berieme najvyssiu (najbezpecnejsiu) cenu/trvanie spomedzi vsetkych zamestnancov,
        // aby sme zakaznikovi nikdy neukazali nizsiu cenu, nez akou by mohol byt nakoniec obsluzeny
        function getMaxPriceAcrossEmployees(svc) {
            const empIds = Object.keys(employeeServicePrices);
            if (empIds.length === 0) return svc.price;
            return Math.max(...empIds.map(id => getEmployeePrice(id, svc)));
        }
        function getMaxDurationAcrossEmployees(svc) {
            const empIds = Object.keys(employeeServicePrices);
            if (empIds.length === 0) return svc.duration;
            return Math.max(...empIds.map(id => getEmployeeDuration(id, svc)));
        }
        function getEffectivePrice(svc) {
            if (!selectedEmployeeId) return getMaxPriceAcrossEmployees(svc);
            return getEmployeePrice(selectedEmployeeId, svc);
        }
        function getEffectiveDuration(svc) {
            if (!selectedEmployeeId) return getMaxDurationAcrossEmployees(svc);
            return getEmployeeDuration(selectedEmployeeId, svc);
        }
        let appliedVoucher = null; // { code, discount_type, discount_value }
        let appliedGiftVoucher = null; // { code, remaining_value }
        const establishmentId = <?= (int)$establishment['id'] ?>;
        const establishmentName = <?= json_encode($establishment['name'] ?? '') ?>;
        const isUserVerified = <?= $is_user_verified ? 'true' : 'false' ?>;
        const isUserLoggedIn = <?= $is_user_logged_in ? 'true' : 'false' ?>;

        // Inicializácia nášho Flatpickr kalendára
        document.addEventListener('DOMContentLoaded', () => {
            loadPackagesSection();
            const fpInput = document.getElementById('bookingFlatpickr');
            if (fpInput && typeof flatpickr !== 'undefined') {
                bookingFpInstance = flatpickr(fpInput, {
                    locale: "sk",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d. m. Y",
                    altInputClass: "calendar-custom-input",
                    minDate: "today",
                    defaultDate: "today",
                    monthSelectorType: "static",
                    showMonths: 1,
                    onReady: function(selectedDates, dateStr, instance) {
                        if (instance.altInput) {
                            instance.altInput.placeholder = "Vyberte voľný termín";
                        }
                    },
                    onChange: function() {
                        loadAvailableSlots();
                    }
                });
            }
        });

        // Prepínanie výberu služby (Multi-select)
        function toggleService(card) {
            const id = parseInt(card.dataset.id);
            const name = card.dataset.name;
            const price = parseFloat(card.dataset.price);
            const duration = parseInt(card.dataset.duration);

            const index = selectedServices.findIndex(s => s.id === id);

            if (index > -1) {
                // Odobrať službu
                selectedServices.splice(index, 1);
                card.classList.remove('selected');
                card.querySelector('.btn-text').textContent = '+ Pridať';
            } else {
                // Pridať službu
                selectedServices.push({ id, name, price, duration });
                card.classList.add('selected');
                card.querySelector('.btn-text').textContent = '✓ Pridané';
            }

            updateStickyBar();
        }

        // Aktualizácia spodnej plávajúcej lišty
        function updateStickyBar() {
            const bar = document.getElementById('stickyBookingBar');
            if (!bar) return;

            if (selectedServices.length === 0) {
                bar.style.display = 'none';
                return;
            }

            bar.style.display = 'flex';

            const count = selectedServices.length;
            const totalPrice = selectedServices.reduce((acc, s) => acc + getEffectivePrice(s), 0);
            const totalDuration = selectedServices.reduce((acc, s) => acc + getEffectiveDuration(s), 0);

            // Text počtu služieb
            let countLabel = count + ' vybraná služba';
            if (count >= 2 && count <= 4) countLabel = count + ' vybrané služby';
            else if (count >= 5) countLabel = count + ' vybraných služieb';

            document.getElementById('stickyCountText').textContent = countLabel;
            document.getElementById('stickyTotalPrice').textContent = totalPrice.toFixed(2) + ' €';
            document.getElementById('stickyDurationText').textContent = '⏱ Celkový čas: ' + totalDuration + ' min.';
            
            // Názvy vybraných služieb v riadku
            const namesSummary = selectedServices.map(s => s.name).join(' + ');
            document.getElementById('stickyNamesText').textContent = namesSummary.length > 50 ? namesSummary.substring(0, 48) + '...' : namesSummary;
        }

        // Otvorenie modalu pre viacnásobnú rezerváciu
        function refreshModalSelectedList() {
            const modalList = document.getElementById('modalSelectedList');
            if (!modalList) return;
            modalList.innerHTML = '';

            let totalDuration = 0;

            selectedServices.forEach(s => {
                const price = getEffectivePrice(s);
                const duration = getEffectiveDuration(s);
                totalDuration += duration;

                const row = document.createElement('div');
                row.className = 'selected-item-row';
                row.innerHTML = `
                    <div>
                        <strong>${s.name}</strong>
                        <span class="svc-dur">(${duration} min.)</span>
                    </div>
                    <strong style="color: var(--primary-color);">${price.toFixed(2)} €</strong>
                `;
                modalList.appendChild(row);
            });

            document.getElementById('modalSelectedCount').textContent = selectedServices.length;
            document.getElementById('modalTotalDuration').textContent = '⏱ ' + totalDuration + ' min.';
            updatePriceDisplay();
        }
        function openMultiBookingModal() {
            selectedServices = [];
            buildWizardServicesList();
            document.querySelectorAll('#wizardServicesList .wizard-service-card').forEach(c => c.classList.remove('selected'));
            refreshModalSelectedList();

            document.getElementById('bookingModalOverlay').style.display = 'flex';
            document.documentElement.classList.add('booking-modal-open');
            document.body.classList.add('booking-modal-open');
            goToBookingStep(1);
            document.querySelectorAll('.booking-step').forEach(el => el.style.display = (el.dataset.step === '1') ? 'block' : 'none');
            const moreOptEl = document.getElementById('moreOptionsSection'); if (moreOptEl) moreOptEl.style.display = 'none';
            const moreOptIcon = document.getElementById('moreOptionsToggleIcon'); if (moreOptIcon) moreOptIcon.textContent = 'expand_more';
            const stepsIndicatorShow = document.getElementById('bookingStepsIndicator'); if (stepsIndicatorShow) stepsIndicatorShow.style.display = 'flex';
            const submitBtnShow = document.getElementById('btnSubmitBooking'); if (submitBtnShow) submitBtnShow.style.display = 'flex';
            const successBoxReset = document.getElementById('bookingSuccessBox');
            if (successBoxReset) { successBoxReset.style.display = 'none'; }
            const recurringElReset = document.getElementById('custRecurringEnabled');
            if (recurringElReset) { recurringElReset.checked = false; toggleRecurringOptions(false); }
            appliedVoucher = null;
            const voucherInputReset = document.getElementById('custVoucherCode');
            const voucherFeedbackReset = document.getElementById('voucherFeedback');
            if (voucherInputReset) { voucherInputReset.value = ''; }
            if (voucherFeedbackReset) { voucherFeedbackReset.style.display = 'none'; }
            appliedGiftVoucher = null;
            const giftVoucherInputReset = document.getElementById('custGiftVoucherCode');
            const giftVoucherFeedbackReset = document.getElementById('giftVoucherFeedback');
            if (giftVoucherInputReset) { giftVoucherInputReset.value = ''; }
            if (giftVoucherFeedbackReset) { giftVoucherFeedbackReset.style.display = 'none'; }
            const custNoteReset = document.getElementById('custNote');
            if (custNoteReset) { custNoteReset.value = ''; }
        }

        function buildWizardServicesList() {
            const target = document.getElementById('wizardServicesList');
            if (!target || target.dataset.built) return;
            target.dataset.built = '1';
            document.querySelectorAll('#servicesList .service-row-card').forEach(src => {
                const card = document.createElement('div');
                card.className = 'wizard-service-card';
                card.dataset.id = src.dataset.id;
                card.dataset.name = src.dataset.name;
                card.dataset.price = src.dataset.price;
                card.dataset.duration = src.dataset.duration;
                card.onclick = () => toggleWizardService(card);
                const price = parseFloat(src.dataset.price);
                card.innerHTML = `
                    <div class="wizard-service-checkbox"><span class="material-symbols-outlined">check</span></div>
                    <div style="flex:1; min-width:0;">
                        <strong style="display:block; font-size:0.92rem;">${src.dataset.name}</strong>
                        <span style="font-size:0.78rem; color:var(--text-secondary);">${src.dataset.duration} min. &middot; ${price.toFixed(2)} €</span>
                    </div>
                `;
                target.appendChild(card);
            });
        }
        function toggleWizardService(card) {
            const id = parseInt(card.dataset.id);
            const name = card.dataset.name;
            const price = parseFloat(card.dataset.price);
            const duration = parseInt(card.dataset.duration);
            const index = selectedServices.findIndex(s => s.id === id);
            if (index > -1) {
                selectedServices.splice(index, 1);
                card.classList.remove('selected');
            } else {
                selectedServices.push({ id, name, price, duration });
                card.classList.add('selected');
            }
            refreshModalSelectedList();
        }

        function goToBookingStepFromServices() {
            if (selectedServices.length === 0) {
                showToast('⚠️ Prosím vyberte aspoň jednu službu.');
                return;
            }
            loadEmployeesForSelection();
            goToBookingStep(2);
        }
        function closeBookingModal() {
            document.getElementById('bookingModalOverlay').style.display = 'none';
            document.documentElement.classList.remove('booking-modal-open');
            document.body.classList.remove('booking-modal-open');
        }

        function selectSlot(btn) {
            document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedTimeSlot = btn.textContent.trim();
        }

        let currentBookingStep = 1;
        function goToBookingStep(n) {
            if (n === 4 && (!bookingFpInstance || !bookingFpInstance.input.value)) {
                showToast('⚠️ Prosím vyberte dátum návštevy.');
                return;
            }
            currentBookingStep = n;
            document.querySelectorAll('.booking-step').forEach(el => {
                el.style.display = (parseInt(el.dataset.step, 10) === n) ? 'block' : 'none';
            });
            document.querySelectorAll('.step-dot').forEach(dot => {
                const s = parseInt(dot.dataset.stepDot, 10);
                dot.classList.toggle('active', s === n);
                dot.classList.toggle('done', s < n);
            });
        }
        function goToBookingStepFromTime() {
            if (!selectedTimeSlot) {
                showToast('⚠️ Prosím vyberte voľný čas.');
                return;
            }
            goToBookingStep(5);
        }
        function toggleMoreOptions() {
            const el = document.getElementById('moreOptionsSection');
            const icon = document.getElementById('moreOptionsToggleIcon');
            if (!el) return;
            const isOpen = el.style.display !== 'none';
            el.style.display = isOpen ? 'none' : 'block';
            if (icon) icon.textContent = isOpen ? 'expand_more' : 'expand_less';
        }
        // Načíta zamestnancov, ktorí vedia vykonať všetky vybrané služby, + možnosť "Ktokoľvek"
        async function loadEmployeesForSelection() {
            const container = document.getElementById('employeeSelectionContainer');
            container.innerHTML = '<span style="font-size:0.85rem;color:var(--text-secondary);">Načítavam zamestnancov...</span>';
            selectedEmployeeId = 0;
            employeeServicePrices = {};

            try {
                const fd = new FormData();
                fd.append('action', 'get_employees_for_services');
                fd.append('establishment_id', establishmentId);
                fd.append('service_ids', JSON.stringify(selectedServices.map(s => s.id)));
                const res = await fetch('api/availability.php', { method: 'POST', body: fd });
                const data = await res.json();

                container.innerHTML = '';

                (data.employees || []).forEach(emp => { employeeServicePrices[emp.id] = emp.service_prices || {}; });

                const anyTotal = selectedServices.reduce((sum, svc) => sum + getMaxPriceAcrossEmployees(svc), 0);
                const anyCard = document.createElement('div');
                anyCard.className = 'employee-select-card selected';
                anyCard.innerHTML = `
                    <span class="employee-radio"></span>
                    <span class="employee-avatar employee-avatar-generic"><span class="material-symbols-outlined">groups</span></span>
                    <span class="employee-info">
                        <strong>Ktokoľvek voľný</strong>
                        <span class="employee-title">Najbližší voľný pracovník</span>
                    </span>
                    <span class="employee-price">${anyTotal.toFixed(2)} €</span>
                `;
                anyCard.onclick = () => selectEmployee(0, anyCard);
                container.appendChild(anyCard);

                (data.employees || []).forEach(emp => {
                    const empTotal = selectedServices.reduce((sum, svc) => sum + getEmployeePrice(emp.id, svc), 0);
                    const card = document.createElement('div');
                    card.className = 'employee-select-card';
                    const avatarHtml = emp.avatar_url
                        ? `<span class="employee-avatar" style="background-image:url('${emp.avatar_url}');"></span>`
                        : `<span class="employee-avatar employee-avatar-generic"><span class="material-symbols-outlined">person</span></span>`;
                    card.innerHTML = `
                        <span class="employee-radio"></span>
                        ${avatarHtml}
                        <span class="employee-info">
                            <strong>${emp.name}</strong>
                            ${emp.title ? '<span class="employee-title">' + emp.title + '</span>' : ''}
                        </span>
                        <span class="employee-price">${empTotal.toFixed(2)} €</span>
                    `;
                    card.onclick = () => selectEmployee(emp.id, card);
                    container.appendChild(card);
                });
            } catch (err) {
                container.innerHTML = '<span style="font-size:0.85rem;color:var(--text-secondary);">Ktokoľvek voľný</span>';
            }

            loadAvailableSlots();
        }

        function selectEmployee(id, btn) {
            selectedEmployeeId = id;
            document.querySelectorAll('#employeeSelectionContainer .employee-select-card').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            updateStickyBar();
            updatePriceDisplay();
            refreshModalSelectedList();
            loadAvailableSlots();
        }

        async function loadPackagesSection() {
            try {
                const fd = new FormData();
                fd.append('action', 'list_available_packages');
                fd.append('establishment_id', establishmentId);
                const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.success || !data.packages || data.packages.length === 0) return;

                const section = document.getElementById('packagesSection');
                const list = document.getElementById('packagesList');
                list.innerHTML = data.packages.map(p => `
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px;border:1px solid var(--border-color);border-radius:12px;flex-wrap:wrap;">
                        <div>
                            <strong style="font-size:0.95rem;">${p.name}</strong>
                            <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(176,128,66,0.12);color:var(--primary-color);margin-left:6px;">${p.type === 'membership' ? 'Členstvo' : 'Balíček'}</span>
                            <div style="font-size:0.82rem;color:var(--text-secondary);margin-top:4px;">${p.visit_count}× návšteva · platnosť ${p.validity_days} dní${p.description ? ' · ' + p.description : ''}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <strong style="color:var(--primary-color);font-size:1.05rem;">${parseFloat(p.price).toFixed(2)} €</strong>
                            <button type="button" class="btn-book-primary" style="padding:8px 16px;" onclick="purchasePackage(${p.id}, '${p.name.replace(/'/g, "\\'")}')">Kúpiť</button>
                        </div>
                    </div>`).join('');
                section.style.display = 'block';
            } catch (err) { /* žiadne balíčky sa nenačítali, sekcia zostane skrytá */ }
        }

        async function purchasePackage(packageId, packageName) {
            if (!isUserVerified) { openAuthModal(); return; }
            try {
                const fd = new FormData();
                fd.append('action', 'purchase_package');
                fd.append('package_id', packageId);
                const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.success) { showToast('⚠️ ' + (data.message || 'Chyba pri vytváraní objednávky.')); return; }

                document.getElementById('packagePurchaseTitle').textContent = 'Kúpa: ' + packageName;
                document.getElementById('packagePurchaseInstructions').textContent = data.message;
                document.getElementById('packagePurchaseCodeBox').style.display = 'none';
                const qrImg = document.getElementById('packagePurchaseQr');
                if (data.qr_code_png_base64) {
                    qrImg.src = 'data:image/png;base64,' + data.qr_code_png_base64;
                    qrImg.style.display = 'inline-block';
                } else {
                    qrImg.style.display = 'none';
                }
                document.getElementById('packagePurchaseModalOverlay').style.display = 'flex';
            } catch (err) {
                showToast('⚠️ Chyba komunikácie so serverom.');
            }
        }

        let selectedGiftAmount = null;
        function selectGiftAmount(amount, btn) {
            document.querySelectorAll('.gv-amount-btn').forEach(b => b.classList.remove('btn-primary'));
            document.querySelectorAll('.gv-amount-btn').forEach(b => b.classList.add('btn-secondary'));
            if (btn) {
                selectedGiftAmount = amount;
                document.getElementById('gv-custom-amount').value = '';
                btn.classList.remove('btn-secondary'); btn.classList.add('btn-primary');
            } else {
                selectedGiftAmount = null; // vlastná suma zadaná ručne, prioritu má input pri odoslaní
            }
        }

        async function purchaseGiftVoucher() {
            if (!isUserVerified) { openAuthModal(); return; }
            const customAmount = parseFloat(document.getElementById('gv-custom-amount').value);
            const amount = customAmount > 0 ? customAmount : selectedGiftAmount;
            const buyerName = document.getElementById('gv-buyer-name').value.trim();
            const buyerEmail = document.getElementById('gv-buyer-email').value.trim();
            if (!amount || amount < 5) { showToast('⚠️ Vyberte alebo zadajte sumu poukazu (min. 5 €).'); return; }
            if (!buyerName || !buyerEmail) { showToast('⚠️ Vyplňte vaše meno a e-mail.'); return; }
            try {
                const fd = new FormData();
                fd.append('establishment_id', establishmentId);
                fd.append('amount', amount);
                fd.append('buyer_name', buyerName);
                fd.append('buyer_email', buyerEmail);
                fd.append('recipient_name', document.getElementById('gv-recipient-name').value.trim());
                fd.append('message', document.getElementById('gv-message').value.trim());
                fd.append('action', 'purchase_gift_voucher');
                const res = await fetch('api/gift_vouchers.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.success) { showToast('⚠️ ' + (data.message || 'Chyba pri vytváraní objednávky.')); return; }

                document.getElementById('packagePurchaseTitle').textContent = 'Darčekový poukaz ' + amount + ' €';
                document.getElementById('packagePurchaseInstructions').textContent = data.message;
                document.getElementById('packagePurchaseCode').textContent = data.code;
                document.getElementById('packagePurchaseCodeBox').style.display = 'inline-block';
                const qrImg = document.getElementById('packagePurchaseQr');
                if (data.qr_code_png_base64) {
                    qrImg.src = 'data:image/png;base64,' + data.qr_code_png_base64;
                    qrImg.style.display = 'inline-block';
                } else {
                    qrImg.style.display = 'none';
                }
                document.getElementById('packagePurchaseModalOverlay').style.display = 'flex';
            } catch (err) {
                showToast('⚠️ Chyba komunikácie so serverom.');
            }
        }

        function toggleRecurringOptions(checked) {
            const box = document.getElementById('recurringOptionsBox');
            if (box) { box.style.display = checked ? 'block' : 'none'; }
        }

        function calculateDiscountedTotal() {
            const rawTotal = selectedServices.reduce((acc, s) => acc + getEffectivePrice(s), 0);
            let discounted = rawTotal;
            if (appliedVoucher) {
                if (appliedVoucher.discount_type === 'percent') {
                    discounted = discounted - (discounted * (parseFloat(appliedVoucher.discount_value) / 100));
                } else {
                    discounted = discounted - parseFloat(appliedVoucher.discount_value);
                }
                discounted = Math.max(0, discounted);
            }
            if (appliedGiftVoucher) {
                discounted = Math.max(0, discounted - Math.min(appliedGiftVoucher.remaining_value, discounted));
            }
            return Math.max(0, discounted);
        }

        function updatePriceDisplay() {
            const rawTotal = selectedServices.reduce((acc, s) => acc + getEffectivePrice(s), 0);
            const finalTotal = calculateDiscountedTotal();
            const priceEl = document.getElementById('modalTotalPrice');
            if (priceEl) {
                priceEl.innerHTML = (appliedVoucher || appliedGiftVoucher)
                    ? `<span style="text-decoration:line-through;color:var(--text-secondary);font-size:0.85em;">${rawTotal.toFixed(2)} €</span> ${finalTotal.toFixed(2)} €`
                    : finalTotal.toFixed(2) + ' €';
            }
            const btnConfirm = document.getElementById('btnConfirmText');
            if (btnConfirm) { btnConfirm.textContent = 'Potvrdiť rezerváciu (' + finalTotal.toFixed(2) + ' €)'; }
        }

        async function applyGiftVoucherCode() {
            const codeInput = document.getElementById('custGiftVoucherCode');
            const feedback = document.getElementById('giftVoucherFeedback');
            const code = codeInput.value.trim();
            if (!code) return;

            try {
                const fd = new FormData();
                fd.append('action', 'check_gift_voucher');
                fd.append('code', code);
                fd.append('establishment_id', establishmentId);
                const res = await fetch('api/gift_vouchers.php', { method: 'POST', body: fd });
                const data = await res.json();
                feedback.style.display = 'block';
                if (data.success) {
                    appliedGiftVoucher = { code: code.toUpperCase(), remaining_value: parseFloat(data.remaining_value) };
                    feedback.style.color = '#10b981';
                    feedback.textContent = '✅ Poukaz uplatnený, zostatok ' + appliedGiftVoucher.remaining_value.toFixed(2) + ' €.';
                } else {
                    appliedGiftVoucher = null;
                    feedback.style.color = '#ef4444';
                    feedback.textContent = '⚠️ ' + (data.message || 'Neplatný kód.');
                }
                updatePriceDisplay();
            } catch (err) {
                showToast('⚠️ Chyba komunikácie so serverom.');
            }
        }

        async function applyVoucherCode() {
            const codeInput = document.getElementById('custVoucherCode');
            const feedback = document.getElementById('voucherFeedback');
            const code = codeInput.value.trim();
            if (!code) return;
            if (selectedServices.length === 0) { showToast('⚠️ Najprv vyberte službu.'); return; }

            try {
                const fd = new FormData();
                fd.append('action', 'validate');
                fd.append('code', code);
                fd.append('business_id', establishmentId);
                const res = await fetch('api/vouchers.php', { method: 'POST', body: fd });
                const data = await res.json();
                feedback.style.display = 'block';
                if (data.success) {
                    appliedVoucher = data.voucher;
                    const label = data.voucher.discount_type === 'percent' ? (data.voucher.discount_value + '%') : (parseFloat(data.voucher.discount_value).toFixed(2) + ' €');
                    feedback.style.color = '#10b981';
                    feedback.textContent = '✅ Zľava ' + label + ' bola uplatnená.';
                } else {
                    appliedVoucher = null;
                    feedback.style.color = '#ef4444';
                    feedback.textContent = '⚠️ ' + (data.error || 'Neplatný kód.');
                }
                updatePriceDisplay();
            } catch (err) {
                showToast('⚠️ Chyba komunikácie so serverom.');
            }
        }

        // Načíta reálne voľné časy podľa dátumu, vybraných služieb a zamestnanca
        async function loadAvailableSlots() {
            const container = document.getElementById('timeSlotsContainer');
            const bookingDate = bookingFpInstance ? bookingFpInstance.input.value : '';
            selectedTimeSlot = null;

            if (!bookingDate || selectedServices.length === 0) {
                container.innerHTML = '<span style="font-size:0.85rem;color:var(--text-secondary);">Najprv vyberte dátum.</span>';
                return;
            }

            container.innerHTML = '<span style="font-size:0.85rem;color:var(--text-secondary);">Hľadám voľné termíny...</span>';

            try {
                const fd = new FormData();
                fd.append('action', 'get_available_slots');
                fd.append('establishment_id', establishmentId);
                fd.append('service_ids', JSON.stringify(selectedServices.map(s => s.id)));
                fd.append('booking_date', bookingDate);
                fd.append('employee_id', selectedEmployeeId);
                const res = await fetch('api/availability.php', { method: 'POST', body: fd });
                const data = await res.json();

                container.innerHTML = '';
                if (!data.success || !data.slots || data.slots.length === 0) {
                    container.innerHTML = `
                        <div style="width:100%;">
                            <span style="font-size:0.85rem;color:var(--text-secondary);display:block;margin-bottom:8px;">Na tento deň už nie sú žiadne voľné termíny.</span>
                            <button type="button" class="btn-book-primary" style="width:auto; padding: 8px 16px; font-size:0.85rem;" onclick="joinWaitlist('${bookingDate}')">
                                <span class="material-symbols-outlined" style="font-size:16px;">notifications_active</span>
                                Pridať sa na čakaciu listinu
                            </button>
                        </div>`;
                    return;
                }

                data.slots.forEach(time => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'time-slot-btn';
                    btn.textContent = time;
                    btn.onclick = () => selectSlot(btn);
                    container.appendChild(btn);
                });
            } catch (err) {
                container.innerHTML = '<span style="font-size:0.85rem;color:var(--text-secondary);">Chyba pri načítaní voľných termínov.</span>';
            }
        }

        async function joinWaitlist(preferredDate) {
            if (!isUserVerified) { openAuthModal(); return; }
            try {
                const fd = new FormData();
                fd.append('action', 'join_waitlist');
                fd.append('establishment_id', establishmentId);
                fd.append('service_ids', JSON.stringify(selectedServices.map(s => s.id)));
                fd.append('preferred_date', preferredDate);
                const res = await fetch('api/waitlist.php', { method: 'POST', body: fd });
                const data = await res.json();
                showToast((data.success ? '✅ ' : '⚠️ ') + data.message);
            } catch (err) {
                showToast('⚠️ Chyba komunikácie so serverom.');
            }
        }

        // Odoslanie overenej rezervácie na API
        async function submitVerifiedBooking() {
            if (!isUserVerified) {
                openAuthModal();
                return;
            }

            if (!selectedTimeSlot) {
                showToast('⚠️ Prosím vyberte voľný čas.');
                return;
            }

            const phoneInput = document.getElementById('custPhone');
            const phone = phoneInput ? phoneInput.value.trim() : '';
            if (!phone) {
                showToast('⚠️ Prosím zadajte telefónne číslo pre SMS notifikáciu.');
                if (phoneInput) phoneInput.focus();
                return;
            }

            const bookingDate = bookingFpInstance ? bookingFpInstance.input.value : new Date().toISOString().slice(0, 10);
            const serviceIds = selectedServices.map(s => s.id);

            const btnSubmit = document.getElementById('btnSubmitBooking');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = '0.7';
                btnSubmit.innerHTML = `<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Spracovávam rezerváciu...`;
            }

            try {
                const fd = new FormData();
                fd.append('establishment_id', establishmentId);
                fd.append('service_ids', JSON.stringify(serviceIds));
                fd.append('booking_date', bookingDate);
                fd.append('start_time', selectedTimeSlot + ':00');
                fd.append('phone', phone);
                fd.append('employee_id', selectedEmployeeId);
                const marketingConsentEl = document.getElementById('custMarketingConsent');
                fd.append('marketing_consent', (marketingConsentEl && marketingConsentEl.checked) ? '1' : '0');

                const recurringEl = document.getElementById('custRecurringEnabled');
                const isRecurring = recurringEl && recurringEl.checked;
                fd.append('recurring_enabled', isRecurring ? '1' : '0');
                if (isRecurring) {
                    fd.append('recurring_interval_weeks', document.getElementById('custRecurringInterval').value);
                    fd.append('recurring_occurrences', document.getElementById('custRecurringOccurrences').value);
                }
                if (appliedVoucher) { fd.append('voucher_code', appliedVoucher.code); }
                if (appliedGiftVoucher) { fd.append('gift_voucher_code', appliedGiftVoucher.code); }
                const custNoteEl = document.getElementById('custNote');
                if (custNoteEl && custNoteEl.value.trim()) { fd.append('customer_note', custNoteEl.value.trim()); }

                const res = await fetch('api/book_appointment.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    showToast('🎉 ' + data.message);

                    if (data.recurring) {
                        let recMsg = `Vytvorených ${data.recurring.created} z ${document.getElementById('custRecurringOccurrences').value} opakovaných termínov.`;
                        if (data.recurring.skipped && data.recurring.skipped.length > 0) {
                            recMsg += ` Tieto termíny boli obsadené, preskočené: ${data.recurring.skipped.join(', ')}. Môžete si ich dorezervovať samostatne na iný čas.`;
                        }
                        showToast('🔁 ' + recMsg);
                    }

                    // Odkaz na pridanie do Google kalendára — zostáva viditeľný v okne, kým ho zákazník sám nezavrie
                    const gcalStart = bookingDate.replace(/-/g, '') + 'T' + selectedTimeSlot.replace(':', '') + '00';
                    const totalDuration = selectedServices.reduce((acc, s) => acc + getEffectiveDuration(s), 0) || 30;
                    const endDateObj = new Date(bookingDate + 'T' + selectedTimeSlot + ':00');
                    endDateObj.setMinutes(endDateObj.getMinutes() + totalDuration);
                    const gcalEnd = endDateObj.toISOString().slice(0, 19).replace(/[-:]/g, '').replace('T', 'T');
                    const namesSummaryForCal = selectedServices.map(s => s.name).join(' + ');
                    const gcalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                        + '&text=' + encodeURIComponent(namesSummaryForCal + ' - ' + establishmentName)
                        + '&dates=' + gcalStart + '/' + gcalEnd;
                    const addToCalLink = document.getElementById('addToCalendarLink');
                    if (addToCalLink) { addToCalLink.href = gcalUrl; }

                    const outlookUrl = 'https://outlook.office.com/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose&rru=addevent'
                        + '&subject=' + encodeURIComponent(namesSummaryForCal + ' - ' + establishmentName)
                        + '&startdt=' + bookingDate + 'T' + selectedTimeSlot + ':00'
                        + '&enddt=' + endDateObj.toISOString().slice(0, 19);
                    const addToOutlookLink = document.getElementById('addToOutlookLink');
                    if (addToOutlookLink) { addToOutlookLink.href = outlookUrl; }

                    const addToIcsLink = document.getElementById('addToIcsLink');
                    if (addToIcsLink && data.manage_token) { addToIcsLink.href = 'api/download_ics.php?token=' + encodeURIComponent(data.manage_token); }

                    const successBox = document.getElementById('bookingSuccessBox');
                    if (successBox) { successBox.style.display = 'flex'; }
                    document.querySelectorAll('.booking-step').forEach(el => el.style.display = 'none');
                    const stepsIndicatorHide = document.getElementById('bookingStepsIndicator'); if (stepsIndicatorHide) stepsIndicatorHide.style.display = 'none';
                    const submitBtnHide = document.getElementById('btnSubmitBooking'); if (submitBtnHide) submitBtnHide.style.display = 'none';

                    // Vyčistenie košíka
                    selectedServices = [];
                    document.querySelectorAll('.service-row-card').forEach(c => {
                        c.classList.remove('selected');
                        c.querySelector('.btn-text').textContent = '+ Pridať';
                    });
                    updateStickyBar();
                } else {
                    if (data.require_auth) {
                        openAuthModal();
                    } else if (data.require_verification) {
                        openAuthModal('verify');
                    } else {
                        showToast('⚠️ ' + (data.message || 'Chyba pri vytváraní rezervácie.'));
                        loadAvailableSlots(); // termín medzičasom mohol obsadiť niekto iný — načítaj aktuálnu dostupnosť
                    }
                }
            } catch (err) {
                console.error(err);
                showToast('⚠️ Chyba komunikácie so serverom.');
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    btnSubmit.innerHTML = `<span class="material-symbols-outlined">check_circle</span> <span>Potvrdiť rezerváciu</span>`;
                }
            }
        }

        // Zdieľanie profilu
        function shareProfile() {
            document.getElementById('shareModalOverlay').style.display = 'flex';
        }
        function closeShareModal() {
            document.getElementById('shareModalOverlay').style.display = 'none';
        }
        function shareProfileTo(platform) {
            const url = window.location.href;
            const title = <?= json_encode($establishment['name']) ?>;
            let shareUrl = '';
            if (platform === 'facebook') shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            else if (platform === 'whatsapp') shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(title + ' ' + url)}`;
            else if (platform === 'x') shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
            else if (platform === 'linkedin') shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
            if (shareUrl) window.open(shareUrl, '_blank', 'width=600,height=500');
        }
        function copyShareLink() {
            const url = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Odkaz na profil bol skopírovaný do schránky!');
                });
            } else {
                showToast('Odkaz: ' + url);
            }
            closeShareModal();
        }


        async function subscribeNewsletter() {
            const emailEl = document.getElementById('nl-email'), agreeEl = document.getElementById('nl-agree');
            const email = emailEl ? emailEl.value.trim() : '';
            if (!email) { showToast('Zadajte e-mail.'); return; }
            if (!agreeEl || !agreeEl.checked) { showToast('Musíte súhlasiť so spracovaním osobných údajov.'); return; }
            const fd = new FormData();
            fd.append('action', 'subscribe');
            fd.append('establishment_id', '<?= (int)($establishment['id'] ?? 0) ?>');
            fd.append('email', email);
            fd.append('agree_terms', '1');
            try {
                const res = await fetch('api/newsletter.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) { showToast('Skontrolujte si e-mail a potvrďte odber.'); if (emailEl) emailEl.value = ''; if (agreeEl) agreeEl.checked = false; }
                else showToast(data.error || 'Nepodarilo sa prihlásiť na odber.');
            } catch (e) { showToast('Chyba spojenia.'); }
        }

        function filterServicesByCategory(catId, btn) {
            document.querySelectorAll('.svc-cat-filter-btn').forEach(b => b.classList.toggle('active', b === btn));
            document.querySelectorAll('#servicesList .service-row-card').forEach(row => {
                const rowCat = parseInt(row.dataset.categoryId || '0', 10);
                row.style.display = (catId === 0 || rowCat === catId) ? '' : 'none';
            });
        }
        function showToast(msg) {
            const toast = document.getElementById('toastNotice');
            if (toast) {
                toast.textContent = msg;
                toast.style.display = 'block';
                setTimeout(() => { toast.style.display = 'none'; }, 3500);
            }
        }

        window.onclick = function(e) {
            const overlay = document.getElementById('bookingModalOverlay');
            if (e.target === overlay) {
                closeBookingModal();
            }
            const authModal = document.getElementById('auth-modal');
            if (e.target === authModal && typeof closeAuthModal === 'function') {
                closeAuthModal();
            }
        };
    </script>

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
            // Zámerne bez localStorage — táto stránka si stav panelu medzi návštevami nepamätá.
            if (pinned) {
                appLayout.classList.add('sidebar-pinned');
                appSidebar.classList.remove('hover-open');
                if (sidebarToggleIcon) {
                    sidebarToggleIcon.style.transform = 'rotate(0deg)';
                    sidebarToggleIcon.style.color = 'var(--primary-color)';
                }
                if (topbarSidebarIcon) {
                    topbarSidebarIcon.textContent = 'left_panel_close';
                }
            } else {
                appLayout.classList.remove('sidebar-pinned');
                if (sidebarToggleIcon) {
                    sidebarToggleIcon.style.transform = 'rotate(45deg)';
                    sidebarToggleIcon.style.color = 'var(--text-secondary)';
                }
                if (topbarSidebarIcon) {
                    topbarSidebarIcon.textContent = 'left_panel_open';
                }
            }
        }

        // Initialize: vždy zbalené na profil.php (je to prezentácia prevádzky, nie naša navigácia) —
        // nepýtame sa localStorage, nič si tu nepamätáme medzi návštevami.
        document.addEventListener('DOMContentLoaded', () => {
            updateSidebarState(false);
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
        function toggleTopbarMoreMenu() {
            const menu = document.getElementById('topbarMoreMenu');
            if (menu) menu.classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('topbarMoreMenu');
            const toggleBtn = document.getElementById('topbar-more-toggle');
            if (menu && menu.classList.contains('open') && !menu.contains(e.target) && e.target !== toggleBtn && !toggleBtn.contains(e.target)) {
                menu.classList.remove('open');
            }
        });
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

        // Plavajuce tlacidla (hore / rezervovat) + schovavanie topbaru podla scrollu
        (function() {
            const scrollTopBtn = document.getElementById('floatingScrollTopBtn');
            const reserveBtn = document.getElementById('floatingReserveBtn');
            const topbarEl = document.querySelector('.topbar');
            let lastScrollY = window.scrollY;
            if (!scrollTopBtn && !reserveBtn && !topbarEl) return;
            function updateOnScroll() {
                const y = window.scrollY;
                const shouldShow = y > 400;
                if (scrollTopBtn) scrollTopBtn.classList.toggle('show', shouldShow);
                if (reserveBtn) reserveBtn.classList.toggle('show', shouldShow);

                if (topbarEl) {
                    if (y <= 80) {
                        topbarEl.classList.remove('topbar-hidden');
                    } else if (y > lastScrollY) {
                        topbarEl.classList.add('topbar-hidden');
                    } else if (y < lastScrollY) {
                        topbarEl.classList.remove('topbar-hidden');
                    }
                }
                lastScrollY = y;
            }
            window.addEventListener('scroll', updateOnScroll, { passive: true });
            updateOnScroll();
        })();

        // Window resize adjustments — táto stránka je vždy zbalená, žiadny localStorage.
        window.addEventListener('resize', () => {
            appSidebar.classList.remove('mobile-open');
            sidebarBackdrop.classList.remove('show');
            updateSidebarState(false);
        });
    </script>
    <script>
    (function() {
        if (location.search.indexOf('debugoverflow') === -1) return;
        window.addEventListener('load', function() {
            setTimeout(function() {
                const results = [];
                document.querySelectorAll('body *').forEach(function(el) {
                    if (el.id === 'overflowDebugBox' || el.closest('#overflowDebugBox')) return;
                    if (el.closest('#app-sidebar') || el.closest('#floatingReserveBtn') || el.closest('#floatingScrollTopBtn')) return;
                    const r = el.getBoundingClientRect();
                    if (r.width > 0 && (r.right > window.innerWidth + 1 || r.left < -1)) {
                        results.push((el.tagName) + (el.id ? '#' + el.id : '') + (el.className ? '.' + (el.className+'').toString().replace(/ /g,'.').slice(0,40) : '') + ' | right=' + Math.round(r.right) + ' left=' + Math.round(r.left) + ' w=' + Math.round(r.width));
                    }
                });
                const box = document.createElement('div');
                box.id = 'overflowDebugBox';
                box.style.cssText = 'position:fixed;top:0;left:0;right:0;max-height:60vh;overflow:auto;background:#000;color:#0f0;font-size:10px;font-family:monospace;padding:8px;z-index:999999;white-space:pre-wrap;';
                box.textContent = 'innerWidth=' + window.innerWidth + ' scrollWidth=' + document.documentElement.scrollWidth + ' docClientW=' + document.documentElement.clientWidth + ' count=' + results.length + '\n\n' + results.join('\n');
                document.body.appendChild(box);
            }, 500);
        });
    })();
    </script>
    <?php include_once 'includes/auth_modal.php'; ?>

    
</body>
</html>
