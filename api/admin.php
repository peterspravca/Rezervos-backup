<?php
session_start();
require_once '../config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php';

header('Content-Type: application/json');

// Overenie roly administrátora
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Chyba databázy.']);
    exit;
}
$conn->set_charset("utf8mb4");

// Self-migrácia: admin-udeľované Sponzorované zvýraznenie prevádzky (nezávisle od platby cez peňaženku)
$conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS sponsored_expires_at DATETIME NULL DEFAULT NULL");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_stats') {
    $stats = [
        'active_businesses' => 0,
        'customers' => 0,
        'bookings_today' => 0,
        'pending_businesses' => 0
    ];

    $res = $conn->query("SELECT COUNT(id) AS c FROM establishments WHERE status = 'active'");
    if ($row = $res->fetch_assoc()) $stats['active_businesses'] = $row['c'];

    $res = $conn->query("SELECT COUNT(id) AS c FROM users WHERE role = 'customer'");
    if ($row = $res->fetch_assoc()) $stats['customers'] = $row['c'];

    $res = $conn->query("SELECT COUNT(id) AS c FROM bookings WHERE DATE(booking_date) = CURDATE()");
    if ($row = $res->fetch_assoc()) $stats['bookings_today'] = $row['c'];

    $res = $conn->query("SELECT COUNT(id) AS c FROM establishments WHERE status = 'pending'");
    if ($row = $res->fetch_assoc()) $stats['pending_businesses'] = $row['c'];

    echo json_encode(['success' => true, 'stats' => $stats]);
}
elseif ($action === 'get_pending_businesses') {
    $res = $conn->query("SELECT e.*, u.full_name AS owner_name FROM establishments e JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}
elseif ($action === 'approve_business') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("UPDATE establishments SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Majiteľa informujeme mailom, že prevádzka bola schválená — zlyhanie mailu nesmie zhodiť schválenie.
        try {
            $ownerStmt = $conn->prepare("SELECT e.name AS establishment_name, u.email, u.full_name FROM establishments e JOIN users u ON u.id = e.user_id WHERE e.id = ?");
            $ownerStmt->bind_param("i", $id);
            $ownerStmt->execute();
            $ownerRow = $ownerStmt->get_result()->fetch_assoc();
            if ($ownerRow && !empty($ownerRow['email'])) {
                require_once '../includes/phpmailer/exception.php';
                require_once '../includes/phpmailer/phpmailer.php';
                require_once '../includes/phpmailer/smtp.php';

                $approvedMail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $approvedMail->isSMTP();
                $approvedMail->Host       = 'mail.usr.sk';
                $approvedMail->SMTPAuth   = true;
                $approvedMail->Username   = $smtp_user;
                $approvedMail->Password   = $smtp_pass;
                $approvedMail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $approvedMail->Port       = 465;
                $approvedMail->CharSet    = 'UTF-8';

                $approvedMail->setFrom($smtp_user, BRAND_NAME);
                $approvedMail->addAddress($ownerRow['email'], $ownerRow['full_name'] ?? '');
                $approvedMail->isHTML(true);
                $approvedMail->Subject = 'Vaša prevádzka bola schválená - ' . BRAND_NAME;
                $approvedMail->Body = "
                    <div style='font-family:Outfit,Arial,sans-serif; color:#0F172A; max-width:520px; margin:0 auto; padding:32px 24px; background:#f4f1ea; border-radius:16px;'>
                        <h2 style='margin:0 0 12px;'>Dobré správy, " . htmlspecialchars($ownerRow['full_name'] ?? '') . "!</h2>
                        <p style='font-size:14.5px; line-height:1.6; color:#334155;'>
                            Vaša prevádzka <b>" . htmlspecialchars($ownerRow['establishment_name']) . "</b> bola schválená a je teraz viditeľná pre zákazníkov.
                        </p>
                        <p style='font-size:14.5px; line-height:1.6; color:#334155;'>
                            Prihláste sa do Nástenky a doplňte si cenník, tím a rezervačné pravidlá, ak ste to ešte nestihli.
                        </p>
                    </div>
                ";
                $approvedMail->AltBody = "Vasa provadzka {$ownerRow['establishment_name']} bola schvalena a je teraz vidiitelna pre zakaznikov.";
                $approvedMail->send();
            }
        } catch (\Exception $mailEx) {
            error_log('Establishment approved email failed: ' . $mailEx->getMessage());
        }

        echo json_encode(['success' => true]);
    }
}
elseif ($action === 'reject_business') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("UPDATE establishments SET status = 'blocked' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}
elseif ($action === 'delete_business') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM establishments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}
elseif ($action === 'get_all_businesses') {
    $res = $conn->query("SELECT e.*, u.full_name AS owner_name, u.email AS owner_email, u.credit, u.credit_purchased, u.credit_earned, u.sms_credits, u.wallet_number, u.subscription_tier AS user_tier FROM establishments e JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}
elseif ($action === 'get_users') {
    $res = $conn->query("SELECT u.id, u.email, u.full_name, u.role, u.status, u.created_at, u.subscription_tier, u.credit, u.credit_purchased, u.credit_earned, u.sms_credits, u.wallet_number, (SELECT e.id FROM establishments e WHERE e.user_id = u.id LIMIT 1) as establishment_id, (SELECT e.name FROM establishments e WHERE e.user_id = u.id LIMIT 1) as establishment_name, (SELECT e.sponsored_expires_at FROM establishments e WHERE e.user_id = u.id LIMIT 1) as sponsored_expires_at FROM users u ORDER BY u.created_at DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}
elseif ($action === 'update_business_tier_and_credit') {
    $establishment_id = intval($_POST['establishment_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $tier = strtolower(trim($_POST['tier'] ?? 'free'));
    $credit_mode = trim($_POST['credit_mode'] ?? 'add'); // 'add' or 'set'
    $credit_amount = floatval($_POST['credit_amount'] ?? 0);
    $sms_mode = trim($_POST['sms_mode'] ?? 'add'); // 'add' or 'set'
    $sms_amount = intval($_POST['sms_amount'] ?? 0);
    $sponsored_grant_months = intval($_POST['sponsored_grant_months'] ?? 0);
    $sponsored_revoke = isset($_POST['sponsored_revoke']) && $_POST['sponsored_revoke'] === '1';

    $valid_tiers = ['free', 'start', 'pro', 'vip'];
    if (!in_array($tier, $valid_tiers)) {
        $tier = 'free';
    }

    if ($establishment_id && !$user_id) {
        $res = $conn->query("SELECT user_id FROM establishments WHERE id = $establishment_id");
        if ($r = $res->fetch_assoc()) {
            $user_id = intval($r['user_id']);
        }
    }

    if ($establishment_id) {
        $stmt = $conn->prepare("UPDATE establishments SET subscription_tier = ? WHERE id = ?");
        $stmt->bind_param("si", $tier, $establishment_id);
        $stmt->execute();

        // Self-migrácia: admin môže ručne udeliť Sponzorované zvýraznenie (napr. za zapojenie do partnerskej akcie), bez platby
        $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS sponsored_expires_at DATETIME NULL DEFAULT NULL");

        if ($sponsored_revoke) {
            $conn->query("UPDATE establishments SET sponsored_expires_at = NULL WHERE id = $establishment_id");
        } elseif ($sponsored_grant_months > 0) {
            $stmt = $conn->prepare("UPDATE establishments SET sponsored_expires_at = DATE_ADD(GREATEST(COALESCE(sponsored_expires_at, NOW()), NOW()), INTERVAL ? MONTH) WHERE id = ?");
            $stmt->bind_param("ii", $sponsored_grant_months, $establishment_id);
            $stmt->execute();
        }
    }

    if ($user_id) {
        $stmt = $conn->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
        $stmt->bind_param("si", $tier, $user_id);
        $stmt->execute();

        if ($credit_amount != 0 || $credit_mode === 'set') {
            if ($credit_mode === 'set') {
                $new_credit = max(0, $credit_amount);
                $stmt = $conn->prepare("UPDATE users SET credit = ?, credit_purchased = ? WHERE id = ?");
                $stmt->bind_param("ddi", $new_credit, $new_credit, $user_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("UPDATE users SET credit = credit + ?, credit_purchased = credit_purchased + ? WHERE id = ?");
                $stmt->bind_param("ddi", $credit_amount, $credit_amount, $user_id);
                $stmt->execute();
            }

            try {
                $note = "Admin úprava kreditu (" . ($credit_mode === 'set' ? 'Nastavené na ' : 'Pridané ') . number_format($credit_amount, 2, ',', ' ') . " €)";
                $type = $credit_amount >= 0 ? 'topup' : 'spend';
                $t_stmt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, note, created_at) VALUES (?, ?, ?, ?, NOW())");
                if ($t_stmt) {
                    $t_stmt->bind_param("isds", $user_id, $type, $credit_amount, $note);
                    $t_stmt->execute();
                }
            } catch (Exception $ex) {}
        }

        if ($sms_amount != 0 || $sms_mode === 'set') {
            if ($sms_mode === 'set') {
                $new_sms = max(0, $sms_amount);
                $stmt = $conn->prepare("UPDATE users SET sms_credits = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_sms, $user_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("UPDATE users SET sms_credits = sms_credits + ? WHERE id = ?");
                $stmt->bind_param("ii", $sms_amount, $user_id);
                $stmt->execute();
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Údaje balíka a peňaženky boli úspešne uložené.'
    ]);
    exit;
}
elseif ($action === 'toggle_user_status') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $res = $conn->query("SELECT status, role FROM users WHERE id = $id");
        if ($row = $res->fetch_assoc()) {
            if ($row['role'] === 'admin') {
                echo json_encode(['success' => false, 'error' => 'Administrátora nie je možné zablokovať.']);
                exit;
            }
            $new_status = ($row['status'] === 'active') ? 'blocked' : 'active';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'new_status' => $new_status]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
}
elseif ($action === 'delete_user') {
    $id = intval($_POST['id'] ?? 0);
    if ($id && $id !== $_SESSION['user_id']) {
        $res = $conn->query("SELECT role FROM users WHERE id = $id");
        if ($row = $res->fetch_assoc()) {
            if ($row['role'] === 'admin') {
                echo json_encode(['success' => false, 'error' => 'Administrátora nie je možné vymazať.']);
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Nemožné vymazať tohto používateľa.']);
}
elseif ($action === 'get_all_classifieds') {
    $res = $conn->query("SELECT c.*, u.full_name AS owner_name, u.email AS owner_email
                          FROM classifieds c
                          LEFT JOIN users u ON c.user_id = u.id
                          ORDER BY c.created_at DESC");
    $data = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
}
elseif ($action === 'delete_classified') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM classifieds WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->affected_rows > 0
            ? ['success' => true]
            : ['success' => false, 'error' => 'Inzerát sa nenašiel.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chýba ID.']);
    }
}

$conn->close();
?>
