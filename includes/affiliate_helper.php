<?php
// includes/affiliate_helper.php
// Fáza 5: Affiliate program pre "virtuálnych obchodníkov" — podľa promo.html (sekcia 5).
// Ktokoľvek prihlásený (prevádzka aj zákazník) môže požiadať o zapojenie, admin schváli/zamietne.
// Po schválení dostane vlastný krátky kód na registráciu ("odporúčací/promo kód" pole, ktoré už
// appka mala). Provízia sa pripíše LEN vtedy, keď sa referovaný účet stane platiacou prevádzkou
// (aktivuje si ROČNÝ platený balík) — nie za obyčajnú registráciu ani mesačnú aktiváciu.

function affiliate_migrate($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;

    try { $pdo->exec("ALTER TABLE users ADD COLUMN affiliate_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN affiliate_code VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN affiliate_requested_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN referred_by_affiliate_id INT DEFAULT NULL"); } catch (Exception $e) {}
    // Počítadlo pre mesačné platby (namiesto ročných) — po 12 zaplatených mesiacoch v kuse sa
    // priebežná odmena pripíše rovnako, ako keby si prevádzka obnovila ročný balík.
    try { $pdo->exec("ALTER TABLE users ADD COLUMN affiliate_monthly_tier VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN affiliate_monthly_count INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS affiliate_commissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        affiliate_user_id INT NOT NULL,
        referred_user_id INT NOT NULL,
        tier VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('signup','renewal') NOT NULL DEFAULT 'signup',
        period_year INT NOT NULL,
        status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        paid_at DATETIME DEFAULT NULL,
        UNIQUE KEY uniq_referred_year (referred_user_id, period_year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Staršie inštalácie (pred zavedením priebežnej ročnej odmeny) môžu mať tabuľku bez týchto
    // stĺpcov a so starým prísnejším UNIQUE(referred_user_id) — doplníme/uvoľníme ich.
    try { $pdo->exec("ALTER TABLE affiliate_commissions ADD COLUMN type ENUM('signup','renewal') NOT NULL DEFAULT 'signup'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE affiliate_commissions ADD COLUMN period_year INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE affiliate_commissions DROP INDEX uniq_referred"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE affiliate_commissions ADD UNIQUE KEY uniq_referred_year (referred_user_id, period_year)"); } catch (Exception $e) {}
}

// Sadzby podľa promo.html — jednorazová odmena za PRVÝ predaný ROČNÝ balík
const AFFILIATE_COMMISSION_RATES = [
    'start' => 20.00,
    'pro'   => 35.00,
    'vip'   => 60.00,
];

// Priebežná odmena "za starostlivosť" — % z ceny ročného balíka pri KAŽDOM ďalšom obnovení
// (nasledujúce roky po prvom predaji), pokým sa prevádzka o balík stará a naďalej platí ročne.
const AFFILIATE_RENEWAL_PERCENT = 10;
const AFFILIATE_YEARLY_PRICES = [
    'start' => 70.80,
    'pro'   => 154.80,
    'vip'   => 322.80,
];

// Vyskúša, či zadaný kód patrí schválenému obchodníkovi (volané z users/verify_code.php pri
// registrácii popri kontrole promo kódu) — ak áno, zapamätá si, kto koho priviedol.
function affiliate_try_attribute($pdo, $new_user_id, $code) {
    affiliate_migrate($pdo);
    $code = trim((string)$code);
    if ($code === '') return false;

    $stmt = $pdo->prepare("SELECT id FROM users WHERE affiliate_code = ? AND affiliate_status = 'approved' LIMIT 1");
    $stmt->execute([$code]);
    $affiliate = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$affiliate || (int)$affiliate['id'] === (int)$new_user_id) return false;

    $pdo->prepare("UPDATE users SET referred_by_affiliate_id = ? WHERE id = ?")->execute([$affiliate['id'], $new_user_id]);
    return true;
}

// Uloží jednu províziu obchodníkovi (spoločné pre ročnú aj "napočítanú" mesačnú cestu).
function affiliate_insert_commission($pdo, $affiliateId, $user_id, $tier, $amount, $type) {
    if ($amount <= 0) return;
    $periodYear = (int)date('Y');
    try {
        $ins = $pdo->prepare("INSERT INTO affiliate_commissions (affiliate_user_id, referred_user_id, tier, amount, type, period_year) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$affiliateId, $user_id, $tier, $amount, $type, $periodYear]);
    } catch (Exception $e) {
        // UNIQUE uniq_referred_year zabráni duplicitnej provízii za rovnaký rok — tichá no-op.
    }
}

// Volané pri KAŽDEJ aktivácii plateného balíka (api/business.php, action=change_tier) — ročnej
// aj mesačnej. Nikdy sa nevolá pri nákupe SMS kreditu ani iných doplnkov (tie idú cez peňaženku/
// api/addon_order.php, nie cez zmenu balíka), takže sa do provízie automaticky nikdy nedostanú.
//
// Ročná aktivácia: prvý predaj = jednorazová odmena, každé ďalšie obnovenie = priebežná odmena
// (AFFILIATE_RENEWAL_PERCENT z ceny balíka).
// Mesačná aktivácia: žiadna jednorazová odmena nikdy — až po 12 mesiacoch zaplatených v kuse
// (rovnaká hodnota, akoby si prevádzka obnovila ročný balík) sa pripíše rovnaká priebežná odmena
// ako pri ročnom obnovení, počítadlo sa vynuluje a začína sa počítať ďalší cyklus.
function affiliate_record_commission_if_applicable($pdo, $user_id, $tier, $period) {
    affiliate_migrate($pdo);
    if (!isset(AFFILIATE_COMMISSION_RATES[$tier])) return;

    $u = $pdo->prepare("SELECT referred_by_affiliate_id, affiliate_monthly_tier, affiliate_monthly_count FROM users WHERE id = ?");
    $u->execute([$user_id]);
    $row = $u->fetch(PDO::FETCH_ASSOC);
    $affiliateId = $row['referred_by_affiliate_id'] ?? null;
    if (empty($affiliateId)) return;

    if ($period === 'yearly') {
        $hasAny = $pdo->prepare("SELECT COUNT(*) FROM affiliate_commissions WHERE referred_user_id = ?");
        $hasAny->execute([$user_id]);
        $isFirstSale = ((int)$hasAny->fetchColumn() === 0);

        if ($isFirstSale) {
            affiliate_insert_commission($pdo, $affiliateId, $user_id, $tier, AFFILIATE_COMMISSION_RATES[$tier], 'signup');
        } else {
            $amount = round((AFFILIATE_YEARLY_PRICES[$tier] ?? 0) * AFFILIATE_RENEWAL_PERCENT / 100, 2);
            affiliate_insert_commission($pdo, $affiliateId, $user_id, $tier, $amount, 'renewal');
        }
        // Prechod na ročný balík reštartuje aj prípadné rozbehnuté mesačné počítadlo
        $pdo->prepare("UPDATE users SET affiliate_monthly_tier = NULL, affiliate_monthly_count = 0 WHERE id = ?")->execute([$user_id]);
        return;
    }

    if ($period === 'monthly') {
        // Zmena balíka počas počítania mesiacov = počítadlo sa reštartuje pre nový balík
        $count = ((string)$row['affiliate_monthly_tier'] === (string)$tier) ? (int)$row['affiliate_monthly_count'] + 1 : 1;

        if ($count >= 12) {
            $amount = round((AFFILIATE_YEARLY_PRICES[$tier] ?? 0) * AFFILIATE_RENEWAL_PERCENT / 100, 2);
            affiliate_insert_commission($pdo, $affiliateId, $user_id, $tier, $amount, 'renewal');
            $count = 0; // ďalší 12-mesačný cyklus začína odznova
        }

        $pdo->prepare("UPDATE users SET affiliate_monthly_tier = ?, affiliate_monthly_count = ? WHERE id = ?")->execute([$tier, $count, $user_id]);
    }
}
