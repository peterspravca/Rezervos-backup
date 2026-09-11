<?php
// api/onboarding.php — akcie pre úvodného sprievodcu nastavením (onboarding.php)
session_start();
header('Content-Type: application/json');

require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'set_role') {
        $role = trim($_POST['role'] ?? '');
        if (!in_array($role, ['customer', 'business'], true)) {
            echo json_encode(['success' => false, 'message' => 'Neplatná rola.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $user_id]);
        $_SESSION['user_role'] = $role;

        // Odporúčací promo kód (zľava/predĺženie balíka) dáva zmysel len pre firemný účet —
        // uplatní sa až teraz, keď si používateľ v sprievodcovi vyberie "Prevádzka". Toto je
        // vedľajší, nepovinný krok — jeho prípadné zlyhanie nesmie zhodiť samotné nastavenie roly.
        if ($role === 'business') {
            try {
                $u = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
                $u->execute([$user_id]);
                $referral_code = $u->fetchColumn();
                if (!empty($referral_code)) {
                    require_once __DIR__ . '/../includes/promo_code_helper.php';
                    redeemPromoCode($user_id, $referral_code);
                }
            } catch (\Throwable $promoEx) {
                error_log('onboarding set_role promo redemption failed: ' . $promoEx->getMessage());
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_customer_basics') {
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');

        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL");
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, city = ? WHERE id = ?");
        $stmt->execute([$phone, $city, $user_id]);

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'complete') {
        $stmt = $pdo->prepare("UPDATE users SET onboarding_completed = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['onboarding_completed'] = 1;

        $isBusiness = ($_SESSION['user_role'] ?? 'customer') === 'business';
        if ($isBusiness) {
            // Nech dashboard.php pri prvom načítaní ukáže modal o schvaľovaní profilu.
            $_SESSION['show_pending_modal'] = 1;
        } else {
            // Nech moj_profil.php pri prvom načítaní ukáže uvítací modal (fotka, Kreslo Hunter...).
            $_SESSION['show_customer_welcome_modal'] = 1;
        }

        // Relatívna URL sa vyhodnocuje voči stránke, ktorá fetch volá (onboarding.php je v koreňovom adresári).
        $redirect_url = $isBusiness ? 'dashboard.php' : 'moj_profil.php';
        echo json_encode(['success' => true, 'redirect_url' => $redirect_url]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
} catch (\PDOException $ex) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy: ' . $ex->getMessage()]);
}
