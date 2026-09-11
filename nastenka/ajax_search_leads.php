<?php
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

$pdo = db_connect();
$query = trim($_POST['query'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Search by name, company or email
$stmt = $pdo->prepare("SELECT id, name, company, email, city 
                       FROM leads 
                       WHERE name LIKE ? OR company LIKE ? OR email LIKE ? 
                       ORDER BY name ASC 
                       LIMIT 10");
$search = "%$query%";
$stmt->execute([$search, $search, $search]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($leads);
