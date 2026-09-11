<?php
/**
 * CRM Gender Migration Script
 * Iterates through all 'unknown' leads and tries to detect Man / Woman / Company.
 */

require_once __DIR__ . '/../config.php';

function detect_gender_php(string $name): string {
    $name = trim($name);
    if (empty($name)) return 'unknown';

    $lowerName = mb_strtolower($name);

    // 1. Company detection
    $companyTerms = [
        's.r.o', 'sro', 'a.s.', ' as', 'spol.', 'o.z.', ' oz', 'n.o.', 'n.f.', 
        'v.o.s', 'vos', 'k.s.', ' s.p.', ' sp ', 'obec', 'mesto', 'zdruze', 'nadac',
        'stavebniny', 'reality', 'servis', 'montaz', 'stolars'
    ];
    foreach ($companyTerms as $term) {
        if (str_contains($lowerName, $term)) return 'other';
    }

    // 2. Slovak name detection
    $parts = explode(' ', $lowerName);
    $lastPart = end($parts);

    // Typical SK female surnames
    if (str_ends_with($lastPart, 'ová') || str_ends_with($lastPart, 'á')) {
        return 'female';
    }

    if (count($parts) > 0) {
        $firstPart = $parts[0];
        // Female first names usually end in -a
        if (str_ends_with($firstPart, 'a')) {
            // Exceptions for males
            $maleExceptions = ['jan', 'benjamin', 'kristian', 'adrian', 'nesta', 'luca', 'toma', 'mustafa'];
            if (!in_array($firstPart, $maleExceptions)) {
                return 'female';
            }
        }
        return 'male';
    }

    return 'unknown';
}

echo "Starting Gender/Company Migration...\n";

try {
    $pdo = db_connect();
    
    // Get all unknown leads
    $stmt = $pdo->query("SELECT id, name FROM leads WHERE gender = 'unknown' OR gender IS NULL");
    $leads = $stmt->fetchAll();
    
    $count_male = 0;
    $count_female = 0;
    $count_other = 0;
    $count_total = count($leads);

    foreach ($leads as $lead) {
        $detected = detect_gender_php($lead['name']);
        if ($detected !== 'unknown') {
            $upd = $pdo->prepare("UPDATE leads SET gender = ? WHERE id = ?");
            $upd->execute([$detected, $lead['id']]);
            
            if ($detected === 'male') $count_male++;
            if ($detected === 'female') $count_female++;
            if ($detected === 'other') $count_other++;
        }
    }

    echo "Migration finished!\n";
    echo "Total checked: $count_total\n";
    echo "Updates: Male: $count_male, Female: $count_female, Company/Other: $count_other\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
