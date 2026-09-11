<?php
session_start();
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$pageTitle   = 'Tabuľa úloh - ' . BRAND_NAME;
$currentPage = 'tabula';
$myName = $_SESSION['full_name'] ?? ($_SESSION['employee_name'] ?? '');
require_once 'includes/dashboard-head.php';
?>
<style>
/* ── Toolbar ── */
.tabula-toolbar {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; margin-bottom: 18px;
}
.tabula-search-wrap {
    position: relative; flex: 0 0 220px;
}
.tabula-search-wrap .material-symbols-outlined {
    position: absolute; left: 9px; top: 50%; transform: translateY(-50%);
    font-size: 18px; color: var(--text-secondary); pointer-events: none;
}
.tabula-search {
    width: 100%; padding: 7px 10px 7px 34px;
    border: 1px solid var(--border-color); border-radius: 6px;
    background: var(--card-bg); color: var(--text-primary);
    font-size: 13px; outline: none; transition: border-color 0.15s;
    box-sizing: border-box;
}
.tabula-search:focus { border-color: var(--primary-color); }
.tabula-search::placeholder { color: var(--text-secondary); }
.tabula-filters { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; flex: 1; }
.tf-btn {
    background: var(--card-bg); border: 1px solid var(--border-color);
    color: var(--text-secondary); border-radius: 6px; padding: 6px 14px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: all 0.18s; white-space: nowrap;
}
.tf-btn:hover { background: var(--bg-color); color: var(--text-primary); }
.tf-btn.active { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
.tf-btn.prio-urgentna.active   { background: #ef4444; border-color: #ef4444; color:#fff; }
.tf-btn.prio-vysoka.active     { background: #f59e0b; border-color: #f59e0b; color:#fff; }
.tf-btn.prio-normalna.active   { background: #6366f1; border-color: #6366f1; color:#fff; }
.tf-btn.status-nove.active     { background: rgba(148,163,184,0.2); border-color: #94a3b8; color: #94a3b8; }
.tf-btn.status-prebieha.active { background: rgba(245,158,11,0.18); border-color: #f59e0b; color: #f59e0b; }
.tf-btn.status-vybavene.active { background: rgba(16,185,129,0.18); border-color: #10b981; color: #10b981; }
.tf-sep { width: 1px; height: 26px; background: var(--border-color); flex-shrink: 0; }

/* ── Kanban board ── */
#tabula-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px; align-items: start;
}
@media (max-width: 900px) { #tabula-grid { grid-template-columns: 1fr; } }

.kanban-col {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    min-height: 200px;
    display: flex; flex-direction: column;
}
.kanban-col-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px 10px;
    border-bottom: 1px solid var(--border-color);
    font-weight: 700; font-size: 13px; color: var(--text-primary);
}
.kanban-col-header .col-count {
    background: var(--border-color); color: var(--text-secondary);
    font-size: 11px; font-weight: 700; min-width: 20px; height: 20px;
    border-radius: 6px; display: inline-flex; align-items: center; justify-content: center;
    padding: 0 6px;
}
.kanban-col-header .col-dot {
    width: 8px; height: 8px; border-radius: 50%; margin-right: 8px; flex-shrink: 0;
}
.kanban-col-body {
    padding: 10px 10px; display: flex; flex-direction: column; gap: 8px;
    flex: 1;
}
.kanban-col.drop-target { outline: 2px dashed var(--primary-color); outline-offset: -2px; }

/* ── Card ── */
.task-card {
    background: var(--card-bg); border: 1px solid var(--border-color);
    border-radius: 8px; padding: 0 !important;
    position: relative; overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: grab;
}
.task-card:active { cursor: grabbing; }
.task-card:hover  {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 50px rgba(0,0,0,0.6), 0 0 20px var(--accent-glow, rgba(99,102,241,0.3));
    z-index: 50;
}
.task-card.dragging { opacity: 0.4; }
.task-card.drag-over { box-shadow: 0 0 0 2px var(--primary-color); }
.task-card-inner {
    padding: 1.25rem;
    display: flex; flex-direction: column; gap: 0.75rem;
    border-left: 4px solid transparent;
}

.task-card-inner.prio-border-urgentna { border-left-color: #ef4444; }
.task-card-inner.prio-border-vysoka   { border-left-color: #f59e0b; }
.task-card-inner.prio-border-normalna { border-left-color: #6366f1; }

/* Badges */
.card-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 9px; }
.badge {
    display: inline-flex; align-items: center;
    font-size: 10px; font-weight: 700; letter-spacing: 0.4px;
    padding: 3px 9px; border-radius: 4px; text-transform: uppercase;
}
.badge-urgentna { background: #ef4444; color: #fff; }
.badge-vysoka   { background: #f59e0b; color: #fff; }
.badge-normalna { background: #6366f1; color: #fff; }
.badge-nove     { background: rgba(148,163,184,0.2); color: #94a3b8; }
.badge-prebieha { background: rgba(245,158,11,0.18); color: #f59e0b; }
.badge-vybavene { background: #10b981; color: #fff; }

/* Card actions — vždy viditeľné */
.card-actions {
    position: absolute; top: 10px; right: 10px;
    display: flex; gap: 4px;
}
.card-act-btn {
    width: 28px; height: 28px; border-radius: 5px;
    background: transparent; border: none;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.15s;
}
.card-act-btn:hover { background: var(--sidebar-hover); }
.card-act-btn .material-symbols-outlined { font-size: 16px; color: var(--text-secondary); }
.card-act-btn.pinned .material-symbols-outlined { color: var(--primary-color); }
.card-act-btn.del:hover .material-symbols-outlined { color: #ef4444; }

.card-title {
    font-size: 15px; font-weight: 700; color: var(--text-primary);
    margin: 0 0 7px; line-height: 1.35; padding-right: 90px; word-break: break-word;
}
.card-desc {
    font-size: 12.5px; color: var(--text-secondary); margin: 0 0 10px;
    line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3;
    -webkit-box-orient: vertical; overflow: hidden;
}

/* Meta row */
.card-meta { display: flex; flex-direction: column; gap: 5px; }
.card-meta-row {
    display: flex; align-items: center; gap: 5px;
    font-size: 12px; color: var(--text-secondary);
}
.card-meta-row .material-symbols-outlined { font-size: 14px; flex-shrink: 0; }

.card-divider { height: 1px; background: var(--border-color); margin: 10px 0; }

.card-note {
    font-size: 12px; color: var(--primary-color); font-weight: 600;
    cursor: pointer;
}
.card-note:hover { text-decoration: underline; }

/* Pin indicator dot */
.card-pin-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--primary-color); display: inline-block;
    margin-right: 4px; vertical-align: middle; display: none;
}
.task-card.is-pinned .card-pin-dot { display: inline-block; }

/* Empty */
.tabula-empty {
    grid-column: 1 / -1; text-align: center; padding: 60px 20px;
    color: var(--text-secondary);
}
.tabula-empty .material-symbols-outlined { font-size: 48px; margin-bottom: 10px; display: block; }

/* Modal */
.tabula-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(3px);
    z-index: 9000; justify-content: center; align-items: center;
}
.tabula-modal-overlay.open { display: flex; }
.tabula-modal {
    background: var(--card-bg); border: 1px solid var(--border-color);
    border-radius: 10px; width: 100%; max-width: 520px; margin: 16px;
    box-shadow: 0 24px 48px rgba(0,0,0,0.3); overflow: hidden;
}
.tabula-modal-header {
    padding: 18px 20px 14px; border-bottom: 1px solid var(--border-color);
    display: flex; align-items: center; justify-content: space-between;
}
.tabula-modal-header h3 { margin: 0; font-size: 17px; font-weight: 700; }
.tabula-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; max-height: 70vh; }
.tabula-modal-footer {
    padding: 14px 20px; border-top: 1px solid var(--border-color);
    display: flex; justify-content: flex-end; gap: 10px;
}
.fm-label { font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; display: block; }
.fm-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.fm-select-group { display: flex; gap: 6px; flex-wrap: wrap; }
.fm-sel-btn {
    flex: 1; min-width: 76px; padding: 7px 8px; border-radius: 6px;
    border: 1.5px solid var(--border-color); background: var(--bg-color);
    color: var(--text-secondary); font-size: 12.5px; font-weight: 600;
    cursor: pointer; text-align: center; transition: all 0.15s;
}
.fm-sel-btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
.fm-sel-btn.p-urgentna.active { background: rgba(239,68,68,0.12); color: #ef4444; border-color: #ef4444; }
.fm-sel-btn.p-vysoka.active   { background: rgba(245,158,11,0.12); color: #f59e0b; border-color: #f59e0b; }
.fm-sel-btn.p-normalna.active { background: rgba(99,102,241,0.12); color: #6366f1; border-color: #6366f1; }
.fm-sel-btn.s-nove.active     { background: rgba(148,163,184,0.12); color: #94a3b8; border-color: #94a3b8; }
.fm-sel-btn.s-prebieha.active { background: rgba(245,158,11,0.12); color: #f59e0b; border-color: #f59e0b; }
.fm-sel-btn.s-vybavene.active { background: rgba(16,185,129,0.12); color: #10b981; border-color: #10b981; }

/* Farebný štítok karty */
.color-swatch-group { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.color-swatch {
    width: 24px; height: 24px; border-radius: 50%; cursor: pointer;
    border: 2px solid transparent; box-sizing: border-box; transition: all 0.15s;
    flex-shrink: 0; padding: 0;
}
.color-swatch:hover { transform: scale(1.15); }
.color-swatch.active { border-color: var(--text-primary); box-shadow: 0 0 0 2px var(--card-bg), 0 0 0 4px var(--swatch-color, transparent); }
.color-swatch.none-swatch {
    background: var(--bg-color) !important; border: 1.5px dashed var(--border-color);
    display: flex; align-items: center; justify-content: center; color: var(--text-secondary);
}
.color-swatch.none-swatch .material-symbols-outlined { font-size: 14px; }
.card-color-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    display: inline-block; margin-right: 6px;
}
.card-color-strip { height: 4px; width: 100%; flex-shrink: 0; }

/* ── Full-page New Form ── */
.tabula-new-page { display: none; }
.tabula-new-page.open { display: block; padding: 20px 24px 24px; }

/* ── Board wrapper padding ── */
#tabula-board { padding: 16px 24px 24px; }
.tnf-header {
    display: flex; align-items: center; justify-content: space-between;
    padding-bottom: 18px; border-bottom: 1px solid var(--border-color);
    margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
}
.tnf-header h3 { margin: 0; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.tnf-header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.tnf-tpl-label { font-size: 12px; color: var(--text-secondary); }
.tnf-tpl-btn {
    background: var(--bg-color); border: 1px solid var(--border-color);
    color: var(--text-secondary); border-radius: 6px; padding: 5px 12px;
    font-size: 12.5px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px; transition: all 0.15s;
}
.tnf-tpl-btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
.tnf-body {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px;
    align-items: stretch;
}
@media (max-width: 700px) { .tnf-body { grid-template-columns: 1fr; } }
.tnf-body .full-col { grid-column: 1 / -1; }
.tnf-body > div { display: flex; flex-direction: column; }
.tnf-body > div .fm-select-group { flex: 1; align-items: stretch; }
.tnf-body > div .fm-select-group .fm-sel-btn { flex: 1; }
.tnf-body > div .form-control { flex: 1; }

/* Ikony v date/time inputoch viditeľné v tmavom móde */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(0.8);
    cursor: pointer;
    opacity: 0.7;
}
input[type="date"]::-webkit-calendar-picker-indicator:hover,
input[type="time"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}
.tnf-upload-zone {
    border: 2px dashed var(--border-color); border-radius: 8px;
    padding: 28px 16px; text-align: center; cursor: pointer;
    transition: border-color 0.15s, background 0.15s; background: var(--bg-color);
}
.tnf-upload-zone:hover { border-color: var(--primary-color); }
.tnf-upload-zone.recording { border-color: #ef4444; background: rgba(239,68,68,0.05); }
.tnf-zone-icon { font-size: 32px; color: var(--text-secondary); margin-bottom: 6px; }
.tnf-zone-icon .material-symbols-outlined { font-size: 32px; }
.tnf-zone-label { font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
.tnf-zone-sub { font-size: 12px; color: var(--text-secondary); }
.tnf-footer {
    display: flex; align-items: center; justify-content: flex-end;
    gap: 10px; padding-top: 16px; border-top: 1px solid var(--border-color); flex-wrap: wrap;
}
.tnf-option-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--bg-color); border: 1px solid var(--border-color);
    border-radius: 6px; padding: 8px 16px; font-size: 13px; font-weight: 600;
    color: var(--text-secondary); cursor: pointer; transition: all 0.15s;
}
.tnf-option-btn.active { background: rgba(99,102,241,0.1); border-color: var(--primary-color); color: var(--primary-color); }
.tnf-option-btn .material-symbols-outlined { font-size: 17px; }

/* Inline delete confirm */
.tabula-confirm-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.55); z-index: 9100;
    justify-content: center; align-items: center;
}
.tabula-confirm-overlay.open { display: flex; }
.tabula-confirm-box {
    background: var(--card-bg); border: 1px solid var(--border-color);
    border-radius: 10px; padding: 24px 24px 20px; max-width: 360px; width: 90%;
    box-shadow: 0 16px 40px rgba(0,0,0,0.25); text-align: center;
}
.tabula-confirm-box h4 { margin: 0 0 8px; font-size: 16px; font-weight: 700; }
.tabula-confirm-box p  { margin: 0 0 20px; font-size: 13px; color: var(--text-secondary); line-height: 1.5; }
.tabula-confirm-actions { display: flex; gap: 10px; justify-content: center; }
</style>

<div class="admin-sidebar">
    <?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
    <?php $headerTitle = 'Tabuľa úloh'; $headerIcon = 'view_kanban'; require_once 'includes/dashboard-topbar.php'; ?>
    <div class="admin-content">
        <div class="section">
            <div class="vueto-card">
                <div class="vueto-card-header">
                    <div>
                        <h2 class="section-header">
                            <span class="material-symbols-outlined">view_kanban</span>
                            Tabuľa úloh
                        </h2>
                        <p class="section-desc">Sledujte úlohy, poznámky a projekty vašej prevádzky.</p>
                    </div>
                    <button class="btn-primary" onclick="showNewForm()">
                        <span class="material-symbols-outlined">add</span>
                        Nový odkaz
                    </button>
                </div>

                <!-- Full-page New Form -->
                <div class="tabula-new-page" id="tabula-new-page">
                    <div class="tnf-header">
                        <h3>
                            <span class="material-symbols-outlined" style="color:var(--primary-color);">edit_note</span>
                            Nový odkaz alebo správa
                        </h3>
                        <div class="tnf-header-right">
                            <span class="tnf-tpl-label">Rýchle vzory:</span>
                            <button type="button" class="tnf-tpl-btn" onclick="setNFTemplate('Hovor','Zavolať späť na číslo: ','normalna')">
                                <span class="material-symbols-outlined" style="font-size:14px;">call</span>Hovor
                            </button>
                            <button type="button" class="tnf-tpl-btn" onclick="setNFTemplate('Stretnutie','Dohodnuté stretnutie na tému: ','normalna')">
                                <span class="material-symbols-outlined" style="font-size:14px;">calendar_month</span>Stretnutie
                            </button>
                            <button type="button" class="tnf-tpl-btn" onclick="setNFTemplate('Dôležité','','urgentna')">
                                <span class="material-symbols-outlined" style="font-size:14px;">priority_high</span>Urgent
                            </button>
                            <button type="button" class="btn-secondary" onclick="hideNewForm()" style="display:inline-flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">arrow_back</span>
                                Späť na tabuľu
                            </button>
                        </div>
                    </div>

                    <div class="tnf-body">
                        <div>
                            <span class="fm-label">Nadpis správy</span>
                            <input type="text" id="nf-title" class="form-control" placeholder="Napr. Dôležité: zavolajte Novákovi..." maxlength="255">
                        </div>
                        <div>
                            <span class="fm-label">Priorita</span>
                            <div class="fm-select-group">
                                <button class="fm-sel-btn p-normalna active" data-nfprio="normalna" onclick="selectNFPrio(this)">Normálna</button>
                                <button class="fm-sel-btn p-vysoka"          data-nfprio="vysoka"   onclick="selectNFPrio(this)">Vysoká</button>
                                <button class="fm-sel-btn p-urgentna"        data-nfprio="urgentna" onclick="selectNFPrio(this)">Urgentná</button>
                            </div>
                        </div>
                        <div>
                            <span class="fm-label">Stav</span>
                            <div class="fm-select-group">
                                <button class="fm-sel-btn s-nove active" data-nfstatus="nove"     onclick="selectNFStatus(this)">Nové</button>
                                <button class="fm-sel-btn s-prebieha"   data-nfstatus="prebieha" onclick="selectNFStatus(this)">Prebieha</button>
                                <button class="fm-sel-btn s-vybavene"   data-nfstatus="vybavene" onclick="selectNFStatus(this)">Vybavené</button>
                            </div>
                        </div>
                        <div>
                            <span class="fm-label">Termín</span>
                            <input type="date" id="nf-due" class="form-control">
                        </div>
                        <div>
                            <span class="fm-label">Farebný štítok</span>
                            <input type="hidden" id="nf-color" value="">
                            <div class="color-swatch-group" id="nf-color-group"></div>
                        </div>
                        <div class="full-col">
                            <span class="fm-label">Obsah alebo popis</span>
                            <textarea id="nf-desc" class="form-control" rows="4" placeholder="Napíšte správu pre kolegu alebo odkaz..." style="resize:vertical;"></textarea>
                        </div>
                        <div>
                            <div class="tnf-upload-zone" id="voice-zone" onclick="toggleRecording()">
                                <div class="tnf-zone-icon"><span class="material-symbols-outlined" id="voice-icon">mic</span></div>
                                <div class="tnf-zone-label" id="voice-label">Nahrať hlasovú správu</div>
                                <div class="tnf-zone-sub" id="voice-sub">Kliknutím spustíte nahrávanie</div>
                            </div>
                        </div>
                        <div>
                            <div class="tnf-upload-zone" onclick="document.getElementById('nf-files').click()">
                                <input type="file" id="nf-files" style="display:none;" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.zip,.rar" onchange="updateNFFileList(this)">
                                <div id="nf-file-placeholder">
                                    <div class="tnf-zone-icon"><span class="material-symbols-outlined">upload_file</span></div>
                                    <div class="tnf-zone-label">Priložiť súbor / obrázok</div>
                                    <div class="tnf-zone-sub">(PDF, Word, Obrázok, Archív...)</div>
                                </div>
                                <div id="nf-file-info" style="display:none;flex-direction:column;gap:4px;align-items:center;"></div>
                            </div>
                        </div>
                        <div>
                            <span class="fm-label">Priradené (meno)</span>
                            <input type="text" id="nf-assigned" class="form-control" placeholder="Kto to rieši..." maxlength="100">
                        </div>
                        <div>
                            <span class="fm-label">Sledujú (mená, čiarkou)</span>
                            <input type="text" id="nf-watchers" class="form-control" placeholder="Napr. Juraj, Martina..." maxlength="255">
                        </div>
                        <div class="full-col">
                            <span class="fm-label">Poznámka / Štítok</span>
                            <input type="text" id="nf-note" class="form-control" placeholder="Napr. Vybavené / Beriem na vedomie" maxlength="255">
                        </div>
                    </div>

                    <div class="tnf-footer">
                        <label id="nf-cal-toggle" class="tnf-option-btn" style="cursor:pointer;user-select:none;">
                            <input type="checkbox" id="nf-cal-cb" style="display:none;" onchange="document.getElementById('nf-cal-fields').style.display=this.checked?'flex':'none'">
                            <span class="material-symbols-outlined">calendar_add_on</span>
                            Zobraziť aj v kalendári
                        </label>
                        <button type="button" class="tnf-option-btn" id="nf-pin-btn" onclick="toggleNFPin()">
                            <span class="material-symbols-outlined">keep</span>
                            Pripnúť na vrch
                        </button>
                        <button type="button" class="btn-secondary" onclick="hideNewForm()">Zrušiť</button>
                        <button type="button" class="btn-primary" onclick="saveNewForm()">
                            <span class="material-symbols-outlined">add</span>
                            Pridať na tabuľu
                        </button>
                    </div>
                    <div id="nf-cal-fields" style="display:none;gap:1rem;flex-wrap:wrap;margin-top:1rem;padding:1rem 1.25rem;background:rgba(99,102,241,0.05);border-radius:8px;border:1px solid rgba(99,102,241,0.2);">
                        <div style="flex:1;min-width:150px;">
                            <span class="fm-label">Dátum v kalendári</span>
                            <input type="date" id="nf-event-date" class="form-control">
                        </div>
                        <div style="flex:1;min-width:120px;">
                            <span class="fm-label">Čas (voliteľné)</span>
                            <input type="time" id="nf-event-time" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Board -->
                <div id="tabula-board">
                <div class="tabula-toolbar">
                    <div class="tabula-search-wrap">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" class="tabula-search" id="tabula-search" placeholder="Hľadaj v úlohách..." oninput="renderGrid()">
                    </div>
                    <div class="tabula-filters" id="tabula-filters">
                        <button class="tf-btn active" data-filter="vsetko">Všetko</button>
                        <?php if ($myName): ?>
                        <button class="tf-btn" data-filter="moje">Moje</button>
                        <?php endif; ?>
                        <button class="tf-btn status-nove"     data-filter="status:nove">Nové</button>
                        <button class="tf-btn status-prebieha" data-filter="status:prebieha">Prebieha</button>
                        <button class="tf-btn status-vybavene" data-filter="status:vybavene">Vybavené</button>
                        <div class="tf-sep"></div>
                        <button class="tf-btn prio-urgentna" data-filter="prio:urgentna">Urgentná</button>
                        <button class="tf-btn prio-vysoka"   data-filter="prio:vysoka">Vysoká</button>
                        <button class="tf-btn prio-normalna" data-filter="prio:normalna">Normálna</button>
                    </div>
                    <button class="tf-btn" id="view-toggle-btn" onclick="toggleView()" title="Prepnúť pohľad">
                        <span class="material-symbols-outlined" id="view-toggle-icon" style="font-size:18px;vertical-align:middle;">view_kanban</span>
                    </button>
                </div>

                <!-- Grid -->
                <div id="tabula-grid">
                    <div class="tabula-empty">
                        <span class="material-symbols-outlined">sync</span>
                        <p>Načítavam...</p>
                    </div>
                </div>
                </div><!-- /#tabula-board -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Pridať / Upraviť úlohu -->
<div class="tabula-modal-overlay" id="task-modal" onclick="if(event.target===this)closeTaskModal()">
    <div class="tabula-modal">
        <div class="tabula-modal-header">
            <h3 id="modal-title">Nová úloha</h3>
            <button class="card-act-btn" onclick="closeTaskModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="tabula-modal-body">
            <input type="hidden" id="fm-id" value="">
            <div>
                <span class="fm-label">Názov úlohy</span>
                <input type="text" id="fm-title" class="form-control" placeholder="Napr. Zavolať dodávateľovi..." maxlength="255">
            </div>
            <div>
                <span class="fm-label">Popis (voliteľný)</span>
                <textarea id="fm-desc" class="form-control" rows="3" placeholder="Detailnejší popis úlohy..." style="resize:vertical;"></textarea>
            </div>
            <div class="fm-row-2">
                <div>
                    <span class="fm-label">Priorita</span>
                    <div class="fm-select-group">
                        <button class="fm-sel-btn p-normalna active" data-prio="normalna" onclick="selectPrio(this)">Normálna</button>
                        <button class="fm-sel-btn p-vysoka"          data-prio="vysoka"   onclick="selectPrio(this)">Vysoká</button>
                        <button class="fm-sel-btn p-urgentna"        data-prio="urgentna" onclick="selectPrio(this)">Urgentná</button>
                    </div>
                </div>
                <div>
                    <span class="fm-label">Stav</span>
                    <div class="fm-select-group">
                        <button class="fm-sel-btn s-nove active" data-status="nove"     onclick="selectStatus(this)">Nové</button>
                        <button class="fm-sel-btn s-prebieha"    data-status="prebieha" onclick="selectStatus(this)">Prebieha</button>
                        <button class="fm-sel-btn s-vybavene"    data-status="vybavene" onclick="selectStatus(this)">Vybavené</button>
                    </div>
                </div>
            </div>
            <div>
                <span class="fm-label">Farebný štítok</span>
                <input type="hidden" id="fm-color" value="">
                <div class="color-swatch-group" id="fm-color-group"></div>
            </div>
            <div class="fm-row-2">
                <div>
                    <span class="fm-label">Priradené (meno)</span>
                    <input type="text" id="fm-assigned" class="form-control" placeholder="Kto to rieši..." maxlength="100">
                </div>
                <div>
                    <span class="fm-label">Termín</span>
                    <input type="date" id="fm-due" class="form-control">
                </div>
            </div>
            <div>
                <span class="fm-label">Sledujú (mená, čiarkou)</span>
                <input type="text" id="fm-watchers" class="form-control" placeholder="Napr. Juraj, Martina..." maxlength="255">
            </div>
            <div>
                <span class="fm-label">Poznámka / Štítok</span>
                <input type="text" id="fm-note" class="form-control" placeholder="Napr. Vybavené / Beriem na vedomie" maxlength="255">
            </div>
        </div>
        <div class="tabula-modal-footer">
            <label id="fm-cal-toggle" class="tnf-option-btn" style="cursor:pointer;user-select:none;margin-right:auto;">
                <input type="checkbox" id="fm-cal-cb" style="display:none;" onchange="document.getElementById('fm-cal-fields').style.display=this.checked?'flex':'none'">
                <span class="material-symbols-outlined">calendar_add_on</span>
                Zobraziť aj v kalendári
            </label>
            <button class="btn-secondary" onclick="closeTaskModal()">Zrušiť</button>
            <button class="btn-primary" onclick="saveTask()">
                <span class="material-symbols-outlined">save</span>
                Uložiť
            </button>
        </div>
        <div id="fm-cal-fields" style="display:none;gap:1rem;flex-wrap:wrap;margin-top:0.75rem;padding:1rem 1.25rem;background:rgba(99,102,241,0.05);border-radius:8px;border:1px solid rgba(99,102,241,0.2);">
            <div style="flex:1;min-width:150px;">
                <span class="fm-label">Dátum v kalendári</span>
                <input type="date" id="fm-event-date" class="form-control">
            </div>
            <div style="flex:1;min-width:120px;">
                <span class="fm-label">Čas (voliteľné)</span>
                <input type="time" id="fm-event-time" class="form-control">
            </div>
        </div>
    </div>
</div>

<!-- Confirm: Zmazať -->
<div class="tabula-confirm-overlay" id="delete-confirm">
    <div class="tabula-confirm-box">
        <h4>Zmazať úlohu?</h4>
        <p id="confirm-task-name"></p>
        <div class="tabula-confirm-actions">
            <button class="btn-secondary" onclick="closeDeleteConfirm()">Zrušiť</button>
            <button class="btn-danger" id="confirm-delete-btn">
                <span class="material-symbols-outlined">delete</span>
                Zmazať
            </button>
        </div>
    </div>
</div>

<script>
// Utility + notifikácie sú v /assets/js/dashboard-common.js

const MY_NAME = <?= json_encode($myName) ?>;

let g_tasks      = [];
let g_filter     = 'vsetko';
let g_list_view  = true;
let g_drag_id    = null;

const PRIO_LABELS   = { normalna: 'Normálna', vysoka: 'Vysoká', urgentna: 'Urgentná' };
const STATUS_LABELS = { nove: 'Nové', prebieha: 'Prebieha', vybavene: 'Vybavené' };
const TASK_COLORS   = ['#ef4444','#f97316','#f59e0b','#10b981','#06b6d4','#3b82f6','#8b5cf6','#ec4899','#64748b'];

// ── Farebný štítok: vykreslenie a výber ──
function buildColorSwatches(groupId, hiddenId, selected) {
    const group = document.getElementById(groupId);
    if (!group) return;
    let html = `<button type="button" class="color-swatch none-swatch ${!selected?'active':''}" title="Bez farby" onclick="selectColor('${groupId}','${hiddenId}',null,this)">
        <span class="material-symbols-outlined">block</span>
    </button>`;
    TASK_COLORS.forEach(c => {
        html += `<button type="button" class="color-swatch ${selected===c?'active':''}" style="background:${c};--swatch-color:${c};" title="${c}" onclick="selectColor('${groupId}','${hiddenId}','${c}',this)"></button>`;
    });
    group.innerHTML = html;
}
function selectColor(groupId, hiddenId, color, btn) {
    document.getElementById(hiddenId).value = color || '';
    document.querySelectorAll('#' + groupId + ' .color-swatch').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ── Načítanie ──
async function loadTasks() {
    try {
        const r   = await fetch('api/tabula.php?action=get_tasks');
        const res = await r.json();
        if (res.success) { g_tasks = res.tasks || []; renderGrid(); }
    } catch(e) { console.error(e); }
}

// ── Render ──
function renderGrid() {
    const grid   = document.getElementById('tabula-grid');
    const search = document.getElementById('tabula-search').value.toLowerCase().trim();

    let filtered = g_tasks.filter(t => {
        if (g_filter === 'moje')            return MY_NAME && (t.assigned_name === MY_NAME || t.created_by_name === MY_NAME);
        if (g_filter.startsWith('prio:'))   return t.priority === g_filter.slice(5);
        if (g_filter.startsWith('status:')) return t.status   === g_filter.slice(7);
        return true;
    });

    if (search) {
        filtered = filtered.filter(t =>
            (t.title         || '').toLowerCase().includes(search) ||
            (t.description   || '').toLowerCase().includes(search) ||
            (t.assigned_name || '').toLowerCase().includes(search) ||
            (t.note          || '').toLowerCase().includes(search)
        );
    }

    // ── Flat grid view ──
    if (g_list_view) {
        grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
        grid.innerHTML = filtered.length === 0
            ? `<div class="tabula-empty" style="grid-column:1/-1;">
                <span class="material-symbols-outlined">inbox</span><p>Žiadne úlohy</p></div>`
            : filtered.map(t => buildCard(t)).join('');
        return;
    }

    // ── Kanban view ──
    const cols = [
        { status: 'nove',     label: 'Nové',     dot: '#94a3b8' },
        { status: 'prebieha', label: 'Prebieha', dot: '#f59e0b' },
        { status: 'vybavene', label: 'Vybavené', dot: '#10b981' },
    ];

    const statusFilter = g_filter.startsWith('status:') ? g_filter.slice(7) : null;
    const visibleCols  = statusFilter ? cols.filter(c => c.status === statusFilter) : cols;

    grid.style.gridTemplateColumns = visibleCols.length === 1 ? '1fr' : 'repeat(3,1fr)';

    grid.innerHTML = visibleCols.map(col => {
        const colTasks = filtered.filter(t => t.status === col.status);
        const cardsHtml = colTasks.length === 0
            ? `<div style="text-align:center;padding:24px 10px;color:var(--text-secondary);font-size:12.5px;">Žiadne úlohy</div>`
            : colTasks.map(t => buildCard(t)).join('');

        return `<div class="kanban-col" data-col="${col.status}"
                    ondragover="onColDragOver(event,'${col.status}')"
                    ondragleave="onColDragLeave(event)"
                    ondrop="onColDrop(event,'${col.status}')">
            <div class="kanban-col-header">
                <div style="display:flex;align-items:center;">
                    <span class="col-dot" style="background:${col.dot};"></span>
                    ${col.label}
                </div>
                <span class="col-count">${colTasks.length}</span>
            </div>
            <div class="kanban-col-body">${cardsHtml}</div>
        </div>`;
    }).join('');
}

function buildCard(t) {
    const pinBtnCls = t.is_pinned == 1 ? 'pinned' : '';
    const pinIcon   = t.is_pinned == 1 ? 'keep' : 'keep_off';
    const pinTitle  = t.is_pinned == 1 ? 'Odopnúť' : 'Pripnúť';

    const assignedStr = t.assigned_name
        ? `<span class="material-symbols-outlined">person</span>${escapeHtml(t.assigned_name)}` : '';
    const dueStr = t.due_date
        ? `<span class="material-symbols-outlined">schedule</span>${fmtDate(t.due_date)}` : '';
    const updStr = (t.updated_at && t.updated_at.slice(0,10) !== (t.created_at||'').slice(0,10))
        ? `<span class="material-symbols-outlined">edit</span>${fmtDate(t.updated_at)}` : '';
    const metaLine = [assignedStr, dueStr, updStr].filter(Boolean).join('&nbsp;&nbsp;');

    const watchersStr = t.watchers
        ? `<div class="card-meta-row"><span class="material-symbols-outlined">visibility</span>${escapeHtml(t.watchers)}</div>` : '';
    const noteStr = t.note
        ? `<div class="card-divider"></div><div class="card-note" onclick="editTask(${t.id})">${escapeHtml(t.note)}</div>` : '';

    // Audio player
    const audioStr = t.audio_path
        ? `<div class="card-divider"></div><audio controls src="${escapeHtml('/' + t.audio_path)}" style="width:100%;height:32px;border-radius:4px;margin-top:2px;"></audio>`
        : '';

    // Attachments
    let attachStr = '';
    if (t.attachment_paths) {
        try {
            const atts = JSON.parse(t.attachment_paths);
            if (atts.length) {
                attachStr = '<div class="card-divider"></div>' + atts.map(a =>
                    `<a href="/${escapeHtml(a.path)}" target="_blank" style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;color:var(--primary-color);text-decoration:none;margin-right:8px;">
                        <span class="material-symbols-outlined" style="font-size:13px;">attach_file</span>${escapeHtml(a.name)}</a>`
                ).join('');
            }
        } catch(e) {}
    }

    const colorDot = t.color ? `<span class="card-color-dot" style="background:${escapeHtml(t.color)};"></span>` : '';

    return `<div class="task-card prio-${t.priority} ${t.is_pinned==1?'is-pinned':''}"
                data-id="${t.id}"
                draggable="true"
                ondragstart="onDragStart(event,${t.id})"
                ondragover="event.stopPropagation()"
                ondragend="onDragEnd(event)">
        ${t.color ? `<div class="card-color-strip" style="background:${escapeHtml(t.color)};"></div>` : ''}
        <div class="task-card-inner prio-border-${t.priority}">
        <div class="card-actions">
            <button class="card-act-btn ${pinBtnCls}" title="${pinTitle}" onclick="togglePin(${t.id})">
                <span class="material-symbols-outlined">${pinIcon}</span>
            </button>
            <button class="card-act-btn" title="Upraviť" onclick="editTask(${t.id})">
                <span class="material-symbols-outlined">edit</span>
            </button>
            <button class="card-act-btn del" title="Zmazať" onclick="askDeleteTask(${t.id})">
                <span class="material-symbols-outlined">delete</span>
            </button>
        </div>
        <div class="card-badges">
            <span class="badge badge-${t.priority}">${PRIO_LABELS[t.priority]||t.priority}</span>
            <span class="badge badge-${t.status}">${STATUS_LABELS[t.status]||t.status}</span>
        </div>
        <div class="card-title"><span class="card-pin-dot"></span>${colorDot}${escapeHtml(t.title)}</div>
        ${t.description ? `<div class="card-desc">${escapeHtml(t.description)}</div>` : ''}
        <div class="card-divider"></div>
        <div class="card-meta">
            ${metaLine ? `<div class="card-meta-row">${metaLine}</div>` : ''}
            ${watchersStr}
        </div>
        ${audioStr}
        ${attachStr}
        ${noteStr}
        </div>
    </div>`;
}

function fmtDate(str) {
    if (!str) return '';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.getDate() + '. ' + (d.getMonth()+1) + '. ' + d.getFullYear();
}

// ── Filtre ──
document.getElementById('tabula-filters').addEventListener('click', function(e) {
    const btn = e.target.closest('.tf-btn[data-filter]');
    if (!btn) return;
    document.querySelectorAll('#tabula-filters .tf-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    g_filter = btn.dataset.filter;
    renderGrid();
});

// ── Grid/Zoznam ──
function toggleView() {
    g_list_view = !g_list_view;
    document.getElementById('view-toggle-icon').textContent = g_list_view ? 'view_kanban' : 'grid_view';
    renderGrid();
}

// ── Drag & Drop (presun medzi stĺpcami = zmena statusu) ──
function onDragStart(e, id) {
    g_drag_id = id;
    e.currentTarget.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}
function onDragEnd(e) {
    e.currentTarget.classList.remove('dragging');
    document.querySelectorAll('.kanban-col').forEach(c => c.classList.remove('drop-target'));
}
function onColDragOver(e, status) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    e.currentTarget.classList.add('drop-target');
}
function onColDragLeave(e) {
    e.currentTarget.classList.remove('drop-target');
}
async function onColDrop(e, newStatus) {
    e.preventDefault();
    e.currentTarget.classList.remove('drop-target');
    if (!g_drag_id) return;
    const task = g_tasks.find(t => t.id == g_drag_id);
    if (!task || task.status === newStatus) { g_drag_id = null; return; }

    // Optimistic update
    task.status = newStatus;
    renderGrid();

    const fd = new FormData();
    fd.append('action', 'set_status');
    fd.append('id', g_drag_id);
    fd.append('status', newStatus);
    try { await fetch('api/tabula.php', { method: 'POST', body: fd }); } catch(e) {}
    g_drag_id = null;
}

// ── Modal ──
function openTaskModal(task) {
    document.getElementById('fm-id').value       = task ? task.id : '';
    document.getElementById('fm-title').value    = task ? task.title : '';
    document.getElementById('fm-desc').value     = task ? (task.description||'') : '';
    document.getElementById('fm-assigned').value = task ? (task.assigned_name||'') : '';
    document.getElementById('fm-due').value      = task ? (task.due_date||'') : '';
    document.getElementById('fm-watchers').value = task ? (task.watchers||'') : '';
    document.getElementById('fm-note').value     = task ? (task.note||'') : '';
    document.getElementById('modal-title').innerText = task ? 'Upraviť úlohu' : 'Nová úloha';

    const prio   = task ? (task.priority||'normalna') : 'normalna';
    const status = task ? (task.status  ||'nove')     : 'nove';
    document.querySelectorAll('.fm-sel-btn[data-prio]').forEach(b =>
        b.classList.toggle('active', b.dataset.prio === prio));
    document.querySelectorAll('.fm-sel-btn[data-status]').forEach(b =>
        b.classList.toggle('active', b.dataset.status === status));
    buildColorSwatches('fm-color-group', 'fm-color', task ? (task.color || null) : null);

    document.getElementById('task-modal').classList.add('open');
    setTimeout(() => document.getElementById('fm-title').focus(), 50);
}
function closeTaskModal() {
    document.getElementById('task-modal').classList.remove('open');
    document.getElementById('fm-cal-cb').checked = false;
    document.getElementById('fm-cal-fields').style.display = 'none';
    document.getElementById('fm-event-date').value = '';
    document.getElementById('fm-event-time').value = '';
}

function selectPrio(btn) {
    document.querySelectorAll('.fm-sel-btn[data-prio]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function selectStatus(btn) {
    document.querySelectorAll('.fm-sel-btn[data-status]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function editTask(id) {
    const t = g_tasks.find(x => x.id == id);
    if (t) openTaskModal(t);
}

// ── CRUD ──
async function saveTask() {
    const id    = document.getElementById('fm-id').value;
    const title = document.getElementById('fm-title').value.trim();
    if (!title) { showAppToast('Názov úlohy je povinný.', 'error'); return; }

    const prio   = document.querySelector('.fm-sel-btn[data-prio].active')?.dataset.prio   || 'normalna';
    const status = document.querySelector('.fm-sel-btn[data-status].active')?.dataset.status || 'nove';

    const fd = new FormData();
    fd.append('action',        id ? 'update_task' : 'add_task');
    if (id) fd.append('id', id);
    fd.append('title',         title);
    fd.append('description',   document.getElementById('fm-desc').value.trim());
    fd.append('priority',      prio);
    fd.append('status',        status);
    fd.append('assigned_name', document.getElementById('fm-assigned').value.trim());
    fd.append('due_date',      document.getElementById('fm-due').value);
    fd.append('watchers',      document.getElementById('fm-watchers').value.trim());
    fd.append('note',          document.getElementById('fm-note').value.trim());
    fd.append('color',         document.getElementById('fm-color').value);
    fd.append('event_date',    document.getElementById('fm-event-date').value);
    fd.append('event_time',    document.getElementById('fm-event-time').value);

    try {
        const r   = await fetch('api/tabula.php', { method: 'POST', body: fd });
        const res = await r.json();
        if (res.success) {
            closeTaskModal();
            showAppToast(id ? 'Úloha aktualizovaná.' : 'Úloha pridaná.', 'success');
            await loadTasks();
        } else { showAppToast(res.message || 'Chyba.', 'error'); }
    } catch(e) { showAppToast('Chyba komunikácie.', 'error'); }
}

async function togglePin(id) {
    const fd = new FormData(); fd.append('action','toggle_pin'); fd.append('id', id);
    const r = await fetch('api/tabula.php', { method: 'POST', body: fd });
    const res = await r.json();
    if (res.success) await loadTasks();
}

// ── Delete confirm ──
let g_delete_id = null;
function askDeleteTask(id) {
    const t = g_tasks.find(x => x.id == id);
    if (!t) return;
    g_delete_id = id;
    document.getElementById('confirm-task-name').textContent = 'Naozaj chcete zmazať úlohu "' + t.title + '"?';
    document.getElementById('delete-confirm').classList.add('open');
}
function closeDeleteConfirm() {
    g_delete_id = null;
    document.getElementById('delete-confirm').classList.remove('open');
}
document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
    if (!g_delete_id) return;
    const id = g_delete_id; closeDeleteConfirm();
    const fd = new FormData(); fd.append('action','delete_task'); fd.append('id', id);
    const r = await fetch('api/tabula.php', { method: 'POST', body: fd });
    const res = await r.json();
    if (res.success) { showAppToast('Úloha zmazaná.', 'success'); await loadTasks(); }
});
document.getElementById('delete-confirm').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteConfirm();
});

document.getElementById('fm-title').addEventListener('keydown', e => {
    if (e.key === 'Enter') saveTask();
});

// ── New Form Toggle ──
function showNewForm() {
    document.getElementById('tabula-new-page').classList.add('open');
    document.getElementById('tabula-board').style.display = 'none';
    resetNewForm();
    setTimeout(() => document.getElementById('nf-title').focus(), 50);
}
function hideNewForm() {
    document.getElementById('tabula-new-page').classList.remove('open');
    document.getElementById('tabula-board').style.display = '';
    if (g_is_recording && g_recorder) { try { g_recorder.stop(); } catch(e) {} }
}
function resetNewForm() {
    ['nf-title','nf-desc','nf-assigned','nf-due','nf-watchers','nf-note'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    document.querySelectorAll('[data-nfprio]').forEach(b => b.classList.toggle('active', b.dataset.nfprio === 'normalna'));
    document.querySelectorAll('[data-nfstatus]').forEach(b => b.classList.toggle('active', b.dataset.nfstatus === 'nove'));
    buildColorSwatches('nf-color-group', 'nf-color', null);
    g_nf_pinned = false;
    document.getElementById('nf-pin-btn').classList.remove('active');
    g_audio_blob = null; g_is_recording = false;
    document.getElementById('voice-icon').textContent = 'mic';
    document.getElementById('voice-label').textContent = 'Nahrať hlasovú správu';
    document.getElementById('voice-sub').textContent = 'Kliknutím spustíte nahrávanie';
    document.getElementById('voice-zone').classList.remove('recording');
    document.getElementById('nf-files').value = '';
    document.getElementById('nf-file-placeholder').style.display = '';
    document.getElementById('nf-file-info').style.display = 'none';
    document.getElementById('nf-cal-cb').checked = false;
    document.getElementById('nf-cal-fields').style.display = 'none';
    document.getElementById('nf-event-date').value = '';
    document.getElementById('nf-event-time').value = '';
}

// ── NF Selectors ──
let g_nf_pinned = false;
function selectNFPrio(btn) {
    document.querySelectorAll('[data-nfprio]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function selectNFStatus(btn) {
    document.querySelectorAll('[data-nfstatus]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function toggleNFPin() {
    g_nf_pinned = !g_nf_pinned;
    document.getElementById('nf-pin-btn').classList.toggle('active', g_nf_pinned);
    document.getElementById('nf-pin-btn').querySelector('.material-symbols-outlined').textContent = g_nf_pinned ? 'keep' : 'keep_off';
}
function setNFTemplate(title, content, prio) {
    document.getElementById('nf-title').value = title;
    document.getElementById('nf-desc').value  = content;
    document.querySelectorAll('[data-nfprio]').forEach(b => b.classList.toggle('active', b.dataset.nfprio === prio));
    document.getElementById('nf-desc').focus();
}

// ── Voice Recording ──
let g_recorder = null;
let g_audio_chunks = [];
let g_audio_blob = null;
let g_is_recording = false;

async function toggleRecording() {
    if (g_is_recording) {
        try { g_recorder.stop(); } catch(e) {}
    } else {
        // Reset blob
        g_audio_blob = null;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            g_audio_chunks = [];
            g_recorder = new MediaRecorder(stream);
            g_recorder.ondataavailable = e => { if (e.data.size > 0) g_audio_chunks.push(e.data); };
            g_recorder.onstop = () => {
                g_audio_blob = new Blob(g_audio_chunks, { type: 'audio/webm' });
                stream.getTracks().forEach(t => t.stop());
                g_is_recording = false;
                document.getElementById('voice-icon').textContent = 'check_circle';
                document.getElementById('voice-label').textContent = 'Nahrávanie hotové';
                document.getElementById('voice-sub').textContent = Math.round(g_audio_blob.size / 1024) + ' KB — klikni pre nové nahrávanie';
                document.getElementById('voice-zone').classList.remove('recording');
            };
            g_recorder.start();
            g_is_recording = true;
            document.getElementById('voice-icon').textContent = 'stop_circle';
            document.getElementById('voice-label').textContent = 'Nahrávanie prebieha...';
            document.getElementById('voice-sub').textContent = 'Kliknutím zastavíte';
            document.getElementById('voice-zone').classList.add('recording');
        } catch(err) {
            showAppToast('Mikrofón nie je dostupný: ' + err.message, 'error');
        }
    }
}

// ── File List ──
function updateNFFileList(input) {
    const files = Array.from(input.files);
    if (!files.length) return;
    document.getElementById('nf-file-placeholder').style.display = 'none';
    const info = document.getElementById('nf-file-info');
    info.style.display = 'flex';
    info.innerHTML = files.map(f =>
        `<div style="font-size:12px;color:var(--text-primary);display:flex;align-items:center;gap:5px;">
            <span class="material-symbols-outlined" style="font-size:14px;color:var(--primary-color);">attach_file</span>
            ${escapeHtml(f.name)} <span style="color:var(--text-secondary);">(${Math.round(f.size/1024)} KB)</span>
        </div>`
    ).join('');
}

// ── Save New Form ──
async function saveNewForm() {
    const title = document.getElementById('nf-title').value.trim();
    if (!title) { showAppToast('Nadpis správy je povinný.', 'error'); return; }

    const prio   = document.querySelector('[data-nfprio].active')?.dataset.nfprio   || 'normalna';
    const status = document.querySelector('[data-nfstatus].active')?.dataset.nfstatus || 'nove';

    const fd = new FormData();
    fd.append('action',        'add_task');
    fd.append('title',         title);
    fd.append('description',   document.getElementById('nf-desc').value.trim());
    fd.append('priority',      prio);
    fd.append('status',        status);
    fd.append('assigned_name', document.getElementById('nf-assigned').value.trim());
    fd.append('due_date',      document.getElementById('nf-due').value);
    fd.append('watchers',      document.getElementById('nf-watchers').value.trim());
    fd.append('note',          document.getElementById('nf-note').value.trim());
    fd.append('color',         document.getElementById('nf-color').value);
    fd.append('is_pinned',     g_nf_pinned ? '1' : '0');
    fd.append('event_date',    document.getElementById('nf-event-date').value);
    fd.append('event_time',    document.getElementById('nf-event-time').value);
    if (g_audio_blob) fd.append('audio', g_audio_blob, 'recording.webm');
    const fileInput = document.getElementById('nf-files');
    for (const f of fileInput.files) fd.append('attachments[]', f, f.name);

    try {
        const r   = await fetch('api/tabula.php', { method: 'POST', body: fd });
        const res = await r.json();
        if (res.success) {
            hideNewForm();
            showAppToast('Odkaz pridaný.', 'success');
            await loadTasks();
        } else { showAppToast(res.message || 'Chyba.', 'error'); }
    } catch(e) { showAppToast('Chyba komunikácie.', 'error'); }
}

document.addEventListener('DOMContentLoaded', loadTasks);
</script>
</body>
</html>
