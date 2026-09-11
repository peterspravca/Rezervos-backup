<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('crm');
$pageTitle = 'Inzercia - ' . BRAND_NAME;
$currentPage = 'inzercia';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Inzercia'; $headerIcon = 'newspaper'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">
      <script>const INZ_WALLET_URL = 'dashboard-penazanka.php';</script>
      <?php require_once 'includes/inzercia-manager.php'; ?>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => InzManager.init());
</script>
</body>
</html>
