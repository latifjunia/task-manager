<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil daftar proyek untuk filter
$projects = isAdmin() ? getAllProjects() : getUserProjects($user_id);

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Terlambat - Task Manager</title>
    
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
            --secondary: #ec4899;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --danger-dark: #dc2626;
            --info: #3b82f6;
            
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
            --shadow-hover: 0 20px 25px -5px rgba(239, 68, 68, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            
            --transition: all 0.2s ease;
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #1e1b4b;
            --secondary: #f472b6;
            --success: #10b981;
            --success-light: #064e3b;
            --warning: #f59e0b;
            --warning-light: #422006;
            --danger: #ef4444;
            --danger-light: #450a0a;
            --danger-dark: #dc2626;
            --info: #3b82f6;
            
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
        ::-webkit-scrollbar-thumb:hover { background: var(--danger); }

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
            width: 36px; 
            height: 36px;
            background: linear-gradient(135deg, var(--danger), var(--danger-dark));
            border-radius: 10px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white; 
            font-size: 1.1rem;
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
            background: var(--danger-light);
            color: var(--danger);
        }

        /* Header Card */
        .header-card {
            background: var(--danger-light);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .header-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-title i {
            color: var(--danger);
            font-size: 1.75rem;
        }
        
        .header-subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        /* Filter Bar */
        .filter-bar {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-select {
            padding: 0.5rem 2rem 0.5rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--surface-hover);
            color: var(--text-dark);
            cursor: pointer;
            min-width: 180px;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .filter-select:hover {
            border-color: var(--danger);
        }

        /* Task Cards */
        .tasks-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .task-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .task-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--danger);
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }
        
        .task-card:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-hover);
            border-color: var(--danger-light);
        }
        
        .task-card:hover::before {
            transform: scaleY(1);
        }
        
        .task-card.urgent::before {
            transform: scaleY(1);
            background: var(--danger);
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .project-badge {
            background: var(--surface-hover);
            color: var(--text-muted);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border: 1px solid var(--border-color);
        }
        
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .priority-low { background: #dcfce7; color: #166534; }
        .priority-medium { background: #fef9c3; color: #854d0e; }
        .priority-high { background: #ffedd5; color: #9a3412; }
        .priority-urgent { background: #fee2e2; color: #991b1b; }
        
        [data-theme="dark"] .priority-low { background: #14532d; color: #86efac; }
        [data-theme="dark"] .priority-medium { background: #713f12; color: #fde047; }
        [data-theme="dark"] .priority-high { background: #7c2d12; color: #fdba74; }
        [data-theme="dark"] .priority-urgent { background: #7f1d1d; color: #fca5a5; }
        
        .task-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .task-description {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .due-date-badge {
            background: var(--danger-light);
            color: var(--danger);
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }
        
        .assignee-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .avatar-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .assignee-name {
            font-size: 0.8rem;
            color: var(--text-main);
        }
        
        .overdue-days {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--danger);
            background: var(--danger-light);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
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

        /* Buttons */
        .btn {
            padding: 0.5rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
            font-size: 0.85rem;
        }
        
        .btn-primary {
            background: var(--danger);
            border: none;
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--danger-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .btn-outline-primary:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-light {
            background: var(--surface-hover);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        
        .btn-light:hover {
            background: var(--surface-active);
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Loading */
        .loading-state {
            text-align: center;
            padding: 3rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--danger);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

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
        
        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        .modal-body {
            padding: 1.5rem;
            color: var(--text-main);
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            background: var(--surface-hover);
        }

        /* Toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast-custom {
            background: var(--surface);
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.75rem 1.25rem;
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .btn-primary {
                width: 100%;
            }
            
            .task-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-title {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            .task-card {
                padding: 1rem;
            }
            
            .due-date-badge {
                font-size: 0.7rem;
                padding: 0.3rem 0.8rem;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand" href="dashboard.php">
                <div class="brand-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <span>Task Manager</span>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                    <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                </div>
                
                <a href="dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-4 py-4">
        
        <!-- Header -->
        <div class="header-card">
            <div class="header-title">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Tugas Terlambat
            </div>
            <p class="header-subtitle">
                Daftar tugas yang melewati batas waktu dan perlu segera diselesaikan
            </p>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <select class="filter-select" id="projectFilter">
                <option value="">📁 Semua Proyek</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select class="filter-select" id="priorityFilter">
                <option value="">🎯 Semua Prioritas</option>
                <option value="urgent">🔴 Urgent</option>
                <option value="high">🟠 High</option>
                <option value="medium">🟡 Medium</option>
                <option value="low">🟢 Low</option>
            </select>
            
            <select class="filter-select" id="sortFilter">
                <option value="overdue_desc">⚠️ Terlama Terlambat</option>
                <option value="overdue_asc">📅 Terbaru Terlambat</option>
                <option value="priority">⚡ Prioritas Tertinggi</option>
            </select>
            
            <button class="btn btn-primary ms-auto" onclick="loadOverdueTasks()">
                <i class="bi bi-search me-2"></i>Terapkan Filter
            </button>
        </div>

        <!-- Tasks Container -->
        <div id="tasksContainer">
            <div class="loading-state">
                <div class="spinner"></div>
                <p class="text-muted">Memuat data...</p>
            </div>
        </div>
    </div>

    <!-- Modal Task Detail -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" id="taskDetailContent"></div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

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

        // Load tasks on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadOverdueTasks();
        });

        // Load Overdue Tasks
        function loadOverdueTasks() {
            const container = document.getElementById('tasksContainer');
            const projectId = document.getElementById('projectFilter').value;
            const priority = document.getElementById('priorityFilter').value;
            const sort = document.getElementById('sortFilter').value;
            
            container.innerHTML = `
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p class="text-muted">Memuat data...</p>
                </div>
            `;
            
            let url = 'api/overdue.php?action=list';
            if (projectId) url += `&project_id=${projectId}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let tasks = data.data;
                        
                        // Filter by priority
                        if (priority) {
                            tasks = tasks.filter(task => task.priority === priority);
                        }
                        
                        // Sort tasks
                        switch(sort) {
                            case 'overdue_desc':
                                tasks.sort((a, b) => b.days_overdue - a.days_overdue);
                                break;
                            case 'overdue_asc':
                                tasks.sort((a, b) => a.days_overdue - b.days_overdue);
                                break;
                            case 'priority':
                                const priorityOrder = { 'urgent': 1, 'high': 2, 'medium': 3, 'low': 4 };
                                tasks.sort((a, b) => priorityOrder[a.priority] - priorityOrder[b.priority]);
                                break;
                        }
                        
                        renderTasks(tasks);
                    } else {
                        showNotification('Gagal memuat data', 'danger');
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-bug"></i></div>
                                <div class="empty-title">Gagal Memuat Data</div>
                                <p class="empty-text">Terjadi kesalahan saat memuat tugas terlambat</p>
                                <button class="btn btn-primary" onclick="loadOverdueTasks()">
                                    <i class="bi bi-arrow-repeat me-2"></i>Coba Lagi
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan koneksi', 'danger');
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-wifi-off"></i></div>
                            <div class="empty-title">Koneksi Gagal</div>
                            <p class="empty-text">Periksa koneksi internet Anda</p>
                            <button class="btn btn-primary" onclick="loadOverdueTasks()">
                                <i class="bi bi-arrow-repeat me-2"></i>Coba Lagi
                            </button>
                        </div>
                    `;
                });
        }

        // Render Tasks
        function renderTasks(tasks) {
            const container = document.getElementById('tasksContainer');
            
            if (tasks.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-emoji-smile"></i></div>
                        <div class="empty-title">Semua Tugas Tepat Waktu!</div>
                        <p class="empty-text">Tidak ada tugas yang melewati batas waktu. Kerja bagus!</p>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-house-door me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="tasks-grid">';
            
            tasks.forEach(task => {
                const priorityClass = 'priority-' + task.priority;
                const priorityLabel = task.priority === 'urgent' ? 'Urgent!' : ucfirst(task.priority);
                const isUrgent = task.priority === 'urgent' || task.days_overdue > 7;
                
                html += `
                    <div class="task-card ${isUrgent ? 'urgent' : ''}" onclick="showTaskDetail(${task.id})">
                        <div class="task-header">
                            <span class="project-badge">
                                <i class="bi bi-folder"></i> ${escapeHtml(task.project_name)}
                            </span>
                            <span class="priority-badge ${priorityClass}">${priorityLabel}</span>
                        </div>
                        
                        <h4 class="task-title">${escapeHtml(task.title)}</h4>
                        
                        ${task.description ? `
                            <div class="task-description">${escapeHtml(task.description.substring(0, 120))}${task.description.length > 120 ? '...' : ''}</div>
                        ` : ''}
                        
                        <div class="due-date-badge">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Terlambat ${task.days_overdue} hari
                            <small class="ms-1">(Jatuh tempo: ${task.due_date_formatted})</small>
                        </div>
                        
                        <div class="task-footer">
                            <div class="assignee-info">
                                ${task.assignee_name ? `
                                    <div class="avatar-circle" style="background: ${getAvatarColor(task.assignee_name)};">
                                        ${getInitials(task.assignee_name)}
                                    </div>
                                    <span class="assignee-name">${escapeHtml(task.assignee_name)}</span>
                                ` : `
                                    <div class="avatar-circle" style="background: var(--text-muted);">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <span class="assignee-name text-muted">Belum ditugaskan</span>
                                `}
                            </div>
                            <div class="overdue-days">
                                <i class="bi bi-clock-history me-1"></i>${task.days_overdue} hari
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        // Show Task Detail
        function showTaskDetail(taskId) {
            const modalContent = document.getElementById('taskDetailContent');
            const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            
            modalContent.innerHTML = `
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2 text-muted">Memuat detail tugas...</p>
                </div>
            `;
            modal.show();
            
            fetch('api/tasks.php?action=get&id=' + taskId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalContent.innerHTML = data.html;
                        // Re-initialize tabs
                        const triggerTabList = [].slice.call(modalContent.querySelectorAll('#taskTab button'));
                        triggerTabList.forEach(el => new bootstrap.Tab(el));
                    } else {
                        modalContent.innerHTML = `
                            <div class="modal-body text-center py-5">
                                <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                                <p class="mt-2">${data.message || 'Gagal memuat data'}</p>
                                <button class="btn btn-primary mt-3" onclick="bootstrap.Modal.getInstance(document.getElementById('taskDetailModal')).hide()">
                                    <i class="bi bi-x-lg me-2"></i>Tutup
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = `
                        <div class="modal-body text-center py-5">
                            <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                            <p class="mt-2">Terjadi kesalahan koneksi</p>
                            <button class="btn btn-primary mt-3" onclick="bootstrap.Modal.getInstance(document.getElementById('taskDetailModal')).hide()">
                                <i class="bi bi-x-lg me-2"></i>Tutup
                            </button>
                        </div>
                    `;
                });
        }

        // Helper Functions
        function ucfirst(string) {
            if (!string) return '';
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

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
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast-custom';
            
            let icon = 'bi-check-circle-fill';
            let color = 'var(--success)';
            if (type === 'danger') {
                icon = 'bi-exclamation-triangle-fill';
                color = 'var(--danger)';
            } else if (type === 'warning') {
                icon = 'bi-exclamation-circle-fill';
                color = 'var(--warning)';
            } else if (type === 'info') {
                icon = 'bi-info-circle-fill';
                color = 'var(--info)';
            }
            
            toast.innerHTML = `
                <i class="bi ${icon}" style="color: ${color}; font-size: 1.2rem;"></i>
                <span style="flex: 1;">${message}</span>
                <i class="bi bi-x-lg" style="cursor: pointer; color: var(--text-muted);" onclick="this.parentElement.remove()"></i>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 3000);
        }

        // Task action functions (will be called from modal)
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
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('taskDetailModal')).hide();
                            loadOverdueTasks();
                        }, 500);
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
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('taskDetailModal')).hide();
                            loadOverdueTasks();
                        }, 500);
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
            if (file.size > 10 * 1024 * 1024) {
                showNotification('Ukuran file maksimal 10MB', 'danger');
                inputElement.value = '';
                return;
            }
            
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('file', file);
            
            const uploadBtn = inputElement.closest('label');
            const originalBtnHtml = uploadBtn.innerHTML;
            uploadBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Mengupload...';
            uploadBtn.disabled = true;
            
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
                uploadBtn.innerHTML = originalBtnHtml;
                uploadBtn.disabled = false;
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
        
        window.refreshAttachments = function(taskId) {
            showTaskDetail(taskId);
        };
    </script>
</body>
</html>