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
        handleListMembers();
        break;
    case 'add':
        handleAddMember();
        break;
    case 'remove':
        handleRemoveMember();
        break;
    case 'update_role':
        handleUpdateRole();
        break;
    case 'search_users':
        handleSearchUsers();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleListMembers() {
    $project_id = (int)($_GET['project_id'] ?? 0);
    
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
        return;
    }
    
    // Cek akses ke proyek
    if (!hasProjectAccess($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke proyek ini']);
        return;
    }
    
    $members = getProjectMembers($project_id);
    
    // Tambahkan informasi tambahan
    foreach ($members as &$member) {
        $member['avatar_color'] = getAvatarColor($member['full_name']);
        $member['initials'] = getInitials($member['full_name']);
        $member['joined_formatted'] = date('d M Y', strtotime($member['joined_at']));
    }
    
    echo json_encode([
        'success' => true,
        'members' => $members,
        'total' => count($members)
    ]);
}

function handleAddMember() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $user_id = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'member';
    
    if ($project_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    // Validasi role
    if (!in_array($role, ['member', 'admin'])) {
        $role = 'member';
    }
    
    // Cek apakah user memiliki izin untuk menambah anggota (admin/owner)
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin untuk menambah anggota']);
        return;
    }
    
    // Cek apakah user sudah menjadi anggota
    $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'User sudah menjadi anggota proyek']);
        return;
    }
    
    try {
        // Dapatkan informasi proyek
        $project = getProjectById($project_id);
        
        // Tambahkan anggota
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        $success = $stmt->execute([$project_id, $user_id, $role]);
        
        if ($success) {
            // Buat notifikasi untuk user yang ditambahkan
            createNotification(
                $user_id,
                'Undangan Proyek',
                $_SESSION['full_name'] . ' menambahkan Anda ke proyek: ' . $project['name'],
                'system'
            );
            
            // Dapatkan data user yang ditambahkan
            $user = getUserById($user_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Anggota berhasil ditambahkan',
                'member' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'username' => $user['username'],
                    'role' => $role,
                    'joined_at' => date('Y-m-d H:i:s'),
                    'avatar_color' => getAvatarColor($user['full_name']),
                    'initials' => getInitials($user['full_name'])
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan anggota']);
        }
    } catch (Exception $e) {
        error_log("Error add member: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleRemoveMember() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($project_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    // Cek apakah user memiliki izin untuk menghapus anggota (admin/owner)
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin untuk menghapus anggota']);
        return;
    }
    
    // Cek role user yang akan dihapus
    $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Anggota tidak ditemukan']);
        return;
    }
    
    // Cegah penghapusan owner
    if ($member['role'] == 'owner') {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus pemilik proyek']);
        return;
    }
    
    // Cegah penghapusan diri sendiri (kecuali owner)
    if ($user_id == $_SESSION['user_id'] && $member['role'] != 'owner') {
        echo json_encode(['success' => false, 'message' => 'Anda tidak dapat menghapus diri sendiri']);
        return;
    }
    
    try {
        // Dapatkan informasi proyek
        $project = getProjectById($project_id);
        
        // Hapus anggota
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $success = $stmt->execute([$project_id, $user_id]);
        
        if ($success) {
            // Buat notifikasi untuk user yang dihapus
            if ($user_id != $_SESSION['user_id']) {
                createNotification(
                    $user_id,
                    'Dihapus dari Proyek',
                    'Anda telah dihapus dari proyek: ' . $project['name'] . ' oleh ' . $_SESSION['full_name'],
                    'system'
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Anggota berhasil dihapus'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus anggota']);
        }
    } catch (Exception $e) {
        error_log("Error remove member: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleUpdateRole() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_role = $_POST['role'] ?? '';
    
    if ($project_id <= 0 || $user_id <= 0 || !in_array($new_role, ['member', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    // Cek apakah user memiliki izin untuk mengubah role (hanya owner)
    if (!isProjectOwner($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Hanya pemilik proyek yang dapat mengubah role']);
        return;
    }
    
    // Cek role user yang akan diubah
    $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Anggota tidak ditemukan']);
        return;
    }
    
    // Cegah perubahan role owner
    if ($member['role'] == 'owner') {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah role pemilik proyek']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?");
        $success = $stmt->execute([$new_role, $project_id, $user_id]);
        
        if ($success) {
            // Dapatkan informasi proyek
            $project = getProjectById($project_id);
            
            // Buat notifikasi
            createNotification(
                $user_id,
                'Role Diubah',
                'Role Anda di proyek ' . $project['name'] . ' diubah menjadi ' . ucfirst($new_role) . ' oleh ' . $_SESSION['full_name'],
                'system'
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Role berhasil diperbarui'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui role']);
        }
    } catch (Exception $e) {
        error_log("Error update role: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleSearchUsers() {
    global $pdo;
    
    $project_id = (int)($_GET['project_id'] ?? 0);
    $search = trim($_GET['q'] ?? '');
    
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
        return;
    }
    
    // Cek akses ke proyek
    if (!hasProjectAccess($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke proyek ini']);
        return;
    }
    
    // Dapatkan ID anggota yang sudah ada
    $stmt = $pdo->prepare("SELECT user_id FROM project_members WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $existing_members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Cari user yang belum menjadi anggota
    $sql = "SELECT id, username, full_name FROM users WHERE 1=1";
    $params = [];
    
    if (!empty($existing_members)) {
        $placeholders = implode(',', array_fill(0, count($existing_members), '?'));
        $sql .= " AND id NOT IN ($placeholders)";
        $params = array_merge($params, $existing_members);
    }
    
    if (!empty($search)) {
        $sql .= " AND (username LIKE ? OR full_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY full_name LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Format data
    foreach ($users as &$user) {
        $user['avatar_color'] = getAvatarColor($user['full_name']);
        $user['initials'] = getInitials($user['full_name']);
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
}
?>