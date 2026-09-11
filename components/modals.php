<?php if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php'; ?>
<button class="fab" onclick="openNewBookingModal()" title="Pridať manuálnu rezerváciu">
    <span class="material-symbols-outlined">add</span>
</button>

<!-- Modal pre manuálnu rezerváciu -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Nová rezervácia</h2>
            <span class="material-symbols-outlined" style="cursor:pointer;" onclick="document.getElementById('bookingModal').classList.remove('active')">close</span>
        </div>
        <form id="manualBookingForm" onsubmit="addManualBooking(event)">
            <div class="form-group">
                <label>Služba</label>
                <select id="mb-service" required>
                    <option value="">Vyberte službu...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Meno zákazníka</label>
                <input type="text" id="mb-name" required placeholder="Napr. Ján Novák">
            </div>
            <div class="form-group">
                <label>Telefón</label>
                <input type="text" id="mb-phone" placeholder="Napr. +421 900 123 456">
            </div>
            <div class="form-group">
                <label>E-mail (voliteľné pre odoslanie potvrdenia)</label>
                <input type="email" id="mb-email" placeholder="Napr. jan@novak.sk">
            </div>
            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Dátum</label>
                    <input type="date" id="mb-date" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Čas (začiatok)</label>
                    <input type="time" id="mb-time" required>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; margin-top:10px;">Uložiť rezerváciu</button>
        </form>
    </div>
</div>

