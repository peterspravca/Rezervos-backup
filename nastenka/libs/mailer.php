<?php
/**
 * VUETO CRM Mailer Helper
 * Zjednotená logika pre odosielanie SMTP e-mailov.
 */

function send_crm_notification($to, $subject, $body, $attachments = []) {
    // Konfigurácia mailu z config.php
    $host = MAIL_HOST;
    $user = MAIL_USER;
    $pass = MAIL_PASS;
    $from_name = "VUETO CRM";

    $boundary = md5(uniqid(time()));
    $headers = "From: $from_name <$user>\r\n";
    $headers .= "Reply-To: <$user>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    $payload = "";
    if (empty($attachments)) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $payload = $body . "\r\n";
    } else {
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $payload .= $body . "\r\n\r\n";
        
        foreach ($attachments as $att) {
            $filename = $att['name'];
            $encoded_filename = "=?UTF-8?B?" . base64_encode($filename) . "?=";
            $payload .= "--$boundary\r\n";
            $payload .= "Content-Type: " . ($att['type'] ?? 'application/octet-stream') . "; name=\"$encoded_filename\"\r\n";
            $payload .= "Content-Transfer-Encoding: base64\r\n";
            $payload .= "Content-Disposition: attachment; filename=\"$encoded_filename\"\r\n\r\n";
            $payload .= chunk_split(base64_encode(file_get_contents($att['path']))) . "\r\n";
        }
        $payload .= "--$boundary--\r\n";
    }

    $full_message = $headers . "\r\n" . $payload;

    $sock = @fsockopen("ssl://" . $host, 465, $errno, $errstr, 10);
    if (!$sock) return "Chyba pripojenia k SMTP: $errstr";
    
    fgets($sock, 515);
    fwrite($sock, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    stream_set_timeout($sock, 2);
    while($line = fgets($sock, 515)) { if(substr($line, 3, 1) == ' ') break; }

    fwrite($sock, "AUTH LOGIN\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($user) . "\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($pass) . "\r\n");
    $auth = fgets($sock, 515);
    if (substr($auth, 0, 3) != '235') {
        fclose($sock);
        return "Chyba prihlásenia k SMTP.";
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
    if ($success) {
        // Zložitý krok: Uložiť kópiu do odoslanej pošty (IMAP)
        save_mail_to_sent_folder($full_message);
    }

    return $success ? true : "Chyba odosielania: $res";
}

/**
 * Univerzálna funkcia pre odosielanie SMTP e-mailov (používaná v email.php)
 */
function send_smtp_email($to, $subject, $body, $host, $user, $pass, $attachments = []) {
    $from_name = "VUETO CRM";

    $boundary = md5(uniqid(time()));
    $headers = "From: $from_name <$user>\r\n";
    $headers .= "Reply-To: <$user>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    $payload = "";
    if (empty($attachments)) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $lang_hint = "<div style='display:none; color:transparent; opacity:0; font-size:1px;'>Táto správa bola odoslaná zo systému VUETO CRM v slovenskom jazyku.</div>";
        $payload = "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'><meta http-equiv='Content-Language' content='sk'><meta name='language' content='Slovak'></head><body lang='sk'>" . $body . $lang_hint . "</body></html>\r\n";
    } else {
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $lang_hint = "<div style='display:none; color:transparent; opacity:0; font-size:1px;'>Táto správa bola odoslaná zo systému VUETO CRM v slovenskom jazyku.</div>";
        $payload .= "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'><meta http-equiv='Content-Language' content='sk'><meta name='language' content='Slovak'></head><body lang='sk'>" . $body . $lang_hint . "</body></html>\r\n\r\n";
        
        foreach ($attachments as $att) {
            $filename = $att['name'];
            $encoded_filename = "=?UTF-8?B?" . base64_encode($filename) . "?=";
            $payload .= "--$boundary\r\n";
            $payload .= "Content-Type: " . ($att['type'] ?? 'application/octet-stream') . "; name=\"$encoded_filename\"\r\n";
            $payload .= "Content-Transfer-Encoding: base64\r\n";
            $payload .= "Content-Disposition: attachment; filename=\"$encoded_filename\"\r\n\r\n";
            
            // Handle both temp uploads (tmp_name) and existing files (path)
            $file_content = "";
            if (isset($att['tmp_name'])) {
                $file_content = file_get_contents($att['tmp_name']);
            } elseif (isset($att['path'])) {
                $file_content = file_get_contents($att['path']);
            }
            
            $payload .= chunk_split(base64_encode($file_content)) . "\r\n";
        }
        $payload .= "--$boundary--\r\n";
    }

    $full_message = $headers . "\r\n" . $payload;

    $sock = @fsockopen("ssl://" . $host, 465, $errno, $errstr, 10);
    if (!$sock) return "Chyba pripojenia k SMTP: $errstr";
    
    fgets($sock, 515);
    fwrite($sock, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    stream_set_timeout($sock, 2);
    while($line = fgets($sock, 515)) { if(substr($line, 3, 1) == ' ') break; }

    fwrite($sock, "AUTH LOGIN\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($user) . "\r\n");
    fgets($sock, 515);
    fwrite($sock, base64_encode($pass) . "\r\n");
    $auth = fgets($sock, 515);
    if (substr($auth, 0, 3) != '235') {
        fclose($sock);
        return "Chyba prihlásenia k SMTP.";
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
    if ($success) {
        save_mail_to_sent_folder($full_message);
    }

    return $success ? true : "Chyba odosielania: $res";
}

/**
 * Pomocná funkcia pre balenie HTML obsahu do profesionálnej šablóny
 */
function wrap_email_content($title, $content, $portal_url = '') {
    $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . ($_SERVER['HTTP_HOST'] ?? 'vueto.sk');
    $logo = "$domain/nastenka/logo-white.png"; // Správna cesta k logu, použité dark logo pre tmavý e-mailový header
    $year = date('Y');
    
    $button = "";
    if ($portal_url) {
        $button = "
        <div style='margin-top: 30px; text-align: center;'>
            <a href='$portal_url' style='background-color: #2563eb; color: #ffffff !important; padding: 12px 25px; text-decoration: none !important; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block; border: 1px solid #2563eb;'>
                <span style='color: #ffffff !important; text-decoration: none !important;'>Sledovať projekt na portáli</span>
            </a>
        </div>";
    }

    return "
    <!DOCTYPE html>
    <html lang='sk'>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Language' content='sk'>
        <meta name='language' content='Slovak'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333333; margin: 0; padding: 0; background-color: #f4f7fa; }
            .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
            .header { background-color: #111827; padding: 40px 20px; text-align: center; }
            .body { padding: 40px; }
            .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
            h1 { color: #111827; font-size: 24px; margin-bottom: 20px; text-align: center; }
            p { margin-bottom: 15px; }
            .status-box { background-color: #f0f7ff; border-left: 4px solid #2563eb; padding: 15px 20px; margin: 25px 0; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='$logo' alt='VUETO' style='max-height: 40px;'>
            </div>
            <div class='body'>
                <h1>$title</h1>
                $content
                $button
            </div>
            <div class='footer'>
                &copy; $year VUETO. Všetky práva vyhradené.<br>
                Tento e-mail bol odoslaný automaticky zo systému CRM.
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Uloží kópiu odoslaného e-mailu do zložky 'Sent' cez IMAP
 */
function save_mail_to_sent_folder($full_message) {
    if (!function_exists('imap_open')) return;
    
    $host = MAIL_HOST;
    $user = MAIL_USER;
    $pass = MAIL_PASS;
    
    // Rôzne názvy zložiek pre odoslanú poštu podľa providera
    $common_folders = ['Sent', 'INBOX.Sent', 'Odoslané', 'INBOX.Odoslané', 'Sent Messages'];
    
    foreach ($common_folders as $folder_name) {
        $imap_path = "{" . $host . ":993/imap/ssl}" . $folder_name;
        $conn = @imap_open($imap_path, $user, $pass, OP_HALFOPEN);
        
        if ($conn) {
            @imap_append($conn, $imap_path, $full_message, "\\Seen");
            imap_close($conn);
            return true; // Podarilo sa uložiť
        }
    }
    
    return false;
}
