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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - Task Manager</title>
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

        .register-card {
            width: 100%;
            max-width: 480px;
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

        .btn-register {
            background: #4e73df;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: 0.2s ease-in-out;
        }

        .btn-register:hover {
            background: #2e59d9;
        }

        .small-text {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

<div class="register-card">

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

</body>
</html>