<!-- Modal: Zamestnanec & Majiteľ -->
<div id="employee-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); z-index:1000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:var(--card-bg); padding:28px 32px; border-radius:16px; max-width:480px; width:92%; max-height:90vh; overflow-y:auto; position:relative; box-shadow:0 20px 40px rgba(0,0,0,0.3); border:1px solid var(--border-color);">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:12px;">
            <h3 id="employee-modal-title" style="margin:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color);">person</span>
                <span>Pridať zamestnanca</span>
            </h3>
            <button type="button" onclick="closeEmployeeModal()" style="background:none; border:none; color:var(--text-secondary); cursor:pointer; padding:4px; display:flex; align-items:center; border-radius:8px;">
                <span class="material-symbols-outlined" style="font-size:22px;">close</span>
            </button>
        </div>

        <form id="employee-form" onsubmit="event.preventDefault(); saveEmployee();">
            <input type="hidden" id="emp-id" value="">
            <input type="hidden" id="emp-is-owner" value="0">

            <!-- UPLOAD AVATARA / FOTOGRAFIE -->
            <div style="display:flex; align-items:center; gap:18px; margin-bottom:20px; background:var(--bg-color); padding:14px 18px; border-radius:12px; border:1px solid var(--border-color);">
                <div id="emp-avatar-preview-box" style="width:68px; height:68px; border-radius:50%; background:var(--primary-color); color:white; display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:700; overflow:hidden; flex-shrink:0; border:2px solid var(--border-color); background-size:cover; background-position:center;">
                    <span id="emp-avatar-initial">?</span>
                </div>
                <div style="flex:1;">
                    <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:4px;">Profilová fotografia</label>
                    <p style="margin:0 0 8px 0; font-size:11.5px; color:var(--text-secondary); line-height:1.4;">Zákazník pri rezervácii uvidí tvár človeka, ktorý sa o neho postará.</p>
                    <label class="btn-secondary" style="padding:6px 12px; font-size:12px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:16px;">photo_camera</span>
                        <span>Vybrať fotku</span>
                        <input type="file" id="emp-avatar-file" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewEmployeeAvatar(this)">
                    </label>
                </div>
            </div>

            <!-- MENO -->
            <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block; margin-bottom:5px; font-size:13px; font-weight:600;">Meno a priezvisko <span style="color:#e74c3c;">*</span></label>
                <input type="text" id="emp-name" class="vueto-input" placeholder="Napr. Jozef Novák" required style="width:100%; box-sizing:border-box;">
            </div>

            <!-- POZÍCIA / TITUL -->
            <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block; margin-bottom:5px; font-size:13px; font-weight:600;">Pracovná pozícia / Titul</label>
                <input type="text" id="emp-title" class="vueto-input" placeholder="Napr. Barber, Kaderníčka, Kozmetička" style="width:100%; box-sizing:border-box;">
            </div>

            <!-- TELEFÓN -->
            <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block; margin-bottom:5px; font-size:13px; font-weight:600;">Telefónne číslo</label>
                <input type="tel" id="emp-phone" class="vueto-input" placeholder="Napr. +421 900 123 456" style="width:100%; box-sizing:border-box;">
            </div>

            <!-- PRIHLASOVACIE ÚDAJE ZAMESTNANCA (SKRYTÉ PRE MAJITEĽA) -->
            <div id="emp-credentials-box" style="margin-top:16px; padding:14px 16px; border-radius:12px; background:var(--bg-color); border:1px solid var(--border-color);">
                <div style="font-size:12.5px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); letter-spacing:0.5px; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px; color:var(--primary-color);">key</span>
                    <span>Prihlasovacie údaje zamestnanca</span>
                </div>

                <div class="form-group" style="margin-bottom:12px;">
                    <label style="display:block; margin-bottom:5px; font-size:12.5px;">Prihlasovací e-mail <span style="color:#e74c3c;">*</span></label>
                    <input type="email" id="emp-email" class="vueto-input" placeholder="zamestnanec@email.sk" style="width:100%; box-sizing:border-box; font-size:13px;">
                </div>

                <div class="form-group" style="margin-bottom:6px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label style="margin:0; font-size:12.5px;">Heslo <span id="emp-pwd-req-star" style="color:#e74c3c;">*</span></label>
                        <button type="button" onclick="generateEmployeePassword()" style="background:none; border:none; color:var(--primary-color); font-size:11.5px; font-weight:600; cursor:pointer; padding:0; display:flex; align-items:center; gap:3px;">
                            <span class="material-symbols-outlined" style="font-size:14px;">autorenew</span> Vygenerovať heslo
                        </button>
                    </div>
                    <div style="position:relative;">
                        <input type="text" id="emp-password" class="vueto-input" placeholder="Zadajte alebo vygenerujte heslo" style="width:100%; box-sizing:border-box; font-size:13px; padding-right:36px;">
                        <span class="material-symbols-outlined" onclick="toggleEmpPasswordVisibility()" id="emp-pwd-eye" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:18px; color:var(--text-secondary); cursor:pointer;">visibility</span>
                    </div>
                    <small id="emp-password-hint" style="display:none; color:var(--text-secondary); font-size:11px; margin-top:4px;">Ak nechcete zmeniť heslo, nechajte pole prázdne.</small>
                </div>
            </div>

            <!-- INFO PRE MAJITEĽA -->
            <div id="emp-owner-info-box" style="display:none; margin-top:16px; padding:12px 16px; border-radius:12px; background:rgba(176, 128, 66, 0.08); border:1px solid rgba(176, 128, 66, 0.25); color:var(--text-primary); font-size:13px;">
                <span class="material-symbols-outlined" style="font-size:16px; color:var(--primary-color); vertical-align:middle; margin-right:4px;">verified_user</span>
                <span>Toto je hlavný profil majiteľa prevádzky. Váš prihlasovací e-mail a heslo zostávajú viazané na Váš hlavný účet salónu.</span>
            </div>

            <!-- STAV AKTIVITY -->
            <div class="form-group" id="emp-active-group" style="margin-top:14px; margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px; font-size:13px; font-weight:600;">Stav v online rezerváciách</label>
                <select id="emp-active" class="vueto-input" style="width:100%; box-sizing:border-box;">
                    <option value="1">Aktívny (Zákazníci si môžu rezervovať termín)</option>
                    <option value="0">Dočasne neaktívny (Skrytý pre zákazníkov)</option>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px; border-top:1px solid var(--border-color); padding-top:16px;">
                <button type="button" onclick="closeEmployeeModal()" class="btn-secondary" style="padding:10px 18px; font-size:13.5px; border-radius:10px;">Zrušiť</button>
                <button type="submit" id="btn-save-emp" class="btn-primary" style="padding:10px 22px; font-size:13.5px; font-weight:700; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">save</span>
                    <span>Uložiť údaje</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Potvrdenie zmazania člena tímu -->
<div id="team-delete-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); z-index:1001; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:var(--card-bg); padding:26px 30px; border-radius:16px; max-width:400px; width:90%; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.3); border:1px solid var(--border-color);">
        <div style="width:54px; height:54px; border-radius:50%; background:rgba(231, 76, 60, 0.12); color:#e74c3c; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px;">
            <span class="material-symbols-outlined" style="font-size:28px;">person_remove</span>
        </div>
        <h3 style="margin:0 0 8px 0; font-size:18px;">Odstrániť zamestnanca?</h3>
        <p id="team-delete-msg" style="margin:0 0 20px 0; font-size:13.5px; color:var(--text-secondary); line-height:1.5;">
            Naozaj si prajete odstrániť tohto zamestnanca z Vášho tímu?
        </p>
        <input type="hidden" id="team-delete-id" value="">
        <div style="display:flex; justify-content:center; gap:12px;">
            <button type="button" onclick="closeTeamDeleteModal()" class="btn-secondary" style="padding:9px 18px; font-size:13px; border-radius:10px;">Zrušiť</button>
            <button type="button" onclick="confirmDeleteEmployee()" class="btn-primary" style="padding:9px 20px; font-size:13px; font-weight:700; border-radius:10px; background:#e74c3c !important;">Odstrániť</button>
        </div>
    </div>
