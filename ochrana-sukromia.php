<?php
require_once 'config.php';
require_once 'includes/branding.php';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ochrana súkromia — <?= BRAND_NAME ?></title>
<link rel="stylesheet" href="assets/css/variables.css?v=7">
<link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
<link rel="stylesheet" href="assets/css/site-footer.css?v=5">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,300,0,0" />
<style>
    .legal-header { max-width: 760px; margin: 0 auto; padding: 50px 20px 10px; }
    .legal-header a.back { color: var(--text-secondary); text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .legal-header a.back:hover { color: var(--primary-color); }
    .legal-header h1 { margin: 20px 0 6px 0; font-size: 1.8rem; }
    .legal-header p.updated { color: var(--text-secondary); font-size: 13px; }
    .legal-content { max-width: 760px; margin: 10px auto 40px; padding: 0 20px; color: var(--text-primary); line-height: 1.7; font-size: 15px; }
    .legal-content h2 { font-size: 1.15rem; margin: 32px 0 10px 0; }
    .legal-content p, .legal-content li { color: var(--text-secondary); }
    .legal-content a { color: var(--primary-color); }
</style>
</head>
<body>

<div class="legal-header">
    <a href="index.php" class="back"><span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Späť na <?= BRAND_SITE ?></a>
    <h1>Ochrana súkromia</h1>
    <p class="updated">Platné od 2. 9. 2026</p>
</div>

<div class="legal-content">
    <p>Táto stránka vysvetľuje, aké osobné údaje o vás <?= BRAND_NAME ?> spracúva, na aký účel, a aké máte v tejto súvislosti práva.</p>

    <h2>1. Aké údaje spracúvame</h2>
    <ul>
        <li>Registračné údaje účtu — meno, e-mail, telefón, prípadne dátum narodenia (ak si ho sami doplníte kvôli narodeninovým zľavám).</li>
        <li>História rezervácií a s ňou súvisiace hodnotenia.</li>
        <li>Súhlas so zasielaním marketingovej komunikácie a/alebo odber noviniek konkrétnej prevádzky — vrátane e-mailovej adresy, ak sa prihlásite na odber noviniek bez vytvorenia účtu.</li>
        <li>Technické údaje potrebné na fungovanie účtu (napr. bezpečnostné tokeny pri odhlásení z odberu alebo potvrdení zrušenia rezervácie).</li>
    </ul>

    <h2>2. Na čo údaje používame</h2>
    <p>Údaje používame na sprostredkovanie rezervácie medzi vami a prevádzkou, na zasielanie potvrdení a pripomienok súvisiacich s rezerváciou, a — len s vaším súhlasom — na zasielanie marketingovej komunikácie alebo noviniek prevádzky.</p>

    <h2>3. Komu údaje sprístupňujeme</h2>
    <p>Údaje potrebné na vybavenie rezervácie (meno, kontakt) vidí prevádzka, u ktorej rezervujete. Prevádzky od vyššieho balíka si môžu zvoliť odosielanie e-mailov z vlastnej pripojenej schránky namiesto nášho systémového účtu — v tom prípade správu doručuje ich vlastný mailový server. Údaje nepredávame tretím stranám na ich vlastné marketingové účely.</p>

    <h2>4. Vaše práva</h2>
    <ul>
        <li>Súhlas s marketingovou komunikáciou môžete kedykoľvek odvolať cez odkaz "Odhlásiť sa" v päte e-mailu.</li>
        <li>Odber noviniek konkrétnej prevádzky môžete kedykoľvek zrušiť cez odkaz v e-maile alebo vo svojom profile.</li>
        <li>O prístup k svojim údajom, ich opravu alebo vymazanie môžete požiadať na <a href="mailto:<?= htmlspecialchars($smtp_user) ?>"><?= htmlspecialchars($smtp_user) ?></a>.</li>
    </ul>

    <h2>5. Súbory cookies</h2>
    <p>Na uloženie prihlásenia a vašich nastavení (napr. zvolený jazyk alebo svetlý/tmavý režim) používame nevyhnutné technické cookies a lokálne úložisko prehliadača. Nepoužívame cookies tretích strán na sledovanie naprieč inými webmi.</p>

    <h2>6. Kontakt</h2>
    <p>Otázky k ochrane súkromia smerujte na <a href="mailto:<?= htmlspecialchars($smtp_user) ?>"><?= htmlspecialchars($smtp_user) ?></a> alebo cez stránku <a href="kontakt.php">Kontakt</a>.</p>
</div>

<?php include 'includes/site-footer.php'; ?>
</body>
</html>
