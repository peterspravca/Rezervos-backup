<?php
/**
 * Ranný sumár aktivít (Cron script)
 * Posiela prehľad na dnes naplánovaných úloh a nových dopyov.
 * Spúšťať ideálne každý pracovný deň ráno (napr. o 7:00).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/mailer.php';

// Zabezpečenie - overenie tajného kľúča v URL
$secret_key = "vueto_cron_7722";
if (($_GET['secret'] ?? '') !== $secret_key) {
    http_response_code(403);
    exit("Prístup zamietnutý. Nesprávny kľúč.\n");
}

// 1. Skontrolujeme, či je pracovný deň (1 = Pondelok, 5 = Piatok)
$day_of_week = date('N');
if ($day_of_week > 5) {
    exit("Dnes je víkend, sumár sa neposiela.\n");
}

$pdo = db_connect();

// Nastavenia - komu poslať sumár
$recipients = ['info@vueto.sk']; 

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Ak je piatok, pozeráme sa dopredu na celý víkend (piatok + sobota + nedeľa)
$look_ahead_days = ($day_of_week == 5) ? 2 : 0; 
$end_date = date('Y-m-d', strtotime("+$look_ahead_days days"));

// 2. Získame dáta pre sumár
$surveys = $pdo->prepare("SELECT id, name, survey_date, survey_time, city FROM leads WHERE survey_date BETWEEN ? AND ? ORDER BY survey_date ASC, survey_time ASC");
$surveys->execute([$today, $end_date]);
$surveys = $surveys->fetchAll();

$realizations = $pdo->prepare("SELECT id, name, realization_date, realization_time, city FROM leads WHERE realization_date BETWEEN ? AND ? ORDER BY realization_date ASC, realization_time ASC");
$realizations->execute([$today, $end_date]);
$realizations = $realizations->fetchAll();

$followups = $pdo->prepare("SELECT id, name, next_followup, next_followup_time, phone FROM leads WHERE next_followup BETWEEN ? AND ? ORDER BY next_followup ASC, next_followup_time ASC");
$followups->execute([$today, $end_date]);
$followups = $followups->fetchAll();

$new_leads = $pdo->prepare("SELECT id, name, created_at, email FROM leads WHERE (created_at >= ? OR status = 'novy') AND status != 'zamietnuty' AND assigned_to IS NULL ORDER BY created_at DESC");
$new_leads->execute([$yesterday . ' 00:00:00']);
$new_leads = $new_leads->fetchAll();

// 3. Skontrolujeme, či máme čo posielať
if (empty($surveys) && empty($realizations) && empty($followups) && empty($new_leads)) {
    exit("Dnes nie je žiadna aktivita na sumár.\n");
}

// 4. Vygenerujeme HTML obsah mailu
$html = "<h2>Dobré ráno! ☕</h2>";
$html .= "<p>Tu je prehľad aktivít na dnešný deň (<strong>" . date('d.m.Y') . "</strong>):</p>";

if (!empty($surveys)) {
    $html .= "<div style='margin-bottom:20px; border-left:4px solid #2563eb; padding-left:15px;'>";
    $html .= "<h3 style='color:#2563eb;'>🔍 Dnešné a víkendové obhliadky (" . count($surveys) . ")</h3><ul>";
    foreach ($surveys as $s) {
        $date_label = (date('Y-m-d', strtotime($s['survey_date'])) !== $today) ? date('d.m.', strtotime($s['survey_date'])) . " " : "";
        $html .= "<li><strong>" . $date_label . substr($s['survey_time'], 0, 5) . "</strong> - " . htmlspecialchars($s['name']) . " (" . htmlspecialchars($s['city']) . ") - <a href='https://vueto.sk/nastenka/contact.php?id=" . $s['id'] . "'>Otvoriť v CRM</a></li>";
    }
    $html .= "</ul></div>";
}

if (!empty($realizations)) {
    $html .= "<div style='margin-bottom:20px; border-left:4px solid #10b981; padding-left:15px;'>";
    $html .= "<h3 style='color:#10b981;'>🏗️ Dnešné a víkendové realizácie (" . count($realizations) . ")</h3><ul>";
    foreach ($realizations as $r) {
        $date_label = (date('Y-m-d', strtotime($r['realization_date'])) !== $today) ? date('d.m.', strtotime($r['realization_date'])) . " " : "";
        $html .= "<li><strong>" . $date_label . substr($r['realization_time'], 0, 5) . "</strong> - " . htmlspecialchars($r['name']) . " (" . htmlspecialchars($r['city']) . ") - <a href='https://vueto.sk/nastenka/contact.php?id=" . $r['id'] . "'>Otvoriť v CRM</a></li>";
    }
    $html .= "</ul></div>";
}

if (!empty($followups)) {
    $html .= "<div style='margin-bottom:20px; border-left:4px solid #d97706; padding-left:15px;'>";
    $html .= "<h3 style='color:#d97706;'>📞 Dnešné a víkendové pripomienky (" . count($followups) . ")</h3><ul>";
    foreach ($followups as $f) {
        $date_label = (date('Y-m-d', strtotime($f['next_followup'])) !== $today) ? date('d.m.', strtotime($f['next_followup'])) . " " : "";
        $html .= "<li><strong>" . $date_label . substr($f['next_followup_time'], 0, 5) . "</strong> - " . htmlspecialchars($f['name']) . " (" . htmlspecialchars($f['phone']) . ") - <a href='https://vueto.sk/nastenka/contact.php?id=" . $f['id'] . "'>Otvoriť v CRM</a></li>";
    }
    $html .= "</ul></div>";
}

if (!empty($new_leads)) {
    $html .= "<div style='margin-bottom:20px; border-left:4px solid #6366f1; padding-left:15px;'>";
    $html .= "<h3 style='color:#6366f1;'>✨ Nové nepridelené dopyty (" . count($new_leads) . ")</h3><ul>";
    foreach ($new_leads as $nl) {
        $html .= "<li>" . htmlspecialchars($nl['name']) . " (" . date('d.m. H:i', strtotime($nl['created_at'])) . ") - <a href='https://vueto.sk/nastenka/contact.php?id=" . $nl['id'] . "'>Otvoriť v CRM</a></li>";
    }
    $html .= "</ul></div>";
}

$html .= "<hr style='border:0; border-top:1px solid #eee; margin:20px 0;'>";
$html .= "<p style='font-size:12px; color:#666;'>Tento mail bol automaticky vygenerovaný systémom VUETO CRM.</p>";

foreach ($recipients as $to) {
    send_crm_notification($to, "Ranný sumár aktivít - " . date('d.m.Y'), wrap_email_content("Ranný sumár", $html));
}

echo "Sumár spracovaný.\n";
