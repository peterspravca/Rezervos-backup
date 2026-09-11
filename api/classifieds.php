<?php
// ── classifieds.php — Inzercia API ───────────────────────────────────────────
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) { http_response_code(200); header('Content-Type: application/json; charset=utf-8'); }
        echo json_encode(['success' => false, 'error' => 'PHP error: ' . $e['message']]);
    }
});

if (session_status() === PHP_SESSION_NONE) session_start();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Čítanie inzerátov (get_listings/list) je verejné — bez prihlásenia.
// Všetko ostatné (pridanie, mazanie, topovanie, moje inzeráty, stav kreditov) vyžaduje prihlásenie.
$publicActions = ['get_listings', 'list', 'ping', 'get_ad', 'increment_views'];
$actionCheck = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));
if (!in_array($actionCheck, $publicActions, true) && empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nie ste prihlásený.']); exit;
}
$userId = (int)($_SESSION['user_id'] ?? 0);

// Vypni mysqli výnimky — namiesto throw vráti false (PHP 8.1+ kompatibilné)
mysqli_report(MYSQLI_REPORT_OFF);

// DB — priame pripojenie bez config.php
$h = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($h, '.cz') !== false) {
    $db = @new mysqli('db1.usr.sk', 'volnekreslo.cz', 'rVslYqLz/DA19MWd', 'volnekreslocz');
} else {
    $db = @new mysqli('db1.usr.sk', 'volnekreslo.sk', 'bq!wL0K*zWH)XT]0', 'volnekreslosk');
}
if ($db->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB: ' . $db->connect_error]); exit;
}
$db->set_charset('utf8mb4');
require_once __DIR__ . '/../includes/content_translation_helper.php';

function dbq($db, $sql) { $r = $db->query($sql); return $r ?: null; }
function dbe($db, $v)   { return $db->real_escape_string(trim((string)($v ?? ''))); }
function dbr($db, $sql) { $r = dbq($db, $sql); return ($r && $r->num_rows > 0) ? $r->fetch_assoc() : null; }

// Zaistenie stĺpcov
@$db->query("CREATE TABLE IF NOT EXISTS classifieds (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(32) NOT NULL DEFAULT 'work',
    listing_type VARCHAR(32) DEFAULT NULL,
    title VARCHAR(255) NOT NULL DEFAULT '',
    specialization VARCHAR(128) DEFAULT NULL,
    description TEXT,
    location VARCHAR(128) DEFAULT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    email VARCHAR(128) DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT NULL,
    `condition` VARCHAR(32) DEFAULT NULL,
    price_unit VARCHAR(32) DEFAULT NULL,
    available_from DATE DEFAULT NULL,
    images TEXT DEFAULT NULL,
    boosted_until DATETIME DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type), INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// MySQL 5.7 safe — bez IF NOT EXISTS v ALTER TABLE
