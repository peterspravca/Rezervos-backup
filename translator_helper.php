<?php
// includes/translator_helper.php
require_once __DIR__ . '/config.php';

/**
 * Preloží slovenský text do cieľového jazyka a uloží ho do cache databázy.
 * Ak je cieľový jazyk 'sk', vráti pôvodný text.
 * 
 * @param string $text Pôvodný slovenský text
 * @param string|null $target_lang Voliteľný kód cieľového jazyka (predvolene $current_lang)
 * @return string Preložený text
 */
function t_migrate($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS translation_cache (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            original_hash CHAR(32) NOT NULL,
            lang VARCHAR(8) NOT NULL,
            translated_text TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_hash_lang (original_hash, lang)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
}

// Krátkodobá medzi-requestová poistka: keď na jednom requeste zlyhajú úplne všetky poskytovatelia
// (Groq pool aj DeepL aj z.ai), ďalších pár desiatok sekúnd to na živých stránkach neskúšame znova
// (to by pri výpadku/vyčerpanej kvóte blokovalo každý ďalší request na desiatky sekúnd) — proste
// vrátime pôvodný text a preklad dobehne cez cron_translate_content.php/cron_translate_static.php.
// $api_offline nižšie rieši len opakovanie v RÁMCI jedného requestu, nie naprieč requestami.
define('TRANSLATE_OFFLINE_FLAG_FILE', sys_get_temp_dir() . '/rezervos_translate_offline.flag');
define('TRANSLATE_OFFLINE_COOLDOWN_SEC', 90);

function translate_providers_recently_exhausted() {
    $f = TRANSLATE_OFFLINE_FLAG_FILE;
    if (!is_file($f)) return false;
    return (time() - (int)@filemtime($f)) < TRANSLATE_OFFLINE_COOLDOWN_SEC;
}

function mark_translate_providers_exhausted() {
    @file_put_contents(TRANSLATE_OFFLINE_FLAG_FILE, (string)time());
}

function t($text, $target_lang = null) {
    global $pdo, $current_lang, $api_offline, $t_last_debug;

    if (empty($text)) {
        return '';
    }

    t_migrate($pdo);

    // Ak nie je cieľový jazyk zadaný, použijeme zistený z konfigurácie
    if ($target_lang === null) {
        $target_lang = $current_lang ?? 'sk';
    }

    // Zoznam precíznych lokálnych prekladov pre jednotný a bezchybný branding (AVE Hunter <=> AVE Hunter)
    $manual_translations = [
        'sk' => [
            'AVE Hunter' => 'AVE Hunter',
            'AVE Hunter Bot' => 'AVE Hunter Bot',
            'Zaktivuj si AVE Huntera' => 'Zaktivuj si AVE Huntera',
            'Prečo si aktivovať AVE Hunter?' => 'Prečo si aktivovať AVE Huntera?',
            'Chyba načítania AVE Hunter panelu.' => 'Chyba načítania panelu AVE Hunter.',
            'AVE Hunter - Hunter ponúk' => 'AVE Hunter - Hunter ponúk',
            '🏹 AVE Hunter: Máme nový úlovok pre revír: ' => '🏹 AVE Hunter: Máme nový úlovok pre revír: ',
            'AVE Hunter hlási úspešný lov!' => 'AVE Hunter hlási úspešný lov!',
            'Tento e-mail bol zaslaný na základe vašich nastavených filtrov AVE Hunter.' => 'Tento e-mail bol zaslaný na základe vašich nastavených filtrov AVE Hunter.',
            'AVE Hunter AI Matchmaker' => 'AVE Hunter AI Matchmaker',
            'AVE Hunter bol úspešne aktivovaný pre administrátora.' => 'AVE Hunter bol úspešne aktivovaný pre administrátora.',
            'Aktivovali ste si 30-dňové bezplatné skúšobné obdobie AVE Hunter!' => 'Aktivovali ste si 30-dňové bezplatné skúšobné obdobie AVE Huntera!',
            'AVE Hunter bol úspešne aktivovaný alebo predĺžený o ďalších 30 dní!' => 'AVE Hunter bol úspešne aktivovaný alebo predĺžený o ďalších 30 dní!',
            'Naozaj si želáte aktivovať alebo predĺžiť AVE Hunter premium na 30 dní za poplatok 3,00 € z vášho kreditu?' => 'Naozaj si želáte aktivovať alebo predĺžiť AVE Hunter premium na 30 dní za poplatok 3,00 € z vášho kreditu?',
            'Vytvorte si svoj prvý filter (napr. kľúčové slová, kategóriu alebo cenu) a AVE Hunter začne ihneď loviť za vás.' => 'Vytvorte si svoj prvý filter (napr. kľúčové slová, kategóriu alebo cenu) a AVE Hunter začne ihneď loviť za vás.',
            'Nehľadajte ponuky ručne. Nechajte AVE Huntera nonstop skenovať portál za vás. Hneď ako sa objaví inzerát, ktorý hľadáte, Hunter vám ho uloví a pošle priamo do schránky.' => 'Nehľadajte ponuky ručne. Nechajte AVE Huntera nonstop skenovať portál za vás. Hneď ako sa objaví inzerát, ktorý hľadáte, Hunter vám ho uloví a pošle priamo do schránky.',
            'Hunter nemôže strieľať naslepo, musí vedieť, po čom ide. Používateľ si na portáli nastaví svoje „Revíry“ (napr. Kategória: Osobné autá, Značka: BMW, Cena do 15 000 €, Lokalita: Žilinský kraj). AVE Hunter nespí, nonstop filtruje databázu a akonáhle sa objaví presná zhoda, okamžite „vystrelí“ upozornenie.' => 'Hunter nemôže strieľať naslepo, musí vedieť, po čom ide. Používateľ si na portáli nastaví svoje „Revíry“ (napr. Kategória: Osobné autá, Značka: BMW, Cena do 15 000 €, Lokalita: Žilinský kraj). AVE Hunter nespí, nonstop filtruje databázu a akonáhle sa objaví presná zhoda, okamžite „vystrelí“ upozornenie.',
            'Lov nekončí len pridaním nového inzerátu. Dobrý Hunter sleduje korisť aj spätne. Ak si používateľ uloží inzerát medzi obľúbené, AVE Hunter ho začne monitorovať. Keď predajca zníži cenu (napr. z 500 € na 450 €), Hunter okamžite posiela echo záujemcovi: „Cena klesla! Tvoj vysnívaný kúsok je lacnejší.“' => 'Lov nekončí len pridaním nového inzerátu. Dobrý Hunter sleduje korisť aj spätne. Ak si používateľ uloží inzerát medzi obľúbené, AVE Hunter ho začne monitorovať. Keď predajca zníži cenu (napr. z 500 € na 450 €), Hunter okamžite posiela echo záujemcovi: „Cena klesla! Tvoj vysnívaný kúsok je lacnejší.“',
            'Pokročilejšia funkcia pre moderný portál: Hunter môže spájať dopyt s ponukou. Ak niekto pridá inzerát typu „Hľadám na kúpu dodávku do 5000 €“ a o tri dni niekto iný pridá inzerát „Predám dodávku za 4800 €“, AVE Hunter ich automaticky prepojí a obom napíše: „Našiel som pre vás ideálnu zhodu.“' => 'Pokročilejšia funkcia pre moderný portál: Hunter môže spájať dopyt s ponukou. Ak niekto pridá inzerát typu „Hľadám na kúpu dodávku do 5000 €“ a o tri dni niekto iný pridá inzerát „Predám dodávku za 4800 €“, AVE Hunter ich automaticky prepojí a obom napíše: „Našiel som pre vás ideálnu zhodu.“',
            'AVE Hunter: AI párovanie! 🤖' => 'AVE Hunter: AI párovanie! 🤖',
            'AVE Hunter: Nový úlovok! 🏹' => 'AVE Hunter: Nový úlovok! 🏹',
            'Cenový stopár (Price Tracking)' => 'Cenový stopár (Sledovanie cien)',
            'Vyjednávač (AI Matchmaking)' => 'Vyjednávač (AI Párovanie)',
            'Premium' => 'Premium',
            'Peňaženka' => 'Peňaženka',
            'Moja Peňaženka' => 'Moja Peňaženka',
        ],
        'en' => [
            'AVE Hunter' => 'AVE Hunter',
            'AVE Hunter Bot' => 'AVE Hunter Bot',
            'Zaktivuj si AVE Huntera' => 'Activate AVE Hunter',
            'Prečo si aktivovať AVE Hunter?' => 'Why activate AVE Hunter?',
            'Chyba načítania AVE Hunter panelu.' => 'Error loading AVE Hunter panel.',
            'AVE Hunter - Hunter ponúk' => 'AVE Hunter - Deal Hunter',
            'Peňaženka' => 'Wallet',
            'Moja Peňaženka' => 'My Wallet',
            '🏹 AVE Hunter: Máme nový úlovok pre revír: ' => '🏹 AVE Hunter: New catch in your forest: ',
            'AVE Hunter hlási úspešný lov!' => 'AVE Hunter reports a successful catch!',
            'Tento e-mail bol zaslaný na základe vašich nastavených filtrov AVE Hunter.' => 'This email was sent based on your AVE Hunter filters.',
            'AVE Hunter AI Matchmaker' => 'AVE Hunter AI Matchmaker',
            'AVE Hunter bol úspešne aktivovaný pre administrátora.' => 'AVE Hunter has been successfully activated for administrator.',
            'Aktivovali ste si 30-dňové bezplatné skúšobné obdobie AVE Hunter!' => 'You have activated your 30-day free trial of AVE Hunter!',
            'AVE Hunter bol úspešne aktivovaný alebo predĺžený o ďalších 30 dní!' => 'AVE Hunter has been successfully activated or extended for another 30 days!',
            'Naozaj si želáte aktivovať alebo predĺžiť AVE Hunter premium na 30 dní za poplatok 3,00 € z vášho kreditu?' => 'Are you sure you want to activate or extend AVE Hunter premium for 30 days for €3.00 from your credit?',
            'Vytvorte si svoj prvý filter (napr. kľúčové slová, kategóriu alebo cenu) a AVE Hunter začne ihneď loviť za vás.' => 'Create your first filter (e.g. keywords, category or price) and AVE Hunter will immediately start hunting for you.',
            'Nehľadajte ponuky ručne. Nechajte AVE Huntera nonstop skenovať portál za vás. Hneď ako sa objaví inzerát, ktorý hľadáte, Hunter vám ho uloví a pošle priamo do schránky.' => 'Don\'t search for offers manually. Let AVE Hunter scan the portal nonstop for you. As soon as the ad you are looking for appears, Hunter will hunt it down for you and send it directly to your inbox.',
            'Hunter nemôže strieľať naslepo, musí vedieť, po čom ide. Používateľ si na portáli nastaví svoje „Revíry“ (napr. Kategória: Osobné autá, Značka: BMW, Cena do 15 000 €, Lokalita: Žilinský kraj). AVE Hunter nespí, nonstop filtruje databázu a akonáhle sa objaví presná zhoda, okamžite „vystrelí“ upozornenie.' => 'A hunter cannot shoot blindly, he must know what he is going after. The user sets up his "hunting forests" on the portal (e.g., Category: Cars, Brand: BMW, Price up to €15,000, Location: Žilina Region). AVE Hunter never sleeps, filters database nonstop, and as soon as an exact match appears, immediately sends a notification.',
            'Lov nekončí len pridaním nového inzerátu. Dobrý Hunter sleduje korisť aj spätne. Ak si používateľ uloží inzerát medzi obľúbené, AVE Hunter ho začne monitorovať. Keď predajca zníži cenu (napr. z 500 € na 450 €), Hunter okamžite posiela echo záujemcovi: „Cena klesla! Tvoj vysnívaný kúsok je lacnejší.“' => 'Hunting doesn\'t end with adding a new ad. A good hunter tracks prey retrospectively. If a user saves an ad to favorites, AVE Hunter starts monitoring it. When the seller drops the price (e.g., from €500 to €450), Hunter immediately sends an echo to the interested party: "Price dropped! Your dream piece is cheaper."',
            'Pokročilejšia funkcia pre moderný portál: Hunter môže spájať dopyt s ponukou. Ak niekto pridá inzerát typu „Hľadám na kúpu dodávku do 5000 €“ a o tri dni niekto iný pridá inzerát „Predám dodávku za 4800 €“, AVE Hunter ich automaticky prepojí a obom napíše: „Našiel som pre vás ideálnu zhodu.“' => 'Advanced feature for a modern portal: Hunter can connect demand with supply. If someone adds a wanted ad like "Looking to buy a van up to €5000" and three days later someone else adds "Selling a van for €4800", AVE Hunter will automatically connect them and write to both: "I found an ideal match for you."',
            'AVE Hunter: AI párovanie! 🤖' => 'AVE Hunter: AI Match! 🤖',
            'AVE Hunter: Nový úlovok! 🏹' => 'AVE Hunter: New catch! 🏹',
            'Personalizovaný lov (Nastavenie filtrov)' => 'Personalized Hunt (Filter Settings)',
            'Cenový stopár (Price Tracking)' => 'Price Tracker (Price Tracking)',
            'Vyjednávač (AI Matchmaking)' => 'Negotiator (AI Matchmaking)',
            'Pokročilý Antispam Bot' => 'Advanced Antispam Bot',
            'Chráni vás pred podvodníkmi. Náš antispam bot nepretržite skenuje inzeráty na vulgárne slová, škodlivé linky (graph.org, bit.ly, t.me), okamžite ich likviduje a chráni vašu bezpečnosť.' => 'Protects you from scammers. Our antispam bot continuously scans ads for profanity, harmful links (graph.org, bit.ly, t.me), instantly eliminates them and protects your safety.',
            'Premium' => 'Premium',
        ]
    ];

    if (isset($manual_translations[$target_lang][$text])) {
        $translated_text = $manual_translations[$target_lang][$text];
        
        // Uložiť aj manuálny preklad do cache, aby cron vedel, že už je spracovaný
        $original_hash = md5($text);
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO translation_cache (original_hash, lang, translated_text) VALUES (?, ?, ?)");
            $stmt->execute([$original_hash, $target_lang, $translated_text]);
        } catch (Exception $e) {}

        return $translated_text;
    }
    
    // Ak je cieľovým jazykom slovenčina, okamžite vrátime pôvodný text
    if ($target_lang === 'sk') {
        return $text;
    }
    
    // Spočítať MD5 hash slovenského textu
    $hash = md5($text);
    
    // Ak neprekladáme do slovenčiny, rozbalíme bežné európske skratky
    if ($target_lang !== 'sk') {
        $expanded_text = expand_common_abbreviations($text);
    } else {
        $expanded_text = $text;
    }

    // 1. Krok: VŽDY najprv skontrolovať preklad v našej lokálnej databázovej cache
    // OPTIMALIZÁCIA: Načítame preklady do pamäte pre daný jazyk, aby sme ušetrili stovky SQL dopytov.
    static $memory_cache = [];
    
    if (!isset($memory_cache[$target_lang])) {
        try {
            $stmt = $pdo->prepare("SELECT original_hash, translated_text FROM translation_cache WHERE lang = ?");
            $stmt->execute([$target_lang]);
            $memory_cache[$target_lang] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if ($memory_cache[$target_lang] === false) {
                $memory_cache[$target_lang] = [];
            }
        } catch (Exception $e) {
            error_log("Chyba hromadného čítania prekladov z cache: " . $e->getMessage());
            $memory_cache[$target_lang] = [];
        }
    }

    if (isset($memory_cache[$target_lang][$hash])) {
        return $memory_cache[$target_lang][$hash];
    }
    
    // 2. Krok: Zavoláme Groq AI (nie Google Translate — to na tomto hostingu naráža na limit 429)
    $translated_text = '';

    // Ak API zlyhalo v predchádzajúcom kroku tohto behu skriptu, nebudeme to skúšať znova
    if (!empty($api_offline)) {
        return $text;
    }

    // Ak úplne všetci poskytovatelia zlyhali nedávno (aj na inom requeste), neblokujeme túto
    // živú stránku ďalším pokusom — počkáme na cooldown a medzitým to dobehne cez cron.
    if (translate_providers_recently_exhausted()) {
        $api_offline = true;
        return $text;
    }

    try {
        $t_used_api = true;
        // Prekladáme ROZBALENÝ text, ale do cache uložíme pod hash PÔVODNÉHO textu
        $translated_text = groq_translate_text($expanded_text, $target_lang, $call_debug);
        if ($translated_text === null) {
            $translated_text = '';
            $t_last_debug = $call_debug;
            // Označiť prekladové API ako nedostupné pre zostávajúce preklady na tejto stránke
            $api_offline = true;
            mark_translate_providers_exhausted();
        }
    } catch (Exception $e) {
        error_log("Chyba pri volaní prekladového API: " . $e->getMessage());
        $api_offline = true;
        mark_translate_providers_exhausted();
    }
    
    // Ak preklad z nejakého dôvodu zlyhal, použijeme pôvodný slovenský text ako zálohu
    if (empty($translated_text)) {
        $translated_text = $text;
    } else {
        // 3. Krok: Uložiť úspešný preklad do databázovej cache pre budúce bleskové načítanie
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO translation_cache (original_hash, lang, translated_text) VALUES (?, ?, ?)");
            $stmt->execute([$hash, $target_lang, $translated_text]);
            // Aktualizujeme aj pamäťovú cache
            $memory_cache[$target_lang][$hash] = $translated_text;
        } catch (Exception $e) {
            error_log("Chyba pri ukladaní prekladu do cache: " . $e->getMessage());
        }
    }
    
    return $translated_text;
}

