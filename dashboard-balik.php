<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('revenue');
$pageTitle = 'Môj Balík - ' . BRAND_NAME;
$currentPage = 'balik';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Môj Balík a Platby'; $headerIcon = 'diamond'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">

<?php include 'components/billing.php'; ?>

  </div>
</div>

<script>
// Utility + notifikácie sú v /assets/js/dashboard-common.js

document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.body.classList.contains('dark-mode');
  const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
  init();
});

function init() {
  // Load current subscription info from API
  loadSubscriptionData();
  const params = new URLSearchParams(window.location.search);
  if (params.get('stripe') === 'success') {
    showAppToast('Platba prijatá, aktivujeme balík...', 'success');
    // Webhook od Stripe zvyčajne príde do pár sekúnd — pár krátkych re-fetchov namiesto jedného okamžitého
    setTimeout(loadSubscriptionData, 2000);
    setTimeout(loadSubscriptionData, 5000);
    window.history.replaceState({}, '', window.location.pathname);
  } else if (params.get('stripe') === 'cancel') {
    showAppToast('Platba bola zrušená.', 'error');
    window.history.replaceState({}, '', window.location.pathname);
  }
}

async function loadSubscriptionData() {
  try {
    const fd = new FormData(); fd.append('action', 'get_profile');
    const res = await fetch('api/business.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success && data.profile) {
      updateBillingUI(
        data.profile.subscription_tier,
        data.profile.subscription_expires_at,
        data.profile.subscription_period
      );
    }
  } catch(e) { console.error('Chyba načítania balíka:', e); }
}
</script>
</body>
</html>
