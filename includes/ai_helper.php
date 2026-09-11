<?php
// includes/ai_helper.php
require_once __DIR__ . '/../config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php';

/**
 * Generuje obsah pomocou Groq AI (Llama 3)
 *
 * @param string $prompt Otázka alebo pokyn pre AI
 * @param string $system_prompt Nastavenie správania AI
 * @return string Odpoveď od AI
 */
function generateAiContent($prompt, $system_prompt = null) {
    if ($system_prompt === null) {
        $system_prompt = "Si " . BRAND_NAME . " – inteligentný asistent pre salóny krásy. Vždy odpovedaj v rovnakom jazyku, v akom ti píše užívateľ.";
    }
    if (!defined('GROQ_API_KEY') || empty(GROQ_API_KEY)) {
        return "Chyba: Groq API kľúč nie je nastavený v config.php.";
    }

    $data = [
        'model' => 'openai/gpt-oss-120b', // Groq vyradil Llama modely — nahradené za gpt-oss-120b
        'messages' => [
            [
                'role' => 'system',
                'content' => $system_prompt
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1024
    ];

    $ch = curl_init(GROQ_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . (function_exists('groq_rotated_key') ? groq_rotated_key() : GROQ_API_KEY)
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return "Chyba pripojenia (cURL): " . $error_msg;
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }

    return "Chyba AI: Nepodarilo sa získať text. Odpoveď servera: " . $response;
}
?>
