<?php
// sidebar.php – Zdieľaný sidebar pre všetky dashboard-*.php stránky
// Pred includovaním nastaviť: $currentPage
if (!isset($currentPage)) $currentPage = '';
$cp = $currentPage;
$isEmployee = !empty($_SESSION['is_employee']);
$empName = $_SESSION['employee_name'] ?? '';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php';
require_once __DIR__ . '/employee_permissions_helper.php';
?>
<a href="dashboard.php" class="admin-logo" style="text-decoration:none;">
<?php if (BRAND_NAME === 'Rezervos'): ?>
    <img src="/rezervoslogodark.png" alt="Rezervos" class="rezervos-sidebar-logomark">
    <span class="logo-text rezervos-sidebar-logofull" style="font-size:20px;font-weight:800;letter-spacing:-0.5px;display:inline-flex;align-items:center;"><span style="color:#e2e8f0;">rezer</span><span style="color:#d4a050;">vos</span></span>
<?php else: ?>
    <img src="<?= BRAND_LOGO_DARK ?>" alt="<?= BRAND_NAME ?>" class="sidebar-logo">
    <span class="logo-text"><?= BRAND_LOGO_TEXT ?></span>
<?php endif; ?>
</a>

<ul class="admin-menu">

    <!-- GROUP: DENNÁ PREVÁDZKA -->
    <li><span class="menu-group-label">Denná prevádzka</span></li>

    <li><a href="dashboard.php" class="<?= $cp==='kalendar'?'active':'' ?>">
        <span class="material-symbols-outlined">calendar_today</span>
        <span class="menu-text">Kalendár</span>
    </a></li>

    <li><a href="dashboard-rezervacie.php" class="<?= $cp==='rezervacie'?'active':'' ?>">
        <span class="material-symbols-outlined">list_alt</span>
        <span class="menu-text">Zoznam rezervácií</span>
    </a></li>

    <li><a href="dashboard-kontakty.php" class="<?= $cp==='kontakty'?'active':'' ?>">
        <span class="material-symbols-outlined">contacts</span>
        <span class="menu-text">Klienti / Kontakty</span>
    </a></li>

    <li><a href="dashboard-tabula.php" class="<?= $cp==='tabula'?'active':'' ?>">
        <span class="material-symbols-outlined">view_kanban</span>
        <span class="menu-text">Tabuľa úloh</span>
    </a></li>

<?php if (employeeCan('settings')): ?>
    <li><div class="menu-divider"></div></li>

    <!-- GROUP: PREVÁDZKA -->
    <li><span class="menu-group-label">Prevádzka</span></li>

    <li><a href="dashboard-profil.php" class="<?= $cp==='profil'?'active':'' ?>">
        <span class="material-symbols-outlined">storefront</span>
        <span class="menu-text">Profil a Galéria</span>
    </a></li>

    <li><a href="dashboard-prevadzka.php" class="<?= $cp==='prevadzka'?'active':'' ?>">
        <span class="material-symbols-outlined">business_center</span>
        <span class="menu-text">Prevádzka</span>
    </a></li>

    <li><a href="dashboard-tym.php" class="<?= $cp==='tym'?'active':'' ?>">
        <span class="material-symbols-outlined">group</span>
        <span class="menu-text"><?= $isEmployee ? 'Môj rozvrh' : 'Ja / Zamestnanci' ?></span>
    </a></li>
<?php endif; ?>

<?php // Vlastný rozvrh a dovolenky smie vidieť KAŽDÝ zamestnanec, aj bez oprávnenia 'settings' — ?>
<?php if ($isEmployee && !employeeCan('settings')): ?>
    <li><div class="menu-divider"></div></li>
    <li><a href="dashboard-tym.php" class="<?= $cp==='tym'?'active':'' ?>">
        <span class="material-symbols-outlined">schedule</span>
        <span class="menu-text">Môj rozvrh</span>
    </a></li>
<?php endif; ?>

