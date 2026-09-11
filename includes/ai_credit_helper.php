<?php
// includes/ai_credit_helper.php — AI kredity pre firemné účty (prevádzky).
// Zákazníci nie sú nijako obmedzovaní, toto sa týka iba biznis AI nástrojov
// (AI asistent v e-mailoch, AI asistent v marketingových kampaniach).
//
// Dve oddelené dávky:
//  - ai_credits_free: bezplatná mesačná dávka podľa balíka prevádzky (FREE 5, ŠTART 50, PRO 100, VIP 150),
//    každý kalendárny mesiac sa obnoví na plnú hodnotu (neprenáša sa, nekumuluje).
//  - ai_credits_purchased: dokúpené cez Peňaženku, nikdy sa neresetujú, minú sa až keď dôjde mesačná dávka.

const AI_CREDIT_PACKAGES = [
    50  => 3.00,
    100 => 5.00,
    250 => 10.00,
];

const AI_CREDIT_MONTHLY_BY_TIER = [
    'free'  => 5,
    'start' => 50,
    'pro'   => 100,
    'vip'   => 150,
];

function ai_credit_migrate($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS ai_credits_free INT NOT NULL DEFAULT 0");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS ai_credits_purchased INT NOT NULL DEFAULT 0");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS ai_credits_reset_month VARCHAR(7) NULL");
}

/**
 * Obnoví mesačnú bezplatnú dávku, ak ešte v tomto kalendárnom mesiaci nebola obnovená.
 */
function ai_credit_ensure_reset($conn, $user_id) {
    ai_credit_migrate($conn);
    $currentMonth = date('Y-m');

    $stmt = $conn->prepare("SELECT ai_credits_reset_month FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && $row['ai_credits_reset_month'] === $currentMonth) return;

    $stmt2 = $conn->prepare("SELECT subscription_tier FROM establishments WHERE user_id = ? LIMIT 1");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $est = $stmt2->get_result()->fetch_assoc();
    $tier = $est['subscription_tier'] ?? 'free';
    $monthlyAmount = AI_CREDIT_MONTHLY_BY_TIER[$tier] ?? AI_CREDIT_MONTHLY_BY_TIER['free'];

    $stmt3 = $conn->prepare("UPDATE users SET ai_credits_free = ?, ai_credits_reset_month = ? WHERE id = ?");
    $stmt3->bind_param("isi", $monthlyAmount, $currentMonth, $user_id);
    $stmt3->execute();
}

function ai_credit_balance($conn, $user_id) {
    ai_credit_ensure_reset($conn, $user_id);
    $stmt = $conn->prepare("SELECT ai_credits_free + ai_credits_purchased AS total FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['total'] : 0;
}

/**
 * Odpočíta 1 AI kredit — najprv z mesačnej bezplatnej dávky, potom z dokúpených.
 * Vracia ['ok' => bool, 'remaining' => int].
 */
function ai_credit_consume($conn, $user_id) {
    ai_credit_ensure_reset($conn, $user_id);

    $stmt = $conn->prepare("UPDATE users SET ai_credits_free = ai_credits_free - 1 WHERE id = ? AND ai_credits_free > 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $stmt2 = $conn->prepare("UPDATE users SET ai_credits_purchased = ai_credits_purchased - 1 WHERE id = ? AND ai_credits_purchased > 0");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        if ($stmt2->affected_rows === 0) {
            return ['ok' => false, 'remaining' => 0];
        }
    }
    return ['ok' => true, 'remaining' => ai_credit_balance($conn, $user_id)];
}
