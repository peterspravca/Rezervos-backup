<?php
/**
 * Skript pre aktualizáciu databázovej schémy - Fáza 4 a 5 (Hodnotenia, Zákazníci, Zálohy)
 */
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    die("Nenájdený súbor s pripojením do DB (config.php)");
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3>Začínam aktualizáciu databázy pre Fázu 4 a 5...</h3>";

    // 1. Tabuľka hodnotení (reviews)
    $sql_reviews = "
    CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        reviewer_type ENUM('customer', 'establishment') NOT NULL,
        reviewer_id INT NOT NULL,
        reviewee_type ENUM('customer', 'establishment') NOT NULL,
        reviewee_id INT NOT NULL,
        rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_reviews);
    echo "Tabuľka 'reviews' skontrolovaná/vytvorená.<br>";

    // Pridáme status zálohy do tabuľky bookings
    try {
        // alter table to modify status enum or add a new status if it's varchar
        // Zistíme typ stĺpca status
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'status'");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($col) {
            if (strpos($col['Type'], 'enum') !== false) {
                // Musíme rozšíriť enum
                $pdo->exec("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'pending_deposit') DEFAULT 'pending'");
                echo "Stĺpec 'status' v tabuľke 'bookings' bol rozšírený o 'pending_deposit'.<br>";
            } else {
                echo "Stĺpec 'status' v tabuľke 'bookings' nie je ENUM, pravdepodobne VARCHAR.<br>";
            }
        }
    } catch (Exception $e) { 
        echo "Chyba pri rozširovaní statusu bookings: " . $e->getMessage() . "<br>";
    }

    echo "<h3 style='color:green'>Aktualizácia databázy prebehla úspešne!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Kritická chyba pri aktualizácii: " . $e->getMessage() . "</h3>";
}
?>