function addColIfMissing($db, $table, $col, $def) {
    $safe = str_replace('`', '', $col);
    $r = $db->query("SHOW COLUMNS FROM `$table` LIKE '$safe'");
    if ($r && $r->num_rows === 0) @$db->query("ALTER TABLE `$table` ADD COLUMN `$safe` $def");
}
addColIfMissing($db, 'classifieds', 'images',          'TEXT DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'boosted_until',   'DATETIME NULL DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'listing_type',    'VARCHAR(32) DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'specialization',  'VARCHAR(128) DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'condition',       "VARCHAR(32) DEFAULT NULL");
addColIfMissing($db, 'classifieds', 'price_unit',      'VARCHAR(32) DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'available_from',  'DATE DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'expires_at',      'DATETIME NULL DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'duration',        "VARCHAR(16) DEFAULT 'month'");
addColIfMissing($db, 'classifieds', 'views',            'INT UNSIGNED NOT NULL DEFAULT 0');
addColIfMissing($db, 'classifieds', 'status',           "VARCHAR(16) NOT NULL DEFAULT 'active'");
addColIfMissing($db, 'classifieds', 'morning_expires_at', 'DATETIME NULL DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'prime_expires_at',   'DATETIME NULL DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'vip_glow_expires_at', 'DATETIME NULL DEFAULT NULL');
addColIfMissing($db, 'classifieds', 'vip_badge_expires_at', 'DATETIME NULL DEFAULT NULL');
addColIfMissing($db, 'users',       'ad_credits',       'INT DEFAULT 0');
addColIfMissing($db, 'users',       'ad_credits_month', 'VARCHAR(7) DEFAULT NULL');

function getUserAdStatus($db, $userId) {
    $PRICE_WEEK  = 0.50;
    $PRICE_MONTH = 1.50;
    $LIMITS   = ['free' => 0, 'start' => 0, 'pro' => 3, 'vip' => 6];
    // Mesačné bezplatné kredity dostávajú len skutočné prevádzky (podľa balíka establishments.subscription_tier).
    // Zákazník/admin nemá establishment — vždy platí z Peňaženky, bez ohľadu na users.subscription_tier
    // (ten stĺpec označuje iné predplatné, napr. Kreslo Hunter, nie oprávnenie na bezplatné inzeráty).
    $tier = 'free';
    $r = dbr($db, "SELECT subscription_tier FROM establishments WHERE user_id=$userId LIMIT 1");
    if ($r) { $tier = strtolower($r['subscription_tier'] ?? 'free'); }
    $limit = $LIMITS[$tier] ?? 0;
    $month = date('Y-m');
    $u = dbr($db, "SELECT ad_credits, ad_credits_month, credit, credit_purchased FROM users WHERE id=$userId LIMIT 1");
    if (!$u) $u = ['ad_credits' => 0, 'ad_credits_month' => null, 'credit' => 0, 'credit_purchased' => 0];
    if (($u['ad_credits_month'] ?? '') !== $month) {
        dbq($db, "UPDATE users SET ad_credits=$limit, ad_credits_month='$month' WHERE id=$userId");
        $u['ad_credits'] = $limit;
    }
    return ['tier' => $tier, 'monthly_limit' => $limit, 'ad_credits' => (int)$u['ad_credits'],
            'wallet' => (float)$u['credit'],
            // Samotné zverejnenie inzerátu sa smie platiť LEN reálnymi peniazmi (nie z nazbieraného/vyťaženého kreditu)
            'wallet_purchased' => (float)($u['credit_purchased'] ?? 0),
            'ad_price_week' => $PRICE_WEEK, 'ad_price_month' => $PRICE_MONTH,
            'ad_price' => $PRICE_WEEK,       // default pre spätnu kompatibilitu
            'credits_week'  => 1,            // kolko kreditov stoji tyzdenný inzerat
            'credits_month' => 3];           // kolko kreditov stoji mesačný inzerat
}

$action  = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));
$allowed = ['work','rental','sale','courses','other','people_seek','people_offer','equipment','chair'];
$legacy  = ['people' => 'work', 'equipment' => 'sale', 'chair' => 'rental'];

