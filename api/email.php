<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once '../config.php';
ob_end_clean(); // Vyčistí akékoľvek náhodné výstupy z config.php
header('Content-Type: application/json');

// PHPMailer sa načíta len keď treba (send/test) — nie pre disconnect/get_settings
function loadPHPMailer() {
    static $loaded = false;
    if ($loaded) return;
    require_once __DIR__ . '/../phpmailer/src/phpmailer.php';
    require_once __DIR__ . '/../phpmailer/src/smtp.php';
    require_once __DIR__ . '/../phpmailer/src/exception.php';
    $loaded = true;
}

set_exception_handler(function($e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
});

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

function encrypt_data($data, $key, &$iv_b64) {
    $raw_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $iv_b64 = base64_encode($raw_iv);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $raw_iv);
    return base64_encode($encrypted);
}

function decrypt_data($data, $key, $iv_b64) {
    if (empty($data) || empty($iv_b64)) return '';
    $decoded = base64_decode($data);
    if ($decoded === false) return '';
    
    // Support base64 encoded IV (length 24) or legacy raw IV
    if (strlen($iv_b64) === 24 && base64_decode($iv_b64, true) !== false) {
        $raw_iv = base64_decode($iv_b64);
    } else {
        $raw_iv = $iv_b64;
    }
    
    $decrypted = openssl_decrypt($decoded, 'aes-256-cbc', $key, 0, $raw_iv);
    return ($decrypted !== false) ? $decrypted : '';
}

function open_imap_stream($server, $port, $user, $pass, $folder = 'INBOX', $timeout = 5) {
    imap_timeout(IMAP_OPENTIMEOUT, $timeout);
    imap_timeout(IMAP_READTIMEOUT, $timeout);
    
    $boxes = [];
    $port = (int)$port ?: 993;
    
    if ($port === 993) {
        $boxes[] = '{' . $server . ':' . $port . '/imap/ssl/novalidate-cert}' . $folder;
        $boxes[] = '{' . $server . ':' . $port . '/imap/ssl}' . $folder;
    } else {
        $boxes[] = '{' . $server . ':' . $port . '/imap/tls/novalidate-cert}' . $folder;
        $boxes[] = '{' . $server . ':' . $port . '/imap/notls}' . $folder;
        $boxes[] = '{' . $server . ':' . $port . '/imap}' . $folder;
    }
    
    foreach ($boxes as $box) {
        $stream = @imap_open($box, $user, $pass);
        if ($stream) return $stream;
    }
    return false;
}

