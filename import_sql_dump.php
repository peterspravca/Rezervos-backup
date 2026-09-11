<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);
require_once 'config.php';

$sqlFile = __DIR__ . '/locations_dump.sql';
if (!file_exists($sqlFile)) {
    die("File not found");
}

$sql = file_get_contents($sqlFile);
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "SQL dump imported successfully!";
} else {
    echo "Error importing: " . $conn->error;
}
?>
