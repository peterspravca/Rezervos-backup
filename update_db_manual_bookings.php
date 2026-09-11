<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$queries = [
    "ALTER TABLE bookings MODIFY customer_id INT NULL",
    "ALTER TABLE bookings ADD COLUMN manual_name VARCHAR(100) NULL AFTER customer_id",
    "ALTER TABLE bookings ADD COLUMN manual_phone VARCHAR(50) NULL AFTER manual_name"
];

foreach ($queries as $sql) {
    try {
        if ($conn->query($sql)) {
            echo "OK: $sql<br>";
        } else {
            echo "Failed or exists: " . $conn->error . " ($sql)<br>";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . " ($sql)<br>";
    }
}
echo "DONE";
?>
