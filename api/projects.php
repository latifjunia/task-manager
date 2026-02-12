<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createProjectHandler();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function createProjectHandler() {
    global $pdo;
    header('Content-Type: application/json');
    
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Nama proyek wajib diisi']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // INSERT PROYEK
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $_SESSION['user_id']]);
        $project_id = $pdo->lastInsertId();
        
        // TAMBAH PEMBUAT SEBAGAI OWNER
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')");
        $stmt->execute([$project_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Proyek berhasil dibuat',
            'project_id' => $project_id,
            'project_name' => $name
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>