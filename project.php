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
    redirect('dashboard.php');
}

$project = getProjectById($project_id);
if (!$project) redirect('dashboard.php');

$members = getProjectMembers($project_id);
$user_role = getUserProjectRole($project_id, $user_id);
$is_admin = isProjectAdmin($project_id, $user_id);
$is_owner = isProjectOwner($project_id, $user_id);

$stats = getProjectStats($project_id);

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['name']) ?> - Task Manager</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #6366f1; --primary-hover: #4f46e5; --primary-light: #e0e7ff; --secondary: #ec4899;
            --bg-body: #f8fafc; --bg-gradient: linear-gradient(120deg, #f8fafc 0%, #eef2ff 100%);
            --surface: #ffffff; --surface-hover: #f1f5f9; --text-dark: #0f172a; --text-main: #334155; --text-muted: #64748b;
            --border-color: rgba(226, 232, 240, 0.8); --border-color-solid: #e2e8f0;
            --kanban-bg: rgba(241, 245, 249, 0.7); --task-count-bg: white;
            --task-shadow: 0 2px 8px rgba(0,0,0,0.04); --task-hover-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.15);
            --danger: #ef4444; --danger-light: #fee2e2; --success: #10b981; --warning: #f59e0b;
            --radius-lg: 24px; --radius-md: 16px; --radius-sm: 12px;
            --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.03); --shadow-hover: 0 15px 30px -10px rgba(99, 102, 241, 0.15);
            --scrollbar-thumb: #cbd5e1; --scrollbar-track: transparent;
        }

        [data-theme="dark"] {
            --primary: #818cf8; --primary-hover: #6366f1; --primary-light: #1e1b4b; --secondary: #f472b6;
            --bg-body: #0f172a; --bg-gradient: linear-gradient(120deg, #0f172a 0%, #1e1b4b 100%);
            --surface: #1e293b; --surface-hover: #334155; --text-dark: #f1f5f9; --text-main: #cbd5e1; --text-muted: #94a3b8;
            --border-color: rgba(51, 65, 85, 0.8); --border-color-solid: #334155;
            --kanban-bg: rgba(30, 41, 59, 0.7); --task-count-bg: #1e293b;
            --task-shadow: 0 2px 8px rgba(0,0,0,0.2); --task-hover-shadow: 0 15px 30px -10px rgba(129, 140, 248, 0.2);
            --danger: #ef4444; --danger-light: #450a0a; --success: #10b981; --warning: #f59e0b;
            --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.2); --shadow-hover: 0 15px 30px -10px rgba(129, 140, 248, 0.2);
            --scrollbar-thumb: #475569; --scrollbar-track: transparent;
        }

        body { background: var(--bg-gradient); font-family: 'Outfit', sans-serif; color: var(--text-main); -webkit-font-smoothing: antialiased; overflow-x: hidden; transition: background 0.3s ease, color 0.3s ease; }
        
        /* Navbar */
        .navbar { background: rgba(var(--surface-rgb, 255,255,255), 0.75); backdrop-filter: blur(16px); border-bottom: 1px solid var(--border-color); padding: 1rem 0; position: sticky; top: 0; z-index: 1000; transition: background 0.3s ease; }
        [data-theme="dark"] .navbar { background: rgba(30, 41, 59, 0.75); }
        .navbar-brand { font-weight: 700; font-size: 1.3rem; color: var(--text-dark) !important; display: flex; align-items: center; gap: 10px; letter-spacing: -0.5px; }
        .brand-icon { width: 38px; height: 38px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3); }

        .btn-primary { background: var(--primary); border: none; font-weight: 600; padding: 0.6rem 1.2rem; border-radius: 12px; transition: all 0.3s; color: white; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.3); color:white; }

        .theme-toggle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--surface); border: 1px solid var(--border-color-solid); color: var(--text-main); cursor: pointer; transition: all 0.3s ease; margin-right: 10px; }
        .theme-toggle:hover { transform: rotate(15deg); background: var(--primary-light); color: var(--primary); }

        .project-action-btn { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--surface); border: 1px solid var(--border-color-solid); color: var(--text-muted); cursor: pointer; transition: all 0.3s ease; }
        .project-action-btn:hover { background: var(--surface-hover); color: var(--primary); transform: rotate(90deg); }

        .members-btn { background: var(--surface); border: 1px solid var(--border-color-solid); color: var(--text-main); padding: 0.5rem 1rem; border-radius: 30px; font-weight: 500; transition: all 0.3s ease; }
        .members-btn:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary); }
        .member-item { transition: background 0.2s ease; border-radius: 8px; }
        .member-item:hover { background: var(--surface-hover) !important; }

        .stats-card { background: var(--surface); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; border: 1px solid var(--border-color); transition: transform 0.2s, box-shadow 0.2s, background 0.3s ease; box-shadow: var(--shadow-soft); }
        .stats-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); background: var(--surface-hover); }
        .stats-label { color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .stats-value { font-size: 1.75rem; font-weight: 700; margin-top: 0.2rem; color: var(--text-dark); line-height: 1;}
        .progress { height: 8px; border-radius: 8px; background: var(--border-color-solid); overflow: hidden; }
        .progress-bar { background: var(--primary); border-radius: 8px; }

        /* Filter Section */
        .filter-section {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-soft);
        }
        .input-group-text {
            background: var(--surface-hover);
            border-color: var(--border-color);
            color: var(--text-muted);
        }

        /* Kanban Board */
        .kanban-board-container { display: flex; gap: 1.5rem; overflow-x: auto; padding-bottom: 2rem; min-height: 70vh; scrollbar-width: thin; scrollbar-color: var(--scrollbar-thumb) var(--scrollbar-track); }
        .kanban-board-container::-webkit-scrollbar { height: 8px; }
        .kanban-board-container::-webkit-scrollbar-track { background: var(--scrollbar-track); }
        .kanban-board-container::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 20px; }
        .kanban-column-wrapper { min-width: 320px; max-width: 320px; display: flex; flex-direction: column; }
        .kanban-column { background: var(--kanban-bg); border-radius: var(--radius-lg); padding: 1rem; display: flex; flex-direction: column; border: 1px solid var(--border-color); height: 100%; backdrop-filter: blur(8px); transition: background 0.3s ease; }
        .column-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0.5rem; }
        .column-header-with-menu { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .column-menu-btn { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: transparent; border: none; color: var(--text-muted); transition: all 0.2s; }
        .column-menu-btn:hover { background: var(--surface-hover); color: var(--primary); }
        .column-title { font-weight: 700; font-size: 1.05rem; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }
        .task-count { background: var(--task-count-bg); color: var(--primary); font-size: 0.75rem; font-weight: 700; padding: 2px 10px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .custom-column-badge { font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; background: var(--surface-hover); color: var(--text-muted); margin-left: 5px; }

        .task-list { flex: 1; overflow-y: auto; padding: 4px; min-height: 150px; }
        
        .task-card { background: var(--surface); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1rem; box-shadow: var(--task-shadow); border: 1px solid var(--border-color); cursor: grab; transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 0.1); position: relative; }
        .task-card:hover { box-shadow: var(--task-hover-shadow); border-color: var(--primary); transform: translateY(-2px); background: var(--surface-hover); }
        .task-card:active { cursor: grabbing; transform: scale(0.98); }
        
        .task-title { font-weight: 600; font-size: 1rem; line-height: 1.3; margin-bottom: 0.5rem; color: var(--text-dark); }
        .task-desc { color: var(--text-muted); font-size: 0.85rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 1rem; }
        .task-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; }

        .badge-soft { padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; }
        [data-theme="light"] .badge-priority-low { background: #dcfce7; color: #166534; }
        [data-theme="light"] .badge-priority-medium { background: #fef9c3; color: #854d0e; }
        [data-theme="light"] .badge-priority-high { background: #ffedd5; color: #9a3412; }
        [data-theme="light"] .badge-priority-urgent { background: #fee2e2; color: #991b1b; }
        [data-theme="dark"] .badge-priority-low { background: #14532d; color: #86efac; }
        [data-theme="dark"] .badge-priority-medium { background: #713f12; color: #fde047; }
        [data-theme="dark"] .badge-priority-high { background: #7c2d12; color: #fdba74; }
        [data-theme="dark"] .badge-priority-urgent { background: #7f1d1d; color: #fca5a5; }

        .avatar-circle { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .avatar-group { display: flex; align-items: center; }
        .avatar-group .avatar-circle { border: 2px solid var(--surface); margin-left: -10px; transition: 0.2s; }
        .avatar-group .avatar-circle:hover { z-index: 10; transform: translateY(-2px); }

        .due-date { font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; font-weight: 500; }
        .due-date.overdue { color: var(--danger); font-weight: 600; background: var(--danger-light); padding: 2px 8px; border-radius: 6px; }

        .sortable-ghost { opacity: 0.4; background: var(--primary-light); border: 2px dashed var(--primary); border-radius: var(--radius-sm); }
        .sortable-chosen { background: var(--surface); box-shadow: var(--shadow-hover); }

        /* Modals & Form Controls */
        .modal-content { border-radius: var(--radius-lg); border: 1px solid var(--border-color); background: var(--surface); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem; background: var(--surface); border-radius: var(--radius-lg) var(--radius-lg) 0 0; }
        .modal-header .modal-title { color: var(--text-dark); }
        [data-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .modal-body { padding: 2rem; }
        .modal-footer { border-top: 1px solid var(--border-color); padding: 1.5rem 2rem; background: var(--surface-hover); border-radius: 0 0 var(--radius-lg) var(--radius-lg); }
        
        .form-control, .form-select { border-radius: 12px; padding: 0.8rem 1rem; border: 1px solid var(--border-color); background: var(--surface-hover); color: var(--text-dark); transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease; }
        .form-control:focus, .form-select:focus { background: var(--surface); border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); color: var(--text-dark); outline: none; }
        .form-control::placeholder { color: var(--text-muted); }
        .form-label { font-weight: 600; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        .dropdown-menu { background: var(--surface); border-color: var(--border-color); border-radius: 12px; box-shadow: var(--shadow-hover); padding: 0.5rem; }
        .dropdown-item { color: var(--text-main); border-radius: 8px; padding: 0.6rem 1rem; font-weight: 500; transition: all 0.2s; }
        .dropdown-item:hover { background: var(--surface-hover); color: var(--text-dark); }
        .dropdown-item.text-danger:hover { background: var(--danger-light); color: var(--danger) !important; }
        .dropdown-divider { border-color: var(--border-color); margin: 0.5rem 0; }
        .dropdown-header { color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; padding: 0.5rem 1rem; }

        .color-option { transition: transform 0.2s, border-color 0.2s; }
        .color-option:hover, .color-option.selected { transform: scale(1.1); border-color: var(--primary) !important; }
        .toast { background: var(--surface) !important; color: var(--text-dark) !important; border: 1px solid var(--border-color) !important; }
        .divider-vertical { height: 30px; width: 1px; background: var(--border-color); margin: 0 15px; }
        
        .search-input { padding-left: 45px; background: var(--surface-hover); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-dark); transition: all 0.3s ease; }
        .search-input:focus { background: var(--surface); border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); outline: none; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10; }

        @media (max-width: 768px) {
            .navbar-brand span:not(.brand-icon) { display: none; }
            .members-btn span { display: none; }
            .filter-section .col-md-3, .filter-section .col-md-4, .filter-section .col-md-2 { margin-bottom: 0.5rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left me-2" style="color: var(--text-muted);" 
                   onmouseover="this.style.color='var(--primary)'" 
                   onmouseout="this.style.color='var(--text-muted)'"></i>
                <div class="brand-icon"><i class="bi bi-kanban"></i></div>
                <span><?= htmlspecialchars($project['name']) ?></span>
            </a>
            
            <div class="d-flex align-items-center ms-auto gap-3">
                <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                    <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                </div>
                
                <?php if ($is_admin): ?>
                <div class="dropdown">
                    <button class="members-btn d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-people-fill"></i>
                        <span>Anggota</span>
                        <span class="badge bg-primary rounded-pill"><?= count($members) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 320px;">
                        <li><h6 class="dropdown-header">Daftar Anggota</h6></li>
                        <li>
                            <div class="px-2" style="max-height: 350px; overflow-y: auto;">
                                <?php foreach ($members as $member): ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3 member-item mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle" style="background: <?= getAvatarColor($member['full_name']) ?>; width: 36px; height: 36px;">
                                                <?= getInitials($member['full_name']) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold small" style="color: var(--text-dark);"><?= htmlspecialchars($member['full_name']) ?></div>
                                                <div class="d-flex align-items-center gap-1 mt-1">
                                                    <span class="badge bg-<?= 
                                                        $member['role'] == 'owner' ? 'danger' : 
                                                        ($member['role'] == 'admin' ? 'warning' : 'secondary') 
                                                    ?> rounded-pill" style="font-size: 0.6rem;">
                                                        <?= ucfirst($member['role']) ?>
                                                    </span>
                                                    <?php if ($member['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-primary rounded-pill" style="font-size: 0.6rem;">Anda</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($is_owner && $member['id'] != $_SESSION['user_id'] && $member['role'] != 'owner'): ?>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-light p-1 text-primary" onclick="event.stopPropagation(); updateMemberRole(<?= $member['id'] ?>, '<?= $member['role'] == 'admin' ? 'member' : 'admin' ?>')" title="Ubah Role ke <?= $member['role'] == 'admin' ? 'Member' : 'Admin' ?>">
                                                    <i class="bi bi-arrow-left-right"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light p-1 text-danger" onclick="event.stopPropagation(); removeMember(<?= $member['id'] ?>)" title="Hapus dari Proyek">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        <?php elseif ($is_admin && $member['id'] != $_SESSION['user_id'] && $member['role'] != 'owner'): ?>
                                            <button class="btn btn-sm btn-light text-danger p-1" onclick="event.stopPropagation(); removeMember(<?= $member['id'] ?>)" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </li>
                        <?php if ($is_admin): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li class="p-2">
                            <button class="btn btn-primary w-100" onclick="openAddMemberModal()">
                                <i class="bi bi-person-plus me-2"></i>Tambah Anggota
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($is_owner || $is_admin): ?>
                <div class="dropdown">
                    <button class="project-action-btn" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button class="dropdown-item" onclick="openEditProjectModal()">
                                <i class="bi bi-pencil-square me-2 text-primary"></i> Edit Proyek
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button class="dropdown-item text-danger" onclick="deleteProject()">
                                <i class="bi bi-trash3 me-2"></i> Hapus Proyek
                            </button>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="avatar-group d-none d-md-flex">
                    <?php 
                    $display_members = array_slice($members, 0, 4);
                    foreach($display_members as $m): 
                    ?>
                        <div class="avatar-circle" style="background: <?= getAvatarColor($m['full_name']) ?>;" 
                             title="<?= htmlspecialchars($m['full_name']) ?>">
                            <?= getInitials($m['full_name']) ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($members) > 4): ?>
                        <div class="avatar-circle bg-light" style="color: var(--text-muted);">
                            +<?= count($members) - 4 ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="divider-vertical d-none d-md-block"></div>

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
                    <div class="stats-label" style="color: var(--primary)">Progress</div>
                    <div class="stats-value"><?= $stats['progress'] ?>%</div>
                    <div class="progress mt-3">
                        <div class="progress-bar" style="width: <?= $stats['progress'] ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-label">To Do</div>
                    <div class="stats-value"><?= $stats['todo'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-label">In Progress</div>
                    <div class="stats-value"><?= $stats['in_progress'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-label">Selesai</div>
                    <div class="stats-value"><?= $stats['done'] ?></div>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text border-end-0 bg-transparent"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchTask" class="form-control border-start-0 ps-0 bg-transparent" placeholder="Cari tugas (judul/deskripsi)..." onkeyup="filterTasks()">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterPriority" class="form-select" onchange="filterTasks()">
                        <option value="">Semua Prioritas</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterAssignee" class="form-select" onchange="filterTasks()">
                        <option value="">Semua Anggota</option>
                        <option value="unassigned">Belum Ditugaskan</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-light w-100 border text-muted" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <h5 class="fw-bold mb-0" style="color: var(--text-dark);">Kanban Board</h5>
            <?php if ($is_admin): ?>
            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openAddColumnModal()">
                <i class="bi bi-plus-lg me-1"></i>Tambah Kolom
            </button>
            <?php endif; ?>
        </div>

        <div class="kanban-board-container" id="kanbanBoard">
            <div class="text-center py-5 w-100">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 text-muted">Memuat board...</p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tugas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newTaskForm">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <input type="hidden" name="column_status" id="task_status" value="todo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul Tugas</label>
                            <input type="text" class="form-control" name="title" required placeholder="Apa yang perlu diselesaikan?">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Tambahkan detail..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Prioritas</label>
                                <select class="form-select" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Assign ke</label>
                                <select class="form-select" name="assignee_id">
                                    <option value="">-- Pilih --</option>
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
                        <button type="button" class="btn btn-outline-secondary border-0" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Buat Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProjectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Proyek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProjectForm">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Proyek</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?= htmlspecialchars($project['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($project['description']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary border-0" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="columnModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="columnModalTitle">Tambah Kolom Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="columnForm">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <input type="hidden" name="column_id" id="columnId" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kolom</label>
                            <input type="text" class="form-control" name="title" id="columnTitle" 
                                   placeholder="Contoh: Testing, Backlog, dsb" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Warna</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php 
                                $colors = [
                                    '#64748b', '#6366f1', '#ec4899', '#ef4444', 
                                    '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'
                                ];
                                foreach ($colors as $code): 
                                ?>
                                <div class="color-option" onclick="selectColor('<?= $code ?>')" 
                                     style="width: 40px; height: 40px; background: <?= $code ?>; border-radius: 50%; cursor: pointer; border: 3px solid transparent;"
                                     data-color="<?= $code ?>"></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="color" id="selectedColor" value="#64748b">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Icon</label>
                            <select class="form-select" name="icon" id="columnIcon">
                                <option value="bi-circle">○ Circle</option>
                                <option value="bi-square">□ Square</option>
                                <option value="bi-triangle">△ Triangle</option>
                                <option value="bi-star">★ Star</option>
                                <option value="bi-flag">🏁 Flag</option>
                                <option value="bi-heart">❤️ Heart</option>
                                <option value="bi-bookmark">🔖 Bookmark</option>
                                <option value="bi-pin">📌 Pin</option>
                                <option value="bi-clock">⏰ Clock</option>
                                <option value="bi-calendar">📅 Calendar</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary border-0" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editColumnModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Kelola Kolom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kolom</label>
                        <input type="text" class="form-control" id="editColumnTitle" placeholder="Nama kolom">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Warna</label>
                        <div class="d-flex gap-2 flex-wrap" id="editColorOptions"></div>
                        <input type="hidden" id="editSelectedColor">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <select class="form-select" id="editColumnIcon">
                            <option value="bi-circle">○ Circle</option>
                            <option value="bi-square">□ Square</option>
                            <option value="bi-triangle">△ Triangle</option>
                            <option value="bi-star">★ Star</option>
                            <option value="bi-flag">🏁 Flag</option>
                            <option value="bi-heart">❤️ Heart</option>
                            <option value="bi-bookmark">🔖 Bookmark</option>
                            <option value="bi-pin">📌 Pin</option>
                            <option value="bi-clock">⏰ Clock</option>
                            <option value="bi-calendar">📅 Calendar</option>
                        </select>
                    </div>
                    
                    <div id="resetDefaultColumnContainer" style="display: none;" class="mt-3">
                        <hr>
                        <button type="button" class="btn btn-outline-warning w-100" onclick="resetDefaultColumn()">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Kembalikan ke Default
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" onclick="deleteCurrentColumn()" id="deleteColumnBtn">
                        <i class="bi bi-trash3 me-2"></i>Hapus Kolom
                    </button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-outline-secondary border-0" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary rounded-pill px-4" onclick="updateCurrentColumn()">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus me-2" style="color: var(--primary);"></i>
                        Tambah Anggota
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cari Pengguna</label>
                        <div class="position-relative">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control search-input" id="searchUserInput" 
                                   placeholder="Ketik nama atau username..." onkeyup="searchUsers()">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="memberRole">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div id="searchResults" class="mt-3" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                            <small>Cari pengguna untuk ditambahkan</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary border-0" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" id="taskDetailContent"></div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-4 mt-5" style="z-index: 9999;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

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

        // State
        let currentEditingColumnId = null;
        let currentEditingIsDefault = false;
        let searchTimeout;

        // Load columns on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadColumns();
            
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('task_due_date');
            if(dateInput) dateInput.setAttribute('min', today);
            
            const newTaskForm = document.getElementById('newTaskForm');
            if (newTaskForm) {
                newTaskForm.addEventListener('submit', handleNewTaskSubmit);
            }
            
            const editProjectForm = document.getElementById('editProjectForm');
            if (editProjectForm) {
                editProjectForm.addEventListener('submit', handleEditProjectSubmit);
            }
            
            const columnForm = document.getElementById('columnForm');
            if (columnForm) {
                columnForm.addEventListener('submit', handleColumnSubmit);
            }
        });

        // ========== FILTER TASK FUNCTIONS ==========
        
        function filterTasks() {
            const searchQuery = document.getElementById('searchTask').value.toLowerCase();
            const filterPriority = document.getElementById('filterPriority').value;
            const filterAssignee = document.getElementById('filterAssignee').value;

            const taskCards = document.querySelectorAll('.task-card');

            taskCards.forEach(card => {
                if(!card.hasAttribute('data-task-id')) return;

                const title = card.getAttribute('data-title');
                const desc = card.getAttribute('data-desc');
                const priority = card.getAttribute('data-priority');
                const assignee = card.getAttribute('data-assignee');

                // Cek kesesuaian filter
                let matchSearch = title.includes(searchQuery) || desc.includes(searchQuery);
                let matchPriority = filterPriority === '' || priority === filterPriority;
                let matchAssignee = filterAssignee === '' || 
                                    (filterAssignee === 'unassigned' && assignee === 'unassigned') || 
                                    assignee === filterAssignee;

                if (matchSearch && matchPriority && matchAssignee) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // Update badge hitungan di tiap kolom setelah disaring
            updateVisibleTaskCounts();
        }

        function resetFilters() {
            document.getElementById('searchTask').value = '';
            document.getElementById('filterPriority').value = '';
            document.getElementById('filterAssignee').value = '';
            filterTasks();
        }

        function updateVisibleTaskCounts() {
            const columns = document.querySelectorAll('.kanban-column-wrapper');
            columns.forEach(col => {
                const columnId = col.getAttribute('data-column-id');
                let visibleCount = 0;
                
                col.querySelectorAll('.task-card').forEach(c => {
                    if (c.style.display !== 'none' && c.hasAttribute('data-task-id')) {
                        visibleCount++;
                    }
                });
                
                const countBadge = document.getElementById('count-' + columnId);
                if (countBadge) {
                    countBadge.textContent = visibleCount;
                }
            });
        }

        // ========== KOLOM FUNCTIONS ==========

        function loadColumns() {
            fetch(`api/columns.php?action=list&project_id=<?= $project_id ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const existingColumns = document.querySelectorAll('.kanban-column-wrapper[data-column-id]');
                        if (existingColumns.length === 0) {
                            renderKanbanBoard(data.columns);
                        } else {
                            updateBoardData(data.columns);
                        }
                    } else {
                        showNotification('Gagal memuat kolom', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan', 'danger');
                });
        }

        function renderKanbanBoard(columns) {
            const container = document.getElementById('kanbanBoard');
            if (!container) return;
            
            let html = '';
            
            columns.forEach(column => {
                const isDefault = column.is_default === true || column.is_default === 'true' || column.is_default === 1 || column.is_default === '1';
                const isCustomized = column.is_customized === true || column.is_customized === 'true' || column.is_customized === 1 || column.is_customized === '1';
                const columnId = column.id;
                
                let badgeHtml = '';
                if (!isDefault) {
                    badgeHtml = '<span class="custom-column-badge">Kustom</span>';
                } else if (isCustomized) {
                    badgeHtml = '<span class="custom-column-badge">Diedit</span>';
                }
                
                html += `
                    <div class="kanban-column-wrapper" data-column-id="${columnId}" data-column-position="${column.position || 0}">
                        <div class="kanban-column">
                            <div class="column-header">
                                <div class="column-header-with-menu">
                                    <div class="column-title">
                                        <i class="bi ${column.icon}" style="color: ${column.color};"></i>
                                        ${column.title}
                                        ${badgeHtml}
                                    </div>
                                    ${<?= $is_admin ? 'true' : 'false' ?> ? `
                                        <button class="column-menu-btn" onclick="event.stopPropagation(); openColumnMenu('${columnId}', '${column.title.replace(/'/g, "\\'")}', '${column.color}', '${column.icon}', ${isDefault}, ${isCustomized})">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                    ` : ''}
                                </div>
                                <span class="task-count" id="count-${columnId}">0</span>
                            </div>
                            
                            <div id="list-${columnId}" class="task-list" data-column="${columnId}"></div>
                            
                            ${columnId === 'todo' ? `
                                <button class="btn w-100 mt-2 fw-bold" style="background: transparent; border: 2px dashed var(--border-color); color: var(--text-muted);" onclick="openNewTaskModal('todo')">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Tugas
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            <?php if ($is_admin): ?>
            html += `
                <div class="kanban-column-wrapper" style="min-width: 280px;">
                    <div class="kanban-column d-flex align-items-center justify-content-center" style="background: transparent; border: 2px dashed var(--border-color);">
                        <button class="btn btn-outline-primary rounded-pill px-4 py-3" onclick="openAddColumnModal()">
                            <i class="bi bi-plus-lg me-2"></i>Tambah Kolom
                        </button>
                    </div>
                </div>
            `;
            <?php endif; ?>
            
            container.innerHTML = html;
            
            columns.forEach(column => {
                loadTasksForColumn(column.id);
            });
            
            setupDragAndDrop();
        }

        function updateBoardData(columns) {
            const existingColumns = document.querySelectorAll('.kanban-column-wrapper[data-column-id]');
            const existingColumnIds = Array.from(existingColumns).map(el => el.dataset.columnId);
            
            const newColumns = columns.filter(col => {
                return !existingColumnIds.includes(col.id);
            });
            
            if (newColumns.length > 0) {
                const addButtonColumn = document.querySelector('.kanban-column-wrapper:last-child .btn-outline-primary');
                let addBtnContainer = null;
                if (addButtonColumn) {
                    addBtnContainer = addButtonColumn.closest('.kanban-column-wrapper');
                    addBtnContainer.remove();
                }
                
                newColumns.forEach(column => {
                    const isDefault = column.is_default === true || column.is_default === 'true' || column.is_default === 1 || column.is_default === '1';
                    const isCustomized = column.is_customized === true || column.is_customized === 'true' || column.is_customized === 1 || column.is_customized === '1';
                    const columnId = column.id;
                    
                    let badgeHtml = '';
                    if (!isDefault) {
                        badgeHtml = '<span class="custom-column-badge">Kustom</span>';
                    } else if (isCustomized) {
                        badgeHtml = '<span class="custom-column-badge">Diedit</span>';
                    }
                    
                    const columnHtml = `
                        <div class="kanban-column-wrapper" data-column-id="${columnId}" data-column-position="${column.position || 0}">
                            <div class="kanban-column">
                                <div class="column-header">
                                    <div class="column-header-with-menu">
                                        <div class="column-title">
                                            <i class="bi ${column.icon}" style="color: ${column.color};"></i>
                                            ${column.title}
                                            ${badgeHtml}
                                        </div>
                                        ${<?= $is_admin ? 'true' : 'false' ?> ? `
                                            <button class="column-menu-btn" onclick="event.stopPropagation(); openColumnMenu('${columnId}', '${column.title.replace(/'/g, "\\'")}', '${column.color}', '${column.icon}', ${isDefault}, ${isCustomized})">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                    <span class="task-count" id="count-${columnId}">0</span>
                                </div>
                                
                                <div id="list-${columnId}" class="task-list" data-column="${columnId}"></div>
                            </div>
                        </div>
                    `;
                    
                    const container = document.getElementById('kanbanBoard');
                    container.insertAdjacentHTML('beforeend', columnHtml);
                    
                    loadTasksForColumn(columnId);
                });
                
                <?php if ($is_admin): ?>
                const addButtonHtml = `
                    <div class="kanban-column-wrapper" style="min-width: 280px;">
                        <div class="kanban-column d-flex align-items-center justify-content-center" style="background: transparent; border: 2px dashed var(--border-color);">
                            <button class="btn btn-outline-primary rounded-pill px-4 py-3" onclick="openAddColumnModal()">
                                <i class="bi bi-plus-lg me-2"></i>Tambah Kolom
                            </button>
                        </div>
                    </div>
                `;
                document.getElementById('kanbanBoard').insertAdjacentHTML('beforeend', addButtonHtml);
                <?php endif; ?>
                
                setupDragAndDrop();
            }
            
            columns.forEach(column => {
                const countElement = document.getElementById(`count-${column.id}`);
                if (countElement && column.task_count !== undefined) {
                    countElement.textContent = column.task_count;
                }
            });
            // Re-apply filters if they were set
            filterTasks();
        }

        function addNewColumnToBoard(column) {
            const container = document.getElementById('kanbanBoard');
            
            const addButtonColumn = document.querySelector('.kanban-column-wrapper:last-child .btn-outline-primary');
            if (addButtonColumn) {
                addButtonColumn.closest('.kanban-column-wrapper').remove();
            }
            
            const columnId = column.id; 
            const position = column.position || (document.querySelectorAll('.kanban-column-wrapper[data-column-id]').length);
            
            const columnHtml = `
                <div class="kanban-column-wrapper" data-column-id="${columnId}" data-column-position="${position}">
                    <div class="kanban-column">
                        <div class="column-header">
                            <div class="column-header-with-menu">
                                <div class="column-title">
                                    <i class="bi ${column.icon}" style="color: ${column.color};"></i>
                                    ${column.title}
                                    <span class="custom-column-badge">Kustom</span>
                                </div>
                                ${<?= $is_admin ? 'true' : 'false' ?> ? `
                                    <button class="column-menu-btn" onclick="event.stopPropagation(); openColumnMenu('${columnId}', '${column.title.replace(/'/g, "\\'")}', '${column.color}', '${column.icon}', false, false)">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                ` : ''}
                            </div>
                            <span class="task-count" id="count-${columnId}">0</span>
                        </div>
                        
                        <div id="list-${columnId}" class="task-list" data-column="${columnId}"></div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', columnHtml);
            
            const listElement = document.getElementById(`list-${columnId}`);
            if (listElement) {
                listElement.innerHTML = '<div class="text-center text-muted py-3 small">Belum ada tugas</div>';
            }
            document.getElementById(`count-${columnId}`).textContent = '0';
            
            <?php if ($is_admin): ?>
            const addButtonHtml = `
                <div class="kanban-column-wrapper" style="min-width: 280px;">
                    <div class="kanban-column d-flex align-items-center justify-content-center" style="background: transparent; border: 2px dashed var(--border-color);">
                        <button class="btn btn-outline-primary rounded-pill px-4 py-3" onclick="openAddColumnModal()">
                            <i class="bi bi-plus-lg me-2"></i>Tambah Kolom
                        </button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', addButtonHtml);
            <?php endif; ?>
            
            setupDragAndDrop();
            
            setTimeout(() => {
                updateColumnPositions();
            }, 100);
        }

        function loadTasksForColumn(columnId) {
            const listElement = document.getElementById(`list-${columnId}`);
            if (!listElement) return;
            
            listElement.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            
            fetch(`api/tasks.php?action=list_by_column&project_id=<?= $project_id ?>&column=${columnId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderTasksInColumn(columnId, data.tasks);
                    } else {
                        listElement.innerHTML = '<div class="text-center text-muted py-3">Gagal memuat</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    listElement.innerHTML = '<div class="text-center text-muted py-3">Error</div>';
                });
        }

        function renderTasksInColumn(columnId, tasks) {
            const listElement = document.getElementById(`list-${columnId}`);
            const countElement = document.getElementById(`count-${columnId}`);
            
            if (!listElement) return;
            
            if (tasks.length === 0) {
                listElement.innerHTML = '<div class="text-center text-muted py-3 small">Belum ada tugas</div>';
                if (countElement) countElement.textContent = '0';
                return;
            }
            
            let html = '';
            tasks.forEach(task => {
                html += createTaskCardElement(task);
            });
            
            listElement.innerHTML = html;
            
            // Re-apply filters seamlessly
            filterTasks();
        }

        function createTaskCardElement(task) {
            const priorityClass = 'badge-priority-' + task.priority;
            let priorityLabel = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
            if (task.priority === 'urgent') priorityLabel = 'Urgent!';
            
            let dueDateHtml = '<div></div>';
            if (task.due_date) {
                const dueDate = new Date(task.due_date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const isOverdue = dueDate < today && task.column_status !== 'done';
                
                dueDateHtml = `
                    <div class="due-date ${isOverdue ? 'overdue' : ''}">
                        <i class="bi bi-calendar-event${isOverdue ? '-fill' : ''}"></i>
                        ${dueDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}
                    </div>
                `;
            }
            
            const assigneeHtml = task.assignee_name ?
                `<div class="avatar-circle" style="background: ${getAvatarColor(task.assignee_name)}; width:26px; height:26px;" title="${escapeHtml(task.assignee_name)}">
                    ${getInitials(task.assignee_name)}
                </div>` :
                `<div class="avatar-circle bg-light" style="width:26px; height:26px; color: var(--text-muted);" title="Belum ditugaskan">
                    <i class="bi bi-person"></i>
                </div>`;
            
            // Attributes for frontend filtering
            const safeTitle = escapeHtml(task.title).toLowerCase().replace(/"/g, '&quot;');
            const safeDesc = task.description ? escapeHtml(task.description).toLowerCase().replace(/"/g, '&quot;') : '';
            const safeAssigneeId = task.assignee_id ? task.assignee_id : 'unassigned';

            return `
                <div class="task-card" 
                     data-task-id="${task.id}" 
                     data-priority="${task.priority}" 
                     data-assignee="${safeAssigneeId}" 
                     data-title="${safeTitle}" 
                     data-desc="${safeDesc}" 
                     onclick="showTaskDetail(${task.id})">
                     
                    <div class="mb-3">
                        <span class="badge-soft ${priorityClass}">${priorityLabel}</span>
                    </div>
                    <div class="task-title">${escapeHtml(task.title)}</div>
                    ${task.description ? `<div class="task-desc">${escapeHtml(task.description.substring(0, 100))}${task.description.length > 100 ? '...' : ''}</div>` : ''}
                    <div class="task-meta">
                        ${dueDateHtml}
                        <div class="d-flex align-items-center">
                            ${assigneeHtml}
                        </div>
                    </div>
                </div>
            `;
        }

        function setupDragAndDrop() {
            if (window.columnSortable) {
                window.columnSortable.destroy();
            }
            
            <?php if ($is_admin): ?>
            const boardContainer = document.getElementById('kanbanBoard');
            if (boardContainer) {
                window.columnSortable = new Sortable(boardContainer, {
                    animation: 150,
                    handle: '.kanban-column-wrapper',
                    draggable: '.kanban-column-wrapper',
                    ghostClass: 'sortable-ghost',
                    onEnd: function() {
                        updateColumnPositions();
                    }
                });
            }
            <?php endif; ?>
            
            document.querySelectorAll('.task-list').forEach(list => {
                if (list.sortable) {
                    list.sortable.destroy();
                }
                
                new Sortable(list, {
                    group: 'tasks',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        const taskId = evt.item.dataset.taskId;
                        const targetColumn = evt.to.dataset.column;
                        moveTask(taskId, targetColumn);
                    }
                });
            });
        }

        function updateColumnPositions() {
            const columns = document.querySelectorAll('.kanban-column-wrapper[data-column-id]');
            const positions = [];
            
            columns.forEach((col, index) => {
                const columnId = col.dataset.columnId;
                if (columnId) {
                    positions.push({
                        id: columnId,
                        position: index
                    });
                }
            });
            
            fetch('api/columns.php?action=update_positions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `project_id=<?= $project_id ?>&positions=${encodeURIComponent(JSON.stringify(positions))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Posisi diam-diam tersimpan
                }
            });
        }

        function moveTask(taskId, targetColumn) {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('target_column', targetColumn);
            formData.append('project_id', <?= $project_id ?>);
            
            fetch('api/tasks.php?action=move_to_column', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showNotification('Gagal memindahkan tugas', 'danger');
                    loadColumns(); 
                } else {
                    // Update hitungan karena task pindah
                    updateVisibleTaskCounts();
                }
            });
        }

        // ========== CRUD KOLOM ==========

        function openAddColumnModal() {
            document.getElementById('columnModalTitle').textContent = 'Tambah Kolom Baru';
            document.getElementById('columnId').value = '';
            document.getElementById('columnTitle').value = '';
            document.getElementById('selectedColor').value = '#64748b';
            document.getElementById('columnIcon').value = 'bi-circle';
            
            document.querySelectorAll('#columnModal .color-option').forEach(opt => {
                opt.style.borderColor = 'transparent';
                if (opt.dataset.color === '#64748b') {
                    opt.style.borderColor = 'var(--primary)';
                }
            });
            
            const modal = new bootstrap.Modal(document.getElementById('columnModal'));
            modal.show();
        }

        function openColumnMenu(columnId, title, color, icon, isDefault, isCustomized) {
            isDefault = (isDefault === true || isDefault === 'true' || isDefault === 1 || isDefault === '1');
            isCustomized = (isCustomized === true || isCustomized === 'true' || isCustomized === 1 || isCustomized === '1');
            
            currentEditingColumnId = columnId;
            currentEditingIsDefault = isDefault;
            
            document.getElementById('editColumnTitle').value = title;
            document.getElementById('editSelectedColor').value = color;
            document.getElementById('editColumnIcon').value = icon;
            
            const resetBtnContainer = document.getElementById('resetDefaultColumnContainer');
            const deleteBtn = document.getElementById('deleteColumnBtn');
            
            if (isDefault) {
                document.querySelector('#editColumnModal .modal-title').textContent = 'Edit Kolom Default';
                resetBtnContainer.style.display = isCustomized ? 'block' : 'none';
                deleteBtn.style.display = 'none';
            } else {
                document.querySelector('#editColumnModal .modal-title').textContent = 'Edit Kolom Kustom';
                resetBtnContainer.style.display = 'none';
                deleteBtn.style.display = 'block';
            }
            
            const colorOptions = document.getElementById('editColorOptions');
            const colors = ['#64748b', '#6366f1', '#ec4899', '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'];
            
            let colorHtml = '';
            colors.forEach(c => {
                colorHtml += `<div class="color-option ${c === color ? 'selected' : ''}" 
                                   onclick="selectEditColor('${c}')" 
                                   style="width: 40px; height: 40px; background: ${c}; border-radius: 50%; cursor: pointer; border: 3px solid ${c === color ? 'var(--primary)' : 'transparent'};"
                                   data-color="${c}"></div>`;
            });
            colorOptions.innerHTML = colorHtml;
            
            const modal = new bootstrap.Modal(document.getElementById('editColumnModal'));
            modal.show();
        }

        function selectColor(color) {
            document.getElementById('selectedColor').value = color;
            document.querySelectorAll('#columnModal .color-option').forEach(opt => {
                opt.style.borderColor = 'transparent';
                if (opt.dataset.color === color) {
                    opt.style.borderColor = 'var(--primary)';
                }
            });
        }

        function selectEditColor(color) {
            document.getElementById('editSelectedColor').value = color;
            document.querySelectorAll('#editColorOptions .color-option').forEach(opt => {
                opt.style.borderColor = 'transparent';
                if (opt.dataset.color === color) {
                    opt.style.borderColor = 'var(--primary)';
                }
            });
        }

        function handleColumnSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const columnId = document.getElementById('columnId').value;
            const isEdit = columnId !== '';
            const action = isEdit ? 'update' : 'create';
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
            
            fetch(`api/columns.php?action=${action}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('columnModal'));
                    modal.hide();
                    showNotification(data.message, 'success');
                    
                    if (action === 'create' && data.column) {
                        addNewColumnToBoard(data.column);
                    } else if (action === 'update') {
                        updateColumnInBoard(columnId, {
                            title: formData.get('title'),
                            color: formData.get('color'),
                            icon: formData.get('icon')
                        });
                    }
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan', 'danger');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        function updateColumnInBoard(columnId, newData) {
            const columnWrapper = document.querySelector(`.kanban-column-wrapper[data-column-id="${columnId}"]`);
            if (!columnWrapper) return;
            
            const titleElement = columnWrapper.querySelector('.column-title');
            if (titleElement) {
                const iconElement = titleElement.querySelector('i');
                if (iconElement) {
                    iconElement.className = `bi ${newData.icon}`;
                    iconElement.style.color = newData.color;
                }
                
                const textNode = titleElement.childNodes[titleElement.childNodes.length - 1];
                if (textNode && textNode.nodeType === Node.TEXT_NODE) {
                    textNode.textContent = ' ' + newData.title;
                } else {
                    const badge = titleElement.querySelector('.custom-column-badge, .custom-column-badge');
                    if (badge) {
                        titleElement.innerHTML = `<i class="bi ${newData.icon}" style="color: ${newData.color};"></i> ${newData.title} `;
                        titleElement.appendChild(badge);
                    } else {
                        titleElement.innerHTML = `<i class="bi ${newData.icon}" style="color: ${newData.color};"></i> ${newData.title}`;
                    }
                }
            }
            
            const menuBtn = columnWrapper.querySelector('.column-menu-btn');
            if (menuBtn) {
                const onclickAttr = menuBtn.getAttribute('onclick');
                if (onclickAttr) {
                    const match = onclickAttr.match(/openColumnMenu\('([^']+)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*([^,]+),\s*([^)]+)\)/);
                    if (match) {
                        const isDefault = match[5] === 'true';
                        const isCustomized = match[6] === 'true';
                        const newOnClick = `openColumnMenu('${columnId}', '${newData.title.replace(/'/g, "\\'")}', '${newData.color}', '${newData.icon}', ${isDefault}, ${isCustomized})`;
                        menuBtn.setAttribute('onclick', `event.stopPropagation(); ${newOnClick}`);
                    }
                }
            }
        }

        function updateCurrentColumn() {
            const title = document.getElementById('editColumnTitle').value.trim();
            const color = document.getElementById('editSelectedColor').value;
            const icon = document.getElementById('editColumnIcon').value;
            
            if (!title) {
                showNotification('Nama kolom tidak boleh kosong', 'warning');
                return;
            }
            
            const saveBtn = document.querySelector('#editColumnModal .btn-primary');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            saveBtn.disabled = true;
            
            if (currentEditingIsDefault) {
                const formData = new FormData();
                formData.append('project_id', <?= $project_id ?>);
                formData.append('column_name', currentEditingColumnId);
                formData.append('title', title);
                formData.append('color', color);
                formData.append('icon', icon);
                
                fetch('api/columns.php?action=update_default', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editColumnModal'));
                        modal.hide();
                        updateColumnInBoard(currentEditingColumnId, { title, color, icon });
                        showNotification('Kolom berhasil diperbarui', 'success');
                    } else {
                        showNotification(data.message, 'danger');
                    }
                })
                .catch(error => {
                    showNotification('Terjadi kesalahan', 'danger');
                })
                .finally(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
            } else {
                const formData = new FormData();
                formData.append('column_id', currentEditingColumnId.replace('custom_', ''));
                formData.append('title', title);
                formData.append('color', color);
                formData.append('icon', icon);
                
                fetch('api/columns.php?action=update', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editColumnModal'));
                        modal.hide();
                        updateColumnInBoard(currentEditingColumnId, { title, color, icon });
                        showNotification('Kolom berhasil diperbarui', 'success');
                    } else {
                        showNotification(data.message, 'danger');
                    }
                })
                .catch(error => {
                    showNotification('Terjadi kesalahan', 'danger');
                })
                .finally(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
            }
        }

        function deleteCurrentColumn() {
            if (currentEditingIsDefault) {
                showNotification('Kolom default tidak dapat dihapus', 'warning');
                return;
            }
            
            if (!confirm('Yakin ingin menghapus kolom ini? Semua tugas akan dipindahkan ke To Do.')) return;
            
            const deleteBtn = document.getElementById('deleteColumnBtn');
            const originalText = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';
            deleteBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('column_id', currentEditingColumnId.replace('custom_', ''));
            
            fetch('api/columns.php?action=delete', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editColumnModal'));
                    modal.hide();
                    removeColumnFromBoard(currentEditingColumnId);
                    showNotification('Kolom berhasil dihapus', 'success');
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan', 'danger');
            })
            .finally(() => {
                deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
            });
        }

        function removeColumnFromBoard(columnId) {
            const columnWrapper = document.querySelector(`.kanban-column-wrapper[data-column-id="${columnId}"]`);
            if (columnWrapper) {
                columnWrapper.remove();
                setTimeout(() => {
                    updateColumnPositions();
                }, 100);
            }
        }

        function resetDefaultColumn() {
            if (!confirm('Kembalikan kolom ke pengaturan default?')) return;
            
            const resetBtn = document.querySelector('#resetDefaultColumnContainer .btn');
            const originalText = resetBtn.innerHTML;
            resetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mereset...';
            resetBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('column_name', currentEditingColumnId);
            
            fetch('api/columns.php?action=reset_default', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editColumnModal'));
                    modal.hide();
                    loadColumns();
                    showNotification('Kolom dikembalikan ke default', 'success');
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan', 'danger');
            })
            .finally(() => {
                resetBtn.innerHTML = originalText;
                resetBtn.disabled = false;
            });
        }

        // ========== PROYEK FUNCTIONS ==========

        function openEditProjectModal() {
            const modal = new bootstrap.Modal(document.getElementById('editProjectModal'));
            modal.show();
        }

        function handleEditProjectSubmit(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            
            const formData = new FormData(form);
            formData.append('action', 'update');
            
            fetch('api/projects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editProjectModal'));
                    modal.hide();
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Gagal memperbarui proyek', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan: ' + error.message, 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function deleteProject() {
            if (!confirm('Yakin ingin menghapus proyek ini? Semua tugas akan ikut terhapus!')) return;
            
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('action', 'delete');
            
            fetch('api/projects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => window.location.href = 'dashboard.php', 1500);
                } else {
                    showNotification(data.message || 'Gagal menghapus proyek', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan: ' + error.message, 'danger');
            });
        }

        // ========== PROJECT MEMBERS CRUD FUNCTIONS ==========

        function openAddMemberModal() {
            document.getElementById('searchResults').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                    <small>Cari pengguna untuk ditambahkan</small>
                </div>
            `;
            document.getElementById('searchUserInput').value = '';
            
            // Tutup dropdown menu anggota terlebih dahulu sebelum buka modal (Opsional, tapi mencegah bug visual di Bootstrap)
            const membersDropdown = document.querySelector('.members-btn');
            if (membersDropdown) {
                const bsDropdown = bootstrap.Dropdown.getInstance(membersDropdown);
                if (bsDropdown) bsDropdown.hide();
            }
            
            const modal = new bootstrap.Modal(document.getElementById('addMemberModal'));
            modal.show();
        }

        function searchUsers() {
            clearTimeout(searchTimeout);
            const query = document.getElementById('searchUserInput').value.trim();
            
            if (query.length < 2) {
                document.getElementById('searchResults').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                        <small>Minimal 2 karakter</small>
                    </div>
                `;
                return;
            }
            
            searchTimeout = setTimeout(() => {
                const resultsDiv = document.getElementById('searchResults');
                resultsDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Mencari...</div>';
                
                fetch(`api/project_members.php?action=search_users&project_id=<?= $project_id ?>&q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.users.length > 0) {
                            let html = '';
                            data.users.forEach(user => {
                                html += `
                                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-2" 
                                         style="background: var(--surface-hover); border: 1px solid var(--border-color);">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle" style="background: ${user.avatar_color}; width: 40px; height: 40px;">
                                                ${user.initials}
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="color: var(--text-dark);">${escapeHtml(user.full_name)}</div>
                                                <small class="text-muted">@${escapeHtml(user.username)}</small>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="addMemberToProject(${user.id})">
                                            <i class="bi bi-person-plus me-1"></i>Tambah
                                        </button>
                                    </div>
                                `;
                            });
                            resultsDiv.innerHTML = html;
                        } else {
                            resultsDiv.innerHTML = `
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-person-x fs-1 d-block mb-2 opacity-50"></i>
                                    <small>Tidak ada pengguna ditemukan</small>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        resultsDiv.innerHTML = '<div class="text-center text-danger py-3">Terjadi kesalahan</div>';
                    });
            }, 500);
        }

        function addMemberToProject(userId) {
            const role = document.getElementById('memberRole').value;
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('user_id', userId);
            formData.append('role', role);
            
            fetch('api/project_members.php?action=add', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addMemberModal'));
                    if(modal) modal.hide();
                    showNotification('Anggota berhasil ditambahkan', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Gagal menambahkan anggota', 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan', 'danger');
            });
        }

        function removeMember(userId) {
            if (!confirm('Yakin ingin menghapus anggota ini?')) return;
            
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
                    showNotification('Anggota berhasil dihapus', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Gagal menghapus anggota', 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan', 'danger');
            });
        }

        function updateMemberRole(userId, newRole) {
            const roleText = newRole === 'admin' ? 'Admin' : 'Member';
            if (!confirm(`Ubah role menjadi ${roleText}?`)) return;
            
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('user_id', userId);
            formData.append('role', newRole);
            
            fetch('api/project_members.php?action=update_role', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Role berhasil diperbarui', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Gagal memperbarui role', 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan', 'danger');
            });
        }

        // ========== TUGAS FUNCTIONS ==========

        function openNewTaskModal(status) {
            document.getElementById('task_status').value = status;
            const modal = new bootstrap.Modal(document.getElementById('newTaskModal'));
            modal.show();
        }

        function handleNewTaskSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
            
            fetch('api/tasks.php?action=create', { 
                method: 'POST', 
                body: new FormData(form) 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Tugas berhasil dibuat', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newTaskModal'));
                    modal.hide();
                    form.reset();
                    document.getElementById('task_status').value = 'todo';
                    
                    // Reload seluruh kolom agar task baru muncul lalu di-filter kembali
                    loadColumns();
                } else {
                    showNotification(data.message || 'Terjadi kesalahan', 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan koneksi', 'danger');
            })
            .finally(() => { 
                submitBtn.innerHTML = originalText; 
                submitBtn.disabled = false; 
            });
        }

        function showTaskDetail(taskId) {
            const modalContent = document.getElementById('taskDetailContent');
            const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            
            modalContent.innerHTML = `<div class="modal-body text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 text-muted">Memuat data...</p>
            </div>`;
            modal.show();
            
            fetch('api/tasks.php?action=get&id=' + taskId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalContent.innerHTML = data.html;
                        const triggerTabList = [].slice.call(modalContent.querySelectorAll('#taskTab button'));
                        triggerTabList.forEach(el => new bootstrap.Tab(el));
                    } else {
                        modalContent.innerHTML = `<div class="modal-body text-center py-5">
                            <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                            <p class="mt-2">${data.message || 'Gagal memuat data'}</p>
                        </div>`;
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="modal-body text-center py-5">
                        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                        <p class="mt-2">Terjadi kesalahan koneksi</p>
                    </div>`;
                });
        }

        // ========== HELPER FUNCTIONS ==========

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        }

        function getAvatarColor(name) {
            const colors = ['#6366f1', '#ec4899', '#14b8a6', '#f59e0b', '#8b5cf6', '#10b981'];
            return colors[(name?.length || 0) % colors.length];
        }

        function showNotification(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            let bgClass, iconClass;
            
            switch(type) {
                case 'success': bgClass = 'bg-success'; iconClass = 'bi-check-circle-fill'; break;
                case 'danger': bgClass = 'bg-danger'; iconClass = 'bi-exclamation-triangle-fill'; break;
                default: bgClass = 'bg-primary'; iconClass = 'bi-info-circle-fill';
            }
            
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
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Task detail functions (akan dipanggil dari dalam form detail Modal)
        window.updateTask = function(taskId) {
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
                    showNotification(data.message || 'Terjadi kesalahan', 'danger');
                }
            });
        };

        window.deleteTask = function(taskId) {
            if (!confirm('Yakin ingin menghapus tugas ini?')) return;
            
            const formData = new FormData();
            formData.append('task_id', taskId);
            
            fetch('api/tasks.php?action=delete', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if(d.success) { 
                    showNotification('Tugas berhasil dihapus', 'success');
                    setTimeout(() => location.reload(), 500); 
                } else { 
                    showNotification(d.message || 'Gagal menghapus tugas', 'danger'); 
                }
            });
        };

        window.addComment = function(taskId, form) {
            const contentInput = form.querySelector('input[name="content"]');
            const content = contentInput.value.trim();
            if (!content) return;
            
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('content', content);
            
            fetch('api/comments.php?action=add', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contentInput.value = ''; 
                    showTaskDetail(taskId);
                } else { 
                    showNotification(data.message || 'Gagal menambahkan komentar', 'danger'); 
                }
            })
            .catch(error => showNotification('Gagal terhubung ke server', 'danger'));
        };

        window.uploadAttachment = function(taskId, inputElement) {
            if (!inputElement.files || inputElement.files.length === 0) return;
            
            const file = inputElement.files[0];
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('file', file);
            
            showNotification('Mengunggah file...', 'info'); 
            
            fetch('api/tasks.php?action=upload_attachment', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    showTaskDetail(taskId); 
                } else {
                    showNotification(data.message || 'Gagal mengunggah', 'danger');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan saat mengunggah', 'danger');
            })
            .finally(() => {
                inputElement.value = ''; 
            });
        };

        window.deleteAttachment = function(attachmentId, taskId) {
            if (!confirm('Hapus lampiran ini?')) return;
            
            const formData = new FormData();
            formData.append('attachment_id', attachmentId);
            
            fetch('api/tasks.php?action=delete_attachment', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    showTaskDetail(taskId); 
                } else {
                    showNotification(data.message || 'Gagal menghapus lampiran', 'danger');
                }
            });
        };

        // Initialize script
        document.addEventListener('DOMContentLoaded', loadColumns);
    </script>
</body>
</html>