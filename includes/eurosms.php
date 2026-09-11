<?php
/**
 * EuroSMS API klient — odosielanie SMS cez https://as.eurosms.com/api/v3/send/one
 * Dokumentácia: EuroSMS s.r.o. - SMS technické rozhranie, Issue 3.1.15 / 1.8.2020
 *
 * Používa Integračné ID a Kľúč (EUROSMS_IID / EUROSMS_KEY v config.php) — NIKDY prihlasovacie
 * meno/heslo na portál eurosms.com, tie sa na API nedajú použiť.
 *
 * Obmedzenie: posiela sa výhradne na slovenské (+421) a české (+420) čísla — iné destinácie
 * zatiaľ nemáme cenovo podložené (pozri poznámku v api/wallet.php $SMS_PACKAGES).
 */

require_once __DIR__ . '/../config.php';

class EuroSmsException extends Exception {}

/**
 * Normalizuje telefónne číslo do medzinárodného tvaru bez '+' (napr. 421903622237).
 * Akceptuje vstupy: 0903622237, +421903622237, 00421903622237, 903622237 (SK/CZ bez predvoľby).
 * Vráti null, ak číslo nie je platné SK/CZ mobilné číslo.
 */
function eurosms_normalize_number($raw) {
    $n = preg_replace('/[^0-9+]/', '', trim((string)$raw));
    if ($n === '') return null;

    if (strpos($n, '+') === 0) {
        $n = substr($n, 1);
    } elseif (strpos($n, '00') === 0) {
        $n = substr($n, 2);
    }

    // Už v medzinárodnom tvare (421.../420...)
    if (preg_match('/^(421|420)9\d{8}$/', $n) || preg_match('/^(421|420)[67]\d{8}$/', $n)) {
        return $n;
    }

    // Národný tvar bez predvoľby krajiny (0903622237 alebo 903622237) — nevieme rozlíšiť SK/CZ,
    // v takom prípade zavolajúci musí explicitne dodať predvoľbu (napr. z profilu prevádzky/zákazníka).
    if (preg_match('/^0?([679]\d{8})$/', $n, $m)) {
        return null; // vyžaduje explicitnú krajinu — pozri eurosms_normalize_number_with_country()
    }

    return null;
}

/** Rovnaké ako vyššie, ale s explicitnou predvoľbou krajiny pre národné čísla bez prefixu. */
function eurosms_normalize_number_with_country($raw, $countryPrefix = '421') {
    $n = preg_replace('/[^0-9+]/', '', trim((string)$raw));
    if ($n === '') return null;

    if (strpos($n, '+') === 0) $n = substr($n, 1);
    elseif (strpos($n, '00') === 0) $n = substr($n, 2);

    if (preg_match('/^(421|420)[679]\d{8}$/', $n)) return $n;

    if (preg_match('/^0?([679]\d{8})$/', $n, $m)) {
        return $countryPrefix . $m[1];
    }

    return null;
}

/** Vypočíta HMAC_SHA1 digitálny podpis podľa 4.2 Výpočet digitálneho podpisu. */
function eurosms_signature($sender, $recipient, $text, $key) {
    $base = $sender . $recipient . $text;
    return hash_hmac('sha1', $base, $key);
}

/**
 * Odošle jednu SMS. Vracia pole ['success' => bool, 'uuid' => string|null, 'error' => string|null].
 *
 * @param string $to           Telefónne číslo príjemcu (medzinárodný alebo národný tvar SK/CZ).
 * @param string $text         Text správy.
 * @param string|null $sender  Meno odosielateľa (max 11 znakov). Ak null, použije sa predvolené "REZERVOS".
 * @param string $countryHint  'SK' alebo 'CZ' — použije sa len ak $to nemá medzinárodnú predvoľbu.
 */
function eurosms_send_sms($to, $text, $sender = null, $countryHint = 'SK') {
    if (empty(EUROSMS_IID) || empty(EUROSMS_KEY)) {
        return ['success' => false, 'uuid' => null, 'error' => 'EuroSMS API nie je nakonfigurované (chýba Integračné ID/Kľúč v config.php).'];
    }

    $countryPrefix = (strtoupper($countryHint) === 'CZ') ? '420' : '421';
    $rcpt = eurosms_normalize_number_with_country($to, $countryPrefix);
    if (!$rcpt) {
        return ['success' => false, 'uuid' => null, 'error' => 'Neplatné telefónne číslo — podporované sú len slovenské a české čísla.'];
    }

    $sndr = trim((string)($sender ?: 'REZERVOS'));
    if (mb_strlen($sndr) > 11) $sndr = mb_substr($sndr, 0, 11);
    if ($sndr === '') $sndr = 'REZERVOS';

    $text = (string)$text;
    $sgn = eurosms_signature($sndr, $rcpt, $text, EUROSMS_KEY);

    $payload = [
        'iid'  => EUROSMS_IID,
        'sgn'  => $sgn,
        'rcpt' => (int)$rcpt,
        'sndr' => $sndr,
        'txt'  => $text,
    ];

    $mode = EUROSMS_TEST_MODE ? 'test' : 'send';
    $url = "https://as.eurosms.com/api/v3/{$mode}/one";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'uuid' => null, 'error' => 'Chyba spojenia s EuroSMS: ' . $curlError];
    }

    $data = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && is_array($data) && !empty($data['uuid'])) {
        $uuid = is_array($data['uuid']) ? ($data['uuid'][0] ?? null) : $data['uuid'];
        return ['success' => true, 'uuid' => $uuid, 'error' => null, 'raw' => $data];
    }

    $errMsg = $data['err_desc'] ?? $data['err_code'] ?? $response;
    return ['success' => false, 'uuid' => null, 'error' => "EuroSMS chyba (HTTP {$httpCode}): {$errMsg}"];
}

/**
 * Odošle SMS a strhne 1 kredit z Peňaženky (users.sms_credits) používateľovi $user_id.
 * Ak sa odoslanie nepodarí, kredit sa nestrháva.
 */
function eurosms_send_and_charge($pdo, $user_id, $to, $text, $sender = null, $countryHint = 'SK') {
    $stmt = $pdo->prepare("SELECT sms_credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $credits = (int)$stmt->fetchColumn();

    if ($credits < 1) {
        return ['success' => false, 'uuid' => null, 'error' => 'Nedostatočný SMS kredit v Peňaženke.', 'need_topup' => true];
    }

    $result = eurosms_send_sms($to, $text, $sender, $countryHint);
    if (!$result['success']) {
        return $result;
    }

    $upd = $pdo->prepare("UPDATE users SET sms_credits = GREATEST(0, sms_credits - 1) WHERE id = ?");
    $upd->execute([$user_id]);

    $tx = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'sms_sent', 0, 'EUR', 'completed', ?)");
    $tx->execute([$user_id, 'Odoslaná SMS na ' . $to . (EUROSMS_TEST_MODE ? ' (TEST režim)' : '')]);

    return $result;
}
