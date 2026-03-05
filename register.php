<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    // Validasi input
    if (empty($fullname) || empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Semua field wajib diisi.";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Panggil fungsi registerUser dengan urutan parameter yang benar
        $result = registerUser($fullname, $username, $email, $password);

        if ($result['success']) {
            $success = $result['message'] . " Silakan login.";
            // Kosongkan form
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}

// Cek preferensi tema dari cookie
$theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <title>Daftar - Task Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
            --danger: #ef4444;
            
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
            --welcome-gradient: linear-gradient(120deg, #4f46e5, #ec4899, #8b5cf6);
        }

        /* Dark Mode Variables */
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
            
            --border-color: rgba(51, 65, 85, 0.8);
            --border-color-solid: #334155;
            
            --card-shadow: 0 10px 40px -10px rgba(0,0,0,0.2);
            --shadow-hover: 0 20px 40px -10px rgba(129, 140, 248, 0.2);
            
            --welcome-gradient: linear-gradient(120deg, #4f46e5, #db2777, #7c3aed);
        }

        * {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.2s ease;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--text-main);
        }

        .register-card {
            width: 100%;
            max-width: 500px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--card-shadow);
            padding: 2.5rem;
            position: relative;
            border: 1px solid var(--border-color);
        }

        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
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
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: var(--primary-light);
            color: var(--primary);
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem auto;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .brand-icon i {
            font-size: 2rem;
            color: white;
        }

        h2 {
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .subtitle {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            background: var(--surface-hover);
            color: var(--text-dark);
            transition: all 0.3s ease;
            font-size: 0.95rem;
            width: 100%;
        }

        .form-control:focus {
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

        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: white;
            width: 100%;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: #fee2e2;
            color: #ef4444;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #10b981;
            border-left: 4px solid #10b981;
        }

        [data-theme="dark"] .alert-danger {
            background: #450a0a;
            color: #fecaca;
        }

        [data-theme="dark"] .alert-success {
            background: #064e3b;
            color: #a7f3d0;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .footer-text {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .password-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .register-card {
                padding: 2rem 1.5rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

<div class="register-card">

    <!-- Theme Toggle Button -->
    <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
        <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
    </div>

    <!-- Brand -->
    <div class="brand-icon">
        <i class="bi bi-layers-half"></i>
    </div>

    <h2>Buat Akun Baru</h2>
    <div class="subtitle">Bergabung dengan Task Manager</div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($success) ?>
            <br>
            <a href="login.php" class="fw-semibold mt-2 d-inline-block">Login sekarang</a>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" 
                   name="fullname" 
                   class="form-control"
                   placeholder="Masukkan nama lengkap" 
                   value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" 
                   name="username" 
                   class="form-control"
                   placeholder="Masukkan username" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" 
                   name="email" 
                   class="form-control"
                   placeholder="nama@email.com" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password"
                   name="password"
                   id="password"
                   class="form-control"
                   placeholder="Minimal 6 karakter"
                   required>
            <div class="password-hint">
                <i class="bi bi-info-circle me-1"></i>
                Minimal 6 karakter
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password"
                   name="confirm_password"
                   id="confirm_password"
                   class="form-control"
                   placeholder="Ulangi password"
                   required>
        </div>

        <button type="submit" class="btn-register">
            <i class="bi bi-person-plus me-2"></i>
            Daftar
        </button>

        <div class="text-center mt-4 footer-text">
            Sudah punya akun?
            <a href="login.php" class="fw-semibold">Login</a>
        </div>

    </form>
</div>

<script>
// Theme Management
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    html.setAttribute('data-theme', newTheme);
    document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
    
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.querySelector('.theme-toggle i');
    if (icon) {
        icon.className = `bi bi-${theme === 'light' ? 'moon' : 'sun'}`;
    }
}

// Password validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak sama');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
});

// Set initial theme icon
document.addEventListener('DOMContentLoaded', function() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcon(currentTheme);
});
</script>

</body>
</html>