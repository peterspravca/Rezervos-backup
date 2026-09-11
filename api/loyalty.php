<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/loyalty_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'require_auth' => true, 'message' => 'Musíte byť prihlásený.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'customer';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

ensureLoyaltyTables($conn);

function getBusinessEstablishmentIdLoyalty($conn, $userId) {
    $stmt = $conn->prepare("SELECT id FROM establishments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['id'] : 0;
}

// ── ZÁKAZNÍCKA AKCIA ─────────────────────────────────────────────────────

if ($action === 'list_my_progress') {
    $res = $conn->query("SELECT clp.*, e.name as establishment_name, lp.reward_type, lp.reward_threshold, lp.reward_description
                          FROM customer_loyalty_progress clp
                          JOIN establishments e ON e.id = clp.establishment_id
                          JOIN loyalty_programs lp ON lp.establishment_id = clp.establishment_id AND lp.is_active = 1
                          WHERE clp.customer_id = $userId
                          ORDER BY clp.updated_at DESC");
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $earned = loyaltyRewardsEarned($r, $r);
        $r['rewards_available'] = max(0, $earned - (int)$r['rewards_redeemed']);
        $r['progress_current'] = $r['reward_type'] === 'spend' ? (float)$r['spend_total'] : (int)$r['visits_count'];
        $r['progress_toward_next'] = fmod((float)$r['progress_current'], (float)$r['reward_threshold']);
        $rows[] = $r;
    }
    echo json_encode(['success' => true, 'progress' => $rows]);
    exit;
}

// ── FIREMNÉ AKCIE (vyžadujú business rolu) ──────────────────────────────

elseif (in_array($action, ['get_program', 'save_program', 'list_members', 'redeem_reward'])) {
    if ($userRole !== 'business') { echo json_encode(['success' => false, 'message' => 'Prístup zamietnutý.']); exit; }
    $establishmentId = getBusinessEstablishmentIdLoyalty($conn, $userId);
    if (!$establishmentId) { echo json_encode(['success' => false, 'message' => 'Prevádzka sa nenašla.']); exit; }

    if ($action === 'get_program') {
        $stmt = $conn->prepare("SELECT * FROM loyalty_programs WHERE establishment_id = ?");
        $stmt->bind_param("i", $establishmentId);
        $stmt->execute();
        $program = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'program' => $program ?: null]);
    }

    elseif ($action === 'save_program') {
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $rewardType = in_array($_POST['reward_type'] ?? '', ['visits', 'spend']) ? $_POST['reward_type'] : 'visits';
        $rewardThreshold = max(0.01, (float)($_POST['reward_threshold'] ?? 10));
        $rewardDescription = trim($_POST['reward_description'] ?? '');
        if (!$rewardDescription) { echo json_encode(['success' => false, 'message' => 'Zadajte popis odmeny.']); exit; }

        $stmt = $conn->prepare("INSERT INTO loyalty_programs (establishment_id, is_active, reward_type, reward_threshold, reward_description)
                                 VALUES (?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), reward_type = VALUES(reward_type),
                                     reward_threshold = VALUES(reward_threshold), reward_description = VALUES(reward_description)");
        $stmt->bind_param("iisds", $establishmentId, $isActive, $rewardType, $rewardThreshold, $rewardDescription);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Vernostný program bol uložený.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chyba DB: ' . $conn->error]);
        }
    }

    elseif ($action === 'list_members') {
        $prog_stmt = $conn->prepare("SELECT * FROM loyalty_programs WHERE establishment_id = ?");
        $prog_stmt->bind_param("i", $establishmentId);
        $prog_stmt->execute();
        $program = $prog_stmt->get_result()->fetch_assoc();

        $res = $conn->prepare("SELECT clp.*, u.full_name as customer_name, u.email as customer_email
                                FROM customer_loyalty_progress clp
                                JOIN users u ON u.id = clp.customer_id
                                WHERE clp.establishment_id = ?
                                ORDER BY clp.visits_count DESC, clp.spend_total DESC
                                LIMIT 300");
        $res->bind_param("i", $establishmentId);
        $res->execute();
        $rows = [];
        $r = $res->get_result();
        while ($row = $r->fetch_assoc()) {
            $earned = loyaltyRewardsEarned($program, $row);
            $row['rewards_available'] = max(0, $earned - (int)$row['rewards_redeemed']);
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'program' => $program ?: null, 'members' => $rows]);
    }

    elseif ($action === 'redeem_reward') {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $upd = $conn->prepare("UPDATE customer_loyalty_progress SET rewards_redeemed = rewards_redeemed + 1 WHERE customer_id = ? AND establishment_id = ?");
        $upd->bind_param("ii", $customerId, $establishmentId);
        if ($upd->execute() && $upd->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Odmena bola označená ako vyzdvihnutá.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Zákazník sa nenašiel.']);
        }
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Neznáma akcia.']);
}
