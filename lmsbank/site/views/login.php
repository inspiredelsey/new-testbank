<?php
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Csrf.php';

Session::start();

// If already logged in, redirect home
if (Session::has('user_id')) {
    header("Location: /lmsbank/index.php");
    exit;
}

$csrfToken = Csrf::generateToken();
$error = Session::get('login_error');
Session::remove('login_error'); // Clear error after display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - LMS Bank</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-indigo: #4f46e5;
            --primary-indigo-hover: #4338ca;
            --bg-slate: #f8fafc;
        }
        body {
            background-color: var(--bg-slate);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }
        .btn-indigo {
            background-color: var(--primary-indigo);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            padding: 0.65rem 1rem;
            transition: background-color 0.2s ease;
        }
        .btn-indigo:hover {
            background-color: var(--primary-indigo-hover);
            color: #fff;
        }
        .form-control:focus {
            border-color: var(--primary-indigo);
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-3 shadow-sm p-3 mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-3 fw-bold" style="color: var(--primary-indigo);">LB</span>
                    </div>
                    <h3 class="fw-bold text-dark">Welcome back</h3>
                    <p class="text-muted small">Sign in to access your dashboard</p>
                </div>

                <div class="card login-card p-4 bg-white">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center small" role="alert">
                            <div>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="../controllers/AuthController.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-medium text-secondary">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-medium text-secondary">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-indigo">Sign In</button>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-4 text-muted small">
                    <p>&copy; 2026 LMS Bank. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
