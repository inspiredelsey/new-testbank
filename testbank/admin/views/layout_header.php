<?php
/**
 * Shared Admin Layout Header
 */
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';
Session::start();
$currentUser = Auth::getUser();
$activeRoute = $_GET['route'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Dashboard'); ?> - Test Bank LMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-indigo: #4f46e5;
            --primary-indigo-hover: #4338ca;
            --primary-indigo-subtle: #f5f3ff;
            --bg-slate: #f8fafc;
            --border-light: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-slate);
            color: var(--text-dark);
        }
        .display-font {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.025em;
        }
        h1, h2, h3, h4, h5, h6, .card-title {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.015em;
            font-weight: 600;
        }
        
        /* Custom sidebar in Clean Minimalism style */
        .sidebar {
            min-height: 100vh;
            background-color: #ffffff;
            color: var(--text-dark);
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            border-right: 1px solid var(--border-light);
        }
        .sidebar .nav-link {
            color: #475569;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.7rem 1.25rem;
            border-radius: 0.5rem;
            margin: 0.2rem 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link:hover {
            color: var(--primary-indigo);
            background-color: var(--primary-indigo-subtle);
        }
        .sidebar .nav-link.active {
            color: var(--primary-indigo) !important;
            background-color: #e0e7ff !important;
            font-weight: 600;
        }
        .sidebar .nav-link i, .sidebar .nav-link [data-lucide] {
            opacity: 0.75;
            transition: opacity 0.2s;
        }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i,
        .sidebar .nav-link:hover [data-lucide], .sidebar .nav-link.active [data-lucide] {
            opacity: 1;
        }
        
        /* Main Content wrapper */
        .main-content {
            margin-left: 260px;
            padding: 2.5rem;
            min-height: 100vh;
            background-color: var(--bg-slate);
        }
        
        /* Custom navbar styles targeting the header block */
        .main-content > .d-flex.justify-content-between.align-items-center.mb-4.bg-white {
            border-bottom: 1px solid #e2e8f0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin-top: -2.5rem;
            margin-left: -2.5rem;
            margin-right: -2.5rem;
            margin-bottom: 2.5rem !important;
            padding: 1.25rem 2.5rem !important;
        }
        
        /* Cards styling in Clean Minimalism */
        .card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .card-header {
            background-color: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 1.25rem 1.5rem !important;
        }
        .card-body {
            padding: 1.5rem !important;
        }
        
        /* Table Styling */
        .table {
            --bs-table-bg: transparent;
            --bs-table-hover-bg: rgba(79, 70, 229, 0.025);
            color: var(--text-dark);
        }
        .table > thead {
            background-color: #f8fafc;
        }
        .table th {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        /* Primary Buttons & Accents */
        .btn-primary {
            background-color: var(--primary-indigo) !important;
            border-color: var(--primary-indigo) !important;
            color: #ffffff !important;
            font-weight: 500;
            border-radius: 0.5rem;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(79, 70, 229, 0.1) !important;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: var(--primary-indigo-hover) !important;
            border-color: var(--primary-indigo-hover) !important;
            color: #ffffff !important;
        }
        .btn-outline-primary {
            color: var(--primary-indigo) !important;
            border-color: var(--primary-indigo) !important;
            font-weight: 500;
            border-radius: 0.5rem;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }
        .btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active {
            background-color: var(--primary-indigo-subtle) !important;
            border-color: var(--primary-indigo) !important;
            color: var(--primary-indigo) !important;
        }
        .btn-outline-secondary {
            border-color: #cbd5e1 !important;
            color: #475569 !important;
        }
        .btn-outline-secondary:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .btn-light {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #475569 !important;
        }
        .btn-light:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        
        /* Form Styling overrides */
        .form-control, .form-select {
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.55rem 1rem;
            font-size: 0.9rem;
            color: var(--text-dark);
            background-color: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(165, 180, 252, 0.25) !important;
            outline: none;
        }
        .form-label {
            color: #475569;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
        }
        
        /* Global utility color overrides */
        .text-primary {
            color: var(--primary-indigo) !important;
        }
        .bg-primary {
            background-color: var(--primary-indigo) !important;
        }
        
        /* Soft, high-contrast alert states */
        .bg-primary-subtle {
            background-color: #e0e7ff !important;
            color: var(--primary-indigo) !important;
        }
        .bg-success-subtle {
            background-color: #ecfdf5 !important;
            color: #059669 !important;
        }
        .bg-danger-subtle {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
        }
        .bg-warning-subtle {
            background-color: #fffbeb !important;
            color: #d97706 !important;
        }
        .bg-info-subtle {
            background-color: #f0f9ff !important;
            color: #0284c7 !important;
        }
        
        /* List Group enhancements */
        .list-group {
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .list-group-item {
            border-color: #e2e8f0;
            padding: 1.25rem 1.5rem;
            background-color: #ffffff;
        }
        .list-group-item-action:hover {
            background-color: #f8fafc;
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            border-radius: 0.375rem;
        }
        .badge.bg-primary {
            background-color: var(--primary-indigo-subtle) !important;
            color: var(--primary-indigo) !important;
            border: 1px solid rgba(79, 70, 229, 0.15) !important;
        }
        .badge.bg-success {
            background-color: #f0fdf4 !important;
            color: #166534 !important;
            border: 1px solid #bbf7d0 !important;
        }
        .badge.bg-warning {
            background-color: #fffbeb !important;
            color: #92400e !important;
            border: 1px solid #fde68a !important;
        }
        .badge.bg-danger {
            background-color: #fef2f2 !important;
            color: #991b1b !important;
            border: 1px solid #fca5a5 !important;
        }
        .badge.bg-secondary {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
        }
        .badge.bg-light {
            background-color: #f8fafc !important;
            color: #64748b !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        .hover-bg-light:hover {
            background-color: #f8fafc;
            cursor: pointer;
        }
        .transition-all {
            transition: all 0.2s ease-in-out;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid var(--border-light);
            }
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            .main-content > .d-flex.justify-content-between.align-items-center.mb-4.bg-white {
                margin-top: 0 !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding: 1rem !important;
                border-radius: 0.5rem !important;
                margin-bottom: 1.5rem !important;
                border: 1px solid #e2e8f0 !important;
            }
        }
    </style>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide === 'undefined') { document.write('<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"><\/script>'); }</script>
</head>
<body>

<div class="d-flex flex-column flex-lg-row">
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column p-0">
        <div class="p-4 d-flex align-items-center gap-2 border-bottom border-light-subtle">
            <div class="w-8 h-8 bg-indigo-600 rounded flex items-center justify-center text-white font-bold" style="background-color: var(--primary-indigo); width: 32px; height: 32px; border-radius: 6px;">TB</div>
            <div>
                <h5 class="m-0 fw-bold text-slate-900" style="font-family: 'Outfit', sans-serif;">TestBank <span class="font-normal text-slate-400" style="font-weight: 400;">LMS</span></h5>
                <small class="text-slate-400 font-sans" style="font-size: 0.75rem;">JoomLMS Edition</small>
            </div>
        </div>
        
        <div class="p-3 border-bottom border-light-subtle">
            <small class="text-uppercase fw-semibold text-slate-400" style="font-size: 0.65rem; letter-spacing: 0.05em; display: block;">Logged in as:</small>
            <div class="text-slate-800 fw-medium text-truncate mt-1" style="font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($currentUser['name']); ?></div>
            <span class="badge text-capitalize mt-1" style="background-color: var(--primary-indigo-subtle); color: var(--primary-indigo); border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($currentUser['role']); ?></span>
        </div>

        <?php
        $mailboxUnreadCount = 0;
        if (Auth::isLoggedIn()) {
            require_once __DIR__ . '/../../includes/Message.php';
            $mailboxUnreadCount = Message::unreadCount($currentUser['id']);
        }
        ?>

        <ul class="nav flex-column mt-3 flex-grow-1">
            <?php if (strpos($activeRoute, 'student/') !== false || !Auth::isInstructor()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'student/dashboard') !== false || empty($activeRoute)) ? 'active' : ''; ?>" href="index.php?route=student/dashboard">
                        <i data-lucide="book-open"></i> Student Portal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'student/gradebook') !== false) ? 'active' : ''; ?>" href="index.php?route=student/gradebook">
                        <i data-lucide="award"></i> My Grades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'student/certificates') !== false) ? 'active' : ''; ?>" href="index.php?route=student/certificates">
                        <i data-lucide="award"></i> My Certificates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'mailbox') !== false) ? 'active' : ''; ?> d-flex align-items-center justify-content-between" href="index.php?route=site/mailbox">
                        <span><i data-lucide="mail"></i> Mailbox</span>
                        <?php if ($mailboxUnreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill px-2 py-1" style="font-size: 0.7rem;"><?php echo $mailboxUnreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'admin/courses') !== false) ? 'active' : ''; ?>" href="index.php?route=admin/courses">
                        <i data-lucide="graduation-cap"></i> Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'mailbox') !== false) ? 'active' : ''; ?> d-flex align-items-center justify-content-between" href="index.php?route=site/mailbox">
                        <span><i data-lucide="mail"></i> Mailbox</span>
                        <?php if ($mailboxUnreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill px-2 py-1" style="font-size: 0.7rem;"><?php echo $mailboxUnreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeRoute === 'admin/exams' || $activeRoute === 'admin' || empty($activeRoute)) ? 'active' : ''; ?>" href="index.php?route=admin/exams">
                        <i data-lucide="file-spreadsheet"></i> Exam Builder
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'admin/questions') !== false) ? 'active' : ''; ?>" href="index.php?route=admin/questions">
                        <i data-lucide="help-circle"></i> Question Bank
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'admin/cases') !== false) ? 'active' : ''; ?>" href="index.php?route=admin/cases">
                        <i data-lucide="folder-open"></i> Case Studies
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeRoute === 'admin/categories' ? 'active' : ''; ?>" href="index.php?route=admin/categories">
                        <i data-lucide="folder-tree"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeRoute === 'admin/attempts' ? 'active' : ''; ?>" href="index.php?route=admin/attempts">
                        <i data-lucide="check-square"></i> Grading & Stats
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'admin/gradebook') !== false) ? 'active' : ''; ?>" href="index.php?route=admin/gradebook">
                        <i data-lucide="grid"></i> Gradebooks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'admin/certificates') !== false) ? 'active' : ''; ?>" href="index.php?route=admin/certificates">
                        <i data-lucide="award"></i> Certificates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($activeRoute, 'admin/analytics') !== false) ? 'active' : ''; ?>" href="index.php?route=admin/analytics">
                        <i data-lucide="line-chart"></i> Tracking & Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeRoute === 'admin/essay-grading' ? 'active' : ''; ?>" href="index.php?route=admin/essay-grading">
                        <i data-lucide="award"></i> Essay Grading
                    </a>
                </li>
                <?php if (Auth::isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeRoute === 'admin/users' ? 'active' : ''; ?>" href="index.php?route=admin/users">
                        <i data-lucide="users"></i> User Accounts
                    </a>
                </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="mt-auto p-3 border-top border-light-subtle">
            <a class="nav-link text-danger m-0 p-2" href="index.php?route=logout">
                <i data-lucide="log-out"></i> Sign Out
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content flex-grow-1">
        <!-- Top Navbar -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm border-0">
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-sm btn-outline-secondary d-lg-none me-2"><i data-lucide="menu"></i></a>
                <?php echo htmlspecialchars($pageTitle ?? 'Admin Dashboard'); ?>
            </h4>
            <div class="d-flex align-items-center gap-3">
                <?php if (Auth::isInstructor()): ?>
                    <?php if (strpos($activeRoute, 'student/') !== false): ?>
                        <a class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" href="index.php?route=admin/courses">
                            <i data-lucide="shield" size="16"></i> Go to Admin Portal
                        </a>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" href="index.php?route=student/dashboard">
                            <i data-lucide="book-open" size="16"></i> Go to Student Portal
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted small font-sans"><i data-lucide="shield" size="14"></i> Student Mode</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 p-3 mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="check-circle" class="text-success"></i>
                    <div><?php echo htmlspecialchars($_GET['success']); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 p-3 mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="alert-circle" class="text-danger"></i>
                    <div><?php echo htmlspecialchars($_GET['error']); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
