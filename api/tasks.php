<?php
// ==========================================
// API TASKS - VERSI LENGKAP + KOLOM KUSTOM
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
    case 'create': handle_create_task(); break;
    case 'get': handle_get_task(); break;
    case 'update': handle_update_task(); break;
    case 'update_status': handle_update_task_status(); break;
    case 'delete': handle_delete_task(); break;
    case 'upload_attachment': handle_upload_attachment(); break;
    case 'delete_attachment': handle_delete_attachment(); break;
    case 'move_to_column': handle_move_to_column(); break;
    case 'list_by_column': handle_list_by_column(); break;
    default: echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper Functions
function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

// ========== CREATE TASK ==========
function handle_create_task() { 
    global $pdo; 
    try { 
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
        
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, project_id, column_status, priority, assignee_id, created_by, due_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        "); 
        $stmt->execute([$title, $description, $project_id, $status, $priority, $assignee_id, $_SESSION['user_id'], $due_date]); 
        
        $task_id = $pdo->lastInsertId(); 
        
        if ($assignee_id && $assignee_id != $_SESSION['user_id']) { 
            if (function_exists('createNotification')) { 
                createNotification($assignee_id, 'Tugas Baru', $_SESSION['full_name'] . ' menugaskan Anda: ' . $title, 'assignment'); 
            } 
        } 
        
        echo json_encode(['success' => true, 'message' => 'Tugas berhasil dibuat', 'task_id' => $task_id]); 
    } catch (Exception $e) { 
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); 
    } 
}

// ========== UPDATE TASK ==========
function handle_update_task() { 
    global $pdo; 
    try { 
        $task_id = (int)($_POST['task_id'] ?? 0); 
        $title = trim($_POST['title'] ?? ''); 
        $description = trim($_POST['description'] ?? ''); 
        $priority = $_POST['priority'] ?? 'medium'; 
        $assignee_id = !empty($_POST['assignee_id']) ? (int)$_POST['assignee_id'] : null; 
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null; 
        
        if ($task_id <= 0 || empty($title)) { 
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']); 
            return; 
        } 
        
        $task = getTaskById($task_id); 
        if (!$task || !hasProjectAccess($task['project_id'], $_SESSION['user_id'])) { 
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']); 
            return; 
        } 
        
        $can_edit = ($task['created_by'] == $_SESSION['user_id'] || isProjectAdmin($task['project_id'], $_SESSION['user_id'])); 
        if (!$can_edit) { 
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan mengedit tugas ini']); 
            return; 
        } 
        
        $stmt = $pdo->prepare("
            UPDATE tasks SET title = ?, description = ?, priority = ?, assignee_id = ?, due_date = ?, updated_at = NOW() 
            WHERE id = ?
        "); 
        $success = $stmt->execute([$title, $description, $priority, $assignee_id, $due_date, $task_id]); 
        
        if ($success) { 
            echo json_encode(['success' => true, 'message' => 'Tugas berhasil diperbarui']); 
        } else { 
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui tugas']); 
        } 
    } catch (Exception $e) { 
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); 
    } 
}

// ========== UPDATE TASK STATUS ==========
function handle_update_task_status() { 
    global $pdo; 
    try { 
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
        
        $stmt = $pdo->prepare("UPDATE tasks SET column_status = ?, column_id = NULL, updated_at = NOW() WHERE id = ?"); 
        $success = $stmt->execute([$status, $task_id]); 
        
        if ($success) { 
            echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']); 
        } else { 
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']); 
        } 
    } catch (Exception $e) { 
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); 
    } 
}

