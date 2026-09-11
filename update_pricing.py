import re
with open('porovnanie-cennikov.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace VIP with BUSINESS
content = re.sub(r'\bVIP\b', 'BUSINESS', content)
content = re.sub(r'tier-vip', 'tier-business', content)

# Replace the BUSINESS price block in the top cards
content = re.sub(r'18,90 €(.*?)Úspora 20,00 € / mes\.', r'21,90 €\g<1>Úspora 17,00 € / mes.', content, flags=re.DOTALL)
content = re.sub(r'UŠETRÍTE 240 € ROČNE', r'UŠETRÍTE 204 € ROČNE', content)

# Replace the header in the table
content = re.sub(r'Rezervos BUSINESS<span class="tier-price-sub">18,90 €</span>', 'Rezervos BUSINESS<span class="tier-price-sub">21,90 €</span>', content)

# Replace textual mentions of VIP/BUSINESS pricing
content = re.sub(r'Vo BUSINESS balíku za 18,90', 'V BUSINESS balíku za 21,90', content)
content = re.sub(r'BUSINESS balíka za 18,90 €', 'BUSINESS balíka za 21,90 €', content)
content = re.sub(r'neobmedzený počet zamestnancov za 18,90 €', 'až 10 zamestnancov za 21,90 €', content)
content = re.sub(r'za 18,90 € max 2 užívateľov', 'za 18,90 € max 2 užívateľov', content) # For competitor
content = re.sub(r'Vo BUSINESS balíku za 21,90 \?', 'V BUSINESS balíku za 21,90 €', content)

# Update limits in the table
# Employees
content = re.sub(r'<td>Používatelia</td>\s*<td class="value">1</td>\s*<td class="value">1</td>\s*<td class="value">3</td>\s*<td class="value">1</td>\s*<td class="value">5</td>\s*<td class="value">2</td>\s*<td class="value inf">∞</td>', 
                 '<td>Používatelia</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">3</td>\n                        <td class="value">2</td>\n                        <td class="value">10</td>', content)

# Pobočky
content = re.sub(r'<td>Pobočky \(Prevádzky\)</td>\s*<td class="value">1</td>\s*<td class="value">1</td>\s*<td class="value">1</td>\s*<td class="value">1</td>\s*<td class="value">1</td>\s*<td class="value">1</td>\s*<td class="value inf">∞</td>',
                 '<td>Pobočky (Prevádzky)</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">1</td>\n                        <td class="value">2</td>', content)

with open('porovnanie-cennikov.html', 'w', encoding='utf-8') as f:
    f.write(content)
