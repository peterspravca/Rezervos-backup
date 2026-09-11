<?php
session_start();
require_once '../config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';
require_once __DIR__ . '/../includes/employee_permissions_helper.php';

header('Content-Type: application/json');

// Overenie roly
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

// Odporúčací kód a uplatnenie promo kódu menia predplatné/zľavy MAJITEĽA — zamestnanec bez
// oprávnenia 'settings' k nim nesmie mať prístup (predtým tu chýbala akákoľvek kontrola).
if (in_array($_POST['action'] ?? $_GET['action'] ?? '', ['get_or_create_referral_code', 'redeem_promo_code'], true) && !employeeCan('settings')) {
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnenie na túto akciu.']);
    exit;
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy.']);
    exit;
}
$conn->set_charset("utf8mb4");

require_once 'auto_ratings_helper.php';
process48HourAutoRatings($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = (int)$_SESSION['user_id'];

if ($action === 'get_profile') {
    $stmt = $conn->prepare("SELECT e.*, u.avatar_path as avatar_url, u.public_id as public_id, u.subscription_tier as u_tier, u.subscription_expires_at as u_expires_at, u.subscription_period as u_period FROM establishments e JOIN users u ON e.user_id = u.id WHERE e.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        // Vždy použi tier z users tabuľky (autoritatívny zdroj) — establishments moze byt nesynced
        $tierOrder = ['free' => 0, 'start' => 1, 'pro' => 2, 'vip' => 3];
        $uTier = $row['u_tier'] ?? 'free';
        $eTier = $row['subscription_tier'] ?? 'free';
        $bestTier = (($tierOrder[$uTier] ?? 0) >= ($tierOrder[$eTier] ?? 0)) ? $uTier : $eTier;
        $row['subscription_tier'] = $bestTier ?: 'free';
        if ($bestTier === $uTier) {
            $row['subscription_expires_at'] = $row['u_expires_at'];
            $row['subscription_period'] = $row['u_period'];
        }
        // If subscription is paid but expires_at is null, initialize with 30 days
        if ($row['subscription_tier'] && $row['subscription_tier'] !== 'free' && empty($row['subscription_expires_at'])) {
            $default_exp = date('Y-m-d H:i:s', strtotime('+30 days'));
            $row['subscription_expires_at'] = $default_exp;
            $conn->query("UPDATE establishments SET subscription_expires_at = '$default_exp' WHERE id = " . intval($row['id']));
            $conn->query("UPDATE users SET subscription_expires_at = '$default_exp' WHERE id = $user_id");
        }
        echo json_encode(['success' => true, 'profile' => $row]);
    } else {
        echo json_encode(['success' => true, 'profile' => null]);
    }
    $stmt->close();
}
elseif ($action === 'change_tier') {
    $tier = strtolower(trim($_POST['tier'] ?? 'free'));
    $period = strtolower(trim($_POST['period'] ?? 'monthly'));
    if (!in_array($tier, ['free', 'start', 'pro', 'vip'])) {
        $tier = 'free';
    }
    if (!in_array($period, ['monthly', 'yearly'])) {
        $period = 'monthly';
    }

    if ($tier === 'free') {
        $stmt1 = $conn->prepare("UPDATE establishments SET subscription_tier = 'free', subscription_expires_at = NULL, subscription_period = 'monthly' WHERE user_id = ?");
        $stmt1->bind_param("i", $user_id);
        $stmt1->execute();

        $stmt2 = $conn->prepare("UPDATE users SET subscription_tier = 'free', subscription_expires_at = NULL, subscription_period = 'monthly' WHERE id = ?");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Váš balík bol úspešne zmenený na FREE.',
            'tier' => 'free',
            'expires_at' => null,
            'period' => 'monthly'
        ]);
        exit;
    } else {
        // Platené balíky sa už neaktivujú priamo tu — táto vetva kedysi priznala balík len na základe
        // čísla poslaného klientom, bez overenia platby (dalo sa obísť napr. cez devtools/curl).
        // Skutočná aktivácia teraz prebieha vo webhooku api/stripe_webhook.php až po potvrdenej
        // platbe zo Stripe. Klient si musí vyžiadať Checkout Session cez api/stripe_checkout.php.
        echo json_encode([
            'success' => false,
            'message' => 'Platené balíky sa aktivujú cez platobnú bránu. Skúste to znova z tejto stránky.',
            'requires_checkout' => true
        ]);
        exit;
    }
}
elseif ($action === 'get_or_create_referral_code') {
    require_once __DIR__ . '/../includes/promo_code_helper.php';
    global $pdo;
    promo_migrate($pdo);

    // Ak už má vlastný odporúčací kód, vrátime ten istý namiesto vytvárania duplicity
    $existing = $pdo->prepare("SELECT code FROM promo_codes WHERE owner_user_id = ? LIMIT 1");
    $existing->execute([$user_id]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode(['success' => true, 'code' => $row['code']]);
        exit;
    }

    // Nový osobný odporúčací kód: kto ho uplatní dostane 15% zľavu na doplnky, vlastníkovi
    // sa za každé uplatnenie pripíše +14 dní k jeho vlastnému balíku.
    do {
        $code = 'KOLEGA' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $dupChk = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ?");
        $dupChk->execute([$code]);
    } while ($dupChk->fetch());

    $ins = $pdo->prepare("INSERT INTO promo_codes (code, type, value, owner_user_id, owner_reward_type, owner_reward_value, note) VALUES (?, 'discount_percent', 15, ?, 'free_days', 14, 'Osobný odporúčací kód')");
    $ins->execute([$code, $user_id]);

    echo json_encode(['success' => true, 'code' => $code]);
    exit;
}
elseif ($action === 'redeem_promo_code') {
    require_once __DIR__ . '/../includes/promo_code_helper.php';
    // Sekcie zobrazené na verejnej stránke (konfigurátor viditeľnosti)
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_gallery TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_videos TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_gift_vouchers TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_packages TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_newsletter TINYINT(1) NOT NULL DEFAULT 0");
    $show_gallery = !empty($_POST['show_gallery']) ? 1 : 0;
    $show_videos = !empty($_POST['show_videos']) ? 1 : 0;
    $show_gift_vouchers = !empty($_POST['show_gift_vouchers']) ? 1 : 0;
    $show_packages = !empty($_POST['show_packages']) ? 1 : 0;
    $show_newsletter = (!empty($_POST['show_newsletter']) && in_array($currentTierChk, ['pro', 'vip'], true)) ? 1 : 0;
    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_row = $est_stmt->get_result()->fetch_assoc();
    $est_id = $est_row ? (int)$est_row['id'] : 0;

    $result = redeemPromoCode($user_id, trim($_POST['code'] ?? ''), $est_id);
    echo json_encode($result);
    exit;
}
elseif ($action === 'upload_banner') {
    if (!isset($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Nebol vybraný žiadny súbor alebo nastala chyba pri nahrávaní.']);
        exit;
    }
    $tmp_name = $_FILES['banner']['tmp_name'];
    $orig_name = basename($_FILES['banner']['name']);
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        echo json_encode(['success' => false, 'message' => 'Povolené formáty sú JPG, PNG a WEBP.']);
        exit;
    }
    if ($_FILES['banner']['size'] > 6 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Maximálna povolená veľkosť bannera je 6 MB.']);
        exit;
    }

    $new_name = 'bn_' . $user_id . '_' . time() . '.' . $ext;
    if (!is_dir('../uploads/banners')) mkdir('../uploads/banners', 0755, true);
    if (move_uploaded_file($tmp_name, '../uploads/banners/' . $new_name)) {
        $banner_url = 'uploads/banners/' . $new_name;
        $upd = $conn->prepare("UPDATE establishments SET banner_url = ? WHERE user_id = ?");
        $upd->bind_param("si", $banner_url, $user_id);
        $upd->execute();
        echo json_encode(['success' => true, 'banner_url' => $banner_url, 'message' => 'Titulný banner bol úspešne nahraný a uložený!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nepodarilo sa uložiť súbor na server.']);
    }
    exit;
}
elseif ($action === 'upload_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Nebol vybraný žiadny súbor alebo nastala chyba pri nahrávaní.']);
        exit;
    }
    $tmp_name = $_FILES['avatar']['tmp_name'];
    $orig_name = basename($_FILES['avatar']['name']);
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        echo json_encode(['success' => false, 'message' => 'Povolené formáty sú JPG, PNG a WEBP.']);
        exit;
    }
    if ($_FILES['avatar']['size'] > 6 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Maximálna povolená veľkosť loga je 6 MB.']);
        exit;
    }

    $new_name = 'av_' . $user_id . '_' . time() . '.' . $ext;
    if (!is_dir('../uploads/avatars')) mkdir('../uploads/avatars', 0755, true);
    if (move_uploaded_file($tmp_name, '../uploads/avatars/' . $new_name)) {
        $avatar_url = 'uploads/avatars/' . $new_name;
        $upd = $conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
        $upd->bind_param("si", $avatar_url, $user_id);
        $upd->execute();
        echo json_encode(['success' => true, 'avatar_url' => $avatar_url, 'message' => 'Logo salónu bolo úspešne nahrané a uložené!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nepodarilo sa uložiť súbor na server.']);
    }
    exit;
}
elseif ($action === 'reset_banner') {
    $upd = $conn->prepare("UPDATE establishments SET banner_url = NULL WHERE user_id = ?");
    $upd->bind_param("i", $user_id);
    $upd->execute();
    echo json_encode(['success' => true, 'message' => 'Banner bol resetovaný na predvolený podľa kategórie.']);
    exit;
}
elseif ($action === 'save_profile') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $opening_hours = trim($_POST['opening_hours'] ?? '');
    
    // Nové polia
    $confirmation_mode = trim($_POST['confirmation_mode'] ?? 'manual');
    $custom_url = trim($_POST['custom_url'] ?? '');
    $deposit_iban = trim($_POST['deposit_iban'] ?? '');
    $social_ig = trim($_POST['social_ig'] ?? '');
    $social_fb = trim($_POST['social_fb'] ?? '');
    $social_ws = trim($_POST['social_ws'] ?? '');
    $social_web = trim($_POST['social_web'] ?? '');
    $social_tiktok = trim($_POST['social_tiktok'] ?? '');
    $social_youtube = trim($_POST['social_youtube'] ?? '');
    $social_telegram = trim($_POST['social_telegram'] ?? '');
    $social_x = trim($_POST['social_x'] ?? '');
    $social_linkedin = trim($_POST['social_linkedin'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $video_url_1 = trim($_POST['video_url_1'] ?? '');
    $video_url_2 = trim($_POST['video_url_2'] ?? '');

    if (empty($name) || empty($address) || empty($city) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Vyplňte povinné polia (Názov, Adresa, Mesto, Kategória).']);
        exit;
    }

    // Server-side kontrola balika a validacia Vlastnej URL (frontend disabled atribut sa da obist priamym requestom)
    $tierCheckStmt = $conn->prepare("SELECT e.custom_url as current_custom_url, u.subscription_tier as u_tier, e.subscription_tier as e_tier FROM users u LEFT JOIN establishments e ON e.user_id = u.id WHERE u.id = ?");
    $tierCheckStmt->bind_param("i", $user_id);
    $tierCheckStmt->execute();
    $tierRow = $tierCheckStmt->get_result()->fetch_assoc();
    $tierOrderChk = ['free' => 0, 'start' => 1, 'pro' => 2, 'vip' => 3];
    $uTierChk = $tierRow['u_tier'] ?? 'free';
    $eTierChk = $tierRow['e_tier'] ?? 'free';
    $currentTierChk = (($tierOrderChk[$uTierChk] ?? 0) >= ($tierOrderChk[$eTierChk] ?? 0)) ? $uTierChk : $eTierChk;
    $currentCustomUrl = $tierRow['current_custom_url'] ?? '';

    // Sekcie zobrazené na verejnej stránke (konfigurátor viditeľnosti) — formulár v Nástenke tieto
    // hodnoty vždy posiela, ale save_profile ich môže dostať volaním aj bez nich (napr. sprievodca
    // nastavením), preto sa musia definovať priamo tu a nespoliehať sa na inú akciu.
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_gallery TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_videos TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_gift_vouchers TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_packages TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS show_newsletter TINYINT(1) NOT NULL DEFAULT 0");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS social_tiktok VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS social_youtube VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS social_telegram VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS social_x VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS social_linkedin VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS contact_email VARCHAR(180) DEFAULT NULL");
    $show_gallery = !empty($_POST['show_gallery']) ? 1 : 0;
    $show_videos = !empty($_POST['show_videos']) ? 1 : 0;
    $show_gift_vouchers = !empty($_POST['show_gift_vouchers']) ? 1 : 0;
    $show_packages = !empty($_POST['show_packages']) ? 1 : 0;
    $show_newsletter = (!empty($_POST['show_newsletter']) && in_array($currentTierChk, ['pro', 'vip'], true)) ? 1 : 0;

    if ($custom_url !== '' && $custom_url !== $currentCustomUrl) {
        if (!in_array($currentTierChk, ['pro', 'vip'], true)) {
            echo json_encode(['success' => false, 'message' => 'Vlastná URL adresa je k dispozícii od balíka PRO.']);
            exit;
        }
        $custom_url = strtolower($custom_url);
        if (!preg_match('/^[a-z0-9\-]{3,40}$/', $custom_url)) {
            echo json_encode(['success' => false, 'message' => 'Vlastná URL môže obsahovať iba malé písmená, čísla a pomlčky (3-40 znakov).']);
            exit;
        }
        $dupCheckStmt = $conn->prepare("SELECT id FROM establishments WHERE custom_url = ? AND user_id != ? LIMIT 1");
        $dupCheckStmt->bind_param("si", $custom_url, $user_id);
        $dupCheckStmt->execute();
        if ($dupCheckStmt->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Táto vlastná URL adresa je už obsadená, zvoľte inú.']);
            exit;
        }
    }

    $avatar_url = null;
    $banner_url = null;
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['avatar']['tmp_name'];
        $orig_name = basename($_FILES['avatar']['name']);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $new_name = 'av_' . $user_id . '_' . time() . '.' . $ext;
            if (!is_dir('../uploads/avatars')) mkdir('../uploads/avatars', 0755, true);
            if (move_uploaded_file($tmp_name, '../uploads/avatars/' . $new_name)) {
                $avatar_url = 'uploads/avatars/' . $new_name;
                // Uložíme avatar_path aj do users
                $upd_user = $conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
                $upd_user->bind_param("si", $avatar_url, $user_id);
                $upd_user->execute();
            }
        }
    }

    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['banner']['tmp_name'];
        $orig_name = basename($_FILES['banner']['name']);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $new_name = 'bn_' . $user_id . '_' . time() . '.' . $ext;
            if (!is_dir('../uploads/banners')) mkdir('../uploads/banners', 0755, true);
            if (move_uploaded_file($tmp_name, '../uploads/banners/' . $new_name)) {
                $banner_url = 'uploads/banners/' . $new_name;
            }
        }
    }

    // Načítame existujúce video polia pre zachovanie hodnôt
    $stmt_exist = $conn->prepare("SELECT video_file_1, video_file_2, video_url_1, video_url_2 FROM establishments WHERE user_id = ?");
    $stmt_exist->bind_param("i", $user_id);
    $stmt_exist->execute();
    $exist_row = $stmt_exist->get_result()->fetch_assoc();
    $cur_vfile_1 = $exist_row['video_file_1'] ?? '';
    $cur_vfile_2 = $exist_row['video_file_2'] ?? '';
    $cur_vyt_1 = $exist_row['video_url_1'] ?? '';
    $cur_vyt_2 = $exist_row['video_url_2'] ?? '';

    $video_file_1 = $cur_vfile_1;
    $video_file_2 = $cur_vfile_2;
    $video_url_1 = trim($_POST['video_url_1'] ?? '');
    $video_url_2 = trim($_POST['video_url_2'] ?? '');

    // Spracovanie Priamych Video Súborov (Video 1 a 2)
    if (isset($_POST['remove_vidfile_1']) && $_POST['remove_vidfile_1'] === '1') {
        $video_file_1 = '';
    } elseif (isset($_FILES['video_file_1']) && $_FILES['video_file_1']['error'] === UPLOAD_ERR_OK) {
        $tmp_v1 = $_FILES['video_file_1']['tmp_name'];
        $orig_v1 = basename($_FILES['video_file_1']['name']);
        $ext_v1 = strtolower(pathinfo($orig_v1, PATHINFO_EXTENSION));
        if (in_array($ext_v1, ['mp4', 'webm', 'mov', 'm4v', 'avi'])) {
            $new_v1 = 'vidfile1_' . $user_id . '_' . time() . '.' . $ext_v1;
            if (!is_dir('../uploads/videos')) mkdir('../uploads/videos', 0755, true);
            if (move_uploaded_file($tmp_v1, '../uploads/videos/' . $new_v1)) {
                $video_file_1 = 'uploads/videos/' . $new_v1;
            }
        }
    }

    if (isset($_POST['remove_vidfile_2']) && $_POST['remove_vidfile_2'] === '1') {
        $video_file_2 = '';
    } elseif (isset($_FILES['video_file_2']) && $_FILES['video_file_2']['error'] === UPLOAD_ERR_OK) {
        $tmp_v2 = $_FILES['video_file_2']['tmp_name'];
        $orig_v2 = basename($_FILES['video_file_2']['name']);
        $ext_v2 = strtolower(pathinfo($orig_v2, PATHINFO_EXTENSION));
        if (in_array($ext_v2, ['mp4', 'webm', 'mov', 'm4v', 'avi'])) {
            $new_v2 = 'vidfile2_' . $user_id . '_' . time() . '.' . $ext_v2;
            if (!is_dir('../uploads/videos')) mkdir('../uploads/videos', 0755, true);
            if (move_uploaded_file($tmp_v2, '../uploads/videos/' . $new_v2)) {
                $video_file_2 = 'uploads/videos/' . $new_v2;
            }
        }
    }

    // Spracovanie YouTube Videa (1 a 2)
    if (isset($_POST['remove_vidyt_1']) && $_POST['remove_vidyt_1'] === '1') {
        $video_url_1 = '';
    }
    if (isset($_POST['remove_vidyt_2']) && $_POST['remove_vidyt_2'] === '1') {
        $video_url_2 = '';
    }

    $stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $updates = ["name=?", "description=?", "address=?", "city=?", "phone=?", "category=?", "opening_hours=?", "confirmation_mode=?", "custom_url=?", "deposit_iban=?", "social_ig=?", "social_fb=?", "social_ws=?", "social_web=?", "social_tiktok=?", "social_youtube=?", "social_telegram=?", "social_x=?", "social_linkedin=?", "contact_email=?", "video_file_1=?", "video_file_2=?", "video_url_1=?", "video_url_2=?", "show_gallery=?", "show_videos=?", "show_gift_vouchers=?", "show_packages=?", "show_newsletter=?"];
        $params = [$name, $description, $address, $city, $phone, $category, $opening_hours, $confirmation_mode, $custom_url, $deposit_iban, $social_ig, $social_fb, $social_ws, $social_web, $social_tiktok, $social_youtube, $social_telegram, $social_x, $social_linkedin, $contact_email, $video_file_1, $video_file_2, $video_url_1, $video_url_2, $show_gallery, $show_videos, $show_gift_vouchers, $show_packages, $show_newsletter];
        
        if ($banner_url) {
            $updates[] = "banner_url=?";
            $params[] = $banner_url;
        }
        
        $params[] = $user_id;
        
        $types = str_repeat('s', count($params) - 1) . 'i';
        $sql = "UPDATE establishments SET " . implode(", ", $updates) . " WHERE user_id=?";
        
        $update = $conn->prepare($sql);
        $update->execute($params);
    } else {
        $insert = $conn->prepare("INSERT INTO establishments (user_id, name, description, address, city, phone, category, opening_hours, confirmation_mode, custom_url, deposit_iban, social_ig, social_fb, social_ws, social_web, social_tiktok, social_youtube, social_telegram, social_x, social_linkedin, contact_email, video_file_1, video_file_2, video_url_1, video_url_2, banner_url, show_gallery, show_videos, show_gift_vouchers, show_packages, show_newsletter, status, max_services) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 10)");
        $insert->bind_param("isssssssssssssssssssssssssiiiii", $user_id, $name, $description, $address, $city, $phone, $category, $opening_hours, $confirmation_mode, $custom_url, $deposit_iban, $social_ig, $social_fb, $social_ws, $social_web, $social_tiktok, $social_youtube, $social_telegram, $social_x, $social_linkedin, $contact_email, $video_file_1, $video_file_2, $video_url_1, $video_url_2, $banner_url, $show_gallery, $show_videos, $show_gift_vouchers, $show_packages, $show_newsletter);
        $insert->execute();

        // Prvé vytvorenie profilu prevádzky — informujeme majiteľa mailom, že profil čaká na schválenie
        // a že ho budeme kontaktovať mailom alebo telefonicky. Zlyhanie mailu nesmie zhodiť uloženie profilu.
        try {
            $owner_stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $owner_stmt->bind_param("i", $user_id);
            $owner_stmt->execute();
            $owner_row = $owner_stmt->get_result()->fetch_assoc();
            if ($owner_row && !empty($owner_row['email'])) {
                require_once '../includes/phpmailer/exception.php';
                require_once '../includes/phpmailer/phpmailer.php';
                require_once '../includes/phpmailer/smtp.php';

                $pendingMail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $pendingMail->isSMTP();
                $pendingMail->Host       = 'mail.usr.sk';
                $pendingMail->SMTPAuth   = true;
                $pendingMail->Username   = $smtp_user;
                $pendingMail->Password   = $smtp_pass;
                $pendingMail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $pendingMail->Port       = 465;
                $pendingMail->CharSet    = 'UTF-8';

                $pendingMail->setFrom($smtp_user, BRAND_NAME);
                $pendingMail->addAddress($owner_row['email'], $owner_row['full_name'] ?? '');
                $pendingMail->isHTML(true);
                $pendingMail->Subject = 'Váš profil prevádzky čaká na schválenie - ' . BRAND_NAME;
                $pendingMail->Body = "
                    <div style='font-family:Outfit,Arial,sans-serif; color:#0F172A; max-width:520px; margin:0 auto; padding:32px 24px; background:#f4f1ea; border-radius:16px;'>
                        <h2 style='margin:0 0 12px;'>Ďakujeme, " . htmlspecialchars($owner_row['full_name'] ?? '') . "!</h2>
                        <p style='font-size:14.5px; line-height:1.6; color:#334155;'>
                            Profil prevádzky <b>" . htmlspecialchars($name) . "</b> je vytvorený a momentálne čaká na schválenie administrátorom.
                        </p>
                        <p style='font-size:14.5px; line-height:1.6; color:#334155;'>
                            Budeme vás kontaktovať e-mailom alebo telefonicky, aby sme si overili detaily a profil čo najskôr schválili.
                        </p>
                    </div>
                ";
                $pendingMail->AltBody = "Profil prevádzky {$name} caka na schvalenie administratorom. Budeme vas kontaktovat mailom alebo telefonicky.";
                $pendingMail->send();
            }
        } catch (\Exception $mailEx) {
            error_log('Establishment pending-approval email failed: ' . $mailEx->getMessage());
        }
    }
    
    echo json_encode(['success' => true, 'avatar_url' => $avatar_url, 'banner_url' => $banner_url, 'video_file_1' => $video_file_1, 'video_file_2' => $video_file_2, 'video_url_1' => $video_url_1, 'video_url_2' => $video_url_2]);
}
elseif ($action === 'get_services') {
    $stmt = $conn->prepare("SELECT s.* FROM services s JOIN establishments e ON s.establishment_id = e.id WHERE e.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}
elseif ($action === 'create_service') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 30);

    // Najprv zistíme establishment_id a limity
    $est_stmt = $conn->prepare("SELECT id, max_services, (SELECT COUNT(*) FROM services WHERE establishment_id = establishments.id) as current_services FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_res = $est_stmt->get_result();
    if ($est = $est_res->fetch_assoc()) {
        if ($est['current_services'] >= $est['max_services']) {
            echo json_encode(['success' => false, 'message' => 'Dosiahli ste maximálny povolený počet služieb (' . $est['max_services'] . ').']);
            exit;
        }

        $ins = $conn->prepare("INSERT INTO services (establishment_id, name, price, duration_minutes) VALUES (?, ?, ?, ?)");
        $ins->bind_param("isdi", $est['id'], $name, $price, $duration);
        if ($ins->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chyba pri ukladaní služby.']);
        }
    } else {
         echo json_encode(['success' => false, 'message' => 'Najprv si musíte vyplniť profil prevádzky.']);
    }
}
elseif ($action === 'delete_service') {
    $id = intval($_POST['id'] ?? 0);
    // Zmazanie len ak patrí tomuto používateľovi
    $del = $conn->prepare("DELETE s FROM services s JOIN establishments e ON s.establishment_id = e.id WHERE s.id = ? AND e.user_id = ?");
    $del->bind_param("ii", $id, $user_id);
    $del->execute();
    echo json_encode(['success' => true]);
}
elseif ($action === 'get_bookings') {

    $stmt = $conn->prepare("
        SELECT b.*,
               s.name as service_name,
               s.duration_minutes as service_duration,
               u.full_name as customer_name,
               u.email as customer_email,
               u.phone as customer_phone,
               u.avatar_path as customer_avatar,
               u.card_verified as customer_verified,
               (SELECT ROUND(AVG(rating), 1) FROM reviews r WHERE r.reviewee_id = u.id AND r.reviewee_type = 'customer') as customer_rating,
               e.subscription_tier,
               emp.name as employee_name,
               emp.color as employee_color
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        LEFT JOIN users u ON b.customer_id = u.id
        JOIN establishments e ON b.establishment_id = e.id
        LEFT JOIN employees emp ON b.employee_id = emp.id
        WHERE e.user_id = ?
        ORDER BY b.created_at ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $bookings = [];
    
    // Limits
    $limits = ['free' => 150, 'start' => 300, 'pro' => 1500, 'vip' => 9999999];
    $monthly_counts = [];
    
    while ($row = $res->fetch_assoc()) {
        $created_date = strtotime($row['created_at'] ?? $row['booking_date']);
        $month_key = date('Y-m', $created_date);
        
        if (!isset($monthly_counts[$month_key])) {
            $monthly_counts[$month_key] = 0;
        }
        
        $tier = $row['subscription_tier'] ?: 'free';
        $limit = $limits[$tier] ?? 150;
        
        $monthly_counts[$month_key]++;
        
        if ($monthly_counts[$month_key] > $limit) {
            $row['is_over_limit'] = true;
            $row['customer_name'] = 'Zamknutý Zákazník';
            $row['customer_email'] = '***@***.**';
            $row['customer_phone'] = '*** *** ***';
            $row['phone'] = '*** *** ***';
            $row['customer_avatar'] = 'assets/img/lock-avatar.png';
            $row['customer_verified'] = 0;
            $row['customer_rating'] = null;
        } else {
            $row['is_over_limit'] = false;
        }
        
        $bookings[] = $row;
    }
    
    
    // Nacitaj aj unavailabilities
    $est_id_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_id_stmt->bind_param("i", $user_id);
    $est_id_stmt->execute();
    $est_id = (int)($est_id_stmt->get_result()->fetch_assoc()['id'] ?? 0);

    $unavail_stmt = $conn->prepare("
        SELECT id, type, start_datetime, end_datetime, note
        FROM unavailabilities
        WHERE establishment_id = ?
    ");
    $unavail_stmt->bind_param("i", $est_id);
    $unavail_stmt->execute();
    $unavail_res = $unavail_stmt->get_result();
    while($u = $unavail_res->fetch_assoc()) {
        $bookings[] = [
            'id' => 'u_'.$u['id'],
            'service_name' => ($u['type'] == 'pn' ? 'PN' : ($u['type'] == 'holiday' ? 'Dovolenka' : 'Zatvorené')) . ($u['note'] ? ' - '.$u['note'] : ''),
            'service_duration' => 0,
            'customer_name' => 'Nedostupnosť',
            'booking_date' => date('Y-m-d', strtotime($u['start_datetime'])),
            'start_time' => date('H:i:s', strtotime($u['start_datetime'])),
            'end_datetime' => $u['end_datetime'],
            'status' => 'unavailable'
        ];
    }

    echo json_encode(['success' => true, 'data' => $bookings]);

}
elseif ($action === 'update_booking') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
        // Vlastníctvo overujeme HNEĎ pri prvom čítaní (nielen v UPDATE), aby cudzia rezervácia
        // (iná prevádzka) nemohla nižšie spustiť freed-slot/waitlist logiku ani keď UPDATE
        // nič nezmení.
        $prev_stmt = $conn->prepare("SELECT b.status FROM bookings b JOIN establishments e ON b.establishment_id = e.id WHERE b.id = ? AND e.user_id = ?");
        $prev_stmt->bind_param("ii", $id, $user_id);
        $prev_stmt->execute();
        $prev_row = $prev_stmt->get_result()->fetch_assoc();

        if (!$prev_row) {
            echo json_encode(['success' => false, 'message' => 'Rezervácia nenájdená.']);
            exit;
        }
        $prev_status = $prev_row['status'];

        $upd = $conn->prepare("UPDATE bookings b JOIN establishments e ON b.establishment_id = e.id SET b.status = ? WHERE b.id = ? AND e.user_id = ?");
        $upd->bind_param("sii", $status, $id, $user_id);
        $upd->execute();

        if ($status === 'completed' && $prev_status !== 'completed') {
            require_once __DIR__ . '/../includes/loyalty_helper.php';
            awardLoyaltyPoints($conn, $id);
        }

        $freed_slot = null;
        if ($status === 'cancelled' && $prev_status !== 'cancelled') {
            $bk_stmt = $conn->prepare("
                SELECT b.establishment_id, b.booking_date, b.start_time, b.service_id, b.price_at_booking,
                       s.name AS service_name, s.price AS service_price
                FROM bookings b LEFT JOIN services s ON s.id = b.service_id
                WHERE b.id = ?
            ");
            $bk_stmt->bind_param("i", $id);
            $bk_stmt->execute();
            if ($bk = $bk_stmt->get_result()->fetch_assoc()) {
                require_once __DIR__ . '/../includes/waitlist_helper.php';
                @notifyWaitlistForFreedSlot($conn, (int)$bk['establishment_id'], $bk['booking_date']);

                // Dáta pre okamžitú ponuku "Last Minute jedným klikom" v dashboarde po zrušení
                if (!empty($bk['service_id']) && $bk['booking_date'] >= date('Y-m-d')) {
                    $price = $bk['price_at_booking'] !== null ? (float)$bk['price_at_booking'] : (float)$bk['service_price'];
                    if ($price > 0) {
                        $freed_slot = [
                            'service_id'   => (int)$bk['service_id'],
                            'service_name' => $bk['service_name'],
                            'slot_date'    => $bk['booking_date'],
                            'slot_time'    => substr($bk['start_time'], 0, 5),
                            'price'        => $price,
                        ];
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'freed_slot' => $freed_slot]);
    } else {
        echo json_encode(['success' => false]);
    }
}
elseif ($action === 'add_last_minute') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $slot_date = $_POST['slot_date'] ?? '';
    $slot_time = $_POST['slot_time'] ?? '';
    $discounted_price = floatval($_POST['discounted_price'] ?? 0);
    $note = $_POST['note'] ?? '';

    $estStmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $estStmt->bind_param("i", $user_id);
    $estStmt->execute();
    $estRes = $estStmt->get_result();
    if ($estRow = $estRes->fetch_assoc()) {
        $est_id = $estRow['id'];

        // Reálnu pôvodnú cenu si vždy dotiahneme zo servera podľa service_id (nikdy nedôverujeme
        // original_price/discounted_price poslaným klientom — pozri "kariéra" v last-minute e-mailoch
        // s odkazom &discount=..., ktorý je počítaný v JS a dá sa upraviť).
        $svcStmt = $conn->prepare("SELECT price FROM services WHERE id = ? AND establishment_id = ?");
        $svcStmt->bind_param("ii", $service_id, $est_id);
        $svcStmt->execute();
        $svcRow = $svcStmt->get_result()->fetch_assoc();
        if (!$svcRow) {
            echo json_encode(['success' => false, 'message' => 'Služba nenájdená.']);
            exit;
        }
        $original_price = (float)$svcRow['price'];

        if ($discounted_price <= 0 || $discounted_price >= $original_price) {
            echo json_encode(['success' => false, 'message' => 'Zľavnená cena musí byť kladná a nižšia ako pôvodná cena služby.']);
            exit;
        }

        $ins = $conn->prepare("INSERT INTO last_minute_slots (establishment_id, service_id, slot_date, slot_time, original_price, discounted_price, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iissdds", $est_id, $service_id, $slot_date, $slot_time, $original_price, $discounted_price, $note);
        if ($ins->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chyba databázy.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Prevádzka nenájdená.']);
    }
}
elseif ($action === 'get_last_minute') {
    $estStmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $estStmt->bind_param("i", $user_id);
    $estStmt->execute();
    $estRes = $estStmt->get_result();
    if ($estRow = $estRes->fetch_assoc()) {
        $est_id = $estRow['id'];
        
        // Auto-expire past slots
        $conn->query("UPDATE last_minute_slots SET status = 'expired' WHERE (slot_date < CURDATE() OR (slot_date = CURDATE() AND slot_time < CURTIME())) AND status = 'active'");
        
        $res = $conn->query("SELECT l.*, s.name as service_name FROM last_minute_slots l JOIN services s ON l.service_id = s.id WHERE l.establishment_id = $est_id ORDER BY l.slot_date ASC, l.slot_time ASC");
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prevádzka nenájdená.']);
    }
}
elseif ($action === 'delete_last_minute') {
    $id = intval($_POST['id'] ?? 0);
    $estStmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $estStmt->bind_param("i", $user_id);
    $estStmt->execute();
    $estRes = $estStmt->get_result();
    if ($estRow = $estRes->fetch_assoc()) {
        $est_id = $estRow['id'];
        $del = $conn->prepare("DELETE FROM last_minute_slots WHERE id = ? AND establishment_id = ?");
        $del->bind_param("ii", $id, $est_id);
        $del->execute();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prevádzka nenájdená.']);
    }
} elseif ($action === 'send_support') {
    $subject = trim($_POST['subject'] ?? 'Podpora Dashboard');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Správa nemôže byť prázdna.']);
        exit;
    }
    
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_email = $row['email'];
        
        require_once '../includes/phpmailer/exception.php';
        require_once '../includes/phpmailer/phpmailer.php';
        require_once '../includes/phpmailer/smtp.php';
        
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mail.usr.sk';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp_user, 'Podpora ' . BRAND_NAME);
            $mail->addReplyTo($user_email);
            $mail->addAddress($smtp_user); // send to admin

            $mail->isHTML(true);
            $mail->Subject = "Podpora: " . $subject;
            $mail->Body    = "Dostali ste novú správu z Dashboardu od salónu (ID: $user_id, Email: $user_email).<br><br><b>Správa:</b><br>" . nl2br(htmlspecialchars($message));

            $mail->send();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Chyba pri odosielaní: ' . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Používateľ nenájdený.']);
    }
} elseif ($action === 'rate_customer') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Neplatné hodnotenie.']);
        exit;
    }
    
    // Overime ci dana rezervacia patri k salonu, ci uz je completed, a zistime id zakaznika a salonu
    $stmt = $conn->prepare("SELECT b.customer_id, e.id as est_id, e.subscription_tier FROM bookings b JOIN establishments e ON b.establishment_id = e.id WHERE b.id = ? AND e.user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (in_array($row['subscription_tier'], ['free'])) {
            echo json_encode(['success' => false, 'message' => 'Hodnotenie zákazníkov je dostupné len od balíka START.']);
            exit;
        }
        if (!$row['customer_id']) {
            echo json_encode(['success' => false, 'message' => 'Nemôžete hodnotiť manuálne zadaného zákazníka.']);
            exit;
        }
        
        // Zistíme či už existuje hodnotenie
        $check = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ? AND reviewer_type = 'establishment'");
        $check->bind_param("i", $booking_id);
        $check->execute();
        $checkRes = $check->get_result();
        if ($checkRes->fetch_assoc()) {
            // Update existujúce
            $upd = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE booking_id = ? AND reviewer_type = 'establishment'");
            $upd->bind_param("isi", $rating, $comment, $booking_id);
            $upd->execute();
        } else {
            // Insert nové
            $ins = $conn->prepare("INSERT INTO reviews (booking_id, reviewer_type, reviewer_id, reviewee_type, reviewee_id, rating, comment) VALUES (?, 'establishment', ?, 'customer', ?, ?, ?)");
            $ins->bind_param("iiiis", $booking_id, $row['est_id'], $row['customer_id'], $rating, $comment);
            $ins->execute();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rezervácia nenájdená.']);
    }
} elseif ($action === 'get_establishment_info') {
    $stmt = $conn->prepare("SELECT e.*, u.email as user_email, u.phone as user_phone, u.avatar_path as avatar_url FROM establishments e JOIN users u ON e.user_id = u.id WHERE e.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($est = $res->fetch_assoc()) {
        $est_id = (int)$est['id'];
        $tier = $est['subscription_tier'] ?: 'free';
        $limits = ['free' => 150, 'start' => 300, 'pro' => 1500, 'vip' => 9999999];
        $booking_limit = $limits[$tier] ?? 150;

        // Počet rezervácií v tomto mesiaci
        $b_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE establishment_id = ? AND MONTH(booking_date) = MONTH(CURRENT_DATE()) AND YEAR(booking_date) = YEAR(CURRENT_DATE())");
        $b_stmt->bind_param("i", $est_id);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result()->fetch_assoc();
        $current_bookings_count = (int)($b_res['cnt'] ?? 0);
        $b_stmt->close();

        // Počet služieb
        $s_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM services WHERE establishment_id = ?");
        $s_stmt->bind_param("i", $est_id);
        $s_stmt->execute();
        $s_res = $s_stmt->get_result()->fetch_assoc();
        $current_services_count = (int)($s_res['cnt'] ?? 0);
        $s_stmt->close();

        // Zoznam zamestnancov
        $emp_stmt = $conn->prepare("SELECT id, name, title, avatar_url, is_owner, is_active, created_at FROM employees WHERE (business_id = ? OR establishment_id = ?) ORDER BY is_owner DESC, order_index ASC, id ASC");
        $emp_stmt->bind_param("ii", $user_id, $est_id);
        $emp_stmt->execute();
        $emp_res = $emp_stmt->get_result();
        $employees = [];
        while ($emp = $emp_res->fetch_assoc()) {
            $employees[] = $emp;
        }
        $emp_stmt->close();

        // Zoznam vybavenia (amenities)
        $amenities = [];
        if (!empty($est['amenities'])) {
            $decoded = json_decode($est['amenities'], true);
            $amenities = is_array($decoded) ? $decoded : explode(',', $est['amenities']);
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'establishment' => $est,
                'tier' => $tier,
                'booking_limit' => $booking_limit,
                'current_bookings_count' => $current_bookings_count,
                'current_services_count' => $current_services_count,
                'employees' => $employees,
                'amenities' => $amenities
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prevádzka nenájdená.']);
    }
    $stmt->close();
} elseif ($action === 'save_establishment_info') {
    $name = trim($_POST['name'] ?? '');
    $legal_name = trim($_POST['legal_name'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $ico = trim($_POST['ico'] ?? '');
    $dic = trim($_POST['dic'] ?? '');
    $ic_dph = trim($_POST['ic_dph'] ?? '');
    $is_vat_payer = intval($_POST['is_vat_payer'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $billing_email = trim($_POST['billing_email'] ?? '');
    $deposit_iban = trim($_POST['deposit_iban'] ?? '');
    $amenities = $_POST['amenities'] ?? '[]';

    if (empty($name) || empty($address) || empty($city)) {
        echo json_encode(['success' => false, 'message' => 'Vyplňte povinné polia (Názov prevádzky, Adresa, Mesto).']);
        exit;
    }

    // Overime ci stĺpce existujú v DB alebo ich updatneme
    $stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $est_id = (int)$row['id'];
        
        $upd = $conn->prepare("
            UPDATE establishments 
            SET name = ?, legal_name = ?, owner_name = ?, ico = ?, dic = ?, ic_dph = ?, is_vat_payer = ?, address = ?, city = ?, phone = ?, billing_email = ?, deposit_iban = ?, amenities = ?
            WHERE user_id = ?
        ");
        if ($upd) {
            $upd->bind_param("ssssssissssssi", $name, $legal_name, $owner_name, $ico, $dic, $ic_dph, $is_vat_payer, $address, $city, $phone, $billing_email, $deposit_iban, $amenities, $user_id);
            $upd->execute();
            $upd->close();
        } else {
            // Fallback ak niektorý stĺpec chýba
            $fallback = $conn->prepare("UPDATE establishments SET name = ?, address = ?, city = ?, phone = ?, deposit_iban = ? WHERE user_id = ?");
            $fallback->bind_param("sssssi", $name, $address, $city, $phone, $deposit_iban, $user_id);
            $fallback->execute();
            $fallback->close();
        }

        // Synchronizujeme aj tabuľku users a employees (pre majiteľa)
        $upd_user = $conn->prepare("UPDATE users SET phone = ?, address = ?, city = ?, ico = ?, dic = ?" . (!empty($owner_name) ? ", full_name = ?" : "") . " WHERE id = ?");
        if ($upd_user) {
            if (!empty($owner_name)) {
                $upd_user->bind_param("ssssssi", $phone, $address, $city, $ico, $dic, $owner_name, $user_id);
            } else {
                $upd_user->bind_param("sssssi", $phone, $address, $city, $ico, $dic, $user_id);
            }
            $upd_user->execute();
            $upd_user->close();
        }

        if (!empty($owner_name)) {
            $upd_emp = $conn->prepare("UPDATE employees SET name = ? WHERE business_id = ? AND is_owner = 1");
            if ($upd_emp) {
                $upd_emp->bind_param("si", $owner_name, $user_id);
                $upd_emp->execute();
                $upd_emp->close();
            }
            $_SESSION['user_name'] = $owner_name;
        }

        echo json_encode(['success' => true, 'message' => 'Údaje prevádzky boli úspešne uložené.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prevádzka neexistuje. Najprv si vytvorte profil.']);
    }
    $stmt->close();
}
elseif ($action === 'report_bug') {
    $description = trim($_POST['description'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $user_agent = trim($_POST['user_agent'] ?? '');
    $screen_res = trim($_POST['screen_res'] ?? '');
    $screenshot_path = null;

    if (empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Chýba popis chyby.']);
        exit;
    }

    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `crm_bug_reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `description` TEXT NOT NULL,
        `url` VARCHAR(500) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `screen_res` VARCHAR(50) DEFAULT NULL,
        `screenshot_path` VARCHAR(255) DEFAULT NULL,
        `status` ENUM('new', 'in_progress', 'fixed', 'closed') DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Handle screenshot upload
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/bugs/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }
        
        $orig_name = $_FILES['screenshot']['name'];
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }
        
        $filename = 'bug_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $target)) {
            $screenshot_path = 'uploads/bugs/' . $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO crm_bug_reports (user_id, description, url, user_agent, screen_res, screenshot_path) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $user_id, $description, $url, $user_agent, $screen_res, $screenshot_path);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Hlásenie bolo úspešne odoslané. Ďakujeme!']);
    exit;
}

// === GET / SAVE CONFIRMATION MODE ===
elseif ($action === 'get_confirmation_mode') {
    $stmt = $conn->prepare("SELECT confirmation_mode FROM establishments WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode(['success' => true, 'mode' => $row['confirmation_mode'] ?? 'manual']);
    exit;
}
elseif ($action === 'save_confirmation_mode') {
    $mode = in_array($_POST['mode'] ?? '', ['manual', 'auto']) ? $_POST['mode'] : 'manual';
    $stmt = $conn->prepare("UPDATE establishments SET confirmation_mode = ? WHERE user_id = ?");
    $stmt->bind_param("si", $mode, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// === GET CRM CONTACTS ===
elseif ($action === 'get_crm_contacts') {
    // Get establishment id
    $est_stmt = $conn->prepare("SELECT id, subscription_tier FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_row = $est_stmt->get_result()->fetch_assoc();
    $est_stmt->close();
    $est_id = $est_row['id'] ?? 0;
    $tier = $est_row['subscription_tier'] ?? 'free';

    $limits = ['free' => 50, 'start' => 200, 'pro' => 1000, 'vip' => 9999999];
    $limit = $limits[$tier] ?? 50;

    // Customers from bookings
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, u.avatar_path, u.card_verified,
               COUNT(b.id) as total_visits,
               MAX(b.booking_date) as last_visit,
               ROUND(AVG(r.rating), 1) as avg_rating,
               'booking' as source
        FROM bookings b
        JOIN users u ON b.customer_id = u.id
        LEFT JOIN reviews r ON r.reviewee_id = u.id AND r.reviewee_type = 'customer'
        WHERE b.establishment_id = ?
        GROUP BY u.id
    ");
    $stmt->bind_param("i", $est_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $contacts = [];
    while ($row = $res->fetch_assoc()) {
        $contacts[] = $row;
    }
    $stmt->close();

    // Manual walk-in contacts
    $conn->query("CREATE TABLE IF NOT EXISTS `crm_manual_contacts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `establishment_id` int(11) NOT NULL,
        `full_name` varchar(120) NOT NULL,
        `email` varchar(180) DEFAULT NULL,
        `phone` varchar(30) DEFAULT NULL,
        `note` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $mstmt = $conn->prepare("SELECT id, full_name, email, phone, 0 as total_visits, created_at as last_visit, NULL as avg_rating, NULL as card_verified, NULL as avatar_path, 'manual' as source FROM crm_manual_contacts WHERE establishment_id = ?");
    $mstmt->bind_param("i", $est_id);
    $mstmt->execute();
    $mres = $mstmt->get_result();
    while ($row = $mres->fetch_assoc()) {
        $contacts[] = $row;
    }
    $mstmt->close();

    // Apply tier limit
    $is_over_limit = count($contacts) > $limit;
    $visible = array_slice($contacts, 0, $limit);
    foreach ($visible as &$c) {
        $c['id'] = $c['id'] ?? '';
        $c['full_name'] = $c['full_name'] ?? '';
        $c['email'] = $c['email'] ?? '';
        $c['phone'] = $c['phone'] ?? '';
        $c['avatar_path'] = $c['avatar_path'] ?? null;
        $c['card_verified'] = $c['card_verified'] ?? 0;
        $c['total_visits'] = $c['total_visits'] ?? 0;
        $c['last_visit'] = $c['last_visit'] ?? null;
        $c['avg_rating'] = $c['avg_rating'] ?? null;
        $c['source'] = $c['source'] ?? 'booking';
    }

    echo json_encode(['success' => true, 'contacts' => $visible, 'is_over_limit' => $is_over_limit]);
    exit;
}

// === DAROVAŤ KRESLO HUNTER ZÁKAZNÍKOVI (za 50 % ceny, hradené výhradne reálnymi peniazmi firmy) ===
elseif ($action === 'gift_hunter') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    if (!$customerId) { echo json_encode(['success' => false, 'message' => 'Chýba zákazník.']); exit; }

    $GIFT_PRICE = 4.50; // polovica z ročnej ceny Kreslo Hunter (9,00 €)

    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_id = ($est_stmt->get_result()->fetch_assoc()['id']) ?? 0;
    $est_stmt->close();
    if (!$est_id) { echo json_encode(['success' => false, 'message' => 'Prevádzka nebola nájdená.']); exit; }

    // Zákazník musí byť reálny, overený klient tejto prevádzky (mal aspoň 1 rezerváciu)
    $chk_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM bookings WHERE establishment_id = ? AND customer_id = ?");
    $chk_stmt->bind_param("ii", $est_id, $customerId);
    $chk_stmt->execute();
    $hasBooking = (int)($chk_stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $chk_stmt->close();
    if (!$hasBooking) { echo json_encode(['success' => false, 'message' => 'Tento zákazník nemá u vás žiadnu rezerváciu.']); exit; }

    // Kontrola reálneho (dobitého) kreditu firmy — nazbieraný kredit sa na darčeky nepoužíva
    $wb_stmt = $conn->prepare("SELECT credit_purchased FROM users WHERE id = ?");
    $wb_stmt->bind_param("i", $user_id);
    $wb_stmt->execute();
    $purchased = (float)($wb_stmt->get_result()->fetch_assoc()['credit_purchased'] ?? 0);
    $wb_stmt->close();

    if ($purchased < $GIFT_PRICE) {
        echo json_encode([
            'success' => false,
            'message' => 'Nedostatočný zostatok reálnych peňazí v Peňaženke. Potrebujete ' . number_format($GIFT_PRICE, 2, ',', ' ') . ' €.',
            'need_topup' => true
        ]);
        exit;
    }

    // Strhneme firme (len z credit_purchased)
    $ded_stmt = $conn->prepare("UPDATE users SET credit = GREATEST(0, credit - ?), credit_purchased = GREATEST(0, credit_purchased - ?) WHERE id = ?");
    $ded_stmt->bind_param("ddi", $GIFT_PRICE, $GIFT_PRICE, $user_id);
    $ded_stmt->execute();
    $ded_stmt->close();

    // Self-migrácia stĺpca (zdieľaný s api/wallet.php)
    $colChk = $conn->query("SHOW COLUMNS FROM users LIKE 'hunter_expires_at'");
    if ($colChk && $colChk->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN hunter_expires_at DATETIME NULL DEFAULT NULL");
    }

    // Predĺžime Huntera zákazníkovi o 1 rok
    $ext_stmt = $conn->prepare("UPDATE users SET hunter_expires_at = DATE_ADD(GREATEST(COALESCE(hunter_expires_at, NOW()), NOW()), INTERVAL 1 YEAR) WHERE id = ?");
    $ext_stmt->bind_param("i", $customerId);
    $ext_stmt->execute();
    $ext_stmt->close();

    $negPrice = -$GIFT_PRICE;
    $bizDesc = 'Darovanie Kreslo Hunter zákazníkovi (1 rok, -50 %)';
    $tx1 = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'hunter_gift', ?, 'EUR', 'completed', ?)");
    $tx1->bind_param("ids", $user_id, $negPrice, $bizDesc);
    $tx1->execute();
    $tx1->close();

    $custDesc = 'Kreslo Hunter darovaný od vašej obľúbenej prevádzky (1 rok)';
    $tx2 = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'hunter_gift_received', 0, 'EUR', 'completed', ?)");
    $tx2->bind_param("is", $customerId, $custDesc);
    $tx2->execute();
    $tx2->close();

    echo json_encode(['success' => true, 'message' => 'Kreslo Hunter bol darovaný zákazníkovi na 1 rok!']);
    exit;
}

// === ADD MANUAL CONTACT ===
elseif ($action === 'add_manual_contact') {
    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_row = $est_stmt->get_result()->fetch_assoc();
    $est_stmt->close();
    $est_id = $est_row['id'] ?? 0;

    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka nenájdená']); exit; }

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (!$full_name) { echo json_encode(['success' => false, 'error' => 'Meno je povinné']); exit; }

    $conn->query("CREATE TABLE IF NOT EXISTS `crm_manual_contacts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `establishment_id` int(11) NOT NULL,
        `full_name` varchar(120) NOT NULL,
        `email` varchar(180) DEFAULT NULL,
        `phone` varchar(30) DEFAULT NULL,
        `note` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $conn->prepare("INSERT INTO crm_manual_contacts (establishment_id, full_name, email, phone, note) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $est_id, $full_name, $email, $phone, $note);
    if ($stmt->execute()) {
        $invite_sent = false;
        if ($email) {
            // Pozvánku pošleme len ak tento e-mail ešte nepatrí žiadnemu registrovanému účtu
            $chk_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk_stmt->bind_param("s", $email);
            $chk_stmt->execute();
            $already = $chk_stmt->get_result()->fetch_assoc();
            $chk_stmt->close();
            if (!$already) {
                $name_stmt = $conn->prepare("SELECT name FROM establishments WHERE id = ?");
                $name_stmt->bind_param("i", $est_id);
                $name_stmt->execute();
                $est_name = $name_stmt->get_result()->fetch_assoc()['name'] ?? 'Vaša prevádzka';
                $name_stmt->close();
                require_once __DIR__ . '/mailer.php';
                $invite_sent = sendRegisterInviteEmail($email, $full_name, $est_name);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Kontakt bol pridaný' . ($invite_sent ? ' a bola mu odoslaná pozvánka na registráciu.' : '.')]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chyba pri ukladaní kontaktu']);
    }
    $stmt->close();
    exit;
}

// === VYHĽADANIE FIRMY PODĽA IČO (RegisterUZ pre s.r.o./a.s., ZRSR pre živnostníkov, VIES pre IČ DPH) ===
elseif ($action === 'lookup_ico') {
    $ico = preg_replace('/\s+/', '', trim($_POST['ico'] ?? ''));
    if (!$ico) { echo json_encode(['success' => false, 'error' => 'Zadajte IČO.']); exit; }

    function rezervos_http_get($url, $headers = [], $cookie_jar = null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        if ($cookie_jar) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
        }
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, $body];
    }

    $ua_json = ['User-Agent: Rezervos/1.0 (Business App for Slovakia)', 'Accept: application/json'];
    $company = null;

    // 1. RegisterUZ — účtovné jednotky (s.r.o., a.s. a pod.)
    try {
        [$code1, $body1] = rezervos_http_get("https://www.registeruz.sk/cruz-public/api/uctovne-jednotky?ico=" . urlencode($ico) . "&zmenene-od=2000-01-01", $ua_json);
        if ($code1 === 200) {
            $search = json_decode($body1, true);
            if (!empty($search['id'][0])) {
                $unit_id = $search['id'][0];
                [$code2, $body2] = rezervos_http_get("https://www.registeruz.sk/cruz-public/api/uctovna-jednotka?id=" . urlencode($unit_id), $ua_json);
                if ($code2 === 200) {
                    $d = json_decode($body2, true);
                    $dic = $d['dic'] ?? '';
                    $ic_dph = $d['icDph'] ?? '';

                    // Ak register nemá priamo IČ DPH, over cez VIES (EU register platcov DPH, "Brusel")
                    if (!$ic_dph && $dic) {
                        try {
                            $vies_ch = curl_init('https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number');
                            curl_setopt($vies_ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($vies_ch, CURLOPT_POST, true);
                            curl_setopt($vies_ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                            curl_setopt($vies_ch, CURLOPT_POSTFIELDS, json_encode(['countryCode' => 'SK', 'vatNumber' => $dic]));
                            curl_setopt($vies_ch, CURLOPT_TIMEOUT, 8);
                            $vies_body = curl_exec($vies_ch);
                            $vies_code = curl_getinfo($vies_ch, CURLINFO_HTTP_CODE);
                            curl_close($vies_ch);
                            if ($vies_code === 200) {
                                $vies_data = json_decode($vies_body, true);
                                if (!empty($vies_data['valid'])) { $ic_dph = 'SK' . $dic; }
                            }
                        } catch (Exception $e) { /* VIES nedostupné — pokračujeme bez IČ DPH */ }
                    }

                    $company = [
                        'legal_name' => $d['nazovUJ'] ?? $d['obchodneMeno'] ?? null,
                        'owner_name' => null,
                        'ico' => $d['ico'] ?? $ico,
                        'dic' => $dic ?: null,
                        'ic_dph' => $ic_dph ?: null,
                        'address' => $d['ulica'] ?? null,
                        'city' => $d['mesto'] ?? $d['obec'] ?? null,
                    ];
                }
            }
        }
    } catch (Exception $e) { /* skúsime ZRSR nižšie */ }

    // 2. Živnostenský register (SZČO) — ak RegisterUZ nič nenašiel
    if (!$company) {
        try {
            $cookie_jar = tempnam(sys_get_temp_dir(), 'zrsr_');
            $ua_html = ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'];
            rezervos_http_get('https://www.zrsr.sk/default.aspx', $ua_html, $cookie_jar);
            [$code3, $html] = rezervos_http_get('https://www.zrsr.sk/zr_ico.aspx?ICO=' . urlencode($ico), array_merge($ua_html, ['Referer: https://www.zrsr.sk/default.aspx']), $cookie_jar);

            if ($code3 === 200 && $html) {
                if (preg_match('/href=[\'"](zr_browse\.aspx\?[^\'"]+)[\'"]/i', $html, $m)) {
                    $detail_url = 'https://www.zrsr.sk/' . str_replace('&amp;', '&', $m[1]);
                    [, $html2] = rezervos_http_get($detail_url, $ua_html, $cookie_jar);
                    if ($html2) { $html = $html2; }
                }
                @unlink($cookie_jar);

                if (preg_match('/(?:Obchodn[eé]\s+meno|Meno\s+a\s+priezvisko)[^<]*<\/td>[^<]*<td[^>]*>([\s\S]*?)<\/td>/i', $html, $nameMatch)) {
                    $name = trim(preg_replace(['/<[^>]*>/', '/&nbsp;/', '/\s+/'], ['', ' ', ' '], $nameMatch[1]));
                    $address = '';
                    if (preg_match('/S[ií]dlo[^<]*<\/td>[^<]*<td[^>]*>([\s\S]*?)<\/td>/i', $html, $addrMatch) ||
                        preg_match('/Trval[eé]\s+bydlisko[^<]*<\/td>[^<]*<td[^>]*>([\s\S]*?)<\/td>/i', $html, $addrMatch)) {
                        $address = trim(str_replace('&nbsp;', ' ', preg_replace('/<[^>]*>/', '', $addrMatch[1])));
                    }
                    $street = ''; $city = '';
                    if ($address) {
                        $parts = explode(',', $address);
                        $street = trim($parts[0] ?? '');
                        $city_zip = trim($parts[1] ?? '');
                        $city = preg_replace('/^\d{3}\s?\d{2}\s*/', '', $city_zip);
                        $city = trim(explode('(', $city)[0]);
                    }
                    $dic = '';
                    if (preg_match('/Da[nň]ov[eé]\s+identifika[cč]n[eé]\s+[cč][ií]slo[^<]*<\/td>[^<]*<td[^>]*>([\s\S]*?)<\/td>/i', $html, $dicMatch)) {
                        $dic = trim(preg_replace('/<[^>]*>/', '', $dicMatch[1]));
                    }
                    $company = [
                        'legal_name' => null,
                        'owner_name' => $name ?: null,
                        'ico' => $ico,
                        'dic' => $dic ?: null,
                        'ic_dph' => null,
                        'address' => $street ?: null,
                        'city' => $city ?: null,
                    ];
                }
            }
        } catch (Exception $e) { /* nič sa nenašlo */ }
    }

    if ($company) {
        echo json_encode(['success' => true, 'company' => $company]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Firma s týmto IČO nebola nájdená.']);
    }
    exit;
}

// === ULOŽENIE ÚDAJOV PREVÁDZKY (fakturačné údaje, kontakt, IBAN, vybavenie, kapacita) ===
elseif ($action === 'update_establishment') {
    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_id = $est_stmt->get_result()->fetch_assoc()['id'] ?? 0;
    $est_stmt->close();
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka nenájdená.']); exit; }

    // Self-migrácia — tieto stĺpce mohli chýbať na starších prevádzkach
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS legal_name VARCHAR(180) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS owner_name VARCHAR(150) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS ico VARCHAR(20) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS dic VARCHAR(20) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS ic_dph VARCHAR(20) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS is_vat_payer TINYINT(1) NOT NULL DEFAULT 0");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS billing_email VARCHAR(180) DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS amenities TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS capacity_per_slot INT DEFAULT NULL");

    $name = trim($_POST['name'] ?? '');
    if (!$name) { echo json_encode(['success' => false, 'error' => 'Názov prevádzky je povinný.']); exit; }

    $legal_name = trim($_POST['legal_name'] ?? '') ?: null;
    $owner_name = trim($_POST['owner_name'] ?? '') ?: null;
    $ico = trim($_POST['ico'] ?? '') ?: null;
    $dic = trim($_POST['dic'] ?? '') ?: null;
    $ic_dph = trim($_POST['ic_dph'] ?? '') ?: null;
    $is_vat_payer = !empty($_POST['is_vat_payer']) ? 1 : 0;
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '') ?: null;
    $billing_email = trim($_POST['billing_email'] ?? '') ?: null;
    $deposit_iban = trim($_POST['deposit_iban'] ?? '') ?: null;
    $amenities = trim($_POST['amenities'] ?? '[]');
    json_decode($amenities); // validácia, že je to skutočne platný JSON
    if (json_last_error() !== JSON_ERROR_NONE) { $amenities = '[]'; }

    $capacity_raw = trim($_POST['capacity_per_slot'] ?? '');
    $capacity_per_slot = ($capacity_raw !== '' && is_numeric($capacity_raw) && (int)$capacity_raw > 0) ? (int)$capacity_raw : null;

    $stmt = $conn->prepare("UPDATE establishments SET name=?, legal_name=?, owner_name=?, ico=?, dic=?, ic_dph=?, is_vat_payer=?, address=?, city=?, phone=?, billing_email=?, deposit_iban=?, amenities=?, capacity_per_slot=? WHERE id=?");
    $stmt->bind_param("ssssssissssssii", $name, $legal_name, $owner_name, $ico, $dic, $ic_dph, $is_vat_payer, $address, $city, $phone, $billing_email, $deposit_iban, $amenities, $capacity_per_slot, $est_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Údaje prevádzky boli uložené.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chyba DB: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// === EXPORT KONTAKTOV DO CSV ===
elseif ($action === 'export_contacts') {
    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_id = $est_stmt->get_result()->fetch_assoc()['id'] ?? 0;
    $est_stmt->close();

    $contacts = [];
    if ($est_id) {
        $stmt = $conn->prepare("
            SELECT u.full_name, u.email, u.phone, COUNT(b.id) as total_visits, MAX(b.booking_date) as last_visit,
                   ROUND(AVG(r.rating), 1) as avg_rating
            FROM bookings b
            JOIN users u ON b.customer_id = u.id
            LEFT JOIN reviews r ON r.reviewee_id = u.id AND r.reviewee_type = 'customer'
            WHERE b.establishment_id = ?
            GROUP BY u.id
        ");
        $stmt->bind_param("i", $est_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $contacts[] = $row; }
        $stmt->close();

        $conn->query("CREATE TABLE IF NOT EXISTS `crm_manual_contacts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `establishment_id` int(11) NOT NULL,
            `full_name` varchar(120) NOT NULL,
            `email` varchar(180) DEFAULT NULL,
            `phone` varchar(30) DEFAULT NULL,
            `note` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $mstmt = $conn->prepare("SELECT full_name, email, phone, 0 as total_visits, created_at as last_visit, NULL as avg_rating FROM crm_manual_contacts WHERE establishment_id = ?");
        $mstmt->bind_param("i", $est_id);
        $mstmt->execute();
        $mres = $mstmt->get_result();
        while ($row = $mres->fetch_assoc()) { $contacts[] = $row; }
        $mstmt->close();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kontakty_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM, aby Excel správne zobrazil diakritiku
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Meno', 'E-mail', 'Telefón', 'Počet návštev', 'Posledná návšteva', 'Priemerné hodnotenie']);
    foreach ($contacts as $c) {
        fputcsv($out, [$c['full_name'], $c['email'], $c['phone'], $c['total_visits'], $c['last_visit'], $c['avg_rating']]);
    }
    fclose($out);
    exit;
}

// === VZOROVÁ CSV ŠABLÓNA NA IMPORT ===
elseif ($action === 'contacts_import_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vzor_import_kontaktov.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Meno', 'E-mail', 'Telefón', 'Poznámka']);
    fputcsv($out, ['Jana Nováková', 'jana.novakova@example.com', '0901123456', 'Klientka z instagramu']);
    fclose($out);
    exit;
}

// === NÁHĽAD IMPORTU KONTAKTOV Z CSV (bez uloženia) ===
elseif ($action === 'preview_import_contacts') {
    if (empty($_FILES['csv_file']['tmp_name'])) { echo json_encode(['success' => false, 'error' => 'Chýba súbor.']); exit; }

    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_id = $est_stmt->get_result()->fetch_assoc()['id'] ?? 0;
    $est_stmt->close();
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka nenájdená.']); exit; }

    // Existujúce e-maily (registrovaní zákazníci aj manuálne kontakty) na zistenie duplicít
    $existing_emails = [];
    $u_res = $conn->query("SELECT DISTINCT u.email FROM bookings b JOIN users u ON u.id = b.customer_id WHERE b.establishment_id = $est_id AND u.email IS NOT NULL AND u.email != ''");
    while ($r = $u_res->fetch_assoc()) { $existing_emails[mb_strtolower(trim($r['email']))] = true; }
    $conn->query("CREATE TABLE IF NOT EXISTS `crm_manual_contacts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `establishment_id` int(11) NOT NULL,
        `full_name` varchar(120) NOT NULL,
        `email` varchar(180) DEFAULT NULL,
        `phone` varchar(30) DEFAULT NULL,
        `note` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $m_res = $conn->query("SELECT email FROM crm_manual_contacts WHERE establishment_id = $est_id AND email IS NOT NULL AND email != ''");
    while ($r = $m_res->fetch_assoc()) { $existing_emails[mb_strtolower(trim($r['email']))] = true; }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$handle) { echo json_encode(['success' => false, 'error' => 'Súbor sa nepodarilo otvoriť.']); exit; }

    $rows = [];
    $first = true;
    $seen_in_file = [];
    while (($data = fgetcsv($handle)) !== false) {
        if ($first) { $first = false; continue; } // preskočiť hlavičku
        if (count($data) < 1 || !trim(implode('', $data))) continue;

        $full_name = trim($data[0] ?? '');
        $email = trim($data[1] ?? '');
        $phone = trim($data[2] ?? '');
        $note = trim($data[3] ?? '');
        if (!$full_name) continue;

        $email_key = mb_strtolower($email);
        if ($email && isset($existing_emails[$email_key])) {
            $status = 'duplicate';
        } elseif ($email && isset($seen_in_file[$email_key])) {
            $status = 'duplicate_in_file';
        } else {
            $status = 'new';
        }
        if ($email) { $seen_in_file[$email_key] = true; }

        $rows[] = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone, 'note' => $note, 'status' => $status];
    }
    fclose($handle);

    echo json_encode(['success' => true, 'rows' => $rows]);
    exit;
}

// === POTVRDENIE IMPORTU KONTAKTOV (uloží len vybrané riadky) ===
elseif ($action === 'commit_import_contacts') {
    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $user_id);
    $est_stmt->execute();
    $est_id = $est_stmt->get_result()->fetch_assoc()['id'] ?? 0;
    $est_stmt->close();
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka nenájdená.']); exit; }

    $rows = json_decode($_POST['rows'] ?? '[]', true) ?: [];
    if (!is_array($rows) || empty($rows)) { echo json_encode(['success' => false, 'error' => 'Žiadne riadky na import.']); exit; }

    $conn->query("CREATE TABLE IF NOT EXISTS `crm_manual_contacts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `establishment_id` int(11) NOT NULL,
        `full_name` varchar(120) NOT NULL,
        `email` varchar(180) DEFAULT NULL,
        `phone` varchar(30) DEFAULT NULL,
        `note` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $send_invites = !empty($_POST['send_invites']);
    $est_name = '';
    if ($send_invites) {
        $name_stmt = $conn->prepare("SELECT name FROM establishments WHERE id = ?");
        $name_stmt->bind_param("i", $est_id);
        $name_stmt->execute();
        $est_name = $name_stmt->get_result()->fetch_assoc()['name'] ?? 'Vaša prevádzka';
        $name_stmt->close();
        require_once __DIR__ . '/mailer.php';
    }

    $stmt = $conn->prepare("INSERT INTO crm_manual_contacts (establishment_id, full_name, email, phone, note) VALUES (?, ?, ?, ?, ?)");
    $inserted = 0;
    $invites_sent = 0;
    foreach ($rows as $r) {
        $full_name = trim($r['full_name'] ?? '');
        if (!$full_name) continue;
        $email = trim($r['email'] ?? '') ?: null;
        $phone = trim($r['phone'] ?? '') ?: null;
        $note = trim($r['note'] ?? '') ?: null;
        $stmt->bind_param("issss", $est_id, $full_name, $email, $phone, $note);
        if ($stmt->execute()) {
            $inserted++;
            // Pozvánku na registráciu (s ktorou zákazník dostane pri registrácii 6-miestny overovací
            // kód a stane sa plnohodnotným používateľom appky) posielame len ak si to firma vyžiadala
            // a e-mail ešte nepatrí žiadnemu existujúcemu účtu.
            if ($send_invites && $email) {
                $chk_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $chk_stmt->bind_param("s", $email);
                $chk_stmt->execute();
                $already = $chk_stmt->get_result()->fetch_assoc();
                $chk_stmt->close();
                if (!$already && function_exists('sendRegisterInviteEmail')) {
                    if (sendRegisterInviteEmail($email, $full_name, $est_name)) { $invites_sent++; }
                }
            }
        }
    }
    $stmt->close();

    $msg = "Naimportovaných $inserted kontaktov.";
    if ($send_invites) { $msg .= " Pozvánka na registráciu odoslaná $invites_sent z nich."; }
    echo json_encode(['success' => true, 'inserted' => $inserted, 'invites_sent' => $invites_sent, 'message' => $msg]);
    exit;
}

$conn->close();
?>
