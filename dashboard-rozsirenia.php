<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('settings');

try { $conn->query("ALTER TABLE establishments ADD COLUMN IF NOT EXISTS calendar_feed_token VARCHAR(40) DEFAULT NULL"); } catch (Exception $e) {}

$est_stmt = $conn->prepare("SELECT id, calendar_feed_token FROM establishments WHERE user_id = ? LIMIT 1");
$est_stmt->bind_param("i", $_SESSION['user_id']);
$est_stmt->execute();
$est_row = $est_stmt->get_result()->fetch_assoc();
$widget_est_id = $est_row ? (int)$est_row['id'] : 0;
$widget_embed_url = 'https://' . BRAND_SITE . '/profil.php?id=' . $widget_est_id . '&embed=1';

$calendar_feed_token = $est_row['calendar_feed_token'] ?? null;
if ($widget_est_id > 0 && empty($calendar_feed_token)) {
    $calendar_feed_token = bin2hex(random_bytes(16));
    $upd_tok = $conn->prepare("UPDATE establishments SET calendar_feed_token = ? WHERE id = ?");
    $upd_tok->bind_param("si", $calendar_feed_token, $widget_est_id);
    $upd_tok->execute();
}
$calendar_feed_url = $calendar_feed_token ? ('https://' . BRAND_SITE . '/calendar_feed.php?token=' . $calendar_feed_token) : '';

