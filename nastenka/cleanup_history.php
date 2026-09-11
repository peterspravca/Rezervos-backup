<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();
$affected = $pdo->exec("UPDATE crm_notes SET note_type = 'system_silent' 
    WHERE note_type = 'system' 
    AND (content LIKE 'Vygenerovaný dokument%' 
    OR content LIKE 'Nahraný súbor%' 
    OR content LIKE 'AUTOMAT: %')");
echo "Updated $affected rows.";
unlink(__FILE__);