</div>

<!-- Modal: Kategória -->
<div id="category-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:var(--card-bg); padding:30px; border-radius:16px; max-width:440px; width:90%; position:relative; box-shadow: var(--shadow-lg); border: 1px solid var(--border-color);">
        <input type="hidden" id="cat-id" value="">
        <h3 id="category-modal-title" style="margin-top:0; font-size: 19px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-outlined" style="color: var(--primary-color);">category</span>
            <span>Pridať kategóriu</span>
        </h3>
        <div class="form-group" style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:8px; font-size: 13.5px; font-weight: 600; color: var(--text-secondary);">Vyberte kategóriu zo zoznamu <span style="color:red">*</span></label>
            <select id="cat-name-select" class="vueto-input" style="width:100%; box-sizing:border-box; height: 44px; font-size: 14px; border-radius: 10px;" onchange="onCategoryModalSelectChange(this.value)">
                <option value="" disabled selected>Vyberte kategóriu...</option>
                <option value="Vlasy">Vlasy (Strihy, farbenie, styling)</option>
                <option value="Holičstvo a Barber">Holičstvo a Barber (Úprava brady, klasické holenie)</option>
                <option value="Nechty">Nechty (Manikúra, pedikúra, gél)</option>
                <option value="Starostlivosť o pleť">Starostlivosť o pleť (Čistenie, peeling, masky)</option>
                <option value="Obočie a riasy">Obočie a riasy (Laminácia, farbenie)</option>
                <option value="Masáž">Masáž (Relaxačná, thajská, športová)</option>
                <option value="Make-up">Make-up (Večerný, svadobný, denný)</option>
                <option value="Wellness a kúpele">Wellness a kúpele (Sauny, vírivky, relax)</option>
                <option value="Vrkoče a dredy">Vrkoče a dredy (Zapletanie, africké vrkoče)</option>
                <option value="Tetovanie">Tetovanie (Tetovanie, permanentný make-up)</option>
                <option value="Lekárska estetika">Lekárska estetika (Botox, výplne, plazma)</option>
                <option value="Depilácia a epilácia">Depilácia a epilácia (Vosk, laser, cukrová pasta)</option>
                <option value="Domáce služby">Domáce služby (Služby priamo u vás doma)</option>
                <option value="Piercing">Piercing (Uši, tvár, telo)</option>
                <option value="Služby pre miláčikov">Služby pre miláčikov (Strihanie, úprava psov)</option>
                <option value="Zubné a ortodontické">Zubné a ortodontické (Bielenie, hygiena, rovnátka)</option>
                <option value="Zdravie a kondícia">Zdravie a kondícia (Tréning, fyzioterapia)</option>
                <option value="Profesionálne služby">Profesionálne služby (Školenia, poradenstvo)</option>
                <option value="Solárium a opaľovanie">Solárium a opaľovanie (Solárium, nástreky)</option>
                <option value="Joga a Pilates">Joga a Pilates (Lekcie, kurzy)</option>
                <option value="Fyzioterapia">Fyzioterapia (Rehabilitácia, naprávanie)</option>
                <option value="Osobní tréneri">Osobní tréneri (Fitness, cvičenie na mieru)</option>
                <option value="Výživové poradenstvo">Výživové poradenstvo (Jedálničky, konzultácie)</option>
                <option value="Svadobné služby">Svadobné služby (Vlasy, vizáž, balíčky)</option>
                <option value="Alternatívna medicína">Alternatívna medicína (Akupunktúra, bankovanie)</option>
                <option value="Psychológia a Terapia">Psychológia a Terapia (Psychológ, logopédia, koučing)</option>
                <option value="Iné">Iné (Zadať vlastný názov)</option>
            </select>
        </div>
        <div class="form-group" id="cat-name-custom-wrapper" style="margin-bottom:15px; display:none;">
            <label style="display:block; margin-bottom:8px; font-size: 13.5px; font-weight: 600; color: var(--text-secondary);">Vlastný názov kategórie <span style="color:red">*</span></label>
            <input type="text" id="cat-name-custom" class="vueto-input" placeholder="Zadajte vlastný názov kategórie..." style="width:100%; box-sizing:border-box; height: 44px; font-size: 14px; border-radius: 10px;">
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:25px; gap: 10px;">
            <button onclick="document.getElementById('category-modal').style.display='none'" class="btn-secondary" style="padding:10px 22px; border-radius: 10px; font-size: 14px;">Zrušiť</button>
            <button id="btn-save-category" onclick="saveCategory()" class="btn-primary" style="padding:10px 24px; border-radius: 10px; font-size: 14px; font-weight: 700;">Uložiť</button>
        </div>
    </div>
