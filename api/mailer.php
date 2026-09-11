<?php
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../phpmailer/src/exception.php';
require_once __DIR__ . '/../phpmailer/src/phpmailer.php';
require_once __DIR__ . '/../phpmailer/src/smtp.php';

require_once __DIR__ . '/../includes/test_email_override.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';

if (!function_exists('decrypt_data')) {
    function decrypt_data($data, $key, $iv_b64) {
        if (empty($data) || empty($iv_b64)) return '';
        $decoded = base64_decode($data);
        if ($decoded === false) return '';
        if (strlen($iv_b64) === 24 && base64_decode($iv_b64, true) !== false) {
            $raw_iv = base64_decode($iv_b64);
        } else {
            $raw_iv = $iv_b64;
        }
        $decrypted = openssl_decrypt($decoded, 'aes-256-cbc', $key, 0, $raw_iv);
        return ($decrypted !== false) ? $decrypted : '';
    }
}

// Načíta a dešifruje vlastné SMTP údaje prevádzky (ak si ich pripojila cez "Prepojiť existujúci e-mail")
// Funkcia je dostupná len od balíka START vyššie — vo FREE sa e-maily zákazníkom vždy posielajú z nášho účtu.
function getEstablishmentSmtpConfig($business_user_id) {
    global $pdo;
    if (!$business_user_id) return null;
    try {
        $tierStmt = $pdo->prepare("SELECT subscription_tier FROM establishments WHERE user_id = ? LIMIT 1");
        $tierStmt->execute([$business_user_id]);
        $tier = strtolower($tierStmt->fetchColumn() ?: 'free');
        if ($tier === 'free') return null;

        $stmt = $pdo->prepare("SELECT smtp_server, smtp_port, smtp_user, smtp_pass, encryption_iv FROM user_email_settings WHERE user_id = ?");
        $stmt->execute([$business_user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['smtp_server']) || empty($row['smtp_user'])) return null;
        $plainPass = decrypt_data($row['smtp_pass'], EMAIL_ENC_KEY, $row['encryption_iv']);
        if ($plainPass === '') return null;
        return [
            'server' => $row['smtp_server'],
            'port'   => (int)($row['smtp_port'] ?: 465),
            'user'   => $row['smtp_user'],
            'pass'   => $plainPass,
        ];
    } catch (\Throwable $e) {
        return null;
    }
}

// Nastaví SMTP transport (vlastný účet prevádzky, alebo predvolený) na existujúcej PHPMailer inštancii
function applySmtpTransport(PHPMailer $mail, $host, $port, $username, $password, $fromEmail, $fromName) {
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $username;
    $mail->Password   = $password;
    $mail->SMTPSecure = ((int)$port === 587) ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = (int)$port;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom($fromEmail, $fromName);
}

