<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Kontrola prihlásenia
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'require_auth' => true,
        'message' => 'Na rezerváciu termínu sa musíte najprv prihlásiť alebo zaregistrovať.'
    ]);
    exit;
}

// Self-migrácia: rezervácia potrebuje zamestnanca a buffer uložený priamo pri sebe (kvôli kontrole kolízie)
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS employee_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS buffer_before_minutes INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS buffer_after_minutes INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
// Bezpečný token na správu rezervácie z e-mailu bez prihlásenia (zrušenie/zmena termínu)
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS manage_token VARCHAR(64) DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD UNIQUE INDEX IF NOT EXISTS idx_manage_token (manage_token)"); } catch (Exception $e) {}
// Opakované rezervácie — séria, ku ktorej patrí táto aj nasledujúce vygenerované rezervácie
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS recurring_series_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("CREATE TABLE IF NOT EXISTS recurring_series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    establishment_id INT NOT NULL,
    interval_weeks INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS voucher_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS gift_voucher_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS gift_voucher_amount_used DECIMAL(8,2) DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS customer_note VARCHAR(500) DEFAULT NULL"); } catch (Exception $e) {}
// Skutočná cena zaplatená za túto rezerváciu (po zľavách/voucheroch) — snímka v čase rezervácie,
// nezávislá od neskorších zmien cenníka služby. Potrebné pre reálnu segmentáciu podľa útraty.
try { $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS price_at_booking DECIMAL(10,2) DEFAULT NULL"); } catch (Exception $e) {}
$conn->query("CREATE TABLE IF NOT EXISTS utm_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    customer_id INT NOT NULL,
    source VARCHAR(100) DEFAULT NULL,
    medium VARCHAR(100) DEFAULT NULL,
    campaign VARCHAR(150) DEFAULT NULL,
    landing_page VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_customer_est (customer_id, establishment_id),
    INDEX idx_est (establishment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

require_once __DIR__ . '/../includes/availability_helper.php';

$user_id = (int)$_SESSION['user_id'];

// 2. Kontrola overenia používateľa (6-miestny kód)
$u_stmt = $conn->prepare("SELECT id, email, full_name, phone, role, avatar_url, is_verified FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user = $u_stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Používateľský účet nebol nájdený.']);
    exit;
}

if (empty($user['is_verified']) || (int)$user['is_verified'] !== 1) {
    echo json_encode([
        'success' => false,
        'require_verification' => true,
        'message' => 'Váš e-mail nie je overený. Prosím overte svoj e-mail zadaním 6-miestneho kódu.'
    ]);
    exit;
}

// 3. Spracovanie vstupov
$establishment_id = isset($_POST['establishment_id']) ? (int)$_POST['establishment_id'] : 0;
$service_ids_raw = $_POST['service_ids'] ?? [];
$booking_date = trim($_POST['booking_date'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$requested_employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0; // 0 = "Ktokoľvek"
$marketing_consent = isset($_POST['marketing_consent']) && $_POST['marketing_consent'] === '1';
$recurring_enabled = isset($_POST['recurring_enabled']) && $_POST['recurring_enabled'] === '1';
$recurring_interval_weeks = max(1, (int)($_POST['recurring_interval_weeks'] ?? 1));
$recurring_occurrences = min(52, max(2, (int)($_POST['recurring_occurrences'] ?? 4))); // vrátane prvej rezervácie, max. 52 do budúcna kvôli rozumnému limitu
$customer_note = trim(mb_substr(trim($_POST['customer_note'] ?? ''), 0, 500));

if ($marketing_consent) {
    try { $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS marketing_consent TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    $consent_stmt = $conn->prepare("UPDATE users SET marketing_consent = 1 WHERE id = ?");
    $consent_stmt->bind_param("i", $user_id);
    $consent_stmt->execute();
}

// Fetch establishment details (for opening hours validation)
$conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS capacity_per_slot INT DEFAULT NULL");
$est_stmt = $conn->prepare("SELECT opening_hours, subscription_tier, deposit_iban, confirmation_mode, name as est_name, capacity_per_slot FROM establishments WHERE id = ?");
$est_stmt->bind_param("i", $establishment_id);
$est_stmt->execute();
$est_res = $est_stmt->get_result();
if ($est_res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Prevádzka neexistuje.']);
    exit;
}
$est_data = $est_res->fetch_assoc();
$opening_hours_json = $est_data['opening_hours'] ?? '{}';
$opening_hours = json_decode($opening_hours_json, true) ?: [];
$capacity_per_slot = $est_data['capacity_per_slot'] !== null ? (int)$est_data['capacity_per_slot'] : null;

if (is_string($service_ids_raw)) {
    $service_ids = json_decode($service_ids_raw, true) ?: [];
} else {
    $service_ids = (array)$service_ids_raw;
}

if ($establishment_id <= 0 || empty($service_ids) || empty($booking_date) || empty($start_time)) {
    echo json_encode(['success' => false, 'message' => 'Prosím vyplňte všetky povinné údaje pre rezerváciu.']);
    exit;
}

// Blokovanie zákazníka danou prevádzkou (súkromné rozhodnutie prevádzky, netýka sa ostatných prevádzok)
$conn->query("CREATE TABLE IF NOT EXISTS blocked_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    customer_id INT NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    blocked_until DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_block (establishment_id, customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$block_check = $conn->prepare("SELECT id FROM blocked_customers WHERE establishment_id = ? AND customer_id = ? AND (blocked_until IS NULL OR blocked_until >= CURDATE())");
$block_check->bind_param("ii", $establishment_id, $user_id);
$block_check->execute();
if ($block_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Online rezervácia v tejto prevádzke momentálne nie je pre váš účet dostupná. V prípade potreby kontaktujte prevádzku priamo.']);
    exit;
}

// Sviatky / celoprevádzkové zatvorenie
$conn->query("CREATE TABLE IF NOT EXISTS establishment_closures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lookup (establishment_id, date_from, date_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$closure_check = $conn->prepare("SELECT reason FROM establishment_closures WHERE establishment_id = ? AND date_from <= ? AND date_to >= ? LIMIT 1");
$closure_check->bind_param("iss", $establishment_id, $booking_date, $booking_date);
$closure_check->execute();
if ($closure_row = $closure_check->get_result()->fetch_assoc()) {
    $reason_txt = $closure_row['reason'] ? (' (' . $closure_row['reason'] . ')') : '';
    echo json_encode(['success' => false, 'message' => "Prevádzka je v tento deň zatvorená{$reason_txt}. Vyberte prosím iný deň."]);
    exit;
}

// Aktualizácia telefónneho čísla používateľa ak ho vyplnil/zmenil
if (!empty($phone) && $phone !== ($user['phone'] ?? '')) {
    $upd_phone = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
    $upd_phone->bind_param("si", $phone, $user_id);
    $upd_phone->execute();
}

// 4. Načítanie vybraných služieb a výpočet celkového času, sumy a bufferu
$service_names = [];
$total_duration = 0;
$total_price = 0.0;
$buffer_before = 0;
$buffer_after = 0;

foreach ($service_ids as $sid) {
    $sid = (int)$sid;
    $s_stmt = $conn->prepare("SELECT name, duration_minutes, price, buffer_before_minutes, buffer_after_minutes FROM services WHERE id = ? AND establishment_id = ?");
    $s_stmt->bind_param("ii", $sid, $establishment_id);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result();
    if ($s_row = $s_res->fetch_assoc()) {
        $svc_price = (float)$s_row['price'];
        $svc_duration = (int)$s_row['duration_minutes'];
        // Individualna cena/trvanie podla zamestnanca (employee_services) ma prednost pred zakladnou cenou,
        // ak si zakaznik vybral konkretneho zamestnanca (nie "Ktokolvek volny")
        if ($requested_employee_id > 0) {
            $es_stmt = $conn->prepare("SELECT price, duration_minutes FROM employee_services WHERE employee_id = ? AND service_id = ?");
            $es_stmt->bind_param("ii", $requested_employee_id, $sid);
            $es_stmt->execute();
            if ($es_row = $es_stmt->get_result()->fetch_assoc()) {
                if ($es_row['price'] !== null) { $svc_price = (float)$es_row['price']; }
                if ($es_row['duration_minutes'] !== null) { $svc_duration = (int)$es_row['duration_minutes']; }
            }
        } else {
            // "Ktokolvek volny" - pouzijeme najvyssiu (najbezpecnejsiu) cenu/trvanie spomedzi vsetkych
            // zamestnancov, aby sa nestalo, ze zakaznikovi ukazeme nizsiu cenu, nez akou ho nakoniec obsluzia
            $max_stmt = $conn->prepare("SELECT MAX(price) as max_price, MAX(duration_minutes) as max_duration FROM employee_services WHERE service_id = ?");
            $max_stmt->bind_param("i", $sid);
            $max_stmt->execute();
            if ($max_row = $max_stmt->get_result()->fetch_assoc()) {
                if ($max_row['max_price'] !== null) { $svc_price = max($svc_price, (float)$max_row['max_price']); }
                if ($max_row['max_duration'] !== null) { $svc_duration = max($svc_duration, (int)$max_row['max_duration']); }
            }
        }
        $service_names[] = $s_row['name'];
        $total_duration += $svc_duration;
        $total_price += $svc_price;
        $buffer_before = max($buffer_before, (int)($s_row['buffer_before_minutes'] ?? 0));
        $buffer_after = max($buffer_after, (int)($s_row['buffer_after_minutes'] ?? 0));
    }
}

if (empty($service_names)) {
    // Fallback ak sú demo služby
    $primary_sid = (int)($service_ids[0] ?? 1);
    $total_duration = 45;
} else {
    $primary_sid = (int)$service_ids[0];
}

// 4c. Voucher / zľavový kód — overenie sa opakuje aj tu server-side, klientská strana sa nedá dôverovať
$applied_voucher_id = null;
$voucher_code = strtoupper(trim($_POST['voucher_code'] ?? ''));
if ($voucher_code !== '') {
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

    $v_stmt = $conn->prepare("SELECT * FROM vouchers WHERE code = ? AND business_id = ? AND is_active = 1 LIMIT 1");
    $v_stmt->bind_param("si", $voucher_code, $establishment_id);
    $v_stmt->execute();
    $voucher_row = $v_stmt->get_result()->fetch_assoc();

    $voucher_ok = $voucher_row
        && (!$voucher_row['valid_until'] || strtotime($voucher_row['valid_until']) >= strtotime('today'))
        && ($voucher_row['max_uses'] === null || (int)$voucher_row['uses_count'] < (int)$voucher_row['max_uses']);

    if ($voucher_ok) {
        $applied_voucher_id = (int)$voucher_row['id'];
        if ($voucher_row['discount_type'] === 'percent') {
            $total_price -= $total_price * ((float)$voucher_row['discount_value'] / 100);
        } else {
            $total_price -= (float)$voucher_row['discount_value'];
        }
        $total_price = max(0, round($total_price, 2));
    }
    // Ak kód nie je platný, jednoducho sa ignoruje (zákazník ho nemusel medzičasom overiť) — rezervácia pokračuje bez zľavy
}

// 4d. Darčekový poukaz — samostatný od zľavového kódu, má peňažnú hodnotu, ktorá sa odpočítava z ceny
$applied_gift_voucher_id = null;
$gift_voucher_amount_used = 0;
$gift_voucher_code = strtoupper(trim($_POST['gift_voucher_code'] ?? ''));
if ($gift_voucher_code !== '') {
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

    $gv_stmt = $conn->prepare("SELECT * FROM gift_vouchers WHERE code = ? AND establishment_id = ? AND status = 'active' LIMIT 1");
    $gv_stmt->bind_param("si", $gift_voucher_code, $establishment_id);
    $gv_stmt->execute();
    $gift_voucher_row = $gv_stmt->get_result()->fetch_assoc();

    $gift_voucher_ok = $gift_voucher_row
        && (!$gift_voucher_row['valid_until'] || strtotime($gift_voucher_row['valid_until']) >= strtotime('today'))
        && (float)$gift_voucher_row['remaining_value'] > 0;

    if ($gift_voucher_ok) {
        $applied_gift_voucher_id = (int)$gift_voucher_row['id'];
        $gift_voucher_amount_used = min((float)$gift_voucher_row['remaining_value'], $total_price);
        $total_price = max(0, round($total_price - $gift_voucher_amount_used, 2));
    }
    // Ak kód nie je platný, jednoducho sa ignoruje — rezervácia pokračuje bez uplatnenia poukazu
}

// Výpočet koncového času (end_time)
$start_dt = new DateTime($booking_date . ' ' . $start_time);
$end_dt = clone $start_dt;
$end_dt->modify('+' . ($total_duration > 0 ? $total_duration : 30) . ' minutes');
$end_time = $end_dt->format('H:i:s');

// 4b. Kontrola kolízie termínov (rovnaká logika ako includes/availability_helper.php) + priradenie zamestnanca
$blocked_start = (clone $start_dt)->modify('-' . $buffer_before . ' minutes');
$blocked_end = (clone $end_dt)->modify('+' . $buffer_after . ' minutes');

// Kapacita (skupinové služby) — dáva zmysel len pri výbere presne jednej služby
$group_service_id = null; $group_capacity = 1;
if (count($service_ids) === 1) {
    $cap_check = $conn->prepare("SELECT capacity FROM services WHERE id = ? AND establishment_id = ?");
    $only_sid = (int)$service_ids[0];
    $cap_check->bind_param("ii", $only_sid, $establishment_id);
    $cap_check->execute();
    $cap_row = $cap_check->get_result()->fetch_assoc();
    $cap_val = (int)($cap_row['capacity'] ?? 1);
    if ($cap_val > 1) { $group_service_id = $only_sid; $group_capacity = $cap_val; }
}

$candidate_ids = [];
if ($requested_employee_id > 0) {
    $chk = $conn->prepare("SELECT id FROM employees WHERE id = ? AND establishment_id = ? AND is_active = 1");
    $chk->bind_param("ii", $requested_employee_id, $establishment_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) { $candidate_ids = [$requested_employee_id]; }
} else {
    $emp_stmt = $conn->prepare("SELECT id FROM employees WHERE establishment_id = ? AND is_active = 1");
    $emp_stmt->bind_param("i", $establishment_id);
    $emp_stmt->execute();
    $emp_res = $emp_stmt->get_result();
    while ($row = $emp_res->fetch_assoc()) { $candidate_ids[] = (int)$row['id']; }
}

if (empty($candidate_ids)) {
    echo json_encode(['success' => false, 'message' => 'Prevádzka nemá dostupného žiadneho zamestnanca.']);
    exit;
}

$in_candidates = implode(',', array_map('intval', $candidate_ids));
$bk_stmt = $conn->query("SELECT employee_id, service_id, start_time, end_time, buffer_before_minutes, buffer_after_minutes FROM bookings
    WHERE establishment_id = $establishment_id AND booking_date = '" . $conn->real_escape_string($booking_date) . "'
    AND status NOT IN ('cancelled','rejected')
    AND (employee_id IN ($in_candidates) OR employee_id IS NULL)");
$bookings_by_employee = [];
while ($row = $bk_stmt->fetch_assoc()) {
    $emp_key = $row['employee_id'] !== null ? (int)$row['employee_id'] : 0;
    $bookings_by_employee[$emp_key][] = $row;
}

$unavailable_employees = [];
$unavail_stmt = $conn->query("SELECT employee_id FROM employee_unavailabilities
    WHERE employee_id IN ($in_candidates) AND date_from <= '" . $conn->real_escape_string($booking_date) . "' AND date_to >= '" . $conn->real_escape_string($booking_date) . "'");
while ($row = $unavail_stmt->fetch_assoc()) { $unavailable_employees[(int)$row['employee_id']] = true; }

// Celoprevádzková kapacita (koľko klientov naraz sa dá obslúžiť, nezávisle od zamestnanca) — naprieč
// VŠETKÝMI zamestnancami prevádzky, nielen kandidátmi (rovnaká logika ako includes/availability_helper.php)
if ($capacity_per_slot !== null && $capacity_per_slot > 0) {
    $cap_bk_stmt = $conn->query("SELECT start_time, end_time, buffer_before_minutes, buffer_after_minutes FROM bookings
        WHERE establishment_id = $establishment_id AND booking_date = '" . $conn->real_escape_string($booking_date) . "'
        AND status NOT IN ('cancelled','rejected')");
    $concurrent = 0;
    while ($row = $cap_bk_stmt->fetch_assoc()) {
        $ex_start = (new DateTime($booking_date . ' ' . $row['start_time']))->modify('-' . (int)$row['buffer_before_minutes'] . ' minutes');
        $ex_end = (new DateTime($booking_date . ' ' . $row['end_time']))->modify('+' . (int)$row['buffer_after_minutes'] . ' minutes');
        if ($blocked_start < $ex_end && $blocked_end > $ex_start) { $concurrent++; }
    }
    if ($concurrent >= $capacity_per_slot) {
        echo json_encode(['success' => false, 'message' => 'Tento termín je už žiaľ plne obsadený. Vyberte prosím iný čas.']);
        exit;
    }
}

// Vlastný rozvrh zamestnanca (pracovné hodiny + obedňajšia prestávka) — rovnaká logika ako includes/availability_helper.php
$employee_schedule = [];
$conn->query("CREATE TABLE IF NOT EXISTS employee_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    day_of_week TINYINT(1) NOT NULL,
    start_time TIME DEFAULT '09:00:00',
    end_time TIME DEFAULT '17:00:00',
    is_off TINYINT(1) NOT NULL DEFAULT 0,
    break_start TIME DEFAULT NULL,
    break_end TIME DEFAULT NULL,
    UNIQUE KEY emp_day (employee_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$day_of_week_db = ((int)$start_dt->format('N')) - 1;
$sched_stmt = $conn->query("SELECT employee_id, start_time, end_time, is_off, break_start, break_end
    FROM employee_schedules WHERE employee_id IN ($in_candidates) AND day_of_week = $day_of_week_db");
while ($row = $sched_stmt->fetch_assoc()) { $employee_schedule[(int)$row['employee_id']] = $row; }

$assigned_employee_id = null;
foreach ($candidate_ids as $emp_id) {
    if (!empty($unavailable_employees[$emp_id])) { continue; }

    if (isset($employee_schedule[$emp_id])) {
        $sched = $employee_schedule[$emp_id];
        if ((int)$sched['is_off'] === 1) { continue; }
        $emp_start = new DateTime($booking_date . ' ' . $sched['start_time']);
        $emp_end = new DateTime($booking_date . ' ' . $sched['end_time']);
        if ($start_dt < $emp_start || $end_dt > $emp_end) { continue; }
        if (!empty($sched['break_start']) && !empty($sched['break_end'])) {
            $break_start = new DateTime($booking_date . ' ' . $sched['break_start']);
            $break_end = new DateTime($booking_date . ' ' . $sched['break_end']);
            if ($start_dt < $break_end && $end_dt > $break_start) { continue; }
        }
    }

    $conflicts = array_merge($bookings_by_employee[$emp_id] ?? [], $bookings_by_employee[0] ?? []);
    $is_free = true;
    $same_group_slot_count = 0;
    foreach ($conflicts as $existing) {
        if ($group_service_id !== null && (int)$existing['service_id'] === $group_service_id && substr($existing['start_time'], 0, 5) === $start_dt->format('H:i')) {
            $same_group_slot_count++;
            continue;
        }
        $ex_start = (new DateTime($booking_date . ' ' . $existing['start_time']))->modify('-' . (int)$existing['buffer_before_minutes'] . ' minutes');
        $ex_end = (new DateTime($booking_date . ' ' . $existing['end_time']))->modify('+' . (int)$existing['buffer_after_minutes'] . ' minutes');
        if ($blocked_start < $ex_end && $blocked_end > $ex_start) { $is_free = false; break; }
    }
    if ($is_free && $group_service_id !== null && $same_group_slot_count >= $group_capacity) { $is_free = false; }

    if ($is_free) { $assigned_employee_id = $emp_id; break; }
}

if ($assigned_employee_id === null) {
    echo json_encode(['success' => false, 'message' => 'Tento termín je už žiaľ obsadený. Vyberte prosím iný čas.']);
    exit;
}

// Validácia voči otváracím hodinám (aby neprekročili záverečnú)
$day_map = ['mon'=>'mon', 'tue'=>'tue', 'wed'=>'wed', 'thu'=>'thu', 'fri'=>'fri', 'sat'=>'sat', 'sun'=>'sun'];
$day_of_week_key = $day_map[strtolower($start_dt->format('D'))] ?? 'mon';
$day_hours = $opening_hours[$day_of_week_key] ?? null;

if ($day_hours && isset($day_hours['close'])) {
    $close_dt = new DateTime($booking_date . ' ' . $day_hours['close']);
    if ($end_dt > $close_dt) {
        echo json_encode(['success' => false, 'message' => "Rezervácia by presiahla otváracie hodiny. Prevádzka zatvára o " . $day_hours['close'] . "."]);
        exit;
    }
} else {
    // Ak otváracie hodiny nie sú definované pre daný deň (môže byť zatvorené)
    if (!$day_hours || empty($day_hours['open'])) {
        echo json_encode(['success' => false, 'message' => "Prevádzka je v tento deň zatvorená."]);
        exit;
    }
}

// 5. Uloženie rezervácie do tabuľky bookings

// 4b. Rozhodnutie o statuse a zálohe
$status = 'pending';
$requires_deposit = false;
$deposit_amount = 0;
$qr_code_png_base64 = '';

$tier = $est_data['subscription_tier'] ?? 'free';
$deposit_tiers = ['pro', 'vip'];

// Dôveryhodnostné skóre zákazníka (obojsmerné hodnotenia) — 'trusted' zálohu nepotrebuje,
// 'risky' ju potrebuje vždy a vo zvýšenej výške, bez ohľadu na nastavenie prevádzky.
require_once __DIR__ . '/../includes/trust_helper.php';
$trust = getCustomerTrustTier($conn, $user_id);

if ($trust['tier'] === 'risky' && !empty($est_data['deposit_iban'])) {
    $status = 'pending_deposit';
    $requires_deposit = true;
    // Zvýšená záloha pre rizikového zákazníka (opakované problémy v minulosti) — 50 % z ceny služby
    $deposit_amount = max(5.0, $total_price * 0.50);
} elseif ($trust['tier'] === 'trusted') {
    // Dôveryhodný zákazník — záloha sa nevyžaduje bez ohľadu na nastavenie prevádzky
} elseif (in_array($tier, $deposit_tiers) && !empty($est_data['deposit_iban'])) {
    if (empty($user['card_verified']) || (int)$user['card_verified'] === 0) {
        $status = 'pending_deposit';
        $requires_deposit = true;
        // Štandardná záloha = 20 % z ceny služby, min. 5 EUR
        $deposit_amount = max(5.0, $total_price * 0.20);
    }
} elseif ($est_data['confirmation_mode'] === 'auto') {
    $status = 'confirmed';
}

// Vygenerovanie platobného QR kódu — spoločné pre všetky vyššie uvedené prípady, ktoré vyžadujú zálohu
if ($requires_deposit && !empty($est_data['deposit_iban'])) {
    $iban = str_replace(' ', '', strtoupper($est_data['deposit_iban']));
    $name = trim(substr(preg_replace('/[^A-Za-z0-9\s]/', '', $est_data['est_name']), 0, 70));
    if ($name === '') { $name = 'Prevadzka'; } // fallback pre Pay by Square, ktoré vyžaduje neprázdny názov príjemcu
    $amount_str = number_format($deposit_amount, 2, '.', '');
    $booking_id_tmp = time(); // dočasný VS, prepíše sa po INSERT
    $msg = "Zaloha rezervacia " . $booking_id_tmp;

    // Detekcia krajiny podľa IBAN prefixu
    $iban_country = substr($iban, 0, 2);

    require_once __DIR__ . '/../includes/qr_helper.php';
    if ($iban_country === 'SK') {
        // Skutočný Pay by Square (LZMA + Base32Hex) — bez tohto kódovania ho banková appka nerozpozná
        try {
            $variable_symbol = substr((string)$booking_id_tmp, 0, 10);
            $pbsq = generate_pay_by_square_payload($iban, $deposit_amount, $name, 'Zaloha rezervacia', $variable_symbol);
            $qr_code_png_base64 = base64_encode(generate_qr_png_bytes($pbsq));
        } catch (Exception $e) {
            // Neplatné údaje (napr. zle zadaný IBAN prevádzky) — radšej rezervácia bez QR než pád celej rezervácie
            error_log('Pay by Square generovanie zlyhalo: ' . $e->getMessage());
            $qr_code_png_base64 = '';
        }
    } elseif ($iban_country === 'CZ') {
        // České SPAYD — jednoduchý text, kompresia sa tu nevyžaduje
        $pbsq = "SPD*1.0*ACC:{$iban}*AM:{$amount_str}*CC:EUR*MSG:Zaloha rezervacia*";
        $qr_code_png_base64 = base64_encode(generate_qr_png_bytes($pbsq));
    } else {
        // EPC / SEPA GiroCode — štandard pre zvyšok EÚ (funguje aj s Revolut a inými SEPA bankami)
        $sepa_str = "BCD\n002\n1\nSCT\n\n{$name}\n{$iban}\nEUR{$amount_str}\n\n\n{$msg}\n";
        $qr_code_png_base64 = base64_encode(generate_qr_png_bytes($sepa_str));
    }
}

// 5. Uloženie rezervácie do tabuľky bookings
$manage_token = bin2hex(random_bytes(20)); // na správu rezervácie z e-mailu bez prihlásenia
$ins_stmt = $conn->prepare("INSERT INTO bookings (customer_id, service_id, establishment_id, employee_id, booking_date, start_time, end_time, buffer_before_minutes, buffer_after_minutes, status, manage_token, voucher_id, gift_voucher_id, gift_voucher_amount_used, customer_note, price_at_booking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$start_time_sql = $start_dt->format('H:i:s');
$ins_stmt->bind_param("iiiisssiissiidsd", $user_id, $primary_sid, $establishment_id, $assigned_employee_id, $booking_date, $start_time_sql, $end_time, $buffer_before, $buffer_after, $status, $manage_token, $applied_voucher_id, $applied_gift_voucher_id, $gift_voucher_amount_used, $customer_note, $total_price);

if ($ins_stmt->execute()) {
    $booking_id = $ins_stmt->insert_id;

    if ($applied_voucher_id !== null) {
        $conn->query("UPDATE vouchers SET uses_count = uses_count + 1 WHERE id = " . (int)$applied_voucher_id);
    }

    if ($applied_gift_voucher_id !== null && $gift_voucher_amount_used > 0) {
        $new_remaining = max(0, round($gift_voucher_row['remaining_value'] - $gift_voucher_amount_used, 2));
        $new_gv_status = $new_remaining <= 0 ? 'redeemed' : 'active';
        $gv_upd = $conn->prepare("UPDATE gift_vouchers SET remaining_value = ?, status = ? WHERE id = ?");
        $gv_upd->bind_param("dsi", $new_remaining, $new_gv_status, $applied_gift_voucher_id);
        $gv_upd->execute();
        $conn->query("INSERT INTO gift_voucher_redemptions (gift_voucher_id, booking_id, amount_used) VALUES (" . (int)$applied_gift_voucher_id . ", " . (int)$booking_id . ", " . (float)$gift_voucher_amount_used . ")");
    }

    // Priradenie UTM zdroja k prvej rezervácii tohto zákazníka v danej prevádzke (ak prišiel cez odkaz s utm_* parametrami)
    if (!empty($_SESSION['utm_attribution'][$establishment_id])) {
        $utm = $_SESSION['utm_attribution'][$establishment_id];
        $utm_stmt = $conn->prepare("INSERT IGNORE INTO utm_leads (establishment_id, customer_id, source, medium, campaign, landing_page) VALUES (?, ?, ?, ?, ?, ?)");
        $utm_stmt->bind_param("iissss", $establishment_id, $user_id, $utm['source'], $utm['medium'], $utm['campaign'], $utm['page']);
        $utm_stmt->execute();
        unset($_SESSION['utm_attribution'][$establishment_id]);
    }

    // 5b. Opakovaná rezervácia — vygenerovanie ďalších termínov v sérii
    $recurring_summary = null;
    if ($recurring_enabled && $recurring_occurrences > 1) {
        $series_stmt = $conn->prepare("INSERT INTO recurring_series (customer_id, establishment_id, interval_weeks) VALUES (?, ?, ?)");
        $series_stmt->bind_param("iii", $user_id, $establishment_id, $recurring_interval_weeks);
        $series_stmt->execute();
        $series_id = $series_stmt->insert_id;

        $link_stmt = $conn->prepare("UPDATE bookings SET recurring_series_id = ? WHERE id = ?");
        $link_stmt->bind_param("ii", $series_id, $booking_id);
        $link_stmt->execute();

        $created_count = 1;
        $skipped_dates = [];

        for ($occurrence = 2; $occurrence <= $recurring_occurrences; $occurrence++) {
            $occurrence_dt = (clone $start_dt)->modify('+' . (($occurrence - 1) * $recurring_interval_weeks * 7) . ' days');
            $occurrence_date = $occurrence_dt->format('Y-m-d');
            $occurrence_time = $occurrence_dt->format('H:i');

            $slot_error = null;
            $employee_for_check = $assigned_employee_id ?: 0;
            $free_slots = computeAvailableSlots($conn, $establishment_id, $service_ids, $occurrence_date, $employee_for_check, $slot_error);

            if ($slot_error || !in_array($occurrence_time, $free_slots)) {
                $skipped_dates[] = $occurrence_date . ' ' . $occurrence_time;
                continue;
            }

            $occ_end_dt = (clone $occurrence_dt)->modify('+' . ($total_duration > 0 ? $total_duration : 30) . ' minutes');
            $occ_manage_token = bin2hex(random_bytes(20));
            $occ_start_sql = $occurrence_dt->format('H:i:s');
            $occ_end_sql = $occ_end_dt->format('H:i:s');
            $occ_status = $status; // rovnaký stav ako prvá rezervácia (napr. čaká na potvrdenie / potvrdené)

            $occ_ins = $conn->prepare("INSERT INTO bookings (customer_id, service_id, establishment_id, employee_id, booking_date, start_time, end_time, buffer_before_minutes, buffer_after_minutes, status, manage_token, recurring_series_id, price_at_booking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $occ_ins->bind_param("iiiisssiissid", $user_id, $primary_sid, $establishment_id, $assigned_employee_id, $occurrence_date, $occ_start_sql, $occ_end_sql, $buffer_before, $buffer_after, $occ_status, $occ_manage_token, $series_id, $total_price);
            if ($occ_ins->execute()) {
                $created_count++;
            } else {
                $skipped_dates[] = $occurrence_date . ' ' . $occurrence_time;
            }
        }

        $recurring_summary = ['created' => $created_count, 'skipped' => $skipped_dates];
    }

    // 6. Odoslanie e-mailových notifikácií (ak existuje mailer)
    @include_once __DIR__ . '/mailer.php';
    if (function_exists('sendBookingEmail')) {
        // Získanie údajov o prevádzke
        $est_info_stmt = $conn->prepare("SELECT e.name as est_name, e.city, e.address, u.id as est_user_id, u.email as est_email FROM establishments e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
        $est_info_stmt->bind_param("i", $establishment_id);
        $est_info_stmt->execute();
        $est_info = $est_info_stmt->get_result()->fetch_assoc();

        if ($est_info) {
            $root_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $booking_details = [
                'service_name' => implode(' + ', $service_names) ?: 'Rezervovaná služba',
                'duration' => $total_duration,
                'date' => $booking_date,
                'time' => $start_time,
                'establishment_name' => $est_info['est_name'],
                'location' => $est_info['address'] . ', ' . $est_info['city'],
                'customer_name' => $user['full_name'],
                'customer_phone' => $phone ?: ($user['phone'] ?? ''),
                'total_price' => number_format($total_price, 2) . ' €',
                'status' => $status,
                'requires_deposit' => $requires_deposit,
                'deposit_amount' => $requires_deposit ? number_format($deposit_amount, 2) . ' €' : '0.00 €',
                'qr_code_png_base64' => $qr_code_png_base64,
                'manage_url' => $root_url . '/sprava-rezervacie.php?token=' . $manage_token,
                'customer_note' => $customer_note
            ];
            
            // E-mail zákazníkovi — ak má prevádzka pripojenú vlastnú schránku, pošle sa z nej
            @sendBookingEmail($user['email'], $user['full_name'], $booking_details, true, $est_info['est_user_id'] ?? null);
            // E-mail prevádzke
            if (!empty($est_info['est_email'])) {
                @sendBookingEmail($est_info['est_email'], $est_info['est_name'], $booking_details, false);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'manage_token' => $manage_token,
        'recurring' => $recurring_summary,
        'message' => 'Vaša rezervácia bola úspešne odoslaná salónu! Podrobnosti nájdete aj vo svojom profile.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nastala chyba pri ukladaní rezervácie do databázy.']);
}