const TRANSLATE_LANG_NAMES = [
    'sk' => 'Slovak', 'cz' => 'Czech', 'en' => 'English', 'de' => 'German',
    'pl' => 'Polish', 'hu' => 'Hungarian', 'ua' => 'Ukrainian',
];

/**
 * Zavolá chat-completion API (Groq alebo z.ai — obe majú OpenAI-kompatibilný tvar) a vráti
 * preložený text, alebo null pri zlyhaní.
 */
function _translate_via_chat_api($url, $api_key, $model, $text, $lang_name, $timeout = 10, $connect_timeout = 4, &$debug_info = null) {
    $system_prompt = "You are a precise translation engine. Translate the given text from Slovak to $lang_name. "
        . "Return ONLY the translated text — no quotes, no explanations, no extra commentary, preserve line breaks.";

    // Groq si pri KAŽDOM volaní rezervuje celý max_tokens voči dennému TPD kvótu (200 000/deň na free tieri),
    // bez ohľadu na skutočnú dĺžku odpovede — preto max_tokens musí byť čo najbližšie reálnej potrebe textu,
    // inak jeden krátky preklad zožerie rovnaké množstvo kvóty ako dlhý odsek.
    $max_tokens = max(80, min(2048, (int)(mb_strlen($text) * 3) + 60));

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $text],
            ],
            'temperature' => 0.3,
            'max_tokens' => $max_tokens,
        ]),
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $connect_timeout,
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ok = ($response !== false && $http_code === 200);
    curl_close($ch);

    if (!$ok) {
        $debug_info = "errno=$errno error=\"$error\" http_code=$http_code body=" . substr((string)$response, 0, 300);
        return null;
    }

    $data = json_decode($response, true);
    $out = trim($data['choices'][0]['message']['content'] ?? '');
    if ($out === '') {
        $debug_info = "prazdna odpoved, body=" . substr((string)$response, 0, 300);
        return null;
    }
    return $out;
}

