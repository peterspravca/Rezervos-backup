with open('porovnanie-cennikov.html', 'r', encoding='utf-8') as f:
    lines = f.readlines()

for i in range(len(lines)):
    # Table 1: Rezervos FREE, START, PRO, BUSINESS
    if 'Počet rezervácií / mesiac' in lines[i] and '150' in lines[i] and '300' in lines[i]:
        lines[i] = lines[i].replace('👥', '<span style="font-size:1.5rem;line-height:1">∞</span>')
    
    if 'Počet používateľov / zamestnancov' in lines[i] and '1 – 3' in lines[i]:
        lines[i] = lines[i].replace('<td class="value inf">👥</td>', '<td class="value" style="color:var(--accent-green);font-weight:800">10</td>')

    # Table 2: Konkurencia FREEMIUM, EASY, SMART, STANDARD, PREMIUM
    if 'Počet rezervácií / mesiac' in lines[i] and '100' in lines[i] and '200' in lines[i]:
        lines[i] = lines[i].replace('👥', '<span style="font-size:1.5rem;line-height:1">∞</span>')
        
    if 'Max. počet užívateľov' in lines[i] and '5' in lines[i]:
        lines[i] = lines[i].replace('👥', '<span style="font-size:1.5rem;line-height:1">∞</span>')

    if 'Max. počet prenájmov' in lines[i] and '5' in lines[i]:
        lines[i] = lines[i].replace('👥', '<span style="font-size:1.5rem;line-height:1">∞</span>')

    if 'Max. počet miestností' in lines[i] and '5' in lines[i]:
        lines[i] = lines[i].replace('👥', '<span style="font-size:1.5rem;line-height:1">∞</span>')

    # Table 3: Large comparison table
    if '<td>Rezervácie / mesiac</td>' in lines[i]:
        # The next lines have 👥 in them. So check following lines up to i+10
        for j in range(i+1, i+12):
            if '👥' in lines[j]:
                lines[j] = lines[j].replace('👥', '<span style="font-size:1.5rem;line-height:1">∞</span>')

    if '<td>Používatelia</td>' in lines[i]:
        for j in range(i+1, i+12):
            if '👥' in lines[j]:
                lines[j] = lines[j].replace('<td class="value inf">👥</td>', '<td class="value" style="color:var(--accent-green);font-weight:800">10</td>')

with open('porovnanie-cennikov.html', 'w', encoding='utf-8') as f:
    f.writelines(lines)
