<?php
// Dynamicky generovaný OG (social share) obrázok pre profil prevádzky — fotka prevádzky na pozadí
// + názov prevádzky + vodoznak loga Rezervos. Generuje sa cez GD (žiadne externé volania za behu —
// font aj logo sú lokálne súbory), výsledok sa cachuje na disk podľa updated_at prevádzky.
require_once '../config.php';

$FALLBACK_IMAGE = __DIR__ . '/../assets/img/ogimage.png';

function serve_fallback($path) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    readfile($path);
    exit;
}

$est_id = (int)($_GET['est_id'] ?? 0);
if (!$est_id) serve_fallback($FALLBACK_IMAGE);

$stmt = $pdo->prepare("SELECT e.id, e.name, e.category, e.banner_url, e.image_url, e.updated_at, u.avatar_path as avatar_url FROM establishments e LEFT JOIN users u ON e.user_id = u.id WHERE e.id = ?");
$stmt->execute([$est_id]);
$est = $stmt->fetch();

if (!$est) serve_fallback($FALLBACK_IMAGE);
if (!function_exists('imagecreatetruecolor')) serve_fallback($FALLBACK_IMAGE);

// Tematický obrázok podľa kategórie — použije sa, ak prevádzka nemá nahratú titulnú fotku ani profilovku
// (rovnaká logika ako get_establishment_image() v prevadzky.php, aby OG obrázok nebol nikdy prázdny)
function og_category_fallback_image($category, $name) {
    $cat = $category ?? ''; $name = $name ?? '';
    if (stripos($cat, 'Barber') !== false || stripos($cat, 'Holi') !== false || stripos($name, 'Barber') !== false) return 'barber_shop.png';
    if (stripos($cat, 'Necht') !== false || stripos($cat, 'Nails') !== false) return 'cat_nails.png';
    if (stripos($cat, 'Masáž') !== false || stripos($cat, 'Spa') !== false || stripos($cat, 'Wellness') !== false) return 'cat_wellness.png';
    if (stripos($cat, 'Pleť') !== false || stripos($cat, 'Kozmet') !== false || stripos($cat, 'Make-up') !== false) return 'cat_makeup.png';
    if (stripos($cat, 'Tetov') !== false || stripos($cat, 'Tattoo') !== false) return 'cat_tattoo.png';
    if (stripos($cat, 'Vrko') !== false || stripos($cat, 'Braids') !== false) return 'cat_braids.png';
    $catFiles = ['Kadernícke' => 'cat_hair.png', 'Nechtový' => 'cat_nails.png', 'Kozmetické' => 'cat_makeup.png', 'Masáže' => 'cat_wellness.png', 'Wellness' => 'cat_wellness.png', 'Tetovanie' => 'cat_tattoo.png', 'Depilácia' => 'cat_depilation.png', 'Obočie' => 'cat_brows.png', 'Fitness' => 'cat_fitness.png', 'Jóga' => 'cat_yoga.png', 'Solárium' => 'cat_solarium.png', 'Piercing' => 'cat_piercing.png', 'Zvieratá' => 'cat_pets.png'];
    foreach ($catFiles as $key => $file) {
        if (stripos($cat, $key) !== false) return $file;
    }
    return 'cat_hair.png';
}

$cache_dir = __DIR__ . '/../assets/og_cache';
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
$cache_file = $cache_dir . '/est_' . $est_id . '.png';

$source_mtime = !empty($est['updated_at']) ? strtotime($est['updated_at']) : 0;
if (file_exists($cache_file) && filemtime($cache_file) >= $source_mtime) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    readfile($cache_file);
    exit;
}

$W = 1200;
$H = 630;
$canvas = imagecreatetruecolor($W, $H);

// Základné pozadie (tmavomodrá, ak sa fotka nenačíta)
$navy = imagecolorallocate($canvas, 15, 23, 42);
imagefilledrectangle($canvas, 0, 0, $W, $H, $navy);

// Nájdeme obrázok prevádzky: titulná fotka > profilovka > legacy image_url > tematický obrázok podľa kategórie
$bgSource = null;
$candidates = [$est['banner_url'], $est['avatar_url'], $est['image_url'] ?? null];
$candidates[] = 'assets/img/' . og_category_fallback_image($est['category'] ?? '', $est['name'] ?? '');
foreach ($candidates as $candidate) {
    if (empty($candidate)) continue;
    if (str_starts_with($candidate, 'http')) { $bgSource = $candidate; break; }
    $local = __DIR__ . '/../' . ltrim($candidate, '/');
    if (file_exists($local)) { $bgSource = $local; break; }
}

