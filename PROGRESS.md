# VoľnéKreslo — Priebeh práce (Inzercia, Peňaženka, Zdieľanie)

Posledná aktualizácia: 27.8.2026

## ⚠️ NEDORIEŠENÉ — čaká sa na sync

`C:\VolneKreslo\inzercia.php` má lokálne správne poradie (taby nad nadpisom "Inzercia",
pod vyhľadávaním) — upravené o 10:54:51, riadok 1931 (taby) pred riadkom 1941 (nadpis).
**Server (rezervos.eu) to ešte nemá** — sync nástroj (FreeFileSync-like, sleduje
`C:\VolneKreslo` → `/public_html`) stratil pripojenie tesne po tejto úprave (11:00:38),
re-scan po reconnecte to zjavne nezachytil ako zmenu.

**Čo skontrolovať po reštarte:**
1. Otvoriť sync nástroj, nájsť `inzercia.php` v zozname, overiť či ho označuje ako zmenený
2. Ak nie, vynútiť ručné porovnanie/prepísanie len tohto súboru (lokálny → vzdialený)
3. Overiť cez `https://rezervos.eu/inzercia.php` — poradie má byť: Vyhľadávanie → Breadcrumbs → Typové taby → "Inzercia" nadpis + tlačidlo → grid inzerátov

Ak sync stále nič nezachytí, dá sa to obísť drobnou "neviditeľnou" úpravou súboru
(zmení sa mtime), nech ho nástroj znova zaradí medzi zmenené.

---

## Čo je hotové (túto session)

### 1. Verejná stránka Inzercia (`inzercia.php`)
- Pridané vyhľadávanie (`q` + `city`), rovnaký štýl ako `prevadzky.php`
- Poradie prvkov zosúladené s `prevadzky.php` (filter panel hore, breadcrumbs pod ním)
- Tlačidlo "Pridať inzerát" vedie na správny dashboard podľa role (business/customer/admin)
- Karty v gride sú teraz klikateľné odkazy na `inzerat.php?id=X`
- **Rozostávajúce:** poradie tabov vs. nadpisu — pozri sekciu vyššie

### 2. Poradie "Inzercia" v bočných paneloch
- `prevadzky.php`: Inzercia presunutá nad "Last minute" v kategóriách (bez medzery/deliaceho pruhu)
- Admin dashboard (`includes/sidebar.php`): vrátené na pôvodné poradie (nemenené)

### 3. Samostatné stránky pre zákazníka a admina (predtým existovalo len pre biznis)
- `moj_profil-inzercia.php` — "Moje inzeráty" pre zákazníka/admina
- `admin-inzercia.php` — "Moje inzeráty" pre admina
- `moj_profil-penazenka.php` — Peňaženka pre zákazníka/admina
- `admin-penazenka.php` — Peňaženka pre admina
- Všetky používajú zdieľané bočné panely (nižšie)

### 4. Zdieľané bočné panely (nové)
- `includes/customer-sidebar.php` — pre `moj_profil.php` aj `moj_profil-inzercia.php`/`moj_profil-penazenka.php`
- `includes/admin-sidebar.php` — pre `admin.php` aj `admin-inzercia.php`/`admin-penazenka.php`
- Riešia: deep-linking zo samostatných stránok naspäť na správnu sekciu (`moj_profil.php?section=X`),
  jednotné zvýraznenie aktívnej položky (opravená CSS špecificita voči `dashboard.css`),
  jednotné rozostupy menu (rovnaká oprava), hover-persist cez `sessionStorage` + JS trieda
  `.js-pinned-open` (CSS `:hover` sa po plnej navigácii neprepočíta, treba JS)

### 5. Admin — moderácia a práva
- `admin.php`: nový tab "Správa inzerátov" (všetky inzeráty v systéme, mazanie hocijakého)
- `api/admin.php`: nové akcie `get_all_classifieds`, `delete_classified`
- Blokovanie/mazanie používateľov už existovalo (`toggle_user_status`, `delete_user`) — overené, funguje

