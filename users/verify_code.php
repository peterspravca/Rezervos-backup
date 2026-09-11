<?php
// users/verify_code.php

$prefix = '../';
require_once $prefix . 'config.php';
require_once $prefix . 'translator_helper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');

    if (empty($email) || empty($code)) {
        $error = t('Prosím vyplňte všetky požadované údaje.');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = t('Používateľ s týmto e-mailom neexistuje.');
            } else if ($user['is_verified']) {
                $error = t('Tento účet je už overený.');
            } else if (empty($user['verification_code']) || $user['verification_code'] !== $code) {
                $error = t('Zadaný kód je nesprávny alebo vypršala jeho platnosť.');
            } else {
                // Kód sedí, účet je správny. Rola sa volí až v úvodnom sprievodcovi nastavením.
                $role = $user['role'] ?: 'customer';
                $update_stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");

                if ($update_stmt->execute([$user['id']])) {
                    // Prihlásime používateľa
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_role'] = $role;
                    $_SESSION['is_admin'] = 0; // Noví používatelia nie sú admini
                    $_SESSION['onboarding_completed'] = (int)($user['onboarding_completed'] ?? 1);
                    unset($_SESSION['verify_email']);

                    if (!empty($user['referral_code'])) {
                        // Promo kód (zľava na doplnky / predĺženie balíka) dáva zmysel len pre firemný účet —
                        // rola sa ale volí až v sprievodcovi nastavením, takže uplatnenie rieši api/onboarding.php
                        // (action=set_role) vo chvíli, keď si používateľ vyberie "Prevádzka".

                        // Affiliate atribúciu si zapíšeme VŽDY, bez ohľadu na zvolenú rolu — provízia sa
                        // priznáva až pri neskoršej platenej aktivácii balíka, ku ktorej môže dôjsť aj vtedy,
                        // keď sa používateľ zaregistroval najprv ako zákazník a na biznis účet prejde neskôr.
                        require_once $prefix . 'includes/affiliate_helper.php';
                        affiliate_try_attribute($pdo, $user['id'], $user['referral_code']);
                    }

                    // Čerstvo overený účet vždy prejde najprv úvodným sprievodcom nastavením.
                    header("Location: ../onboarding.php");
                    exit;
                } else {
                    $error = t('Nepodarilo sa overiť účet. Skúste to prosím znova.');
                }
            }
        } catch (\PDOException $ex) {
            $error = t('Chyba databázy: ') . $ex->getMessage();
        }
    }
}

// Ak dôjde k chybe, presmerujeme späť s chybovou hláškou
if (!empty($error)) {
    $_SESSION['auth_error'] = $error;
    $_SESSION['verify_email'] = $email;
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
    if (strpos($ref, 'verify_required=1') === false) {
        $ref .= (strpos($ref, '?') !== false ? '&' : '?') . 'verify_required=1';
    }
    header("Location: " . $ref);
    exit;
}

// Ak sa stránka otvorí napriamo cez GET
header("Location: ../index.php");
exit;
