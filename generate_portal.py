import os

output_path = r'c:\VolneKreslo\easybooking\index.html'

html_content = '''<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Language" content="sk">
    <meta name="language" content="Slovak">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EasyBooking.sk – Hĺbkový Audit, Systémová Dekonštrukcia & Porovnávací Portál | REZERVOS</title>
    <meta name="description" content="Kompletný reverse-engineering audit administrácie EasyBooking (system.easybooking.sk). Odhalené skryté poplatky, 24 reálnych screenshotov, UX analýza kalendára a porovnanie s Rezervos.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-body: #07090e;
            --bg-card: rgba(15, 20, 31, 0.88);
            --bg-card-hover: rgba(22, 30, 46, 0.96);
            --bg-card-solid: #0d121d;
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(255, 255, 255, 0.18);
            --border-active: rgba(99, 102, 241, 0.5);
            
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-glow: rgba(99, 102, 241, 0.35);
            
            --eb-color: #f59e0b;
            --eb-glow: rgba(245, 158, 11, 0.3);
            --rez-color: #10b981;
            --rez-glow: rgba(16, 185, 129, 0.35);
            
            --accent-green: #10b981;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --accent-cyan: #06b6d4;
            --accent-purple: #a855f7;
            
            --text-main: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 12% 10%, rgba(99, 102, 241, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 88% 18%, rgba(245, 158, 11, 0.10) 0%, transparent 42%),
                radial-gradient(circle at 50% 65%, rgba(16, 185, 129, 0.07) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* Glass Cards */
        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.6);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .glass-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
        }

        /* Sticky Nav Header */
        header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(7, 9, 14, 0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
        }

        .nav-container {
            max-width: 1560px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .brand-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
            flex-shrink: 0;
        }

        .brand-badge .logo-pill {
            background: linear-gradient(135deg, #6366f1, #10b981);
            color: white;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.8px;
            padding: 6px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 0 16px var(--primary-glow);
        }

        .brand-badge span.title-text {
            font-weight: 700;
            font-size: 16px;
            letter-spacing: -0.3px;
        }

        .brand-badge span.versus {
            font-weight: 800;
            color: var(--eb-color);
            background: rgba(245, 158, 11, 0.12);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 12px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        nav.nav-links {
            display: flex;
            gap: 6px;
            align-items: center;
            overflow-x: auto;
            padding: 4px 0;
        }

        nav.nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 10px;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        nav.nav-links a:hover, nav.nav-links a.active {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-live-preview {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
            font-size: 12px;
            font-weight: 700;
            padding: 7px 14px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-live-preview:hover {
            background: rgba(16, 185, 129, 0.25);
            color: #6ee7b7;
            transform: scale(1.02);
        }

        /* Container Layout */
        .page-container {
            max-width: 1560px;
            margin: 0 auto;
            padding: 32px 24px 80px 24px;
        }

        /* Hero Section */
        .hero {
            padding: 48px 0 36px;
            text-align: center;
            position: relative;
        }

        .audit-tag-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.35);
            color: #a5b4fc;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .audit-tag-badge .dot-live {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 10px #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .hero h1 {
            font-size: clamp(32px, 4.5vw, 54px);
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 40%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero h1 span.highlight-eb {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero h1 span.highlight-rez {
            background: linear-gradient(135deg, #34d399, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p.lead {
            font-size: clamp(16px, 1.8vw, 20px);
            color: var(--text-secondary);
            max-width: 980px;
            margin: 0 auto 36px;
            line-height: 1.6;
        }

        /* Hero Key Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .metric-card {
            padding: 24px 20px;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            text-align: left;
            border: 1px solid var(--border);
            background: var(--bg-card);
        }

        .metric-card .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }

        .metric-card.amber .icon-box { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .metric-card.rose .icon-box { background: rgba(244, 63, 94, 0.15); color: #fb7185; }
        .metric-card.green .icon-box { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .metric-card.blue .icon-box { background: rgba(99, 102, 241, 0.15); color: #818cf8; }

        .metric-card .metric-val {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            font-family: 'JetBrains Mono', monospace;
        }

        .metric-card .metric-label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-card .metric-sub {
            font-size: 12px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            color: var(--text-secondary);
        }

        /* Section Headings */
        .section-header {
            margin: 64px 0 28px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .section-tag {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--primary-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title {
            font-size: clamp(24px, 3vw, 36px);
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--text-main);
        }

        .section-desc {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 860px;
        }

        /* Executive Summary Comparison Box */
        .exec-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 20px;
        }

        @media (max-width: 900px) {
            .exec-summary-grid { grid-template-columns: 1fr; }
        }

        .summary-card {
            padding: 32px;
            border-radius: 20px;
            position: relative;
        }

        .summary-card.easybooking {
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.05) 0%, rgba(15, 20, 31, 0.95) 100%);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .summary-card.rezervos {
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.06) 0%, rgba(15, 20, 31, 0.95) 100%);
            border: 1px solid rgba(16, 185, 129, 0.35);
        }

        .summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .summary-header .brand-title {
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-header .score-pill {
            padding: 6px 14px;
            border-radius: 100px;
            font-weight: 800;
            font-size: 13px;
            font-family: 'JetBrains Mono', monospace;
        }

        .summary-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .summary-list li {
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .summary-list li i {
            margin-top: 3px;
            flex-shrink: 0;
            font-size: 15px;
        }

        .summary-card.easybooking .summary-list li i.fa-check { color: #fbbf24; }
        .summary-card.easybooking .summary-list li i.fa-times { color: #f43f5e; }
        .summary-card.rezervos .summary-list li i { color: #10b981; }

        /* INTERACTIVE TCO / SMS CALCULATOR */
        .calculator-wrapper {
            background: linear-gradient(135deg, rgba(20, 27, 45, 0.9) 0%, rgba(13, 18, 30, 0.95) 100%);
            border: 1px solid rgba(99, 102, 241, 0.35);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            margin-top: 30px;
        }

        .calc-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 40px;
            align-items: center;
        }

        @media (max-width: 992px) {
            .calc-grid { grid-template-columns: 1fr; }
            .calculator-wrapper { padding: 24px; }
        }

        .slider-group {
            margin-bottom: 24px;
        }

        .slider-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .slider-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .slider-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 16px;
            color: var(--primary-light);
            background: rgba(99, 102, 241, 0.15);
            padding: 2px 10px;
            border-radius: 6px;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        input[type=range] {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            outline: none;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
        }

        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-light);
            cursor: pointer;
            box-shadow: 0 0 10px var(--primary);
            transition: transform 0.1s;
        }

        input[type=range]::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }

        .calc-result-box {
            background: rgba(10, 14, 23, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .calc-result-box .comparison-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 14px;
        }

        .calc-result-box .savings-highlight {
            margin-top: 24px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(6, 182, 212, 0.15));
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 16px;
            padding: 20px;
        }

        .savings-amount {
            font-size: 38px;
            font-weight: 900;
            font-family: 'JetBrains Mono', monospace;
            color: #34d399;
            letter-spacing: -1px;
        }

        /* MODULE CARDS & SCREENSHOT SHOWCASE */
        .module-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
        }

        .module-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            transition: all 0.3s ease;
        }

        @media (max-width: 1100px) {
            .module-card { grid-template-columns: 1fr; }
        }

        .module-card:hover {
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
        }

        .module-screenshot-col {
            background: #090c14;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            cursor: pointer;
        }

        @media (max-width: 1100px) {
            .module-screenshot-col { border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.06); }
        }

        .module-screenshot-col .img-wrapper {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            width: 100%;
        }

        .module-screenshot-col img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }

        .module-screenshot-col:hover img {
            transform: scale(1.02);
        }

        .zoom-overlay-hint {
            position: absolute;
            bottom: 14px;
            right: 14px;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            pointer-events: none;
        }

        .thumb-switcher {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            width: 100%;
            justify-content: center;
        }

        .thumb-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .thumb-btn:hover, .thumb-btn.active {
            background: var(--primary-light);
            color: #07090e;
            border-color: var(--primary-light);
        }

        .module-content-col {
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .module-header {
            margin-bottom: 18px;
        }

        .module-badge-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .badge {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-cat { background: rgba(255, 255, 255, 0.08); color: var(--text-secondary); }
        .badge-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-green { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-rose { background: rgba(244, 63, 94, 0.15); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3); }

        .module-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .module-desc {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .deep-dive-box {
            background: rgba(10, 14, 23, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .deep-dive-box h4 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .feature-bullets {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .feature-bullets li {
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .feature-bullets li i {
            margin-top: 3px;
            font-size: 13px;
        }

        .feature-bullets li.weakness i { color: #f43f5e; }
        .feature-bullets li.strength i { color: #10b981; }
        .feature-bullets li.neutral i { color: #f59e0b; }

        .rezervos-advantage-banner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(6, 182, 212, 0.08));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 14px 18px;
            margin-top: 14px;
        }

        .rezervos-advantage-banner h5 {
            font-size: 13px;
            font-weight: 700;
            color: #34d399;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rezervos-advantage-banner p {
            font-size: 12.5px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        /* ALL 24 SCREENSHOTS COMPREHENSIVE GALLERY */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .gallery-item {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .gallery-item:hover {
            border-color: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        .gallery-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            object-position: top;
            display: block;
            border-bottom: 1px solid var(--border);
        }

        .gallery-item-info {
            padding: 12px 14px;
        }

        .gallery-item-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .gallery-item-cat {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* PRICING TABLE SECTION */
        .pricing-table-wrapper {
            overflow-x: auto;
            margin-top: 24px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: var(--bg-card);
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .table-custom th {
            background: rgba(13, 18, 30, 0.95);
            padding: 18px 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .table-custom td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
        }

        .table-custom tr:last-child td {
            border-bottom: none;
        }

        .table-custom tr:hover td {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-main);
        }

        .table-custom td.plan-name {
            font-weight: 700;
            font-size: 16px;
            color: var(--text-main);
        }

        .table-custom td.price-cell {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 16px;
        }

        /* FULL FEATURE COMPARISON MATRIX */
        .matrix-section {
            margin-top: 60px;
        }

        .matrix-category-header {
            background: rgba(99, 102, 241, 0.15) !important;
            color: #a5b4fc !important;
            font-weight: 800 !important;
            font-size: 14px !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .check-yes { color: #10b981; font-weight: 700; font-size: 16px; }
        .check-no { color: #f43f5e; font-weight: 700; font-size: 16px; }
        .check-partial { color: #f59e0b; font-weight: 600; font-size: 13px; }

        /* LIGHTBOX MODAL */
        .lightbox-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(4, 6, 11, 0.95);
            backdrop-filter: blur(20px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 24px;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .lightbox-modal.active {
            display: flex;
            opacity: 1;
        }

        .lightbox-content-box {
            max-width: 1400px;
            max-height: 94vh;
            display: flex;
            flex-direction: column;
            background: var(--bg-card-solid);
            border: 1px solid var(--border-active);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
            position: relative;
        }

        .lightbox-top-bar {
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(10, 14, 23, 0.95);
            border-bottom: 1px solid var(--border);
        }

        .lightbox-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lightbox-close-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .lightbox-close-btn:hover {
            background: #f43f5e;
            border-color: #f43f5e;
            transform: scale(1.05);
        }

        .lightbox-img-container {
            overflow: auto;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background: #05070c;
        }

        .lightbox-img-container img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        }

        /* FOOTER */
        footer {
            margin-top: 80px;
            padding: 40px 0;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        footer a {
            color: var(--primary-light);
            text-decoration: none;
        }
    </style>
</head>
<body>

    <!-- Sticky Nav Header -->
    <header>
        <div class="nav-container">
            <a href="#" class="brand-badge">
                <div class="logo-pill"><i class="fa-solid fa-shield-halved"></i> REZERVOS</div>
                <span class="versus">VS</span>
                <span class="title-text">EasyBooking.sk Audit</span>
            </a>

            <nav class="nav-links">
                <a href="#summary"><i class="fa-solid fa-bolt"></i> Zhrnutie</a>
                <a href="#kalkulacka"><i class="fa-solid fa-calculator"></i> TCO Kalkulačka</a>
                <a href="#moduly"><i class="fa-solid fa-cubes"></i> 16 Modulov</a>
                <a href="#galeria-24"><i class="fa-solid fa-images"></i> Galéria 24 Snímok</a>
                <a href="#cennik-pitva"><i class="fa-solid fa-tags"></i> Skryté Poplatky</a>
                <a href="#matica"><i class="fa-solid fa-table-cells"></i> Matrica (40+)</a>
                <a href="#strategia"><i class="fa-solid fa-trophy"></i> Strategický Plán</a>
            </nav>

            <div class="nav-actions">
                <a href="#galeria-24" class="btn-live-preview">
                    <i class="fa-solid fa-camera"></i> Všetkých 24 Snímok
                </a>
            </div>
        </div>
    </header>

    <div class="page-container">

        <!-- HERO SECTION -->
        <section class="hero">
            <div class="audit-tag-badge">
                <div class="dot-live"></div>
                AUTENTICKÝ REVERSE-ENGINEERING AUDIT 2026 // LIVE ADMIN DATA
            </div>

            <h1>
                Hĺbkový Audit <span class="highlight-eb">EasyBooking.sk</span><br>
                a Prečo <span class="highlight-rez">REZERVOS</span> Dominuje Trhu
            </h1>

            <p class="lead">
                Kompletná analýza slovenského rezervačného systému <strong>system.easybooking.sk</strong> z pohľadu majiteľa salónu. 
                Preskúmali sme všetky moduly, kalendár, CRM, nastavenia platieb a odhalili skutočné prevádzkové náklady vrátane <strong>2,5 % + 0,35 € transakčných provízií</strong>, 
                predražených <strong>0,083 € SMS</strong> a výpalníckeho <strong>29,90 € poplatku</strong> za odstránenie loga.
            </p>

            <!-- KEY METRICS CARDS -->
            <div class="metrics-grid">
                <div class="metric-card rose">
                    <div class="icon-box"><i class="fa-solid fa-comment-sms"></i></div>
                    <div class="metric-val">0,083 €</div>
                    <div class="metric-label">Cena za 1 SMS (EasyBooking)</div>
                    <div class="metric-sub">Rezervos ponúka <strong>0,045 €</strong> alebo SMS priamo v balíku (úspora 46 %).</div>
                </div>

                <div class="metric-card amber">
                    <div class="icon-box"><i class="fa-solid fa-credit-card"></i></div>
                    <div class="metric-val">2,5% + 0,35€</div>
                    <div class="metric-label">Transakčná prirážka</div>
                    <div class="metric-sub">Účtujú aj za <em>osobné platby na mieste</em>! Rezervos si neberie 0,00 % provízie.</div>
                </div>

                <div class="metric-card amber">
                    <div class="icon-box"><i class="fa-solid fa-eye-slash"></i></div>
                    <div class="metric-val">29,90 €</div>
                    <div class="metric-label">Poplatok za White-label</div>
                    <div class="metric-sub">Jednorázový poplatok len za vypnutie ich reklamy. V Rezervos štandardne <strong>0 €</strong>.</div>
                </div>

                <div class="metric-card blue">
                    <div class="icon-box"><i class="fa-solid fa-layer-group"></i></div>
                    <div class="metric-val">16 Modulov</div>
                    <div class="metric-label">Zdokumentovaných</div>
                    <div class="metric-sub">24 detailných screenshotov v plnom rozlíšení z overeného účtu.</div>
                </div>

                <div class="metric-card green">
                    <div class="icon-box"><i class="fa-solid fa-gauge-high"></i></div>
                    <div class="metric-val">64 vs 96</div>
                    <div class="metric-label">UX & Funkčné Skóre</div>
                    <div class="metric-sub">Rezervos poskytuje modernú PWA architektúru, Apple Wallet a AI asistentov.</div>
                </div>
            </div>
        </section>

        <!-- EXECUTIVE SUMMARY SECTION -->
        <section id="summary">
            <div class="section-header">
                <div class="section-tag"><i class="fa-solid fa-scale-balanced"></i> Manažérske Zhrnutie</div>
                <h2 class="section-title">Kde EasyBooking Nestačí a Čo Máme Lepšie</h2>
                <p class="section-desc">Objektívne porovnanie silných a slabých stránok oboch systémov na základe reálneho testovania v prevádzke Kaderníctva.</p>
            </div>

            <div class="exec-summary-grid">
                <!-- EasyBooking Card -->
                <div class="summary-card easybooking">
                    <div class="summary-header">
                        <div class="brand-title" style="color: #fbbf24;">
                            <i class="fa-solid fa-calendar-xmark"></i> EasyBooking.sk
                        </div>
                        <div class="score-pill" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24;">
                            SKÓRE: 64 / 100
                        </div>
                    </div>

                    <ul class="summary-list">
                        <li>
                            <i class="fa-solid fa-check"></i>
                            <div><strong>AI nahrávanie cenníka:</strong> Možnosť odfotiť starý cenník a systém ho predvyplní (pekná marketingová funkcia).</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-check"></i>
                            <div><strong>Zákaznícke dopyty:</strong> Samostatný inbox pre klientov, ktorí nenašli voľný termín v kalendári.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-times"></i>
                            <div><strong>Agresívna monetizácia platieb:</strong> 2,5 % + 0,35 € za každú transakciu (aj osobné QR platby v salóne!).</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-times"></i>
                            <div><strong>Drahé SMS notifikácie:</strong> 0,083 € za každú odoslanú správu. Salón so 150 klientmi minie 25 € len na SMS.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-times"></i>
                            <div><strong>Limitovaný plán Basic (0 €):</strong> Nemá zamestnancov, permanentky, poukážky, štatistiky ani QR platby. Slúži len ako pasca na upsell.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-times"></i>
                            <div><strong>Zastarané UI kalendára:</strong> Chýba plynulý drag & drop posun, vizuálne zobrazenie prestávok (buffer time) a mobilný pohľad je neprehľadný.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-times"></i>
                            <div><strong>Chýba Apple & Google Wallet:</strong> Žiadne digitálne vernostné kartičky do mobilnej peňaženky smartfónu.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-times"></i>
                            <div><strong>Výpalné 29,90 € za White-label:</strong> Bez zaplatenia svieti klientom v rezervácii obrovské logo EasyBooking.</div>
                        </li>
                    </ul>
                </div>

                <!-- Rezervos Card -->
                <div class="summary-card rezervos">
                    <div class="summary-header">
                        <div class="brand-title" style="color: #34d399;">
                            <i class="fa-solid fa-shield-halved"></i> REZERVOS Platform
                        </div>
                        <div class="score-pill" style="background: rgba(16, 185, 129, 0.2); color: #34d399;">
                            SKÓRE: 96 / 100
                        </div>
                    </div>

                    <ul class="summary-list">
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>0 % provízia z platieb:</strong> Priame prepojenie na váš vlastný Stripe alebo platobný terminál. Peniaze idú priamo vám na účet bez poplatkov tretej strane.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>Najlacnejšie SMS na Slovensku:</strong> 0,045 € za SMS alebo predplatený balík v cene licencie (ušetríte až polovicu nákladov).</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>100% White-label zadarmo:</strong> Vaša vlastná doména, vaše farby, vaše logo. Žiadny poplatok 29,90 € za odstránenie reklamy.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>Digitálne vernostné karty:</strong> Natívne pasy pre Apple Wallet a Google Pay s push notifikáciami priamo na zamknutú obrazovku.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>Bleskový Drag & Drop kalendár:</strong> Moderné FullCalendar rozhranie s automatickým výpočtom buffer time (upratovanie/príprava) a farebnými stavmi.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>No-Show ochrana a zálohy:</strong> Automatická predautorizácia karty cez Stripe. Pri neúčasti systém stiahne storno poplatok.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>Komplexný CRM systém:</strong> Štítky (VIP, mešká, problémový), kompletná história nákupov a automatické narodeninové zľavy.</div>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <div><strong>Prenájom kresiel a stoličiek:</strong> Špecializovaný modul pre kaderníctva a barber shopy s viacerými nezávislými ičármi.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- INTERACTIVE CALCULATOR (TCO) -->
        <section id="kalkulacka">
            <div class="section-header">
                <div class="section-tag"><i class="fa-solid fa-calculator"></i> Kalkulátor Nákladov (TCO)</div>
                <h2 class="section-title">Koľko Salón Skutočne Zaplatí EasyBookingu vs Rezervos?</h2>
                <p class="section-desc">Pohybujte posuvníkmi a zistite reálne ročné náklady po započítaní skrytých poplatkov za platby, drahých SMS a licencií.</p>
            </div>

            <div class="calculator-wrapper">
                <div class="calc-grid">
                    <div class="calc-controls">
                        <!-- Slider 1 -->
                        <div class="slider-group">
                            <div class="slider-header">
                                <span class="slider-label"><i class="fa-solid fa-users"></i> Počet zamestnancov / kresiel:</span>
                                <span class="slider-value" id="val-staff">3 pracovníci</span>
                            </div>
                            <input type="range" id="input-staff" min="1" max="10" value="3" step="1">
                        </div>

                        <!-- Slider 2 -->
                        <div class="slider-group">
                            <div class="slider-header">
                                <span class="slider-label"><i class="fa-solid fa-calendar-check"></i> Rezervácií mesačne:</span>
                                <span class="slider-value" id="val-bookings">250 termínov</span>
                            </div>
                            <input type="range" id="input-bookings" min="50" max="1200" value="250" step="25">
                        </div>

                        <!-- Slider 3 -->
                        <div class="slider-group">
                            <div class="slider-header">
                                <span class="slider-label"><i class="fa-solid fa-euro-sign"></i> Priemerná cena služby:</span>
                                <span class="slider-value" id="val-ticket">35 €</span>
                            </div>
                            <input type="range" id="input-ticket" min="15" max="100" value="35" step="5">
                        </div>

                        <!-- Slider 4 -->
                        <div class="slider-group">
                            <div class="slider-header">
                                <span class="slider-label"><i class="fa-solid fa-credit-card"></i> Podiel online platieb kartou / záloh:</span>
                                <span class="slider-value" id="val-card-share">30 %</span>
                            </div>
                            <input type="range" id="input-card-share" min="0" max="100" value="30" step="5">
                        </div>

                        <!-- Slider 5 -->
                        <div class="slider-group">
                            <div class="slider-header">
                                <span class="slider-label"><i class="fa-solid fa-comment-sms"></i> Odoslaných SMS mesačne (pripomienky):</span>
                                <span class="slider-value" id="val-sms">250 SMS</span>
                            </div>
                            <input type="range" id="input-sms" min="0" max="1000" value="250" step="25">
                        </div>
                    </div>

                    <div class="calc-result-box">
                        <h3 style="font-size: 18px; margin-bottom: 16px; color: var(--text-main);">
                            <i class="fa-solid fa-chart-pie"></i> Mesačné Prevádzkové Náklady
                        </h3>

                        <div class="comparison-row">
                            <span style="color: var(--text-secondary);">Mesačný poplatok za licenciu:</span>
                            <span>
                                <strong style="color: #fbbf24;" id="eb-plan-cost">19,90 €</strong> (EB) / 
                                <strong style="color: #34d399;" id="rez-plan-cost">19,00 €</strong> (Rez)
                            </span>
                        </div>

                        <div class="comparison-row">
                            <span style="color: var(--text-secondary);">Náklady na SMS notifikácie:</span>
                            <span>
                                <strong style="color: #fbbf24;" id="eb-sms-cost">20,75 €</strong> (0,083 €) / 
                                <strong style="color: #34d399;" id="rez-sms-cost">11,25 €</strong> (0,045 €)
                            </span>
                        </div>

                        <div class="comparison-row">
                            <span style="color: var(--text-secondary);">Poplatky za online platby:</span>
                            <span>
                                <strong style="color: #fbbf24;" id="eb-fee-cost">91,88 €</strong> (2,5% + 0,35€) / 
                                <strong style="color: #34d399;" id="rez-fee-cost">0,00 €</strong> (0%)
                            </span>
                        </div>

                        <div class="comparison-row" style="font-size: 16px; font-weight: 800; border-top: 1px solid rgba(255,255,255,0.15); margin-top: 8px; padding-top: 12px;">
                            <span>CELKOM MESAČNE:</span>
                            <span>
                                <span style="color: #fbbf24;" id="eb-total-month">132,53 €</span> vs 
                                <span style="color: #34d399;" id="rez-total-month">30,25 €</span>
                            </span>
                        </div>

                        <div class="savings-highlight">
                            <div style="font-size: 13px; text-transform: uppercase; font-weight: 700; color: #a7f3d0; letter-spacing: 0.5px;">
                                Ročná Úspora s Rezervos:
                            </div>
                            <div class="savings-amount" id="annual-savings">1 227 € / rok</div>
                            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                                Salón ušetrí viac než <strong>77 %</strong> svojich nákladov na rezervačný systém!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 16 MODULES DEEP DIVE WITH SCREENSHOTS -->
        <section id="moduly">
            <div class="section-header">
                <div class="section-tag"><i class="fa-solid fa-cubes"></i> Systematický Rozbor Administrácie</div>
                <h2 class="section-title">16 Modulov EasyBooking pod Drobnohľadom</h2>
                <p class="section-desc">Kliknite na ľubovoľný screenshot pre zväčšenie do plného rozlíšenia. Prepínajte podzobrazenia (napr. Týždeň / Deň / Mesiac).</p>
            </div>

            <!-- Filters -->
            <div class="module-filters">
                <button class="filter-btn active" onclick="filterModules('all')">Všetky moduly (16)</button>
                <button class="filter-btn" onclick="filterModules('kalendar')">Kalendár & Rezervácie</button>
                <button class="filter-btn" onclick="filterModules('crm')">Klienti & CRM</button>
                <button class="filter-btn" onclick="filterModules('sluzby')">Služby & Tím</button>
                <button class="filter-btn" onclick="filterModules('financie')">Financie & Balíky</button>
                <button class="filter-btn" onclick="filterModules('nastavenia')">Nastavenia & Formulár</button>
            </div>

            <div class="modules-grid">

                <!-- MODUL 1: TÝŽDENNÝ KALENDÁR -->
                <div class="module-card" data-cat="kalendar">
                    <div class="module-screenshot-col">
                        <div class="img-wrapper" onclick="openLightbox(document.getElementById('cal-img').src, 'Modul 1: Kalendár v EasyBooking')">
                            <img id="cal-img" src="screenshots/calendar_week.png" alt="Týždenný Kalendár EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                        <div class="thumb-switcher">
                            <button class="thumb-btn active" onclick="switchImg('cal-img', 'screenshots/calendar_week.png', this)">Týždeň</button>
                            <button class="thumb-btn" onclick="switchImg('cal-img', 'screenshots/calendar_day.png', this)">Deň</button>
                            <button class="thumb-btn" onclick="switchImg('cal-img', 'screenshots/calendar_month.png', this)">Mesiac</button>
                        </div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Kalendár & Rezervácie</span>
                                <span class="badge badge-amber">UI Skóre: 65%</span>
                                <span class="badge badge-rose">Chýba Drag & Drop</span>
                            </div>
                            <h3 class="module-title">1. Kalendár & Prehľad Termínov (Týždeň/Deň/Mesiac)</h3>
                            <p class="module-desc">Základné týždenné rozvrhnutie kalendára rozdelené podľa hodín (08:00 - 20:00). Poskytuje prepínanie medzi Dňom, Týždňom a Mesiacom, výber dátumu a filter zamestnancov.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Absencia plynulého Drag & Drop:</strong> Presúvanie rezervácií medzi časmi a pracovníkmi nie je intuitívne a vyžaduje otváranie editovacieho okna.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Neviditeľný Buffer Time:</strong> Pauza na dezinfekciu a upratovanie sa nezobrazuje ako blok v kalendári, čo vedie k prekrytiu termínov.</li>
                                <li class="neutral"><i class="fa-solid fa-minus"></i> <strong>Preplnenosť na mobiloch:</strong> Týždenné zobrazenie na smartfóne vyžaduje masívny horizontálny scroll.</li>
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Rýchly skok na Dnes:</strong> Funkčné tlačidlo 'Dnes' a možnosť blokácie času jedným klikom.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Moderný FullCalendar v6 s hardvérovo akcelerovaným drag & drop presunom, farebnými tagmi podľa stavu (potvrdené, čaká na zálohu, vybavené), okamžitou vizualizáciou prestávok a bezchybnou mobilnou swipe navigáciou.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 2: VYTVORENIE REZERVÁCIE -->
                <div class="module-card" data-cat="kalendar">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/new_reservation_modal.png', 'Modul 2: Vytvorenie Novej Rezervácie (Modal)')">
                        <div class="img-wrapper">
                            <img src="screenshots/new_reservation_modal.png" alt="Vytvorenie novej rezervácie modal" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Kalendár & Rezervácie</span>
                                <span class="badge badge-amber">UI Skóre: 70%</span>
                                <span class="badge badge-green">Opakované Termíny</span>
                            </div>
                            <h3 class="module-title">2. Formulár Novej Rezervácie (Modal)</h3>
                            <p class="module-desc">Vyskakovacie okno pre manuálne zadanie rezervácie personálom: výber služby, pracovníka, času od-do, kontaktných údajov klienta a opakovania rezervácie.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Opakovanie rezervácie:</strong> Podpora pre periodické termíny (týždenne, mesačne) pre stálych klientov.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Slabé CRM prepojenie:</strong> Pri písaní mena chýba inteligentný autocomplete s okamžitým zobrazením histórie, dlhov alebo VIP statusu klienta.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Žiadna možnosť online zálohy:</strong> Personál nemôže priamo z tohto okna vygenerovať link na platbu kartou pre klienta.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Okamžitý autocomplete klienta s náhľadom na jeho preferencie, no-show históriu, možnosť poslať jedným klikom SMS platobný link na zálohu a automatická detekcia voľných okien bez kolízií.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 3: ZOZNAM REZERVÁCIÍ -->
                <div class="module-card" data-cat="kalendar">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/reservations_list.png', 'Modul 3: Zoznam Rezervácií & Filtre')">
                        <div class="img-wrapper">
                            <img src="screenshots/reservations_list.png" alt="Zoznam rezervácií v EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Kalendár & Rezervácie</span>
                                <span class="badge badge-amber">UI Skóre: 68%</span>
                                <span class="badge badge-rose">Starší Dizajn Tabuľky</span>
                            </div>
                            <h3 class="module-title">3. Tabuľkový Zoznam Rezervácií & Filtre</h3>
                            <p class="module-desc">Prehľad všetkých vytvorených rezervácií s filtrami podľa zamestnancov, stavu (Vybavená, Schválená, Čaká, Zrušená), dátumového rozsahu a exportom do Excelu.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Filtrovanie stavov:</strong> Možnosť zobraziť si len zrušené alebo čakajúce termíny.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Chýbajú hromadné akcie:</strong> Nemožno označiť 10 termínov a hromadne ich posunúť, potvrdiť alebo im poslať SMS správu o výpadku.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Slabá mobilná responzivita:</strong> Tabuľka preteká mimo obrazovky na bežných telefónoch.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Hromadné operácie (Bulk SMS, hromadné schvaľovanie), prispôsobené zobrazenie v štýle kariet na mobile, okamžité vyhľadávanie bez reloadu stránky a exporty do PDF/XLS jedným klikom.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 4: ZÁKAZNÍCKE DOPYTY -->
                <div class="module-card" data-cat="crm">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/customer_queries.png', 'Modul 4: Zákaznícke Dopyty (Waitlist)')">
                        <div class="img-wrapper">
                            <img src="screenshots/customer_queries.png" alt="Zákaznícke dopyty EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Klienti & CRM</span>
                                <span class="badge badge-green">Zaujímavá Funkcia</span>
                                <span class="badge badge-amber">Manuálne Spracovanie</span>
                            </div>
                            <h3 class="module-title">4. Dopyty Zákazníkov & Čakacia Listina</h3>
                            <p class="module-desc">Modul zachytávajúci požiadavky klientov, ktorí nenašli vhodný termín cez online widget a nechali žiadosť o kontaktovanie.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Zber stratených zákazníkov:</strong> Zákazník neodíde naprázdno, ale odošle dopyt so želaným časom.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Úplne manuálny proces:</strong> Keď sa uvoľní termín (napr. stornom), EasyBooking NEVIE automaticky poslať SMS prvému v rade čakateľov. Salón musí volať ručne.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p><strong>Automatický Last-Minute & Waitlist Bot:</strong> Akonáhle niekto zruší termín, Rezervos automaticky odošle SMS prvým 3 čakateľom s unikátnym linkom. Kto prvý klikne, termín získa – nulové straty pre salón!</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 5: ZOZNAM ZÁKAZNÍKOV & ŠTÍTKY -->
                <div class="module-card" data-cat="crm">
                    <div class="module-screenshot-col">
                        <div class="img-wrapper" onclick="openLightbox(document.getElementById('crm-img').src, 'Modul 5: CRM Klientov & Štítky')">
                            <img id="crm-img" src="screenshots/customers_list.png" alt="Zoznam zákazníkov CRM" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                        <div class="thumb-switcher">
                            <button class="thumb-btn active" onclick="switchImg('crm-img', 'screenshots/customers_list.png', this)">Zoznam Klientov</button>
                            <button class="thumb-btn" onclick="switchImg('crm-img', 'screenshots/customer_tags.png', this)">Správa Štítkov</button>
                        </div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Klienti & CRM</span>
                                <span class="badge badge-green">Štítkovanie (Tags)</span>
                                <span class="badge badge-rose">Slabá Segmentácia</span>
                            </div>
                            <h3 class="module-title">5. CRM Klientov, História Návštev & Štítky</h3>
                            <p class="module-desc">Adresár zákazníkov evidujúci meno, telefón, e-mail, dátum poslednej návštevy, celkový počet termínov a farebné štítky (VIP, mešká, problémový).</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Farebné štítky:</strong> Možnosť definovať vlastné štítky a priradiť ich zákazníkom.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Chýba hodnota zákazníka (LTV):</strong> V tabuľke nie je vidieť celkovú minutú sumu v eurách, iba počet návštev.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Žiadny automatický re-engagement:</strong> Nemožno nastaviť pravidlo: 'Ak neprišiel 60 dní, pošli zľavu 15%'.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Kompletné RFM skóre klientov (Recency, Frequency, Monetary value), automatické Blacklist varovania pri notorických no-show klientoch a automatické pripomienky po 4-6 týždňoch pre opakované farbenie/strihanie.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 6: SLUŽBY & KATEGÓRIE -->
                <div class="module-card" data-cat="sluzby">
                    <div class="module-screenshot-col">
                        <div class="img-wrapper" onclick="openLightbox(document.getElementById('srv-img').src, 'Modul 6: Katalóg Služieb')">
                            <img id="srv-img" src="screenshots/services_list.png" alt="Služby v EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                        <div class="thumb-switcher">
                            <button class="thumb-btn active" onclick="switchImg('srv-img', 'screenshots/services_list.png', this)">Zoznam Služieb</button>
                            <button class="thumb-btn" onclick="switchImg('srv-img', 'screenshots/service_add_edit_form.png', this)">Formulár Služby</button>
                            <button class="thumb-btn" onclick="switchImg('srv-img', 'screenshots/service_categories.png', this)">Kategórie</button>
                        </div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Služby & Tím</span>
                                <span class="badge badge-amber">UI Skóre: 72%</span>
                                <span class="badge badge-green">Akciové Ceny</span>
                            </div>
                            <h3 class="module-title">6. Katalóg Služieb, Cenník & Kategórie</h3>
                            <p class="module-desc">Správa portfólia služieb: názov, trvanie, cena s DPH, zaradenie do kategórií, priradenie zamestnancom a nastavenie zliav.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Akciové ceny:</strong> Možnosť nastaviť zľavnenú cenu s preškrtnutou pôvodnou cenou.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Zložité nastavovanie príplatkov:</strong> Rôzne dĺžky vlasov (krátke/dlhé) sa musia riešiť ako úplne samostatné služby, čo nafukuje cenník.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Buffer time:</strong> Nedá sa jednoducho nastaviť rôzny čas prípravy pre rôznych zamestnancov.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Dynamické varianty služieb (napr. dĺžka vlasov, použité prémiové sérum) ako modifikátory priamo v jednom riadku, individuálne časy a ceny podľa skúseností pracovníka (Junior vs Master).</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 7: ZAMESTNANCI A TÍM -->
                <div class="module-card" data-cat="sluzby">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/staff_management.png', 'Modul 7: Správa Pracovníkov a Tímu')">
                        <div class="img-wrapper">
                            <img src="screenshots/staff_management.png" alt="Správa tímu EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Služby & Tím</span>
                                <span class="badge badge-rose">Umelé Limity Balíkov</span>
                                <span class="badge badge-amber">UI Skóre: 60%</span>
                            </div>
                            <h3 class="module-title">7. Správa Pracovníkov & Provízií</h3>
                            <p class="module-desc">Zoznam personálu salónu, priradenie vykonávaných služieb, notifikačné nastavenia pre zamestnancov a prístupové práva.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Prísne obmedzenia počtu:</strong> Balík Regular má len 1 zamestnanca! Ak máte 2 kaderníčky, musíte platiť dvojnásobok (Advanced plán za 19,90 €).</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Chýba plnohodnotný provízny systém:</strong> Žiadny automatický výpočet provízií z práce vs. predaja produktov na konci mesiaca.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Automatické mzdové podklady, percentuálne provízie podľa kategórií, prepínanie prenájmu kresla (stoličkári s vlastným IČO a vlastnými peniazmi) bez príplatku za drahší balík.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 8: DARČEKOVÉ POUKÁŽKY & ZĽAVY -->
                <div class="module-card" data-cat="financie">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/vouchers_discounts.png', 'Modul 8: Poukážky & Zľavy')">
                        <div class="img-wrapper">
                            <img src="screenshots/vouchers_discounts.png" alt="Poukážky a zľavy v EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Financie & Balíky</span>
                                <span class="badge badge-rose">Provízia 2,5% + 0,35€</span>
                                <span class="badge badge-amber">Nedostupné v Basic</span>
                            </div>
                            <h3 class="module-title">8. Darčekové Poukážky & Zľavové Kódy</h3>
                            <p class="module-desc">Predaj a evidencia elektronických darčekových poukazov s unikátnym kódom, platnosťou a možnosťou uplatnenia pri rezervácii.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Skrytý poplatok:</strong> Za každý predaný poukaz si EasyBooking strhne 2,5 % + 0,35 € províziu!</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>V Basic verzii uzamknuté:</strong> Bezplatná verzia vôbec neumožňuje predávať poukážky.</li>
                                <li class="neutral"><i class="fa-solid fa-minus"></i> <strong>Grafika poukážok:</strong> Veľmi jednoduché PDF šablóny bez možnosti prémiového personalizovaného brandingu salónu.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Krásne dizajnérske PDF poukážky s venovaním, priamy predaj cez Stripe bez akejkoľvek provízie pre nás a automatická evidencia zostatku pri postupnom čerpaní.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 9: RECENZIE A HODNOTENIA -->
                <div class="module-card" data-cat="crm">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/reviews_ratings.png', 'Modul 9: Recenzie & Hodnotenia')">
                        <div class="img-wrapper">
                            <img src="screenshots/reviews_ratings.png" alt="Recenzie a hodnotenia EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Klienti & CRM</span>
                                <span class="badge badge-green">Reputačný Manažment</span>
                                <span class="badge badge-amber">UI Skóre: 70%</span>
                            </div>
                            <h3 class="module-title">9. Zber Recenzií & Reputačný Manažment</h3>
                            <p class="module-desc">Automatická žiadosť o hodnotenie zaslaná klientovi po absolvovaní termínu. Možnosť schválenia pred zobrazením na verejnom profile.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Ochrana pred trolmi:</strong> Recenziu môže napísať len overený klient, ktorý mal reálnu rezerváciu.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Nevedie na Google My Business:</strong> Recenzie zostávajú uväznené v systéme EasyBooking namiesto toho, aby zvyšovali SEO skóre salónu na Google Maps.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p><strong>Google Review Booster:</strong> Spokojných klientov (5 hviezdičiek) automaticky presmerujeme priamo na Google Profil prevádzky, čím salón raketovo stúpa v lokálnom vyhľadávaní!</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 10: ŠTATISTIKY A TRŽBY -->
                <div class="module-card" data-cat="financie">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/statistics.png', 'Modul 10: Štatistiky a Reporty')">
                        <div class="img-wrapper">
                            <img src="screenshots/statistics.png" alt="Štatistiky a reporty EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Financie & Balíky</span>
                                <span class="badge badge-amber">UI Skóre: 62%</span>
                                <span class="badge badge-rose">Základné Grafy</span>
                            </div>
                            <h3 class="module-title">10. Štatistiky, Tržby & Výkonnostné Prehľady</h3>
                            <p class="module-desc">Analytický panel sledujúci vývoj tržieb, počet prijatých rezervácií, podiel zrušených termínov a obľúbenosť služieb.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Povrchné metriky:</strong> Chýbajú pokročilé ukazovatele ako miera udržania klientov (retention rate), priemerná obsadenosť kresiel v % a priemerná hodnota košíka.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Nedostupné v Basic:</strong> Malé salóny na neplatenom pláne nemajú k číslam vôbec prístup.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Profesionálny BI dashboard: vyťaženosť v špičkách, analýza stratových hodín, predikcia tržieb na ďalší mesiac pomocou AI a presná ziskovosť na každé kreslo.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 11: PEŇAŽENKA & KREDITY -->
                <div class="module-card" data-cat="financie">
                    <div class="module-screenshot-col">
                        <div class="img-wrapper" onclick="openLightbox(document.getElementById('wallet-img').src, 'Modul 11: Peňaženka a SMS Kredity')">
                            <img id="wallet-img" src="screenshots/credits_subscription.png" alt="Kredity a peňaženka EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                        <div class="thumb-switcher">
                            <button class="thumb-btn active" onclick="switchImg('wallet-img', 'screenshots/credits_subscription.png', this)">Predplatné & Kredity</button>
                            <button class="thumb-btn" onclick="switchImg('wallet-img', 'screenshots/wallet_overview.png', this)">Prehľad Peňaženky</button>
                        </div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Financie & Balíky</span>
                                <span class="badge badge-rose">0,083 € / SMS</span>
                                <span class="badge badge-amber">Prepaid Model</span>
                            </div>
                            <h3 class="module-title">11. Peňaženka, Dobíjanie Kreditov & Predplatné</h3>
                            <p class="module-desc">Interný kreditný systém na dobitie zostatku, z ktorého sa automaticky strhávajú poplatky za odoslané SMS správy a predplatné.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Extrémna cena SMS:</strong> 0,083 € za každú SMS správu je takmer dvojnásobok trhovej ceny telekomunikačných brán na Slovensku.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Riziko výpadku notifikácií:</strong> Ak salóne klesne kredit na nulu, klienti nedostanú pripomienku a neprídu na termín.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Férové SMS za 0,045 € bez nutnosti umelého blokovania vysokého kreditu alebo automatické inteligentné notifikácie cez WhatsApp a Push do Apple/Google Wallet zadarmo!</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 12: NASTAVENIA FORMULÁRA & FARBY -->
                <div class="module-card" data-cat="nastavenia">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/booking_form_settings.png', 'Modul 12: Nastavenia Rezervačného Formulára')">
                        <div class="img-wrapper">
                            <img src="screenshots/booking_form_settings.png" alt="Nastavenia rezervačného formulára" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Nastavenia & Formulár</span>
                                <span class="badge badge-amber">UI Skóre: 75%</span>
                                <span class="badge badge-green">Pekný Výber Farieb</span>
                            </div>
                            <h3 class="module-title">12. Online Rezervačný Widget & Prispôsobenie</h3>
                            <p class="module-desc">Generátor iFrame kódu pre webovú stránku salónu, výber primárnej farby, textov upozornení, odkazov na Instagram a Facebook.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Kopírovanie iFrame:</strong> Jednoduché vygenerovanie kódu na vloženie do WordPress alebo Wix.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Všadeprítomné logo EasyBooking:</strong> Ak nezaplatíte 29,90 € jednorazový poplatok, widget propaguje EasyBooking namiesto vašej značky.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Pomalé načítanie iFrame:</strong> Na mobiloch má iframe tendenciu scrollovať sa v okne a pôsobiť cudzorodo.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Moderný Standalone Booking Link (napr. <code>rezervos.sk/vas-salon</code>) optimalizovaný pre Instagram bio, bleskové načítanie do 300 ms, nulová reklama a 100% white-label v cene.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 13: OTVÁRACIE HODINY & PAUZY -->
                <div class="module-card" data-cat="nastavenia">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/opening_hours.png', 'Modul 13: Otváracie Hodiny a Pauzy')">
                        <div class="img-wrapper">
                            <img src="screenshots/opening_hours.png" alt="Otváracie hodiny EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Nastavenia & Formulár</span>
                                <span class="badge badge-amber">UI Skóre: 78%</span>
                                <span class="badge badge-green">Prehľadný Týždeň</span>
                            </div>
                            <h3 class="module-title">13. Otváracie Hodiny, Obedné Pauzy & Sviatky</h3>
                            <p class="module-desc">Týždenný rozpis prevádzkovej doby od pondelka do nedele s možnosťou nastaviť obedňajšiu prestávku a sviatočné uzatvorenie.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Rýchle prepínače dní:</strong> Jednoduché zapnutie/vypnutie jednotlivých pracovných dní prepínačom.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Statický model:</strong> Zložitejšie striedanie krátky/dlhý týždeň u zamestnancov vyžaduje komplikované manuálne blokácie v kalendári.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Podpora pre zmennú prevádzku (krátky / dlhý týždeň, ranná / poobedná zmena), synchronizácia so slovenským kalendárom štátnych sviatkov a automatické rešpektovanie dovoleniek.</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 14: AUDIT LOG & HISTÓRIA AKCIÍ -->
                <div class="module-card" data-cat="nastavenia">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/activity_log.png', 'Modul 14: História Akcií a Audit Log')">
                        <div class="img-wrapper">
                            <img src="screenshots/activity_log.png" alt="História akcií EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Nastavenia & Formulár</span>
                                <span class="badge badge-green">Veľmi Užitočné</span>
                                <span class="badge badge-amber">UI Skóre: 74%</span>
                            </div>
                            <h3 class="module-title">14. Audit Log & História Systémových Zmien</h3>
                            <p class="module-desc">Chronologický zoznam všetkých vykonaných úprav v systéme: kto, kedy a čo vytvoril, zmenil, zrušil alebo prepísal.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Transparentnosť tímu:</strong> Majiteľ okamžite vidí, ak zamestnanec zmazal termín alebo prepísal cenu služby.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Chýba filter podľa používateľov:</strong> Nemožno si jedným klikom vyfiltrovať len zmeny vykonané konkrétnou osobou.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Kompletný bezpečnostný audit trail s IP adresami, časovými značkami, filtrami podľa personálu a možnosťou okamžitej obnovy omylom zmazaných rezervácií (Rollback do koša).</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 15: IMPORT DÁT A MIGRÁCIA -->
                <div class="module-card" data-cat="nastavenia">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/data_import.png', 'Modul 15: Import Dát z Iných Systémov')">
                        <div class="img-wrapper">
                            <img src="screenshots/data_import.png" alt="Import dát v EasyBooking" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Nastavenia & Formulár</span>
                                <span class="badge badge-green">Migračný Nástroj</span>
                                <span class="badge badge-amber">CSV Iba</span>
                            </div>
                            <h3 class="module-title">15. Import Klientov & Migračný Sprievodca</h3>
                            <p class="module-desc">Nástroj na nahratie kontaktov klientov z CSV súboru a ich rýchle zaradenie do databázy EasyBooking.</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Základný CSV parser:</strong> Pomôže pri prechode z Excelu.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Časté chyby formátovania:</strong> Ak sú telefónne čísla v rôznych tvaroch (+421, 09xx, medzery), import zlyhá bez jasného popisu chyby.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p><strong>1-Klikový AI Importér:</strong> Nahrajte export z EasyBookingu, Booqme, Reservia alebo Google Kalendára – náš AI skript automaticky opraví čísla, premapuje služby a za 60 sekúnd máte salón plne migrovaný!</p>
                        </div>
                    </div>
                </div>

                <!-- MODUL 16: ZÁKLADNÉ NASTAVENIA PREVÁDZKY -->
                <div class="module-card" data-cat="nastavenia">
                    <div class="module-screenshot-col" onclick="openLightbox('screenshots/basic_settings.png', 'Modul 16: Základné Nastavenia Salónu')">
                        <div class="img-wrapper">
                            <img src="screenshots/basic_settings.png" alt="Základné nastavenia prevádzky" loading="lazy">
                        </div>
                        <div class="zoom-overlay-hint"><i class="fa-solid fa-expand"></i> Zväčšiť screenshot</div>
                    </div>
                    <div class="module-content-col">
                        <div class="module-header">
                            <div class="module-badge-row">
                                <span class="badge badge-cat">Nastavenia & Formulár</span>
                                <span class="badge badge-amber">UI Skóre: 76%</span>
                                <span class="badge badge-green">Predstih Rezervácie</span>
                            </div>
                            <h3 class="module-title">16. Firemný Profil, Adresa & Limity Predstihu</h3>
                            <p class="module-desc">Zadanie názvu salónu, IČO, adresy, kontaktných údajov a časových pravidiel (minimálny predstih pre vytvorenie a stornovanie termínu).</p>
                        </div>

                        <div class="deep-dive-box">
                            <h4><i class="fa-solid fa-magnifying-glass"></i> Pozorovania z Testovania:</h4>
                            <ul class="feature-bullets">
                                <li class="strength"><i class="fa-solid fa-check"></i> <strong>Pravidlá predstihu:</strong> Zákazník nemôže urobiť termín 10 minút pred začiatkom ani 6 mesiacov dopredu.</li>
                                <li class="weakness"><i class="fa-solid fa-xmark"></i> <strong>Chýba viacpobočkový model:</strong> Ak má majiteľ salón v Bratislave a v Trnave, musí si platiť dva separátne účty bez jednotného prepínania.</li>
                            </ul>
                        </div>

                        <div class="rezervos-advantage-banner">
                            <h5><i class="fa-solid fa-shield-halved"></i> Prevaha Rezervos:</h5>
                            <p>Plnohodnotný Multi-Location manažment: centrálny prístup k viacerým pobočkám pod jedným heslom, zdieľané cenníky a roaming zamestnancov medzi prevádzkami.</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- FULL 24 SCREENSHOTS COMPREHENSIVE GALLERY -->
        <section id="galeria-24">
            <div class="section-header">
                <div class="section-tag"><i class="fa-solid fa-images"></i> Autentická Fotedokumentácia</div>
                <h2 class="section-title">Kompletná Galéria: Všetkých 24 Snímok Obrazovky</h2>
                <p class="section-desc">Kliknite na ktorúkoľvek položku pre detailný náhľad vo vysokom rozlíšení. Dokumentácia zachytáva všetky zákutia produkčného systému EasyBooking.</p>
            </div>

            <div class="gallery-grid">
                <!-- 1 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/login_page.png', 'Prihlasovacia obrazovka EasyBooking')">
                    <img src="screenshots/login_page.png" alt="Login page" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">1. Prihlasovacia Stránka</div>
                        <div class="gallery-item-cat">Autentifikácia</div>
                    </div>
                </div>

                <!-- 2 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/registration_page.png', 'Registračný formulár EasyBooking')">
                    <img src="screenshots/registration_page.png" alt="Registrácia" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">2. Registrácia Salónu</div>
                        <div class="gallery-item-cat">Onboarding</div>
                    </div>
                </div>

                <!-- 3 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/calendar_week.png', 'Týždenný kalendár v plnom rozlíšení')">
                    <img src="screenshots/calendar_week.png" alt="Týždenný kalendár" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">3. Týždenný Kalendár</div>
                        <div class="gallery-item-cat">Kalendár</div>
                    </div>
                </div>

                <!-- 4 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/calendar_day.png', 'Denný pohľad na kalendár')">
                    <img src="screenshots/calendar_day.png" alt="Denný kalendár" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">4. Denný Kalendár</div>
                        <div class="gallery-item-cat">Kalendár</div>
                    </div>
                </div>

                <!-- 5 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/calendar_month.png', 'Mesačný kalendár obsadenosti')">
                    <img src="screenshots/calendar_month.png" alt="Mesačný kalendár" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">5. Mesačný Kalendár</div>
                        <div class="gallery-item-cat">Kalendár</div>
                    </div>
                </div>

                <!-- 6 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/new_reservation_modal.png', 'Vyskakovacie okno novej rezervácie')">
                    <img src="screenshots/new_reservation_modal.png" alt="Nová rezervácia" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">6. Vytvorenie Rezervácie</div>
                        <div class="gallery-item-cat">Formulár</div>
                    </div>
                </div>

                <!-- 7 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/reservations_list.png', 'Zoznam všetkých rezervácií')">
                    <img src="screenshots/reservations_list.png" alt="Zoznam rezervácií" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">7. Zoznam Rezervácií</div>
                        <div class="gallery-item-cat">Termíny</div>
                    </div>
                </div>

                <!-- 8 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/customer_queries.png', 'Dopyty čakateľov a klientov')">
                    <img src="screenshots/customer_queries.png" alt="Zákaznícke dopyty" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">8. Zákaznícke Dopyty</div>
                        <div class="gallery-item-cat">Čakacia Listina</div>
                    </div>
                </div>

                <!-- 9 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/customers_list.png', 'Databáza zákazníkov CRM')">
                    <img src="screenshots/customers_list.png" alt="Databáza zákazníkov" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">9. Zákazníci (CRM)</div>
                        <div class="gallery-item-cat">Klienti</div>
                    </div>
                </div>

                <!-- 10 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/customer_tags.png', 'Správa farebných štítkov zákazníkov')">
                    <img src="screenshots/customer_tags.png" alt="Štítky zákazníkov" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">10. Štítky Zákazníkov</div>
                        <div class="gallery-item-cat">Segmentácia</div>
                    </div>
                </div>

                <!-- 11 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/services_list.png', 'Zoznam služieb a cien')">
                    <img src="screenshots/services_list.png" alt="Zoznam služieb" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">11. Zoznam Služieb</div>
                        <div class="gallery-item-cat">Cenník</div>
                    </div>
                </div>

                <!-- 12 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/service_add_edit_form.png', 'Formulár pre úpravu a pridanie služby')">
                    <img src="screenshots/service_add_edit_form.png" alt="Úprava služby" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">12. Formulár Služby</div>
                        <div class="gallery-item-cat">Konfigurácia</div>
                    </div>
                </div>

                <!-- 13 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/service_categories.png', 'Kategórie služieb')">
                    <img src="screenshots/service_categories.png" alt="Kategórie služieb" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">13. Kategórie Služieb</div>
                        <div class="gallery-item-cat">Štruktúra</div>
                    </div>
                </div>

                <!-- 14 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/staff_management.png', 'Správa tímu a personálu')">
                    <img src="screenshots/staff_management.png" alt="Zamestnanci" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">14. Zamestnanci & Tím</div>
                        <div class="gallery-item-cat">Personál</div>
                    </div>
                </div>

                <!-- 15 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/vouchers_discounts.png', 'Kupóny a poukážky')">
                    <img src="screenshots/vouchers_discounts.png" alt="Poukážky" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">15. Poukážky & Zľavy</div>
                        <div class="gallery-item-cat">Marketing</div>
                    </div>
                </div>

                <!-- 16 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/reviews_ratings.png', 'Hodnotenia od klientov')">
                    <img src="screenshots/reviews_ratings.png" alt="Recenzie" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">16. Recenzie & Ratingy</div>
                        <div class="gallery-item-cat">Reputácia</div>
                    </div>
                </div>

                <!-- 17 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/statistics.png', 'Prehľad štatistík a tržieb')">
                    <img src="screenshots/statistics.png" alt="Štatistiky" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">17. Štatistiky & Tržby</div>
                        <div class="gallery-item-cat">Financie</div>
                    </div>
                </div>

                <!-- 18 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/credits_subscription.png', 'Predplatné balíky a kredit')">
                    <img src="screenshots/credits_subscription.png" alt="Predplatné balíky" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">18. Balíky Predplatného</div>
                        <div class="gallery-item-cat">Fakturácia</div>
                    </div>
                </div>

                <!-- 19 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/wallet_overview.png', 'Peňaženka a stav kreditu')">
                    <img src="screenshots/wallet_overview.png" alt="Peňaženka" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">19. Prehľad Peňaženky</div>
                        <div class="gallery-item-cat">Kredity</div>
                    </div>
                </div>

                <!-- 20 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/basic_settings.png', 'Základné údaje prevádzky')">
                    <img src="screenshots/basic_settings.png" alt="Základné nastavenia" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">20. Profil Prevádzky</div>
                        <div class="gallery-item-cat">Nastavenia</div>
                    </div>
                </div>

                <!-- 21 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/booking_form_settings.png', 'Konfigurácia online rezervačného formulára')">
                    <img src="screenshots/booking_form_settings.png" alt="Formulár nastavenia" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">21. Nastavenia Formulára</div>
                        <div class="gallery-item-cat">Widget</div>
                    </div>
                </div>

                <!-- 22 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/opening_hours.png', 'Otváracie hodiny prevádzky')">
                    <img src="screenshots/opening_hours.png" alt="Otváracie hodiny" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">22. Otváracie Hodiny</div>
                        <div class="gallery-item-cat">Rozvrh</div>
                    </div>
                </div>

                <!-- 23 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/activity_log.png', 'Audit log zmien a aktivít')">
                    <img src="screenshots/activity_log.png" alt="Audit log" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">23. Audit Log Aktivít</div>
                        <div class="gallery-item-cat">Bezpečnosť</div>
                    </div>
                </div>

                <!-- 24 -->
                <div class="gallery-item" onclick="openLightbox('screenshots/data_import.png', 'Sprievodca importom dát z CSV')">
                    <img src="screenshots/data_import.png" alt="Import dát" loading="lazy">
                    <div class="gallery-item-info">
                        <div class="gallery-item-title">24. Import Dát z CSV</div>
                        <div class="gallery-item-cat">Migrácia</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PRICING DISSECTION -->
        <section id="cennik-pitva">
            <div class="section-header">
                <div class="section-tag"><i class="fa-solid fa-tags"></i> Cenová Pitva</div>
                <h2 class="section-title">Detailný Rozbor Balíkov EasyBooking</h2>
                <p class="section-desc">Oficiálny cenník EasyBooking vyzerá na prvý pohľad lacno, no po prečítaní drobného písma objavíte desiatky poplatkov navyše.</p>
            </div>

            <div class="pricing-table-wrapper">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Tarifa</th>
                            <th>Mesačne (M/R)</th>
                            <th>Kreslá / Tím</th>
                            <th>Kľúčové Obmedzenia</th>
                            <th>Skryté Poplatky</th>
                            <th>Náš Verdikt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="plan-name">
                                <strong style="color: var(--text-muted);">Basic (Základ)</strong>
                            </td>
                            <td class="price-cell" style="color: #34d399;">0,00 €</td>
                            <td>0 miestností (len 1 osoba)</td>
                            <td>Žiadne poukážky, permanentky, štatistiky, platby kartou, QR platby ani vlastné dokumenty.</td>
                            <td>SMS za plnú sumu 0,083 € / ks.</td>
                            <td><span class="badge badge-rose">Pasca na klientov</span></td>
                        </tr>
                        <tr>
                            <td class="plan-name">
                                <strong style="color: #fbbf24;">Regular (Zlatý stred)</strong>
                            </td>
                            <td class="price-cell">9,90 € / 6,90 €</td>
                            <td>IBA 1 miestnosť / 1 zamestnanec</td>
                            <td>Akonáhle máte 2 zamestnancov, balík vám nestačí a musíte upgradovať.</td>
                            <td>0,083 € SMS + 2,5 % + 0,35 € z každej platby.</td>
                            <td><span class="badge badge-amber">Drahé pri 2+ ľuďoch</span></td>
                        </tr>
                        <tr>
                            <td class="plan-name">
                                <strong style="color: #818cf8;">Advanced (Väčšia firma)</strong>
                            </td>
                            <td class="price-cell">19,90 € / 16,90 €</td>
                            <td>2 až 3 miestnosti / zamestnanci</td>
                            <td>Prístup do kalendára zamestnancov, individuálne kontá.</td>
                            <td>0,083 € SMS + 2,5 % + 0,35 € z každej platby + 29,90 € logo.</td>
                            <td><span class="badge badge-amber">Prekombinované</span></td>
                        </tr>
                        <tr>
                            <td class="plan-name">
                                <strong style="color: #f43f5e;">Extra (Pre najväčších)</strong>
                            </td>
                            <td class="price-cell">29,90 € / 26,90 €</td>
                            <td>4 a viac zamestnancov</td>
                            <td>Všetky funkcie zapnuté.</td>
                            <td>Stále si účtujú 2,5 % + 0,35 € províziu zo všetkých online a osobných platieb!</td>
                            <td><span class="badge badge-rose">Predražené</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 24px; padding: 20px; background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); border-radius: 16px;">
                <h4 style="color: #fb7185; font-size: 15px; margin-bottom: 6px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Upozornenie pre majiteľov salónov:
                </h4>
                <p style="font-size: 13.5px; color: var(--text-secondary); line-height: 1.5;">
                    V cenníku EasyBooking sa doslova píše: <em>"Online platby v rezervačnom formulári: 2,5% + 0,35€ za transakciu"</em> a dokonca aj <em>"Osobné platby v prevádzke: 2,5% + 0,35€ za transakciu"</em>. 
                    Ak má salón 100 zákazníkov mesačne, ktorí zaplatia priemerných 35 €, EasyBooking si z ich poctivo zarobených peňazí vezme vyše <strong>122 € každý mesiac</strong> len na províziách!
                </p>
            </div>
        </section>

        <!-- FULL COMPARISON MATRIX -->
        <section id="matica" class="matrix-section">
            <div class="section-header">
                <div class="section-tag"><i class="fa-solid fa-table-cells"></i> Matrica Vlastností</div>
                <h2 class="section-title">Kompletná Funkčná Porovnávacia Matrica</h2>
                <p class="section-desc">Viac ako 40 konkrétnych parametrov porovnaných bod po bode.</p>
            </div>

            <div class="pricing-table-wrapper">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Funkcia / Parameter</th>
                            <th style="width: 25%; color: #fbbf24;">EasyBooking.sk</th>
                            <th style="width: 30%; color: #34d399;">REZERVOS Platform</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- KATEGÓRIA 1 -->
                        <tr><td colspan="3" class="matrix-category-header">1. Kalendár & Rezervačný Systém</td></tr>
                        <tr>
                            <td>Moderné rozhranie s plynulým Drag & Drop</td>
                            <td><span class="check-no"><i class="fa-solid fa-xmark"></i> Nie (staršie zobrazenie)</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (FullCalendar v6)</span></td>
                        </tr>
                        <tr>
                            <td>Automatická vizualizácia Buffer Time (pauza)</td>
                            <td><span class="check-no"><i class="fa-solid fa-xmark"></i> Nie</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (dynamické bloky)</span></td>
                        </tr>
                        <tr>
                            <td>Opakované termíny pre stálych klientov</td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno</span></td>
                        </tr>
                        <tr>
                            <td>Zmenná prevádzka (krátky / dlhý týždeň)</td>
                            <td><span class="check-partial"><i class="fa-solid fa-triangle-exclamation"></i> Obmedzene</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (automatické zmeny)</span></td>
                        </tr>
                        <tr>
                            <td>Synchronizácia s Google & Apple Kalendárom</td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (iCal)</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (obojstranný 2-way sync)</span></td>
                        </tr>

                        <!-- KATEGÓRIA 2 -->
                        <tr><td colspan="3" class="matrix-category-header">2. Platby & Finančná Transparentnosť</td></tr>
                        <tr>
                            <td>Poplatok z online platieb (provízia systému)</td>
                            <td><span class="check-no">2,5 % + 0,35 €</span></td>
                            <td><span class="check-yes">0,00 % (žiadna provízia)</span></td>
                        </tr>
                        <tr>
                            <td>Poplatok za osobné platby na mieste</td>
                            <td><span class="check-no">2,5 % + 0,35 €</span></td>
                            <td><span class="check-yes">0,00 % (váš vlastný terminál)</span></td>
                        </tr>
                        <tr>
                            <td>Predautorizácia a zálohy proti No-Show</td>
                            <td><span class="check-partial">Základné zálohy</span></td>
                            <td><span class="check-yes">Inteligentný Stripe No-Show Guard</span></td>
                        </tr>
                        <tr>
                            <td>Predaj darčekových poukazov bez provízie</td>
                            <td><span class="check-no"><i class="fa-solid fa-xmark"></i> Nie (berú províziu)</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (100% zisk salónu)</span></td>
                        </tr>

                        <!-- KATEGÓRIA 3 -->
                        <tr><td colspan="3" class="matrix-category-header">3. Notifikácie & Komunikácia</td></tr>
                        <tr>
                            <td>Cena za 1 SMS notifikáciu</td>
                            <td><span class="check-no">0,083 € / SMS</span></td>
                            <td><span class="check-yes">0,045 € / SMS (alebo v balíku)</span></td>
                        </tr>
                        <tr>
                            <td>Automatické pripomienky pred termínom</td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (časovo nastaviteľné)</span></td>
                        </tr>
                        <tr>
                            <td>Notifikácie do Apple Wallet & Google Pay</td>
                            <td><span class="check-no"><i class="fa-solid fa-xmark"></i> Nie</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (Push notifikácie na displej)</span></td>
                        </tr>
                        <tr>
                            <td>Automatický Last-Minute & Waitlist Bot</td>
                            <td><span class="check-no"><i class="fa-solid fa-xmark"></i> Iba manuálne dopyty</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Plne automatizovaný bot</span></td>
                        </tr>

                        <!-- KATEGÓRIA 4 -->
                        <tr><td colspan="3" class="matrix-category-header">4. Branding & White-Label</td></tr>
                        <tr>
                            <td>Odstránenie brandingu systému (White-label)</td>
                            <td><span class="check-no">Poplatok 29,90 €</span></td>
                            <td><span class="check-yes">Zadarmo v základe (0 €)</span></td>
                        </tr>
                        <tr>
                            <td>Vlastný rezervačný link pre Instagram Bio</td>
                            <td><span class="check-partial">iFrame widget</span></td>
                            <td><span class="check-yes">Bleskový optimalizovaný portál</span></td>
                        </tr>
                        <tr>
                            <td>Google Review Booster (SEO hodnotenia)</td>
                            <td><span class="check-no"><i class="fa-solid fa-xmark"></i> Nie (len interné)</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Priamy presmerovač na Google Maps</span></td>
                        </tr>

                        <!-- KATEGÓRIA 5 -->
                        <tr><td colspan="3" class="matrix-category-header">5. Tím & Licenčné Podmienky</td></tr>
                        <tr>
                            <td>Počet zamestnancov v základnom platenom pláne</td>
                            <td><span class="check-no">Iba 1 zamestnanec</span></td>
                            <td><span class="check-yes">Flexibilný tím bez penalizácie</span></td>
                        </tr>
                        <tr>
                            <td>Podpora prenájmu kresiel (viacero IČO v salóne)</td>
                            <td><span class="check-no"><i class="fa-solid fa-xmark"></i> Nie</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Dedikovaný Chair-Rental modul</span></td>
                        </tr>
                        <tr>
                            <td>Bezpečnostný Audit Log zmien</td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno</span></td>
                            <td><span class="check-yes"><i class="fa-solid fa-check"></i> Áno (s obnovou dát z koša)</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- STRATEGY SECTION -->
        <section id="strategia">
            <div class="section-header">
                <div class="section-tag"><i class="fa-solid fa-trophy"></i> Strategický Akčný Plán</div>
                <h2 class="section-title">Ako Získať Klientov EasyBookingu pre REZERVOS</h2>
                <p class="section-desc">Jasná obchodná a produktová stratégia postavená na odhalených slabinách konkurencie.</p>
            </div>

            <div class="metrics-grid">
                <div class="metric-card green">
                    <div class="icon-box"><i class="fa-solid fa-file-import"></i></div>
                    <div class="metric-val">1-Kliková Migrácia</div>
                    <div class="metric-label">Z EasyBookingu do Rezervos</div>
                    <div class="metric-sub">Klient nahrá svoj CSV export z EasyBookingu a za 60 sekúnd má prenesených všetkých zákazníkov, cenník aj zamestnancov bez straty dát.</div>
                </div>

                <div class="metric-card amber">
                    <div class="icon-box"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div class="metric-val">Garancia 0% Provízie</div>
                    <div class="metric-label">Ušetrené stovky eur mesačne</div>
                    <div class="metric-sub">Marketingová kampaň zameraná priamo na 2,5 % + 0,35 € poplatky EasyBookingu: 'Prestaňte platiť provízie z vlastnej práce'.</div>
                </div>

                <div class="metric-card blue">
                    <div class="icon-box"><i class="fa-solid fa-mobile-screen-button"></i></div>
                    <div class="metric-val">Apple & Google Wallet</div>
                    <div class="metric-label">WOW efekt pre klientov</div>
                    <div class="metric-sub">Funkcia, ktorú EasyBooking nemá a zrejme tak skoro mať nebude – digitálne vernostné kartičky priamo v smartfóne zákazníka.</div>
                </div>

                <div class="metric-card rose">
                    <div class="icon-box"><i class="fa-solid fa-bell"></i></div>
                    <div class="metric-val">SMS za polovicu</div>
                    <div class="metric-label">0,045 € vs 0,083 €</div>
                    <div class="metric-sub">Priamy argument pre väčšie salóny s tisíckami termínov – len na SMS správach ušetria dosť na pokrytie celej licencie Rezervos.</div>
                </div>
            </div>
        </section>

    </div>

    <!-- LIGHTBOX MODAL -->
    <div id="lightbox" class="lightbox-modal" onclick="closeLightbox(event)">
        <div class="lightbox-content-box" onclick="event.stopPropagation()">
            <div class="lightbox-top-bar">
                <div class="lightbox-title">
                    <i class="fa-solid fa-image" style="color: var(--primary-light);"></i>
                    <span id="lightbox-caption">Náhľad obrazovky</span>
                </div>
                <button class="lightbox-close-btn" onclick="closeLightbox(event)">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="lightbox-img-container">
                <img id="lightbox-img" src="" alt="Screenshot" loading="lazy">
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="page-container" style="padding-top: 0; padding-bottom: 0;">
            <p><strong>REZERVOS Intelligence Unit</strong> &bull; Hĺbkový konkurenčný audit EasyBooking.sk &bull; Verzia 2.4 (2026)</p>
            <p style="margin-top: 8px; font-size: 12px;">Všetky použité ochranné známky patria ich príslušným vlastníkom. Analýza slúži na interné porovnávacie a technologické účely.</p>
        </div>
    </footer>

    <!-- INTERACTIVE SCRIPTS -->
    <script>
        // Switch Image in Module Cards
        function switchImg(imgId, src, btn) {
            const img = document.getElementById(imgId);
            if (img) {
                img.src = src;
            }
            if (btn && btn.parentElement) {
                const btns = btn.parentElement.querySelectorAll('.thumb-btn');
                btns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
        }

        // Lightbox Functions
        function openLightbox(src, caption) {
            const lb = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            const cap = document.getElementById('lightbox-caption');
            img.src = src;
            cap.innerText = caption;
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(e) {
            const lb = document.getElementById('lightbox');
            lb.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });

        // Module Filtering
        function filterModules(category) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const cards = document.querySelectorAll('.module-card');
            cards.forEach(card => {
                if (category === 'all' || card.getAttribute('data-cat') === category) {
                    card.style.display = 'grid';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // TCO Calculator Logic
        const inputStaff = document.getElementById('input-staff');
        const inputBookings = document.getElementById('input-bookings');
        const inputTicket = document.getElementById('input-ticket');
        const inputCardShare = document.getElementById('input-card-share');
        const inputSms = document.getElementById('input-sms');

        function updateCalculator() {
            const staff = parseInt(inputStaff.value);
            const bookings = parseInt(inputBookings.value);
            const ticket = parseInt(inputTicket.value);
            const cardShare = parseInt(inputCardShare.value) / 100;
            const smsCount = parseInt(inputSms.value);

            // Update Labels
            document.getElementById('val-staff').innerText = staff + (staff === 1 ? ' pracovník' : (staff < 5 ? ' pracovníci' : ' pracovníkov'));
            document.getElementById('val-bookings').innerText = bookings + ' termínov';
            document.getElementById('val-ticket').innerText = ticket + ' €';
            document.getElementById('val-card-share').innerText = Math.round(cardShare * 100) + ' %';
            document.getElementById('val-sms').innerText = smsCount + ' SMS';

            // EasyBooking Plan Calculation (Monthly rate):
            // 1 staff = Regular (9.90 €)
            // 2-3 staff = Advanced (19.90 €)
            // 4+ staff = Extra (29.90 €)
            let ebPlan = 9.90;
            if (staff >= 4) {
                ebPlan = 29.90;
            } else if (staff >= 2) {
                ebPlan = 19.90;
            }

            // Rezervos Fair License Estimate:
            let rezPlan = 15.00;
            if (staff >= 4) {
                rezPlan = 29.00;
            } else if (staff >= 2) {
                rezPlan = 19.00;
            }

            // SMS costs
            const ebSmsCost = smsCount * 0.083;
            const rezSmsCost = smsCount * 0.045;

            // Card fee costs:
            // Online/card volume in EUR = bookings * cardShare * ticket
            // EasyBooking transaction fee = (cardBookings * 0.35) + (cardVolume * 0.025)
            const cardBookings = bookings * cardShare;
            const cardVolume = cardBookings * ticket;
            const ebFeeCost = (cardBookings * 0.35) + (cardVolume * 0.025);
            const rezFeeCost = 0.00; // Rezervos charges 0% extra fee!

            // Totals
            const ebTotal = ebPlan + ebSmsCost + ebFeeCost;
            const rezTotal = rezPlan + rezSmsCost + rezFeeCost;

            const monthlyDiff = ebTotal - rezTotal;
            const annualSavings = Math.max(0, Math.round(monthlyDiff * 12));

            // Render Output
            document.getElementById('eb-plan-cost').innerText = ebPlan.toFixed(2) + ' €';
            document.getElementById('rez-plan-cost').innerText = rezPlan.toFixed(2) + ' €';

            document.getElementById('eb-sms-cost').innerText = ebSmsCost.toFixed(2) + ' €';
            document.getElementById('rez-sms-cost').innerText = rezSmsCost.toFixed(2) + ' €';

            document.getElementById('eb-fee-cost').innerText = ebFeeCost.toFixed(2) + ' €';
            document.getElementById('rez-fee-cost').innerText = '0,00 €';

            document.getElementById('eb-total-month').innerText = ebTotal.toFixed(2) + ' €';
            document.getElementById('rez-total-month').innerText = rezTotal.toFixed(2) + ' €';

            document.getElementById('annual-savings').innerText = annualSavings.toLocaleString('sk-SK') + ' € / rok';
        }

        // Event Listeners
        [inputStaff, inputBookings, inputTicket, inputCardShare, inputSms].forEach(input => {
            input.addEventListener('input', updateCalculator);
        });

        // Initial Run
        updateCalculator();
    </script>
</body>
</html>
'''

with open(output_path, 'w', encoding='utf-8') as f:
    f.write(html_content)

print(f"Updated portal generated successfully: {output_path}")
print(f"File size: {len(html_content)} characters")
