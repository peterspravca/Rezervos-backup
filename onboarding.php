<?php
// onboarding.php — jednorazový úvodný sprievodca nastavením po registrácii.
require_once 'config.php';
require_once 'translator_helper.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT full_name, role, onboarding_completed FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit;
}

if ((int)$user['onboarding_completed'] === 1) {
    header('Location: ' . ($user['role'] === 'business' ? 'dashboard.php' : 'moj_profil.php'));
    exit;
}

$first_name = trim(explode(' ', trim($user['full_name']))[0]);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo t('Nastavenie účtu'); ?> — <?php echo BRAND_NAME; ?></title>
<link rel="stylesheet" href="assets/css/variables.css?v=3">
<link rel="stylesheet" href="assets/css/scrollbars.css?v=3">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<script src="assets/js/theme.js" defer></script>
<style>
*{box-sizing:border-box;}
body{margin:0;padding:24px 16px;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.wiz-shell{width:100%;max-width:600px;}
.wiz-card{background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;box-shadow:var(--shadow-lg);overflow:hidden;}
.wiz-close{flex-shrink:0;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--text-secondary);text-decoration:none;transition:all .2s ease;}
.wiz-close:hover{background:var(--bg-secondary);color:var(--primary-color);transform:rotate(90deg) scale(1.15);}
.wiz-close .material-symbols-outlined{font-size:24px;}
.wiz-top{padding:22px 32px 0;}
.wiz-progress-row{display:flex;align-items:center;gap:14px;margin-bottom:16px;}
.wiz-steplabel{font-size:12px;font-weight:700;color:var(--text-secondary);letter-spacing:.03em;white-space:nowrap;font-variant-numeric:tabular-nums;}
.wiz-track{flex:1;height:6px;border-radius:99px;background:var(--border-color);overflow:hidden;}
.wiz-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#d4af37,var(--primary-color));transition:width .3s ease;}
.wiz-body{padding:8px 32px 30px;min-height:340px;display:flex;flex-direction:column;justify-content:center;}
.step-heading{display:flex;align-items:center;gap:9px;margin-bottom:8px;}
.step-heading .material-symbols-outlined{font-size:22px;color:var(--primary-color);flex-shrink:0;}
.wiz-body h2{font-size:22px;font-weight:800;margin:0;letter-spacing:-.01em;}
.wiz-body .sub{color:var(--text-secondary);font-size:14.5px;line-height:1.55;margin:0 0 24px;}
.wiz-foot{display:flex;justify-content:space-between;align-items:center;padding:18px 32px;border-top:1px solid var(--border-color);}
.wiz-err{display:none;background:rgba(220,38,38,.1);border:1.5px solid rgba(220,38,38,.25);color:#dc2626;border-radius:10px;padding:10px 13px;font-size:13px;margin-bottom:16px;}

.role-grid{display:flex;gap:14px;}
.role-card{flex:1;border:2px solid var(--border-color);border-radius:14px;padding:22px 16px;text-align:center;cursor:pointer;background:var(--bg-secondary);transition:all .15s ease;}
.role-card:hover{border-color:var(--primary-color);}
.role-card .material-symbols-outlined{font-size:32px;color:var(--primary-color);margin-bottom:10px;}
.role-card b{display:block;font-size:15px;margin-bottom:4px;}
.role-card span.d{font-size:12.5px;color:var(--text-secondary);}

label.field{display:block;margin-bottom:15px;}
label.field span{display:block;font-size:12.5px;font-weight:700;color:var(--text-secondary);margin-bottom:6px;}
label.field span .req{color:#dc2626;font-style:normal;}
.field input, .field select{width:100%;font-family:inherit;font-size:14.5px;color:var(--text-primary);background:var(--input-bg);border:1.5px solid var(--border-color);border-radius:10px;padding:11px 13px;}
.field input::placeholder{color:var(--text-secondary);opacity:.6;font-style:italic;}
.field select{
  scrollbar-width:thin;scrollbar-color:#aab1bb transparent;
  appearance:none;-webkit-appearance:none;-moz-appearance:none;
  padding-right:38px;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 14px center;background-size:16px;
}
html.dark-mode .field select, body.dark-mode .field select{scrollbar-color:#b08042 transparent;}
.field-row{display:flex;gap:12px;}
.field-row .field{flex:1;}

.loc-field{position:relative;}
.loc-suggestions{
  display:none;position:fixed;z-index:9999;
  background:var(--card-bg);border:1.5px solid var(--border-color);border-radius:10px;
  box-shadow:var(--shadow-lg);max-height:220px;overflow-y:auto;padding:6px;
  scrollbar-width:thin;scrollbar-color:#aab1bb transparent;
}
html.dark-mode .loc-suggestions, body.dark-mode .loc-suggestions{scrollbar-color:#b08042 transparent;}
.loc-suggestions::-webkit-scrollbar{width:2px;}
.loc-suggestions::-webkit-scrollbar-track{background:transparent;}
.loc-suggestions::-webkit-scrollbar-thumb{
  background:linear-gradient(to bottom,transparent 0%,transparent 27%,#aab1bb 27%,#aab1bb 73%,transparent 73%,transparent 100%);
}
html.dark-mode .loc-suggestions::-webkit-scrollbar-thumb, body.dark-mode .loc-suggestions::-webkit-scrollbar-thumb{
  background:linear-gradient(to bottom,transparent 0%,transparent 27%,#b08042 27%,#b08042 73%,transparent 73%,transparent 100%);
}
.loc-suggestion{
  display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;cursor:pointer;font-size:13.5px;color:var(--text-primary);
}
.loc-suggestion:hover{background:var(--bg-secondary);}
.loc-suggestion .material-symbols-outlined{font-size:18px;color:var(--primary-color);flex-shrink:0;}
.loc-suggestion .muted{color:var(--text-secondary);font-size:12px;}
.loc-suggestion.empty{color:var(--text-secondary);cursor:default;}
.loc-suggestion.empty:hover{background:none;}

.ico-row{display:flex;gap:8px;}
.ico-row input{flex:1;}
.ico-btn{font-family:inherit;font-size:12.5px;font-weight:700;white-space:nowrap;color:#fff;background:var(--primary-color);border:none;border-radius:10px;padding:0 14px;cursor:pointer;}
.ico-btn:disabled{opacity:.6;cursor:default;}
.ico-found{display:none;align-items:flex-start;gap:10px;background:rgba(21,128,61,.1);border:1.5px solid rgba(21,128,61,.25);border-radius:10px;padding:11px 13px;margin:2px 0 18px;font-size:12.5px;color:var(--text-primary);line-height:1.5;}
.ico-found .material-symbols-outlined{font-size:17px;color:#15803d;flex-shrink:0;}
.ico-found b{display:block;color:#15803d;font-size:13.5px;}

.svc-edit-row{position:relative;padding-top:4px;}
.svc-edit-row + .svc-edit-row{margin-top:6px;padding-top:18px;border-top:1px solid var(--border-color);}
.svc-remove{position:absolute;top:14px;right:0;background:none;border:none;color:var(--text-secondary);font-size:12px;font-weight:700;cursor:pointer;padding:4px 0;}
.svc-remove:hover{color:#dc2626;}
.add-row{display:flex;align-items:center;justify-content:center;gap:8px;color:var(--primary-color);font-weight:700;font-size:13px;border:1.5px dashed var(--border-color);border-radius:10px;padding:11px 13px;cursor:pointer;margin-bottom:2px;}
.add-row:hover{border-color:var(--primary-color);background:rgba(176,128,66,.06);}

.sched-row{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border-color);font-size:13.5px;}
.sched-row:last-child{border-bottom:none;}
.sched-day{width:70px;font-weight:700;flex-shrink:0;}
.sched-toggle{width:34px;height:20px;border-radius:99px;background:var(--border-color);position:relative;flex-shrink:0;cursor:pointer;border:none;padding:0;}
.sched-toggle.on{background:var(--primary-color);}
.sched-toggle i{position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:.15s;}
.sched-toggle.on i{left:16px;}
.sched-times{display:flex;gap:6px;flex:1;align-items:center;}
.sched-times input{width:110px;min-width:110px;font-family:inherit;font-size:13px;border:1.5px solid var(--border-color);border-radius:8px;padding:6px 8px;background:var(--input-bg);color:var(--text-primary);}
.sched-times span{color:var(--text-secondary);font-size:12px;}
.sched-row.off .sched-times{visibility:hidden;}

.later-note{display:flex;align-items:flex-start;gap:9px;margin-top:16px;padding:11px 13px;border:1.5px dashed var(--border-color);border-radius:10px;font-size:12px;color:var(--text-secondary);line-height:1.55;}
.later-note .material-symbols-outlined{font-size:16px;color:var(--primary-color);flex-shrink:0;}
.later-note b{color:var(--text-primary);}

.done-badge{width:60px;height:60px;border-radius:50%;background:rgba(21,128,61,.12);color:#15803d;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;}
.done-badge .material-symbols-outlined{font-size:30px;}
.center{text-align:center;}

.btn{font-family:inherit;font-size:14px;font-weight:700;border-radius:10px;padding:10px 20px;cursor:pointer;transition:all .15s ease;border:1.5px solid transparent;}
.btn-ghost{background:transparent;border-color:var(--border-color);color:var(--text-primary);}
.btn-ghost:disabled{opacity:.35;cursor:default;}
.btn-primary{background:var(--primary-color);color:#fff;box-shadow:0 3px 10px rgba(176,128,66,.3);}
.btn-primary:disabled{opacity:.6;cursor:default;}

@media (max-width:520px){ .wiz-top,.wiz-body,.wiz-foot{padding-left:18px;padding-right:18px;} .field-row{flex-direction:column;} }
</style>
</head>
<body>
<div class="wiz-shell">
  <div class="wiz-card">
    <div class="wiz-top">
      <div class="wiz-progress-row">
        <span class="wiz-steplabel" id="steplabel">1/1</span>
        <div class="wiz-track"><div class="wiz-fill" id="fill" style="width:10%"></div></div>
        <a href="index.php" class="wiz-close" title="<?php echo htmlspecialchars(t('Dokončiť neskôr')); ?>">
          <span class="material-symbols-outlined notranslate" translate="no">close</span>
        </a>
      </div>
    </div>
    <div class="wiz-body" id="body"></div>
    <div class="wiz-foot">
      <button type="button" class="btn btn-ghost" id="backbtn"><?php echo t('Späť'); ?></button>
      <button type="button" class="btn btn-primary" id="nextbtn"><?php echo t('Pokračovať'); ?></button>
    </div>
  </div>
</div>

<script>
const T = {
  requiredIco: <?php echo json_encode(t('Zadajte platné 8-miestne IČO.')); ?>,
  icoNotFound: <?php echo json_encode(t('Firma s týmto IČO sa nenašla. Údaje doplňte ručne.')); ?>,
  requiredBasics: <?php echo json_encode(t('Vyplňte prosím názov, adresu, mesto, kategóriu a telefón.')); ?>,
  requiredService: <?php echo json_encode(t('Zadajte názov služby.')); ?>,
  networkError: <?php echo json_encode(t('Nepodarilo sa spojiť so serverom. Skúste to prosím znova.')); ?>
};
const FIRST_NAME = <?php echo json_encode($first_name); ?>;

const DAYS = [
  ['mon','<?php echo t('Pondelok'); ?>'], ['tue','<?php echo t('Utorok'); ?>'], ['wed','<?php echo t('Streda'); ?>'],
  ['thu','<?php echo t('Štvrtok'); ?>'], ['fri','<?php echo t('Piatok'); ?>'], ['sat','<?php echo t('Sobota'); ?>'], ['sun','<?php echo t('Nedeľa'); ?>']
];

const COUNTRY_NAMES = { SK:'Slovensko', CZ:'Česká republika', AT:'Rakúsko', HU:'Maďarsko', PL:'Poľsko', DE:'Nemecko' };
const SITE_COUNTRY = <?php echo json_encode(($current_lang === 'cz') ? 'CZ' : 'SK'); ?>;

const CATEGORIES = ['Vlasy','Holičstvo a Barber','Nechty','Starostlivosť o pleť','Obočie a riasy','Masáž','Make-up','Wellness a kúpele','Vrkoče a dredy','Tetovanie','Lekárska estetika','Depilácia a epilácia','Domáce služby','Piercing','Služby pre miláčikov','Zubné a ortodontické','Zdravie a kondícia','Profesionálne služby','Solárium a opaľovanie','Joga a Pilates','Fyzioterapia','Osobní tréneri','Výživové poradenstvo','Svadobné služby','Alternatívna medicína','Psychológia a Terapia','Iné'];

// Návrh služby podľa vybranej kategórie — len ako predvyplnený štartovací bod, dá sa upraviť.
const SERVICE_SUGGESTIONS = {
  'Vlasy': {name:'Dámsky strih',duration:45,price:25},
  'Holičstvo a Barber': {name:'Pánsky strih',duration:30,price:15},
  'Nechty': {name:'Manikúra',duration:60,price:20},
  'Starostlivosť o pleť': {name:'Kozmetické ošetrenie tváre',duration:60,price:35},
  'Obočie a riasy': {name:'Úprava obočia',duration:30,price:12},
  'Masáž': {name:'Relaxačná masáž',duration:60,price:40},
  'Make-up': {name:'Denný make-up',duration:45,price:30},
  'Wellness a kúpele': {name:'Vstup do wellness',duration:90,price:25},
  'Vrkoče a dredy': {name:'Zapletanie vrkočov',duration:90,price:40},
  'Tetovanie': {name:'Malé tetovanie',duration:60,price:60},
  'Lekárska estetika': {name:'Konzultácia',duration:30,price:30},
  'Depilácia a epilácia': {name:'Depilácia nôh',duration:30,price:20},
  'Domáce služby': {name:'Služba u klienta',duration:60,price:40},
  'Piercing': {name:'Piercing ucha',duration:20,price:15},
  'Služby pre miláčikov': {name:'Kúpanie a strihanie',duration:60,price:30},
  'Zubné a ortodontické': {name:'Dentálna hygiena',duration:45,price:40},
  'Zdravie a kondícia': {name:'Konzultácia',duration:30,price:20},
  'Profesionálne služby': {name:'Konzultácia',duration:30,price:20},
  'Solárium a opaľovanie': {name:'Solárium 10 min',duration:10,price:8},
  'Joga a Pilates': {name:'Skupinová lekcia',duration:60,price:10},
  'Fyzioterapia': {name:'Fyzioterapeutické ošetrenie',duration:45,price:35},
  'Osobní tréneri': {name:'Osobný trénerský tréning',duration:60,price:30},
  'Výživové poradenstvo': {name:'Výživová konzultácia',duration:45,price:30},
  'Svadobné služby': {name:'Svadobný make-up',duration:90,price:80},
  'Alternatívna medicína': {name:'Konzultácia',duration:45,price:30},
  'Psychológia a Terapia': {name:'Terapeutická konzultácia',duration:50,price:40},
  'Iné': {name:'Základná služba',duration:30,price:20}
};

const state = {
  role: null,
  customer: { phone:'', city:'' },
  business: {
    ico:'', name:'', category:'', address:'', city:'', phone:'',
    hours: { mon:{on:true,start:'09:00',end:'17:00'}, tue:{on:true,start:'09:00',end:'17:00'}, wed:{on:true,start:'09:00',end:'17:00'},
             thu:{on:true,start:'09:00',end:'17:00'}, fri:{on:true,start:'09:00',end:'17:00'}, sat:{on:false,start:'',end:''}, sun:{on:false,start:'',end:''} },
    services: []
  }
};

let flow = ['role'];
let idx = 0;
let busy = false;

function icon(name){ return `<span class="material-symbols-outlined notranslate" translate="no">${name}</span>`; }
function laterNote(where){
  return `<div class="later-note">${icon('info')}<div><?php echo t('Toto môžete kedykoľvek doplniť aj neskôr v'); ?> <b>${where}</b>. <?php echo t('Netreba to riešiť teraz.'); ?></div></div>`;
}
function err(msg){
  let e = document.getElementById('wiz-err');
  if (!e) return alert(msg);
  e.textContent = msg;
  e.style.display = 'block';
}
function clearErr(){
  let e = document.getElementById('wiz-err');
  if (e) e.style.display = 'none';
}

async function api(url, params){
  const fd = new FormData();
  Object.keys(params).forEach(k => fd.append(k, params[k]));
  const res = await fetch(url, { method:'POST', body: fd });
  return res.json();
}

function renderShell(bodyHtml){
  document.getElementById('body').innerHTML = `<div class="wiz-err" id="wiz-err"></div>${bodyHtml}`;
}

const STEPS = {
  role: {
    render(){
      renderShell(`
        <div class="step-heading">${icon('waving_hand')}<h2><?php echo t('Vitajte'); ?>${FIRST_NAME ? ', ' + FIRST_NAME : ''}</h2></div>
        <p class="sub"><?php echo t('Než vás pustíme do nástenky, povedzte nám, na čo budete Rezervos používať.'); ?></p>
        <div class="role-grid">
          <div class="role-card" data-role="customer">
            ${icon('person')}
            <b><?php echo t('Som zákazník'); ?></b>
            <span class="d"><?php echo t('Chcem si rezervovať termíny'); ?></span>
          </div>
          <div class="role-card" data-role="business">
            ${icon('storefront')}
            <b><?php echo t('Mám prevádzku'); ?></b>
            <span class="d"><?php echo t('Chcem prijímať rezervácie'); ?></span>
          </div>
        </div>
        <div class="later-note">${icon('info')}<div><?php echo t('Vyplnenie zaberie len okamih — a pri prevádzke max minútu. Formulár nedokážeme uložiť ako rozpracovaný, ak ho nedokončíte, budete ho musieť vyplniť znova.'); ?></div></div>
        <div class="later-note">${icon('info')}<div><?php echo t('Ide o prvý krok. Ostatné údaje môžete kedykoľvek doplniť neskôr vo svojej Nástenke.'); ?></div></div>
      `);
      document.querySelectorAll('.role-card').forEach(c => c.addEventListener('click', () => chooseRole(c.dataset.role)));
      document.getElementById('nextbtn').style.display = 'none';
    }
  },
  cust_basics: {
    render(){
      renderShell(`
        <div class="step-heading">${icon('badge')}<h2><?php echo t('Vaše údaje'); ?></h2></div>
        <p class="sub"><?php echo t('Telefón'); ?>: <?php echo t('SMS pripomienky termínov.'); ?><br><?php echo t('Mesto'); ?>: <?php echo t('Zobrazenie prevádzok vo vašom okolí.'); ?></p>
        <label class="field"><span><?php echo t('Telefón'); ?></span><input id="c-phone" value="${state.customer.phone}" placeholder="+421 900 123 456"></label>
        <label class="field loc-field"><span><?php echo t('Mesto alebo PSČ'); ?></span><input id="c-city" value="${state.customer.city}" placeholder="<?php echo t('napr. Bratislava alebo 811 06'); ?>" autocomplete="off"></label>
        ${laterNote('<?php echo t('Nastavenia profilu'); ?>')}
      `);
      document.getElementById('nextbtn').style.display = '';
      wireCityAutocomplete('c-city');
    },
    async submit(){
      state.customer.phone = document.getElementById('c-phone').value.trim();
      state.customer.city = document.getElementById('c-city').value.trim();
      try {
        const r = await api('api/onboarding.php', { action:'save_customer_basics', phone: state.customer.phone, city: state.customer.city });
        if (!r.success) { err(r.message || T.networkError); return false; }
        return true;
      } catch (e) {
        err(T.networkError);
        return false;
      }
    }
  },
  cust_done: {
    render(){
      renderShell(`
        <div class="done-badge">${icon('check')}</div>
        <div class="center">
          <h2><?php echo t('Ste pripravení!'); ?></h2>
          <p class="sub"><?php echo t('Vaša nástenka je pripravená.'); ?></p>
        </div>
      `);
      document.getElementById('backbtn').style.display = 'none';
      document.getElementById('nextbtn').textContent = <?php echo json_encode(t('Prejsť do nástenky')); ?>;
    },
    async submit(){ return await finish(); }
  },
  biz_basics: {
    render(){
      renderShell(`
        <div class="step-heading">${icon('storefront')}<h2><?php echo t('Základné údaje o prevádzke'); ?></h2></div>
        <p class="sub"><?php echo t('Zadajte IČO a väčšinu údajov doplníme automaticky.'); ?></p>
        <label class="field">
          <span><?php echo t('IČO'); ?></span>
          <div class="ico-row">
            <input id="b-ico" value="${state.business.ico}" maxlength="12" style="letter-spacing:1px">
            <button type="button" class="ico-btn" id="ico-lookup-btn"><?php echo t('Doplniť podľa IČO'); ?></button>
          </div>
        </label>
        <div class="ico-found" id="ico-found"><span class="material-symbols-outlined notranslate">check_circle</span><div id="ico-found-text"></div></div>
        <label class="field"><span><?php echo t('Názov prevádzky (verejný)'); ?> <i class="req">*</i></span><input id="b-name" value="${state.business.name}"></label>
        <label class="field"><span><?php echo t('Kategória'); ?> <i class="req">*</i></span>
          <select id="b-category">
            <option value=""><?php echo t('Vyberte kategóriu'); ?></option>
            ${CATEGORIES.map(c => `<option value="${c}" ${state.business.category===c?'selected':''}>${c}</option>`).join('')}
          </select>
        </label>
        <div class="field-row">
          <label class="field"><span><?php echo t('Adresa'); ?> <i class="req">*</i></span><input id="b-address" value="${state.business.address}"></label>
          <label class="field loc-field"><span><?php echo t('Mesto alebo PSČ'); ?> <i class="req">*</i></span><input id="b-city" value="${state.business.city}" placeholder="<?php echo t('napr. Bratislava alebo 811 06'); ?>" autocomplete="off"></label>
        </div>
        <label class="field"><span><?php echo t('Telefón'); ?> <i class="req">*</i></span><input id="b-phone" value="${state.business.phone}" placeholder="+421 900 123 456"></label>
      `);
      document.getElementById('nextbtn').style.display = '';
      document.getElementById('ico-lookup-btn').addEventListener('click', lookupIco);
      wireCityAutocomplete('b-city');
    },
    collect(){
      state.business.name = document.getElementById('b-name').value.trim();
      state.business.category = document.getElementById('b-category').value;
      state.business.address = document.getElementById('b-address').value.trim();
      state.business.city = document.getElementById('b-city').value.trim();
      state.business.phone = document.getElementById('b-phone').value.trim();
    },
    submit(){
      this.collect();
      const missing = {
        'b-name': !state.business.name,
        'b-address': !state.business.address,
        'b-city': !state.business.city,
        'b-category': !state.business.category,
        'b-phone': !state.business.phone
      };
      Object.keys(missing).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.borderColor = missing[id] ? '#dc2626' : 'var(--border-color)';
      });
      if (Object.values(missing).some(Boolean)) {
        err(T.requiredBasics);
        return false;
      }
      return true; // odošle sa spolu s krokom hodín
    }
  },
  biz_hours: {
    render(){
      const rows = DAYS.map(([key,label]) => {
        const d = state.business.hours[key];
        return `<div class="sched-row ${d.on?'':'off'}" data-day="${key}">
          <span class="sched-day">${label}</span>
          <button type="button" class="sched-toggle ${d.on?'on':''}"><i></i></button>
          <div class="sched-times">
            <input type="time" class="t-start" value="${d.start||'09:00'}"> <span>–</span> <input type="time" class="t-end" value="${d.end||'17:00'}">
          </div>
        </div>`;
      }).join('');
      renderShell(`
        <div class="step-heading">${icon('schedule')}<h2><?php echo t('Otváracie hodiny'); ?></h2></div>
        <p class="sub"><?php echo t('Rýchly štart už predvyplnený, kedykoľvek upravíte.'); ?></p>
        ${rows}
        ${laterNote('<?php echo t('Prevádzka'); ?>')}
      `);
      document.getElementById('nextbtn').style.display = '';
      document.querySelectorAll('.sched-toggle').forEach(btn => btn.addEventListener('click', () => {
        btn.classList.toggle('on');
        btn.closest('.sched-row').classList.toggle('off');
      }));
    },
    async submit(){
      document.querySelectorAll('.sched-row').forEach(row => {
        const key = row.dataset.day;
        const on = row.querySelector('.sched-toggle').classList.contains('on');
        state.business.hours[key] = {
          on, start: row.querySelector('.t-start').value, end: row.querySelector('.t-end').value
        };
      });
      const oh = {};
      Object.keys(state.business.hours).forEach(key => {
        const d = state.business.hours[key];
        if (d.on && d.start && d.end) oh[key] = { open: d.start, close: d.end };
      });
      try {
        const r = await api('api/business.php', {
          action: 'save_profile',
          name: state.business.name,
          category: state.business.category,
          address: state.business.address,
          city: state.business.city,
          phone: state.business.phone,
          opening_hours: JSON.stringify(oh)
        });
        if (!r.success) { err(r.message || T.networkError); return false; }
        return true;
      } catch (e) {
        err(T.networkError);
        return false;
      }
    }
  },
  biz_service: {
    render(){
      // Ak zatiaľ nie je žiadna služba, predvyplníme návrh podľa kategórie z kroku 2 (dá sa prepísať).
      if (!state.business.services.length) {
        const suggestion = SERVICE_SUGGESTIONS[state.business.category] || SERVICE_SUGGESTIONS['Iné'];
        state.business.services = [{ ...suggestion }];
      }
      const rows = state.business.services.map((s, i) => `
        <div class="svc-edit-row" data-idx="${i}">
          ${state.business.services.length > 1 ? `<button type="button" class="svc-remove" data-idx="${i}">${<?php echo json_encode(t('Odstrániť')); ?>}</button>` : ''}
          <label class="field"><span><?php echo t('Názov služby'); ?></span><input class="s-name" value="${s.name}" placeholder="<?php echo t('napr. Dámsky strih'); ?>"></label>
          <div class="field-row">
            <label class="field"><span><?php echo t('Trvanie (min)'); ?></span><input class="s-duration" type="number" min="5" step="5" value="${s.duration}"></label>
            <label class="field"><span><?php echo t('Cena (€)'); ?></span><input class="s-price" type="number" min="0" step="0.5" value="${s.price}"></label>
          </div>
        </div>
      `).join('');
      renderShell(`
        <div class="step-heading">${icon('sell')}<h2><?php echo t('Aspoň jedna služba'); ?></h2></div>
        <p class="sub"><?php echo t('Navrhli sme podľa vašej kategórie — kľudne upravte. Môžete pridať aj viac, zvyšné doplníte kedykoľvek v Cenníku.'); ?></p>
        ${rows}
        <div class="add-row" id="add-service-row">${icon('add')}<?php echo t('Pridať ďalšiu službu'); ?></div>
        ${laterNote('<?php echo t('Cenník'); ?>')}
      `);
      document.getElementById('nextbtn').style.display = '';
      this.syncFromDom();
      document.getElementById('add-service-row').addEventListener('click', () => {
        this.syncFromDom();
        state.business.services.push({ name:'', duration:30, price:15 });
        this.render();
      });
      document.querySelectorAll('.svc-remove').forEach(btn => btn.addEventListener('click', () => {
        this.syncFromDom();
        state.business.services.splice(parseInt(btn.dataset.idx, 10), 1);
        this.render();
      }));
    },
    syncFromDom(){
      document.querySelectorAll('.svc-edit-row').forEach((row, i) => {
        if (!state.business.services[i]) return;
        state.business.services[i].name = row.querySelector('.s-name').value.trim();
        state.business.services[i].duration = parseInt(row.querySelector('.s-duration').value, 10) || 30;
        state.business.services[i].price = parseFloat(row.querySelector('.s-price').value) || 0;
      });
    },
    async submit(){
      this.syncFromDom();
      const valid = state.business.services.filter(s => s.name);
      if (!valid.length) { err(T.requiredService); return false; }
      try {
        for (const s of valid) {
          const r = await api('api/services.php', { action:'add_service', name: s.name, duration_minutes: s.duration, price: s.price });
          if (!r.success) { err(r.message || T.networkError); return false; }
        }
        return true;
      } catch (e) {
        err(T.networkError);
        return false;
      }
    }
  },
  biz_done: {
    render(){
      renderShell(`
        <div class="done-badge">${icon('check')}</div>
        <div class="center">
          <h2><?php echo t('Profil je pripravený na spustenie!'); ?></h2>
          <p class="sub"><?php echo t('Tím, rezervačné pravidlá aj logo doplníte v Nástenke, keď budete mať chvíľu.'); ?></p>
        </div>
      `);
      document.getElementById('backbtn').style.display = 'none';
      document.getElementById('nextbtn').textContent = <?php echo json_encode(t('Prejsť do nástenky')); ?>;
    },
    async submit(){ return await finish(); }
  }
};

// Jeden zdieľaný dropdown pripojený priamo k <body> (nie do karty), aby ho neorezávalo
// zaoblenie/overflow karty — pozicuje sa cez position:fixed podľa polohy aktívneho poľa.
let __locBox = null;
function getLocBox(){
  if (!__locBox) {
    __locBox = document.createElement('div');
    __locBox.className = 'loc-suggestions';
    document.body.appendChild(__locBox);
  }
  return __locBox;
}
function positionLocBox(box, input){
  const r = input.getBoundingClientRect();
  box.style.left = r.left + 'px';
  box.style.top = (r.bottom + 4) + 'px';
  box.style.width = r.width + 'px';
}

function wireCityAutocomplete(inputId){
  const input = document.getElementById(inputId);
  if (!input) return;
  const box = getLocBox();
  let debounce = null;

  input.addEventListener('input', () => {
    clearTimeout(debounce);
    const val = input.value.trim();
    if (val.length < 2) { box.style.display = 'none'; box.innerHTML = ''; return; }
    debounce = setTimeout(async () => {
      try {
        const res = await fetch('api/search_locations.php?q=' + encodeURIComponent(val));
        const data = await res.json();
        box.innerHTML = '';
        // Pole "Mesto" nepotrebuje PSČ — jedno mesto má často viac PSČ, takže tu ich zlúčime do jednej položky.
        // Zahraničné mestá ukážeme len ak sa v našej krajine nič nenašlo (napr. Žilina existuje aj v ČR).
        let pool = (data.success && data.data) ? data.data : [];
        const homeOnly = pool.filter(i => i.country === SITE_COUNTRY);
        if (homeOnly.length) pool = homeOnly;

        const seen = new Set();
        const items = pool.filter(item => {
          const key = item.city_name + '|' + (item.admin_name || '');
          if (seen.has(key)) return false;
          seen.add(key);
          return true;
        });
        if (items.length) {
          items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'loc-suggestion';
            const admin = item.admin_name ? ` <span class="muted">(${item.admin_name})</span>` : '';
            const countryName = (item.country && item.country !== SITE_COUNTRY) ? (COUNTRY_NAMES[item.country] || item.country) : '';
            const country = countryName ? ` <span class="muted">· ${countryName}</span>` : '';
            row.innerHTML = `${icon('location_on')}<span>${item.city_name}${admin}${country}</span>`;
            row.addEventListener('click', () => {
              input.value = item.city_name;
              box.style.display = 'none';
            });
            box.appendChild(row);
          });
          positionLocBox(box, input);
          box.style.display = 'block';
        } else {
          box.style.display = 'none';
        }
      } catch(e) { box.style.display = 'none'; }
    }, 250);
  });

  window.addEventListener('resize', () => { if (box.style.display === 'block') positionLocBox(box, input); });
  document.addEventListener('scroll', () => { if (box.style.display === 'block') positionLocBox(box, input); }, true);
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !box.contains(e.target)) box.style.display = 'none';
  });
}

async function lookupIco(){
  const ico = document.getElementById('b-ico').value.replace(/\s+/g,'');
  if (!/^\d{6,8}$/.test(ico)) { err(T.requiredIco); return; }
  clearErr();
  const btn = document.getElementById('ico-lookup-btn');
  btn.disabled = true;
  const prevLabel = btn.textContent;
  btn.textContent = '…';
  try {
    const r = await api('api/business.php', { action:'lookup_ico', ico });
    if (r.success && r.company) {
      state.business.ico = ico;
      const c = r.company;
      document.getElementById('b-name').value = c.legal_name || c.owner_name || '';
      document.getElementById('b-address').value = c.address || '';
      document.getElementById('b-city').value = c.city || '';
      const foundEl = document.getElementById('ico-found');
      document.getElementById('ico-found-text').innerHTML = `<b>${c.legal_name || c.owner_name || ico}</b>${[c.address, c.city].filter(Boolean).join(', ')}`;
      foundEl.style.display = 'flex';
    } else {
      err(r.error || T.icoNotFound);
    }
  } catch(e) {
    err(T.networkError);
  } finally {
    btn.disabled = false;
    btn.textContent = prevLabel;
  }
}

async function chooseRole(role){
  if (busy) return;
  busy = true;
  clearErr();
  try {
    const r = await api('api/onboarding.php', { action:'set_role', role });
    if (!r.success) { err(r.message || T.networkError); return; }
    state.role = role;
    flow = role === 'business'
      ? ['role','biz_basics','biz_hours','biz_service','biz_done']
      : ['role','cust_basics','cust_done'];
    idx = 1;
    renderStep();
  } catch (e) {
    err(T.networkError);
  } finally {
    busy = false;
  }
}

async function finish(){
  try {
    const r = await api('api/onboarding.php', { action:'complete' });
    if (r.success && r.redirect_url) {
      window.location.href = r.redirect_url;
      return true;
    }
    err((r && r.message) || T.networkError);
    return false;
  } catch (e) {
    err(T.networkError);
    return false;
  }
}

function renderStep(){
  const key = flow[idx];
  const step = STEPS[key];
  document.getElementById('nextbtn').textContent = <?php echo json_encode(t('Pokračovať')); ?>;
  document.getElementById('nextbtn').style.display = '';
  document.getElementById('backbtn').style.display = idx === 0 ? 'none' : '';
  step.render();
  if (key === 'role') {
    document.getElementById('steplabel').textContent = <?php echo json_encode(t('Začíname')); ?>;
    document.getElementById('fill').style.width = '6%';
  } else {
    document.getElementById('steplabel').textContent = `${idx+1}/${flow.length}`;
    document.getElementById('fill').style.width = `${((idx+1)/flow.length)*100}%`;
  }
}

document.getElementById('backbtn').addEventListener('click', () => {
  if (idx === 0 || busy) return;
  idx -= 1;
  renderStep();
});

document.getElementById('nextbtn').addEventListener('click', async () => {
  if (busy) return;
  const key = flow[idx];
  const step = STEPS[key];
  if (!step.submit) return;
  busy = true;
  const nextBtn = document.getElementById('nextbtn');
  nextBtn.disabled = true;
  clearErr();
  try {
    const ok = await step.submit();
    if (!ok) return;
    if (idx < flow.length - 1) {
      idx += 1;
      renderStep();
    }
  } finally {
    busy = false;
    nextBtn.disabled = false;
  }
});

renderStep();
</script>
</body>
</html>
