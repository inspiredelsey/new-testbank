<?php
/**
 * Users List View - LMS Bank Admin
 */

require_once __DIR__ . '/../../../../includes/Session.php';
require_once __DIR__ . '/../../../../includes/Csrf.php';

Session::start();

// Generate a CSRF token for toggle and delete actions on this page load
$csrfToken = Csrf::generateToken();

// Retrieve and clear flash messages
$successMsg = Session::get('success_msg');
$errorMsg = Session::get('error_msg');
Session::remove('success_msg');
Session::remove('error_msg');

// Get current logged-in user to check if we can disable/delete
$currentLoggedInUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - LMS Bank</title>
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
        .badge-role-admin {
            background-color: #fee2e2;
            color: #ef4444;
        }
        .badge-role-instructor {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        .badge-role-student {
            background-color: #f0fdf4;
            color: #16a34a;
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
                        <a class="nav-link text-dark fw-bold active border-bottom border-primary border-2" href="UserController.php?action=list">User Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-secondary fw-medium" href="/lmsbank/admin/controllers/GroupController.php?action=list">Group Management</a>
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
                <h1 class="fw-bold text-dark h3 mb-1">User Management</h1>
                <p class="text-muted mb-0 small">Create, edit, toggle, and manage LMS Bank users</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="UserController.php?action=create" class="btn btn-indigo px-4 py-2 d-inline-flex align-items-center shadow-sm">
                    <i class="bi bi-person-plus-fill me-2"></i> Add New User
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

        <!-- Search & Filter Controls -->
        <div class="card card-custom p-4 mb-4 bg-white">
            <form action="UserController.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="list">
                
                <div class="col-md-4">
                    <label for="role" class="form-label small fw-medium text-secondary">Filter by Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="instructor" <?php echo (isset($_GET['role']) && $_GET['role'] === 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                        <option value="student" <?php echo (isset($_GET['role']) && $_GET['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label small fw-medium text-secondary">Filter by Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="disabled" <?php echo (isset($_GET['status']) && $_GET['status'] === 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-indigo flex-grow-1 py-2">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <a href="UserController.php?action=list" class="btn btn-light py-2 border">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Users Table Card -->
        <div class="card card-custom overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered At</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-3 text-secondary"></i>
                                        <p class="mb-0 fw-semibold">No users found matching the filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <?php 
                                    $isSelf = ((int)$currentLoggedInUser['id'] === (int)$u['id']);
                                    
                                    // Set role badge classes
                                    $roleBadgeClass = 'badge-role-student';
                                    if ($u['role'] === 'admin') $roleBadgeClass = 'badge-role-admin';
                                    if ($u['role'] === 'instructor') $roleBadgeClass = 'badge-role-instructor';
                                    
                                    // Set status badge classes
                                    $statusBadgeClass = ($u['status'] === 'active') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold me-3" style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <span class="d-block fw-semibold text-dark">
                                                    <?php echo htmlspecialchars($u['name']); ?>
                                                    <?php if ($isSelf): ?>
                                                        <span class="badge bg-dark-subtle text-dark ms-1 font-monospace small" style="font-size: 0.65rem;">YOU</span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="text-muted small"><?php echo htmlspecialchars($u['email']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $roleBadgeClass; ?> text-capitalize px-3 py-1.5">
                                            <?php echo htmlspecialchars($u['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusBadgeClass; ?> text-capitalize px-2.5 py-1.5 border border-opacity-25 rounded">
                                            <?php echo htmlspecialchars($u['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary small">
                                        <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($u['created_at']))); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-2">
                                            <!-- Edit button -->
                                            <a href="UserController.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Profile">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            
                                            <!-- Enable / Disable toggle -->
                                            <?php if ($isSelf): ?>
                                                <button class="btn btn-outline-secondary btn-sm disabled" title="You cannot disable your own account" disabled>
                                                    <i class="bi bi-toggle-on text-muted"></i>
                                                </button>
                                            <?php else: ?>
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <a href="UserController.php?action=toggle&id=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-outline-warning btn-sm" title="Disable User" onclick="return confirmToggle(event, '<?php echo htmlspecialchars($u['name']); ?>', 'disable')">
                                                        <i class="bi bi-toggle-on text-success"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="UserController.php?action=toggle&id=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-outline-secondary btn-sm" title="Enable User" onclick="return confirmToggle(event, '<?php echo htmlspecialchars($u['name']); ?>', 'enable')">
                                                        <i class="bi bi-toggle-off text-muted"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Soft Delete -->
                                            <?php if ($isSelf): ?>
                                                <button class="btn btn-outline-danger btn-sm disabled" title="You cannot delete your own account" disabled>
                                                    <i class="bi bi-trash-fill text-muted"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="UserController.php?action=delete&id=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-outline-danger btn-sm" title="Soft-delete (Disable) User" onclick="return confirmDelete(event, '<?php echo htmlspecialchars($u['name']); ?>')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            <?php endif; ?>
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
        function confirmToggle(event, name, action) {
            if (!confirm(`Are you sure you want to ${action} the user "${name}"?`)) {
                event.preventDefault();
                return false;
            }
            return true;
        }

        function confirmDelete(event, name) {
            if (!confirm(`Are you sure you want to soft-delete/disable "${name}"? This action will set their status to "disabled".`)) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
