<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/marketing_send_helper.php';
require_once __DIR__ . '/../includes/holidays_helper.php';
require_once __DIR__ . '/../includes/ai_credit_helper.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'business') {
    echo json_encode(['success' => false, 'error' => 'Neautorizovaný prístup.']);
    exit;
}
$user_id = (int)$_SESSION['user_id'];

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'Chýba akcia']);
    exit;
}

// Helper to load namedays
function get_namedays_data($today_key = null) {
    if (!$today_key) $today_key = date('m-d');
    
    $sk_file = __DIR__ . '/../libs/namedays_sk.json';
    $cz_file = __DIR__ . '/../libs/namedays_cz.json';
    
    $sk_data = file_exists($sk_file) ? json_decode(file_get_contents($sk_file), true) : [];
    $cz_data = file_exists($cz_file) ? json_decode(file_get_contents($cz_file), true) : [];
    
    $sk_today = in_array($today_key, getNonNameNamedayKeys(), true) ? '' : ($sk_data[$today_key] ?? '');
    $cz_today = $cz_data[$today_key] ?? '';
    
    return [
        'today_key' => $today_key,
        'sk_today' => $sk_today,
        'cz_today' => $cz_today,
        'sk_all' => $sk_data,
        'cz_all' => $cz_data
    ];
}

// Helper to strip accents
function remove_accents($str) {
    $transl = [
        'á'=>'a','ä'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ĺ'=>'l','ľ'=>'l','ň'=>'n','ó'=>'o','ô'=>'o','ŕ'=>'r','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z',
        'Á'=>'A','Ä'=>'A','Č'=>'C','Ď'=>'D','É'=>'E','Ě'=>'E','Í'=>'I','Ĺ'=>'L','Ľ'=>'L','Ň'=>'N','Ó'=>'O','Ô'=>'O','Ŕ'=>'R','Ř'=>'R','Š'=>'S','Ť'=>'T','Ú'=>'U','Ů'=>'U','Ý'=>'Y','Ž'=>'Z'
    ];
    return strtr($str, $transl);
}

// Helper to check if name matches nameday
function is_name_in_nameday($full_name, $nameday_str) {
    if (!$nameday_str || !$full_name) return false;
    $first_name = trim(explode(' ', trim($full_name))[0]);
    if (!$first_name) return false;
    
    $clean_first = mb_strtolower(remove_accents($first_name));
    $names = array_map('trim', explode(',', $nameday_str));
    foreach ($names as $n) {
        $n = trim(explode('/', $n)[0]);
        $clean_n = mb_strtolower(remove_accents($n));
        if ($clean_first === $clean_n || strpos($clean_first, $clean_n) !== false || strpos($clean_n, $clean_first) !== false) {
            return true;
        }
    }
    return false;
}

