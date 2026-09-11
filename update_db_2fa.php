<?php
require_once 'config.php';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pridanie stĺpca pre 2-fázové overenie
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'two_factor_enabled'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0");
        echo "Stlpec two_factor_enabled bol uspesne pridany.\n";
    } else {
        echo "Stlpec two_factor_enabled uz existuje.\n";
    }

} catch (PDOException $e) {
    die("Chyba databazy: " . $e->getMessage());
}
?>