// Naše interné kódy jazykov -> kódy cieľového jazyka podľa DeepL API
const DEEPL_LANG_CODES = [
    'cz' => 'CS', 'en' => 'EN-US', 'de' => 'DE',
    'pl' => 'PL', 'hu' => 'HU', 'ua' => 'UK',
];

/**
 * Zavolá DeepL API Free (skutočné prekladové API, nie chat model — presnejšie a bez "reasoning" réžie).
 */
function _translate_via_deepl($text, $target_lang, &$debug_info = null) {
    $deepl_lang = DEEPL_LANG_CODES[$target_lang] ?? null;
    if ($deepl_lang === null) {
        $debug_info = "jazyk $target_lang nie je podporovaný DeepL mapou";
        return null;
    }

    $ch = curl_init(DEEPL_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: DeepL-Auth-Key ' . DEEPL_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'text' => [$text],
            'source_lang' => 'SK',
            'target_lang' => $deepl_lang,
        ]),
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        $debug_info = "errno=$errno error=\"$error\" http_code=$http_code body=" . substr((string)$response, 0, 300);
        return null;
    }

    $data = json_decode($response, true);
    $out = trim($data['translations'][0]['text'] ?? '');
    if ($out === '') {
        $debug_info = "prazdna odpoved, body=" . substr((string)$response, 0, 300);
        return null;
    }
    return $out;
}

