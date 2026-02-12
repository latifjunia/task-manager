<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$projects = getUserProjects($user_id);
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
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .project-card {
            height: 100%;
            transition: all 0.3s;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .project-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        .activity-item {
            border-left: 3px solid #4361ee;
            padding-left: 15px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .activity-item:hover {
            background-color: #f8f9fa;
            border-left-color: #3a0ca3;
        }
        @media (max-width: 768px) {
            .navbar-nav {
                flex-direction: column;
            }
            .stat-card {
                margin-bottom: 15px;
            }
            .project-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
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
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#newProjectModal">Proyek Baru</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span class="badge bg-danger notification-badge"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Notifikasi</h6>
                            <?php if (empty($notifications)): ?>
                                <a class="dropdown-item text-muted" href="#">
                                    <small>Tidak ada notifikasi</small>
                                </a>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <a class="dropdown-item" href="#">
                                        <div><strong><?php echo htmlspecialchars($notif['title']); ?></strong></div>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($notif['message'], 0, 50)); ?>...</small>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
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

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 👋</h2>
                                <p class="mb-0">Kelola tugas Anda dan kolaborasi dengan tim secara efisien.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                                    <i class="bi bi-plus-circle"></i> Buat Proyek Baru
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Total Proyek</h6>
                                <h2><?php echo $stats['total_projects']; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-folder display-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Tugas Aktif</h6>
                                <h2><?php echo $stats['active_tasks']; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-list-task display-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Tugas Selesai</h6>
                                <h2><?php echo $stats['completed_tasks']; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-check-circle display-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Deadline Mendatang</h6>
                                <h2><?php echo $stats['upcoming_deadlines']; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-calendar-event display-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Projects -->
            <div class="col-lg-8">
                <!-- My Projects -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-folder"></i> Proyek Saya</h5>
                        <a href="projects.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($projects)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-folder-x display-1 text-muted mb-3"></i>
                                <h5>Belum ada proyek</h5>
                                <p class="text-muted">Mulai dengan membuat proyek pertama Anda</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                                    <i class="bi bi-plus-circle"></i> Buat Proyek
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach (array_slice($projects, 0, 4) as $project): ?>
                                    <?php $project_stats = getProjectStats($project['id']); ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card project-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1">
                                                            <i class="bi bi-folder2 text-primary"></i> 
                                                            <?php echo htmlspecialchars($project['name']); ?>
                                                        </h5>
                                                        <p class="card-text text-muted small mb-2">
                                                            <?php echo htmlspecialchars(substr($project['description'] ?? 'Tidak ada deskripsi', 0, 100)); ?>
                                                        </p>
                                                    </div>
                                                    <span class="badge bg-primary">
                                                        <?php echo $project_stats['total']; ?> tugas
                                                    </span>
                                                </div>
                                                
                                                <!-- Progress Bar -->
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small class="text-muted">Progress</small>
                                                        <small>
                                                            <?php 
                                                            $progress = $project_stats['total'] > 0 
                                                                ? round(($project_stats['done'] / $project_stats['total']) * 100) 
                                                                : 0;
                                                            echo $progress; ?>%
                                                        </small>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Quick Stats -->
                                                <div class="row text-center">
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
                                                
                                                <div class="mt-3">
                                                    <a href="project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                                        <i class="bi bi-box-arrow-in-right"></i> Buka Proyek
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Activities & Deadlines -->
            <div class="col-lg-4">
                <!-- Recent Activities -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Aktivitas Terbaru</h5>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($activities)): ?>
                            <p class="text-muted text-center py-3">Belum ada aktivitas</p>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-primary"><?php echo htmlspecialchars($activity['title']); ?></strong>
                                        <small class="text-muted"><?php echo $activity['time_ago']; ?></small>
                                    </div>
                                    <p class="mb-0 small"><?php echo htmlspecialchars($activity['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Deadlines -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Deadline Mendatang</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_deadlines)): ?>
                            <p class="text-muted text-center py-3">Tidak ada deadline mendatang</p>
                        <?php else: ?>
                            <?php foreach ($upcoming_deadlines as $task): ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <div class="bg-<?php echo getPriorityColor($task['priority']); ?> rounded-circle p-2">
                                            <i class="bi bi-calendar text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-folder"></i> <?php echo htmlspecialchars($task['project_name']); ?>
                                            • Due: <?php echo date('d M', strtotime($task['due_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Project Modal -->
    <div class="modal fade" id="newProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Proyek Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="newProjectForm" method="POST" action="api/projects.php?action=create">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="projectName" class="form-label">Nama Proyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="projectName" name="name" required 
                                   placeholder="Masukkan nama proyek" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="projectDescription" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="projectDescription" name="description" rows="3"
                                      placeholder="Deskripsi proyek (opsional)"></textarea>
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

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // New Project Form Handler
        document.getElementById('newProjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            const originalText = submitBtn.innerHTML;
            
            // Show loading
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
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newProjectModal'));
                    modal.hide();
                    
                    // Show success message
                    showToast('success', 'Proyek berhasil dibuat!');
                    
                    // Redirect to new project
                    setTimeout(() => {
                        if (data.project_id) {
                            window.location.href = 'project.php?id=' + data.project_id;
                        } else {
                            location.reload();
                        }
                    }, 1500);
                } else {
                    showToast('error', data.message || 'Gagal membuat proyek');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Terjadi kesalahan');
            })
            .finally(() => {
                // Reset button
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                submitBtn.innerHTML = originalText;
            });
        });

        // Toast notification function
        function showToast(type, message) {
            // Create toast container if not exists
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
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
            
            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
            
            // Remove toast after hiding
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }

        // Real-time notification updates
        function updateNotificationCount() {
            fetch('api/notifications.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            if (data.count > 0) {
                                badge.textContent = data.count;
                                badge.style.display = 'inline-block';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Update every 30 seconds
        setInterval(updateNotificationCount, 30000);
        
        // Initial update
        updateNotificationCount();
    </script>
</body>
</html>