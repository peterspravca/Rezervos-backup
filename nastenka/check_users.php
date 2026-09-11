<?php
require_once __DIR__ . '/config.php';
$pdo = new PDO("mysql:host=db1.usr.sk;dbname=vueto;charset=utf8mb4", "vueto.sk", "KHfgSjbar(819nNE");
$users = $pdo->query("SELECT id, username, full_name, email, role, is_active FROM crm_users")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users, JSON_PRETTY_PRINT);
