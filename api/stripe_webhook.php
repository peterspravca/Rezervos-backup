<?php
// api/stripe_webhook.php — jediné miesto, kde sa po REÁLNE potvrdenej platbe pripíše balík alebo
// kredit do Peňaženky. Volá ho výhradne Stripe (nastav v Dashboard -> Developers -> Webhooks ako
// endpoint URL https://tvoja-domena/api/stripe_webhook.php), nie prehliadač používateľa — preto tu
// nie je session/login kontrola, ale namiesto nej overenie kryptografického podpisu (Stripe-Signature).
require_once '../config.php';
require_once '../includes/stripe_helper.php';

header('Content-Type: application/json; charset=utf-8');

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!stripe_is_configured() || !stripe_verify_webhook_signature($payload, $sigHeader, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$event = json_decode($payload, true);
if (!$event || empty($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$type = $event['type'];
$obj = $event['data']['object'] ?? [];
$meta = $obj['metadata'] ?? [];

// Multi-domain podpora: ak platba pochádza z českej domény (.cz), prepneme PDO na českú databázu
if (!empty($meta['origin_host']) && strpos($meta['origin_host'], '.cz') !== false) {
    try {
        $pdo = new PDO("mysql:host=db1.usr.sk;dbname=volnekreslocz;charset=utf8mb4", 'volnekreslo.cz', 'rVslYqLz/DA19MWd');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

try { $pdo->exec("ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN stripe_subscription_id VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
// stripe_event_id: dedup — Stripe môže ten istý webhook doručiť viackrát, tento UNIQUE index
// zaručí, že sa kredit/predplatné nepripíše dvakrát za tú istú udalosť
try { $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN stripe_event_id VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("CREATE UNIQUE INDEX idx_wallet_tx_stripe_event ON wallet_transactions (stripe_event_id)"); } catch (Exception $e) {}

function alreadyProcessed($pdo, $eventId) {
    $stmt = $pdo->prepare("SELECT id FROM wallet_transactions WHERE stripe_event_id = ? LIMIT 1");
    $stmt->execute([$eventId]);
    return (bool)$stmt->fetchColumn();
}

if ($type === 'checkout.session.completed') {
    $purpose = $meta['purpose'] ?? '';
    $eventId = $event['id'];

    if (alreadyProcessed($pdo, $eventId)) {
        echo json_encode(['received' => true, 'note' => 'already processed']);
        exit;
    }

    if ($purpose === 'subscription' && ($obj['payment_status'] ?? '') === 'paid') {
        $user_id = (int)($meta['user_id'] ?? 0);
        $tier = strtolower($meta['tier'] ?? '');
        $period = strtolower($meta['period'] ?? 'monthly');
        $subscriptionId = $obj['subscription'] ?? null;

        if ($user_id > 0 && in_array($tier, ['start', 'pro', 'vip'])) {
            $interval = ($period === 'yearly') ? '1 YEAR' : '1 MONTH';

            $stmt1 = $pdo->prepare("UPDATE establishments SET subscription_tier = ?, subscription_period = ?, subscription_expires_at = DATE_ADD(NOW(), INTERVAL $interval) WHERE user_id = ?");
            $stmt1->execute([$tier, $period, $user_id]);

            $stmt2 = $pdo->prepare("UPDATE users SET subscription_tier = ?, subscription_period = ?, subscription_expires_at = DATE_ADD(NOW(), INTERVAL $interval), stripe_subscription_id = ? WHERE id = ?");
            $stmt2->execute([$tier, $period, $subscriptionId, $user_id]);

            $tierNames = ['start' => 'START', 'pro' => 'PRO', 'vip' => 'VIP'];
            $amountPaid = ($obj['amount_total'] ?? 0) / 100;
            $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description, stripe_event_id) VALUES (?, 'subscription_payment', ?, 'EUR', 'completed', ?, ?)");
            $stmt_t->execute([$user_id, -$amountPaid, "Aktivácia balíka {$tierNames[$tier]} cez Stripe ({$period})", $eventId]);

            require_once __DIR__ . '/../includes/affiliate_helper.php';
            affiliate_record_commission_if_applicable($pdo, $user_id, $tier, $period);
        }
    }

    if ($purpose === 'wallet_topup' && ($obj['payment_status'] ?? '') === 'paid') {
        $user_id = (int)($meta['user_id'] ?? 0);
        $amount = (float)($obj['amount_total'] ?? 0) / 100;

        if ($user_id > 0 && $amount > 0) {
            $stmt_upd = $pdo->prepare("UPDATE users SET credit = credit + ?, credit_purchased = credit_purchased + ? WHERE id = ?");
            $stmt_upd->execute([$amount, $amount, $user_id]);

            $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description, stripe_event_id) VALUES (?, 'deposit', ?, 'EUR', 'completed', ?, ?)");
            $stmt_t->execute([$user_id, $amount, 'Dobitie kreditu do Peňaženky (Stripe platobná karta)', $eventId]);
        }
    }
}

if ($type === 'customer.subscription.deleted') {
    $meta = $obj['metadata'] ?? [];
    $user_id = (int)($meta['user_id'] ?? 0);
    if ($user_id > 0) {
        $stmt1 = $pdo->prepare("UPDATE establishments SET subscription_tier = 'free', subscription_expires_at = NULL, subscription_period = 'monthly' WHERE user_id = ?");
        $stmt1->execute([$user_id]);
        $stmt2 = $pdo->prepare("UPDATE users SET subscription_tier = 'free', subscription_expires_at = NULL, subscription_period = 'monthly', stripe_subscription_id = NULL WHERE id = ?");
        $stmt2->execute([$user_id]);
    }
}

echo json_encode(['received' => true]);
