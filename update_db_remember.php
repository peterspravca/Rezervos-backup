<?php
require_once 'config.php';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($conn->query("ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL") === TRUE) {
    echo "Column added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
$conn->close();
?>