### 6. Peňaženka — zdieľaný modul (`includes/wallet-topup.php`)
- Dobitie kreditu (5/10/20/50 €) — `api/wallet.php` akcia `topup`, teraz univerzálna pre každého (nielen biznis)
- Zostatok rozdelený na "Dobité kartou" vs. "Za zdieľanie" (`credit_purchased` / `credit_earned`)
- Karta "Zdieľajte stránku" (+0,10 €) a "Zdieľajte svoje inzeráty" (+0,05 €, max 2× denne,
  odkaz na Moje inzeráty) — samostatné, každá s vlastným stavovým odznakom "Dnešný limit vyčerpaný"

### 7. Odmeny za zdieľanie (`api/wallet.php`, akcia `claim_share_reward`)
- Spoločný denný limit **0,10 €** naprieč: zdieľanie appky (0,10 €) ALEBO až 2× zdieľanie
  vlastného inzerátu (0,05 € × 2)
- Nová akcia `get_share_status` (today_amount, today_ad_count, remaining, app_available, listing_available)
- Nové stĺpce `users.share_reward_today_amount`, `users.share_reward_ad_count_today`
- Oprava: `getUserAdStatus()` v `api/classifieds.php` už nepoužíva `users.subscription_tier`
  ako náhradu za biznis balík — zákazník/admin vždy platí z peňaženky (nie mesačné kredity)

### 8. Redizajn "Moje inzeráty" (zdieľané pre biznis/zákazník/admin — `includes/inzercia-manager.php`)
Podľa vzoru z priečinka `aveino`:
- Karta: reálny náhľad fotky, cena, lokalita, počet zobrazení, dropdown stavu
  (Aktívny/Rezervované/Predané), tlačidlá Zobraziť/Zvýrazniť/Zdieľať/Zmazať
- Rozšírený modál "Zviditeľniť inzerát" — z 2 na 5 úrovní:
  - Skupina "Posun hore": Jednorazové tapnutie (0,25 €), Ranné vtáča 7 dní (0,90 €), 7-dňové topovanie (1,20 €)
  - Skupina "Vizuálne zvýraznenie": Neon Glow rámček 7 dní (0,60 €), Štítok "Rýchly predaj" 7 dní (0,40 €)
- Nová stránka detailu inzerátu **`inzerat.php?id=X`** — galéria, popis, kontakt, počítadlo
  zobrazení, tlačidlo Zdieľať (dostupné každému, odmenu dostane len majiteľ), pre majiteľa aj
  Zviditeľniť (rovnaký 5-úrovňový modál)

### 9. Nové DB stĺpce (`classifieds` tabuľka, auto-migrácia v `api/classifieds.php`)
`views`, `status`, `morning_expires_at`, `prime_expires_at`, `vip_glow_expires_at`, `vip_badge_expires_at`

### 10. Opravené chyby po ceste
- **Kritická:** `inz_render_image_upload_zone()` bola obalená v `function_exists()` a definovaná
  AŽ za miestom prvého volania → PHP fatal error → tichý orez celej stránky (vyzeralo to ako
  zamrznuté "Načítavam..."). Opravené presunom na začiatok súboru ako obyčajná deklarácia.
- `my_list` v `api/classifieds.php` nefiltroval `is_active=1` — zobrazovali sa aj zmazané inzeráty
- Preklep v texte "+0,05 €" namiesto "+0,10 €" pri zdieľaní appky (opravené)
- CSS špecificita: `assets/css/dashboard.css` prebíjala pôvodné (väčšie) rozostupy a farby
  aktívnej položky menu — opravené `!important` override v zdieľaných sidebar súboroch
- Card v peňaženke (`wt-wrap`) sa naťahovala cez celú šírku — rovnaká príčina (CSS špecificita)

---

## Kľúčové súbory (referencia)
- `api/classifieds.php` — hlavné API pre inzeráty (CRUD, boost, views, status)
- `api/wallet.php` — peňaženka, dobitie, odmeny za zdieľanie
- `api/admin.php` — admin akcie (users, businesses, classifieds moderácia)
- `includes/inzercia-manager.php` — zdieľaný self-service modul (karty, modály, JS `InzManager`)
- `includes/wallet-topup.php` — zdieľaný modul peňaženky (JS `WalletTopup`)
- `includes/customer-sidebar.php`, `includes/admin-sidebar.php` — zdieľané bočné panely
- `inzerat.php` — nová stránka detailu jedného inzerátu
- Referenčný dizajn (na porovnanie/inšpiráciu): `aveino/users/dashboard.php`, `aveino/inzerat.php`
