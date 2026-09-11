<?php if (!defined('BRAND_NAME')) require_once __DIR__ . '/../includes/branding.php'; ?>
<style>
.pcfg-topbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; background:var(--card-bg); border:1px solid var(--border-color); border-radius:14px; padding:16px 20px; margin-bottom:16px; }
.pcfg-topbar-tier { display:flex; align-items:center; gap:12px; }
.pcfg-topbar-tier-icon { width:40px; height:40px; border-radius:10px; background:var(--input-bg); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pcfg-topbar-mid { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pcfg-tier-pill { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:7px 14px; font-size:12.5px; font-weight:800; border:1.5px solid var(--border-color); background:var(--input-bg); color:var(--text-secondary); cursor:pointer; white-space:nowrap; }
.pcfg-tier-pill.active { background:var(--primary-color); border-color:var(--primary-color); color:#fff; cursor:default; }
.pcfg-tier-pill.locked { opacity:0.75; }
.pcfg-tabs { display:flex; gap:8px; flex-wrap:wrap; background:var(--card-bg); border:1px solid var(--border-color); border-radius:14px; padding:8px; margin-bottom:20px; }
.pcfg-tab { display:flex; align-items:center; gap:7px; padding:10px 14px; border-radius:9px; font-size:13px; font-weight:700; color:var(--text-secondary); cursor:pointer; border:none; background:transparent; white-space:nowrap; }
.pcfg-tab.active { background:var(--primary-color); color:#fff; }
.pcfg-tab .pcfg-tab-req { font-size:10px; font-weight:800; padding:1px 6px; border-radius:6px; background:rgba(0,0,0,0.08); margin-left:2px; }
.pcfg-tab.active .pcfg-tab-req { background:rgba(255,255,255,0.25); }
.pcfg-tab .material-symbols-outlined { font-size:18px; }
.pcfg-layout { display:grid; grid-template-columns: 1fr; gap:20px; align-items:start; }
@media (min-width: 1100px) { .pcfg-layout { grid-template-columns: 1fr 340px; } }
.pcfg-sidebar { display:flex; flex-direction:column; gap:16px; position:sticky; top:20px; }
.pcfg-checklist-item { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 0; border-top:1px solid var(--border-color); font-size:13px; }
.pcfg-checklist-item:first-child { border-top:none; }
.pcfg-lock-card { background:rgba(139,92,246,0.05); border:1.5px dashed rgba(139,92,246,0.3); border-radius:14px; padding:30px 22px; text-align:center; }
.pcfg-preview-frame { border:1px solid var(--border-color); border-radius:14px; overflow:hidden; background:var(--bg-color); }
.pcfg-preview-banner { height:100px; background-size:cover; background-position:center; position:relative; }
.pcfg-preview-avatar { width:52px; height:52px; border-radius:50%; background:var(--input-bg); background-size:cover; background-position:center; border:3px solid var(--card-bg); margin:-30px 0 0 14px; box-shadow:0 2px 6px rgba(0,0,0,0.15); }
.profile-grid > .pcfg-panel { grid-column: 1 / -1; }
</style>
<div id="sec-profile" class="section">
    <div id="public-link-container" style="background: var(--input-bg); padding: 15px 20px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 16px; display: none; align-items:center; justify-content:space-between;">
        <div>
            <strong style="display:block; margin-bottom:5px;">Vaša verejná stránka:</strong>
            <a id="public-link" href="#" target="_blank" style="color: var(--primary-color); font-weight: 600; font-size:16px;"></a>
        </div>
        <a href="#" target="_blank" id="public-link-btn" class="btn-primary" style="text-decoration:none; padding:8px 15px; font-size:14px;">Zobraziť vizitku</a>
    </div>

    <!-- HORNÝ PANEL: aktuálny balík + prepínač náhľadu balíkov -->
    <div class="pcfg-topbar">
        <div class="pcfg-topbar-tier">
            <div class="pcfg-topbar-tier-icon"><span class="material-symbols-outlined" style="color:var(--primary-color);">storefront</span></div>
            <div>
                <div style="font-size:13px; color:var(--text-secondary);">Váš aktuálny balík: <strong id="pcfg-current-tier-name" style="color:var(--text-primary);">START</strong> <span style="background:rgba(16,185,129,0.15); color:#10b981; font-size:11px; font-weight:800; padding:2px 8px; border-radius:6px; margin-left:4px;">Aktívny</span></div>
                <div id="pcfg-current-tier-price" style="font-size:12px; color:var(--text-secondary);"></div>
            </div>
        </div>
        <div class="pcfg-topbar-mid">
            <span style="font-size:12.5px; color:var(--text-secondary); margin-right:4px;">Pozrite sa, čo získate vo vyšších balíkoch:</span>
            <button type="button" class="pcfg-tier-pill" data-tier="free" onclick="ProfileConfigurator.setPreviewTier('free')">FREE</button>
            <button type="button" class="pcfg-tier-pill" data-tier="start" onclick="ProfileConfigurator.setPreviewTier('start')">START</button>
            <button type="button" class="pcfg-tier-pill" data-tier="pro" onclick="ProfileConfigurator.setPreviewTier('pro')">PRO</button>
            <button type="button" class="pcfg-tier-pill" data-tier="vip" onclick="ProfileConfigurator.setPreviewTier('vip')">VIP</button>
        </div>
        <a href="#" target="_blank" id="pcfg-preview-link-btn" class="btn-secondary" style="text-decoration:none; padding:9px 16px; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
            <span class="material-symbols-outlined" style="font-size:16px;">visibility</span> Náhľad profilu
        </a>
    </div>

    <!-- TABY -->
    <div class="pcfg-tabs" id="pcfg-tabs">
        <button type="button" class="pcfg-tab active" data-tab="zakladne" onclick="ProfileConfigurator.showTab('zakladne')"><span class="material-symbols-outlined">badge</span> Základné údaje</button>
        <button type="button" class="pcfg-tab" data-tab="vzhlad" onclick="ProfileConfigurator.showTab('vzhlad')"><span class="material-symbols-outlined">imagesmode</span> Vzhľad</button>
        <button type="button" class="pcfg-tab" data-tab="galeria" onclick="ProfileConfigurator.showTab('galeria')"><span class="material-symbols-outlined">collections</span> Galéria</button>
        <button type="button" class="pcfg-tab" data-tab="zobrazenie" onclick="ProfileConfigurator.showTab('zobrazenie')"><span class="material-symbols-outlined">visibility</span> Zobrazenie stránky</button>
        <button type="button" class="pcfg-tab" data-tab="siete" data-min-tier="1" onclick="ProfileConfigurator.showTab('siete')"><span class="material-symbols-outlined">tune</span> Sociálne siete</button>
        <button type="button" class="pcfg-tab" data-tab="widget" onclick="ProfileConfigurator.showTab('widget')"><span class="material-symbols-outlined">code</span> Widget</button>
        <button type="button" class="pcfg-tab" data-tab="tim" data-min-tier="2" onclick="ProfileConfigurator.showTab('tim')"><span class="material-symbols-outlined">group</span> Tím <span class="pcfg-tab-req">PRO</span></button>
        <button type="button" class="pcfg-tab" data-tab="videa" data-min-tier="2" onclick="ProfileConfigurator.showTab('videa')"><span class="material-symbols-outlined">smart_display</span> Videá <span class="pcfg-tab-req">PRO</span></button>
        <button type="button" class="pcfg-tab" data-tab="ponuky" data-min-tier="2" onclick="ProfileConfigurator.showTab('ponuky')"><span class="material-symbols-outlined">local_offer</span> Ponuky <span class="pcfg-tab-req">PRO</span></button>
        <button type="button" class="pcfg-tab" data-tab="vlastna-url" data-min-tier="3" onclick="ProfileConfigurator.showTab('vlastna-url')"><span class="material-symbols-outlined">link</span> Vlastná URL <span class="pcfg-tab-req">VIP</span></button>
        <button type="button" class="pcfg-tab" data-tab="rezervos-web" data-min-tier="3" onclick="ProfileConfigurator.showTab('rezervos-web')"><span class="material-symbols-outlined">language</span> Rezervos Web <span class="pcfg-tab-req">VIP</span></button>
    </div>

    <div class="pcfg-layout">
    <div class="pcfg-main">
    <form id="profileForm" onsubmit="saveProfile(event)" class="profile-grid">

        <!-- ===================== TAB: ZÁKLADNÉ ÚDAJE ===================== -->
        <div class="pcfg-panel" data-panel="zakladne">
            <div class="vueto-card full-width">
                <div class="vueto-card-header">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">badge</span> Základné informácie</h2>
                </div>
                <div class="vueto-card-body">
                    <div class="form-group">
                        <label>Názov prevádzky</label>
                        <input type="text" id="prof-name" required placeholder="Zadajte názov prevádzky" oninput="ProfileConfigurator.refreshPreview()">
                    </div>
                    <div class="form-group">
                        <label>Hlavná kategória</label>
                        <select id="prof-main-cat" required onchange="onMainCategoryChange(this.value); ProfileConfigurator.refreshPreview();">
                            <option value="" disabled selected>Vyberte hlavnú kategóriu...</option>
                            <option value="Vlasy">Vlasy (Strihy, farbenie, styling)</option>
                            <option value="Holičstvo a Barber">Holičstvo a Barber (Úprava brady, klasické holenie)</option>
                            <option value="Nechty">Nechty (Manikúra, pedikúra, gél)</option>
                            <option value="Starostlivosť o pleť">Starostlivosť o pleť (Čistenie, peeling, masky)</option>
                            <option value="Obočie a riasy">Obočie a riasy (Laminácia, farbenie)</option>
                            <option value="Masáž">Masáž (Relaxačná, thajská, športová)</option>
                            <option value="Make-up">Make-up (Večerný, svadobný, denný)</option>
                            <option value="Wellness a kúpele">Wellness a kúpele (Sauny, vírivky, relax)</option>
                            <option value="Vrkoče a dredy">Vrkoče a dredy (Zapletanie, africké vrkoče)</option>
                            <option value="Tetovanie">Tetovanie (Tetovanie, permanentný make-up)</option>
                            <option value="Lekárska estetika">Lekárska estetika (Botox, výplne, plazma)</option>
                            <option value="Depilácia a epilácia">Depilácia a epilácia (Vosk, laser, cukrová pasta)</option>
                            <option value="Domáce služby">Domáce služby (Služby priamo u vás doma)</option>
                            <option value="Piercing">Piercing (Uši, tvár, telo)</option>
                            <option value="Služby pre miláčikov">Služby pre miláčikov (Strihanie, úprava psov)</option>
                            <option value="Zubné a ortodontické">Zubné a ortodontické (Bielenie, hygiena, rovnátka)</option>
                            <option value="Zdravie a kondícia">Zdravie a kondícia (Tréning, fyzioterapia)</option>
                            <option value="Profesionálne služby">Profesionálne služby (Školenia, poradenstvo)</option>
                            <option value="Solárium a opaľovanie">Solárium a opaľovanie (Solárium, nástreky)</option>
                            <option value="Joga a Pilates">Joga a Pilates (Lekcie, kurzy)</option>
                            <option value="Fyzioterapia">Fyzioterapia (Rehabilitácia, naprávanie)</option>
                            <option value="Osobní tréneri">Osobní tréneri (Fitness, cvičenie na mieru)</option>
                            <option value="Výživové poradenstvo">Výživové poradenstvo (Jedálničky, konzultácie)</option>
                            <option value="Svadobné služby">Svadobné služby (Vlasy, vizáž, balíčky)</option>
                            <option value="Alternatívna medicína">Alternatívna medicína (Akupunktúra, bankovanie)</option>
                            <option value="Psychológia a Terapia">Psychológia a Terapia (Psychológ, logopédia, koučing)</option>
                            <option value="Iné">Iné (Ďalšie špeciálne služby)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="margin-bottom: 10px; display: block;">Doplnkové kategórie (môžete vybrať viaceré)</label>
                        <div class="category-tiles-grid">

                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Vlasy">
                                <div><span class="category-tile-title">Vlasy</span><span class="category-tile-desc">Strihy, farbenie, styling</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Holičstvo a Barber">
                                <div><span class="category-tile-title">Holičstvo a Barber</span><span class="category-tile-desc">Úprava brady, klasické holenie</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Nechty">
                                <div><span class="category-tile-title">Nechty</span><span class="category-tile-desc">Manikúra, pedikúra, gél</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Starostlivosť o pleť">
                                <div><span class="category-tile-title">Starostlivosť o pleť</span><span class="category-tile-desc">Čistenie, peeling, masky</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Obočie a riasy">
                                <div><span class="category-tile-title">Obočie a riasy</span><span class="category-tile-desc">Laminácia, farbenie</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Masáž">
                                <div><span class="category-tile-title">Masáž</span><span class="category-tile-desc">Relaxačná, thajská, športová</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Make-up">
                                <div><span class="category-tile-title">Make-up</span><span class="category-tile-desc">Večerný, svadobný, denný</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Wellness a kúpele">
                                <div><span class="category-tile-title">Wellness a kúpele</span><span class="category-tile-desc">Sauny, vírivky, relax</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Vrkoče a dredy">
                                <div><span class="category-tile-title">Vrkoče a dredy</span><span class="category-tile-desc">Zapletanie, africké vrkoče</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Tetovanie">
                                <div><span class="category-tile-title">Tetovanie</span><span class="category-tile-desc">Tetovanie, permanentný make-up</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Lekárska estetika">
                                <div><span class="category-tile-title">Lekárska estetika</span><span class="category-tile-desc">Botox, výplne, plazma</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Depilácia a epilácia">
                                <div><span class="category-tile-title">Depilácia a epilácia</span><span class="category-tile-desc">Vosk, laser, cukrová pasta</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Domáce služby">
                                <div><span class="category-tile-title">Domáce služby</span><span class="category-tile-desc">Služby priamo u vás doma</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Piercing">
                                <div><span class="category-tile-title">Piercing</span><span class="category-tile-desc">Uši, tvár, telo</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Služby pre miláčikov">
                                <div><span class="category-tile-title">Služby pre miláčikov</span><span class="category-tile-desc">Strihanie, úprava psov</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Zubné a ortodontické">
                                <div><span class="category-tile-title">Zubné a ortodontické</span><span class="category-tile-desc">Bielenie, hygiena, rovnátka</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Zdravie a kondícia">
                                <div><span class="category-tile-title">Zdravie a kondícia</span><span class="category-tile-desc">Tréning, fyzioterapia</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Profesionálne služby">
                                <div><span class="category-tile-title">Profesionálne služby</span><span class="category-tile-desc">Školenia, poradenstvo</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Solárium a opaľovanie">
                                <div><span class="category-tile-title">Solárium a opaľovanie</span><span class="category-tile-desc">Solárium, nástreky</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Joga a Pilates">
                                <div><span class="category-tile-title">Joga a Pilates</span><span class="category-tile-desc">Lekcie, kurzy</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Fyzioterapia">
                                <div><span class="category-tile-title">Fyzioterapia</span><span class="category-tile-desc">Rehabilitácia, naprávanie</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Osobní tréneri">
                                <div><span class="category-tile-title">Osobní tréneri</span><span class="category-tile-desc">Fitness, cvičenie na mieru</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Výživové poradenstvo">
                                <div><span class="category-tile-title">Výživové poradenstvo</span><span class="category-tile-desc">Jedálničky, konzultácie</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Svadobné služby">
                                <div><span class="category-tile-title">Svadobné služby</span><span class="category-tile-desc">Vlasy, vizáž, balíčky</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Alternatívna medicína">
                                <div><span class="category-tile-title">Alternatívna medicína</span><span class="category-tile-desc">Akupunktúra, bankovanie</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" name="prof_cat[]" value="Psychológia a Terapia">
                                <div><span class="category-tile-title">Psychológia a Terapia</span><span class="category-tile-desc">Psychológ, logopédia, koučing</span></div>
                            </label>
                            <label class="category-tile-card">
                                <input type="checkbox" id="cat-other-cb" onchange="document.getElementById('cat-other-wrapper').style.display = this.checked ? 'block' : 'none'">
                                <div><span class="category-tile-title">Iné</span><span class="category-tile-desc">Ďalšie špeciálne služby</span></div>
                            </label>

                        </div>
                        <div id="cat-other-wrapper" style="display: none; margin-top:15px;">
                            <input type="text" id="cat-other-text" placeholder="Zadajte vlastnú kategóriu..." style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="vueto-card">
                <div class="vueto-card-header">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">location_on</span> Lokalita a Kontakt</h2>
                </div>
                <div class="vueto-card-body">
                    <div class="form-group">
                        <label>Mesto (PSČ alebo Názov)</label>
                        <div style="position:relative;">
                            <input type="text" id="prof-city" required autocomplete="off" placeholder="Začnite písať..." oninput="ProfileConfigurator.refreshPreview()">
                            <div id="city-autocomplete" style="display:none; position:absolute; top:100%; left:0; right:0; background:var(--card-bg); border:1px solid var(--border-color); border-radius:8px; max-height:200px; overflow-y:auto; z-index:10; box-shadow: var(--shadow-md);"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Ulica a číslo</label>
                        <input type="text" id="prof-address" required placeholder="Napr. Hlavná 12">
                    </div>
                    <div class="form-group">
                        <label>Telefón</label>
                        <input type="text" id="prof-phone" placeholder="+421 900 000 000" oninput="ProfileConfigurator.refreshPreview()">
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:6px;">
                            <svg style="width:18px; height:18px; flex-shrink:0;" viewBox="0 0 24 24" fill="#25D366">
                                <path d="M12.031 0C5.396 0 .015 5.381.015 12.016c0 2.12.553 4.187 1.604 6.008L0 24l6.168-1.618c1.758.96 3.742 1.466 5.863 1.466 6.635 0 12.016-5.381 12.016-12.016C24.047 5.381 18.666 0 12.031 0zm0 22.016c-1.802 0-3.567-.484-5.105-1.398l-.366-.217-3.791.995 1.012-3.695-.238-.379c-1.006-1.603-1.537-3.469-1.537-5.306 0-5.529 4.498-10.027 10.025-10.027 2.678 0 5.195 1.044 7.089 2.938s2.938 4.411 2.938 7.089c0 5.529-4.498 10.027-10.026 10.027zm5.495-7.509c-.301-.151-1.785-.881-2.062-.982-.277-.101-.479-.151-.681.151s-.782.982-.958 1.183c-.176.202-.353.226-.654.076s-1.272-.469-2.423-1.496c-.896-.799-1.501-1.786-1.677-2.088s-.019-.465.132-.616c.136-.135.301-.353.452-.529s.202-.302.302-.503.05-.377-.025-.528c-.076-.151-.681-1.642-.932-2.25-.245-.592-.494-.511-.681-.52-.176-.008-.378-.01-.58-.01s-.529.076-.806.377c-.277.302-1.058 1.034-1.058 2.521s1.084 2.923 1.235 3.125c.151.202 2.133 3.258 5.168 4.568.721.312 1.284.499 1.724.639.724.23 1.383.198 1.904.12.581-.087 1.785-.73 2.037-1.434s.252-1.308.176-1.434c-.075-.126-.277-.202-.578-.353z"/>
                            </svg>
                            <span>WhatsApp kontakt (číslo alebo odkaz)</span>
                        </label>
                        <input type="text" id="prof-social-ws" placeholder="+421 900 000 000 alebo https://wa.me/421900000000">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Popis prevádzky</label>
                        <textarea id="prof-desc" rows="4" placeholder="Napíšte niečo o vašej prevádzke, čo priláka zákazníkov..." oninput="ProfileConfigurator.refreshPreview()"></textarea>
                    </div>
                </div>
            </div>

            <div class="vueto-card">
                <div class="vueto-card-header">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">settings</span> Rozšírené nastavenia</h2>
                </div>
                <div class="vueto-card-body">
                    <div class="form-group">
                        <label>Režim potvrdzovania rezervácií</label>
                        <select id="prof-conf-mode">
                            <option value="manual">Manuálne (Vyžaduje schválenie)</option>
                            <option value="auto">Automaticky (Ihneď potvrdené)</option>
                        </select>
                        <div id="lock-conf-mode" style="color:#e74c3c; font-size:12px; margin-top:5px; display:none;">
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">lock</span> K dispozícii od balíka START.
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>IBAN pre zálohy (VIP)</label>
                        <input type="text" id="prof-deposit-iban" placeholder="SKXX XXXX XXXX XXXX XXXX XXXX">
                        <div id="lock-deposit-iban" style="color:#e74c3c; font-size:12px; margin-top:5px; display:none;">
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">lock</span> K dispozícii len pre VIP balík.
                        </div>
                    </div>
                </div>
            </div>

            <div class="vueto-card full-width">
                <div class="vueto-card-header">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">schedule</span> Otváracie hodiny</h2>
                </div>
                <div class="vueto-card-body">
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; margin-bottom:6px;">
                        <span></span>
                        <span style="font-size:11px; color:var(--text-secondary); font-weight:700;">Otvorené od</span>
                        <span style="font-size:11px; color:var(--text-secondary); font-weight:700;">Otvorené do</span>
                        <span style="font-size:11px; color:var(--text-secondary); font-weight:700;">Prestávka od</span>
                        <span style="font-size:11px; color:var(--text-secondary); font-weight:700;">Prestávka do</span>
                    </div>
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; align-items:center; margin-bottom:8px;">
                        <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Pondelok</span>
                        <input type="time" id="oh-mon-start" title="Otvorené od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-mon-end" title="Otvorené do" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-mon-break-start" title="Prestávka od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-mon-break-end" title="Prestávka do" style="padding:8px; font-size:12.5px;">
                    </div>
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; align-items:center; margin-bottom:8px;">
                        <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Utorok</span>
                        <input type="time" id="oh-tue-start" title="Otvorené od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-tue-end" title="Otvorené do" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-tue-break-start" title="Prestávka od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-tue-break-end" title="Prestávka do" style="padding:8px; font-size:12.5px;">
                    </div>
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; align-items:center; margin-bottom:8px;">
                        <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Streda</span>
                        <input type="time" id="oh-wed-start" title="Otvorené od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-wed-end" title="Otvorené do" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-wed-break-start" title="Prestávka od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-wed-break-end" title="Prestávka do" style="padding:8px; font-size:12.5px;">
                    </div>
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; align-items:center; margin-bottom:8px;">
                        <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Štvrtok</span>
                        <input type="time" id="oh-thu-start" title="Otvorené od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-thu-end" title="Otvorené do" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-thu-break-start" title="Prestávka od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-thu-break-end" title="Prestávka do" style="padding:8px; font-size:12.5px;">
                    </div>
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; align-items:center; margin-bottom:8px;">
                        <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Piatok</span>
                        <input type="time" id="oh-fri-start" title="Otvorené od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-fri-end" title="Otvorené do" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-fri-break-start" title="Prestávka od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-fri-break-end" title="Prestávka do" style="padding:8px; font-size:12.5px;">
                    </div>
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; align-items:center; margin-bottom:8px;">
                        <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Sobota</span>
                        <input type="time" id="oh-sat-start" title="Otvorené od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-sat-end" title="Otvorené do" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-sat-break-start" title="Prestávka od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-sat-break-end" title="Prestávka do" style="padding:8px; font-size:12.5px;">
                    </div>
                    <div style="display:grid; grid-template-columns: 90px repeat(4, 1fr); gap:8px; align-items:center; margin-bottom:8px;">
                        <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Nedeľa</span>
                        <input type="time" id="oh-sun-start" title="Otvorené od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-sun-end" title="Otvorené do" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-sun-break-start" title="Prestávka od" style="padding:8px; font-size:12.5px;">
                        <input type="time" id="oh-sun-break-end" title="Prestávka do" style="padding:8px; font-size:12.5px;">
                    </div>
                    <p style="font-size:12px; color:var(--text-secondary); margin:8px 0 0;">Nechajte "Otvorené od/do" prázdne, ak je prevádzka v daný deň zatvorená. Prestávka je nepovinná.</p>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: VZHĽAD ===================== -->
        <div class="pcfg-panel" data-panel="vzhlad" style="display:none;">
            <div class="vueto-card full-width">
                <div class="vueto-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">imagesmode</span> Vzhľad a Branding</h2>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button type="button" onclick="document.getElementById('profileForm').requestSubmit()" class="btn-primary" style="padding: 7px 16px; font-size: 12.5px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 3px 10px rgba(176, 128, 66, 0.25);">
                            <span class="material-symbols-outlined" style="font-size: 16px;">save</span>
                            <span>Uložiť profil</span>
                        </button>
                        <span style="font-size:12px; color:var(--text-secondary); font-weight:600;">Váš balík:</span>
                        <span id="prof-branding-tier-badge" style="padding: 4px 10px; border-radius: 8px; font-size: 11.5px; font-weight: 800; background: rgba(176, 128, 66, 0.15); color: var(--primary-color);">VIP (ELITE)</span>
                    </div>
                </div>
                <div class="vueto-card-body">
                    <div id="prof-branding-status-box" style="background: rgba(176, 128, 66, 0.06); border: 1px solid rgba(176, 128, 66, 0.2); border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="material-symbols-outlined" style="font-size: 22px; color: var(--primary-color);">verified</span>
                            <div>
                                <div id="prof-branding-tier-title" style="font-size: 13.5px; font-weight: 800; color: var(--text-primary);">Balík VIP (ELITE) – Kompletný branding aktívny</div>
                                <div id="prof-branding-tier-sub" style="font-size: 12px; color: var(--text-secondary);">Máte plný prístup k nahratiu vlastného loga prevádzky, titulného bannera aj sociálnych sietí.</div>
                            </div>
                        </div>
                        <button type="button" onclick="showSection('billing')" style="background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-primary); padding: 8px 16px; font-size: 12.5px; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='var(--primary-color)'; this.style.color='var(--primary-color)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.color='var(--text-primary)';">
                            <span class="material-symbols-outlined" style="font-size: 16px; color: var(--primary-color);">workspace_premium</span>
                            <span>Zmeniť balík</span>
                        </button>
                    </div>

                    <div class="brand-visuals">
                        <div class="banner-upload" id="banner-bg" style="background-size: cover; background-position: center; background-repeat: no-repeat;">
                            <div id="banner-cat-tag" style="position: absolute; top: 12px; right: 12px; z-index: 2; background: rgba(0,0,0,0.65); backdrop-filter: blur(8px); color: #fff; padding: 5px 12px; border-radius: 8px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(255,255,255,0.2);">
                                <span class="material-symbols-outlined" style="font-size: 15px; color: #f5b041;">auto_awesome</span>
                                <span id="banner-cat-name">Predvolený banner</span>
                            </div>
                            <div style="position: absolute; bottom: 12px; right: 12px; z-index: 2; display: flex; gap: 8px; align-items: center;">
                                <button type="button" id="btn-reset-banner" onclick="resetToDefaultBanner(event)" style="display:none; background: rgba(0,0,0,0.65); backdrop-filter: blur(8px); color: #fff; border: 1px solid rgba(255,255,255,0.25); padding: 7px 12px; border-radius: 8px; font-size: 11.5px; font-weight: 700; cursor: pointer; align-items: center; gap: 5px;" onmouseover="this.style.background='rgba(239,68,68,0.85)';" onmouseout="this.style.background='rgba(0,0,0,0.65)';">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">restart_alt</span>
                                    <span>Resetovať</span>
                                </button>
                                <button type="button" id="btn-change-banner" onclick="handleBannerClick(event)" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); color: #fff; border: 1px solid rgba(255,255,255,0.3); padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='var(--primary-color)';" onmouseout="this.style.background='rgba(0,0,0,0.7)';">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">photo_camera</span>
                                    <span>Zmeniť banner (3:1)</span>
                                </button>
                            </div>
                        </div>
                        <input type="file" id="prof-banner" accept="image/png, image/jpeg, image/webp" style="display:none;" onchange="handleBannerUploadChange(this)">

                        <div class="avatar-upload" id="avatar-bg" style="cursor: pointer;" onclick="handleAvatarClick(event)" title="Kliknite pre nahratie loga salónu">
                            <div class="overlay"><span class="material-symbols-outlined" style="font-size: 24px;">photo_camera</span></div>
                        </div>
                        <input type="file" id="prof-avatar" accept="image/png, image/jpeg, image/webp" style="display:none;" onchange="handleAvatarUploadChange(this)">
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-top:12px;">
                        <span style="font-size:12.5px; color:var(--text-secondary); display:inline-flex; align-items:center; gap:6px;">
                            <span class="material-symbols-outlined" style="font-size:16px; color:var(--primary-color);">info</span>
                            <span id="banner-note-text">Predvolený banner sa nastavuje automaticky podľa kategórie Vašich služieb. Vlastný banner salónu je možné nahrať pri balíkoch PRO a VIP.</span>
                        </span>
                        <p style="font-size:12px; color:var(--text-secondary); margin:0;">Odporúčaný pomer 3:1. Formáty: JPG, PNG, WEBP (Max 4MB).</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: GALÉRIA ===================== -->
        <div class="pcfg-panel" data-panel="galeria" style="display:none;">
            <div class="vueto-card full-width">
                <div class="vueto-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">collections</span> Galéria prevádzky</h2>
                    <span id="gallery-limit-info" style="font-weight:bold; color:var(--text-color);">Limit: <span id="gallery-count">0</span> / <span id="gallery-max">0</span></span>
                </div>
                <div class="vueto-card-body">
                    <p style="font-size:13px; color:var(--text-secondary); margin-top:0;">Pridajte fotky z Vašej prevádzky alebo Vašej práce. Limit závisí od Vášho balíka.</p>
                    <div style="display:flex; gap:15px; margin-bottom:20px;">
                        <label for="gallery-upload" class="btn-primary" id="btn-add-photo" style="padding:10px 20px; cursor:pointer; display:inline-block;">
                            <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:5px;">upload</span> Pridať fotku
                        </label>
                        <input type="file" id="gallery-upload" accept="image/png, image/jpeg, image/webp" multiple style="display:none;" onchange="uploadGalleryImage(this)">
                        <button type="button" class="btn-secondary" onclick="openVideoModal()" id="btn-add-video" style="padding:10px 20px; display:none;">
                            <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:5px;">play_circle</span> Pridať Video (VIP)
                        </button>
                    </div>
                    <div id="gallery-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:15px;"></div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: ZOBRAZENIE STRÁNKY ===================== -->
        <div class="pcfg-panel" data-panel="zobrazenie" style="display:none;">
            <div class="vueto-card full-width">
                <div class="vueto-card-header">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">visibility</span> Zobrazenie stránky</h2>
                </div>
                <div class="vueto-card-body">
                    <p style="font-size:13px; color:var(--text-secondary); margin-top:0;">Vyberte si, ktoré sekcie sa majú zobrazovať na vašej verejnej stránke.</p>

                    <div style="padding:14px 16px; background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:15px; margin-bottom:12px;">
                        <div>
                            <strong style="font-size:14px; color:var(--text-primary);">Fotogaléria priestorov</strong>
                            <span style="font-size:12px; color:var(--text-secondary); display:block; margin-top:2px;">Fotky nahrané v záložke "Galéria".</span>
                        </div>
                        <label class="switch" style="margin:0;">
                            <input type="checkbox" id="show-gallery" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div style="padding:14px 16px; background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:15px; margin-bottom:12px;">
                        <div>
                            <strong style="font-size:14px; color:var(--text-primary);">Video prezentácia</strong>
                            <span style="font-size:12px; color:var(--text-secondary); display:block; margin-top:2px;">Videá nahrané v záložke "Videá" (PRO, VIP).</span>
                        </div>
                        <label class="switch" style="margin:0;">
                            <input type="checkbox" id="show-videos" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div style="padding:14px 16px; background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:15px; margin-bottom:12px;">
                        <div>
                            <strong style="font-size:14px; color:var(--text-primary);">Darčekové poukazy</strong>
                            <span style="font-size:12px; color:var(--text-secondary); display:block; margin-top:2px;">Zákazníci si u vás môžu kúpiť darčekový poukaz priamo na stránke.</span>
                        </div>
                        <label class="switch" style="margin:0;">
                            <input type="checkbox" id="show-gift-vouchers" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div style="padding:14px 16px; background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:15px; margin-bottom:12px;">
                        <div>
                            <strong style="font-size:14px; color:var(--text-primary);">Balíčky a členstvá (permanentky)</strong>
                            <span style="font-size:12px; color:var(--text-secondary); display:block; margin-top:2px;">Ak máte vytvorené permanentky, zobrazia sa na stránke.</span>
                        </div>
                        <label class="switch" style="margin:0;">
                            <input type="checkbox" id="show-packages" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div style="padding:14px 16px; background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:15px;">
                        <div>
                            <strong style="font-size:14px; color:var(--text-primary);">Odber noviniek e-mailom <span id="lock-newsletter-tag" style="font-size:10px; font-weight:800; padding:2px 6px; border-radius:6px; background:rgba(139,92,246,0.15); color:#8b5cf6; margin-left:4px;">PRO, VIP</span></strong>
                            <span style="font-size:12px; color:var(--text-secondary); display:block; margin-top:2px;">Zákazníci vám na stránke zanechajú e-mail a vy im v záložke "Marketing → Novinky prevádzky" môžete posielať novinky a akcie.</span>
                        </div>
                        <label class="switch" style="margin:0;">
                            <input type="checkbox" id="show-newsletter">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div id="lock-newsletter" style="color:#e74c3c; font-size:12px; margin-top:8px; display:none;">
                        <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">lock</span> Zber e-mailových adries je k dispozícii od balíka PRO.
                    </div>
                </div>
            </div>
        </div>
        <!-- ===================== TAB: SOCIÁLNE SIETE ===================== -->
        <div class="pcfg-panel" data-panel="siete" style="display:none;">
            <div class="vueto-card full-width">
                <div class="vueto-card-header">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">tune</span> Sociálne siete a web</h2>
                </div>
                <div class="vueto-card-body">
                    <div id="lock-socials" style="color:#e74c3c; font-size:12px; margin-bottom:15px; display:none;">
                        <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">lock</span> Sociálne siete k dispozícii od balíka PRO.
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="none">
                                <defs><linearGradient id="ig-grad-prof" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></linearGradient></defs>
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke="url(#ig-grad-prof)" stroke-width="2"/>
                                <circle cx="12" cy="12" r="4" stroke="url(#ig-grad-prof)" stroke-width="2"/>
                                <circle cx="18" cy="6" r="1.2" fill="url(#ig-grad-prof)"/>
                            </svg>
                            <input type="text" id="prof-social-ig" placeholder="Instagram (napr. instagram.com/mojaprevadzka alebo @prevadzka)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            <input type="text" id="prof-social-fb" placeholder="Facebook (napr. facebook.com/mojaprevadzka)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="var(--text-primary)"><path d="M16.6 5.82s.51.5 0 0A4.278 4.278 0 0115.54 3h-3.09v12.4a2.592 2.592 0 01-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 004.3 1.38V7.3s-1.88.09-3.24-1.48z"/></svg>
                            <input type="text" id="prof-social-tiktok" placeholder="TikTok (napr. tiktok.com/@mojaprevadzka alebo @prevadzka)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="#FF0000"><path d="M23.5 6.19a3.02 3.02 0 00-2.12-2.14C19.51 3.5 12 3.5 12 3.5s-7.51 0-9.38.55A3.02 3.02 0 00.5 6.19 31.6 31.6 0 000 12a31.6 31.6 0 00.5 5.81 3.02 3.02 0 002.12 2.14C4.49 20.5 12 20.5 12 20.5s7.51 0 9.38-.55a3.02 3.02 0 002.12-2.14A31.6 31.6 0 0024 12a31.6 31.6 0 00-.5-5.81zM9.75 15.57V8.43L15.82 12l-6.07 3.57z"/></svg>
                            <input type="text" id="prof-social-youtube" placeholder="YouTube (napr. youtube.com/@mojaprevadzka)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="#26A5E4"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8l-1.65 7.78c-.12.55-.45.69-.92.43l-2.55-1.88-1.23 1.18c-.14.14-.25.25-.51.25l.18-2.6 4.72-4.27c.21-.18-.04-.29-.32-.1l-5.84 3.68-2.51-.79c-.55-.17-.56-.55.11-.81l9.82-3.78c.46-.17.86.11.7.91z"/></svg>
                            <input type="text" id="prof-social-telegram" placeholder="Telegram (napr. t.me/mojaprevadzka alebo @prevadzka)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="var(--text-primary)"><path d="M18.9 2H22l-7.19 8.21L23.3 22h-6.62l-5.18-6.78L5.5 22H2.4l7.7-8.8L1 2h6.78l4.68 6.2L18.9 2zm-1.16 18h1.83L7.34 3.9H5.38L17.74 20z"/></svg>
                            <input type="text" id="prof-social-x" placeholder="X / Twitter (napr. x.com/mojaprevadzka alebo @prevadzka)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="#0A66C2"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.38-1.85 3.62 0 4.28 2.38 4.28 5.47v6.27zM5.34 7.43a2.06 2.06 0 110-4.12 2.06 2.06 0 010 4.12zM7.12 20.45H3.56V9h3.56v11.45z"/></svg>
                            <input type="text" id="prof-social-linkedin" placeholder="LinkedIn (napr. linkedin.com/company/mojaprevadzka)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="var(--text-secondary)"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                            <input type="email" id="prof-contact-email" placeholder="Kontaktný e-mail (napr. info@mojaprevadzka.sk)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <div style="display:flex; align-items:center; background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:10px; padding:0 12px; gap:10px;">
                            <svg style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 24 24" fill="var(--text-secondary)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                            <input type="text" id="prof-website" placeholder="Webstránka (napr. https://mojaprevadzka.sk)" style="border:none; background:transparent; padding:12px 0; width:100%; color:var(--text-primary); font-size:13px; outline:none; box-shadow:none;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: WIDGET ===================== -->
        <div class="pcfg-panel" data-panel="widget" style="display:none;">
            <div class="vueto-card full-width">
                <div class="vueto-card-header"><h2 class="premium-card-title"><span class="material-symbols-outlined">code</span> Rezervačný widget na váš web</h2></div>
                <div class="vueto-card-body">
                    <p style="font-size:13px; color:var(--text-secondary);">Vložte jednoduchý rezervačný widget priamo na váš vlastný web pomocou krátkeho embed kódu — nájdete ho v sekcii Rozšírenia.</p>
                    <a href="dashboard-rozsirenia.php" class="btn-primary" style="text-decoration:none; padding:10px 20px; display:inline-flex; align-items:center; gap:8px;">
                        <span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span> Prejsť na Rozšírenia
                    </a>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: TÍM (PRO) ===================== -->
        <div class="pcfg-panel" data-panel="tim" style="display:none;">
            <div class="pcfg-lock-card" id="pcfg-lock-tim">
                <span class="material-symbols-outlined" style="font-size:44px; color:#8b5cf6; margin-bottom:8px;">group</span>
                <h3 style="font-size:16px; margin:0 0 6px 0; color:var(--text-primary);">Tím a predstavenie zamestnancov je funkcia balíka PRO a VIP</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin:0 0 16px 0; max-width:550px; margin-left:auto; margin-right:auto;">Ukážte zákazníkom fotky a mená vášho tímu priamo vo verejnom profile.</p>
                <button type="button" onclick="showSection('billing')" class="btn-primary" style="padding:9px 20px; font-size:13px; border-radius:6px; font-weight:700;"><span class="material-symbols-outlined" style="font-size:17px;">workspace_premium</span> Aktivovať balík PRO alebo VIP</button>
            </div>
            <div id="pcfg-unlocked-tim" style="display:none;">
                <div class="vueto-card full-width">
                    <div class="vueto-card-header"><h2 class="premium-card-title"><span class="material-symbols-outlined">group</span> Tím prevádzky</h2></div>
                    <div class="vueto-card-body">
                        <p style="font-size:13px; color:var(--text-secondary);">Správa zamestnancov (fotky, mená, pozície) prebieha v sekcii Tím.</p>
                        <a href="dashboard-tym.php" class="btn-primary" style="text-decoration:none; padding:10px 20px; display:inline-flex; align-items:center; gap:8px;"><span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span> Prejsť na Tím</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: VIDEÁ (PRO) ===================== -->
        <div class="pcfg-panel" data-panel="videa" style="display:none;">
            <div class="vueto-card full-width" id="prof-video-card">
                <div class="vueto-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 class="premium-card-title"><span class="material-symbols-outlined">video_library</span> Video Prezentácia salónu</h2>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:12px; color:var(--text-secondary); font-weight:600;">Dostupné pre:</span>
                        <span style="padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 800; background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">PRO & VIP (2x Vlastné video + 2x YouTube)</span>
                    </div>
                </div>
                <div class="vueto-card-body">
                    <div id="video-tier-locked" style="display:none; background: rgba(139, 92, 246, 0.05); border: 1.5px dashed rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 28px 20px; text-align: center;">
                        <span class="material-symbols-outlined" style="font-size: 44px; color: #8b5cf6; margin-bottom: 8px;">lock</span>
                        <h3 style="font-size: 16px; margin: 0 0 6px 0; color: var(--text-primary);">Video prezentácia je exkluzívna funkcia pre balíky PRO a VIP</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); margin: 0 0 16px 0; max-width: 550px; margin-left: auto; margin-right: auto;">
                            Zvýšte atraktivitu vášho salónu a získajte viac rezervácií pridaním <strong>2 vlastných videí priamo na server</strong> a <strong>2 YouTube videí</strong> do vášho profilu.
                        </p>
                        <button type="button" onclick="showSection('billing')" class="btn-primary" style="padding: 9px 20px; font-size: 13px; border-radius: 6px; font-weight: 700;">
                            <span class="material-symbols-outlined" style="font-size: 17px;">workspace_premium</span> Aktivovať balík PRO alebo VIP
                        </button>
                    </div>

                    <div id="video-tier-active" style="display:block;">
                        <div style="margin-bottom: 22px;">
                            <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Máte k dispozícii <strong>2 sloty na priame nahratie videa zo zariadenia</strong> (MP4, MOV, WEBM) a <strong>2 sloty na YouTube video odkazy</strong>.</p>
                        </div>

                        <div style="margin-bottom: 24px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="material-symbols-outlined" style="color: #10b981; font-size: 20px;">cloud_upload</span>
                                    <h3 style="font-size: 14px; font-weight: 800; margin: 0; color: var(--text-primary);">1. Nahrať priame videá zo zariadenia (Max 2 videá u nás na serveri)</h3>
                                </div>
                                <span style="font-size: 11.5px; font-weight: 700; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 3px 8px; border-radius: 6px;">MP4 / MOV / WEBM max 50 MB</span>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                                <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; display: flex; flex-direction: column;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <span style="font-weight: 700; font-size: 13px; color: var(--text-primary); display: flex; align-items: center; gap: 6px;"><span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">movie</span> 1. Video zo zariadenia</span>
                                        <span id="badge-vidfile-1" style="font-size: 11px; padding: 2px 7px; border-radius: 5px; background: rgba(16,185,129,0.1); color: #10b981; font-weight: 700;">Prázdny</span>
                                    </div>
                                    <input type="file" id="prof-video-file-1" accept="video/mp4,video/webm,video/quicktime" style="display:none;" onchange="handleDirectVideoChange(1, this)">
                                    <div id="dropzone-vidfile-1" onclick="document.getElementById('prof-video-file-1').click()" style="border: 1.5px dashed var(--border-color); border-radius: 8px; padding: 14px; text-align: center; cursor: pointer; background: rgba(16,185,129,0.03);">
                                        <span class="material-symbols-outlined" style="font-size: 26px; color: #10b981; margin-bottom: 2px;">upload_file</span>
                                        <div style="font-size: 12px; font-weight: 700; color: var(--text-primary);" id="label-vidfile-1">Kliknite pre výber videa 1</div>
                                        <div style="font-size: 11px; color: var(--text-secondary);">Max 50 MB</div>
                                    </div>
                                    <div id="preview-wrapper-vidfile-1" style="aspect-ratio: 16/9; max-height: 60vh; width: 100%; border-radius: 8px; overflow: hidden; background: #000; margin-top: 10px; display: none;">
                                        <video id="player-vidfile-1" src="" controls playsinline style="width: 100%; height: 100%; object-fit: contain;" onloadedmetadata="if(this.videoWidth&&this.videoHeight){this.parentElement.style.aspectRatio=this.videoWidth+'/'+this.videoHeight;}"></video>
                                    </div>
                                    <input type="hidden" id="prof-remove-vidfile-1" value="0">
                                    <button type="button" id="btn-remove-vidfile-1" onclick="removeDirectVideo(1)" style="display:none; margin-top:8px; background:transparent; border:1px solid #ef4444; color:#ef4444; padding:5px 10px; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer; align-items:center; justify-content:center; gap:4px;"><span class="material-symbols-outlined" style="font-size: 14px;">delete</span><span>Odstrániť video</span></button>
                                </div>
                                <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; display: flex; flex-direction: column;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <span style="font-weight: 700; font-size: 13px; color: var(--text-primary); display: flex; align-items: center; gap: 6px;"><span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">movie</span> 2. Video zo zariadenia</span>
                                        <span id="badge-vidfile-2" style="font-size: 11px; padding: 2px 7px; border-radius: 5px; background: rgba(16,185,129,0.1); color: #10b981; font-weight: 700;">Prázdny</span>
                                    </div>
                                    <input type="file" id="prof-video-file-2" accept="video/mp4,video/webm,video/quicktime" style="display:none;" onchange="handleDirectVideoChange(2, this)">
                                    <div id="dropzone-vidfile-2" onclick="document.getElementById('prof-video-file-2').click()" style="border: 1.5px dashed var(--border-color); border-radius: 8px; padding: 14px; text-align: center; cursor: pointer; background: rgba(16,185,129,0.03);">
                                        <span class="material-symbols-outlined" style="font-size: 26px; color: #10b981; margin-bottom: 2px;">upload_file</span>
                                        <div style="font-size: 12px; font-weight: 700; color: var(--text-primary);" id="label-vidfile-2">Kliknite pre výber videa 2</div>
                                        <div style="font-size: 11px; color: var(--text-secondary);">Max 50 MB</div>
                                    </div>
                                    <div id="preview-wrapper-vidfile-2" style="aspect-ratio: 16/9; max-height: 60vh; width: 100%; border-radius: 8px; overflow: hidden; background: #000; margin-top: 10px; display: none;">
                                        <video id="player-vidfile-2" src="" controls playsinline style="width: 100%; height: 100%; object-fit: contain;" onloadedmetadata="if(this.videoWidth&&this.videoHeight){this.parentElement.style.aspectRatio=this.videoWidth+'/'+this.videoHeight;}"></video>
                                    </div>
                                    <input type="hidden" id="prof-remove-vidfile-2" value="0">
                                    <button type="button" id="btn-remove-vidfile-2" onclick="removeDirectVideo(2)" style="display:none; margin-top:8px; background:transparent; border:1px solid #ef4444; color:#ef4444; padding:5px 10px; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer; align-items:center; justify-content:center; gap:4px;"><span class="material-symbols-outlined" style="font-size: 14px;">delete</span><span>Odstrániť video</span></button>
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom: 24px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="material-symbols-outlined" style="color: #ff0000; font-size: 20px;">smart_display</span>
                                    <h3 style="font-size: 14px; font-weight: 800; margin: 0; color: var(--text-primary);">2. YouTube video prezentácie (2 YouTube videá)</h3>
                                </div>
                                <span style="font-size: 11.5px; font-weight: 700; color: #ff0000; background: rgba(255, 0, 0, 0.1); padding: 3px 8px; border-radius: 6px;">YouTube link</span>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                                <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; display: flex; flex-direction: column;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;"><span style="font-weight: 700; font-size: 13px; color: var(--text-primary); display: flex; align-items: center; gap: 6px;"><span class="material-symbols-outlined" style="font-size: 18px; color: #ff0000;">play_circle</span> 1. YouTube video</span><span id="badge-vidyt-1" style="font-size: 11px; padding: 2px 7px; border-radius: 5px; background: rgba(255,0,0,0.1); color: #ff0000; font-weight: 700;">Prázdny</span></div>
                                    <div style="margin-bottom: 10px;"><input type="url" id="prof-video-yt-1" placeholder="https://www.youtube.com/watch?v=..." oninput="updateYoutubePreview(1)" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-primary); font-size: 13px;"></div>
                                    <div id="preview-wrapper-vidyt-1" style="aspect-ratio: 16/9; width: 100%; border-radius: 8px; overflow: hidden; background: #000; display: none;"><iframe id="iframe-vidyt-1" src="" style="width: 100%; height: 100%; border: none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
                                    <input type="hidden" id="prof-remove-vidyt-1" value="0">
                                    <button type="button" id="btn-remove-vidyt-1" onclick="removeYoutubeVideo(1)" style="display:none; margin-top:8px; background:transparent; border:1px solid #ef4444; color:#ef4444; padding:5px 10px; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer; align-items:center; justify-content:center; gap:4px;"><span class="material-symbols-outlined" style="font-size: 14px;">delete</span><span>Zmazať YouTube video</span></button>
                                </div>
                                <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; display: flex; flex-direction: column;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;"><span style="font-weight: 700; font-size: 13px; color: var(--text-primary); display: flex; align-items: center; gap: 6px;"><span class="material-symbols-outlined" style="font-size: 18px; color: #ff0000;">play_circle</span> 2. YouTube video</span><span id="badge-vidyt-2" style="font-size: 11px; padding: 2px 7px; border-radius: 5px; background: rgba(255,0,0,0.1); color: #ff0000; font-weight: 700;">Prázdny</span></div>
                                    <div style="margin-bottom: 10px;"><input type="url" id="prof-video-yt-2" placeholder="https://www.youtube.com/watch?v=..." oninput="updateYoutubePreview(2)" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-primary); font-size: 13px;"></div>
                                    <div id="preview-wrapper-vidyt-2" style="aspect-ratio: 16/9; width: 100%; border-radius: 8px; overflow: hidden; background: #000; display: none;"><iframe id="iframe-vidyt-2" src="" style="width: 100%; height: 100%; border: none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
                                    <input type="hidden" id="prof-remove-vidyt-2" value="0">
                                    <button type="button" id="btn-remove-vidyt-2" onclick="removeYoutubeVideo(2)" style="display:none; margin-top:8px; background:transparent; border:1px solid #ef4444; color:#ef4444; padding:5px 10px; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer; align-items:center; justify-content:center; gap:4px;"><span class="material-symbols-outlined" style="font-size: 14px;">delete</span><span>Zmazať YouTube video</span></button>
                                </div>
                            </div>
                        </div>

                        <div style="background: linear-gradient(135deg, rgba(255, 0, 0, 0.05) 0%, rgba(176, 128, 66, 0.08) 100%); border: 1.5px solid rgba(176, 128, 66, 0.25); border-radius: 12px; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 14px; max-width: 680px;">
                                <div style="width: 44px; height: 44px; border-radius: 8px; background: #ff0000; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(255, 0, 0, 0.25);"><span class="material-symbols-outlined" style="font-size: 26px;">cloud_upload</span></div>
                                <div>
                                    <div style="font-size: 14px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span>Nemáte vlastný YouTube kanál? Nahráme video za vás!</span>
                                        <a href="https://www.youtube.com/@volnekreslo" target="_blank" style="font-size: 11px; font-weight: 700; color: #ff0000; background: rgba(255, 0, 0, 0.1); border: 1px solid rgba(255, 0, 0, 0.2); padding: 2px 8px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-outlined" style="font-size: 13px;">smart_display</span> youtube.com/@volnekreslo</a>
                                    </div>
                                    <div style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.4;">Nemusíte sa starať o vytváranie YouTube účtu. Pošlite nám vaše video z telefónu či kamery – my ho profesionálne spracujeme, optimalizujeme a nahráme na náš oficiálny kanál <strong><?= BRAND_NAME ?> (@volnekreslo)</strong> s odkazom priamo do vášho profilu.</div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <a href="https://www.youtube.com/@volnekreslo" target="_blank" class="btn" style="padding: 9px 14px; font-size: 12px; border-radius: 6px; color: #ff0000; border-color: rgba(255, 0, 0, 0.3);"><span class="material-symbols-outlined" style="font-size: 16px;">open_in_new</span><span>Náš kanál</span></a>
                                <button type="button" onclick="openYoutubeHelpModal()" style="background: var(--card-bg); border: 1.5px solid var(--primary-color); color: var(--primary-color); padding: 9px 18px; font-size: 12.5px; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; white-space: nowrap;"><span class="material-symbols-outlined" style="font-size: 17px;">send_to_mobile</span><span>Požiadať o nahratie videa</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: PONUKY (PRO) ===================== -->
        <div class="pcfg-panel" data-panel="ponuky" style="display:none;">
            <div class="pcfg-lock-card" id="pcfg-lock-ponuky">
                <span class="material-symbols-outlined" style="font-size:44px; color:#8b5cf6; margin-bottom:8px;">local_offer</span>
                <h3 style="font-size:16px; margin:0 0 6px 0; color:var(--text-primary);">Last Minute ponuky sú funkcia balíka PRO a VIP</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin:0 0 16px 0; max-width:550px; margin-left:auto; margin-right:auto;">Zľavnite uvoľnené termíny priamo vo verejnom profile a získajte viac rezervácií.</p>
                <button type="button" onclick="showSection('billing')" class="btn-primary" style="padding:9px 20px; font-size:13px; border-radius:6px; font-weight:700;"><span class="material-symbols-outlined" style="font-size:17px;">workspace_premium</span> Aktivovať balík PRO alebo VIP</button>
            </div>
            <div id="pcfg-unlocked-ponuky" style="display:none;">
                <div class="vueto-card full-width">
                    <div class="vueto-card-header"><h2 class="premium-card-title"><span class="material-symbols-outlined">local_offer</span> Last Minute ponuky</h2></div>
                    <div class="vueto-card-body">
                        <p style="font-size:13px; color:var(--text-secondary);">Správa Last Minute ponúk prebieha v samostatnej sekcii.</p>
                        <a href="dashboard-last-minute.php" class="btn-primary" style="text-decoration:none; padding:10px 20px; display:inline-flex; align-items:center; gap:8px;"><span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span> Prejsť na Last Minute</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: VLASTNÁ URL (VIP) ===================== -->
        <div class="pcfg-panel" data-panel="vlastna-url" style="display:none;">
            <div id="premium-lock-container" style="background: rgba(245, 176, 65, 0.1); padding: 20px; border-radius: 12px; border: 1px dashed #f5b041; display: none; align-items: center; gap: 20px; margin-bottom:16px;">
                <span class="material-symbols-outlined" style="font-size: 40px; color: #f5b041;">lock</span>
                <div style="flex:1;">
                    <h3 style="margin: 0 0 5px 0; color: #d68910;">Prémiová funkcia: Verejná Vizitka</h3>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px;">Vaša vlastná webová adresa (<?= BRAND_SITE ?>/@nazov) je dostupná len v prémiovom balíku.</p>
                </div>
                <button type="button" onclick="showSection('billing')" class="btn-primary" style="background: #f5b041; color: #fff; border:none; padding:10px 20px;">Zistiť viac</button>
            </div>
            <div class="vueto-card full-width">
                <div class="vueto-card-header"><h2 class="premium-card-title"><span class="material-symbols-outlined">link</span> Vlastná URL adresa</h2></div>
                <div class="vueto-card-body">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Vlastná URL adresa (PRO, VIP)</label>
                        <div style="display:flex; align-items:center;">
                            <span style="padding:12px; background:var(--input-bg); border:1px solid var(--border-color); border-right:none; color:var(--text-secondary); border-radius:8px 0 0 8px;">/@</span>
                            <input type="text" id="prof-custom-url" style="border-radius:0 8px 8px 0;" placeholder="nazov-prevadzky">
                        </div>
                        <div id="lock-custom-url" style="color:#e74c3c; font-size:12px; margin-top:5px; display:none;">
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">lock</span> K dispozícii od balíka PRO.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB: REZERVOS WEB (VIP) ===================== -->
        <div class="pcfg-panel" data-panel="rezervos-web" style="display:none;">
            <div class="pcfg-lock-card" style="border-color:rgba(176,128,66,0.35); background:rgba(176,128,66,0.05);">
                <span class="material-symbols-outlined" style="font-size:44px; color:var(--primary-color); margin-bottom:8px;">language</span>
                <h3 style="font-size:16px; margin:0 0 6px 0; color:var(--text-primary);">Rezervos Web — vlastná webstránka</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin:0 0 16px 0; max-width:550px; margin-left:auto; margin-right:auto;">Samostatná pripravovaná služba — vlastná profesionálna webstránka pre vašu prevádzku. Pre viac informácií kontaktujte podporu.</p>
                <a href="dashboard-podpora.php" class="btn-primary" style="text-decoration:none; padding:9px 20px; font-size:13px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; gap:6px;"><span class="material-symbols-outlined" style="font-size:17px;">support_agent</span> Kontaktovať podporu</a>
            </div>
        </div>

        <div class="full-width" style="text-align: right; margin-top: 10px;">
            <button type="submit" class="btn-primary" style="padding: 15px 40px; font-size: 16px; box-shadow: 0 4px 15px rgba(176, 128, 66, 0.35);">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 5px;">save</span> Uložiť zmeny
            </button>
        </div>
    </form>
    </div>

    <!-- ===================== PRAVÝ PANEL: checklist + náhľad ===================== -->
    <div class="pcfg-sidebar">
        <div class="vueto-card" style="margin-bottom:0;">
            <div class="vueto-card-header"><h2 class="premium-card-title" style="font-size:14px;"><span class="material-symbols-outlined" style="font-size:18px;">checklist</span> Dokončenie profilu</h2></div>
            <div class="vueto-card-body" style="padding:16px 20px;">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:6px;">
                    <div id="pcfg-completion-pct" style="font-size:28px; font-weight:800; color:var(--primary-color);">0%</div>
                    <div style="font-size:12px; color:var(--text-secondary);">Váš profil je vyplnený na <span id="pcfg-completion-pct-inline">0</span>%</div>
                </div>
                <div id="pcfg-checklist"></div>
            </div>
        </div>

        <div class="vueto-card" style="margin-bottom:0;">
            <div class="vueto-card-header" style="padding:14px 18px;"><h2 class="premium-card-title" style="font-size:14px;"><span class="material-symbols-outlined" style="font-size:18px;">visibility</span> Náhľad vášho profilu</h2></div>
            <div class="vueto-card-body" style="padding:14px;">
                <div class="pcfg-preview-frame">
                    <div class="pcfg-preview-banner" id="pcfg-preview-banner"></div>
                    <div class="pcfg-preview-avatar" id="pcfg-preview-avatar"></div>
                    <div style="padding:8px 14px 14px;">
                        <div id="pcfg-preview-name" style="font-size:15px; font-weight:800; color:var(--text-primary);">Názov prevádzky</div>
                        <div id="pcfg-preview-cat" style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Kategória</div>
                        <div id="pcfg-preview-contact" style="font-size:12px; color:var(--text-secondary); display:flex; flex-direction:column; gap:2px; margin-bottom:8px;"></div>
                        <div id="pcfg-preview-desc" style="font-size:12px; color:var(--text-secondary); line-height:1.4;"></div>
                        <div id="pcfg-preview-upsell" style="display:none; margin-top:10px; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.25); border-radius:8px; padding:8px 10px; font-size:11.5px; color:#8b5cf6;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal: Pridať Video (galéria) -->
    <div id="video-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div class="modal-content" style="background:var(--card-bg); padding:30px; border-radius:12px; max-width:400px; width:90%; position:relative;">
            <h3 style="margin-top:0;">Pridať YouTube/Vimeo Video</h3>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">URL adresa videa <span style="color:red">*</span></label>
                <input type="text" id="gallery-video-url" class="vueto-input" placeholder="Napr. https://youtube.com/watch?v=..." style="width:100%; box-sizing:border-box;">
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:25px;">
                <button onclick="document.getElementById('video-modal').style.display='none'" class="btn-secondary" style="padding:10px 20px;">Zrušiť</button>
                <button onclick="saveGalleryVideo()" class="btn-primary" style="padding:10px 20px;">Uložiť</button>
            </div>
        </div>
    </div>
