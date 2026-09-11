<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/mailer.php';
header('Content-Type: application/json; charset=utf-8');

function ensureNewsletterTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS establishment_newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        email VARCHAR(190) NOT NULL,
        confirmed TINYINT(1) NOT NULL DEFAULT 0,
        confirm_token VARCHAR(64) NOT NULL,
        unsubscribe_token VARCHAR(64) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_est_email (establishment_id, email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// ── VEREJNÉ PRIHLÁSENIE NA ODBER (formulár na verejnom profile prevádzky) ──
if ($action === 'subscribe') {
    ensureNewsletterTable($conn);
    $establishment_id = (int)($_POST['establishment_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $agreed = ($_POST['agree_terms'] ?? '') === '1';

    if (!$establishment_id) { echo json_encode(['success' => false, 'error' => 'Chýba prevádzka.']); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'error' => 'Zadajte platnú e-mailovú adresu.']); exit; }
    if (!$agreed) { echo json_encode(['success' => false, 'error' => 'Musíte súhlasiť s obchodnými podmienkami a spracovaním osobných údajov.']); exit; }

    // Prevádzka musí byť od balíka Pro vyššie, inak formulár neexistuje ani na verejnom profile —
    // táto kontrola je len poistka proti priamemu volaniu API
    $tierStmt = $conn->prepare("SELECT e.subscription_tier as e_tier, u.subscription_tier as u_tier, e.name FROM establishments e LEFT JOIN users u ON u.id = e.user_id WHERE e.id = ?");
    $tierStmt->bind_param("i", $establishment_id);
    $tierStmt->execute();
    $est = $tierStmt->get_result()->fetch_assoc();
    $bestTier = ((['free'=>0,'start'=>1,'pro'=>2,'vip'=>3][strtolower($est['u_tier'] ?? 'free')] ?? 0) >= (['free'=>0,'start'=>1,'pro'=>2,'vip'=>3][strtolower($est['e_tier'] ?? 'free')] ?? 0)) ? strtolower($est['u_tier'] ?? 'free') : strtolower($est['e_tier'] ?? 'free');
    if (!$est || !in_array($bestTier, ['pro', 'vip', 'premium'], true)) {
        echo json_encode(['success' => false, 'error' => 'Táto funkcia nie je pre danú prevádzku dostupná.']);
        exit;
    }

    $confirm_token = bin2hex(random_bytes(20));
    $unsub_token = bin2hex(random_bytes(20));

    $stmt = $conn->prepare("SELECT id, confirmed FROM establishment_newsletter_subscribers WHERE establishment_id = ? AND email = ?");
    $stmt->bind_param("is", $establishment_id, $email);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing && $existing['confirmed']) {
        echo json_encode(['success' => true, 'message' => 'Tento e-mail už odber noviniek odoberá.']);
        exit;
    } elseif ($existing) {
        $upd = $conn->prepare("UPDATE establishment_newsletter_subscribers SET confirm_token = ? WHERE id = ?");
        $upd->bind_param("si", $confirm_token, $existing['id']);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO establishment_newsletter_subscribers (establishment_id, email, confirm_token, unsubscribe_token) VALUES (?, ?, ?, ?)");
        $ins->bind_param("isss", $establishment_id, $email, $confirm_token, $unsub_token);
        $ins->execute();
    }

    $root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
    $confirm_url = $root_url . 'potvrdenie-odberu.php?token=' . $confirm_token;
    sendNewsletterConfirmEmail($email, $est['name'], $confirm_url);

    echo json_encode(['success' => true, 'message' => 'Na váš e-mail sme poslali potvrdzovací odkaz — bez potvrdenia sa odber neaktivuje.']);
    exit;
}

