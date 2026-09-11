<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();
$leads = $pdo->query("SELECT * FROM leads LIMIT 5")->fetchAll();
echo "<pre>";
print_r($leads);
echo "</pre>";
