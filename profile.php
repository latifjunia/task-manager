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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-person-circle"></i> Profil Pengguna</h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="mb-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=4361ee&color=fff&size=150" 
                                         class="rounded-circle img-thumbnail" 
                                         alt="Profile Picture"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                </div>
                                <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                <p class="text-muted mb-1">@<?php echo htmlspecialchars($user['username']); ?></p>
                                <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                    <?php echo $user['role'] == 'admin' ? 'Administrator' : 'Anggota'; ?>
                                </span>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> Bergabung: <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <form method="POST" action="">
                                    <h5 class="mb-3">Informasi Profil</h5>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="full_name" class="form-label">Nama Lengkap</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                            <small class="text-muted">Username tidak dapat diubah</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo $user['role'] == 'admin' ? 'Administrator' : 'Anggota'; ?>" disabled>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h5 class="mb-3">Ubah Password</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="current_password" class="form-label">Password Saat Ini</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password">
                                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label">Password Baru</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Batal
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Statistics -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Statistik Pengguna</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <?php
                            // Get user statistics
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total_projects FROM project_members WHERE user_id = ?");
                            $stmt->execute([$user_id]);
                            $total_projects = $stmt->fetch()['total_projects'];
                            
                            $stmt = $pdo->prepare("SELECT COUNT(*) as created_tasks FROM tasks WHERE created_by = ?");
                            $stmt->execute([$user_id]);
                            $created_tasks = $stmt->fetch()['created_tasks'];
                            
                            $stmt = $pdo->prepare("SELECT COUNT(*) as assigned_tasks FROM tasks WHERE assignee_id = ?");
                            $stmt->execute([$user_id]);
                            $assigned_tasks = $stmt->fetch()['assigned_tasks'];
                            
                            $stmt = $pdo->prepare("SELECT COUNT(*) as completed_tasks FROM tasks WHERE assignee_id = ? AND column_status = 'done'");
                            $stmt->execute([$user_id]);
                            $completed_tasks = $stmt->fetch()['completed_tasks'];
                            ?>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h3 class="text-primary"><?php echo $total_projects; ?></h3>
                                        <small class="text-muted">Proyek</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h3 class="text-success"><?php echo $created_tasks; ?></h3>
                                        <small class="text-muted">Tugas Dibuat</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h3 class="text-warning"><?php echo $assigned_tasks; ?></h3>
                                        <small class="text-muted">Tugas Diberikan</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h3 class="text-info"><?php echo $completed_tasks; ?></h3>
                                        <small class="text-muted">Tugas Selesai</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>