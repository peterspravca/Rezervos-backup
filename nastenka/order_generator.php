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
        $draft_data = $content['editor_data'] ?? null;
        $lead_id = $doc['lead_id'];
    }
}

$page_title = $draft_data ? 'Úprava objednávky' : 'Nová objednávka';
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
            margin: 0; padding: 0; height: 100%;
            font-family: 'Outfit', sans-serif;
            background-color: #1f2937;
            color: var(--text-primary);
            overflow: hidden;
        }

        .app-toolbar {
            height: 70px; background: var(--bg-toolbar); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        }

        .toolbar-left, .toolbar-right, .toolbar-center { display: flex; align-items: center; gap: 1rem; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
            padding: 0 1.15rem; height: 38px; border-radius: 10px;
            font-family: inherit; font-size: .875rem; font-weight: 600;
            cursor: pointer; border: 1px solid transparent; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;
            line-height: 1; white-space: nowrap; color: inherit;
        }

        .btn:active { transform: scale(0.96); }

        .btn-secondary { background: rgba(255,255,255,0.05); border: 1px solid var(--border); }
        .btn-primary { background: var(--accent); color: white; }
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

        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            display: none; align-items: center; justify-content: center;
            z-index: 999;
        }
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
        .tab-btn.active { background: #6366f1; color: white; box-shadow: 0 8px 16px rgba(99, 102, 241, 0.25); }
        .tab-btn:hover:not(.active) { color: white; background: rgba(255,255,255,0.05); }

        .search-item {
            padding: 12px 16px; border: 1px solid rgba(255,255,255,0.05); border-radius: 14px; 
            cursor: pointer; transition: all 0.2s; background: rgba(255,255,255,0.02); margin-bottom: 10px;
        }
        .search-item:hover { border-color: #6366f1; background: rgba(99, 102, 241, 0.1); transform: translateY(-1px); }

        .w-full { width: 100%; }
        .mt-4 { margin-top: 1.5rem; }
        .mb-0 { margin-bottom: 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .app-main {
            margin-top: 70px; height: calc(100vh - 70px);
            overflow-y: auto; padding: 2rem; display: flex; justify-content: center;
            background: #111827; -webkit-overflow-scrolling: touch;
        }

        .printable-document {
            width: 210mm; min-height: 296mm; background: white;
            padding: 15mm; box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            color: #333; font-family: 'Outfit', sans-serif;
            position: relative; border-radius: 4px; margin-bottom: 3rem;
            transform-origin: top center;
        }

        [contenteditable="true"]:focus { background: rgba(37,99,235,0.05); outline: none; border-radius: 4px; }
        [contenteditable="true"]:hover { background: rgba(37,99,235,0.03); border-radius: 4px; }

        @media print {
            @page { size: auto; margin: 0mm; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .app-toolbar { display: none !important; }
            .app-main { margin-top: 0; padding: 0; background: white; overflow: visible; }
            .printable-document { box-shadow: none; margin: 0; padding: 15mm; width: 210mm; height: 296mm; transform: none !important; }
            body { background: white; }
            .remove-row, .clear-sig { display: none !important; }
        }

        /* SHARE MODAL SPECIFIC */
        .copy-input-group {
            display: flex; gap: 8px; margin: 1.5rem 0; background: rgba(0,0,0,0.4);
            padding: 6px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.1);
            align-items: center;
        }
        #share_url_input {
            background: transparent; border: none; color: #9da3c8; flex: 1;
            padding: 4px 8px; font-family: monospace; font-size: 0.85rem; outline: none;
        }

        @media screen and (max-width: 768px) {
            .app-main { padding: 1rem 0; display: block; overflow: auto; }
            .printable-document { zoom: 0.85; width: 210mm !important; min-width: 210mm !important; margin: 0 auto; }
            .hide-mobile { display: none !important; }
            .toolbar-center { gap: 0.5rem; }
        }
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
            <a href="documents.php" class="btn btn-secondary" title="Späť">
                <i class="ti ti-arrow-left"></i> <span class="hide-mobile">Späť</span>
            </a>
            <div style="font-weight: 700; font-size: 1.1rem; color: var(--accent); letter-spacing: -0.01em;">Záväzná objednávka</div>
        </div>

        <div class="toolbar-center">
            <button class="btn btn-secondary" onclick="addRow()">
                <i class="ti ti-plus"></i> <span class="hide-mobile">Položka</span>
            </button>
        </div>

        <div class="toolbar-right" style="display: flex; align-items: center; gap: 8px;">
            <button class="btn-toolbar btn-secondary" onclick="saveDocument(false, 'draft', false)" id="save_draft_btn" title="Uložiť">
                <i class="ti ti-device-floppy"></i> <span class="hide-mobile">Uložiť</span>
            </button>
            <button class="btn-toolbar btn-primary" onclick="saveDocument(false, 'sent', true)" id="email_document_btn" title="Odoslať mailom" <?= $lead_id === 0 ? 'disabled' : '' ?>>
                <i class="ti ti-mail"></i> <span class="hide-mobile">Cez mail</span>
            </button>
            <button class="btn-toolbar" style="background: #6366f1;" onclick="saveDocument(true, 'sent', false)" id="share_document_btn" title="Zdielať link (WhatsApp a iné)" <?= $lead_id === 0 ? 'disabled' : '' ?>>
                <i class="ti ti-share"></i> <span class="hide-mobile">Zdielať</span>
            </button>
            <button class="btn-toolbar btn-success" onclick="window.print()" title="Tlačiť / PDF">
                <i class="ti ti-printer"></i> <span class="hide-mobile">Tlačiť / PDF</span>
            </button>
        </div>
    </header>
    <div id="duplicateWarning" style="display:none; background: #fffbeb; border-bottom: 1px solid #fde68a; padding: 10px 20px; font-size: 0.9rem; color: #92400e; position: fixed; top: 70px; left: 0; right: 0; z-index: 99; transition: all 0.3s;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 15px;">
            <i class="ti ti-alert-circle" style="font-size: 1.2rem;"></i>
            <span id="duplicateWarningMsg"></span>
            <button onclick="this.parentElement.parentElement.style.display='none'" style="background: none; border: none; cursor: pointer; color: #92400e; font-weight: bold;">✕</button>
        </div>
    </div>

    <?php if ($lead_id === 0): ?>
    <div class="client-warning-banner" id="client_warning">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="ti ti-alert-triangle" style="font-size: 1.2rem;"></i>
            <span>Tento dokument nie je priradený k žiadnemu klientovi v CRM.</span>
        </div>
        <button class="btn btn-primary" onclick="showQuickAddModal()" style="height: 32px; font-size: 0.75rem;">
            Vybrať alebo pridať klienta
        </button>
    </div>
    <?php endif; ?>

    <main class="app-main" <?= $lead_id === 0 ? 'style="margin-top: 110px;"' : '' ?>>
        <div id="order_document" class="printable-document">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <div style="width: 150px;">
                    <img src="vueto_logo.png" style="max-height: 50px; max-width: 100%; object-fit: contain;">
                </div>
                <div style="text-align: right;">
                    <h1 style="color: #2563eb; margin: 0; font-size: 2.25rem; font-weight: 800; text-transform: uppercase;">Záväzná objednávka</h1>
                    <div style="font-size: 1.1rem; color: #666; margin-top: 2px;">
                        # <span id="order_number" contenteditable="true" style="border-bottom: 1px dotted #ccc; outline: none; min-width: 80px; display: inline-block;"><?= date('Y') ?>001</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 30px; margin-bottom: 30px;">
                <div style="flex: 1; border: 1px solid #f1f5f9; border-radius: 8px; padding: 12px;">
                    <div style="background: #f8fafc; color: #475569; padding: 4px 10px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; margin-bottom: 10px;">
                        Dodávateľ
                    </div>
                    <div style="padding: 0 10px; line-height: 1.5; font-size: 0.85rem;">
                        <div contenteditable="true" style="font-weight: 800; font-size: 1rem; margin-bottom: 2px; outline: none;">CONTRACTOR GROUPS s.r.o.</div>
                        <div contenteditable="true">Jabloňová 850/77</div>
                        <div contenteditable="true">010 04 Žilina</div>

                    </div>
                </div>

                <div style="flex: 1; border: 1px solid #f1f5f9; border-radius: 8px; padding: 12px;">
                    <div style="background: #f8fafc; color: #475569; padding: 4px 10px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; margin-bottom: 10px;">
                        Odberateľ
                    </div>
                    <div style="padding: 0 10px; line-height: 1.5; font-size: 0.85rem;">
                        <div contenteditable="true" style="font-weight: 800; font-size: 1rem; margin-bottom: 2px; outline: none; color: #2563eb;"><?= $client ? htmlspecialchars($client['name']) : 'Meno klienta' ?></div>
                        <?php if($client && !empty($client['company'])): ?>
                            <div contenteditable="true"><?= htmlspecialchars($client['company']) ?></div>
                        <?php endif; ?>
                        <div contenteditable="true">
                            <?= ($client && $client['address']) ? htmlspecialchars($client['address']) : 'Ulica a číslo' ?>
                            <?= ($client && !empty($client['apartment'])) ? ' / ' . htmlspecialchars($client['apartment']) : '' ?>
                        </div>
                        <div contenteditable="true">
                            <?= ($client && $client['zip']) ? htmlspecialchars($client['zip']) . ' ' : '' ?>
                            <?= ($client && $client['city']) ? htmlspecialchars($client['city']) : 'Mesto' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background: #fafafa; padding: 12px 15px; border-radius: 8px; display: flex; gap: 40px; margin-bottom: 30px; border: 1px solid #eee;">
                <div style="font-size: 0.85rem;">
                    <span style="color: #64748b; font-weight: 700; text-transform: uppercase; margin-right: 8px;">Dátum objednania:</span>
                    <input type="text" id="order_date" value="<?= date('d.m.Y') ?>" style="border: none; background: transparent; font-family: inherit; font-size: 0.85rem; outline: none; border-bottom: 1px dotted #cbd5e1; width: 100px; font-weight: 600;">
                </div>
            </div>

            <table id="items_table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <thead>
                    <tr style="background: #2563eb; color: white;">
                        <th style="padding: 12px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Predmet objednávky</th>
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

                <div style="width: 280px; background: #2563eb; color: white; padding: 20px; border-radius: 12px; text-align: right; box-shadow: 0 10px 25px rgba(37,99,235,0.25);">
                    <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700; opacity: 0.9; margin-bottom: 4px;">Celková suma objednávky</div>
                    <div style="font-size: 2rem; font-weight: 800;">
                        <span id="offer_total">0.00</span> <span style="font-size: 1.2rem; margin-left: 5px;">€</span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; padding: 15px; background: #fdfdfd; border: 1px solid #f1f5f9; border-radius: 8px;">
                <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px;">Dôležité informácie:</div>
                <div contenteditable="true" style="outline: none; font-size: 0.9rem; line-height: 1.6; min-height: 50px;">
                    Zákazník prehlasuje, že súhlasí s rozsahom a cenou položiek a záväzne si ich objednáva u spoločnosti CONTRACTOR GROUPS s.r.o. <br>
                    <strong>Na základe tejto objednávky bude vystavená zálohová faktúra. Projekt bude zadaný do výroby až po pripísaní platby na účet dodávateľa.</strong><br>
                    Táto objednávka v plnom rozsahu nahrádza zmluvu o dielo.
                </div>
            </div>

            <div style="margin-top: 60px; display: flex; justify-content: space-between; align-items: flex-end;">
                <div style="width: 300px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #475569; font-size: 0.9rem;">
                        <i class="ti ti-user" style="color: #2563eb; font-size: 1.2rem;"></i>
                        <span contenteditable="true" style="outline:none; font-weight: 600;"><?= htmlspecialchars($user['full_name']) ?></span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #475569; font-size: 0.9rem;">
                        <i class="ti ti-mail" style="color: #2563eb; font-size: 1.2rem;"></i>
                        <span contenteditable="true" style="outline:none;"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                </div>

                <div style="width: 250px; text-align: center;">
                    <input type="hidden" name="signature_data" id="signature_data">
                    <div id="sig_trigger" class="sig-preview-trigger" onclick="openSignatureModalGlobal('signature_data', 'sig_preview_img', 'sig_placeholder_modal')">
                        <div class="sig-placeholder-sig" id="sig_placeholder_modal">
                            <i class="ti ti-pencil-plus"></i>
                            Pridať podpis odberateľa
                        </div>
                        <img src="" id="sig_preview_img" class="sig-preview-img-modal" alt="Podpis odberateľa" style="max-height: 80px; display: none;">
                    </div>
                    <div id="sig_customer_display"></div>
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">Za odberateľa</div>
                </div>
            </div>

            <div style="margin-top: 50px; border-top: 1px solid #f1f5f9; padding-top: 20px; font-size: 0.75rem; color: #94a3b8; text-align: center;">
                Objednáv    <script>
        // ==========================================
        // 1. GLOBÁLNE FUNKCIE (Dostupné odvšadiaľ)
        // ==========================================
        window.showQuickAddModal = function() { 
            const m = document.getElementById('quickAddModal');
            if(m) m.classList.add('open'); 
        };
        window.closeQuickAddModal = function() { 
            const m = document.getElementById('quickAddModal');
            if(m) m.classList.remove('open'); 
        };
        window.showShareModal = function(url, msg = null) {
            const modal = document.getElementById('shareModal');
            if (!modal) return;
            const linkGroup = document.getElementById('shareInputGroup');
            if (url) {
                document.getElementById('share_url_input').value = url;
                document.getElementById('open_link_btn').href = url;
                if (linkGroup) linkGroup.style.display = 'flex';
                document.getElementById('shareModalTitle').innerText = "Uložené";
            } else {
                if (linkGroup) linkGroup.style.display = 'none';
                document.getElementById('shareModalTitle').innerText = "Uložené";
            }
            if (msg) document.getElementById('shareModalMsg').innerText = msg;
            modal.classList.add('open');
        };
        window.closeShareModal = function() { document.getElementById('shareModal').classList.remove('open'); };
        
        window.switchTab = function(tabName) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(b => {
                const click = b.getAttribute('onclick');
                if(click && click.includes(tabName)) b.classList.add('active');
            });
            const pane = document.getElementById('pane-' + tabName);
            if(pane) pane.style.display = 'block';
        };

        window.searchLeads = function(query) {
            const results = document.getElementById('searchResults');
            if (query.length < 2) {
                results.innerHTML = '<div style="text-align:center; color:var(--text-muted); padding:20px; font-size:0.9rem;">Začnite písať...</div>';
                return;
            }
            fetch('ajax_search_leads.php?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = '';
                    if (data.length === 0) {
                        results.innerHTML = '<div style="text-align:center; color:var(--text-muted); padding:20px;">Nenašli sa žiadne výsledky.</div>';
                        return;
                    }
                    data.forEach(lead => {
                        const div = document.createElement('div');
                        div.className = 'search-result-item';
                        div.style.padding = '12px'; div.style.cursor = 'pointer'; div.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
                        div.innerHTML = `<strong>${lead.full_name}</strong><br><small style="color:var(--text-muted)">${lead.email || ''} | ${lead.city || ''}</small>`;
                        div.onclick = () => selectLead(lead.id);
                        results.appendChild(div);
                    });
                });
        };

        window.selectLead = function(id) {
            const url = new URL(window.location.href);
            url.searchParams.set('lead_id', id);
            window.location.href = url.toString();
        };

        window.submitQuickAddLead = function() {
            const btn = document.getElementById('qSubmitBtn');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ti ti-loader-2 rotate"></i> Ukladám...';

            const formData = new FormData();
            formData.append('full_name', document.getElementById('qName').value);
            formData.append('email', document.getElementById('qEmail').value);
            formData.append('phone', document.getElementById('qPhone').value);
            formData.append('address', document.getElementById('qAddress').value);
            formData.append('city', document.getElementById('qCity').value);

            fetch('ajax_quick_add_lead.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) selectLead(res.lead_id);
                    else {
                        alert('Chyba: ' + res.error);
                        btn.disabled = false; btn.innerHTML = original;
                    }
                });
        };

        window.copyShareUrl = function() {
            const el = document.getElementById('share_url_input');
            el.select();
            document.execCommand('copy');
            const btn = document.querySelector('#shareInputGroup .btn-success');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check"></i> Skopírované!';
            setTimeout(() => btn.innerHTML = orig, 2000);
        };

        // ==========================================
        // 2. LOGIKA DOKUMENTU (Po načítaní DOM)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            const items_body = document.getElementById('items_body');
            const add_item_btn = document.getElementById('add_item_btn');
            const leadId = '<?= $lead_id ?>';
            let editToken = '<?= $edit_token ?>';
            const draftData = <?= json_encode($draft_data) ?>;

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
                tr.className = 'item-row';
                tr.style.borderBottom = '1px solid #f1f5f9';
                
                const is_zero = data && data.vat === '0';
                
                tr.innerHTML = `
                    <td style="padding: 12px; position:relative;">
                        <div class="item-desc" contenteditable="true" style="outline: none; font-size: 0.9rem;">${data ? data.description : 'Nová položka'}</div>
                        <button class="remove-row" style="position:absolute; left:-35px; top:12px; border:none; background:none; color:#f87171; cursor:pointer; font-size: 1.2rem; opacity:0.3;" onclick="this.closest('tr').remove(); window.recalculate();">×</button>
                    </td>
                    <td style="padding: 12px; text-align: center;"><div class="qty" contenteditable="true" style="outline: none; font-size: 0.9rem; font-weight: 700;">${data ? data.qty : '1'}</div></td>
                    <td style="padding: 12px; text-align: center;"><div contenteditable="true" style="outline: none; font-size: 0.9rem;">${data ? data.unit : 'ks'}</div></td>
                    <td style="padding: 12px; text-align: right;"><div contenteditable="true" class="unit-price" style="outline: none; font-size: 0.9rem;">${data ? data.price : '0.00'}</div></td>
                    <td style="padding: 12px; text-align: center;">
                        <select class="vat-rate" style="outline: none; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; background: #f8fafc; cursor: pointer; color: #2563eb; font-weight: 700; text-align: center; margin: 0 auto; display: block;">
                            <option value="23" ${!is_zero ? 'selected' : ''}>23%</option>
                            <option value="0" ${is_zero ? 'selected' : ''}>0%</option>
                        </select>
                    </td>
                    <td style="padding: 12px; text-align: right; font-weight: 700; white-space: nowrap;"><span class="row-total" style="font-size: 0.9rem;">0.00</span> <span style="font-size: 0.75rem;">€</span></td>
                `;
                items_body.appendChild(tr);
                tr.querySelector('.vat-rate').addEventListener('change', window.recalculate);
                window.recalculate();
            };

            if(add_item_btn) add_item_btn.addEventListener('click', () => window.addRow());
            items_body.addEventListener('input', window.recalculate);


            if (draftData && draftData.items) {
                if (draftData.number) document.getElementById('order_number').innerText = draftData.number;
                if (draftData.date) document.getElementById('order_date').value = draftData.date;
                
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

                items_body.innerHTML = '';
                draftData.items.forEach(item => window.addRow(item));
            } else {
                window.addRow();
            }

            // Ukladanie dokumentu
            window.saveDocument = function(showUrl = false, status = 'sent', sendEmail = false) {
                if (!leadId || leadId === '0') {
                    showShareModal(null, "Vyberte klienta pred uložením.");
                    return;
                }

                const btn = showUrl ? document.getElementById('share_document_btn') : 
                            (status === 'draft' ? document.getElementById('save_draft_btn') : document.getElementById('email_document_btn'));
                const originalContent = btn.innerHTML;
                
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="ti ti-loader-2 rotate"></i> Ukladám...';

                    const editorData = {
                        number: document.getElementById('order_number').innerText,
                        date: document.getElementById('order_date').value,
                        signature_data: document.getElementById('signature_data').value,
                        items: []
                    };

                    document.querySelectorAll('.item-row').forEach(tr => {
                        editorData.items.push({
                            description: tr.querySelector('.item-desc').innerText,
                            qty: tr.querySelector('.qty').innerText,
                            unit: tr.querySelectorAll('td')[2].innerText,
                            price: tr.querySelector('.unit-price').innerText,
                            vat: tr.querySelector('.vat-rate').value
                        });
                    });

                    const docClone = document.getElementById('order_document').cloneNode(true);
                    docClone.querySelectorAll('.remove-row, .clear-sig, .btn').forEach(el => el.remove());
                    docClone.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'));
                    const originalElements = document.getElementById('order_document').querySelectorAll('input, select');
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
                        if (sigPlaceholder) sigPlaceholder.remove(); // Ak je podpísané, odstránime inštrukciu
                        if (sigTrigger) {
                            sigTrigger.removeAttribute('onclick');
                            sigTrigger.style.cursor = 'default';
                            sigTrigger.style.border = 'none';
                            sigTrigger.style.background = 'transparent';
                        }
                        if (sigImg) sigImg.style.display = 'block';
                    }

                    const data = new FormData();
                    data.append('lead_id', leadId);
                    data.append('type', 'order');
                    data.append('status', status);
                    data.append('send_email', sendEmail ? '1' : '0');
                    data.append('doc_number', editorData.number);
                    data.append('title', 'Objednávka ' + editorData.number);
                    if (editToken) data.append('token', editToken);
                    data.append('content', JSON.stringify({ 
                        html: docClone.innerHTML,
                        editor_data: editorData 
                    }));

                    fetch('save_document.php', { method: 'POST', body: data })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            if (!editToken) editToken = res.token;
                            if (showUrl) showShareModal(res.url);
                            else if (status === 'draft') showShareModal(null, "Dokument bol úspešne uložený.");
                            else showShareModal(null, "Dokument bol uložený a odoslaný klientovi.");
                        } else {
                            alert('Chyba: ' + res.error);
                        }
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    });
                } catch (e) {
                    btn.disabled = false; btn.innerHTML = originalContent;
                }
            };

            // Číslovanie pri štarte
            if (leadId && !editToken) {
                fetch(`ajax_get_doc_info.php?lead_id=${leadId}&type=order`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const numEl = document.getElementById('order_number');
                            if (numEl) numEl.innerText = data.next_number;
                            if (data.already_exists) {
                                document.getElementById('duplicateWarningMsg').innerHTML = 
                                    `Pozor, tomuto klientovi už bola dňa ${new Date(data.existing_docs[0].created_at).toLocaleDateString('sk-SK')} odoslaná <strong>Objednávka (${data.existing_docs[0].doc_number})</strong>.`;
                                document.getElementById('duplicateWarning').style.display = 'block';
                            }
                        }
                    });
            }
        });
    </script>

    <!-- Modály -->
    <div id="quickAddModal" class="modal-overlay">
        <div class="modal-box" style="width: 500px; max-width: 95%;">
            <div class="modal-header">
                <div class="modal-title" style="display: flex; align-items: center; gap: 8px;"><i class="ti ti-users"></i> Vybrať alebo pridať klienta</div>
                <button class="modal-close" onclick="closeQuickAddModal()">✕</button>
            </div>
            <div class="tabs" style="margin-bottom: 20px; display: flex; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
                <button class="tab-btn active" onclick="switchTab('search')" style="background: none; border: none; color: white; padding: 8px 15px; cursor: pointer; border-radius: 8px; font-weight: 600;">Hľadať v CRM</button>
                <button class="tab-btn" onclick="switchTab('add')" style="background: none; border: none; color: white; padding: 8px 15px; cursor: pointer; border-radius: 8px; font-weight: 600;">Rýchlo pridať</button>
            </div>
            <div id="pane-search" class="tab-pane">
                <div style="position:relative; margin-bottom:15px;">
                    <i class="ti ti-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                    <input type="text" class="form-control" placeholder="Meno, firma alebo email..." style="padding-left: 40px;" onkeyup="searchLeads(this.value)">
                </div>
                <div id="searchResults" style="max-height:280px; overflow-y:auto; padding-right:5px;">
                    <div style="text-align:center; color:var(--text-muted); padding:20px; font-size:0.9rem;">Začnite písať...</div>
                </div>
            </div>
            <div class="tab-pane" id="pane-add" style="display:none;">
                <div class="form-group"><label class="form-label">Meno</label><input type="text" id="qName" class="form-control"></div>
                <div class="form-group"><label class="form-label">E-mail</label><input type="email" id="qEmail" class="form-control"></div>
                <div class="form-group"><label class="form-label">Telefón</label><input type="text" id="qPhone" class="form-control"></div>
                <div class="grid-2">
                    <div class="form-group"><label class="form-label">Ulica</label><input type="text" id="qAddress" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Mesto</label><input type="text" id="qCity" class="form-control"></div>
                </div>
                <button id="qSubmitBtn" onclick="submitQuickAddLead()" class="btn btn-primary w-full mt-4" style="justify-content:center;"><i class="ti ti-user-plus"></i> Vytvoriť a vybrať</button>
            </div>
        </div>
    </div>

    <div id="shareModal" class="modal-overlay">
        <div class="modal-box" style="text-align: center;">
            <div class="modal-header">
                <div class="modal-title" id="shareModalTitle">Uložené</div>
                <button class="modal-close" onclick="closeShareModal()">✕</button>
            </div>
            <div id="shareModalMsg" style="color: #9da3c8; margin-bottom: 1rem; font-size: 0.95rem;">Zmeny boli uložené.</div>
            <div class="copy-input-group" id="shareInputGroup" style="display: none; gap: 8px; margin: 1.5rem 0; background: rgba(0,0,0,0.25); padding: 8px; border-radius: 12px; border: 1px solid var(--border);">
                <input type="text" id="share_url_input" readonly style="flex: 1; background: transparent; border: none; color: white; padding: 5px; font-size: 0.85rem;">
                <button class="btn btn-success" onclick="copyShareUrl()" style="height: 32px; font-size: 0.75rem; padding: 0 12px;"><i class="ti ti-copy"></i> Skopírované!</button>
            </div>
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 2rem;">
                <button class="btn btn-secondary" onclick="closeShareModal()" style="min-width: 100px;">Zavrieť</button>
                <a href="#" id="open_link_btn" target="_blank" class="btn btn-primary" style="min-width: 100px; background: #6366f1;"><i class="ti ti-external-link"></i> Otvoriť</a>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/partials/signature_modal.php'; ?>
</body>
</html>