// ── 1. GET MARKETING DATA ──
if ($action === 'get_marketing_data') {
    $namedays = get_namedays_data();
    
    // Get establishment for current business
    $stmt = $pdo->prepare("SELECT id, name FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    $est_name = $est['name'] ?? BRAND_NAME;

    // Fetch clients
    $clients = [];
    if ($est_id > 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date DATE NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('female','male','other') NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS marketing_consent TINYINT(1) NOT NULL DEFAULT 0");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS price_at_booking DECIMAL(10,2) DEFAULT NULL");
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.full_name AS name, u.email, u.phone, u.gender, u.birth_date, u.company_name, u.marketing_consent, 'customer' as type,
                   (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = u.id AND b.establishment_id = ?) as total_bookings,
                   (SELECT MAX(booking_date) FROM bookings b WHERE b.customer_id = u.id AND b.establishment_id = ?) as last_booking,
                   (SELECT COALESCE(SUM(COALESCE(b.price_at_booking, s.price)), 0)
                      FROM bookings b LEFT JOIN services s ON s.id = b.service_id
                      WHERE b.customer_id = u.id AND b.establishment_id = ? AND b.status = 'completed') as total_spent
            FROM users u
            JOIN bookings b ON b.customer_id = u.id
            WHERE b.establishment_id = ? AND u.email IS NOT NULL AND u.email != ''
            ORDER BY u.full_name ASC
        ");
        $stmt->execute([$est_id, $est_id, $est_id, $est_id]);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Zákazník registrovaný/nakupujúci ako firma (má vyplnený názov spoločnosti) sa do
        // segmentácie podľa pohlavia nehodí — pýtať sa "žena/muž" nedáva zmysel pre firmu,
        // preto ho bez ohľadu na skutočnú hodnotu gender stĺpca zaradíme do "iné".
        foreach ($clients as &$c) {
            if (!empty($c['company_name'])) { $c['gender'] = 'other'; }
        }
        unset($c);
    }
    
    if (empty($clients)) {
        // Fallback default list for demo/testing
        $clients = [
            ['id' => 1, 'name' => 'Andrea Zbojanová', 'email' => 'andrea.zbojanova@gmail.com', 'phone' => '0901 234 567', 'gender' => 'female', 'total_bookings' => 6, 'last_booking' => '2026-08-10'],
            ['id' => 2, 'name' => 'Lýdia Kováčová', 'email' => 'lydia.kovacova@centrum.sk', 'phone' => '0915 987 654', 'gender' => 'female', 'total_bookings' => 3, 'last_booking' => '2026-08-18'],
            ['id' => 3, 'name' => 'Ludvík Svoboda', 'email' => 'ludvik.svoboda@seznam.cz', 'phone' => '+420 777 123 456', 'gender' => 'male', 'total_bookings' => 4, 'last_booking' => '2026-08-08'],
            ['id' => 4, 'name' => 'Diana Nováková', 'email' => 'diana.novakova@gmail.com', 'phone' => '0944 111 222', 'gender' => 'female', 'total_bookings' => 8, 'last_booking' => '2026-08-12'],
            ['id' => 5, 'name' => 'Peter Molnár', 'email' => 'peter.molnar@gmail.com', 'phone' => '0902 333 444', 'gender' => 'male', 'total_bookings' => 2, 'last_booking' => '2026-08-01'],
            ['id' => 6, 'name' => 'Jana Šimková', 'email' => 'jana.simkova@azet.sk', 'phone' => '0918 555 666', 'gender' => 'female', 'total_bookings' => 12, 'last_booking' => '2026-08-14'],
            ['id' => 7, 'name' => 'Michal Horváth', 'email' => 'michal.horvath@post.sk', 'phone' => '0905 777 888', 'gender' => 'male', 'total_bookings' => 5, 'last_booking' => '2026-08-05'],
            ['id' => 8, 'name' => 'Zuzana Kráľová', 'email' => 'zuzka.kralova@gmail.com', 'phone' => '0948 999 000', 'gender' => 'female', 'total_bookings' => 7, 'last_booking' => '2026-08-11'],
            ['id' => 9, 'name' => 'Lukáš Varga', 'email' => 'lukas.varga@gmail.com', 'phone' => '0910 222 333', 'gender' => 'male', 'total_bookings' => 1, 'last_booking' => '2026-07-28'],
            ['id' => 10, 'name' => 'Elena Tóthová', 'email' => 'elena.toth@gmail.com', 'phone' => '0903 444 555', 'gender' => 'female', 'total_bookings' => 9, 'last_booking' => '2026-08-16'],
            ['id' => 11, 'name' => 'Beauty Salon s.r.o.', 'email' => 'partner@beautystudio.sk', 'phone' => '0912 666 777', 'gender' => 'other', 'total_bookings' => 15, 'last_booking' => '2026-08-17']
        ];
        // Demo dáta nemajú reálnu útratu — dopočítame orientačnú hodnotu, nech náhľad segmentácie nie je prázdny
        foreach ($clients as &$dc) { $dc['total_spent'] = $dc['total_bookings'] * 28.5; }
        unset($dc);
    }
    
    // Tag celebrants
    $today_month_day = date('m-d');
    $celebrants = [];
    $birthday_celebrants = [];
    foreach ($clients as &$c) {
        $c['celebrates_sk'] = is_name_in_nameday($c['name'], $namedays['sk_today']);
        $c['celebrates_cz'] = is_name_in_nameday($c['name'], $namedays['cz_today']);
        $c['celebrates_today'] = $c['celebrates_sk'];
        if ($c['celebrates_today']) {
            $celebrants[] = $c;
        }
        $c['has_birthday_today'] = !empty($c['birth_date']) && date('m-d', strtotime($c['birth_date'])) === $today_month_day;
        if ($c['has_birthday_today']) {
            $birthday_celebrants[] = $c;
        }
    }
    unset($c);

    // Templates list
    $templates = [
        [
            'id' => 'none',
            'name' => 'Iba text (bez grafiky)',
            'desc' => 'Základná e-mailová správa bez HTML šablóny',
            'icon' => 'description',
            'preview_img' => ''
        ],
        [
            'id' => 'nameday_bouquet',
            'name' => 'Blahoželanie k meninám (Kytica)',
            'desc' => 'Elegantné sviatočné prianie s kyticou kvetov (univerzálne / pre dámy)',
            'icon' => 'celebration',
            'preview_img' => 'libs/templates/images/nameday_bouquet.png'
        ],
        [
            'id' => 'nameday_whiskey',
            'name' => 'Blahoželanie k meninám (Whiskey a poháre)',
            'desc' => 'Prémiové blahoželanie k meninám (špeciálne pre pánov)',
            'icon' => 'liquor',
            'preview_img' => 'libs/templates/images/nameday_whiskey.png'
        ],
        [
            'id' => 'easter_slovak',
            'name' => 'Veľkonočný pozdrav (Slovenský motív)',
            'desc' => 'Jarné sviatočné prianie k Veľkej noci',
            'icon' => 'egg',
            'preview_img' => 'libs/templates/images/easter_slovak.png'
        ],
        [
            'id' => 'easter_full',
            'name' => 'Veľkonočný pozdrav (Jarná výzdoba)',
            'desc' => 'Jarné sviatočné prianie k Veľkej noci',
            'icon' => 'egg',
            'preview_img' => 'libs/templates/images/easter_full.png'
        ],
        [
            'id' => 'easter_korbac',
            'name' => 'Veľkonočný pozdrav (Korbáč a stužky)',
            'desc' => 'Jarné sviatočné prianie k Veľkej noci',
            'icon' => 'egg',
            'preview_img' => 'libs/templates/images/easter_korbac.png'
        ]
    ];

    // Predvolené šablóny, ktoré si táto prevádzka skryla (len pre ňu, ostatné ich vidia naďalej)
    $hidden_templates = [];
    if ($est_id > 0) {
        ensureHiddenTemplatesTable($conn);
        $hidden_stmt = $conn->prepare("SELECT template_key FROM establishment_hidden_templates WHERE establishment_id = ?");
        $hidden_stmt->bind_param("i", $est_id);
        $hidden_stmt->execute();
        $hidden_keys = [];
        $hidden_res = $hidden_stmt->get_result();
        while ($h = $hidden_res->fetch_assoc()) { $hidden_keys[$h['template_key']] = true; }
        $hidden_templates = array_values(array_filter($templates, fn($t) => !empty($hidden_keys[$t['id']])));
        $templates = array_values(array_filter($templates, fn($t) => empty($hidden_keys[$t['id']])));
    }

    // Vlastné šablóny prevádzky (vlastný nahraný obrázok)
    if ($est_id > 0) {
        ensureEstablishmentTemplatesTable($conn);
        $tpl_stmt = $conn->prepare("SELECT id, name, image_path FROM establishment_templates WHERE establishment_id = ? ORDER BY created_at DESC");
        $tpl_stmt->bind_param("i", $est_id);
        $tpl_stmt->execute();
        $tpl_res = $tpl_stmt->get_result();
        while ($t = $tpl_res->fetch_assoc()) {
            $templates[] = [
                'id' => 'custom_' . $t['id'],
                'name' => $t['name'],
                'desc' => 'Vlastná šablóna prevádzky',
                'icon' => 'image',
                'preview_img' => $t['image_path'],
                'is_custom' => true,
                'custom_id' => (int)$t['id']
            ];
        }
    }

    // UTM / Marketing leads — reálne priradenie zachytené pri prvej rezervácii zákazníka
    // prichádzajúceho cez odkaz s utm_* parametrami (viz profil.php + api/book_appointment.php)
    $utm_leads = [];
    if ($est_id > 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS utm_leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            establishment_id INT NOT NULL,
            customer_id INT NOT NULL,
            source VARCHAR(100) DEFAULT NULL,
            medium VARCHAR(100) DEFAULT NULL,
            campaign VARCHAR(150) DEFAULT NULL,
            landing_page VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_customer_est (customer_id, establishment_id),
            INDEX idx_est (establishment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $utm_stmt = $pdo->prepare("
            SELECT ul.created_at, u.full_name as name, ul.source, ul.medium, ul.campaign, ul.landing_page as page
            FROM utm_leads ul
            JOIN users u ON u.id = ul.customer_id
            WHERE ul.establishment_id = ?
            ORDER BY ul.created_at DESC
            LIMIT 100
        ");
        $utm_stmt->execute([$est_id]);
        foreach ($utm_stmt->fetchAll(PDO::FETCH_ASSOC) as $lead) {
            $lead['date'] = date('d.m.Y H:i', strtotime($lead['created_at']));
            unset($lead['created_at']);
            $utm_leads[] = $lead;
        }
    }

    echo json_encode([
        'success' => true,
        'establishment_name' => $est_name,
        'namedays' => [
            'today_key' => $namedays['today_key'],
            'sk_today' => $namedays['sk_today'],
            'cz_today' => $namedays['cz_today']
        ],
        'clients' => $clients,
        'celebrants' => $celebrants,
        'birthday_celebrants' => $birthday_celebrants,
        'templates' => $templates,
        'hidden_templates' => $hidden_templates,
        'utm_leads' => $utm_leads,
        'upcoming_reminders' => $est_id > 0 ? getUpcomingReminders($conn, $est_id) : []
    ]);
    exit;
}

// ── AUTOMATIZÁCIA POZDRAVOV (meniny/narodeniny/veľké sviatky) ──
if ($action === 'get_occasion_settings') {
    $stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka sa nenašla.']); exit; }

    echo json_encode(['success' => true, 'settings' => array_values(getOccasionSettings($conn, $est_id))]);
    exit;
}

if ($action === 'save_occasion_settings') {
    $stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka sa nenašla.']); exit; }

    ensureOccasionAutomationTable($conn);
    $rows = json_decode($_POST['settings'] ?? '[]', true) ?: [];
    $valid_keys = array_keys(getAllOccasionDefinitions());
    $valid_modes = ['manual', 'remind', 'auto'];
    $valid_reward_types = ['greeting', 'discount'];
    $valid_discount_types = ['percent', 'fixed'];

    $stmt = $conn->prepare("INSERT INTO occasion_automation
        (establishment_id, occasion_key, mode, lead_days, reward_type, discount_type, discount_value, discount_validity_days, message_template, template_id, template_id_female, template_id_male)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE mode=VALUES(mode), lead_days=VALUES(lead_days), reward_type=VALUES(reward_type),
            discount_type=VALUES(discount_type), discount_value=VALUES(discount_value),
            discount_validity_days=VALUES(discount_validity_days), message_template=VALUES(message_template),
            template_id=VALUES(template_id), template_id_female=VALUES(template_id_female), template_id_male=VALUES(template_id_male)");

    foreach ($rows as $r) {
        $key = $r['occasion_key'] ?? '';
        if (!in_array($key, $valid_keys, true)) continue;
        $mode = in_array($r['mode'] ?? '', $valid_modes, true) ? $r['mode'] : 'manual';
        $lead_days = max(1, min(30, (int)($r['lead_days'] ?? 1)));
        $reward_type = in_array($r['reward_type'] ?? '', $valid_reward_types, true) ? $r['reward_type'] : 'greeting';
        $discount_type = in_array($r['discount_type'] ?? '', $valid_discount_types, true) ? $r['discount_type'] : 'percent';
        $discount_value = max(0, (float)($r['discount_value'] ?? 10));
        $discount_validity_days = max(1, min(90, (int)($r['discount_validity_days'] ?? 14)));
        $message_template = trim($r['message_template'] ?? '') ?: null;
        $template_id = trim($r['template_id'] ?? 'none') ?: 'none';
        $template_id_female = trim($r['template_id_female'] ?? 'none') ?: 'none';
        $template_id_male = trim($r['template_id_male'] ?? 'none') ?: 'none';
        $stmt->bind_param("ississdissss", $est_id, $key, $mode, $lead_days, $reward_type, $discount_type, $discount_value, $discount_validity_days, $message_template, $template_id, $template_id_female, $template_id_male);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Nastavenia automatizácie boli uložené.']);
    exit;
}

// ── VLASTNÉ ŠABLÓNY (nahratý obrázok prevádzky) ──
if ($action === 'upload_template') {
    $stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka sa nenašla.']); exit; }

    $name = trim($_POST['name'] ?? '');
    if (!$name) { echo json_encode(['success' => false, 'error' => 'Zadajte názov šablóny.']); exit; }
    if (empty($_FILES['image']['tmp_name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Nahrajte obrázok.']); exit;
    }
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        echo json_encode(['success' => false, 'error' => 'Podporované sú len JPG, PNG a WEBP obrázky.']); exit;
    }
    $upload_dir = __DIR__ . '/../uploads/templates/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
    $filename = 'tpl_' . $est_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
        echo json_encode(['success' => false, 'error' => 'Nahranie obrázku zlyhalo.']); exit;
    }

    ensureEstablishmentTemplatesTable($conn);
    $image_path = 'uploads/templates/' . $filename;
    $ins = $conn->prepare("INSERT INTO establishment_templates (establishment_id, name, image_path) VALUES (?, ?, ?)");
    $ins->bind_param("iss", $est_id, $name, $image_path);
    $ins->execute();

    echo json_encode(['success' => true, 'message' => 'Šablóna bola vytvorená.', 'template' => ['id' => 'custom_' . $ins->insert_id, 'name' => $name, 'preview_img' => $image_path]]);
    exit;
}

if ($action === 'update_template') {
    $stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka sa nenašla.']); exit; }

    ensureEstablishmentTemplatesTable($conn);
    $tpl_id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if (!$name) { echo json_encode(['success' => false, 'error' => 'Zadajte názov šablóny.']); exit; }

    $chk = $conn->prepare("SELECT image_path FROM establishment_templates WHERE id = ? AND establishment_id = ?");
    $chk->bind_param("ii", $tpl_id, $est_id);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    if (!$existing) { echo json_encode(['success' => false, 'error' => 'Šablóna sa nenašla.']); exit; }

    $image_path = $existing['image_path'];
    if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            echo json_encode(['success' => false, 'error' => 'Podporované sú len JPG, PNG a WEBP obrázky.']); exit;
        }
        $upload_dir = __DIR__ . '/../uploads/templates/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        $filename = 'tpl_' . $est_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $old_full_path = __DIR__ . '/../' . $existing['image_path'];
            if (is_file($old_full_path)) { @unlink($old_full_path); }
            $image_path = 'uploads/templates/' . $filename;
        }
    }

    $upd = $conn->prepare("UPDATE establishment_templates SET name = ?, image_path = ? WHERE id = ? AND establishment_id = ?");
    $upd->bind_param("ssii", $name, $image_path, $tpl_id, $est_id);
    $upd->execute();

    echo json_encode(['success' => true, 'message' => 'Šablóna bola upravená.', 'template' => ['id' => 'custom_' . $tpl_id, 'name' => $name, 'preview_img' => $image_path]]);
    exit;
}

