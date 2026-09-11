<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();
$stmt = $pdo->query("DESCRIBE crm_generated_documents");
print_r($stmt->fetchAll());
