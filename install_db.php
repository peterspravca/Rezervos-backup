<?php
// Inštalačný skript pre databázu
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

echo "<h2>Inštalácia databázy " . htmlspecialchars(BRAND_NAME) . "</h2>";

// config.php má definované $db_host, $db_user, $db_pass, $db_name
// ale $conn je zatiaľ zakomentované. Vytvoríme pripojenie na účely inštalácie.

$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("<p style='color:red;'>Chyba pripojenia k MySQL: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green;'>Úspešne pripojené k databázovému serveru.</p>";

// Vytvorenie databázy ak neexistuje
$sql_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql_db) === TRUE) {
    echo "<p>Databáza `$db_name` skontrolovaná/vytvorená.</p>";
} else {
    echo "<p style='color:red;'>Chyba pri vytváraní databázy: " . $conn->error . "</p>";
}

$conn->select_db($db_name);

// Načítanie a vykonanie SQL súboru
$sqlFile = 'init_db.sql';
if (!file_exists($sqlFile)) {
    die("<p style='color:red;'>Súbor init_db.sql sa nenašiel!</p>");
}

$sqlContent = file_get_contents($sqlFile);
if (empty(trim($sqlContent))) {
    die("<p style='color:red;'>Súbor init_db.sql je prázdny!</p>");
}

// Rozdelenie dotazov podľa ';'
$queries = explode(';', $sqlContent);
$success = true;

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!$conn->query($query)) {
            echo "<p style='color:red;'>Chyba pri vykonávaní SQL dotazu:<br><code>$query</code><br>Chyba: " . $conn->error . "</p>";
            $success = false;
        }
    }
}

if ($success) {
    echo "<p style='color:green;'><strong>Všetky tabuľky boli úspešne vytvorené a dáta vložené!</strong></p>";
    echo "<p>Zabezpečenie: Teraz môžeš tento súbor (install_db.php) zmazať alebo premenovať, aby sa nespustil znova.</p>";
}

$conn->close();
?>
