<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $result = loginUser($username, $password);

        if ($result['success']) {
            redirect('index.php');
        } else {
            $error = $result['message'];
        }
    } else {
        $error = "Username dan password wajib diisi.";
    }
}

// Cek preferensi tema dari cookie
$theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <title>Login - Task Manager</title>
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

        .login-card {
            width: 100%;
            max-width: 440px;
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

        .input-group {
            border-radius: 12px;
            overflow: hidden;
        }

        .input-group-text {
            background: var(--surface-hover);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 0.8rem 1rem;
        }

        .form-control {
            border-radius: 0 12px 12px 0;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            background: var(--surface-hover);
            color: var(--text-dark);
            transition: all 0.3s ease;
            font-size: 0.95rem;
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

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-outline-secondary {
            background: var(--surface-hover);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 0.8rem 1rem;
            border-radius: 0 12px 12px 0;
        }

        .btn-outline-secondary:hover {
            background: var(--surface);
            color: var(--text-dark);
        }

        .alert {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: var(--danger-light, #fee2e2);
            color: var(--danger, #ef4444);
            border-left: 4px solid var(--danger, #ef4444);
        }

        [data-theme="dark"] .alert-danger {
            background: #450a0a;
            color: #fecaca;
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

        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

<div class="login-card">

    <!-- Theme Toggle Button -->
    <div class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
        <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
    </div>

    <!-- Brand -->
    <div class="brand-icon">
        <i class="bi bi-layers-half"></i>
    </div>

    <h2>Selamat Datang</h2>
    <div class="subtitle">Silakan login untuk melanjutkan</div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <div class="mb-4">
            <label class="form-label">Username atau Email</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-person"></i>
                </span>
                <input type="text"
                       name="username"
                       class="form-control"
                       placeholder="Masukkan username atau email"
                       required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>
                <input type="password"
                       name="password"
                       id="password"
                       class="form-control"
                       placeholder="Masukkan password"
                       required>

                <button type="button"
                        class="btn btn-outline-secondary"
                        onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>
            Login
        </button>

        <div class="text-center mt-4 footer-text">
            Belum punya akun?
            <a href="register.php" class="fw-semibold">Daftar Sekarang</a>
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

// Toggle Password Visibility
function togglePassword() {
    const password = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (password.type === "password") {
        password.type = "text";
        eyeIcon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        password.type = "password";
        eyeIcon.classList.replace("bi-eye-slash", "bi-eye");
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