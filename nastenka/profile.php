<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo  = db_connect();
$user = current_user();
$csrf = csrf_token();

$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        // ... (existing logic)
    } elseif ($action === 'add_event') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $date = $_POST['event_date'] ?? '';
        $end_date = $_POST['end_date'] ?? null;
        $type = $_POST['type'] ?? 'note';
        $desc = $_POST['description'] ?? '';
        
        if ($date) {
            // Swap dates if end_date is before start date
            if ($end_date && $end_date < $date) {
                $temp = $date;
                $date = $end_date;
                $end_date = $temp;
            }
            if ($event_id > 0) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE calendar_events SET event_date = ?, end_date = ?, type = ?, description = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$date, $end_date ?: null, $type, $desc, $event_id, $user['id']]);
                $msg = 'Udalosť bola úspešne upravená.';
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, event_date, end_date, type, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user['id'], $date, $end_date ?: null, $type, $desc]);
                $msg = 'Udalosť bola úspešne pridaná do kalendára.';
            }
            $msg_type = 'success';
        }
    } elseif ($action === 'delete_event') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
        $stmt->execute([$event_id, $user['id']]);
        $msg = 'Udalosť bola odstránená.';
        $msg_type = 'success';
    } elseif ($action === 'update_email') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if ($email || empty(trim($_POST['email']))) {
            try { $pdo->exec("ALTER TABLE crm_users ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL DEFAULT NULL AFTER username"); } catch(Exception $e){}
            $stmt = $pdo->prepare("UPDATE crm_users SET email = ? WHERE id = ?");
            $stmt->execute([$email ?: null, $user['id']]);
            $msg = 'Vaša e-mailová adresa bola úspešne uložená.';
            $msg_type = 'success';
        } else {
            $msg = 'Zadali ste neplatnú e-mailovú adresu.';
            $msg_type = 'error';
        }
    } elseif ($action === 'update_full_name') {
        $new_name = trim($_POST['full_name'] ?? '');
        if (strlen($new_name) >= 2) {
            $stmt = $pdo->prepare("UPDATE crm_users SET full_name = ? WHERE id = ?");
            $stmt->execute([$new_name, $user['id']]);
            $_SESSION['crm_full_name'] = $new_name;
            $msg = 'Zobrazované meno zmenené na \u201e' . htmlspecialchars($new_name) . '\u201c.';
            $msg_type = 'success';
        } else {
            $msg = 'Meno musí mať aspoň 2 znaky.';
            $msg_type = 'error';
        }
    } elseif ($action === 'update_phone') {
        $phone = trim($_POST['phone'] ?? '');
        try { $pdo->exec("ALTER TABLE crm_users ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL DEFAULT NULL AFTER email"); } catch(Exception $e){}
        $stmt = $pdo->prepare("UPDATE crm_users SET phone = ? WHERE id = ?");
        $stmt->execute([$phone, $user['id']]);
        $msg = 'Vaše telefónne číslo bolo úspešne uložené.';
        $msg_type = 'success';
    }
}

