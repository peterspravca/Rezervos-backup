<?php
require_once 'config.php';
header('Content-Type: application/json');

$results = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Check existing columns in services
    $stmt = $pdo->query("SHOW COLUMNS FROM services");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('business_id', $cols)) {
        $pdo->exec("ALTER TABLE services ADD COLUMN business_id INT NULL AFTER id");
        $results[] = "Added business_id to services.";
    } else {
        $results[] = "business_id already exists in services.";
    }

    if (!in_array('category_id', $cols)) {
        $pdo->exec("ALTER TABLE services ADD COLUMN category_id INT NULL AFTER business_id");
        $results[] = "Added category_id to services.";
    } else {
        $results[] = "category_id already exists in services.";
    }

    if (!in_array('order_index', $cols)) {
        $pdo->exec("ALTER TABLE services ADD COLUMN order_index INT DEFAULT 0");
        $results[] = "Added order_index to services.";
    } else {
        $results[] = "order_index already exists in services.";
    }

    // 2. Populate business_id from establishments where missing
    $pdo->exec("
        UPDATE services s 
        JOIN establishments e ON s.establishment_id = e.id 
        SET s.business_id = e.user_id 
        WHERE s.business_id IS NULL OR s.business_id = 0
    ");
    $results[] = "Updated existing services with business_id.";

    // 3. Make sure service_categories exists and has proper columns
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS service_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            business_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            order_index INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_sc_business (business_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $results[] = "Checked/created service_categories.";

    // 4. Make sure employees table exists and has avatar_url etc.
    $stmt_emp = $pdo->query("SHOW COLUMNS FROM employees");
    $emp_cols = $stmt_emp->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('avatar_url', $emp_cols)) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN avatar_url VARCHAR(255) NULL");
        $results[] = "Added avatar_url to employees.";
    }

    // 5. Make sure employee_services table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employee_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            service_id INT NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            duration_minutes INT NOT NULL DEFAULT 30,
            UNIQUE KEY unique_emp_srv (employee_id, service_id),
            KEY idx_es_srv (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $results[] = "Checked/created employee_services.";

    echo json_encode(['success' => true, 'results' => $results], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
