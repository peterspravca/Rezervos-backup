<?php
/**
 * Shared Signature Modal Component
 * Usage: include 'partials/signature_modal.php';
 * Required: An <input type="hidden" id="signature_data" name="signature_data"> in your form.
 */
?>
<!-- Signature Modal Styles -->
<style>
    .modal-overlay-sig {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .modal-content-sig {
        background: white;
        width: 100%;
        max-width: 600px;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: modalFadeIn 0.3s ease-out;
        color: #1e293b;
    }
    @keyframes modalFadeIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .modal-header-sig {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .modal-title-sig { font-weight: 800; font-size: 1.25rem; color: #0f172a; display: flex; align-items: center; gap: 10px; }
    .close-modal-sig { 
        background: #f1f5f9; border: none; width: 36px; height: 36px; 
        border-radius: 50%; cursor: pointer; color: #64748b;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .close-modal-sig:hover { background: #fee2e2; color: #ef4444; }
    
    .signature-wrap-sig {
        position: relative;
        height: 300px;
        background: #fdfdfd;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        margin: 1rem 0;
        overflow: hidden;
        touch-action: none !important;
    }
    #signature_canvas_modal {
        width: 100%;
        height: 100%;
        display: block;
        cursor: crosshair;
    }
    
    .modal-footer-sig { display: flex; gap: 12px; margin-top: 1.5rem; }
    .btn-sig {
        padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 0.95rem;
        cursor: pointer; transition: all 0.2s; border: none; font-family: inherit;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-save-sig { background: #2563eb; color: white; flex: 2; }
    .btn-save-sig:hover { background: #1d4ed8; transform: translateY(-1px); }
    .btn-clear-sig { background: #f1f5f9; color: #64748b; flex: 1; }
    .btn-clear-sig:hover { background: #e2e8f0; }

    /* Preview Component Styles */
    .sig-preview-trigger {
        width: 100%;
        height: 120px;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        background: rgba(37,99,235,0.02);
        color: #64748b;
        position: relative;
        overflow: hidden;
    }
    .sig-preview-trigger:hover { border-color: #2563eb; background: rgba(37,99,235,0.05); color: #2563eb; }
    .sig-preview-img-modal { max-height: 100px; max-width: 90%; display: none; }
    .sig-placeholder-sig { font-size: 0.85rem; font-weight: 600; text-align: center; }
    .sig-placeholder-sig i { font-size: 1.8rem; margin-bottom: 5px; opacity: 0.5; display: block; }

    @media (max-width: 600px) {
        .modal-content-sig { padding: 1.5rem 1rem; border-radius: 20px 20px 0 0; position: fixed; bottom: 0; }
        .signature-wrap-sig { height: 260px; }
        .modal-overlay-sig { align-items: flex-end; padding: 0; }
    }

    /* Rozšírená optimalizácia pre landscape (na šírku) */
    @media (max-height: 500px) {
        .modal-overlay-sig { padding: 0; align-items: stretch; }
        .modal-content-sig { 
            padding: 10px !important; 
            max-width: 100% !important; 
            border-radius: 0 !important;
            height: 100vh !important;
            max-height: 100vh !important;
            display: grid !important;
            grid-template-columns: 1fr 140px; /* Tlačidlá na boku */
            grid-template-rows: auto 1fr;
            gap: 10px;
            box-sizing: border-box;
        }
        .modal-header-sig { 
            grid-column: 1 / 3; 
            margin: 0 !important;
            padding-bottom: 5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-title-sig { font-size: 0.9rem !important; }
        .signature-wrap-sig { 
            grid-column: 1 / 2; 
            grid-row: 2 / 3;
            height: calc(100vh - 65px) !important; 
            margin: 0 !important;
            border-width: 1px;
        }
        .modal-footer-sig { 
            grid-column: 2 / 3; 
            grid-row: 2 / 3;
            flex-direction: column !important;
            margin: 0 !important;
            gap: 8px !important;
            justify-content: center;
        }
        .btn-sig { 
            width: 100% !important; 
            padding: 10px 5px !important; 
            font-size: 0.85rem !important;
            flex: none !important;
        }
        .hide-landscape { display: none !important; }
    }
</style>

<!-- Signature Modal HTML -->
<div id="signature_modal_global" class="modal-overlay-sig">
    <div class="modal-content-sig">
        <div class="modal-header-sig">
            <div class="modal-title-sig"><i class="ti ti-writing-sign"></i> Pridanie podpisu</div>
            <button type="button" class="close-modal-sig" onclick="closeSignatureModalGlobal()"><i class="ti ti-x"></i></button>
        </div>
        <div class="hide-landscape" style="font-size: 0.9rem; color: #64748b; margin-bottom: 1rem; line-height: 1.4;">
            Podpíšte sa do poľa nižšie. <span class="hide-mobile">Použite myš.</span><span style="display:none;" id="mobile_note">Na mobile otočte telefón pre viac miesta.</span>
        </div>
        <div class="signature-wrap-sig">
            <canvas id="signature_canvas_modal"></canvas>
        </div>
        <div class="modal-footer-sig">
            <button type="button" class="btn-sig btn-clear-sig" id="modal_clear_global">Vymazať</button>
            <button type="button" class="btn-sig btn-save-sig" onclick="saveSignatureGlobal()">Uložiť podpis</button>
        </div>
    </div>
</div>

<!-- SignaturePad Library Script -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
    let globalSignaturePad;
    let lastSignatureData = null; // Tu budeme držať zálohu bodov
    const sigModalGlobal = document.getElementById('signature_modal_global');
    const sigCanvasGlobal = document.getElementById('signature_canvas_modal');
    let targetPreviewImg = null;
    let targetPlaceholder = null;
    let targetInput = null;

    document.addEventListener('DOMContentLoaded', function() {
        if (!sigCanvasGlobal) return;

        globalSignaturePad = new SignaturePad(sigCanvasGlobal, {
            minWidth: 1.2,
            maxWidth: 4.5,
            penColor: "#0f172a",
            velocityFilterWeight: 0.7
        });

        // Po každom dokončení ťahu si uložíme dáta do zálohy
        globalSignaturePad.onEnd = function() {
            lastSignatureData = globalSignaturePad.toData();
        };

        if ('ontouchstart' in window) {
            const note = document.getElementById('mobile_note');
            if(note) note.style.display = 'inline';
        }

        window.addEventListener("resize", resizeSigCanvasGlobal);
        document.getElementById('modal_clear_global').addEventListener('click', (e) => {
            e.preventDefault();
            globalSignaturePad.clear();
            lastSignatureData = null;
        });
    });

    let resizeTimeout;
    function resizeSigCanvasGlobal() {
        if (!sigModalGlobal || sigModalGlobal.style.display === 'none') return;
        
        clearTimeout(resizeTimeout);
        
        // Pri otáčaní si okamžite vezmeme aktuálne dáta, ak by náhodou SignaturePad ešte nemal onEnd
        const currentData = globalSignaturePad.toData();
        if (currentData.length > 0) {
            lastSignatureData = currentData;
        }

        resizeTimeout = setTimeout(() => {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            
            // Záloha pred zmenou rozmerov
            const oldWidth = sigCanvasGlobal.width;
            const oldHeight = sigCanvasGlobal.height;
            
            sigCanvasGlobal.width = sigCanvasGlobal.offsetWidth * ratio;
            sigCanvasGlobal.height = sigCanvasGlobal.offsetHeight * ratio;
            
            const ctx = sigCanvasGlobal.getContext("2d");
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            
            globalSignaturePad.clear();
            
            if (lastSignatureData) {
                // SignaturePad draw from data
                globalSignaturePad.fromData(lastSignatureData);
            }
        }, 100); // Skrátené na 100ms pre rýchlejšiu odozvu
    }

    window.openSignatureModalGlobal = function(inputId, previewId, placeholderId) {
        targetInput = document.getElementById(inputId);
        targetPreviewImg = document.getElementById(previewId);
        targetPlaceholder = document.getElementById(placeholderId);
        
        sigModalGlobal.style.display = 'flex';
        lastSignatureData = null;
        setTimeout(resizeSigCanvasGlobal, 50);
    };

    window.closeSignatureModalGlobal = function() {
        sigModalGlobal.style.display = 'none';
        globalSignaturePad.clear();
        lastSignatureData = null;
    };

    window.saveSignatureGlobal = function() {
        if (globalSignaturePad.isEmpty()) {
            alert("Pred uložením sa prosím podpíšte.");
            return;
        }
        const dataUrl = globalSignaturePad.toDataURL('image/png');
        if (targetInput) targetInput.value = dataUrl;
        if (targetPreviewImg) {
            targetPreviewImg.src = dataUrl;
            targetPreviewImg.style.display = 'block';
        }
        if (targetPlaceholder) targetPlaceholder.style.display = 'none';
        
        sigModalGlobal.style.display = 'none';
    };

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sigModalGlobal.style.display === 'flex') {
            closeSignatureModalGlobal();
        }
    });
</script>
