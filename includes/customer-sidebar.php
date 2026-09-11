<?php
// includes/customer-sidebar.php — Zdieľaný bočný panel pre moj_profil.php a moj_profil-inzercia.php
// Vyžaduje: $user_name (zobrazené meno používateľa)
// Voliteľné premenné pred includom:
//   $active_nav — id položky menu, ktorá má byť zvýraznená ako aktívna (napr. 'dashboard', 'inzercia')
//   $spa_host   — true na moj_profil.php (SPA prepínanie sekcií cez showSection()), false na samostatných
//                 stránkach (kde showSection() neexistuje, takže položky vedú späť na moj_profil.php)
$active_nav = $active_nav ?? 'dashboard';
$spa_host   = $spa_host ?? true;
$user_name  = $user_name ?? '';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php';
?>
<aside class="admin-sidebar" id="sidebar">
    <a href="index.php" style="text-decoration:none;" class="admin-logo">
    <?php if (BRAND_NAME === 'Rezervos'): ?>
        <img src="/rezervoslogodark.png" alt="Rezervos" class="rezervos-sidebar-logomark">
        <span class="logo-text rezervos-sidebar-logofull" style="font-size:20px;font-weight:800;letter-spacing:-0.5px;display:inline-flex;align-items:center;"><span style="color:#e2e8f0;">rezer</span><span style="color:#d4a050;">vos</span></span>
    <?php else: ?>
        <img src="<?= BRAND_LOGO_DARK ?>" alt="<?= BRAND_NAME ?>" class="sidebar-logo">
        <span class="logo-text"><?= BRAND_LOGO_TEXT ?></span>
    <?php endif; ?>
    </a>
    <ul class="admin-menu">
        <li><a <?= $spa_host ? "onclick=\"showSection('dashboard')\"" : 'href="moj_profil.php"' ?> id="nav-dashboard" class="<?= $active_nav==='dashboard'?'active':'' ?>"><span class="material-symbols-outlined">dashboard</span> <span class="menu-text">Prehľad</span></a></li>
        <?php if ($_SESSION['user_role'] === 'customer'): ?>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Moje rezervácie</span></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('bookings')\"" : 'href="moj_profil.php?section=bookings"' ?> id="nav-bookings" class="<?= $active_nav==='bookings'?'active':'' ?>"><span class="material-symbols-outlined">event</span> <span class="menu-text">Moje termíny</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('memberships')\"" : 'href="moj_profil.php?section=memberships"' ?> id="nav-memberships" class="<?= $active_nav==='memberships'?'active':'' ?>"><span class="material-symbols-outlined">card_membership</span> <span class="menu-text">Moje permanentky</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('giftvouchers')\"" : 'href="moj_profil.php?section=giftvouchers"' ?> id="nav-giftvouchers" class="<?= $active_nav==='giftvouchers'?'active':'' ?>"><span class="material-symbols-outlined">card_giftcard</span> <span class="menu-text">Darčekové poukazy</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('loyalty')\"" : 'href="moj_profil.php?section=loyalty"' ?> id="nav-loyalty" class="<?= $active_nav==='loyalty'?'active':'' ?>"><span class="material-symbols-outlined">loyalty</span> <span class="menu-text">Vernostný program</span></a></li>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Objavuj</span></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('newsletters')\"" : 'href="moj_profil.php?section=newsletters"' ?> id="nav-newsletters" class="<?= $active_nav==='newsletters'?'active':'' ?>"><span class="material-symbols-outlined">campaign</span> <span class="menu-text">Novinky od prevádzok</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('hunter')\"" : 'href="moj_profil.php?section=hunter"' ?> id="nav-hunter" style="color: #f59e0b;" class="<?= $active_nav==='hunter'?'active':'' ?>"><span class="material-symbols-outlined" style="color: #f59e0b;">radar</span> <span class="menu-text">Kreslo Hunter</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('favorites')\"" : 'href="moj_profil.php?section=favorites"' ?> id="nav-favorites" class="<?= $active_nav==='favorites'?'active':'' ?>"><span class="material-symbols-outlined">favorite</span> <span class="menu-text">Obľúbené</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('reviews')\"" : 'href="moj_profil.php?section=reviews"' ?> id="nav-reviews" class="<?= $active_nav==='reviews'?'active':'' ?>"><span class="material-symbols-outlined">star</span> <span class="menu-text">Moje recenzie</span></a></li>
        <?php endif; ?>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Financie a nástroje</span></li>
        <li><a href="moj_profil-inzercia.php" id="nav-inzercia" class="<?= $active_nav==='inzercia'?'active':'' ?>"><span class="material-symbols-outlined">newspaper</span> <span class="menu-text">Moje inzeráty</span></a></li>
        <li><a href="moj_profil-penazenka.php" id="nav-penazenka" class="<?= $active_nav==='penazenka'?'active':'' ?>"><span class="material-symbols-outlined">account_balance_wallet</span> <span class="menu-text">Peňaženka</span></a></li>
        <li><a href="affiliate.php" id="nav-affiliate" class="<?= $active_nav==='affiliate'?'active':'' ?>"><span class="material-symbols-outlined">handshake</span> <span class="menu-text">Affiliate program</span></a></li>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Nastavenia</span></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('settings')\"" : 'href="moj_profil.php?section=settings"' ?> id="nav-settings" class="<?= $active_nav==='settings'?'active':'' ?>"><span class="material-symbols-outlined">person</span> <span class="menu-text">Nastavenia profilu</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('security')\"" : 'href="moj_profil.php?section=security"' ?> id="nav-security" class="<?= $active_nav==='security'?'active':'' ?>"><span class="material-symbols-outlined">security</span> <span class="menu-text">Zabezpečenie</span></a></li>
    </ul>
    <div class="logout-container">
        <a href="logout.php"><span class="material-symbols-outlined">logout</span> <span class="menu-text">Odhlásiť sa</span></a>
    </div>
</aside>
