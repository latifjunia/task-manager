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
    case 'add':
        addMemberHandler();
        break;
    case 'remove':
        removeMemberHandler();
        break;
    case 'update_role':
        updateMemberRoleHandler();
        break;
    case 'list':
        getProjectMembersHandler();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function addMemberHandler() {
    global $pdo;
    header('Content-Type: application/json');
    
    try {
        $project_id = intval($_POST['project_id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'member';
        
        if ($project_id <= 0 || $user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Project ID dan User ID wajib diisi']);
            return;
        }
        
        // CEK PERMISSION - HANYA ADMIN PROYEK
        if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan menambah anggota']);
            return;
        }
        
        // CEK APAKAH SUDAH ANGGOTA
        $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'User sudah menjadi anggota']);
            return;
        }
        
        // TAMBAH ANGGOTA
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        $success = $stmt->execute([$project_id, $user_id, $role]);
        
        if ($success) {
            // AMBIL INFO PROYEK
            $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            // NOTIFIKASI
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
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function removeMemberHandler() {
    global $pdo;
    header('Content-Type: application/json');
    
    try {
        $project_id = intval($_POST['project_id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($project_id <= 0 || $user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Project ID dan User ID wajib diisi']);
            return;
        }
        
        // CEK PERMISSION - HANYA ADMIN PROYEK
        if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan menghapus anggota']);
            return;
        }
        
        // CEK ROLE YANG DIHAPUS
        $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        $member = $stmt->fetch();
        
        if (!$member) {
            echo json_encode(['success' => false, 'message' => 'Anggota tidak ditemukan']);
            return;
        }
        
        // TIDAK BISA HAPUS OWNER
        if ($member['role'] == 'owner') {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus pemilik proyek']);
            return;
        }
        
        // HAPUS ANGGOTA
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $success = $stmt->execute([$project_id, $user_id]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Anggota berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus anggota']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function updateMemberRoleHandler() {
    global $pdo;
    header('Content-Type: application/json');
    
    try {
        $project_id = intval($_POST['project_id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'member';
        
        if ($project_id <= 0 || $user_id <= 0 || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
            return;
        }
        
        // HANYA OWNER YANG BISA UBAH ROLE
        if (!isProjectOwner($project_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Hanya pemilik proyek yang dapat mengubah role']);
            return;
        }
        
        // CEK ROLE YANG DIUBAH
        $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        $member = $stmt->fetch();
        
        if (!$member) {
            echo json_encode(['success' => false, 'message' => 'Anggota tidak ditemukan']);
            return;
        }
        
        // TIDAK BISA UBAH ROLE OWNER
        if ($member['role'] == 'owner') {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah role pemilik']);
            return;
        }
        
        // TIDAK BISA UBAH ROLE SENDIRI
        if ($user_id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah role sendiri']);
            return;
        }
        
        // UPDATE ROLE
        $stmt = $pdo->prepare("UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?");
        $success = $stmt->execute([$role, $project_id, $user_id]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Role berhasil diubah']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah role']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getProjectMembersHandler() {
    global $pdo;
    header('Content-Type: application/json');
    
    try {
        $project_id = intval($_GET['project_id'] ?? 0);
        
        if ($project_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Project ID wajib diisi']);
            return;
        }
        
        $members = getProjectMembers($project_id);
        
        echo json_encode([
            'success' => true,
            'members' => $members,
            'total' => count($members)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>