/**
 * Preloží text zo slovenčiny do cieľového jazyka. Skúsi najprv Groq AI (rýchlejšie, hlavné),
 * potom DeepL (kvalitné skutočné prekladové API), a napokon z.ai — používateľ chybu uvidí len ak zlyhajú všetky tri.
 */
function groq_translate_text($text, $target_lang, &$debug = null) {
    if (trim((string)$text) === '') return '';

    $lang_name = TRANSLATE_LANG_NAMES[$target_lang] ?? $target_lang;

    $groq_debug = null;
    $deepl_debug = null;
    $zai_debug = null;

    // Skúsime celý fond Groq kľúčov (každý zo samostatného účtu s vlastnou dennou kvótou) —
    // ak jeden účet práve narazil na denný limit (HTTP 429), okamžite skúsime ďalší v tom istom behu.
    if (defined('GROQ_API_KEYS_POOL') && !empty(GROQ_API_KEYS_POOL)) {
        $pool = GROQ_API_KEYS_POOL;
        // Začneme od dnešného "hlavného" kľúča, nie vždy od prvého — rovnomernejšie rozloženie záťaže
        $start = defined('GROQ_API_KEY') ? array_search(groq_rotated_key(), $pool) : 0;
        if ($start === false) $start = 0;
        $count = count($pool);
        for ($i = 0; $i < $count; $i++) {
            $key = $pool[($start + $i) % $count];
            $out = _translate_via_chat_api(GROQ_API_URL, $key, 'openai/gpt-oss-120b', $text, $lang_name, 10, 4, $groq_debug);
            if ($out !== null) return $out;
            // Pokračujeme na ďalší kľúč len pri rate-limite (429) — pri inej chybe (napr. výpadok Groq) nemá zmysel skúšať ostatné
            if (strpos((string)$groq_debug, 'http_code=429') === false) break;
        }
    } elseif (defined('GROQ_API_KEY') && !empty(GROQ_API_KEY)) {
        // gpt-oss-120b je "reasoning" model — premýšľa dlhšie než klasický model, potrebuje viac než pár sekúnd
        $out = _translate_via_chat_api(GROQ_API_URL, GROQ_API_KEY, 'openai/gpt-oss-120b', $text, $lang_name, 10, 4, $groq_debug);
        if ($out !== null) return $out;
    }

    // Groq zlyhal (napr. vyčerpaná denná kvóta) — skúsime DeepL, skutočné prekladové API bez tokenových limitov ako u Groq
    if (defined('DEEPL_API_KEY') && !empty(DEEPL_API_KEY)) {
        $out = _translate_via_deepl($text, $target_lang, $deepl_debug);
        if ($out !== null) return $out;
    }

    // Ak zlyhá aj DeepL, posledná záloha je z.ai, potichu, bez chyby pre používateľa.
    // Krátky timeout: z.ai je z tohto hostingu momentálne nedostupné (timeout), netreba čakať dlho na istú prehru.
    if (defined('ZAI_API_KEY') && !empty(ZAI_API_KEY)) {
        $out = _translate_via_chat_api(ZAI_API_URL, ZAI_API_KEY, 'glm-4.5-flash', $text, $lang_name, 4, 3, $zai_debug);
        if ($out !== null) return $out;
    }

    $debug = "Zlyhali všetky AI (Groq, DeepL aj z.ai). Groq: [$groq_debug] DeepL: [$deepl_debug] z.ai: [$zai_debug]";
    return null;
}

