<?php
/**
 * Lokálne generovanie platobných QR kódov — zámerne bez volania externej URL
 * (napr. api.qrserver.com), lebo takéto požiadavky na tretiu stranu blokujú
 * adblockery a privacy rozšírenia (uBlock, Brave Shields) a zákazník by
 * QR kód vôbec nevidel.
 */
// Stará knižnica phpqrcode hlási už pri načítaní neškodné E_DEPRECATED upozornenia
// (poradie parametrov vo funkciách) kvôli novšiemu PHP — potlačené len pre toto načítanie.
$__qr_prev_level = error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/phpqrcode/qrlib.php';
error_reporting($__qr_prev_level);
unset($__qr_prev_level);

// Skutočný slovenský Pay by Square formát (vyžaduje LZMA kompresiu + Base32Hex kódovanie,
// obyčajný text ako pri českom SPAYD nestačí — bez toho by ho banková appka nerozpoznala).
require_once __DIR__ . '/paybysquare/ValidationException.php';
require_once __DIR__ . '/paybysquare/Base32Hex.php';
require_once __DIR__ . '/paybysquare/Lzma/RangeEncoder.php';
require_once __DIR__ . '/paybysquare/Lzma/Lzma1Encoder.php';
require_once __DIR__ . '/paybysquare/Payment.php';
require_once __DIR__ . '/paybysquare/PayBySquare.php';

function generate_pay_by_square_payload($iban, $amount, $beneficiaryName, $note = '', $variableSymbol = '') {
    $payment = \PayBySquare\Payment::fromArray([
        'amount' => (float)$amount,
        'iban' => $iban,
        'beneficiaryName' => $beneficiaryName,
        'currency' => 'EUR',
        'variableSymbol' => $variableSymbol,
        'note' => $note,
    ]);
    return \PayBySquare\PayBySquare::generatePayload($payment);
}

function generate_qr_png_bytes($text, $size = 6, $margin = 2) {
    if (!function_exists('imagecreate')) {
        error_log('QR kód sa nedá vygenerovať — chýba PHP rozšírenie GD na serveri.');
        return '';
    }

    // Stará knižnica phpqrcode hlási na PHP 8.4 neškodné E_DEPRECATED upozornenia
    // (poradie parametrov, dynamic properties) — potlačíme ich len tu, nech nezamoria
    // výstup JSON API, ale skutočné chyby (E_ERROR a pod.) sa stále zachytia nižšie.
    $prev_level = error_reporting(E_ALL & ~E_DEPRECATED);
    try {
        $tmp_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        QRcode::png($text, $tmp_file, QR_ECLEVEL_M, $size, $margin);
        $png_data = @file_get_contents($tmp_file);
        @unlink($tmp_file);
        return $png_data === false ? '' : $png_data;
    } catch (Throwable $e) {
        error_log('Generovanie QR kódu zlyhalo: ' . $e->getMessage());
        return '';
    } finally {
        error_reporting($prev_level);
    }
}

// Pre zobrazenie v prehliadači (nie v e-maile — tam sa používa CID embed, lebo Gmail a iné klienty
// base64 obrázky v e-mailoch blokujú).
function generate_qr_data_uri($text, $size = 6, $margin = 2) {
    $png_data = generate_qr_png_bytes($text, $size, $margin);
    if ($png_data === '') return '';
    return 'data:image/png;base64,' . base64_encode($png_data);
}
