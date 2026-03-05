# Task Manager - Aplikasi Manajemen Tugas

Aplikasi manajemen tugas berbasis web yang memungkinkan tim untuk berkolaborasi dalam proyek, melacak kemajuan tugas, dan mengelola tenggat waktu dengan antarmuka yang modern dan responsif.

## 🚀 Fitur Utama

- ✅ **Manajemen Proyek** - Buat dan kelola beberapa proyek
- 📋 **Kanban Board** - Drag & drop tugas antar kolom (To Do, In Progress, Review, Done)
- 👥 **Kolaborasi Tim** - Tambah anggota, beri komentar, upload file
- 🔔 **Notifikasi** - Pemberitahuan real-time
- 🌓 **Dark Mode** - Tersedia tema gelap dan terang
- 📱 **Responsive** - Bisa diakses dari HP dan desktop

## 🛠️ Teknologi

- PHP 7.4+ (Native)
- MySQL 5.7+
- Bootstrap 5
- JavaScript (SortableJS)

## 🚀 Cara Instalasi

### 1. Install XAMPP
Download dan install XAMPP dari [apachefriends.org](https://www.apachefriends.org/)

### 2. Start Apache & MySQL
Buka XAMPP Control Panel, klik **Start** pada Apache dan MySQL

### 3. Copy Project
Copy folder `task-manager` ke:
C:\xampp\htdocs\task-manager

### 4. Buat Database
- Buka: http://localhost/phpmyadmin
- Klik **New**, buat database dengan nama: **task_manager**
- Klik tab **Import**, pilih file **task_manager.sql**
- Klik **Go**

### 5. Jalankan Aplikasi
Buka browser, akses:
http://localhost/task-manager

## 🔐 Akun Login
### 👑 Administrator
Username : admin
Password : admin098
Email : admin@taskmanager.com
### 👤 Member
Username : john
Password : jj1234
Email : john@example.com

## 📁 Struktur Folder
task-manager/
├── api/ # File API (attachments, comments, dll)
├── assets/ # CSS, JavaScript
├── includes/ # Config, functions, auth
├── uploads/ # Folder untuk file upload
├── index.php # Halaman utama
├── login.php # Halaman login
├── register.php # Halaman registrasi
├── project.php # Detail proyek
└── profile.php # Profil user
