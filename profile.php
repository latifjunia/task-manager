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
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: #e2e8f0;
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #1e1b4b;
            --success: #10b981;
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
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--border-color); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }

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
            gap: 10px;
        }
        
        .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
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
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: var(--primary-light);
            color: var(--primary);
        }

        .icon-circle-sm {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface-hover);
            color: var(--text-main);
            transition: all 0.3s ease;
        }
        
        .icon-circle-sm:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .profile-username {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .badge-role {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

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
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .form-control { 
            border-radius: var(--radius-md); 
            padding: 0.6rem 1rem; 
            border: 1px solid var(--border-color); 
            background: var(--surface-hover);
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        
        .form-control:focus { 
            background: var(--surface); 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); 
            outline: none;
        }
        
        .form-control:disabled {
            background: var(--surface-hover);
            opacity: 0.7;
        }

        .btn { 
            padding: 0.5rem 1.25rem; 
            border-radius: var(--radius-md); 
            font-weight: 600; 
            transition: all 0.3s ease; 
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
        
        .btn-outline-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        
        .btn-outline-secondary:hover {
            background: var(--surface-hover);
            color: var(--text-dark);
        }

        .alert {
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            border: none;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        [data-theme="dark"] .alert-success {
            background: #064e3b;
            color: #a7f3d0;
        }

        [data-theme="dark"] .alert-danger {
            background: #450a0a;
            color: #fecaca;
        }

        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 1.5rem 0;
        }
        
        .section-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
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

        .dropdown-menu {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.5rem;
        }
        
        .dropdown-item {
            color: var(--text-main);
            border-radius: var(--radius-sm);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .dropdown-item:hover {
            background: var(--surface-hover);
            color: var(--text-dark);
        }
        
        .dropdown-divider {
            border-color: var(--border-color);
        }

        /* ===== DARK MODE INPUT FIX ===== */
        [data-theme="dark"] input,
        [data-theme="dark"] textarea,
        [data-theme="dark"] .form-control {
            color: #ffffff !important;
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] input::placeholder,
        [data-theme="dark"] textarea::placeholder,
        [data-theme="dark"] .form-control::placeholder {
            color: #94a3b8 !important;
        }

        [data-theme="dark"] input:focus,
        [data-theme="dark"] .form-control:focus {
            color: #ffffff !important;
            background-color: #0f172a !important;
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2) !important;
        }

        [data-theme="dark"] input:disabled,
        [data-theme="dark"] .form-control:disabled {
            background-color: #0f172a !important;
            color: #64748b !important;
        }

        @media (max-width: 768px) {
            .profile-avatar { width: 80px; height: 80px; font-size: 2rem; }
            .profile-name { font-size: 1.5rem; }
            .profile-header { padding: 1.5rem; }
            .card-body { padding: 1.25rem; }
        }
        
        @media (max-width: 576px) {
            .navbar-brand span { display: none; }
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
                    <a class="nav-link position-relative d-flex align-items-center p-0" href="#" data-bs-toggle="dropdown">
                        <div class="icon-circle-sm">
                            <i class="bi bi-bell"></i>
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
                                <i class="bi bi-bell-slash fs-1 text-muted opacity-50"></i>
                                <p class="text-muted small mt-2">Belum ada notifikasi</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 360px; overflow-y: auto;">
                                <?php foreach ($notifications as $notif): ?>
                                    <a class="dropdown-item d-flex p-3 border-bottom" href="notifications.php">
                                        <div class="me-3">
                                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: var(--primary-light); color: var(--primary);">
                                                <i class="bi bi-info-circle"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small" style="color: var(--text-dark);"><?= htmlspecialchars($notif['title']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($notif['message']) ?></div>
                                            <small class="text-muted mt-1 d-block"><?= timeAgo($notif['created_at']) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="p-2 text-center border-top">
                                <a href="notifications.php" class="text-decoration-none small" style="color: var(--primary);">Lihat semua notifikasi</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <a class="d-flex align-items-center gap-2 text-decoration-none" href="#" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=6366f1&color=fff&size=36&bold=true&length=2" 
                             class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                        <div class="d-none d-md-block text-start">
                            <div class="fw-semibold small" style="color: var(--text-dark);"><?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?></div>
                            <small class="text-muted" style="font-size: 0.7rem;"><?= $is_admin ? 'Admin' : 'Member' ?></small>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow mt-2">
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

    <div class="container-fluid px-3 px-lg-4 py-4">
        
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
                            <i class="bi bi-shield-check me-1"></i>
                            <?= $user['role'] == 'admin' ? 'Administrator' : 'Anggota Tim' ?>
                        </span>
                        <span class="badge-role">
                            <i class="bi bi-calendar me-1"></i> Bergabung <?= date('M Y', strtotime($user['created_at'])) ?>
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
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                            <small class="text-muted-small mt-1 d-block">Username tidak dapat diubah</small>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="section-title">
                        <i class="bi bi-lock-fill"></i>
                        Ubah Password
                    </div>
                    <p class="text-muted-small mb-4">Kosongkan jika tidak ingin mengubah password</p>
                    
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
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
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
            fixDarkModeInputs();
        }

        function fixDarkModeInputs() {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.querySelectorAll('input, textarea, .form-control').forEach(el => {
                    el.style.color = '#ffffff';
                    el.style.backgroundColor = '#1e293b';
                    el.addEventListener('focus', function() {
                        this.style.backgroundColor = '#0f172a';
                        this.style.borderColor = '#818cf8';
                    });
                    el.addEventListener('blur', function() {
                        this.style.backgroundColor = '#1e293b';
                        this.style.borderColor = '#334155';
                    });
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            fixDarkModeInputs();
            
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePassword() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Password tidak sama');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                newPassword.addEventListener('change', validatePassword);
                confirmPassword.addEventListener('keyup', validatePassword);
            }
        });

        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
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
    </script>
</body>
</html>