switch ($action) {

case 'ping':
    echo json_encode(['success' => true, 'user_id' => $userId]); break;

case 'get_ad_status':
    $ads = getUserAdStatus($db, $userId);
    echo json_encode(['success' => true] + $ads); break;

case 'get_listings':
case 'list':
    $type = dbe($db, $_GET['type'] ?? $_POST['type'] ?? '');
    if (isset($legacy[$type])) $type = $legacy[$type];
    if ($type === 'all') {
        $tc = '1=1';
    } else {
        if (!in_array($type, $allowed)) { echo json_encode(['success' => false, 'error' => 'Neplatný typ.']); break; }
        if ($type === 'work')   $tc = "c.type IN ('work','people_seek','people_offer')";
        elseif ($type === 'sale')   $tc = "c.type IN ('sale','equipment')";
        elseif ($type === 'rental') $tc = "c.type IN ('rental','chair')";
        else $tc = "c.type = '$type'";
    }
    // ORDER BY bezpečne — boostové stĺpce môžu chýbať na starších inštaláciách
    $bchk    = $db->query("SHOW COLUMNS FROM classifieds LIKE 'boosted_until'");
    $orderBy = ($bchk && $bchk->num_rows > 0)
               ? "(c.boosted_until > NOW() OR c.morning_expires_at > NOW() OR c.prime_expires_at > NOW()) DESC, c.created_at DESC"
               : "c.created_at DESC";

    // Základný select bez user stĺpcov (users.name môže byť iný názov)
    // expires_at NULL = staré inzeráty bez expirácie (zobrazíme ich), inak musí byť v budúcnosti
    $expChk = $db->query("SHOW COLUMNS FROM classifieds LIKE 'expires_at'");
    $expCond = ($expChk && $expChk->num_rows > 0) ? "AND (c.expires_at IS NULL OR c.expires_at > NOW())" : "";

    $searchCond = "";
    $q = trim($_GET['q'] ?? $_POST['q'] ?? '');
    if ($q !== '') {
        $qEsc = dbe($db, $q);
        $searchCond .= " AND (c.title LIKE '%$qEsc%' OR c.description LIKE '%$qEsc%' OR c.specialization LIKE '%$qEsc%')";
    }
    $cityQ = trim($_GET['city'] ?? $_POST['city'] ?? '');
    if ($cityQ !== '') {
        $cityEsc = dbe($db, $cityQ);
        $searchCond .= " AND c.location LIKE '%$cityEsc%'";
    }

    $res = dbq($db, "SELECT c.* FROM classifieds c
                     WHERE c.is_active=1 AND $tc $expCond $searchCond
                     ORDER BY $orderBy LIMIT 100");
    if (!$res) { echo json_encode(['success' => false, 'error' => 'DB: ' . $db->error]); break; }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $r['images_arr'] = !empty($r['images']) ? (json_decode($r['images'], true) ?: []) : [];
        $r['title'] = ct_get($db, 'classified', $r['id'], 'title', $r['title']);
        $r['description'] = ct_get($db, 'classified', $r['id'], 'description', $r['description']);
        $rows[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $rows]); break;

case 'get_my_listings':
case 'my_list':
    $res = dbq($db, "SELECT * FROM classifieds WHERE user_id=$userId AND is_active=1 ORDER BY created_at DESC");
    if (!$res) { echo json_encode(['success' => false, 'error' => 'DB: ' . $db->error]); break; }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $r['images_arr'] = !empty($r['images']) ? (json_decode($r['images'], true) ?: []) : [];
        $rows[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $rows]); break;

case 'get_ad':
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'Chýba ID.']); break; }
    $r = dbr($db, "SELECT * FROM classifieds WHERE id=$id AND is_active=1 LIMIT 1");
    if (!$r) { echo json_encode(['success' => false, 'error' => 'Inzerát sa nenašiel.']); break; }
    $r['images_arr'] = !empty($r['images']) ? (json_decode($r['images'], true) ?: []) : [];
    $r['is_owner'] = $userId && (int)$r['user_id'] === $userId;
    if (empty($r['is_owner'])) {
        $r['title'] = ct_get($db, 'classified', $r['id'], 'title', $r['title']);
        $r['description'] = ct_get($db, 'classified', $r['id'], 'description', $r['description']);
    }
    echo json_encode(['success' => true, 'data' => $r]); break;

case 'increment_views':
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'Chýba ID.']); break; }
    dbq($db, "UPDATE classifieds SET views=views+1 WHERE id=$id AND is_active=1");
    echo json_encode(['success' => true]); break;

