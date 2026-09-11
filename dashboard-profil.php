<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('settings');
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
$pageTitle = 'Profil a Galéria - ' . BRAND_NAME;
$currentPage = 'profil';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Profil a Galéria'; $headerIcon = 'storefront'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <?php include 'components/profile.php'; ?>
  </div>
</div>

<!-- YouTube Help Modal -->
<div id="modal-youtube-help" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:3000;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border-radius:16px;padding:28px;max-width:460px;width:90%;border:1px solid var(--border-color);">
    <h3 style="margin:0 0 12px 0;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#ff0000;">smart_display</span> Ako pridať YouTube video?</h3>
    <ol style="margin:0;padding-left:20px;font-size:14px;color:var(--text-secondary);line-height:1.7;">
      <li>Otvorte YouTube a nájdite video, ktoré chcete pridať.</li>
      <li>Kliknite na <strong>Zdieľať</strong> pod videom.</li>
      <li>Skopírujte odkaz (napr. <code>https://youtu.be/abc123</code>).</li>
      <li>Vložte odkaz do poľa YouTube URL vyššie.</li>
    </ol>
    <button type="button" onclick="closeYoutubeHelpModal()" class="btn-primary" style="margin-top:20px;width:100%;">Rozumiem</button>
  </div>
</div>

<div class="app-toast-container" id="app-toast-container"></div>
<script>
let hasProfile = false;
let g_profileTier = 'free';
let g_hasCustomBanner = false;

const cityInput = document.getElementById('prof-city');
const cityAutocomplete = document.getElementById('city-autocomplete');
let cityTimeout = null;
if (cityInput && cityAutocomplete) {
    cityInput.addEventListener('input', function() {
        clearTimeout(cityTimeout);
        let val = this.value.trim();
        if (val.length < 2) { cityAutocomplete.style.display = 'none'; return; }
        cityTimeout = setTimeout(async () => {
            const res = await fetch('api/search_locations.php?q=' + encodeURIComponent(val));
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(loc => {
                    html += `<div class="ac-item" onclick="selectCity('${loc.city_name}','${loc.zip_code}')">${loc.city_name} (${loc.zip_code})</div>`;
                });
                cityAutocomplete.innerHTML = html;
                cityAutocomplete.style.display = 'block';
            } else { cityAutocomplete.style.display = 'none'; }
        }, 300);
    });
    document.addEventListener('click', function(e) {
        if (e.target !== cityInput && !cityAutocomplete.contains(e.target)) cityAutocomplete.style.display = 'none';
    });
}
function selectCity(city, zip) { if (cityInput) cityInput.value = city; if (cityAutocomplete) cityAutocomplete.style.display = 'none'; }

