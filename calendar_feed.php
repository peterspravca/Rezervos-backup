<?php
// calendar_feed.php — Fáza 4: univerzálny jednosmerný .ics kalendárový odber (Rezervos -> kalendár).
// Funguje pre Google, Apple, Outlook, Thunderbird, Windows aj Samsung Kalendár rovnako — všetky vedia
// "prihlásiť sa na odber" štandardného iCalendar feedu, žiadne OAuth ani appka-špecifické API netreba.
require_once __DIR__ . '/config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

// Self-migrácia: tajný token prevádzky pre prístup k jej kalendárovému feedu (bez prihlásenia — kalendárové
// appky odkaz len periodicky sťahujú, nevedia sa prihlásiť).
try { $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS calendar_feed_token VARCHAR(40) DEFAULT NULL"); } catch (Exception $e) {}

$token = trim($_GET['token'] ?? '');
$employee_id = (int)($_GET['employee'] ?? 0);

header('Content-Type: text/calendar; charset=utf-8');

if (empty($token)) {
    http_response_code(403);
    echo "Chýba prístupový token.";
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM establishments WHERE calendar_feed_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$est = $stmt->get_result()->fetch_assoc();

if (!$est) {
    http_response_code(403);
    echo "Neplatný alebo expirovaný odkaz na kalendár.";
    exit;
}

$est_id = (int)$est['id'];
$cal_name = $est['name'] ?: BRAND_NAME;

$sql = "
    SELECT b.id, b.booking_date, b.start_time, b.end_time, b.status, b.customer_note,
           s.name AS service_name, u.full_name AS customer_name, e.name AS employee_name
    FROM bookings b
    LEFT JOIN services s ON s.id = b.service_id
    LEFT JOIN users u ON u.id = b.customer_id
    LEFT JOIN employees e ON e.id = b.employee_id
    WHERE b.establishment_id = ?
      AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
      AND b.booking_date <= DATE_ADD(CURDATE(), INTERVAL 180 DAY)
";
$types = "i";
$params = [$est_id];
if ($employee_id > 0) {
    $sql .= " AND b.employee_id = ?";
    $types .= "i";
    $params[] = $employee_id;
}
$sql .= " ORDER BY b.booking_date ASC, b.start_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function ics_escape($text) {
    $text = (string)$text;
    $text = str_replace(["\\", ";", ",", "\n"], ["\\\\", "\\;", "\\,", "\\n"], $text);
    return $text;
}

function ics_dt($date, $time) {
    return date('Ymd\THis', strtotime("$date $time"));
}

$lines = [];
$lines[] = "BEGIN:VCALENDAR";
$lines[] = "VERSION:2.0";
$lines[] = "PRODID:-//" . BRAND_NAME . "//Kalendarovy feed//SK";
$lines[] = "CALSCALE:GREGORIAN";
$lines[] = "METHOD:PUBLISH";
$lines[] = "X-WR-CALNAME:" . ics_escape($cal_name . ' - ' . BRAND_NAME);
$lines[] = "X-WR-TIMEZONE:Europe/Bratislava";
$lines[] = "REFRESH-INTERVAL;VALUE=DURATION:PT30M";
$lines[] = "X-PUBLISHED-TTL:PT30M";

foreach ($bookings as $b) {
    $dtstart = ics_dt($b['booking_date'], $b['start_time']);
    $dtend = ics_dt($b['booking_date'], $b['end_time'] ?: $b['start_time']);
    $status = ($b['status'] === 'cancelled') ? 'CANCELLED' : 'CONFIRMED';
    $summary = trim(($b['service_name'] ?: 'Rezervácia') . ($b['customer_name'] ? ' - ' . $b['customer_name'] : ''));
    $descParts = [];
    if ($b['employee_name']) $descParts[] = 'Pracovník: ' . $b['employee_name'];
    if ($b['customer_note']) $descParts[] = 'Poznámka: ' . $b['customer_note'];

    $lines[] = "BEGIN:VEVENT";
    $lines[] = "UID:booking-" . $b['id'] . "@" . BRAND_SITE;
    $lines[] = "DTSTAMP:" . gmdate('Ymd\THis\Z');
    $lines[] = "DTSTART:" . $dtstart;
    $lines[] = "DTEND:" . $dtend;
    $lines[] = "SUMMARY:" . ics_escape($summary);
    if ($descParts) $lines[] = "DESCRIPTION:" . ics_escape(implode('\n', $descParts));
    $lines[] = "STATUS:" . $status;
    $lines[] = "END:VEVENT";
}

$lines[] = "END:VCALENDAR";

echo implode("\r\n", $lines);
