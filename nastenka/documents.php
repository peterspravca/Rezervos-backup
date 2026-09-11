<?php
require_once __DIR__ . '/auth.php';
require_admin();

$page_title = 'Dokumenty a tlačivá';
include __DIR__ . '/partials/header.php';
?>

<div class="content-header" style="margin-bottom: 2rem;">
    <div class="flex justify-between items-center">
        <div>
            <h1 style="margin:0; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.025em; color: var(--text-primary);">
                Dokumenty a tlačivá
            </h1>
            <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 0.95rem;">
                Vyberte si vzor dokumentu pre online vyplnenie alebo stiahnutie.
            </p>
        </div>
    </div>
</div>

<div class="doc-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
    
    <!-- Cenová ponuka -->
    <div class="card doc-card-hover" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s, border-color 0.2s;">
        <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
            <div class="card-title" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                    <i class="ti ti-file-description" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem;">Cenová ponuka</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Online vzor / PDF</div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 1.5rem; flex: 1;">
            <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.4; margin: 0;">
                Šablóna pre výpočet a zaslanie ponuky klientovi.
            </p>
        </div>
        <div class="card-footer" style="padding: 1.25rem; border-top: 1px solid var(--border); background: rgba(255,255,255,0.01);">
            <a href="offer_generator.php" class="btn btn-primary w-full" style="justify-content: center; gap: 8px; background: #f59e0b; border: none;">
                <i class="ti ti-plus"></i> Vytvoriť ponuku
            </a>
        </div>
    </div>

    <!-- Objednávka -->
    <div class="card doc-card-hover" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s, border-color 0.2s;">
        <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
            <div class="card-title" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99, 102, 241, 0.1); display: flex; align-items: center; justify-content: center; color: var(--accent-2);">
                    <i class="ti ti-file-invoice" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem;">Objednávka</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Online vzor / PDF</div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 1.5rem; flex: 1;">
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                Záväzná objednávka tovaru a služieb od zákazníka. Slúži ako podklad pre vystavenie zálohovej faktúry a zadanie do výroby.
            </p>
        </div>
        <div class="card-footer" style="padding: 1.25rem; border-top: 1px solid var(--border); background: rgba(255,255,255,0.01);">
            <a href="order_generator.php" class="btn btn-primary w-full" style="justify-content: center; gap: 8px;">
                <i class="ti ti-plus"></i> Vytvoriť objednávku
            </a>
        </div>
    </div>

    <!-- Dodací list -->
    <div class="card doc-card-hover" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s, border-color 0.2s;">
        <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
            <div class="card-title" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: #10b981;">
                    <i class="ti ti-truck-delivery" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem;">Dodací list</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Online vzor / PDF</div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 1.5rem; flex: 1;">
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                Sprievodný doklad k tovaru. Obsahuje zoznam položiek a údaje o preprave a prevzatí.
            </p>
        </div>
        <div class="card-footer" style="padding: 1.25rem; border-top: 1px solid var(--border); background: rgba(255,255,255,0.01);">
            <a href="delivery_generator.php" class="btn btn-primary w-full" style="justify-content: center; gap: 8px; background: #10b981; border: none;">
                <i class="ti ti-plus"></i> Vytvoriť dodací list
            </a>
        </div>
    </div>

    <!-- Preberací protokol -->
    <div class="card doc-card-hover" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s, border-color 0.2s;">
        <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
            <div class="card-title" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; color: #ef4444;">
                    <i class="ti ti-clipboard-check" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem;">Preberací protokol</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Online vzor / PDF</div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 1.5rem; flex: 1;">
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                Protokol o odovzdaní a prevzatí diela alebo tovaru so záznamom o stave a závadách.
            </p>
        </div>
        <div class="card-footer" style="padding: 1.25rem; border-top: 1px solid var(--border); background: rgba(255,255,255,0.01);">
            <a href="protocol_generator.php" class="btn btn-primary w-full" style="justify-content: center; gap: 8px; background: #ef4444; border: none;">
                <i class="ti ti-plus"></i> Vytvoriť protokol
            </a>
        </div>
    </div>

    <!-- Príjmový doklad -->
    <div class="card doc-card-hover" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s, border-color 0.2s;">
        <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
            <div class="card-title" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(8, 145, 178, 0.1); display: flex; align-items: center; justify-content: center; color: #0891b2;">
                    <i class="ti ti-receipt" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem;">Príjmový doklad</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Online vzor / PDF</div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 1.5rem; flex: 1;">
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                Potvrdenie o prijatí platby v hotovosti. Obsahuje všetky náležitosti pre pokladničný doklad.
            </p>
        </div>
        <div class="card-footer" style="padding: 1.25rem; border-top: 1px solid var(--border); background: rgba(255,255,255,0.01);">
            <a href="receipt_generator.php" class="btn btn-primary w-full" style="justify-content: center; gap: 8px; background: #0891b2; border: none;">
                <i class="ti ti-plus"></i> Vytvoriť príjmový doklad
            </a>
        </div>
    </div>
    
    <!-- Zálohová faktúra -->
    <div class="card doc-card-hover" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s, border-color 0.2s;">
        <div class="card-header pb-2" style="border-bottom: 1px solid var(--border);">
            <div class="card-title" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(14, 165, 233, 0.1); display: flex; align-items: center; justify-content: center; color: #0ea5e9;">
                    <i class="ti ti-currency-euro" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem;">Zálohová faktúra</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Online vzor / QR platba</div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 1.5rem; flex: 1;">
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                Výzva k úhrade pre zákazníka. Obsahuje IBAN, variabilný symbol a inteligentný výpočet výšky zálohy.
            </p>
        </div>
        <div class="card-footer" style="padding: 1.25rem; border-top: 1px solid var(--border); background: rgba(255,255,255,0.01);">
            <a href="proforma_generator.php" class="btn btn-primary w-full" style="justify-content: center; gap: 8px; background: #0ea5e9; border: none;">
                <i class="ti ti-plus"></i> Vytvoriť zálohovku
            </a>
        </div>
    </div>

</div>

<style>
.doc-card-hover:hover {
    transform: translateY(-5px);
    border-color: var(--accent) !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
@media (max-width: 768px) {
    .doc-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