case 'update_status':
    $id = (int)($_POST['id'] ?? 0);
    $status = dbe($db, $_POST['status'] ?? 'active');
    if (!in_array($status, ['active', 'sold', 'reserved'], true)) { echo json_encode(['success' => false, 'error' => 'Neplatný stav.']); break; }
    if (!$id) { echo json_encode(['success' => false, 'error' => 'Chýba ID.']); break; }
    dbq($db, "UPDATE classifieds SET status='$status' WHERE id=$id AND user_id=$userId");
    echo json_encode($db->affected_rows > 0
        ? ['success' => true, 'message' => 'Stav bol zmenený.']
        : ['success' => false, 'error' => 'Inzerát sa nenašiel.']); break;

case 'create_listing':
case 'create':
    $type = dbe($db, $_POST['type'] ?? '');
    if (isset($legacy[$type])) $type = $legacy[$type];
    if (!in_array($type, $allowed)) { echo json_encode(['success' => false, 'error' => 'Neplatný typ.']); break; }
    $title  = dbe($db, $_POST['title']       ?? '');
    $desc   = dbe($db, $_POST['description'] ?? '');
    if (!$title && !$desc) { echo json_encode(['success' => false, 'error' => 'Vyplňte aspoň názov alebo popis.']); break; }
    $loc    = dbe($db, $_POST['location']      ?? '');
    $phone  = dbe($db, $_POST['phone']         ?? $_POST['contact_phone'] ?? '');
    $email  = dbe($db, $_POST['email']         ?? $_POST['contact_email'] ?? '');
    $spec   = dbe($db, $_POST['specialization'] ?? '');
    $lt     = dbe($db, $_POST['listing_type']  ?? $_POST['listing_subtype'] ?? '');
    $pRaw   = is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : null;
    $pSQL   = $pRaw === null ? 'NULL' : $pRaw;
    $cond   = dbe($db, $_POST['condition']     ?? '');
    $pu     = dbe($db, $_POST['price_unit']    ?? '');
    $af     = dbe($db, $_POST['available_from'] ?? '');
    $afSQL  = $af ? "'$af'" : 'NULL';

    $duration = in_array($_POST['duration'] ?? '', ['week','month']) ? $_POST['duration'] : 'week';
    $ads = getUserAdStatus($db, $userId);

    $creditsNeeded = $duration === 'week' ? 1 : 3;
    $adPrice       = $duration === 'week' ? $ads['ad_price_week'] : $ads['ad_price_month'];

    $usedFree = $usedWallet = false;
    if ($ads['ad_credits'] >= $creditsNeeded) {
        // Dostatok kreditov — strhni
        dbq($db, "UPDATE users SET ad_credits=ad_credits-$creditsNeeded WHERE id=$userId");
        $usedFree = true;
    } elseif ($ads['wallet_purchased'] >= $adPrice) {
        // Platba z peňaženky — len z reálne dobitého kreditu, nazbieraný/vyťažený sa na zverejnenie inzerátu nepoužíva
        $p = $adPrice;
        dbq($db, "UPDATE users SET credit=GREATEST(0,credit-$p), credit_purchased=GREATEST(0,credit_purchased-$p) WHERE id=$userId");
        $usedWallet = true;
    } else {
        $haveC = $ads['ad_credits'];
        $needC = $creditsNeeded;
        $msg   = $haveC > 0
            ? "Nemáte dostatok kreditov (potrebujete $needC, máte $haveC). Doplňte Peňaženku o " . number_format($adPrice,2,',','') . " €."
            : "Potrebujete $needC kredit" . ($needC > 1 ? 'y' : '') . " alebo " . number_format($adPrice,2,',','') . " € v Peňaženke.";
        echo json_encode(['success' => false, 'error' => $msg, 'need_credit' => true]); break;
    }
    $expiresAt = $duration === 'week'
        ? date('Y-m-d H:i:s', strtotime('+7 days'))
        : date('Y-m-d H:i:s', strtotime('+30 days'));

    $imgs = [];
    if (!empty($_FILES['images']['tmp_name'])) {
        $dir = '../uploads/classifieds/'; if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $f = $_FILES['images']; $multi = is_array($f['tmp_name']); $cnt = $multi ? count($f['tmp_name']) : 1;
        $mainIdx = (int)($_POST['main_image_index'] ?? 0); $saved = [];
        for ($i = 0; $i < min($cnt, 5); $i++) {
            $tmp = $multi ? $f['tmp_name'][$i] : $f['tmp_name'];
            $nm  = $multi ? $f['name'][$i]     : $f['name'];
            $sz  = $multi ? $f['size'][$i]      : $f['size'];
            $er  = $multi ? $f['error'][$i]     : $f['error'];
            if ($er !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || $sz > 5242880) continue;
            $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;
            $fn = 'cl_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($tmp, $dir . $fn)) $saved[$i] = 'uploads/classifieds/' . $fn;
        }
        if (isset($saved[$mainIdx])) { $imgs[] = $saved[$mainIdx]; unset($saved[$mainIdx]); }
        foreach ($saved as $pp) $imgs[] = $pp;
    }
    $imgsJ = dbe($db, json_encode($imgs));

    $dur  = dbe($db, $duration);
    $expSQL = "'$expiresAt'";
    $ok = dbq($db, "INSERT INTO classifieds
        (user_id,type,listing_type,title,specialization,description,location,phone,email,price,`condition`,price_unit,available_from,images,is_active,duration,expires_at)
        VALUES ($userId,'$type','$lt','$title','$spec','$desc','$loc','$phone','$email',$pSQL,'$cond','$pu',$afSQL,'$imgsJ',1,'$dur',$expSQL)");
    if ($ok) {
        $a2 = getUserAdStatus($db, $userId);
        $durLabel = $duration === 'week' ? '7 dní' : '30 dní';
        $note = $usedFree
            ? "Použité $creditsNeeded " . ($creditsNeeded > 1 ? 'kredity' : 'kredit') . ". Zostatok: " . $a2['ad_credits'] . " kreditov."
            : number_format($adPrice,2,',','') . ' € strhnutých z Peňaženky.';
        if ($usedWallet) {
            $negPrice = -$adPrice;
            // $title je už dbe()-escapované vyššie (použité aj v INSERT INTO classifieds)
            dbq($db, "INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES ($userId, 'classified_listing', $negPrice, 'EUR', 'completed', 'Zverejnenie inzerátu \"$title\" ($durLabel)')");
        }
        echo json_encode(['success' => true, 'id' => $db->insert_id, 'message' => 'Inzerát bol pridaný.', 'billing_note' => $note, 'expires_in' => $durLabel]);
    } else {
        if ($usedFree)   dbq($db, "UPDATE users SET ad_credits=ad_credits+1 WHERE id=$userId");
        if ($usedWallet) dbq($db, "UPDATE users SET credit=credit+$adPrice WHERE id=$userId");
        echo json_encode(['success' => false, 'error' => 'DB: ' . $db->error]);
    }
    break;

case 'delete_listing':
case 'delete':
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'Chýba ID.']); break; }
    dbq($db, "UPDATE classifieds SET is_active=0 WHERE id=$id AND user_id=$userId");
    echo json_encode($db->affected_rows > 0
        ? ['success' => true, 'message' => 'Inzerát bol odstránený.']
        : ['success' => false, 'error' => 'Inzerát sa nenašiel.']);
    break;