// ========== DELETE TASK ==========
function handle_delete_task() { 
    global $pdo; 
    try { 
        $task_id = (int)($_POST['task_id'] ?? 0); 
        if ($task_id <= 0) return; 
        
        $task = getTaskById($task_id); 
        if (!$task) return; 
        
        $can_delete = ($task['created_by'] == $_SESSION['user_id'] || isAdmin() || isProjectOwner($task['project_id'], $_SESSION['user_id'])); 
        if (!$can_delete) return; 
        
        $pdo->prepare("DELETE FROM comments WHERE task_id = ?")->execute([$task_id]); 
        $pdo->prepare("DELETE FROM attachments WHERE task_id = ?")->execute([$task_id]); 
        $success = $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]); 
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Terhapus' : 'Gagal']); 
    } catch (Exception $e) { 
        echo json_encode(['success' => false]); 
    } 
}

// ========== MOVE TASK TO COLUMN ==========
function handle_move_to_column() {
    global $pdo;
    
    $task_id = (int)($_POST['task_id'] ?? 0);
    $target_column = $_POST['target_column'] ?? '';
    $project_id = (int)($_POST['project_id'] ?? 0);
    
    if ($task_id <= 0 || empty($target_column) || $project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    $success = moveTaskToColumn($task_id, $target_column, $project_id);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Tugas dipindahkan' : 'Gagal memindahkan tugas'
    ]);
}

