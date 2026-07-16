<?php
$pageTitle = 'Create Course';
include __DIR__ . '/../layout_header.php';
?>

<div class="mb-4">
    <a href="index.php?route=admin/courses" class="text-decoration-none text-muted small d-flex align-items-center gap-1">
        <i data-lucide="arrow-left" size="14"></i> Back to Courses
    </a>
    <h3 class="display-font fw-bold text-dark mt-2 mb-1">Create New Course</h3>
    <p class="text-muted small">Establish a new course space to distribute documents, links, and design learning paths.</p>
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

            <form action="index.php?route=admin/courses&action=create" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                <div class="mb-4">
                    <label for="title" class="form-label fw-semibold text-muted small">Course Title</label>
                    <input type="text" class="form-control" id="title" name="title" required placeholder="e.g. NCLEX-RN Comprehensive Review Course 2026">
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold text-muted small">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Provide a detailed syllabus or course description..."></textarea>
                </div>

                <div class="row mb-4">
                    <?php if (Auth::hasRole(['admin'])): ?>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="instructor_id" class="form-label fw-semibold text-muted small">Assign Instructor</label>
                            <select class="form-select" id="instructor_id" name="instructor_id" required>
                                <option value="">-- Choose Instructor --</option>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?php echo $inst['id']; ?>" <?php echo (Session::get('user_id') == $inst['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($inst['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-semibold text-muted small">Publishing Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" selected>Draft (Hidden from students)</option>
                            <option value="published">Published (Visible to enrolled students)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="index.php?route=admin/courses" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i data-lucide="check" size="18"></i> Create Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