</div>

<!-- Modal: Služba a Cenník -->
<div id="service-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:var(--card-bg); padding:28px; border-radius:14px; max-width:520px; width:92%; position:relative; box-shadow: var(--shadow-lg); border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto;">
        <input type="hidden" id="srv-id" value="">
        <button type="button" onclick="document.getElementById('service-modal').style.display='none'" style="position:absolute; top:16px; right:16px; background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size:20px; display:flex; align-items:center;">
            <span class="material-symbols-outlined">close</span>
        </button>

        <h3 id="service-modal-title" style="margin:0 0 16px 0; font-size:19px; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">add_circle</span>
            <span>Pridať službu do cenníka</span>
        </h3>

        <!-- Kategória -->
        <div class="form-group" style="margin-bottom:14px;">
            <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:var(--text-secondary);">Kategória služby <span style="color:red">*</span></label>
            <div style="display:flex; gap:8px;">
                <select id="srv-category" class="vueto-input" style="width:100%; box-sizing:border-box; height:42px; border-radius:8px;" onchange="onServiceCategoryChange(this.value)"></select>
                <button type="button" onclick="document.getElementById('category-modal').style.display='flex'" class="btn-secondary" style="padding:0 12px; border-radius:8px; white-space:nowrap; font-size:12.5px;" title="Vytvoriť novú kategóriu">
                    <span class="material-symbols-outlined" style="font-size:18px;">add</span>
                </button>
            </div>
        </div>

        <!-- Rýchle návrhy služieb (podľa vybratej kategórie) -->
        <div id="srv-presets-container" style="margin-bottom:14px; display:none;">
            <label style="display:block; margin-bottom:6px; font-size:11.5px; font-weight:700; text-transform:uppercase; color:var(--primary-color);">Rýchle návrhy pre túto kategóriu (kliknite pre vyplnenie):</label>
            <div id="srv-presets-chips" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
        </div>

        <!-- Názov služby -->
        <div class="form-group" style="margin-bottom:14px; position:relative;">
            <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:var(--text-secondary);">Názov služby <span style="color:red">*</span></label>
            <input type="text" id="srv-name" class="vueto-input" placeholder="Napr. Detský strih, Pánsky strih, Úprava brady..." style="width:100%; box-sizing:border-box; height:42px; border-radius:8px;" oninput="onServiceNameInput(this.value)">
            <div id="srv-name-suggestions" style="display:none; margin-top:6px; flex-wrap:wrap; gap:6px;"></div>
        </div>

        <!-- Cena a Trvanie -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:14px;">
            <div class="form-group">
                <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:var(--text-secondary);">Cena v € <span style="color:red">*</span></label>
                <div style="position:relative;">
                    <input type="number" step="0.50" id="srv-price" class="vueto-input" placeholder="Napr. 15.00" value="15.00" style="width:100%; box-sizing:border-box; height:42px; border-radius:8px; padding-right:30px;">
                    <span style="position:absolute; right:12px; top:11px; font-weight:700; color:var(--text-secondary);">€</span>
                </div>
            </div>
            <div class="form-group">
                <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:var(--text-secondary);">Trvanie (minúty) <span style="color:red">*</span></label>
                <div style="position:relative;">
                    <input type="number" step="5" id="srv-duration" class="vueto-input" placeholder="Napr. 30" value="30" style="width:100%; box-sizing:border-box; height:42px; border-radius:8px; padding-right:45px;">
                    <span style="position:absolute; right:10px; top:11px; font-size:12px; font-weight:600; color:var(--text-secondary);">min</span>
                </div>
            </div>
        </div>

        <!-- Kto službu poskytuje (Pracovník / Kolega) -->
        <div class="form-group" style="margin-bottom:14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <label style="margin:0; font-size:13px; font-weight:600; color:var(--text-secondary);">Priradiť k pracovníkovi / kolegovi</label>
                <button type="button" onclick="toggleQuickColleagueBox()" style="background:none; border:none; color:var(--primary-color); font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:3px; padding:0;">
                    <span class="material-symbols-outlined" style="font-size:16px;">person_add</span>
                    <span id="quick-colleague-btn-label">+ Pridať nového kolegu</span>
                </button>
            </div>
            <select id="srv-employee" class="vueto-input" style="width:100%; box-sizing:border-box; height:42px; border-radius:8px;" onchange="handleServiceEmployeeChange(this.value)">
                <option value="0">Všetci pracovníci (celý tím / Ja)</option>
            </select>
        </div>

        <!-- RÝCHLE PRIDANIE A NAHRATIE KOLEGU PRIAMO V SLUŽBE -->
        <div id="srv-quick-colleague-box" style="display:none; background:var(--bg-color); border:1.5px solid var(--border-color); border-radius:12px; padding:16px; margin-bottom:16px; position:relative;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <strong style="font-size:13.5px; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="color:var(--primary-color); font-size:18px;">badge</span>
                    Pridať nového kolegu do tímu
                </strong>
                <button type="button" onclick="toggleQuickColleagueBox(false)" style="background:none; border:none; color:var(--text-secondary); cursor:pointer; padding:2px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">close</span>
                </button>
            </div>

            <!-- Foto / Avatar kolegu -->
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                <div id="quick-emp-avatar-preview" style="width:48px; height:48px; border-radius:50%; background:var(--primary-color); color:white; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; overflow:hidden; flex-shrink:0; border:2px solid var(--border-color); background-size:cover; background-position:center;">
                    <span id="quick-emp-avatar-letter">?</span>
                </div>
                <div style="flex:1;">
                    <label class="btn-secondary" style="padding:5px 12px; font-size:11.5px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                        <span class="material-symbols-outlined" style="font-size:15px;">photo_camera</span>
                        <span>Nahrať foto kolegu</span>
                        <input type="file" id="quick-emp-avatar-file" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewQuickEmployeeAvatar(this)">
                    </label>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Odporúčaný štvorec (JPG, PNG)</div>
                </div>
            </div>

            <!-- Meno a Pozícia -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px; color:var(--text-secondary);">Meno a priezvisko <span style="color:red">*</span></label>
                    <input type="text" id="quick-emp-name" class="vueto-input" placeholder="Napr. Peter Kováč" style="width:100%; box-sizing:border-box; height:38px; font-size:13px; border-radius:8px;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px; color:var(--text-secondary);">Pozícia / Špecializácia</label>
                    <input type="text" id="quick-emp-title" class="vueto-input" placeholder="Napr. Barber, Kaderníčka" style="width:100%; box-sizing:border-box; height:38px; font-size:13px; border-radius:8px;">
                </div>
            </div>

            <!-- E-mail a Heslo -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px; color:var(--text-secondary);">Prihlasovací e-mail <span style="color:red">*</span></label>
                    <input type="email" id="quick-emp-email" class="vueto-input" placeholder="kolega@prevadzka.sk" style="width:100%; box-sizing:border-box; height:38px; font-size:13px; border-radius:8px;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px; color:var(--text-secondary);">Heslo (min. 6 znakov) <span style="color:red">*</span></label>
                    <input type="text" id="quick-emp-password" class="vueto-input" placeholder="Heslo pre kolegu" style="width:100%; box-sizing:border-box; height:38px; font-size:13px; border-radius:8px;">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" onclick="toggleQuickColleagueBox(false)" class="btn-secondary" style="padding:6px 14px; font-size:12px; border-radius:8px;">Zrušiť</button>
                <button type="button" onclick="saveQuickColleague()" class="btn-primary" style="padding:6px 16px; font-size:12px; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:5px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">save</span>
                    <span>Uložiť kolegu a priradiť</span>
                </button>
            </div>
        </div>

        <!-- Popis -->
        <div class="form-group" style="margin-bottom:20px;">
            <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:var(--text-secondary);">Popis služby (nepovinné)</label>
            <textarea id="srv-desc" class="vueto-input" style="width:100%; box-sizing:border-box; height:55px; border-radius:8px; font-size:13px;" placeholder="Krátky popis čo služba zahŕňa (umytie, styling, masáž hlavy)..."></textarea>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:20px; flex-wrap:wrap;">
            <button type="button" onclick="document.getElementById('service-modal').style.display='none'" class="btn-secondary" style="padding:10px 18px; border-radius:8px;">Zrušiť</button>
            <div style="display:flex; gap:8px;">
                <button type="button" id="btn-save-service-add-another" onclick="saveService(true)" class="btn" style="padding:10px 16px; border-radius:8px; font-size:13px; background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-primary);">
                    + Uložiť a pridať ďalšiu
                </button>
                <button type="button" id="btn-save-service" onclick="saveService(false)" class="btn-primary" style="padding:10px 20px; border-radius:8px; font-weight:700; font-size:13.5px;">
                    Uložiť službu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Priradiť zamestnanca k službe -->
