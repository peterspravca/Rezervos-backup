<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
$pageTitle = 'Kontakty a CRM - ' . BRAND_NAME;
$currentPage = 'kontakty';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Klienti a Kontakty'; $headerIcon = 'contacts'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">
      <div id="sec-contacts">
        <div class="vueto-card">
          <div class="vueto-card-header">
            <div>
              <h2 class="section-header"><span class="material-symbols-outlined">contacts</span> Klienti / Kontakty</h2>
              <p class="section-desc">Zoznam všetkých vašich klientov a ich kontaktné údaje.</p>
              <p style="margin:5px 0 0 0;font-size:13px;color:var(--text-secondary);">Databáza vašich zákazníkov.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <button class="btn-secondary" style="padding:8px 16px;font-size:13px;" onclick="loadCRMData()"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">refresh</span> Obnoviť</button>
              <a class="btn-secondary" style="padding:8px 16px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;" href="api/business.php?action=export_contacts"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">download</span>&nbsp;Exportovať CSV</a>
              <button class="btn-secondary" style="padding:8px 16px;font-size:13px;" onclick="openImportContactsModal()"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">upload</span> Importovať CSV</button>
              <button class="btn-primary" style="padding:8px 16px;font-size:13px;" onclick="openAddContactModal()"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">person_add</span> Pridať kontakt</button>
            </div>
          </div>
          <div class="vueto-table-wrapper">
            <table class="vueto-table">
              <thead>
                <tr>
                  <th>Klient</th>
                  <th>Kontakt</th>
                  <th>Štatistika</th>
                  <th>Hodnotenie</th>
                  <th>Akcie</th>
                </tr>
              </thead>
              <tbody id="crm-contacts-tbody">
                <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-secondary);">Načítavam dáta...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Rating Modal -->
<div id="rating-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:3000;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px;max-width:440px;width:90%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.3);">
    <h3 style="margin:0 0 6px 0;display:flex;align-items:center;gap:8px;font-size:18px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">star</span> Hodnotiť klienta</h3>
    <p id="rating-modal-name" style="margin:0 0 18px 0;font-size:14px;color:var(--text-secondary);"></p>
    <input type="hidden" id="rating-customer-id">
    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:8px;font-weight:600;font-size:14px;color:var(--text-primary);">Hodnotenie (1–5 hviezdičiek)</label>
      <div id="star-selector" style="display:flex;gap:8px;">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <button type="button" class="star-btn" data-val="<?= $i ?>" onclick="selectRatingStar(<?= $i ?>)"
          style="background:none;border:none;cursor:pointer;font-size:28px;color:#d1d5db;padding:0;transition:color 0.15s ease;" title="<?= $i ?> hviezdičiek">&#9733;</button>
        <?php endfor; ?>
      </div>
      <input type="hidden" id="rating-value" value="">
    </div>
    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:8px;font-weight:600;font-size:14px;color:var(--text-primary);">Podrobné hodnotenie (voliteľné)</label>
      <div style="display:grid;grid-template-columns:1fr auto;gap:8px 12px;align-items:center;font-size:13px;">
        <span>Dochvíľnosť</span><select id="cat-dochvilnost" style="padding:5px 8px;border-radius:6px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>
        <span>Správanie</span><select id="cat-spravanie" style="padding:5px 8px;border-radius:6px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>
        <span>Komunikácia</span><select id="cat-komunikacia" style="padding:5px 8px;border-radius:6px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>
      </div>
    </div>
    <div style="margin-bottom:18px;">
      <label style="display:block;margin-bottom:8px;font-weight:600;font-size:14px;color:var(--text-primary);">Poznámka (voliteľné, súkromná — vidíte len vy)</label>
      <textarea id="rating-comment" rows="3" placeholder="Napr. Výborný zákazník, vždy dochvíľny..." style="width:100%;padding:10px 14px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);border-radius:8px;font-family:inherit;font-size:14px;box-sizing:border-box;resize:vertical;"></textarea>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-bottom:14px;">
      <button type="button" onclick="closeRatingModal()" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
      <button type="button" onclick="submitCustomerRatingFromModal()" class="btn-primary" style="padding:10px 20px;"><span class="material-symbols-outlined" style="font-size:16px;">star</span> Uložiť hodnotenie</button>
    </div>
    <div style="border-top:1px solid var(--border-color);padding-top:14px;display:flex;justify-content:space-between;gap:10px;">
      <button type="button" onclick="openBlockCustomerPrompt()" style="background:none;border:none;color:#ef4444;font-size:12.5px;cursor:pointer;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:15px;">block</span> Zablokovať zákazníka</button>
      <button type="button" onclick="openIncidentPrompt()" style="background:none;border:none;color:#f59e0b;font-size:12.5px;cursor:pointer;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:15px;">report</span> Nahlásiť incident</button>
    </div>
  </div>
