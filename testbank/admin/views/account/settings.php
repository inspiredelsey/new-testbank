<?php
$pageTitle = 'Account Settings';
include __DIR__ . '/../layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h2 class="display-font fw-bold text-dark mb-4">Account Settings</h2>

        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item"><a class="nav-link" href="index.php?route=account/profile">Profile</a></li>
            <li class="nav-item"><a class="nav-link active" href="index.php?route=account/settings">Settings</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?route=account/preferences">Preferences</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?route=account/billing">Billing &amp; Orders</a></li>
        </ul>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                <i data-lucide="alert-circle" size="20"></i>
                <div class="small fw-semibold"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                <i data-lucide="check-circle" size="20"></i>
                <div class="small fw-semibold"><?php echo htmlspecialchars($success); ?></div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold">Change Password</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="index.php?route=account/settings">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                        <small class="text-muted" style="font-size: 0.75rem;">At least 8 characters.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
