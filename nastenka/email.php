<?php
require_once __DIR__ . '/auth.php';
require_login();
session_write_close();
$pdo = db_connect();
$user = current_user();
session_write_close();

if (!function_exists('imap_open')) {
    die('PHP rozšírenie IMAP nie je nainštalované alebo povolené. Toto je vyžadované pre čítanie e-mailov.');
}

require_once __DIR__ . '/libs/mailer.php';

// E-mail konfigurácia z config.php
$mail_host = MAIL_HOST;
$mail_user = MAIL_USER;
$mail_pass = MAIL_PASS;


$flash_msg = '';
$flash_error = '';

$folder = $_GET['folder'] ?? 'INBOX';

// send_smtp_email removed, now in libs/mailer.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();
    if ($_POST['action'] === 'send_email') {
        $to = $_POST['to'];
    $subject = $_POST['subject'];
    $body = $_POST['message'];
    
    $attachments = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $attachments[] = [
                    'name' => $_FILES['attachments']['name'][$key],
                    'type' => $_FILES['attachments']['type'][$key] ?: 'application/octet-stream',
                    'tmp_name' => $tmp_name
                ];
            }
        }
    }
    
    $result = send_smtp_email($to, $subject, $body, $mail_host, $mail_user, $mail_pass, $attachments);
    if ($result === true) {
        global $folder;
        header('Location: email.php?folder=' . urlencode($folder) . '&sent=1');
        exit;
    } else {
        $flash_error = $result;
    }
    } elseif ($_POST['action'] === 'bulk_delete') {
        if (!empty($_POST['msgs']) && is_array($_POST['msgs'])) {
            $is_destructive = (stripos($folder, 'trash') !== false || stripos($folder, 'junk') !== false || stripos($folder, 'spam') !== false);
            imap_timeout(IMAP_OPENTIMEOUT, 15);
$imap_box = "{" . $mail_host . ":993/imap/ssl}" . $folder;
            $imap_stream_del = @imap_open($imap_box, $mail_user, $mail_pass);
            if ($imap_stream_del) {
                $trash_folder_bulk = null;
                if (!$is_destructive) {
                    $box_list = @imap_list($imap_stream_del, "{" . $mail_host . ":993/imap/ssl}", "*");
                    if ($box_list) {
                        foreach ($box_list as $b) {
                            $bn = str_replace("{" . $mail_host . ":993/imap/ssl}", "", $b);
                            if (stripos($bn, 'trash') !== false) { $trash_folder_bulk = $bn; break; }
                        }
                    }
                }
                $msg_sequence = implode(',', array_map('intval', $_POST['msgs']));
                if ($trash_folder_bulk && !$is_destructive) {
                    @imap_mail_copy($imap_stream_del, $msg_sequence, $trash_folder_bulk, 0);
                }
                // Mark all as deleted regardless (move = copy + delete)
                foreach ($_POST['msgs'] as $msg_id) {
                    @imap_delete($imap_stream_del, (int)$msg_id);
                }
                @imap_expunge($imap_stream_del);
                @imap_close($imap_stream_del);
            }
            $status = $is_destructive ? 'deleted' : 'moved_trash';
            header('Location: email.php?folder=' . urlencode($folder) . '&bulk=' . $status);
            exit;
        }
    }
}

if (isset($_GET['sent'])) $flash_msg = 'E-mail bol úspešne odoslaný (a uložený do zložky Odoslané).';
if (isset($_GET['bulk'])) {
    if ($_GET['bulk'] === 'moved_trash') $flash_msg = 'Vybraté správy boli presunuté do Koša.';
    if ($_GET['bulk'] === 'deleted') $flash_msg = 'Vybraté správy boli natrvalo zmazané.';
}
if (isset($_GET['moved'])) $flash_msg = 'E-mail bol presunutý do Koša.';
if (isset($_GET['deleted'])) $flash_msg = 'E-mail bol natrvalo zmazaný.';

function decode_imap_text($str) {
    if (!$str) return '';
    $result = '';
    $decode = imap_mime_header_decode($str);
    foreach ($decode as $obj) {
        $result .= $obj->text;
    }
    return $result;
}

function display_name_only($str) {
    if (preg_match('/^(.*?)\s*<([^>]+)>$/', trim($str), $matches)) {
        $name = trim($matches[1], ' "\'');
        return $name ? $name : trim($matches[2]);
    }
    return $str;
}

function decode_imap_body($body, $encoding) {
    if ($encoding == 3) return imap_base64($body);
    if ($encoding == 4) return quoted_printable_decode($body);
    return $body;
}

function get_email_part($imap, $msg_num, $mime_type, $structure = null, $part_num = "") {
    if (!$structure) {
        $structure = imap_fetchstructure($imap, $msg_num);
    }
    
    if ($structure) {
        if ($mime_type == get_mime_type($structure)) {
            $part_num = ($part_num == "") ? "1" : $part_num;
            $body = imap_fetchbody($imap, $msg_num, $part_num);
            return decode_imap_body($body, $structure->encoding);
        }
        
        if ($structure->type == 1) { // multipart
            foreach ($structure->parts as $index => $sub_structure) {
                $prefix = "";
                if ($part_num != "") {
                    $prefix = $part_num . ".";
                }
                $data = get_email_part($imap, $msg_num, $mime_type, $sub_structure, $prefix . ($index + 1));
                if ($data) return $data;
            }
        }
    }
    return false;
}

