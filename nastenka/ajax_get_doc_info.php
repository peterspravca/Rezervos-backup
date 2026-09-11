<?php
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

$pdo = db_connect();

$lead_id = (int)($_GET['lead_id'] ?? 0);
$type    = $_GET['type'] ?? '';
$year    = date('Y');

if (!$type) {
    echo json_encode(['success' => false, 'error' => 'Missing document type']);
    exit;
}

try {
    // 1. Get next number
    // We search for numbers starting with the current year
    $stmt = $pdo->prepare("SELECT doc_number FROM crm_generated_documents WHERE type = ? AND doc_number LIKE ? ORDER BY doc_number DESC LIMIT 1");
    $stmt->execute([$type, $year . '%']);
    $last_number = $stmt->fetchColumn();

    if ($last_number) {
        // Extract the numeric part (everything after the year)
        $num = (int)substr($last_number, 4);
        $next_number = $year . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $next_number = $year . '001';
    }

    // 2. Check for existing documents for this lead
    $stmt = $pdo->prepare("
        SELECT d.doc_number, d.created_at, u.full_name as author 
        FROM crm_generated_documents d
        JOIN crm_notes n ON n.content LIKE CONCAT('%', d.token, '%') AND n.lead_id = d.lead_id
        JOIN crm_users u ON n.user_id = u.id
        WHERE d.lead_id = ? AND d.type = ? 
        ORDER BY d.created_at DESC
    ");
    // Note: The author join is a bit tricky because we don't store user_id in crm_generated_documents directly.
    // However, save_document.php adds a note. We can try to find the user from the note.
    // Better: let's just use the latest note creator for that document type if possible.
    
    // Simpler check for now:
    $stmt = $pdo->prepare("SELECT doc_number, created_at, status, token FROM crm_generated_documents WHERE lead_id = ? AND type = ? ORDER BY created_at DESC");
    $stmt->execute([$lead_id, $type]);
    $existing = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'next_number' => $next_number,
        'already_exists' => count($existing) > 0,
        'existing_docs' => $existing
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
