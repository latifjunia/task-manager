<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$project_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// CEK AKSES
if (!hasProjectAccess($project_id, $user_id) && !isAdmin()) {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke proyek ini';
    redirect('index.php');
}

// AMBIL DATA PROYEK
$project = getProjectById($project_id);
if (!$project) {
    $_SESSION['error'] = 'Proyek tidak ditemukan';
    redirect('index.php');
}

// AMBIL ANGGOTA
$members = getProjectMembers($project_id);

// CEK ROLE USER
$user_role = getUserProjectRole($project_id, $user_id);
$is_admin_global = isAdmin();
$is_owner = ($user_role == 'owner' || $is_admin_global);
$is_project_admin = ($user_role == 'owner' || $user_role == 'admin' || $is_admin_global);
$is_member = ($user_role == 'member' && !$is_admin_global);

// AMBIL TUGAS
$todo_tasks = getTasksByStatus($project_id, 'todo');
$in_progress_tasks = getTasksByStatus($project_id, 'in_progress');
$review_tasks = getTasksByStatus($project_id, 'review');
$done_tasks = getTasksByStatus($project_id, 'done');

$stats = getProjectStats($project_id);

// MESSAGES
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title><?php echo htmlspecialchars($project['name']); ?> - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .kanban-column {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            min-height: 500px;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .column-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .column-header h6 {
            margin: 0;
            font-weight: 600;
        }
        
        .sortable-list {
            flex: 1;
            min-height: 300px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
        
        .task-card {
            background: white;
            border: 1px solid #e9ecef;
            border-left: 4px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }
        
        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        
        .task-card.priority-low { border-left-color: #28a745; }
        .task-card.priority-medium { border-left-color: #ffc107; }
        .task-card.priority-high { border-left-color: #fd7e14; }
        .task-card.priority-urgent { border-left-color: #dc3545; }
        
        .task-card.overdue {
            background: #fff5f5;
            border-left-color: #dc3545;
        }
        
        .task-card.due-soon {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        
        .task-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #212529;
            line-height: 1.4;
        }
        
        .task-description {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.7rem;
        }
        
        .stats-card {
            border-radius: 10px;
            padding: 1rem;
            color: white;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .sortable-ghost {
            opacity: 0.4;
            background: #e9ecef;
        }
        
        .sortable-chosen {
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 992px) {
            .kanban-column {
                min-height: 400px;
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-nav {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .kanban-column {
                min-height: 350px;
                padding: 0.75rem;
            }
            
            .task-card {
                padding: 0.75rem;
            }
            
            .stats-card {
                margin-bottom: 0.75rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .column-header {
                padding: 0.5rem 0.75rem;
            }
            
            .task-title {
                font-size: 0.85rem;
            }
            
            .badge {
                padding: 0.25rem 0.5rem;
                font-size: 0.65rem;
            }
        }
        
        .toast-container {
            z-index: 1100;
        }
        
        .member-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <!-- NAVIGATION -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-arrow-left me-2"></i> 
                <span class="d-none d-md-inline">Kembali</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav me-auto">
                    <span class="navbar-text text-white">
                        <i class="bi bi-folder me-1"></i> 
                        <?php echo htmlspecialchars(substr($project['name'], 0, 30)); ?>
                        <?php if (strlen($project['name']) > 30): ?>...<?php endif; ?>
                    </span>
                </div>
                
                <div class="navbar-nav">
                    <!-- MEMBER COUNT -->
                    <span class="navbar-text text-white me-3">
                        <i class="bi bi-people"></i> 
                        <span class="d-none d-md-inline"><?php echo count($members); ?> anggota</span>
                        <span class="d-md-none"><?php echo count($members); ?></span>
                        
                        <?php if ($user_role): ?>
                            <span class="badge bg-light text-dark ms-1">
                                <?php 
                                if ($is_owner) echo 'Pemilik';
                                elseif ($user_role == 'admin') echo 'Admin';
                                else echo 'Anggota';
                                ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    
                    <!-- KELOLA ANGGOTA - HANYA UNTUK ADMIN PROYEK -->
                    <?php if ($is_project_admin): ?>
                        <button class="btn btn-outline-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#manageMembersModal">
                            <i class="bi bi-people-fill"></i> 
                            <span class="d-none d-md-inline">Kelola Anggota</span>
                        </button>
                    <?php endif; ?>
                    
                    <!-- TOMBOL TUGAS BARU - SEMUA ANGGOTA BISA -->
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                        <i class="bi bi-plus-circle"></i> 
                        <span class="d-none d-md-inline">Tugas Baru</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container-fluid mt-3 px-3 px-md-4">
        <!-- MESSAGES -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- PROJECT HEADER -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="bi bi-folder text-primary me-2"></i>
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </h4>
                                <p class="text-muted mb-2">
                                    <?php echo nl2br(htmlspecialchars($project['description'] ?: 'Tidak ada deskripsi')); ?>
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-primary">
                                        <i class="bi bi-list-task me-1"></i> <?php echo $stats['total']; ?> tugas
                                    </span>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i> <?php echo $stats['done']; ?> selesai
                                    </span>
                                    <?php if ($stats['overdue'] > 0): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-clock me-1"></i> <?php echo $stats['overdue']; ?> terlambat
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <small class="text-muted d-block">
                                    <i class="bi bi-person"></i> Dibuat oleh: <?php echo htmlspecialchars($project['creator_name'] ?? 'Tidak diketahui'); ?>
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($project['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROGRESS STATS -->
        <div class="row mb-4">
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <div class="stats-card bg-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Progress</h6>
                            <h3 class="mb-0"><?php echo $stats['progress']; ?>%</h3>
                        </div>
                        <i class="bi bi-pie-chart fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <div class="stats-card bg-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">To Do</h6>
                            <h3 class="mb-0"><?php echo $stats['todo']; ?></h3>
                        </div>
                        <i class="bi bi-card-checklist fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="stats-card bg-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">In Progress</h6>
                            <h3 class="mb-0"><?php echo $stats['in_progress']; ?></h3>
                        </div>
                        <i class="bi bi-arrow-repeat fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="stats-card bg-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Selesai</h6>
                            <h3 class="mb-0"><?php echo $stats['done']; ?></h3>
                        </div>
                        <i class="bi bi-check2-all fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KANBAN BOARD -->
        <div class="row g-3" id="kanban-board">
            <!-- TO DO COLUMN -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h6><i class="bi bi-card-checklist me-2"></i> To Do</h6>
                        <span class="badge bg-light text-dark"><?php echo count($todo_tasks); ?></span>
                    </div>
                    <div id="todo-column" class="sortable-list">
                        <?php foreach ($todo_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($todo_tasks)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 mb-2"></i>
                                <p class="mb-0">Belum ada tugas</p>
                                <small>Klik "Tugas Baru" untuk mulai</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-outline-primary w-100 mt-3" onclick="showNewTaskModal('todo')">
                        <i class="bi bi-plus"></i> Tambah Tugas
                    </button>
                </div>
            </div>

            <!-- IN PROGRESS COLUMN -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h6><i class="bi bi-arrow-repeat me-2"></i> In Progress</h6>
                        <span class="badge bg-light text-dark"><?php echo count($in_progress_tasks); ?></span>
                    </div>
                    <div id="in-progress-column" class="sortable-list">
                        <?php foreach ($in_progress_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($in_progress_tasks)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-hourglass-split fs-1 mb-2"></i>
                                <p class="mb-0">Belum ada tugas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- REVIEW COLUMN -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h6><i class="bi bi-eye me-2"></i> Review</h6>
                        <span class="badge bg-light text-dark"><?php echo count($review_tasks); ?></span>
                    </div>
                    <div id="review-column" class="sortable-list">
                        <?php foreach ($review_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($review_tasks)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-check fs-1 mb-2"></i>
                                <p class="mb-0">Belum ada tugas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DONE COLUMN -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h6><i class="bi bi-check2-circle me-2"></i> Done</h6>
                        <span class="badge bg-light text-dark"><?php echo count($done_tasks); ?></span>
                    </div>
                    <div id="done-column" class="sortable-list">
                        <?php foreach ($done_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($done_tasks)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-check2-all fs-1 mb-2"></i>
                                <p class="mb-0">Belum ada tugas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TUGAS BARU - SEMUA ANGGOTA BISA -->
    <div class="modal fade" id="newTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i> Tugas Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="newTaskForm" method="POST" action="api/tasks.php?action=create">
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    <input type="hidden" name="column_status" id="taskColumnStatus" value="todo">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="taskTitle" class="form-label">Judul Tugas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="taskTitle" name="title" 
                                           required maxlength="200" placeholder="Contoh: Membuat halaman login">
                                </div>
                                <div class="mb-3">
                                    <label for="taskDescription" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="taskDescription" name="description" 
                                              rows="4" placeholder="Jelaskan detail tugas..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="taskPriority" class="form-label">Prioritas</label>
                                    <select class="form-select" id="taskPriority" name="priority">
                                        <option value="low">Rendah</option>
                                        <option value="medium" selected>Sedang</option>
                                        <option value="high">Tinggi</option>
                                        <option value="urgent">Mendesak</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="taskAssignee" class="form-label">Ditugaskan kepada</label>
                                    <select class="form-select" id="taskAssignee" name="assignee_id">
                                        <option value="">Pilih anggota...</option>
                                        <?php foreach ($members as $member): ?>
                                            <option value="<?php echo $member['id']; ?>">
                                                <?php echo htmlspecialchars($member['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="taskDueDate" class="form-label">Tenggat Waktu</label>
                                    <input type="date" class="form-control" id="taskDueDate" name="due_date">
                                    <small class="text-muted">Kosongkan jika tidak ada deadline</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none"></span>
                            Simpan Tugas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL KELOLA ANGGOTA - HANYA UNTUK ADMIN PROYEK -->
    <?php if ($is_project_admin): ?>
    <div class="modal fade" id="manageMembersModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-people-fill me-2"></i> Kelola Anggota Proyek
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- DAFTAR ANGGOTA -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Anggota Proyek (<?php echo count($members); ?>)</h6>
                        <div id="membersList" style="max-height: 350px; overflow-y: auto;">
                            <!-- LOAD VIA AJAX -->
                        </div>
                    </div>
                    
                    <!-- TAMBAH ANGGOTA BARU - HANYA UNTUK ADMIN/OWNER -->
                    <?php if ($is_project_admin): ?>
                    <div>
                        <h6 class="border-bottom pb-2">Tambah Anggota Baru</h6>
                        <form id="addMemberForm">
                            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <select class="form-select" name="user_id" id="userSelect" required>
                                        <option value="">Pilih pengguna...</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="role" required>
                                        <option value="member">Anggota</option>
                                        <?php if ($is_owner): ?>
                                            <option value="admin">Admin</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-plus"></i> Tambah
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL DETAIL TUGAS -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="taskDetailContent">
                <!-- LOAD VIA AJAX -->
            </div>
        </div>
    </div>

    <!-- SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        // ==================== GLOBAL VARIABLES ====================
        const currentProjectId = <?php echo $project_id; ?>;
        const currentUserId = <?php echo $user_id; ?>;
        const isProjectAdmin = <?php echo $is_project_admin ? 'true' : 'false'; ?>;
        const isOwner = <?php echo $is_owner ? 'true' : 'false'; ?>;
        const isAdminGlobal = <?php echo $is_admin_global ? 'true' : 'false'; ?>;

        // ==================== INITIALIZATION ====================
        document.addEventListener('DOMContentLoaded', function() {
            initSortable();
            initForms();
            initTaskWarnings();
            setMinDateForDueDate();
            
            // LOAD MEMBERS SAAT MODAL DIBUKA
            const manageMembersModal = document.getElementById('manageMembersModal');
            if (manageMembersModal) {
                manageMembersModal.addEventListener('shown.bs.modal', loadProjectMembers);
            }
        });

        // ==================== SORTABLE DRAG & DROP ====================
        function initSortable() {
            const columns = [
                { id: 'todo-column', status: 'todo' },
                { id: 'in-progress-column', status: 'in_progress' },
                { id: 'review-column', status: 'review' },
                { id: 'done-column', status: 'done' }
            ];
            
            columns.forEach(column => {
                const element = document.getElementById(column.id);
                if (element) {
                    new Sortable(element, {
                        group: 'tasks',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        onEnd: function(evt) {
                            const taskId = evt.item.dataset.taskId;
                            const newStatus = evt.to.id.replace('-column', '').replace('-', '_');
                            updateTaskStatus(taskId, newStatus);
                        }
                    });
                }
            });
        }

        // ==================== UPDATE STATUS TUGAS ====================
        function updateTaskStatus(taskId, status) {
            fetch('api/tasks.php?action=update_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'task_id=' + taskId + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Status tugas berhasil diperbarui');
                } else {
                    showToast('danger', data.message || 'Gagal memperbarui status');
                    location.reload();
                }
            })
            .catch(error => {
                showToast('danger', 'Terjadi kesalahan');
                location.reload();
            });
        }

        // ==================== FORM HANDLERS ====================
        function initForms() {
            // FORM TUGAS BARU
            const newTaskForm = document.getElementById('newTaskForm');
            if (newTaskForm) {
                newTaskForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const form = this;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.disabled = true;
                    if (spinner) spinner.classList.remove('d-none');
                    
                    const formData = new FormData(form);
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('newTaskModal'));
                            modal.hide();
                            showToast('success', 'Tugas berhasil dibuat!');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showToast('danger', data.message || 'Gagal membuat tugas');
                        }
                    })
                    .catch(error => {
                        showToast('danger', 'Terjadi kesalahan');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        if (spinner) spinner.classList.add('d-none');
                        submitBtn.innerHTML = originalText;
                    });
                });
            }
            
            // FORM TAMBAH ANGGOTA - HANYA UNTUK ADMIN
            const addMemberForm = document.getElementById('addMemberForm');
            if (addMemberForm) {
                addMemberForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const form = this;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    
                    const formData = new FormData(form);
                    
                    fetch('api/project_members.php?action=add', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', 'Anggota berhasil ditambahkan');
                            loadProjectMembers();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showToast('danger', data.message || 'Gagal menambahkan anggota');
                        }
                    })
                    .catch(error => {
                        showToast('danger', 'Terjadi kesalahan');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });
            }
        }

        // ==================== SHOW NEW TASK MODAL ====================
        function showNewTaskModal(status) {
            document.getElementById('taskColumnStatus').value = status;
            const modal = new bootstrap.Modal(document.getElementById('newTaskModal'));
            modal.show();
        }

        // ==================== SHOW TASK DETAIL ====================
        function showTaskDetail(taskId) {
            const modalContent = document.getElementById('taskDetailContent');
            modalContent.innerHTML = `
                <div class="modal-header">
                    <h5 class="modal-title">Memuat...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-3">Memuat detail tugas</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            modal.show();
            
            fetch('api/tasks.php?action=get&id=' + taskId)
                .then(response => response.text())
                .then(html => {
                    modalContent.innerHTML = html;
                })
                .catch(error => {
                    modalContent.innerHTML = `
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">Error</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-danger">Gagal memuat detail tugas</p>
                        </div>
                    `;
                });
        }

        // ==================== DELETE TASK ====================
        function deleteTask(taskId) {
            if (!confirm('Apakah Anda yakin ingin menghapus tugas ini?')) return;
            
            fetch('api/tasks.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'task_id=' + taskId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('taskDetailModal'));
                    if (modal) modal.hide();
                    showToast('success', 'Tugas berhasil dihapus');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('danger', data.message || 'Gagal menghapus tugas');
                }
            })
            .catch(error => {
                showToast('danger', 'Terjadi kesalahan');
            });
        }

        // ==================== LOAD PROJECT MEMBERS ====================
        function loadProjectMembers() {
            const membersList = document.getElementById('membersList');
            if (!membersList) return;
            
            membersList.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div><p class="mt-2">Memuat...</p></div>';
            
            fetch('api/project_members.php?action=list&project_id=' + currentProjectId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.members) {
                        let html = '';
                        
                        data.members.forEach(member => {
                            const isCurrentUser = member.id == currentUserId;
                            const isOwner = member.role == 'owner';
                            
                            html += `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(member.full_name)}&background=4361ee&color=fff&size=40" 
                                         class="rounded-circle me-3" width="40" height="40">
                                    <div>
                                        <div class="fw-bold">${member.full_name}</div>
                                        <div class="small text-muted">@${member.username}</div>
                                        <div class="small text-muted">
                                            ${new Date(member.joined_at).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'})}
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge ${member.role == 'owner' ? 'bg-danger' : member.role == 'admin' ? 'bg-warning' : 'bg-primary'} me-3">
                                        ${member.role == 'owner' ? 'Pemilik' : member.role == 'admin' ? 'Admin' : 'Anggota'}
                                    </span>
                                    <!-- DROPDOWN UNTUK ADMIN PROYEK -->
                                    ${isProjectAdmin && !isOwner && !isCurrentUser ? `
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            ${isOwner ? `
                                            <li><a class="dropdown-item" href="#" onclick="changeRole(${member.id}, 'admin')">
                                                <i class="bi bi-shield-check me-2"></i> Jadikan Admin
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="changeRole(${member.id}, 'member')">
                                                <i class="bi bi-person me-2"></i> Jadikan Anggota
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            ` : ''}
                                            <li><a class="dropdown-item text-danger" href="#" onclick="removeMember(${member.id})">
                                                <i class="bi bi-person-dash me-2"></i> Hapus dari Proyek
                                            </a></li>
                                        </ul>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            `;
                        });
                        
                        membersList.innerHTML = html;
                        
                        // LOAD USERS UNTUK DITAMBAH
                        loadUsersForAdding(data.members);
                    }
                })
                .catch(error => {
                    membersList.innerHTML = '<p class="text-danger text-center py-3">Gagal memuat anggota</p>';
                });
        }

        // ==================== LOAD USERS FOR ADDING ====================
        function loadUsersForAdding(existingMembers) {
            const userSelect = document.getElementById('userSelect');
            if (!userSelect) return;
            
            fetch('api/users.php?action=get_all')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users) {
                        let html = '<option value="">Pilih pengguna...</option>';
                        
                        data.users.forEach(user => {
                            const isInProject = existingMembers.some(m => m.id == user.id);
                            if (!isInProject) {
                                html += `<option value="${user.id}">${user.full_name} (@${user.username})</option>`;
                            }
                        });
                        
                        userSelect.innerHTML = html;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // ==================== CHANGE MEMBER ROLE ====================
        function changeRole(userId, newRole) {
            if (!confirm('Ubah role pengguna ini?')) return;
            
            const formData = new FormData();
            formData.append('project_id', currentProjectId);
            formData.append('user_id', userId);
            formData.append('role', newRole);
            
            fetch('api/project_members.php?action=update_role', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Role berhasil diubah');
                    loadProjectMembers();
                } else {
                    showToast('danger', data.message || 'Gagal mengubah role');
                }
            })
            .catch(error => {
                showToast('danger', 'Terjadi kesalahan');
            });
        }

        // ==================== REMOVE MEMBER ====================
        function removeMember(userId) {
            if (!confirm('Hapus anggota ini dari proyek?')) return;
            
            const formData = new FormData();
            formData.append('project_id', currentProjectId);
            formData.append('user_id', userId);
            
            fetch('api/project_members.php?action=remove', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Anggota berhasil dihapus');
                    loadProjectMembers();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('danger', data.message || 'Gagal menghapus anggota');
                }
            })
            .catch(error => {
                showToast('danger', 'Terjadi kesalahan');
            });
        }

        // ==================== TASK WARNINGS ====================
        function initTaskWarnings() {
            const today = new Date();
            const taskCards = document.querySelectorAll('.task-card');
            
            taskCards.forEach(card => {
                const dueDateStr = card.dataset.dueDate;
                if (!dueDateStr) return;
                
                const dueDate = new Date(dueDateStr);
                const daysDiff = Math.ceil((dueDate - today) / (1000 * 3600 * 24));
                
                if (daysDiff < 0) {
                    card.classList.add('overdue');
                    if (!card.querySelector('.overdue-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-danger position-absolute top-0 end-0 m-2 overdue-badge';
                        badge.textContent = 'Terlambat';
                        card.appendChild(badge);
                    }
                } else if (daysDiff <= 2) {
                    card.classList.add('due-soon');
                    if (!card.querySelector('.due-soon-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-warning position-absolute top-0 end-0 m-2 due-soon-badge';
                        badge.textContent = 'Segera';
                        card.appendChild(badge);
                    }
                }
            });
        }

        // ==================== SET MIN DATE ====================
        function setMinDateForDueDate() {
            const dueDate = document.getElementById('taskDueDate');
            if (dueDate) {
                const today = new Date().toISOString().split('T')[0];
                dueDate.min = today;
            }
        }

        // ==================== TOAST NOTIFICATION ====================
        function showToast(type, message) {
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        // ==================== RESET FORMS ====================
        document.getElementById('newTaskModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('newTaskForm')?.reset();
        });
    </script>
</body>
</html>