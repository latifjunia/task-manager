<?php
require_once 'config.php';
require_once 'functions.php';

function registerUser($full_name, $username, $email, $password) {
    global $pdo;
    
    // Cek apakah username atau email sudah terdaftar
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Username atau email sudah terdaftar'];
    }
    
    // Hash password dengan benar
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user baru - perhatikan urutan parameter sesuai struktur tabel
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, created_at) VALUES (?, ?, ?, ?, 'member', NOW())");
    
    if ($stmt->execute([$username, $email, $hashed, $full_name])) {
        return ['success' => true, 'message' => 'Registrasi berhasil'];
    }
    
    return ['success' => false, 'message' => 'Registrasi gagal'];
}

function loginUser($username, $password) {
    global $pdo;
    
    // Cari user berdasarkan username ATAU email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    // Debug: log percobaan login
    error_log("Login attempt - username: " . $username);
    error_log("User found: " . ($user ? 'Yes' : 'No'));
    
    if ($user) {
        error_log("Password verify: " . (password_verify($password, $user['password']) ? 'Success' : 'Failed'));
    }
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        return ['success' => true, 'message' => 'Login berhasil'];
    }
    
    return ['success' => false, 'message' => 'Username atau password salah'];
}

function logout() {
    session_destroy();
    redirect('login.php');
}
?>