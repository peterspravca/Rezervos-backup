<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boxicons Preview – VUETO CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Boxicons CDN -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Tabler Icons (for comparison) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    
    <style>
        body { padding: 3rem; background: var(--bg-base); color: var(--text-primary); font-family: 'Outfit', sans-serif; }
        .preview-container { max-width: 1200px; margin: 0 auto; }
        .comparison-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 2rem; }
        .comp-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .comp-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent-2); margin-bottom: 1.5rem; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; }
        
        .icon-item { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; font-size: 1.1rem; }
        .icon-box { width: 40px; height: 40px; border-radius: 10px; background: rgba(99, 102, 241, 0.1); display: flex; align-items: center; justify-content: center; color: var(--accent-2); font-size: 1.4rem; }
        
        .mock-sidebar { width: 250px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 1rem; }
        .mock-nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; color: var(--text-secondary); text-decoration: none; margin-bottom: 4px; font-weight: 500; }
        .mock-nav-item.active { background: rgba(99, 102, 241, 0.15); color: var(--accent-2); }
        .mock-nav-item i { font-size: 1.3rem; }
        
        .badge-premium { background: var(--accent); color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; margin-left: auto; }
        
        h2 { font-weight: 800; margin-bottom: 0.5rem; background: linear-gradient(135deg, #fff 0%, #94a3b8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        p { color: var(--text-muted); margin-bottom: 2rem; }
    </style>
</head>
<body>

<div class="preview-container">
    <h2>Boxicons – Vizuálny Náhľad</h2>
    <p>Toto je ukážka toho, ako by vaše CRM vyzeralo s ikonami <strong>Boxicons</strong>. Sú o niečo "mäkšie" a pôsobia moderne.</p>

    <!-- 1. ICON COMPARISON -->
    <div class="comparison-grid">
        <!-- current: Tabler -->
        <div class="comp-card">
            <div class="comp-title">Aktuálne: Tabler Icons</div>
            <div class="icon-item"><div class="icon-box"><i class="ti ti-layout-dashboard"></i></div> Dashboard</div>
            <div class="icon-item"><div class="icon-box"><i class="ti ti-users"></i></div> Kontakty</div>
            <div class="icon-item"><div class="icon-box"><i class="ti ti-mail"></i></div> E-mail</div>
            <div class="icon-item"><div class="icon-box"><i class="ti ti-calendar"></i></div> Kalendár</div>
            <div class="icon-item"><div class="icon-box"><i class="ti ti-settings"></i></div> Nastavenia</div>
        </div>

        <!-- NEW: Boxicons -->
        <div class="comp-card" style="border-color: var(--accent);">
            <div class="comp-title" style="color: var(--accent);">Návrh: Boxicons (Regular / Solid)</div>
            <div class="icon-item"><div class="icon-box" style="background: rgba(99, 102, 241, 0.2);"><i class='bx bxs-dashboard'></i></div> Dashboard</div>
            <div class="icon-item"><div class="icon-box" style="background: rgba(99, 102, 241, 0.2);"><i class='bx bxs-contact'></i></div> Kontakty</div>
            <div class="icon-item"><div class="icon-box" style="background: rgba(99, 102, 241, 0.2);"><i class='bx bxs-envelope'></i></div> E-mail</div>
            <div class="icon-item"><div class="icon-box" style="background: rgba(99, 102, 241, 0.2);"><i class='bx bxs-calendar'></i></div> Kalendár</div>
            <div class="icon-item"><div class="icon-box" style="background: rgba(99, 102, 241, 0.2);"><i class='bx bxs-cog'></i></div> Nastavenia</div>
        </div>
    </div>

    <!-- 2. SIDEBAR MOCKUP -->
    <div style="margin-top: 4rem;">
        <h3 style="margin-bottom: 1.5rem;">Ukážka Bočného Menu</h3>
        <div class="comparison-grid">
            <div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-bottom: 10px;">AKTUÁLNE (LUCIDE/TABLER)</div>
                <div class="mock-sidebar">
                    <a href="#" class="mock-nav-item active"><i class="ti ti-layout-dashboard"></i> Dashboard <span class="badge-premium">5</span></a>
                    <a href="#" class="mock-nav-item"><i class="ti ti-users"></i> Kontakty</a>
                    <a href="#" class="mock-nav-item"><i class="ti ti-mail"></i> E-mail</a>
                    <a href="#" class="mock-nav-item"><i class="ti ti-calendar"></i> Kalendár</a>
                </div>
            </div>
            <div>
                <div style="font-size: 0.7rem; color: var(--accent); margin-bottom: 10px;">NOVÉ (BOXICONS - LOGO ŠTÝL)</div>
                <div class="mock-sidebar" style="border-color: var(--accent); box-shadow: 0 0 20px rgba(99, 102, 241, 0.1);">
                    <a href="#" class="mock-nav-item active" style="background: rgba(99, 102, 241, 0.2);"><i class='bx bxs-grid-alt'></i> Dashboard <span class="badge-premium">5</span></a>
                    <a href="#" class="mock-nav-item"><i class='bx bxs-user-detail'></i> Kontakty</a>
                    <a href="#" class="mock-nav-item"><i class='bx bxs-envelope-open'></i> E-mail</a>
                    <a href="#" class="mock-nav-item"><i class='bx bxs-calendar-event'></i> Kalendár</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. ACTIONS -->
    <div style="margin-top: 4rem; text-align: center; background: rgba(255,255,255,0.03); padding: 3rem; border-radius: 24px; border: 1px dashed var(--border);">
        <h3 style="margin-bottom: 1rem;">Čo si o tom myslíte?</h3>
        <p>Boxicons ponúkajú širšiu škálu "Solid" (výplňových) ikon, ktoré v tmavom režime vyzerajú veľmi dobre, pretože tvoria jasné farebné body.</p>
        <div class="flex gap-2" style="justify-content: center;">
            <a href="index.php" class="btn btn-secondary">Späť na Dashboard</a>
            <button class="btn btn-primary" onclick="alert('Boxicons sú pripravené na globálne nasadenie!')">Páčia sa mi, nasadiť!</button>
        </div>
    </div>

</div>

</body>
</html>
