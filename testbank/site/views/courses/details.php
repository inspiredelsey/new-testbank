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

<a href="index.php?route=courses" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3">
    <i data-lucide="arrow-left" size="14"></i> Back to Catalog
</a>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <?php if (!empty($course['category_name'])): ?>
                    <span class="badge bg-primary-subtle text-primary fw-normal mb-2"><?php echo htmlspecialchars($course['category_name']); ?></span>
                <?php endif; ?>
                <h2 class="display-font fw-bold text-dark mb-3"><?php echo htmlspecialchars($course['title']); ?></h2>
                <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($course['description'] ?: 'No description provided.')); ?></p>

                <div class="d-flex flex-wrap gap-4 text-muted small border-top pt-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="user" size="16"></i>
                        <span>Instructor: <strong class="text-dark"><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unassigned'); ?></strong></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="users" size="16"></i>
                        <span><strong class="text-dark"><?php echo $enrolledCount; ?></strong> enrolled</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="clipboard-check" size="16"></i>
                        <span><strong class="text-dark"><?php echo $examCount; ?></strong> exam<?php echo $examCount !== 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="target" size="16"></i>
                        <span>Pass mark: <strong class="text-dark"><?php echo htmlspecialchars($course['pass_percentage']); ?>%</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($pathItems)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="route" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">What's Included</h5>
            </div>
            <div class="card-body">
                <ol class="list-group list-group-numbered list-group-flush">
                    <?php foreach ($pathItems as $item): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 border-0 px-0">
                            <i data-lucide="<?php echo $typeIcons[$item['item_type']] ?? 'circle'; ?>" size="16" class="text-muted"></i>
                            <span><?php echo htmlspecialchars($item['title'] ?: ucfirst($item['item_type'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="position: sticky; top: 1rem;">
            <div class="card-body text-center">
                <?php if ($isPublicVisitor): ?>
                    <div class="mb-3 d-inline-flex p-3 rounded-circle bg-primary-subtle text-primary">
                        <i data-lucide="graduation-cap" size="28"></i>
                    </div>
                    <p class="text-muted small mb-3">Log in or create a free account to enroll in this course.</p>
                    <a href="index.php?route=login" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-2">
                        Log In to Enroll <i data-lucide="arrow-right" size="16"></i>
                    </a>
                    <a href="index.php?route=register" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2">
                        Create an Account
                    </a>
                <?php elseif ($isEnrolled): ?>
                    <div class="mb-3 d-inline-flex p-3 rounded-circle bg-success-subtle text-success">
                        <i data-lucide="check-circle" size="28"></i>
                    </div>
                    <p class="text-muted small mb-3">You're enrolled in this course.</p>
                    <a href="index.php?route=student/course/view&id=<?php echo $course['id']; ?>"
                       class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                        Go to Course <i data-lucide="arrow-right" size="16"></i>
                    </a>
                <?php else: ?>
                    <div class="mb-3 d-inline-flex p-3 rounded-circle bg-primary-subtle text-primary">
                        <i data-lucide="graduation-cap" size="28"></i>
                    </div>
                    <p class="text-muted small mb-3">Enroll to get access to all course content and exams.</p>
                    <a href="index.php?route=course/enroll&id=<?php echo $course['id']; ?>"
                       class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                        Enroll Now <i data-lucide="arrow-right" size="16"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_footer.php';
}
?>
