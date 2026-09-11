<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Neprihlásený používateľ.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'get_status') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_hunter_settings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();

        // If not exists, insert default
        if (!$row) {
            $ins = $pdo->prepare("INSERT INTO user_hunter_settings (user_id, is_active, city, notify_email) VALUES (?, 0, '', 1)");
            $ins->execute([$user_id]);
            $stmt->execute([$user_id]);
            $row = $stmt->fetch();
        }

        $now = date('Y-m-d H:i:s');
        $is_active = (bool)$row['is_active'];
        if ($is_active && $row['expires_at'] && $row['expires_at'] < $now) {
            $is_active = false;
        }

        // Fetch live Last Minute deals / offers from last_minute_slots as catches
        $catches = [];
        try {
            $deals_stmt = $pdo->query("
                SELECT l.*, s.name as service_name, e.name as establishment_name, e.city, e.address, e.category, e.avatar_url, e.slug,
                ROUND(((l.original_price - l.discounted_price) / l.original_price) * 100) as discount_percent,
                l.discounted_price as new_price, l.original_price as price, l.note as description
                FROM last_minute_slots l
                JOIN establishments e ON l.establishment_id = e.id
                JOIN services s ON l.service_id = s.id
                WHERE l.status = 'active' AND (l.slot_date > CURDATE() OR (l.slot_date = CURDATE() AND l.slot_time >= CURTIME()))
                ORDER BY l.slot_date ASC, l.slot_time ASC LIMIT 12
            ");
            $catches = $deals_stmt->fetchAll();
        } catch (Exception $e) {}

        echo json_encode([
            'success' => true,
            'is_active' => $is_active,
            'plan_type' => $row['plan_type'] ?? 'monthly',
            'expires_at' => $row['expires_at'] ? date('d.m.Y', strtotime($row['expires_at'])) : null,
            'settings' => [
                'city' => $row['city'] ?? '',
                'categories' => $row['categories'] ? json_decode($row['categories'], true) : [],
                'min_discount' => (int)($row['min_discount'] ?? 10),
                'notify_email' => (bool)($row['notify_email'] ?? 1)
            ],
            'catches' => $catches
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'activate') {
    $plan = $_POST['plan'] ?? 'monthly';
    if (!in_array($plan, ['monthly', 'yearly', 'combo', 'combo_yearly'])) $plan = 'monthly';

    // Monthly: +1 month, Yearly / Combo: +12 months (12 mesiacov za cenu 10 mesiacov = 9 €)
    if (in_array($plan, ['yearly', 'combo', 'combo_yearly'])) {
        $expires = date('Y-m-d H:i:s', strtotime('+12 months'));
    } else {
        $expires = date('Y-m-d H:i:s', strtotime('+1 month'));
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_hunter_settings (user_id, is_active, plan_type, expires_at) 
            VALUES (?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE is_active = 1, plan_type = VALUES(plan_type), expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$user_id, $plan, $expires]);

        // If combo plan, automatically verify card/user without deposit requirement
        if (in_array($plan, ['combo', 'combo_yearly'])) {
            $pdo->prepare("UPDATE users SET card_verified = 1 WHERE id = ?")->execute([$user_id]);
        }

        $msg = in_array($plan, ['combo', 'combo_yearly'])
            ? 'VIP Balík (Kreslo Hunter + Overený zákazník bez záloh) bol úspešne aktivovaný!'
            : 'Kreslo Hunter bol úspešne aktivovaný!';

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'expires_at' => date('d.m.Y', strtotime($expires)),
            'plan_type' => $plan,
            'is_verified' => in_array($plan, ['combo', 'combo_yearly']) ? 1 : 0
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_filters') {
    $city = trim($_POST['city'] ?? '');
    $categories = $_POST['categories'] ?? [];
    if (!is_array($categories)) $categories = [];
    $cat_json = json_encode($categories);
    $min_discount = (int)($_POST['min_discount'] ?? 10);
    $notify_email = isset($_POST['notify_email']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_hunter_settings (user_id, city, categories, min_discount, notify_email) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE city = VALUES(city), categories = VALUES(categories), min_discount = VALUES(min_discount), notify_email = VALUES(notify_email)
        ");
        $stmt->execute([$user_id, $city, $cat_json, $min_discount, $notify_email]);

        echo json_encode(['success' => true, 'message' => 'Filtre Huntera boli úspešne uložené!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Neznáma akcia']);