</div>

<!-- Gift Kreslo Hunter Modal -->
<div id="gift-hunter-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:3000;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px;max-width:440px;width:90%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.3);text-align:center;">
    <div style="width:56px;height:56px;border-radius:14px;background:rgba(176,128,66,0.12);color:var(--primary-color);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
      <span class="material-symbols-outlined" style="font-size:28px;">bolt</span>
    </div>
    <h3 style="margin:0 0 8px 0;font-size:18px;font-weight:800;">Darovať Kreslo Hunter</h3>
    <p id="gift-hunter-modal-name" style="margin:0 0 18px 0;font-size:14px;color:var(--text-secondary);"></p>
    <input type="hidden" id="gift-hunter-customer-id">
    <div style="background:var(--input-bg);border:1px solid var(--border-color);border-radius:14px;padding:16px;margin-bottom:20px;text-align:left;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <span style="font-size:13.5px;color:var(--text-secondary);">Dĺžka:</span>
        <strong style="font-size:14px;color:var(--text-primary);">1 rok</strong>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border-color);padding-top:8px;">
        <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Cena (–50 %, namiesto 9,00 €):</span>
        <strong style="font-size:18px;font-weight:800;color:var(--primary-color);">4,50 €</strong>
      </div>
    </div>
    <p style="margin:0 0 20px 0;font-size:12px;color:var(--text-secondary);">Suma sa strhne z reálneho zostatku vo vašej Peňaženke.</p>
    <div style="display:flex;gap:10px;">
      <button type="button" onclick="closeGiftHunterModal()" class="btn-secondary" style="flex:1;padding:12px;border-radius:12px;">Zrušiť</button>
      <button type="button" id="btn-confirm-gift-hunter" onclick="confirmGiftHunter()" class="btn-primary" style="flex:1.3;padding:12px;border-radius:12px;">Darovať za 4,50 €</button>
    </div>
  </div>
</div>

<!-- Add Manual Contact Modal -->
<div id="add-contact-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:3000;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px 30px;max-width:480px;width:90%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.3);position:relative;">
    <button type="button" onclick="closeAddContactModal()" style="position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:8px;background:var(--bg-color);border:1px solid var(--border-color);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-secondary);">
      <span class="material-symbols-outlined" style="font-size:18px;">close</span>
    </button>
    <h3 style="margin:0 0 6px 0;display:flex;align-items:center;gap:8px;font-size:18px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">person_add</span> Pridať kontakt manuálne</h3>
    <p style="margin:0 0 20px 0;font-size:13px;color:var(--text-secondary);">Pre chodiacich klientov z ulice – pridajte ich kontakt ručne.</p>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div>
        <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Meno a priezvisko *</label>
        <input type="text" id="mc-name" placeholder="Ján Novák" style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:14px;outline:none;">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">E-mail</label>
          <input type="email" id="mc-email" placeholder="jan@novak.sk" style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:14px;outline:none;">
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Telefón</label>
          <input type="tel" id="mc-phone" placeholder="+421 900 000 000" style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:14px;outline:none;">
        </div>
      </div>
      <div>
        <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:5px;">Poznámka (voliteľné)</label>
        <textarea id="mc-note" rows="2" placeholder="Napr. preferuje ranné termíny, alergia na latex..." style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:14px;font-family:inherit;resize:vertical;outline:none;"></textarea>
      </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:22px;">
      <button type="button" onclick="closeAddContactModal()" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
      <button type="button" onclick="saveManualContact()" class="btn-primary" style="padding:10px 22px;"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">save</span> Uložiť kontakt</button>
    </div>
  </div>
