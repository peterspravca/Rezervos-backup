<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = db_connect();
$lead_id = (int)($_POST['lead_id'] ?? 0);

if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Chýba Lead ID']);
    exit;
}

// Fetch the latest proforma document for this lead
$stmt = $pdo->prepare("SELECT content_json FROM crm_generated_documents 
                       WHERE lead_id = ? AND type = 'proforma' 
                       ORDER BY created_at DESC 
                       LIMIT 1");
$stmt->execute([$lead_id]);
$doc = $stmt->fetch();

if ($doc) {
    $content = json_decode($doc['content_json'], true);
    $editor_data = $content['editor_data'] ?? null;
    
    if ($editor_data) {
        echo json_encode([
            'success' => true,
            'amount' => $editor_data['custom_total'] ?? 0,
            'number' => $editor_data['number'] ?? ''
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Nenašla sa žiadna zálohová faktúra pre tohto klienta.']);
