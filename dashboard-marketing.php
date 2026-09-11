<?php
require_once 'config.php';
require_once 'includes/ai_credit_helper.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('crm');
$pageTitle = 'Marketing - ' . BRAND_NAME;
$currentPage = 'marketing';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Marketing'; $headerIcon = 'campaign'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">

    <div class="vueto-card">

      <!-- TAB BAR -->
      <div class="vueto-card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;border-bottom:1px solid var(--border-color);padding-bottom:15px;">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <button type="button" class="btn-primary mkt-tab-btn" id="mkt-btn-emails" onclick="switchMarketingTab('emails')" style="padding:9px 18px;font-size:13.5px;border-radius:12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">mail</span>
            <span>E-mailové kampane</span>
          </button>
          <button type="button" class="btn-secondary mkt-tab-btn" id="mkt-btn-vouchers" onclick="switchMarketingTab('vouchers')" style="padding:9px 18px;font-size:13.5px;border-radius:12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">local_offer</span>
            <span>Vouchery a Zľavy</span>
          </button>
          <button type="button" class="btn-secondary mkt-tab-btn" id="mkt-btn-giftvouchers" onclick="switchMarketingTab('giftvouchers'); loadGiftVouchers();" style="padding:9px 18px;font-size:13.5px;border-radius:12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">card_giftcard</span>
            <span>Darčekové poukazy</span>
          </button>
          <button type="button" class="btn-secondary mkt-tab-btn" id="mkt-btn-permanentky" onclick="switchMarketingTab('permanentky'); loadOverview(); loadPackages(); loadPurchases();" style="padding:9px 18px;font-size:13.5px;border-radius:12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">card_membership</span>
            <span>Permanentky</span>
          </button>
          <button type="button" class="btn-secondary mkt-tab-btn" id="mkt-btn-loyalty" onclick="switchMarketingTab('loyalty'); loadLoyaltyProgram(); loadLoyaltyMembers();" style="padding:9px 18px;font-size:13.5px;border-radius:12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">loyalty</span>
            <span>Vernostný program</span>
          </button>
          <button type="button" class="btn-secondary mkt-tab-btn" id="mkt-btn-newsletter" onclick="switchMarketingTab('newsletter'); loadNewsletterSubscriberCount();" style="padding:9px 18px;font-size:13.5px;border-radius:12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">campaign</span>
            <span>Novinky prevádzky</span>
          </button>
          <button type="button" class="btn-secondary mkt-tab-btn" id="mkt-btn-utm" onclick="switchMarketingTab('utm')" style="padding:9px 18px;font-size:13.5px;border-radius:12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">insights</span>
            <span>Zdroje dopytov (UTM)</span>
          </button>
        </div>
        <div>
          <span style="font-size:12px;color:var(--text-secondary);display:flex;align-items:center;gap:4px;">
            <span class="material-symbols-outlined" style="font-size:16px;color:var(--primary-color);">verified</span>
            Hromadná komunikácia s klientmi
          </span>
        </div>
      </div>

      <div style="padding:25px 20px;">

        <!-- ═══ TAB 1: E-MAILOVÉ KAMPANE ═══ -->
        <div id="mkt-tab-emails" class="mkt-tab-content">

          <?php
          require_once __DIR__ . '/includes/holidays_helper.php';
          // Include the nameday data logic from marketing.php
          $today_key = date('m-d');
          $sk_namedays_file = __DIR__ . '/libs/namedays_sk.json';
          if (!file_exists($sk_namedays_file)) {
              $sk_namedays_file = __DIR__ . '/libs/namedays.json';
          }
          $sk_namedays_arr = file_exists($sk_namedays_file) ? json_decode(file_get_contents($sk_namedays_file), true) : [];
          $today_sk_nameday = in_array($today_key, getNonNameNamedayKeys(), true) ? '' : ($sk_namedays_arr[$today_key] ?? 'Lýdia');
          ?>

          <!-- DNEŠNÉ MENINY -->
          <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px 24px;margin-bottom:25px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:20px;">
              <div style="display:flex;align-items:flex-start;gap:16px;flex:1;min-width:290px;">
                <div style="width:50px;height:50px;border-radius:14px;background:rgba(176,128,66,0.15);display:flex;align-items:center;justify-content:center;color:var(--primary-color);flex-shrink:0;">
                  <span class="material-symbols-outlined" style="font-size:26px;">cake</span>
                </div>
                <div style="flex:1;">
                  <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:0.5px;">Dnes má meniny:</span>
                    <span id="nameday-sk-name" style="font-size:19px;font-weight:800;color:var(--primary-color);"><?= $today_sk_nameday !== '' ? htmlspecialchars($today_sk_nameday) : '—' ?></span>
                  </div>
                  <div id="nameday-celebrants-box" style="margin-top:12px;padding:12px 16px;border-radius:12px;background:var(--card-bg);border:1px solid var(--border-color);">
                    <div id="nameday-celebrants-info" style="font-size:13.5px;color:var(--text-primary);">
                      <?php if ($today_sk_nameday === ''): ?>
                      <span style="color:var(--text-secondary);display:inline-flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">info</span> Dnes nemá v kalendári nikto meniny (len štátny sviatok).</span>
                      <?php else: ?>
                      <span style="color:var(--text-secondary);display:inline-flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">info</span> V kontaktoch nemáte žiadneho zákazníka s týmto menom.</span>
                      <?php endif; ?>
                    </div>
                    <div id="nameday-celebrants-pills" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;"></div>
                  </div>
                </div>
              </div>
              <div style="flex-shrink:0;">
                <button type="button" onclick="prepareNamedayCampaign()" class="btn-primary" style="padding:11px 22px;font-size:13.5px;font-weight:700;border-radius:12px;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;" <?= $today_sk_nameday === '' ? 'disabled' : '' ?>>
                  <span class="material-symbols-outlined" style="font-size:20px;">celebration</span>
                  <span>Pripraviť blahoželanie</span>
                </button>
              </div>
            </div>
          </div>

          <!-- DNEŠNÉ NARODENINY -->
          <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px 24px;margin-bottom:25px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:20px;">
              <div style="display:flex;align-items:flex-start;gap:16px;flex:1;min-width:290px;">
                <div style="width:50px;height:50px;border-radius:14px;background:rgba(236,72,153,0.15);display:flex;align-items:center;justify-content:center;color:#ec4899;flex-shrink:0;">
                  <span class="material-symbols-outlined" style="font-size:26px;">cake</span>
                </div>
                <div style="flex:1;">
                  <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:0.5px;">Dnes má narodeniny:</span>
                  </div>
                  <div id="birthday-celebrants-box" style="margin-top:12px;padding:12px 16px;border-radius:12px;background:var(--card-bg);border:1px solid var(--border-color);">
                    <div id="birthday-celebrants-info" style="font-size:13.5px;color:var(--text-primary);">
                      <span style="color:var(--text-secondary);display:inline-flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">info</span> Žiadny zákazník dnes neoslavuje narodeniny (alebo si dátum narodenia ešte nezadal).</span>
                    </div>
                    <div id="birthday-celebrants-pills" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;"></div>
                  </div>
                </div>
              </div>
              <div style="flex-shrink:0;">
                <button type="button" id="btn-birthday-campaign" onclick="prepareBirthdayCampaign()" class="btn-primary" style="padding:11px 22px;font-size:13.5px;font-weight:700;border-radius:12px;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;background:#ec4899;" disabled>
                  <span class="material-symbols-outlined" style="font-size:20px;">redeem</span>
                  <span>Poslať prianie so zľavou</span>
                </button>
              </div>
            </div>
          </div>

          <!-- BLÍŽIACE SA SVIATKY (režim "Pripomenúť vopred") -->
          <div id="upcoming-reminders-box" style="display:none;flex-direction:column;gap:12px;margin-bottom:25px;"></div>

          <!-- AUTOMATIZÁCIA POZDRAVOV -->
          <div class="vueto-card" style="margin-bottom:25px;">
            <div class="vueto-card-header" style="cursor:pointer;" onclick="toggleAutomationPanel()">
              <div>
                <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined">auto_awesome</span> Automatizácia pozdravov</h2>
                <p class="section-desc" style="margin:4px 0 0 0;">Pre každú príležitosť si zvoľte, či ju riešite manuálne, chcete len pripomenúť vopred, alebo má systém posielať úplne automaticky.</p>
              </div>
              <span class="material-symbols-outlined" id="automation-toggle-icon">expand_more</span>
            </div>
            <div class="vueto-card-body" id="automation-panel" style="display:none;">
              <div class="vueto-table-wrapper" style="overflow-x:auto;">
                <table class="vueto-table" style="width:100%;min-width:900px;">
                  <thead>
                    <tr>
                      <th>Príležitosť</th><th>Režim</th><th>Dní vopred</th><th>Odmena</th><th>Zľava</th><th>Platnosť kódu</th><th>Šablóna</th><th>Text správy</th>
                    </tr>
                  </thead>
                  <tbody id="automation-settings-tbody">
                    <tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-secondary);">Načítavam...</td></tr>
                  </tbody>
                </table>
              </div>
              <button type="button" onclick="saveOccasionSettings()" class="btn-primary" style="margin-top:16px;padding:11px 22px;font-size:14px;">
                <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">save</span> Uložiť automatizáciu
              </button>
              <span id="automation-save-msg" style="margin-left:12px;font-size:13px;"></span>
            </div>
          </div>

          <!-- FILTRE PRÍJEMCOV -->
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:var(--primary-color);">send</span> Hromadná správa
            </h3>
          </div>

          <div id="marketing-filter-bar">
          <div style="display:flex;flex-wrap:wrap;gap:16px;margin:-10px 0 14px 0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
              <span style="font-size:11.5px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.3px;">Návštevy:</span>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('visits_1',this)" style="padding:5px 12px;font-size:12px;border-radius:10px;">Noví (1×) (<span class="m-count-visits_1">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('visits_2_4',this)" style="padding:5px 12px;font-size:12px;border-radius:10px;">Vracajúci sa (2-4×) (<span class="m-count-visits_2_4">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('visits_5plus',this)" style="padding:5px 12px;font-size:12px;border-radius:10px;">Verní (5×+) (<span class="m-count-visits_5plus">0</span>)</button>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
              <span style="font-size:11.5px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.3px;">Útrata:</span>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('spend_low',this)" style="padding:5px 12px;font-size:12px;border-radius:10px;">Nízka (&lt;30 €) (<span class="m-count-spend_low">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('spend_mid',this)" style="padding:5px 12px;font-size:12px;border-radius:10px;">Stredná (30-100 €) (<span class="m-count-spend_mid">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('spend_high',this)" style="padding:5px 12px;font-size:12px;border-radius:10px;">Vysoká (100 €+) (<span class="m-count-spend_high">0</span>)</button>
            </div>
          </div>

          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
              <button type="button" class="btn-primary" onclick="setMarketingFilter('all',this)" style="padding:6px 14px;font-size:12.5px;border-radius:10px;">Všetci (<span class="m-count-all">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('nameday',this)" style="padding:6px 14px;font-size:12.5px;border-radius:10px;"><span class="material-symbols-outlined" style="font-size:15px;">cake</span> Meniny (<span class="m-count-nameday">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('female',this)" style="padding:6px 14px;font-size:12.5px;border-radius:10px;">Ženy (<span class="m-count-female">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('male',this)" style="padding:6px 14px;font-size:12.5px;border-radius:10px;">Muži (<span class="m-count-male">0</span>)</button>
              <button type="button" class="btn-secondary" onclick="setMarketingFilter('other_gender',this)" style="padding:6px 14px;font-size:12.5px;border-radius:10px;">Iné (<span class="m-count-other-gender">0</span>)</button>
          </div>
          </div>

          <!-- GRID: FORMULÁR + PRÍJEMCOVIA -->
          <div style="display:grid;grid-template-columns:2fr 1.2fr;gap:25px;align-items:start;" class="marketing-grid-responsive">

            <div style="display:flex;flex-direction:column;gap:20px;">
              <!-- Šablóna -->
              <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px;">
                <div style="font-size:13px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:0.5px;margin-bottom:12px;">1. Výber šablóny</div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:15px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:15px;flex-wrap:wrap;">
                  <div style="display:flex;align-items:center;gap:14px;">
                    <div id="tpl-display-icon" style="width:50px;height:50px;border-radius:10px;background:rgba(176,128,66,0.15);display:flex;align-items:center;justify-content:center;color:var(--primary-color);flex-shrink:0;">
                      <span class="material-symbols-outlined" style="font-size:26px;">description</span>
                    </div>
                    <div>
                      <strong id="tpl-display-title" style="font-size:15px;color:var(--text-primary);display:block;">Iba text (bez grafiky)</strong>
                      <span id="tpl-display-desc" style="font-size:12.5px;color:var(--text-secondary);">Základná e-mailová správa bez HTML šablóny</span>
                    </div>
                  </div>
                  <button type="button" onclick="openMarketingTemplateModal()" class="btn-secondary" style="padding:8px 16px;font-size:13px;border-radius:10px;display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">dashboard_customize</span> Zmeniť
                  </button>
                </div>
              </div>

              <!-- Obsah -->
              <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px;">
                <div style="font-size:13px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:0.5px;margin-bottom:12px;">2. Obsah e-mailu</div>
                <div class="form-group" style="margin-bottom:15px;">
                  <label style="font-size:13px;font-weight:600;margin-bottom:6px;display:block;">Predmet <span style="color:red">*</span></label>
                  <input type="text" id="campaign-subject" placeholder="Napr. Špeciálna ponuka pre vás!" style="width:100%;padding:12px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <label style="font-size:13px;font-weight:600;margin-bottom:0;">Text správy <span style="color:red">*</span></label>
                    <span style="font-size:11.5px;color:var(--text-secondary);">Zástupné znaky: <code>{{NAME}}</code> meno, <code>{{SLUZBA}}</code> posledná služba, <code>{{DATUM}}</code> posledná návšteva</span>
                  </div>
                  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-bottom:0;border-top-left-radius:10px;border-top-right-radius:10px;padding:8px 12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;color:var(--primary-color);display:flex;align-items:center;gap:4px;margin-right:6px;"><span class="material-symbols-outlined" style="font-size:16px;">auto_awesome</span> AI:</span>
                    <button type="button" onclick="runMarketingAI('fix_grammar')" class="btn-secondary" style="padding:4px 10px;font-size:11.5px;border-radius:8px;">Gramatika</button>
                    <button type="button" onclick="runMarketingAI('professional')" class="btn-secondary" style="padding:4px 10px;font-size:11.5px;border-radius:8px;">Profesionálne</button>
                    <button type="button" onclick="runMarketingAI('friendly')" class="btn-secondary" style="padding:4px 10px;font-size:11.5px;border-radius:8px;">Priateľsky</button>
                    <button type="button" onclick="runMarketingAI('shorten')" class="btn-secondary" style="padding:4px 10px;font-size:11.5px;border-radius:8px;">Skrátiť</button>
                    <span style="margin-left:auto;font-size:11px;font-weight:700;color:var(--text-primary);background:var(--bg-color);border:1px solid var(--border-color);border-radius:8px;padding:3px 9px;display:flex;align-items:center;gap:4px;">
                        <span class="material-symbols-outlined" style="font-size:13px;color:var(--primary-color);">bolt</span>
                        <span id="ai-credits-count"><?= (int)ai_credit_balance($conn, (int)$_SESSION['user_id']) ?></span>
                    </span>
                    <button type="button" onclick="openAiCreditsModal()" class="btn-secondary" style="padding:3px 9px;font-size:11px;border-radius:8px;">Dokúpiť</button>
                  </div>
                  <textarea id="campaign-content" rows="7" placeholder="Napíšte vašu správu..." style="width:100%;padding:14px;border:1px solid var(--border-color);border-bottom-left-radius:10px;border-bottom-right-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;resize:vertical;line-height:1.5;"></textarea>
                </div>
              </div>

              <!-- Test -->
              <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px;">
                <div style="font-size:13px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:0.5px;margin-bottom:12px;">3. Test pred odoslaním</div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                  <input type="email" id="marketing-test-email" placeholder="Váš testovací e-mail..." style="flex:1;min-width:220px;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:13.5px;box-sizing:border-box;">
                  <button type="button" onclick="sendMarketingTestEmail()" class="btn-secondary" style="padding:10px 18px;font-size:13px;border-radius:12px;display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">send_time_extension</span> Test
                  </button>
                  <button type="button" onclick="openMarketingPreviewModal()" class="btn-secondary" style="padding:10px 18px;font-size:13px;border-radius:12px;display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">visibility</span> Náhľad
                  </button>
                </div>
              </div>
            </div>

            <!-- Príjemcovia -->
            <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px;position:sticky;top:20px;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h4 style="margin:0;font-size:16px;font-weight:700;">Príjemcovia (<span id="recipients-active-count">0</span>)</h4>
                <span style="font-size:12px;color:var(--text-secondary);">Aktívny výber</span>
              </div>
              <div style="margin-bottom:15px;position:relative;">
                <input type="text" id="recipients-search-input" oninput="filterRecipientsBySearch(this.value)" placeholder="Filtrovať..." style="width:100%;padding:9px 12px 9px 34px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:13px;box-sizing:border-box;">
                <span class="material-symbols-outlined" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:18px;color:var(--text-secondary);">search</span>
              </div>
              <div id="recipients-scroll-box" style="max-height:420px;overflow-y:auto;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);">
                <div id="recipients-list-tbody" style="display:flex;flex-direction:column;">
                  <div style="text-align:center;padding:25px;color:var(--text-secondary);font-size:13px;">Načítavam klientov...</div>
                </div>
              </div>
              <div style="margin-top:20px;">
                <button type="button" onclick="submitMarketingCampaign()" id="btn-submit-campaign-main" class="btn-primary" style="width:100%;padding:14px 20px;font-size:15px;font-weight:700;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;">
                  <span class="material-symbols-outlined">forward_to_inbox</span>
                  <span>Spustiť kampaň (<span id="btn-count-num">0</span>)</span>
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- ═══ TAB 2: UTM ═══ -->
        <div id="mkt-tab-utm" class="mkt-tab-content" style="display:none;">
          <h3 style="margin:0 0 15px 0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">analytics</span> Prehľad zdrojov návštevnosti (UTM)
          </h3>
          <div class="vueto-table-wrapper" style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;overflow-x:auto;">
            <table class="vueto-table" style="width:100%;">
              <thead>
                <tr>
                  <th>DÁTUM</th><th>ZÁKAZNÍK</th><th>ZDROJ / MÉDIUM</th><th>KAMPAŇ</th><th>CIEĽOVÁ STRÁNKA</th>
                </tr>
              </thead>
              <tbody id="utm-leads-tbody">
                <tr><td colspan="5" style="text-align:center;padding:25px;color:var(--text-secondary);">Načítavam UTM dáta...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ═══ TAB: NOVINKY PREVÁDZKY ═══ -->
        <div id="mkt-tab-newsletter" class="mkt-tab-content" style="display:none;">
          <h3 style="margin:0 0 6px 0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--primary-color);">campaign</span> Novinky prevádzky
          </h3>
          <p style="font-size:13px;color:var(--text-secondary);margin:0 0 18px 0;max-width:640px;">Samostatný kanál od hromadných kampaní — na aktuality o prevádzke (nová kolegyňa, zmena hodín, nová služba) sa zákazníci prihlasujú zvlášť, na vašom verejnom profile alebo vo svojom účte. Dostupné od balíka Pro.</p>
          <div id="newsletter-locked-box" style="display:none;background:var(--bg-color);border:1px solid var(--border-color);border-radius:12px;padding:18px 20px;max-width:560px;">
            <p style="margin:0;font-size:13.5px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px;">lock</span> Táto funkcia je dostupná od balíka Pro vyššie. <a href="dashboard-balik.php">Prejsť na vyšší balík</a></p>
          </div>
          <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
          <div id="newsletter-content-box" style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:20px;max-width:640px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
              <span style="font-size:13px;color:var(--text-secondary);">Počet potvrdených odberateľov</span>
              <strong id="newsletter-sub-count" style="font-size:15px;color:var(--text-primary);">–</strong>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
              <label style="font-size:12.5px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.3px;display:block;margin-bottom:6px;">Predmet e-mailu</label>
              <input type="text" id="newsletter-subject" placeholder="Napr. Máme novú kolegyňu!" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
              <label style="font-size:12.5px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.3px;display:block;margin-bottom:6px;">Text správy</label>
              <div id="newsletter-editor" style="background:var(--card-bg);border-radius:8px;min-height:180px;">Radi by sme vám oznámili, že sme prijali nového kolegu...</div>
            </div>
            <button type="button" onclick="sendNewsletterUpdate()" class="btn-primary" id="btn-send-newsletter" style="padding:10px 22px;">
              <span class="material-symbols-outlined" style="font-size:18px;">send</span> Odoslať novinku
            </button>
          </div>
          <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
          <script>
            var newsletterQuill = null;
            document.addEventListener('DOMContentLoaded', function () {
              var el = document.getElementById('newsletter-editor');
              if (!el || typeof Quill === 'undefined') return;
              newsletterQuill = new Quill('#newsletter-editor', {
                theme: 'snow',
                modules: {
                  toolbar: [
                    [{ 'font': [] }, { 'size': [] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'clean']
                  ]
                }
              });
            });
          </script>
        </div>

        <!-- ═══ TAB 3: VOUCHERY & ZĽAVY ═══ -->
        <div id="mkt-tab-vouchers" class="mkt-tab-content" style="display:none;">

          <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start;" class="voucher-grid-responsive">

            <!-- Zoznam voucherov -->
            <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:16px;padding:24px;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="margin:0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
                  <span class="material-symbols-outlined" style="color:var(--primary-color);">local_offer</span> Moje vouchery
                </h3>
                <button type="button" onclick="loadVouchers()" class="btn-secondary" style="padding:7px 14px;font-size:13px;border-radius:10px;display:flex;align-items:center;gap:6px;">
                  <span class="material-symbols-outlined" style="font-size:16px;">refresh</span> Obnoviť
                </button>
              </div>
              <div id="vouchers-list" style="display:flex;flex-direction:column;gap:12px;">
                <div style="text-align:center;padding:30px;color:var(--text-secondary);">Načítavam vouchery...</div>
              </div>
            </div>

            <!-- Formulár vytvorenia -->
            <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:16px;padding:24px;position:sticky;top:20px;">
              <h4 style="margin:0 0 18px 0;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color);">add_circle</span> Vytvoriť nový voucher
              </h4>

              <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Kód vouchera <span style="color:red">*</span></label>
                <input type="text" id="v-code" placeholder="Napr. ZIMA20" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;text-transform:uppercase;">
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div class="form-group">
                  <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Typ zľavy</label>
                  <select id="v-discount-type" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;">
                    <option value="percent">Percentuálna (%)</option>
                    <option value="fixed">Pevná suma (€)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Hodnota zľavy <span style="color:red">*</span></label>
                  <input type="number" id="v-discount-value" placeholder="Napr. 10" min="0" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;">
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div class="form-group">
                  <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Platný do</label>
                  <input type="date" id="v-valid-until" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;">
                </div>
                <div class="form-group">
                  <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Max. použití</label>
                  <input type="number" id="v-max-uses" placeholder="Neobmedzene" min="1" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;">
                </div>
              </div>

              <div class="form-group" style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Popis (nepovinné)</label>
                <textarea id="v-description" rows="2" placeholder="Napr. Zľava na zimnú akciu..." style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);box-sizing:border-box;font-size:14px;resize:vertical;"></textarea>
              </div>

              <button type="button" onclick="createVoucher()" class="btn-primary" style="width:100%;padding:13px;font-size:14px;font-weight:700;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;">
                <span class="material-symbols-outlined">add_circle</span> Vytvoriť voucher
              </button>
            </div>
          </div>
        </div>

        <div id="mkt-tab-giftvouchers" class="mkt-tab-content" style="display:none;">
          <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start;" class="voucher-grid-responsive">
            <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:16px;padding:24px;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="margin:0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
                  <span class="material-symbols-outlined" style="color:var(--primary-color);">card_giftcard</span> Predané darčekové poukazy
                </h3>
                <button type="button" onclick="loadGiftVouchers()" class="btn-secondary" style="padding:7px 14px;font-size:13px;border-radius:10px;display:flex;align-items:center;gap:6px;">
                  <span class="material-symbols-outlined" style="font-size:16px;">refresh</span> Obnoviť
                </button>
              </div>
              <div id="gift-vouchers-list" style="display:flex;flex-direction:column;gap:12px;">
                <div style="text-align:center;padding:30px;color:var(--text-secondary);">Načítavam...</div>
              </div>
            </div>

            <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:16px;padding:24px;position:sticky;top:20px;">
              <h4 style="margin:0 0 14px 0;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--primary-color);">insights</span> Prehľad
              </h4>
              <div style="display:flex;flex-direction:column;gap:10px;font-size:13.5px;">
                <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-secondary);">Čaká na potvrdenie:</span><strong id="gv-stat-pending">0</strong></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-secondary);">Aktívne poukazy:</span><strong id="gv-stat-active">0</strong></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-secondary);">Nevyčerpaný zostatok:</span><strong id="gv-stat-outstanding">0,00 €</strong></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-secondary);">Tržba tento mesiac:</span><strong id="gv-stat-revenue">0,00 €</strong></div>
              </div>
              <p style="font-size:12.5px;color:var(--text-secondary);margin-top:16px;line-height:1.5;">
                Zákazníci si darčekové poukazy kupujú priamo na vašej verejnej stránke — vyberú si sumu, zaplatia cez QR kód na váš účet a vy tu iba potvrdíte prijatie platby, čím sa poukaz aktivuje. Aktívny poukaz potom môže ktokoľvek uplatniť pri rezervácii pomocou jeho kódu.
              </p>
            </div>
          </div>
        </div>

        <!-- ═══ TAB: PERMANENTKY ═══ -->
        <div id="mkt-tab-permanentky" class="mkt-tab-content" style="display:none;">
          <div class="vueto-card" style="margin-bottom: 20px;">
            <div class="vueto-card-header">
              <div>
                <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined">insights</span> Prehľad</h2>
                <p class="section-desc" style="margin:4px 0 0 0;">Peniaze idú vždy priamo na váš bankový účet (podľa IBAN v nastaveniach prevádzky) — Rezervos platby nespracúva, len eviduje ich stav.</p>
              </div>
            </div>
            <div class="vueto-card-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;" id="sales-overview-grid">
              <div style="background:var(--bg-color);border-radius:12px;padding:16px;text-align:center;">
                <div style="font-size:24px;font-weight:800;color:var(--primary-color);" id="ov-active">–</div>
                <div style="font-size:12px;color:var(--text-secondary);">Aktívnych permanentiek</div>
              </div>
              <div style="background:var(--bg-color);border-radius:12px;padding:16px;text-align:center;">
                <div style="font-size:24px;font-weight:800;color:#f59e0b;" id="ov-pending">–</div>
                <div style="font-size:12px;color:var(--text-secondary);">Čaká na potvrdenie platby</div>
              </div>
              <div style="background:var(--bg-color);border-radius:12px;padding:16px;text-align:center;">
                <div style="font-size:24px;font-weight:800;color:#10b981;" id="ov-revenue">–</div>
                <div style="font-size:12px;color:var(--text-secondary);">Tržba tento mesiac</div>
              </div>
            </div>
          </div>

          <div class="vueto-card" style="margin-bottom: 20px;">
            <div class="vueto-card-header">
              <div>
                <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined">card_membership</span> Moje balíčky a členstvá</h2>
                <p class="section-desc" style="margin:4px 0 0 0;">Balíček = jednorazový nákup N návštev. Členstvo = pravidelná mesačná platba.</p>
              </div>
              <button type="button" onclick="document.getElementById('package-modal').style.display='flex'" class="btn-primary" style="padding:9px 18px;font-size:13px;">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add</span> Vytvoriť balíček
              </button>
            </div>
            <div class="vueto-card-body" id="packages-list">
              <p style="color:var(--text-secondary);">Načítavam...</p>
            </div>
          </div>

          <div class="vueto-card">
            <div class="vueto-card-header">
              <div>
                <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined">receipt_long</span> Predaje zákazníkom</h2>
                <p class="section-desc" style="margin:4px 0 0 0;">Keď zákazník zaplatí priamo na váš účet, potvrďte to tu — tým sa mu permanentka aktivuje.</p>
              </div>
            </div>
            <div class="vueto-card-body" id="purchases-list">
              <p style="color:var(--text-secondary);">Načítavam...</p>
            </div>
          </div>
        </div>

        <!-- ═══ TAB: VERNOSTNÝ PROGRAM ═══ -->
        <div id="mkt-tab-loyalty" class="mkt-tab-content" style="display:none;">
          <div class="vueto-card" style="margin-bottom: 20px;">
            <div class="vueto-card-header">
              <div>
                <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined">loyalty</span> Nastavenie vernostného programu</h2>
                <p class="section-desc" style="margin:4px 0 0 0;">Digitálna "pečiatková karta" — zákazník zbiera návštevy (alebo minutú sumu) a po dosiahnutí prahu dostane odmenu.</p>
              </div>
            </div>
            <div class="vueto-card-body">
              <label style="display:flex;align-items:center;gap:10px;margin-bottom:18px;cursor:pointer;">
                <input type="checkbox" id="lp-is-active" style="width:18px;height:18px;">
                <span style="font-weight:700;">Program je aktívny</span>
              </label>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="form-group">
                  <label>Odmena sa počíta podľa</label>
                  <select id="lp-reward-type" class="form-control" onchange="toggleThresholdLabel()">
                    <option value="visits">Počtu návštev</option>
                    <option value="spend">Minutej sumy (€)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label id="lp-threshold-label">Počet návštev na odmenu</label>
                  <input type="number" id="lp-reward-threshold" class="form-control" min="1" step="1" value="10">
                </div>
              </div>

              <div class="form-group" style="margin-bottom:18px;">
                <label>Popis odmeny</label>
                <input type="text" id="lp-reward-description" class="form-control" placeholder="Napr. Jedna služba zdarma / 15 % zľava na ďalšiu návštevu">
              </div>

              <button type="button" onclick="saveLoyaltyProgram()" class="btn-primary" style="padding:11px 22px;font-size:14px;">
                <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">save</span> Uložiť nastavenie
              </button>
              <span id="lp-save-msg" style="margin-left:12px;font-size:13px;"></span>
            </div>
          </div>

          <div class="vueto-card">
            <div class="vueto-card-header">
              <div>
                <h2 class="section-header" style="margin:0;"><span class="material-symbols-outlined">groups</span> Zákazníci v programe</h2>
                <p class="section-desc" style="margin:4px 0 0 0;">Keď zákazník príde vyzdvihnúť odmenu osobne, označte ju tu ako vyzdvihnutú.</p>
              </div>
              <button type="button" onclick="loadLoyaltyMembers()" class="btn-secondary" style="padding:8px 14px;font-size:13px;">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">refresh</span> Obnoviť
              </button>
            </div>
            <div class="vueto-card-body" id="loyalty-members-list">
              <p style="color:var(--text-secondary);">Načítavam...</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    </div>
  </div>
</div>

<!-- Modal: Vytvoriť balíček -->
<div id="package-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:1000;justify-content:center;align-items:center;overflow-y:auto;padding:20px;box-sizing:border-box;">
  <div style="background:var(--card-bg);border-radius:16px;padding:26px;max-width:480px;width:100%;border:1px solid var(--border-color);box-shadow:0 20px 40px rgba(0,0,0,0.35);margin:auto;">
    <h3 style="margin:0 0 18px 0;font-size:18px;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">card_membership</span> Nový balíček/členstvo</h3>
    <div class="form-group"><label>Názov *</label><input type="text" id="pkg-name" placeholder="Napr. 5x Masáž chrbta"></div>
    <div class="form-group">
      <label>Typ</label>
      <select id="pkg-type">
        <option value="package">Balíček (jednorazový nákup)</option>
        <option value="membership">Členstvo (pravidelná mesačná platba)</option>
      </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div class="form-group"><label>Cena (€) *</label><input type="number" id="pkg-price" value="50.00" min="0" step="0.5"></div>
      <div class="form-group"><label>Počet návštev *</label><input type="number" id="pkg-visits" value="5" min="1" step="1"></div>
    </div>
    <div class="form-group"><label>Platnosť (dní)</label><input type="number" id="pkg-validity" value="180" min="1" step="1"></div>
    <div class="form-group"><label>Popis (voliteľné)</label><textarea id="pkg-description" rows="2" placeholder="Čo balíček obsahuje..."></textarea></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
      <button type="button" onclick="document.getElementById('package-modal').style.display='none'" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
      <button type="button" onclick="createPackage()" class="btn-primary" style="padding:10px 20px;">Vytvoriť</button>
    </div>
  </div>
</div>

<!-- MODÁL: VÝBER ŠABLÓNY -->
<div id="marketingTemplateModal" class="modal-overlay" style="display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);z-index:2100;position:fixed;inset:0;">
  <div class="modal-content" style="max-width:750px;width:92%;background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;color:var(--text-primary);padding:0;overflow:hidden;">
    <div class="modal-header" style="padding:18px 24px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;font-size:17px;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">dashboard_customize</span> Vyberte grafickú šablónu</h3>
      <button type="button" onclick="document.getElementById('marketingTemplateModal').style.display='none'" style="background:transparent;border:none;color:var(--text-secondary);cursor:pointer;font-size:22px;">&times;</button>
    </div>
    <div style="padding:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px;max-height:60vh;overflow-y:auto;" id="templates-grid-box"></div>
    <div id="hidden-templates-box" style="display:none;padding:0 24px 18px 24px;"></div>
    <div style="padding:15px 24px;border-top:1px solid var(--border-color);background:var(--bg-color);display:flex;justify-content:flex-end;align-items:center;">
      <button type="button" onclick="document.getElementById('marketingTemplateModal').style.display='none'" class="btn-secondary" style="padding:8px 20px;border-radius:10px;">Zavrieť</button>
    </div>
  </div>
</div>

<!-- MODÁL: VYTVORIŤ / UPRAVIŤ VLASTNÚ ŠABLÓNU -->
<div id="createTemplateModal" class="modal-overlay" style="display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);z-index:2150;position:fixed;inset:0;">
  <div class="modal-content" style="max-width:420px;width:92%;background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;color:var(--text-primary);padding:0;overflow:hidden;">
    <div class="modal-header" style="padding:18px 24px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
      <h3 id="tpl-modal-title" style="margin:0;font-size:17px;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">add_photo_alternate</span> Vytvoriť vlastnú šablónu</h3>
      <button type="button" onclick="closeCreateTemplateModal()" style="background:transparent;border:none;color:var(--text-secondary);cursor:pointer;font-size:22px;">&times;</button>
    </div>
    <div style="padding:24px;">
      <div class="form-group" style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Názov šablóny <span style="color:red">*</span></label>
        <input type="text" id="tpl-new-name" placeholder="Napr. Vianoce 2026" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--bg-color);color:var(--text-primary);box-sizing:border-box;font-size:14px;">
      </div>
      <div class="form-group" style="margin-bottom:6px;">
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Obrázok (JPG/PNG/WEBP) <span style="color:red">*</span></label>
        <input type="file" id="tpl-new-image" accept=".jpg,.jpeg,.png,.webp" style="width:100%;padding:8px;border:1px solid var(--border-color);border-radius:10px;background:var(--bg-color);color:var(--text-primary);box-sizing:border-box;font-size:13px;">
      </div>
      <p id="tpl-image-hint" style="font-size:12px;color:var(--text-secondary);margin:6px 0 0 0;"></p>
      <p style="font-size:12px;color:var(--text-secondary);margin:6px 0 0 0;">Obrázok sa zobrazí navrchu e-mailu, pod ním váš text správy.</p>
    </div>
    <div style="padding:15px 24px;border-top:1px solid var(--border-color);background:var(--bg-color);display:flex;justify-content:flex-end;gap:10px;">
      <button type="button" onclick="closeCreateTemplateModal()" class="btn-secondary" style="padding:8px 20px;border-radius:10px;">Zrušiť</button>
      <button type="button" onclick="createCustomTemplate()" class="btn-primary" style="padding:8px 20px;border-radius:10px;">Uložiť</button>
    </div>
  </div>
</div>

<!-- MODÁL: ŽIVÝ NÁHĽAD -->
<div id="marketingPreviewModal" class="modal-overlay" style="display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.7);z-index:2200;position:fixed;inset:0;">
  <div class="modal-content" style="max-width:680px;width:95%;height:85vh;background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;display:flex;flex-direction:column;overflow:hidden;">
    <div class="modal-header" style="padding:16px 22px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
      <h3 style="margin:0;font-size:16px;"><span class="material-symbols-outlined" style="color:var(--primary-color);">preview</span> Náhľad e-mailu</h3>
      <button type="button" onclick="document.getElementById('marketingPreviewModal').style.display='none'" style="background:transparent;border:none;color:var(--text-secondary);cursor:pointer;font-size:22px;">&times;</button>
    </div>
    <div style="flex:1;overflow:hidden;background:#f0f2f5;">
      <iframe id="marketing-preview-iframe" style="width:100%;height:100%;border:none;display:block;"></iframe>
    </div>
    <div style="padding:12px 22px;border-top:1px solid var(--border-color);background:var(--bg-color);display:flex;justify-content:flex-end;flex-shrink:0;">
      <button type="button" onclick="document.getElementById('marketingPreviewModal').style.display='none'" class="btn-secondary" style="padding:8px 20px;border-radius:10px;">Zavrieť</button>
    </div>
  </div>
</div>

<div id="ai-credits-modal" class="modal-overlay" style="display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);z-index:2100;position:fixed;inset:0;">
  <div class="modal-content" style="max-width:480px;width:92%;background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;padding:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
      <h3 style="margin:0;font-size:17px;font-weight:800;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--primary-color);">bolt</span> Dokúpiť AI kredity
      </h3>
      <button type="button" onclick="closeAiCreditsModal()" style="background:none;border:none;cursor:pointer;color:var(--text-secondary);"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div id="ai-credits-exhausted-banner" style="display:none;align-items:flex-start;gap:8px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);color:#ef4444;font-size:12.5px;font-weight:600;padding:10px 14px;border-radius:8px;margin-bottom:14px;">
      <span class="material-symbols-outlined" style="font-size:18px;">error</span>
      <span>Vyčerpali ste všetky AI kredity. Ak chcete túto funkciu naďalej používať, dobite si kredit v Peňaženke — pri kúpe balíčka nižšie sa vám automaticky strhne.</span>
    </div>
    <button type="button" onclick="shareForAiCredit()" style="width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border:1px dashed var(--border-color);border-radius:8px;background:none;color:var(--text-primary);font-size:12.5px;font-weight:700;cursor:pointer;margin-bottom:16px;">
      <span class="material-symbols-outlined" style="font-size:16px;color:#1877f2;">share</span> Alebo zdieľajte na Facebooku a získajte kredit (+0,10 €)
    </button>
    <p style="font-size:13px;color:var(--text-secondary);margin:0 0 18px;">Kredity sa strhávajú z Peňaženky pri každom použití AI asistenta (e-maily, kontrola gramatiky, tvorba kampaní). Ak máte kredit získaný zdieľaním, minie sa vždy prednostne, až potom sa siahne na reálne dobitý kredit.</p>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <?php foreach (AI_CREDIT_PACKAGES as $pkg_count => $pkg_price): ?>
      <button type="button" onclick="buyAiCredits(<?= (int)$pkg_count ?>)" style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border:1px solid var(--border-color);border-radius:8px;background:var(--bg-color);cursor:pointer;text-align:left;">
        <span style="font-weight:700;font-size:14px;color:var(--text-primary);"><?= (int)$pkg_count ?> AI kreditov</span>
        <span style="font-weight:800;font-size:15px;color:var(--primary-color);"><?= number_format($pkg_price, 2, ',', ' ') ?> €</span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
@media (max-width:900px) {
  .marketing-grid-responsive { grid-template-columns:1fr !important; }
  .voucher-grid-responsive { grid-template-columns:1fr !important; }
}
.mkt-tab-content { }
.m-template-card { border:2px solid var(--border-color);border-radius:12px;padding:14px;background:var(--bg-color);cursor:pointer;transition:all 0.2s ease;display:flex;flex-direction:column; }
.m-template-card:hover { border-color:var(--primary-color);transform:translateY(-2px); }
.m-template-card.active { border-color:var(--primary-color);background:rgba(176,128,66,0.08); }
.recipient-row { display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--border-color);transition:background 0.2s; }
.recipient-row:last-child { border-bottom:0; }
.recipient-row:hover { background:var(--bg-color); }
.recipient-row.excluded { opacity:0.45; }
.voucher-card { background:var(--card-bg);border:1px solid var(--border-color);border-radius:14px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;transition:all 0.2s; }
.voucher-card:hover { border-color:var(--primary-color);box-shadow:0 4px 15px rgba(176,128,66,0.12); }
</style>