function get_mime_type($structure) {
    $primary_types = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if ($structure->subtype) {
        return $primary_types[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
}

function get_attachments_list($imap, $msg_num, $structure = null, $part_num = "") {
    $attachments = [];
    if (!$structure) {
        $structure = imap_fetchstructure($imap, $msg_num);
    }
    
    if (isset($structure->parts) && count($structure->parts)) {
        foreach ($structure->parts as $index => $part) {
            $current_part_num = ($part_num == "") ? ($index + 1) : ($part_num . "." . ($index + 1));
            
            // Check if it's an attachment
            $is_attachment = false;
            $filename = "";
            
            if ($part->ifdparameters) {
                foreach ($part->dparameters as $object) {
                    if (strtolower($object->attribute) == 'filename') {
                        $is_attachment = true;
                        $filename = decode_imap_text($object->value);
                    }
                }
            }
            
            if (!$is_attachment && $part->ifparameters) {
                foreach ($part->parameters as $object) {
                    if (strtolower($object->attribute) == 'name') {
                        $is_attachment = true;
                        $filename = decode_imap_text($object->value);
                    }
                }
            }
            
            // Fallback for images/videos that might not be marked as attachments
            if (!$is_attachment && ($part->type == 5 || $part->type == 6)) {
                $is_attachment = true;
                $filename = "media_" . str_replace('.', '_', $current_part_num);
                if ($part->ifparameters) {
                    foreach ($part->parameters as $object) {
                        if (strtolower($object->attribute) == 'name') $filename = decode_imap_text($object->value);
                    }
                }
                // Append correct extension if missing
                $ext = strtolower($part->subtype);
                if (!str_ends_with(strtolower($filename), '.' . $ext)) {
                    if ($ext == 'jpeg') $ext = 'jpg';
                    if ($ext == 'mpeg') $ext = 'mp4';
                    $filename .= '.' . $ext;
                }
            }
            
            if ($is_attachment) {
                $attachments[] = [
                    'part_num' => $current_part_num,
                    'filename' => $filename ?: 'unnamed_file',
                    'type' => get_mime_type($part),
                    'size' => $part->bytes
                ];
            } elseif ($part->type == 1) { // multipart
                $attachments = array_merge($attachments, get_attachments_list($imap, $msg_num, $part, $current_part_num));
            }
        }
    }
    return $attachments;
}

imap_timeout(IMAP_OPENTIMEOUT, 15);
$imap_box = "{" . $mail_host . ":993/imap/ssl}" . $folder;
$imap_stream = @imap_open($imap_box, $mail_user, $mail_pass);

// Debug: show all IMAP folders
if (isset($_GET['debug_folders'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "=== IMAP DEBUG ===\n";
    echo "Stream: " . ($imap_stream ? "OK" : "FAILED") . "\n";
    if (!$imap_stream) {
        echo "PHP IMAP errors:\n";
        print_r(imap_errors());
        print_r(imap_alerts());
    } else {
        $all_boxes = imap_list($imap_stream, "{" . $mail_host . ":993/imap/ssl}", "*");
        echo "Folders:\n";
        if ($all_boxes) foreach ($all_boxes as $b) echo "  " . str_replace("{" . $mail_host . ":993/imap/ssl}", "", $b) . "\n";
        else echo "  (none found)\n";
        echo "Errors: "; print_r(imap_errors());
    }
    exit;
}

// Debug: test actual delete on message number from URL ?debug_delete=MSGNO
if (isset($_GET['debug_delete']) && $imap_stream) {
    header('Content-Type: text/plain; charset=UTF-8');
    $dmsg = (int)$_GET['debug_delete'];
    echo "=== DELETE DEBUG: msg=$dmsg folder=$folder ===\n";
    $imap_del2 = imap_open("{" . $mail_host . ":993/imap/ssl}" . $folder, $mail_user, $mail_pass);
    echo "Open stream: " . ($imap_del2 ? "OK" : "FAIL") . "\n";
    if ($imap_del2) {
        $num = imap_num_msg($imap_del2);
        echo "Msgs in box: $num\n";
        $copy_ok = imap_mail_copy($imap_del2, "$dmsg", "Trash", 0);
        echo "Copy to Trash: " . ($copy_ok ? "OK" : "FAIL") . "\n";
        $del_ok = imap_delete($imap_del2, $dmsg);
        echo "imap_delete: " . ($del_ok ? "OK" : "FAIL") . "\n";
        $exp_ok = imap_expunge($imap_del2);
        echo "imap_expunge: " . ($exp_ok ? "OK" : "FAIL") . "\n";
        $num2 = imap_num_msg($imap_del2);
        echo "Msgs after: $num2\n";
        imap_close($imap_del2);
    }
    echo "IMAP Errors: "; print_r(imap_errors());
    echo "IMAP Alerts: "; print_r(imap_alerts());
    exit;
}

$emails = [];
$view_msg = null;
$msg_detail = null;
$available_folders = [];

if ($imap_stream) {
    $boxes = imap_getmailboxes($imap_stream, "{" . $mail_host . ":993/imap/ssl}", "*");
    if ($boxes) {
        foreach ($boxes as $val) {
            $name = str_replace("{" . $mail_host . ":993/imap/ssl}", "", $val->name);
            $available_folders[] = $name;
        }
    }

    if (isset($_GET['msg'])) {
        $view_msg = (int)$_GET['msg'];
        if (isset($_GET['del']) && $_GET['del'] == 1) {
            $is_destructive_folder = (stripos($folder, 'trash') !== false || stripos($folder, 'junk') !== false || stripos($folder, 'spam') !== false);
            // Find trash folder from already-loaded list
            $trash_folder = null;
            if (!$is_destructive_folder) {
                foreach ($available_folders as $af) {
                    if (stripos($af, 'trash') !== false) { $trash_folder = $af; break; }
                }
            }
            // Open a fresh dedicated stream for write operations
            $imap_del = imap_open("{" . $mail_host . ":993/imap/ssl}" . $folder, $mail_user, $mail_pass);
            if ($imap_del) {
                if ($trash_folder) {
                    imap_mail_copy($imap_del, "$view_msg", $trash_folder, 0);
                }
                imap_delete($imap_del, $view_msg);
                imap_expunge($imap_del);
                imap_close($imap_del);
            }
            $loc = $trash_folder ? '&moved=1' : '&deleted=1';
            header("Location: email.php?folder=" . urlencode($folder) . $loc);
            exit;
        }

        $header = imap_headerinfo($imap_stream, $view_msg);
        
        // Robust fetching of body
        $body = get_email_part($imap_stream, $view_msg, "TEXT/HTML");
        $is_html = true;
        
        if (!$body) {
            $body = get_email_part($imap_stream, $view_msg, "TEXT/PLAIN");
            $is_html = false;
        }
        
        if (!$body) {
            $body = "(Žiadny obsah správy)";
        }
        
        // Get attachments
        $attachments = get_attachments_list($imap_stream, $view_msg);
        
        if (!mb_check_encoding($body, 'UTF-8')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'auto');
        }
        
        $subject = decode_imap_text($header->subject ?? '');
        $from = decode_imap_text($header->fromaddress ?? '');
        $to = decode_imap_text($header->toaddress ?? '');
        imap_setflag_full($imap_stream, $view_msg, "\\Seen");

        $msg_detail = [
            'subject' => $subject ?: '(Bez predmetu)',
            'from' => $from,
            'to' => $to,
            'date' => date('Y-m-d H:i', strtotime($header->date ?? 'now')),
            'body' => $body,
            'is_html' => $is_html,
            'attachments' => $attachments
        ];
    } else {
        $num_msgs = imap_num_msg($imap_stream);
        if ($num_msgs > 0) {
            $start = max(1, $num_msgs - 50);
            $result = imap_fetch_overview($imap_stream, "$start:$num_msgs", 0);
            if(is_array($result)) {
                $result = array_reverse($result);
                foreach ($result as $overview) {
                    $emails[] = [
                        'msgno' => $overview->msgno,
                        'subject' => decode_imap_text($overview->subject ?? '(Bez predmetu)'),
                        'from' => decode_imap_text($overview->from ?? 'Neznámy'),
                        'date' => date('d.m.Y H:i', strtotime($overview->date ?? 'now')),
                        'seen' => $overview->seen
                    ];
                }
            }
        }
    }
} else {
    $errors = imap_errors();
    $flash_error = 'Nepodarilo sa pripojiť k IMAP zložke. ' . (is_array($errors) ? implode(', ', $errors) : 'Skontrolujte prístupové údaje.');
}

// Ziskaj maily z kontaktov pre autocompletion
$contact_emails = $pdo->query("SELECT name, email FROM leads WHERE email IS NOT NULL AND email != '' GROUP BY email ORDER BY name")->fetchAll();

$page_title = 'E-mail';
include __DIR__ . '/partials/header.php';
?>
<!-- Quill Editor Assets -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<style>
    /* Quill Dark Theme Refinement */
    .ql-toolbar.ql-snow { border-color: var(--border) !important; background: rgba(255,255,255,0.02) !important; border-radius: 12px 12px 0 0 !important; }
    .ql-container.ql-snow { border-color: var(--border) !important; background: #0f1117 !important; border-radius: 0 0 12px 12px !important; }
    .ql-editor { min-height: 250px !important; color: var(--text-primary) !important; }
    .ql-snow .ql-stroke { stroke: var(--text-secondary) !important; }
    .ql-snow .ql-fill { fill: var(--text-secondary) !important; }
    .ql-snow .ql-picker { color: var(--text-secondary) !important; }

    @media (max-width: 768px) {
        .composer-footer-responsive {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 15px !important;
        }
        .send-btn-container .btn-primary {
            width: 100% !important;
            justify-content: center !important;
        }
        
        .container-fluid { padding-left: 10px !important; padding-right: 10px !important; touch-action: pan-y !important; }
        .email-view-card { border-radius: 12px !important; margin: 0 -5px; touch-action: pan-y !important; }
        .email-detail-container { padding: 1.25rem 1rem !important; }
        
        .ql-container, .ql-editor { touch-action: pan-y !important; }

        /* Fix scrolling */
        body { overflow-y: auto !important; height: auto !important; position: relative !important; }
        .crm-layout { height: auto !important; min-height: 100vh !important; overflow: visible !important; }
    }
</style>
<?php

// Ziskaj dynamicky e-mail a telefon odosielatela z DB
$u_stm = $pdo->prepare("SELECT email, phone FROM crm_users WHERE id = ?");
$u_stm->execute([$user['id']]);
$u_det = $u_stm->fetch(PDO::FETCH_ASSOC) ?: [];
$dyn_email = (!empty($u_det['email'])) ? $u_det['email'] : 'info@vueto.sk';
$dyn_phone = (!empty($u_det['phone'])) ? $u_det['phone'] : '+421 950 400 203';

$signature = "\n\n--\nS pozdravom,\n" . htmlspecialchars($user['full_name'] ?? 'Užívateľ') . "\nTím VUETO\n📞 " . htmlspecialchars($dyn_phone) . "\n🌐 www.vueto.sk\n✉️ " . htmlspecialchars($dyn_email);

?>

<datalist id="contactEmails">
    <?php foreach($contact_emails as $ce): ?>
        <option value="<?= htmlspecialchars($ce['email']) ?>"><?= htmlspecialchars($ce['name']) ?></option>
    <?php endforeach; ?>
</datalist>



<div class="container-fluid">
    <?php if($flash_error): ?>
        <div class="alert alert-danger" style="grid-column: 1 / -1; margin-bottom: 1rem;"><?= htmlspecialchars($flash_error) ?></div>
    <?php endif; ?>

    <!-- Folder Navigation Tabs (Pill Style) -->
    <div class="email-folder-tabs" style="display: flex; gap: 8px; overflow-x: auto; padding: 0.5rem 0 1.5rem; scrollbar-width: none; -ms-overflow-style: none;">
        <style>
            .email-folder-tabs::-webkit-scrollbar { display: none; }
            .folder-pill {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                height: 38px;
                padding: 0 1.25rem;
                background: rgba(255,255,255,0.03);
                color: rgba(255,255,255,0.7);
                text-decoration: none;
                border-radius: 12px; /* Standardized radius */
                font-weight: 700;
                font-size: 0.88rem;
                white-space: nowrap;
                transition: all 0.2s;
                border: 1px solid rgba(255,255,255,0.08);
            }
            .folder-pill.active {
                background: #6366f1 !important; 
                color: #fff !important;
                border: 1px solid #6366f1;
                box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            }
            .folder-pill:hover:not(.active) {
                background: rgba(255,255,255,0.08);
                color: #fff;
            }
            .ai-helper-btn-nav {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                height: 38px;
                padding: 0 1.25rem;
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(168, 85, 247, 0.25));
                border: 1px solid rgba(99, 102, 241, 0.4);
                color: white;
                text-decoration: none;
                border-radius: 12px;
                font-weight: 700;
                font-size: 0.88rem;
                white-space: nowrap;
                transition: all 0.3s;
                margin-left: 5px;
            }
            .ai-helper-btn-nav:hover {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.4), rgba(168, 85, 247, 0.4));
                box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
                transform: translateY(-1px);
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
        </style>
        
        <?php 
        $display_folders = [
            'INBOX' => ['label' => 'Doručené', 'icon' => 'ti-inbox'],
            'Sent' => ['label' => 'Odoslané', 'icon' => 'ti-send'],
            'Trash' => ['label' => 'Kôš', 'icon' => 'ti-trash'],
            'Spam' => ['label' => 'Spam', 'icon' => 'ti-alert-circle']
        ];
        
        // Find actual IMAP folders in the available list that match our labels
        foreach ($display_folders as $key => $info):
            $actual_folder = $key;
            foreach ($available_folders as $af) {
                if (stripos($af, $key) !== false) { $actual_folder = $af; break; }
                if ($key == 'Sent' && stripos($af, 'Odoslan') !== false) { $actual_folder = $af; break; }
            }
            $is_active = (strtoupper($folder) == strtoupper($actual_folder));
        ?>
            <a href="email.php?folder=<?= urlencode($actual_folder) ?>" class="folder-pill <?= $is_active ? 'active' : '' ?>">
                <i class="ti <?= $info['icon'] ?>"></i>
                <?= $info['label'] ?>
            </a>
        <?php endforeach; ?>
        <a href="ai_assistant.php<?= ($view_msg ? '?folder='.urlencode($folder).'&msg='.urlencode($view_msg) : '') ?>" class="ai-helper-btn-nav">
            <i class="ti ti-sparkles"></i> AI asistent
        </a>
    </div>

    <div class="<?= ($view_msg && $msg_detail) ? 'is-viewing-email' : '' ?>">
        <?php if($view_msg && $msg_detail): ?>
            <div class="card mb-2 email-view-card" style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border); overflow: hidden;">
                <div class="card-header pb-2" style="border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; padding: 1.25rem 1.5rem;">
                    <button class="btn btn-secondary" onclick="window.location='email.php?folder=<?= urlencode($folder) ?>'"><i class="ti ti-arrow-left"></i> Späť</button>
                    <a href="#" data-del-href="email.php?folder=<?= urlencode($folder) ?>&msg=<?= $view_msg ?>&del=1" class="btn btn-danger"><i class="ti ti-trash"></i> Zmaza&#x165;</a>
                </div>
                <div class="card-body email-detail-container" style="padding: 1.5rem;">
                    <div style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                        <div style="width: 44px; height: 44px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; color: var(--accent-2); font-size: 1.6rem; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1);">
                            <i class="ti ti-user"></i>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:center; min-height:44px;">
                            <div style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.4; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span>Od: <strong style="color: var(--accent-1); font-weight: 600; font-size: 0.95rem;"><?= htmlspecialchars(display_name_only($msg_detail['from'])) ?></strong></span>
                                <span style="font-size: 0.8rem; opacity: 0.7;">&lt;<?php preg_match('/<([^>]+)>/', $msg_detail['from'], $m); echo htmlspecialchars($m[1] ?? $msg_detail['from']); ?>&gt;</span>
                                <i class="ti ti-arrow-right" style="font-size: 0.8rem; opacity: 0.5;"></i>
                                <span>Komu: <strong style="color: var(--accent-2); font-weight: 600; font-size: 0.95rem;"><?= htmlspecialchars(display_name_only($msg_detail['to'])) ?></strong></span>
                                <span style="font-size: 0.75rem; opacity:0.6; margin-left: 5px; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-clock"></i> <?= htmlspecialchars($msg_detail['date']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0px;" class="email-subject-group">
                        <div style="font-size: 1.1rem; color: var(--text-primary); font-weight: 700; line-height: 1.3;" class="email-subject-line">
                            Predmet: <?= htmlspecialchars($msg_detail['subject']) ?>
                        </div>
                        <button type="button" id="emailThemeBtn" onclick="toggleEmailTheme()" class="btn btn-secondary btn-sm" style="display:flex; align-items:center; gap:6px; background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--text-secondary); height: 30px; font-size: 0.8rem; padding: 0 10px; border-radius: 20px;">
                            <i class="ti ti-sun"></i> <span>Svetlý mód</span>
                        </button>
                    </div>
                    
                    <div style="background: #0f1117; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; position: relative; margin-top: 10px;" class="email-detail-container">
                        <?php if(!empty($msg_detail['is_html'])): ?>
                            <?php 
                                // Precise internal filtering isolation
                                $base_css = "<style>
                                    html, body { background: #0f1117 !important; margin: 0; padding: 0; }
                                    .email-internal-wrapper { 
                                        font-family: 'Outfit', sans-serif;
                                        font-size: 15px !important; 
                                        line-height: 1.5 !important; 
                                        padding: 20px !important;
                                        background: transparent !important;
                                        color: #000 !important;
                                        filter: invert(0.92) hue-rotate(180deg) brightness(1.05) contrast(1.05);
                                        transition: filter 0.3s;
                                        min-height: 300px;
                                    }
                                    .email-internal-wrapper.no-filter { filter: none !important; color: #000 !important; background: #ffffff !important; }
                                    p, div { margin-bottom: 12px; }
                                    img { max-width: 100% !important; height: auto !important; }
                                    a { color: #6366f1; text-decoration: underline; }
                                </style><base target='_blank'>";
                                $styled_body = $base_css . '<div class="email-internal-wrapper" id="filterWrapper">' . $msg_detail['body'] . '</div>';
                            ?>
                            <iframe id="emailIframe" data-theme="dark" sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation" srcdoc="<?= htmlspecialchars($styled_body) ?>" style="width: 100%; min-height: 300px; border: none; display: block; background: #0f1117; overflow: hidden;" scrolling="no" onload="if(this.contentWindow) { this.style.height = (this.contentWindow.document.documentElement.scrollHeight + 50) + 'px'; }"></iframe>
                            <script>
                            function toggleEmailTheme() {
                                var iframe = document.getElementById('emailIframe');
                                var btn = document.getElementById('emailThemeBtn');
                                var icon = btn.querySelector('i');
                                var span = btn.querySelector('span');
                                
                                if (iframe && iframe.contentWindow) {
                                    var wrapper = iframe.contentWindow.document.getElementById('filterWrapper');
                                    var internalBody = iframe.contentWindow.document.body;
                                    if (iframe.getAttribute('data-theme') === 'dark') {
                                        if(wrapper) wrapper.classList.add('no-filter');
                                        if(internalBody) internalBody.style.background = '#ffffff';
                                        iframe.style.background = '#ffffff';
                                        iframe.setAttribute('data-theme', 'light');
                                        icon.className = 'ti ti-moon';
                                        span.textContent = 'Tmavý mód';
                                    } else {
                                        if(wrapper) wrapper.classList.remove('no-filter');
                                        if(internalBody) internalBody.style.background = '#0f1117';
                                        iframe.style.background = '#0f1117';
                                        iframe.setAttribute('data-theme', 'dark');
                                        icon.className = 'ti ti-sun';
                                        span.textContent = 'Svetlý mód';
                                    }
                                }
                                
                                var plainText = document.getElementById('plainEmailBody');
                                if (plainText) {
                                    var isDark = plainText.getAttribute('data-theme') === 'dark';
                                    if (isDark) {
                                        plainText.style.background = '#f8f9fa';
                                        plainText.style.color = '#1a1d21';
                                        plainText.setAttribute('data-theme', 'light');
                                        icon.className = 'ti ti-moon';
                                        span.textContent = 'Tmavý mód';
                                    } else {
                                        plainText.style.background = '#0f1117';
                                        plainText.style.color = 'var(--text-primary)';
                                        plainText.setAttribute('data-theme', 'dark');
                                        icon.className = 'ti ti-sun';
                                        span.textContent = 'Svetlý mód';
                                    }
                                }
                            }
                            </script>
                        <?php else: ?>
                            <div id="plainEmailBody" data-theme="dark" style="font-family: inherit; font-size: 15px; color: var(--text-primary); white-space: pre-wrap; line-height: 1.7; word-break: break-word; padding: 20px; transition: all 0.3s; min-height: 300px; overflow: hidden; background: #0f1117; border-radius: 4px;"><?= htmlspecialchars(trim($msg_detail['body'])) ?></div>
                            <script>
                                if (typeof toggleEmailTheme !== 'function') {
                                    function toggleEmailTheme() {
                                        var btn = document.getElementById('emailThemeBtn');
                                        var icon = btn.querySelector('i');
                                        var span = btn.querySelector('span');
                                        var plainText = document.getElementById('plainEmailBody');
                                        if (plainText) {
                                            if (plainText.getAttribute('data-theme') === 'dark') {
                                                plainText.style.background = '#f8f9fa';
                                                plainText.style.color = '#1a1d21';
                                                plainText.setAttribute('data-theme', 'light');
                                                icon.className = 'ti ti-moon';
                                                span.textContent = 'Tmavý mód';
                                            } else {
                                                plainText.style.background = '#0f1117';
                                                plainText.style.color = 'var(--text-primary)';
                                                plainText.setAttribute('data-theme', 'dark');
                                                icon.className = 'ti ti-sun';
                                                span.textContent = 'Svetlý mód';
                                            }
                                        }
                                    }
                                }
                            </script>
                        <?php endif; ?>
                    </div>

                    <!-- Attachments Section -->
                    <?php if (!empty($msg_detail['attachments'])): ?>
                        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border);">
                            <h5 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                                <i class="ti ti-paperclip" style="color: var(--accent-1);"></i> Prílohy (<?= count($msg_detail['attachments']) ?>)
                            </h5>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px;">
                                <?php foreach ($msg_detail['attachments'] as $att): ?>
                                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 12px; display: flex; flex-direction: column; gap: 10px; transition: transform 0.2s, background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='rgba(255,255,255,0.02)'; this.style.transform='translateY(0)';">
                                        <?php 
                                            $is_image = stripos($att['type'], 'image/') !== false;
                                            $is_video = stripos($att['type'], 'video/') !== false;
                                            $att_url = "email_attachment.php?folder=" . urlencode($folder) . "&msg=" . $view_msg . "&part=" . $att['part_num'];
                                            
                                            // Determine icon based on file type
                                            $icon = 'ti-file';
                                            if ($is_image) $icon = 'ti-photo';
                                            elseif ($is_video) $icon = 'ti-video';
                                            elseif (stripos($att['type'], 'pdf') !== false) $icon = 'ti-file-description';
                                            elseif (stripos($att['type'], 'zip') !== false || stripos($att['type'], 'rar') !== false) $icon = 'ti-zip';
                                            elseif (stripos($att['type'], 'word') !== false || stripos($att['type'], 'text/') !== false) $icon = 'ti-file-text';
                                        ?>
                                        
                                        <div style="height: 130px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.25); border-radius: 12px; font-size: 3.5rem; color: var(--accent-1); border: 1px solid rgba(255,255,255,0.05); box-shadow: inset 0 2px 10px rgba(0,0,0,0.2);">
                                            <i class="ti <?= $icon ?>"></i>
                                        </div>
                                        
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <span style="font-size: 0.85rem; color: var(--text-primary); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($att['filename']) ?>">
                                                <?= htmlspecialchars($att['filename']) ?>
                                            </span>
                                            <span style="font-size: 0.7rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                                                <span><?= strtoupper(explode('/', $att['type'])[1] ?? 'FILE') ?></span>
                                                <span><?= round($att['size'] / 1024, 1) ?> KB</span>
                                            </span>
                                        </div>
                                        <a href="<?= $att_url ?>" download="<?= htmlspecialchars($att['filename']) ?>" class="btn btn-primary btn-sm" style="width: 100%; font-size: 0.85rem; padding: 8px; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);">
                                            <i class="ti ti-download"></i> Stiahnuť súbor
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
                    <div class="card-title">Odpoveď</div>
                </div>
                <form method="POST" style="margin-top: 1rem;" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="send_email">
                    
                    <?php 
                        $reply_to = '';
                        if (preg_match('/<([^>]+)>/', $msg_detail['from'], $matches)) {
                            $reply_to = $matches[1];
                        } else {
                            $reply_to = $msg_detail['from'];
                        }
                    ?>
                    <div class="form-group mb-2">
                        <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:700; margin-bottom:10px; letter-spacing: 0.05em; line-height: 1;"><i data-lucide="user" style="width:14px; height:14px; color:var(--accent);"></i> <span>Komu</span></label>
                        <input type="email" name="to" list="contactEmails" class="form-control" value="<?= htmlspecialchars($reply_to) ?>" required style="font-size: 1.05rem; padding: 0.6rem 1rem;">
                    </div>
                    <div class="form-group mb-2">
                        <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:700; margin-bottom:10px; letter-spacing: 0.05em; line-height: 1;"><i data-lucide="pencil" style="width:14px; height:14px; color:var(--accent);"></i> <span>Predmet</span></label>
                        <?php 
                            $original_subject = $msg_detail['subject'];
                            $new_subject = (stripos($original_subject, 'Re:') === 0) ? $original_subject : 'Re: ' . $original_subject;
                        ?>
                        <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($new_subject) ?>" required style="font-size: 1.05rem; font-weight: bold; padding: 0.6rem 1rem; background: #0f1117; color: var(--text-primary); border: 1px solid var(--border);">
                    </div>
                    <div class="form-group mb-2">
                        <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:700; margin-bottom:12px; letter-spacing: 0.05em; line-height: 1;"><i data-lucide="message-square" style="width:14px; height:14px; color:var(--accent);"></i> <span>Správa</span></label>
                        
                        <div style="border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
                            <div class="ai-assistant-bar">
                                <div class="ai-loading" id="aiLoading">
                                    <i class="ti ti-loader"></i> <span>AI premýšľa...</span>
                                </div>
                                <button type="button" class="btn-ai-minimal" onclick="runAI('generate_email')">
                                    <i data-lucide="sparkles" style="width:14px; height:14px; margin-top: -1px;"></i> Napísať z bodov
                                </button>
                                <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'profesionálny'})">
                                    <i data-lucide="briefcase" style="width:14px; height:14px; margin-top: -1px;"></i> Profesionálne
                                </button>
                                <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'priateľský'})">
                                    <i data-lucide="smile" style="width:14px; height:14px; margin-top: -1px;"></i> Priateľsky
                                </button>
                                <button type="button" class="btn-ai-minimal" onclick="runAI('fix_grammar')">
                                    <i data-lucide="check-circle" style="width:14px; height:14px; margin-top: -1px;"></i> Gramatika
                                </button>
                            </div>

                            <input type="hidden" name="message" id="hiddenMessage">
                            <div id="editor-container" style="min-height: 250px; border: none !important; border-radius: 0;"><?= nl2br($signature) ?></div>
                        </div>
                    </div>
                    <label style="font-weight: 500; margin-bottom: 0.5rem; display:block; margin-top: 1.5rem;">Prílohy <small class="text-muted">(voliteľné)</small></label>
                    <div class="composer-footer-responsive" style="display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-top: 1.5rem;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 15px; background: rgba(0,0,0,0.15); padding: 0.5rem; border-radius: 12px; border: 1px dashed var(--border);">
                                <label for="reply-attachments" class="btn btn-secondary" style="margin: 0; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; height: 38px; border-radius: 12px; padding: 0 1.25rem; font-weight: 600;">
                                    <i data-lucide="paperclip" style="width: 17px; height: 17px;"></i> Priložiť súbory
                                </label>
                                <input type="file" name="attachments[]" id="reply-attachments" multiple style="display: none;" onchange="document.getElementById('reply-file-names').textContent = this.files.length > 0 ? Array.from(this.files).map(f => f.name).join(', ') : 'Nevybraté'">
                                <span id="reply-file-names" style="font-size: 0.85rem; color: var(--text-muted); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">Žiadne prílohy</span>
                            </div>
                        </div>
                        <div class="send-btn-container">
                            <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 1.5rem; border-radius: 12px; display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.88rem; box-shadow: 0 4px 12px rgba(115,103,240,0.2); transition: all 0.2s;">
                                <i data-lucide="send" style="width: 18px; height: 18px;"></i> Odoslať odpoveď
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div class="card" style="padding:0;overflow:hidden;">
                <div class="card-header" style="padding:1rem 1.5rem 0.5rem 1.5rem; border-bottom: none;">
                    <div class="card-title"><?= htmlspecialchars(strtoupper($folder) == 'INBOX' ? 'Doručená pošta' : $folder) ?> <small>(info@vueto.sk)</small></div>
                </div>
                <?php if(empty($emails)): ?>
                <div class="empty-state">
                    <div class="es-icon"><i class="ti ti-mail-opened"></i></div>
                    <p>Žiadne e-maily.</p>
                </div>
                <?php else: ?>
                <form method="POST" id="bulkActionForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="bulk_delete">
                    <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">

                    <div id="bulk-bar-mobile-fix" class="email-action-bar" style="display: flex; align-items: center; background: rgba(255,255,255,0.015); padding: 4px 20px; border-bottom: 1px solid var(--border); margin-top: -2px;">
                        <label class="custom-control" style="margin: 0; width: 36px;">
                                <input type="checkbox" id="selectAll" onchange="const checked = this.checked; document.querySelectorAll('.msg-check').forEach(cb => { cb.checked = checked; }); toggleBulkDeleteBtn();">
                                <span class="custom-checkbox-styled"></span>
                        </label>
                        <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm" style="display:none; font-weight:700; margin-left: auto;"><i class="ti ti-trash"></i> Zmazať</button>
                    </div>

                    <style>
                    <style>
                    <style>
                        /* Stable Vertical Alignment - Standard Flow (No Absolute positioning) */
                        .bulk-select-column {
                            width: 36px !important;
                            display: flex !important;
                            justify-content: center !important;
                            align-items: center !important;
                            flex-shrink: 0 !important;
                            padding: 0 !important;
                        }

                        /* Advanced Custom Checkbox */
                        .custom-control {
                            position: relative;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                        }
                        .custom-control input {
                            position: absolute;
                            opacity: 0;
                            cursor: pointer;
                            height: 0;
                            width: 0;
                        }
                        .custom-checkbox-styled {
                            height: 20px;
                            width: 20px;
                            background-color: rgba(255,255,255,0.05);
                            border: 2px solid rgba(99, 102, 241, 0.4);
                            border-radius: 6px;
                            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .custom-control input:checked ~ .custom-checkbox-styled {
                            background-color: #6366f1;
                            border-color: #6366f1;
                            box-shadow: 0 0 12px rgba(99, 102, 241, 0.4);
                        }
                        .custom-checkbox-styled:after {
                            content: "";
                            display: none;
                            width: 6px;
                            height: 10px;
                            border: solid white;
                            border-width: 0 2.5px 2.5px 0;
                            transform: rotate(45deg);
                            margin-top: -2px;
                        }
                        .custom-control input:checked ~ .custom-checkbox-styled:after {
                            display: block;
                        }

                        .email-row.unread { background: rgba(99, 102, 241, 0.03); }
                        .email-row.unread td:first-child { position: relative; }
                        .email-row.unread td:first-child::before {
                            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
                            background: var(--accent); box-shadow: 2px 0 10px rgba(99,102,241,0.5);
                        }
                        .email-row.read { opacity: 0.8; transition: opacity 0.2s, background 0.2s; }
                        .email-row.read:hover { opacity: 1; background: var(--bg-hover); }
                        
                        .email-icon-box {
                            width: 32px; height: 32px; border-radius: 50%;
                            display: flex; align-items: center; justify-content: center; margin: 0 auto;
                        }
                        .custom-checkbox-styled {
                            width: 18px;
                            height: 18px;
                            border: 2px solid rgba(99, 102, 241, 0.4);
                            border-radius: 5px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.2s;
                            background: rgba(255,255,255,0.03);
                            flex-shrink: 0;
                        }
                        .email-icon-unread {
                            background: rgba(99,102,241,0.15); color: var(--accent-2);
                            border: 1px solid rgba(99,102,241,0.25);
                            box-shadow: 0 0 15px rgba(99,102,241,0.15);
                        }
                    </style>
                    <div style="overflow-x:auto;">
                        <table class="data-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 48px; text-align:center; padding-left: 20px !important;"></th>
                                    <th style="width: 48px; text-align:center;"></th>
                                    <th style="text-align: left !important; padding-left: 10px !important;">OD</th>
                                    <th style="text-align: left !important;">PREDMET</th>
                                    <th style="text-align: left !important;">DÁTUM</th>
                                    <th style="width:50px; text-align:center;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($emails as $email): ?>
                                <tr class="email-row <?= !$email['seen'] ? 'unread' : 'read' ?>" onclick="window.location='email.php?folder=<?= urlencode($folder) ?>&msg=<?= $email['msgno'] ?>'" style="cursor:pointer;">
                                    <td style="text-align:center; padding-left: 20px !important;" onclick="event.stopPropagation();">
                                        <label class="custom-control" style="margin: 0; width: 24px; height: 24px;">
                                            <input type="checkbox" name="msgs[]" value="<?= $email['msgno'] ?>" class="msg-check" onchange="toggleBulkDeleteBtn();">
                                            <span class="custom-checkbox-styled"></span>
                                        </label>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if(!$email['seen']): ?>
                                            <div class="email-icon-box email-icon-unread">
                                                <i class="ti ti-mail" style="font-size:1.15rem;"></i>
                                            </div>
                                        <?php else: ?>
                                            <i class="ti ti-mail-opened" style="font-size:1.3rem; opacity:0.4;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: <?= !$email['seen'] ? '600' : '400' ?>; color: <?= !$email['seen'] ? 'var(--text-primary)' : 'var(--text-secondary)' ?>;"><?= htmlspecialchars(display_name_only(ltrim($email['from'], ' '))) ?></td>
                                    <td style="font-weight: <?= !$email['seen'] ? '600' : '400' ?>; color: <?= !$email['seen'] ? 'var(--text-primary)' : 'var(--text-secondary)' ?>;"><?= htmlspecialchars(ltrim($email['subject'], ' ')) ?></td>
                                    <td class="text-sm" style="color: <?= !$email['seen'] ? 'var(--text-secondary)' : 'var(--text-muted)' ?>;"><?= htmlspecialchars($email['date']) ?></td>
                                    <td style="text-align:center;" onclick="event.stopPropagation();">
                                        <button type="button" data-del-href="email.php?folder=<?= urlencode($folder) ?>&msg=<?= $email['msgno'] ?>&del=1" style="background:none; border:none; color: var(--red); padding: 5px; opacity: 0.7; cursor:pointer;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7" title="Zmazať e-mail">
                                            <i class="ti ti-trash" style="font-size:1.1rem;"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                <script>
                function toggleBulkDeleteBtn() {
                    const checked = document.querySelectorAll('.msg-check:checked').length;
                    document.getElementById('bulkDeleteBtn').style.display = checked > 0 ? 'inline-block' : 'none';
                }
                </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
var quill = null;

async function runAI(action, extraParams = {}) {
    const loading = document.getElementById('aiLoading');
    if (!quill || !loading) return;

    const content = quill.getText().trim();
    if (content.length < 5 && action !== 'generate_email') {
        alert('Prosím, napíšte aspoň krátky text alebo body, aby AI malo z čoho vychádzať.');
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
            quill.setText(data.result);
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
    // Initialize Quill Editor
    var editorCont = document.getElementById('editor-container');
    if (editorCont) {
        quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            },
            placeholder: 'Napíšte vašu správu...'
        });
    }

    // Handle form submission
    var form = document.querySelector('form[method="POST"][enctype="multipart/form-data"]');
    if (form) {
        form.addEventListener('submit', function() {
            var messageInput = document.getElementById('hiddenMessage');
            if (messageInput) {
                messageInput.value = quill.root.innerHTML;
            }
        });
    }

    // Initial expansion for any other textareas if exist
    setTimeout(() => {
        document.querySelectorAll('.auto-expand').forEach(el => {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        });
    }, 500);
});
</script>

