<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$isLoggedIn = isset($_SESSION['user_id']);
$isBusiness = $isLoggedIn && $_SESSION['user_role'] === 'business';

if (!$isBusiness) {
    echo json_encode(['success' => false, 'error' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action !== 'order_addons') {
    echo json_encode(['success' => false, 'error' => 'Neznáma akcia.']);
    exit;
}

// Autoritatívny cenník doplnkov (server-side, klientske ceny sa neberú do úvahy).
// earned_ok = true → smie sa platiť z nazbieraných/vyťažených kreditov (credit_earned),
// earned_ok = false → musí byť zaplatené reálnymi prostriedkami (credit_purchased).
$ADDON_CATALOG = [
    'employee'     => ['name' => 'Ďalší zamestnanec',   'has_period' => true,  'monthly' => 4.90, 'yearly' => 46.80, 'earned_ok' => false],
    'branch'       => ['name' => 'Ďalšia pobočka',       'has_period' => true,  'monthly' => 9.90, 'yearly' => 106.80, 'earned_ok' => false],
    'ad_top'       => ['name' => 'TOP inzerátu',         'has_period' => false, 'flat' => 0.50,    'earned_ok' => true],
    'ad_highlight' => ['name' => 'Zvýraznenie inzerátu', 'has_period' => false, 'flat' => 0.50,    'earned_ok' => true],
];

try {
    // Self-migrácia: log tabuľka objednávok doplnkov (rovnaký vzor ako v api/wallet.php)
    $chk = $pdo->query("SHOW TABLES LIKE 'addon_orders'");
    if ($chk && $chk->rowCount() === 0) {
        $pdo->exec("CREATE TABLE addon_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            addon_key VARCHAR(50) NOT NULL,
            period VARCHAR(10) DEFAULT NULL,
            price DECIMAL(8,2) NOT NULL,
            paid_from_earned DECIMAL(8,2) NOT NULL DEFAULT 0,
            paid_from_purchased DECIMAL(8,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
        )");
    }

    $itemsRaw = $_POST['items'] ?? '[]';
    $items = json_decode($itemsRaw, true);
    if (!is_array($items) || count($items) === 0) {
        echo json_encode(['success' => false, 'error' => 'Košík je prázdny.']);
        exit;
    }

    // Validuj a napočítaj ceny podľa autoritatívneho cenníka (nie podľa toho, čo poslal klient)
    $orderLines = [];
    foreach ($items as $it) {
        $key = trim($it['key'] ?? '');
        if (!isset($ADDON_CATALOG[$key])) {
            echo json_encode(['success' => false, 'error' => 'Neplatná položka v košíku.']);
            exit;
        }
        $def = $ADDON_CATALOG[$key];
        $period = null;
        if ($def['has_period']) {
            $period = (trim($it['period'] ?? 'monthly') === 'yearly') ? 'yearly' : 'monthly';
            $price = ($period === 'yearly') ? (float)$def['yearly'] : (float)$def['monthly'];
        } else {
            $price = (float)$def['flat'];
        }
        $orderLines[] = ['key' => $key, 'name' => $def['name'], 'period' => $period, 'price' => $price, 'earned_ok' => $def['earned_ok']];
    }

    // Kombo zľava: TOP + Zvýraznenie spolu v jednej objednávke = 0,90 € namiesto 1,00 €
    $keys = array_column($orderLines, 'key');
    if (in_array('ad_top', $keys, true) && in_array('ad_highlight', $keys, true)) {
        foreach ($orderLines as &$line) {
            if ($line['key'] === 'ad_top') { $line['price'] -= 0.05; }
            if ($line['key'] === 'ad_highlight') { $line['price'] -= 0.05; }
        }
        unset($line);
    }

    // Aktuálny stav peňaženky
    $stmt = $pdo->prepare("SELECT credit, credit_purchased, credit_earned, active_discount_percent, active_discount_fixed, active_discount_code, active_discount_expires_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$wallet) {
        echo json_encode(['success' => false, 'error' => 'Používateľ nebol nájdený.']);
        exit;
    }

    // Aktívna promo zľava (Fáza 5) — uplatní sa na tento nákup a potom sa spotrebuje (jednorazovo)
    $hasActiveDiscount = !empty($wallet['active_discount_code'])
        && (empty($wallet['active_discount_expires_at']) || strtotime($wallet['active_discount_expires_at']) > time());
    $discountApplied = 0.0;
    if ($hasActiveDiscount) {
        if (!empty($wallet['active_discount_percent'])) {
            // Poistka: nech je hodnota v DB akákoľvek, zľava nikdy neprekročí 100 % (inak by
            // cena vyšla do záporu a odčítanie záporu by zákazníkovi navýšilo kredit namiesto platby).
            $pct = min(100, max(0, (float)$wallet['active_discount_percent']));
            foreach ($orderLines as &$line) {
                $reduced = round($line['price'] * (1 - $pct / 100), 2);
                $discountApplied += round($line['price'] - $reduced, 2);
                $line['price'] = $reduced;
            }
            unset($line);
        } elseif (!empty($wallet['active_discount_fixed'])) {
            $remaining = (float)$wallet['active_discount_fixed'];
            foreach ($orderLines as &$line) {
                if ($remaining <= 0) break;
                $reduce = min($remaining, $line['price']);
                $line['price'] = round($line['price'] - $reduce, 2);
                $remaining -= $reduce;
                $discountApplied += $reduce;
            }
            unset($line);
        }
    }

    $availEarned = (float)$wallet['credit_earned'];
    $availPurchased = (float)$wallet['credit_purchased'];

    // Simulácia odpočtu pre celý košík naraz (všetko alebo nič)
    $totalEarnedUsed = 0.0;
    $totalPurchasedUsed = 0.0;
    $totalMissing = 0.0;

    foreach ($orderLines as &$line) {
        $price = round($line['price'], 2);
        if ($line['earned_ok']) {
            $useEarned = min($price, $availEarned);
            $usePurchased = round($price - $useEarned, 2);
        } else {
            $useEarned = 0.0;
            $usePurchased = $price;
        }

        if ($usePurchased > $availPurchased + 0.001) {
            $totalMissing += round($usePurchased - $availPurchased, 2);
            $usePurchased = $availPurchased; // minimálne toľko, koľko je — zvyšok je "chýbajúca suma"
        }

        $availEarned = max(0, round($availEarned - $useEarned, 2));
        $availPurchased = max(0, round($availPurchased - $usePurchased, 2));

        $totalEarnedUsed += $useEarned;
        $totalPurchasedUsed += $usePurchased;

        $line['use_earned'] = $useEarned;
        $line['use_purchased'] = $usePurchased;
    }
    unset($line);

    if ($totalMissing > 0.001) {
        echo json_encode([
            'success' => false,
            'need_topup' => true,
            'missing' => round($totalMissing, 2),
            'error' => 'Nedostatočný zostatok v Peňaženke. Chýba vám ' . number_format($totalMissing, 2, ',', ' ') . ' €. Doplňte si Peňaženku a skúste znova.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Všetko sedí — zapíšeme naraz
    $stmtUpd = $pdo->prepare("UPDATE users SET credit = GREATEST(0, credit - ?), credit_earned = GREATEST(0, credit_earned - ?), credit_purchased = GREATEST(0, credit_purchased - ?) WHERE id = ?");
    $stmtUpd->execute([$totalEarnedUsed + $totalPurchasedUsed, $totalEarnedUsed, $totalPurchasedUsed, $user_id]);

    $stmtOrder = $pdo->prepare("INSERT INTO addon_orders (user_id, addon_key, period, price, paid_from_earned, paid_from_purchased) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtTx = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES (?, 'addon_purchase', ?, 'EUR', 'completed', ?)");

    $namesList = [];
    foreach ($orderLines as $line) {
        $stmtOrder->execute([$user_id, $line['key'], $line['period'], $line['price'], $line['use_earned'], $line['use_purchased']]);
        $desc = 'Doplnok: ' . $line['name'] . ($line['period'] ? (' (' . ($line['period'] === 'yearly' ? 'ročne' : 'mesačne') . ')') : '');
        $stmtTx->execute([$user_id, -$line['price'], $desc]);
        $namesList[] = $line['name'];
    }

    // Promo zľava sa použije len raz — po úspešnom nákupe ju vynulujeme
    if ($hasActiveDiscount) {
        $pdo->prepare("UPDATE users SET active_discount_percent = NULL, active_discount_fixed = NULL, active_discount_code = NULL, active_discount_expires_at = NULL WHERE id = ?")->execute([$user_id]);
    }

    $newBalance = (float)$pdo->query("SELECT credit FROM users WHERE id = {$user_id}")->fetchColumn();

    $discountMsg = ($discountApplied > 0) ? (' (uplatnená zľava ' . number_format($discountApplied, 2, ',', ' ') . ' €)') : '';
    echo json_encode([
        'success' => true,
        'message' => 'Objednávka bola úspešne aktivovaná: ' . implode(', ', $namesList) . '.' . $discountMsg,
        'new_balance' => $newBalance,
        'discount_applied' => $discountApplied
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
