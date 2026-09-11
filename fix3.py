with open('porovnanie-cennikov.html', 'r', encoding='utf-8') as f:
    c = f.read()

# Replace the specific card block content (line 1361)
c = c.replace('<span class="new-price" style="font-size: 1.8rem; font-weight: 900; color: #059669;">18,90 €</span>',
              '<span class="new-price" style="font-size: 1.8rem; font-weight: 900; color: #059669;">21,90 €</span>')
c = c.replace('<div class="saving" style="font-size: 0.85rem; font-weight: 700; color: #059669; background: #d1fae5; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-bottom: 10px;">Úspora 20,00 € / mes. (−51%)</div>',
              '<div class="saving" style="font-size: 0.85rem; font-weight: 700; color: #059669; background: #d1fae5; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-bottom: 10px;">Úspora 17,00 € / mes. (−43%)</div>')
c = c.replace('💚 UŠETRÍTE 240 € ROČNE', '💚 UŠETRÍTE 204 € ROČNE')

# Double check line 2102
c = c.replace('Až 10 zamestnancov za 18,90 €', 'Až 10 zamestnancov za 21,90 €')
c = c.replace('Konkurencia má za 18,90 €', 'Konkurencia má za 18,90 €') # Keep this one as it is just in case it triggers on the line 2103
c = c.replace('Vo BUSINESS balíku až 10 zamestnancov. Konkurencia má za 18,90 €', 'Vo BUSINESS balíku až 10 zamestnancov. Konkurencia má za 18,90 €')

with open('porovnanie-cennikov.html', 'w', encoding='utf-8') as f:
    f.write(c)
