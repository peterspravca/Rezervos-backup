<div id="sec-security" class="section">
    <div class="vueto-card">
        <div class="vueto-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 18px;">
            <div>
                <h2 class="section-header" style="margin: 0;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">security</span>
                    Zabezpečenie účtu
                </h2>
                <p class="section-desc" style="margin-top: 5px;">Spravujte svoje prihlasovacie heslá, 2FA e-mailové overenie a 3FA bezpečnostné otázky pre maximálnu ochranu vášho účtu.</p>
            </div>
        </div>
        <div class="vueto-card-body" style="padding: 24px 20px;">

        <!-- VIACSTUPŇOVÉ OVERENIE KARTA -->
        <div style="background: var(--card-bg); padding: 24px; border-radius: 16px; margin-bottom: 25px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
            <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 22px; color: var(--primary-color);">verified_user</span>
                Viacstupňové overenie (2FA / 3FA)
            </h3>

            <!-- 2FA ROW -->
            <div style="padding: 16px; background: var(--bg-color); border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="flex: 1; min-width: 240px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <strong style="font-size: 14.5px; color: var(--text-primary);">Dvojfázové overenie (2FA – E-mail)</strong>
                        <span style="font-size: 11px; font-weight: 700; background: rgba(59, 130, 246, 0.12); color: #3b82f6; padding: 2px 7px; border-radius: 6px;">2. STUPEŇ</span>
                    </div>
                    <span style="font-size: 12.5px; color: var(--text-secondary); display: block; margin-top: 3px;">Pri každom prihlásení bude vyžadovaný 6-miestny bezpečnostný kód zaslaný na váš e-mail.</span>
                </div>
                <label class="switch" style="margin: 0;">
                    <input type="checkbox" id="toggle-2fa" onchange="toggle2FA(this.checked)">
                    <span class="slider round"></span>
                </label>
            </div>

            <!-- 3FA ROW -->
            <div style="padding: 16px; background: var(--bg-color); border-radius: 12px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="flex: 1; min-width: 240px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <strong style="font-size: 14.5px; color: var(--text-primary);">Bezpečnostné otázky (3FA – 3. stupeň)</strong>
                        <span style="font-size: 11px; font-weight: 700; background: rgba(176, 128, 66, 0.15); color: var(--primary-color); padding: 2px 7px; border-radius: 6px;">3. STUPEŇ</span>
                    </div>
                    <span style="font-size: 12.5px; color: var(--text-secondary); display: block; margin-top: 3px;">Systém sa vás pri prihlásení opýta na 1 náhodnú otázku z troch, ktoré si sami zadefinujete.</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" onclick="toggleSecurityQuestionsSetup()" class="btn-secondary" style="padding: 6px 12px; font-size: 12px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">edit_note</span>
                        <span>Nastaviť otázky</span>
                    </button>
                    <label class="switch" style="margin: 0;">
                        <input type="checkbox" id="toggle-3fa-questions" onchange="toggle3FAQuestions(this.checked)">
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>

            <!-- 3FA FORM CONTAINER -->
            <div id="security-questions-setup" style="display: none; margin-top: 16px; background: var(--card-bg); border: 1.5px solid rgba(176, 128, 66, 0.3); border-radius: 14px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 20px; color: var(--primary-color);">psychology</span>
                        Definujte si 3 vlastné bezpečnostné otázky
                    </h4>
                    <button type="button" onclick="toggleSecurityQuestionsSetup(false)" style="background: transparent; border: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center;">
                        <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
                    </button>
                </div>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin: 0 0 16px 0; line-height: 1.4;">
                    Zadajte otázky, na ktoré poznáte odpoveď len vy. Pri odpovedi nezáleží na veľkých/malých písmenách ani diakritike.
                </p>

                <form id="settings-questions-form" onsubmit="saveSecurityQuestions(event)">
                    <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 18px;">
                        <!-- Otázka 1 -->
                        <div style="background: var(--bg-color); padding: 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">1. Bezpečnostná otázka</label>
                            <input type="text" id="sq-q1" placeholder="Napr. Aké bolo meno vášho prvého domáceho miláčika?" required style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 13.5px; box-sizing: border-box; margin-bottom: 8px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Vaša odpoveď na otázku č. 1</label>
                            <input type="text" id="sq-a1" placeholder="Zadajte odpoveď..." required style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 13.5px; box-sizing: border-box;">
                        </div>

                        <!-- Otázka 2 -->
                        <div style="background: var(--bg-color); padding: 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">2. Bezpečnostná otázka</label>
                            <input type="text" id="sq-q2" placeholder="Napr. V akom meste ste sa narodili?" required style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 13.5px; box-sizing: border-box; margin-bottom: 8px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Vaša odpoveď na otázku č. 2</label>
                            <input type="text" id="sq-a2" placeholder="Zadajte odpoveď..." required style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 13.5px; box-sizing: border-box;">
                        </div>

                        <!-- Otázka 3 -->
                        <div style="background: var(--bg-color); padding: 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">3. Bezpečnostná otázka</label>
                            <input type="text" id="sq-q3" placeholder="Napr. Aká je vaša obľúbená kniha alebo film?" required style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 13.5px; box-sizing: border-box; margin-bottom: 8px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Vaša odpoveď na otázku č. 3</label>
                            <input type="text" id="sq-a3" placeholder="Zadajte odpoveď..." required style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 13.5px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <button type="submit" id="save-questions-btn" style="background: var(--primary-color); border: none; color: #fff; padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(176, 128, 66, 0.3);">
                            <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                            <span>Uložiť a aktivovať 3FA</span>
                        </button>
                        <span id="questions-status-msg" style="font-size: 12.5px; font-weight: 600; color: #10b981;"></span>
                    </div>
                </form>
            </div>
        </div>

        <!-- ZMENA HESLA KARTA -->
        <div style="background: var(--card-bg); padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); max-width: 600px;">
            <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 22px; color: var(--primary-color);">lock_reset</span>
                Zmena hesla
            </h3>
            <form id="password-form" onsubmit="changePassword(event)">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display:block; margin-bottom:5px; font-size: 13px; font-weight: 600; color: var(--text-primary);">Súčasné heslo</label>
                    <input type="password" class="form-control" id="current_password" required style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--input-bg); color:var(--text-primary); box-sizing:border-box; font-size: 14px;">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display:block; margin-bottom:5px; font-size: 13px; font-weight: 600; color: var(--text-primary);">Nové heslo</label>
                    <input type="password" class="form-control" id="new_password" required minlength="6" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--input-bg); color:var(--text-primary); box-sizing:border-box; font-size: 14px;">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display:block; margin-bottom:5px; font-size: 13px; font-weight: 600; color: var(--text-primary);">Zopakujte nové heslo</label>
                    <input type="password" class="form-control" id="confirm_password" required minlength="6" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--input-bg); color:var(--text-primary); box-sizing:border-box; font-size: 14px;">
                </div>
                <button type="submit" class="btn-primary" style="padding: 11px 24px; border-radius: 8px; font-weight: 700; font-size: 13.5px; border: none; background: var(--primary-color); color: #fff; cursor: pointer; box-shadow: 0 4px 12px rgba(176, 128, 66, 0.3);">Zmeniť heslo</button>
            </form>
        </div>
        </div>
    </div>
</div>