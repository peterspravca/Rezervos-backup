<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo  = db_connect();
$user = current_user();
$csrf = csrf_token();

$msg = ''; $msg_type = '';

// Automatická migrácia stĺpcov
try { $pdo->exec("ALTER TABLE crm_board ADD COLUMN target_user_id INT NULL DEFAULT NULL AFTER user_id"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_board ADD COLUMN sort_order INT NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_board ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal' AFTER pin_to_top"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_board ADD COLUMN event_date DATE NULL DEFAULT NULL AFTER priority"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_board ADD COLUMN event_time TIME NULL DEFAULT NULL AFTER event_date"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE crm_board ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'new' AFTER event_time"); } catch (PDOException $e) {}
// Per-user order table
$pdo->exec("CREATE TABLE IF NOT EXISTS crm_board_order (
    user_id   INT NOT NULL,
    board_id  INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    column_id  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, board_id)
)");
try { $pdo->exec("ALTER TABLE crm_board_order ADD COLUMN column_id INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}

// Confirmations table
$pdo->exec("CREATE TABLE IF NOT EXISTS crm_board_confirmations (
    board_id INT NOT NULL,
    user_id INT NOT NULL,
    note VARCHAR(255) NULL,
    confirmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (board_id, user_id),
    FOREIGN KEY (board_id) REFERENCES crm_board(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES crm_users(id) ON DELETE CASCADE
)");
try { $pdo->exec("ALTER TABLE crm_board_confirmations ADD COLUMN note VARCHAR(255) NULL AFTER user_id"); } catch(Exception $e) {}

// ── AJAX: reorder (per-user) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reorder') {
    csrf_check();
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (is_array($ids)) {
        $stmt = $pdo->prepare(
            "INSERT INTO crm_board_order (user_id, board_id, sort_order, column_id)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), column_id = VALUES(column_id)"
        );
        foreach ($ids as $col_idx => $col_ids) {
            foreach ($col_ids as $pos => $id) {
                $stmt->execute([$user['id'], (int)$id, (int)$pos, (int)$col_idx]);
            }
        }
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX: confirm read ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_read') {
    csrf_check();
    $bid = (int)$_POST['board_id'];
    $note = trim($_POST['note'] ?? '');
    
    $stmt = $pdo->prepare("INSERT INTO crm_board_confirmations (board_id, user_id, note) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE note = VALUES(note)");
    $stmt->execute([$bid, $user['id'], $note ?: null]);

    if (stripos($note, 'Vybavené') !== false) {
        $pdo->prepare("UPDATE crm_board SET status = 'done' WHERE id = ?")->execute([$bid]);
    }

    // Recalculate unread count for the sidebar
    $u_id_calc = $user['id'];
    $unread_count = $pdo->query("SELECT COUNT(*) FROM crm_board b 
        WHERE b.user_id != $u_id_calc 
        AND NOT EXISTS (SELECT 1 FROM crm_board_confirmations c WHERE c.board_id = b.id AND c.user_id = $u_id_calc)")->fetchColumn();

    echo json_encode(['ok' => true, 'user_name' => $user['full_name'], 'note' => $note, 'unread_count' => (int)$unread_count]);
    exit;
}

// ── POST: add/edit/delete board item ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $color   = $_POST['color'] ?? '#6366f1';
        $pin     = isset($_POST['pin_to_top']) ? 1 : 0;
        $target  = $_POST['target_user_id'] ?? '';
        $target_id = ($target && $target !== 'all') ? (int)$target : null;
        
        $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
        $event_time = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
        
        $uploaded_count = 0;
        // MULTIPLE AUDIO UPLOADS
        if (isset($_FILES['audio_blob']) && is_array($_FILES['audio_blob']['name'])) {
            $dir = __DIR__ . '/uploads/voice';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            foreach($_FILES['audio_blob']['name'] as $i => $name) {
                if ($_FILES['audio_blob']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: 'webm';
                    $filename = time() . '_' . rand(1000,9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['audio_blob']['tmp_name'][$i], $dir . '/' . $filename)) {
                        $content .= "\n\n[[AUDIO::uploads/voice/" . $filename . "]]";
                        $uploaded_count++;
                    }
                }
            }
        }

        // MULTIPLE DOCUMENT UPLOADS
        if (isset($_FILES['doc_attachment']) && is_array($_FILES['doc_attachment']['name'])) {
            $dir = __DIR__ . '/uploads/docs';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $allowed = ['doc', 'docx', 'pdf', 'eml', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach($_FILES['doc_attachment']['name'] as $i => $orig_name) {
                if ($_FILES['doc_attachment']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $base_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", pathinfo($orig_name, PATHINFO_FILENAME));
                        $filename = time() . '_' . $base_name . '.' . $ext;
                        if (move_uploaded_file($_FILES['doc_attachment']['tmp_name'][$i], $dir . '/' . $filename)) {
                            $content .= "\n\n[[FILE::uploads/docs/" . $filename . "|" . $orig_name . "]]";
                            $uploaded_count++;
                        }
                    }
                }
            }
        }

        if ($title && trim($content)) {
            $priority = $_POST['priority'] ?? 'normal';
            $status   = $_POST['status'] ?? 'new';
            $stmt = $pdo->prepare("INSERT INTO crm_board (user_id, target_user_id, title, content, color, pin_to_top, priority, event_date, event_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $target_id, $title, $content, $color, $pin, $priority, $event_date, $event_time, $status]);
            $inserted_id = $pdo->lastInsertId();
            
            // --- E-MAILOVÉ NOTIFIKÁCIE ---
            try {
                // Pokus o pridanie stĺpca email, ak neexistuje
                $pdo->exec("ALTER TABLE crm_users ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL DEFAULT NULL AFTER username");
            } catch (Exception $e) {}

            $smtp_config = [
                'host' => 'mail.usr.sk',
                'port' => 465,
                'user' => 'info@vueto.sk',
                'pass' => '020225Pem@',
                'from' => 'info@vueto.sk',
                'from_name' => 'VUETO CRM'
            ];

            if (!function_exists('send_crm_smtp_mail')) {
                function send_crm_smtp_mail($to, $subject, $body, $config) {
                    if (empty($to)) return false;
                    $message_id = "<" . md5(uniqid(time())) . "@vueto.sk>";
                    $header = "From: \"" . $config['from_name'] . "\" <" . $config['from'] . ">\r\n";
                    $header .= "Reply-To: <" . $config['from'] . ">\r\n";
                    $header .= "To: <" . $to . ">\r\n";
                    $header .= "Message-ID: " . $message_id . "\r\n";
                    $header .= "Date: " . date("r") . "\r\n";
                    $header .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
                    $header .= "MIME-Version: 1.0\r\n";
                    $header .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $header .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                    $message = $header . $body . "\r\n.\r\n";

                    $errno = ""; $errstr = "";
                    $socket = @fsockopen("ssl://" . $config['host'], $config['port'], $errno, $errstr, 5);
                    if (!$socket) {
                        file_put_contents(__DIR__ . '/smtp_debug.log', date('Y-m-d H:i:s') . " - Socket Error: $errstr ($errno) to $to\n", FILE_APPEND);
                        return false;
                    }

                    $log = "Connecting to " . $config['host'] . " for $to...\n";
                    $log .= fgets($socket, 515);
                    fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
                    while ($line = fgets($socket, 515)) { $log .= $line; if (substr($line, 3, 1) == " ") break; }

                    fputs($socket, "AUTH LOGIN\r\n"); $log .= fgets($socket, 515);
                    fputs($socket, base64_encode($config['user']) . "\r\n"); $log .= fgets($socket, 515);
                    fputs($socket, base64_encode($config['pass']) . "\r\n"); $response = fgets($socket, 515);
                    $log .= $response;
                    if (substr($response, 0, 3) != '235') { 
                        file_put_contents(__DIR__ . '/smtp_debug.log', date('Y-m-d H:i:s') . " - AUTH ERROR to $to:\n$log\n", FILE_APPEND);
                        fclose($socket); return false; 
                    }

                    fputs($socket, "MAIL FROM: <" . $config['from'] . ">\r\n"); $log .= fgets($socket, 515);
                    fputs($socket, "RCPT TO: <" . $to . ">\r\n"); $log .= fgets($socket, 515);
                    fputs($socket, "DATA\r\n"); $log .= fgets($socket, 515);
                    fputs($socket, $message); $response = fgets($socket, 515);
                    $log .= $response;
                    fputs($socket, "QUIT\r\n"); fclose($socket);
                    
                    file_put_contents(__DIR__ . '/smtp_debug.log', date('Y-m-d H:i:s') . " - DELIVERY to $to:\n$log\n-------------------\n", FILE_APPEND);
                    
                    return substr($response, 0, 3) == '250';
                }
            }

            // Získanie cieľových e-mailov
            $recipients = [];
            if ($target_id) {
                // Konkrétny používateľ
                $stmt = $pdo->prepare("SELECT email, full_name FROM crm_users WHERE id = ? AND email IS NOT NULL AND email != '' AND full_name != 'Administrátor'");
                $stmt->execute([$target_id]);
                $recipients = $stmt->fetchAll();
            } else {
                // Všetci aktívni okrem odosielateľa
                $stmt = $pdo->prepare("SELECT email, full_name FROM crm_users WHERE is_active = 1 AND id != ? AND email IS NOT NULL AND email != '' AND full_name != 'Administrátor'");
                $stmt->execute([$user['id']]);
                $recipients = $stmt->fetchAll();
            }

            // Odoslanie notifikácií na pozadí (pre zrýchlenie to v PHP ide sekvencne, tak použijeme rýchly timeout)
            foreach ($recipients as $r) {
                $safe_title = htmlspecialchars($title);
                $mail_body = "<html><body style='font-family: 'Outfit', sans-serif; color: #333;'>";
                $mail_body .= "<h3>Dobrý deň {$r['full_name']},</h3>";
                $mail_body .= "<p>používateľ <strong>{$user['full_name']}</strong> pridal na tímovú tabuľu nový odkaz:</p>";
                $mail_body .= "<div style='background: #f4f4f5; padding: 15px; border-left: 4px solid #6366f1; margin: 20px 0;'><strong>{$safe_title}</strong></div>";
                $mail_body .= "<p>Prihláste sa prosím do CRM pre zobrazenie detailov a prípadných príloh.</p>";
                $mail_body .= "<p>S pozdravom,<br><strong>Tím VUETO CRM</strong></p>";
                $mail_body .= "</body></html>";
                send_crm_smtp_mail($r['email'], "Nový odkaz na nástenke - {$user['full_name']}", $mail_body, $smtp_config);
            }
            // --- KONIEC EMAILOVEJ LOGIKY ---

            $msg = 'Odkaz bol pridaný' . ($uploaded_count > 0 ? " (vrátane $uploaded_count príloh)!" : "."); 
            $msg_type = 'success';
        }
    }
    if ($action === 'delete') {
        $bid = (int)($_POST['board_id'] ?? 0);
        // Only own notes or admin can delete
        $check = $pdo->prepare("SELECT user_id, content FROM crm_board WHERE id=?");
        $check->execute([$bid]);
        $row = $check->fetch();
        if ($row && ($row['user_id'] == $user['id'] || is_admin())) {
            // Delete physical files
            preg_match_all('/\[\[AUDIO::(.*?)\]\]/', $row['content'], $audios);
            foreach($audios[1] as $a) { if(file_exists(__DIR__.'/'.$a)) unlink(__DIR__.'/'.$a); }

            preg_match_all('/\[\[FILE::(.*?)\|.*?\]\]/', $row['content'], $files);
            foreach($files[1] as $f) { if(file_exists(__DIR__.'/'.$f)) unlink(__DIR__.'/'.$f); }

            $pdo->prepare("DELETE FROM crm_board WHERE id=?")->execute([$bid]);
            $msg = 'Zmazané (vrátane príloh).'; $msg_type = 'success';
        }
    }
    if ($action === 'edit') {
        $bid = (int)($_POST['board_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        // Only own notes or admin can edit
        $check = $pdo->prepare("SELECT user_id FROM crm_board WHERE id=?");
        $check->execute([$bid]);
        $row = $check->fetch();
        if ($row && ($row['user_id'] == $user['id'] || is_admin())) {
            if ($title && $content) {
                $priority = $_POST['priority'] ?? 'normal';
                $status   = $_POST['status'] ?? 'new';
                $ev_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
                $ev_time = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
                $pdo->prepare("UPDATE crm_board SET title=?, content=?, priority=?, status=?, event_date=?, event_time=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                    ->execute([$title, $content, $priority, $status, $ev_date, $ev_time, $bid]);
                $msg = 'Odkaz bol upravený.'; $msg_type = 'success';
            }
        }
    }
    if ($action === 'toggle_pin') {
        $bid = (int)($_POST['board_id'] ?? 0);
        $pdo->prepare("UPDATE crm_board SET pin_to_top = 1 - pin_to_top WHERE id = ?")->execute([$bid]);
        $msg = 'Stav pripnutia bol upravený.'; $msg_type = 'success';
    }
    header("Location: board.php?ok=" . urlencode($msg));
    exit;
}
if (isset($_GET['ok'])) { $msg = $_GET['ok']; $msg_type = 'success'; }

$show_form = isset($_GET['new']);

// Fetch active users for the dropdown
$active_users = $pdo->query("SELECT id, full_name FROM crm_users WHERE is_active=1 AND full_name != 'Administrátor' ORDER BY full_name")->fetchAll();

// Fetch all board items in this user's preferred order
$items = $pdo->prepare(
    "SELECT b.*, u.full_name, t.full_name AS target_name,
            COALESCE(o.sort_order, 9999) AS my_order,
            COALESCE(o.column_id, 0) AS column_id
     FROM crm_board b
     JOIN crm_users u ON b.user_id = u.id
     LEFT JOIN crm_users t ON b.target_user_id = t.id
     LEFT JOIN crm_board_order o ON o.board_id = b.id AND o.user_id = ?
     ORDER BY b.pin_to_top DESC, column_id ASC, my_order ASC, b.created_at DESC"
);
$items->execute([$user['id']]);
$items = $items->fetchAll();

// Fetch confirmations for all items
$confirmations = [];
$conf_stmt = $pdo->query("SELECT bc.*, u.full_name FROM crm_board_confirmations bc JOIN crm_users u ON bc.user_id = u.id WHERE u.full_name != 'Administrátor' ORDER BY bc.confirmed_at ASC");
while ($row = $conf_stmt->fetch()) {
    $confirmations[$row['board_id']][] = $row;
}

$colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#a855f7','#06b6d4','#f97316','#ec4899'];

$page_title = "Tabuľa";
include __DIR__ . '/partials/header.php';
?>

<?php if($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<style>
    /* Mobile-Specific Overrides for Board Form */
    @media (max-width: 768px) {
        .bf-header { flex-direction: column !important; align-items: flex-start !important; gap: 1rem !important; }
        .bf-header-actions { flex-direction: column !important; align-items: flex-start !important; width: 100% !important; gap: 1rem !important; }
        .bf-quick-examples { flex-wrap: wrap !important; justify-content: flex-start !important; gap: 0.5rem !important; }
        .bf-quick-examples span { width: 100% !important; margin-bottom: 0.2rem !important; }
        .bf-body { grid-template-columns: 1fr !important; gap: 1rem !important; }
        .bf-footer { padding: 0.5rem 1rem 1.5rem 1rem !important; }
        .bf-footer-row { flex-direction: column !important; align-items: stretch !important; width: 100% !important; gap: 0.75rem !important; }
        .bf-footer-row label, .bf-footer-row button { width: 100% !important; min-width: unset !important; margin: 0 !important; }
        .priority-selector.compact { flex-direction: row !important; flex-wrap: wrap !important; gap: 0.5rem !important; }
        .priority-selector.compact label { flex: 1 !important; min-width: 100px !important; text-align: center !important; }
        #cal-fields { flex-direction: column !important; }
    }
</style>

<div id="board-form-container" style="display:none; margin-bottom:2rem; animation: slideDown 0.3s ease;">
    <form action="board.php" method="POST" class="card" style="border-color:var(--accent); position:relative; overflow:hidden;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="add">
        <div class="card-header bf-header" style="padding:1rem 1.5rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem; display:flex; align-items:center; gap:.5rem;">
                <i class="ti ti-edit" style="color:var(--accent); font-size:1.2rem;"></i>
                Nový odkaz alebo správa
            </h3>
            <div class="bf-header-actions" style="display:flex; align-items:center; gap:1.5rem;">
                <div class="bf-quick-examples" style="font-size:0.75rem; color:var(--text-muted); display:flex; gap:0.75rem; align-items:center;" id="form-quick-examples">
                    <span style="opacity:0.6;">Rýchle vzory:</span>
                    <button type="button" onclick="setTemplate('Hovor', 'Zavolať späť na číslo: ', 'high')" class="btn btn-secondary" style="font-size:0.75rem; height:32px; border-radius:50px;">
                        <i class="ti ti-phone"></i> Hovor
                    </button>
                    <button type="button" onclick="setTemplate('Stretnutie', 'Dohodnuté stretnutie na tému: ', 'normal')" class="btn btn-secondary" style="font-size:0.75rem; height:32px; border-radius:50px;">
                        <i class="ti ti-calendar"></i> Stretnutie
                    </button>
                    <button type="button" onclick="setTemplate('DÔLEŽITÉ', '', 'urgent')" class="btn btn-secondary" style="font-size:0.75rem; height:32px; border-radius:50px;">
                        <i class="ti ti-alert-octagon"></i> Urgent
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" onclick="toggleBoardForm()">
                    <i class="ti ti-arrow-back-up"></i> Späť na tabuľu
                </button>
            </div>
        </div>
        
        <div class="card-body bf-body" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:1.5rem; padding:1.5rem;">
            <div class="form-group">
                <label class="form-label">Nadpis správy</label>
                <input type="text" name="title" id="form-title" class="form-control" placeholder="Napr. Dôležité: zavolajte Novákovi..." required>
            </div>
            <div class="form-group">
                <label class="form-label">Pre koho je správa?</label>
                <select name="target_user_id" class="form-control">
                    <option value="all">Pre všetkých (verejné)</option>
                    <?php foreach($active_users as $au): ?>
                    <option value="<?= $au['id'] ?>"><?= htmlspecialchars($au['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Stav</label>
                <div class="priority-selector compact status-selector">
                    <label class="p-opt opt-status-new"><input type="radio" name="status" value="new" checked><span>Nové</span></label>
                    <label class="p-opt opt-status-progress"><input type="radio" name="status" value="in_progress"><span>Prebieha</span></label>
                    <label class="p-opt opt-status-done"><input type="radio" name="status" value="done"><span>Vybavené</span></label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Priorita</label>
                <div class="priority-selector compact">
                    <label class="p-opt normal"><input type="radio" name="priority" value="normal" id="p-normal-new" checked><span>Normálna</span></label>
                    <label class="p-opt high"><input type="radio" name="priority" value="high" id="p-high-new"><span>Vysoká</span></label>
                    <label class="p-opt urgent"><input type="radio" name="priority" value="urgent" id="p-urgent-new"><span>Urgent</span></label>
                </div>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Obsah alebo Hlasová správa</label>
                <textarea name="content" class="form-control mb-1" rows="3" placeholder="Napíšte správu pre kolegu alebo odkaz..."></textarea>
                
                <div class="grid-2" style="margin-top:.75rem;">
                    <!-- Voice Recording Area -->
                    <div class="upload-zone" id="voice-zone" onclick="toggleRecording()">
                        <div class="icon-box" id="voice-icon"><i class="ti ti-microphone"></i></div>
                        <div class="zone-label" id="record-btn-text">Nahrať hlasovú správu</div>
                        <div class="zone-sub" id="record-label">Kliknutím spustíte nahrávanie</div>
                        <input type="file" name="audio_blob[]" id="audio_input" accept="audio/*" style="display:none;" multiple>
                    </div>
                    
                    <!-- Document Upload Area -->
                    <div class="upload-zone" onclick="document.getElementById('doc_input').click()">
                        <input type="file" name="doc_attachment[]" id="doc_input" style="display:none;" onchange="updateFileInfo(this)" accept=".doc,.docx,.pdf,.eml,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.webp" multiple>
                        <div id="doc-placeholder">
                            <div class="icon-box"><i class="ti ti-file-upload"></i></div>
                            <div class="zone-label">Priložiť súbor / obrázok</div>
                            <div class="zone-sub">(PDF, Word, Obrázok, Archív...)</div>
                        </div>
                        <div id="doc-info" style="display:none; flex-direction:column; align-items:center; width:100%;">
                            <!-- Staged files here via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Photoshop Mockup: Right-aligned cluster (Reverted to original position but higher) -->
        <div class="bf-footer" style="margin-top:0.2rem; padding: 0 1.5rem 1.5rem 1.5rem;">
            <div class="bf-footer-row" style="display:flex; justify-content:flex-end; gap:0.75rem; flex-wrap:wrap; align-items:center;">
                <!-- 1. Kalendár -->
                <label id="calendar-toggle" style="display:flex; align-items:center; gap:.75rem; cursor:pointer; padding:.65rem 1.25rem; border-radius:10px; border:1px solid var(--border); background:rgba(255,255,255,0.03); font-size:.85rem; color:var(--text-secondary); transition:all .2s; user-select:none; margin:0; min-width:180px; justify-content:center;" title="Pridať tento odkaz do kalendára">
                    <input type="checkbox" id="cal-cb" onchange="document.getElementById('cal-fields').style.display=this.checked?'flex':'none'" style="cursor:pointer; width:15px; height:15px;">
                    <i class="ti ti-calendar-plus" style="font-size:1.1rem;"></i>
                    <span style="font-weight:600;">Do kalendára</span>
                </label>

                <!-- 2. Pripnutie -->
                <label id="pin-toggle" style="display:flex; align-items:center; gap:.75rem; border:1px solid var(--border); background:rgba(255,255,255,0.03); padding:.65rem 1.25rem; border-radius:10px; font-size:.85rem; color:var(--text-secondary); cursor:pointer; transition:all .2s; user-select:none; margin:0; min-width:180px; justify-content:center;">
                    <input type="checkbox" name="pin_to_top" id="pin-cb" style="display:none;">
                    <i id="pin-icon" class="ti ti-pin" style="font-size:1.1rem;"></i>
                    <span id="pin-label" style="font-weight:600;">Pripnúť na vrch</span>
                </label>

                <!-- 3. Pridať na tabuľu -->
                <button class="btn btn-primary" type="submit" style="display:flex; align-items:center; gap:.75rem; padding:.65rem 1.5rem; border-radius:10px; font-weight:800; border:none; box-shadow:0 4px 15px var(--accent-glow); min-width:180px; justify-content:center;">
                    <i class="ti ti-plus" style="font-size:1.1rem;"></i>
                    <span>Pridať na tabuľu</span>
                </button>
            </div>
            
            <!-- Calendar Fields (Expandable) -->
            <div id="cal-fields" style="display:none; gap:1rem; flex-wrap:wrap; margin-top:1.5rem; padding:1.2rem; background:rgba(99,102,241,0.05); border-radius:12px; border:1px solid rgba(99,102,241,0.2); animation: slideDown 0.3s ease;">
                <div style="flex:1; min-width:150px;">
                    <label class="form-label" style="font-size:0.75rem; opacity:0.8; color:var(--accent);">Dátum v kalendári</label>
                    <input type="date" name="event_date" class="form-control" style="background:var(--bg-base); border-color:rgba(99,102,241,0.3);">
                </div>
                <div style="flex:1; min-width:120px;">
                    <label class="form-label" style="font-size:0.75rem; opacity:0.8; color:var(--accent);">Čas (voliteľné)</label>
                    <input type="time" name="event_time" class="form-control" style="background:var(--bg-base); border-color:rgba(99,102,241,0.3);">
                </div>
            </div>
        </div>
    </form>
</div>
<script>
(function(){
    var lbl = document.getElementById('pin-toggle');
    var cb  = document.getElementById('pin-cb');
    var icn = document.getElementById('pin-icon');
    var txt = document.getElementById('pin-label');
    if (!lbl) return;
    lbl.addEventListener('click', function(){
        cb.checked = !cb.checked;
        if (cb.checked) {
            lbl.style.background = 'rgba(99,102,241,.18)';
            lbl.style.borderColor = 'var(--accent)';
            lbl.style.color = 'var(--accent-2)';
            icn.className = 'ti ti-pin-filled';
            txt.textContent = 'Pripnuté ✓';
        } else {
            lbl.style.background = 'rgba(255,255,255,0.03)';
            lbl.style.borderColor = 'var(--border)';
            lbl.style.color = 'var(--text-secondary)';
            icn.className = 'ti ti-pin';
            txt.textContent = 'Pripnúť na vrch';
        }
    });
})();

function toggleBoardForm() {
    var container = document.getElementById('board-form-container');
    var main = document.getElementById('board-main-content');
    if (!container || !main) return;
    
    if (container.style.display === 'none' || container.style.display === '') {
        container.style.display = 'block';
        main.style.display = 'none'; // HIDE cards and search
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        container.style.display = 'none';
        main.style.display = 'block'; // SHOW cards and search
    }
}

function setTemplate(title, content, priority) {
    document.getElementById('form-title').value = title;
    document.getElementsByName('content')[0].value = content;
    
    // Set priority
    const rad = document.getElementById('p-' + priority + '-new');
    if (rad) rad.checked = true;
    
    // Focus content
    document.getElementsByName('content')[0].focus();
}
</script>

<style>
/* Board Filter Overrides to match index.php exactly */
.filter-bar input[type="search"] {
    background: var(--bg-card); 
    border: 1px solid var(--border);
    color: var(--text-primary); 
    border-radius: var(--radius-sm);
    font-family: inherit; font-size: .875rem;
    transition: border-color .15s;
}

.board-filters-group { display: flex; gap: 0.5rem; align-items: center; }
.board-filters-group .filter-pill.active { background: var(--accent) !important; color: white !important; border-color: var(--accent) !important; }

@media (max-width: 900px) {
    .filter-bar { flex-direction: column; align-items: stretch; }
    .board-filters-group { flex-direction: column; width: 100%; }
    .board-filters-group .filter-pill { width: 100%; text-align: center; }
}

/* Card Action Buttons (Minimalist) */
.note-actions { display: flex; gap: 6px; align-items: center; }
.note-actions .btn-action { transition: all 0.2s; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #64748b; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); }
.note-actions .btn-action:hover { background: rgba(255, 255, 255, 0.06); }
.note-actions .btn-action.btn-pin:hover { color: #e2e8f0; }
.note-actions .btn-action.btn-edit:hover { color: #f59e0b; }
.note-actions .btn-action.btn-trash:hover { color: #ef4444; }
.note-actions .btn-action.btn-pinned { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.1); }
</style>

<div id="board-main-content">

<form method="GET" class="filter-bar" style="margin-bottom: 2rem; display:flex; gap:1.5rem; align-items:center;">
    <input type="search" id="board-search" placeholder="   Hľadaj v odkazoch..." onkeyup="searchBoard()" style="flex: 1; padding-left: 2.5rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%239da3c8%22 stroke-width=%222.5%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 class=%22icon icon-tabler icons-tabler-outline icon-tabler-search%22><path stroke=%22none%22 d=%22M0 0h24v24H0z%22 fill=%22none%22/><path d=%22M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0%22 /><path d=%22M21 21l-6 -6%22 /></svg>'); background-repeat: no-repeat; background-position: 0.8rem center;">
    
    <div class="board-filters-group">
        <button type="button" class="btn btn-secondary filter-pill active" onclick="filterBoard('all', this)">Všetko</button>
        <button type="button" class="btn btn-secondary filter-pill" onclick="filterBoard('mine', this)">Moje</button>
        <button type="button" class="btn btn-secondary filter-pill" onclick="filterBoard('unread', this)">Nové</button>
        <button type="button" class="btn btn-secondary filter-pill" onclick="filterBoard('progress', this)">Prebieha</button>
        <button type="button" class="btn btn-secondary filter-pill" onclick="filterBoard('done', this)">Vybavené</button>
        
        <span style="width:1px; height:20px; background:var(--border); margin:0 0.5rem; display:block; opacity: 0.5;"></span>
        
        <button type="button" class="btn btn-secondary filter-pill" onclick="filterBoard('urgent', this)" style="color:var(--red); border-color:rgba(239,68,68,0.2);">Urgentná</button>
        <button type="button" class="btn btn-secondary filter-pill" onclick="filterBoard('high', this)" style="color:var(--orange); border-color:rgba(249,115,22,0.2);">Vysoká</button>
        <button type="button" class="btn btn-secondary filter-pill" onclick="filterBoard('normal', this)" style="color:var(--accent-2); border-color:var(--border);">Normálna</button>
        
        <button type="button" class="btn btn-secondary" id="layout-toggle" title="Zmeniť rozloženie (Mriežka / Pod seba)" onclick="toggleLayout()" style="margin-left: 0.5rem;">
            <i class="ti ti-layout-grid"></i>
        </button>
    </div>
</form>

<div id="board-grid-wrapper">
    <div class="board-grid" id="board-grid">
    <?php if(empty($items)): ?>
        <div class="empty-state card" style="grid-column: 1 / -1;">
            <div class="es-icon"><i class="ti ti-pin" style="font-size: 3rem; opacity: 0.3;"></i></div>
            <p>Tabuľa je prázdna. Pridajte prvý odkaz pre kolegu!</p>
            <button class="btn btn-primary mt-2" onclick="toggleBoardForm()">+ Pridať odkaz</button>
        </div>
    <?php else: ?>
<?php foreach($items as $item): 
    $item_confs = $confirmations[$item['id']] ?? [];
    $is_unread = true;
    $is_done = ($item['status'] === 'done');
    foreach($item_confs as $c) {
        if((int)$c['user_id'] === (int)$user['id']) $is_unread = false;
        if(!$is_done && stripos($c['note'] ?? '', 'Vybavené') !== false) $is_done = true;
    }
    $is_mine = ((int)$item['user_id'] === (int)$user['id']);
    $priority_map = ['urgent' => 'urgentná urgent', 'high' => 'vysoká high', 'normal' => 'normálna normal'];
    $p_text = $priority_map[$item['priority']] ?? '';
    $search_text = mb_strtolower($item['title'] . ' ' . strip_tags($item['content']) . ' ' . $p_text);
?>
<div class="sticky-note" 
     data-id="<?= $item['id'] ?>" 
     data-column="<?= $item['column_id'] ?>"
     data-mine="<?= $is_mine ? '1' : '0' ?>" 
     data-unread="<?= $is_unread ? '1' : '0' ?>" 
     data-done="<?= $is_done ? '1' : '0' ?>"
     data-status="<?= htmlspecialchars($item['status'] ?? 'new') ?>"
     data-raw-title="<?= htmlspecialchars($item['title']) ?>"
     data-raw-content="<?= htmlspecialchars($item['content']) ?>"
     data-raw-priority="<?= htmlspecialchars($item['priority']) ?>"
     data-event-date="<?= htmlspecialchars($item['event_date'] ?? '') ?>"
     data-event-time="<?= htmlspecialchars($item['event_time'] ?? '') ?>"
     data-search="<?= htmlspecialchars($search_text) ?>"
     <?= !$item['pin_to_top'] ? 'draggable="true"' : '' ?> 
     style="<?= !$item['pin_to_top'] ? 'cursor:grab;' : '' ?>">
    <div class="note-accent" style="background:<?= htmlspecialchars($item['color']) ?>"></div>
    <div class="priority-border p-<?= $item['priority'] ?>"></div>
    <div class="note-inner">
        <!-- Unified Header Row -->
        <div class="note-header-row">
            <div class="meta-left">
                <?php if($item['priority'] === 'urgent'): ?><span class="p-badge urgent">Urgentná</span><?php endif; ?>
                <?php if($item['priority'] === 'high'): ?><span class="p-badge high">Vysoká</span><?php endif; ?>
                <?php if($item['priority'] === 'normal'): ?><span class="p-badge normal">Normálna</span><?php endif; ?>
                <?php if($item['status'] === 'in_progress'): ?>
                    <span class="p-badge progress">Prebieha</span>
                <?php elseif($item['status'] === 'done'): ?>
                    <span class="p-badge done">Vybavené</span>
                <?php endif; ?>
                <?php if($item['target_name']): ?>
                    <span class="tag recipient-tag">👉 Pre: <?= htmlspecialchars($item['target_name']) ?></span>
                <?php endif; ?>
            </div>
            <div class="note-actions">
                <form method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="toggle_pin">
                    <input type="hidden" name="board_id" value="<?= $item['id'] ?>">
                    <button class="btn-action <?= $item['pin_to_top'] ? 'btn-pinned' : 'btn-pin' ?>" type="submit" style="font-size:1.1rem; padding:6px; width:34px; height:34px;" title="<?= $item['pin_to_top']?'Odopnúť':'Pripnúť na vrch' ?>">
                        <?= $item['pin_to_top'] ? '<span style="font-size: 1.1rem; filter: saturate(1.2);">📌</span>' : '<i class="ti ti-pin" style="font-size: 1.25rem;"></i>' ?>
                    </button>
                </form>
                <?php if($item['user_id'] == $user['id'] || is_admin()): ?>
                    <button class="btn-action btn-edit" type="button" style="font-size:1.1rem; padding:6px; width:34px; height:34px;" onclick="editBoardItem(<?= $item['id'] ?>)" title="Upraviť">
                        <i class="ti ti-pencil" style="font-size: 1.25rem;"></i>
                    </button>
                    <form method="POST" style="display:inline" data-confirm="Zmazať tento odkaz?">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="board_id" value="<?= $item['id'] ?>">
                        <button class="btn-action btn-trash" type="submit" style="font-size:1.1rem; padding:6px; width:34px; height:34px;" title="Zmazať"><i class="ti ti-trash" style="font-size: 1.25rem;"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Title Row -->
        <div class="note-title"><?= htmlspecialchars($item['title']) ?></div>

        <?php if($item['event_date']): ?>
        <div style="margin-top: -0.5rem; margin-bottom: 0.75rem; font-size: 0.8rem; font-weight: 700; color: var(--accent-2); display: flex; align-items: center; gap: 6px;">
            <i class="ti ti-calendar-event"></i> Termín: <?= date('d.m.Y', strtotime($item['event_date'])) ?>
            <?php if($item['event_time']): ?>
                <i class="ti ti-clock"></i> <?= substr($item['event_time'], 0, 5) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>


        <div style="margin-bottom:1rem;">
            <?php 
            $item_confs = $confirmations[$item['id']] ?? [];
            $already_confirmed = false;
            foreach($item_confs as $c) if($c['user_id'] == $user['id']) $already_confirmed = true;
            ?>
            <?php if ((int)$item['user_id'] !== (int)$user['id']): ?>
                <div class="flex gap-1" style="flex-wrap:wrap; margin-top: 0.5rem; margin-bottom: 0.75rem;">
                    <?php if (!$already_confirmed): ?>
                        <button class="btn btn-primary btn-xs" onclick="confirmRead(<?= $item['id'] ?>, this)" style="background:var(--green); border:none; padding:3px 10px; font-size:0.7rem; border-radius:6px; font-weight:700;">
                            <i class="ti ti-check"></i> Potvrdiť prečítanie
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-secondary btn-xs" onclick="confirmRead(<?= $item['id'] ?>, this, true)" style="padding:3px 10px; font-size:0.7rem; border-radius:6px; background:rgba(255,255,255,0.05); border:1px solid var(--border); font-weight:600;">
                        <i class="ti ti-message"></i> Odpovedať
                    </button>
                    
                    <button class="btn btn-secondary btn-xs" onclick="confirmRead(<?= $item['id'] ?>, this, false, 'Vybavené / Beriem na vedomie')" style="padding:3px 10px; font-size:0.7rem; border-radius:6px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:var(--accent-2); font-weight:600;">
                        <i class="ti ti-archive"></i> Vybavené
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $raw = htmlspecialchars($item['content']);
        
        // Render Audios
        $raw = preg_replace_callback('/\[\[AUDIO::(.*?)\]\]/', function($m) {
            return '<div style="margin-top:.75rem;">
                        <audio controls src="'.htmlspecialchars($m[1]).'" style="height:35px;width:100%;outline:none;border-radius:20px;"></audio>
                    </div>';
        }, $raw);

        // Render Files
        $raw = preg_replace_callback('/\[\[FILE::(.*?)\|(.*?)\]\]/', function($mf) {
            $url = $mf[1];
            $name = $mf[2];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $is_img = in_array($ext, ['jpg','jpeg','png','gif','webp']);
            
            if($is_img) {
                return '<div style="margin-top:.75rem;">
                            <a href="'.htmlspecialchars($url).'" target="_blank" style="display:block;border-radius:8px;overflow:hidden;border:1px solid var(--border-light);">
                                <img src="'.htmlspecialchars($url).'" style="width:100%;max-height:180px;object-fit:cover;display:block;" alt="Príloha">
                            </a>
                        </div>';
            } else {
                $icon = 'ti-file';
                if($ext === 'pdf') $icon = 'ti-file-type-pdf';
                if(in_array($ext, ['zip','rar','7z'])) $icon = 'ti-zip';
                if(in_array($ext, ['doc','docx'])) $icon = 'ti-file-description';
                return '<div style="margin-top:.75rem;">
                            <a href="'.htmlspecialchars($url).'" target="_blank" class="btn btn-secondary btn-sm" style="width:100%;justify-content:flex-start;padding:.6rem 1rem;background:rgba(255,255,255,0.03);border:1px solid var(--border-light);">
                                <i class="ti '.$icon.'" style="font-size:1.1rem;margin-right:8px;"></i>
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;text-align:left;">'.htmlspecialchars($name).'</span>
                                <span style="font-size:.7rem;opacity:.6;margin-left:8px;">'.strtoupper($ext).'</span>
                            </a>
                        </div>';
            }
        }, $raw);
        ?>
        <div style="font-size:.875rem;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap;"><?= trim($raw) ?></div>

        <div class="note-meta">
            <span class="meta-item"><i class="ti ti-user"></i> <?= htmlspecialchars($item['full_name']) ?></span>
            <span class="meta-sep">•</span>
            <span class="meta-item"><i class="ti ti-clock"></i> <?= time_ago($item['created_at']) ?></span>
            <?php if($item['updated_at'] !== $item['created_at']): ?>
                <span class="meta-sep">•</span>
                <span class="meta-item" title="Upravené"><i class="ti ti-edit"></i> <?= time_ago($item['updated_at']) ?></span>
            <?php endif; ?>
        </div>

        <?php if(!empty($item_confs)): ?>
        <div class="note-confirmations" id="conf-list-<?= $item['id'] ?>" style="margin-top:0.75rem; border-top:1px solid var(--border-light); padding-top:0.5rem; font-size:0.7rem; color:var(--text-muted); display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
            <i class="ti ti-eye" title="Videné/Potvrdené"></i>
            <?php foreach($item_confs as $c): ?>
                <span class="conf-user" style="background:var(--bg-hover); padding:2px 6px; border-radius:4px; display:flex; align-items:center; gap:5px;">
                    <?= htmlspecialchars($c['full_name']) ?>
                    <?php if($c['note']): ?>
                        <span style="color:var(--accent-2); border-left:1px solid var(--border-light); padding-left:5px; font-style:italic;">
                            <?= htmlspecialchars($c['note']) ?>
                        </span>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="note-confirmations" id="conf-list-<?= $item['id'] ?>" style="margin-top:0.75rem; border-top:1px solid var(--border-light); padding-top:0.5rem; font-size:0.7rem; color:var(--text-muted); display:none; flex-wrap:wrap; gap:6px; align-items:center;">
            <i class="ti ti-eye" title="Videné/Potvrdené"></i>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
    </div> <!-- end #board-grid -->
<?php endif; ?>
</div> <!-- end #board-grid-wrapper -->
</div> <!-- end #board-main-content -->

<!-- EDIT MODAL -->
<div id="edit-modal-overlay" class="modal-overlay">
    <div class="modal-box" style="width: min(500px, 92vw);">
        <div class="modal-header">
            <h3 class="modal-title"><i class="ti ti-pencil"></i> Upraviť odkaz</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="edit-board-form" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="board_id" id="edit-board-id">
            
            <div class="form-group mb-3">
                <label class="form-label">Nadpis</label>
                <input type="text" name="title" id="edit-board-title" class="form-control" required style="background:var(--bg-base); color:white;">
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">Obsah</label>
                <textarea name="content" id="edit-board-content" class="form-control" rows="8" required style="background:var(--bg-base); color:white;"></textarea>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Stav</label>
                <div class="priority-selector status-selector" style="margin-top:0.5rem;">
                    <label class="p-opt opt-status-new"><input type="radio" name="status" value="new" id="edit-status-new"><span>Nové</span></label>
                    <label class="p-opt opt-status-progress"><input type="radio" name="status" value="in_progress" id="edit-status-progress"><span>Prebieha</span></label>
                    <label class="p-opt opt-status-done"><input type="radio" name="status" value="done" id="edit-status-done"><span>Vybavené</span></label>
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Priorita</label>
                <div class="priority-selector">
                    <label class="p-opt normal"><input type="radio" name="priority" value="normal" id="edit-p-normal"><span>Normálna</span></label>
                    <label class="p-opt high"><input type="radio" name="priority" value="high" id="edit-p-high"><span>Vysoká</span></label>
                    <label class="p-opt urgent"><input type="radio" name="priority" value="urgent" id="edit-p-urgent"><span>Urgent</span></label>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label"><i class="ti ti-calendar"></i> Termín v kalendári</label>
                <div class="flex gap-2">
                    <input type="date" name="event_date" id="edit-event-date" class="form-control" style="background:var(--bg-base); color:white;">
                    <input type="time" name="event_time" id="edit-event-time" class="form-control" style="background:var(--bg-base); color:white;">
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:.75rem; margin-top:1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Zrušiť</button>
                <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
            </div>
        </form>
    </div>
</div>

<script>
function editBoardItem(id) {
    const card = document.querySelector(`.sticky-note[data-id="${id}"]`);
    if (!card) return;
    
    const title = card.getAttribute('data-raw-title');
    const content = card.getAttribute('data-raw-content');
    const priority = card.getAttribute('data-raw-priority') || 'normal';
    const status = card.getAttribute('data-status') || 'new';
    const evDate = card.getAttribute('data-event-date') || '';
    const evTime = card.getAttribute('data-event-time') || '';
    
    document.getElementById('edit-board-id').value = id;
    document.getElementById('edit-board-title').value = title;
    document.getElementById('edit-board-content').value = content;
    
    // Set Status radio
    const statusRadio = document.getElementById('edit-status-' + (status === 'in_progress' ? 'progress' : status));
    if (statusRadio) statusRadio.checked = true;

    document.getElementById('edit-event-date').value = evDate;
    document.getElementById('edit-event-time').value = evTime ? evTime.substring(0,5) : '';
    
    // Set priority radio
    document.querySelectorAll('#edit-modal-overlay input[name="priority"]').forEach(rad => {
        rad.checked = (rad.value === priority);
    });
    
    document.getElementById('edit-modal-overlay').classList.add('open');
}

function closeEditModal() {
    document.getElementById('edit-modal-overlay').classList.remove('open');
}
</script>

<script>
function toggleNewForm() {
    const card = document.getElementById('form-card');
    const main = document.getElementById('board-main-content');
    const isHidden = card.style.display === 'none';
    
    card.style.display = isHidden ? 'block' : 'none';
    if (main) main.style.display = isHidden ? 'none' : 'block';
}

let mediaRecorder;
let audioChunks = [];
let recInterval;
let recSecs = 0;

let stagedAudios = [];
let stagedDocs = [];

async function toggleRecording() {
    const zone = document.getElementById('voice-zone');
    const label = document.getElementById('record-label');
    const btnText = document.getElementById('record-btn-text');
    const icon = document.getElementById('voice-icon');
    
    if(mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        zone.classList.remove('recording-active');
        btnText.innerText = "Nahrať ďalšiu hlasovku";
        icon.innerHTML = '<i class="ti ti-microphone"></i>';
        clearInterval(recInterval);
    } else {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({audio:true});
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            
            mediaRecorder.addEventListener('dataavailable', e => audioChunks.push(e.data));
            mediaRecorder.addEventListener('stop', () => {
                const blob = new Blob(audioChunks, {type:'audio/webm'});
                const file = new File([blob], `voice_${Date.now()}.webm`, {type:'audio/webm'});
                stagedAudios.push(file);
                renderStaged();
                stream.getTracks().forEach(t => t.stop());
                document.querySelector('textarea[name="content"]').removeAttribute('required');
            });
            
            mediaRecorder.start();
            recSecs = 0; 
            label.innerHTML = "🔴 Nahráva sa (0s)...";
            btnText.innerText = "Zastaviť nahrávanie";
            icon.innerHTML = '<i class="ti ti-square-rounded-filled"></i>';
            zone.classList.add('recording-active');
            
            recInterval = setInterval(() => {
                recSecs++; label.innerHTML = `🔴 Nahráva sa (${recSecs}s)...`;
            }, 1000);
        } catch(e) {
            crmAlert('Chyba', 'Nepodarilo sa získať prístup ku mikrofónu.');
        }
    }
}

function updateFileInfo(input) {
    if (input.files) {
        for(let f of input.files) stagedDocs.push(f);
        renderStaged();
        input.value = ""; // clear for next selection
    }
}

function renderStaged() {
    const label = document.getElementById('record-label');
    const placeholder = document.getElementById('doc-placeholder');
    const info = document.getElementById('doc-info');
    
    label.innerHTML = "";
    
    // Render staged audios
    stagedAudios.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        const div = document.createElement('div');
        div.style = "display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.05);padding:8px 12px;border-radius:12px;border:1px solid var(--border-light);";
        div.innerHTML = `
            <audio src="${url}" controls style="height:32px;width:160px;"></audio>
            <button type="button" onclick="removeAudio(${index})" class="btn btn-danger btn-sm" style="padding:4px 8px;"><i class="ti ti-trash"></i></button>
        `;
        label.appendChild(div);
    });

    // Render staged docs
    if(stagedDocs.length > 0) {
        placeholder.style.display = 'none';
        info.style.display = 'flex';
        info.innerHTML = stagedDocs.map((f, i) => `
            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;background:rgba(255,255,255,0.03);padding:6px 10px;border-radius:8px;margin-bottom:4px;border:1px solid var(--border-light);">
                <span style="font-size:0.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;"><i class="ti ti-file"></i> ${f.name}</span>
                <button type="button" onclick="removeDoc(${i})" class="btn btn-danger btn-sm" style="padding:2px 6px;font-size:0.7rem;"><i class="ti ti-trash"></i></button>
            </div>
        `).join('');
        const addMore = document.createElement('button');
        addMore.type = "button";
        addMore.className = "btn btn-secondary btn-sm";
        addMore.style = "width:100%;margin-top:5px;font-size:0.7rem;";
        addMore.innerHTML = "+ Pridať ďalšie súbory";
        addMore.onclick = () => document.getElementById('doc_input').click();
        info.appendChild(addMore);
    } else {
        placeholder.style.display = 'flex';
        info.style.display = 'none';
    }

    syncInputs();
}

function removeAudio(index) { stagedAudios.splice(index, 1); renderStaged(); }
function removeDoc(index) { stagedDocs.splice(index, 1); renderStaged(); }

function syncInputs() {
    const dtAudio = new DataTransfer();
    stagedAudios.forEach(f => dtAudio.items.add(f));
    document.getElementById('audio_input').files = dtAudio.files;

    const dtDocs = new DataTransfer();
    stagedDocs.forEach(f => dtDocs.items.add(f));
    document.getElementById('doc_input').files = dtDocs.files;
}

function setColor(el, hex) {
    document.querySelectorAll('#color-picker div').forEach(d => d.style.borderColor = 'transparent');
    el.style.borderColor = '#fff';
}
document.addEventListener('DOMContentLoaded', () => {
    const first = document.querySelector('#color-picker div');
    if(first) first.style.borderColor = '#fff';
});

// ── Drag & Drop reorder ──
(function() {
    const grid = document.getElementById('board-grid');
    if (!grid) return;

    let dragEl = null;

    grid.addEventListener('dragstart', e => {
        dragEl = e.target.closest('.sticky-note');
        if (!dragEl) return;
        setTimeout(() => dragEl.style.opacity = '0.4', 0);
        e.dataTransfer.effectAllowed = 'move';
    });

    grid.addEventListener('dragend', e => {
        if (dragEl) dragEl.style.opacity = '';
        document.querySelectorAll('.sticky-note').forEach(n => n.classList.remove('drag-over'));
        dragEl = null;
        saveOrder();
    });

    grid.addEventListener('dragover', e => {
        e.preventDefault();
        const target = e.target.closest('.sticky-note');
        const column = e.target.closest('.board-column');
        
        if (column && !target) {
            column.appendChild(dragEl);
            return;
        }

        if (!target || target === dragEl || target.getAttribute('draggable') === null) return;

        const rect = target.getBoundingClientRect();
        const midY = rect.top + rect.height / 2;

        if (e.clientY < midY) {
            target.parentNode.insertBefore(dragEl, target);
        } else {
            target.parentNode.insertBefore(dragEl, target.nextSibling);
        }
        
        document.querySelectorAll('.sticky-note').forEach(n => n.classList.remove('drag-over'));
        target.classList.add('drag-over');
    });

    function saveOrder() {
        const isFridge = document.getElementById('board-grid').classList.contains('list-view');
        let ids = [];

        if (isFridge) {
            document.querySelectorAll('.board-column').forEach((col, idx) => {
                ids[idx] = [...col.querySelectorAll('.sticky-note[data-id]')]
                    .filter(n => n.getAttribute('draggable') === 'true')
                    .map(n => n.dataset.id);
            });
        } else {
            ids[0] = [...grid.querySelectorAll('.sticky-note[data-id]')]
                .filter(n => n.getAttribute('draggable') === 'true')
                .map(n => n.dataset.id);
        }

        const fd = new FormData();
        fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        fd.append('action', 'reorder');
        fd.append('ids', JSON.stringify(ids));
        fetch('board.php', { method: 'POST', body: fd });
    }
})();

function confirmRead(boardId, btn, askNote = false, fixedNote = '') {
    const proceed = (note) => {
        const fd = new FormData();
        fd.append('csrf_token', '<?= $csrf ?>');
        fd.append('action', 'confirm_read');
        fd.append('board_id', boardId);
        fd.append('note', note);

        fetch('board.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if(data.ok) {
                // Hide only Rozumiem, keep Odpovedat and Vybavene
                const buttons = btn.closest('.flex');
                const rozumiem = buttons.querySelector('button[style*="background:var(--green)"]');
                if (rozumiem) rozumiem.style.display = 'none';

                const list = document.getElementById('conf-list-' + boardId);
                list.style.display = 'flex';
                
                // Remove old entry if same user is already there
                const oldEntry = [...list.querySelectorAll('.conf-user')].find(el => el.textContent.includes(data.user_name));
                if (oldEntry) oldEntry.remove();

                const span = document.createElement('span');
                span.className = 'conf-user';
                span.style = "background:var(--bg-hover); padding:2px 6px; border-radius:4px; display:flex; align-items:center; gap:5px;";
                
                let content = data.user_name;
                if (data.note) {
                    content += `<span style="color:var(--accent-2); border-left:1px solid var(--border-light); padding-left:5px; font-style:italic;">${data.note}</span>`;
                }
                span.innerHTML = content;
                list.appendChild(span);

                // Update sidebar badge
                const sbBadge = document.querySelector('a[href*="board.php"] .nav-badge');
                if (sbBadge) {
                    if (data.unread_count > 0) {
                        sbBadge.innerText = data.unread_count;
                    } else {
                        sbBadge.remove();
                    }
                }
            }
        });
    };

    if (askNote) {
        crmPrompt("Odpoveď", "Zadajte krátku odpoveď pre kolegu:", (val) => {
            if (val !== null) proceed(val);
        });
    } else {
        proceed(fixedNote);
    }
}
/* Board Filtering Logic */
function filterBoard(type, btn) {
    // Update active pill
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    
    const notes = document.querySelectorAll('.sticky-note');
    let count = 0;
    
    notes.forEach(note => {
        let show = false;
        if(type === 'all') show = true;
        else if(type === 'unread') show = (note.getAttribute('data-unread') === '1' && note.getAttribute('data-status') !== 'done');
        else if(type === 'progress') show = note.getAttribute('data-status') === 'in_progress';
        else if(type === 'mine') show = note.getAttribute('data-mine') === '1';
        else if(type === 'done') show = note.getAttribute('data-done') === '1' || note.getAttribute('data-status') === 'done';
        else if(type === 'urgent') show = note.getAttribute('data-raw-priority') === 'urgent';
        else if(type === 'high') show = note.getAttribute('data-raw-priority') === 'high';
        else if(type === 'normal') show = note.getAttribute('data-raw-priority') === 'normal';
        
        note.style.display = show ? 'block' : 'none';
        if(show) count++;
    });
    
    const countEl = document.getElementById('board-count-text');
    if(countEl) countEl.innerText = count + ' odkazov (filter: ' + btn.innerText.trim() + ')';
    
    localStorage.setItem('board-filter', type);
}

function searchBoard() {
    const q = document.getElementById('board-search').value.toLowerCase();
    const notes = document.querySelectorAll('.sticky-note');
    let count = 0;
    
    // Reset active filter to 'all' when searching
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    const allBtn = document.querySelector('.filter-pill[onclick*="all"]');
    if(allBtn) allBtn.classList.add('active');

    notes.forEach(note => {
        const text = note.getAttribute('data-search') || '';
        const show = text.includes(q);
        note.style.display = show ? 'block' : 'none';
        if(show) count++;
    });
    
    document.getElementById('board-count-text').innerText = count + ' nájdených záznamov';
}

function toggleLayout() {
    const grid = document.getElementById('board-grid');
    const btn = document.getElementById('layout-toggle');
    if (!grid || !btn) return;
    
    grid.classList.toggle('list-view');
    const isFridge = grid.classList.contains('list-view');
    
    if (isFridge) {
        // Move cards into columns
        const notes = [...grid.querySelectorAll('.sticky-note')];
        grid.innerHTML = `
            <div class="board-column" data-col="0"></div>
            <div class="board-column" data-col="1"></div>
            <div class="board-column" data-col="2"></div>
        `;
        const cols = grid.querySelectorAll('.board-column');
        notes.forEach(n => {
            const colIdx = parseInt(n.dataset.column) || 0;
            if (cols[colIdx]) cols[colIdx].appendChild(n);
            else cols[0].appendChild(n);
        });
    } else {
        // Flatten cards back into single grid
        const notes = [...grid.querySelectorAll('.sticky-note')];
        grid.innerHTML = '';
        notes.forEach(n => grid.appendChild(n));
    }

    btn.innerHTML = isFridge ? '<i class="ti ti-layout-list"></i>' : '<i class="ti ti-layout-grid"></i>';
    localStorage.setItem('board-layout', isFridge ? 'fridge' : 'grid');
}

document.addEventListener('DOMContentLoaded', () => {
    if(localStorage.getItem('board-layout') === 'fridge') {
        toggleLayout();
    }
    
    // Restore saved filter
    const savedFilter = localStorage.getItem('board-filter');
    if (savedFilter && savedFilter !== 'all') {
        const filterBtn = document.querySelector(`.filter-pill[onclick*="'${savedFilter}'"]`);
        if (filterBtn) {
            filterBoard(savedFilter, filterBtn);
        }
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
