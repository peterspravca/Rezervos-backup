<div id="sec-support" class="section">
    <div class="admin-panel">
        <h2 class="section-header"><span class="material-symbols-outlined">headset_mic</span> Kontaktovať podporu</h2>
        <p class="section-desc">Máte otázku alebo technický problém? Napíšte nám a my vám odpovieme priamo na váš e-mail, ktorý máte v profile.</p>
        
        <form id="supportForm" onsubmit="sendSupport(event)">
            <div class="form-group">
                <label>Predmet</label>
                <input type="text" id="supp-subject" required placeholder="Napr. Problém s kalendárom">
            </div>
            <div class="form-group">
                <label>Správa</label>
                <textarea id="supp-message" rows="5" required placeholder="Rozpíšte váš problém alebo dotaz..." style="width: 100%; padding: 12px 15px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-primary); border-radius: 10px; box-sizing: border-box; resize: vertical;"></textarea>
            </div>
            <button type="submit" class="btn-primary" id="supp-btn" style="border-radius: 12px; padding: 12px 28px;">Odoslať správu</button>
            <p id="supp-msg" style="margin-top: 15px; display: none;"></p>
        </form>
    </div>
</div>