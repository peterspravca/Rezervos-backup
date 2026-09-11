<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('revenue');
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
$pageTitle = 'Peňaženka - ' . BRAND_NAME;
$currentPage = 'penazanka';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Peňaženka'; $headerIcon = 'account_balance_wallet'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">

    <div class="admin-panel" style="width:100%;max-width:100%;margin:0;box-sizing:border-box;">

      <!-- HLAVIČKA -->
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;margin-bottom:25px;">
        <div>
          <h2 class="section-header" style="margin-bottom:6px;display:flex;align-items:center;gap:10px;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">account_balance_wallet</span>
            Peňaženka, Rozšírenia a SMS
          </h2>
          <p class="section-desc" style="margin:0;">Spravujte svoj kredit, zviditeľňujte prevádzku v katalógu a nakupujte SMS balíčky pre klientov.</p>
        </div>
        <div>
          <button type="button" onclick="loadWalletData()" class="btn-secondary" style="padding:8px 14px;font-size:13px;border-radius:10px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:16px;">refresh</span> Aktualizovať zostatok
          </button>
        </div>
      </div>

      <!-- 1. HORNÝ BLOK: peňaženka + 2 share karty vedľa seba -->
      <div style="display:grid;grid-template-columns:360px 1fr 1fr;gap:18px;margin-bottom:18px;" class="wallet-top-grid">

        <!-- KARTA PEŇAŽENKY – credit card štýl -->
        <div style="background:linear-gradient(135deg,#7a5a20 0%,#c49433 35%,#e8b84b 60%,#a06d22 100%);border-radius:18px;padding:22px 26px;color:#ffffff;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 8px 24px rgba(176,128,66,0.35);height:215px;position:relative;overflow:hidden;box-sizing:border-box;">
          <div style="position:absolute;right:-30px;bottom:-30px;width:160px;height:160px;background:rgba(255,255,255,0.07);border-radius:50%;pointer-events:none;"></div>
          <div style="position:absolute;left:-20px;top:-20px;width:100px;height:100px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
          <!-- Horný riadok: čip + brand -->
          <div style="display:flex;justify-content:space-between;align-items:flex-start;z-index:2;">
            <div style="width:36px;height:27px;background:rgba(255,255,255,0.22);border-radius:4px;border:1px solid rgba(255,255,255,0.35);display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;gap:2px;padding:3px;box-sizing:border-box;">
              <div style="background:rgba(255,255,255,0.45);border-radius:1px;"></div>
              <div style="background:rgba(255,255,255,0.45);border-radius:1px;"></div>
              <div style="background:rgba(255,255,255,0.45);border-radius:1px;"></div>
              <div style="background:rgba(255,255,255,0.45);border-radius:1px;"></div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:2px;opacity:0.65;">Peňaženka</div>
              <div style="font-size:11px;font-weight:900;letter-spacing:1px;margin-top:1px;"><?= mb_strtoupper(BRAND_NAME) ?></div>
            </div>
          </div>
          <!-- Zostatok -->
          <div style="z-index:2;">
            <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;opacity:0.65;margin-bottom:3px;">Zostatok</div>
            <div id="ext-wallet-balance" style="font-size:30px;font-weight:900;line-height:1;letter-spacing:-0.5px;">0,00 <span style="font-size:16px;font-weight:700;">€</span></div>
          </div>
          <!-- Spodný riadok -->
          <div style="display:flex;justify-content:space-between;align-items:flex-end;z-index:2;">
            <div style="min-width:0;flex:1;margin-right:10px;">
              <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:0.6;margin-bottom:2px;">Prevádzka</div>
              <div id="wallet-card-holder-name" style="font-size:10.5px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">— — —</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:0.6;margin-bottom:2px;">Platný do</div>
              <div id="wallet-card-expiry" style="font-size:10.5px;font-weight:800;">&#x221E;</div>
            </div>
          </div>
        </div>

        <!-- ZDIEĽANIE: <?= BRAND_SITE ?> -->
        <div style="background:var(--card-bg);border:1.5px solid rgba(99,102,241,0.22);border-radius:18px;padding:20px;height:215px;box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between;">
          <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
              <div style="width:38px;height:38px;background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:20px;color:#fff;">devices</span>
              </div>
              <span id="share-daily-status-badge" style="font-size:10px;font-weight:800;padding:3px 8px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.25);">+0,10 kredit</span>
            </div>
            <div style="font-size:13px;font-weight:800;color:var(--text-primary);margin-bottom:3px;"><?= BRAND_SITE ?></div>
            <div style="font-size:11.5px;color:var(--text-secondary);line-height:1.4;">Zdieľajte výhody AI rezervácií a zarábajte kredit.</div>
          </div>
          <div style="display:flex;gap:6px;">
            <button type="button" onclick="shareRewardsApp('facebook')" style="flex:1;background:rgba(24,119,242,0.08);border:1px solid rgba(24,119,242,0.25);color:#1877f2;border-radius:7px;padding:8px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">Facebook</button>
            <button type="button" onclick="shareRewardsApp('whatsapp')" style="flex:1;background:rgba(37,211,102,0.08);border:1px solid rgba(37,211,102,0.25);color:#25d366;border-radius:7px;padding:8px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">WhatsApp</button>
            <button type="button" onclick="shareRewardsApp('instagram')" style="flex:1;background:rgba(225,48,108,0.08);border:1px solid rgba(225,48,108,0.25);color:#e1306c;border-radius:7px;padding:8px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">Instagram</button>
          </div>
        </div>

        <!-- ZDIEĽANIE: Moja prevádzka -->
        <div style="background:var(--card-bg);border:1.5px solid rgba(176,128,66,0.28);border-radius:18px;padding:20px;height:215px;box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between;">
          <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
              <div style="width:38px;height:38px;background:linear-gradient(135deg,#b08042,#8c602b);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:20px;color:#fff;">storefront</span>
              </div>
              <span style="font-size:10px;font-weight:800;padding:3px 8px;border-radius:6px;background:rgba(176,128,66,0.12);color:#b08042;border:1px solid rgba(176,128,66,0.3);">+0,05 kredit</span>
            </div>
            <div id="share-card-salon-name" style="font-size:13px;font-weight:800;color:var(--text-primary);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Moja prevádzka</div>
            <div style="font-size:11.5px;color:var(--text-secondary);line-height:1.4;">Zdieľajte odkaz s online rezervačným kalendárom.</div>
          </div>
          <div style="display:flex;gap:6px;">
            <button type="button" onclick="shareMySalon('facebook')" style="flex:1;background:rgba(24,119,242,0.08);border:1px solid rgba(24,119,242,0.25);color:#1877f2;border-radius:7px;padding:8px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">Facebook</button>
            <button type="button" onclick="shareMySalon('whatsapp')" style="flex:1;background:rgba(37,211,102,0.08);border:1px solid rgba(37,211,102,0.25);color:#25d366;border-radius:7px;padding:8px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">WhatsApp</button>
            <button type="button" onclick="shareMySalon('instagram')" style="flex:1;background:rgba(225,48,108,0.08);border:1px solid rgba(225,48,108,0.25);color:#e1306c;border-radius:7px;padding:8px 4px;font-size:11.5px;font-weight:800;cursor:pointer;">Instagram</button>
          </div>
        </div>

      </div>

      <!-- ROZPIS ZOSTATKU: dobité kartou vs. získané zdieľaním -->
      <div style="display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
        <div style="flex:1;min-width:160px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(176,128,66,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:19px;color:var(--primary-color);">credit_card</span>
          </div>
          <div style="min-width:0;">
            <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-secondary);">Dobité kartou</div>
            <div id="ext-purchased-balance" style="font-size:15px;font-weight:800;color:var(--text-primary);">0,00 €</div>
          </div>
        </div>
        <div style="flex:1;min-width:160px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(16,185,129,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:19px;color:#10b981;">share</span>
          </div>
          <div style="min-width:0;">
            <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-secondary);">Za zdieľanie (nazbierané)</div>
            <div id="ext-earned-balance" style="font-size:15px;font-weight:800;color:var(--text-primary);">0,00 €</div>
          </div>
        </div>
      </div>

      <!-- VYSVETLENIE: ako sa platí z peňaženky -->
      <div style="display:flex;align-items:flex-start;gap:10px;background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.2);border-radius:12px;padding:12px 16px;margin-bottom:18px;">
        <span class="material-symbols-outlined" style="font-size:18px;color:#10b981;flex-shrink:0;margin-top:1px;">info</span>
        <div style="font-size:12px;color:var(--text-secondary);line-height:1.5;">
          <strong style="color:var(--text-primary);">Ako to funguje:</strong> pri platbe za topovanie a vybrané doplnky (napr. Kreslo Hunter, TOP, Zvýraznenie inzerátu) sa najprv použije váš <strong>nazbieraný kredit zo zdieľania</strong>, a až keď nestačí, zvyšok sa doplatí z <strong>reálne dobitých eur</strong>. Nemusíte nič vyberať — systém to spraví automaticky.
        </div>
      </div>

      <!-- DOBITIE KREDITU – kompaktný horizontálny bar -->
      <div class="premium-card" style="margin-bottom:28px;padding:16px 20px;">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
          <!-- Label -->
          <div style="flex-shrink:0;min-width:110px;">
            <div style="font-size:13.5px;font-weight:800;color:var(--text-primary);">Dobiť kredit</div>
            <div style="font-size:11px;color:var(--text-secondary);margin-top:1px;">Kredit nemá expiráciu</div>
          </div>
          <!-- Sumy -->
          <div id="topup-panel-card" style="display:flex;gap:8px;flex:1;min-width:200px;">
            <div id="topup-amount-buttons" style="display:flex;gap:8px;width:100%;">
              <button type="button" class="topup-pill active" onclick="selectTopupAmount(5,this)" style="flex:1;">5 €</button>
              <button type="button" class="topup-pill" onclick="selectTopupAmount(10,this)" style="flex:1;">10 €</button>
              <button type="button" class="topup-pill" onclick="selectTopupAmount(20,this)" style="flex:1;">20 €</button>
              <button type="button" class="topup-pill" onclick="selectTopupAmount(50,this)" style="flex:1;">50 €</button>
            </div>
          </div>
          <!-- Odkaz na kúpu SMS balíčka nižšie (SMS sa platia z toho istého kreditu, nie je to iný spôsob platby) -->
          <a href="#sms-balicky" onclick="document.getElementById('sms-balicky').scrollIntoView({behavior:'smooth'});" class="btn-secondary" style="flex-shrink:0;padding:9px 14px;border-radius:8px;font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:5px;text-decoration:none;">
            <span class="material-symbols-outlined" style="font-size:16px;">sms</span> Kúpiť SMS balíček ↓
          </a>
          <!-- Tlačidlo Dobiť -->
          <button type="button" id="topup-submit-btn" onclick="processTopup()" class="btn-primary" style="flex-shrink:0;padding:10px 20px;border-radius:10px;font-weight:700;font-size:13.5px;display:flex;align-items:center;gap:6px;white-space:nowrap;">
            <span class="material-symbols-outlined" style="font-size:16px;">payments</span>
            <span id="topup-submit-btn-text">Dobiť 5,00 €</span>
          </button>
        </div>
      </div>

      <!-- 3. TOPOVANIE -->
      <div style="margin-bottom:35px;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
          <div>
            <h3 style="font-size:18px;font-weight:800;margin:0 0 4px 0;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:var(--primary-color);">trending_up</span> Zviditeľnenie a Topovanie prevádzky
            </h3>
            <p style="font-size:13px;color:var(--text-secondary);margin:0;">Posuňte svoju prevádzku na 1. miesto vo vyhľadávaní. Hradené z Peňaženky.</p>
          </div>
          <div id="ext-boost-status-container">
            <span id="ext-boost-status" style="background:rgba(176,128,66,0.12);color:var(--primary-color);border:1px solid rgba(176,128,66,0.25);padding:5px 12px;border-radius:8px;font-size:12px;font-weight:800;display:inline-flex;align-items:center;gap:6px;">
              <span class="material-symbols-outlined" style="font-size:16px;">rocket_launch</span> Stav: Neaktívne
            </span>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;" class="boost-grid">
          <!-- Jednorazové tapnutie -->
          <div class="premium-card" style="margin:0;display:flex;flex-direction:column;justify-content:space-between;border:1px solid var(--border-color);border-radius:16px;padding:22px;">
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(176,128,66,0.12);color:var(--primary-color);display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="font-size:24px;">touch_app</span></div>
                <span style="background:rgba(176,128,66,0.12);color:var(--primary-color);border:1px solid rgba(176,128,66,0.25);padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;">Rýchly posun</span>
              </div>
              <h4 style="font-size:16px;font-weight:800;margin:0 0 6px 0;">Jednorazové tapnutie</h4>
              <div style="font-size:24px;font-weight:900;color:var(--primary-color);margin-bottom:10px;">0,45 € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ 1x tap</span></div>
              <p style="font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 8px 0;">Posunie vašu prevádzku na 1. miesto vo výpise salónov.</p>
              <div style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#10b981;font-weight:700;margin:0 0 16px 0;"><span class="material-symbols-outlined" style="font-size:14px;">savings</span> Možno platiť aj z nazbieraných kreditov (0,45 kredit)</div>
            </div>
            <button type="button" onclick="purchaseBoost('single_tap')" class="btn-primary" style="width:100%;padding:10px;border-radius:10px;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">bolt</span> Tapnúť (0,45 €)</button>
          </div>
          <!-- Ranné vtáča -->
          <div class="premium-card" style="margin:0;display:flex;flex-direction:column;justify-content:space-between;border:2px solid var(--primary-color);border-radius:16px;padding:22px;box-shadow:0 4px 20px rgba(176,128,66,0.12);">
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(245,158,11,0.15);color:#f59e0b;display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="font-size:24px;">wb_sunny</span></div>
                <span style="background:var(--primary-color);color:#fff;padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;">Najpopulárnejšie • 7 Dní</span>
              </div>
              <h4 style="font-size:16px;font-weight:800;margin:0 0 6px 0;">Ranné vtáča balíček</h4>
              <div style="font-size:24px;font-weight:900;color:var(--primary-color);margin-bottom:10px;">2,40 € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ 7 dní auto-tap</span></div>
              <p style="font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 8px 0;">Automatický posun každé ráno o <b>08:00</b> po dobu 7 dní.</p>
              <div style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#10b981;font-weight:700;margin:0 0 16px 0;"><span class="material-symbols-outlined" style="font-size:14px;">savings</span> Možno platiť aj z nazbieraných kreditov (2,40 kredit)</div>
            </div>
            <button type="button" onclick="purchaseBoost('morning_bird')" class="btn-primary" style="width:100%;padding:10px;border-radius:10px;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">schedule</span> Aktivovať (2,40 €)</button>
          </div>
          <!-- Prime-time -->
          <div class="premium-card" style="margin:0;display:flex;flex-direction:column;justify-content:space-between;border:1px solid var(--border-color);border-radius:16px;padding:22px;">
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(239,68,68,0.12);color:#ef4444;display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="font-size:24px;">rocket_launch</span></div>
                <span style="background:rgba(239,68,68,0.12);color:#ef4444;border:1px solid rgba(239,68,68,0.25);padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;">Špička • 7 Dní</span>
              </div>
              <h4 style="font-size:16px;font-weight:800;margin:0 0 6px 0;">Prime-time Bombardér</h4>
              <div style="font-size:24px;font-weight:900;color:var(--primary-color);margin-bottom:10px;">4,90 € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ 7 dní auto-tap</span></div>
              <p style="font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 8px 0;">Automatický posun v čase <b>17:00 – 20:00</b> denne po 7 dní.</p>
              <div style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#10b981;font-weight:700;margin:0 0 16px 0;"><span class="material-symbols-outlined" style="font-size:14px;">savings</span> Možno platiť aj z nazbieraných kreditov (4,90 kredit)</div>
            </div>
            <button type="button" onclick="purchaseBoost('primetime_bomber')" class="btn-primary" style="width:100%;padding:10px;border-radius:10px;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">local_fire_department</span> Aktivovať (4,90 €)</button>
          </div>
        </div>
      </div>

      <!-- 3b. TOP / SPONZOROVANÉ / ODPORÚČANÉ -->
      <div style="margin-bottom:35px;">
        <div style="margin-bottom:16px;">
          <h3 style="font-size:18px;font-weight:800;margin:0 0 4px 0;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">military_tech</span> Predĺžené zvýraznenie v katalógu
          </h3>
          <p style="font-size:13px;color:var(--text-secondary);margin:0;">Trvalé mesačné/ročné zvýraznenie prevádzky vo výpise na <code>prevádzky</code> — na rozdiel od topovania vyššie ide o dlhodobú pozíciu, nie jednorazový/dočasný posun. Poradie: TOP &gt; Sponzorované &gt; Odporúčané &gt; ostatné.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;" class="boost-grid">
          <!-- TOP -->
          <div class="premium-card" style="margin:0;display:flex;flex-direction:column;justify-content:space-between;border:2px solid #f59e0b;border-radius:16px;padding:22px;box-shadow:0 4px 20px rgba(245,158,11,0.12);">
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(245,158,11,0.15);color:#f59e0b;display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="font-size:24px;">workspace_premium</span></div>
                <span style="background:#f59e0b;color:#fff;padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;">Najvyššia priorita</span>
              </div>
              <h4 style="font-size:16px;font-weight:800;margin:0 0 6px 0;">TOP</h4>
              <div style="font-size:24px;font-weight:900;color:#f59e0b;margin-bottom:4px;">30 € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ mesiac</span></div>
              <div style="font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:10px;">alebo 300 € / rok</div>
              <p style="font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 8px 0;">Prevádzka sa zobrazuje úplne navrchu výpisu, nad všetkými ostatnými. Len platené.</p>
              <div id="top-status-line" style="font-size:11.5px;color:var(--text-secondary);font-weight:700;margin:0 0 16px 0;">Stav: Neaktívne</div>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="button" onclick="purchasePlan('purchase_top','monthly')" class="btn-primary" style="flex:1;padding:10px;border-radius:10px;font-weight:700;font-size:12.5px;background:#f59e0b;">30 €/mes</button>
              <button type="button" onclick="purchasePlan('purchase_top','yearly')" class="btn-primary" style="flex:1;padding:10px;border-radius:10px;font-weight:700;font-size:12.5px;background:#f59e0b;">300 €/rok</button>
            </div>
          </div>
          <!-- SPONZOROVANÉ -->
          <div class="premium-card" style="margin:0;display:flex;flex-direction:column;justify-content:space-between;border:1px solid #8b5cf6;border-radius:16px;padding:22px;">
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(139,92,246,0.15);color:#8b5cf6;display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="font-size:24px;">handshake</span></div>
                <span style="background:rgba(139,92,246,0.12);color:#8b5cf6;border:1px solid rgba(139,92,246,0.25);padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;">Stredná priorita</span>
              </div>
              <h4 style="font-size:16px;font-weight:800;margin:0 0 6px 0;">Sponzorované</h4>
              <div style="font-size:24px;font-weight:900;color:#8b5cf6;margin-bottom:4px;">15 € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ mesiac</span></div>
              <div style="font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:10px;">alebo 150 € / rok</div>
              <p style="font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 8px 0;">Zvýraznenie nad bežnými prevádzkami. Toto zvýraznenie môže prevádzke udeliť zadarmo aj administrátor pri zapojení do partnerskej akcie.</p>
              <div id="sponsored-status-line" style="font-size:11.5px;color:var(--text-secondary);font-weight:700;margin:0 0 16px 0;">Stav: Neaktívne</div>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="button" onclick="purchasePlan('purchase_sponsored','monthly')" class="btn-secondary" style="flex:1;padding:10px;border-radius:10px;font-weight:700;font-size:12.5px;">15 €/mes</button>
              <button type="button" onclick="purchasePlan('purchase_sponsored','yearly')" class="btn-secondary" style="flex:1;padding:10px;border-radius:10px;font-weight:700;font-size:12.5px;">150 €/rok</button>
            </div>
          </div>
          <!-- ODPORÚČANÉ -->
          <div class="premium-card" style="margin:0;display:flex;flex-direction:column;justify-content:space-between;border:1px solid var(--border-color);border-radius:16px;padding:22px;">
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="font-size:24px;">thumb_up</span></div>
                <span style="background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.25);padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;">Aj zadarmo</span>
              </div>
              <h4 style="font-size:16px;font-weight:800;margin:0 0 6px 0;">Odporúčané</h4>
              <div style="font-size:24px;font-weight:900;color:#10b981;margin-bottom:4px;">10 € <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">/ mesiac</span></div>
              <div style="font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:10px;">alebo 100 € / rok</div>
              <p style="font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 8px 0;">Získate zadarmo automaticky, ak dosiahnete <b>100× 5★ recenziu</b> v aktuálnom mesiaci — inak sa dá kedykoľvek kúpiť.</p>
              <div id="recommended-status-line" style="font-size:11.5px;color:var(--text-secondary);font-weight:700;margin:0 0 16px 0;">Stav: Neaktívne</div>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="button" onclick="purchasePlan('purchase_recommended','monthly')" class="btn-secondary" style="flex:1;padding:10px;border-radius:10px;font-weight:700;font-size:12.5px;">10 €/mes</button>
              <button type="button" onclick="purchasePlan('purchase_recommended','yearly')" class="btn-secondary" style="flex:1;padding:10px;border-radius:10px;font-weight:700;font-size:12.5px;">100 €/rok</button>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. SMS BALÍČKY -->
      <div id="sms-balicky" class="premium-card" style="margin-bottom:30px;padding:28px;scroll-margin-top:20px;">
        <div style="margin-bottom:18px;">
          <h3 style="font-size:18px;font-weight:800;margin:0;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#3b82f6;">sms</span> SMS upozornenia</h3>
          <p style="font-size:13px;color:var(--text-secondary);margin:4px 0 0 0;">Kredit sa strháva z rovnakej Peňaženky ako platba za topovanie a doplnky — nie je to samostatný spôsob platby.</p>
        </div>
        <div style="display:flex;align-items:flex-start;gap:10px;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:12px;padding:12px 16px;margin-bottom:20px;">
          <span class="material-symbols-outlined" style="font-size:18px;color:#3b82f6;flex-shrink:0;margin-top:1px;">info</span>
          <div style="font-size:12px;color:var(--text-secondary);line-height:1.5;">
            <strong style="color:var(--text-primary);">Ako to funguje:</strong> jedna cena platí pre SMS na <strong>slovenské aj české čísla</strong> — netreba vyberať krajinu. Kredit nakúpite hneď teraz; <strong>samotné odosielanie</strong> sa spustí čoskoro (dokončujeme napojenie na SMS bránu).<br>
            <strong style="color:#10b981;">Kredit nikdy neprepadá</strong> — nie je to paušál, kde vám nevyužitý kredit na konci obdobia prepadne. Čo si kúpite, to vám zostáva uložené bez časového obmedzenia, kým ho nevyužijete.
          </div>
        </div>
        <div style="overflow-x:auto;margin-bottom:18px;">
          <table style="width:100%;border-collapse:collapse;text-align:left;font-size:13.5px;">
            <thead><tr style="border-bottom:2px solid var(--border-color);color:var(--text-secondary);font-size:12px;font-weight:700;text-transform:uppercase;">
              <th style="padding:12px 14px;">SMS balíček</th>
              <th style="padding:12px 14px;">Cena</th>
              <th style="padding:12px 14px;">Cena za SMS</th>
              <th style="padding:12px 14px;text-align:right;">Akcia</th>
            </tr></thead>
            <tbody id="sms-packages-tbody"></tbody>
          </table>
        </div>
        <div style="display:flex;justify-content:flex-end;">
          <button type="button" onclick="buySelectedSms()" class="btn-primary" style="padding:12px 24px;font-size:14px;font-weight:700;border-radius:12px;display:inline-flex;align-items:center;gap:8px;background:#3b82f6;">
            <span class="material-symbols-outlined" style="font-size:18px;">shopping_cart</span> Kúpiť SMS kredity
          </button>
        </div>
      </div>

      <!-- 4b. VLASTNÉ MENO ODOSIELATEĽA SMS -->
      <div class="premium-card" style="margin-bottom:30px;padding:28px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;margin-bottom:16px;">
          <div>
            <h3 style="font-size:18px;font-weight:800;margin:0;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">badge</span> Vlastné meno odosielateľa SMS</h3>
            <p style="font-size:13px;color:var(--text-secondary);margin:4px 0 0 0;">Zákazníkom príde SMS s menom vašej prevádzky namiesto "Rezervos" (max 11 znakov).</p>
          </div>
          <span id="sms-sender-status" style="font-size:11px;font-weight:800;padding:5px 12px;border-radius:8px;background:rgba(100,116,139,0.12);color:var(--text-secondary);white-space:nowrap;">Neaktivované</span>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
          <input type="text" id="sms-sender-input" maxlength="11" placeholder="napr. MARSOFT" style="flex:1;min-width:180px;padding:11px 14px;border-radius:10px;border:1.5px solid var(--border-color);background:var(--input-bg);color:var(--text-primary);font-size:13.5px;font-weight:700;text-transform:uppercase;">
          <button type="button" id="sms-sender-btn" onclick="activateSmsSender()" class="btn-primary" style="padding:11px 20px;border-radius:10px;font-weight:700;font-size:13px;display:flex;align-items:center;gap:6px;white-space:nowrap;">
            <span class="material-symbols-outlined" style="font-size:17px;">check_circle</span> <span id="sms-sender-btn-text">Aktivovať (49 €/jednorazovo)</span>
          </button>
        </div>
        <p style="font-size:11.5px;color:var(--text-secondary);margin:10px 0 0 0;">Jednorazový poplatok, platba len reálnymi peniazmi z Peňaženky. Meno je potom možné kedykoľvek bezplatne zmeniť.</p>
      </div>

      <!-- 5. HISTÓRIA TRANSAKCIÍ -->
      <div class="premium-card" style="margin:0;padding:24px;">
        <h4 style="font-size:16px;font-weight:800;margin:0 0 14px 0;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="font-size:20px;color:var(--primary-color);">history</span> Posledné transakcie
        </h4>
        <div id="ext-transactions-list" style="max-height:240px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;">
          <p style="font-size:13px;color:var(--text-secondary);margin:0;">Žiadne predchádzajúce transakcie.</p>
        </div>
      </div>

    </div>

    </div>
  </div>
