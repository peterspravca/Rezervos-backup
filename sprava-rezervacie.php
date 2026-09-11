<?php
require_once 'config.php';

$token = trim($_GET['token'] ?? '');
$booking = null;
$error = '';

if (empty($token)) {
    $error = 'Chýba odkaz na rezerváciu. Skontrolujte prosím, či ste otvorili celý odkaz z e-mailu.';
} else {
    $stmt = $conn->prepare("SELECT b.*, s.name as service_name, e.name as establishment_name, e.address, e.city
                             FROM bookings b
                             LEFT JOIN services s ON s.id = b.service_id
                             LEFT JOIN establishments e ON e.id = b.establishment_id
                             WHERE b.manage_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    if (!$booking) {
        $error = 'Rezervácia sa nenašla. Odkaz je buď neplatný, alebo bol už raz použitý.';
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<title>Správa rezervácie — Rezervos</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root { --primary-color: #b08042; --bg-color: #f7f5f2; --card-bg: #ffffff; --text-primary: #2b2419; --text-secondary: #6f6455; --border-color: #e6dcca; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Outfit', sans-serif; background: var(--bg-color); color: var(--text-primary); }
    .wrap { max-width: 480px; margin: 0 auto; padding: 40px 20px; }
    .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    h1 { font-size: 20px; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row span:first-child { color: var(--text-secondary); }
    .detail-row span:last-child { font-weight: 600; text-align: right; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; width: 100%; padding: 13px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; border: none; margin-top: 10px; }
    .btn-primary { background: var(--primary-color); color: #fff; }
    .btn-danger { background: #fff; color: #c0392b; border: 1.5px solid #c0392b; }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .msg { padding: 12px 14px; border-radius: 10px; font-size: 13.5px; margin-bottom: 16px; }
    .msg-error { background: #fdecea; color: #c0392b; }
    .msg-success { background: #e8f5e9; color: #2e7d32; }
    #reschedule-box { display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); }
    input[type=date], select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 14px; margin-bottom: 10px; }
    .slots { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .slot-btn { padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; font-size: 13px; }
    .slot-btn.selected { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>📅 Vaša rezervácia</h1>
    <div id="app-content">
      <?php if ($error): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
      <?php else: ?>
        <?php $can_manage = !in_array($booking['status'], ['cancelled', 'completed']); ?>
        <?php if (!$can_manage): ?>
          <div class="msg msg-error">Táto rezervácia je už <?= $booking['status'] === 'cancelled' ? 'zrušená' : 'ukončená' ?> a nedá sa upraviť.</div>
        <?php endif; ?>
        <div class="detail-row"><span>Prevádzka</span><span><?= htmlspecialchars($booking['establishment_name'] ?? '') ?></span></div>
        <div class="detail-row"><span>Služba</span><span><?= htmlspecialchars($booking['service_name'] ?? 'Rezervovaná služba') ?></span></div>
        <div class="detail-row"><span>Dátum</span><span id="detail-date"><?= htmlspecialchars($booking['booking_date']) ?></span></div>
        <div class="detail-row"><span>Čas</span><span id="detail-time"><?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?></span></div>
        <div class="detail-row"><span>Adresa</span><span><?= htmlspecialchars(trim(($booking['address'] ?? '') . ', ' . ($booking['city'] ?? ''), ', ')) ?></span></div>

        <?php
            $cal_start_dt = new DateTime($booking['booking_date'] . ' ' . $booking['start_time']);
            $cal_end_dt = (clone $cal_start_dt)->modify('+30 minutes'); // presné trvanie sa doplní pri sťahovaní .ics
            $cal_title = urlencode(($booking['service_name'] ?: 'Rezervácia') . ' - ' . $booking['establishment_name']);
            $gcal_url = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . $cal_title
                . '&dates=' . $cal_start_dt->format('Ymd\THis') . '/' . $cal_end_dt->format('Ymd\THis');
            $outlook_url = 'https://outlook.office.com/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose&rru=addevent&subject=' . $cal_title
                . '&startdt=' . $cal_start_dt->format('c') . '&enddt=' . $cal_end_dt->format('c');
        ?>
        <div style="display:flex; flex-direction:column; gap:8px; margin: 16px 0; padding: 14px; background: var(--bg-color); border-radius: 10px;">
          <a href="<?= htmlspecialchars($gcal_url) ?>" target="_blank" style="color:var(--primary-color); font-weight:700; font-size:13px; text-decoration:none;">📅 Pridať do Google kalendára</a>
          <a href="<?= htmlspecialchars($outlook_url) ?>" target="_blank" style="color:var(--primary-color); font-weight:700; font-size:13px; text-decoration:none;">📅 Pridať do Outlook kalendára</a>
          <a href="api/download_ics.php?token=<?= urlencode($token) ?>" style="color:var(--primary-color); font-weight:700; font-size:13px; text-decoration:none;">📅 Pridať do Apple / iný kalendár (.ics)</a>
        </div>

        <?php if ($can_manage): ?>
        <button class="btn btn-primary" onclick="showReschedule()">Zmeniť termín</button>
        <button class="btn btn-danger" id="btn-cancel" onclick="cancelBooking()">Zrušiť tento termín</button>
        <?php if (!empty($booking['recurring_series_id'])): ?>
        <button class="btn btn-danger" id="btn-cancel-series" onclick="cancelSeries()">Zrušiť tento a všetky budúce termíny (opakovaná rezervácia)</button>
        <?php endif; ?>

        <div id="reschedule-box">
          <label style="font-size:13px;font-weight:700;">Pracovník:</label>
          <select id="new-employee" onchange="loadRescheduleSlots()">
            <option value="0">Ktokoľvek voľný</option>
          </select>
          <label style="font-size:13px;font-weight:700;">Nový dátum:</label>
          <input type="date" id="new-date" min="<?= date('Y-m-d') ?>" onchange="loadRescheduleSlots()">
          <div id="reschedule-slots" class="slots"></div>
          <button class="btn btn-primary" id="btn-confirm-reschedule" onclick="confirmReschedule()" disabled>Potvrdiť nový termín</button>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const TOKEN = <?= json_encode($token) ?>;
const ESTABLISHMENT_ID = <?= (int)($booking['establishment_id'] ?? 0) ?>;
const SERVICE_ID = <?= (int)($booking['service_id'] ?? 0) ?>;
const ORIGINAL_EMPLOYEE_ID = <?= (int)($booking['employee_id'] ?? 0) ?>;
let selectedNewTime = null;

function showMsg(text, type) {
    const el = document.createElement('div');
    el.className = 'msg msg-' + type;
    el.textContent = text;
    document.getElementById('app-content').prepend(el);
}

function confirmModal(message, options = {}) {
    return new Promise((resolve) => {
        let overlay = document.getElementById('_confirm_modal_overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = '_confirm_modal_overlay';
            overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:100001;align-items:center;justify-content:center;backdrop-filter:blur(2px);';
            overlay.innerHTML = `
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;max-width:420px;width:90%;padding:24px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
                    <p id="_confirm_modal_msg" style="margin:0 0 20px 0;font-size:14px;color:#111;line-height:1.55;white-space:pre-line;"></p>
                    <div style="display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" id="_confirm_modal_cancel" class="btn" style="width:auto;margin-top:0;padding:9px 18px;border-radius:10px;background:#f3f4f6;color:#374151;">Zrušiť</button>
                        <button type="button" id="_confirm_modal_ok" class="btn btn-primary" style="width:auto;margin-top:0;padding:9px 18px;border-radius:10px;">Potvrdiť</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
        }
        overlay.querySelector('#_confirm_modal_msg').textContent = message;
        const okBtn = overlay.querySelector('#_confirm_modal_ok');
        const cancelBtn = overlay.querySelector('#_confirm_modal_cancel');
        okBtn.textContent = options.okText || 'Potvrdiť';
        cancelBtn.textContent = options.cancelText || 'Zrušiť';
        overlay.style.display = 'flex';
        const cleanup = (result) => {
            overlay.style.display = 'none';
            okBtn.onclick = null; cancelBtn.onclick = null; overlay.onclick = null;
            resolve(result);
        };
        okBtn.onclick = () => cleanup(true);
        cancelBtn.onclick = () => cleanup(false);
        overlay.onclick = (e) => { if (e.target === overlay) cleanup(false); };
    });
}

async function cancelBooking() {
    if (!(await confirmModal('Naozaj chcete zrušiť túto rezerváciu?'))) return;
    const btn = document.getElementById('btn-cancel');
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'cancel_booking');
        fd.append('token', TOKEN);
        const res = await fetch('api/manage_booking.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showMsg(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showMsg(data.message, 'error');
            btn.disabled = false;
        }
    } catch (err) {
        showMsg('Chyba komunikácie so serverom.', 'error');
        btn.disabled = false;
    }
}

async function cancelSeries() {
    if (!(await confirmModal('Naozaj chcete zrušiť tento aj všetky budúce termíny tejto opakovanej rezervácie?'))) return;
    const btn = document.getElementById('btn-cancel-series');
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'cancel_series');
        fd.append('token', TOKEN);
        const res = await fetch('api/manage_booking.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showMsg(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showMsg(data.message, 'error');
            btn.disabled = false;
        }
    } catch (err) {
        showMsg('Chyba komunikácie so serverom.', 'error');
        btn.disabled = false;
    }
}

async function showReschedule() {
    document.getElementById('reschedule-box').style.display = 'block';
    const sel = document.getElementById('new-employee');
    try {
        const fd = new FormData();
        fd.append('action', 'get_employees_for_services');
        fd.append('establishment_id', ESTABLISHMENT_ID);
        fd.append('service_ids', JSON.stringify([SERVICE_ID]));
        const res = await fetch('api/availability.php', { method: 'POST', body: fd });
        const data = await res.json();
        (data.employees || []).forEach(emp => {
            const opt = document.createElement('option');
            opt.value = emp.id;
            opt.textContent = emp.name + (emp.title ? ' (' + emp.title + ')' : '');
            if (parseInt(emp.id) === ORIGINAL_EMPLOYEE_ID) { opt.selected = true; }
            sel.appendChild(opt);
        });
    } catch (err) { /* zoznam pracovníkov sa nepodarilo načítať, ostane len "Ktokoľvek voľný" */ }
}

async function loadRescheduleSlots() {
    const date = document.getElementById('new-date').value;
    const container = document.getElementById('reschedule-slots');
    selectedNewTime = null;
    document.getElementById('btn-confirm-reschedule').disabled = true;
    if (!date) { container.innerHTML = ''; return; }

    container.innerHTML = '<span style="font-size:13px;color:var(--text-secondary);">Hľadám voľné termíny...</span>';
    try {
        const fd = new FormData();
        fd.append('action', 'get_reschedule_slots');
        fd.append('token', TOKEN);
        fd.append('booking_date', date);
        fd.append('employee_id', document.getElementById('new-employee').value);
        const res = await fetch('api/manage_booking.php', { method: 'POST', body: fd });
        const data = await res.json();
        container.innerHTML = '';
        if (!data.success || !data.slots || data.slots.length === 0) {
            container.innerHTML = '<span style="font-size:13px;color:var(--text-secondary);">Na tento deň nie sú voľné termíny.</span>';
            return;
        }
        data.slots.forEach(time => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'slot-btn';
            btn.textContent = time;
            btn.onclick = () => {
                document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                selectedNewTime = time;
                document.getElementById('btn-confirm-reschedule').disabled = false;
            };
            container.appendChild(btn);
        });
    } catch (err) {
        container.innerHTML = '<span style="font-size:13px;color:var(--text-secondary);">Chyba pri načítaní termínov.</span>';
    }
}

async function confirmReschedule() {
    const date = document.getElementById('new-date').value;
    if (!date || !selectedNewTime) return;
    const btn = document.getElementById('btn-confirm-reschedule');
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'reschedule_booking');
        fd.append('token', TOKEN);
        fd.append('booking_date', date);
        fd.append('start_time', selectedNewTime + ':00');
        fd.append('employee_id', document.getElementById('new-employee').value);
        const res = await fetch('api/manage_booking.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showMsg(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showMsg(data.message, 'error');
            btn.disabled = false;
            loadRescheduleSlots();
        }
    } catch (err) {
        showMsg('Chyba komunikácie so serverom.', 'error');
        btn.disabled = false;
    }
}
</script>
</body>
</html>