</div>

<!-- Import Contacts Modal -->
<div id="import-contacts-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:3000;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px 30px;max-width:720px;width:92%;max-height:85vh;overflow-y:auto;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.3);position:relative;">
    <button type="button" onclick="closeImportContactsModal()" style="position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:8px;background:var(--bg-color);border:1px solid var(--border-color);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-secondary);">
      <span class="material-symbols-outlined" style="font-size:18px;">close</span>
    </button>
    <h3 style="margin:0 0 6px 0;display:flex;align-items:center;gap:8px;font-size:18px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">upload</span> Importovať kontakty z CSV</h3>
    <p style="margin:0 0 18px 0;font-size:13px;color:var(--text-secondary);">Súbor musí mať stĺpce v poradí: Meno, E-mail, Telefón, Poznámka. <a href="api/business.php?action=contacts_import_template" style="color:var(--primary-color);font-weight:600;">Stiahnuť vzorovú šablónu</a></p>

    <div id="import-step-upload">
      <input type="file" id="import-csv-file" accept=".csv" style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:14px;margin-bottom:16px;">
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" onclick="closeImportContactsModal()" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
        <button type="button" onclick="previewImportContacts()" class="btn-primary" style="padding:10px 22px;"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">preview</span> Zobraziť náhľad</button>
      </div>
    </div>

    <div id="import-step-preview" style="display:none;">
      <div id="import-preview-summary" style="font-size:13px;color:var(--text-secondary);margin-bottom:10px;"></div>
      <div style="max-height:340px;overflow-y:auto;border:1px solid var(--border-color);border-radius:10px;">
        <table class="vueto-table" style="width:100%;">
          <thead>
            <tr><th style="width:34px;"></th><th>Meno</th><th>E-mail</th><th>Telefón</th><th>Stav</th></tr>
          </thead>
          <tbody id="import-preview-tbody"></tbody>
        </table>
      </div>
      <label style="display:flex;align-items:center;gap:8px;margin-top:16px;font-size:13px;color:var(--text-primary);cursor:pointer;">
        <input type="checkbox" id="import-send-invites">
        Poslať vybraným kontaktom s e-mailom pozvánku na registráciu (pri registrácii dostanú 6-miestny overovací kód a stanú sa plnohodnotnými používateľmi appky)
      </label>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
        <button type="button" onclick="closeImportContactsModal()" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
        <button type="button" onclick="commitImportContacts()" class="btn-primary" style="padding:10px 22px;"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">save</span> Importovať vybrané</button>
      </div>
    </div>
  </div>
</div>

<div class="app-toast-container" id="app-toast-container"></div>
<script>
async function loadCRMData() {
    const tbody = document.getElementById('crm-contacts-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-secondary);">Načítavam dáta...</td></tr>';
    const fd = new FormData();
    fd.append('action', 'get_crm_contacts');
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd });
        let data = await res.json();
        if (data.success) { renderCRMContacts(data.contacts, data.is_over_limit); }
        else { if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#e74c3c;">Chyba pri načítaní kontaktov.</td></tr>'; }
    } catch (e) {
        console.error('Error loading CRM contacts', e);
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#e74c3c;">Chyba komunikácie so serverom.</td></tr>';
    }
}

