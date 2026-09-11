<?php
require_once __DIR__ . '/auth.php';
require_login();
date_default_timezone_set('Europe/Bratislava');

$pdo = db_connect();
$user = current_user();

// --- Schema Migration ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        event_date DATE NOT NULL,
        end_date DATE NULL,
        type ENUM('vacation', 'pn', 'doctor', 'note') NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES crm_users(id) ON DELETE CASCADE
    )");
    // Safely add end_date column if it's missing (CREATE TABLE IF NOT EXISTS doesn't add columns to existing tables)
    $pdo->exec("ALTER TABLE calendar_events ADD COLUMN end_date DATE NULL AFTER event_date");
} catch (Throwable $e) {
    // Migration failed or column already exists, safe to ignore
}

// --- View Selection ---
$view  = $_GET['view'] ?? 'month';
$today_date = date('Y-m-d');

// --- Month / Year Selection ---
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$first_day_ts = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = (int)date('t', $first_day_ts);
$start_wday = (int)date('N', $first_day_ts); // 1 (Mon) to 7 (Sun)

$prev_m = $month - 1; $prev_y = $year;
$next_m = $month + 1; $next_y = $year;

// --- Fetch Events ---
if ($view === 'today') {
    $start_date = $today_date;
    $end_date   = $today_date;
} else {
    $start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $end_date   = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-$days_in_month";
}

$sql = "SELECT id, name, phone, source,
               survey_date, survey_time,
               production_at, 
               realization_date, realization_time,
               next_followup, next_followup_time
        FROM leads 
        WHERE (survey_date BETWEEN ? AND ?)
           OR (production_at BETWEEN ? AND ?)
           OR (realization_date BETWEEN ? AND ?)
           OR (next_followup BETWEEN ? AND ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
$leads = $stmt->fetchAll();

// --- Fetch User Events (Vacations, Notes) ---
$sql_user_events = "SELECT e.*, u.full_name 
                    FROM calendar_events e 
                    JOIN crm_users u ON e.user_id = u.id 
                    WHERE (e.event_date <= ? AND (e.end_date >= ? OR e.end_date IS NULL))
                    AND u.full_name != 'Administrátor'
                    ORDER BY e.event_date ASC";
$stmt_ue = $pdo->prepare($sql_user_events);
$stmt_ue->execute([$end_date, $start_date]);
$user_evs = $stmt_ue->fetchAll();

// --- Fetch Board Events ---
$sql_board = "SELECT b.id, b.title, b.event_date, b.event_time, b.color, u.full_name AS author_name 
              FROM crm_board b
              JOIN crm_users u ON b.user_id = u.id
              WHERE b.event_date BETWEEN ? AND ?
              AND (b.target_user_id IS NULL OR b.target_user_id = ? OR b.user_id = ?)";
$stmt_b = $pdo->prepare($sql_board);
$stmt_b->execute([$start_date, $end_date, $user['id'], $user['id']]);
$board_evs = $stmt_b->fetchAll();

$events = [];
foreach ($leads as $l) {
    if (!empty($l['survey_date']))      $events[$l['survey_date']][]      = ['type'=>'survey',      'time'=>$l['survey_time'],      'lead'=>$l];
    if (!empty($l['production_at']))    $events[$l['production_at']][]    = ['type'=>'production',  'time'=>null,                   'lead'=>$l];
    if (!empty($l['realization_date'])) $events[$l['realization_date']][] = ['type'=>'realization', 'time'=>$l['realization_time'], 'lead'=>$l];
    if (!empty($l['next_followup']))    $events[$l['next_followup']][]    = ['type'=>'followup',    'time'=>$l['next_followup_time'],'lead'=>$l];
}

foreach ($user_evs as $ue) {
    if (empty($ue['event_date'])) continue;
    
    try {
        if (empty($ue['end_date']) || $ue['end_date'] === $ue['event_date']) {
            $events[$ue['event_date']][] = ['type'=>'user_event', 'time'=>null, 'event'=>$ue];
        } else {
            $start_ts = strtotime($ue['event_date']);
            $end_ts   = strtotime($ue['end_date']);
            
            if (!$start_ts || !$end_ts || $end_ts < $start_ts) {
                $events[$ue['event_date']][] = ['type'=>'user_event', 'time'=>null, 'event'=>$ue];
                continue;
            }

            $current_ts = $start_ts;
            $safety_counter = 0;
            while ($current_ts <= $end_ts && $safety_counter < 366) {
                $d_str = date('Y-m-d', $current_ts);
                $events[$d_str][] = ['type'=>'user_event', 'time'=>null, 'event'=>$ue];
                $current_ts = strtotime('+1 day', $current_ts);
                $safety_counter++;
            }
        }
    } catch (Throwable $e) {
        // Skip
    }
}

foreach ($board_evs as $be) {
    if (empty($be['event_date'])) continue;
    $events[$be['event_date']][] = ['type'=>'board_event', 'time'=>$be['event_time'], 'board'=>$be];
}

$page_title = "Kalendár";
include __DIR__ . '/partials/header.php';
?>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--border);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.calendar-header-day {
    background: var(--bg-hover);
    padding: .5rem; /* Compact header */
    text-align: center;
    font-size: .75rem; /* Smaller text */
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
}
.calendar-day {
    background: var(--bg-card);
    min-height: 120px; /* Precise height for single-screen view */
    padding: .6rem; /* More airy padding */
    display: flex;
    flex-direction: column;
    gap: .2rem;
}
.calendar-day.empty { background: var(--bg-base); opacity: .5; }
.calendar-day.today { background: rgba(99,102,241,.05); }
.calendar-day.today .day-num { color: var(--accent-2); font-weight: 800; }

