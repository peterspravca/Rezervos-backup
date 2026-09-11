<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Last Minute termíny - ' . BRAND_NAME;
$currentPage = 'last-minute';
require_once 'includes/dashboard-head.php';
?>

<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>

<div class="admin-main">
    <?php $headerTitle = 'Last Minute'; $headerIcon = 'bolt'; require_once 'includes/dashboard-topbar.php'; ?>

    <div class="admin-content">
        <div class="section">

            <!-- Locked state (no profile) -->
            <div id="last-minute-locked" style="display:none;background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;padding:50px;text-align:center;box-shadow:var(--shadow-sm);">
                <span class="material-symbols-outlined" style="font-size:52px;color:var(--primary-color);margin-bottom:12px;">lock</span>
                <h3 style="margin:0 0 10px 0;">Najprv vyplňte profil</h3>
                <p style="color:var(--text-secondary);max-width:400px;margin:0 auto 20px auto;">Aby ste mohli pridávať Last Minute termíny, musíte si najskôr kompletne vyplniť a uložiť profil prevádzky.</p>
                <a href="dashboard.php" class="btn-primary" style="text-decoration:none;display:inline-flex;">
                    <span class="material-symbols-outlined" style="font-size:18px;">store</span> Vyplniť profil
                </a>
            </div>

            <!-- Active content -->
            <div id="last-minute-content">

                <!-- Info banner -->
                <div style="background:linear-gradient(135deg,rgba(176,128,66,0.12),rgba(176,128,66,0.04));border:1px solid rgba(176,128,66,0.25);border-radius:14px;padding:18px 22px;margin-bottom:22px;display:flex;align-items:center;gap:16px;">
                    <div style="width:46px;height:46px;border-radius:12px;background:rgba(176,128,66,0.15);color:var(--primary-color);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="material-symbols-outlined" style="font-size:26px;">bolt</span>
                    </div>
                    <div>
                        <strong style="font-size:15px;color:var(--text-primary);">Last Minute termíny</strong>
                        <p style="margin:3px 0 0 0;font-size:13px;color:var(--text-secondary);">Vytvorte zľavnený termín na blízku dobu a zaplňte svoje prázdne miesta. Zákazníci uvidia tieto ponuky ako zvýraznené na vašom profile.</p>
                    </div>
                </div>

                <!-- Add form -->
                <div class="vueto-card">
                  <div class="vueto-card-header">
                    <div>
                        <h2 class="section-header" style="margin:0;">
                            <span class="material-symbols-outlined">add_circle</span> Pridať Last Minute termín
                        </h2>
                        <p class="section-desc" style="margin:4px 0 0 0;">Vytvorte zľavnený termín. Zákazník musí rezervovať do 24 hodín.</p>
                    </div>
                  </div>
                  <div class="vueto-card-body">
                    <form id="lastMinuteForm" onsubmit="addLastMinute(event)">
                        <div class="form-group">
                            <label>Služba <span style="color:#ef4444;">*</span></label>
                            <select id="lm-service" required>
                                <option value="">Vyberte službu...</option>
                            </select>
                        </div>
                        <div style="display:flex;gap:15px;flex-wrap:wrap;">
                            <div class="form-group" style="flex:1;min-width:140px;">
                                <label>Dátum <span style="color:#ef4444;">*</span></label>
                                <input type="date" id="lm-date" required>
                            </div>
                            <div class="form-group" style="flex:1;min-width:120px;">
                                <label>Čas <span style="color:#ef4444;">*</span></label>
                                <input type="time" id="lm-time" required>
                            </div>
                        </div>
                        <div style="display:flex;gap:15px;flex-wrap:wrap;">
                            <div class="form-group" style="flex:1;min-width:140px;">
                                <label>Pôvodná cena (€) <span style="color:#ef4444;">*</span></label>
                                <input type="number" step="0.01" min="0" id="lm-orig-price" required placeholder="napr. 25.00">
                            </div>
                            <div class="form-group" style="flex:1;min-width:140px;">
                                <label>Zľavnená cena (€) <span style="color:#ef4444;">*</span></label>
                                <input type="number" step="0.01" min="0" id="lm-disc-price" required placeholder="napr. 18.00">
                            </div>
                        </div>
                        <div id="discount-preview" style="display:none;margin-bottom:16px;padding:10px 14px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:10px;font-size:13px;color:#10b981;font-weight:600;"></div>
                        <div class="form-group">
                            <label>Poznámka (voliteľné)</label>
                            <input type="text" id="lm-note" placeholder="Napr. Platí len pre prvý termín...">
                        </div>
                        <button type="submit" class="btn-primary" id="lm-submit-btn">
                            <span class="material-symbols-outlined" style="font-size:18px;">bolt</span> Pridať Last Minute termín
                        </button>
                    </form>
                  </div>
                </div>

                <!-- List -->
                <div class="vueto-card">
                  <div class="vueto-card-header">
                    <h2 class="section-header" style="margin:0;">
                        <span class="material-symbols-outlined">list</span> Aktívne Last Minute termíny
                    </h2>
                    <button class="btn" onclick="loadLastMinute()" style="padding:7px 14px;font-size:13px;">
                        <span class="material-symbols-outlined" style="font-size:16px;">refresh</span> Obnoviť
                    </button>
                  </div>
                  <div class="vueto-card-body">
                    <div id="last-minute-container">
                        <div style="text-align:center;padding:30px;color:var(--text-secondary);">
                            <span class="material-symbols-outlined" style="font-size:32px;display:block;margin-bottom:8px;">hourglass_empty</span>
                            Načítavam...
                        </div>
                    </div>
                  </div>
                </div>

            </div><!-- /last-minute-content -->
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div id="confirm-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:2000;justify-content:center;align-items:center;">
    <div class="modal-content" style="max-width:380px;text-align:center;">
        <div style="width:54px;height:54px;border-radius:50%;background:rgba(239,68,68,0.12);color:#ef4444;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <span class="material-symbols-outlined" style="font-size:28px;">delete</span>
        </div>
        <h3 id="confirm-title" style="margin:0 0 8px 0;font-size:18px;">Zmazať termín?</h3>
        <p id="confirm-message" style="margin:0 0 22px 0;font-size:13.5px;color:var(--text-secondary);line-height:1.5;">Naozaj si prajete vymazať tento Last Minute termín?</p>
        <div style="display:flex;justify-content:center;gap:12px;">
            <button class="btn" onclick="closeConfirmModal()" style="padding:9px 18px;font-size:13px;">Zrušiť</button>
            <button id="confirm-ok-btn" class="btn-primary" style="padding:9px 22px;font-size:13px;background:#ef4444 !important;border-color:#ef4444 !important;">Vymazať</button>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
