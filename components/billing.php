<?php if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php'; ?>
<div id="sec-billing" class="section">
    <div class="vueto-card">
        <div class="vueto-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 class="section-header" style="margin: 0;">
                        <span class="material-symbols-outlined" style="color: var(--primary-color);">workspace_premium</span> 
                        Môj Balík a Predplatné
                    </h2>
                    <p class="section-desc" style="margin-top: 5px;">Vyberte si balík služieb na mieru pre vašu prevádzku. Žiadne skryté poplatky, kedykoľvek môžete prejsť na iný balík.</p>
                </div>
            </div>
        </div>
        <div class="premium-card-body" style="padding: 24px 20px;">
            
            <!-- 1. Súhrnný panel aktuálneho predplatného s dátumom expirácie -->
            <div id="billing-current-summary" style="background: linear-gradient(135deg, rgba(176, 128, 66, 0.08), rgba(99, 102, 241, 0.08)); border: 1.5px solid var(--border-color); border-radius: 16px; padding: 20px 24px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div id="summary-tier-icon-wrap" style="width: 52px; height: 52px; border-radius: 14px; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 4px 12px rgba(176, 128, 66, 0.35);">
                        <span id="summary-tier-icon" class="material-symbols-outlined" style="font-size: 28px;">workspace_premium</span>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 13px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Aktuálny balík</span>
                            <span id="summary-tier-badge" style="font-size: 12px; font-weight: 800; padding: 2px 10px; border-radius: 6px; background: rgba(176, 128, 66, 0.2); color: var(--primary-color);">VIP (ELITE)</span>
                        </div>
                        <h3 id="summary-tier-title" style="margin: 3px 0 0 0; font-size: 20px; font-weight: 800; color: var(--text-primary);">Balík VIP</h3>
                    </div>
                </div>

                <!-- Informácia o platnosti a dátume do kedy -->
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 10px 18px; display: flex; align-items: center; gap: 12px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);">
                        <span class="material-symbols-outlined" style="font-size: 24px; color: #10b981;">event_available</span>
                        <div>
                            <div style="font-size: 11px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase;">Platnosť predplatného do</div>
                            <div id="summary-tier-expiry" style="font-size: 15px; font-weight: 800; color: #10b981;">
                                <span id="summary-expiry-date">19. 09. 2026</span>
                                <span id="summary-expiry-days" style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-left: 5px;">(zostáva 30 dní)</span>
                            </div>
                        </div>
                    </div>

                    <div id="summary-status-tag" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; font-weight: 800; font-size: 13px; padding: 8px 14px; border-radius: 10px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">check_circle</span>
                        <span>Aktívne</span>
                    </div>
                </div>
            </div>

            <!-- Trial banner -->
            <div style="display: flex; align-items: center; gap: 14px; background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(99,102,241,0.08)); border: 1.5px solid rgba(59,130,246,0.25); border-radius: 14px; padding: 14px 20px; margin-bottom: 28px;">
                <span class="material-symbols-outlined" style="font-size: 28px; color: #3b82f6; flex-shrink: 0;">rocket_launch</span>
                <div>
                    <div style="font-size: 14px; font-weight: 800; color: var(--text-primary);">Nová registrácia = 30 dní START zadarmo</div>
                    <div style="font-size: 12.5px; color: var(--text-secondary); margin-top: 2px;">Každý nový účet začína s plným balíkom START na 30 dní bez platby. Po uplynutí si vyberiete, na čom pokračujete.</div>
                </div>
            </div>

            <!-- Promo kód (Fáza 5) -->
            <div id="promo-code-box" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px 22px; margin-bottom: 28px;">
                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(176,128,66,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-outlined" style="font-size: 20px; color: var(--primary-color);">redeem</span>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <strong style="font-size: 14px; color: var(--text-primary); display: block;">Mám promo kód</strong>
                        <span id="promo-active-info" style="font-size: 12px; color: var(--text-secondary);">Zľava alebo predĺženie balíka na základe akvizičného kódu.</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="promo-code-input" placeholder="Napr. KRESLO30" style="text-transform: uppercase; padding: 9px 14px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-primary); font-size: 13px; width: 160px;">
                        <button type="button" onclick="redeemPromoCodeBox()" class="btn-primary" style="padding: 9px 18px; font-size: 13px; white-space: nowrap;">Uplatniť</button>
                    </div>
                </div>
                <div style="border-top: 1px solid var(--border-color); margin-top: 16px; padding-top: 14px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <strong style="font-size: 13px; color: var(--text-primary); display: block;">Odporučte kolegovi salón</strong>
                        <span style="font-size: 12px; color: var(--text-secondary);">Kto uplatní váš kód, dostane 15% zľavu na doplnky — vám sa za to pripíše +14 dní k balíku.</span>
                    </div>
                    <div id="referral-code-display" style="display: none; align-items: center; gap: 8px;">
                        <code id="referral-code-value" style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 14px; font-size: 13.5px; font-weight: 700; color: var(--primary-color);"></code>
                        <button type="button" onclick="copyReferralCode()" class="btn-secondary" style="padding: 8px 12px; font-size: 12.5px;"><span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">content_copy</span></button>
                    </div>
                    <button type="button" id="btn-generate-referral" onclick="generateReferralCode()" class="btn-secondary" style="padding: 9px 18px; font-size: 13px; white-space: nowrap;">Vygenerovať môj kód</button>
                </div>
            </div>

            <!-- Prepínač Mesačne / Ročne -->
            <div style="text-align: center; margin-bottom: 24px; margin-top: 5px;">
                <div style="display: inline-flex; align-items: center; background: rgba(0, 0, 0, 0.05); border: 1.5px solid var(--border-color); border-radius: 18px; padding: 6px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.04);">
                    <button type="button" id="btn-yearly" onclick="toggleBilling('yearly')" style="background: linear-gradient(135deg, #10b981, #059669); border: 1px solid #10b981; padding: 10px 24px; border-radius: 13px; font-weight: 800; cursor: pointer; color: #ffffff; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s ease; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);">
                        <span class="material-symbols-outlined" id="icon-yearly" style="font-size: 19px; color: #ffffff;">event_repeat</span>
                        <span>Ročne</span>
                        <span id="badge-yearly-discount" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 8px; letter-spacing: 0.3px;">ODPORÚČANÉ</span>
                    </button>
                    <button type="button" id="btn-monthly" onclick="toggleBilling('monthly')" style="background: transparent; border: 1px solid transparent; padding: 10px 24px; border-radius: 13px; font-weight: 700; cursor: pointer; color: var(--text-secondary); font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s ease;">
                        <span class="material-symbols-outlined" style="font-size: 19px; color: var(--text-secondary);">calendar_month</span>
                        <span>Mesačne</span>
                    </button>
                </div>
            </div>

            <!-- Karty balíkov -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; align-items: stretch;">
                
                <!-- 1. FREE (Neutrálny / Sivý) -->
                <div id="card-tier-free" class="pricing-card card-free" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 16px; padding: 28px; display: flex; flex-direction: column; text-align: left; transition: all 0.3s ease; position: relative;">
                    <!-- Active Ribbon Slot -->
                    <div id="ribbon-tier-free" class="tier-active-ribbon" style="display: none; position: absolute; top: -13px; right: 18px; background: #10b981; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 12px; border-radius: 8px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); align-items: center; gap: 4px; z-index: 2;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> VÁŠ AKTÍVNY BALÍK
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h3 style="color: #64748b; margin: 0; font-size: 20px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 22px;">eco</span>
                            FREE
                        </h3>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; background: rgba(100, 116, 139, 0.12); padding: 3px 8px; border-radius: 6px;">ZÁKLAD</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-secondary); min-height: 38px; margin: 4px 0 15px 0; line-height: 1.4;">Základná prítomnosť a manuálna správa pre vašu prevádzku.</p>
                    
                    <div style="margin: 0 0 15px 0;">
                        <p style="font-size: 34px; font-weight: 800; margin: 0; color: var(--text-primary); line-height: 1;">0.00 €<span class="price-period-label" style="font-size: 14px; color: var(--text-secondary); font-weight: 600;"> / mesiac</span></p>
                        <div id="status-line-free" class="savings-tag" style="min-height: 22px; margin-top: 6px; font-size: 12px; color: var(--text-secondary);">Trvalý bezplatný prístup</div>
                    </div>
                    
                    <hr style="border:none; border-top: 1px solid var(--border-color); margin: 0 0 18px 0;">
                    
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 2px;">Čo je v balíku</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>150 rezervácií mesačne</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>1 používateľ</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>Kalendár a zoznam rezervácií</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>CRM klientov (plný prístup)</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>Profil prevádzky a cenník</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>Potvrdzujúce e-maily rezervácií</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>Tabuľa úloh</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">auto_awesome</span><span>5 AI kreditov mesačne</span></div>
                        <div style="border-top:1px solid var(--border-color);margin:4px 0 2px 0;"></div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-secondary);margin-bottom:2px;">Nie je k dispozícii</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#64748b;flex-shrink:0;margin-top:1px;">check_circle</span><span>Inzercia dostupná · 0,50 € / inzerát z Peňaženky</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Vlastné logo a sociálne siete</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Automatické schvaľovanie rezervácií</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Hodnotenia a recenzie zákazníkov</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Google Calendar sync</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Vlastná e-mailová adresa pri potvrdeniach</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Zamestnanci, Last Minute, Marketing</span></div>
                    </div>

                    <div id="btn-container-free" style="margin-top: 25px;">
                        <button type="button" id="btn-tier-free" onclick="promptChangeTier('free')" class="btn" style="width:100%; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-secondary); padding: 12px; font-weight: 700; cursor: pointer;">Prejsť na FREE</button>
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--text-secondary);">sms</span><span style="font-size:12px;color:var(--text-secondary);">Zasielanie SMS — pripravujeme</span></div>
                    </div>
                </div>
                
                <!-- 2. START (Svieža Modrá) -->
                <div id="card-tier-start" class="pricing-card card-start" style="background: var(--card-bg); border: 1.5px solid #3b82f6; border-radius: 16px; padding: 28px; display: flex; flex-direction: column; text-align: left; transition: all 0.3s ease; position: relative;">
                    <!-- Active Ribbon Slot -->
                    <div id="ribbon-tier-start" class="tier-active-ribbon" style="display: none; position: absolute; top: -13px; right: 18px; background: #10b981; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 12px; border-radius: 8px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); align-items: center; gap: 4px; z-index: 2;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> VÁŠ AKTÍVNY BALÍK
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h3 style="color: #3b82f6; margin: 0; font-size: 20px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 22px;">rocket_launch</span>
                            START
                        </h3>
                        <span style="font-size: 11px; font-weight: 700; color: #3b82f6; background: rgba(59, 130, 246, 0.12); padding: 3px 8px; border-radius: 6px;">BASIC</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-secondary); min-height: 38px; margin: 4px 0 15px 0; line-height: 1.4;">Ideálny pre menšiu prevádzku a jedného špecialistu.</p>
                    
                    <div style="margin: 0 0 15px 0;">
                        <p style="font-size: 34px; font-weight: 800; margin: 0; color: var(--text-primary); line-height: 1;">
                            <span class="price-val" data-monthly="6.90" data-yearly="5.90" data-yearly-total="70.80">5.90</span> €
                            <span class="price-period-label" style="font-size: 14px; color: var(--text-secondary); font-weight: 600;"> / mesiac</span>
                        </p>
                        <div id="status-line-start" class="savings-tag" style="min-height: 22px; margin-top: 6px; font-size: 12px; color: var(--text-secondary);">
                            <span class="tag-monthly" style="display:none;">Bez záväzkov · Platba každý mesiac</span>
                            <span class="tag-yearly" style="display:inline-flex; color: #10b981; font-weight: 700; align-items: center; gap: 4px;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">savings</span> Ušetríte 12,00 € ročne
                            </span>
                        </div>
                    </div>
                    
                    <hr style="border:none; border-top: 1px solid var(--border-color); margin: 0 0 18px 0;">
                    
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 2px;">Čo je v balíku</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>300 rezervácií mesačne</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>1 používateľ</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Kalendár, rezervácie, CRM klientov</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Vlastné logo a sociálne siete</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Automatické schvaľovanie rezervácií</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Hodnotenia a recenzie zákazníkov</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Tabuľa úloh</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Potvrdzujúce e-maily + Google Calendar sync</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Vlastná e-mailová adresa pri potvrdeniach zákazníkom</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">auto_awesome</span><span>50 AI kreditov mesačne</span></div>
                        <div style="border-top:1px solid var(--border-color);margin:4px 0 2px 0;"></div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-secondary);margin-bottom:2px;">Nie je k dispozícii</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#3b82f6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Inzercia dostupná · 0,50 € / inzerát z Peňaženky</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Zamestnanci / tím</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Last Minute a Titulný banner</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Marketing, e-mail kampane, štatistiky</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Vernostný program, Pobočky</span></div>
                    </div>
                    
                    <div id="btn-container-start" style="margin-top: 25px;">
                        <button type="button" id="btn-tier-start" onclick="promptChangeTier('start')" class="btn" style="width:100%; border-radius: 12px; background: #3b82f6; border: none; color: #fff; padding: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">Vybrať START</button>
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--text-secondary);">sms</span><span style="font-size:12px;color:var(--text-secondary);">Zasielanie SMS — pripravujeme</span></div>
                    </div>
                </div>
                
                <!-- 3. PRO (Fialová / Najpredávanejší) -->
                <div id="card-tier-pro" class="pricing-card card-pro" style="background: var(--card-bg); border: 2px solid #8b5cf6; border-radius: 16px; padding: 28px; display: flex; flex-direction: column; text-align: left; transition: all 0.3s ease; position: relative; box-shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.4);">
                    <!-- Najpredávanejší odznak -->
                    <div id="badge-popular-pro" style="position:absolute; top:-14px; left:50%; transform:translateX(-50%); background: linear-gradient(135deg, #8b5cf6, #6366f1); color:#fff; font-size:11px; padding: 4px 14px; border-radius:8px; font-weight: 800; letter-spacing: 0.6px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.5); display: inline-flex; align-items: center; gap: 4px; z-index: 1;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">star</span>
                        NAJPREDÁVANEJŠÍ
                    </div>

                    <!-- Active Ribbon Slot -->
                    <div id="ribbon-tier-pro" class="tier-active-ribbon" style="display: none; position: absolute; top: -13px; right: 18px; background: #10b981; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 12px; border-radius: 8px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); align-items: center; gap: 4px; z-index: 2;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> VÁŠ AKTÍVNY BALÍK
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h3 style="color: #8b5cf6; margin: 0; font-size: 20px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 22px;">group</span>
                            PRO
                        </h3>
                        <span style="font-size: 11px; font-weight: 700; color: #8b5cf6; background: rgba(139, 92, 246, 0.12); padding: 3px 8px; border-radius: 6px;">ODPORÚČANÝ</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-secondary); min-height: 38px; margin: 4px 0 15px 0; line-height: 1.4;">Pre zabehnuté prevádzky s tímom kolegov.</p>
                    
                    <div style="margin: 0 0 15px 0;">
                        <p style="font-size: 34px; font-weight: 800; margin: 0; color: var(--text-primary); line-height: 1;">
                            <span class="price-val" data-monthly="14.90" data-yearly="12.90" data-yearly-total="154.80">12.90</span> €
                            <span class="price-period-label" style="font-size: 14px; color: var(--text-secondary); font-weight: 600;"> / mesiac</span>
                        </p>
                        <div id="status-line-pro" class="savings-tag" style="min-height: 22px; margin-top: 6px; font-size: 12px; color: var(--text-secondary);">
                            <span class="tag-monthly" style="display:none;">Bez záväzkov · Platba každý mesiac</span>
                            <span class="tag-yearly" style="display:inline-flex; color: #10b981; font-weight: 700; align-items: center; gap: 4px;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">savings</span> Ušetríte 24,00 € ročne
                            </span>
                        </div>
                    </div>
                    
                    <hr style="border:none; border-top: 1px solid var(--border-color); margin: 0 0 18px 0;">
                    
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 2px;">Čo je v balíku</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>1 500 rezervácií mesačne</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>1–3 zamestnanci</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Všetko zo START</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Last Minute ponuky a Titulný banner</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Marketing — základné nástroje</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Hromadné e-maily zákazníkom</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Pokročilé štatistiky a reporty</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>QR kód záloha na rezerváciu (Pay by Square / SEPA)</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">check_circle</span><span>Inzercia · 3 inzeráty mesačne zadarmo · ďalšie 0,50 € z Peňaženky</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#8b5cf6;flex-shrink:0;margin-top:1px;">auto_awesome</span><span>100 AI kreditov mesačne</span></div>
                        <div style="border-top:1px solid var(--border-color);margin:4px 0 2px 0;"></div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-secondary);margin-bottom:2px;">Nie je k dispozícii</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Vernostný program</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Viac pobočiek</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:17px;color:#ef4444;flex-shrink:0;margin-top:1px;">cancel</span><span>Exporty pre účtovníka, API integrácie</span></div>
                    </div>
                    
                    <div id="btn-container-pro" style="margin-top: 25px;">
                        <button type="button" id="btn-tier-pro" onclick="promptChangeTier('pro')" class="btn" style="width:100%; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #6366f1); border: none; color: #fff; padding: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.4);" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">Vybrať PRO</button>
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--text-secondary);">sms</span><span style="font-size:12px;color:var(--text-secondary);">Zasielanie SMS — pripravujeme</span></div>
                    </div>
                </div>
                
                <!-- 4. VIP (Zlatá <?= BRAND_NAME ?>) -->
                <div id="card-tier-vip" class="pricing-card card-vip" style="background: var(--card-bg); border: 2.5px solid var(--primary-color); border-radius: 16px; padding: 28px; display: flex; flex-direction: column; text-align: left; transition: all 0.3s ease; position: relative; box-shadow: 0 10px 30px -10px rgba(176, 128, 66, 0.35);">
                    <!-- Maximálny výkon odznak -->
                    <div id="badge-popular-vip" style="position:absolute; top:-14px; left:50%; transform:translateX(-50%); background: linear-gradient(135deg, #b08042, #d4af37); color:#fff; font-size:11px; padding: 4px 14px; border-radius:8px; font-weight: 800; letter-spacing: 0.6px; box-shadow: 0 4px 12px rgba(176, 128, 66, 0.45); display: inline-flex; align-items: center; gap: 4px; z-index: 1;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">diamond</span>
                        MAXIMÁLNY VÝKON
                    </div>

                    <!-- Active Ribbon Slot -->
                    <div id="ribbon-tier-vip" class="tier-active-ribbon" style="display: none; position: absolute; top: -13px; right: 18px; background: #10b981; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 12px; border-radius: 8px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); align-items: center; gap: 4px; z-index: 2;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> VÁŠ AKTÍVNY BALÍK
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h3 style="color: var(--primary-color); margin: 0; font-size: 20px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 22px;">workspace_premium</span>
                            VIP
                        </h3>
                        <span style="font-size: 11px; font-weight: 700; color: var(--primary-color); background: rgba(176, 128, 66, 0.15); padding: 3px 8px; border-radius: 6px;">ELITE</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-secondary); min-height: 38px; margin: 4px 0 15px 0; line-height: 1.4;">Neobmedzený výkon a kompletný marketing.</p>
                    
                    <div style="margin: 0 0 15px 0;">
                        <p style="font-size: 34px; font-weight: 800; margin: 0; color: var(--text-primary); line-height: 1;">
                            <span class="price-val" data-monthly="29.90" data-yearly="26.90" data-yearly-total="322.80">26.90</span> €
                            <span class="price-period-label" style="font-size: 14px; color: var(--text-secondary); font-weight: 600;"> / mesiac</span>
                        </p>
                        <div id="status-line-vip" class="savings-tag" style="min-height: 22px; margin-top: 6px; font-size: 12px; color: var(--text-secondary);">
                            <span class="tag-monthly" style="display:none;">Bez záväzkov · Platba každý mesiac</span>
                            <span class="tag-yearly" style="display:inline-flex; color: #10b981; font-weight: 700; align-items: center; gap: 4px;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">savings</span> Ušetríte 36,00 € ročne
                            </span>
                        </div>
                    </div>
                    
                    <hr style="border:none; border-top: 1px solid var(--border-color); margin: 0 0 18px 0;">
                    
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 2px;">Čo je v balíku</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>Neobmedzené rezervácie</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>10 zamestnancov v cene</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>2 pobočky v cene</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>Všetko z PRO</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>Vernostný program pre zákazníkov</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>Exporty pre účtovníka</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>Rozšírenia a API integrácie</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>Prioritná zákaznícka podpora</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">check_circle</span><span>Inzercia · 6 inzerátov mesačne zadarmo · ďalšie 0,50 € z Peňaženky</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:var(--primary-color);flex-shrink:0;margin-top:1px;">auto_awesome</span><span>150 AI kreditov mesačne</span></div>
                    </div>

                    <div id="btn-container-vip" style="margin-top: 25px;">
                        <button type="button" id="btn-tier-vip" onclick="promptChangeTier('vip')" class="btn" style="width:100%; border-radius: 12px; background: linear-gradient(135deg, #b08042, #d4af37); border: none; color: #fff; padding: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 14px rgba(176, 128, 66, 0.4);" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">Vybrať VIP</button>
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--text-secondary);">sms</span><span style="font-size:12px;color:var(--text-secondary);">Zasielanie SMS — pripravujeme</span></div>
                    </div>
                </div>

                <!-- 5. ENTERPRISE (Tmavá / Individuálna) -->
                <div id="card-tier-enterprise" class="pricing-card card-enterprise" style="background: var(--card-bg); border: 2px solid #0f172a; border-radius: 16px; padding: 28px; display: flex; flex-direction: column; text-align: left; transition: all 0.3s ease; position: relative; box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.3);">
                    <div id="ribbon-tier-enterprise" class="tier-active-ribbon" style="display: none; position: absolute; top: -13px; right: 18px; background: #10b981; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 12px; border-radius: 8px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); align-items: center; gap: 4px; z-index: 2;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> VÁŠ AKTÍVNY BALÍK
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h3 style="color: #0f172a; margin: 0; font-size: 20px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 22px; color: #0f172a;">corporate_fare</span>
                            ENTERPRISE
                        </h3>
                        <span style="font-size: 11px; font-weight: 700; color: #0f172a; background: rgba(15, 23, 42, 0.1); padding: 3px 8px; border-radius: 6px;">NA MIERU</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-secondary); min-height: 38px; margin: 4px 0 15px 0; line-height: 1.4;">Pre sieť prevádzok, franšízy a veľké organizácie s individuálnymi požiadavkami.</p>

                    <div style="margin: 0 0 15px 0;">
                        <p style="font-size: 34px; font-weight: 800; margin: 0; color: var(--text-primary); line-height: 1;">Individuálne</p>
                        <div class="savings-tag" style="min-height: 22px; margin-top: 6px; font-size: 12px; color: var(--text-secondary);">
                            <span style="display:inline-flex; align-items: center; gap: 4px; color: #0f172a; font-weight: 700;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">handshake</span> Cena podľa rozsahu a počtu pobočiek
                            </span>
                        </div>
                    </div>

                    <hr style="border:none; border-top: 1px solid var(--border-color); margin: 0 0 18px 0;">

                    <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 2px;">Čo je v balíku</div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>Neobmedzené rezervácie a pobočky</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>Neobmedzení zamestnanci a správcovia</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>Všetko z VIP balíka</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>Dedikovaný account manager</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>SLA zmluva a prioritná podpora 24/7</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>Vlastné integrácie a API prístup</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>White-label možnosti</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">check_circle</span><span>Školenie a onboarding tímu</span></div>
                        <div style="display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text-primary);"><span class="material-symbols-outlined" style="font-size:17px;color:#0f172a;flex-shrink:0;margin-top:1px;">auto_awesome</span><span>AI kredity podľa potreby</span></div>
                    </div>

                    <div id="btn-container-enterprise" style="margin-top: 25px;">
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:10px;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--text-secondary);">schedule</span><span style="font-size:12px;color:var(--text-secondary);">Individuálna ponuka do 48 hodín</span></div>
                        <a href="mailto:info@rezervos.sk?subject=Enterprise dopyt" class="btn" style="width:100%; border-radius: 12px; background: #0f172a; border: none; color: #fff; padding: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; box-sizing: border-box;" onmouseover="this.style.opacity='0.85';" onmouseout="this.style.opacity='1';">
                            <span class="material-symbols-outlined" style="font-size: 18px;">mail</span>
                            Kontaktujte nás
                        </a>
                    </div>
                </div>

            </div>

            <!-- Doplnkové balíčky a služby -->
            <div style="margin-top: 40px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-outlined" style="color: var(--primary-color);">extension</span> Doplnkové balíčky a služby
                        </h3>
                        <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Rozšírte si aktuálny balík alebo si dokúpte služby podľa potreby. Ceny sú uvedené bez DPH.</p>
                    </div>
                    <button type="button" id="btn-open-cart" onclick="openAddonCart()" style="position:relative;display:inline-flex;align-items:center;gap:8px;background:var(--card-bg);border:1.5px solid var(--border-color);border-radius:12px;padding:10px 16px;font-weight:700;font-size:13px;color:var(--text-primary);cursor:pointer;flex-shrink:0;">
                        <span class="material-symbols-outlined" style="font-size:20px;color:var(--primary-color);">shopping_cart</span>
                        Košík
                        <span id="addon-cart-badge" style="display:none;position:absolute;top:-7px;right:-7px;background:#ef4444;color:#fff;font-size:11px;font-weight:800;min-width:19px;height:19px;border-radius:10px;align-items:center;justify-content:center;padding:0 4px;">0</span>
                    </button>
                </div>

                <div style="text-align:center;margin:18px 0 4px 0;">
                    <span style="font-size:12px;color:var(--text-secondary);display:inline-flex;align-items:center;gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:15px;">sync</span>
                        Predplatné doplnky sa fakturujú v rovnakom cykle ako váš balík (prepínač Mesačne / Ročne vyššie)
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; margin-top: 12px;">

                    <div class="addon-card" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px; display:flex; flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="material-symbols-outlined" style="color:#3b82f6;">person_add</span><strong style="font-size:14px;color:var(--text-primary);">Ďalší zamestnanec</strong></div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px 0;">Pridajte ďalších ľudí do vášho tímu nad rámec limitu balíka.</p>
                        <div style="font-size:18px;font-weight:800;color:var(--text-primary);"><span class="addon-price-val" data-monthly="4.90" data-yearly="3.90">3.90</span> € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ mesiac</span></div>
                        <div class="addon-tag-monthly" style="display:none;font-size:11.5px;color:var(--text-secondary);margin-top:2px;">Bez záväzkov · Platba každý mesiac</div>
                        <div class="addon-tag-yearly" style="font-size:12px;color:#10b981;font-weight:700;margin-top:2px;">Fakturované ročne 46,80 € · ušetríte 12 € ročne</div>
                        <button type="button" class="btn" onclick="addToCart('employee')" style="margin-top:12px;width:100%;border-radius:10px;background:var(--primary-color);border:none;color:#fff;padding:10px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                            <span class="material-symbols-outlined" style="font-size:17px;">add_shopping_cart</span> Pridať do košíka
                        </button>
                    </div>

                    <div class="addon-card" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px; display:flex; flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="material-symbols-outlined" style="color:#3b82f6;">store</span><strong style="font-size:14px;color:var(--text-primary);">Ďalšia pobočka</strong></div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px 0;">Spravujte viac pobočiek z jedného účtu.</p>
                        <div style="font-size:18px;font-weight:800;color:var(--text-primary);"><span class="addon-price-val" data-monthly="9.90" data-yearly="8.90">8.90</span> € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ mesiac</span></div>
                        <div class="addon-tag-monthly" style="display:none;font-size:11.5px;color:var(--text-secondary);margin-top:2px;">Bez záväzkov · Platba každý mesiac</div>
                        <div class="addon-tag-yearly" style="font-size:12px;color:#10b981;font-weight:700;margin-top:2px;">Fakturované ročne 106,80 € · ušetríte 12 € ročne</div>
                        <button type="button" class="btn" onclick="addToCart('branch')" style="margin-top:12px;width:100%;border-radius:10px;background:var(--primary-color);border:none;color:#fff;padding:10px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                            <span class="material-symbols-outlined" style="font-size:17px;">add_shopping_cart</span> Pridať do košíka
                        </button>
                    </div>

                    <div class="addon-card" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px; display:flex; flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="material-symbols-outlined" style="color:#8b5cf6;">sms</span><strong style="font-size:14px;color:var(--text-primary);">SMS správy</strong></div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px 0;">Platíte len za odoslanú SMS z predplateného kreditu.</p>
                        <div style="font-size:14px;font-weight:800;color:var(--text-primary);">podľa spotreby</div>
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">Dobitie kreditu: 5 €, 10 €, 20 €, 50 €, 100 €</div>
                        <div style="margin-top:12px;display:inline-flex;align-items:center;justify-content:center;gap:6px;background:rgba(100,116,139,0.12);color:var(--text-secondary);font-size:12.5px;font-weight:700;padding:9px 12px;border-radius:10px;">
                            <span class="material-symbols-outlined" style="font-size:16px;">schedule</span> Čoskoro dostupné
                        </div>
                    </div>

                    <div class="addon-card" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px; display:flex; flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="material-symbols-outlined" style="color:#8b5cf6;">credit_card</span><strong style="font-size:14px;color:var(--text-primary);">Online platby a zálohy</strong></div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px 0;">Prijímajte platby a zálohy online od svojich klientov.</p>
                        <div style="font-size:14px;font-weight:800;color:var(--text-primary);">transakčný poplatok</div>
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">Záloha v € aj v %, 100 % platba, storno poplatky, refundácie</div>
                        <div style="margin-top:12px;display:inline-flex;align-items:center;justify-content:center;gap:6px;background:rgba(100,116,139,0.12);color:var(--text-secondary);font-size:12.5px;font-weight:700;padding:9px 12px;border-radius:10px;">
                            <span class="material-symbols-outlined" style="font-size:16px;">schedule</span> Čoskoro dostupné
                        </div>
                    </div>

                    <div class="addon-card" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px; display:flex; flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="material-symbols-outlined" style="color:#ea580c;">campaign</span><strong style="font-size:14px;color:var(--text-primary);"><?= BRAND_NAME ?> inzercia</strong></div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px 0;">Zverejňujte pracovné ponuky, prenájmy a ďalšie inzeráty.</p>
                        <div style="font-size:18px;font-weight:800;color:var(--text-primary);">0,50 € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ týždeň</span></div>
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">alebo 1,50 €/mesiac · prvý firemný inzerát/mesiac zadarmo po overení IČO · platba vždy reálnymi prostriedkami (nie z nazbieraných kreditov), pokiaľ nie je zahrnuté v balíku</div>
                        <a href="dashboard-inzercia.php" class="btn" style="margin-top:12px;width:100%;border-radius:10px;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-primary);padding:10px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;box-sizing:border-box;">
                            <span class="material-symbols-outlined" style="font-size:17px;">arrow_forward</span> Spravovať inzeráty
                        </a>
                    </div>

                    <div class="addon-card" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px; display:flex; flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="material-symbols-outlined" style="color:#ea580c;">trending_up</span><strong style="font-size:14px;color:var(--text-primary);">TOP a zvýraznenie</strong></div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px 0;">Zvýraznite svoj inzerát na VoľnomKresle. Možno platiť aj z nazbieraných kreditov v Peňaženke.</p>
                        <div style="font-size:12px;color:var(--text-primary);">TOP <strong>+0,50 €</strong> · Zvýraznenie <strong>+0,50 €</strong></div>
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">Oboje spolu v košíku len 0,90 €</div>
                        <div style="display:flex;gap:8px;margin-top:12px;">
                            <button type="button" onclick="addToCart('ad_top')" style="flex:1;border-radius:10px;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-primary);padding:9px;font-weight:700;font-size:12px;cursor:pointer;">+ TOP</button>
                            <button type="button" onclick="addToCart('ad_highlight')" style="flex:1;border-radius:10px;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-primary);padding:9px;font-weight:700;font-size:12px;cursor:pointer;">+ Zvýraznenie</button>
                        </div>
                    </div>

                    <div class="addon-card" style="background: linear-gradient(135deg, rgba(176,128,66,0.06), rgba(176,128,66,0.02)); border: 1.5px solid rgba(176,128,66,0.3); border-radius: 14px; padding: 18px; display:flex; flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">bolt</span><strong style="font-size:14px;color:var(--text-primary);">AI kredity</strong></div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px 0;">Dokúpte si ďalšie AI kredity pre AI asistenta v e-mailoch a marketingových kampaniach (gramatika, tón, tvorba textu).</p>
                        <div style="font-size:12px;color:var(--text-secondary);margin-bottom:8px;">Aktuálny zostatok: <strong id="addon-ai-credits-count" style="color:var(--text-primary);">…</strong></div>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <button type="button" onclick="buyAiCreditsAddon(50)" style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border:1px solid var(--border-color);border-radius:8px;background:var(--card-bg);cursor:pointer;">
                                <span style="font-weight:700;font-size:12.5px;color:var(--text-primary);">50 kreditov</span>
                                <span style="font-weight:800;font-size:13px;color:var(--primary-color);">3,00 €</span>
                            </button>
                            <button type="button" onclick="buyAiCreditsAddon(100)" style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border:1px solid var(--border-color);border-radius:8px;background:var(--card-bg);cursor:pointer;">
                                <span style="font-weight:700;font-size:12.5px;color:var(--text-primary);">100 kreditov</span>
                                <span style="font-weight:800;font-size:13px;color:var(--primary-color);">5,00 €</span>
                            </button>
                            <button type="button" onclick="buyAiCreditsAddon(250)" style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border:1px solid var(--border-color);border-radius:8px;background:var(--card-bg);cursor:pointer;">
                                <span style="font-weight:700;font-size:12.5px;color:var(--text-primary);">250 kreditov</span>
                                <span style="font-weight:800;font-size:13px;color:var(--primary-color);">10,00 €</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Košík doplnkov: overlay + bočný panel -->
            <div id="addon-cart-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9998;" onclick="closeAddonCart()"></div>
            <div id="addon-cart-drawer" style="display:none;position:fixed;top:0;right:-420px;width:100%;max-width:400px;height:100vh;background:var(--card-bg);z-index:9999;box-shadow:-10px 0 30px rgba(0,0,0,0.25);transition:right 0.28s cubic-bezier(0.4,0,0.2,1);flex-direction:column;box-sizing:border-box;">
                <div style="padding:20px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
                    <h3 style="margin:0;font-size:17px;font-weight:800;color:var(--text-primary);display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined">shopping_cart</span> Košík doplnkov</h3>
                    <button type="button" onclick="closeAddonCart()" style="background:none;border:none;cursor:pointer;color:var(--text-secondary);display:flex;"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div id="addon-cart-items" style="flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:10px;"></div>
                <div style="padding:18px 20px;border-top:1px solid var(--border-color);flex-shrink:0;box-sizing:border-box;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <span style="font-size:14px;color:var(--text-secondary);font-weight:600;">Spolu k úhrade:</span>
                        <strong id="addon-cart-total" style="font-size:20px;font-weight:800;color:var(--primary-color);">0,00 €</strong>
                    </div>
                    <button type="button" id="btn-addon-checkout" onclick="checkoutAddonCart()" class="btn" style="width:100%;border-radius:12px;background:var(--primary-color);border:none;color:#fff;padding:12px;font-weight:800;cursor:pointer;">Objednať</button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Vlastný dizajnový modál na potvrdenie zmeny / aktivácie balíka (Rule 6: Žiadne natívne okná) -->
