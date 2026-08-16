<?php
$pageTitle = 'Profile Information';
include __DIR__ . '/../layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h2 class="display-font fw-bold text-dark mb-4">Profile Information</h2>

        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item"><a class="nav-link active" href="index.php?route=account/profile">Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?route=account/settings">Settings</a></li>
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
            <div class="card-body p-4">
                <form method="POST" action="index.php?route=account/profile">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Email Address</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>

                    <div class="mb-4 p-3 bg-light rounded-3">
                        <span class="text-muted small">Role</span>
                        <span class="fw-semibold small text-capitalize float-end"><?php echo htmlspecialchars($user['role']); ?></span>
                        <div class="clearfix"></div>
                        <p class="text-muted mb-0 mt-1" style="font-size: 0.75rem;">Your role is managed by an administrator and can't be changed here.</p>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