/**
 * Rozbalí bežné európske inzertné skratky (SK, CZ, DE, EN) na plné slová pre lepší preklad
 * 
 * @param string $text Pôvodný text
 * @return string Text s rozbalenými skratkami
 */
function expand_common_abbreviations($text) {
    if (empty($text)) return $text;
    
    // POZOR: PHP/PCRE \b (hranica slova) berie do úvahy iba ASCII písmená [A-Za-z0-9_] —
    // slovenské diakritické znaky (č, š, ž...) sa preň NEPOČÍTAJÚ ako súčasť slova. To znamená,
    // že napr. \bOBO\b by sa omylom zhodlo aj vnútri slova "Obočie" (za "Obo" nasleduje "č",
    // čo PCRE bez /u a \p{L} vníma ako koniec slova). Preto tu namiesto \b používame explicitné
    // Unicode-vedomé hranice (?<![\p{L}\p{N}]) a (?![\p{L}\p{N}]) s modifikátorom /u.
    $abbreviations = [
        // SK / CZ
        '/(?<![\p{L}\p{N}])TPP(?![\p{L}\p{N}])/iu' => 'Trvalý pracovný pomer',
        '/(?<![\p{L}\p{N}])HPP(?![\p{L}\p{N}])/iu' => 'Hlavný pracovný pomer',
        '/(?<![\p{L}\p{N}])VZV(?![\p{L}\p{N}])/iu' => 'Vysokozdvižný vozík',
        '/(?<![\p{L}\p{N}])SZČO(?![\p{L}\p{N}])/iu' => 'Živnosť (SZČO)',
        '/(?<![\p{L}\p{N}])OSVČ(?![\p{L}\p{N}])/iu' => 'Živnosť (OSVČ)',
        '/(?<![\p{L}\p{N}])STK(?![\p{L}\p{N}])/iu' => 'Stanica technickej kontroly',
        '/(?<![\p{L}\p{N}])EK(?![\p{L}\p{N}])/iu' => 'Emisná kontrola',
        '/(?<![\p{L}\p{N}])DPH(?![\p{L}\p{N}])/iu' => 'Daň z pridanej hodnoty',
        '/(?<![\p{L}\p{N}])MHD(?![\p{L}\p{N}])/iu' => 'Mestská hromadná doprava',
        '/(?<![\p{L}\p{N}])ZŤP(?![\p{L}\p{N}])/iu' => 'Zdravotne ťažko postihnutý',
        '/(?<![\p{L}\p{N}])SRO(?![\p{L}\p{N}])/iu' => 'Spoločnosť s ručením obmedzeným',
        '/(?<![\p{L}\p{N}])s\.r\.o\.(?![\p{L}\p{N}])/iu' => 'Spoločnosť s ručením obmedzeným',
        '/(?<![\p{L}\p{N}])a\.s\.(?![\p{L}\p{N}])/iu' => 'Akciová spoločnosť',
        '/(?<![\p{L}\p{N}])RD(?![\p{L}\p{N}])/iu' => 'Rodinný dom',
        '/(?<![\p{L}\p{N}])NZ(?![\p{L}\p{N}])/iu' => 'Novostavba',
        '/(?<![\p{L}\p{N}])RK(?![\p{L}\p{N}])/iu' => 'Realitná kancelária',

        // DE
        '/(?<![\p{L}\p{N}])LKW(?![\p{L}\p{N}])/iu' => 'Lastkraftwagen (Nákladné auto)',
        '/(?<![\p{L}\p{N}])PKW(?![\p{L}\p{N}])/iu' => 'Personenkraftwagen (Osobné auto)',
        '/(?<![\p{L}\p{N}])TÜV(?![\p{L}\p{N}])/iu' => 'Technischer Überwachungsverein (STK)',
        '/(?<![\p{L}\p{N}])MwSt(?![\p{L}\p{N}])/iu' => 'Mehrwertsteuer (DPH)',
        '/(?<![\p{L}\p{N}])EZ(?![\p{L}\p{N}])/iu' => 'Erstzulassung (Prvá registrácia)',
        '/(?<![\p{L}\p{N}])HU(?![\p{L}\p{N}])/iu' => 'Hauptuntersuchung (Hlavná prehliadka)',
        '/(?<![\p{L}\p{N}])VB(?![\p{L}\p{N}])/iu' => 'Verhandlungsbasis (Dohoda možná)',

        // EN
        '/(?<![\p{L}\p{N}])ASAP(?![\p{L}\p{N}])/iu' => 'As Soon As Possible (Čo najskôr)',
        '/(?<![\p{L}\p{N}])VAT(?![\p{L}\p{N}])/iu' => 'Value Added Tax (DPH)',
        '/(?<![\p{L}\p{N}])OBO(?![\p{L}\p{N}])/iu' => 'Or Best Offer (Alebo najlepšia ponuka)',
        '/(?<![\p{L}\p{N}])SUV(?![\p{L}\p{N}])/iu' => 'Sport Utility Vehicle'
    ];

    return preg_replace(array_keys($abbreviations), array_values($abbreviations), $text);
}

