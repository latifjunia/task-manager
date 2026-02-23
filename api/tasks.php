<?php
// ==========================================
// API TASKS - VERSI FINAL (FIX)
// ==========================================

// 1. Matikan tampilan error ke layar (agar tidak merusak JSON)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// 2. Tetap catat error ke file log server
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 3. Header JSON
header('Content-Type: application/json; charset=utf-8');

// 4. Load file pendukung (Gunakan __DIR__ agar path absolut dan aman)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek Login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// Routing ke fungsi internal (Prefix handle_ agar tidak bentrok dengan functions.php)
switch ($action) {
    case 'create':
        handle_create_task();
        break;
    case 'get':
        handle_get_task();
        break;
    case 'update':
        handle_update_task();
        break;
    case 'update_status':
        handle_update_task_status();
        break;
    case 'delete':
        handle_delete_task();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// ==========================================
// FUNGSI-FUNGSI LOGIKA (BACKEND)
// ==========================================

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
        
        // Kirim Notifikasi (Jika fungsi tersedia)
        if ($assignee_id && $assignee_id != $_SESSION['user_id']) {
            if (function_exists('createNotification')) {
                createNotification(
                    $assignee_id,
                    'Tugas Baru',
                    $_SESSION['full_name'] . ' menugaskan Anda: ' . $title,
                    'assignment'
                );
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Tugas berhasil dibuat', 'task_id' => $task_id]);
        
    } catch (Exception $e) {
        error_log("Error handle_create_task: " . $e->getMessage());
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
        
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, priority = ?, assignee_id = ?, due_date = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$title, $description, $priority, $assignee_id, $due_date, $task_id]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Tugas berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui tugas']);
        }
        
    } catch (Exception $e) {
        error_log("Error handle_update_task: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

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
        
        // Ambil Data Tambahan (Safe Check jika fungsi tidak ada)
        $comments = function_exists('getTaskComments') ? getTaskComments($task_id) : [];
        $members = function_exists('getProjectMembers') ? getProjectMembers($task['project_id']) : [];
        
        $is_admin = isProjectAdmin($task['project_id'], $_SESSION['user_id']);
        $can_edit = ($task['created_by'] == $_SESSION['user_id'] || $is_admin);
        
        // BUFFER OUTPUT HTML UNTUK TAMPILAN ESTETIK
        ob_start();
        ?>
        <div class="modal-header border-bottom px-4 py-4" style="background: var(--surface); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
            <div class="d-flex flex-column w-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <?php 
                        $prioBg = ['low' => '#dcfce7', 'medium' => '#fef9c3', 'high' => '#ffedd5', 'urgent' => '#fee2e2'];
                        $prioCol = ['low' => '#166534', 'medium' => '#854d0e', 'high' => '#9a3412', 'urgent' => '#991b1b'];
                        $p = $task['priority'];
                    ?>
                    <span class="badge" style="background: <?= $prioBg[$p] ?>; color: <?= $prioCol[$p] ?>; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <?= ucfirst($p == 'urgent' ? 'Urgent!' : $p) ?>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <h4 class="modal-title fw-bold text-dark mb-0 lh-base" style="font-size: 1.4rem;"><?= htmlspecialchars($task['title']) ?></h4>
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
                        <h6 class="fw-bold mb-3 text-dark text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Deskripsi Tugas</h6>
                        <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.95rem;">
                            <?= nl2br(htmlspecialchars($task['description'] ?: 'Tidak ada deskripsi yang ditambahkan untuk tugas ini.')) ?>
                        </p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="background: #f8fafc; border: 1px solid var(--border-color);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Status</small>
                                <?php 
                                    $statFormat = str_replace('_', ' ', $task['column_status']);
                                    $statCol = $task['column_status'] == 'done' ? 'text-success' : 'text-primary';
                                ?>
                                <span class="fw-bold <?= $statCol ?> fs-6"><?= strtoupper($statFormat) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="background: #f8fafc; border: 1px solid var(--border-color);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Batas Waktu</small>
                                <?php if ($task['due_date']): ?>
                                    <span class="fw-bold <?= strtotime($task['due_date']) < time() && $task['column_status'] != 'done' ? 'text-danger' : 'text-dark' ?>" style="font-size: 0.95rem;">
                                        <i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($task['due_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fw-bold">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="background: #f8fafc; border: 1px solid var(--border-color);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Ditugaskan Ke</small>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($task['assignee_name']): ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 28px; height: 28px; font-size: 0.7rem; background: var(--primary);">
                                            <?= strtoupper(substr($task['assignee_name'], 0, 2)) ?>
                                        </div>
                                        <span class="fw-bold text-dark text-truncate" style="font-size: 0.9rem; max-width: 120px;"><?= htmlspecialchars($task['assignee_name']) ?></span>
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light border text-muted d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-person"></i></div>
                                        <span class="fw-bold text-muted" style="font-size: 0.9rem;">Belum ada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="background: #f8fafc; border: 1px solid var(--border-color);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Dibuat Oleh</small>
                                <span class="fw-bold text-dark text-truncate d-block" style="font-size: 0.9rem; max-width: 150px;">
                                    <i class="bi bi-pencil-square text-muted me-1"></i> <?= htmlspecialchars($task['creator_name']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="comments" role="tabpanel">
                    <div style="height: 350px; overflow-y: auto; padding-right: 10px;" class="mb-4 custom-scrollbar d-flex flex-column">
                        <?php if (empty($comments)): ?>
                            <div class="text-center m-auto">
                                <div class="bg-light rounded-circle mx-auto mb-3 d-flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                                    <i class="bi bi-chat-text text-muted opacity-50" style="font-size: 2.5rem;"></i>
                                </div>
                                <h6 class="fw-bold text-dark">Belum ada diskusi</h6>
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
                                            <strong class="text-dark" style="font-size: 0.85rem;"><?= $is_me ? 'Kamu' : htmlspecialchars($comment['full_name']) ?></strong>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= function_exists('timeAgo') ? timeAgo($comment['created_at']) : $comment['created_at'] ?></small>
                                        </div>
                                        <div class="p-3 rounded-4 shadow-sm" style="background: <?= $is_me ? 'var(--primary)' : '#f1f5f9' ?>; color: <?= $is_me ? 'white' : 'var(--text-main)' ?>; font-size: 0.9rem; line-height: 1.5; border-top-<?= $is_me ? 'right' : 'left' ?>-radius: 4px !important;">
                                            <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <form onsubmit="event.preventDefault(); addComment(<?= $task_id ?>, this);" class="mt-2 border-top pt-3">
                        <div class="position-relative">
                            <input type="text" class="form-control rounded-pill pe-5" name="content" placeholder="Tulis komentar..." required style="padding: 1rem 1.5rem; background: #f8fafc; border: 1px solid var(--border-color); box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
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
                            <label class="form-label text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Judul Tugas</label>
                            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($task['description']) ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-4">
                                <label class="form-label text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Prioritas</label>
                                <select class="form-select fw-bold" name="priority">
                                    <option value="low" <?= $task['priority'] == 'low' ? 'selected' : '' ?> class="text-success">Low</option>
                                    <option value="medium" <?= $task['priority'] == 'medium' ? 'selected' : '' ?> class="text-warning">Medium</option>
                                    <option value="high" <?= $task['priority'] == 'high' ? 'selected' : '' ?> class="text-orange" style="color: #f97316;">High</option>
                                    <option value="urgent" <?= $task['priority'] == 'urgent' ? 'selected' : '' ?> class="text-danger">Urgent</option>
                                </select>
                            </div>
                            <div class="col-6 mb-4">
                                <label class="form-label text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Ditugaskan Ke</label>
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
                            <label class="form-label text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Tenggat Waktu</label>
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
        
        <div class="modal-footer px-4 py-3 d-flex justify-content-between align-items-center" style="background: #f8fafc; border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
            <?php if ($can_edit): ?>
                <button type="button" class="btn btn-link text-danger text-decoration-none px-0 fw-bold d-flex align-items-center gap-2" onclick="deleteTask(<?= $task_id ?>)">
                    <i class="bi bi-trash3"></i> Hapus Tugas
                </button>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <button type="button" class="btn btn-light border fw-bold rounded-pill px-4 text-dark shadow-sm" data-bs-dismiss="modal">Tutup</button>
        </div>
        
        <style>
            /* Custom CSS khusus untuk bagian dalam modal */
            .custom-scrollbar::-webkit-scrollbar { width: 6px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
            #taskTab .nav-link { color: var(--text-muted); background: transparent; border: 1px solid transparent; }
            #taskTab .nav-link:hover { background: #f1f5f9; }
            #taskTab .nav-link.active { background: var(--primary-light); color: var(--primary); border-color: rgba(99, 102, 241, 0.2); }
        </style>
        <?php
        $html = ob_get_clean();
        echo json_encode(['success' => true, 'html' => $html]);
        
    } catch (Exception $e) {
        error_log("Error handle_get_task: " . $e->getMessage());
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
        error_log("Error handle_update_task_status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handle_delete_task() {
    global $pdo;
    
    try {
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
        
        $can_delete = ($task['created_by'] == $_SESSION['user_id'] || isAdmin() || isProjectOwner($task['project_id'], $_SESSION['user_id']));
        
        if (!$can_delete) {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan menghapus tugas ini']);
            return;
        }
        
        // Hapus child data dulu
        $pdo->prepare("DELETE FROM comments WHERE task_id = ?")->execute([$task_id]);
        $pdo->prepare("DELETE FROM attachments WHERE task_id = ?")->execute([$task_id]);
        
        // Hapus task utama
        $success = $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Tugas berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus tugas']);
        }
        
    } catch (Exception $e) {
        error_log("Error handle_delete_task: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>