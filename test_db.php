<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

$categoryImages = [
    'Vlasy' => 'cat_hair.png',
    'Holičstvo a Barber' => 'barber_shop.png',
    'Nechty' => 'nail_salon.png',
    'Starostlivosť o pleť' => 'cat_skincare.png',
    'Obočie a riasy' => 'cat_brows.png',
    'Masáž' => 'spa_massage.png',
    'Make-up' => 'cat_makeup.png',
    'Wellness a kúpele' => 'cat_wellness.png',
    'Vrkoče a dredy' => 'cat_braids.png',
    'Tetovanie' => 'cat_tattoo.png',
    'Lekárska estetika' => 'cat_medical.png',
    'Depilácia a epilácia' => 'cat_depilation.png',
    'Domáce služby' => 'cat_home.png',
    'Piercing' => 'cat_piercing.png',
    'Služby pre miláčikov' => 'cat_pets.png',
    'Zubné a ortodontické' => 'cat_dentistry.png',
    'Zdravie a kondícia' => 'cat_fitness.png',
    'Solárium a opaľovanie' => 'cat_solarium.png',
    'Joga a Pilates' => 'cat_yoga.png',
    'Fyzioterapia' => 'cat_physio.png',
    'Osobný tréneri' => 'cat_trainer.png',
    'Výživové poradenstvo' => 'cat_nutrition.png',
    'Svadobné služby' => 'cat_wedding.png',
    'Alternatívna medicína' => 'cat_altmedicine.png',
    'Psychológia a Terapia' => 'cat_psychology.png'
];

$total = 0;
foreach ($categoryImages as $category => $imageFile) {
    $imageUrl = 'assets/img/' . $imageFile;
    $stmt = $conn->prepare('UPDATE establishments SET image_url = ? WHERE category = ?');
    if ($stmt) {
        $stmt->bind_param('ss', $imageUrl, $category);
        $stmt->execute();
        $total += $stmt->affected_rows;
        $stmt->close();
    }
}
echo 'SUCCESS_UPDATED_TOTAL_' . $total;
?>
