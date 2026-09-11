<?php
$pdo = new PDO('mysql:host=localhost;dbname=antigravity;charset=utf8', 'root', '');
$stmt = $pdo->query("SELECT id, token, type, title, doc_number, content_json FROM crm_generated_documents ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