<script>
// Tab switching
async function loadNewsletterSubscriberCount() {
  try {
    const res = await fetch('api/newsletter.php?action=get_subscriber_count');
    const data = await res.json();
    const lockedBox = document.getElementById('newsletter-locked-box');
    const contentBox = document.getElementById('newsletter-content-box');
    if (data.success) {
      lockedBox.style.display = 'none';
      contentBox.style.display = 'block';
      document.getElementById('newsletter-sub-count').innerText = data.count;
    } else {
      lockedBox.style.display = 'block';
      contentBox.style.display = 'none';
    }
  } catch (err) { console.error(err); }
}

async function sendNewsletterUpdate() {
  const subject = document.getElementById('newsletter-subject').value.trim();
  const content = newsletterQuill ? newsletterQuill.root.innerHTML : '';
  const contentText = newsletterQuill ? newsletterQuill.getText().trim() : '';
  if (!subject || !contentText) { showAppToast('Vyplňte predmet aj obsah správy.', 'warning'); return; }
  if (!(await confirmModal('Odoslať túto novinku všetkým potvrdeným odberateľom?'))) return;
  const btn = document.getElementById('btn-send-newsletter');
  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('action', 'send_update');
    fd.append('subject', subject);
    fd.append('content', content);
    const res = await fetch('api/newsletter.php', { method: 'POST', body: fd });
    const data = await res.json();
    showAppToast(data.success ? (data.message || 'Odoslané!') : (data.error || 'Chyba'), data.success ? 'success' : 'error');
    if (data.success) { document.getElementById('newsletter-subject').value = ''; if (newsletterQuill) newsletterQuill.setText(''); }
  } catch (err) { showAppToast('Chyba pripojenia k serveru.', 'error'); }
  finally { btn.disabled = false; }
}