if ($action === 'get_settings') {
    $stmt = $pdo->prepare("SELECT * FROM user_email_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();

    $stmt_u = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_u->execute([$user_id]);
    $u_row = $stmt_u->fetch();
    $default_email = $u_row['email'] ?? '';

    $pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS marketing_email_sender ENUM('rezervos','own') NOT NULL DEFAULT 'rezervos'");
    $stmt_e = $pdo->prepare("SELECT marketing_email_sender FROM establishments WHERE user_id = ? LIMIT 1");
    $stmt_e->execute([$user_id]);
    $marketing_email_sender = $stmt_e->fetchColumn() ?: 'rezervos';

    if ($settings && !empty($settings['imap_server'])) {
        echo json_encode([
            'success' => true,
            'is_configured' => true,
            'settings' => [
                'imap_server' => $settings['imap_server'],
                'imap_port' => $settings['imap_port'],
                'imap_user' => $settings['imap_user'],
                'smtp_server' => $settings['smtp_server'],
                'smtp_port' => $settings['smtp_port'],
                'smtp_user' => $settings['smtp_user']
            ],
            'default_email' => $default_email,
            'marketing_email_sender' => $marketing_email_sender
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'is_configured' => false,
            'settings' => null,
            'default_email' => $default_email,
            'marketing_email_sender' => $marketing_email_sender
        ]);
    }
    exit;
}

if ($action === 'set_marketing_sender') {
    $value = ($_POST['value'] ?? '') === 'own' ? 'own' : 'rezervos';
    $pdo->exec("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS marketing_email_sender ENUM('rezervos','own') NOT NULL DEFAULT 'rezervos'");
    $stmt = $pdo->prepare("UPDATE establishments SET marketing_email_sender = ? WHERE user_id = ?");
    $stmt->execute([$value, $user_id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'disconnect') {
    $stmt = $pdo->prepare("DELETE FROM user_email_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'message' => 'E-mailová schránka bola úspešne odpojená.']);
    exit;
}

if ($action === 'save_settings') {
    $imap_server = trim($_POST['imap_server'] ?? '');
    $imap_port = (int)($_POST['imap_port'] ?? 993);
    $imap_user = trim($_POST['imap_user'] ?? '');
    $imap_pass = $_POST['imap_pass'] ?? '';
    $smtp_server = trim($_POST['smtp_server'] ?? '');
    $smtp_port = (int)($_POST['smtp_port'] ?? 465);
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    
    if (empty($imap_server) || empty($imap_user) || empty($smtp_server) || empty($smtp_user)) {
        echo json_encode(['success' => false, 'error' => 'Chýbajú povinné údaje (server alebo prihlasovací e-mail).']);
        exit;
    }
    
    // Check if updating or inserting
    $stmt = $pdo->prepare("SELECT * FROM user_email_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();
    
    // If password fields are empty, keep the old ones
    if (empty($imap_pass) && $existing) {
        $enc_imap_pass = $existing['imap_pass'];
        $iv = $existing['encryption_iv'];
        $actual_imap_pass = decrypt_data($enc_imap_pass, EMAIL_ENC_KEY, $iv);
    } else {
        if (empty($imap_pass)) {
            echo json_encode(['success' => false, 'error' => 'Zadajte heslo k e-mailovej schránke.']);
            exit;
        }
        $enc_imap_pass = encrypt_data($imap_pass, EMAIL_ENC_KEY, $iv);
        $actual_imap_pass = $imap_pass;
    }
    
    if (empty($smtp_pass) && $existing) {
        $enc_smtp_pass = $existing['smtp_pass'];
    } else {
        $smtp_pass_to_enc = !empty($smtp_pass) ? $smtp_pass : $actual_imap_pass;
        $enc_smtp_pass = encrypt_data($smtp_pass_to_enc, EMAIL_ENC_KEY, $iv);
    }
    
    // Test IMAP Connection quickly before saving
    if (!empty($actual_imap_pass)) {
        $test_stream = open_imap_stream($imap_server, $imap_port, $imap_user, $actual_imap_pass, 'INBOX', 6);
        if (!$test_stream) {
            $last_err = imap_last_error();
            echo json_encode([
                'success' => false, 
                'error' => 'Nepodarilo sa pripojiť k IMAP serveru. Skontrolujte prihlasovací e-mail, heslo a názov servera. ' . ($last_err ? "($last_err)" : "")
            ]);
            exit;
        }
        @imap_close($test_stream);
    }
    
    if ($existing) {
        $sql = "UPDATE user_email_settings SET imap_server=?, imap_port=?, imap_user=?, imap_pass=?, smtp_server=?, smtp_port=?, smtp_user=?, smtp_pass=?, encryption_iv=? WHERE user_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$imap_server, $imap_port, $imap_user, $enc_imap_pass, $smtp_server, $smtp_port, $smtp_user, $enc_smtp_pass, $iv, $user_id]);
    } else {
        $sql = "INSERT INTO user_email_settings (user_id, imap_server, imap_port, imap_user, imap_pass, smtp_server, smtp_port, smtp_user, smtp_pass, encryption_iv) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $imap_server, $imap_port, $imap_user, $enc_imap_pass, $smtp_server, $smtp_port, $smtp_user, $enc_smtp_pass, $iv]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_inbox') {
    $stmt = $pdo->prepare("SELECT * FROM user_email_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();
    
    if (!$settings) {
        echo json_encode(['success' => false, 'error' => 'E-mailová schránka nie je nastavená.']);
        exit;
    }
    
    $imap_pass = decrypt_data($settings['imap_pass'], EMAIL_ENC_KEY, $settings['encryption_iv']);
    $inbox = open_imap_stream($settings['imap_server'], $settings['imap_port'], $settings['imap_user'], $imap_pass, 'INBOX', 6);
    
    if (!$inbox) {
        echo json_encode(['success' => false, 'error' => 'Chyba pripojenia k IMAP: ' . (imap_last_error() ?: 'Server neodpovedá.')]);
        exit;
    }
    
    $emails = @imap_search($inbox, 'ALL');
    $messages = [];
    
    if ($emails) {
        rsort($emails);
        $emails = array_slice($emails, 0, 15);
        
        foreach ($emails as $email_number) {
            $overview = @imap_fetch_overview($inbox, $email_number, 0);
            
            if ($overview && isset($overview[0])) {
                $subject = isset($overview[0]->subject) ? imap_utf8($overview[0]->subject) : '(Bez predmetu)';
                $from = isset($overview[0]->from) ? imap_utf8($overview[0]->from) : '(Neznámy)';
                $date = isset($overview[0]->date) ? date("d.m.Y H:i", strtotime($overview[0]->date)) : '';
                $seen = (!empty($overview[0]->seen)) ? 1 : 0;
                
                $messages[] = [
                    'id' => $email_number,
                    'subject' => $subject,
                    'from' => $from,
                    'date' => $date,
                    'seen' => $seen
                ];
            }
        }
    }
    
    @imap_close($inbox);
    echo json_encode(['success' => true, 'emails' => $messages]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Neznáma akcia']);
