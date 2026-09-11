<?php
// ============================================================
// CRM Nástenka – Auth helper (PHP 8.4 compatible)
// ============================================================
ob_start(); // Buffer all output to prevent "headers already sent" errors

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/notifications.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name(SESSION_NAME);
    session_start();

    // ONE-TIME DB FIX for the eye emoji
    if (!isset($_SESSION['fixed_eye_emoji'])) {
        $pdo_fix = db_connect();
        $old_emojis = ['👁️', '👁'];
        $new_icon = "<i class='ti ti-file-search' style='margin-right:4px;'></i>";
        foreach ($old_emojis as $emoji) {
            $pdo_fix->prepare("UPDATE crm_notes SET content = REPLACE(content, ?, ?) WHERE content LIKE ?")
                    ->execute([$emoji, $new_icon, "%$emoji%"]);
        }
        $_SESSION['fixed_eye_emoji'] = true;
    }
}

function is_logged_in(): bool {
    return isset($_SESSION['crm_user_id']) && !empty($_SESSION['crm_user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    // Session expiry
    if (isset($_SESSION['crm_last_activity']) && (time() - $_SESSION['crm_last_activity']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }
    $_SESSION['crm_last_activity'] = time();
}

function current_user(): array {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT email, phone FROM crm_users WHERE id = ?");
    $stmt->execute([$_SESSION['crm_user_id'] ?? 0]);
    $u_data = $stmt->fetch();

    return [
        'id'        => $_SESSION['crm_user_id']   ?? 0,
        'username'  => $_SESSION['crm_username']   ?? '',
        'full_name' => $_SESSION['crm_full_name']  ?? '',
        'role'      => $_SESSION['crm_role']       ?? 'user',
        'email'     => $u_data['email'] ?? '',
        'phone'     => $u_data['phone'] ?? '',
    ];
}

function is_admin(): bool {
    return ($_SESSION['crm_role'] ?? '') === 'admin';
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: index.php?err=noadmin');
        exit;
    }
}

function login_user(string $username, string $password): bool {
    $pdo  = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM crm_users WHERE username = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    $_SESSION['crm_user_id']   = $user['id'];
    $_SESSION['crm_username']  = $user['username'];
    $_SESSION['crm_full_name'] = $user['full_name'];
    $_SESSION['crm_role']      = $user['role'];
    $_SESSION['crm_last_activity'] = time();
    // Update last login
    $pdo->prepare("UPDATE crm_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
    return true;
}

function logout_user(): void {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// CSRF protection
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): void {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        die(json_encode(['error' => 'CSRF token mismatch']));
    }
}

?>

