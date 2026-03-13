<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Kelola Tugas & Proyek Tim dengan Mudah</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --primary-light: #e0e7ff;
            --secondary: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            
            --dark: #0f172a;
            --dark-soft: #1e293b;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --light: #f8fafc;
            
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #6366f1, #ec4899);
            --gradient-3: linear-gradient(135deg, #667eea, #764ba2, #6366f1);
            
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-md: 0 20px 25px -5px rgba(0,0,0,0.1);
            --shadow-lg: 0 25px 50px -12px rgba(0,0,0,0.25);
            
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 24px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.03);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(99,102,241,0.3);
        }

        .nav-link {
            font-weight: 500;
            color: var(--gray) !important;
            padding: 0.5rem 1.2rem !important;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary) !important;
            background: var(--primary-light);
        }

        .btn-login {
            background: transparent;
            border: 2px solid var(--primary-light);
            color: var(--primary);
            padding: 0.5rem 1.8rem;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-register {
            background: var(--gradient-2);
            border: none;
            color: white;
            padding: 0.5rem 1.8rem;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(99,102,241,0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99,102,241,0.4);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            padding: 160px 0 100px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, rgba(236,72,153,0.1) 100%);
            border-radius: 50%;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 10;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(99,102,241,0.1);
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(99,102,241,0.2);
            backdrop-filter: blur(5px);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }

        .hero-title span {
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-primary-custom {
            background: var(--gradient-2);
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(99,102,241,0.3);
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(99,102,241,0.4);
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--primary-light);
            padding: 1rem 2.5rem;
            border-radius: 50px;
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline-custom:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .hero-stats {
            display: flex;
            gap: 3rem;
            margin-top: 3rem;
        }

        .stat-item {
            text-align: left;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .hero-image {
            position: relative;
            z-index: 10;
            animation: float 6s ease-in-out infinite;
        }

        .hero-image img {
            width: 100%;
            max-width: 600px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Features Section */
        .features-section {
            padding: 100px 0;
            background: white;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto 4rem;
        }

        .feature-card {
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: var(--radius-lg);
            padding: 2rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--gray);
            line-height: 1.6;
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background: #f8fafc;
        }

        .step-card {
            text-align: center;
            padding: 2rem;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 25px rgba(99,102,241,0.3);
        }

        .step-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .step-description {
            color: var(--gray);
            line-height: 1.6;
        }

        /* Testimonials */
        .testimonials-section {
            padding: 100px 0;
            background: white;
        }

        .testimonial-card {
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .testimonial-text {
            font-size: 1rem;
            color: var(--dark);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .author-info h6 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.2rem;
        }

        .author-info small {
            color: var(--gray);
        }

        /* Pricing Section */
        .pricing-section {
            padding: 100px 0;
            background: #f8fafc;
        }

        .pricing-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .pricing-card.popular {
            transform: scale(1.05);
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-lg);
        }

        .popular-badge {
            position: absolute;
            top: 20px;
            right: -35px;
            background: var(--gradient-2);
            color: white;
            padding: 0.5rem 3rem;
            transform: rotate(45deg);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .pricing-card.popular:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .pricing-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .pricing-price small {
            font-size: 1rem;
            font-weight: 400;
            color: var(--gray);
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            padding: 0.5rem 0;
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .pricing-features i {
            color: var(--success);
        }

        .btn-pricing {
            background: transparent;
            border: 2px solid var(--primary-light);
            color: var(--primary);
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-pricing:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-pricing.popular {
            background: var(--gradient-2);
            border: none;
            color: white;
            box-shadow: 0 5px 15px rgba(99,102,241,0.3);
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: var(--gradient-2);
            color: white;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .cta-description {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            opacity: 0.9;
        }

        .btn-cta {
            background: white;
            color: var(--primary);
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            color: var(--primary-dark);
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 30px;
        }

        .footer-brand {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-description {
            color: var(--gray-light);
            margin-bottom: 2rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: var(--gray-light);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            margin-top: 40px;
            text-align: center;
            color: var(--gray-light);
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-stats {
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .pricing-card.popular {
                transform: scale(1);
            }
            
            .pricing-card.popular:hover {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 767.98px) {
            .hero-section {
                padding: 120px 0 60px;
                text-align: center;
            }
            
            .hero-description {
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-cta {
                justify-content: center;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-image {
                margin-top: 3rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .cta-title {
                font-size: 2rem;
            }
        }

        /* Animations */
        .fade-up {
            animation: fadeUp 1s ease forwards;
            opacity: 0;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.3s; }
        .delay-3 { animation-delay: 0.5s; }
        .delay-4 { animation-delay: 0.7s; }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="brand-icon">
                    <i class="bi bi-layers-half"></i>
                </div>
                <span>TaskFlow</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1" style="color: var(--dark);"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">Cara Kerja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimoni</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Harga</a>
                    </li>
                </ul>
                
                <div class="d-flex gap-2">
                    <a href="login.php" class="btn-login">Masuk</a>
                    <a href="register.php" class="btn-register">Daftar</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="hero-badge fade-up">
                        <i class="bi bi-rocket-takeoff me-2"></i>
                        Kelola Tugas Lebih Efisien
                    </div>
                    
                    <h1 class="hero-title fade-up delay-1">
                        Kelola Proyek & <span>Tugas Tim</span> dengan Mudah
                    </h1>
                    
                    <p class="hero-description fade-up delay-2">
                        Platform manajemen tugas yang intuitif untuk tim produktif. 
                        Kolaborasi, lacak progress, dan selesaikan proyek tepat waktu.
                    </p>
                    
                    <div class="hero-cta fade-up delay-3">
                        <a href="register.php" class="btn-primary-custom">
                            <i class="bi bi-rocket me-2"></i>Mulai Gratis
                        </a>
                        <a href="#features" class="btn-outline-custom">
                            <i class="bi bi-play-circle me-2"></i>Demo
                        </a>
                    </div>
                    
                    <div class="hero-stats fade-up delay-4">
                        <div class="stat-item">
                            <div class="stat-number">50K+</div>
                            <div class="stat-label">Pengguna Aktif</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">100K+</div>
                            <div class="stat-label">Proyek Selesai</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">⭐ 4.9</div>
                            <div class="stat-label">Rating Pengguna</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="hero-image fade-up">
                        <img src="https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" 
                             alt="Task Management Dashboard" 
                             class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <h2 class="section-title fade-up">Fitur Unggulan</h2>
            <p class="section-subtitle fade-up delay-1">
                Semua yang Anda butuhkan untuk mengelola tugas dan proyek tim dengan efektif
            </p>
            
            <div class="row g-4">
                <div class="col-md-4 fade-up delay-1">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-kanban"></i>
                        </div>
                        <h4 class="feature-title">Kanban Board</h4>
                        <p class="feature-description">
                            Visualisasikan workflow tim Anda dengan papan Kanban yang intuitif. 
                            Drag & drop tugas dengan mudah.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up delay-2">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4 class="feature-title">Kolaborasi Tim</h4>
                        <p class="feature-description">
                            Undang anggota tim, bagi tugas, dan berkomentar langsung 
                            pada setiap tugas untuk diskusi yang lebih terarah.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up delay-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h4 class="feature-title">Deadline Tracking</h4>
                        <p class="feature-description">
                            Pantau tenggat waktu setiap tugas. Dapatkan notifikasi 
                            otomatis untuk tugas yang mendekati deadline.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-paperclip"></i>
                        </div>
                        <h4 class="feature-title">File Attachments</h4>
                        <p class="feature-description">
                            Lampirkan file ke tugas. Dukungan berbagai format 
                            termasuk gambar, PDF, dokumen, dan lainnya.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h4 class="feature-title">Laporan & Statistik</h4>
                        <p class="feature-description">
                            Pantau progress proyek dengan grafik dan statistik 
                            real-time. Lihat performa tim Anda.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h4 class="feature-title">Notifikasi Real-time</h4>
                        <p class="feature-description">
                            Dapatkan notifikasi instan untuk update tugas, komentar baru, 
                            dan deadline yang mendekat.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <h2 class="section-title fade-up">Bagaimana Cara Kerjanya</h2>
            <p class="section-subtitle fade-up delay-1">
                Mulai kelola tugas tim Anda dalam 3 langkah mudah
            </p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="step-card fade-up delay-1">
                        <div class="step-number">1</div>
                        <h4 class="step-title">Buat Proyek</h4>
                        <p class="step-description">
                            Buat proyek baru, tentukan nama dan deskripsi. 
                            Atur kolom sesuai kebutuhan tim Anda.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card fade-up delay-2">
                        <div class="step-number">2</div>
                        <h4 class="step-title">Tambah Anggota</h4>
                        <p class="step-description">
                            Undang anggota tim ke proyek. Atur role dan 
                            izin akses sesuai kebutuhan.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card fade-up delay-3">
                        <div class="step-number">3</div>
                        <h4 class="step-title">Mulai Kolaborasi</h4>
                        <p class="step-description">
                            Buat tugas, tentukan prioritas dan deadline. 
                            Mulai kolaborasi dengan tim Anda.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials-section" id="testimonials">
        <div class="container">
            <h2 class="section-title fade-up">Apa Kata Pengguna Kami</h2>
            <p class="section-subtitle fade-up delay-1">
                Ribuan tim telah merasakan manfaat menggunakan TaskFlow
            </p>
            
            <div class="row g-4">
                <div class="col-md-4 fade-up delay-1">
                    <div class="testimonial-card">
                        <div class="testimonial-text">
                            "TaskFlow membantu tim kami meningkatkan produktivitas hingga 40%. 
                            Fitur Kanban board sangat intuitif dan mudah digunakan."
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">AB</div>
                            <div class="author-info">
                                <h6>Ahmad Budiman</h6>
                                <small>Project Manager, TechCorp</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up delay-2">
                    <div class="testimonial-card">
                        <div class="testimonial-text">
                            "Sangat membantu dalam mengelola deadline. Notifikasi otomatis 
                            membuat kami tidak pernah melewatkan tenggat waktu."
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">SR</div>
                            <div class="author-info">
                                <h6>Siti Rahma</h6>
                                <small>Team Lead, StartupHub</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up delay-3">
                    <div class="testimonial-card">
                        <div class="testimonial-text">
                            "UI/UX yang modern dan responsif. Bisa diakses dari mana saja 
                            dan sangat membantu tim remote work kami."
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">DP</div>
                            <div class="author-info">
                                <h6>Dimas Putra</h6>
                                <small>Founder, Digital Agency</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section" id="pricing">
        <div class="container">
            <h2 class="section-title fade-up">Harga yang Fleksibel</h2>
            <p class="section-subtitle fade-up delay-1">
                Pilih paket yang sesuai dengan kebutuhan tim Anda
            </p>
            
            <div class="row g-4 align-items-center">
                <div class="col-md-4 fade-up delay-1">
                    <div class="pricing-card">
                        <h4 class="pricing-name">Basic</h4>
                        <div class="pricing-price">
                            Gratis <small>/selamanya</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="bi bi-check-lg"></i> 3 proyek aktif</li>
                            <li><i class="bi bi-check-lg"></i> 5 anggota tim</li>
                            <li><i class="bi bi-check-lg"></i> Kanban board</li>
                            <li><i class="bi bi-check-lg"></i> File attachments</li>
                            <li><i class="bi bi-check-lg"></i> Notifikasi dasar</li>
                        </ul>
                        <a href="register.php" class="btn-pricing">Mulai Gratis</a>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up delay-2">
                    <div class="pricing-card popular">
                        <div class="popular-badge">POPULER</div>
                        <h4 class="pricing-name">Pro</h4>
                        <div class="pricing-price">
                            Rp99k <small>/bulan</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="bi bi-check-lg"></i> Proyek tak terbatas</li>
                            <li><i class="bi bi-check-lg"></i> 20 anggota tim</li>
                            <li><i class="bi bi-check-lg"></i> Kolom kustom</li>
                            <li><i class="bi bi-check-lg"></i> Laporan & statistik</li>
                            <li><i class="bi bi-check-lg"></i> Prioritas support</li>
                            <li><i class="bi bi-check-lg"></i> Backup data</li>
                        </ul>
                        <a href="register.php" class="btn-pricing popular">Pilih Pro</a>
                    </div>
                </div>
                
                <div class="col-md-4 fade-up delay-3">
                    <div class="pricing-card">
                        <h4 class="pricing-name">Enterprise</h4>
                        <div class="pricing-price">
                            Custom
                        </div>
                        <ul class="pricing-features">
                            <li><i class="bi bi-check-lg"></i> Semua fitur Pro</li>
                            <li><i class="bi bi-check-lg"></i> Unlimited members</li>
                            <li><i class="bi bi-check-lg"></i> SSO & advanced security</li>
                            <li><i class="bi bi-check-lg"></i> Dedicated account manager</li>
                            <li><i class="bi bi-check-lg"></i> API akses</li>
                            <li><i class="bi bi-check-lg"></i> SLA guarantee</li>
                        </ul>
                        <a href="contact.php" class="btn-pricing">Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title fade-up">Siap Meningkatkan Produktivitas Tim?</h2>
            <p class="cta-description fade-up delay-1">
                Mulai kelola proyek dengan lebih efisien. Daftar gratis dan 
                rasakan manfaatnya dalam 5 menit!
            </p>
            <a href="register.php" class="btn-cta fade-up delay-2">
                <i class="bi bi-rocket-takeoff me-2"></i>Mulai Gratis Sekarang
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-brand">
                        <div class="brand-icon">
                            <i class="bi bi-layers-half"></i>
                        </div>
                        <span>TaskFlow</span>
                    </div>
                    <p class="footer-description">
                        Platform manajemen tugas modern untuk tim produktif. 
                        Kelola proyek, kolaborasi, dan capai target tepat waktu.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                        <a href="#"><i class="bi bi-github"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h6 class="footer-title">Produk</h6>
                    <ul class="footer-links">
                        <li><a href="#features">Fitur</a></li>
                        <li><a href="#pricing">Harga</a></li>
                        <li><a href="#">Keamanan</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h6 class="footer-title">Perusahaan</h6>
                    <ul class="footer-links">
                        <li><a href="#">Tentang Kami</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Karir</a></li>
                        <li><a href="#">Kontak</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h6 class="footer-title">Sumber Daya</h6>
                    <ul class="footer-links">
                        <li><a href="#">Dokumentasi</a></li>
                        <li><a href="#">API</a></li>
                        <li><a href="#">Bantuan</a></li>
                        <li><a href="#">Komunitas</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 mb-4">
                    <h6 class="footer-title">Legal</h6>
                    <ul class="footer-links">
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">Syarat & Ketentuan</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 TaskFlow. All rights reserved. Made with <i class="bi bi-heart-fill text-danger"></i> in Indonesia</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNav');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.8s ease';
            observer.observe(el);
        });

        // Counter animation
        function animateValue(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);
                element.textContent = value.toLocaleString() + '+';
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Trigger counter when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = document.querySelectorAll('.stat-number');
                    animateValue(statNumbers[0], 0, 50000, 2000);
                    animateValue(statNumbers[1], 0, 100000, 2000);
                    animateValue(statNumbers[2], 0, 49, 2000);
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const heroStats = document.querySelector('.hero-stats');
        if (heroStats) {
            statsObserver.observe(heroStats);
        }
    </script>
</body>
</html>