// template_id: "custom_<id>" zmaže vlastnú šablónu prevádzky natrvalo, akékoľvek iné (predvolené,
// napr. "nameday_bouquet") sa pre túto prevádzku len skryjú — ostatné prevádzky ich vidia naďalej
if ($action === 'delete_template') {
    $stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka sa nenašla.']); exit; }

    $template_id = trim($_POST['id'] ?? '');
    if (!$template_id || $template_id === 'none') { echo json_encode(['success' => false, 'error' => 'Túto šablónu nie je možné odstrániť.']); exit; }

    if (strpos($template_id, 'custom_') === 0) {
        ensureEstablishmentTemplatesTable($conn);
        $tpl_id = (int)substr($template_id, 7);
        $chk = $conn->prepare("SELECT image_path FROM establishment_templates WHERE id = ? AND establishment_id = ?");
        $chk->bind_param("ii", $tpl_id, $est_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row) { echo json_encode(['success' => false, 'error' => 'Šablóna sa nenašla.']); exit; }

        $del = $conn->prepare("DELETE FROM establishment_templates WHERE id = ? AND establishment_id = ?");
        $del->bind_param("ii", $tpl_id, $est_id);
        $del->execute();
        $full_path = __DIR__ . '/../' . $row['image_path'];
        if (is_file($full_path)) { @unlink($full_path); }

        echo json_encode(['success' => true, 'message' => 'Šablóna bola odstránená.']);
    } else {
        ensureHiddenTemplatesTable($conn);
        $ins = $conn->prepare("INSERT IGNORE INTO establishment_hidden_templates (establishment_id, template_key) VALUES (?, ?)");
        $ins->bind_param("is", $est_id, $template_id);
        $ins->execute();
        echo json_encode(['success' => true, 'message' => 'Predvolená šablóna bola pre vašu prevádzku skrytá.']);
    }
    exit;
}