<div id="assign-emp-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:var(--card-bg); padding:30px; border-radius:12px; max-width:400px; width:90%; position:relative;">
        <h3 style="margin-top:0;">Cena služby u zamestnanca</h3>
        <input type="hidden" id="assign-srv-id" value="">
        <div class="form-group" style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px;">Zamestnanec <span style="color:red">*</span></label>
            <select id="assign-emp-id" class="vueto-input" style="width:100%; box-sizing:border-box;"></select>
        </div>
        <div style="display:flex; gap:15px; margin-bottom:15px;">
            <div class="form-group" style="flex:1;">
                <label style="display:block; margin-bottom:5px;">Cena (€) <span style="color:red">*</span></label>
                <input type="number" step="0.01" id="assign-price" class="vueto-input" placeholder="Napr. 15.00" style="width:100%; box-sizing:border-box;">
            </div>
            <div class="form-group" style="flex:1;">
                <label style="display:block; margin-bottom:5px;">Trvanie (min) <span style="color:red">*</span></label>
                <input type="number" id="assign-duration" class="vueto-input" placeholder="Napr. 30" style="width:100%; box-sizing:border-box;">
            </div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:25px;">
            <button onclick="document.getElementById('assign-emp-modal').style.display='none'" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
            <button onclick="saveEmployeeService()" class="btn-primary" style="padding:10px 20px;">Uložiť Cenu</button>
        </div>
    </div>
