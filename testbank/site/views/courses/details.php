<?php
$pageTitle = htmlspecialchars($course['title']);
$isPublicVisitor = empty($user);
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_header.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}

$typeIcons = [
    'document' => 'file-text',
    'link' => 'link',
    'quiz' => 'clipboard-check',
];
?>

<?php if ($isPublicVisitor): ?><div class="container my-4"><?php endif; ?>

<a href="index.php?route=courses" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3 hover-primary">
    <i data-lucide="arrow-left" size="14"></i> Back to Course Catalog
</a>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <?php if (!empty($course['category_name'])): ?>
                    <span class="badge bg-primary-subtle text-primary fw-semibold mb-2 px-3 py-1.5 rounded-pill"><?php echo htmlspecialchars($course['category_name']); ?></span>
                <?php endif; ?>
                <h1 class="display-font fw-bold text-dark mb-3 fs-2"><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="text-muted lead fs-6 mb-4" style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($course['description'] ?: 'Complete learning path and practice test bank for this subject.')); ?></p>

                <div class="d-flex flex-wrap gap-4 text-muted small border-top pt-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="user" size="16" class="text-primary"></i>
                        <span>Instructor: <strong class="text-dark"><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unassigned'); ?></strong></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="users" size="16" class="text-primary"></i>
                        <span><strong class="text-dark"><?php echo $enrolledCount; ?></strong> enrolled student<?php echo $enrolledCount !== 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="clipboard-check" size="16" class="text-primary"></i>
                        <span><strong class="text-dark"><?php echo $examCount; ?></strong> practice exam<?php echo $examCount !== 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="target" size="16" class="text-primary"></i>
                        <span>Passing score: <strong class="text-dark"><?php echo htmlspecialchars($course['pass_percentage']); ?>%</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($pathItems)): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="route" class="text-primary" size="20"></i>
                <h5 class="mb-0 fw-bold text-dark display-font">Course Curriculum & Learning Modules</h5>
            </div>
            <div class="card-body p-4">
                <ol class="list-group list-group-numbered list-group-flush">
                    <?php foreach ($pathItems as $item): ?>
                        <li class="list-group-item d-flex align-items-center gap-3 border-0 px-0 py-2.5">
                            <span class="p-2 bg-light rounded-2 text-primary d-inline-flex">
                                <i data-lucide="<?php echo $typeIcons[$item['item_type']] ?? 'circle'; ?>" size="16"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($item['title'] ?: ucfirst($item['item_type'])); ?></h6>
                                <span class="text-muted small text-capitalize"><?php echo htmlspecialchars($item['item_type']); ?> module</span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4" style="position: sticky; top: 5rem;">
            <div class="card-body p-4 text-center">
                <?php if ($isPublicVisitor): ?>
                    <div class="mb-3 d-inline-flex p-3.5 rounded-circle bg-primary-subtle text-primary">
                        <i data-lucide="graduation-cap" size="32"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Enroll in This Course</h5>
                    <p class="text-muted small mb-4">Get instant access to all learning materials, practice test banks, and automated grading.</p>
                    <a href="index.php?route=login" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-2 py-2.5">
                        <span>Log In to Enroll</span>
                        <i data-lucide="arrow-right" size="16"></i>
                    </a>
                    <a href="index.php?route=register" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2.5">
                        <span>Create Free Account</span>
                    </a>
                <?php elseif ($isEnrolled): ?>
                    <div class="mb-3 d-inline-flex p-3.5 rounded-circle bg-success-subtle text-success">
                        <i data-lucide="check-circle" size="32"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">You're Enrolled!</h5>
                    <p class="text-muted small mb-4">You have active access to all learning modules and practice exams.</p>
                    <a href="index.php?route=student/course/view&id=<?php echo $course['id']; ?>"
                       class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2 py-2.5">
                        <span>Go to Course Content</span>
                        <i data-lucide="arrow-right" size="16"></i>
                    </a>
                <?php else: ?>
                    <div class="mb-3 d-inline-flex p-3.5 rounded-circle bg-primary-subtle text-primary">
                        <i data-lucide="graduation-cap" size="32"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Start Learning Today</h5>
                    <p class="text-muted small mb-4">Enroll now to access all course modules and test banks.</p>
                    <a href="index.php?route=course/enroll&id=<?php echo $course['id']; ?>"
                       class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2.5">
                        <span>Enroll Now (Free)</span>
                        <i data-lucide="arrow-right" size="16"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isPublicVisitor): ?></div><?php endif; ?>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_footer.php';
}
?>
