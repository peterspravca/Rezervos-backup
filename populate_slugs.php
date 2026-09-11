<?php
require_once 'config.php';

$res = $conn->query("SELECT id, full_name, public_id FROM users WHERE public_id IS NULL OR public_id = ''");
while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['full_name'];
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name))));
    $slug = trim($slug, '-');
    if (empty($slug)) $slug = 'user';
    $public_id = $slug . '-' . substr(md5(uniqid()), 0, 6);
    
    $stmt = $conn->prepare("UPDATE users SET public_id = ? WHERE id = ?");
    $stmt->bind_param("si", $public_id, $id);
    $stmt->execute();
    echo "Updated User ID $id with slug $public_id\n";
}
echo "Done.";
?>
