<?php
require_once 'config.php';

try {
    // Check if column referral_code exists
    $result = $conn->query("SHOW COLUMNS FROM `users` LIKE 'referral_code'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE `users` ADD `referral_code` VARCHAR(50) DEFAULT NULL AFTER `user_role`";
        if ($conn->query($sql) === TRUE) {
            echo "Column referral_code added successfully.<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    } else {
        echo "Column referral_code already exists.<br>";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
}

// Upload via script:
// FTP this file to the server and then execute it via a browser or HTTP request.
echo "Done.";
?>
