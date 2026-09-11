<?php
/**
 * Zdieľané funkcie na odoslanie marketingovej správy — používa ich api/marketing.php
 * (manuálne kampane) aj cron (automatické pozdravy k meninám/narodeninám/sviatkom).
 */
require_once __DIR__ . '/../api/mailer.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Nahradí zástupné znaky v texte kampane skutočnými údajmi príjemcu
function apply_marketing_placeholders($content, $name, $last_service, $last_visit_date) {
    return str_replace(
        ['{{NAME}}', '{{SLUZBA}}', '{{DATUM}}'],
        [$name ?: 'Vážený zákazník', $last_service ?: 'našej službe', $last_visit_date ? date('d.m.Y', strtotime($last_visit_date)) : 'poslednej návšteve'],
        $content
    );
}

function ensureEstablishmentTemplatesTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS establishment_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        name VARCHAR(120) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_est (establishment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Predvolené (vstavané) šablóny, ktoré si daná prevádzka pre seba skryla — sú zdieľané globálne
// pre všetky prevádzky, takže sa nemažú, len sa pre konkrétnu prevádzku vynechajú zo zoznamu
function ensureHiddenTemplatesTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS establishment_hidden_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        template_key VARCHAR(30) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_est_tpl (establishment_id, template_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Zabalí (už s dosadenými premennými) text do grafickej HTML šablóny, ak je vybraná. Vlastné šablóny
// prevádzky majú template_id v tvare "custom_<id>" — $conn a $establishment_id treba len pre ne
// (overenie, že šablóna naozaj patrí danej prevádzke), pri vstavaných šablónach sa nepoužijú.
function wrap_marketing_template($template_id, $content_html, $recipient_name, $est_name, $root_url, $conn = null, $establishment_id = null, $business_user_id = null, $recipient_user_id = null) {
    $final_html = nl2br($content_html);
    $tpl_file = null;
    $img_path = null;
    $unsubscribe_html = '';
    if ($conn && $recipient_user_id) {
        $unsubscribe_url = buildUnsubscribeUrl($conn, $root_url, $recipient_user_id);
        if ($unsubscribe_url) {
            $unsubscribe_html = '<div style="margin-top:8px;"><a href="' . htmlspecialchars($unsubscribe_url) . '" style="color:#9b8f7c; text-decoration:underline;">Odhlásiť sa z odberu takýchto správ</a></div>';
        }
    }

    // Prevádzka od balíka Štart vyššie s pripojenou vlastnou schránkou vidí v hlavičke e-mailu
    // svoj názov (biely, bez loga Rezervos) — brand appky ostáva len v pätičke šablóny.
    $header_html = '<span style="font-family: \'Outfit\', sans-serif; font-size: 24px; font-weight: 800; letter-spacing: 1px;"><span style="color: #ffffff;">REZER</span><span style="color: #b08042; font-weight: 900;">VOS</span></span>';
    if ($business_user_id && getEstablishmentSmtpConfig($business_user_id)) {
        $header_html = '<span style="font-family: \'Outfit\', sans-serif; font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">' . htmlspecialchars($est_name) . '</span>';
    }

    if ($template_id === 'nameday_bouquet' || $template_id === 'nameday_whiskey') {
        $tpl_file = __DIR__ . '/../libs/templates/nameday_template.html';
        $img_path = 'libs/templates/images/' . ($template_id === 'nameday_bouquet' ? 'nameday_bouquet.png' : 'nameday_whiskey.png');
    } elseif (in_array($template_id, ['easter_slovak', 'easter_full', 'easter_korbac'], true)) {
        $tpl_file = __DIR__ . '/../libs/templates/easter_template.html';
        $img_path = 'libs/templates/images/' . $template_id . '.png';
    } elseif (strpos($template_id, 'custom_') === 0 && $conn && $establishment_id) {
        ensureEstablishmentTemplatesTable($conn);
        $custom_id = (int)substr($template_id, 7);
        $stmt = $conn->prepare("SELECT image_path FROM establishment_templates WHERE id = ? AND establishment_id = ?");
        $stmt->bind_param("ii", $custom_id, $establishment_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $tpl_file = __DIR__ . '/../libs/templates/custom_template.html';
            $img_path = $row['image_path'];
        }
    }

    if ($tpl_file && file_exists($tpl_file)) {
        $tpl = file_get_contents($tpl_file);
        $final_html = str_replace(
            ['{{ROOT_URL}}', '{{IMAGE_PATH}}', '{{NAME}}', '{{MESSAGE}}', '{{ESTABLISHMENT_NAME}}', '{{YEAR}}', '{{HEADER_HTML}}', '{{UNSUBSCRIBE_HTML}}'],
            [$root_url, $img_path, htmlspecialchars($recipient_name), $content_html, htmlspecialchars($est_name), date('Y'), $header_html, $unsubscribe_html],
            $tpl
        );
    } elseif ($unsubscribe_html) {
        // "Iba text" šablóna nemá žiadnu grafickú pätičku — odhlasovací odkaz sa pripojí aspoň ako obyčajný text
        $final_html .= '<br><br><hr style="border:none;border-top:1px solid #e5e5e5;">' . $unsubscribe_html;
    }
    return $final_html;
}

// Vráti trvalý odhlasovací token zákazníka (vygeneruje ho pri prvom použití) a poskladá z neho
// verejný odkaz, cez ktorý sa dá vypnúť súhlas s marketingom bez prihlásenia
function buildUnsubscribeUrl($conn, $root_url, $recipient_user_id) {
    $recipient_user_id = (int)$recipient_user_id;
    if (!$recipient_user_id) return null;
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS unsubscribe_token VARCHAR(64) DEFAULT NULL");
    $stmt = $conn->prepare("SELECT unsubscribe_token FROM users WHERE id = ?");
    $stmt->bind_param("i", $recipient_user_id);
    $stmt->execute();
    $token = $stmt->get_result()->fetch_assoc()['unsubscribe_token'] ?? null;
    if (!$token) {
        $token = bin2hex(random_bytes(20));
        $upd = $conn->prepare("UPDATE users SET unsubscribe_token = ? WHERE id = ?");
        $upd->bind_param("si", $token, $recipient_user_id);
        $upd->execute();
    }
    return $root_url . 'odhlasenie.php?uid=' . $recipient_user_id . '&token=' . $token;
}

// Odošle jeden e-mail kampane — ak má prevádzka (od balíka Štart vyššie) pripojenú a funkčnú
// vlastnú schránku, pošle sa z nej (zákazník vidí adresu a názov salónu, nie nás), inak ide
// cez náš systémový účet. Pri zlyhaní vlastnej schránky automaticky spadne späť na systémový účet.
function send_marketing_email($to_email, $to_name, $subject, $html_body, $business_user_id = null, $est_name = null) {
    global $smtp_user, $smtp_pass;
    $mail = new PHPMailer(true);
    try {
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;

        $customConfig = $business_user_id ? getEstablishmentSmtpConfig($business_user_id) : null;
        if ($customConfig) {
            try {
                applySmtpTransport($mail, $customConfig['server'], $customConfig['port'], $customConfig['user'], $customConfig['pass'], $customConfig['user'], $est_name ?: 'Rezervos');
                apply_test_email_override($mail, $to_email);
                $mail->send();
                return true;
            } catch (PHPMailerException $e) {
                error_log("Vlastný SMTP prevádzky zlyhal pri marketingovom e-maile (user_id={$business_user_id}), prepínam na predvolený účet: {$mail->ErrorInfo}");
            }
        }

        applySmtpTransport($mail, 'mail.usr.sk', 465, $smtp_user, $smtp_pass, $smtp_user, 'Rezervos');
        apply_test_email_override($mail, $to_email);
        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('Marketingový e-mail sa nepodarilo odoslať: ' . $mail->ErrorInfo);
        return false;
    }
}
