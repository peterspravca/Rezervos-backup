<?php
require_once 'config.php';

$queries = [
    "ALTER TABLE users ADD COLUMN public_id VARCHAR(100) NULL AFTER role",
    "ALTER TABLE users ADD COLUMN banner_url VARCHAR(255) NULL AFTER avatar_url",
    "ALTER TABLE users ADD UNIQUE INDEX (public_id)"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Success: $sql\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>
