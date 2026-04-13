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
    
    if (empty($full_name) || empty($email)) {
        $error = 'Nama lengkap dan email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
            
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
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$notif_count = getNotificationCount($user_id);
$notifications = getUnreadNotifications($user_id);
$is_admin = isAdmin();
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Task Manager</title>
    
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
            --danger: #ef4444;
            --warning: #f59e0b;
            
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            --surface-active: #f1f5f9;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            
            --transition: all 0.2s ease;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #1e1b4b;
            
            --bg-body: #0f172a;
            --surface: #1e293b;
            --surface-hover: #334155;
            --surface-active: #2d3a4e;
            
            --text-dark: #f1f5f9;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            
            --border-color: #334155;
            --border-light: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--border-light); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }

        /* Navbar */
        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
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
            background: var(--primary);
            border-radius: 10px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white; 
            font-size: 1.1rem;
        }

        /* Icon Circle */
        .icon-circle-sm {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            transition: var(--transition);
            cursor: pointer;
        }
        
        .icon-circle-sm:hover {
            background: var(--surface-hover);
            border-color: var(--primary);
            color: var(--primary);
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
            background: var(--surface-hover);
            color: var(--primary);
        }

        /* Profile Header */
        .profile-header {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .profile-name {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        .profile-username {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .badge-role {
            background: var(--surface-hover);
            color: var(--text-main);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--border-color);
        }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }

        /* Form Elements */
        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .form-control { 
            border-radius: var(--radius-sm); 
            padding: 0.6rem 1rem; 
            border: 1px solid var(--border-color); 
            background: var(--surface);
            color: var(--text-dark);
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .form-control:focus { 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); 
            outline: none;
        }
        
        .form-control:disabled {
            background: var(--surface-hover);
            opacity: 0.7;
        }

        /* Buttons */
        .btn { 
            padding: 0.6rem 1.5rem; 
            border-radius: var(--radius-sm); 
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
        }
        
        .btn-outline-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        
        .btn-outline-secondary:hover {
            background: var(--surface-hover);
            border-color: var(--text-muted);
        }

        /* Alerts */
        .alert {
            border-radius: var(--radius-sm);
            padding: 0.875rem 1.25rem;
            border: 1px solid transparent;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            border-color: #a7f3d0;
            color: #065f46;
        }
        
        .alert-danger {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        [data-theme="dark"] .alert-success {
            background: #064e3b;
            border-color: #065f46;
            color: #a7f3d0;
        }

        [data-theme="dark"] .alert-danger {
            background: #450a0a;
            border-color: #7f1d1d;
            color: #fecaca;
        }

        /* Divider */
        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 1.5rem 0;
        }
        
        /* Section Title */
        .section-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: var(--primary);
        }
        
        .text-muted-small {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* Dropdown */
        .dropdown-menu {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 0.5rem;
        }
        
        .dropdown-item {
            color: var(--text-main);
            border-radius: var(--radius-sm);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        
        .dropdown-item:hover {
            background: var(--surface-hover);
            color: var(--text-dark);
        }
        
        .dropdown-divider {
            border-color: var(--border-color);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header { padding: 1.5rem; }
            .profile-avatar { width: 80px; height: 80px; font-size: 2rem; }
            .profile-name { font-size: 1.5rem; }
            .card-body { padding: 1.25rem; }
        }
        
        @media (max-width: 576px) {
            .navbar-brand span { display: none; }
            .profile-header .row { flex-direction: column; text-align: center; gap: 1rem; }
            .profile-header .col-md-auto { text-align: center !important; }
            .profile-avatar { margin: 0 auto !important; }
            .d-flex.gap-2.flex-wrap { justify-content: center; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand" href="dashboard.php">
                <div class="brand-icon"><i class="bi bi-check2-square"></i></div>
                <span>Task Manager</span>
            </a>
            
            <div class="d-flex align-items-center ms-auto gap-2">
                <div class="theme-toggle" onclick="toggleTheme()">
                    <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                </div>

                <div class="dropdown">
                    <div class="position-relative" data-bs-toggle="dropdown" style="cursor: pointer;">
                        <div class="icon-circle-sm">
                            <i class="bi bi-bell"></i>
                            <?php if ($notif_count > 0): ?>
                                <span class="notification-badge"><?= $notif_count > 9 ? '9+' : $notif_count ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm" style="width: 340px;">
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <h6 class="mb-0 fw-semibold small" style="color: var(--text-dark);">Notifikasi</h6>
                            <?php if ($notif_count > 0): ?>
                                <span class="badge bg-primary rounded-pill" style="font-size: 0.65rem;"><?= $notif_count ?> Baru</span>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bell-slash fs-2 text-muted opacity-50"></i>
                                <p class="text-muted small mt-2 mb-0">Belum ada notifikasi</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 360px; overflow-y: auto;">
                                <?php foreach ($notifications as $notif): ?>
                                    <a class="dropdown-item d-flex p-3 border-bottom" href="notifications.php">
                                        <div class="me-3 flex-shrink-0">
                                            <div class="rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: var(--primary-light); color: var(--primary);">
                                                <i class="bi bi-info-circle"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small" style="color: var(--text-dark);"><?= htmlspecialchars($notif['title']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($notif['message']) ?></div>
                                            <small class="text-muted mt-1 d-block" style="font-size: 0.6rem;"><?= timeAgo($notif['created_at']) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="p-2 text-center border-top">
                                <a href="notifications.php" class="text-decoration-none small fw-semibold" style="color: var(--primary);">Lihat semua</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <a class="d-flex align-items-center gap-2 text-decoration-none" href="#" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=6366f1&color=fff&size=36&bold=true&length=2&rounded=true" 
                             class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                        <div class="d-none d-md-block text-start">
                            <div class="fw-semibold small" style="color: var(--text-dark);"><?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?></div>
                            <small class="text-muted" style="font-size: 0.65rem;"><?= $is_admin ? 'Admin' : 'Member' ?></small>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm mt-2">
                        <a class="dropdown-item" href="profile.php">
                            <i class="bi bi-person me-2"></i>Profil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container px-3 px-lg-4 py-4">
        
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto text-center text-md-start">
                    <div class="profile-avatar mx-auto mx-md-0">
                        <?= getInitials($user['full_name']) ?>
                    </div>
                </div>
                <div class="col-md">
                    <h1 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h1>
                    <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge-role">
                            <i class="bi bi-<?= $user['role'] == 'admin' ? 'shield-check' : 'person-check' ?>"></i>
                            <?= $user['role'] == 'admin' ? 'Administrator' : 'Member' ?>
                        </span>
                        <span class="badge-role">
                            <i class="bi bi-calendar"></i> <?= date('M Y', strtotime($user['created_at'])) ?>
                        </span>
                        <span class="badge-role">
                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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
                    
                    <div class="mb-4">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                        <small class="text-muted-small mt-1 d-block">
                            <i class="bi bi-info-circle"></i> Username tidak dapat diubah
                        </small>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="section-title">
                        <i class="bi bi-lock-fill"></i>
                        Ubah Password
                    </div>
                    <p class="text-muted-small mb-4">Kosongkan jika tidak ingin mengubah password</p>
                    
                    <div class="mb-4">
                        <label class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control" name="current_password" id="current_password">
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Minimal 6 karakter">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
            const themeIcon = document.querySelector('.theme-toggle i');
            if (themeIcon) themeIcon.className = `bi bi-${newTheme === 'dark' ? 'sun' : 'moon'}`;
        }

        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePassword() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Password tidak sama');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                    
                    if (newPassword.value.length > 0 && newPassword.value.length < 6) {
                        newPassword.setCustomValidity('Password minimal 6 karakter');
                    } else {
                        newPassword.setCustomValidity('');
                    }
                }
                
                newPassword.addEventListener('input', validatePassword);
                confirmPassword.addEventListener('input', validatePassword);
            }
        });

        // Form submit with loading state
        const profileForm = document.getElementById('profileForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (profileForm && submitBtn) {
            profileForm.addEventListener('submit', function() {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }, 5000);
            });
        }
        
        // Auto-dismiss alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 4000);
        });
    </script>
</body>
</html>