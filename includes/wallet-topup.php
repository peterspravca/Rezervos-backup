<?php
// includes/wallet-topup.php
// Kompaktný widget na dobitie Peňaženky (users.credit) — pre zákazníka a admina.
// Používa api/wallet.php akciu 'get_balance' a api/stripe_checkout.php akciu 'create_topup_session'
// (skutočné dobitie kartou ide cez Stripe Checkout, kredit sa pripíše až vo webhooku po potvrdenej platbe).
// Vlastný JS namespace (window.WalletTopup), aby sa neprebíjal s JS hostiteľskej stránky.
// Voliteľné premenné pred includom:
// - $wt_inzercia_url — kam vedie odkaz "Moje inzeráty" (moj_profil-inzercia.php / admin-inzercia.php)
// - $wt_hide_topup — true skryje kartu "Dobiť kartou" (admin si kredit pridáva priamo cez
//   admin.php -> "Spravovať balík a peňaženku", nepotrebuje platiť sám sebe kartou cez Stripe)
$wt_inzercia_url = $wt_inzercia_url ?? 'moj_profil-inzercia.php';
$wt_hide_topup = $wt_hide_topup ?? false;
if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php';
?>
<div class="wt-wrap">
  <div class="wt-row">
    <div class="inz-card wt-col-wallet">
      <div style="padding:22px;">
        <div class="wt-balance-card" style="position:relative;">
          <button type="button" onclick="WalletTopup.load()" class="inz-btn-secondary" title="Aktualizovať zostatok"
                  style="position:absolute;top:18px;right:20px;width:34px;height:34px;padding:0;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:rgba(255,255,255,0.18);border-color:rgba(255,255,255,0.3);color:#fff;">
            <span class="material-symbols-outlined" style="font-size:18px;">refresh</span>
          </button>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;opacity:0.75;margin-bottom:6px;">Zostatok</div>
          <div id="wt-balance" style="font-size:30px;font-weight:900;line-height:1;">— €</div>
        </div>
        <div style="display:flex;gap:8px;margin-top:10px;">
          <div style="flex:1;background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:9px 12px;">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-secondary);display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">credit_card</span>Dobité kartou</div>
            <div id="wt-purchased" style="font-size:15px;font-weight:800;color:var(--text-primary);margin-top:2px;">— €</div>
          </div>
          <div style="flex:1;background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:9px 12px;">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-secondary);display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">share</span>Za zdieľanie</div>
            <div id="wt-earned" style="font-size:15px;font-weight:800;color:var(--text-primary);margin-top:2px;">— €</div>
          </div>
        </div>

        <div style="display:flex;align-items:flex-start;gap:8px;background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:10px 12px;margin-top:10px;">
          <span class="material-symbols-outlined" style="font-size:16px;color:#10b981;flex-shrink:0;margin-top:1px;">info</span>
          <div style="font-size:11.5px;color:var(--text-secondary);line-height:1.5;">
            <strong style="color:var(--text-primary);">Ako to funguje:</strong> pri platbe za inzerát a jeho topovanie sa najprv použije váš <strong>kredit zo zdieľania</strong>, a až keď nestačí, zvyšok sa doplatí z <strong>reálne dobitých eur</strong>.
          </div>
        </div>

        <?php if (!$wt_hide_topup): ?>
        <p style="margin:18px 0 10px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-secondary);">Zvoľte sumu na dobitie</p>
        <div id="wt-amount-buttons" class="wt-pill-grid">
          <button type="button" class="wt-pill wt-pill-active" onclick="WalletTopup.selectAmount(5,this)">5 €</button>
          <button type="button" class="wt-pill" onclick="WalletTopup.selectAmount(10,this)">10 €</button>
          <button type="button" class="wt-pill" onclick="WalletTopup.selectAmount(20,this)">20 €</button>
          <button type="button" class="wt-pill" onclick="WalletTopup.selectAmount(50,this)">50 €</button>
        </div>

        <button type="button" id="wt-submit-btn" onclick="WalletTopup.submit()" class="inz-btn-primary wt-submit-btn">
          <span class="material-symbols-outlined" style="font-size:18px;">add_card</span>
          <span id="wt-submit-btn-text">Dobiť 5,00 €</span>
        </button>

        <p style="margin:12px 0 0;font-size:11.5px;color:var(--text-secondary);text-align:center;">Kredit slúži na platbu za inzeráty a ich topovanie.</p>
        <?php else: ?>
        <div style="margin-top:18px;background:rgba(0,0,0,0.03);border:1px solid var(--border-color);border-radius:10px;padding:12px 14px;font-size:12px;color:var(--text-secondary);line-height:1.5;">
          Ako admin si kredit pridávaš priamo cez <strong style="color:var(--text-primary);">Správa používateľov → ikona peňaženky → Spravovať balík a peňaženku</strong>, bez platby kartou.
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="wt-col-share">
      <div class="inz-card">
        <div style="padding:22px;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:6px;">
            <h2 style="margin:0;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;color:var(--text-primary);">
              <span class="material-symbols-outlined" style="color:var(--primary-color);">devices</span>
              Zdieľajte stránku
            </h2>
            <span id="wt-share-app-badge" style="font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.25);white-space:nowrap;">…</span>
          </div>
          <p style="margin:0 0 16px;font-size:11.5px;color:var(--text-secondary);line-height:1.4;">Zdieľajte <?= BRAND_SITE ?> s priateľmi a získajte +0,10 kredit do Peňaženky.</p>
          <div style="display:flex;gap:6px;margin-bottom:6px;">
            <button type="button" onclick="WalletTopup.shareApp('facebook')" style="flex:1;background:rgba(24,119,242,0.08);border:1px solid rgba(24,119,242,0.25);color:#1877f2;border-radius:7px;padding:9px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">Facebook</button>
            <button type="button" onclick="WalletTopup.shareApp('whatsapp')" style="flex:1;background:rgba(37,211,102,0.08);border:1px solid rgba(37,211,102,0.25);color:#25d366;border-radius:7px;padding:9px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">WhatsApp</button>
          </div>
          <div style="display:flex;gap:6px;">
            <button type="button" onclick="WalletTopup.openInstagramModal()" style="flex:1;background:rgba(225,48,108,0.08);border:1px solid rgba(225,48,108,0.25);color:#e1306c;border-radius:7px;padding:9px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">Instagram</button>
            <button type="button" onclick="WalletTopup.shareApp('x')" style="flex:1;background:rgba(0,0,0,0.06);border:1px solid rgba(0,0,0,0.2);color:var(--text-primary);border-radius:7px;padding:9px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">X (Twitter)</button>
          </div>
        </div>
      </div>

      <div class="inz-card">
        <div style="padding:22px;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:6px;">
            <h2 style="margin:0;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;color:var(--text-primary);">
              <span class="material-symbols-outlined" style="color:var(--primary-color);">campaign</span>
              Zdieľajte svoje inzeráty
            </h2>
            <span id="wt-share-listing-badge" style="font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.25);white-space:nowrap;">…</span>
          </div>
          <p style="margin:0 0 16px;font-size:11.5px;color:var(--text-secondary);line-height:1.4;">Za zdieľanie vlastného inzerátu získate +0,05 kredit, až 2× denne. Zdieľajte priamo pri inzeráte v sekcii Moje inzeráty.</p>
          <a href="<?= htmlspecialchars($wt_inzercia_url) ?>" class="inz-btn-secondary" style="width:100%;padding:10px;border-radius:9px;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;box-sizing:border-box;">
            <span class="material-symbols-outlined" style="font-size:17px;">newspaper</span> Prejsť na Moje inzeráty
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="inz-card">
    <div style="padding:22px;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:6px;">
        <h2 style="margin:0;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;color:var(--text-primary);">
          <span class="material-symbols-outlined" style="color:var(--primary-color);">bolt</span>
          Kreslo Hunter
        </h2>
        <span id="wt-hunter-status" style="font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:6px;background:rgba(100,116,139,0.12);color:var(--text-secondary);border:1px solid var(--border-color);white-space:nowrap;">Stav: Neaktívne</span>
      </div>
      <p style="margin:0 0 12px;font-size:11.5px;color:var(--text-secondary);line-height:1.4;">Automaticky stráži ponuky podľa vašich kritérií a upozorní vás na najlepšie termíny.</p>
      <div style="display:flex;gap:8px;">
        <button type="button" onclick="WalletTopup.purchaseHunter('monthly')" class="inz-btn-secondary" style="flex:1;padding:10px 6px;border-radius:9px;font-weight:700;font-size:12.5px;display:flex;flex-direction:column;align-items:center;gap:2px;">
          <span>Mesačne</span><strong style="font-size:14px;color:var(--primary-color);">0,90 €</strong>
        </button>
        <button type="button" onclick="WalletTopup.purchaseHunter('yearly')" class="inz-btn-primary" style="flex:1;padding:10px 6px;border-radius:9px;font-weight:700;font-size:12.5px;display:flex;flex-direction:column;align-items:center;gap:2px;">
          <span>Ročne <span style="opacity:0.85;">(namiesto 10,80 €)</span></span><strong style="font-size:14px;">9,00 €</strong>
        </button>
      </div>
      <div style="display:flex;align-items:flex-start;gap:8px;background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:10px 12px;margin-top:12px;">
        <span class="material-symbols-outlined" style="font-size:16px;color:#10b981;flex-shrink:0;margin-top:1px;">savings</span>
        <div style="font-size:11.5px;color:var(--text-secondary);line-height:1.5;">
          Možno platiť aj z <strong style="color:var(--text-primary);">nazbieraného kreditu</strong> zo zdieľania — najprv sa použije kredit, zvyšok sa doplatí z reálne dobitých eur.
        </div>
      </div>
    </div>
  </div>

  <div class="inz-card">
    <div style="padding:22px;">
      <h2 style="margin:0 0 4px;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;color:var(--text-primary);">
        <span class="material-symbols-outlined" style="color:var(--primary-color);">receipt_long</span>
        Výpis transakcií
      </h2>
      <p style="margin:0 0 16px;font-size:11.5px;color:var(--text-secondary);line-height:1.4;">Prehľad histórie vašej Peňaženky — kedy a koľko ste dobili aj získali zdieľaním.</p>
      <div id="wt-transactions" style="display:flex;flex-direction:column;gap:8px;">
        <div style="text-align:center;padding:16px;color:var(--text-secondary);font-size:12.5px;">Načítavam...</div>
      </div>
    </div>
  </div>
