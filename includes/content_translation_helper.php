<?php
// includes/content_translation_helper.php
// Preklady obsahu z DB (názvy/popisy prevádzok, inzeráty) — oddelené od translator_helper.php,
// ktorý prekladá iba pevné UI texty. Napĺňa/aktualizuje ich cron_translate_content.php.

function ct_migrate($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS content_translations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        entity_type VARCHAR(32) NOT NULL,
        entity_id INT UNSIGNED NOT NULL,
        field VARCHAR(32) NOT NULL,
        lang VARCHAR(8) NOT NULL,
        source_hash CHAR(32) NOT NULL,
        translated_text TEXT,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_entity_field_lang (entity_type, entity_id, field, lang),
        INDEX idx_lookup (entity_type, entity_id, lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Vráti preložený text pre daný entity/field, ak existuje platný preklad pre aktuálny jazyk session.
 * Ak preklad neexistuje (alebo sa pôvodný text zmenil), vráti pôvodný text.
 */
function ct_get($conn, $entity_type, $entity_id, $field, $original_text) {
    $target = $_SESSION['lang'] ?? 'sk';
    if ($target === 'sk' || empty(trim((string)$original_text))) {
        return $original_text;
    }
    ct_migrate($conn);
    $hash = md5($original_text);
    $stmt = $conn->prepare("SELECT translated_text FROM content_translations WHERE entity_type=? AND entity_id=? AND field=? AND lang=? AND source_hash=? LIMIT 1");
    $stmt->bind_param("sisss", $entity_type, $entity_id, $field, $target, $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['translated_text'] : $original_text;
}
