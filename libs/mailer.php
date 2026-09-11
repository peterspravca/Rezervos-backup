<?php
/**
 * Mailer Helper
 * Logika pre odosielanie SMTP e-mailov a IMAP ukladanie do Odoslaných.
 */
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';

function send_smtp_email($to, $subject, $body, $host, $user, $pass, $attachments = [], $port = 465) {
    $from_name = BRAND_NAME;

    $boundary = md5(uniqid(time()));
    $headers = "From: $from_name <$user>\r\n";
    $headers .= "Reply-To: <$user>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    $payload = "";
    if (empty($attachments)) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $payload = "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'></head><body>" . $body . "</body></html>\r\n";
    } else {
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $payload .= "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'></head><body>" . $body . "</body></html>\r\n\r\n";
        
        foreach ($attachments as $att) {
            $filename = $att['name'];
            $encoded_filename = "=?UTF-8?B?" . base64_encode($filename) . "?=";
            $payload .= "--$boundary\r\n";
            $payload .= "Content-Type: " . ($att['type'] ?? 'application/octet-stream') . "; name=\"$encoded_filename\"\r\n";
            $payload .= "Content-Transfer-Encoding: base64\r\n";
            $payload .= "Content-Disposition: attachment; filename=\"$encoded_filename\"\r\n\r\n";
            
            $file_content = !empty($att['tmp_name']) ? file_get_contents($att['tmp_name']) : (!empty($att['path']) ? file_get_contents($att['path']) : '');
            $payload .= chunk_split(base64_encode($file_content)) . "\r\n";
        }
        $payload .= "--$boundary--\r\n";
    }

    $full_message = $headers . "\r\n" . $payload;

    $sock = @fsockopen("ssl://" . $host, $port, $errno, $errstr, 12);
    if (!$sock) return "Chyba pripojenia k SMTP: $errstr ($errno)";
    
    fgets($sock, 515);
    $helo_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'volnekreslo.sk';
    fwrite($sock, "EHLO " . $helo_host . "\r\n");
    stream_set_timeout($sock, 3);
    while($line = fgets($sock, 515)) { if(substr($line, 3, 1) == ' ') break; }

    fwrite($sock, "AUTH LOGIN\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($user) . "\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($pass) . "\r\n");
    $auth = fgets($sock, 515);
    if (substr($auth, 0, 3) != '235') {
        fclose($sock);
        return "Chyba prihlásenia k SMTP serveru: " . trim($auth);
    }

    fwrite($sock, "MAIL FROM:<$user>\r\n");
    fgets($sock, 515);
    fwrite($sock, "RCPT TO:<$to>\r\n");
    fgets($sock, 515);
    fwrite($sock, "DATA\r\n");
    fgets($sock, 515);

    $chunks = str_split($full_message . ".\r\n", 4096);
    foreach ($chunks as $c) fwrite($sock, $c);
    
    $res = fgets($sock, 515);
    fwrite($sock, "QUIT\r\n");
    fclose($sock);
    
    $success = (substr($res, 0, 3) == '250');
    return $success ? true : "Chyba odosielania: $res";
}