</div>

<!-- MODÁL: PROPAGOVAŤ NA INSTAGRAME -->
<div id="wt-ig-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:440px;width:100%;padding:26px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
      <h3 style="margin:0;font-size:17px;font-weight:800;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:#e1306c;">photo_camera</span>Propagovať na Instagrame
      </h3>
      <button type="button" onclick="WalletTopup.closeInstagramModal()" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:22px;">&times;</button>
    </div>
    <p style="margin:0 0 18px;font-size:12px;color:var(--text-secondary);">Vyberte si spôsob, akým chcete odkaz zdieľať na Instagrame.</p>

    <div style="display:flex;gap:6px;border-bottom:1px solid var(--border-color);margin-bottom:18px;">
      <button type="button" onclick="WalletTopup.switchInstagramTab('story')" id="wt-ig-btn-story" class="wt-ig-tab-btn active" style="flex:1;padding:10px;background:none;border:none;border-radius:0;border-bottom:2.5px solid #e1306c;color:#e1306c;font-weight:800;font-size:11.5px;cursor:pointer;">Možnosť A: Story</button>
      <button type="button" onclick="WalletTopup.switchInstagramTab('dm')" id="wt-ig-btn-dm" class="wt-ig-tab-btn" style="flex:1;padding:10px;background:none;border:none;border-radius:0;border-bottom:2.5px solid transparent;color:var(--text-secondary);font-weight:700;font-size:11.5px;cursor:pointer;">Možnosť B: Direct (DM)</button>
    </div>

    <div id="wt-ig-pane-story" class="wt-ig-tab-pane">
      <p style="font-size:12px;color:var(--text-secondary);line-height:1.5;margin-bottom:14px;">Instagram Stories sú najrýchlejší spôsob. Jedným klikom skopírujeme váš odkaz a navigujeme vás k vytvoreniu príbehu.</p>
      <div style="background:var(--bg-color);border:1px solid var(--border-color);padding:15px;border-radius:14px;margin-bottom:18px;">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--text-primary);font-weight:600;">
            <span style="width:18px;height:18px;border-radius:50%;background:#e1306c;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;flex-shrink:0;">1</span>
            <span>Odkaz sa skopíruje automaticky pri otvorení Instagramu</span>
          </div>
          <div style="display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--text-primary);font-weight:600;">
            <span style="width:18px;height:18px;border-radius:50%;background:#e1306c;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;flex-shrink:0;">2</span>
            <span>Vytvoríte nový príbeh (Story) na Instagrame</span>
          </div>
          <div style="display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--text-primary);font-weight:600;">
            <span style="width:18px;height:18px;border-radius:50%;background:#e1306c;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;flex-shrink:0;">3</span>
            <span>Pridáte nálepku „ODKAZ“ a vložíte zo schránky váš link</span>
          </div>
        </div>
      </div>
      <button type="button" onclick="WalletTopup.executeInstagramAction('story')" style="width:100%;height:42px;background:linear-gradient(135deg,#e1306c,#f77737);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 15px rgba(225,48,108,0.25);">Kopírovať odkaz a otvoriť Instagram</button>
    </div>
    <div id="wt-ig-pane-dm" class="wt-ig-tab-pane" style="display:none;">
      <p style="font-size:12px;color:var(--text-secondary);line-height:1.5;margin-bottom:14px;">Pošlite priamy odkaz s pozvánkou vybranému priateľovi do správ Direct Message.</p>
      <div style="background:var(--bg-color);border:1px solid var(--border-color);padding:15px;border-radius:14px;margin-bottom:18px;">
        <div style="font-size:11.5px;color:var(--text-secondary);font-weight:600;line-height:1.5;">
          📋 <strong>Predvyplnený text v schránke:</strong><br>
          <span style="font-style:italic;opacity:0.85;">Objavte portál <?= BRAND_SITE ?> – AI online rezervácie! https://<?= BRAND_SITE ?></span>
        </div>
      </div>
      <button type="button" onclick="WalletTopup.executeInstagramAction('dm')" style="width:100%;height:42px;background:linear-gradient(135deg,#e1306c,#833ab4);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 15px rgba(131,58,180,0.25);">Skopírovať správu a otvoriť Instagram</button>
    </div>
  </div>
