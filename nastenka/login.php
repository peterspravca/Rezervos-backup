<?php
// Standalone login – žiadne externé závislosti pre testovanie
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB config inline
$db_host = 'db1.usr.sk';
$db_user = 'vueto.sk';
$db_pass = 'KHfgSjbar(819nNE';
$db_name = 'vueto';

$error = '';
$success = '';

// Session štart
if (session_status() === PHP_SESSION_NONE) {
    session_name('vueto_crm');
    session_start();
}

// Ak už prihlásený
if (!empty($_SESSION['crm_user_id'])) {
    header('Location: index.php');
    exit;
}

// Spracovanie prihlásenia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT * FROM crm_users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['crm_user_id']   = $user['id'];
            $_SESSION['crm_username']  = $user['username'];
            $_SESSION['crm_full_name'] = $user['full_name'];
            $_SESSION['crm_role']      = $user['role'];
            $_SESSION['crm_last_activity'] = time();
            
            $pdo->prepare("UPDATE crm_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Nesprávne meno alebo heslo.';
        }
    } catch (Exception $e) {
        $error = 'Chyba databázy: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Prihlásenie – VUETO CRM</title>
<link rel="apple-touch-icon" sizes="180x180" href="/nastenka/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/nastenka/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/nastenka/favicon/favicon-16x16.png">
<link rel="manifest" href="/nastenka/favicon/site.webmanifest">
<link rel="shortcut icon" href="/nastenka/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #0f1117; --card: #1a1d27; --border: #2a2e3f;
    --accent: #6366f1; --accent2: #818cf8;
    --text: #f0f2ff; --muted: #636b99;
    --green: #10b981; --red: #ef4444;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Outfit', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
}
.wrap { width: 100%; max-width: 400px; padding: 1rem; }
.card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.logo { text-align: center; margin-bottom: 2rem; }
.logo-icon {
    width: 64px; height: 64px; border-radius: 16px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 28px; margin-bottom: 1rem;
}
.logo h1 { font-size: 1.5rem; font-weight: 800; }
.logo p  { color: var(--muted); font-size: .85rem; margin-top: .25rem; }
.form-group { margin-bottom: 1.25rem; }
label {
    display: block; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em;
    color: var(--muted); margin-bottom: .5rem;
}
input[type=text], input[type=password] {
    width: 100%; padding: .75rem 1rem;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 10px; color: var(--text);
    font-family: inherit; font-size: .9rem;
    transition: border-color .2s, box-shadow .2s;
}
input:focus {
    outline: none; border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(99,102,241,.2);
}
.btn {
    width: 100%; height: 48px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border: none; border-radius: 12px; color: #fff;
    font-family: inherit; font-size: 1rem; font-weight: 700;
    cursor: pointer; transition: opacity .2s, transform .1s;
    margin-top: .25rem;
    display: inline-flex; align-items: center; justify-content: center;
}
.btn:hover { opacity: .9; transform: translateY(-1px); }
.alert {
    padding: .75rem 1rem; border-radius: 10px;
    font-size: .85rem; margin-bottom: 1.25rem;
}
.alert-error {
    background: rgba(239,68,68,.15);
    border: 1px solid rgba(239,68,68,.3);
    color: #fca5a5;
}
footer {
    text-align: center; color: var(--muted); font-size: .72rem;
    margin-top: 1.5rem;
}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="logo">
            <div class="logo-icon" style="overflow: hidden; padding: 6px;">
                <img src="/nastenka/vueto_logo.png" alt="VUETO" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1>VUETO CRM</h1>
            <p>Interná nástenka tímu</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <div class="form-group">
                <label for="username">Používateľské meno</label>
                <input type="text" id="username" name="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Heslo</label>
                <input type="password" id="password" name="password"
                    autocomplete="current-password" required>
            </div>
            <button class="btn" type="submit">Prihlásiť sa →</button>
        </form>
    </div>
    <footer>© 2026 VUETO – Prístup len pre zamestnancov</footer>
</div>
</body>
</html>
