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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #e0e7ff;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            --surface-active: #f1f5f9;
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            
            --kanban-bg: #f8fafc;
            --task-count-bg: #f1f5f9;
            --task-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            --task-hover-shadow: 0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.02);
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            
            --transition: all 0.2s ease;
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-hover: #6366f1;
            --primary-light: #1e1b4b;
            --bg-body: #0f172a;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --surface: #1e293b;
            --surface-hover: #334155;
            --surface-active: #2d3a4e;
            --text-dark: #f1f5f9;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --border-light: #1e293b;
            
            --kanban-bg: #0f172a;
            --task-count-bg: #334155;
            --task-shadow: 0 1px 3px rgba(0,0,0,0.3);
            --task-hover-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: var(--bg-gradient); 
            font-family: 'Inter', sans-serif; 
            color: var(--text-main); 
            -webkit-font-smoothing: antialiased; 
            min-height: 100vh;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--border-light); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }
        
        .navbar { 
            background: rgba(var(--surface-rgb, 255,255,255), 0.8); 
            backdrop-filter: blur(20px); 
            border-bottom: 1px solid var(--border-color); 
            padding: 0.75rem 0; 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }
        [data-theme="dark"] .navbar { background: rgba(30, 41, 59, 0.8); }
        
        .navbar-brand { 
            font-weight: 700; 
            font-size: 1.2rem; 
            color: var(--text-dark) !important; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
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
        
        .btn-primary { 
            background: var(--primary); 
            border: none; 
            font-weight: 600; 
            padding: 0.5rem 1.2rem; 
            border-radius: 10px; 
            transition: var(--transition); 
        }
        .btn-primary:hover { 
            background: var(--primary-hover); 
            transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); 
        }
        
        .btn-outline-primary {
            border-color: var(--border-color);
            color: var(--text-main);
            background: transparent;
        }
        .btn-outline-primary:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--task-hover-shadow);
            border-color: var(--primary-light);
        }
        
        .stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar-custom {
            height: 6px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .filter-section {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .kanban-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        
        .kanban-header h5 {
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }
        
        .kanban-board {
            display: flex;
            gap: 1.25rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            min-height: 65vh;
        }
        
        .kanban-column {
            min-width: 320px;
            width: 320px;
            background: var(--kanban-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 280px);
        }
        
        .column-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .column-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        
        .column-title i {
            font-size: 1rem;
        }
        
        .task-count {
            background: var(--task-count-bg);
            color: var(--primary);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }
        
        .task-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem;
            min-height: 200px;
        }
        
        .task-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }
        
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--task-hover-shadow);
            border-color: var(--primary-light);
        }
        
        .task-priority {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.75rem;
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
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .task-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        
        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .due-date {
            font-size: 0.7rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .due-date.overdue {
            color: var(--danger);
            font-weight: 600;
        }
        
        .avatar-small {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: white;
        }
        
        .add-column-card {
            min-width: 280px;
            width: 280px;
            background: var(--surface);
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .add-column-card:hover {
            border-color: var(--primary);
            background: var(--surface-hover);
        }
        
        .modal-content {
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            background: var(--surface);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            background: var(--surface-hover);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.6rem 1rem;
            border: 1px solid var(--border-color);
            background: var(--surface);
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: var(--surface);
        }
        
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .avatar-group {
            display: flex;
            align-items: center;
        }
        
        .avatar-group .avatar-small {
            border: 2px solid var(--surface);
            margin-left: -8px;
        }
        
        .avatar-group .avatar-small:first-child {
            margin-left: 0;
        }
        
        .theme-toggle {
            width: 38px;
            height: 38px;
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
            background: var(--primary-light);
            color: var(--primary);
            transform: rotate(15deg);
        }
        
        .members-btn {
            background: var(--surface);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.85rem;
            transition: var(--transition);
            color: var(--text-dark);
        }
        
        .members-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .project-action-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            transition: var(--transition);
        }
        
        .project-action-btn:hover {
            background: var(--surface-hover);
            color: var(--primary);
        }
        
        .dropdown-menu {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            padding: 0.5rem;
        }
        
        .dropdown-item {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            color: var(--text-main);
        }
        
        .dropdown-item:hover {
            background: var(--surface-hover);
            color: var(--text-dark);
        }
        
        .dropdown-header {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dropdown-menu .fw-semibold {
            color: var(--text-dark);
        }
        
        .kanban-column .btn-light {
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        
        .kanban-column .btn-light:hover {
            background: var(--surface-hover);
            color: var(--primary);
        }
        
        .kanban-column .btn-light.w-100 {
            background: var(--surface);
            border: 1px dashed var(--border-color);
            color: var(--text-main);
        }
        
        .kanban-column .btn-light.w-100:hover {
            background: var(--surface-hover);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .sortable-ghost {
            opacity: 0.4;
            background: var(--primary-light);
            border: 2px dashed var(--primary);
        }
        
        .toast {
            background: var(--surface) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--radius-md) !important;
        }
        
        /* ===== DARK MODE INPUT TEXT FIX ===== */
        [data-theme="dark"] input,
        [data-theme="dark"] textarea,
        [data-theme="dark"] select,
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select,
        [data-theme="dark"] input.form-control,
        [data-theme="dark"] textarea.form-control,
        [data-theme="dark"] select.form-select,
        [data-theme="dark"] input[type="text"],
        [data-theme="dark"] input[type="email"],
        [data-theme="dark"] input[type="password"],
        [data-theme="dark"] input[type="date"],
        [data-theme="dark"] input[type="number"],
        [data-theme="dark"] input[type="search"],
        [data-theme="dark"] input[type="tel"],
        [data-theme="dark"] input[type="url"] {
            color: #ffffff !important;
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] input::placeholder,
        [data-theme="dark"] textarea::placeholder,
        [data-theme="dark"] .form-control::placeholder {
            color: #94a3b8 !important;
            opacity: 1 !important;
        }

        [data-theme="dark"] input:focus,
        [data-theme="dark"] textarea:focus,
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            color: #ffffff !important;
            background-color: #0f172a !important;
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2) !important;
        }

        [data-theme="dark"] .input-group-text {
            color: #94a3b8 !important;
            background-color: #334155 !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] input:disabled,
        [data-theme="dark"] textarea:disabled,
        [data-theme="dark"] .form-control:disabled {
            background-color: #0f172a !important;
            color: #64748b !important;
        }

        [data-theme="dark"] select option {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        [data-theme="dark"] input:-webkit-autofill,
        [data-theme="dark"] input:-webkit-autofill:hover,
        [data-theme="dark"] input:-webkit-autofill:focus,
        [data-theme="dark"] textarea:-webkit-autofill,
        [data-theme="dark"] textarea:-webkit-autofill:hover,
        [data-theme="dark"] textarea:-webkit-autofill:focus,
        [data-theme="dark"] select:-webkit-autofill,
        [data-theme="dark"] select:-webkit-autofill:hover,
        [data-theme="dark"] select:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff !important;
            -webkit-box-shadow: 0 0 0px 1000px #1e293b inset !important;
            background-color: #1e293b !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Dark mode fixes for task cards */
        [data-theme="dark"] .task-card {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] .task-card:hover {
            background-color: #334155 !important;
        }

        [data-theme="dark"] .task-title {
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .task-desc {
            color: #94a3b8 !important;
        }

        [data-theme="dark"] .modal-content {
            background-color: #1e293b !important;
        }

        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer {
            border-color: #334155 !important;
            background-color: #1e293b !important;
        }

        [data-theme="dark"] .modal-title {
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%) !important;
        }

        [data-theme="dark"] .dropdown-menu {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] .dropdown-item {
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] .dropdown-item:hover {
            background-color: #334155 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] .kanban-column {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] .column-header {
            border-color: #334155 !important;
        }

        [data-theme="dark"] .column-title {
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .task-count {
            background-color: #334155 !important;
            color: #818cf8 !important;
        }

        [data-theme="dark"] .add-column-card {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] .add-column-card:hover {
            background-color: #334155 !important;
        }

        /* Task Detail Modal Styling */
        #taskDetailModal .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }

        #taskDetailModal .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--surface);
        }

        #taskDetailModal .modal-header .modal-title {
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #taskDetailModal .modal-body {
            padding: 0;
        }

        /* Tab Navigation */
        #taskDetailModal .nav-tabs {
            border-bottom: 1px solid var(--border-color);
            padding: 0 1.5rem;
            background: var(--surface);
            gap: 0.5rem;
        }

        #taskDetailModal .nav-tabs .nav-link {
            border: none;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.75rem 1rem;
            color: var(--text-muted);
            background: transparent;
            border-radius: 0;
            position: relative;
            transition: all 0.2s ease;
        }

        #taskDetailModal .nav-tabs .nav-link i {
            margin-right: 8px;
            font-size: 0.9rem;
        }

        #taskDetailModal .nav-tabs .nav-link:hover {
            color: var(--primary);
            background: var(--surface-hover);
        }

        #taskDetailModal .nav-tabs .nav-link.active {
            color: var(--primary);
            background: transparent;
        }

        #taskDetailModal .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary);
            border-radius: 2px;
        }

        /* Tab Content */
        #taskDetailModal .tab-content {
            padding: 1.5rem;
            background: var(--surface);
        }

        /* Info Cards */
        #taskDetailModal .info-card {
            background: var(--surface-hover);
            border-radius: 16px;
            padding: 1rem;
            border: 1px solid var(--border-color);
        }

        #taskDetailModal .info-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        #taskDetailModal .info-value {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        /* Priority Badge */
        #taskDetailModal .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        /* Comments Section */
        #taskDetailModal .comment-item {
            background: var(--surface-hover);
            border-radius: 16px;
            padding: 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        #taskDetailModal .comment-item:hover {
            border-color: var(--primary-light);
        }

        #taskDetailModal .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: white;
            flex-shrink: 0;
        }

        #taskDetailModal .comment-content {
            flex: 1;
        }

        #taskDetailModal .comment-author {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        #taskDetailModal .comment-time {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        #taskDetailModal .comment-text {
            font-size: 0.85rem;
            color: var(--text-main);
            margin-top: 0.5rem;
            line-height: 1.5;
        }

        /* Attachments */
        #taskDetailModal .attachment-item {
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        #taskDetailModal .attachment-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--task-hover-shadow);
        }

        /* Form Styling inside Modal */
        #taskDetailModal .form-control,
        #taskDetailModal .form-select {
            border-radius: 12px;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
        }

        #taskDetailModal .form-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        /* Modal Footer */
        #taskDetailModal .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--surface);
            gap: 0.75rem;
        }

        #taskDetailModal .modal-footer .btn {
            border-radius: 12px;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Status Badge */
        #taskDetailModal .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            background: var(--border-light);
            color: var(--text-main);
        }

        /* Dark Mode Adjustments for Task Detail Modal */
        [data-theme="dark"] #taskDetailModal .info-card {
            background: #0f172a;
        }

        [data-theme="dark"] #taskDetailModal .comment-item {
            background: #0f172a;
        }

        [data-theme="dark"] #taskDetailModal .status-badge {
            background: #334155;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .kanban-column {
                min-width: 280px;
                width: 280px;
            }
            
            .navbar-brand span:not(.brand-icon) {
                display: none;
            }
            
            .members-btn span {
                display: none;
            }

            #taskDetailModal .modal-dialog {
                margin: 0.5rem;
            }
            
            #taskDetailModal .tab-content {
                padding: 1rem;
            }
            
            #taskDetailModal .nav-tabs {
                padding: 0 0.75rem;
            }
            
            #taskDetailModal .nav-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }
            
            #taskDetailModal .modal-footer {
                flex-wrap: wrap;
            }
            
            #taskDetailModal .modal-footer .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left me-1" style="color: var(--text-muted); font-size: 1.2rem;"></i>
                <div class="brand-icon"><i class="bi bi-check2-square"></i></div>
                <span><?= htmlspecialchars($project['name']) ?></span>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <div class="theme-toggle" onclick="toggleTheme()">
                    <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                </div>
                
                <?php if ($is_admin): ?>
                <div class="dropdown">
                    <button class="members-btn d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-people-fill"></i>
                        <span>Anggota</span>
                        <span class="badge bg-primary rounded-pill" style="font-size: 0.7rem;"><?= count($members) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                        <li><h6 class="dropdown-header">Daftar Anggota</h6></li>
                        <li>
                            <div style="max-height: 350px; overflow-y: auto;">
                                <?php foreach ($members as $member): ?>
                                    <div class="d-flex align-items-center justify-content-between px-3 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-small" style="background: <?= getAvatarColor($member['full_name']) ?>;">
                                                <?= getInitials($member['full_name']) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold small" style="color: var(--text-dark);"><?= htmlspecialchars($member['full_name']) ?></div>
                                                <div class="d-flex gap-1 mt-1">
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
                                                <button class="btn btn-sm btn-light p-1" onclick="event.stopPropagation(); updateMemberRole(<?= $member['id'] ?>, '<?= $member['role'] == 'admin' ? 'member' : 'admin' ?>')">
                                                    <i class="bi bi-arrow-left-right text-primary"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light p-1" onclick="event.stopPropagation(); removeMember(<?= $member['id'] ?>)">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </div>
                                        <?php elseif ($is_admin && $member['id'] != $_SESSION['user_id'] && $member['role'] != 'owner'): ?>
                                            <button class="btn btn-sm btn-light p-1" onclick="event.stopPropagation(); removeMember(<?= $member['id'] ?>)">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </li>
                        <?php if ($is_admin): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li class="px-3 py-2">
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
                
                <div class="avatar-group">
                    <?php 
                    $display_members = array_slice($members, 0, 3);
                    foreach($display_members as $m): 
                    ?>
                        <div class="avatar-small" style="background: <?= getAvatarColor($m['full_name']) ?>;" 
                             title="<?= htmlspecialchars($m['full_name']) ?>">
                            <?= getInitials($m['full_name']) ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($members) > 3): ?>
                        <div class="avatar-small" style="background: var(--surface-hover); color: var(--text-muted); border: 1px solid var(--border-color);">
                            +<?= count($members) - 3 ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openNewTaskModal('todo')">
                    <i class="bi bi-plus-lg"></i> <span>Tugas Baru</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-4 py-4">
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Progress</div>
                <div class="stat-value"><?= $stats['progress'] ?>%</div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?= $stats['progress'] ?>%"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">To Do</div>
                <div class="stat-value"><?= $stats['todo'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-value"><?= $stats['in_progress'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Selesai</div>
                <div class="stat-value"><?= $stats['done'] ?></div>
            </div>
        </div>

        <div class="filter-section">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="searchTask" class="form-control ps-4" style="padding-left: 35px;" placeholder="Cari tugas..." onkeyup="filterTasks()">
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
                    <button class="btn btn-outline-primary w-100" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="kanban-header">
            <h5>Kanban Board</h5>
            <?php if ($is_admin): ?>
            <button class="btn btn-outline-primary btn-sm" onclick="openAddColumnModal()">
                <i class="bi bi-plus-lg me-1"></i>Tambah Kolom
            </button>
            <?php endif; ?>
        </div>

        <div class="kanban-board" id="kanbanBoard">
            <div class="text-center py-5 w-100">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 text-muted small">Memuat board...</p>
            </div>
        </div>
    </div>

    <!-- Modals -->
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
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Buat Tugas</button>
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
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($project['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($project['description']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
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
                            <input type="text" class="form-control" name="title" id="columnTitle" placeholder="Contoh: Testing, Backlog, dsb" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warna</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php 
                                $colors = ['#64748b', '#6366f1', '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'];
                                foreach ($colors as $code): 
                                ?>
                                <div class="color-option" onclick="selectColor('<?= $code ?>')" 
                                     style="width: 36px; height: 36px; background: <?= $code ?>; border-radius: 50%; cursor: pointer; border: 2px solid transparent;"
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
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
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
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary px-4" onclick="updateCurrentColumn()">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Anggota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cari Pengguna</label>
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="text" class="form-control ps-4" style="padding-left: 35px;" id="searchUserInput" placeholder="Ketik nama atau username..." onkeyup="searchUsers()">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="memberRole">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div id="searchResults" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-search fs-4 d-block mb-2 opacity-50"></i>
                            <small>Cari pengguna untuk ditambahkan</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Detail Modal - Improved -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" id="taskDetailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;"></div>

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
            if (themeIcon) themeIcon.className = `bi bi-${newTheme === 'dark' ? 'sun' : 'moon'}`;
            document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
            fixDarkModeInputs();
        }

        // Fix dark mode inputs
        function fixDarkModeInputs() {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                const inputs = document.querySelectorAll('input, textarea, select, .form-control, .form-select');
                inputs.forEach(input => {
                    input.style.color = '#ffffff';
                    input.style.backgroundColor = '#1e293b';
                    input.addEventListener('focus', function() {
                        this.style.backgroundColor = '#0f172a';
                        this.style.borderColor = '#818cf8';
                    });
                    input.addEventListener('blur', function() {
                        this.style.backgroundColor = '#1e293b';
                        this.style.borderColor = '#334155';
                    });
                });
                const inputGroups = document.querySelectorAll('.input-group-text');
                inputGroups.forEach(group => {
                    group.style.color = '#94a3b8';
                    group.style.backgroundColor = '#334155';
                });
            }
        }

        // State
        let currentEditingColumnId = null;
        let currentEditingIsDefault = false;
        let searchTimeout;

        document.addEventListener('DOMContentLoaded', function() {
            loadColumns();
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('task_due_date');
            if(dateInput) dateInput.setAttribute('min', today);
            
            const newTaskForm = document.getElementById('newTaskForm');
            if (newTaskForm) newTaskForm.addEventListener('submit', handleNewTaskSubmit);
            
            const editProjectForm = document.getElementById('editProjectForm');
            if (editProjectForm) editProjectForm.addEventListener('submit', handleEditProjectSubmit);
            
            const columnForm = document.getElementById('columnForm');
            if (columnForm) columnForm.addEventListener('submit', handleColumnSubmit);
            
            fixDarkModeInputs();
            
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'data-theme') {
                        fixDarkModeInputs();
                    }
                });
            });
            observer.observe(document.documentElement, { attributes: true });
        });

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
                let matchSearch = title.includes(searchQuery) || desc.includes(searchQuery);
                let matchPriority = filterPriority === '' || priority === filterPriority;
                let matchAssignee = filterAssignee === '' || (filterAssignee === 'unassigned' && assignee === 'unassigned') || assignee === filterAssignee;
                card.style.display = (matchSearch && matchPriority && matchAssignee) ? 'block' : 'none';
            });
            updateVisibleTaskCounts();
        }

        function resetFilters() {
            document.getElementById('searchTask').value = '';
            document.getElementById('filterPriority').value = '';
            document.getElementById('filterAssignee').value = '';
            filterTasks();
        }

        function updateVisibleTaskCounts() {
            document.querySelectorAll('.kanban-column-wrapper').forEach(col => {
                const columnId = col.getAttribute('data-column-id');
                let visibleCount = 0;
                col.querySelectorAll('.task-card').forEach(c => {
                    if (c.style.display !== 'none' && c.hasAttribute('data-task-id')) visibleCount++;
                });
                const countBadge = document.getElementById('count-' + columnId);
                if (countBadge) countBadge.textContent = visibleCount;
            });
        }

        function loadColumns() {
            fetch(`api/columns.php?action=list&project_id=<?= $project_id ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const existingColumns = document.querySelectorAll('.kanban-column-wrapper[data-column-id]');
                        if (existingColumns.length === 0) renderKanbanBoard(data.columns);
                        else updateBoardData(data.columns);
                    } else showNotification('Gagal memuat kolom', 'danger');
                })
                .catch(error => showNotification('Terjadi kesalahan', 'danger'));
        }

        function renderKanbanBoard(columns) {
            const container = document.getElementById('kanbanBoard');
            if (!container) return;
            let html = '';
            columns.forEach(column => {
                const isDefault = column.is_default === true || column.is_default === 'true' || column.is_default === 1;
                const isCustomized = column.is_customized === true || column.is_customized === 'true';
                const columnId = column.id;
                let badgeHtml = '';
                if (!isDefault) badgeHtml = '<span class="custom-column-badge">Kustom</span>';
                else if (isCustomized) badgeHtml = '<span class="custom-column-badge">Diedit</span>';
                html += `
                    <div class="kanban-column-wrapper" data-column-id="${columnId}" data-column-position="${column.position || 0}">
                        <div class="kanban-column">
                            <div class="column-header">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="column-title">
                                        <i class="bi ${column.icon}" style="color: ${column.color};"></i>
                                        <span>${column.title}</span>
                                        ${badgeHtml}
                                    </div>
                                    ${<?= $is_admin ? 'true' : 'false' ?> ? `
                                        <button class="btn btn-sm btn-light p-1 rounded-circle" style="width: 28px; height: 28px;" onclick="event.stopPropagation(); openColumnMenu('${columnId}', '${column.title.replace(/'/g, "\\'")}', '${column.color}', '${column.icon}', ${isDefault}, ${isCustomized})">
                                            <i class="bi bi-three-dots-vertical small"></i>
                                        </button>
                                    ` : ''}
                                </div>
                                <span class="task-count" id="count-${columnId}">0</span>
                            </div>
                            <div id="list-${columnId}" class="task-list" data-column="${columnId}"></div>
                            ${columnId === 'todo' ? `
                                <button class="btn btn-light btn-sm w-100 mt-2" style="border: 1px dashed var(--border-color);" onclick="openNewTaskModal('todo')">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Tugas
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            <?php if ($is_admin): ?>
            html += `
                <div class="add-column-card" onclick="openAddColumnModal()">
                    <div class="text-center">
                        <i class="bi bi-plus-lg fs-3 d-block mb-1" style="color: var(--text-muted);"></i>
                        <small class="text-muted">Tambah Kolom</small>
                    </div>
                </div>
            `;
            <?php endif; ?>
            container.innerHTML = html;
            columns.forEach(column => loadTasksForColumn(column.id));
            setupDragAndDrop();
            fixDarkModeInputs();
        }

        function updateBoardData(columns) {
            const existingColumns = document.querySelectorAll('.kanban-column-wrapper[data-column-id]');
            const existingColumnIds = Array.from(existingColumns).map(el => el.dataset.columnId);
            const newColumns = columns.filter(col => !existingColumnIds.includes(col.id));
            if (newColumns.length > 0) {
                const addButton = document.querySelector('.add-column-card');
                if (addButton) addButton.remove();
                newColumns.forEach(column => {
                    const isDefault = column.is_default === true || column.is_default === 'true';
                    const isCustomized = column.is_customized === true || column.is_customized === 'true';
                    const columnId = column.id;
                    let badgeHtml = '';
                    if (!isDefault) badgeHtml = '<span class="custom-column-badge">Kustom</span>';
                    else if (isCustomized) badgeHtml = '<span class="custom-column-badge">Diedit</span>';
                    const columnHtml = `
                        <div class="kanban-column-wrapper" data-column-id="${columnId}" data-column-position="${column.position || 0}">
                            <div class="kanban-column">
                                <div class="column-header">
                                    <div class="d-flex align-items-center justify-content-between w-100">
                                        <div class="column-title">
                                            <i class="bi ${column.icon}" style="color: ${column.color};"></i>
                                            <span>${column.title}</span>
                                            ${badgeHtml}
                                        </div>
                                        ${<?= $is_admin ? 'true' : 'false' ?> ? `
                                            <button class="btn btn-sm btn-light p-1 rounded-circle" style="width: 28px; height: 28px;" onclick="event.stopPropagation(); openColumnMenu('${columnId}', '${column.title.replace(/'/g, "\\'")}', '${column.color}', '${column.icon}', ${isDefault}, ${isCustomized})">
                                                <i class="bi bi-three-dots-vertical small"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                    <span class="task-count" id="count-${columnId}">0</span>
                                </div>
                                <div id="list-${columnId}" class="task-list" data-column="${columnId}"></div>
                            </div>
                        </div>
                    `;
                    document.getElementById('kanbanBoard').insertAdjacentHTML('beforeend', columnHtml);
                    loadTasksForColumn(columnId);
                });
                <?php if ($is_admin): ?>
                const addButtonHtml = `<div class="add-column-card" onclick="openAddColumnModal()"><div class="text-center"><i class="bi bi-plus-lg fs-3 d-block mb-1" style="color: var(--text-muted);"></i><small class="text-muted">Tambah Kolom</small></div></div>`;
                document.getElementById('kanbanBoard').insertAdjacentHTML('beforeend', addButtonHtml);
                <?php endif; ?>
                setupDragAndDrop();
            }
            columns.forEach(column => {
                const countElement = document.getElementById(`count-${column.id}`);
                if (countElement && column.task_count !== undefined) countElement.textContent = column.task_count;
            });
            filterTasks();
            fixDarkModeInputs();
        }

        function loadTasksForColumn(columnId) {
            const listElement = document.getElementById(`list-${columnId}`);
            if (!listElement) return;
            listElement.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            fetch(`api/tasks.php?action=list_by_column&project_id=<?= $project_id ?>&column=${columnId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) renderTasksInColumn(columnId, data.tasks);
                    else listElement.innerHTML = '<div class="text-center text-muted py-4 small">Gagal memuat</div>';
                })
                .catch(error => listElement.innerHTML = '<div class="text-center text-muted py-4 small">Error</div>');
        }

        function renderTasksInColumn(columnId, tasks) {
            const listElement = document.getElementById(`list-${columnId}`);
            const countElement = document.getElementById(`count-${columnId}`);
            if (!listElement) return;
            if (tasks.length === 0) {
                listElement.innerHTML = '<div class="text-center text-muted py-4 small">Belum ada tugas</div>';
                if (countElement) countElement.textContent = '0';
                return;
            }
            let html = '';
            tasks.forEach(task => html += createTaskCardElement(task));
            listElement.innerHTML = html;
            filterTasks();
            fixDarkModeInputs();
        }

        function createTaskCardElement(task) {
            const priorityClass = 'priority-' + task.priority;
            let priorityLabel = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
            if (task.priority === 'urgent') priorityLabel = 'Urgent!';
            let dueDateHtml = '<div></div>';
            if (task.due_date) {
                const dueDate = new Date(task.due_date);
                const today = new Date();
                today.setHours(0,0,0,0);
                const isOverdue = dueDate < today && task.column_status !== 'done';
                
                // Format tanggal: 12 Mar 2024 (ringkas tapi jelas tahunnya)
                const formattedDate = dueDate.toLocaleDateString('id-ID', { 
                    day: 'numeric', 
                    month: 'short', 
                    year: 'numeric' 
                });
                
                dueDateHtml = `<div class="due-date ${isOverdue ? 'overdue' : ''}" title="${dueDate.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}">
                    <i class="bi bi-calendar-event${isOverdue ? '-fill' : ''} me-1"></i>
                    ${formattedDate}
                </div>`;
            }
            const assigneeHtml = task.assignee_name ? 
                `<div class="avatar-small" style="background: ${getAvatarColor(task.assignee_name)};" title="${escapeHtml(task.assignee_name)}">${getInitials(task.assignee_name)}</div>` : 
                `<div class="avatar-small" style="background: var(--surface-hover); color: var(--text-muted); border: 1px solid var(--border-color);"><i class="bi bi-person fs-6"></i></div>`;
            const safeTitle = escapeHtml(task.title).toLowerCase().replace(/"/g, '&quot;');
            const safeDesc = task.description ? escapeHtml(task.description).toLowerCase().replace(/"/g, '&quot;') : '';
            const safeAssigneeId = task.assignee_id ? task.assignee_id : 'unassigned';
            return `
                <div class="task-card" data-task-id="${task.id}" data-priority="${task.priority}" data-assignee="${safeAssigneeId}" data-title="${safeTitle}" data-desc="${safeDesc}" onclick="showTaskDetail(${task.id})">
                    <span class="task-priority ${priorityClass}">${priorityLabel}</span>
                    <div class="task-title">${escapeHtml(task.title)}</div>
                    ${task.description ? `<div class="task-desc">${escapeHtml(task.description.substring(0, 100))}${task.description.length > 100 ? '...' : ''}</div>` : ''}
                    <div class="task-meta">
                        ${dueDateHtml}
                        ${assigneeHtml}
                    </div>
                </div>
            `;
        }

        function setupDragAndDrop() {
            if (window.columnSortable) window.columnSortable.destroy();
            <?php if ($is_admin): ?>
            const boardContainer = document.getElementById('kanbanBoard');
            if (boardContainer) {
                window.columnSortable = new Sortable(boardContainer, {
                    animation: 150,
                    handle: '.kanban-column-wrapper',
                    draggable: '.kanban-column-wrapper',
                    ghostClass: 'sortable-ghost',
                    onEnd: () => updateColumnPositions()
                });
            }
            <?php endif; ?>
            document.querySelectorAll('.task-list').forEach(list => {
                if (list.sortable) list.sortable.destroy();
                new Sortable(list, {
                    group: 'tasks',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    onEnd: (evt) => moveTask(evt.item.dataset.taskId, evt.to.dataset.column)
                });
            });
        }

        function updateColumnPositions() {
            const columns = document.querySelectorAll('.kanban-column-wrapper[data-column-id]');
            const positions = [];
            columns.forEach((col, index) => {
                const columnId = col.dataset.columnId;
                if (columnId) positions.push({ id: columnId, position: index });
            });
            fetch('api/columns.php?action=update_positions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `project_id=<?= $project_id ?>&positions=${encodeURIComponent(JSON.stringify(positions))}`
            });
        }

        function moveTask(taskId, targetColumn) {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('target_column', targetColumn);
            formData.append('project_id', <?= $project_id ?>);
            fetch('api/tasks.php?action=move_to_column', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { if (!data.success) loadColumns(); else updateVisibleTaskCounts(); });
        }

        function openAddColumnModal() {
            document.getElementById('columnModalTitle').textContent = 'Tambah Kolom Baru';
            document.getElementById('columnId').value = '';
            document.getElementById('columnTitle').value = '';
            document.getElementById('selectedColor').value = '#64748b';
            document.getElementById('columnIcon').value = 'bi-circle';
            document.querySelectorAll('#columnModal .color-option').forEach(opt => {
                opt.style.borderColor = 'transparent';
                if (opt.dataset.color === '#64748b') opt.style.borderColor = 'var(--primary)';
            });
            new bootstrap.Modal(document.getElementById('columnModal')).show();
            setTimeout(fixDarkModeInputs, 100);
        }

        function openColumnMenu(columnId, title, color, icon, isDefault, isCustomized) {
            currentEditingColumnId = columnId;
            currentEditingIsDefault = isDefault === true || isDefault === 'true';
            document.getElementById('editColumnTitle').value = title;
            document.getElementById('editSelectedColor').value = color;
            document.getElementById('editColumnIcon').value = icon;
            const resetBtnContainer = document.getElementById('resetDefaultColumnContainer');
            const deleteBtn = document.getElementById('deleteColumnBtn');
            if (currentEditingIsDefault) {
                document.querySelector('#editColumnModal .modal-title').textContent = 'Edit Kolom Default';
                resetBtnContainer.style.display = isCustomized ? 'block' : 'none';
                deleteBtn.style.display = 'none';
            } else {
                document.querySelector('#editColumnModal .modal-title').textContent = 'Edit Kolom Kustom';
                resetBtnContainer.style.display = 'none';
                deleteBtn.style.display = 'block';
            }
            const colors = ['#64748b', '#6366f1', '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'];
            let colorHtml = '';
            colors.forEach(c => {
                colorHtml += `<div class="color-option ${c === color ? 'selected' : ''}" onclick="selectEditColor('${c}')" style="width: 36px; height: 36px; background: ${c}; border-radius: 50%; cursor: pointer; border: 2px solid ${c === color ? 'var(--primary)' : 'transparent'};" data-color="${c}"></div>`;
            });
            document.getElementById('editColorOptions').innerHTML = colorHtml;
            new bootstrap.Modal(document.getElementById('editColumnModal')).show();
            setTimeout(fixDarkModeInputs, 100);
        }

        function selectColor(color) {
            document.getElementById('selectedColor').value = color;
            document.querySelectorAll('#columnModal .color-option').forEach(opt => {
                opt.style.borderColor = 'transparent';
                if (opt.dataset.color === color) opt.style.borderColor = 'var(--primary)';
            });
        }

        function selectEditColor(color) {
            document.getElementById('editSelectedColor').value = color;
            document.querySelectorAll('#editColorOptions .color-option').forEach(opt => {
                opt.style.borderColor = 'transparent';
                if (opt.dataset.color === color) opt.style.borderColor = 'var(--primary)';
            });
        }

        function handleColumnSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const columnId = document.getElementById('columnId').value;
            const action = columnId !== '' ? 'update' : 'create';
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
            fetch(`api/columns.php?action=${action}`, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('columnModal')).hide();
                        showNotification(data.message, 'success');
                        if (action === 'create' && data.column) addNewColumnToBoard(data.column);
                        else if (action === 'update') updateColumnInBoard(columnId, { title: formData.get('title'), color: formData.get('color'), icon: formData.get('icon') });
                    } else showNotification(data.message, 'danger');
                })
                .catch(error => showNotification('Terjadi kesalahan', 'danger'))
                .finally(() => { submitBtn.innerHTML = originalText; submitBtn.disabled = false; });
        }

        function updateColumnInBoard(columnId, newData) {
            const columnWrapper = document.querySelector(`.kanban-column-wrapper[data-column-id="${columnId}"]`);
            if (!columnWrapper) return;
            const titleElement = columnWrapper.querySelector('.column-title');
            if (titleElement) {
                const iconElement = titleElement.querySelector('i');
                if (iconElement) { iconElement.className = `bi ${newData.icon}`; iconElement.style.color = newData.color; }
                const textSpan = titleElement.querySelector('span');
                if (textSpan) textSpan.textContent = newData.title;
            }
        }

        function updateCurrentColumn() {
            const title = document.getElementById('editColumnTitle').value.trim();
            const color = document.getElementById('editSelectedColor').value;
            const icon = document.getElementById('editColumnIcon').value;
            if (!title) { showNotification('Nama kolom tidak boleh kosong', 'warning'); return; }
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
                fetch('api/columns.php?action=update_default', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('editColumnModal')).hide();
                            updateColumnInBoard(currentEditingColumnId, { title, color, icon });
                            showNotification('Kolom berhasil diperbarui', 'success');
                        } else showNotification(data.message, 'danger');
                    })
                    .catch(error => showNotification('Terjadi kesalahan', 'danger'))
                    .finally(() => { saveBtn.innerHTML = originalText; saveBtn.disabled = false; });
            } else {
                const formData = new FormData();
                formData.append('column_id', currentEditingColumnId.replace('custom_', ''));
                formData.append('title', title);
                formData.append('color', color);
                formData.append('icon', icon);
                fetch('api/columns.php?action=update', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('editColumnModal')).hide();
                            updateColumnInBoard(currentEditingColumnId, { title, color, icon });
                            showNotification('Kolom berhasil diperbarui', 'success');
                        } else showNotification(data.message, 'danger');
                    })
                    .catch(error => showNotification('Terjadi kesalahan', 'danger'))
                    .finally(() => { saveBtn.innerHTML = originalText; saveBtn.disabled = false; });
            }
        }

        function deleteCurrentColumn() {
            if (currentEditingIsDefault) { showNotification('Kolom default tidak dapat dihapus', 'warning'); return; }
            if (!confirm('Yakin ingin menghapus kolom ini? Semua tugas akan dipindahkan ke To Do.')) return;
            const deleteBtn = document.getElementById('deleteColumnBtn');
            const originalText = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';
            deleteBtn.disabled = true;
            const formData = new FormData();
            formData.append('column_id', currentEditingColumnId.replace('custom_', ''));
            fetch('api/columns.php?action=delete', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editColumnModal')).hide();
                        document.querySelector(`.kanban-column-wrapper[data-column-id="${currentEditingColumnId}"]`)?.remove();
                        showNotification('Kolom berhasil dihapus', 'success');
                    } else showNotification(data.message, 'danger');
                })
                .catch(error => showNotification('Terjadi kesalahan', 'danger'))
                .finally(() => { deleteBtn.innerHTML = originalText; deleteBtn.disabled = false; });
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
            fetch('api/columns.php?action=reset_default', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editColumnModal')).hide();
                        loadColumns();
                        showNotification('Kolom dikembalikan ke default', 'success');
                    } else showNotification(data.message, 'danger');
                })
                .catch(error => showNotification('Terjadi kesalahan', 'danger'))
                .finally(() => { resetBtn.innerHTML = originalText; resetBtn.disabled = false; });
        }

        function addNewColumnToBoard(column) {
            const container = document.getElementById('kanbanBoard');
            const addButton = document.querySelector('.add-column-card');
            if (addButton) addButton.remove();
            const columnId = column.id;
            const columnHtml = `
                <div class="kanban-column-wrapper" data-column-id="${columnId}" data-column-position="${column.position || 0}">
                    <div class="kanban-column">
                        <div class="column-header">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="column-title">
                                    <i class="bi ${column.icon}" style="color: ${column.color};"></i>
                                    <span>${column.title}</span>
                                    <span class="custom-column-badge">Kustom</span>
                                </div>
                                ${<?= $is_admin ? 'true' : 'false' ?> ? `
                                    <button class="btn btn-sm btn-light p-1 rounded-circle" style="width: 28px; height: 28px;" onclick="event.stopPropagation(); openColumnMenu('${columnId}', '${column.title.replace(/'/g, "\\'")}', '${column.color}', '${column.icon}', false, false)">
                                        <i class="bi bi-three-dots-vertical small"></i>
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
            document.getElementById(`list-${columnId}`).innerHTML = '<div class="text-center text-muted py-4 small">Belum ada tugas</div>';
            <?php if ($is_admin): ?>
            container.insertAdjacentHTML('beforeend', `<div class="add-column-card" onclick="openAddColumnModal()"><div class="text-center"><i class="bi bi-plus-lg fs-3 d-block mb-1" style="color: var(--text-muted);"></i><small class="text-muted">Tambah Kolom</small></div></div>`);
            <?php endif; ?>
            setupDragAndDrop();
            fixDarkModeInputs();
        }

        function openEditProjectModal() { new bootstrap.Modal(document.getElementById('editProjectModal')).show(); setTimeout(fixDarkModeInputs, 100); }
        
        function handleEditProjectSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            const formData = new FormData(form);
            formData.append('action', 'update');
            fetch('api/projects.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editProjectModal')).hide();
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else showNotification(data.message || 'Gagal memperbarui proyek', 'danger');
                })
                .catch(error => showNotification('Terjadi kesalahan: ' + error.message, 'danger'))
                .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = originalText; });
        }
        
        function deleteProject() {
            if (!confirm('Yakin ingin menghapus proyek ini? Semua tugas akan ikut terhapus!')) return;
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('action', 'delete');
            fetch('api/projects.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => window.location.href = 'dashboard.php', 1500);
                    } else showNotification(data.message || 'Gagal menghapus proyek', 'danger');
                });
        }

        function openAddMemberModal() {
            document.getElementById('searchResults').innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-search fs-4 d-block mb-2 opacity-50"></i><small>Cari pengguna untuk ditambahkan</small></div>`;
            document.getElementById('searchUserInput').value = '';
            new bootstrap.Modal(document.getElementById('addMemberModal')).show();
            setTimeout(fixDarkModeInputs, 100);
        }
        
        function searchUsers() {
            clearTimeout(searchTimeout);
            const query = document.getElementById('searchUserInput').value.trim();
            if (query.length < 2) {
                document.getElementById('searchResults').innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-search fs-4 d-block mb-2 opacity-50"></i><small>Minimal 2 karakter</small></div>`;
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
                                html += `<div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-2" style="background: var(--surface-hover); border: 1px solid var(--border-color);">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-small" style="background: ${user.avatar_color};">${user.initials}</div>
                                        <div><div class="fw-semibold" style="color: var(--text-dark);">${escapeHtml(user.full_name)}</div><small class="text-muted">@${escapeHtml(user.username)}</small></div>
                                    </div>
                                    <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="addMemberToProject(${user.id})"><i class="bi bi-person-plus me-1"></i>Tambah</button>
                                </div>`;
                            });
                            resultsDiv.innerHTML = html;
                        } else resultsDiv.innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-person-x fs-4 d-block mb-2 opacity-50"></i><small>Tidak ada pengguna ditemukan</small></div>`;
                        fixDarkModeInputs();
                    })
                    .catch(error => resultsDiv.innerHTML = '<div class="text-center text-danger py-3">Terjadi kesalahan</div>');
            }, 500);
        }
        
        function addMemberToProject(userId) {
            const role = document.getElementById('memberRole').value;
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('user_id', userId);
            formData.append('role', role);
            fetch('api/project_members.php?action=add', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addMemberModal'))?.hide();
                        showNotification('Anggota berhasil ditambahkan', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else showNotification(data.message || 'Gagal menambahkan anggota', 'danger');
                });
        }
        
        function removeMember(userId) {
            if (!confirm('Yakin ingin menghapus anggota ini?')) return;
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('user_id', userId);
            fetch('api/project_members.php?action=remove', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) { showNotification('Anggota berhasil dihapus', 'success'); setTimeout(() => location.reload(), 1000); }
                    else showNotification(data.message || 'Gagal menghapus anggota', 'danger');
                });
        }
        
        function updateMemberRole(userId, newRole) {
            const roleText = newRole === 'admin' ? 'Admin' : 'Member';
            if (!confirm(`Ubah role menjadi ${roleText}?`)) return;
            const formData = new FormData();
            formData.append('project_id', <?= $project_id ?>);
            formData.append('user_id', userId);
            formData.append('role', newRole);
            fetch('api/project_members.php?action=update_role', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) { showNotification('Role berhasil diperbarui', 'success'); setTimeout(() => location.reload(), 1000); }
                    else showNotification(data.message || 'Gagal memperbarui role', 'danger');
                });
        }

        function openNewTaskModal(status) {
            document.getElementById('task_status').value = status;
            new bootstrap.Modal(document.getElementById('newTaskModal')).show();
            setTimeout(fixDarkModeInputs, 100);
        }
        
        function handleNewTaskSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
            fetch('api/tasks.php?action=create', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Tugas berhasil dibuat', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                        form.reset();
                        document.getElementById('task_status').value = 'todo';
                        loadColumns();
                    } else showNotification(data.message || 'Terjadi kesalahan', 'danger');
                })
                .catch(error => showNotification('Terjadi kesalahan koneksi', 'danger'))
                .finally(() => { submitBtn.innerHTML = originalText; submitBtn.disabled = false; });
        }
        
        function showTaskDetail(taskId) {
            const modalContent = document.getElementById('taskDetailContent');
            const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            modalContent.innerHTML = `
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        Detail Tugas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted small">Memuat data...</p>
                </div>
            `;
            modal.show();
            fetch('api/tasks.php?action=get&id=' + taskId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalContent.innerHTML = data.html;
                        
                        // Initialize tabs
                        const triggerTabList = [].slice.call(modalContent.querySelectorAll('#taskTab button'));
                        triggerTabList.forEach(el => {
                            const tab = new bootstrap.Tab(el);
                            el.addEventListener('click', (e) => {
                                e.preventDefault();
                                tab.show();
                            });
                        });
                        
                        // Set active tab from URL hash or default to details
                        const hash = window.location.hash;
                        if (hash === '#comments') {
                            const commentsTab = modalContent.querySelector('#comments-tab');
                            if (commentsTab) bootstrap.Tab.getInstance(commentsTab)?.show();
                        }
                        
                        setTimeout(fixDarkModeInputs, 100);
                    } else {
                        modalContent.innerHTML = `
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                    Detail Tugas
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-5">
                                <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                                <p class="mt-2">${data.message || 'Gagal memuat data'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = `
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                Detail Tugas
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center py-5">
                            <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                            <p class="mt-2">Terjadi kesalahan koneksi</p>
                        </div>
                    `;
                });
        }

        window.uploadAttachment = function(taskId, inputElement) {
            if (!inputElement.files || inputElement.files.length === 0) return;
            const file = inputElement.files[0];
            if (file.size > 10 * 1024 * 1024) { showNotification('Ukuran file maksimal 10MB', 'danger'); inputElement.value = ''; return; }
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('file', file);
            const uploadBtn = inputElement.closest('label');
            const originalBtnHtml = uploadBtn.innerHTML;
            uploadBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Mengupload...';
            uploadBtn.disabled = true;
            fetch('api/tasks.php?action=upload_attachment', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) { showNotification(data.message, 'success'); refreshAttachments(taskId); }
                    else showNotification(data.message || 'Gagal mengunggah', 'danger');
                })
                .catch(error => showNotification('Terjadi kesalahan saat mengunggah', 'danger'))
                .finally(() => { uploadBtn.innerHTML = originalBtnHtml; uploadBtn.disabled = false; inputElement.value = ''; });
        };
        
        window.refreshAttachments = function(taskId) {
            fetch(`api/tasks.php?action=get_attachments&task_id=${taskId}`)
                .then(response => response.json())
                .then(data => { if (data.success && data.attachments) updateAttachmentsUI(taskId, data.attachments); else showTaskDetail(taskId); })
                .catch(error => showTaskDetail(taskId));
        };
        
        window.updateAttachmentsUI = function(taskId, attachments) {
            const modalContent = document.getElementById('taskDetailContent');
            if (!modalContent) return;
            const attachmentsContainer = modalContent.querySelector('.d-flex.flex-column.gap-2');
            if (!attachmentsContainer) return;
            const canEdit = modalContent.querySelector('#edit-tab') !== null;
            const currentUserId = <?= $_SESSION['user_id'] ?>;
            if (!attachments || attachments.length === 0) {
                attachmentsContainer.innerHTML = `<div class="text-center p-3 rounded-4" style="border: 1px dashed var(--border-color); background: var(--surface-hover);"><small class="text-muted fw-bold">Belum ada file terlampir</small></div>`;
                return;
            }
            let attachmentsHtml = '';
            attachments.forEach(file => {
                const ext = String(file.filename).split('.').pop().toLowerCase();
                let icon = 'bi-file-earmark', bg = '#f1f5f9', col = '#64748b', bgDark = '#1e293b', colDark = '#94a3b8';
                if (['jpg','jpeg','png','gif','webp'].includes(ext)) { icon = 'bi-file-image'; bg = '#e0e7ff'; col = '#4f46e5'; bgDark = '#1e1b4b'; colDark = '#818cf8'; }
                else if (ext === 'pdf') { icon = 'bi-file-pdf'; bg = '#fee2e2'; col = '#ef4444'; bgDark = '#450a0a'; colDark = '#f87171'; }
                else if (['doc','docx','txt'].includes(ext)) { icon = 'bi-file-text'; bg = '#dcfce7'; col = '#10b981'; bgDark = '#064e3b'; colDark = '#34d399'; }
                else if (['xls','xlsx','csv'].includes(ext)) { icon = 'bi-file-spreadsheet'; bg = '#dcfce7'; col = '#10b981'; bgDark = '#064e3b'; colDark = '#34d399'; }
                else if (['zip','rar','7z'].includes(ext)) { icon = 'bi-file-zip'; bg = '#fef3c7'; col = '#d97706'; bgDark = '#422006'; colDark = '#fbbf24'; }
                const canDelete = canEdit || file.uploaded_by === currentUserId;
                attachmentsHtml += `<div class="d-flex align-items-center justify-content-between p-2 rounded-3 attachment-item" style="border: 1px solid var(--border-color); background: var(--surface);">
                    <div class="d-flex align-items-center gap-3 overflow-hidden">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: ${bg}; color: ${col};"><i class="bi ${icon} fs-5"></i></div>
                        <div class="lh-sm text-truncate">
                            <div class="fw-semibold text-truncate small" style="color: var(--text-dark);" title="${escapeHtml(file.filename)}">${escapeHtml(file.filename)}</div>
                            <small class="text-muted" style="font-size: 0.65rem;">Oleh: ${escapeHtml(file.uploaded_by_name || 'Unknown')} • ${new Date(file.uploaded_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}</small>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0 ms-2">
                        <a href="api/tasks.php?action=download_attachment&id=${file.id}" class="btn btn-sm btn-light rounded-circle p-1" style="width: 28px; height: 28px;"><i class="bi bi-download"></i></a>
                        ${canDelete ? `<button type="button" class="btn btn-sm btn-light rounded-circle p-1" style="width: 28px; height: 28px; color: var(--danger);" onclick="deleteAttachment(${file.id}, ${taskId})"><i class="bi bi-trash3"></i></button>` : ''}
                    </div>
                </div>`;
            });
            attachmentsContainer.innerHTML = attachmentsHtml;
            fixDarkModeInputs();
        };
        
        window.deleteAttachment = function(attachmentId, taskId) {
            if (!confirm('Hapus lampiran ini?')) return;
            const formData = new FormData();
            formData.append('attachment_id', attachmentId);
            const deleteBtn = document.querySelector(`button[onclick*="deleteAttachment(${attachmentId},"]`);
            if (deleteBtn) {
                const originalHtml = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                deleteBtn.disabled = true;
                fetch('api/tasks.php?action=delete_attachment', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) { showNotification(data.message, 'success'); refreshAttachments(taskId); }
                        else { showNotification(data.message || 'Gagal menghapus lampiran', 'danger'); deleteBtn.innerHTML = originalHtml; deleteBtn.disabled = false; }
                    })
                    .catch(error => { showNotification('Terjadi kesalahan', 'danger'); deleteBtn.innerHTML = originalHtml; deleteBtn.disabled = false; });
            } else {
                fetch('api/tasks.php?action=delete_attachment', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => { if (data.success) { showNotification(data.message, 'success'); refreshAttachments(taskId); } });
            }
        };
        
        window.updateTask = function(taskId) {
            const form = document.getElementById('editTaskForm');
            if (!form) return;
            const formData = new FormData(form);
            formData.append('task_id', taskId);
            fetch('api/tasks.php?action=update', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { if (data.success) { showNotification('Perubahan disimpan', 'success'); setTimeout(() => location.reload(), 500); } else showNotification(data.message || 'Terjadi kesalahan', 'danger'); });
        };
        
        window.deleteTask = function(taskId) {
            if (!confirm('Yakin ingin menghapus tugas ini?')) return;
            const formData = new FormData();
            formData.append('task_id', taskId);
            fetch('api/tasks.php?action=delete', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => { if(d.success) { showNotification('Tugas berhasil dihapus', 'success'); setTimeout(() => location.reload(), 500); } else showNotification(d.message || 'Gagal menghapus tugas', 'danger'); });
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
                .then(data => { if (data.success) { contentInput.value = ''; showTaskDetail(taskId); } else showNotification(data.message || 'Gagal menambahkan komentar', 'danger'); });
        };

        function escapeHtml(text) { if (!text) return ''; const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        function getInitials(name) { if (!name) return '?'; return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase(); }
        function getAvatarColor(name) { const colors = ['#6366f1', '#14b8a6', '#f59e0b', '#8b5cf6', '#10b981']; return colors[(name?.length || 0) % colors.length]; }
        
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
            toast.style.borderRadius = '10px';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `<div class="d-flex p-2"><div class="toast-body fw-semibold small"><i class="bi ${iconClass} me-2"></i>${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
            container.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }
    </script>
</body>
</html>