$team_stmt = $conn->prepare("SELECT id, name FROM employees WHERE (business_id = ? OR establishment_id = ?) AND is_active = 1 ORDER BY is_owner DESC, id ASC");
$team_stmt->bind_param("ii", $_SESSION['user_id'], $widget_est_id);
$team_stmt->execute();
$widget_team = $team_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Rozšírenia - ' . BRAND_NAME;
$currentPage = 'rozsirenia';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Rozšírenia'; $headerIcon = 'extension'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">
      <div class="vueto-card">
        <div class="vueto-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 18px;">
          <div>
            <h2 class="section-header" style="margin: 0;">
              <span class="material-symbols-outlined" style="color: var(--primary-color);">extension</span>
              Rozšírenia a integrácie
            </h2>
            <p class="section-desc" style="margin-top: 5px;">Prepojte Rezervos s nástrojmi, ktoré už používate, a automatizujte si prevádzku. Postupne pridávame ďalšie integrácie — nižšie vidíte, čo je v príprave.</p>
          </div>
        </div>

        <div class="vueto-card-body" style="padding: 24px 20px;">

          <!-- WIDGET NA VLASTNÝ WEB (Fáza 4) -->
          <div style="background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 22px; margin-bottom: 22px;">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;flex-wrap:wrap;">
              <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(176,128,66,0.12); display: flex; align-items: center; justify-content: center; flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size: 24px; color: var(--primary-color);">code_blocks</span>
              </div>
              <div>
                <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 2px 0; color: var(--text-primary);">Rezervačný widget na váš web</h3>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin:0;">Vložte tento kód do vlastnej stránky (napr. na squarespace, wordpress, wix) a zákazníci si u vás budú môcť rezervovať priamo tam, bez odchodu na Rezervos.</p>
              </div>
              <span style="margin-left:auto;display: inline-flex; align-items: center; gap: 5px; background: rgba(16,185,129,0.1); color: #10b981; font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 7px; text-transform: uppercase; letter-spacing: 0.4px;">
                <span class="material-symbols-outlined" style="font-size: 13px;">check_circle</span> Dostupné
              </span>
            </div>
            <?php if ($widget_est_id > 0): ?>
              <div style="position:relative;">
                <textarea id="widget-embed-code" readonly rows="3" style="width:100%;box-sizing:border-box;padding:12px 90px 12px 14px;border-radius:10px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-family:monospace;font-size:12.5px;resize:vertical;"><iframe src="<?= htmlspecialchars($widget_embed_url) ?>" width="100%" height="900" style="border:none;max-width:480px;"></iframe></textarea>
                <button type="button" onclick="copyWidgetEmbedCode()" class="btn-primary" style="position:absolute;top:10px;right:10px;padding:7px 14px;font-size:12.5px;">
                  <span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">content_copy</span> Kopírovať
                </button>
              </div>
              <p style="font-size:11.5px;color:var(--text-secondary);margin:8px 0 0 0;">Šírku a výšku (<span style="font-family:monospace;">width</span>/<span style="font-family:monospace;">height</span>) si podľa svojej stránky môžete upraviť priamo v kóde.</p>
            <?php else: ?>
              <p style="font-size:13px;color:var(--text-secondary);margin:0;">Widget bude dostupný, hneď ako si dokončíte profil prevádzky.</p>
            <?php endif; ?>
          </div>

          <!-- KALENDÁROVÝ ODBER (Fáza 4) -->
          <div style="background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 22px; margin-bottom: 22px;">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;flex-wrap:wrap;">
              <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(16,185,129,0.12); display: flex; align-items: center; justify-content: center; flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size: 24px; color: #10b981;">sync_alt</span>
              </div>
              <div>
                <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 2px 0; color: var(--text-primary);">Odber rezervácií do kalendára</h3>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin:0;">Funguje v Google, Apple, Outlook, Thunderbird, Windows aj Samsung Kalendári — jeden odkaz sa vloží ako "prihlásenie na odber kalendára" a rezervácie sa tam odvtedy sami aktualizujú.</p>
              </div>
              <span style="margin-left:auto;display: inline-flex; align-items: center; gap: 5px; background: rgba(16,185,129,0.1); color: #10b981; font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 7px; text-transform: uppercase; letter-spacing: 0.4px;">
                <span class="material-symbols-outlined" style="font-size: 13px;">check_circle</span> Dostupné
              </span>
            </div>
            <?php if ($calendar_feed_url): ?>
              <div class="form-group" style="margin-bottom:12px;">
                <label>Zobraziť odkaz pre</label>
                <select id="cal-feed-employee-select" onchange="updateCalendarFeedUrl()">
                  <option value="0">Celú prevádzku (všetci zamestnanci)</option>
                  <?php foreach ($widget_team as $emp): ?>
                    <option value="<?= (int)$emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div style="position:relative;">
                <input type="text" id="calendar-feed-url" readonly value="<?= htmlspecialchars($calendar_feed_url) ?>" style="width:100%;box-sizing:border-box;padding:12px 90px 12px 14px;border-radius:10px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);font-family:monospace;font-size:12.5px;">
                <button type="button" onclick="copyCalendarFeedUrl()" class="btn-primary" style="position:absolute;top:6px;right:6px;padding:7px 14px;font-size:12.5px;">
                  <span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">content_copy</span> Kopírovať
                </button>
              </div>
              <p style="font-size:11.5px;color:var(--text-secondary);margin:8px 0 0 0;">Napr. v Google Kalendári: Nastavenia → Pridať kalendár → Z URL. V Apple Kalendári: Súbor → Nový odber kalendára.</p>
            <?php else: ?>
              <p style="font-size:13px;color:var(--text-secondary);margin:0;">Kalendárový odber bude dostupný, hneď ako si dokončíte profil prevádzky.</p>
            <?php endif; ?>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;">

            <div style="background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 22px;">
              <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                <span class="material-symbols-outlined" style="font-size: 24px; color: #6366f1;">api</span>
              </div>
              <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 6px 0; color: var(--text-primary);">API prístup</h3>
              <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.55; margin: 0 0 12px 0;">Vlastný API kľúč pre napojenie Rezervos na váš informačný systém, e-shop alebo interné nástroje — rezervácie, klienti a rozvrh dostupné programovo.</p>
              <span style="display: inline-flex; align-items: center; gap: 5px; background: rgba(99,102,241,0.1); color: #6366f1; font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 7px; text-transform: uppercase; letter-spacing: 0.4px;">
                <span class="material-symbols-outlined" style="font-size: 13px;">schedule</span> Pripravujeme
              </span>
            </div>

            <div style="background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 22px;">
              <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(245,158,11,0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                <span class="material-symbols-outlined" style="font-size: 24px; color: #f59e0b;">auto_awesome</span>
              </div>
              <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 6px 0; color: var(--text-primary);">Automatizácie a Webhooks</h3>
              <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.55; margin: 0 0 12px 0;">Nastavte si vlastné automatické akcie — napr. odoslanie webhooku pri novej rezervácii, prepojenie so Zapierom/Make alebo vlastným skriptom.</p>
              <span style="display: inline-flex; align-items: center; gap: 5px; background: rgba(245,158,11,0.1); color: #f59e0b; font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 7px; text-transform: uppercase; letter-spacing: 0.4px;">
                <span class="material-symbols-outlined" style="font-size: 13px;">schedule</span> Pripravujeme
              </span>
            </div>

            <div style="background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: 14px; padding: 22px;">
              <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(239,68,68,0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                <span class="material-symbols-outlined" style="font-size: 24px; color: #ef4444;">point_of_sale</span>
              </div>
              <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 6px 0; color: var(--text-primary);">Platobné brány</h3>
              <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.55; margin: 0 0 12px 0;">Priame napojenie na Stripe a PayPal popri už dostupnej platbe cez QR kód (Pay by square / SEPA) — pre online platby kartou priamo pri rezervácii.</p>
              <span style="display: inline-flex; align-items: center; gap: 5px; background: rgba(239,68,68,0.1); color: #ef4444; font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 7px; text-transform: uppercase; letter-spacing: 0.4px;">
                <span class="material-symbols-outlined" style="font-size: 13px;">schedule</span> Pripravujeme
              </span>
            </div>

          </div>

          <div style="margin-top: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: rgba(176,128,66,0.06); border: 1px solid rgba(176,128,66,0.25); border-radius: 14px; padding: 18px 22px;">
            <div style="display: flex; align-items: center; gap: 14px;">
              <span class="material-symbols-outlined" style="font-size: 26px; color: var(--primary-color);">lightbulb</span>
              <div>
                <strong style="font-size: 14px; color: var(--text-primary); display: block;">Chýba vám integrácia, ktorú potrebujete?</strong>
                <span style="font-size: 12.5px; color: var(--text-secondary);">Napíšte nám, čo by vám najviac pomohlo — pri plánovaní nových rozšírení sa riadime vašou spätnou väzbou.</span>
              </div>
            </div>
            <a href="dashboard-podpora.php" class="btn-primary" style="padding: 10px 20px; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;">
              <span class="material-symbols-outlined" style="font-size: 18px;">forum</span> Kontaktovať podporu
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.body.classList.contains('dark-mode');
  const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
});
function init() {}

