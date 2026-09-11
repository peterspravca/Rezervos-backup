<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';

// Assuming config.php has $host, $dbname, $username, $password
// Let's parse config.php to get credentials for mysqldump
$config_content = file_get_contents('includes/config.php');
preg_match("/'host'\s*=>\s*'([^']+)'/", $config_content, $host_match);
preg_match("/'dbname'\s*=>\s*'([^']+)'/", $config_content, $db_match);
preg_match("/'username'\s*=>\s*'([^']+)'/", $config_content, $user_match);
preg_match("/'password'\s*=>\s*'([^']+)'/", $config_content, $pass_match);

$db_host = $host_match[1] ?? 'localhost';
$db_name = $db_match[1] ?? 'volnekreslo_sk';
$db_user = $user_match[1] ?? 'volnekreslo_sk';
$db_pass = $pass_match[1] ?? 'Jedoh1airif6E';

// We just know the credentials from previous context:
$db_host = 'localhost';
$db_name = 'volnekreslo_sk';
$db_user = 'volnekreslo_sk';
$db_pass = 'Jedoh1airif6E';

$output = [];
$return_var = 0;
exec("mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} > database_dump.sql 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo "SUCCESS\n";
} else {
    echo "FAILED\n";
    echo implode("\n", $output);
}
