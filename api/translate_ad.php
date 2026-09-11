<?php
// api/translate_ad.php — okamžitý preklad jedného inzerátu na požiadanie (tlačidlo "Preložiť do..." na inzerat.php)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/content_translation_helper.php';
require_once __DIR__ . '/../translator_helper.php'; // groq_translate_text()

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Iba POST požiadavky sú povolené.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ad_id = (int)($input['ad_id'] ?? 0);
$target_lang = trim($input['target_lang'] ?? '');
$allowed = ['cz', 'en', 'de', 'pl', 'hu', 'ua'];

if (!$ad_id || !in_array($target_lang, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Neplatná požiadavka.']);
    exit;
}

$stmt = $conn->prepare("SELECT title, description FROM classifieds WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param("i", $ad_id);
$stmt->execute();
$ad = $stmt->get_result()->fetch_assoc();
if (!$ad) {
    echo json_encode(['success' => false, 'error' => 'Inzerát nebol nájdený.']);
    exit;
}

// Groq AI namiesto Google Translate — to na tomto hostingu naráža na HTTP 429 limit.
$debug_info = '';
$translated_title = groq_translate_text($ad['title'], $target_lang, $debug_info);
$translated_description = groq_translate_text($ad['description'], $target_lang, $debug_info);

if ($translated_title === null || $translated_description === null) {
    echo json_encode(['success' => false, 'error' => 'Prekladová služba je momentálne preťažená, skúste to prosím o chvíľu znova.']);
    exit;
}

// Uložíme aj do zdieľanej cache, nech to cron aj ostatné zobrazenia inzerátu už nemusia prekladať znova
ct_migrate($conn);
foreach ([['title', $ad['title'], $translated_title], ['description', $ad['description'], $translated_description]] as $f) {
    [$field, $source, $translated] = $f;
    if (trim((string)$source) === '') continue;
    $hash = md5($source);
    $ins = $conn->prepare("INSERT INTO content_translations (entity_type, entity_id, field, lang, source_hash, translated_text) VALUES ('classified', ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE source_hash = VALUES(source_hash), translated_text = VALUES(translated_text)");
    $ins->bind_param("issss", $ad_id, $field, $target_lang, $hash, $translated);
    $ins->execute();
}

$lang_names_sk = ['cz' => 'čeština', 'en' => 'angličtina', 'de' => 'nemčina', 'pl' => 'poľština', 'hu' => 'maďarčina', 'ua' => 'ukrajinčina'];

echo json_encode([
    'success' => true,
    'translated_title' => trim($translated_title),
    'translated_description' => trim($translated_description),
    'source_lang_name' => $lang_names_sk[$target_lang] ?? $target_lang,
]);