function switchMarketingTab(tab) {
  document.querySelectorAll('.mkt-tab-content').forEach(t => t.style.display='none');
  document.querySelectorAll('.mkt-tab-btn').forEach(b => { b.className='btn-secondary mkt-tab-btn'; });
  document.getElementById('mkt-tab-'+tab).style.display='block';
  document.getElementById('mkt-btn-'+tab).className='btn-primary mkt-tab-btn';
}

// ═══════ MARKETING STATE ═══════
let marketingState = {
  clients:[],celebrants:[],birthdayCelebrants:[],templates:[],hiddenTemplates:[],utmLeads:[],namedays:{},
  selectedTemplateId:'none',activeFilter:'all',excludedIds:new Set(),establishmentName:<?= json_encode(BRAND_NAME) ?>
};

async function loadMarketingData() {
  try {
    const res = await fetch('api/marketing.php?action=get_marketing_data');
    const data = await res.json();
    if (data.success) {
      marketingState.clients = data.clients || [];
      marketingState.celebrants = data.celebrants || [];
      marketingState.birthdayCelebrants = data.birthday_celebrants || [];
      marketingState.templates = data.templates || [];
      marketingState.hiddenTemplates = data.hidden_templates || [];
      marketingState.utmLeads = data.utm_leads || [];
      marketingState.namedays = data.namedays || {};
      marketingState.establishmentName = data.establishment_name || <?= json_encode(BRAND_NAME) ?>;

      const celebCount = marketingState.celebrants.length;
      const celebInfo = document.getElementById('nameday-celebrants-info');
      const celebPills = document.getElementById('nameday-celebrants-pills');
      if (celebCount > 0) {
        celebInfo.innerHTML = `<span style="color:#10b981;font-weight:800;display:inline-flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:18px;">celebration</span> Dnes oslavuje ${celebCount} ${celebCount===1?'váš klient':(celebCount<5?'vaši klienti':'vašich klientov')}:</span>`;
        let pillsHtml = '';
        marketingState.celebrants.forEach(c => { pillsHtml += `<span style="background:rgba(176,128,66,0.15);border:1px solid var(--primary-color);color:var(--text-primary);padding:5px 12px;border-radius:8px;font-size:13px;font-weight:700;">${c.name}</span>`; });
        if (celebPills) { celebPills.innerHTML = pillsHtml; celebPills.style.display='flex'; }
      } else {
        celebInfo.innerHTML = `<span style="color:var(--text-secondary);display:inline-flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">info</span> V kontaktoch nemáte zákazníka s týmto menom.</span>`;
        if (celebPills) { celebPills.innerHTML = ''; celebPills.style.display='none'; }
      }
      const bdayCount = marketingState.birthdayCelebrants.length;
      const bdayInfo = document.getElementById('birthday-celebrants-info');
      const bdayPills = document.getElementById('birthday-celebrants-pills');
      const bdayBtn = document.getElementById('btn-birthday-campaign');
      if (bdayCount > 0) {
        bdayInfo.innerHTML = `<span style="color:#ec4899;font-weight:800;display:inline-flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:18px;">cake</span> Dnes oslavuje narodeniny ${bdayCount} ${bdayCount===1?'váš klient':(bdayCount<5?'vaši klienti':'vašich klientov')}:</span>`;
        let bdayPillsHtml = '';
        marketingState.birthdayCelebrants.forEach(c => { bdayPillsHtml += `<span style="background:rgba(236,72,153,0.15);border:1px solid #ec4899;color:var(--text-primary);padding:5px 12px;border-radius:8px;font-size:13px;font-weight:700;">${c.name}</span>`; });
        if (bdayPills) { bdayPills.innerHTML = bdayPillsHtml; bdayPills.style.display='flex'; }
        if (bdayBtn) bdayBtn.disabled = false;
      } else {
        bdayInfo.innerHTML = `<span style="color:var(--text-secondary);display:inline-flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:16px;">info</span> Žiadny zákazník dnes neoslavuje narodeniny (alebo si dátum narodenia ešte nezadal).</span>`;
        if (bdayPills) { bdayPills.innerHTML = ''; bdayPills.style.display='none'; }
        if (bdayBtn) bdayBtn.disabled = true;
      }
      document.querySelector('.m-count-all').innerText = marketingState.clients.length;
      document.querySelector('.m-count-nameday').innerText = celebCount;
      document.querySelector('.m-count-female').innerText = marketingState.clients.filter(c=>c.gender==='female').length;
      document.querySelector('.m-count-male').innerText = marketingState.clients.filter(c=>c.gender==='male').length;
      document.querySelector('.m-count-other-gender').innerText = marketingState.clients.filter(c=>c.gender==='other').length;
      document.querySelector('.m-count-visits_1').innerText = marketingState.clients.filter(c=>(c.total_bookings||0)===1).length;
      document.querySelector('.m-count-visits_2_4').innerText = marketingState.clients.filter(c=>(c.total_bookings||0)>=2 && (c.total_bookings||0)<=4).length;
      document.querySelector('.m-count-visits_5plus').innerText = marketingState.clients.filter(c=>(c.total_bookings||0)>=5).length;
      document.querySelector('.m-count-spend_low').innerText = marketingState.clients.filter(c=>(parseFloat(c.total_spent)||0)<30).length;
      document.querySelector('.m-count-spend_mid').innerText = marketingState.clients.filter(c=>(parseFloat(c.total_spent)||0)>=30 && (parseFloat(c.total_spent)||0)<100).length;
      document.querySelector('.m-count-spend_high').innerText = marketingState.clients.filter(c=>(parseFloat(c.total_spent)||0)>=100).length;
      renderUpcomingReminders(data.upcoming_reminders || []);
      renderRecipientsList(); renderUtmTable(); renderTemplatesModal();
      if (!document.getElementById('campaign-subject').value) {
        document.getElementById('campaign-subject').value = 'Špeciálna ponuka a novinky pre vás';
        document.getElementById('campaign-content').value = "Dobrý deň {{NAME}},\n\npripravili sme pre Vás novinky a exkluzívne voľné termíny.\n\nTešíme sa na Vašu ďalšiu návštevu!";
      }
    }
  } catch(err) { console.error('Chyba marketingu:', err); }
}

