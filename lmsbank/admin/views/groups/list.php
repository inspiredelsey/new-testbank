<?php
/**
 * Groups List View - LMS Bank Admin
 */

require_once __DIR__ . '/../../../../includes/Session.php';
require_once __DIR__ . '/../../../../includes/Csrf.php';

Session::start();

// Generate a CSRF token for delete actions on this page load
$csrfToken = Csrf::generateToken();

// Retrieve and clear flash messages
$successMsg = Session::get('success_msg');
$errorMsg = Session::get('error_msg');
Session::remove('success_msg');
Session::remove('error_msg');

// Get current logged-in user to check roles
$currentLoggedInUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Management - LMS Bank</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
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
        .navbar-brand-custom {
            font-weight: 700;
            color: var(--primary-indigo) !important;
        }
        .card-custom {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        .btn-indigo {
            background-color: var(--primary-indigo);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        .btn-indigo:hover {
            background-color: var(--primary-indigo-hover);
            color: #fff;
        }
        .table-custom th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #64748b;
            background-color: #f1f5f9;
        }
        .table-custom td {
            vertical-align: middle;
        }
    </style>
</head>
<body>

    <!-- Admin Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand navbar-brand-custom d-flex align-items-center" href="/lmsbank/admin/index.php">
                <span class="fs-4 fw-bold me-2" style="color: var(--primary-indigo);">LB</span>
                <span class="text-dark">LMS Bank</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link text-secondary fw-medium" href="/lmsbank/admin/index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-secondary fw-medium" href="/lmsbank/admin/controllers/UserController.php?action=list">User Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold active border-bottom border-primary border-2" href="/lmsbank/admin/controllers/GroupController.php?action=list">Group Management</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="me-3 text-end d-none d-md-block">
                        <span class="d-block text-dark fw-semibold small"><?php echo htmlspecialchars($currentLoggedInUser['name'] ?? ''); ?></span>
                        <span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;"><?php echo htmlspecialchars($currentLoggedInUser['role'] ?? ''); ?></span>
                    </div>
                    <a href="/lmsbank/site/controllers/AuthController.php?action=logout" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        
        <!-- Welcome Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h1 class="fw-bold text-dark h3 mb-1">Group Management</h1>
                <p class="text-muted mb-0 small">Create, edit, and organize learning groups and their members</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="GroupController.php?action=create" class="btn btn-indigo px-4 py-2 d-inline-flex align-items-center shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i> Create New Group
                </a>
            </div>
        </div>

        <!-- Success & Error Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show card-custom border-0 border-start border-success border-4 p-3 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                    <div>
                        <strong class="d-block text-dark small">Success</strong>
                        <span class="text-secondary small"><?php echo htmlspecialchars($successMsg); ?></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show card-custom border-0 border-start border-danger border-4 p-3 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-3"></i>
                    <div>
                        <strong class="d-block text-dark small">Action Blocked</strong>
                        <span class="text-secondary small"><?php echo htmlspecialchars($errorMsg); ?></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Groups Table Card -->
        <div class="card card-custom overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 25%;">Group Name</th>
                            <th style="width: 45%;">Description</th>
                            <th style="width: 15%;">Members Count</th>
                            <th class="text-end pe-4" style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($groups)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-collection fs-1 d-block mb-3 text-secondary"></i>
                                        <p class="mb-0 fw-semibold">No groups found in the database.</p>
                                        <p class="small text-secondary mt-1">Get started by creating your very first learning group!</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($groups as $g): ?>
                                <tr>
                                    <td class="ps-4 py-3 fw-semibold text-dark">
                                        <?php echo htmlspecialchars($g['name']); ?>
                                    </td>
                                    <td class="text-secondary small">
                                        <?php echo !empty($g['description']) ? htmlspecialchars($g['description']) : '<em class="text-muted small">No description provided</em>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded bg-indigo-subtle text-primary border border-primary border-opacity-10 px-3 py-1.5 fw-bold" style="background-color: #f5f3ff; color: var(--primary-indigo);">
                                            <i class="bi bi-people-fill me-1"></i> <?php echo (int)$g['member_count']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-2">
                                            <!-- Manage Members button -->
                                            <a href="GroupController.php?action=members&id=<?php echo $g['id']; ?>" class="btn btn-outline-primary btn-sm px-3" title="Manage Members">
                                                <i class="bi bi-people-fill me-1"></i> Members
                                            </a>
                                            
                                            <!-- Edit button -->
                                            <a href="GroupController.php?action=edit&id=<?php echo $g['id']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Group">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            
                                            <!-- Delete button -->
                                            <a href="GroupController.php?action=delete&id=<?php echo $g['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-outline-danger btn-sm" title="Delete Group" onclick="return confirmDelete(event, '<?php echo htmlspecialchars($g['name']); ?>')">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(event, name) {
            if (!confirm(`Are you sure you want to delete the group "${name}"? This action cannot be undone.`)) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
