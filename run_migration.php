<?php
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'migrate2026') { die('no access'); }

$conn = new mysqli('db1.usr.sk', 'volnekreslo.sk', 'bq!wL0K*zWH)XT]0', 'volnekreslosk');
if ($conn->connect_error) { die('DB error: ' . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$out = "=== Migration ===\n\n";

// Add columns one by one
$cols = array(
    "ALTER TABLE establishments ADD COLUMN subscription_tier VARCHAR(20) DEFAULT 'free'",
    "ALTER TABLE establishments ADD COLUMN subscription_period VARCHAR(20) DEFAULT 'monthly'",
    "ALTER TABLE establishments ADD COLUMN subscription_expires_at DATETIME DEFAULT NULL"
);

foreach ($cols as $sql) {
    $r = $conn->query($sql);
    if ($r) {
        $out .= "OK: column added\n";
    } else {
        $e = $conn->error;
        if (strpos($e, 'Duplicate') !== false || strpos($e, 'exists') !== false) {
            $out .= "INFO: column already exists\n";
        } else {
            $out .= "ERROR: " . $e . "\n";
        }
    }
}

// Sync from users to establishments
$r2 = $conn->query("UPDATE establishments e JOIN users u ON e.user_id = u.id SET e.subscription_tier = u.subscription_tier, e.subscription_period = u.subscription_period, e.subscription_expires_at = u.subscription_expires_at WHERE u.subscription_tier != 'free' AND u.subscription_tier IS NOT NULL");
if ($r2) {
    $out .= "OK: sync done, rows=" . $conn->affected_rows . "\n";
} else {
    $out .= "ERROR sync: " . $conn->error . "\n";
}

// Show result
$r3 = $conn->query("SELECT e.id, e.name, e.subscription_tier as et, u.subscription_tier as ut FROM establishments e JOIN users u ON e.user_id = u.id LIMIT 5");
$out .= "\n=== Check ===\n";
if ($r3) {
    while ($row = $r3->fetch_assoc()) {
        $out .= "id=" . $row['id'] . " name=" . $row['name'] . " est_tier=" . $row['et'] . " user_tier=" . $row['ut'] . "\n";
    }
}

$out .= "\n=== Done ===";
echo "<pre>" . htmlspecialchars($out) . "</pre>";
?>
