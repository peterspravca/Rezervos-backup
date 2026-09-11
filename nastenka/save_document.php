<?php
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$pdo = db_connect();

$lead_id = (int)($_POST['lead_id'] ?? 0);
$type    = $_POST['type'] ?? '';
$title   = $_POST['title'] ?? '';
$content = $_POST['content'] ?? ''; // This should be JSON string from JS

if (!$lead_id || !$type || !$content) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$token = $_POST['token'] ?? '';
$status = $_POST['status'] ?? 'sent';
$is_update = !empty($token);

if (!$is_update) {
    $token = bin2hex(random_bytes(16));
}

$doc_number = $_POST['doc_number'] ?? '';

try {
    if ($is_update) {
        $stmt = $pdo->prepare("UPDATE crm_generated_documents SET title = ?, content_json = ?, status = ?, doc_number = ?, created_at = NOW() WHERE token = ? AND lead_id = ?");
        $stmt->execute([$title, $content, $status, $doc_number, $token, $lead_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO crm_generated_documents (lead_id, token, type, title, content_json, status, doc_number, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$lead_id, $token, $type, $title, $content, $status, $doc_number]);
    }
    
    // Add a note to the lead
    $type_label = '';
    switch($type) {
        case 'offer': $type_label = 'Cenová ponuka'; break;
        case 'order': $type_label = 'Objednávka'; break;
        case 'proforma': $type_label = 'Zálohová faktúra'; break;
        case 'delivery': $type_label = 'Dodací list'; break;
        case 'protocol': $type_label = 'Preberací protokol'; break;
        case 'receipt': $type_label = 'Príjmový doklad'; break;
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $public_url = $protocol . $host . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/portal.php?t=" . $token;

    $user = current_user();
    $note = "Vygenerovaný dokument: **$type_label** ($title). Odkaz pre klienta: $public_url";
    
    // Odoslať e-mail klientovi, ak je status 'sent' A je to explicitne vyžiadané (cez tlačidlo 'Cez mail')
    $mail_status_note = "";
    $should_send_email = ($_POST['send_email'] ?? '0') === '1';

    if ($status === 'sent' && $should_send_email) {
        require_once __DIR__ . '/libs/mailer.php';
        
        // Získať údaje o klientovi
        $l_stmt = $pdo->prepare("SELECT name, email FROM leads WHERE id = ?");
        $l_stmt->execute([$lead_id]);
        $lead_data = $l_stmt->fetch();
        
        if ($lead_data && !empty($lead_data['email'])) {
            $subject = "$type_label: " . ($doc_number ?: $title) . " - VUETO";
            
            $html_content = "<p>Dobrý deň <strong>" . htmlspecialchars($lead_data['name']) . "</strong>,</p>";
            $html_content .= "<p>Pripravili sme pre Vás dôležitý dokument: <strong>" . htmlspecialchars($type_label) . "</strong>.</p>";
            $html_content .= "<p>Dokument si môžete kedykoľvek zobraziť, stiahnuť alebo digitálne podpísať na odkaze nižšie:</p>";
            
            $mail_res = send_crm_notification(
                $lead_data['email'], 
                $subject, 
                wrap_email_content($type_label, $html_content, $public_url)
            );
            
            if ($mail_res === true) {
                $mail_status_note = " (✅ Odoslané mailom na " . $lead_data['email'] . ")";
            } else {
                $mail_status_note = " (❌ Chyba odosielania mailu: $mail_res)";
            }
        } else {
            $mail_status_note = " (⚠️ Mail neodoslaný - chýba adresa klienta)";
        }
    }

    $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content, created_at) VALUES (?, ?, 'system_silent', ?, NOW())")
        ->execute([$lead_id, $user['id'], $note . $mail_status_note]);


    echo json_encode([
        'success' => true, 
        'token'   => $token,
        'url'     => $public_url
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
