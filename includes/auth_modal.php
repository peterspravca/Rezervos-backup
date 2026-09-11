<?php
$prefix = $prefix ?? '';
// Zachytíme chybu/cieľový pohľad ešte pred prvým (login) blokom nižšie, ktorý by inak
// $_SESSION['auth_error'] zmazal skôr, než sa dostane k pohľadu, pre ktorý bol naozaj určený
// (napr. chyba z registrácie by sa stratila v skrytom login pohľade).
$__modal_auth_error = $_SESSION['auth_error'] ?? null;
$__modal_auth_view = $_SESSION['auth_view'] ?? null;
unset($_SESSION['auth_error'], $_SESSION['auth_view']);
?>
<style>
.auth-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 40px;
    width: 100%;
    max-width: 800px; /* Široké pre horizontálne zobrazenie na desktopoch */
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: row;
    gap: 40px;
    align-items: stretch;
    transition: all 0.3s ease;
    position: relative;
    box-sizing: border-box;
    max-height: 90vh;
    overflow-y: auto;
}
.auth-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    border-right: 1px solid var(--border-color);
    padding-right: 40px;
    min-height: 280px;
}
.auth-right {
    flex: 1.2;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.auth-logo {
    display: flex;
    justify-content: center;
    margin-top: auto;
    margin-bottom: 20px;
}
.auth-logo-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    background: rgba(176, 128, 66, 0.1);
    border: 2px solid var(--primary-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    box-shadow: 0 4px 15px rgba(176, 128, 66, 0.3);
}
.auth-logo-icon .material-symbols-outlined { font-size: 28px !important; }
.auth-heading {
    margin-bottom: auto;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.auth-heading h1 {
    font-size: 24px;
    font-weight: 800;
    color: var(--text-primary);
    margin: 0;
    letter-spacing: -0.5px;
}
.auth-heading p {
    color: var(--text-secondary);
    font-size: 14px;
    margin: 6px 0 0;
    font-weight: 500;
}
.auth-divider {
    width: 40px;
    height: 3px;
    background: var(--primary-color);
    border-radius: 2px;
    margin: 12px auto 0;
}
.auth-left-footer {
    font-size: 13px;
    color: var(--text-secondary);
    font-weight: 500;
    width: 100%;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
}
.auth-left-footer p {
    margin-bottom: 6px;
}
.auth-switch-btn {
    color: var(--primary-color);
    font-weight: 800;
    text-decoration: underline;
    font-size: 16px;
    display: inline-block;
    transition: opacity 0.2s;
}
.auth-switch-btn:hover { text-decoration: underline; opacity: 0.8; }

/* Responzivita pre tablety a mobilné zariadenia */
@media (max-width: 768px) {
    .auth-card {
        flex-direction: column;
        max-width: 420px;
        padding: 30px 24px;
        gap: 24px;
    }
    .auth-left {
        border-right: none;
        padding-right: 0;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
        min-height: auto;
    }
}

/* Form inputs & interactive styles */
.auth-field {
    margin-bottom: 16px;
}
.auth-field label {
    display: block;
    margin-bottom: 7px;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* --- Floating Labels --- */
.floating-group .auth-field label {
    display: none; /* Hide default label in floating group */
}
.floating-group .auth-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.floating-group .floating-label {
    position: absolute;
    left: 40px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    font-weight: 600;
    color: var(--text-secondary);
    pointer-events: none;
    transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
    background: var(--bg-color);
    padding: 0 4px;
    margin: 0;
    text-transform: none;
    letter-spacing: normal;
    border-radius: 4px;
}
.floating-group input {
    width: 100%;
    height: 50px; /* Trochu vyssie pre floating label */
    padding: 0 42px 0 38px;
    border-radius: 12px;
    border: 1.5px solid var(--border-color);
    background: var(--bg-color);
    color: var(--text-primary);
    font-size: 14px;
    font-weight: 600;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
    font-family: 'Outfit', sans-serif;
}
.floating-group input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(176, 128, 66, 0.1);
}
.floating-group input:focus ~ .floating-label,
.floating-group input:not(:placeholder-shown) ~ .floating-label,
.floating-group input:-webkit-autofill ~ .floating-label {
    top: 0;
    font-size: 11px;
    font-weight: 800;
    color: var(--primary-color);
}
.floating-group input:not(:focus):not(:placeholder-shown) ~ .floating-label,
.floating-group input:not(:focus):-webkit-autofill ~ .floating-label {
    color: var(--text-secondary); /* Ak je vyplnené ale nie focusnuté */
}

/* Fix pre autofill background v prehliadačoch (Webkit) */
.floating-group input:-webkit-autofill,
.floating-group input:-webkit-autofill:hover,
.floating-group input:-webkit-autofill:focus,
.floating-group input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px var(--bg-color) inset !important;
    -webkit-text-fill-color: var(--text-primary) !important;
    transition: background-color 5000s ease-in-out 0s;
}

.auth-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.auth-input-icon {
    position: absolute;
    left: 12px;
    color: var(--text-secondary);
    font-size: 18px !important;
    pointer-events: none;
    line-height: 1;
    z-index: 2;
}

/* Ensure password input height matches */
.auth-input-wrap input:not(.floating-group input) {
    width: 100%;
    height: 46px;
    padding: 0 42px 0 38px;
    border-radius: 10px;
    border: 1.5px solid var(--border-color);
    background: var(--bg-color);
    color: var(--text-primary);
    font-size: 14px;
    font-weight: 500;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
    font-family: 'Outfit', sans-serif;
}
.auth-input-wrap input:not(.floating-group input):focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(176, 128, 66, 0.1);
}

