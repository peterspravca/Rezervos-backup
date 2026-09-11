<?php
/**
 * Definície "veľkých sviatkov" pre automatizáciu pozdravov (mimo meniny/narodeniny,
 * ktoré sú per-zákazník a riešia sa samostatne v api/marketing.php / api/auto_ratings_helper.php).
 */

// Tieto dni v slovenskom kalendári nemajú (overiteľne) pridelené žiadne osobné meno, len štátny
// sviatok — na tieto dni sa nemá zobrazovať "Meniny dnes" ani ponúkať blahoželanie. (Iné dni, ktoré
// v našich dátach kedysi mylne obsahovali len text sviatku namiesto mena — 8.5., 29.8., 1.9., 17.11.,
// 24.12. Adam/Eva, 26.12. Štefan — už boli opravené priamo v libs/namedays_sk.json a namedays.json.)
function getNonNameNamedayKeys() {
    return ['01-01', '05-01', '12-25'];
}

function getFixedOccasionDefinitions() {
    return [
        'new_year'    => 'Nový rok',
        'womens_day'  => 'MDŽ',
        'easter'      => 'Veľká noc',
        'labour_day'  => '1. máj',
        'mothers_day' => 'Deň matiek',
        'fathers_day' => 'Deň otcov',
        'christmas'   => 'Vianoce',
        'silvester'   => 'Silvester',
    ];
}

// Všetky príležitosti vrátane meninín/narodenín — pre nastavenia automatizácie
function getAllOccasionDefinitions() {
    return array_merge(
        ['nameday' => 'Meniny', 'birthday' => 'Narodeniny'],
        getFixedOccasionDefinitions()
    );
}

// Dátum Veľkonočnej nedele — Meeus/Jones/Butcher algoritmus (funguje bez ext-calendar, ktoré
// nemusí byť na zdieľanom hostingu vždy povolené)
function getEasterSundayDate($year) {
    if (function_exists('easter_days')) {
        try {
            $days = easter_days($year);
            $dt = new DateTime(sprintf('%04d-03-21', $year));
            $dt->modify("+{$days} days");
            return $dt->format('Y-m-d');
        } catch (Exception $e) { /* spadni na algoritmus nižšie */ }
    }
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

function getNthSundayOfMonth($year, $month, $n) {
    $dt = new DateTime(sprintf('%04d-%02d-01', $year, $month));
    $dow = (int)$dt->format('N'); // 1=Po..7=Ne
    $firstSunday = 1 + ((7 - $dow) % 7);
    $day = $firstSunday + ($n - 1) * 7;
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

// Vráti dátum (Y-m-d) danej pevnej/pohyblivej príležitosti pre daný rok, alebo null pre meniny/narodeniny
function getFixedOccasionDate($key, $year) {
    switch ($key) {
        case 'new_year':   return sprintf('%04d-01-01', $year);
        case 'womens_day': return sprintf('%04d-03-08', $year);
        case 'labour_day': return sprintf('%04d-05-01', $year);
        case 'christmas':  return sprintf('%04d-12-24', $year);
        case 'silvester':  return sprintf('%04d-12-31', $year);
        case 'easter':
            return getEasterSundayDate($year);
        case 'mothers_day': return getNthSundayOfMonth($year, 5, 2);
        case 'fathers_day': return getNthSundayOfMonth($year, 6, 3);
        default: return null;
    }
}

function ensureOccasionAutomationTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS occasion_automation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        occasion_key VARCHAR(30) NOT NULL,
        mode ENUM('manual','remind','auto') NOT NULL DEFAULT 'manual',
        lead_days INT NOT NULL DEFAULT 1,
        reward_type ENUM('greeting','discount') NOT NULL DEFAULT 'greeting',
        discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(8,2) NOT NULL DEFAULT 10,
        discount_validity_days INT NOT NULL DEFAULT 14,
        message_template TEXT DEFAULT NULL,
        template_id VARCHAR(30) DEFAULT NULL,
        template_id_female VARCHAR(30) DEFAULT NULL,
        template_id_male VARCHAR(30) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_est_occasion (establishment_id, occasion_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conn->query("ALTER TABLE occasion_automation ADD COLUMN IF NOT EXISTS template_id VARCHAR(30) DEFAULT NULL");
    $conn->query("ALTER TABLE occasion_automation ADD COLUMN IF NOT EXISTS template_id_female VARCHAR(30) DEFAULT NULL");
    $conn->query("ALTER TABLE occasion_automation ADD COLUMN IF NOT EXISTS template_id_male VARCHAR(30) DEFAULT NULL");

    $conn->query("CREATE TABLE IF NOT EXISTS occasion_send_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        occasion_key VARCHAR(30) NOT NULL,
        occasion_year INT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_est_occasion_year (establishment_id, occasion_key, occasion_year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Vráti nastavenia (mode/lead_days/reward...) danej prevádzky pre všetky príležitosti, doplnené o defaulty
function getOccasionSettings($conn, $establishment_id) {
    ensureOccasionAutomationTable($conn);
    $defs = getAllOccasionDefinitions();
    $settings = [];
    foreach ($defs as $key => $label) {
        $settings[$key] = [
            'occasion_key' => $key, 'label' => $label, 'mode' => 'manual', 'lead_days' => 1,
            'reward_type' => 'greeting', 'discount_type' => 'percent', 'discount_value' => 10,
            'discount_validity_days' => 14, 'message_template' => null,
            'template_id' => 'none', 'template_id_female' => 'none', 'template_id_male' => 'none',
        ];
    }
    $stmt = $conn->prepare("SELECT * FROM occasion_automation WHERE establishment_id = ?");
    $stmt->bind_param("i", $establishment_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $key = $row['occasion_key'];
        if (isset($settings[$key])) {
            $settings[$key] = array_merge($settings[$key], $row);
            $settings[$key]['label'] = $defs[$key];
            // NULL v DB (šablóna nikdy nebola nastavená) znamená "žiadna" — nie prázdny select
            foreach (['template_id', 'template_id_female', 'template_id_male'] as $tf) {
                if ($settings[$key][$tf] === null) { $settings[$key][$tf] = 'none'; }
            }
        }
    }
    return $settings;
}

// Príležitosti v režime 'remind', ktoré pripadajú na dnešok + lead_days dní vopred (vrátane dnešného dňa)
function getUpcomingReminders($conn, $establishment_id) {
    $settings = getOccasionSettings($conn, $establishment_id);
    $today = new DateTime('today');
    $reminders = [];
    foreach (getFixedOccasionDefinitions() as $key => $label) {
        $s = $settings[$key];
        if ($s['mode'] !== 'remind') continue;
        $year = (int)$today->format('Y');
        $date_str = getFixedOccasionDate($key, $year);
        $date = new DateTime($date_str);
        if ($date < $today) { $date_str = getFixedOccasionDate($key, $year + 1); $date = new DateTime($date_str); }
        $days_until = (int)$today->diff($date)->format('%a');
        if ($days_until <= (int)$s['lead_days']) {
            $reminders[] = ['occasion_key' => $key, 'label' => $label, 'date' => $date_str, 'days_until' => $days_until];
        }
    }
    return $reminders;
}
