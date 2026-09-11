<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();

try {
    $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL AFTER is_safe");
    $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS max_views INT DEFAULT 1 AFTER password_hash");
    $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER max_views");
    $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER view_count");
    echo "Table crm_private_messages updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage() . "\n";
}
