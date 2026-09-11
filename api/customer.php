<?php
session_start();
header('Content-Type: application/json');

require '../config.php';

// Check auth
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

require_once 'auto_ratings_helper.php';
process48HourAutoRatings($conn);

try {
    if ($action === 'get_profile') {
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date DATE NULL");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date_locked_until DATE NULL");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date_first_set_at DATETIME NULL");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('female','male','other') NULL");
        $stmt = $conn->prepare("SELECT phone, whatsapp, avatar_url, birth_date, birth_date_locked_until, birth_date_first_set_at, gender FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $locked_until = $res['birth_date_locked_until'] ?? null;
        $first_set_at = $res['birth_date_first_set_at'] ?? null;
        $within_grace = $first_set_at && (time() - strtotime($first_set_at)) <= 3 * 86400;
        $is_locked = $locked_until && strtotime($locked_until) > time() && !$within_grace;
        $grace_until = $within_grace ? date('Y-m-d H:i:s', strtotime($first_set_at) + 3 * 86400) : '';
        echo json_encode(['success' => true, 'data' => [
            'phone' => $res['phone'] ?? '',
            'whatsapp' => $res['whatsapp'] ?? '',
            'avatar_url' => $res['avatar_url'] ?? '',
            'birth_date' => $res['birth_date'] ?? '',
            'birth_date_locked' => $is_locked,
            'birth_date_locked_until' => $is_locked ? $locked_until : '',
            'birth_date_grace_until' => $grace_until,
            'gender' => $res['gender'] ?? ''
        ]]);

    } elseif ($action === 'update_profile') {
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $password = $_POST['password'] ?? '';
        $birth_date = trim($_POST['birth_date'] ?? '');
        if ($birth_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) { $birth_date = ''; }
        $gender = trim($_POST['gender'] ?? '');
        if (!in_array($gender, ['female', 'male', 'other'], true)) { $gender = null; }

        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER email");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(30) NULL AFTER phone");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date DATE NULL");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date_locked_until DATE NULL");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date_first_set_at DATETIME NULL");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('female','male','other') NULL");

        // Dátum narodenia sa dá zmeniť len raz za rok (kvôli narodeninovým zľavám — inak by si ich zákazník
        // vedel nastaviť opakovane na "dnes" a čerpať zľavu koľkokrát chce). Výnimka: 3-dňové ochranné okno
        // od prvého uloženia v danom cykle na opravu preklepu — toto okno ale NEPREDLžuje 365-dňový zámok,
        // ten sa vždy počíta od prvého uloženia v cykle, takže opakovanými "opravami" sa zámok obísť nedá.
        $cur_stmt = $conn->prepare("SELECT birth_date, birth_date_locked_until, birth_date_first_set_at FROM users WHERE id = ?");
        $cur_stmt->bind_param("i", $user_id);
        $cur_stmt->execute();
        $cur = $cur_stmt->get_result()->fetch_assoc();
        $current_birth_date = $cur['birth_date'] ?? null;
        $locked_until = $cur['birth_date_locked_until'] ?? null;
        $first_set_at = $cur['birth_date_first_set_at'] ?? null;
        $birth_date_is_changing = $birth_date !== ($current_birth_date ?? '');

        $within_grace = $first_set_at && (time() - strtotime($first_set_at)) <= 3 * 86400;
        $is_locked = $locked_until && strtotime($locked_until) > time() && !$within_grace;

        if ($birth_date_is_changing && $current_birth_date && $is_locked) {
            echo json_encode(['success' => false, 'message' => 'Dátum narodenia sa dá zmeniť len raz za rok. Ďalšiu zmenu budete môcť urobiť až ' . date('d.m.Y', strtotime($locked_until)) . '.']);
            exit;
        }

        // Nový cyklus (prvé nastavenie, alebo predchádzajúci zámok už vypršal) štartuje nový 365-dňový zámok
        // a nové 3-dňové ochranné okno. Oprava v rámci ešte bežiaceho ochranného okna zámok nereštartuje.
        $starting_new_cycle = $birth_date_is_changing && $birth_date !== '' && (!$locked_until || strtotime($locked_until) <= time());

        $avatar_url = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['avatar']['tmp_name'];
            $name = basename($_FILES['avatar']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = '../uploads/avatars/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $avatar_url = 'uploads/avatars/' . $new_name;
                }
            }
        }
        
        $fields = ["full_name = ?", "phone = ?", "whatsapp = ?", "birth_date = ?", "gender = ?"];
        $types = "sssss";
        $params = [$full_name, $phone, $whatsapp, $birth_date !== '' ? $birth_date : null, $gender];

        if ($starting_new_cycle) {
            // Nový cyklus: 365-dňový zámok + 3-dňové ochranné okno na opravu preklepu
            $fields[] = "birth_date_locked_until = ?";
            $types .= "s";
            $params[] = date('Y-m-d', strtotime('+365 days'));
            $fields[] = "birth_date_first_set_at = NOW()";
        }

        if (!empty($password)) {
            $fields[] = "password_hash = ?";
            $types .= "s";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($avatar_url !== null) {
            $fields[] = "avatar_url = ?";
            $types .= "s";
            $params[] = $avatar_url;
        }
        
        $types .= "i";
        $params[] = $user_id;
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            if (!empty($full_name)) $_SESSION['full_name'] = $full_name;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chyba pri ukladaní údajov.']);
        }
        
    } elseif ($action === 'get_dashboard_stats') {
        $stats = ['upcoming' => 0, 'completed' => 0, 'favorites' => 0];
        
        $res = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE customer_id = $user_id AND status IN ('pending', 'confirmed') AND booking_date >= CURDATE()");
        if ($r = $res->fetch_assoc()) $stats['upcoming'] = (int)$r['c'];
        
        $res = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE customer_id = $user_id AND status = 'completed'");
        if ($r = $res->fetch_assoc()) $stats['completed'] = (int)$r['c'];
        
        $res = $conn->query("SELECT COUNT(*) as c FROM favorites WHERE user_id = $user_id");
        if ($r = $res->fetch_assoc()) $stats['favorites'] = (int)$r['c'];
        
        // Nearest booking
        $nearest = null;
        $res = $conn->query("SELECT b.booking_date, b.start_time, e.name as salon_name FROM bookings b JOIN establishments e ON b.establishment_id = e.id WHERE b.customer_id = $user_id AND b.status IN ('pending', 'confirmed') AND b.booking_date >= CURDATE() ORDER BY b.booking_date ASC, b.start_time ASC LIMIT 1");
        if ($r = $res->fetch_assoc()) $nearest = $r;
        
        echo json_encode(['success' => true, 'stats' => $stats, 'nearest' => $nearest]);
        
    } elseif ($action === 'get_bookings') {
        $type = $_POST['type'] ?? 'upcoming'; // upcoming or past
        
        $op = ($type === 'upcoming') ? ">=" : "<";
        $statuses = ($type === 'upcoming') ? "'pending', 'confirmed'" : "'completed', 'cancelled', 'pending', 'confirmed'";
        if ($type === 'past') {
            // For past, include anything before today OR completed/cancelled anytime
            $where = "customer_id = $user_id AND (booking_date < CURDATE() OR status IN ('completed', 'cancelled'))";
        } else {
            $where = "customer_id = $user_id AND booking_date >= CURDATE() AND status NOT IN ('completed', 'cancelled')";
        }
        
        $query = "SELECT b.id, b.booking_date, b.start_time, b.status, e.id as establishment_id, e.name as salon_name, s.name as service_name, s.price 
                  FROM bookings b 
                  JOIN establishments e ON b.establishment_id = e.id 
                  JOIN services s ON b.service_id = s.id 
                  WHERE $where 
                  ORDER BY b.booking_date ASC, b.start_time ASC";
                  
        $res = $conn->query($query);
        $data = [];
        while($r = $res->fetch_assoc()) {
            $data[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $data]);
        
    } elseif ($action === 'cancel_booking') {
        $bid = (int)$_POST['booking_id'];
        // Check ownership
        $res = $conn->query("SELECT status, booking_date FROM bookings WHERE id = $bid AND customer_id = $user_id");
        if ($r = $res->fetch_assoc()) {
            if ($r['status'] === 'pending' || $r['status'] === 'confirmed') {
                require_once __DIR__ . '/../includes/cancellation_helper.php';
                $root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
                if (requestCustomerCancellation($conn, $root_url, $bid, 'single')) {
                    echo json_encode(['success' => true, 'message' => 'Na váš e-mail sme poslali potvrdzovací odkaz. Rezervácia bude zrušená až po jeho potvrdení.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Zrušenie sa nepodarilo, skúste to prosím znova.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Tento termín už nie je možné zrušiť.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenájdené.']);
        }

    } elseif ($action === 'toggle_favorite') {
        $est_id = (int)$_POST['establishment_id'];
        $res = $conn->query("SELECT id FROM favorites WHERE user_id = $user_id AND establishment_id = $est_id");
        if ($res->num_rows > 0) {
            $conn->query("DELETE FROM favorites WHERE user_id = $user_id AND establishment_id = $est_id");
            echo json_encode(['success' => true, 'is_favorite' => false]);
        } else {
            $conn->query("INSERT INTO favorites (user_id, establishment_id) VALUES ($user_id, $est_id)");
            echo json_encode(['success' => true, 'is_favorite' => true]);
        }
        
    } elseif ($action === 'get_favorites') {
        $query = "SELECT e.id, e.name, e.category, e.city, e.address, e.image_url 
                  FROM favorites f 
                  JOIN establishments e ON f.establishment_id = e.id 
                  WHERE f.user_id = $user_id";
        $res = $conn->query($query);
        $data = [];
        while($r = $res->fetch_assoc()) {
            $data[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $data]);
        
    } elseif ($action === 'get_reviews') {
        // Vlastné recenzie zákazníka o prevádzkach — zoraďované podľa rezervácie (skutočná štruktúra tabuľky)
        $query = "SELECT r.id, r.rating, r.comment as review_text, r.created_at, e.name as salon_name
                  FROM reviews r
                  JOIN bookings b ON b.id = r.booking_id
                  JOIN establishments e ON e.id = b.establishment_id
                  WHERE r.reviewer_id = $user_id AND r.reviewer_type = 'customer'
                  ORDER BY r.created_at DESC";
        $res = $conn->query($query);
        $data = [];
        while($r = $res->fetch_assoc()) {
            $data[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $data]);

    } elseif ($action === 'add_review') {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $rating = (int)$_POST['rating'];
        $text = trim($_POST['review_text'] ?? '');

        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Neplatné hodnotenie.']);
            exit;
        }
        if (!$booking_id) {
            echo json_encode(['success' => false, 'message' => 'Chýba rezervácia, ku ktorej sa hodnotenie viaže.']);
            exit;
        }

        // Overenie, že rezervácia patrí tomuto zákazníkovi a je ukončená
        $bk_stmt = $conn->prepare("SELECT establishment_id FROM bookings WHERE id = ? AND customer_id = ? AND status = 'completed'");
        $bk_stmt->bind_param("ii", $booking_id, $user_id);
        $bk_stmt->execute();
        $bk_row = $bk_stmt->get_result()->fetch_assoc();
        if (!$bk_row) {
            echo json_encode(['success' => false, 'message' => 'Túto rezerváciu nie je možné hodnotiť.']);
            exit;
        }
        $est_id = (int)$bk_row['establishment_id'];

        // Jedno hodnotenie zákazníka na jednu rezerváciu
        $existing_stmt = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ? AND reviewer_type = 'customer'");
        $existing_stmt->bind_param("i", $booking_id);
        $existing_stmt->execute();
        if ($existing_stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Túto návštevu ste už ohodnotili.']);
            exit;
        }

        $category_ratings_raw = $_POST['category_ratings'] ?? '';
        $category_ratings_json = null;
        if ($category_ratings_raw) {
            $decoded = json_decode($category_ratings_raw, true);
            if (is_array($decoded)) { $category_ratings_json = json_encode($decoded); }
        }

        if ($category_ratings_json !== null) {
            $stmt = $conn->prepare("INSERT INTO reviews (booking_id, reviewer_type, reviewer_id, reviewee_type, reviewee_id, rating, comment, category_ratings) VALUES (?, 'customer', ?, 'establishment', ?, ?, ?, ?)");
            $stmt->bind_param("iiiiss", $booking_id, $user_id, $est_id, $rating, $text, $category_ratings_json);
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (booking_id, reviewer_type, reviewer_id, reviewee_type, reviewee_id, rating, comment) VALUES (?, 'customer', ?, 'establishment', ?, ?, ?)");
            $stmt->bind_param("iiiis", $booking_id, $user_id, $est_id, $rating, $text);
        }
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chyba pri pridávaní recenzie.']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Neznáma akcia']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

$conn->close();
?>
