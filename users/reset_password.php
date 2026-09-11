<?php
// users/reset_password.php
$prefix = '../';
require_once $prefix . 'includes/config.php';
require_once $prefix . 'includes/translator_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged in users
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

$token = trim($_GET['token'] ?? '');
$email = trim($_GET['email'] ?? '');

if (empty($token) || empty($email)) {
    header('Location: login.php');
    exit;
}

$hashed_token = hash('sha256', $token);

// Validate token and email
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$email, $hashed_token]);
$user = $stmt->fetch();

if (!$user) {
    $error = t('Odkaz na obnovenie hesla je neplatný, už bol použitý alebo expiroval.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $honeypot = $_POST['hp_username'] ?? '';
    
    if ($honeypot !== '') {
        $error = t('Bezpečnostná ochrana zlyhala. Skúste to prosím znova (Anti-Spam).');
    } else {
        // Turnstile Overenie
        $ts_token = $_POST['cf-turnstile-response'] ?? '';
        $turnstile_valid = true;
        if (!empty($ts_token)) {
            $turnstile_valid = verify_turnstile($ts_token, TURNSTILE_SECRET_KEY);
        }
        
        if (!$turnstile_valid) {
            $error = t('Bezpečnostné overenie zlyhalo. Skúste to znova. (Anti-Spam)');
        } elseif (strlen($password) < 8) {
            $error = t('Nové heslo musí mať minimálne 8 znakov.');
        } elseif ($password !== $password_confirm) {
            $error = t('Zadané heslá sa nezhodujú.');
        } else {
            // Hash new password
            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token fields
            $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $update_stmt->execute([$new_password_hash, $user['id']]);
            
            // Redirect to login page with custom success parameter
            header('Location: login.php?reset_success=1');
            exit;
        }
    }
}

require_once $prefix . 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px; flex-direction: column;">
        <div style="padding: 40px; width: 100%; box-sizing: border-box;">
            
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(34, 180, 120, 0.1); color: #22c478; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                    <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 30px;">vpn_key</span>
                </div>
                <h1 style="margin: 0 0 10px 0; font-size: 24px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px;"><?php echo t('Nové heslo'); ?></h1>
                <p style="margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.5;"><?php echo t('Zadajte svoje nové bezpečné heslo k účtu.'); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="auth-error" style="margin-bottom: 20px;">
                    <span class="material-symbols-outlined notranslate" style="font-size:16px;">error</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($user && !$success): ?>
                <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>&email=<?php echo urlencode($email); ?>" method="POST">
                    
                    <div class="auth-field" style="margin-bottom: 20px;">
                        <label><?php echo t('Nové heslo'); ?></label>
                        <div class="auth-input-wrap">
                            <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">lock</span>
                            <input type="password" name="password" id="reset-password" placeholder="<?php echo t('Minimálne 8 znakov'); ?>" required minlength="8" style="font-size: 14px;">
                            <button type="button" class="auth-eye-btn" onclick="togglePwd('reset-password', this)" title="<?php echo t('Zobraziť heslo'); ?>">
                                <span class="material-symbols-outlined notranslate" translate="no">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="auth-field" style="margin-bottom: 25px;">
                        <label><?php echo t('Potvrdenie nového hesla'); ?></label>
                        <div class="auth-input-wrap">
                            <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">lock_open</span>
                            <input type="password" name="password_confirm" id="reset-password-confirm" placeholder="<?php echo t('Zopakujte nové heslo'); ?>" required minlength="8" style="font-size: 14px;">
                            <button type="button" class="auth-eye-btn" onclick="togglePwd('reset-password-confirm', this)" title="<?php echo t('Zobraziť heslo'); ?>">
                                <span class="material-symbols-outlined notranslate" translate="no">visibility</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Honeypot pasca na spamových robotov -->
                    <div style="display: none !important;">
                        <label><?php echo t('Nevyplňujte toto pole, ak ste človek:'); ?></label>
                        <input type="text" name="hp_username" value="" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <!-- Cloudflare Turnstile (Anti-Spam) -->
                    <div style="margin: 20px 0; display: flex; justify-content: center;">
                        <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="auto"></div>
                    </div>
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

                    <button type="submit" class="auth-submit" style="font-weight: 700; height: 48px; border-radius: 12px; font-size: 14.5px;"><?php echo t('Zmeniť heslo'); ?></button>
                    
                </form>
            <?php endif; ?>
            
            <div style="margin-top: 25px; text-align: center;">
                <a href="login.php" style="color: var(--text-secondary); font-size: 13.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 18px;">arrow_back</span>
                    <?php echo t('Späť na prihlásenie'); ?>
                </a>
            </div>
            
        </div>
    </div>
</div>

<script>
function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('.material-symbols-outlined');
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}
</script>

<?php require_once $prefix . 'includes/footer.php'; ?>
