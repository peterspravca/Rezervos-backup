<?php
/**
 * CRM Pripomienkovač Termínov (Cron) - UPRAVENÁ VERZIA (len pre OFFICE)
 * Tento skript hľadá termíny naplánované na zajtra a posiela sumárny e-mail do firmy.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/mailer.php';

// Ak sa spúšťa cez prehliadač, vyžadujeme prihlásenie alebo špeciálny klúč
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/auth.php';
    if (!isset($_SESSION['user_id']) && ($_GET['key'] ?? '') !== 'vueto_secret_77') {
        die("E-mail pripomienky je možné spustiť len zo systému alebo cez CRON.");
    }
}

$pdo = db_connect();
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Hľadáme všetky dôležité termíny na zajtra
$stmt = $pdo->prepare("SELECT l.*, u.full_name as admin_name 
    FROM leads l 
    LEFT JOIN crm_users u ON l.assigned_to = u.id
    WHERE (l.survey_date = :tomorrow 
       OR l.production_at = :tomorrow 
       OR l.realization_date = :tomorrow 
       OR l.handover_date = :tomorrow 
       OR l.next_followup = :tomorrow)
    AND l.status != 'ukonceny' AND l.status != 'zamietnuty'");

$stmt->execute(['tomorrow' => $tomorrow]);
$leads = $stmt->fetchAll();

if (empty($leads)) {
    echo "Na zajtra ($tomorrow) nie sú naplánované žiadne dôležité termíny.";
    exit;
}

$office_tasks = [];

foreach ($leads as $l) {
    // Identifikácia typu termínu pre tento lead
    $milestones = [];
    if ($l['survey_date'] === $tomorrow) $milestones[] = "Obhliadka (" . ($l['survey_time'] ?? 'Čas neuvedený') . ")";
    if ($l['production_at'] === $tomorrow) $milestones[] = "Výroba";
    if ($l['realization_date'] === $tomorrow) $milestones[] = "Realizácia (" . ($l['realization_time'] ?? 'Čas neuvedený') . ")";
    if ($l['handover_date'] === $tomorrow) $milestones[] = "Odovzdanie";
    if ($l['next_followup'] === $tomorrow) $milestones[] = "Follow-up (" . ($l['next_followup_time'] ?? '-') . ")";

    $office_tasks[] = [
        'client' => $l['name'],
        'phone'  => $l['phone'] ?? 'Neuvedený',
        'city'   => $l['city'] ?? '-',
        'assigned' => $l['admin_name'] ?? 'Nikto',
        'milestones' => implode(", ", $milestones),
        'url'    => "https://" . $_SERVER['HTTP_HOST'] . "/contact.php?id=" . $l['id']
    ];
}

// POSIELANIE DO OFFICE (Sumár všetkého)
if (defined('OFFICE_EMAIL') && !empty($office_tasks)) {
    $subject = "DENNÝ SUMÁR CRM: Plán na zajtra (" . date('d.m.Y', strtotime($tomorrow)) . ")";
    $html = "<p>Dobrý deň,</p><p>tu je sumárny prehľad termínov v teréne na zajtrajší deň:</p>";
    
    foreach ($office_tasks as $t) {
        $html .= "<div style='border-bottom: 2px solid #eee; padding: 15px 0; margin-bottom: 10px;'>";
        $html .= "<strong>" . htmlspecialchars($t['client']) . "</strong> (" . htmlspecialchars($t['city']) . ")<br>";
        $html .= "📅 " . $t['milestones'] . "<br>";
        $html .= "👤 Zodpovedný: <strong>" . htmlspecialchars($t['assigned']) . "</strong><br>";
        $html .= "📞 Kontakt: <a href='tel:" . $t['phone'] . "'>" . htmlspecialchars($t['phone']) . "</a><br>";
        $html .= "<a href='" . $t['url'] . "' style='font-weight:bold; color:#2563eb; display:inline-block; margin-top:5px;'>Otvoriť v CRM</a>";
        $html .= "</div>";
    }
    
    send_crm_notification(OFFICE_EMAIL, $subject, wrap_email_content("Denný sumár úloh", $html));
    echo "Odoslaný sumár do OFFICE: " . OFFICE_EMAIL . "<br>";
}

echo "Hotovo. Pripomienky spracované (odoslané len na firemný mail).";
