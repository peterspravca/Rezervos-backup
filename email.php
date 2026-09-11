<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/mailer.php';
require_once __DIR__ . '/includes/ai_credit_helper.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/includes/employee_permissions_helper.php';
requireEmployeePermission('crm');

$user_id = (int)$_SESSION['user_id'];
$ai_credits_balance = ai_credit_balance($conn, $user_id);

function decrypt_data($data, $key, $iv_b64) {
    if (empty($data) || empty($iv_b64)) return '';
    $decoded = base64_decode($data);
    if ($decoded === false) return '';
    if (strlen($iv_b64) === 24 && base64_decode($iv_b64, true) !== false) {
        $raw_iv = base64_decode($iv_b64);
    } else {
        $raw_iv = $iv_b64;
    }
    $decrypted = openssl_decrypt($decoded, 'aes-256-cbc', $key, 0, $raw_iv);
    return ($decrypted !== false) ? $decrypted : '';
}

// Load user specific email settings
$stmt = $pdo->prepare("SELECT * FROM user_email_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch();

$is_configured = ($settings && !empty($settings['imap_server']) && !empty($settings['imap_user']) && !empty($settings['imap_pass']));

$tierStmt = $pdo->prepare("SELECT subscription_tier FROM establishments WHERE user_id = ? LIMIT 1");
$tierStmt->execute([$user_id]);
$subscription_tier = strtolower($tierStmt->fetchColumn() ?: 'free');

$pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS marketing_email_sender ENUM('rezervos','own') NOT NULL DEFAULT 'rezervos'");
$senderStmt = $pdo->prepare("SELECT marketing_email_sender FROM establishments WHERE user_id = ? LIMIT 1");
$senderStmt->execute([$user_id]);
$marketing_email_sender = $senderStmt->fetchColumn() ?: 'rezervos';

if ($is_configured) {
    $mail_host = $settings['imap_server'];
    $mail_port = (int)$settings['imap_port'] ?: 993;
    $mail_user = $settings['imap_user'];
    $mail_pass = decrypt_data($settings['imap_pass'], EMAIL_ENC_KEY, $settings['encryption_iv']);
    $smtp_host = !empty($settings['smtp_server']) ? $settings['smtp_server'] : $mail_host;
    $smtp_port = (int)$settings['smtp_port'] ?: 465;
    $smtp_user = !empty($settings['smtp_user']) ? $settings['smtp_user'] : $mail_user;
    $smtp_pass = !empty($settings['smtp_pass']) ? decrypt_data($settings['smtp_pass'], EMAIL_ENC_KEY, $settings['encryption_iv']) : $mail_pass;
} else {
    $mail_host = '';
    $mail_port = 993;
    $mail_user = '';
    $mail_pass = '';
    $smtp_host = '';
    $smtp_port = 465;
    $smtp_user = '';
    $smtp_pass = '';
}

$flash_msg = '';
$flash_error = '';
$folder = $_GET['folder'] ?? 'INBOX';

// Handle POST actions (send email, bulk delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_email') {
        $to = $_POST['to'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['message'] ?? '';
        
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
        
        $result = send_smtp_email($to, $subject, $body, $smtp_host, $smtp_user, $smtp_pass, $attachments, $smtp_port);
        if ($result === true) {
            header('Location: email.php?folder=' . urlencode($folder) . '&sent=1');
            exit;
        } else {
            $flash_error = $result;
        }
    } elseif ($_POST['action'] === 'bulk_delete') {
        if (!empty($_POST['msgs']) && is_array($_POST['msgs']) && $is_configured) {
            $is_destructive = (stripos($folder, 'trash') !== false || stripos($folder, 'junk') !== false || stripos($folder, 'spam') !== false || stripos($folder, 'kos') !== false);
            imap_timeout(IMAP_OPENTIMEOUT, 4);
            $imap_box = "{" . $mail_host . ":" . $mail_port . "/imap/ssl/novalidate-cert}" . $folder;
            $imap_stream_del = @imap_open($imap_box, $mail_user, $mail_pass);
            if ($imap_stream_del) {
                $trash_folder_bulk = null;
                if (!$is_destructive) {
                    $box_list = @imap_list($imap_stream_del, "{" . $mail_host . ":" . $mail_port . "/imap/ssl/novalidate-cert}", "*");
                    if ($box_list) {
                        foreach ($box_list as $b) {
                            $bn = str_replace("{" . $mail_host . ":" . $mail_port . "/imap/ssl/novalidate-cert}", "", $b);
                            if (stripos($bn, 'trash') !== false || stripos($bn, 'kos') !== false) { $trash_folder_bulk = $bn; break; }
                        }
                    }
                }
                $msg_sequence = implode(',', array_map('intval', $_POST['msgs']));
                if ($trash_folder_bulk && !$is_destructive) {
                    @imap_mail_copy($imap_stream_del, $msg_sequence, $trash_folder_bulk, 0);
                }
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

if (isset($_GET['sent'])) $flash_msg = 'E-mail bol úspešne odoslaný.';
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
    if (is_array($decode)) {
        foreach ($decode as $obj) {
            $result .= $obj->text;
        }
    } else {
        $result = $str;
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
        $structure = @imap_fetchstructure($imap, $msg_num);
    }
    
    if ($structure) {
        if ($mime_type == get_mime_type($structure)) {
            $part_num = ($part_num == "") ? "1" : $part_num;
            $body = @imap_fetchbody($imap, $msg_num, $part_num);
            return decode_imap_body($body, $structure->encoding);
        }
        
        if ($structure->type == 1 && isset($structure->parts)) { // multipart
            foreach ($structure->parts as $index => $sub_structure) {
                $prefix = ($part_num != "") ? ($part_num . ".") : "";
                $data = get_email_part($imap, $msg_num, $mime_type, $sub_structure, $prefix . ($index + 1));
                if ($data) return $data;
            }
        }
    }
    return false;
}

function get_mime_type($structure) {
    $primary_types = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if (isset($structure->subtype)) {
        return $primary_types[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
}

function get_attachments_list($imap, $msg_num, $structure = null, $part_num = "") {
    $attachments = [];
    if (!$structure) {
        $structure = @imap_fetchstructure($imap, $msg_num);
    }
    
    if (isset($structure->parts) && count($structure->parts)) {
        foreach ($structure->parts as $index => $part) {
            $current_part_num = ($part_num == "") ? ($index + 1) : ($part_num . "." . ($index + 1));
            
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
            
            if (!$is_attachment && ($part->type == 5 || $part->type == 6)) {
                $is_attachment = true;
                $filename = "subor_" . str_replace('.', '_', $current_part_num);
                if ($part->ifparameters) {
                    foreach ($part->parameters as $object) {
                        if (strtolower($object->attribute) == 'name') $filename = decode_imap_text($object->value);
                    }
                }
                $ext = strtolower($part->subtype ?? 'bin');
                if (!str_ends_with(strtolower($filename), '.' . $ext)) {
                    if ($ext == 'jpeg') $ext = 'jpg';
                    $filename .= '.' . $ext;
                }
            }
            
            if ($is_attachment) {
                $attachments[] = [
                    'part_num' => $current_part_num,
                    'filename' => $filename ?: 'priloha',
                    'type' => get_mime_type($part),
                    'size' => $part->bytes ?? 0
                ];
            } elseif ($part->type == 1) { // multipart
                $attachments = array_merge($attachments, get_attachments_list($imap, $msg_num, $part, $current_part_num));
            }
        }
    }
    return $attachments;
}

$emails = [];
$view_msg = null;
$msg_detail = null;
$available_folders = [];
$imap_stream = false;

if ($is_configured) {
    imap_timeout(IMAP_OPENTIMEOUT, 5);
    imap_timeout(IMAP_READTIMEOUT, 5);
    
    $try_boxes = [];
    if ($mail_port === 993) {
        $try_boxes[] = "{" . $mail_host . ":" . $mail_port . "/imap/ssl/novalidate-cert}" . $folder;
        $try_boxes[] = "{" . $mail_host . ":" . $mail_port . "/imap/ssl}" . $folder;
    } else {
        $try_boxes[] = "{" . $mail_host . ":" . $mail_port . "/imap/tls/novalidate-cert}" . $folder;
        $try_boxes[] = "{" . $mail_host . ":" . $mail_port . "/imap/notls}" . $folder;
        $try_boxes[] = "{" . $mail_host . ":" . $mail_port . "/imap}" . $folder;
    }
    
    foreach ($try_boxes as $b_str) {
        $imap_stream = @imap_open($b_str, $mail_user, $mail_pass);
        if ($imap_stream) break;
    }
}

if ($imap_stream) {
    $boxes = @imap_getmailboxes($imap_stream, "{" . $mail_host . ":" . $mail_port . "/imap/ssl/novalidate-cert}", "*");
    if (!$boxes) $boxes = @imap_getmailboxes($imap_stream, "{" . $mail_host . ":" . $mail_port . "/imap/ssl}", "*");
    if ($boxes) {
        foreach ($boxes as $val) {
            $name = preg_replace('/^\{.*?\}/', '', $val->name);
            $available_folders[] = $name;
        }
    }

    if (isset($_GET['msg'])) {
        $view_msg = (int)$_GET['msg'];
        if (isset($_GET['del']) && $_GET['del'] == 1) {
            $is_destructive_folder = (stripos($folder, 'trash') !== false || stripos($folder, 'junk') !== false || stripos($folder, 'spam') !== false || stripos($folder, 'kos') !== false);
            $trash_folder = null;
            if (!$is_destructive_folder) {
                foreach ($available_folders as $af) {
                    if (stripos($af, 'trash') !== false || stripos($af, 'kos') !== false) { $trash_folder = $af; break; }
                }
            }
            if ($trash_folder) {
                @imap_mail_copy($imap_stream, "$view_msg", $trash_folder, 0);
            }
            @imap_delete($imap_stream, $view_msg);
            @imap_expunge($imap_stream);
            $loc = $trash_folder ? '&moved=1' : '&deleted=1';
            header("Location: email.php?folder=" . urlencode($folder) . $loc);
            exit;
        }

        $header = @imap_headerinfo($imap_stream, $view_msg);
        
        $body = get_email_part($imap_stream, $view_msg, "TEXT/HTML");
        $is_html = true;
        
        if (!$body) {
            $body = get_email_part($imap_stream, $view_msg, "TEXT/PLAIN");
            $is_html = false;
        }
        
        if (!$body) {
            $body = "(Žiadny obsah správy)";
        }
        
        $attachments = get_attachments_list($imap_stream, $view_msg);
        
        if (!mb_check_encoding($body, 'UTF-8')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'auto');
        }
        
        $subject = decode_imap_text($header->subject ?? '');
        $from = decode_imap_text($header->fromaddress ?? '');
        $to = decode_imap_text($header->toaddress ?? '');
        @imap_setflag_full($imap_stream, $view_msg, "\\Seen");

        $msg_detail = [
            'subject' => $subject ?: '(Bez predmetu)',
            'from' => $from,
            'to' => $to,
            'date' => date('d.m.Y H:i', strtotime($header->date ?? 'now')),
            'body' => $body,
            'is_html' => $is_html,
            'attachments' => $attachments
        ];
    } else {
        $num_msgs = @imap_num_msg($imap_stream);
        if ($num_msgs > 0) {
            $start = max(1, $num_msgs - 50);
            $result = @imap_fetch_overview($imap_stream, "$start:$num_msgs", 0);
            if (is_array($result)) {
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
    if ($is_configured) {
        $last_err = imap_last_error();
        $flash_error = 'Nepodarilo sa pripojiť k IMAP serveru (' . htmlspecialchars($mail_host) . '). Skontrolujte heslo a nastavenia schránky. ' . ($last_err ? '(' . htmlspecialchars($last_err) . ')' : '');
    }
}

// Podpis pre salón
$signature = "\n\n--\nS pozdravom,\n" . BRAND_NAME . "\n✉️ " . htmlspecialchars($mail_user);
?>
<?php
$pageTitle = 'E-mail - ' . BRAND_NAME;
$currentPage = 'email';
require_once 'includes/dashboard-head.php';
?>
    <style>
        :root {
            --bg-color: #ffffff;
            --card-bg: #ffffff;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --primary-color: #b08042;
            --primary-hover: #9c6f35;
            --sidebar-bg: #111827;
            --sidebar-hover: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --input-bg: #f9fafb;
        }

        body.dark-mode {
            --bg-color: #0d0f12;
            --card-bg: rgba(20, 24, 28, 0.7);
            --text-primary: #ffffff;
            --text-secondary: #a0a5ab;
            --border-color: rgba(255, 255, 255, 0.1);
            --sidebar-bg: #14181c;
            --sidebar-hover: rgba(255,255,255,0.05);
            --shadow-sm: 0 4px 20px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 8px 30px rgba(0, 0, 0, 0.5);
            --input-bg: #1a1e23;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar štýly (.admin-sidebar, .admin-menu, .logout-container, ...) sú teraz
           len v zdieľanom includes/dashboard-head.php — už sa tu neduplikujú, aby sa
           nemohli rozísť s ostatnými stránkami. */

        /* Main */
        .admin-main {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow-y: auto;
            background-color: var(--bg-color);
        }

        .admin-header {
            height: 60px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--card-bg);
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .admin-header-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            height: 26px;
            line-height: 26px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .theme-btn {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .theme-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-primary {
            background: var(--primary-color);
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-secondary, .btn {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-secondary:hover, .btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-danger:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Container & Alerts */
        .email-container {
            padding: 24px 30px;
            flex-grow: 1;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #10b981;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 600;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #ef4444;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Folder Tabs */
        .email-folder-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .folder-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background: var(--card-bg);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13.5px;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .folder-tab:hover {
            border-color: var(--primary-color);
            color: var(--text-primary);
        }

        .folder-tab.active {
            background: var(--primary-color) !important;
            color: #ffffff !important;
            border-color: var(--primary-color) !important;
        }

        /* AI Tab */
        .folder-tab-ai {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background: linear-gradient(135deg, #b08042 0%, #d4a853 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
            font-family: inherit;
            margin-left: auto;
        }
        .folder-tab-ai:hover { opacity: 0.9; transform: translateY(-1px); }
        .folder-tab-ai.open { background: linear-gradient(135deg, #8a6030 0%, #b08042 100%); }

        /* AI Panel */
        #ai-email-panel {
            display: none;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .ai-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, rgba(176,128,66,0.08) 0%, rgba(176,128,66,0.03) 100%);
        }
        .ai-panel-header-left {
            display: flex; align-items: center; gap: 10px;
            font-size: 14.5px; font-weight: 700; color: var(--text-primary);
        }
        .ai-chat-area {
            height: 340px;
            overflow-y: auto;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .ai-bubble {
            max-width: 78%;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13.5px;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .ai-bubble.user {
            align-self: flex-end;
            background: var(--primary-color);
            color: #ffffff;
            border-bottom-right-radius: 3px;
        }
        .ai-bubble.assistant {
            align-self: flex-start;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-bottom-left-radius: 3px;
        }
        .ai-bubble.typing {
            align-self: flex-start;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-bottom-left-radius: 3px;
            font-style: italic;
        }
        .ai-quick-actions {
            padding: 10px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            border-top: 1px solid var(--border-color);
            background: var(--bg-color);
        }
        .ai-quick-btn {
            padding: 5px 12px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s ease;
            display: flex; align-items: center; gap: 5px;
        }
        .ai-quick-btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
        .ai-input-row {
            display: flex;
            gap: 10px;
            padding: 12px 20px;
            border-top: 1px solid var(--border-color);
        }
        .ai-input-row textarea {
            flex: 1;
            resize: none;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 13.5px;
            font-family: inherit;
            background: var(--bg-color);
            color: var(--text-primary);
            line-height: 1.4;
            max-height: 90px;
        }
        .ai-input-row textarea:focus { outline: none; border-color: var(--primary-color); }
        .ai-send-btn {
            padding: 0 18px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            font-family: inherit;
            display: flex; align-items: center; gap: 6px;
            transition: opacity 0.2s;
        }
        .ai-send-btn:disabled { opacity: 0.55; cursor: not-allowed; }

        /* Card container */
        .vueto-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        /* Email Table */
        .email-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        .email-table th {
            padding: 14px 18px;
            text-align: left;
            font-weight: 700;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-color);
        }

        .email-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .email-table tr.email-row {
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .email-table tr.email-row:hover {
            background: var(--bg-color);
        }

        .email-table tr.email-row.unread {
            background: rgba(176, 128, 66, 0.04);
            font-weight: 700;
        }

        /* Custom Checkbox */
        .custom-chk {
            width: 17px;
            height: 17px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        /* Quill Editor styling */
        .ql-toolbar.ql-snow {
            border-color: var(--border-color) !important;
            background: var(--bg-color) !important;
            border-radius: 8px 8px 0 0 !important;
        }
        .ql-container.ql-snow {
            border-color: var(--border-color) !important;
            background: var(--card-bg) !important;
            border-radius: 0 0 8px 8px !important;
            font-size: 14px !important;
            color: var(--text-primary) !important;
        }
        .ql-editor {
            min-height: 180px !important;
        }

        /* Toast */
        #app-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #111827;
            color: #ffffff;
            padding: 12px 22px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 99999;
            border: 1px solid rgba(255,255,255,0.1);
        }
        #app-toast.success { border-color: #10b981; }
        #app-toast.error { border-color: #ef4444; }
    </style>


<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>

    <!-- MAIN -->
    <div class="admin-main">
        <?php $headerTitle = 'E-mailová schránka'; $headerIcon = 'mail'; require_once 'includes/dashboard-topbar.php'; ?>

        <?php if ($subscription_tier === 'free'): ?>
        <div class="vueto-card" style="padding: 54px 24px; text-align: center; max-width: 620px; margin: 30px auto; border-radius: 16px; position: relative; overflow: hidden;">
            <div style="position:absolute; top:16px; right:16px; background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-secondary); font-size:11.5px; font-weight:700; padding:4px 10px; border-radius:8px; display:flex; align-items:center; gap:5px;">
                <span class="material-symbols-outlined" style="font-size:14px;">lock</span> Od balíka Štart
            </div>
            <div style="width: 76px; height: 76px; border-radius: 20px; background: rgba(176, 128, 66, 0.12); color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <span class="material-symbols-outlined" style="font-size: 40px;">forward_to_inbox</span>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0 0 10px 0;">Odomknite si vlastnú e-mailovú schránku</h2>
            <p style="font-size: 14px; color: var(--text-secondary); max-width: 480px; margin: 0 auto 24px auto; line-height: 1.5;">
                V balíku Free táto funkcia nie je sprístupnená. Od balíka Štart vyššie získate priamo tu:
            </p>
            <div style="text-align:left; max-width: 460px; margin: 0 auto 28px auto; display: flex; flex-direction: column; gap: 14px;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <span class="material-symbols-outlined" style="font-size: 22px; color: #10b981; flex-shrink:0;">forward_to_inbox</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--text-primary);">Vlastnú schránku priamo v Rezervose</p>
                        <p style="margin:2px 0 0 0; font-size:12.5px; color:var(--text-secondary);">Čítajte a odpovedajte na správy od zákazníkov bez prepínania do iného e-mailu.</p>
                    </div>
                </div>
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <span class="material-symbols-outlined" style="font-size: 22px; color: #10b981; flex-shrink:0;">celebration</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--text-primary);">Vlastného odosielateľa pre pozdravy a kampane</p>
                        <p style="margin:2px 0 0 0; font-size:12.5px; color:var(--text-secondary);">Automatické pozdravy k meninám a narodeninám aj marketingové kampane môžu chodiť z vášho mailu namiesto nášho — pôsobí to osobnejšie a znižuje riziko, že správa skončí v spame.</p>
                    </div>
                </div>
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <span class="material-symbols-outlined" style="font-size: 22px; color: #10b981; flex-shrink:0;">lock</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--text-primary);">Bezpečné šifrovanie AES-256</p>
                        <p style="margin:2px 0 0 0; font-size:12.5px; color:var(--text-secondary);">Prihlasovacie údaje k vašej schránke sú u nás šifrované, nie uložené v čitateľnej podobe.</p>
                    </div>
                </div>
            </div>
            <a href="dashboard-balik.php" class="btn-primary" style="padding: 12px 28px; font-size: 14.5px; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; margin: 0 auto; cursor: pointer;">
                <span class="material-symbols-outlined" style="font-size: 20px;">upgrade</span>
                <span>Prejsť na vyšší balík</span>
            </a>
        </div>
        <?php else: ?>
        <div style="display:flex;gap:10px;align-items:center;padding:12px 20px;border-bottom:1px solid var(--border-color);flex-wrap:wrap;">
            <button type="button" onclick="openComposeModal()" class="btn-primary">
                <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
                <span>Nová správa</span>
            </button>
            <?php if ($is_configured): ?>
            <button type="button" onclick="openEmailSettingsModal()" class="btn">
                <span class="material-symbols-outlined" style="font-size:18px;">settings</span>
                <span>Nastavenia e-mailu</span>
            </button>
            <?php else: ?>
            <button type="button" onclick="openEmailSettingsModal()" class="btn">
                <span class="material-symbols-outlined" style="font-size:18px;">link</span>
                <span>Prepojiť e-mail</span>
            </button>
            <?php endif; ?>
            <?php if ($is_configured): ?>
            <span style="font-size:12px;color:var(--text-secondary);background:var(--bg-color);border:1px solid var(--border-color);padding:3px 10px;border-radius:6px;"><?= htmlspecialchars($mail_user) ?></span>
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-secondary);margin-left:auto;">
                <span class="material-symbols-outlined" style="font-size:16px;">campaign</span>
                <span>Pozdravy a kampane posielať z:</span>
                <select id="marketing-sender-select" onchange="setMarketingSender(this.value)" style="font-size:12.5px;padding:4px 8px;border-radius:6px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);">
                    <option value="rezervos" <?= $marketing_email_sender === 'rezervos' ? 'selected' : '' ?>>Rezervos (predvolené)</option>
                    <option value="own" <?= $marketing_email_sender === 'own' ? 'selected' : '' ?>>Táto schránka</option>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div class="email-container">
            <?php if ($flash_msg): ?>
                <div class="alert-success"><?= htmlspecialchars($flash_msg) ?></div>
            <?php endif; ?>

            <?php if ($flash_error): ?>
                <div class="alert-danger">
                    <span><?= htmlspecialchars($flash_error) ?></span>
                    <button type="button" onclick="openEmailSettingsModal()" class="btn-primary" style="padding:4px 12px; font-size:12px;">Prepojiť teraz</button>
                </div>
            <?php endif; ?>

            <?php if (!$is_configured): ?>
                <!-- ONBOARDING WELCOME CARD -->
                <div class="vueto-card" style="padding: 54px 24px; text-align: center; max-width: 680px; margin: 30px auto; border-radius: 16px;">
                    <div style="width: 76px; height: 76px; border-radius: 20px; background: rgba(176, 128, 66, 0.12); color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                        <span class="material-symbols-outlined" style="font-size: 40px;">forward_to_inbox</span>
                    </div>
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0 0 10px 0;">Prepojte si svoju vlastnú e-mailovú schránku</h2>
                    <p style="font-size: 14px; color: var(--text-secondary); max-width: 500px; margin: 0 auto 24px auto; line-height: 1.5;">
                        Pripojte si ľubovoľný e-mail (Gmail, Seznam, Zoznam, WebSupport, Webglobe alebo vlastnú doménu) a vybavujte správy od zákazníkov priamo z vášho konta.
                    </p>
                    <div style="display: flex; justify-content: center; gap: 12px; margin-bottom: 28px; flex-wrap: wrap;">
                        <span style="font-size: 12px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 15px; color: #10b981;">check_circle</span> Bezpečné šifrovanie AES-256
                        </span>
                        <span style="font-size: 12px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 15px; color: #10b981;">check_circle</span> Rýchle automatické predvoľby
                        </span>
                        <span style="font-size: 12px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 15px; color: #10b981;">check_circle</span> Vlastný odosielateľ a príjem
                        </span>
                    </div>
                    <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px 20px; margin: 0 auto 28px auto; max-width: 560px; text-align: left; display: flex; gap: 14px; align-items: flex-start;">
                        <span class="material-symbols-outlined" style="font-size: 26px; color: var(--primary-color); flex-shrink: 0;">celebration</span>
                        <div>
                            <p style="margin: 0 0 4px 0; font-weight: 700; font-size: 13.5px; color: var(--text-primary);">Váš balík túto funkciu už podporuje</p>
                            <p style="margin: 0; font-size: 13px; color: var(--text-secondary); line-height: 1.55;">
                                Po prepojení schránky si budete môcť vybrať, či zákazníkom budú chodiť automatické pozdravy k meninám a narodeninám a vaše marketingové kampane z vášho vlastného mailu, alebo naďalej z Rezervosu — vlastný mail pôsobí osobnejšie, buduje dôveru vo vzťahu so zákazníkom a znižuje riziko, že správa skončí v spame. V pätičke e-mailu vždy zostane odkaz na Rezervos.
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="openEmailSettingsModal()" class="btn-primary" style="padding: 12px 28px; font-size: 14.5px; font-weight: 700; border-radius: 10px; margin: 0 auto; cursor: pointer;">
                        <span class="material-symbols-outlined" style="font-size: 20px;">add_link</span>
                        <span>Prepojiť existujúci e-mail teraz</span>
                    </button>
                </div>
            <?php else: ?>
                <!-- Folder Navigation Tabs -->
                <div class="email-folder-tabs">
                    <?php 
                    $display_folders = [
                        'INBOX' => ['label' => 'Doručené', 'icon' => 'inbox'],
                        'Sent' => ['label' => 'Odoslané', 'icon' => 'send'],
                        'Trash' => ['label' => 'Kôš', 'icon' => 'delete'],
                        'Spam' => ['label' => 'Spam', 'icon' => 'report']
                    ];
                    
                    foreach ($display_folders as $key => $info):
                        $actual_folder = $key;
                        foreach ($available_folders as $af) {
                            if (stripos($af, $key) !== false) { $actual_folder = $af; break; }
                            if ($key == 'Sent' && stripos($af, 'Odoslan') !== false) { $actual_folder = $af; break; }
                            if ($key == 'Trash' && stripos($af, 'kos') !== false) { $actual_folder = $af; break; }
                        }
                        $is_active = (strtoupper($folder) == strtoupper($actual_folder));
                    ?>
                        <a href="email.php?folder=<?= urlencode($actual_folder) ?>" class="folder-tab <?= $is_active ? 'active' : '' ?>">
                            <span class="material-symbols-outlined" style="font-size:18px;"><?= $info['icon'] ?></span>
                            <?= $info['label'] ?>
                        </a>
                    <?php endforeach; ?>
                    <!-- AI Asistent Tab -->
                    <button type="button" id="ai-tab-btn" class="folder-tab-ai" onclick="toggleAiPanel()">
                        <span class="material-symbols-outlined" style="font-size:18px;">auto_awesome</span>
                        AI asistent
                    </button>
                </div>

                <!-- AI Panel -->
                <?php
                    // Build email context if viewing a message
                    $ai_email_context = '';
                    if (!empty($msg_detail)) {
                        $ai_email_context = 'Predmet: ' . ($msg_detail['subject'] ?? '') . "\n";
                        $ai_email_context .= 'Od: ' . ($msg_detail['from'] ?? '') . "\n";
                        $ai_email_context .= 'Dátum: ' . ($msg_detail['date'] ?? '') . "\n\n";
                        $body = $msg_detail['body'] ?? '';
                        if (!empty($msg_detail['is_html'])) {
                            $body = strip_tags($body);
                        }
                        $ai_email_context .= mb_substr(trim($body), 0, 1200);
                    }
                ?>
                <div id="ai-email-panel">
                    <div class="ai-panel-header">
                        <div class="ai-panel-header-left">
                            <span class="material-symbols-outlined" style="font-size:22px;color:var(--primary-color);">auto_awesome</span>
                            AI asistent
                            <span style="font-size:11.5px;font-weight:400;color:var(--text-secondary);padding:2px 8px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:6px;">Beta</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if (!empty($ai_email_context)): ?>
                            <span style="font-size:12px;color:var(--text-secondary);display:flex;align-items:center;gap:5px;">
                                <span class="material-symbols-outlined" style="font-size:15px;color:#10b981;">check_circle</span>
                                E-mail načítaný ako kontext
                            </span>
                            <?php endif; ?>
                            <span id="ai-credits-badge" style="font-size:12px;font-weight:700;color:var(--text-primary);background:var(--bg-color);border:1px solid var(--border-color);border-radius:8px;padding:4px 10px;display:flex;align-items:center;gap:5px;">
                                <span class="material-symbols-outlined" style="font-size:14px;color:var(--primary-color);">bolt</span>
                                Kredity: <span id="ai-credits-count"><?= (int)$ai_credits_balance ?></span>
                            </span>
                            <button type="button" onclick="openAiCreditsModal()" style="font-size:12px;font-weight:700;color:#fff;background:var(--primary-color);border:none;border-radius:8px;padding:5px 12px;cursor:pointer;">Dokúpiť</button>
                            <button type="button" onclick="toggleAiPanel()" style="background:none;border:none;cursor:pointer;color:var(--text-secondary);display:flex;align-items:center;">
                                <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                            </button>
                        </div>
                    </div>
                    <div class="ai-chat-area" id="ai-chat-area">
                        <div class="ai-bubble assistant">Dobrý deň! Som AI asistent pre <?= BRAND_NAME ?>. Pomôžem vám s odpoveďami zákazníkom, tvorbou e-mailov a ich úpravou. <?php echo !empty($ai_email_context) ? 'Vidím aktuálne zobrazený e-mail a môžem s ním pracovať.' : 'Môžete mi napísať čo potrebujete.' ?></div>
                    </div>
                    <!-- Quick action buttons -->
                    <div class="ai-quick-actions" id="ai-quick-actions">
                        <?php if (!empty($ai_email_context)): ?>
                        <button type="button" class="ai-quick-btn" onclick="aiQuickAction('summarize_email', '<?= addslashes($ai_email_context) ?>')">
                            <span class="material-symbols-outlined" style="font-size:14px;">summarize</span> Zhrnutie e-mailu
                        </button>
                        <button type="button" class="ai-quick-btn" onclick="aiQuickAction('generate_reply', 'Napíš profesionálnu odpoveď.')">
                            <span class="material-symbols-outlined" style="font-size:14px;">reply</span> Napísať odpoveď
                        </button>
                        <?php endif; ?>
                        <button type="button" class="ai-quick-btn" onclick="aiQuickAction('generate_email', 'Napíš profesionálny e-mail zákazníkovi o potvrdení rezervácie.')">
                            <span class="material-symbols-outlined" style="font-size:14px;">edit</span> Napísať e-mail
                        </button>
                        <button type="button" class="ai-quick-btn" onclick="showAiInput('Preformuluj tento text profesionálnejšie: ')">
                            <span class="material-symbols-outlined" style="font-size:14px;">auto_fix_high</span> Preformulovať
                        </button>
                        <button type="button" class="ai-quick-btn" onclick="aiClearChat()">
                            <span class="material-symbols-outlined" style="font-size:14px;">restart_alt</span> Vyčistiť
                        </button>
                    </div>
                    <div class="ai-input-row">
                        <textarea id="ai-user-input" rows="2" placeholder="Napíšte správu pre AI asistenta..." onkeydown="aiInputKeydown(event)"></textarea>
                        <button type="button" class="ai-send-btn" id="ai-send-btn" onclick="aiSendMessage()">
                            <span class="material-symbols-outlined" style="font-size:18px;">send</span>
                            Odoslať
                        </button>
                    </div>
                </div>
                <script>
                    var aiEmailContext = <?= json_encode($ai_email_context) ?>;
                </script>

                <!-- Modal na dokúpenie AI kreditov -->
                <div id="ai-credits-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
                    <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:24px;max-width:480px;width:92%;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <h3 style="margin:0;font-size:17px;font-weight:800;display:flex;align-items:center;gap:8px;">
                                <span class="material-symbols-outlined" style="color:var(--primary-color);">bolt</span> Dokúpiť AI kredity
                            </h3>
                            <button type="button" onclick="closeAiCreditsModal()" style="background:none;border:none;cursor:pointer;color:var(--text-secondary);"><span class="material-symbols-outlined">close</span></button>
                        </div>
                        <div id="ai-credits-exhausted-banner" style="display:none;align-items:flex-start;gap:8px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);color:#ef4444;font-size:12.5px;font-weight:600;padding:10px 14px;border-radius:8px;margin-bottom:14px;">
                            <span class="material-symbols-outlined" style="font-size:18px;">error</span>
                            <span>Vyčerpali ste všetky AI kredity. Ak chcete túto funkciu naďalej používať, dobite si kredit v Peňaženke — pri kúpe balíčka nižšie sa vám automaticky strhne.</span>
                        </div>
                        <button type="button" onclick="shareForAiCredit()" style="width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border:1px dashed var(--border-color);border-radius:8px;background:none;color:var(--text-primary);font-size:12.5px;font-weight:700;cursor:pointer;margin-bottom:16px;">
                            <span class="material-symbols-outlined" style="font-size:16px;color:#1877f2;">share</span> Alebo zdieľajte na Facebooku a získajte kredit (+0,10 €)
                        </button>
                        <p style="font-size:13px;color:var(--text-secondary);margin:0 0 18px;">Kredity sa strhávajú z Peňaženky pri každom použití AI asistenta (e-maily, kontrola gramatiky, tvorba kampaní). Ak máte kredit získaný zdieľaním, minie sa vždy prednostne, až potom sa siahne na reálne dobitý kredit.</p>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <?php foreach (AI_CREDIT_PACKAGES as $pkg_count => $pkg_price): ?>
                            <button type="button" onclick="buyAiCredits(<?= (int)$pkg_count ?>)" style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border:1px solid var(--border-color);border-radius:8px;background:var(--bg-color);cursor:pointer;text-align:left;">
                                <span style="font-weight:700;font-size:14px;color:var(--text-primary);"><?= (int)$pkg_count ?> AI kreditov</span>
                                <span style="font-weight:800;font-size:15px;color:var(--primary-color);"><?= number_format($pkg_price, 2, ',', ' ') ?> €</span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <script>
                    function openAiCreditsModal(exhausted) {
                        document.getElementById('ai-credits-exhausted-banner').style.display = exhausted ? 'flex' : 'none';
                        document.getElementById('ai-credits-modal').style.display = 'flex';
                    }
                    function closeAiCreditsModal() { document.getElementById('ai-credits-modal').style.display = 'none'; }

                    function shareForAiCredit() {
                        var url = 'https://' + <?= json_encode(BRAND_SITE) ?>;
                        var fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
                        var win = window.open(fbUrl, 'fb-share', 'width=600,height=500');
                        var timer = setInterval(function () {
                            if (!win || win.closed) {
                                clearInterval(timer);
                                claimAiShareReward();
                            }
                        }, 1000);
                    }

                    async function claimAiShareReward() {
                        try {
                            var fd = new FormData();
                            fd.append('action', 'claim_share_reward'); fd.append('platform', 'facebook'); fd.append('target', 'app');
                            var res = await fetch('api/wallet.php', { method: 'POST', body: fd });
                            var data = await res.json();
                            if (data.success) {
                                showAppToast(data.message || 'Kredit bol pripísaný!', 'success');
                            } else if (!data.already_claimed) {
                                showAppToast(data.error || 'Odmenu sa nepodarilo pripísať.', 'error');
                            } else {
                                showAppToast(data.error || 'Dnešná odmena už bola vyčerpaná.', 'warning');
                            }
                        } catch (e) {}
                    }

                    async function buyAiCredits(count) {
                        try {
                            var fd = new FormData();
                            fd.append('action', 'buy_ai_credits');
                            fd.append('count', count);
                            var res = await fetch('api/wallet.php', { method: 'POST', body: fd });
                            var data = await res.json();
                            if (data.success) {
                                document.getElementById('ai-credits-count').textContent = data.new_ai_credits;
                                closeAiCreditsModal();
                                showAppToast(data.message || 'AI kredity boli pripísané.', 'success');
                            } else {
                                showAppToast(data.error || 'Nákup sa nepodaril.', 'error');
                            }
                        } catch (e) {
                            showAppToast('Chyba pripojenia.', 'error');
                        }
                    }
                </script>

                <?php if ($view_msg && $msg_detail): ?>
                    <!-- DETAIL VIEW OF EMAIL -->
                    <div class="vueto-card" style="margin-bottom: 25px;">
                        <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <a href="email.php?folder=<?= urlencode($folder) ?>" class="btn">
                                <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Späť do schránky
                            </a>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" id="emailThemeBtn" onclick="toggleEmailTheme()" class="btn" style="font-size: 12px; padding: 6px 12px;">
                                    <span class="material-symbols-outlined" style="font-size:16px;">light_mode</span> <span>Svetlý mód správy</span>
                                </button>
                                <a href="email.php?folder=<?= urlencode($folder) ?>&msg=<?= $view_msg ?>&del=1" class="btn-danger">
                                    <span class="material-symbols-outlined" style="font-size:18px;">delete</span> Zmazať
                                </a>
                            </div>
                        </div>

                        <div style="padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                <h2 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.3;">
                                    <?= htmlspecialchars($msg_detail['subject']) ?>
                                </h2>
                                <span style="font-size: 12.5px; color: var(--text-secondary);"><?= htmlspecialchars($msg_detail['date']) ?></span>
                            </div>

                            <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: 13.5px; display: flex; flex-direction: column; gap: 6px;">
                                <div>
                                    <span style="color: var(--text-secondary); font-weight: 600;">Od:</span>
                                    <strong style="color: var(--text-primary);"><?= htmlspecialchars(display_name_only($msg_detail['from'])) ?></strong>
                                    <span style="color: var(--text-secondary); font-size: 12px;">&lt;<?php preg_match('/<([^>]+)>/', $msg_detail['from'], $m); echo htmlspecialchars($m[1] ?? $msg_detail['from']); ?>&gt;</span>
                                </div>
                                <div>
                                    <span style="color: var(--text-secondary); font-weight: 600;">Komu:</span>
                                    <span style="color: var(--text-primary);"><?= htmlspecialchars(display_name_only($msg_detail['to'])) ?></span>
                                </div>
                            </div>

                            <!-- Email Body -->
                            <div style="background: #0f1117; border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
                                <?php if (!empty($msg_detail['is_html'])): ?>
                                    <?php 
                                        $base_css = "<style>
                                            html, body { background: #0f1117 !important; margin: 0; padding: 0; }
                                            .email-internal-wrapper { 
                                                font-family: 'Outfit', sans-serif;
                                                font-size: 14.5px !important; 
                                                line-height: 1.6 !important; 
                                                padding: 24px !important;
                                                color: #ffffff !important;
                                                min-height: 250px;
                                            }
                                            .email-internal-wrapper.no-filter { background: #ffffff !important; color: #111827 !important; }
                                            p, div { margin-bottom: 12px; }
                                            img { max-width: 100% !important; height: auto !important; }
                                            a { color: #b08042; text-decoration: underline; }
                                        </style><base target='_blank'>";
                                        $styled_body = $base_css . '<div class="email-internal-wrapper" id="filterWrapper">' . $msg_detail['body'] . '</div>';
                                    ?>
                                    <iframe id="emailIframe" data-theme="dark" sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation" srcdoc="<?= htmlspecialchars($styled_body) ?>" style="width: 100%; min-height: 300px; border: none; display: block; background: #0f1117;" scrolling="no" onload="if(this.contentWindow) { this.style.height = (this.contentWindow.document.documentElement.scrollHeight + 40) + 'px'; }"></iframe>
                                <?php else: ?>
                                    <div id="plainEmailBody" style="font-size: 14.5px; color: #ffffff; white-space: pre-wrap; line-height: 1.7; word-break: break-word; padding: 24px; min-height: 250px; background: #0f1117;">
                                        <?= htmlspecialchars(trim($msg_detail['body'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Attachments -->
                            <?php if (!empty($msg_detail['attachments'])): ?>
                                <div style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-bottom: 20px;">
                                    <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                        <span class="material-symbols-outlined" style="color: var(--primary-color); font-size: 18px;">attachment</span>
                                        <span>Prílohy (<?= count($msg_detail['attachments']) ?>)</span>
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px;">
                                        <?php foreach ($msg_detail['attachments'] as $att): ?>
                                            <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <span class="material-symbols-outlined" style="font-size: 28px; color: var(--primary-color);">draft</span>
                                                    <div style="overflow: hidden;">
                                                        <div style="font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($att['filename']) ?>">
                                                            <?= htmlspecialchars($att['filename']) ?>
                                                        </div>
                                                        <div style="font-size: 11.5px; color: var(--text-secondary);"><?= round($att['size'] / 1024, 1) ?> KB</div>
                                                    </div>
                                                </div>
                                                <a href="email_attachment.php?folder=<?= urlencode($folder) ?>&msg=<?= $view_msg ?>&part=<?= $att['part_num'] ?>" class="btn" style="justify-content: center; font-size: 12px; padding: 6px;">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">download</span> Stiahnuť
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- REPLY FORM -->
                    <div class="vueto-card" style="margin-bottom: 40px;">
                        <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color);">
                            <h3 style="font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                                <span class="material-symbols-outlined" style="color: var(--primary-color);">reply</span>
                                <span>Rýchla odpoveď</span>
                            </h3>
                        </div>
                        <form method="POST" style="padding: 24px;" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="send_email">
                            <?php 
                                $reply_to = '';
                                if (preg_match('/<([^>]+)>/', $msg_detail['from'], $matches)) {
                                    $reply_to = $matches[1];
                                } else {
                                    $reply_to = $msg_detail['from'];
                                }
                                $orig_subj = $msg_detail['subject'];
                                $new_subj = (stripos($orig_subj, 'Re:') === 0) ? $orig_subj : 'Re: ' . $orig_subj;
                            ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                <div>
                                    <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:6px;">Komu</label>
                                    <input type="email" name="to" value="<?= htmlspecialchars($reply_to) ?>" required style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13.5px; outline:none;">
                                </div>
                                <div>
                                    <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:6px;">Predmet</label>
                                    <input type="text" name="subject" value="<?= htmlspecialchars($new_subj) ?>" required style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13.5px; outline:none;">
                                </div>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:6px;">Text odpovede</label>
                                <input type="hidden" name="message" id="replyHiddenMessage">
                                <div id="reply-editor" style="min-height: 180px;"><?= nl2br($signature) ?></div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                                <div>
                                    <input type="file" name="attachments[]" id="reply-attachments" multiple style="display:none;" onchange="document.getElementById('reply-files-info').textContent = this.files.length > 0 ? (this.files.length + ' súbor(y) vybraté') : 'Žiadne prílohy'">
                                    <label for="reply-attachments" class="btn" style="cursor: pointer;">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">attach_file</span> Priložiť súbory
                                    </label>
                                    <span id="reply-files-info" style="font-size: 12.5px; color: var(--text-secondary); margin-left: 8px;"></span>
                                </div>
                                <button type="submit" class="btn-primary">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">send</span> Odoslať odpoveď
                                </button>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <!-- EMAIL LIST VIEW -->
                    <div class="vueto-card">
                        <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="text" id="tableSearch" placeholder="Filtrovať v správach..." oninput="filterTable(this.value)" style="padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-primary); font-size: 13px; outline: none; width: 260px;">
                                <a href="email.php?folder=<?= urlencode($folder) ?>" class="theme-btn" title="Obnoviť schránku" style="width: 36px; height: 36px;">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">refresh</span>
                                </a>
                            </div>

                            <div>
                                <button type="button" id="bulkDeleteBtn" onclick="document.getElementById('bulkActionForm').submit();" class="btn-danger" style="display:none;">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">delete</span> Zmazať vybraté
                                </button>
                            </div>
                        </div>

                        <?php if (empty($emails)): ?>
                            <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 12px;">mail</span>
                                <p style="font-size: 15px; font-weight: 600;">Žiadne e-maily v tejto zložke.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="bulkActionForm">
                                <input type="hidden" name="action" value="bulk_delete">
                                <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">

                                <div style="overflow-x: auto;">
                                    <table class="email-table" id="emailsTable">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px; text-align: center;">
                                                    <input type="checkbox" id="selectAll" class="custom-chk" onchange="const c = this.checked; document.querySelectorAll('.msg-chk').forEach(el => el.checked = c); toggleBulkBtn();">
                                                </th>
                                                <th style="width: 40px; text-align: center;"></th>
                                                <th style="width: 240px;">OD</th>
                                                <th>PREDMET</th>
                                                <th style="width: 140px; text-align: right;">DÁTUM</th>
                                                <th style="width: 60px; text-align: center;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($emails as $em): ?>
                                                <tr class="email-row <?= !$em['seen'] ? 'unread' : '' ?>" onclick="window.location='email.php?folder=<?= urlencode($folder) ?>&msg=<?= $em['msgno'] ?>'">
                                                    <td style="text-align: center;" onclick="event.stopPropagation();">
                                                        <input type="checkbox" name="msgs[]" value="<?= $em['msgno'] ?>" class="msg-chk custom-chk" onchange="toggleBulkBtn();">
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <span class="material-symbols-outlined" style="font-size: 20px; color: <?= !$em['seen'] ? 'var(--primary-color)' : 'var(--text-secondary)' ?>;">
                                                            <?= !$em['seen'] ? 'mail' : 'drafts' ?>
                                                        </span>
                                                    </td>
                                                    <td style="color: var(--text-primary); font-weight: <?= !$em['seen'] ? '700' : '500' ?>;">
                                                        <?= htmlspecialchars(display_name_only($em['from'])) ?>
                                                    </td>
                                                    <td style="color: var(--text-primary); font-weight: <?= !$em['seen'] ? '700' : '400' ?>;">
                                                        <?= htmlspecialchars($em['subject']) ?>
                                                    </td>
                                                    <td style="text-align: right; color: var(--text-secondary); font-size: 12.5px;">
                                                        <?= htmlspecialchars($em['date']) ?>
                                                    </td>
                                                    <td style="text-align: center;" onclick="event.stopPropagation();">
                                                        <a href="email.php?folder=<?= urlencode($folder) ?>&msg=<?= $em['msgno'] ?>&del=1" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-secondary)'" title="Zmazať">
                                                            <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div><!-- end admin-main -->

    <!-- Modal pre novú správu (Compose) -->
    <div id="composeModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:10000; align-items:center; justify-content:center; backdrop-filter:blur(6px); padding:20px; box-sizing:border-box;">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:16px; width:100%; max-width:650px; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4); position:relative; color:var(--text-primary); padding:26px 28px;">
            <button type="button" onclick="document.getElementById('composeModal').style.display='none'" style="position:absolute; top:20px; right:20px; width:34px; height:34px; border-radius:8px; background:var(--bg-color); border:1px solid var(--border-color); cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--text-secondary);">
                <span class="material-symbols-outlined" style="font-size:20px;">close</span>
            </button>

            <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
                <div style="width:40px; height:40px; border-radius:8px; background:rgba(176, 128, 66, 0.15); color:var(--primary-color); display:flex; align-items:center; justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:22px;">edit</span>
                </div>
                <h3 style="margin:0; font-size:17px; font-weight:800;">Nová e-mailová správa</h3>
            </div>

            <form method="POST" enctype="multipart/form-data" id="composeForm">
                <input type="hidden" name="action" value="send_email">
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:4px;">Komu (E-mail príjemcu)</label>
                    <input type="email" name="to" required placeholder="klient@email.sk" style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13.5px; outline:none;">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:4px;">Predmet</label>
                    <input type="text" name="subject" required placeholder="Predmet správy..." style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13.5px; outline:none;">
                </div>
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:4px;">Správa</label>
                    <input type="hidden" name="message" id="composeHiddenMessage">
                    <div id="compose-editor" style="min-height: 200px;"><?= nl2br($signature) ?></div>
                </div>
                <div style="margin-bottom: 20px;">
                    <input type="file" name="attachments[]" id="compose-attachments" multiple style="display:none;" onchange="document.getElementById('compose-files-info').textContent = this.files.length > 0 ? (this.files.length + ' súbor(y) vybraté') : 'Žiadne prílohy'">
                    <label for="compose-attachments" class="btn" style="cursor: pointer;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">attach_file</span> Priložiť súbory
                    </label>
                    <span id="compose-files-info" style="font-size: 12.5px; color: var(--text-secondary); margin-left: 8px;"></span>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="document.getElementById('composeModal').style.display='none'" class="btn">Zrušiť</button>
                    <button type="submit" class="btn-primary">
                        <span class="material-symbols-outlined" style="font-size:17px;">send</span> Odoslať správu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal pre prepojenie existujúceho e-mailu (Zdieľaný samostatný komponent) -->
    <?php include 'components/email_settings_modal.php'; ?>

    <!-- Toast Notification -->
    <div id="app-toast"></div>

    <script>
        function showToast(msg, type = 'success') {
            const t = document.getElementById('app-toast');
            if (!t) return;
            t.className = type;
            t.innerText = msg;
            t.style.display = 'flex';
            setTimeout(() => { t.style.display = 'none'; }, 3500);
        }

        async function setMarketingSender(value) {
            const fd = new FormData();
            fd.append('action', 'set_marketing_sender');
            fd.append('value', value);
            try {
                const res = await fetch('api/email.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(value === 'own' ? 'Pozdravy a kampane budú chodiť z vašej schránky.' : 'Pozdravy a kampane budú chodiť z Rezervosu.', 'success');
                } else {
                    showToast(data.error || 'Chyba pri ukladaní.', 'error');
                }
            } catch (err) {
                showToast('Chyba pripojenia k serveru.', 'error');
            }
        }

        function openEmailSettingsModal() {
            document.getElementById('emailSettingsModal').style.display = 'flex';
            loadEmailSettings();
        }

        function openComposeModal() {
            document.getElementById('composeModal').style.display = 'flex';
        }

        function toggleBulkBtn() {
            const cnt = document.querySelectorAll('.msg-chk:checked').length;
            const btn = document.getElementById('bulkDeleteBtn');
            if (btn) btn.style.display = cnt > 0 ? 'inline-flex' : 'none';
        }

        function filterTable(q) {
            q = q.toLowerCase();
            document.querySelectorAll('#emailsTable tbody tr').forEach(r => {
                const txt = r.innerText.toLowerCase();
                r.style.display = txt.includes(q) ? '' : 'none';
            });
        }

        function toggleEmailTheme() {
            const iframe = document.getElementById('emailIframe');
            if (iframe && iframe.contentWindow) {
                const wrapper = iframe.contentWindow.document.getElementById('filterWrapper');
                const btn = document.getElementById('emailThemeBtn');
                if (iframe.getAttribute('data-theme') === 'dark') {
                    if (wrapper) wrapper.classList.add('no-filter');
                    iframe.setAttribute('data-theme', 'light');
                    if (btn) btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">dark_mode</span> <span>Tmavý mód správy</span>';
                } else {
                    if (wrapper) wrapper.classList.remove('no-filter');
                    iframe.setAttribute('data-theme', 'dark');
                    if (btn) btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">light_mode</span> <span>Svetlý mód správy</span>';
                }
            }
        }

        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                if (btn) btn.textContent = 'visibility';
            } else {
                input.type = 'password';
                if (btn) btn.textContent = 'visibility_off';
            }
        }

        function autoDetectEmailProvider(email) {
            if (!email || !email.includes('@')) return;
            const parts = email.split('@');
            if (parts.length < 2) return;
            const domain = parts[1].toLowerCase().trim();
            
            const imapSrv = document.getElementById('imap-server');
            const imapPrt = document.getElementById('imap-port');
            const smtpSrv = document.getElementById('smtp-server');
            const smtpPrt = document.getElementById('smtp-port');
            const hintBox = document.getElementById('email-provider-hint');
            
            if (hintBox) {
                hintBox.style.display = 'none';
                hintBox.innerHTML = '';
            }

            if (domain === 'gmail.com' || domain === 'googlemail.com') {
                if (imapSrv) imapSrv.value = 'imap.gmail.com';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.gmail.com';
                if (smtpPrt) smtpPrt.value = '465';
                if (hintBox) {
                    hintBox.innerHTML = '💡 <strong>Tip pre Gmail:</strong> Ak máte 2-stupňové overenie, v Google účte (Zabezpečenie) vytvorte <em>Heslo aplikácie (App Password)</em> a zadajte ho do poľa Heslo.';
                    hintBox.style.display = 'block';
                }
            } else if (domain === 'seznam.cz' || domain === 'email.cz' || domain === 'post.cz') {
                if (imapSrv) imapSrv.value = 'imap.seznam.cz';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.seznam.cz';
                if (smtpPrt) smtpPrt.value = '465';
                if (hintBox) {
                    hintBox.innerHTML = '💡 <strong>Tip pre Seznam.cz:</strong> V nastaveniach schránky na Seznam.cz povoľte IMAP protokol a vygenerujte si <em>Heslo pre aplikácie</em>.';
                    hintBox.style.display = 'block';
                }
            } else if (domain === 'zoznam.sk') {
                if (imapSrv) imapSrv.value = 'imap.zoznam.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.zoznam.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (domain === 'centrum.sk' || domain === 'centrum.cz' || domain === 'atlas.sk' || domain === 'atlas.cz') {
                if (imapSrv) imapSrv.value = 'imap.centrum.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.centrum.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (domain === 'azet.sk') {
                if (imapSrv) imapSrv.value = 'imap.azet.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.azet.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (domain === 'post.sk') {
                if (imapSrv) imapSrv.value = 'imap.post.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.post.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (domain === 'outlook.com' || domain === 'hotmail.com' || domain === 'live.com' || domain === 'office365.com') {
                if (imapSrv) imapSrv.value = 'outlook.office365.com';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.office365.com';
                if (smtpPrt) smtpPrt.value = '587';
            } else if (domain === 'volnekreslo.sk' || domain === 'volnekreslo.cz' || domain === 'usr.sk') {
                if (imapSrv) imapSrv.value = 'mail.usr.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'mail.usr.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (domain.includes('.')) {
                if (imapSrv && (!imapSrv.value || imapSrv.value.startsWith('mail.') || imapSrv.value.startsWith('imap.'))) {
                    imapSrv.value = 'mail.' + domain;
                }
                if (smtpSrv && (!smtpSrv.value || smtpSrv.value.startsWith('mail.') || smtpSrv.value.startsWith('smtp.'))) {
                    smtpSrv.value = 'mail.' + domain;
                }
            }
        }

        // Email settings modal logic
        function applyEmailPreset(type) {
            const imapSrv = document.getElementById('imap-server');
            const imapPrt = document.getElementById('imap-port');
            const smtpSrv = document.getElementById('smtp-server');
            const smtpPrt = document.getElementById('smtp-port');
            const hintBox = document.getElementById('email-provider-hint');
            if (hintBox) { hintBox.style.display = 'none'; hintBox.innerHTML = ''; }

            if (type === 'gmail') {
                if (imapSrv) imapSrv.value = 'imap.gmail.com';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.gmail.com';
                if (smtpPrt) smtpPrt.value = '465';
                if (hintBox) {
                    hintBox.innerHTML = '💡 <strong>Tip pre Gmail:</strong> Ak máte 2-stupňové overenie, v Google účte vygenerujte <em>Heslo aplikácie (App Password)</em> a zadajte ho do poľa Heslo.';
                    hintBox.style.display = 'block';
                }
            } else if (type === 'seznam') {
                if (imapSrv) imapSrv.value = 'imap.seznam.cz';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.seznam.cz';
                if (smtpPrt) smtpPrt.value = '465';
                if (hintBox) {
                    hintBox.innerHTML = '💡 <strong>Tip pre Seznam.cz:</strong> V nastaveniach schránky na Seznam.cz povoľte IMAP protokol a vygenerujte si <em>Heslo pre aplikácie</em>.';
                    hintBox.style.display = 'block';
                }
            } else if (type === 'websupport') {
                if (imapSrv) imapSrv.value = 'imap.websupport.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.websupport.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (type === 'webglobe' || type === 'usr' || type === 'volnekreslo') {
                if (imapSrv) imapSrv.value = 'mail.usr.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'mail.usr.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (type === 'zoznam') {
                if (imapSrv) imapSrv.value = 'imap.zoznam.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.zoznam.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (type === 'centrum') {
                if (imapSrv) imapSrv.value = 'imap.centrum.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.centrum.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (type === 'azet') {
                if (imapSrv) imapSrv.value = 'imap.azet.sk';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.azet.sk';
                if (smtpPrt) smtpPrt.value = '465';
            } else if (type === 'outlook') {
                if (imapSrv) imapSrv.value = 'outlook.office365.com';
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'smtp.office365.com';
                if (smtpPrt) smtpPrt.value = '587';
            } else if (type === 'custom') {
                const userVal = document.getElementById('imap-user') ? document.getElementById('imap-user').value : '';
                let dom = 'vasadomena.sk';
                if (userVal && userVal.includes('@')) dom = userVal.split('@')[1];
                if (imapSrv) imapSrv.value = 'mail.' + dom;
                if (imapPrt) imapPrt.value = '993';
                if (smtpSrv) smtpSrv.value = 'mail.' + dom;
                if (smtpPrt) smtpPrt.value = '465';
            }
            showToast('Predvoľba ' + type.toUpperCase() + ' nastavená.', 'success');
        }

        function toggleSyncSmtp(checked) {
            const box = document.getElementById('smtp-custom-creds');
            if (box) box.style.display = checked ? 'none' : 'grid';
            if (checked) syncEmailCredentials();
        }

        function syncEmailCredentials() {
            const isSync = document.getElementById('sync-smtp-creds');
            if (isSync && isSync.checked) {
                const imapU = document.getElementById('imap-user') ? document.getElementById('imap-user').value : '';
                const imapP = document.getElementById('imap-pass') ? document.getElementById('imap-pass').value : '';
                if (document.getElementById('smtp-user')) document.getElementById('smtp-user').value = imapU;
                if (document.getElementById('smtp-pass')) document.getElementById('smtp-pass').value = imapP;
            }
        }

        function disconnectEmailAccount() {
            document.getElementById('emailSettingsModal').style.display = 'none';
            document.getElementById('disconnect-confirm-modal').style.display = 'flex';
        }

        async function confirmDisconnectEmail() {
            document.getElementById('disconnect-confirm-modal').style.display = 'none';
            try {
                let fd = new FormData();
                fd.append('action', 'disconnect');
                let res = await fetch('api/email.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    showToast('E-mailová schránka bola odpojená.', 'success');
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    showToast(data.error || 'Nastala chyba pri odpájaní.', 'error');
                }
            } catch(e) {
                showToast('Chyba komunikácie so serverom.', 'error');
            }
        }

        async function saveEmailSettings() {
            syncEmailCredentials();
            let fd = new FormData();
            fd.append('action', 'save_settings');
            fd.append('imap_server', document.getElementById('imap-server').value.trim());
            fd.append('imap_port', document.getElementById('imap-port').value.trim());
            fd.append('imap_user', document.getElementById('imap-user').value.trim());
            fd.append('imap_pass', document.getElementById('imap-pass').value);
            fd.append('smtp_server', document.getElementById('smtp-server').value.trim());
            fd.append('smtp_port', document.getElementById('smtp-port').value.trim());
            fd.append('smtp_user', document.getElementById('smtp-user').value.trim());
            fd.append('smtp_pass', document.getElementById('smtp-pass').value);

            let btn = document.getElementById('btn-save-email-settings');
            if (btn) {
                let orig = btn.innerHTML;
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">sync</span> Pripájam a overujem...';
                btn.disabled = true;
                try {
                    let res = await fetch('api/email.php', { method: 'POST', body: fd });
                    let data = await res.json();
                    if (data.success) {
                        showToast('E-mailové nastavenia boli úspešne uložené a schránka pripojená!', 'success');
                        document.getElementById('emailSettingsModal').style.display = 'none';
                        setTimeout(() => { window.location.reload(); }, 1000);
                    } else {
                        showToast(data.error || 'Nastala chyba pri ukladaní a overení schránky.', 'error');
                    }
                } catch(e) {
                    showToast('Chyba pripojenia k serveru.', 'error');
                } finally {
                    btn.innerHTML = orig;
                    btn.disabled = false;
                }
            }
        }

        async function loadEmailSettings() {
            try {
                let fd = new FormData(); fd.append('action', 'get_settings');
                let res = await fetch('api/email.php', { method: 'POST', body: fd });
                let data = await res.json();
                const disBtn = document.getElementById('btn-disconnect-email');
                if (data.success) {
                    if (data.is_configured && data.settings) {
                        if (document.getElementById('imap-server')) document.getElementById('imap-server').value = data.settings.imap_server || '';
                        if (document.getElementById('imap-port')) document.getElementById('imap-port').value = data.settings.imap_port || '993';
                        if (document.getElementById('imap-user')) document.getElementById('imap-user').value = data.settings.imap_user || '';
                        if (document.getElementById('smtp-server')) document.getElementById('smtp-server').value = data.settings.smtp_server || '';
                        if (document.getElementById('smtp-port')) document.getElementById('smtp-port').value = data.settings.smtp_port || '465';
                        if (document.getElementById('smtp-user')) document.getElementById('smtp-user').value = data.settings.smtp_user || '';
                        if (disBtn) disBtn.style.display = 'inline-flex';
                    } else {
                        // Prefill with account default email
                        const uField = document.getElementById('imap-user');
                        if (uField && !uField.value && data.default_email) {
                            uField.value = data.default_email;
                            autoDetectEmailProvider(data.default_email);
                            syncEmailCredentials();
                        }
                        if (disBtn) disBtn.style.display = 'none';
                    }
                }
            } catch(e) {}
        }

        // ============================================================
        // AI ASISTENT
        // ============================================================
        var aiHistory = [];
        var aiOpen = false;

        function toggleAiPanel() {
            var panel = document.getElementById('ai-email-panel');
            var btn   = document.getElementById('ai-tab-btn');
            aiOpen = !aiOpen;
            panel.style.display = aiOpen ? 'block' : 'none';
            btn.classList.toggle('open', aiOpen);
            if (aiOpen) {
                document.getElementById('ai-user-input').focus();
                scrollAiChat();
            }
        }

        function scrollAiChat() {
            var area = document.getElementById('ai-chat-area');
            area.scrollTop = area.scrollHeight;
        }

        function addAiBubble(role, text) {
            var area  = document.getElementById('ai-chat-area');
            var div   = document.createElement('div');
            div.className = 'ai-bubble ' + role;
            div.textContent = text;
            area.appendChild(div);
            scrollAiChat();
            return div;
        }

        function aiClearChat() {
            var area = document.getElementById('ai-chat-area');
            area.innerHTML = '<div class="ai-bubble assistant">Chat bol vyčistený. Ako vám môžem pomôcť?</div>';
            aiHistory = [];
        }

        function showAiInput(prefill) {
            var inp = document.getElementById('ai-user-input');
            inp.value = prefill || '';
            inp.focus();
            inp.selectionStart = inp.selectionEnd = inp.value.length;
        }

        function aiInputKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); aiSendMessage(); }
        }

        async function aiQuickAction(action, content) {
            var typingEl = addAiBubble('typing', 'AI píše...');
            var sendBtn  = document.getElementById('ai-send-btn');
            sendBtn.disabled = true;

            try {
                var payload = { action: action, content: content, context: aiEmailContext, history: [] };
                var res  = await fetch('api/ai-email.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                var data = await res.json();
                typingEl.remove();
                if (data.success) {
                    addAiBubble('assistant', data.result);
                    updateAiCreditsBadge(data.ai_credits_remaining);
                } else {
                    addAiBubble('assistant', 'Chyba: ' + (data.error || 'Neznáma chyba.'));
                    if (data.need_ai_credit) openAiCreditsModal(true);
                }
            } catch(e) {
                typingEl.remove();
                addAiBubble('assistant', 'Chyba pripojenia k AI serveru.');
            } finally {
                sendBtn.disabled = false;
            }
        }

        function updateAiCreditsBadge(remaining) {
            if (remaining === null || remaining === undefined) return;
            var badge = document.getElementById('ai-credits-count');
            if (badge) badge.textContent = remaining;
            if (remaining <= 5) showAppToast('Zostáva vám už len ' + remaining + ' AI kreditov.', 'warning');
        }

        async function aiSendMessage() {
            var inp   = document.getElementById('ai-user-input');
            var text  = inp.value.trim();
            if (!text) return;

            addAiBubble('user', text);
            aiHistory.push({ role: 'user', content: text });
            inp.value = '';

            var typingEl = addAiBubble('typing', 'AI píše...');
            var sendBtn  = document.getElementById('ai-send-btn');
            sendBtn.disabled = true;

            try {
                var payload = { action: 'general_chat', content: text, context: aiEmailContext, history: aiHistory.slice(-14) };
                var res  = await fetch('api/ai-email.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                var data = await res.json();
                typingEl.remove();
                if (data.success) {
                    addAiBubble('assistant', data.result);
                    aiHistory.push({ role: 'assistant', content: data.result });
                    updateAiCreditsBadge(data.ai_credits_remaining);
                } else {
                    addAiBubble('assistant', 'Chyba: ' + (data.error || 'Neznáma chyba.'));
                    if (data.need_ai_credit) openAiCreditsModal(true);
                }
            } catch(e) {
                typingEl.remove();
                addAiBubble('assistant', 'Chyba pripojenia k AI serveru.');
            } finally {
                sendBtn.disabled = false;
            }
        }

        // Initialize Quill editors
        document.addEventListener('DOMContentLoaded', function() {
            var replyEl = document.getElementById('reply-editor');
            if (replyEl) {
                var replyQuill = new Quill('#reply-editor', {
                    theme: 'snow',
                    modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['clean']] }
                });
                var rForm = replyEl.closest('form');
                if (rForm) {
                    rForm.addEventListener('submit', function() {
                        document.getElementById('replyHiddenMessage').value = replyQuill.root.innerHTML;
                    });
                }
            }

            var composeEl = document.getElementById('compose-editor');
            if (composeEl) {
                var composeQuill = new Quill('#compose-editor', {
                    theme: 'snow',
                    modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['clean']] }
                });
                var cForm = document.getElementById('composeForm');
                if (cForm) {
                    cForm.addEventListener('submit', function() {
                        document.getElementById('composeHiddenMessage').value = composeQuill.root.innerHTML;
                    });
                }
            }
        });
    </script>
<!-- Confirm Disconnect Modal -->
<div id="disconnect-confirm-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:10001;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px;max-width:400px;width:90%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.35);text-align:center;">
    <div style="width:50px;height:50px;border-radius:50%;background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 16px auto;">
      <span class="material-symbols-outlined" style="font-size:26px;">link_off</span>
    </div>
    <h3 style="margin:0 0 8px 0;font-size:17px;color:var(--text-primary);">Odpojiť e-mailovú schránku?</h3>
    <p style="margin:0 0 24px 0;font-size:13px;color:var(--text-secondary);line-height:1.5;">Po odpojení nebudete môcť prijímať ani odosielať e-maily cez dashboard.</p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button type="button" onclick="document.getElementById('disconnect-confirm-modal').style.display='none'" class="btn-secondary" style="padding:10px 22px;font-size:13px;">Zrušiť</button>
      <button type="button" onclick="confirmDisconnectEmail()" style="padding:10px 22px;font-size:13px;font-weight:700;border-radius:8px;background:#ef4444;border:none;color:#fff;cursor:pointer;">Odpojiť</button>
    </div>
  </div>
</div>
</body>
</html>