</div>

<style>
.topup-pill{padding:9px 12px;border-radius:10px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:13px;font-weight:700;cursor:pointer;transition:all 0.2s ease;}
.topup-pill:hover{border-color:var(--primary-color);color:var(--primary-color);}
.topup-pill.active{background:var(--primary-color);color:#fff;border-color:var(--primary-color);}
.sms-country-tab{background:transparent;color:var(--text-secondary);}
.sms-country-tab.active{background:var(--primary-color);color:#fff;}
@media(max-width:900px){.wallet-top-grid{grid-template-columns:1fr!important;}}
@media(max-width:768px){.wallet-responsive-grid,.share-two-col-grid,.boost-grid{grid-template-columns:1fr!important;}}
</style>

<script>
let g_walletData = null;
let g_selectedTopupAmount = 5;
let g_selectedSmsCount = 100;

async function loadWalletData() {
  try {
    const r = await fetch('api/wallet.php?action=get_wallet');
    const res = await r.json();
    if (res.success) {
      g_walletData = res;
      const balEl = document.getElementById('ext-wallet-balance');
      if (balEl) { const bal = res.wallet.credit||0; balEl.innerHTML=`${bal.toFixed(2).replace('.',',')} <span style="font-size:24px;font-weight:700;">€</span>`; }
      const earnEl = document.getElementById('ext-earned-balance');
      if (earnEl) { const earn = res.wallet.credit_earned||0; earnEl.innerText=`${earn.toFixed(2).replace('.',',')} kredit`; }
      const purchEl = document.getElementById('ext-purchased-balance');
      if (purchEl) { const purch = res.wallet.credit_purchased||0; purchEl.innerText=`${purch.toFixed(2).replace('.',',')} €`; }
      const boostEl = document.getElementById('ext-boost-status');
      if (boostEl) {
        const est = res.establishment;
        if (est&&est.boost_type&&est.boost_expires_at&&new Date(est.boost_expires_at)>new Date()) {
          const exp = new Date(est.boost_expires_at);
          const expStr = exp.toLocaleDateString('sk-SK')+' '+exp.toLocaleTimeString('sk-SK',{hour:'2-digit',minute:'2-digit'});
          const boostName = (est.boost_type==='morning_bird')?'Ranné vtáča':(est.boost_type==='primetime_bomber'?'Prime-time Bombardér':'Aktívne');
          boostEl.innerHTML=`<span class="material-symbols-outlined" style="font-size:16px;color:#10b981;">rocket_launch</span><span style="color:#10b981;font-weight:800;"> Stav: ${boostName}</span> (do ${expStr})`;
        } else {
          boostEl.innerHTML=`<span class="material-symbols-outlined" style="font-size:16px;">rocket_launch</span> Stav: Neaktívne`;
        }
      }
      updatePlanStatusLines(res.establishment);
      const country = res.wallet.sms_country||'SK';
      const countryInput = document.getElementById('sms-country-select');
      if (countryInput) countryInput.value = country;
      document.querySelectorAll('.sms-country-tab').forEach(b=>b.classList.remove('active'));
      const activeBtn = document.getElementById(country==='CZ'?'sms-btn-cz':'sms-btn-sk');
      if (activeBtn) activeBtn.classList.add('active');
      const salonNameEl = document.getElementById('share-card-salon-name');
      if (salonNameEl&&res.establishment&&res.establishment.name) salonNameEl.innerText=res.establishment.name;
      // Kreditná karta – meno prevádzky
      const holderEl=document.getElementById('wallet-card-holder-name');
      if (holderEl&&res.establishment&&res.establishment.name) holderEl.innerText=res.establishment.name.toUpperCase();
      // Kreditná karta – dátum platnosti balíka
      const expiryEl=document.getElementById('wallet-card-expiry');
      if (expiryEl) {
        const expRaw=res.establishment?.subscription_expires_at||null;
        if (expRaw) {
          const d=new Date(expRaw);
          const mm=String(d.getMonth()+1).padStart(2,'0');
          const yy=String(d.getFullYear()).slice(-2);
          expiryEl.innerText=`${mm}/${yy}`;
        } else {
          expiryEl.innerHTML='&#x221E;';
        }
      }
      const badgeEl = document.getElementById('share-daily-status-badge');
      const canClaim = res.wallet.can_claim_share_today;
      if (badgeEl) {
        if (canClaim) { badgeEl.innerHTML='Dnes k dispozícii (+0,10 kredit)';badgeEl.style.color='#10b981'; }
        else { badgeEl.innerHTML='✓ Dnes už získané (+0,10 kredit)';badgeEl.style.color='#3b82f6'; }
      }
      renderSmsTable(); updateSmsSenderUI(); renderWalletTransactions();
    }
  } catch(e) { console.error('Error loading wallet:',e); }
}

function updatePlanStatusLines(est) {
  const now = new Date();
  const fmtActive = (dateStr) => {
    const d = new Date(dateStr);
    return `<span style="color:#10b981;">Aktívne do ${d.toLocaleDateString('sk-SK')}</span>`;
  };

  const topEl = document.getElementById('top-status-line');
  if (topEl) {
    topEl.innerHTML = (est && est.top_expires_at && new Date(est.top_expires_at) > now)
      ? fmtActive(est.top_expires_at)
      : 'Stav: Neaktívne';
  }

  const sponsEl = document.getElementById('sponsored-status-line');
  if (sponsEl) {
    sponsEl.innerHTML = (est && est.sponsored_expires_at && new Date(est.sponsored_expires_at) > now)
      ? fmtActive(est.sponsored_expires_at)
      : 'Stav: Neaktívne';
  }

  const recEl = document.getElementById('recommended-status-line');
  if (recEl) {
    const count = est ? (est.recommended_five_star_count || 0) : 0;
    const paidActive = est && est.recommended_expires_at && new Date(est.recommended_expires_at) > now;
    if (paidActive) {
      recEl.innerHTML = fmtActive(est.recommended_expires_at);
    } else if (count >= 100) {
      recEl.innerHTML = `<span style="color:#10b981;">Aktívne zadarmo (${count}/100 recenzií tento mesiac)</span>`;
    } else {
      recEl.innerHTML = `Stav: Neaktívne (${count}/100 päťhviezdičkových recenzií tento mesiac)`;
    }
  }
}

async function purchasePlan(action, period) {
  const fd = new FormData();
  fd.append('action', action);
  fd.append('period', period);
  try {
    const r = await fetch('api/wallet.php', { method: 'POST', body: fd });
    const res = await r.json();
    if (res.success) {
      showToast(res.message || 'Úspešne aktivované.', 'success');
      loadWalletData();
    } else {
      showToast(res.error || 'Chyba pri aktivácii.', 'error');
    }
  } catch(e) { showToast('Chyba komunikácie so serverom.', 'error'); }
}

const BRAND_SITE_JS = <?= json_encode(BRAND_SITE) ?>;
const BRAND_NAME_JS = <?= json_encode(BRAND_NAME) ?>;
function shareRewardsApp(platform) {
  const url='https://'+BRAND_SITE_JS, title=`Objavte portál ${BRAND_SITE_JS} – AI online rezervácie!`;
  if (platform==='facebook') shareRewardsFB(url,title,'app');
  else if (platform==='whatsapp') shareRewardsWA(url,title,'app');
  else claimRewardsShareReward('instagram',title,'app');
}
function shareMySalon(platform) {
  const estId=g_walletData?.establishment?.id||'', estName=g_walletData?.establishment?.name||'Moja prevádzka';
  const url=estId?`https://${BRAND_SITE_JS}/prevadzky.php?est_id=${estId}`:`https://${BRAND_SITE_JS}`;
  const title=`Rezervujte si termín online v ${estName} na ${BRAND_SITE_JS}!`;
  if (platform==='facebook') shareRewardsFB(url,title,'establishment');
  else if (platform==='whatsapp') shareRewardsWA(url,title,'establishment');
  else claimRewardsShareReward('instagram',title,'establishment');
}
function shareRewardsFB(url,title,target) {
  const fbUrl=`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`;
  const win=window.open(fbUrl,'fb-share','width=600,height=500');
  const timer=setInterval(()=>{if(!win||win.closed){clearInterval(timer);claimRewardsShareReward('facebook',title,target);}},1000);
}
function shareRewardsWA(url,title,target) {
  window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(title+' '+url)}`,'_blank');
  claimRewardsShareReward('whatsapp',title,target);
}
async function claimRewardsShareReward(platform,title,target='app') {
  try {
    const fd=new FormData(); fd.append('action','claim_share_reward'); fd.append('platform',platform); fd.append('target',target);
    const r=await fetch('api/wallet.php',{method:'POST',body:fd}); const res=await r.json();
    if (res.success) { showToast(res.message,'success'); loadWalletData(); }
    else showToast(res.error||'Nepodarilo sa pripísať odmenu.','error');
  } catch(e) { console.error(e); }
}
function selectTopupAmount(amount,btn) {
  g_selectedTopupAmount=amount;
  document.querySelectorAll('#topup-amount-buttons .topup-pill').forEach(b=>b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  const btnText=document.getElementById('topup-submit-btn-text'); if (btnText) btnText.innerText=`Dobiť ${amount.toFixed(2).replace('.',',')} €`;
}
async function processTopup() {
  let amount=g_selectedTopupAmount;
  if (!amount||amount<5) { showToast('Minimálna suma je 5,00 €','error'); return; }
  // Dobitie ide cez Stripe Checkout — kredit sa pripíše až po potvrdenej platbe (api/stripe_webhook.php)
  const fd=new FormData(); fd.append('action','create_topup_session'); fd.append('amount',amount);
  try {
    const r=await fetch('api/stripe_checkout.php',{method:'POST',body:fd}); const res=await r.json();
    if (res.success && res.checkout_url) { window.location.href=res.checkout_url; }
    else showToast(res.message||'Chyba pri otváraní platby','error');
  } catch(e) { showToast('Chyba komunikácie','error'); }
}
async function purchaseBoost(boostKey) {
  const fd=new FormData(); fd.append('action','purchase_boost'); fd.append('boost_key',boostKey);
  try {
    const r=await fetch('api/wallet.php',{method:'POST',body:fd}); const res=await r.json();
    if (res.success) { showToast(res.message||'Aktivované!','success'); loadWalletData(); }
    else showToast(res.error||'Nepodarilo sa aktivovať','error');
  } catch(e) { showToast('Chyba komunikácie','error'); }
}
function renderSmsTable() {
  const tbody=document.getElementById('sms-packages-tbody'); if (!tbody) return;
  const packages=g_walletData?.sms_packages||{
    10000:{price:800,unit_price:0.08},
    5000:{price:450,unit_price:0.09},
    1000:{price:100,unit_price:0.10},
    500:{price:60,unit_price:0.12},
    100:{price:15,unit_price:0.15}
  };
  let html='';
  [10000,5000,1000,500,100].forEach(cnt=>{
    const item=packages[cnt]; if (!item) return;
    const isSel=(g_selectedSmsCount===cnt);
    const priceFmt=`${item.price.toFixed(2).replace('.',',')} €`;
    const unitFmt=`${item.unit_price.toFixed(2).replace('.',',')} €`;
    html+=`<tr style="border-bottom:1px solid var(--border-color);cursor:pointer;background:${isSel?'rgba(59,130,246,0.08)':'transparent'};" onclick="selectSmsRow(${cnt})">
      <td style="padding:14px;font-weight:700;"><label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0;"><input type="radio" name="sms_pkg_radio" value="${cnt}" ${isSel?'checked':''} style="accent-color:#3b82f6;"><span>${cnt.toLocaleString('sk-SK')}</span></label></td>
      <td style="padding:14px;font-weight:800;">${priceFmt}</td>
      <td style="padding:14px;color:var(--text-secondary);">${unitFmt}</td>
      <td style="padding:14px;text-align:right;"><button type="button" onclick="event.stopPropagation();g_selectedSmsCount=${cnt};renderSmsTable();" class="btn-secondary" style="padding:6px 14px;font-size:12px;border-radius:8px;">Vybrať</button></td>
    </tr>`;
  });
  tbody.innerHTML=html;
}
function selectSmsRow(cnt) { g_selectedSmsCount=cnt; renderSmsTable(); }
async function buySelectedSms() {
  const fd=new FormData(); fd.append('action','purchase_sms'); fd.append('count',g_selectedSmsCount);
  try {
    const r=await fetch('api/wallet.php',{method:'POST',body:fd}); const res=await r.json();
    if (res.success) { showToast(res.message||'SMS kredity zakúpené!','success'); loadWalletData(); }
    else if (res.need_topup) { showToast(res.error,'error'); }
    else showToast(res.error||'Nepodarilo sa kúpiť SMS kredity','error');
  } catch(e) { showToast('Chyba komunikácie','error'); }
}
function updateSmsSenderUI() {
  const badge=document.getElementById('sms-sender-status');
  const input=document.getElementById('sms-sender-input');
  const btnText=document.getElementById('sms-sender-btn-text');
  const name=g_walletData?.sms_sender_name||'';
  if (badge) {
    if (name) { badge.innerText='Aktívne: '+name; badge.style.background='rgba(16,185,129,0.12)'; badge.style.color='#10b981'; }
    else { badge.innerText='Neaktivované'; badge.style.background='rgba(100,116,139,0.12)'; badge.style.color='var(--text-secondary)'; }
  }
  if (input && !input.value) input.value=name;
  if (btnText) btnText.innerText = name ? 'Zmeniť meno' : 'Aktivovať (49 €/jednorazovo)';
}
async function activateSmsSender() {
  const input=document.getElementById('sms-sender-input');
  const name=(input?.value||'').trim();
  if (!name) { showToast('Zadaj meno odosielateľa.','error'); return; }
  const btn=document.getElementById('sms-sender-btn');
  btn.disabled=true;
  try {
    const fd=new FormData(); fd.append('action','activate_sms_sender'); fd.append('sender_name',name);
    const r=await fetch('api/wallet.php',{method:'POST',body:fd}); const res=await r.json();
    if (res.success) { showToast(res.message,'success'); loadWalletData(); }
    else if (res.need_topup) { showToast(res.error,'error'); }
    else showToast(res.error||'Nepodarilo sa aktivovať.','error');
  } catch(e) { showToast('Chyba komunikácie','error'); }
  finally { btn.disabled=false; }
}
function renderWalletTransactions() {
  const list=document.getElementById('ext-transactions-list'); if (!list) return;
  const trs=g_walletData?.transactions||[];
  if (!trs.length) { list.innerHTML='<p style="font-size:12.5px;color:var(--text-secondary);margin:0;">Zatiaľ nemáte žiadne transakcie.</p>'; return; }
  let html='';
  trs.forEach(t=>{
    const amt=parseFloat(t.amount), isPlus=amt>0;
    const dateStr=new Date(t.created_at).toLocaleDateString('sk-SK')+' '+new Date(t.created_at).toLocaleTimeString('sk-SK',{hour:'2-digit',minute:'2-digit'});
    html+=`<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:8px;font-size:12.5px;">
      <div><strong style="display:block;color:var(--text-primary);">${t.description||''}</strong><span style="font-size:11px;color:var(--text-secondary);">${dateStr}</span></div>
      <strong style="color:${isPlus?'#10b981':'var(--text-primary)'};font-size:13.5px;white-space:nowrap;">${isPlus?'+':''}${amt.toFixed(2).replace('.',',')} €</strong>
    </div>`;
  });
  list.innerHTML=html;
}

function showToast(msg,type='success') {
  if (typeof showAppToast==='function'){showAppToast(msg,type);return;}
  let c=document.getElementById('_tc');if(!c){c=document.createElement('div');c.id='_tc';c.style.cssText='position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';document.body.appendChild(c);}
  const t=document.createElement('div');t.style.cssText=`background:${type==='error'?'#ef4444':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;opacity:0;transform:translateY(-15px);transition:all 0.3s;box-shadow:0 10px 25px rgba(0,0,0,0.2);`;
  t.innerText=msg;c.appendChild(t);setTimeout(()=>{t.style.opacity='1';t.style.transform='translateY(0)';},10);setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(-15px)';setTimeout(()=>t.remove(),300);},3500);
}

document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.body.classList.contains('dark-mode');
  const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
  init();
});
function init() {
  loadWalletData();
  const params = new URLSearchParams(window.location.search);
  if (params.get('stripe') === 'success') {
    showToast('Platba prijatá, spracúvame dobitie kreditu...', 'success');
    // Webhook od Stripe zvyčajne príde do pár sekúnd — pár krátkych re-fetchov namiesto jedného okamžitého
    setTimeout(loadWalletData, 2000);
    setTimeout(loadWalletData, 5000);
    window.history.replaceState({}, '', window.location.pathname);
  } else if (params.get('stripe') === 'cancel') {
    showToast('Platba bola zrušená.', 'error');
    window.history.replaceState({}, '', window.location.pathname);
  }
}
</script>
</body>
</html>
