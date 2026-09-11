<?php
require_once 'config.php';

$sql = "ALTER TABLE establishments ADD COLUMN subscription_tier ENUM('free', 'premium') NOT NULL DEFAULT 'free' AFTER max_services";
if ($conn->query($sql) === TRUE) {
    echo "Success: $sql\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
