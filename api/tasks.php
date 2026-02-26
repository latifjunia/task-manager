<?php
// ==========================================
// API TASKS - VERSI FINAL + LAMPIRAN (SESUAI DATABASE)
// ==========================================
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

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
    default: echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper Format Ukuran File
function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

// FUNGSI CRUD TASKS
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
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, project_id, column_status, priority, assignee_id, created_by, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"); 
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
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, assignee_id = ?, due_date = ?, updated_at = NOW() WHERE id = ?"); 
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
        $stmt = $pdo->prepare("UPDATE tasks SET column_status = ?, updated_at = NOW() WHERE id = ?"); 
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

// FUNGSI GET TASK DENGAN PERBAIKAN DARK MODE
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
        
        $comments = function_exists('getTaskComments') ? getTaskComments($task_id) : [];
        $members = function_exists('getProjectMembers') ? getProjectMembers($task['project_id']) : [];
        
        $is_admin = isProjectAdmin($task['project_id'], $_SESSION['user_id']);
        $can_edit = ($task['created_by'] == $_SESSION['user_id'] || $is_admin);
        
        // MENGAMBIL DATA LAMPIRAN DARI DATABASE
        $stmtAtt = $pdo->prepare("SELECT a.*, u.full_name FROM attachments a JOIN users u ON a.uploaded_by = u.id WHERE a.task_id = ? ORDER BY a.uploaded_at DESC");
        $stmtAtt->execute([$task_id]);
        $attachments = $stmtAtt->fetchAll();
        
        ob_start();
        ?>
        <div class="modal-header border-bottom px-4 py-4" style="background: var(--surface); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
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
                    <span class="badge priority-badge" style="background: <?= $prioBg[$p] ?>; color: <?= $prioCol[$p] ?>; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;" 
                          data-priority="<?= $p ?>">
                        <?= ucfirst($p == 'urgent' ? 'Urgent!' : $p) ?>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <h4 class="modal-title fw-bold mb-0 lh-base" style="font-size: 1.4rem; color: var(--text-dark);"><?= htmlspecialchars($task['title']) ?></h4>
            </div>
        </div>

        <div class="modal-body px-4 py-4">
            <ul class="nav nav-pills mb-4 gap-2" id="taskTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4 rounded-pill" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" style="transition: 0.3s; font-size: 0.9rem;">
                        <i class="bi bi-info-circle me-1"></i> Detail
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 rounded-pill" id="comments-tab" data-bs-toggle="pill" data-bs-target="#comments" type="button" style="transition: 0.3s; font-size: 0.9rem;">
                        <i class="bi bi-chat-text me-1"></i> Komentar (<?= count($comments) ?>)
                    </button>
                </li>
                <?php if ($can_edit): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 rounded-pill" id="edit-tab" data-bs-toggle="pill" data-bs-target="#edit" type="button" style="transition: 0.3s; font-size: 0.9rem;">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="tab-content" id="taskTabContent">
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="bg-light p-4 rounded-4 mb-4" style="border: 1px solid var(--border-color);">
                        <h6 class="fw-bold mb-3 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px; color: var(--text-muted);">Deskripsi Tugas</h6>
                        <p class="mb-0" style="line-height: 1.6; font-size: 0.95rem; color: var(--text-main);">
                            <?= nl2br(htmlspecialchars($task['description'] ?: 'Tidak ada deskripsi yang ditambahkan untuk tugas ini.')) ?>
                        </p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100 info-box" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Status</small>
                                <?php 
                                    $statFormat = str_replace('_', ' ', $task['column_status']);
                                    $statCol = $task['column_status'] == 'done' ? 'text-success' : 'text-primary';
                                ?>
                                <span class="fw-bold <?= $statCol ?> fs-6"><?= strtoupper($statFormat) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100 info-box" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Batas Waktu</small>
                                <?php if ($task['due_date']): ?>
                                    <?php 
                                        $isOverdue = strtotime($task['due_date']) < time() && $task['column_status'] != 'done';
                                        $dueDateClass = $isOverdue ? 'text-danger' : 'deadline-text';
                                    ?>
                                    <span class="fw-bold <?= $dueDateClass ?>" style="font-size: 0.95rem;">
                                        <i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($task['due_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fw-bold">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100 info-box" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Ditugaskan Ke</small>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($task['assignee_name']): ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 28px; height: 28px; font-size: 0.7rem; background: var(--primary);">
                                            <?= strtoupper(substr($task['assignee_name'], 0, 2)) ?>
                                        </div>
                                        <span class="fw-bold assignee-text text-truncate" style="font-size: 0.9rem; max-width: 120px; color: var(--text-dark);"><?= htmlspecialchars($task['assignee_name']) ?></span>
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light border text-muted d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: var(--surface) !important;"><i class="bi bi-person"></i></div>
                                        <span class="fw-bold text-muted" style="font-size: 0.9rem;">Belum ada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100 info-box" style="border: 1px solid var(--border-color); background: var(--surface-hover);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Dibuat Oleh</small>
                                <span class="fw-bold text-truncate d-block" style="font-size: 0.9rem; max-width: 150px; color: var(--text-dark);">
                                    <i class="bi bi-pencil-square text-muted me-1"></i> <?= htmlspecialchars($task['creator_name']) ?>
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
                                        $icon = 'bi-file-earmark'; $bg = '#f1f5f9'; $col = '#64748b';
                                        
                                        if(in_array($ext, ['jpg','jpeg','png','gif'])) { $icon = 'bi-file-image'; $bg = '#e0e7ff'; $col = '#4f46e5'; }
                                        elseif(in_array($ext, ['pdf'])) { $icon = 'bi-filetype-pdf'; $bg = '#fee2e2'; $col = '#ef4444'; }
                                        elseif(in_array($ext, ['doc','docx','txt'])) { $icon = 'bi-file-earmark-text'; $bg = '#dcfce7'; $col = '#10b981'; }
                                        elseif(in_array($ext, ['xls','xlsx','csv'])) { $icon = 'bi-file-earmark-spreadsheet'; $bg = '#dcfce7'; $col = '#10b981'; }
                                        elseif(in_array($ext, ['zip','rar','7z'])) { $icon = 'bi-file-earmark-zip'; $bg = '#fef3c7'; $col = '#d97706'; }
                                    ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3 attachment-item" style="border: 1px solid var(--border-color); background: var(--surface); transition: 0.2s;">
                                        <div class="d-flex align-items-center gap-3 overflow-hidden">
                                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: <?= $bg ?>; color: <?= $col ?>;">
                                                <i class="bi <?= $icon ?> fs-5"></i>
                                            </div>
                                            <div class="lh-sm text-truncate">
                                                <div class="fw-bold text-truncate" style="font-size: 0.85rem; color: var(--text-dark);" title="<?= htmlspecialchars($file['filename']) ?>">
                                                    <?= htmlspecialchars($file['filename']) ?>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.7rem;">
                                                    Oleh: <?= htmlspecialchars($file['full_name']) ?> • <?= date('d M Y', strtotime($file['uploaded_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                            <a href="../uploads/tasks/<?= htmlspecialchars($file['filepath']) ?>" target="_blank" class="btn btn-light btn-sm rounded-circle shadow-none" style="background: var(--surface-hover); color: var(--primary); border-color: var(--border-color);" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if ($can_edit || $file['uploaded_by'] == $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-light btn-sm rounded-circle shadow-none" style="background: var(--surface-hover); color: var(--danger); border-color: var(--border-color);" title="Hapus" onclick="deleteAttachment(<?= $file['id'] ?>, <?= $task_id ?>)">
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
                    <div style="height: 350px; overflow-y: auto; padding-right: 10px;" class="mb-4 custom-scrollbar d-flex flex-column">
                        <?php if (empty($comments)): ?>
                            <div class="text-center m-auto">
                                <div class="rounded-circle mx-auto mb-3 d-flex justify-content-center align-items-center" style="width: 80px; height: 80px; background: var(--surface-hover);">
                                    <i class="bi bi-chat-text" style="color: var(--text-muted); opacity: 0.5; font-size: 2.5rem;"></i>
                                </div>
                                <h6 class="fw-bold" style="color: var(--text-dark);">Belum ada diskusi</h6>
                                <p class="text-muted small">Jadilah yang pertama memberikan komentar.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <?php $is_me = ($comment['user_id'] == $_SESSION['user_id']); ?>
                                <div class="d-flex gap-3 mb-4 <?= $is_me ? 'flex-row-reverse text-end' : '' ?>">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow-sm" style="width: 38px; height: 38px; font-size: 0.85rem; background: <?= $is_me ? 'var(--secondary)' : 'var(--primary)' ?>;">
                                        <?= strtoupper(substr($comment['full_name'], 0, 2)) ?>
                                    </div>
                                    <div style="max-width: 85%;">
                                        <div class="d-flex align-items-baseline gap-2 mb-1 <?= $is_me ? 'justify-content-end' : '' ?>">
                                            <strong style="color: var(--text-dark); font-size: 0.85rem;"><?= $is_me ? 'Kamu' : htmlspecialchars($comment['full_name']) ?></strong>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= function_exists('timeAgo') ? timeAgo($comment['created_at']) : $comment['created_at'] ?></small>
                                        </div>
                                        <div class="p-3 rounded-4 shadow-sm comment-bubble" style="background: <?= $is_me ? 'var(--primary)' : 'var(--surface-hover)' ?>; color: <?= $is_me ? 'white' : 'var(--text-main)' ?>; font-size: 0.9rem; line-height: 1.5; border-top-<?= $is_me ? 'right' : 'left' ?>-radius: 4px !important;">
                                            <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <form onsubmit="event.preventDefault(); addComment(<?= $task_id ?>, this);" class="mt-2 border-top pt-3" style="border-top-color: var(--border-color) !important;">
                        <div class="position-relative">
                            <input type="text" class="form-control rounded-pill pe-5" name="content" placeholder="Tulis komentar..." required style="padding: 1rem 1.5rem; background: var(--surface-hover); border: 1px solid var(--border-color); color: var(--text-dark);">
                            <button class="btn btn-primary rounded-circle position-absolute shadow-none" type="submit" style="top: 7px; right: 8px; width: 38px; height: 38px; padding: 0; display:flex; align-items:center; justify-content:center;">
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
                                <select class="form-select fw-bold" name="priority">
                                    <option value="low" <?= $task['priority'] == 'low' ? 'selected' : '' ?> style="color: #166534;">Low</option>
                                    <option value="medium" <?= $task['priority'] == 'medium' ? 'selected' : '' ?> style="color: #854d0e;">Medium</option>
                                    <option value="high" <?= $task['priority'] == 'high' ? 'selected' : '' ?> style="color: #9a3412;">High</option>
                                    <option value="urgent" <?= $task['priority'] == 'urgent' ? 'selected' : '' ?> style="color: #991b1b;">Urgent</option>
                                </select>
                            </div>
                            <div class="col-6 mb-4">
                                <label class="form-label fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px; color: var(--text-muted);">Ditugaskan Ke</label>
                                <select class="form-select" name="assignee_id">
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
                            <input type="date" class="form-control" name="due_date" value="<?= $task['due_date'] ?>">
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
            <button type="button" class="btn border fw-bold rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" style="background: var(--surface); color: var(--text-dark); border-color: var(--border-color) !important;">Tutup</button>
        </div>
        
        <style>
            .custom-scrollbar::-webkit-scrollbar { width: 6px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 10px; }
            
            #taskTab .nav-link { 
                color: var(--text-muted); 
                background: transparent; 
                border: 1px solid transparent; 
            }
            #taskTab .nav-link:hover { 
                background: var(--surface-hover); 
            }
            #taskTab .nav-link.active { 
                background: var(--primary-light); 
                color: var(--primary); 
                border-color: rgba(99, 102, 241, 0.2); 
            }
            
            /* Dark mode styles for modal content */
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
            
            [data-theme="dark"] .info-box {
                background: #1e293b !important;
                border-color: #334155 !important;
            }
            
            [data-theme="dark"] .deadline-text {
                color: #f1f5f9 !important;
            }
            
            [data-theme="dark"] .assignee-text {
                color: #f1f5f9 !important;
            }
            
            [data-theme="dark"] .attachment-item {
                background: #1e293b !important;
                border-color: #334155 !important;
            }
            
            [data-theme="dark"] .comment-bubble {
                background: #1e293b !important;
                color: #cbd5e1 !important;
            }
            
            [data-theme="dark"] .modal-footer button.btn.border {
                background: #1e293b !important;
                border-color: #334155 !important;
                color: #f1f5f9 !important;
            }
            
            [data-theme="dark"] select.form-select option {
                background: #1e293b;
                color: #f1f5f9;
            }
            
            [data-theme="dark"] select.form-select option:hover {
                background: #334155;
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

// FUNGSI UPLOAD LAMPIRAN
function handle_upload_attachment() {
    global $pdo;
    try {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau terjadi error']); return;
        }
        
        $file = $_FILES['file'];
        $originalFileName = basename($file['name']);
        $fileSize = $file['size'];
        
        // Batasi ukuran file maksimal 10MB
        if ($fileSize > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 10MB']); return;
        }
        
        // Buat nama unik
        $uniqueFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $originalFileName);
        
        // Path ke folder uploads/tasks
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
            echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file ke server']);
        }
    } catch (Exception $e) {
        error_log("Upload Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
}

// FUNGSI HAPUS LAMPIRAN
function handle_delete_attachment() {
    global $pdo;
    try {
        $attachment_id = (int)($_POST['attachment_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
        $stmt->execute([$attachment_id]);
        $attachment = $stmt->fetch();
        
        if (!$attachment) {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); return;
        }
        
        // Hapus file fisik dari server
        $filePath = __DIR__ . '/../../uploads/tasks/' . $attachment['filepath'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Hapus dari database
        $stmtDel = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
        $success = $stmtDel->execute([$attachment_id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'File dihapus' : 'Gagal menghapus file']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    }
}
?>