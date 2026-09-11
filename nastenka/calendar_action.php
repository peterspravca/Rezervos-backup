<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = db_connect();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if (!$id && $action !== 'add_custom_event') {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

try {
    switch ($action) {
        case 'update_lead_date':
            $type = $_POST['type'] ?? ''; // survey, realization, followup
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? null;
            
            $column_date = '';
            $column_time = '';
            
            if ($type === 'survey') {
                $column_date = 'survey_date';
                $column_time = 'survey_time';
            } elseif ($type === 'realization') {
                $column_date = 'realization_date';
                $column_time = 'realization_time';
            } elseif ($type === 'followup') {
                $column_date = 'next_followup';
                $column_time = 'next_followup_time';
            }
            
            if ($column_date) {
                $stmt = $pdo->prepare("UPDATE leads SET $column_date = ?, $column_time = ? WHERE id = ?");
                $stmt->execute([$date ?: null, $time ?: null, $id]);
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
            }
            break;
            
        case 'mark_followup_done':
            // Clear the next_followup date to remove it from calendar
            $stmt = $pdo->prepare("UPDATE leads SET next_followup = NULL, next_followup_time = NULL WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'delete_custom_event':
            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND (user_id = ? OR ? = 'admin')");
            $stmt->execute([$id, current_user()['id'], current_user()['role']]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
