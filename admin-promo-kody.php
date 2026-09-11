<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once 'config.php';
require_once 'includes/promo_code_helper.php';
promo_migrate($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'create') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = trim($_POST['type'] ?? '');
    $value = (float)($_POST['value'] ?? 0);
    $max_uses = trim($_POST['max_uses'] ?? '');
    $max_uses = ($max_uses === '') ? null : (int)$max_uses;
    $expires_at = trim($_POST['expires_at'] ?? '');
    $expires_at = ($expires_at === '') ? null : $expires_at . ' 23:59:59';
    $note = trim($_POST['note'] ?? '');

    if ($code === '' || !in_array($type, ['discount_percent', 'discount_fixed', 'free_days'], true) || $value <= 0) {
        $error = 'Vyplňte kód, typ a kladnú hodnotu.';
    } elseif ($type === 'discount_percent' && $value > 100) {
        $error = 'Percentuálna zľava nemôže byť vyššia ako 100 %.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO promo_codes (code, type, value, max_uses, expires_at, note) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $max_uses, $expires_at, $note]);
            $success = "Kód \"$code\" bol vytvorený.";
        } catch (Exception $e) {
            $error = (strpos($e->getMessage(), 'Duplicate') !== false) ? "Kód \"$code\" už existuje." : 'Chyba databázy: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE promo_codes SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
}

$codes = $pdo->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Promo kódy - Administrácia';
require_once 'includes/dashboard-head.php';
?>
<?php $active_nav = 'promo-kody'; $spa_host = false; require_once 'includes/admin-sidebar.php'; ?>
<div class="admin-main">
    <?php $headerTitle = 'Promo kódy'; $headerIcon = 'redeem'; require_once 'includes/dashboard-topbar.php'; ?>
    <div class="admin-content">
        <div class="section">

            <?php if ($success): ?><div style="background:rgba(16,185,129,0.12);color:#10b981;padding:12px 18px;border-radius:10px;margin-bottom:18px;font-size:13.5px;font-weight:600;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.12);color:#ef4444;padding:12px 18px;border-radius:10px;margin-bottom:18px;font-size:13.5px;font-weight:600;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="vueto-card" style="margin-bottom:22px;">
                <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
                    <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined" style="color:var(--primary-color);">add_circle</span> Nový kód</h2>
                </div>
                <div class="vueto-card-body" style="padding:22px 20px;">
                    <form method="POST">
                        <input type="hidden" name="form_action" value="create">
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                            <div class="form-group">
                                <label>Kód</label>
                                <input type="text" name="code" placeholder="napr. KRESLO30" required style="text-transform:uppercase;">
                            </div>
                            <div class="form-group">
                                <label>Typ</label>
                                <select name="type" required>
                                    <option value="discount_percent">Percentuálna zľava na doplnky</option>
                                    <option value="discount_fixed">Pevná zľava na doplnky (€)</option>
                                    <option value="free_days">Predĺženie balíka o X dní</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Hodnota (% / € / dni)</label>
                                <input type="number" name="value" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                            <div class="form-group">
                                <label>Max. počet použití (prázdne = neobmedzené)</label>
                                <input type="number" name="max_uses" min="1">
                            </div>
                            <div class="form-group">
                                <label>Platnosť do (prázdne = bez expirácie)</label>
                                <input type="date" name="expires_at">
                            </div>
                            <div class="form-group">
                                <label>Poznámka (interná)</label>
                                <input type="text" name="note" placeholder="napr. B2B akvizícia salónov">
                            </div>
                        </div>
                        <button type="submit" class="btn-primary" style="padding:10px 22px;">Vytvoriť kód</button>
                    </form>
                </div>
            </div>

            <div class="vueto-card">
                <div class="vueto-card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:18px;">
                    <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined" style="color:var(--primary-color);">list</span> Existujúce kódy (<?= count($codes) ?>)</h2>
                </div>
                <div class="vueto-card-body" style="padding:10px 20px 20px;overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead><tr style="text-align:left;color:var(--text-secondary);font-size:11.5px;text-transform:uppercase;">
                            <th style="padding:10px 8px;">Kód</th><th>Typ</th><th>Hodnota</th><th>Použité</th><th>Platí do</th><th>Stav</th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($codes as $c): ?>
                            <tr style="border-top:1px solid var(--border-color);">
                                <td style="padding:10px 8px;">
                                    <strong><?= htmlspecialchars($c['code']) ?></strong>
                                    <?php if ($c['note']): ?><div style="font-size:11px;color:var(--text-secondary);"><?= htmlspecialchars($c['note']) ?></div><?php endif; ?>
                                </td>
                                <td><?= ['discount_percent' => '% zľava', 'discount_fixed' => '€ zľava', 'free_days' => 'dní naviac'][$c['type']] ?? $c['type'] ?></td>
                                <td><?= htmlspecialchars($c['value']) ?><?= $c['type'] === 'discount_percent' ? '%' : ($c['type'] === 'discount_fixed' ? ' €' : ' dní') ?></td>
                                <td><?= (int)$c['used_count'] ?><?= $c['max_uses'] !== null ? ' / ' . (int)$c['max_uses'] : '' ?></td>
                                <td><?= $c['expires_at'] ? date('d.m.Y', strtotime($c['expires_at'])) : '—' ?></td>
                                <td>
                                    <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;<?= $c['is_active'] ? 'background:rgba(16,185,129,0.12);color:#10b981;' : 'background:rgba(239,68,68,0.12);color:#ef4444;' ?>">
                                        <?= $c['is_active'] ? 'Aktívny' : 'Vypnutý' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="form_action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                        <button type="submit" class="btn-secondary" style="padding:5px 12px;font-size:12px;"><?= $c['is_active'] ? 'Vypnúť' : 'Zapnúť' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$codes): ?><tr><td colspan="7" style="text-align:center;color:var(--text-secondary);padding:20px;">Zatiaľ žiadne kódy.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
