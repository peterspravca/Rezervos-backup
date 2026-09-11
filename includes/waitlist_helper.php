<?php
/**
 * Čakacia listina — keď sa niekde zruší rezervácia, skontroluje, či niekto čaká
 * na voľný termín v ten istý deň, a ak áno, pošle mu e-mail s odkazom na rezerváciu.
 * Volá sa po každom zrušení (zákazníkom aj prevádzkou).
 */
require_once __DIR__ . '/availability_helper.php';

if (!function_exists('ensureWaitlistTable')) {
    function ensureWaitlistTable($conn) {
        $conn->query("CREATE TABLE IF NOT EXISTS waitlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            establishment_id INT NOT NULL,
            service_ids VARCHAR(255) NOT NULL,
            preferred_date DATE NOT NULL,
            status ENUM('waiting','notified','cancelled') NOT NULL DEFAULT 'waiting',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lookup (establishment_id, preferred_date, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

if (!function_exists('notifyWaitlistForFreedSlot')) {
    function notifyWaitlistForFreedSlot($conn, $establishment_id, $date) {
        ensureWaitlistTable($conn);

        $stmt = $conn->prepare("SELECT w.*, u.email, u.full_name FROM waitlist w
                                 JOIN users u ON u.id = w.customer_id
                                 WHERE w.establishment_id = ? AND w.preferred_date = ? AND w.status = 'waiting'");
        $stmt->bind_param("is", $establishment_id, $date);
        $stmt->execute();
        $entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($entries)) { return; }

        $est_stmt = $conn->prepare("SELECT name FROM establishments WHERE id = ?");
        $est_stmt->bind_param("i", $establishment_id);
        $est_stmt->execute();
        $est_name = $est_stmt->get_result()->fetch_assoc()['name'] ?? 'prevádzka';

        @include_once __DIR__ . '/../api/mailer.php';
        if (!function_exists('sendWaitlistNotification')) { return; }

        $root_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'rezervos.eu');

        foreach ($entries as $entry) {
            $service_ids = json_decode($entry['service_ids'], true) ?: [];
            if (empty($service_ids)) { continue; }

            $error = null;
            $slots = computeAvailableSlots($conn, $establishment_id, $service_ids, $date, 0, $error);
            if ($error || empty($slots)) { continue; }

            $s_stmt = $conn->prepare("SELECT name FROM services WHERE id = ? LIMIT 1");
            $first_sid = (int)$service_ids[0];
            $s_stmt->bind_param("i", $first_sid);
            $s_stmt->execute();
            $service_name = $s_stmt->get_result()->fetch_assoc()['name'] ?? 'Rezervovaná služba';

            $booking_url = $root_url . '/profil.php?id=' . $establishment_id;

            @sendWaitlistNotification($entry['email'], $entry['full_name'], $est_name, $service_name, $date, $slots[0], $booking_url);

            $upd = $conn->prepare("UPDATE waitlist SET status = 'notified' WHERE id = ?");
            $upd->bind_param("i", $entry['id']);
            $upd->execute();
        }
    }
}