<div id="modal-confirm-tier" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 20px; max-width: 460px; width: 100%; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); position: relative; text-align: center; animation: modalFadeIn 0.25s ease;">
        
        <div id="modal-tier-icon-wrap" style="width: 60px; height: 60px; border-radius: 16px; background: var(--primary-color); color: #fff; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 6px 16px rgba(176, 128, 66, 0.35);">
            <span id="modal-tier-icon" class="material-symbols-outlined" style="font-size: 32px;">workspace_premium</span>
        </div>

        <h3 id="modal-tier-heading" style="margin: 0 0 8px 0; font-size: 22px; font-weight: 800; color: var(--text-primary);">Aktivácia balíka VIP</h3>
        <p id="modal-tier-desc" style="font-size: 14px; color: var(--text-secondary); margin: 0 0 20px 0; line-height: 1.5;">
            Chystáte sa aktivovať predplatné pre vašu prevádzku.
        </p>

        <!-- Detaily ceny -->
        <div style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; margin-bottom: 22px; text-align: left;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 13.5px; color: var(--text-secondary);">Zvolený balík:</span>
                <strong id="modal-tier-name" style="font-size: 14.5px; color: var(--text-primary);">VIP (ELITE)</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 13.5px; color: var(--text-secondary);">Fakturačné obdobie:</span>
                <strong id="modal-tier-period" style="font-size: 13.5px; color: var(--text-primary);">Mesačne</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="font-size: 14px; font-weight: 700; color: var(--text-primary);">Cena k úhrade:</span>
                <strong id="modal-tier-price" style="font-size: 18px; font-weight: 800; color: var(--primary-color);">14.90 €</strong>
            </div>
        </div>

        <!-- Akčné tlačidlá -->
        <div style="display: flex; gap: 12px;">
            <button type="button" onclick="closeConfirmTierModal()" class="btn" style="flex: 1; padding: 12px; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-secondary); font-weight: 700; cursor: pointer;">
                Zrušiť
            </button>
            <button type="button" id="btn-confirm-tier-action" onclick="executeTierChange()" class="btn" style="flex: 1.3; padding: 12px; border-radius: 12px; background: var(--primary-color); border: none; color: #fff; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(176, 128, 66, 0.4);">
                Potvrdiť a aktivovať
            </button>
        </div>
    </div>
