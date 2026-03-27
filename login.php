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
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #1e1b4b;
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
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--text-main);
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2.5rem;
            position: relative;
            border: 1px solid var(--border-color);
        }

        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
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
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: var(--primary-light);
            color: var(--primary);
        }

        .brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem auto;
        }

        .brand-icon i {
            font-size: 1.8rem;
            color: white;
        }

        h2 {
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .subtitle {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .input-group {
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .input-group-text {
            background: var(--surface-hover);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 0.7rem 1rem;
        }

        .form-control {
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            padding: 0.7rem 1rem;
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

        .btn-login {
            background: var(--primary);
            border: none;
            border-radius: var(--radius-md);
            padding: 0.8rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-outline-secondary {
            background: var(--surface-hover);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 0.7rem 1rem;
        }

        .btn-outline-secondary:hover {
            background: var(--surface);
            color: var(--text-dark);
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        [data-theme="dark"] .alert-danger {
            background: #450a0a;
            color: #fecaca;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .footer-text {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* ===== DARK MODE INPUT FIX ===== */
        [data-theme="dark"] input,
        [data-theme="dark"] .form-control,
        [data-theme="dark"] input.form-control {
            color: #ffffff !important;
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] input::placeholder,
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

        [data-theme="dark"] .input-group-text {
            color: #94a3b8 !important;
            background-color: #334155 !important;
            border-color: #334155 !important;
        }

        @media (max-width: 576px) {
            .login-card { padding: 2rem 1.5rem; }
            h2 { font-size: 1.4rem; }
        }
    </style>
</head>

<body>

<div class="login-card">
    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
    </div>

    <div class="brand-icon">
        <i class="bi bi-check2-square"></i>
    </div>

    <h2>Selamat Datang</h2>
    <div class="subtitle">Silakan login untuk melanjutkan</div>

    <?php if ($error): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-4">
            <label class="form-label">Username atau Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username atau email" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </button>

        <div class="text-center mt-4 footer-text">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>
        </div>
    </form>
</div>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
    const icon = document.querySelector('.theme-toggle i');
    if (icon) icon.className = `bi bi-${newTheme === 'dark' ? 'sun' : 'moon'}`;
    fixDarkModeInputs();
}

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

function fixDarkModeInputs() {
    if (document.documentElement.getAttribute('data-theme') === 'dark') {
        document.querySelectorAll('input, .form-control').forEach(el => {
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
        document.querySelectorAll('.input-group-text').forEach(el => {
            el.style.color = '#94a3b8';
            el.style.backgroundColor = '#334155';
        });
    }
}

document.addEventListener('DOMContentLoaded', fixDarkModeInputs);
</script>

</body>
</html>