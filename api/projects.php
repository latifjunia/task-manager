<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_GET['action'] == 'create') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nama proyek wajib diisi']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $_SESSION['user_id']]);
        $project_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')");
        $stmt->execute([$project_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Proyek berhasil dibuat',
            'project_id' => $project_id
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>