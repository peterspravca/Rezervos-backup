<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    die('Prístup odopretý.');
}

if (!isset($_GET['msg']) || !isset($_GET['part'])) {
    die('Chýbajúce parametre.');
}

$user_id = (int)$_SESSION['user_id'];
$msg_num = (int)$_GET['msg'];
$part_num = $_GET['part'];
$folder = $_GET['folder'] ?? 'INBOX';

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

$stmt = $pdo->prepare("SELECT * FROM user_email_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch();

if ($settings && !empty($settings['imap_server'])) {
    $mail_host = $settings['imap_server'];
    $mail_port = (int)$settings['imap_port'] ?: 993;
    $mail_user = $settings['imap_user'];
    $mail_pass = decrypt_data($settings['imap_pass'], EMAIL_ENC_KEY, $settings['encryption_iv']);
} else {
    $mail_host = 'mail.usr.sk';
    $mail_port = 993;
    $mail_user = $smtp_user ?? 'info@volnekreslo.sk';
    $mail_pass = $smtp_pass ?? '2dDUEzsWbm6w';
}

imap_timeout(IMAP_OPENTIMEOUT, 15);
$imap_box = "{" . $mail_host . ":" . $mail_port . "/imap/ssl/novalidate-cert}" . $folder;
$imap_stream = @imap_open($imap_box, $mail_user, $mail_pass);

if (!$imap_stream) {
    die('Nepodarilo sa pripojiť k IMAP serveru.');
}

$structure = imap_fetchstructure($imap_stream, $msg_num);

function find_part($structure, $part_num) {
    $parts = explode('.', $part_num);
    $current = $structure;
    
    foreach ($parts as $p) {
        if (isset($current->parts[$p - 1])) {
            $current = $current->parts[$p - 1];
        } else {
            return null;
        }
    }
    return $current;
}

$part_structure = find_part($structure, $part_num);

if (!$part_structure) {
    die('Príloha nebola nájdená.');
}

function get_mime_type_local($structure) {
    $primary_types = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if (isset($structure->subtype)) {
        return $primary_types[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "application/octet-stream";
}

$mime_type = get_mime_type_local($part_structure);

function decode_imap_text_local($str) {
    if (!$str) return '';
    $result = '';
    $decode = imap_mime_header_decode($str);
    foreach ($decode as $obj) {
        $result .= $obj->text;
    }
    return $result;
}

$filename = 'priloha';
if ($part_structure->ifdparameters) {
    foreach ($part_structure->dparameters as $object) {
        if (strtolower($object->attribute) == 'filename') {
            $filename = decode_imap_text_local($object->value);
        }
    }
}
if ($filename == 'priloha' && $part_structure->ifparameters) {
    foreach ($part_structure->parameters as $object) {
        if (strtolower($object->attribute) == 'name') {
            $filename = decode_imap_text_local($object->value);
        }
    }
}

$attachment_data = imap_fetchbody($imap_stream, $msg_num, $part_num);

if ($part_structure->encoding == 3) {
    $attachment_data = imap_base64($attachment_data);
} elseif ($part_structure->encoding == 4) {
    $attachment_data = quoted_printable_decode($attachment_data);
}

imap_close($imap_stream);

header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . strlen($attachment_data));
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

echo $attachment_data;
exit;