</div>

<!-- Modal: Nahratie videa na YouTube cez <?= BRAND_NAME ?> -->
<div id="modal-youtube-help" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10000; justify-content:center; align-items:center; backdrop-filter:blur(4px);">
    <div class="modal-content" style="background:var(--card-bg); padding:30px; border-radius:14px; max-width:520px; width:92%; position:relative; border:1px solid var(--border-color); box-shadow:0 20px 40px rgba(0,0,0,0.3);">
        <button type="button" onclick="closeYoutubeHelpModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size:20px; display:flex; align-items:center;">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="width:44px; height:44px; border-radius:8px; background:#ff0000; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:26px;">smart_display</span>
            </div>
            <div>
                <h3 style="margin:0 0 3px 0; font-size:17px; color:var(--text-primary);">Nahratie videa na YouTube</h3>
                <span style="font-size:12px; color:var(--text-secondary);">Oficiálna služba <?= BRAND_NAME ?> (@volnekreslo)</span>
            </div>
        </div>

        <p style="font-size:13px; color:var(--text-secondary); line-height:1.5; margin:0 0 16px 0;">
            Nemáte čas alebo skúsenosti so zakladaním vlastného YouTube kanála? Žiadny problém! Zašlite nám vaše video a náš tím sa postará o kompletné spracovanie:
        </p>

        <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:10px; padding:14px; margin-bottom:20px; font-size:12.5px; line-height:1.6; color:var(--text-primary);">
            <div style="display:flex; align-items:flex-start; gap:8px; margin-bottom:8px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color); font-size:18px;">check_circle</span>
                <span><strong>1. Pošlete nám video:</strong> Cez WhatsApp alebo E-mail / Uschovna.cz.</span>
            </div>
            <div style="display:flex; align-items:flex-start; gap:8px; margin-bottom:8px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color); font-size:18px;">check_circle</span>
                <span><strong>2. Optimalizujeme a nahráme:</strong> Vložíme video vo Full HD kvalite na náš oficiálny kanál <a href="https://www.youtube.com/@volnekreslo" target="_blank" style="color:#ff0000; font-weight:700; text-decoration:underline;">@volnekreslo</a> s názvom a popisom vášho salónu.</span>
            </div>
            <div style="display:flex; align-items:flex-start; gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color); font-size:18px;">check_circle</span>
                <span><strong>3. Automatické prepojenie:</strong> Odkaz na video automaticky priradíme k vášmu profilu, kde sa začne ihneď prehrávať.</span>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="https://wa.me/421918808298?text=Dobry%20den,%20chcem%20nahrat%20promo%20video%20na%20YouTube%20pre%20moju%20prevadzku" target="_blank" style="background:#25d366; color:#fff; padding:11px 18px; border-radius:8px; font-size:13.5px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                <span class="material-symbols-outlined" style="font-size:19px;">chat</span>
                Poslať video cez WhatsApp (+421 918 808 298)
            </a>
            <a href="mailto:info@<?= BRAND_SITE ?>?subject=Ziadost%20o%20nahratie%20videa%20na%20YouTube%20pre%20prevadzku" style="background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary); padding:11px 18px; border-radius:8px; font-size:13.5px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                <span class="material-symbols-outlined" style="font-size:19px;">mail</span>
                Napísať nám na info@<?= BRAND_SITE ?>
            </a>
            <a href="https://www.youtube.com/@volnekreslo" target="_blank" style="background:rgba(255,0,0,0.08); border:1px solid rgba(255,0,0,0.25); color:#ff0000; padding:10px 18px; border-radius:8px; font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                <span class="material-symbols-outlined" style="font-size:18px;">smart_display</span>
                Pozrieť náš YouTube kanál (@volnekreslo)
            </a>
        </div>
    </div>
