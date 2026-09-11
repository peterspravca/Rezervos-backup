<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();
$res = $pdo->query("SELECT status, COUNT(*) as c FROM leads GROUP BY status")->fetchAll();
echo "<pre>";
print_r($res);
echo "</pre>";
