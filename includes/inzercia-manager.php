<?php
// includes/inzercia-manager.php
// Zdieľaný "Inzercia" self-service modul (pridávanie/správa vlastných inzerátov).
// Používané v: dashboard-inzercia.php (biznis), moj_profil.php (zákazník), admin.php (admin).
// Vyžaduje: prihláseného používateľa ($_SESSION['user_id']) — o to sa stará api/classifieds.php.
// Vlastný CSS namespace (.inz-*) a JS namespace (window.InzManager), aby sa nič nebilo
// s CSS/JS triedami hostiteľskej stránky.

if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php';

$inz_tabs = [
    ['id'=>'all',     'label'=>'Všetky inzeráty',    'icon'=>'apps'],
    ['id'=>'work',    'label'=>'Práca a spolupráca', 'icon'=>'work'],
    ['id'=>'rental',  'label'=>'Prenájom',           'icon'=>'storefront'],
    ['id'=>'sale',    'label'=>'Predaj a bazar',     'icon'=>'sell'],
    ['id'=>'courses', 'label'=>'Kurzy a školenia',   'icon'=>'school'],
    ['id'=>'other',   'label'=>'Ostatné',            'icon'=>'category'],
];

// Deklarované ako bežná (nepodmienená) top-level funkcia, aby ju PHP zaregistrovalo hneď pri
// kompilácii súboru — a bola tak dostupná už pri prvom volaní nižšie (v modáli "Práca").
// Obalenie do if(!function_exists()) by ju spravilo dostupnou až po vykonaní tohto miesta v súbore.
function inz_render_image_upload_zone($key) {
    return <<<HTML
<div>
  <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Fotografie <span style="font-weight:400;color:var(--text-secondary);">(max. 5, každá do 5 MB)</span></label>
  <div id="{$key}-upload-zone"
       style="border:2px dashed var(--border-color);border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:border-color 0.2s;"
       onclick="document.getElementById('{$key}-file-input').click()"
       ondragover="event.preventDefault();this.style.borderColor='var(--primary-color)'"
       ondragleave="this.style.borderColor=''"
       ondrop="InzManager.handleImageDrop(event,'{$key}')">
    <span class="material-symbols-outlined" style="font-size:32px;color:var(--text-secondary);display:block;margin-bottom:6px;">add_photo_alternate</span>
    <span style="font-size:12.5px;color:var(--text-secondary);">Kliknite alebo pretiahnite fotografie sem</span>
  </div>
  <input type="file" id="{$key}-file-input" multiple accept="image/*" style="display:none;"
         onchange="InzManager.handleImageSelect(this,'{$key}')">
  <div id="{$key}-preview-grid" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
  <input type="hidden" id="{$key}-main-image-index" value="0">
</div>
HTML;
}
?>

<!-- KARTY KATEGÓRIÍ -->
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;">
  <?php foreach ($inz_tabs as $i => $tab): ?>
  <button type="button" onclick="InzManager.switchTab('<?= $tab['id'] ?>')" id="cat-card-<?= $tab['id'] ?>"
          style="display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:12px;border:1px solid <?= $i===0?'var(--primary-color)':'var(--border-color)' ?>;background:<?= $i===0?'rgba(176,128,66,0.08)':'var(--card-bg)' ?>;color:<?= $i===0?'var(--primary-color)':'var(--text-secondary)' ?>;cursor:pointer;font-family:inherit;font-size:13.5px;font-weight:700;transition:all 0.15s;">
    <span class="material-symbols-outlined" style="font-size:18px;color:<?= $i===0?'var(--primary-color)':'var(--text-secondary)' ?>;"><?= $tab['icon'] ?></span>
    <span><?= $tab['label'] ?></span>
  </button>
  <?php endforeach; ?>
</div>

<!-- CENNÍK / STATUS PRUH -->
<div id="inz-pricing-bar" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:12px 18px;margin-bottom:16px;font-size:13px;">
  <div style="display:flex;align-items:center;gap:8px;color:var(--text-secondary);">
    <span class="material-symbols-outlined" style="font-size:18px;color:var(--primary-color);">sell</span>
    <span>Inzerát na <strong style="color:var(--text-primary);">1 týždeň</strong> — <strong style="color:var(--primary-color);">0,50 €</strong></span>
  </div>
  <div style="width:1px;height:18px;background:var(--border-color);flex-shrink:0;"></div>
  <div style="display:flex;align-items:center;gap:8px;color:var(--text-secondary);">
    <span class="material-symbols-outlined" style="font-size:18px;color:var(--primary-color);">event_repeat</span>
    <span>Inzerát na <strong style="color:var(--text-primary);">1 mesiac</strong> — <strong style="color:var(--primary-color);">1,50 €</strong></span>
  </div>
  <div style="width:1px;height:18px;background:var(--border-color);flex-shrink:0;"></div>
  <div id="inz-credits-status" style="display:flex;align-items:center;gap:6px;color:var(--text-secondary);">
    <span class="material-symbols-outlined" style="font-size:16px;">hourglass_empty</span>
    <span style="font-size:12px;">Načítavam…</span>
  </div>
</div>

<!-- OBSAH TABULIEK -->
<div class="inz-card" style="margin-bottom:20px;">
  <div style="padding:20px;">
    <?php foreach ($inz_tabs as $i => $tab): ?>
    <div id="inz-tab-<?= $tab['id'] ?>" class="inz-tab-content"<?= $i>0?' style="display:none;"':'' ?>>
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <h3 style="margin:0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--primary-color);font-size:22px;"><?= $tab['icon'] ?></span>
          <?= $tab['label'] ?>
        </h3>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <?php if ($tab['id']==='work'): ?>
          <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:3px;display:flex;gap:0;">
            <button type="button" class="inz-filter-btn" id="work-f-all" onclick="InzManager.filterWork('all',this)"
                    style="border-radius:8px;padding:6px 12px;font-size:12.5px;font-weight:600;border:none !important;background:var(--primary-color) !important;color:#fff !important;">Všetky</button>
            <button type="button" class="inz-filter-btn" id="work-f-seek" onclick="InzManager.filterWork('seek',this)"
                    style="border-radius:8px;padding:6px 12px;font-size:12.5px;font-weight:600;border:none !important;">Hľadám</button>
            <button type="button" class="inz-filter-btn" id="work-f-offer" onclick="InzManager.filterWork('offer',this)"
                    style="border-radius:8px;padding:6px 12px;font-size:12.5px;font-weight:600;border:none !important;">Ponúkam</button>
          </div>
          <?php endif; ?>
          <?php if ($tab['id'] !== 'all'): ?>
          <button type="button" class="inz-btn-primary" onclick="InzManager.openModal('modal-<?= $tab['id'] ?>')"
                  style="padding:9px 16px;font-size:13px;border-radius:12px;display:inline-flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;">add</span> Pridať inzerát
          </button>
          <?php endif; ?>
        </div>
      </div>
      <div id="<?= $tab['id'] ?>-listings"
           style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
        <div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-secondary);">
          <span class="material-symbols-outlined" style="font-size:36px;display:block;margin-bottom:8px;opacity:0.4;"><?= $tab['icon'] ?></span>
          Načítavam inzeráty...
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- MOJE INZERÁTY -->
<div class="inz-card">
  <div style="padding:20px;">
    <h3 style="margin:0 0 16px 0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
      <span class="material-symbols-outlined" style="color:var(--primary-color);">manage_accounts</span>
      Moje inzeráty
    </h3>
    <div id="my-listings" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
      <div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-secondary);">Načítavam...</div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     MODAL: PRÁCA A SPOLUPRÁCA
     ════════════════════════════════════════ -->
