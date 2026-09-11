import re
with open('porovnanie-cennikov.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix table header
content = content.replace('BUSINESS<span class="tier-price-sub">18,90 € / mes.</span>', 'BUSINESS<span class="tier-price-sub">21,90 € / mes.</span>')

# Fix the big card around line 1356: 'Pokročilý balík (BUSINESS vs STANDARD)'
content = re.sub(r'(Pokročilý balík \(BUSINESS vs STANDARD\).*?)18,90 €(.*?)Úspora 20,00 € / mes\.', r'\g<1>21,90 €\g<2>Úspora 17,00 € / mes.', content, flags=re.DOTALL)

# Fix 'Až 10 zamestnancov za 18,90 €'
content = content.replace('Až 10 zamestnancov za 18,90 €', 'Až 10 zamestnancov za 21,90 €')

with open('porovnanie-cennikov.html', 'w', encoding='utf-8') as f:
    f.write(content)