// ========== LIST TASKS BY COLUMN ==========
function handle_list_by_column() {
    $project_id = (int)($_GET['project_id'] ?? 0);
    $column = $_GET['column'] ?? '';
    
    if ($project_id <= 0 || empty($column)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    if (!hasProjectAccess($project_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']);
        return;
    }
    
    $tasks = getTasksByColumn($project_id, $column);
    
    foreach ($tasks as &$task) {
        $task['priority_label'] = getPriorityLabel($task['priority']);
        $task['priority_class'] = getPriorityClass($task['priority']);
        $task['due_date_formatted'] = $task['due_date'] ? date('d M', strtotime($task['due_date'])) : null;
        $task['is_overdue'] = $task['due_date'] && strtotime($task['due_date']) < time() && $task['column_status'] != 'done';
    }
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
}

// ========== GET TASK DETAIL ==========
function handle_get_task() {
    global $pdo;
    
    try {
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
        
        // Cek akses ke proyek
        if (!hasProjectAccess($task['project_id'], $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke tugas ini']);
            return;
        }
        
        $comments = getTaskComments($task_id);
        $members = getProjectMembers($task['project_id']);
        $attachments = getTaskAttachments($task_id);
        
        $is_admin = isProjectAdmin($task['project_id'], $_SESSION['user_id']);
        $can_edit = ($task['created_by'] == $_SESSION['user_id'] || $is_admin);
        
        ob_start();
        ?>
        <div class="modal-header border-bottom px-4 py-4" style="background: var(--surface); border-bottom-color: var(--border-color) !important;">
            <div class="d-flex flex-column w-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <?php 
                        $prioBg = ['low' => '#dcfce7', 'medium' => '#fef9c3', 'high' => '#ffedd5', 'urgent' => '#fee2e2'];
                        $prioCol = ['low' => '#166534', 'medium' => '#854d0e', 'high' => '#9a3412', 'urgent' => '#991b1b'];
                        // Dark mode colors for priority badges
                        $prioBgDark = ['low' => '#14532d', 'medium' => '#713f12', 'high' => '#7c2d12', 'urgent' => '#7f1d1d'];
                        $prioColDark = ['low' => '#86efac', 'medium' => '#fde047', 'high' => '#fdba74', 'urgent' => '#fca5a5'];
                        $p = $task['priority'];
                    ?>
                    <span class="badge priority-badge" 
                          style="background: <?= $prioBg[$p] ?>; color: <?= $prioCol[$p] ?>; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;" 
                          data-priority="<?= $p ?>"
                          data-theme-light-bg="<?= $prioBg[$p] ?>"
                          data-theme-light-color="<?= $prioCol[$p] ?>"
                          data-theme-dark-bg="<?= $prioBgDark[$p] ?>"
                          data-theme-dark-color="<?= $prioColDark[$p] ?>">
                        <?= ucfirst($p == 'urgent' ? 'Urgent!' : $p) ?>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <h4 class="modal-title fw-bold mb-0" style="color: var(--text-dark);"><?= htmlspecialchars($task['title']) ?></h4>
            </div>
        </div>

        <div class="modal-body px-4 py-4" style="background: var(--surface); color: var(--text-main);">
            <ul class="nav nav-pills mb-4 gap-2" id="taskTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4 rounded-pill" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" style="transition: 0.3s; font-size: 0.9rem; color: var(--text-muted); background: transparent; border: 1px solid transparent;">
                        <i class="bi bi-info-circle me-1"></i> Detail
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 rounded-pill" id="comments-tab" data-bs-toggle="pill" data-bs-target="#comments" type="button" style="transition: 0.3s; font-size: 0.9rem; color: var(--text-muted); background: transparent; border: 1px solid transparent;">
                        <i class="bi bi-chat-text me-1"></i> Komentar (<?= count($comments) ?>)
                    </button>
                </li>
                <?php if ($can_edit): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 rounded-pill" id="edit-tab" data-bs-toggle="pill" data-bs-target="#edit" type="button" style="transition: 0.3s; font-size: 0.9rem; color: var(--text-muted); background: transparent; border: 1px solid transparent;">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="tab-content" id="taskTabContent">
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="p-4 rounded-4 mb-4" style="background: var(--surface-hover); border: 1px solid var(--border-color);">
                        <h6 class="fw-bold mb-3 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px; color: var(--text-muted);">Deskripsi Tugas</h6>
                        <p class="mb-0" style="line-height: 1.6; font-size: 0.95rem; color: var(--text-main);">
                            <?= nl2br(htmlspecialchars($task['description'] ?: 'Tidak ada deskripsi yang ditambahkan untuk tugas ini.')) ?>
                        </p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px; color: var(--text-muted) !important;">Status</small>
                                <?php 
                                    $statFormat = str_replace('_', ' ', $task['column_status']);
                                    $statCol = $task['column_status'] == 'done' ? 'text-success' : 'text-primary';
                                ?>
                                <span class="fw-bold <?= $statCol ?>" style="font-size: 0.95rem; color: <?= $task['column_status'] == 'done' ? 'var(--success)' : 'var(--primary)' ?>;"><?= strtoupper($statFormat) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px; color: var(--text-muted) !important;">Batas Waktu</small>
                                <?php if ($task['due_date']): ?>
                                    <?php 
                                        $isOverdue = strtotime($task['due_date']) < time() && $task['column_status'] != 'done';
                                        $dueDateClass = $isOverdue ? 'text-danger' : '';
                                    ?>
                                    <span class="fw-bold <?= $dueDateClass ?>" style="font-size: 0.95rem; color: <?= $isOverdue ? 'var(--danger)' : 'var(--text-dark)' ?>;">
                                        <i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($task['due_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="fw-bold" style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px; color: var(--text-muted) !important;">Ditugaskan Ke</small>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($task['assignee_name']): ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 28px; height: 28px; font-size: 0.7rem; background: <?= getAvatarColor($task['assignee_name']) ?>;">
                                            <?= getInitials($task['assignee_name']) ?>
                                        </div>
                                        <span class="fw-bold" style="font-size: 0.9rem; color: var(--text-dark);"><?= htmlspecialchars($task['assignee_name']) ?></span>
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: var(--surface-hover) !important; border-color: var(--border-color) !important;">
                                            <i class="bi bi-person" style="color: var(--text-muted);"></i>
                                        </div>
                                        <span class="fw-bold" style="color: var(--text-muted); font-size: 0.9rem;">Belum ada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px; color: var(--text-muted) !important;">Dibuat Oleh</small>
                                <span class="fw-bold d-block" style="font-size: 0.9rem; color: var(--text-dark);">
                                    <i class="bi bi-pencil-square me-1" style="color: var(--text-muted);"></i> <?= htmlspecialchars($task['creator_name']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top" style="border-top-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px; color: var(--text-muted);">
                                <i class="bi bi-paperclip me-1"></i> Lampiran File
                            </h6>
                            
                            <label class="btn btn-sm btn-soft-primary rounded-pill mb-0 fw-bold" style="cursor: pointer; padding: 0.4rem 1rem; background: var(--primary-light); color: var(--primary); border: none;">
                                <i class="bi bi-cloud-arrow-up me-1"></i> Upload
                                <input type="file" class="d-none" id="fileUploadInput" onchange="uploadAttachment(<?= $task_id ?>, this)">
                            </label>
                        </div>
                        
                        <div class="d-flex flex-column gap-2">
                            <?php if (empty($attachments)): ?>
                                <div class="text-center p-3 rounded-4" style="border: 1px dashed var(--border-color); background: var(--surface-hover);">
                                    <small class="text-muted fw-bold">Belum ada file terlampir</small>
                                </div>
                            <?php else: ?>
                                <?php foreach($attachments as $file): ?>
                                    <?php 
                                        $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
                                        $icon = 'bi-file-earmark'; 
                                        $bg = '#f1f5f9'; 
                                        $col = '#64748b';
                                        
                                        if(in_array($ext, ['jpg','jpeg','png','gif','webp'])) { 
                                            $icon = 'bi-file-image'; 
                                            $bg = '#e0e7ff'; 
                                            $col = '#4f46e5'; 
                                        }
                                        elseif(in_array($ext, ['pdf'])) { 
                                            $icon = 'bi-file-pdf'; 
                                            $bg = '#fee2e2'; 
                                            $col = '#ef4444'; 
                                        }
                                        elseif(in_array($ext, ['doc','docx','txt'])) { 
                                            $icon = 'bi-file-text'; 
                                            $bg = '#dcfce7'; 
                                            $col = '#10b981'; 
                                        }
                                        elseif(in_array($ext, ['xls','xlsx','csv'])) { 
                                            $icon = 'bi-file-spreadsheet'; 
                                            $bg = '#dcfce7'; 
                                            $col = '#10b981'; 
                                        }
                                        elseif(in_array($ext, ['zip','rar','7z'])) { 
                                            $icon = 'bi-file-zip'; 
                                            $bg = '#fef3c7'; 
                                            $col = '#d97706'; 
                                        }
                                        
                                        // Dark mode colors
                                        $bgDark = '#1e293b';
                                        $colDark = '#94a3b8';
                                        
                                        if(in_array($ext, ['jpg','jpeg','png','gif','webp'])) { 
                                            $bgDark = '#1e1b4b'; 
                                            $colDark = '#818cf8'; 
                                        }
                                        elseif(in_array($ext, ['pdf'])) { 
                                            $bgDark = '#450a0a'; 
                                            $colDark = '#f87171'; 
                                        }
                                        elseif(in_array($ext, ['doc','docx','txt'])) { 
                                            $bgDark = '#064e3b'; 
                                            $colDark = '#34d399'; 
                                        }
                                        elseif(in_array($ext, ['xls','xlsx','csv'])) { 
                                            $bgDark = '#064e3b'; 
                                            $colDark = '#34d399'; 
                                        }
                                        elseif(in_array($ext, ['zip','rar','7z'])) { 
                                            $bgDark = '#422006'; 
                                            $colDark = '#fbbf24'; 
                                        }
                                    ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3 attachment-item" 
                                         style="border: 1px solid var(--border-color); background: var(--surface); transition: 0.2s;">
                                        <div class="d-flex align-items-center gap-3 overflow-hidden">
                                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                                                 style="width: 40px; height: 40px; background: <?= $bg ?>; color: <?= $col ?>;"
                                                 data-theme-light-bg="<?= $bg ?>"
                                                 data-theme-light-color="<?= $col ?>"
                                                 data-theme-dark-bg="<?= $bgDark ?>"
                                                 data-theme-dark-color="<?= $colDark ?>">
                                                <i class="bi <?= $icon ?> fs-5"></i>
                                            </div>
                                            <div class="lh-sm text-truncate">
                                                <div class="fw-bold text-truncate" style="font-size: 0.85rem; color: var(--text-dark);" title="<?= htmlspecialchars($file['filename']) ?>">
                                                    <?= htmlspecialchars($file['filename']) ?>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.7rem; color: var(--text-muted) !important;">
                                                    Oleh: <?= htmlspecialchars($file['uploaded_by_name'] ?? 'Unknown') ?> • <?= date('d M Y', strtotime($file['uploaded_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                            <a href="../uploads/tasks/<?= htmlspecialchars($file['filepath']) ?>" target="_blank" class="btn btn-light btn-sm rounded-circle shadow-none" 
                                               style="background: var(--surface-hover); color: var(--primary); border: 1px solid var(--border-color);" 
                                               title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if ($can_edit || $file['uploaded_by'] == $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-light btn-sm rounded-circle shadow-none" 
                                                        style="background: var(--surface-hover); color: var(--danger); border: 1px solid var(--border-color);" 
                                                        title="Hapus" onclick="deleteAttachment(<?= $file['id'] ?>, <?= $task_id ?>)">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="comments" role="tabpanel">
                    <div style="height: 350px; overflow-y: auto; padding-right: 10px;" class="mb-4 d-flex flex-column">
                        <?php if (empty($comments)): ?>
                            <div class="text-center m-auto">
                                <div class="rounded-circle mx-auto mb-3 d-flex justify-content-center align-items-center" style="width: 80px; height: 80px; background: var(--surface-hover);">
                                    <i class="bi bi-chat-text" style="color: var(--text-muted); opacity: 0.5; font-size: 2.5rem;"></i>
                                </div>
                                <h6 class="fw-bold" style="color: var(--text-dark);">Belum ada diskusi</h6>
                                <p class="text-muted small" style="color: var(--text-muted) !important;">Jadilah yang pertama memberikan komentar.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <?php $is_me = ($comment['user_id'] == $_SESSION['user_id']); ?>
                                <div class="d-flex gap-3 mb-4 <?= $is_me ? 'flex-row-reverse text-end' : '' ?>">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow-sm" 
                                         style="width: 38px; height: 38px; font-size: 0.85rem; background: <?= $is_me ? 'var(--secondary)' : 'var(--primary)' ?>;">
                                        <?= getInitials($comment['full_name']) ?>
                                    </div>
                                    <div style="max-width: 85%;">
                                        <div class="d-flex align-items-baseline gap-2 mb-1 <?= $is_me ? 'justify-content-end' : '' ?>">
                                            <strong style="color: var(--text-dark); font-size: 0.85rem;"><?= $is_me ? 'Kamu' : htmlspecialchars($comment['full_name']) ?></strong>
                                            <small class="text-muted" style="color: var(--text-muted) !important; font-size: 0.7rem;"><?= timeAgo($comment['created_at']) ?></small>
                                        </div>
                                        <div class="p-3 rounded-4 shadow-sm comment-bubble" 
                                             style="background: <?= $is_me ? 'var(--primary)' : 'var(--surface-hover)' ?>; color: <?= $is_me ? 'white' : 'var(--text-main)' ?>; font-size: 0.9rem; line-height: 1.5; border-top-<?= $is_me ? 'right' : 'left' ?>-radius: 4px !important;">
                                            <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <form onsubmit="event.preventDefault(); addComment(<?= $task_id ?>, this);" class="mt-2 border-top pt-3" style="border-top-color: var(--border-color) !important;">
                        <div class="position-relative">
                            <input type="text" class="form-control rounded-pill pe-5" name="content" placeholder="Tulis komentar..." required 
                                   style="padding: 1rem 1.5rem; background: var(--surface-hover); border: 1px solid var(--border-color); color: var(--text-dark);">
                            <button class="btn btn-primary rounded-circle position-absolute shadow-none" type="submit" 
                                    style="top: 7px; right: 8px; width: 38px; height: 38px; padding: 0; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-send-fill" style="margin-left: -2px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if ($can_edit): ?>
                <div class="tab-pane fade" id="edit" role="tabpanel">
                    <form id="editTaskForm" onsubmit="event.preventDefault(); updateTask(<?= $task_id ?>);">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px; color: var(--text-muted);">Judul Tugas</label>
                            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px; color: var(--text-muted);">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($task['description']) ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-4">
                                <label class="form-label fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px; color: var(--text-muted);">Prioritas</label>
                                <select class="form-select fw-bold" name="priority" style="color: var(--text-dark); background: var(--surface-hover); border-color: var(--border-color);">
                                    <option value="low" <?= $task['priority'] == 'low' ? 'selected' : '' ?> style="color: #166534;">Low</option>
                                    <option value="medium" <?= $task['priority'] == 'medium' ? 'selected' : '' ?> style="color: #854d0e;">Medium</option>
                                    <option value="high" <?= $task['priority'] == 'high' ? 'selected' : '' ?> style="color: #9a3412;">High</option>
                                    <option value="urgent" <?= $task['priority'] == 'urgent' ? 'selected' : '' ?> style="color: #991b1b;">Urgent</option>
                                </select>
                            </div>
                            <div class="col-6 mb-4">
                                <label class="form-label fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px; color: var(--text-muted);">Ditugaskan Ke</label>
                                <select class="form-select" name="assignee_id" style="color: var(--text-dark); background: var(--surface-hover); border-color: var(--border-color);">
                                    <option value="">-- Kosong --</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?= $member['id'] ?>" <?= $member['id'] == $task['assignee_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($member['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px; color: var(--text-muted);">Tenggat Waktu</label>
                            <input type="date" class="form-control" name="due_date" value="<?= $task['due_date'] ?>" style="color: var(--text-dark); background: var(--surface-hover); border-color: var(--border-color);">
                        </div>
                        <div class="d-grid mt-2">
                            <button type="submit" class="btn btn-primary rounded-pill py-3 fw-bold" style="letter-spacing: 0.5px;">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="modal-footer px-4 py-3 d-flex justify-content-between align-items-center" style="background: var(--surface-hover); border-radius: 0 0 var(--radius-lg) var(--radius-lg); border-top: 1px solid var(--border-color);">
            <?php if ($can_edit): ?>
                <button type="button" class="btn btn-link text-danger text-decoration-none px-0 fw-bold d-flex align-items-center gap-2" onclick="deleteTask(<?= $task_id ?>)">
                    <i class="bi bi-trash3"></i> Hapus Tugas
                </button>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <button type="button" class="btn border fw-bold rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" 
                    style="background: var(--surface); color: var(--text-dark); border-color: var(--border-color) !important;">
                Tutup
            </button>
        </div>
        
        <style>
            /* Dark mode styles untuk tab navigasi */
            [data-theme="dark"] #taskTab .nav-link {
                color: var(--text-muted) !important;
                background: transparent !important;
            }
            [data-theme="dark"] #taskTab .nav-link:hover {
                background: var(--surface-hover) !important;
            }
            [data-theme="dark"] #taskTab .nav-link.active {
                background: var(--primary-light) !important;
                color: var(--primary) !important;
                border-color: rgba(129, 140, 248, 0.2) !important;
            }
            
            /* Dark mode styles untuk priority badge */
            [data-theme="dark"] .priority-badge[data-priority="low"] {
                background: #14532d !important;
                color: #86efac !important;
            }
            [data-theme="dark"] .priority-badge[data-priority="medium"] {
                background: #713f12 !important;
                color: #fde047 !important;
            }
            [data-theme="dark"] .priority-badge[data-priority="high"] {
                background: #7c2d12 !important;
                color: #fdba74 !important;
            }
            [data-theme="dark"] .priority-badge[data-priority="urgent"] {
                background: #7f1d1d !important;
                color: #fca5a5 !important;
            }
            
            /* Dark mode styles untuk attachment icon */
            [data-theme="dark"] .attachment-item [style*="background:"] {
                background: var(--surface-hover) !important;
            }
            
            /* Dark mode styles untuk form selects */
            [data-theme="dark"] select.form-select option {
                background: var(--surface);
                color: var(--text-dark);
            }
            
            /* Dark mode styles untuk comment bubble */
            [data-theme="dark"] .comment-bubble {
                background: var(--surface-hover) !important;
                color: var(--text-main) !important;
            }
            
            /* Dark mode styles untuk modal footer button */
            [data-theme="dark"] .modal-footer .btn.border {
                background: var(--surface) !important;
                border-color: var(--border-color) !important;
                color: var(--text-dark) !important;
            }
            
            /* Dark mode styles untuk text colors */
            [data-theme="dark"] .text-muted {
                color: var(--text-muted) !important;
            }
            [data-theme="dark"] .text-primary {
                color: var(--primary) !important;
            }
            [data-theme="dark"] .text-success {
                color: var(--success) !important;
            }
            [data-theme="dark"] .text-danger {
                color: var(--danger) !important;
            }
            [data-theme="dark"] .text-warning {
                color: var(--warning) !important;
            }
            
            /* Dark mode styles untuk border */
            [data-theme="dark"] .border,
            [data-theme="dark"] .border-top,
            [data-theme="dark"] .border-bottom {
                border-color: var(--border-color) !important;
            }
            
            /* Dark mode styles untuk background */
            [data-theme="dark"] [style*="background: #f8fafc"],
            [data-theme="dark"] [style*="background:#f8fafc"] {
                background: var(--surface-hover) !important;
            }
            [data-theme="dark"] [style*="background: #f1f5f9"],
            [data-theme="dark"] [style*="background:#f1f5f9"] {
                background: var(--surface-hover) !important;
            }
            [data-theme="dark"] [style*="background: #e2e8f0"],
            [data-theme="dark"] [style*="background:#e2e8f0"] {
                background: var(--border-color) !important;
            }
            
            /* Dark mode styles untuk form inputs */
            [data-theme="dark"] .form-control,
            [data-theme="dark"] .form-select {
                background: var(--surface-hover);
                border-color: var(--border-color);
                color: var(--text-dark);
            }
            [data-theme="dark"] .form-control:focus,
            [data-theme="dark"] .form-select:focus {
                background: var(--surface);
                border-color: var(--primary);
            }
        </style>
        <?php
        $html = ob_get_clean();
        echo json_encode(['success' => true, 'html' => $html]);
        
    } catch (Exception $e) {
        error_log("Error handle_get_task: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ========== UPLOAD ATTACHMENT ==========
function handle_upload_attachment() {
    global $pdo;
    try {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); 
            return;
        }
        
        $file = $_FILES['file'];
        $originalFileName = basename($file['name']);
        $fileSize = $file['size'];
        
        if ($fileSize > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 10MB']); 
            return;
        }
        
        $uniqueFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $originalFileName);
        
        $uploadDir = __DIR__ . '/../../uploads/tasks/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $destination = $uploadDir . $uniqueFileName;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $stmt = $pdo->prepare("INSERT INTO attachments (task_id, filename, filepath, uploaded_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$task_id, $originalFileName, $uniqueFileName, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Lampiran berhasil diunggah']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
        }
    } catch (Exception $e) {
        error_log("Upload Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    }
}

// ========== DELETE ATTACHMENT ==========
function handle_delete_attachment() {
    global $pdo;
    try {
        $attachment_id = (int)($_POST['attachment_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
        $stmt->execute([$attachment_id]);
        $attachment = $stmt->fetch();
        
        if (!$attachment) {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); 
            return;
        }
        
        $filePath = __DIR__ . '/../../uploads/tasks/' . $attachment['filepath'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $stmtDel = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
        $success = $stmtDel->execute([$attachment_id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'File dihapus' : 'Gagal menghapus']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    }
}
?>