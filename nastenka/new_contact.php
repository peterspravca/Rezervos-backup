<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo  = db_connect();
$user = current_user();
$csrf = csrf_token();

$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $apartment = trim($_POST['apartment'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $zip      = trim($_POST['zip'] ?? '');
    $interest = trim($_POST['interest'] ?? 'Iné / Všeobecné');
    $message  = trim($_POST['message'] ?? '');
    $type     = trim($_POST['type'] ?? 'prospect');
    $gender   = trim($_POST['gender'] ?? 'unknown');
    
    // Set default empty values to avoid DB NOT NULL errors if omitted
    $email = $email ?: 'neuvedeny@kontakt.sk';
    $phone = $phone ?: 'Neuvedené';
    
    if (empty($name)) {
        $msg = 'Meno je povinný údaj.';
        $msg_type = 'error';
    } else {
        try {
            // Auto-migrate: add columns if missing
            try { $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER assigned_to"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER phone"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS city VARCHAR(255) NULL AFTER address"); } catch(Exception $e) {}

            $stmt = $pdo->prepare("INSERT INTO leads 
                (name, email, phone, address, apartment, city, zip, interest, message, status, priority, assigned_to, created_by, source, type, gender) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'novy', 'stredna', ?, ?, 'manual', ?, ?)");
            
            $assigned = isset($_POST['assign_to_me']) ? $user['id'] : null;
            
            $stmt->execute([
                $name, $email, $phone, $address, $apartment, $city, $zip, $interest, $message,
                $assigned, $user['id'], $type, $gender
            ]);
            
            $new_id = $pdo->lastInsertId();
            
            // Zápis do logu aktivít kto kontakt pridal
            try {
                $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                    ->execute([$new_id, $user['id'], 'system', "Kontakt bol ručne pridaný kolegom {$user['full_name']}."]);
            } catch(Exception $e) {}
            
            header("Location: contact.php?id=" . $new_id . "&ok=Úspešne pridané");
            exit;
        } catch (PDOException $e) {
            $msg = 'Chyba databázy: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

$page_title = "Pridať nový kontakt";
include __DIR__ . '/partials/header.php';
?>

<div>
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="ti ti-user-plus"></i> Pridať kontakt ručne</div>
                <div class="card-subtitle">Tu si môžete zadať zákazníka, ktorý vás kontaktoval inak ako cez web formulár (napr. telefonicky).</div>
            </div>
        </div>
        
        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> mb-2"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Meno zákazníka *</label>
                    <input type="text" name="name" class="form-control" required placeholder="Napr. Ján Novák">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telefónne číslo</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+421 9XX XXX XXX">
                </div>
                
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" placeholder="jan@novak.sk">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Hlavný záujem</label>
                    <select name="interest" class="form-control">
                        <option value="Bezrámové zasklenie">Bezrámové zasklenie</option>
                        <option value="Rámové zasklenie">Rámové zasklenie</option>
                        <option value="Hliníkové dvere">Hliníkové dvere</option>
                        <option value="Posuvné dvere MB-77HS">Posuvné dvere</option>
                        <option value="Zimná záhrada">Zimná záhrada</option>
                        <option value="Prístrešok">Prístrešok / Pergola</option>
                        <option value="Servis a oprava">Servis a oprava</option>
                        <option value="Iné / Všeobecné" selected>Iné / Všeobecné</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Ulica a číslo domu</label>
                    <input type="text" name="address" class="form-control" placeholder="Napr. Hlavná 15">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Číslo bytu / vchodu</label>
                    <input type="text" name="apartment" class="form-control" placeholder="Napr. byt č. 4">
                </div>

                <div class="form-group">
                    <label class="form-label">Mesto / Lokalita</label>
                    <input type="text" name="city" class="form-control" placeholder="Napr. Bratislava">
                </div>

                <div class="form-group">
                    <label class="form-label">PSČ</label>
                    <input type="text" name="zip" class="form-control" placeholder="Napr. 010 01">
                </div>

                <div class="form-group">
                    <label class="form-label">Typ kontaktu</label>
                    <select name="type" class="form-control">
                        <option value="prospect" selected>Záujemca (Lead)</option>
                        <option value="customer">Zákazník (Stály)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Pohlavie (pre marketing)</label>
                    <select name="gender" class="form-control" id="genderSelect">
                        <option value="unknown">Neuvedené</option>
                        <option value="male">Muž (Pán)</option>
                        <option value="female">Žena (Pani)</option>
                        <option value="other">Firma / Iné</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Poznámka zo stretnutia alebo telefonátu (bude uložená ako správa od klienta)</label>
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
                    </div>
                    <textarea name="message" id="message-area" class="form-control" style="min-height: 300px; font-size: 1.1rem; padding: 1.25rem; border: none; border-radius: 0; background: rgba(0,0,0,0.2); width: 100%; color: white;" placeholder="Zákazník volal, že potrebuje vymeniť..."></textarea>
                </div>
            </div>
            
            <div class="form-group" style="padding: 1rem; background: var(--bg-hover); border-radius: var(--radius-sm); display:flex; gap:1rem; align-items:center;">
                <input type="checkbox" name="assign_to_me" id="assign_to_me" style="width:20px;height:20px; cursor:pointer;">
                <label for="assign_to_me" style="cursor:pointer;font-weight:600;">Priradiť tento kontakt rovno mne (<?= htmlspecialchars($user['full_name']) ?>)</label>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Vytvoriť kontakt</button>
                <a href="index.php?status=all" class="btn btn-secondary"><i class="ti ti-x"></i> Zrušiť</a>
            </div>
        </form>
    </div>
</div>

<style>
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

<script>
document.querySelector('input[name="name"]').addEventListener('input', function(e) {
    const name = e.target.value.trim();
    const genderSelect = document.getElementById('genderSelect');
    if (!name) return;
    
    const lowerName = name.toLowerCase();
    
    // Check for companies first
    const companyTerms = ['s.r.o', 'sro', 'a.s.', ' as', 'spol.', 'o.z.', ' oz', 'n.o.', 'n.f.', 'v.o.s', 'vos', 'k.s.', ' s.p.', ' sp ', 'obec', 'mesto', 'zdruze', 'nadac'];
    const isCompany = companyTerms.some(term => lowerName.includes(term));
    
    if (isCompany) {
        genderSelect.value = 'other'; // Firma
        return;
    }
    // Slovak gender detection
    const parts = lowerName.split(' ');
    const lastPart = parts[parts.length - 1];
    
    // Surnames ending in -ová, -á (prevalent for females)
    if (lastPart.endsWith('ová') || lastPart.endsWith('á')) {
        genderSelect.value = 'female';
    } else if (parts.length > 0) {
        // If it's a common female first name ending in -a
        const firstPart = parts[0];
        if (firstPart.endsWith('a') && !['nesta','luca','toma', 'jan', 'benjamin', 'kristian', 'adrian'].some(n => firstPart === n)) { 
             genderSelect.value = 'female';
        } else {
            genderSelect.value = 'male';
        }
    }
});

async function runAI(action, extraParams = {}) {
    const loading = document.getElementById('aiLoading');
    const textArea = document.getElementById('message-area');
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
<style>
    /* Address Autocomplete Styling */
    .autocomplete-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #111827;
        border: 1px solid var(--border);
        border-radius: 8px;
        z-index: 2000;
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        display: none;
        margin-top: 4px;
    }
    .autocomplete-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 0.9rem;
        transition: background 0.2s;
    }
    .autocomplete-item:hover { background: rgba(255,255,255,0.05); color: var(--accent); }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item .city { font-size: 0.75rem; color: var(--text-muted); display: block; }
</style>

<script>
    async function initAddressAutocomplete(inputSelector, citySelector, zipSelector) {
        const input = document.querySelector(inputSelector);
        if (!input) return;

        const container = document.createElement('div');
        container.className = 'autocomplete-results';
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(container);

        let debounceTimer;
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const query = input.value.trim();
            if (query.length < 3) { container.style.display = 'none'; return; }

            debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5&lat=48.66&lon=19.69&bbox=16.8,47.7,22.6,49.6`);
                    const data = await response.json();
                    if (data.features.length > 0) {
                        container.innerHTML = '';
                        data.features.forEach(f => {
                            const p = f.properties;
                            const street = p.street || p.name || '';
                            const housenumber = p.housenumber || '';
                            const city = p.city || p.town || '';
                            const postcode = p.postcode || '';
                            const fullAddress = `${street} ${housenumber}`.trim();
                            if (!fullAddress) return;
                            const item = document.createElement('div');
                            item.className = 'autocomplete-item';
                            item.innerHTML = `<strong>${fullAddress}</strong><span class="city">${postcode} ${city}</span>`;
                            item.onclick = () => {
                                input.value = fullAddress;
                                if (citySelector) {
                                    const cityEl = document.querySelector(citySelector);
                                    if (cityEl) cityEl.value = city;
                                }
                                if (zipSelector) {
                                    const zipEl = document.querySelector(zipSelector);
                                    if (zipEl) zipEl.value = postcode;
                                }
                                container.style.display = 'none';
                            };
                            container.appendChild(item);
                        });
                        container.style.display = 'block';
                    } else { container.style.display = 'none'; }
                } catch (e) { console.error('Autocomplete error:', e); }
            }, 300);
        });
        document.addEventListener('click', (e) => { if (e.target !== input) container.style.display = 'none'; });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initAddressAutocomplete('input[name="address"]', 'input[name="city"]', 'input[name="zip"]');
    });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