</div>

<style>
@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.pricing-card:hover {
    transform: translateY(-5px);
}
.pricing-card.is-active-tier {
    box-shadow: 0 0 0 3px #10b981, 0 14px 35px -8px rgba(16, 185, 129, 0.4) !important;
}
.card-free:hover {
    box-shadow: 0 12px 25px -8px rgba(100, 116, 139, 0.25);
}
.card-start:hover {
    box-shadow: 0 12px 28px -8px rgba(59, 130, 246, 0.35);
}
.card-pro:hover {
    box-shadow: 0 14px 35px -8px rgba(139, 92, 246, 0.45);
}
.card-vip:hover {
    box-shadow: 0 14px 35px -8px rgba(176, 128, 66, 0.45);
}
</style>

<script>
let g_currentBillingPeriod = 'yearly';
let g_selectedTargetTier = null;

function toggleBilling(period) {
    g_currentBillingPeriod = period;
    const btnMonthly = document.getElementById('btn-monthly');
    const btnYearly = document.getElementById('btn-yearly');
    const iconMonthly = btnMonthly ? btnMonthly.querySelector('.material-symbols-outlined') : null;
    const iconYearly = document.getElementById('icon-yearly');
    const badgeDiscount = document.getElementById('badge-yearly-discount');
    const priceVals = document.querySelectorAll('.price-val');
    const pricePeriods = document.querySelectorAll('.price-period-label');
    const tagsMonthly = document.querySelectorAll('.tag-monthly');
    const tagsYearly = document.querySelectorAll('.tag-yearly');
    
    if (period === 'monthly') {
        if (btnMonthly) {
            btnMonthly.style.background = 'var(--primary-color)';
            btnMonthly.style.borderColor = 'var(--primary-color)';
            btnMonthly.style.color = '#ffffff';
            btnMonthly.style.boxShadow = '0 4px 14px rgba(176, 128, 66, 0.35)';
        }
        if (iconMonthly) iconMonthly.style.color = '#ffffff';
        
        if (btnYearly) {
            btnYearly.style.background = 'transparent';
            btnYearly.style.borderColor = 'transparent';
            btnYearly.style.color = 'var(--text-secondary)';
            btnYearly.style.boxShadow = 'none';
        }
        if (iconYearly) iconYearly.style.color = '#10b981';
        if (badgeDiscount) {
            badgeDiscount.style.background = 'rgba(16, 185, 129, 0.15)';
            badgeDiscount.style.borderColor = 'rgba(16, 185, 129, 0.35)';
            badgeDiscount.style.color = '#10b981';
        }
        
        priceVals.forEach(el => {
            el.innerText = el.getAttribute('data-monthly');
        });
        pricePeriods.forEach(el => {
            el.innerText = ' / mesiac';
        });
        tagsMonthly.forEach(el => el.style.display = 'block');
        tagsYearly.forEach(el => el.style.display = 'none');
    } else {
        if (btnYearly) {
            btnYearly.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            btnYearly.style.borderColor = '#10b981';
            btnYearly.style.color = '#ffffff';
            btnYearly.style.boxShadow = '0 4px 15px rgba(16, 185, 129, 0.4)';
        }
        if (iconYearly) iconYearly.style.color = '#ffffff';
        if (badgeDiscount) {
            badgeDiscount.style.background = 'rgba(255, 255, 255, 0.25)';
            badgeDiscount.style.borderColor = 'rgba(255, 255, 255, 0.5)';
            badgeDiscount.style.color = '#ffffff';
        }
        
        if (btnMonthly) {
            btnMonthly.style.background = 'transparent';
            btnMonthly.style.borderColor = 'transparent';
            btnMonthly.style.color = 'var(--text-secondary)';
            btnMonthly.style.boxShadow = 'none';
        }
        if (iconMonthly) iconMonthly.style.color = 'var(--text-secondary)';
        
        priceVals.forEach(el => {
            el.innerText = el.getAttribute('data-yearly');
        });
        pricePeriods.forEach(el => {
            el.innerText = ' / mesiac';
        });
        tagsMonthly.forEach(el => el.style.display = 'none');
        tagsYearly.forEach(el => {
            const pv = el.closest('.savings-tag') ? el.closest('.savings-tag').previousElementSibling : null;
            const priceEl = el.closest('[id^="status-line-"]')
                ? el.closest('[id^="status-line-"]').previousElementSibling.querySelector('.price-val')
                : null;
            if (priceEl) {
                const total = priceEl.getAttribute('data-yearly-total');
                const monthly = priceEl.getAttribute('data-monthly');
                const yearly = parseFloat(priceEl.getAttribute('data-yearly'));
                const savings = ((parseFloat(monthly) - yearly) * 12).toFixed(0);
                el.innerHTML = '<span class="material-symbols-outlined" style="font-size:15px;">savings</span> Fakturovaných ' + total + ' € ročne &nbsp;·&nbsp; Ušetríte ' + savings + ' €';
            }
            el.style.display = 'inline-flex';
        });
    }

    // Doplnkové karty (jednoduchý mesačný/ročný ekvivalent, bez zložitého prepočtu)
    document.querySelectorAll('.addon-price-val').forEach(el => {
        el.innerText = el.getAttribute(period === 'monthly' ? 'data-monthly' : 'data-yearly');
    });
    document.querySelectorAll('.addon-tag-monthly').forEach(el => el.style.display = (period === 'monthly') ? 'block' : 'none');
    document.querySelectorAll('.addon-tag-yearly').forEach(el => el.style.display = (period === 'monthly') ? 'none' : 'block');
    renderAddonCart();
}

