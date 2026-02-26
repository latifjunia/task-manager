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

    if ($fullname && $username && $email && $password && $confirm) {

        if ($password !== $confirm) {
            $error = "Konfirmasi password tidak cocok.";
        } else {
            $result = registerUser($fullname, $username, $email, $password);

            if ($result['success']) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = $result['message'];
            }
        }

    } else {
        $error = "Semua field wajib diisi.";
    }
}

// Cek preferensi tema dari cookie
$theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <title>Register - Task Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4e73df;
            --primary-hover: #2e59d9;
            --bg-body: #f8f9fc;
            --surface: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --input-bg: #ffffff;
            --shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-hover: #6366f1;
            --bg-body: #0f172a;
            --surface: #1e293b;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #334155;
            --shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        * {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.2s ease;
        }

        body {
            background: var(--bg-body);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .register-card {
            width: 100%;
            max-width: 480px;
            background: var(--surface);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 35px;
            position: relative;
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
            border: 1px solid var(--border-color);
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.3s;
        }
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: var(--input-bg);
        }

        .logo {
            width: 55px;
            height: 55px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
        }

        .logo i {
            font-size: 1.5rem;
            color: white;
        }

        h4 {
            color: var(--text-main);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .form-control:focus {
            background: var(--surface);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            color: var(--text-main);
        }
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.5;
        }

        .btn-register {
            background: var(--primary);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: 0.2s ease-in-out;
            color: white;
        }
        .btn-register:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }
        [data-theme="dark"] .alert-danger {
            background-color: #7f1d1d;
            color: #fecaca;
            border-color: #991b1b;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        [data-theme="dark"] .alert-success {
            background-color: #064e3b;
            color: #a7f3d0;
            border-color: #10b981;
        }

        a {
            color: var(--primary);
        }
        a:hover {
            color: var(--primary-hover);
        }

        .small-text {
            font-size: 0.9rem;
        }

        .form-label {
            color: var(--text-main) !important;
        }
    </style>
</head>

<body>

<div class="register-card">

    <!-- Theme Toggle Button -->
    <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
        <i class="bi bi-sun-fill" id="themeIcon"></i>
    </div>

    <div class="text-center mb-4">
        <div class="logo">
            <i class="bi bi-kanban-fill"></i>
        </div>
        <h4 class="fw-bold">Daftar Akun</h4>
        <p class="text-muted small-text mb-0">Bergabung dengan Task Manager</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($success) ?>
            <br>
            <a href="login.php" class="fw-semibold text-decoration-none">Login sekarang</a>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="fullname" class="form-control"
                   placeholder="Masukkan nama lengkap" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control"
                   placeholder="Masukkan username" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control"
                   placeholder="nama@email.com" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password"
                   id="password" class="form-control"
                   placeholder="Masukkan password" required>
        </div>

        <div class="mb-4">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="confirm_password"
                   id="confirm_password" class="form-control"
                   placeholder="Ulangi password" required>
        </div>

        <button type="submit" class="btn btn-register w-100">
            <i class="bi bi-person-plus me-2"></i>
            Daftar
        </button>

        <div class="text-center mt-4 small-text">
            Sudah punya akun?
            <a href="login.php" class="fw-semibold text-decoration-none">Login</a>
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
    document.cookie = `theme=${newTheme}; path=/; max-age=31536000`; // 1 year
    
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (icon) {
        icon.className = theme === 'light' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
}

// Set initial theme icon
document.addEventListener('DOMContentLoaded', function() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcon(currentTheme);
});
</script>

</body>
</html>