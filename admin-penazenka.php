<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once 'config.php';
$pageTitle = 'Peňaženka - Administrácia';
require_once 'includes/dashboard-head.php';
?>
<?php $active_nav = 'penazenka'; $spa_host = false; require_once 'includes/admin-sidebar.php'; ?>
<div class="admin-main">
    <?php $headerTitle = 'Peňaženka'; $headerIcon = 'account_balance_wallet'; require_once 'includes/dashboard-topbar.php'; ?>
    <div class="admin-content">
        <div class="section">
            <?php $wt_inzercia_url = 'admin-inzercia.php'; $wt_hide_topup = true; require_once 'includes/wallet-topup.php'; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => { WalletTopup.load(); WalletTopup.loadShareStatus(); WalletTopup.checkStripeReturn(); });
</script>
</body>
</html>
