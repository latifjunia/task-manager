<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Jika user sudah login, langsung arahkan ke dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
// Jika belum login, arahkan ke halaman login
    redirect('login.php');
}
?>