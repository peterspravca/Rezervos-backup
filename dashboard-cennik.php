<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('settings');
$pageTitle = 'Cenník - ' . BRAND_NAME;
$currentPage = 'cennik';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Cenník a Služby'; $headerIcon = 'payments'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <?php include 'components/services.php'; ?>
  </div>
</div>

<!-- Category Modal -->
<div id="category-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px;max-width:480px;width:90%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.3);position:relative;">
    <h3 id="category-modal-title" style="margin-top:0;font-size:19px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">category</span> <span>Pridať kategóriu</span></h3>
    <input type="hidden" id="cat-id">
    <div class="form-group">
      <label>Názov kategórie</label>
      <select id="cat-name-select" onchange="onCategoryModalSelectChange(this.value)" style="margin-bottom:10px;">
        <option value="">-- Vyberte kategóriu --</option>
        <option>Vlasy</option><option>Holičstvo a Barber</option><option>Nechty</option>
        <option>Starostlivosť o pleť</option><option>Obočie a riasy</option><option>Masáž</option>
        <option>Make-up</option><option>Wellness a kúpele</option><option>Vrkoče a dredy</option>
        <option>Tetovanie</option><option>Lekárska estetika</option><option>Depilácia a epilácia</option>
        <option>Domáce služby</option><option>Piercing</option><option>Služby pre miláčikov</option>
        <option>Zubné a ortodontické</option><option>Zdravie a kondícia</option><option>Profesionálne služby</option>
        <option>Solárium a opaľovanie</option><option>Joga a Pilates</option><option>Fyzioterapia</option>
        <option>Osobní tréneri</option><option>Výživové poradenstvo</option><option>Svadobné služby</option>
        <option>Alternatívna medicína</option><option>Psychológia a Terapia</option><option>Iné</option>
      </select>
      <div id="cat-name-custom-wrapper" style="display:none;">
        <input type="text" id="cat-name-custom" placeholder="Zadajte vlastný názov kategórie...">
      </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
      <button type="button" onclick="document.getElementById('category-modal').style.display='none'" class="btn-secondary" style="padding:10px 22px;border-radius:10px;">Zrušiť</button>
      <button type="button" id="btn-save-category" onclick="saveCategory()" class="btn-primary" style="padding:10px 22px;border-radius:10px;">Uložiť</button>
    </div>
  </div>
</div>

