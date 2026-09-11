# -*- coding: utf-8 -*-
"""
Generator for c:/VolneKreslo/zakaznik/index.html
Complete User Manual for Customers (Zákaznícky Manuál Rezervos)
Strictly ZERO emojis - only Google Material Symbols Outlined.
Rezervos Gold Brand palette.
"""

import os
import re

html_content = """<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manuál Rezervos Zákazník | Používateľský sprievodca a funkcie klientskeho portálu</title>
  <meta name="description" content="Kompletný vizuálny návod a manuál pre zákaznícky portál Rezervos.eu. Ako spravovať rezervácie, permanentky, vernostné body, kreslo hunter a platby.">
  
  <!-- Fonts & Material Symbols from Rezervos.eu -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- Top Progress Bar -->
  <div id="progress-bar"></div>

  <!-- Mobile Sidebar Backdrop -->
  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

  <!-- Main Header -->
  <header class="top-header">
    <div class="brand-wrapper">
      <button class="mobile-nav-toggle" id="mobile-toggle" title="Otvoriť menu">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <div class="brand-logo-icon">R</div>
      <div class="brand-info">
        <h1>Rezervos Zákazník</h1>
        <span class="brand-sub">Kompletný sprievodca klientskym portálom</span>
      </div>
    </div>

    <div class="header-actions">
      <a href="https://rezervos.eu/moj_profil.php" target="_blank" rel="noopener noreferrer" class="btn-header btn-primary" title="Prihlásiť sa na Rezervos.eu">
        <span class="btn-header-text">Klientsky portál</span>
        <span class="material-symbols-outlined" style="font-size: 18px;">open_in_new</span>
      </a>
      <button class="theme-toggle-btn" id="theme-toggle" title="Prepnúť tmavý/svetlý režim">
        <span class="material-symbols-outlined">dark_mode</span>
      </button>
    </div>
  </header>

  <!-- Layout Container -->
  <div class="layout-container">
    
    <!-- Left Sticky Sidebar Navigation -->
    <aside class="sidebar">
      <div class="sidebar-mobile-header">
        <div class="sidebar-mobile-brand">
          <div class="brand-logo-icon" style="width: 32px; height: 32px; font-size: 15px;">R</div>
          <span style="font-weight: 700; font-size: 15px;">Zákaznícke Menu</span>
        </div>
        <button class="sidebar-mobile-close" id="sidebar-close" title="Zavrieť menu">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="sidebar-search-box">
        <span class="material-symbols-outlined search-icon">search</span>
        <input type="text" id="search-input" placeholder="Hľadať funkciu, nastavenie..." autocomplete="off">
      </div>

      <div class="sidebar-nav-group">
        <div class="nav-section-title">
          <span class="material-symbols-outlined">menu_open</span>
          <span>Bočné Menu & Navigácia</span>
        </div>
        <ul class="sidebar-nav-list">
          <li class="sidebar-nav-item active">
            <a href="#sec-00">
              <span class="material-symbols-outlined nav-icon">menu_open</span>
              <span>Bočné menu zákazníka</span>
              <span class="nav-badge-num">00</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-nav-group">
        <div class="nav-section-title">
          <span class="material-symbols-outlined">calendar_month</span>
          <span>Termíny & Rezervácie</span>
        </div>
        <ul class="sidebar-nav-list">
          <li class="sidebar-nav-item">
            <a href="#sec-01">
              <span class="material-symbols-outlined nav-icon">dashboard</span>
              <span>Prehľad zákazníka</span>
              <span class="nav-badge-num">01</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-02">
              <span class="material-symbols-outlined nav-icon">event_available</span>
              <span>Moje termíny a história</span>
              <span class="nav-badge-num">02</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-nav-group">
        <div class="nav-section-title">
          <span class="material-symbols-outlined">card_membership</span>
          <span>Permanentky & Poukazy</span>
        </div>
        <ul class="sidebar-nav-list">
          <li class="sidebar-nav-item">
            <a href="#sec-03">
              <span class="material-symbols-outlined nav-icon">loyalty</span>
              <span>Moje permanentky</span>
              <span class="nav-badge-num">03</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-04">
              <span class="material-symbols-outlined nav-icon">featured_seasonal_and_gifts</span>
              <span>Darčekové poukazy</span>
              <span class="nav-badge-num">04</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-nav-group">
        <div class="nav-section-title">
          <span class="material-symbols-outlined">military_tech</span>
          <span>Vernostný program & Zľavy</span>
        </div>
        <ul class="sidebar-nav-list">
          <li class="sidebar-nav-item">
            <a href="#sec-05">
              <span class="material-symbols-outlined nav-icon">stars</span>
              <span>Vernostný program</span>
              <span class="nav-badge-num">05</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-06">
              <span class="material-symbols-outlined nav-icon">campaign</span>
              <span>Novinky od prevádzok</span>
              <span class="nav-badge-num">06</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-07">
              <span class="material-symbols-outlined nav-icon">notifications_active</span>
              <span>Kreslo Hunter (Lovec)</span>
              <span class="nav-badge-num">07</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-nav-group">
        <div class="nav-section-title">
          <span class="material-symbols-outlined">storefront</span>
          <span>Salóny & Recenzie</span>
        </div>
        <ul class="sidebar-nav-list">
          <li class="sidebar-nav-item">
            <a href="#sec-08">
              <span class="material-symbols-outlined nav-icon">favorite</span>
              <span>Obľúbené salóny</span>
              <span class="nav-badge-num">08</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-09">
              <span class="material-symbols-outlined nav-icon">rate_review</span>
              <span>Moje recenzie</span>
              <span class="nav-badge-num">09</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-15">
              <span class="material-symbols-outlined nav-icon">travel_explore</span>
              <span>Ako nájsť salón & rezervovať</span>
              <span class="nav-badge-num">15</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-nav-group">
        <div class="nav-section-title">
          <span class="material-symbols-outlined">payments</span>
          <span>Financie, Inzercia, Provízie</span>
        </div>
        <ul class="sidebar-nav-list">
          <li class="sidebar-nav-item">
            <a href="#sec-10">
              <span class="material-symbols-outlined nav-icon">store</span>
              <span>Moje inzeráty</span>
              <span class="nav-badge-num">10</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-11">
              <span class="material-symbols-outlined nav-icon">account_balance_wallet</span>
              <span>Klientska Peňaženka</span>
              <span class="nav-badge-num">11</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-12">
              <span class="material-symbols-outlined nav-icon">group_add</span>
              <span>Affiliate program</span>
              <span class="nav-badge-num">12</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-nav-group">
        <div class="nav-section-title">
          <span class="material-symbols-outlined">manage_accounts</span>
          <span>Profil & Bezpečnosť</span>
        </div>
        <ul class="sidebar-nav-list">
          <li class="sidebar-nav-item">
            <a href="#sec-13">
              <span class="material-symbols-outlined nav-icon">badge</span>
              <span>Nastavenia profilu & VIP</span>
              <span class="nav-badge-num">13</span>
            </a>
          </li>
          <li class="sidebar-nav-item">
            <a href="#sec-14">
              <span class="material-symbols-outlined nav-icon">security</span>
              <span>Zabezpečenie & 2FA</span>
              <span class="nav-badge-num">14</span>
            </a>
          </li>
        </ul>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">

      <!-- Hero Banner -->
      <section class="hero-banner">
        <div class="hero-badge-tag">
          <span class="material-symbols-outlined">verified</span>
          <span>Oficiálny Sprievodca Klientskym Portálom</span>
        </div>
        <h1 class="hero-title">Ako efektívne používať zákaznícky účet Rezervos</h1>
        <p class="hero-subtitle">
          Detailný manuál a vizuálny sprievodca pre zákazníkov. V tomto návode nájdete presný popis všetkých 16 klientskych modulov, HD screenshoty rozhrania a postupy krok za krokom pre rezervácie, permanentky, vouchery a Kreslo Hunter.
        </p>

        <div class="hero-stats-bar">
          <div class="stat-box">
            <span class="stat-value">16</span>
            <span class="stat-label">Modulov &amp; sekcií</span>
          </div>
          <div class="stat-box">
            <span class="stat-value">19</span>
            <span class="stat-label">HD Screenshotov systému</span>
          </div>
          <div class="stat-box">
            <span class="stat-value">100%</span>
            <span class="stat-label">Overené v zákazníckej zóne</span>
          </div>
          <div class="stat-box">
            <span class="stat-value">&lt; 60 s</span>
            <span class="stat-label">Priemerný čas rezervácie</span>
          </div>
        </div>
      </section>

      <!-- Category Filter Pills -->
      <div class="filter-pills-bar">
        <button class="filter-pill active" data-category="all">
          <span class="material-symbols-outlined">apps</span>
          <span>Všetky funkcie</span>
        </button>
        <button class="filter-pill" data-category="menu">
          <span class="material-symbols-outlined">menu_open</span>
          <span>Menu &amp; Navigácia</span>
        </button>
        <button class="filter-pill" data-category="terminy">
          <span class="material-symbols-outlined">calendar_month</span>
          <span>Termíny &amp; Rezervácie</span>
        </button>
        <button class="filter-pill" data-category="clenske">
          <span class="material-symbols-outlined">card_membership</span>
          <span>Permanentky &amp; Poukazy</span>
        </button>
        <button class="filter-pill" data-category="benefity">
          <span class="material-symbols-outlined">military_tech</span>
          <span>Vernostný program &amp; Zľavy</span>
        </button>
        <button class="filter-pill" data-category="oblubene">
          <span class="material-symbols-outlined">storefront</span>
          <span>Salóny &amp; Recenzie</span>
        </button>
        <button class="filter-pill" data-category="financie">
          <span class="material-symbols-outlined">payments</span>
          <span>Financie &amp; Inzercia</span>
        </button>
        <button class="filter-pill" data-category="profil">
          <span class="material-symbols-outlined">manage_accounts</span>
          <span>Profil &amp; Bezpečnosť</span>
        </button>
      </div>

      <!-- SECTION 00: Bočné menu zákazníka -->
      <section class="guide-section" id="sec-00" data-category="menu">
        <div class="section-header-card">
          <div class="section-num-badge">00</div>
          <div class="section-header-text">
            <h2>Bočné Navigačné Menu Zákazníka</h2>
            <p class="section-subtitle">Porovnanie zbaleného a roztiahnutého stavu klientskeho menu s kompletným zoznamom sekcií</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Prístup:</span>
              <span class="meta-val">Vždy prítomné na ľavej strane obrazovky po prihlásení zákazníka</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">view_sidebar</span>
              <span class="meta-label">Režimy:</span>
              <span class="meta-val">Zbalený stav (64px) &amp; Roztiahnutý stav (250px)</span>
            </div>
          </div>

          <p class="section-text-lead">
            Klientske rozhranie Rezervos využíva moderné plávajúce bočné menu (sidebar). Na veľkých obrazovkách zostáva v kompaktnej šírke 64 pixelov pre maximálny priestor na obsah. Hneď ako naň prejdete myšou (alebo kliknete), plynule sa roztiahne na 250 pixelov a odhalí plné slovenské názvy všetkých klientskych modulov.
          </p>

          <!-- Menu Comparison View (Exactly like Prevadzka) -->
          <div class="menu-compare-grid">
            <div class="menu-card">
              <div class="menu-card-header">
                <span>Celkový pohľad na systém s roztiahnutým menu</span>
                <span class="menu-card-badge">Šírka 250 px</span>
              </div>
              <div class="screenshot-container" data-caption="Zákaznícky portál Rezervos s plne roztiahnutým bočným menu (250 px)">
                <img loading="eager" src="images/00_menu_expanded.png" alt="Rezervos s roztiahnutým bočným menu">
                <div class="screenshot-zoom-hint">
                  <span class="material-symbols-outlined">zoom_in</span>
                  <span>Kliknite pre zväčšenie celkového pohľadu</span>
                </div>
              </div>
            </div>

            <div class="menu-card">
              <div class="menu-card-header">
                <span>Detail všetkých položiek menu</span>
                <span class="menu-card-badge">Zákaznícke moduly</span>
              </div>
              <div class="screenshot-container" data-caption="Detail roztiahnutého bočného menu zákazníka so všetkými položkami">
                <img loading="eager" src="images/00_sidebar_detail.png" alt="Detail položiek bočného menu zákazníka">
                <div class="screenshot-zoom-hint">
                  <span class="material-symbols-outlined">zoom_in</span>
                  <span>Kliknite pre zväčšenie detailu menu</span>
                </div>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">dashboard</span>
                <h3>Hlavný prehľad &amp; Termíny</h3>
              </div>
              <p>Rýchly prístup k uvítacej tabuli, odpočítavaniu najbližšej návštevy a kalendáru vašich budúcich aj minulých rezervácií.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">card_membership</span>
                <h3>Karty, Poukazy, Body</h3>
              </div>
              <p>Správa predplatených permanentiek, darčekových poukazov s kreditom a nazbieraných pečiatok vo vernostnom programe.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">account_balance_wallet</span>
                <h3>Peňaženka, Inzercia &amp; Hunter</h3>
              </div>
              <p>Prehľad zostatku peňaženky, lovec voľných termínov Kreslo Hunter, podávanie inzerátov a odporúčací affiliate program.</p>
            </div>
          </div>

          <div class="alert-box alert-tip">
            <span class="material-symbols-outlined alert-icon">lightbulb</span>
            <div class="alert-content">
              <strong>Tip pre mobilné zariadenia:</strong> Na smartfónoch a tabletoch sa menu zobrazuje ako vysúvací panel cez ikonu v ľavom hornom rohu hlavičky. Po kliknutí na položku sa panel automaticky zavrie a presmeruje vás na požadovanú sekciu.
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 01: Prehľad zákazníka -->
      <section class="guide-section" id="sec-01" data-category="terminy">
        <div class="section-header-card">
          <div class="section-num-badge">01</div>
          <div class="section-header-text">
            <h2>Prehľad Zákazníka (Klientsky Dashboard)</h2>
            <p class="section-subtitle">Vstupná obrazovka po prihlásení s odpočítavaním najbližšieho termínu a kľúčovými štatistikami</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Prehľad (záložka #dashboard)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">badge</span>
              <span class="meta-label">Účel:</span>
              <span class="meta-val">Okamžitý sumár termínov, klientskych výhod a rýchle akcie</span>
            </div>
          </div>

          <p class="section-text-lead">
            Po prihlásení do klientskej zóny vás privíta prehľadná uvítacia obrazovka s vaším menom a profilovým statusom. Hlavným prvkom je informačný panel s vaším <strong>najbližším plánovaným termínom</strong>, vďaka čomu presne viete, do akého salónu a v aký čas máte prísť.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">dashboard</span>
              <span>Pohľad na zákaznícky Prehľad (Dashboard)</span>
            </div>
            <div class="screenshot-container" data-caption="Klientsky Prehľad - uvítanie, najbližšia rezervácia a rýchle odkazy">
              <img src="images/01_prehlad.png" alt="Prehľad zákazníka Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">schedule</span>
                <h3>Najbližší termín</h3>
              </div>
              <p>Zobrazuje presný dátum, čas, názov salónu, objednanú službu, priradeného pracovníka a odkaz na stiahnutie do kalendára.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">savings</span>
                <h3>Stav peňaženky &amp; bodov</h3>
              </div>
              <p>Okamžitý zostatok vášho predplateného kreditu a nazbierané vernostné body z vašich doterajších návštev.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">touch_app</span>
                <h3>Rýchle odkazy</h3>
              </div>
              <p>Tlačidlá pre okamžitý skok do katalógu salónov, dobitie kreditu alebo správu osobných údajov a hesla.</p>
            </div>
          </div>

          <div class="steps-card">
            <h3>Čo nájdete na paneli najbližšieho termínu:</h3>
            <div class="step-item">
              <div class="step-number">1</div>
              <div class="step-desc">
                <strong>Názov a adresa salónu:</strong> Kliknutím na adresu sa otvorí Google Maps s navigáciou priamo pred dvere prevádzky.
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">2</div>
              <div class="step-desc">
                <strong>Objednané procedúry:</strong> Presný zoznam položiek, celková dĺžka trvania v minútach a konečná cena.
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">3</div>
              <div class="step-desc">
                <strong>Možnosť zmeny či zrušenia:</strong> Ak vám termín nevyhovuje, môžete ho zrušiť priamo z dashboardu v súlade so storno podmienkami salónu.
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 02: Moje termíny -->
      <section class="guide-section" id="sec-02" data-category="terminy">
        <div class="section-header-card">
          <div class="section-num-badge">02</div>
          <div class="section-header-text">
            <h2>Moje Termíny &amp; História Rezervácií</h2>
            <p class="section-subtitle">Kompletný archív vašich nadchádzajúcich aj ukončených návštev s možnosťou preobjednania</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Moje termíny (záložka #bookings)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">history</span>
              <span class="meta-label">Pohľady:</span>
              <span class="meta-val">Nadchádzajúce termíny &amp; História termínov</span>
            </div>
          </div>

          <p class="section-text-lead">
            V sekcii <strong>Moje termíny</strong> máte 100% kontrolu nad svojím harmonogramom návštev. Nemusíte hľadať potvrdzujúce e-maily ani SMS správy — všetky rezervácie sú usporiadané chronologicky s detailnými údajmi o salóne, cene a poskytovateľovi služby.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">event_note</span>
              <span>Prehľad rezervácií a história návštev</span>
            </div>
            <div class="screenshot-container" data-caption="Moje termíny - karta rezervácie so stavom, cenou a možnosťou správy">
              <img src="images/02_moje_terminy.png" alt="Moje termíny Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-2">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">event_upcoming</span>
                <h3>Nadchádzajúce rezervácie</h3>
              </div>
              <p>Zoznam termínov, ktoré vás ešte len čakajú. Pri každom vidíte status (Potvrdená / Čaká na potvrdenie), presný čas a personál. Môžete si termín stiahnuť do kalendára v mobile (.ics / Google Calendar) alebo požiadať o storno.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">history_edu</span>
                <h3>História návštev</h3>
              </div>
              <p>Archív všetkých vašich minulých procedúr. Ak ste boli s účesom alebo masážou mimoriadne spokojní, tlačidlom <strong>„Objednať znova“</strong> si rezervujete rovnakú službu na 1 kliknutie bez nového vyhľadávania.</p>
            </div>
          </div>

          <div class="alert-box alert-info">
            <span class="material-symbols-outlined alert-icon">info</span>
            <div class="alert-content">
              <strong>Storno podmienky salónov:</strong> Každá prevádzka si nastavuje vlastnú lehotu bezplatného storna (napríklad najneskôr 24 hodín vopred). Tlačidlo na zrušenie termínu je aktívne v závislosti od pravidiel konkrétneho salónu.
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 03: Permanentky a členstvá -->
      <section class="guide-section" id="sec-03" data-category="clenske">
        <div class="section-header-card">
          <div class="section-num-badge">03</div>
          <div class="section-header-text">
            <h2>Moje Permanentky &amp; Členstvá</h2>
            <p class="section-subtitle">Digitálna evidencia vstupových a časových permanentiek s odpočítavaním zostatku</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Moje permanentky (záložka #memberships)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">qr_code</span>
              <span class="meta-label">Identifikácia:</span>
              <span class="meta-val">Unikátny kód karty / QR kód pre rýchle uplatnenie na recepcii</span>
            </div>
          </div>

          <p class="section-text-lead">
            Už so sebou nemusíte nosiť papierové kartičky ani pečiatkové preukazy. Všetky zakúpené permanentky (napríklad 10 vstupov na strih, 5 masáží alebo mesačné členstvo) máte bezpečne uložené vo svojom profile s automatickým odpočítavaním čerpania.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">card_membership</span>
              <span>Prehľad digitálnych permanentiek</span>
            </div>
            <div class="screenshot-container" data-caption="Permanentky - grafický ukazovateľ vyčerpaných vstupov a platnosť karty">
              <img src="images/03_permanentky.png" alt="Moje permanentky Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">pin</span>
                <h3>Kód a identifikátor</h3>
              </div>
              <p>Každá permanentka má jedinečný kód, ktorý stačí nahlásiť personálu, alebo si ho systém automaticky priradí pri online rezervácii.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">data_usage</span>
                <h3>Zostatok vstupov</h3>
              </div>
              <p>Vizuálny ukazovateľ (napr. 7 z 10 vstupov voľných). Presne viete, koľko návštev vám ešte zostáva.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">event_busy</span>
                <h3>Dátum expirácie</h3>
              </div>
              <p>Zreteľné zobrazenie platnosti permanentky (napr. platná do 31.12.2026), aby vám žiadny predplatený vstup neprepadol.</p>
            </div>
          </div>

          <div class="steps-card">
            <h3>Ako uplatniť permanentku pri rezervácii:</h3>
            <div class="step-item">
              <div class="step-number">1</div>
              <div class="step-desc">
                <strong>Výber služby:</strong> Pri objednávaní v salóne, kde máte zakúpenú permanentku, si vyberte príslušnú službu.
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">2</div>
              <div class="step-desc">
                <strong>Automatické prepojenie:</strong> Ak ste prihlásený, systém vám v kroku platby automaticky ponúkne uplatnenie vstupovej permanentky (cena za termín bude 0 €).
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">3</div>
              <div class="step-desc">
                <strong>Odpis vstupu:</strong> Po absolvovaní termínu vám personál salónu odpočíta 1 vstup a zostatok sa okamžite aktualizuje vo vašom profile.
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 04: Darčekové poukazy -->
      <section class="guide-section" id="sec-04" data-category="clenske">
        <div class="section-header-card">
          <div class="section-num-badge">04</div>
          <div class="section-header-text">
            <h2>Darčekové Poukazy &amp; Vouchery</h2>
            <p class="section-subtitle">Evidencia kódov poukazov, zostatku kreditu a možnosť darovania či stiahnutia v PDF</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Darčekové poukazy (záložka #giftvouchers)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">euro</span>
              <span class="meta-label">Typy:</span>
              <span class="meta-val">Hodnotové (finančný kredit) alebo Službové (konkrétna procedúra)</span>
            </div>
          </div>

          <p class="section-text-lead">
            Dostali ste od blízkych darčekový poukaz na návštevu salónu, alebo ste si sami zakúpili voucher? V tejto sekcii nájdete všetky aktívne poukazy, ich nominálnu hodnotu, aktuálny nevyčerpaný zostatok a dátum platnosti.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">featured_seasonal_and_gifts</span>
              <span>Zoznam darčekových poukazov zákazníka</span>
            </div>
            <div class="screenshot-container" data-caption="Darčekové poukazy - zobrazenie kódu, zostatku v eurách a platnosti">
              <img src="images/04_darcekove_poukazy.png" alt="Darčekové poukazy Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-2">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">receipt_long</span>
                <h3>Postupné čerpanie kreditu</h3>
              </div>
              <p>Pokiaľ máte napríklad 100 € poukaz a vaša procedúra stála 40 €, zvyšných 60 € vám zostáva k dispozícii na vašu ďalšiu návštevu. Systém si pamätá presný zostatok po každej transakcii.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">picture_as_pdf</span>
                <h3>Tlač a stiahnutie v PDF</h3>
              </div>
              <p>Ak ste kúpili poukaz pre niekoho iného, môžete si ho jedným kliknutím stiahnuť v reprezentatívnom grafickom PDF formáte, vytlačiť alebo poslať obdarovanému e-mailom.</p>
            </div>
          </div>

          <div class="alert-box alert-tip">
            <span class="material-symbols-outlined alert-icon">redeem</span>
            <div class="alert-content">
              <strong>Ako uplatniť kód poukazu:</strong> Pri dokončovaní online rezervácie na webe Rezervos vložte alfanumerický kód do poľa <em>„Mám darčekový poukaz / zľavový kód“</em>. Hodnota sa okamžite odpočíta z konečnej sumy objednávky.
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 05: Vernostný program -->
      <section class="guide-section" id="sec-05" data-category="benefity">
        <div class="section-header-card">
          <div class="section-num-badge">05</div>
          <div class="section-header-text">
            <h2>Vernostný Program &amp; Klientske Odmeny</h2>
            <p class="section-subtitle">Zbieranie bodov za každú návštevu, pečiatkové karty a odomykanie zliav</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Vernostný program (záložka #loyalty)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">award_star</span>
              <span class="meta-label">Princíp:</span>
              <span class="meta-val">Automatické pripisovanie bodov a pečiatok po ukončení termínu</span>
            </div>
          </div>

          <p class="section-text-lead">
            Rezervos odmeňuje vašu vernosť. Za každú absolvovanú návštevu zapojených prevádzok automaticky získavate vernostné body alebo digitálne pečiatky, ktoré môžete následne premeniť na zľavy, darčekové procedúry či špeciálne balíčky.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">military_tech</span>
              <span>Stav vernostných bodov a odomknuté odmeny</span>
            </div>
            <div class="screenshot-container" data-caption="Vernostný program - zoznam salónov s vašimi bodmi a ponuka odmien">
              <img src="images/05_vernostny_program.png" alt="Vernostný program Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">toll</span>
                <h3>Bodové konto podľa salónov</h3>
              </div>
              <p>Máte prehľadný rozpis bodov pre každú navštevovanú prevádzku zvlášť. Vidíte, koľko bodov ste získali a koľko vám chýba k ďalšej úrovni.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">workspace_premium</span>
                <h3>Katalóg dostupných odmien</h3>
              </div>
              <p>Zoznam benefitov pripravených na výmenu (napríklad: 50 bodov = 10% zľava, 100 bodov = strih zdarma, darčekové vlasové sérum).</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">auto_awesome</span>
                <h3>Automatické pripísanie</h3>
              </div>
              <p>Nemusíte si pýtať pečiatku na pokladni. Akonáhle salón označí vašu rezerváciu ako vybavenú, body sa na váš účet pripíšu okamžite.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 06: Novinky od prevádzok -->
      <section class="guide-section" id="sec-06" data-category="benefity">
        <div class="section-header-card">
          <div class="section-num-badge">06</div>
          <div class="section-header-text">
            <h2>Novinky &amp; Akcie Od Prevádzok</h2>
            <p class="section-subtitle">Exkluzívny kanál sezónnych ponúk, zliav a dôležitých oznamov z vašich salónov</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Novinky od prevádzok (záložka #newsletters)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">notifications</span>
              <span class="meta-label">Obsah:</span>
              <span class="meta-val">Limitované akcie, nové procedúry, oznamy o dovolenkách</span>
            </div>
          </div>

          <p class="section-text-lead">
            V tejto sekcii nájdete personalizovaný informačný kanál od salónov, ktoré ste v minulosti navštívili alebo si ich uložili medzi obľúbené. Majitelia salónov tu publikujú špeciálne novinky, limitované zľavové akcie či informácie o nových členoch tímu.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">campaign</span>
              <span>Prehľad noviniek a akcií</span>
            </div>
            <div class="screenshot-container" data-caption="Novinky od prevádzok - časová os oznámení a akčných ponúk">
              <img src="images/06_novinky.png" alt="Novinky od prevádzok Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-2">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">bolt</span>
                <h3>Priama rezervácia z akcie</h3>
              </div>
              <p>Pokiaľ salón vyhlási napríklad jarnú akciu na regeneračnú kúru, pod správou nájdete tlačidlo na priamu rezerváciu s už aplikovanou akčnou cenou.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">event_busy</span>
                <h3>Oznamy o sanitárnych dňoch a dovolenkách</h3>
              </div>
              <p>Dozviete sa v predstihu, kedy má váš obľúbený barber či kozmetička dovolenku, aby ste si stihli rezervovať termín včas pred odchodom.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 07: Kreslo Hunter -->
      <section class="guide-section" id="sec-07" data-category="benefity">
        <div class="section-header-card">
          <div class="section-num-badge">07</div>
          <div class="section-header-text">
            <h2>Kreslo Hunter (Automatický Lovec Termínov)</h2>
            <p class="section-subtitle">Inteligentný agent sledujúci uvoľnené a last-minute termíny vo vašom revíri</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Kreslo Hunter (záložka #hunter)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">radar</span>
              <span class="meta-label">Technológia:</span>
              <span class="meta-val">Okamžitý záchyt uvoľnených termínov (storná iných klientov)</span>
            </div>
          </div>

          <p class="section-text-lead">
            <strong>Kreslo Hunter</strong> je prémiová unikátna funkcia platformy Rezervos. Ak sú vaše obľúbené salóny plne vybookované na týždne dopredu, Hunter za vás nepretržite stráži kalendáre. Hneď ako niekto iný zruší svoju rezerváciu na poslednú chvíľu, Hunter uvoľnené kreslo zachytí a pošle vám okamžitú ponuku.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">radar</span>
              <span>Rozhranie funkcie Kreslo Hunter a konfigurácia revíru</span>
            </div>
            <div class="screenshot-container" data-caption="Kreslo Hunter - nastavenie sledovaných salónov, zliav a filtrov dostupnosti">
              <img src="images/07_kreslo_hunter.png" alt="Kreslo Hunter Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">my_location</span>
                <h3>Nastavenie revíru</h3>
              </div>
              <p>Zvoľte si mesto alebo oblasť, preferované služby (napr. Dámsky strih, Barber, Gélové nechty) a maximálnu vzdialenosť.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">percent</span>
                <h3>Last-minute zľavy</h3>
              </div>
              <p>Mnoho salónov ponúka uvoľnené termíny v ten istý deň so zľavou 20% až 50%, aby nezostali s prázdnym kreslom. Hunter tieto zľavy prioritne zobrazuje.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">timer</span>
                <h3>Rýchla rezervácia</h3>
              </div>
              <p>Ulovený termín si môžete zarezervovať na 1 kliknutie z notifikácie skôr, ako ho stihne obsadiť niekto iný.</p>
            </div>
          </div>

          <div class="steps-card">
            <h3>Ako aktivovať svojho Lovca termínov:</h3>
            <div class="step-item">
              <div class="step-number">1</div>
              <div class="step-desc">
                <strong>Zadajte kritériá:</strong> Vyberte kategóriu služby, mesto a dni v týždni, kedy máte voľný čas (napr. Piatok poobede alebo Víkend).
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">2</div>
              <div class="step-desc">
                <strong>Zapnite notifikácie:</strong> Povoľte odosielanie SMS alebo e-mailových upozornení na ulovené termíny v sekcii nastavení.
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">3</div>
              <div class="step-desc">
                <strong>Kliknite a potvrďte:</strong> Po doručení notifikácie o voľnom kresle stačí otvoriť odkaz a potvrdiť rezerváciu.
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 08: Obľúbené salóny -->
      <section class="guide-section" id="sec-08" data-category="oblubene">
        <div class="section-header-card">
          <div class="section-num-badge">08</div>
          <div class="section-header-text">
            <h2>Obľúbené Salóny (Môj Zoznam Prevádzok)</h2>
            <p class="section-subtitle">Váš osobný zoznam uložených podnikov pre okamžitú rezerváciu bez hľadania</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Obľúbené salóny (záložka #favorites)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">favorite</span>
              <span class="meta-label">Funkcia:</span>
              <span class="meta-val">Ukladanie kliknutím na ikonu srdiečka pri profile salónu</span>
            </div>
          </div>

          <p class="section-text-lead">
            Nemusíte si pamätať presný názov kaderníctva, kozmetického štúdia či masážneho salónu. Všetky prevádzky, ktoré si označíte srdiečkom, sa prehľadne zhromažďujú na tejto karte. Jedným kliknutím sa dostanete k ich aktuálnemu cenníku, personálu a voľným termínom.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">favorite_border</span>
              <span>Karty uložených obľúbených salónov</span>
            </div>
            <div class="screenshot-container" data-caption="Obľúbené salóny - zoznam podnikov s adresami a tlačidlom rýchlej objednávky">
              <img src="images/08_oblubene.png" alt="Obľúbené salóny Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">bookmark</span>
                <h3>Priamy prístup</h3>
              </div>
              <p>Preskočte katalógové vyhľadávanie. Otvorte obľúbený salón a ihneď si vyberte svojho osvedčeného majstra a voľný čas.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">call</span>
                <h3>Kontaktné informácie</h3>
              </div>
              <p>Okamžitý prístup k telefónnemu číslu salónu, presnej adrese, otváracím hodinám a odkazom na ich sociálne siete.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">delete_outline</span>
                <h3>Jednoduchá správa</h3>
              </div>
              <p>Ak už do salónu neplánujete chodiť, opätovným kliknutím na srdiečko ho kedykoľvek zo zoznamu obľúbených odstránite.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 09: Moje recenzie -->
      <section class="guide-section" id="sec-09" data-category="oblubene">
        <div class="section-header-card">
          <div class="section-num-badge">09</div>
          <div class="section-header-text">
            <h2>Moje Recenzie &amp; Hodnotenia</h2>
            <p class="section-subtitle">Archív vašich hodnotení služieb, spätná väzba a odpovede manažérov salónov</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Moje recenzie (záložka #reviews)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">verified</span>
              <span class="meta-label">Dôveryhodnosť:</span>
              <span class="meta-val">Iba overené recenzie od reálnych klientov po absolvovaní termínu</span>
            </div>
          </div>

          <p class="section-text-lead">
            Na platforme Rezervos môžu recenzie písať výhradne overení zákazníci, ktorí procedúru reálne absolvovali a zaplatili. V tejto sekcii máte pohromade všetky svoje udelené hviezdičkové hodnotenia a slovné komentáre.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">rate_review</span>
              <span>Zoznam publikovaných hodnotení zákazníka</span>
            </div>
            <div class="screenshot-container" data-caption="Moje recenzie - hviezdičkové hodnotenie, text recenzie a reakcie prevádzok">
              <img src="images/09_recenzie.png" alt="Moje recenzie Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-2">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">edit_note</span>
                <h3>Úprava a doplnenie spätnej väzby</h3>
              </div>
              <p>Ak sa po čase rozhodnete svoje hodnotenie spresniť alebo doplniť novú skúsenosť, môžete svoju recenziu kedykoľvek upraviť.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">reply</span>
                <h3>Odpovede salónov</h3>
              </div>
              <p>Máte možnosť sledovať oficiálne odpovede a poďakovania od majiteľov prevádzok na vašu zanechanú spätnú väzbu.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 10: Moje inzeráty -->
      <section class="guide-section" id="sec-10" data-category="financie">
        <div class="section-header-card">
          <div class="section-num-badge">10</div>
          <div class="section-header-text">
            <h2>Moje Inzeráty (Klientska Inzercia)</h2>
            <p class="section-subtitle">Bezplatné pridávanie a správa inzerátov, predaj vybavenia či dopyt po službách</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Samostatná stránka:</span>
              <span class="meta-val">moj_profil-inzercia.php</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">store</span>
              <span class="meta-label">Zameranie:</span>
              <span class="meta-val">B2C a C2C inzercia pre komunitu krásy, zdravia a služieb</span>
            </div>
          </div>

          <p class="section-text-lead">
            Rezervos prepája nielen zákazníkov so salónmi, ale vytvára celú komunitu. V sekcii <strong>Moje inzeráty</strong> môžete pridávať vlastné inzeráty — či už predávate profesionálne kozmetické prístroje, ponúkate prenájom kresla, alebo hľadáte spoľahlivú vizážistku na svadobný termín.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">post_add</span>
              <span>Portál klientskej inzercie</span>
            </div>
            <div class="screenshot-container" data-caption="Moje inzeráty - formulár na pridanie inzerátu, zoznam aktívnych ponúk a štatistiky">
              <img src="images/10_inzercia.png" alt="Moje inzeráty Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">add_photo_alternate</span>
                <h3>Bohatá fotogaléria</h3>
              </div>
              <p>K inzerátu môžete nahrať viacero fotografií, detailný popis, predajnú cenu a lokalitu pôsobenia.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">visibility</span>
                <h3>Počítadlo zobrazení</h3>
              </div>
              <p>Presná štatistika, koľko návštevníkov portálu Rezervos si váš inzerát prezrelo a prejavilo záujem.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">toggle_on</span>
                <h3>Aktivácia a archivácia</h3>
              </div>
              <p>Inzerát môžete kedykoľvek dočasne pozastaviť, označiť za predaný alebo úplne vymazať z databázy.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 11: Klientska Peňaženka -->
      <section class="guide-section" id="sec-11" data-category="financie">
        <div class="section-header-card">
          <div class="section-num-badge">11</div>
          <div class="section-header-text">
            <h2>Klientska Peňaženka &amp; Dobíjanie Kreditu</h2>
            <p class="section-subtitle">Bezkontaktné platby za rezervácie z predplateného kreditu a transparentná história transakcií</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Samostatná stránka:</span>
              <span class="meta-val">moj_profil-penazenka.php</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">credit_card</span>
              <span class="meta-label">Platobné metódy:</span>
              <span class="meta-val">Platobná karta (Stripe / GP Webpay), Apple Pay, Google Pay</span>
            </div>
          </div>

          <p class="section-text-lead">
            S klientskou peňaženkou Rezervos nemusíte pri návšteve salónu riešiť hotovosť ani vyťahovať platobnú kartu pri pokladni. Dobite si kredit vopred a plaťte za služby bezpečne, rýchlo a bezkontaktne priamo z aplikácie.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">account_balance_wallet</span>
              <span>Rozhranie Peňaženky a história pohybov na účte</span>
            </div>
            <div class="screenshot-container" data-caption="Klientska Peňaženka - aktuálny zostatok, formulár dobitia a výpis transakcií">
              <img src="images/11_penazenka.png" alt="Klientska Peňaženka Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">add_card</span>
                <h3>Okamžité dobitie kreditu</h3>
              </div>
              <p>Zvoľte si sumu (napr. 20 €, 50 €, 100 €) a zaplaťte online. Kredit je na vašom účte pripísaný do niekoľkých sekúnd.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">receipt</span>
                <h3>História transakcií</h3>
              </div>
              <p>Prehľadný bankový výpis každého pohybu: dobitia, platby za rezervácie, pripísané bonusy a prípadné vrátenia peňazí pri storne.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">lock</span>
                <h3>Maximálna bezpečnosť</h3>
              </div>
              <p>Všetky platobné operácie prebiehajú cez šifrovanú platobnú bránu podľa bankových bezpečnostných štandardov PCI-DSS.</p>
            </div>
          </div>

          <div class="alert-box alert-tip">
            <span class="material-symbols-outlined alert-icon">redeem</span>
            <div class="alert-content">
              <strong>Kreditné bonusy:</strong> Niektoré salóny a akcie Rezervos ponúkajú bonusový kredit k dobitiu (napríklad dobite 50 € a získajte kredit 55 € na využitie v obľúbenom salóne).
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 12: Affiliate program -->
      <section class="guide-section" id="sec-12" data-category="financie">
        <div class="section-header-card">
          <div class="section-num-badge">12</div>
          <div class="section-header-text">
            <h2>Affiliate Program (Odporučte a Získajte Provízie)</h2>
            <p class="section-subtitle">Váš unikátny odporúčací link, štatistiky konverzií a pasívny príjem či kredity</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Samostatná stránka:</span>
              <span class="meta-val">affiliate.php</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">share</span>
              <span class="meta-label">Odmena:</span>
              <span class="meta-val">Finančná provízia za každú odporúčanú prevádzku, ktorá sa zaregistruje</span>
            </div>
          </div>

          <p class="section-text-lead">
            Páči sa vám systém Rezervos a poznáte kaderníčku, barbera, maséra alebo majiteľa kozmetického salónu, ktorý stále používa papierový diár? Zdieľajte s ním svoj osobný odporúčací odkaz. Keď si prevádzka aktivuje balík na Rezervos, vy získate férovú províziu alebo kredit na služby zadarmo.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">group_add</span>
              <span>Ovládací panel Affiliate programu</span>
            </div>
            <div class="screenshot-container" data-caption="Affiliate program - váš unikátny link, počet preklikov a zarobené provízie">
              <img src="images/12_affiliate.png" alt="Affiliate program Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">link</span>
                <h3>Váš unikátny link</h3>
              </div>
              <p>Automaticky vygenerovaný odkaz obsahujúci vaše klientske ID. Môžete ho skopírovať a poslať cez WhatsApp, Messenger alebo sociálne siete.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">query_stats</span>
                <h3>Transparentné štatistiky</h3>
              </div>
              <p>Vidíte presný počet návštevníkov, ktorí cez váš odkaz prišli, koľko prevádzok sa zaregistrovalo a koľko aktívne platí.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">monetization_on</span>
                <h3>Pravidelné vyplácanie</h3>
              </div>
              <p>Získané provízie si môžete nechať poslať na svoj bankový účet alebo ich premeniť na kredit do peňaženky pre bezplatné návštevy salónov.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 13: Nastavenia profilu & VIP odznak -->
      <section class="guide-section" id="sec-13" data-category="profil">
        <div class="section-header-card">
          <div class="section-num-badge">13</div>
          <div class="section-header-text">
            <h2>Nastavenia Profilu &amp; Klientsky VIP Odznak</h2>
            <p class="section-subtitle">Úprava osobných údajov, telefónu pre SMS notifikácie a nastavenie súkromia</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Nastavenia profilu (záložka #settings)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">verified</span>
              <span class="meta-label">VIP Status:</span>
              <span class="meta-val">Odznak spoľahlivého klienta na základe dochádzky bez neospravedlnených absencií</span>
            </div>
          </div>

          <p class="section-text-lead">
            V nastaveniach profilu máte pod kontrolou svoje identifikačné a kontaktné údaje. Správne vyplnené telefónne číslo je kľúčové pre bezplatné SMS pripomienky pred termínom. Zároveň tu vidíte svoj <strong>VIP status</strong> — salóny prioritne schvaľujú rezervácie klientom s vysokým skóre spoľahlivosti.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">manage_accounts</span>
              <span>Klientsky profil, VIP odznak a formulár osobných údajov</span>
            </div>
            <div class="screenshot-container" data-caption="Nastavenia profilu - editácia mena, e-mailu, telefónu a notifikačných predvolieb">
              <img src="images/13_nastavenia_profilu.png" alt="Nastavenia profilu Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-3">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">phone_iphone</span>
                <h3>Telefónne číslo &amp; SMS</h3>
              </div>
              <p>Zadajte telefónne číslo v medzinárodnom tvare (+421...), aby vám 24 hodín a 2 hodiny pred procedúrou prišla SMS pripomienka.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">workspace_premium</span>
                <h3>VIP odznak spoľahlivosti</h3>
              </div>
              <p>Klienti, ktorí na rezervované termíny chodia načas a nerušia ich na poslednú chvíľu, získavajú odznak VIP. Salóny im dôverujú a často poskytujú výhody.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">notifications_off</span>
                <h3>Predvoľby komunikácie</h3>
              </div>
              <p>Môžete si nastaviť, aké typy správ chcete dostávať — potvrdenia, pripomienky termínov, newslettere salónov či systémové novinky.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 14: Zabezpečenie & 2FA -->
      <section class="guide-section" id="sec-14" data-category="profil">
        <div class="section-header-card">
          <div class="section-num-badge">14</div>
          <div class="section-header-text">
            <h2>Zabezpečenie Účtu &amp; Dvojfaktorové Prihlásenie (2FA)</h2>
            <p class="section-subtitle">Zmena prístupového hesla, aktivácia mobilného 2FA overenia a audit prihlásených zariadení</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Umiestnenie v menu:</span>
              <span class="meta-val">Zabezpečenie (záložka #security)</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">shield</span>
              <span class="meta-label">Úroveň ochrany:</span>
              <span class="meta-val">Šifrované heslá (Argon2 / bcrypt) + Authenticator TOTP</span>
            </div>
          </div>

          <p class="section-text-lead">
            Bezpečnosť vašich osobných údajov a peňaženky je prvoradá. V sekcii Zabezpečenie si môžete kedykoľvek aktualizovať prihlasovacie heslo a aktivovať <strong>dvojfaktorovú autentifikáciu (2FA)</strong>, ktorá zabráni neoprávnenému prístupu k vášmu účtu aj v prípade prezradenia hesla.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">security</span>
              <span>Obrazovka zabezpečenia a aktivácie 2FA</span>
            </div>
            <div class="screenshot-container" data-caption="Zabezpečenie účtu - zmena hesla, nastavenie 2FA cez Authenticator aplikáciu a aktívne relácie">
              <img src="images/14_zabezpecenie.png" alt="Zabezpečenie účtu Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="card-grid-2">
            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">password</span>
                <h3>Zmena prihlasovacieho hesla</h3>
              </div>
              <p>Formulár vyžaduje overenie vášho aktuálneho hesla a zadanie nového hesla s indikátorom sily. Odporúčame použiť kombináciu veľkých a malých písmen, čísel a symbolov.</p>
            </div>

            <div class="feature-card">
              <div class="feature-card-header">
                <span class="material-symbols-outlined feature-icon">phonelink_lock</span>
                <h3>Dvojfaktorové overenie (2FA)</h3>
              </div>
              <p>Prepojte si účet s mobilnou aplikáciou (Google Authenticator, Microsoft Authenticator alebo Authy). Pri prihlasovaní z nového zariadenia budete vyzvaný na zadanie 6-miestneho kódu.</p>
            </div>
          </div>

          <div class="alert-box alert-warning">
            <span class="material-symbols-outlined alert-icon">warning</span>
            <div class="alert-content">
              <strong>Záchranné kódy:</strong> Pri aktivácii 2FA si nezabudnite opísať záchranné záložné kódy. Pomôžu vám prihlásiť sa do systému v prípade, že stratíte alebo zmeníte svoj mobilný telefón.
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 15: Ako nájsť salón a rezervovať termín -->
      <section class="guide-section" id="sec-15" data-category="oblubene">
        <div class="section-header-card">
          <div class="section-num-badge">15</div>
          <div class="section-header-text">
            <h2>Ako Nájsť Salón &amp; Rezervovať Termín</h2>
            <p class="section-subtitle">Kompletný sprievodca vyhľadávaním prevádzok a 4-krokovým rezervačným procesom</p>
          </div>
        </div>

        <div class="section-card">
          <div class="section-meta-row">
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">link</span>
              <span class="meta-label">Verejný katalóg:</span>
              <span class="meta-val">rezervos.eu/prevadzky.php</span>
            </div>
            <div class="meta-item">
              <span class="material-symbols-outlined meta-icon">schedule</span>
              <span class="meta-label">Čas rezervácie:</span>
              <span class="meta-val">Menej ako 60 sekúnd, 24 hodín denne / 7 dní v týždni</span>
            </div>
          </div>

          <p class="section-text-lead">
            Objednať sa na strihanie, masáž či nechtový dizajn nebolo nikdy jednoduchšie. Nemusíte čakať na otváracie hodiny salónu ani telefonovať počas pracovného zhonu. Rezervos vám umožňuje prezrieť si voľné termíny v reálnom čase kedykoľvek, hoci aj o polnoci z pohodlia domova.
          </p>

          <div class="screenshot-box">
            <div class="screenshot-label">
              <span class="material-symbols-outlined">travel_explore</span>
              <span>Verejný katalóg prevádzok a výber služieb</span>
            </div>
            <div class="screenshot-container" data-caption="Katalóg prevádzok Rezervos - filtrovanie podľa kategórie, mesta, hodnotenia a voľných termínov">
              <img src="images/15_hladanie_salonov.png" alt="Hľadanie salónov a rezervácia Rezervos" loading="eager">
              <div class="screenshot-zoom-hint">
                <span class="material-symbols-outlined">zoom_in</span>
                <span>Kliknite pre zväčšenie</span>
              </div>
            </div>
          </div>

          <div class="steps-card">
            <h3>4 jednoduché kroky k vášmu termínu:</h3>
            <div class="step-item">
              <div class="step-number">1</div>
              <div class="step-desc">
                <strong>Vyhľadanie a výber salónu:</strong> Na stránke prevádzok zadajte vaše mesto alebo kategóriu (Kaderníctvo, Barber, Kozmetika, Masáže...). Pozrite si hodnotenia ostatných zákazníkov, fotky interiéru a cenník.
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">2</div>
              <div class="step-desc">
                <strong>Voľba služby a pracovníka:</strong> Kliknite na tlačidlo <em>„Rezervovať“</em>. Zvoľte požadovanú službu (napr. Pánsky strih + úprava brady) a konkrétneho zamestnanca, alebo nechajte možnosť <em>„Ktokoľvek voľný“</em> pre najrýchlejší termín.
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">3</div>
              <div class="step-desc">
                <strong>Výber dňa a času:</strong> V interaktívnom kalendári sa vám rozbalia iba skutočne voľné časové sloty. Vyberte si čas, ktorý vám presne vyhovuje.
              </div>
            </div>
            <div class="step-item">
              <div class="step-number">4</div>
              <div class="step-desc">
                <strong>Potvrdenie rezervácie:</strong> Skontrolujte zhrnutie, zadajte zľavový kód či permanentku (ak máte) a kliknite na <em>„Potvrdiť rezerváciu“</em>. Na e-mail a do SMS vám okamžite dorazí potvrdenie so všetkými podrobnosťami.
              </div>
            </div>
          </div>

          <div class="alert-box alert-tip">
            <span class="material-symbols-outlined alert-icon">check_circle</span>
            <div class="alert-content">
              <strong>Automatická synchronizácia s mobilom:</strong> V potvrdzujúcom e-maili aj v klientskom profile máte k dispozícii tlačidlo <em>„Pridať do kalendára“</em>, ktoré termín okamžite vloží do vášho Google Kalendára alebo Apple Kalendára v iPhone s automatickou pripomienkou.
            </div>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- Lightbox Modal for Zooming Screenshots -->
  <div class="lightbox-modal" id="lightbox-modal">
    <div class="lightbox-content">
      <button class="lightbox-close" id="lightbox-close" title="Zavrieť náhľad">
        <span class="material-symbols-outlined">close</span>
      </button>
      <img id="lightbox-img" src="" alt="Náhľad v plnej veľkosti" class="lightbox-img">
      <div class="lightbox-caption" id="lightbox-caption"></div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="portal-footer">
    <p>
      &copy; 2026 Rezervos Zákazník Manuál &bull; Vytvorené ako komplexná používateľská príručka pre zákazníkov.
    </p>
    <p style="margin-top: 6px; font-size: 12px; color: var(--text-light);">
      Všetky práva a ochranné známky patria ich príslušným vlastníkom. Vytvorené v súlade s platformou Rezervos.eu.
    </p>
  </footer>

  <!-- Interactive Logic -->
  <script src="app.js"></script>
</body>
</html>
"""

# Write to file
target_path = "c:/VolneKreslo/zakaznik/index.html"
with open(target_path, "w", encoding="utf-8") as f:
    f.write(html_content)

print(f"Generated {target_path} successfully. File size: {len(html_content)} bytes.")

# Strict emoji audit
emojis = re.findall(r'[\U00010000-\U0010ffff\u2600-\u27bf]', html_content)
print(f"Emoji count: {len(emojis)}")
if emojis:
    print(f"Warning emojis found: {emojis}")
else:
    print("Zero emojis verified!")
