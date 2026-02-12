<?php
require_once 'config.php';
require_once 'functions.php';

// FUNGSI REGISTRASI
function registerUser($username, $email, $password, $full_name) {
    global $pdo;
    
    // Cek apakah username atau email sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Username atau email sudah terdaftar'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user baru
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
        return ['success' => true, 'message' => 'Registrasi berhasil'];
    }
    
    return ['success' => false, 'message' => 'Registrasi gagal'];
}

// FUNGSI LOGIN
function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        
        return ['success' => true, 'message' => 'Login berhasil'];
    }
    
    return ['success' => false, 'message' => 'Username atau password salah'];
}

// FUNGSI LOGOUT
function logout() {
    session_destroy();
    redirect('login.php');
}
?>