// ── POČET ODBERATEĽOV (dashboard prevádzky) ──
if ($action === 'get_subscriber_count') {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['success' => false, 'error' => 'Neprihlásený.']); exit; }
    ensureNewsletterTable($conn);
    $user_id = (int)$_SESSION['user_id'];
    $estStmt = $conn->prepare("SELECT id, subscription_tier FROM establishments WHERE user_id = ?");
    $estStmt->bind_param("i", $user_id);
    $estStmt->execute();
    $est = $estStmt->get_result()->fetch_assoc();
    if (!$est || !in_array(strtolower($est['subscription_tier'] ?? 'free'), ['pro', 'vip', 'premium'], true)) {
        echo json_encode(['success' => false, 'error' => 'Táto funkcia je dostupná od balíka Pro vyššie.']);
        exit;
    }
    $est_id = (int)$est['id'];
    $cStmt = $conn->prepare("SELECT COUNT(*) as c FROM establishment_newsletter_subscribers WHERE establishment_id = ? AND confirmed = 1");
    $cStmt->bind_param("i", $est_id);
    $cStmt->execute();
    $count = (int)($cStmt->get_result()->fetch_assoc()['c'] ?? 0);
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// ── ODOSLANIE NOVINKY VŠETKÝM POTVRDENÝM ODBERATEĽOM (dashboard prevádzky) ──
if ($action === 'send_update') {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['success' => false, 'error' => 'Neprihlásený.']); exit; }
    require_once __DIR__ . '/../includes/marketing_send_helper.php';
    ensureNewsletterTable($conn);
    $user_id = (int)$_SESSION['user_id'];
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (empty($subject) || empty($content)) { echo json_encode(['success' => false, 'error' => 'Predmet aj obsah sú povinné.']); exit; }

    $estStmt = $conn->prepare("SELECT id, name, subscription_tier, marketing_email_sender FROM establishments WHERE user_id = ?");
    $estStmt->bind_param("i", $user_id);
    $estStmt->execute();
    $est = $estStmt->get_result()->fetch_assoc();
    if (!$est || !in_array(strtolower($est['subscription_tier'] ?? 'free'), ['pro', 'vip', 'premium'], true)) {
        echo json_encode(['success' => false, 'error' => 'Táto funkcia je dostupná od balíka Pro vyššie.']);
        exit;
    }
    $est_id = (int)$est['id'];
    $sender_uid = (($est['marketing_email_sender'] ?? 'rezervos') === 'own') ? $user_id : null;
    $root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";

    $subStmt = $conn->prepare("SELECT email, unsubscribe_token FROM establishment_newsletter_subscribers WHERE establishment_id = ? AND confirmed = 1");
    $subStmt->bind_param("i", $est_id);
    $subStmt->execute();
    $subscribers = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $sent_count = 0;
    foreach ($subscribers as $s) {
        $unsub_url = $root_url . 'odhlasenie-noviniek.php?token=' . $s['unsubscribe_token'];
        // $content je teraz HTML z richtext editora (Quill) — neuniká sa, aby formátovanie (farby, tučné, zoznamy...) fungovalo v e-maile
        $html = $content . '<br><br><hr style="border:none;border-top:1px solid #e5e5e5;"><div style="margin-top:8px;font-size:12px;color:#9b8f7c;"><a href="' . htmlspecialchars($unsub_url) . '" style="color:#9b8f7c;">Odhlásiť sa z odberu noviniek</a></div>';
        if (send_marketing_email($s['email'], '', $subject, $html, $sender_uid, $est['name'])) { $sent_count++; }
    }

    echo json_encode(['success' => true, 'message' => "Novinka bola odoslaná $sent_count odberateľom."]);
    exit;
}

// ── ZOZNAM PREVÁDZOK (OD PRO) KDE UŽ PRIHLÁSENÝ ZÁKAZNÍK REZERVOVAL + STAV ODBERU ──
if ($action === 'get_my_subscriptions') {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
        echo json_encode(['success' => false, 'error' => 'Neprihlásený.']); exit;
    }
    ensureNewsletterTable($conn);
    $user_id = (int)$_SESSION['user_id'];
    $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $emailStmt->bind_param("i", $user_id);
    $emailStmt->execute();
    $my_email = $emailStmt->get_result()->fetch_assoc()['email'] ?? '';

    $stmt = $conn->prepare("
        SELECT DISTINCT e.id, e.name,
               (SELECT confirmed FROM establishment_newsletter_subscribers WHERE establishment_id = e.id AND email = ?) as confirmed
        FROM bookings b
        JOIN establishments e ON e.id = b.establishment_id
        WHERE b.customer_id = ? AND b.status = 'completed' AND e.subscription_tier IN ('pro','vip','premium')
    ");
    $stmt->bind_param("si", $my_email, $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'establishments' => $rows]);
    exit;
}

// ── PRIHLÁSENÝ ZÁKAZNÍK ZAP/VYP ODBER U KONKRÉTNEJ PREVÁDZKY (z osobného profilu) ──
// E-mail je už overený cez účet, takže sa netreba znova potvrdzovať cez odkaz v e-maile.
if ($action === 'toggle_my_subscription') {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
        echo json_encode(['success' => false, 'error' => 'Neprihlásený.']); exit;
    }
    ensureNewsletterTable($conn);
    $user_id = (int)$_SESSION['user_id'];
    $establishment_id = (int)($_POST['establishment_id'] ?? 0);
    $subscribe = ($_POST['subscribe'] ?? '') === '1';
    if (!$establishment_id) { echo json_encode(['success' => false, 'error' => 'Chýba prevádzka.']); exit; }

    $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $emailStmt->bind_param("i", $user_id);
    $emailStmt->execute();
    $my_email = $emailStmt->get_result()->fetch_assoc()['email'] ?? '';
    if (!$my_email) { echo json_encode(['success' => false, 'error' => 'Chýba e-mail účtu.']); exit; }

    if ($subscribe) {
        // Poistka: prihlásiť sa priamo z profilu (bez potvrdzovacieho e-mailu) môže zákazník len
        // u prevádzky, kde už mal dokončenú rezerváciu — inak nech použije verejný formulár na profile
        $checkStmt = $conn->prepare("SELECT 1 FROM bookings WHERE customer_id = ? AND establishment_id = ? AND status = 'completed' LIMIT 1");
        $checkStmt->bind_param("ii", $user_id, $establishment_id);
        $checkStmt->execute();
        if (!$checkStmt->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'error' => 'Odber si takto môžete zapnúť až po dokončenej návšteve tejto prevádzky.']);
            exit;
        }
        $unsub_token = bin2hex(random_bytes(20));
        $stmt = $conn->prepare("INSERT INTO establishment_newsletter_subscribers (establishment_id, email, confirmed, confirm_token, unsubscribe_token)
                                 VALUES (?, ?, 1, '', ?)
                                 ON DUPLICATE KEY UPDATE confirmed = 1");
        $stmt->bind_param("iss", $establishment_id, $my_email, $unsub_token);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("UPDATE establishment_newsletter_subscribers SET confirmed = 0 WHERE establishment_id = ? AND email = ?");
        $stmt->bind_param("is", $establishment_id, $my_email);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Neznáma akcia']);
