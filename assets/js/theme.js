/**
 * VoľnéKreslo - Globálny Správca Motívov (Svetlý / Tmavý režim)
 * Verzia 2.0 - Plná synchronizácia medzi všetkými podstránkami
 */

(function() {
    'use strict';

    // Kľúče pre úložisko
    const STORAGE_KEY_1 = 'vk_theme';
    const STORAGE_KEY_2 = 'theme';

    /**
     * Získaj aktuálne nastavenú tému
     * @returns {'dark'|'light'}
     */
    function getCurrentTheme() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY_1) || localStorage.getItem(STORAGE_KEY_2);
            if (saved === 'dark') return 'dark';
            return 'light'; // Predvolený je svetlý režim
        } catch(e) {
            return 'light';
        }
    }

    /**
     * Aplikuj zvolený motív na stránku
     * @param {'dark'|'light'} theme 
     */
    function applyTheme(theme) {
        const isDark = (theme === 'dark');
        
        if (isDark) {
            document.documentElement.classList.add('dark-mode');
            if (document.body) document.body.classList.add('dark-mode');
        } else {
            document.documentElement.classList.remove('dark-mode');
            if (document.body) document.body.classList.remove('dark-mode');
        }

        try {
            localStorage.setItem(STORAGE_KEY_1, isDark ? 'dark' : 'light');
            localStorage.setItem(STORAGE_KEY_2, isDark ? 'dark' : 'light');
        } catch(e) {}

        updateAllThemeIcons(isDark);
    }

    /**
     * Aktualizuj ikony a tooltipy všetkých prepínačov na stránke
     * @param {boolean} isDark 
     */
    function updateAllThemeIcons(isDark) {
        // Hľadáme všetky tlačidlá prepínania témy
        const buttons = document.querySelectorAll('#theme-toggle, [id^="theme-toggle"], .theme-toggle-btn, button[onclick*="toggleDark"], button[onclick*="toggleTheme"], button[onclick*="toggleAppTheme"]');
        
        buttons.forEach(btn => {
            // Preskočiť tlačidlá, ktoré nie sú prepínače témy
            if (btn.id === 'notif-btn' || btn.id === 'notif-sound-btn' || btn.classList.contains('logout-btn') || btn.classList.contains('login-btn')) {
                return;
            }

            const icon = btn.querySelector('.material-symbols-outlined, .material-icons, span');
            if (icon) {
                // Keď je tmavý režim -> ikona je slnko (light_mode) pre prepnutie na svetlý
                // Keď je svetlý režim -> ikona je mesiac (dark_mode) pre prepnutie na tmavý
                icon.textContent = isDark ? 'light_mode' : 'dark_mode';
            }

            btn.setAttribute('title', isDark ? 'Prepnúť na svetlý režim' : 'Prepnúť na tmavý režim');
            btn.setAttribute('aria-label', isDark ? 'Prepnúť na svetlý režim' : 'Prepnúť na tmavý režim');
        });
    }

    /**
     * Prepnúť motív (zavolať pri kliknutí)
     */
    function toggleAppTheme(e) {
        if (e && e.preventDefault) e.preventDefault();
        const current = getCurrentTheme();
        const nextTheme = (current === 'dark') ? 'light' : 'dark';
        applyTheme(nextTheme);
        return nextTheme;
    }

    // Exponovanie do globálneho okna (window)
    window.getCurrentTheme = getCurrentTheme;
    window.applyTheme = applyTheme;
    window.toggleAppTheme = toggleAppTheme;
    window.toggleDarkMode = toggleAppTheme; // Spätná kompatibilita
    window.toggleTheme = toggleAppTheme;    // Spätná kompatibilita

    // Inicializácia pri načítaní DOM
    function initTheme() {
        const theme = getCurrentTheme();
        applyTheme(theme);

        // Priradiť event listenery na všetky tlačidlá
        const buttons = document.querySelectorAll('#theme-toggle, [id^="theme-toggle"], .theme-toggle-btn, button[onclick*="toggleDark"], button[onclick*="toggleTheme"], button[onclick*="toggleAppTheme"]');
        
        buttons.forEach(btn => {
            if (btn.id === 'notif-btn' || btn.id === 'notif-sound-btn' || btn.classList.contains('logout-btn') || btn.classList.contains('login-btn')) {
                return;
            }
            // Odstrániť prípadné staré listenery a priradiť nový
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleAppTheme(e);
            };
        });
    }

    // Okamžitá aplikácia na <html> pred vykreslením DOM
    const initialTheme = getCurrentTheme();
    if (initialTheme === 'dark') {
        document.documentElement.classList.add('dark-mode');
    } else {
        document.documentElement.classList.remove('dark-mode');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }
})();

// Funkcia pre pridávanie do obľúbených (zdieľaná funkcionalita)
async function toggleFavorite(estId, btnElement) {
    if (!btnElement) return;
    const fd = new FormData();
    fd.append('action', 'toggle_favorite');
    fd.append('establishment_id', estId);
    
    try {
        let res = await fetch('api/customer.php', { method: 'POST', body: fd });
        let data = await res.json();
        
        if (data.success) {
            let icon = btnElement.querySelector('span.material-symbols-outlined');
            if (icon) {
                if (data.is_favorite) {
                    icon.style.color = '#e74c3c';
                    icon.classList.add('favorite-active');
                } else {
                    icon.style.color = '';
                    icon.classList.remove('favorite-active');
                }
            }
        }
    } catch(e) {
        console.error(e);
    }
}
