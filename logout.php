<?php
session_start();
require_once 'config.php'; // Includes DB connection

if (isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    // Clear remember token in DB
    $clear_stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $clear_stmt->bind_param("i", $u_id);
    $clear_stmt->execute();
    $clear_stmt->close();
}

if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

session_destroy();
header('Location: index.php');
exit;
?>
