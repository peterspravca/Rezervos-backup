<?php
// api/stripe_checkout.php — vytvorenie Stripe Checkout Session pre (1) aktiváciu plateného balíka
// a (2) dobitie Peňaženky. Skutočná zmena balíka / pripísanie kreditu nastáva AŽ vo webhooku
// (api/stripe_webhook.php) po potvrdenej platbe od Stripe, nikdy tu — predtým "change_tier" a
// "topup" akcie priznávali balík/kredit hneď na základe čísla poslaného klientom, bez overenia
// platby, čo šlo obísť napr. cez devtools/curl. Tento súbor len otvára platobnú bránu.
session_start();
require_once '../config.php';
require_once '../includes/stripe_helper.php';
require_once '../includes/employee_permissions_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Dobitie Peňaženky (create_topup_session) smie ktokoľvek prihlásený (business/admin/customer —
// peňaženka existuje pre všetky role, viď dashboard-penazanka.php, admin-penazenka.php,
// moj_profil-penazenka.php). Aktivácia plateného balíka (create_subscription_session) je len pre
// prevádzky, balíky START/PRO/VIP existujú iba pre role 'business'.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}
$requestedAction = $_POST['action'] ?? '';
if ($requestedAction === 'create_subscription_session') {
    if ($_SESSION['user_role'] !== 'business') {
        echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
        exit;
    }
    if (!empty($_SESSION['is_employee']) && !employeeCan('revenue')) {
        echo json_encode(['success' => false, 'message' => 'Nemáte oprávnenie na túto akciu.']);
        exit;
    }
}
if (!stripe_is_configured()) {
    echo json_encode(['success' => false, 'message' => 'Platby cez Stripe zatiaľ nie sú nastavené. Kontaktujte podporu.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try { $pdo->exec("ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN stripe_subscription_id VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

function stripe_get_or_create_customer($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT stripe_customer_id, email, full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) return null;
    if (!empty($u['stripe_customer_id'])) return $u['stripe_customer_id'];

    $resp = stripe_request('POST', 'customers', [
        'email' => $u['email'] ?? '',
        'name' => $u['full_name'] ?? '',
        'metadata' => ['user_id' => $user_id],
    ]);
    if (!empty($resp['error']) || empty($resp['id'])) return null;

    $upd = $pdo->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
    $upd->execute([$resp['id'], $user_id]);
    return $resp['id'];
}

if ($action === 'create_subscription_session') {
    $tier = strtolower(trim($_POST['tier'] ?? ''));
    $period = strtolower(trim($_POST['period'] ?? 'monthly'));

    // Ceny v centoch — musia zodpovedať tomu, čo sa zobrazuje v components/billing.php (promptChangeTier)
    $prices = [
        'start' => ['monthly' => 690,  'yearly' => 7080],
        'pro'   => ['monthly' => 1490, 'yearly' => 15480],
        'vip'   => ['monthly' => 2990, 'yearly' => 32280],
    ];
    if (!isset($prices[$tier][$period])) {
        echo json_encode(['success' => false, 'message' => 'Neplatný balík alebo fakturačné obdobie.']);
        exit;
    }

    $customerId = stripe_get_or_create_customer($pdo, $user_id);
    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'Nepodarilo sa vytvoriť platobný profil v Stripe.']);
        exit;
    }

    $interval = ($period === 'yearly') ? 'year' : 'month';
    $tierNames = ['start' => 'START', 'pro' => 'PRO', 'vip' => 'VIP'];

    $resp = stripe_request('POST', 'checkout/sessions', [
        'mode' => 'subscription',
        'customer' => $customerId,
        'success_url' => $baseUrl . '/dashboard-balik.php?stripe=success',
        'cancel_url' => $baseUrl . '/dashboard-balik.php?stripe=cancel',
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $prices[$tier][$period],
                'recurring' => ['interval' => $interval],
                'product_data' => ['name' => 'Rezervos balík ' . ($tierNames[$tier] ?? strtoupper($tier))],
            ],
        ]],
        'metadata' => ['user_id' => $user_id, 'tier' => $tier, 'period' => $period, 'purpose' => 'subscription', 'origin_host' => $_SERVER['HTTP_HOST'] ?? ''],
        'subscription_data' => ['metadata' => ['user_id' => $user_id, 'tier' => $tier, 'period' => $period, 'origin_host' => $_SERVER['HTTP_HOST'] ?? '']],
    ]);

    if (!empty($resp['error'])) {
        echo json_encode(['success' => false, 'message' => 'Stripe chyba: ' . ($resp['error']['message'] ?? 'neznáma')]);
        exit;
    }
    echo json_encode(['success' => true, 'checkout_url' => $resp['url']]);
    exit;
}

if ($action === 'create_topup_session') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount < 5.0 || $amount > 2000.0) {
        echo json_encode(['success' => false, 'message' => 'Suma dobitia musí byť medzi 5,00 € a 2000,00 €.']);
        exit;
    }
    $amountCents = (int)round($amount * 100);

    // Vráti používateľa presne na tú peňaženku, odkiaľ prišiel (business/admin/customer majú každý
    // svoju vlastnú stránku) — bez toho by sa admin/zákazník po platbe presmeroval na business dashboard.
    $returnPages = ['dashboard-penazanka.php', 'admin-penazenka.php', 'moj_profil-penazenka.php'];
    $returnPage = in_array($_POST['return_page'] ?? '', $returnPages, true) ? $_POST['return_page'] : 'dashboard-penazanka.php';

    $customerId = stripe_get_or_create_customer($pdo, $user_id);
    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'Nepodarilo sa vytvoriť platobný profil v Stripe.']);
        exit;
    }

    $resp = stripe_request('POST', 'checkout/sessions', [
        'mode' => 'payment',
        'customer' => $customerId,
        'success_url' => $baseUrl . '/' . $returnPage . '?stripe=success',
        'cancel_url' => $baseUrl . '/' . $returnPage . '?stripe=cancel',
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $amountCents,
                'product_data' => ['name' => 'Dobitie Peňaženky Rezervos'],
            ],
        ]],
        'metadata' => ['user_id' => $user_id, 'amount' => $amount, 'purpose' => 'wallet_topup', 'origin_host' => $_SERVER['HTTP_HOST'] ?? ''],
    ]);

    if (!empty($resp['error'])) {
        echo json_encode(['success' => false, 'message' => 'Stripe chyba: ' . ($resp['error']['message'] ?? 'neznáma')]);
        exit;
    }
    echo json_encode(['success' => true, 'checkout_url' => $resp['url']]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
