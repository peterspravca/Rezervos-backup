<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Limits helper
function getGalleryLimits($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT u.subscription_tier as u_tier, e.subscription_tier as e_tier
                           FROM users u
                           LEFT JOIN establishments e ON u.id = e.user_id
                           WHERE u.id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $tierOrder = ['free' => 0, 'start' => 1, 'pro' => 2, 'vip' => 3];
    $uTier = strtolower($row['u_tier'] ?? 'free');
    $eTier = strtolower($row['e_tier'] ?? 'free');
    $tier = strtoupper((($tierOrder[$uTier] ?? 0) >= ($tierOrder[$eTier] ?? 0)) ? $uTier : $eTier);

    $limits = [
        'FREE' => 0,
        'START' => 3,
        'PRO' => 12,
        'VIP' => 9999 // Unlimited
    ];
    return $limits[$tier] ?? 0;
}
try {
    if ($action === 'get_gallery') {
        $stmt = $pdo->prepare("SELECT * FROM business_gallery WHERE business_id = ? ORDER BY order_index ASC, id DESC");
        $stmt->execute([$user_id]);
        $gallery = $stmt->fetchAll();
        
        $limit = getGalleryLimits($pdo, $user_id);
        
        echo json_encode([
            'success' => true, 
            'gallery' => $gallery,
            'limit' => $limit
        ]);
    }
    elseif ($action === 'upload_media') {
        $limit = getGalleryLimits($pdo, $user_id);
        if ($limit === 0) {
            echo json_encode(['success' => false, 'message' => 'Váš balík neumožňuje nahrávať fotky.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM business_gallery WHERE business_id = ?");
        $stmt->execute([$user_id]);
        $current_count = $stmt->fetchColumn();

        if ($current_count >= $limit) {
            echo json_encode(['success' => false, 'message' => 'Dosiahli ste limit galérie pre Váš balík.', 'limit_reached' => true]);
            exit;
        }

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $file_type = $_FILES['file']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Nepovolený formát súboru.']);
                exit;
            }

            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $new_name = 'gallery_' . $user_id . '_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            
            // Create dir if not exists
            $upload_dir = '../uploads/gallery/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $target_file = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $db_url = 'uploads/gallery/' . $new_name;
                $stmt = $pdo->prepare("INSERT INTO business_gallery (business_id, media_type, media_url) VALUES (?, 'image', ?)");
                $stmt->execute([$user_id, $db_url]);
                
                echo json_encode(['success' => true, 'message' => 'Fotka úspešne nahratá.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Chyba pri ukladaní súboru na server.']);
            }
        } 
        elseif (isset($_POST['video_url']) && !empty($_POST['video_url'])) {
            // Check if VIP to allow video
            $stmt = $pdo->prepare("SELECT IFNULL(e.subscription_tier, IFNULL(u.subscription_tier, 'free')) as subscription_tier 
                                   FROM users u 
                                   LEFT JOIN establishments e ON u.id = e.user_id 
                                   WHERE u.id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (strtoupper($user['subscription_tier'] ?? '') !== 'VIP') {
                echo json_encode(['success' => false, 'message' => 'Video galéria je prístupná iba pre VIP balík.']);
                exit;
            }

            $video_url = trim($_POST['video_url']);
            $stmt = $pdo->prepare("INSERT INTO business_gallery (business_id, media_type, media_url) VALUES (?, 'video', ?)");
            $stmt->execute([$user_id, $video_url]);
            
            echo json_encode(['success' => true, 'message' => 'Video úspešne pridané.']);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Nebol vybraný žiadny súbor.']);
        }
    }
    elseif ($action === 'reorder') {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (!is_array($order) || empty($order)) {
            echo json_encode(['success' => false, 'message' => 'Neplatné poradie.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE business_gallery SET order_index = ? WHERE id = ? AND business_id = ?");
        foreach ($order as $position => $itemId) {
            $stmt->execute([$position, (int)$itemId, $user_id]);
        }
        echo json_encode(['success' => true, 'message' => 'Poradie bolo uložené.']);
    }
    elseif ($action === 'delete_media') {
        $id = (int)($_POST['id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT media_url FROM business_gallery WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $user_id]);
        $media = $stmt->fetch();
        
        if ($media) {
            // Delete file if it's an image
            if (strpos($media['media_url'], 'uploads/gallery/') !== false) {
                $file_path = '../' . $media['media_url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM business_gallery WHERE id = ? AND business_id = ?");
            $stmt->execute([$id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Položka odstránená.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Položka nenájdená.']);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy: ' . $e->getMessage()]);
}
