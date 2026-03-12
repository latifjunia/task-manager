<?php
// ==========================================
// API COLUMNS - Manajemen Kolom Kustom & Default
// ==========================================
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleListColumns();
        break;
    case 'create':
        handleCreateColumn();
        break;
    case 'update':
        handleUpdateColumn();
        break;
    case 'delete':
        handleDeleteColumn();
        break;
    case 'update_positions':
        handleUpdatePositions();
        break;
    case 'update_default':
        handleUpdateDefaultColumn();
        break;
    case 'reset_default':
        handleResetDefaultColumn();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleListColumns() {
    $project_id = (int)($_GET['project_id'] ?? 0);
    
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
        return;
    }
    
    if (!hasProjectAccess($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']);
        return;
    }
    
    $columns = getProjectColumns($project_id);
    
    // Pastikan kolom Done selalu ada dan tidak terduplikasi
    $columns = ensureDoneColumnExists($columns);
    
    // Hitung jumlah tugas untuk setiap kolom
    foreach ($columns as &$column) {
        if ($column['is_default']) {
            $tasks = getTasksByColumn($project_id, $column['id']);
        } else {
            $tasks = getTasksByColumn($project_id, 'custom_' . $column['db_id']);
        }
        $column['task_count'] = count($tasks);
    }
    
    echo json_encode([
        'success' => true,
        'columns' => array_values($columns) // Reset array indices
    ]);
}

// Fungsi baru untuk memastikan kolom Done selalu ada
function ensureDoneColumnExists($columns) {
    $doneExists = false;
    $filteredColumns = [];
    
    foreach ($columns as $column) {
        // Jika ini adalah kolom default dengan nama 'done'
        if ($column['is_default'] && $column['id'] === 'done') {
            $doneExists = true;
        }
        
        // Filter out any duplicate custom columns that might represent 'done'
        if (!$column['is_default'] && $column['title'] === 'Done') {
            // Skip custom columns with title 'Done' to prevent duplicates
            continue;
        }
        
        $filteredColumns[] = $column;
    }
    
    // Jika kolom Done belum ada, tambahkan secara manual
    if (!$doneExists) {
        $doneColumn = [
            'id' => 'done',
            'title' => 'Done',
            'color' => '#10b981',
            'icon' => 'bi-check2-circle',
            'position' => 999, // Tempatkan di akhir
            'is_default' => true,
            'task_count' => 0
        ];
        
        // Cek apakah ada pengaturan kustom untuk kolom Done di database
        global $pdo;
        if (isset($_GET['project_id'])) {
            $project_id = (int)$_GET['project_id'];
            $stmt = $pdo->prepare("SELECT * FROM default_column_settings WHERE project_id = ? AND column_name = 'done'");
            $stmt->execute([$project_id]);
            $settings = $stmt->fetch();
            
            if ($settings) {
                $doneColumn['title'] = $settings['custom_title'] ?? 'Done';
                $doneColumn['color'] = $settings['custom_color'] ?? '#10b981';
                $doneColumn['icon'] = $settings['custom_icon'] ?? 'bi-check2-circle';
            }
        }
        
        $filteredColumns[] = $doneColumn;
    }
    
    // Urutkan berdasarkan position
    usort($filteredColumns, function($a, $b) {
        return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
    });
    
    return $filteredColumns;
}

function handleUpdateDefaultColumn() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $column_name = $_POST['column_name'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $color = $_POST['color'] ?? '';
    $icon = $_POST['icon'] ?? '';
    
    if ($project_id <= 0 || empty($column_name) || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    // Cegah update untuk kolom Done - selalu gunakan default
    if ($column_name === 'done') {
        echo json_encode(['success' => false, 'message' => 'Kolom Done tidak dapat dimodifikasi - menggunakan default sistem']);
        return;
    }
    
    if (!in_array($column_name, ['todo', 'in_progress', 'review'])) {
        echo json_encode(['success' => false, 'message' => 'Nama kolom tidak valid']);
        return;
    }
    
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin']);
        return;
    }
    
    // Cek apakah sudah ada setting untuk kolom ini
    $stmt = $pdo->prepare("SELECT id FROM default_column_settings WHERE project_id = ? AND column_name = ?");
    $stmt->execute([$project_id, $column_name]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing
        $stmt = $pdo->prepare("
            UPDATE default_column_settings 
            SET custom_title = ?, custom_color = ?, custom_icon = ?, updated_at = NOW() 
            WHERE project_id = ? AND column_name = ?
        ");
        $success = $stmt->execute([$title, $color, $icon, $project_id, $column_name]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("
            INSERT INTO default_column_settings (project_id, column_name, custom_title, custom_color, custom_icon) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([$project_id, $column_name, $title, $color, $icon]);
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Kolom berhasil diperbarui'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui kolom']);
    }
}

function handleResetDefaultColumn() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $column_name = $_POST['column_name'] ?? '';
    
    if ($project_id <= 0 || empty($column_name)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    // Cegah reset untuk kolom Done
    if ($column_name === 'done') {
        echo json_encode(['success' => false, 'message' => 'Kolom Done selalu menggunakan default sistem']);
        return;
    }
    
    if (!in_array($column_name, ['todo', 'in_progress', 'review'])) {
        echo json_encode(['success' => false, 'message' => 'Nama kolom tidak valid']);
        return;
    }
    
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM default_column_settings WHERE project_id = ? AND column_name = ?");
    $success = $stmt->execute([$project_id, $column_name]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Kolom dikembalikan ke default'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mereset kolom']);
    }
}

