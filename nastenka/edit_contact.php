<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo  = db_connect();
$user = current_user();
$csrf = csrf_token();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$lead = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
$lead->execute([$id]);
$lead = $lead->fetch();

if (!$lead) { header('Location: index.php'); exit; }

$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $apartment = trim($_POST['apartment'] ?? '');
    $company   = trim($_POST['company'] ?? '');
    $ico       = trim($_POST['ico'] ?? '');
    $tax_id    = trim($_POST['tax_id'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $zip       = trim($_POST['zip'] ?? '');
    $interest  = trim($_POST['interest'] ?? '');
    $type      = $_POST['type'] ?? 'prospect';
    $gender    = $_POST['gender'] ?? 'unknown';
    
    if (empty($name)) {
        $msg = 'Meno je povinný údaj.';
        $msg_type = 'error';
    } else {
        try {
            $pdo->prepare("UPDATE leads SET name=?, email=?, phone=?, address=?, apartment=?, company=?, ico=?, tax_id=?, city=?, zip=?, interest=?, type=?, gender=?, updated_at=NOW() WHERE id=?")
                ->execute([$name, $email, $phone, $address, $apartment, $company, $ico, $tax_id, $city, $zip, $interest, $type, $gender, $id]);
            
            $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content) VALUES (?,?,?,?)")
                ->execute([$id, $user['id'], 'system', "Základné údaje klienta (meno, kontakt, adresa) boli upravené na novej stránke úpravy."]);

            header("Location: contact.php?id=$id&ok=Údaje boli úspešne upravené");
            exit;
        } catch (PDOException $e) {
            $msg = 'Chyba databázy: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

$page_title = "Upraviť kontakt: " . htmlspecialchars($lead['name']);
include __DIR__ . '/partials/header.php';
?>

<div class="mb-2">
    <a href="contact.php?id=<?= $id ?>" class="btn btn-secondary btn-sm"><i class="ti ti-arrow-left"></i> Späť na detail kontaktu</a>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title"><i class="ti ti-edit"></i> Upraviť údaje klienta</div>
            <div class="card-subtitle">Tu môžete zmeniť základné identifikačné a kontaktné údaje zákazníka.</div>
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
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($lead['name']) ?>" required style="font-size: 1.1rem; font-weight: 700;">
            </div>
            
            <div class="form-group">
                <label class="form-label">Telefónne číslo</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($lead['phone']) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($lead['email']) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Hlavný záujem</label>
                <select name="interest" class="form-control">
                    <?php 
                    $interests = ["Bezrámové zasklenie", "Rámové zasklenie", "Hliníkové dvere", "Posuvné dvere", "Zimná záhrada", "Prístrešok / Pergola", "Servis a oprava", "Iné / Všeobecné"];
                    foreach($interests as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($lead['interest'] == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Názov firmy</label>
                <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($lead['company'] ?? '') ?>" placeholder="Napr. CONTRACTOR GROUPS s.r.o.">
            </div>

            <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div>
                    <label class="form-label">IČO</label>
                    <input type="text" name="ico" class="form-control" value="<?= htmlspecialchars($lead['ico'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">DIČ / IČ DPH</label>
                    <input type="text" name="tax_id" class="form-control" value="<?= htmlspecialchars($lead['tax_id'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ulica a číslo domu</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($lead['address'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Číslo bytu / vchodu</label>
                <input type="text" name="apartment" class="form-control" value="<?= htmlspecialchars($lead['apartment'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Mesto / Lokalita</label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($lead['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">PSČ</label>
                <input type="text" name="zip" class="form-control" value="<?= htmlspecialchars($lead['zip'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Typ kontaktu</label>
                <select name="type" class="form-control">
                    <option value="prospect" <?= $lead['type']==='prospect'?'selected':'' ?>>Záujemca (Lead)</option>
                    <option value="customer" <?= $lead['type']==='customer'?'selected':'' ?>>Zákazník (Stály)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Pohlavie (pre marketing)</label>
                <select name="gender" class="form-control">
                    <option value="unknown" <?= ($lead['gender']??'unknown')==='unknown'?'selected':'' ?>>Neuvedené</option>
                    <option value="male"    <?= ($lead['gender']??'')==='male'?'selected':''    ?>>Muž (Pán)</option>
                    <option value="female"  <?= ($lead['gender']??'')==='female'?'selected':''  ?>>Žena (Pani)</option>
                    <option value="other"   <?= ($lead['gender']??'')==='other'?'selected':''   ?>>Firma / Iné</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
            <button type="submit" class="btn btn-primary" style="padding-left: 2rem; padding-right: 2rem;"><i class="ti ti-device-floppy"></i> Uložiť zmeny</button>
            <a href="contact.php?id=<?= $id ?>" class="btn btn-secondary"><i class="ti ti-x"></i> Zrušiť</a>
        </div>
    </form>
</div>

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
