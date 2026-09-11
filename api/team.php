<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/employee_permissions_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$my_employee_id = isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0;

// Akcie, ktoré menia tím/nastavenia zamestnancov, smie robiť len majiteľ alebo zamestnanec
// s výslovne udeleným oprávnením 'settings' — inak by si mohol zamestnanec cez priame
// volanie API prideliť práva sám sebe (obídením kontroly na úrovni stránky).
$TEAM_MANAGEMENT_ACTIONS = ['add_employee', 'update_employee', 'delete_employee'];
if (in_array($action, $TEAM_MANAGEMENT_ACTIONS, true) && !employeeCan('settings')) {
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnenie na úpravu tímu.']);
    exit;
}

// Rozvrh a dovolenky: zamestnanec si vždy smie spravovať SVOJ VLASTNÝ záznam;
// cudzí záznam len s oprávnením 'settings'. (delete_employee_unavailability sa rieši
// nižšie priamo v handleri, keďže identifikuje záznam cez `id`, nie `employee_id`.)
$SCHEDULE_ACTIONS = ['get_employee_hours', 'save_employee_hours', 'get_employee_unavailabilities', 'add_employee_unavailability'];
if (in_array($action, $SCHEDULE_ACTIONS, true)) {
    $target_emp_id = intval($_POST['employee_id'] ?? 0);
    $is_self = $my_employee_id > 0 && $target_emp_id === $my_employee_id;
    if (!$is_self && !employeeCan('settings')) {
        echo json_encode(['success' => false, 'message' => 'Nemáte oprávnenie na úpravu rozvrhu iného zamestnanca.']);
        exit;
    }
}

// Helper to get subscription tier and limits
function getBusinessLimits($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT IFNULL(e.subscription_tier, IFNULL(u.subscription_tier, 'free')) as subscription_tier 
                           FROM users u 
                           LEFT JOIN establishments e ON u.id = e.user_id 
                           WHERE u.id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $tier = strtoupper($user['subscription_tier'] ?? 'FREE');

    $limits = [
        'FREE' => ['employees' => 1, 'gallery' => 0],
        'START' => ['employees' => 3, 'gallery' => 3],
        'PRO' => ['employees' => 12, 'gallery' => 12],
        'VIP' => ['employees' => 9999, 'gallery' => 9999]
    ];
    
    if (!isset($limits[$tier])) {
        $tier = 'FREE';
    }

    $employee_limit = $limits[$tier]['employees'];
    if ($employee_limit < 9999) {
        // Každý aktívny doplnok "Ďalší zamestnanec" (dashboard-balik.php) pridá +1 miesto nad rámec balíka
        try {
            $chk = $pdo->query("SHOW TABLES LIKE 'addon_orders'");
            if ($chk && $chk->rowCount() > 0) {
                $a = $pdo->prepare("SELECT COUNT(*) FROM addon_orders WHERE user_id = ? AND addon_key = 'employee' AND status = 'active'");
                $a->execute([$user_id]);
                $employee_limit += (int)$a->fetchColumn();
            }
        } catch (Exception $e) {}
    }
    $limits[$tier]['employees'] = $employee_limit;

    return [
        'tier' => $tier,
        'limits' => $limits[$tier]
    ];
}

// Helper to handle avatar upload
function handleAvatarUpload($file_input_name = 'avatar') {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$file_input_name];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $max_size) {
        throw new Exception('Obrázok je príliš veľký. Maximálna povolená veľkosť je 5 MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        throw new Exception('Nepodporovaný formát obrázka. Povolené sú JPG, PNG a WEBP.');
    }

    $upload_dir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($ext)) $ext = 'jpg';
    $new_filename = 'emp_' . uniqid() . '_' . time() . '.' . strtolower($ext);
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return 'uploads/avatars/' . $new_filename;
    } else {
        throw new Exception('Chyba pri ukladaní fotografie.');
    }
}

// Self-migrácia: farebný štítok zamestnanca (na rozlíšenie v Kalendári)
try { $pdo->exec("ALTER TABLE employees ADD COLUMN color VARCHAR(9) DEFAULT NULL AFTER avatar_url"); } catch (Exception $e) {}

