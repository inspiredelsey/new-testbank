<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Test Bank LMS</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide === 'undefined') { document.write('<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"><\/script>'); }</script>
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
            background-color: var(--bg-slate);
            color: var(--text-dark);
            font-family: 'Inter', sans-serif;
        }
        .display-font {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.025em;
        }
        .login-card {
            background-color: #ffffff;
            border: 1px solid var(--border-light) !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
        }
        .form-control, .form-select {
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.55rem 1rem;
            font-size: 0.9rem;
            color: var(--text-dark);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(165, 180, 252, 0.25) !important;
            outline: none;
        }
        .btn-primary {
            background-color: var(--primary-indigo) !important;
            border-color: var(--primary-indigo) !important;
            color: #ffffff !important;
            font-weight: 500;
            border-radius: 0.5rem;
            padding: 0.6rem 1.25rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(79, 70, 229, 0.1) !important;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: var(--primary-indigo-hover) !important;
            border-color: var(--primary-indigo-hover) !important;
        }
        .bg-primary {
            background-color: var(--primary-indigo) !important;
        }
        .text-primary {
            color: var(--primary-indigo) !important;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5">

<div class="container" style="max-width: 480px;">
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 bg-primary text-white rounded-4 mb-3 shadow-sm">
            <i data-lucide="user-plus" size="36"></i>
        </div>
        <h2 class="display-font fw-bold text-dark mb-1">Create Account</h2>
        <p class="text-muted small">Create your student account to get started</p>
    </div>

    <div class="card login-card bg-white p-4">
        <div class="card-body">
            <h4 class="fw-bold text-dark mb-4 text-center">Sign Up</h4>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i data-lucide="alert-circle" size="20"></i>
                    <div class="small fw-semibold"><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form action="index.php?route=register" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold text-muted small">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i data-lucide="user" size="16" class="text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="name" name="name" required placeholder="John Doe">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold text-muted small">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i data-lucide="mail" size="16" class="text-muted"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" required placeholder="john@example.com">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold text-muted small">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i data-lucide="lock" size="16" class="text-muted"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
                    Create Account <i data-lucide="check" size="18"></i>
                </button>
            </form>
        </div>
    </div>

    <p class="text-center mt-4 text-muted small">
        Already have an account? <a href="index.php?route=login" class="fw-bold text-primary text-decoration-none">Log In</a>
    </p>
    <p class="text-center mt-2 text-muted small" style="font-size: 0.8rem;">
        Instructor accounts are created by an administrator, not through self-registration.
    </p>
</div>

<script>
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }
</script>
</body>
</html>