$bgImg = null;
if ($bgSource) {
    $data = @file_get_contents($bgSource);
    if ($data !== false) $bgImg = @imagecreatefromstring($data);
}

if ($bgImg) {
    $sw = imagesx($bgImg);
    $sh = imagesy($bgImg);
    $dstRatio = $W / $H;
    $srcRatio = $sw / $sh;
    if ($srcRatio > $dstRatio) {
        $cropW = (int)($sh * $dstRatio);
        $cropH = $sh;
        $srcX = (int)(($sw - $cropW) / 2);
        $srcY = 0;
    } else {
        $cropW = $sw;
        $cropH = (int)($sw / $dstRatio);
        $srcX = 0;
        $srcY = (int)(($sh - $cropH) / 2);
    }
    imagecopyresampled($canvas, $bgImg, 0, 0, $srcX, $srcY, $W, $H, $cropW, $cropH);
    imagedestroy($bgImg);
}

// Tmavý gradient prekryv v spodnej tretine (čitateľnosť textu)
$gradStart = (int)($H * 0.45);
for ($y = $gradStart; $y < $H; $y++) {
    $factor = ($y - $gradStart) / ($H - $gradStart);
    $alpha = 127 - (int)($factor * 100); // 127=priehľadné, 27=takmer nepriehľadné
    $alpha = max(20, min(127, $alpha));
    $color = imagecolorallocatealpha($canvas, 5, 10, 20, $alpha);
    imageline($canvas, 0, $y, $W, $y, $color);
}

$fontExtraBold = __DIR__ . '/../assets/fonts/Outfit-ExtraBold.ttf';
$fontBold = __DIR__ . '/../assets/fonts/Outfit-Bold.ttf';
$white = imagecolorallocate($canvas, 255, 255, 255);
$gold = imagecolorallocate($canvas, 214, 168, 110);

// Názov prevádzky (skrátený a zalomený, ak je príliš dlhý)
$name = $est['name'] ?? BRAND_NAME;
$maxNameWidth = $W - 120;
$fontSize = 48;
if (file_exists($fontExtraBold)) {
    while ($fontSize > 30) {
        $box = imagettfbbox($fontSize, 0, $fontExtraBold, $name);
        $textW = abs($box[2] - $box[0]);
        if ($textW <= $maxNameWidth) break;
        $fontSize -= 2;
    }
    imagettftext($canvas, $fontSize, 0, 60, $H - 70, $white, $fontExtraBold, $name);
} else {
    imagestring($canvas, 5, 60, $H - 60, $name, $white);
}

// Vodoznak loga Rezervos — ikona + dvojfarebný nápis, vpravo dole
$logoPath = __DIR__ . '/../rezervoslogo.png';
$logo = @imagecreatefrompng($logoPath);
$wmSize = 46;
$wmY = $H - $wmSize - 34;
$wmTextSize = 22;

if (file_exists($fontBold)) {
    $box1 = imagettfbbox($wmTextSize, 0, $fontBold, 'rezer');
    $w1 = abs($box1[2] - $box1[0]);
    $box2 = imagettfbbox($wmTextSize, 0, $fontBold, 'vos');
    $w2 = abs($box2[2] - $box2[0]);
} else {
    $w1 = 60; $w2 = 36;
}

$logoDrawW = $logo ? $wmSize : 0;
$gap = $logo ? 10 : 0;
$totalW = $logoDrawW + $gap + $w1 + $w2;
$wmX = $W - $totalW - 50;

if ($logo) {
    imagealphablending($logo, true);
    imagesavealpha($logo, true);
    $lw = imagesx($logo);
    $lh = imagesy($logo);
    imagecopyresampled($canvas, $logo, $wmX, $wmY, 0, 0, $wmSize, $wmSize, $lw, $lh);
    imagedestroy($logo);
}

$textBaselineY = $wmY + $wmSize - 10;
if (file_exists($fontBold)) {
    imagettftext($canvas, $wmTextSize, 0, $wmX + $logoDrawW + $gap, $textBaselineY, $white, $fontBold, 'rezer');
    imagettftext($canvas, $wmTextSize, 0, $wmX + $logoDrawW + $gap + $w1, $textBaselineY, $gold, $fontBold, 'vos');
}

imagepng($canvas, $cache_file);
imagedestroy($canvas);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
readfile($cache_file);
