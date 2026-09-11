<?php
require_once __DIR__ . '/auth.php';
require_login();
session_write_close();

if (!isset($_GET['msg']) || !isset($_GET['part'])) {
    die('Missing parameters.');
}

$msg_num = (int)$_GET['msg'];
$part_num = $_GET['part'];
$folder = $_GET['folder'] ?? 'INBOX';

require_once __DIR__ . '/libs/mailer.php';

// E-mail konfigurácia z config.php
$mail_host = MAIL_HOST;
$mail_user = MAIL_USER;
$mail_pass = MAIL_PASS;

imap_timeout(IMAP_OPENTIMEOUT, 15);
$imap_box = "{" . $mail_host . ":993/imap/ssl}" . $folder;
$imap_stream = @imap_open($imap_box, $mail_user, $mail_pass);

if (!$imap_stream) {
    die('Could not connect to IMAP.');
}

$structure = imap_fetchstructure($imap_stream, $msg_num);

// Find the specific part structure
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
    die('Part not found.');
}

function get_mime_type_local($structure) {
    $primary_types = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if (isset($structure->subtype)) {
        return $primary_types[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "application/octet-stream";
}

$mime_type = get_mime_type_local($part_structure);
$filename = "attachment";

if ($part_structure->ifdparameters) {
    foreach ($part_structure->dparameters as $object) {
        if (strtolower($object->attribute) == 'filename') {
            $filename = imap_mime_header_decode($object->value)[0]->text;
        }
    }
}

if ($filename == "attachment" && $part_structure->ifparameters) {
    foreach ($part_structure->parameters as $object) {
        if (strtolower($object->attribute) == 'name') {
            $filename = imap_mime_header_decode($object->value)[0]->text;
        }
    }
}

$body = imap_fetchbody($imap_stream, $msg_num, $part_num);

if ($part_structure->encoding == 3) {
    $body = imap_base64($body);
} elseif ($part_structure->encoding == 4) {
    $body = quoted_printable_decode($body);
}

$size = strlen($body);
$start = 0;
$end = $size - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if ($size_unit == 'bytes') {
        list($range, $extra_ranges) = explode(',', $range_orig, 2);
        list($start_str, $end_str) = explode('-', $range, 2);
        
        $start = (int)$start_str;
        $end = ($end_str === '') ? $size - 1 : (int)$end_str;
        
        if ($start > $end || $start > $size - 1 || $end >= $size) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes */$size");
            exit;
        }
        
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$size");
        $body = substr($body, $start, $end - $start + 1);
        header('Content-Length: ' . strlen($body));
    }
} else {
    header('Content-Length: ' . $size);
}

header('Accept-Ranges: bytes');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . $filename . '"');

echo $body;

imap_close($imap_stream);