<?php 
if ($imap_stream) imap_close($imap_stream);
?>

<!-- Custom Confirm Modal -->
<div id="confirmModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:2rem; max-width:420px; width:90%; box-shadow:0 25px 50px rgba(0,0,0,0.5);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:1rem;">
            <div style="width:40px; height:40px; border-radius:50%; background:rgba(239,68,68,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="ti ti-trash" style="color:#ef4444; font-size:1.4rem;"></i>
            </div>
            <div>
                <div style="font-weight:700; font-size:1.05rem; color:var(--text-primary);">Potvrdiť vymazanie</div>
                <div id="confirmModalMsg" style="font-size:0.9rem; color:var(--text-secondary); margin-top:2px;">Naozaj chcete zmazať túto položku?</div>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:1.5rem;">
            <button onclick="closeConfirmModal()" class="btn btn-secondary" style="padding:0.55rem 1.4rem;">Zrušiť</button>
            <button id="confirmModalOk" class="btn btn-danger" style="padding:0.55rem 1.4rem;"><i class="ti ti-trash"></i> Vymazať</button>
        </div>
    </div>
</div>

<script>
var _confirmCallback = null;
function showConfirm(msg, onOk) {
    document.getElementById('confirmModalMsg').textContent = msg;
    document.getElementById('confirmModal').style.display = 'flex';
    _confirmCallback = onOk;
}
function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    _confirmCallback = null;
}
document.getElementById('confirmModalOk').onclick = function() {
    var cb = _confirmCallback;
    closeConfirmModal();
    if (typeof cb === 'function') cb();
};
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeConfirmModal();
});