case 'update_listing':
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'Chýba ID.']); break; }
    $t = dbe($db, $_POST['title'] ?? ''); $d = dbe($db, $_POST['description'] ?? '');
    $l = dbe($db, $_POST['location'] ?? ''); $ph = dbe($db, $_POST['phone'] ?? '');
    $em = dbe($db, $_POST['email'] ?? '');
    $p  = is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : null;
    $pS = $p === null ? 'NULL' : $p;
    dbq($db, "UPDATE classifieds SET title='$t',description='$d',location='$l',phone='$ph',email='$em',price=$pS,updated_at=NOW() WHERE id=$id AND user_id=$userId");
    echo json_encode($db->affected_rows > 0
        ? ['success' => true, 'message' => 'Inzerát bol aktualizovaný.']
        : ['success' => false, 'error' => 'Inzerát sa nenašiel alebo nie sú zmeny.']);
    break;

case 'boost_listing':
    $id = (int)($_POST['id'] ?? 0);
    $boostKey = trim((string)($_POST['boost_key'] ?? ''));
    // Spätná kompatibilita so starým 'days' parametrom
    if ($boostKey === '') {
        $days = (int)($_POST['days'] ?? 1);
        $boostKey = $days === 7 ? 'week' : 'single_tap';
    }
    if (!$id) { echo json_encode(['success' => false, 'error' => 'Chýba ID.']); break; }

    // key => [stĺpec, počet dní (0 = jednorazovo/NOW), cena, popis]
    $BOOST_TIERS = [
        'single_tap' => ['boosted_until',        0, 0.25, 'Jednorazové tapnutie'],
        'week'       => ['boosted_until',        7, 1.20, '7-dňové topovanie'],
        'morning'    => ['morning_expires_at',   7, 0.90, 'Ranné vtáča balíček'],
        'vip_glow'   => ['vip_glow_expires_at',  7, 0.60, 'Neon Glow rámček'],
        'vip_badge'  => ['vip_badge_expires_at', 7, 0.40, 'Štítok "Rýchly predaj"'],
    ];
    if (!isset($BOOST_TIERS[$boostKey])) { echo json_encode(['success' => false, 'error' => 'Neplatný balík.']); break; }
    [$col, $boostDays, $price, $label] = $BOOST_TIERS[$boostKey];

    if (!dbr($db, "SELECT id FROM classifieds WHERE id=$id AND user_id=$userId AND is_active=1")) {
        echo json_encode(['success' => false, 'error' => 'Inzerát sa nenašiel.']); break;
    }
    $wr = dbr($db, "SELECT credit, credit_earned FROM users WHERE id=$userId LIMIT 1");
    $w  = $wr ? (float)$wr['credit'] : 0;
    if ($w < $price) {
        echo json_encode(['success' => false, 'error' => 'Nedostatok kreditu. Potrebujete ' . number_format($price,2,',','') . ' €.', 'need_credit' => true]); break;
    }
    // Strhneme kredit - prioritne z nazbieraného (zdieľanie/odmeny), zvyšok z reálne dobitého
    $creditEarned = $wr ? (float)$wr['credit_earned'] : 0;
    $deductEarned = min($price, $creditEarned);
    $deductPurchased = round($price - $deductEarned, 2);
    dbq($db, "UPDATE users SET credit=GREATEST(0,credit-$price), credit_earned=GREATEST(0,credit_earned-$deductEarned), credit_purchased=GREATEST(0,credit_purchased-$deductPurchased) WHERE id=$userId");
    if ($boostDays > 0) {
        dbq($db, "UPDATE classifieds SET `$col`=DATE_ADD(GREATEST(COALESCE(`$col`,NOW()),NOW()), INTERVAL $boostDays DAY) WHERE id=$id");
    } else {
        dbq($db, "UPDATE classifieds SET `$col`=DATE_ADD(GREATEST(COALESCE(`$col`,NOW()),NOW()), INTERVAL 1 DAY) WHERE id=$id");
    }
    $negPrice = -$price;
    dbq($db, "INSERT INTO wallet_transactions (user_id, type, amount, currency, status, description) VALUES ($userId, 'boost_$boostKey', $negPrice, 'EUR', 'completed', 'Zviditeľnenie inzerátu: $label')");
    echo json_encode(['success' => true, 'message' => "Aktivované: $label!", 'wallet' => round($w-$price,2)]);
    break;

default:
    echo json_encode(['success' => false, 'error' => 'Neznáma akcia: ' . htmlspecialchars($action)]);
}
$db->close();