// Fetch user's email and phone
try { $pdo->exec("ALTER TABLE crm_users ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL DEFAULT NULL AFTER username"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE crm_users ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL DEFAULT NULL AFTER email"); } catch(Exception $e){}
$stmt = $pdo->prepare("SELECT email, phone FROM crm_users WHERE id = ?");
$stmt->execute([$user['id']]);
$u_data = $stmt->fetch();
$user_email = $u_data['email'] ?? '';
$user_phone = $u_data['phone'] ?? '';

// Fetch user's future events
$stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE user_id = ? AND (event_date >= ? OR end_date >= ?) ORDER BY event_date ASC");
$stmt->execute([$user['id'], date('Y-m-d'), date('Y-m-d')]);
$user_events = $stmt->fetchAll();

$page_title = "Môj profil";
include __DIR__ . '/partials/header.php';
?>

<div>
    <!-- Absence Management Section (Moved to Top) -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div class="card-title"><i class="ti ti-calendar-user"></i> Moje neprítomnosti a poznámky</div>
                <div class="card-subtitle">Plánovanie dovoleniek do spoločného kalendára</div>
            </div>
            <div style="text-align: right; color: var(--text-muted); font-size: 0.8rem;">
                <?php 
                $c = count($user_events);
                $word = 'záznamov';
                if ($c == 1) $word = 'záznam';
                elseif ($c >= 2 && $c <= 4) $word = 'záznamy';
                echo "Máte <strong>$c $word</strong> • Viditeľné pre kolegov";
                ?>
            </div>
        </div>
        
        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> mb-2"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST" id="eventForm" style="background: var(--bg-hover); padding: 2rem; border-radius: var(--radius-md); margin-bottom: 2rem; border: 1px solid var(--border);">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="add_event">
            <input type="hidden" name="event_id" id="edit_event_id" value="0">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label" style="font-weight:700;">Dátum od *</label>
                    <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight:700;">Dátum do (voliteľné)</label>
                    <input type="date" name="end_date" class="form-control" placeholder="dd.mm.rrrr">
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight:700;">Typ udalosti</label>
                    <select name="type" class="form-control" style="cursor:pointer;">
                        <option value="vacation">Dovolenka</option>
                        <option value="pn">PN (Choroba)</option>
                        <option value="doctor">Lekár</option>
                        <option value="note">Poznámka / Iné</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="font-weight:700;">Poznámka (voliteľné)</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Napr. Sťahovanie, kontrola u zubára, práca z domu..."></textarea>
            </div>
            
            <div id="editModeIndicator" style="display:none; margin-bottom: 1rem; padding: 0.5rem 1rem; background: rgba(99,102,241,0.1); border-radius: 4px; border-left: 4px solid var(--accent); font-weight: 700; color: var(--accent-2);">
                <i class="ti ti-edit"></i> Práve upravujete existujúci záznam
                <button type="button" onclick="cancelEdit()" class="btn btn-secondary" style="height: 24px; padding: 0 10px; font-size: 0.7rem; margin-left: 10px;">Zrušiť úpravu</button>
            </div>

            <button type="submit" id="submitBtn" class="btn btn-primary" style="width:100%; font-weight:700;"><i class="ti ti-plus"></i> Pridať záznam do kalendára</button>
        </form>

        <div style="margin-top: 1rem;">
            <h4 style="margin-bottom: 1rem; color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Aktuálne a plánované záznamy</h4>
            <div style="background: var(--bg-base); border-radius: var(--radius-md); overflow: hidden;">
                <table class="table" style="font-size: 0.95rem; margin-bottom: 0;">
                    <thead style="background: rgba(255,255,255,0.03);">
                        <tr>
                            <th style="padding: 1rem;">Dátum</th>
                            <th style="padding: 1rem;">Typ</th>
                            <th style="padding: 1rem;">Poznámka</th>
                            <th style="padding: 1rem; text-align:right;">Akcia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($user_events)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding: 3rem;">Nemáte žiadne plánované neprítomnosti.</td></tr>
                        <?php endif; ?>
                        <?php foreach($user_events as $ev): ?>
                        <tr>
                            <td style="padding: 1rem; font-weight:700; color: var(--accent-2);">
                                <?= date('d.m.Y', strtotime($ev['event_date'])) ?>
                                <?php if($ev['end_date']): ?>
                                    <span style="color: var(--text-muted); font-weight: 400; margin: 0 5px;">–</span> <?= date('d.m.Y', strtotime($ev['end_date'])) ?>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php 
                                if($ev['type'] === 'vacation') echo '<span class="status-badge" style="background:var(--cyan); color:white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem;"><i class="ti ti-beach"></i> Dovolenka</span>';
                                if($ev['type'] === 'pn')       echo '<span class="status-badge" style="background:var(--red); color:white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem;"><i class="ti ti-pill"></i> PN</span>';
                                if($ev['type'] === 'doctor')   echo '<span class="status-badge" style="background:var(--orange); color:white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem;"><i class="ti ti-building-hospital"></i> Lekár</span>';
                                if($ev['type'] === 'note')     echo '<span class="status-badge" style="background:var(--bg-hover); color:var(--text-primary); padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; border:1px solid var(--border);"><i class="ti ti-note"></i> Poznámka</span>';
                                ?>
                            </td>
                            <td style="padding: 1rem; color:var(--text-muted); italic;"><?= htmlspecialchars($ev['description']) ?: '-' ?></td>
                            <td style="padding: 1rem; text-align:right;">
                                <button type="button" class="btn" style="color:var(--accent); opacity: 0.7; transition: opacity 0.2s;" 
                                        onclick='editEvent(<?= json_encode($ev) ?>)'
                                        onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">
                                    <i class="ti ti-edit"></i> <span style="font-size: 0.8rem; margin-left: 5px;">Upraviť</span>
                                </button>
                                <form method="POST" style="display:inline;" data-confirm="Naozaj chcete zmazať túto udalosť?">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                    <button type="submit" class="btn" style="color:var(--red); opacity: 0.7; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">
                                        <i class="ti ti-trash"></i> <span style="font-size: 0.8rem; margin-left: 5px;">Zmazať</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Osobné údaje Section (Combined) -->
    <div class="card mt-2">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="ti ti-user"></i> Osobné údaje</div>
                <div class="card-subtitle">Správa vášho mena a kontaktného e-mailu pre notifikácie.</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; padding: 1rem 0;">
            <!-- Display Name -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update_full_name">
                <div class="form-group">
                    <label class="form-label">Zobrazované meno</label>
                    <input type="text" name="full_name" class="form-control" placeholder="napr. Peter Maršo" value="<?= htmlspecialchars($user['full_name']) ?>" required minlength="2">
                </div>
                <button type="submit" class="btn btn-secondary"><i class="ti ti-device-floppy"></i> <?= !empty($user['full_name']) ? 'Aktualizovať meno' : 'Uložiť meno' ?></button>
            </form>

            <!-- Contact Info -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update_email">
                <div class="form-group">
                    <label class="form-label">E-mail pre notifikácie</label>
                    <input type="email" name="email" class="form-control" placeholder="napr. jozko.mrkvicka@gmail.com" value="<?= htmlspecialchars($user_email ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-secondary"><i class="ti ti-device-floppy"></i> <?= !empty($user_email) ? 'Aktualizovať e-mail' : 'Uložiť e-mail' ?></button>
            </form>

            <!-- Contact Phone -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update_phone">
                <div class="form-group">
                    <label class="form-label">Telefónne číslo</label>
                    <input type="text" name="phone" class="form-control" placeholder="napr. +421 900 123 456" value="<?= htmlspecialchars($user_phone ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-secondary"><i class="ti ti-device-floppy"></i> <?= !empty($user_phone) ? 'Aktualizovať číslo' : 'Uložiť číslo' ?></button>
            </form>
        </div>
    </div>

    <!-- Account Settings Section (Bottom) -->
    <div class="card mt-2">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="ti ti-lock"></i> Zmena prístupového hesla</div>
                <div class="card-subtitle">Tu si môžete zmeniť svoje heslo (<?= htmlspecialchars($user['full_name']) ?>).</div>
            </div>
        </div>
        
        <form method="POST" style="padding: 1rem 0;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="change_password">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Súčasné heslo</label>
                    <input type="password" name="old_password" class="form-control" required placeholder="Staré heslo pre overenie">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nové heslo</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Minimálne 6 znakov" minlength="6">
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-secondary"><i class="ti ti-shield-lock"></i> Aktualizovať heslo</button>
            </div>
        </form>
    </div>
</div>

<script>
function editEvent(data) {
    document.getElementById('edit_event_id').value = data.id;
    document.getElementById('eventForm').querySelector('input[name="event_date"]').value = data.event_date;
    document.getElementById('eventForm').querySelector('input[name="end_date"]').value = data.end_date || '';
    document.getElementById('eventForm').querySelector('select[name="type"]').value = data.type;
    document.getElementById('eventForm').querySelector('textarea[name="description"]').value = data.description || '';
    
    document.getElementById('submitBtn').innerHTML = '<i class="ti ti-device-floppy"></i> Uložiť zmeny záznamu';
    document.getElementById('editModeIndicator').style.display = 'block';
    
    // Scroll to form
    document.getElementById('eventForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function cancelEdit() {
    document.getElementById('edit_event_id').value = '0';
    document.getElementById('eventForm').reset();
    document.getElementById('submitBtn').innerHTML = '<i class="ti ti-plus"></i> Pridať záznam do kalendára';
    document.getElementById('editModeIndicator').style.display = 'none';
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
