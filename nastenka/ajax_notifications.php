<?php
require_once __DIR__ . '/auth.php';
require_login();

$user = current_user();
$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $notifications = get_recent_notifications($user['id'], 20);
    
    if (empty($notifications)) {
        echo '<div style="padding: 40px 20px; text-align: center; color: #64748b; font-size: 0.9rem;">Zatiaľ tu nie sú žiadne upozornenia.</div>';
        exit;
    }
    
    foreach ($notifications as $n) {
        $bg = $n['is_read'] ? 'transparent' : 'rgba(99, 102, 241, 0.08)';
        $url = $n['link'] ? htmlspecialchars($n['link']) : '#';
        $icon = $n['icon'] ? $n['icon'] : 'ti-bell';
        $time = date('d.m.Y H:i', strtotime($n['created_at']));
        $id = $n['id'];
        
        echo "<div id=\"notif-item-$id\" style=\"display: flex; gap: 15px; padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.06); background: $bg; transition: all 0.3s ease; position: relative;\" class=\"notif-item\">";
        
        // Icon
        echo "  <div style=\"width: 44px; height: 44px; border-radius: 12px; background: rgba(99, 102, 241, 0.15); color: #818cf8; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.2rem;\">";
        echo "    <i class=\"ti $icon\"></i>";
        echo "  </div>";
        
        // Content
        echo "  <div style=\"flex: 1; min-width: 0; cursor: pointer;\" onclick=\"window.location.href='$url'\">";
        echo "    <div style=\"font-size: 0.95rem; font-weight: 700; color: #f8fafc; margin-bottom: 4px; line-height: 1.2;\">" . htmlspecialchars($n['title']) . "</div>";
        echo "    <div style=\"font-size: 0.85rem; color: #94a3b8; line-height: 1.4; margin-bottom: 6px;\">" . $n['message'] . "</div>";
        echo "    <div style=\"font-size: 0.7rem; color: #64748b;\">$time</div>";
        echo "  </div>";
        
        // Actions
        echo "  <div style=\"display: flex; flex-direction: column; gap: 8px; justify-content: start; padding-top: 2px;\">";
        if (!$n['is_read']) {
            echo "    <button onclick=\"handleNotificationAction($id, 'mark_read')\" style=\"background: rgba(34, 197, 94, 0.15); color: #4ade80; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;\" title=\"Označiť ako prečítané\"><i class=\"ti ti-check\" style=\"font-size: 1rem;\"></i></button>";
        }
        echo "    <button onclick=\"handleNotificationAction($id, 'delete')\" style=\"background: rgba(239, 68, 68, 0.1); color: #f87171; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;\" title=\"Odstrániť\"><i class=\"ti ti-trash\" style=\"font-size: 1rem;\"></i></button>";
        echo "  </div>";
        
        echo "</div>";
    }
    exit;
}

if ($action === 'mark_read' || $action === 'delete' || $action === 'mark_all_read') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo = db_connect();
        
        if ($action === 'mark_read') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE crm_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM crm_notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
        } elseif ($action === 'mark_all_read') {
            $stmt = $pdo->prepare("UPDATE crm_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['id']]);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Invalid action']);
