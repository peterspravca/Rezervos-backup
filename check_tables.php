<?php
require_once 'config.php';
header('Content-Type: application/json');
$res = [];
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    foreach (['services', 'service_categories', 'employees', 'employee_services', 'establishments'] as $tbl) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $tbl");
            $res[$tbl] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $ex) {
            $res[$tbl] = 'ERROR: ' . $ex->getMessage();
        }
    }
} catch (Exception $e) {
    $res['error'] = $e->getMessage();
}
echo json_encode($res, JSON_PRETTY_PRINT);
