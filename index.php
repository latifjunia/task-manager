<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Jika user sudah login, langsung arahkan ke dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Cek preferensi tema dari cookie
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - Kelola Tugas & Proyek Tim dengan Mudah</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #eef2ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            
            --text-dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            
            --border-color: #e2e8f0;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            
            --radius-sm: 12px;
            --radius-md: 20px;
            --radius-lg: 24px;
            --radius-full: 9999px;
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #1e1b4b;
            
            --bg-body: #0f172a;
            --surface: #1e293b;
            --surface-hover: #334155;
            
            --text-dark: #f1f5f9;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            
            --border-color: #334155;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
        }

        /* Navigation */
        .navbar {
            background: var(--surface);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--text-dark) !important;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-muted) !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        .theme-toggle {
            width: 36px;
            height: 36px;
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
            background: var(--primary-light);
            color: var(--primary);
        }

        .btn-login {
            background: transparent;
            border: 1.5px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--primary);
            color: white;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99,102,241,0.4);
            color: white;
        }

        /* Hero Section (Updated) */
        .hero-section {
            padding: 160px 0 100px;
            position: relative;
            background: linear-gradient(to bottom, var(--bg-body), var(--surface));
            overflow: hidden;
        }

        .hero-bg-glow {
            position: absolute;
            top: -10%;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 400px;
            background: radial-gradient(ellipse at center, rgba(99, 102, 241, 0.15) 0%, rgba(255,255,255,0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        [data-theme="dark"] .hero-bg-glow {
            background: radial-gradient(ellipse at center, rgba(99, 102, 241, 0.25) 0%, rgba(30, 41, 59, 0) 70%);
        }

        .hero-content {
            text-align: center;
            max-width: 850px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            background: var(--surface);
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(99, 102, 241, 0.2);
            box-shadow: var(--shadow-sm);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero-title .text-gradient {
            background: linear-gradient(135deg, var(--primary), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        .hero-cta {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 0.9rem 2.2rem;
            border-radius: var(--radius-full);
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .btn-outline-custom {
            background: var(--surface);
            border: 1px solid var(--border-color);
            padding: 0.9rem 2.2rem;
            border-radius: var(--radius-full);
            color: var(--text-main);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline-custom:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .hero-stats {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stat-number {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* Features */
        .features-section {
            padding: 70px 0;
            background: var(--surface);
        }

        .section-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1rem;
            color: var(--text-muted);
            max-width: 650px;
            margin: 0 auto 3rem;
        }

        .feature-card {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.8rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .feature-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--primary-light), transparent);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .feature-description {
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 0.9rem;
        }

        /* How It Works */
        .how-it-works {
            padding: 70px 0;
        }

        .step-card {
            text-align: center;
            padding: 1.5rem;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0 auto 1.2rem;
        }

        .step-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .step-description {
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 0.9rem;
        }

        /* Footer */
        .footer {
            background: var(--surface);
            padding: 50px 0 30px;
            border-top: 1px solid var(--border-color);
        }

        .footer-brand {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-dark);
            text-decoration: none;
        }

        .footer-description {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-title {
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .social-links {
            display: flex;
            gap: 0.8rem;
        }

        .social-links a {
            width: 35px;
            height: 35px;
            background: var(--surface-hover);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .social-links a:hover {
            background: var(--primary);
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .hero-section { padding: 120px 0 60px; }
            .hero-title { font-size: 2.5rem; }
            .navbar-collapse {
                background: var(--surface);
                padding: 1rem;
                border-radius: var(--radius-md);
                margin-top: 0.5rem;
                border: 1px solid var(--border-color);
            }
        }

        @media (max-width: 576px) {
            .hero-title { font-size: 2rem; }
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            .hero-cta a {
                text-align: center;
                width: 100%;
                justify-content: center;
            }
        }

        .fade-up {
            animation: fadeUp 0.6s ease forwards;
            opacity: 0;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .delay-1 { animation-delay: 0.1s; animation-fill-mode: forwards; }
        .delay-2 { animation-delay: 0.2s; animation-fill-mode: forwards; }
        .delay-3 { animation-delay: 0.3s; animation-fill-mode: forwards; }
        .delay-4 { animation-delay: 0.4s; animation-fill-mode: forwards; }
        
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="brand-icon"><i class="bi bi-check2-square"></i></div>
                <span>Task Manager</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-2" style="color: var(--text-dark);"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">Cara Kerja</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
                    <div class="theme-toggle" onclick="toggleTheme()">
                        <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                    </div>
                    <a href="login.php" class="btn-login">Masuk</a>
                    <a href="register.php" class="btn-register">Daftar</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="hero-bg-glow"></div>
        <div class="container position-relative">
            <div class="hero-content">
                <div class="hero-badge fade-up"><i class="bi bi-stars me-1"></i>Kelola Tugas Lebih Efisien</div>
                <h1 class="hero-title fade-up delay-1">Kelola Proyek & <span class="text-gradient">Tugas Tim</span> dengan Mudah</h1>
                <p class="hero-description fade-up delay-2">Platform manajemen tugas yang intuitif untuk tim produktif. Kolaborasi, lacak progress, dan selesaikan proyek tepat waktu.</p>
                <div class="hero-cta fade-up delay-3">
                    <a href="register.php" class="btn-primary-custom"><i class="bi bi-rocket-takeoff me-2"></i>Mulai Sekarang</a>
                    <a href="#features" class="btn-outline-custom"><i class="bi bi-play-circle me-2"></i>Lihat Fitur</a>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section" id="features">
        <div class="container">
            <h2 class="section-title reveal">Fitur Unggulan</h2>
            <p class="section-subtitle reveal">Semua yang Anda butuhkan untuk mengelola tugas dan proyek tim dengan efektif</p>
            <div class="row g-4">
                <div class="col-md-4 reveal">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-kanban"></i></div>
                        <h4 class="feature-title">Kanban Board</h4>
                        <p class="feature-description">Visualisasikan workflow tim Anda dengan papan Kanban yang intuitif. Drag & drop tugas dengan mudah.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-people"></i></div>
                        <h4 class="feature-title">Kolaborasi Tim</h4>
                        <p class="feature-description">Undang anggota tim, bagi tugas, dan berkomentar langsung pada setiap tugas untuk diskusi yang lebih terarah.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-calendar-check"></i></div>
                        <h4 class="feature-title">Deadline Tracking</h4>
                        <p class="feature-description">Pantau tenggat waktu setiap tugas. Dapatkan notifikasi otomatis untuk tugas yang mendekati deadline.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-paperclip"></i></div>
                        <h4 class="feature-title">File Attachments</h4>
                        <p class="feature-description">Lampirkan file ke tugas. Dukungan berbagai format termasuk gambar, PDF, dokumen, dan lainnya.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-graph-up"></i></div>
                        <h4 class="feature-title">Laporan & Statistik</h4>
                        <p class="feature-description">Pantau progress proyek dengan grafik dan statistik real-time. Lihat performa tim Anda.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-bell"></i></div>
                        <h4 class="feature-title">Notifikasi Real-time</h4>
                        <p class="feature-description">Dapatkan notifikasi instan untuk update tugas, komentar baru, dan deadline yang mendekat.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <h2 class="section-title reveal">Bagaimana Cara Kerjanya</h2>
            <p class="section-subtitle reveal">Mulai kelola tugas tim Anda dalam 3 langkah mudah</p>
            <div class="row">
                <div class="col-md-4 reveal">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h4 class="step-title">Buat Proyek</h4>
                        <p class="step-description">Buat proyek baru, tentukan nama dan deskripsi. Atur kolom sesuai kebutuhan tim Anda.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h4 class="step-title">Tambah Anggota</h4>
                        <p class="step-description">Undang anggota tim ke proyek. Atur role dan izin akses sesuai kebutuhan.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4 class="step-title">Mulai Kolaborasi</h4>
                        <p class="step-description">Buat tugas, tentukan prioritas dan deadline. Mulai kolaborasi dengan tim Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Task Manager. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            const icon = document.querySelector('.theme-toggle i');
            if (icon) icon.className = `bi bi-${newTheme === 'dark' ? 'sun' : 'moon'}`;
            document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== "#" && href.length > 1) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Scroll reveal
        const reveals = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        reveals.forEach(reveal => observer.observe(reveal));
        
        // Fade-up elements
        document.querySelectorAll('.fade-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            
            const fadeObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        fadeObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            fadeObserver.observe(el);
        });
    </script>
</body>
</html>