<?php
/**
 * Verejný endpoint pre dopyty z webovej stránky.
 * Capture UTM parameters and other lead data.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/notifications.php';
require_once __DIR__ . '/libs/mailer.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Povoľte dopyty z vašej webstránky
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$pdo = db_connect();

// Bezpečnostná kontrola (cez Header alebo POST)
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? '';
if ($apiKey !== WEB_LEAD_KEY) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');
$city     = trim($_POST['city'] ?? '');
$message  = trim($_POST['message'] ?? '');
$intent   = trim($_POST['intent'] ?? 'kontaktovanie');

// UTM Parametre
$utm_source   = trim($_POST['utm_source'] ?? '');
$utm_medium   = trim($_POST['utm_medium'] ?? '');
$utm_campaign = trim($_POST['utm_campaign'] ?? '');
$utm_term     = trim($_POST['utm_term'] ?? '');
$utm_content  = trim($_POST['utm_content'] ?? '');
$page_url     = trim($_POST['page_url'] ?? '');

if (empty($name) || (empty($email) && empty($phone))) {
    echo json_encode(['success' => false, 'error' => 'Missing mandatory fields (name, email/phone)']);
    exit;
}

// Detekcia pohlavia
function detect_gender_web(string $name): string {
    $lower = mb_strtolower($name);
    $companyTerms = ['s.r.o', 'sro', 'a.s.', ' as', 'spol.', 'o.z.', ' oz', 'n.o.', 'n.f.', 'v.o.s', 'vos', 'k.s.', ' s.p.', ' sp ', 'obec', 'mesto', 'zdruze', 'nadac'];
    foreach ($companyTerms as $term) { if (str_contains($lower, $term)) return 'other'; }
    $parts = explode(' ', $lower);
    $lastPart = end($parts);
    if (str_ends_with($lastPart, 'ová') || str_ends_with($lastPart, 'á')) return 'female';
    if (count($parts) > 0) {
        $first = $parts[0];
        if (str_ends_with($first, 'a') && !in_array($first, ['jan', 'benjamin', 'kristian', 'adrian', 'nesta', 'luca', 'toma'])) return 'female';
        return 'male';
    }
    return 'unknown';
}

$gender = detect_gender_web($name);

try {
    $stmt = $pdo->prepare("INSERT INTO leads 
        (name, email, phone, address, city, message, intent, gender, utm_source, utm_medium, utm_campaign, utm_term, utm_content, page_url, source, status, priority, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'web', 'novy', 'stredna', NOW())");
    
    $stmt->execute([
        $name, $email, $phone, $address, $city, $message, $intent, $gender, 
        $utm_source, $utm_medium, $utm_campaign, $utm_term, $utm_content, $page_url
    ]);
    
    $new_id = $pdo->lastInsertId();
    
    // 1. Interná notifikácia v CRM
    create_notification(0, "Nový dopyt: $name", "Prišiel nový dopyt z webu cez $utm_source. Záujem: $intent", "contact.php?id=$new_id", "ti-user-plus");
    
    // 2. Emailová notifikácia pre firmu (Zjednodušená verzia)
    $subject = "Nový dopyt z webu: $name";
    $html = "Máte nový dopyt z webu.<br><br>";
    $html .= "--- ÚDAJE ZÁKAZNÍKA ---<br>";
    $html .= "<b>Meno:</b> $name<br>";
    $html .= "<b>Email:</b> $email<br>";
    $html .= "<b>Telefón:</b> $phone<br><br>";
    $html .= "--- ZÁUJEM ---<br>";
    $html .= "<b>Typ:</b> $intent<br><br>";
    $html .= "<b>Správa/Poznámka:</b><br>" . nl2br(htmlspecialchars($message)) . "<br><br>";
    $html .= "--- UTM PARAMETRE ---<br>";
    $html .= "<b>Zdroj:</b> $utm_source<br>";
    $html .= "<b>Médium:</b> $utm_medium<br>";
    $html .= "<b>Kampaň:</b> $utm_campaign<br>";
    $html .= "<b>Stránka (URL):</b> $page_url<br><br>";
    $html .= "---<br>";
    $html .= "<a href='https://" . $_SERVER['HTTP_HOST'] . "/nastenka/contact.php?id=$new_id'>Zobraziť detail v CRM</a>";
    
    send_crm_notification(OFFICE_EMAIL, $subject, wrap_email_content($subject, $html));

    echo json_encode(['success' => true, 'id' => $new_id]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}
