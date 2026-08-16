<?php
$pageTitle = 'Preferences';
include __DIR__ . '/../layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h2 class="display-font fw-bold text-dark mb-4">Preferences</h2>

        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item"><a class="nav-link" href="index.php?route=account/profile">Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?route=account/settings">Settings</a></li>
            <li class="nav-item"><a class="nav-link active" href="index.php?route=account/preferences">Preferences</a></li>
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
                <h6 class="mb-0 fw-semibold">Notifications &amp; Timezone</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="index.php?route=account/preferences">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="emailNotifications" name="email_notifications"
                               <?php echo !empty($user['email_notifications']) ? 'checked' : ''; ?>>
                        <label class="form-check-label small fw-semibold" for="emailNotifications">
                            Email notifications
                        </label>
                        <p class="text-muted mb-0 mt-1" style="font-size: 0.75rem;">Receive email updates about exam results, certificates, and course activity.</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Timezone</label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezoneOptions as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($user['timezone'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.75rem;">Used for displaying exam dates and deadlines.</small>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
