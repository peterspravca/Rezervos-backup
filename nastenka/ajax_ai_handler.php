<?php
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$content = $input['content'] ?? '';
$context = $input['context'] ?? '';

if (empty($action) || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Missing action or content']);
    exit;
}

if (!defined('GROQ_API_KEY') || empty(GROQ_API_KEY)) {
    echo json_encode(['success' => false, 'error' => 'Groq API key not configured']);
    exit;
}

$system_prompt = "Si skúsený biznis asistent v slovenskej firme VUETO. Tvojou úlohou je pomáhať kolegom s komunikáciou. Reaguj vždy profesionálne, ľudsky a v slovenčine. 

DÔLEŽITÉ PRAVIDLÁ:
1. Vráť VŽDY IBA výsledný text (e-mail alebo poznámku). 
2. NIKDY nepridávaj žiadne vysvetlivky, poznámky pod čiarou ani komentáre k tomu, čo si urobil.
3. Nepoužívaj nadbytočné prázdne riadky medzi odsekmi (stačí jeden enter).
4. Ak píšeš e-mail, začni priamo oslovením a skonči pozdravom.";

switch ($action) {
    case 'generate_email':
        $prompt = "Na základe týchto bodov napíš profesionálny a priateľský e-mail zákazníkovi. Body: \n" . $content;
        if (!empty($context)) $prompt .= "\nKontext (predchádzajúca správa): " . $context;
        break;

    case 'rephrase':
        $tone = $input['tone'] ?? 'profesionálny';
        $prompt = "Preformuluj nasledujúci text tak, aby znel viac $tone, ale zachovaj pôvodný význam: \n" . $content;
        break;

    case 'summarize_notes':
        $prompt = "Tu je história poznámok a aktivít u zákazníka. Urob z nich stručný a jasný manažérsky súhrn (3-5 kľúčových bodov). Ak sú tam dôležité termíny alebo rozpočty, zvýrazni ich. Poznámky: \n" . $content;
        break;

    case 'fix_grammar':
        $prompt = "Oprav gramatické a štylistické chyby v nasledujúcom texte. Vráť len opravený text bez ďalšieho komentára: \n" . $content;
        break;

    case 'general_chat':
        $prompt = "Pomôž kolegovi s touto požiadavkou: \n" . $content;
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
}

// Groq API Call (OpenAI compatible)
$api_url = "https://api.groq.com/openai/v1/chat/completions";

$messages = [["role" => "system", "content" => $system_prompt]];

if ($action === 'general_chat' && !empty($input['history']) && is_array($input['history'])) {
    foreach ($input['history'] as $h) {
        $messages[] = ["role" => $h['role'], "content" => $h['content']];
    }
}

$messages[] = ["role" => "user", "content" => $prompt];

$data = [
    "model" => "llama-3.3-70b-versatile",
    "messages" => $messages,
    "temperature" => 0.6,
    "max_tokens" => 2048,
    "top_p" => 1
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . GROQ_API_KEY
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => 'Curl error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);

if ($http_code !== 200) {
    $err_msg = $result['error']['message'] ?? 'Unknown Groq error';
    echo json_encode(['success' => false, 'error' => 'Groq API error: ' . $err_msg]);
    exit;
}

$ai_text = $result['choices'][0]['message']['content'] ?? '';

if (empty($ai_text)) {
    echo json_encode(['success' => false, 'error' => 'No response from AI']);
    exit;
}

echo json_encode(['success' => true, 'result' => trim($ai_text)]);
