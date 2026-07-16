<?php
$pageTitle = 'Courses';
include __DIR__ . '/../layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="display-font fw-bold text-dark mb-1">Courses</h3>
        <p class="text-muted small mb-0">Manage your course list, student enrollments, documents, links, and learning paths.</p>
    </div>
    <a href="index.php?route=admin/courses&action=create" class="btn btn-primary d-flex align-items-center gap-2">
        <i data-lucide="plus-circle" size="18"></i> Create Course
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($courses)): ?>
            <div class="text-center py-5">
                <i data-lucide="book-open" class="text-muted d-block mx-auto mb-3" size="48"></i>
                <h5 class="fw-bold text-dark">No Courses Found</h5>
                <p class="text-muted small">Get started by creating your first course.</p>
                <a href="index.php?route=admin/courses&action=create" class="btn btn-primary btn-sm mt-2">
                    <i data-lucide="plus" size="16" class="me-1"></i> Create First Course
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Course Details</th>
                            <?php if (Auth::hasRole(['admin'])): ?>
                                <th>Instructor</th>
                            <?php endif; ?>
                            <th>Enrollments</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2.5 bg-primary-subtle text-primary rounded-3">
                                            <i data-lucide="graduation-cap" size="24"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">
                                                <a href="index.php?route=admin/courses&action=view&id=<?php echo $c['id']; ?>" class="text-dark text-decoration-none hover-text-primary">
                                                    <?php echo htmlspecialchars($c['title']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted text-truncate d-block" style="max-width: 350px;">
                                                <?php echo htmlspecialchars($c['description'] ?: 'No description provided.'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <?php if (Auth::hasRole(['admin'])): ?>
                                    <td>
                                        <span class="text-dark fw-medium small"><i data-lucide="user" size="12" class="me-1 text-muted"></i><?php echo htmlspecialchars($c['instructor_name'] ?? 'Unassigned'); ?></span>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge bg-light text-dark border font-sans px-2.5 py-1.5 rounded-pill">
                                        <i data-lucide="users" size="12" class="me-1 text-primary"></i>
                                        <strong><?php echo $c['enrollment_count']; ?></strong> enrolled
                                    </span>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'published'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-sans px-2.5 py-1.5 rounded-pill"><i data-lucide="check-circle" size="12" class="me-1"></i>Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-sans px-2.5 py-1.5 rounded-pill"><i data-lucide="edit" size="12" class="me-1"></i>Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a href="index.php?route=admin/courses&action=view&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                                            <i data-lucide="settings" size="14"></i> Manage
                                        </a>
                                        <a href="index.php?route=admin/courses&action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-light border d-flex align-items-center justify-content-center p-1.5 rounded-3 hover-text-primary" title="Edit course">
                                            <i data-lucide="edit-3" size="14"></i>
                                        </a>
                                        <form action="index.php?route=admin/courses&action=delete&id=<?php echo $c['id']; ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this course and all its content? This cannot be undone.');" class="m-0 d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-sm btn-light border text-danger d-flex align-items-center justify-content-center p-1.5 rounded-3" title="Delete course">
                                                <i data-lucide="trash-2" size="14"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