</div>

<script>
const ProfileConfigurator = (function(){
    const TIERS = ['free', 'start', 'pro', 'vip'];
    const TIER_LABELS = { free: 'FREE', start: 'START', pro: 'PRO', vip: 'VIP' };
    let previewTier = 'free';

    function showTab(tab) {
        document.querySelectorAll('.pcfg-tab').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
        document.querySelectorAll('.pcfg-panel').forEach(p => p.style.display = (p.dataset.panel === tab) ? 'block' : 'none');
    }

    function setPreviewTier(tier) {
        previewTier = tier;
        document.querySelectorAll('.pcfg-tier-pill').forEach(p => p.classList.toggle('active', p.dataset.tier === tier));
        refreshPreview();
    }

    function markCurrentTier(tier) {
        previewTier = tier;
        document.querySelectorAll('.pcfg-tier-pill').forEach(p => {
            const isCurrent = p.dataset.tier === tier;
            p.classList.toggle('active', isCurrent);
            p.innerHTML = TIER_LABELS[p.dataset.tier] + (isCurrent ? ' <span class="material-symbols-outlined" style="font-size:14px;">check</span>' : (TIERS.indexOf(p.dataset.tier) < TIERS.indexOf(tier) ? '' : ' <span class="material-symbols-outlined" style="font-size:14px;">lock</span>'));
        });
    }

    function applyTabLocks(tierLevel) {
        document.querySelectorAll('.pcfg-tab[data-min-tier]').forEach(tab => {
            const req = parseInt(tab.dataset.minTier, 10);
            const locked = tierLevel < req;
            tab.style.opacity = locked ? '0.65' : '1';
        });
        const lockTim = document.getElementById('pcfg-lock-tim'), unlockTim = document.getElementById('pcfg-unlocked-tim');
        const lockPonuky = document.getElementById('pcfg-lock-ponuky'), unlockPonuky = document.getElementById('pcfg-unlocked-ponuky');
        if (lockTim && unlockTim) { const ok = tierLevel >= 2; lockTim.style.display = ok ? 'none' : 'block'; unlockTim.style.display = ok ? 'block' : 'none'; }
        if (lockPonuky && unlockPonuky) { const ok = tierLevel >= 2; lockPonuky.style.display = ok ? 'none' : 'block'; unlockPonuky.style.display = ok ? 'block' : 'none'; }
    }

    function checklistItem(label, ok) {
        return '<div class="pcfg-checklist-item"><span>' + label + '</span>' +
            (ok ? '<span class="material-symbols-outlined" style="color:#10b981; font-size:18px;">check_circle</span>' : '<span class="material-symbols-outlined" style="color:#f59e0b; font-size:18px;">radio_button_unchecked</span>') +
            '</div>';
    }

    function refreshChecklist() {
        const val = id => (document.getElementById(id)?.value || '').trim();
        const items = [
            ['Základné údaje', !!val('prof-name') && !!val('prof-main-cat')],
            ['Kontakt', !!val('prof-city') && !!val('prof-address')],
            ['Popis prevádzky', !!val('prof-desc')],
            ['Galéria (aspoň 1 foto)', document.querySelectorAll('#gallery-grid > *').length > 0],
            ['Otváracie hodiny', !!document.getElementById('oh-mon-start')?.value || !!document.getElementById('oh-tue-start')?.value],
        ];
        const done = items.filter(i => i[1]).length;
        const pct = Math.round((done / items.length) * 100);
        const pctEl = document.getElementById('pcfg-completion-pct'), pctInline = document.getElementById('pcfg-completion-pct-inline');
        if (pctEl) pctEl.innerText = pct + '%';
        if (pctInline) pctInline.innerText = pct;
        const box = document.getElementById('pcfg-checklist');
        if (box) box.innerHTML = items.map(i => checklistItem(i[0], i[1])).join('');
    }

    function refreshPreview() {
        const val = id => (document.getElementById(id)?.value || '').trim();
        const nameEl = document.getElementById('pcfg-preview-name'), catEl = document.getElementById('pcfg-preview-cat'),
              contactEl = document.getElementById('pcfg-preview-contact'), descEl = document.getElementById('pcfg-preview-desc'),
              bannerEl = document.getElementById('pcfg-preview-banner'), avatarEl = document.getElementById('pcfg-preview-avatar'),
              upsellEl = document.getElementById('pcfg-preview-upsell');
        if (nameEl) nameEl.innerText = val('prof-name') || 'Názov prevádzky';
        if (catEl) catEl.innerText = val('prof-main-cat') || 'Kategória';
        if (contactEl) {
            let lines = [];
            if (val('prof-city')) lines.push('📍 ' + val('prof-city'));
            if (val('prof-phone')) lines.push('📞 ' + val('prof-phone'));
            contactEl.innerHTML = lines.map(l => '<span>' + l + '</span>').join('');
        }
        if (descEl) descEl.innerText = val('prof-desc') ? (val('prof-desc').length > 120 ? val('prof-desc').substring(0, 120) + '…' : val('prof-desc')) : '';
        const bg = document.getElementById('banner-bg'); if (bannerEl && bg && bg.style.backgroundImage) bannerEl.style.backgroundImage = bg.style.backgroundImage;
        const av = document.getElementById('avatar-bg'); if (avatarEl && av && av.style.backgroundImage) avatarEl.style.backgroundImage = av.style.backgroundImage;

        const realTierIdx = TIERS.indexOf((window.g_profileTier || 'free').toLowerCase());
        const previewIdx = TIERS.indexOf(previewTier);
        if (upsellEl) {
            if (previewIdx > realTierIdx) {
                const extras = {
                    start: 'Vlastné logo prevádzky',
                    pro: 'Titulný banner, Tím, Videá, Last Minute ponuky',
                    vip: 'Neobmedzené rezervácie, vlastná URL, zálohy cez IBAN'
                };
                upsellEl.style.display = 'block';
                upsellEl.innerHTML = '<strong>V balíku ' + TIER_LABELS[previewTier] + ' navyše:</strong> ' + (extras[previewTier] || '');
            } else {
                upsellEl.style.display = 'none';
            }
        }
    }

    function initTierUI(tier) {
        markCurrentTier(tier);
        const nameEl = document.getElementById('pcfg-current-tier-name');
        if (nameEl) nameEl.innerText = TIER_LABELS[tier] || tier.toUpperCase();
        applyTabLocks(TIERS.indexOf(tier));
        refreshPreview();
        refreshChecklist();
    }

    document.addEventListener('input', function(e){ if (e.target && e.target.closest && e.target.closest('#profileForm')) refreshChecklist(); });

    return { showTab, setPreviewTier, refreshPreview, refreshChecklist, initTierUI };
})();
window.ProfileConfigurator = ProfileConfigurator;
</script>
