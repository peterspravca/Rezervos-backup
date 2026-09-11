<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/qr_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'require_auth' => true, 'message' => 'Musíte byť prihlásený.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'customer';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function esc($conn, $v) { return $conn->real_escape_string(trim($v ?? '')); }

$conn->query("CREATE TABLE IF NOT EXISTS membership_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('package','membership') NOT NULL DEFAULT 'package',
    price DECIMAL(8,2) NOT NULL DEFAULT 0,
    visit_count INT NOT NULL DEFAULT 1,
    validity_days INT NOT NULL DEFAULT 180,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_est (establishment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS customer_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    customer_id INT NOT NULL,
    establishment_id INT NOT NULL,
    status ENUM('pending_payment','active','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
    visits_total INT NOT NULL DEFAULT 1,
    visits_used INT NOT NULL DEFAULT 0,
    valid_until DATE DEFAULT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_est (establishment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function getBusinessEstablishmentId($conn, $userId) {
    $stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['id'] : 0;
}

// ── VEREJNÉ / ZÁKAZNÍCKE AKCIE ──────────────────────────────────────────

if ($action === 'list_available_packages') {
    $establishmentId = (int)($_POST['establishment_id'] ?? $_GET['establishment_id'] ?? 0);
    if (!$establishmentId) { echo json_encode(['success' => false, 'message' => 'Chýba prevádzka.']); exit; }
    $res = $conn->query("SELECT id, name, type, price, visit_count, validity_days, description FROM membership_packages
                          WHERE establishment_id = $establishmentId AND is_active = 1 ORDER BY price ASC");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'packages' => $rows]);
    exit;
}

elseif ($action === 'purchase_package') {
    $packageId = (int)($_POST['package_id'] ?? 0);
    $pkg_stmt = $conn->prepare("SELECT * FROM membership_packages WHERE id = ? AND is_active = 1");
    $pkg_stmt->bind_param("i", $packageId);
    $pkg_stmt->execute();
    $pkg = $pkg_stmt->get_result()->fetch_assoc();
    if (!$pkg) { echo json_encode(['success' => false, 'message' => 'Balíček sa nenašiel.']); exit; }

    $est_stmt = $conn->prepare("SELECT name, deposit_iban FROM establishments WHERE id = ?");
    $est_stmt->bind_param("i", $pkg['establishment_id']);
    $est_stmt->execute();
    $est = $est_stmt->get_result()->fetch_assoc();
    if (empty($est['deposit_iban'])) {
        echo json_encode(['success' => false, 'message' => 'Prevádzka zatiaľ nemá nastavený spôsob platby za permanentky.']); exit;
    }

    $ins = $conn->prepare("INSERT INTO customer_memberships (package_id, customer_id, establishment_id, status, visits_total) VALUES (?, ?, ?, 'pending_payment', ?)");
    $ins->bind_param("iiii", $packageId, $userId, $pkg['establishment_id'], $pkg['visit_count']);
    $ins->execute();
    $purchaseId = $ins->insert_id;

    // Platobný QR — rovnaký princíp ako záloha pri rezervácii: peniaze idú priamo prevádzke, Rezervos len eviduje stav
    $iban = str_replace(' ', '', strtoupper($est['deposit_iban']));
    $name = trim(substr(preg_replace('/[^A-Za-z0-9\s]/', '', $est['name']), 0, 70)) ?: 'Prevadzka';
    $amount_str = number_format((float)$pkg['price'], 2, '.', '');
    $iban_country = substr($iban, 0, 2);
    $qr_png_base64 = '';
    if ($iban_country === 'SK') {
        try {
            $variable_symbol = substr((string)$purchaseId, 0, 10);
            $pbsq = generate_pay_by_square_payload($iban, (float)$pkg['price'], $name, 'Permanentka ' . $pkg['name'], $variable_symbol);
            $qr_png_base64 = base64_encode(generate_qr_png_bytes($pbsq));
        } catch (Exception $e) { $qr_png_base64 = ''; }
    } elseif ($iban_country === 'CZ') {
        $pbsq = "SPD*1.0*ACC:{$iban}*AM:{$amount_str}*CC:EUR*MSG:Permanentka*";
        $qr_png_base64 = base64_encode(generate_qr_png_bytes($pbsq));
    } else {
        $sepa_str = "BCD\n002\n1\nSCT\n\n{$name}\n{$iban}\nEUR{$amount_str}\n\n\nPermanentka\n";
        $qr_png_base64 = base64_encode(generate_qr_png_bytes($sepa_str));
    }

    echo json_encode([
        'success' => true,
        'purchase_id' => $purchaseId,
        'qr_code_png_base64' => $qr_png_base64,
        'message' => 'Naskenujte QR kód vo vašej bankovej aplikácii. Permanentka sa aktivuje, keď prevádzka potvrdí prijatie platby.'
    ]);
    exit;
}

elseif ($action === 'list_my_memberships') {
    $res = $conn->query("SELECT cm.*, mp.name as package_name, mp.type, mp.price, e.name as establishment_name
                          FROM customer_memberships cm
                          JOIN membership_packages mp ON mp.id = cm.package_id
                          JOIN establishments e ON e.id = cm.establishment_id
                          WHERE cm.customer_id = $userId
                          ORDER BY cm.purchased_at DESC");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'memberships' => $rows]);
    exit;
}

// ── FIREMNÉ AKCIE (vyžadujú business rolu) ──────────────────────────────

elseif (in_array($action, ['create_package', 'toggle_package', 'delete_package', 'list_packages', 'list_purchases', 'confirm_payment', 'log_visit', 'get_sales_overview'])) {
    if ($userRole !== 'business') { echo json_encode(['success' => false, 'message' => 'Prístup zamietnutý.']); exit; }
    $establishmentId = getBusinessEstablishmentId($conn, $userId);
    if (!$establishmentId) { echo json_encode(['success' => false, 'message' => 'Prevádzka sa nenašla.']); exit; }

    if ($action === 'list_packages') {
        $res = $conn->query("SELECT * FROM membership_packages WHERE establishment_id = $establishmentId ORDER BY created_at DESC");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success' => true, 'packages' => $rows]);
    }

    elseif ($action === 'create_package') {
        $name = esc($conn, $_POST['name'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['package', 'membership']) ? $_POST['type'] : 'package';
        $price = max(0, (float)($_POST['price'] ?? 0));
        $visitCount = max(1, (int)($_POST['visit_count'] ?? 1));
        $validityDays = max(1, (int)($_POST['validity_days'] ?? 180));
        $description = esc($conn, $_POST['description'] ?? '');
        if (!$name) { echo json_encode(['success' => false, 'message' => 'Zadajte názov balíčka.']); exit; }

        $ins = $conn->prepare("INSERT INTO membership_packages (establishment_id, name, type, price, visit_count, validity_days, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("issdiis", $establishmentId, $name, $type, $price, $visitCount, $validityDays, $description);
        if ($ins->execute()) {
            echo json_encode(['success' => true, 'message' => 'Balíček bol vytvorený.', 'id' => $ins->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chyba DB: ' . $conn->error]);
        }
    }

    elseif ($action === 'toggle_package') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $conn->query("SELECT is_active FROM membership_packages WHERE id=$id AND establishment_id=$establishmentId")->fetch_assoc();
        if (!$row) { echo json_encode(['success' => false, 'message' => 'Balíček sa nenašiel.']); exit; }
        $newState = $row['is_active'] ? 0 : 1;
        $conn->query("UPDATE membership_packages SET is_active=$newState WHERE id=$id");
        echo json_encode(['success' => true, 'is_active' => $newState]);
    }

    elseif ($action === 'delete_package') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM membership_packages WHERE id=$id AND establishment_id=$establishmentId");
        echo json_encode(['success' => true, 'message' => 'Balíček bol odstránený.']);
    }

    elseif ($action === 'list_purchases') {
        $res = $conn->query("SELECT cm.*, mp.name as package_name, mp.type, mp.price, u.full_name as customer_name, u.email as customer_email
                              FROM customer_memberships cm
                              JOIN membership_packages mp ON mp.id = cm.package_id
                              JOIN users u ON u.id = cm.customer_id
                              WHERE cm.establishment_id = $establishmentId
                              ORDER BY (cm.status = 'pending_payment') DESC, cm.purchased_at DESC
                              LIMIT 200");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success' => true, 'purchases' => $rows]);
    }

    elseif ($action === 'confirm_payment') {
        $id = (int)($_POST['id'] ?? 0);
        $cm_stmt = $conn->prepare("SELECT cm.*, mp.validity_days FROM customer_memberships cm JOIN membership_packages mp ON mp.id = cm.package_id WHERE cm.id = ? AND cm.establishment_id = ? AND cm.status = 'pending_payment'");
        $cm_stmt->bind_param("ii", $id, $establishmentId);
        $cm_stmt->execute();
        $cm = $cm_stmt->get_result()->fetch_assoc();
        if (!$cm) { echo json_encode(['success' => false, 'message' => 'Nájdená nebola žiadna čakajúca platba.']); exit; }

        $validUntil = date('Y-m-d', strtotime('+' . (int)$cm['validity_days'] . ' days'));
        $upd = $conn->prepare("UPDATE customer_memberships SET status = 'active', valid_until = ?, activated_at = NOW() WHERE id = ?");
        $upd->bind_param("si", $validUntil, $id);
        $upd->execute();
        echo json_encode(['success' => true, 'message' => 'Platba potvrdená, permanentka je aktívna.']);
    }

    elseif ($action === 'log_visit') {
        $id = (int)($_POST['id'] ?? 0);
        $cm_stmt = $conn->prepare("SELECT * FROM customer_memberships WHERE id = ? AND establishment_id = ? AND status = 'active'");
        $cm_stmt->bind_param("ii", $id, $establishmentId);
        $cm_stmt->execute();
        $cm = $cm_stmt->get_result()->fetch_assoc();
        if (!$cm) { echo json_encode(['success' => false, 'message' => 'Aktívna permanentka sa nenašla.']); exit; }
        if ((int)$cm['visits_used'] >= (int)$cm['visits_total']) { echo json_encode(['success' => false, 'message' => 'Permanentka je už vyčerpaná.']); exit; }

        $newUsed = (int)$cm['visits_used'] + 1;
        $newStatus = ($newUsed >= (int)$cm['visits_total']) ? 'expired' : 'active';
        $upd = $conn->prepare("UPDATE customer_memberships SET visits_used = ?, status = ? WHERE id = ?");
        $upd->bind_param("isi", $newUsed, $newStatus, $id);
        $upd->execute();
        echo json_encode(['success' => true, 'visits_used' => $newUsed, 'status' => $newStatus]);
    }

    elseif ($action === 'get_sales_overview') {
        $active = $conn->query("SELECT COUNT(*) as c FROM customer_memberships WHERE establishment_id=$establishmentId AND status='active'")->fetch_assoc()['c'];
        $pending = $conn->query("SELECT COUNT(*) as c FROM customer_memberships WHERE establishment_id=$establishmentId AND status='pending_payment'")->fetch_assoc()['c'];
        $revenue = $conn->query("SELECT COALESCE(SUM(mp.price),0) as s FROM customer_memberships cm JOIN membership_packages mp ON mp.id=cm.package_id
                                  WHERE cm.establishment_id=$establishmentId AND cm.status IN ('active','expired') AND MONTH(cm.activated_at)=MONTH(CURDATE()) AND YEAR(cm.activated_at)=YEAR(CURDATE())")->fetch_assoc()['s'];
        echo json_encode(['success' => true, 'active' => (int)$active, 'pending' => (int)$pending, 'revenue_this_month' => (float)$revenue]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
