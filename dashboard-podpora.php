<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
$pageTitle = 'Podpora - ' . BRAND_NAME;
$currentPage = 'podpora';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Podpora'; $headerIcon = 'help'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">
      <div class="vueto-card">
        <div class="vueto-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 18px;">
          <div>
            <h2 class="section-header" style="margin: 0;">
              <span class="material-symbols-outlined" style="color: var(--primary-color);">headset_mic</span>
              Kontaktovať podporu
            </h2>
            <p class="section-desc" style="margin-top: 5px;">Máte otázku alebo technický problém? Napíšte nám a my vám odpovieme priamo na váš e-mail, ktorý máte v profile.</p>
          </div>
        </div>
        <div class="vueto-card-body" style="padding: 24px 20px;">

      <!-- FAQ sekcia -->
      <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px;margin-bottom:25px;">
        <h3 style="margin:0 0 14px 0;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--primary-color);">quiz</span> Časté otázky
        </h3>
        <div style="display:flex;flex-direction:column;gap:0;">
          <?php
          $faqs = [
            ['Ako môžem zmeniť svoje heslo?', 'Prejdite do sekcie Zabezpečenie → Zmena hesla. Budete potrebovať aktuálne heslo a overovací kód zaslaný na váš e-mail.'],
            ['Kde nájdem moje fakturácie a platby?', 'V sekcii Môj Balík nájdete prehľad aktívneho predplatného, históriu platieb a možnosť upgradovania balíka.'],
            ['Ako funguje Peňaženka a kredit?', 'Kredit v Peňaženke slúži na platenie za prémiové funkcie (topovanie prevádzky, SMS balíčky). Kredit nemá expiráciu a môžete ho dobiť kedykoľvek.'],
            ['Prečo mi zákazníci nedostávajú e-mailové potvrdenia?', 'Skontrolujte nastavenia e-mailu v sekcii Prevádzka. Ak problém pretrváva, kontaktujte nás prostredníctvom formulára nižšie.'],
            ['Ako zruším predplatné?', 'Kontaktujte nás cez formulár nižšie alebo e-mailom. Zrušenie je bezplatné a bez viazanosti, pričom prístup zostane aktívny do konca plateného obdobia.'],
          ];
          foreach ($faqs as $idx => $faq): ?>
          <div style="border-bottom:1px solid var(--border-color);">
            <button type="button" onclick="toggleFaq(<?= $idx ?>)" style="width:100%;text-align:left;background:transparent;border:none;padding:14px 0;cursor:pointer;display:flex;justify-content:space-between;align-items:center;color:var(--text-primary);font-size:14px;font-weight:600;">
              <span><?= htmlspecialchars($faq[0]) ?></span>
              <span class="material-symbols-outlined" id="faq-icon-<?= $idx ?>" style="font-size:20px;color:var(--primary-color);transition:transform 0.2s;">expand_more</span>
            </button>
            <div id="faq-body-<?= $idx ?>" style="display:none;padding:0 0 14px 0;font-size:13.5px;color:var(--text-secondary);line-height:1.5;">
              <?= htmlspecialchars($faq[1]) ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Kontaktný formulár -->
      <form id="supportForm" onsubmit="sendSupport(event)">
        <div class="form-group">
          <label>Predmet</label>
          <input type="text" id="supp-subject" required placeholder="Napr. Problém s kalendárom">
        </div>
        <div class="form-group">
          <label>Kategória</label>
          <select id="supp-category" style="width:100%;padding:12px 15px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);border-radius:10px;font-size:14px;box-sizing:border-box;margin-bottom:4px;">
            <option value="general">Všeobecná otázka</option>
            <option value="technical">Technický problém</option>
            <option value="billing">Platba / Predplatné</option>
            <option value="booking">Rezervácie / Kalendár</option>
            <option value="feature">Návrh novej funkcie</option>
            <option value="other">Iné</option>
          </select>
        </div>
        <div class="form-group">
          <label>Správa</label>
          <textarea id="supp-message" rows="6" required placeholder="Rozpíšte váš problém alebo dotaz čo najpodrobnejšie..." style="width:100%;padding:12px 15px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);border-radius:10px;box-sizing:border-box;resize:vertical;font-size:14px;"></textarea>
        </div>
        <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
          <button type="submit" class="btn-primary" id="supp-btn" style="border-radius:12px;padding:12px 28px;font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:18px;">send</span>
            Odoslať správu
          </button>
          <p id="supp-msg" style="margin:0;display:none;font-size:13.5px;font-weight:600;"></p>
        </div>
      </form>

      <!-- Priamy kontakt -->
      <div style="margin-top:30px;padding-top:25px;border-top:1px solid var(--border-color);">
        <h3 style="margin:0 0 14px 0;font-size:16px;font-weight:700;">Priamy kontakt</h3>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <a href="mailto:support@<?= BRAND_SITE ?>" style="display:inline-flex;align-items:center;gap:8px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:12px 18px;color:var(--text-primary);text-decoration:none;font-size:14px;font-weight:600;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">mail</span>
            support@<?= BRAND_SITE ?>
          </a>
          <a href="https://<?= BRAND_SITE ?>/faq" target="_blank" style="display:inline-flex;align-items:center;gap:8px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:12px 18px;color:var(--text-primary);text-decoration:none;font-size:14px;font-weight:600;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">open_in_new</span>
            Online FAQ
          </a>
        </div>
      </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function toggleFaq(idx) {
  const body = document.getElementById('faq-body-'+idx);
  const icon = document.getElementById('faq-icon-'+idx);
  const isOpen = body.style.display !== 'none';
  body.style.display = isOpen ? 'none' : 'block';
  icon.style.transform = isOpen ? '' : 'rotate(180deg)';
}

async function sendSupport(e) {
  e.preventDefault();
  const btn = document.getElementById('supp-btn');
  const msg = document.getElementById('supp-msg');
  btn.disabled = true;
  btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">sync</span> Odosielam...';
  try {
    const fd = new FormData();
    fd.append('action', 'send_support');
    fd.append('subject', document.getElementById('supp-subject').value);
    fd.append('category', document.getElementById('supp-category').value);
    fd.append('message', document.getElementById('supp-message').value);
    const res = await fetch('api/business.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      msg.style.display = 'block';
      msg.style.color = '#10b981';
      msg.innerText = data.message || 'Správa bola odoslaná! Odpovieme vám na váš e-mail.';
      document.getElementById('supportForm').reset();
    } else {
      msg.style.display = 'block';
      msg.style.color = '#ef4444';
      msg.innerText = data.error || 'Chyba pri odosielaní. Skúste neskôr alebo nás kontaktujte priamo.';
    }
  } catch(err) {
    msg.style.display = 'block';
    msg.style.color = '#ef4444';
    msg.innerText = 'Chyba pripojenia k serveru.';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">send</span> Odoslať správu';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.body.classList.contains('dark-mode');
  const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
});
function init() {}
</script>
</body>
</html>
