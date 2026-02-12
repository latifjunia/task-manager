<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get data based on role
$projects = $is_admin ? getAllProjects() : getUserProjects($user_id);
$notifications = getNotifications($user_id);
$stats = getUserStatistics($user_id);
$deadlines = getUpcomingDeadlines($user_id);
$notif_count = getNotificationCount($user_id);
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
        body {
            background: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 0;
        }
        .navbar-brand {
            font-weight: 700;
            color: #4361ee;
        }
        .stat-card {
            background: white;
            border: none;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: all 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(67,97,238,0.1);
        }
        .project-card {
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
            height: 100%;
        }
        .project-card:hover {
            border-color: #4361ee;
            box-shadow: 0 4px 12px rgba(67,97,238,0.1);
        }
        .activity-item {
            border-bottom: 1px solid #edf2f7;
            padding: 12px 0;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background: #3046c0;
        }
        .btn-outline-primary {
            border: 1px solid #4361ee;
            color: #4361ee;
            border-radius: 8px;
            padding: 8px 16px;
        }
        .btn-outline-primary:hover {
            background: #4361ee;
            color: white;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .progress {
            height: 6px;
            border-radius: 3px;
            background: #edf2f7;
        }
        .progress-bar {
            background: #4361ee;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-kanban me-2"></i>Task Manager
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#newProjectModal">Proyek Baru</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($notif_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $notif_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Notifikasi</span>
                                <?php if ($notif_count > 0): ?>
                                    <span class="badge bg-danger"><?= $notif_count ?> baru</span>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-4 px-3">
                                    <i class="bi bi-bell-slash fs-1 text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Tidak ada notifikasi</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <a class="dropdown-item border-bottom py-2" href="#">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <?php if ($notif['type'] == 'assignment'): ?>
                                                    <i class="bi bi-person-plus text-primary"></i>
                                                <?php elseif ($notif['type'] == 'comment'): ?>
                                                    <i class="bi bi-chat text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-info-circle text-info"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small"><?= htmlspecialchars($notif['title']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($notif['message']) ?></div>
                                                <div class="small text-muted mt-1"><?= timeAgo($notif['created_at']) ?></div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                 style="width: 32px; height: 32px;">
                                <?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?>
                            </div>
                            <span><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                            <?php if ($is_admin): ?>
                                <span class="badge bg-danger ms-2">Admin</span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person me-2"></i>Profil
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid px-4 py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="bg-white p-4 rounded-3 shadow-sm">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">Halo, <?= htmlspecialchars($_SESSION['full_name']) ?>! 👋</h2>
                            <p class="text-muted mb-0">
                                <?php if ($is_admin): ?>
                                    Anda memiliki akses penuh ke semua proyek.
                                <?php else: ?>
                                    Kelola tugas dan kolaborasi dengan tim Anda.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                                <i class="bi bi-plus-circle me-2"></i>Proyek Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Proyek</p>
                            <h2 class="mb-0 fw-bold"><?= $stats['total_projects'] ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-folder text-primary fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tugas Aktif</p>
                            <h2 class="mb-0 fw-bold"><?= $stats['active_tasks'] ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-list-task text-warning fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tugas Selesai</p>
                            <h2 class="mb-0 fw-bold"><?= $stats['completed_tasks'] ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-check-circle text-success fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Deadline Mendatang</p>
                            <h2 class="mb-0 fw-bold"><?= $stats['upcoming_deadlines'] ?></h2>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-calendar-event text-danger fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects & Activities -->
        <div class="row g-4">
            <!-- Projects List -->
            <div class="col-lg-8">
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-folder me-2 text-primary"></i>
                            <?= $is_admin ? 'Semua Proyek' : 'Proyek Saya' ?>
                        </h5>
                        <span class="badge bg-primary"><?= count($projects) ?> Proyek</span>
                    </div>
                    
                    <?php if (empty($projects)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-folder-x display-1 text-muted mb-3"></i>
                            <h5>Belum ada proyek</h5>
                            <p class="text-muted mb-4">Mulai dengan membuat proyek pertama Anda</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                                <i class="bi bi-plus-circle me-2"></i>Buat Proyek
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach (array_slice($projects, 0, 4) as $project): ?>
                                <?php $stats = getProjectStats($project['id']); ?>
                                <div class="col-md-6">
                                    <div class="project-card">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($project['name']) ?></h6>
                                                <p class="small text-muted mb-2">
                                                    <?= htmlspecialchars(substr($project['description'] ?? 'Tidak ada deskripsi', 0, 60)) ?>...
                                                </p>
                                            </div>
                                            <?php if ($is_admin): ?>
                                                <span class="badge bg-info"><?= $project['total_members'] ?? 0 ?> anggota</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted">Progress</small>
                                                <small class="fw-bold"><?= $stats['progress'] ?>%</small>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?= $stats['progress'] ?>%"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="small text-muted">
                                                <i class="bi bi-list-task me-1"></i><?= $stats['total'] ?> tugas
                                            </div>
                                            <a href="project.php?id=<?= $project['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                Buka <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- Recent Activities -->
                <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-clock-history me-2 text-warning"></i>
                        Aktivitas Terbaru
                    </h5>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($notifications)): ?>
                            <p class="text-muted text-center py-3">Belum ada aktivitas</p>
                        <?php else: ?>
                            <?php foreach (array_slice($notifications, 0, 5) as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex">
                                        <div class="me-2">
                                            <?php if ($activity['type'] == 'assignment'): ?>
                                                <i class="bi bi-person-plus text-primary"></i>
                                            <?php elseif ($activity['type'] == 'comment'): ?>
                                                <i class="bi bi-chat text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-info-circle text-info"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars($activity['title']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($activity['message']) ?></div>
                                            <div class="small text-muted mt-1"><?= timeAgo($activity['created_at']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Upcoming Deadlines -->
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-calendar-check me-2 text-success"></i>
                        Deadline Mendatang
                    </h5>
                    <?php if (empty($deadlines)): ?>
                        <p class="text-muted text-center py-3">Tidak ada deadline mendatang</p>
                    <?php else: ?>
                        <?php foreach ($deadlines as $task): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-shrink-0">
                                    <div class="bg-<?= getPriorityColor($task['priority']) ?> bg-opacity-10 p-2 rounded-3 me-3">
                                        <i class="bi bi-calendar text-<?= getPriorityColor($task['priority']) ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($task['title']) ?></h6>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($task['project_name']) ?> • 
                                        <?= date('d M', strtotime($task['due_date'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Project Modal -->
    <div class="modal fade" id="newProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Proyek Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="newProjectForm" action="api/projects.php?action=create" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Proyek</label>
                            <input type="text" class="form-control" name="name" required 
                                   placeholder="Contoh: Website E-Commerce">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" 
                                      placeholder="Jelaskan tujuan proyek ini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Buat Proyek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // New Project Form
        document.getElementById('newProjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Membuat...';
            submitBtn.disabled = true;
            
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('newProjectModal')).hide();
                    window.location.href = 'project.php?id=' + data.project_id;
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>