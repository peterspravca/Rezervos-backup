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
<title>Cenník a balíčky — <?= BRAND_NAME ?></title>
<link rel="stylesheet" href="assets/css/variables.css?v=7">
<link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
<link rel="stylesheet" href="assets/css/site-footer.css?v=5">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,300,0,0" />
<style>
    .legal-header { max-width: 1100px; margin: 0 auto; padding: 50px 20px 10px; text-align: center; }
    .legal-header a.back { color: var(--text-secondary); text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; float: left; }
    .legal-header a.back:hover { color: var(--primary-color); }
    .legal-header h1 { margin: 30px 0 8px 0; font-size: 2rem; }
    .legal-header p { color: var(--text-secondary); font-size: 15px; max-width: 560px; margin: 0 auto; }

    .pricing-wrap { max-width: 1350px; margin: 30px auto 50px; padding: 0 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 20px; align-items: stretch; }
    @media (max-width: 980px) { .pricing-wrap { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 600px) { .pricing-wrap { grid-template-columns: 1fr; } }

    .p-card { background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 16px; padding: 26px 22px; display: flex; flex-direction: column; text-align: left; position: relative; }
    .p-card.featured { border: 2px solid #8b5cf6; box-shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.35); }
    .p-badge { position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #8b5cf6, #6366f1); color: #fff; font-size: 10.5px; padding: 4px 12px; border-radius: 8px; font-weight: 800; letter-spacing: 0.5px; }
    .p-card h3 { margin: 0 0 6px 0; font-size: 19px; font-weight: 800; display: flex; align-items: center; gap: 6px; }
    .p-card .p-desc { font-size: 12.5px; color: var(--text-secondary); min-height: 34px; margin: 0 0 14px 0; line-height: 1.4; }
    .p-card .p-price { font-size: 30px; font-weight: 800; margin: 0 0 4px 0; }
    .p-card .p-price span { font-size: 13px; color: var(--text-secondary); font-weight: 600; }
    .p-card .p-save { font-size: 11.5px; color: #10b981; font-weight: 700; margin-bottom: 14px; min-height: 16px; }
    .p-features { list-style: none; margin: 0 0 20px 0; padding: 0; display: flex; flex-direction: column; gap: 9px; flex: 1; }
    .p-features li { display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; color: var(--text-primary); }
    .p-features .material-symbols-outlined { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
    .p-cta { display: block; text-align: center; padding: 11px; border-radius: 10px; font-weight: 700; font-size: 13.5px; text-decoration: none; cursor: pointer; border: none; }
</style>
</head>
<body>

<div class="legal-header">
    <a href="index.php" class="back"><span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Späť na <?= BRAND_SITE ?></a>
    <h1>Cenník a balíčky</h1>
    <p>Vyberte si balík podľa veľkosti vašej prevádzky. Ceny sú mesačné, kedykoľvek môžete prejsť na iný balík.</p>
</div>

<div class="pricing-wrap">

    <div class="p-card">
        <h3 style="color:#64748b;"><span class="material-symbols-outlined">eco</span> FREE</h3>
        <p class="p-desc">Základná prítomnosť a manuálna správa pre vašu prevádzku.</p>
        <p class="p-price">0,00 € <span>/ mesiac</span></p>
        <div class="p-save">&nbsp;</div>
        <ul class="p-features">
            <li><span class="material-symbols-outlined" style="color:#64748b;">check_circle</span> 150 rezervácií mesačne</li>
            <li><span class="material-symbols-outlined" style="color:#64748b;">check_circle</span> Kalendár a zoznam rezervácií</li>
            <li><span class="material-symbols-outlined" style="color:#64748b;">check_circle</span> CRM klientov (plný prístup)</li>
            <li><span class="material-symbols-outlined" style="color:#64748b;">check_circle</span> Profil prevádzky a cenník</li>
            <li><span class="material-symbols-outlined" style="color:#64748b;">check_circle</span> Potvrdzujúce e-maily rezervácií</li>
            <li><span class="material-symbols-outlined" style="color:#64748b;">auto_awesome</span> 5 AI kreditov mesačne</li>
        </ul>
        <a href="#" onclick="openAuthModal('register'); return false;" class="p-cta" style="background: var(--input-bg); color: var(--text-primary); border: 1px solid var(--border-color);">Začať zadarmo</a>
    </div>

    <div class="p-card">
        <h3 style="color:#3b82f6;"><span class="material-symbols-outlined">rocket_launch</span> ŠTART</h3>
        <p class="p-desc">Ideálny pre menšiu prevádzku a jedného špecialistu.</p>
        <p class="p-price">6,90 € <span>/ mesiac</span></p>
        <div class="p-save">Ročne 5,90 €/mesiac — ušetríte 12 €</div>
        <ul class="p-features">
            <li><span class="material-symbols-outlined" style="color:#3b82f6;">check_circle</span> 300 rezervácií mesačne</li>
            <li><span class="material-symbols-outlined" style="color:#3b82f6;">check_circle</span> Vlastné logo a sociálne siete</li>
            <li><span class="material-symbols-outlined" style="color:#3b82f6;">check_circle</span> Automatické schvaľovanie rezervácií</li>
            <li><span class="material-symbols-outlined" style="color:#3b82f6;">check_circle</span> Hodnotenia a recenzie zákazníkov</li>
            <li><span class="material-symbols-outlined" style="color:#3b82f6;">check_circle</span> Vlastná e-mailová schránka priamo v appke</li>
            <li><span class="material-symbols-outlined" style="color:#3b82f6;">auto_awesome</span> 50 AI kreditov mesačne</li>
        </ul>
        <a href="#" onclick="openAuthModal('register'); return false;" class="p-cta" style="background:#3b82f6; color:#fff;">Vybrať Štart</a>
    </div>

    <div class="p-card featured">
        <div class="p-badge"><span class="material-symbols-outlined" style="font-size:12px;vertical-align:-1px;">star</span> NAJOBĽÚBENEJŠÍ</div>
        <h3 style="color:#8b5cf6;"><span class="material-symbols-outlined">group</span> PRO</h3>
        <p class="p-desc">Pre zabehnuté prevádzky s tímom kolegov.</p>
        <p class="p-price">14,90 € <span>/ mesiac</span></p>
        <div class="p-save">Ročne 12,90 €/mesiac — ušetríte 24 €</div>
        <ul class="p-features">
            <li><span class="material-symbols-outlined" style="color:#8b5cf6;">check_circle</span> 1 500 rezervácií mesačne, 1–3 zamestnanci</li>
            <li><span class="material-symbols-outlined" style="color:#8b5cf6;">check_circle</span> Všetko zo Štart</li>
            <li><span class="material-symbols-outlined" style="color:#8b5cf6;">check_circle</span> Last Minute ponuky a titulný banner</li>
            <li><span class="material-symbols-outlined" style="color:#8b5cf6;">check_circle</span> Hromadné e-maily a novinky pre zákazníkov</li>
            <li><span class="material-symbols-outlined" style="color:#8b5cf6;">check_circle</span> QR platba zálohy, pokročilé štatistiky</li>
            <li><span class="material-symbols-outlined" style="color:#8b5cf6;">auto_awesome</span> 100 AI kreditov mesačne</li>
        </ul>
        <a href="#" onclick="openAuthModal('register'); return false;" class="p-cta" style="background: linear-gradient(135deg, #8b5cf6, #6366f1); color:#fff;">Vybrať Pro</a>
    </div>

    <div class="p-card" style="border-color: var(--primary-color);">
        <h3 style="color: var(--primary-color);"><span class="material-symbols-outlined">workspace_premium</span> VIP</h3>
        <p class="p-desc">Pre väčšie prevádzky a siete pobočiek.</p>
        <p class="p-price">29,90 € <span>/ mesiac</span></p>
        <div class="p-save">Ročne 26,90 €/mesiac — ušetríte 36 €</div>
        <ul class="p-features">
            <li><span class="material-symbols-outlined" style="color:var(--primary-color);">check_circle</span> Neobmedzené rezervácie</li>
            <li><span class="material-symbols-outlined" style="color:var(--primary-color);">check_circle</span> 10 zamestnancov a 2 pobočky v cene</li>
            <li><span class="material-symbols-outlined" style="color:var(--primary-color);">check_circle</span> Všetko z Pro + vernostný program</li>
            <li><span class="material-symbols-outlined" style="color:var(--primary-color);">check_circle</span> Exporty pre účtovníka, API integrácie</li>
            <li><span class="material-symbols-outlined" style="color:var(--primary-color);">check_circle</span> Prioritná zákaznícka podpora</li>
            <li><span class="material-symbols-outlined" style="color:var(--primary-color);">auto_awesome</span> 150 AI kreditov mesačne</li>
        </ul>
        <a href="#" onclick="openAuthModal('register'); return false;" class="p-cta" style="background: linear-gradient(135deg, #b08042, #d4af37); color:#fff;">Vybrať VIP</a>
    </div>

    <div class="p-card" style="border-color:#0f172a;">
        <h3 style="color:#0f172a;"><span class="material-symbols-outlined">corporate_fare</span> ENTERPRISE</h3>
        <p class="p-desc">Pre sieť prevádzok, franšízy a veľké organizácie s individuálnymi požiadavkami.</p>
        <p class="p-price" style="font-size:24px;">Individuálne</p>
        <div class="p-save">Cena podľa rozsahu a počtu pobočiek</div>
        <ul class="p-features">
            <li><span class="material-symbols-outlined" style="color:#0f172a;">check_circle</span> Neobmedzené rezervácie a pobočky</li>
            <li><span class="material-symbols-outlined" style="color:#0f172a;">check_circle</span> Dedikovaný account manager, SLA 24/7</li>
            <li><span class="material-symbols-outlined" style="color:#0f172a;">check_circle</span> Vlastné integrácie, API a white-label</li>
            <li><span class="material-symbols-outlined" style="color:#0f172a;">auto_awesome</span> AI kredity podľa potreby</li>
        </ul>
        <a href="mailto:info@<?= BRAND_SITE ?>?subject=Enterprise dopyt" class="p-cta" style="background:#0f172a; color:#fff; display:flex; align-items:center; justify-content:center; gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">mail</span> Kontaktujte nás</a>
    </div>

</div>

<?php include 'includes/site-footer.php'; ?>
<?php include_once 'includes/auth_modal.php'; ?>
</body>
</html>
