<?php
session_start();
require_once '../config.php';
require_once '../includes/phpmailer/exception.php';
require_once '../includes/phpmailer/phpmailer.php';
require_once '../includes/phpmailer/smtp.php';
require_once '../includes/profanity_filter.php';
require_once '../includes/test_email_override.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia']);
    exit;
}

// Inicializácia pripojenia (keďže v config.php bolo zakomentované)
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Vyplňte všetky polia']);
        exit;
    }

    $ts_token = $_POST['cf-turnstile-response'] ?? '';
    if (!verify_turnstile($ts_token, TURNSTILE_SECRET_KEY)) {
        echo json_encode(['success' => false, 'message' => 'Bezpečnostné overenie zlyhalo. Obnovte stránku a skúste znova.']);
        exit;
    }

    if (has_profanity($name)) {
        echo json_encode(['success' => false, 'message' => 'Vaše meno obsahuje nepovolené výrazy.']);
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Tento e-mail je už zaregistrovaný.']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Generate 6-digit code
    $verification_code = sprintf("%06d", mt_rand(1, 999999));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate public_id (slug) from name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name))));
    $slug = trim($slug, '-');
    if (empty($slug)) $slug = 'user';
    $public_id = $slug . '-' . substr(md5(uniqid()), 0, 6);
    
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, full_name, verification_code, is_verified, public_id, referral_code) VALUES (?, ?, ?, ?, 0, ?, ?)");
    $referral = trim($_POST['ref_code'] ?? '');
    $stmt->bind_param("ssssss", $email, $password_hash, $name, $verification_code, $public_id, $referral);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $_SESSION['temp_user_id'] = $user_id; // For verification step
        
        // Send email
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

            $mail->setFrom($smtp_user, BRAND_NAME);
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Overovací kód - ' . BRAND_NAME;
            $mail->Body    = "
                <div style='font-family: 'Outfit', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                    <h2 style='color: #b08042; text-align: center;'>Vitaj v " . htmlspecialchars(BRAND_NAME) . "!</h2>
                    <p>Ahoj <b>{$name}</b>,</p>
                    <p>Ďakujeme za tvoju registráciu. Pre dokončenie procesu, prosím, zadaj nasledujúci 6-miestny overovací kód do okna na stránke:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; background: #f9f9f9; padding: 15px 30px; border-radius: 8px; border: 1px dashed #b08042; color: #333;'>{$verification_code}</span>
                    </div>
                    <p>Ak si o tento kód nežiadal, môžeš tento e-mail ignorovať.</p>
                    <p>S pozdravom,<br>Tím " . htmlspecialchars(BRAND_NAME) . "</p>
                </div>
            ";

            apply_test_email_override($mail, $email);
            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Na váš e-mail bol zaslaný 6-miestny kód.', 'require_verification' => true]);
        } catch (Exception $e) {
            // Delete user if email fails to send? Or keep and they can request resend. Let's keep for now.
            echo json_encode(['success' => false, 'message' => 'E-mail sa nepodarilo odoslať: ' . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Chyba databázy: ' . $stmt->error]);
    }
    $stmt->close();
}


elseif ($action === 'forgot_password') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Zadajte e-mailovú adresu.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($uid, $ufull);
        $stmt->fetch();
        
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Ensure reset_token column exists or just mock it for now
        // Let's just mock the email sending for now, or use verification_code
        $update = $conn->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
        $update->bind_param("si", $token, $uid);
        $update->execute();
        $update->close();
        
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

            $mail->setFrom($smtp_user, BRAND_NAME);
            $mail->addAddress($email, $ufull);

            $mail->isHTML(true);
            $mail->Subject = 'Obnovenie hesla - ' . BRAND_NAME;
            // We don't have a reset page yet, just send a dummy link or instructions
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
            $mail->Body    = "
                <div style='font-family: 'Outfit', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                    <h2 style='color: #b08042; text-align: center;'>Obnovenie hesla</h2>
                    <p>Ahoj <b>{$ufull}</b>,</p>
                    <p>Dostali sme žiadosť o obnovenie hesla k vášmu účtu.</p>
                    <p>Pre vytvorenie nového hesla kliknite na nasledujúci odkaz (platí 1 hodinu):</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$reset_link}' style='background: #b08042; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Obnoviť heslo</a>
                    </div>
                    <p>Ak ste o zmenu hesla nežiadali, tento e-mail môžete ignorovať.</p>
                    <p>S pozdravom,<br>Tím " . htmlspecialchars(BRAND_NAME) . "</p>
                </div>
            ";

            apply_test_email_override($mail, $email);
            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Odkaz na obnovenie hesla bol odoslaný.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Nepodarilo sa odoslať e-mail.']);
        }
    } else {
        // Obfuscate user existence
        echo json_encode(['success' => true, 'message' => 'Ak účet s týmto e-mailom existuje, odoslali sme naň odkaz.']);
    }
    $stmt->close();
}

