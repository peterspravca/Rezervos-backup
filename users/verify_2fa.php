<?php
// users/verify_2fa.php
$prefix = '../';
require_once $prefix . 'config.php';
require_once $prefix . 'translator_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$code = $_POST['code'] ?? '';
$error = '';

if (empty($_SESSION['temp_login_user_id']) || empty($_SESSION['temp_login_code'])) {
    $error = t('Platnosť kódu vypršala alebo nastala chyba. Prihláste sa znova.');
    $_SESSION['auth_view'] = 'login';
} elseif (time() > $_SESSION['temp_login_code_expires']) {
    $error = t('Platnosť kódu vypršala. Prihláste sa znova.');
    $_SESSION['auth_view'] = 'login';
} elseif ($code !== $_SESSION['temp_login_code']) {
    $error = t('Nesprávny overovací kód.');
    $_SESSION['auth_view'] = 'verify_2fa';
} else {
    // Kód je správny, prihlásime používateľa
    $user_id = $_SESSION['temp_login_user_id'];
    
    // Načítanie používateľa z DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Ak má používateľ zapnutý aj 3. stupeň (Bezpečnostné otázky)
        if (!empty($user['two_factor_questions'])) {
            $_SESSION['2fa_email_verified'] = true;
            
            $available_questions = [];
            for ($i = 1; $i <= 3; $i++) {
                if (!empty($user['security_q' . $i]) && !empty($user['security_a' . $i])) {
                    $available_questions[] = $i;
                }
            }
            $qIdx = !empty($available_questions) ? $available_questions[array_rand($available_questions)] : 1;
            $_SESSION['temp_login_question_idx'] = $qIdx;
            $_SESSION['temp_login_question_text'] = $user['security_q' . $qIdx] ?? 'Bezpečnostná otázka';
            $_SESSION['auth_view'] = 'verify_3fa';
            
            $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
            header('Location: ' . $ref);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = (isset($user['role']) && $user['role'] === 'admin') ? 1 : 0;
        $_SESSION['user_role'] = $user['role'] ?? 'customer';
        $_SESSION['onboarding_completed'] = isset($user['onboarding_completed']) ? (int)$user['onboarding_completed'] : 1;
        
        $remember = $_SESSION['temp_login_remember'] ?? false;
        
        if ($remember) {
            setcookie('remembered_email', $user['email'], time() + (30 * 24 * 60 * 60), "/");
            
            $token = bin2hex(random_bytes(32));
            $hashed_token = hash('sha256', $token);
            
            $update_stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $update_stmt->execute([$hashed_token, $user['id']]);
            
            setcookie('remember_me', $user['id'] . ':' . $token, time() + (30 * 24 * 60 * 60), "/", "", true, true);
        } else {
            setcookie('remembered_email', '', time() - 3600, "/");
            $update_stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
            $update_stmt->execute([$user['id']]);
            setcookie('remember_me', '', time() - 3600, "/");
        }
        
        // Zmazanie dočasných sessions
        unset($_SESSION['temp_login_user_id'], $_SESSION['temp_login_email'], $_SESSION['temp_login_remember'], $_SESSION['temp_login_code'], $_SESSION['temp_login_code_expires'], $_SESSION['2fa_email_verified'], $_SESSION['temp_login_question_idx'], $_SESSION['temp_login_question_text'], $_SESSION['auth_view']);
        
        $redirect_url = '../index.php';
        if (isset($user['role'])) {
            if ($user['role'] === 'admin') {
                $redirect_url = '../admin.php';
            } elseif ($user['role'] === 'business') {
                $redirect_url = '../dashboard.php';
            } elseif ($user['role'] === 'customer') {
                $redirect_url = '../moj_profil.php';
            }
        }
        header('Location: ' . $redirect_url);
        exit;
    } else {
        $error = t('Nastala neočakávaná chyba. Skúste to znova.');
        $_SESSION['auth_view'] = 'login';
    }
}

if (!empty($error)) {
    $_SESSION['auth_error'] = $error;
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
    if (strpos($ref, 'login_error=') === false) {
        $ref .= (strpos($ref, '?') !== false ? '&' : '?') . 'login_error=1';
    }
    header('Location: ' . $ref);
    exit;
}
