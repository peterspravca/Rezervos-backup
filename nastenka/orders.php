<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = db_connect();
$user = current_user();

$tab = $_GET['tab'] ?? 'active';
$page_title = ($tab === 'archive') ? "Archív zákaziek" : "Aktuálne zákazky";

// Fetch orders
if ($tab === 'archive') {
    $sql = "SELECT * FROM leads WHERE status = 'ukonceny' AND order_number IS NOT NULL ORDER BY handover_date DESC, updated_at DESC";
} else {
    // Show both 'zakazka' and any other that has an order number but isn't 'ukonceny'
    $sql = "SELECT * FROM leads WHERE (status = 'zakazka' OR (order_number IS NOT NULL AND status != 'ukonceny')) ORDER BY created_at DESC";
}

$orders = $pdo->query($sql)->fetchAll();

// Stats for the current view
$total_count = count($orders);
$total_value = 0;
foreach($orders as $o) {
    if ($o['quote_price']) $total_value += $o['quote_price'];
    elseif ($o['deal_value']) $total_value += $o['deal_value'];
}

include __DIR__ . '/partials/header.php';
?>

<div class="orders-container">
    <!-- Premium Unified Header -->
    <div class="card orders-header mb-3">
        <div class="flex justify-between items-center gap-2 flex-wrap">
            <!-- Tab Switcher -->
            <div class="tab-switcher">
                <a href="orders.php?tab=active" class="tab-item <?= $tab === 'active' ? 'active' : '' ?>">
                    <i class="ti ti-loader"></i> Aktuálne
                </a>
                <a href="orders.php?tab=archive" class="tab-item <?= $tab === 'archive' ? 'active' : '' ?>">
                    <i class="ti ti-archive"></i> Archív
                </a>
            </div>

            <!-- Header Stats -->
            <div class="header-stats flex gap-3">
                <div class="stat-group">
                    <div class="stat-label">Zákazky</div>
                    <div class="stat-value"><?= $total_count ?></div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-group">
                    <div class="stat-label"><?= $tab==='archive'?'Zrealizované':'Potenciálne' ?></div>
                    <div class="stat-value accent"><?= number_format($total_value, 0, ',', ' ') ?> €</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card empty-state-card p-5 text-center">
            <div class="es-icon-wrapper">
                <i class="ti ti-clipboard-off"></i>
            </div>
            <h3 class="mt-2 mb-1">Žiadne zákazky</h3>
            <p class="text-muted">V tejto sekcii sa momentálne nenachádzajú žiadne záznamy.</p>
        </div>
    <?php else: ?>
        <div class="grid-1 md:grid-2 lg:grid-3 gap-2">
            <?php foreach ($orders as $o): 
                $steps = [
                    ['l' => 'Obhliadka', 'done' => !empty($o['survey_date'])],
                    ['l' => 'Ponuka/Obj', 'done' => !empty($o['quote_price'])],
                    ['l' => 'Výroba',    'done' => !empty($o['production_at'])],
                    ['l' => 'Realizácia', 'done' => !empty($o['realization_date'])],
                    ['l' => 'Odovzdanie','done' => !empty($o['handover_date']) || $o['status'] === 'ukonceny']
                ];
                $done_count = 0;
                foreach($steps as $s) if($s['done']) $done_count++;
                $progress_pct = ($done_count / count($steps)) * 100;
            ?>
                <div class="card order-card" onclick="window.location='contact.php?id=<?= $o['id'] ?>'">
                    <div class="card-tag">#<?= htmlspecialchars($o['order_number'] ?? $o['id']) ?></div>
                    
                    <div class="flex justify-between items-start mb-2 mt-1">
                        <div class="order-info">
                            <h3 class="order-address"><?= htmlspecialchars(($o['address'] ?: 'Bez adresy') . ', ' . ($o['city'] ?: 'Bez mesta')) ?></h3>
                            <div class="order-client">
                                <?= htmlspecialchars($o['name']) ?>
                                <?php if(($o['gender'] ?? 'unknown') === 'female'): ?>
                                    <i class="ti ti-user-heart female"></i>
                                <?php elseif(($o['gender'] ?? 'unknown') === 'male'): ?>
                                    <i class="ti ti-user-bolt male"></i>
                                <?php elseif(($o['gender'] ?? 'unknown') === 'other'): ?>
                                    <i class="ti ti-building-community company"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="status-badge <?= status_class($o['status']) ?>"><?= status_label($o['status']) ?></span>
                    </div>

                    <!-- Enhanced Progress -->
                    <div class="progress-section mb-3">
                        <div class="flex justify-between items-center text-xs mb-1">
                            <span class="text-muted font-bold text-uppercase" style="font-size:0.65rem; letter-spacing:0.5px;">Postup prác</span>
                            <span class="progress-pct"><?= round($progress_pct) ?>%</span>
                        </div>
                        <div class="progress-steps-bar">
                            <?php foreach($steps as $s): ?>
                                <div class="p-step <?= $s['done'] ? 'done' : '' ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="order-footer">
                         <div class="order-price">
                            <?php if($o['quote_price']): ?>
                                <span class="price-val"><?= number_format($o['quote_price'],0,',',' ') ?> €</span>
                            <?php else: ?>
                                <span class="price-none">Cena neurčená</span>
                            <?php endif; ?>
                         </div>
                         <div class="order-meta text-right">
                            <i class="ti ti-history"></i> <?= time_ago($o['updated_at'] ?? $o['created_at']) ?>
                         </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Unified Layout */
