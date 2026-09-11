<?php
/**
 * ⚠️ TESTOVACÍ REŽIM — pozri TEST_EMAIL_OVERRIDE v config.php.
 * Volať tesne pred $mail->send() vo všetkých miestach, kde sa posiela e-mail (rôzne súbory
 * používajú rôzne kópie PHPMailer knižnice, preto tu zámerne netypujeme parameter na konkrétnu triedu).
 */
if (!function_exists('apply_test_email_override')) {
    function apply_test_email_override($mail, $original_email) {
        if (!defined('TEST_EMAIL_OVERRIDE') || empty(TEST_EMAIL_OVERRIDE)) return;
        if (strcasecmp($original_email, TEST_EMAIL_OVERRIDE) === 0) return; // už ide priamo na testovaciu adresu
        $mail->clearAddresses();
        $mail->addAddress(TEST_EMAIL_OVERRIDE, 'Rezervos test');
        $mail->Subject = '[TEST, pôvodne pre ' . $original_email . '] ' . $mail->Subject;
    }
}