function renderUpcomingReminders(reminders) {
  const box = document.getElementById('upcoming-reminders-box');
  if (!reminders.length) { box.style.display = 'none'; box.innerHTML = ''; return; }
  box.style.display = 'flex';
  box.innerHTML = reminders.map(r => {
    const when = r.days_until === 0 ? 'Dnes' : (r.days_until === 1 ? 'Zajtra' : ('O ' + r.days_until + ' dní'));
    return `<div style="background:rgba(236,72,153,0.08);border:1px solid #ec4899;border-radius:14px;padding:16px 22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
      <div style="display:flex;align-items:center;gap:14px;">
        <span class="material-symbols-outlined" style="font-size:26px;color:#ec4899;">celebration</span>
        <div><strong style="font-size:14.5px;">${when} je ${r.label}</strong><div style="font-size:12px;color:var(--text-secondary);">${r.date.split('-').reverse().join('.')} — nastavené na pripomenutie vopred v Automatizácii nižšie</div></div>
      </div>
      <button type="button" onclick="switchMarketingTab('emails'); document.getElementById('automation-panel').style.display='block'; document.getElementById('automation-panel').scrollIntoView({behavior:'smooth'});" class="btn-secondary" style="padding:8px 16px;font-size:13px;">Zobraziť nastavenie</button>
    </div>`;
  }).join('');
}

