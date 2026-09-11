<?php
// includes/stripe_helper.php — priamy cURL klient pre Stripe REST API.
// Projekt nepoužíva composer/vendor SDK (rovnaký vzor ako includes/eurosms.php), preto Stripe
// voláme priamo cez HTTPS namiesto oficiálneho stripe-php balíčka.

if (!defined('STRIPE_SECRET_KEY')) {
    $stripe_secrets_file = __DIR__ . '/../stripe_secrets.php';
    if (file_exists($stripe_secrets_file)) {
        require_once $stripe_secrets_file;
    } else {
        // Záloha pre servery, kde sú kľúče nastavené ako env premenné namiesto stripe_secrets.php
        define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
        define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
        define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');
    }
}

function stripe_is_configured() {
    return STRIPE_SECRET_KEY !== '';
}

// $params môže obsahovať vnorené polia (napr. 'line_items' => [[...]]) — http_build_query ich
// prirodzene serializuje do zátvorkovej notácie (line_items[0][price_data][currency]=eur),
// presne v tvare, aký Stripe API na strane príjmu očakáva.
function stripe_request($method, $endpoint, $params = []) {
    $url = 'https://api.stripe.com/v1/' . ltrim($endpoint, '/');
    $method = strtoupper($method);

    $ch = curl_init();
    $headers = ['Authorization: Bearer ' . STRIPE_SECRET_KEY];

    if ($method === 'GET') {
        if (!empty($params)) $url .= '?' . http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $sslVerify = true;
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && !ini_get('curl.cainfo') && !ini_get('openssl.cafile')) {
        $sslVerify = false;
    }

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => $sslVerify,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    if (PHP_VERSION_ID < 80500) {
        @curl_close($ch);
    }

    if ($response === false) {
        return ['error' => ['message' => 'Stripe API spojenie zlyhalo: ' . $curlErr]];
    }

    $data = json_decode($response, true);
    if ($httpCode >= 400) {
        return ['error' => ($data['error'] ?? ['message' => 'Stripe API vrátilo chybu ' . $httpCode])];
    }
    return $data;
}

// Overenie Stripe-Signature hlavičky webhooku (HMAC-SHA256, viď Stripe docs "Verify webhook signatures
// manually") — bez tohto by mohol ktokoľvek poslať na webhook falošnú správu "platba prebehla".
function stripe_verify_webhook_signature($payload, $sigHeader, $secret) {
    if (!$sigHeader || !$secret) return false;

    $parts = [];
    foreach (explode(',', $sigHeader) as $pair) {
        $kv = explode('=', $pair, 2);
        if (count($kv) === 2) $parts[$kv[0]] = $kv[1];
    }
    if (empty($parts['t']) || empty($parts['v1'])) return false;

    // Tolerancia 5 minút proti replay útoku so starým zachyteným payloadom
    if (abs(time() - (int)$parts['t']) > 300) return false;

    $signedPayload = $parts['t'] . '.' . $payload;
    $expectedSig = hash_hmac('sha256', $signedPayload, $secret);
    return hash_equals($expectedSig, $parts['v1']);
}
