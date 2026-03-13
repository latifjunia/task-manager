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
            background: rgba(255,255,255,0.85) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background 0.3s ease;
        }
        
        [data-theme="dark"] .navbar {
            background: rgba(30, 41, 59, 0.85) !important;
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
            margin: 0 0.2rem;
        }
        .nav-link:hover, .nav-link.active { 
            color: var(--primary) !important; 
            background: var(--primary-light); 
        }

        /* UI Elements */
        .theme-toggle {
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            background: var(--surface); border: 1px solid var(--border-color-solid); color: var(--text-main); 
            cursor: pointer; transition: 0.3s;
        }
        .theme-toggle:hover { background: var(--primary-light); color: var(--primary); transform: rotate(15deg); }
        .icon-circle-sm { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        
        .btn { padding: 0.6rem 1.2rem; border-radius: 12px; font-weight: 600; transition: 0.3s; letter-spacing: 0.3px; }
        .btn-light { background: var(--surface); color: var(--text-dark); border: 1px solid var(--border-color); font-weight: 600; }
        .btn-light:hover { background: var(--surface-hover); color: var(--primary); }

        /* Search Input Modern */
        .search-input {
            padding-left: 45px; background: var(--surface); border: 1px solid var(--border-color); 
            color: var(--text-dark); transition: all 0.3s ease; height: 45px;
            box-shadow: var(--shadow-soft);
        }
        .search-input:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); outline: none; background: var(--surface);
        }
        .search-input::placeholder { color: var(--text-muted); opacity: 0.7; font-weight: 400; }
        .search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10; }

        /* Dropdown */
        .dropdown-menu { background: var(--surface); border-color: var(--border-color); border-radius: 12px; padding: 0.5rem; box-shadow: var(--card-hover-shadow); }
        .dropdown-item { color: var(--text-main); border-radius: 8px; padding: 0.5rem 1rem; transition: 0.2s; }
        .dropdown-item:hover { background: var(--surface-hover); color: var(--text-dark); }
        .dropdown-divider { border-color: var(--border-color); }

        /* Project Cards */
        .card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--card-shadow); transition: 0.3s; overflow: hidden; }
        .project-card { padding: 1.5rem; height: 100%; display: flex; flex-direction: column; cursor: pointer; border-radius: var(--radius-md); background: var(--surface); border: 1px solid var(--border-color); transition: all 0.3s ease; box-shadow: var(--card-shadow); }
        .project-card:hover { border-color: var(--primary); box-shadow: var(--card-hover-shadow); transform: translateY(-5px); background: var(--surface-hover); }
        .project-title { font-weight: 700; color: var(--text-dark); font-size: 1.15rem; line-height: 1.3; margin-bottom: 0.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .project-desc { font-size: 0.85rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 0.5rem 0 1.2rem 0; min-height: 2.5rem; }
        
        .progress-wrapper { background: var(--border-color-solid); height: 6px; border-radius: 8px; overflow: hidden; margin: 0.5rem 0; }
        .progress-fill { background: var(--primary); height: 100%; border-radius: 8px; transition: width 1s ease; }
        
        .mini-stats { background: var(--surface-hover); padding: 0.8rem; border-radius: 12px; display: flex; justify-content: space-around; margin-top: auto; border: 1px solid var(--border-color); }
        .mini-stat-item { text-align: center; }
        .mini-stat-item span { display: block; font-size: 0.65rem; font-weight: 600; color: var(--text-muted); }
        .mini-stat-item strong { font-size: 1rem; color: var(--text-dark); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* =========================================
           MOBILE & TABLET RESPONSIVE FIXES
           ========================================= */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: var(--surface); padding: 1rem; border-radius: 12px; 
                box-shadow: var(--shadow-hover); margin-top: 10px; border: 1px solid var(--border-color); 
                position: absolute; top: 100%; left: 1rem; right: 1rem; z-index: 1050;
            }
            .navbar-nav { gap: 0.5rem !important; }
            .navbar-nav.align-items-center {
                align-items: flex-start !important; flex-direction: row; flex-wrap: wrap; gap: 10px !important;
                margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color);
            }
            .navbar-nav .dropdown-menu { position: static !important; float: none; box-shadow: none; border: none; background: transparent; padding: 0; }
        }

        @media (max-width: 767.98px) {
            .container-fluid { padding-left: 1rem !important; padding-right: 1rem !important; }
            .navbar-brand span:not(.brand-icon) { font-size: 1.1rem; }
            .header-actions { flex-direction: column !important; width: 100%; align-items: stretch !important; gap: 0.8rem !important; margin-top: 1rem; }
            .header-actions .position-relative, .header-actions .btn { width: 100%; }
            .project-card { padding: 1.25rem; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg position-relative">
        <div class="container-fluid px-3 px-lg-5">
            <a class="navbar-brand" href="dashboard.php">
                <div class="brand-icon"><i class="bi bi-layers-half"></i></div>
                <span>Task Manager</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1" style="color: var(--text-dark);"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ps-lg-4 gap-1">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-grid-1x2 me-2"></i>Dashboard
                        </a>
                    </li>
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
                        <a class="nav-link active" href="projects.php">
                            <i class="bi bi-folder me-2"></i>Semua Proyek
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item">
                        <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                            <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative d-flex align-items-center" href="#" data-bs-toggle="dropdown" style="padding: 0.5rem !important;">
                            <div class="icon-circle-sm" style="background: var(--surface-hover); color: var(--text-main);">
                                <i class="bi bi-bell"></i>
                                <?php if ($notif_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white" style="font-size: 0.65rem; padding: 0.25rem 0.5rem;">
                                        <?= $notif_count ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="d-lg-none ms-2 fw-bold text-dark">Notifikasi</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 350px; border-radius: 16px; padding: 0; overflow: hidden; max-width: 90vw;">
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
                                        <a class="dropdown-item d-flex p-3 border-bottom" href="notifications.php" style="white-space: normal;">
                                            <div class="me-3 mt-1">
                                                <?php 
                                                    $bg = 'var(--surface-hover)'; 
                                                    $color = 'var(--text-muted)'; 
                                                    $icon = 'bi-info-circle';
                                                    if ($notif['type'] == 'assignment') { $bg = 'var(--primary-light)'; $color = 'var(--primary)'; $icon = 'bi-person-check'; }
                                                    elseif ($notif['type'] == 'comment') { $bg = 'var(--success-light)'; $color = 'var(--success)'; $icon = 'bi-chat-dots'; }
                                                    elseif ($notif['type'] == 'deadline') { $bg = 'var(--warning-light)'; $color = 'var(--warning)'; $icon = 'bi-clock'; }
                                                ?>
                                                <div class="icon-circle-sm" style="background: <?= $bg ?>; color: <?= $color ?>; width: 36px; height: 36px; font-size: 1rem;">
                                                    <i class="bi <?= $icon ?>"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="color: var(--text-dark); font-size: 0.9rem; margin-bottom: 2px;"><?= htmlspecialchars($notif['title']) ?></div>
                                                <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4;"><?= htmlspecialchars($notif['message']) ?></div>
                                                <small class="text-muted opacity-75 mt-1 d-block" style="font-size: 0.7rem;">
                                                    <i class="bi bi-clock me-1"></i><?= timeAgo($notif['created_at']) ?>
                                                </small>
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

                    <li class="nav-item dropdown w-100 w-lg-auto mt-2 mt-lg-0">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" style="padding: 0 !important;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=0f172a&color=fff&size=40" 
                                 class="rounded-circle border border-2" style="border-color: var(--border-color) !important; width: 40px; height: 40px;">
                            <div class="text-start lh-1">
                                <div class="fw-bold" style="color: var(--text-dark); font-size: 0.9rem;">
                                    <?= htmlspecialchars($_SESSION['full_name']) ?>
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <?= $is_admin ? 'Administrator' : 'Anggota Tim' ?>
                                </small>
                            </div>
                            <i class="bi bi-chevron-down ms-auto ms-lg-1 text-muted" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2 w-100" style="border-radius: 16px; min-width: 200px;">
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

    <div class="container-fluid px-3 px-lg-5 py-4 pb-5">
        
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--text-dark);">
                    <i class="bi bi-folder-fill me-2" style="color: var(--primary);"></i>Semua Proyek
                </h3>
                <p class="text-muted mb-0 small" id="projectCountDisplay">
                    Menampilkan <?= count($projects) ?> proyek <?= $is_admin ? 'di sistem' : 'yang kamu ikuti' ?>.
                </p>
            </div>
            
            <div class="header-actions d-flex flex-column flex-md-row align-items-md-center gap-2">
                <div class="position-relative flex-grow-1" style="min-width: 280px;">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="searchProject" class="form-control search-input rounded-pill w-100" placeholder="Cari nama atau deskripsi proyek..." onkeyup="filterProjects()">
                </div>
                <a href="dashboard.php" class="btn btn-light rounded-pill px-4 d-flex align-items-center justify-content-center" style="height: 45px;">
                    <i class="bi bi-arrow-left me-md-2"></i><span class="d-none d-md-inline">Kembali</span>
                </a>
            </div>
        </div>

        <?php if (empty($projects)): ?>
            <div class="card p-5 text-center border-0 shadow-sm" style="border-radius: var(--radius-lg);">
                <i class="bi bi-folder-x mb-3" style="font-size: 4rem; color: var(--text-muted); opacity: 0.5;"></i>
                <h4 class="fw-bold" style="color: var(--text-dark);">Belum ada proyek</h4>
                <p class="text-muted mb-0">Daftar proyek masih kosong saat ini.</p>
            </div>
        <?php else: ?>
            <div class="row g-3 g-lg-4" id="projectGrid">
                <?php foreach ($projects as $project): ?>
                    <?php 
                        $p_stats = getProjectStats($project['id']); 
                        $safe_name = htmlspecialchars(strtolower($project['name']));
                        $safe_desc = htmlspecialchars(strtolower($project['description'] ?? ''));
                    ?>
                    <div class="col-12 col-md-6 col-xl-3 project-col" data-name="<?= $safe_name ?>" data-desc="<?= $safe_desc ?>">
                        <div class="project-card" onclick="window.location.href='project.php?id=<?= $project['id'] ?>'">
                            
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="project-title" title="<?= htmlspecialchars($project['name']) ?>"><?= htmlspecialchars($project['name']) ?></h5>
                                <?php if ($is_admin && isset($project['total_members'])): ?>
                                    <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.75rem; padding: 0.35rem 0.6rem;">
                                        <i class="bi bi-people-fill me-1"></i><?= $project['total_members'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="project-desc">
                                <?= htmlspecialchars($project['description'] ?: 'Tidak ada deskripsi pada proyek ini...') ?>
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted fw-bold">Progress</small>
                                    <small class="fw-bold" style="color: var(--primary);"><?= $p_stats['progress'] ?>%</small>
                                </div>
                                <div class="progress-wrapper">
                                    <div class="progress-fill" style="width: <?= $p_stats['progress'] ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="mini-stats mt-3">
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
                            
                            <?php if ($p_stats['overdue'] > 0): ?>
                                <div class="mt-3 text-center">
                                    <span class="badge bg-danger rounded-pill px-3 py-2 w-100" style="font-weight: 500;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <?= $p_stats['overdue'] ?> tugas terlambat
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="emptySearchState" style="display: none;" class="card p-5 text-center border-0 shadow-sm mt-4">
                <div class="icon-circle-sm mx-auto mb-3" style="background: var(--surface-hover); color: var(--text-muted); width: 80px; height: 80px; font-size: 2.5rem;">
                    <i class="bi bi-search"></i>
                </div>
                <h5 class="fw-bold" style="color: var(--text-dark);">Proyek tidak ditemukan</h5>
                <p class="text-muted mb-0">Tidak ada proyek yang cocok dengan kata kunci pencarianmu.</p>
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
            if (themeIcon) { themeIcon.className = `bi bi-${newTheme === 'dark' ? 'sun' : 'moon'}`; }
            document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
        }

        function filterProjects() {
            const query = document.getElementById('searchProject').value.toLowerCase();
            const projectCols = document.querySelectorAll('.project-col');
            const emptyState = document.getElementById('emptySearchState');
            const countDisplay = document.getElementById('projectCountDisplay');
            
            let visibleCount = 0;

            projectCols.forEach(col => {
                const name = col.getAttribute('data-name');
                const desc = col.getAttribute('data-desc');

                if (name.includes(query) || desc.includes(query)) {
                    col.style.display = 'block';
                    col.style.animation = 'fadeIn 0.3s ease forwards';
                    visibleCount++;
                } else {
                    col.style.display = 'none';
                }
            });

            if (countDisplay) {
                const totalText = <?= count($projects) ?>;
                if (query === '') {
                    countDisplay.textContent = `Menampilkan ${totalText} proyek.`;
                } else {
                    countDisplay.textContent = `Ditemukan ${visibleCount} proyek.`;
                }
            }

            if (visibleCount === 0 && projectCols.length > 0) {
                emptyState.style.display = 'block';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        }
    </script>
</body>
</html>