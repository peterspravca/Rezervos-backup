<?php
require_once 'config.php';
$pdo = db_connect();
try {
    $stmt = $pdo->query("DESCRIBE leads");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in leads table:\n";
    print_r($cols);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