// Utility + notifikácie sú v /assets/js/dashboard-common.js

// ---- Confirm Modal ----
let confirmCallback = null;

function showConfirmModal(title, message, callback) {
    document.getElementById('confirm-title').innerText = title;
    document.getElementById('confirm-message').innerText = message;
    confirmCallback = callback;
    document.getElementById('confirm-modal').style.display = 'flex';
    document.getElementById('confirm-ok-btn').onclick = function() {
        closeConfirmModal();
        if (confirmCallback) confirmCallback();
    };
}

function closeConfirmModal() {
    document.getElementById('confirm-modal').style.display = 'none';
    confirmCallback = null;
}

// ---- Discount Preview ----
function updateDiscountPreview() {
    const orig = parseFloat(document.getElementById('lm-orig-price').value) || 0;
    const disc = parseFloat(document.getElementById('lm-disc-price').value) || 0;
    const previewEl = document.getElementById('discount-preview');
    if (orig > 0 && disc > 0 && disc < orig) {
        const savings = (orig - disc).toFixed(2);
        const pct = Math.round((1 - disc / orig) * 100);
        previewEl.style.display = 'block';
        previewEl.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">savings</span> Zákazník ušetrí <strong>${savings} €</strong> (${pct}% zľava)`;
    } else {
        previewEl.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('lm-orig-price').addEventListener('input', updateDiscountPreview);
    document.getElementById('lm-disc-price').addEventListener('input', updateDiscountPreview);
});

// ---- Services ----
async function loadServices() {
    const fd = new FormData();
    fd.append('action', 'get_services');
    let res = await fetch('api/business.php', { method: 'POST', body: fd });
    let data = await res.json();
    if (data.success) {
        let selectHtml = '<option value="">Vyberte službu...</option>';
        if (data.data.length === 0) {
            selectHtml = '<option value="">Žiadne služby – najprv si pridajte služby v cenníku</option>';
        } else {
            data.data.forEach(s => {
                selectHtml += `<option value="${s.id}" data-price="${parseFloat(s.price).toFixed(2)}">${escapeHtml(s.name)} (${parseFloat(s.price).toFixed(2)} €)</option>`;
            });
        }
        document.getElementById('lm-service').innerHTML = selectHtml;

        // Auto-fill price when service selected
        document.getElementById('lm-service').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const price = opt ? opt.getAttribute('data-price') : null;
            if (price) {
                document.getElementById('lm-orig-price').value = price;
                updateDiscountPreview();
            }
        });

        // Check if profile exists
        if (data.data.length > 0) {
            document.getElementById('last-minute-locked').style.display = 'none';
            document.getElementById('last-minute-content').style.display = 'block';
        } else {
            // We have profile but no services yet - still show content
            document.getElementById('last-minute-locked').style.display = 'none';
            document.getElementById('last-minute-content').style.display = 'block';
        }
    }
}

