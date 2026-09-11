<?php
/**
 * Automatický prekladač PEVNÝCH UI TEXTOV z kódu (t('...') volania) — beží na pozadí cez cron-job.org.
 * Rovnaký princíp ako aveino/cron_translate_all.php (skenuje zdrojový kód, nie databázu), len namiesto
 * Google Translate používa Groq/z.ai (translator_helper.php::t()), rovnako ako cron_translate_content.php.
 */

set_time_limit(0);
ini_set('memory_limit', '512M');
ignore_user_abort(true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/translator_helper.php';

$secret_token = 'Neviem0950400203';
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['run']) || !isset($_GET['token']) || $_GET['token'] !== $secret_token) {
        die("Pristup odmietnuty. Zly token.");
    }
}

t_migrate($pdo);

$start_time = microtime(true);

echo "<pre>\n";
echo "==============================================\n";
echo "   REZERVOS AUTO-PREKLADAC STATICKYCH TEXTOV (CRON)\n";
echo "==============================================\n\n";
@ob_flush(); @flush();

$languages = ['cz', 'en', 'de', 'pl', 'hu', 'ua'];
$dir = __DIR__;

// Priečinky, ktoré neskenujeme — referenčné/pomocné projekty a vendor kód
$exclude_dirs = [
    '/vendor/', '/node_modules/', '/logs/', '/cache/', '/.git/',
    '/aveino/', '/zaloha/', '/nastenka/', '/Zarobsi/', '/scratch/',
];

function get_all_php_files_static($dir, $exclude_dirs) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $path = str_replace('\\', '/', $file->getPathname());
            $skip = false;
            foreach ($exclude_dirs as $ex) {
                if (strpos($path, $ex) !== false) { $skip = true; break; }
            }
            if (!$skip) $files[] = $path;
        }
    }
    return $files;
}

$files = get_all_php_files_static($dir, $exclude_dirs);
$unique_strings = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    preg_match_all("/t\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)/", $content, $matches1);
    preg_match_all('/t\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*\)/', $content, $matches2);
    foreach ($matches1[1] as $str) {
        $str = str_replace(["\\'", "\\\\"], ["'", "\\"], $str);
        $unique_strings[$str] = true;
    }
    foreach ($matches2[1] as $str) {
        $unique_strings[$str] = true;
    }
}

$texts = array_keys($unique_strings);
$texts = array_filter($texts, function ($t) {
    $t = trim($t);
    return $t !== '' && mb_strlen($t) < 5000;
});

$total_found = count($texts);
$new_translations = 0;
$api_offline_global = false;
$time_limit_hit = false;

echo "Nájdených unikátnych textov v kóde: $total_found\n";
echo "Počet podporovaných jazykov: " . count($languages) . "\n";
echo "----------------------------------------------\n";
@ob_flush(); @flush();

// Prednačítanie existujúcej cache pre rýchlu kontrolu
$existing_cache = [];
try {
    $stmt = $pdo->query("SELECT original_hash, lang FROM translation_cache");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_cache[$row['lang']][$row['original_hash']] = true;
    }
} catch (Exception $e) {
    die("Chyba databázy: " . $e->getMessage());
}

foreach ($languages as $lang_code) {
    if ($api_offline_global || $time_limit_hit) break;
    $lang_count = 0;

    foreach ($texts as $text) {
        $hash = md5($text);
        if (isset($existing_cache[$lang_code][$hash])) continue;

        if (microtime(true) - $start_time > 12) {
            echo "--> UPOZORNENIE: Dosiahnutý bezpečný časový limit (12s) pre cron-job.org. Prerušujem beh.\n";
            $time_limit_hit = true;
            break 2;
        }

        global $api_offline, $t_last_debug;
        $api_offline = false;
        $t_last_debug = null;
        $translated = t($text, $lang_code);

        if (!empty($api_offline)) {
            echo "--> UPOZORNENIE: Prekladové API je nedostupné. Detail: " . ($t_last_debug ?? 'žiadny debug') . "\n";
            $api_offline_global = true;
            break 2;
        }

        $existing_cache[$lang_code][$hash] = true;
        $new_translations++;
        $lang_count++;
        echo "[$lang_code] Preložené: '$text' -> '$translated'\n";
        @ob_flush(); @flush();

        usleep(300000); // 0,3s odstup — Groq (na rozdiel od Google Translate) rýchle opakované volania zvláda bez problémov
    }

    if ($lang_count > 0) {
        echo "Dokončený jazyk $lang_code (nových prekladov: $lang_count)\n";
    } elseif (!$api_offline_global && !$time_limit_hit) {
        echo "Jazyk $lang_code je už kompletne preložený, pokračujem ďalším jazykom.\n";
    }
}

$duration = round(microtime(true) - $start_time, 2);
echo "----------------------------------------------\n";
echo "HOTOVO.\n";
echo "Trvanie: $duration sekúnd\n";
echo "Nových prekladov pridaných do DB: $new_translations\n";
echo "</pre>\n";
