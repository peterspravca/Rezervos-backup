<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo  = db_connect();
$user = current_user();

// Flash messages
$flash = '';
if (isset($_GET['deleted'])) $flash = '<span style="display:flex;align-items:center;gap:8px;"><i class="ti ti-trash"></i> Kontakt bol úspešne zmazaný.</span>';

// ── Obsluha rýchleho zmazania z nástenky (len admin) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_lead' && is_admin()) {
    csrf_check();
    $del_id = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM crm_notes WHERE lead_id=?")->execute([$del_id]);
    $pdo->prepare("DELETE FROM leads WHERE id=?")->execute([$del_id]);
    header('Location: index.php?deleted=1');
    exit;
}

// ── Filters ──
$search   = trim($_GET['q']     ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_priority = trim($_GET['priority'] ?? '');
$f_source = trim($_GET['source'] ?? '');
$f_intent = trim($_GET['intent'] ?? '');

// Funkcia pre automatickú detekciu na pozadí
function auto_detect_gender($nameStr) {
    if (empty($nameStr)) return 'unknown';
    $lower = mb_strtolower(trim($nameStr));
    $companyTerms = ['s.r.o', 'sro', 'a.s.', ' as', 'spol.', 'o.z.', ' oz', 'n.o.', 'n.f.', 'v.o.s', 'vos', 'k.s.', ' s.p.', ' sp ', 'obec', 'mesto', 'zdruze', 'nadac', 'stavebniny', 'reality', 'servis', 'montaz', 'stolars', 'marsoft'];
    foreach ($companyTerms as $term) { if (str_contains($lower, $term)) return 'other'; }
    $parts = explode(' ', $lower);
    $lastPart = end($parts);
    if (str_ends_with($lastPart, 'ová') || str_ends_with($lastPart, 'á')) return 'female';
    if (count($parts) > 0) {
        $firstPart = $parts[0];
        if (str_ends_with($firstPart, 'a')) {
            if (!in_array($firstPart, ['jan', 'benjamin', 'kristian', 'adrian', 'nesta', 'luca', 'toma', 'mustafa'])) return 'female';
        }
        return 'male';
    }
    return 'unknown';
}

$f_type = trim($_GET['type'] ?? '');
$f_date_from = trim($_GET['date_from'] ?? '');
$f_date_to   = trim($_GET['date_to'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;

$is_contacts_mode = isset($_GET['status']) || !empty($search) || !empty($f_priority) || !empty($f_source) || !empty($f_intent) || !empty($f_type) || !empty($f_date_from) || !empty($f_date_to);

// NOTE: Database migrations moved to a separate maintenance script to improve performance.

// ── Stats ──
$stmt_stats = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'novy' OR status = 'prideleny') AS new_count,
        SUM(status = 'v_procese') AS progress_count,
        SUM(status = 'ukonceny') AS done_count,
        SUM(status = 'zakazka') AS orders_count,
        SUM(status LIKE 'kontaktovany%') AS contacted_count,
        SUM(assigned_to = :my_id) AS my_leads_count,
        SUM(CASE WHEN status NOT IN ('ukonceny','zamietnuty') THEN deal_value ELSE 0 END) AS potential_value
    FROM leads
");
$stmt_stats->execute([':my_id' => $user['id']]);
$stats = $stmt_stats->fetch();

// ── Board posts count ──
$board_count = $pdo->query("SELECT COUNT(*) FROM crm_board")->fetchColumn();

// ── Build query ──
$where  = [];
$params = [];
if ($search) {
    $where[]  = "(l.name LIKE :q1 OR l.email LIKE :q2 OR l.phone LIKE :q3 OR l.message LIKE :q4 OR l.city LIKE :q5 OR l.address LIKE :q6)";
    $params[':q1'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
    $params[':q4'] = "%$search%";
    $params[':q5'] = "%$search%";
    $params[':q6'] = "%$search%";
}
if ($f_status && $f_status !== 'all') {
    if ($f_status === 'kontaktovany') {
        $where[] = "l.status IN ('kontaktovany_email', 'kontaktovany_telefon', 'kontaktovany_oboje')";
    } elseif ($f_status === 'moje') {
        $where[] = "l.assigned_to = :my_id";
        $params[':my_id'] = $user['id'];
    } else {
        $where[]  = "l.status = :status";
        $params[':status'] = $f_status;
    }
} elseif (!$is_contacts_mode) {
    // Na dashboarde zobrazuj všetky aktívne dopyty (Nové, Pridelené, Kontaktované, V procese, Zákazka)
    $where[]  = "l.status IN ('novy', 'prideleny', 'kontaktovany_email', 'kontaktovany_telefon', 'kontaktovany_oboje', 'v_procese', 'zakazka')";
}
if ($f_priority) {
    $where[]  = "l.priority = :priority";
    $params[':priority'] = $f_priority;
}
if ($f_source) {
    $where[]  = "l.source = :source";
    $params[':source'] = $f_source;
}
if ($f_type) {
    $where[]  = "l.type = :type";
    $params[':type'] = $f_type;
}
if ($f_intent) {
    $where[]  = "l.intent = :intent";
    $params[':intent'] = $f_intent;
}
if ($f_date_from) {
    $where[]  = "DATE(l.created_at) >= :date_from";
    $params[':date_from'] = $f_date_from;
}
if ($f_date_to) {
    $where[]  = "DATE(l.created_at) <= :date_to";
    $params[':date_to'] = $f_date_to;
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$total_rows = $pdo->prepare("SELECT COUNT(*) FROM leads l $where_sql");
$total_rows->execute($params);
$total_count = $total_rows->fetchColumn();
$total_pages = max(1, (int)ceil($total_count / $per_page));
$offset = ($page - 1) * $per_page;

$sql = "SELECT l.*, u.full_name AS assigned_name
        FROM leads l
        LEFT JOIN crm_users u ON l.assigned_to = u.id
        $where_sql
        ORDER BY l.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$leads = $stmt->fetchAll();

// ── Users for filter / assign ──
$users = $pdo->query("SELECT id, full_name FROM crm_users WHERE is_active=1 AND full_name != 'Administrátor' ORDER BY full_name")->fetchAll();

// ── Board items (top 3) ──
$board_items = $pdo->query("SELECT b.*, u.full_name FROM crm_board b JOIN crm_users u ON b.user_id=u.id ORDER BY pin_to_top DESC, created_at DESC LIMIT 3")->fetchAll();
// ── Recent Activities (Global timeline) ──
$recent_activities = $pdo->query("
    SELECT n.*, u.full_name, l.name as lead_name, l.id as lead_id
    FROM crm_notes n
    JOIN crm_users u ON n.user_id = u.id
    JOIN leads l ON n.lead_id = l.id
    WHERE n.note_type != 'system_silent'
    AND n.content NOT LIKE '%AUTOMAT:%'
    AND n.content NOT LIKE '%Vygenerovaný dokument%'
    AND n.content NOT LIKE '%Nahraný súbor%'
    AND n.content NOT LIKE '%SCHVÁLIL a PODPÍSAL%'
    ORDER BY n.created_at DESC
    LIMIT 30
")->fetchAll();
// ── Name Days Logic ──
$today_key = date('m-d');
$nameday_json = @file_get_contents(__DIR__ . '/libs/namedays.json');
$namedays = $nameday_json ? json_decode($nameday_json, true) : [];
$today_names = $namedays[$today_key] ?? '';
$celebrants = [];
if ($today_names) {
    $name_list = explode(',', $today_names);
    $celebrant_where = [];
    foreach ($name_list as $n) {
        $celebrant_where[] = "l.name LIKE " . $pdo->quote('%' . trim($n) . '%');
    }
    $celebrants = $pdo->query("SELECT id, name FROM leads l WHERE (" . implode(" OR ", $celebrant_where) . ") AND l.status != 'zamietnuty' LIMIT 10")->fetchAll();
}

// Nadpis podľa kontextu
if ($is_contacts_mode) {
    if (!empty($search)) {
        $page_title = 'Výsledky: ' . htmlspecialchars($search);
    } elseif ($f_status === 'all' || $f_status === '') {
        $page_title = 'Všetky kontakty';
    } elseif (isset($f_status)) {
        $page_title = status_label($f_status) ?: 'Kontakty';
    } else {
        $page_title = 'Kontakty';
    }
    // Append priority to title if filtered
    if ($f_priority) {
        $page_title .= ' (Priorita: ' . priority_label($f_priority) . ')';
    }
} else {
    $page_title = 'Dashboard';
}
include __DIR__ . '/partials/header.php';
?>

<!-- Stats (len na Dashboarde) -->
<?php if($flash): ?>
<div class="alert alert-success mt-1 mb-1"><?= $flash ?></div>
<?php endif; ?>

<?php if(!$is_contacts_mode): ?>

<div class="stats-grid">
    <div class="stat-card accent">
        <div class="stat-label">Všetky kontakty</div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-icon"><i class="ti ti-address-book"></i></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Pridelené mne</div>
        <div class="stat-value"><?= (int)$stats['my_leads_count'] ?></div>
        <div class="stat-icon"><i class="ti ti-user-check"></i></div>
    </div>
    <div class="stat-card purple">
        <div class="stat-label">Kontaktovaní</div>
        <div class="stat-value"><?= $stats['contacted_count'] ?></div>
        <div class="stat-icon"><i class="ti ti-phone-calling"></i></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">V procese</div>
        <div class="stat-value"><?= $stats['progress_count'] ?></div>
        <div class="stat-icon"><i class="ti ti-settings"></i></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Ukončené</div>
        <div class="stat-value"><?= $stats['done_count'] ?></div>
        <div class="stat-icon"><i class="ti ti-circle-check"></i></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Zákazky</div>
        <div class="stat-value"><?= $stats['orders_count'] ?></div>
        <div class="stat-icon"><i class="ti ti-briefcase"></i></div>
    </div>
</div>
<?php endif; ?>

<!-- Board preview + Contacts -->
<div class="dashboard-grid <?= $is_contacts_mode ? 'mode-contacts' : 'mode-dashboard' ?>">

<div>
<?php if ($is_contacts_mode): ?>
<!-- Filter bar -->
<form method="GET" class="filter-bar" style="margin-bottom: 1.5rem;">
    <input type="search" name="q" placeholder="   Hľadaj meno, email, telefón..." value="<?= htmlspecialchars($search) ?>" style="padding-left: 2.5rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%239da3c8%22 stroke-width=%222.5%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 class=%22icon icon-tabler icons-tabler-outline icon-tabler-search%22><path stroke=%22none%22 d=%22M0 0h24v24H0z%22 fill=%22none%22/><path d=%22M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0%22 /><path d=%22M21 21l-6 -6%22 /></svg>'); background-repeat: no-repeat; background-position: 0.8rem center;">
    <select name="status" onchange="this.form.submit()">
        <option value="all" <?= ($f_status==='all' || $f_status==='') ? 'selected' : '' ?>>Všetky aktívne</option>
        <option value="moje" <?= $f_status==='moje' ? 'selected' : '' ?>>⭐ Moje dopyty (všetky)</option>
        <?php foreach(['novy','prideleny','kontaktovany','v_procese','ukonceny','zamietnuty','zakazka'] as $s): ?>
        <option value="<?= $s ?>" <?= $f_status===$s?'selected':'' ?>><?= ($s === 'kontaktovany' ? 'Všetci kontaktovaní' : status_label($s)) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="priority">
        <option value="">Všetky priority</option>
        <option value="vysoka"  <?= $f_priority==='vysoka'  ? 'selected':'' ?>>Vysoká</option>
        <option value="stredna" <?= $f_priority==='stredna' ? 'selected':'' ?>>Stredná</option>
        <option value="nizka"   <?= $f_priority==='nizka'   ? 'selected':'' ?>>Nízka</option>
    </select>
    <select name="source">
        <option value="">Všetky zdroje</option>
        <option value="web"    <?= $f_source==='web'    ? 'selected':'' ?>>Web</option>
        <option value="manual" <?= $f_source==='manual' ? 'selected':'' ?>>Manuálne</option>
    </select>
    <select name="intent">
        <option value="">Všetky zámery</option>
        <option value="quote"    <?= $f_intent==='quote'    ? 'selected':'' ?>>Cenová ponuka</option>
        <option value="callback" <?= $f_intent==='callback' ? 'selected':'' ?>>Spätné volanie</option>
    </select>
    <select name="type">
        <option value="">Všetky typy</option>
        <option value="prospect" <?= $f_type==='prospect' ? 'selected':'' ?>>Záujemca</option>
        <option value="customer" <?= $f_type==='customer' ? 'selected':'' ?>>Zákazník</option>
    </select>
    <div style="display:flex; align-items:center; gap:5px; background:var(--bg-base); border:1px solid var(--border); border-radius:12px; padding:0 10px;">
        <span style="font-size:0.7rem; color:var(--text-muted); font-weight:700;">OD</span>
        <input type="date" name="date_from" value="<?= htmlspecialchars($f_date_from) ?>" style="border:none; background:transparent; padding:8px 5px; width:120px; font-size:0.85rem;">
        <span style="font-size:0.7rem; color:var(--text-muted); font-weight:700;">DO</span>
        <input type="date" name="date_to" value="<?= htmlspecialchars($f_date_to) ?>" style="border:none; background:transparent; padding:8px 5px; width:120px; font-size:0.85rem;">
    </div>
    <button class="btn btn-secondary" type="submit"><i class="ti ti-filter"></i> Filtrovať</button>
    <a href="index.php?status=all&date_from=<?= date('Y-m-d', strtotime('-14 days')) ?>" class="btn btn-secondary" style="border-color:var(--accent-2); color:var(--accent-2);"><i class="ti ti-calendar-event"></i> Posledných 14 dní</a>
    <?php if($search || ($f_status && $f_status !== 'all') || $f_priority || $f_source || $f_intent || $f_type || $f_date_from || $f_date_to): ?>
    <a href="index.php?status=all" class="btn btn-secondary"><i class="ti ti-x"></i> Reset</a>
    <?php endif; ?>
</form>
<?php endif; ?>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden;">
    <div class="card-header">
        <div>
            <div class="card-title"><?= $is_contacts_mode ? 'Kontakty' : 'Aktívne dopyty' ?></div>
            <div class="card-subtitle"><?= $total_count ?> záznamov v prehľade</div>
        </div>
    </div>

    <?php if(empty($leads)): ?>
    <div class="empty-state">
        <div class="es-icon"><i class="ti ti-mailbox-off"></i></div>
        <p>Žiadne kontakty pre tieto filtre.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="data-table leads-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Meno</th>
                <th>Kontakt</th>
                <th>Stav</th>
                <?php if($is_contacts_mode): ?>
                <th>Priorita</th>
                <th>Pridelený</th>
                <?php endif; ?>
                <th>Dátum</th>
                <?php if(is_admin()): ?><th>Akcia</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach($leads as $lead): 
            $p = $lead['priority'] ?? 'stredna';
            $gender = $lead['gender'] ?? 'unknown';
            
            // AUTO-FIX: Ak je pohlavie neznáme, skúsime ho určiť a hneď uložiť
            if ($gender === 'unknown' || empty($gender)) {
                $gender = auto_detect_gender($lead['name']);
                if ($gender !== 'unknown') {
                    $pdo->prepare("UPDATE leads SET gender = ? WHERE id = ?")->execute([$gender, $lead['id']]);
                }
            }
        ?>
        <tr onclick="window.location='contact.php?id=<?= $lead['id'] ?>'" title="Otvoriť detail" class="priority-<?= $p ?>">
            <td data-label="#" class="text-muted text-sm"><?= $lead['id'] ?></td>
            <td data-label="Meno">
                <div class="td-name">
                    <?= htmlspecialchars($lead['name']) ?>
                    <?php if(($lead['source'] ?? 'web') === 'web'): ?>
                        <span style="font-size:0.7rem; color:var(--accent-2); margin-left:4px; font-weight:700;" title="Webový dopyt">
                            <i class="ti ti-world" style="font-size:0.8rem;"></i> WEB <?= intent_label($lead['intent']) ?>
                        </span>
                    <?php endif; ?>

                    <?php if($gender === 'female'): ?>
                        <i class="ti ti-user-heart" style="color:#db2777; font-size:0.75rem; margin-left:4px;" title="Žena"></i>
                    <?php elseif($gender === 'male'): ?>
                        <i class="ti ti-user-bolt" style="color:#2563eb; font-size:0.75rem; margin-left:4px;" title="Muž"></i>
                    <?php elseif($gender === 'other'): ?>
                        <i class="ti ti-building-community" style="color:#d97706; font-size:0.75rem; margin-left:4px;" title="Firma"></i>
                    <?php endif; ?>
                </div>
                <div class="td-meta-info" style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                    <?php 
                    $addr_parts = [];
                    if(!empty($lead['address'])) {
                        $addr = htmlspecialchars($lead['address']);
                        if(!empty($lead['apartment'])) $addr .= ' / ' . htmlspecialchars($lead['apartment']);
                        $addr_parts[] = $addr;
                    }
                    if(!empty($lead['city'])) $addr_parts[] = htmlspecialchars($lead['city']);
                    
                    if(!empty($addr_parts)): ?>
                        <span title="Lokalita"><i class="ti ti-map-pin" style="font-size:0.8rem; color:var(--accent-2);"></i> <?= implode(', ', $addr_parts) ?></span>
                    <?php elseif(!empty($lead['internal_note'])): ?>
                        <span title="Poznámka" style="opacity:0.8;"><i class="ti ti-notes" style="font-size:0.8rem;"></i> <?= htmlspecialchars(mb_substr($lead['internal_note'],0,40)) ?>…</span>
                    <?php endif; ?>
                </div>
            </td>
            <td data-label="Kontakt">
                <div class="td-email"><i class="ti ti-mail"></i> <?= htmlspecialchars($lead['email']) ?></div>
                <div class="td-phone"><i class="ti ti-phone"></i> <?= htmlspecialchars($lead['phone']) ?></div>
            </td>
            <td data-label="Stav">
                <span class="status-badge <?= status_class($lead['status'] ?? 'novy') ?>">
                    <?= status_label($lead['status'] ?? 'novy') ?>
                </span>
            </td>
            <?php if($is_contacts_mode): ?>
            <td data-label="Priorita">
                <?php $p = $lead['priority'] ?? 'stredna'; ?>
                <span class="<?= $p==='vysoka'?'priority-high':($p==='nizka'?'priority-low':'priority-mid') ?>">
                    <?= priority_label($p) ?>
                </span>
            </td>
            <td data-label="Pridelený" class="text-sm text-muted"><?= htmlspecialchars($lead['assigned_name'] ?? '—') ?></td>
            <?php endif; ?>
            <td data-label="Dátum" class="td-date"><?= date('d.m.Y', strtotime($lead['created_at'])) ?></td>
            <?php if(is_admin()): ?>
            <td data-label="Akcia" onclick="event.stopPropagation();">
                <form method="POST" style="margin:0;" data-confirm="Naozaj zmazať kontakt a všetky jeho poznámky? Táto akcia je nevratná!">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="delete_lead">
                    <input type="hidden" name="delete_id" value="<?= $lead['id'] ?>">
                    <button type="submit" class="btn btn-danger" title="Zmazať kontakt"><i class="ti ti-trash"></i></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="flex gap-1 items-center" style="padding:1rem 1.5rem;border-top:1px solid var(--border);">
        <?php
        $qs = http_build_query(array_filter(['q'=>$search,'status'=>$f_status,'priority'=>$f_priority]));
        for($i=1; $i<=$total_pages; $i++):
        ?>
        <a href="?page=<?=$i?>&<?=$qs?>" class="btn <?=$i===$page?'btn-primary':'btn-secondary'?>"><?=$i?></a>
        <?php endfor; ?>
        <span class="text-muted text-sm" style="margin-left:auto">Strana <?=$page?>/<?=$total_pages?></span>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div><!-- .card -->
</div><!-- left col -->

<!-- Right col: Board (len na Dashboarde) -->
<?php if(!$is_contacts_mode): ?>
<div>
    <div class="card mb-2 <?= !empty($celebrants) ? 'celebration-active' : '' ?>" style="padding: 0;">
        <div class="card-header" style="border-bottom: 1px solid var(--border); display: flex; align-items: center;">
            <div class="card-title" style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.25rem; line-height: 1;">🎉</span>
                <span>Meniny a Sviatky</span>
            </div>
        </div>
        
        <div style="padding: 1rem 1.5rem 1.5rem 1.5rem;">
            <div class="text-xs text-muted mb-1 font-bold text-uppercase" style="letter-spacing:1px; line-height: 1;">Dnes má meniny</div>
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1rem; line-height: 1.2;">
                <?= htmlspecialchars($today_names) ?>
            </div>
            
            <?php if(!empty($celebrants)): ?>
                <div style="background: rgba(99, 102, 241, 0.1); padding: 1rem; border-radius: 12px; border: 1px solid rgba(99, 102, 241, 0.2);">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--accent-2); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ti ti-confetti" style="font-size: 1.2rem;"></i>
                        Dnes oslavuje <?= count($celebrants) ?> dopytov!
                    </div>
                    <div class="flex flex-wrap gap-1 mb-1">
                        <?php foreach($celebrants as $c): ?>
                            <a href="contact.php?id=<?= $c['id'] ?>" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 10px;"><?= htmlspecialchars($c['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <a href="marketing.php?type=nameday&date=<?= $today_key ?>" class="btn btn-primary w-full mt-1" style="justify-content:center; gap: 8px;">
                        <i class="ti ti-mail"></i> Poslať hromadné prianie
                    </a>
                </div>
            <?php else: ?>
                <div class="text-sm text-muted" style="background: rgba(255, 255, 255, 0.03); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
                    <i class="ti ti-info-circle"></i> Medzi tvojimi kontaktmi dnes nikto neoslavuje.
                </div>
            <?php endif; ?>
        </div>
    </div>
    

    <!-- Recent Activities (Process) -->
    <div class="card mt-2">
        <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
            <div class="card-title" style="display: flex; align-items: center; gap: 8px;">
                <i class="ti ti-activity" style="color:var(--accent-2);"></i>
                Nedávna aktivita
            </div>
        </div>
        <div style="padding: 1rem;">
            <?php if (empty($recent_activities)): ?>
                <p class="text-xs text-muted">Zatiaľ žiadna aktivita.</p>
            <?php else: ?>
                <div class="timeline-mini" id="dashboard-timeline" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($recent_activities as $idx => $ra): ?>
                        <div class="activity-item" style="position: relative; padding-left: 1.5rem; <?= $idx >= 5 ? 'display:none;' : '' ?>">
                            <div style="position: absolute; left: 0; top: 0.25rem; width: 8px; height: 8px; border-radius: 50%; background: var(--accent-2); box-shadow: 0 0 8px var(--accent-glow);"></div>
                            <div style="font-size: 0.8rem; font-weight: 700; display: flex; justify-content: space-between;">
                                <a href="contact.php?id=<?= $ra['lead_id'] ?>" style="color:inherit; text-decoration:none;"><?= htmlspecialchars($ra['lead_name']) ?></a>
                                <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 400;"><?= time_ago($ra['created_at']) ?></span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary); line-height: 1.4; margin-top: 2px;">
                                <?= mb_substr(strip_tags($ra['content']), 0, 70) ?><?= mb_strlen($ra['content']) > 70 ? '...' : '' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($recent_activities) > 5): ?>
                    <button type="button" id="show-all-activity" class="btn btn-secondary w-full mt-2" style="font-size: 0.75rem; justify-content: center; gap: 8px;" onclick="toggleDashboardActivity()">
                        <span id="activity-btn-text"><i class="ti ti-arrows-vertical"></i> Zobraziť celú aktivitu</span>
                    </button>
                    <script>
                    function toggleDashboardActivity() {
                        const items = document.querySelectorAll('#dashboard-timeline .activity-item');
                        const btnText = document.getElementById('activity-btn-text');
                        const isExpanded = items[items.length - 1].style.display !== 'none';

                        if (isExpanded) {
                            items.forEach((el, idx) => { if(idx >= 5) el.style.display = 'none'; });
                            btnText.innerHTML = '<i class="ti ti-arrows-vertical"></i> Zobraziť celú aktivitu';
                        } else {
                            items.forEach(el => el.style.display = 'block');
                            btnText.innerHTML = '<i class="ti ti-arrows-minimize"></i> Zbaliť aktivitu';
                        }
                    }
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div><!-- right col -->
<?php endif; ?>
</div><!-- grid -->

<style>
.celebration-active {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1)) !important;
    border-color: var(--accent-glow) !important;
}
.celebration-active .stat-icon {
    color: var(--accent-2) !important;
    opacity: 0.5 !important;
}
.doc-item:hover {
    background: rgba(99, 102, 241, 0.1) !important;
    border-color: var(--accent) !important;
    color: var(--text-primary) !important;
    transform: translateX(4px);
}
</style>
<?php include __DIR__ . '/partials/footer.php'; ?>
