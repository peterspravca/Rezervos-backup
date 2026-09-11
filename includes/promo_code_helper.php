<?php
// includes/promo_code_helper.php
// Fáza 5: univerzálny promo-kódový engine — zľava na doplnky (percentuálna/pevná) alebo predĺženie
// balíka o X dní (napr. predĺženie trialu). Jeden kód, jedno uplatnenie na používateľa, žiadna
// kumulácia zliav (nová zľava sa nedá uplatniť, kým je predchádzajúca ešte aktívna a nevyužitá).
//
// Kódy môžu mať voliteľného "vlastníka" (owner_user_id) — napr. osobný odporúčací kód konkrétnej
// prevádzky (KOLEGA štýl). Keď niekto taký kód uplatní, dostane svoju odmenu ako zvyčajne A NAVYŠE
// sa vlastníkovi kódu automaticky pripíše jeho vlastná odmena (owner_reward_type/value).

function promo_migrate($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS promo_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(40) NOT NULL UNIQUE,
        type ENUM('discount_percent','discount_fixed','free_days') NOT NULL,
        value DECIMAL(10,2) NOT NULL,
        max_uses INT DEFAULT NULL,
        used_count INT NOT NULL DEFAULT 0,
        expires_at DATETIME DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS promo_code_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        promo_code_id INT NOT NULL,
        user_id INT NOT NULL,
        redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_code_user (promo_code_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Aktívna (ešte nevyužitá) zľava na doplnky, uplatní sa pri najbližšom nákupe v api/addon_order.php
    try { $pdo->exec("ALTER TABLE users ADD COLUMN active_discount_percent DECIMAL(5,2) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN active_discount_fixed DECIMAL(10,2) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN active_discount_code VARCHAR(40) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN active_discount_expires_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}

    // Voliteľný "vlastník" kódu (osobný odporúčací kód) + jeho vlastná odmena za každé uplatnenie
    try { $pdo->exec("ALTER TABLE promo_codes ADD COLUMN owner_user_id INT DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE promo_codes ADD COLUMN owner_reward_type ENUM('discount_percent','discount_fixed','free_days') DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE promo_codes ADD COLUMN owner_reward_value DECIMAL(10,2) DEFAULT NULL"); } catch (Exception $e) {}
}

// Aplikuje jeden efekt (predĺženie balíka / % zľava / pevná zľava) na daného používateľa.
// Používa sa pre odmenu uplatniteľa AJ pre odmenu vlastníka kódu — rovnaká logika pre oboch.
function promo_apply_effect($pdo, $user_id, $type, $value, $est_id = 0) {
    if ($type === 'free_days') {
        $days = (int)$value;
        $exp = $pdo->prepare("SELECT subscription_expires_at FROM users WHERE id = ?");
        $exp->execute([$user_id]);
        $current_exp = $exp->fetch(PDO::FETCH_ASSOC)['subscription_expires_at'] ?? null;
        $base = ($current_exp && strtotime($current_exp) > time()) ? $current_exp : date('Y-m-d H:i:s');
        $new_exp = date('Y-m-d H:i:s', strtotime($base . " +{$days} days"));

        $pdo->prepare("UPDATE users SET subscription_expires_at = ? WHERE id = ?")->execute([$new_exp, $user_id]);
        if ($est_id > 0) {
            $pdo->prepare("UPDATE establishments SET subscription_expires_at = ? WHERE id = ?")->execute([$new_exp, $est_id]);
        } else {
            $pdo->prepare("UPDATE establishments SET subscription_expires_at = ? WHERE user_id = ?")->execute([$new_exp, $user_id]);
        }
        return "predĺženie balíka o {$days} dní";
    } elseif ($type === 'discount_percent') {
        // Poistka nad rámec validácie v admin-promo-kody.php: aj keby sa do DB dostala hodnota
        // nad 100 (staršie dáta, priama úprava DB...), nikdy neaplikujeme viac ako 100% zľavu.
        $value = min(100, max(0, $value));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $pdo->prepare("UPDATE users SET active_discount_percent = ?, active_discount_fixed = NULL, active_discount_code = 'BONUS', active_discount_expires_at = ? WHERE id = ?")
            ->execute([$value, $expires, $user_id]);
        return "zľavu {$value}% na najbližší nákup doplnkov";
    } elseif ($type === 'discount_fixed') {
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $pdo->prepare("UPDATE users SET active_discount_fixed = ?, active_discount_percent = NULL, active_discount_code = 'BONUS', active_discount_expires_at = ? WHERE id = ?")
            ->execute([$value, $expires, $user_id]);
        return "zľavu " . number_format($value, 2) . " € na najbližší nákup doplnkov";
    }
    return null;
}

// Uplatní promo kód pre daného používateľa. Vráti ['success' => bool, 'message' => string].
// Používa vždy globálny $pdo (nie mysqli $conn), bez ohľadu na to, ktorý súbor helper volá —
// config.php zaručuje, že $pdo je vždy dostupný.
function redeemPromoCode($user_id, $code, $est_id = 0) {
    global $pdo;
    promo_migrate($pdo);

    $code = strtoupper(trim((string)$code));
    if ($code === '') {
        return ['success' => false, 'message' => 'Zadajte promo kód.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        return ['success' => false, 'message' => 'Neplatný promo kód.'];
    }
    if (!$promo['is_active']) {
        return ['success' => false, 'message' => 'Tento promo kód už nie je aktívny.'];
    }
    if (!empty($promo['expires_at']) && strtotime($promo['expires_at']) < time()) {
        return ['success' => false, 'message' => 'Platnosť tohto promo kódu už vypršala.'];
    }
    if ($promo['max_uses'] !== null && (int)$promo['used_count'] >= (int)$promo['max_uses']) {
        return ['success' => false, 'message' => 'Tento promo kód už bol vyčerpaný (dosiahnutý limit použití).'];
    }
    if (!empty($promo['owner_user_id']) && (int)$promo['owner_user_id'] === (int)$user_id) {
        return ['success' => false, 'message' => 'Svoj vlastný odporúčací kód si nemôžete uplatniť.'];
    }

    // Jeden kód = jedno uplatnenie na používateľa
    $chk = $pdo->prepare("SELECT id FROM promo_code_redemptions WHERE promo_code_id = ? AND user_id = ?");
    $chk->execute([$promo['id'], $user_id]);
    if ($chk->fetch()) {
        return ['success' => false, 'message' => 'Tento promo kód ste už uplatnili.'];
    }

    // Zákaz kumulácie: ak je typ zľava a používateľ má už aktívnu nevyužitú zľavu, novú neuplatníme
    if (in_array($promo['type'], ['discount_percent', 'discount_fixed'], true)) {
        $u = $pdo->prepare("SELECT active_discount_code, active_discount_expires_at FROM users WHERE id = ?");
        $u->execute([$user_id]);
        $urow = $u->fetch(PDO::FETCH_ASSOC);
        if (!empty($urow['active_discount_code']) && (empty($urow['active_discount_expires_at']) || strtotime($urow['active_discount_expires_at']) > time())) {
            return ['success' => false, 'message' => 'Máte už aktívnu nevyužitú zľavu. Kódy sa nedajú kombinovať — najprv využite tú súčasnú.'];
        }
    }

    $effect_desc = promo_apply_effect($pdo, $user_id, $promo['type'], $promo['value'], $est_id);
    if ($effect_desc === null) {
        return ['success' => false, 'message' => 'Neznámy typ promo kódu.'];
    }
    $message = "Kód uplatnený! Získali ste {$effect_desc}.";

    $pdo->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?")->execute([$promo['id']]);
    $pdo->prepare("INSERT INTO promo_code_redemptions (promo_code_id, user_id) VALUES (?, ?)")->execute([$promo['id'], $user_id]);

    // Ak má kód nastaveného vlastníka (osobný odporúčací kód), pripíšeme aj jemu jeho vlastnú odmenu
    if (!empty($promo['owner_user_id']) && !empty($promo['owner_reward_type']) && $promo['owner_reward_value'] !== null) {
        promo_apply_effect($pdo, (int)$promo['owner_user_id'], $promo['owner_reward_type'], $promo['owner_reward_value']);
    }

    return ['success' => true, 'message' => $message];
}
