<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS unavailabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'other',
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table unavailabilities created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
