<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/libs/mailer.php';
require_login();

$pdo  = db_connect();
$user = current_user();
$csrf = csrf_token();

$id   = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Auto-migrate schema silently to ensure all columns exist
$cols_to_add = [
    "address"   => "VARCHAR(255) NULL AFTER phone",
    "company"   => "VARCHAR(255) NULL AFTER address",
    "ico"       => "VARCHAR(20) NULL AFTER company",
    "tax_id"    => "VARCHAR(30) NULL AFTER ico",
    "apartment" => "VARCHAR(20) NULL AFTER address",
    "zip"       => "VARCHAR(10) NULL AFTER city",
    "source"    => "ENUM('web', 'manual') DEFAULT 'manual' AFTER created_at",
    "intent"    => "VARCHAR(50) DEFAULT 'unknown' AFTER source",
    "gender"    => "ENUM('male', 'female', 'other', 'unknown') DEFAULT 'unknown' AFTER type"
];

foreach ($cols_to_add as $col => $definition) {
    try {
        $pdo->exec("ALTER TABLE leads ADD COLUMN $col $definition");
    } catch (PDOException $e) {
        // Column probably exists, ignore
    }
}
try {
    $pdo->exec("ALTER TABLE leads MODIFY COLUMN gender ENUM('male', 'female', 'other', 'unknown') DEFAULT 'unknown'");
    $pdo->exec("ALTER TABLE leads MODIFY COLUMN status ENUM('novy', 'prideleny', 'kontaktovany_email', 'kontaktovany_telefon', 'kontaktovany_oboje', 'v_procese', 'ukonceny', 'zamietnuty', 'zakazka') DEFAULT 'novy'");
} catch(PDOException $e) {}


