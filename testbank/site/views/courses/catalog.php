<?php
$pageTitle = 'Course Catalog';
$isPublicVisitor = empty($user);
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_header.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h2 class="display-font fw-bold text-dark mb-1">Course Catalog</h2>
        <p class="text-muted mb-0">Browse available courses and enroll to get started.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="d-flex gap-2">
            <input type="hidden" name="route" value="courses">
            <input type="text" name="search" class="form-control" placeholder="Search courses..."
                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-1 px-3">
                <i data-lucide="search" size="16"></i> Search
            </button>
        </form>
    </div>
</div>

<?php if (empty($courses)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i data-lucide="book-open" class="text-muted d-block mx-auto mb-3" size="40"></i>
            <h5 class="fw-semibold text-dark">No courses found</h5>
            <p class="text-muted mb-0">
                <?php echo !empty($search) ? 'Try a different search term.' : 'Check back soon — new courses are added regularly.'; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($courses as $course): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <?php if (!empty($course['category_name'])): ?>
                                <span class="badge bg-primary-subtle text-primary fw-normal"><?php echo htmlspecialchars($course['category_name']); ?></span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <?php if (!empty($course['is_enrolled'])): ?>
                                <span class="badge bg-success-subtle text-success fw-normal d-flex align-items-center gap-1">
                                    <i data-lucide="check-circle" size="12"></i> Enrolled
                                </span>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($course['title']); ?></h5>
                        <p class="text-muted small mb-3 flex-grow-1">
                            <?php
                                $desc = $course['description'] ?? '';
                                echo htmlspecialchars(mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '...' : $desc);
                            ?>
                        </p>
                        <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
                            <i data-lucide="user" size="14"></i>
                            <span><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unassigned'); ?></span>
                        </div>
                        <a href="index.php?route=course/details&id=<?php echo $course['id']; ?>"
                           class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-1">
                            <?php echo !empty($course['is_enrolled']) ? 'View Course' : 'View Details'; ?>
                            <i data-lucide="arrow-right" size="14"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_footer.php';
}
?>