</div>

<style>
.wt-wrap { max-width: 900px !important; width: 100% !important; }
.wt-wrap .inz-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.wt-row { display: flex; gap: 16px; align-items: stretch; flex-wrap: wrap; margin-bottom: 16px; }
.wt-col-wallet { flex: 1 1 320px; min-width: 300px; margin-bottom: 0 !important; }
.wt-col-share { flex: 1 1 280px; min-width: 260px; display: flex; flex-direction: column; gap: 16px; }
.wt-col-share .inz-card { margin-bottom: 0 !important; }
.wt-balance-card {
  background: linear-gradient(135deg,#7a5a20 0%,#c49433 35%,#e8b84b 60%,#a06d22 100%);
  border-radius: 14px;
  padding: 18px 20px;
  color: #fff;
  box-shadow: 0 8px 20px rgba(176,128,66,0.3);
}
.wt-pill-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:18px; }
.wt-pill { padding:10px 6px; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13px; font-weight:700; cursor:pointer; transition:all 0.15s ease; text-align:center; }
.wt-pill:hover { border-color:var(--primary-color); color:var(--primary-color); }
.wt-pill-active { background:var(--primary-color) !important; color:#fff !important; border-color:var(--primary-color) !important; }
.wt-submit-btn { width:100%; padding:12px; border-radius:10px; font-weight:700; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; }
@media (max-width: 480px) {
  .wt-wrap { max-width: 100%; }
}
</style>

<script>
(function () {
  'use strict';
  let g_amount = 5;
  const WT_BRAND_SITE = <?= json_encode(BRAND_SITE) ?>;

  function toast(msg, type) {
    if (typeof showAppToast === 'function') { showAppToast(msg, type); return; }
    let c = document.getElementById('_wt_tc');
    if (!c) { c = document.createElement('div'); c.id = '_wt_tc'; c.style.cssText = 'position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;'; document.body.appendChild(c); }
    const t = document.createElement('div');
    t.style.cssText = `background:${type==='error'?'#ef4444':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;opacity:0;transform:translateY(-15px);transition:all 0.3s;pointer-events:none;`;
    t.innerText = msg; c.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; }));
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(-15px)'; setTimeout(() => t.remove(), 320); }, 3500);
  }

  function selectAmount(amount, btn) {
    g_amount = amount;
    document.querySelectorAll('#wt-amount-buttons .wt-pill').forEach(b => b.classList.remove('wt-pill-active'));
    if (btn) btn.classList.add('wt-pill-active');
    const btnText = document.getElementById('wt-submit-btn-text');
    if (btnText) btnText.innerText = `Dobiť ${amount.toFixed(2).replace('.', ',')} €`;
  }

  function fmtEur(n) { return (n || 0).toFixed(2).replace('.', ',') + ' €'; }
  function fmtCredit(n) { return (n || 0).toFixed(2).replace('.', ',') + ' kredit'; }

  async function load() {
    const el = document.getElementById('wt-balance');
    const elP = document.getElementById('wt-purchased');
    const elE = document.getElementById('wt-earned');
    if (!el) return;
    try {
      const fd = new FormData(); fd.append('action', 'get_balance');
      const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        el.innerText = fmtEur(data.balance);
        if (elP) elP.innerText = fmtEur(data.purchased);
        if (elE) elE.innerText = fmtCredit(data.earned);
        renderTransactions(data.transactions || []);
        updateHunterStatus(data.hunter_active, data.hunter_expires_at);
      } else {
        el.innerText = '— €';
      }
    } catch (e) { el.innerText = '— €'; }
  }

  function checkStripeReturn() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('stripe') === 'success') {
      toast('Platba prijatá, spracúvame dobitie kreditu...', 'success');
      setTimeout(load, 2000);
      setTimeout(load, 5000);
      window.history.replaceState({}, '', window.location.pathname);
    } else if (params.get('stripe') === 'cancel') {
      toast('Platba bola zrušená.', 'error');
      window.history.replaceState({}, '', window.location.pathname);
    }
  }

  function updateHunterStatus(active, expiresAt) {
    const badge = document.getElementById('wt-hunter-status');
    if (!badge) return;
    if (active && expiresAt) {
      const d = new Date(expiresAt.replace(' ', 'T'));
      const dateStr = isNaN(d) ? '' : d.toLocaleDateString('sk-SK');
      badge.innerText = `Aktívne do ${dateStr}`;
      badge.style.background = 'rgba(16,185,129,0.12)'; badge.style.color = '#10b981'; badge.style.borderColor = 'rgba(16,185,129,0.25)';
    } else {
      badge.innerText = 'Stav: Neaktívne';
      badge.style.background = 'rgba(100,116,139,0.12)'; badge.style.color = 'var(--text-secondary)'; badge.style.borderColor = 'var(--border-color)';
    }
  }

  async function purchaseHunter(period) {
    try {
      const fd = new FormData(); fd.append('action', 'purchase_hunter'); fd.append('period', period);
      const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) { toast(data.message || 'Kreslo Hunter aktivovaný!', 'success'); load(); }
      else toast(data.error || 'Nepodarilo sa aktivovať.', 'error');
    } catch (e) { toast('Chyba pripojenia.', 'error'); }
  }

  function escHtml(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function renderTransactions(list) {
    const el = document.getElementById('wt-transactions');
    if (!el) return;
    if (!list.length) { el.innerHTML = '<div style="text-align:center;padding:16px;color:var(--text-secondary);font-size:12.5px;">Zatiaľ žiadne transakcie.</div>'; return; }
    const icons = { deposit: 'credit_card', reward: 'share' };
    el.innerHTML = list.map(t => {
      const amount = parseFloat(t.amount);
      const positive = amount >= 0;
      const icon = icons[t.type] || (positive ? 'add_circle' : 'shopping_cart');
      const dt = new Date(t.created_at);
      const dateStr = isNaN(dt) ? '' : dt.toLocaleDateString('sk-SK') + ' ' + dt.toLocaleTimeString('sk-SK', { hour: '2-digit', minute: '2-digit' });
      return `<div style="display:flex;align-items:center;gap:12px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:10px 14px;">
        <div style="width:34px;height:34px;border-radius:50%;background:${positive ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.1)'};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <span class="material-symbols-outlined" style="font-size:17px;color:${positive ? '#10b981' : '#ef4444'};">${icon}</span>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(t.description || (positive ? 'Pripísanie kreditu' : 'Odpočet kreditu'))}</div>
          <div style="font-size:11px;color:var(--text-secondary);">${dateStr}</div>
        </div>
        <div style="font-size:14px;font-weight:800;color:${positive ? '#10b981' : '#ef4444'};white-space:nowrap;">${positive ? '+' : ''}${t.type === 'reward' ? fmtCredit(amount) : fmtEur(amount)}</div>
      </div>`;
    }).join('');
  }

  async function submit() {
    if (!g_amount || g_amount < 5) { toast('Minimálna suma dobitia je 5,00 €.', 'error'); return; }
    const btn = document.getElementById('wt-submit-btn');
    if (btn) btn.disabled = true;
    try {
      // Dobitie ide cez Stripe Checkout — kredit sa pripíše až po potvrdenej platbe (api/stripe_webhook.php)
      const returnPage = window.location.pathname.split('/').pop();
      const fd = new FormData();
      fd.append('action', 'create_topup_session');
      fd.append('amount', g_amount);
      fd.append('return_page', returnPage);
      const res = await fetch('api/stripe_checkout.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success && data.checkout_url) { window.location.href = data.checkout_url; }
      else { toast(data.message || 'Chyba pri otváraní platby.', 'error'); if (btn) btn.disabled = false; }
    } catch (e) {
      toast('Chyba pripojenia.', 'error');
      if (btn) btn.disabled = false;
    }
  }

  /* ── Zdieľanie a odmeny ── */
  function setBadge(el, ok, okText, failText) {
    if (!el) return;
    if (ok) {
      el.style.background = 'rgba(16,185,129,0.12)'; el.style.color = '#10b981'; el.style.borderColor = 'rgba(16,185,129,0.25)';
      el.innerText = okText;
    } else {
      el.style.background = 'rgba(239,68,68,0.12)'; el.style.color = '#ef4444'; el.style.borderColor = 'rgba(239,68,68,0.25)';
      el.innerText = failText;
    }
  }

  async function loadShareStatus() {
    const appBadge = document.getElementById('wt-share-app-badge');
    const listingBadge = document.getElementById('wt-share-listing-badge');
    if (!appBadge && !listingBadge) return;
    try {
      const fd = new FormData(); fd.append('action', 'get_share_status');
      const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) return;
      setBadge(appBadge, data.app_available, '+0,10 kredit', 'Dnešný limit vyčerpaný');
      setBadge(listingBadge, data.listing_available, 'Zostáva ' + (2 - data.today_ad_count) + ' z 2', 'Dnešný limit vyčerpaný');
    } catch (e) {}
  }

  function shareApp(platform) {
    const url = 'https://' + WT_BRAND_SITE, title = `Objavte portál ${WT_BRAND_SITE} – AI online rezervácie!`;
    if (platform === 'facebook') {
      const fbUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`;
      const win = window.open(fbUrl, 'fb-share', 'width=600,height=500');
      const timer = setInterval(() => { if (!win || win.closed) { clearInterval(timer); claimShareReward('facebook', 'app'); } }, 1000);
    } else if (platform === 'x') {
      const xUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
      const win = window.open(xUrl, 'x-share', 'width=600,height=500');
      const timer = setInterval(() => { if (!win || win.closed) { clearInterval(timer); claimShareReward('x', 'app'); } }, 1000);
    } else {
      window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(title + ' ' + url)}`, '_blank');
      claimShareReward('whatsapp', 'app');
    }
  }

  async function claimShareReward(platform, target, listingId) {
    try {
      const fd = new FormData(); fd.append('action', 'claim_share_reward'); fd.append('platform', platform); fd.append('target', target);
      if (listingId) fd.append('listing_id', listingId);
      const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) { toast(data.message, 'success'); load(); loadShareStatus(); }
      else if (!data.already_claimed) toast(data.error || 'Nepodarilo sa pripísať odmenu.', 'error');
      return data;
    } catch (e) { toast('Chyba pripojenia.', 'error'); }
  }

  /* ── Instagram modál (Story / Direct DM) ── */
  function openInstagramModal() {
    document.getElementById('wt-ig-modal').style.display = 'flex';
    switchInstagramTab('story');
  }
  function closeInstagramModal() {
    document.getElementById('wt-ig-modal').style.display = 'none';
  }
  function switchInstagramTab(tab) {
    document.querySelectorAll('.wt-ig-tab-btn').forEach(b => { b.classList.remove('active'); b.style.borderColor = 'transparent'; b.style.color = 'var(--text-secondary)'; });
    document.querySelectorAll('.wt-ig-tab-pane').forEach(p => p.style.display = 'none');
    const btn = document.getElementById('wt-ig-btn-' + tab);
    if (btn) { btn.classList.add('active'); btn.style.borderColor = '#e1306c'; btn.style.color = '#e1306c'; }
    const pane = document.getElementById('wt-ig-pane-' + tab);
    if (pane) pane.style.display = 'block';
  }
  function executeInstagramAction(type) {
    const url = 'https://' + WT_BRAND_SITE;
    const message = `Objavte portál ${WT_BRAND_SITE} – AI online rezervácie! ${url}`;
    if (type === 'story') {
      navigator.clipboard.writeText(url).then(() => {
        toast('Odkaz bol skopírovaný! Otváram Instagram Stories...', 'success');
        setTimeout(() => { window.open('https://www.instagram.com/', '_blank'); closeInstagramModal(); claimShareReward('instagram_story', 'app'); }, 1000);
      });
    } else if (type === 'dm') {
      navigator.clipboard.writeText(message).then(() => {
        toast('Správa skopírovaná! Otváram Instagram DM...', 'success');
        setTimeout(() => { window.open('https://www.instagram.com/direct/inbox/', '_blank'); closeInstagramModal(); claimShareReward('instagram_dm', 'app'); }, 1000);
      });
    }
  }

  window.WalletTopup = {
    load, selectAmount, submit, loadShareStatus, shareApp, claimShareReward,
    openInstagramModal, closeInstagramModal, switchInstagramTab, executeInstagramAction,
    purchaseHunter, checkStripeReturn
  };
})();
</script>
