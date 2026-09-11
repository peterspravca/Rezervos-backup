<?php
$files = glob("C:/Antigravity/nastenka2/*_generator.php");
foreach ($files as $file) {
    if (basename($file) !== "admin_generator.php" && basename($file) !== "invoice_generator.php") {
        $str = file_get_contents($file);
        // Reverse the double conversion
        $bytes = iconv("UTF-8", "Windows-1250", $str);
        if ($bytes !== false) {
            file_put_contents($file, $bytes);
            echo "Fixed: $file\n";
        } else {
            echo "Failed: $file\n";
        }
    }
}
