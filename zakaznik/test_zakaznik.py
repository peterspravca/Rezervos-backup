import os
from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page(viewport={'width': 1440, 'height': 900})
    errors = []
    page.on('pageerror', lambda e: errors.append(str(e)))
    page.on('console', lambda msg: errors.append(msg.text) if msg.type == 'error' else None)
    
    file_url = 'file:///' + os.path.abspath('c:/VolneKreslo/zakaznik/index.html').replace('\\', '/')
    page.goto(file_url, wait_until='networkidle')
    
    imgs = page.evaluate('''() => {
        return Array.from(document.querySelectorAll('img:not(#lightbox-img)')).map(img => ({
            src: img.src.split('/').pop(),
            loaded: img.naturalWidth > 0,
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight
        }));
    }''')
    
    print('Found images:', len(imgs))
    all_loaded = all(img['loaded'] for img in imgs)
    print('All images loaded:', all_loaded)
    for i, img in enumerate(imgs):
        status = 'OK' if img['loaded'] else 'FAILED'
        src_name = img['src']
        w = img['naturalWidth']
        h = img['naturalHeight']
        print(f"  [{i}] {status}: {src_name} ({w}x{h})")
        
    print('Console errors:', len(errors), errors)
    page.screenshot(path='c:/VolneKreslo/zakaznik/test_desktop.png')
    
    # Mobile test
    mpage = browser.new_page(viewport={'width': 390, 'height': 844})
    mpage.goto(file_url, wait_until='networkidle')
    scroll_w = mpage.evaluate('() => document.documentElement.scrollWidth')
    client_w = mpage.evaluate('() => document.documentElement.clientWidth')
    print(f"Mobile: clientWidth={client_w}, scrollWidth={scroll_w}, overflow={scroll_w > client_w}")
    mpage.screenshot(path='c:/VolneKreslo/zakaznik/test_mobile.png')
    
    mpage.click('#mobile-toggle')
    mpage.wait_for_timeout(400)
    is_open = mpage.evaluate('() => document.querySelector(".sidebar").classList.contains("mobile-open")')
    print('Mobile sidebar open:', is_open)
    mpage.screenshot(path='c:/VolneKreslo/zakaznik/test_mobile_drawer.png')
    
    browser.close()
