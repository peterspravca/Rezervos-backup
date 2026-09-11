<?php
$databases = [
    [
        'host' => 'db1.usr.sk',
        'name' => 'volnekreslosk',
        'user' => 'volnekreslo.sk',
        'pass' => 'bq!wL0K*zWH)XT]0',
        'admin_email' => 'info@volnekreslo.sk',
        'admin_pass' => '2dDUEzsWbm6w',
        'admin_name' => 'Admin SK'
    ],
    [
        'host' => 'db1.usr.sk',
        'name' => 'volnekreslocz',
        'user' => 'volnekreslo.cz',
        'pass' => 'rVslYqLz/DA19MWd',
        'admin_email' => 'info@volnekreslo.cz',
        'admin_pass' => 'zNa68uA7tspr',
        'admin_name' => 'Admin CZ'
    ]
];

foreach ($databases as $db) {
    try {
        $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['name'] . ";charset=utf8mb4", $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                verification_code VARCHAR(20) DEFAULT NULL,
                is_verified TINYINT(1) DEFAULT 0,
                role VARCHAR(50) DEFAULT 'customer',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        $hash = password_hash($db['admin_pass'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$db['admin_email']]);
        if ($stmt->fetch()) {
            $update = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin', is_verified = 1 WHERE email = ?");
            $update->execute([$hash, $db['admin_email']]);
            echo "Updated admin: " . $db['admin_email'] . "\n";
        } else {
            $insert = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, is_verified) VALUES (?, ?, ?, 'admin', 1)");
            $insert->execute([$db['admin_name'], $db['admin_email'], $hash]);
            echo "Created admin: " . $db['admin_email'] . "\n";
        }
    } catch (PDOException $e) {
        echo "Error on " . $db['name'] . ": " . $e->getMessage() . "\n";
    }
}
?>