let occasionSettingsCache = [];
function toggleAutomationPanel() {
  const panel = document.getElementById('automation-panel');
  const icon = document.getElementById('automation-toggle-icon');
  const opening = panel.style.display === 'none';
  panel.style.display = opening ? 'block' : 'none';
  icon.textContent = opening ? 'expand_less' : 'expand_more';
  if (opening && !occasionSettingsCache.length) loadOccasionSettings();
}

async function loadOccasionSettings() {
  const tbody = document.getElementById('automation-settings-tbody');
  try {
    const res = await fetch('api/marketing.php?action=get_occasion_settings');
    const data = await res.json();
    if (!data.success) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#ef4444;">Chyba načítania.</td></tr>'; return; }
    occasionSettingsCache = data.settings;
    tbody.innerHTML = occasionSettingsCache.map((s, i) => `
      <tr>
        <td><strong>${s.label}</strong></td>
        <td>
          <select class="form-control occ-mode" data-idx="${i}" style="min-width:150px;" onchange="onOccasionModeChange(${i}, this.value)">
            <option value="manual" ${s.mode==='manual'?'selected':''}>Manuálne</option>
            <option value="remind" ${s.mode==='remind'?'selected':''}>Pripomenúť vopred</option>
            <option value="auto" ${s.mode==='auto'?'selected':''}>Automaticky</option>
          </select>
        </td>
        <td><input type="number" class="form-control occ-lead" data-idx="${i}" min="1" max="30" value="${s.lead_days}" style="width:70px;" ${s.mode!=='remind'?'disabled':''}></td>
        <td>
          <select class="form-control occ-reward" data-idx="${i}" onchange="onOccasionRewardChange(${i}, this.value)">
            <option value="greeting" ${s.reward_type==='greeting'?'selected':''}>Len pozdrav</option>
            <option value="discount" ${s.reward_type==='discount'?'selected':''}>Pozdrav + zľava</option>
          </select>
        </td>
        <td style="white-space:nowrap;">
          <select class="form-control occ-discount-type" data-idx="${i}" style="width:70px;display:inline-block;" ${s.reward_type!=='discount'?'disabled':''}>
            <option value="percent" ${s.discount_type==='percent'?'selected':''}>%</option>
            <option value="fixed" ${s.discount_type==='fixed'?'selected':''}>€</option>
          </select>
          <input type="number" class="form-control occ-discount-value" data-idx="${i}" min="0" step="0.5" value="${s.discount_value}" style="width:70px;display:inline-block;" ${s.reward_type!=='discount'?'disabled':''}>
        </td>
        <td><input type="number" class="form-control occ-validity" data-idx="${i}" min="1" max="90" value="${s.discount_validity_days}" style="width:70px;" ${s.reward_type!=='discount'?'disabled':''}> dní</td>
        <td style="min-width:150px;">${renderOccasionTemplateSelects(s, i)}</td>
        <td><textarea class="form-control occ-message" data-idx="${i}" rows="1" placeholder="Predvolený text" style="min-width:180px;">${s.message_template || ''}</textarea></td>
      </tr>`).join('');
  } catch (err) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#ef4444;">Chyba pripojenia.</td></tr>';
  }
}

function occasionTemplateOptions(selectedId) {
  return (marketingState.templates || []).map(t => `<option value="${t.id}" ${t.id===selectedId?'selected':''}>${t.name}</option>`).join('');
}

function renderOccasionTemplateSelects(s, i) {
  if (s.occasion_key === 'nameday' || s.occasion_key === 'birthday') {
    return `<div style="display:flex;flex-direction:column;gap:4px;">
      <div style="display:flex;align-items:center;gap:4px;"><span style="font-size:12px;flex-shrink:0;">♀</span><select class="form-control occ-tpl-female" data-idx="${i}" title="Šablóna pre ženy" style="font-size:12px;">${occasionTemplateOptions(s.template_id_female)}</select></div>
      <div style="display:flex;align-items:center;gap:4px;"><span style="font-size:12px;flex-shrink:0;">♂</span><select class="form-control occ-tpl-male" data-idx="${i}" title="Šablóna pre mužov" style="font-size:12px;">${occasionTemplateOptions(s.template_id_male)}</select></div>
    </div>`;
  }
  return `<select class="form-control occ-tpl" data-idx="${i}">${occasionTemplateOptions(s.template_id)}</select>`;
}

function onOccasionModeChange(idx, mode) {
  const input = document.querySelector(`.occ-lead[data-idx="${idx}"]`);
  if (input) input.disabled = mode !== 'remind';
}

function onOccasionRewardChange(idx, rewardType) {
  document.querySelectorAll(`.occ-discount-type[data-idx="${idx}"], .occ-discount-value[data-idx="${idx}"], .occ-validity[data-idx="${idx}"]`).forEach(el => { el.disabled = rewardType !== 'discount'; });
}

async function saveOccasionSettings() {
  const msg = document.getElementById('automation-save-msg');
  const rows = occasionSettingsCache.map((s, i) => {
    const isGendered = s.occasion_key === 'nameday' || s.occasion_key === 'birthday';
    return {
      occasion_key: s.occasion_key,
      mode: document.querySelector(`.occ-mode[data-idx="${i}"]`).value,
      lead_days: document.querySelector(`.occ-lead[data-idx="${i}"]`).value,
      reward_type: document.querySelector(`.occ-reward[data-idx="${i}"]`).value,
      discount_type: document.querySelector(`.occ-discount-type[data-idx="${i}"]`).value,
      discount_value: document.querySelector(`.occ-discount-value[data-idx="${i}"]`).value,
      discount_validity_days: document.querySelector(`.occ-validity[data-idx="${i}"]`).value,
      message_template: document.querySelector(`.occ-message[data-idx="${i}"]`).value.trim(),
      template_id: isGendered ? 'none' : (document.querySelector(`.occ-tpl[data-idx="${i}"]`)?.value || 'none'),
      template_id_female: isGendered ? (document.querySelector(`.occ-tpl-female[data-idx="${i}"]`)?.value || 'none') : 'none',
      template_id_male: isGendered ? (document.querySelector(`.occ-tpl-male[data-idx="${i}"]`)?.value || 'none') : 'none',
    };
  });
  const fd = new FormData();
  fd.append('action', 'save_occasion_settings');
  fd.append('settings', JSON.stringify(rows));
  try {
    const res = await fetch('api/marketing.php', { method: 'POST', body: fd });
    const data = await res.json();
    msg.style.color = data.success ? '#10b981' : '#ef4444';
    msg.textContent = data.message || data.error || (data.success ? 'Uložené.' : 'Chyba.');
    if (data.success) { occasionSettingsCache = []; loadOccasionSettings(); loadMarketingData(); }
  } catch (err) {
    msg.style.color = '#ef4444';
    msg.textContent = 'Chyba pripojenia.';
  }
}

function getFilteredClients() {
  let list = marketingState.clients;
  if (marketingState.activeFilter==='nameday') list = marketingState.celebrants;
  else if (marketingState.activeFilter==='birthday') list = marketingState.birthdayCelebrants;
  else if (marketingState.activeFilter==='female') list = list.filter(c=>c.gender==='female');
  else if (marketingState.activeFilter==='male') list = list.filter(c=>c.gender==='male');
  else if (marketingState.activeFilter==='other_gender') list = list.filter(c=>c.gender==='other');
  else if (marketingState.activeFilter==='visits_1') list = list.filter(c=>(c.total_bookings||0)===1);
  else if (marketingState.activeFilter==='visits_2_4') list = list.filter(c=>(c.total_bookings||0)>=2 && (c.total_bookings||0)<=4);
  else if (marketingState.activeFilter==='visits_5plus') list = list.filter(c=>(c.total_bookings||0)>=5);
  else if (marketingState.activeFilter==='spend_low') list = list.filter(c=>(parseFloat(c.total_spent)||0)<30);
  else if (marketingState.activeFilter==='spend_mid') list = list.filter(c=>(parseFloat(c.total_spent)||0)>=30 && (parseFloat(c.total_spent)||0)<100);
  else if (marketingState.activeFilter==='spend_high') list = list.filter(c=>(parseFloat(c.total_spent)||0)>=100);
  const q=(document.getElementById('recipients-search-input')?.value||'').toLowerCase().trim();
  if (q) list = list.filter(c=>c.name.toLowerCase().includes(q)||c.email.toLowerCase().includes(q));
  return list;
}

function renderRecipientsList() {
  const container = document.getElementById('recipients-list-tbody');
  const filtered = getFilteredClients();
  let activeCount = 0;
  if (!filtered.length) {
    container.innerHTML = `<div style="text-align:center;padding:25px;color:var(--text-secondary);font-size:13px;">Žiadni klienti nespĺňajú filter.</div>`;
  } else {
    let html = '';
    filtered.forEach(c => {
      const noConsent = !c.marketing_consent;
      if (noConsent) {
        html += `<div class="recipient-row excluded" id="rec-item-${c.id}" style="opacity:0.6;">
          <div><strong style="font-size:13.5px;">${c.name}</strong><br><span style="font-size:12px;color:var(--text-secondary);">${c.email}</span></div>
          <span style="font-size:11px;color:var(--text-secondary);border:1px solid var(--border-color);border-radius:8px;padding:3px 8px;display:inline-flex;align-items:center;gap:4px;">
            <span class="material-symbols-outlined" style="font-size:14px;">block</span> Bez súhlasu
          </span>
        </div>`;
        return;
      }
      const isExcluded = marketingState.excludedIds.has(c.id);
      if (!isExcluded) activeCount++;
      html += `<div class="recipient-row ${isExcluded?'excluded':''}" id="rec-item-${c.id}">
        <div><strong style="font-size:13.5px;">${c.name}</strong><br><span style="font-size:12px;color:var(--text-secondary);">${c.email}</span></div>
        <button type="button" onclick="toggleExcludeRecipient(${c.id})" style="background:transparent;border:none;cursor:pointer;color:${isExcluded?'#10b981':'#ef4444'};padding:4px;">
          <span class="material-symbols-outlined" style="font-size:18px;">${isExcluded?'add_circle':'delete'}</span>
        </button>
      </div>`;
    });
    container.innerHTML = html;
  }
  document.getElementById('recipients-active-count').innerText = activeCount;
  document.getElementById('btn-count-num').innerText = activeCount;
}

function toggleExcludeRecipient(id) {
  if (marketingState.excludedIds.has(id)) marketingState.excludedIds.delete(id);
  else marketingState.excludedIds.add(id);
  renderRecipientsList();
}
function filterRecipientsBySearch() { renderRecipientsList(); }
function setMarketingFilter(filterType, btn) {
  marketingState.activeFilter = filterType;
  document.querySelectorAll('#marketing-filter-bar button').forEach(b => b.className='btn-secondary');
  if (btn) btn.className='btn-primary';
  renderRecipientsList();
}
function prepareNamedayCampaign() {
  setMarketingFilter('nameday', document.querySelector('#marketing-filter-bar button:nth-child(2)'));
  document.getElementById('campaign-subject').value = 'Všetko najlepšie k dnešným meninám! ';
  document.getElementById('campaign-content').value = "Milá / Milý {{NAME}},\n\nk dnešným meninám Vám prajeme všetko najlepšie!\n\nAko pozornosť máme pre Vás zľavu 10% na najbližšiu návštevu.";
  showToast('Pripravené blahoželanie k meninám!');
}