<!-- Service Modal -->
<div id="service-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;justify-content:center;align-items:flex-start;overflow-y:auto;padding:20px;box-sizing:border-box;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px;max-width:560px;width:100%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.3);position:relative;margin:auto;">
    <button type="button" onclick="document.getElementById('service-modal').style.display='none'" style="position:absolute;top:16px;right:16px;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:20px;display:flex;align-items:center;"><span class="material-symbols-outlined">close</span></button>
    <h3 id="service-modal-title" style="margin:0 0 16px 0;font-size:19px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">add_circle</span> <span>Pridať službu do cenníka</span></h3>
    <input type="hidden" id="srv-id">
    <div class="form-group">
      <label>Kategória <button type="button" onclick="document.getElementById('category-modal').style.display='flex'" class="btn-secondary" style="padding:0 12px;border-radius:8px;white-space:nowrap;font-size:12.5px;margin-left:6px;" title="Vytvoriť novú kategóriu">+ Nová</button></label>
      <select id="srv-category" onchange="onServiceCategoryChange(this.value)"></select>
    </div>
    <div class="form-group" style="position:relative;">
      <label>Názov služby *</label>
      <input type="text" id="srv-name" placeholder="Napr. Pánsky strih, Gél lak..." oninput="onServiceNameInput(this.value)" autocomplete="off">
      <div id="srv-name-suggestions" style="display:none;flex-wrap:wrap;gap:6px;margin-top:8px;padding:10px;background:var(--input-bg);border:1px solid var(--border-color);border-radius:10px;"></div>
    </div>
    <div class="form-group"><label>Popis (voliteľné)</label><textarea id="srv-desc" rows="2" placeholder="Krátky popis služby..."></textarea></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div class="form-group"><label>Cena (€) *</label><input type="number" id="srv-price" value="15.00" min="0" step="0.50"></div>
      <div class="form-group"><label>Trvanie (min) *</label><input type="number" id="srv-duration" value="30" min="5" step="5"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div class="form-group"><label>Čas na prípravu PRED službou (min)</label><input type="number" id="srv-buffer-before" value="0" min="0" step="5" placeholder="0"></div>
      <div class="form-group"><label>Čas na očistenie PO službe (min)</label><input type="number" id="srv-buffer-after" value="0" min="0" step="5" placeholder="0"></div>
    </div>
    <p style="font-size:11.5px;color:var(--text-secondary);margin:-8px 0 14px 0;">Voliteľné — klient vidí len trvanie služby, tento čas sa naviac zablokuje v kalendári (napr. očistenie soláriu, príprava miestnosti).</p>
    <div class="form-group">
      <label>Kapacita (max. počet klientov na ten istý termín)</label>
      <input type="number" id="srv-capacity" value="1" min="1" step="1">
      <p style="font-size:11.5px;color:var(--text-secondary);margin:4px 0 0 0;">Nechajte na 1 pri bežnej individuálnej službe. Vyššie číslo použite pre skupinové služby (napr. kurz jogy pre 10 ľudí) — viac klientov si tak môže rezervovať rovnaký termín naraz.</p>
    </div>
    <div class="form-group">
      <label>Vykonáva pracovník <span style="font-weight:400;color:var(--text-secondary);font-size:11.5px;">(zaškrtnite všetkých, ktorí túto službu robia)</span></label>
      <div id="srv-employee-list" style="display:flex;flex-direction:column;gap:6px;max-height:170px;overflow-y:auto;border:1px solid var(--border-color);border-radius:10px;padding:10px;background:var(--input-bg);"></div>
      <button type="button" onclick="toggleQuickColleagueBox(true)" class="btn-secondary" style="margin-top:8px;padding:7px 14px;font-size:12.5px;">
        <span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">person_add</span> Pridať nového kolegu
      </button>
    </div>
    <div id="srv-quick-colleague-box" style="display:none;background:var(--bg-color);border:1px solid var(--border-color);border-radius:12px;padding:16px;margin-bottom:14px;">
      <p style="margin:0 0 12px 0;font-size:13px;font-weight:700;color:var(--primary-color);">Rýchle pridanie nového kolegu</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="form-group" style="margin:0;"><label style="font-size:12px;">Meno a priezvisko *</label><input type="text" id="quick-emp-name" placeholder="Ján Novák"></div>
        <div class="form-group" style="margin:0;"><label style="font-size:12px;">Pozícia</label><input type="text" id="quick-emp-title" placeholder="Kaderník"></div>
        <div class="form-group" style="margin:0;"><label style="font-size:12px;">E-mail (login) *</label><input type="email" id="quick-emp-email" placeholder="jan@salon.sk"></div>
        <div class="form-group" style="margin:0;"><label style="font-size:12px;">Heslo</label><input type="text" id="quick-emp-password" placeholder="Vygenerované"></div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;margin-top:10px;">
        <div id="quick-emp-avatar-preview" style="width:48px;height:48px;border-radius:50%;background:var(--input-bg);border:2px solid var(--border-color);display:flex;align-items:center;justify-content:center;overflow:hidden;background-size:cover;background-position:center;">
          <span id="quick-emp-avatar-letter" style="font-size:20px;font-weight:700;color:var(--primary-color);">?</span>
        </div>
        <label style="cursor:pointer;font-size:13px;color:var(--primary-color);font-weight:600;"><input type="file" id="quick-emp-avatar-file" accept="image/*" style="display:none;" onchange="previewQuickEmployeeAvatar(this)"> Nahrať foto</label>
        <button type="button" onclick="saveQuickColleague()" class="btn-primary" style="padding:7px 16px;font-size:13px;margin-left:auto;">Uložiť kolegu</button>
      </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
      <button type="button" onclick="document.getElementById('service-modal').style.display='none'" class="btn-secondary" style="padding:10px 18px;border-radius:8px;">Zrušiť</button>
      <button type="button" id="btn-save-service-add-another" onclick="saveService(true)" class="btn-secondary" style="padding:10px 18px;border-radius:8px;display:none;">+ Ďalšia služba</button>
      <button type="button" id="btn-save-service" onclick="saveService(false)" class="btn-primary" style="padding:10px 20px;border-radius:8px;font-weight:700;">Uložiť službu</button>
    </div>
  </div>
</div>

<!-- Assign Employee Modal -->
<div id="assign-emp-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:26px;max-width:420px;width:90%;border:1px solid var(--border-color);">
    <h3 style="margin:0 0 16px 0;font-size:18px;font-weight:700;">Priradiť zamestnanca k službe</h3>
    <input type="hidden" id="assign-srv-id">
    <div class="form-group"><label>Zamestnanec</label><select id="assign-emp-id"></select></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div class="form-group"><label>Cena (€)</label><input type="number" id="assign-price" value="15.00" min="0" step="0.50"></div>
      <div class="form-group"><label>Trvanie (min)</label><input type="number" id="assign-duration" value="30" min="5" step="5"></div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button onclick="document.getElementById('assign-emp-modal').style.display='none'" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
      <button onclick="saveEmployeeService()" class="btn-primary" style="padding:10px 20px;">Uložiť</button>
    </div>
  </div>
</div>

<!-- Confirm Modal container (dynamically created by showConfirmModal) -->
<div class="app-toast-container" id="app-toast-container"></div>
<script>
let g_categories = [], g_services = [], g_emp_services = [], g_team = [];