if ($action === 'restore_default_templates') {
    $stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka sa nenašla.']); exit; }

    ensureHiddenTemplatesTable($conn);
    $del = $conn->prepare("DELETE FROM establishment_hidden_templates WHERE establishment_id = ?");
    $del->bind_param("i", $est_id);
    $del->execute();
    echo json_encode(['success' => true, 'message' => 'Predvolené šablóny boli obnovené.']);
    exit;
}

if ($action === 'unhide_template') {
    $stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    if (!$est_id) { echo json_encode(['success' => false, 'error' => 'Prevádzka sa nenašla.']); exit; }

    $template_id = trim($_POST['id'] ?? '');
    if (!$template_id) { echo json_encode(['success' => false, 'error' => 'Chýba šablóna.']); exit; }

    ensureHiddenTemplatesTable($conn);
    $del = $conn->prepare("DELETE FROM establishment_hidden_templates WHERE establishment_id = ? AND template_key = ?");
    $del->bind_param("is", $est_id, $template_id);
    $del->execute();
    echo json_encode(['success' => true, 'message' => 'Šablóna bola obnovená.']);
    exit;
}

// ── 2. AI ASSISTANT / REPHRASE (Groq LLaMA 3.3) ──
if ($action === 'ai_assist') {
    $mode = $_POST['mode'] ?? 'fix_grammar';
    $text = trim($_POST['text'] ?? '');
    
    if (empty($text)) {
        echo json_encode(['success' => false, 'error' => 'Text je prázdny']);
        exit;
    }

    $ai_credit = ai_credit_consume($conn, $user_id);
    if (!$ai_credit['ok']) {
        echo json_encode(['success' => false, 'error' => 'Minuli ste všetky AI kredity. Dokúpte si ďalšie v Peňaženke.', 'need_ai_credit' => true]);
        exit;
    }

    $groq_key = function_exists('groq_rotated_key') ? groq_rotated_key() : (defined('GROQ_API_KEY') ? GROQ_API_KEY : '');

    $system_prompt = "Si skúsený biznis asistent a copywriter v slovenskom portáli " . BRAND_NAME . ". Tvojou úlohou je pomáhať prevádzkam a salónom s komunikáciou so zákazníkmi. Reaguj vždy profesionálne, ľudsky a v bezchybnej slovenčine.
DÔLEŽITÉ PRAVIDLÁ:
1. Vráť VŽDY IBA výsledný text (e-mailovú správu).
2. NIKDY nepridávaj žiadne vysvetlivky, úvody, poznámky ani komentáre k tomu, čo si urobil.
3. Ak sa v texte nachádza placeholder {{NAME}}, zachovaj ho.";

    switch ($mode) {
        case 'fix_grammar':
            $user_prompt = "Oprav gramatické, štylistické a interpunkčné chyby v nasledujúcom texte. Vráť len opravený text:\n\n" . $text;
            break;
        case 'professional':
            $user_prompt = "Preformuluj nasledujúci text tak, aby znel formálne, vysoko profesionálne a úctivo, no zároveň zrozumiteľne. Zachovaj pôvodný zmysel:\n\n" . $text;
            break;
        case 'friendly':
            $user_prompt = "Preformuluj nasledujúci text do priateľského, vrelého a milého tónu pre zákazníka salónu/prevádzky. Zachovaj pôvodný zmysel:\n\n" . $text;
            break;
        case 'shorten':
            $user_prompt = "Stručne a výstižne skráť nasledujúci text tak, aby obsahoval len podstatu:\n\n" . $text;
            break;
        default:
            $user_prompt = "Vylepši nasledujúci text správy pre zákazníka:\n\n" . $text;
    }

    // Call Groq API
    $api_url = "https://api.groq.com/openai/v1/chat/completions";
    $messages = [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $user_prompt]
    ];
    $payload = [
        "model" => "openai/gpt-oss-120b",
        "messages" => $messages,
        "temperature" => 0.5,
        "max_tokens" => 1024
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $groq_key
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $http_code === 200) {
        $res_json = json_decode($response, true);
        $ai_text = $res_json['choices'][0]['message']['content'] ?? '';
        if (!empty($ai_text)) {
            echo json_encode(['success' => true, 'result' => trim($ai_text), 'ai_credits_remaining' => $ai_credit['remaining']]);
            exit;
        }
    }

    // Offline fallback if network fails
    $result = $text;
    if ($mode === 'fix_grammar') {
        $result = ucfirst($result);
    } elseif ($mode === 'professional') {
        $result = "Vážený zákazník,\n\ndovoľujeme si Vám v mene celého nášho tímu popriať všetko najlepšie k Vášmu dnešnému sviatku. Želáme Vám pevné zdravie, veľa osobných a pracovných úspechov.\n\nVeľmi si vážime Vašu priazeň a tešíme sa na Vašu ďalšiu návštevu.";
    } elseif ($mode === 'friendly') {
        $result = "Milý náš klient,\n\nk dnešným meninám Ti zo srdca prajeme krásny deň, veľa úsmevu, zdravia a skvelej nálady! \n\nSme veľmi radi, že patríš k našim stálym zákazníkom.";
    } elseif ($mode === 'shorten') {
        $result = "Všetko najlepšie k meninám, veľa zdravia a pohody Vám praje náš tím! Ďakujeme za Vašu priazeň.";
    }

    echo json_encode(['success' => true, 'result' => $result, 'ai_credits_remaining' => $ai_credit['remaining']]);
    exit;
}

