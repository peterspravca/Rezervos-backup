<?php
require_once 'config.php';
$pdo = db_connect();
try {
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS utm_source VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS utm_medium VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(100) NULL");
    echo "UTM columns ensured.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
