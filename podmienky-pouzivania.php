<?php
require_once 'config.php';
require_once 'includes/branding.php';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Podmienky používania — <?= BRAND_NAME ?></title>
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
    <h1>Podmienky používania</h1>
    <p class="updated">Platné od 2. 9. 2026</p>
</div>

<div class="legal-content">
    <p><?= BRAND_NAME ?> (ďalej len "platforma") prevádzkuje online rezervačný systém, ktorý spája zákazníkov s prevádzkami poskytujúcimi služby (salóny, štúdiá, trénerov a podobne). Používaním platformy súhlasíte s nižšie uvedenými podmienkami.</p>

    <h2>1. Kto môže platformu používať</h2>
    <p>Platformu môže využívať každý, kto si vytvorí účet a overí svoju e-mailovú adresu 6-miestnym kódom. Overenie účtu je podmienkou pre vytvorenie rezervácie — chráni to pred falošnými rezerváciami a je nutným predpokladom pre fungovanie systému hodnotení dôveryhodnosti.</p>

    <h2>2. Rezervácie</h2>
    <p>Rezerváciou termínu vzniká záväzok medzi vami a danou prevádzkou. Platforma sprostredkúva rezerváciu, ale samotnú službu poskytuje prevádzka — podmienky poskytnutia služby (napr. stornopoplatky, zálohy) určuje prevádzka. Zálohy a platby idú vždy priamo prevádzke, platforma peniaze nedrží.</p>
    <p>Rezerváciu môžete zrušiť vo svojom profile alebo cez odkaz v potvrdzovacom e-maile. Z bezpečnostných dôvodov je zrušenie dvojfázové — po potvrdení vám príde ešte jeden e-mail s odkazom, ktorý treba potvrdiť, aby sa zrušenie naozaj vykonalo.</p>

    <h2>3. Hodnotenia a dôveryhodnosť</h2>
    <p>Po návšteve si zákazník a prevádzka môžu navzájom udeliť hodnotenie. Agregovaný priemer hodnotení je verejný, podrobné poznámky ostávajú súkromné. Pri opakovanom porušovaní pravidiel (napr. časté neospravedlnené absencie) môže prevádzka zákazníka zablokovať len pre seba, nie naprieč celou platformou.</p>

    <h2>4. Marketingová komunikácia a novinky prevádzok</h2>
    <p>Marketingové e-maily (napr. narodeninové zľavy, hromadné kampane prevádzky) vám chodia len na základe samostatného súhlasu, ktorý dobrovoľne udeľujete pri rezervácii. Súhlas môžete kedykoľvek odvolať cez odkaz "Odhlásiť sa" v päte e-mailu. Odber noviniek konkrétnej prevádzky je samostatná, nezávislá voľba s vlastným odhlasovacím odkazom.</p>

    <h2>5. Zodpovednosť</h2>
    <p>Platforma sa snaží zabezpečiť spoľahlivú funkčnosť rezervačného systému, negarantuje však kvalitu služieb poskytovaných jednotlivými prevádzkami — tá je vecou dohody medzi vami a danou prevádzkou.</p>

    <h2>6. Zmeny podmienok</h2>
    <p>Podmienky môžeme z času na čas upraviť, napríklad pri zavedení novej funkcie. O väčších zmenách vás budeme informovať.</p>

    <h2>7. Kontakt</h2>
    <p>Otázky k týmto podmienkam smerujte na <a href="mailto:<?= htmlspecialchars($smtp_user) ?>"><?= htmlspecialchars($smtp_user) ?></a> alebo cez stránku <a href="kontakt.php">Kontakt</a>.</p>
</div>

<?php include 'includes/site-footer.php'; ?>
</body>
</html>
