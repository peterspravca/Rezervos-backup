<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset("utf8mb4");
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;

try {
    if ($lat !== null && $lon !== null) {
        // GPS vyhľadávanie - nájde najbližšiu obec pomocou Haversine vzorca (vzdialenosť v km)
        $sql = "
            SELECT city_name, zip_code, admin_name, country,
            (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance
            FROM locations
            ORDER BY distance ASC
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddd", $lat, $lon, $lat);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenájdené žiadne blízke lokality.']);
        }
    } elseif ($query !== '') {
        // Vyhľadávanie podľa textu alebo PSČ
        $cleanQuery = str_replace(' ', '', $query);
        $zipQuery = $cleanQuery . '%';
        $nameQuery = $query . '%';
        $containQuery = '%' . $query . '%';
        
        $sql = "
            SELECT city_name, zip_code, admin_name, country
            FROM locations
            WHERE REPLACE(zip_code, ' ', '') LIKE ?
               OR city_name LIKE ?
               OR city_name LIKE ?
               OR admin_name LIKE ?
            GROUP BY zip_code, city_name
            ORDER BY 
               CASE 
                   WHEN city_name LIKE ? THEN 1 
                   WHEN REPLACE(zip_code, ' ', '') LIKE ? THEN 2
                   ELSE 3 
               END,
               city_name ASC
            LIMIT 12
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $zipQuery, $nameQuery, $containQuery, $containQuery, $nameQuery, $zipQuery);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $results]);
    } else {
        $sql = "SELECT COUNT(*) as cnt FROM locations";
        $stmt = $conn->query($sql);
        $cnt = $stmt->fetch_assoc()['cnt'];
        echo json_encode(['success' => true, 'data' => [], 'count' => $cnt]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