<?php if (employeeCan('settings')): ?>
    <li><a href="dashboard-cennik.php" class="<?= $cp==='cennik'?'active':'' ?>">
        <span class="material-symbols-outlined">payments</span>
        <span class="menu-text">Cenník</span>
    </a></li>

    <li><a href="dashboard-last-minute.php" class="<?= $cp==='last-minute'?'active':'' ?>">
        <span class="material-symbols-outlined">bolt</span>
        <span class="menu-text">Last Minute</span>
    </a></li>
<?php endif; ?>

<?php if (employeeCan('crm')): ?>
    <li><div class="menu-divider"></div></li>

    <!-- GROUP: RAST & MARKETING -->
    <li><span class="menu-group-label">Rast a Marketing</span></li>

    <li><a href="dashboard-marketing.php" class="<?= $cp==='marketing'?'active':'' ?>">
        <span class="material-symbols-outlined">campaign</span>
        <span class="menu-text">Marketing</span>
    </a></li>

    <li><a href="email.php" class="<?= $cp==='email'?'active':'' ?>">
        <span class="material-symbols-outlined">alternate_email</span>
        <span class="menu-text">E-mail</span>
    </a></li>

    <li><a href="dashboard-hodnotenia.php" class="<?= $cp==='hodnotenia'?'active':'' ?>">
        <span class="material-symbols-outlined">star</span>
        <span class="menu-text">Hodnotenia a Recenzie</span>
    </a></li>

    <li><a href="dashboard-inzercia.php" class="<?= $cp==='inzercia'?'active':'' ?>">
        <span class="material-symbols-outlined">newspaper</span>
        <span class="menu-text">Inzercia</span>
    </a></li>
<?php endif; ?>

<?php if (employeeCan('revenue')): ?>
    <li><div class="menu-divider"></div></li>

    <!-- GROUP: FINANCIE -->
    <li><span class="menu-group-label">Financie</span></li>

    <li><a href="dashboard-penazanka.php" class="<?= $cp==='penazanka'?'active':'' ?>">
        <span class="material-symbols-outlined">account_balance_wallet</span>
        <span class="menu-text">Peňaženka</span>
    </a></li>

    <li><a href="dashboard-balik.php" class="<?= $cp==='balik'?'active':'' ?>">
        <span class="material-symbols-outlined">diamond</span>
        <span class="menu-text">Môj Balík</span>
    </a></li>
<?php endif; ?>

    <li><div class="menu-divider"></div></li>

    <!-- GROUP: NÁSTROJE & NASTAVENIA -->
    <li><span class="menu-group-label">Nástroje a Nastavenia</span></li>

<?php if (employeeCan('settings')): ?>
    <li><a href="dashboard-rozsirenia.php" class="<?= $cp==='rozsirenia'?'active':'' ?>">
        <span class="material-symbols-outlined">extension</span>
        <span class="menu-text">Rozšírenia</span>
    </a></li>
<?php endif; ?>

<?php if (employeeCan('settings')): ?>
    <li><a href="affiliate.php" class="<?= $cp==='affiliate'?'active':'' ?>">
        <span class="material-symbols-outlined">handshake</span>
        <span class="menu-text">Affiliate program</span>
    </a></li>
<?php endif; ?>

    <li><a href="dashboard-podpora.php" class="<?= $cp==='podpora'?'active':'' ?>">
        <span class="material-symbols-outlined">help</span>
        <span class="menu-text">Podpora</span>
    </a></li>

    <li><a href="dashboard-zabezpecenie.php" class="<?= $cp==='zabezpecenie'?'active':'' ?>">
        <span class="material-symbols-outlined">shield</span>
        <span class="menu-text">Zabezpečenie</span>
    </a></li>

    <li><a href="#" onclick="openBugReportModal(); return false;">
        <span class="material-symbols-outlined">bug_report</span>
        <span class="menu-text">Nahlásiť chybu</span>
    </a></li>

</ul>

<div class="logout-container">
    <a href="logout.php" class="logout-link">
        <span class="material-symbols-outlined">logout</span>
        <span class="menu-text">Odhlásiť sa</span>
    </a>
</div>
