<?php
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Auth.php';

Session::start();
Auth::requireLogin();

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - LMS Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0 p-5 bg-white">
                    <h1 class="fw-bold text-indigo mb-4" style="color: #4f46e5;">Student Portal</h1>
                    <hr>
                    <p class="fs-5">Welcome, <strong><?php echo htmlspecialchars($user['name'] ?? ''); ?></strong>!</p>
                    <p class="text-muted">You are logged in with the email: <code><?php echo htmlspecialchars($user['email'] ?? ''); ?></code></p>
                    <p class="text-muted">Your role is: <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($user['role'] ?? ''); ?></span></p>
                    <div class="mt-4">
                        <a href="controllers/AuthController.php?action=logout" class="btn btn-danger">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
