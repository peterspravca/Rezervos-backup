<?php
require_once 'config.php';
$res = $conn->query("DESCRIBE users");
echo "TABLE: users\n";
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
