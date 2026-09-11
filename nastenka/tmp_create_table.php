<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();

$sql = "CREATE TABLE IF NOT EXISTS crm_private_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    message TEXT NOT NULL,
    is_safe TINYINT(1) DEFAULT 0,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES crm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES crm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "Table crm_private_messages created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
