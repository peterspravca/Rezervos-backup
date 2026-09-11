<div id="sec-dashboard" class="section active">
            
            <div class="welcome-card" style="background: var(--card-bg); padding: 25px; border-radius: 16px; margin-bottom: 30px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div>
                    <h2 style="margin: 0 0 5px 0; color: var(--text-primary); font-size: 24px; display: flex; align-items: center; gap: 8px;">Vitaj, <?= $user_name ?>! <span class="material-symbols-outlined" style="color: #d4af37;">waving_hand</span></h2>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 15px;">Dnes je <strong id="current-date-sk"></strong>. Prajeme ti úspešný deň a veľa skvelých termínov!</p>
                </div>
                <div class="calendar-icon" style="background: rgba(212,175,55,0.1); width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #d4af37;">
                    <span class="material-symbols-outlined" style="font-size: 30px;">calendar_month</span>
                </div>
            </div>
            <script>
                const dateOpts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('current-date-sk').innerText = new Date().toLocaleDateString('sk-SK', dateOpts);
            </script>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">event</span></div>
                    <div class="stat-info">
                        <h4>Nadchádzajúce termíny</h4>
                        <p id="stat-upcoming">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">history</span></div>
                    <div class="stat-info">
                        <h4>Absolvované termíny</h4>
                        <p id="stat-completed">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-symbols-outlined">favorite</span></div>
                    <div class="stat-info">
                        <h4>Obľúbené prevádzky</h4>
                        <p id="stat-favorites">0</p>
                    </div>
                </div>
            </div>

            <div class="admin-panel">
                <h2>Môj najbližší termín</h2>
                <div id="next-booking-container">
                    <p>Zatiaľ nemáš žiadny nadchádzajúci termín. <br><br> <a href="prevadzky.php" class="btn" style="display:inline-block; text-decoration:none;">Nájsť prevádzku</a></p>
                </div>
            </div>
        </div>