const CAL_FEED_BASE_URL = <?= json_encode($calendar_feed_url) ?>;

function updateCalendarFeedUrl() {
    const sel = document.getElementById('cal-feed-employee-select');
    const input = document.getElementById('calendar-feed-url');
    if (!sel || !input || !CAL_FEED_BASE_URL) return;
    const empId = sel.value;
    input.value = CAL_FEED_BASE_URL + (empId && empId !== '0' ? '&employee=' + empId : '');
}

function copyCalendarFeedUrl() {
    const el = document.getElementById('calendar-feed-url');
    if (!el) return;
    el.select();
    navigator.clipboard.writeText(el.value).then(() => {
        showAppToast('Odkaz na kalendárový odber bol skopírovaný.', 'success');
    }).catch(() => {
        showAppToast('Kopírovanie zlyhalo, skopírujte odkaz ručne.', 'error');
    });
}

function copyWidgetEmbedCode() {
    const el = document.getElementById('widget-embed-code');
    if (!el) return;
    el.select();
    navigator.clipboard.writeText(el.value).then(() => {
        showAppToast('Kód widgetu bol skopírovaný do schránky.', 'success');
    }).catch(() => {
        showAppToast('Kopírovanie zlyhalo, skopírujte kód ručne.', 'error');
    });
}
</script>
</body>
</html>