async function prepareBirthdayCampaign() {
  if (!marketingState.birthdayCelebrants.length) return;
  const btn = document.getElementById('btn-birthday-campaign');
  btn.disabled = true;
  showToast('Vytváram narodeninový zľavový kód...');
  try {
    const code = 'NARODENINY-' + Math.random().toString(36).substring(2, 6).toUpperCase();
    const validUntil = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('code', code);
    fd.append('discount_type', 'percent');
    fd.append('discount_value', '15');
    fd.append('valid_until', validUntil);
    fd.append('max_uses', String(marketingState.birthdayCelebrants.length));
    fd.append('description', 'Automatická narodeninová zľava — ' + new Date().toLocaleDateString('sk-SK'));
    const res = await fetch('api/vouchers.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'Vytvorenie kódu zlyhalo.', 'error'); btn.disabled = false; return; }

    setMarketingFilter('birthday', null);
    document.getElementById('campaign-subject').value = 'Všetko najlepšie k narodeninám! 🎉';
    document.getElementById('campaign-content').value = "Milá / Milý {{NAME}},\n\nk narodeninám Vám želáme všetko najlepšie!\n\nAko darček máte od nás zľavu 15% na najbližšiu návštevu — stačí pri rezervácii zadať kód " + code + " (platný 14 dní).";
    if (typeof loadVouchers === 'function') loadVouchers();
    showToast('Narodeninový kód ' + code + ' vytvorený a vložený do správy!');
  } catch (err) {
    showToast('Chyba pri vytváraní kódu.', 'error');
  }
  btn.disabled = false;
}
function renderTemplatesModal() {
  const grid = document.getElementById('templates-grid-box');
  let html = '';
  marketingState.templates.forEach(t => {
    const isSel = marketingState.selectedTemplateId===t.id;
    const thumb = t.is_custom && t.preview_img
      ? `<img src="${t.preview_img}" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:10px;">`
      : `<div style="height:100px;background:rgba(176,128,66,0.08);border-radius:8px;margin-bottom:10px;display:flex;align-items:center;justify-content:center;color:var(--primary-color);"><span class="material-symbols-outlined" style="font-size:36px;">${t.icon}</span></div>`;
    const editBtn = t.id === 'none' ? '' : `<button type="button" onclick="event.stopPropagation(); openEditTemplateModal('${t.id}')" title="Upraviť" style="position:absolute;top:6px;right:32px;background:rgba(0,0,0,0.55);border:none;border-radius:6px;width:24px;height:24px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><span class="material-symbols-outlined" style="font-size:14px;color:#fff;">edit</span></button>`;
    const deleteBtn = t.id === 'none' ? '' : `<button type="button" onclick="event.stopPropagation(); deleteCustomTemplate('${t.id}')" title="${t.is_custom?'Zmazať':'Skryť'}" style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,0.55);border:none;border-radius:6px;width:24px;height:24px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><span class="material-symbols-outlined" style="font-size:14px;color:#fff;">${t.is_custom?'delete':'visibility_off'}</span></button>`;
    html += `<div class="m-template-card ${isSel?'active':''}" style="position:relative;" onclick="selectTemplate('${t.id}')">
      ${editBtn}
      ${deleteBtn}
      ${thumb}
      <strong style="font-size:14px;color:var(--text-primary);margin-bottom:4px;">${t.name}</strong>
      <p style="font-size:12px;color:var(--text-secondary);margin:0;line-height:1.4;flex:1;">${t.desc}</p>
    </div>`;
  });
  html += `<div class="m-template-card" style="align-items:center;justify-content:center;border-style:dashed;cursor:pointer;" onclick="openCreateTemplateModal()">
    <span class="material-symbols-outlined" style="font-size:32px;color:var(--primary-color);margin-bottom:8px;">add_circle</span>
    <strong style="font-size:13.5px;color:var(--primary-color);">Vytvoriť šablónu</strong>
  </div>`;
  grid.innerHTML = html;

  const hiddenBox = document.getElementById('hidden-templates-box');
  const hidden = marketingState.hiddenTemplates || [];
  if (hidden.length) {
    hiddenBox.style.display = 'block';
    hiddenBox.innerHTML = `<div style="font-size:12px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:0.4px;margin-bottom:8px;">Skryté predvolené šablóny</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        ${hidden.map(t => `<span style="display:inline-flex;align-items:center;gap:6px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:20px;padding:5px 6px 5px 12px;font-size:12.5px;">
          ${t.name}
          <button type="button" onclick="unhideTemplate('${t.id}')" title="Obnoviť" style="background:var(--primary-color);border:none;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
            <span class="material-symbols-outlined" style="font-size:13px;color:#fff;">restore</span>
          </button>
        </span>`).join('')}
      </div>`;
  } else {
    hiddenBox.style.display = 'none';
    hiddenBox.innerHTML = '';
  }
}

let editingTemplateCustomId = null; // null = vytváranie novej, inak číslo = úprava vlastnej existujúcej

function openCreateTemplateModal() {
  editingTemplateCustomId = null;
  document.getElementById('tpl-modal-title').textContent = 'Vytvoriť vlastnú šablónu';
  document.getElementById('tpl-new-name').value = '';
  document.getElementById('tpl-new-image').value = '';
  document.getElementById('tpl-new-image').required = true;
  document.getElementById('tpl-image-hint').textContent = '';
  document.getElementById('createTemplateModal').style.display = 'flex';
}

function openEditTemplateModal(templateId) {
  const tpl = (marketingState.templates || []).find(t => t.id === templateId);
  if (!tpl) return;
  document.getElementById('tpl-new-name').value = tpl.is_custom ? tpl.name : (tpl.name + ' (moja verzia)');
  document.getElementById('tpl-new-image').value = '';
  if (tpl.is_custom) {
    editingTemplateCustomId = tpl.custom_id;
    document.getElementById('tpl-modal-title').textContent = 'Upraviť šablónu';
    document.getElementById('tpl-new-image').required = false;
    document.getElementById('tpl-image-hint').textContent = 'Nechajte prázdne, ak nechcete meniť obrázok.';
  } else {
    // Predvolená šablóna sa nedá priamo upraviť (je zdieľaná pre všetky prevádzky) — úprava
    // vytvorí vašu vlastnú kópiu s novým obrázkom, originál ostáva nedotknutý.
    editingTemplateCustomId = null;
    document.getElementById('tpl-modal-title').textContent = 'Vytvoriť vlastnú kópiu šablóny';
    document.getElementById('tpl-new-image').required = true;
    document.getElementById('tpl-image-hint').textContent = 'Toto je predvolená šablóna zdieľaná pre všetky prevádzky — úpravou vznikne vaša vlastná kópia s novým obrázkom.';
  }
  document.getElementById('createTemplateModal').style.display = 'flex';
}

function closeCreateTemplateModal() { document.getElementById('createTemplateModal').style.display = 'none'; }

async function createCustomTemplate() {
  const name = document.getElementById('tpl-new-name').value.trim();
  const fileInput = document.getElementById('tpl-new-image');
  if (!name) { showToast('Zadajte názov šablóny.', 'error'); return; }
  if (!editingTemplateCustomId && !fileInput.files.length) { showToast('Nahrajte obrázok.', 'error'); return; }

  const fd = new FormData();
  fd.append('action', editingTemplateCustomId ? 'update_template' : 'upload_template');
  if (editingTemplateCustomId) { fd.append('id', editingTemplateCustomId); }
  fd.append('name', name);
  if (fileInput.files.length) { fd.append('image', fileInput.files[0]); }
  try {
    const res = await fetch('api/marketing.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'Chyba pri ukladaní šablóny.', 'error'); return; }
    showToast(data.message || 'Šablóna bola uložená.', 'success');
    closeCreateTemplateModal();
    await loadMarketingData();
    renderTemplatesModal();
    if (typeof loadOccasionSettings === 'function' && occasionSettingsCache.length) { occasionSettingsCache = []; loadOccasionSettings(); }
  } catch (err) { showToast('Chyba pripojenia.', 'error'); }
}

async function deleteCustomTemplate(templateId) {
  const isCustom = String(templateId).indexOf('custom_') === 0;
  const msg = isCustom ? 'Naozaj zmazať túto šablónu?' : 'Táto predvolená šablóna sa skryje len pre vašu prevádzku (ostatné prevádzky ju budú mať naďalej). Pokračovať?';
  if (!(await confirmModal(msg))) return;
  const fd = new FormData(); fd.append('action', 'delete_template'); fd.append('id', templateId);
  try {
    const res = await fetch('api/marketing.php', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.message || (data.success ? 'Hotovo.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) { await loadMarketingData(); renderTemplatesModal(); }
  } catch (err) { showToast('Chyba pripojenia.', 'error'); }
}

async function restoreDefaultTemplates() {
  if (!(await confirmModal('Obnoviť všetky predvolené šablóny, ktoré ste si skryli?'))) return;
  const fd = new FormData(); fd.append('action', 'restore_default_templates');
  try {
    const res = await fetch('api/marketing.php', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.message || (data.success ? 'Obnovené.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) { await loadMarketingData(); renderTemplatesModal(); }
  } catch (err) { showToast('Chyba pripojenia.', 'error'); }
}

async function unhideTemplate(templateId) {
  const fd = new FormData(); fd.append('action', 'unhide_template'); fd.append('id', templateId);
  try {
    const res = await fetch('api/marketing.php', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.message || (data.success ? 'Obnovené.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) { await loadMarketingData(); renderTemplatesModal(); }
  } catch (err) { showToast('Chyba pripojenia.', 'error'); }
}
function openMarketingTemplateModal() { renderTemplatesModal(); document.getElementById('marketingTemplateModal').style.display='flex'; }
function selectTemplate(tplId) {
  marketingState.selectedTemplateId = tplId;
  const tpl = marketingState.templates.find(t=>t.id===tplId);
  if (tpl) {
    document.getElementById('tpl-display-title').innerText = tpl.name;
    document.getElementById('tpl-display-desc').innerText = tpl.desc;
    document.getElementById('tpl-display-icon').innerHTML = `<span class="material-symbols-outlined" style="font-size:26px;">${tpl.icon}</span>`;
  }
  document.getElementById('marketingTemplateModal').style.display='none';
}
async function runMarketingAI(mode) {
  const text = document.getElementById('campaign-content').value;
  if (!text.trim()) { showToast('Najskôr napíšte text správy.','warning'); return; }
  showToast('AI upravuje text...');
  try {
    const fd = new FormData(); fd.append('action','ai_assist'); fd.append('mode',mode); fd.append('text',text);
    const res = await fetch('api/marketing.php',{method:'POST',body:fd});
    const data = await res.json();
    if (data.success && data.result) {
      document.getElementById('campaign-content').value=data.result;
      showToast('Text upravený AI!');
      updateAiCreditsBadge(data.ai_credits_remaining);
    } else {
      showToast(data.error || 'AI úprava zlyhala.', 'error');
      if (data.need_ai_credit) openAiCreditsModal(true);
    }
  } catch(err) { showToast('Chyba pripojenia.', 'error'); }
}

function updateAiCreditsBadge(remaining) {
  if (remaining === null || remaining === undefined) return;
  const badge = document.getElementById('ai-credits-count');
  if (badge) badge.textContent = remaining;
  if (remaining <= 5) showToast('Zostáva vám už len ' + remaining + ' AI kreditov.', 'warning');
}
function openAiCreditsModal(exhausted) {
  document.getElementById('ai-credits-exhausted-banner').style.display = exhausted ? 'flex' : 'none';
  document.getElementById('ai-credits-modal').style.display = 'flex';
}
function shareForAiCredit() {
  const url = <?= json_encode('https://' . BRAND_SITE) ?>;
  const fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
  const win = window.open(fbUrl, 'fb-share', 'width=600,height=500');
  const timer = setInterval(() => {
    if (!win || win.closed) { clearInterval(timer); claimAiShareReward(); }
  }, 1000);
}
async function claimAiShareReward() {
  try {
    const fd = new FormData(); fd.append('action', 'claim_share_reward'); fd.append('platform', 'facebook'); fd.append('target', 'app');
    const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) showToast(data.message || 'Kredit bol pripísaný!');
    else showToast(data.error || 'Dnešná odmena už bola vyčerpaná.', data.already_claimed ? 'warning' : 'error');
  } catch (e) {}
}
function closeAiCreditsModal() { document.getElementById('ai-credits-modal').style.display = 'none'; }
async function buyAiCredits(count) {
  try {
    const fd = new FormData(); fd.append('action', 'buy_ai_credits'); fd.append('count', count);
    const res = await fetch('api/wallet.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      document.getElementById('ai-credits-count').textContent = data.new_ai_credits;
      closeAiCreditsModal();
      showToast(data.message || 'AI kredity boli pripísané.');
    } else {
      showToast(data.error || 'Nákup sa nepodaril.', 'error');
    }
  } catch (e) { showToast('Chyba pripojenia.', 'error'); }
}
function openMarketingPreviewModal() {
  const subject = document.getElementById('campaign-subject').value||'Bez predmetu';
  const content = document.getElementById('campaign-content').value||'Text správy...';
  const previewContent = content.replace(/{{NAME}}/g,'Jana Kováčová').replace(/{{SLUZBA}}/g,'Strihanie vlasov').replace(/{{DATUM}}/g,'12.08.2026');
  const html = `<div style="font-family: 'Outfit', sans-serif;padding:25px;background:white;color:#333;"><div style="border-bottom:1px solid #eee;padding-bottom:12px;margin-bottom:20px;"><div style="font-size:12px;color:#888;">PREDMET:</div><div style="font-size:17px;font-weight:bold;color:#111;">${subject}</div></div><div style="font-size:15px;line-height:1.7;white-space:pre-wrap;">${previewContent}</div><div style="margin-top:30px;padding-top:15px;border-top:1px solid #eee;font-size:13px;color:#888;">S úctou, <strong>${marketingState.establishmentName}</strong></div></div>`;
  document.getElementById('marketing-preview-iframe').srcdoc = html;
  document.getElementById('marketingPreviewModal').style.display='flex';
}
async function sendMarketingTestEmail() {
  const testEmail = document.getElementById('marketing-test-email').value.trim();
  if (!testEmail) { showToast('Zadajte testovaciu e-mailovú adresu.','warning'); return; }
  showToast('Odosielam testovací e-mail...');
  try {
    const fd = new FormData(); fd.append('action','send_test'); fd.append('test_email',testEmail); fd.append('subject',document.getElementById('campaign-subject').value); fd.append('content',document.getElementById('campaign-content').value); fd.append('template_id',marketingState.selectedTemplateId);
    const res = await fetch('api/marketing.php',{method:'POST',body:fd});
    const data = await res.json();
    showToast(data.success?(data.message||'Testovací e-mail odoslaný!'):(data.error||'Chyba'), data.success?'success':'error');
  } catch(err) { showToast('Chyba pripojenia.','error'); }
}
async function submitMarketingCampaign() {
  const filtered = getFilteredClients().filter(c=>!marketingState.excludedIds.has(c.id) && c.marketing_consent);
  if (!filtered.length) { showToast('Vyberte aspoň jedného príjemcu.','warning'); return; }
  const subject = document.getElementById('campaign-subject').value.trim();
  const content = document.getElementById('campaign-content').value.trim();
  if (!subject||!content) { showToast('Vyplňte predmet aj obsah správy.','warning'); return; }
  const btn = document.getElementById('btn-submit-campaign-main');
  btn.disabled=true; btn.innerHTML='<span class="material-symbols-outlined">sync</span> Odosielam...';
  try {
    const fd = new FormData(); fd.append('action','send_campaign'); fd.append('subject',subject); fd.append('content',content); fd.append('template_id',marketingState.selectedTemplateId); fd.append('recipients',JSON.stringify(filtered.map(c=>({id:c.id,name:c.name,email:c.email}))));
    const res = await fetch('api/marketing.php',{method:'POST',body:fd});
    const data = await res.json();
    showToast(data.success?(data.message||'Kampaň odoslaná!'):(data.error||'Chyba'), data.success?'success':'error');
  } catch(err) { showToast('Chyba odosielania.','error'); }
  finally { btn.disabled=false; btn.innerHTML='<span class="material-symbols-outlined">forward_to_inbox</span><span>Spustiť kampaň (<span id="btn-count-num">'+filtered.length+'</span>)</span>'; }
}
function renderUtmTable() {
  const tbody = document.getElementById('utm-leads-tbody');
  if (!marketingState.utmLeads||!marketingState.utmLeads.length) { tbody.innerHTML=`<tr><td colspan="5" style="text-align:center;padding:25px;color:var(--text-secondary);">Zatiaľ žiadne UTM záznamy.</td></tr>`; return; }
  let html='';
  marketingState.utmLeads.forEach(u => { html+=`<tr><td style="font-size:12.5px;color:var(--text-secondary);">${u.date}</td><td><strong>${u.name}</strong></td><td><span style="background:rgba(176,128,66,0.12);color:var(--primary-color);font-weight:700;font-size:11.5px;padding:3px 8px;border-radius:6px;">${u.source}</span> (${u.medium})</td><td><strong>${u.campaign}</strong></td><td style="font-size:12px;color:var(--text-secondary);">${u.page}</td></tr>`; });
  tbody.innerHTML=html;
}

// ═══════ VOUCHERS ═══════
async function loadVouchers() {
  const list = document.getElementById('vouchers-list');
  list.innerHTML = '<div style="text-align:center;padding:25px;color:var(--text-secondary);">Načítavam...</div>';
  try {
    const res = await fetch('api/vouchers.php?action=list');
    const data = await res.json();
    if (data.success) renderVouchers(data.vouchers||[]);
    else list.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba načítania voucherov.</div>';
  } catch(e) { list.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba pripojenia.</div>'; }
}

function renderVouchers(vouchers) {
  const list = document.getElementById('vouchers-list');
  if (!vouchers.length) { list.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:48px;margin-bottom:12px;display:block;">local_offer</span>Zatiaľ žiadne vouchery. Vytvorte prvý!</div>'; return; }
  list.innerHTML = vouchers.map(v => {
    const isActive = parseInt(v.is_active);
    const discountStr = v.discount_type==='percent' ? `${v.discount_value}%` : `${parseFloat(v.discount_value).toFixed(2)} €`;
    const validStr = v.valid_until ? `do ${v.valid_until}` : 'Bez expirácie';
    const usageStr = v.max_uses ? `${v.used_count}/${v.max_uses}x` : `${v.used_count}x použitý`;
    return `<div class="voucher-card" style="opacity:${isActive?1:0.6}">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
          <code style="font-size:16px;font-weight:800;color:var(--primary-color);background:rgba(176,128,66,0.1);padding:4px 10px;border-radius:8px;">${v.code}</code>
          <span style="font-size:13px;font-weight:700;color:${isActive?'#10b981':'#ef4444'};background:${isActive?'rgba(16,185,129,0.12)':'rgba(239,68,68,0.12)'};padding:2px 8px;border-radius:6px;">${isActive?'Aktívny':'Neaktívny'}</span>
        </div>
        <div style="font-size:13px;color:var(--text-secondary);display:flex;gap:16px;flex-wrap:wrap;">
          <span><strong style="color:var(--text-primary);">Zľava:</strong> ${discountStr}</span>
          <span><strong style="color:var(--text-primary);">Platnosť:</strong> ${validStr}</span>
          <span><strong style="color:var(--text-primary);">Použitie:</strong> ${usageStr}</span>
        </div>
        ${v.description?`<div style="font-size:12.5px;color:var(--text-secondary);margin-top:4px;">${v.description}</div>`:''}
      </div>
      <div style="display:flex;gap:8px;flex-shrink:0;">
        <button type="button" onclick="toggleVoucher(${v.id})" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;" title="${isActive?'Deaktivovať':'Aktivovať'}">
          <span class="material-symbols-outlined" style="font-size:16px;">${isActive?'toggle_on':'toggle_off'}</span>
        </button>
        <button type="button" onclick="deleteVoucher(${v.id})" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;color:#ef4444;" title="Zmazať">
          <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
        </button>
      </div>
    </div>`;
  }).join('');
}

async function createVoucher() {
  const code = document.getElementById('v-code').value.trim().toUpperCase();
  const dtype = document.getElementById('v-discount-type').value;
  const dval = document.getElementById('v-discount-value').value;
  const vu = document.getElementById('v-valid-until').value;
  const mu = document.getElementById('v-max-uses').value;
  const desc = document.getElementById('v-description').value;
  if (!code||!dval) { showToast('Vyplňte kód a hodnotu zľavy.','warning'); return; }
  try {
    const fd = new FormData();
    fd.append('action','create'); fd.append('code',code); fd.append('discount_type',dtype); fd.append('discount_value',dval);
    if (vu) fd.append('valid_until',vu); if (mu) fd.append('max_uses',mu); if (desc) fd.append('description',desc);
    const res = await fetch('api/vouchers.php',{method:'POST',body:fd});
    const data = await res.json();
    if (data.success) { showToast('Voucher bol vytvorený!'); loadVouchers(); document.getElementById('v-code').value=''; document.getElementById('v-discount-value').value=''; document.getElementById('v-valid-until').value=''; document.getElementById('v-max-uses').value=''; document.getElementById('v-description').value=''; }
    else showToast(data.error||'Chyba pri vytváraní vouchera.','error');
  } catch(e) { showToast('Chyba pripojenia.','error'); }
}

async function toggleVoucher(id) {
  const fd = new FormData(); fd.append('action','toggle'); fd.append('id',id);
  const res = await fetch('api/vouchers.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) loadVouchers(); else showToast(data.error||'Chyba.','error');
}

async function deleteVoucher(id) {
  if (!(await confirmModal('Skutočne zmazať tento voucher?'))) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('id',id);
  const res = await fetch('api/vouchers.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { showToast('Voucher bol zmazaný.'); loadVouchers(); } else showToast(data.error||'Chyba.','error');
}

// ═══════ DARČEKOVÉ POUKAZY ═══════
async function loadGiftVouchers() {
  const list = document.getElementById('gift-vouchers-list');
  list.innerHTML = '<div style="text-align:center;padding:25px;color:var(--text-secondary);">Načítavam...</div>';
  try {
    const [purchasesRes, overviewRes] = await Promise.all([
      fetch('api/gift_vouchers.php?action=list_purchases'),
      fetch('api/gift_vouchers.php?action=get_sales_overview')
    ]);
    const purchasesData = await purchasesRes.json();
    const overviewData = await overviewRes.json();
    if (purchasesData.success) renderGiftVouchers(purchasesData.purchases || []);
    else list.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba načítania poukazov.</div>';
    if (overviewData.success) {
      document.getElementById('gv-stat-pending').innerText = overviewData.pending;
      document.getElementById('gv-stat-active').innerText = overviewData.active;
      document.getElementById('gv-stat-outstanding').innerText = parseFloat(overviewData.outstanding_balance).toFixed(2).replace('.',',') + ' €';
      document.getElementById('gv-stat-revenue').innerText = parseFloat(overviewData.revenue_this_month).toFixed(2).replace('.',',') + ' €';
    }
  } catch(e) { list.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba pripojenia.</div>'; }
}

function renderGiftVouchers(purchases) {
  const list = document.getElementById('gift-vouchers-list');
  if (!purchases.length) { list.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:48px;margin-bottom:12px;display:block;">card_giftcard</span>Zatiaľ žiadne predané darčekové poukazy.</div>'; return; }
  const statusLabels = { pending_payment:['Čaká na platbu','#f59e0b'], active:['Aktívny','#10b981'], redeemed:['Vyčerpaný','#6b7280'], expired:['Expirovaný','#6b7280'], cancelled:['Zrušený','#ef4444'] };
  list.innerHTML = purchases.map(v => {
    const [label, color] = statusLabels[v.status] || [v.status, '#6b7280'];
    return `<div class="voucher-card">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
          <code style="font-size:15px;font-weight:800;color:var(--primary-color);background:rgba(176,128,66,0.1);padding:4px 10px;border-radius:8px;">${v.code}</code>
          <span style="font-size:13px;font-weight:700;color:${color};background:${color}1f;padding:2px 8px;border-radius:6px;">${label}</span>
        </div>
        <div style="font-size:13px;color:var(--text-secondary);display:flex;gap:16px;flex-wrap:wrap;">
          <span><strong style="color:var(--text-primary);">Hodnota:</strong> ${parseFloat(v.initial_value).toFixed(2)} € (zostáva ${parseFloat(v.remaining_value).toFixed(2)} €)</span>
          <span><strong style="color:var(--text-primary);">Kupujúci:</strong> ${v.buyer_name} (${v.buyer_email})</span>
          ${v.recipient_name ? `<span><strong style="color:var(--text-primary);">Pre:</strong> ${v.recipient_name}</span>` : ''}
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-shrink:0;">
        ${v.status === 'pending_payment' ? `<button type="button" onclick="confirmGiftVoucherPayment(${v.id})" class="btn-primary" style="padding:7px 14px;font-size:12.5px;border-radius:8px;"><span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> Potvrdiť platbu</button>` : ''}
      </div>
    </div>`;
  }).join('');
}

async function confirmGiftVoucherPayment(id) {
  if (!(await confirmModal('Potvrdiť, že platba za tento darčekový poukaz bola prijatá na váš účet?'))) return;
  const fd = new FormData(); fd.append('action','confirm_payment'); fd.append('id',id);
  const res = await fetch('api/gift_vouchers.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { showToast('Platba potvrdená, poukaz je aktívny.'); loadGiftVouchers(); } else showToast(data.message||'Chyba.','error');
}

function showToast(msg, type='success') {
  if (typeof showAppToast === 'function') { showAppToast(msg,type); return; }
  if (typeof showToastNotification === 'function') { showToastNotification(msg,type); return; }
  let c = document.getElementById('_toast_c');
  if (!c) { c=document.createElement('div'); c.id='_toast_c'; c.style.cssText='position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;'; document.body.appendChild(c); }
  const t = document.createElement('div');
  t.style.cssText=`background:${type==='error'?'#ef4444':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.25);font-size:13.5px;font-weight:600;opacity:0;transform:translateY(-15px);transition:all 0.3s;`;
  t.innerText=msg; c.appendChild(t);
  setTimeout(()=>{t.style.opacity='1';t.style.transform='translateY(0)';},10);
  setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(-15px)';setTimeout(()=>t.remove(),300);},3500);
}

document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.body.classList.contains('dark-mode');
  const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
  init();
});

function init() {
  loadMarketingData();
  loadVouchers();
}

// ═══════ PERMANENTKY A ČLENSTVÁ ═══════
async function loadOverview() {
    const fd = new FormData(); fd.append('action', 'get_sales_overview');
    const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        document.getElementById('ov-active').textContent = data.active;
        document.getElementById('ov-pending').textContent = data.pending;
        document.getElementById('ov-revenue').textContent = data.revenue_this_month.toFixed(2) + ' €';
    }
}

async function loadPackages() {
    const list = document.getElementById('packages-list');
    const fd = new FormData(); fd.append('action', 'list_packages');
    const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success || !data.packages.length) {
        list.innerHTML = '<p style="color:var(--text-secondary);">Zatiaľ nemáte vytvorený žiadny balíček.</p>';
        return;
    }
    list.innerHTML = data.packages.map(p => `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--bg-color);border-radius:12px;border:1px solid var(--border-color);margin-bottom:10px;gap:12px;flex-wrap:wrap;opacity:${p.is_active == 1 ? 1 : 0.55};">
            <div>
                <strong style="font-size:14px;">${escapeHtml(p.name)}</strong>
                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(176,128,66,0.12);color:var(--primary-color);margin-left:6px;">${p.type === 'membership' ? 'Členstvo' : 'Balíček'}</span>
                <div style="font-size:12.5px;color:var(--text-secondary);margin-top:4px;">${parseFloat(p.price).toFixed(2)} € · ${p.visit_count} návštev · platnosť ${p.validity_days} dní</div>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" onclick="togglePackage(${p.id})" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;">${p.is_active == 1 ? 'Deaktivovať' : 'Aktivovať'}</button>
                <button type="button" onclick="deletePackage(${p.id})" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;color:#ef4444;">Zmazať</button>
            </div>
        </div>`).join('');
}

async function createPackage() {
    const name = document.getElementById('pkg-name').value.trim();
    if (!name) { showToast('Zadajte názov balíčka.', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'create_package');
    fd.append('name', name);
    fd.append('type', document.getElementById('pkg-type').value);
    fd.append('price', document.getElementById('pkg-price').value);
    fd.append('visit_count', document.getElementById('pkg-visits').value);
    fd.append('validity_days', document.getElementById('pkg-validity').value);
    fd.append('description', document.getElementById('pkg-description').value);
    const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.message || (data.success ? 'Vytvorené.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) {
        document.getElementById('package-modal').style.display = 'none';
        document.getElementById('pkg-name').value = '';
        loadPackages();
    }
}

async function togglePackage(id) {
    const fd = new FormData(); fd.append('action', 'toggle_package'); fd.append('id', id);
    await fetch('api/memberships.php', { method: 'POST', body: fd });
    loadPackages();
}

async function deletePackage(id) {
    if (!(await confirmModal('Naozaj zmazať tento balíček?'))) return;
    const fd = new FormData(); fd.append('action', 'delete_package'); fd.append('id', id);
    await fetch('api/memberships.php', { method: 'POST', body: fd });
    loadPackages();
}

async function loadPurchases() {
    const list = document.getElementById('purchases-list');
    const fd = new FormData(); fd.append('action', 'list_purchases');
    const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success || !data.purchases.length) {
        list.innerHTML = '<p style="color:var(--text-secondary);">Zatiaľ žiadne predaje.</p>';
        return;
    }
    const statusLabel = { pending_payment: 'Čaká na platbu', active: 'Aktívna', expired: 'Vyčerpaná', cancelled: 'Zrušená' };
    const statusColor = { pending_payment: '#f59e0b', active: '#10b981', expired: '#9ca3af', cancelled: '#ef4444' };
    list.innerHTML = data.purchases.map(p => `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--bg-color);border-radius:12px;border:1px solid var(--border-color);margin-bottom:10px;gap:12px;flex-wrap:wrap;">
            <div>
                <strong style="font-size:14px;">${escapeHtml(p.customer_name)}</strong> — ${escapeHtml(p.package_name)}
                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;background:${statusColor[p.status]}22;color:${statusColor[p.status]};margin-left:6px;">${statusLabel[p.status]}</span>
                <div style="font-size:12.5px;color:var(--text-secondary);margin-top:4px;">${parseFloat(p.price).toFixed(2)} € · využité ${p.visits_used}/${p.visits_total}${p.valid_until ? ' · platí do ' + p.valid_until : ''}</div>
            </div>
            <div style="display:flex;gap:8px;">
                ${p.status === 'pending_payment' ? `<button type="button" onclick="confirmPayment(${p.id})" class="btn-primary" style="padding:6px 14px;font-size:12px;border-radius:8px;">Potvrdiť platbu</button>` : ''}
                ${p.status === 'active' ? `<button type="button" onclick="logVisit(${p.id})" class="btn-secondary" style="padding:6px 14px;font-size:12px;border-radius:8px;">+1 návšteva</button>` : ''}
            </div>
        </div>`).join('');
}

async function confirmPayment(id) {
    const fd = new FormData(); fd.append('action', 'confirm_payment'); fd.append('id', id);
    const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.message || (data.success ? 'Potvrdené.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) { loadPurchases(); loadOverview(); }
}

async function logVisit(id) {
    const fd = new FormData(); fd.append('action', 'log_visit'); fd.append('id', id);
    const res = await fetch('api/memberships.php', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.message || (data.success ? 'Zapísané.' : 'Chyba.'), data.success ? 'success' : 'error');
    if (data.success) loadPurchases();
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

// ═══════ VERNOSTNÝ PROGRAM ═══════
function toggleThresholdLabel() {
    const type = document.getElementById('lp-reward-type').value;
    document.getElementById('lp-threshold-label').textContent = type === 'spend' ? 'Suma na odmenu (€)' : 'Počet návštev na odmenu';
}

async function loadLoyaltyProgram() {
    try {
        const fd = new FormData(); fd.append('action', 'get_program');
        const res = await fetch('api/loyalty.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success && data.program) {
            document.getElementById('lp-is-active').checked = !!parseInt(data.program.is_active);
            document.getElementById('lp-reward-type').value = data.program.reward_type;
            document.getElementById('lp-reward-threshold').value = data.program.reward_threshold;
            document.getElementById('lp-reward-description').value = data.program.reward_description || '';
            toggleThresholdLabel();
        }
    } catch (err) { console.error(err); }
}

async function saveLoyaltyProgram() {
    const msg = document.getElementById('lp-save-msg');
    const fd = new FormData();
    fd.append('action', 'save_program');
    fd.append('is_active', document.getElementById('lp-is-active').checked ? '1' : '0');
    fd.append('reward_type', document.getElementById('lp-reward-type').value);
    fd.append('reward_threshold', document.getElementById('lp-reward-threshold').value);
    fd.append('reward_description', document.getElementById('lp-reward-description').value.trim());
    try {
        const res = await fetch('api/loyalty.php', { method: 'POST', body: fd });
        const data = await res.json();
        msg.style.color = data.success ? '#10b981' : '#ef4444';
        msg.textContent = data.message || (data.success ? 'Uložené.' : 'Chyba.');
        if (data.success) loadLoyaltyMembers();
    } catch (err) {
        msg.style.color = '#ef4444';
        msg.textContent = 'Chyba pripojenia.';
    }
}

async function loadLoyaltyMembers() {
    const container = document.getElementById('loyalty-members-list');
    try {
        const fd = new FormData(); fd.append('action', 'list_members');
        const res = await fetch('api/loyalty.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) { container.innerHTML = '<p style="color:#ef4444;">Chyba načítania.</p>'; return; }
        const program = data.program;
        if (!program) { container.innerHTML = '<p style="color:var(--text-secondary);">Najprv nastavte a uložte program vyššie.</p>'; return; }
        if (!data.members.length) { container.innerHTML = '<p style="color:var(--text-secondary);">Zatiaľ žiadni zákazníci v programe.</p>'; return; }

        const threshold = parseFloat(program.reward_threshold);
        const isSpend = program.reward_type === 'spend';
        container.innerHTML = data.members.map(m => {
            const current = isSpend ? parseFloat(m.spend_total) : parseInt(m.visits_count);
            const progressInCycle = current % threshold;
            const pct = Math.min(100, Math.round((progressInCycle / threshold) * 100));
            const unit = isSpend ? '€' : 'návštev';
            return `<div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px;border:1px solid var(--border-color);border-radius:12px;margin-bottom:10px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <strong>${escapeHtml(m.customer_name)}</strong>
                    <div style="font-size:12px;color:var(--text-secondary);">${escapeHtml(m.customer_email)}</div>
                    <div style="background:var(--bg-color);border-radius:8px;height:8px;margin-top:8px;overflow:hidden;max-width:220px;">
                        <div style="background:var(--primary-color);height:100%;width:${pct}%;"></div>
                    </div>
                    <div style="font-size:11.5px;color:var(--text-secondary);margin-top:4px;">${progressInCycle.toFixed(isSpend?2:0)} / ${threshold} ${unit} do ďalšej odmeny</div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    ${m.rewards_available > 0 ? `<span style="background:rgba(16,185,129,0.12);color:#10b981;font-weight:700;font-size:12.5px;padding:4px 10px;border-radius:8px;">${m.rewards_available}× odmena k dispozícii</span>
                    <button type="button" onclick="redeemLoyaltyReward(${m.customer_id})" class="btn-primary" style="padding:7px 14px;font-size:12.5px;">Vyzdvihnúť odmenu</button>` : `<span style="font-size:12px;color:var(--text-secondary);">Zatiaľ žiadna odmena</span>`}
                </div>
            </div>`;
        }).join('');
    } catch (err) {
        container.innerHTML = '<p style="color:#ef4444;">Chyba pripojenia.</p>';
    }
}

async function redeemLoyaltyReward(customerId) {
    if (!(await confirmModal('Potvrdiť vyzdvihnutie odmeny týmto zákazníkom?'))) return;
    const fd = new FormData(); fd.append('action', 'redeem_reward'); fd.append('customer_id', customerId);
    const res = await fetch('api/loyalty.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { loadLoyaltyMembers(); } else { showToast(data.message || 'Chyba.', 'error'); }
}
</script>
</body>
</html>