$lead = $pdo->prepare("SELECT l.*, u.full_name AS assigned_name
    FROM leads l LEFT JOIN crm_users u ON l.assigned_to = u.id WHERE l.id = ?");
$lead->execute([$id]);
$lead = $lead->fetch();
if (!$lead) { header('Location: index.php'); exit; }

// Notes / activity
$notes = $pdo->prepare("SELECT n.*, u.full_name, u.role FROM crm_notes n
    JOIN crm_users u ON n.user_id = u.id WHERE n.lead_id = ? ORDER BY n.created_at DESC");
$notes->execute([$id]);
$notes = $notes->fetchAll();

// Users for assign
$users = $pdo->query("SELECT id, full_name FROM crm_users WHERE is_active=1 AND full_name != 'Administrátor' ORDER BY full_name")->fetchAll();

// Shared documents for this lead
$shared_docs = $pdo->prepare("SELECT * FROM crm_generated_documents WHERE lead_id = ? ORDER BY created_at DESC");
$shared_docs->execute([$id]);
$shared_docs = $shared_docs->fetchAll();

// ── POST actions ──
$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $upd_name      = trim($_POST['name'] ?? '');
        $upd_email     = trim($_POST['email'] ?? '');
        $upd_phone     = trim($_POST['phone'] ?? '');
        $upd_address   = trim($_POST['address'] ?? '');
        $upd_apartment = trim($_POST['apartment'] ?? '');
        $upd_company   = trim($_POST['company'] ?? '');
        $upd_ico       = trim($_POST['ico'] ?? '');
        $upd_tax_id    = trim($_POST['tax_id'] ?? '');
        $upd_city      = trim($_POST['city'] ?? '');
        $upd_zip       = trim($_POST['zip'] ?? '');
        $upd_interest  = trim($_POST['interest'] ?? '');
        $upd_type      = $_POST['type'] ?? 'prospect';
        $upd_gender    = $_POST['gender'] ?? 'unknown';
        
        
        try {
            $pdo->prepare("UPDATE leads SET name=?, email=?, phone=?, address=?, apartment=?, company=?, ico=?, tax_id=?, city=?, zip=?, interest=?, type=?, gender=?, updated_at=NOW() WHERE id=?")
                ->execute([$upd_name, $upd_email, $upd_phone, $upd_address, $upd_apartment, $upd_company, $upd_ico, $upd_tax_id, $upd_city, $upd_zip, $upd_interest, $upd_type, $upd_gender, $id]);
        } catch (PDOException $e) {
            // Log error but redirect with error flag
            header("Location: contact.php?id=$id&err=db&msg=" . urlencode($e->getMessage()));
            exit;
        }
            
        $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
            ->execute([$id, $user['id'], 'system', "Základné údaje klienta (meno, kontakt, firma, adresa) boli upravené."]);
            
        header("Location: contact.php?id=$id&ok=1");
        exit;
    }

    if ($action === 'update_status') {
        $status   = $_POST['status'] ?? $lead['status'];
        $priority = $_POST['priority'] ?? $lead['priority'];
        $assigned = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $followup = !empty($_POST['next_followup']) ? $_POST['next_followup'] : null;
        $followup_time = !empty($_POST['next_followup_time']) ? $_POST['next_followup_time'] : null;
        $survey_date = !empty($_POST['survey_date']) ? $_POST['survey_date'] : null;
        $survey_time = !empty($_POST['survey_time']) ? $_POST['survey_time'] : null;
        $production  = !empty($_POST['production_at']) ? $_POST['production_at'] : null;
        $realization = !empty($_POST['realization_date']) ? $_POST['realization_date'] : null;
        $realization_time = !empty($_POST['next_followup_time_real']) ? $_POST['next_followup_time_real'] : (!empty($_POST['realization_time']) ? $_POST['realization_time'] : null);
        $deal     = !empty($_POST['deal_value']) ? (float)$_POST['deal_value'] : null;
        $order_num = !empty($_POST['order_number']) ? trim($_POST['order_number']) : $lead['order_number'];
        $quote_p   = !empty($_POST['quote_price']) ? (float)$_POST['quote_price'] : $lead['quote_price'];
        $invoice_n = !empty($_POST['invoice_number']) ? trim($_POST['invoice_number']) : $lead['invoice_number'];
        $survey_n  = !empty($_POST['survey_notes']) ? trim($_POST['survey_notes']) : $lead['survey_notes'];
        $handover  = !empty($_POST['handover_date']) ? $_POST['handover_date'] : $lead['handover_date'];
        $city      = !empty($_POST['city']) ? trim($_POST['city']) : $lead['city'];
        $deposit_p = isset($_POST['is_deposit_paid']) ? 1 : 0;
        $deposit_a = !empty($_POST['deposit_amount']) ? (float)$_POST['deposit_amount'] : $lead['deposit_amount'];

        // If switching to 'zakazka', check address
        if ($status === 'zakazka' && empty($lead['address'])) {
            header("Location: contact.php?id=$id&err=address_required");
            exit;
        }

        // Generate order number if not exists
        if ($status === 'zakazka' && empty($order_num)) {
            $year = date('Y');
            $last = $pdo->prepare("SELECT order_number FROM leads WHERE order_number LIKE ? ORDER BY order_number DESC LIMIT 1");
            $last->execute([$year . '%']);
            $last_val = $last->fetchColumn();
            if ($last_val) {
                $num = (int)substr($last_val, 4) + 1;
            } else {
                $num = 1;
            }
            $order_num = $year . str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        // Auto-switch type to customer if status is 'zakazka' or 'ukonceny'
        $type = $lead['type'];
        if ($status === 'zakazka' || $status === 'ukonceny') {
            $type = 'customer';
        }

        $pdo->prepare("UPDATE leads SET 
            status=?, priority=?, assigned_to=?, 
            next_followup=?, next_followup_time=?,
            survey_date=?, survey_time=?,
            production_at=?, 
            realization_date=?, realization_time=?,
            deal_value=?, order_number=?, city=?,
            quote_price=?, invoice_number=?,
            survey_notes=?, handover_date=?,
            is_deposit_paid=?, deposit_amount=?,
            type=?, updated_at=NOW() WHERE id=?")
            ->execute([
                $status, $priority, $assigned, 
                $followup, $followup_time,
                $survey_date, $survey_time,
                $production,
                $realization, $realization_time,
                $deal, $order_num, $city,
                $quote_p, $invoice_n,
                $survey_n, $handover,
                $deposit_p, $deposit_a,
                $type, $id
            ]);

        // --- ZÁPIS DO HISTÓRIE (DETEKCIA ZMIEN) ---
        $changes = [];
        if ($status !== $lead['status']) {
            $changes[] = "🔄 Stav: " . status_label($lead['status']) . " → " . status_label($status);
        }
        if ($assigned != $lead['assigned_to']) {
            $old_u = 'Nepridelený'; $new_u = 'Nepridelený';
            foreach($users as $u) {
                if ($u['id'] == $lead['assigned_to']) $old_u = $u['full_name'];
                if ($u['id'] == $assigned) $new_u = $u['full_name'];
            }
            $changes[] = "👤 Kolega: $old_u → $new_u";
        }
        if ($survey_date !== $lead['survey_date']) {
            $changes[] = "🔍 Obhliadka: " . ($lead['survey_date'] ? date('d.m.Y', strtotime($lead['survey_date'])) : '—') . " → " . ($survey_date ? date('d.m.Y', strtotime($survey_date)) : '—');
        }
        if ($realization_date !== $lead['realization_date']) {
            $changes[] = "🏗️ Realizácia: " . ($lead['realization_date'] ? date('d.m.Y', strtotime($lead['realization_date'])) : '—') . " → " . ($realization_date ? date('d.m.Y', strtotime($realization_date)) : '—');
        }
        if ($followup !== $lead['next_followup']) {
            $changes[] = "📞 Spätné volanie: " . ($lead['next_followup'] ? date('d.m.Y', strtotime($lead['next_followup'])) : '—') . " → " . ($followup ? date('d.m.Y', strtotime($followup)) : '—');
        }

        if (!empty($changes)) {
            $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                ->execute([$id, $user['id'], 'system', implode("\n", $changes)]);
        }

        // --- AUTOMATIC STATUS UPGRADE ---
        // Ak bol stav 'novy' a pridelili sme kolegu, prepneme na 'prideleny'
        if ($lead['status'] === 'novy' && $assigned && $status === 'novy') {
            $pdo->prepare("UPDATE leads SET status='prideleny' WHERE id=?")->execute([$id]);
            $status = 'prideleny';
        }


        // --- AUTOMATICKÉ NOTIFIKÁCIE ---
        $notifications_sent = [];
        $admin_notifications_sent = [];
        
        $portal_token = $pdo->prepare("SELECT token FROM crm_generated_documents WHERE lead_id = ? ORDER BY created_at DESC LIMIT 1");
        $portal_token->execute([$id]);
        $token_val = $portal_token->fetchColumn();
        $prot = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $portal_url = $token_val ? ($prot . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/portal.php?t=" . $token_val) : "";

        // Získanie údajov priradeného admina
        $admin_email = ""; $admin_name = "";
        if ($assigned) {
            $adm_stmt = $pdo->prepare("SELECT email, full_name FROM crm_users WHERE id = ?");
            $adm_stmt->execute([$assigned]);
            $adm_row = $adm_stmt->fetch();
            if ($adm_row) {
                $admin_email = $adm_row['email'];
                $admin_name = $adm_row['full_name'];
            }
        }

        // 1. Zmena STATUSU (Dôležité stavy)
        $major_statuses = ['v_procese', 'zakazka', 'ukonceny'];
        if ($status !== $lead['status']) {
            // Záznam do logu
            $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                ->execute([$id, $user['id'], 'system', "🚩 STAV ZMENENÝ: " . status_label($lead['status']) . " → " . status_label($status)]);
            
            // 1. Notifikácia KLIENTOVI
            if (in_array($status, $major_statuses) && !empty($lead['email'])) {
                $subject = "Aktualizácia stavu Vašej objednávky - VUETO";
                $html = "<p>Dobrý deň <strong>" . htmlspecialchars($lead['name']) . "</strong>,</p>";
                $html .= "<p>Radi by sme Vás informovali, že Vaša objednávka sa posunula do nového stavu:</p>";
                $html .= "<div class='status-box'><strong>" . status_label($status) . "</strong></div>";
                $html .= "<p>Celý priebeh a dôležité dokumenty môžete kedykoľvek sledovať na Vašom klientskom portáli.</p>";
                
                $mail_res = send_crm_notification($lead['email'], $subject, wrap_email_content("Zmena stavu objednávky", $html, $portal_url));
                if ($mail_res === true) $notifications_sent[] = "E-mail klientovi (Stav)";
            }

            // 2. Notifikácia do OFFICE a ZVONČEKA
            $prot = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $link = $prot . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/contact.php?id=$id";
            
            // In-app notifikácia vždy
            $notif_title = "Zmena statusu klienta";
            $notif_msg = "Prišlo k zmene u vyžiadania <strong>" . htmlspecialchars($lead['name']) . "</strong>. Nový status: <strong>" . status_label($status) . "</strong>";
            create_notification(0, $notif_title, $notif_msg, $link, 'ti-flag', $user['id']);
            $admin_notifications_sent[] = "In-App notifikácia (Stav)";

            // E-mail do OFFICE len pri dôležitých zmenách
            $office_status_triggers = ['zakazka', 'v_procese', 'ukonceny'];
            if (defined('OFFICE_EMAIL') && in_array($status, $office_status_triggers)) {
                $adm_subject = "CRM INFO: Dôležitá zmena stavu (Klient: " . $lead['name'] . " -> " . status_label($status) . ")";
                $adm_html = "<p>Dobrý deň,</p><p>V CRM prišlo k dôležitej zmene stavu u klienta:</p>";
                $adm_html .= "<div class='status-box'>";
                $adm_html .= "Klient: <strong>" . htmlspecialchars($lead['name']) . "</strong><br>";
                $adm_html .= "Nový status: <strong>" . status_label($status) . "</strong>";
                $adm_html .= "</div>";
                $adm_html .= "<p>Zmenu vykonal: <strong>" . $user['full_name'] . "</strong></p>";
                $adm_html .= "<p><a href='$link'>Otvoriť kontakt v CRM</a></p>";
                
                send_crm_notification(OFFICE_EMAIL, $adm_subject, wrap_email_content("Kľúčová zmena v CRM", $adm_html));
                $admin_notifications_sent[] = "E-mail do OFFICE (Stav)";
            }
        }

        // 2. Zmena TERMÍNOV
        $dates_to_check = [
            'survey_date' => 'Termín obhliadky',
            'production_at' => 'Termín výroby',
            'realization_date' => 'Termín realizácie',
            'handover_date' => 'Termín odovzdania'
        ];

        foreach ($dates_to_check as $field => $label) {
            $new_val = $$field; 
            if ($new_val && $new_val !== $lead[$field]) {
                $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                    ->execute([$id, $user['id'], 'system', "📅 TERMÍN UPRAVENÝ: $label na " . date('d.m.Y', strtotime($new_val))]);
                
                // 1. Notifikácia KLIENTOVI
                if (!empty($lead['email'])) {
                    $subject = "Nový termín pre: $label - VUETO";
                    $html = "<p>Dobrý deň,</p><p>informujeme Vás o stanovení alebo zmene dôležitého termínu:</p>";
                    $html .= "<div class='status-box'><strong>$label: " . date('d.m.Y', strtotime($new_val)) . "</strong></div>";
                    $html .= "<p>Prosíme Vás o potvrdenie alebo nahlásenie prípadnej kolízie. Všetky detaily nájdete na Vašom portáli.</p>";
                    
                    $mail_res = send_crm_notification($lead['email'], $subject, wrap_email_content("Aktualizácia termínu", $html, $portal_url));
                    if ($mail_res === true) $notifications_sent[] = "E-mail klientovi ($label)";
                }

                // 2. Notifikácia do OFFICE a ZVONČEKA
                $prot = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $link = $prot . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/contact.php?id=$id";
                
                $notif_title = "Upravený termín: $label";
                $notif_msg = "Pre " . htmlspecialchars($lead['name']) . " bol " . strtolower($label) . " stanovený na <strong>" . date('d.m.Y', strtotime($new_val)) . "</strong>";
                create_notification(0, $notif_title, $notif_msg, $link, 'ti-calendar-event', $user['id']);
                $admin_notifications_sent[] = "In-App notifikácia ($label)";

                if (defined('OFFICE_EMAIL') && in_array($field, ['realization_date', 'handover_date'])) {
                    $adm_subject = "CRM INFO: Zmena termínu ($label - Klient: " . $lead['name'] . ")";
                    $adm_html = "<p>Dobrý deň,</p><p>V CRM bol upravený dôležitý termín:</p>";
                    $adm_html .= "<div class='status-box'>";
                    $adm_html .= "Klient: <strong>" . htmlspecialchars($lead['name']) . "</strong><br>";
                    $adm_html .= "$label: <strong>" . date('d.m.Y', strtotime($new_val)) . "</strong>";
                    $adm_html .= "</div>";
                    $adm_html .= "<p>Zmenu vykonal: <strong>" . $user['full_name'] . "</strong></p>";
                    $adm_html .= "<p><a href='$link'>Otvoriť kontakt v CRM</a></p>";
                    
                    send_crm_notification(OFFICE_EMAIL, $adm_subject, wrap_email_content("Upozornenie na termín", $adm_html));
                    $admin_notifications_sent[] = "E-mail do OFFICE ($label)";
                }
            }
        }

        // Finálny zápis o odoslaných mailoch
        $total_sent = array_merge($notifications_sent, $admin_notifications_sent);
        if (!empty($total_sent)) {
            $log_msg = "⚙️ AUTOMAT: Systém úspešne odoslal notifikácie: " . implode(", ", $total_sent);
            $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                ->execute([$id, $user['id'], 'system_silent', $log_msg]);
        }


        $msg = 'Kontakt bol aktualizovaný.'; $msg_type = 'success';
        // Reload to show fresh data
        header("Location: contact.php?id=$id&ok=1");
        exit;
    }

    if ($action === 'add_note') {
        $content   = trim($_POST['content'] ?? '');
        $note_type = $_POST['note_type'] ?? 'poznamka';
        if (!empty($content)) {
            $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                ->execute([$id, $user['id'], $note_type, $content]);
        }
        header("Location: contact.php?id=$id&ok=note");
        exit;
    }

    if ($action === 'update_note_field') {
        $internal = $_POST['internal_note'] ?? '';
        $pdo->prepare("UPDATE leads SET internal_note=?, updated_at=NOW() WHERE id=?")->execute([$internal, $id]);
        header("Location: contact.php?id=$id&ok=1");
        exit;
    }

    if ($action === 'delete_note') {
        $note_id = (int)$_POST['note_id'];
        $n = $pdo->prepare("SELECT user_id FROM crm_notes WHERE id = ?");
        $n->execute([$note_id]);
        $note_user = $n->fetchColumn();
        
        if (is_admin() || (int)$note_user === (int)$user['id']) {
            $pdo->prepare("DELETE FROM crm_notes WHERE id = ?")->execute([$note_id]);
            header("Location: contact.php?id=$id&ok=deleted_note");
        } else {
            header("Location: contact.php?id=$id&err=unauthorized");
        }
        exit;
    }

    if ($action === 'delete_shared_doc') {
        $doc_id = (int)$_POST['doc_id'];
        if (is_admin()) {
            $pdo->prepare("DELETE FROM crm_generated_documents WHERE id = ? AND lead_id = ?")->execute([$doc_id, $id]);
            header("Location: contact.php?id=$id&ok=deleted_doc");
            exit;
        }
    }

    // DELETE – len admin
    if ($action === 'delete_lead' && is_admin()) {
        $pdo->prepare("DELETE FROM crm_notes WHERE lead_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM leads WHERE id=?")->execute([$id]);
        header("Location: index.php?deleted=1");
        exit;
    }
    // File upload
    if ($action === 'upload_file') {
        $category = $_POST['category'] ?? 'survey';
        $file = $_FILES['order_file'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/orders/';
            if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $new_name = $id . '_' . $category . '_' . time() . '_' . $safe_name;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $pdo->prepare("INSERT INTO crm_order_files (lead_id, file_path, category, original_name, uploaded_by) VALUES (?,?,?,?,?)")
                    ->execute([$id, 'uploads/orders/' . $new_name, $category, $file['name'], $user['id']]);
                
                $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                    ->execute([$id, $user['id'], 'system_silent', "Nahraný súbor v kategórii " . $category . ": " . $file['name']]);

                header("Location: contact.php?id=$id&ok=file");
                exit;
            }
        }
    }
}

if (isset($_GET['ok'])) { 
    $msg = match($_GET['ok']) {
        'note' => 'Poznámka pridaná.',
        'file' => 'Súbor nahraný.',
        default => 'Uložené.'
    };
    $msg_type = 'success'; 
}

$page_title = "Kontakt: " . htmlspecialchars($lead['name']);
include __DIR__ . '/partials/header.php';
?>
<!-- Quill Editor Assets -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<style>
    /* Quill Dark Theme Refinement */
    .ql-toolbar.ql-snow {
        border-color: var(--border) !important;
        background: rgba(255,255,255,0.02) !important;
        border-radius: 12px 12px 0 0 !important;
    }
    .ql-container.ql-snow {
        border-color: var(--border) !important;
        background: #0f1117 !important;
        border-radius: 0 0 12px 12px !important;
        font-family: inherit !important;
        font-size: 1rem !important;
    }
    .ql-editor {
        min-height: 150px !important;
        color: var(--text-primary) !important;
    }
    .ql-snow .ql-stroke { stroke: var(--text-secondary) !important; }
    .ql-snow .ql-fill { fill: var(--text-secondary) !important; }
    .ql-snow .ql-picker { color: var(--text-secondary) !important; }
    
    /* Activity Collapsible */
    .timeline-hidden {
        display: none !important;
    }
    .show-all-btn {
        width: 100%;
        margin-top: 1rem;
        padding: 0.75rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border);
        border-radius: 12px;
        color: var(--text-secondary);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .show-all-btn:hover {
        background: rgba(255,255,255,0.06);
        color: var(--text-primary);
    }
</style>
<?php if($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if(isset($_GET['err']) && $_GET['err']==='address_required'): ?>
<div class="alert alert-danger">⚠️ Pre prepnutie do stavu "Zákazka" musí mať klient vyplnenú **Adresu**. Upravte údaje klienta nižšie.</div>
<?php endif; ?>

<div class="flex gap-2 items-center mb-2">
    <a href="index.php?status=all" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Zoznam</a>
    <span class="status-badge <?= status_class($lead['status'] ?? 'novy') ?>"><?= status_label($lead['status'] ?? 'novy') ?></span>
</div>

<div class="detail-grid">

<!-- LEFT: Lead info + notes -->
<div>
    <!-- Lead info card -->
    <div class="card mb-2">
        <div class="card-header" style="padding-left: 0; padding-top: 0.5rem; padding-bottom: 1.25rem;">
            <div>
                <div class="card-title" style="display: flex; align-items: center; gap: 10px;">
                    <i class="ti ti-clipboard-text" style="font-size: 1.25rem; color: var(--accent-2);"></i>
                    <span>Informácie o kontakte<?= !empty($lead['order_number']) ? ' – Obj: ' . htmlspecialchars($lead['order_number']) : '' ?></span>
                </div>
                <div class="card-subtitle">Vytvorené: <?= date('d.m.Y H:i', strtotime($lead['created_at'])) ?></div>
            </div>
            <a href="edit_contact.php?id=<?= $id ?>" class="btn btn-secondary"><i class="ti ti-edit"></i> Upraviť údaje</a>
        </div>

        <div class="grid-2">
            <div class="detail-field">
                <div class="df-label">Meno a priezvisko</div>
                <div class="df-val">
                    <?= htmlspecialchars($lead['name']) ?>
                    <?php if(($lead['gender'] ?? 'unknown') === 'female'): ?>
                        <i class="ti ti-user-heart" style="color:#db2777; font-size:1rem; margin-left:5px;" title="Žena"></i>
                    <?php elseif(($lead['gender'] ?? 'unknown') === 'male'): ?>
                        <i class="ti ti-user-bolt" style="color:#2563eb; font-size:1rem; margin-left:5px;" title="Muž"></i>
                    <?php elseif(($lead['gender'] ?? 'unknown') === 'other'): ?>
                        <i class="ti ti-building-community" style="color:#d97706; font-size:1rem; margin-left:5px;" title="Firma / Iné"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="df-label">Záujem</div>
                <div class="df-val">
                    <?php if(!empty($lead['interest']) && (trim(mb_strtolower($lead['interest'])) !== 'kontaktovanie' || ($lead['source'] ?? 'web') !== 'web')): ?>
                        <span class="tag" style="margin-right:5px; opacity:0.8;"><?= htmlspecialchars(intent_label($lead['interest'])) ?></span>
                    <?php endif; ?>
                    <?php if(($lead['source'] ?? 'web') === 'web'): ?>
                        <span class="status-badge" style="background:rgba(99,102,241,0.15); color:var(--accent-2); border:1px solid var(--accent-2); font-size:0.7rem; padding:2px 8px;">
                            🌐 WEB <?= intent_label($lead['intent']) ?>
                        </span>
                    <?php else: ?>
                        <span class="tag" style="opacity:0.6;">👤 MANUÁLNY ZÁPIS</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="df-label" style="display: flex; align-items: center; gap: 5px;">
                    <i class="ti ti-mail" style="font-size: 0.9rem; color: var(--accent-2);"></i>
                    <span>Email</span>
                </div>
                <div class="df-val"><a href="mailto:<?= htmlspecialchars($lead['email']) ?>"><?= htmlspecialchars($lead['email']) ?></a></div>
            </div>
            <div class="detail-field">
                <div class="df-label" style="display: flex; align-items: center; gap: 5px;">
                    <i class="ti ti-phone" style="font-size: 0.9rem; color: var(--accent-2);"></i>
                    <span>Telefón</span>
                </div>
                <div class="df-val" style="font-size:0.95rem; font-weight:700;"><a href="tel:<?= htmlspecialchars($lead['phone']) ?>"><?= htmlspecialchars($lead['phone']) ?></a></div>
            </div>
            <?php if(!empty($lead['company'])): ?>
            <div class="detail-field">
                <div class="df-label" style="display: flex; align-items: center; gap: 5px;">
                    <i class="ti ti-building" style="font-size: 0.9rem; color: var(--accent-2);"></i>
                    <span>Firma (Názov/IČO)</span>
                </div>
                <div class="df-val"><?= htmlspecialchars($lead['company']) ?></div>
            </div>
            <?php endif; ?>
            
            <div style="grid-column:1/-1;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:1rem;">
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-zoom-check" style="color:var(--accent-2);"></i> Obhliadka</div>
                    <div class="df-val"><?= $lead['survey_date'] ? date('d.m.Y', strtotime($lead['survey_date'])) . ' ' . substr($lead['survey_time'],0,5) : '<span class="text-muted">—</span>' ?></div>
                </div>
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-settings" style="color:var(--accent-2);"></i> Výroba</div>
                    <div class="df-val"><?= $lead['production_at'] ? date('d.m.Y', strtotime($lead['production_at'])) : '<span class="text-muted">—</span>' ?></div>
                </div>
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-tool" style="color:var(--accent-2);"></i> Realizácia</div>
                    <div class="df-val"><?= $lead['realization_date'] ? date('d.m.Y', strtotime($lead['realization_date'])) . ' ' . substr($lead['realization_time'],0,5) : '<span class="text-muted">—</span>' ?></div>
                </div>
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-calendar" style="color:var(--accent-2);"></i> Spätné volanie</div>
                    <div class="df-val"><?= $lead['next_followup'] ? date('d.m.Y', strtotime($lead['next_followup'])) . ' ' . ($lead['next_followup_time'] ? substr($lead['next_followup_time'],0,5) : '') : '<span class="text-muted">—</span>' ?></div>
                </div>
            </div>

            <?php if(!empty($lead['address'])): ?>
            <div class="detail-field" style="margin-top:1rem;">
                <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-map-pin" style="color:var(--accent-2);"></i> Adresa</div>
                <div class="df-val">
                    <?= htmlspecialchars($lead['address']) ?><?= !empty($lead['apartment']) ? ' / ' . htmlspecialchars($lead['apartment']) : '' ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if(!empty($lead['city'])): ?>
            <div class="detail-field" style="margin-top:1rem;">
                <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-building-community" style="color:var(--accent-2);"></i> Mesto a PSČ</div>
                <div class="df-val"><?= !empty($lead['zip']) ? htmlspecialchars($lead['zip']) . ' ' : '' ?><?= htmlspecialchars($lead['city']) ?></div>
            </div>
            <?php endif; ?>

            <?php if($lead['status'] === 'zakazka' || !empty($lead['order_number'])): ?>
            <div style="grid-column:1/-1;margin-top:1rem;padding:1rem;background:rgba(99,102,241,0.05);border-radius:12px;display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:1rem;border:1px solid rgba(99,102,241,0.1);">
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-coin" style="color:var(--accent-2);"></i> Cena spolu</div>
                    <div class="df-val" style="color:var(--accent-2);font-weight:700;"><?= $lead['quote_price'] ? number_format($lead['quote_price'],2,',',' ') . ' €' : '—' ?></div>
                </div>
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-credit-card" style="color:var(--accent-2);"></i> Záloha</div>
                    <div class="df-val">
                        <?php if($lead['is_deposit_paid']): ?>
                            <span class="status-badge status-done">Zaplatená</span> (<?= number_format($lead['deposit_amount'],2,',',' ') ?> €)
                        <?php else: ?>
                            <span class="status-badge status-progress">Čaká sa</span> (<?= number_format($lead['deposit_amount'],2,',',' ') ?> €)
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-file-description" style="color:var(--accent-2);"></i> Faktúra</div>
                    <div class="df-val"><?= htmlspecialchars($lead['invoice_number'] ?: '—') ?></div>
                </div>
                <div class="detail-field">
                    <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-flag" style="color:var(--accent-2);"></i> Odovzdané</div>
                    <div class="df-val" style="font-weight:600;"><?= $lead['handover_date'] ? date('d.m.Y', strtotime($lead['handover_date'])) : '—' ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if($lead['message']): ?>
            <div class="detail-field" style="grid-column:1/-1; margin-top:1rem;">
                <div class="df-label">Pôvodná správa</div>
                <div class="df-val" style="white-space:pre-wrap; font-size:0.85rem; opacity:0.8;"><?= htmlspecialchars($lead['message']) ?></div>
            </div>
            <?php endif; ?>
            <?php if($lead['survey_notes']): ?>
            <div class="detail-field" style="grid-column:1/-1; margin-top:1rem; padding:0.75rem; background:rgba(255,255,255,0.03); border-radius:8px;">
                <div class="df-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-notes" style="color:var(--accent-2);"></i> Poznámky z obhliadky</div>
                <div class="df-val" style="white-space:pre-wrap;"><?= htmlspecialchars($lead['survey_notes']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- UTM -->
        <?php if($lead['utm_source'] || $lead['utm_medium'] || $lead['utm_campaign']): ?>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
            <div class="df-label" style="margin-bottom:.5rem;">UTM Zdroj</div>
            <div class="flex gap-1" style="flex-wrap:wrap;">
                <?php if($lead['utm_source']): ?><span class="tag">src: <?= htmlspecialchars($lead['utm_source']) ?></span><?php endif; ?>
                <?php if($lead['utm_medium']): ?><span class="tag">med: <?= htmlspecialchars($lead['utm_medium']) ?></span><?php endif; ?>
                <?php if($lead['utm_campaign']): ?><span class="tag">camp: <?= htmlspecialchars($lead['utm_campaign']) ?></span><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabs: Notes / Add note -->
    <div class="card">
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('tab-portal',this)"><i class="ti ti-world"></i> Dokumenty a portál (<?= count($shared_docs) ?>)</button>
            <button class="tab-btn" onclick="switchTab('tab-files',this)"><i class="ti ti-folder"></i> Súbory zákazky</button>
            <button class="tab-btn" onclick="switchTab('tab-addnote',this)"><i class="ti ti-plus"></i> Pridať záznam</button>
            <button class="tab-btn" onclick="switchTab('tab-internal',this)"><i class="ti ti-lock"></i> Interná poznámka</button>
            <button class="tab-btn" onclick="switchTab('tab-log',this)"><i class="ti ti-notes"></i> Aktivita (<?= count($notes) ?>)</button>
        </div>

        <!-- Documents & Portal Tab -->
        <div class="tab-pane active" id="tab-portal">
            <?php if(empty($shared_docs)): ?>
                <div class="empty-state"><div class="es-icon">📄</div><p>Zatiaľ žiadne vygenerované dokumenty.</p></div>
            <?php else: ?>
                <div class="grid-1 gap-1">
                    <?php foreach($shared_docs as $doc): 
                        $gen_file = '';
                        switch($doc['type']) {
                            case 'offer':    $gen_file = 'offer_generator.php'; break;
                            case 'order':    $gen_file = 'order_generator.php'; break;
                            case 'delivery': $gen_file = 'delivery_generator.php'; break;
                            case 'protocol': $gen_file = 'protocol_generator.php'; break;
                            case 'receipt':  $gen_file = 'receipt_generator.php'; break;
                            case 'proforma': $gen_file = 'proforma_generator.php'; break;
                        }
                    ?>
                    <div class="flex justify-between items-center p-1 bg-glass-light rounded-xl border border-glass">
                        <div class="flex items-center gap-1">
                            <div style="width:40px;height:40px;background:rgba(255,255,255,0.05);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                                <i class="ti ti-file-description"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold"><?= htmlspecialchars($doc['title']) ?></div>
                                <div class="text-xs text-muted" style="display: flex; align-items: center; gap: 6px;">
                                    <?= date('d.m.Y H:i', strtotime($doc['created_at'])) ?>
                                    <?php if($doc['status'] === 'draft'): ?>
                                        <span style="background:rgba(255,255,255,0.1); color:#fff; font-size:10px; padding:1px 6px; border-radius:4px; font-weight:700; text-transform:uppercase;">Koncept</span>
                                    <?php elseif($doc['status'] === 'sent'): ?>
                                        <span style="background:#2563eb; color:#fff; font-size:10px; padding:1px 6px; border-radius:4px; font-weight:700; text-transform:uppercase;">Odoslané</span>
                                    <?php elseif($doc['status'] === 'approved'): ?>
                                        <span style="background:#10b981; color:#fff; font-size:10px; padding:1px 6px; border-radius:4px; font-weight:700; text-transform:uppercase;">Schválené</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <?php if($gen_file): ?>
                            <a href="<?= $gen_file ?>?id=<?= $id ?>&edit_t=<?= $doc['token'] ?>" class="btn btn-secondary btn-sm" title="Pokračovať v úprave">
                                <i class="ti ti-edit"></i> <span class="hide-mobile">Upraviť</span>
                            </a>
                            <?php endif; ?>
                            <a href="portal.php?t=<?= $doc['token'] ?>" target="_blank" class="btn btn-<?= $doc['status'] === 'approved' ? 'primary' : 'secondary' ?> btn-sm" title="Zobraziť portál">
                                <i class="ti ti-world"></i> <?= $doc['status'] === 'approved' ? 'Zobraziť podpísaný' : '' ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activity log -->
        <div class="tab-pane" id="tab-log">
            <?php if(empty($notes)): ?>
            <div class="empty-state"><div class="es-icon">💬</div><p>Zatiaľ žiadne záznamy.</p></div>
            <?php else: ?>
            <div id="aiSummaryBox" style="display: none;" class="ai-summary-box">
                <h4><i class="ti ti-sparkles"></i> AI Zhrnutie histórie</h4>
                <div class="ai-summary-content" id="aiSummaryContent"></div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                <button type="button" class="btn btn-ai btn-sm" id="btnSummarize" onclick="summarizeHistory()">
                    <i class="ti ti-history"></i> ✨ Zhrnúť aktivitu AI
                </button>
            </div>
            <ul class="timeline">
            <?php foreach($notes as $index => $note): 
                $isHidden = $index >= 5;
            ?>
            <li class="timeline-item <?= $isHidden ? 'timeline-hidden' : '' ?>">
                <div class="timeline-dot"><?= $note['note_type']==='system'?'<i class="ti ti-settings"></i>':($note['note_type']==='hovor'?'<i class="ti ti-phone"></i>':($note['note_type']==='email'?'<i class="ti ti-mail"></i>':($note['note_type']==='stretnutie'?'<i class="ti ti-friends"></i>':'<i class="ti ti-notes"></i>'))) ?></div>
                <div class="timeline-body">
                    <div class="t-head" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.25rem;">
                        <span class="t-author" style="font-weight: 700; font-size: 0.85rem; color: var(--text-primary);">
                            <?= htmlspecialchars($note['full_name']) ?> 
                            <span style="font-weight: 400; font-size: 0.75rem; color: var(--text-muted); margin-left: 4px;"><?= note_type_label($note['note_type']) ?></span>
                        </span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="t-time" style="font-size: 0.7rem; color: var(--text-muted); white-space: nowrap;"><?= time_ago($note['created_at']) ?></span>
                            <?php if (is_admin()): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Naozaj chcete vymazať tento záznam?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="delete_note">
                                <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                                <button type="submit" style="background:none; border:none; padding:0; color:#f87171; cursor:pointer; opacity:0.5;" title="Zmazať záznam" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5">
                                    <i class="ti ti-trash" style="font-size:0.85rem;"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="t-text" style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; word-break: break-word;">
                        <?= $note['note_type'] === 'system' ? nl2br(htmlspecialchars($note['content'])) : $note['content'] ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
            </ul>
            
            <?php if(count($notes) > 5): ?>
                <button type="button" class="show-all-btn" onclick="toggleTimeline()">
                    <i class="ti ti-arrows-vertical"></i>
                    <span id="toggleText">Zobraziť celú aktivitu (<?= count($notes) ?>)</span>
                </button>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <style>
            #note-editor {
                background: rgba(0, 0, 0, 0.2) !important;
                color: var(--text-primary) !important;
                font-size: 0.95rem !important;
                border: none !important;
            }
            .ql-toolbar.ql-snow {
                background: rgba(255, 255, 255, 0.05) !important;
                border: none !important;
                border-bottom: 1px solid var(--border) !important;
            }
            .ql-container.ql-snow {
                border: none !important;
            }
            .ql-editor {
                min-height: 150px;
            }

            /* AI Action Bar Styling (Minimalist style) */
            .ai-assistant-bar {
                display: flex;
                align-items: center;
                gap: 20px;
                padding: 10px 1.5rem;
                background: rgba(255, 255, 255, 0.02);
                border-bottom: 1px solid var(--border);
                flex-wrap: wrap;
            }
            .btn-ai-minimal {
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 6px;
                color: var(--text-muted);
                transition: all 0.2s;
                font-size: 0.85rem;
                font-weight: 600;
                background: none;
                border: none;
                padding: 6px 0;
            }
            .btn-ai-minimal:hover {
                color: #6366f1;
                transform: translateY(-1px);
            }
            .btn-ai-minimal i {
                font-size: 1rem;
                opacity: 0.8;
            }
            .ai-loading {
                display: none;
                align-items: center;
                gap: 8px;
                color: var(--accent-2);
                font-size: 0.85rem;
                font-weight: 600;
                margin-right: 10px;
            }
            .ai-loading i { animation: spin 1s linear infinite; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

            .ai-loading i { animation: spin 1s linear infinite; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        </style>

        <!-- Add note -->
        <div class="tab-pane" id="tab-addnote">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="add_note">
                <div class="form-group">
                    <label class="form-label">Typ záznamu</label>
                    <select name="note_type" class="form-control">
                        <option value="poznamka">Poznámka</option>
                        <option value="hovor">Hovor</option>
                        <option value="email">Email</option>
                        <option value="stretnutie">Stretnutie</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Obsah záznamu</label>
                    
                    <div style="border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
                        <div class="ai-assistant-bar">
                            <div class="ai-loading" id="aiLoading">
                                <i class="ti ti-loader"></i> <span>AI premýšľa...</span>
                            </div>
                            <button type="button" class="btn-ai-minimal" onclick="runAI('fix_grammar')">
                                <i class="ti ti-wand"></i> Gramatika
                            </button>
                            <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'profesionálny'})">
                                <i class="ti ti-briefcase"></i> Profesionálne
                            </button>
                        </div>

                        <textarea name="content" id="note-text-area" class="form-control" style="min-height: 200px; background: rgba(0,0,0,0.2); border: none; border-radius: 0; color: white; padding: 15px; width: 100%; resize: vertical;" placeholder="Sem napíšte vašu poznámku..."></textarea>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> Uložiť záznam</button>
            </form>
        </div>

        <!-- Order Files Tab -->
        <?php
        $files = $pdo->prepare("SELECT f.*, u.full_name FROM crm_order_files f LEFT JOIN crm_users u ON f.uploaded_by = u.id WHERE f.lead_id = ? ORDER BY f.created_at DESC");
        $files->execute([$id]);
        $order_files = $files->fetchAll();
        ?>
        <div class="tab-pane" id="tab-files">
            <div class="flex justify-between items-center mb-1">
                <h4 class="m-0">Dokumenty a fotky</h4>
                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('upload-area').style.display='block';this.style.display='none';">+ Nahrať súbor</button>
            </div>

            <div id="upload-area" style="display:none; padding:1rem; background:rgba(255,255,255,0.03); border-radius:12px; border:1px dashed var(--border); margin-bottom:1rem;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="upload_file">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Vyberte súbor</label>
                            <input type="file" name="order_file" class="form-control" required style="padding:4px;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategória</label>
                            <select name="category" class="form-control">
                                <option value="survey">📌 Obhliadka (fotky/nákres)</option>
                                <option value="quote">📄 Cenová ponuka</option>
                                <option value="confirmation">✅ Potvrdenie objednávky</option>
                                <option value="delivery_note">📦 Dodací list</option>
                                <option value="invoice">💰 Faktúra</option>
                                <option value="realization">🏗️ Realizácia (fotky)</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-1 mt-1">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-upload"></i> Nahrať súbor</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('upload-area').style.display='none';">Zrušiť</button>
                    </div>
                </form>
            </div>

            <?php if(empty($order_files)): ?>
                <p class="text-muted text-center py-2">Žiadne súbory neboli nahrané.</p>
            <?php else: ?>
                <div class="grid-1 gap-1">
                    <?php foreach($order_files as $f): 
                        $is_img = in_array(strtolower(pathinfo($f['file_path'], PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif']);
                    ?>
                    <div class="flex justify-between items-center p-1 bg-glass-light rounded-xl border border-glass">
                        <div class="flex items-center gap-1">
                            <div style="width:40px;height:40px;background:rgba(255,255,255,0.05);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                                <?php if($is_img): ?><i class="ti ti-photo"></i><?php else: ?><i class="ti ti-file-text"></i><?php endif; ?>
                            </div>
                            <div>
                                <div class="text-sm font-bold"><a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($f['original_name']) ?></a></div>
                                <div class="text-xs text-muted">Kat: <?= $f['category'] ?> • Nahral: <?= htmlspecialchars($f['full_name']) ?></div>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($f['file_path']) ?>" download class="btn btn-secondary btn-sm" title="Stiahnuť"><i class="ti ti-download"></i></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- Internal note -->
        <div class="tab-pane" id="tab-internal">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update_note_field">
                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-lock"></i> Interná poznámka (viditeľná len tímu)</label>
                    
                    <div style="border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-top: 10px;">
                        <div class="ai-assistant-bar">
                            <div class="ai-loading" id="aiLoadingInternal">
                                <i class="ti ti-loader"></i> <span>AI premýšľa...</span>
                            </div>
                            <button type="button" class="btn-ai-minimal" onclick="runAI('fix_grammar', {}, 'internal-note-area', 'aiLoadingInternal')">
                                <i class="ti ti-wand"></i> Gramatika
                            </button>
                            <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'profesionálny'}, 'internal-note-area', 'aiLoadingInternal')">
                                <i class="ti ti-briefcase"></i> Profesionálne
                            </button>
                        </div>
                        <textarea name="internal_note" id="internal-note-area" class="form-control" style="min-height: 200px; background: rgba(0,0,0,0.2); border: none; border-radius: 0; color: white; padding: 15px; width: 100%; resize: vertical;" placeholder="Interné poznámky, cenová kalkulácia, špeciálne požiadavky..."><?= htmlspecialchars($lead['internal_note'] ?? '') ?></textarea>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top: 1rem;"><i class="ti ti-device-floppy"></i> Uložiť</button>
            </form>
        </div>
    </div>
</div>

<!-- RIGHT: Status panel -->
<div>
    <div class="card">
        <div class="card-title mb-2" style="display: flex; align-items: center; gap: 8px;"><i class="ti ti-bolt" style="color:var(--accent-2);"></i> Správa kontaktu</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="update_status">

            <div class="form-group">
                <label class="form-label">Stav</label>
                <select name="status" class="form-control">
                    <?php foreach(['novy','prideleny','kontaktovany_email','kontaktovany_telefon','kontaktovany_oboje','v_procese','ukonceny','zamietnuty','zakazka'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($lead['status']??'novy')===$s?'selected':'' ?>><?= status_label($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Priorita</label>
                <select name="priority" class="form-control">
                    <option value="nizka"   <?= ($lead['priority']??'')==='nizka'?'selected':''  ?>>⚪ Nízka</option>
                    <option value="stredna" <?= ($lead['priority']??'stredna')==='stredna'?'selected':'' ?>>🟡 Stredná</option>
                    <option value="vysoka"  <?= ($lead['priority']??'')==='vysoka'?'selected':'' ?>>🔴 Vysoká</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Pridelený kolega</label>
                <select name="assigned_to" class="form-control">
                    <option value="">— Nepridelený —</option>
                    <?php foreach($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $lead['assigned_to']==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-calendar-event"></i> Ďalší hovor (Pripomienka)</label>
                <div class="flex gap-1">
                    <input type="date" name="next_followup" class="form-control" value="<?= htmlspecialchars($lead['next_followup'] ?? '') ?>">
                    <input type="time" name="next_followup_time" class="form-control" style="width:120px;" value="<?= htmlspecialchars($lead['next_followup_time'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="border-top:1px solid var(--border);padding-top:1rem;">
                <label class="form-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-zoom-check"></i> Termín Obhliadky</label>
                <div class="flex gap-1">
                    <input type="date" name="survey_date" class="form-control" value="<?= htmlspecialchars($lead['survey_date'] ?? '') ?>">
                    <input type="time" name="survey_time" class="form-control" style="width:120px;" value="<?= htmlspecialchars($lead['survey_time'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-settings"></i> Zadané do výroby</label>
                <input type="date" name="production_at" class="form-control" value="<?= htmlspecialchars($lead['production_at'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-tool"></i> Termín Realizácie</label>
                <div class="flex gap-1">
                    <input type="date" name="realization_date" class="form-control" value="<?= htmlspecialchars($lead['realization_date'] ?? '') ?>">
                    <input type="time" name="realization_time" class="form-control" style="width:120px;" value="<?= htmlspecialchars($lead['realization_time'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="border-top:1px solid var(--border);padding-top:1rem;">
                <label class="form-label" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-chart-bar"></i> Detaily Zákazky</label>
                <div class="flex gap-1 mb-1">
                    <div style="flex:1;">
                        <label class="text-xs text-muted">Číslo zákazky</label>
                        <input type="text" name="order_number" class="form-control" placeholder="Generuje sa automaticky" value="<?= htmlspecialchars($lead['order_number'] ?? '') ?>">
                    </div>
                    <div style="flex:1;">
                        <label class="text-xs text-muted">Mesto</label>
                        <input type="text" name="city" class="form-control" placeholder="Mesto realizácie" value="<?= htmlspecialchars($lead['city'] ?? '') ?>">
                    </div>
                </div>
                <div class="flex gap-1 mb-1">
                    <div style="flex:1;">
                        <label class="text-xs text-muted">Cena spolu (€)</label>
                        <input type="number" name="quote_price" step="0.01" class="form-control" value="<?= htmlspecialchars($lead['quote_price'] ?? '') ?>" placeholder="0.00">
                    </div>
                    <div style="flex:1;">
                        <label class="text-xs text-muted">Číslo faktúry</label>
                        <input type="text" name="invoice_number" class="form-control" value="<?= htmlspecialchars($lead['invoice_number'] ?? '') ?>">
                    </div>
                </div>
                <div class="flex gap-1 items-end mb-1">
                    <div style="flex:1;">
                        <label class="text-xs text-muted">Suma zálohy (€)</label>
                        <input type="number" name="deposit_amount" step="0.01" class="form-control" value="<?= htmlspecialchars($lead['deposit_amount'] ?? '') ?>" placeholder="0.00">
                    </div>
                    <div style="padding-bottom:10px;">
                        <label class="flex items-center gap-1 cursor-pointer" style="font-size:0.8rem;">
                            <input type="checkbox" name="is_deposit_paid" value="1" <?= $lead['is_deposit_paid']?'checked':'' ?>> Zaplatená
                        </label>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="text-xs text-muted">Poznámky z obhliadky (technické)</label>
                    <textarea name="survey_notes" class="form-control" rows="2" style="font-size:0.85rem;"><?= htmlspecialchars($lead['survey_notes'] ?? '') ?></textarea>
                </div>
                <div class="mb-1">
                    <label class="text-xs text-muted" style="display: flex; align-items: center; gap: 5px;"><i class="ti ti-flag"></i> Dátum odovzdania</label>
                    <input type="date" name="handover_date" class="form-control" value="<?= htmlspecialchars($lead['handover_date'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="border-top:1px solid var(--border);padding-top:1rem;">
                <label class="form-label" style="display: flex; align-items: center; gap: 5px;">Odhadovaná hodnota (€)</label>
                <input type="number" name="deal_value" step="0.01" class="form-control"
                    value="<?= htmlspecialchars($lead['deal_value'] ?? '') ?>" placeholder="0.00">
            </div>

            <button class="btn btn-primary w-full" type="submit" style="justify-content:center;"><i class="ti ti-device-floppy"></i> Uložiť zmeny</button>
        </form>
    </div>

    <!-- Quick actions -->
    <div class="card mt-2">
        <div class="card-title mb-2" style="display: flex; align-items: center; gap: 8px;"><i class="ti ti-mail-fast" style="color:var(--accent-2);"></i> Rýchle akcie</div>
        <div class="flex gap-1" style="flex-direction:column;">
            <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem;"><i class="ti ti-mail"></i> Poslať email</a>
            <a href="tel:<?= htmlspecialchars($lead['phone']) ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem;"><i class="ti ti-phone"></i> Zavolať</a>
            <?php if($lead['deal_value']): ?>
            <div class="alert alert-success mt-1" style="display: flex; align-items: center; justify-content: flex-start; padding-left: 1.25rem;">
                Hodnota: &nbsp;<strong><?= number_format($lead['deal_value'], 2, ',', ' ') ?> €</strong>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Document Generation -->
    <div class="card mt-2">
        <div class="card-title mb-2" style="display: flex; align-items: center; gap: 8px;"><i class="ti ti-file-description" style="color:var(--accent-2);"></i> Generovať dokumenty</div>
        <div class="flex gap-1" style="flex-direction:column;">
            <a href="offer_generator.php?id=<?= $id ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem;"><i class="ti ti-file-invoice"></i> Cenová ponuka</a>
            <a href="order_generator.php?id=<?= $id ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem;"><i class="ti ti-shopping-cart"></i> Objednávka</a>
            <a href="delivery_generator.php?id=<?= $id ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem;"><i class="ti ti-truck-delivery"></i> Dodací list</a>
            <a href="protocol_generator.php?id=<?= $id ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem;"><i class="ti ti-clipboard-check"></i> Preberací protokol</a>
            <a href="receipt_generator.php?id=<?= $id ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem;"><i class="ti ti-receipt"></i> Príjmový doklad</a>
            <a href="proforma_generator.php?id=<?= $id ?>" class="btn btn-secondary" style="justify-content: flex-start; padding-left: 1.25rem; background: rgba(14,165,233,0.1); color:#0ea5e9; border-color:rgba(14,165,233,0.2);"><i class="ti ti-currency-euro"></i> Zálohová faktúra</a>
        </div>
    </div>

    <?php if(is_admin()): ?>
    <!-- Delete – len admin -->
    <div class="card mt-2" style="border-color:rgba(239,68,68,.25);">
        <div class="card-title mb-1" style="color:#f87171;">⚠️ Nebezpečná zóna</div>
        <p class="text-sm text-muted mb-2">Zmazanie kontaktu je nevratné. Zmažú sa aj všetky poznámky.</p>
        <button class="btn btn-danger w-full" style="justify-content:center;" onclick="document.getElementById('modal-delete').classList.add('open')">
            <i class="ti ti-trash"></i> Zmazať kontakt
        </button>
    </div>
    <?php endif; ?>
</div>

</div><!-- detail-grid -->

<!-- Delete modal -->
<?php if(is_admin()): ?>
<div class="modal-overlay" id="modal-delete">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" style="display: flex; align-items: center; gap: 8px;"><i class="ti ti-trash"></i> Zmazať kontakt?</div>
            <button class="modal-close" onclick="document.getElementById('modal-delete').classList.remove('open')">✕</button>
        </div>
        <p style="color:var(--text-secondary);margin-bottom:1.5rem;">
            Naozaj chceš zmazať kontakt <strong><?= htmlspecialchars($lead['name']) ?></strong>?<br>
            Zmažú sa aj všetky poznámky a záznamy aktivít. Táto akcia je <strong style="color:var(--red)">nevratná</strong>.
        </p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="delete_lead">
            <div class="flex gap-2">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-delete').classList.remove('open')" style="flex:1;justify-content:center;">Zrušiť</button>
                <button type="submit" class="btn btn-danger" style="flex:1;justify-content:center;"><i class="ti ti-trash"></i> Áno, zmazať</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>


<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); });
});


// Fix: Removed problematic gender detection block that was causing a ReferenceError
// and preventing AI features from initializing.

var quill = null;
const notesData = <?= json_encode($notes) ?>;

async function summarizeHistory() {
    const box = document.getElementById('aiSummaryBox');
    const content = document.getElementById('aiSummaryContent');
    const btn = document.getElementById('btnSummarize');
    
    if (!notesData || notesData.length === 0) return;
    
    btn.disabled = true;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class=\"ti ti-loader spin\"></i> Generujem...';
    
    // Prepare notes text (strip HTML tags)
    const notesText = notesData.map(n => {
        const tmp = document.createElement('DIV');
        tmp.innerHTML = n.content;
        const plain = tmp.textContent || tmp.innerText || '';
        return `[${n.created_at}] ${n.full_name} (${n.note_type}): ${plain}`;
    }).join('\n');
    
    try {
        const response = await fetch('ajax_ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'summarize_notes',
                content: notesText
            })
        });
        
        const data = await response.json();
        if (data.success) {
            box.style.display = 'block';
            content.innerHTML = data.result.replace(/\n/g, '<br>').replace(/\* /g, '• ');
            box.scrollIntoView({ behavior: 'smooth' });
        } else {
            alert('AI Chyba: ' + data.error);
        }
    } catch (e) {
        alert('Chyba pri komunikácii s AI.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    }
}

async function runAI(action, extraParams = {}, targetId = 'note-text-area', loadingId = 'aiLoading') {
    const loading = document.getElementById(loadingId);
    const textArea = document.getElementById(targetId);
    if (!textArea || !loading) return;

    const content = textArea.value.trim();
    if (content.length < 5) {
        alert('Prosím, napíšte aspoň krátky text.');
        return;
    }

    loading.style.display = 'inline-flex';
    document.querySelectorAll('.btn-ai-minimal').forEach(b => b.disabled = true);
    
    try {
        const response = await fetch('ajax_ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                content: content,
                ...extraParams
            })
        });
        
        const data = await response.json();
        if (data.success) {
            textArea.value = data.result;
        } else {
            alert('AI Chyba: ' + data.error);
        }
    } catch (e) {
        alert('Chyba pri komunikácii s AI.');
    } finally {
        loading.style.display = 'none';
        document.querySelectorAll('.btn-ai-minimal').forEach(b => b.disabled = false);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Activity Toggle
    window.toggleTimeline = function() {
        const items = document.querySelectorAll('.timeline-item');
        const btnText = document.getElementById('toggleText');
        const isHidden = document.querySelector('.timeline-hidden');
        
        if (isHidden) {
            items.forEach(it => it.classList.remove('timeline-hidden'));
            btnText.innerText = 'Zbaliť aktivitu';
        } else {
            items.forEach((it, index) => {
                if (index >= 5) it.classList.add('timeline-hidden');
            });
            btnText.innerText = 'Zobraziť celú aktivitu (<?= count($notes) ?>)';
            document.getElementById('tab-log').scrollIntoView({ behavior: 'smooth' });
        }
    };
});

