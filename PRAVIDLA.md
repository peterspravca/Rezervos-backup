# Pravidlá pre VoľnéKreslo dashboard

## Jazyk

- **Bez znaku "&"** kdekoľvek v UI. Namiesto "&" sa vždy píše "a" (po slovensky/česky).

- **Bez systémových dialógov** — žiadne `confirm()`, `alert()`, `prompt()`. Vždy vlastný modal s tlačidlami "Potvrdiť" / "Zrušiť".

## Vizuálny štýl

- **Bez emoji kdekoľvek v UI.** Namiesto emoji sa vždy používajú Material Symbols Outlined ikony (`<span class="material-symbols-outlined">`).
- Farba ikon: `var(--primary-color)` (#b08042) alebo `var(--text-secondary)` podľa kontextu.
- V `<select><option>` prvkoch nie sú ikony možné — text zostáva čistý, bez emoji.
- V zoznamoch renderovaných cez `innerHTML` sa ikony vkladajú ako `<span class="material-symbols-outlined">`.
- **Bez pilulkových tvarov (border-radius: 100px / 9999px).** Zaoblené rohy sa robia cez `border-radius: 10px` alebo `12px`. Platí pre tlačidlá, prepínače, tagy, bežce — všade.
