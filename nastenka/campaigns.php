<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/libs/mailer.php';

$pdo = db_connect();
$user = current_user();

$msg = ''; $msg_type = '';
$root_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/";

// ── HTML Templates Metadata ──
$html_templates = [
    'none' => ['id' => 'none', 'name' => 'Iba text (bez šablóny)', 'file' => ''],
    'nameday_fancy' => [
        'id' => 'nameday_fancy',
        'name' => 'Blahoželanie k meninám',
        'file' => 'libs/templates/nameday_template.html',
        'variants' => [
            ['id' => 'nameday_bouquet.png', 'name' => 'Kytica (Univerzálna)', 'preview' => 'libs/templates/images/nameday_bouquet.png'],
            ['id' => 'nameday_whiskey.png', 'name' => 'Whiskey a poháre (Pre pánov)', 'preview' => 'libs/templates/images/nameday_whiskey.png']
        ]
    ],
    'easter_fancy' => [
        'id' => 'easter_fancy',
        'name' => 'Veľkonočný pozdrav',
        'file' => 'libs/templates/easter_template.html',
        'variants' => [
            ['id' => 'easter_slovak.png', 'name' => 'Kraslice a bahniatka', 'preview' => 'libs/templates/images/easter_slovak.png'],
            ['id' => 'easter_full.png', 'name' => 'Korbáč, kraslice a bahniatka', 'preview' => 'libs/templates/images/easter_full.png']
        ]
    ]
];

// ── Handle Sending ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_campaign') {
    csrf_check();
    
    $recipients_data = $_POST['recipients_data'] ?? []; // JSON string of [{email, name}, ...]
    $recipients = json_decode($recipients_data, true) ?: [];
    
    $subject = trim($_POST['subject'] ?? '');
    $user_msg = trim($_POST['body'] ?? '');
    $selected_tpl = $_POST['template_id'] ?? 'none';
    $selected_variant = $_POST['variant_id'] ?? '';
    
    if (empty($recipients)) {
        $msg = "Nevybrali ste žiadnych príjemcov.";
        $msg_type = "error";
    } elseif (empty($subject) || empty($user_msg)) {
        $msg = "Predmet a správa sú povinné.";
        $msg_type = "error";
    } else {
        $success_count = 0;
        $error_count = 0;
        
        $template_html = '';
        if ($selected_tpl !== 'none' && isset($html_templates[$selected_tpl])) {
            $tpl_file = __DIR__ . '/' . $html_templates[$selected_tpl]['file'];
            if (file_exists($tpl_file)) {
                $template_html = file_get_contents($tpl_file);
                $template_html = str_replace(['{{ROOT_URL}}', '{{VARIANT}}'], [$root_url, $selected_variant], $template_html);
            }
        }
        
        foreach ($recipients as $r) {
            $email = $r['email'];
            $name = $r['name'];
            
            $final_body = $user_msg;
            if ($template_html) {
                $final_body = str_replace(
                    ['{{NAME}}', '{{MESSAGE}}'], 
                    [$name, nl2br($user_msg)], 
                    $template_html
                );
            }
            
            $res = send_smtp_email($email, $subject, $final_body, $mail_host, $mail_user, $mail_pass);
            if ($res === true) {
                $success_count++;
            } else {
                $error_count++;
            }
            usleep(150000); // 0.15s delay
        }
        
        $msg = "Kampaň odoslaná. Úspešne: $success_count, Chyby: $error_count.";
        $msg_type = $error_count === 0 ? "success" : "warning";
    }
}

