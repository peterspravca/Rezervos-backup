<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = new PDO("mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($action === 'get_profile') {
        $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.phone, u.whatsapp, u.avatar_url, u.banner_url, u.public_id, u.company_name, u.ico, u.dic, u.address, u.role, IFNULL(e.subscription_tier, 'free') as subscription_tier FROM users u LEFT JOIN establishments e ON u.id = e.user_id WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'profile' => $profile]);
        exit;
    }
    
    if ($action === 'update_profile') {
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $company_name = $_POST['company_name'] ?? '';
        $ico = $_POST['ico'] ?? '';
        $dic = $_POST['dic'] ?? '';
        $address = $_POST['address'] ?? '';
        
        $avatar_url = null;
        $banner_url = null;
        
        // Handle file upload avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['avatar']['tmp_name'];
            $name = basename($_FILES['avatar']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = '../uploads/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $avatar_url = 'uploads/avatars/' . $new_name;
                }
            }
        }
        
        // Handle banner upload
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['banner']['tmp_name'];
            $name = basename($_FILES['banner']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_name = 'banner_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = '../uploads/banners/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $banner_url = 'uploads/banners/' . $new_name;
                }
            }
        }

        // Build the dynamic UPDATE query based on what was uploaded
        $updates = ["full_name = ?", "phone = ?", "whatsapp = ?", "company_name = ?", "ico = ?", "dic = ?", "address = ?"];
        $params = [$full_name, $phone, $whatsapp, $company_name, $ico, $dic, $address];
        
        if ($avatar_url) {
            $updates[] = "avatar_url = ?";
            $params[] = $avatar_url;
        }
        if ($banner_url) {
            $updates[] = "banner_url = ?";
            $params[] = $banner_url;
        }
        
        $params[] = $user_id;
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Profil úspešne uložený.', 'avatar_url' => $avatar_url, 'banner_url' => $banner_url]);
        exit;
    }
    
    
    if ($action === 'simulate_payment') {
        $stmt = $pdo->prepare("UPDATE users SET card_verified = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy: ' . $e->getMessage()]);
}
?>
