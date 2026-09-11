<?php
// branding.php – Rozpozná doménu a nastaví logo, názov, farby
// Includuj pred sidebar.php a dashboard-head.php

$_host = strtolower($_SERVER['HTTP_HOST'] ?? '');

if (str_contains($_host, 'rezervos')) {
    define('BRAND_NAME',      'Rezervos');
    define('BRAND_LOGO',      '/rezervoslogo.png');
    define('BRAND_LOGO_DARK', '/rezervoslogodark.png');
    define('BRAND_LOGO_TEXT', '<span style="color:#9ca3af;font-weight:800;">rezer</span><span style="color:#b08042;font-weight:800;">vos</span>');
    define('BRAND_PRIMARY',   '#b08042');
    define('BRAND_PRIMARY_H', '#966c35');
    define('BRAND_SITE',      'rezervos.eu');
} else {
    // VoľnéKreslo (default)
    define('BRAND_NAME',      'VoľnéKreslo');
    define('BRAND_LOGO',      '/volnekreslologo.png');
    define('BRAND_LOGO_DARK', '/volnekreslologo.png');
    define('BRAND_LOGO_TEXT', '<span style="color:#ffffff;">volne</span><span style="color:#b08042;">kreslo</span>');
    define('BRAND_PRIMARY',   '#b08042');
    define('BRAND_PRIMARY_H', '#966c35');
    define('BRAND_SITE',      'volnekreslo.sk');
}
