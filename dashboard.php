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
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            /* Light Mode Variables */
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #e0e7ff;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --info: #3b82f6;
            --info-light: #dbeafe;
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            --surface-active: #f1f5f9;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-hover: 0 20px 25px -5px rgba(99, 102, 241, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            
            --transition: all 0.2s ease;
        }

        /* Dark Mode Variables */
        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #1e1b4b;
            --success: #10b981;
            --success-light: #064e3b;
            --warning: #f59e0b;
            --warning-light: #422006;
            --danger: #ef4444;
            --danger-light: #450a0a;
            --info: #3b82f6;
            --info-light: #1e293b;
            
            --bg-body: #0f172a;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --surface: #1e293b;
            --surface-hover: #334155;
            --surface-active: #2d3a4e;
            
            --text-dark: #f1f5f9;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --text-light: #64748b;
            
            --border-color: #334155;
            --border-light: #1e293b;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 20px 25px -5px rgba(129, 140, 248, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            transition: var(--transition);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--border-light); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        /* Navbar */
        .navbar {
            background: rgba(var(--surface-rgb, 255,255,255), 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        [data-theme="dark"] .navbar {
            background: rgba(30, 41, 59, 0.8);
        }
        
        .navbar-brand { 
            font-weight: 700; 
            font-size: 1.2rem; 
            color: var(--text-dark) !important; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
        }
        
        .nav-link { 
            font-weight: 500; 
            color: var(--text-muted) !important; 
            padding: 0.5rem 1rem !important; 
            border-radius: 8px; 
            transition: var(--transition); 
            font-size: 0.9rem;
        }
        .nav-link:hover, .nav-link.active { 
            color: var(--primary) !important; 
            background: var(--primary-light); 
        }

        /* Theme Toggle */
        .theme-toggle {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }
        
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .stat-icon.purple { background: var(--primary-light); color: var(--primary); }
        .stat-icon.orange { background: var(--warning-light); color: var(--warning); }
        .stat-icon.green { background: var(--success-light); color: var(--success); }
        .stat-icon.red { background: var(--danger-light); color: var(--danger); }
        .stat-icon.blue { background: var(--info-light); color: var(--info); }
        
        .stat-info { flex: 1; }
        .stat-label { 
            font-size: 0.7rem; 
            font-weight: 600; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            margin-bottom: 0.25rem;
        }
        .stat-value { 
            font-size: 1.75rem; 
            font-weight: 800; 
            color: var(--text-dark); 
            line-height: 1.2;
        }

        /* Alert Banner */
        .alert-banner {
            background: var(--danger-light);
            border-left: 4px solid var(--danger);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .alert-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .alert-icon {
            width: 44px;
            height: 44px;
            background: var(--danger);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .alert-text h5 {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }
        
        .alert-text p {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-bottom: 0;
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: var(--primary);
        }

        /* Project Cards */
        .projects-grid {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .project-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1rem;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .project-card:hover {
            transform: translateX(4px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            background: var(--surface-hover);
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .project-name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.95rem;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .project-progress {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.8rem;
        }
        
        .progress-bar-custom {
            height: 6px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .project-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .task-badge {
            background: var(--surface-hover);
            color: var(--text-muted);
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }
        
        .member-count {
            font-size: 0.7rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Card Container */
        .card-custom {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .card-custom-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            background: transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-custom-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.95rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-custom-badge {
            background: var(--surface-hover);
            color: var(--text-muted);
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
        }
        
        .card-custom-body {
            padding: 0;
            flex: 1;
            overflow-y: auto;
            max-height: 400px;
        }

        /* List Items */
        .list-item {
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
            cursor: pointer;
        }
        
        .list-item:hover {
            background: var(--surface-hover);
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .deadline-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .deadline-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .deadline-icon.critical { background: var(--danger-light); color: var(--danger); }
        .deadline-icon.warning { background: var(--warning-light); color: var(--warning); }
        
        .deadline-info {
            flex: 1;
            min-width: 0;
        }
        
        .deadline-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .deadline-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .deadline-project {
            background: var(--surface-hover);
            color: var(--text-muted);
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
        }
        
        .deadline-date {
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .deadline-date.critical { color: var(--danger); }
        .deadline-date.warning { color: var(--warning); }
        
        .activity-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            margin-top: 0.5rem;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .activity-desc {
            color: var(--text-muted);
            font-size: 0.75rem;
            line-height: 1.4;
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.65rem;
            color: var(--text-light);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .empty-state {
            padding: 2rem;
            text-align: center;
        }
        
        .empty-icon {
            font-size: 2.5rem;
            color: var(--text-muted);
            opacity: 0.5;
            margin-bottom: 0.75rem;
        }
        
        .empty-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .empty-text {
            color: var(--text-muted);
            font-size: 0.75rem;
            margin-bottom: 0;
        }

        /* Buttons */
        .btn { 
            padding: 0.5rem 1.25rem; 
            border-radius: 10px; 
            font-weight: 600; 
            transition: var(--transition); 
            font-size: 0.85rem;
        }
        .btn-primary { 
            background: var(--primary); 
            border: none; 
            color: white; 
        }
        .btn-primary:hover { 
            background: var(--primary-dark); 
            transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); 
        }
        .btn-soft-primary { 
            background: var(--primary-light); 
            color: var(--primary); 
            border: none; 
        }
        .btn-soft-primary:hover { 
            background: var(--primary); 
            color: white; 
            transform: translateY(-1px);
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
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-md); 
            padding: 0.5rem; 
            box-shadow: var(--shadow-lg);
        }
        .dropdown-item { 
            color: var(--text-main); 
            border-radius: 8px; 
            padding: 0.5rem 1rem; 
            font-size: 0.85rem;
        }
        .dropdown-item:hover { 
            background: var(--surface-hover); 
            color: var(--text-dark); 
        }
        .dropdown-divider { border-color: var(--border-color); }

        /* Modal */
        .modal-content { 
            background: var(--surface); 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-xl); 
        }
        .modal-header { 
            border-bottom: 1px solid var(--border-color); 
            padding: 1.25rem 1.5rem; 
        }
        .modal-header .modal-title { 
            color: var(--text-dark); 
            font-weight: 700;
        }
        [data-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .modal-body { 
            padding: 1.5rem; 
            color: var(--text-main); 
        }
        .modal-footer { 
            border-top: 1px solid var(--border-color); 
            padding: 1rem 1.5rem; 
            background: var(--surface-hover); 
        }
        
        /* Form */
        .form-control, .form-select { 
            border-radius: 10px; 
            padding: 0.6rem 1rem; 
            border: 1px solid var(--border-color); 
            background: var(--surface-hover); 
            color: var(--text-dark); 
            font-size: 0.9rem;
        }
        .form-control:focus, .form-select:focus { 
            background: var(--surface); 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); 
            outline: none; 
        }
        .form-label { 
            font-size: 0.7rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            color: var(--text-muted); 
            margin-bottom: 0.5rem;
        }

        /* Toast */
        .toast { 
            background: var(--surface) !important; 
            color: var(--text-dark) !important; 
            border: 1px solid var(--border-color) !important; 
            border-radius: var(--radius-md) !important; 
        }
        
        /* Avatar */
        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .alert-banner {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .alert-content {
                width: 100%;
            }
            
            .btn-danger-soft {
                align-self: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .navbar-brand span {
                display: none;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand" href="dashboard.php">
                <div class="brand-icon"><i class="bi bi-check2-square"></i></div>
                <span>Task Manager</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-2" style="color: var(--text-dark);"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ps-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="overdue.php" id="overdueLink">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Terlambat
                            <span class="badge bg-danger ms-1" id="overdueCountBadge" style="display: <?= $overdue_count > 0 ? 'inline-block' : 'none' ?>; font-size: 0.65rem;">
                                <?= $overdue_count ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">
                            <i class="bi bi-folder2-open me-2"></i>Semua Proyek
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav align-items-center gap-2">
                    <li class="nav-item">
                        <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                            <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                        </div>
                    </li>
                    
                    <li class="nav-item d-none d-md-block">
                        <button class="btn btn-primary" onclick="openNewProjectModal()">
                            <i class="bi bi-plus-lg me-1"></i> Proyek Baru
                        </button>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" style="padding: 0 !important;">
                            <div class="position-relative">
                                <i class="bi bi-bell fs-5" style="color: var(--text-muted);"></i>
                                <?php if ($notif_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 0.2rem 0.4rem;">
                                        <?= $notif_count ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="width: 340px;">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                <h6 class="mb-0 fw-bold" style="color: var(--text-dark);">Notifikasi</h6>
                                <?php if ($notif_count > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?= $notif_count ?> Baru</span>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($notifications)): ?>
                                <div class="empty-state py-4">
                                    <i class="bi bi-bell-slash empty-icon"></i>
                                    <p class="empty-text">Belum ada notifikasi baru</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 360px; overflow-y: auto;">
                                    <?php foreach ($notifications as $notif): ?>
                                        <a class="dropdown-item d-flex p-3 border-bottom" href="notifications.php" style="white-space: normal;">
                                            <div class="me-3">
                                                <?php 
                                                    $bg = 'var(--surface-hover)'; 
                                                    $color = 'var(--text-muted)'; 
                                                    $icon = 'bi-info-circle';
                                                    if ($notif['type'] == 'assignment') { $bg = 'var(--primary-light)'; $color = 'var(--primary)'; $icon = 'bi-person-check'; }
                                                    elseif ($notif['type'] == 'comment') { $bg = 'var(--success-light)'; $color = 'var(--success)'; $icon = 'bi-chat-dots'; }
                                                    elseif ($notif['type'] == 'deadline') { $bg = 'var(--warning-light)'; $color = 'var(--warning)'; $icon = 'bi-clock-history'; }
                                                ?>
                                                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: <?= $bg ?>; color: <?= $color ?>;">
                                                    <i class="bi <?= $icon ?>"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-semibold" style="color: var(--text-dark); font-size: 0.85rem;"><?= htmlspecialchars($notif['title']) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($notif['message']) ?></div>
                                                <small class="text-muted mt-1 d-block" style="font-size: 0.65rem;"><?= timeAgo($notif['created_at']) ?></small>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="p-2 text-center border-top">
                                    <a href="notifications.php" class="text-decoration-none small" style="color: var(--primary);">Lihat semua notifikasi</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" style="padding: 0 !important;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=6366f1&color=fff&size=36&bold=true&length=2" 
                                 class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                            <div class="d-none d-xl-block text-start">
                                <div class="fw-semibold" style="color: var(--text-dark); font-size: 0.85rem;">
                                    <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <?= $is_admin ? 'Admin' : 'Member' ?>
                                </small>
                            </div>
                            <i class="bi bi-chevron-down text-muted d-none d-xl-block" style="font-size: 0.7rem;"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow mt-2">
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person me-2"></i>Profil Saya
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Keluar
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-4 py-4">
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="bi bi-briefcase"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Total Proyek</div>
                    <div class="stat-value"><?= $stats['total_projects'] ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Tugas Aktif</div>
                    <div class="stat-value"><?= $stats['active_tasks'] ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Selesai</div>
                    <div class="stat-value"><?= $stats['completed_tasks'] ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Mendekati Deadline</div>
                    <div class="stat-value"><?= $stats['upcoming_deadlines'] ?></div>
                </div>
            </div>
        </div>

        <!-- Overdue Alert -->
        <?php if ($overdue_count > 0): ?>
        <div class="alert-banner">
            <div class="alert-content">
                <div class="alert-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="alert-text">
                    <h5><?= $overdue_count ?> tugas terlambat</h5>
                    <p>Tugas-tugas ini melewati batas waktu dan perlu segera diselesaikan.</p>
                </div>
            </div>
            <a href="overdue.php" class="btn btn-danger-soft">
                <i class="bi bi-eye me-1"></i>Lihat Semua
            </a>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="row g-4">
            
            <!-- Proyek Aktif Section -->
            <div class="col-xl-4 col-lg-12">
                <div class="section-header">
                    <h5 class="section-title">
                        <i class="bi bi-folder-fill"></i> Proyek Aktif
                    </h5>
                    <a href="projects.php" class="btn btn-soft-primary btn-sm rounded-pill px-3">Lihat Semua</a>
                </div>
                
                <?php if (empty($projects)): ?>
                    <div class="card-custom">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-folder-plus"></i>
                            </div>
                            <div class="empty-title">Belum ada proyek</div>
                            <p class="empty-text">Buat proyek pertamamu untuk mulai mengelola tugas</p>
                            <button class="btn btn-primary btn-sm mt-3" onclick="openNewProjectModal()">
                                <i class="bi bi-plus-lg me-1"></i>Buat Proyek
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="projects-grid">
                        <?php foreach (array_slice($projects, 0, 5) as $project): ?>
                            <?php $p_stats = getProjectStats($project['id']); ?>
                            <div class="project-card" onclick="window.location.href='project.php?id=<?= $project['id'] ?>'">
                                <div class="project-header">
                                    <h6 class="project-name"><?= htmlspecialchars($project['name']) ?></h6>
                                    <span class="project-progress"><?= $p_stats['progress'] ?>%</span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="progress-fill" style="width: <?= $p_stats['progress'] ?>%"></div>
                                </div>
                                <div class="project-footer">
                                    <span class="task-badge">
                                        <i class="bi bi-check2-circle me-1 text-success"></i><?= $p_stats['done'] ?>/<?= $p_stats['total'] ?> Tugas
                                    </span>
                                    <?php if ($is_admin && isset($project['total_members'])): ?>
                                        <span class="member-count">
                                            <i class="bi bi-people-fill"></i> <?= $project['total_members'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Deadline Section -->
            <div class="col-xl-4 col-lg-6">
                <div class="card-custom">
                    <div class="card-custom-header">
                        <div class="card-custom-title">
                            <i class="bi bi-calendar-check" style="color: var(--warning);"></i>
                            Deadline Mendekat
                        </div>
                        <span class="card-custom-badge"><?= count($deadlines) ?></span>
                    </div>
                    <div class="card-custom-body">
                        <?php if (empty($deadlines)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-emoji-smile"></i>
                                </div>
                                <div class="empty-title">Santai Dulu!</div>
                                <p class="empty-text">Tidak ada tugas yang mendekati deadline</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($deadlines as $task): ?>
                                <?php 
                                    $days_left = ceil((strtotime($task['due_date']) - time()) / 86400);
                                    $is_critical = $days_left <= 2;
                                ?>
                                <div class="list-item" onclick="window.location.href='project.php?id=<?= $task['project_id'] ?>'">
                                    <div class="deadline-item">
                                        <div class="deadline-icon <?= $is_critical ? 'critical' : 'warning' ?>">
                                            <i class="bi bi-hourglass-split"></i>
                                        </div>
                                        <div class="deadline-info">
                                            <div class="deadline-title"><?= htmlspecialchars($task['title']) ?></div>
                                            <div class="deadline-meta">
                                                <span class="deadline-project"><?= htmlspecialchars($task['project_name']) ?></span>
                                                <span class="deadline-date <?= $is_critical ? 'critical' : 'warning' ?>">
                                                    <i class="bi bi-calendar3 me-1"></i><?= date('d M', strtotime($task['due_date'])) ?> (<?= $days_left ?> hari)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Section -->
            <div class="col-xl-4 col-lg-6">
                <div class="card-custom">
                    <div class="card-custom-header">
                        <div class="card-custom-title">
                            <i class="bi bi-clock-history" style="color: var(--primary);"></i>
                            Aktivitas Terbaru
                        </div>
                    </div>
                    <div class="card-custom-body">
                        <?php if (empty($recent_activities)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-journal-x"></i>
                                </div>
                                <div class="empty-title">Belum ada aktivitas</div>
                                <p class="empty-text">Aktivitas terbaru akan muncul di sini</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-dot"></div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?= htmlspecialchars($activity['title']) ?></div>
                                        <div class="activity-desc"><?= htmlspecialchars($activity['message']) ?></div>
                                        <div class="activity-time"><?= strtoupper(timeAgo($activity['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="newProjectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Proyek Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newProjectForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Proyek</label>
                            <input type="text" class="form-control" name="name" required placeholder="Contoh: Website Redesign">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Tujuan dan detail proyek..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Proyek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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

        function openNewProjectModal() {
            const modal = new bootstrap.Modal(document.getElementById('newProjectModal'));
            modal.show();
        }

        document.getElementById('newProjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            
            const formData = new FormData(form);
            formData.append('action', 'create');
            
            fetch('api/projects.php', { method: 'POST', body: formData })
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
                .catch(error => showToast('Terjadi kesalahan sistem', 'danger'))
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });

        document.getElementById('newProjectModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('newProjectForm').reset();
        });

        function showToast(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            let bgClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-primary');
            let iconClass = type === 'success' ? 'bi-check-circle-fill' : (type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
            
            toast.className = `toast align-items-center text-white ${bgClass} border-0 shadow`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex p-2">
                    <div class="toast-body fw-semibold small">
                        <i class="bi ${iconClass} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            container.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateOverdueCount();
        });
        setInterval(updateOverdueCount, 300000);
    </script>
</body>
</html>