# VoľnéKreslo – Pravidlá projektu

## Prostredie
- Pracujem na **lokálnych súboroch** v `C:\VolneKreslo\`
- Každé uloženie súboru (`Ctrl + S`) sa **okamžite synchronizuje na živý FTP server**
- Platí teda: nefunkčný kód = broken live site

## Požiadavky na výstupy

1. **Presný názov a cesta** – vždy uveď plnú cestu k upravovanému/vytváranému súboru (napr. `C:\VolneKreslo\api\ai-email.php`)
2. **Kompletný kód** – žiadne skratky, žiadne `// ... zvyšok ostáva rovnaký`. Každý výstup musí byť pripravený na priame skopírovanie/uloženie bez toho, aby musel používateľ čokoľvek doplňovať.
3. **Kontrola pred odoslaním** – pred generovaním odpovede dôsledne skontrolovať syntaktické aj logické chyby (neuzavreté zátvorky, chýbajúce bodkočiarky, zlý scope premenných, neexistujúce CSS triedy, chýbajúce HTML elementy na ktoré odkazuje JS, atď.)

## Technické pravidlá projektu (PRAVIDLÁ.md)
- **Bez pills** – border-radius max 6–8px, nie 100px/9999px
- **Žiadne `&`** v textoch (použiť "a" alebo "aj")
- **Žiadne emoji** – namiesto nich Material Symbols Outlined (`<span class="material-symbols-outlined">`)
- **Žiadne system dialogy** (alert / confirm / prompt) – vždy vlastné UI modaly
- Groq API: model `llama-3.3-70b-versatile`, temp 0.6, max_tokens 2048
- CSS custom properties: `var(--primary-color)`, `var(--border-color)`, `var(--card-bg)`, `var(--text-primary)`, `var(--text-secondary)`
- FullCalendar 6.1.15, dayGridMonth, slovenská locale
- Side drawer: CSS width transition (0 → 236px), `.open` class toggle

## Stack
- PHP (session auth, config.php s konštantami)
- MySQL cez PDO
- Vanilla JS (žiadny framework okrem FullCalendar)
- CSS custom properties, bez frameworkov
