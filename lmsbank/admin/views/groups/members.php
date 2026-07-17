<?php
/**
 * Manage Group Members View - LMS Bank Admin
 */

require_once __DIR__ . '/../../../../includes/Session.php';
require_once __DIR__ . '/../../../../includes/Csrf.php';

Session::start();

// Generate a CSRF token for add/remove submit actions on this load
$csrfToken = Csrf::generateToken();

// Note: $group, $members, $availableUsers, $successMsg, $errorMsg are passed from GroupController
$currentLoggedInUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members: <?php echo htmlspecialchars($group['name']); ?> - LMS Bank</title>
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

    <!-- Main Content Container -->
    <div class="container py-5">
        
        <!-- Back Link & Header -->
        <div class="mb-4">
            <a href="GroupController.php?action=list" class="text-decoration-none text-secondary d-inline-flex align-items-center small fw-medium">
                <i class="bi bi-arrow-left-short fs-4 me-1"></i> Back to Group List
            </a>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <span class="badge bg-light text-secondary border px-2 py-1 mb-2 font-monospace text-uppercase" style="font-size: 0.65rem;">Group Membership Manager</span>
                <h1 class="fw-bold text-dark h3 mb-1">Manage Members: <?php echo htmlspecialchars($group['name']); ?></h1>
                <?php if (!empty($group['description'])): ?>
                    <p class="text-muted mb-0 small mt-1"><?php echo htmlspecialchars($group['description']); ?></p>
                <?php endif; ?>
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

        <div class="row g-4">
            
            <!-- Left Side: Current Members Table -->
            <div class="col-lg-8">
                <div class="card card-custom bg-white overflow-hidden h-100 d-flex flex-column">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h2 class="card-title fw-bold text-dark h6 mb-0 d-flex align-items-center">
                            <i class="bi bi-people-fill text-primary me-2"></i> Current Group Members
                        </h2>
                        <span class="badge bg-secondary rounded-pill font-monospace small" style="font-size: 0.75rem;"><?php echo count($members); ?> Total</span>
                    </div>
                    
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($members)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted my-4">
                                                <i class="bi bi-people fs-1 d-block mb-3 text-secondary"></i>
                                                <p class="mb-0 fw-semibold">No members in this group yet.</p>
                                                <p class="small text-secondary mt-1">Use the panel on the right to search and add members.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($members as $m): 
                                        $roleBadgeClass = 'badge-role-student';
                                        if ($m['role'] === 'admin') $roleBadgeClass = 'badge-role-admin';
                                        if ($m['role'] === 'instructor') $roleBadgeClass = 'badge-role-instructor';
                                        $statusBadgeClass = ($m['status'] === 'active') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                                    ?>
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold me-3" style="width: 38px; height: 38px;">
                                                        <?php echo strtoupper(substr($m['name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <span class="d-block fw-semibold text-dark small"><?php echo htmlspecialchars($m['name']); ?></span>
                                                        <span class="text-muted small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($m['email']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo $roleBadgeClass; ?> text-capitalize px-2.5 py-1" style="font-size: 0.7rem;">
                                                    <?php echo htmlspecialchars($m['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $statusBadgeClass; ?> text-capitalize px-2 py-1 rounded" style="font-size: 0.7rem;">
                                                    <?php echo htmlspecialchars($m['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="GroupController.php?action=remove_member&group_id=<?php echo $group['id']; ?>&user_id=<?php echo $m['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-outline-danger btn-sm rounded px-2.5" title="Remove Member" onclick="return confirmRemove(event, '<?php echo htmlspecialchars($m['name']); ?>')">
                                                    <i class="bi bi-person-x-fill me-1"></i> Remove
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Add Member Control Panel -->
            <div class="col-lg-4">
                <div class="card card-custom p-4 bg-white sticky-lg-top" style="top: 2rem; z-index: 10;">
                    <h3 class="fw-bold text-dark h6 border-bottom pb-3 mb-4 d-flex align-items-center">
                        <i class="bi bi-person-plus-fill text-primary fs-5 me-2"></i> Add Member
                    </h3>

                    <?php if (empty($availableUsers)): ?>
                        <div class="alert alert-light text-center border p-4 mb-0" role="alert">
                            <i class="bi bi-emoji-smile fs-3 text-muted d-block mb-2"></i>
                            <span class="d-block fw-semibold text-dark small mb-1">All Set!</span>
                            <span class="text-muted small">All active users in LMS Bank are already members of this group.</span>
                        </div>
                    <?php else: ?>
                        <!-- Dynamic Text Filter to easily find members -->
                        <div class="mb-3">
                            <label for="userSearchInput" class="form-label small fw-medium text-secondary mb-1">Search User Directory</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-secondary" id="search-addon">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control bg-light border-start-0" id="userSearchInput" placeholder="Type to filter list..." aria-describedby="search-addon" onkeyup="filterUserDropdown()">
                            </div>
                        </div>

                        <form action="GroupController.php?action=add_member" method="POST">
                            <!-- CSRF Security Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <!-- Group Association -->
                            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">

                            <div class="mb-4">
                                <label for="user_id" class="form-label small fw-medium text-secondary mb-1">Select User <span class="text-danger">*</span></label>
                                <select class="form-select py-2" id="user_id" name="user_id" required size="10" style="height: 250px; font-size: 0.85rem;">
                                    <?php foreach ($availableUsers as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" data-name="<?php echo strtolower($user['name']); ?>" data-email="<?php echo strtolower($user['email']); ?>">
                                            <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>) [<?php echo strtoupper($user['role']); ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text small text-muted mt-1">
                                    Select an active student, instructor, or admin user to insert into this group.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-indigo w-full py-2.5 d-flex align-items-center justify-content-center shadow-sm">
                                <i class="bi bi-person-plus-fill me-2"></i> Add to Group
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmRemove(event, name) {
            if (!confirm(`Are you sure you want to remove "${name}" from this group?`)) {
                event.preventDefault();
                return false;
            }
            return true;
        }

        // Vanilla JS Filter for User Options selection
        function filterUserDropdown() {
            const input = document.getElementById('userSearchInput');
            const filter = input.value.toLowerCase();
            const select = document.getElementById('user_id');
            
            if (!select) return;
            
            const options = select.options;
            
            for (let i = 0; i < options.length; i++) {
                const opt = options[i];
                const name = opt.getAttribute('data-name') || '';
                const email = opt.getAttribute('data-email') || '';
                
                if (name.includes(filter) || email.includes(filter)) {
                    opt.style.display = "";
                } else {
                    opt.style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
