<?php
// users/forgot_password.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$prefix = '../';
require_once $prefix . 'config.php';
require_once $prefix . 'translator_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged in users
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $honeypot = $_POST['hp_username'] ?? '';
    
    if ($honeypot !== '') {
        $error = t('Bezpečnostná ochrana zlyhala. Skúste to prosím znova (Anti-Spam).');
    } else {
        // Turnstile Overenie
        $ts_token = $_POST['cf-turnstile-response'] ?? '';
        $turnstile_valid = true;
        if (!empty($ts_token)) {
            $turnstile_valid = verify_turnstile($ts_token, TURNSTILE_SECRET_KEY);
        }
        
        if (!$turnstile_valid) {
            $error = t('Bezpečnostné overenie zlyhalo. Skúste to znova. (Anti-Spam)');
        } elseif ($email) {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate a secure random token
                $token = bin2hex(random_bytes(32));
                $hashed_token = hash('sha256', $token);
                
                // Save hashed token and expiration in database
                $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                $update_stmt->execute([$hashed_token, $user['id']]);
                
                // Build absolute reset link
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domain = $_SERVER['HTTP_HOST'];
                $current_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $reset_link = $protocol . $domain . $current_dir . "/reset_password.php?token=" . $token . "&email=" . urlencode($email);
                
                // Load PHPMailer files
                require_once $prefix . 'includes/phpmailer/exception.php';
                require_once $prefix . 'includes/phpmailer/phpmailer.php';
                require_once $prefix . 'includes/phpmailer/smtp.php';
                
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'mail.usr.sk';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'no-reply@aveino.eu';
                    $mail->Password   = 'Neviem0950400203';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    
                    $mail->CharSet = 'UTF-8';
                    $mail->setFrom('no-reply@aveino.eu', t('AVEINO Bezpečnosť'));
                    $mail->addAddress($user['email'], $user['username']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = t('Žiadosť o obnovenie hesla - AVEINO');
                    
                    // Elegant, modern HTML design for the reset email
                    $mail->Body = "
                    <div style='font-family: 'Outfit', sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e1e8ed; border-radius: 16px; padding: 40px; background-color: #fafaf9; box-shadow: 0 4px 20px rgba(0,0,0,0.02);'>
                        <div style='text-align: center; margin-bottom: 30px;'>
                            <img src='https://aveino.eu/assets/logos/logo.png' alt='AVEINO' width='80' style='max-width: 80px; height: auto; display: inline-block;'>
                            <p style='font-size: 13px; color: #888; margin-top: 4px; font-weight: 600;'>" . t('Inteligentná inzercia poháňaná AI') . "</p>
                        </div>
                        
                        <div style='background-color: #ffffff; padding: 35px; border-radius: 12px; border: 1px solid #eaeae9; box-shadow: 0 4px 12px rgba(0,0,0,0.015);'>
                            <h3 style='margin-top: 0; font-size: 20px; font-weight: 700; color: #111; letter-spacing: -0.3px;'>" . t('Obnovenie prístupu k účtu') . "</h3>
                            <p style='font-size: 15px; color: #555; line-height: 1.6; margin-bottom: 25px;'>" . sprintf(t('Dobrý deň %s, obdržali sme žiadosť o obnovenie hesla k vášmu účtu na portáli AVEINO. Pre nastavenie nového hesla kliknite na tlačidlo nižšie:'), htmlspecialchars($user['username'])) . "</p>
                            
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$reset_link}' style='display: inline-block; padding: 14px 30px; background-color: #ff6f00; color: #ffffff; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 10px; transition: background-color 0.2s; box-shadow: 0 4px 12px rgba(255, 111, 0, 0.25);'>" . t('Obnoviť heslo') . "</a>
                            </div>
                            
                            <p style='font-size: 13px; color: #888; line-height: 1.5; margin-top: 25px;'>" . t('Odkaz na obnovenie hesla je platný nasledujúcich 60 minút.') . "</p>
                            <p style='font-size: 12px; color: #a0a09f; line-height: 1.4; border-top: 1px solid #f0f0ef; padding-top: 15px; margin-top: 25px;'>" . t('Ak ste o toto obnovenie nežiadali, môžete tento e-mail bezpečne ignorovať. Vaše súčasné heslo zostane nezmenené.') . "</p>
                        </div>
                        
                        <div style='text-align: center; margin-top: 25px; font-size: 12px; color: #888;'>
                            <p>&copy; " . date('Y') . " AVEINO | " . t('Bezpečný inzertný portál') . "</p>
                        </div>
                    </div>";
                    
                    $mail->AltBody = t('Dobrý deň ') . $user['username'] . t(', pre obnovenie hesla kliknite na nasledujúci odkaz alebo ho skopírujte do prehliadača: ') . "\n" . $reset_link . "\n\n" . t('Odkaz platí 60 minút.');
                    
                    $mail->send();
                } catch (\Exception $e) {
                    try {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->send();
                    } catch (\Exception $e2) {
                        error_log("Password reset email failed to send: " . $mail->ErrorInfo);
                    }
                }
            }
            
            // Reassuring message for privacy/anti-enumeration
            $success = t('Ak sa e-mailová adresa nachádza v našej databáze, odoslali sme na ňu odkaz na obnovenie hesla. Skontrolujte prosím svoju schránku (aj priečinok Spam).');
        } else {
            $error = t('Zadajte prosím platnú e-mailovú adresu.');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/scrollbars.css?v=3">
    <title><?php echo t('Zabudnuté heslo?'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        :root {
            --bg-color: #f7f7f8;
            --card-bg: #ffffff;
            --text-primary: #111111;
            --text-secondary: #555555;
            --border-color: #e5e5e5;
            --accent: #d1b06b;
        }
        body { margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg-color); font-family: 'Outfit', sans-serif; }
        .auth-wrapper { width: 100%; max-width: 500px; padding: 20px; box-sizing: border-box; }
        .auth-card { background: var(--card-bg); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color); }
        .auth-field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .auth-field label { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .auth-input-wrap { position: relative; display: flex; align-items: center; }
        .auth-input-icon { position: absolute; left: 15px; color: #888; font-size: 20px; }
        .auth-input-wrap input { width: 100%; height: 50px; padding: 0 15px 0 45px; box-sizing: border-box; border: 1px solid var(--border-color); border-radius: 12px; font-size: 14px; background: var(--bg-color); color: var(--text-primary); outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif; }
        .auth-input-wrap input:focus { border-color: var(--accent); background: var(--card-bg); box-shadow: 0 0 0 4px rgba(209, 176, 107, 0.1); }
        .auth-submit { width: 100%; height: 50px; border: none; border-radius: 12px; background: var(--text-primary); color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.2s; font-family: 'Outfit', sans-serif; }
        .auth-submit:hover { background: var(--accent); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(209, 176, 107, 0.3); }
        .auth-error { background: #fde2e2; color: #d32f2f; padding: 12px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; gap: 8px; border: 1px solid #ffbaba; }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px; flex-direction: column;">
        <div style="padding: 40px; width: 100%; box-sizing: border-box;">
            
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="width: 60px; height: 60px; border-radius: 18px; background: rgba(176, 128, 66, 0.1); border: 2px solid #b08042; display: flex; align-items: center; justify-content: center; color: #b08042; box-shadow: 0 4px 15px rgba(176, 128, 66, 0.3); margin: 0 auto 20px auto;">
                    <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 28px;">lock_reset</span>
                </div>
                <h1 style="margin: 0 0 10px 0; font-size: 24px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px;"><?php echo t('Zabudnuté heslo?'); ?></h1>
                <p style="margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.5;"><?php echo t('Zadajte svoju e-mailovú adresu a my vám zašleme odkaz na resetovanie hesla.'); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="auth-error" style="margin-bottom: 20px;">
                    <span class="material-symbols-outlined notranslate" style="font-size:16px;">error</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="auth-success" style="margin-bottom: 20px; color: #27ae60; background: rgba(39,174,96,0.1); border-radius: 12px; padding: 16px; display: flex; align-items: flex-start; gap: 12px; font-size: 13.5px; font-weight: 600; line-height: 1.5; border: 1px solid rgba(39,174,96,0.15);">
                    <span class="material-symbols-outlined notranslate" style="font-size:18px; color: #27ae60; flex-shrink: 0; margin-top: 1px;">check_circle</span>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form action="forgot_password.php" method="POST">
                    
                    <div class="auth-field" style="margin-bottom: 25px;">
                        <label><?php echo t('E-mailová adresa'); ?></label>
                        <div class="auth-input-wrap">
                            <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">alternate_email</span>
                            <input type="email" name="email" placeholder="<?php echo t('vas@email.sk'); ?>" autocomplete="off" autofocus required style="font-size: 14px;">
                        </div>
                    </div>
                    
                    <!-- Honeypot pasca na spamových robotov -->
                    <div style="display: none !important;">
                        <label><?php echo t('Nevyplňujte toto pole, ak ste človek:'); ?></label>
                        <input type="text" name="hp_username" value="" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <!-- Cloudflare Turnstile (Anti-Spam) -->
                    <div style="margin: 20px 0; display: flex; justify-content: center;">
                        <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="auto"></div>
                    </div>
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

                    <button type="submit" class="auth-submit" style="font-weight: 700; height: 48px; border-radius: 12px; font-size: 14.5px;"><?php echo t('Odoslať odkaz na obnovenie'); ?></button>
                    
                </form>
            <?php endif; ?>
            
            <div style="margin-top: 25px; text-align: center;">
                <a href="login.php" style="color: var(--text-secondary); font-size: 13.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 18px;">arrow_back</span>
                    <?php echo t('Späť na prihlásenie'); ?>
                </a>
            </div>
            
        </div>
    </div>
</div>

</div>

</body>
</html>
