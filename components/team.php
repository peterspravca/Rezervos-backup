<div id="sec-team" class="section">
    <div class="vueto-card">
        <div class="vueto-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
            <div>
                <h2 class="section-header" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--primary-color);">groups</span>
                    Ja / Zamestnanci
                </h2>
                <p class="section-desc" style="margin: 4px 0 0 0; color: var(--text-secondary); font-size: 13px;">
                    Spravujte svoj profil majiteľa/pracovníka a členov tímu, pozície a fotografie.
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <div style="background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 10px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--primary-color);">badge</span>
                    <span>Kapacita tímu: <strong id="team-count" style="color: var(--primary-color);">0</strong> / <strong id="team-max">1</strong></span>
                    <span id="team-tier-badge" style="font-size: 11px; padding: 2px 6px; border-radius: 6px; background: rgba(176, 128, 66, 0.15); color: var(--primary-color); font-weight: 700; text-transform: uppercase;">FREE</span>
                </div>
                <button class="btn-primary" id="btn-add-employee" onclick="openEmployeeModal()" style="padding: 9px 18px; font-size: 13.5px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; cursor: pointer;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">person_add</span>
                    <span>Pridať zamestnanca</span>
                </button>
            </div>
        </div>

        <div class="vueto-card-body" style="padding: 25px 20px;">
            
            <!-- INFORMAČNÝ BANNER PRE MAJITEĽA (AJ PRE FREE VERZIU) -->
            <div style="background: linear-gradient(135deg, rgba(176, 128, 66, 0.08) 0%, rgba(176, 128, 66, 0.02) 100%); border: 1px solid rgba(176, 128, 66, 0.25); border-left: 4px solid var(--primary-color); border-radius: 12px; padding: 16px 20px; margin-bottom: 25px; display: flex; align-items: flex-start; gap: 15px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(176, 128, 66, 0.15); display: flex; align-items: center; justify-content: center; color: var(--primary-color); flex-shrink: 0; margin-top: 2px;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                </div>
                <div style="flex: 1;">
                    <strong style="font-size: 14.5px; color: var(--text-primary); display: block; margin-bottom: 4px;">
                        Dôležité upozornenie pre nastavenie profilu majiteľa
                    </strong>
                    <p style="margin: 0; font-size: 13.5px; color: var(--text-secondary); line-height: 1.55;">
                        <strong>Aj pri balíku ZDARMA (FREE)</strong> je potrebné vyplniť a nastaviť profil majiteľa prevádzky (zadať Vaše meno, pracovnú pozíciu a nahrať vlastnú profilovú fotografiu). Vďaka tomu zákazníci pri online rezervácii presne vedia, ku komu sa objednávajú a kto im bude službu poskytovať.
                    </p>
                </div>
            </div>

            <!-- GRID KARIET ZAMESTNANCOV -->
            <div id="team-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 40px 20px; grid-column: 1/-1; color: var(--text-secondary);">
                    <span class="material-symbols-outlined" style="font-size: 36px; animation: spin 1.5s linear infinite;">sync</span>
                    <p style="margin-top: 10px; font-size: 14px;">Načítavam zoznam zamestnancov...</p>
                </div>
            </div>
        </div>
    </div>
</div>