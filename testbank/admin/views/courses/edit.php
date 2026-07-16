<?php
$pageTitle = 'Edit Course';
include __DIR__ . '/../layout_header.php';
?>

<div class="mb-4">
    <a href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>" class="text-decoration-none text-muted small d-flex align-items-center gap-1">
        <i data-lucide="arrow-left" size="14"></i> Back to Course Dashboard
    </a>
    <h3 class="display-font fw-bold text-dark mt-2 mb-1">Edit Course Details</h3>
    <p class="text-muted small">Update course meta information, assigned instructor, or status.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i data-lucide="alert-circle" size="18"></i>
                    <div class="small fw-semibold"><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form action="index.php?route=admin/courses&action=edit&id=<?php echo $course['id']; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                <div class="mb-4">
                    <label for="title" class="form-label fw-semibold text-muted small">Course Title</label>
                    <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($course['title']); ?>">
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold text-muted small">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                </div>

                <div class="row mb-4">
                    <?php if (Auth::hasRole(['admin'])): ?>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="instructor_id" class="form-label fw-semibold text-muted small">Assign Instructor</label>
                            <select class="form-select" id="instructor_id" name="instructor_id" required>
                                <option value="">-- Choose Instructor --</option>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?php echo $inst['id']; ?>" <?php echo ($course['instructor_id'] == $inst['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($inst['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-semibold text-muted small">Publishing Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" <?php echo ($course['status'] === 'draft') ? 'selected' : ''; ?>>Draft (Hidden from students)</option>
                            <option value="published" <?php echo ($course['status'] === 'published') ? 'selected' : ''; ?>>Published (Visible to enrolled students)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i data-lucide="check" size="18"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