<div id="modal-work" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:580px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
      <h3 style="margin:0;font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--primary-color);">work</span> Nový inzerát – Práca
      </h3>
      <button type="button" onclick="InzManager.closeModal('modal-work')" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:24px;line-height:1;">&times;</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <!-- Typ — kartový selector -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <button type="button" id="work-type-offer" onclick="InzManager.setWorkType('offer')"
                style="border:2px solid var(--primary-color);border-radius:10px;padding:14px 10px;background:rgba(176,128,66,0.07);cursor:pointer;text-align:center;display:flex;flex-direction:column;align-items:center;gap:6px;transition:all 0.15s;font-family:inherit;">
          <span class="material-symbols-outlined" style="font-size:26px;color:var(--primary-color);">work</span>
          <span style="font-size:13px;font-weight:800;color:var(--primary-color);">Ponúkam prácu</span>
          <span style="font-size:11.5px;color:var(--text-secondary);line-height:1.3;">Hľadám zamestnanca</span>
        </button>
        <button type="button" id="work-type-seek" onclick="InzManager.setWorkType('seek')"
                style="border:2px solid var(--border-color);border-radius:10px;padding:14px 10px;background:transparent;cursor:pointer;text-align:center;display:flex;flex-direction:column;align-items:center;gap:6px;transition:all 0.15s;font-family:inherit;">
          <span class="material-symbols-outlined" style="font-size:26px;color:var(--text-secondary);">person_search</span>
          <span style="font-size:13px;font-weight:800;color:var(--text-secondary);">Hľadám prácu</span>
          <span style="font-size:11.5px;color:var(--text-secondary);line-height:1.3;">Chcem sa zamestnať</span>
        </button>
      </div>
      <input type="hidden" id="work-listing-type" value="offer">

      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Nadpis <span style="color:#ef4444;">*</span></label>
        <input type="text" id="work-title" placeholder="Napr. Hľadám skúsenú kaderníčku na TPP"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Špecializácia</label>
        <input type="text" id="work-specialization" placeholder="Napr. Kaderníctvo, Manikúra, Kozmetika..."
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Popis</label>
        <textarea id="work-description" rows="4" placeholder="Popíšte pracovnú ponuku alebo požiadavky..."
                  style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Lokalita</label>
          <input type="text" id="work-location" placeholder="Napr. Bratislava"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Telefón</label>
          <input type="text" id="work-phone" placeholder="+421..."
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Kontaktný e-mail</label>
        <input type="email" id="work-email" placeholder="info@salon.sk"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <?php echo inz_render_image_upload_zone('work'); ?>
      <div id="work-duration-wrap" style="display:flex;gap:10px;">
        <button type="button" id="work-dur-week" onclick="InzManager.setDuration('work','week')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--primary-color);background:var(--primary-color);color:#fff;font-size:13px;font-weight:700;cursor:pointer;">
          1 týždeň — 0,50 €
        </button>
        <button type="button" id="work-dur-month" onclick="InzManager.setDuration('work','month')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--border-color);background:transparent;color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;">
          1 mesiac — 1,50 €
        </button>
      </div>
      <input type="hidden" id="work-duration" value="week">
      <div id="work-cost-info"></div>
      <button type="button" id="work-submit-btn" onclick="InzManager.submitListing('work')" class="inz-btn-primary"
              style="padding:13px;font-size:14px;font-weight:700;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;">
        <span class="material-symbols-outlined">publish</span> Zverejniť inzerát
      </button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     MODAL: PRENÁJOM
     ════════════════════════════════════════ -->
<div id="modal-rental" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:580px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
      <h3 style="margin:0;font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--primary-color);">storefront</span> Nový inzerát – Prenájom
      </h3>
      <button type="button" onclick="InzManager.closeModal('modal-rental')" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:24px;line-height:1;">&times;</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Typ prenájmu</label>
        <select id="rental-subtype" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
          <option value="chair">Prenájom kresla</option>
          <option value="room">Prenájom miestnosti</option>
          <option value="space">Prenájom priestoru / salóna</option>
          <option value="other">Iný prenájom</option>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Nadpis <span style="color:#ef4444;">*</span></label>
        <input type="text" id="rental-title" placeholder="Napr. Voľné kreslo v centre Bratislavy"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Popis <span style="color:#ef4444;">*</span></label>
        <textarea id="rental-description" rows="4" placeholder="Napr. Plne vybavené kreslo, wifi, klimatizácia, parkovanie..."
                  style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Cena <span style="color:#ef4444;">*</span></label>
          <input type="number" id="rental-price" placeholder="0.00" min="0" step="0.01"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Jednotka</label>
          <select id="rental-price-unit" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
            <option value="deň">za deň</option>
            <option value="týždeň">za týždeň</option>
            <option value="mesiac" selected>za mesiac</option>
          </select>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Dostupné od</label>
          <input type="date" id="rental-available-from"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Lokalita</label>
          <input type="text" id="rental-location" placeholder="Napr. Nitra"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Kontaktný e-mail</label>
        <input type="email" id="rental-email" placeholder="info@salon.sk"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <?php echo inz_render_image_upload_zone('rental'); ?>
      <div id="rental-duration-wrap" style="display:flex;gap:10px;">
        <button type="button" id="rental-dur-week" onclick="InzManager.setDuration('rental','week')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--primary-color);background:var(--primary-color);color:#fff;font-size:13px;font-weight:700;cursor:pointer;">
          1 týždeň — 0,50 €
        </button>
        <button type="button" id="rental-dur-month" onclick="InzManager.setDuration('rental','month')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--border-color);background:transparent;color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;">
          1 mesiac — 1,50 €
        </button>
      </div>
      <input type="hidden" id="rental-duration" value="week">
      <div id="rental-cost-info"></div>
      <button type="button" id="rental-submit-btn" onclick="InzManager.submitListing('rental')" class="inz-btn-primary"
              style="padding:13px;font-size:14px;font-weight:700;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;">
        <span class="material-symbols-outlined">publish</span> Zverejniť inzerát
      </button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     MODAL: PREDAJ A BAZAR
     ════════════════════════════════════════ -->
<div id="modal-sale" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:580px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
      <h3 style="margin:0;font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--primary-color);">sell</span> Nový inzerát – Predaj
      </h3>
      <button type="button" onclick="InzManager.closeModal('modal-sale')" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:24px;line-height:1;">&times;</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Názov <span style="color:#ef4444;">*</span></label>
        <input type="text" id="sale-title" placeholder="Napr. Kadernícky umývadlový blok MALETTI"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Popis</label>
        <textarea id="sale-description" rows="3" placeholder="Stav, vek, rozmery, dôvod predaja..."
                  style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Cena (€)</label>
          <input type="number" id="sale-price" placeholder="0.00" min="0" step="0.01"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Stav</label>
          <select id="sale-condition" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
            <option value="nové">Nové</option>
            <option value="použité" selected>Použité</option>
            <option value="repasované">Repasované</option>
          </select>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Lokalita</label>
          <input type="text" id="sale-location" placeholder="Napr. Košice"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Kontaktný e-mail</label>
          <input type="email" id="sale-email" placeholder="info@salon.sk"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
      </div>
      <?php echo inz_render_image_upload_zone('sale'); ?>
      <div id="sale-duration-wrap" style="display:flex;gap:10px;">
        <button type="button" id="sale-dur-week" onclick="InzManager.setDuration('sale','week')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--primary-color);background:var(--primary-color);color:#fff;font-size:13px;font-weight:700;cursor:pointer;">
          1 týždeň — 0,50 €
        </button>
        <button type="button" id="sale-dur-month" onclick="InzManager.setDuration('sale','month')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--border-color);background:transparent;color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;">
          1 mesiac — 1,50 €
        </button>
      </div>
      <input type="hidden" id="sale-duration" value="week">
      <div id="sale-cost-info"></div>
      <button type="button" id="sale-submit-btn" onclick="InzManager.submitListing('sale')" class="inz-btn-primary"
              style="padding:13px;font-size:14px;font-weight:700;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;">
        <span class="material-symbols-outlined">publish</span> Zverejniť inzerát
      </button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     MODAL: KURZY A ŠKOLENIA
     ════════════════════════════════════════ -->
