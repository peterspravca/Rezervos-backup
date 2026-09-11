<?php
require 'config.php';
$pdo = db_connect();
$stmt = $pdo->query('SELECT id, username, full_name, role FROM crm_users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