/**
 * Formátovanie dátumu na slovenský formát napr. "19. 09. 2026"
 */
function formatSlovakDate(dateStr) {
    if (!dateStr) return null;
    try {
        const d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dateStr;
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}. ${month}. ${year}`;
    } catch(e) {
        return dateStr;
    }
}

/**
 * Výpočet počtu zostávajúcich dní
 */
function getDaysRemaining(dateStr) {
    if (!dateStr) return null;
    try {
        const target = new Date(dateStr.replace(' ', 'T')).getTime();
        const now = new Date().getTime();
        const diffDays = Math.ceil((target - now) / (1000 * 60 * 60 * 24));
        return Math.max(0, diffDays);
    } catch(e) {
        return null;
    }
}

/**
 * Aktualizácia kompletného zobrazenia balíkov podľa aktuálneho predplatného
 */
function updateBillingUI(tier, expiresAt, period) {
    tier = (tier || 'free').toLowerCase();
    if (tier === 'premium') tier = 'vip'; // alias
    
    const formattedDate = formatSlovakDate(expiresAt);
    const daysLeft = getDaysRemaining(expiresAt);

    // 1. Aktualizácia horného súhrnného panelu
    const summaryBadge = document.getElementById('summary-tier-badge');
    const summaryTitle = document.getElementById('summary-tier-title');
    const summaryExpiryDate = document.getElementById('summary-expiry-date');
    const summaryExpiryDays = document.getElementById('summary-expiry-days');
    const summaryIconWrap = document.getElementById('summary-tier-icon-wrap');
    const summaryIcon = document.getElementById('summary-tier-icon');

    const tierMeta = {
        'free': { name: 'FREE (Základ)', title: 'Balík FREE', icon: 'eco', color: '#64748b', bg: 'rgba(100, 116, 139, 0.2)' },
        'start': { name: 'START (Basic)', title: 'Balík START', icon: 'rocket_launch', color: '#3b82f6', bg: 'rgba(59, 130, 246, 0.2)' },
        'pro': { name: 'PRO (Odporúčaný)', title: 'Balík PRO', icon: 'group', color: '#8b5cf6', bg: 'rgba(139, 92, 246, 0.2)' },
        'vip': { name: 'VIP (ELITE)', title: 'Balík VIP', icon: 'workspace_premium', color: '#b08042', bg: 'rgba(176, 128, 66, 0.2)' }
    };

    const currentMeta = tierMeta[tier] || tierMeta['free'];

    if (summaryBadge) {
        summaryBadge.innerText = currentMeta.name;
        summaryBadge.style.background = currentMeta.bg;
        summaryBadge.style.color = currentMeta.color;
    }
    if (summaryTitle) {
        summaryTitle.innerText = currentMeta.title;
    }
    if (summaryIconWrap && summaryIcon) {
        summaryIconWrap.style.background = currentMeta.color;
        summaryIcon.innerText = currentMeta.icon;
    }

    if (summaryExpiryDate) {
        if (tier === 'free') {
            summaryExpiryDate.innerText = 'Trvalý bezplatný prístup';
            if (summaryExpiryDays) summaryExpiryDays.innerText = '(bez obmedzenia)';
        } else {
            summaryExpiryDate.innerText = formattedDate || 'Aktívne predplatné';
            if (summaryExpiryDays) {
                if (daysLeft !== null) {
                    summaryExpiryDays.innerText = `(zostáva ${daysLeft} ${daysLeft === 1 ? 'deň' : (daysLeft >= 2 && daysLeft <= 4 ? 'dni' : 'dní')})`;
                } else {
                    summaryExpiryDays.innerText = '';
                }
            }
        }
    }

    // 2. Aktualizácia všetkých 4 kariet
    const allTiers = ['free', 'start', 'pro', 'vip'];

    allTiers.forEach(t => {
        const card = document.getElementById(`card-tier-${t}`);
        const ribbon = document.getElementById(`ribbon-tier-${t}`);
        const btnContainer = document.getElementById(`btn-container-${t}`);
        const statusLine = document.getElementById(`status-line-${t}`);

        const isActive = (t === tier);

        if (card) {
            if (isActive) {
                card.classList.add('is-active-tier');
            } else {
                card.classList.remove('is-active-tier');
            }
        }

        if (ribbon) {
            ribbon.style.display = 'none';
        }

        if (btnContainer) {
            if (isActive) {
                btnContainer.innerHTML = `
                    <button type="button" style="width:100%; border-radius: 12px; background: #10b981; border: none; color: #ffffff; padding: 12px; font-weight: 800; cursor: default; display: flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit; font-size: 14px;" disabled>
                        <span class="material-symbols-outlined" style="font-size: 18px;">check_circle</span>
                        Váš aktuálny balík
                    </button>
                `;
            } else {
                let btnText = (t === 'free') ? 'Prejsť na FREE' : `Vybrať ${t.toUpperCase()}`;
                let btnBg = (t === 'free') ? 'var(--input-bg)' : ((t === 'start') ? '#3b82f6' : ((t === 'pro') ? 'linear-gradient(135deg, #8b5cf6, #6366f1)' : 'linear-gradient(135deg, #b08042, #d4af37)'));
                let btnColor = (t === 'free') ? 'var(--text-secondary)' : '#ffffff';
                let btnBorder = (t === 'free') ? '1px solid var(--border-color)' : 'none';
                let btnShadow = (t === 'free') ? 'none' : '0 4px 14px rgba(0,0,0,0.15)';

                btnContainer.innerHTML = `
                    <button type="button" onclick="promptChangeTier('${t}')" class="btn" style="width:100%; border-radius: 12px; background: ${btnBg}; border: ${btnBorder}; color: ${btnColor}; padding: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; box-shadow: ${btnShadow};">
                        ${btnText}
                    </button>
                `;
            }
        }

        // Informácia o dátume priamo na aktívnej karte
        if (statusLine && isActive) {
            if (t === 'free') {
                statusLine.innerHTML = `<span style="color: #10b981; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 16px;">verified</span> Váš aktívny balík (Trvalý prístup)</span>`;
            } else {
                statusLine.innerHTML = `
                    <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; font-weight: 700; font-size: 12px; padding: 4px 8px; border-radius: 7px; display: inline-flex; align-items: center; gap: 5px;">
                        <span class="material-symbols-outlined" style="font-size: 15px;">event_available</span>
                        Platný do: <strong>${formattedDate || '30 dní'}</strong>
                    </div>
                `;
            }
        }
    });
}

/**
 * Otvorenie potvrdzovacieho modálu pre zmenu balíka
 */
function promptChangeTier(targetTier) {
    g_selectedTargetTier = targetTier;
    const modal = document.getElementById('modal-confirm-tier');
    if (!modal) return;

    const prices = {
        'free': { monthly: '0.00 €', yearly: '0.00 €', name: 'FREE (Základ)', desc: 'Prechod na základný bezplatný balík. Niektoré prémiové funkcie (napr. vlastné logo, banner, viac zamestnancov) budú obmedzené.', color: '#64748b', icon: 'eco' },
        'start': { monthly: '6.90 € / mesiac', yearly: '70.80 € / rok (ušetríš 12 €)', name: 'START (Basic)', desc: 'Aktivácia balíka START s vlastným logom, 300 rezerváciami mesačne a recenziami.', color: '#3b82f6', icon: 'rocket_launch' },
        'pro': { monthly: '14.90 € / mesiac', yearly: '154.80 € / rok (ušetríš 24 €)', name: 'PRO (Odporúčaný)', desc: 'Aktivácia balíka PRO s titulným bannerom, tímom 1-3 zamestnancov a Last Minute ponukami.', color: '#8b5cf6', icon: 'group' },
        'vip': { monthly: '29.90 € / mesiac', yearly: '322.80 € / rok (ušetríš 36 €)', name: 'VIP (ELITE)', desc: 'Aktivácia balíka VIP s neobmedzeným počtom rezervácií, vernostným programom a prednostným VIP zobrazením.', color: '#b08042', icon: 'workspace_premium' }
    };

    const p = prices[targetTier] || prices['free'];
    const periodName = (g_currentBillingPeriod === 'yearly') ? 'Ročná platba' : 'Mesačná platba';
    const priceVal = (g_currentBillingPeriod === 'yearly') ? p.yearly : p.monthly;

    document.getElementById('modal-tier-heading').innerText = (targetTier === 'free') ? 'Prechod na balík FREE' : `Aktivácia balíka ${targetTier.toUpperCase()}`;
    document.getElementById('modal-tier-desc').innerText = p.desc;
    document.getElementById('modal-tier-name').innerText = p.name;
    document.getElementById('modal-tier-period').innerText = periodName;
    document.getElementById('modal-tier-price').innerText = priceVal;
    
    const iconWrap = document.getElementById('modal-tier-icon-wrap');
    const iconSpan = document.getElementById('modal-tier-icon');
    if (iconWrap && iconSpan) {
        iconWrap.style.background = p.color;
        iconSpan.innerText = p.icon;
    }

    modal.style.display = 'flex';
}

async function redeemPromoCodeBox() {
    const input = document.getElementById('promo-code-input');
    const code = input ? input.value.trim() : '';
    if (!code) { showAppToast('Zadajte promo kód.', 'error'); return; }
    try {
        const fd = new FormData();
        fd.append('action', 'redeem_promo_code');
        fd.append('code', code);
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAppToast(data.message, 'success', 6000);
            input.value = '';
            setTimeout(() => window.location.reload(), 1800);
        } else {
            showAppToast(data.message || 'Chyba pri uplatňovaní kódu.', 'error');
        }
    } catch (e) {
        showAppToast('Chyba pripojenia.', 'error');
    }
}

async function generateReferralCode() {
    const btn = document.getElementById('btn-generate-referral');
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'get_or_create_referral_code');
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            document.getElementById('referral-code-value').innerText = data.code;
            document.getElementById('referral-code-display').style.display = 'inline-flex';
            btn.style.display = 'none';
        } else {
            showAppToast(data.message || 'Chyba pri generovaní kódu.', 'error');
        }
    } catch (e) {
        showAppToast('Chyba pripojenia.', 'error');
    }
    btn.disabled = false;
}

function copyReferralCode() {
    const code = document.getElementById('referral-code-value').innerText;
    navigator.clipboard.writeText(code).then(() => {
        showAppToast('Kód "' + code + '" bol skopírovaný.', 'success');
    }).catch(() => {
        showAppToast('Kopírovanie zlyhalo, skopírujte kód ručne.', 'error');
    });
}

function closeConfirmTierModal() {
    const modal = document.getElementById('modal-confirm-tier');
    if (modal) modal.style.display = 'none';
    g_selectedTargetTier = null;
}

/**
 * Vykonanie zmeny balíka cez API
 */
async function executeTierChange() {
    if (!g_selectedTargetTier) return;
    const btn = document.getElementById('btn-confirm-tier-action');
    const origText = btn.innerText;
    btn.disabled = true;

    // Prechod na FREE nevyžaduje platbu, ide priamo
    if (g_selectedTargetTier === 'free') {
        btn.innerText = 'Aktivujem...';
        try {
            const fd = new FormData();
            fd.append('action', 'change_tier');
            fd.append('tier', 'free');
            fd.append('period', g_currentBillingPeriod);

            const res = await fetch('api/business.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                showAppToast(data.message || 'Balík bol úspešne zmenený!', 'success');
                closeConfirmTierModal();
                updateBillingUI(data.tier, data.expires_at, data.period);
                setTimeout(() => window.location.reload(), 1800);
            } else {
                showAppToast(data.message || 'Chyba pri zmene balíka.', 'error');
            }
        } catch (e) {
            console.error('Chyba zmeny balíka:', e);
            showAppToast('Chyba spojenia so serverom.', 'error');
        } finally {
            btn.innerText = origText;
            btn.disabled = false;
        }
        return;
    }

    // Platené balíky (START/PRO/VIP) idú cez Stripe Checkout — presmerujeme na platobnú bránu,
    // balík sa aktivuje až po potvrdenej platbe (viď api/stripe_webhook.php)
    btn.innerText = 'Presmerúvam na platbu...';
    try {
        const fd = new FormData();
        fd.append('action', 'create_subscription_session');
        fd.append('tier', g_selectedTargetTier);
        fd.append('period', g_currentBillingPeriod);

        const res = await fetch('api/stripe_checkout.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            showAppToast(data.message || 'Chyba pri otváraní platby.', 'error');
            btn.innerText = origText;
            btn.disabled = false;
        }
    } catch (e) {
        console.error('Chyba Stripe Checkout:', e);
        showAppToast('Chyba spojenia so serverom.', 'error');
        btn.innerText = origText;
        btn.disabled = false;
    }
}

/* ========================= Košík doplnkov ========================= */

const ADDON_META = {
    employee:     { label: 'Ďalší zamestnanec',     icon: 'person_add',      hasPeriod: true,  monthly: 4.90, yearly: 46.80, earnedOk: false },
    branch:       { label: 'Ďalšia pobočka',         icon: 'store',           hasPeriod: true,  monthly: 9.90, yearly: 106.80, earnedOk: false },
    ad_top:       { label: 'TOP inzerátu',           icon: 'trending_up',     hasPeriod: false, flat: 0.50,  earnedOk: true },
    ad_highlight: { label: 'Zvýraznenie inzerátu',   icon: 'trending_up',     hasPeriod: false, flat: 0.50,  earnedOk: true }
};

let g_addonCart = []; // pole { key }

function priceOfAddon(key) {
    const meta = ADDON_META[key];
    if (!meta) return 0;
    if (meta.hasPeriod) return (g_currentBillingPeriod === 'yearly') ? meta.yearly : meta.monthly;
    return meta.flat;
}

function computeAddonCartTotal() {
    let total = 0;
    g_addonCart.forEach(item => total += priceOfAddon(item.key));
    const hasTop = g_addonCart.some(i => i.key === 'ad_top');
    const hasHighlight = g_addonCart.some(i => i.key === 'ad_highlight');
    if (hasTop && hasHighlight) total -= 0.10; // kombo zľava: spolu 0,90 € namiesto 1,00 €
    return Math.max(0, total);
}

function addToCart(key) {
    if (!ADDON_META[key]) return;
    if (g_addonCart.find(i => i.key === key)) {
        showAppToast('Táto položka je už v košíku.', 'info');
        openAddonCart();
        return;
    }
    g_addonCart.push({ key });
    renderAddonCart();
    openAddonCart();
}

function removeFromCart(key) {
    g_addonCart = g_addonCart.filter(i => i.key !== key);
    renderAddonCart();
}

function renderAddonCart() {
    const badge = document.getElementById('addon-cart-badge');
    if (badge) {
        badge.innerText = g_addonCart.length;
        badge.style.display = g_addonCart.length > 0 ? 'flex' : 'none';
    }
    const box = document.getElementById('addon-cart-items');
    if (box) {
        if (g_addonCart.length === 0) {
            box.innerHTML = '<div style="text-align:center;color:var(--text-secondary);font-size:13px;padding:30px 0;">Košík je prázdny.</div>';
        } else {
            box.innerHTML = g_addonCart.map(item => {
                const meta = ADDON_META[item.key];
                const price = priceOfAddon(item.key).toFixed(2).replace('.', ',');
                const periodLabel = meta.hasPeriod ? (g_currentBillingPeriod === 'yearly' ? '/ rok (fakturované ročne)' : '/ mesiac') : (item.key === 'vip_member' ? '/ rok' : 'jednorazovo');
                return `<div style="display:flex;align-items:center;gap:10px;background:var(--input-bg);border:1px solid var(--border-color);border-radius:12px;padding:10px 12px;">
                    <span class="material-symbols-outlined" style="color:var(--primary-color);">${meta.icon}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:700;color:var(--text-primary);">${meta.label}</div>
                        <div style="font-size:11.5px;color:var(--text-secondary);">${price} € ${periodLabel}${meta.earnedOk ? ' · možno z nazbieraných kreditov' : ' · reálne prostriedky'}</div>
                    </div>
                    <button type="button" onclick="removeFromCart('${item.key}')" style="background:none;border:none;cursor:pointer;color:#ef4444;display:flex;"><span class="material-symbols-outlined" style="font-size:18px;">delete</span></button>
                </div>`;
            }).join('');
        }
    }
    const totalEl = document.getElementById('addon-cart-total');
    if (totalEl) totalEl.innerText = computeAddonCartTotal().toFixed(2).replace('.', ',') + ' €';
}

function openAddonCart() {
    const overlay = document.getElementById('addon-cart-overlay');
    const drawer = document.getElementById('addon-cart-drawer');
    if (overlay) overlay.style.display = 'block';
    if (drawer) {
        drawer.style.display = 'flex';
        requestAnimationFrame(() => { drawer.style.right = '0'; });
    }
}

function closeAddonCart() {
    const overlay = document.getElementById('addon-cart-overlay');
    const drawer = document.getElementById('addon-cart-drawer');
    if (drawer) drawer.style.right = '-420px';
    if (overlay) overlay.style.display = 'none';
    setTimeout(() => { if (drawer) drawer.style.display = 'none'; }, 280);
}

async function checkoutAddonCart() {
    if (g_addonCart.length === 0) return;
    const btn = document.getElementById('btn-addon-checkout');
    const origText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Spracúvam...';

    try {
        const items = g_addonCart.map(item => ({
            key: item.key,
            period: ADDON_META[item.key].hasPeriod ? g_currentBillingPeriod : null
        }));

        const fd = new FormData();
        fd.append('action', 'order_addons');
        fd.append('items', JSON.stringify(items));

        const res = await fetch('api/addon_order.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showAppToast(data.message || 'Objednávka bola úspešne spracovaná!', 'success');
            g_addonCart = [];
            renderAddonCart();
            closeAddonCart();
        } else if (data.need_topup) {
            showAppToast(data.error || 'Nedostatočný zostatok v Peňaženke. Presmerúvam na dobitie...', 'error');
            setTimeout(() => { window.location.href = 'dashboard-penazanka.php'; }, 1800);
        } else {
            showAppToast(data.error || 'Chyba pri spracovaní objednávky.', 'error');
        }
    } catch (e) {
        console.error('Chyba objednávky doplnkov:', e);
        showAppToast('Chyba spojenia so serverom.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = origText;
    }
}

/* ==================================================================== */

// Inicializácia billing UI pri načítaní stránky
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_profile');
        const res = await fetch('api/business.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success && data.profile) {
            const tier = data.profile.subscription_tier || 'free';
            const expiresAt = data.profile.subscription_expires_at || null;
            const period = data.profile.subscription_period || 'monthly';
            updateBillingUI(tier, expiresAt, period);
        }
    } catch(e) {
        console.error('Billing init error:', e);
    }
    loadAiCreditsBalance();
});

async function loadAiCreditsBalance() {
    try {
        const fd = new FormData(); fd.append('action', 'get_wallet');
        const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
        const data = await res.json();
        const el = document.getElementById('addon-ai-credits-count');
        if (el && data.success && data.wallet) el.textContent = data.wallet.ai_credits ?? 0;
    } catch (e) {}
}

async function buyAiCreditsAddon(count) {
    try {
        const fd = new FormData(); fd.append('action', 'buy_ai_credits'); fd.append('count', count);
        const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            document.getElementById('addon-ai-credits-count').textContent = data.new_ai_credits;
            showAppToast(data.message || 'AI kredity boli pripísané.', 'success');
        } else {
            showAppToast(data.error || 'Nákup sa nepodaril.', 'error');
        }
    } catch (e) { showAppToast('Chyba pripojenia.', 'error'); }
}
</script>