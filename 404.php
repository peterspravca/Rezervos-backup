<?php
require_once 'config.php';
require_once 'translator_helper.php';
require_once 'includes/branding.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?? 'sk'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Stránka sa nenašla | <?= BRAND_NAME ?></title>
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,300,0,0" />
    
    <link rel="stylesheet" href="assets/css/variables.css?v=7">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <script src="assets/js/theme.js?v=2.0"></script>

    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        .top-bar a.logo-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 19px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
        }

        .top-bar a.logo-link img {
            height: 28px;
            filter: brightness(0) invert(1);
        }
        body:not(.dark-mode) .top-bar a.logo-link img {
            filter: brightness(0);
        }

        .theme-btn {
            background: none;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s;
        }
        .theme-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .content-404 {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
            max-width: 680px;
            margin: 0 auto;
        }

        @keyframes floatLogo {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }

        .logo-container img {
            max-height: 130px;
            margin-bottom: 5px;
            transition: filter 0.3s;
            animation: floatLogo 4s ease-in-out infinite;
        }

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

        .brand-block {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 480px;
            margin-bottom: 25px;
        }

        .icons-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 12px;
        }

        .icons-wrapper .line {
            flex-grow: 1;
            height: 1.5px;
            background-color: var(--primary-color);
            margin: 0 15px;
        }

        .category-icons {
            display: flex;
            gap: 16px;
            color: var(--text-primary);
        }
        .category-icons .material-symbols-outlined {
            font-size: 26px;
            font-weight: 200;
        }

        .slogan {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--text-primary);
            white-space: nowrap;
            text-align: center;
        }

        .error-code {
            font-family: 'Outfit', sans-serif;
            font-size: 80px;
            font-weight: 800;
            line-height: 1;
            margin: 15px 0 10px 0;
            background: linear-gradient(135deg, #d4af37 0%, #b08042 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-title {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: var(--text-primary);
        }

        .error-desc {
            font-size: 15px;
            color: var(--text-secondary);
            margin: 0 0 30px 0;
            line-height: 1.6;
        }

        .btn-group {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-home {
            background: var(--primary-color);
            color: #ffffff;
            border: 1px solid var(--primary-color);
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(176, 128, 66, 0.35);
            transition: all 0.25s ease;
        }
        .btn-home:hover {
            background: var(--primary-hover, #906634);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(176, 128, 66, 0.45);
        }

        .btn-browse {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1.5px solid var(--border-color);
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }
        .btn-browse:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .footer {
            padding: 20px;
            text-align: center;
            border-top: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

    <header class="top-bar">
        <a href="index.php" class="logo-link">
            <?php if (BRAND_NAME === 'Rezervos'): ?>
            <span><span style="color:var(--text-primary);">rezer</span><span style="color:var(--primary-color);">vos</span></span>
            <?php else: ?>
            <span>volnekreslo<span style="color:var(--primary-color);">.sk</span></span>
            <?php endif; ?>
        </a>
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="index.php" class="theme-btn" title="Domov" style="text-decoration:none;">
                <span class="material-symbols-outlined" style="font-size:20px;">home</span>
            </a>
            <button id="theme-toggle" class="theme-btn" aria-label="Toggle Dark Mode" title="Prepnúť režim">
                <span class="material-symbols-outlined" style="font-size: 20px;">dark_mode</span>
            </button>
        </div>
    </header>

    <main class="content-404">
        <div class="logo-container">
            <?php if (BRAND_NAME === 'Rezervos'): ?>
            <img src="/rezervoslogo.png"     alt="Rezervos" class="logo-light">
            <img src="/rezervoslogodark.png" alt="Rezervos" class="logo-dark">
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
            <div class="slogan">VŠETKY SLUŽBY &bull; JEDNO MIESTO</div>
        </div>

        <div class="error-code">404</div>
        <h1 class="error-title">Stránka sa nenašla</h1>
        <p class="error-desc">
            Ups! Zdá sa, že stránka, ktorú hľadáte, neexistuje, bola presunutá alebo zmenila svoju URL adresu.
        </p>

        <div class="btn-group">
            <a href="index.php" class="btn-home">
                <span class="material-symbols-outlined" style="font-size: 20px;">home</span>
                <span>Návrat na Domovskú stránku</span>
            </a>
            <a href="prevadzky.php" class="btn-browse">
                <span class="material-symbols-outlined" style="font-size: 20px;">storefront</span>
                <span>Preskúmať prevádzky</span>
            </a>
        </div>
    </main>

    <footer class="footer">
        &copy; <?= date('Y') ?> <?= BRAND_SITE ?>. Všetky práva vyhradené.
    </footer>

</body>
</html>
