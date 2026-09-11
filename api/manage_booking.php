<?php
require_once '../config.php';
require_once __DIR__ . '/../includes/availability_helper.php';
require_once __DIR__ . '/../includes/waitlist_helper.php';
require_once __DIR__ . '/../includes/cancellation_helper.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$token = trim($_POST['token'] ?? $_GET['token'] ?? '');

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Chýba token rezervácie.']);
    exit;
}

function fetchBookingByToken($conn, $token) {
    $stmt = $conn->prepare("SELECT b.*, s.name as service_name, s.duration_minutes, s.buffer_before_minutes as srv_buffer_before, s.buffer_after_minutes as srv_buffer_after,
                                    e.name as establishment_name, e.address, e.city
                             FROM bookings b
                             LEFT JOIN services s ON s.id = b.service_id
                             LEFT JOIN establishments e ON e.id = b.establishment_id
                             WHERE b.manage_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$booking = fetchBookingByToken($conn, $token);
if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Rezervácia sa nenašla — odkaz je neplatný alebo bol už použitý.']);
    exit;
}

if ($action === 'get_booking') {
    echo json_encode([
        'success' => true,
        'booking' => [
            'id' => (int)$booking['id'],
            'service_name' => $booking['service_name'] ?: 'Rezervovaná služba',
            'establishment_name' => $booking['establishment_name'],
            'location' => trim(($booking['address'] ?? '') . ', ' . ($booking['city'] ?? ''), ', '),
            'booking_date' => $booking['booking_date'],
            'start_time' => substr($booking['start_time'], 0, 5),
            'end_time' => substr($booking['end_time'], 0, 5),
            'status' => $booking['status'],
            'establishment_id' => (int)$booking['establishment_id'],
            'service_id' => (int)$booking['service_id'],
            'employee_id' => $booking['employee_id'] !== null ? (int)$booking['employee_id'] : 0,
            'can_manage' => !in_array($booking['status'], ['cancelled', 'completed'])
        ]
    ]);
    exit;
}

if (in_array($booking['status'], ['cancelled', 'completed'])) {
    echo json_encode(['success' => false, 'message' => 'Táto rezervácia sa už nedá upraviť (je zrušená alebo ukončená).']);
    exit;
}

if ($action === 'cancel_booking') {
    $root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
    if (requestCustomerCancellation($conn, $root_url, (int)$booking['id'], 'single')) {
        echo json_encode(['success' => true, 'message' => 'Na váš e-mail sme poslali potvrdzovací odkaz. Rezervácia bude zrušená až po jeho potvrdení.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Zrušenie sa nepodarilo, skúste to prosím znova.']);
    }
    exit;
}

elseif ($action === 'cancel_series') {
    if (empty($booking['recurring_series_id'])) {
        echo json_encode(['success' => false, 'message' => 'Táto rezervácia nie je súčasťou opakovanej série.']);
        exit;
    }
    $root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
    if (requestCustomerCancellation($conn, $root_url, (int)$booking['id'], 'series')) {
        echo json_encode(['success' => true, 'message' => 'Na váš e-mail sme poslali potvrdzovací odkaz. Táto aj všetky budúce rezervácie v sérii budú zrušené až po jeho potvrdení.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Zrušenie série sa nepodarilo, skúste to prosím znova.']);
    }
    exit;
}

elseif ($action === 'get_reschedule_slots') {
    $booking_date = trim($_POST['booking_date'] ?? $_GET['booking_date'] ?? '');
    if (empty($booking_date)) {
        echo json_encode(['success' => false, 'message' => 'Chýba dátum.']);
        exit;
    }
    // 0/"any" = Ktokoľvek voľný (napr. keď pôvodný pracovník nahlásil PN) — inak konkrétny zamestnanec z formulára, s pádom na pôvodného
    $employee_id_raw = $_POST['employee_id'] ?? $_GET['employee_id'] ?? null;
    $employee_id_filter = $employee_id_raw !== null ? (int)$employee_id_raw : ($booking['employee_id'] !== null ? (int)$booking['employee_id'] : 0);
    $error = null;
    $slots = computeAvailableSlots($conn, (int)$booking['establishment_id'], [(int)$booking['service_id']], $booking_date, $employee_id_filter, $error, (int)$booking['id']);
    if ($error) {
        echo json_encode(['success' => false, 'message' => $error]);
    } else {
        echo json_encode(['success' => true, 'slots' => $slots]);
    }
    exit;
}

elseif ($action === 'reschedule_booking') {
    $new_date = trim($_POST['booking_date'] ?? '');
    $new_time = trim($_POST['start_time'] ?? '');
    if (empty($new_date) || empty($new_time)) {
        echo json_encode(['success' => false, 'message' => 'Vyberte prosím nový dátum aj čas.']);
        exit;
    }

    $duration = (int)($booking['duration_minutes'] ?? 30);
    $buffer_before = (int)($booking['srv_buffer_before'] ?? 0);
    $buffer_after = (int)($booking['srv_buffer_after'] ?? 0);

    // 0/"any" = Ktokoľvek voľný (napr. keď pôvodný pracovník nahlásil PN) — inak konkrétny zamestnanec z formulára
    $employee_id_raw = $_POST['employee_id'] ?? null;
    $employee_id_filter = $employee_id_raw !== null ? (int)$employee_id_raw : ($booking['employee_id'] !== null ? (int)$booking['employee_id'] : 0);

    // Overenie, že nový termín je stále reálne voľný (rovnaká kontrola ako pri pôvodnej rezervácii)
    $error = null;
    $available_slots = computeAvailableSlots($conn, (int)$booking['establishment_id'], [(int)$booking['service_id']], $new_date, $employee_id_filter, $error, (int)$booking['id']);
    if ($error) {
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
    if (!in_array(substr($new_time, 0, 5), $available_slots)) {
        echo json_encode(['success' => false, 'message' => 'Tento termín už žiaľ nie je voľný. Vyberte prosím iný čas.']);
        exit;
    }

    $start_dt = new DateTime($new_date . ' ' . $new_time);
    $end_dt = (clone $start_dt)->modify('+' . $duration . ' minutes');

    // Zistenie, ktorý konkrétny zamestnanec je pri "Ktokoľvek" reálne voľný (aby sa rezervácia priradila správne)
    $new_employee_id = $employee_id_filter;
    if ($employee_id_filter <= 0) {
        $candidates = [];
        $emp_stmt = $conn->prepare("SELECT id FROM employees WHERE establishment_id = ? AND is_active = 1");
        $emp_stmt->bind_param("i", $booking['establishment_id']);
        $emp_stmt->execute();
        $emp_res = $emp_stmt->get_result();
        while ($row = $emp_res->fetch_assoc()) { $candidates[] = (int)$row['id']; }
        foreach ($candidates as $cand_id) {
            $cand_error = null;
            $cand_slots = computeAvailableSlots($conn, (int)$booking['establishment_id'], [(int)$booking['service_id']], $new_date, $cand_id, $cand_error, (int)$booking['id']);
            if (!$cand_error && in_array(substr($new_time, 0, 5), $cand_slots)) { $new_employee_id = $cand_id; break; }
        }
        if ($new_employee_id <= 0) { $new_employee_id = null; } // nepodarilo sa určiť konkrétneho — ostane bez priradenia
    }

    $upd = $conn->prepare("UPDATE bookings SET booking_date = ?, start_time = ?, end_time = ?, employee_id = ? WHERE id = ?");
    $start_sql = $start_dt->format('H:i:s');
    $end_sql = $end_dt->format('H:i:s');
    $upd->bind_param("sssii", $new_date, $start_sql, $end_sql, $new_employee_id, $booking['id']);
    if ($upd->execute()) {
        echo json_encode(['success' => true, 'message' => 'Termín bol úspešne presunutý.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Presun sa nepodaril, skúste to prosím znova.']);
    }
    exit;
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
