<?php
// api/ai_chat.php
require_once '../config.php';
require_once '../includes/ai_helper.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';

header('Content-Type: application/json');

// Povolenie iba POST požiadaviek
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Iba POST požiadavky sú povolené.']);
    exit;
}

// Načítanie dát z požiadavky
$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? '';

if (empty($prompt)) {
    echo json_encode(['error' => 'Správa nesmie byť prázdna.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zatiaľ nebudeme obmedzovať otázky, alebo môžeme obmedziť ako v bete.
// Kontrola limitov pre používateľov
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    $stmt = $conn->prepare("SELECT ai_queries_today, ai_last_query_date FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['ai_last_query_date'] !== $today) {
            $queries = 0;
        } else {
            $queries = (int)$row['ai_queries_today'];
        }
        
        if ($queries >= 3) {
            echo json_encode(['error' => 'Dosiahli ste denný limit pre AI otázky (max 3 denne). Skúste to opäť zajtra.']);
            exit;
        }
        
        $new_queries = $queries + 1;
        $stmt_update = $conn->prepare("UPDATE users SET ai_queries_today = ?, ai_last_query_date = ? WHERE id = ?");
        $stmt_update->bind_param("isi", $new_queries, $today, $user_id);
        $stmt_update->execute();
    }
} else {
    // Neregistrovaný používateľ
    if (isset($_SESSION['ai_guest_count']) && $_SESSION['ai_guest_count'] >= 1) {
        echo json_encode(['error' => 'Neregistrovaní používatelia môžu položiť iba jednu otázku. Pre viac otázok sa prosím zaregistrujte.']);
        exit;
    }
    $_SESSION['ai_guest_count'] = 1;
}

$brand = BRAND_NAME;
$system_prompt = "Si AI Asistent pre inzertný a rezervačný portál {$brand}.
Tvoja slovenčina musí byť absolútne dokonalá, prirodzená, gramaticky správna a priateľská.

DÔLEŽITÉ FORMÁTOVANIE (STRIKTNÉ PRAVIDLÁ):
1. NIKDY nepoužívaj Markdown formátovanie (žiadne hviezdičky '**' pre tučné písmo).
2. Na zvýraznenie textu použi výhradne HTML tag <strong>...</strong> (napr. <strong>dôležité</strong>).
3. Pre odriadkovanie použi HTML tag <br>.
4. Pre zoznamy použi klasické HTML tagy <ul> a <li>.

DÔLEŽITÉ PRAVIDLÁ A OBMEDZENIA:
1. Pomáhaš používateľom výhradne s hľadaním služieb krásy a zdravia na portáli {$brand}.
2. NIKDY neposkytuješ externé služby.
3. Ak sa používateľ spýta na niečo off-topic, slušne vysvetli, že si AI asistent pre portál {$brand} a pomáhaš s hľadaním kaderníctiev, barberov, kozmetiky atď.
4. Tvojou hlavnou úlohou je pomôcť používateľom zorientovať sa a poradiť im.

HLAVNÉ KATEGÓRIE NA PORTÁLI {$brand}:
- Barber a Kaderníctvo
- Kozmetika a Vizáž
- Nechty a Manikúra
- Masáže a Spa

ŠTÝL KOMUNIKÁCIE:
- Odpovedaj stručne, priateľsky, profesionálne a prehľadne.
- Zameraj sa na krásu, oddych a starostlivosť o zovňajšok.
- Píš výhradne ČISTOU SLOVENČINOU.";

$ai_response = generateAiContent($prompt, $system_prompt);

// Dodatočné poistenie pre prípad, že by AI predsa len skĺzla do Markdownu
$ai_response = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $ai_response);
$ai_response = preg_replace('/\* (.+?)(?:\n|<br>|$)/', '<li>$1</li>', $ai_response);
$ai_response = str_replace("\n", "<br>", $ai_response);

echo json_encode(['response' => $ai_response]);
?>
