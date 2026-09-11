<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Aktualizácia databázy pre sekciu Prevádzka...</h3>";

    $columns = [
        "ico VARCHAR(20) DEFAULT NULL",
        "dic VARCHAR(20) DEFAULT NULL",
        "ic_dph VARCHAR(20) DEFAULT NULL",
        "is_vat_payer TINYINT(1) DEFAULT 0",
        "legal_name VARCHAR(255) DEFAULT NULL",
        "owner_name VARCHAR(255) DEFAULT NULL",
        "amenities TEXT DEFAULT NULL",
        "billing_email VARCHAR(255) DEFAULT NULL"
    ];

    foreach ($columns as $colDef) {
        $colName = explode(" ", $colDef)[0];
        try {
            $pdo->exec("ALTER TABLE establishments ADD COLUMN $colDef");
            echo "Pridaný stĺpec $colName do establishments.<br>";
        } catch (Exception $e) {
            echo "Stĺpec $colName už existuje.<br>";
        }
    }

    echo "<h3 style='color:green'>Databázová štruktúra je pripravená!</h3>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>Chyba databázy: " . $e->getMessage() . "</h3>";
}
?>
