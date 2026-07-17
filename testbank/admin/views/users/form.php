<?php
/**
 * User Add/Edit Form View - Test Bank LMS
 */
$pageTitle = $title;
include __DIR__ . '/../layout_header.php';

$isEdit = isset($user) && isset($user['id']);
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <a href="index.php?route=admin/users&action=list" class="btn btn-sm btn-light border p-1 d-inline-flex align-items-center justify-content-center me-2" title="Back to List">
                    <i data-lucide="arrow-left" size="18"></i>
                </a>
                <i data-lucide="<?php echo $isEdit ? 'user-cog' : 'user-plus'; ?>" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold"><?php echo htmlspecialchars($title); ?></h5>
            </div>
            <div class="card-body p-4">
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-start gap-2">
                            <i data-lucide="alert-circle" class="text-danger mt-1" size="18"></i>
                            <div>
                                <span class="fw-semibold">Please correct the following errors:</span>
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?php echo htmlspecialchars($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?php echo $submitUrl; ?>" method="POST" class="needs-validation">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" required placeholder="e.g. Jane Doe" value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['name']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" required placeholder="e.g. jane@example.com" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label fw-medium">System Role <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">-- Select Role --</option>
                                <option value="admin" <?php echo (($formData['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                <option value="instructor" <?php echo (($formData['role'] ?? '') === 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                                <option value="student" <?php echo (($formData['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['role']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label fw-medium">Account Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" required>
                                <option value="active" <?php echo (($formData['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active (Enabled)</option>
                                <option value="disabled" <?php echo (($formData['status'] ?? '') === 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['status']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-medium">Password <?php echo $isEdit ? '(Leave blank to keep current)' : '<span class="text-danger">*</span>'; ?></label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?> placeholder="<?php echo $isEdit ? '••••••••' : 'At least 8 characters'; ?>">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php?route=admin/users&action=list" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                            <i data-lucide="save" size="18"></i> <?php echo $isEdit ? 'Save Changes' : 'Create User'; ?>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layout_footer.php';
?>
