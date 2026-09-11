<?php
// includes/profanity_filter.php

/**
 * Normalizuje text: odstráni diakritiku, konvertuje na malé písmená a nahradí leetspeak znaky.
 */
function normalize_text_for_filter($text) {
    if (empty($text)) return '';

    // Odstránenie diakritiky (fallback, ak Transliterator nie je dostupný)
    $search = ['á','ä','č','ď','é','í','ľ','ĺ','ň','ó','ô','ŕ','š','ť','ú','ý','ž','Á','Č','Ď','É','Í','Ľ','Ĺ','Ň','Ó','Ô','Ŕ','Š','Ť','Ú','Ý','Ž'];
    $replace = ['a','a','c','d','e','i','l','l','n','o','o','r','s','t','u','y','z','a','c','d','e','i','l','l','n','o','o','r','s','t','u','y','z'];
    $normalized = str_replace($search, $replace, mb_strtolower($text, 'UTF-8'));

    // Ochrana pred leetspeak obchádzaním (napr. k0k0t -> kokot, p1ca -> pica, f*ck -> fuck)
    $leetspeak = [
        '0' => 'o',
        '1' => 'i',
        '3' => 'e',
        '4' => 'a',
        '5' => 's',
        '8' => 'b',
        '7' => 't',
        '@' => 'a',
        '!' => 'i',
        '$' => 's',
        '*' => 'u' // napr. f*ck -> fuck
    ];
    $normalized = str_replace(array_keys($leetspeak), array_values($leetspeak), $normalized);

    // Odstránenie prebytočných interpunkčných znamienok pre plynulú kontrolu slov
    $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

    return $normalized;
}

/**
 * Skontroluje, či text obsahuje nepovolené/vulgárne slová vo viacerých jazykoch (SK, CZ, PL, EN, DE).
 * Vracia true, ak je nájdené vulgárne slovo, inak false.
 */
