<?php
/**
 * Public Layout Header — used ONLY for anonymous/public-facing pages
 * (the course catalog landing experience). This is intentionally a
 * separate file from admin/views/layout_header.php, which assumes an
 * authenticated session — keeping them separate means nothing about
 * the existing logged-in experience needs to change.
 */
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Session.php';
Session::start();
$activeRoute = $_GET['route'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Course Catalog'); ?> - Test Bank LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide === 'undefined') { document.write('<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"><\/script>'); }</script>
    <style>
        :root {
            --primary-indigo: #4f46e5;
            --primary-indigo-hover: #4338ca;
            --bg-slate: #f8fafc;
            --border-light: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-slate); color: var(--text-dark); }
        .display-font { font-family: 'Outfit', sans-serif; letter-spacing: -0.025em; }
        .btn-primary { background-color: var(--primary-indigo); border-color: var(--primary-indigo); }
        .btn-primary:hover, .btn-primary:focus { background-color: var(--primary-indigo-hover); border-color: var(--primary-indigo-hover); }
        .public-navbar { background: #fff; border-bottom: 1px solid var(--border-light); }
        .public-navbar .nav-link { color: var(--text-dark); font-weight: 500; }
        .public-navbar .nav-link.active { color: var(--primary-indigo); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg public-navbar py-3 mb-4">
    <div class="container">
        <a class="navbar-brand display-font fw-bold d-flex align-items-center gap-2" href="index.php?route=courses">
            <i data-lucide="graduation-cap" class="text-primary"></i> Test Bank LMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeRoute === 'courses' || strpos($activeRoute, 'course/details') !== false) ? 'active' : ''; ?>" href="index.php?route=courses">Course Catalog</a>
                </li>
                <li class="nav-item mt-2 mt-lg-0">
                    <a class="btn btn-outline-primary btn-sm px-3" href="index.php?route=login">Log In</a>
                </li>
                <li class="nav-item mt-2 mt-lg-0">
                    <a class="btn btn-primary btn-sm px-3" href="index.php?route=register">Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container pb-5">
