<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

// Zamestnanci majú business_id = ID majiteľa (uložené v SESSION pri prihlásení)
$user_id = (int)($_SESSION['business_id'] ?? $_SESSION['user_id']);
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// Povolené farebné štítky kariet (nezávislé od farby priority)
$ALLOWED_COLORS = ['#ef4444','#f97316','#f59e0b','#10b981','#06b6d4','#3b82f6','#8b5cf6','#ec4899','#64748b'];
function sanitize_task_color($val, $allowed) {
    $val = trim((string)$val);
    return in_array($val, $allowed, true) ? $val : null;
}

// Zabezpeč existenciu tabuľky
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS board_tasks (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        business_id     INT NOT NULL,
        title           VARCHAR(255) NOT NULL,
        description     TEXT,
        priority        ENUM('normalna','vysoka','urgentna') DEFAULT 'normalna',
        status          ENUM('nove','prebieha','vybavene')   DEFAULT 'nove',
        assigned_name   VARCHAR(100) DEFAULT NULL,
        due_date        DATE         DEFAULT NULL,
        is_pinned       TINYINT(1)   DEFAULT 0,
        sort_order      INT          DEFAULT 0,
        note            VARCHAR(255) DEFAULT NULL,
        watchers        TEXT         DEFAULT NULL,
        audio_path      VARCHAR(500) DEFAULT NULL,
        attachment_paths TEXT        DEFAULT NULL,
        created_by_name VARCHAR(100) DEFAULT NULL,
        created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_business (business_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

try {
    // Migrácie stĺpcov pre existujúce DB
    try { $pdo->exec("ALTER TABLE board_tasks ADD COLUMN sort_order INT DEFAULT 0 AFTER is_pinned"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE board_tasks ADD COLUMN audio_path VARCHAR(500) DEFAULT NULL AFTER watchers"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE board_tasks ADD COLUMN attachment_paths TEXT DEFAULT NULL AFTER audio_path"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE board_tasks ADD COLUMN color VARCHAR(9) DEFAULT NULL AFTER note"); } catch(Exception $e) {}

    if ($action === 'get_tasks') {
        $stmt = $pdo->prepare("SELECT * FROM board_tasks WHERE business_id = ? ORDER BY is_pinned DESC, sort_order ASC, id ASC");
        $stmt->execute([$user_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'tasks' => $tasks]);

    } elseif ($action === 'add_task') {
        $title        = trim($_POST['title']        ?? '');
        $description  = trim($_POST['description']  ?? '');
        $priority     = $_POST['priority']  ?? 'normalna';
        $status       = $_POST['status']    ?? 'nove';
        $assigned     = trim($_POST['assigned_name']   ?? '');
        $due_date     = $_POST['due_date']  ?? null;
        $note         = trim($_POST['note'] ?? '');
        $watchers     = trim($_POST['watchers'] ?? '');
        $color        = sanitize_task_color($_POST['color'] ?? '', $ALLOWED_COLORS);

        if (!in_array($priority, ['normalna','vysoka','urgentna'])) $priority = 'normalna';
        if (!in_array($status,   ['nove','prebieha','vybavene']))   $status   = 'nove';
        if (empty($title)) { echo json_encode(['success' => false, 'message' => 'Názov úlohy je povinný.']); exit; }
        if (empty($due_date)) $due_date = null;
        $is_pinned = (int)($_POST['is_pinned'] ?? 0);

        $by_name = $_SESSION['full_name'] ?? ($_SESSION['employee_name'] ?? 'Neznámy');

        // Hlasová nahrávka
        $audio_path = null;
        if (!empty($_FILES['audio']['tmp_name'])) {
            $udir = dirname(__DIR__) . '/uploads/tabula/';
            if (!is_dir($udir)) mkdir($udir, 0755, true);
            $ext   = 'webm';
            $fname = 'audio_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['audio']['tmp_name'], $udir . $fname)) {
                $audio_path = 'uploads/tabula/' . $fname;
            }
        }

        // Prílohy
        $attachment_paths_json = null;
        if (!empty($_FILES['attachments']['tmp_name'][0])) {
            $udir = dirname(__DIR__) . '/uploads/tabula/';
            if (!is_dir($udir)) mkdir($udir, 0755, true);
            $arr = [];
            foreach ($_FILES['attachments']['tmp_name'] as $i => $tmp) {
                if (empty($tmp)) continue;
                $orig  = basename($_FILES['attachments']['name'][$i]);
                $safe  = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);
                $fname = uniqid() . '_' . $safe;
                if (move_uploaded_file($tmp, $udir . $fname)) {
                    $arr[] = ['name' => $orig, 'path' => 'uploads/tabula/' . $fname];
                }
            }
            if ($arr) $attachment_paths_json = json_encode($arr, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $pdo->prepare("INSERT INTO board_tasks (business_id, title, description, priority, status, assigned_name, due_date, note, color, watchers, is_pinned, audio_path, attachment_paths, created_by_name)
                               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$user_id, $title, $description, $priority, $status, $assigned, $due_date, $note, $color, $watchers, $is_pinned, $audio_path, $attachment_paths_json, $by_name]);
        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'Odkaz pridaný.']);

    } elseif ($action === 'update_task') {
        $id           = (int)($_POST['id'] ?? 0);
        $title        = trim($_POST['title']        ?? '');
        $description  = trim($_POST['description']  ?? '');
        $priority     = $_POST['priority']  ?? 'normalna';
        $status       = $_POST['status']    ?? 'nove';
        $assigned     = trim($_POST['assigned_name']   ?? '');
        $due_date     = $_POST['due_date']  ?? null;
        $note         = trim($_POST['note'] ?? '');
        $watchers     = trim($_POST['watchers'] ?? '');
        $color        = sanitize_task_color($_POST['color'] ?? '', $ALLOWED_COLORS);

        if (!in_array($priority, ['normalna','vysoka','urgentna'])) $priority = 'normalna';
        if (!in_array($status,   ['nove','prebieha','vybavene']))   $status   = 'nove';
        if (empty($title)) { echo json_encode(['success' => false, 'message' => 'Názov úlohy je povinný.']); exit; }
        if (empty($due_date)) $due_date = null;

        $stmt = $pdo->prepare("UPDATE board_tasks SET title=?, description=?, priority=?, status=?, assigned_name=?, due_date=?, note=?, color=?, watchers=? WHERE id=? AND business_id=?");
        $stmt->execute([$title, $description, $priority, $status, $assigned, $due_date, $note, $color, $watchers, $id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Úloha aktualizovaná.']);

    } elseif ($action === 'toggle_pin') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE board_tasks SET is_pinned = NOT is_pinned WHERE id=? AND business_id=?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'set_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'nove';
        if (!in_array($status, ['nove','prebieha','vybavene'])) { echo json_encode(['success' => false]); exit; }
        $stmt = $pdo->prepare("UPDATE board_tasks SET status=? WHERE id=? AND business_id=?");
        $stmt->execute([$status, $id, $user_id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_task') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM board_tasks WHERE id=? AND business_id=?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Úloha zmazaná.']);

    } elseif ($action === 'reorder_tasks') {
        $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
        foreach ($ids as $order => $id) {
            $pdo->prepare("UPDATE board_tasks SET sort_order=? WHERE id=? AND business_id=?")
                ->execute([$order, $id, $user_id]);
        }
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy: ' . $e->getMessage()]);
}
