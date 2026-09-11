<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Nie ste prihlásený.']); exit;
}

$userId     = (int)$_SESSION['user_id'];
$userRole   = $_SESSION['user_role'] ?? 'customer';
// $_SESSION['business_id'] is never set anywhere in this codebase — resolve the real
// establishments.id here instead (same fix as api/vouchers.php), otherwise a business's
// own reviews/replies/ratings get queried under users.id and never match anything.
$businessId = 0;
if ($userRole === 'business') {
    $est_stmt0 = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $est_stmt0->bind_param("i", $userId);
    $est_stmt0->execute();
    $est_row0 = $est_stmt0->get_result()->fetch_assoc();
    $businessId = $est_row0 ? (int)$est_row0['id'] : 0;
}
$action     = $_POST['action'] ?? $_GET['action'] ?? '';

function esc($conn, $v) { return $conn->real_escape_string(trim($v??'')); }

// Self-migrácia: tabuľka na odpovede firmy na recenzie
$conn->query("CREATE TABLE IF NOT EXISTS review_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    business_id INT NOT NULL,
    reply_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_business (review_id, business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Skutočná štruktúra tabuľky reviews (overené 2026-09-01 cez SHOW COLUMNS):
// id, booking_id, reviewer_type ('customer'/'establishment'), reviewer_id,
// reviewee_type ('customer'/'establishment'), reviewee_id, rating, comment, created_at
// + category_ratings (JSON text), had_problem, is_auto (pridané self-migráciou v auto_ratings_helper.php)
require_once 'auto_ratings_helper.php';
require_once __DIR__ . '/../includes/trust_helper.php';
process72HourAutoRatings($conn);

switch ($action) {

    // ------------------------------------------------------------------
    // GET BUSINESS REVIEWS (reviews of this business left by customers)
    // ------------------------------------------------------------------
    case 'get_business_reviews':
        if ($userRole !== 'business') { echo json_encode(['success'=>false,'error'=>'Prístup zamietnutý.']); exit; }

        $revealed = reviewRevealedCondition('establishment');
        $sql = "SELECT r.*, u.full_name as reviewer_name, u.avatar_path as reviewer_avatar,
                       rr.reply_text, rr.created_at as reply_created_at
                FROM reviews r
                LEFT JOIN users u ON u.id = r.reviewer_id
                LEFT JOIN review_replies rr ON rr.review_id = r.id AND rr.business_id = $businessId
                WHERE r.reviewee_id = $businessId AND r.reviewee_type = 'establishment' AND r.reviewer_type = 'customer'
                  AND $revealed
                ORDER BY r.created_at DESC
                LIMIT 200";
        $res = $conn->query($sql);
        if (!$res) { echo json_encode(['success'=>false,'error'=>'DB chyba: '.$conn->error]); exit; }
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $r['category_ratings'] = $r['category_ratings'] ? json_decode($r['category_ratings'], true) : null;
            $rows[] = $r;
        }

        $stats = computeAggregateScore($conn, $businessId, 'establishment', 'establishment');

        echo json_encode([
            'success'  => true,
            'reviews'  => $rows,
            'stats'    => [
                'total'      => $stats['count'],
                'avg_rating' => $stats['average'],
                'is_new'     => $stats['is_new'],
            ]
        ]);
        break;

    // ------------------------------------------------------------------
    // REPLY TO A REVIEW (business replies to customer review)
    // ------------------------------------------------------------------
    case 'reply_to_review':
        if ($userRole !== 'business') { echo json_encode(['success'=>false,'error'=>'Prístup zamietnutý.']); exit; }
        $reviewId  = (int)($_POST['review_id']??0);
        $replyText = esc($conn, $_POST['reply_text']??'');
        if (!$reviewId || !$replyText) { echo json_encode(['success'=>false,'error'=>'Chýba ID recenzie alebo text odpovede.']); exit; }

        $check = $conn->query("SELECT id FROM reviews WHERE id=$reviewId AND reviewee_id=$businessId AND reviewee_type='establishment' LIMIT 1");
        if (!$check || $check->num_rows === 0) {
            echo json_encode(['success'=>false,'error'=>'Recenzia sa nenašla.']); exit;
        }

        $sql = "INSERT INTO review_replies (review_id, business_id, reply_text)
                VALUES ($reviewId, $businessId, '$replyText')
                ON DUPLICATE KEY UPDATE reply_text='$replyText', updated_at=NOW()";
        if ($conn->query($sql)) {
            echo json_encode(['success'=>true,'message'=>'Odpoveď bola uložená.']);
        } else {
            echo json_encode(['success'=>false,'error'=>'Chyba DB: '.$conn->error]);
        }
        break;

    // ------------------------------------------------------------------
    // GET MY CUSTOMER RATINGS (ratings this business gave to customers — vlastné, vidí ich vždy)
    // ------------------------------------------------------------------
    case 'get_my_customer_ratings':
        if ($userRole !== 'business') { echo json_encode(['success'=>false,'error'=>'Prístup zamietnutý.']); exit; }

        $sql = "SELECT r.*, u.full_name as customer_name, u.avatar_path as customer_avatar
                FROM reviews r
                LEFT JOIN users u ON u.id = r.reviewee_id
                WHERE r.reviewer_id = $businessId AND r.reviewer_type = 'establishment' AND r.reviewee_type = 'customer'
                ORDER BY r.created_at DESC
                LIMIT 200";
        $res = $conn->query($sql);
        if (!$res) { echo json_encode(['success'=>false,'error'=>'DB chyba.']); exit; }
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $r['category_ratings'] = $r['category_ratings'] ? json_decode($r['category_ratings'], true) : null;
            $rows[] = $r;
        }
        echo json_encode(['success'=>true,'ratings'=>$rows]);
        break;

    // ------------------------------------------------------------------
    // RATE CUSTOMER (business rates a customer) — viazané na konkrétnu rezerváciu
    // ------------------------------------------------------------------
    case 'rate_customer':
        if ($userRole !== 'business') { echo json_encode(['success'=>false,'error'=>'Prístup zamietnutý.']); exit; }
        $customerId = (int)($_POST['customer_id']??0);
        $rating     = min(5, max(1, (int)($_POST['rating']??5)));
        $comment    = esc($conn, $_POST['comment']??'');
        $bookingId  = (int)($_POST['booking_id']??0);
        $categoryRatingsRaw = $_POST['category_ratings'] ?? '';
        $categoryRatingsJson = null;
        if ($categoryRatingsRaw) {
            $decoded = json_decode($categoryRatingsRaw, true);
            if (is_array($decoded)) { $categoryRatingsJson = esc($conn, json_encode($decoded)); }
        }
        if (!$customerId) { echo json_encode(['success'=>false,'error'=>'Chýba ID zákazníka.']); exit; }

        // Ak konkrétna rezervácia nie je zadaná (napr. hodnotenie z CRM kontaktov), nájdeme
        // najnovšiu ukončenú návštevu tohto zákazníka, ktorú prevádzka ešte neohodnotila.
        if (!$bookingId) {
            $find = $conn->query("SELECT b.id FROM bookings b
                                   WHERE b.customer_id = $customerId AND b.establishment_id = $businessId AND b.status = 'completed'
                                     AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.id AND r.reviewer_type = 'establishment')
                                   ORDER BY b.booking_date DESC, b.start_time DESC LIMIT 1")->fetch_assoc();
            $bookingId = (int)($find['id'] ?? 0);
        }
        if (!$bookingId) {
            echo json_encode(['success'=>false,'error'=>'Tohto zákazníka nie je možné hodnotiť — nemá u vás žiadnu ukončenú návštevu, ktorá by ešte nebola ohodnotená.']); exit;
        }

        $categorySet = $categoryRatingsJson !== null ? ", category_ratings='$categoryRatingsJson'" : "";
        $categoryCol = $categoryRatingsJson !== null ? ", category_ratings" : "";
        $categoryVal = $categoryRatingsJson !== null ? ", '$categoryRatingsJson'" : "";

        $existing = $conn->query("SELECT id FROM reviews WHERE booking_id=$bookingId AND reviewer_type='establishment' LIMIT 1")->fetch_assoc();
        if ($existing) {
            $conn->query("UPDATE reviews SET rating=$rating, comment='$comment'$categorySet WHERE id=".(int)$existing['id']);
        } else {
            $conn->query("INSERT INTO reviews (booking_id, reviewer_type, reviewer_id, reviewee_type, reviewee_id, rating, comment$categoryCol)
                          VALUES ($bookingId, 'establishment', $businessId, 'customer', $customerId, $rating, '$comment'$categoryVal)");
        }
        echo json_encode(['success'=>true,'message'=>'Hodnotenie zákazníka bolo uložené.']);
        break;

    // ------------------------------------------------------------------
    // GET REVIEWS GIVEN TO ME (customer: my ratings from businesses) — len odhalené
    // ------------------------------------------------------------------
    case 'get_my_ratings':
        $revealed = reviewRevealedCondition('customer');
        $sql = "SELECT r.*, e.name as business_name
                FROM reviews r
                LEFT JOIN establishments e ON e.id = r.reviewer_id
                WHERE r.reviewee_id = $userId AND r.reviewee_type = 'customer'
                  AND $revealed
                ORDER BY r.created_at DESC
                LIMIT 100";
        $res = $conn->query($sql);
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;

        $stats = computeAggregateScore($conn, $userId, 'customer', 'customer');

        echo json_encode(['success'=>true,'ratings'=>$rows,'stats'=>[
            'total' => $stats['count'], 'avg_rating' => $stats['average'], 'is_new' => $stats['is_new']
        ]]);
        break;

    // ------------------------------------------------------------------
    // LEAVE REVIEW FOR BUSINESS (customer rates a business) — viazané na konkrétnu rezerváciu
    // ------------------------------------------------------------------
    case 'leave_review':
        $bookingId = (int)($_POST['booking_id']??0);
        $rating    = min(5, max(1, (int)($_POST['rating']??5)));
        $comment   = esc($conn, $_POST['comment']??'');
        if (!$bookingId) { echo json_encode(['success'=>false,'error'=>'Chýba rezervácia, ku ktorej sa hodnotenie viaže.']); exit; }

        $bk = $conn->query("SELECT establishment_id FROM bookings WHERE id=$bookingId AND customer_id=$userId AND status='completed' LIMIT 1")->fetch_assoc();
        if (!$bk) { echo json_encode(['success'=>false,'error'=>'Túto rezerváciu nie je možné hodnotiť.']); exit; }
        $targetBusinessId = (int)$bk['establishment_id'];

        $existing = $conn->query("SELECT id FROM reviews WHERE booking_id=$bookingId AND reviewer_type='customer' LIMIT 1")->fetch_assoc();
        if ($existing) {
            $conn->query("UPDATE reviews SET rating=$rating, comment='$comment' WHERE id=".(int)$existing['id']);
        } else {
            $conn->query("INSERT INTO reviews (booking_id, reviewer_type, reviewer_id, reviewee_type, reviewee_id, rating, comment)
                          VALUES ($bookingId, 'customer', $userId, 'establishment', $targetBusinessId, $rating, '$comment')");
        }
        echo json_encode(['success'=>true,'message'=>'Vaša recenzia bola odoslaná. Ďakujeme!']);
        break;

    // ------------------------------------------------------------------
    // GET TRUST TIER (interné použitie — napr. book_appointment.php pri rozhodovaní o zálohe)
    // ------------------------------------------------------------------
    case 'get_customer_trust_tier':
        $customerId = (int)($_POST['customer_id'] ?? $_GET['customer_id'] ?? 0);
        if (!$customerId) { echo json_encode(['success'=>false,'error'=>'Chýba ID zákazníka.']); exit; }
        $trust = getCustomerTrustTier($conn, $customerId);
        echo json_encode(['success'=>true,'tier'=>$trust['tier'],'average'=>$trust['average'],'count'=>$trust['count']]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Neznáma akcia: '.htmlspecialchars($action)]);
}