function handleCreateColumn() {
    global $pdo;
    
    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $color = $_POST['color'] ?? '#64748b';
    $icon = $_POST['icon'] ?? 'bi-circle';
    
    if ($project_id <= 0 || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    // Cegah pembuatan kolom dengan judul "Done"
    if (strtolower(trim($title)) === 'done') {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat membuat kolom dengan judul "Done" - sudah tersedia sebagai kolom default']);
        return;
    }
    
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin']);
        return;
    }
    
    $column_id = createCustomColumn($project_id, $title, $color, $icon);
    
    if ($column_id) {
        $stmt = $pdo->prepare("SELECT * FROM project_columns WHERE id = ?");
        $stmt->execute([$column_id]);
        $column = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Kolom berhasil ditambahkan',
            'column' => [
                'id' => 'custom_' . $column['id'],
                'original_id' => $column['id'],
                'title' => $column['title'],
                'color' => $column['color'],
                'icon' => $column['icon'],
                'position' => $column['position'],
                'is_default' => false,
                'task_count' => 0
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan kolom']);
    }
}

function handleUpdateColumn() {
    $column_id = (int)($_POST['column_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $color = $_POST['color'] ?? '#64748b';
    $icon = $_POST['icon'] ?? 'bi-circle';
    
    if ($column_id <= 0 || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    // Cegah update kolom menjadi "Done"
    if (strtolower(trim($title)) === 'done') {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah judul kolom menjadi "Done"']);
        return;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT pc.*, p.created_by, p.id as project_id
        FROM project_columns pc
        JOIN projects p ON pc.project_id = p.id
        WHERE pc.id = ?
    ");
    $stmt->execute([$column_id]);
    $column = $stmt->fetch();
    
    if (!$column) {
        echo json_encode(['success' => false, 'message' => 'Kolom tidak ditemukan']);
        return;
    }
    
    if (!isProjectAdmin($column['project_id'], $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin']);
        return;
    }
    
    $success = updateCustomColumn($column_id, [
        'title' => $title,
        'color' => $color,
        'icon' => $icon
    ]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Kolom berhasil diperbarui'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui kolom']);
    }
}

function handleDeleteColumn() {
    $column_id = (int)($_POST['column_id'] ?? 0);
    
    if ($column_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT pc.*, p.created_by, p.id as project_id
        FROM project_columns pc
        JOIN projects p ON pc.project_id = p.id
        WHERE pc.id = ?
    ");
    $stmt->execute([$column_id]);
    $column = $stmt->fetch();
    
    if (!$column) {
        echo json_encode(['success' => false, 'message' => 'Kolom tidak ditemukan']);
        return;
    }
    
    if (!isProjectAdmin($column['project_id'], $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin']);
        return;
    }
    
    $success = deleteCustomColumn($column_id, $column['project_id']);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Kolom berhasil dihapus'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus kolom']);
    }
}

function handleUpdatePositions() {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $positions_json = $_POST['positions'] ?? '';
    
    if ($project_id <= 0 || empty($positions_json)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    if (!isProjectAdmin($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin']);
        return;
    }
    
    $positions = json_decode($positions_json, true);
    
    if (!is_array($positions)) {
        echo json_encode(['success' => false, 'message' => 'Format data tidak valid']);
        return;
    }
    
    $success = updateColumnPositions($project_id, $positions);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Posisi kolom berhasil diperbarui'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui posisi']);
    }
}
?>