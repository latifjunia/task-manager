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

// Cek preferensi tema dari cookie
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$notif_count = getNotificationCount($user_id);
$overdue_count = getOverdueTasksCount($user_id);
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
            --danger: #ef4444;
            
            --bg-body: #f8fafc;
            --bg-gradient: linear-gradient(120deg, #f8fafc 0%, #eef2ff 100%);
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
            --danger: #ef4444;
            
            --bg-body: #0f172a;
            --bg-gradient: linear-gradient(120deg, #0f172a 0%, #1e1b4b 100%);
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
        }

        .theme-toggle {
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            background: var(--surface); border: 1px solid var(--border-color); color: var(--text-main); 
            cursor: pointer; transition: 0.3s;
        }
        .theme-toggle:hover { background: var(--primary-light); color: var(--primary); transform: rotate(15deg); }

        .icon-circle-sm { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
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

        /* Form Controls */
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .form-control { 
            border-radius: 12px; 
            padding: 0.8rem 1rem; 
            border: 1px solid var(--border-color); 
            background: var(--surface-hover);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        
        .form-control:focus { 
            background: var(--surface); 
            border-color: var(--primary); 
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); 
            outline: none;
        }
        
        .form-control:disabled {
            background: var(--surface-hover);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-primary { 
            background: var(--primary); 
            border: none; 
            padding: 0.7rem 1.5rem; 
            border-radius: 12px; 
            font-weight: 600; 
            transition: 0.3s; 
            color: white;
        }
        
        .btn-primary:hover { 
            background: var(--primary-hover); 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); 
        }
        
        .btn-outline-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 0.7rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
        }
        
        .btn-outline-secondary:hover {
            background: var(--surface-hover);
            color: var(--text-dark);
        }

        .alert {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border: none;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #10b981;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #ef4444;
            border-left: 4px solid #ef4444;
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
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="dashboard.php">
                <div class="brand-icon"><i class="bi bi-layers-half"></i></div>
                <span>Task Manager</span>
            </a>
            
            <div class="d-flex align-items-center ms-auto gap-3">
                <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                    <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                </div>

                <!-- Notifikasi -->
                <div class="dropdown">
                    <a class="nav-link position-relative d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                        <div class="icon-circle-sm" style="background: var(--surface-hover); color: var(--text-main);">
                            <i class="bi bi-bell"></i>
                            <?php if ($notif_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white" style="font-size: 0.65rem; padding: 0.25rem 0.5rem;">
                                    <?= $notif_count ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 350px; border-radius: 16px; padding: 0; overflow: hidden;">
                        <div class="p-3 border-bottom">
                            <h6 class="mb-0 fw-bold" style="color: var(--text-dark);">Notifikasi</h6>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bell-slash fs-1 text-muted opacity-50 mb-2"></i>
                                <p class="text-muted mb-0 small">Belum ada notifikasi</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 320px; overflow-y: auto;">
                                <?php foreach ($notifications as $notif): ?>
                                    <a class="dropdown-item d-flex p-3 border-bottom" href="notifications.php">
                                        <div class="me-3">
                                            <div class="icon-circle-sm" style="background: var(--primary-light); color: var(--primary); width: 36px; height: 36px;">
                                                <i class="bi bi-info-circle"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars($notif['title']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($notif['message']) ?></div>
                                            <small class="text-muted mt-1 d-block"><?= timeAgo($notif['created_at']) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <a class="d-flex align-items-center gap-2 text-decoration-none" href="#" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=6366f1&color=fff&size=40" 
                             class="rounded-circle border border-2" style="border-color: var(--border-color) !important; width: 40px; height: 40px;">
                        <div class="d-none d-md-block text-start">
                            <div class="fw-bold small" style="color: var(--text-dark);"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                            <small class="text-muted" style="font-size: 0.7rem;"><?= $is_admin ? 'Admin' : 'Member' ?></small>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" style="border-radius: 12px; min-width: 180px;">
                        <a class="dropdown-item py-2 px-3 rounded-3" href="profile.php">
                            <i class="bi bi-person me-2"></i>Profil
                        </a>
                        <div class="dropdown-divider my-2"></div>
                        <a class="dropdown-item py-2 px-3 rounded-3 text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container px-lg-5 py-4">
        
        <!-- Profile Header -->
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
                    <div>
                        <span class="badge-role">
                            <i class="bi bi-shield-check me-1"></i>
                            <?= $user['role'] == 'admin' ? 'Administrator' : 'Anggota Tim' ?>
                        </span>
                        <span class="badge-role ms-2">
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

        <!-- Form Edit Profil (Sederhana) -->
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
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4">
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
            
            document.cookie = `theme=${newTheme}; path=/; max-age=604800`;
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

        // Form submission loading state
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>