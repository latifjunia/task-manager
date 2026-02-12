<?php
require_once 'config.php';
require_once 'functions.php';

function registerUser($username, $email, $password, $full_name) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Username atau email sudah terdaftar'];
    }
    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$username, $email, $hashed, $full_name])) {
        return ['success' => true, 'message' => 'Registrasi berhasil'];
    }
    return ['success' => false, 'message' => 'Registrasi gagal'];
}

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return ['success' => true, 'message' => 'Login berhasil'];
    }
    return ['success' => false, 'message' => 'Username atau password salah'];
}

function logout() {
    session_destroy();
    redirect('login.php');
}
?>