.day-num { font-size: .85rem; font-weight: 600; margin-bottom: .15rem; padding: 1px 4px; }

.cal-event {
    font-size: .72rem;
    padding: 3px 6px;
    border-radius: 4px;
    text-decoration: none;
    line-height: 1.2;
    display: block;
    color: #fff;
    border-left: 3px solid rgba(0,0,0,.2);
    transition: transform .1s;
}
.cal-event:hover { transform: scale(1.02); filter: brightness(1.1); }

.ev-survey      { background: var(--yellow); }
.ev-production  { background: var(--accent); }
.ev-realization { background: var(--green); }
.ev-followup    { background: var(--purple); }
.ev-user-vacation  { background: var(--cyan); color: #fff; border-color: rgba(0,0,0,0.1); }
.ev-user-pn        { background: var(--red); color: #fff; border-color: rgba(0,0,0,0.1); }
.ev-user-doctor    { background: var(--orange); color: #fff; border-color: rgba(0,0,0,0.1); }
.ev-user-note      { background: var(--bg-hover); color: var(--text-primary); border: 1px solid var(--border); }
.ev-board-event { background: var(--bg-hover); color: var(--accent-2); border: 1px solid var(--accent); border-left: 3px solid var(--accent); font-weight: 700; }

/* Edit Modal Styles */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,.7); display: none; align-items: center; justify-content: center; z-index: 1000;
    backdrop-filter: blur(4px);
}
.modal-content {
    background: var(--bg-card); padding: 2rem; border-radius: var(--radius-lg);
    width: 100%; max-width: 450px; border: 1px solid var(--border);
}

.cal-controls {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.month-title { font-size: 1.5rem; font-weight: 800; min-width: 220px; text-align: center; }

@media (max-width: 1024px) {
    .calendar-grid { display: block !important; border: none; background: none; }
    .calendar-header-day { display: none; }
    .calendar-day { 
        min-height: auto; 
        padding: 1rem; 
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        margin-bottom: 1rem;
        background: var(--bg-card);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .calendar-day.empty { display: none; }
    .calendar-day.is-empty:not(.today),
    .calendar-day.is-past { display: none !important; }
    
    .calendar-day.today { 
        border: 2px solid var(--accent); 
        background: rgba(99,102,241,0.08); 
        box-shadow: 0 0 20px var(--accent-glow);
    }
    .day-num { 
        font-size: 1rem; 
        font-weight: 800;
        border-bottom: 1px solid var(--border); 
        margin-bottom: 0.75rem; 
        padding-bottom: 0.5rem; 
        display: flex; 
        align-items: center; 
        gap: 0.5rem; 
        color: var(--text-primary);
    }
    .day-num::before { content: "\eb91"; font-family: "tabler-icons"; font-size: 1rem; opacity: 0.7; }
    .calendar-day.today .day-num::after { content: "Dnes"; font-size: 0.65rem; color: #fff; background: var(--accent); padding: 2px 6px; border-radius: 4px; margin-left: auto; }
    
    .cal-event {
        font-size: 0.85rem;
        padding: 8px 12px;
        margin-bottom: 4px;
        border-radius: 8px;
        border-left-width: 5px;
    }

    .cal-controls { flex-direction: column; gap: 1rem; align-items: stretch; }
    .month-title { min-width: 100%; text-align: center; font-size: 1.4rem; margin-bottom: 0.5rem; }
    .cal-controls .btn { width: 100%; height: 38px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; margin: 0 !important; }
}
</style>

<div class="cal-controls">
    <?php if($view === 'month'): ?>
        <a href="?m=<?= $prev_m ?>&y=<?= $prev_y ?>" class="btn btn-secondary"><i class="ti ti-chevron-left"></i> Predchádzajúci</a>
        <div class="month-title">
            <?= date('F', $first_day_ts) === 'January' ? 'Január' : '' ?>
            <?= date('F', $first_day_ts) === 'February' ? 'Február' : '' ?>
            <?= date('F', $first_day_ts) === 'March' ? 'Marec' : '' ?>
            <?= date('F', $first_day_ts) === 'April' ? 'Apríl' : '' ?>
            <?= date('F', $first_day_ts) === 'May' ? 'Máj' : '' ?>
            <?= date('F', $first_day_ts) === 'June' ? 'Jún' : '' ?>
            <?= date('F', $first_day_ts) === 'July' ? 'Júl' : '' ?>
            <?= date('F', $first_day_ts) === 'August' ? 'August' : '' ?>
            <?= date('F', $first_day_ts) === 'September' ? 'September' : '' ?>
            <?= date('F', $first_day_ts) === 'October' ? 'Október' : '' ?>
            <?= date('F', $first_day_ts) === 'November' ? 'November' : '' ?>
            <?= date('F', $first_day_ts) === 'December' ? 'December' : '' ?>
            <?= $year ?>
        </div>
        <a href="?m=<?= $next_m ?>&y=<?= $next_y ?>" class="btn btn-secondary">Nasledujúci <i class="ti ti-chevron-right"></i></a>
        <a href="?view=today" class="btn btn-primary" style="margin-left:auto;"><i class="ti ti-calendar-event"></i> Dnešná agenda</a>
    <?php else: ?>
        <div class="month-title"><i class="ti ti-calendar-event"></i> Dnešná agenda: <?= date('d.m.Y') ?></div>
        <a href="?view=month" class="btn btn-secondary" style="margin-left:auto;"><i class="ti ti-calendar"></i> Späť na mesačný náhľad</a>
    <?php endif; ?>
</div>

<?php if($view === 'today'): ?>
    <div class="card" style="border:2px solid var(--accent-glow);">
        <div class="card-header">
            <div class="card-title"><i class="ti ti-pin"></i> Čo ťa dnes čaká? (<?= count($events[$today_date]??[]) ?> akcií)</div>
        </div>
        <?php if(empty($events[$today_date])): ?>
            <div class="empty-state">
                <div class="es-icon"><i class="ti ti-coffee"></i></div>
                <p>Na dnes nemáš naplánované žiadne hovory, obhliadky ani realizácie. Daj si kávu!</p>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1rem;">
            <?php foreach($events[$today_date] as $ev): ?>
                <?php if($ev['type'] === 'user_event'): ?>
                <?php 
                $ue_color = 'var(--border)';
                if($ev['event']['type'] === 'vacation') $ue_color = 'var(--cyan)';
                if($ev['event']['type'] === 'pn')       $ue_color = 'var(--red)';
                if($ev['event']['type'] === 'doctor')   $ue_color = 'var(--orange)';
                ?>
                <div class="flex items-center gap-2 p-1" style="background:var(--bg-hover);padding:1rem;border-radius:var(--radius-md);border-left:5px solid <?= $ue_color ?>;">
                    <div style="font-size:1.5rem;">
                        <?php 
                        if($ev['event']['type'] === 'vacation') echo '<i class="ti ti-beach"></i>';
                        if($ev['event']['type'] === 'pn')       echo '<i class="ti ti-pill"></i>';
                        if($ev['event']['type'] === 'doctor')   echo '<i class="ti ti-building-hospital"></i>';
                        if($ev['event']['type'] === 'note')     echo '<i class="ti ti-note"></i>';
                        ?>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">
                            <?php 
                            if($ev['event']['type'] === 'vacation') echo 'Dovolenka';
                            if($ev['event']['type'] === 'pn')       echo 'PN (Choroba)';
                            if($ev['event']['type'] === 'doctor')   echo 'Lekár';
                            if($ev['event']['type'] === 'note')     echo 'Poznámka';
                            ?>
                        </div>
                        <div style="font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($ev['event']['full_name']) ?></div>
                        <div class="text-sm"><?= htmlspecialchars($ev['event']['description']) ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex items-center gap-2 p-1" style="background:var(--bg-hover);padding:1rem;border-radius:var(--radius-md);border-left:5px solid <?= $ev['type']==='board_event'?'var(--accent)':($ev['type']==='survey'?'var(--yellow)':($ev['type']==='production'?'var(--accent)':($ev['type']==='realization'?'var(--green)':'var(--purple)'))) ?>;">
                    <div style="font-size:1.5rem;">
                        <?= $ev['type'] === 'board_event' ? '<i class="ti ti-pin"></i>' : '' ?>
                        <?= $ev['type'] === 'survey' ? '<i class="ti ti-search"></i>' : '' ?>
                        <?= $ev['type'] === 'production' ? '<i class="ti ti-tools-kitchen-2"></i>' : '' ?>
                        <?= $ev['type'] === 'realization' ? '<i class="ti ti-hammer"></i>' : '' ?>
                        <?= $ev['type'] === 'followup' ? '<i class="ti ti-phone-outgoing"></i>' : '' ?>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">
                            <?php 
                            if($ev['type']==='board_event') echo 'Odkaz z nástenky';
                            elseif($ev['type']==='survey') echo 'Obhliadka';
                            elseif($ev['type']==='production') echo 'Zadanie do výroby';
                            elseif($ev['type']==='realization') echo 'Realizácia';
                            else {
                                echo ($ev['lead']['source'] === 'web' ? '🌐 Spätný hovor (Web)' : 'Spätný hovor');
                            }
                            ?>
                        </div>
                        <div style="font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($ev['type']==='board_event'?$ev['board']['title']:$ev['lead']['name']) ?></div>
                        <?php if($ev['time']): ?><div class="text-sm">🕒 Čas: <strong><?= substr($ev['time'],0,5) ?></strong></div><?php endif; ?>
                    </div>
                    <div style="text-align:right;display:flex;flex-direction:column;gap:.5rem;">
                        <?php if($ev['type'] === 'board_event'): ?>
                            <a href="board.php" class="btn btn-secondary">Otvoriť tabuľu</a>
                        <?php else: ?>
                            <a href="contact.php?id=<?= $ev['lead']['id'] ?>" class="btn btn-secondary">Detail</a>
                            <?php if($ev['type'] === 'followup'): ?>
                                <button onclick="markFollowupDone(<?= $ev['lead']['id'] ?>)" class="btn btn-primary" style="background:var(--green);border:none;"> <i class="ti ti-check"></i> Vybaviť</button>
                            <?php endif; ?>
                            <button onclick="openEditModal('<?= $ev['type'] ?>', <?= $ev['lead']['id'] ?>, '<?= $ev['lead']['name'] ?>', '<?= $today_date ?>', '<?= $ev['time'] ?>')" class="btn btn-secondary"><i class="ti ti-clock"></i> Zmeniť čas</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="calendar-grid">
        <div class="calendar-header-day">Po</div>
        <div class="calendar-header-day">Ut</div>
        <div class="calendar-header-day">St</div>
        <div class="calendar-header-day">Št</div>
        <div class="calendar-header-day">Pi</div>
        <div class="calendar-header-day">So</div>
        <div class="calendar-header-day">Ne</div>

        <?php
        $mobile_shown_count = 0; // Track how many days we show on mobile
        
        // Padding for first week
        for ($i = 1; $i < $start_wday; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }

        // Days
        for ($d = 1; $d <= $days_in_month; $d++) {
            $curr_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
            $is_today = ($curr_date === date('Y-m-d'));
            $is_past  = ($curr_date < date('Y-m-d'));
            ?>
            <div class="calendar-day <?= $is_today ? 'today' : '' ?> <?= empty($events[$curr_date]) ? 'is-empty' : '' ?> <?= $is_past ? 'is-past' : '' ?>">
                <div class="day-num"><?= $d ?></div>
                <?php if (isset($events[$curr_date])): ?>
                    <?php foreach ($events[$curr_date] as $ev): ?>
                        <?php if ($ev['type'] !== 'user_event'): ?>
                            <div class="cal-event ev-<?= $ev['type'] ?>" title="<?= htmlspecialchars($ev['lead']['name']) ?>">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <a href="contact.php?id=<?= $ev['lead']['id'] ?>" style="color:white; text-decoration:none; flex:1;">
                                        <strong>
                                            <?= $ev['type'] === 'survey' ? '<i class="ti ti-search" style="font-size:0.8rem;"></i>' : '' ?>
                                            <?= $ev['type'] === 'production' ? '<i class="ti ti-tools-kitchen-2" style="font-size:0.8rem;"></i>' : '' ?>
                                            <?= $ev['type'] === 'realization' ? '<i class="ti ti-hammer" style="font-size:0.8rem;"></i>' : '' ?>
                                            <?= $ev['type'] === 'followup' ? ($ev['lead']['source'] === 'web' ? '🌐' : '<i class="ti ti-phone-outgoing" style="font-size:0.8rem;"></i>') : '' ?>
                                            <?= $ev['time'] ? substr($ev['time'], 0, 5) : '' ?>
                                        </strong>
                                        <?= htmlspecialchars(mb_substr($ev['lead']['name'], 0, 10)) ?>
                                    </a>
                                    <button onclick="openEditModal('<?= $ev['type'] ?>', <?= $ev['lead']['id'] ?>, '<?= $ev['lead']['name'] ?>', '<?= $curr_date ?>', '<?= $ev['time'] ?>')" 
                                            style="background:none; border:none; color:white; padding:0 2px; cursor:pointer; font-size:11px; opacity:0.8;">
                                        <i class="ti ti-clock"></i>
                                    </button>
                                </div>
                            </div>
                        <?php elseif ($ev['type'] === 'board_event'): ?>
                            <div class="cal-event ev-board-event" title="<?= htmlspecialchars($ev['board']['title']) ?>">
                                <a href="board.php" style="color:inherit; text-decoration:none; display:flex; justify-content:space-between; align-items:center;">
                                    <strong>
                                        <i class="ti ti-pin" style="font-size:0.8rem;"></i>
                                        <?= $ev['time'] ? substr($ev['time'], 0, 5) : '' ?>
                                    </strong>
                                    <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1; margin-left:4px;">
                                        <?= htmlspecialchars(mb_substr($ev['board']['title'], 0, 12)) ?>
                                    </span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="cal-event ev-user-<?= $ev['event']['type'] ?>" style="cursor:default;" title="<?= htmlspecialchars($ev['event']['description']) ?>">
                                <span style="font-size:.8rem;">
                                    <?php 
                                    if($ev['event']['type'] === 'vacation') echo '<i class="ti ti-beach"></i>';
                                    if($ev['event']['type'] === 'pn')       echo '<i class="ti ti-pill"></i>';
                                    if($ev['event']['type'] === 'doctor')   echo '<i class="ti ti-building-hospital"></i>';
                                    if($ev['event']['type'] === 'note')     echo '<i class="ti ti-note"></i>';
                                    ?>
                                </span>
                                <strong><?= htmlspecialchars($ev['event']['full_name']) ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php
            if (!empty($events[$curr_date]) || $is_today) $mobile_shown_count++;
        }

        // Padding for last week
        $remaining = (7 - (($start_wday + $days_in_month - 1) % 7)) % 7;
        for ($i = 0; $i < $remaining; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }
        ?>
    </div>
    
    <?php if($mobile_shown_count === 0): ?>
    <div class="empty-state mobile-only-block" style="padding:4rem 2rem; background:rgba(255,255,255,0.02); border-radius:var(--radius-lg); border:1px dashed var(--border);">
        <div class="es-icon"><i class="ti ti-calendar"></i></div>
        <p>V tomto mesiaci nemáš naplánované žiadne udalosti ani poznámky.</p>
    </div>
    <?php endif; ?>

    <style>
    /* Helper to show the block only on mobile */
    .mobile-only-block { display: none; }
    @media (max-width: 900px) {
        .mobile-only-block { display: block; }
    }
    </style>

    <div class="mt-3 flex gap-2" style="flex-wrap:wrap;">
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--purple);border-radius:2px;"></span> Volanie</div>
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--yellow);border-radius:2px;"></span> Obhliadka</div>
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--accent);border-radius:2px;"></span> Výroba</div>
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--green);border-radius:2px;"></span> Realizácia</div>
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--orange);border-radius:2px;"></span> Lekár</div>
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--red);border-radius:2px;"></span> PN</div>
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--cyan);border-radius:2px;"></span> Dovolenka</div>
        <div class="flex items-center gap-1 text-sm"><span style="width:12px;height:12px;background:var(--bg-hover);border:1px solid var(--border);border-radius:2px;"></span> Poznámka / Iné</div>
    </div>
