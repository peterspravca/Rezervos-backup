<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Create user_email_settings table
    $sql1 = "CREATE TABLE IF NOT EXISTS user_email_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        imap_server VARCHAR(255) NOT NULL,
        imap_port INT DEFAULT 993,
        imap_user VARCHAR(255) NOT NULL,
        imap_pass VARCHAR(255) NOT NULL,
        smtp_server VARCHAR(255) NOT NULL,
        smtp_port INT DEFAULT 465,
        smtp_user VARCHAR(255) NOT NULL,
        smtp_pass VARCHAR(255) NOT NULL,
        encryption_iv VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY(user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql1);
    echo "Tabuľka user_email_settings bola vytvorená alebo už existuje.<br>";

    // 2. Add marketing_addon to business_profiles if exists (Wait, let's just add it to users table for simplicity)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN marketing_addon BOOLEAN DEFAULT FALSE");
        echo "Stĺpec marketing_addon bol pridaný do tabuľky users.<br>";
    } catch(PDOException $e) {
        // Ignorujeme, ak stĺpec už existuje
        if(strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Stĺpec marketing_addon už existuje.<br>";
        } else {
            throw $e;
        }
    }
    
    // 3. Create marketing_campaigns table just in case we need it
    $sql2 = "CREATE TABLE IF NOT EXISTS marketing_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        status ENUM('draft', 'sent') DEFAULT 'draft',
        recipients_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql2);
    echo "Tabuľka marketing_campaigns bola vytvorená.<br>";

    echo "<h3>Všetko prebehlo úspešne!</h3>";

} catch (PDOException $e) {
    echo "Chyba databázy: " . $e->getMessage();
}
