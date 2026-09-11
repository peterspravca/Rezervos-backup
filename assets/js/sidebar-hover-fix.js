// assets/js/sidebar-hover-fix.js
// Bočný panel appky sa v zbalenom stave rozbaľuje len cez CSS :hover (64px -> 250px). Dva samostatné
// problémy pôsobia, akoby sa panel "zavrel spod myši":
// 1) Bežný pohyb myšou (najmä smerom nižšie k položkám) môže na okamih vybočiť mimo úzky 64px
//    pruh -> panel sa okamžite zbalí skôr, než sa stihne kliknúť. Rieši sa 300ms záchrannou pauzou.
// 2) Kliknutie na položku menu (kurzor je vtedy nad ROZBALENÝM 250px panelom) spôsobí načítanie
//    novej stránky. Tá sa vykreslí so ZBALENÝM 64px panelom - kurzor fyzicky ostáva na tom istom
//    mieste, ale to miesto už nie je nad užším pruhom, takže :hover sa po novom načítaní nesplní
//    a panel sa javí, že sa "zavrel spod myši", hoci sa myš vôbec nepohla. Rieši sa tak, že si pri
//    mouseenter zapamätáme (sessionStorage) že bol panel rozbalený, po novom načítaní ho hneď
//    rozbalíme, a prvým skutočným pohybom myši overíme, či tam kurzor naozaj ešte je.
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.querySelector('.admin-sidebar');
    if (!sidebar) return;

    var STORAGE_KEY = 'sidebarPinnedOpen';
    var collapseTimer = null;

    if (sessionStorage.getItem(STORAGE_KEY) === '1') {
        sidebar.classList.add('js-pinned-open');

        var verifyCursorStillOver = function (e) {
            document.removeEventListener('mousemove', verifyCursorStillOver);
            var rect = sidebar.getBoundingClientRect();
            var stillOver = e.clientX >= rect.left && e.clientX <= rect.right &&
                e.clientY >= rect.top && e.clientY <= rect.bottom;
            if (!stillOver) {
                sidebar.classList.remove('js-pinned-open');
                sessionStorage.removeItem(STORAGE_KEY);
            }
        };
        document.addEventListener('mousemove', verifyCursorStillOver);
    }

    sidebar.addEventListener('mouseenter', function () {
        clearTimeout(collapseTimer);
        sidebar.classList.add('js-pinned-open');
        sessionStorage.setItem(STORAGE_KEY, '1');
    });

    sidebar.addEventListener('mouseleave', function () {
        collapseTimer = setTimeout(function () {
            sidebar.classList.remove('js-pinned-open');
            sessionStorage.removeItem(STORAGE_KEY);
        }, 300);
    });
});
