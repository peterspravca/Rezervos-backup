<?php
require_once 'config.php';
require_once __DIR__ . '/includes/cancellation_helper.php';

$token = trim($_GET['token'] ?? '');
$root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
$success = false;
$message = '';

if (empty($token)) {
    $message = 'Odkaz na potvrdenie zrušenia je neplatný. Skontrolujte prosím, či ste otvorili celý odkaz z e-mailu.';
} else {
    $result = finalizeCustomerCancellationByToken($conn, $root_url, $token);
    $success = $result['success'];
    $message = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<title>Potvrdenie zrušenia — Rezervos</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root { --primary-color: #b08042; --bg-color: #f7f5f2; --card-bg: #ffffff; --text-primary: #2b2419; --text-secondary: #6f6455; --border-color: #e6dcca; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Outfit', sans-serif; background: var(--bg-color); color: var(--text-primary); }
    .wrap { max-width: 440px; margin: 0 auto; padding: 60px 20px; text-align: center; }
    .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 32px 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    .icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px auto; font-size: 26px; }
    .icon-success { background: rgba(16,185,129,0.12); color: #10b981; }
    .icon-error { background: rgba(239,68,68,0.12); color: #ef4444; }
    h1 { font-size: 19px; margin: 0 0 10px 0; }
    p { font-size: 14px; color: var(--text-secondary); line-height: 1.55; margin: 0; }
    a.home-link { display: inline-block; margin-top: 22px; color: var(--primary-color); text-decoration: none; font-weight: 700; font-size: 13.5px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <?php if ($success): ?>
            <div class="icon icon-success">✓</div>
            <h1>Rezervácia bola zrušená</h1>
            <p><?= htmlspecialchars($message) ?></p>
        <?php else: ?>
            <div class="icon icon-error">!</div>
            <h1>Zrušenie sa nepodarilo</h1>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <a href="index.php" class="home-link">Späť na Rezervos</a>
    </div>
</div>
</body>
</html>
