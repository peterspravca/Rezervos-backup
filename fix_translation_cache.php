<?php
// fix_translation_cache.php — jednorazovo vymaže poškodený/zlý preklad konkrétneho reťazca
// (napr. "Všetky prevádzky" -> zmiešaná latinka/cyrilika "Usi операції"), nech sa pri ďalšom
// behu cronu (cron_translate_static.php) preloží nanovo správne. Zmazať po použití.
require_once __DIR__ . '/config.php';

$targets = [
    'Všetky prevádzky',
];

$total = 0;
foreach ($targets as $text) {
    $hash = md5($text);
    $stmt = $pdo->prepare("DELETE FROM translation_cache WHERE original_hash = ?");
    $stmt->execute([$hash]);
    $count = $stmt->rowCount();
    $total += $count;
    echo "„$text“ (hash $hash): vymazaných $count záznamov\n";
}

echo "Spolu vymazaných: $total\n";
