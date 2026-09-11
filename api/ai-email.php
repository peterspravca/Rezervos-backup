<?php
require_once '../config.php';
require_once '../includes/ai_credit_helper.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nie ste prihlásený.']); exit;
}

$ai_credits_remaining = null;
if (($_SESSION['user_role'] ?? '') === 'business') {
    $ai_credit = ai_credit_consume($conn, (int)$_SESSION['user_id']);
    if (!$ai_credit['ok']) {
        echo json_encode(['success' => false, 'error' => 'Minuli ste všetky AI kredity. Dokúpte si ďalšie v Peňaženke.', 'need_ai_credit' => true]); exit;
    }
    $ai_credits_remaining = $ai_credit['remaining'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Neplatná metóda.']); exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$action  = $input['action']  ?? '';
$content = $input['content'] ?? '';
$history = $input['history'] ?? [];
$context = $input['context'] ?? '';   // obsah emailu pre kontext

if (empty($action) || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Chýba akcia alebo obsah.']); exit;
}

if (!defined('GROQ_API_KEY') || empty(GROQ_API_KEY)) {
    echo json_encode(['success' => false, 'error' => 'Groq API kľúč nie je nakonfigurovaný.']); exit;
}

// Systémový prompt pre asistenta
$system_prompt = "Si inteligentný asistent salóna " . BRAND_NAME . ". Pomáhaš majiteľom a zamestnancom salónov krásy s ich každodennou komunikáciou — e-mailmi, odpoveďami zákazníkom a textami. Reaguješ vždy profesionálne, priateľsky a v slovenčine.

DÔLEŽITÉ PRAVIDLÁ:
1. Vráť VŽDY IBA výsledný text. Žiadne vysvetlivky, komentáre ani poznámky navyše.
2. Ak píšeš e-mail, začni priamo oslovením (Dobrý deň, Vážený...) a skonči pozdravom (S pozdravom...).
3. Nepoužívaj zbytočné prázdne riadky — jeden enter medzi odsekmi stačí.
4. Tón: profesionálny ale ľudský, vhodný pre salón krásy.";

switch ($action) {

    case 'general_chat':
        $prompt = $content;
        if (!empty($context) && count($history) === 0) {
            $prompt = "Kontext e-mailu:\n" . $context . "\n\nPožiadavka: " . $content;
        }
        break;

    case 'generate_reply':
        $prompt = "Napíš profesionálnu odpoveď na nasledujúci e-mail. Buď ľudský, priateľský a stručný.";
        if (!empty($context)) $prompt .= "\n\nPôvodný e-mail:\n" . $context;
        if (!empty($content))  $prompt .= "\n\nPokyny pre odpoveď: " . $content;
        break;

    case 'generate_email':
        $prompt = "Na základe týchto bodov napíš profesionálny e-mail zákazníkovi salóna. Body:\n" . $content;
        if (!empty($context)) $prompt .= "\n\nKontext (predchádzajúci e-mail): " . $context;
        break;

    case 'rephrase':
        $tone  = $input['tone'] ?? 'profesionálny';
        $prompt = "Preformuluj nasledujúci text tak, aby znel viac {$tone}, ale zachovaj pôvodný zmysel. Vráť len preformulovaný text:\n" . $content;
        break;

    case 'shorten':
        $prompt = "Skráť nasledujúci text — zachovaj kľúčové informácie, odstráň zbytočné slová. Vráť len skrátený text:\n" . $content;
        break;

    case 'fix_grammar':
        $prompt = "Oprav gramatické a štylistické chyby v nasledujúcom texte. Vráť len opravený text:\n" . $content;
        break;

    case 'summarize_email':
        $prompt = "Urob stručné zhrnutie nasledujúceho e-mailu — čo odosielateľ chce, aké sú prípadné termíny alebo akcie potrebné zo strany salóna. Max 3-5 viet:\n" . $content;
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Neznáma akcia.']); exit;
}

// Zostav históriu správ
$messages = [["role" => "system", "content" => $system_prompt]];

if ($action === 'general_chat' && !empty($history)) {
    foreach ($history as $h) {
        $role = ($h['role'] === 'user' || $h['role'] === 'assistant') ? $h['role'] : 'user';
        $messages[] = ["role" => $role, "content" => $h['content']];
    }
}

$messages[] = ["role" => "user", "content" => $prompt];

// Groq API volanie
$data = [
    "model"       => "openai/gpt-oss-120b",
    "messages"    => $messages,
    "temperature" => 0.6,
    "max_tokens"  => 2048,
    "top_p"       => 1
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($data),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . (function_exists('groq_rotated_key') ? groq_rotated_key() : GROQ_API_KEY)
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $err = curl_error($ch); curl_close($ch);
    echo json_encode(['success' => false, 'error' => 'Chyba siete: ' . $err]); exit;
}
curl_close($ch);

$result = json_decode($response, true);

if ($http_code !== 200) {
    $msg = $result['error']['message'] ?? 'Neznáma chyba Groq.';
    echo json_encode(['success' => false, 'error' => 'AI chyba: ' . $msg]); exit;
}

$ai_text = trim($result['choices'][0]['message']['content'] ?? '');

if (empty($ai_text)) {
    echo json_encode(['success' => false, 'error' => 'AI nevrátila žiadnu odpoveď.']); exit;
}

echo json_encode(['success' => true, 'result' => $ai_text, 'ai_credits_remaining' => $ai_credits_remaining]);