// ---- Last Minute CRUD ----
async function loadLastMinute() {
    const fd = new FormData();
    fd.append('action', 'get_last_minute');
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        const container = document.getElementById('last-minute-container');
        if (data.success) {
            if (data.data.length === 0) {
                container.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-secondary);">
                    <span class="material-symbols-outlined" style="font-size:40px;display:block;margin-bottom:10px;opacity:0.4;">bolt</span>
                    <p style="margin:0;font-size:14px;">Nemáte žiadne aktívne Last Minute termíny.</p>
                    <p style="margin:8px 0 0 0;font-size:13px;">Pridajte prvý termín pomocou formulára vyššie.</p>
                </div>`;
                return;
            }
            let html = '';
            data.data.forEach(lm => {
                const isExpired = lm.status === 'expired';
                const isBooked = lm.status === 'booked';
                let statusHtml = '';
                if (isExpired) statusHtml = '<span class="status-badge status-blocked" style="margin-left:8px;">Expirovaný</span>';
                else if (isBooked) statusHtml = '<span class="status-badge" style="margin-left:8px;background:rgba(16,185,129,0.12);color:#10b981;">Rezervovaný</span>';

                const origPrice = parseFloat(lm.original_price || 0).toFixed(2);
                const discPrice = parseFloat(lm.discounted_price || 0).toFixed(2);
                const pct = lm.original_price > 0 ? Math.round((1 - lm.discounted_price / lm.original_price) * 100) : 0;

                html += `<div class="data-card" style="${isExpired ? 'opacity:0.55;' : ''}">
                    <div class="data-info">
                        <h3 class="data-title">${escapeHtml(lm.service_name)}</h3>
                        <div class="data-subtitle">
                            <span class="material-symbols-outlined" style="font-size:16px;">calendar_today</span>
                            ${escapeHtml(lm.slot_date)} o ${escapeHtml(lm.slot_time)}
                            ${statusHtml}
                        </div>
                        ${lm.note ? `<div class="data-detail" style="font-size:12px;color:var(--text-secondary);margin-top:4px;"><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">notes</span> ${escapeHtml(lm.note)}</div>` : ''}
                    </div>
                    <div class="data-actions">
                        <div style="text-align:right;margin-right:6px;">
                            <div><s style="color:var(--text-secondary);font-size:12px;">${origPrice} €</s></div>
                            <div style="color:var(--primary-color);font-size:20px;font-weight:700;">${discPrice} €</div>
                            ${pct > 0 ? `<div style="font-size:11px;background:rgba(176,128,66,0.12);color:var(--primary-color);border-radius:6px;padding:2px 7px;display:inline-block;margin-top:2px;font-weight:700;">-${pct}%</div>` : ''}
                        </div>
                        ${!isBooked ? `<button class="btn-action" title="Vymazať" onclick="deleteLastMinute(${lm.id})">
                            <span class="material-symbols-outlined" style="color:#ef4444;">delete</span>
                        </button>` : `<span class="material-symbols-outlined" style="color:#10b981;font-size:22px;" title="Rezervovaný zákazníkom">check_circle</span>`}
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = `<p style="color:var(--text-secondary);">Chyba pri načítaní: ${escapeHtml(data.message || 'Neznáma chyba')}</p>`;
        }
    } catch(err) {
        console.error('Error loading last minute', err);
        document.getElementById('last-minute-container').innerHTML = '<p style="color:#ef4444;">Chyba pri načítaní termínov.</p>';
    }
}

