<?php
/**
 * Admin Gradebook Course List View
 */
$pageTitle = 'Gradebook Management';
include __DIR__ . '/../layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="display-font fw-bold text-dark mb-1">Gradebooks</h3>
                <p class="text-muted small mb-0">Select a course to manage gradebook items, weights, and view/enter student grades.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="book-open" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">My Courses</h5>
                </div>
                <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5"><?php echo count($courses); ?> Courses</span>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="award" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">No courses assigned</h6>
                        <p class="text-muted mb-0 small">You are not currently assigned as an instructor for any courses.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Course Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4" style="width: 320px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($c['title']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($c['code'] ?? ''); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">
                                                <?php echo htmlspecialchars($c['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($c['status'] === 'published'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">Published</span>
                                            <?php elseif ($c['status'] === 'draft'): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill small">Draft</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill small">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="index.php?route=admin/gradebook&action=manage&course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                                                    <i data-lucide="sliders" size="14"></i> Manage Items
                                                </a>
                                                <a href="index.php?route=admin/gradebook&action=grid&course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                                                    <i data-lucide="grid" size="14"></i> Grades Spreadsheet
                                                </a>
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
    </div>
</div>

<?php
include __DIR__ . '/../layout_footer.php';
?>