/**
 * Zoznam všetkých krajín sveta v slovenčine s podporou dynamic localization
 */
function get_all_countries($include_all = false) {
    $countries = [
        'SK' => t('Slovensko'),
        'CZ' => t('Česko'),
        'DE' => t('Nemecko'),
        'AT' => t('Rakúsko'),
        'PL' => t('Poľsko'),
        'HU' => t('Maďarsko'),
        'BE' => t('Belgicko'),
        'BG' => t('Bulharsko'),
        'CY' => t('Cyprus'),
        'DK' => t('Dánsko'),
        'EE' => t('Estónsko'),
        'FI' => t('Fínsko'),
        'FR' => t('Francúzsko'),
        'GR' => t('Grécko'),
        'NL' => t('Holandsko'),
        'HR' => t('Chorvátsko'),
        'IE' => t('Írsko'),
        'LT' => t('Litva'),
        'LV' => t('Lotyšsko'),
        'LU' => t('Luxembursko'),
        'MT' => t('Malta'),
        'PT' => t('Portugalsko'),
        'RO' => t('Rumunsko'),
        'SI' => t('Slovinsko'),
        'ES' => t('Španielsko'),
        'CH' => t('Švajčiarsko'),
        'SE' => t('Švédsko'),
        'IT' => t('Taliansko'),
        'UA' => t('Ukrajina'),
        'GB' => t('Veľká Británia')
    ];

    if ($include_all) {
        return array_merge(['ALL' => t('Celý portál')], $countries);
    }
    return $countries;
}
?>