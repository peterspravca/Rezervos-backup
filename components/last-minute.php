<div id="sec-last-minute" class="section">
                <div class="admin-panel" id="last-minute-locked" style="display:none; text-align:center; padding: 40px;">
                    <span class="material-symbols-outlined" style="font-size: 48px; color: var(--primary-color);">lock</span>
                    <h3>Najprv vyplňte profil</h3>
                    <p>Aby ste mohli pridávať Last Minute termíny, musíte si najskôr kompletne vyplniť a uložiť profil v záložke "Môj profil".</p>
                </div>

                <div id="last-minute-content">
                    <div class="admin-panel">
                        <h2 class="section-header"><span class="material-symbols-outlined">schedule</span> Pridať Last Minute termín</h2>
                          <p class="section-desc">Vytvorte zľavnený termín na blízku dobu a zaplňte svoje prázdne miesta.</p>
                        <form id="lastMinuteForm" onsubmit="addLastMinute(event)">
                            <div class="form-group">
                                <label>Služba</label>
                                <select id="lm-service" required>
                                    <option value="">Vyberte službu...</option>
                                </select>
                            </div>
                            <div style="display:flex; gap:15px;">
                                <div class="form-group" style="flex:1;">
                                    <label>Dátum</label>
                                    <input type="date" id="lm-date" required>
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Čas</label>
                                    <input type="time" id="lm-time" required>
                                </div>
                            </div>
                            <div style="display:flex; gap:15px;">
                                <div class="form-group" style="flex:1;">
                                    <label>Pôvodná cena (€)</label>
                                    <input type="number" step="0.01" id="lm-orig-price" required>
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Zľavnená cena (€)</label>
                                    <input type="number" step="0.01" id="lm-disc-price" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Poznámka (voliteľné)</label>
                                <input type="text" id="lm-note" placeholder="Napr. Platí len pre študentov...">
                            </div>
                            <button type="submit" class="btn-primary">Pridať termín</button>
                        </form>
                    </div>

                    <div class="admin-panel">
                        <h2 class="section-header"><span class="material-symbols-outlined">list</span> Aktuálne Last Minute termíny</h2>
                        <div id="last-minute-container" style="margin-top: 15px;">
                            <p>Načítavam...</p>
                        </div>
                    </div>
                </div>
            </div>