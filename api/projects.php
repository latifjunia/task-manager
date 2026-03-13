<?php
// ==========================================
// API PROJECTS - Manajemen Proyek
// ==========================================
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Debug: log received data
error_log("Projects API called");
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));
error_log("REQUEST: " . print_r($_REQUEST, true));

// Cek action dari berbagai sumber
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

// Jika tidak ada action, coba deteksi dari endpoint
if (empty($action)) {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($request_uri, '?action=') !== false) {
        parse_str(parse_url($request_uri, PHP_URL_QUERY), $query);
        $action = $query['action'] ?? '';
    }
}

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Action tidak ditemukan. Data diterima: ' . json_encode($_POST)]);
    exit;
}

switch ($action) {
    case 'create':
        handleCreateProject();
        break;
    case 'update':
        handleUpdateProject();
        break;
    case 'delete':
        handleDeleteProject();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}

/**
 * Buat proyek baru
 */
function handleCreateProject() {
    global $pdo;
    
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Nama proyek tidak boleh kosong']);
            return;
        }
        
        // Insert proyek baru - HAPUS updated_at karena tidak ada di tabel
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, created_by, created_at) VALUES (?, ?, ?, NOW())");
        $success = $stmt->execute([$name, $description, $_SESSION['user_id']]);
        
        if ($success) {
            $project_id = $pdo->lastInsertId();
            
            // Tambahkan creator sebagai owner
            $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')");
            $stmt->execute([$project_id, $_SESSION['user_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Proyek berhasil dibuat',
                'project_id' => $project_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat proyek']);
        }
        
    } catch (Exception $e) {
        error_log("Error create project: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
}

/**
 * Update proyek
 */
function handleUpdateProject() {
    global $pdo;
    
    try {
        $project_id = (int)($_POST['project_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        error_log("Updating project ID: $project_id, Name: $name");
        
        if ($project_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
            return;
        }
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Nama proyek tidak boleh kosong']);
            return;
        }
        
        // Cek akses ke proyek
        if (!hasProjectAccess($project_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke proyek ini']);
            return;
        }
        
        // Cek apakah user adalah admin atau owner
        if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin untuk mengedit proyek']);
            return;
        }
        
        // Update proyek - HAPUS updated_at karena tidak ada di tabel
        $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ? WHERE id = ?");
        $success = $stmt->execute([$name, $description, $project_id]);
        
        if ($success) {
            // Buat notifikasi untuk anggota proyek
            if (function_exists('createNotification')) {
                $members = getProjectMembers($project_id);
                foreach ($members as $member) {
                    if ($member['id'] != $_SESSION['user_id']) {
                        createNotification(
                            $member['id'],
                            'Proyek Diperbarui',
                            $_SESSION['full_name'] . ' memperbarui proyek: ' . $name,
                            'system'
                        );
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Proyek berhasil diperbarui'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui proyek']);
        }
        
    } catch (Exception $e) {
        error_log("Error update project: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
}

/**
 * Hapus proyek
 */
function handleDeleteProject() {
    global $pdo;
    
    try {
        $project_id = (int)($_POST['project_id'] ?? 0);
        
        error_log("Deleting project ID: $project_id");
        
        if ($project_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
            return;
        }
        
        // Cek apakah user adalah owner
        if (!isProjectOwner($project_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Hanya pemilik proyek yang dapat menghapus proyek']);
            return;
        }
        
        // Mulai transaksi
        $pdo->beginTransaction();
        
        // Dapatkan info proyek untuk notifikasi
        $project = getProjectById($project_id);
        $members = getProjectMembers($project_id);
        
        // Hapus attachments (file fisik)
        $stmt = $pdo->prepare("
            SELECT a.filepath 
            FROM attachments a
            INNER JOIN tasks t ON a.task_id = t.id
            WHERE t.project_id = ?
        ");
        $stmt->execute([$project_id]);
        $attachments = $stmt->fetchAll();
        
        $uploadDir = __DIR__ . '/../../uploads/tasks/';
        foreach ($attachments as $attachment) {
            $filepath = $uploadDir . $attachment['filepath'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // Hapus semua data terkait
        // 1. Hapus comments (via tasks)
        $pdo->prepare("DELETE c FROM comments c INNER JOIN tasks t ON c.task_id = t.id WHERE t.project_id = ?")->execute([$project_id]);
        
        // 2. Hapus attachments (via tasks)
        $pdo->prepare("DELETE a FROM attachments a INNER JOIN tasks t ON a.task_id = t.id WHERE t.project_id = ?")->execute([$project_id]);
        
        // 3. Hapus tasks
        $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$project_id]);
        
        // 4. Hapus custom columns
        $pdo->prepare("DELETE FROM project_columns WHERE project_id = ?")->execute([$project_id]);
        
        // 5. Hapus default column settings
        $pdo->prepare("DELETE FROM default_column_settings WHERE project_id = ?")->execute([$project_id]);
        
        // 6. Hapus project members
        $pdo->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$project_id]);
        
        // 7. Hapus project
        $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$project_id]);
        
        // Commit transaksi
        $pdo->commit();
        
        // Buat notifikasi untuk anggota (setelah commit)
        if (function_exists('createNotification')) {
            foreach ($members as $member) {
                if ($member['id'] != $_SESSION['user_id']) {
                    createNotification(
                        $member['id'],
                        'Proyek Dihapus',
                        'Proyek "' . $project['name'] . '" telah dihapus oleh ' . $_SESSION['full_name'],
                        'system'
                    );
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Proyek berhasil dihapus'
        ]);
        
    } catch (Exception $e) {
        // Rollback jika terjadi error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error delete project: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
}
?>