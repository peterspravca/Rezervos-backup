<?php
require_once __DIR__ . '/config.php';

$pdo = db_connect();

$token = $_GET['t'] ?? '';
if (!$token) {
    die("Chýbajúci prístupový kód.");
}

// Fetch document with assigned user info for notification
$stmt = $pdo->prepare("SELECT d.*, l.name as client_name, l.status as lead_status, l.assigned_to, 
                              u.email as admin_email, u.full_name as admin_name,
                              l.survey_date, l.order_number, l.production_at, l.realization_date, l.handover_date, l.address, l.city
                       FROM crm_generated_documents d
                       JOIN leads l ON d.lead_id = l.id
                       LEFT JOIN crm_users u ON l.assigned_to = u.id
                       WHERE d.token = ?");
$stmt->execute([$token]);
$doc = $stmt->fetch();

if (!$doc) {
    die("Dokument sa nenašiel alebo odkaz expiroval.");
}

// Blokovanie konceptov
if ($doc['status'] === 'draft') {
    ?>
    <!DOCTYPE html>
    <html lang="sk">
    <head>
        <meta charset="UTF-8">
        <link rel="apple-touch-icon" sizes="180x180" href="/nastenka/favicon/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/nastenka/favicon/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/nastenka/favicon/favicon-16x16.png">
        <link rel="manifest" href="/nastenka/favicon/site.webmanifest">
        <link rel="shortcut icon" href="/nastenka/favicon.ico">
        <style>
            body { font-family: sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; color: #1e293b; text-align: center; }
            .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 400px; }
            h1 { font-size: 1.5rem; margin-bottom: 1rem; color: #2563eb; }
            p { line-height: 1.6; opacity: 0.8; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Na tomto dokumente pracujeme</h1>
            <p>Dobrý deň. Práve pre Vás pripravujeme finálnu verziu tohto dokumentu. Odkaz bude plne funkčný hneď po jeho dokončení.</p>
            <p>Ďakujeme za trpezlivosť.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$content = json_decode($doc['content_json'], true);

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    if ($doc['status'] !== 'approved') {
        $signature_data = $_POST['signature_data'] ?? '';
        
        // Finalize document status
        $stmt = $pdo->prepare("UPDATE crm_generated_documents SET status = 'approved', approved_at = NOW(), ip_address = ?, user_agent = ? WHERE id = ?");
        $stmt->execute([$_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $doc['id']]);
        
        // If signature is provided, inject it into the HTML content
        if (!empty($signature_data)) {
            $content_data = json_decode($doc['content_json'], true);
            $html = $content_data['html'] ?? '';
            
            // Logic to replace signature spot or append to it
            // We search for canvas with id="sig_receiver" or similar spots
            $sig_img_tag = '<img src="' . $signature_data . '" style="max-width: 100%; max-height: 100px; display: block; margin: 0 auto;">';
            
            // We track if we successfully injected the signature
            $injected = false;
            
            // 1. Try modern marker
            if (strpos($html, 'id="sig_customer_display"') !== false) {
                $html = preg_replace('/<div id="sig_customer_display"[^>]*><\/div>/i', '<div id="sig_customer_display">' . $sig_img_tag . '</div>', $html);
                $injected = true;
            } 
            // 2. Try older marker
            elseif (strpos($html, 'id="sig_receiver"') !== false) {
                $html = preg_replace('/<canvas id="sig_receiver"[^>]*><\/canvas>/i', $sig_img_tag, $html);
                $injected = true;
            } 
            // 3. Try text markers
            else {
                if (strpos($html, 'Za odberateľa') !== false) {
                     $html = str_replace('Za odberateľa', $sig_img_tag . '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Za odberateľa</div>', $html);
                     $injected = true;
                } elseif (strpos($html, 'Prevzal (Odberateľ)') !== false) {
                     $html = str_replace('Prevzal (Odberateľ)', $sig_img_tag . '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Prevzal (Odberateľ)</div>', $html);
                     $injected = true;
                } elseif (strpos($html, 'Objednávateľ (Odberateľ)') !== false) {
                     $html = str_replace('Objednávateľ (Odberateľ)', $sig_img_tag . '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Objednávateľ (Odberateľ)</div>', $html);
                     $injected = true;
                }
            }

            // 4. Desperate fallback: If still not injected, append to bottom
            if (!$injected) {
                $html .= '<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end;">';
                $html .= '<div style="width: 250px; text-align: center;">' . $sig_img_tag . '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">Za odberateľa (Podpísané na portáli)</div></div>';
                $html .= '</div>';
            }
            
            $content_data['html'] = $html;
            $content_data['signature_customer'] = $signature_data; // Save as backup for dynamic rendering
            $updated_json = json_encode($content_data);
            
            $pdo->prepare("UPDATE crm_generated_documents SET content_json = ? WHERE id = ?")
                ->execute([$updated_json, $doc['id']]);
        }

        // --- NOTIFICATION LOGIC ---
        require_once __DIR__ . '/libs/mailer.php';
        $doc_title = $doc['title'] ?: $doc['type'];
        $prot = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $doc_link = $prot . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/portal.php?t=" . $token;

        // 1. Log to CRM notes with a direct link
        $note_content = "Zákazník práve SCHVÁLIL a PODPÍSAL dokument: **" . $doc_title . "** cez klientsky portál.<br><br><a href='$doc_link' target='_blank' style='display:inline-block; background:#22c55e; color:white; padding:5px 12px; border-radius:6px; text-decoration:none; font-weight:700; font-size:12px;'><i class='ti ti-file-search' style='margin-right:4px;'></i> ZOBRAZIŤ PODPÍSANÝ DOKUMENT</a>";
        $pdo->prepare("INSERT INTO crm_notes (lead_id, user_id, note_type, content, created_at) VALUES (?, 1, 'system_silent', ?, NOW())")
            ->execute([$doc['lead_id'], $note_content]);

        // 2. Send E-mail Notification
        $subject = "✅ Dokument PODPÍSANÝ: " . $doc['client_name'] . " - " . $doc_title;
        $email_body = "<p>Dobrý deň,</p>";
        $email_body .= "<p>Zákazník <strong>" . htmlspecialchars($doc['client_name']) . "</strong> práve schválil a digitálne podpísal dokument:</p>";
        $email_body .= "<div style='background:#f0f9ff; border-left:4px solid #0ea5e9; padding:15px; margin:20px 0;'>";
        $email_body .= "Dokument: <strong>$doc_title</strong><br>";
        $email_body .= "Dátum podpisu: " . date('d.m.Y H:i') . "<br>";
        $email_body .= "IP adresa: " . $_SERVER['REMOTE_ADDR'];
        $email_body .= "</div>";
        $email_body .= "<p>Podpísaný dokument si môžete kedykoľvek zobraziť kliknutím na tlačidlo nižšie.</p>";
        
        $wrapped_body = wrap_email_content("Nový podpísaný dokument", $email_body, $doc_link);
        
        // Send to office e-mail only
        if (defined('OFFICE_EMAIL')) {
            send_crm_notification(OFFICE_EMAIL, $subject, $wrapped_body);
        }

        // 3. In-App Notification (Bell icon)
        require_once __DIR__ . '/libs/notifications.php';
        $notif_title = "✅ Dokument podpísaný: " . $doc['client_name'];
        $notif_msg = "Zákazník práve podpísal dokument <strong>" . $doc_title . "</strong>.";
        $notif_link = "contact.php?id=" . $doc['lead_id'];
        create_notification(0, $notif_title, $notif_msg, $notif_link, 'ti-certificate');
        // --- END NOTIFICATION LOGIC ---
        
        header("Location: portal.php?t=" . $token . "&success=1");
        exit;
    }
}

// Timeline logic - defined steps
$all_steps = [
    ['label' => 'Cenová ponuka', 'date' => $doc['created_at'], 'icon' => 'ti-file-description'],
    ['label' => 'Obhliadka', 'date' => $doc['survey_date'], 'icon' => 'ti-calendar-event'],
    ['label' => 'Zameranie', 'date' => $doc['order_number'] ? 'Active' : null, 'icon' => 'ti-shopping-cart'],
    ['label' => 'Vo výrobe', 'date' => $doc['production_at'], 'icon' => 'ti-settings-automation'],
    ['label' => 'Realizácia', 'date' => $doc['realization_date'], 'icon' => 'ti-tools'],
    ['label' => 'Odovzdanie', 'date' => $doc['handover_date'], 'icon' => 'ti-circle-check'],
];

// If no order number, show only first 3 steps (Sales phase)
if (!$doc['order_number']) {
    $steps = array_slice($all_steps, 0, 3);
} else {
    $steps = $all_steps;
}

// Determine current step
$current_index = 0;
if ($doc['handover_date']) $current_index = 5;
elseif ($doc['realization_date']) $current_index = 4;
elseif ($doc['production_at']) $current_index = 3;
elseif ($doc['order_number']) $current_index = 2;
elseif ($doc['survey_date']) $current_index = 1;

?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($doc['title'] ?: 'Dokument') ?> | VUETO CRM</title>
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
            --accent: #2563eb;
            --accent-bg: #eff6ff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: var(--text-main);
            margin: 0; padding: 0;
            line-height: 1.5;
        }

        @media print {
            @page { size: auto; margin: 0; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .header, .timeline, .status-pill, .approve-bar, .signature-section, .footer, footer, #signature_modal {
                display: none !important;
            }
            body { background: white !important; }
            .container { 
                max-width: 100% !important; 
                margin: 0 !important; 
                padding: 15mm !important; 
                width: 210mm !important;
            }
            .doc-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .doc-viewport {
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .doc-viewport > div {
                transform: none !important;
                zoom: 1 !important;
            }
        }

        .header {
            background: white;
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        @media (min-width: 768px) {
            .header { display: none; }
        }
        .header img { max-height: 50px; }

        .container {
            max-width: 900px; margin: 2rem auto; padding: 0 1rem;
        }

        /* Timeline Styles */
        .timeline {
            display: flex; justify-content: space-between; margin-bottom: 3rem;
            position: relative; padding: 0 10px;
        }
        .timeline::before {
            content: ''; position: absolute; top: 20px; left: 0; right: 0;
            height: 2px; background: #e2e8f0; z-index: 1;
        }
        .timeline-step {
            position: relative; z-index: 2; text-align: center; flex: 1;
        }
        .step-icon {
            width: 40px; height: 40px; background: white; border: 2px solid var(--border);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 0.5rem; font-size: 1.25rem; color: var(--text-muted);
            transition: all 0.3s;
        }
        .step-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .step-date { font-size: 0.7rem; color: var(--text-muted); margin-top: 2px; }

        .timeline-step.active .step-icon { border-color: var(--accent); color: var(--accent); box-shadow: 0 0 0 4px var(--accent-bg); }
        .timeline-step.active .step-label { color: var(--accent); }
        .timeline-step.complete .step-icon { background: var(--success); border-color: var(--success); color: white; }
        .timeline-step.complete .step-label { color: var(--success); }

        /* Progress line highlight */
        .progress-line {
            position: absolute; top: 20px; left: 0; height: 2px;
            background: var(--success); z-index: 1; transition: width 0.5s;
        }

        /* Document Preview */
        .doc-card {
            background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 2.5rem; border: 1px solid var(--border); overflow: hidden;
            position: relative;
        }

        .approve-bar {
            margin-top: 2rem; background: white; border: 1px solid var(--border);
            padding: 1.5rem; border-radius: 12px; display: flex; align-items: center;
            justify-content: space-between; gap: 1rem;
        }

        .btn-approve {
            background: var(--success); color: white; border: none; padding: 0.75rem 2rem;
            border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 1rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .btn-approve:hover { filter: brightness(1.1); }

        .status-pill {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1rem; border-radius: 20px; font-weight: 700;
            font-size: 0.85rem; background: var(--accent-bg); color: var(--accent);
            margin-bottom: 1rem;
        }
        .status-pill.approved { background: #d1fae5; color: #065f46; }

        @media (max-width: 600px) {
            .timeline { flex-wrap: wrap; gap: 1rem; }
            .timeline::before { display: none; }
            .timeline-step { flex: 0 0 30%; margin-bottom: 1rem; }
            .approve-bar { flex-direction: column; text-align: center; }
            .doc-card { padding: 1.25rem; }
            
            /* Responsive Document Overrides */
            .doc-viewport div[style*="display: flex"] {
                flex-direction: column !important;
                gap: 15px !important;
            }
            .doc-viewport table {
                min-width: 650px !important; /* Force scroll instead of squish */
            }
            .doc-viewport div[style*="width: 280px"], 
            .doc-viewport div[style*="width: 300px"] {
                width: 100% !important;
            }
            .doc-viewport img {
                max-width: 150px !important;
            }
            /* Hide document internal logo on mobile to avoid duplication with portal header */
            .doc-viewport img[src*="logo"] {
                display: none !important;
            }
        }

        .doc-viewport {
            overflow-x: auto;
            margin: 0 -1rem;
            padding: 0 1rem;
            -webkit-overflow-scrolling: touch;
        }
        /* Mobile scroll hint */
        @media (max-width: 600px) {
            .doc-viewport::after {
                content: "← Posúvajte tabuľku do strán →";
                display: block;
                text-align: center;
                font-size: 0.7rem;
                color: var(--text-muted);
                margin-top: 1rem;
                font-style: italic;
            }
        }

        /* Prevent price wrapping in tables */
        .doc-card table td:last-child {
            white-space: nowrap !important;
        }

        /* Hide internal signature pads/buttons from generators when viewing in portal */
        .doc-viewport #signature_pad_container,
        .doc-viewport #clear_signature,
        .doc-viewport #sig_receiver,
        .doc-viewport #sig_trigger,
        .doc-viewport .sig-placeholder-sig,
        .doc-viewport .sig-preview-trigger,
        .doc-viewport .clear-sig,
        .doc-viewport [id^="signature_pad"],
        .doc-viewport [id^="sig_receiver"],
        .doc-viewport .ti-pencil-plus,
        .doc-viewport .ti-writing-sign {
            display: none !important;
        }

        /* Hide images with broken or empty src inside the document preview */
        .doc-viewport img[src=""], 
        .doc-viewport img:not([src]),
        .doc-viewport img[src*="undefined"],
        .doc-viewport img[alt="Podpis a pečiatka"] {
            display: none !important;
        }

        /* Signature styles */
        .signature-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        .signature-pad-wrap {
            position: relative;
            height: 300px;
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin: 1rem 0;
            overflow: hidden;
            touch-action: none !important;
        }
        #customer_signature_canvas {
            width: 100%;
            height: 100%;
            cursor: crosshair;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-content {
            background: white;
            width: 100%;
            max-width: 600px;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalPop {
            0% { transform: scale(0.9) translateY(20px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .modal-title { font-weight: 700; font-size: 1.1rem; color: #1e293b; }
        .close-modal { 
            background: #f1f5f9; border: none; width: 32px; height: 32px; 
            border-radius: 50%; cursor: pointer; color: #64748b;
            display: flex; align-items: center; justify-content: center;
        }
        
        /* Preview area */
        .sig-preview-box {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1rem;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s;
            margin: 1rem 0;
            position: relative;
        }
        .sig-preview-box:hover { border-color: var(--accent); background: white; }
        .sig-preview-img { max-height: 120px; display: none; }
        .sig-placeholder { color: var(--text-muted); font-size: 0.9rem; text-align: center; }
        .sig-placeholder i { font-size: 1.5rem; display: block; margin-bottom: 5px; }

        .btn-sig-open {
            width: 100%;
            padding: 1rem;
            background: white;
            border: 2px solid var(--accent);
            color: var(--accent);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
        }
        .btn-sig-open:hover { background: var(--accent-bg); }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }
        .btn-save-sig {
            flex: 2;
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-clear-sig {
            flex: 1;
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        @media (max-height: 500px) {
            .modal-overlay { padding: 0; align-items: stretch; }
            .modal-content { 
                padding: 10px !important; 
                max-width: 100% !important; 
                border-radius: 0 !important;
                height: 100vh !important;
                max-height: 100vh !important;
                display: grid !important;
                grid-template-columns: 1fr 140px; 
                grid-template-rows: auto 1fr;
                gap: 10px;
                box-sizing: border-box;
            }
            .modal-header { grid-column: 1 / 3; margin: 0 !important; padding-bottom: 5px; border-bottom: 1px solid #f1f5f9; }
            .modal-title { font-size: 0.9rem !important; }
            .signature-pad-wrap { 
                grid-column: 1 / 2; 
                grid-row: 2 / 3;
                height: calc(100vh - 65px) !important; 
                margin: 0 !important;
            }
            .modal-footer { 
                grid-column: 2 / 3; 
                grid-row: 2 / 3;
                flex-direction: column !important;
                margin: 0 !important;
                gap: 8px !important;
                justify-content: center;
            }
            .btn-save-sig, .btn-clear-sig { width: 100% !important; padding: 10px 5px !important; font-size: 0.85rem !important; flex: none !important; }
            .hide-landscape { display: none !important; }
        }
        .clear-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #fee2e2;
            color: #ef4444;
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            z-index: 10;
        }
        .signature-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>

    <header class="header">
        <img src="vueto_logo.png" alt="CONTRACTOR GROUPS">
    </header>

    <div class="container">
        
        <!-- Timeline -->
        <div class="timeline">
            <?php 
            $denominator = count($steps) > 1 ? (count($steps) - 1) : 1;
            $progress_width = ($current_index / $denominator) * 100;
            // Cap progress at 100% since current_index might be higher than visible steps in Lead mode
            if ($progress_width > 100) $progress_width = 100;
            ?>
            <div class="progress-line" style="width: <?= $progress_width ?>%;"></div>
            <?php foreach($steps as $i => $s): 
                $cls = '';
                if ($i < $current_index) $cls = 'complete';
                elseif ($i == $current_index) $cls = 'active';
                
                $d = null;
                if ($s['date'] && $s['date'] !== 'Active') $d = date('d.m.Y', strtotime($s['date']));
            ?>
            <div class="timeline-step <?= $cls ?>">
                <div class="step-icon">
                    <i class="ti <?= $cls === 'complete' ? 'ti-check' : $s['icon'] ?>"></i>
                </div>
                <div class="step-label"><?= $s['label'] ?></div>
                <div class="step-date"><?= $d ?: ($s['date'] === 'Active' ? 'V procese' : 'Naplánované') ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #10b981; font-weight: 600; text-align:center;">
                <i class="ti ti-circle-check" style="font-size: 1.5rem; vertical-align: middle;"></i> 
                Ďakujeme! Dokument bol úspešne schválený. Budeme Vás informovať o ďalšom postupe.
            </div>
        <?php endif; ?>

        <!-- Document View -->
        <div class="doc-card" id="document_content">
            <?php if($doc['status'] === 'approved'): ?>
                <div class="status-pill approved">
                    <i class="ti ti-certificate"></i> SCHVÁLENÉ (<?= date('d.m.Y H:i', strtotime($doc['approved_at'])) ?>)
                </div>
            <?php else: ?>
                <div class="status-pill">
                    <i class="ti ti-clock"></i> <?= $doc['type'] === 'proforma' ? 'ČAKÁ NA ÚHRADU' : 'ČAKÁ NA SCHVÁLENIE' ?>
                </div>
            <?php endif; ?>

            <!-- The document itself (Rendered from JSON) -->
            <div class="doc-viewport">
                <div style="transform-origin: top left; zoom: 1;">
                    <?php 
                    $html = $content['html'] ?? '<p>Chyba pri načítaní obsahu dokumentu.</p>';
                    
                    // --- DYNAMIC SIGNATURE INJECTION (Fallback for already approved or missed injection) ---
                    if ($doc['status'] === 'approved' && !empty($content['signature_customer']) && strpos($html, 'data:image') === false) {
                        $sig_img = '<img src="' . $content['signature_customer'] . '" style="max-width: 100%; max-height: 100px; display: block; margin: 0 auto;">';
                        
                        $injected_dynamic = false;
                        if (strpos($html, 'id="sig_customer_display"') !== false) {
                            $html = str_replace('id="sig_customer_display"></div>', 'id="sig_customer_display">' . $sig_img . '</div>', $html);
                            $injected_dynamic = true;
                        } elseif (strpos($html, 'Za odberateľa') !== false) {
                            $html = str_replace('Za odberateľa', $sig_img . '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Za odberateľa</div>', $html);
                            $injected_dynamic = true;
                        }

                        if (!$injected_dynamic) {
                            $html .= '<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end;">';
                            $html .= '<div style="width: 250px; text-align: center;">' . $sig_img . '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">Za odberateľa</div></div>';
                            $html .= '</div>';
                        }
                    }
                    // --- END DYNAMIC INJECTION ---

                    // Match old headers for backward compatibility
                    $html = str_replace(
                        ['Cena bez DPH', 'Celkom s DPH', 'Celková suma k úhrade', 'Cena za j.'],
                        ['Bez DPH', 'Spolu', 'Suma spolu', 'Bez DPH'],
                        $html
                    );

                    // Fix for PRINT: Force blue background and white text for the total box
                    $html = str_replace(
                        'background: #2563eb;', 
                        'background: #2563eb !important; border: 2px solid #2563eb;', 
                        $html
                    );
                    $html = str_replace(
                        'color: white;', 
                        'color: white !important;', 
                        $html
                    );
                    // Also ensure nested spans (the actual numbers) are white
                    $html = str_replace(
                        'id="offer_total">', 
                        'id="offer_total" style="color: white !important;">', 
                        $html
                    );
                    // And the "Suma spolu" label
                    $html = str_replace(
                        'Suma spolu</div>', 
                        '<span style="color: white !important;">Suma spolu</span></div>', 
                        $html
                    );

                    $html = str_replace(
                        'background: #0077c8;', 
                        'background: #0077c8 !important;', 
                        $html
                    );

                    echo $html;
                    ?>
                </div>
            </div>
        </div>

        <?php if($doc['status'] !== 'approved' && $doc['type'] !== 'proforma'): ?>
            <form method="POST" action="portal.php?t=<?= urlencode($token) ?>" onsubmit="return handleApproval(event)">
            <?php if($doc['type'] === 'offer'): 
                $offer_type = $content['editor_data']['offer_type'] ?? 'orientational';
                if ($offer_type === 'orientational'):
            ?>
                <div style="background: #fff9db; color: #856404; padding: 1.2rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #ffeeba; font-size: 0.95rem; display: flex; gap: 12px; align-items: start;">
                    <i class="ti ti-info-circle" style="font-size: 1.5rem; flex-shrink: 0; margin-top: 2px;"></i>
                    <div>
                        <strong>Orientačná ponuka:</strong> Táto cenová ponuka je momentálne iba orientačná. Finálnu cenovú ponuku dostanete až po obhliadke a presnom zameraní naším technikom.
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #e0f2fe; color: #0369a1; padding: 1.2rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #bae6fd; font-size: 0.95rem; display: flex; gap: 12px; align-items: start;">
                    <i class="ti ti-certificate" style="font-size: 1.5rem; flex-shrink: 0; margin-top: 2px;"></i>
                    <div>
                        <strong>Finálna ponuka:</strong> Táto cenová ponuka je finálna a vypracovaná na základe Vašich požiadaviek. V prípade súhlasu ju môžete nižšie schváliť.
                    </div>
                </div>
            <?php endif; endif; ?>

            <form method="POST" id="approval_form" onsubmit="return handleApproval(event)">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="signature_data" id="signature_data">
                
                <div class="signature-section">
                    <div class="signature-label"><?= $doc['type'] === 'offer' ? 'Váš podpis (voliteľné)' : 'Váš podpis' ?></div>
                    
                    <div class="sig-preview-box" onclick="openSignatureModal()">
                        <div class="sig-placeholder" id="sig_placeholder">
                            <i class="ti ti-pencil-plus"></i>
                            Kliknite sem pre pridanie podpisu
                        </div>
                        <img src="" id="sig_preview" class="sig-preview-img" alt="Váš podpis">
                    </div>

                    <button type="button" class="btn-sig-open" onclick="openSignatureModal()">
                        <i class="ti ti-writing-sign"></i> 
                        <?= $doc['status'] === 'approved' ? 'Zmeniť podpis' : 'Podpísať dokument' ?>
                    </button>
                </div>

                <div class="approve-bar">
                    <div style="display: flex; align-items: start; gap: 10px;">
                        <input type="checkbox" id="consent" required style="margin-top: 5px; width: 20px; height: 20px; cursor:pointer;">
                        <label for="consent" style="font-size: 0.9rem; cursor:pointer;">
                            <?php if($doc['type'] === 'offer'): ?>
                                Potvrdzujem, že súhlasím s obsahom tejto cenovej ponuky a schvaľujem jej spracovanie.
                            <?php else: ?>
                                Potvrdzujem, že súhlasím s obsahom tohto dokumentu (množstvá, ceny, termíny) a záväzne ho schvaľujem.
                            <?php endif; ?>
                        </label>
                    </div>
                    <button type="submit" class="btn-approve">
                        <i class="ti ti-check"></i> <?= $doc['type'] === 'offer' ? 'Schváliť ponuku' : 'Schváliť a podpísať' ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div style="text-align: center; margin-top:3rem; color: var(--text-muted); font-size: 0.8rem;">
            &copy; <?= date('Y') ?> CONTRACTOR GROUPS s.r.o. | CRM Klientsky portál
        </div>
    </div>

    <!-- Signature Modal -->
    <div id="signature_modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Pridanie podpisu</div>
                <button type="button" class="close-modal" onclick="closeSignatureModal()"><i class="ti ti-x"></i></button>
            </div>
            <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem;">
                Podpíšte sa do poľa nižšie. Na mobile môžete telefón otočiť pre viac miesta.
            </div>
            <div class="signature-pad-wrap">
                <canvas id="customer_signature_canvas"></canvas>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-clear-sig" id="modal_clear">Vymazať</button>
                <button type="button" class="btn-save-sig" onclick="saveSignature()">Uložiť podpis</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

    <script>
        let signaturePad;
        let lastSignatureData = null;
        const canvas = document.getElementById('customer_signature_canvas');
        const modal = document.getElementById('signature_modal');
        const sigPreview = document.getElementById('sig_preview');
        const sigPlaceholder = document.getElementById('sig_placeholder');
        const sigInput = document.getElementById('signature_data');

        document.addEventListener('DOMContentLoaded', function() {
            if (!canvas) return;
            
            signaturePad = new SignaturePad(canvas, {
                minWidth: 1.0,
                maxWidth: 4.0,
                penColor: "#0f172a"
            });

            signaturePad.onEnd = function() {
                lastSignatureData = signaturePad.toData();
            };

            window.addEventListener("resize", resizeCanvas);
            
            document.getElementById('modal_clear').addEventListener('click', (e) => {
                e.preventDefault();
                signaturePad.clear();
                lastSignatureData = null;
            });

            window.openSignatureModal = function() {
                modal.style.display = 'flex';
                lastSignatureData = null;
                setTimeout(resizeCanvas, 50);
            };

            window.closeSignatureModal = function() {
                modal.style.display = 'none';
            };

            window.saveSignature = function() {
                if (signaturePad.isEmpty()) {
                    alert("Pred uložením sa prosím podpíšte.");
                    return;
                }
                const dataUrl = signaturePad.toDataURL('image/png');
                sigInput.value = dataUrl;
                sigPreview.src = dataUrl;
                sigPreview.style.display = 'block';
                sigPlaceholder.style.display = 'none';
                closeSignatureModal();
            };

            let resizeTimeout;
            function resizeCanvas() {
                if (!modal || modal.style.display === 'none') return;
                
                clearTimeout(resizeTimeout);
                const currentData = signaturePad.toData();
                if (currentData.length > 0) lastSignatureData = currentData;

                resizeTimeout = setTimeout(() => {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").setTransform(ratio, 0, 0, ratio, 0, 0);
                    
                    signaturePad.clear();
                    if (lastSignatureData) {
                        signaturePad.fromData(lastSignatureData);
                    }
                }, 100);
            }

            window.handleApproval = function(e) {
                const isOffer = "<?= $doc['type'] ?>" === "offer";
                const hasSignature = sigInput.value !== "";

                if (!hasSignature && !isOffer) {
                    alert("Pred schválením sa prosím podpíšte kliknutím na tlačidlo 'Podpísať dokument'.");
                    e.preventDefault(); return false;
                }
                
                const confirmMsg = isOffer 
                    ? "Naozaj si prajete schváliť túto cenovú ponuku?" 
                    : "Naozaj si prajete schváliť a podpísať tento dokument?";
                
                if (!confirm(confirmMsg)) {
                    e.preventDefault(); return false;
                }
                return true;
            };
            
            // Close modal on escape key
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    closeSignatureModal();
                }
            });
        });
    </script>
</body>
</html>