// Address Autocomplete Logic
async function initAddressAutocomplete(inputSelector, citySelector, zipSelector) {
    const input = document.querySelector(inputSelector);
    if (!input) return;

    const container = document.createElement('div');
    container.className = 'autocomplete-results';
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(container);

    let debounceTimer;
    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();
        if (query.length < 3) { container.style.display = 'none'; return; }

        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5&lat=48.66&lon=19.69&bbox=16.8,47.7,22.6,49.6`);
                const data = await response.json();
                if (data.features.length > 0) {
                    container.innerHTML = '';
                    data.features.forEach(f => {
                        const p = f.properties;
                        const street = p.street || p.name || '';
                        const housenumber = p.housenumber || '';
                        const city = p.city || p.town || '';
                        const postcode = p.postcode || '';
                        const fullAddress = `${street} ${housenumber}`.trim();
                        if (!fullAddress) return;
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.innerHTML = `<strong>${fullAddress}</strong><span class="city">${postcode} ${city}</span>`;
                        item.onclick = () => {
                            input.value = fullAddress;
                            if (citySelector) {
                                const cityEl = document.querySelector(citySelector);
                                if (cityEl) cityEl.value = city;
                            }
                            if (zipSelector) {
                                const zipEl = document.querySelector(zipSelector);
                                if (zipEl) zipEl.value = postcode;
                            }
                            container.style.display = 'none';
                        };
                        container.appendChild(item);
                    });
                    container.style.display = 'block';
                } else { container.style.display = 'none'; }
            } catch (e) { console.error('Autocomplete error:', e); }
        }, 300);
    });
    document.addEventListener('click', (e) => { if (e.target !== input) container.style.display = 'none'; });
}

document.addEventListener('DOMContentLoaded', () => {
    // Basic address autocomplete for any address/city/zip fields
    initAddressAutocomplete('input[name="address"]', 'input[name="city"]', 'input[name="zip"]');
    initAddressAutocomplete('input[name="survey_address"]', 'input[name="survey_city"]', 'input[name="survey_zip"]');
});
</script>

<style>
    /* Address Autocomplete Styling */
    .autocomplete-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #111827;
        border: 1px solid var(--border);
        border-radius: 8px;
        z-index: 2000;
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        display: none;
        margin-top: 4px;
    }
    .autocomplete-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 0.9rem;
        transition: background 0.2s;
    }
    .autocomplete-item:hover { background: rgba(255,255,255,0.05); color: var(--accent); }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item .city { font-size: 0.75rem; color: var(--text-muted); display: block; }
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
