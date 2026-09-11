<?php if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php'; ?>
<div id="sec-extensions" class="section">
    <div class="admin-panel" style="width: 100%; max-width: 100%; margin: 0; box-sizing: border-box;">

        <!-- HLAVIČKA -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
            <div>
                <h2 class="section-header" style="margin-bottom: 6px; display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">account_balance_wallet</span>
                    Peňaženka, Rozšírenia a SMS
                </h2>
                <p class="section-desc" style="margin: 0;">Spravujte svoj kredit, zviditeľňujte prevádzku v katalógu a nakupujte SMS balíčky pre klientov.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="loadWalletData()" class="btn-secondary" style="padding: 8px 14px; font-size: 13px; border-radius: 10px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">refresh</span>
                    <span>Aktualizovať zostatok</span>
                </button>
            </div>
        </div>

        <!-- 1. HORNÝ BLOK: KARTA PEŇAŽENKY A RÝCHLE DOBITIE -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;" class="wallet-responsive-grid">
            
            <!-- KARTA PEŇAŽENKY -->
            <div style="background: linear-gradient(135deg, #b08042 0%, #d4a359 50%, #8c602b 100%); border-radius: 20px; padding: 28px; color: #ffffff; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 10px 25px rgba(176, 128, 66, 0.25); min-height: 220px; position: relative; overflow: hidden; box-sizing: border-box;">
                <div style="position: absolute; right: -30px; top: -30px; width: 160px; height: 160px; background: rgba(255,255,255,0.12); border-radius: 50%; pointer-events: none;"></div>
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; z-index: 2;">
                    <div>
                        <span style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.85;">Zostatok Peňaženky</span>
                        <h3 id="ext-wallet-balance" style="font-size: 42px; font-weight: 900; margin: 8px 0 0 0; line-height: 1.1; letter-spacing: -0.5px;">
                            0,00 <span style="font-size: 26px; font-weight: 700;">€</span>
                        </h3>
                    </div>
                    <div style="width: 52px; height: 52px; border-radius: 16px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                        <span class="material-symbols-outlined" style="font-size: 30px;">account_balance_wallet</span>
                    </div>
                </div>

                <div style="z-index: 2; margin-top: auto; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div style="font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.18); padding: 7px 14px; border-radius: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 18px; opacity: 0.9;">redeem</span>
                        <span style="opacity: 0.85; font-size: 11px; text-transform: uppercase;">Získané zdieľaním:</span>
                        <span id="ext-earned-balance" style="font-weight: 850; letter-spacing: 0.3px;">0,00 €</span>
                    </div>
                    <span style="font-size: 12px; font-weight: 700; opacity: 0.85; display: inline-flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">verified</span> Účet aktívny
                    </span>
                </div>
            </div>

            <!-- RÝCHLE DOBITIE KREDITU -->
            <div class="premium-card" style="margin: 0; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: var(--primary-color);">add_card</span>
                        Dobiť kredit do Peňaženky
                    </h3>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 0 0 16px 0;">Zvoľte si sumu pre dobitie. Kredit nemá expiráciu.</p>

                    <!-- Výber sumy -->
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px;" id="topup-amount-buttons">
                        <button type="button" class="topup-pill active" onclick="selectTopupAmount(5, this)">5 €</button>
                        <button type="button" class="topup-pill" onclick="selectTopupAmount(10, this)">10 €</button>
                        <button type="button" class="topup-pill" onclick="selectTopupAmount(20, this)">20 €</button>
                        <button type="button" class="topup-pill" onclick="selectTopupAmount(50, this)">50 €</button>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <span class="material-symbols-outlined" style="position: absolute; left: 14px; font-size: 18px; color: var(--primary-color); pointer-events: none;">euro_symbol</span>
                            <input type="number" id="custom-topup-amount" placeholder="Alebo zadajte vlastnú sumu v € (min. 1 €)" min="1" step="1" oninput="clearPillActive(this)" style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; border-radius: 12px; border: 1.5px solid var(--border-color); background: var(--bg-color); color: var(--text-primary); font-size: 13.5px; font-weight: 600; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(176, 128, 66, 0.15)';" onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none';">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" onclick="processTopup()" class="btn-primary" style="flex: 1; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 15px rgba(176, 128, 66, 0.3);">
                        <span class="material-symbols-outlined" style="font-size: 18px;">payments</span>
                        <span id="topup-submit-btn-text">Dobiť 5,00 €</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. BLOK: ZARÁBAJTE RÝCHLYM ZDIEĽANÍM (2 HLAVNÉ KATEGÓRIE) -->
        <div class="reward-box-card" style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 20px; padding: 26px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                <div>
                    <h4 style="margin: 0; font-size: 18px; font-weight: 850; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: var(--primary-color); font-size: 24px;">campaign</span>
                        <span>2. Zarábajte rýchlym zdieľaním</span>
                    </h4>
                    <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-secondary);">Zdieľajte aplikáciu alebo svoj salón na sociálnych sieťach a získajte kredit priamo do Peňaženky.</p>
                </div>
                <span id="share-daily-status-badge" style="font-size: 11.5px; font-weight: 800; padding: 5px 12px; border-radius: 8px; background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);">
                    Dnes k dispozícii
                </span>
            </div>

            <!-- 2 VEĽKÉ KATEGÓRIE: APLIKÁCIA VS PREVÁDZKA -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 18px;" class="share-two-col-grid">
                
                <!-- KATEGÓRIA 1: PROPAGÁCIA APLIKÁCIE (+0,10 €) -->
                <div style="background: var(--bg-color); border: 1.5px solid rgba(99, 102, 241, 0.3); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 6px 18px rgba(99, 102, 241, 0.06);">
                    <!-- Obrázok aplikácie -->
                    <div style="position: relative; height: 160px; overflow: hidden; background: #0e0a1a;">
                        <img src="assets/images/promo_app.jpg" alt="Aplikácia <?= BRAND_NAME ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="position: absolute; top: 12px; left: 12px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 4px;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">rocket_launch</span>
                            <span>Aplikácia • Získate +0,10 €</span>
                        </div>
                        <a href="assets/images/promo_app.jpg" download="volnekreslo_aplikacia_promo.jpg" style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: #fff; padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 4px;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">download</span>
                            <span>Stiahnuť</span>
                        </a>
                    </div>

                    <div style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 850; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                <span class="material-symbols-outlined" style="color: #6366f1; font-size: 19px;">devices</span>
                                <span>1. Propagácia aplikácie <?= BRAND_NAME ?></span>
                            </h4>
                            <p style="margin: 0 0 14px 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.45;">
                                Zdieľajte výhody aplikácie, online AI kalendára a Last Minute rezervácií pre zákazníkov.
                            </p>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="shareRewardsApp('facebook')" style="flex: 1; background: rgba(24, 119, 242, 0.08); border: 1px solid rgba(24, 119, 242, 0.25); color: #1877f2; border-radius: 10px; padding: 10px 6px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">share</span>
                                <span>Facebook</span>
                            </button>
                            <button type="button" onclick="shareRewardsApp('whatsapp')" style="flex: 1; background: rgba(37, 211, 102, 0.08); border: 1px solid rgba(37, 211, 102, 0.25); color: #25d366; border-radius: 10px; padding: 10px 6px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">chat</span>
                                <span>WhatsApp</span>
                            </button>
                            <button type="button" onclick="openInstagramPromoModal('https://<?= BRAND_SITE ?>', 'Aplikácia <?= BRAND_NAME ?>', 'app')" style="flex: 1; background: rgba(225, 48, 108, 0.08); border: 1px solid rgba(225, 48, 108, 0.25); color: #e1306c; border-radius: 10px; padding: 10px 6px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">photo_camera</span>
                                <span>Instagram</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KATEGÓRIA 2: PROPAGÁCIA VLASTNEJ PREVÁDZKY (+0,05 €) -->
                <div style="background: var(--bg-color); border: 1.5px solid rgba(176, 128, 66, 0.35); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 6px 18px rgba(176, 128, 66, 0.06);">
                    <!-- Obrázok prevádzky -->
                    <div style="position: relative; height: 160px; overflow: hidden; background: #1c140a;">
                        <img src="assets/images/promo_salon.jpg" alt="Moja prevádzka" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="position: absolute; top: 12px; left: 12px; background: linear-gradient(135deg, #b08042, #8c602b); color: #fff; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 4px;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">storefront</span>
                            <span>Prevádzka • Získate +0,05 €</span>
                        </div>
                        <a href="assets/images/promo_salon.jpg" download="moja_prevadzka_promo.jpg" style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: #fff; padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 4px;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">download</span>
                            <span>Stiahnuť</span>
                        </a>
                    </div>

                    <div style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 850; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                <span class="material-symbols-outlined" style="color: var(--primary-color); font-size: 19px;">store</span>
                                <span id="share-card-salon-name">2. Propagácia mojej prevádzky & termínov</span>
                            </h4>
                            <p style="margin: 0 0 14px 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.45;">
                                Zdieľajte priamy odkaz na vašu prevádzku s vašimi službami, cenníkom a online kalendárom.
                            </p>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="shareMySalon('facebook')" style="flex: 1; background: rgba(24, 119, 242, 0.08); border: 1px solid rgba(24, 119, 242, 0.25); color: #1877f2; border-radius: 10px; padding: 10px 6px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">share</span>
                                <span>Facebook</span>
                            </button>
                            <button type="button" onclick="shareMySalon('whatsapp')" style="flex: 1; background: rgba(37, 211, 102, 0.08); border: 1px solid rgba(37, 211, 102, 0.25); color: #25d366; border-radius: 10px; padding: 10px 6px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">chat</span>
                                <span>WhatsApp</span>
                            </button>
                            <button type="button" onclick="shareMySalon('instagram')" style="flex: 1; background: rgba(225, 48, 108, 0.08); border: 1px solid rgba(225, 48, 108, 0.25); color: #e1306c; border-radius: 10px; padding: 10px 6px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">photo_camera</span>
                                <span>Instagram</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- DOMINANTNÝ EXPANDOVANÝ BANNER: MARKETINGOVÝ BALÍK -->
        <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.09) 0%, rgba(176, 128, 66, 0.07) 50%, rgba(99, 102, 241, 0.05) 100%); border: 2px solid rgba(99, 102, 241, 0.35); border-radius: 22px; padding: 30px; margin-bottom: 30px; box-shadow: 0 12px 35px -5px rgba(99, 102, 241, 0.12); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -30px; top: -30px; width: 180px; height: 180px; background: rgba(99, 102, 241, 0.12); border-radius: 50%; pointer-events: none; filter: blur(35px);"></div>
            <div style="position: absolute; left: 30%; bottom: -40px; width: 220px; height: 220px; background: rgba(176, 128, 66, 0.08); border-radius: 50%; pointer-events: none; filter: blur(40px);"></div>
            
            <!-- 1. Hlavička s ikonou, titulkom a cenovým odznakom -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; margin-bottom: 22px; position: relative; z-index: 2;">
                <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 290px;">
                    <div style="width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);">
                        <span class="material-symbols-outlined" style="font-size: 34px;">campaign</span>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                            <h3 style="margin: 0; font-size: 23px; font-weight: 850; color: var(--text-primary); letter-spacing: -0.3px;">Marketingový balík a Automatické Kampane</h3>
                            <span style="background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1.5px solid rgba(99, 102, 241, 0.35); padding: 4px 12px; border-radius: 8px; font-size: 11.5px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.5px;">PRÉMIOVÝ RAST PREVÁDZKY</span>
                        </div>
                        <p style="margin: 0; font-size: 14.5px; color: var(--text-secondary); line-height: 1.5;">
                            Kompletný marketingový systém na automatizované získavanie a udržanie zákazníkov. Oslovte klientov presne vtedy, keď to má najväčší účinok.
                        </p>
                    </div>
                </div>

                <div style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 10px 18px; text-align: right; box-shadow: var(--shadow-sm);">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Cena doplnku</div>
                    <div style="font-size: 20px; font-weight: 850; color: #6366f1; line-height: 1.1;">4.90 € <span style="font-size: 12.5px; color: var(--text-secondary); font-weight: 600;">/ mesiac</span></div>
                    <div style="font-size: 11px; color: #10b981; font-weight: 700; margin-top: 2px;">✓ Súčasť balíkov PRO a VIP</div>
                </div>
            </div>

            <!-- 2. Prečo si to aktivovať (3 hlavné prínosy) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 22px; position: relative; z-index: 2;">
                <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 16px; display: flex; align-items: flex-start; gap: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 22px; color: #10b981; flex-shrink: 0; margin-top: 2px;">trending_up</span>
                    <div>
                        <strong style="font-size: 13.5px; color: var(--text-primary); display: block; margin-bottom: 2px;">Až o 35% viac opakovaných návštev</strong>
                        <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.4; display: block;">Pripomeňte sa klientom v pravý moment – na meniny, sviatky alebo po dlhšej odmlke.</span>
                    </div>
                </div>

                <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 16px; display: flex; align-items: flex-start; gap: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 22px; color: #6366f1; flex-shrink: 0; margin-top: 2px;">auto_awesome</span>
                    <div>
                        <strong style="font-size: 13.5px; color: var(--text-primary); display: block; margin-bottom: 2px;">Žiadne manuálne písanie e-mailov</strong>
                        <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.4; display: block;">Vstavaný AI asistent a hotové HTML šablóny pripravia profesionálnu kampaň za pár sekúnd.</span>
                    </div>
                </div>

                <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 16px; display: flex; align-items: flex-start; gap: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 22px; color: #f59e0b; flex-shrink: 0; margin-top: 2px;">event_available</span>
                    <div>
                        <strong style="font-size: 13.5px; color: var(--text-primary); display: block; margin-bottom: 2px;">Rýchle zaplnenie voľných termínov</strong>
                        <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.4; display: block;">Máte voľné miesta? Pošlite bleskovú ponuku alebo zľavu na 1 klik všetkým klientom.</span>
                    </div>
                </div>
            </div>

            <!-- 3. Čo všetko služba obsahuje (Detailná mriežka funkcií) -->
            <div style="background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 16px; padding: 20px 22px; margin-bottom: 22px; position: relative; z-index: 2;">
                <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--primary-color);">checklist</span>
                    Čo všetko Marketingový balík obsahuje:
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                    
                    <!-- Funkcia 1: Meninové kampane -->
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">cake</span>
                        </div>
                        <div>
                            <strong style="font-size: 13.5px; color: var(--text-primary); display: block;">Automatické meninové kampane na 1 klik</strong>
                            <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.45; display: block; margin-top: 2px;">
                                Systém denne deteguje dnešných oslávencov z vašich kontaktov a predpripraví personalizované blahoželanie s vašou ponukou.
                            </span>
                        </div>
                    </div>

                    <!-- Funkcia 2: Hromadný newsletter -->
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(99, 102, 241, 0.12); color: #6366f1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">mail</span>
                        </div>
                        <div>
                            <strong style="font-size: 13.5px; color: var(--text-primary); display: block;">Hromadný newsletter a e-maily klientom</strong>
                            <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.45; display: block; margin-top: 2px;">
                                Rozosielajte novinky, sezónne akcie, zmeny v otváracích hodinách alebo špeciálne balíčky služieb bez obmedzení.
                            </span>
                        </div>
                    </div>

                    <!-- Funkcia 3: Segmentácia klientov -->
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59, 130, 246, 0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">groups</span>
                        </div>
                        <div>
                            <strong style="font-size: 13.5px; color: var(--text-primary); display: block;">Cielená segmentácia zákazníkov</strong>
                            <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.45; display: block; margin-top: 2px;">
                                Filtrujte príjemcov podľa pohlavia (ženy / muži), vernosti (stáli klienti s 3+ návštevami) či histórie rezervácií.
                            </span>
                        </div>
                    </div>

                    <!-- Funkcia 4: Prémiové HTML šablóny -->
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(176, 128, 66, 0.12); color: var(--primary-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">palette</span>
                        </div>
                        <div>
                            <strong style="font-size: 13.5px; color: var(--text-primary); display: block;">Hotové dizajnové HTML šablóny</strong>
                            <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.45; display: block; margin-top: 2px;">
                                Profesionálne naformátované vizuály pre meniny, Vianoce, Black Friday, poďakovanie za návštevu a zľavové vouchery.
                            </span>
                        </div>
                    </div>

                    <!-- Funkcia 5: AI Asistent textov -->
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(236, 72, 153, 0.12); color: #ec4899; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">psychology</span>
                        </div>
                        <div>
                            <strong style="font-size: 13.5px; color: var(--text-primary); display: block;">Vstavaný AI Asistent textov</strong>
                            <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.45; display: block; margin-top: 2px;">
                                Automatická oprava gramatiky, skracovanie textu jedným kliknutím a tvorba pútavých správ v priateľskom alebo biznis štýle.
                            </span>
                        </div>
                    </div>

                    <!-- Funkcia 6: UTM a Zdroje dopytov -->
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245, 158, 11, 0.12); color: #f59e0b; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">insights</span>
                        </div>
                        <div>
                            <strong style="font-size: 13.5px; color: var(--text-primary); display: block;">Sledovanie zdrojov dopytov (UTM)</strong>
                            <span style="font-size: 12px; color: var(--text-secondary); line-height: 1.45; display: block; margin-top: 2px;">
                                Prehľadný reporting o tom, z ktorých marketingových kampaní, sociálnych sietí a odkazov k vám prichádzajú nové rezervácie.
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 4. Spodná akčná lišta s tlačidlom na rozkliknutie -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: relative; z-index: 2;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary);">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">verified_user</span>
                    <span>Žiadna viazanosť • Aktivácia a správa okamžite priamo v systéme</span>
                </div>

                <div>
                    <button type="button" class="btn-primary" style="padding: 14px 32px; border-radius: 14px; font-size: 15px; font-weight: 800; background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4); display: inline-flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.25s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';" onclick="showSection('marketing')">
                        <span class="material-symbols-outlined" style="font-size: 22px;">rocket_launch</span>
                        <span>Otvoriť Marketing a Kampane</span>
                        <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. SEKCIA: ZVIDITEĽNENIE A TOPOVANIE PREVÁDZKY (PLATBA Z PEŇAŽENKY) -->
        <div style="margin-bottom: 35px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: var(--primary-color);">trending_up</span>
                        Zviditeľnenie a Topovanie prevádzky
                    </h3>
                    <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Posuňte svoju prevádzku na 1. miesto vo výsledkoch vyhľadávania a získajte viac zákazníkov. Hradené priamo z Peňaženky.</p>
                </div>
                <div id="ext-boost-status-container">
                    <span id="ext-boost-status" style="background: rgba(176, 128, 66, 0.12); color: var(--primary-color); border: 1px solid rgba(176, 128, 66, 0.25); padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">rocket_launch</span>
                        <span>Stav: Neaktívne</span>
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                
                <!-- SLUŽBA 1: JEDNORAZOVÉ TAPNUTIE -->
                <div class="premium-card" style="margin: 0; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid var(--border-color); border-radius: 16px; padding: 22px; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(176, 128, 66, 0.12); color: var(--primary-color); display: flex; align-items: center; justify-content: center;">
                                <span class="material-symbols-outlined" style="font-size: 24px;">touch_app</span>
                            </div>
                            <span style="background: rgba(176, 128, 66, 0.12); color: var(--primary-color); border: 1px solid rgba(176, 128, 66, 0.25); padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Rýchly posun</span>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 800; margin: 0 0 6px 0; color: var(--text-primary);">Jednorazové tapnutie</h4>
                        <div style="font-size: 24px; font-weight: 900; color: var(--primary-color); margin-bottom: 10px;">
                            0,45 € <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">/ 1x tap</span>
                        </div>
                        <p style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; margin: 0 0 16px 0;">
                            Posunie vašu prevádzku na 1. miesto vo výpise salónov a v príslušných kategóriách služieb.
                        </p>
                        <div style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#10b981;font-weight:700;margin:0 0 16px 0;"><span class="material-symbols-outlined" style="font-size:14px;">savings</span> Možno platiť aj z nazbieraných kreditov</div>
                    </div>
                    <button type="button" onclick="purchaseBoost('single_tap')" class="btn-primary" style="width: 100%; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">bolt</span>
                        <span>Tapnúť teraz (0,45 €)</span>
                    </button>
                </div>

                <!-- SLUŽBA 2: RANNÉ VTÁČA -->
                <div class="premium-card" style="margin: 0; display: flex; flex-direction: column; justify-content: space-between; border: 2px solid var(--primary-color); border-radius: 16px; padding: 22px; position: relative; background: var(--card-bg); box-shadow: 0 4px 20px rgba(176, 128, 66, 0.12);">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center;">
                                <span class="material-symbols-outlined" style="font-size: 24px;">wb_sunny</span>
                            </div>
                            <span style="background: var(--primary-color); color: #ffffff; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(176, 128, 66, 0.3);">
                                Najpopulárnejšie • 7 Dní
                            </span>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 800; margin: 0 0 6px 0; color: var(--text-primary);">Ranné vtáča balíček</h4>
                        <div style="font-size: 24px; font-weight: 900; color: var(--primary-color); margin-bottom: 10px;">
                            2,40 € <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">/ 7 dní auto-tap</span>
                        </div>
                        <p style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; margin: 0 0 16px 0;">
                            Automatický posun na vrchol každé ráno o <b>08:00 hod.</b> po dobu celých 7 dní. Ideálne pre ranné rezervácie.
                        </p>
                        <div style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#10b981;font-weight:700;margin:0 0 16px 0;"><span class="material-symbols-outlined" style="font-size:14px;">savings</span> Možno platiť aj z nazbieraných kreditov</div>
                    </div>
                    <button type="button" onclick="purchaseBoost('morning_bird')" class="btn-primary" style="width: 100%; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 15px rgba(176, 128, 66, 0.3);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">schedule</span>
                        <span>Aktivovať balíček (2,40 €)</span>
                    </button>
                </div>

                <!-- SLUŽBA 3: PRIME-TIME BOMBARDÉR -->
                <div class="premium-card" style="margin: 0; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid var(--border-color); border-radius: 16px; padding: 22px; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(239, 68, 68, 0.12); color: #ef4444; display: flex; align-items: center; justify-content: center;">
                                <span class="material-symbols-outlined" style="font-size: 24px;">rocket_launch</span>
                            </div>
                            <span style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25); padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Špička • 7 Dní</span>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 800; margin: 0 0 6px 0; color: var(--text-primary);">Prime-time Bombardér</h4>
                        <div style="font-size: 24px; font-weight: 900; color: var(--primary-color); margin-bottom: 10px;">
                            4,90 € <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">/ 7 dní auto-tap</span>
                        </div>
                        <p style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; margin: 0 0 16px 0;">
                            Automatický posun v najsilnejšom špičkovom čase najvyššej návštevnosti (<b>17:00 – 20:00</b>) denne počas 7 dní.
                        </p>
                        <div style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#10b981;font-weight:700;margin:0 0 16px 0;"><span class="material-symbols-outlined" style="font-size:14px;">savings</span> Možno platiť aj z nazbieraných kreditov</div>
                    </div>
                    <button type="button" onclick="purchaseBoost('primetime_bomber')" class="btn-primary" style="width: 100%; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">local_fire_department</span>
                        <span>Aktivovať balíček (4,90 €)</span>
                    </button>
                </div>

            </div>

            <!-- PROMO VIDEO BALÍČEK (YOUTUBE @VOLNEKRESLO) -->
            <div style="background: linear-gradient(135deg, rgba(255, 0, 0, 0.05) 0%, rgba(176, 128, 66, 0.08) 100%); border: 1.5px solid rgba(176, 128, 66, 0.25); border-radius: 16px; padding: 22px 26px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 16px; max-width: 720px;">
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #ff0000; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(255, 0, 0, 0.25);">
                        <span class="material-symbols-outlined" style="font-size: 28px;">smart_display</span>
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span>Služba: Nahráme vaše video na náš YouTube kanál</span>
                            <span style="background: #ff0000; color: #fff; font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 6px;">@volnekreslo</span>
                        </div>
                        <div style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.4;">
                            Nemáte vlastný YouTube kanál? Pošlite nám vaše video – my ho spracujeme a nahráme na náš oficiálny kanál <a href="https://www.youtube.com/@volnekreslo" target="_blank" style="color: #ff0000; font-weight: 700; text-decoration: underline;"><?= BRAND_NAME ?> (@volnekreslo)</a> a automaticky ho prepojíme s vaším profilom.
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="https://www.youtube.com/@volnekreslo" target="_blank" class="btn" style="padding: 9px 15px; font-size: 12.5px; border-radius: 6px; color: #ff0000; border-color: rgba(255, 0, 0, 0.3);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">open_in_new</span>
                        <span>Náš kanál</span>
                    </a>
                    <button type="button" onclick="openYoutubeHelpModal()" class="btn-primary" style="padding: 9px 18px; font-size: 12.5px; border-radius: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 17px;">cloud_upload</span>
                        <span>Požiadať o nahratie</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. SEKCIA: SMS UPOZORNENIA A BALÍČKY (SLOVENSKO A ČESKO) -->
        <div class="premium-card" style="margin-bottom: 30px; padding: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 18px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                        <h3 style="font-size: 18px; font-weight: 800; margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-outlined" style="color: #3b82f6;">sms</span>
                            Koľko stoja SMS upozornenia?
                        </h3>
                        <span style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase;">Pripravujeme</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-secondary); margin: 0; line-height: 1.5; max-width: 700px;">
                        SMS pripomenutia fungujú na báze kreditov, pričom za každú správu sa vám jeden kredit odráta. Cenník balíčkov podľa jednotlivých krajín nájdete v tabuľke nižšie:
                    </p>
                </div>

                <!-- ELEGANTNÝ PREPÍNAČ KRAJINY (SLOVENSKO / ČESKO) -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 6px;">Krajina doručenia SMS:</label>
                    <div style="display: inline-flex; background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: 12px; padding: 4px; gap: 4px;">
                        <button type="button" id="sms-btn-sk" onclick="setSmsCountry('SK')" class="sms-country-tab active" style="padding: 8px 18px; border-radius: 9px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease;">
                            <span>Slovensko (€)</span>
                        </button>
                        <button type="button" id="sms-btn-cz" onclick="setSmsCountry('CZ')" class="sms-country-tab" style="padding: 8px 18px; border-radius: 9px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease;">
                            <span>Česko (CZK)</span>
                        </button>
                    </div>
                    <input type="hidden" id="sms-country-select" value="SK">
                </div>
            </div>

            <!-- INFORMAČNÝ BANNER: PRIPRAVUJEME -->
            <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-outlined" style="font-size: 22px;">construction</span>
                    </div>
                    <div>
                        <strong style="font-size: 13.5px; color: var(--text-primary); display: block;">Priame odosielanie SMS správ momentálne pripravujeme</strong>
                        <span style="font-size: 12.5px; color: var(--text-secondary);">Cenník balíčkov je zverejnený pre váš prehľad. Možnosť nákupu a automatických SMS pripomienok bude spustená už čoskoro.</span>
                    </div>
                </div>
                <span style="background: #3b82f6; color: #ffffff; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">Už čoskoro</span>
            </div>

            <!-- TABUĽKA SMS BALÍČKOV -->
            <div style="overflow-x: auto; margin-bottom: 18px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 12px 14px;">SMS balíček</th>
                            <th style="padding: 12px 14px;">Cena</th>
                            <th style="padding: 12px 14px;">Cena za SMS</th>
                            <th style="padding: 12px 14px; text-align: right;">Akcia</th>
                        </tr>
                    </thead>
                    <tbody id="sms-packages-tbody">
                        <!-- Generované cez JS -->
                    </tbody>
                </table>
            </div>

            <p style="font-size: 12px; color: var(--text-secondary); margin: 0 0 20px 0; line-height: 1.5;">
                Ceny sú uvedené bez DPH, ktorá sa vypočítava podľa fakturačnej krajiny používateľa. Konečnú cenu uvidíte na stránke s platbou pred dokončením nákupu.
            </p>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" onclick="buySelectedSms()" class="btn-primary" style="padding: 12px 24px; font-size: 14px; font-weight: 700; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); background: #3b82f6;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">shopping_cart</span>
                    <span>Kúpiť SMS kredity (Pripravujeme)</span>
                </button>
            </div>
        </div>

        <!-- 4. SEKCIA: HISTÓRIA TRANSAKCIÍ -->
        <div class="premium-card" style="margin: 0; padding: 24px;">
            <h4 style="font-size: 16px; font-weight: 800; margin: 0 0 14px 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 20px; color: var(--primary-color);">history</span>
                Posledné transakcie a pohyby v Peňaženke
            </h4>
            <div id="ext-transactions-list" style="max-height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Žiadne predchádzajúce transakcie.</p>
            </div>
        </div>

    </div>
</div>

<style>
.topup-pill {
    padding: 9px 12px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: var(--bg-color);
    color: var(--text-primary);
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
.topup-pill:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}
.topup-pill.active {
    background: var(--primary-color);
    color: #ffffff;
    border-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(176, 128, 66, 0.3);
}
@media (max-width: 768px) {
    .wallet-responsive-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
