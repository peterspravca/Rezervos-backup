<div id="sec-services" class="section">
    <div class="vueto-card">
        <div class="vueto-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="section-header"><span class="material-symbols-outlined">list_alt</span> Môj / Náš Cenník Služieb</h2>
                <p class="section-desc">Spravujte svoje kategórie a služby, ktoré ponúkate zákazníkom.</p>
                <p style="margin:5px 0 0 0; font-size:13px; color:var(--text-secondary);">Pridávajte kategórie a služby, a priraďte k nim ceny pre každého pracovníka.</p>
            </div>
            <div>
                <button class="btn-secondary" onclick="openCategoryModal()" style="padding: 8px 16px; font-size: 13px; margin-right: 10px;">
                    <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle;">add_box</span> Pridať Kategóriu
                </button>
                <button class="btn-primary" onclick="openServiceModal()" style="padding: 8px 16px; font-size: 13px;">
                    <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle;">add</span> Pridať Službu
                </button>
            </div>
        </div>
        <div class="vueto-card-body" style="padding: 20px;">
            <div id="services-container">
                <!-- Kategórie a služby budú vykreslené sem -->
            </div>
        </div>
    </div>
</div>