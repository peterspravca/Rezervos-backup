<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
// $_SESSION['business_id'] is never set anywhere in this codebase — resolve the real
// establishments.id here instead (same fix as api/vouchers.php), otherwise blocks/ratings
// get stored under users.id and never match what book_appointment.php checks against.
$est_stmt0 = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
$est_stmt0->bind_param("i", $user_id);
$est_stmt0->execute();
$est_row0 = $est_stmt0->get_result()->fetch_assoc();
$businessId = $est_row0 ? (int)$est_row0['id'] : 0;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$conn->query("CREATE TABLE IF NOT EXISTS blocked_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    customer_id INT NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    blocked_until DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_block (establishment_id, customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    customer_id INT NOT NULL,
    booking_id INT DEFAULT NULL,
    description TEXT NOT NULL,
    status ENUM('new','reviewed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function esc($conn, $v) { return $conn->real_escape_string(trim($v??'')); }

if ($action === 'block_customer') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $reason = esc($conn, $_POST['reason'] ?? '');
    $duration = $_POST['duration'] ?? 'permanent'; // 'permanent' alebo počet mesiacov
    if (!$customerId) { echo json_encode(['success'=>false,'message'=>'Chýba ID zákazníka.']); exit; }

    $blockedUntil = null;
    if ($duration !== 'permanent') {
        $months = max(1, (int)$duration);
        $blockedUntil = date('Y-m-d', strtotime("+$months months"));
    }

    $sql = "INSERT INTO blocked_customers (establishment_id, customer_id, reason, blocked_until)
            VALUES ($businessId, $customerId, '$reason', " . ($blockedUntil ? "'$blockedUntil'" : "NULL") . ")
            ON DUPLICATE KEY UPDATE reason='$reason', blocked_until=" . ($blockedUntil ? "'$blockedUntil'" : "NULL") . ", created_at=NOW()";
    if ($conn->query($sql)) {
        echo json_encode(['success'=>true,'message'=>'Zákazník bol zablokovaný pre vašu prevádzku.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Chyba DB: '.$conn->error]);
    }
    exit;
}

elseif ($action === 'unblock_customer') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $conn->query("DELETE FROM blocked_customers WHERE establishment_id=$businessId AND customer_id=$customerId");
    echo json_encode(['success'=>true,'message'=>'Blokovanie bolo zrušené.']);
    exit;
}

elseif ($action === 'get_blocked_customers') {
    $res = $conn->query("SELECT bc.*, u.full_name, u.email FROM blocked_customers bc
                          JOIN users u ON u.id = bc.customer_id
                          WHERE bc.establishment_id = $businessId
                          ORDER BY bc.created_at DESC");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success'=>true,'blocked'=>$rows]);
    exit;
}

elseif ($action === 'report_incident') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $description = esc($conn, $_POST['description'] ?? '');
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if (!$customerId || !$description) { echo json_encode(['success'=>false,'message'=>'Vyplňte popis incidentu.']); exit; }

    $bookingIdSql = $bookingId ?: 'NULL';
    $ins = $conn->query("INSERT INTO incident_reports (establishment_id, customer_id, booking_id, description) VALUES ($businessId, $customerId, $bookingIdSql, '$description')");

    // Notifikácia na Rezervos support e-mail (rešpektuje TEST_EMAIL_OVERRIDE počas testovania) —
    // jednoduchý priamy mail(), nejde o zákaznícky e-mail, takže netreba PHPMailer šablónu.
    $est_stmt = $conn->query("SELECT name FROM establishments WHERE id = $businessId");
    $est_name = $est_stmt->fetch_assoc()['name'] ?? 'Prevádzka #' . $businessId;
    $cust_stmt = $conn->query("SELECT full_name, email FROM users WHERE id = $customerId");
    $cust = $cust_stmt->fetch_assoc();
    $to = defined('TEST_EMAIL_OVERRIDE') && !empty(TEST_EMAIL_OVERRIDE) ? TEST_EMAIL_OVERRIDE : 'info@rezervos.eu';
    $body = "Nové nahlásenie závažného incidentu\n\nPrevádzka: {$est_name} (ID {$businessId})\nZákazník: " . ($cust['full_name'] ?? '?') . " (" . ($cust['email'] ?? '?') . ")\n\nPopis:\n{$description}";
    @mail($to, '[Rezervos] Nahlásenie incidentu', $body);

    echo json_encode(['success'=>true,'message'=>'Incident bol nahlásený, Rezervos support ho preverí.']);
    exit;
}

else {
    echo json_encode(['success'=>false,'message'=>'Neznáma akcia.']);
}
