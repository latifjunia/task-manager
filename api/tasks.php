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
        createTaskHandler();
        break;
    case 'get':
        getTaskHandler();
        break;
    case 'update_status':
        updateTaskStatusHandler();
        break;
    case 'delete':
        deleteTaskHandler();
        break;
    case 'update':
        updateTaskHandler();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function createTaskHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $project_id = intval($_POST['project_id'] ?? 0);
        $column_status = $_POST['column_status'] ?? 'todo';
        $priority = $_POST['priority'] ?? 'medium';
        $assignee_id = !empty($_POST['assignee_id']) ? intval($_POST['assignee_id']) : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Validasi
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Judul tugas wajib diisi']);
            return;
        }
        
        if ($project_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
            return;
        }
        
        // Cek akses user ke project
        if (!hasProjectAccess($project_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke project ini']);
            return;
        }
        
        // Validasi assignee
        if ($assignee_id) {
            // Cek apakah assignee adalah anggota project
            $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$project_id, $assignee_id]);
            if ($stmt->rowCount() == 0) {
                echo json_encode(['success' => false, 'message' => 'Assignee bukan anggota project']);
                return;
            }
        }
        
        // Validasi due date
        if ($due_date) {
            $due_timestamp = strtotime($due_date);
            if ($due_timestamp === false) {
                echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid']);
                return;
            }
            
            // Pastikan due date tidak di masa lalu
            if ($due_timestamp < time()) {
                echo json_encode(['success' => false, 'message' => 'Due date tidak boleh di masa lalu']);
                return;
            }
        }
        
        // Mulai transaction
        $pdo->beginTransaction();
        
        // Insert task
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, project_id, column_status, priority, assignee_id, created_by, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $title, 
            $description, 
            $project_id, 
            $column_status, 
            $priority, 
            $assignee_id, 
            $_SESSION['user_id'], 
            $due_date
        ]);
        
        $task_id = $pdo->lastInsertId();
        
        // Create notification for assignee if assigned
        if ($assignee_id && $assignee_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            createNotification(
                $assignee_id,
                'Tugas Baru',
                $_SESSION['full_name'] . ' menugaskan Anda: ' . $title . ' di proyek ' . $project['name'],
                'assignment'
            );
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tugas berhasil dibuat',
            'task_id' => $task_id,
            'task_title' => $title
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error createTaskHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getTaskHandler() {
    global $pdo;
    
    try {
        $task_id = intval($_GET['id'] ?? 0);
        
        if ($task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
            return;
        }
        
        // Get task details
        $task = getTaskById($task_id);
        
        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
            return;
        }
        
        // Check access
        if (!hasTaskAccess($task_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke tugas ini']);
            return;
        }
        
        // Get comments
        $comments = getTaskComments($task_id);
        
        // Get attachments
        $attachments = getTaskAttachments($task_id);
        
        // Get project members for assignee dropdown
        $project_members = getProjectMembers($task['project_id']);
        
        // Prepare HTML response
        ob_start();
        ?>
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
                <i class="bi bi-card-checklist me-2"></i>
                <?php echo htmlspecialchars($task['title']); ?>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
            <!-- Task Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <!-- Description -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Deskripsi</h6>
                        <p class="mb-0">
                            <?php echo nl2br(htmlspecialchars($task['description'] ?: 'Tidak ada deskripsi')); ?>
                        </p>
                    </div>
                    
                    <!-- Task Details -->
                    <div class="row g-3">
                        <!-- Status -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Status</h6>
                            <?php 
                            $status_badges = [
                                'todo' => ['class' => 'warning', 'text' => 'To Do'],
                                'in_progress' => ['class' => 'info', 'text' => 'In Progress'],
                                'review' => ['class' => 'primary', 'text' => 'Review'],
                                'done' => ['class' => 'success', 'text' => 'Done']
                            ];
                            ?>
                            <span class="badge bg-<?php echo $status_badges[$task['column_status']]['class']; ?>">
                                <?php echo $status_badges[$task['column_status']]['text']; ?>
                            </span>
                        </div>
                        
                        <!-- Priority -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Prioritas</h6>
                            <?php 
                            $priority_badges = [
                                'low' => ['class' => 'success', 'text' => 'Rendah'],
                                'medium' => ['class' => 'warning', 'text' => 'Sedang'],
                                'high' => ['class' => 'danger', 'text' => 'Tinggi'],
                                'urgent' => ['class' => 'danger', 'text' => 'Mendesak']
                            ];
                            ?>
                            <span class="badge bg-<?php echo $priority_badges[$task['priority']]['class']; ?>">
                                <?php echo $priority_badges[$task['priority']]['text']; ?>
                            </span>
                        </div>
                        
                        <!-- Assignee -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Ditugaskan kepada</h6>
                            <div class="d-flex align-items-center">
                                <?php if ($task['assignee_name']): ?>
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($task['assignee_name']); ?>&background=4361ee&color=fff&size=32" 
                                         class="rounded-circle me-2" width="32" height="32">
                                    <span><?php echo htmlspecialchars($task['assignee_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Belum ditugaskan</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Creator -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Dibuat oleh</h6>
                            <div class="d-flex align-items-center">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($task['creator_name']); ?>&background=4361ee&color=fff&size=32" 
                                     class="rounded-circle me-2" width="32" height="32">
                                <span><?php echo htmlspecialchars($task['creator_name']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Due Date -->
                        <?php if ($task['due_date']): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Tenggat Waktu</h6>
                                <?php
                                $due_date = new DateTime($task['due_date']);
                                $today = new DateTime();
                                $interval = $today->diff($due_date);
                                
                                $is_overdue = ($today > $due_date && $task['column_status'] != 'done');
                                $is_due_soon = ($interval->days <= 2 && $interval->invert == 0 && $task['column_status'] != 'done');
                                ?>
                                <span class="<?php echo $is_overdue ? 'text-danger' : ($is_due_soon ? 'text-warning' : ''); ?>">
                                    <i class="bi bi-calendar-event me-2"></i>
                                    <?php echo date('d M Y', strtotime($task['due_date'])); ?>
                                    <?php if ($is_overdue): ?>
                                        <span class="badge bg-danger ms-2">Terlambat</span>
                                    <?php elseif ($is_due_soon): ?>
                                        <span class="badge bg-warning ms-2">Segera</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Created Date -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Dibuat pada</h6>
                            <span>
                                <i class="bi bi-clock me-2"></i>
                                <?php echo date('d M Y H:i', strtotime($task['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Comments Section -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i> Komentar</h6>
                </div>
                <div class="card-body">
                    <div id="commentsSection" style="max-height: 250px; overflow-y: auto;" class="mb-3">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted text-center py-3">Belum ada komentar</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['full_name']); ?>&background=4361ee&color=fff&size=32" 
                                                 class="rounded-circle me-2" width="32" height="32">
                                            <div>
                                                <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong>
                                                <div class="small text-muted">
                                                    <?php echo timeAgo($comment['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 ps-4"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add Comment Form -->
                    <form id="addCommentForm">
                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="content" 
                                   placeholder="Tulis komentar..." required>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Attachments Section -->
            <?php if (!empty($attachments)): ?>
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i> Lampiran</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark me-3 fs-4 text-primary"></i>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($attachment['filename']); ?></div>
                                            <small class="text-muted">
                                                Diunggah oleh <?php echo htmlspecialchars($attachment['full_name']); ?> • 
                                                <?php echo timeAgo($attachment['uploaded_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="../<?php echo $attachment['filepath']; ?>" 
                                           class="btn btn-sm btn-outline-primary me-2" 
                                           target="_blank" 
                                           data-bs-toggle="tooltip" 
                                           title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="../<?php echo $attachment['filepath']; ?>" 
                                           class="btn btn-sm btn-outline-success" 
                                           download
                                           data-bs-toggle="tooltip" 
                                           title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="modal-footer">
            <!-- Upload Attachment Button -->
            <button type="button" class="btn btn-outline-primary" onclick="showUploadAttachment(<?php echo $task_id; ?>)">
                <i class="bi bi-paperclip me-1"></i> Upload File
            </button>
            
            <!-- Edit Button (for creator or admin) -->
            <?php if ($task['created_by'] == $_SESSION['user_id'] || isAdmin() || isProjectAdmin($task['project_id'], $_SESSION['user_id'])): ?>
                <button type="button" class="btn btn-outline-warning" onclick="editTask(<?php echo $task_id; ?>)">
                    <i class="bi bi-pencil me-1"></i> Edit
                </button>
            <?php endif; ?>
            
            <!-- Delete Button (for creator or admin) -->
            <?php if ($task['created_by'] == $_SESSION['user_id'] || isAdmin() || isProjectOwner($task['project_id'], $_SESSION['user_id'])): ?>
                <button type="button" class="btn btn-outline-danger" onclick="deleteTask(<?php echo $task_id; ?>)">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
            <?php endif; ?>
            
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
        <?php
        $html = ob_get_clean();
        
        echo $html;
        
    } catch (Exception $e) {
        error_log("Error getTaskHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function updateTaskStatusHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $task_id = intval($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
            return;
        }
        
        if (!in_array($status, ['todo', 'in_progress', 'review', 'done'])) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            return;
        }
        
        // Check access
        if (!hasTaskAccess($task_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke tugas ini']);
            return;
        }
        
        // Get current task info
        $task = getTaskById($task_id);
        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
            return;
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE tasks SET column_status = ?, updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$status, $task_id]);
        
        if ($success) {
            // Create notification for task creator if status changed by someone else
            if ($task['created_by'] != $_SESSION['user_id']) {
                $status_texts = [
                    'todo' => 'To Do',
                    'in_progress' => 'In Progress',
                    'review' => 'Review',
                    'done' => 'Done'
                ];
                
                createNotification(
                    $task['created_by'],
                    'Status Tugas Diubah',
                    $_SESSION['full_name'] . ' mengubah status tugas "' . $task['title'] . '" menjadi ' . $status_texts[$status],
                    'system'
                );
            }
            
            // Create notification for assignee if different from current user
            if ($task['assignee_id'] && $task['assignee_id'] != $_SESSION['user_id']) {
                createNotification(
                    $task['assignee_id'],
                    'Status Tugas Anda Diubah',
                    $_SESSION['full_name'] . ' mengubah status tugas "' . $task['title'] . '" menjadi ' . $status_texts[$status],
                    'system'
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Status berhasil diperbarui',
                'status' => $status
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
        }
        
    } catch (Exception $e) {
        error_log("Error updateTaskStatusHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteTaskHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $task_id = intval($_POST['task_id'] ?? 0);
        
        if ($task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
            return;
        }
        
        // Get task info
        $task = getTaskById($task_id);
        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
            return;
        }
        
        // Check permission
        $can_delete = false;
        
        // Creator can delete
        if ($task['created_by'] == $_SESSION['user_id']) {
            $can_delete = true;
        }
        // Admin can delete
        elseif (isAdmin()) {
            $can_delete = true;
        }
        // Project owner can delete
        elseif (isProjectOwner($task['project_id'], $_SESSION['user_id'])) {
            $can_delete = true;
        }
        
        if (!$can_delete) {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan menghapus tugas ini']);
            return;
        }
        
        // Delete task
        $success = deleteTask($task_id);
        
        if ($success) {
            // Create notification for assignee if exists
            if ($task['assignee_id']) {
                createNotification(
                    $task['assignee_id'],
                    'Tugas Dihapus',
                    'Tugas "' . $task['title'] . '" telah dihapus oleh ' . $_SESSION['full_name'],
                    'system'
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tugas berhasil dihapus'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus tugas']);
        }
        
    } catch (Exception $e) {
        error_log("Error deleteTaskHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function updateTaskHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $task_id = intval($_POST['task_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $assignee_id = !empty($_POST['assignee_id']) ? intval($_POST['assignee_id']) : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        if ($task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
            return;
        }
        
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Judul tugas wajib diisi']);
            return;
        }
        
        // Get current task info
        $task = getTaskById($task_id);
        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
            return;
        }
        
        // Check permission
        $can_edit = false;
        
        // Creator can edit
        if ($task['created_by'] == $_SESSION['user_id']) {
            $can_edit = true;
        }
        // Admin can edit
        elseif (isAdmin()) {
            $can_edit = true;
        }
        // Project admin/owner can edit
        elseif (isProjectAdmin($task['project_id'], $_SESSION['user_id'])) {
            $can_edit = true;
        }
        
        if (!$can_edit) {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan mengedit tugas ini']);
            return;
        }
        
        // Validasi assignee
        if ($assignee_id) {
            // Cek apakah assignee adalah anggota project
            $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$task['project_id'], $assignee_id]);
            if ($stmt->rowCount() == 0) {
                echo json_encode(['success' => false, 'message' => 'Assignee bukan anggota project']);
                return;
            }
        }
        
        // Validasi due date
        if ($due_date) {
            $due_timestamp = strtotime($due_date);
            if ($due_timestamp === false) {
                echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid']);
                return;
            }
        }
        
        // Update task
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET title = ?, 
                description = ?, 
                priority = ?, 
                assignee_id = ?, 
                due_date = ?, 
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $success = $stmt->execute([
            $title, 
            $description, 
            $priority, 
            $assignee_id, 
            $due_date, 
            $task_id
        ]);
        
        if ($success) {
            // Check if assignee changed
            $old_assignee_id = $task['assignee_id'];
            $assignee_changed = ($old_assignee_id != $assignee_id);
            
            // Create notification for new assignee if assigned
            if ($assignee_changed && $assignee_id) {
                createNotification(
                    $assignee_id,
                    'Tugas Ditugaskan',
                    $_SESSION['full_name'] . ' menugaskan Anda: ' . $title,
                    'assignment'
                );
            }
            
            // Create notification for old assignee if removed
            if ($assignee_changed && $old_assignee_id) {
                createNotification(
                    $old_assignee_id,
                    'Tugas Dipindahkan',
                    'Tugas "' . $title . '" telah dipindahkan dari Anda',
                    'system'
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tugas berhasil diperbarui',
                'task_id' => $task_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui tugas']);
        }
        
    } catch (Exception $e) {
        error_log("Error updateTaskHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>