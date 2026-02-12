<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_GET['action'] == 'get_all') {
    $stmt = $pdo->prepare("SELECT id, username, full_name FROM users WHERE id != ? ORDER BY full_name");
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'users' => $users]);
}
?>