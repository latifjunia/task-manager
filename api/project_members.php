<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listMembers();
        break;
    case 'add':
        addMember();
        break;
    case 'remove':
        removeMember();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function listMembers() {
    $project_id = (int)($_GET['project_id'] ?? 0);
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
        return;
    }
    
    $members = getProjectMembers($project_id);
    echo json_encode(['success' => true, 'members' => $members]);
}

function addMember() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $user_id = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'member';
    
    if ($project_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak diizinkan']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Sudah menjadi anggota']);
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
    $success = $stmt->execute([$project_id, $user_id, $role]);
    
    if ($success) {
        $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        createNotification(
            $user_id,
            'Undangan Proyek',
            $_SESSION['full_name'] . ' mengundang Anda ke proyek: ' . $project['name'],
            'system'
        );
        
        echo json_encode(['success' => true, 'message' => 'Anggota berhasil ditambahkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan anggota']);
    }
}

function removeMember() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($project_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak diizinkan']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Anggota tidak ditemukan']);
        return;
    }
    
    if ($member['role'] == 'owner') {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus pemilik']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
    $success = $stmt->execute([$project_id, $user_id]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Anggota berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus anggota']);
    }
}
?>