.orders-container { padding-bottom: 2rem; }

/* Premium Header */
.orders-header {
    background: linear-gradient(135deg, rgba(30,41,59,0.7), rgba(15,23,42,0.8));
    border: 1px solid var(--border);
    backdrop-filter: blur(12px);
    padding: 1.25rem 1.5rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
}

/* Tab Switcher (Segmented Control) */
.tab-switcher {
    display: flex;
    background: rgba(0,0,0,0.2);
    padding: 4px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
}
.tab-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.tab-item i { font-size: 1.1rem; opacity: 0.7; }
.tab-item:hover { color: var(--text-primary); background: rgba(255,255,255,0.03); }
.tab-item.active {
    background: var(--accent);
    color: white;
    box-shadow: 0 4px 12px var(--accent-glow);
}
.tab-item.active i { opacity: 1; }

/* Header Stats */
.header-stats { align-items: center; }
.stat-group { text-align: right; }
.stat-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 800; margin-bottom: 2px; }
.stat-value { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); line-height: 1; }
.stat-value.accent { color: var(--accent-2); }
.stat-divider { width: 1px; height: 24px; background: var(--border); margin: 0 0.5rem; }

/* Order Cards */
.order-card {
    position: relative;
    padding: 1.25rem;
    border: 1px solid var(--border);
    background: var(--bg-card);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.order-card::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    opacity: 0; transition: opacity 0.3s;
}
.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.4);
    border-color: var(--accent);
}
.order-card:hover::before { opacity: 1; }

.card-tag {
    font-size: 0.65rem; font-weight: 800; color: var(--accent);
    text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;
}
.order-address { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--text-primary); line-height: 1.3; }
.order-client { font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-top: 4px; }
.order-client i { font-size: 1rem; }
.order-client i.female { color: #db2777; }
.order-client i.male { color: #2563eb; }
.order-client i.company { color: #d97706; }

/* Progress Bar */
.progress-steps-bar {
    display: flex;
    gap: 4px;
    height: 8px;
}
.p-step {
    flex: 1;
    background: rgba(255,255,255,0.05);
    border-radius: 4px;
    transition: all 0.4s;
}
.p-step.done {
    background: linear-gradient(to right, var(--accent), var(--accent-2));
    box-shadow: 0 0 5px var(--accent-glow);
}
.progress-pct { font-weight: 800; color: var(--accent-2); }

/* Card Footer */
.order-footer {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid rgba(255,255,255,0.05);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.price-val { font-size: 1.1rem; font-weight: 800; color: var(--text-primary); }
.price-none { font-size: 0.8rem; color: var(--text-muted); font-style: italic; }
.order-meta { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; }
.order-meta i { font-size: 0.8rem; margin-right: 2px; }

/* Empty State */
.empty-state-card { background: rgba(255,255,255,0.02); border: 2px dashed var(--border); }
.es-icon-wrapper {
    width: 80px; height: 80px; background: rgba(99,102,241,0.05);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    margin: 0 auto; font-size: 3rem; color: var(--text-muted); opacity: 0.4;
}

@media (max-width: 768px) {
    .orders-header { flex-direction: column; align-items: stretch; gap: 1rem; }
    .header-stats { justify-content: space-between; }
    .tab-switcher { justify-content: center; }
}
</style>

<style>
.order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.3);
    border-color: rgba(99, 102, 241, 0.4);
}
.bg-glass-light {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border);
    backdrop-filter: blur(10px);
}
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
