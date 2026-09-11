<?php
require_once 'config.php';

$token = trim($_GET['token'] ?? '');
$success = false;
$error = '';

if (empty($token)) {
    $error = 'Odkaz na potvrdenie je neplatný. Skontrolujte prosím, či ste otvorili celý odkaz z e-mailu.';
} else {
    $conn->query("CREATE TABLE IF NOT EXISTS establishment_newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        email VARCHAR(190) NOT NULL,
        confirmed TINYINT(1) NOT NULL DEFAULT 0,
        confirm_token VARCHAR(64) NOT NULL,
        unsubscribe_token VARCHAR(64) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_est_email (establishment_id, email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $conn->prepare("SELECT id FROM establishment_newsletter_subscribers WHERE confirm_token = ? AND confirm_token != ''");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $upd = $conn->prepare("UPDATE establishment_newsletter_subscribers SET confirmed = 1 WHERE id = ?");
        $upd->bind_param("i", $row['id']);
        $upd->execute();
        $success = true;
    } else {
        $error = 'Odkaz na potvrdenie je neplatný alebo už bol použitý.';
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<title>Potvrdenie odberu — Rezervos</title>
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
            <h1>Odber potvrdený</h1>
            <p>Odteraz budete dostávať novinky priamo od prevádzky. Odhlásiť sa môžete kedykoľvek cez odkaz v ktoromkoľvek e-maile.</p>
        <?php else: ?>
            <div class="icon icon-error">!</div>
            <h1>Potvrdenie sa nepodarilo</h1>
            <p><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <a href="index.php" class="home-link">Späť na Rezervos</a>
    </div>
</div>
</body>
</html>
