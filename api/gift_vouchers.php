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

$conn->query("CREATE TABLE IF NOT EXISTS gift_vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    initial_value DECIMAL(8,2) NOT NULL DEFAULT 0,
    remaining_value DECIMAL(8,2) NOT NULL DEFAULT 0,
    buyer_customer_id INT DEFAULT NULL,
    buyer_name VARCHAR(150) DEFAULT NULL,
    buyer_email VARCHAR(150) DEFAULT NULL,
    recipient_name VARCHAR(150) DEFAULT NULL,
    message VARCHAR(500) DEFAULT NULL,
    status ENUM('pending_payment','active','redeemed','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
    valid_until DATE DEFAULT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_code (code),
    INDEX idx_est (establishment_id),
    INDEX idx_buyer (buyer_customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS gift_voucher_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gift_voucher_id INT NOT NULL,
    booking_id INT NOT NULL,
    amount_used DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gv (gift_voucher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function getBusinessEstablishmentId($conn, $userId) {
    $stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['id'] : 0;
}

function generateGiftVoucherCode($conn) {
    do {
        $code = 'GV-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $exists = $conn->query("SELECT id FROM gift_vouchers WHERE code = '" . $conn->real_escape_string($code) . "'")->num_rows > 0;
    } while ($exists);
    return $code;
}

// ── VEREJNÉ / ZÁKAZNÍCKE AKCIE ──────────────────────────────────────────

if ($action === 'purchase_gift_voucher') {
    $establishmentId = (int)($_POST['establishment_id'] ?? 0);
    $amount = round((float)($_POST['amount'] ?? 0), 2);
    $buyerName = esc($conn, $_POST['buyer_name'] ?? '');
    $buyerEmail = esc($conn, $_POST['buyer_email'] ?? '');
    $recipientName = esc($conn, $_POST['recipient_name'] ?? '');
    $message = esc($conn, $_POST['message'] ?? '');

    if ($amount < 5) { echo json_encode(['success' => false, 'message' => 'Minimálna hodnota poukazu je 5 €.']); exit; }
    if ($amount > 1000) { echo json_encode(['success' => false, 'message' => 'Maximálna hodnota poukazu je 1000 €.']); exit; }
    if (!$buyerName || !$buyerEmail) { echo json_encode(['success' => false, 'message' => 'Vyplňte meno a e-mail.']); exit; }

    $est_stmt = $conn->prepare("SELECT name, deposit_iban FROM establishments WHERE id = ?");
    $est_stmt->bind_param("i", $establishmentId);
    $est_stmt->execute();
    $est = $est_stmt->get_result()->fetch_assoc();
    if (!$est || empty($est['deposit_iban'])) {
        echo json_encode(['success' => false, 'message' => 'Prevádzka zatiaľ nemá nastavený spôsob platby za darčekové poukazy.']); exit;
    }

    $code = generateGiftVoucherCode($conn);
    $ins = $conn->prepare("INSERT INTO gift_vouchers (establishment_id, code, initial_value, remaining_value, buyer_customer_id, buyer_name, buyer_email, recipient_name, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment')");
    $ins->bind_param("isddissss", $establishmentId, $code, $amount, $amount, $userId, $buyerName, $buyerEmail, $recipientName, $message);
    $ins->execute();
    $giftVoucherId = $ins->insert_id;

    // Platobný QR — rovnaký princíp ako pri permanentkách a zálohách: peniaze idú priamo prevádzke
    $iban = str_replace(' ', '', strtoupper($est['deposit_iban']));
    $name = trim(substr(preg_replace('/[^A-Za-z0-9\s]/', '', $est['name']), 0, 70)) ?: 'Prevadzka';
    $amount_str = number_format($amount, 2, '.', '');
    $iban_country = substr($iban, 0, 2);
    $qr_png_base64 = '';
    if ($iban_country === 'SK') {
        try {
            $variable_symbol = substr((string)$giftVoucherId, 0, 10);
            $pbsq = generate_pay_by_square_payload($iban, $amount, $name, 'Darcekovy poukaz', $variable_symbol);
            $qr_png_base64 = base64_encode(generate_qr_png_bytes($pbsq));
        } catch (Exception $e) { $qr_png_base64 = ''; }
    } elseif ($iban_country === 'CZ') {
        $pbsq = "SPD*1.0*ACC:{$iban}*AM:{$amount_str}*CC:EUR*MSG:Darcekovy poukaz*";
        $qr_png_base64 = base64_encode(generate_qr_png_bytes($pbsq));
    } else {
        $sepa_str = "BCD\n002\n1\nSCT\n\n{$name}\n{$iban}\nEUR{$amount_str}\n\n\nDarcekovy poukaz\n";
        $qr_png_base64 = base64_encode(generate_qr_png_bytes($sepa_str));
    }

    echo json_encode([
        'success' => true,
        'gift_voucher_id' => $giftVoucherId,
        'code' => $code,
        'qr_code_png_base64' => $qr_png_base64,
        'message' => 'Naskenujte QR kód vo vašej bankovej aplikácii. Kód poukazu bude platný na použitie, keď prevádzka potvrdí prijatie platby.'
    ]);
    exit;
}

elseif ($action === 'check_gift_voucher') {
    // Overenie kódu pri uplatnení v rezervácii (bez odpočítania) — volá sa z profil.php pri zadaní kódu
    $establishmentId = (int)($_POST['establishment_id'] ?? $_GET['establishment_id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? $_GET['code'] ?? ''));
    if (!$code) { echo json_encode(['success' => false, 'message' => 'Zadajte kód poukazu.']); exit; }

    $gv_stmt = $conn->prepare("SELECT * FROM gift_vouchers WHERE code = ? AND establishment_id = ?");
    $gv_stmt->bind_param("si", $code, $establishmentId);
    $gv_stmt->execute();
    $gv = $gv_stmt->get_result()->fetch_assoc();

    if (!$gv) { echo json_encode(['success' => false, 'message' => 'Poukaz s týmto kódom sa v tejto prevádzke nenašiel.']); exit; }
    if ($gv['status'] === 'pending_payment') { echo json_encode(['success' => false, 'message' => 'Tento poukaz ešte čaká na potvrdenie platby prevádzkou.']); exit; }
    if ($gv['status'] !== 'active') { echo json_encode(['success' => false, 'message' => 'Tento poukaz už bol vyčerpaný alebo je neplatný.']); exit; }
    if ($gv['valid_until'] && strtotime($gv['valid_until']) < strtotime('today')) { echo json_encode(['success' => false, 'message' => 'Platnosť tohto poukazu už vypršala.']); exit; }
    if ((float)$gv['remaining_value'] <= 0) { echo json_encode(['success' => false, 'message' => 'Tento poukaz je už vyčerpaný.']); exit; }

    echo json_encode(['success' => true, 'remaining_value' => (float)$gv['remaining_value']]);
    exit;
}

elseif ($action === 'list_my_gift_vouchers') {
    $res = $conn->query("SELECT gv.*, e.name as establishment_name
                          FROM gift_vouchers gv JOIN establishments e ON e.id = gv.establishment_id
                          WHERE gv.buyer_customer_id = $userId
                          ORDER BY gv.purchased_at DESC");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'gift_vouchers' => $rows]);
    exit;
}

// ── FIREMNÉ AKCIE (vyžadujú business rolu) ──────────────────────────────

elseif (in_array($action, ['list_purchases', 'confirm_payment', 'get_sales_overview'])) {
    if ($userRole !== 'business') { echo json_encode(['success' => false, 'message' => 'Prístup zamietnutý.']); exit; }
    $establishmentId = getBusinessEstablishmentId($conn, $userId);
    if (!$establishmentId) { echo json_encode(['success' => false, 'message' => 'Prevádzka sa nenašla.']); exit; }

    if ($action === 'list_purchases') {
        $res = $conn->query("SELECT * FROM gift_vouchers WHERE establishment_id = $establishmentId
                              ORDER BY (status = 'pending_payment') DESC, purchased_at DESC LIMIT 200");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success' => true, 'purchases' => $rows]);
    }

    elseif ($action === 'confirm_payment') {
        $id = (int)($_POST['id'] ?? 0);
        $gv_stmt = $conn->prepare("SELECT * FROM gift_vouchers WHERE id = ? AND establishment_id = ? AND status = 'pending_payment'");
        $gv_stmt->bind_param("ii", $id, $establishmentId);
        $gv_stmt->execute();
        $gv = $gv_stmt->get_result()->fetch_assoc();
        if (!$gv) { echo json_encode(['success' => false, 'message' => 'Nájdená nebola žiadna čakajúca platba.']); exit; }

        $validUntil = date('Y-m-d', strtotime('+1 year'));
        $upd = $conn->prepare("UPDATE gift_vouchers SET status = 'active', valid_until = ?, activated_at = NOW() WHERE id = ?");
        $upd->bind_param("si", $validUntil, $id);
        $upd->execute();
        echo json_encode(['success' => true, 'message' => 'Platba potvrdená, poukaz je aktívny a pripravený na použitie.']);
    }

    elseif ($action === 'get_sales_overview') {
        $active = $conn->query("SELECT COUNT(*) as c FROM gift_vouchers WHERE establishment_id=$establishmentId AND status='active'")->fetch_assoc()['c'];
        $pending = $conn->query("SELECT COUNT(*) as c FROM gift_vouchers WHERE establishment_id=$establishmentId AND status='pending_payment'")->fetch_assoc()['c'];
        $revenue = $conn->query("SELECT COALESCE(SUM(initial_value),0) as s FROM gift_vouchers WHERE establishment_id=$establishmentId AND status IN ('active','redeemed') AND MONTH(activated_at)=MONTH(CURDATE()) AND YEAR(activated_at)=YEAR(CURDATE())")->fetch_assoc()['s'];
        $outstanding = $conn->query("SELECT COALESCE(SUM(remaining_value),0) as s FROM gift_vouchers WHERE establishment_id=$establishmentId AND status='active'")->fetch_assoc()['s'];
        echo json_encode(['success' => true, 'active' => (int)$active, 'pending' => (int)$pending, 'revenue_this_month' => (float)$revenue, 'outstanding_balance' => (float)$outstanding]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
