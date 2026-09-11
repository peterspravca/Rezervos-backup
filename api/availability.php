<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Self-migrácia: rezervácia si potrebuje pamätať, ktorý zamestnanec ju robí a aký buffer bol v čase rezervácie použitý
// (buffer sa ukladá priamo k rezervácii, nie len k službe, aby sa dodatočná zmena buffra v cenníku
// spätne nepokazila kontrolu kolízie pri už existujúcich rezerváciách)
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS employee_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS buffer_before_minutes INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS buffer_after_minutes INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}

require_once __DIR__ . '/../includes/availability_helper.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_available_slots') {
    $establishment_id = (int)($_POST['establishment_id'] ?? $_GET['establishment_id'] ?? 0);
    $booking_date = trim($_POST['booking_date'] ?? $_GET['booking_date'] ?? '');
    $employee_id_filter = (int)($_POST['employee_id'] ?? $_GET['employee_id'] ?? 0);
    $service_ids_raw = $_POST['service_ids'] ?? $_GET['service_ids'] ?? '[]';
    $service_ids = is_string($service_ids_raw) ? (json_decode($service_ids_raw, true) ?: []) : (array)$service_ids_raw;

    if ($establishment_id <= 0 || empty($booking_date) || empty($service_ids)) {
        echo json_encode(['success' => false, 'message' => 'Chýbajú povinné parametre.']);
        exit;
    }

    $error = null;
    $slots = computeAvailableSlots($conn, $establishment_id, $service_ids, $booking_date, $employee_id_filter, $error);
    if ($error) {
        echo json_encode(['success' => false, 'message' => $error]);
    } else {
        echo json_encode(['success' => true, 'slots' => $slots]);
    }
    exit;
}

elseif ($action === 'get_employees_for_services') {
    $establishment_id = (int)($_POST['establishment_id'] ?? $_GET['establishment_id'] ?? 0);
    $service_ids_raw = $_POST['service_ids'] ?? $_GET['service_ids'] ?? '[]';
    $service_ids = is_string($service_ids_raw) ? (json_decode($service_ids_raw, true) ?: []) : (array)$service_ids_raw;

    if ($establishment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Chýba establishment_id.']);
        exit;
    }

    $emp_stmt = $conn->prepare("SELECT id, name, title, avatar_url FROM employees WHERE establishment_id = ? AND is_active = 1 ORDER BY is_owner DESC, order_index ASC, id ASC");
    $emp_stmt->bind_param("i", $establishment_id);
    $emp_stmt->execute();
    $emp_res = $emp_stmt->get_result();
    $employees = [];
    while ($row = $emp_res->fetch_assoc()) { $employees[] = $row; }

    if (!empty($employees) && !empty($service_ids)) {
        $in_services = implode(',', array_map('intval', $service_ids));
        $in_emp = implode(',', array_map(function($e){ return (int)$e['id']; }, $employees));
        $cap_stmt = $conn->query("SELECT employee_id, COUNT(DISTINCT service_id) as cnt FROM employee_services WHERE service_id IN ($in_services) AND employee_id IN ($in_emp) GROUP BY employee_id");
        $qualified = [];
        while ($row = $cap_stmt->fetch_assoc()) {
            if ((int)$row['cnt'] === count($service_ids)) { $qualified[(int)$row['employee_id']] = true; }
        }
        if (!empty($qualified)) {
            $employees = array_values(array_filter($employees, function($e) use ($qualified) { return isset($qualified[(int)$e['id']]); }));
        }
    }

    // Individualne ceny/trvanie sluzieb podla zamestnanca (employee_services), aby verejna
    // stranka mohla prepocitat cenu po vybere konkretneho zamestnanca
    if (!empty($employees) && !empty($service_ids)) {
        $in_services2 = implode(',', array_map('intval', $service_ids));
        $in_emp2 = implode(',', array_map(function($e){ return (int)$e['id']; }, $employees));
        $price_stmt = $conn->query("SELECT employee_id, service_id, price, duration_minutes FROM employee_services WHERE service_id IN ($in_services2) AND employee_id IN ($in_emp2)");
        $priceMap = [];
        while ($row = $price_stmt->fetch_assoc()) {
            $priceMap[(int)$row['employee_id']][(int)$row['service_id']] = [
                'price' => $row['price'] !== null ? (float)$row['price'] : null,
                'duration_minutes' => $row['duration_minutes'] !== null ? (int)$row['duration_minutes'] : null,
            ];
        }
        foreach ($employees as &$emp) {
            $emp['service_prices'] = $priceMap[(int)$emp['id']] ?? [];
        }
        unset($emp);
    }
    echo json_encode(['success' => true, 'employees' => $employees]);
    exit;
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
