<?php
/**
 * Shared User Create/Edit Form View - LMS Bank Admin
 */

require_once __DIR__ . '/../../../../includes/Session.php';
require_once __DIR__ . '/../../../../includes/Csrf.php';

Session::start();

// Generate a CSRF token for the form submit action
$csrfToken = Csrf::generateToken();

// Determine mode (create vs. edit)
$isEditMode = (isset($_GET['id']) && $action === 'edit');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Retrieve errors and form data passed from the controller session
// Note: $errors and $formData are defined by UserController before requiring this view,
// but let's fall back gracefully if loaded directly or via unexpected routes.
$errors = $errors ?? [];
$formData = $formData ?? [];

// Helper function to extract a value safely from the form data
function getFormValue($data, $key, $default = '') {
    return isset($data[$key]) ? htmlspecialchars($data[$key]) : $default;
}

// Get current logged-in user for the navigation header
$currentLoggedInUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEditMode ? 'Edit User' : 'Add User'; ?> - LMS Bank</title>
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
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-indigo);
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
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

    <!-- Main Content Container -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Back Link -->
                <div class="mb-4">
                    <a href="UserController.php?action=list" class="text-decoration-none text-secondary d-inline-flex align-items-center small fw-medium">
                        <i class="bi bi-arrow-left-short fs-4 me-1"></i> Back to User List
                    </a>
                </div>

                <!-- Form Header -->
                <div class="mb-4">
                    <h1 class="fw-bold text-dark h3 mb-1"><?php echo htmlspecialchars($title); ?></h1>
                    <p class="text-muted mb-0 small">
                        <?php echo $isEditMode ? 'Modify account details, change role or password.' : 'Register a new admin, instructor, or student user.'; ?>
                    </p>
                </div>

                <!-- Main Form Card -->
                <div class="card card-custom p-4 p-md-5 bg-white">
                    
                    <!-- Global Error Message Alert -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger card-custom border-0 border-start border-danger border-4 p-3 mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-3"></i>
                                <div>
                                    <strong class="d-block text-dark small">Validation Errors</strong>
                                    <span class="text-secondary small">Please fix the highlighted errors below before submitting the form.</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($submitUrl); ?>" method="POST" autocomplete="off">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- Input: Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" placeholder="John Doe" value="<?php echo getFormValue($formData, 'name'); ?>" required maxlength="150">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback small"><?php echo htmlspecialchars($errors['name']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Input: Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" placeholder="johndoe@example.com" value="<?php echo getFormValue($formData, 'email'); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback small"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row mb-4">
                            <!-- Input: Role -->
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="role" class="form-label small fw-semibold text-secondary">Role <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>" id="role" name="role" required>
                                    <option value="" disabled <?php echo empty(getFormValue($formData, 'role')) ? 'selected' : ''; ?>>Select a Role</option>
                                    <option value="student" <?php echo getFormValue($formData, 'role') === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="instructor" <?php echo getFormValue($formData, 'role') === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                    <option value="admin" <?php echo getFormValue($formData, 'role') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <?php if (isset($errors['role'])): ?>
                                    <div class="invalid-feedback small"><?php echo htmlspecialchars($errors['role']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Input: Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label small fw-semibold text-secondary">Account Status <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" id="status" name="status" required>
                                    <option value="active" <?php echo getFormValue($formData, 'status', 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="disabled" <?php echo getFormValue($formData, 'status') === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback small"><?php echo htmlspecialchars($errors['status']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Input: Password -->
                        <div class="mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label small fw-semibold text-secondary mb-0">
                                    Password <?php echo !$isEditMode ? '<span class="text-danger">*</span>' : ''; ?>
                                </label>
                                <?php if ($isEditMode): ?>
                                    <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 0.65rem;">Leave blank to keep current password</span>
                                <?php endif; ?>
                            </div>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="<?php echo $isEditMode ? '••••••••' : 'Minimum 8 characters'; ?>" <?php echo !$isEditMode ? 'required' : ''; ?> minlength="8" autocomplete="new-password">
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback small"><?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Form Submission Buttons -->
                        <div class="d-flex flex-column flex-md-row gap-3 pt-3 border-top justify-content-end">
                            <a href="UserController.php?action=list" class="btn btn-light px-4 py-2 border order-2 order-md-1">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-indigo px-5 py-2 order-1 order-md-2 d-inline-flex align-items-center justify-content-center shadow-sm">
                                <i class="bi bi-check-circle-fill me-2"></i> Save User Profile
                            </button>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