// escapeHtml + showAppToast sú v /assets/js/dashboard-common.js
function escapeJs(str) { if (!str) return ''; return String(str).replace(/'/g,"\\'"); }

function showConfirmModal(title, message, onConfirm, confirmText = 'Zmazať / Potvrdiť', cancelText = 'Zrušiť') {
    let modal = document.getElementById('global-custom-confirm-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'global-custom-confirm-modal';
        modal.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.78);backdrop-filter:blur(4px);z-index:9999;justify-content:center;align-items:center;';
        modal.innerHTML = `<div style="background:var(--card-bg);padding:26px 28px;border-radius:16px;max-width:420px;width:90%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.4);text-align:center;">
            <div style="width:50px;height:50px;border-radius:50%;background:rgba(239,68,68,0.12);color:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 14px auto;">
                <span class="material-symbols-outlined" style="font-size:26px;">help</span>
            </div>
            <h3 id="gcc-title" style="margin:0 0 8px 0;font-size:17.5px;font-weight:800;color:var(--text-primary);">Potvrdenie akcie</h3>
            <p id="gcc-msg" style="margin:0 0 22px 0;font-size:13.5px;color:var(--text-secondary);line-height:1.5;">Naozaj chcete vykonať túto akciu?</p>
            <div style="display:flex;justify-content:center;gap:10px;">
                <button type="button" id="gcc-cancel" class="btn-secondary" style="padding:9px 18px;font-size:13px;border-radius:9px;">Zrušiť</button>
                <button type="button" id="gcc-ok" class="btn-primary" style="padding:9px 20px;font-size:13px;font-weight:700;border-radius:9px;background:#ef4444;border-color:#ef4444;color:#fff;">Potvrdiť</button>
            </div>
        </div>`;
        document.body.appendChild(modal);
    }
    document.getElementById('gcc-title').innerText = title;
    document.getElementById('gcc-msg').innerText = message;
    document.getElementById('gcc-ok').innerText = confirmText;
    document.getElementById('gcc-cancel').innerText = cancelText;
    const okBtn = document.getElementById('gcc-ok'), cancelBtn = document.getElementById('gcc-cancel');
    okBtn.onclick = function() { modal.style.display = 'none'; if (typeof onConfirm === 'function') onConfirm(); };
    cancelBtn.onclick = function() { modal.style.display = 'none'; };
    modal.style.display = 'flex';
}

window.alert = function(msg) { if (typeof showAppToast === 'function') showAppToast(String(msg), 'info'); else console.warn('alert:', msg); };

async function loadServices() {
    try {
        let fd = new FormData(); fd.append('action', 'get_services');
        let r = await fetch('api/services.php', { method: 'POST', body: fd });
        let res = await r.json();
        if (res.success) {
            g_categories = window.g_categories = res.categories || [];
            g_services = window.g_services = res.services || [];
            g_emp_services = window.g_emp_services = res.employee_services || [];
            renderServices();
        }
    } catch(e) { console.error(e); }
}

function renderServices() {
    let container = document.getElementById('services-container');
    if (!container) return;
    let html = '';
    if (g_categories.length === 0) {
        container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:40px;">Zatiaľ nemáte žiadne kategórie. Začnite pridaním kategórie (napr. Pánske strihy).</p>';
        return;
    }
    g_categories.forEach(cat => {
        html += `<div style="background:var(--input-bg);border-radius:12px;margin-bottom:20px;overflow:hidden;border:1px solid var(--border-color);">
            <div style="background:rgba(99,102,241,0.05);padding:15px 20px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:16px;">${escapeHtml(cat.name)}</h3>
                <div style="display:flex;gap:6px;">
                    <button onclick="editCategory(${cat.id},'${escapeJs(cat.name)}')" class="btn-action" style="color:var(--text-secondary);" title="Premenovať kategóriu"><span class="material-symbols-outlined">edit</span></button>
                    <button onclick="deleteCategory(${cat.id})" class="btn-action" style="color:#e74c3c;" title="Zmazať kategóriu"><span class="material-symbols-outlined">delete</span></button>
                </div>
            </div>
            <div style="padding:10px 20px;">`;
        let srvs = g_services.filter(s => s.category_id == cat.id);
        if (srvs.length === 0) {
            html += '<p style="font-size:13px;color:var(--text-secondary);margin:10px 0;">V tejto kategórii nie sú žiadne služby.</p>';
        } else {
            srvs.forEach(s => {
                html += `<div style="border:1px solid var(--border-color);border-radius:12px;margin-bottom:15px;padding:16px;background:var(--card-bg);">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                        <div>
                            <strong style="font-size:15px;color:var(--text-primary);">${escapeHtml(s.name)}</strong>
                            <p style="margin:3px 0 0 0;font-size:13px;color:var(--text-secondary);">${escapeHtml(s.description || '')}</p>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <button onclick="editService(${s.id})" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;display:inline-flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:15px;">edit</span> Upraviť</button>
                            <button onclick="openAssignModal(${s.id})" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;display:inline-flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:15px;">person_add</span> Priradiť kolegu</button>
                            <button onclick="deleteService(${s.id})" class="btn-secondary" style="padding:6px 8px;font-size:12px;border-radius:8px;color:#e74c3c;"><span class="material-symbols-outlined" style="font-size:16px;">delete</span></button>
                        </div>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;align-items:center;">`;
                let es = g_emp_services.filter(x => x.service_id == s.id);
                if (es.length === 0) {
                    html += '<span style="font-size:12px;color:#f39c12;background:rgba(243,156,18,0.1);padding:4px 10px;border-radius:8px;display:inline-flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:14px;">warning</span> Zatiaľ nevykonáva žiadny zamestnanec</span>';
                } else {
                    es.forEach(x => {
                        let fullName = (x.employee_name || '').trim();
                        let firstName = fullName.split(' ')[0] || fullName;
                        html += `<div style="display:inline-flex;align-items:center;gap:8px;background:var(--bg-color);border:1px solid var(--border-color);padding:6px 12px;border-radius:10px;font-size:13px;">
                            ${x.employee_avatar ? `<img src="${x.employee_avatar}" style="width:24px;height:24px;border-radius:50%;object-fit:cover;" alt="${escapeHtml(firstName)}">` : `<div style="width:24px;height:24px;border-radius:50%;background:var(--primary-color);color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">${firstName.charAt(0).toUpperCase()}</div>`}
                            <span style="color:var(--text-secondary);font-size:12.5px;">Postará sa o vás: <strong style="color:var(--text-primary);font-weight:600;">${escapeHtml(firstName)}</strong></span>
                            <span style="color:var(--primary-color);font-weight:700;margin-left:4px;">${parseFloat(x.price).toFixed(2)} €</span>
                            <span style="color:var(--text-secondary);font-size:11.5px;display:inline-flex;align-items:center;gap:2px;"><span class="material-symbols-outlined" style="font-size:13px;">schedule</span> ${x.duration_minutes} min</span>
                            <span class="material-symbols-outlined" style="font-size:16px;color:#e74c3c;cursor:pointer;margin-left:4px;" onclick="removeEmployeeService(${s.id},${x.employee_id})">close</span>
                        </div>`;
                    });
                }
                html += `</div></div>`;
            });
        }
        html += `</div></div>`;
    });
    container.innerHTML = html;
}

function openCategoryModal() {
    let catIdInput = document.getElementById('cat-id'); if (catIdInput) catIdInput.value = '';
    const titleEl = document.getElementById('category-modal-title'); if (titleEl) titleEl.innerHTML = '<span class="material-symbols-outlined" style="color:var(--primary-color);">category</span> <span>Pridať kategóriu</span>';
    const btnSave = document.getElementById('btn-save-category'); if (btnSave) btnSave.innerText = 'Uložiť';
    let sel = document.getElementById('cat-name-select'); if (sel) sel.value = '';
    let custom = document.getElementById('cat-name-custom'); if (custom) custom.value = '';
    let wrap = document.getElementById('cat-name-custom-wrapper'); if (wrap) wrap.style.display = 'none';
    document.getElementById('category-modal').style.display = 'flex';
}

function editCategory(id, currentName) {
    let catIdInput = document.getElementById('cat-id'); if (catIdInput) catIdInput.value = id;
    const titleEl = document.getElementById('category-modal-title'); if (titleEl) titleEl.innerHTML = '<span class="material-symbols-outlined" style="color:var(--primary-color);">edit</span> <span>Upraviť kategóriu</span>';
    const btnSave = document.getElementById('btn-save-category'); if (btnSave) btnSave.innerText = 'Uložiť zmeny';
    let sel = document.getElementById('cat-name-select'), custom = document.getElementById('cat-name-custom'), wrap = document.getElementById('cat-name-custom-wrapper');
    let found = false;
    if (sel) { for (let i = 0; i < sel.options.length; i++) { if (sel.options[i].value === currentName) { sel.selectedIndex = i; found = true; break; } } }
    if (found) { if (wrap) wrap.style.display = 'none'; if (custom) custom.value = ''; }
    else { if (sel) sel.value = 'Iné'; if (wrap) wrap.style.display = 'block'; if (custom) custom.value = currentName; }
    document.getElementById('category-modal').style.display = 'flex';
}

function onCategoryModalSelectChange(val) { let w = document.getElementById('cat-name-custom-wrapper'); if (w) w.style.display = (val === 'Iné') ? 'block' : 'none'; }

const ALL_SERVICE_PRESETS = [
    { name: 'Detský strih', price: '10.00', duration: 20, desc: 'Trpezlivý a moderný strih pre deti.', tags: ['detsky','strih','vlasy','chlapec'] },
    { name: 'Pánsky strih', price: '15.00', duration: 30, desc: 'Klasický alebo moderný strih nožnicami a strojčekom so stylingom.', tags: ['pansky','strih','muz','fade','vlasy','barber'] },
    { name: 'Úprava brady a holenie', price: '12.00', duration: 25, desc: 'Formovanie kontúr, skrátenie brady a ošetrenie prémiovým olejom.', tags: ['brada','holenie','uprava','barber'] },
    { name: 'Kombinácia: Strih a Brada', price: '25.00', duration: 50, desc: 'Kompletný balík - strih vlasov aj úprava brady s horúcim uterákom.', tags: ['kombi','strih','brada','komplet','barber'] },
    { name: 'Dámsky strih a fúkaná', price: '25.00', duration: 45, desc: 'Umytie vlasov, precízny strih a finálny styling fúkanou.', tags: ['damsky','strih','fukana','zena','vlasy'] },
    { name: 'Farbenie vlasov', price: '45.00', duration: 60, desc: 'Profesionálne jednotné farbenie šetrnými prémiovými farbami.', tags: ['farbenie','farba','tonovanie','vlasy'] },
    { name: 'Balayage / Melír', price: '65.00', duration: 90, desc: 'Moderné presvetlenie a tieňovanie vlasov pre prirodzený prechod.', tags: ['balayage','melir','presvetlenie'] },
    { name: 'Klasická manikúra', price: '18.00', duration: 35, desc: 'Úprava tvaru nechtov, ošetrenie kožičky a výživný olejček.', tags: ['manikura','nechty','ruky'] },
    { name: 'Gélové nechty (Nová modeláž)', price: '35.00', duration: 60, desc: 'Kompletná modeláž a predĺženie nechtov UV gélom.', tags: ['gelove','gel','nechty','modelaz'] },
    { name: 'Pedikúra (Kombinovaná)', price: '28.00', duration: 45, desc: 'Ošetrenie chodidiel, nechtov a zmäkčujúci zábal.', tags: ['pedikura','nohy','chodidla'] },
    { name: 'Úprava a farbenie obočia', price: '15.00', duration: 25, desc: 'Presné vymeranie tvaru pinzetou / voskom a tónovanie obočia.', tags: ['obocie','farbenie','uprava'] },
    { name: 'Laminácia obočia', price: '25.00', duration: 35, desc: 'Dlhodobá fixácia a vyživenie nepoddajných chĺpkov.', tags: ['laminacia','obocie'] },
    { name: 'Lash Lifting (Mihalnice)', price: '30.00', duration: 45, desc: 'Prirodzené vytočenie, optické predĺženie a vyživenie rias.', tags: ['mihalnice','lash','lifting','rias'] },
    { name: 'Hĺbkové čistenie pleti', price: '38.00', duration: 50, desc: 'Ultrazvukové a manuálne čistenie pórov so záverečnou maskou.', tags: ['cistenie','pleti','tvar','kozmetika'] },
    { name: 'Klasická masáž (Chrbát a šija)', price: '25.00', duration: 30, desc: 'Uvoľnenie svalového napätia a stuhnutosti chrbtice.', tags: ['masaz','chrbat','sija','relax'] },
    { name: 'Celotelová relaxačná masáž', price: '45.00', duration: 60, desc: 'Hlboká relaxácia celého tela s aromatickými olejmi.', tags: ['celotelova','masaz','relax'] }
];

function applyServicePreset(name, duration, desc) {
    // Zámerne nevypĺňame cenu — každá prevádzka má iný cenník, nemá zmysel jej vnucovať odporúčanú sumu.
    const ne = document.getElementById('srv-name'), de = document.getElementById('srv-duration'), dse = document.getElementById('srv-desc');
    if (ne) ne.value = name; if (de) de.value = duration; if (dse) dse.value = desc || '';
    const su = document.getElementById('srv-name-suggestions'); if (su) su.style.display = 'none';
}

function onServiceNameInput(query) {
    const container = document.getElementById('srv-name-suggestions'); if (!container) return;
    const q = (query || '').trim().toLowerCase(); if (q.length < 2) { container.style.display = 'none'; container.innerHTML = ''; return; }
    const normQ = q.normalize('NFD').replace(/[̀-ͯ]/g, '');
    const matches = ALL_SERVICE_PRESETS.filter(item => {
        const normName = item.name.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        if (normName.includes(normQ)) return true;
        return item.tags && item.tags.some(tag => tag.includes(normQ) || normQ.includes(tag));
    }).slice(0, 5);
    if (matches.length === 0) { container.style.display = 'none'; container.innerHTML = ''; return; }
    let html = '<div style="width:100%;font-size:11px;font-weight:700;color:var(--primary-color);text-transform:uppercase;margin-bottom:4px;">Navrhované služby (kliknite pre vyplnenie):</div>';
    matches.forEach(m => {
        html += `<button type="button" onclick="applyServicePreset('${escapeJs(m.name)}',${m.duration},'${escapeJs(m.desc)}')" style="background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-primary);padding:5px 10px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s;" onmouseover="this.style.borderColor='var(--primary-color)'" onmouseout="this.style.borderColor='var(--border-color)'">
            <span class="material-symbols-outlined" style="font-size:14px;color:var(--primary-color);">auto_fix_high</span>
            <span>${escapeHtml(m.name)}</span><span style="color:var(--text-secondary);font-size:11px;">(${m.duration} min)</span>
        </button>`;
    });
    container.innerHTML = html; container.style.display = 'flex';
}

function onServiceCategoryChange(catId) {
    // Rýchle predvoľby boli zámerne odstránené — každá prevádzka nech si zadá vlastné služby a ceny.
}

async function saveCategory() {
    let catId = document.getElementById('cat-id') ? document.getElementById('cat-id').value : '';
    let sel = document.getElementById('cat-name-select'), selVal = sel ? sel.value : '';
    let name = selVal === 'Iné' ? document.getElementById('cat-name-custom').value.trim() : selVal;
    if (!name) { showAppToast('Vyberte alebo zadajte názov kategórie.', 'error'); return; }
    let fd = new FormData(); fd.append('action', catId ? 'edit_category' : 'add_category'); if (catId) fd.append('id', catId); fd.append('name', name);
    try {
        let r = await fetch('api/services.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) { document.getElementById('category-modal').style.display = 'none'; await loadServices(); showAppToast(res.message || (catId ? 'Kategória bola upravená!' : 'Kategória bola vytvorená!'), 'success'); if (!catId) { setTimeout(() => openServiceModal(res.id), 300); } }
        else showAppToast(res.message || 'Chyba pri ukladaní kategórie.', 'error');
    } catch(err) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

function deleteCategory(id) {
    showConfirmModal('Zmazať kategóriu?', 'Naozaj chcete zmazať túto kategóriu? Zmažú sa aj všetky služby v nej.', async () => {
        let fd = new FormData(); fd.append('action', 'delete_category'); fd.append('id', id);
        let r = await fetch('api/services.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) { showAppToast('Kategória bola zmazaná.', 'info'); loadServices(); } else showAppToast(res.message, 'error');
    });
}

function populateServiceEmployees(selectedIds = []) {
    let listEl = document.getElementById('srv-employee-list'); if (!listEl) return;
    let sel = (Array.isArray(selectedIds) ? selectedIds : [selectedIds]).map(String);
    if (g_team.length === 0) {
        listEl.innerHTML = '<span style="font-size:12.5px;color:var(--text-secondary);">Zatiaľ nemáte žiadnych zamestnancov — pridajte prvého tlačidlom nižšie.</span>';
        return;
    }
    listEl.innerHTML = g_team.map(e => `
        <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13.5px;cursor:pointer;">
            <input type="checkbox" class="srv-emp-checkbox" value="${e.id}" ${sel.includes(String(e.id)) ? 'checked' : ''} style="accent-color:var(--primary-color);width:16px;height:16px;flex-shrink:0;">
            <span>${escapeHtml(e.name)} ${e.title ? '('+escapeHtml(e.title)+')' : ''}</span>
        </label>
    `).join('');
}

function toggleQuickColleagueBox(show = null) {
    const box = document.getElementById('srv-quick-colleague-box'); if (!box) return;
    const shouldOpen = show !== null ? show : box.style.display !== 'block';
    if (shouldOpen) {
        box.style.display = 'block';
        ['quick-emp-name','quick-emp-title','quick-emp-email'].forEach(id => { let el = document.getElementById(id); if (el) el.value = ''; });
        document.getElementById('quick-emp-password').value = 'Kolega' + Math.floor(1000 + Math.random() * 9000);
        document.getElementById('quick-emp-avatar-file').value = '';
        document.getElementById('quick-emp-avatar-preview').style.backgroundImage = 'none';
        let ltr = document.getElementById('quick-emp-avatar-letter'); if (ltr) { ltr.style.display = 'block'; ltr.innerText = '?'; }
    } else {
        box.style.display = 'none';
    }
}

function previewQuickEmployeeAvatar(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0]; if (file.size > 5 * 1024 * 1024) { showAppToast('Maximálna veľkosť fotky je 5 MB.', 'error'); input.value = ''; return; }
        const reader = new FileReader();
        reader.onload = function(e) { const pb = document.getElementById('quick-emp-avatar-preview'), ltr = document.getElementById('quick-emp-avatar-letter'); if (pb) pb.style.backgroundImage = `url('${e.target.result}')`; if (ltr) ltr.style.display = 'none'; };
        reader.readAsDataURL(file);
    }
}

async function showLimitReachedModal(message) {
    const ok = await confirmModal(
        (message || 'Dosiahli ste maximálny počet členov tímu pre váš balík.') + '\n\nViac miest získate dokúpením doplnku „Ďalší zamestnanec” alebo prechodom na vyšší balík.',
        { okText: 'Prejsť na Môj Balík', cancelText: 'Zavrieť' }
    );
    if (ok) window.location.href = 'dashboard-balik.php';
}

async function saveQuickColleague() {
    const name = document.getElementById('quick-emp-name').value.trim(), title = document.getElementById('quick-emp-title').value.trim(),
          email = document.getElementById('quick-emp-email').value.trim(), password = document.getElementById('quick-emp-password').value.trim(),
          fileInput = document.getElementById('quick-emp-avatar-file');
    if (!name) { showAppToast('Zadajte meno a priezvisko kolegu.', 'error'); return; }
    if (!email) { showAppToast('Zadajte prihlasovací e-mail pre kolegu.', 'error'); return; }
    if (!password || password.length < 6) { showAppToast('Heslo pre kolegu musí mať aspoň 6 znakov.', 'error'); return; }
    const fd = new FormData(); fd.append('action', 'add_employee'); fd.append('name', name); fd.append('title', title || 'Kolega'); fd.append('email', email); fd.append('password', password); fd.append('is_active', '1');
    if (fileInput && fileInput.files[0]) fd.append('avatar', fileInput.files[0]);
    try {
        let r = await fetch('api/team.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) {
            showAppToast('Nový kolega "' + name + '" bol úspešne pridaný!', 'success');
            // Zapamätáme si, koho mal používateľ už zaškrtnutého, obnovíme tím a pridáme k tomu nového kolegu
            let alreadyChecked = Array.from(document.querySelectorAll('.srv-emp-checkbox:checked')).map(cb => cb.value);
            let tfd = new FormData(); tfd.append('action', 'get_team');
            let tr = await fetch('api/team.php', { method: 'POST', body: tfd }); let tres = await tr.json();
            if (tres.success) g_team = tres.team || [];
            alreadyChecked.push(String(res.id));
            populateServiceEmployees(alreadyChecked); toggleQuickColleagueBox(false);
        } else if (res.limit_reached) {
            showLimitReachedModal(res.message);
        } else showAppToast(res.message || 'Chyba pri ukladaní kolegu.', 'error');
    } catch(err) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

async function openServiceModal(preselectedCatId = null) {
    let srvIdInput = document.getElementById('srv-id'); if (srvIdInput) srvIdInput.value = '';
    const titleEl = document.getElementById('service-modal-title'); if (titleEl) titleEl.innerHTML = '<span class="material-symbols-outlined" style="color:var(--primary-color);">add_circle</span> <span>Pridať službu do cenníka</span>';
    const btnSave = document.getElementById('btn-save-service'); if (btnSave) btnSave.innerText = 'Uložiť službu';
    const btnAnother = document.getElementById('btn-save-service-add-another'); if (btnAnother) btnAnother.style.display = 'inline-block';
    let sel = document.getElementById('srv-category'); sel.innerHTML = '';
    if (g_categories.length === 0) { sel.innerHTML = '<option value="0">Všeobecné služby (Vytvorí sa automaticky)</option>'; }
    else { g_categories.forEach(c => { let isSel = (preselectedCatId && String(c.id) === String(preselectedCatId)) ? 'selected' : ''; sel.innerHTML += `<option value="${c.id}" ${isSel}>${c.name}</option>`; }); }
    if (g_team.length === 0) {
        let tfd = new FormData(); tfd.append('action', 'get_team');
        let tr = await fetch('api/team.php', { method: 'POST', body: tfd }); let tres = await tr.json();
        if (tres.success) g_team = tres.team || [];
    }
    populateServiceEmployees([]); toggleQuickColleagueBox(false);
    document.getElementById('srv-name').value = ''; document.getElementById('srv-desc').value = '';
    document.getElementById('srv-price').value = '15.00'; document.getElementById('srv-duration').value = '30';
    document.getElementById('srv-buffer-before').value = '0'; document.getElementById('srv-buffer-after').value = '0';
    document.getElementById('srv-capacity').value = '1';
    onServiceCategoryChange(sel.value); document.getElementById('service-modal').style.display = 'flex';
}

async function editService(id) {
    let s = g_services.find(item => parseInt(item.id) === parseInt(id)); if (!s) return;
    let srvIdInput = document.getElementById('srv-id'); if (srvIdInput) srvIdInput.value = s.id;
    const titleEl = document.getElementById('service-modal-title'); if (titleEl) titleEl.innerHTML = '<span class="material-symbols-outlined" style="color:var(--primary-color);">edit</span> <span>Upraviť službu</span>';
    const btnSave = document.getElementById('btn-save-service'); if (btnSave) btnSave.innerText = 'Uložiť zmeny';
    const btnAnother = document.getElementById('btn-save-service-add-another'); if (btnAnother) btnAnother.style.display = 'none';
    let sel = document.getElementById('srv-category'); sel.innerHTML = '';
    g_categories.forEach(c => { let isSel = (String(c.id) === String(s.category_id)) ? 'selected' : ''; sel.innerHTML += `<option value="${c.id}" ${isSel}>${c.name}</option>`; });
    if (g_team.length === 0) {
        let tfd = new FormData(); tfd.append('action', 'get_team');
        let tr = await fetch('api/team.php', { method: 'POST', body: tfd }); let tres = await tr.json();
        if (tres.success) g_team = tres.team || [];
    }
    let assignedEmps = g_emp_services.filter(es => String(es.service_id) === String(s.id)).map(es => es.employee_id);
    populateServiceEmployees(assignedEmps); toggleQuickColleagueBox(false);
    document.getElementById('srv-name').value = s.name || ''; document.getElementById('srv-desc').value = s.description || '';
    document.getElementById('srv-price').value = parseFloat(s.price || 15).toFixed(2); document.getElementById('srv-duration').value = s.duration_minutes || 30;
    document.getElementById('srv-buffer-before').value = s.buffer_before_minutes || 0; document.getElementById('srv-buffer-after').value = s.buffer_after_minutes || 0;
    document.getElementById('srv-capacity').value = s.capacity || 1;
    document.getElementById('service-modal').style.display = 'flex';
}

async function saveService(keepOpen = false) {
    let srvId = document.getElementById('srv-id') ? document.getElementById('srv-id').value : '';
    let catId = document.getElementById('srv-category').value, name = document.getElementById('srv-name').value.trim(),
        desc = document.getElementById('srv-desc').value.trim(), price = document.getElementById('srv-price').value,
        duration = document.getElementById('srv-duration').value,
        employeeIds = Array.from(document.querySelectorAll('.srv-emp-checkbox:checked')).map(cb => cb.value),
        bufferBefore = document.getElementById('srv-buffer-before').value || 0, bufferAfter = document.getElementById('srv-buffer-after').value || 0,
        capacity = document.getElementById('srv-capacity').value || 1;
    if (!name) { showAppToast('Zadajte prosím názov služby.', 'error'); document.getElementById('srv-name').focus(); return; }
    let fd = new FormData();
    if (srvId) { fd.append('action', 'edit_service'); fd.append('id', srvId); } else { fd.append('action', 'add_service'); }
    fd.append('category_id', catId); fd.append('name', name); fd.append('description', desc); fd.append('price', price); fd.append('duration_minutes', duration);
    fd.append('buffer_before_minutes', bufferBefore); fd.append('buffer_after_minutes', bufferAfter); fd.append('capacity', capacity);
    fd.append('employee_ids_submitted', '1');
    employeeIds.forEach(id => fd.append('employee_ids[]', id));
    try {
        let r = await fetch('api/services.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) {
            showAppToast(res.message || (srvId ? 'Služba bola úspešne upravená!' : 'Služba bola úspešne pridaná do cenníka!'), 'success');
            loadServices();
            if (keepOpen && !srvId) { document.getElementById('srv-name').value = ''; document.getElementById('srv-desc').value = ''; document.getElementById('srv-name').focus(); }
            else document.getElementById('service-modal').style.display = 'none';
        } else showAppToast(res.message || 'Chyba pri ukladaní služby.', 'error');
    } catch(err) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

function deleteService(id) {
    showConfirmModal('Zmazať službu?', 'Naozaj si prajete vymazať túto službu z cenníka?', async () => {
        let fd = new FormData(); fd.append('action', 'delete_service'); fd.append('id', id);
        let r = await fetch('api/services.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) { showAppToast('Služba bola odstránená.', 'info'); loadServices(); } else showAppToast(res.message, 'error');
    });
}

async function openAssignModal(srv_id) {
    if (!g_team || g_team.length === 0) {
        let tfd = new FormData(); tfd.append('action', 'get_team');
        let tr = await fetch('api/team.php', { method: 'POST', body: tfd }); let tres = await tr.json();
        if (tres.success) g_team = tres.team || [];
    }
    if (!g_team || g_team.length === 0) { showAppToast('Najprv si pridajte zamestnanca v sekcii Tím a Zamestnanci.', 'info'); return; }
    document.getElementById('assign-srv-id').value = srv_id;
    let sel = document.getElementById('assign-emp-id'); sel.innerHTML = '';
    g_team.forEach(e => { sel.innerHTML += `<option value="${e.id}">${e.name} ${e.title ? '('+e.title+')' : ''}</option>`; });
    // Predvyplniť cenu a trvanie podľa existujúceho priradenia (ak už niekto službu robí), inak podľa základnej ceny služby
    let existing = g_emp_services.find(es => String(es.service_id) === String(srv_id));
    let srv = g_services.find(item => String(item.id) === String(srv_id));
    document.getElementById('assign-price').value = parseFloat((existing ? existing.price : (srv ? srv.price : 15)) || 15).toFixed(2);
    document.getElementById('assign-duration').value = (existing ? existing.duration_minutes : (srv ? srv.duration_minutes : 30)) || 30;
    document.getElementById('assign-emp-modal').style.display = 'flex';
}

async function saveEmployeeService() {
    let fd = new FormData(); fd.append('action', 'save_employee_service');
    fd.append('service_id', document.getElementById('assign-srv-id').value);
    fd.append('employee_id', document.getElementById('assign-emp-id').value);
    fd.append('price', document.getElementById('assign-price').value);
    fd.append('duration_minutes', document.getElementById('assign-duration').value);
    try {
        let r = await fetch('api/services.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) { document.getElementById('assign-emp-modal').style.display = 'none'; showAppToast('Cena a trvanie pre zamestnanca boli uložené.', 'success'); loadServices(); }
        else showAppToast(res.message, 'error');
    } catch(err) { showAppToast('Chyba komunikácie so serverom.', 'error'); }
}

function removeEmployeeService(srv_id, emp_id) {
    showConfirmModal('Odstrániť priradenie?', 'Naozaj si prajete odstrániť tohto zamestnanca z tejto služby?', async () => {
        let fd = new FormData(); fd.append('action', 'remove_employee_service'); fd.append('service_id', srv_id); fd.append('employee_id', emp_id);
        let r = await fetch('api/services.php', { method: 'POST', body: fd }); let res = await r.json();
        if (res.success) { showAppToast('Priradenie bolo odstránené.', 'info'); loadServices(); } else showAppToast(res.message, 'error');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadServices();
});
</script>
</body>
</html>
