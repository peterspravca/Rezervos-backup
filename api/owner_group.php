<?php
// api/owner_group.php
// Fáza 4: "Multi-prevádzka" — zjednodušená verzia. Nemení dátový model existujúcich prevádzok
// (každá prevádzka má naďalej svoj vlastný samostatný účet/dashboard presne ako doteraz),
// len umožňuje majiteľovi prepojiť viacero svojich účtov pod jednu skupinu a vidieť ich
// súhrnne v novej stránke dashboard-prevadzky.php, s rýchlym prepnutím medzi nimi.
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business' || !empty($_SESSION['is_employee'])) {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Self-migrácia: skupina prepojených účtov (NULL = samostatný účet, nezmenené správanie)
try { $pdo->exec("ALTER TABLE users ADD COLUMN multi_owner_root_id INT DEFAULT NULL"); } catch (Exception $e) {}

function getRootId($pdo, $uid) {
    $stmt = $pdo->prepare("SELECT multi_owner_root_id FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $root = $stmt->fetchColumn();
    return $root ? (int)$root : (int)$uid;
}

// Koľko prevádzok smie mať účet prepojených v skupine (1 = len vlastná, žiadna ďalšia): podľa balíka
// (FREE/ŠTART/PRO = 1, VIP = 2) plus počet zakúpených doplnkov "Ďalšia pobočka" (cennik.php/dashboard-rozsirenia.php).
function getAllowedBranchCount($pdo, $uid) {
    $stmt = $pdo->prepare("SELECT IFNULL(e.subscription_tier, IFNULL(u.subscription_tier, 'free')) AS tier
                            FROM users u LEFT JOIN establishments e ON u.id = e.user_id WHERE u.id = ? LIMIT 1");
    $stmt->execute([$uid]);
    $tier = strtoupper($stmt->fetchColumn() ?: 'FREE');

    if ($tier === 'ENTERPRISE') return PHP_INT_MAX;

    $included = ['FREE' => 1, 'START' => 1, 'PRO' => 1, 'VIP' => 2];
    $base = $included[$tier] ?? 1;

    $chk = $pdo->query("SHOW TABLES LIKE 'addon_orders'");
    $addonCount = 0;
    if ($chk && $chk->rowCount() > 0) {
        $a = $pdo->prepare("SELECT COUNT(*) FROM addon_orders WHERE user_id = ? AND addon_key = 'branch' AND status = 'active'");
        $a->execute([$uid]);
        $addonCount = (int)$a->fetchColumn();
    }

    return $base + $addonCount;
}

if ($action === 'get_group') {
    $root_id = getRootId($pdo, $user_id);
    $stmt = $pdo->prepare("
        SELECT e.id AS establishment_id, e.name, e.image_url, u.id AS owner_user_id, u.email
        FROM users u
        JOIN establishments e ON e.user_id = u.id
        WHERE u.id = ? OR u.multi_owner_root_id = ?
        ORDER BY (u.id = ?) DESC, e.name ASC
    ");
    $stmt->execute([$root_id, $root_id, $user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
    foreach ($rows as &$row) {
        $eid = (int)$row['establishment_id'];
        $s = $pdo->prepare("
            SELECT
                COUNT(CASE WHEN booking_date = ? AND status IN ('pending','confirmed','completed') THEN 1 END) AS bookings_today,
                COUNT(CASE WHEN booking_date >= ? AND status IN ('confirmed','completed') THEN 1 END) AS bookings_month,
                COALESCE(SUM(CASE WHEN booking_date >= ? AND status IN ('confirmed','completed') THEN price_at_booking END), 0) AS revenue_month
            FROM bookings WHERE establishment_id = ?
        ");
        $s->execute([$today, $monthStart, $monthStart, $eid]);
        $stats = $s->fetch(PDO::FETCH_ASSOC);
        $row['bookings_today'] = (int)$stats['bookings_today'];
        $row['bookings_month'] = (int)$stats['bookings_month'];
        $row['revenue_month'] = round((float)$stats['revenue_month'], 2);
        $row['is_current'] = ((int)$row['owner_user_id'] === $user_id);
    }
    unset($row);

    echo json_encode([
        'success' => true,
        'establishments' => $rows,
        'is_grouped' => count($rows) > 1,
        'allowed_branches' => getAllowedBranchCount($pdo, $user_id),
    ]);
}

elseif ($action === 'link_establishment') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Zadajte e-mail aj heslo druhého účtu.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target || !password_verify($password, $target['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Nesprávny e-mail alebo heslo.']);
        exit;
    }
    if ($target['role'] !== 'business') {
        echo json_encode(['success' => false, 'message' => 'Tento účet nie je firemný účet prevádzky.']);
        exit;
    }
    if ((int)$target['id'] === $user_id) {
        echo json_encode(['success' => false, 'message' => 'To je váš aktuálny účet.']);
        exit;
    }

    $stmtEst = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmtEst->execute([$target['id']]);
    if (!$stmtEst->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Tento účet zatiaľ nemá vytvorenú prevádzku.']);
        exit;
    }

    // Limit počtu prevádzok podľa balíka žiadajúceho účtu (+ zakúpené doplnky "Ďalšia pobočka")
    $root_id_check = getRootId($pdo, $user_id);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? OR multi_owner_root_id = ?");
    $countStmt->execute([$root_id_check, $root_id_check]);
    $currentCount = (int)$countStmt->fetchColumn();
    $allowed = getAllowedBranchCount($pdo, $user_id);
    if ($currentCount + 1 > $allowed) {
        echo json_encode([
            'success' => false,
            'message' => "Váš balík umožňuje $allowed prevádzku/prevádzky. Pre prepojenie ďalšej si dokúpte doplnok „Ďalšia pobočka” v sekcii Môj Balík, alebo prejdite na vyšší balík.",
            'limit_reached' => true
        ]);
        exit;
    }

    // Koreň skupiny: ak niektorý z dvoch účtov už je súčasťou skupiny, použijeme ten; inak založíme
    // novú skupinu s koreňom v aktuálnom (žiadajúcom) účte.
    $root_id = getRootId($pdo, $user_id);
    $target_root = getRootId($pdo, (int)$target['id']);

    // Ak cieľový účet je už súčasťou inej existujúcej skupiny (iný koreň, nie sám sebou), odmietneme —
    // aby sme nerozbili jeho existujúce prepojenie s iným majiteľom.
    $target_stmt = $pdo->prepare("SELECT multi_owner_root_id FROM users WHERE id = ?");
    $target_stmt->execute([$target['id']]);
    $target_existing_root = $target_stmt->fetchColumn();
    if ($target_existing_root && (int)$target_existing_root !== $root_id) {
        echo json_encode(['success' => false, 'message' => 'Tento účet je už prepojený s inou skupinou prevádzok.']);
        exit;
    }

    $pdo->prepare("UPDATE users SET multi_owner_root_id = ? WHERE id = ?")->execute([$root_id, $user_id]);
    $pdo->prepare("UPDATE users SET multi_owner_root_id = ? WHERE id = ?")->execute([$root_id, $target['id']]);

    echo json_encode(['success' => true, 'message' => 'Prevádzka bola úspešne prepojená.']);
}

elseif ($action === 'unlink_establishment') {
    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $root_id = getRootId($pdo, $user_id);

    // Smie odpojiť len účet z vlastnej skupiny, a nie sám seba (odpojenie seba by stránku uzamklo)
    if (!$target_user_id || $target_user_id === $user_id) {
        echo json_encode(['success' => false, 'message' => 'Neplatná požiadavka.']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT multi_owner_root_id FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $their_root = (int)$stmt->fetchColumn();
    if ($their_root !== $root_id) {
        echo json_encode(['success' => false, 'message' => 'Tento účet nie je súčasťou vašej skupiny.']);
        exit;
    }
    $pdo->prepare("UPDATE users SET multi_owner_root_id = NULL WHERE id = ?")->execute([$target_user_id]);
    echo json_encode(['success' => true, 'message' => 'Prevádzka bola odpojená zo skupiny.']);
}

elseif ($action === 'switch_to') {
    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $root_id = getRootId($pdo, $user_id);

    $stmt = $pdo->prepare("SELECT id, role, multi_owner_root_id FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    $their_root = $target ? (int)($target['multi_owner_root_id'] ?: $target['id']) : null;
    if (!$target || $target['role'] !== 'business' || $their_root !== $root_id) {
        echo json_encode(['success' => false, 'message' => 'Táto prevádzka nie je súčasťou vašej skupiny.']);
        exit;
    }

    $_SESSION['user_id'] = (int)$target['id'];
    $_SESSION['user_role'] = 'business';
    unset($_SESSION['is_employee'], $_SESSION['employee_id'], $_SESSION['employee_name'], $_SESSION['employee_title'],
          $_SESSION['emp_can_view_revenue'], $_SESSION['emp_can_view_crm'], $_SESSION['emp_can_edit_settings']);

    echo json_encode(['success' => true, 'message' => 'Prepnuté na vybranú prevádzku.']);
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
