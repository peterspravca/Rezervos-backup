<?php
require_once 'config.php';
require_once 'includes/content_translation_helper.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

$public_id = $_GET['id'] ?? '';

if (empty($public_id)) {
    die("Nenájdené (Chýba ID)");
}

$stmt = $conn->prepare("SELECT u.full_name, u.email, u.phone, u.whatsapp, u.avatar_url, u.banner_url,
                               e.id as est_id, e.name as est_name, e.description, e.address, e.city, e.category, e.opening_hours, e.subscription_tier
                        FROM users u
                        LEFT JOIN establishments e ON u.id = e.user_id
                        WHERE u.public_id = ?");
$stmt->bind_param("s", $public_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Stránka sa nenašla.");
}

$profile = $res->fetch_assoc();

if (($profile['subscription_tier'] ?? 'free') === 'free') {
    // Show locked page
    ?>
    <!DOCTYPE html>
    <html lang="sk">
    <head>
        <meta charset="UTF-8">
        <title>Profil nedostupný - <?= BRAND_NAME ?></title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
        <style>
            body { font-family: 'Outfit', sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .lock-card { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; max-width: 400px; }
            .lock-icon { font-size: 50px; margin-bottom: 20px; display: block; }
            h1 { font-size: 24px; color: #1e293b; margin-top: 0; }
            p { color: #64748b; line-height: 1.5; margin-bottom: 25px; }
            .btn { display: inline-block; background: var(--primary-color); color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="lock-card">
            <span class="lock-icon">🔒</span>
            <h1>Profil nie je aktívny</h1>
            <p>Táto prevádzka zatiaľ nemá aktivovanú verejnú vizitku. Skúste to neskôr, alebo sa vráťte na hlavnú stránku.</p>
            <a href="index.php" class="btn">Späť na hlavnú stránku</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$name = !empty($profile['est_name']) ? $profile['est_name'] : $profile['full_name'];
$description = $profile['description'] ?? 'Vitajte na našej stránke.';
if (!empty($profile['est_id'])) {
    if (!empty($profile['est_name'])) {
        $name = ct_get($conn, 'establishment', $profile['est_id'], 'name', $name);
    }
    $description = ct_get($conn, 'establishment', $profile['est_id'], 'description', $description);
}
$address = ($profile['address'] && $profile['city']) ? $profile['address'] . ', ' . $profile['city'] : '';
$categories = $profile['category'] ? array_map('trim', explode(',', $profile['category'])) : [];
$main_cat = $categories[0] ?? '';

// Fallback pre logo (Ikona podľa kategórie)
$default_icon = 'store';
$cat_banner_suffix = 'default';

switch ($main_cat) {
    case 'Vlasy': $default_icon = 'content_cut'; $cat_banner_suffix = 'hair'; break;
    case 'Holičstvo a Barber': $default_icon = 'content_cut'; $cat_banner_suffix = 'barber_shop'; break; // We have barber_shop.png
    case 'Nechty': $default_icon = 'back_hand'; $cat_banner_suffix = 'nail_salon'; break; // We have nail_salon.png
    case 'Starostlivosť o pleť': $default_icon = 'face'; $cat_banner_suffix = 'skincare'; break;
    case 'Obočie a riasy': $default_icon = 'visibility'; $cat_banner_suffix = 'brows'; break;
    case 'Masáž': $default_icon = 'spa'; $cat_banner_suffix = 'spa_massage'; break; // We have spa_massage.png
    case 'Make-up': $default_icon = 'brush'; $cat_banner_suffix = 'makeup'; break;
    case 'Wellness a kúpele': $default_icon = 'hot_tub'; $cat_banner_suffix = 'wellness'; break;
    case 'Vrkoče a dredy': $default_icon = 'face_3'; $cat_banner_suffix = 'braids'; break;
    case 'Tetovanie': $default_icon = 'draw'; $cat_banner_suffix = 'tattoo'; break;
    case 'Lekárska estetika': $default_icon = 'medical_services'; $cat_banner_suffix = 'medical'; break;
    case 'Depilácia a epilácia': $default_icon = 'airline_seat_flat'; $cat_banner_suffix = 'depilation'; break;
    case 'Domáce služby': $default_icon = 'home'; $cat_banner_suffix = 'home'; break;
    case 'Piercing': $default_icon = 'adjust'; $cat_banner_suffix = 'piercing'; break;
    case 'Služby pre miláčikov': $default_icon = 'pets'; $cat_banner_suffix = 'pets'; break;
    case 'Zubné a ortodontické': $default_icon = 'dentistry'; $cat_banner_suffix = 'dental'; break;
    case 'Zdravie a kondícia': $default_icon = 'fitness_center'; $cat_banner_suffix = 'default'; break;
    case 'Profesionálne služby': $default_icon = 'work'; $cat_banner_suffix = 'default'; break;
    case 'Solárium a opaľovanie': $default_icon = 'wb_sunny'; $cat_banner_suffix = 'default'; break;
    case 'Joga a Pilates': $default_icon = 'self_improvement'; $cat_banner_suffix = 'default'; break;
    case 'Fyzioterapia': $default_icon = 'accessibility_new'; $cat_banner_suffix = 'default'; break;
    default: $default_icon = 'star'; $cat_banner_suffix = 'default'; break;
}

$avatar_html = '';
if ($profile['avatar_url']) {
    $avatar_html = '<img src="' . htmlspecialchars($profile['avatar_url']) . '" alt="Logo">';
} else {
    $avatar_html = '<span class="material-symbols-outlined default-avatar-icon">' . $default_icon . '</span>';
}

$banner_url = 'assets/img/default_banner.jpg'; // We should probably create a default banner or just use one of the ones we have.
if ($profile['banner_url']) {
    $banner_url = $profile['banner_url'];
} else {
    // If not default suffix, check if it exists or we use cat_...
    if (in_array($cat_banner_suffix, ['barber_shop', 'nail_salon', 'spa_massage'])) {
        $banner_url = 'assets/img/' . $cat_banner_suffix . '.png';
    } else {
        $banner_url = 'assets/img/cat_' . $cat_banner_suffix . '.png';
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($name) ?> - <?= BRAND_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .bp-banner {
            width: 100%;
            height: 400px;
            background-color: #333;
            background-image: url('<?= htmlspecialchars($banner_url) ?>');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .bp-banner::after {
            content: '';
            position: absolute;
            top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.4);
        }
        .bp-container {
            max-width: 1000px;
            margin: -80px auto 40px auto;
            position: relative;
            z-index: 2;
            padding: 0 20px;
        }
        .bp-header-card {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 25px;
        }
        .bp-logo-box {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .bp-logo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .default-avatar-icon {
            font-size: 60px;
            color: var(--primary-color);
        }
        .bp-title h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            color: #1e293b;
        }
        .bp-title p {
            margin: 0;
            color: #64748b;
            font-size: 16px;
        }
        .bp-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .bp-badge {
            background: var(--primary-color);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .bp-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .bp-main-panel, .bp-side-panel {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .bp-main-panel h2, .bp-side-panel h2 {
            margin-top: 0;
            font-size: 20px;
            color: #1e293b;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .bp-hours {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .bp-hours li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            color: #475569;
        }
        .bp-hours li:last-child {
            border-bottom: none;
        }
        .book-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: var(--primary-color);
            color: #fff;
            padding: 15px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.2s;
        }
        .book-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .bp-content { grid-template-columns: 1fr; }
            .bp-header-card { flex-direction: column; text-align: center; }
            .bp-badges { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="bp-banner"></div>

<div class="bp-container">
    <div class="bp-header-card">
        <div class="bp-logo-box">
            <?= $avatar_html ?>
        </div>
        <div class="bp-title">
            <h1><?= htmlspecialchars($name) ?></h1>
            <?php if ($address): ?>
            <p><span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">location_on</span> <?= htmlspecialchars($address) ?></p>
            <?php endif; ?>
            <div class="bp-badges">
                <?php foreach($categories as $cat): ?>
                    <?php if(!empty($cat)): ?><span class="bp-badge"><?= htmlspecialchars($cat) ?></span><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="bp-content">
        <div class="bp-main-panel">
            <h2>O prevádzke</h2>
            <p style="line-height: 1.6; color: #475569;"><?= nl2br(htmlspecialchars($description)) ?></p>
        </div>
        
        <div class="bp-side-panel">
            <h2>Otváracie hodiny</h2>
            <ul class="bp-hours">
                <?php 
                $oh = json_decode($profile['opening_hours'], true) ?? [];
                $days = ['mon'=>'Pondelok', 'tue'=>'Utorok', 'wed'=>'Streda', 'thu'=>'Štvrtok', 'fri'=>'Piatok', 'sat'=>'Sobota', 'sun'=>'Nedeľa'];
                foreach ($days as $key => $dname) {
                    $val = '<span style="color:var(--text-secondary);">Zatvorené</span>';
                    if (isset($oh[$key]) && is_array($oh[$key])) {
                        $val = $oh[$key]['open'] . ' - ' . $oh[$key]['close'];
                        if (!empty($oh[$key]['break_start']) && !empty($oh[$key]['break_end'])) {
                            $val .= '<br><span style="font-size:12px; color:var(--text-secondary);">Prestávka: ' . $oh[$key]['break_start'] . ' - ' . $oh[$key]['break_end'] . '</span>';
                        }
                    }
                    echo "<li style='align-items:flex-start;'><span>$dname</span> <strong style='text-align:right;'>$val</strong></li>";
                }
                ?>
            </ul>
            
            <a href="#" class="book-btn">Rezervovať termín</a>

            <?php if (in_array(strtolower($profile['subscription_tier'] ?? 'free'), ['pro', 'vip', 'premium'], true)): ?>
            <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border-color, #e2e8f0);">
                <h2 style="margin-top:0;">Novinky od prevádzky</h2>
                <p style="font-size:13px; color:#64748b; margin:0 0 12px 0;">Nechajte si posielať aktuality priamo od <?= htmlspecialchars($name) ?> — nová ponuka, zmeny otváracích hodín a podobne.</p>
                <div id="newsletter-form">
                    <input type="email" id="newsletter-email" placeholder="Váš e-mail" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color, #e2e8f0); margin-bottom:10px; box-sizing:border-box;">
                    <label style="display:flex; align-items:flex-start; gap:8px; font-size:12px; color:#64748b; margin-bottom:12px; cursor:pointer;">
                        <input type="checkbox" id="newsletter-agree" style="margin-top:2px;">
                        <span>Súhlasím s obchodnými podmienkami a spracovaním osobných údajov.</span>
                    </label>
                    <button type="button" onclick="subscribeNewsletter(<?= (int)$profile['est_id'] ?>)" class="book-btn" style="width:100%; border:none; cursor:pointer;">Odoberať novinky</button>
                    <p id="newsletter-msg" style="font-size:12.5px; margin:10px 0 0 0; display:none;"></p>
                </div>
            </div>
            <script>
                async function subscribeNewsletter(establishmentId) {
                    const emailEl = document.getElementById('newsletter-email');
                    const agreeEl = document.getElementById('newsletter-agree');
                    const msgEl = document.getElementById('newsletter-msg');
                    const email = emailEl.value.trim();
                    msgEl.style.display = 'none';
                    if (!email) { msgEl.textContent = 'Zadajte e-mailovú adresu.'; msgEl.style.color = '#ef4444'; msgEl.style.display = 'block'; return; }
                    if (!agreeEl.checked) { msgEl.textContent = 'Musíte súhlasiť s obchodnými podmienkami a spracovaním osobných údajov.'; msgEl.style.color = '#ef4444'; msgEl.style.display = 'block'; return; }
                    const fd = new FormData();
                    fd.append('action', 'subscribe');
                    fd.append('establishment_id', establishmentId);
                    fd.append('email', email);
                    fd.append('agree_terms', '1');
                    try {
                        const res = await fetch('api/newsletter.php', { method: 'POST', body: fd });
                        const data = await res.json();
                        msgEl.textContent = data.message || data.error || '';
                        msgEl.style.color = data.success ? '#10b981' : '#ef4444';
                        msgEl.style.display = 'block';
                        if (data.success) { emailEl.value = ''; agreeEl.checked = false; }
                    } catch (err) {
                        msgEl.textContent = 'Chyba pripojenia k serveru.'; msgEl.style.color = '#ef4444'; msgEl.style.display = 'block';
                    }
                }
            </script>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
