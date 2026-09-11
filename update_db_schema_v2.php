<?php
/**
 * Skript pre aktualizáciu databázovej schémy podľa nového biznis plánu.
 */
// Ak existuje config, použijeme ho
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    die("Nenájdený súbor s pripojením do DB (config.php)");
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3>Začínam aktualizáciu databázy...</h3>";

    // 1. Úpravy tabuľky users (Zákazníci)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) DEFAULT NULL");
        echo "Pridaný stĺpec avatar_path do users.<br>";
    } catch (Exception $e) { echo "Stĺpec avatar_path možno už existuje.<br>"; }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN card_verified TINYINT(1) DEFAULT 0");
        echo "Pridaný stĺpec card_verified do users.<br>";
    } catch (Exception $e) { echo "Stĺpec card_verified možno už existuje.<br>"; }

    // 2. Úpravy tabuľky establishments (Prevádzky)
    $establishment_columns = [
        "subscription_tier ENUM('free', 'start', 'pro', 'vip') DEFAULT 'free'",
        "deposit_iban VARCHAR(50) DEFAULT NULL",
        "confirmation_mode ENUM('manual', 'auto') DEFAULT 'manual'",
        "social_ig VARCHAR(255) DEFAULT NULL",
        "social_fb VARCHAR(255) DEFAULT NULL",
        "social_web VARCHAR(255) DEFAULT NULL",
        "custom_url VARCHAR(100) DEFAULT NULL",
        "banner_url VARCHAR(255) DEFAULT NULL"
    ];

    foreach ($establishment_columns as $colDef) {
        $colName = explode(" ", $colDef)[0];
        try {
            $pdo->exec("ALTER TABLE establishments ADD COLUMN $colDef");
            echo "Pridaný stĺpec $colName do establishments.<br>";
        } catch (Exception $e) {}
    }

    // 3. Nová tabuľka employees (Zamestnanci)
    $sql_employees = "
    CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        avatar_url VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql_employees);
    echo "Tabuľka employees skontrolovaná/vytvorená.<br>";

    // 4. Prepojenie zamestnancov s rezerváciami
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN employee_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_booking_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL");
        echo "Pridaný stĺpec employee_id do bookings.<br>";
    } catch (Exception $e) {}

    echo "<h3 style='color:green'>Aktualizácia databázy prebehla úspešne!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Kritická chyba pri aktualizácii: " . $e->getMessage() . "</h3>";
}
?>
