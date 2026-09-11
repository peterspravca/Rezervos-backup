<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8mb4");

$password = password_hash("dummy123", PASSWORD_DEFAULT);

$conn->query("CREATE TABLE IF NOT EXISTS last_minute_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    service_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2) NOT NULL,
    note VARCHAR(255),
    status ENUM('active','expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)");

$images = [
    ['cat' => 'Vlasy', 'img' => 'cat_hair_1786360997303.png', 'name' => 'Vlasové Štúdio Elegance'],
    ['cat' => 'Starostlivosť o pleť', 'img' => 'cat_skincare_1786361007973.png', 'name' => 'SkinCare Clinic'],
    ['cat' => 'Obočie a riasy', 'img' => 'cat_brows_1786361018198.png', 'name' => 'Brow Bar'],
    ['cat' => 'Make-up', 'img' => 'cat_makeup_1786361030317.png', 'name' => 'Glow Makeup Studio'],
    ['cat' => 'Nechty', 'img' => 'cat_nechty_1786361169371.png', 'name' => 'Nails & Beauty'],
    ['cat' => 'Masáž', 'img' => 'cat_masaz_1786361179277.png', 'name' => 'Zen Masáže'],
    ['cat' => 'Wellness a kúpele', 'img' => 'cat_wellness_1786361189783.png', 'name' => 'Royal Spa'],
    ['cat' => 'Vrkoče a dredy', 'img' => 'cat_vrkoce_1786361202615.png', 'name' => 'African Braids'],
    ['cat' => 'Tetovanie', 'img' => 'cat_tetovanie_1786361223970.png', 'name' => 'Ink Master Tattoo'],
    ['cat' => 'Lekárska estetika', 'img' => 'cat_estetika_1786361231782.png', 'name' => 'Aesthetic Clinic'],
    ['cat' => 'Depilácia a epilácia', 'img' => 'cat_depilacia_1786361261035.png', 'name' => 'Smooth Waxing'],
    ['cat' => 'Domáce služby', 'img' => 'cat_domace_1786361273091.png', 'name' => 'Home Beauty Care']
];

foreach ($images as $i => $item) {
    $email = "dummy{$i}@volnekreslo.sk";
    $cat = $conn->real_escape_string($item['cat']);
    $est_name = $conn->real_escape_string($item['name']);
    $img = $conn->real_escape_string($item['img']);
    $logo_path = "assets/img/" . $img;
    
    // User
    $conn->query("INSERT IGNORE INTO users (email, password_hash, role, full_name, is_verified) VALUES ('$email', '$password', 'business', '$est_name', 1)");
    $res = $conn->query("SELECT id FROM users WHERE email='$email'");
    $user_id = $res->fetch_assoc()['id'];
    
    // Establishment
    $conn->query("INSERT IGNORE INTO establishments (user_id, name, city, address, phone, image_url, status, description, category) VALUES ($user_id, '$est_name', 'Bratislava', 'Hlavná {$i}', '+42190000000{$i}', '$logo_path', 'active', 'Testovacia prevádzka', '$cat')");
    $res = $conn->query("SELECT id FROM establishments WHERE user_id=$user_id");
    $est_id = $res->fetch_assoc()['id'];
    
    // Zabezpečenie updatu ak uz exituje
    $conn->query("UPDATE establishments SET image_url='$logo_path', status='active' WHERE id=$est_id");
    
    // Service
    $price = 20 + $i;
    $conn->query("INSERT IGNORE INTO services (establishment_id, name, duration_minutes, price, description) VALUES ($est_id, '$cat Test', 60, $price, 'Testovacia služba')");
    $res = $conn->query("SELECT id FROM services WHERE establishment_id=$est_id LIMIT 1");
    $srv_id = $res->fetch_assoc()['id'];
    
    // Last Minute
    $discount = 15 + $i;
    $conn->query("INSERT IGNORE INTO last_minute_slots (establishment_id, service_id, slot_date, slot_time, original_price, discounted_price, note, status) VALUES ($est_id, $srv_id, CURDATE(), '16:00', $price, $discount, 'Test last minute', 'active')");
}

echo "Hotovo - data vytvorene!";
?>
