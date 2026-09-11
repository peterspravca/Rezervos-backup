<?php
// api/affiliate.php — Fáza 5: Affiliate program. Na rozdiel od api/business.php je dostupné pre
// KAŽDÚ prihlásenú rolu (prevádzka aj zákazník môžu požiadať o zapojenie), nie len pre 'business'.
session_start();
require_once '../config.php';
require_once '../includes/affiliate_helper.php';
require_once '../includes/employee_permissions_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Neautorizovaný prístup.']);
    exit;
}

// $_SESSION['user_id'] pre zamestnanca je ID MAJITEĽA (spoločná session), takže bez tejto
// kontroly by tu zamestnanec videl/ovládal affiliate program a províznu históriu majiteľa.
if (!empty($_SESSION['is_employee']) && !employeeCan('settings')) {
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnenie na túto akciu.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
affiliate_migrate($pdo);

if ($action === 'get_status') {
    $stmt = $pdo->prepare("SELECT affiliate_status, affiliate_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $commissions = [];
    $totals = ['pending' => 0, 'paid' => 0];
    if ($row && $row['affiliate_status'] === 'approved') {
        $c = $pdo->prepare("SELECT tier, amount, type, status, created_at FROM affiliate_commissions WHERE affiliate_user_id = ? ORDER BY created_at DESC");
        $c->execute([$user_id]);
        $commissions = $c->fetchAll(PDO::FETCH_ASSOC);
        foreach ($commissions as $com) {
            $totals[$com['status']] += (float)$com['amount'];
        }
    }

    echo json_encode([
        'success' => true,
        'status' => $row['affiliate_status'] ?? 'none',
        'code' => $row['affiliate_code'] ?? null,
        'commissions' => $commissions,
        'totals' => $totals,
        'rates' => AFFILIATE_COMMISSION_RATES,
        'renewal_percent' => AFFILIATE_RENEWAL_PERCENT,
    ]);
}

elseif ($action === 'apply') {
    $stmt = $pdo->prepare("SELECT affiliate_status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC)['affiliate_status'] ?? 'none';

    if (in_array($status, ['pending', 'approved'], true)) {
        echo json_encode(['success' => false, 'message' => 'Žiadosť už bola podaná alebo je schválená.']);
        exit;
    }

    $pdo->prepare("UPDATE users SET affiliate_status = 'pending', affiliate_requested_at = NOW() WHERE id = ?")->execute([$user_id]);
    echo json_encode(['success' => true, 'message' => 'Žiadosť o zapojenie do affiliate programu bola odoslaná. Ozveme sa vám po posúdení.']);
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
