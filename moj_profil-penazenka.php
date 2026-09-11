<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['customer', 'admin'])) {
    header('Location: index.php');
    exit;
}
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_name = $stmt->get_result()->fetch_assoc()['full_name'] ?? '';

$pageTitle = 'Peňaženka - ' . BRAND_NAME;
require_once 'includes/dashboard-head.php';
?>
<?php $active_nav = 'penazenka'; $spa_host = false; require_once 'includes/customer-sidebar.php'; ?>
<div class="admin-main">
    <?php $headerTitle = 'Peňaženka'; $headerIcon = 'account_balance_wallet'; require_once 'includes/dashboard-topbar.php'; ?>
    <div class="admin-content">
        <div class="section">
            <?php require_once 'includes/wallet-topup.php'; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => { WalletTopup.load(); WalletTopup.loadShareStatus(); WalletTopup.checkStripeReturn(); });
</script>
</body>
</html>
