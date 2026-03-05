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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: #e2e8f0;
            
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --shadow-soft: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-hover: 0 4px 12px rgba(99, 102, 241, 0.15);
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-hover: #6366f1;
            --primary-light: #1e1b4b;
            --secondary: #f472b6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-light: #450a0a;
            
            --bg-body: #0f172a;
            --surface: #1e293b;
            --surface-hover: #334155;
            
            --text-dark: #f1f5f9;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            
            --border-color: #334155;
        }

        body {
            background: var(--bg-body);
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            transition: background 0.3s ease, color 0.3s ease;
            margin: 0;
            padding: 0;
        }

        /* Navbar */
        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand { 
            font-weight: 700; 
            font-size: 1.3rem; 
            color: var(--text-dark) !important; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .brand-icon {
            width: 38px; height: 38px;
            background: var(--danger);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
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
            border: 1px solid var(--border-color);
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Header Card */
        .header-card {
            background: var(--danger-light);
            border-left: 4px solid var(--danger);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .header-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Filter Bar */
        .filter-bar {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1rem;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 0.5rem 2rem 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--surface-hover);
            color: var(--text-dark);
            cursor: pointer;
            min-width: 180px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Task Cards */
        .task-card {
            background: var(--surface);
            border-radius: var(--radius-sm);
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .task-card:hover {
            border-left: 4px solid var(--danger);
            box-shadow: var(--shadow-hover);
            transform: translateX(5px);
        }
        
        .task-card.urgent {
            border-left: 4px solid var(--danger);
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .project-badge {
            background: var(--surface-hover);
            color: var(--text-muted);
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
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
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .task-description {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1rem;
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
            font-size: 0.8rem;
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
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast {
            background: var(--surface);
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow-hover);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
            }
            
            .filter-select {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="index.php">
                <div class="brand-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <span>Tugas Terlambat</span>
            </a>
            
            <div class="d-flex align-items-center ms-auto gap-3">
                <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                    <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                </div>
                
                <a href="index.php" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-lg-5 py-4">
        
        <!-- Header -->
        <div class="header-card">
            <h1 class="header-title">
                <i class="bi bi-exclamation-triangle-fill me-2" style="color: var(--danger);"></i>
                Tugas Terlambat
            </h1>
            <p class="header-subtitle">
                Daftar tugas yang melewati batas waktu dan perlu segera diselesaikan
            </p>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <select class="filter-select" id="projectFilter">
                <option value="">Semua Proyek</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select class="filter-select" id="priorityFilter">
                <option value="">Semua Prioritas</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
            
            <select class="filter-select" id="sortFilter">
                <option value="overdue_desc">Terlama Terlambat</option>
                <option value="overdue_asc">Terbaru Terlambat</option>
                <option value="priority">Prioritas Tertinggi</option>
            </select>
            
            <button class="btn btn-primary ms-auto" onclick="loadOverdueTasks()">
                <i class="bi bi-search me-2"></i>Terapkan Filter
            </button>
        </div>

        <!-- Tasks Container -->
        <div id="tasksContainer">
            <div class="loading">
                <div class="spinner"></div>
                <p>Memuat data...</p>
            </div>
        </div>
    </div>

    <!-- Modal Task Detail -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
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
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Memuat data...</p>
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
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan', 'danger');
                });
        }

        // Render Tasks
        function renderTasks(tasks) {
            const container = document.getElementById('tasksContainer');
            
            if (tasks.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-emoji-smile"></i>
                        <h3>Tidak Ada Tugas Terlambat</h3>
                        <p>Semua tugas diselesaikan tepat waktu. Kerja bagus!</p>
                        <a href="index.php" class="btn btn-primary px-4">
                            <i class="bi bi-house-door me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="row">';
            
            tasks.forEach(task => {
                const priorityClass = 'priority-' + task.priority;
                const priorityLabel = task.priority === 'urgent' ? 'Urgent!' : ucfirst(task.priority);
                
                html += `
                    <div class="col-12">
                        <div class="task-card ${task.priority === 'urgent' ? 'urgent' : ''}" onclick="showTaskDetail(${task.id})">
                            <div class="task-header">
                                <span class="project-badge">
                                    <i class="bi bi-folder"></i> ${escapeHtml(task.project_name)}
                                </span>
                                <span class="priority-badge ${priorityClass}">${priorityLabel}</span>
                            </div>
                            
                            <h4 class="task-title">${escapeHtml(task.title)}</h4>
                            
                            ${task.description ? `
                                <div class="task-description">${escapeHtml(task.description.substring(0, 150))}${task.description.length > 150 ? '...' : ''}</div>
                            ` : ''}
                            
                            <div class="due-date-badge">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Terlambat ${task.days_overdue} hari
                                <small class="ms-2">(Jatuh tempo: ${task.due_date_formatted})</small>
                            </div>
                            
                            <div class="task-footer">
                                <div class="assignee-info">
                                    ${task.assignee_name ? `
                                        <div class="avatar-circle" style="background: ${getAvatarColor(task.assignee_name)};">
                                            ${getInitials(task.assignee_name)}
                                        </div>
                                        <span>${escapeHtml(task.assignee_name)}</span>
                                    ` : `
                                        <div class="avatar-circle" style="background: var(--text-muted);">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <span class="text-muted">Belum ditugaskan</span>
                                    `}
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> ${task.days_overdue} hari
                                </small>
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
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Memuat data...</p>
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
                                    Tutup
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
                                Tutup
                            </button>
                        </div>
                    `;
                });
        }

        // Helper Functions
        function ucfirst(string) {
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
            toast.className = 'toast';
            
            let icon = 'bi-check-circle-fill';
            if (type === 'danger') icon = 'bi-exclamation-triangle-fill';
            if (type === 'warning') icon = 'bi-exclamation-circle-fill';
            
            toast.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi ${icon} fs-5" style="color: var(--${type});"></i>
                    <span>${message}</span>
                </div>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
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
    </script>
</body>
</html>