function handleAvatarClick(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    if ((g_profileTier || 'free').toLowerCase() === 'free') { showAppToast('Nahratie vlastného loga je dostupné od balíka START, PRO a VIP.', 'info'); return false; }
    const f = document.getElementById('prof-avatar'); if (f) f.click();
}
function handleBannerClick(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    let t = (g_profileTier || 'free').toLowerCase();
    if (t === 'free' || t === 'start') { showAppToast('Vlastný titulný banner je dostupný od balíka PRO a VIP.', 'info'); return false; }
    const f = document.getElementById('prof-banner'); if (f) f.click();
}
async function handleAvatarUploadChange(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 6 * 1024 * 1024) { showAppToast('Maximálna povolená veľkosť loga je 6 MB.', 'error'); input.value = ''; return; }
    const reader = new FileReader();
    reader.onload = function(e) { let avBg = document.getElementById('avatar-bg'); if (avBg) { avBg.style.backgroundImage = 'url(' + e.target.result + ')'; avBg.style.boxShadow = '0 0 0 4px var(--primary-color)'; } };
    reader.readAsDataURL(file);
    showAppToast('Nahrávam logo prevádzky...', 'info');
    const fd = new FormData(); fd.append('action', 'upload_avatar'); fd.append('avatar', file);
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd }); let data = await res.json();
        if (data.success) { let avBg = document.getElementById('avatar-bg'); if (avBg) { avBg.style.backgroundImage = 'url(' + data.avatar_url + ')'; avBg.style.boxShadow = ''; } showAppToast(data.message || 'Logo prevádzky bolo úspešne nahrané!', 'success'); if (window.ProfileConfigurator) ProfileConfigurator.refreshPreview(); }
        else showAppToast(data.message || 'Chyba pri nahrávaní loga.', 'error');
    } catch(err) { console.error(err); showAppToast('Chyba spojenia pri nahrávaní loga.', 'error'); }
    input.value = '';
}
async function handleBannerUploadChange(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 6 * 1024 * 1024) { showAppToast('Maximálna povolená veľkosť bannera je 6 MB.', 'error'); input.value = ''; return; }
    const reader = new FileReader(); reader.onload = function(e) { let b = document.getElementById('banner-bg'); if (b) b.style.backgroundImage = 'url(' + e.target.result + ')'; }; reader.readAsDataURL(file);
    showAppToast('Nahrávam titulný banner...', 'info');
    const fd = new FormData(); fd.append('action', 'upload_banner'); fd.append('banner', file);
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd }); let data = await res.json();
        if (data.success) {
            let b = document.getElementById('banner-bg'); if (b) b.style.backgroundImage = 'url(' + data.banner_url + ')';
            let ct = document.getElementById('banner-cat-name'); if (ct) ct.innerText = 'Vlastný banner prevádzky';
            let br = document.getElementById('btn-reset-banner'); if (br) br.style.display = 'inline-flex';
            g_hasCustomBanner = true; showAppToast(data.message || 'Titulný banner bol úspešne uložený!', 'success'); if (window.ProfileConfigurator) ProfileConfigurator.refreshPreview();
        } else showAppToast(data.message || 'Chyba pri nahrávaní bannera.', 'error');
    } catch(err) { console.error(err); showAppToast('Chyba spojenia pri nahrávaní bannera.', 'error'); }
    input.value = '';
}
async function resetToDefaultBanner(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    if (!(await confirmModal('Naozaj chcete obnoviť predvolený banner podľa kategórie?'))) return;
    const fd = new FormData(); fd.append('action', 'reset_banner');
    try {
        let res = await fetch('api/business.php', { method: 'POST', body: fd }); let data = await res.json();
        if (data.success) {
            g_hasCustomBanner = false;
            let mc = document.getElementById('prof-main-cat')?.value || '';
            let di = CATEGORY_DEFAULT_IMAGES[mc] || 'assets/img/cat_hair.png';
            let b = document.getElementById('banner-bg'); if (b) b.style.backgroundImage = 'url(' + di + ')';
            let ct = document.getElementById('banner-cat-name'); if (ct) ct.innerText = 'Predvolený: ' + (mc || 'Podľa kategórie');
            let br = document.getElementById('btn-reset-banner'); if (br) br.style.display = 'none';
            showAppToast(data.message || 'Banner bol obnovený na predvolený.', 'success');
        }
    } catch(err) { console.error(err); }
}
const CATEGORY_DEFAULT_IMAGES = {
    'Vlasy': 'assets/img/cat_hair.png', 'Holičstvo a Barber': 'assets/img/barber_shop.png',
    'Barber': 'assets/img/barber_shop.png', 'Holičstvo': 'assets/img/barber_shop.png',
    'Kozmetika a make-up': 'assets/img/cat_makeup.png', 'Make-up': 'assets/img/cat_makeup.png',
    'Starostlivosť o pleť': 'assets/img/cat_skincare.png', 'Nechty': 'assets/img/cat_nails.png',
    'Obočie a mihalnice': 'assets/img/cat_brows.png', 'Obočie a riasy': 'assets/img/cat_brows.png',
    'Masáže a wellness': 'assets/img/cat_wellness.png', 'Masáž': 'assets/img/spa_massage.png',
    'Wellness a kúpele': 'assets/img/cat_wellness.png', 'Vrkoče a dredy': 'assets/img/cat_braids.png',
    'Tetovanie': 'assets/img/cat_tattoo.png', 'Lekárska estetika': 'assets/img/cat_medical.png',
    'Depilácia a epilácia': 'assets/img/cat_depilation.png', 'Domáce služby': 'assets/img/cat_home.png',
    'Piercing': 'assets/img/cat_piercing.png', 'Služby pre miláčikov': 'assets/img/cat_pets.png',
    'Zubné a ortodontické': 'assets/img/cat_dentistry.png', 'Zdravie a kondícia': 'assets/img/cat_fitness.png',
    'Profesionálne služby': 'assets/img/cat_proservices.png', 'Solárium a opaľovanie': 'assets/img/cat_solarium.png',
    'Joga a Pilates': 'assets/img/cat_yoga.png', 'Fyzioterapia': 'assets/img/cat_physio.png',
    'Osobní tréneri': 'assets/img/cat_trainer.png', 'Výživové poradenstvo': 'assets/img/cat_nutrition.png',
    'Svadobné služby': 'assets/img/cat_wedding.png', 'Alternatívna medicína': 'assets/img/cat_altmedicine.png',
    'Psychológia a Terapia': 'assets/img/cat_psychology.png', 'Iné': 'assets/img/cat_other.png'
};
function onMainCategoryChange(val) {
    if (val) { const cb = document.querySelector(`input[name="prof_cat[]"][value="${val}"]`); if (cb) cb.checked = true; }
    if (!g_hasCustomBanner) { let b = document.getElementById('banner-bg'), ct = document.getElementById('banner-cat-name'), di = CATEGORY_DEFAULT_IMAGES[val] || 'assets/img/cat_hair.png'; if (b) b.style.backgroundImage = 'url(' + di + ')'; if (ct) ct.innerText = 'Predvolený: ' + val; }
}
function switchVideoMode(num, mode) {
    const bf = document.getElementById('tab-btn-file-' + num), by = document.getElementById('tab-btn-yt-' + num), mf = document.getElementById('video-mode-file-' + num), my = document.getElementById('video-mode-yt-' + num);
    if (mode === 'file') { if (bf) { bf.style.background = 'var(--primary-color)'; bf.style.color = '#fff'; } if (by) { by.style.background = 'transparent'; by.style.color = 'var(--text-secondary)'; } if (mf) mf.style.display = 'block'; if (my) my.style.display = 'none'; }
    else { if (by) { by.style.background = 'var(--primary-color)'; by.style.color = '#fff'; } if (bf) { bf.style.background = 'transparent'; bf.style.color = 'var(--text-secondary)'; } if (mf) mf.style.display = 'none'; if (my) my.style.display = 'block'; }
}
function handleDirectVideoChange(num, input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 55 * 1024 * 1024) { showAppToast('Maximálna veľkosť videa je 50 MB.', 'error'); input.value = ''; return; }
        const blobUrl = URL.createObjectURL(file);
        const pl = document.getElementById('player-vidfile-' + num), pw = document.getElementById('preview-wrapper-vidfile-' + num),
              lb = document.getElementById('label-vidfile-' + num), bg = document.getElementById('badge-vidfile-' + num),
              br = document.getElementById('btn-remove-vidfile-' + num), ri = document.getElementById('prof-remove-vidfile-' + num);
        if (ri) ri.value = '0'; if (lb) lb.innerText = 'Vybrané: ' + file.name;
        if (bg) { bg.innerText = 'Pripravené na uloženie'; bg.style.background = 'rgba(16,185,129,0.15)'; bg.style.color = '#10b981'; }
        if (pl && pw) { pl.src = blobUrl; pw.style.display = 'block'; } if (br) br.style.display = 'inline-flex';
    }
}
function removeDirectVideo(num) {
    const fi = document.getElementById('prof-video-file-' + num), pl = document.getElementById('player-vidfile-' + num),
          pw = document.getElementById('preview-wrapper-vidfile-' + num), lb = document.getElementById('label-vidfile-' + num),
          bg = document.getElementById('badge-vidfile-' + num), br = document.getElementById('btn-remove-vidfile-' + num),
          ri = document.getElementById('prof-remove-vidfile-' + num);
    if (fi) fi.value = ''; if (ri) ri.value = '1'; if (lb) lb.innerText = 'Kliknite pre výber videa ' + num;
    if (bg) { bg.innerText = 'Prázdny'; bg.style.background = 'rgba(16,185,129,0.1)'; bg.style.color = '#10b981'; }
    if (pl) pl.src = ''; if (pw) pw.style.display = 'none'; if (br) br.style.display = 'none';
}
function extractYoutubeId(url) {
    if (!url) return null; url = url.trim();
    const m = url.match(/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=|shorts\/)([^#&?]*).*/);
    return (m && m[2].length === 11) ? m[2] : null;
}
function updateYoutubePreview(num) {
    const inp = document.getElementById('prof-video-yt-' + num), pw = document.getElementById('preview-wrapper-vidyt-' + num),
          ifr = document.getElementById('iframe-vidyt-' + num), bg = document.getElementById('badge-vidyt-' + num),
          br = document.getElementById('btn-remove-vidyt-' + num), ri = document.getElementById('prof-remove-vidyt-' + num);
    if (ri) ri.value = '0';
    const ytId = extractYoutubeId(inp ? inp.value : '');
    if (ytId) { if (ifr) ifr.src = 'https://www.youtube.com/embed/' + ytId; if (pw) pw.style.display = 'block'; if (bg) { bg.innerText = 'Aktívne video'; bg.style.background = 'rgba(255,0,0,0.15)'; bg.style.color = '#ff0000'; } if (br) br.style.display = 'inline-flex'; }
    else { if (ifr) ifr.src = ''; if (pw) pw.style.display = 'none'; if (bg) { bg.innerText = 'Prázdny'; bg.style.background = 'rgba(255,0,0,0.1)'; bg.style.color = '#ff0000'; } if (br) br.style.display = 'none'; }
}
function removeYoutubeVideo(num) {
    const inp = document.getElementById('prof-video-yt-' + num), pw = document.getElementById('preview-wrapper-vidyt-' + num),
          ifr = document.getElementById('iframe-vidyt-' + num), bg = document.getElementById('badge-vidyt-' + num),
          br = document.getElementById('btn-remove-vidyt-' + num), ri = document.getElementById('prof-remove-vidyt-' + num);
    if (inp) inp.value = ''; if (ri) ri.value = '1'; if (ifr) ifr.src = ''; if (pw) pw.style.display = 'none';
    if (bg) { bg.innerText = 'Prázdny'; bg.style.background = 'rgba(255,0,0,0.1)'; bg.style.color = '#ff0000'; } if (br) br.style.display = 'none';
}
function openYoutubeHelpModal() { const m = document.getElementById('modal-youtube-help'); if (m) m.style.display = 'flex'; }
function closeYoutubeHelpModal() { const m = document.getElementById('modal-youtube-help'); if (m) m.style.display = 'none'; }

function showSection(section) {
    if (section === 'billing') window.location.href = 'dashboard-balik.php';
    else if (section === 'team') window.location.href = 'dashboard-tym.php';
}

let g_galleryDragId = null;

async function loadGallery() {
    const fd = new FormData(); fd.append('action', 'get_gallery');
    try {
        let res = await fetch('api/gallery.php', { method: 'POST', body: fd }); let data = await res.json();
        if (data.success) renderGallery(data.gallery || [], data.limit || 0);
    } catch (err) { console.error(err); }
}

function renderGallery(items, limit) {
    const countEl = document.getElementById('gallery-count'), maxEl = document.getElementById('gallery-max');
    if (countEl) countEl.innerText = items.length;
    if (maxEl) maxEl.innerText = limit;
    const addBtn = document.getElementById('btn-add-photo');
    if (addBtn) addBtn.style.display = (limit > 0 && items.length >= limit) ? 'none' : 'inline-block';
    const grid = document.getElementById('gallery-grid');
    if (grid) {
        grid.innerHTML = items.map(item => {
            const inner = item.media_type === 'video'
                ? '<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#000;"><span class="material-symbols-outlined" style="color:#fff; font-size:32px;">smart_display</span></div>'
                : '<img src="' + item.media_url + '" style="width:100%; height:100%; object-fit:cover; pointer-events:none;" alt="Galéria">';
            return '<div class="gallery-item" draggable="true" data-id="' + item.id + '" style="position:relative; border-radius:10px; overflow:hidden; aspect-ratio:1; cursor:grab;">' + inner +
                '<button type="button" onclick="deleteGalleryItem(' + item.id + ')" style="position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.65); color:#fff; border:none; border-radius:6px; width:26px; height:26px; cursor:pointer; display:flex; align-items:center; justify-content:center;"><span class="material-symbols-outlined" style="font-size:16px;">close</span></button></div>';
        }).join('');
        initGalleryDragDrop(grid);
    }
    if (window.ProfileConfigurator) ProfileConfigurator.refreshChecklist();
}

function initGalleryDragDrop(grid) {
    const items = grid.querySelectorAll('.gallery-item');
    items.forEach(item => {
        item.addEventListener('dragstart', () => { g_galleryDragId = item.dataset.id; item.style.opacity = '0.4'; });
        item.addEventListener('dragend', () => { item.style.opacity = ''; });
        item.addEventListener('dragover', (e) => { e.preventDefault(); });
        item.addEventListener('drop', (e) => {
            e.preventDefault();
            const targetId = item.dataset.id;
            if (!g_galleryDragId || g_galleryDragId === targetId) return;
            const dragEl = grid.querySelector('[data-id="' + g_galleryDragId + '"]');
            if (!dragEl) return;
            const allItems = Array.from(grid.querySelectorAll('.gallery-item'));
            const dragIdx = allItems.indexOf(dragEl), targetIdx = allItems.indexOf(item);
            if (dragIdx < targetIdx) item.after(dragEl); else item.before(dragEl);
            saveGalleryOrder(grid);
        });
    });
}

async function saveGalleryOrder(grid) {
    const order = Array.from(grid.querySelectorAll('.gallery-item')).map(el => el.dataset.id);
    const fd = new FormData(); fd.append('action', 'reorder'); fd.append('order', JSON.stringify(order));
    try {
        let res = await fetch('api/gallery.php', { method: 'POST', body: fd }); let data = await res.json();
        if (!data.success) showAppToast(data.message || 'Nepodarilo sa uložiť poradie.', 'error');
    } catch (err) { console.error(err); showAppToast('Chyba spojenia pri ukladaní poradia.', 'error'); }
}

async function uploadGalleryImage(input) {
    if (!input.files || !input.files.length) return;
    const files = Array.from(input.files);
    let uploaded = 0, failed = 0, limitHit = false;
    showAppToast('Nahrávam fotky...', 'info');
    for (const file of files) {
        if (limitHit) break;
        if (file.size > 6 * 1024 * 1024) { failed++; continue; }
        const fd = new FormData(); fd.append('action', 'upload_media'); fd.append('file', file);
        try {
            let res = await fetch('api/gallery.php', { method: 'POST', body: fd }); let data = await res.json();
            if (data.success) uploaded++;
            else { failed++; if (data.limit_reached) limitHit = true; }
        } catch (err) { failed++; }
    }
    if (uploaded > 0) showAppToast(uploaded + ' ' + (uploaded === 1 ? 'fotka bola úspešne nahratá.' : 'fotiek bolo úspešne nahratých.'), 'success');
    if (limitHit) showAppToast('Dosiahli ste limit galérie pre Váš balík.', 'error');
    else if (failed > 0) showAppToast(failed + ' ' + (failed === 1 ? 'fotka sa nepodarilo nahrať.' : 'fotiek sa nepodarilo nahrať.'), 'error');
    loadGallery();
    input.value = '';
}

async function deleteGalleryItem(id) {
    if (!(await confirmModal('Naozaj chcete odstrániť túto položku z galérie?'))) return;
    const fd = new FormData(); fd.append('action', 'delete_media'); fd.append('id', id);
    try {
        let res = await fetch('api/gallery.php', { method: 'POST', body: fd }); let data = await res.json();
        if (data.success) { showAppToast(data.message || 'Položka bola odstránená.', 'success'); loadGallery(); }
        else showAppToast(data.message || 'Chyba pri odstraňovaní.', 'error');
    } catch (err) { console.error(err); showAppToast('Chyba spojenia.', 'error'); }
}

function openVideoModal() {
    if ((g_profileTier || 'free').toLowerCase() !== 'vip') { showAppToast('Video galéria je prístupná iba pre balík VIP.', 'info'); return; }
    const inp = document.getElementById('gallery-video-url'); if (inp) inp.value = '';
    const m = document.getElementById('video-modal'); if (m) m.style.display = 'flex';
}

async function saveGalleryVideo() {
    const inp = document.getElementById('gallery-video-url');
    const url = inp ? inp.value.trim() : '';
    if (!url) { showAppToast('Prosím, zadajte URL adresu videa.', 'error'); return; }
    const fd = new FormData(); fd.append('action', 'upload_media'); fd.append('video_url', url);
    try {
        let res = await fetch('api/gallery.php', { method: 'POST', body: fd }); let data = await res.json();
        if (data.success) {
            showAppToast(data.message || 'Video bolo pridané.', 'success');
            const m = document.getElementById('video-modal'); if (m) m.style.display = 'none';
            loadGallery();
        } else showAppToast(data.message || 'Chyba pri pridávaní videa.', 'error');
    } catch (err) { console.error(err); showAppToast('Chyba spojenia.', 'error'); }
}

async function loadProfile() {
    const fd = new FormData(); fd.append('action', 'get_profile');
    let res = await fetch('api/business.php', { method: 'POST', body: fd }); let data = await res.json();
    if (data.success && data.profile) {
        hasProfile = true; window.g_lastProfileData = data.profile;
        document.getElementById('prof-name').value = data.profile.name;
        let cats = data.profile.category ? data.profile.category.split(',').map(c => c.trim()) : [];
        let mainCat = cats.length > 0 ? cats[0] : '';
        let ms = document.getElementById('prof-main-cat'); if (ms) ms.value = mainCat;
        let hasOther = false, otherValue = '';
        document.querySelectorAll('input[name="prof_cat[]"]').forEach(cb => { cb.checked = cats.includes(cb.value); });
        cats.forEach(c => { if (c.startsWith('Iné')) { hasOther = true; if (c.includes('(')) otherValue = c.substring(c.indexOf('(') + 1, c.indexOf(')')); } });
        let otherCb = document.getElementById('cat-other-cb'), otherWrap = document.getElementById('cat-other-wrapper'), otherTxt = document.getElementById('cat-other-text');
        if (otherCb) { otherCb.checked = hasOther; if (otherWrap) otherWrap.style.display = hasOther ? 'block' : 'none'; if (otherTxt) otherTxt.value = otherValue; }
        let publicHandle = data.profile.custom_url || data.profile.public_id;
        if (publicHandle) {
            if (['start', 'pro', 'vip', 'premium'].includes((data.profile.subscription_tier || '').toLowerCase())) {
                let pl = document.getElementById('public-link'); if (pl) { pl.href = 'https://<?= BRAND_SITE ?>/@' + publicHandle; pl.textContent = '<?= BRAND_SITE ?>/@' + publicHandle; }
                let plb = document.getElementById('public-link-btn'); if (plb) plb.href = 'https://<?= BRAND_SITE ?>/@' + publicHandle;
                let plc = document.getElementById('public-link-container'); if (plc) plc.style.display = 'flex';
                let pll = document.getElementById('premium-lock-container'); if (pll) pll.style.display = 'none';
            } else {
                let plc = document.getElementById('public-link-container'); if (plc) plc.style.display = 'none';
                let pll = document.getElementById('premium-lock-container'); if (pll) pll.style.display = 'flex';
            }
            let ppl = document.getElementById('pcfg-preview-link-btn'); if (ppl) ppl.href = 'https://<?= BRAND_SITE ?>/@' + publicHandle;
        }
        if (data.profile.avatar_url) { let avBg = document.getElementById('avatar-bg'); if (avBg) avBg.style.backgroundImage = 'url(' + data.profile.avatar_url + ')'; }
        g_hasCustomBanner = !!data.profile.banner_url;
        let bnBg = document.getElementById('banner-bg'), catTag = document.getElementById('banner-cat-name');
        if (data.profile.banner_url) { if (bnBg) bnBg.style.backgroundImage = 'url(' + data.profile.banner_url + ')'; if (catTag) catTag.innerText = 'Vlastný banner prevádzky'; let br = document.getElementById('btn-reset-banner'); if (br) br.style.display = 'inline-flex'; }
        else { let di = CATEGORY_DEFAULT_IMAGES[mainCat] || 'assets/img/cat_hair.png'; if (bnBg) bnBg.style.backgroundImage = 'url(' + di + ')'; if (catTag) catTag.innerText = 'Predvolený: ' + (mainCat || 'Podľa kategórie'); let br = document.getElementById('btn-reset-banner'); if (br) br.style.display = 'none'; }
        const fieldMap = [['prof-address','address'],['prof-city','city'],['prof-phone','phone'],['prof-desc','description'],['prof-conf-mode','confirmation_mode'],['prof-custom-url','custom_url'],['prof-deposit-iban','deposit_iban'],['prof-social-ig','social_ig'],['prof-social-fb','social_fb'],['prof-website','social_web']];
        fieldMap.forEach(([id, key]) => { let el = document.getElementById(id); if (el) el.value = data.profile[key] || ''; });
        if (document.getElementById('prof-social-ws')) document.getElementById('prof-social-ws').value = data.profile.social_ws || '';
        if (document.getElementById('prof-social-tiktok')) document.getElementById('prof-social-tiktok').value = data.profile.social_tiktok || '';
        if (document.getElementById('prof-social-youtube')) document.getElementById('prof-social-youtube').value = data.profile.social_youtube || '';
        if (document.getElementById('prof-social-telegram')) document.getElementById('prof-social-telegram').value = data.profile.social_telegram || '';
        if (document.getElementById('prof-social-x')) document.getElementById('prof-social-x').value = data.profile.social_x || '';
        if (document.getElementById('prof-social-linkedin')) document.getElementById('prof-social-linkedin').value = data.profile.social_linkedin || '';
        if (document.getElementById('prof-contact-email')) document.getElementById('prof-contact-email').value = data.profile.contact_email || '';
        let tier = (data.profile.subscription_tier || 'free').toLowerCase(); g_profileTier = tier;
        let tl = ['free', 'start', 'pro', 'vip'].indexOf(tier); if (tl === -1) tl = (tier === 'premium') ? 3 : 0;
        let btnAddVideo = document.getElementById('btn-add-video'); if (btnAddVideo) btnAddVideo.style.display = (tier === 'vip') ? 'inline-block' : 'none';
        let showGallery = document.getElementById('show-gallery'); if (showGallery) showGallery.checked = data.profile.show_gallery === undefined ? true : !!parseInt(data.profile.show_gallery);
        let showVideos = document.getElementById('show-videos'); if (showVideos) showVideos.checked = data.profile.show_videos === undefined ? true : !!parseInt(data.profile.show_videos);
        let showGiftVouchers = document.getElementById('show-gift-vouchers'); if (showGiftVouchers) showGiftVouchers.checked = data.profile.show_gift_vouchers === undefined ? true : !!parseInt(data.profile.show_gift_vouchers);
        let showPackages = document.getElementById('show-packages'); if (showPackages) showPackages.checked = data.profile.show_packages === undefined ? true : !!parseInt(data.profile.show_packages);
        let showNewsletter = document.getElementById('show-newsletter'); if (showNewsletter) { showNewsletter.checked = !!parseInt(data.profile.show_newsletter || 0); showNewsletter.disabled = tl < 2; }
        let lockNewsletter = document.getElementById('lock-newsletter'); if (lockNewsletter) lockNewsletter.style.display = tl < 2 ? 'block' : 'none';
        if (window.ProfileConfigurator) ProfileConfigurator.initTierUI(['free','start','pro','vip'][tl] || 'free');
        const bb = document.getElementById('prof-branding-tier-badge'), bt = document.getElementById('prof-branding-tier-title'), bs = document.getElementById('prof-branding-tier-sub');
        if (bb) { if (tier==='vip'){bb.innerText='VIP (ELITE)';bb.style.background='rgba(176,128,66,0.2)';bb.style.color='#d4af37';} else if(tier==='pro'){bb.innerText='PRO';bb.style.background='rgba(139,92,246,0.2)';bb.style.color='#8b5cf6';} else if(tier==='start'){bb.innerText='START';bb.style.background='rgba(59,130,246,0.2)';bb.style.color='#3b82f6';} else{bb.innerText='FREE';bb.style.background='rgba(156,163,175,0.2)';bb.style.color='#9ca3af';} }
        if (bt && bs) { if(tier==='vip'){bt.innerHTML='Balík VIP (ELITE) – Kompletný branding aktívny';bs.innerHTML='Máte plný prístup k nahratiu vlastného loga prevádzky, titulného bannera, sociálnych sietí aj neobmedzeným funkciám.';}else if(tier==='pro'){bt.innerHTML='Balík PRO – Vlastné logo aj Titulný banner aktívne';bs.innerHTML='Máte povolené vlastné logo prevádzky, titulný banner (3:1), Last Minute ponuky aj sociálne siete.';}else if(tier==='start'){bt.innerHTML='Balík START – Vlastné logo prevádzky aktívne';bs.innerHTML='Máte povolené vlastné logo prevádzky. Titulný banner (3:1) je dostupný od balíka PRO a VIP.';}else{bt.innerHTML='Balík FREE – Základný profil (bez vlastného loga)';bs.innerHTML='Pre nahratie vlastného loga prevádzky je potrebný balík START, PRO alebo VIP. Banner sa nastavuje automaticky.';} }
        let v1 = document.getElementById('prof-video-file-1'), v2 = document.getElementById('prof-video-file-2');
        [1, 2].forEach(num => {
            const vf = data.profile['video_file_' + num], pl = document.getElementById('player-vidfile-' + num), pw = document.getElementById('preview-wrapper-vidfile-' + num),
                  lb = document.getElementById('label-vidfile-' + num), bg2 = document.getElementById('badge-vidfile-' + num),
                  br = document.getElementById('btn-remove-vidfile-' + num), ri = document.getElementById('prof-remove-vidfile-' + num);
            if (ri) ri.value = '0';
            if (vf && vf.trim() !== '') { if (pl && pw) { pl.src = vf; pw.style.display = 'block'; } if (lb) lb.innerText = 'Nahrané video ' + num; if (bg2) { bg2.innerText = 'Nahrané'; bg2.style.background = 'rgba(16,185,129,0.15)'; bg2.style.color = '#10b981'; } if (br) br.style.display = 'inline-flex'; }
            else { if (pw) pw.style.display = 'none'; if (lb) lb.innerText = 'Kliknite pre výber videa ' + num; if (bg2) { bg2.innerText = 'Prázdny'; bg2.style.background = 'rgba(16,185,129,0.1)'; bg2.style.color = '#10b981'; } if (br) br.style.display = 'none'; }
        });
        [1, 2].forEach(num => { const vy = data.profile['video_url_' + num], inp = document.getElementById('prof-video-yt-' + num), ri = document.getElementById('prof-remove-vidyt-' + num); if (ri) ri.value = '0'; if (inp) inp.value = vy || ''; updateYoutubePreview(num); });
        const setDisable = (id, disabled) => { let el = document.getElementById(id); if (el) el.disabled = disabled; };
        const setDisplay = (id, display) => { let el = document.getElementById(id); if (el) el.style.display = display; };
        setDisable('prof-conf-mode', tl < 1); setDisplay('lock-conf-mode', tl < 1 ? 'block' : 'none');
        ['prof-custom-url','prof-social-ig','prof-social-fb','prof-social-tiktok','prof-social-youtube','prof-social-telegram','prof-social-x','prof-social-linkedin','prof-contact-email','prof-website','prof-banner'].forEach(id => setDisable(id, tl < 2));
        setDisplay('lock-custom-url', tl < 2 ? 'block' : 'none'); setDisplay('lock-socials', tl < 2 ? 'block' : 'none');
        setDisplay('video-tier-locked', tl < 2 ? 'block' : 'none'); setDisplay('video-tier-active', tl < 2 ? 'none' : 'block');
        if (v1) v1.disabled = tl < 2; if (v2) v2.disabled = tl < 2;
        setDisable('prof-deposit-iban', tl < 3); setDisplay('lock-deposit-iban', tl < 3 ? 'block' : 'none');
        if (data.profile.opening_hours) {
            try {
                let oh = JSON.parse(data.profile.opening_hours);
                ['mon','tue','wed','thu','fri','sat','sun'].forEach(day => {
                    if (oh[day]) { document.getElementById('oh-'+day+'-start').value = oh[day].open || ''; document.getElementById('oh-'+day+'-end').value = oh[day].close || ''; document.getElementById('oh-'+day+'-break-start').value = oh[day].break_start || ''; document.getElementById('oh-'+day+'-break-end').value = oh[day].break_end || ''; }
                    else { document.getElementById('oh-'+day+'-start').value = ''; document.getElementById('oh-'+day+'-end').value = ''; document.getElementById('oh-'+day+'-break-start').value = ''; document.getElementById('oh-'+day+'-break-end').value = ''; }
                });
            } catch(e) { console.error('Chyba parsovania otváracích hodín'); }
        }
        if (window.ProfileConfigurator) ProfileConfigurator.refreshChecklist();
        let sa = document.getElementById('status-alert'); if (sa) sa.style.display = (data.profile.status === 'pending') ? 'block' : 'none';
    }
}

async function saveProfile(e) {
    e.preventDefault();
    let selectedCats = [];
    let mainCat = document.getElementById('prof-main-cat').value;
    if (!mainCat) { showAppToast('Prosím, vyberte hlavnú kategóriu.', 'error'); return; }
    selectedCats.push(mainCat);
    document.querySelectorAll('input[name="prof_cat[]"]:checked').forEach(cb => { if (cb.value !== mainCat) selectedCats.push(cb.value); });
    let otherCb = document.getElementById('cat-other-cb');
    if (otherCb && otherCb.checked) { let t = document.getElementById('cat-other-text').value.trim(); selectedCats.push(t ? 'Iné (' + t + ')' : 'Iné'); }
    const fd = new FormData();
    fd.append('action', 'save_profile'); fd.append('name', document.getElementById('prof-name').value); fd.append('category', selectedCats.join(', '));
    let avf = document.getElementById('prof-avatar').files[0]; if (avf) fd.append('avatar', avf);
    let bnf = document.getElementById('prof-banner').files[0]; if (bnf) fd.append('banner', bnf);
    fd.append('address', document.getElementById('prof-address').value); fd.append('city', document.getElementById('prof-city').value);
    fd.append('phone', document.getElementById('prof-phone').value); fd.append('description', document.getElementById('prof-desc').value);
    fd.append('confirmation_mode', document.getElementById('prof-conf-mode').value);
    fd.append('custom_url', document.getElementById('prof-custom-url').value);
    fd.append('deposit_iban', document.getElementById('prof-deposit-iban').value);
    fd.append('social_ig', document.getElementById('prof-social-ig').value); fd.append('social_fb', document.getElementById('prof-social-fb').value);
    if (document.getElementById('prof-social-ws')) fd.append('social_ws', document.getElementById('prof-social-ws').value);
    if (document.getElementById('prof-social-tiktok')) fd.append('social_tiktok', document.getElementById('prof-social-tiktok').value);
    if (document.getElementById('prof-social-youtube')) fd.append('social_youtube', document.getElementById('prof-social-youtube').value);
    if (document.getElementById('prof-social-telegram')) fd.append('social_telegram', document.getElementById('prof-social-telegram').value);
    if (document.getElementById('prof-social-x')) fd.append('social_x', document.getElementById('prof-social-x').value);
    if (document.getElementById('prof-social-linkedin')) fd.append('social_linkedin', document.getElementById('prof-social-linkedin').value);
    if (document.getElementById('prof-contact-email')) fd.append('contact_email', document.getElementById('prof-contact-email').value);
    fd.append('social_web', document.getElementById('prof-website').value);
    if (document.getElementById('prof-video-yt-1')) fd.append('video_url_1', document.getElementById('prof-video-yt-1').value.trim());
    if (document.getElementById('prof-video-yt-2')) fd.append('video_url_2', document.getElementById('prof-video-yt-2').value.trim());
    const vf1 = document.getElementById('prof-video-file-1')?.files?.[0]; if (vf1) fd.append('video_file_1', vf1);
    const vf2 = document.getElementById('prof-video-file-2')?.files?.[0]; if (vf2) fd.append('video_file_2', vf2);
    fd.append('remove_vidfile_1', document.getElementById('prof-remove-vidfile-1')?.value || '0');
    fd.append('remove_vidfile_2', document.getElementById('prof-remove-vidfile-2')?.value || '0');
    fd.append('remove_vidyt_1', document.getElementById('prof-remove-vidyt-1')?.value || '0');
    fd.append('remove_vidyt_2', document.getElementById('prof-remove-vidyt-2')?.value || '0');
    let oh = {};
    ['mon','tue','wed','thu','fri','sat','sun'].forEach(day => {
        let s = document.getElementById('oh-'+day+'-start').value, en = document.getElementById('oh-'+day+'-end').value,
            bs = document.getElementById('oh-'+day+'-break-start').value, be = document.getElementById('oh-'+day+'-break-end').value;
        if (s && en) { oh[day] = { open: s, close: en }; if (bs && be) { oh[day].break_start = bs; oh[day].break_end = be; } }
    });
    fd.append('opening_hours', JSON.stringify(oh));
    fd.append('show_gallery', document.getElementById('show-gallery')?.checked ? '1' : '0');
    fd.append('show_videos', document.getElementById('show-videos')?.checked ? '1' : '0');
    fd.append('show_gift_vouchers', document.getElementById('show-gift-vouchers')?.checked ? '1' : '0');
    fd.append('show_packages', document.getElementById('show-packages')?.checked ? '1' : '0');
    fd.append('show_newsletter', document.getElementById('show-newsletter')?.checked ? '1' : '0');
    let res = await fetch('api/business.php', { method: 'POST', body: fd }); let data = await res.json();
    if (data.success) {
        let pm = document.getElementById('prof-msg'); if (pm) { pm.style.display = 'block'; setTimeout(() => pm.style.display = 'none', 3000); }
        showAppToast('Zmeny profilu a logo boli úspešne uložené!', 'success');
        const avBg = document.getElementById('avatar-bg'); if (avBg) avBg.style.boxShadow = '';
        if (data.avatar_url && avBg) avBg.style.backgroundImage = 'url(' + data.avatar_url + ')';
        if (data.banner_url) { let b = document.getElementById('banner-bg'); if (b) b.style.backgroundImage = 'url(' + data.banner_url + ')'; }
        hasProfile = true; loadProfile();
        if (window.ProfileConfigurator) { ProfileConfigurator.refreshPreview(); ProfileConfigurator.refreshChecklist(); }
    } else showAppToast(data.message || 'Nepodarilo sa uložiť zmeny profilu.', 'error');
}

function toggleTheme() { document.body.classList.toggle('dark-mode'); const d = document.body.classList.contains('dark-mode'); localStorage.setItem('theme', d ? 'dark' : 'light'); document.getElementById('theme-icon').textContent = d ? 'dark_mode' : 'light_mode'; }
function loadPageData() { loadProfile(); loadGallery(); }
document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.body.classList.contains('dark-mode');
    const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
    loadPageData();
});
</script>
</body>
</html>
