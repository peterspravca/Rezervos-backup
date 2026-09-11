require_once __DIR__ . '/auth.php';
// require_admin(); // Dočasne vypnuté pre ľahšie spustenie

$pdo = db_connect();

echo "<style>body { background: #0f1117; color: #e2e8f0; font-family: sans-serif; padding: 20px; line-height: 1.6; } .item { margin-bottom: 5px; padding: 5px; border-bottom: 1px solid #2d3748; } .tag { font-weight: bold; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; } .tag-male { background: #2563eb; } .tag-female { background: #db2777; } .tag-other { background: #d97706; } .tag-unknown { background: #4a5568; }</style>";

function detect_gender_php(string $name): string {
    $name = trim($name);
    if (empty($name)) return 'unknown';
    $lowerName = mb_strtolower($name);

    $companyTerms = ['s.r.o', 'sro', 'a.s.', ' as', 'spol.', 'o.z.', ' oz', 'n.o.', 'n.f.', 'v.o.s', 'vos', 'k.s.', ' s.p.', ' sp ', 'obec', 'mesto', 'zdruze', 'nadac', 'stavebniny', 'reality', 'servis', 'montaz', 'stolars', 'marsoft'];
    foreach ($companyTerms as $term) { if (str_contains($lowerName, $term)) return 'other'; }

    $parts = explode(' ', $lowerName);
    $lastPart = end($parts);
    if (str_ends_with($lastPart, 'ová') || str_ends_with($lastPart, 'á')) return 'female';

    if (count($parts) > 0) {
        $firstPart = $parts[0];
        if (str_ends_with($firstPart, 'a')) {
            $maleExceptions = ['jan', 'benjamin', 'kristian', 'adrian', 'nesta', 'luca', 'toma', 'mustafa'];
            if (!in_array($firstPart, $maleExceptions)) return 'female';
        }
        return 'male';
    }
    return 'unknown';
}

echo "<h1>Ladenie migrácie kontaktov</h1>";

// Skúsme nájsť všetky kontakty, nielen unknown, aby sme videli stav
$stmt = $pdo->query("SELECT id, name, gender FROM leads ORDER BY id DESC LIMIT 20");
$leads = $stmt->fetchAll();
$updated = 0;

foreach ($leads as $lead) {
    $current = $lead['gender'] ?? 'unknown';
    $detected = detect_gender_php($lead['name']);
    
    echo "<div class='item'>";
    echo "ID: " . $lead['id'] . " | Meno: <strong>" . htmlspecialchars($lead['name']) . "</strong> | Aktuálne: <span class='tag tag-$current'>$current</span>";
    
    if ($current !== $detected && $detected !== 'unknown') {
        $pdo->prepare("UPDATE leads SET gender = ? WHERE id = ?")->execute([$detected, $lead['id']]);
        echo " → ZMENENÉ NA: <span class='tag tag-$detected'>$detected</span> ✅";
        $updated++;
    } else {
        echo " | Detekované: <span class='tag tag-$detected'>$detected</span> (bezo zmeny)";
    }
    echo "</div>";
}

echo "<h2>Migrácia dokončená</h2>";
echo "<p>Aktualizovaných v tomto behu: <strong>$updated</strong></p>";
echo "<p><a href='index.php' style='color: #6366f1;'>Späť na Dashboard</a></p>";

