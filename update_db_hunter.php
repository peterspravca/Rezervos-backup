<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_hunter_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            is_active TINYINT(1) DEFAULT 0,
            plan_type ENUM('monthly', 'yearly') DEFAULT 'monthly',
            expires_at DATETIME NULL,
            categories TEXT NULL,
            city VARCHAR(100) DEFAULT '',
            min_discount INT DEFAULT 10,
            notify_email TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "OK: Table user_hunter_settings created/updated successfully.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
