<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS last_minute_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    service_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2) NOT NULL,
    note VARCHAR(255),
    status ENUM('active', 'expired', 'booked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table last_minute_slots created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
