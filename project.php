<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) redirect('login.php');
if (!isset($_GET['id'])) redirect('index.php');

$project_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

if (!hasProjectAccess($project_id, $user_id)) {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke proyek ini';
    redirect('index.php');
}

$project = getProjectById($project_id);
if (!$project) redirect('index.php');

$members = getProjectMembers($project_id);
$user_role = getUserProjectRole($project_id, $user_id);
$is_admin = isProjectAdmin($project_id, $user_id);
$is_owner = isProjectOwner($project_id, $user_id);

// Get tasks
$todo_tasks = getTasksByStatus($project_id, 'todo');
$in_progress_tasks = getTasksByStatus($project_id, 'in_progress');
$review_tasks = getTasksByStatus($project_id, 'review');
$done_tasks = getTasksByStatus($project_id, 'done');

$stats = getProjectStats($project_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['name']) ?> - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Inter', -apple-system, sans-serif;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 12px 0;
        }
        .navbar-brand {
            font-weight: 700;
            color: #4361ee;
        }
        .kanban-column {
            background: white;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            min-height: 600px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
        }
        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #edf2f7;
        }
        .task-list {
            flex: 1;
            min-height: 400px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .task-card {
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 4px solid #dee2e6;
        }
        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-color: #4361ee;
            transform: translateY(-2px);
        }
        .task-card.priority-low { border-left-color: #28a745; }
        .task-card.priority-medium { border-left-color: #ffc107; }
        .task-card.priority-high { border-left-color: #fd7e14; }
        .task-card.priority-urgent { border-left-color: #dc3545; }
        .task-card.overdue { background: #fff5f5; }
        .task-card.due-soon { background: #fff3cd; }
        
        .sortable-ghost {
            opacity: 0.4;
            background: #e9ecef;
        }
        .sortable-chosen {
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        
        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
        }
        .btn-primary:hover { background: #3046c0; }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .kanban-column {
                min-height: 400px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-arrow-left me-2"></i><?= htmlspecialchars($project['name']) ?>
            </a>
            
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-3">
                    <i class="bi bi-people me-1"></i><?= count($members) ?> anggota
                    <?php if ($user_role): ?>
                        <span class="ms-1 bg-white text-primary px-2 py-1 rounded-pill">
                            <?= $user_role == 'owner' ? 'Owner' : ($user_role == 'admin' ? 'Admin' : 'Member') ?>
                        </span>
                    <?php endif; ?>
                </span>
                
                <?php if ($is_admin): ?>
                    <button class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#manageMembersModal">
                        <i class="bi bi-people-fill me-1"></i>Kelola Anggota
                    </button>
                <?php endif; ?>
                
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                    <i class="bi bi-plus-circle me-1"></i>Tugas Baru
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid px-4 py-4">
        <!-- Project Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stats-card bg-primary text-white">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">Progress</h6>
                            <h2 class="mb-0"><?= $stats['progress'] ?>%</h2>
                        </div>
                        <i class="bi bi-pie-chart fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-warning">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">To Do</h6>
                            <h2 class="mb-0"><?= $stats['todo'] ?></h2>
                        </div>
                        <i class="bi bi-card-checklist fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-info text-white">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">In Progress</h6>
                            <h2 class="mb-0"><?= $stats['in_progress'] ?></h2>
                        </div>
                        <i class="bi bi-arrow-repeat fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-success text-white">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">Done</h6>
                            <h2 class="mb-0"><?= $stats['done'] ?></h2>
                        </div>
                        <i class="bi bi-check2-all fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="row g-4">
            <!-- Todo Column -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h5 class="fw-bold mb-0">To Do</h5>
                        <span class="badge bg-secondary"><?= count($todo_tasks) ?></span>
                    </div>
                    <div id="todo-list" class="task-list">
                        <?php foreach ($todo_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-outline-primary w-100 mt-3" onclick="openNewTaskModal('todo')">
                        <i class="bi bi-plus"></i>Tambah Tugas
                    </button>
                </div>
            </div>
            
            <!-- In Progress Column -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h5 class="fw-bold mb-0">In Progress</h5>
                        <span class="badge bg-primary"><?= count($in_progress_tasks) ?></span>
                    </div>
                    <div id="progress-list" class="task-list">
                        <?php foreach ($in_progress_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Review Column -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h5 class="fw-bold mb-0">Review</h5>
                        <span class="badge bg-info"><?= count($review_tasks) ?></span>
                    </div>
                    <div id="review-list" class="task-list">
                        <?php foreach ($review_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Done Column -->
            <div class="col-xl-3 col-lg-6">
                <div class="kanban-column">
                    <div class="column-header">
                        <h5 class="fw-bold mb-0">Done</h5>
                        <span class="badge bg-success"><?= count($done_tasks) ?></span>
                    </div>
                    <div id="done-list" class="task-list">
                        <?php foreach ($done_tasks as $task): ?>
                            <?php include 'includes/task_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Task Modal -->
    <div class="modal fade" id="newTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tugas Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="newTaskForm" action="api/tasks.php?action=create" method="POST">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <input type="hidden" name="column_status" id="task_status" value="todo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Judul Tugas</label>
                            <input type="text" class="form-control" name="title" required 
                                   placeholder="Contoh: Membuat halaman login">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" 
                                      placeholder="Jelaskan detail tugas..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prioritas</label>
                            <select class="form-select" name="priority">
                                <option value="low">Rendah</option>
                                <option value="medium" selected>Sedang</option>
                                <option value="high">Tinggi</option>
                                <option value="urgent">Mendesak</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Ditugaskan Kepada</label>
                            <select class="form-select" name="assignee_id">
                                <option value="">Pilih anggota...</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tenggat Waktu</label>
                            <input type="date" class="form-control" name="due_date" id="task_due_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Task Detail Modal -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" id="taskDetailContent">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Manage Members Modal (Admin Only) -->
    <?php if ($is_admin): ?>
    <div class="modal fade" id="manageMembersModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i>Kelola Anggota</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3">Daftar Anggota</h6>
                    <div id="membersList" style="max-height: 300px; overflow-y: auto;">
                        <!-- Load via AJAX -->
                    </div>
                    
                    <hr>
                    
                    <h6 class="fw-bold mb-3">Tambah Anggota</h6>
                    <form id="addMemberForm">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">
                        <div class="mb-3">
                            <select class="form-select" name="user_id" required>
                                <option value="">Pilih pengguna...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <select class="form-select" name="role">
                                <option value="member">Anggota</option>
                                <?php if ($is_owner): ?>
                                    <option value="admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Tambah</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        // Initialize Sortable
        const columns = ['todo-list', 'progress-list', 'review-list', 'done-list'];
        columns.forEach(columnId => {
            const el = document.getElementById(columnId);
            if (el) {
                new Sortable(el, {
                    group: 'tasks',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function(evt) {
                        const taskId = evt.item.dataset.taskId;
                        const status = evt.to.id.replace('-list', '');
                        updateTaskStatus(taskId, status);
                    }
                });
            }
        });

        // Update task status
        function updateTaskStatus(taskId, status) {
            fetch('api/tasks.php?action=update_status', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'task_id=' + taskId + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Status tugas berhasil diperbarui', 'success');
                } else {
                    location.reload();
                }
            });
        }

        // Open new task modal with status
        function openNewTaskModal(status) {
            document.getElementById('task_status').value = status;
            new bootstrap.Modal(document.getElementById('newTaskModal')).show();
        }

        // Show task detail
        function showTaskDetail(taskId) {
            fetch('api/tasks.php?action=get&id=' + taskId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('taskDetailContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
                });
        }

        // Delete task
        function deleteTask(taskId) {
            if (confirm('Hapus tugas ini?')) {
                fetch('api/tasks.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'task_id=' + taskId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('taskDetailModal')).hide();
                        location.reload();
                    }
                });
            }
        }

        // Add comment
        function addComment(taskId, form) {
            const content = form.querySelector('input[name="content"]').value;
            if (!content.trim()) return;
            
            fetch('api/comments.php?action=add', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'task_id=' + taskId + '&content=' + encodeURIComponent(content)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.querySelector('input[name="content"]').value = '';
                    showTaskDetail(taskId);
                }
            });
        }

        // Load members for admin modal
        <?php if ($is_admin): ?>
        document.getElementById('manageMembersModal')?.addEventListener('shown.bs.modal', function() {
            loadProjectMembers();
            loadAvailableUsers();
        });

        function loadProjectMembers() {
            fetch('api/project_members.php?action=list&project_id=<?= $project_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        data.members.forEach(member => {
                            html += `
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <div class="fw-bold">${member.full_name}</div>
                                        <small class="text-muted">@${member.username}</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-${member.role == 'owner' ? 'danger' : member.role == 'admin' ? 'warning' : 'primary'} me-2">
                                            ${member.role}
                                        </span>
                                        <?php if ($is_owner): ?>
                                        ${member.role != 'owner' && member.id != <?= $user_id ?> ? `
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeMember(${member.id})">
                                                <i class="bi bi-person-dash"></i>
                                            </button>
                                        ` : ''}
                                        <?php endif; ?>
                                    </div>
                                </div>
                            `;
                        });
                        document.getElementById('membersList').innerHTML = html;
                    }
                });
        }

        function loadAvailableUsers() {
            fetch('api/users.php?action=get_all')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let options = '<option value="">Pilih pengguna...</option>';
                        data.users.forEach(user => {
                            options += `<option value="${user.id}">${user.full_name} (@${user.username})</option>`;
                        });
                        document.querySelector('#addMemberForm select[name="user_id"]').innerHTML = options;
                    }
                });
        }

        function removeMember(userId) {
            if (confirm('Hapus anggota ini?')) {
                const formData = new FormData();
                formData.append('project_id', <?= $project_id ?>);
                formData.append('user_id', userId);
                
                fetch('api/project_members.php?action=remove', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadProjectMembers();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }

        document.getElementById('addMemberForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/project_members.php?action=add', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    loadProjectMembers();
                    loadAvailableUsers();
                } else {
                    alert(data.message);
                }
            });
        });
        <?php endif; ?>

        // New task form
        document.getElementById('newTaskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
            
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.innerHTML = 'Simpan';
                    submitBtn.disabled = false;
                }
            });
        });

        // Set min date for due date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('task_due_date').min = today;

        // Show notification
        function showNotification(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `position-fixed top-0 end-0 p-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="toast align-items-center text-white bg-${type} border-0 show" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi ${type == 'success' ? 'bi-check-circle' : 'bi-info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>