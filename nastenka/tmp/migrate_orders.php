<?php
require_once __DIR__ . '/../auth.php';
require_login();
if (!is_admin()) die("Only admin can migrate.");

$pdo = db_connect();

try {
    // 1. Update LEADS table
    // Note: If your MySQL version < 8.0.19, 'IF NOT EXISTS' for columns might fail.
    // We use a safer way by checking the columns first if necessary, 
    // but we'll try the direct approach first as seen in index.php.

    $pdo->exec("ALTER TABLE leads 
        ADD COLUMN IF NOT EXISTS order_number VARCHAR(20) NULL AFTER id,
        ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL AFTER address,
        ADD COLUMN IF NOT EXISTS quote_price DECIMAL(10,2) NULL,
        ADD COLUMN IF NOT EXISTS deposit_amount DECIMAL(10,2) NULL,
        ADD COLUMN IF NOT EXISTS is_deposit_paid TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(50) NULL,
        ADD COLUMN IF NOT EXISTS survey_notes TEXT NULL,
        ADD COLUMN IF NOT EXISTS handover_date DATE NULL");

    // Ensure 'zakazka' is in the status ENUM
    // This is tricky with ALTER TABLE ... MODIFY. 
    // We'll just try to change the column definition.
    $pdo->exec("ALTER TABLE leads MODIFY COLUMN status ENUM('novy', 'kontaktovany', 'v_procese', 'ukonceny', 'zamietnuty', 'zakazka') DEFAULT 'novy'");

    // 2. Create crm_order_files table
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_order_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        category ENUM('survey', 'quote', 'confirmation', 'delivery_note', 'invoice', 'realization') NOT NULL,
        original_name VARCHAR(255) NULL,
        uploaded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lead (lead_id),
        INDEX idx_cat (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "Migration successful!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