function sendBookingEmail($to_email, $to_name, $booking_details, $is_customer = true, $business_user_id = null) {
    global $smtp_user, $smtp_pass;

    $mail = new PHPMailer(true);

    try {
        $mail->addAddress($to_email, $to_name);

        // Vytvorenie .ics obsahu (zdieľané s priamym stiahnutím z prehliadača)
        require_once __DIR__ . '/../includes/ics_helper.php';
        $ics_content = generate_ics_content($booking_details);
        $mail->addStringAttachment($ics_content, 'rezervacia.ics', 'base64', 'text/calendar');

        $mail->isHTML(true);
        if ($is_customer) {
            $deposit_html = "";
            if (isset($booking_details['requires_deposit']) && $booking_details['requires_deposit']) {
                $qr_img_html = "";
                if (!empty($booking_details['qr_code_png_base64'])) {
                    // Vložené ako CID príloha, nie ako base64 data URI — Gmail a mnohé ďalšie
                    // e-mailové klienty base64 obrázky priamo v tele e-mailu blokujú.
                    $mail->addStringEmbeddedImage(base64_decode($booking_details['qr_code_png_base64']), 'zalohaqr', 'qr.png', 'base64', 'image/png');
                    $qr_img_html = "<img src='cid:zalohaqr' alt='QR Kód pre platbu' style='max-width:200px; border:1px solid #d1d5db; border-radius:8px; padding:5px; background:#fff;'>";
                }
                $deposit_html = "
                <div style='background:#fef3c7; border-left:4px solid #f5b041; padding:15px; margin-top:20px; border-radius:4px;'>
                    <h3 style='margin-top:0; color:#b45309;'>Uhraďte prosím zálohu</h3>
                    <p style='color:#92400e; margin-bottom:15px;'>Vaša rezervácia bude potvrdená až po úhrade zálohy vo výške <strong>{$booking_details['deposit_amount']}</strong>.</p>
                    <p style='margin-bottom:10px;'>Môžete ju uhradiť naskenovaním tohto QR kódu vo vašej bankovej aplikácii (Pay by square / SEPA):</p>
                    {$qr_img_html}
                </div>";
            }
            
            $status_text = (isset($booking_details['status']) && $booking_details['status'] === 'pending_deposit') ? "čaká na úhradu zálohy" : "bola prijatá (čaká na potvrdenie)";

            $manage_html = "";
            if (!empty($booking_details['manage_url'])) {
                $manage_html = "<p style='margin-top:20px;'><a href='{$booking_details['manage_url']}' style='color:#b08042; font-weight:bold;'>Zobraziť, zmeniť alebo zrušiť rezerváciu</a> (bez potreby prihlásenia)</p>";
            }

            $mail->Subject = 'Rezervácia termínu - ' . $booking_details['establishment_name'];
            $mail->Body    = "<h2>Dobrý deň, {$to_name}!</h2>
                              <p>Vaša rezervácia v prevádzke <strong>{$booking_details['establishment_name']}</strong> {$status_text}.</p>
                              <ul>
                                <li><strong>Služba:</strong> {$booking_details['service_name']}</li>
                                <li><strong>Dátum:</strong> {$booking_details['date']}</li>
                                <li><strong>Čas:</strong> {$booking_details['time']}</li>
                                <li><strong>Adresa:</strong> {$booking_details['location']}</li>
                                <li><strong>Celková cena:</strong> {$booking_details['total_price']}</li>
                              </ul>
                              {$deposit_html}
                              {$manage_html}
                              <p style='margin-top:20px;'>V prílohe nájdete súbor pre pridanie udalosti do Google / Apple kalendára.</p>
                              <br>
                              <p>S pozdravom,<br>Tím " . BRAND_NAME . "</p>";
        } else {
            $note_html = "";
            if (!empty($booking_details['customer_note'])) {
                $note_html = "<div style='background:#fef3c7; border-left:4px solid #f5b041; padding:12px 15px; margin-top:15px; border-radius:4px;'>
                    <strong style='color:#92400e;'>Poznámka zákazníka:</strong>
                    <p style='margin:6px 0 0 0; white-space:pre-wrap;'>" . htmlspecialchars($booking_details['customer_note']) . "</p>
                </div>";
            }
            $mail->Subject = 'Nová rezervácia - ' . $booking_details['service_name'];
            $mail->Body    = "<h2>Nová rezervácia!</h2>
                              <p>Zákazník <strong>{$to_name}</strong> si vytvoril rezerváciu.</p>
                              <ul>
                                <li><strong>Služba:</strong> {$booking_details['service_name']}</li>
                                <li><strong>Dátum:</strong> {$booking_details['date']}</li>
                                <li><strong>Čas:</strong> {$booking_details['time']}</li>
                                <li><strong>E-mail zákazníka:</strong> {$to_email}</li>
                              </ul>
                              {$note_html}
                              <br>
                              <p>S pozdravom,<br>Tím " . BRAND_NAME . "</p>";
        }

        // Ak ide o e-mail zákazníkovi a prevádzka má pripojenú vlastnú schránku, skúsime poslať cez ňu
        // (aby zákazník videl adresu a názov svojho salónu, nie našu). Pri zlyhaní automaticky spadneme
        // späť na predvolený systémový účet, aby potvrdenie nezmizlo.
        $customConfig = $is_customer ? getEstablishmentSmtpConfig($business_user_id) : null;
        $fromName = $is_customer ? ($booking_details['establishment_name'] ?? BRAND_NAME) : BRAND_NAME;

        if ($customConfig) {
            try {
                applySmtpTransport($mail, $customConfig['server'], $customConfig['port'], $customConfig['user'], $customConfig['pass'], $customConfig['user'], $fromName);
                apply_test_email_override($mail, $to_email);
                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("Vlastný SMTP prevádzky zlyhal (user_id={$business_user_id}), prepínam na predvolený účet: {$mail->ErrorInfo}");
            }
        }

        applySmtpTransport($mail, 'mail.usr.sk', 465, $smtp_user, $smtp_pass, $smtp_user, $fromName);
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

function sendRegisterInviteEmail($to_email, $to_name, $establishment_name) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Vytvorte si účet na Rezervos a získajte výhody';
        $mail->Body    = "<h2>Dobrý deň, {$to_name}!</h2>
                          <p>Prevádzka <strong>{$establishment_name}</strong> vás pridala do svojich klientov na Rezervos.</p>
                          <p>Ak si vytvoríte vlastný účet, získate navyše:</p>
                          <ul>
                            <li>Prehľad všetkých svojich rezervácií na jednom mieste</li>
                            <li>Možnosť rezervovať online priamo z aplikácie</li>
                            <li>Peňaženku s kreditom a odmenami za zdieľanie</li>
                            <li>Prístup ku Kreslo Hunter a ďalším výhodám</li>
                          </ul>
                          <p><a href='https://rezervos.eu/index.php?ref=invite' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Vytvoriť si účet zadarmo</a></p>
                          <br>
                          <p>S pozdravom,<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

function sendLastMinuteNotification($to_email, $to_name, $est_name, $service_name, $slot_date, $slot_time, $price) {
    global $smtp_user, $smtp_pass;
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
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Uvoľnil sa termín v prevádzke ' . $est_name . '!';
        $mail->Body    = "<h2>Dobrý deň, {$to_name}!</h2>
                          <p>Vaša obľúbená prevádzka <strong>{$est_name}</strong> práve pridala nový Last Minute termín:</p>
                          <ul>
                            <li><strong>Služba:</strong> {$service_name}</li>
                            <li><strong>Dátum:</strong> {$slot_date}</li>
                            <li><strong>Čas:</strong> {$slot_time}</li>
                            <li><strong>Cena:</strong> {$price} €</li>
                          </ul>
                          <p><a href='https://" . BRAND_SITE . "/' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Uchmatni si tento termín hneď!</a></p>
                          <br>
                          <p>S pozdravom,<br>Tím " . BRAND_NAME . "</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

// Druhá fáza zrušenia rezervácie zákazníkom — kým klikne na tento odkaz, rezervácia ostáva
// v platnosti. Chráni pred nechceným zrušením (napr. omylom kliknuté tlačidlo v e-maile).
function sendCancelConfirmationEmail($to_email, $to_name, $est_name, $service_name, $date, $time, $confirm_url) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Potvrďte zrušenie rezervácie v prevádzke ' . $est_name;
        $mail->Body    = "<h2>Naozaj chcete zrušiť túto rezerváciu?</h2>
                          <ul>
                            <li><strong>Prevádzka:</strong> {$est_name}</li>
                            <li><strong>Služba:</strong> {$service_name}</li>
                            <li><strong>Dátum:</strong> {$date}</li>
                            <li><strong>Čas:</strong> {$time}</li>
                          </ul>
                          <p>Rezervácia <strong>zatiaľ nie je zrušená</strong> — zostáva v platnosti, kým nepotvrdíte kliknutím na tlačidlo nižšie:</p>
                          <p><a href='{$confirm_url}' style='background:#c0392b; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Áno, zrušiť rezerváciu</a></p>
                          <p style='font-size:12px; color:#888;'>Ak ste zrušenie nechceli (napr. omylom kliknuté tlačidlo), tento e-mail jednoducho ignorujte — rezervácia zostane platná.</p>
                          <br>
                          <p>S pozdravom,<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

// Upozornenie prevádzke, že zákazník naozaj zrušil rezerváciu — s kontaktom naň (pre prípad,
// že chce zavolať a overiť, či to nebol omyl) a odkazom na rýchle vytvorenie Last Minute ponuky
// z uvoľneného termínu.
function sendCancellationLastMinuteOffer($to_email, $est_name, $customer_name, $customer_phone, $service_name, $date, $time, $lm_create_url) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email, $est_name);

        $phone_html = $customer_phone ? "<li><strong>Telefón zákazníka:</strong> {$customer_phone}</li>" : "";

        $mail->isHTML(true);
        $mail->Subject = 'Zákazník zrušil rezerváciu — uvoľnil sa termín';
        $mail->Body    = "<h2>Uvoľnil sa vám termín</h2>
                          <p>Zákazník <strong>{$customer_name}</strong> zrušil svoju rezerváciu:</p>
                          <ul>
                            <li><strong>Služba:</strong> {$service_name}</li>
                            <li><strong>Dátum:</strong> {$date}</li>
                            <li><strong>Čas:</strong> {$time}</li>
                            {$phone_html}
                          </ul>
                          <p style='font-size:13px; color:#666;'>Ak si myslíte, že išlo o omyl, môžete zákazníkovi zavolať a termín mu ponechať.</p>
                          <p><a href='{$lm_create_url}' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Ponúknuť ako Last Minute (-20%, jedným klikom)</a></p>
                          <br>
                          <p>S pozdravom,<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

function sendUnavailabilityRescheduleNotification($to_email, $to_name, $est_name, $service_name, $date, $time, $manage_url) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Potrebná zmena termínu v prevádzke ' . $est_name;
        $mail->Body    = "<h2>Dobrý deň, {$to_name}!</h2>
                          <p>Ľutujeme, ale pracovník, ktorý mal vykonať vašu rezerváciu v prevádzke <strong>{$est_name}</strong>, nebude v tomto termíne k dispozícii:</p>
                          <ul>
                            <li><strong>Služba:</strong> {$service_name}</li>
                            <li><strong>Dátum:</strong> {$date}</li>
                            <li><strong>Čas:</strong> {$time}</li>
                          </ul>
                          <p>Prosím, vyberte si nový termín (napr. u iného kolegu) alebo rezerváciu zrušte:</p>
                          <p><a href='{$manage_url}' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Zmeniť alebo zrušiť rezerváciu</a></p>
                          <br>
                          <p>Ospravedlňujeme sa za komplikácie.<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

function sendWaitlistNotification($to_email, $to_name, $est_name, $service_name, $date, $time, $booking_url) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Uvoľnil sa termín v prevádzke ' . $est_name . '!';
        $mail->Body    = "<h2>Dobrý deň, {$to_name}!</h2>
                          <p>Prihlásili ste sa na čakaciu listinu v prevádzke <strong>{$est_name}</strong> a práve sa uvoľnil termín, ktorý by vám mohol vyhovovať:</p>
                          <ul>
                            <li><strong>Služba:</strong> {$service_name}</li>
                            <li><strong>Dátum:</strong> {$date}</li>
                            <li><strong>Čas:</strong> {$time}</li>
                          </ul>
                          <p><a href='{$booking_url}' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Rezervovať tento termín</a></p>
                          <p style='font-size:12px; color:#888;'>Termín si môže rezervovať ktokoľvek — odporúčame konať čo najskôr.</p>
                          <br>
                          <p>S pozdravom,<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

// Potvrdenie e-mailu pri prihlásení na odber noviniek prevádzky (verejný formulár bez konta —
// e-mail nie je overený, preto vyžadujeme klik na odkaz, kým sa odber naozaj aktivuje)
function sendNewsletterConfirmEmail($to_email, $est_name, $confirm_url) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email);

        $mail->isHTML(true);
        $mail->Subject = 'Potvrďte odber noviniek od ' . $est_name;
        $mail->Body    = "<h2>Potvrďte svoj e-mail</h2>
                          <p>Požiadali ste o odber noviniek od prevádzky <strong>{$est_name}</strong>. Kliknutím na tlačidlo nižšie potvrdíte, že táto adresa je vaša a odber sa aktivuje:</p>
                          <p><a href='{$confirm_url}' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Potvrdiť odber</a></p>
                          <p style='font-size:12px; color:#888;'>Ak ste o odber nežiadali, tento e-mail jednoducho ignorujte — bez potvrdenia sa odber neaktivuje.</p>
                          <br>
                          <p>S pozdravom,<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

// Žiadosť o hodnotenie — posiela sa 2 dni po ukončenej návšteve, ešte pred 72h automatickým
// slepým odhalením (viz process72HourAutoRatings), aby mal zákazník reálnu šancu ohodnotiť sám.
function sendReviewRequestEmail($to_email, $to_name, $est_name, $service_name, $review_url) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Ako sa Vám páčila návšteva v ' . $est_name . '?';
        $mail->Body    = "<h2>Dobrý deň, {$to_name}!</h2>
                          <p>Nedávno ste navštívili prevádzku <strong>{$est_name}</strong> (služba: {$service_name}). Boli by sme radi za pár slov o tom, ako ste boli spokojní.</p>
                          <p><a href='{$review_url}' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Ohodnotiť návštevu</a></p>
                          <p style='font-size:12px; color:#888;'>Vaše hodnotenie uvidí prevádzka aj po odpovedi od nej samotnej — obe strany hodnotia navzájom.</p>
                          <br>
                          <p>S pozdravom,<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

// "Chýbate nám" — jednorazová pripomienka, keď sa zákazník k prevádzke dlhšie nevrátil (viz sendWinbackEmails)
function sendWinbackEmail($to_email, $to_name, $est_name, $booking_url) {
    global $smtp_user, $smtp_pass;
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

        $mail->setFrom($smtp_user, 'Rezervos');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Chýbate nám, ' . $to_name . '!';
        $mail->Body    = "<h2>Dobrý deň, {$to_name}!</h2>
                          <p>Všimli sme si, že ste dlhšie nenavštívili prevádzku <strong>{$est_name}</strong>. Radi by sme Vás opäť privítali!</p>
                          <p><a href='{$booking_url}' style='background:#b08042; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px;'>Rezervovať nový termín</a></p>
                          <br>
                          <p>S pozdravom,<br>Tím Rezervos</p>";
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sa nepodarilo odoslať: {$mail->ErrorInfo}");
        return false;
    }
}

?>
