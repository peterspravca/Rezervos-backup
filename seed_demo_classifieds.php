<?php
// seed_demo_classifieds.php — jednorazovo vloží 3 fiktívne inzeráty pre náhľad sekcie
// "Najnovšie inzeráty" na domovskej stránke. Pred spustením naostro TREBA zmazať
// (viz [[project_prelaunch_checklist]] — rovnaké pravidlo ako pri fiktívnych prevádzkach/zákazníkoch).
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

$conn->query("CREATE TABLE IF NOT EXISTS classifieds (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(32) NOT NULL DEFAULT 'work',
    listing_type VARCHAR(32) DEFAULT NULL,
    title VARCHAR(255) NOT NULL DEFAULT '',
    specialization VARCHAR(128) DEFAULT NULL,
    description TEXT,
    location VARCHAR(128) DEFAULT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    email VARCHAR(128) DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT NULL,
    `condition` VARCHAR(32) DEFAULT NULL,
    price_unit VARCHAR(32) DEFAULT NULL,
    available_from DATE DEFAULT NULL,
    images TEXT DEFAULT NULL,
    boosted_until DATETIME DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type), INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Prvý admin/business účet v users — len na vyplnenie povinného user_id, demo dáta nie sú viazané na nikoho konkrétneho
$owner = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch_assoc();
$user_id = $owner ? (int)$owner['id'] : 1;

$demo = [
    [
        'type' => 'sale',
        'title' => 'Kadernícke kreslo — takmer nové',
        'description' => 'Predávam hydraulické kadernícke kreslo, používané len 3 mesiace, výborný stav.',
        'location' => 'Bratislava',
        'price' => 180.00,
        'condition' => 'použité',
        'images' => json_encode(['assets/img/cat_hair.png']),
    ],
    [
        'type' => 'work',
        'listing_type' => 'seek',
        'title' => 'Hľadám prácu — manikérka',
        'description' => 'Skúsená manikérka hľadá miesto na živnosť alebo prenájom kresla v salóne v Bratislave.',
        'location' => 'Bratislava',
        'images' => json_encode(['assets/img/cat_nails.png']),
    ],
    [
        'type' => 'rental',
        'title' => 'Prenájom kresla v salóne — centrum Košíc',
        'description' => 'Voľné kreslo na prenájom v zabehnutom salóne, ideálne pre kaderníka alebo barbera.',
        'location' => 'Košice',
        'price' => 25.00,
        'price_unit' => 'deň',
        'images' => json_encode(['assets/img/barber_shop.png']),
    ],
];

$stmt = $conn->prepare("INSERT INTO classifieds (user_id, type, listing_type, title, description, location, price, `condition`, price_unit, images, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
$inserted = 0;
foreach ($demo as $d) {
    $listing_type = $d['listing_type'] ?? null;
    $price = $d['price'] ?? null;
    $condition = $d['condition'] ?? null;
    $price_unit = $d['price_unit'] ?? null;
    $stmt->bind_param(
        "isssssdsss",
        $user_id, $d['type'], $listing_type, $d['title'], $d['description'],
        $d['location'], $price, $condition, $price_unit, $d['images']
    );
    if ($stmt->execute()) { $inserted++; }
}

echo "Vložených fiktívnych inzerátov: $inserted\n";
