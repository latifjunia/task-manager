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

// Helper function untuk inisial
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            /* Palette Terang Aestetik */
            --primary: #6366f1; /* Indigo */
            --primary-hover: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #ec4899; /* Pink */
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(120deg, #f8fafc 0%, #eef2ff 100%);
            --surface: #ffffff;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: rgba(226, 232, 240, 0.8);
            --radius-lg: 24px;
            --radius-md: 16px;
            --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.03);
            --shadow-hover: 0 20px 40px -10px rgba(99, 102, 241, 0.15);
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* --- Navbar (Glassmorphism) --- */
        .navbar {
            background: rgba(255, 255, 255, 0.75) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
            padding: 1.25rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-brand { font-weight: 700; font-size: 1.3rem; color: var(--text-dark) !important; letter-spacing: -0.5px;}
        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }
        .nav-link { font-weight: 500; color: var(--text-muted) !important; padding: 0.5rem 1rem !important; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--primary) !important; background: var(--primary-light); }

        /* --- Global Cards --- */
        .card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        /* --- Welcome Banner (Mesh Gradient) --- */
        .welcome-card {
            background: linear-gradient(120deg, #4f46e5, #ec4899, #8b5cf6);
            background-size: 200% 200%;
            animation: gradientMove 10s ease infinite;
            color: white;
            border: none;
            padding: 2.5rem 2rem;
            position: relative;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .welcome-card::before {
            content: '';
            position: absolute; right: -5%; top: -20%;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        .welcome-title { font-weight: 700; font-size: 2rem; letter-spacing: -1px; margin-bottom: 0.5rem;}
        .welcome-subtitle { font-weight: 300; font-size: 1.1rem; opacity: 0.9; }

        /* --- Stats Cards --- */
        .stat-card {
            padding: 1.5rem;
            height: 100%;
            display: flex;
            align-items: center;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-right: 1.25rem;
            flex-shrink: 0;
        }
        .icon-purple { background: #f3e8ff; color: #6b21a8; }
        .icon-orange { background: #fff7ed; color: #b45309; }
        .icon-green { background: #f0fdf4; color: #15803d; }
        .icon-red { background: #fef2f2; color: #b91c1c; }
        
        .stat-info .label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.2rem;}
        .stat-info .value { font-size: 2rem; font-weight: 700; color: var(--text-dark); line-height: 1; }

        /* --- Project Grid --- */
        .project-card {
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            border-radius: var(--radius-md);
        }
        .project-card:hover { border-color: var(--primary); box-shadow: var(--shadow-hover); transform: translateY(-4px); }
        .project-title { font-weight: 700; color: var(--text-dark); font-size: 1.1rem; line-height: 1.3;}
        .project-desc { font-size: 0.85rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 1rem 0;}
        
        .progress-wrapper { background: #f1f5f9; height: 8px; border-radius: 8px; overflow: hidden; margin-top: 0.5rem; }
        .progress-fill { background: var(--primary); height: 100%; border-radius: 8px; transition: width 1s ease; }
        
        .mini-stats { background: #f8fafc; padding: 0.75rem; border-radius: 12px; display: flex; justify-content: space-around; margin-top: 1.5rem; border: 1px solid var(--border-color);}
        .mini-stat-item { text-align: center; }
        .mini-stat-item span { display: block; font-size: 0.65rem; font-weight: 600; color: var(--text-muted); }
        .mini-stat-item strong { font-size: 1rem; color: var(--text-dark); }

        /* --- Lists (Activity & Deadline) --- */
        .list-group-item {
            border: none;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            transition: 0.2s;
            background: transparent;
        }
        .list-group-item:hover { background: #f8fafc; }
        .list-group-item:last-child { border-bottom: none; }
        
        .icon-circle-sm {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* --- Buttons --- */
        .btn { padding: 0.7rem 1.5rem; border-radius: 12px; font-weight: 600; transition: 0.3s; letter-spacing: 0.3px;}
        .btn-primary { background: var(--primary); border: none; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4); }
        .btn-light { background: white; color: var(--primary); border: 1px solid white; }
        .btn-light:hover { background: #f8fafc; color: var(--primary-hover); }

        /* --- Utilities --- */
        .badge { padding: 0.5em 0.8em; border-radius: 8px; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.5px; }
        .bg-light-primary { background: var(--primary-light); color: var(--primary); }
        .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Forms */
        .form-control, .form-select { border-radius: 12px; padding: 0.8rem 1rem; border: 1px solid var(--border-color); background: #f8fafc;}
        .form-control:focus, .form-select:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        
        /* Modal */
        .modal-content { border-radius: var(--radius-lg); border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem; }
        .modal-body { padding: 2rem; }
        .modal-footer { border-top: 1px solid var(--border-color); padding: 1.5rem 2rem; background: #f8fafc; border-radius: 0 0 var(--radius-lg) var(--radius-lg);}
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <div class="brand-icon"><i class="bi bi-layers-half"></i></div>
                <span>TaskFlow</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1 text-dark"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ps-lg-4 gap-1">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-grid-1x2 me-2"></i>Dashboard</a></li>
                    <?php if ($is_admin): ?>
                    <li class="nav-item"><a class="nav-link" href="admin.php"><i class="bi bi-shield-check me-2"></i>Admin</a></li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item d-none d-lg-block">
                        <button class="btn btn-primary btn-sm px-3 py-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                            <i class="bi bi-plus-lg me-1"></i> Proyek
                        </button>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" style="padding: 0.5rem !important;">
                            <div class="icon-circle-sm" style="background: #f1f5f9; color: var(--text-main);">
                                <i class="bi bi-bell"></i>
                                <?php if ($notif_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white" style="font-size: 0.65rem; transform: translate(-30%, 10%) !important;">
                                        <?= $notif_count ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 350px; border-radius: 16px; padding: 0; overflow: hidden; margin-top: 10px;">
                            <div class="bg-light p-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">Notifikasi</h6>
                                <?php if ($notif_count > 0): ?><span class="badge bg-primary rounded-pill"><?= $notif_count ?> Baru</span><?php endif; ?>
                            </div>
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-bell-slash fs-1 text-muted opacity-50 mb-2 d-block"></i>
                                    <p class="text-muted mb-0 small">Belum ada notifikasi baru</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 320px; overflow-y: auto;">
                                    <?php foreach ($notifications as $notif): ?>
                                        <a class="dropdown-item d-flex p-3 border-bottom" href="#" style="white-space: normal;">
                                            <div class="me-3 mt-1">
                                                <?php 
                                                    $bg = 'bg-light'; $color = 'text-secondary'; $icon = 'bi-info-circle';
                                                    if ($notif['type'] == 'assignment') { $bg = 'var(--primary-light)'; $color = 'var(--primary)'; $icon = 'bi-person-check'; }
                                                    elseif ($notif['type'] == 'comment') { $bg = 'var(--success-light)'; $color = 'var(--success)'; $icon = 'bi-chat-dots'; }
                                                ?>
                                                <div class="icon-circle-sm" style="background: <?= $bg ?>; color: <?= $color ?>; width: 36px; height: 36px; font-size: 1rem;">
                                                    <i class="bi <?= $icon ?>"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.9rem; margin-bottom: 2px;"><?= htmlspecialchars($notif['title']) ?></div>
                                                <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4;"><?= htmlspecialchars($notif['message']) ?></div>
                                                <small class="text-muted opacity-75 mt-1 d-block" style="font-size: 0.7rem;"><i class="bi bi-clock me-1"></i><?= timeAgo($notif['created_at']) ?></small>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" style="padding: 0 !important;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=0f172a&color=fff&size=40" 
                                 class="rounded-circle border border-2 border-white shadow-sm">
                            <div class="d-none d-xl-block text-start lh-1">
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $is_admin ? 'Administrator' : 'Anggota Tim' ?></small>
                            </div>
                            <i class="bi bi-chevron-down ms-1 text-muted d-none d-xl-block" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2" style="border-radius: 16px; min-width: 200px;">
                            <div class="d-xl-none p-3 border-bottom mb-2">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                                <small class="text-muted"><?= $is_admin ? 'Admin' : 'Member' ?></small>
                            </div>
                            <a class="dropdown-item py-2 px-3 rounded-3" href="profile.php"><i class="bi bi-person me-3 text-muted"></i>Profil Saya</a>
                            <a class="dropdown-item py-2 px-3 rounded-3" href="#"><i class="bi bi-gear me-3 text-muted"></i>Pengaturan</a>
                            <div class="dropdown-divider my-2"></div>
                            <a class="dropdown-item py-2 px-3 rounded-3 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-3"></i>Keluar</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-lg-5 py-4 pb-5">
        
        <div class="card welcome-card mb-5">
            <div class="row align-items-center position-relative z-index-1">
                <div class="col-md-8">
                    <h1 class="welcome-title">Selamat datang kembali, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h1>
                    <p class="welcome-subtitle">
                        <?= $is_admin ? 'Berikut adalah ringkasan performa seluruh proyek tim hari ini.' : 'Sudah siap menyelesaikan tugas-tugas hebatmu hari ini?' ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <button class="btn btn-light rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                        <i class="bi bi-plus-circle-fill me-2"></i>Mulai Proyek Baru
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-sm-6">
                <div class="card stat-card">
                    <div class="stat-icon icon-purple"><i class="bi bi-briefcase"></i></div>
                    <div class="stat-info">
                        <div class="label">Total Proyek</div>
                        <div class="value"><?= $stats['total_projects'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card stat-card">
                    <div class="stat-icon icon-orange"><i class="bi bi-kanban"></i></div>
                    <div class="stat-info">
                        <div class="label">Tugas Aktif</div>
                        <div class="value"><?= $stats['active_tasks'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card stat-card">
                    <div class="stat-icon icon-green"><i class="bi bi-check2-all"></i></div>
                    <div class="stat-info">
                        <div class="label">Diselesaikan</div>
                        <div class="value"><?= $stats['completed_tasks'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card stat-card">
                    <div class="stat-icon icon-red"><i class="bi bi-fire"></i></div>
                    <div class="stat-info">
                        <div class="label">Mendekati Deadline</div>
                        <div class="value"><?= $stats['upcoming_deadlines'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-xl-8">
                <div class="d-flex justify-content-between align-items-center mb-4 px-1">
                    <h4 class="fw-bold mb-0 text-dark">Proyek Aktif</h4>
                    <a href="projects.php" class="btn btn-sm btn-soft-primary rounded-pill px-3">Lihat Semua</a>
                </div>
                
                <?php if (empty($projects)): ?>
                    <div class="card p-5 text-center">
                        <img src="https://illustrations.popsy.co/amber/freelancer.svg" alt="Empty" style="height: 200px; opacity: 0.8; margin-bottom: 1rem;">
                        <h4 class="fw-bold">Ruang kerja masih kosong</h4>
                        <p class="text-muted mb-4">Buat proyek pertamamu untuk mulai berkolaborasi.</p>
                        <div>
                            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                                <i class="bi bi-plus-lg me-2"></i>Buat Proyek
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach (array_slice($projects, 0, 4) as $project): ?>
                            <?php $p_stats = getProjectStats($project['id']); ?>
                            <div class="col-md-6">
                                <div class="card project-card mb-0" onclick="window.location.href='project.php?id=<?= $project['id'] ?>'">
                                    
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="project-title text-truncate pe-3"><?= htmlspecialchars($project['name']) ?></h5>
                                        <?php if ($is_admin): ?>
                                            <span class="badge bg-light text-muted border"><i class="bi bi-people-fill me-1"></i><?= $project['total_members'] ?? 0 ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="project-desc"><?= htmlspecialchars($project['description'] ?: 'Tidak ada deskripsi detail untuk proyek ini.') ?></p>
                                    
                                    <div class="mt-auto">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-end mb-1">
                                                <span class="fw-bold text-dark" style="font-size: 0.8rem;">Progress</span>
                                                <span class="fw-bold" style="font-size: 0.8rem; color: var(--primary);"><?= $p_stats['progress'] ?>%</span>
                                            </div>
                                            <div class="progress-wrapper">
                                                <div class="progress-fill" style="width: <?= $p_stats['progress'] ?>%"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="mini-stats">
                                            <div class="mini-stat-item">
                                                <span>TO DO</span>
                                                <strong><?= $p_stats['todo'] ?></strong>
                                            </div>
                                            <div style="width: 1px; background: var(--border-color);"></div>
                                            <div class="mini-stat-item">
                                                <span>ON GOING</span>
                                                <strong style="color: var(--warning);"><?= $p_stats['in_progress'] ?></strong>
                                            </div>
                                            <div style="width: 1px; background: var(--border-color);"></div>
                                            <div class="mini-stat-item">
                                                <span>DONE</span>
                                                <strong style="color: var(--success);"><?= $p_stats['done'] ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-xl-4 d-flex flex-column gap-4">
                
                <div class="card flex-fill mb-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Pekerjaan Mendesak</span>
                        <span class="badge bg-danger-light text-danger rounded-pill"><?= count($deadlines) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($deadlines)): ?>
                            <div class="p-5 text-center">
                                <div class="icon-circle-sm mx-auto mb-3" style="background: var(--success-light); color: var(--success); width: 60px; height: 60px; font-size: 2rem;">
                                    <i class="bi bi-emoji-smile"></i>
                                </div>
                                <h6 class="fw-bold">Santai Dulu!</h6>
                                <p class="text-muted small mb-0">Tidak ada tugas yang mendekati deadline.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                                <?php foreach ($deadlines as $task): ?>
                                    <div class="list-group-item d-flex align-items-center gap-3">
                                        <?php 
                                            $is_late = strtotime($task['due_date']) < time();
                                            $iconBg = $is_late ? 'var(--danger-light)' : 'var(--warning-light)';
                                            $iconCol = $is_late ? 'var(--danger)' : 'var(--warning)';
                                            $iconClass = $is_late ? 'bi-exclamation-triangle' : 'bi-hourglass-split';
                                        ?>
                                        <div class="icon-circle-sm" style="background: <?= $iconBg ?>; color: <?= $iconCol ?>;">
                                            <i class="bi <?= $iconClass ?>"></i>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h6 class="fw-bold mb-1 text-truncate" style="font-size: 0.95rem;"><?= htmlspecialchars($task['title']) ?></h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-light text-muted border fw-normal text-truncate" style="max-width: 100px;">
                                                    <?= htmlspecialchars($task['project_name']) ?>
                                                </span>
                                                <small class="fw-bold <?= $is_late ? 'text-danger' : 'text-warning' ?>">
                                                    <?= date('d M', strtotime($task['due_date'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card flex-fill mb-0">
                    <div class="card-header">Aktivitas Terkini</div>
                    <div class="card-body p-0">
                        <?php if (empty($notifications)): ?>
                            <div class="p-4 text-center text-muted small">Belum ada aktivitas.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach (array_slice($notifications, 0, 5) as $activity): ?>
                                    <div class="list-group-item d-flex gap-3">
                                        <div class="mt-1 text-primary"><i class="bi bi-record-circle-fill" style="font-size: 0.8rem;"></i></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($activity['title']) ?></div>
                                            <div class="text-muted mt-1" style="font-size: 0.85rem; line-height: 1.4;"><?= htmlspecialchars($activity['message']) ?></div>
                                            <div class="text-muted mt-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;"><?= strtoupper(timeAgo($activity['created_at'])) ?></div>
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

    <div class="modal fade" id="newProjectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title fw-bold text-dark">Buat Proyek Baru</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newProjectForm" action="api/projects.php?action=create" method="POST">
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Nama Proyek</label>
                            <input type="text" class="form-control form-control-lg fs-6" name="name" required placeholder="Contoh: Redesign Aplikasi Mobile">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Deskripsi Opsional</label>
                            <textarea class="form-control form-control-lg fs-6" name="description" rows="3" placeholder="Tujuan singkat proyek..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">
                            <span class="spinner-border spinner-border-sm d-none me-2"></span>Simpan Proyek
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-4 mt-5" style="z-index: 9999;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toast Function
        function showToast(type, message) {
            const container = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            
            const isSuccess = type === 'success';
            const bgClass = isSuccess ? 'bg-success' : 'bg-danger';
            const iconClass = isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            
            toast.className = `toast align-items-center text-white ${bgClass} border-0 shadow-lg`;
            toast.style.borderRadius = '12px';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex p-2">
                    <div class="toast-body fw-bold fs-6">
                        <i class="bi ${iconClass} me-2 fs-5" style="vertical-align: -2px;"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            container.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        // Form Submit
        document.getElementById('newProjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            const originalText = submitBtn.innerText;
            
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('newProjectModal')).hide();
                    showToast('success', 'Proyek berhasil dibuat!');
                    setTimeout(() => { window.location.href = 'project.php?id=' + data.project_id; }, 1000);
                } else {
                    showToast('danger', data.message || 'Gagal membuat proyek');
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                }
            })
            .catch(error => {
                showToast('danger', 'Terjadi kesalahan sistem');
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });

        // Reset Modal
        document.getElementById('newProjectModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('newProjectForm').reset();
        });
    </script>
</body>
</html>