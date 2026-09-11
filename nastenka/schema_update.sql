-- ============================================================
-- CRM Nástenka - Schema Update
-- Spusti toto v phpMyAdmin alebo cez SSH na db1.usr.sk
-- ============================================================

-- 1. Pridaj CRM stĺpce do tabuľky leads
ALTER TABLE leads
    ADD COLUMN IF NOT EXISTS status ENUM('novy', 'kontaktovany', 'v_procese', 'ukonceny', 'zamietnuty') DEFAULT 'novy' AFTER message,
    ADD COLUMN IF NOT EXISTS assigned_to INT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS priority ENUM('nizka', 'stredna', 'vysoka') DEFAULT 'stredna' AFTER assigned_to,
    ADD COLUMN IF NOT EXISTS internal_note TEXT NULL AFTER priority,
    ADD COLUMN IF NOT EXISTS next_followup DATE NULL AFTER internal_note,
    ADD COLUMN IF NOT EXISTS deal_value DECIMAL(10,2) NULL AFTER next_followup,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 2. Tabuľka používateľov (max ~5, admin ich pridáva ručne)
CREATE TABLE IF NOT EXISTS crm_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabuľka poznámok / aktivít ku kontaktu
CREATE TABLE IF NOT EXISTS crm_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    user_id INT NOT NULL,
    note_type ENUM('poznamka', 'hovor', 'email', 'stretnutie', 'system') DEFAULT 'poznamka',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES crm_users(id)
);

-- 4. Tabuľka odkazov / tabule (sticky notes pre kolegu)
CREATE TABLE IF NOT EXISTS crm_board (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    color VARCHAR(20) DEFAULT '#3b82f6',
    pin_to_top TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES crm_users(id) ON DELETE CASCADE
);

-- 5. Vlož admin účet: admin / Admin1234! (ZMEŇ HESLO po prvom prihlásení!)
INSERT IGNORE INTO crm_users (username, password_hash, full_name, email, role)
VALUES (
    'admin',
    '$2y$12$eImiTXuWVxfM37uY4JANjOe5XwGGaqiELXvgVJMEXcY8QQxGAW7Wm',
    'Administrátor',
    'info@vueto.sk',
    'admin'
);

-- Heslo pre admin: Admin1234!
-- ZMEŇ SI HO na stránke po prihlásení!