var isTrash = <?= json_encode(stripos($folder,'trash')!==false||stripos($folder,'spam')!==false||stripos($folder,'junk')!==false) ?>;

// Single delete via data-del-href attribute
document.querySelectorAll('[data-del-href]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var href = this.getAttribute('data-del-href');
        var msg = isTrash ? 'Natrvalo vymazať tento e-mail?' : 'Presunúť tento e-mail do Koša?';
        showConfirm(msg, function() { window.location.href = href; });
    });
});

// Bulk delete button (type=button, does NOT submit form on click)
var bulkBtn = document.getElementById('bulkDeleteBtn');
if (bulkBtn) {
    bulkBtn.addEventListener('click', function(e) {
        var count = document.querySelectorAll('.msg-check:checked').length;
        var msg = isTrash ? 'Natrvalo vymazať ' + count + ' správ(u)?' : 'Presunúť ' + count + ' správ(u) do Koša?';
        showConfirm(msg, function() { document.getElementById('bulkActionForm').submit(); });
    });
}
</script>

</div>

<script>
// Logic to handle AI generated draft
document.addEventListener('DOMContentLoaded', function() {
    const aiDraft = sessionStorage.getItem('ai_draft');
    if (aiDraft && window.location.search.includes('compose=ai')) {
        // Clear it so it doesn't reopen on next refresh
        sessionStorage.removeItem('ai_draft');
        
        // If we have a msg ID, it's a reply
        if (window.location.search.includes('msg=')) {
            // Wait for Quill to be ready
            setTimeout(() => {
                if (typeof quill !== 'undefined') {
                    quill.setText(aiDraft);
                    // Scroll to reply section
                    const replyCard = document.querySelector('.card-title')?.closest('.card');
                    if (replyCard) replyCard.scrollIntoView({ behavior: 'smooth' });
                } else {
                    const textarea = document.getElementById('editor-container');
                    if (textarea) {
                        textarea.innerText = aiDraft;
                        textarea.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            }, 800);
        } else {
            // It's a new email
            const composeModal = document.getElementById('newEmailModal');
            if (composeModal) {
                composeModal.style.display = 'flex';
                setTimeout(() => {
                    if (typeof quill !== 'undefined') {
                        quill.setText(aiDraft);
                    } else {
                        const textarea = document.querySelector('#newEmailModal textarea[name="message"]');
                        if (textarea) textarea.value = aiDraft;
                    }
                }, 500);
            }
        }
    }
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
