<?php
require_once __DIR__ . '/auth.php';
require_admin();

$pdo  = db_connect();
$user = current_user();
$csrf = csrf_token();
$tab  = $_GET['tab'] ?? 'users';
$msg  = ''; $msg_type = '';

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // Add user
    if ($action === 'add_user') {
        $username  = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'user';
        $password  = $_POST['password'] ?? '';

        if (strlen($password) < 8) {
            $msg = 'Heslo musí mať aspoň 8 znakov.'; $msg_type = 'error';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("INSERT INTO crm_users (username, password_hash, full_name, email, role) VALUES (?,?,?,?,?)")
                    ->execute([$username, $hash, $full_name, $email, $role]);
                $msg = 'Používateľ „' . $full_name . '“ bol vytvorený.'; $msg_type = 'success';
            } catch (PDOException $e) {
                $msg = 'Chyba: Meno používateľa alebo email už existuje.'; $msg_type = 'error';
            }
        }
    }

    // Toggle active
    if ($action === 'toggle_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid !== $user['id']) { // Don't deactivate yourself
            $pdo->prepare("UPDATE crm_users SET is_active = NOT is_active WHERE id=?")->execute([$uid]);
            $msg = 'Stav používateľa zmenený.'; $msg_type = 'success';
        }
    }

    // Delete user
    if ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid !== $user['id']) { // Don't delete yourself
            $pdo->prepare("DELETE FROM crm_users WHERE id=?")->execute([$uid]);
            $msg = 'Používateľ bol vymazaný.'; $msg_type = 'success';
        }
    }

    // Change role
    if ($action === 'change_role') {
        $uid  = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'user';
        if ($uid !== $user['id']) { // Don't demote yourself
            $pdo->prepare("UPDATE crm_users SET role=? WHERE id=?")->execute([$role, $uid]);
            $msg = 'Rola používateľa bola zmenená.'; $msg_type = 'success';
        }
    }

    // Change own password
    if ($action === 'change_password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $me  = $pdo->prepare("SELECT password_hash FROM crm_users WHERE id=?");
        $me->execute([$user['id']]);
        $me = $me->fetch();
        if (!password_verify($old, $me['password_hash'])) {
            $msg = 'Staré heslo je nesprávne.'; $msg_type = 'error';
        } elseif (strlen($new) < 8) {
            $msg = 'Nové heslo musí mať aspoň 8 znakov.'; $msg_type = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE crm_users SET password_hash=? WHERE id=?")->execute([$hash, $user['id']]);
            $msg = 'Heslo bolo zmenené.'; $msg_type = 'success';
        }
    }

    // Toggle bug status
    if ($action === 'toggle_bug_status') {
        $bid = (int)($_POST['bug_id'] ?? 0);
        $pdo->prepare("UPDATE crm_bug_reports SET status = IF(status='fixed', 'new', 'fixed') WHERE id=?")->execute([$bid]);
        $msg = 'Stav hlásenia zmenený.'; $msg_type = 'success';
    }

    // Delete bug report
    if ($action === 'delete_bug') {
        $bid = (int)($_POST['bug_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT screenshot_path FROM crm_bug_reports WHERE id=?");
        $stmt->execute([$bid]);
        $bug = $stmt->fetch();
        if ($bug && $bug['screenshot_path']) {
            $full_path = __DIR__ . '/' . $bug['screenshot_path'];
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
        }
        $pdo->prepare("DELETE FROM crm_bug_reports WHERE id=?")->execute([$bid]);
        $msg = 'Hlásenie bolo vymazané.'; $msg_type = 'success';
    }

    if (!$msg) { header("Location: admin.php?tab=$tab&ok=1"); exit; }
}

// ── Automatická migrácia crm_users ──
try {
    $pdo->exec("ALTER TABLE crm_users 
        ADD COLUMN IF NOT EXISTS last_login DATETIME NULL,
        ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (PDOException $e) { /* Tabuľka už je OK alebo iná chyba */ }

if (isset($_GET['ok'])) { $msg = 'Zmena uložená.'; $msg_type = 'success'; }

// Načítanie používateľov (zoradenie podľa ID ak created_at zlyhá, ale migrácia by ho mala vytvoriť)
try {
    $users_list = $pdo->query("SELECT * FROM crm_users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $users_list = $pdo->query("SELECT * FROM crm_users ORDER BY id DESC")->fetchAll();
}

$page_title = "Administrácia";
include __DIR__ . '/partials/header.php';
?>

<?php if($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="tabs">
    <button class="tab-btn <?= $tab==='users'?'active':'' ?>" onclick="switchTab('tab-users',this)"><i class="ti ti-users"></i> Používatelia</button>
    <button class="tab-btn <?= $tab==='bugs'?'active':'' ?>" onclick="switchTab('tab-bugs',this)"><i class="ti ti-bug"></i> Hlásenia chýb</button>
    <button class="tab-btn <?= $tab==='settings'?'active':'' ?>" onclick="switchTab('tab-settings',this)"><i class="ti ti-lock"></i> Moje heslo</button>
</div>

<!-- USERS TAB -->
<div class="tab-pane <?= $tab==='users'?'active':'' ?>" id="tab-users">

    <!-- Add user form -->
    <div class="card mb-2">
        <div class="card-title mb-2"><i class="ti ti-user-plus"></i> Pridať nového kolegu</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="add_user">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Celé meno</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Peter Varga" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Používateľské meno</label>
                    <input type="text" name="username" class="form-control" placeholder="peter.varga" required pattern="[a-z0-9._-]+">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="peter@vueto.sk" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Heslo (min. 8 znakov)</label>
                    <input type="password" name="password" class="form-control" minlength="8" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label">Rola</label>
                    <select name="role" class="form-control">
                        <option value="user">Kolega (user)</option>
                        <option value="admin">Admin</option>
                        <option value="zakaznik">Zákazník</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary" type="submit"><i class="ti ti-plus"></i> Vytvoriť účet</button>
        </form>
    </div>

    <!-- Users list -->
    <div class="card" style="padding:0;overflow:hidden;">
        <div class="card-header" style="padding:1.25rem 1.5rem;">
            <div class="card-title">Tímové účty (<?= count($users_list) ?>/5+)</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Meno</th>
                    <th>Meno účtu</th>
                    <th>Email</th>
                    <th>Rola</th>
                    <th>Posledné prihlásenie</th>
                    <th>Stav</th>
                    <th>Akcia</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($users_list as $u): ?>
            <tr>
                <td data-label="Meno">
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;">
                            <?= strtoupper(substr($u['full_name'],0,1)) ?>
                        </div>
                        <?= htmlspecialchars($u['full_name']) ?>
                        <?php if($u['id'] == $user['id']): ?><span class="tag" style="font-size:.65rem;">ja</span><?php endif; ?>
                    </div>
                </td>
                <td data-label="Meno účtu" class="text-muted text-sm"><?= htmlspecialchars($u['username']) ?></td>
                <td data-label="Email" class="text-sm"><?= htmlspecialchars($u['email']) ?></td>
                <td data-label="Rola">
                    <?php if($u['id'] != $user['id']): ?>
                    <form method="POST" style="margin:0;" id="role-form-<?= $u['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="role" id="role-input-<?= $u['id'] ?>" value="<?= $u['role'] ?>">
                        
                        <?php 
                            $role_label = $u['role']==='admin'?'Admin':($u['role']==='zakaznik'?'Zákazník':'Kolega');
                            $role_class = $u['role']==='admin'?'status-progress':($u['role']==='zakaznik'?'status-done':'status-contacted');
                        ?>
                        <span class="status-badge <?= $role_class ?>" 
                              style="cursor:pointer; user-select:none; font-weight:700; letter-spacing:0.5px;" 
                              onclick="cycleRole(<?= $u['id'] ?>)" 
                              title="Kliknutím prepnete rolu">
                            <?= $role_label ?>
                        </span>
                    </form>
                    <?php else: ?>
                    <span class="status-badge" style="background:var(--yellow); color:#000; font-weight:800; border:none; box-shadow:0 0 15px rgba(245,158,11,0.3);">ADMIN (JA)</span>
                    <?php endif; ?>
                </td>
                <td data-label="Prihlásenie" class="text-muted text-sm"><?= $u['last_login'] ? time_ago($u['last_login']) : 'Nikdy' ?></td>
                <td data-label="Stav">
                    <span class="status-badge <?= $u['is_active']?'status-done':'status-rejected' ?>">
                        <?= $u['is_active']?'Aktívny':'Neaktívny' ?>
                    </span>
                </td>
                <td data-label="Akcia">
                    <?php if($u['id'] != $user['id']): ?>
                    <form method="POST" style="display:inline" data-confirm="Zmeniť stav tohto používateľa?">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="toggle_user">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm <?= $u['is_active']?'btn-secondary':'btn-green' ?>" type="submit">
                            <?= $u['is_active']?'Deaktivovať':'Aktivovať' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline" data-confirm="Naozaj chcete ÚPLNE VYMAZAŤ tohto používateľa? Táto akcia je nevratná.">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit" title="Vymazať">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- BUG REPORTS TAB -->
<div class="tab-pane <?= $tab==='bugs'?'active':'' ?>" id="tab-bugs">
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="card-header" style="padding:1.25rem 1.5rem;">
            <div class="card-title">Hlásenia od kolegov</div>
        </div>
        <?php
            $bugs = $pdo->query("SELECT b.*, u.full_name FROM crm_bug_reports b 
                                 JOIN crm_users u ON b.user_id = u.id 
                                 ORDER BY b.created_at DESC")->fetchAll();
            if (empty($bugs)):
        ?>
            <div style="padding:40px; text-align:center; color:var(--text-muted);">
                <i class="ti ti-check" style="font-size:2rem; margin-bottom:10px;"></i>
                <p>Momentálne nie sú nahlásené žiadne chyby.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dátum</th>
                            <th>Kolega</th>
                            <th>Popis chyby</th>
                            <th>Technické dáta</th>
                            <th>Screenshot</th>
                            <th>Stav</th>
                            <th>Akcia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bugs as $b): ?>
                        <tr style="vertical-align: top;">
                            <td class="text-sm"><?= date('d.m.Y H:i', strtotime($b['created_at'])) ?></td>
                            <td class="text-sm"><strong><?= htmlspecialchars($b['full_name']) ?></strong></td>
                            <td style="max-width:300px;">
                                <div style="font-size:0.9rem; line-height:1.4; color:var(--text-primary);">
                                    <?= nl2br(htmlspecialchars($b['description'])) ?>
                                </div>
                            </td>
                            <td class="text-xs text-muted">
                                <div><strong>URL:</strong> <?= htmlspecialchars($b['url']) ?></div>
                                <div style="margin-top:5px; max-width:200px; overflow:hidden; text-overflow:ellipsis;"><strong>Browser:</strong> <?= htmlspecialchars($b['user_agent']) ?></div>
                            </td>
                            <td>
                                <?php if($b['screenshot_path']): ?>
                                    <a href="<?= htmlspecialchars($b['screenshot_path']) ?>" target="_blank">
                                        <img src="<?= htmlspecialchars($b['screenshot_path']) ?>" style="width:80px; height:80px; object-fit:cover; border-radius:10px; border:1px solid var(--border); box-shadow:0 4px 12px rgba(0,0,0,0.2); transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div style="display:none; width:80px; height:80px; border-radius:10px; background:rgba(255,255,255,0.05); border:1px dashed var(--border); align-items:center; justify-content:center; color:var(--text-muted); font-size:0.7rem; text-align:center; padding:5px;">Chýba súbor</div>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="toggle_bug_status">
                                    <input type="hidden" name="bug_id" value="<?= $b['id'] ?>">
                                    <span class="status-badge <?= $b['status']==='new'?'status-new':($b['status']==='fixed'?'status-done':'status-progress') ?>" 
                                          style="cursor:pointer; font-weight:800;" 
                                          onclick="this.closest('form').submit()">
                                        <?= strtoupper($b['status']) ?>
                                    </span>
                                </form>
                            </td>
                            <td>
                                <form method="POST" style="display:inline" data-confirm="Naozaj vymazať toto hlásenie o chybe?">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete_bug">
                                    <input type="hidden" name="bug_id" value="<?= $b['id'] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SETTINGS TAB -->
<div class="tab-pane <?= $tab==='settings'?'active':'' ?>" id="tab-settings">
    <div class="card">
        <div class="card-title mb-2"><i class="ti ti-lock"></i> Zmena hesla</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label class="form-label">Aktuálne heslo</label>
                <input type="password" name="old_password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label class="form-label">Nové heslo (min. 8 znakov)</label>
                <input type="password" name="new_password" class="form-control" minlength="8" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> Zmeniť heslo</button>
        </form>
    </div>
</div>

<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

function cycleRole(userId) {
    const input = document.getElementById('role-input-' + userId);
    const form  = document.getElementById('role-form-' + userId);
    const current = input.value;
    
    let next = 'user';
    if (current === 'user') next = 'admin';
    else if (current === 'admin') next = 'zakaznik';
    else next = 'user';
    
    input.value = next;
    form.submit();
}

// Global confirm handler
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.dataset.confirm) {
        if (!confirm(form.dataset.confirm)) {
            e.preventDefault();
        }
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
