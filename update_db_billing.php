<?php
require_once 'config.php';
header('Content-Type: application/json');

$res = [];
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Establishments columns
    $stmt = $pdo->query("SHOW COLUMNS FROM establishments");
    $est_cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $res['est_cols'] = $est_cols;

    if (!in_array('subscription_expires_at', $est_cols)) {
        $pdo->exec("ALTER TABLE establishments ADD COLUMN subscription_expires_at DATETIME DEFAULT NULL AFTER subscription_tier");
        $res['added_expires_to_est'] = true;
    }

    if (!in_array('subscription_period', $est_cols)) {
        $pdo->exec("ALTER TABLE establishments ADD COLUMN subscription_period ENUM('monthly', 'yearly') DEFAULT 'monthly' AFTER subscription_expires_at");
        $res['added_period_to_est'] = true;
    }

    // 2. Users columns
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $users_cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $res['users_cols'] = $users_cols;

    if (!in_array('subscription_expires_at', $users_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN subscription_expires_at DATETIME DEFAULT NULL AFTER subscription_tier");
        $res['added_expires_to_users'] = true;
    }

    if (!in_array('subscription_period', $users_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN subscription_period ENUM('monthly', 'yearly') DEFAULT 'monthly' AFTER subscription_expires_at");
        $res['added_period_to_users'] = true;
    }

    // 3. Set default expiration date (+30 days) for all establishments and users where tier is not 'free' and expires_at is null
    $pdo->exec("UPDATE establishments SET subscription_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE subscription_tier != 'free' AND (subscription_expires_at IS NULL OR subscription_expires_at < NOW())");
    $pdo->exec("UPDATE users SET subscription_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE subscription_tier != 'free' AND (subscription_expires_at IS NULL OR subscription_expires_at < NOW())");

    $res['success'] = true;
    $res['message'] = "Databáza bola úspešne rozšírená o stĺpce pre dátum expirácie predplatného.";
} catch (Exception $e) {
    $res['success'] = false;
    $res['error'] = $e->getMessage();
}

echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
