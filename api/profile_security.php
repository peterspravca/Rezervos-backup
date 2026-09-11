<?php
session_start();
require_once '../config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';
require_once '../includes/phpmailer/exception.php';
require_once '../includes/phpmailer/phpmailer.php';
require_once '../includes/phpmailer/smtp.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Neprihlásený používateľ']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = (int)$_SESSION['user_id'];

if ($action === 'get_security_settings') {
    $stmt = $conn->prepare("SELECT two_factor_enabled, two_factor_questions, security_q1, security_a1, security_q2, security_a2, security_q3, security_a3 FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    
    if ($data) {
        echo json_encode([
            'success' => true,
            'settings' => [
                'two_factor_enabled' => (int)($data['two_factor_enabled'] ?? 0),
                'two_factor_questions' => (int)($data['two_factor_questions'] ?? 0),
                'security_q1' => $data['security_q1'] ?? '',
                'security_a1' => $data['security_a1'] ?? '',
                'security_q2' => $data['security_q2'] ?? '',
                'security_a2' => $data['security_a2'] ?? '',
                'security_q3' => $data['security_q3'] ?? '',
                'security_a3' => $data['security_a3'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Používateľ nebol nájdený']);
    }
    exit;
}

elseif ($action === 'toggle_2fa') {
    $enabled = (int)($_POST['enabled'] ?? 0);
    $stmt = $conn->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $enabled, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Dvojfázové overenie (2FA) bolo ' . ($enabled ? 'zapnuté' : 'vypnuté') . '.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chyba databázy']);
    }
    exit;
}

elseif ($action === 'toggle_2fa_questions') {
    $enabled = (int)($_POST['enabled'] ?? 0);
    
    if ($enabled) {
        // Skontrolujeme, či má používateľ vyplnené všetky 3 otázky
        $stmt = $conn->prepare("SELECT security_q1, security_a1, security_q2, security_a2, security_q3, security_a3 FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if (empty($row['security_q1']) || empty($row['security_a1']) || 
            empty($row['security_q2']) || empty($row['security_a2']) || 
            empty($row['security_q3']) || empty($row['security_a3'])) {
            echo json_encode(['success' => false, 'error' => 'Najskôr musíte vyplniť a uložiť všetky 3 bezpečnostné otázky a odpovede.']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("UPDATE users SET two_factor_questions = ? WHERE id = ?");
    $stmt->bind_param("ii", $enabled, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Trojstupňové overenie otázkami (3FA) bolo ' . ($enabled ? 'zapnuté' : 'vypnuté') . '.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chyba databázy']);
    }
    exit;
}

elseif ($action === 'save_security_questions') {
    $q1 = trim($_POST['q1'] ?? '');
    $a1 = trim($_POST['a1'] ?? '');
    $q2 = trim($_POST['q2'] ?? '');
    $a2 = trim($_POST['a2'] ?? '');
    $q3 = trim($_POST['q3'] ?? '');
    $a3 = trim($_POST['a3'] ?? '');
    $auto_enable = (int)($_POST['auto_enable'] ?? 1);
    
    if (empty($q1) || empty($a1) || empty($q2) || empty($a2) || empty($q3) || empty($a3)) {
        echo json_encode(['success' => false, 'error' => 'Všetky 3 otázky aj odpovede musia byť vyplnené.']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE users SET security_q1 = ?, security_a1 = ?, security_q2 = ?, security_a2 = ?, security_q3 = ?, security_a3 = ?, two_factor_questions = ? WHERE id = ?");
    $stmt->bind_param("ssssssii", $q1, $a1, $q2, $a2, $q3, $a3, $auto_enable, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Bezpečnostné otázky (3FA) boli úspešne uložené a aktivované!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chyba pri ukladaní otázok do databázy.']);
    }
    exit;
}

elseif ($action === 'request_password_change') {
    // Generovanie kódu a odoslanie na e-mail
    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Používateľ neexistuje']);
        exit;
    }
    
    $code = sprintf("%06d", mt_rand(1, 999999));
    $_SESSION['pwd_change_code'] = $code;
    $_SESSION['pwd_change_expires'] = time() + 900;
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.usr.sk';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtp_user, BRAND_NAME . ' Bezpečnosť');
        $mail->addAddress($user['email'], $user['full_name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Overovací kód pre zmenu hesla';
        $mail->Body = "
        <div style='font-family: 'Outfit', sans-serif; background-color: #faf8f5; padding: 20px;'>
            <div style='background-color: #ffffff; padding: 30px; border-radius: 12px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center;'>
                <h2 style='color: #222; margin-bottom: 20px;'>Overovací kód</h2>
                <p>Na zmenu Vášho hesla použite tento 6-miestny kód:</p>
                <div style='background-color: #b08042; color: #ffffff; font-size: 28px; font-weight: bold; letter-spacing: 8px; padding: 15px 25px; border-radius: 8px; display: inline-block; margin-bottom: 20px;'>
                    {$code}
                </div>
                <p style='font-size: 13px; color: #888;'>Kód je platný 15 minút.</p>
            </div>
        </div>";
        $mail->send();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Chyba pri odosielaní e-mailu']);
    }
    exit;
}

elseif ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $newp = $_POST['new_password'] ?? '';
    $code = $_POST['code'] ?? '';
    
    if (empty($_SESSION['pwd_change_code']) || time() > $_SESSION['pwd_change_expires']) {
        echo json_encode(['success' => false, 'error' => 'Platnosť kódu vypršala. Požiadajte o nový.']);
        exit;
    }
    if ($code !== $_SESSION['pwd_change_code']) {
        echo json_encode(['success' => false, 'error' => 'Nesprávny overovací kód.']);
        exit;
    }
    if (strlen($newp) < 6) {
        echo json_encode(['success' => false, 'error' => 'Nové heslo musí mať aspoň 6 znakov.']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    
    if (!$user || !password_verify($current, $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Súčasné heslo nie je správne.']);
        exit;
    }
    
    $hash = password_hash($newp, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $upd->bind_param("si", $hash, $user_id);
    if ($upd->execute()) {
        unset($_SESSION['pwd_change_code'], $_SESSION['pwd_change_expires']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chyba databázy.']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Neznáma akcia']);
