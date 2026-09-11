<?php
/**
 * Zdieľaná logika výpočtu reálne voľných termínov — používa ju api/availability.php
 * (verejný rezervačný formulár) aj api/manage_booking.php (presun existujúcej rezervácie).
 * Nesmie obsahovať žiadny vykonávací kód mimo funkcie, aby sa dal bezpečne require-núť odkiaľkoľvek.
 */

if (!function_exists('computeAvailableSlots')) {
    /**
     * Vypočíta zoznam reálne voľných začiatočných časov pre daný dátum, vybrané služby a (voliteľne) zamestnanca.
     * Zohľadňuje: otváracie hodiny prevádzky, existujúce rezervácie (+ ich buffer), dovolenku/PN zamestnanca.
     *
     * @param int|null $exclude_booking_id Rezervácia, ktorá sa má pri kontrole kolízie ignorovať (napr. tá, ktorú práve presúvame)
     */
    function computeAvailableSlots($conn, $establishment_id, $service_ids, $booking_date, $employee_id_filter, &$error, $exclude_booking_id = null) {
        // Otváracie hodiny prevádzky
        $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS capacity_per_slot INT DEFAULT NULL");
        $est_stmt = $conn->prepare("SELECT opening_hours, capacity_per_slot FROM establishments WHERE id = ?");
        $est_stmt->bind_param("i", $establishment_id);
        $est_stmt->execute();
        $est_res = $est_stmt->get_result()->fetch_assoc();
        if (!$est_res) { $error = 'Prevádzka neexistuje.'; return []; }
        $capacity_per_slot = $est_res['capacity_per_slot'] !== null ? (int)$est_res['capacity_per_slot'] : null;

        $opening_hours = json_decode($est_res['opening_hours'] ?? '{}', true) ?: [];
        $day_map = ['Mon'=>'mon','Tue'=>'tue','Wed'=>'wed','Thu'=>'thu','Fri'=>'fri','Sat'=>'sat','Sun'=>'sun'];
        $date_obj = DateTime::createFromFormat('Y-m-d', $booking_date);
        if (!$date_obj) { $error = 'Neplatný dátum.'; return []; }
        $day_key = $day_map[$date_obj->format('D')] ?? 'mon';
        $day_hours = $opening_hours[$day_key] ?? null;

        if (!$day_hours || empty($day_hours['open']) || empty($day_hours['close'])) {
            return []; // prevádzka je v tento deň zatvorená
        }

        // Sviatky / celoprevádzkové zatvorenie (dovolenka firmy a pod.)
        $conn->query("CREATE TABLE IF NOT EXISTS establishment_closures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            establishment_id INT NOT NULL,
            date_from DATE NOT NULL,
            date_to DATE NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lookup (establishment_id, date_from, date_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $closure_stmt = $conn->prepare("SELECT id FROM establishment_closures WHERE establishment_id = ? AND date_from <= ? AND date_to >= ? LIMIT 1");
        $closure_stmt->bind_param("iss", $establishment_id, $booking_date, $booking_date);
        $closure_stmt->execute();
        if ($closure_stmt->get_result()->num_rows > 0) {
            return []; // prevádzka je v tento deň celkovo zatvorená
        }

        $open_dt = new DateTime($booking_date . ' ' . $day_hours['open']);
        $close_dt = new DateTime($booking_date . ' ' . $day_hours['close']);

        // Trvanie a buffer vybraných služieb (MAX buffer naprieč vybranými službami — bezpečnejšie pri kombinácii viacerých služieb)
        $total_duration = 0; $buffer_before = 0; $buffer_after = 0;
        foreach ($service_ids as $sid) {
            $sid = (int)$sid;
            $s_stmt = $conn->prepare("SELECT duration_minutes, buffer_before_minutes, buffer_after_minutes FROM services WHERE id = ? AND establishment_id = ?");
            $s_stmt->bind_param("ii", $sid, $establishment_id);
            $s_stmt->execute();
            if ($s_row = $s_stmt->get_result()->fetch_assoc()) {
                $total_duration += (int)$s_row['duration_minutes'];
                $buffer_before = max($buffer_before, (int)($s_row['buffer_before_minutes'] ?? 0));
                $buffer_after = max($buffer_after, (int)($s_row['buffer_after_minutes'] ?? 0));
            }
        }
        if ($total_duration <= 0) { $total_duration = 30; }

        // Kapacita (skupinové služby, napr. kurz jogy) — dáva zmysel len pri výbere presne jednej služby
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

        // Kandidáti na zamestnanca
        $candidate_ids = [];
        if ($employee_id_filter > 0) {
            $candidate_ids = [$employee_id_filter];
        } else {
            $emp_stmt = $conn->prepare("SELECT id FROM employees WHERE establishment_id = ? AND is_active = 1");
            $emp_stmt->bind_param("i", $establishment_id);
            $emp_stmt->execute();
            $emp_res = $emp_stmt->get_result();
            while ($row = $emp_res->fetch_assoc()) { $candidate_ids[] = (int)$row['id']; }

            // Ak máme service_ids, obmedzíme len na zamestnancov priradených ku VŠETKÝM vybraným službám (ak sú priradenia nastavené)
            if (!empty($candidate_ids) && !empty($service_ids)) {
                $in_services = implode(',', array_map('intval', $service_ids));
                $in_emp = implode(',', $candidate_ids);
                $cap_stmt = $conn->query("SELECT employee_id, COUNT(DISTINCT service_id) as cnt FROM employee_services WHERE service_id IN ($in_services) AND employee_id IN ($in_emp) GROUP BY employee_id");
                $qualified = [];
                while ($row = $cap_stmt->fetch_assoc()) {
                    if ((int)$row['cnt'] === count($service_ids)) { $qualified[] = (int)$row['employee_id']; }
                }
                if (!empty($qualified)) { $candidate_ids = $qualified; }
            }
        }
        if (empty($candidate_ids)) { return []; }

        // Existujúce rezervácie a neprítomnosti pre kandidátov v daný deň
        $in_candidates = implode(',', array_map('intval', $candidate_ids));
        $exclude_clause = $exclude_booking_id ? (" AND id != " . (int)$exclude_booking_id) : "";
        $bookings_by_employee = [];
        $bk_stmt = $conn->query("SELECT employee_id, service_id, start_time, end_time, buffer_before_minutes, buffer_after_minutes FROM bookings
            WHERE establishment_id = $establishment_id AND booking_date = '" . $conn->real_escape_string($booking_date) . "'
            AND status NOT IN ('cancelled','rejected')
            AND (employee_id IN ($in_candidates) OR employee_id IS NULL){$exclude_clause}");
        while ($row = $bk_stmt->fetch_assoc()) {
            $emp_key = $row['employee_id'] !== null ? (int)$row['employee_id'] : 0; // 0 = legacy rezervácia bez zamestnanca, blokuje všetkých
            $bookings_by_employee[$emp_key][] = $row;
        }

        $unavailable_employees = [];
        $unavail_stmt = $conn->query("SELECT employee_id FROM employee_unavailabilities
            WHERE employee_id IN ($in_candidates) AND date_from <= '" . $conn->real_escape_string($booking_date) . "' AND date_to >= '" . $conn->real_escape_string($booking_date) . "'");
        while ($row = $unavail_stmt->fetch_assoc()) { $unavailable_employees[(int)$row['employee_id']] = true; }

        // Celoprevádzková kapacita (napr. počet kresiel) — nezávislá od toho, ktorý konkrétny zamestnanec
        // je voľný. Naprieč VŠETKÝMI zamestnancami prevádzky (nielen kandidátmi, keďže napr. pri filtri na
        // jedného konkrétneho zamestnanca by inak chýbali rezervácie ostatných kolegov v ten istý deň).
        $all_bookings_today = [];
        if ($capacity_per_slot !== null && $capacity_per_slot > 0) {
            $cap_bk_stmt = $conn->query("SELECT start_time, end_time, buffer_before_minutes, buffer_after_minutes FROM bookings
                WHERE establishment_id = $establishment_id AND booking_date = '" . $conn->real_escape_string($booking_date) . "'
                AND status NOT IN ('cancelled','rejected'){$exclude_clause}");
            while ($row = $cap_bk_stmt->fetch_assoc()) { $all_bookings_today[] = $row; }
        }

        // Vlastný rozvrh zamestnanca (pracovné hodiny + obedňajšia prestávka) — ak zamestnanec nemá
        // rozvrh vôbec nastavený, berie sa ako dostupný počas celých otváracích hodín prevádzky (default).
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
        $day_of_week_db = ((int)$date_obj->format('N')) - 1; // 0=Po ... 6=Ne, zhoduje sa s employee_schedules
        $sched_stmt = $conn->query("SELECT employee_id, start_time, end_time, is_off, break_start, break_end
            FROM employee_schedules WHERE employee_id IN ($in_candidates) AND day_of_week = $day_of_week_db");
        while ($row = $sched_stmt->fetch_assoc()) { $employee_schedule[(int)$row['employee_id']] = $row; }

        // Vygenerovanie kandidátnych časov po 15 minútach
        $step_minutes = 15;
        $now = new DateTime();
        $min_start = clone $open_dt;
        if ($date_obj->format('Y-m-d') === $now->format('Y-m-d')) {
            $lead = clone $now;
            $lead->modify('+30 minutes');
            if ($lead > $min_start) { $min_start = $lead; }
        }

        $available = [];
        $cursor = clone $open_dt;
        while ($cursor < $close_dt) {
            if ($cursor >= $min_start) {
                $slot_start = clone $cursor;
                $slot_end = (clone $slot_start)->modify('+' . $total_duration . ' minutes');

                if ($slot_end <= $close_dt) {
                    $blocked_start = (clone $slot_start)->modify('-' . $buffer_before . ' minutes');
                    $blocked_end = (clone $slot_end)->modify('+' . $buffer_after . ' minutes');

                    // Celoprevádzková kapacita (napr. počet kresiel) — ak je už v tomto čase obsadených
                    // toľko miest, koľko má prevádzka kapacitu, termín sa neponúka bez ohľadu na to,
                    // že konkrétny zamestnanec by inak bol voľný.
                    if ($capacity_per_slot !== null && $capacity_per_slot > 0) {
                        $concurrent = 0;
                        foreach ($all_bookings_today as $existing) {
                            $ex_start = (new DateTime($booking_date . ' ' . $existing['start_time']))->modify('-' . (int)$existing['buffer_before_minutes'] . ' minutes');
                            $ex_end = (new DateTime($booking_date . ' ' . $existing['end_time']))->modify('+' . (int)$existing['buffer_after_minutes'] . ' minutes');
                            if ($blocked_start < $ex_end && $blocked_end > $ex_start) { $concurrent++; }
                        }
                        if ($concurrent >= $capacity_per_slot) {
                            $cursor->modify('+' . $step_minutes . ' minutes');
                            continue;
                        }
                    }

                    foreach ($candidate_ids as $emp_id) {
                        if (!empty($unavailable_employees[$emp_id])) { continue; }

                        // Vlastný rozvrh zamestnanca (ak je nastavený) — musí byť voľno, termín sa musí zmestiť
                        // do jeho pracovných hodín a nesmie zasahovať do jeho obedňajšej prestávky
                        if (isset($employee_schedule[$emp_id])) {
                            $sched = $employee_schedule[$emp_id];
                            if ((int)$sched['is_off'] === 1) { continue; }
                            $emp_start = new DateTime($booking_date . ' ' . $sched['start_time']);
                            $emp_end = new DateTime($booking_date . ' ' . $sched['end_time']);
                            if ($slot_start < $emp_start || $slot_end > $emp_end) { continue; }
                            if (!empty($sched['break_start']) && !empty($sched['break_end'])) {
                                $break_start = new DateTime($booking_date . ' ' . $sched['break_start']);
                                $break_end = new DateTime($booking_date . ' ' . $sched['break_end']);
                                if ($slot_start < $break_end && $slot_end > $break_start) { continue; }
                            }
                        }

                        $conflicts = array_merge($bookings_by_employee[$emp_id] ?? [], $bookings_by_employee[0] ?? []);
                        $is_free = true;
                        $same_group_slot_count = 0;
                        foreach ($conflicts as $existing) {
                            // Skupinová služba: rezervácie na tú istú službu a presne ten istý čas sa navzájom neblokujú,
                            // len sa počítajú do kapacity — inak (iná služba / iný čas) blokujú ako doteraz
                            if ($group_service_id !== null && (int)$existing['service_id'] === $group_service_id && substr($existing['start_time'], 0, 5) === $slot_start->format('H:i')) {
                                $same_group_slot_count++;
                                continue;
                            }
                            $ex_start = (new DateTime($booking_date . ' ' . $existing['start_time']))->modify('-' . (int)$existing['buffer_before_minutes'] . ' minutes');
                            $ex_end = (new DateTime($booking_date . ' ' . $existing['end_time']))->modify('+' . (int)$existing['buffer_after_minutes'] . ' minutes');
                            if ($blocked_start < $ex_end && $blocked_end > $ex_start) { $is_free = false; break; }
                        }
                        if ($is_free && $group_service_id !== null && $same_group_slot_count >= $group_capacity) { $is_free = false; }

                        if ($is_free) {
                            $available[$slot_start->format('H:i')] = true;
                            break; // stačí jeden voľný kandidát na "Ktokoľvek"
                        }
                    }
                }
            }
            $cursor->modify('+' . $step_minutes . ' minutes');
        }

        return array_keys($available);
    }
}
