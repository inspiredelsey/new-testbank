<?php
/**
 * Manage Learning Path View - Test Bank LMS
 * Lists learning path items, allows editing, deletion, reordering (up/down).
 */
$pageTitle = 'Manage Learning Path - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="mb-4">
    <a href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-2">
        <i data-lucide="arrow-left" size="14"></i> Back to Course Overview
    </a>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="display-font fw-bold text-dark mb-1">Learning Path Sequence</h3>
            <p class="text-muted small mb-0">Course: <strong class="text-dark"><?php echo htmlspecialchars($course['title']); ?></strong></p>
        </div>
        <a href="index.php?route=admin/learning-path&action=create&course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-medium">
            <i data-lucide="plus" size="16"></i> Add Path Item
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show font-sans small mb-4 shadow-sm" role="alert">
        <i data-lucide="check-circle" size="16" class="me-1.5 text-success"></i>
        <?php echo htmlspecialchars($_GET['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show font-sans small mb-4 shadow-sm" role="alert">
        <i data-lucide="alert-circle" size="16" class="me-1.5 text-danger"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold text-dark font-sans"><i data-lucide="activity" class="text-primary me-1" size="18"></i>Sequenced Path Items</h5>
        <span class="badge bg-light text-dark border font-sans"><?php echo count($items); ?> Items Total</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
            <div class="text-center py-5">
                <i data-lucide="map" class="text-muted d-block mx-auto mb-3" size="48"></i>
                <h5 class="fw-bold">No path items configured yet</h5>
                <p class="text-muted small mb-3">Add documents or external links to build a guided curriculum for your students.</p>
                <a href="index.php?route=admin/learning-path&action=create&course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary px-3">
                    <i data-lucide="plus" size="14"></i> Add First Item
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 font-sans">
                    <thead class="table-light text-muted small uppercase">
                        <tr>
                            <th style="width: 70px;" class="text-center">Order</th>
                            <th style="width: 130px;">Item Type</th>
                            <th>Milestone / Content Title</th>
                            <th>Prerequisite Item</th>
                            <th style="width: 120px;" class="text-center">Grading</th>
                            <th style="width: 180px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-primary text-primary fw-bold rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                        <?php echo $index + 1; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item['item_type'] === 'document'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1.5 px-2.5 small d-inline-flex align-items-center gap-1">
                                            <i data-lucide="file-text" size="12"></i> Document
                                        </span>
                                    <?php elseif ($item['item_type'] === 'link'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle py-1.5 px-2.5 small d-inline-flex align-items-center gap-1">
                                            <i data-lucide="link" size="12"></i> Link
                                        </span>
                                    <?php elseif ($item['item_type'] === 'quiz'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1.5 px-2.5 small d-inline-flex align-items-center gap-1">
                                            <i data-lucide="award" size="12"></i> Quiz
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">ID Reference: <?php echo $item['item_id']; ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($item['prerequisite_item_id'])): ?>
                                        <div class="d-flex align-items-center gap-1 text-muted small fw-medium">
                                            <i data-lucide="lock" size="12" class="text-danger"></i>
                                            <?php echo htmlspecialchars($item['prerequisite_title'] ?? 'Prerequisite Item'); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">None (Always Unlocked)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['is_required']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">Required</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border px-2 py-1 small">Optional</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Reordering Buttons -->
                                        <a href="index.php?route=admin/learning-path&action=reorder&id=<?php echo $item['id']; ?>&direction=up&course_id=<?php echo $course['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                           class="btn btn-sm btn-light border <?php echo ($index === 0) ? 'disabled text-muted' : ''; ?>" 
                                           title="Move Up">
                                            <i data-lucide="chevron-up" size="14"></i>
                                        </a>
                                        <a href="index.php?route=admin/learning-path&action=reorder&id=<?php echo $item['id']; ?>&direction=down&course_id=<?php echo $course['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                           class="btn btn-sm btn-light border <?php echo ($index === count($items) - 1) ? 'disabled text-muted' : ''; ?>" 
                                           title="Move Down">
                                            <i data-lucide="chevron-down" size="14"></i>
                                        </a>

                                        <!-- Edit config -->
                                        <a href="index.php?route=admin/learning-path&action=edit&id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-light border" 
                                           title="Edit Settings">
                                            <i data-lucide="edit-3" size="14"></i>
                                        </a>

                                        <!-- Delete item -->
                                        <a href="index.php?route=admin/learning-path&action=delete&id=<?php echo $item['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                           class="btn btn-sm btn-light border text-danger" 
                                           onclick="return confirm('Are you sure you want to remove this item from the learning path? This will clear all progress completions for this specific step.');" 
                                           title="Remove">
                                            <i data-lucide="trash-2" size="14"></i>
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

<?php include __DIR__ . '/../layout_footer.php'; ?>
