<?php
/**
 * Automatické odosielanie pozdravov (meniny/narodeniny/veľké sviatky) pre prevádzky,
 * ktoré si pre danú príležitosť zvolili režim 'auto' (viz includes/holidays_helper.php).
 * Volané z cron_auto_ratings.php spolu s ostatnými periodickými úlohami.
 */
require_once __DIR__ . '/holidays_helper.php';
require_once __DIR__ . '/marketing_send_helper.php';

function ensureOccasionCustomerLog($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS occasion_customer_send_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        occasion_key VARCHAR(30) NOT NULL,
        customer_id INT NOT NULL,
        sent_date DATE NOT NULL,
        UNIQUE KEY uniq_est_occ_cust_date (establishment_id, occasion_key, customer_id, sent_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function getDefaultOccasionMessage($occasion_key) {
    $defaults = [
        'nameday'     => "Milá / Milý {{NAME}},\n\nk dnešným meninám Vám prajeme všetko najlepšie!",
        'birthday'    => "Milá / Milý {{NAME}},\n\nk narodeninám Vám želáme všetko najlepšie!",
        'new_year'    => "Milá / Milý {{NAME}},\n\ndo nového roka Vám prajeme veľa zdravia, šťastia a spokojnosti.",
        'womens_day'  => "Milá {{NAME}},\n\npri príležitosti MDŽ Vám prajeme všetko najlepšie!",
        'easter'      => "Milá / Milý {{NAME}},\n\nprajeme Vám krásne a pokojné veľkonočné sviatky.",
        'labour_day'  => "Milá / Milý {{NAME}},\n\nprajeme Vám príjemný 1. máj.",
        'mothers_day' => "Milá {{NAME}},\n\npri príležitosti Dňa matiek Vám prajeme všetko najlepšie.",
        'fathers_day' => "Milý {{NAME}},\n\npri príležitosti Dňa otcov Vám prajeme všetko najlepšie.",
        'christmas'   => "Milá / Milý {{NAME}},\n\nprajeme Vám krásne a pokojné Vianoce.",
        'silvester'   => "Milá / Milý {{NAME}},\n\nprajeme Vám šťastný a úspešný nový rok!",
    ];
    return $defaults[$occasion_key] ?? "Milá / Milý {{NAME}},\n\nprajeme Vám všetko najlepšie!";
}

function getDefaultOccasionSubject($occasion_key, $label) {
    $defaults = [
        'nameday' => 'Všetko najlepšie k meninám!',
        'birthday' => 'Všetko najlepšie k narodeninám! 🎉',
    ];
    return $defaults[$occasion_key] ?? "$label — prajeme Vám všetko najlepšie!";
}

// Reálni zákazníci prevádzky (mali aspoň jednu rezerváciu) — rovnaký princíp ako api/marketing.php
function getEstablishmentRealClients($conn, $establishment_id) {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date DATE NULL");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS marketing_consent TINYINT(1) NOT NULL DEFAULT 0");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('female','male','other') NULL");
    // Automatické pozdravy (nezriedka s vloženým zľavovým kódom) sú marketingová komunikácia —
    // posielajú sa len zákazníkom, ktorí s tým pri rezervácii súhlasili
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.full_name AS name, u.email, u.birth_date, u.gender,
               (SELECT s.name FROM bookings b2 LEFT JOIN services s ON s.id = b2.service_id
                WHERE b2.customer_id = u.id AND b2.establishment_id = ? ORDER BY b2.booking_date DESC LIMIT 1) as last_service,
               (SELECT MAX(booking_date) FROM bookings b3 WHERE b3.customer_id = u.id AND b3.establishment_id = ?) as last_booking
        FROM users u
        JOIN bookings b ON b.customer_id = u.id
        WHERE b.establishment_id = ? AND u.email IS NOT NULL AND u.email != '' AND u.marketing_consent = 1
    ");
    $stmt->bind_param("iii", $establishment_id, $establishment_id, $establishment_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    return $rows;
}

// Vygeneruje jeden zdieľaný zľavový kód pre danú príležitosť/deň (max_uses = počet dnešných príjemcov)
function createOccasionVoucher($conn, $establishment_id, $occasion_key, $settings, $recipient_count) {
    $conn->query("CREATE TABLE IF NOT EXISTS vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        business_id INT NOT NULL,
        code VARCHAR(50) NOT NULL,
        discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(8,2) NOT NULL DEFAULT 0,
        valid_until DATE DEFAULT NULL,
        max_uses INT DEFAULT NULL,
        uses_count INT NOT NULL DEFAULT 0,
        description VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_business (business_id),
        UNIQUE KEY uniq_business_code (business_id, code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    do {
        $code = strtoupper($occasion_key) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $exists = $conn->query("SELECT id FROM vouchers WHERE business_id = " . (int)$establishment_id . " AND code = '" . $conn->real_escape_string($code) . "'")->num_rows > 0;
    } while ($exists);

    $valid_until = date('Y-m-d', strtotime('+' . (int)$settings['discount_validity_days'] . ' days'));
    $stmt = $conn->prepare("INSERT INTO vouchers (business_id, code, discount_type, discount_value, valid_until, max_uses, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $description = 'Automatický pozdrav — ' . $settings['label'] . ' (' . date('d.m.Y') . ')';
    $stmt->bind_param("issdsis", $establishment_id, $code, $settings['discount_type'], $settings['discount_value'], $valid_until, $recipient_count, $description);
    $stmt->execute();
    return $code;
}

function buildOccasionMessage($settings, $voucher_code) {
    $content = $settings['message_template'] ?: getDefaultOccasionMessage($settings['occasion_key']);
    if ($settings['reward_type'] === 'discount' && $voucher_code) {
        $discount_label = $settings['discount_type'] === 'percent' ? ((float)$settings['discount_value'] . '%') : (number_format((float)$settings['discount_value'], 2) . ' €');
        $content .= "\n\nAko darček máte od nás zľavu {$discount_label} — stačí pri rezervácii zadať kód {$voucher_code} (platný " . (int)$settings['discount_validity_days'] . " dní).";
    }
    return $content;
}

function processOccasionAutomation($conn) {
    ensureOccasionAutomationTable($conn);
    ensureOccasionCustomerLog($conn);

    $today = date('Y-m-d');
    $today_year = (int)date('Y');
    $root_url = defined('APP_ROOT_URL') ? APP_ROOT_URL : 'https://rezervos.eu';

    $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS marketing_email_sender ENUM('rezervos','own') NOT NULL DEFAULT 'rezervos'");
    $est_res = $conn->query("SELECT DISTINCT oa.establishment_id, e.name as est_name, e.user_id as business_user_id, e.marketing_email_sender FROM occasion_automation oa JOIN establishments e ON e.id = oa.establishment_id WHERE oa.mode = 'auto'");
    if (!$est_res) return;

    while ($est_row = $est_res->fetch_assoc()) {
        $establishment_id = (int)$est_row['establishment_id'];
        $est_name = $est_row['est_name'];
        // Prevádzka si sama vyberá, či pozdravy idú z jej vlastnej pripojenej schránky, alebo z nášho účtu
        $business_user_id = ($est_row['marketing_email_sender'] === 'own') ? (int)$est_row['business_user_id'] : null;
        $settings = getOccasionSettings($conn, $establishment_id);
        $clients = null; // lazy-loaded len ak treba

        // ── MENINY (denne, per zákazník podľa mena) ──
        if ($settings['nameday']['mode'] === 'auto') {
            $sk_file = __DIR__ . '/../libs/namedays_sk.json';
            $sk_today = '';
            if (file_exists($sk_file)) {
                $sk_data = json_decode(file_get_contents($sk_file), true) ?: [];
                $sk_today = $sk_data[date('m-d')] ?? '';
            }
            if ($sk_today) {
                if ($clients === null) $clients = getEstablishmentRealClients($conn, $establishment_id);
                $todays_names = array_map('trim', explode(',', $sk_today));
                $celebrants = array_filter($clients, function ($c) use ($todays_names) {
                    $first = trim(explode(' ', trim($c['name']))[0] ?? '');
                    foreach ($todays_names as $n) {
                        $n = trim(explode('/', $n)[0]);
                        if (mb_strtolower($first) === mb_strtolower($n)) return true;
                    }
                    return false;
                });
                sendOccasionCampaign($conn, $establishment_id, $est_name, $business_user_id, 'nameday', $settings['nameday'], array_values($celebrants), $root_url, $today);
            }
        }

        // ── NARODENINY (denne, per zákazník podľa birth_date) ──
        if ($settings['birthday']['mode'] === 'auto') {
            if ($clients === null) $clients = getEstablishmentRealClients($conn, $establishment_id);
            $today_md = date('m-d');
            $celebrants = array_filter($clients, fn($c) => !empty($c['birth_date']) && date('m-d', strtotime($c['birth_date'])) === $today_md);
            sendOccasionCampaign($conn, $establishment_id, $est_name, $business_user_id, 'birthday', $settings['birthday'], array_values($celebrants), $root_url, $today);
        }

        // ── VEĽKÉ SVIATKY (raz do roka, všetci zákazníci) ──
        foreach (getFixedOccasionDefinitions() as $key => $label) {
            if ($settings[$key]['mode'] !== 'auto') continue;
            if (getFixedOccasionDate($key, $today_year) !== $today) continue;

            $already_sent = $conn->query("SELECT id FROM occasion_send_log WHERE establishment_id = $establishment_id AND occasion_key = '" . $conn->real_escape_string($key) . "' AND occasion_year = $today_year")->num_rows > 0;
            if ($already_sent) continue;

            if ($clients === null) $clients = getEstablishmentRealClients($conn, $establishment_id);
            if (empty($clients)) continue;

            sendOccasionCampaign($conn, $establishment_id, $est_name, $business_user_id, $key, $settings[$key], $clients, $root_url, $today);

            $log = $conn->prepare("INSERT INTO occasion_send_log (establishment_id, occasion_key, occasion_year) VALUES (?, ?, ?)");
            $log->bind_param("isi", $establishment_id, $key, $today_year);
            $log->execute();
        }
    }
}

// Pošle pozdrav (+ prípadný zľavový kód) danému zoznamu príjemcov pre jednu príležitosť.
// $today sa použije len na dedup u per-zákaznícky logovaných príležitostí (meniny/narodeniny).
function sendOccasionCampaign($conn, $establishment_id, $est_name, $business_user_id, $occasion_key, $settings, $recipients, $root_url, $today) {
    if (empty($recipients)) return;

    $is_per_customer_log = in_array($occasion_key, ['nameday', 'birthday'], true);
    if ($is_per_customer_log) {
        $already = [];
        $check = $conn->prepare("SELECT customer_id FROM occasion_customer_send_log WHERE establishment_id = ? AND occasion_key = ? AND sent_date = ?");
        $check->bind_param("iss", $establishment_id, $occasion_key, $today);
        $check->execute();
        $res = $check->get_result();
        while ($r = $res->fetch_assoc()) { $already[(int)$r['customer_id']] = true; }
        $recipients = array_values(array_filter($recipients, fn($c) => empty($already[(int)$c['id']])));
        if (empty($recipients)) return;
    }

    $voucher_code = null;
    if ($settings['reward_type'] === 'discount') {
        $voucher_code = createOccasionVoucher($conn, $establishment_id, $occasion_key, $settings, count($recipients));
    }

    $subject = getDefaultOccasionSubject($occasion_key, $settings['label']);
    $content = buildOccasionMessage($settings, $voucher_code);

    $is_gendered = in_array($occasion_key, ['nameday', 'birthday'], true);

    foreach ($recipients as $c) {
        $personalized = apply_marketing_placeholders($content, $c['name'], $c['last_service'] ?? null, $c['last_booking'] ?? null);

        // Pri meninách/narodeninách sa (ak je nastavená) uprednostní šablóna podľa pohlavia zákazníka
        $tpl_id = $settings['template_id'] ?? 'none';
        if ($is_gendered) {
            $gender = $c['gender'] ?? null;
            if ($gender === 'female' && !empty($settings['template_id_female']) && $settings['template_id_female'] !== 'none') {
                $tpl_id = $settings['template_id_female'];
            } elseif ($gender === 'male' && !empty($settings['template_id_male']) && $settings['template_id_male'] !== 'none') {
                $tpl_id = $settings['template_id_male'];
            }
        }

        $html = wrap_marketing_template($tpl_id, $personalized, $c['name'], $est_name, $root_url, $conn, $establishment_id, $business_user_id, $c['id']);
        send_marketing_email($c['email'], $c['name'], $subject, $html, $business_user_id, $est_name);

        if ($is_per_customer_log) {
            $log = $conn->prepare("INSERT IGNORE INTO occasion_customer_send_log (establishment_id, occasion_key, customer_id, sent_date) VALUES (?, ?, ?, ?)");
            $log->bind_param("isis", $establishment_id, $occasion_key, $c['id'], $today);
            $log->execute();
        }
    }
}
