<?php
// users/verify_3fa.php
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

$enteredAnswer = trim($_POST['answer'] ?? '');
$error = '';

if (empty($_SESSION['temp_login_user_id']) || empty($_SESSION['temp_login_question_idx'])) {
    $error = t('Relácia overenia vypršala. Prosím, prihláste sa znova.');
    $_SESSION['auth_view'] = 'login';
} else {
    $user_id = $_SESSION['temp_login_user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = t('Používateľ nebol nájdený.');
        $_SESSION['auth_view'] = 'login';
    } else {
        $qIdx = (int)($_SESSION['temp_login_question_idx'] ?? 1);
        $actualAnswer = $user['security_a' . $qIdx] ?? '';
        
        function normalizeStr3FA($str) {
            $str = mb_strtolower($str, 'UTF-8');
            // Odstránenie diakritiky
            $unwanted_array = [
                'á'=>'a', 'ä'=>'a', 'č'=>'c', 'ď'=>'d', 'é'=>'e', 'ě'=>'e', 'í'=>'i', 'ĺ'=>'l', 'ľ'=>'l', 'ň'=>'n',
                'ó'=>'o', 'ô'=>'o', 'ö'=>'o', 'ŕ'=>'r', 'ř'=>'r', 'š'=>'s', 'ť'=>'t', 'ú'=>'u', 'ů'=>'u', 'ü'=>'u',
                'ý'=>'y', 'ž'=>'z', 'Á'=>'a', 'Ä'=>'a', 'Č'=>'c', 'Ď'=>'d', 'É'=>'e', 'Ě'=>'e', 'Í'=>'i', 'Ĺ'=>'l',
                'Ľ'=>'l', 'Ň'=>'n', 'Ó'=>'o', 'Ô'=>'o', 'Ö'=>'o', 'Ŕ'=>'r', 'Ř'=>'r', 'Š'=>'s', 'Ť'=>'t', 'Ú'=>'u',
                'Ů'=>'u', 'Ü'=>'u', 'Ý'=>'y', 'Ž'=>'z'
            ];
            $str = strtr($str, $unwanted_array);
            return preg_replace('/[^\p{L}\p{N}]/u', '', $str);
        }
        
        if (empty($enteredAnswer) || normalizeStr3FA($enteredAnswer) !== normalizeStr3FA($actualAnswer)) {
            $error = t('Nesprávna odpoveď na bezpečnostnú otázku. Skúste to znova.');
            $_SESSION['auth_view'] = 'verify_3fa';
        } else {
            // Úspešne zodpovedaná 3FA otázka! Prihlásime používateľa
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
            
            // Vyčistenie dočasných sessions
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
        }
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
