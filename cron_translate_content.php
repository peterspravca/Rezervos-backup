<?php
/**
 * Automatický prekladač OBSAHU z databázy (prevádzky, inzeráty) — beží na pozadí cez cron-job.org.
 * Na rozdiel od aveino/cron_translate_all.php (ktorý prekladá iba pevné texty t('...') v kóde),
 * tento skript ťahá reálne dáta z tabuliek establishments/classifieds a prekladá ich do content_translations.
 */

set_time_limit(0);
ini_set('memory_limit', '512M');
ignore_user_abort(true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/content_translation_helper.php';
require_once __DIR__ . '/translator_helper.php'; // groq_translate_text()

// Ochrana pri spustení cez prehliadač/cron-job.org (rovnaký token ako pri aveino)
$secret_token = 'Neviem0950400203';
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['run']) || !isset($_GET['token']) || $_GET['token'] !== $secret_token) {
        die("Pristup odmietnuty. Zly token.");
    }
}

ct_migrate($conn);

$start_time = microtime(true);

echo "<pre>\n";
echo "==============================================\n";
echo "   REZERVOS AUTO-PREKLADAC OBSAHU (CRON)\n";
echo "==============================================\n\n";
@ob_flush(); @flush();

// SK sa preskakuje, prekladáme do všetkých ostatných podporovaných jazykov
$languages = ['cz', 'en', 'de', 'pl', 'hu', 'ua'];

$targets = [
    'establishment' => ['table' => 'establishments', 'fields' => ['name', 'description']],
    'classified'     => ['table' => 'classifieds',    'fields' => ['title', 'description']],
];

function ct_translate_text($text, $target_lang, &$api_offline, &$fail_debug = null) {
    // Groq AI namiesto Google Translate — to na tomto hostingu naráža na HTTP 429 limit.
    $translated = groq_translate_text($text, $target_lang, $fail_debug);
    if ($translated === null) {
        $api_offline = true;
        return $text;
    }
    return $translated;
}

$api_offline = false;
$time_limit_hit = false;
$new_translations = 0;

foreach ($languages as $lang_code) {
    if ($api_offline || $time_limit_hit) break;

    echo "Prekladám v jazyku: $lang_code...\n";
    @ob_flush(); @flush();
    $lang_count = 0;

    foreach ($targets as $entity_type => $cfg) {
        if ($api_offline || $time_limit_hit) break;

        $rows = $conn->query("SELECT id, " . implode(',', $cfg['fields']) . " FROM `{$cfg['table']}`");
        if (!$rows) continue;

        while ($row = $rows->fetch_assoc()) {
            foreach ($cfg['fields'] as $field) {
                $source = trim((string)($row[$field] ?? ''));
                if ($source === '') continue;
                $hash = md5($source);

                $chk = $conn->prepare("SELECT id FROM content_translations WHERE entity_type=? AND entity_id=? AND field=? AND lang=? AND source_hash=? LIMIT 1");
                $chk->bind_param("sisss", $entity_type, $row['id'], $field, $lang_code, $hash);
                $chk->execute();
                if ($chk->get_result()->fetch_assoc()) {
                    continue; // už preložené a nezmenené, netreba znova
                }

                // Jeden preklad môže v najhoršom prípade čakať až 14s (10s Groq timeout + 4s
                // záložný z.ai timeout), takže potrebujeme dosť rezervy, aby celý beh spoľahlivo
                // skončil pod ~30s limitom cron-job.org.
                if (microtime(true) - $start_time > 12) {
                    echo "--> UPOZORNENIE: Dosiahnutý bezpečný časový limit (12s) pre cron-job.org. Prerušujem beh.\n";
                    $time_limit_hit = true;
                    break 3;
                }

                $translated = ct_translate_text($source, $lang_code, $api_offline, $fail_debug);
                if ($api_offline) {
                    echo "--> UPOZORNENIE: Prekladové API je nedostupné. Detail: " . ($fail_debug ?? 'žiadny debug') . "\n";
                    break 3;
                }

                $ins = $conn->prepare("INSERT INTO content_translations (entity_type, entity_id, field, lang, source_hash, translated_text) VALUES (?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE source_hash = VALUES(source_hash), translated_text = VALUES(translated_text)");
                $ins->bind_param("sissss", $entity_type, $row['id'], $field, $lang_code, $hash, $translated);
                $ins->execute();

                $new_translations++;
                $lang_count++;
                echo "[$lang_code] $entity_type #{$row['id']} ($field): preložené\n";
                @ob_flush(); @flush();

                usleep(300000); // 0,3s odstup — Groq (na rozdiel od Google Translate) rýchle opakované volania zvláda bez problémov
            }
        }
    }

    if ($lang_count > 0) {
        echo "Dokončený jazyk $lang_code (nových prekladov: $lang_count)\n";
    } elseif (!$api_offline && !$time_limit_hit) {
        echo "Jazyk $lang_code je už kompletne preložený, pokračujem ďalším jazykom.\n";
    }
}

$duration = round(microtime(true) - $start_time, 2);
echo "----------------------------------------------\n";
echo "HOTOVO.\n";
echo "Trvanie: $duration sekúnd\n";
echo "Nových prekladov pridaných do DB: $new_translations\n";
echo "</pre>\n";
