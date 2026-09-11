<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();
try {
    $stmt = $pdo->query("DESCRIBE leads");
    $cols = $stmt->fetchAll();
    echo "COLUMNS IN leads:\n";
    foreach ($cols as $c) {
        echo "- " . $c['Field'] . " (" . $c['Type'] . ") " . ($c['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
