<?php
require_once 'config.php';
require_once 'translator_helper.php';
require_once 'includes/branding.php';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kontakt — <?= BRAND_NAME ?></title>
<link rel="stylesheet" href="assets/css/variables.css?v=7">
<link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
<link rel="stylesheet" href="assets/css/site-footer.css?v=5">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,300,0,0" />
<style>
    .legal-header { max-width: 800px; margin: 0 auto; padding: 50px 20px 10px; }
    .legal-header a.back { color: var(--text-secondary); text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .legal-header a.back:hover { color: var(--primary-color); }
    .legal-header h1 { margin: 20px 0 6px 0; font-size: 1.8rem; }
    .legal-header p { color: var(--text-secondary); font-size: 15px; }
    .contact-wrap { max-width: 800px; margin: 20px auto 40px; padding: 0 20px; display: flex; flex-direction: column; gap: 14px; }
    .contact-card { display: flex; align-items: center; gap: 16px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 20px; }
    .contact-icon { width: 46px; height: 46px; border-radius: 12px; background: rgba(176,128,66,0.12); color: var(--primary-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .contact-card h3 { margin: 0 0 4px 0; font-size: 15px; }
    .contact-card a, .contact-card span { color: var(--text-secondary); font-size: 14px; text-decoration: none; }
    .contact-card a:hover { color: var(--primary-color); }
</style>
</head>
<body>

<div class="legal-header">
    <a href="index.php" class="back"><span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Späť na <?= BRAND_SITE ?></a>
    <h1>Kontakt</h1>
    <p>Máte otázku k rezervácii, k účtu, alebo chcete zaregistrovať svoju prevádzku? Ozvite sa nám.</p>
</div>

<div class="contact-wrap">
    <div class="contact-card">
        <div class="contact-icon"><span class="material-symbols-outlined">mail</span></div>
        <div>
            <h3>E-mail</h3>
            <a href="mailto:<?= htmlspecialchars($smtp_user) ?>"><?= htmlspecialchars($smtp_user) ?></a>
        </div>
    </div>
    <div class="contact-card">
        <div class="contact-icon"><span class="material-symbols-outlined">help</span></div>
        <div>
            <h3>Časté otázky</h3>
            <a href="faq.php">Skúste najprv Časté otázky — možno tam nájdete odpoveď hneď.</a>
        </div>
    </div>
    <div class="contact-card">
        <div class="contact-icon"><span class="material-symbols-outlined">storefront</span></div>
        <div>
            <h3>Registrácia prevádzky</h3>
            <a href="#" onclick="openAuthModal('register'); return false;">Zaregistrujte svoju prevádzku priamo tu</a>
        </div>
    </div>
</div>

<?php include 'includes/site-footer.php'; ?>
<?php include_once 'includes/auth_modal.php'; ?>
</body>
</html>