function renderCRMContacts(contacts, is_over_limit) {
    const tbody = document.getElementById('crm-contacts-tbody');
    if (!tbody) return;
    if (contacts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-secondary);">Zatiaľ nemáte žiadnych klientov.</td></tr>';
        return;
    }
    let html = '';
    contacts.forEach(c => {
        let avatar = c.avatar_path ? c.avatar_path : 'assets/img/default-avatar.png';
        let verifiedBadge = c.card_verified ? '<span class="material-symbols-outlined" style="color:#3498db;font-size:16px;" title="Overená karta">verified</span>' : '';
        let rating = c.avg_rating ? `<span class="vueto-badge yellow"><span class="material-symbols-outlined" style="font-size:14px;">star</span> ${c.avg_rating}</span>` : '<span style="font-size:12px;color:var(--text-secondary);">Nehodnotené</span>';
        let nameLock = is_over_limit ? '<span class="material-symbols-outlined" style="font-size:14px;color:#e74c3c;">lock</span> ' : '';
        let customerId = c.id || c.user_id || '';
        let customerName = c.full_name || '';
        html += `<tr>
            <td>
                <div style="display:flex;align-items:center;gap:15px;">
                    <img src="${avatar}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                    <div class="td-icon-text">
                        <strong>${nameLock}${customerName} ${verifiedBadge}</strong>
                    </div>
                </div>
            </td>
            <td>
                <div class="td-icon-text">
                    <span><span class="material-symbols-outlined" style="font-size:16px;">mail</span> ${c.email}</span>
                    <span><span class="material-symbols-outlined" style="font-size:16px;">call</span> ${c.phone || 'Neuvedené'}</span>
                </div>
            </td>
            <td>
                <div class="td-icon-text">
                    <strong>${c.total_visits} rezervácií</strong>
                    <span>Posledná: ${c.last_visit ? new Date(c.last_visit).toLocaleDateString('sk-SK') : '-'}</span>
                </div>
            </td>
            <td>${rating}</td>
            <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" onclick="openRatingModal('${customerId}','${customerName.replace(/'/g,"&#39;")}')"
                        class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;display:inline-flex;align-items:center;gap:4px;" title="Hodnotiť klienta">
                        <span class="material-symbols-outlined" style="font-size:15px;color:var(--primary-color);">star</span> Hodnotiť
                    </button>
                    ${c.source === 'booking' ? `<button type="button" onclick="giftHunter('${customerId}','${customerName.replace(/'/g,"&#39;")}')"
                        class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;display:inline-flex;align-items:center;gap:4px;" title="Darovať Kreslo Hunter za polovičnú cenu">
                        <span class="material-symbols-outlined" style="font-size:15px;color:#b08042;">bolt</span> Darovať Huntera
                    </button>` : ''}
                </div>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

// --- Gift Kreslo Hunter ---
function giftHunter(customerId, customerName) {
    document.getElementById('gift-hunter-customer-id').value = customerId;
    document.getElementById('gift-hunter-modal-name').textContent = 'Zákazník: ' + customerName;
    document.getElementById('gift-hunter-modal').style.display = 'flex';
}

function closeGiftHunterModal() {
    document.getElementById('gift-hunter-modal').style.display = 'none';
}

async function confirmGiftHunter() {
    const customerId = document.getElementById('gift-hunter-customer-id').value;
    if (!customerId) return;
    const btn = document.getElementById('btn-confirm-gift-hunter');
    const origText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Spracúvam...';
    try {
        const fd = new FormData();
        fd.append('action', 'gift_hunter');
        fd.append('customer_id', customerId);
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAppToast(data.message || 'Kreslo Hunter bol darovaný!', 'success');
            closeGiftHunterModal();
        } else if (data.need_topup) {
            showAppToast(data.message || 'Nedostatočný zostatok v Peňaženke.', 'error');
            setTimeout(() => { window.location.href = 'dashboard-penazanka.php'; }, 1800);
        } else {
            showAppToast(data.message || 'Chyba pri darovaní Huntera.', 'error');
        }
    } catch (e) {
        showAppToast('Chyba spojenia so serverom.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = origText;
    }
}

// --- Rating Feature ---
let selectedRating = 0;

function openRatingModal(customerId, customerName) {
    selectedRating = 0;
    document.getElementById('rating-customer-id').value = customerId;
    document.getElementById('rating-modal-name').textContent = 'Klient: ' + customerName;
    document.getElementById('rating-comment').value = '';
    document.getElementById('rating-value').value = '';
    document.querySelectorAll('.star-btn').forEach(btn => { btn.style.color = '#d1d5db'; });
    document.getElementById('rating-modal').style.display = 'flex';
}

function closeRatingModal() {
    document.getElementById('rating-modal').style.display = 'none';
}

function selectRatingStar(val) {
    selectedRating = val;
    document.getElementById('rating-value').value = val;
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.style.color = parseInt(btn.getAttribute('data-val')) <= val ? '#f59e0b' : '#d1d5db';
    });
}

async function submitCustomerRatingFromModal() {
    const customerId = document.getElementById('rating-customer-id').value;
    const rating = document.getElementById('rating-value').value;
    const comment = document.getElementById('rating-comment').value;
    if (!rating) { showAppToast('Vyberte počet hviezdičiek.', 'error'); return; }
    await submitCustomerRating(customerId, rating, comment);
}

async function submitCustomerRating(customerId, rating, comment) {
    const fd = new FormData();
    fd.append('action', 'rate_customer');
    fd.append('customer_id', customerId);
    fd.append('rating', rating);
    fd.append('comment', comment);
    const categories = {};
    ['dochvilnost','spravanie','komunikacia'].forEach(cat => {
        const v = document.getElementById('cat-' + cat).value;
        if (v) categories[cat] = parseInt(v);
    });
    if (Object.keys(categories).length > 0) fd.append('category_ratings', JSON.stringify(categories));
    try {
        const res = await fetch('api/reviews.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) { showAppToast('Hodnotenie uložené', 'success'); closeRatingModal(); loadCRMData(); }
        else showAppToast(data.error || data.message || 'Chyba pri ukladaní hodnotenia.', 'error');
    } catch(err) { console.error(err); showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

async function openBlockCustomerPrompt() {
    const customerId = document.getElementById('rating-customer-id').value;
    if (!customerId) return;
    if (!(await confirmModal('Naozaj chcete zablokovať tohto zákazníka pre vašu prevádzku? Nebude si u vás môcť online rezervovať.'))) return;
    const reason = prompt('Dôvod blokovania (voliteľné):', '') || '';
    const fd = new FormData();
    fd.append('action', 'block_customer');
    fd.append('customer_id', customerId);
    fd.append('reason', reason);
    fd.append('duration', 'permanent');
    try {
        const res = await fetch('api/customer_trust.php', { method: 'POST', body: fd });
        const data = await res.json();
        showAppToast(data.message || (data.success ? 'Zablokované.' : 'Chyba.'), data.success ? 'success' : 'error');
        if (data.success) closeRatingModal();
    } catch (err) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

async function openIncidentPrompt() {
    const customerId = document.getElementById('rating-customer-id').value;
    if (!customerId) return;
    const description = prompt('Popíšte, čo sa stalo (odošle sa na posúdenie Rezervos support):', '');
    if (!description) return;
    const fd = new FormData();
    fd.append('action', 'report_incident');
    fd.append('customer_id', customerId);
    fd.append('description', description);
    try {
        const res = await fetch('api/customer_trust.php', { method: 'POST', body: fd });
        const data = await res.json();
        showAppToast(data.message || (data.success ? 'Nahlásené.' : 'Chyba.'), data.success ? 'success' : 'error');
    } catch (err) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

// showAppToast je v /assets/js/dashboard-common.js

function openAddContactModal() {
    document.getElementById('mc-name').value = '';
    document.getElementById('mc-email').value = '';
    document.getElementById('mc-phone').value = '';
    document.getElementById('mc-note').value = '';
    document.getElementById('add-contact-modal').style.display = 'flex';
}

function closeAddContactModal() {
    document.getElementById('add-contact-modal').style.display = 'none';
}

async function saveManualContact() {
    const name = document.getElementById('mc-name').value.trim();
    if (!name) { showAppToast('Meno je povinné.', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'add_manual_contact');
    fd.append('full_name', name);
    fd.append('email', document.getElementById('mc-email').value.trim());
    fd.append('phone', document.getElementById('mc-phone').value.trim());
    fd.append('note', document.getElementById('mc-note').value.trim());
    try {
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAppToast('Kontakt bol pridaný.', 'success');
            closeAddContactModal();
            loadCRMData();
        } else {
            showAppToast(data.error || 'Chyba pri ukladaní.', 'error');
        }
    } catch(e) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

let importPreviewRows = [];

function openImportContactsModal() {
    document.getElementById('import-csv-file').value = '';
    document.getElementById('import-step-upload').style.display = 'block';
    document.getElementById('import-step-preview').style.display = 'none';
    document.getElementById('import-send-invites').checked = false;
    importPreviewRows = [];
    document.getElementById('import-contacts-modal').style.display = 'flex';
}

function closeImportContactsModal() {
    document.getElementById('import-contacts-modal').style.display = 'none';
}

async function previewImportContacts() {
    const fileInput = document.getElementById('import-csv-file');
    if (!fileInput.files.length) { showAppToast('Vyberte CSV súbor.', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'preview_import_contacts');
    fd.append('csv_file', fileInput.files[0]);
    try {
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) { showAppToast(data.error || 'Chyba pri načítaní súboru.', 'error'); return; }
        if (!data.rows.length) { showAppToast('Súbor neobsahuje žiadne platné riadky.', 'error'); return; }
        importPreviewRows = data.rows;
        renderImportPreview();
        document.getElementById('import-step-upload').style.display = 'none';
        document.getElementById('import-step-preview').style.display = 'block';
    } catch (e) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

function renderImportPreview() {
    const statusLabel = { new: ['Nový', '#10b981'], duplicate: ['Už existuje', '#9ca3af'], duplicate_in_file: ['Duplicita v súbore', '#f59e0b'] };
    const newCount = importPreviewRows.filter(r => r.status === 'new').length;
    document.getElementById('import-preview-summary').textContent = `Nájdených ${importPreviewRows.length} riadkov, z toho ${newCount} nových. Duplicity sú predvolene odznačené.`;
    document.getElementById('import-preview-tbody').innerHTML = importPreviewRows.map((r, i) => {
        const [label, color] = statusLabel[r.status] || [r.status, '#9ca3af'];
        const checked = r.status === 'new' ? 'checked' : '';
        return `<tr>
            <td><input type="checkbox" class="import-row-check" data-idx="${i}" ${checked}></td>
            <td>${escapeHtmlKontakty(r.full_name)}</td>
            <td>${escapeHtmlKontakty(r.email)}</td>
            <td>${escapeHtmlKontakty(r.phone)}</td>
            <td><span style="color:${color};font-weight:700;font-size:12px;">${label}</span></td>
        </tr>`;
    }).join('');
}

function escapeHtmlKontakty(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

async function commitImportContacts() {
    const selected = [];
    document.querySelectorAll('.import-row-check:checked').forEach(cb => { selected.push(importPreviewRows[parseInt(cb.dataset.idx)]); });
    if (!selected.length) { showAppToast('Nevybrali ste žiadne riadky na import.', 'error'); return; }

    const fd = new FormData();
    fd.append('action', 'commit_import_contacts');
    fd.append('rows', JSON.stringify(selected));
    fd.append('send_invites', document.getElementById('import-send-invites').checked ? '1' : '0');
    try {
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAppToast(data.message || 'Import dokončený.', 'success');
            closeImportContactsModal();
            loadCRMData();
        } else {
            showAppToast(data.error || 'Chyba pri importe.', 'error');
        }
    } catch (e) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

document.addEventListener('DOMContentLoaded', () => { loadCRMData(); });
</script>
</body>
</html>
