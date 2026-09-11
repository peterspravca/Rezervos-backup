<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = db_connect();
$user = current_user();

// Fetch lead data if ID is provided
$lead_id = (int)($_GET['id'] ?? 0);
$edit_token = $_GET['edit_t'] ?? null;
$client = null;
$draft_data = null;

if ($lead_id) {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->execute([$lead_id]);
    $client = $stmt->fetch();
}

if ($edit_token) {
    $stmt = $pdo->prepare("SELECT * FROM crm_generated_documents WHERE token = ?");
    $stmt->execute([$edit_token]);
    $doc = $stmt->fetch();
    if ($doc) {
        $content = json_decode($doc['content_json'], true);
        if (is_array($content)) {
            $draft_data = $content['editor_data'] ?? $content;
            $lead_id = $doc['lead_id']; 
        }
    }
}

$page_title = $draft_data ? 'Úprava cenovej ponuky' : 'Nová cenová ponuka';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> – VUETO CRM</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/nastenka/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/nastenka/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/nastenka/favicon/favicon-16x16.png">
    <link rel="manifest" href="/nastenka/favicon/site.webmanifest">
    <link rel="shortcut icon" href="/nastenka/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-app: #0f172a;
            --bg-toolbar: rgba(15, 23, 42, 0.8);
            --border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #2563eb;
        }

        * { box-sizing: border-box; }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Outfit', sans-serif;
            background-color: #1f2937;
            color: var(--text-primary);
            overflow: hidden;
        }

        /* TOOLBAR */
        .app-toolbar {
            height: 70px;
            background: var(--bg-toolbar);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .toolbar-left { display: flex; align-items: center; gap: 1rem; }
        .toolbar-right { display: flex; align-items: center; gap: 1rem; }
        .toolbar-center { display: flex; align-items: center; gap: 1.5rem; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
            padding: 0 1.15rem; height: 38px; border-radius: 10px;
            font-family: inherit; font-size: .875rem; font-weight: 600;
            cursor: pointer; border: 1px solid transparent; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;
            line-height: 1; white-space: nowrap; color: inherit;
        }
        .btn-primary { background: var(--accent); color: white; border: none; }
        .btn-primary:hover { background: #d97706; transform: translateY(-1px); }
        .btn-secondary { background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-primary); }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

        .client-warning-banner {
            background: #fef2f2;
            color: #991b1b;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid #fecaca;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            z-index: 90;
        }

        /* MODAL STYLES */
        .modal-overlay.open { display: flex !important; opacity: 1; }
        .modal-box {
            background: rgba(26, 29, 39, 0.9); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px; padding: 2rem; width: 480px; max-width: 92%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: scale(0.95) translateY(10px); transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            backdrop-filter: blur(12px);
            text-align: left;
        }
        .modal-overlay.open .modal-box { transform: scale(1) translateY(0); }

        .btn-toolbar {
            height: 38px; padding: 0 16px; display: flex; align-items: center; justify-content: center;
            gap: 8px; font-size: 0.85rem; border-radius: 12px; font-weight: 700; cursor: pointer;
            transition: all 0.2s; border: none; font-family: inherit; color: white;
        }
        .btn-toolbar i { font-size: 1.1rem; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-title { font-size: 1.2rem; font-weight: 800; color: white; display: flex; align-items: center; gap: 10px; }
        .modal-close { background: none; border: none; font-size: 1.5rem; color: #636b99; cursor: pointer; transition: color 0.2s; padding: 5px; line-height: 1; }
        .modal-close:hover { color: #ef4444; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.75rem; color: #636b99; margin-bottom: 0.5rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; }
        .form-control { 
            width: 100%; background: rgba(0,0,0,0.3) !important; border: 1px solid #2a2e3f !important;
            border-radius: 12px; padding: 0.75rem 1rem; color: #f0f2ff !important; outline: none; transition: all 0.2s;
            font-family: inherit; font-size: 0.9rem;
        }
        .form-control:focus { border-color: #6366f1 !important; background: rgba(0,0,0,0.4) !important; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        
        .tabs { display: flex; margin-bottom: 1.5rem; border: 1px solid #2a2e3f; border-radius: 14px; background: rgba(0,0,0,0.25); padding: 5px; }
        .tab-btn { 
            flex: 1; padding: 10px; border: none; background: transparent; color: #9da3c8; border-radius: 10px; 
            cursor: pointer; font-weight: 700; font-size: 0.85rem; display:flex; align-items:center; justify-content:center; gap:8px;
            transition: all 0.2s;
        }
        .tab-btn.active { background: #6366f1; color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .tab-btn:hover:not(.active) { color: white; background: rgba(255,255,255,0.05); }

        /* SHARE MODAL SPECIFIC */
        .copy-input-group {
            display: flex; gap: 8px; margin: 1.5rem 0; background: rgba(0,0,0,0.25);
            padding: 8px; border-radius: 12px; border: 1px solid var(--border);
        }
        #share_url_input {
            background: transparent; border: none; color: #9da3c8; flex: 1;
            padding: 4px 8px; font-family: monospace; font-size: 0.85rem; outline: none;
        }

        .search-item {
            padding: 12px 16px; border: 1px solid rgba(255,255,255,0.05); border-radius: 14px; 
            cursor: pointer; transition: all 0.2s; background: rgba(255,255,255,0.02); margin-bottom: 10px;
        }
        .search-item:hover { border-color: #6366f1; background: rgba(99, 102, 241, 0.1); transform: translateY(-1px); }

        .w-full { width: 100%; }
        .mt-4 { margin-top: 1.5rem; }
        .mb-0 { margin-bottom: 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .toolbar-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            outline: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .toolbar-select option {
            background: #2d3748;
            color: white;
        }

        /* APP CONTENT */
        .app-main {
            margin-top: 70px;
            height: calc(100vh - 70px);
            overflow-y: auto;
            padding: 2rem;
            display: flex;
            justify-content: center;
            background: #111827;
        }

        /* DOCUMENT PREVIEW */
        .printable-document {
            width: 210mm;
            min-height: 296mm;
            background: white;
            padding: 15mm;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            color: #333;
            font-family: 'Outfit', sans-serif;
            position: relative;
            border-radius: 4px;
            margin-bottom: 3rem;
            transform-origin: top center;
        }

        [contenteditable="true"]:focus { background: rgba(0,119,200,0.05); outline: none; border-radius: 4px; }
        [contenteditable="true"]:hover { background: rgba(0,119,200,0.03); border-radius: 4px; }

        /* PRINT OPTIMIZATION */
        @media print {
            @page { size: auto; margin: 0mm; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .app-toolbar { display: none !important; }
            .app-main { margin-top: 0; padding: 0; background: white; overflow: visible; }
            .printable-document { 
                box-shadow: none; 
                margin: 0; 
                padding: 15mm; 
                width: 210mm; 
                height: 296mm;
                transform: none !important;
            }
            body { background: white; }
            .remove-row, #clear_signature { display: none !important; }
            /* Hide the dropdown arrow in print for select if possible */
            select { -webkit-appearance: none; -moz-appearance: none; appearance: none; }
        }

        /* MOBILE & TABLET OPTIMIZATIONS */
        @media screen and (max-width: 768px) {
            .app-toolbar {
                height: auto;
                flex-direction: column;
                padding: 0.75rem;
                gap: 0.75rem;
            }
            .toolbar-left, .toolbar-right, .toolbar-center {
                width: 100%;
                justify-content: space-between;
                gap: 0.5rem;
            }
            .toolbar-center {
                order: 3;
                background: rgba(255,255,255,0.03);
                padding: 0.5rem;
                border-radius: 10px;
            }
            .app-main {
                margin-top: 70px; /* Thinner toolbar */
                padding: 1rem 0;
                height: calc(100vh - 70px);
                overflow: auto;
                background: #0f1117;
                display: block;
                -webkit-overflow-scrolling: touch;
            }
            .hide-mobile { display: none !important; }
            .nav-text-mobile { font-size: 1rem !important; }
            
            .printable-document {
                zoom: 0.85; /* Better readability, allows scrolling */
                width: 210mm !important;
                min-width: 210mm !important;
                margin: 0 auto 2rem auto;
                transform-origin: top center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            }
            
            .app-toolbar {
                height: 70px;
                padding: 0 1rem;
                gap: 0.5rem;
                flex-direction: row; /* Keep it horizontal */
                flex-wrap: nowrap;
            }
            .toolbar-center {
                order: 2;
                background: none;
                padding: 0;
                gap: 0.4rem;
            }
            .toolbar-left, .toolbar-right { width: auto; }
            .toolbar-select { font-size: 0.8rem; padding: 0.3rem 0.5rem; }
        }

        @media screen and (min-width: 769px) and (max-width: 1600px) { .printable-document { zoom: 0.85; } }
        @media screen and (min-width: 769px) and (max-width: 1400px) { .printable-document { zoom: 0.75; } }
            .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            display: none; align-items: center; justify-content: center;
            z-index: 999;
        }
    </style>
</head>
<body>
    <header class="app-toolbar">
        <div class="toolbar-left">
            <a href="contact.php?id=<?= $lead_id ?>" class="btn btn-secondary" title="Späť">
                <i class="ti ti-arrow-left"></i> <span class="hide-mobile">Späť</span>
            </a>
            <div class="nav-text-mobile" style="font-weight: 700; font-size: 1.1rem; color: var(--accent); letter-spacing: -0.01em;">Cenová ponuka</div>
        </div>

        <div class="toolbar-center">
            <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                <span class="hide-mobile" style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Typ ponuky:</span>
                <select id="offer_type_select" class="toolbar-select" style="background: transparent; border: none; color: white; outline: none; font-weight: 700; cursor: pointer;">
                    <option value="orientational" style="background: #1e293b;">Orientačná</option>
                    <option value="final" style="background: #1e293b;">Finálna</option>
                </select>
            </div>
            <button class="btn btn-secondary" id="add_item_btn">
                <i class="ti ti-plus"></i> <span class="hide-mobile">Pridať položku</span>
            </button>
        </div>

        <div class="toolbar-right" style="display: flex; align-items: center; gap: 8px;">
            <button class="btn-toolbar btn-secondary" onclick="window.saveDocument(false, 'draft', false)" id="save_draft_btn" title="Uložiť">
                <i class="ti ti-device-floppy"></i> <span class="hide-mobile">Uložiť</span>
            </button>
            <?php if($edit_token): ?>
            <button class="btn-toolbar" style="background: #8b5cf6;" onclick="window.duplicateDocument()" title="Vytvoriť kópiu (ako novú ponuku)">
                <i class="ti ti-copy"></i> <span class="hide-mobile">Duplikovať</span>
            </button>
            <?php endif; ?>
            <button class="btn-toolbar btn-primary" onclick="window.saveDocument(false, 'sent', true)" id="email_document_btn" title="Odoslať mailom" <?= $lead_id === 0 ? 'disabled' : '' ?>>
                <i class="ti ti-mail"></i> <span class="hide-mobile">Cez mail</span>
            </button>
            <button class="btn-toolbar" style="background: #6366f1;" onclick="window.saveDocument(true, 'sent', false)" id="share_document_btn" title="Zdielať link (WhatsApp a iné)" <?= $lead_id === 0 ? 'disabled' : '' ?>>
                <i class="ti ti-share"></i> <span class="hide-mobile">Zdielať</span>
            </button>
            <button class="btn-toolbar btn-success" onclick="window.print()" title="Tlačiť / PDF">
                <i class="ti ti-printer"></i> <span class="hide-mobile">Tlačiť</span>
            </button>
        </div>
    </header>

    <div id="duplicateWarning" style="display:none; background: #fffbeb; border-bottom: 1px solid #fde68a; padding: 10px 20px; font-size: 0.9rem; color: #92400e; position: fixed; top: 70px; left: 0; right: 0; z-index: 99;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 15px;">
            <i class="ti ti-alert-circle" style="font-size: 1.2rem;"></i>
            <span id="duplicateWarningMsg"></span>
            <button onclick="this.parentElement.parentElement.style.display='none'" style="background: none; border: none; cursor: pointer; color: #92400e; font-weight: bold;">✕</button>
        </div>
    </div>

    <main class="app-main">
        <div id="offer_document" class="printable-document">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <div style="width: 150px;">
                    <img src="vueto_logo.png" style="max-height: 50px; max-width: 100%; object-fit: contain;">
                </div>
                <div style="text-align: right;">
                    <h1 style="color: #0077c8; margin: 0; font-size: 2.25rem; font-weight: 800; text-transform: uppercase;">Cenová ponuka</h1>
                    <div style="font-size: 1.1rem; color: #666; margin-top: 2px;">
                        # <span id="offer_number" contenteditable="true" style="border-bottom: 1px dotted #ccc; outline: none; min-width: 80px; display: inline-block;"><?= date('Y') ?>001</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 30px; margin-bottom: 30px;">
                <div style="flex: 1; border: 1px solid #f1f5f9; border-radius: 8px; padding: 12px;">
                    <div style="background: #f8fafc; color: #475569; padding: 4px 10px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; margin-bottom: 10px;">
                        Dodávateľ
                    </div>
                    <div style="padding: 0 10px; line-height: 1.5; font-size: 0.9rem;">
                        <div contenteditable="true" style="font-weight: 800; font-size: 1rem; margin-bottom: 2px; outline: none;">CONTRACTOR GROUPS s.r.o.</div>
                        <div contenteditable="true" style="outline: none;">Jabloňová 850/77</div>
                        <div contenteditable="true" style="outline: none;">010 04 Žilina</div>
                    </div>
                </div>

                <div style="flex: 1; border: 1px solid #f1f5f9; border-radius: 8px; padding: 12px;">
                    <div style="background: #f8fafc; color: #475569; padding: 4px 10px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; margin-bottom: 10px;">
                        Odberateľ
                    </div>
                    <div style="padding: 0 10px; line-height: 1.5; font-size: 0.9rem;">
                        <div contenteditable="true" style="font-weight: 800; font-size: 1rem; margin-bottom: 2px; outline: none; color: #0077c8;"><?= $client ? htmlspecialchars($client['name']) : 'Meno klienta' ?></div>
                        <?php if($client && !empty($client['company'])): ?>
                            <div contenteditable="true" style="outline: none;"><?= htmlspecialchars($client['company']) ?></div>
                        <?php endif; ?>
                        <div contenteditable="true" style="outline: none;"><?= ($client && $client['address']) ? htmlspecialchars($client['address']) : 'Ulica a číslo' ?></div>
                        <div contenteditable="true" style="outline: none;"><?= ($client && $client['city']) ? htmlspecialchars($client['city']) : 'Mesto' ?></div>
                    </div>
                </div>
            </div>

            <div style="background: #fafafa; padding: 12px 15px; border-radius: 8px; display: flex; gap: 40px; margin-bottom: 30px; border: 1px solid #eee;">
                <div style="font-size: 0.85rem;">
                    <span style="color: #64748b; font-weight: 700; text-transform: uppercase; margin-right: 8px;">Dátum vystavenia:</span>
                    <input type="text" id="offer_date" value="<?= date('d.m.Y') ?>" style="border: none; background: transparent; outline: none; border-bottom: 1px dotted #cbd5e1; width: 100px; font-family: inherit; font-size: inherit; font-weight: 600;">
                </div>
                <div style="font-size: 0.85rem;">
                    <span style="color: #64748b; font-weight: 700; text-transform: uppercase; margin-right: 8px;">Platnosť dokumentu do:</span>
                    <input type="text" id="offer_validity" value="<?= date('d.m.Y', strtotime('+14 days')) ?>" style="border: none; background: transparent; outline: none; border-bottom: 1px dotted #cbd5e1; width: 100px; font-family: inherit; font-size: inherit; font-weight: 600;">
                </div>
            </div>

            <table id="items_table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <thead>
                    <tr style="background: #0077c8; color: white;">
                        <th style="padding: 12px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Popis položky alebo služby</th>
                        <th style="padding: 12px; text-align: center; font-size: 0.75rem; text-transform: uppercase; width: 60px;">Množ.</th>
                        <th style="padding: 12px; text-align: center; font-size: 0.75rem; text-transform: uppercase; width: 45px;">Jedn.</th>
                        <th id="price_header" style="padding: 12px; text-align: right; font-size: 0.75rem; text-transform: uppercase; width: 100px;">Bez DPH</th>
                        <th class="vat-col" style="padding: 12px; text-align: center; font-size: 0.75rem; text-transform: uppercase; width: 60px;">DPH</th>
                        <th id="total_header" style="padding: 12px; text-align: right; font-size: 0.75rem; text-transform: uppercase; width: 110px;">Spolu</th>
                    </tr>
                </thead>
                <tbody id="items_body">
                    <!-- Položky sa pridávajú cez JS -->
                </tbody>
            </table>

            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex: 1; padding-right: 60px; margin-top: 10px;">
                    <table id="vat_breakdown" style="width: 100%; font-size: 0.8rem; color: #64748b;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <th style="text-align: left; padding: 8px;">Sadzba dane</th>
                                <th style="text-align: right; padding: 8px;">Základ dane</th>
                                <th style="text-align: right; padding: 8px;">Výška DPH</th>
                                <th style="text-align: right; padding: 8px;">Spolu</th>
                            </tr>
                        </thead>
                        <tbody id="vat_summary"></tbody>
                    </table>
                    <div id="non_vat_msg" style="font-size: 0.85rem; color: #64748b; font-style: italic; display: none;">
                        Neobsahuje položky s DPH.
                    </div>
                </div>

                <div style="width: 280px; background: #2563eb !important; color: white !important; padding: 20px; border-radius: 12px; text-align: right; box-shadow: 0 10px 25px rgba(37,99,235,0.25); border: 2px solid #2563eb;">
                    <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700; opacity: 0.9; margin-bottom: 4px; color: white !important;">Suma spolu</div>
                    <div style="font-size: 2rem; font-weight: 800; color: white !important;">
                        <span id="offer_total" style="color: white !important;">0.00</span> <span style="font-size: 1.2rem; margin-left: 5px; color: white !important;">€</span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 60px; display: flex; justify-content: space-between; align-items: flex-end; gap: 20px;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #475569; font-size: 0.9rem;">
                        <i class="ti ti-user" style="color: #0077c8; font-size: 1.2rem;"></i>
                        <span contenteditable="true" style="outline:none; font-weight: 600;"><?= htmlspecialchars($user['full_name']) ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #475569; font-size: 0.9rem;">
                        <i class="ti ti-mail" style="color: #0077c8; font-size: 1.2rem;"></i>
                        <span contenteditable="true" style="outline:none; text-decoration: none; color: inherit;"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                </div>

                <div style="width: 200px; text-align: center;">
                    <input type="hidden" name="signature_data" id="signature_data">
                    <div id="sig_customer_display"></div>
                    <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-top: 5px; color: #94a3b8;">Za odberateľa</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="quickAddModal" class="modal-overlay">
        <div class="modal-box" style="width: 500px; max-width: 95%;">
            <div class="modal-header">
                <div class="modal-title" style="display: flex; align-items: center; gap: 8px;"><i class="ti ti-users"></i> Vybrať alebo pridať klienta</div>
                <button class="modal-close" onclick="closeQuickAddModal()">✕</button>
            </div>
            
            <div class="tabs" style="margin-bottom: 20px;">
                <button id="tab-search-btn" class="tab-btn active" onclick="switchPickerTab('search')" style="flex:1;"><i class="ti ti-search"></i> Hľadať v CRM</button>
                <button id="tab-add-btn" class="tab-btn" onclick="switchPickerTab('add')" style="flex:1;"><i class="ti ti-plus"></i> Rýchlo pridať</button>
            </div>

            <div class="tab-pane active" id="pane-search" style="display:block;">
                <div class="form-group" style="position:relative;">
                    <i class="ti ti-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                    <input type="text" class="form-control" placeholder="Meno, firma alebo email..." style="padding-left: 40px;" onkeyup="searchLeads(this.value)">
                </div>
                <div id="searchResults" style="max-height:280px; overflow-y:auto; padding-right:5px;">
                    <div style="text-align:center; color:var(--text-muted); padding:20px; font-size:0.9rem;">Začnite písať pre vyhľadávanie...</div>
                </div>
            </div>

            <div class="tab-pane" id="pane-add" style="display:none;">
                <div class="form-group">
                    <label class="form-label">Meno a priezvisko</label>
                    <input type="text" id="qName" class="form-control" placeholder="napr. Jozef Mrkva">
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input type="email" id="qEmail" class="form-control" placeholder="jozef@email.sk">
                </div>
                <div class="form-group">
                    <label class="form-label">Telefón</label>
                    <input type="text" id="qPhone" class="form-control" placeholder="+421 9xx xxx xxx">
                </div>
                <div class="grid-2">
                    <div class="form-group mb-0">
                        <label class="form-label">Ulica a číslo</label>
                        <input type="text" id="qAddress" class="form-control" placeholder="Jabloňová 8">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Mesto (a PSČ)</label>
                        <input type="text" id="qCity" class="form-control" placeholder="Žilina">
                    </div>
                </div>
                <button id="qSubmitBtn" onclick="submitQuickAddLead()" class="btn btn-primary w-full mt-4" style="justify-content:center;">
                    <i class="ti ti-user-plus"></i> Vytvoriť a vybrať
                </button>
            </div>
        </div>
    </div>

    <div id="shareModal" class="modal-overlay">
        <div class="modal-box" style="text-align: center;">
            <div class="modal-header">
                <div class="modal-title" id="shareModalTitle">Uložené</div>
                <button class="modal-close" onclick="closeShareModal()">✕</button>
            </div>
            
            <div id="shareModalMsg" style="color: #9da3c8; margin-bottom: 1rem; font-size: 0.95rem;">
                Odkaz na dokument bol vygenerovaný a je pripravený na zdieľanie.
            </div>

            <div class="copy-input-group" id="shareInputGroup" style="display: none;">
                <input type="text" id="share_url_input" readonly>
                <button class="btn btn-success" onclick="copyShareUrl()" style="height: 32px; font-size: 0.75rem; padding: 0 12px;">
                    <i class="ti ti-copy"></i> Kopírovať
                </button>
            </div>

            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 2rem;">
                <button class="btn btn-secondary" onclick="closeShareModal()" style="min-width: 120px;">Zavrieť</button>
                <a href="#" id="open_link_btn" target="_blank" class="btn btn-primary" style="min-width: 120px; background: #6366f1; display: none;">
                    <i class="ti ti-external-link"></i> Otvoriť
                </a>
            </div>
        </div>
    </div>

    <!-- Generic Confirmation Modal -->
    <div id="confirmModal" class="modal-overlay" style="z-index: 10000;">
        <div class="modal-box" style="text-align: center; max-width: 400px;">
            <div class="modal-header">
                <div class="modal-title" id="confirmModalTitle">Potvrdenie</div>
                <button class="modal-close" onclick="closeConfirmModal()">✕</button>
            </div>
            <div id="confirmModalMsg" style="color: #9da3c8; margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.5;"></div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn btn-secondary" onclick="closeConfirmModal()" style="flex: 1;">Zrušiť</button>
                <button id="confirmModalBtn" class="btn btn-primary" style="flex: 1; background: var(--accent);">Potvrdiť</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/signature_modal.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.leadId = <?= $lead_id ?>;
            window.editToken = "<?= $edit_token ?>";

            // Auto-open client picker if no client selected
            if (window.leadId === 0) {
                setTimeout(() => window.showQuickAddModal(), 100);
            }
            const draftData = <?= json_encode($draft_data) ?>;
            const items_body = document.getElementById('items_body');

            if (window.leadId && !window.editToken) {
                fetch(`ajax_get_doc_info.php?lead_id=${window.leadId}&type=offer`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const numEl = document.getElementById('offer_number');
                            if (numEl) numEl.innerText = data.next_number;
                            
                            if (data.already_exists) {
                                const latest = data.existing_docs[0];
                                const dateStr = new Date(latest.created_at).toLocaleDateString('sk-SK');
                                const warnMsg = document.getElementById('duplicateWarningMsg');
                                if (warnMsg) warnMsg.innerHTML = 
                                    `Pozor, tomuto klientovi už bola uložená <strong><a href="offer_generator.php?id=${window.leadId}&edit_t=${latest.token}" style="color:#b45309; text-decoration:underline;">Cenová ponuka (${latest.doc_number || 'bez čísla'})</a></strong> dňa ${dateStr}.`;
                                const warnWrap = document.getElementById('duplicateWarning');
                                if (warnWrap) warnWrap.style.display = 'block';
                            }
                        }
                    });
            }

            function parseValue(val) {
                if (!val) return 0;
                let clean = val.toString().replace(/\s/g, '').replace(/&nbsp;/g, '').replace(',', '.').trim();
                let parsed = parseFloat(clean);
                return isNaN(parsed) ? 0 : parsed;
            }

            window.recalculate = function() {
                let sum_base = 0;
                let sum_vat = 0;
                let has_vat_items = false;
                const rows = items_body.querySelectorAll('.item-row');

                rows.forEach(row => {
                    const qty = parseValue(row.querySelector('.qty').innerText) || 1;
                    const price = parseValue(row.querySelector('.unit-price').innerText);
                    
                    const vat_select = row.querySelector('.vat-rate');
                    const vat_rate = parseFloat(vat_select.value) || 0;
                    
                    if (vat_rate > 0) has_vat_items = true;

                    const base = qty * price;
                    const vat = base * (vat_rate / 100);
                    const row_total = base + vat;

                    row.querySelector('.row-total').innerText = row_total.toLocaleString('sk-SK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    
                    sum_base += base;
                    sum_vat += vat;
                });

                const grand_total = sum_base + sum_vat;
                document.getElementById('offer_total').innerText = grand_total.toLocaleString('sk-SK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const vat_summary_table = document.getElementById('vat_summary');
                vat_summary_table.innerHTML = '';
                
                if (has_vat_items) {
                    document.getElementById('vat_breakdown').style.display = 'table';
                    document.getElementById('non_vat_msg').style.display = 'none';
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="padding: 8px; font-weight: 600;">23%</td>
                        <td style="padding: 8px; text-align: right;">${sum_base.toLocaleString('sk-SK', { minimumFractionDigits: 2 })} €</td>
                        <td style="padding: 8px; text-align: right;">${sum_vat.toLocaleString('sk-SK', { minimumFractionDigits: 2 })} €</td>
                        <td style="padding: 8px; text-align: right; font-weight: 600; color: #333;">${grand_total.toLocaleString('sk-SK', { minimumFractionDigits: 2 })} €</td>
                    `;
                    vat_summary_table.appendChild(tr);
                } else {
                    document.getElementById('vat_breakdown').style.display = 'none';
                    document.getElementById('non_vat_msg').style.display = 'block';
                }
            };

            window.addRow = function(data = null) {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #f1f5f9';
                tr.className = 'item-row';
                
                const is_zero = data && data.vat === '0';
                
                tr.innerHTML = `
                    <td style="padding: 12px; position: relative;">
                        <div contenteditable="true" class="item-desc" style="outline: none; font-size: 0.9rem;">${data ? data.description : 'Popis položky'}</div>
                        <button class="remove-row" style="position: absolute; left: -30px; top: 12px; border: none; background: none; color: #ef4444; cursor: pointer; padding: 0; display: flex; align-items: center;" onclick="this.closest('tr').remove(); window.recalculate();"><i class="ti ti-trash" style="font-size: 1rem;"></i></button>
                    </td>
                    <td style="padding: 12px; text-align: center;"><div contenteditable="true" class="qty" style="outline: none; font-size: 0.9rem;">${data ? data.qty : '1'}</div></td>
                    <td style="padding: 12px; text-align: center;"><div contenteditable="true" style="outline: none; font-size: 0.9rem;">${data ? data.unit : 'ks'}</div></td>
                    <td style="padding: 12px; text-align: right;"><div contenteditable="true" class="unit-price" style="outline: none; font-size: 0.9rem;">${data ? data.price : '0.00'}</div></td>
                    <td style="padding: 12px; text-align: center;">
                        <select class="vat-rate" style="outline: none; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; background: #f8fafc; cursor: pointer; color: #0077c8; font-weight: 700; text-align: center; margin: 0 auto; display: block;">
                            <option value="23" ${!is_zero ? 'selected' : ''}>23%</option>
                            <option value="0" ${is_zero ? 'selected' : ''}>0%</option>
                        </select>
                    </td>
                    <td style="padding: 12px; text-align: right; font-weight: 700; white-space: nowrap;"><span class="row-total" style="font-size: 0.9rem;">0.00</span> <span style="font-size: 0.75rem;">€</span></td>
                `;
                items_body.appendChild(tr);
                // Listen to VAT change
                tr.querySelector('.vat-rate').addEventListener('change', window.recalculate);
                window.recalculate();
            };

            document.getElementById('add_item_btn').addEventListener('click', () => window.addRow());
            items_body.addEventListener('input', window.recalculate);

            if (draftData) {
                if (draftData.number) document.getElementById('offer_number').innerText = draftData.number;
                if (draftData.date_issued) document.getElementById('offer_date').value = draftData.date_issued;
                if (draftData.date_valid) document.getElementById('offer_validity').value = draftData.date_valid;
                if (draftData.offer_type) document.getElementById('offer_type_select').value = draftData.offer_type;
                
                // Načítanie podpisu ak existuje
                if (draftData.signature_data) {
                    const sigInput = document.getElementById('signature_data');
                    const sigPreview = document.getElementById('sig_preview_img');
                    const sigPlaceholder = document.getElementById('sig_placeholder_modal');
                    if (sigInput) sigInput.value = draftData.signature_data;
                    if (sigPreview) {
                        sigPreview.src = draftData.signature_data;
                        sigPreview.style.display = 'block';
                    }
                    if (sigPlaceholder) sigPlaceholder.style.display = 'none';
                }

                const itemsToLoad = draftData.items || (Array.isArray(draftData) ? draftData : null);
                if (itemsToLoad && Array.isArray(itemsToLoad)) {
                    items_body.innerHTML = '';
                    itemsToLoad.forEach(item => window.addRow(item));
                }
            } else {
                window.addRow(); // Initial row
            }


            window.saveDocument = function(showUrl = false, status = 'sent', sendEmail = false) {
                if (!window.leadId || window.leadId === 0) {
                    alert("Pred uložením alebo odoslaním vyberte klienta.");
                    return;
                }

                const btn = showUrl ? document.getElementById('share_document_btn') : document.getElementById(status === 'draft' ? 'save_draft_btn' : 'email_document_btn');
                const originalContent = btn.innerHTML;
                
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="ti ti-loader-2 rotate"></i> Ukladám...';

                    const editorData = {
                        number: document.getElementById('offer_number').innerText,
                        date_issued: document.getElementById('offer_date').value,
                        date_valid: document.getElementById('offer_validity').value,
                        offer_type: document.getElementById('offer_type_select').value,
                        signature_data: document.getElementById('signature_data') ? document.getElementById('signature_data').value : '',
                        items: []
                    };

                    document.querySelectorAll('.item-row').forEach(row => {
                        editorData.items.push({
                            description: row.querySelector('.item-desc').innerText,
                            qty: row.querySelector('.qty').innerText,
                            unit: row.querySelectorAll('td')[2].innerText,
                            price: row.querySelector('.unit-price').innerText,
                            vat: row.querySelector('.vat-rate').value
                        });
                    });

                    const docClone = document.getElementById('offer_document').cloneNode(true);
                    docClone.querySelectorAll('.remove-row, #clear_signature').forEach(el => el.remove());
                    docClone.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'));
                    const originalElements = document.getElementById('offer_document').querySelectorAll('input, select');
                    
                    docClone.querySelectorAll('input, select').forEach((el, idx) => {
                        if (el.type === 'hidden') return;
                        const original = originalElements[idx];
                        const replacement = document.createElement('div');
                        replacement.style.display = 'block';
                        replacement.style.width = '100%';
                        
                        if (el.tagName === 'SELECT') {
                            replacement.innerText = original.options[original.selectedIndex].text;
                        } else {
                            replacement.innerText = original.value;
                        }
                        el.parentNode.replaceChild(replacement, el);
                    });

                    // Vyčistenie sekcie podpisu v klone pre klienta
                    const sigImg = docClone.querySelector('#sig_preview_img');
                    const sigTrigger = docClone.querySelector('#sig_trigger');
                    const sigPlaceholder = docClone.querySelector('#sig_placeholder_modal');
                    
                    if (sigImg && (!sigImg.src || sigImg.src === window.location.href || sigImg.src.length < 100)) {
                        if (sigTrigger) sigTrigger.remove(); // Ak nie je podpísané, odstránime celú sekciu
                    } else {
                        if (sigPlaceholder) sigPlaceholder.remove(); // Ak je podpísané, odstránime inštrukciu "Kliknite sem"
                        if (sigTrigger) {
                            sigTrigger.removeAttribute('onclick');
                            sigTrigger.style.cursor = 'default';
                            sigTrigger.style.border = 'none';
                            sigTrigger.style.background = 'transparent';
                        }
                        if (sigImg) sigImg.style.display = 'block';
                    }

                    const data = new FormData();
                    data.append('lead_id', window.leadId);
                    data.append('type', 'offer');
                    data.append('status', status);
                    data.append('send_email', sendEmail ? '1' : '0');
                    data.append('doc_number', editorData.number);
                    data.append('title', 'Cenová ponuka ' + editorData.number);
                    if (window.editToken) data.append('token', window.editToken);
                    data.append('content', JSON.stringify({ 
                        html: docClone.innerHTML,
                        editor_data: editorData 
                    }));

                    fetch('save_document.php', { method: 'POST', body: data })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            if (!window.editToken) window.editToken = res.token;
                            if (showUrl) {
                                window.showShareModal(res.url);
                            } else if (status === 'draft') {
                                window.showShareModal(null, "Dokument bol úspešne uložený.");
                            } else {
                                window.showShareModal(null, "Cenová ponuka bola úspešne odoslaná.");
                            }
                        } else {
                            alert("Chyba: " + res.error);
                        }
                    })
                    .catch(err => alert("Chyba spojenia: " + err))
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    });
                } catch (e) {
                    console.error(e);
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            };

            window.showConfirm = function(msg, title, onConfirm) {
                document.getElementById('confirmModalTitle').innerText = title || 'Potvrdenie';
                document.getElementById('confirmModalMsg').innerText = msg;
                const btn = document.getElementById('confirmModalBtn');
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                newBtn.addEventListener('click', () => {
                    closeConfirmModal();
                    onConfirm();
                });
                document.getElementById('confirmModal').classList.add('open');
            };
            window.closeConfirmModal = () => document.getElementById('confirmModal').classList.remove('open');

            window.duplicateDocument = function(keepItems = true) {
                const msg = keepItems ? "Chcete vytvoriť novú ponuku na základe tejto aktuálnej? Pôvodná ponuka zostane zachovaná." : "Chcete začať úplne novú prázdnu ponuku pre tohto klienta?";
                window.showConfirm(msg, "Duplikovať ponuku", () => {
                    // Clear the edit token and get a new number
                    window.editToken = null;
                    const url = new URL(window.location.href);
                    url.searchParams.delete('edit_t');
                    window.history.pushState({}, '', url);

                    if (!keepItems) {
                        const ib = document.getElementById('items_body');
                        if (ib) {
                            ib.innerHTML = '';
                            window.addRow();
                        }
                    }

                    // Fetch next number
                    fetch(`ajax_get_doc_info.php?lead_id=${window.leadId}&type=offer`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('offer_number').innerText = data.next_number;
                                document.getElementById('duplicateWarning').style.display = 'none';
                                // Trigger save as new draft
                                window.saveDocument(false, 'draft', false);
                            }
                        });
                });
            };

            window.startFreshOffer = function() {
                window.duplicateDocument(false);
            };


            // Modal logic
            window.showQuickAddModal = () => document.getElementById('quickAddModal').classList.add('open');
            window.closeQuickAddModal = () => document.getElementById('quickAddModal').classList.remove('open');
            
            window.switchPickerTab = function(tab) {
                document.getElementById('pane-search').style.display = tab === 'search' ? 'block' : 'none';
                document.getElementById('pane-add').style.display = tab === 'add' ? 'block' : 'none';
                document.getElementById('tab-search-btn').classList.toggle('active', tab === 'search');
                document.getElementById('tab-add-btn').classList.toggle('active', tab === 'add');
            };

            window.searchLeads = function(query) {
                const resultsArea = document.getElementById('searchResults');
                if (query.length < 2) {
                    resultsArea.innerHTML = '<div style="text-align:center; color:#94a3b8; padding:20px; font-size:0.9rem;">Začnite písať pre vyhľadávanie...</div>';
                    return;
                }
                const data = new FormData(); data.append('query', query);
                fetch('ajax_search_leads.php', { method: 'POST', body: data })
                .then(res => res.json()).then(leads => {
                    if (leads.length === 0) { resultsArea.innerHTML = '<div style="text-align:center; color:#94a3b8; padding:20px; font-size:0.9rem;">Nenašli sa žiadni klienti.</div>'; return; }
                    resultsArea.innerHTML = leads.map(l => `
                        <div onclick="window.selectLead(${l.id})" class="search-item">
                            <div style="font-weight:700; color:white; text-align:left; font-size:0.95rem;">${l.name}</div>
                            <div style="font-size:0.75rem; color:#9ca3af; text-align:left; margin-top:2px;">${l.company || ''} ${l.city ? '• ' + l.city : ''}</div>
                            <div style="font-size:0.75rem; color:#64748b; text-align:left;">${l.email}</div>
                        </div>
                    `).join('');
                });
            };

            window.selectLead = function(id) {
                const url = new URL(window.location.href);
                url.searchParams.set('id', id);
                window.location.href = url.toString();
            };

            window.submitQuickAddLead = function() {
                const btn = document.getElementById('qSubmitBtn');
                const originalText = btn.innerHTML;
                const name = document.getElementById('qName').value.trim();
                const email = document.getElementById('qEmail').value.trim();
                const phone = document.getElementById('qPhone').value.trim();
                const address = document.getElementById('qAddress').value.trim();
                const city = document.getElementById('qCity').value.trim();
                
                if (!name || !email) { alert("Meno a E-mail sú povinné."); return; }
                btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader-2 rotate"></i> Ukladám...';
                const data = new FormData(); data.append('name', name); data.append('email', email); data.append('phone', phone); data.append('address', address); data.append('city', city);
                fetch('ajax_quick_add_lead.php', { method: 'POST', body: data })
                .then(res => res.json()).then(res => {
                    if (res.success) window.selectLead(res.id);
                    else { alert("Chyba: " + (res.error || "Nepodarilo sa vytvoriť.")); btn.disabled = false; btn.innerHTML = originalText; }
                }).catch(err => { alert("Chyba siete."); btn.disabled = false; btn.innerHTML = originalText; });
            };

            const shareModal = document.getElementById('shareModal');
            window.showShareModal = function(url, message = null) {
                const titleEl = document.getElementById('shareModalTitle');
                const msgEl = document.getElementById('shareModalMsg');
                const inputGroup = document.getElementById('shareInputGroup');
                const urlInput = document.getElementById('share_url_input');
                const openBtn = document.getElementById('open_link_btn');

                if (url) {
                    titleEl.innerText = "Dokument je pripravený";
                    msgEl.innerText = "Odkaz na online verziu bol vygenerovaný. Môžete ho poslať klientovi.";
                    inputGroup.style.display = 'flex';
                    urlInput.value = url;
                    openBtn.style.display = 'inline-flex';
                    openBtn.href = url;
                } else {
                    titleEl.innerText = "Uložené";
                    msgEl.innerText = message || "Zmeny boli uložené.";
                    inputGroup.style.display = 'none';
                    openBtn.style.display = 'none';
                }
                shareModal.classList.add('open');
            };

            window.closeShareModal = () => shareModal.classList.remove('open');
            window.copyShareUrl = function() {
                document.getElementById('share_url_input').select();
                document.execCommand('copy');
                const copyBtn = document.querySelector('#shareInputGroup .btn-success');
                const originalHtml = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="ti ti-check"></i> Skopírované!';
                setTimeout(() => { copyBtn.innerHTML = originalHtml; }, 2000);
            };
        });
    </script>
</body>
</html>


