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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Task Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fc;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
            padding: 35px;
        }

        .logo {
            width: 55px;
            height: 55px;
            background: #4e73df;
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

        .form-control {
            border-radius: 10px;
            padding: 10px;
        }

        .btn-login {
            background: #4e73df;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: 0.2s ease-in-out;
        }

        .btn-login:hover {
            background: #2e59d9;
        }

        .small-text {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="text-center mb-4">
        <div class="logo">
            <i class="bi bi-kanban-fill"></i>
        </div>
        <h4 class="fw-bold">Task Manager</h4>
        <p class="text-muted small-text mb-0">Sistem Manajemen Tugas</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <div class="mb-3">
            <label class="form-label">Username atau Email</label>
            <div class="input-group">
                <span class="input-group-text bg-white">
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
                <span class="input-group-text bg-white">
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

        <button type="submit" class="btn btn-login w-100">
            <i class="bi bi-box-arrow-in-right me-2"></i>
            Login
        </button>

        <div class="text-center mt-4 small-text">
            Belum punya akun?
            <a href="register.php" class="fw-semibold text-decoration-none">Daftar</a>
        </div>

    </form>
</div>

<script>
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
</script>

</body>
</html>
