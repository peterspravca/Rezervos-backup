<?php
/**
 * Zdieľaná logika pre dvojfázové zrušenie rezervácie zákazníkom (na priamu žiadosť
 * cez api/customer.php aj cez odkaz z e-mailu v api/manage_booking.php).
 * Rezervácia sa reálne zruší až po kliknutí na potvrdzovací odkaz v druhom e-maile
 * (potvrdenie-zrusenia.php) — ochrana pred nechceným/omylovým zrušením.
 */
require_once __DIR__ . '/waitlist_helper.php';
require_once __DIR__ . '/../api/mailer.php';

function ensureCancellationColumns($conn) {
    $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS pending_cancel_token VARCHAR(64) DEFAULT NULL");
    $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS pending_cancel_scope ENUM('single','series') DEFAULT NULL");
    $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancelled_by ENUM('customer','establishment') DEFAULT NULL");
    $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancelled_at DATETIME DEFAULT NULL");
}

// Vygeneruje token pre danú rezerváciu a pošle zákazníkovi potvrdzovací e-mail. Rezervácia sa
// zatiaľ nemení — zostáva platná, kým sa nepotvrdí druhým klikom.
function requestCustomerCancellation($conn, $root_url, $booking_id, $scope = 'single') {
    ensureCancellationColumns($conn);

    $stmt = $conn->prepare("
        SELECT b.id, b.customer_id, b.booking_date, b.start_time, s.name as service_name,
               e.name as establishment_name, u.email as customer_email, u.full_name as customer_name
        FROM bookings b
        LEFT JOIN services s ON s.id = b.service_id
        LEFT JOIN establishments e ON e.id = b.establishment_id
        LEFT JOIN users u ON u.id = b.customer_id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $b = $stmt->get_result()->fetch_assoc();
    if (!$b || empty($b['customer_email'])) return false;

    $token = bin2hex(random_bytes(20));
    $upd = $conn->prepare("UPDATE bookings SET pending_cancel_token = ?, pending_cancel_scope = ? WHERE id = ?");
    $upd->bind_param("ssi", $token, $scope, $booking_id);
    $upd->execute();

    $confirm_url = $root_url . 'potvrdenie-zrusenia.php?token=' . $token;
    @sendCancelConfirmationEmail(
        $b['customer_email'],
        $b['customer_name'] ?: 'zákazník',
        $b['establishment_name'] ?: 'prevádzka',
        $b['service_name'] ?: 'rezervovaná služba',
        date('d.m.Y', strtotime($b['booking_date'])),
        substr($b['start_time'], 0, 5),
        $confirm_url
    );
    return true;
}

// Skutočne zruší rezerváciu (a pri scope='series' aj všetky budúce v sérii) — volané len z
// potvrdenie-zrusenia.php po kliknutí na odkaz v druhom e-maile.
function finalizeCustomerCancellationByToken($conn, $root_url, $token) {
    ensureCancellationColumns($conn);

    $stmt = $conn->prepare("SELECT * FROM bookings WHERE pending_cancel_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    if (!$booking) return ['success' => false, 'message' => 'Odkaz na potvrdenie zrušenia je neplatný alebo už bol použitý.'];
    if (in_array($booking['status'], ['cancelled', 'completed'], true)) {
        // Rezervácia sa medzičasom už zmenila (napr. prevádzka ju medzitým označila ako dokončenú,
        // alebo bola zrušená inou cestou) — starý odkaz z e-mailu už nemá čo potvrdiť.
        return ['success' => false, 'message' => 'Táto rezervácia už bola medzičasom zrušená alebo ukončená — odkaz už nie je platný.'];
    }

    $affected_dates = [$booking['booking_date']];

    if (($booking['pending_cancel_scope'] ?? 'single') === 'series' && !empty($booking['recurring_series_id'])) {
        $affected_stmt = $conn->prepare("SELECT DISTINCT booking_date FROM bookings WHERE recurring_series_id = ? AND (booking_date > ? OR (booking_date = ? AND start_time >= ?)) AND status NOT IN ('cancelled','completed')");
        $affected_stmt->bind_param("isss", $booking['recurring_series_id'], $booking['booking_date'], $booking['booking_date'], $booking['start_time']);
        $affected_stmt->execute();
        $affected_dates = array_column($affected_stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'booking_date');

        $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancelled_by = 'customer', cancelled_at = NOW(), pending_cancel_token = NULL, pending_cancel_scope = NULL WHERE recurring_series_id = ? AND (booking_date > ? OR (booking_date = ? AND start_time >= ?)) AND status NOT IN ('cancelled','completed')");
        $upd->bind_param("isss", $booking['recurring_series_id'], $booking['booking_date'], $booking['booking_date'], $booking['start_time']);
        $upd->execute();
    } else {
        $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancelled_by = 'customer', cancelled_at = NOW(), pending_cancel_token = NULL, pending_cancel_scope = NULL WHERE id = ? AND status NOT IN ('cancelled','completed')");
        $upd->bind_param("i", $booking['id']);
        $upd->execute();
        if ($upd->affected_rows === 0) {
            return ['success' => false, 'message' => 'Táto rezervácia už bola medzičasom zrušená alebo ukončená — odkaz už nie je platný.'];
        }
    }

    foreach ($affected_dates as $affected_date) {
        @notifyWaitlistForFreedSlot($conn, (int)$booking['establishment_id'], $affected_date);
    }
    notifyEstablishmentOfCancellation($conn, $root_url, (int)$booking['id']);

    return ['success' => true, 'message' => 'Rezervácia bola zrušená.'];
}

// Upozorní prevádzku (zvonček rieši api/notifications.php samo, toto je len e-mail) —
// s kontaktom na zákazníka a odkazom na rýchle vytvorenie Last Minute ponuky.
function notifyEstablishmentOfCancellation($conn, $root_url, $booking_id) {
    $stmt = $conn->prepare("
        SELECT b.service_id, b.booking_date, b.start_time, s.name as service_name, s.price,
               e.id as establishment_id, e.name as establishment_name, u.id as est_user_id, u.email as est_email,
               cu.full_name as customer_name, cu.phone as customer_phone
        FROM bookings b
        LEFT JOIN services s ON s.id = b.service_id
        LEFT JOIN establishments e ON e.id = b.establishment_id
        LEFT JOIN users u ON u.id = e.user_id
        LEFT JOIN users cu ON cu.id = b.customer_id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r || empty($r['est_email'])) return;

    $lm_url = $root_url . 'dashboard-last-minute.php'
        . '?prefill_service=' . (int)$r['service_id']
        . '&prefill_date=' . urlencode($r['booking_date'])
        . '&prefill_time=' . urlencode(substr($r['start_time'], 0, 5))
        . '&prefill_price=' . urlencode($r['price'] ?? '')
        . '&quick=1&discount=20';

    @sendCancellationLastMinuteOffer(
        $r['est_email'],
        $r['establishment_name'] ?: 'prevádzka',
        $r['customer_name'] ?: 'Zákazník',
        $r['customer_phone'] ?? '',
        $r['service_name'] ?: 'rezervovaná služba',
        date('d.m.Y', strtotime($r['booking_date'])),
        substr($r['start_time'], 0, 5),
        $lm_url
    );
}