<div id="modal-courses" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:580px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
      <h3 style="margin:0;font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--primary-color);">school</span> Nový inzerát – Kurz / Školenie
      </h3>
      <button type="button" onclick="InzManager.closeModal('modal-courses')" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:24px;line-height:1;">&times;</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Názov kurzu <span style="color:#ef4444;">*</span></label>
        <input type="text" id="courses-title" placeholder="Napr. Školenie – Balayage pre pokročilých"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Popis</label>
        <textarea id="courses-description" rows="4" placeholder="Obsah kurzu, pre koho je určený, čo sa naučíte..."
                  style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Cena (€)</label>
          <input type="number" id="courses-price" placeholder="0.00" min="0" step="0.01"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Dátum kurzu</label>
          <input type="date" id="courses-date"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Lokalita</label>
          <input type="text" id="courses-location" placeholder="Napr. Bratislava alebo Online"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Kontaktný e-mail</label>
          <input type="email" id="courses-email" placeholder="info@salon.sk"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
      </div>
      <?php echo inz_render_image_upload_zone('courses'); ?>
      <div id="courses-duration-wrap" style="display:flex;gap:10px;">
        <button type="button" id="courses-dur-week" onclick="InzManager.setDuration('courses','week')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--primary-color);background:var(--primary-color);color:#fff;font-size:13px;font-weight:700;cursor:pointer;">
          1 týždeň — 0,50 €
        </button>
        <button type="button" id="courses-dur-month" onclick="InzManager.setDuration('courses','month')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--border-color);background:transparent;color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;">
          1 mesiac — 1,50 €
        </button>
      </div>
      <input type="hidden" id="courses-duration" value="week">
      <div id="courses-cost-info"></div>
      <button type="button" id="courses-submit-btn" onclick="InzManager.submitListing('courses')" class="inz-btn-primary"
              style="padding:13px;font-size:14px;font-weight:700;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;">
        <span class="material-symbols-outlined">publish</span> Zverejniť inzerát
      </button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     MODAL: OSTATNÉ
     ════════════════════════════════════════ -->
<div id="modal-other" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:580px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
      <h3 style="margin:0;font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--primary-color);">category</span> Nový inzerát – Ostatné
      </h3>
      <button type="button" onclick="InzManager.closeModal('modal-other')" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:24px;line-height:1;">&times;</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Nadpis <span style="color:#ef4444;">*</span></label>
        <input type="text" id="other-title" placeholder="O čom je váš inzerát?"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Popis</label>
        <textarea id="other-description" rows="4" placeholder="Popíšte vašu ponuku alebo dopyt..."
                  style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Cena (€)</label>
          <input type="number" id="other-price" placeholder="Nepovinné" min="0" step="0.01"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Lokalita</label>
          <input type="text" id="other-location" placeholder="Napr. Žilina"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
        </div>
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Kontaktný e-mail</label>
        <input type="email" id="other-email" placeholder="info@salon.sk"
               style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-primary);font-size:14px;box-sizing:border-box;">
      </div>
      <?php echo inz_render_image_upload_zone('other'); ?>
      <div id="other-duration-wrap" style="display:flex;gap:10px;">
        <button type="button" id="other-dur-week" onclick="InzManager.setDuration('other','week')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--primary-color);background:var(--primary-color);color:#fff;font-size:13px;font-weight:700;cursor:pointer;">
          1 týždeň — 0,50 €
        </button>
        <button type="button" id="other-dur-month" onclick="InzManager.setDuration('other','month')"
                style="flex:1;padding:10px;border-radius:8px;border:2px solid var(--border-color);background:transparent;color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;">
          1 mesiac — 1,50 €
        </button>
      </div>
      <input type="hidden" id="other-duration" value="week">
      <div id="other-cost-info"></div>
      <button type="button" id="other-submit-btn" onclick="InzManager.submitListing('other')" class="inz-btn-primary"
              style="padding:13px;font-size:14px;font-weight:700;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;">
        <span class="material-symbols-outlined">publish</span> Zverejniť inzerát
      </button>
    </div>
  </div>
</div>

<!-- MODAL: ZVIDITEĽNIŤ INZERÁT (ROCKET BOOSTER) -->
<div id="modal-boost" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:460px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <span class="material-symbols-outlined" style="font-size:26px;color:var(--primary-color);">rocket_launch</span>
        <h3 style="margin:0;font-size:17px;font-weight:800;">Zviditeľniť inzerát</h3>
      </div>
      <button type="button" onclick="InzManager.closeModal('modal-boost')" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:22px;line-height:1;">&times;</button>
    </div>
    <p style="margin:0 0 18px;color:var(--text-secondary);font-size:12.5px;">Poplatok za zvolenú službu bude strhnutý z vašej Peňaženky.</p>

    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-secondary);margin-bottom:8px;display:flex;align-items:center;gap:5px;"><span class="material-symbols-outlined" style="font-size:14px;">arrow_upward</span>1. Posun hore</div>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;">
      <button type="button" onclick="InzManager.confirmBoost('single_tap',0.25)" class="inz-boost-option">
        <div><div class="inz-boost-name">Jednorazové tapnutie</div><div class="inz-boost-desc">Posunie inzerát na 1. miesto v kategórii (ako nový)</div><div style="color:#10b981;font-size:10.5px;font-weight:700;margin-top:3px;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">savings</span>Možno platiť aj z nazbieraných kreditov (0,25 kredit)</div></div>
        <strong class="inz-boost-price">0,25 €</strong>
      </button>
      <button type="button" onclick="InzManager.confirmBoost('morning',0.90)" class="inz-boost-option">
        <div><div class="inz-boost-name">Ranné vtáča balíček (7 dní)</div><div class="inz-boost-desc">Automatické tapnutie každé ráno po dobu 7 dní</div><div style="color:#10b981;font-size:10.5px;font-weight:700;margin-top:3px;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">savings</span>Možno platiť aj z nazbieraných kreditov (0,90 kredit)</div></div>
        <strong class="inz-boost-price">0,90 €</strong>
      </button>
      <button type="button" onclick="InzManager.confirmBoost('week',1.20)" class="inz-boost-option inz-boost-option-popular">
        <div style="position:absolute;top:-10px;left:16px;background:var(--primary-color);color:#fff;font-size:10px;font-weight:800;padding:2px 10px;border-radius:6px;white-space:nowrap;">Najpopulárnejšie</div>
        <div><div class="inz-boost-name">7-dňové topovanie</div><div class="inz-boost-desc">Trvalo topovaný v kategórii po dobu 7 dní</div><div style="color:#10b981;font-size:10.5px;font-weight:700;margin-top:3px;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">savings</span>Možno platiť aj z nazbieraných kreditov (1,20 kredit)</div></div>
        <strong class="inz-boost-price">1,20 €</strong>
      </button>
    </div>

    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-secondary);margin-bottom:8px;display:flex;align-items:center;gap:5px;"><span class="material-symbols-outlined" style="font-size:14px;">auto_awesome</span>2. Vizuálne zvýraznenie</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <button type="button" onclick="InzManager.confirmBoost('vip_glow',0.60)" class="inz-boost-option">
        <div><div class="inz-boost-name">Neon Glow rámček (7 dní)</div><div class="inz-boost-desc">Zvýrazňujúci farebný rámček okolo vašej karty</div><div style="color:#10b981;font-size:10.5px;font-weight:700;margin-top:3px;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">savings</span>Možno platiť aj z nazbieraných kreditov (0,60 kredit)</div></div>
        <strong class="inz-boost-price">0,60 €</strong>
      </button>
      <button type="button" onclick="InzManager.confirmBoost('vip_badge',0.40)" class="inz-boost-option">
        <div><div class="inz-boost-name">Štítok "Rýchly predaj" (7 dní)</div><div class="inz-boost-desc">Výrazný štítok pre urýchlenie dopytu</div><div style="color:#10b981;font-size:10.5px;font-weight:700;margin-top:3px;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">savings</span>Možno platiť aj z nazbieraných kreditov (0,40 kredit)</div></div>
        <strong class="inz-boost-price">0,40 €</strong>
      </button>
    </div>

    <button type="button" onclick="InzManager.closeModal('modal-boost')" class="inz-btn-secondary"
            style="width:100%;margin-top:18px;padding:10px;border-radius:10px;font-size:14px;font-weight:600;">Zrušiť</button>
  </div>
</div>