.auth-eye-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    padding: 4px;
    border-radius: 6px;
    transition: color 0.2s;
    z-index: 10;
}
.auth-eye-btn:hover { color: var(--primary-color); }
.auth-eye-btn .material-symbols-outlined { font-size: 17px !important; }

/* Skrytie natívneho tlačidla Edge pre odhalenie hesla */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
    display: none;
}

.auth-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 4px 0 20px;
}
.auth-row .auth-check-label {
    margin-bottom: 0;
    align-items: center;
}
.auth-check-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    color: var(--text-secondary);
    cursor: pointer;
    font-weight: 500;
    margin-bottom: 20px;
    line-height: 1.4;
}
.auth-check-label input[type="checkbox"] {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    background: var(--bg-color);
    cursor: pointer;
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
    margin-top: 1px;
}
.auth-check-label input[type="checkbox"]:hover {
    border-color: var(--primary-color);
}
.auth-check-label input[type="checkbox"]:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E");
    background-size: 11px;
    background-repeat: no-repeat;
    background-position: center;
}
.auth-forgot {
    font-size: 13px;
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}
.auth-forgot:hover { opacity: 0.75; }
.auth-submit {
    width: 100%;
    height: 46px;
    border: none;
    border-radius: 12px;
    background: var(--primary-color);
    color: white;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: opacity 0.2s, transform 0.2s;
    font-family: 'Outfit', sans-serif;
    box-shadow: 0 4px 14px rgba(176, 128, 66, 0.3);
}
.auth-submit:hover { opacity: 0.9; transform: translateY(-1px); }

.auth-error {
    background: rgba(220, 50, 47, 0.08);
    border: 1px solid rgba(220, 50, 47, 0.3);
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 20px;
    color: #dc322f;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.auth-success {
    background: rgba(34, 180, 120, 0.08);
    border: 1px solid rgba(34, 180, 120, 0.3);
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 20px;
    color: #22b478;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.auth-hint {
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 5px;
    padding-left: 2px;
    opacity: 0.7;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Custom premium tooltips for Verified Sellers */
.verified-tooltip-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: help;
}

.verified-tooltip {
    visibility: hidden;
    position: absolute;
    bottom: 135%;
    left: 50%;
    transform: translateX(-50%);
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    text-align: center;
    padding: 10px 14px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    z-index: 999;
    width: 260px;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.2s, transform 0.2s;
    pointer-events: none;
}

.verified-tooltip-wrap:hover .verified-tooltip {
    visibility: visible;
    opacity: 1;
    transform: translateX(-50%) translateY(-5px);
}

.verified-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -6px;
    border-width: 6px;
    border-style: solid;
    border-color: var(--border-color) transparent transparent transparent;
}

/* Ad Detail Typography Styles */
.ad-detail-title {
    font-size: 16px;
    font-weight: 800;
    margin: 0;
    color: var(--text-primary);
    line-height: 1.2;
}

.ad-detail-price {
    font-size: 22px;
    font-weight: 850;
    color: var(--primary-color);
    letter-spacing: -0.5px;
    line-height: 1;
}

@media (max-width: 768px) {
    .ad-detail-title {
        font-size: 15px;
        font-weight: 700;
    }
    .ad-detail-price {
        font-size: 18px;
        font-weight: 800;
    }
}

/* Ad Detail Request Badges (Symmetrical Green Pill Badges) */
.request-status-badge {
    background: rgba(76, 209, 55, 0.08);
    border: 1px solid rgba(76, 209, 55, 0.2);
    border-radius: 8px;
    padding: 4px 8px;
    color: #2ecc71;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    box-sizing: border-box;
}