// ── 3. SEND TEST EMAIL ──
if ($action === 'send_test') {
    $test_email = trim($_POST['test_email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Testovacia správa');
    $content = trim($_POST['content'] ?? '');
    $template_id = $_POST['template_id'] ?? 'none';
    
    if (empty($test_email)) {
        echo json_encode(['success' => false, 'error' => 'Zadajte platnú e-mailovú adresu pre test']);
        exit;
    }

    $pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS marketing_email_sender ENUM('rezervos','own') NOT NULL DEFAULT 'rezervos'");
    $stmt = $pdo->prepare("SELECT id, name, marketing_email_sender FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id = $est ? (int)$est['id'] : 0;
    $est_name = $est['name'] ?? BRAND_NAME;
    // Prevádzka si sama vyberá, či pozdravy/kampane idú z jej vlastnej pripojenej schránky, alebo z nášho účtu
    $sender_uid = (($est['marketing_email_sender'] ?? 'rezervos') === 'own') ? $user_id : null;

    $root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";

    $personalized = apply_marketing_placeholders($content, 'Jana Testovacia', 'Strihanie vlasov', date('Y-m-d', strtotime('-14 days')));
    $final_html = wrap_marketing_template($template_id, $personalized, 'Jana Testovacia', $est_name, $root_url, $conn, $est_id, $sender_uid);

    $sent = send_marketing_email($test_email, 'Testovací príjemca', "[TEST] " . $subject, $final_html, $sender_uid, $est_name);

    echo json_encode(['success' => $sent, 'message' => $sent ? "Testovací e-mail bol úspešne odoslaný na $test_email." : 'Odoslanie testovacieho e-mailu zlyhalo.']);
    exit;
}

// ── 4. SEND CAMPAIGN ──
if ($action === 'send_campaign') {
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $template_id = $_POST['template_id'] ?? 'none';
    $recipients_raw = $_POST['recipients'] ?? '[]';
    $recipients = json_decode($recipients_raw, true) ?: [];

    if (empty($recipients)) {
        echo json_encode(['success' => false, 'error' => 'Neboli vybraní žiadni príjemcovia']);
        exit;
    }
    if (empty($subject) || empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Predmet aj obsah správy sú povinné']);
        exit;
    }

    $pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS marketing_email_sender ENUM('rezervos','own') NOT NULL DEFAULT 'rezervos'");
    $stmt = $pdo->prepare("SELECT id, name, marketing_email_sender FROM establishments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    $est_id_for_send = $est ? (int)$est['id'] : 0;
    $est_name = $est['name'] ?? BRAND_NAME;
    $sender_uid = (($est['marketing_email_sender'] ?? 'rezervos') === 'own') ? $user_id : null;
    $root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";

    // Posledná služba a dátum návštevy pre {{SLUZBA}} / {{DATUM}} — dohľadané zo servera,
    // nie od klienta, aby sa nedali sfalšovať a aby boli aktuálne
    $recipient_ids = array_map(fn($r) => (int)($r['id'] ?? 0), $recipients);
    $last_visit_by_customer = [];
    if ($est_id_for_send > 0 && !empty($recipient_ids)) {
        $placeholders = implode(',', array_fill(0, count($recipient_ids), '?'));
        $lv_stmt = $pdo->prepare("
            SELECT b.customer_id, s.name as service_name, b.booking_date
            FROM bookings b
            LEFT JOIN services s ON s.id = b.service_id
            WHERE b.establishment_id = ? AND b.customer_id IN ($placeholders)
            ORDER BY b.booking_date DESC
        ");
        $lv_stmt->execute(array_merge([$est_id_for_send], $recipient_ids));
        foreach ($lv_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!isset($last_visit_by_customer[$row['customer_id']])) {
                $last_visit_by_customer[$row['customer_id']] = $row;
            }
        }
    }

    // Bezpečnostná poistka proti manipulácii zoznamu príjemcov z klienta — bez ohľadu na to,
    // čo príde v $_POST, nikdy sa neodošle nikomu, kto nedal súhlas so zasielaním marketingu,
    // ani nikomu, kto v skutočnosti nie je zákazníkom TEJTO prevádzky (inak by prihlásená
    // firma mohla poslať kampaň ľubovoľnému súhlasiacemu používateľovi na celej platforme)
    $consented_ids = [];
    if (!empty($recipient_ids) && $est_id_for_send > 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS marketing_consent TINYINT(1) NOT NULL DEFAULT 0");
        $placeholders_c = implode(',', array_fill(0, count($recipient_ids), '?'));
        $c_stmt = $pdo->prepare("
            SELECT DISTINCT u.id
            FROM users u
            JOIN bookings b ON b.customer_id = u.id AND b.establishment_id = ?
            WHERE u.id IN ($placeholders_c) AND u.marketing_consent = 1
        ");
        $c_stmt->execute(array_merge([$est_id_for_send], $recipient_ids));
        $consented_ids = array_flip(array_map('intval', $c_stmt->fetchAll(PDO::FETCH_COLUMN)));
    }

    $sent_count = 0;
    $skipped_no_consent = 0;
    foreach ($recipients as $r) {
        $r_id = (int)($r['id'] ?? 0);
        $r_name = trim($r['name'] ?? '');
        $r_email = trim($r['email'] ?? '');
        if (!$r_email) continue;
        if (!isset($consented_ids[$r_id])) { $skipped_no_consent++; continue; }

        $last = $last_visit_by_customer[$r_id] ?? null;
        $personalized = apply_marketing_placeholders($content, $r_name, $last['service_name'] ?? null, $last['booking_date'] ?? null);
        $final_html = wrap_marketing_template($template_id, $personalized, $r_name, $est_name, $root_url, $conn, $est_id_for_send, $sender_uid, $r_id);

        if (send_marketing_email($r_email, $r_name, $subject, $final_html, $sender_uid, $est_name)) { $sent_count++; }
    }

    // Save campaign record into DB
    try {
        $stmt = $pdo->prepare("INSERT INTO marketing_campaigns (user_id, subject, content, status, recipients_count) VALUES (?, ?, ?, 'sent', ?)");
        $stmt->execute([$user_id, $subject, $content, $sent_count]);
    } catch (Exception $e) {
        // Continue if table doesn't have identical schema
    }

    $consent_note = $skipped_no_consent > 0 ? " ($skipped_no_consent bez súhlasu so zasielaním boli automaticky vynechaní.)" : "";
    echo json_encode([
        'success' => true,
        'message' => "Hromadná e-mailová kampaň bola odoslaná pre $sent_count z " . count($recipients) . " príjemcov.$consent_note"
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Neznáma akcia']);
