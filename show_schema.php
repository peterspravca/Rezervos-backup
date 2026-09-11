<?php
require_once 'config.php';
$res = $conn->query("DESCRIBE users");
echo "USERS:\n";
while ($row = $res->fetch_assoc()) { echo $row['Field'] . ' - ' . $row['Type'] . "\n"; }
$res = $conn->query("DESCRIBE establishments");
echo "\nESTABLISHMENTS:\n";
while ($row = $res->fetch_assoc()) { echo $row['Field'] . ' - ' . $row['Type'] . "\n"; }
?>
