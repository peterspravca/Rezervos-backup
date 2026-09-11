<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset("utf8mb4");
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if ($query !== '') {
        $searchQuery = '%' . $query . '%';
        
        function removeAccents($str) {
            $search = explode(",","ç,æ,œ,á,ä,é,í,ó,ô,ú,à,è,ì,ò,ù,ë,ï,ö,ü,ÿ,â,ê,î,û,å,e,i,ø,u,š,ť,č,ž,ň,ý,ď,ľ,ĺ,ŕ,Ä,Č,Ď,É,Ě,Ë,Í,Ň,Ó,Ö,Ř,Š,Ť,Ú,Ů,Ü,Ý,Ž,Ľ,Ĺ,Ŕ,Á");
            $replace = explode(",","c,ae,oe,a,a,e,i,o,o,u,a,e,i,o,u,e,i,o,u,y,a,e,i,u,a,e,i,o,u,s,t,c,z,n,y,d,l,l,r,A,C,D,E,E,E,I,N,O,O,R,S,T,U,U,U,Y,Z,L,L,R,A");
            return str_ireplace($search, $replace, $str);
        }

        $queryNoAccents = mb_strtolower(removeAccents($query), 'UTF-8');

        // Všetkých 27 oficiálnych kategórií a ich ikony
        $allCategories = [
            'Last minute' => 'bolt',
            'Vlasy' => 'face',
            'Holičstvo a Barber' => 'content_cut',
            'Kozmetika a make-up' => 'face_retouching_natural',
            'Nechty' => 'back_hand',
            'Obočie a mihalnice' => 'visibility',
            'Masáže a wellness' => 'spa',
            'Vrkoče a dredy' => 'waves',
            'Tetovanie' => 'ink_pen',
            'Lekárska estetika' => 'medical_services',
            'Depilácia a epilácia' => 'eco',
            'Domáce služby' => 'home',
            'Piercing' => 'scatter_plot',
            'Služby pre miláčikov' => 'pets',
            'Zubné a ortodontické' => 'dentistry',
            'Zdravie a kondícia' => 'fitness_center',
            'Profesionálne služby' => 'work',
            'Solárium a opaľovanie' => 'sunny',
            'Joga a Pilates' => 'self_improvement',
            'Fyzioterapia' => 'healing',
            'Osobní tréneri' => 'directions_run',
            'Výživové poradenstvo' => 'restaurant_menu',
            'Svadobné služby' => 'celebration',
            'Alternatívna medicína' => 'emoji_nature',
            'Psychológia a Terapia' => 'psychology',
            'Iné' => 'more_horiz'
        ];

        // Synonáma pre kategórie (napr. kaderník -> Vlasy / Holičstvo a Barber)
        $categorySynonyms = [
            'kadernik' => 'Vlasy',
            'kadernictvo' => 'Vlasy',
            'strihanie' => 'Vlasy',
            'barber' => 'Holičstvo a Barber',
            'holic' => 'Holičstvo a Barber',
            'brada' => 'Holičstvo a Barber',
            'manikura' => 'Nechty',
            'pedikura' => 'Nechty',
            'gelove nechty' => 'Nechty',
            'masaz' => 'Masáže a wellness',
            'wellness' => 'Masáže a wellness',
            'sauna' => 'Masáže a wellness',
            'tetovanie' => 'Tetovanie',
            'tattoo' => 'Tetovanie',
            'fitness' => 'Zdravie a kondícia',
            'posilnovna' => 'Zdravie a kondícia',
            'trener' => 'Osobní tréneri',
            'joga' => 'Joga a Pilates',
            'pilates' => 'Joga a Pilates',
            'zuby' => 'Zubné a ortodontické',
            'dentalka' => 'Zubné a ortodontické',
            'pes' => 'Služby pre miláčikov',
            'psy' => 'Služby pre miláčikov',
            'macka' => 'Služby pre miláčikov'
        ];

        $matchedCategories = [];
        foreach ($allCategories as $catName => $icon) {
            $catClean = mb_strtolower(removeAccents($catName), 'UTF-8');
            if (mb_stripos($catClean, $queryNoAccents) !== false || mb_stripos($catName, $query) !== false) {
                $matchedCategories[] = ['name' => $catName, 'type' => 'Kategória', 'icon' => $icon];
            }
        }
        foreach ($categorySynonyms as $synonym => $targetCat) {
            if (mb_stripos($synonym, $queryNoAccents) !== false) {
                $icon = $allCategories[$targetCat] ?? 'category';
                $matchedCategories[] = ['name' => $targetCat, 'type' => 'Kategória', 'icon' => $icon];
            }
        }

        // Získame služby z DB
        $sqlServ = "SELECT DISTINCT name, 'Služba' as type FROM services WHERE name LIKE ? LIMIT 6";
        $stmtServ = $conn->prepare($sqlServ);
        $stmtServ->bind_param("s", $searchQuery);
        $stmtServ->execute();
        $servs = $stmtServ->get_result()->fetch_all(MYSQLI_ASSOC);

        // Získame salóny / prevádzky z DB
        $sqlEst = "SELECT DISTINCT name, 'Salón' as type, city FROM establishments WHERE status = 'active' AND (name LIKE ? OR description LIKE ?) LIMIT 6";
        $stmtEst = $conn->prepare($sqlEst);
        $stmtEst->bind_param("ss", $searchQuery, $searchQuery);
        $stmtEst->execute();
        $ests = $stmtEst->get_result()->fetch_all(MYSQLI_ASSOC);

        // Zoznam populárnych služieb pre fallback
        $staticServices = [
            'Pánsky strih', 'Dámsky strih', 'Detský strih', 'Farbenie vlasov', 'Balayage', 'Melír',
            'Úprava brady', 'Klasické holenie', 'Hĺbkové čistenie pleti', 'Laminácia obočia',
            'Predlžovanie mihalníc', 'Lash lifting', 'Manikúra', 'Pedikúra', 'Gélové nechty',
            'Klasická masáž', 'Športová masáž', 'Thajská masáž', 'Lávové kamene',
            'Laserová epilácia', 'Depilácia voskom', 'Dentálna hygiena', 'Bielenie zubov',
            'Osobný tréning', 'Jedálniček na mieru', 'Svadobné líčenie', 'Permanentný make-up'
        ];

        $matchedStaticServices = [];
        foreach ($staticServices as $serv) {
            $servClean = mb_strtolower(removeAccents($serv), 'UTF-8');
            if (mb_stripos($servClean, $queryNoAccents) !== false) {
                $matchedStaticServices[] = ['name' => $serv, 'type' => 'Služba', 'icon' => 'room_service'];
            }
        }

        // Zlúčenie výsledkov
        $grouped = [
            'Kategórie' => [],
            'Služby' => [],
            'Salóny' => []
        ];

        // Deduplikácia kategórií
        $seenCats = [];
        foreach ($matchedCategories as $cat) {
            if (!isset($seenCats[$cat['name']])) {
                $seenCats[$cat['name']] = true;
                if (count($grouped['Kategórie']) < 4) {
                    $grouped['Kategórie'][] = $cat;
                }
            }
        }

        // Deduplikácia služieb
        $seenServs = [];
        foreach (array_merge($servs, $matchedStaticServices) as $s) {
            if (!isset($seenServs[$s['name']])) {
                $seenServs[$s['name']] = true;
                if (count($grouped['Služby']) < 5) {
                    $grouped['Služby'][] = [
                        'name' => $s['name'],
                        'type' => 'Služba',
                        'icon' => 'content_cut'
                    ];
                }
            }
        }

        // Salóny
        $seenEsts = [];
        foreach ($ests as $e) {
            if (!isset($seenEsts[$e['name']])) {
                $seenEsts[$e['name']] = true;
                if (count($grouped['Salóny']) < 4) {
                    $grouped['Salóny'][] = [
                        'name' => $e['name'],
                        'type' => 'Salón',
                        'city' => $e['city'] ?? '',
                        'icon' => 'storefront'
                    ];
                }
            }
        }

        $hasData = !empty($grouped['Kategórie']) || !empty($grouped['Služby']) || !empty($grouped['Salóny']);

        echo json_encode([
            'success' => true,
            'data' => $hasData ? $grouped : []
        ]);
    } else {
        echo json_encode(['success' => true, 'data' => []]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
