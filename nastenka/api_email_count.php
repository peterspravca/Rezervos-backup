<?php
require_once __DIR__ . '/auth.php';
require_login();
session_write_close();

if (!function_exists('imap_open')) {
    echo json_encode(['count' => 0]);
    exit;
}

$mail_host = MAIL_HOST;
$mail_user = MAIL_USER;
$mail_pass = MAIL_PASS;

$imap = @imap_open("{" . $mail_host . ":993/imap/ssl}INBOX", $mail_user, $mail_pass, OP_READONLY);
$count = 0;
if ($imap) {
    $unseen = imap_search($imap, 'UNSEEN');
    if ($unseen !== false) {
        $count = count($unseen);
    }
    imap_close($imap);
}

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
