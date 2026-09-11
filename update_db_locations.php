<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes
require_once 'config.php';

echo "Vytváram tabuľku locations...<br>";

try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS `locations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `country` VARCHAR(2) NOT NULL,
            `zip_code` VARCHAR(20) NOT NULL,
            `city_name` VARCHAR(150) NOT NULL,
            `admin_name` VARCHAR(150),
            `lat` DECIMAL(10, 8) NOT NULL,
            `lon` DECIMAL(11, 8) NOT NULL,
            INDEX `idx_zip` (`zip_code`),
            INDEX `idx_city` (`city_name`),
            INDEX `idx_country` (`country`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Vyčistíme tabuľku, ak už existuje (aby sme mali vždy čerstvé dáta)
    $conn->query("TRUNCATE TABLE `locations`");
    
    echo "Tabuľka pripravená.<br>";

    $countries = ['SK', 'CZ'];
    $stmt = $conn->prepare("INSERT INTO `locations` (`country`, `zip_code`, `city_name`, `admin_name`, `lat`, `lon`) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($countries as $cc) {
        echo "Sťahujem dáta pre $cc...<br>";
        
        $url = "http://download.geonames.org/export/zip/{$cc}.zip";
        $zipFile = __DIR__ . "/{$cc}.zip";
        
        $ch = curl_init($url);
        $fp = fopen($zipFile, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            $txtFile = $zip->getNameIndex(0);
            $content = $zip->getFromIndex(0);
            echo "Obsah súboru má dĺžku: " . strlen($content) . "<br>";
            $zip->close();
            unlink($zipFile); // zmazať zip archív
            
            $lines = explode("\n", $content);
            echo "Počet riadkov: " . count($lines) . "<br>";
            $inserted = 0;
            
            // Pripravíme transakciu pre rýchlejší insert
            $conn->begin_transaction();
            
            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                $cols = explode("\t", $line);
                
                // GeoNames TSV formát:
                // 0: country code, 1: postal code, 2: place name, 3: admin name1, ... 9: lat, 10: lon
                if (count($cols) >= 11) {
                    $country = trim($cols[0]);
                    $zipCode = trim($cols[1]);
                    $cityName = trim($cols[2]);
                    $adminName = trim($cols[3]); // Kraj/Okres
                    $lat = trim($cols[9]);
                    $lon = trim($cols[10]);
                    
                    if ($lat && $lon) {
                        $stmt->bind_param("ssssdd", $country, $zipCode, $cityName, $adminName, $lat, $lon);
                        if ($stmt->execute()) {
                            $inserted++;
                        } else {
                            echo "Error on row: " . $stmt->error . "<br>";
                        }
                    }
                }
            }
            $conn->commit();
            echo "Krajina $cc: úspešne vložených $inserted záznamov.<br>";
        } else {
            echo "Chyba pri rozbaľovaní ZIP pre $cc.<br>";
        }
    }
    
    echo "Hotovo! Lokality boli naimportované.";
    
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage();
}
?>
