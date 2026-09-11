<?php
require_once '../config.php';
require_once __DIR__ . '/../includes/ics_helper.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    http_response_code(400);
    exit('Chýba token.');
}

$stmt = $conn->prepare("SELECT b.*, s.name as service_name, s.duration_minutes, e.name as establishment_name, e.address, e.city
                         FROM bookings b
                         LEFT JOIN services s ON s.id = b.service_id
                         LEFT JOIN establishments e ON e.id = b.establishment_id
                         WHERE b.manage_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    http_response_code(404);
    exit('Rezervácia sa nenašla.');
}

$ics = generate_ics_content([
    'uid' => 'booking-' . $booking['id'],
    'service_name' => $booking['service_name'] ?: 'Rezervovaná služba',
    'establishment_name' => $booking['establishment_name'],
    'location' => trim(($booking['address'] ?? '') . ', ' . ($booking['city'] ?? ''), ', '),
    'date' => $booking['booking_date'],
    'time' => substr($booking['start_time'], 0, 5),
    'duration' => $booking['duration_minutes'] ?: 30
]);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="rezervacia.ics"');
header('Content-Length: ' . strlen($ics));
echo $ics;
