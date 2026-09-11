<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();
$token = '990cbffdb6257624c9f4a19364df135f';
$stmt = $pdo->prepare("SELECT id, status, approved_at, content_json FROM crm_generated_documents WHERE token = ?");
$stmt->execute([$token]);
$doc = $stmt->fetch();
echo "ID: " . $doc['id'] . "\n";
echo "Status: " . $doc['status'] . "\n";
echo "Approved At: " . $doc['approved_at'] . "\n";
if ($doc['status'] === 'approved') {
    $content = json_decode($doc['content_json'], true);
    $html = $content['html'];
    echo "Has img tag: " . (strpos($html, '<img src="data:image') !== false ? "YES" : "NO") . "\n";
    echo "Has sig_customer_display: " . (strpos($html, 'sig_customer_display') !== false ? "YES" : "NO") . "\n";
}
