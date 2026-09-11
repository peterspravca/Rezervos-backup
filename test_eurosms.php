<?php
/**
 * Rýchly test pripojenia na EuroSMS API.
 * Spusti v prehliadači: https://rezervos.eu/test_eurosms.php?to=0903622237&text=Test
 * (alebo cez php test_eurosms.php v termináli s hodnotami nižšie).
 *
 * Kým je v config.php EUROSMS_TEST_MODE = true, SMS sa reálne NEPOŠLE ani neúčtuje —
 * len sa overí, že formát a podpis požiadavky EuroSMS akceptuje.
 */
require_once __DIR__ . '/includes/eurosms.php';

header('Content-Type: text/plain; charset=utf-8');

if (empty(EUROSMS_IID) || empty(EUROSMS_KEY)) {
    echo "CHYBA: V config.php nie sú vyplnené EUROSMS_IID a EUROSMS_KEY.\n";
    echo "Doplň ich (Integračné ID a Kľúč z eurosms.com -> Nastavenia -> SMS API) a skús znova.\n";
    exit;
}

$to = $_GET['to'] ?? $_POST['to'] ?? '0903622237';
$text = $_GET['text'] ?? $_POST['text'] ?? 'Testovacia sprava z Rezervos';
$sender = $_GET['sender'] ?? $_POST['sender'] ?? 'REZERVOS';
$country = $_GET['country'] ?? $_POST['country'] ?? 'SK';

echo "Režim: " . (EUROSMS_TEST_MODE ? "TEST (nič sa reálne nepošle ani neúčtuje)" : "OSTRÝ (reálne odošle a strhne kredit z EuroSMS účtu!)") . "\n";
echo "Odosielateľ: $sender\n";
echo "Príjemca (vstup): $to (krajina: $country)\n";
echo "Text: $text\n";
echo str_repeat('-', 50) . "\n";

$result = eurosms_send_sms($to, $text, $sender, $country);

echo "Výsledok:\n";
print_r($result);
