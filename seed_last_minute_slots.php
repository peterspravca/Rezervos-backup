<?php
// seed_last_minute_slots.php
require_once 'config.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 1. Create table last_minute_slots if not exists
$conn->query("CREATE TABLE IF NOT EXISTS last_minute_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    service_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time VARCHAR(10) NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2) NOT NULL,
    note VARCHAR(255) NULL,
    status ENUM('active', 'booked', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// establishments.status has no self-migration anywhere else in the codebase — add it
// defensively here so this script doesn't fatal on a fresh database that never got it.
$conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS status ENUM('active','pending','blocked') NOT NULL DEFAULT 'active'");

// 2. Fetch some existing establishments and services
$res = $conn->query("SELECT e.id as est_id, s.id as srv_id, s.price
                     FROM establishments e
                     JOIN services s ON s.establishment_id = e.id
                     WHERE e.status = 'active'
                     LIMIT 8");

$tomorrow = date('Y-m-d', strtotime('+1 day'));
$in_two_days = date('Y-m-d', strtotime('+2 days'));

while ($row = $res->fetch_assoc()) {
    $est_id = (int)$row['est_id'];
    $srv_id = (int)$row['srv_id'];
    $orig_price = (float)$row['price'] ?: 30.00;
    $disc_price = round($orig_price * 0.75, 2); // 25% discount
    
    // Check if slot already exists
    $chk = $conn->query("SELECT id FROM last_minute_slots WHERE establishment_id = $est_id AND service_id = $srv_id AND status = 'active'");
    if ($chk->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO last_minute_slots (establishment_id, service_id, slot_date, slot_time, original_price, discounted_price, note, status) VALUES (?, ?, ?, '14:00', ?, ?, 'Last minute zľava 25%', 'active')");
        $stmt->bind_param("iisdd", $est_id, $srv_id, $tomorrow, $orig_price, $disc_price);
        $stmt->execute();
        echo "Added last minute slot for est_id $est_id\n";
    }
}

echo "Last minute slots seeded successfully.\n";
$conn->close();
?>
