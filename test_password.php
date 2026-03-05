<?php
require_once 'includes/config.php';

echo "<h2>Test Password Login</h2>";

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    echo "<h3>Hasil Pencarian:</h3>";
    if ($user) {
        echo "User ditemukan: " . $user['username'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Full Name: " . $user['full_name'] . "<br>";
        echo "Password hash: " . $user['password'] . "<br>";
        echo "Password verify: " . (password_verify($password, $user['password']) ? '✅ BENAR' : '❌ SALAH') . "<br>";
    } else {
        echo "❌ User tidak ditemukan!<br>";
    }
}

// Tampilkan semua users
echo "<h3>Daftar Users:</h3>";
$stmt = $pdo->query("SELECT id, username, email, full_name, LEFT(password, 30) as password_preview FROM users");
$users = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nama</th><th>Password (preview)</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . $user['email'] . "</td>";
    echo "<td>" . $user['full_name'] . "</td>";
    echo "<td>" . $user['password_preview'] . "...</td>";
    echo "</tr>";
}
echo "</table>";
?>

<h3>Test Login Manual:</h3>
<form method="POST">
    <input type="text" name="username" placeholder="Username atau Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Test Login</button>
</form>