function has_profanity($text) {
    if (empty($text)) return false;

    $normalized = normalize_text_for_filter($text);

    // Multijazyčný slovník vulgarizmov (SK, CZ, PL, EN, DE)
    $blacklist = [
        // --- SLOVENSKÉ & ČESKÉ & POĽSKÉ ---
        'kokot', 'kokotina', 'kokotiny', 'kokoti', 'skokot', 'pokot',
        'kurva', 'kurvy', 'kurvam', 'kurvami', 'skurv', 'skurvysyn', 'skurven',
        'pica', 'pici', 'pice', 'picovina', 'picoviny',
        'jebat', 'jebnut', 'jebly', 'jebem', 'jebe', 'ojeb', 'vyjeb', 'odjeb', 'jebacka', 'zjeb',
        'chuj', 'chuje', 'chujovina',
        'suka', 'suky', 'sukin',
        'debil', 'debilny', 'debilita', 'debili', 'debilko',
        'zmrd', 'zmrdi', 'zmrdov',
        'drbnut', 'drbo', 'drbnuty',
        'curak', 'curaci', 'curakom', 'prdel', 'hovno', 'hovna',
        'spierdalaj', 'pierdole',
        'kkt', 'pci', 'jbt', // skratky

        // --- ANGLICKÉ ---
        'fuck', 'fucking', 'fucker', 'fuckers', 'motherfuck', 'motherfucker',
        'shit', 'shitty', 'bitch', 'bitches', 'cunt', 'cunts',
        'asshole', 'assholes', 'bastard', 'bastards', 'whore', 'whores', 'slut', 'sluts',
        'crap', 'dick', 'dicks', 'pussy', 'pussies',

        // --- NEMECKÉ ---
        'scheisse', 'scheiss', 'arsch', 'arschloch', 'arschlocher',
        'schlampe', 'schlampen', 'hurensohn', 'hurensohne', 'wichser'
    ];

    // Rozdelenie na jednotlivé slová
    $words = preg_split('/\s+/', $normalized);

    foreach ($blacklist as $bad_word) {
        // 1. Presná zhoda celého slova
        if (in_array($bad_word, $words)) {
            return true;
        }

        // 2. Podreťazcová zhoda (pre nebezpečné korene slov na zamedzenie obchádzania príponami)
        $substring_triggers = [
            'kokot', 'kurv', 'picov', 'jebn', 'vyjeb', 'skurv', 'ojeba', 'odjeb',
            'fuck', 'motherfuck', 'scheiss', 'arschlo', 'curak', 'hurensoh'
        ];
        foreach ($substring_triggers as $trigger) {
            if (strpos($normalized, $trigger) !== false) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Kontroluje, či meno používateľa nevykazuje spamové/bot vlastnosti (odkazy, domény, nepovolené znaky, atď.)
 */
function is_invalid_username($username) {
    if (empty($username)) return false;

    $normalized = mb_strtolower(trim($username), 'UTF-8');
    $original   = trim($username);

    // 1. Ochrana pred linkami/schémami a špecifickými bot znakmi
    $invalid_patterns = [
        'http://', 'https://', 'www.', '@', '=>>', '->', 'graph.org', 't.me', 'bit.ly', 'tinyurl'
    ];
    foreach ($invalid_patterns as $pattern) {
        if (strpos($normalized, $pattern) !== false) {
            return true;
        }
    }

    // 2. Kontrola regulárnym výrazom na prítomnosť domén a lomiek
    if (preg_match('/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.(com|org|net|info|xyz|ru|ua|biz|co|io|in|us|uk|pl|cz|sk|me)(:[0-9]{1,5})?(\/.*)?/i', $normalized)) {
        return true;
    }

    // Lomky alebo spätné lomky
    if (strpos($normalized, '/') !== false || strpos($normalized, '\\') !== false) {
        return true;
    }

    // 3. BOT DETEKCIA: Náhodne generované mená (napr. "iLWmsicBSHtLWzVSvAen")
    // Príznaky: príliš veľa striedaní veľkých a malých písmen bez prirodzeného slova
    $len = mb_strlen($original, 'UTF-8');
    if ($len >= 6) {
        // Spočítame, koľkokrát sa zmení veľkosť písmena (veľké -> malé alebo naopak)
        $switches = 0;
        $prev_upper = null;
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($original, $i, 1, 'UTF-8');
            if (!preg_match('/\p{L}/u', $char)) continue; // preskočíme nealfabetické znaky
            $is_upper = ($char === mb_strtoupper($char, 'UTF-8') && $char !== mb_strtolower($char, 'UTF-8'));
            if ($prev_upper !== null && $is_upper !== $prev_upper) {
                $switches++;
            }
            $prev_upper = $is_upper;
        }
        // Ak je viac ako 40% znakov "prepnutých" => náhodný reťazec (bot)
        // Ľudské meno ako "Ján Kováč" má max 1-2 prepnutia (na začiatku slov)
        if ($switches > ($len * 0.40)) {
            return true;
        }

        // 4. BOT DETEKCIA: Žiadna medzera a viac ako 12 znakov bez akejkoľvek samohlásky skupiny
        // Normálne slovenské/európske meno má vždy medzeru (meno priezvisko)
        $has_space = (strpos($original, ' ') !== false);
        if (!$has_space && $len > 12) {
            // Skontroluj pomer samohlások – reálne mená majú aspoň 25% samohlások
            $vowels = preg_match_all('/[aeiouáäéíóôúýAEIOUÁÄÉÍÓÔÚÝ]/u', $original);
            $vowel_ratio = $vowels / $len;
            if ($vowel_ratio < 0.20) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Skontroluje rate limit registrácií z jednej IP adresy.
 * Vracia true, ak IP prekročila povolený počet pokusov (3 za hodinu).
 */
function is_registration_rate_limited($pdo, $ip) {
    try {
        // Vytvoríme tabuľku, ak neexistuje
        $pdo->exec("CREATE TABLE IF NOT EXISTS registration_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip_address),
            INDEX idx_time (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Zmažeme staré záznamy (staršie ako 1 hodina)
        $pdo->exec("DELETE FROM registration_attempts WHERE attempted_at < NOW() - INTERVAL 1 HOUR");

        // Spočítame pokusy z tejto IP za poslednú hodinu
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM registration_attempts WHERE ip_address = ? AND attempted_at > NOW() - INTERVAL 1 HOUR");
        $stmt->execute([$ip]);
        $count = (int)$stmt->fetchColumn();

        return ($count >= 3);
    } catch (\Exception $e) {
        return false; // V prípade chyby neblokujeme
    }
}

/**
 * Zaznamená pokus o registráciu z danej IP.
 */
function log_registration_attempt($pdo, $ip) {
    try {
        $stmt = $pdo->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)");
        $stmt->execute([$ip]);
    } catch (\Exception $e) {}
}

/**
 * Skontroluje, či meno používateľa alebo premium handle neobsahuje chránené označenie "aveino"
 * alebo podozrivú kombináciu so slovom "ave", ktorá by mohla evokovať oficiálneho zástupcu platformy
 * (napr. ave_specialist, avespecialista, avepodpora, ave_podpora, ave-admin, atd.)
 */
function is_brand_protected_username($name) {
    if (empty($name)) return false;
    
    $normalized = mb_strtolower(trim($name), 'UTF-8');
    
    // 1. Zákaz slova "aveino" v akejkoľvek forme (prísna ochrana značky)
    if (strpos($normalized, 'aveino') !== false) {
        return true;
    }
    
    // 2. Samostatné slovo "ave" (presná zhoda)
    if ($normalized === 'ave') {
        return true;
    }
    
    // 3. Kombinácie "ave" s oddelovačmi nasledované administratívnymi alebo reprezentatívnymi slovami
    // napr. ave_specialista, ave-admin, ave_support, ave_help, atď.
    $admin_terms = [
        'admin', 'support', 'help', 'contact', 'wallet', 'hunter', 'shop', 'info', 'team', 'tim',
        'specialista', 'specialist', 'podpora', 'moderator', 'mod', 'aveino', 'ave', 'official', 'oficialny'
    ];
    
    // Zákaz začiatku na "ave" nasledovaného oddelovačom alebo priamym admin slovom
    // e.g. "ave_admin", "ave-support", "aveadmin", "avesupport", "avepodpora", "ave-specialista"
    foreach ($admin_terms as $term) {
        if ($normalized === 'ave' . $term || 
            $normalized === 'ave_' . $term || 
            $normalized === 'ave-' . $term || 
            $normalized === $term . 'ave' || 
            $normalized === $term . '_ave' || 
            $normalized === $term . '-ave') {
            return true;
        }
    }
    
    // Zákaz "ave" ako samostatného slova obklopeného oddelovačmi
    // napr. "moje-ave-meno", "ave-specialist"
    if (preg_match('/\bave\b/u', $normalized)) {
        // Ak sa "ave" vyskytuje ako samostatné slovo (napr. "ave specialista", "ave podpora")
        // Skontrolujeme, či je v reťazci prítomné nejaké z admin slov
        foreach ($admin_terms as $term) {
            if (strpos($normalized, $term) !== false) {
                return true;
            }
        }
    }
    
    // 4. Samostatné zakázané slová, ktoré evokujú administráciu portálu (presná zhoda)
    $forbidden_exact = [
        'admin', 'administrator', 'support', 'help', 'contact', 'info', 'team', 'tim',
        'specialista', 'specialist', 'podpora', 'moderator', 'mod', 'official', 'oficialny',
        'system', 'root', 'superuser', 'majitel', 'owner', 'riaditel', 'spravca', 'manager', 
        'security', 'bezpecnost', 'aveinoteam', 'aveinosupport'
    ];
    
    if (in_array($normalized, $forbidden_exact)) {
        return true;
    }
    
    return false;
}
