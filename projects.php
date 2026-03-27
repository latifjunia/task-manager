<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Dapatkan semua proyek
$projects = $is_admin ? getAllProjects() : getUserProjects($user_id);

// Data untuk notifikasi dan overdue di Navbar
$notif_count = getNotificationCount($user_id);
$overdue_count = getOverdueTasksCount($user_id);
$notifications = getUnreadNotifications($user_id);

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Proyek - Task Manager</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #e0e7ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: #e2e8f0;
            
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #1e1b4b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            
            --bg-body: #0f172a;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --surface: #1e293b;
            --surface-hover: #334155;
            
            --text-dark: #f1f5f9;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            
            --border-color: #334155;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--border-color); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        /* Navbar */
        .navbar {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(20px);
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
            width: 36px; 
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white; 
            font-size: 1.1rem;
        }
        
        .nav-link { 
            font-weight: 500; 
            color: var(--text-muted) !important; 
            padding: 0.5rem 1rem !important; 
            border-radius: 8px; 
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .nav-link:hover, .nav-link.active { 
            color: var(--primary) !important; 
            background: var(--primary-light); 
        }

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
            transition: all 0.2s ease;
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Header Section */
        .header-section {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-title i {
            color: var(--primary);
            font-size: 1.75rem;
        }
        
        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        /* Search Bar */
        .search-container {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-wrapper {
            position: relative;
            flex: 1;
            min-width: 280px;
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--surface);
            color: var(--text-dark);
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .back-btn {
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .back-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateX(-2px);
            text-decoration: none;
        }

        /* Projects Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }
        
        .project-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }
        
        .project-card:hover::before {
            transform: scaleX(1);
        }
        
        .member-badge {
            background: var(--surface-hover);
            color: var(--text-muted);
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            float: right;
            border: 1px solid var(--border-color);
        }
        
        .project-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
            margin: 0 0 0.75rem 0;
            padding-right: 60px;
        }
        
        .project-description {
            color: var(--text-muted);
            font-size: 0.8rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            min-height: 2.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Progress Section */
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .progress-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .progress-value {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .progress-bar-custom {
            height: 6px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        /* ===== MINI STATS - FIX POSISI TENGAH ===== */
        .mini-stats {
            background: var(--surface-hover);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 0.75rem;
            border: 1px solid var(--border-color);
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }
        
        .stat-number {
            font-size: 1rem;
            font-weight: 700;
            display: block;
            line-height: 1.3;
        }
        
        .stat-number.todo { color: var(--text-muted); }
        .stat-number.progress { color: var(--warning); }
        .stat-number.done { color: var(--success); }
        
        .divider {
            width: 1px;
            height: 35px;
            background: var(--border-color);
        }
        
        /* Overdue Badge */
        .overdue-badge {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.4rem 0.75rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            width: 100%;
            justify-content: center;
        }
        
        [data-theme="dark"] .overdue-badge {
            background: #7f1d1d;
            color: #fecaca;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--text-muted);
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-text {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
        }
        
        /* Dropdown */
        .dropdown-menu { 
            background: var(--surface); 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        .dropdown-item { 
            color: var(--text-main); 
            border-radius: 8px; 
            padding: 0.5rem 1rem; 
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        .dropdown-item:hover { 
            background: var(--surface-hover); 
            color: var(--text-dark); 
        }
        .dropdown-divider { border-color: var(--border-color); }

        /* Search Empty State */
        .search-empty {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            margin-top: 1rem;
        }
        
        .search-empty-icon {
            width: 80px;
            height: 80px;
            background: var(--surface-hover);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .search-empty-icon i {
            font-size: 2.5rem;
            color: var(--text-muted);
        }
        
        /* Animation */
        .project-card {
            animation: fadeInUp 0.4s ease forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== DARK MODE FIX - BACKGROUND MINI-STATS GELAP ===== */
        [data-theme="dark"] .mini-stats {
            background: #0f172a !important;
            border-color: #334155 !important;
        }
        
        [data-theme="dark"] .stat-item {
            background: transparent !important;
        }
        
        [data-theme="dark"] .stat-number {
            color: #f1f5f9 !important;
        }
        
        [data-theme="dark"] .stat-number.todo {
            color: #94a3b8 !important;
        }
        
        [data-theme="dark"] .stat-number.progress {
            color: #fbbf24 !important;
        }
        
        [data-theme="dark"] .stat-number.done {
            color: #34d399 !important;
        }
        
        [data-theme="dark"] .stat-label {
            color: #64748b !important;
        }
        
        [data-theme="dark"] .divider {
            background: #334155 !important;
        }
        
        [data-theme="dark"] .member-badge {
            background: #334155 !important;
            color: #94a3b8 !important;
            border-color: #475569 !important;
        }
        
        [data-theme="dark"] .project-description {
            color: #94a3b8 !important;
        }
        
        [data-theme="dark"] .project-name {
            color: #f1f5f9 !important;
        }
        
        [data-theme="dark"] .page-title {
            color: #f1f5f9 !important;
        }
        
        [data-theme="dark"] .page-subtitle {
            color: #94a3b8 !important;
        }
        
        [data-theme="dark"] .search-input {
            color: #ffffff !important;
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }
        
        [data-theme="dark"] .search-input::placeholder {
            color: #64748b !important;
        }
        
        [data-theme="dark"] .search-input:focus {
            background-color: #0f172a !important;
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2) !important;
        }
        
        [data-theme="dark"] .search-icon {
            color: #64748b !important;
        }
        
        [data-theme="dark"] .back-btn {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #cbd5e1 !important;
        }
        
        [data-theme="dark"] .back-btn:hover {
            color: #818cf8 !important;
            border-color: #818cf8 !important;
            background: #334155 !important;
        }
        
        [data-theme="dark"] .empty-state,
        [data-theme="dark"] .search-empty {
            background: #1e293b !important;
            border-color: #334155 !important;
        }
        
        [data-theme="dark"] .empty-title {
            color: #f1f5f9 !important;
        }
        
        [data-theme="dark"] .empty-text {
            color: #94a3b8 !important;
        }
        
        [data-theme="dark"] .project-card {
            background: #1e293b !important;
            border-color: #334155 !important;
        }
        
        [data-theme="dark"] .project-card:hover {
            border-color: #818cf8 !important;
        }
        
        [data-theme="dark"] .progress-label {
            color: #94a3b8 !important;
        }
        
        [data-theme="dark"] .progress-value {
            color: #818cf8 !important;
        }
        
        [data-theme="dark"] .progress-bar-custom {
            background: #334155 !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-wrapper {
                width: 100%;
            }
            
            .back-btn {
                justify-content: center;
            }
            
            .navbar-collapse {
                background: var(--surface);
                padding: 1rem;
                border-radius: 12px;
                margin-top: 0.5rem;
                border: 1px solid var(--border-color);
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            .project-card {
                padding: 1rem;
            }
            
            .mini-stats {
                padding: 0.5rem;
            }
            
            .stat-number {
                font-size: 0.9rem;
            }
            
            .stat-label {
                font-size: 0.55rem;
            }
            
            .project-name {
                font-size: 1rem;
                padding-right: 50px;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="overdue.php">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Terlambat
                            <span class="badge bg-danger ms-1" id="overdueCountBadge" style="display: <?= $overdue_count > 0 ? 'inline-block' : 'none' ?>; font-size: 0.65rem;">
                                <?= $overdue_count ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="projects.php">
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
                                <div class="text-center py-4">
                                    <i class="bi bi-bell-slash fs-1 text-muted opacity-50 mb-2 d-block"></i>
                                    <p class="text-muted mb-0 small">Belum ada notifikasi baru</p>
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
        
        <div class="header-section">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="page-title">
                        <i class="bi bi-folder-fill"></i>
                        Semua Proyek
                    </h1>
                    <p class="page-subtitle" id="projectCountDisplay">
                        <?= count($projects) ?> proyek <?= $is_admin ? 'di sistem' : 'yang kamu ikuti' ?>
                    </p>
                </div>
                
                <div class="search-container">
                    <div class="search-wrapper">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="searchProject" class="search-input" placeholder="Cari proyek..." onkeyup="filterProjects()">
                    </div>
                    <a href="dashboard.php" class="back-btn">
                        <i class="bi bi-arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-folder-x"></i>
                </div>
                <h3 class="empty-title">Belum ada proyek</h3>
                <p class="empty-text">Mulai buat proyek pertamamu untuk mengelola tugas dengan lebih baik</p>
                <a href="dashboard.php" class="btn-primary">
                    <i class="bi bi-plus-lg"></i> Buat Proyek Baru
                </a>
            </div>
        <?php else: ?>
            <div class="projects-grid" id="projectGrid">
                <?php foreach ($projects as $project): ?>
                    <?php 
                        $p_stats = getProjectStats($project['id']); 
                        $safe_name = htmlspecialchars(strtolower($project['name']));
                        $safe_desc = htmlspecialchars(strtolower($project['description'] ?? ''));
                    ?>
                    <div class="project-card" data-name="<?= $safe_name ?>" data-desc="<?= $safe_desc ?>" onclick="window.location.href='project.php?id=<?= $project['id'] ?>'">
                        <?php if ($is_admin && isset($project['total_members'])): ?>
                            <span class="member-badge">
                                <i class="bi bi-people-fill"></i>
                                <?= $project['total_members'] ?>
                            </span>
                        <?php endif; ?>
                        
                        <h5 class="project-name" title="<?= htmlspecialchars($project['name']) ?>">
                            <?= htmlspecialchars($project['name']) ?>
                        </h5>
                        
                        <div class="project-description">
                            <?= htmlspecialchars($project['description'] ?: 'Tidak ada deskripsi') ?>
                        </div>
                        
                        <div class="progress-header">
                            <span class="progress-label">Progress</span>
                            <span class="progress-value"><?= $p_stats['progress'] ?>%</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: <?= $p_stats['progress'] ?>%"></div>
                        </div>
                        
                        <!-- MINI STATS -->
                        <div class="mini-stats">
                            <div class="stat-item">
                                <span class="stat-label">To Do</span>
                                <div class="stat-number todo"><?= $p_stats['todo'] ?></div>
                            </div>
                            <div class="divider"></div>
                            <div class="stat-item">
                                <span class="stat-label">Progress</span>
                                <div class="stat-number progress"><?= $p_stats['in_progress'] ?></div>
                            </div>
                            <div class="divider"></div>
                            <div class="stat-item">
                                <span class="stat-label">Selesai</span>
                                <div class="stat-number done"><?= $p_stats['done'] ?></div>
                            </div>
                        </div>
                        
                        <?php if ($p_stats['overdue'] > 0): ?>
                            <div class="overdue-badge">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <?= $p_stats['overdue'] ?> tugas terlambat
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="emptySearchState" style="display: none;">
                <div class="search-empty">
                    <div class="search-empty-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5 class="fw-bold" style="color: var(--text-dark);">Proyek tidak ditemukan</h5>
                    <p class="text-muted mb-0">Tidak ada proyek yang cocok dengan kata kunci pencarianmu</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

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

        function filterProjects() {
            const query = document.getElementById('searchProject').value.toLowerCase();
            const projectCards = document.querySelectorAll('.project-card');
            const emptyState = document.getElementById('emptySearchState');
            const countDisplay = document.getElementById('projectCountDisplay');
            const totalProjects = <?= count($projects) ?>;
            
            let visibleCount = 0;

            projectCards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const desc = card.getAttribute('data-desc') || '';

                if (name.includes(query) || desc.includes(query)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (countDisplay) {
                if (query === '') {
                    countDisplay.textContent = `${totalProjects} proyek ${<?= $is_admin ? 'true' : 'false' ?> ? 'di sistem' : 'yang kamu ikuti'}`;
                } else {
                    countDisplay.textContent = `${visibleCount} proyek ditemukan`;
                }
            }

            if (visibleCount === 0 && projectCards.length > 0) {
                emptyState.style.display = 'block';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
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

        setInterval(updateOverdueCount, 300000);
    </script>
</body>
</html>