</div>

<!-- Modal: Instagram Propagácia & Zdieľanie -->
<div id="instagram-promo-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10000; justify-content:center; align-items:center; backdrop-filter:blur(6px);">
    <div style="background:var(--card-bg); border:1px solid var(--border-color); width:100%; max-width:540px; border-radius:18px; padding:28px; box-shadow:var(--shadow-lg); text-align:left; position:relative; box-sizing:border-box; max-height:92vh; overflow-y:auto;">
        <!-- Close Button -->
        <button type="button" onclick="closeInstagramPromoModal()" style="position:absolute; top:18px; right:18px; background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size:22px; display:flex; align-items:center;">
            <span class="material-symbols-outlined">close</span>
        </button>

        <h3 style="margin:0 0 6px 0; font-size:18px; font-weight:850; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="color:#e1306c; font-size:24px;">photo_camera</span>
            Propagovať na Instagrame
        </h3>
        <p style="margin:0 0 18px 0; font-size:13px; color:var(--text-secondary);">Vyberte si jednu z metód zdieľania odkazu a získajte <strong>+0,10 €</strong> do Peňaženky.</p>

        <!-- Instagram Tabs Buttons -->
        <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:1px; margin-bottom:18px;">
            <button type="button" onclick="switchInstagramTab('story')" id="ig-btn-story" class="ig-tab-btn active" style="flex:1; padding:9px 6px; background:none; border:none; border-bottom:2.5px solid #e1306c; color:#e1306c; font-weight:800; font-size:12px; cursor:pointer; transition:all 0.2s; outline:none;">Možnosť A: Story</button>
            <button type="button" onclick="switchInstagramTab('dm')" id="ig-btn-dm" class="ig-tab-btn" style="flex:1; padding:9px 6px; background:none; border:none; border-bottom:2.5px solid transparent; color:var(--text-secondary); font-weight:700; font-size:12px; cursor:pointer; transition:all 0.2s; outline:none;">Možnosť B: Správa (DM)</button>
            <button type="button" onclick="switchInstagramTab('card')" id="ig-btn-card" class="ig-tab-btn" style="flex:1; padding:9px 6px; background:none; border:none; border-bottom:2.5px solid transparent; color:var(--text-secondary); font-weight:700; font-size:12px; cursor:pointer; transition:all 0.2s; outline:none;">Možnosť C: Kupón</button>
        </div>

        <input type="hidden" id="ig-selected-ad-title" value="">
        <input type="hidden" id="ig-selected-affiliate-link" value="">
        <input type="hidden" id="ig-selected-ad-target" value="app">

        <!-- TAB CONTENT: STORY -->
        <div id="ig-tab-content-story" class="ig-tab-pane" style="display:block;">
            <p style="font-size:13px; color:var(--text-secondary); line-height:1.5; margin-bottom:14px;">
                Instagram Stories sú najrýchlejší spôsob. Jedným klikom skopírujeme váš odkaz a otvoríme Instagram pre vytvorenie príbehu.
            </p>
            <div style="background:var(--bg-color); border:1px solid var(--border-color); padding:14px 16px; border-radius:12px; margin-bottom:18px;">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; align-items:center; gap:10px; font-size:12.5px; color:var(--text-primary); font-weight:600;">
                        <span style="width:20px; height:20px; border-radius:50%; background:#e1306c; color:white; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; flex-shrink:0;">1</span>
                        <span>Odkaz sa automaticky skopíruje do schránky</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; font-size:12.5px; color:var(--text-primary); font-weight:600;">
                        <span style="width:20px; height:20px; border-radius:50%; background:#e1306c; color:white; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; flex-shrink:0;">2</span>
                        <span>Vytvoríte nový príbeh (Story) na Instagrame</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; font-size:12.5px; color:var(--text-primary); font-weight:600;">
                        <span style="width:20px; height:20px; border-radius:50%; background:#e1306c; color:white; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; flex-shrink:0;">3</span>
                        <span>Pridáte nálepku „ODKAZ“ a vložíte link</span>
                    </div>
                </div>
            </div>
            <button type="button" onclick="executeInstagramAction('story')" style="width:100%; height:44px; background:linear-gradient(135deg, #e1306c, #f77737); color:white; border:none; border-radius:10px; font-size:13.5px; font-weight:800; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 15px rgba(225, 48, 108, 0.25); display:flex; align-items:center; justify-content:center; gap:8px;">
                <span class="material-symbols-outlined" style="font-size:18px;">content_copy</span>
                <span>Kopírovať odkaz a otvoriť Instagram</span>
            </button>
        </div>

        <!-- TAB CONTENT: DIRECT MESSAGE -->
        <div id="ig-tab-content-dm" class="ig-tab-pane" style="display:none;">
            <p style="font-size:13px; color:var(--text-secondary); line-height:1.5; margin-bottom:14px;">
                Pošlite priamy odkaz s pozvánkou známym alebo zákazníkom do správ Direct Message na Instagrame.
            </p>
            <div style="background:var(--bg-color); border:1px solid var(--border-color); padding:14px 16px; border-radius:12px; margin-bottom:18px;">
                <div style="font-size:12px; color:var(--text-secondary); font-weight:600; line-height:1.5;">
                    📋 <strong>Predvyplnená správa v schránke:</strong><br>
                    <span id="ig-dm-preview-text" style="font-style:italic; opacity:0.9; color:var(--text-primary);">Ahoj! Pozri si ponuku a voľné termíny na portáli <?= BRAND_NAME ?>. Rezervuj si kreslo online bez čakania: https://<?= BRAND_SITE ?>/</span>
                </div>
            </div>
            <button type="button" onclick="executeInstagramAction('dm')" style="width:100%; height:44px; background:linear-gradient(135deg, #e1306c, #833ab4); color:white; border:none; border-radius:10px; font-size:13.5px; font-weight:800; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 15px rgba(131, 58, 180, 0.25); display:flex; align-items:center; justify-content:center; gap:8px;">
                <span class="material-symbols-outlined" style="font-size:18px;">send</span>
                <span>Kopírovať správu a otvoriť Instagram DM</span>
            </button>
        </div>

        <!-- TAB CONTENT: PROMO KUPÓN CARD -->
        <div id="ig-tab-content-card" class="ig-tab-pane" style="display:none; text-align:center;">
            <p style="font-size:13px; color:var(--text-secondary); line-height:1.5; margin-bottom:15px; text-align:left;">
                Zdieľajte svoj digitálny promo kupón! Vy získate odmenu <strong>+0,10 €</strong> do Peňaženky a každý, kto sa zaregistruje s vaším promo kódom, získa <strong>14 dní Last Minute termínov ZADARMO</strong>.
            </p>
            
            <div id="rewards-ig-coupon-element" style="background:linear-gradient(135deg, #1b0a2a 0%, #0d0413 100%); border:2px solid rgba(225, 48, 108, 0.5); border-radius:18px; padding:22px 20px; color:white; margin:0 auto 18px auto; max-width:330px; box-shadow:0 10px 30px rgba(0,0,0,0.5); position:relative; overflow:hidden; box-sizing:border-box; text-align:center;">
                <div style="font-size:10px; font-weight:900; letter-spacing:2px; color:#e1306c; text-transform:uppercase; margin-bottom:6px;"><?= mb_strtoupper(BRAND_NAME) ?> • BONUS VOUCHER</div>
                <h4 style="margin:0; font-size:19px; font-weight:900; color:#fff; line-height:1.2;">14 DNÍ <span style="color:#f59e0b;">LAST MINUTE</span></h4>
                <p style="margin:4px 0 12px 0; font-size:11.5px; color:rgba(255,255,255,0.85); line-height:1.35;">Zadaj tento promo kód pri registrácii a získaj 14 dní bleskových termínov ZADARMO!</p>
                <div style="background:rgba(255,255,255,0.1); border:1.5px dashed rgba(245, 158, 11, 0.7); padding:9px 12px; border-radius:10px; display:inline-block; width:100%; box-sizing:border-box; margin-bottom:12px;">
                    <div style="font-size:9.5px; opacity:0.8; text-transform:uppercase; font-weight:700; color:#e1306c;">Registračný Promo Kód</div>
                    <div id="ig-coupon-code-text" style="font-size:16px; font-weight:900; font-family:monospace; color:#f59e0b; letter-spacing:2px; margin-top:2px;">LASTMINUTE14</div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:9.5px; opacity:0.85; font-weight:700; border-top:1px solid rgba(255,255,255,0.1); padding-top:8px;">
                    <span>www.<?= BRAND_SITE ?></span>
                    <span style="color:#e1306c;">+0,10 € Bonus Salón</span>
                </div>
            </div>

            <button type="button" onclick="executeInstagramAction('card')" style="width:100%; height:44px; background:linear-gradient(135deg, #833ab4, #ff3040); color:white; border:none; border-radius:10px; font-size:13.5px; font-weight:800; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 4px 15px rgba(255, 48, 64, 0.25);">
                <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span>
                <span>Potvrdiť zdieľanie a získať odmenu (+0,10 €)</span>
            </button>
        </div>
    </div>
</div>
