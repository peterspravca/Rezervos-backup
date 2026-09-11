<?php
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$pdo = db_connect();
$user = current_user();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');
$city     = trim($_POST['city'] ?? '');

// MANDATORY VALIDATION
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Meno klienta je povinné.']);
    exit;
}
if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'E-mail je povinný údaj.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Zadajte platný e-mail.']);
    exit;
}

try {
    // Basic insert
    $stmt = $pdo->prepare("INSERT INTO leads 
        (name, email, phone, address, city, status, priority, assigned_to, created_by, source, type, gender, interest) 
        VALUES (?, ?, ?, ?, ?, 'novy', 'stredna', ?, ?, 'manual', 'prospect', 'unknown', 'Iné / Všeobecné')");
    
    $stmt->execute([
        $name, $email, $phone, $address, $city, $user['id'], $user['id']
    ]);
    
    $new_id = $pdo->lastInsertId();
    
    // Add system note
    try {
        $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
            ->execute([$new_id, $user['id'], 'system', "Kontakt bol rýchlo pridaný z generátora dokumentov kolegom {$user['full_name']}."]);
    } catch(Exception $e) {}
    
    echo json_encode([
        'success' => true, 
        'id' => $new_id,
        'name' => $name
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Chyba databázy: ' . $e->getMessage()]);
}
