<?php
require_once __DIR__ . '/config.php';
$pdo = db_connect();

echo "<h1>DB Debug</h1>";

// 1. Check table structure
echo "<h2>Table Structure (leads)</h2>";
$cols = $pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($cols, true) . "</pre>";

// 2. Check Ján Mäsiar directly
echo "<h2>Ján Mäsiar Row</h2>";
$masiar = $pdo->query("SELECT id, name, status, created_at FROM leads WHERE name LIKE '%Mäsiar%'")->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($masiar, true) . "</pre>";

// 3. Check All leads
echo "<h2>All Leads</h2>";
$all = $pdo->query("SELECT id, name, status, created_at FROM leads")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($all, true) . "</pre>";

// 4. Check query that index.php uses for leads
echo "<h2>Dashboard Query Result</h2>";
// Emulate the dashboard query logic
$statuses = ['novy', 'kontaktovany', 'kontaktovany_email', 'kontaktovany_telefon', 'kontaktovany_oboje', 'v_procese', 'zakazka'];
$in_list = "'" . implode("','", $statuses) . "'";
$sql = "SELECT id, name, status FROM leads WHERE status IN ($in_list)";
echo "<p>SQL: $sql</p>";
$results = $pdo->query($sql)->fetchAll();
echo "<pre>" . print_r($results, true) . "</pre>";
?>
