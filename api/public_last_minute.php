<?php
require_once '../config.php';
header('Content-Type: application/json');

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy.']);
    exit;
}
$conn->set_charset("utf8mb4");

// Auto-expire past slots first
$conn->query("UPDATE last_minute_slots SET status = 'expired' WHERE (slot_date < CURDATE() OR (slot_date = CURDATE() AND slot_time < CURTIME())) AND status = 'active'");

// Get all active slots with their establishment and service info
$sql = "SELECT l.*, s.name as service_name, s.duration_minutes as service_duration, e.name as establishment_name, e.city, e.address, e.id as est_id, e.image_url, u.id as user_id 
        FROM last_minute_slots l 
        JOIN services s ON l.service_id = s.id 
        JOIN establishments e ON l.establishment_id = e.id 
        JOIN users u ON e.user_id = u.id
        WHERE l.status = 'active' AND e.status = 'active'
        ORDER BY l.slot_date ASC, l.slot_time ASC";
        
$res = $conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $data]);
$conn->close();
?>
