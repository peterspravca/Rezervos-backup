<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$conn->query("CREATE TABLE IF NOT EXISTS establishment_closures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lookup (establishment_id, date_from, date_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
$est_stmt->bind_param("i", $user_id);
$est_stmt->execute();
$est_row = $est_stmt->get_result()->fetch_assoc();
$establishment_id = (int)($est_row['id'] ?? 0);

if ($establishment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Prevádzka sa nenašla.']);
    exit;
}

if ($action === 'get_closures') {
    $stmt = $conn->prepare("SELECT * FROM establishment_closures WHERE establishment_id = ? ORDER BY date_from DESC");
    $stmt->bind_param("i", $establishment_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

elseif ($action === 'add_closure') {
    $date_from = trim($_POST['date_from'] ?? '');
    $date_to = trim($_POST['date_to'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if (empty($date_from) || empty($date_to)) {
        echo json_encode(['success' => false, 'message' => 'Vyplňte dátumy.']);
        exit;
    }
    if ($date_to < $date_from) {
        echo json_encode(['success' => false, 'message' => 'Dátum konca musí byť po dátume začiatku.']);
        exit;
    }

    $ins = $conn->prepare("INSERT INTO establishment_closures (establishment_id, date_from, date_to, reason) VALUES (?, ?, ?, ?)");
    $ins->bind_param("isss", $establishment_id, $date_from, $date_to, $reason);
    if ($ins->execute()) {
        echo json_encode(['success' => true, 'message' => 'Zatvorenie bolo pridané.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nepodarilo sa pridať, skúste to prosím znova.']);
    }
    exit;
}

elseif ($action === 'delete_closure') {
    $id = (int)($_POST['id'] ?? 0);
    $del = $conn->prepare("DELETE FROM establishment_closures WHERE id = ? AND establishment_id = ?");
    $del->bind_param("ii", $id, $establishment_id);
    $del->execute();
    echo json_encode(['success' => true]);
    exit;
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
