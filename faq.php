<?php
require_once 'config.php';
require_once 'includes/branding.php';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Časté otázky — <?= BRAND_NAME ?></title>
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
    .faq-wrap { max-width: 800px; margin: 20px auto 40px; padding: 0 20px; }
    .faq-item { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; margin-bottom: 12px; overflow: hidden; }
    .faq-q { width: 100%; text-align: left; background: none; border: none; padding: 18px 20px; font-size: 15px; font-weight: 700; color: var(--text-primary); cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .faq-q .material-symbols-outlined { transition: transform 0.2s; color: var(--primary-color); flex-shrink: 0; }
    .faq-item.open .faq-q .material-symbols-outlined { transform: rotate(180deg); }
    .faq-a { max-height: 0; overflow: hidden; transition: max-height 0.25s ease; padding: 0 20px; }
    .faq-item.open .faq-a { max-height: 400px; padding: 0 20px 18px; }
    .faq-a p { margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.6; }
</style>
</head>
<body>

<div class="legal-header">
    <a href="index.php" class="back"><span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Späť na <?= BRAND_SITE ?></a>
    <h1>Časté otázky</h1>
</div>

<div class="faq-wrap">
    <div class="faq-item">
        <button class="faq-q" onclick="this.parentElement.classList.toggle('open')">Ako si vytvorím rezerváciu?<span class="material-symbols-outlined">expand_more</span></button>
        <div class="faq-a"><p>Vyhľadajte prevádzku alebo službu na stránke <a href="prevadzky.php">Vyhľadať prevádzky</a>, vyberte si voľný termín a dokončite rezerváciu. Na dokončenie potrebujete overený účet — pri registrácii vám pošleme 6-miestny overovací kód na e-mail.</p></div>
    </div>
    <div class="faq-item">
        <button class="faq-q" onclick="this.parentElement.classList.toggle('open')">Ako zruším rezerváciu?<span class="material-symbols-outlined">expand_more</span></button>
        <div class="faq-a"><p>V potvrdzovacom e-maile aj vo svojom profile nájdete tlačidlo na zrušenie. Z bezpečnostných dôvodov zrušenie nie je okamžité — po kliknutí vám príde ešte jeden e-mail s potvrdzovacím odkazom, aby sa rezervácia nezrušila omylom. Rezervácia sa zruší až po kliknutí na tento druhý odkaz.</p></div>
    </div>
    <div class="faq-item">
        <button class="faq-q" onclick="this.parentElement.classList.toggle('open')">Prečo mi chodia/nechodia e-maily s ponukami?<span class="material-symbols-outlined">expand_more</span></button>
        <div class="faq-a"><p>Marketingové e-maily a zľavy (napr. narodeninová zľava) vám chodia len vtedy, ak ste pri rezervácii zaškrtli súhlas so zasielaním ponúk. Súhlas môžete kedykoľvek odvolať cez odkaz "Odhlásiť sa" v päte každého takého e-mailu.</p></div>
    </div>
    <div class="faq-item">
        <button class="faq-q" onclick="this.parentElement.classList.toggle('open')">Čím sa líšia "novinky prevádzky" od marketingových e-mailov?<span class="material-symbols-outlined">expand_more</span></button>
        <div class="faq-a"><p>Novinky prevádzky sú samostatný, dobrovoľný odber aktualít od konkrétnej prevádzky (napr. nová kolegyňa, zmena otváracích hodín) — prihlasujete sa naň zvlášť, nezávisle od súhlasu s marketingom, a rovnako sa dá kedykoľvek odhlásiť.</p></div>
    </div>
    <div class="faq-item">
        <button class="faq-q" onclick="this.parentElement.classList.toggle('open')">Ako fungujú balíky pre prevádzky (Free, Štart, Pro...)?<span class="material-symbols-outlined">expand_more</span></button>
        <div class="faq-a"><p>Prevádzky si vyberajú spomedzi niekoľkých balíkov s rôznym rozsahom funkcií (napr. vlastná e-mailová schránka priamo v appke od balíka Štart, alebo odber noviniek pre zákazníkov od balíka Pro). Bližšie informácie získa prevádzka po prihlásení vo svojom dashboarde.</p></div>
    </div>
    <div class="faq-item">
        <button class="faq-q" onclick="this.parentElement.classList.toggle('open')">Neviem si nájsť odpoveď — čo teraz?<span class="material-symbols-outlined">expand_more</span></button>
        <div class="faq-a"><p>Napíšte nám cez stránku <a href="kontakt.php">Kontakt</a>, radi pomôžeme.</p></div>
    </div>
</div>

<?php include 'includes/site-footer.php'; ?>
</body>
</html>
