<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();

try {
    // Modify column to add new enum values
    $pdo->exec("ALTER TABLE leads MODIFY COLUMN status ENUM('novy', 'kontaktovany', 'kontaktovany_email', 'kontaktovany_telefon', 'v_procese', 'ukonceny', 'zamietnuty', 'zakazka') DEFAULT 'novy'");
    echo "Database migrated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
unlink(__FILE__); // Autodelete
