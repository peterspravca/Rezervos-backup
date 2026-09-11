<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
$stmt = $pdo->prepare("SELECT id, email, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll();
foreach ($users as $u) {
    echo $u['email'] . " -> " . $u['role'] . "<br>";
}