// Self-migrácia: zamestnanecké oprávnenia (Fáza 4) — kto vidí tržby / CRM / môže upravovať nastavenia
try { $pdo->exec("ALTER TABLE employees ADD COLUMN can_view_revenue TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE employees ADD COLUMN can_view_crm TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE employees ADD COLUMN can_edit_settings TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

$EMPLOYEE_COLORS = ['#ef4444','#f97316','#f59e0b','#10b981','#06b6d4','#3b82f6','#8b5cf6','#ec4899','#64748b'];
function sanitize_employee_color($val, $allowed) {
    $val = trim((string)$val);
    return in_array($val, $allowed, true) ? $val : null;
}

try {
    if ($action === 'get_team') {
        // 1. Získame dáta o majiteľovi a prevádzke
        $stmt_user = $pdo->prepare("SELECT id, full_name, email, avatar_path FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        $stmt_est = $pdo->prepare("SELECT id, name, owner_name, image_url FROM establishments WHERE user_id = ? LIMIT 1");
        $stmt_est->execute([$user_id]);
        $est = $stmt_est->fetch(PDO::FETCH_ASSOC);
        $est_id = $est ? (int)$est['id'] : 0;

        // 2. Skontrolujeme, či má majiteľ záznam v tabuľke employees s is_owner = 1
        $stmt_owner_chk = $pdo->prepare("SELECT * FROM employees WHERE business_id = ? AND is_owner = 1 LIMIT 1");
        $stmt_owner_chk->execute([$user_id]);
        $owner_emp = $stmt_owner_chk->fetch(PDO::FETCH_ASSOC);

        if (!$owner_emp) {
            // Vytvoríme záznam majiteľa
            $owner_name = !empty($user['full_name']) ? $user['full_name'] : (!empty($est['owner_name']) ? $est['owner_name'] : ($est['name'] ?? 'Majiteľ'));
            $owner_avatar = !empty($user['avatar_path']) ? $user['avatar_path'] : '';
            $owner_email = $user['email'] ?? '';

            $stmt_ins = $pdo->prepare("INSERT INTO employees (business_id, establishment_id, name, title, email, phone, avatar_url, is_active, is_owner, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, 0)");
            $stmt_ins->execute([$user_id, $est_id, $owner_name, 'Majiteľ / Vlastník', $owner_email, '', $owner_avatar]);
        } else {
            // Ak majiteľ má avatar v users, ale nemá v employees (alebo naopak), zosynchronizujeme
            if (empty($owner_emp['avatar_url']) && !empty($user['avatar_path'])) {
                $pdo->prepare("UPDATE employees SET avatar_url = ? WHERE id = ?")->execute([$user['avatar_path'], $owner_emp['id']]);
            }
        }

        // 3. Načítame všetkých členov tímu (majiteľ bude prvý vďaka is_owner DESC, order_index ASC)
        $stmt = $pdo->prepare("SELECT id, business_id, establishment_id, name, title, email, phone, avatar_url, color, is_active, is_owner, order_index, created_at, can_view_revenue, can_view_crm, can_edit_settings FROM employees WHERE business_id = ? ORDER BY is_owner DESC, order_index ASC, id ASC");
        $stmt->execute([$user_id]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tier_info = getBusinessLimits($pdo, $user_id);
        $max_limit = $tier_info['limits']['employees'];

        echo json_encode([
            'success' => true,
            'team' => $employees,
            'tier' => $tier_info['tier'],
            'count' => count($employees),
            'limit' => $max_limit
        ], JSON_UNESCAPED_UNICODE);
    }
    elseif ($action === 'add_employee') {
        $name = trim($_POST['name'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $color = sanitize_employee_color($_POST['color'] ?? '', $EMPLOYEE_COLORS);
        $can_view_revenue = isset($_POST['can_view_revenue']) ? 1 : 0;
        $can_view_crm = isset($_POST['can_view_crm']) ? 1 : 0;
        $can_edit_settings = isset($_POST['can_edit_settings']) ? 1 : 0;

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Meno zamestnanca je povinné.']);
            exit;
        }

        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Prihlasovací e-mail zamestnanca je povinný.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Zadajte platnú e-mailovú adresu.']);
            exit;
        }

        if (empty($password) || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Heslo musí mať aspoň 6 znakov.']);
            exit;
        }

        // Kontrola limitov balíka
        $tier_info = getBusinessLimits($pdo, $user_id);
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE business_id = ?");
        $stmt_count->execute([$user_id]);
        $current_count = (int)$stmt_count->fetchColumn();

        if ($current_count >= $tier_info['limits']['employees']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Dosiahli ste maximálny počet členov tímu pre Váš balík (' . $tier_info['limits']['employees'] . '). Pre pridanie ďalších zamestnancov si navýšte balík.',
                'limit_reached' => true
            ]);
            exit;
        }

        // Kontrola duplicity e-mailu v tabuľke employees a users
        $stmt_chk = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt_chk->execute([$email]);
        if ($stmt_chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Tento prihlasovací e-mail už používa iný zamestnanec.']);
            exit;
        }

        $stmt_chk_user = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_chk_user->execute([$email]);
        if ($stmt_chk_user->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Tento e-mail je už registrovaný ako hlavný používateľský účet. Použite iný e-mail pre zamestnanca.']);
            exit;
        }

        // Upload avatara
        $avatar_url = '';
        try {
            $uploaded = handleAvatarUpload('avatar');
            if ($uploaded) {
                $avatar_url = $uploaded;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        // Est id
        $stmt_est = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ? LIMIT 1");
        $stmt_est->execute([$user_id]);
        $est_id = (int)$stmt_est->fetchColumn();

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt_ins = $pdo->prepare("
            INSERT INTO employees (business_id, establishment_id, name, title, email, password_hash, phone, avatar_url, color, is_active, is_owner, order_index, can_view_revenue, can_view_crm, can_edit_settings)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 99, ?, ?, ?)
        ");
        $stmt_ins->execute([$user_id, $est_id, $name, $title, $email, $password_hash, $phone, $avatar_url, $color, $is_active, $can_view_revenue, $can_view_crm, $can_edit_settings]);

        echo json_encode(['success' => true, 'message' => 'Zamestnanec bol úspešne pridaný.', 'id' => (int)$pdo->lastInsertId()]);
    }
    elseif ($action === 'update_employee') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $color = sanitize_employee_color($_POST['color'] ?? '', $EMPLOYEE_COLORS);
        $can_view_revenue = isset($_POST['can_view_revenue']) ? 1 : 0;
        $can_view_crm = isset($_POST['can_view_crm']) ? 1 : 0;
        $can_edit_settings = isset($_POST['can_edit_settings']) ? 1 : 0;

        if (!$id || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Neplatné údaje alebo chýba meno.']);
            exit;
        }

        // Skontrolujeme zamestnanca
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $user_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            echo json_encode(['success' => false, 'message' => 'Zamestnanec nenájdený.']);
            exit;
        }

        $avatar_url = $emp['avatar_url'];
        try {
            $uploaded = handleAvatarUpload('avatar');
            if ($uploaded) {
                $avatar_url = $uploaded;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        // Ak ide o MAJITEĽA
        if (!empty($emp['is_owner'])) {
            $stmt_upd = $pdo->prepare("
                UPDATE employees
                SET name = ?, title = ?, phone = ?, avatar_url = ?, color = ?
                WHERE id = ? AND business_id = ?
            ");
            $stmt_upd->execute([$name, $title, $phone, $avatar_url, $color, $id, $user_id]);

            // Synchronizujeme aj do profilu majiteľa a prevádzky
            if (!empty($avatar_url)) {
                $pdo->prepare("UPDATE users SET avatar_path = ?, full_name = ? WHERE id = ?")->execute([$avatar_url, $name, $user_id]);
                $pdo->prepare("UPDATE establishments SET owner_name = ? WHERE user_id = ?")->execute([$name, $user_id]);
                $_SESSION['user_avatar'] = $avatar_url;
            } else {
                $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?")->execute([$name, $user_id]);
                $pdo->prepare("UPDATE establishments SET owner_name = ? WHERE user_id = ?")->execute([$name, $user_id]);
            }
            $_SESSION['user_name'] = $name;

            echo json_encode(['success' => true, 'message' => 'Profil majiteľa bol úspešne aktualizovaný.']);
            exit;
        }

        // Ak ide o bežného ZAMESTNANCA
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Zadajte platný e-mail zamestnanca.']);
            exit;
        }

        // Kontrola duplicity emailu
        $stmt_chk = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $stmt_chk->execute([$email, $id]);
        if ($stmt_chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Tento e-mail už používa iný zamestnanec.']);
            exit;
        }

        // Zmena hesla ak bolo zadané
        if (!empty($password)) {
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Nové heslo musí mať aspoň 6 znakov.']);
                exit;
            }
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_upd = $pdo->prepare("
                UPDATE employees
                SET name = ?, title = ?, email = ?, password_hash = ?, phone = ?, avatar_url = ?, color = ?, is_active = ?, can_view_revenue = ?, can_view_crm = ?, can_edit_settings = ?
                WHERE id = ? AND business_id = ?
            ");
            $stmt_upd->execute([$name, $title, $email, $password_hash, $phone, $avatar_url, $color, $is_active, $can_view_revenue, $can_view_crm, $can_edit_settings, $id, $user_id]);
        } else {
            $stmt_upd = $pdo->prepare("
                UPDATE employees
                SET name = ?, title = ?, email = ?, phone = ?, avatar_url = ?, color = ?, is_active = ?, can_view_revenue = ?, can_view_crm = ?, can_edit_settings = ?
                WHERE id = ? AND business_id = ?
            ");
            $stmt_upd->execute([$name, $title, $email, $phone, $avatar_url, $color, $is_active, $can_view_revenue, $can_view_crm, $can_edit_settings, $id, $user_id]);
        }

        echo json_encode(['success' => true, 'message' => 'Údaje zamestnanca boli úspešne uložené.']);
    }
    elseif ($action === 'delete_employee') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $user_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            echo json_encode(['success' => false, 'message' => 'Zamestnanec nenájdený.']);
            exit;
        }

        if (!empty($emp['is_owner'])) {
            echo json_encode(['success' => false, 'message' => 'Hlavný profil majiteľa nie je možné vymazať.']);
            exit;
        }

        $stmt_del = $pdo->prepare("DELETE FROM employees WHERE id = ? AND business_id = ?");
        $stmt_del->execute([$id, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Zamestnanec bol úspešne odstránený.']);
    }
    // === PRACOVNÉ HODINY ===
    elseif ($action === 'get_employee_hours') {
        $emp_id = intval($_POST['employee_id'] ?? 0);
        $own_stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND business_id = ?");
        $own_stmt->execute([$emp_id, $user_id]);
        if (!$own_stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Zamestnanec nenájdený.']); return; }
        $pdo->exec("CREATE TABLE IF NOT EXISTS `employee_schedules` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `day_of_week` tinyint(1) NOT NULL COMMENT '0=Po,1=Ut,2=St,3=Št,4=Pi,5=So,6=Ne',
            `start_time` time DEFAULT '09:00:00',
            `end_time` time DEFAULT '17:00:00',
            `is_off` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_day` (`employee_id`,`day_of_week`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        try {
            $pdo->exec("ALTER TABLE employee_schedules ADD COLUMN IF NOT EXISTS break_start TIME DEFAULT NULL");
            $pdo->exec("ALTER TABLE employee_schedules ADD COLUMN IF NOT EXISTS break_end TIME DEFAULT NULL");
        } catch (Exception $e) {}
        $stmt = $pdo->prepare("SELECT day_of_week, start_time, end_time, is_off, break_start, break_end FROM employee_schedules WHERE employee_id = ? ORDER BY day_of_week ASC");
        $stmt->execute([$emp_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Fill missing days with defaults
        $schedule = [];
        $existing = [];
        foreach ($rows as $r) { $existing[$r['day_of_week']] = $r; }
        for ($d = 0; $d <= 6; $d++) {
            $schedule[] = $existing[$d] ?? ['day_of_week' => $d, 'start_time' => '09:00', 'end_time' => '17:00', 'is_off' => ($d >= 5 ? 1 : 0), 'break_start' => null, 'break_end' => null];
        }
        echo json_encode(['success' => true, 'schedule' => $schedule]);
    }

    elseif ($action === 'save_employee_hours') {
        $emp_id = intval($_POST['employee_id'] ?? 0);
        $days = json_decode($_POST['days'] ?? '[]', true);
        if (!$emp_id || !is_array($days)) { echo json_encode(['success' => false, 'message' => 'Neplatné dáta']); return; }
        $own_stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND business_id = ?");
        $own_stmt->execute([$emp_id, $user_id]);
        if (!$own_stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Zamestnanec nenájdený.']); return; }
        $pdo->exec("CREATE TABLE IF NOT EXISTS `employee_schedules` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `day_of_week` tinyint(1) NOT NULL,
            `start_time` time DEFAULT '09:00:00',
            `end_time` time DEFAULT '17:00:00',
            `is_off` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_day` (`employee_id`,`day_of_week`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        try {
            $pdo->exec("ALTER TABLE employee_schedules ADD COLUMN IF NOT EXISTS break_start TIME DEFAULT NULL");
            $pdo->exec("ALTER TABLE employee_schedules ADD COLUMN IF NOT EXISTS break_end TIME DEFAULT NULL");
        } catch (Exception $e) {}
        $stmt = $pdo->prepare("INSERT INTO employee_schedules (employee_id, day_of_week, start_time, end_time, is_off, break_start, break_end) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE start_time=VALUES(start_time), end_time=VALUES(end_time), is_off=VALUES(is_off), break_start=VALUES(break_start), break_end=VALUES(break_end)");
        foreach ($days as $d) {
            $hasBreak = !empty($d['break']);
            $stmt->execute([
                $emp_id, intval($d['day']), $d['start'] ?? '09:00', $d['end'] ?? '17:00', intval($d['off'] ?? 0),
                $hasBreak ? ($d['break_start'] ?? null) : null,
                $hasBreak ? ($d['break_end'] ?? null) : null
            ]);
        }
        // Voliteľné upozornenie (neblokuje uloženie) — ak má prevádzka nastavenú kapacitu (koľko klientov
        // naraz dokáže obslúžiť) a v niektorý deň má naplánovaných viac súbežne pracujúcich zamestnancov,
        // než je táto kapacita, na to nemusí prísť, kým to nespôsobí reálny problém pri rezervovaní.
        $capacity_warning = null;
        $est_stmt = $pdo->prepare("SELECT id, capacity_per_slot FROM establishments WHERE user_id = ?");
        $est_stmt->execute([$user_id]);
        $est_row = $est_stmt->fetch(PDO::FETCH_ASSOC);
        if ($est_row && !empty($est_row['capacity_per_slot'])) {
            $capacity = (int)$est_row['capacity_per_slot'];
            $day_numbers = array_unique(array_map(fn($d) => (int)($d['day'] ?? 0), $days));
            $sched_stmt = $pdo->prepare("SELECT es.day_of_week, es.start_time, es.end_time, es.is_off, es.break_start, es.break_end
                                          FROM employee_schedules es
                                          JOIN employees e ON e.id = es.employee_id
                                          WHERE e.establishment_id = ?");
            $sched_stmt->execute([(int)$est_row['id']]);
            $schedules_by_day = [];
            foreach ($sched_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $schedules_by_day[(int)$row['day_of_week']][] = $row;
            }
            $day_names = ['Pondelok','Utorok','Streda','Štvrtok','Piatok','Sobota','Nedeľa'];
            $overloaded_days = [];
            foreach ($day_numbers as $dnum) {
                $events = [];
                foreach (($schedules_by_day[$dnum] ?? []) as $r) {
                    if (!empty($r['is_off'])) continue;
                    $events[] = [$r['start_time'], 1];
                    $events[] = [$r['end_time'], -1];
                    if (!empty($r['break_start']) && !empty($r['break_end'])) {
                        $events[] = [$r['break_start'], -1];
                        $events[] = [$r['break_end'], 1];
                    }
                }
                usort($events, fn($a, $b) => strcmp($a[0], $b[0]));
                $running = 0; $peak = 0;
                foreach ($events as $ev) { $running += $ev[1]; $peak = max($peak, $running); }
                if ($peak > $capacity) { $overloaded_days[] = $day_names[$dnum] ?? "deň $dnum"; }
            }
            if (!empty($overloaded_days)) {
                $capacity_warning = 'Pozor: v dňoch ' . implode(', ', $overloaded_days) . ' máte naplánovaných viac súbežne pracujúcich zamestnancov, než je nastavená kapacita prevádzky (' . $capacity . '). Skontrolujte rozvrh, alebo si kapacitu upravte v "Moja Prevádzka".';
            }
        }

        echo json_encode(['success' => true, 'message' => 'Pracovné hodiny uložené.', 'capacity_warning' => $capacity_warning]);
    }

    // === DOVOLENKY / PN ===
    elseif ($action === 'get_employee_unavailabilities') {
        $emp_id = intval($_POST['employee_id'] ?? 0);
        $own_stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND business_id = ?");
        $own_stmt->execute([$emp_id, $user_id]);
        if (!$own_stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Zamestnanec nenájdený.']); return; }
        $pdo->exec("CREATE TABLE IF NOT EXISTS `employee_unavailabilities` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `type` enum('vacation','pn','other') NOT NULL DEFAULT 'vacation',
            `date_from` date NOT NULL,
            `date_to` date NOT NULL,
            `note` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $stmt = $pdo->prepare("SELECT * FROM employee_unavailabilities WHERE employee_id = ? ORDER BY date_from DESC");
        $stmt->execute([$emp_id]);
        echo json_encode(['success' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'add_employee_unavailability') {
        $emp_id = intval($_POST['employee_id'] ?? 0);
        $type = in_array($_POST['type'] ?? '', ['vacation','pn','other']) ? $_POST['type'] : 'vacation';
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $note = trim($_POST['note'] ?? '');
        if (!$emp_id || !$date_from || !$date_to) { echo json_encode(['success' => false, 'message' => 'Vyplňte dátumy.']); return; }
        if ($date_to < $date_from) { echo json_encode(['success' => false, 'message' => 'Dátum konca musí byť po dátume začiatku.']); return; }
        $own_stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND business_id = ?");
        $own_stmt->execute([$emp_id, $user_id]);
        if (!$own_stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Zamestnanec nenájdený.']); return; }
        $pdo->exec("CREATE TABLE IF NOT EXISTS `employee_unavailabilities` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `type` enum('vacation','pn','other') NOT NULL DEFAULT 'vacation',
            `date_from` date NOT NULL,
            `date_to` date NOT NULL,
            `note` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $stmt = $pdo->prepare("INSERT INTO employee_unavailabilities (employee_id, type, date_from, date_to, note) VALUES (?,?,?,?,?)");
        $stmt->execute([$emp_id, $type, $date_from, $date_to, $note]);

        // Ak nové obdobie (napr. spätne nahlásená PN) zasahuje existujúce rezervácie, upozorniť dotknutých zákazníkov,
        // aby si mohli vybrať iný termín (napr. u kolegu) alebo zrušiť.
        $affected_count = 0;
        $affected_stmt = $conn->prepare("SELECT b.id, b.manage_token, b.booking_date, b.start_time, u.email, u.full_name,
                                                 s.name as service_name, e.name as establishment_name
                                          FROM bookings b
                                          JOIN users u ON u.id = b.customer_id
                                          LEFT JOIN services s ON s.id = b.service_id
                                          LEFT JOIN establishments e ON e.id = b.establishment_id
                                          WHERE b.employee_id = ? AND b.booking_date BETWEEN ? AND ? AND b.status NOT IN ('cancelled','completed')");
        $affected_stmt->bind_param("iss", $emp_id, $date_from, $date_to);
        $affected_stmt->execute();
        $affected = $affected_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (!empty($affected)) {
            @include_once __DIR__ . '/mailer.php';
            if (function_exists('sendUnavailabilityRescheduleNotification')) {
                $root_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'rezervos.eu');
                foreach ($affected as $bk) {
                    if (empty($bk['manage_token'])) { continue; } // staršie rezervácie bez tokenu (pred zavedením tejto funkcie)
                    $manage_url = $root_url . '/sprava-rezervacie.php?token=' . $bk['manage_token'];
                    @sendUnavailabilityRescheduleNotification(
                        $bk['email'], $bk['full_name'], $bk['establishment_name'] ?: 'prevádzka',
                        $bk['service_name'] ?: 'Rezervovaná služba', $bk['booking_date'], substr($bk['start_time'], 0, 5), $manage_url
                    );
                    $affected_count++;
                }
            }
        }

        $msg = 'Blok bol pridaný.';
        if ($affected_count > 0) {
            $msg .= " Upozornili sme $affected_count zákazníka(ov) s existujúcou rezerváciou v tomto období, aby si vybrali iný termín.";
        }
        echo json_encode(['success' => true, 'message' => $msg, 'affected_bookings' => $affected_count]);
    }

    elseif ($action === 'delete_employee_unavailability') {
        $id = intval($_POST['id'] ?? 0);
        $own_stmt = $pdo->prepare("SELECT eu.id, eu.employee_id FROM employee_unavailabilities eu JOIN employees e ON e.id = eu.employee_id WHERE eu.id = ? AND e.business_id = ?");
        $own_stmt->execute([$id, $user_id]);
        $row = $own_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success' => false, 'message' => 'Záznam nenájdený.']); return; }
        $is_self = $my_employee_id > 0 && (int)$row['employee_id'] === $my_employee_id;
        if (!$is_self && !employeeCan('settings')) {
            echo json_encode(['success' => false, 'message' => 'Nemáte oprávnenie na úpravu rozvrhu iného zamestnanca.']);
            return;
        }
        $stmt = $pdo->prepare("DELETE FROM employee_unavailabilities WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Chyba servera: ' . $e->getMessage()]);
}
