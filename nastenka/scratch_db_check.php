<?php
require_once __DIR__ . '/auth.php';
$pdo = db_connect();
$stmt = $pdo->query("SELECT id, type, content_json FROM crm_generated_documents ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/plain; charset=utf-8');
foreach ($rows as $row) {
    echo "ID: " . $row['id'] . " | Type: " . $row['type'] . "\n";
    $content = json_decode($row['content_json'], true);
    if (isset($content['html'])) {
        echo "HTML length: " . strlen($content['html']) . "\n";
        // Check for the strings
        $has_old_price = strpos($content['html'], 'Cena bez DPH') !== false;
        $has_old_total = strpos($content['html'], 'Celkom s DPH') !== false;
        $has_old_footer = strpos($content['html'], 'Celková suma k úhrade') !== false;
        
        echo "Has 'Cena bez DPH': " . ($has_old_price ? 'YES' : 'NO') . "\n";
        echo "Has 'Celkom s DPH': " . ($has_old_total ? 'YES' : 'NO') . "\n";
        echo "Has 'Celková suma k úhrade': " . ($has_old_footer ? 'YES' : 'NO') . "\n";
    }
    echo "-----------------------------------\n";
}
