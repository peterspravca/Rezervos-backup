<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/holidays_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['business', 'customer'], true)) {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$action  = $_POST['action'] ?? $_GET['action'] ?? 'get_notifications';

// Auto-create notification_state table (safe – runs only if missing)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_state (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        notif_key  VARCHAR(120) NOT NULL,
        is_read    TINYINT(1) NOT NULL DEFAULT 0,
        is_deleted TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_key (user_id, notif_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) { /* table already exists or no permission */ }

try {

    // ── mark_read ─────────────────────────────────────────────────────────────
    if ($action === 'mark_read') {
        $key = trim($_POST['key'] ?? $_GET['key'] ?? '');
        if (!$key) { echo json_encode(['success' => false, 'message' => 'Chýba key.']); exit; }
        $pdo->prepare("INSERT INTO notification_state (user_id, notif_key, is_read, is_deleted)
                        VALUES (?, ?, 1, 0)
                        ON DUPLICATE KEY UPDATE is_read = 1")
            ->execute([$user_id, $key]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── delete ────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $key = trim($_POST['key'] ?? $_GET['key'] ?? '');
        if (!$key) { echo json_encode(['success' => false, 'message' => 'Chýba key.']); exit; }
        $pdo->prepare("INSERT INTO notification_state (user_id, notif_key, is_read, is_deleted)
                        VALUES (?, ?, 1, 1)
                        ON DUPLICATE KEY UPDATE is_read = 1, is_deleted = 1")
            ->execute([$user_id, $key]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── mark_all_read ─────────────────────────────────────────────────────────
    if ($action === 'mark_all_read') {
        $keys_raw = $_POST['keys'] ?? $_GET['keys'] ?? '';
        $keys = array_filter(array_map('trim', explode(',', $keys_raw)));
        $stmt = $pdo->prepare("INSERT INTO notification_state (user_id, notif_key, is_read, is_deleted)
                                VALUES (?, ?, 1, 0)
                                ON DUPLICATE KEY UPDATE is_read = 1");
        foreach ($keys as $k) {
            $stmt->execute([$user_id, $k]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // ── delete_read ───────────────────────────────────────────────────────────
    if ($action === 'delete_read') {
        $pdo->prepare("UPDATE notification_state SET is_deleted = 1 WHERE user_id = ? AND is_read = 1")
            ->execute([$user_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── get_notifications ─────────────────────────────────────────────────────
    $today    = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    // Load persisted read/deleted state — fault-tolerant
    $state_map = [];
    try {
        $stmt_state = $pdo->prepare("SELECT notif_key, is_read, is_deleted FROM notification_state WHERE user_id = ?");
        $stmt_state->execute([$user_id]);
        foreach ($stmt_state->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $state_map[$row['notif_key']] = [
                'is_read'    => (bool)$row['is_read'],
                'is_deleted' => (bool)$row['is_deleted'],
            ];
        }
    } catch (Exception $e) {
        // notification_state table might not exist yet — show all notifications unread
    }

    $visible = function(string $key) use ($state_map): bool {
        return empty($state_map[$key]['is_deleted']);
    };
    $isRead = function(string $key) use ($state_map): bool {
        return !empty($state_map[$key]['is_read']);
    };

    $notifications = [];

    // Get establishment
    $est    = null;
    $est_id = 0;
    try {
        $stmt_est = $pdo->prepare("SELECT id, name, status FROM establishments WHERE user_id = ? LIMIT 1");
        $stmt_est->execute([$user_id]);
        $est    = $stmt_est->fetch(PDO::FETCH_ASSOC);
        $est_id = $est ? (int)$est['id'] : 0;
    } catch (Exception $e) {}

    // ① Prevádzka čaká na schválenie
    try {
        if ($est && $est['status'] === 'pending') {
            $key = 'status_pending';
            if ($visible($key)) {
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'warning',
                    'icon'    => 'hourglass_top',
                    'title'   => 'Čaká na schválenie',
                    'message' => 'Váš profil prevádzky momentálne čaká na schválenie administrátorom.',
                    'time'    => 'Dnes',
                    'is_read' => $isRead($key),
                ];
            }
        }
    } catch (Exception $e) {}

    if ($est_id > 0) {

        // ② Rezervácie čakajúce na potvrdenie
        try {
            $stmt_b = $pdo->prepare("
                SELECT b.id, COALESCE(u.full_name, b.manual_name, 'Zákazník') AS customer_name, b.booking_date, b.start_time, s.name AS service_name
                FROM bookings b
                LEFT JOIN services s ON b.service_id = s.id
                LEFT JOIN users u ON u.id = b.customer_id
                WHERE b.establishment_id = ? AND b.status = 'pending'
                ORDER BY b.booking_date ASC, b.start_time ASC
                LIMIT 10
            ");
            $stmt_b->execute([$est_id]);
            foreach ($stmt_b->fetchAll(PDO::FETCH_ASSOC) as $b) {
                $key = 'booking_pending_' . $b['id'];
                if (!$visible($key)) continue;
                $client = !empty($b['customer_name']) ? $b['customer_name'] : 'Zákazník';
                $srv    = !empty($b['service_name'])  ? $b['service_name']  : 'Služba';
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'booking_pending',
                    'icon'    => 'event_upcoming',
                    'title'   => 'Rezervácia čaká na potvrdenie',
                    'message' => "{$client} — {$srv} · " . date('d.m.Y', strtotime($b['booking_date'])) . ' o ' . substr($b['start_time'], 0, 5),
                    'time'    => date('d.m.', strtotime($b['booking_date'])),
                    'is_read' => $isRead($key),
                ];
            }
        } catch (Exception $e) {}

        // ③ Nové potvrdené rezervácie za posledných 24 hodín
        try {
            $stmt_new = $pdo->prepare("
                SELECT b.id, COALESCE(u.full_name, b.manual_name, 'Zákazník') AS customer_name, b.booking_date, b.start_time, s.name AS service_name
                FROM bookings b
                LEFT JOIN services s ON b.service_id = s.id
                LEFT JOIN users u ON u.id = b.customer_id
                WHERE b.establishment_id = ? AND b.status = 'confirmed'
                  AND b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY b.created_at DESC
                LIMIT 5
            ");
            $stmt_new->execute([$est_id]);
            foreach ($stmt_new->fetchAll(PDO::FETCH_ASSOC) as $b) {
                $key = 'booking_new_' . $b['id'];
                if (!$visible($key)) continue;
                $client = !empty($b['customer_name']) ? $b['customer_name'] : 'Zákazník';
                $srv    = !empty($b['service_name'])  ? $b['service_name']  : 'Služba';
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'booking_new',
                    'icon'    => 'event_available',
                    'title'   => 'Nová rezervácia',
                    'message' => "{$client} — {$srv} · " . date('d.m.Y', strtotime($b['booking_date'])) . ' o ' . substr($b['start_time'], 0, 5),
                    'time'    => 'Dnes',
                    'is_read' => $isRead($key),
                ];
            }
        } catch (Exception $e) {}

        // ③b Zákazník zrušil rezerváciu (posledné 3 dni) — príležitosť ponúknuť Last Minute
        try {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancelled_by ENUM('customer','establishment') DEFAULT NULL");
            $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancelled_at DATETIME DEFAULT NULL");
            $stmt_c = $pdo->prepare("
                SELECT b.id, b.booking_date, b.start_time, s.name AS service_name, s.id as service_id, s.price,
                       u.full_name AS customer_name, u.phone AS customer_phone
                FROM bookings b
                LEFT JOIN services s ON b.service_id = s.id
                LEFT JOIN users u ON u.id = b.customer_id
                WHERE b.establishment_id = ? AND b.status = 'cancelled' AND b.cancelled_by = 'customer'
                  AND b.cancelled_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
                ORDER BY b.cancelled_at DESC
                LIMIT 10
            ");
            $stmt_c->execute([$est_id]);
            foreach ($stmt_c->fetchAll(PDO::FETCH_ASSOC) as $b) {
                $key = 'booking_cancelled_' . $b['id'];
                if (!$visible($key)) continue;
                $client = !empty($b['customer_name']) ? $b['customer_name'] : 'Zákazník';
                $srv    = !empty($b['service_name'])  ? $b['service_name']  : 'Služba';
                $phone_part = !empty($b['customer_phone']) ? (' · ' . $b['customer_phone']) : '';
                $lm_link = 'dashboard-last-minute.php?prefill_service=' . (int)($b['service_id'] ?? 0)
                    . '&prefill_date=' . urlencode($b['booking_date'])
                    . '&prefill_time=' . urlencode(substr($b['start_time'], 0, 5))
                    . '&prefill_price=' . urlencode($b['price'] ?? '');
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'warning',
                    'icon'    => 'event_busy',
                    'title'   => 'Zákazník zrušil rezerváciu',
                    'message' => "{$client}{$phone_part} — {$srv} · " . date('d.m.Y', strtotime($b['booking_date'])) . ' o ' . substr($b['start_time'], 0, 5),
                    'time'    => date('d.m.', strtotime($b['booking_date'])),
                    'is_read' => $isRead($key),
                    'link'    => $lm_link,
                ];
            }
        } catch (Exception $e) {}

        // ④ Nízka obsadenosť zajtra (< 40 %)
        try {
            $key_occ = 'occ_low_' . $tomorrow;
            if ($visible($key_occ)) {
                $stmt_occ = $pdo->prepare("
                    SELECT COALESCE(SUM(COALESCE(s.duration_minutes, 60)), 0) AS booked_mins
                    FROM bookings b
                    LEFT JOIN services s ON b.service_id = s.id
                    WHERE b.establishment_id = ? AND b.booking_date = ?
                      AND b.status NOT IN ('cancelled', 'unavailable')
                ");
                $stmt_occ->execute([$est_id, $tomorrow]);
                $booked_mins = (int)($stmt_occ->fetchColumn() ?? 0);
                $total_mins  = (18 - 8) * 60; // 600 min
                $occ_pct     = $total_mins > 0 ? (int)round($booked_mins / $total_mins * 100) : 0;
                if ($occ_pct < 40) {
                    $free_h = number_format(($total_mins - $booked_mins) / 60, 1, ',', '');
                    $notifications[] = [
                        'key'     => $key_occ,
                        'type'    => 'low_occupancy',
                        'icon'    => 'calendar_month',
                        'title'   => 'Nízka obsadenosť zajtra',
                        'message' => "Zajtra máte len {$occ_pct} % obsadenosť. Voľné: {$free_h} h. Zvážte Last Minute ponuku.",
                        'time'    => 'Zajtra',
                        'is_read' => $isRead($key_occ),
                    ];
                }
            }
        } catch (Exception $e) {}

        // ⑤ Expirujúce Last Minute (dnes alebo zajtra)
        try {
            $stmt_lm = $pdo->prepare("
                SELECT l.id, l.slot_date, l.slot_time, s.name AS service_name, l.discounted_price
                FROM last_minute_slots l
                LEFT JOIN services s ON l.service_id = s.id
                WHERE l.establishment_id = ? AND l.status = 'active'
                  AND l.slot_date IN (?, ?)
                ORDER BY l.slot_date ASC, l.slot_time ASC
                LIMIT 5
            ");
            $stmt_lm->execute([$est_id, $today, $tomorrow]);
            foreach ($stmt_lm->fetchAll(PDO::FETCH_ASSOC) as $lm) {
                $key = 'lm_expiring_' . $lm['id'];
                if (!$visible($key)) continue;
                $when  = ($lm['slot_date'] === $today) ? 'Dnes' : 'Zajtra';
                $srv   = !empty($lm['service_name']) ? $lm['service_name'] : 'Služba';
                $price = number_format((float)$lm['discounted_price'], 2, ',', ' ');
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'lm_expiring',
                    'icon'    => 'bolt',
                    'title'   => 'Last Minute vyprší čoskoro',
                    'message' => "{$srv} · {$when} o " . substr($lm['slot_time'], 0, 5) . " · {$price} €",
                    'time'    => $when,
                    'is_read' => $isRead($key),
                ];
            }
        } catch (Exception $e) {}

        // ⑥ Nové recenzie za posledných 24 hodín
        try {
            $stmt_rev = $pdo->prepare("
                SELECT r.id, r.rating, r.comment, u.full_name
                FROM reviews r
                LEFT JOIN users u ON r.reviewer_id = u.id
                WHERE r.reviewee_id = ? AND r.reviewee_type = 'establishment'
                  AND r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY r.created_at DESC
                LIMIT 5
            ");
            $stmt_rev->execute([$est_id]);
            foreach ($stmt_rev->fetchAll(PDO::FETCH_ASSOC) as $rev) {
                $key = 'review_new_' . $rev['id'];
                if (!$visible($key)) continue;
                $name    = trim($rev['full_name'] ?? '') ?: 'Zákazník';
                $rating  = (int)$rev['rating'];
                $stars   = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                $snippet = mb_substr($rev['comment'] ?? '', 0, 65, 'UTF-8');
                if (mb_strlen($rev['comment'] ?? '', 'UTF-8') > 65) $snippet .= '…';
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'review',
                    'icon'    => 'star',
                    'title'   => "Nová recenzia — {$name} ({$stars})",
                    'message' => $snippet ?: 'Bez komentára.',
                    'time'    => 'Dnes',
                    'is_read' => $isRead($key),
                ];
            }
        } catch (Exception $e) {}

    } // end $est_id > 0

    // ⑦ Dnešné meniny
    try {
        $sk_file = __DIR__ . '/../libs/namedays_sk.json';
        if (file_exists($sk_file)) {
            $sk_namedays = json_decode(file_get_contents($sk_file), true) ?: [];
            $today_name  = in_array(date('m-d'), getNonNameNamedayKeys(), true) ? '' : ($sk_namedays[date('m-d')] ?? '');
            if ($today_name) {
                $key = 'nameday_' . $today;
                if ($visible($key)) {
                    $notifications[] = [
                        'key'     => $key,
                        'type'    => 'nameday',
                        'icon'    => 'cake',
                        'title'   => "Meniny dnes: {$today_name}",
                        'message' => 'Nezabudnite blahoželať alebo poslať špeciálnu ponuku Vašim zákazníkom.',
                        'time'    => 'Dnes',
                        'is_read' => $isRead($key),
                    ];
                }
            }
        }
    } catch (Exception $e) {}

    // ⑧ Blížiace sa veľké sviatky (režim "Pripomenúť vopred" v Marketing → Automatizácia)
    try {
        if ($est_id > 0) {
            foreach (getUpcomingReminders($conn, $est_id) as $r) {
                $key = 'occasion_remind_' . $r['occasion_key'] . '_' . $r['date'];
                if (!$visible($key)) continue;
                $when = $r['days_until'] === 0 ? 'Dnes' : ($r['days_until'] === 1 ? 'Zajtra' : ('O ' . $r['days_until'] . ' dní'));
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'occasion_remind',
                    'icon'    => 'celebration',
                    'title'   => "{$when} je {$r['label']}",
                    'message' => 'Pripravte si pozdrav pre zákazníkov v Marketing → Automatizácia pozdravov.',
                    'time'    => $when,
                    'is_read' => $isRead($key),
                ];
            }
        }
    } catch (Exception $e) {}

    // ⑨ Zákazník — stav Kreslo Huntera a Last Minute ponuky z obľúbených prevádzok
    if ($user_role === 'customer') {
        try {
            $u_stmt = $pdo->prepare("SELECT hunter_expires_at FROM users WHERE id = ?");
            $u_stmt->execute([$user_id]);
            $hunter_expires = $u_stmt->fetchColumn();
            $hunter_active  = $hunter_expires && strtotime($hunter_expires) > time();

            if ($hunter_active) {
                $days_left = (int)ceil((strtotime($hunter_expires) - time()) / 86400);
                if ($days_left <= 7) {
                    $key = 'hunter_expiring_' . date('Y-m-d', strtotime($hunter_expires));
                    if ($visible($key)) {
                        $notifications[] = [
                            'key'     => $key,
                            'type'    => 'lm_expiring',
                            'icon'    => 'radar',
                            'title'   => 'Kreslo Hunter čoskoro vyprší',
                            'message' => 'Vaše predplatné vyprší o ' . $days_left . ' ' . ($days_left === 1 ? 'deň' : 'dní') . '. Predĺžte si ho, aby ste nezmeškali Last Minute ponuky.',
                            'time'    => 'Dnes',
                            'link'    => 'moj_profil.php?section=hunter',
                            'is_read' => $isRead($key),
                        ];
                    }
                }
            } else {
                $key = 'hunter_upsell';
                if ($visible($key)) {
                    $notifications[] = [
                        'key'     => $key,
                        'type'    => 'lm_expiring',
                        'icon'    => 'radar',
                        'title'   => 'Nezmeškajte Last Minute ponuky',
                        'message' => 'Aktivujte si Kreslo Hunter (0,90 €/mes.) a dostávajte okamžité upozornenia na uvoľnené termíny u vašich obľúbených prevádzok.',
                        'time'    => '',
                        'link'    => 'moj_profil.php?section=hunter',
                        'is_read' => $isRead($key),
                    ];
                }
            }
        } catch (Exception $e) {}

        try {
            $fav_stmt = $pdo->prepare("
                SELECT l.id, l.slot_date, l.slot_time, l.discounted_price, l.original_price,
                       s.name AS service_name, e.name AS est_name, e.public_id AS est_public_id
                FROM last_minute_slots l
                JOIN favorites f ON f.establishment_id = l.establishment_id
                JOIN establishments e ON e.id = l.establishment_id
                JOIN services s ON s.id = l.service_id
                WHERE f.user_id = ? AND l.status = 'active' AND l.slot_date >= CURDATE()
                ORDER BY l.slot_date ASC, l.slot_time ASC
                LIMIT 5
            ");
            $fav_stmt->execute([$user_id]);
            foreach ($fav_stmt->fetchAll(PDO::FETCH_ASSOC) as $slot) {
                $key = 'lm_slot_' . $slot['id'];
                if (!$visible($key)) continue;
                $when = date('d.m.', strtotime($slot['slot_date'])) . ' ' . substr($slot['slot_time'], 0, 5);
                $notifications[] = [
                    'key'     => $key,
                    'type'    => 'lm_expiring',
                    'icon'    => 'bolt',
                    'title'   => 'Last Minute: ' . $slot['est_name'],
                    'message' => $slot['service_name'] . ' — ' . $when . ' za ' . number_format((float)$slot['discounted_price'], 2) . ' € (bežne ' . number_format((float)$slot['original_price'], 2) . ' €)',
                    'time'    => $when,
                    'link'    => !empty($slot['est_public_id']) ? ('business_page.php?id=' . $slot['est_public_id']) : null,
                    'is_read' => $isRead($key),
                ];
            }
        } catch (Exception $e) {}
    }

    // Spočítať neprečítané
    $unread_count = 0;
    foreach ($notifications as $n) {
        if (empty($n['is_read'])) $unread_count++;
    }

    echo json_encode([
        'success'       => true,
        'notifications' => $notifications,
        'unread_count'  => $unread_count,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
