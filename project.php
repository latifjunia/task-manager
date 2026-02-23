<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Cek Login & Akses Project
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

// Helper function untuk UI Avatar
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(" ", $name);
        $acronym = "";
        foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, 1);
        }
        return strtoupper(substr($acronym, 0, 2));
    }
}

// Helper warna acak pastel untuk avatar
function getAvatarColor($name) {
    $colors = ['#6366f1', '#ec4899', '#14b8a6', '#f59e0b', '#8b5cf6', '#10b981'];
    return $colors[strlen($name) % count($colors)];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?= htmlspecialchars($project['name']) ?> - Task Manager</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            /* Palette Seragam dengan Dashboard */
            --primary: #6366f1; 
            --primary-hover: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #ec4899; 
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(120deg, #f8fafc 0%, #eef2ff 100%);
            --surface: #ffffff;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: rgba(226, 232, 240, 0.8);
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.03);
            --shadow-hover: 0 15px 30px -10px rgba(99, 102, 241, 0.15);
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* --- Navbar (Glassmorphism) --- */
        .navbar {
            background: rgba(255, 255, 255, 0.75) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand { font-weight: 700; font-size: 1.3rem; color: var(--text-dark) !important; display: flex; align-items: center; gap: 10px; letter-spacing: -0.5px;}
        
        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        .btn-primary { background: var(--primary); border: none; font-weight: 600; padding: 0.6rem 1.2rem; border-radius: 12px; transition: all 0.3s; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.3); }

        /* --- Stats Cards (Mini Version) --- */
        .stats-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1.25rem 1.5rem;
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: var(--shadow-soft);
        }
        .stats-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
        .stats-label { color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .stats-value { font-size: 1.75rem; font-weight: 700; margin-top: 0.2rem; color: var(--text-dark); line-height: 1;}

        /* Progress Bar Modern */
        .progress { height: 8px; border-radius: 8px; background: #f1f5f9; overflow: hidden; }
        .progress-bar { background: var(--primary); border-radius: 8px; }

        /* --- Kanban Board Layout --- */
        .kanban-board-container {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding-bottom: 2rem;
            min-height: 70vh;
            /* Scrollbar styling */
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .kanban-board-container::-webkit-scrollbar { height: 8px; }
        .kanban-board-container::-webkit-scrollbar-track { background: transparent; }
        .kanban-board-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }

        .kanban-column-wrapper {
            min-width: 320px;
            max-width: 320px;
            display: flex;
            flex-direction: column;
        }

        .kanban-column {
            background: rgba(241, 245, 249, 0.7); /* Slate 100 with opacity */
            border-radius: var(--radius-lg);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,255,255,0.5);
            height: 100%;
        }

        .column-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem; padding: 0.5rem;
        }

        .column-title { font-weight: 700; font-size: 1.05rem; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }
        
        .task-count {
            background: white; color: var(--primary); font-size: 0.75rem; font-weight: 700;
            padding: 2px 10px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* --- Task Cards --- */
        .task-list { flex: 1; overflow-y: auto; padding: 4px; min-height: 150px; }
        
        .task-card {
            background: var(--surface);
            border-radius: var(--radius-sm);
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid rgba(226, 232, 240, 0.5);
            cursor: grab;
            transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
        }
        .task-card:hover { box-shadow: var(--shadow-hover); border-color: var(--primary-light); transform: translateY(-2px); }
        .task-card:active { cursor: grabbing; transform: scale(0.98); }

        .task-title { font-weight: 600; font-size: 1rem; line-height: 1.3; margin-bottom: 0.5rem; color: var(--text-dark); }
        .task-desc { color: var(--text-muted); font-size: 0.85rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 1rem; }

        /* Meta Info (Bottom of card) */
        .task-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; }

        /* Soft Badges */
        .badge-soft { padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-priority-low { background: #dcfce7; color: #166534; }
        .badge-priority-medium { background: #fef9c3; color: #854d0e; }
        .badge-priority-high { background: #ffedd5; color: #9a3412; }
        .badge-priority-urgent { background: #fee2e2; color: #991b1b; }

        /* Avatar Circle */
        .avatar-circle {
            width: 30px; height: 30px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.75rem; font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .avatar-group { display: flex; align-items: center; }
        .avatar-group .avatar-circle { border: 2px solid white; margin-left: -10px; transition: 0.2s; }
        .avatar-group .avatar-circle:hover { z-index: 10; transform: translateY(-2px); }
        .avatar-group .avatar-circle:first-child { margin-left: 0; }

        .due-date { font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; font-weight: 500;}
        .due-date.overdue { color: var(--danger); font-weight: 600; background: var(--danger-light); padding: 2px 8px; border-radius: 6px;}

        /* Drag & Drop Visuals */
        .sortable-ghost { opacity: 0.4; background: var(--primary-light); border: 2px dashed var(--primary); border-radius: var(--radius-sm); }
        .sortable-chosen { background: var(--surface); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

        /* --- Modals & Forms --- */
        .modal-content { border-radius: var(--radius-lg); border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .modal-header { border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem; background: var(--surface); border-radius: var(--radius-lg) var(--radius-lg) 0 0; }
        .modal-body { padding: 2rem; }
        .modal-footer { border-top: 1px solid var(--border-color); padding: 1.5rem 2rem; background: #f8fafc; border-radius: 0 0 var(--radius-lg) var(--radius-lg);}
        
        .form-control, .form-select { border-radius: 12px; padding: 0.8rem 1rem; border: 1px solid var(--border-color); background: #f8fafc; }
        .form-control:focus, .form-select:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .form-label { font-weight: 600; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-arrow-left me-2 text-muted" style="font-size: 1.2rem; transition: 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"></i>
                <div class="brand-icon"><i class="bi bi-kanban"></i></div>
                <?= htmlspecialchars($project['name']) ?>
            </a>
            
            <div class="d-flex align-items-center ms-auto gap-4">
                <div class="avatar-group d-none d-md-flex">
                    <?php 
                    $display_members = array_slice($members, 0, 4);
                    foreach($display_members as $m): 
                        $initials = getInitials($m['full_name']);
                        $bg = getAvatarColor($m['full_name']);
                    ?>
                        <div class="avatar-circle" style="background: <?= $bg ?>;" title="<?= htmlspecialchars($m['full_name']) ?>">
                            <?= $initials ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($members) > 4): ?>
                        <div class="avatar-circle bg-light text-muted" style="font-size: 0.65rem;">
                            +<?= count($members) - 4 ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-none d-md-block" style="height: 30px; width: 1px; background: var(--border-color);"></div>

                <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openNewTaskModal('todo')">
                    <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Tugas Baru</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-lg-5 py-4">
        
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-label text-primary">Progress Proyek</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div class="stats-value"><?= $stats['progress'] ?>%</div>
                    </div>
                    <div class="progress mt-3">
                        <div class="progress-bar" style="width: <?= $stats['progress'] ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card d-flex flex-column justify-content-center">
                    <div class="stats-label text-muted">To Do</div>
                    <div class="stats-value"><?= $stats['todo'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card d-flex flex-column justify-content-center">
                    <div class="stats-label text-primary">In Progress</div>
                    <div class="stats-value"><?= $stats['in_progress'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card d-flex flex-column justify-content-center">
                    <div class="stats-label text-success">Selesai</div>
                    <div class="stats-value"><?= $stats['done'] ?></div>
                </div>
            </div>
        </div>

        <div class="kanban-board-container">
            <?php 
            $columns = [
                'todo' => ['title' => 'To Do', 'icon' => 'bi-circle', 'color' => '#64748b', 'tasks' => $todo_tasks, 'id' => 'todo-list'],
                'in_progress' => ['title' => 'In Progress', 'icon' => 'bi-arrow-repeat', 'color' => 'var(--primary)', 'tasks' => $in_progress_tasks, 'id' => 'progress-list'],
                'review' => ['title' => 'Review', 'icon' => 'bi-eye', 'color' => 'var(--warning)', 'tasks' => $review_tasks, 'id' => 'review-list'],
                'done' => ['title' => 'Done', 'icon' => 'bi-check2-circle', 'color' => 'var(--success)', 'tasks' => $done_tasks, 'id' => 'done-list']
            ];
            ?>

            <?php foreach($columns as $key => $col): ?>
            <div class="kanban-column-wrapper">
                <div class="kanban-column">
                    <div class="column-header">
                        <div class="column-title">
                            <i class="bi <?= $col['icon'] ?>" style="color: <?= $col['color'] ?>; font-size: 1.1rem;"></i>
                            <?= $col['title'] ?>
                        </div>
                        <span class="task-count"><?= count($col['tasks']) ?></span>
                    </div>
                    
                    <div id="<?= $col['id'] ?>" class="task-list">
                        <?php foreach($col['tasks'] as $task): ?>
                            <div class="task-card" data-task-id="<?= $task['id'] ?>" onclick="showTaskDetail(<?= $task['id'] ?>)">
                                
                                <div class="mb-3">
                                    <?php 
                                    $prioClass = 'badge-priority-' . $task['priority'];
                                    $prioLabel = ucfirst($task['priority']);
                                    if($task['priority'] == 'urgent') $prioLabel = 'Urgent!';
                                    ?>
                                    <span class="badge-soft <?= $prioClass ?>"><?= $prioLabel ?></span>
                                </div>

                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                
                                <?php if(!empty($task['description'])): ?>
                                    <div class="task-desc"><?= strip_tags(htmlspecialchars_decode($task['description'])) ?></div>
                                <?php endif; ?>

                                <div class="task-meta">
                                    <?php if($task['due_date']): 
                                        $isOverdue = strtotime($task['due_date']) < time() && $task['column_status'] != 'done';
                                    ?>
                                        <div class="due-date <?= $isOverdue ? 'overdue' : '' ?>">
                                            <i class="bi bi-calendar-event<?= $isOverdue ? '-fill' : '' ?>"></i>
                                            <?= date('d M', strtotime($task['due_date'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <div></div> <?php endif; ?>

                                    <div class="d-flex align-items-center">
                                        <?php if($task['assignee_name']): ?>
                                            <div class="avatar-circle" style="background: <?= getAvatarColor($task['assignee_name']) ?>; width:26px; height:26px; font-size:0.65rem;" title="<?= htmlspecialchars($task['assignee_name']) ?>">
                                                <?= getInitials($task['assignee_name']) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="avatar-circle bg-light text-muted border" style="width:26px; height:26px;" title="Belum ditugaskan"><i class="bi bi-person"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if($key == 'todo'): ?>
                        <button class="btn w-100 mt-2 text-muted fw-bold" style="background: transparent; border: 2px dashed var(--border-color);" onclick="openNewTaskModal('todo')">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Tugas
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="newTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark mb-0">Tugas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newTaskForm">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <input type="hidden" name="column_status" id="task_status" value="todo">
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label">Judul Tugas</label>
                            <input type="text" class="form-control" name="title" required placeholder="Apa yang perlu diselesaikan?">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Tambahkan detail..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-4">
                                <label class="form-label">Prioritas</label>
                                <select class="form-select" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-6 mb-4">
                                <label class="form-label">Assign ke</label>
                                <select class="form-select" name="assignee_id">
                                    <option value="">-- Pilih Anggota --</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Deadline</label>
                            <input type="date" class="form-control" name="due_date" id="task_due_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Buat Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" id="taskDetailContent">
                </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup Drag & Drop Kanban
            const columns = ['todo-list', 'progress-list', 'review-list', 'done-list'];
            columns.forEach(columnId => {
                const el = document.getElementById(columnId);
                if (el) {
                    new Sortable(el, {
                        group: 'tasks',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        dragClass: 'sortable-drag',
                        delay: 100, // Cegah drag gak sengaja di HP
                        delayOnTouchOnly: true,
                        onEnd: function(evt) {
                            const taskId = evt.item.dataset.taskId;
                            const status = evt.to.id.replace('-list', '').replace('progress', 'in_progress'); 
                            updateTaskStatus(taskId, status);
                        }
                    });
                }
            });
            
            // Set minimal tanggal untuk Date Picker
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('task_due_date');
            if(dateInput) dateInput.setAttribute('min', today);
        });

        // --- CRUD FUNCTIONS ---
        function openNewTaskModal(status) {
            document.getElementById('task_status').value = status;
            const modal = new bootstrap.Modal(document.getElementById('newTaskModal'));
            modal.show();
        }

        document.getElementById('newTaskForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
            
            fetch('api/tasks.php?action=create', { method: 'POST', body: new FormData(form) })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Tugas berhasil dibuat', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                    setTimeout(() => location.reload(), 500); 
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => showNotification('Terjadi kesalahan koneksi', 'danger'))
            .finally(() => { submitBtn.innerHTML = originalText; submitBtn.disabled = false; });
        });

        function showTaskDetail(taskId) {
            const modalContent = document.getElementById('taskDetailContent');
            const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            
            modalContent.innerHTML = `<div class="modal-body text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat data...</p></div>`;
            modal.show();
            
            fetch('api/tasks.php?action=get&id=' + taskId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalContent.innerHTML = data.html;
                        // Re-initialize Bootstrap Tabs
                        const triggerTabList = [].slice.call(modalContent.querySelectorAll('#taskTab button'));
                        triggerTabList.forEach(function (triggerEl) { new bootstrap.Tab(triggerEl); });
                    } else {
                        modalContent.innerHTML = `<div class="modal-body text-center text-danger py-5 fw-bold"><i class="bi bi-exclamation-triangle fs-1"></i><br>${data.message}</div>`;
                    }
                });
        }

        function updateTask(taskId) {
            const form = document.getElementById('editTaskForm');
            if (!form) return;
            const formData = new FormData(form);
            formData.append('task_id', taskId);
            
            fetch('api/tasks.php?action=update', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Perubahan disimpan', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(data.message, 'danger');
                }
            });
        }

        function updateTaskStatus(taskId, newStatus) {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('status', newStatus);

            fetch('api/tasks.php?action=update_status', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showNotification(data.message, 'danger');
                    setTimeout(() => location.reload(), 1000); // Revert drag if failed
                }
            });
        }

        function deleteTask(taskId) {
            if (!confirm('Yakin ingin menghapus tugas ini?')) return;
            const formData = new FormData();
            formData.append('task_id', taskId);
            fetch('api/tasks.php?action=delete', { method: 'POST', body: formData })
            .then(r => r.json()).then(d => {
                if(d.success) { location.reload(); } else { showNotification(d.message, 'danger'); }
            });
        }

        // --- KOMENTAR ---
        function addComment(taskId, form) {
            const contentInput = form.querySelector('input[name="content"]');
            const content = contentInput.value.trim();
            if (!content) return;
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalHtml = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            submitBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('content', content);
            
            fetch('api/comments.php?action=add', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contentInput.value = ''; 
                    showTaskDetail(taskId); // Refresh detail modal
                } else { showNotification(data.message, 'danger'); }
            })
            .catch(error => showNotification('Gagal terhubung ke server', 'danger'))
            .finally(() => { submitBtn.innerHTML = originalHtml; submitBtn.disabled = false; });
        }

        // --- NOTIFIKASI TOAST ---
        function showNotification(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container') || (() => {
                const container = document.createElement('div');
                container.className = 'toast-container position-fixed top-0 end-0 p-4 mt-5';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                return container;
            })();

            const toast = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
            const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            
            toast.className = `toast align-items-center text-white ${bgClass} border-0 shadow-lg`;
            toast.style.borderRadius = '12px';
            toast.innerHTML = `<div class="d-flex p-2"><div class="toast-body fw-bold fs-6"><i class="bi ${iconClass} me-2 fs-5" style="vertical-align:-2px;"></i>${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }
    </script>
</body>
</html>