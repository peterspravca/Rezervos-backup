<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
require_once 'translator_helper.php';

$isLoggedIn = isset($_SESSION['user_id']);
$ctaUrl = $isLoggedIn ? 'affiliate.php' : '#';
$lang = $lang ?? ($_SESSION['lang'] ?? 'sk');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('Kariéra') ?> - <?= htmlspecialchars(BRAND_NAME) ?></title>
    <meta name="description" content="Zarábajte províziu odporúčaním salónov a prevádzok do <?= htmlspecialchars(BRAND_NAME) ?>.">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/variables.css?v=7">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <link rel="stylesheet" href="assets/css/site-footer.css">
    <script src="assets/js/theme.js?v=2.0"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }

        /* Horný panel — identický s index.php */
        .top-bar { display:flex; align-items:center; justify-content:space-between; padding:20px; max-width:1200px; margin:0 auto; width:100%; box-sizing:border-box; }
        .top-bar-left, .top-bar-right { display:flex; align-items:center; gap:10px; }
        .top-btn { height:40px; min-height:40px; max-height:40px; box-sizing:border-box; background:transparent; color:var(--text-primary); border:1px solid #c8d2df; padding:0 16px; border-radius:12px; cursor:pointer; font-size:0.95rem; font-weight:500; display:inline-flex; align-items:center; justify-content:center; gap:8px; font-family:inherit; transition:all 0.2s; text-decoration:none; line-height:1; margin:0; }
        body.dark-mode .top-btn { border-color:var(--border-color); }
        .top-btn:hover { border-color:var(--primary-color); background-color:var(--input-bg); }
        .top-btn .material-symbols-outlined { font-size:20px; font-weight:300; line-height:1; }
        .lang-btn { height:40px; min-height:40px; max-height:40px; padding:0 12px; font-size:0.85rem; display:inline-flex; align-items:center; justify-content:center; gap:6px; line-height:1; box-sizing:border-box; }
        .lang-btn .material-symbols-outlined { font-size:18px; line-height:1; }
        .lang-dropdown { position:relative; display:flex; align-items:center; }
        .lang-dropdown::after { content:''; position:absolute; bottom:-15px; left:0; width:100%; height:15px; background:transparent; }
        .lang-menu { display:none; position:absolute; left:0; top:100%; margin-top:5px; background-color:var(--card-bg); min-width:140px; box-shadow:var(--shadow-md); border-radius:10px; border:1px solid var(--border-color); z-index:100; overflow:hidden; }
        .lang-dropdown:hover .lang-menu { display:block; }
        .lang-menu a { color:var(--text-primary); padding:10px 15px; text-decoration:none; display:flex; align-items:center; gap:10px; font-size:0.9rem; transition:background 0.2s; }
        .lang-menu a:hover { background-color:var(--input-bg); }
        .theme-btn { background:none; width:40px; min-width:40px; max-width:40px; height:40px; min-height:40px; max-height:40px; box-sizing:border-box; color:var(--text-primary); cursor:pointer; padding:0; display:inline-flex; align-items:center; justify-content:center; line-height:1; border:1px solid #c8d2df; border-radius:12px; }
        body.dark-mode .theme-btn { border-color:var(--border-color); }
        .theme-btn .material-symbols-outlined { font-size:20px; line-height:1; }

        /* Obsah stránky */
        .container { max-width:980px; margin:0 auto; padding:0 20px; }
        .btn-primary { background:var(--primary-color); color:#fff; border:none; padding:14px 28px; border-radius:12px; font-weight:700; cursor:pointer; font-size:15px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary:hover { background:var(--primary-hover); }
        .hero { text-align:center; padding:40px 20px 50px; }
        .hero h1 { font-size:38px; font-weight:800; margin:0 0 16px 0; line-height:1.2; }
        .hero p { font-size:16px; color:var(--text-secondary); max-width:560px; margin:0 auto 30px; }
        .grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin:40px 0; }
        .feature-card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:16px; padding:26px; text-align:center; }
        .feature-card .icon-wrap { width:52px; height:52px; border-radius:14px; background:rgba(176,128,66,0.12); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
        .feature-card h3 { font-size:16px; margin:0 0 8px 0; }
        .feature-card p { font-size:13.5px; color:var(--text-secondary); margin:0; line-height:1.5; }
        .steps { background:var(--card-bg); border:1px solid var(--border-color); border-radius:18px; padding:36px; margin:40px 0; }
        .steps h2 { text-align:center; font-size:24px; margin:0 0 30px 0; }
        .step-row { display:flex; gap:18px; align-items:flex-start; margin-bottom:22px; }
        .step-num { width:32px; height:32px; border-radius:50%; background:var(--primary-color); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0; }
        .cta-box { text-align:center; background:linear-gradient(135deg,rgba(176,128,66,0.12),rgba(176,128,66,0.04)); border:1.5px solid rgba(176,128,66,0.3); border-radius:18px; padding:44px 20px; margin:40px 0; }
        .cta-box h2 { font-size:24px; margin:0 0 10px 0; }
        .cta-box p { color:var(--text-secondary); margin:0 0 22px 0; }
        @media (max-width:768px) { .grid-3 { grid-template-columns:1fr; } .hero h1 { font-size:28px; } }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="top-bar-left">
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
        <a href="index.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text-primary);font-weight:800;font-size:18px;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">calendar_month</span> <?= htmlspecialchars(BRAND_NAME) ?>
        </a>
    </div>

    <div class="top-bar-right">
        <?php if ($isLoggedIn): ?>
            <?php if ($_SESSION['user_role'] === 'business'): ?>
                <a href="dashboard.php" class="top-btn" title="Moja Prevádzka"><span class="material-symbols-outlined">storefront</span></a>
            <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin.php" class="top-btn" title="Admin Panel"><span class="material-symbols-outlined">admin_panel_settings</span></a>
            <?php else: ?>
                <a href="moj_profil.php" class="top-btn" title="Môj Profil"><span class="material-symbols-outlined">person</span></a>
            <?php endif; ?>
            <button id="theme-toggle" class="theme-btn" aria-label="Tmavý režim" title="Tmavý režim"><span class="material-symbols-outlined">dark_mode</span></button>
            <a href="logout.php" class="top-btn" title="Odhlásiť sa"><span class="material-symbols-outlined">logout</span></a>
        <?php else: ?>
            <button id="theme-toggle" class="theme-btn" aria-label="Tmavý režim" title="Tmavý režim"><span class="material-symbols-outlined">dark_mode</span></button>
            <a href="#" onclick="openAuthModal(); return false;" class="top-btn"><span class="material-symbols-outlined">login</span> <?= t('Prihlásiť sa') ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1><?= t('Staň sa obchodníkom a získaj ďalší príjem') ?></h1>
        <p><?= t('Poznáte majiteľov salónov, barbershopov alebo iných prevádzok služieb? Odporúčajte ich do') ?> <?= htmlspecialchars(BRAND_NAME) ?> <?= t('a získajte za to reálnu odmenu. Ak sa o svoje prevádzky budete aj naďalej starať, získate za ne opakovane ďalšie odmeny — napríklad každý rok percento z toho, čo prevádzky za svoj balík zaplatia.') ?></p>
        <?php if ($isLoggedIn): ?>
            <a href="<?= htmlspecialchars($ctaUrl) ?>" class="btn-primary"><span class="material-symbols-outlined">handshake</span> <?= t('Prejsť na Affiliate program') ?></a>
        <?php else: ?>
            <a href="#" onclick="openAuthModal('register'); return false;" class="btn-primary"><span class="material-symbols-outlined">handshake</span> <?= t('Chcem sa zapojiť') ?></a>
        <?php endif; ?>
    </div>

    <div class="grid-3">
        <div class="feature-card">
            <div class="icon-wrap"><span class="material-symbols-outlined" style="color:var(--primary-color);">payments</span></div>
            <h3><?= t('Reálna odmena') ?></h3>
            <p><?= t('Za každú prevádzku, ktorú privediete, dostanete odmenu — a pokým sa o ňu staráte a ostáva platiacim zákazníkom, získavate odmenu opakovane, nielen raz.') ?></p>
        </div>
        <div class="feature-card">
            <div class="icon-wrap"><span class="material-symbols-outlined" style="color:var(--primary-color);">link</span></div>
            <h3><?= t('Vlastný odkaz') ?></h3>
            <p><?= t('Po schválení dostanete jednoduchý odporúčací odkaz a kód, ktorý stačí posunúť ďalej.') ?></p>
        </div>
        <div class="feature-card">
            <div class="icon-wrap"><span class="material-symbols-outlined" style="color:var(--primary-color);">bar_chart</span></div>
            <h3><?= t('Prehľadný dashboard') ?></h3>
            <p><?= t('Vidíte presne, koho ste priviedli, koľko vám to prináša a kedy vám bola provízia vyplatená.') ?></p>
        </div>
    </div>

    <div class="steps">
        <h2><?= t('Ako to funguje') ?></h2>
        <div class="step-row">
            <div class="step-num">1</div>
            <div><strong><?= t('Podáte žiadosť') ?></strong><p style="margin:4px 0 0 0;color:var(--text-secondary);font-size:14px;"><?= t('Prihláste sa (alebo si založte účet) a jedným klikom požiadate o zapojenie do programu.') ?></p></div>
        </div>
        <div class="step-row">
            <div class="step-num">2</div>
            <div><strong><?= t('Posúdime žiadosť') ?></strong><p style="margin:4px 0 0 0;color:var(--text-secondary);font-size:14px;"><?= t('Po schválení dostanete prístup k vlastnému odkazu a presnému prehľadu odmien pre jednotlivé balíky.') ?></p></div>
        </div>
        <div class="step-row">
            <div class="step-num">3</div>
            <div><strong><?= t('Odporúčate a zarábate') ?></strong><p style="margin:4px 0 0 0;color:var(--text-secondary);font-size:14px;"><?= t('Za každú prevádzku, ktorá sa cez váš odkaz stane platiacim zákazníkom, vám pripíšeme odmenu — a kým sa o ňu staráte, dostávate ju priebežne aj naďalej, nielen jednorazovo.') ?></p></div>
        </div>
    </div>

    <div class="cta-box">
        <h2><?= t('Pripravení začať?') ?></h2>
        <p><?= t('Zapojenie je zadarmo, bez záväzkov.') ?></p>
        <?php if ($isLoggedIn): ?>
            <a href="<?= htmlspecialchars($ctaUrl) ?>" class="btn-primary"><span class="material-symbols-outlined">arrow_forward</span> <?= t('Pokračovať') ?></a>
        <?php else: ?>
            <a href="#" onclick="openAuthModal('register'); return false;" class="btn-primary"><span class="material-symbols-outlined">arrow_forward</span> <?= t('Zaregistrovať sa') ?></a>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/site-footer.php'; ?>
<?php if (!$isLoggedIn) include_once 'includes/auth_modal.php'; ?>
</body>
</html>
