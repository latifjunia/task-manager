<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi
    if (empty($full_name) || empty($email)) {
        $error = 'Nama lengkap dan email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        try {
            // Update basic info
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
            
            // Update password if provided
            if (!empty($current_password) && !empty($new_password)) {
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Password saat ini salah';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Password baru tidak sama dengan konfirmasi';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password baru minimal 6 karakter';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    $success .= ' Password berhasil diubah.';
                }
            }
            
            if (empty($error)) {
                $success = 'Profil berhasil diperbarui.' . ($success ?? '');
                // Update session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get user statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_projects FROM project_members WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_projects = $stmt->fetch()['total_projects'];

$stmt = $pdo->prepare("SELECT COUNT(*) as created_tasks FROM tasks WHERE created_by = ?");
$stmt->execute([$user_id]);
$created_tasks = $stmt->fetch()['created_tasks'];

$stmt = $pdo->prepare("SELECT COUNT(*) as assigned_tasks FROM tasks WHERE assignee_id = ?");
$stmt->execute([$user_id]);
$assigned_tasks = $stmt->fetch()['assigned_tasks'];

$stmt = $pdo->prepare("SELECT COUNT(*) as completed_tasks FROM tasks WHERE assignee_id = ? AND column_status = 'done'");
$stmt->execute([$user_id]);
$completed_tasks = $stmt->fetch()['completed_tasks'];

// Cek preferensi tema dari cookie
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Task Manager</title>
    
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
            background: rgba(var(--surface-rgb, 255,255,255), 0.75) !important;
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
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            box-shadow: var(--card-hover-shadow);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }

        /* Profile Header */
        .profile-header {
            background: var(--welcome-gradient);
            background-size: 200% 200%;
            animation: gradientMove 10s ease infinite;
            color: white;
            border: none;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .profile-header::before {
            content: '';
            position: absolute; right: -5%; top: -20%;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .profile-username {
            font-size: 1.1rem;
            opacity: 0.9;
            color: white;
        }

        /* Stats Cards */
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
            background: var(--surface-hover);
        }
        
        .stat-icon-sm {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        .stat-icon-purple { 
            background: var(--primary-light); 
            color: var(--primary); 
        }
        .stat-icon-green { 
            background: var(--success-light); 
            color: var(--success); 
        }
        .stat-icon-orange { 
            background: var(--warning-light); 
            color: var(--warning); 
        }
        .stat-icon-blue { 
            background: #dbeafe; 
            color: #2563eb; 
        }
        
        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.2;
        }

        /* Form Controls */
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select { 
            border-radius: 12px; 
            padding: 0.8rem 1rem; 
            border: 1px solid var(--border-color); 
            background: var(--surface-hover);
            color: var(--text-dark);
            transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease;
            font-size: 0.95rem;
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
        
        .form-control:disabled, .form-control[readonly] {
            background: var(--surface-hover);
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Buttons */
        .btn { 
            padding: 0.7rem 1.5rem; 
            border-radius: 12px; 
            font-weight: 600; 
            transition: 0.3s; 
            letter-spacing: 0.3px;
            font-size: 0.95rem;
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
        
        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            background: transparent;
            border: 2px solid var(--border-color-solid);
            color: var(--text-muted);
        }
        
        .btn-outline-secondary:hover {
            background: var(--surface-hover);
            color: var(--text-dark);
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border: none;
            font-weight: 500;
        }
        
        .alert-success {
            background: var(--success-light);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background: var(--danger-light);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .badge.bg-primary { background: var(--primary) !important; }
        .badge.bg-success { background: var(--success) !important; }
        .badge.bg-warning { background: var(--warning) !important; color: var(--text-dark); }
        .badge.bg-danger { background: var(--danger) !important; }

        /* Divider */
        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 1.5rem 0;
        }

        /* Info Box */
        .info-box {
            background: var(--surface-hover);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
        }
        
        .info-box .label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-box .value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .profile-name {
                font-size: 1.5rem;
            }
            
            .profile-header {
                padding: 1.5rem;
                text-align: center;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }

        /* Toast */
        .toast {
            background: var(--surface) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
        }
        
        .toast.bg-success {
            background: var(--success) !important;
            color: white !important;
        }
        
        .toast.bg-danger {
            background: var(--danger) !important;
            color: white !important;
        }
        
        .toast-container {
            z-index: 9999;
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
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="index.php">
                <div class="brand-icon"><i class="bi bi-layers-half"></i></div>
                <span>Task Manager</span>
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

    <div class="container px-lg-5 py-4">
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center position-relative">
                <div class="col-md-auto text-center text-md-start">
                    <div class="profile-avatar mx-auto mx-md-0">
                        <?= getInitials($user['full_name']) ?>
                    </div>
                </div>
                <div class="col-md">
                    <h1 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h1>
                    <div class="profile-username mb-2">@<?= htmlspecialchars($user['username']) ?></div>
                    <div>
                        <span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                            <?= $user['role'] == 'admin' ? 'Administrator' : 'Anggota Tim' ?>
                        </span>
                        <span class="badge bg-light text-muted ms-2">
                            <i class="bi bi-calendar me-1"></i> Bergabung <?= date('M Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column - Profile Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil-square me-2" style="color: var(--primary);"></i>
                        Edit Profil
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="profileForm">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="full_name" 
                                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                    <small class="text-muted">Username tidak dapat diubah</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" 
                                           value="<?= $user['role'] == 'admin' ? 'Administrator' : 'Anggota' ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <h5 class="mb-3" style="color: var(--text-dark);">Ubah Password</h5>
                            <p class="text-muted small mb-4">Kosongkan jika tidak ingin mengubah password</p>
                            
                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Password Saat Ini</label>
                                    <input type="password" class="form-control" name="current_password" id="current_password">
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" name="new_password" id="new_password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-outline-secondary px-4">
                                    <i class="bi bi-x-circle me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Statistics -->
            <div class="col-lg-4">
                <!-- Info Cards -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-2" style="color: var(--primary);"></i>
                        Informasi Akun
                    </div>
                    <div class="card-body">
                        <div class="info-box mb-3">
                            <div class="label">ID Pengguna</div>
                            <div class="value">#<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        
                        <div class="info-box mb-3">
                            <div class="label">Email</div>
                            <div class="value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        
                        <div class="info-box">
                            <div class="label">Terdaftar Sejak</div>
                            <div class="value"><?= date('d F Y', strtotime($user['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-bar-chart me-2" style="color: var(--success);"></i>
                        Statistik Aktivitas
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon-sm stat-icon-purple">
                                        <i class="bi bi-folder"></i>
                                    </div>
                                    <div class="stat-label">Proyek</div>
                                    <div class="stat-value"><?= $total_projects ?></div>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon-sm stat-icon-green">
                                        <i class="bi bi-pencil-square"></i>
                                    </div>
                                    <div class="stat-label">Dibuat</div>
                                    <div class="stat-value"><?= $created_tasks ?></div>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon-sm stat-icon-orange">
                                        <i class="bi bi-person-check"></i>
                                    </div>
                                    <div class="stat-label">Ditugaskan</div>
                                    <div class="stat-value"><?= $assigned_tasks ?></div>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon-sm stat-icon-blue">
                                        <i class="bi bi-check2-all"></i>
                                    </div>
                                    <div class="stat-label">Selesai</div>
                                    <div class="stat-value"><?= $completed_tasks ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3" style="background: var(--surface-hover); border-radius: 12px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold" style="color: var(--text-dark);">Produktivitas</span>
                                <span class="badge bg-success"><?= $assigned_tasks > 0 ? round(($completed_tasks / max($assigned_tasks, 1)) * 100) : 0 ?>%</span>
                            </div>
                            <div class="progress-wrapper mt-2">
                                <div class="progress-fill" style="width: <?= $assigned_tasks > 0 ? round(($completed_tasks / max($assigned_tasks, 1)) * 100) : 0 ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-4 mt-5"></div>

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

        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Password tidak sama');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            newPassword.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });

        // Form submission with toast
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            // Biarkan form submit normal
            // Tapi kita bisa tambah loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
        });

        // Show toast if there's success/error message
        <?php if (!empty($success)): ?>
        setTimeout(function() {
            showToast('<?= addslashes($success) ?>', 'success');
        }, 500);
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        setTimeout(function() {
            showToast('<?= addslashes($error) ?>', 'danger');
        }, 500);
        <?php endif; ?>

        // Toast function
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
    </script>
</body>
</html>