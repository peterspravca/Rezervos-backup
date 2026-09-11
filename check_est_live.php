<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

echo '=== ESTABLISHMENTS COLUMNS ===
';
$res = $conn->query('SHOW COLUMNS FROM establishments');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . ' (' . $row['Type'] . ')
';
    }
}

echo '
=== SAMPLE ESTABLISHMENTS ===
';
$res = $conn->query('SELECT * FROM establishments LIMIT 3');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
