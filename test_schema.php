<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
$stmt = $pdo->prepare("DESCRIBE users");
$stmt->execute();
$columns = $stmt->fetchAll();
foreach ($columns as $col) {
    echo $col['Field'] . "<br>";
}
