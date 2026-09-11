<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/waitlist_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'require_auth' => true, 'message' => 'Na pridanie na čakaciu listinu sa musíte prihlásiť.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

ensureWaitlistTable($conn);

if ($action === 'join_waitlist') {
    $establishment_id = (int)($_POST['establishment_id'] ?? 0);
    $preferred_date = trim($_POST['preferred_date'] ?? '');
    $service_ids_raw = $_POST['service_ids'] ?? '[]';
    $service_ids = is_string($service_ids_raw) ? (json_decode($service_ids_raw, true) ?: []) : (array)$service_ids_raw;

    if ($establishment_id <= 0 || empty($preferred_date) || empty($service_ids)) {
        echo json_encode(['success' => false, 'message' => 'Chýbajú povinné údaje.']);
        exit;
    }

    // Nepridávať duplicitne, ak už na tento deň čaká
    $check = $conn->prepare("SELECT id FROM waitlist WHERE customer_id = ? AND establishment_id = ? AND preferred_date = ? AND status = 'waiting'");
    $check->bind_param("iis", $user_id, $establishment_id, $preferred_date);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Už ste na čakacej listine pre tento deň.']);
        exit;
    }

    $service_ids_json = json_encode(array_map('intval', $service_ids));
    $ins = $conn->prepare("INSERT INTO waitlist (customer_id, establishment_id, service_ids, preferred_date) VALUES (?, ?, ?, ?)");
    $ins->bind_param("iiss", $user_id, $establishment_id, $service_ids_json, $preferred_date);
    if ($ins->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pridali sme vás na čakaciu listinu. Ak sa uvoľní termín, pošleme vám e-mail.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nepodarilo sa pridať na čakaciu listinu, skúste to prosím znova.']);
    }
    exit;
}

elseif ($action === 'leave_waitlist') {
    $id = (int)($_POST['id'] ?? 0);
    $upd = $conn->prepare("UPDATE waitlist SET status = 'cancelled' WHERE id = ? AND customer_id = ?");
    $upd->bind_param("ii", $id, $user_id);
    $upd->execute();
    echo json_encode(['success' => true, 'message' => 'Odhlásili sme vás z čakacej listiny.']);
    exit;
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
