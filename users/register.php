<?php
// users/register.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$prefix = '../';
require_once $prefix . 'config.php';
require_once $prefix . 'translator_helper.php';
if (!defined('BRAND_NAME')) require_once $prefix . 'includes/branding.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_ip_country($ip) {
    if (empty($ip) || in_array($ip, ['127.0.0.1', '::1'])) {
        return 'SK'; // Lokálne testovanie
    }
    
    // Použijeme bezplatné API ip-api.com s timeoutom 1.5 sekundy
    $url = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,countryCode";
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 1.5
        ]
    ]);
    
    $response = @file_get_contents($url, false, $ctx);
    if ($response) {
        $data = json_decode($response, true);
        if (($data['status'] ?? '') === 'success') {
            return strtoupper($data['countryCode'] ?? '');
        }
    }
    return ''; // V prípade zlyhania vrátime prázdny reťazec (fallback)
}


require_once $prefix . 'includes/profanity_filter.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ref_code_post = trim($_POST['ref_code'] ?? '');
    $honeypot = $_POST['hp_check_field'] ?? '';
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

    // Zistíme IP adresu pre rate limiting
    $reg_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $reg_ip = trim(reset($ips));
    }

    if ($honeypot !== '') {
        $error = t('Bezpečnostná ochrana zlyhala. Skúste to prosím znova (Anti-Spam).');
    } elseif (is_registration_rate_limited($pdo, $reg_ip)) {
        $error = t('Príliš veľa pokusov o registráciu. Skúste to prosím znova o hodinu.');
     } else {
        $turnstile_valid = true;
        if (!empty($turnstile_token)) {
            $turnstile_valid = verify_turnstile($turnstile_token, TURNSTILE_SECRET_KEY);
        }
        
        if (!$turnstile_valid) {
            $error = t('Overenie CAPTCHA zlyhalo. Skúste to prosím znova.');
        } elseif ($fullname && $email && $password) {
        if (has_profanity($fullname)) {
            $error = t('Zadané meno obsahuje nevhodné alebo vulgárne výrazy. Vyberte si prosím slušné meno.');
        } elseif (is_brand_protected_username($fullname)) {
            $error = t('Meno obsahuje vyhradené slovo (aveino, ave a pod.). Vyberte si prosím iné meno.');
        } elseif (is_invalid_username($fullname)) {
            $error = t('Meno obsahuje nepovolené znaky alebo odkazy. Zadajte prosím vaše skutočné krstné meno.');
        } else {
            // B. Geografická kontrola (iba európske štáty)
            $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $user_ip = trim(end($ips));
            }
            
            $country = get_ip_country($user_ip);
            // Zoznam všetkých európskych krajín vrátane Ukrajiny (UA), bez Ruska (RU) a Turecka (TR)
            $allowedCountries = [
                'SK', 'CZ', 'PL', 'HU', 'AT', 'DE', 'GB', 'IE', 'FR', 'IT', 'ES', 'PT', 
                'BE', 'NL', 'DK', 'SE', 'NO', 'FI', 'CH', 'UA', 'RO', 'BG', 'GR', 'HR', 
                'SI', 'LT', 'LV', 'EE', 'LU', 'CY', 'MT', 'IS', 'AL', 'BA', 'MK', 'ME', 
                'RS', 'MD', 'AD', 'LI', 'MC', 'SM', 'VA', 'XK', 'BY', 'GI', 'IM', 'JE', 
                'GG', 'FO'
            ];
            
            if (!empty($country) && !in_array($country, $allowedCountries)) {
                $error = t('Registrácia na portáli AVEINO je povolená len pre používateľov z Európskej únie a vybraných európskych krajín.');
            }
            
            if (empty($error)) {
                // A. Skontrolujeme jednorazové e-maily (disposable emails)
                $emailParts = explode('@', $email);
            $domain = strtolower(end($emailParts));
            
            $blockedDomains = [
                'temp-mail.org', '10minutemail.com', 'yopmail.com', 'mailinator.com', 
                'dispostable.com', 'guerrillamail.com', 'tempmail.net', 'tempmail.com', 
                'fakemailgenerator.com', 'sharklasers.com', 'guerrillamailblock.com', 
                'guerrillamail.net', 'guerrillamail.org', 'guerrillamail.biz', 'grr.la', 
                'trashmail.com', 'getairmail.com', 'moakt.com', 'pokemail.net', 
                'temporary-mail.net', 'crazymailing.com', 'generator.email', 
                'tempmailo.com', 'internets.ru', 'dropmail.me', 'tempmail.dev', 
                'temp-mail.io', 'mailnesia.com', 'maildrop.cc', 'getnada.com'
            ];

            if (in_array($domain, $blockedDomains)) {
                $error = t('Registrácia s dočasnými alebo jednorazovými e-mailami nie je povolená. Použite prosím reálny e-mail.');
            } else {
                // 1. Skontrolujeme, či už e-mail existuje
                // Zaznamenáme pokus (aj neúspešný) pre rate limiting
                log_registration_attempt($pdo, $reg_ip);

                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = t('Tento e-mail je už zaregistrovaný.');
                } else {
                    $token = sprintf("%06d", mt_rand(100000, 999999));
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Skontrolujeme cookie a POST referral kod
                    $referred_by = null;
                    $referral_stage = 0;
                    $referrer_id = null;
                    $ref_code = !empty($ref_code_post) ? $ref_code_post : (isset($_COOKIE['ref_code']) ? trim($_COOKIE['ref_code']) : '');
                    
                    if (!empty($ref_code)) {
                        $ref_stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
                        $ref_stmt->execute([$ref_code]);
                        $ref_row = $ref_stmt->fetch();
                        if ($ref_row) {
                            $referrer_id = intval($ref_row['id']);
                            $referred_by = $referrer_id;
                            $referral_stage = 1;
                        }
                    }
                    
                    try {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, is_verified, verification_code, onboarding_completed) VALUES (?, ?, ?, 0, ?, 0)");
                if ($stmt->execute([$fullname, $email, $hashed, $token])) {
                    $new_user_id = intval($pdo->lastInsertId());
                    
                    // Referral is temporarily disabled due to schema changes
                    
                    // Odoslanie overovacieho e-mailu
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
                        $mail->setFrom($smtp_user, BRAND_NAME);
                        $mail->addAddress($email, $fullname);

                        $mail->isHTML(true);
                        $mail->Subject = t('Váš overovací kód - ') . BRAND_NAME;

                        $emailFont = "Outfit, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif";
                        $firstName = trim(explode(' ', trim($fullname))[0]);
                        $body = "
                        <div style='margin:0; padding:32px 16px; background-color:#f4f1ea; font-family:{$emailFont};'>
                          <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='max-width:520px; margin:0 auto; border-collapse:collapse;'>
                            <tr>
                              <td style='text-align:center; padding-bottom:24px;'>
                                <span style='font-family:{$emailFont}; font-size:22px; font-weight:800; letter-spacing:-0.3px; color:#0F172A;'>" . htmlspecialchars(BRAND_NAME) . "</span>
                              </td>
                            </tr>
                            <tr>
                              <td style='background-color:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:40px 32px; text-align:center; box-shadow:0 4px 20px rgba(15,23,42,0.04);'>
                                <p style='margin:0 0 6px; font-family:{$emailFont}; font-size:13px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#b08042;'>" . t('Overenie e-mailu') . "</p>
                                <h1 style='margin:0 0 12px; font-family:{$emailFont}; font-size:22px; font-weight:700; color:#0F172A;'>" . t('Dobrý deň, ') . htmlspecialchars($firstName) . "!</h1>
                                <p style='margin:0 0 28px; font-family:{$emailFont}; font-size:14.5px; line-height:1.6; color:#64748b;'>" . t('Ďakujeme za registráciu. Zadajte tento 6-miestny kód a dokončite overenie účtu:') . "</p>
                                <table role='presentation' align='center' cellpadding='0' cellspacing='0' style='margin:0 auto 8px;'>
                                  <tr>
                                    <td style='background:linear-gradient(135deg,#d4af37,#b08042); border-radius:14px; padding:20px 36px; box-shadow:0 8px 20px rgba(176,128,66,0.3);'>
                                      <span style='font-family:{$emailFont}; font-size:36px; font-weight:800; letter-spacing:8px; color:#ffffff;'>" . htmlspecialchars($token) . "</span>
                                    </td>
                                  </tr>
                                </table>
                                <p style='margin:20px 0 0; font-family:{$emailFont}; font-size:12.5px; color:#94a3b8;'>" . t('Kód je platný krátku dobu. Ak ste o registráciu nežiadali, tento e-mail môžete ignorovať.') . "</p>
                              </td>
                            </tr>
                            <tr>
                              <td style='padding-top:24px; text-align:center;'>
                                <p style='margin:0; font-family:{$emailFont}; font-size:12px; color:#94a3b8; line-height:1.5;'>" . t('Tento e-mail bol vygenerovaný automaticky systémom') . " " . htmlspecialchars(BRAND_NAME) . "." . "</p>
                              </td>
                            </tr>
                          </table>
                        </div>
                        ";

                        $mail->Body    = $body;
                        $mail->AltBody = t('Dobrý deň') . " " . $firstName . ",\n\n" . t('Váš 6-miestny overovací kód je: ') . $token;

                        $mail->send();
                    } catch (\Exception $e) {
                        try {
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->send();
                        } catch (\Exception $e2) {
                            error_log("Overovací e-mail zlyhal: " . $mail->ErrorInfo);
                        }
                    }

                    $_SESSION['verify_email'] = $email;
                    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
                    if (strpos($ref, 'verify_required=1') === false) {
                        $ref .= (strpos($ref, '?') !== false ? '&' : '?') . 'verify_required=1';
                    }
                    header("Location: " . $ref);
                    exit;
                } else {
                    $error = t('Chyba pri registrácii.');
                }
            } catch (\PDOException $ex) {
                if ($ex->getCode() == 23000 || strpos($ex->getMessage(), '1062') !== false) {
                    $error = t('Tento e-mail je už zaregistrovaný.');
                } else {
                    $error = t('Chyba databázy: ') . $ex->getMessage();
                }
            }
            }
        }
        } // end of if (empty($error))
        }
    } else {
        $error = t('Vyplňte všetky polia.');
    }
    }
}

if (!empty($error)) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['auth_error'] = $error;
    $_SESSION['auth_view'] = 'register';
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
    if (strpos($ref, 'register_error=') === false) {
        $ref .= (strpos($ref, '?') !== false ? '&' : '?') . 'register_error=1';
    }
    header('Location: ' . $ref);
    exit;
}