// ── Handle Test Send ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test') {
    csrf_check();
    
    $test_email = trim($_POST['test_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $user_msg = trim($_POST['body'] ?? '');
    $selected_tpl = $_POST['template_id'] ?? 'none';
    $selected_variant = $_POST['variant_id'] ?? '';
    
    if (empty($test_email)) {
        $msg = "Zadajte testovací e-mail.";
        $msg_type = "error";
    } else {
        $template_html = '';
        if ($selected_tpl !== 'none' && isset($html_templates[$selected_tpl])) {
            $tpl_file = __DIR__ . '/' . $html_templates[$selected_tpl]['file'];
            if (file_exists($tpl_file)) {
                $template_html = file_get_contents($tpl_file);
                $template_html = str_replace(['{{ROOT_URL}}', '{{VARIANT}}'], [$root_url, $selected_variant], $template_html);
            }
        }
        
        $final_body = $user_msg;
        if ($template_html) {
            $final_body = str_replace(['{{NAME}}', '{{MESSAGE}}'], ['Testovací Príjemca', nl2br($user_msg)], $template_html);
        }
        
        $res = send_smtp_email($test_email, "[TEST] " . $subject, $final_body, $mail_host, $mail_user, $mail_pass);
        if ($res === true) {
            setcookie('crm_last_test_email', $test_email, time() + (86400 * 30), "/");
            $msg = "Testovací e-mail bol odoslaný na $test_email.";
            $msg_type = "success";
        } else {
            $msg = "Chyba pri odosielaní testu: $res";
            $msg_type = "error";
        }
    }
}

$last_test_email = $_COOKIE['crm_last_test_email'] ?? '';

// ── Filter Logic ──
$target_type = $_GET['type'] ?? 'all';
$f_gender    = $_GET['gender'] ?? '';

$sql = "SELECT id, name, email, city, gender FROM leads WHERE email IS NOT NULL AND email != '' AND status != 'zamietnuty'";

if ($f_gender === 'male') {
    $sql .= " AND gender = 'male'";
} elseif ($f_gender === 'female') {
    $sql .= " AND gender = 'female'";
} elseif ($f_gender === 'other') {
    $sql .= " AND gender = 'other'";
}

if ($target_type === 'nameday') {
    $today_key = date('m-d');
    $nameday_json = @file_get_contents(__DIR__ . '/libs/namedays.json');
    $namedays = $nameday_json ? json_decode($nameday_json, true) : [];
    $today_names = $namedays[$today_key] ?? '';
    if ($today_names) {
        $name_list = explode(',', $today_names);
        $where_clauses = [];
        foreach ($name_list as $n) { $where_clauses[] = "name LIKE " . $pdo->quote('%' . trim($n) . '%'); }
        $sql .= " AND (" . implode(" OR ", $where_clauses) . ")";
    } else { $sql .= " AND 1=0"; }
} elseif ($target_type === 'prospects') {
    $sql .= " AND type = 'prospect'";
} elseif ($target_type === 'customers') {
    $sql .= " AND type = 'customer'";
}

$sql .= " ORDER BY name ASC";

$raw_leads = $pdo->query($sql)->fetchAll();
$leads = [];
$seen_emails = [];
foreach ($raw_leads as $r) {
    $e = strtolower(trim($r['email']));
    if (!isset($seen_emails[$e])) {
        $seen_emails[$e] = true;
        $leads[] = $r;
    }
}

// ── Default Prefills ──
$prefill_templates = [
    'nameday' => [
        'subject' => 'Všetko najlepšie k meninám!',
        'body' => "Dovoľte nám v mene celého tímu zaželať Vám k dnešnému sviatku všetko najlepšie, veľa zdravia a úspechov.\n\nSme radi, že ste s nami."
    ],
    'easter' => [
        'subject' => 'Príjemné veľkonočné sviatky!',
        'body' => "Prajeme Vám veselú Veľkú noc plnú radosti, pohody a jarného slnka v kruhu Vašej rodiny a priateľov.\n\nĎakujeme za Vašu dôveru a tešíme sa na našu ďalšiu spoluprácu."
    ],
    'holiday' => [
        'subject' => 'Príjemné prežitie sviatkov',
        'body' => "Prajeme Vám pokojné a radostné prežitie nadchádzajúcich sviatkov v kruhu najbližších.\n\nĎakujeme za Vašu dôveru."
    ]
];
$prefill = $prefill_templates[$target_type] ?? ['subject' => '', 'body' => ''];

$page_title = "Marketingové kampane";
include __DIR__ . '/partials/header.php';
?>

<style>
    .template-card { border: 2px solid var(--border); border-radius: 10px; padding: 0.65rem; cursor: pointer; transition: all 0.2s; background: var(--bg-sidebar); }
    .template-card:hover { border-color: var(--accent); transform: translateY(-2px); }
    .template-card.active { border-color: var(--accent); background: rgba(99, 102, 241, 0.15); box-shadow: var(--shadow-glow-sm); }
    .variant-img { width: 100%; height: 80px; object-fit: contain; border-radius: 6px; background: white; margin-bottom: 0.4rem; display: block; border: 1px solid #ddd; }
    .preview-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
    .preview-content { background: white; width: 100%; max-width: 700px; height: 90vh; border-radius: 16px; overflow: hidden; position: relative; }
    .preview-header { padding: 15px 20px; background: #1a1d27; color: white; display: flex; justify-content: space-between; align-items: center; }
    
    /* Active button icon color fix */
    .btn-primary i { color: #fff !important; }
    .icon-male { color: #2563eb; }
    .icon-female { color: #db2777; }
    .icon-firm { color: #d97706; }

    /* AI Action Bar Styling (Minimalist style) */
    .ai-assistant-bar {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 10px 1.5rem;
        background: rgba(255, 255, 255, 0.02);
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }
    .btn-ai-minimal {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        transition: all 0.2s;
        font-size: 0.85rem;
        font-weight: 600;
        background: none;
        border: none;
        padding: 6px 0;
    }
    .btn-ai-minimal:hover {
        color: #6366f1;
        transform: translateY(-1px);
    }
    .btn-ai-minimal i {
        font-size: 1rem;
        opacity: 0.8;
    }
    .ai-loading {
        display: none;
        align-items: center;
        gap: 8px;
        color: var(--accent-2);
        font-size: 0.85rem;
        font-weight: 600;
        margin-right: 10px;
    }
    .ai-loading i { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

    <div class="flex justify-between items-center mb-2">
        <h2 class="m-0"><i class="ti ti-mail-forward"></i> Hromadné e-maily</h2>
        <div class="flex flex-responsive gap-1">
            <a href="campaigns.php?type=all&gender=" class="btn <?= ($target_type==='all' && empty($f_gender))?'btn-primary':'btn-secondary' ?> btn-sm">Všetci</a>
            <a href="campaigns.php?type=all&gender=male" class="btn <?= $f_gender==='male'?'btn-primary':'btn-secondary' ?> btn-sm"><i class="ti ti-user-bolt icon-male" style="margin-right:4px;"></i> Muži</a>
            <a href="campaigns.php?type=all&gender=female" class="btn <?= $f_gender==='female'?'btn-primary':'btn-secondary' ?> btn-sm"><i class="ti ti-user-heart icon-female" style="margin-right:4px;"></i> Ženy</a>
            <a href="campaigns.php?type=all&gender=other" class="btn <?= $f_gender==='other'?'btn-primary':'btn-secondary' ?> btn-sm"><i class="ti ti-building-community icon-firm" style="margin-right:4px;"></i> Firmy</a>
            <a href="campaigns.php?type=nameday&gender=<?= $f_gender ?>" class="btn <?= $target_type==='nameday'?'btn-primary':'btn-secondary' ?> btn-sm">Dnešné meniny</a>
            <a href="campaigns.php?type=customers&gender=<?= $f_gender ?>" class="btn <?= $target_type==='customers'?'btn-primary':'btn-secondary' ?> btn-sm">Zákazníci</a>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> mb-2"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="dashboard-grid mode-dashboard">
        <!-- Campaign Form -->
        <div class="card">
            <form method="POST" id="campaign-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="send_campaign">
                <input type="hidden" name="recipients_data" id="recipients-data" value='<?= json_encode(array_map(fn($l) => ['email' => $l['email'], 'name' => $l['name'], 'id' => $l['id']], $leads)) ?>'>
                <input type="hidden" name="template_id" id="input-tpl-id" value="none">
                <input type="hidden" name="variant_id" id="input-variant-id" value="">

                <div class="card-header"><div class="card-title">1. Výber šablóny a vizuálu</div></div>
                <div class="p-2">
                    <div id="selected-template-preview" style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 12px; border: 1px dashed var(--border);">
                        <div id="tpl-preview-img" style="width: 100px; height: 60px; background: #f0f2ff; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #1a1d27; overflow: hidden; flex-shrink: 0;">
                            <i class="ti ti-file-text" style="font-size: 1.5rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div id="tpl-preview-name" style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Iba text (bez grafiky)</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Základná e-mailová správa bez HTML šablóny</div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openTemplateModal()" style="border-radius: 8px;">
                            <i class="ti ti-layout-grid"></i> Zmeniť šablónu
                        </button>
                    </div>

                    <div class="card-header p-0 mb-1" style="border:0;"><div class="card-title" style="font-size: 1.1rem;">2. Obsah e-mailu</div></div>
                    
                    <div class="form-group">
                        <label class="form-label">Predmet správy</label>
                        <input type="text" name="subject" class="form-control" required value="<?= htmlspecialchars($prefill['subject']) ?>" placeholder="Predmet e-mailu">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Text správy (bude vložený do šablóny)</label>
                        <div style="border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
                            <div class="ai-assistant-bar">
                                <div class="ai-loading" id="aiLoading">
                                    <i class="ti ti-loader"></i> <span>AI premýšľa...</span>
                                </div>
                                <button type="button" class="btn-ai-minimal" onclick="runAI('fix_grammar')">
                                    <i class="ti ti-wand"></i> Gramatika
                                </button>
                                <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'profesionálny'})">
                                    <i class="ti ti-briefcase"></i> Profesionálne
                                </button>
                                <button type="button" class="btn-ai-minimal" onclick="runAI('rephrase', {tone: 'priateľský'})">
                                    <i class="ti ti-mood-smile"></i> Priateľsky
                                </button>
                            </div>
                            <textarea name="body" id="mail-body" class="form-control" style="min-height: 250px; padding: 1rem; border: none; border-radius: 0; background: rgba(0,0,0,0.2); width: 100%; color: white;" required><?= htmlspecialchars($prefill['body']) ?></textarea>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Pre-flight / Test Band -->
            <div style="background: var(--bg-sidebar); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 1.5rem 2rem;">
                <div style="font-size: 0.7rem; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="ti ti-test-pipe"></i> Kontrola pred odoslaním (Odoslať test)
                </div>
                <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="send_test">
                    <input type="hidden" name="template_id" class="hidden-tpl-id">
                    <input type="hidden" name="variant_id" class="hidden-variant-id">
                    <input type="hidden" name="subject" class="hidden-subject">
                    <input type="hidden" name="body" class="hidden-body">

                    <div style="flex: 1;">
                        <input type="email" name="test_email" class="form-control" placeholder="Testovací e-mail (napr. váš@adres.sk)" style="background: var(--bg-card); border-color: var(--border); font-size: 0.9rem;">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="white-space: nowrap; height: 38px;" onclick="syncTestForm(this.form)">
                        Odoslať test
                    </button>
                </form>
            </div>

            <!-- Full Bottom Actions -->
            <div style="padding: 1.5rem 2rem; display: flex; align-items: center; justify-content: space-between;">
                <button type="button" class="btn btn-secondary" onclick="openPreview()" style="height: 42px; padding: 0 25px;">
                    <i class="ti ti-eye"></i> Zobraziť vizuálny náhľad
                </button>
                <button type="button" class="btn btn-primary" id="btn-submit-campaign" onclick="submitMainForm()" style="height: 42px; padding: 0 35px; min-width: 200px;">
                    <i class="ti ti-send"></i> Spustiť kampaň (<?= count($leads) ?>)
                </button>
            </div>
        </div>

        <!-- Recipients List -->
        <div class="card recipients-list-card">
            <div class="card-header" style="padding: 1rem 1rem 0.5rem 1rem;"><div class="card-title">Príjemcovia (<?= count($leads) ?>)</div></div>
            <div class="p-0" style="max-height: 700px; overflow-y: auto;">
                <?php if(empty($leads)): ?>
                    <p class="text-muted text-center py-4">Žiadne kontakty nespĺňajú kritériá.</p>
                <?php else: ?>
                    <table class="data-table mb-0">
                        <thead style="position: sticky; top: 0; z-index: 10; background: var(--bg-card);"><tr><th>Príjemca</th><th style="width:40px;"></th></tr></thead>
                        <tbody>
                            <?php foreach($leads as $l): ?>
                            <tr id="recipient-<?= $l['id'] ?>" style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                <td style="padding: 0.65rem 1rem;">
                                    <div style="display: flex; flex-direction: column;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <a href="contact.php?id=<?= $l['id'] ?>" target="_blank" style="color: var(--text-primary); text-decoration: none; font-weight: 700; font-size: 0.9rem;">
                                                <?= htmlspecialchars($l['name']) ?>
                                            </a>
                                            <?php if($l['gender'] === 'female'): ?> <i class="ti ti-user-heart" style="color:#db2777; font-size:0.75rem;" title="Žena"></i>
                                            <?php elseif($l['gender'] === 'male'): ?> <i class="ti ti-user-bolt" style="color:#2563eb; font-size:0.75rem;" title="Muž"></i>
                                            <?php elseif($l['gender'] === 'other'): ?> <i class="ti ti-building-community" style="color:#d97706; font-size:0.75rem;" title="Firma"></i> <?php endif; ?>
                                            
                                            <?php if(!filter_var($l['email'], FILTER_VALIDATE_EMAIL)): ?>
                                                <span class="status-badge" style="background: rgba(239,68,68,.1) !important; color: #ef4444 !important; font-size: 0.6rem !important; padding: 2px 6px !important; margin-left: auto;">
                                                    <i class="ti ti-alert-triangle" style="font-size: 0.8rem; margin-right: 2px;"></i> Neplatný e-mail
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); opacity: 0.7; margin-top: 1px;"><?= htmlspecialchars($l['email']) ?></div>
                                    </div>
                                </td>
                                <td style="text-align: right; padding: 0.65rem 1rem;">
                                    <button type="button" class="btn btn-sm action-btn" style="background:transparent; color: #ef4444; opacity: 0.8; padding: 4px; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'" onclick="excludeRecipient(<?= $l['id'] ?>)" title="Vyradiť">
                                        <i class="ti ti-trash" style="font-size: 1.1rem;"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="preview-modal" class="preview-modal">
    <div class="preview-content">
        <div class="preview-header">
            <strong>Náhľad e-mailu</strong>
            <button class="btn btn-secondary btn-sm" onclick="closePreview()">✕ Zavrieť</button>
        </div>
        <iframe id="preview-iframe" style="width: 100%; height: calc(100% - 55px); border: none;"></iframe>
    </div>
</div>

<!-- Template Selection Modal -->
<div id="template-modal" class="modal-overlay">
    <div class="modal-box" style="width: min(800px, 95vw); max-height: 85vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="ti ti-layout-grid"></i> Vyberte šablónu e-mailu</h3>
            <button class="modal-close" onclick="closeTemplateModal()">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto; padding: 1.5rem;">
            <div class="template-grid">
                <div class="template-card" data-tpl="none" data-variant="" data-name="Iba text" data-desc="Základná e-mailová správa bez HTML šablóny" onclick="setTemplate('none', '', this)">
                    <div class="tpl-icon-box">
                        <i class="ti ti-file-text" style="font-size: 2rem;"></i>
                    </div>
                    <div style="font-weight: 700; font-size: 0.85rem;">Iba text</div>
                </div>
                <?php foreach($html_templates['nameday_fancy']['variants'] as $v): ?>
                <div class="template-card" data-tpl="nameday_fancy" data-variant="<?= $v['id'] ?>" data-name="<?= htmlspecialchars($v['name']) ?>" data-desc="Grafická šablóna pre meninovú gratuláciu" onclick="setTemplate('nameday_fancy', '<?= $v['id'] ?>', this)">
                    <img src="<?= $v['preview'] ?>" class="variant-img">
                    <div style="font-weight: 700; font-size: 0.85rem;"><?= $v['name'] ?></div>
                </div>
                <?php endforeach; ?>
                <?php foreach($html_templates['easter_fancy']['variants'] as $v): ?>
                <div class="template-card" data-tpl="easter_fancy" data-variant="<?= $v['id'] ?>" data-name="<?= htmlspecialchars($v['name']) ?>" data-desc="Sviatočná veľkonočná grafická šablóna" onclick="setTemplate('easter_fancy', '<?= $v['id'] ?>', this)">
                    <img src="<?= $v['preview'] ?>" class="variant-img">
                    <div style="font-weight: 700; font-size: 0.85rem;"><?= $v['name'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
let allRecipients = <?= json_encode(array_map(fn($l) => ['id' => (int)$l['id'], 'name' => $l['name'], 'email' => $l['email']], $leads)) ?>;

function excludeRecipient(id) {
    allRecipients = allRecipients.filter(r => r.id !== id);
    const row = document.getElementById('recipient-' + id);
    if (row) row.remove();
    document.getElementById('recipients-data').value = JSON.stringify(allRecipients);
    const count = allRecipients.length;
    document.getElementById('btn-submit-campaign').innerHTML = `<i class="ti ti-send"></i> Spustiť kampaň (${count})`;
}

function openTemplateModal() { document.getElementById('template-modal').classList.add('open'); }
function closeTemplateModal() { document.getElementById('template-modal').classList.remove('open'); }

function setTemplate(tplId, variantId, el) {
    document.getElementById('input-tpl-id').value = tplId;
    document.getElementById('input-variant-id').value = variantId;
    
    // Update preview in main UI
    const previewImg = document.getElementById('tpl-preview-img');
    const previewName = document.getElementById('tpl-preview-name');
    const previewDesc = previewName.nextElementSibling;
    
    if (tplId === 'none') {
        previewImg.innerHTML = '<i class="ti ti-file-text" style="font-size: 1.5rem;"></i>';
    } else {
        const img = el.querySelector('img').cloneNode();
        img.style.width = '100%';
        img.style.height = '100%';
        previewImg.innerHTML = '';
        previewImg.appendChild(img);
    }
    
    previewName.textContent = el.dataset.name;
    previewDesc.textContent = el.dataset.desc;
    
    document.querySelectorAll('.template-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    
    closeTemplateModal();
}

async function openPreview() {
    const tplId = document.getElementById('input-tpl-id').value;
    const variantId = document.getElementById('input-variant-id').value;
    const body = document.getElementById('mail-body').value;
    const subject = document.getElementsByName('subject')[0].value;
    
    const iframe = document.getElementById('preview-iframe');
    const modal = document.getElementById('preview-modal');
    modal.style.display = 'flex';

    if (tplId === 'none') {
        iframe.contentDocument.body.innerHTML = `
            <div style="font-family: sans-serif; padding: 40px; background: #fff; line-height: 1.6;">
                <h2 style="margin-top:0;">${subject}</h2>
                <hr>
                ${body.replace(/\n/g, '<br>')}
            </div>
        `;
    } else {
        const tplMapping = { 'nameday_fancy': 'libs/templates/nameday_template.html', 'easter_fancy': 'libs/templates/easter_template.html' };
        const response = await fetch(tplMapping[tplId]);
        let html = await response.text();
        html = html.split('{{ROOT_URL}}').join('<?= $root_url ?>');
        html = html.split('{{VARIANT}}').join(variantId);
        html = html.split('{{NAME}}').join('Meno Príjemcu');
        html = html.split('{{MESSAGE}}').join(body.replace(/\n/g, '<br>'));
        iframe.srcdoc = html;
    }
}

function closePreview() { document.getElementById('preview-modal').style.display = 'none'; }

function syncTestForm(form) {
    const mainForm = document.getElementById('campaign-form');
    form.querySelector('.hidden-tpl-id').value = mainForm.querySelector('#input-tpl-id').value;
    form.querySelector('.hidden-variant-id').value = mainForm.querySelector('#input-variant-id').value;
    form.querySelector('.hidden-subject').value = mainForm.querySelector('input[name="subject"]').value;
    form.querySelector('.hidden-body').value = mainForm.querySelector('textarea[name="body"]').value;
}

function submitMainForm() {
    const mainForm = document.getElementById('campaign-form');
    const recipientCount = allRecipients.length;
    if (recipientCount <= 0) { alert("Zoznam príjemcov je prázdny."); return; }
    if (confirm(`Spustiť hromadné odosielanie pre ${recipientCount} príjemcov?`)) mainForm.submit();
}

async function runAI(action, extraParams = {}) {
    const loading = document.getElementById('aiLoading');
    const textArea = document.getElementById('mail-body');
    if (!textArea || !loading) return;

    const content = textArea.value.trim();
    if (content.length < 5) {
        alert('Prosím, napíšte aspoň krátky text.');
        return;
    }

    loading.style.display = 'inline-flex';
    document.querySelectorAll('.btn-ai-minimal').forEach(b => b.disabled = true);
    
    try {
        const response = await fetch('ajax_ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                content: content,
                ...extraParams
            })
        });
        
        const data = await response.json();
        if (data.success) {
            textArea.value = data.result;
        } else {
            alert('AI Chyba: ' + data.error);
        }
    } catch (e) {
        alert('Chyba pri komunikácii s AI.');
    } finally {
        loading.style.display = 'none';
        document.querySelectorAll('.btn-ai-minimal').forEach(b => b.disabled = false);
    }
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
