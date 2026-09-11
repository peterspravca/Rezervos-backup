<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once 'config.php';
require_once 'includes/affiliate_helper.php';
affiliate_migrate($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($formAction === 'approve' && $targetId) {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $chk = $pdo->prepare("SELECT id FROM users WHERE affiliate_code = ?");
            $chk->execute([$code]);
        } while ($chk->fetch());
        $pdo->prepare("UPDATE users SET affiliate_status = 'approved', affiliate_code = ? WHERE id = ?")->execute([$code, $targetId]);
    } elseif ($formAction === 'reject' && $targetId) {
        $pdo->prepare("UPDATE users SET affiliate_status = 'rejected' WHERE id = ?")->execute([$targetId]);
    } elseif ($formAction === 'mark_paid') {
        $commissionId = (int)($_POST['commission_id'] ?? 0);
        $pdo->prepare("UPDATE affiliate_commissions SET status = 'paid', paid_at = NOW() WHERE id = ?")->execute([$commissionId]);
    }
}

$pending = $pdo->query("SELECT id, full_name, email, affiliate_requested_at FROM users WHERE affiliate_status = 'pending' ORDER BY affiliate_requested_at ASC")->fetchAll(PDO::FETCH_ASSOC);
$approved = $pdo->query("SELECT id, full_name, email, affiliate_code FROM users WHERE affiliate_status = 'approved' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$commissions = $pdo->query("
    SELECT ac.*, u1.full_name AS affiliate_name, u1.email AS affiliate_email, u2.full_name AS referred_name
    FROM affiliate_commissions ac
    JOIN users u1 ON u1.id = ac.affiliate_user_id
    JOIN users u2 ON u2.id = ac.referred_user_id
    ORDER BY ac.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Affiliate - Administrácia';
require_once 'includes/dashboard-head.php';
?>
<?php $active_nav = 'affiliate'; $spa_host = false; require_once 'includes/admin-sidebar.php'; ?>
<div class="admin-main">
    <?php $headerTitle = 'Affiliate program'; $headerIcon = 'handshake'; require_once 'includes/dashboard-topbar.php'; ?>
    <div class="admin-content">
        <div class="section">

            <div class="vueto-card" style="margin-bottom:22px;">
                <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
                    <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined" style="color:var(--primary-color);">hourglass_top</span> Čakajúce žiadosti (<?= count($pending) ?>)</h2>
                </div>
                <div class="vueto-card-body" style="padding:10px 20px 20px;overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead><tr style="text-align:left;color:var(--text-secondary);font-size:11.5px;text-transform:uppercase;"><th style="padding:10px 8px;">Meno</th><th>E-mail</th><th>Požiadal</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($pending as $p): ?>
                            <tr style="border-top:1px solid var(--border-color);">
                                <td style="padding:10px 8px;"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td><?= $p['affiliate_requested_at'] ? date('d.m.Y', strtotime($p['affiliate_requested_at'])) : '—' ?></td>
                                <td style="display:flex;gap:6px;">
                                    <form method="POST"><input type="hidden" name="form_action" value="approve"><input type="hidden" name="user_id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn-primary" style="padding:6px 14px;font-size:12px;">Schváliť</button></form>
                                    <form method="POST"><input type="hidden" name="form_action" value="reject"><input type="hidden" name="user_id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn-secondary" style="padding:6px 14px;font-size:12px;color:#ef4444;">Zamietnuť</button></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$pending): ?><tr><td colspan="4" style="text-align:center;color:var(--text-secondary);padding:20px;">Žiadne čakajúce žiadosti.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="vueto-card" style="margin-bottom:22px;">
                <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
                    <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined" style="color:var(--primary-color);">group</span> Schválení obchodníci (<?= count($approved) ?>)</h2>
                </div>
                <div class="vueto-card-body" style="padding:10px 20px 20px;overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead><tr style="text-align:left;color:var(--text-secondary);font-size:11.5px;text-transform:uppercase;"><th style="padding:10px 8px;">Meno</th><th>E-mail</th><th>Kód</th></tr></thead>
                        <tbody>
                        <?php foreach ($approved as $a): ?>
                            <tr style="border-top:1px solid var(--border-color);">
                                <td style="padding:10px 8px;"><?= htmlspecialchars($a['full_name']) ?></td>
                                <td><?= htmlspecialchars($a['email']) ?></td>
                                <td><strong><?= htmlspecialchars($a['affiliate_code']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$approved): ?><tr><td colspan="3" style="text-align:center;color:var(--text-secondary);padding:20px;">Zatiaľ žiadni schválení obchodníci.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="vueto-card">
                <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
                    <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined" style="color:var(--primary-color);">payments</span> Provízie (<?= count($commissions) ?>)</h2>
                </div>
                <div class="vueto-card-body" style="padding:10px 20px 20px;overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead><tr style="text-align:left;color:var(--text-secondary);font-size:11.5px;text-transform:uppercase;"><th style="padding:10px 8px;">Obchodník</th><th>Priviedol</th><th>Balík</th><th>Typ</th><th>Suma</th><th>Dátum</th><th>Stav</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($commissions as $c): ?>
                            <tr style="border-top:1px solid var(--border-color);">
                                <td style="padding:10px 8px;"><?= htmlspecialchars($c['affiliate_name']) ?><div style="font-size:11px;color:var(--text-secondary);"><?= htmlspecialchars($c['affiliate_email']) ?></div></td>
                                <td><?= htmlspecialchars($c['referred_name']) ?></td>
                                <td><?= htmlspecialchars(strtoupper($c['tier'])) ?></td>
                                <td><?= $c['type'] === 'signup' ? 'Predaj' : 'Ročná odmena' ?></td>
                                <td><?= number_format($c['amount'], 2) ?> €</td>
                                <td><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;<?= $c['status'] === 'paid' ? 'background:rgba(16,185,129,0.12);color:#10b981;' : 'background:rgba(245,158,11,0.12);color:#f59e0b;' ?>">
                                        <?= $c['status'] === 'paid' ? 'Vyplatené' : 'Čaká' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($c['status'] !== 'paid'): ?>
                                    <form method="POST"><input type="hidden" name="form_action" value="mark_paid"><input type="hidden" name="commission_id" value="<?= (int)$c['id'] ?>"><button type="submit" class="btn-secondary" style="padding:5px 12px;font-size:12px;">Označiť ako vyplatené</button></form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$commissions): ?><tr><td colspan="8" style="text-align:center;color:var(--text-secondary);padding:20px;">Zatiaľ žiadne provízie.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
