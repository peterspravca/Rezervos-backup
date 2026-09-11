<?php
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$pdo = db_connect();
$user = current_user();

$description = trim($_POST['description'] ?? '');
$url         = trim($_POST['url'] ?? '');
$user_agent  = trim($_POST['user_agent'] ?? '');
$screenshot_path = null;

if (!$description) {
    echo json_encode(['success' => false, 'error' => 'Chýba popis chyby.']);
    exit;
}

// Handle screenshot upload
if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/bugs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
    $filename = 'bug_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $target)) {
        $screenshot_path = 'uploads/bugs/' . $filename;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO crm_bug_reports (user_id, description, url, user_agent, screenshot_path) VALUES (?,?,?,?,?)");
    $stmt->execute([$user['id'], $description, $url, $user_agent, $screenshot_path]);
    
    // Create notifications for all admins
    $admins = $pdo->query("SELECT id FROM crm_users WHERE role = 'admin' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $admin_id) {
        $msg = "Nahlásená nová chyba od používateľa " . $user['full_name'];
        create_notification($admin_id, 'Zistená chyba', $msg, 'admin.php?tab=bugs');
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