elseif ($action === 'verify_code') {
    $code = trim($_POST['code'] ?? '');
    $user_id = $_SESSION['temp_user_id'] ?? 0;

    if (!$user_id || empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Neplatná požiadavka.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND verification_code = ?");
    $stmt->bind_param("is", $user_id, $code);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Update user to verified
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
        $update->bind_param("i", $user_id);
        $update->execute();
        $update->close();
        
        // Log them in fully so they can select role
        $_SESSION['user_id'] = $user_id;
        
        echo json_encode(['success' => true, 'message' => 'E-mail úspešne overený.', 'require_role' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nesprávny kód.']);
    }
    $stmt->close();
}

elseif ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Vyplňte všetky polia']);
        exit;
    }

    $ts_token = $_POST['cf-turnstile-response'] ?? '';
    if (!verify_turnstile($ts_token, TURNSTILE_SECRET_KEY)) {
        echo json_encode(['success' => false, 'message' => 'Bezpečnostné overenie zlyhalo. Obnovte stránku a skúste znova.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, password_hash, role, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            
            if ($row['is_verified'] == 0) {
                $_SESSION['temp_user_id'] = $row['id'];
                echo json_encode(['success' => true, 'require_verification' => true, 'message' => 'Pre dokončenie zadajte 6-miestny kód, ktorý sme vám zaslali.']);
                exit;
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_role'] = $row['role'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $update_token = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $update_token->bind_param("si", $token, $row['id']);
                $update_token->execute();
                $update_token->close();
                setcookie('remember_me', $row['id'] . ':' . $token, time() + (30 * 24 * 60 * 60), "/");
            }

            if ($row['role'] === 'admin') {
                echo json_encode(['success' => true, 'redirect' => 'admin.php', 'message' => 'Prihlásenie úspešné.']);
            } elseif ($row['role'] === 'business') {
                echo json_encode(['success' => true, 'redirect' => 'dashboard.php', 'message' => 'Prihlásenie úspešné.']);
            } else {
                echo json_encode(['success' => true, 'redirect' => 'prevadzky.php', 'message' => 'Prihlásenie úspešné.']);
            }
            $stmt->close();
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Nesprávne heslo.']);
            $stmt->close();
            exit;
        }
    }
    $stmt->close();

    // Kontrola prihlásenia zamestnanca
    // Self-migrácia (pre istotu, ak ešte nikto nenavštívil dashboard-tym.php): zamestnanecké oprávnenia
    try { $conn->query("ALTER TABLE employees ADD COLUMN can_view_revenue TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $conn->query("ALTER TABLE employees ADD COLUMN can_view_crm TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $conn->query("ALTER TABLE employees ADD COLUMN can_edit_settings TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    $stmt_emp = $conn->prepare("SELECT id, business_id, name, title, password_hash, is_active, can_view_revenue, can_view_crm, can_edit_settings FROM employees WHERE email = ? AND is_active = 1");
    $stmt_emp->bind_param("s", $email);
    $stmt_emp->execute();
    $res_emp = $stmt_emp->get_result();

    if ($emp = $res_emp->fetch_assoc()) {
        if (!empty($emp['password_hash']) && password_verify($password, $emp['password_hash'])) {
            $_SESSION['user_id'] = (int)$emp['business_id'];
            $_SESSION['employee_id'] = (int)$emp['id'];
            $_SESSION['employee_name'] = $emp['name'];
            $_SESSION['employee_title'] = $emp['title'];
            $_SESSION['is_employee'] = 1;
            $_SESSION['emp_can_view_revenue'] = (int)($emp['can_view_revenue'] ?? 0);
            $_SESSION['emp_can_view_crm'] = (int)($emp['can_view_crm'] ?? 0);
            $_SESSION['emp_can_edit_settings'] = (int)($emp['can_edit_settings'] ?? 0);
            $_SESSION['user_role'] = 'business';

            echo json_encode(['success' => true, 'redirect' => 'dashboard.php', 'message' => 'Prihlásenie zamestnanca úspešné.']);
            $stmt_emp->close();
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Nesprávne heslo.']);
            $stmt_emp->close();
            exit;
        }
    }
    $stmt_emp->close();

    echo json_encode(['success' => false, 'message' => 'Používateľ s týmto e-mailom neexistuje.']);
    exit;
}

elseif ($action === 'set_role') {
    $role = $_POST['role'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    if (!$user_id || !in_array($role, ['customer', 'business'])) {
        echo json_encode(['success' => false, 'message' => 'Neplatná rola.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $user_id);
    if ($stmt->execute()) {
        $_SESSION['user_role'] = $role;
        $redir = ($role === 'business') ? 'dashboard.php' : 'prevadzky.php';
        echo json_encode(['success' => true, 'redirect' => $redir]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Chyba databázy.']);
    }
    $stmt->close();
}

$conn->close();
?>
