<?php
// Zdieľaná pätička pre verejné stránky (index.php, prevadzky.php, inzercia.php).
// Vyžaduje, aby volajúca stránka už mala includnuté includes/branding.php (BRAND_NAME/BRAND_SITE)
// a definované openAuthModal()/openEcoModal() JS funkcie a .site-footer CSS.
if (!defined('BRAND_NAME')) {
    require_once __DIR__ . '/branding.php';
}
?>
<footer class="site-footer">
    <div class="footer-container">

        <div class="footer-top">
            <div class="footer-brand">
                <?php
                // BRAND_LOGO_TEXT je farebne ladený pre tmavé pozadie (sidebar) — pätička má svetlé
                // pozadie, preto tu má "rezer"/"volne" časť vlastnú farbu naviazanú na motív (svetlý/tmavý režim).
                $footer_wordmark = (BRAND_NAME === 'Rezervos')
                    ? '<span style="color:var(--text-primary);font-weight:800;">rezer</span><span style="color:' . BRAND_PRIMARY . ';font-weight:800;">vos</span>'
                    : '<span style="color:var(--text-primary);font-weight:800;">volne</span><span style="color:' . BRAND_PRIMARY . ';font-weight:800;">kreslo</span>';
                ?>
                <div class="footer-brand-name">
                    <img src="<?= BRAND_LOGO ?>" alt="<?= BRAND_NAME ?>" class="footer-brand-icon footer-brand-icon-light">
                    <img src="<?= defined('BRAND_LOGO_DARK') ? BRAND_LOGO_DARK : BRAND_LOGO ?>" alt="<?= BRAND_NAME ?>" class="footer-brand-icon footer-brand-icon-dark">
                    <span><?= $footer_wordmark ?></span>
                </div>
                <p class="footer-brand-desc"><?= t('Nájdite si svoj termín a rezervujte si ho online kedykoľvek a kdekoľvek.') ?></p>
                <div class="footer-socials">
                    <a href="https://www.facebook.com/volnekreslo" target="_blank" title="Facebook"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2.04c-5.5 0-10 4.48-10 10.02 0 5 3.66 9.15 8.44 9.9v-7H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.89 3.78-3.89 1.09 0 2.23.19 2.23.19v2.47h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.9h-2.33v7a10 10 0 0 0 8.44-9.9c0-5.54-4.5-10.02-10-10.02Z"/></svg></a>
                    <a href="https://www.instagram.com/volnekreslo" target="_blank" title="Instagram"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/></svg></a>
                    <a href="https://www.tiktok.com/@volnekreslo" target="_blank" title="TikTok"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.12-3.44-3.17-3.8-5.46-.4-2.51.33-5.18 1.93-7.1 1.54-1.83 3.96-2.91 6.36-2.73V8.8c-1.21-.18-2.45.14-3.51.78-1.29.74-2.22 1.98-2.51 3.42-.23 1.15-.05 2.37.52 3.39.58 1.05 1.59 1.83 2.75 2.14 1.26.33 2.64.13 3.73-.55 1.09-.69 1.83-1.87 2.01-3.15.06-.57.04-1.15.04-1.72V0l-.25.02z"/></svg></a>
                    <a href="https://www.youtube.com/@volnekreslo" target="_blank" title="YouTube"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M21.58 6.4c-.23-.86-.91-1.54-1.77-1.77C18.25 4.2 12 4.2 12 4.2s-6.25 0-7.81.43c-.86.23-1.54.91-1.77 1.77C2 7.97 2 12 2 12s0 4.03.42 5.6c.23.86.91 1.54 1.77 1.77 1.56.43 7.81.43 7.81.43s6.25 0 7.81-.43c.86-.23 1.54-.91 1.77-1.77.42-1.57.42-5.6.42-5.6s0-4.03-.42-5.6zM9.99 15.5v-7l6.33 3.5-6.33 3.5z"/></svg></a>
                </div>
            </div>

            <div class="footer-col">
                <h4><?= t('Platforma') ?></h4>
                <a href="#"><span class="material-symbols-outlined" style="color:#14b8a6;">chevron_right</span> <?= t('Kampane') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#14b8a6;">chevron_right</span> <?= t('Ambasádori') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#14b8a6;">chevron_right</span> <?= t('Pre Firmy') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#14b8a6;">chevron_right</span> <?= t('Akadémia') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#14b8a6;">chevron_right</span> <?= t('Creator Hub') ?></a>
            </div>

            <div class="footer-col">
                <h4><?= t('Nástroje') ?></h4>
                <a href="#"><span class="material-symbols-outlined" style="color:#0ea5e9;">mail</span> <?= t('Profi E-mail') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#0ea5e9;">inbox</span> <?= t('Webmail') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#0ea5e9;">dashboard</span> <?= t('Dashboard') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#0ea5e9;">bar_chart</span> <?= t('Štatistiky') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#0ea5e9;">share</span> <?= t('Affiliate') ?></a>
            </div>

            <div class="footer-col">
                <h4><?= t('Komunita') ?></h4>
                <a href="#"><span class="material-symbols-outlined" style="color:#f43f5e;">history_edu</span> <?= t('Changelog') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#f97316;">rss_feed</span> <?= t('RSS Kanál') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#f43f5e;">groups</span> <?= t('Fórum') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#f43f5e;">menu_book</span> <?= t('Blog') ?></a>
                <a href="kariera.php"><span class="material-symbols-outlined" style="color:#f43f5e;">work</span> <?= t('Kariéra') ?></a>
            </div>

            <div class="footer-col">
                <h4><?= t('Podpora') ?></h4>
                <a href="#"><span class="material-symbols-outlined" style="color:#a855f7;">support</span> <?= t('Centrum pomoci') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#a855f7;">shield</span> <?= t('Ochrana súkromia') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#a855f7;">cookie</span> <?= t('Nastavenia cookies') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#a855f7;">description</span> <?= t('Podmienky') ?></a>
                <a href="#"><span class="material-symbols-outlined" style="color:#a855f7;">mail</span> <?= t('Kontakt') ?></a>
            </div>
        </div>

        <div class="footer-badges">
            <div class="footer-eco">
                <span class="material-symbols-outlined">eco</span>
                <span class="footer-eco-text"><?= t('Znižujeme našu digitálnu uhlíkovú stopu.') ?></span>
                <span onclick="openEcoModal()" style="cursor:pointer;"><?= t('Zistiť viac') ?></span>
            </div>
            <div class="status-indicator" title="<?= htmlspecialchars(t('Servery sú online a všetky služby fungujú normálne')) ?>">
                <div class="dot"></div>
                <?= t('Všetky systémy bežia') ?>
            </div>
        </div>

        <div class="footer-stats">
            342 <?= t('salónov') ?> &bull; 12 458 <?= t('úspešných rezervácií') ?>
        </div>

        <div class="footer-info">
            <div>&copy; <?php echo date('Y'); ?> <?= BRAND_SITE ?> | <span id="footer-rotating-slogan" style="color:var(--primary-color);font-weight:700;transition:opacity 0.4s ease;"></span> | <?= t('verzia') ?> 1.1.0</div>
        </div>
    </div>
    <script>
    (function () {
        var slogans = <?php echo json_encode([
            t('Tvoj čas. Tvoja voľba.'), t('Všetko začína rezerváciou.'), t('Nájdite. Rezervujte. Vybavené.'),
            t('Rezervácie jednoducho.'), t('Váš čas má hodnotu.'), t('Keď čas patrí vám.'),
            t('Miesto pre váš čas.'), t('Váš termín. Vaša voľba.'), t('Služby na dosah.'),
            t('Nájdite si svoj termín.'), t('Váš svet služieb.'), t('Služby, ktoré si vás nájdu.'),
            t('Objav. Vyber. Rezervuj.'), t('Všetky služby. Jedno miesto.'), t('Všetky termíny na jednom mieste.'),
            t('Spájame ľudí so službami.'), t('Správna služba. Správny čas.'), t('Váš čas, naše riešenie.'),
            t('Jednoducho k tomu, čo potrebujete.'), t('Rezervuj si svoj čas.'), t('Čas na to, čo potrebujete.'),
            t('Váš termín je len začiatok.'), t('Od výberu po rezerváciu.'), t('Služby bez čakania.'),
            t('Nájdite čas na seba.'), t('Vyberte si. My rezervujeme.'), t('Jedno miesto. Tisíce možností.'),
            t('Tam, kde sa služby stretávajú s ľuďmi.'), t('Váš čas. Vaše miesto.'), t('Rezervujte život jednoduchšie.'),
        ], JSON_UNESCAPED_UNICODE); ?>;
        var el = document.getElementById('footer-rotating-slogan');
        if (!el) return;
        var i = Math.floor(Math.random() * slogans.length);
        el.textContent = '"' + slogans[i] + '"';
        setInterval(function () {
            el.style.opacity = '0';
            setTimeout(function () {
                i = (i + 1) % slogans.length;
                el.textContent = '"' + slogans[i] + '"';
                el.style.opacity = '1';
            }, 400);
        }, 4000);
    })();
    </script>
</footer>

<?php // Štýly: assets/css/site-footer.css — musí byť includnuté v <head> volajúcej stránky ?>
