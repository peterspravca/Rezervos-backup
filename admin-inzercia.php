<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once 'config.php';
$pageTitle = 'Moje inzeráty - Administrácia';
require_once 'includes/dashboard-head.php';
?>
<?php $active_nav = 'moje-inzeraty'; $spa_host = false; require_once 'includes/admin-sidebar.php'; ?>
<div class="admin-main">
    <?php $headerTitle = 'Moje inzeráty'; $headerIcon = 'newspaper'; require_once 'includes/dashboard-topbar.php'; ?>
    <div class="admin-content">
        <div class="section">
            <script>const INZ_WALLET_URL = 'admin-penazenka.php';</script>
            <?php require_once 'includes/inzercia-manager.php'; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => InzManager.init());
</script>
</body>
</html>
