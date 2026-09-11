<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';
require_once 'includes/branding.php';
require_once 'translator_helper.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: inzercia.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM classifieds WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$ad = $stmt->get_result()->fetch_assoc();
if (!$ad) { header('Location: inzercia.php'); exit; }

$is_owner = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$ad['user_id'];
$images   = !empty($ad['images']) ? (json_decode($ad['images'], true) ?: []) : [];

$type_labels = [
    'work' => 'Práca a spolupráca', 'people_seek' => 'Práca a spolupráca', 'people_offer' => 'Práca a spolupráca',
    'rental' => 'Prenájom', 'chair' => 'Prenájom',
    'sale' => 'Predaj a bazár', 'equipment' => 'Predaj a bazár',
    'courses' => 'Kurzy a školenia',
    'other' => 'Ostatné',
];
$type_label = $type_labels[$ad['type']] ?? 'Inzercia';

$page_title = htmlspecialchars($ad['title'] ?: 'Inzerát') . ' | ' . BRAND_SITE;
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,300,0,0" />
    <link rel="stylesheet" href="assets/css/variables.css?v=7">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <script src="assets/js/theme.js?v=2.0"></script>
    <script src="assets/js/dashboard-common.js?v=3.1" defer></script>

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; background: var(--bg-color); color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
        }
        .topbar {
            height: 64px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border-color); background: var(--card-bg); position: sticky; top: 0; z-index: 50;
        }
        .topbar-left,
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .icon-btn {
            width: 40px; min-width: 40px; height: 40px; border-radius: 12px; border: 2px solid var(--border-color); background: var(--card-bg);
            color: var(--text-primary); display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;
            transition: all 0.15s;
        }
        .icon-btn .material-symbols-outlined { font-size: 22px; }
        .icon-btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
        body.dark-mode .icon-btn:not(.logout-control):hover {
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
            background-color: rgba(176, 128, 66, 0.12) !important;
        }
        .icon-btn.login-control,
        .icon-btn.logout-control {
            width: 40px !important;
            min-width: 40px !important;
            max-width: 40px !important;
            padding: 0 !important;
        }
        .icon-btn.logout-control { color: #ef4444; }
        .icon-btn.logout-control:hover { border-color: #ef4444; background: rgba(239, 68, 68, 0.1); }
        .wrap { max-width: 900px; margin: 0 auto; padding: 24px 20px 60px; }
        .breadcrumbs { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-secondary); margin-bottom: 18px; flex-wrap: wrap; }
        .breadcrumbs a { color: var(--primary-color); text-decoration: none; }
        .breadcrumbs a:hover { text-decoration: underline; }
        .gallery { border-radius: 16px; overflow: hidden; background: var(--card-bg); border: 1px solid var(--border-color); margin-bottom: 20px; }
        .gallery-main { height: 340px; background: var(--input-bg); display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .gallery-main img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-thumbs { display: flex; gap: 8px; padding: 10px; overflow-x: auto; }
        .gallery-thumbs img { width: 64px; height: 64px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; flex-shrink: 0; }
        .gallery-thumbs img.active { border-color: var(--primary-color); }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        .title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 6px; }
        .title { font-size: 24px; font-weight: 800; margin: 0; }
        .price { font-size: 22px; font-weight: 800; color: var(--primary-color); white-space: nowrap; }
        .badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .badge { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 8px; background: rgba(176,128,66,0.12); color: var(--primary-color); }
        .badge.status-sold { background: rgba(239,68,68,0.12); color: #ef4444; }
        .badge.status-reserved { background: rgba(245,158,11,0.12); color: #f59e0b; }
        .meta-row { display: flex; gap: 18px; flex-wrap: wrap; color: var(--text-secondary); font-size: 13.5px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid var(--border-color); }
        .meta-row span { display: flex; align-items: center; gap: 5px; }
        .desc { font-size: 14.5px; line-height: 1.7; color: var(--text-primary); white-space: pre-wrap; margin-bottom: 20px; }
        .contact-box { background: var(--input-bg); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .contact-box a { color: var(--primary-color); text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); }
        .btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
        .btn-primary { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
        .btn-primary:hover { opacity: 0.9; color: #fff; }
        .empty-img { display:flex; align-items:center; justify-content:center; height:100%; color: var(--text-secondary); opacity: 0.3; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-left">
            <a href="inzercia.php" class="icon-btn" title="Späť na inzerciu" aria-label="Späť na inzerciu">
                <span class="material-symbols-outlined">left_panel_open</span>
            </a>
            <a href="index.php" class="icon-btn" title="Domov" aria-label="Domov">
                <span class="material-symbols-outlined">home</span>
            </a>
        </div>
        <div class="topbar-right">
            <button id="theme-toggle" class="icon-btn" aria-label="Tmavý režim"><span class="material-symbols-outlined">dark_mode</span></button>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="icon-btn logout-control" title="Odhlásiť sa" aria-label="Odhlásiť sa">
                    <span class="material-symbols-outlined">logout</span>
                </a>
            <?php else: ?>
                <a href="#" onclick="openAuthModal(); return false;" class="icon-btn login-control" title="Prihlásiť sa" aria-label="Prihlásiť sa">
                    <span class="material-symbols-outlined">login</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="wrap">
        <div class="breadcrumbs">
            <a href="index.php">Domov</a>
            <span class="material-symbols-outlined" style="font-size:14px;">chevron_right</span>
            <a href="inzercia.php">Inzercia</a>
            <span class="material-symbols-outlined" style="font-size:14px;">chevron_right</span>
            <span><?php echo htmlspecialchars($ad['title']); ?></span>
        </div>

        <div class="gallery">
            <div class="gallery-main" id="gallery-main">
                <?php if (!empty($images[0])): ?>
                    <img id="gallery-main-img" src="/<?php echo htmlspecialchars($images[0]); ?>" alt="">
                <?php else: ?>
                    <div class="empty-img"><span class="material-symbols-outlined" style="font-size:56px;">image</span></div>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                    <img src="/<?php echo htmlspecialchars($img); ?>" class="<?php echo $i===0?'active':''; ?>" onclick="switchImage('/<?php echo htmlspecialchars($img); ?>', this)" alt="">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="title-row">
                <h1 class="title" id="ad-title-text"><?php echo htmlspecialchars($ad['title']); ?></h1>
                <?php if ($ad['price']): ?><div class="price"><?php echo number_format((float)$ad['price'], 2, ',', ' '); ?> €<?php echo $ad['price_unit'] ? ' / '.htmlspecialchars($ad['price_unit']) : ''; ?></div><?php endif; ?>
            </div>
            <div class="badges">
                <span class="badge"><?php echo htmlspecialchars($type_label); ?></span>
                <?php if ($ad['condition']): ?><span class="badge"><?php echo htmlspecialchars($ad['condition']); ?></span><?php endif; ?>
                <?php if (($ad['status'] ?? 'active') === 'sold'): ?><span class="badge status-sold">Predané</span><?php endif; ?>
                <?php if (($ad['status'] ?? 'active') === 'reserved'): ?><span class="badge status-reserved">Rezervované</span><?php endif; ?>
            </div>
            <div class="meta-row">
                <?php if ($ad['location']): ?><span><span class="material-symbols-outlined" style="font-size:16px;">location_on</span><?php echo htmlspecialchars($ad['location']); ?></span><?php endif; ?>
                <span><span class="material-symbols-outlined" style="font-size:16px;">calendar_today</span><?php echo date('d.m.Y', strtotime($ad['created_at'])); ?></span>
                <span><span class="material-symbols-outlined" style="font-size:16px;">visibility</span><span id="views-count"><?php echo (int)$ad['views']; ?></span>×</span>
            </div>
            <?php if ($ad['description']): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:8px;">
                <h3 style="font-size:14px;margin:0;color:var(--text-primary);font-weight:700;display:flex;align-items:center;gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">description</span> Popis inzerátu
                </h3>
                <div class="translate-dropdown" style="position:relative;display:inline-block;">
                    <div id="translate-ad-container" onclick="toggleTranslateDropdown()" style="display:inline-flex;align-items:center;gap:6px;background:var(--input-bg);border:1px solid var(--border-color);color:var(--primary-color);font-size:12px;font-weight:700;padding:6px 12px;border-radius:8px;cursor:pointer;">
                        <span id="translate-btn-icon" class="material-symbols-outlined" style="font-size:16px;">g_translate</span>
                        <span id="translate-btn-text">Preložiť do...</span>
                        <span class="material-symbols-outlined" style="font-size:16px;">arrow_drop_down</span>
                    </div>
                    <div id="translate-dropdown-menu" style="display:none;position:absolute;top:100%;right:0;margin-top:6px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.15);width:180px;z-index:100;overflow:hidden;">
                        <ul style="list-style:none;margin:0;padding:6px 0;">
                            <?php
                            $translate_langs = ['cz' => 'Čeština', 'en' => 'English', 'de' => 'Deutsch', 'pl' => 'Polski', 'hu' => 'Magyar', 'ua' => 'Українська'];
                            foreach ($translate_langs as $t_code => $t_name):
                                if ($t_code === ($lang ?? 'sk')) continue;
                            ?>
                            <li onclick="selectTranslateLang('<?php echo $t_code; ?>')" style="padding:8px 14px;cursor:pointer;font-size:13px;color:var(--text-primary);display:flex;align-items:center;justify-content:space-between;">
                                <span><?php echo htmlspecialchars($t_name); ?></span>
                                <span style="opacity:0.4;font-size:11px;font-weight:800;text-transform:uppercase;"><?php echo strtoupper($t_code); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div id="translate-sub-banner" style="display:none;align-items:center;gap:8px;background:rgba(39,174,96,0.08);border:1px solid rgba(39,174,96,0.25);color:#27ae60;font-size:12.5px;font-weight:600;padding:10px 14px;border-radius:8px;margin-bottom:14px;">
                <span class="material-symbols-outlined" style="font-size:18px;color:#27ae60;">auto_awesome</span>
                <div style="display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:8px;">
                    <span id="translate-banner-info"></span>
                    <a href="javascript:void(0)" onclick="revertToOriginal()" style="color:var(--primary-color);text-decoration:underline;font-weight:700;">Zobraziť originál</a>
                </div>
            </div>
            <div class="desc" id="ad-desc-text"><?php echo htmlspecialchars($ad['description']); ?></div>
            <?php endif; ?>

            <?php if ($ad['phone'] || $ad['email']): ?>
            <div class="contact-box">
                <?php if ($ad['phone']): ?><a href="tel:<?php echo htmlspecialchars($ad['phone']); ?>"><span class="material-symbols-outlined" style="font-size:18px;">call</span><?php echo htmlspecialchars($ad['phone']); ?></a><?php endif; ?>
                <?php if ($ad['email']): ?><a href="mailto:<?php echo htmlspecialchars($ad['email']); ?>"><span class="material-symbols-outlined" style="font-size:18px;">mail</span><?php echo htmlspecialchars($ad['email']); ?></a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <?php if ($ad['phone']): ?>
            <a href="tel:<?php echo htmlspecialchars($ad['phone']); ?>" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size:18px;">call</span> Zavolať
            </a>
            <?php endif; ?>
            <?php if ($ad['email']): ?>
            <a href="mailto:<?php echo htmlspecialchars($ad['email']); ?>" class="btn">
                <span class="material-symbols-outlined" style="font-size:18px;">mail</span> Poslať e-mail
            </a>
            <?php endif; ?>
            <button type="button" class="btn<?php echo (!$ad['phone'] && !$ad['email']) ? ' btn-primary' : ''; ?>" onclick="shareAd()">
                <span class="material-symbols-outlined" style="font-size:18px;">share</span> Zdieľať
            </button>
            <?php if ($is_owner): ?>
            <?php $my_listings_url = (isset($_SESSION['user_role']) && $_SESSION['user_role']==='business') ? 'dashboard-inzercia.php' : (($_SESSION['user_role'] ?? '')==='admin' ? 'admin-inzercia.php' : 'moj_profil-inzercia.php'); ?>
            <button type="button" class="btn" onclick="document.getElementById('modal-boost').style.display='flex'">
                <span class="material-symbols-outlined" style="font-size:18px;">rocket_launch</span> Zviditeľniť
            </button>
            <a href="<?php echo $my_listings_url; ?>" class="btn">
                <span class="material-symbols-outlined" style="font-size:18px;">edit</span> Upraviť
            </a>
            <button type="button" class="btn" onclick="deleteAd()">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span> Zmazať
            </button>
            <a href="<?php echo $my_listings_url; ?>" class="btn">
                <span class="material-symbols-outlined" style="font-size:18px;">newspaper</span> Moje inzeráty
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($is_owner): ?>
    <!-- MODAL: ZVIDITEĽNIŤ INZERÁT -->
    <div id="modal-boost" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
      <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:440px;width:100%;padding:26px;box-shadow:0 20px 40px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <h3 style="margin:0;font-size:17px;font-weight:800;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">rocket_launch</span>Zviditeľniť inzerát</h3>
          <button type="button" onclick="document.getElementById('modal-boost').style.display='none'" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:22px;">&times;</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <button type="button" onclick="confirmBoost('single_tap',0.25)" class="boost-opt"><div><strong>Jednorazové tapnutie</strong><div style="font-size:11.5px;color:var(--text-secondary);">Posun na 1. miesto</div></div><span style="color:var(--primary-color);font-weight:800;">0,25 €</span></button>
          <button type="button" onclick="confirmBoost('morning',0.90)" class="boost-opt"><div><strong>Ranné vtáča (7 dní)</strong><div style="font-size:11.5px;color:var(--text-secondary);">Tapnutie každé ráno</div></div><span style="color:var(--primary-color);font-weight:800;">0,90 €</span></button>
          <button type="button" onclick="confirmBoost('week',1.20)" class="boost-opt"><div><strong>7-dňové topovanie</strong><div style="font-size:11.5px;color:var(--text-secondary);">Trvalo hore po dobu 7 dní</div></div><span style="color:var(--primary-color);font-weight:800;">1,20 €</span></button>
          <button type="button" onclick="confirmBoost('vip_glow',0.60)" class="boost-opt"><div><strong>Neon Glow rámček (7 dní)</strong><div style="font-size:11.5px;color:var(--text-secondary);">Farebný rámček karty</div></div><span style="color:var(--primary-color);font-weight:800;">0,60 €</span></button>
          <button type="button" onclick="confirmBoost('vip_badge',0.40)" class="boost-opt"><div><strong>Štítok "Rýchly predaj" (7 dní)</strong></div><span style="color:var(--primary-color);font-weight:800;">0,40 €</span></button>
        </div>
      </div>
    </div>
    <?php include_once 'includes/auth_modal.php'; ?>
    <style>
      .boost-opt { border:2px solid var(--border-color); border-radius:12px; padding:12px 14px; cursor:pointer; text-align:left; background:var(--card-bg); display:flex; justify-content:space-between; align-items:center; gap:10px; font-family:inherit; width:100%; box-sizing:border-box; }
      .boost-opt:hover { border-color:var(--primary-color); }
    </style>
    <?php endif; ?>

    <script>
        const AD_ID = <?php echo (int)$ad['id']; ?>;

        function switchImage(src, el) {
            document.getElementById('gallery-main-img').src = src;
            document.querySelectorAll('.gallery-thumbs img').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
        }

        function toast(msg, type) {
            let c = document.getElementById('_ad_tc');
            if (!c) { c = document.createElement('div'); c.id = '_ad_tc'; c.style.cssText = 'position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;'; document.body.appendChild(c); }
            const t = document.createElement('div');
            t.style.cssText = `background:${type==='error'?'#ef4444':type==='warning'?'#f59e0b':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,0.18);`;
            t.innerText = msg; c.appendChild(t);
            setTimeout(() => t.remove(), 3500);
        }

        async function claimShareReward(platform) {
            try {
                const fd = new FormData();
                fd.append('action', 'claim_share_reward'); fd.append('platform', platform);
                fd.append('target', 'listing'); fd.append('listing_id', AD_ID);
                const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) toast(data.message, 'success');
                else if (!data.already_claimed && data.error && data.error.indexOf('prihlásený') === -1) toast(data.error, 'error');
            } catch (e) {}
        }

        function shareAd() {
            const url = window.location.href, title = <?php echo json_encode($ad['title'] ?: 'Inzerát na ' . BRAND_SITE); ?>;
            if (navigator.share) {
                navigator.share({ title, url }).then(() => claimShareReward('native')).catch(() => {});
                return;
            }
            const fbUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`;
            const win = window.open(fbUrl, 'fb-share', 'width=600,height=500');
            const timer = setInterval(() => { if (!win || win.closed) { clearInterval(timer); claimShareReward('facebook'); } }, 1000);
        }

        <?php if ($is_owner): ?>
        async function deleteAd() {
            const ok = await confirmModal('Naozaj chcete natrvalo zmazať tento inzerát?', { okText: 'Zmazať', cancelText: 'Zrušiť' });
            if (!ok) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete_listing'); fd.append('id', AD_ID);
                const res = await fetch('api/classifieds.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showAppToast(data.message || 'Inzerát bol zmazaný.', 'success');
                    setTimeout(() => { window.location.href = 'inzercia.php'; }, 800);
                } else {
                    showAppToast(data.error || 'Zmazanie sa nepodarilo.', 'error');
                }
            } catch (e) { showAppToast('Chyba pripojenia.', 'error'); }
        }

        async function confirmBoost(boostKey, price) {
            document.getElementById('modal-boost').style.display = 'none';
            try {
                const fd = new FormData();
                fd.append('action', 'boost_listing'); fd.append('id', AD_ID); fd.append('boost_key', boostKey);
                const res = await fetch('api/classifieds.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) toast(data.message || 'Aktivované!', 'success');
                else toast(data.error || 'Chyba.', 'error');
            } catch (e) { toast('Chyba pripojenia.', 'error'); }
        }
        <?php endif; ?>

        // Zvýšenie počtu zobrazení (raz za návštevu)
        (function () {
            const key = 'vk_viewed_' + AD_ID;
            try {
                if (sessionStorage.getItem(key)) return;
                sessionStorage.setItem(key, '1');
            } catch (e) {}
            const fd = new FormData(); fd.append('action', 'increment_views'); fd.append('id', AD_ID);
            fetch('api/classifieds.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => { if (d.success) { const el = document.getElementById('views-count'); if (el) el.innerText = parseInt(el.innerText) + 1; } })
                .catch(() => {});
        })();

        // Preklad inzerátu na požiadanie ("Preložiť do...")
        let isTranslating = false, isTranslated = false, originalTitle = null, originalDescHTML = null;

        function toggleTranslateDropdown() {
            const menu = document.getElementById('translate-dropdown-menu');
            if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
        document.addEventListener('click', function (event) {
            const container = document.querySelector('.translate-dropdown');
            const menu = document.getElementById('translate-dropdown-menu');
            if (container && menu && !container.contains(event.target)) menu.style.display = 'none';
        });

        function selectTranslateLang(lang) {
            document.getElementById('translate-dropdown-menu').style.display = 'none';
            translateAdContent(lang);
        }

        function translateAdContent(targetLang) {
            if (isTranslating) return;
            const titleEl = document.getElementById('ad-title-text');
            const descEl = document.getElementById('ad-desc-text');
            if (originalTitle === null) { originalTitle = titleEl.textContent; originalDescHTML = descEl.innerHTML; }

            const btnText = document.getElementById('translate-btn-text');
            const icon = document.getElementById('translate-btn-icon');
            const container = document.getElementById('translate-ad-container');
            const banner = document.getElementById('translate-sub-banner');
            const bannerInfo = document.getElementById('translate-banner-info');

            isTranslating = true;
            container.style.pointerEvents = 'none';
            const prevBtnText = btnText.textContent, prevIcon = icon.textContent;
            icon.textContent = 'sync';
            icon.style.animation = 'spin 1s linear infinite';
            btnText.textContent = 'Prekladám...';

            fetch('api/translate_ad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ad_id: AD_ID, target_lang: targetLang })
            })
            .then(r => r.json())
            .then(data => {
                isTranslating = false;
                container.style.pointerEvents = 'auto';
                btnText.textContent = prevBtnText;
                icon.textContent = prevIcon;
                icon.style.animation = '';

                if (data.success) {
                    isTranslated = true;
                    titleEl.textContent = data.translated_title;
                    descEl.textContent = data.translated_description;
                    bannerInfo.innerHTML = `Preložené z jazyka: <strong>${data.source_lang_name}</strong> pomocou AI.`;
                    banner.style.display = 'flex';
                } else {
                    toast(data.error || 'Nepodarilo sa preložiť inzerát.', 'error');
                }
            })
            .catch(() => {
                isTranslating = false;
                container.style.pointerEvents = 'auto';
                btnText.textContent = prevBtnText;
                icon.textContent = prevIcon;
                icon.style.animation = '';
                toast('Chyba spojenia s prekladovou službou.', 'error');
            });
        }

        function revertToOriginal() {
            if (!isTranslated) return;
            document.getElementById('ad-title-text').textContent = originalTitle;
            document.getElementById('ad-desc-text').innerHTML = originalDescHTML;
            document.getElementById('translate-sub-banner').style.display = 'none';
            isTranslated = false;
        }
    </script>
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</body>
</html>