/* Zviditelnit Button in Dashboard */
[data-theme='light'] .btn-zviditelnit-ad { border: 1px solid rgba(255, 111, 0, 0.3) !important; background: rgba(255, 111, 0, 0.08) !important; color: #ff6f00 !important; }
[data-theme='dark'] .btn-zviditelnit-ad { border: 1px solid rgba(168, 85, 247, 0.3) !important; background: rgba(168, 85, 247, 0.08) !important; color: #a855f7 !important; }
.btn-zviditelnit-ad:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

/* Mobile Responsive Overrides for Inline Grids */
@media (max-width: 768px) {
    [style*="grid-template-columns: 1fr 1fr"],
    [style*="grid-template-columns: 1.1fr 0.9fr"],
    [style*="grid-template-columns: 1fr 1.2fr"],
    [style*="grid-template-columns: 1.2fr 1fr"],
    [style*="grid-template-columns: 1.25fr 0.75fr"] {
        grid-template-columns: 1fr !important;
    }
    /* Keep small grids (CVC/Exspiracia with small gap) side-by-side */
    [style*="grid-template-columns: 1fr 1fr"][style*="gap: 10px"],
    [style*="grid-template-columns: 1fr 1fr"][style*="gap: 12px"] {
        grid-template-columns: 1fr 1fr !important;
    }

    /* Ad Detail Header Flex Reordering */
    .ad-detail-header-flex {
        align-items: flex-start !important;
    }
    .ad-detail-star-btn {
        order: -1 !important;
        margin-right: 5px;
    }
    .ad-detail-badge {
        order: 0 !important;
    }
    .ad-detail-title {
        order: 1 !important;
        width: 100% !important;
        margin-top: 5px !important;
    }

    /* Footer Eco Badge Overlap Fix & Centering */
    .eco-badge {
        flex-direction: row !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        margin-bottom: 0 !important;
        padding: 12px 16px !important;
        align-items: center !important;
    }
    .eco-badge-text {
        order: 1 !important;
        width: 100% !important;
        text-align: center !important;
        margin-bottom: 2px !important;
    }
    .eco-badge-icon {
        order: 2 !important;
    }
    .eco-badge-link {
        order: 3 !important;
    }

    /* Force Breadcrumb Title to New Line on Mobile */
    .breadcrumb-title {
        flex-basis: 100% !important;
        margin-top: 4px;
        line-height: 1.4;
    }
}

/* Close button animation */
.auth-close-btn {
    position: absolute; 
    right: 18px; 
    top: 18px; 
    background: none; 
    border: none; 
    color: var(--text-secondary); 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    padding: 5px; 
    border-radius: 50%; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.auth-close-btn:hover {
    background: var(--accent-bg);
    color: var(--primary-color);
    transform: rotate(90deg) scale(1.15);
}
</style>
<div id="auth-modal" style="display: none; position: fixed; z-index: 10002; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
        <div class="auth-card" style="position: relative; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            <!-- Tlačidlo Zavrieť -->
            <button onclick="closeAuthModal()" class="auth-close-btn" title="<?php echo htmlspecialchars(t('Zavrieť')); ?>">
                <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 20px;">close</span>
            </button>

            <!-- PRIHLASOVACIA ČASŤ MODALU -->
            <div id="modal-login-view" style="display: flex; width: 100%; gap: 40px; flex-direction: inherit;">
                <div class="auth-left">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">
                            <span class="material-symbols-outlined notranslate" translate="no">login</span>
                        </div>
                    </div>
                    <div class="auth-heading">
                        <h1><?php echo t('Vitajte späť'); ?></h1>
                        <p><?php echo t('Prihláste sa do svojho účtu'); ?></p>
                        <div class="auth-divider"></div>
                    </div>
                    <div class="auth-left-footer">
                        <p><?php echo t('Ešte nemáte účet?'); ?></p>
                        <button type="button" onclick="switchAuthView('register')" class="auth-switch-btn" style="background:none; border:none; padding:0; cursor:pointer; font-size:inherit; font-family:inherit;"><?php echo t('Zaregistrujte sa'); ?></button>
                    </div>
                </div>
                <div class="auth-right">
            <?php if ($__modal_auth_error && ($__modal_auth_view === 'login' || $__modal_auth_view === null)): ?>
                <div class="auth-error" style="background: #fde2e2; color: #d32f2f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #ffbaba;">
                    <?php echo $__modal_auth_error; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['auth_success'])): ?>
                <div class="auth-success" style="background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #c8e6c9;">
                    <?php echo $_SESSION['auth_success']; unset($_SESSION['auth_success']); ?>
                </div>
            <?php endif; ?>  
                
<style>
input[type="radio"][name="role"]:checked + .role-card {
    border-color: #d1b06b !important;
    background: rgba(209, 176, 107, 0.05) !important;
}
input[type="radio"][name="role"]:checked + .role-card .role-check {
    background: #d1b06b !important;
    border-color: #d1b06b !important;
}
input[type="radio"][name="role"]:checked + .role-card .role-check span {
    opacity: 1 !important;
    color: white !important;
}
input[type="radio"][name="role"]:checked + .role-card > span:not(.role-check) {
    color: #d1b06b !important;
}
</style>

<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        <?php if ($__modal_auth_view): ?>
                        setTimeout(() => { openAuthModal('<?php echo $__modal_auth_view; ?>'); }, 500);
                        <?php endif; ?>
                    });
                </script>

                    <form action="<?php echo $prefix; ?>users/login.php" method="POST">
                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">alternate_email</span>
                                <input type="email" name="email" placeholder=" " value="<?php echo htmlspecialchars($_COOKIE['remembered_email'] ?? ''); ?>" required>
                                <label class="floating-label"><?php echo t('E-mailová adresa'); ?></label>
                            </div>
                        </div>
                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">lock</span>
                                <input type="password" name="password" id="modal-login-password" placeholder=" " required>
                                <label class="floating-label"><?php echo t('Heslo'); ?></label>
                                <button type="button" class="auth-eye-btn" onclick="togglePwd('modal-login-password', this)" title="<?php echo htmlspecialchars(t('Zobraziť heslo')); ?>">
                                    <span class="material-symbols-outlined notranslate" translate="no">visibility_off</span>
                                </button>
                            </div>
                        </div>
                        <div class="auth-row">
                            <label class="auth-check-label">
                                <input type="checkbox" name="remember" <?php echo isset($_COOKIE['remembered_email']) ? 'checked' : ''; ?>> <?php echo t('Zapamätať si ma'); ?>
                            </label>
                            <a href="#" onclick="switchAuthView('forgot'); return false;" class="auth-forgot"><?php echo t('Zabudnuté heslo?'); ?></a>
                        </div>
                        <button type="submit" class="auth-submit"><?php echo t('Prihlásiť sa'); ?></button>
                    </form>
                </div>
            </div>

            <!-- REGISTRAČNÁ ČASŤ MODALU -->
            <div id="modal-register-view" style="display: none; width: 100%; gap: 40px; flex-direction: inherit;">
                <div class="auth-left">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">
                            <span class="material-symbols-outlined notranslate" translate="no">person_add</span>
                        </div>
                    </div>
                    <div class="auth-heading">
                        <h1><?php echo t('Registrácia'); ?></h1>
                        <p><?php echo t('Vytvorte si účet a spravujte svoje termíny jednoducho a rýchlo'); ?></p>
                        <div class="auth-divider"></div>
                    </div>
                    <div class="auth-left-footer">
                        <p><?php echo t('Už máte účet?'); ?></p>
                        <button type="button" onclick="switchAuthView('login')" class="auth-switch-btn" style="background:none; border:none; padding:0; cursor:pointer; font-size:inherit; font-family:inherit;"><?php echo t('Prihláste sa'); ?></button>
                    </div>
                </div>
                <div class="auth-right">
                    <?php if ($__modal_auth_error && $__modal_auth_view === 'register'): ?>
                        <div class="auth-error" style="background: #fde2e2; color: #d32f2f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #ffbaba;">
                            <?php echo $__modal_auth_error; ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo $prefix; ?>users/register.php" method="POST" id="modal-register-form">
                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">person</span>
                                <input type="text" name="fullname" placeholder=" " required autocomplete="name">
                                <label class="floating-label"><?php echo t('Celé meno'); ?></label>
                            </div>
                        </div>
                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">alternate_email</span>
                                <input type="email" name="email" placeholder=" " required autocomplete="username">
                                <label class="floating-label"><?php echo t('E-mailová adresa'); ?></label>
                            </div>
                        </div>
                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">lock</span>
                                <input type="password" name="password" id="modal-reg-password" placeholder=" " required minlength="8" autocomplete="new-password">
                                <label class="floating-label"><?php echo t('Heslo'); ?></label>
                                <button type="button" class="auth-eye-btn" onclick="togglePwd('modal-reg-password', this)" title="<?php echo htmlspecialchars(t('Zobraziť heslo')); ?>">
                                    <span class="material-symbols-outlined notranslate" translate="no">visibility_off</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="auth-promo-toggle" style="margin-bottom: 12px; text-align: left;">
                            <button type="button" onclick="document.getElementById('modal-promo-code-field').style.display='block'; this.style.display='none';" style="background: none; border: none; padding: 0; cursor: pointer; color: var(--accent); font-weight: 600; font-size: 13px; text-decoration: none; display: <?php echo (isset($_GET['ref']) || isset($_COOKIE['ref_code'])) ? 'none' : 'inline-flex'; ?>; align-items: center; gap: 6px;">
                                <span class="material-symbols-outlined notranslate" style="font-size: 16px;">add_circle</span>
                                <?php echo t('Mám odporúčací / promo kód'); ?>
                            </button>
                        </div>

                        <div class="auth-field floating-group" id="modal-promo-code-field" style="display: <?php echo (isset($_GET['ref']) || isset($_COOKIE['ref_code'])) ? 'block' : 'none'; ?>;">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">redeem</span>
                                <input type="text" name="ref_code" value="<?php echo htmlspecialchars($_GET['ref'] ?? $_COOKIE['ref_code'] ?? ''); ?>" placeholder=" ">
                                <label class="floating-label"><?php echo t('Promo kód (voliteľné)'); ?></label>
                            </div>
                        </div>
                        <label class="auth-check-label" for="modal-agree-rules" style="align-items: flex-start; margin-bottom: 12px;">
                            <input type="checkbox" id="modal-agree-rules" required style="margin-top: 2px;">
                            <span><?php echo t('Súhlasím s'); ?> <a href="podmienky-pouzivania.php" target="_blank" style="color: var(--primary-color);"><?php echo t('obchodnými podmienkami'); ?></a> <?php echo t('a'); ?> <a href="ochrana-sukromia.php" target="_blank" style="color: var(--primary-color);"><?php echo t('spracovaním osobných údajov'); ?></a>.</span>
                        </label>

                        <!-- Invisible Turnstile widget (NEZOBRAZUJE SA - overuje na pozadí) -->
                        <div class="cf-turnstile"
                             id="modal-cf-turnstile-widget"
                             data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>"
                             data-size="invisible"
                             data-callback="onModalTurnstileVerified"
                             data-error-callback="onModalTurnstileError"
                             data-theme="auto"
                             style="display: none;">
                        </div>

                        <!-- Vlastný prémiový security badge s Material Symbols ikonou -->
                        <div id="modal-security-badge" style="
                            width: 100%;
                            box-sizing: border-box;
                            height: 52px;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            padding: 0 14px;
                            border-radius: 10px;
                            border: 1px solid var(--border-color);
                            background: var(--bg-secondary);
                            margin-bottom: 12px;
                            transition: border-color 0.35s ease, background 0.35s ease;
                        ">
                            <!-- Ikona stavu (Material Symbols) -->
                            <span id="modal-badge-icon" class="material-symbols-outlined notranslate" translate="no" style="
                                font-size: 22px;
                                color: var(--text-secondary);
                                transition: color 0.35s ease;
                                font-variation-settings: 'FILL' 0, 'wght' 400;
                                flex-shrink: 0;
                            ">shield</span>

                            <!-- Text -->
                            <div style="flex: 1; min-width: 0; text-align: left;">
                                <div id="modal-badge-title" style="
                                    font-size: 12px;
                                    font-weight: 700;
                                    color: var(--text-primary);
                                    transition: color 0.35s ease;
                                    line-height: 1.3;
                                "><?php echo t('Overujem bezpečnosť...'); ?></div>
                                <div style="font-size: 10px; color: var(--text-secondary); font-weight: 500; margin-top: 1px;">
                                    <?php echo t('Cloudflare Turnstile ochrana'); ?>
                                </div>
                            </div>

                            <!-- Pravá strana: spinner / checkmark -->
                            <div id="modal-badge-status" style="flex-shrink: 0;">
                                <span id="modal-badge-spinner" class="material-symbols-outlined notranslate" translate="no" style="
                                    font-size: 18px;
                                    color: var(--text-secondary);
                                    animation: modal-badge-spin 1.2s linear infinite;
                                    display: inline-block;
                                ">progress_activity</span>
                            </div>
                        </div>

                        <style>
                            @keyframes modal-badge-spin {
                                from { transform: rotate(0deg); }
                                to   { transform: rotate(360deg); }
                            }
                            #modal-security-badge.verified {
                                border-color: #27ae60;
                                background: rgba(39, 174, 96, 0.07);
                            }
                            #modal-security-badge.error-state {
                                border-color: #e74c3c;
                                background: rgba(231, 76, 60, 0.07);
                            }
                        </style>

                        <!-- Honeypot pasca pre spamových robotov -->
                        <div style="display: none !important;">
                            <label><?php echo t('Nevyplňujte toto pole, ak ste človek:'); ?></label>
                            <input type="text" name="hp_check_field" value="" tabindex="-1" autocomplete="off">
                        </div>

                        <button type="submit" class="auth-submit"><?php echo t('Zaregistrovať sa'); ?></button>
                    </form>


                </div>
            </div>

            <!-- OVEROVACIA ČASŤ MODALU -->
            <div id="modal-verify-view" style="display: none; width: 100%; gap: 40px; flex-direction: inherit;">
                <div class="auth-left">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">
                            <span class="material-symbols-outlined notranslate" translate="no">verified_user</span>
                        </div>
                    </div>
                    <div class="auth-heading">
                        <h1><?php echo t('Overenie'); ?></h1>
                        <p><?php echo t('Zadajte 6-miestny kód, ktorý sme vám poslali na e-mail.'); ?></p>
                        <div class="auth-divider"></div>
                    </div>
                    <div class="auth-left-footer">
                        <p><?php echo t('Zadali ste zlý e-mail?'); ?></p>
                        <button type="button" onclick="switchAuthView('register')" class="auth-switch-btn" style="background:none; border:none; padding:0; cursor:pointer; font-size:inherit; font-family:inherit;"><?php echo t('Zaregistrujte sa znova'); ?></button>
                    </div>
                </div>
                <div class="auth-right">
                    <?php if ($__modal_auth_error): ?>
                        <div class="auth-error" style="background: #fde2e2; color: #d32f2f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #ffbaba;">
                            <?php echo $__modal_auth_error; ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo $prefix; ?>users/verify_code.php" method="POST" id="modal-verify-form">
                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">mail</span>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['verify_email'] ?? $_GET['email'] ?? ''); ?>" placeholder=" " readonly required style="background: rgba(255,255,255,0.05); color: #888;">
                                <label class="floating-label"><?php echo t('E-mail'); ?></label>
                            </div>
                        </div>

                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">password</span>
                                <input type="text" name="code" placeholder=" " required maxlength="6" style="letter-spacing: 4px; font-weight: bold; text-align: center;">
                                <label class="floating-label"><?php echo t('6-miestny kód'); ?></label>
                            </div>
                        </div>

                        <button type="submit" class="auth-submit"><?php echo t('Overiť a pokračovať'); ?></button>
                    </form>
                </div>
            </div>

            <!-- 2FA OVERENIE -->
            <div id="modal-2fa-verify" style="display: none; width: 100%; gap: 40px; flex-direction: inherit;">
                <div class="auth-left">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">
                            <span class="material-symbols-outlined notranslate" translate="no">verified_user</span>
                        </div>
                    </div>
                    <div class="auth-heading">
                        <h1><?php echo t('Bezpečnostné overenie'); ?></h1>
                        <p><?php echo t('Zadajte kód zaslaný na váš e-mail'); ?></p>
                        <div class="auth-divider"></div>
                    </div>
                </div>
                <div class="auth-right">
                    <?php if ($__modal_auth_error && $__modal_auth_view === 'verify_2fa'): ?>
                        <div class="auth-error" style="background: #fde2e2; color: #d32f2f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #ffbaba;">
                            <?php echo $__modal_auth_error; ?>
                        </div>
                    <?php endif; ?>
                    <form action="users/verify_2fa.php" method="POST" class="auth-form" autocomplete="off">
                        <div class="auth-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">pin</span>
                                <input type="text" name="code" placeholder="<?php echo t('6-miestny kód'); ?>" required style="letter-spacing: 5px; font-size: 20px; text-align: center; font-weight: bold;" maxlength="6" pattern="\d{6}">
                            </div>
                        </div>
                        <button type="submit" class="auth-submit"><?php echo t('Prihlásiť sa'); ?></button>
                        <p style="text-align: center; margin-top: 15px; font-size: 14px; color: var(--text-muted); cursor: pointer;" onclick="switchAuthView('login')"><?php echo t('Zrušiť'); ?></p>
                    </form>
                </div>
            </div>

            <!-- 3FA SECURITY QUESTION VIEW -->
            <div id="modal-3fa-verify" style="display: none; width: 100%; gap: 40px; flex-direction: inherit;">
                <div class="auth-left">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">
                            <span class="material-symbols-outlined notranslate" translate="no">psychology</span>
                        </div>
                    </div>
                    <div class="auth-heading">
                        <h1><?php echo t('Bezpečnostná otázka'); ?></h1>
                        <p><?php echo t('Trojstupňové overenie (3FA)'); ?></p>
                        <div class="auth-divider"></div>
                    </div>
                </div>
                <div class="auth-right">
                    <?php if ($__modal_auth_error && $__modal_auth_view === 'verify_3fa'): ?>
                        <div class="auth-error" style="background: #fde2e2; color: #d32f2f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #ffbaba;">
                            <?php echo $__modal_auth_error; ?>
                        </div>
                    <?php endif; ?>
                    <div style="background: rgba(176, 128, 66, 0.08); border: 1px solid rgba(176, 128, 66, 0.25); border-radius: 12px; padding: 16px; margin-bottom: 20px; text-align: center;">
                        <span style="font-size: 11.5px; font-weight: 700; color: var(--primary-color); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;"><?php echo t('Otázka pre overenie totožnosti'); ?></span>
                        <strong style="font-size: 15px; color: var(--text-primary); display: block;">
                            <?php echo htmlspecialchars($_SESSION['temp_login_question_text'] ?? t('Vaša bezpečnostná otázka')); ?>
                        </strong>
                    </div>
                    <form action="<?php echo $prefix; ?>users/verify_3fa.php" method="POST" class="auth-form" autocomplete="off">
                        <div class="auth-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">key</span>
                                <input type="text" name="answer" placeholder="<?php echo t('Vaša odpoveď...'); ?>" required style="font-size: 15px; font-weight: 600;">
                            </div>
                        </div>
                        <button type="submit" class="auth-submit"><?php echo t('Potvrdiť a prihlásiť sa'); ?></button>
                        <p style="text-align: center; margin-top: 15px; font-size: 14px; color: var(--text-muted); cursor: pointer;" onclick="switchAuthView('login')"><?php echo t('Zrušiť'); ?></p>
                    </form>
                </div>
            </div>

            <!-- FORGOT PASSWORD VIEW -->
            <div id="modal-forgot-view" style="display: none; width: 100%; gap: 40px; flex-direction: inherit;">
                <div class="auth-left">
                    <div class="auth-logo">
                        <div class="auth-logo-icon">
                            <span class="material-symbols-outlined notranslate" translate="no">password</span>
                        </div>
                    </div>
                    <div class="auth-heading">
                        <h1><?php echo t('Zabudnuté heslo?'); ?></h1>
                        <p><?php echo t('Zadajte e-mail pre obnovenie'); ?></p>
                        <div class="auth-divider"></div>
                    </div>
                </div>
                <div class="auth-right">
                    <form action="<?php echo $prefix; ?>api/auth.php" method="POST" class="auth-form" autocomplete="off" onsubmit="handleForgot(event, this)">
                        <input type="hidden" name="action" value="forgot_password">
                        <div class="auth-field floating-group">
                            <div class="auth-input-wrap">
                                <span class="material-symbols-outlined auth-input-icon notranslate" translate="no">alternate_email</span>
                                <input type="email" name="email" placeholder=" " required>
                                <label class="floating-label"><?php echo t('E-mailová adresa'); ?></label>
                            </div>
                        </div>
                        <button type="submit" class="auth-submit"><?php echo t('Odoslať odkaz'); ?></button>
                        <p style="text-align: center; margin-top: 15px; font-size: 14px; color: var(--text-muted); cursor: pointer;" onclick="switchAuthView('login')"><?php echo t('Späť na prihlásenie'); ?></p>
                        <div id="forgot-msg" style="margin-top:15px;text-align:center;font-size:14px;display:none;"></div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
    function openAuthModal(view = 'login') {
        const modal = document.getElementById('auth-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            switchAuthView(view);
        }
    }
    function closeAuthModal() {
        const modal = document.getElementById('auth-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    // ─── Invisible Turnstile pre Modal ───────────────────────────
    var modalTsVerified = false;

    window.onModalTurnstileVerified = function(token) {
        modalTsVerified = true;
        var badge  = document.getElementById('modal-security-badge');
        var icon   = document.getElementById('modal-badge-icon');
        var title  = document.getElementById('modal-badge-title');
        var status = document.getElementById('modal-badge-status');

        if (badge) badge.classList.add('verified');
        if (icon) {
            icon.textContent = 'verified_user';
            icon.style.color = '#27ae60';
            icon.style.fontVariationSettings = "'FILL' 1, 'wght' 500";
        }
        if (title) title.textContent = '<?php echo t('Overenie úspešné'); ?>';
        if (status) status.innerHTML  = '<span class="material-symbols-outlined notranslate" translate="no" style="font-size:18px;color:#27ae60;font-variation-settings:\'FILL\' 1">check_circle</span>';
    };

    window.onModalTurnstileError = function() {
        var badge  = document.getElementById('modal-security-badge');
        var icon   = document.getElementById('modal-badge-icon');
        var title  = document.getElementById('modal-badge-title');
        var status = document.getElementById('modal-badge-status');

        if (badge) badge.classList.add('error-state');
        if (icon) {
            icon.textContent = 'gpp_bad';
            icon.style.color = '#e74c3c';
        }
        if (title) title.textContent = '<?php echo t('Overenie zlyhalo. Skúste znova.'); ?>';
        if (status) status.innerHTML  = '<span class="material-symbols-outlined notranslate" translate="no" style="font-size:18px;color:#e74c3c;font-variation-settings:\'FILL\' 1">cancel</span>';
    };

    function switchAuthView(view) {
        const loginView = document.getElementById('modal-login-view');
        const registerView = document.getElementById('modal-register-view');
        const verifyView = document.getElementById('modal-verify-view');
        const verify2faView = document.getElementById('modal-2fa-verify');
        const verify3faView = document.getElementById('modal-3fa-verify');
        const forgotView = document.getElementById('modal-forgot-view');

        if(loginView) loginView.style.display = 'none';
        if(registerView) registerView.style.display = 'none';
        if(verifyView) verifyView.style.display = 'none';
        if(verify2faView) verify2faView.style.display = 'none';
        if(verify3faView) verify3faView.style.display = 'none';
        if(forgotView) forgotView.style.display = 'none';

        if (view === 'verify' && verifyView) {
            verifyView.style.display = 'flex';
        } else if (view === 'verify_2fa' && verify2faView) {
            verify2faView.style.display = 'flex';
        } else if (view === 'verify_3fa' && verify3faView) {
            verify3faView.style.display = 'flex';
        } else if (view === 'forgot' && forgotView) {
            forgotView.style.display = 'flex';
        } else if (view === 'login' && loginView) {
            loginView.style.display = 'flex';
        } else if (view === 'register' && registerView) {
            registerView.style.display = 'flex';
            
            // Spustíme neviditeľný Turnstile pre modal po zobrazení registrácie
            setTimeout(function() {
                if (typeof turnstile !== 'undefined' && !modalTsVerified) {
                    turnstile.execute('#modal-cf-turnstile-widget');
                }
            }, 300);
        }
    }

    // Interceptujeme submit modal formulára
    document.addEventListener('DOMContentLoaded', function() {
        var modalForm = document.getElementById('modal-register-form');
        if (modalForm) {
            modalForm.addEventListener('submit', function(e) {
                if (modalTsVerified) return; // token už máme, normálne submit
                e.preventDefault();

                if (typeof turnstile !== 'undefined') {
                    turnstile.execute('#modal-cf-turnstile-widget', {
                        callback: function(token) {
                            window.onModalTurnstileVerified(token);
                            setTimeout(function() { modalForm.submit(); }, 500);
                        },
                        'error-callback': function() {
                            window.onModalTurnstileError();
                            setTimeout(function() { modalForm.submit(); }, 800);
                        }
                    });
                } else {
                    modalForm.submit();
                }
            });
        }
    });
    
    function togglePwd(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('.material-symbols-outlined');
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility_off';
        }
    }
    
    function openEcoModal() {
        const modal = document.getElementById('eco-info-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    function closeEcoModal() {
        const modal = document.getElementById('eco-info-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    
    // Zatvorenie kliknutím mimo kartu modalu
    window.addEventListener('click', function(e) {
        const authModal = document.getElementById('auth-modal');
        if (e.target === authModal) {
            closeAuthModal();
        }
        const ecoModal = document.getElementById('eco-info-modal');
        if (e.target === ecoModal) {
            closeEcoModal();
        }
    });

    async function handleForgot(e, form) {
        e.preventDefault();
        const msgDiv = document.getElementById('forgot-msg');
        msgDiv.style.display = 'none';
        
        const fd = new FormData(form);
        try {
            let res = await fetch(form.action, { method: 'POST', body: fd });
            let data = await res.json();
            msgDiv.style.display = 'block';
            if (data.success) {
                msgDiv.style.color = '#27ae60';
                msgDiv.innerText = data.message || 'Na e-mail bol zaslaný odkaz na obnovenie hesla.';
                form.reset();
            } else {
                msgDiv.style.color = '#e74c3c';
                msgDiv.innerText = data.error || 'Nastala chyba.';
            }
        } catch(err) {
            msgDiv.style.display = 'block';
            msgDiv.style.color = '#e74c3c';
            msgDiv.innerText = 'Chyba siete.';
        }
    }
    </script>
<?php if (isset($_GET['verify_required']) || isset($_SESSION['verify_email'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => { openAuthModal('verify'); }, 500);
        });
    </script>
<?php endif; ?>
