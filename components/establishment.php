<?php if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php'; ?>
<div id="sec-establishment" class="section">
    <div style="width: 100%; margin-bottom: 25px;">

        <!-- Hlavička sekcie -->
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 class="section-header" style="font-size: 24px; margin-bottom: 4px;">
                    <span class="material-symbols-outlined" style="font-size: 28px; color: var(--primary-color);">storefront</span> 
                    Moja prevádzka
                </h2>
                <p class="section-desc" style="margin-bottom: 0;">Kompletná správa firemných údajov, predplatného balíka, smien zamestnancov a vybavenia prevádzky.</p>
            </div>
            <div>
                <button type="button" onclick="loadEstablishmentInfo()" class="btn-secondary" style="padding: 8px 18px; border-radius: 12px; font-weight: 500; font-size: 13.5px;" title="Obnoviť údaje">
                    <span class="material-symbols-outlined" style="font-size: 18px;">refresh</span>
                    <span>Obnoviť</span>
                </button>
            </div>
        </div>

        <form id="establishmentForm" onsubmit="saveEstablishmentInfo(event)">
            
            <div class="profile-grid">
                
                <!-- KARTA 1: FIREMNÉ A FAKTURAČNÉ ÚDAJE -->
                <div class="vueto-card full-width">
                    <div class="vueto-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="premium-card-title">
                            <span class="material-symbols-outlined">domain</span> 
                            Firemné a fakturačné údaje
                        </h3>
                        <span style="font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 4px;">
                            <span class="material-symbols-outlined" style="font-size: 16px; color: var(--primary-color);">verified_user</span> 
                            FinStat a Register SR
                        </span>
                    </div>
                    <div class="vueto-card-body">
                        
                        <!-- Rýchle vyhľadanie podľa IČO -->
                        <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                            <div style="flex: 1; min-width: 250px;">
                                <strong style="display: block; font-size: 14px; margin-bottom: 3px;">Automatické načítanie údajov z registra</strong>
                                <span style="font-size: 13px; color: var(--text-secondary);">Zadajte IČO a systém automaticky overí a doplní firemné údaje.</span>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="est-ico-search" placeholder="Zadajte IČO..." style="width: 140px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--card-bg); font-size: 14px;">
                                <button type="button" onclick="fetchRegisterData()" class="btn-secondary" style="padding: 10px 18px; font-size: 13.5px; border-radius: 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">search</span>
                                    <span>Načítať</span>
                                </button>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                            
                            <div class="form-group">
                                <label>Obchodné meno (názov s.r.o. / živnosti)</label>
                                <input type="text" id="est-legal-name" placeholder="Napr. Beauty Studio s.r.o.">
                            </div>

                            <div class="form-group">
                                <label>Názov prevádzky (pre zákazníkov) <span style="color:red">*</span></label>
                                <input type="text" id="est-name" required placeholder="Napr. Štúdio Krásy Bratislava">
                            </div>

                            <div class="form-group">
                                <label>Meno konateľa / majiteľa</label>
                                <input type="text" id="est-owner-name" placeholder="Napr. Mgr. Jana Kováčová">
                            </div>

                            <div class="form-group">
                                <label>IČO</label>
                                <input type="text" id="est-ico" placeholder="12345678">
                            </div>

                            <div class="form-group">
                                <label>DIČ</label>
                                <input type="text" id="est-dic" placeholder="2021234567">
                            </div>

                            <div class="form-group">
                                <label>IČ DPH (ak je platca)</label>
                                <input type="text" id="est-ic-dph" placeholder="SK2021234567">
                            </div>

                        </div>

                        <div style="margin: 15px 0; padding: 12px 16px; background: var(--bg-color); border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="font-size: 14px; display: block;">Platca DPH</strong>
                                <span style="font-size: 12px; color: var(--text-secondary);">Je vaša prevádzka / firma registrovaným platcom DPH?</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="est-is-vat-payer">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                            
                            <div class="form-group">
                                <label>Ulica a číslo prevádzky <span style="color:red">*</span></label>
                                <input type="text" id="est-address" required placeholder="Napr. Obchodná 15">
                            </div>

                            <div class="form-group">
                                <label>Mesto a PSČ <span style="color:red">*</span></label>
                                <input type="text" id="est-city" required placeholder="Napr. Bratislava">
                            </div>

                            <div class="form-group">
                                <label>Kontaktný telefón prevádzky</label>
                                <input type="text" id="est-phone" placeholder="+421 900 123 456">
                            </div>

                            <div class="form-group">
                                <label>Fakturačný e-mail</label>
                                <input type="email" id="est-billing-email" placeholder="faktury@prevadzka.sk">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Bankový účet (IBAN pre platby a zálohy)</label>
                                <input type="text" id="est-deposit-iban" placeholder="SK00 0000 0000 0000 0000 0000">
                            </div>

                        </div>

                    </div>
                </div>

                <!-- KARTA 2: PREDPLATNÉ A PREDPLATENÉ SLUŽBY -->
                <div class="vueto-card">
                    <div class="vueto-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="premium-card-title">
                            <span class="material-symbols-outlined" style="color: var(--primary-color);">workspace_premium</span> 
                            Predplatné balíka a funkcie
                        </h3>
                        <span id="est-tier-badge" style="padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; background: rgba(176, 128, 66, 0.15); color: var(--primary-color);">FREE</span>
                    </div>
                    <div class="vueto-card-body">
                        
                        <!-- Popis balíka a služba -->
                        <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; margin-bottom: 18px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-size: 11.5px; text-transform: uppercase; font-weight: 700; color: var(--text-secondary); letter-spacing: 0.5px;">Aktívny program:</span>
                                <strong id="est-plan-name" style="font-size: 13.5px; color: var(--primary-color);">FREE – Základný</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12.5px;">
                                <span style="color: var(--text-secondary);">Zameranie prevádzky:</span>
                                <span id="est-main-service-name" style="font-weight: 600; color: var(--text-primary);">Služby krásy a starostlivosti</span>
                            </div>
                        </div>

                        <!-- Mesačný limit rezervácií -->
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                <span style="color: var(--text-secondary);">Mesačný limit rezervácií:</span>
                                <strong id="est-limit-text" style="color: var(--text-primary);">0 / 150</strong>
                            </div>
                            <div style="background: var(--bg-color); height: 8px; border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color);">
                                <div id="est-limit-bar" style="width: 0%; height: 100%; background: var(--primary-color); border-radius: 4px; transition: width 0.4s;"></div>
                            </div>
                        </div>

                        <!-- Zoznam aktívnych funkcií balíka -->
                        <h4 style="font-size: 12px; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.5px; margin: 0 0 12px 0; font-weight: 800;">Zahrnuté funkcie a služby portálu:</h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: #10b981;">check_circle</span>
                                <span>Online kalendár a rezervácie</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: #10b981;">check_circle</span>
                                <span>Zoznam a správa zákazníkov (CRM)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;" id="feat-lastminute">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: #10b981;">check_circle</span>
                                <span>Last Minute voľné termíny a zľavy</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;" id="feat-custom-url">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: var(--text-secondary);" id="icon-custom-url">lock</span>
                                <span>Vlastná unikátna adresa (<?= BRAND_SITE ?>/@nazov)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;" id="feat-auto-confirm">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: var(--text-secondary);" id="icon-auto-confirm">lock</span>
                                <span>Automatické schvaľovanie termínov</span>
                            </div>
                        </div>

                        <button type="button" onclick="showSection('billing')" class="btn-secondary" style="width: 100%; padding: 10px; font-size: 13px; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">upgrade</span>
                            <span>Spravovať balík a predplatné</span>
                        </button>

                    </div>
                </div>

                <!-- KARTA 3: ZAMESTNANCI A SMENY -->
                <div class="vueto-card">
                    <div class="vueto-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="premium-card-title">
                            <span class="material-symbols-outlined">group</span> 
                            Zamestnanci a smeny
                        </h3>
                        <button type="button" onclick="showSection('team')" class="btn-secondary" style="padding: 6px 14px; border-radius: 10px; font-size: 12px; display: flex; align-items: center; gap: 4px;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">person_add</span>
                            <span>Pridať</span>
                        </button>
                    </div>
                    <div class="vueto-card-body">
                        
                        <div id="est-employees-list" style="display: flex; flex-direction: column; gap: 10px; min-height: 120px;">
                            <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Načítavam zoznam zamestnancov...</p>
                        </div>

                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: var(--text-secondary);">Rozpis pracovných smien a voľna:</span>
                            <button type="button" onclick="showSection('team')" class="btn-primary" style="padding: 8px 16px; font-size: 12px; border-radius: 10px;">
                                Nastaviť smeny
                            </button>
                        </div>

                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                            <label for="est-capacity" style="display: block; font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Koľko klientov naraz dokážete obslúžiť? (nepovinné)</label>
                            <p style="font-size: 11.5px; color: var(--text-secondary); margin: 0 0 8px 0;">Fyzický strop prevádzky (počet kresiel, stanovíšť, trénerov a pod.) — ak zamestnanci majú prekrývajúce sa zmeny, systém nikdy neponúkne viac súbežných rezervácií, než sem zadáte. Nechajte prázdne, ak toto obmedzenie nepotrebujete.</p>
                            <input type="number" id="est-capacity" min="1" step="1" placeholder="Napr. 2" style="width: 120px; box-sizing: border-box; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-primary); font-size: 14px;">
                        </div>

                    </div>
                </div>

                <!-- KARTA 4: VYBAVENIE A VÝHODY PREVÁDZKY -->
                <div class="vueto-card full-width">
                    <div class="vueto-card-header">
                        <h3 class="premium-card-title">
                            <span class="material-symbols-outlined">hotel_class</span> 
                            Vybavenie a výhody prevádzky
                        </h3>
                    </div>
                    <div class="vueto-card-body">
                        <p style="font-size: 13px; color: var(--text-secondary); margin-top: 0; margin-bottom: 15px;">
                            Označte vybavenie a benefity, ktorými vaša prevádzka disponuje. Tieto položky sa zobrazujú zákazníkom vo verejnom profile prevádzky.
                        </p>

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;" id="amenities-grid">
                            
                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="parking">
                                <span class="material-symbols-outlined">local_parking</span>
                                <span class="amenity-name">Parkovanie pri prevádzke</span>
                            </label>

                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="card_payment">
                                <span class="material-symbols-outlined">credit_card</span>
                                <span class="amenity-name">Platba kartou</span>
                            </label>

                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="coffee">
                                <span class="material-symbols-outlined">coffee</span>
                                <span class="amenity-name">Káva / Občerstvenie</span>
                            </label>

                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="wifi">
                                <span class="material-symbols-outlined">wifi</span>
                                <span class="amenity-name">Wi-Fi pre zákazníkov</span>
                            </label>

                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="wheelchair">
                                <span class="material-symbols-outlined">accessible</span>
                                <span class="amenity-name">Bezbariérový prístup</span>
                            </label>

                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="ac">
                                <span class="material-symbols-outlined">ac_unit</span>
                                <span class="amenity-name">Klimatizácia</span>
                            </label>

                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="pet_friendly">
                                <span class="material-symbols-outlined">pets</span>
                                <span class="amenity-name">Pet friendly (zvieratá vítané)</span>
                            </label>

                            <label class="amenity-tile">
                                <input type="checkbox" name="amenities[]" value="kids_corner">
                                <span class="material-symbols-outlined">child_care</span>
                                <span class="amenity-name">Detský kútik</span>
                            </label>

                        </div>

                    </div>
                </div>

                <!-- TLAČIDLO ULOŽIŤ -->
                <div class="full-width" style="text-align: right; margin-top: 10px;">
                    <button type="submit" class="btn-primary" style="padding: 14px 36px; font-size: 15px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(176, 128, 66, 0.35);">
                        <span class="material-symbols-outlined">save</span>
                        <span>Uložiť údaje prevádzky</span>
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>

<style>
.amenity-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}
.amenity-tile:hover {
    border-color: var(--primary-color);
    background: var(--card-bg);
}
.amenity-tile input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color);
    cursor: pointer;
}
.amenity-tile .material-symbols-outlined {
    font-size: 22px;
    color: var(--primary-color);
}
.amenity-tile .amenity-name {
    font-size: 13.5px;
    font-weight: 500;
    color: var(--text-primary);
}
.amenity-tile:has(input:checked) {
    background: rgba(176, 128, 66, 0.1);
    border-color: var(--primary-color);
}
</style>