async function addLastMinute(e) {
    e.preventDefault();
    const btn = document.getElementById('lm-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">hourglass_empty</span> Ukladám...';

    const origPrice = parseFloat(document.getElementById('lm-orig-price').value) || 0;
    const discPrice = parseFloat(document.getElementById('lm-disc-price').value) || 0;
    if (discPrice >= origPrice) {
        showAppToast('Zľavnená cena musí byť nižšia ako pôvodná cena.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">bolt</span> Pridať Last Minute termín';
        return;
    }

    const fd = new FormData();
    fd.append('action', 'add_last_minute');
    fd.append('service_id', document.getElementById('lm-service').value);
    fd.append('slot_date', document.getElementById('lm-date').value);
    fd.append('slot_time', document.getElementById('lm-time').value);
    fd.append('original_price', origPrice);
    fd.append('discounted_price', discPrice);
    fd.append('note', document.getElementById('lm-note').value);

    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        if (data.success) {
            document.getElementById('lastMinuteForm').reset();
            document.getElementById('discount-preview').style.display = 'none';
            loadLastMinute();
            showAppToast('Last Minute termín bol úspešne pridaný!', 'success');
        } else {
            showAppToast(data.message || 'Chyba pri pridávaní termínu.', 'error');
        }
    } catch(err) {
        showAppToast('Chyba siete.', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">bolt</span> Pridať Last Minute termín';
}

function deleteLastMinute(id) {
    showConfirmModal(
        'Zmazať Last Minute termín?',
        'Naozaj si prajete vymazať tento Last Minute termín? Zákazníci ho prestanú vidieť okamžite.',
        async () => {
            const fd = new FormData();
            fd.append('action', 'delete_last_minute');
            fd.append('id', id);
            try {
                let res = await fetch('api/business.php', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) {
                    showAppToast('Termín bol vymazaný.', 'info');
                    loadLastMinute();
                } else {
                    showAppToast(data.message || 'Chyba pri mazaní.', 'error');
                }
            } catch(err) {
                showAppToast('Chyba siete.', 'error');
            }
        }
    );
}

// ---- Profile Check ----
async function checkProfile() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_profile');
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        if (data.success && data.profile && data.profile.name) {
            document.getElementById('last-minute-locked').style.display = 'none';
            document.getElementById('last-minute-content').style.display = 'block';
            await loadServices();
            await loadLastMinute();
        } else {
            document.getElementById('last-minute-locked').style.display = 'block';
            document.getElementById('last-minute-content').style.display = 'none';
        }
    } catch(err) {
        console.error('Profile check error', err);
        // Show content anyway as fallback
        document.getElementById('last-minute-locked').style.display = 'none';
        document.getElementById('last-minute-content').style.display = 'block';
        await loadServices();
        await loadLastMinute();
    }
}

// ---- Init ----
// Predvyplnenie formulára po kliknutí z upozornenia na zrušenú rezerváciu (zvonček/e-mail) —
// ?prefill_service, ?prefill_date, ?prefill_time, ?prefill_price
// ?quick=1&discount=20 navyše rovno odošle formulár za používateľa (odkaz z e-mailu = 1 klik)
async function applyLastMinutePrefillFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const service = params.get('prefill_service');
    const date = params.get('prefill_date');
    const time = params.get('prefill_time');
    const price = params.get('prefill_price');
    if (!service && !date && !time && !price) return;

    if (service) document.getElementById('lm-service').value = service;
    if (date) document.getElementById('lm-date').value = date;
    if (time) document.getElementById('lm-time').value = time;
    if (price) document.getElementById('lm-orig-price').value = price;

    const isQuick = params.get('quick') === '1' && service && date && time && price;
    if (isQuick) {
        const discountPct = parseFloat(params.get('discount')) || 20;
        const discPrice = Math.round(parseFloat(price) * (1 - discountPct / 100) * 100) / 100;
        document.getElementById('lm-disc-price').value = discPrice.toFixed(2);
        updateDiscountPreview();
        await addLastMinute(new Event('submit'));
        // Vyčistiť URL, aby prípadný refresh stránky ponuku nevytvoril druhýkrát
        window.history.replaceState({}, '', 'dashboard-last-minute.php');
        return;
    }

    updateDiscountPreview();
    document.getElementById('lm-service')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

document.addEventListener('DOMContentLoaded', async function() {
    // Set min date and pre-fill from URL ?date= param if present
    const dateInput = document.getElementById('lm-date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        const urlDate = new URLSearchParams(window.location.search).get('date');
        dateInput.value = (urlDate && urlDate >= today) ? urlDate : today;
    }

    await checkProfile();
    applyLastMinutePrefillFromUrl();
    // Notifikácie spravuje dashboard-common.js
});
</script>
</body>
</html>
