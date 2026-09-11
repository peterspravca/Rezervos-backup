<?php
session_start();
require_once '../config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? 'get_wallet';

// topup, get_balance a claim_share_reward sú univerzálne (users.credit aj denná odmena za
// zdieľanie patria každému používateľovi, nielen prevádzke). Ostatné akcie (SMS balíčky,
// zviditeľnenie/topovanie prevádzky) zostávajú len pre biznis účty.
$actionsForAnyUser = ['topup', 'get_balance', 'claim_share_reward', 'get_share_status', 'purchase_hunter'];
$isLoggedIn = isset($_SESSION['user_id']);
$isBusiness = $isLoggedIn && $_SESSION['user_role'] === 'business';

if (!$isBusiness && !($isLoggedIn && in_array($action, $actionsForAnyUser, true))) {
    echo json_encode(['success' => false, 'error' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Self-migrácia: stĺpec na sledovanie predplatného Kreslo Hunter (len pre zákazníkov)
$chk_hunter_col = $pdo->query("SHOW COLUMNS FROM users LIKE 'hunter_expires_at'");
if ($chk_hunter_col && $chk_hunter_col->rowCount() === 0) {
    try { $pdo->exec("ALTER TABLE users ADD COLUMN hunter_expires_at DATETIME NULL DEFAULT NULL"); } catch (Exception $e) {}
}

// Self-migrácia: vlastné meno odosielateľa SMS (aktivuje sa jednorazovým poplatkom, max 11 znakov)
$chk_sender_col = $pdo->query("SHOW COLUMNS FROM users LIKE 'sms_sender_name'");
if ($chk_sender_col && $chk_sender_col->rowCount() === 0) {
    try { $pdo->exec("ALTER TABLE users ADD COLUMN sms_sender_name VARCHAR(11) NULL DEFAULT NULL"); } catch (Exception $e) {}
}

// Self-migrácia: platené zvýraznenie prevádzky vo výpisoch (TOP / Sponzorované / Odporúčané)
$pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS top_expires_at DATETIME NULL DEFAULT NULL");
$pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS sponsored_expires_at DATETIME NULL DEFAULT NULL");
$pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS recommended_expires_at DATETIME NULL DEFAULT NULL");

if ($action === 'get_balance') {
    $stmt_b = $pdo->prepare("SELECT credit, credit_purchased, credit_earned, hunter_expires_at FROM users WHERE id = ?");
    $stmt_b->execute([$user_id]);
    $row = $stmt_b->fetch(PDO::FETCH_ASSOC) ?: ['credit' => 0, 'credit_purchased' => 0, 'credit_earned' => 0, 'hunter_expires_at' => null];

    $stmt_t = $pdo->prepare("SELECT type, amount, description, created_at FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt_t->execute([$user_id]);
    $transactions = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

    // Stav TOP / Sponzorované / Odporúčané zvýraznenia (len ak je prevádzka vytvorená)
    $topExpiresAt = null; $sponsoredExpiresAt = null; $recommendedExpiresAt = null; $fiveStarThisMonth = 0;
    $stmt_est2 = $pdo->prepare("SELECT top_expires_at, sponsored_expires_at, recommended_expires_at FROM establishments WHERE user_id = ?");
    $stmt_est2->execute([$user_id]);
    if ($estRow = $stmt_est2->fetch(PDO::FETCH_ASSOC)) {
        $topExpiresAt = $estRow['top_expires_at'];
        $sponsoredExpiresAt = $estRow['sponsored_expires_at'];
        $recommendedExpiresAt = $estRow['recommended_expires_at'];
        $stmt_5star = $pdo->prepare("
            SELECT COUNT(*) FROM reviews r
            WHERE r.reviewee_id = ? AND r.reviewee_type = 'establishment' AND r.rating = 5
              AND r.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
              AND EXISTS (SELECT 1 FROM reviews r2 WHERE r2.booking_id = r.booking_id AND r2.reviewer_type = 'customer')
        ");
        $stmt_5star->execute([$user_id]);
        $fiveStarThisMonth = (int)$stmt_5star->fetchColumn();
    }
    $topActive = !empty($topExpiresAt) && strtotime($topExpiresAt) > time();
    $sponsoredActive = !empty($sponsoredExpiresAt) && strtotime($sponsoredExpiresAt) > time();
    $recommendedActivePaid = !empty($recommendedExpiresAt) && strtotime($recommendedExpiresAt) > time();
    $recommendedActiveEarned = $fiveStarThisMonth >= 100;

    echo json_encode([
        'success' => true,
        'balance' => (float)$row['credit'],
        'purchased' => (float)$row['credit_purchased'],
        'earned' => (float)$row['credit_earned'],
        'hunter_expires_at' => $row['hunter_expires_at'],
        'hunter_active' => !empty($row['hunter_expires_at']) && strtotime($row['hunter_expires_at']) > time(),
        'top_expires_at' => $topExpiresAt,
        'top_active' => $topActive,
        'sponsored_expires_at' => $sponsoredExpiresAt,
        'sponsored_active' => $sponsoredActive,
        'recommended_expires_at' => $recommendedExpiresAt,
        'recommended_active_paid' => $recommendedActivePaid,
        'recommended_active_earned' => $recommendedActiveEarned,
        'recommended_active' => $recommendedActivePaid || $recommendedActiveEarned,
        'recommended_five_star_count' => $fiveStarThisMonth,
        'transactions' => $transactions
    ]);
    exit;
}

if ($action === 'get_share_status') {
    foreach (['share_reward_today_amount' => 'DECIMAL(4,2) NOT NULL DEFAULT 0.00', 'share_reward_ad_count_today' => 'INT NOT NULL DEFAULT 0'] as $col => $def) {
        $chk = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($col));
        if ($chk && $chk->rowCount() === 0) { try { $pdo->exec("ALTER TABLE users ADD COLUMN `$col` $def"); } catch (Exception $e) {} }
    }
    $stmt_s = $pdo->prepare("SELECT last_share_reward_date, share_reward_today_amount, share_reward_ad_count_today FROM users WHERE id = ?");
    $stmt_s->execute([$user_id]);
    $u_s = $stmt_s->fetch(PDO::FETCH_ASSOC);
    $isNewDay = !$u_s || $u_s['last_share_reward_date'] !== date('Y-m-d');
    $todayAmount  = $isNewDay ? 0.0 : (float)$u_s['share_reward_today_amount'];
    $todayAdCount = $isNewDay ? 0   : (int)$u_s['share_reward_ad_count_today'];
    $remaining = max(0, round(0.10 - $todayAmount, 2));
    echo json_encode([
        'success' => true,
        'today_amount' => $todayAmount,
        'today_ad_count' => $todayAdCount,
        'daily_cap' => 0.10,
        'remaining' => $remaining,
        'app_available' => $remaining >= 0.10 - 0.001,
        'listing_available' => $todayAdCount < 2 && $remaining >= 0.05 - 0.001
    ]);
    exit;
}

// SMS Cenník — jednotný pre SK aj CZ čísla (veľkoobchodné náklady EuroSMS sú pre obe destinácie
// prakticky rovnaké, ~0,028-0,031 €/SMS), takže netreba rozlišovať podľa krajiny príjemcu.
$SMS_PACKAGES = [
    100 => ['price' => 15.00, 'unit_price' => 0.15, 'currency' => 'EUR', 'symbol' => '€'],
    500 => ['price' => 60.00, 'unit_price' => 0.12, 'currency' => 'EUR', 'symbol' => '€'],
    1000 => ['price' => 100.00, 'unit_price' => 0.10, 'currency' => 'EUR', 'symbol' => '€'],
    5000 => ['price' => 450.00, 'unit_price' => 0.09, 'currency' => 'EUR', 'symbol' => '€'],
    10000 => ['price' => 800.00, 'unit_price' => 0.08, 'currency' => 'EUR', 'symbol' => '€']
];

// Ceny Topovacích služieb (v EUR)
$BOOST_PRICES = [
    'single_tap' => ['price' => 0.45, 'name' => 'Jednorazové tapnutie (posun hore)', 'duration_days' => 0],
    'morning_bird' => ['price' => 2.40, 'name' => 'Ranné vtáča balíček (7 dní auto-tap)', 'duration_days' => 7],
    'primetime_bomber' => ['price' => 4.90, 'name' => 'Prime-time Bombardér (7 dní auto-tap)', 'duration_days' => 7]
];

try {
    // 1. GET WALLET DATA
    if ($action === 'get_wallet') {
        $stmt_u = $pdo->prepare("SELECT credit, credit_purchased, credit_earned, wallet_number, sms_credits, sms_sender_name, last_share_reward_date FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $u = $stmt_u->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            echo json_encode(['success' => false, 'error' => 'Používateľ nebol nájdený.']);
            exit;
        }

        // Ak chýba wallet number, vygenerujeme
        if (empty($u['wallet_number'])) {
            $wn = 'VK-' . mt_rand(1000, 9999) . '-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            $stmt_upd = $pdo->prepare("UPDATE users SET wallet_number = ? WHERE id = ?");
            $stmt_upd->execute([$wn, $user_id]);
            $u['wallet_number'] = $wn;
        }

        $today = date('Y-m-d');
        $can_claim_share = (empty($u['last_share_reward_date']) || $u['last_share_reward_date'] !== $today);

        // Získame informácie o prevádzke, booste a predplatnom
        $stmt_e = $pdo->prepare("SELECT e.id, e.name, e.boost_type, e.boost_expires_at, e.boost_last_tapped_at, e.subscription_tier, e.subscription_expires_at, e.top_expires_at, e.sponsored_expires_at, e.recommended_expires_at, u.subscription_tier as u_tier, u.subscription_expires_at as u_expires_at FROM establishments e JOIN users u ON e.user_id = u.id WHERE e.user_id = ? LIMIT 1");
        $stmt_e->execute([$user_id]);
        $est = $stmt_e->fetch(PDO::FETCH_ASSOC);
        // Synchronizuj tier (použi vyšší z oboch tabuliek)
        if ($est) {
            $tierOrder = ['free' => 0, 'start' => 1, 'pro' => 2, 'vip' => 3];
            $eTier = $est['subscription_tier'] ?? 'free';
            $uTier = $est['u_tier'] ?? 'free';
            if (($tierOrder[$uTier] ?? 0) > ($tierOrder[$eTier] ?? 0)) {
                $est['subscription_tier'] = $uTier;
                $est['subscription_expires_at'] = $est['u_expires_at'];
            }
            unset($est['u_tier'], $est['u_expires_at']);

            $stmt_5star2 = $pdo->prepare("
                SELECT COUNT(*) FROM reviews r
                WHERE r.reviewee_id = ? AND r.reviewee_type = 'establishment' AND r.rating = 5
                  AND r.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
                  AND EXISTS (SELECT 1 FROM reviews r2 WHERE r2.booking_id = r.booking_id AND r2.reviewer_type = 'customer')
            ");
            $stmt_5star2->execute([$user_id]);
            $est['recommended_five_star_count'] = (int)$stmt_5star2->fetchColumn();
        }

        // Získame transakcie
        $stmt_t = $pdo->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
        $stmt_t->execute([$user_id]);
        $transactions = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'wallet' => [
                'credit' => (float)$u['credit'],
                'credit_purchased' => (float)$u['credit_purchased'],
                'credit_earned' => (float)$u['credit_earned'],
                'wallet_number' => $u['wallet_number'],
                'sms_credits' => (int)$u['sms_credits'],
                'sms_sender_name' => $u['sms_sender_name'],
                'can_claim_share_today' => $can_claim_share,
                'last_share_reward_date' => $u['last_share_reward_date'],
                'ai_credits' => (function() use ($conn, $user_id) {
                    require_once '../includes/ai_credit_helper.php';
                    return ai_credit_balance($conn, $user_id);
                })()
            ],
            'establishment' => $est,
            'transactions' => $transactions,
            'sms_packages' => $SMS_PACKAGES,
            'boost_prices' => $BOOST_PRICES
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CLAIM SHARE REWARD — denný limit 0,10 € na používateľa, bez ohľadu na kombináciu:
    //   target=app           → 0,10 € (zdieľanie webu, dostupné každému)
    //   target=establishment → 0,05 € (zdieľanie vlastnej prevádzky, len biznis účet)
    //   target=listing       → 0,05 € (zdieľanie vlastného inzerátu, max 2x denne)
    if ($action === 'claim_share_reward') {
        $platform = trim($_POST['platform'] ?? 'facebook');
        $target = trim($_POST['target'] ?? 'app');
        $today = date('Y-m-d');
        $DAILY_CAP = 0.10;

        // Auto-migrácia stĺpcov na sledovanie dennej sumy a počtu odmien za inzeráty
        foreach ([
            'share_reward_today_amount'   => 'DECIMAL(4,2) NOT NULL DEFAULT 0.00',
            'share_reward_ad_count_today' => 'INT NOT NULL DEFAULT 0',
        ] as $col => $def) {
            $chk = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($col));
            if ($chk && $chk->rowCount() === 0) { try { $pdo->exec("ALTER TABLE users ADD COLUMN `$col` $def"); } catch (Exception $e) {} }
        }

        $stmt_chk = $pdo->prepare("SELECT last_share_reward_date, share_reward_today_amount, share_reward_ad_count_today FROM users WHERE id = ?");
        $stmt_chk->execute([$user_id]);
        $u_chk = $stmt_chk->fetch(PDO::FETCH_ASSOC);

        $isNewDay      = !$u_chk || $u_chk['last_share_reward_date'] !== $today;
        $todayAmount   = $isNewDay ? 0.0 : (float)$u_chk['share_reward_today_amount'];
        $todayAdCount  = $isNewDay ? 0   : (int)$u_chk['share_reward_ad_count_today'];

        $listingId = 0;
        if ($target === 'listing') {
            $listingId = (int)($_POST['listing_id'] ?? 0);
            if (!$listingId) {
                echo json_encode(['success' => false, 'error' => 'Chýba ID inzerátu.']); exit;
            }
            $stmt_own = $pdo->prepare("SELECT id FROM classifieds WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt_own->execute([$listingId, $user_id]);
            if (!$stmt_own->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Inzerát nebol nájdený.']); exit;
            }
            if ($todayAdCount >= 2) {
                echo json_encode(['success' => false, 'already_claimed' => true, 'error' => 'Dnes ste už získali odmenu za zdieľanie 2 inzerátov.']); exit;
            }
            $reward_amount = 0.05;
        } elseif ($target === 'establishment' || $target === 'salon') {
            $stmt_est = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ? LIMIT 1");
            $stmt_est->execute([$user_id]);
            if (!$stmt_est->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Prevádzka nebola nájdená.']); exit;
            }
            $reward_amount = 0.05;
        } else {
            $target = 'app';
            $reward_amount = 0.10;
        }

        if ($todayAmount + $reward_amount > $DAILY_CAP + 0.001) {
            echo json_encode([
                'success' => false,
                'already_claimed' => true,
                'error' => 'Dosiahli ste dennú hranicu odmien za zdieľanie (0,10 €). Ďalšiu odmenu môžete získať zajtra!'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $reward_formatted = number_format($reward_amount, 2, ',', ' ') . ' €';
        $newAmount  = $todayAmount + $reward_amount;
        $newAdCount = $todayAdCount + ($target === 'listing' ? 1 : 0);

        $stmt_upd = $pdo->prepare("UPDATE users SET credit = credit + ?, credit_earned = credit_earned + ?, last_share_reward_date = ?, share_reward_today_amount = ?, share_reward_ad_count_today = ? WHERE id = ?");
        $stmt_upd->execute([$reward_amount, $reward_amount, $today, $newAmount, $newAdCount, $user_id]);

        $platform_names = ['facebook' => 'Facebook', 'whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'instagram_story' => 'Instagram Story', 'instagram_dm' => 'Instagram DM', 'x' => 'X (Twitter)'];
        $p_label = $platform_names[$platform] ?? ucfirst($platform);
        $type_label = $target === 'listing' ? 'inzerátu' : (($target === 'establishment' || $target === 'salon') ? 'vlastnej prevádzky' : 'aplikácie ' . BRAND_NAME);

        $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'reward', ?, 'EUR', 'completed', ?)");
        $desc = "Odmena ({$reward_formatted}) za zdieľanie {$type_label} na {$p_label}" . ($listingId ? " (#{$listingId})" : '');
        $stmt_t->execute([$user_id, $reward_amount, $desc]);

        $new_credit = (float)$pdo->query("SELECT credit FROM users WHERE id = {$user_id}")->fetchColumn();
        $new_earned = (float)$pdo->query("SELECT credit_earned FROM users WHERE id = {$user_id}")->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => "Skvelé! Získali ste odmenu +{$reward_formatted} do Peňaženky." . ($newAmount >= $DAILY_CAP ? ' Dnešný denný limit 0,10 € je vyčerpaný.' : ''),
            'reward_amount' => $reward_amount,
            'new_balance' => $new_credit,
            'new_earned' => $new_earned,
            'today_amount' => $newAmount,
            'today_ad_count' => $newAdCount
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. TOPUP WALLET (DOBITIE PEŇAŽENKY)
    if ($action === 'topup') {
        // Táto akcia kedysi pripísala kredit priamo na základe sumy poslanej klientom, bez overenia
        // akejkoľvek platby (dalo sa obísť napr. cez devtools/curl). Skutočné dobitie kartou teraz ide
        // cez api/stripe_checkout.php (action=create_topup_session) a kredit sa pripíše až vo webhooku
        // api/stripe_webhook.php po potvrdenej platbe zo Stripe.
        echo json_encode([
            'success' => false,
            'error' => 'Dobitie kreditu ide cez platobnú bránu. Skúste to znova z tejto stránky.',
            'requires_checkout' => true
        ]);
        exit;
    }

    // 3. PURCHASE BOOST (TOPOVANIE / POSUN HORE)
    if ($action === 'purchase_boost') {
        $boost_key = trim($_POST['boost_key'] ?? '');

        if (!isset($BOOST_PRICES[$boost_key])) {
            echo json_encode(['success' => false, 'error' => 'Neplatný typ zviditeľnenia.']);
            exit;
        }

        $boost = $BOOST_PRICES[$boost_key];
        $price = (float)$boost['price'];

        // Získame aktuálny kredit používateľa
        $stmt_u = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $curr_credit = (float)$stmt_u->fetchColumn();

        if ($curr_credit < $price) {
            echo json_encode([
                'success' => false,
                'error' => 'Nedostatočný zostatok v Peňaženke. Potrebujete ' . number_format($price, 2, ',', ' ') . ' €, aktuálny zostatok je ' . number_format($curr_credit, 2, ',', ' ') . ' €.',
                'need_topup' => true
            ]);
            exit;
        }

        // Získame prevádzku
        $stmt_est = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ? LIMIT 1");
        $stmt_est->execute([$user_id]);
        $est_id = (int)$stmt_est->fetchColumn();

        if (!$est_id) {
            echo json_encode(['success' => false, 'error' => 'Prevádzka nebola nájdená.']);
            exit;
        }

        // Strhneme kredit - prioritne z bonusového kreditu (získaného zdieľaním), zvyšok z dobitého
        $stmt_u_detail = $pdo->prepare("SELECT credit_earned, credit_purchased FROM users WHERE id = ?");
        $stmt_u_detail->execute([$user_id]);
        $u_det = $stmt_u_detail->fetch(PDO::FETCH_ASSOC);
        $curr_earned = (float)($u_det['credit_earned'] ?? 0);
        
        $deduct_earned = min($price, $curr_earned);
        $deduct_purchased = max(0, $price - $deduct_earned);

        $stmt_deduct = $pdo->prepare("UPDATE users SET credit = GREATEST(0, credit - ?), credit_earned = GREATEST(0, credit_earned - ?), credit_purchased = GREATEST(0, credit_purchased - ?) WHERE id = ?");
        $stmt_deduct->execute([$price, $deduct_earned, $deduct_purchased, $user_id]);

        // Aplikujeme boost na prevádzku
        if ($boost_key === 'single_tap') {
            $stmt_app = $pdo->prepare("UPDATE establishments SET boost_last_tapped_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt_app->execute([$est_id]);
            $msg = "Vaša prevádzka bola posunutá na 1. miesto!";
        } else {
            $days = (int)$boost['duration_days'];
            $stmt_app = $pdo->prepare("UPDATE establishments SET boost_type = ?, boost_expires_at = DATE_ADD(NOW(), INTERVAL ? DAY), boost_last_tapped_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt_app->execute([$boost_key, $days, $est_id]);
            $msg = "Balíček {$boost['name']} bol úspešne aktivovaný na {$days} dní!";
        }

        // Zaznamenáme transakciu
        $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'EUR', 'completed', ?)");
        $stmt_t->execute([$user_id, 'boost_' . $boost_key, -$price, "Aktivácia: {$boost['name']}"]);

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'new_balance' => (float)$pdo->query("SELECT credit FROM users WHERE id = {$user_id}")->fetchColumn()
        ]);
        exit;
    }

    // 3b. PREDPLATNÉ KRESLO HUNTER (mesačne 0,90 € / ročne 9,00 € — paušál, nie prepočet z mesačnej)
    if ($action === 'purchase_hunter') {
        $period = (trim($_POST['period'] ?? 'monthly') === 'yearly') ? 'yearly' : 'monthly';
        $price = ($period === 'yearly') ? 9.00 : 0.90;
        $intervalSql = ($period === 'yearly') ? '1 YEAR' : '1 MONTH';

        $stmt_u = $pdo->prepare("SELECT credit, credit_earned, credit_purchased FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
        $curr_credit = (float)($u['credit'] ?? 0);

        if ($curr_credit < $price) {
            echo json_encode([
                'success' => false,
                'error' => 'Nedostatočný zostatok v Peňaženke. Potrebujete ' . number_format($price, 2, ',', ' ') . ' €, aktuálny zostatok je ' . number_format($curr_credit, 2, ',', ' ') . ' €.',
                'need_topup' => true
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Strhneme kredit — prioritne z nazbieraného (zdieľanie), zvyšok z reálne dobitého
        $curr_earned = (float)($u['credit_earned'] ?? 0);
        $deduct_earned = min($price, $curr_earned);
        $deduct_purchased = round($price - $deduct_earned, 2);

        $stmt_deduct = $pdo->prepare("UPDATE users SET credit = GREATEST(0, credit - ?), credit_earned = GREATEST(0, credit_earned - ?), credit_purchased = GREATEST(0, credit_purchased - ?), hunter_expires_at = DATE_ADD(GREATEST(COALESCE(hunter_expires_at, NOW()), NOW()), INTERVAL {$intervalSql}) WHERE id = ?");
        $stmt_deduct->execute([$price, $deduct_earned, $deduct_purchased, $user_id]);

        $periodLabel = $period === 'yearly' ? 'na 1 rok' : 'na 1 mesiac';
        $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'hunter_subscription', ?, 'EUR', 'completed', ?)");
        $stmt_t->execute([$user_id, -$price, "Kreslo Hunter predĺžený {$periodLabel}"]);

        $newExpiry = $pdo->query("SELECT hunter_expires_at FROM users WHERE id = {$user_id}")->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => 'Kreslo Hunter bol úspešne predĺžený ' . $periodLabel . '!',
            'new_balance' => (float)$pdo->query("SELECT credit FROM users WHERE id = {$user_id}")->fetchColumn(),
            'hunter_expires_at' => $newExpiry
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3c. PLATENÉ ZVÝRAZNENIE PREVÁDZKY VO VÝPISOCH — TOP (30 €/mes, 300 €/rok), Sponzorované (15 €/mes, 150 €/rok) a Odporúčané (10 €/mes, 100 €/rok)
    if ($action === 'purchase_top' || $action === 'purchase_sponsored' || $action === 'purchase_recommended') {
        $period = (trim($_POST['period'] ?? 'monthly') === 'yearly') ? 'yearly' : 'monthly';
        $PLAN_PRICES = [
            'purchase_top'         => ['monthly' => 30.00, 'yearly' => 300.00, 'column' => 'top_expires_at',         'label' => 'TOP zvýraznenie',         'tx' => 'top_subscription'],
            'purchase_sponsored'   => ['monthly' => 15.00, 'yearly' => 150.00, 'column' => 'sponsored_expires_at',   'label' => 'Sponzorované zvýraznenie', 'tx' => 'sponsored_subscription'],
            'purchase_recommended' => ['monthly' => 10.00, 'yearly' => 100.00, 'column' => 'recommended_expires_at', 'label' => 'Odporúčané zvýraznenie',   'tx' => 'recommended_subscription'],
        ];
        $plan = $PLAN_PRICES[$action];
        $price = $plan[$period];
        $intervalSql = ($period === 'yearly') ? '1 YEAR' : '1 MONTH';
        $column = $plan['column'];
        $label = $plan['label'];
        $txType = $plan['tx'];

        $stmt_est = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
        $stmt_est->execute([$user_id]);
        $est_id = $stmt_est->fetchColumn();
        if (!$est_id) {
            echo json_encode(['success' => false, 'error' => 'Najprv si musíte vyplniť profil prevádzky.']);
            exit;
        }

        $stmt_u = $pdo->prepare("SELECT credit, credit_earned, credit_purchased FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
        $curr_credit = (float)($u['credit'] ?? 0);

        if ($curr_credit < $price) {
            echo json_encode([
                'success' => false,
                'error' => 'Nedostatočný zostatok v Peňaženke. Potrebujete ' . number_format($price, 2, ',', ' ') . ' €, aktuálny zostatok je ' . number_format($curr_credit, 2, ',', ' ') . ' €.',
                'need_topup' => true
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $curr_earned = (float)($u['credit_earned'] ?? 0);
        $deduct_earned = min($price, $curr_earned);
        $deduct_purchased = round($price - $deduct_earned, 2);

        $stmt_deduct = $pdo->prepare("UPDATE users SET credit = GREATEST(0, credit - ?), credit_earned = GREATEST(0, credit_earned - ?), credit_purchased = GREATEST(0, credit_purchased - ?) WHERE id = ?");
        $stmt_deduct->execute([$price, $deduct_earned, $deduct_purchased, $user_id]);

        $stmt_ext = $pdo->prepare("UPDATE establishments SET {$column} = DATE_ADD(GREATEST(COALESCE({$column}, NOW()), NOW()), INTERVAL {$intervalSql}) WHERE id = ?");
        $stmt_ext->execute([$est_id]);

        $periodLabel = $period === 'yearly' ? 'na 1 rok' : 'na 1 mesiac';
        $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'EUR', 'completed', ?)");
        $stmt_t->execute([$user_id, $txType, -$price, "{$label} predĺžené {$periodLabel}"]);

        $newExpiry = $pdo->query("SELECT {$column} FROM establishments WHERE id = {$est_id}")->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => $label . ' bolo úspešne predĺžené ' . $periodLabel . '!',
            'new_balance' => (float)$pdo->query("SELECT credit FROM users WHERE id = {$user_id}")->fetchColumn(),
            $column => $newExpiry
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. PURCHASE SMS PACKAGE (NÁKUP SMS SPRÁV)
    if ($action === 'purchase_sms') {
        $count = (int)($_POST['count'] ?? 0);

        if (!isset($SMS_PACKAGES[$count])) {
            echo json_encode(['success' => false, 'error' => 'Neplatný SMS balíček.']);
            exit;
        }

        $pkg = $SMS_PACKAGES[$count];
        $price = (float)$pkg['price'];

        // Získame kredit
        $stmt_u = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $curr_credit = (float)$stmt_u->fetchColumn();

        if ($curr_credit < $price) {
            echo json_encode([
                'success' => false,
                'error' => "Nedostatočný zostatok v Peňaženke. Balíček stojí " . number_format($price, 2, ',', ' ') . " €, váš zostatok je " . number_format($curr_credit, 2, ',', ' ') . " €.",
                'need_topup' => true
            ]);
            exit;
        }

        // Strhneme kredit a pripíšeme SMS (jeden spoločný pool, platí pre SK aj CZ čísla)
        $stmt_deduct = $pdo->prepare("UPDATE users SET credit = credit - ?, sms_credits = sms_credits + ? WHERE id = ?");
        $stmt_deduct->execute([$price, $count, $user_id]);

        // Zaznamenáme transakciu
        $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'sms_package', ?, 'EUR', 'completed', ?)");
        $desc = "Nákup balíčka {$count} SMS správ (SK/CZ)";
        $stmt_t->execute([$user_id, -$price, $desc]);

        $new_sms = (int)$pdo->query("SELECT sms_credits FROM users WHERE id = {$user_id}")->fetchColumn();
        $new_bal = (float)$pdo->query("SELECT credit FROM users WHERE id = {$user_id}")->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => "Balíček {$count} SMS správ bol úspešne zakúpený a pripísaný do vášho účtu!",
            'new_sms_credits' => $new_sms,
            'new_balance' => $new_bal
        ]);
        exit;
    }

    // 4b. NÁKUP AI KREDITOV (AI asistent v e-mailoch a marketingových kampaniach)
    if ($action === 'buy_ai_credits') {
        require_once '../includes/ai_credit_helper.php';
        $count = (int)($_POST['count'] ?? 0);

        if (!isset(AI_CREDIT_PACKAGES[$count])) {
            echo json_encode(['success' => false, 'error' => 'Neplatný balíček AI kreditov.']);
            exit;
        }

        $price = (float)AI_CREDIT_PACKAGES[$count];

        $stmt_u = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $curr_credit = (float)$stmt_u->fetchColumn();

        if ($curr_credit < $price) {
            echo json_encode([
                'success' => false,
                'error' => "Nedostatočný zostatok v Peňaženke. Balíček stojí " . number_format($price, 2, ',', ' ') . " €, váš zostatok je " . number_format($curr_credit, 2, ',', ' ') . " €.",
                'need_topup' => true
            ]);
            exit;
        }

        ai_credit_migrate($conn);

        // Strhneme kredit - prioritne z bonusového kreditu (získaného zdieľaním), zvyšok z dobitého
        $stmt_u_detail = $pdo->prepare("SELECT credit_earned FROM users WHERE id = ?");
        $stmt_u_detail->execute([$user_id]);
        $curr_earned = (float)$stmt_u_detail->fetchColumn();
        $deduct_earned = min($price, $curr_earned);
        $deduct_purchased = max(0, $price - $deduct_earned);

        $stmt_deduct = $pdo->prepare("UPDATE users SET credit = GREATEST(0, credit - ?), credit_earned = GREATEST(0, credit_earned - ?), credit_purchased = GREATEST(0, credit_purchased - ?), ai_credits_purchased = ai_credits_purchased + ? WHERE id = ?");
        $stmt_deduct->execute([$price, $deduct_earned, $deduct_purchased, $count, $user_id]);

        $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'ai_credits', ?, 'EUR', 'completed', ?)");
        $stmt_t->execute([$user_id, -$price, "Nákup balíčka {$count} AI kreditov"]);

        $new_ai = (int)$pdo->query("SELECT ai_credits_free + ai_credits_purchased FROM users WHERE id = {$user_id}")->fetchColumn();
        $new_bal = (float)$pdo->query("SELECT credit FROM users WHERE id = {$user_id}")->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => "Balíček {$count} AI kreditov bol úspešne zakúpený!",
            'new_ai_credits' => $new_ai,
            'new_balance' => $new_bal
        ]);
        exit;
    }

    // 5. AKTIVÁCIA VLASTNÉHO MENA ODOSIELATEĽA SMS (jednorazový poplatok 49 €, len z reálnych peňazí)
    if ($action === 'activate_sms_sender') {
        $senderName = trim($_POST['sender_name'] ?? '');
        $ACTIVATION_PRICE = 49.00;

        if ($senderName === '' || mb_strlen($senderName) > 11 || !preg_match('/^[A-Za-z0-9 .\-]+$/', $senderName)) {
            echo json_encode(['success' => false, 'error' => 'Meno odosielateľa smie mať max. 11 znakov (písmená, číslice, medzera, bodka, pomlčka).']);
            exit;
        }

        $stmt_u = $pdo->prepare("SELECT credit_purchased, sms_sender_name FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
        $alreadyActive = !empty($u['sms_sender_name']);
        $purchased = (float)($u['credit_purchased'] ?? 0);

        // Ak je služba už aktívna, meno sa dá zmeniť zadarmo (platí sa len raz za aktiváciu)
        if (!$alreadyActive) {
            if ($purchased < $ACTIVATION_PRICE) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Nedostatočný zostatok reálnych peňazí v Peňaženke. Aktivácia stojí ' . number_format($ACTIVATION_PRICE, 2, ',', ' ') . ' €.',
                    'need_topup' => true
                ]);
                exit;
            }
            $stmt_deduct = $pdo->prepare("UPDATE users SET credit = GREATEST(0, credit - ?), credit_purchased = GREATEST(0, credit_purchased - ?), sms_sender_name = ? WHERE id = ?");
            $stmt_deduct->execute([$ACTIVATION_PRICE, $ACTIVATION_PRICE, $senderName, $user_id]);

            $stmt_t = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'sms_sender_activation', ?, 'EUR', 'completed', ?)");
            $stmt_t->execute([$user_id, -$ACTIVATION_PRICE, "Aktivácia vlastného mena odosielateľa SMS ({$senderName})"]);

            $msg = 'Vlastné meno odosielateľa SMS bolo aktivované!';
        } else {
            $stmt_upd = $pdo->prepare("UPDATE users SET sms_sender_name = ? WHERE id = ?");
            $stmt_upd->execute([$senderName, $user_id]);
            $msg = 'Meno odosielateľa SMS bolo zmenené.';
        }

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'sms_sender_name' => $senderName
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Neznáma akcia.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
