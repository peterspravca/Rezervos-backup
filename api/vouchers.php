<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Nie ste prihlásený.']); exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Validácia kódu pri rezervácii musí vedieť zavolať aj zákazník (nie len firma) — preto je mimo kontroly nižšie.
if ($action !== 'validate' && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'business')) {
    echo json_encode(['success'=>false,'error'=>'Prístup zamietnutý.']); exit;
}

$userId     = (int)$_SESSION['user_id'];

// $_SESSION['business_id'] is never set anywhere in this codebase, so the old
// "?? $userId" fallback silently stored/looked up vouchers under the logged-in
// user's own users.id instead of their establishments.id — the same id that
// checkout's 'validate' action and api/book_appointment.php check against.
// Resolve the real establishment id here so list/create/toggle/delete operate
// on the same id the voucher is actually redeemed against.
$businessId = $userId;
if (($_SESSION['user_role'] ?? '') === 'business') {
    $est_stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt->bind_param("i", $userId);
    $est_stmt->execute();
    $est_row = $est_stmt->get_result()->fetch_assoc();
    $businessId = $est_row ? (int)$est_row['id'] : 0;
}

function esc($conn, $v) { return $conn->real_escape_string(trim($v??'')); }

// Self-migrácia: tabuľka voucherov ešte v DB neexistovala vôbec, čo spôsobovalo 500 chybu pri každom volaní
$conn->query("CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(8,2) NOT NULL DEFAULT 0,
    valid_until DATE DEFAULT NULL,
    max_uses INT DEFAULT NULL,
    uses_count INT NOT NULL DEFAULT 0,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_business (business_id),
    UNIQUE KEY uniq_business_code (business_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

switch ($action) {

    // ------------------------------------------------------------------
    // LIST VOUCHERS
    // ------------------------------------------------------------------
    case 'list':
        $sql = "SELECT * FROM vouchers WHERE business_id = $businessId ORDER BY created_at DESC";
        $res = $conn->query($sql);
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success'=>true,'vouchers'=>$rows]);
        break;

    // ------------------------------------------------------------------
    // CREATE VOUCHER
    // ------------------------------------------------------------------
    case 'create':
        $code          = strtoupper(esc($conn, $_POST['code']??''));
        $discountType  = esc($conn, $_POST['discount_type']??'percent');
        $discountValue = is_numeric($_POST['discount_value']??'') ? (float)$_POST['discount_value'] : 0;
        $validUntil    = esc($conn, $_POST['valid_until']??'');
        $maxUses       = is_numeric($_POST['max_uses']??'') ? (int)$_POST['max_uses'] : null;
        $description   = esc($conn, $_POST['description']??'');

        if (!$code) { echo json_encode(['success'=>false,'error'=>'Kód voucheru nesmie byť prázdny.']); exit; }
        if (!in_array($discountType, ['percent','fixed'])) { echo json_encode(['success'=>false,'error'=>'Neplatný typ zľavy.']); exit; }
        if ($discountValue <= 0) { echo json_encode(['success'=>false,'error'=>'Hodnota zľavy musí byť kladná.']); exit; }

        $validSql    = $validUntil ? "'$validUntil'" : 'NULL';
        $maxUsesSql  = $maxUses !== null ? $maxUses : 'NULL';

        // Check uniqueness for this business
        $check = $conn->query("SELECT id FROM vouchers WHERE business_id=$businessId AND code='$code' LIMIT 1");
        if ($check->num_rows > 0) {
            echo json_encode(['success'=>false,'error'=>'Voucher s týmto kódom už existuje.']); exit;
        }

        $sql = "INSERT INTO vouchers (business_id, code, discount_type, discount_value, valid_until, max_uses, description, is_active)
                VALUES ($businessId, '$code', '$discountType', $discountValue, $validSql, $maxUsesSql, '$description', 1)";
        if ($conn->query($sql)) {
            echo json_encode(['success'=>true,'id'=>$conn->insert_id,'message'=>"Voucher $code bol vytvorený."]);
        } else {
            echo json_encode(['success'=>false,'error'=>'Chyba DB: '.$conn->error]);
        }
        break;

    // ------------------------------------------------------------------
    // TOGGLE VOUCHER (activate / deactivate)
    // ------------------------------------------------------------------
    case 'toggle':
        $id = (int)($_POST['id']??0);
        if (!$id) { echo json_encode(['success'=>false,'error'=>'Chýba ID.']); exit; }
        $row = $conn->query("SELECT id, is_active FROM vouchers WHERE id=$id AND business_id=$businessId LIMIT 1")->fetch_assoc();
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Voucher sa nenašiel.']); exit; }
        $newState = $row['is_active'] ? 0 : 1;
        $conn->query("UPDATE vouchers SET is_active=$newState WHERE id=$id AND business_id=$businessId");
        echo json_encode(['success'=>true,'is_active'=>$newState,'message'=>$newState ? 'Voucher bol aktivovaný.' : 'Voucher bol deaktivovaný.']);
        break;

    // ------------------------------------------------------------------
    // DELETE VOUCHER
    // ------------------------------------------------------------------
    case 'delete':
        $id = (int)($_POST['id']??0);
        if (!$id) { echo json_encode(['success'=>false,'error'=>'Chýba ID.']); exit; }
        $conn->query("DELETE FROM vouchers WHERE id=$id AND business_id=$businessId");
        if ($conn->affected_rows > 0) {
            echo json_encode(['success'=>true,'message'=>'Voucher bol odstránený.']);
        } else {
            echo json_encode(['success'=>false,'error'=>'Voucher sa nenašiel.']);
        }
        break;

    // ------------------------------------------------------------------
    // VALIDATE VOUCHER (for use at booking — public-ish)
    // ------------------------------------------------------------------
    case 'validate':
        $code       = strtoupper(esc($conn, $_POST['code']??''));
        $businessParam = (int)($_POST['business_id']??$businessId);
        if (!$code) { echo json_encode(['success'=>false,'error'=>'Kód voucheru chýba.']); exit; }
        $sql = "SELECT * FROM vouchers WHERE code='$code' AND business_id=$businessParam AND is_active=1 LIMIT 1";
        $row = $conn->query($sql)->fetch_assoc();
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Voucher nie je platný.']); exit; }
        // Check validity date
        if ($row['valid_until'] && strtotime($row['valid_until']) < strtotime('today')) {
            echo json_encode(['success'=>false,'error'=>'Voucher vypršal.']); exit;
        }
        // Check max uses
        if ($row['max_uses'] !== null && $row['uses_count'] >= $row['max_uses']) {
            echo json_encode(['success'=>false,'error'=>'Voucher bol vyčerpaný (dosiahnutý maximálny počet použití).']); exit;
        }
        echo json_encode(['success'=>true,'voucher'=>['code'=>$row['code'],'discount_type'=>$row['discount_type'],'discount_value'=>$row['discount_value']]]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Neznáma akcia.']);
}