<div id="modal-delete-confirm" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:3000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:420px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
      <span class="material-symbols-outlined" style="font-size:28px;color:#ef4444;">delete_forever</span>
      <h3 style="margin:0;font-size:18px;font-weight:800;">Zmazať inzerát?</h3>
    </div>
    <p style="margin:0 0 22px;color:var(--text-secondary);font-size:14px;">Inzerát bude deaktivovaný a zmizne zo zoznamu. Táto akcia je nevratná.</p>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button type="button" onclick="InzManager.closeModal('modal-delete-confirm')" class="inz-btn-secondary"
              style="padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;">Zrušiť</button>
      <button type="button" onclick="InzManager.confirmDeleteListing()" class="inz-btn-primary"
              style="padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;background:#ef4444 !important;border-color:#ef4444 !important;">
        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">delete</span> Zmazať
      </button>
    </div>
  </div>
</div>

<style>
.inz-tab-content { }
.inz-card {
  background: var(--card-bg);
  border-radius: 14px;
  margin-bottom: 25px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  overflow: hidden;
}
.inz-btn-primary {
  background: var(--primary-color) !important;
  color: #fff !important;
  border: 1px solid var(--primary-color) !important;
  font-weight: 600;
  cursor: pointer;
}
.inz-btn-primary:hover {
  background: var(--primary-hover) !important;
  border-color: var(--primary-hover) !important;
}
.inz-btn-secondary {
  background: var(--card-bg) !important;
  color: var(--text-primary) !important;
  border: 1px solid var(--border-color) !important;
  font-weight: 500;
  cursor: pointer;
}
.inz-btn-secondary:hover {
  background: var(--bg-color) !important;
  border-color: var(--primary-color) !important;
  color: var(--primary-color) !important;
}
.inz-listing-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.2s;
}
.inz-listing-card:hover {
  border-color: var(--primary-color);
  box-shadow: 0 4px 16px rgba(176,128,66,0.13);
  transform: translateY(-1px);
}
.inz-listing-card .card-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}
.inz-filter-btn {
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.inz-my-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.2s;
}
.inz-my-card:hover {
  border-color: var(--primary-color);
  box-shadow: 0 4px 16px rgba(176,128,66,0.13);
}
.inz-boost-option {
  border: 2px solid var(--border-color);
  border-radius: 12px;
  padding: 12px 14px;
  cursor: pointer;
  text-align: left;
  background: var(--card-bg);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  font-family: inherit;
  transition: border-color 0.15s;
  position: relative;
  width: 100%;
  box-sizing: border-box;
}
.inz-boost-option:hover { border-color: var(--primary-color); }
.inz-boost-option-popular { border-color: var(--primary-color); background: rgba(176,128,66,0.06); margin-top: 4px; }
.inz-boost-name { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
.inz-boost-desc { font-size: 11.5px; color: var(--text-secondary); margin-top: 2px; line-height: 1.3; }
.inz-boost-price { font-size: 15px; font-weight: 800; color: var(--primary-color); white-space: nowrap; flex-shrink: 0; }
</style>

<script>
(function () {
  'use strict';

  /* ── Globals (scoped to this module) ── */
  const g_walletUrl = (typeof INZ_WALLET_URL !== 'undefined') ? INZ_WALLET_URL : 'dashboard-penazanka.php';
  const g_tabTypes = ['all','work','rental','sale','courses','other'];
  let   g_workAll  = [];
  let   g_workFilter = 'all';
  const g_images   = {}; // { modalKey: [{file, url}, ...] }
  let   g_deleteId = null;
  let   g_boostId  = null;
  let   g_adStatus = null;

  /* ── Tab switching ── */
  function switchTab(tab) {
    document.querySelectorAll('.inz-tab-content').forEach(t => t.style.display = 'none');
    document.getElementById('inz-tab-' + tab).style.display = 'block';

    g_tabTypes.forEach(t => {
      const card = document.getElementById('cat-card-' + t);
      if (!card) return;
      const active = t === tab;
      card.style.borderColor = active ? 'var(--primary-color)' : 'var(--border-color)';
      card.style.background  = active ? 'rgba(176,128,66,0.08)' : 'var(--card-bg)';
      card.style.color       = active ? 'var(--primary-color)' : 'var(--text-secondary)';
      const icon = card.querySelector('.material-symbols-outlined');
      if (icon) icon.style.color = active ? 'var(--primary-color)' : 'var(--text-secondary)';
    });
  }

  /* ── Work type toggle (Hľadám prácu / Ponúkam prácu) ── */
  function setWorkType(type) {
    document.getElementById('work-listing-type').value = type;
    const btnSeek  = document.getElementById('work-type-seek');
    const btnOffer = document.getElementById('work-type-offer');

    const applyActive = (btn) => {
      btn.style.borderColor = 'var(--primary-color)';
      btn.style.background  = 'rgba(176,128,66,0.07)';
      const icon  = btn.querySelector('.material-symbols-outlined');
      const title = btn.querySelector('span:nth-child(2)');
      if (icon)  icon.style.color  = 'var(--primary-color)';
      if (title) title.style.color = 'var(--primary-color)';
    };
    const applyInactive = (btn) => {
      btn.style.borderColor = 'var(--border-color)';
      btn.style.background  = 'transparent';
      const icon  = btn.querySelector('.material-symbols-outlined');
      const title = btn.querySelector('span:nth-child(2)');
      if (icon)  icon.style.color  = 'var(--text-secondary)';
      if (title) title.style.color = 'var(--text-secondary)';
    };

    if (type === 'seek') { applyActive(btnSeek);  applyInactive(btnOffer); }
    else                 { applyActive(btnOffer); applyInactive(btnSeek);  }
  }

  /* ── Work filter (Všetky / Hľadám / Ponúkam) ── */
  function filterWork(f, btn) {
    g_workFilter = f;
    ['all','seek','offer'].forEach(k => {
      const b = document.getElementById('work-f-' + k);
      if (!b) return;
      const active = k === f;
      b.style.cssText = `border-radius:8px;padding:6px 12px;font-size:12.5px;font-weight:600;border:none !important;cursor:pointer;${active?'background:var(--primary-color) !important;color:#fff !important;':'background:transparent;color:var(--text-secondary);'}`;
    });
    renderWork();
  }

  /* ── Duration selector ── */
  function setDuration(key, dur) {
    document.getElementById(key + '-duration').value = dur;
    const btnWeek  = document.getElementById(key + '-dur-week');
    const btnMonth = document.getElementById(key + '-dur-month');
    const activeS  = 'flex:1;padding:10px;border-radius:8px;border:2px solid var(--primary-color);background:var(--primary-color);color:#fff;font-size:13px;font-weight:700;cursor:pointer;';
    const inactiveS= 'flex:1;padding:10px;border-radius:8px;border:2px solid var(--border-color);background:transparent;color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;';
    if (btnWeek)  btnWeek.style.cssText  = dur === 'week'  ? activeS : inactiveS;
    if (btnMonth) btnMonth.style.cssText = dur === 'month' ? activeS : inactiveS;
    if (g_adStatus) {
      const el  = document.getElementById(key + '-cost-info');
      const btn = document.getElementById(key + '-submit-btn');
      renderCostInfo(el, btn, g_adStatus, key);
    }
  }

  /* ── Ad cost info ── */
  async function loadAdStatus(modalKey) {
    const el = document.getElementById(modalKey + '-cost-info');
    const btn = document.getElementById(modalKey + '-submit-btn');
    if (!el) return;
    el.innerHTML = '<div style="font-size:12px;color:var(--text-secondary);">Načítavam info o inzeráte…</div>';
    try {
      const res  = await fetch('api/classifieds.php?action=get_ad_status');
      const data = await res.json();
      if (!data.success) { el.innerHTML = ''; return; }
      g_adStatus = data;
      renderCostInfo(el, btn, data, modalKey);
      renderPricingBar(data);
    } catch (e) { el.innerHTML = ''; }
  }

  function renderCostInfo(el, btn, d, key) {
    const { tier, monthly_limit, ad_credits, wallet, ad_price_week, ad_price_month,
            credits_week = 1, credits_month = 3 } = d;
    const dur          = key ? (document.getElementById(key + '-duration')?.value || 'week') : 'week';
    const creditsNeed  = dur === 'week' ? credits_week : credits_month;
    const adPrice      = dur === 'week' ? (ad_price_week || 0.50) : (ad_price_month || 1.50);
    const hasCreds     = monthly_limit > 0 && ad_credits >= creditsNeed;
    const hasAnyCreds  = monthly_limit > 0 && ad_credits > 0;
    const durWrap      = key ? document.getElementById(key + '-duration-wrap') : null;

    if (durWrap) {
      durWrap.style.display = 'flex';
      const wBtn = document.getElementById(key + '-dur-week');
      const mBtn = document.getElementById(key + '-dur-month');
      if (monthly_limit > 0) {
        if (wBtn) wBtn.innerHTML = '1 týždeň &nbsp;—&nbsp; 1 kredit';
        if (mBtn) mBtn.innerHTML = '1 mesiac &nbsp;—&nbsp; 3 kredity';
      } else {
        if (wBtn) wBtn.innerHTML = '1 týždeň &nbsp;—&nbsp; 0,50 €';
        if (mBtn) mBtn.innerHTML = '1 mesiac &nbsp;—&nbsp; 1,50 €';
      }
    }

    if (hasCreds) {
      const left = ad_credits - creditsNeed;
      el.innerHTML = `<div style="display:flex;align-items:center;gap:10px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:7px;padding:10px 14px;">
        <span class="material-symbols-outlined" style="color:#10b981;font-size:20px;">verified</span>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:700;color:#10b981;">Zadarmo — ${creditsNeed} ${creditsNeed > 1 ? 'kredity' : 'kredit'} (${tier.toUpperCase()})</div>
          <div style="font-size:12px;color:var(--text-secondary);">Zostatok po: <strong>${left}</strong> z ${monthly_limit} kreditov</div>
        </div>
      </div>`;
      if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer'; }
    } else if (hasAnyCreds && !hasCreds) {
      const canPay = wallet >= adPrice;
      el.innerHTML = `<div style="display:flex;align-items:center;gap:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:7px;padding:10px 14px;">
        <span class="material-symbols-outlined" style="color:#f59e0b;font-size:20px;">info</span>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:700;color:#f59e0b;">Nestačí ${ad_credits} ${ad_credits > 1 ? 'kredity' : 'kredit'} (potrebujete ${creditsNeed})</div>
          <div style="font-size:12px;color:var(--text-secondary);">${canPay ? 'Bude strhnuté <strong>' + adPrice.toFixed(2) + ' €</strong> z Peňaženky · Zostatok: ' + wallet.toFixed(2) + ' €' : 'Peňaženka: ' + wallet.toFixed(2) + ' € — <a href="' + g_walletUrl + '" style="color:var(--primary-color);">Nabiť</a>'}</div>
        </div>
      </div>`;
      if (btn) { btn.disabled = !canPay; btn.style.opacity = canPay ? '1' : '0.45'; btn.style.cursor = canPay ? 'pointer' : 'not-allowed'; }
    } else {
      const canPay = wallet >= adPrice;
      if (canPay) {
        el.innerHTML = `<div style="display:flex;align-items:center;gap:10px;background:rgba(176,128,66,0.08);border:1px solid rgba(176,128,66,0.25);border-radius:7px;padding:10px 14px;">
          <span class="material-symbols-outlined" style="color:var(--primary-color);font-size:20px;">account_balance_wallet</span>
          <div style="flex:1;">
            <div style="font-size:13px;font-weight:700;color:var(--primary-color);">Platba z Peňaženky</div>
            <div style="font-size:12px;color:var(--text-secondary);">Strhne sa <strong>${adPrice.toFixed(2)} €</strong> · Zostatok: ${wallet.toFixed(2)} €</div>
          </div>
        </div>`;
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer'; }
      } else {
        el.innerHTML = `<div style="display:flex;align-items:center;gap:10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:7px;padding:10px 14px;">
          <span class="material-symbols-outlined" style="color:#ef4444;font-size:20px;">error</span>
          <div style="flex:1;">
            <div style="font-size:13px;font-weight:700;color:#ef4444;">Nedostatočný zostatok</div>
            <div style="font-size:12px;color:var(--text-secondary);">Potrebujete <strong>${adPrice.toFixed(2)} €</strong> · Zostatok: ${wallet.toFixed(2)} € — <a href="${g_walletUrl}" style="color:var(--primary-color);">Nabiť peňaženku</a></div>
          </div>
        </div>`;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.45'; btn.style.cursor = 'not-allowed'; }
      }
    }
  }

  function renderPricingBar(d) {
    const el = document.getElementById('inz-credits-status');
    if (!el) return;
    const { tier, monthly_limit, ad_credits, wallet } = d;
    if (monthly_limit > 0) {
      const col  = ad_credits >= 3 ? '#10b981' : ad_credits >= 1 ? '#f59e0b' : '#ef4444';
      const ic   = ad_credits >= 1 ? 'toll' : 'warning';
      const wMax = monthly_limit;
      el.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;color:${col};">${ic}</span>
        <span style="font-size:12.5px;font-weight:700;color:${col};">${tier.toUpperCase()}: <strong>${ad_credits}</strong> z ${wMax} kreditov</span>
        <span style="font-size:11.5px;color:var(--text-secondary);">(${Math.floor(ad_credits)} týždenných alebo ${Math.floor(ad_credits/3)} mesačných)</span>`;
    } else {
      el.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;color:var(--primary-color);">account_balance_wallet</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">Peňaženka: <strong style="color:var(--text-primary);">${wallet.toFixed(2)} €</strong></span>`;
    }
  }

  /* ── Modal open / close ── */
  function openModal(id) {
    document.getElementById(id).style.display = 'flex';
    const key = id.replace('modal-', '');
    g_adStatus = null;
    const btn = document.getElementById(key + '-submit-btn');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.45'; btn.style.cursor = 'not-allowed'; }
    loadAdStatus(key);
  }
  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    const key = id.replace('modal-', '');
    g_images[key] = [];
    const grid = document.getElementById(key + '-preview-grid');
    if (grid) grid.innerHTML = '';
    const mainInput = document.getElementById(key + '-main-image-index');
    if (mainInput) mainInput.value = '0';
    const costEl = document.getElementById(key + '-cost-info');
    if (costEl) costEl.innerHTML = '';
    const btn = document.getElementById(key + '-submit-btn');
    if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer'; }
    const durInput = document.getElementById(key + '-duration');
    if (durInput) durInput.value = 'week';
    const durWrap = document.getElementById(key + '-duration-wrap');
    if (durWrap) durWrap.style.display = 'flex';
    setDuration(key, 'week');
    g_adStatus = null;
  }

  /* ══════════════════════════════════════════════
     IMAGE UPLOAD
     ══════════════════════════════════════════════ */
  function handleImageSelect(input, key) {
    addImages(key, input.files);
    input.value = '';
  }

  function handleImageDrop(event, key) {
    event.preventDefault();
    event.currentTarget.style.borderColor = '';
    addImages(key, event.dataTransfer.files);
  }

  function addImages(key, fileList) {
    if (!g_images[key]) g_images[key] = [];
    const arr = g_images[key];
    for (const f of fileList) {
      if (arr.length >= 5) break;
      if (!f.type.startsWith('image/')) continue;
      const idx = arr.length;
      arr.push({ file: f, url: null });
      const reader = new FileReader();
      reader.onload = e => {
        arr[idx].url = e.target.result;
        renderPreviews(key);
      };
      reader.readAsDataURL(f);
    }
  }

  function renderPreviews(key) {
    const grid = document.getElementById(key + '-preview-grid');
    if (!grid) return;
    const imgs    = g_images[key] || [];
    const mainIdx = parseInt(document.getElementById(key + '-main-image-index')?.value || '0');
    grid.innerHTML = imgs.map((img, i) => {
      if (!img.url) return '';
      const isMain = (i === mainIdx);
      return `<div onclick="InzManager.setMainImg('${key}',${i})"
                   style="position:relative;width:88px;height:88px;border-radius:10px;overflow:hidden;border:2px solid ${isMain?'var(--primary-color)':'var(--border-color)'};cursor:pointer;flex-shrink:0;">
        <img src="${img.url}" style="width:100%;height:100%;object-fit:cover;" alt="">
        ${isMain?`<div style="position:absolute;bottom:0;left:0;right:0;background:var(--primary-color);color:#fff;font-size:10px;font-weight:700;text-align:center;padding:2px 0;line-height:1.4;">Hlavná</div>`:''}
        <button onclick="event.stopPropagation();InzManager.removeImg('${key}',${i})"
                style="position:absolute;top:3px;right:3px;background:rgba(0,0,0,0.65);border:none;color:#fff;border-radius:6px;width:20px;height:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;line-height:1;padding:0;">&times;</button>
      </div>`;
    }).join('');
  }

  function setMainImg(key, idx) {
    const inp = document.getElementById(key + '-main-image-index');
    if (inp) inp.value = idx;
    renderPreviews(key);
  }

  function removeImg(key, idx) {
    if (!g_images[key]) return;
    g_images[key].splice(idx, 1);
    const inp = document.getElementById(key + '-main-image-index');
    if (inp) {
      const cur = parseInt(inp.value);
      if (cur >= g_images[key].length) inp.value = Math.max(0, g_images[key].length - 1);
      else if (idx < cur) inp.value = cur - 1;
    }
    renderPreviews(key);
  }

  /* ══════════════════════════════════════════════
     LOAD LISTINGS
     ══════════════════════════════════════════════ */
  async function loadListings(type) {
    const container = document.getElementById(type + '-listings');
    if (!container) return;
    container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-secondary);">Načítavam...</div>`;
    try {
      const res  = await fetch(`api/classifieds.php?action=get_listings&type=${type}`);
      const data = await res.json();
      if (data.success) {
        if (type === 'work') { g_workAll = data.data || []; renderWork(); }
        else renderCards(type, data.data || []);
      } else {
        container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:30px;color:#ef4444;">Chyba načítania.</div>`;
      }
    } catch (e) {
      container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:30px;color:#ef4444;">Chyba pripojenia.</div>`;
    }
  }

  /* ── Work (Práca) render ── */
  function renderWork() {
    const container = document.getElementById('work-listings');
    if (!container) return;
    let list = g_workAll;
    if (g_workFilter === 'seek')  list = list.filter(i => i.listing_type === 'seek');
    if (g_workFilter === 'offer') list = list.filter(i => i.listing_type === 'offer');
    if (!list.length) {
      container.innerHTML = emptyState('work', 'Zatiaľ žiadne pracovné inzeráty.');
      return;
    }
    container.innerHTML = list.map(i => {
      const isSeek    = i.listing_type === 'seek';
      const badgeC    = isSeek ? '#3b82f6' : '#10b981';
      const badgeT    = isSeek ? 'Hľadám' : 'Ponúkam';
      const imgHtml   = firstImage(i);
      const isBoosted = i.boosted_until && new Date(i.boosted_until) > new Date();
      return `<div class="inz-listing-card" style="${isBoosted?'border-color:var(--primary-color);box-shadow:0 4px 16px rgba(176,128,66,0.18);':''}">
        ${isBoosted?`<div style="background:linear-gradient(90deg,var(--primary-color),#d4af37);color:#fff;font-size:11px;font-weight:800;padding:4px 12px;display:flex;align-items:center;gap:5px;"><span class="material-symbols-outlined" style="font-size:14px;">rocket_launch</span>TOP</div>`:''}
        ${imgHtml}
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
            <h4 style="margin:0;font-size:14.5px;font-weight:800;color:var(--text-primary);line-height:1.3;">${escHtml(i.title)}</h4>
            <span style="background:${badgeC};color:#fff;font-size:10.5px;font-weight:800;padding:3px 9px;border-radius:6px;flex-shrink:0;">${badgeT}</span>
          </div>
          ${i.specialization?`<div style="font-size:12px;color:var(--primary-color);font-weight:600;">${escHtml(i.specialization)}</div>`:''}
          ${i.description?`<p style="margin:0;font-size:12.5px;color:var(--text-secondary);line-height:1.4;">${escHtml(i.description.substring(0,110))}${i.description.length>110?'…':''}</p>`:''}
          <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:12px;color:var(--text-secondary);margin-top:auto;">
            ${i.location?`<span><span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">location_on</span> ${escHtml(i.location)}</span>`:''}
            <span><span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">calendar_today</span> ${formatDate(i.created_at)}</span>
          </div>
          ${i.contact_email||i.phone?`<div style="font-size:12px;color:var(--primary-color);display:flex;gap:10px;flex-wrap:wrap;">
            ${i.contact_email||i.email?`<span>${escHtml(i.contact_email||i.email)}</span>`:''}
            ${i.phone?`<span>${escHtml(i.phone)}</span>`:''}
          </div>`:''}
        </div>
      </div>`;
    }).join('');
  }

  /* ── Generic cards render ── */
  function renderCards(type, items) {
    const container = document.getElementById(type + '-listings');
    const typeIcons  = { all:'apps', rental:'storefront', sale:'sell', courses:'school', other:'category' };
    if (!items.length) {
      container.innerHTML = emptyState(typeIcons[type]||'article', 'Zatiaľ žiadne inzeráty v tejto kategórii.');
      return;
    }
    container.innerHTML = items.map(i => {
      const imgHtml   = firstImage(i);
      const isBoosted = i.boosted_until && new Date(i.boosted_until) > new Date();
      let extra = '';
      if (type === 'rental') {
        extra = `
          ${i.price?`<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} € ${i.price_unit?'/ '+escHtml(i.price_unit):''}</strong>`:''}
          ${i.available_from?`<span style="font-size:12px;color:var(--text-secondary);">Dostupné od: ${i.available_from}</span>`:''}`;
      } else if (type === 'sale') {
        extra = `<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          ${i.price?`<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} €</strong>`:''}
          ${i.condition?`<span style="background:rgba(176,128,66,0.1);color:var(--primary-color);font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;">${escHtml(i.condition)}</span>`:''}
        </div>`;
      } else if (type === 'courses') {
        extra = `<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          ${i.price?`<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} €</strong>`:''}
          ${i.available_from?`<span style="font-size:12px;color:var(--text-secondary);display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:13px;">event</span>${i.available_from}</span>`:''}
        </div>`;
      } else if (type === 'other') {
        extra = i.price ? `<strong style="color:var(--primary-color);font-size:15px;">${parseFloat(i.price).toFixed(2)} €</strong>` : '';
      }
      return `<div class="inz-listing-card" style="${isBoosted?'border-color:var(--primary-color);box-shadow:0 4px 16px rgba(176,128,66,0.18);':''}">
        ${isBoosted?`<div style="background:linear-gradient(90deg,var(--primary-color),#d4af37);color:#fff;font-size:11px;font-weight:800;padding:4px 12px;display:flex;align-items:center;gap:5px;"><span class="material-symbols-outlined" style="font-size:14px;">rocket_launch</span>TOP</div>`:''}
        ${imgHtml}
        <div class="card-body">
          <h4 style="margin:0;font-size:14.5px;font-weight:800;color:var(--text-primary);line-height:1.3;">${escHtml(i.title)}</h4>
          ${i.description?`<p style="margin:0;font-size:12.5px;color:var(--text-secondary);line-height:1.4;">${escHtml(i.description.substring(0,110))}${i.description.length>110?'…':''}</p>`:''}
          ${extra}
          <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:12px;color:var(--text-secondary);margin-top:auto;">
            ${i.location?`<span><span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">location_on</span> ${escHtml(i.location)}</span>`:''}
            <span><span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">calendar_today</span> ${formatDate(i.created_at)}</span>
          </div>
          ${i.email||i.contact_email?`<div style="font-size:12px;color:var(--primary-color);">${escHtml(i.email||i.contact_email)}</div>`:''}
        </div>
      </div>`;
    }).join('');
  }

  function firstImage(item) {
    const imgs = item.images_arr || [];
    if (!imgs.length) return '';
    return `<div style="height:160px;overflow:hidden;background:var(--bg-color);">
      <img src="/${imgs[0]}" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy" onerror="this.parentElement.style.display='none'">
    </div>`;
  }

  function emptyState(icon, text) {
    return `<div style="grid-column:1/-1;text-align:center;padding:48px 20px;color:var(--text-secondary);">
      <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.35;">${icon}</span>
      <p style="margin:0;font-size:14px;">${text}</p>
    </div>`;
  }

  /* ══════════════════════════════════════════════
     MOJE INZERÁTY
     ══════════════════════════════════════════════ */
  const typeLabel = { work:'Práca', rental:'Prenájom', sale:'Predaj', courses:'Kurzy', other:'Ostatné',
                      people_seek:'Práca', people_offer:'Práca', equipment:'Predaj', chair:'Prenájom' };

  function myListingCard(i) {
    const now = new Date();
    const activePromos = [];
    if (i.boosted_until        && new Date(i.boosted_until)        > now) activePromos.push(['Topované',              i.boosted_until]);
    if (i.morning_expires_at   && new Date(i.morning_expires_at)   > now) activePromos.push(['Ranné vtáča',            i.morning_expires_at]);
    if (i.prime_expires_at     && new Date(i.prime_expires_at)     > now) activePromos.push(['Prime-time Bombardér',   i.prime_expires_at]);
    if (i.vip_glow_expires_at  && new Date(i.vip_glow_expires_at)  > now) activePromos.push(['Neon Glow rámček',       i.vip_glow_expires_at]);
    if (i.vip_badge_expires_at && new Date(i.vip_badge_expires_at) > now) activePromos.push(['Štítok "Rýchly predaj"', i.vip_badge_expires_at]);
    const isBoosted = activePromos.length > 0;
    const status = i.status || 'active';
    const img = (i.images_arr && i.images_arr[0]) ? `/${i.images_arr[0]}` : '';

    return `<div class="inz-my-card" id="my-listing-${i.id}" style="${isBoosted ? 'border-color:var(--primary-color);box-shadow:0 4px 16px rgba(176,128,66,0.13);' : ''}">
      ${isBoosted ? `<div style="position:absolute;top:10px;left:10px;z-index:2;background:linear-gradient(90deg,var(--primary-color),#d4af37);color:#fff;font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:6px;display:flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(0,0,0,0.2);"><span class="material-symbols-outlined" style="font-size:13px;">rocket_launch</span>ZVÝRAZNENÝ</div>` : ''}
      ${status !== 'active' ? `<div style="position:absolute;top:10px;${isBoosted?'left:140px;':'left:10px;'}z-index:2;background:${status==='sold'?'#ef4444':'#f59e0b'};color:#fff;font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:6px;text-transform:uppercase;box-shadow:0 2px 6px rgba(0,0,0,0.2);">${status==='sold'?'Predané':'Rezervované'}</div>` : ''}
      <div style="height:150px;background:var(--bg-color);display:flex;align-items:center;justify-content:center;overflow:hidden;">
        ${img ? `<img src="${img}" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy" onerror="this.parentElement.innerHTML='<span class=&quot;material-symbols-outlined&quot; style=&quot;font-size:40px;opacity:0.2;&quot;>image</span>'">`
              : `<span class="material-symbols-outlined" style="font-size:40px;color:var(--text-secondary);opacity:0.2;">image</span>`}
      </div>
      <div style="padding:14px;display:flex;flex-direction:column;gap:6px;flex:1;">
        <div style="font-weight:800;font-size:14.5px;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(i.title || '—')}</div>
        ${i.price ? `<div style="color:var(--primary-color);font-weight:800;font-size:16px;">${parseFloat(i.price).toFixed(2)} €</div>` : ''}
        <div style="display:flex;justify-content:space-between;align-items:center;color:var(--text-secondary);font-size:11.5px;margin-top:auto;padding-top:8px;border-top:1px solid var(--border-color);">
          <span style="display:flex;align-items:center;gap:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><span class="material-symbols-outlined" style="font-size:14px;">location_on</span>${escHtml(i.location || '—')}</span>
          <span style="display:flex;align-items:center;gap:3px;flex-shrink:0;"><span class="material-symbols-outlined" style="font-size:14px;">visibility</span>${parseInt(i.views||0)}×</span>
        </div>
      </div>
      <div style="background:var(--bg-color);border-top:1px solid var(--border-color);padding:12px 14px;display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
          <span style="font-size:11.5px;font-weight:700;color:var(--text-secondary);white-space:nowrap;">Stav:</span>
          <select onchange="InzManager.updateStatus(${i.id}, this.value)" style="flex:1;height:28px;padding:0 6px;border-radius:6px;font-size:11.5px;font-weight:700;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-primary);">
            <option value="active" ${status==='active'?'selected':''}>Aktívny</option>
            <option value="reserved" ${status==='reserved'?'selected':''}>Rezervované</option>
            <option value="sold" ${status==='sold'?'selected':''}>Predané</option>
          </select>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <a href="inzerat.php?id=${i.id}" class="inz-btn-secondary" style="flex:1;min-width:70px;padding:7px 4px;font-size:11.5px;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:4px;text-decoration:none;">
            <span class="material-symbols-outlined" style="font-size:15px;">visibility</span>Zobraziť
          </a>
          <button type="button" onclick="InzManager.boostListing(${i.id})" class="inz-btn-secondary" style="flex:1;min-width:70px;padding:7px 4px;font-size:11.5px;border-radius:8px;color:var(--primary-color);display:flex;align-items:center;justify-content:center;gap:4px;">
            <span class="material-symbols-outlined" style="font-size:15px;">rocket_launch</span>Zvýrazniť
          </button>
          <button type="button" onclick="InzManager.shareListing(${i.id})" class="inz-btn-secondary" style="padding:7px 10px;font-size:11.5px;border-radius:8px;color:#10b981;display:flex;align-items:center;justify-content:center;" title="Zdieľať a získať odmenu">
            <span class="material-symbols-outlined" style="font-size:15px;">share</span>
          </button>
          <button type="button" onclick="InzManager.deleteListing(${i.id})" class="inz-btn-secondary" style="padding:7px 10px;font-size:11.5px;border-radius:8px;color:#ef4444;display:flex;align-items:center;justify-content:center;" title="Zmazať">
            <span class="material-symbols-outlined" style="font-size:15px;">delete</span>
          </button>
        </div>
        ${activePromos.length ? `<div style="border-top:1px dashed var(--border-color);padding-top:8px;display:flex;flex-direction:column;gap:3px;">
          ${activePromos.map(([name, exp]) => `<div style="display:flex;justify-content:space-between;font-size:10.5px;"><span style="font-weight:700;color:var(--primary-color);">${name}</span><span style="color:var(--text-secondary);">${formatDate(exp)}</span></div>`).join('')}
        </div>` : ''}
      </div>
    </div>`;
  }

  async function updateStatus(id, status) {
    try {
      const fd = new FormData(); fd.append('action', 'update_status'); fd.append('id', id); fd.append('status', status);
      const res = await fetch('api/classifieds.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) toast('Stav bol zmenený.', 'success');
      else toast(data.error || 'Chyba.', 'error');
    } catch (e) { toast('Chyba pripojenia.', 'error'); }
  }

  async function loadMyListings() {
    const container = document.getElementById('my-listings');
    if (!container) return;
    try {
      const fd = new FormData(); fd.append('action','my_list');
      const res  = await fetch('api/classifieds.php', { method:'POST', body:fd });
      const data = await res.json();
      if (data.success && data.data && data.data.length) {
        container.innerHTML = data.data.map(myListingCard).join('');
      } else {
        container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:24px;color:var(--text-secondary);">Zatiaľ ste nezverejnili žiadny inzerát.</div>';
      }
    } catch (e) {
      container.innerHTML = '<div style="grid-column:1/-1;padding:20px;color:#ef4444;">Chyba načítania.</div>';
    }
  }

  /* ══════════════════════════════════════════════
     SUBMIT LISTING
     ══════════════════════════════════════════════ */
  async function submitListing(type) {
    const dur = document.getElementById(type + '-duration')?.value || 'week';

    if (g_adStatus) {
      const { monthly_limit, ad_credits, wallet, ad_price_week, ad_price_month, credits_week = 1, credits_month = 3 } = g_adStatus;
      const adPriceForDur = dur === 'week' ? (ad_price_week || 0.50) : (ad_price_month || 1.50);
      const creditsNeed = dur === 'week' ? credits_week : credits_month;
      const hasCredits  = monthly_limit > 0 && ad_credits >= creditsNeed;
      const canPay      = wallet >= adPriceForDur;
      if (!hasCredits && !canPay) {
        toast('Nedostatočný zostatok v peňaženke. Nabite peňaženku a skúste znovu.', 'error');
        return;
      }
    }

    const fd = new FormData();
    fd.append('action', 'create_listing');
    fd.append('type', type);
    fd.append('duration', dur);

    const g = id => document.getElementById(id);
    const v = id => g(id)?.value?.trim() || '';

    if (type === 'work') {
      const title = v('work-title');
      if (!title) { toast('Vyplňte nadpis.', 'warning'); return; }
      fd.append('title', title);
      fd.append('listing_type', v('work-listing-type'));
      fd.append('specialization', v('work-specialization'));
      fd.append('description', v('work-description'));
      fd.append('location', v('work-location'));
      fd.append('phone', v('work-phone'));
      fd.append('email', v('work-email'));

    } else if (type === 'rental') {
      const title = v('rental-title');
      const desc  = v('rental-description');
      const price = v('rental-price');
      if (!title) { toast('Vyplňte nadpis.', 'warning'); return; }
      if (!price)  { toast('Vyplňte cenu.', 'warning'); return; }
      fd.append('title', title);
      fd.append('listing_type', v('rental-subtype'));
      fd.append('description', desc);
      fd.append('price', price);
      fd.append('price_unit', v('rental-price-unit'));
      fd.append('available_from', v('rental-available-from'));
      fd.append('location', v('rental-location'));
      fd.append('email', v('rental-email'));

    } else if (type === 'sale') {
      const title = v('sale-title');
      if (!title) { toast('Vyplňte názov.', 'warning'); return; }
      fd.append('title', title);
      fd.append('description', v('sale-description'));
      fd.append('price', v('sale-price'));
      fd.append('condition', v('sale-condition'));
      fd.append('location', v('sale-location'));
      fd.append('email', v('sale-email'));

    } else if (type === 'courses') {
      const title = v('courses-title');
      if (!title) { toast('Vyplňte názov kurzu.', 'warning'); return; }
      fd.append('title', title);
      fd.append('description', v('courses-description'));
      fd.append('price', v('courses-price'));
      fd.append('available_from', v('courses-date'));
      fd.append('location', v('courses-location'));
      fd.append('email', v('courses-email'));

    } else if (type === 'other') {
      const title = v('other-title');
      if (!title) { toast('Vyplňte nadpis.', 'warning'); return; }
      fd.append('title', title);
      fd.append('description', v('other-description'));
      fd.append('price', v('other-price'));
      fd.append('location', v('other-location'));
      fd.append('email', v('other-email'));
    }

    const imgs = g_images[type] || [];
    const mainIdx = parseInt(g(type + '-main-image-index')?.value || '0');
    fd.append('main_image_index', mainIdx);
    imgs.forEach(img => { if (img.file) fd.append('images[]', img.file); });

    try {
      const res  = await fetch('api/classifieds.php', { method:'POST', body:fd });
      const data = await res.json();
      if (data.success) {
        toast('Inzerát bol úspešne zverejnený!', 'success');
        closeModal('modal-' + type);
        loadListings(type);
        loadMyListings();
      } else {
        toast(data.error || 'Chyba pri zverejňovaní.', 'error');
      }
    } catch (e) {
      toast('Chyba pripojenia.', 'error');
    }
  }

  /* ══════════════════════════════════════════════
     DELETE LISTING
     ══════════════════════════════════════════════ */
  function deleteListing(id) {
    g_deleteId = id;
    openModal('modal-delete-confirm');
  }

  async function confirmDeleteListing() {
    if (!g_deleteId) return;
    closeModal('modal-delete-confirm');
    const fd = new FormData();
    fd.append('action', 'delete_listing');
    fd.append('id', g_deleteId);
    g_deleteId = null;
    try {
      const res  = await fetch('api/classifieds.php', { method:'POST', body:fd });
      const data = await res.json();
      if (data.success) {
        toast('Inzerát bol zmazaný.', 'success');
        loadMyListings();
        g_tabTypes.forEach(t => loadListings(t));
      } else {
        toast(data.error || 'Chyba.', 'error');
      }
    } catch (e) {
      toast('Chyba pripojenia.', 'error');
    }
  }

  /* ══════════════════════════════════════════════
     TOPOVANIE (BOOST)
     ══════════════════════════════════════════════ */
  function boostListing(id) {
    g_boostId = id;
    openModal('modal-boost');
  }

  async function confirmBoost(boostKey, price) {
    if (!g_boostId) return;
    const id = g_boostId;
    g_boostId = null;
    closeModal('modal-boost');
    const fd = new FormData();
    fd.append('action', 'boost_listing');
    fd.append('id', id);
    fd.append('boost_key', boostKey);
    try {
      const res  = await fetch('api/classifieds.php', { method:'POST', body:fd });
      const data = await res.json();
      if (data.success) {
        toast(data.message || 'Inzerát je topovaný!', 'success');
        loadMyListings();
        g_tabTypes.forEach(t => loadListings(t));
      } else if (data.need_credit) {
        toast(data.error, 'warning');
      } else {
        toast(data.error || 'Chyba.', 'error');
      }
    } catch (e) {
      toast('Chyba pripojenia.', 'error');
    }
  }

  /* ══════════════════════════════════════════════
     ZDIEĽANIE INZERÁTU (odmena do Peňaženky)
     ══════════════════════════════════════════════ */
  function shareListing(id) {
    const url = 'https://<?= BRAND_SITE ?>/inzercia.php';
    const title = 'Pozrite si tento inzerát na <?= BRAND_SITE ?>!';
    const fbUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`;
    const win = window.open(fbUrl, 'fb-share', 'width=600,height=500');
    const timer = setInterval(() => { if (!win || win.closed) { clearInterval(timer); claimShareReward('facebook', id); } }, 1000);
  }

  async function claimShareReward(platform, listingId) {
    try {
      const fd = new FormData();
      fd.append('action', 'claim_share_reward');
      fd.append('platform', platform);
      fd.append('target', 'listing');
      fd.append('listing_id', listingId);
      const res  = await fetch('api/wallet.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) toast(data.message, 'success');
      else if (!data.already_claimed) toast(data.error || 'Nepodarilo sa pripísať odmenu.', 'error');
      else toast(data.error, 'warning');
    } catch (e) {
      toast('Chyba pripojenia.', 'error');
    }
  }

  /* ── Helpers ── */
  function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function formatDate(str) {
    if (!str) return '';
    try { return new Date(str).toLocaleDateString('sk-SK'); } catch(_) { return str; }
  }

  function toast(msg, type = 'success') {
    if (typeof showAppToast === 'function') { showAppToast(msg, type); return; }
    let c = document.getElementById('_inz_tc');
    if (!c) { c = document.createElement('div'); c.id = '_inz_tc'; c.style.cssText = 'position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;'; document.body.appendChild(c); }
    const t = document.createElement('div');
    t.style.cssText = `background:${type==='error'?'#ef4444':type==='warning'?'#f59e0b':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;opacity:0;transform:translateY(-15px);transition:all 0.3s;pointer-events:none;`;
    t.innerText = msg; c.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; }));
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(-15px)'; setTimeout(() => t.remove(), 320); }, 3500);
  }

  /* ── Init (host page calls InzManager.init() when this section becomes visible) ── */
  function init() {
    g_tabTypes.forEach(t => loadListings(t));
    loadMyListings();
    fetch('api/classifieds.php?action=get_ad_status')
      .then(r => r.json())
      .then(d => { if (d.success) renderPricingBar(d); })
      .catch(() => {});
  }

  window.InzManager = {
    switchTab, setWorkType, filterWork, setDuration, submitListing,
    openModal, closeModal, confirmBoost, confirmDeleteListing,
    boostListing, deleteListing, handleImageSelect, handleImageDrop,
    setMainImg, removeImg, shareListing, updateStatus, init
  };
})();
</script>
