<div id="sec-email" class="section">
                <div class="vueto-card">
                    <div class="vueto-card-header" style="display: flex; gap: 10px; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <button class="btn-primary" style="padding: 8px 16px; font-size: 13px; border-radius: 8px; display: flex; align-items: center; gap: 5px;"><span class="material-symbols-outlined" style="font-size:16px;">inbox</span> Doručené</button>
                        <button class="btn" style="padding: 8px 16px; font-size: 13px; border-radius: 8px; background: transparent; border: 1px solid var(--border-color); color: var(--text-secondary); display: flex; align-items: center; gap: 5px;" onclick="document.getElementById('emailSettingsModal').style.display='flex'"><span class="material-symbols-outlined" style="font-size:16px;">settings</span> Prepojiť existujúci e-mail</button>
                    </div>
                    <div style="padding: 20px;">
                        <table class="vueto-table" id="email-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>OD</th>
                                    <th>PREDMET</th>
                                    <th style="text-align: right;">DÁTUM</th>
                                </tr>
                            </thead>
                            <tbody id="email-list">
                                <tr><td colspan="4" style="text-align:center; padding: 30px; color: var(--text-secondary);">Načítavam maily alebo schránka nie je pripojená...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>