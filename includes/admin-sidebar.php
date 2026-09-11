<?php
// includes/admin-sidebar.php — Zdieľaný bočný panel pre admin.php a admin-inzercia.php
// Voliteľné premenné pred includom:
//   $active_nav — id položky menu, ktorá má byť zvýraznená ako aktívna (napr. 'dashboard', 'moje-inzeraty')
//   $spa_host   — true na admin.php (SPA prepínanie sekcií cez showSection()), false na samostatných stránkach
//                 (kde showSection() neexistuje, takže položky vedú späť na admin.php)
$active_nav = $active_nav ?? 'dashboard';
$spa_host   = $spa_host ?? true;
if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php';
?>
<aside class="admin-sidebar">
    <div class="admin-logo" onclick="window.location.href='prevadzky.php'" style="cursor: pointer;" title="Späť na prevádzky">
    <?php if (BRAND_NAME === 'Rezervos'): ?>
        <img src="/rezervoslogodark.png" alt="Rezervos" class="rezervos-sidebar-logomark">
        <span class="logo-text rezervos-sidebar-logofull" style="font-size:20px;font-weight:800;letter-spacing:-0.5px;display:inline-flex;align-items:center;"><span style="color:#e2e8f0;">rezer</span><span style="color:#d4a050;">vos</span></span>
    <?php else: ?>
        <img src="<?= BRAND_LOGO_DARK ?>" alt="<?= BRAND_NAME ?>" class="sidebar-logo">
        <span class="logo-text"><?= BRAND_LOGO_TEXT ?></span>
    <?php endif; ?>
    </div>
    <ul class="admin-menu">
        <li><a <?= $spa_host ? "onclick=\"showSection('dashboard')\"" : 'href="admin.php?section=dashboard"' ?> id="nav-dashboard" class="<?= $active_nav==='dashboard'?'active':'' ?>"><span class="material-symbols-outlined">dashboard</span> <span class="menu-text">Prehľad</span></a></li>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Správa</span></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('users')\"" : 'href="admin.php?section=users"' ?> id="nav-users" class="<?= $active_nav==='users'?'active':'' ?>"><span class="material-symbols-outlined">group</span> <span class="menu-text">Používatelia</span></a></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('businesses')\"" : 'href="admin.php?section=businesses"' ?> id="nav-businesses" class="<?= $active_nav==='businesses'?'active':'' ?>"><span class="material-symbols-outlined">storefront</span> <span class="menu-text">Prevádzky</span></a></li>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Inzercia</span></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('inzercia')\"" : 'href="admin.php?section=inzercia"' ?> id="nav-inzercia" class="<?= $active_nav==='inzercia'?'active':'' ?>"><span class="material-symbols-outlined">newspaper</span> <span class="menu-text">Správa inzerátov</span></a></li>
        <li><a href="admin-inzercia.php" id="nav-moje-inzeraty" class="<?= $active_nav==='moje-inzeraty'?'active':'' ?>"><span class="material-symbols-outlined">campaign</span> <span class="menu-text">Moje inzeráty</span></a></li>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Financie a rast</span></li>
        <li><a href="admin-penazenka.php" id="nav-penazenka" class="<?= $active_nav==='penazenka'?'active':'' ?>"><span class="material-symbols-outlined">account_balance_wallet</span> <span class="menu-text">Peňaženka</span></a></li>
        <li><a href="admin-promo-kody.php" id="nav-promo-kody" class="<?= $active_nav==='promo-kody'?'active':'' ?>"><span class="material-symbols-outlined">redeem</span> <span class="menu-text">Promo kódy</span></a></li>
        <li><a href="admin-affiliate.php" id="nav-affiliate" class="<?= $active_nav==='affiliate'?'active':'' ?>"><span class="material-symbols-outlined">handshake</span> <span class="menu-text">Affiliate</span></a></li>

        <li><div class="menu-divider"></div></li>
        <li><span class="menu-group-label">Nastavenia</span></li>
        <li><a <?= $spa_host ? "onclick=\"showSection('security')\"" : 'href="admin.php?section=security"' ?> id="nav-security" class="<?= $active_nav==='security'?'active':'' ?>"><span class="material-symbols-outlined">security</span> <span class="menu-text">Zabezpečenie</span></a></li>
    </ul>
    <div class="logout-container">
        <a href="logout.php"><span class="material-symbols-outlined">logout</span> <span class="menu-text">Odhlásiť sa</span></a>
    </div>
</aside>
