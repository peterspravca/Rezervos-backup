<?php
// ============================================================
// CRM Nástenka – Private Messenger API
// ============================================================
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');
$pdo  = db_connect();
$user = current_user();

// ── Lazy Table Creation ──
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_private_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        message TEXT NOT NULL,
        is_safe TINYINT(1) DEFAULT 0,
        password_hash VARCHAR(255) NULL,
        max_views INT DEFAULT 1,
        view_count INT DEFAULT 0,
        expires_at DATETIME NULL,
        is_read TINYINT(1) DEFAULT 0,
        read_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_users (sender_id, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Auto-migrate: Add file_path if missing
    $cols = $pdo->query("DESCRIBE crm_private_messages")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('file_path', $cols)) {
        $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN file_path VARCHAR(255) NULL AFTER message");
    }
    
    // Auto-migrate columns if missing
    try { $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL AFTER is_safe"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS max_views INT DEFAULT 1 AFTER password_hash"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER max_views"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE crm_private_messages ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER view_count"); } catch(Exception $e){}
} catch (PDOException $e) {}

$action = $_GET['action'] ?? '';

// 1. Fetch conversations / contacts list
if ($action === 'fetch_contacts') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, 
        (SELECT COUNT(*) FROM crm_private_messages WHERE recipient_id = ? AND sender_id = u.id AND is_read = 0) as unread_count
        FROM crm_users u 
        WHERE u.id != ? AND u.is_active = 1 AND u.full_name NOT LIKE '%Administrátor%'
        ORDER BY u.full_name ASC
    ");
    $stmt->execute([$user['id'], $user['id']]);
    echo json_encode(['contacts' => $stmt->fetchAll()]);
    exit;
}

// 2. Fetch messages with a specific contact
if ($action === 'fetch_messages') {
    $contact_id = (int)($_GET['contact_id'] ?? 0);
    if (!$contact_id) die(json_encode(['error' => 'Missing contact_id']));

    // Mark non-safe messages as read when opening a conversation
    $pdo->prepare("UPDATE crm_private_messages SET is_read = 1, read_at = NOW() 
                   WHERE recipient_id = ? AND sender_id = ? AND is_read = 0 AND is_safe = 0")
        ->execute([$user['id'], $contact_id]);

    // Fetch messages
    $stmt = $pdo->prepare("
        SELECT * FROM crm_private_messages 
        WHERE (sender_id = ? AND recipient_id = ?) 
           OR (sender_id = ? AND recipient_id = ?)
        ORDER BY created_at ASC 
        LIMIT 100
    ");
    $stmt->execute([$user['id'], $contact_id, $contact_id, $user['id']]);
    $messages = $stmt->fetchAll();

    $processed = [];
    foreach ($messages as $msg) {
        // If the current user is receiving a message they haven't "viewed" yet
        // If the current user is receiving a message they haven't "viewed" yet
        if ($msg['recipient_id'] === $user['id'] && !$msg['is_read']) {
            // Recipient-side check
            if ($msg['is_safe']) {
                $has_pwd = !empty($msg['password_hash']);
                $unlock_attempt = $_GET['pwd_unlock'] ?? '';

                if ($has_pwd) {
                    if (empty($unlock_attempt)) {
                        $msg['message'] = "[LOCKED]";
                        $msg['is_locked'] = true;
                    } else if (!password_verify($unlock_attempt, $msg['password_hash'])) {
                        $msg['message'] = "[INCORRECT PASSWORD]";
                        $msg['is_locked'] = true;
                    } else {
                        // Correct password!
                        $pdo->prepare("UPDATE crm_private_messages SET view_count = view_count + 1, is_read = 1, read_at = NOW() WHERE id = ?")
                            ->execute([$msg['id']]);
                    }
                } else {
                    // SafeNote WITHOUT password - Requires explicit "REVEAL" to avoid accidental view consumption
                    if ($unlock_attempt !== 'REVEAL') {
                        $msg['message'] = "[REVEAL_REQUIRED]";
                        $msg['is_revealahle'] = true;
                    } else {
                        // Explicitly revealed!
                        $pdo->prepare("UPDATE crm_private_messages SET view_count = view_count + 1, is_read = 1, read_at = NOW() WHERE id = ?")
                            ->execute([$msg['id']]);
                    }
                }
            }
        }

        // SENDER-SIDE: Can ALWAYS see their own sent messages
        if ($msg['sender_id'] === $user['id']) {
            $msg['is_locked'] = false;
        }

        // Logic for self-destruction after reaching max views
        if ($msg['is_safe'] && $msg['view_count'] >= $msg['max_views']) {
            // Delete physical file if exists
            if (!empty($msg['file_path'])) {
                $fpath = __DIR__ . '/' . $msg['file_path'];
                if (file_exists($fpath)) unlink($fpath);
            }
            // Delete it so it's gone from DB
            $pdo->prepare("DELETE FROM crm_private_messages WHERE id = ?")->execute([$msg['id']]);
        }
        
        $processed[] = $msg;
    }

    echo json_encode(['messages' => $processed]);
    exit;
}

// 3. Send message
if ($action === 'send') {
    csrf_check();
    $recipients   = $_POST['recipient_ids'] ?? []; // Supports array for multi-select
    if (is_string($recipients)) $recipients = explode(',', $recipients);
    
    $content      = trim($_POST['message'] ?? '');
    $is_safe      = (int)($_POST['is_safe'] ?? 0);
    $max_views    = (int)($_POST['max_views'] ?? 1);
    $password     = trim($_POST['password'] ?? '');
    $pwd_hash     = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null;

    // Validate and handle file upload
    $file_path = null;
    if (!empty($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
        $up_dir = __DIR__ . '/uploads/private/';
        if (!is_dir($up_dir)) mkdir($up_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['chat_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','zip','txt'];
        if (in_array($ext, $allowed)) {
            $f_name = 'msg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $up_dir . $f_name)) {
                $file_path = 'uploads/private/' . $f_name;
            }
        }
    }

    if (empty($content) && empty($file_path)) {
        die(json_encode(['error' => 'Empty message']));
    }

    foreach ($recipients as $rid) {
        $rid = (int)$rid;
        if (!$rid) continue;
        $stmt = $pdo->prepare("INSERT INTO crm_private_messages 
            (sender_id, recipient_id, message, file_path, is_safe, password_hash, max_views) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $rid, $content, $file_path, $is_safe, $pwd_hash, $max_views]);
    }

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
