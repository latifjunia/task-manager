<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get data based on role
$projects = $is_admin ? getAllProjects() : getUserProjects($user_id);
$notifications = getUnreadNotifications($user_id);
$stats = getUserStatistics($user_id);
$deadlines = getUpcomingDeadlines($user_id);
$notif_count = getNotificationCount($user_id);
$overdue_count = getOverdueTasksCount($user_id);

// Get recent activities
$recent_activities = getRecentActivities($user_id, 5);

// Cek preferensi tema dari cookie
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
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
            /* Light Mode Variables */
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #ec4899;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(120deg, #f8fafc 0%, #eef2ff 100%);
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: rgba(226, 232, 240, 0.8);
            --border-color-solid: #e2e8f0;
            
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.03);
            --shadow-hover: 0 20px 40px -10px rgba(99, 102, 241, 0.15);
            
            --card-shadow: 0 10px 40px -10px rgba(0,0,0,0.03);
            --card-hover-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.15);
            
            --welcome-gradient: linear-gradient(120deg, #4f46e5, #ec4899, #8b5cf6);
            
            /* Scrollbar */
            --scrollbar-thumb: #cbd5e1;
            --scrollbar-track: transparent;
        }

        /* Dark Mode Variables */
        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-hover: #6366f1;
            --primary-light: #1e1b4b;
            --secondary: #f472b6;
            --success: #10b981;
            --success-light: #064e3b;
            --warning: #f59e0b;
            --warning-light: #422006;
            --danger: #ef4444;
            --danger-light: #450a0a;
            
            --bg-body: #0f172a;
            --bg-gradient: linear-gradient(120deg, #0f172a 0%, #1e1b4b 100%);
            --surface: #1e293b;
            --surface-hover: #334155;
            
            --text-dark: #f1f5f9;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            
            --border-color: rgba(51, 65, 85, 0.8);
            --border-color-solid: #334155;
            
            --card-shadow: 0 10px 40px -10px rgba(0,0,0,0.2);
            --card-hover-shadow: 0 20px 40px -10px rgba(129, 140, 248, 0.2);
            
            --welcome-gradient: linear-gradient(120deg, #4f46e5, #db2777, #7c3aed);
            
            --scrollbar-thumb: #475569;
            --scrollbar-track: transparent;
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            transition: background 0.3s ease, color 0.3s ease;
            margin: 0;
            padding: 0;
        }

        /* Navbar */
        .navbar {
            background: rgba(255,255,255,0.75) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background 0.3s ease;
        }
        
        [data-theme="dark"] .navbar {
            background: rgba(30, 41, 59, 0.75) !important;
        }
        
        .navbar-brand { 
            font-weight: 700; 
            font-size: 1.3rem; 
            color: var(--text-dark) !important; 
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }
        
        .nav-link { 
            font-weight: 500; 
            color: var(--text-muted) !important; 
            padding: 0.5rem 1rem !important; 
            border-radius: 8px; 
            transition: 0.3s; 
        }
        .nav-link:hover, .nav-link.active { 
            color: var(--primary) !important; 
            background: var(--primary-light); 
        }

        /* Theme Toggle */
        .theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border-color-solid);
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: var(--card-hover-shadow);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        /* Welcome Banner */
        .welcome-card {
            background: var(--welcome-gradient);
            background-size: 200% 200%;
            animation: gradientMove 10s ease infinite;
            color: white;
            border: none;
            padding: 2.5rem 2rem;
            position: relative;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
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
        
        .welcome-title { 
            font-weight: 700; 
            font-size: 2rem; 
            letter-spacing: -1px; 
            margin-bottom: 0.5rem;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .welcome-subtitle { 
            font-weight: 300; 
            font-size: 1.1rem; 
            opacity: 0.9; 
            color: white;
            position: relative;
            z-index: 1;
        }

        /* Stats Cards */
        .stat-card {
            padding: 1.5rem;
            height: 100%;
            display: flex;
            align-items: center;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
        }
        .stat-card:hover { 
            transform: translateY(-5px); 
            box-shadow: var(--card-hover-shadow); 
            background: var(--surface-hover);
        }
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-right: 1.25rem;
            flex-shrink: 0;
        }
        .icon-purple { 
            background: var(--primary-light); 
            color: var(--primary); 
        }
        .icon-orange { 
            background: var(--warning-light); 
            color: var(--warning); 
        }
        .icon-green { 
            background: var(--success-light); 
            color: var(--success); 
        }
        .icon-red { 
            background: var(--danger-light); 
            color: var(--danger); 
        }
        
        .stat-info .label { 
            font-size: 0.75rem; 
            font-weight: 600; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-bottom: 0.2rem;
        }
        .stat-info .value { 
            font-size: 2rem; 
            font-weight: 700; 
            color: var(--text-dark); 
            line-height: 1; 
        }

        /* Project Cards */
        .project-card {
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            border-radius: var(--radius-md);
            background: var(--surface);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .project-card:hover { 
            border-color: var(--primary); 
            box-shadow: var(--card-hover-shadow); 
            transform: translateY(-4px); 
            background: var(--surface-hover);
        }
        .project-title { 
            font-weight: 700; 
            color: var(--text-dark); 
            font-size: 1.1rem; 
            line-height: 1.3;
            margin-bottom: 0.5rem;
        }
        .project-desc { 
            font-size: 0.85rem; 
            color: var(--text-muted); 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
            margin: 0.5rem 0 1rem 0;
        }
        
        .progress-wrapper { 
            background: var(--border-color-solid); 
            height: 8px; 
            border-radius: 8px; 
            overflow: hidden; 
            margin: 0.75rem 0; 
        }
        .progress-fill { 
            background: var(--primary); 
            height: 100%; 
            border-radius: 8px; 
            transition: width 1s ease; 
        }
        
        .mini-stats { 
            background: var(--surface-hover); 
            padding: 0.75rem; 
            border-radius: 12px; 
            display: flex; 
            justify-content: space-around; 
            margin-top: 1rem; 
            border: 1px solid var(--border-color);
        }
        .mini-stat-item { 
            text-align: center; 
        }
        .mini-stat-item span { 
            display: block; 
            font-size: 0.65rem; 
            font-weight: 600; 
            color: var(--text-muted); 
        }
        .mini-stat-item strong { 
            font-size: 1rem; 
            color: var(--text-dark); 
        }

        /* List Items */
        .list-group-item {
            border: none;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            transition: 0.2s;
            background: transparent;
            color: var(--text-main);
        }
        .list-group-item:hover { 
            background: var(--surface-hover); 
        }
        .list-group-item:last-child { 
            border-bottom: none; 
        }
        
        .icon-circle-sm {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* Badge Overdue */
        .badge-overdue {
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            margin-left: 5px;
        }

        /* Buttons */
        .btn { 
            padding: 0.7rem 1.5rem; 
            border-radius: 12px; 
            font-weight: 600; 
            transition: 0.3s; 
            letter-spacing: 0.3px;
        }
        .btn-primary { 
            background: var(--primary); 
            border: none; 
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); 
            color: white;
        }
        .btn-primary:hover { 
            background: var(--primary-hover); 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4); 
        }
        .btn-light { 
            background: white; 
            color: var(--primary); 
            border: 1px solid white; 
        }
        .btn-light:hover { 
            background: #f8fafc; 
            color: var(--primary-hover); 
        }
        [data-theme="dark"] .btn-light {
            background: var(--surface);
            color: var(--primary);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .btn-light:hover {
            background: var(--surface-hover);
        }
        
        .btn-soft-primary {
            background: var(--primary-light);
            color: var(--primary);
            border: none;
        }
        .btn-soft-primary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-danger-soft {
            background: var(--danger-light);
            color: var(--danger);
            border: none;
        }
        .btn-danger-soft:hover {
            background: var(--danger);
            color: white;
        }

        /* Dropdown */
        .dropdown-menu {
            background: var(--surface);
            border-color: var(--border-color);
            box-shadow: var(--card-hover-shadow);
            border-radius: 12px;
            padding: 0.5rem;
        }
        
        .dropdown-item {
            color: var(--text-main);
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background: var(--surface-hover);
            color: var(--text-dark);
        }
        
        .dropdown-divider {
            border-color: var(--border-color);
        }

        /* Modal */
        .modal-content { 
            background: var(--surface);
            border-color: var(--border-color);
            border-radius: var(--radius-lg); 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); 
        }
        .modal-header { 
            border-bottom: 1px solid var(--border-color); 
            padding: 1.5rem 2rem; 
            background: var(--surface); 
        }
        .modal-header .modal-title {
            color: var(--text-dark);
        }
        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .modal-body { 
            padding: 2rem; 
            color: var(--text-main);
        }
        .modal-footer { 
            border-top: 1px solid var(--border-color); 
            padding: 1.5rem 2rem; 
            background: var(--surface-hover); 
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        /* Form Controls */
        .form-control, .form-select { 
            border-radius: 12px; 
            padding: 0.8rem 1rem; 
            border: 1px solid var(--border-color); 
            background: var(--surface-hover);
            color: var(--text-dark);
            transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
        .form-control:focus, .form-select:focus { 
            background: var(--surface); 
            border-color: var(--primary); 
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); 
            color: var(--text-dark);
            outline: none;
        }
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        /* Toast */
        .toast {
            background: var(--surface) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .stat-info .value {
                font-size: 1.5rem;
            }
            
            .navbar-nav .nav-link {
                padding: 0.5rem !important;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="index.php">
                <div class="brand-icon"><i class="bi bi-layers-half"></i></div>
                <span>Task Manager</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1" style="color: var(--text-dark);"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ps-lg-4 gap-1">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-grid-1x2 me-2"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- OVERDUE MENU -->
                    <li class="nav-item">
                        <a class="nav-link" href="overdue.php" id="overdueLink">
                            <i class="bi bi-exclamation-triangle me-2" style="color: var(--danger);"></i>
                            Terlambat
                            <span class="badge bg-danger ms-2" id="overdueCountBadge" style="display: <?= $overdue_count > 0 ? 'inline-block' : 'none' ?>;">
                                <?= $overdue_count ?>
                            </span>
                        </a>
                    </li>
                    
                    
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">
                            <i class="bi bi-folder me-2"></i>Semua Proyek
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav align-items-center gap-3">
                    <!-- Theme Toggle -->
                    <li class="nav-item">
                        <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                            <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                        </div>
                    </li>
                    
                    <li class="nav-item d-none d-lg-block">
                        <button class="btn btn-primary btn-sm px-3 py-2 rounded-pill" onclick="openNewProjectModal()">
                            <i class="bi bi-plus-lg me-1"></i> Proyek Baru
                        </button>
                    </li>

                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" style="padding: 0.5rem !important;">
                            <div class="icon-circle-sm" style="background: var(--surface-hover); color: var(--text-main);">
                                <i class="bi bi-bell"></i>
                                <?php if ($notif_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white" style="font-size: 0.65rem; padding: 0.25rem 0.5rem;">
                                        <?= $notif_count ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 350px; border-radius: 16px; padding: 0; overflow: hidden;">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background: var(--surface);">
                                <h6 class="mb-0 fw-bold" style="color: var(--text-dark);">Notifikasi</h6>
                                <?php if ($notif_count > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?= $notif_count ?> Baru</span>
                                <?php endif; ?>
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
                                                    $bg = 'var(--surface-hover)'; 
                                                    $color = 'var(--text-muted)'; 
                                                    $icon = 'bi-info-circle';
                                                    if ($notif['type'] == 'assignment') { 
                                                        $bg = 'var(--primary-light)'; 
                                                        $color = 'var(--primary)'; 
                                                        $icon = 'bi-person-check'; 
                                                    }
                                                    elseif ($notif['type'] == 'comment') { 
                                                        $bg = 'var(--success-light)'; 
                                                        $color = 'var(--success)'; 
                                                        $icon = 'bi-chat-dots'; 
                                                    }
                                                    elseif ($notif['type'] == 'deadline') { 
                                                        $bg = 'var(--warning-light)'; 
                                                        $color = 'var(--warning)'; 
                                                        $icon = 'bi-clock'; 
                                                    }
                                                ?>
                                                <div class="icon-circle-sm" style="background: <?= $bg ?>; color: <?= $color ?>; width: 36px; height: 36px; font-size: 1rem;">
                                                    <i class="bi <?= $icon ?>"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="color: var(--text-dark); font-size: 0.9rem; margin-bottom: 2px;">
                                                    <?= htmlspecialchars($notif['title']) ?>
                                                </div>
                                                <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4;">
                                                    <?= htmlspecialchars($notif['message']) ?>
                                                </div>
                                                <small class="text-muted opacity-75 mt-1 d-block" style="font-size: 0.7rem;">
                                                    <i class="bi bi-clock me-1"></i><?= timeAgo($notif['created_at']) ?>
                                                </small>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="p-2 text-center border-top">
                                    <a href="#" class="text-decoration-none small" style="color: var(--primary);" onclick="markAllNotificationsRead()">Tandai semua dibaca</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" style="padding: 0 !important;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=0f172a&color=fff&size=40" 
                                 class="rounded-circle border border-2" style="border-color: var(--border-color) !important; width: 40px; height: 40px;">
                            <div class="d-none d-xl-block text-start lh-1">
                                <div class="fw-bold" style="color: var(--text-dark); font-size: 0.9rem;">
                                    <?= htmlspecialchars($_SESSION['full_name']) ?>
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <?= $is_admin ? 'Administrator' : 'Anggota Tim' ?>
                                </small>
                            </div>
                            <i class="bi bi-chevron-down ms-1 text-muted d-none d-xl-block" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2" style="border-radius: 16px; min-width: 200px;">
                            <div class="d-xl-none p-3 border-bottom mb-2">
                                <div class="fw-bold" style="color: var(--text-dark);"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                                <small class="text-muted"><?= $is_admin ? 'Admin' : 'Member' ?></small>
                            </div>
                            <a class="dropdown-item py-2 px-3 rounded-3" href="profile.php">
                                <i class="bi bi-person me-3 text-muted"></i>Profil Saya
                            </a>
                            <div class="dropdown-divider my-2"></div>
                            <a class="dropdown-item py-2 px-3 rounded-3 text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-3"></i>Keluar
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid px-lg-5 py-4 pb-5">
        

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="bi bi-briefcase"></i></div>
                    <div class="stat-info">
                        <div class="label">Total Proyek</div>
                        <div class="value"><?= $stats['total_projects'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="bi bi-kanban"></i></div>
                    <div class="stat-info">
                        <div class="label">Tugas Aktif</div>
                        <div class="value"><?= $stats['active_tasks'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon icon-green"><i class="bi bi-check2-all"></i></div>
                    <div class="stat-info">
                        <div class="label">Diselesaikan</div>
                        <div class="value"><?= $stats['completed_tasks'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon icon-red"><i class="bi bi-fire"></i></div>
                    <div class="stat-info">
                        <div class="label">Mendekati Deadline</div>
                        <div class="value"><?= $stats['upcoming_deadlines'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Alert Card (hanya muncul jika ada tugas terlambat) -->
        <?php if ($overdue_count > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card" style="border-left: 4px solid var(--danger); background: var(--danger-light);">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle-sm" style="background: var(--danger); color: white; width: 48px; height: 48px; font-size: 1.5rem;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1" style="color: var(--text-dark);">Ada <?= $overdue_count ?> tugas terlambat</h5>
                                <p class="mb-0 text-muted">Tugas-tugas ini melewati batas waktu dan perlu segera diselesaikan.</p>
                            </div>
                        </div>
                        <a href="overdue.php" class="btn btn-danger-soft rounded-pill px-4">
                            <i class="bi bi-eye me-2"></i>Lihat Semua
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- Projects Section -->
            <div class="col-xl-8">
                <div class="d-flex justify-content-between align-items-center mb-4 px-1">
                    <h4 class="fw-bold mb-0" style="color: var(--text-dark);">Proyek Aktif</h4>
                    <a href="projects.php" class="btn btn-soft-primary rounded-pill px-3">Lihat Semua</a>
                </div>
                
                <?php if (empty($projects)): ?>
                    <div class="card p-5 text-center">
                        <img src="https://illustrations.popsy.co/amber/freelancer.svg" alt="Empty" style="height: 200px; opacity: 0.8; margin-bottom: 1rem;">
                        <h4 class="fw-bold" style="color: var(--text-dark);">Ruang kerja masih kosong</h4>
                        <p class="text-muted mb-4">Buat proyek pertamamu untuk mulai berkolaborasi.</p>
                        <div>
                            <button class="btn btn-primary rounded-pill px-4" onclick="openNewProjectModal()">
                                <i class="bi bi-plus-lg me-2"></i>Buat Proyek
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach (array_slice($projects, 0, 4) as $project): ?>
                            <?php $p_stats = getProjectStats($project['id']); ?>
                            <div class="col-md-6">
                                <div class="project-card" onclick="window.location.href='project.php?id=<?= $project['id'] ?>'">
                                    
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="project-title"><?= htmlspecialchars($project['name']) ?></h5>
                                        <?php if ($is_admin && isset($project['total_members'])): ?>
                                            <span class="badge bg-light text-muted border">
                                                <i class="bi bi-people-fill me-1"></i><?= $project['total_members'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="project-desc">
                                        <?= htmlspecialchars($project['description'] ?: 'Tidak ada deskripsi') ?>
                                    </p>
                                    
                                    <!-- Progress Bar -->
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Progress</small>
                                            <small class="fw-bold" style="color: var(--primary);"><?= $p_stats['progress'] ?>%</small>
                                        </div>
                                        <div class="progress-wrapper">
                                            <div class="progress-fill" style="width: <?= $p_stats['progress'] ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Mini Stats -->
                                    <div class="mini-stats">
                                        <div class="mini-stat-item">
                                            <span>To Do</span>
                                            <strong><?= $p_stats['todo'] ?></strong>
                                        </div>
                                        <div style="width: 1px; background: var(--border-color);"></div>
                                        <div class="mini-stat-item">
                                            <span>Progress</span>
                                            <strong style="color: var(--warning);"><?= $p_stats['in_progress'] ?></strong>
                                        </div>
                                        <div style="width: 1px; background: var(--border-color);"></div>
                                        <div class="mini-stat-item">
                                            <span>Selesai</span>
                                            <strong style="color: var(--success);"><?= $p_stats['done'] ?></strong>
                                        </div>
                                    </div>
                                    
                                    <!-- Creator Info (untuk admin) -->
                                    <?php if ($is_admin && isset($project['creator_name'])): ?>
                                        <div class="small text-muted mt-2">
                                            <i class="bi bi-person"></i> Dibuat oleh: <?= htmlspecialchars($project['creator_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Overdue Badge -->
                                    <?php if ($p_stats['overdue'] > 0): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                <?= $p_stats['overdue'] ?> tugas terlambat
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column: Deadlines & Activities -->
            <div class="col-xl-4 d-flex flex-column gap-4">
                
                <!-- Upcoming Deadlines -->
                <div class="card flex-fill mb-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-check me-2" style="color: var(--warning);"></i>Deadline Mendatang</span>
                        <span class="badge bg-warning rounded-pill"><?= count($deadlines) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($deadlines)): ?>
                            <div class="p-5 text-center">
                                <div class="icon-circle-sm mx-auto mb-3" style="background: var(--success-light); color: var(--success); width: 60px; height: 60px; font-size: 2rem;">
                                    <i class="bi bi-emoji-smile"></i>
                                </div>
                                <h6 class="fw-bold" style="color: var(--text-dark);">Santai Dulu!</h6>
                                <p class="text-muted small mb-0">Tidak ada tugas yang mendekati deadline.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                                <?php foreach ($deadlines as $task): ?>
                                    <?php 
                                        $days_left = ceil((strtotime($task['due_date']) - time()) / 86400);
                                        $is_critical = $days_left <= 2;
                                    ?>
                                    <div class="list-group-item d-flex align-items-center gap-3">
                                        <div class="icon-circle-sm" style="background: <?= $is_critical ? 'var(--danger-light)' : 'var(--warning-light)' ?>; color: <?= $is_critical ? 'var(--danger)' : 'var(--warning)' ?>;">
                                            <i class="bi bi-hourglass-split"></i>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h6 class="fw-bold mb-1 text-truncate" style="color: var(--text-dark); font-size: 0.95rem;">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-light text-muted border fw-normal text-truncate" style="max-width: 100px;">
                                                    <?= htmlspecialchars($task['project_name']) ?>
                                                </span>
                                                <small class="fw-bold <?= $is_critical ? 'text-danger' : 'text-warning' ?>">
                                                    <?= date('d M', strtotime($task['due_date'])) ?>
                                                    (<?= $days_left ?> hari)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card flex-fill mb-0">
                    <div class="card-header">
                        <i class="bi bi-clock-history me-2" style="color: var(--primary);"></i>Aktivitas Terkini
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_activities)): ?>
                            <div class="p-4 text-center text-muted small">Belum ada aktivitas.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="list-group-item d-flex gap-3">
                                        <div class="mt-1" style="color: var(--primary);">
                                            <i class="bi bi-record-circle-fill" style="font-size: 0.8rem;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="color: var(--text-dark); font-size: 0.9rem;">
                                                <?= htmlspecialchars($activity['title']) ?>
                                            </div>
                                            <div class="text-muted mt-1" style="font-size: 0.85rem; line-height: 1.4;">
                                                <?= htmlspecialchars($activity['message']) ?>
                                            </div>
                                            <div class="text-muted mt-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                                <?= strtoupper(timeAgo($activity['created_at'])) ?>
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

    <!-- New Project Modal -->
<div class="modal fade" id="newProjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fw-bold" style="color: var(--text-dark);">Buat Proyek Baru</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newProjectForm" method="POST">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
                            Nama Proyek
                        </label>
                        <input type="text" class="form-control form-control-lg fs-6" name="name" 
                               required placeholder="Contoh: Redesign Aplikasi Mobile">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
                            Deskripsi Opsional
                        </label>
                        <textarea class="form-control form-control-lg fs-6" name="description" 
                                  rows="3" placeholder="Tujuan singkat proyek..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-decoration-none fw-bold" data-bs-dismiss="modal" style="color: var(--text-muted);">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">
                        <span class="spinner-border spinner-border-sm d-none me-2"></span>Simpan Proyek
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-4 mt-5" style="z-index: 9999;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Theme Toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            
            const themeIcon = document.querySelector('.theme-toggle i');
            if (themeIcon) {
                themeIcon.className = `bi bi-${newTheme === 'dark' ? 'sun' : 'moon'}`;
            }
            
            document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
        }

        // ========== OVERDUE COUNT FUNCTIONS ==========
        function updateOverdueCount() {
            fetch('api/overdue.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('overdueCountBadge');
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error updating overdue count:', error));
        }

        // ========== NOTIFICATION FUNCTIONS ==========
        function markAllNotificationsRead() {
            fetch('api/notifications.php?action=mark_all_read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        // ========== PROJECT FUNCTIONS ==========
        function openNewProjectModal() {
            const modal = new bootstrap.Modal(document.getElementById('newProjectModal'));
            modal.show();
        }

        // New Project Form Submit
document.getElementById('newProjectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    const formData = new FormData(form);
    formData.append('action', 'create'); // Tambahkan action
    
    fetch('api/projects.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('newProjectModal')).hide();
            showToast('Proyek berhasil dibuat!', 'success');
            setTimeout(() => { window.location.href = 'project.php?id=' + data.project_id; }, 1000);
        } else {
            showToast(data.message || 'Gagal membuat proyek', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Terjadi kesalahan sistem', 'danger');
    })
    .finally(() => {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
        submitBtn.innerHTML = originalText;
    });
});

// Reset Modal on Hide
document.getElementById('newProjectModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newProjectForm').reset();
});

        // Reset Modal on Hide
        document.getElementById('newProjectModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('newProjectForm').reset();
        });

        // ========== TOAST NOTIFICATION ==========
        function showToast(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            let bgClass, iconClass;
            
            switch(type) {
                case 'success':
                    bgClass = 'bg-success';
                    iconClass = 'bi-check-circle-fill';
                    break;
                case 'danger':
                    bgClass = 'bg-danger';
                    iconClass = 'bi-exclamation-triangle-fill';
                    break;
                default:
                    bgClass = 'bg-primary';
                    iconClass = 'bi-info-circle-fill';
            }
            
            toast.className = `toast align-items-center text-white ${bgClass} border-0 shadow-lg`;
            toast.style.borderRadius = '12px';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex p-2">
                    <div class="toast-body fw-bold fs-6">
                        <i class="bi ${iconClass} me-2 fs-5"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            container.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateOverdueCount();
        });

        // Update overdue count every 5 minutes
        setInterval(updateOverdueCount, 300000);
    </script>
</body>
</html>