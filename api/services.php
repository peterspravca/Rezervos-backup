<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/employee_permissions_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Úprava cenníka (kategórie, služby, priradenie zamestnancov) vyžaduje oprávnenie 'settings' —
// zhodné s gateom na dashboard-cennik.php, aby to nešlo obísť priamym volaním API.
$CENNIK_WRITE_ACTIONS = ['add_category', 'delete_category', 'add_service', 'delete_service', 'edit_service', 'update_service', 'edit_category', 'update_category', 'save_employee_service', 'remove_employee_service'];
if (in_array($action, $CENNIK_WRITE_ACTIONS, true) && !employeeCan('settings')) {
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnenie na úpravu cenníka.']);
    exit;
}

try {
    // Resolve establishment_id for this business user
    $est_stmt = $pdo->prepare("SELECT id FROM establishments WHERE user_id = ? LIMIT 1");
    $est_stmt->execute([$user_id]);
    $est_id = (int)$est_stmt->fetchColumn() ?: 0;

    // Self-migrácia: buffer pred/po službe (čas na prípravu/očistenie), chýbalo v pôvodnej schéme
    try {
        $pdo->exec("ALTER TABLE services ADD COLUMN IF NOT EXISTS buffer_before_minutes INT NOT NULL DEFAULT 0");
        $pdo->exec("ALTER TABLE services ADD COLUMN IF NOT EXISTS buffer_after_minutes INT NOT NULL DEFAULT 0");
        // Kapacita — pre skupinové služby (napr. kurz jogy), koľko klientov sa zmestí na ten istý termín naraz
        $pdo->exec("ALTER TABLE services ADD COLUMN IF NOT EXISTS capacity INT NOT NULL DEFAULT 1");
    } catch (Exception $e) { /* stĺpce už existujú */ }

    if ($action === 'get_services') {
        // ── DB CLEANUP: odstrán duplikátne riadky (rovnaký name+price pre toho istého business) ──
        try {
            $dup_stmt = $pdo->prepare("
                SELECT s1.id FROM services s1
                JOIN services s2
                    ON s1.business_id = s2.business_id
                    AND LOWER(TRIM(s1.name)) = LOWER(TRIM(s2.name))
                    AND ABS(COALESCE(s1.price, 0) - COALESCE(s2.price, 0)) < 0.01
                    AND s1.id < s2.id
                WHERE s1.business_id = ?
            ");
            $dup_stmt->execute([$user_id]);
            $dup_ids = array_column($dup_stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
            if ($dup_ids) {
                $in = implode(',', array_map('intval', $dup_ids));
                $pdo->exec("DELETE FROM employee_services WHERE service_id IN ($in)");
                $pdo->exec("DELETE FROM services WHERE id IN ($in)");
            }
        } catch (Exception $e) { /* tabuľka alebo stĺpec neexistuje – preskočiť */ }

        // Fetch categories
        $stmt = $pdo->prepare("SELECT * FROM service_categories WHERE business_id = ? ORDER BY order_index ASC, id ASC");
        $stmt->execute([$user_id]);
        $categories = $stmt->fetchAll();

        // Fetch services — establishment_id má prednosť pred business_id
        $stmt = $pdo->prepare("SELECT * FROM services WHERE (business_id = ? OR (establishment_id > 0 AND establishment_id = ?)) ORDER BY establishment_id DESC, category_id ASC, order_index ASC, id ASC");
        $stmt->execute([$user_id, $est_id]);
        $raw = $stmt->fetchAll();
        // Záloha PHP dedup pre prípad že DB cleanup nestihol
        $seen     = [];
        $services = [];
        foreach ($raw as $s) {
            $key = strtolower(trim($s['name'])) . '|' . (float)($s['price'] ?? 0);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $services[] = $s;
            }
        }

        // Fetch employee_services links
        $stmt = $pdo->prepare("
            SELECT es.*, e.name as employee_name, e.avatar_url as employee_avatar, e.title as employee_title, e.is_owner as employee_is_owner
            FROM employee_services es 
            JOIN employees e ON es.employee_id = e.id 
            WHERE e.business_id = ? OR e.establishment_id = ?
        ");
        $stmt->execute([$user_id, $est_id]);
        $employee_services = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'categories' => $categories,
            'services' => $services,
            'employee_services' => $employee_services
        ]);
    }
    elseif ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Názov kategórie je povinný.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO service_categories (business_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
        
        echo json_encode(['success' => true, 'message' => 'Kategória pridaná.', 'id' => $pdo->lastInsertId()]);
    }
    elseif ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM service_categories WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Kategória odstránená.']);
    }
    elseif ($action === 'add_service') {
        $category_id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 15.00;
        $duration = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 30;
        $buffer_before = isset($_POST['buffer_before_minutes']) ? max(0, (int)$_POST['buffer_before_minutes']) : 0;
        $buffer_after = isset($_POST['buffer_after_minutes']) ? max(0, (int)$_POST['buffer_after_minutes']) : 0;
        $capacity = isset($_POST['capacity']) ? max(1, (int)$_POST['capacity']) : 1;
        $employee_ids = isset($_POST['employee_ids']) && is_array($_POST['employee_ids']) ? array_map('intval', $_POST['employee_ids']) : [];

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Názov služby je povinný.']);
            exit;
        }

        // ── OCHRANA PRED DUPLIKÁTMI: ak existuje rovnaká služba, neprida ju znova ──
        $dup_check = $pdo->prepare("
            SELECT id FROM services
            WHERE business_id = ?
              AND LOWER(TRIM(name)) = LOWER(TRIM(?))
              AND ABS(COALESCE(price, 0) - ?) < 0.01
            LIMIT 1
        ");
        $dup_check->execute([$user_id, $name, $price]);
        if ($dup_check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Služba s rovnakým názvom a cenou už v cenníku existuje.']);
            exit;
        }

        // If category_id is 0 or empty, create default or pick first category
        if ($category_id === 0) {
            $cat_stmt = $pdo->prepare("SELECT id FROM service_categories WHERE business_id = ? ORDER BY id ASC LIMIT 1");
            $cat_stmt->execute([$user_id]);
            $cat_row = $cat_stmt->fetch();
            if ($cat_row) {
                $category_id = (int)$cat_row['id'];
            } else {
                $cat_ins = $pdo->prepare("INSERT INTO service_categories (business_id, name) VALUES (?, 'Všeobecné služby')");
                $cat_ins->execute([$user_id]);
                $category_id = (int)$pdo->lastInsertId();
            }
        }

        $stmt = $pdo->prepare("INSERT INTO services (establishment_id, business_id, category_id, name, description, price, duration_minutes, buffer_before_minutes, buffer_after_minutes, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$est_id, $user_id, $category_id, $name, $description, $price, $duration, $buffer_before, $buffer_after, $capacity]);
        $service_id = (int)$pdo->lastInsertId();

        // Auto-assign employee with price and duration
        $emp_stmt = $pdo->prepare("SELECT id FROM employees WHERE (business_id = ? OR establishment_id = ?) AND is_active = 1 ORDER BY is_owner DESC, id ASC");
        $emp_stmt->execute([$user_id, $est_id]);
        $employees = $emp_stmt->fetchAll();
        
        if (empty($employees)) {
            // Create owner employee if not exists
            $u_stmt = $pdo->prepare("SELECT full_name, avatar_path FROM users WHERE id = ?");
            $u_stmt->execute([$user_id]);
            $u_data = $u_stmt->fetch();
            $owner_name = !empty($u_data['full_name']) ? $u_data['full_name'] : 'Majiteľ';
            $owner_avatar = !empty($u_data['avatar_path']) ? $u_data['avatar_path'] : '';
            
            $e_ins = $pdo->prepare("INSERT INTO employees (business_id, establishment_id, name, title, avatar_url, is_active, is_owner) VALUES (?, ?, ?, 'Majiteľ / Ja', ?, 1, 1)");
            $e_ins->execute([$user_id, $est_id, $owner_name, $owner_avatar]);
            $employee_id = (int)$pdo->lastInsertId();
            $employees = [['id' => $employee_id]];
        }
        
        $assign_stmt = $pdo->prepare("INSERT INTO employee_services (employee_id, service_id, price, duration_minutes) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), duration_minutes = VALUES(duration_minutes)");
        if (!isset($_POST['employee_ids_submitted'])) {
            // Formulár vôbec neposlal výber pracovníkov (starší/iný volajúci) — zachovávame
            // pôvodné správanie a priradíme všetkým aktívnym pracovníkom.
            foreach ($employees as $emp) {
                $assign_stmt->execute([(int)$emp['id'], $service_id, $price, $duration]);
            }
        } elseif (!empty($employee_ids)) {
            // Priradiť len skutočne zaškrtnutých pracovníkov (overených, že patria tejto prevádzke)
            $valid_stmt = $pdo->prepare("SELECT id FROM employees WHERE id IN (" . implode(',', array_fill(0, count($employee_ids), '?')) . ") AND (business_id = ? OR establishment_id = ?)");
            $valid_stmt->execute(array_merge($employee_ids, [$user_id, $est_id]));
            foreach ($valid_stmt->fetchAll(PDO::FETCH_COLUMN) as $eid) {
                $assign_stmt->execute([(int)$eid, $service_id, $price, $duration]);
            }
        }
        // Inak (marker poslaný, ale nič nezaškrtnuté) necháme službu úmyselne bez priradeného
        // pracovníka — nie tichým priradením všetkým, ako to bolo predtým.
        
        echo json_encode(['success' => true, 'message' => 'Služba s cenníkom bola úspešne pridaná.', 'id' => $service_id]);
    }
    elseif ($action === 'delete_service') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND (business_id = ? OR establishment_id = ?)");
        $stmt->execute([$id, $user_id, $est_id]);
        
        echo json_encode(['success' => true, 'message' => 'Služba odstránená.']);
    }
    elseif ($action === 'edit_service' || $action === 'update_service') {
        $id = (int)($_POST['id'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) ? (float)$_POST['price'] : null;
        $duration = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null;
        $buffer_before = isset($_POST['buffer_before_minutes']) ? max(0, (int)$_POST['buffer_before_minutes']) : null;
        $buffer_after = isset($_POST['buffer_after_minutes']) ? max(0, (int)$_POST['buffer_after_minutes']) : null;
        $capacity = isset($_POST['capacity']) ? max(1, (int)$_POST['capacity']) : null;

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Názov služby je povinný.']);
            exit;
        }

        // Verify service ownership
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND (business_id = ? OR establishment_id = ?)");
        $stmt->execute([$id, $user_id, $est_id]);
        $srv = $stmt->fetch();
        if (!$srv) {
            echo json_encode(['success' => false, 'message' => 'Služba nebola nájdená.']);
            exit;
        }

        $buffer_before = $buffer_before ?? (int)($srv['buffer_before_minutes'] ?? 0);
        $buffer_after = $buffer_after ?? (int)($srv['buffer_after_minutes'] ?? 0);
        $capacity = $capacity ?? (int)($srv['capacity'] ?? 1);

        if ($category_id > 0) {
            $upd = $pdo->prepare("UPDATE services SET name = ?, description = ?, category_id = ?, price = ?, duration_minutes = ?, buffer_before_minutes = ?, buffer_after_minutes = ?, capacity = ? WHERE id = ?");
            $upd->execute([$name, $description, $category_id, $price ?? $srv['price'], $duration ?? $srv['duration_minutes'], $buffer_before, $buffer_after, $capacity, $id]);
        } else {
            $upd = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration_minutes = ?, buffer_before_minutes = ?, buffer_after_minutes = ?, capacity = ? WHERE id = ?");
            $upd->execute([$name, $description, $price ?? $srv['price'], $duration ?? $srv['duration_minutes'], $buffer_before, $buffer_after, $capacity, $id]);
        }

        $final_price = $price ?? $srv['price'];
        $final_duration = $duration ?? $srv['duration_minutes'];

        // Also update existing employee_services links for this service
        if ($price !== null && $duration !== null) {
            $upd_es = $pdo->prepare("UPDATE employee_services SET price = ?, duration_minutes = ? WHERE service_id = ?");
            $upd_es->execute([$price, $duration, $id]);
        }

        // Ak formulár posiela zoznam zaškrtnutých pracovníkov ("Vykonáva pracovník"), nastavíme
        // priradenie presne podľa neho (odškrtnutých odstránime, zaškrtnutých priradíme/aktualizujeme) —
        // predtým sa tento výber pri úprave služby ticho ignoroval.
        if (isset($_POST['employee_ids_submitted'])) {
            $employee_ids = isset($_POST['employee_ids']) && is_array($_POST['employee_ids']) ? array_map('intval', $_POST['employee_ids']) : [];

            $valid_ids = [];
            if (!empty($employee_ids)) {
                $valid_stmt = $pdo->prepare("SELECT id FROM employees WHERE id IN (" . implode(',', array_fill(0, count($employee_ids), '?')) . ") AND (business_id = ? OR establishment_id = ?)");
                $valid_stmt->execute(array_merge($employee_ids, [$user_id, $est_id]));
                $valid_ids = array_map('intval', $valid_stmt->fetchAll(PDO::FETCH_COLUMN));
            }

            if (!empty($valid_ids)) {
                $del = $pdo->prepare("DELETE FROM employee_services WHERE service_id = ? AND employee_id NOT IN (" . implode(',', array_fill(0, count($valid_ids), '?')) . ")");
                $del->execute(array_merge([$id], $valid_ids));
            } else {
                $pdo->prepare("DELETE FROM employee_services WHERE service_id = ?")->execute([$id]);
            }

            $assign = $pdo->prepare("
                INSERT INTO employee_services (employee_id, service_id, price, duration_minutes)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE price = VALUES(price), duration_minutes = VALUES(duration_minutes)
            ");
            foreach ($valid_ids as $eid) {
                $assign->execute([$eid, $id, $final_price, $final_duration]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Služba bola úspešne upravená.']);
    }
    elseif ($action === 'edit_category' || $action === 'update_category') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Názov kategórie je povinný.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE service_categories SET name = ? WHERE id = ? AND business_id = ?");
        $stmt->execute([$name, $id, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Kategória bola úspešne upravená.']);
    }
    elseif ($action === 'save_employee_service') {
        $service_id = (int)($_POST['service_id'] ?? 0);
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $duration = (int)($_POST['duration_minutes'] ?? 30);
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ? AND (business_id = ? OR establishment_id = ?)");
        $stmt->execute([$service_id, $user_id, $est_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Služba nenájdená.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND (business_id = ? OR establishment_id = ?)");
        $stmt->execute([$employee_id, $user_id, $est_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Zamestnanec nenájdený.']);
            exit;
        }

        // Upsert
        $stmt = $pdo->prepare("
            INSERT INTO employee_services (employee_id, service_id, price, duration_minutes) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE price = VALUES(price), duration_minutes = VALUES(duration_minutes)
        ");
        $stmt->execute([$employee_id, $service_id, $price, $duration]);
        
        echo json_encode(['success' => true, 'message' => 'Priradenie uložené.']);
    }
    elseif ($action === 'remove_employee_service') {
        $service_id = (int)($_POST['service_id'] ?? 0);
        $employee_id = (int)($_POST['employee_id'] ?? 0);

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ? AND (business_id = ? OR establishment_id = ?)");
        $stmt->execute([$service_id, $user_id, $est_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Oprávnenie odmietnuté.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM employee_services WHERE employee_id = ? AND service_id = ?");
        $stmt->execute([$employee_id, $service_id]);
        
        echo json_encode(['success' => true, 'message' => 'Priradenie odstránené.']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy: ' . $e->getMessage()]);
}
