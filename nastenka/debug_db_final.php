<?php
require_once __DIR__ . '/config.php';
try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $out = "LOG start\n";
    
    // Test the status modify
    try {
        $pdo->exec("ALTER TABLE leads MODIFY COLUMN status ENUM('novy', 'kontaktovany', 'kontaktovany_email', 'kontaktovany_telefon', 'kontaktovany_oboje', 'v_procese', 'ukonceny', 'zamietnuty', 'zakazka') DEFAULT 'novy'");
        $out .= "ALTER TABLE success\n";
    } catch (Exception $e) {
        $out .= "ALTER TABLE failed: " . $e->getMessage() . "\n";
    }
    
    $leads = $pdo->query("SELECT id, name, status FROM leads WHERE name LIKE '%Mäsiar%'")->fetchAll();
    $out .= "Masiar count: " . count($leads) . "\n";
    foreach($leads as $l) {
        $out .= "ID: {$l['id']}, Name: {$l['name']}, Status: [{$l['status']}]\n";
    }
    
    file_put_contents('scratch/debug_output.txt', $out);
    echo "Done. Check scratch/debug_output.txt";
} catch (Exception $e) {
    file_put_contents('scratch/debug_output.txt', "Global Error: " . $e->getMessage());
}
?>
