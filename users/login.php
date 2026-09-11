<?php
// users/login.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$prefix = '../';
require_once $prefix . 'config.php';
require_once $prefix . 'translator_helper.php';
if (!defined('BRAND_NAME')) require_once $prefix . 'includes/branding.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = t('Registrácia úspešná! Teraz sa môžete prihlásiť.');
} elseif (isset($_GET['verify_required'])) {
    $success = t('Účet bol úspešne vytvorený! Na váš e-mail sme odoslali overovací odkaz. Skontrolujte prosím vašu schránku (aj priečinok Spam) a overte svoj účet pred prihlásením.');
} elseif (isset($_GET['verified'])) {
    $success = t('Váš e-mail bol úspešne overený! Teraz sa môžete prihlásiť.');
} elseif (isset($_GET['reset_success'])) {
    $success = t('Heslo bolo úspešne zmenené. Teraz sa môžete prihlásiť.');
}

if (isset($_GET['invalid_token'])) {
    $error = t('Neplatný, použitý alebo expirovaný overovací odkaz.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $honeypot = $_POST['hp_username'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if ($honeypot !== '') {
        $error = t('Bezpečnostná ochrana zlyhala. Skúste to prosím znova (Anti-Spam).');
    } else {
         // ─── Turnstile Overenie (Hybridná ochrana) ───
        $ts_token = $_POST['cf-turnstile-response'] ?? '';
        $turnstile_valid = true;
        if (!empty($ts_token)) {
            $turnstile_valid = verify_turnstile($ts_token, TURNSTILE_SECRET_KEY);
        }
        
        if (!$turnstile_valid) {
            $error = t('Bezpečnostné overenie zlyhalo. Skúste to znova. (Anti-Spam)');
        } elseif ($email && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (isset($user['is_verified']) && !$user['is_verified']) {
                $error = t('Pred prihlásením musíte overiť svoju e-mailovú adresu. Skontrolujte prosím svoju doručenú poštu (aj priečinok Spam).');
            } else {
                // Skontrolujeme, či má zapnuté 2FA (E-mail) alebo 3FA (Otázky)
                if (!empty($user['two_factor_enabled'])) {
                    $_SESSION['temp_login_user_id'] = $user['id'];
                    $_SESSION['temp_login_email'] = $user['email'];
                    $_SESSION['temp_login_remember'] = $remember;
                    
                    $code = sprintf("%06d", mt_rand(1, 999999));
                    $_SESSION['temp_login_code'] = $code;
                    $_SESSION['temp_login_code_expires'] = time() + 900; // platnosť 15 minút
                    
                    // Pre účely dema alebo lokálneho testovania zalogujeme kód
                    error_log("2FA Kód pre " . $user['email'] . " je: " . $code);
                    
                    // Skutočné odoslanie pomocou PHPMailer
                    require_once $prefix . 'includes/phpmailer/exception.php';
                    require_once $prefix . 'includes/phpmailer/phpmailer.php';
                    require_once $prefix . 'includes/phpmailer/smtp.php';

                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'mail.usr.sk';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $smtp_user;
                        $mail->Password   = $smtp_pass;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;

                        $mail->CharSet = 'UTF-8';
                        $mail->setFrom($smtp_user, BRAND_NAME . ' ' . t('Bezpečnosť'));
                        $mail->addAddress($user['email']);

                        $mail->isHTML(true);
                        $mail->Subject = t('Váš bezpečnostný kód pre prihlásenie');

                        $mail->Body = "
                        <div style='font-family: 'Outfit', sans-serif; color: #333; max-width: 600px; margin: 0 auto; background-color: #faf8f5; border-radius: 16px; padding: 20px;'>
                            <!-- Hlavička (Čierna) -->
                            <div style='background-color: #111111; padding: 40px 20px; border-radius: 12px; text-align: center; margin-bottom: 20px;'>
                                <div style='color: #b08042; font-size: 16px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase;'>
                                    " . htmlspecialchars(BRAND_NAME) . "
                                </div>
                                <div style='color: #ffffff; font-size: 13px; margin-top: 5px; opacity: 0.8;'>
                                    " . t('Bezpečnostné overenie') . "
                                </div>
                            </div>

                            <!-- Obsah (Biely) -->
                            <div style='background-color: #ffffff; padding: 40px 30px; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03);'>
                                <h2 style='color: #222; margin-top: 0; font-size: 22px; margin-bottom: 15px; font-weight: 800;'>" . t('Váš bezpečnostný kód pre prihlásenie') . "</h2>
                                <p style='font-size: 15px; color: #555; margin-bottom: 30px;'>" . t('Váš 6-miestny overovací kód je:') . "</p>
                                
                                <div style='background-color: #b08042; color: #ffffff; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 30px; border-radius: 12px; display: inline-block; margin-bottom: 30px; box-shadow: 0 6px 15px rgba(176, 128, 66, 0.3);'>
                                    " . $code . "
                                </div>
                                
                                <p style='font-size: 13px; color: #888; margin: 0;'>" . t('Kód platí 15 minút.') . "</p>
                            </div>

                            <!-- Pätička -->
                            <div style='text-align: center; margin-top: 30px; padding: 0 20px;'>
                                <p style='font-size: 12px; color: #999; margin: 0; line-height: 1.5;'>
                                    " . t('Tento e-mail bol automaticky vygenerovaný systémom') . " " . htmlspecialchars(BRAND_NAME) . ".<br>
                                    &copy; " . date('Y') . " " . htmlspecialchars(BRAND_NAME) . ". " . t('Všetky práva vyhradené.') . "
                                </p>
                            </div>
                        </div>";
                        
                        $mail->AltBody = t('Váš 6-miestny overovací kód je: ') . $code . "\n\n" . t('Kód platí 15 minút.');

                        $mail->send();
                    } catch (\Exception $e) {
                        try {
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->send();
                        } catch (\Exception $e2) {
                            error_log("2FA e-mail zlyhal: " . $mail->ErrorInfo);
                        }
                    }
                    
                    $_SESSION['auth_view'] = 'verify_2fa';
                    $redirect_url = '../index.php';
                    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'login.php') === false) {
                        $redirect_url = $_SERVER['HTTP_REFERER'];
                    }
                    header('Location: ' . $redirect_url);
                    exit;
                }
                elseif (!empty($user['two_factor_questions'])) {
                    $_SESSION['temp_login_user_id'] = $user['id'];
                    $_SESSION['temp_login_email'] = $user['email'];
                    $_SESSION['temp_login_remember'] = $remember;
                    
                    $available_questions = [];
                    for ($i = 1; $i <= 3; $i++) {
                        if (!empty($user['security_q' . $i]) && !empty($user['security_a' . $i])) {
                            $available_questions[] = $i;
                        }
                    }
                    $qIdx = !empty($available_questions) ? $available_questions[array_rand($available_questions)] : 1;
                    $_SESSION['temp_login_question_idx'] = $qIdx;
                    $_SESSION['temp_login_question_text'] = $user['security_q' . $qIdx] ?? 'Bezpečnostná otázka';
                    $_SESSION['auth_view'] = 'verify_3fa';
                    
                    $redirect_url = '../index.php';
                    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'login.php') === false) {
                        $redirect_url = $_SERVER['HTTP_REFERER'];
                    }
                    header('Location: ' . $redirect_url);
                    exit;
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = (isset($user['role']) && $user['role'] === 'admin') ? 1 : 0;
$_SESSION['user_role'] = $user['role'] ?? 'customer';
                $_SESSION['onboarding_completed'] = isset($user['onboarding_completed']) ? (int)$user['onboarding_completed'] : 1;
                
                if ($remember) {
                    setcookie('remembered_email', $email, time() + (30 * 24 * 60 * 60), "/");
                    
                    // Generate secure remember token
                    $token = bin2hex(random_bytes(32));
                    $hashed_token = hash('sha256', $token);
                    
                    // Save to DB
                    $update_stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $update_stmt->execute([$hashed_token, $user['id']]);
                    
                    // Set remember_me cookie for 30 days (Secure & HttpOnly)
                    setcookie('remember_me', $user['id'] . ':' . $token, time() + (30 * 24 * 60 * 60), "/", "", true, true);
                } else {
                    setcookie('remembered_email', '', time() - 3600, "/");
                    
                    // Clear remember token in DB
                    $update_stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
                    $update_stmt->execute([$user['id']]);
                    
                    // Clear remember_me cookie
                    setcookie('remember_me', '', time() - 3600, "/");
                }
                
                
                $redirect_url = '../index.php';
                if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'login.php') === false) {
                    $redirect_url = $_SERVER['HTTP_REFERER'];
                }
                if (isset($user['role'])) {
                    if ($user['role'] === 'admin') {
                        $redirect_url = '../admin.php';
                    } elseif ($user['role'] === 'business') {
                        $redirect_url = '../dashboard.php';
                    } elseif ($user['role'] === 'customer') {
                        $redirect_url = '../moj_profil.php';
                    }
                }
                header('Location: ' . $redirect_url);
                exit;
            }
        } else {
            // Skontrolujeme zamestnancov
            $stmt_emp = $pdo->prepare("SELECT id, business_id, name, title, password_hash, is_active FROM employees WHERE email = ? AND is_active = 1");
            $stmt_emp->execute([$email]);
            $emp = $stmt_emp->fetch(PDO::FETCH_ASSOC);
            if ($emp && !empty($emp['password_hash']) && password_verify($password, $emp['password_hash'])) {
                $_SESSION['user_id'] = (int)$emp['business_id'];
                $_SESSION['employee_id'] = (int)$emp['id'];
                $_SESSION['employee_name'] = $emp['name'];
                $_SESSION['employee_title'] = $emp['title'];
                $_SESSION['is_employee'] = 1;
                $_SESSION['user_role'] = 'business';

                $redirect_url = '../dashboard.php';
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $error = t('Nesprávny email alebo heslo.');
            }
        }
        } else { $error = t('Prosím vyplňte všetky polia.'); }
    }
}

if (!empty($error)) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['auth_error'] = $error;
    $_SESSION['auth_view'] = 'login';
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
    if (strpos($ref, 'login_error=') === false) {
        $ref .= (strpos($ref, '?') !== false ? '&' : '?') . 'login_error=1';
    }
    header('Location: ' . $ref);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (!empty($success)) {
        $_SESSION['auth_success'] = $success;
    }
    
    // Vždy otvoríme modal, ak sme prišli na login.php
    $_SESSION['auth_view'] = 'login';
    
    $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ../index.php' . $query);
    exit;
}
