<?php
/**
 * Public Layout Header — used for anonymous visitors and public catalog view
 */
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Session.php';
Session::start();
$activeRoute = $_GET['route'] ?? '';
$currentUser = Auth::getUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Course Catalog'); ?> - Test Bank LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide === 'undefined') { document.write('<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"><\/script>'); }</script>
    <style>
        :root {
            --primary-indigo: #4f46e5;
            --primary-indigo-hover: #4338ca;
            --primary-light: #eef2ff;
            --bg-slate: #f8fafc;
            --border-light: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-slate); 
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .display-font { font-family: 'Outfit', sans-serif; letter-spacing: -0.025em; }
        
        .btn-primary { 
            background-color: var(--primary-indigo); 
            border-color: var(--primary-indigo);
            font-weight: 600;
            padding: 0.6rem 1.25rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus { 
            background-color: var(--primary-indigo-hover); 
            border-color: var(--primary-indigo-hover); 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        
        .btn-outline-primary {
            color: var(--primary-indigo);
            border-color: #cbd5e1;
            font-weight: 600;
            border-radius: 0.5rem;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-light);
            color: var(--primary-indigo-hover);
            border-color: var(--primary-indigo);
        }

        .public-navbar { 
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-light); 
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .public-navbar .nav-link { 
            color: var(--text-dark); 
            font-weight: 600; 
            padding: 0.5rem 0.85rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease;
        }
        .public-navbar .nav-link:hover, .public-navbar .nav-link.active { 
            color: var(--primary-indigo); 
            background-color: var(--primary-light);
        }

        /* Hero & Section Enhancements */
        .hero-banner {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.94) 0%, rgba(30, 27, 75, 0.92) 50%, rgba(49, 46, 129, 0.95) 100%), 
                        url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.3) !important;
        }
        .hero-banner::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(0,0,0,0) 70%);
            pointer-events: none;
        }

        /* Course Cards */
        .course-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            border-radius: 1rem;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px -8px rgba(15, 23, 42, 0.12) !important;
            border-color: #cbd5e1;
        }
        .course-thumb-box {
            height: 200px;
            width: 100%;
            position: relative;
            overflow: hidden;
            background-color: #1e293b;
        }
        .course-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .course-card:hover .course-thumb-img {
            transform: scale(1.05);
        }

        /* Category Grid Cards */
        .category-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 0.85rem;
            padding: 1.25rem 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            transition: all 0.2s ease;
            height: 100%;
        }
        .category-card:hover {
            border-color: var(--primary-indigo);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.12);
            color: var(--primary-indigo);
        }
        .category-card.active {
            background: var(--primary-indigo);
            border-color: var(--primary-indigo);
            color: #ffffff !important;
            box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.3);
        }
        .category-card.active .category-icon-box {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        .category-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 0.65rem;
            background: var(--primary-light);
            color: var(--primary-indigo);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 1rem;
            padding: 1.75rem;
            transition: all 0.2s ease;
        }
        .feature-card:hover {
            border-color: #c7d2fe;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.08);
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            background: var(--primary-light);
            color: var(--primary-indigo);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg public-navbar py-2 mb-0">
    <div class="container">
        <a class="navbar-brand display-font fw-bold d-flex align-items-center gap-2 text-dark fs-4" href="index.php?route=courses">
            <span class="p-2 bg-primary text-white rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i data-lucide="graduation-cap" size="22"></i>
            </span>
            <span>Test Bank <span class="text-primary">LMS</span></span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeRoute === 'courses' || strpos($activeRoute, 'course/') !== false) ? 'active' : ''; ?>" href="index.php?route=courses">
                        <i data-lucide="book-open" size="16" class="me-1"></i> Course Catalog
                    </a>
                </li>
                <?php if ($currentUser): ?>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="index.php?route=<?php echo $currentUser['role'] === 'student' ? 'student/dashboard' : 'admin/dashboard'; ?>">
                            <i data-lucide="layout-dashboard" size="16"></i>
                            <span>My Dashboard</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-outline-primary px-3 py-2" href="index.php?route=login">
                            <i data-lucide="log-in" size="16" class="me-1"></i> Log In
                        </a>
                    </li>
                    <li class="nav-item ms-lg-1 mt-2 mt-lg-0">
                        <a class="btn btn-primary px-3 py-2" href="index.php?route=register">
                            <i data-lucide="user-plus" size="16" class="me-1"></i> Get Started Free
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">

