<?php
// ============================================================
// CRM Nástenka – Notifikačný systém (In-App)
// ============================================================
require_once __DIR__ . '/../config.php';

// Inicializácia DB tabuľky
$pdo = db_connect();
$pdo->exec("CREATE TABLE IF NOT EXISTS `crm_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'ti-bell',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `is_read_idx` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

/**
 * Vytvorí internú webovú notifikáciu v CRM.
 * Ak je $user_id = 0, pošle všetkým aktívnym používateľom (okrem voliteľného $exclude_user_id).
 */
function create_notification(int $user_id, string $title, string $message, string $link = '', string $icon = 'ti-bell', int $exclude_user_id = 0): void {
    $pdo = db_connect();
    
    if ($user_id === 0) {
        // Pošli všetkým aktívnym kolegom
        $stmt = $pdo->prepare("SELECT id FROM crm_users WHERE is_active = 1" . ($exclude_user_id > 0 ? " AND id != $exclude_user_id" : ""));
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "INSERT INTO crm_notifications (user_id, title, message, link, icon) VALUES (?, ?, ?, ?, ?)";
        $insert = $pdo->prepare($sql);
        foreach ($users as $u_id) {
            $insert->execute([$u_id, $title, $message, $link, $icon]);
        }
    } else {
        $sql = "INSERT INTO crm_notifications (user_id, title, message, link, icon) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$user_id, $title, $message, $link, $icon]);
    }
}

/**
 * Načíta najnovšie neprečítané notifikácie pre zadaného usera
 */
function get_unread_notifications(int $user_id, int $limit = 10): array {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM crm_notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Načíta aj staršie notifikácie do histórie
 */
function get_recent_notifications(int $user_id, int $limit = 20): array {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM crm_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Zistí celkový počet neprečítaných
 */
function count_unread_notifications(int $user_id): int {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM crm_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}
