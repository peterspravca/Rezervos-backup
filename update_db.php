<?php
require_once 'config.php';

$sql = "ALTER TABLE establishments ADD COLUMN opening_hours TEXT NULL AFTER category";
if ($conn->query($sql) === TRUE) {
    echo "Column opening_hours added successfully.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
