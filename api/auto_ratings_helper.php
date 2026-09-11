<?php
/**
 * Auto-rating helper:
 * Ak zákazník alebo prevádzka neohodnotí návštevu do 72 hodín od jej ukončenia,
 * systém automaticky udelí 5 hviezdičiek chýbajúcej strane. Toto zároveň slúži ako
 * "slepé" odhalenie obojstranného hodnotenia — keď existujú OBE strany (reálne alebo
 * automatické), hodnotenie sa stáva viditeľným (pozri computeVisibleReviewCondition v api/reviews.php).
 */

function process72HourAutoRatings($conn) {
    if (!$conn) return;

    // 1. Uistíme sa, že tabuľka reviews existuje a má stĺpce pre kategórie a "problém" príznak
    $conn->query("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            reviewer_type ENUM('customer', 'establishment') NOT NULL,
            reviewer_id INT NOT NULL,
            reviewee_type ENUM('customer', 'establishment') NOT NULL,
            reviewee_id INT NOT NULL,
            rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY (booking_id),
            KEY (reviewer_id),
            KEY (reviewee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    try { $conn->query("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS category_ratings TEXT DEFAULT NULL"); } catch (Exception $e) {}
    try { $conn->query("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS had_problem TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $conn->query("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS is_auto TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    // 2. Nájdeme všetky rezervácie, ktoré prebehli pred viac ako 72 hodinami
    $sql = "
        SELECT b.id as booking_id, b.customer_id, b.establishment_id,
               b.booking_date, b.end_time, b.status
        FROM bookings b
        WHERE b.status IN ('completed', 'confirmed')
          AND TIMESTAMPDIFF(HOUR, CONCAT(b.booking_date, ' ', b.end_time), NOW()) >= 72
          AND b.customer_id IS NOT NULL
    ";

    $res = $conn->query($sql);
    if (!$res) return;

    $updated_establishments = [];

    while ($row = $res->fetch_assoc()) {
        $booking_id = (int)$row['booking_id'];
        $customer_id = (int)$row['customer_id'];
        $establishment_id = (int)$row['establishment_id'];

        // A) Automatické 5★ hodnotenie prevádzky zo strany zákazníka (ak ešte nehodnotil)
        $chkCust = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ? AND reviewer_type = 'customer'");
        if ($chkCust) {
            $chkCust->bind_param("i", $booking_id);
            $chkCust->execute();
            $cRes = $chkCust->get_result();
            if ($cRes && $cRes->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO reviews (booking_id, reviewer_type, reviewer_id, reviewee_type, reviewee_id, rating, comment, is_auto) VALUES (?, 'customer', ?, 'establishment', ?, 5, 'Automatické 5★ hodnotenie spokojnosti (po 72 hod)', 1)");
                if ($ins) {
                    $ins->bind_param("iii", $booking_id, $customer_id, $establishment_id);
                    $ins->execute();
                    $ins->close();
                    $updated_establishments[$establishment_id] = true;
                }
            }
            $chkCust->close();
        }

        // B) Automatické 5★ hodnotenie zákazníka zo strany prevádzky (ak ešte nehodnotila)
        $chkEst = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ? AND reviewer_type = 'establishment'");
        if ($chkEst) {
            $chkEst->bind_param("i", $booking_id);
            $chkEst->execute();
            $eRes = $chkEst->get_result();
            if ($eRes && $eRes->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO reviews (booking_id, reviewer_type, reviewer_id, reviewee_type, reviewee_id, rating, comment, is_auto) VALUES (?, 'establishment', ?, 'customer', ?, 5, 'Automatické 5★ hodnotenie spoľahlivého klienta (po 72 hod)', 1)");
                if ($ins) {
                    $ins->bind_param("iii", $booking_id, $establishment_id, $customer_id);
                    $ins->execute();
                    $ins->close();
                }
            }
            $chkEst->close();
        }

        // C) Ak bol status len 'confirmed' a prešlo 72h, zmeníme na 'completed'
        if ($row['status'] === 'confirmed') {
            $upd = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
            if ($upd) {
                $upd->bind_param("i", $booking_id);
                $upd->execute();
                $upd->close();
            }
            require_once __DIR__ . '/../includes/loyalty_helper.php';
            awardLoyaltyPoints($conn, $booking_id);
        }
    }

    // 3. Prepočet priemerného ratingu pre dotknuté prevádzky (len z hodnotení, ktoré majú obe strany = sú viditeľné)
    foreach (array_keys($updated_establishments) as $est_id) {
        $r_stmt = $conn->prepare("
            SELECT ROUND(AVG(r.rating), 2) as avg_rating, COUNT(*) as cnt
            FROM reviews r
            WHERE r.reviewee_id = ? AND r.reviewee_type = 'establishment'
              AND EXISTS (SELECT 1 FROM reviews r2 WHERE r2.booking_id = r.booking_id AND r2.reviewer_type = 'establishment')
        ");
        if ($r_stmt) {
            $r_stmt->bind_param("i", $est_id);
            $r_stmt->execute();
            $avg = $r_stmt->get_result()->fetch_assoc();
            $r_stmt->close();

            // Menej ako 3 viditeľné hodnotenia = ešte "Nová prevádzka", nezverejňujeme konkrétne číslo
            $new_rating = ((int)($avg['cnt'] ?? 0) >= 3 && $avg['avg_rating']) ? (float)$avg['avg_rating'] : null;

            try {
                $u_stmt = $conn->prepare("UPDATE establishments SET rating = ? WHERE id = ?");
                if ($u_stmt) {
                    $u_stmt->bind_param("di", $new_rating, $est_id);
                    $u_stmt->execute();
                    $u_stmt->close();
                }
            } catch (Exception $e) { /* stĺpec rating nemusí povoľovať NULL — hodnota sa dopočíta naživo v api/reviews.php */ }
        }
    }
}

// Spätná kompatibilita so starším názvom funkcie (48h) — presmerované na nové 72h správanie
if (!function_exists('process48HourAutoRatings')) {
    function process48HourAutoRatings($conn) {
        process72HourAutoRatings($conn);
    }
}

/**
 * Žiadosť o hodnotenie: 48 hodín po ukončení návštevy (ale pred 72h automatickým odhalením
 * vyššie) pošleme zákazníkovi e-mail s prosbou o ohodnotenie, ak ešte sám nehodnotil.
 * Bookings staršie ako 14 dní už nepripomíname (strata relevancie, aj aby sa cron nezacyklil
 * na starých dátach pri prvom nasadení).
 */
function sendReviewRequestEmails($conn) {
    if (!$conn) return;

    $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS review_request_sent_at DATETIME NULL");

    require_once __DIR__ . '/mailer.php';
    if (!function_exists('sendReviewRequestEmail')) return;

    $sql = "
        SELECT b.id as booking_id, b.customer_id, b.establishment_id,
               u.email as customer_email, u.full_name as customer_name,
               e.name as establishment_name,
               s.name as service_name
        FROM bookings b
        JOIN users u ON u.id = b.customer_id
        JOIN establishments e ON e.id = b.establishment_id
        LEFT JOIN services s ON s.id = b.service_id
        WHERE b.status IN ('completed', 'confirmed')
          AND b.review_request_sent_at IS NULL
          AND TIMESTAMPDIFF(HOUR, CONCAT(b.booking_date, ' ', b.end_time), NOW()) >= 48
          AND TIMESTAMPDIFF(DAY, CONCAT(b.booking_date, ' ', b.end_time), NOW()) <= 14
          AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.id AND r.reviewer_type = 'customer' AND r.is_auto = 0)
    ";
    $res = $conn->query($sql);
    if (!$res) return;

    $root_url = defined('APP_ROOT_URL') ? APP_ROOT_URL : 'https://rezervos.eu';

    while ($row = $res->fetch_assoc()) {
        $booking_id = (int)$row['booking_id'];
        $review_url = $root_url . '/moj_profil.php?section=bookings';

        @sendReviewRequestEmail($row['customer_email'], $row['customer_name'], $row['establishment_name'], $row['service_name'] ?: 'návšteva', $review_url);

        $mark = $conn->prepare("UPDATE bookings SET review_request_sent_at = NOW() WHERE id = ?");
        if ($mark) {
            $mark->bind_param("i", $booking_id);
            $mark->execute();
            $mark->close();
        }
    }
}

/**
 * "Chýbate nám" — ak sa zákazník k prevádzke nevrátil 60+ dní od poslednej dokončenej návštevy
 * a nemá už naplánovaný ďalší termín, pošleme mu jednorazovú pripomienku. Odoslanie sa loguje
 * do customer_winback_log, aby sa rovnakému páru zákazník+prevádzka neposielalo opakovane
 * (najskôr znova po 180 dňoch od predošlého win-back e-mailu).
 */
function sendWinbackEmails($conn) {
    if (!$conn) return;

    $conn->query("CREATE TABLE IF NOT EXISTS customer_winback_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        establishment_id INT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_customer_est (customer_id, establishment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    require_once __DIR__ . '/mailer.php';
    if (!function_exists('sendWinbackEmail')) return;

    $sql = "
        SELECT u.id as customer_id, u.email as customer_email, u.full_name as customer_name,
               e.id as establishment_id, e.name as establishment_name,
               MAX(b.booking_date) as last_visit
        FROM bookings b
        JOIN users u ON u.id = b.customer_id
        JOIN establishments e ON e.id = b.establishment_id
        WHERE b.status = 'completed'
        GROUP BY u.id, e.id
        HAVING TIMESTAMPDIFF(DAY, last_visit, CURDATE()) BETWEEN 60 AND 90
           AND NOT EXISTS (
               SELECT 1 FROM bookings b2
               WHERE b2.customer_id = u.id AND b2.establishment_id = e.id
                 AND b2.status IN ('pending', 'confirmed', 'pending_deposit')
                 AND b2.booking_date >= CURDATE()
           )
           AND NOT EXISTS (
               SELECT 1 FROM customer_winback_log w
               WHERE w.customer_id = u.id AND w.establishment_id = e.id
                 AND w.sent_at > DATE_SUB(NOW(), INTERVAL 180 DAY)
           )
    ";
    $res = $conn->query($sql);
    if (!$res) return;

    $root_url = defined('APP_ROOT_URL') ? APP_ROOT_URL : 'https://rezervos.eu';

    while ($row = $res->fetch_assoc()) {
        $customer_id = (int)$row['customer_id'];
        $establishment_id = (int)$row['establishment_id'];
        $booking_url = $root_url . '/profil.php?id=' . $establishment_id;

        @sendWinbackEmail($row['customer_email'], $row['customer_name'], $row['establishment_name'], $booking_url);

        $log = $conn->prepare("INSERT INTO customer_winback_log (customer_id, establishment_id) VALUES (?, ?)");
        if ($log) {
            $log->bind_param("ii", $customer_id, $establishment_id);
            $log->execute();
            $log->close();
        }
    }
}