<?php endif; ?>

<!-- Edit Lead Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-bottom: 1.5rem;">Zmena termínu</h3>
        <form id="editForm">
            <input type="hidden" id="editLeadId" name="id">
            <input type="hidden" id="editType" name="type">
            <input type="hidden" name="action" value="update_lead_date">
            
            <div class="form-group">
                <label id="dateLabel">Nový dátum</label>
                <input type="date" id="editDate" name="date" class="form-control" required style="width:100%;">
            </div>
            <div class="form-group mt-2">
                <label>Čas</label>
                <input type="time" id="editTime" name="time" class="form-control" style="width:100%;">
            </div>
            
            <div class="flex gap-2 mt-3" style="justify-content:flex-end;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Zrušiť</button>
                <button type="submit" class="btn btn-primary">Uložiť zmenu</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(type, id, name, currDate, currTime) {
    document.getElementById('editLeadId').value = id;
    document.getElementById('editType').value = type;
    document.getElementById('editDate').value = currDate;
    document.getElementById('editTime').value = currTime ? currTime.substring(0,5) : '';
    
    let typeName = '';
    if(type === 'survey') typeName = 'zamerania';
    if(type === 'realization') typeName = 'realizácie';
    if(type === 'followup') typeName = 'volania';
    
    document.getElementById('modalTitle').innerText = 'Zmena ' + typeName + ': ' + name;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('calendar_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            location.reload();
        } else {
            crmAlert('Chyba', data.message);
        }
    });
});

function markFollowupDone(id) {
    crmConfirm('Potvrdenie', 'Označiť hovor za vybavený? Odstráni sa z kalendára.', (ok) => {
        if (!ok) return;
        const formData = new FormData();
        formData.append('action', 'mark_followup_done');
        formData.append('id', id);
        fetch('calendar_action.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload();
            } else {
                crmAlert('Chyba', data.message);
            }
        });
    });
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
