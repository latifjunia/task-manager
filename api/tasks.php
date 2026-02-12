<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createTask();
        break;
    case 'get':
        getTask();
        break;
    case 'update_status':
        updateTaskStatus();
        break;
    case 'delete':
        deleteTask();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function createTask() {
    global $pdo;
    
    $title = trim($_POST['title'] ?? '');
    $project_id = (int)($_POST['project_id'] ?? 0);
    
    if (empty($title) || $project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Judul dan proyek wajib diisi']);
        return;
    }
    
    if (!hasProjectAccess($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']);
        return;
    }
    
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['column_status'] ?? 'todo';
    $priority = $_POST['priority'] ?? 'medium';
    $assignee_id = !empty($_POST['assignee_id']) ? (int)$_POST['assignee_id'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, project_id, column_status, priority, assignee_id, created_by, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$title, $description, $project_id, $status, $priority, $assignee_id, $_SESSION['user_id'], $due_date]);
        $task_id = $pdo->lastInsertId();
        
        if ($assignee_id && $assignee_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            createNotification(
                $assignee_id,
                'Tugas Baru',
                $_SESSION['full_name'] . ' menugaskan Anda: ' . $title,
                'assignment'
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Tugas berhasil dibuat', 'task_id' => $task_id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getTask() {
    global $pdo;
    
    $task_id = (int)($_GET['id'] ?? 0);
    if ($task_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
        return;
    }
    
    $task = getTaskById($task_id);
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
        return;
    }
    
    $comments = getTaskComments($task_id);
    $members = getProjectMembers($task['project_id']);
    $is_admin = isProjectAdmin($task['project_id'], $_SESSION['user_id']);
    
    ob_start();
    ?>
    <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-card-checklist me-2"></i><?= htmlspecialchars($task['title']) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <!-- Description -->
        <div class="mb-4">
            <h6 class="fw-bold mb-2">Deskripsi</h6>
            <p class="mb-0"><?= nl2br(htmlspecialchars($task['description'] ?: 'Tidak ada deskripsi')) ?></p>
        </div>
        
        <!-- Details -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <small class="text-muted d-block">Status</small>
                <?= getStatusBadge($task['column_status']) ?>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Prioritas</small>
                <?= getPriorityBadge($task['priority']) ?>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Ditugaskan</small>
                <span><?= $task['assignee_name'] ?: 'Belum ditugaskan' ?></span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Dibuat oleh</small>
                <span><?= htmlspecialchars($task['creator_name']) ?></span>
            </div>
            <?php if ($task['due_date']): ?>
                <div class="col-12">
                    <small class="text-muted d-block">Tenggat Waktu</small>
                    <span class="<?= strtotime($task['due_date']) < time() ? 'text-danger fw-bold' : '' ?>">
                        <?= date('d M Y', strtotime($task['due_date'])) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Comments -->
        <div class="mb-4">
            <h6 class="fw-bold mb-3">Komentar</h6>
            <div style="max-height: 200px; overflow-y: auto;" class="mb-3">
                <?php if (empty($comments)): ?>
                    <p class="text-muted text-center py-3">Belum ada komentar</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($comment['full_name']) ?></strong>
                                <small class="text-muted"><?= timeAgo($comment['created_at']) ?></small>
                            </div>
                            <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Add Comment -->
            <form onsubmit="event.preventDefault(); addComment(<?= $task_id ?>, this);">
                <div class="input-group">
                    <input type="text" class="form-control" name="content" placeholder="Tulis komentar..." required>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-footer">
        <?php if ($task['created_by'] == $_SESSION['user_id'] || $is_admin): ?>
            <button type="button" class="btn btn-outline-danger" onclick="deleteTask(<?= $task_id ?>)">
                <i class="bi bi-trash me-1"></i>Hapus
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>
    <?php
    echo ob_get_clean();
}

function updateTaskStatus() {
    global $pdo;
    
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($task_id <= 0 || !in_array($status, ['todo', 'in_progress', 'review', 'done'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    $task = getTaskById($task_id);
    if (!$task || !hasProjectAccess($task['project_id'], $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE tasks SET column_status = ?, updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$status, $task_id]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
    }
}

function deleteTask() {
    global $pdo;
    
    $task_id = (int)($_POST['task_id'] ?? 0);
    if ($task_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
        return;
    }
    
    $task = getTaskById($task_id);
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
        return;
    }
    
    // Check permission
    $can_delete = ($task['created_by'] == $_SESSION['user_id'] || isAdmin() || isProjectOwner($task['project_id'], $_SESSION['user_id']));
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'Tidak diizinkan menghapus tugas ini']);
        return;
    }
    
    $success = deleteTask($task_id);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Tugas berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus tugas']);
    }
}
?>