<?php
/**
 * Users List View - Test Bank LMS
 */
$pageTitle = 'User Management';
include __DIR__ . '/../layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="users" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">All User Accounts</h5>
                </div>
                <a href="index.php?route=admin/users&action=create" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                    <i data-lucide="plus" size="16"></i> Add User
                </a>
            </div>
            <div class="card-body p-4">
                <!-- Filters -->
                <form method="GET" action="index.php" class="row g-3 align-items-end mb-4">
                    <input type="hidden" name="route" value="admin/users">
                    <input type="hidden" name="action" value="list">
                    
                    <div class="col-md-4">
                        <label for="role" class="form-label fw-medium">Filter by Role</label>
                        <select name="role" id="role" class="form-select">
                            <option value="">-- All Roles --</option>
                            <option value="admin" <?php echo ($roleFilter === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            <option value="instructor" <?php echo ($roleFilter === 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                            <option value="student" <?php echo ($roleFilter === 'student') ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label fw-medium">Filter by Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">-- All Statuses --</option>
                            <option value="active" <?php echo ($statusFilter === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="disabled" <?php echo ($statusFilter === 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center gap-1">
                            <i data-lucide="filter" size="16"></i> Apply Filters
                        </button>
                        <a href="index.php?route=admin/users" class="btn btn-light border d-flex align-items-center justify-content-center gap-1" title="Reset Filters">
                            <i data-lucide="rotate-ccw" size="16"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th style="width: 150px;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i data-lucide="user-x" size="40" class="d-block mx-auto mb-2 text-slate-300"></i>
                                        No user accounts found matching your filters.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="fw-medium text-slate-500">#<?php echo $u['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 0.9rem; background-color: var(--primary-indigo);">
                                                    <?php echo strtoupper(substr($u['name'] ?? 'U', 0, 2)); ?>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-semibold text-slate-800"><?php echo htmlspecialchars($u['name'] ?? ''); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-slate-600">
                                            <code><?php echo htmlspecialchars($u['email'] ?? ''); ?></code>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php elseif ($u['role'] === 'instructor'): ?>
                                                <span class="badge bg-primary">Instructor</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Student</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($u['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="index.php?route=admin/users&action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit User">
                                                    <i data-lucide="edit-3" size="14"></i>
                                                </a>
                                                
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <a href="index.php?route=admin/users&action=toggle&id=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-sm btn-outline-warning" title="Disable User" onclick="return confirmToggle(event, '<?php echo htmlspecialchars($u['name'] ?? ''); ?>', 'disable')">
                                                        <i data-lucide="user-minus" size="14"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="index.php?route=admin/users&action=toggle&id=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-sm btn-outline-success" title="Enable User" onclick="return confirmToggle(event, '<?php echo htmlspecialchars($u['name'] ?? ''); ?>', 'enable')">
                                                        <i data-lucide="user-check" size="14"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <a href="index.php?route=admin/users&action=delete&id=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-sm btn-outline-danger" title="Delete User" onclick="return confirmDelete(event, '<?php echo htmlspecialchars($u['name'] ?? ''); ?>')">
                                                    <i data-lucide="trash-2" size="14"></i>
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
    </div>
</div>

<script>
function confirmToggle(e, name, action) {
    if (!confirm('Are you sure you want to ' + action + ' the user "' + name + '"?')) {
        e.preventDefault();
        return false;
    }
    return true;
}

function confirmDelete(e, name) {
    if (!confirm('Are you sure you want to delete the user "' + name + '"? This will disable their login access.')) {
        e.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php
include __DIR__ . '/../layout_footer.php';
?>
