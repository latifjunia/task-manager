<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// BEDAKAN DATA UNTUK ADMIN VS ANGGOTA
if ($is_admin) {
    // ADMIN: Lihat SEMUA proyek
    $projects = getAllProjects();
} else {
    // ANGGOTA: Lihat proyek yang DIIKUTI saja
    $projects = getUserProjects($user_id);
}

$notifications = getNotifications($user_id);
$stats = getUserStatistics($user_id);
$activities = getRecentActivities($user_id);
$upcoming_deadlines = getUpcomingDeadlines($user_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
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
            font-size: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .stat-card {
            border-radius: 15px;
            padding: 1.5rem;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .project-card {
            height: 100%;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 1.25rem;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--success), #3a8fd9);
            border-radius: 4px;
        }
        
        .activity-item {
            border-left: 3px solid var(--primary);
            padding-left: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
            border-left-color: var(--secondary);
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        @media (max-width: 768px) {
            .card {
                margin-bottom: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-card i {
                font-size: 2rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-kanban me-2"></i> Task Manager
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house-door me-1"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($is_admin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="bi bi-shield-lock me-1"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                            <i class="bi bi-plus-circle me-1"></i> Proyek Baru
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- NOTIFICATIONS -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5"></i>
                            <?php $notif_count = getNotificationCount($user_id); ?>
                            <?php if ($notif_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                    <?php echo $notif_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                            <h6 class="dropdown-header bg-light">Notifikasi</h6>
                            <?php if (empty($notifications)): ?>
                                <a class="dropdown-item text-muted text-center py-3" href="#">
                                    <i class="bi bi-bell-slash"></i> Tidak ada notifikasi
                                </a>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <a class="dropdown-item border-bottom py-2" href="#">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <?php if ($notif['type'] == 'assignment'): ?>
                                                    <i class="bi bi-person-plus text-primary"></i>
                                                <?php elseif ($notif['type'] == 'comment'): ?>
                                                    <i class="bi bi-chat text-success"></i>
                                                <?php elseif ($notif['type'] == 'deadline'): ?>
                                                    <i class="bi bi-calendar text-warning"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-info-circle text-info"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small"><?php echo htmlspecialchars($notif['title']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($notif['message']); ?></div>
                                                <div class="small text-muted mt-1"><?php echo timeAgo($notif['created_at']); ?></div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center small" href="notifications.php">
                                    Lihat semua
                                </a>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <!-- USER MENU -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=random&size=32" 
                                 class="rounded-circle me-2" width="32" height="32">
                            <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                            <?php if ($is_admin): ?>
                                <span class="badge bg-danger ms-2">Admin</span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person me-2"></i> Profil
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container-fluid mt-4 px-4">
        <!-- WELCOME SECTION -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">
                                    Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 
                                    <?php if ($is_admin): ?>
                                        <span class="badge bg-light text-primary ms-2">Administrator</span>
                                    <?php endif; ?>
                                </h2>
                                <p class="mb-0 opacity-75">
                                    <?php if ($is_admin): ?>
                                        Anda memiliki akses penuh ke semua proyek dan dapat mengelola seluruh sistem.
                                    <?php else: ?>
                                        Kelola tugas Anda dan kolaborasi dengan tim secara efisien.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                                    <i class="bi bi-plus-circle me-2"></i> Buat Proyek Baru
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-2">Total Proyek</h6>
                                <h2 class="mb-0"><?php echo $stats['total_projects']; ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-folder"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-2">Tugas Aktif</h6>
                                <h2 class="mb-0"><?php echo $stats['active_tasks']; ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-list-task"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-2">Tugas Selesai</h6>
                                <h2 class="mb-0"><?php echo $stats['completed_tasks']; ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-2">Deadline Mendatang</h6>
                                <h2 class="mb-0"><?php echo $stats['upcoming_deadlines']; ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-calendar-event"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- LEFT COLUMN: PROJECTS -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-folder me-2 text-primary"></i> 
                            <?php echo $is_admin ? 'Semua Proyek' : 'Proyek Saya'; ?>
                        </h5>
                        <?php if (!$is_admin): ?>
                            <span class="badge bg-primary"><?php echo count($projects); ?> Proyek</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($projects)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-folder-x display-1 text-muted mb-3"></i>
                                <h5>Belum ada proyek</h5>
                                <p class="text-muted mb-4">Mulai dengan membuat proyek pertama Anda</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                                    <i class="bi bi-plus-circle me-2"></i> Buat Proyek
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach (array_slice($projects, 0, 6) as $project): ?>
                                    <?php 
                                    $project_stats = getProjectStats($project['id']);
                                    $progress = $project_stats['total'] > 0 ? round(($project_stats['done'] / $project_stats['total']) * 100) : 0;
                                    ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="project-card h-100">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <i class="bi bi-folder2 text-primary me-1"></i>
                                                        <?php echo htmlspecialchars($project['name']); ?>
                                                    </h5>
                                                    <p class="text-muted small mb-2">
                                                        <?php echo htmlspecialchars(substr($project['description'] ?? 'Tidak ada deskripsi', 0, 100)); ?>
                                                        <?php if (strlen($project['description'] ?? '') > 100): ?>...<?php endif; ?>
                                                    </p>
                                                </div>
                                                <?php if ($is_admin): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo $project['total_members'] ?? 0; ?> anggota
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- PROGRESS BAR -->
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">Progress</small>
                                                    <small class="fw-bold"><?php echo $progress; ?>%</small>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                                </div>
                                            </div>
                                            
                                            <!-- STATS -->
                                            <div class="row text-center mb-3">
                                                <div class="col-4">
                                                    <small class="text-muted d-block">To Do</small>
                                                    <span class="fw-bold"><?php echo $project_stats['todo']; ?></span>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Progress</small>
                                                    <span class="fw-bold"><?php echo $project_stats['in_progress']; ?></span>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Selesai</small>
                                                    <span class="fw-bold"><?php echo $project_stats['done']; ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- CREATOR INFO (UNTUK ADMIN) -->
                                            <?php if ($is_admin && isset($project['creator_name'])): ?>
                                                <div class="small text-muted mb-2">
                                                    <i class="bi bi-person"></i> Dibuat oleh: <?php echo htmlspecialchars($project['creator_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- BUTTON -->
                                            <a href="project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary w-100">
                                                <i class="bi bi-box-arrow-in-right me-2"></i> Buka Proyek
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($projects) > 6): ?>
                                <div class="text-center mt-3">
                                    <a href="projects.php" class="btn btn-link text-decoration-none">
                                        Lihat semua proyek <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: ACTIVITIES & DEADLINES -->
            <div class="col-lg-4">
                <!-- RECENT ACTIVITIES -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-2 text-warning"></i> Aktivitas Terbaru</h6>
                    </div>
                    <div class="card-body p-0">
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php if (empty($activities)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-bell-slash fs-1 text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Belum ada aktivitas</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item py-2 px-3">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <i class="bi bi-info-circle text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small"><?php echo htmlspecialchars($activity['title']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($activity['message']); ?></div>
                                                <div class="small text-muted mt-1"><?php echo $activity['time_ago']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- UPCOMING DEADLINES -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-calendar-check me-2 text-success"></i> Deadline Mendatang</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($upcoming_deadlines)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x fs-1 text-muted mb-2"></i>
                                <p class="text-muted mb-0">Tidak ada deadline mendatang</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($upcoming_deadlines as $task): ?>
                                    <div class="d-flex align-items-center p-3 border-bottom">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-<?php echo getPriorityColor($task['priority']); ?> rounded-circle p-2">
                                                <i class="bi bi-calendar text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-folder"></i> <?php echo htmlspecialchars($task['project_name']); ?>
                                                </small>
                                                <small class="<?php echo strtotime($task['due_date']) < time() ? 'text-danger' : 'text-muted'; ?>">
                                                    <i class="bi bi-clock"></i> 
                                                    <?php echo date('d M Y', strtotime($task['due_date'])); ?>
                                                </small>
                                                <span class="badge bg-<?php echo getPriorityColor($task['priority']); ?>">
                                                    <?php echo getPriorityText($task['priority']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW PROJECT MODAL -->
    <div class="modal fade" id="newProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i> Proyek Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="newProjectForm" method="POST" action="api/projects.php?action=create">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="projectName" class="form-label">Nama Proyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="projectName" name="name" required 
                                   placeholder="Contoh: Website Company Profile" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="projectDescription" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="projectDescription" name="description" rows="4"
                                      placeholder="Deskripsi singkat tentang proyek ini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Buat Proyek
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // NEW PROJECT FORM
        document.getElementById('newProjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newProjectModal'));
                    modal.hide();
                    showToast('success', 'Proyek berhasil dibuat!');
                    setTimeout(() => {
                        window.location.href = 'project.php?id=' + data.project_id;
                    }, 1500);
                } else {
                    showToast('danger', data.message || 'Gagal membuat proyek');
                }
            })
            .catch(error => {
                showToast('danger', 'Terjadi kesalahan');
            })
            .finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                submitBtn.innerHTML = originalText;
            });
        });

        // TOAST NOTIFICATION
        function showToast(type, message) {
            const toastContainer = document.querySelector('.toast-container') || (() => {
                const container = document.createElement('div');
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(container);
                return container;
            })();
            
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

        // RESET FORM ON MODAL HIDE
        document.getElementById('newProjectModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('newProjectForm').reset();
        });
    </script>
</body>
</html>