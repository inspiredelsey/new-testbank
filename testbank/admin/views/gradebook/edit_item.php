<?php
/**
 * Admin Edit Gradebook Item View
 */
$pageTitle = 'Edit Gradebook Item - ' . htmlspecialchars($item['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="row justify-content-center mb-4">
    <div class="col-md-8 col-lg-6">
        <!-- Breadcrumbs & Header -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?route=admin/gradebook" class="text-decoration-none">Gradebooks</a></li>
                <li class="breadcrumb-item"><a href="index.php?route=admin/gradebook&action=manage&course_id=<?php echo $item['course_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($course['title']); ?> Setup</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Item</li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="edit-3" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Edit Graded Item</h5>
            </div>

            <div class="card-body p-4">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
                        <i data-lucide="alert-circle" class="text-danger" size="18"></i>
                        <div><?php echo htmlspecialchars($_GET['error']); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?route=admin/gradebook&action=edit_item&id=<?php echo $item['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <?php if ($item['item_type'] === 'quiz'): ?>
                        <!-- Quiz Type item - Title and max_score are locked -->
                        <div class="alert alert-light border d-flex align-items-start gap-2.5 mb-4 p-3 rounded-3">
                            <i data-lucide="info" size="20" class="text-info mt-0.5 flex-shrink-0"></i>
                            <div class="small text-muted">
                                This component is automatically linked to the quiz <strong><?php echo htmlspecialchars($item['exam_title'] ?: $item['title']); ?></strong>. Its title and maximum score are synced directly from the exam configuration. Only the weight can be adjusted here.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium text-dark small">Item Title (Locked)</label>
                            <input type="text" class="form-control font-sans bg-light" value="<?php echo htmlspecialchars($item['title']); ?>" disabled>
                            <input type="hidden" name="title" value="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium text-dark small">Maximum Score (Locked)</label>
                            <div class="input-group">
                                <input type="number" class="form-control font-sans bg-light" value="<?php echo number_format($item['max_score'], 2); ?>" disabled>
                                <span class="input-group-text small text-muted">points</span>
                            </div>
                            <input type="hidden" name="max_score" value="<?php echo $item['max_score']; ?>">
                        </div>
                    <?php else: ?>
                        <!-- Manual Type item - Fully editable -->
                        <div class="mb-3">
                            <label for="itemTitle" class="form-label fw-medium text-dark small">Item Title</label>
                            <input type="text" name="title" id="itemTitle" class="form-control font-sans" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="itemMaxScore" class="form-label fw-medium text-dark small">Maximum Score</label>
                            <div class="input-group">
                                <input type="number" name="max_score" id="itemMaxScore" class="form-control font-sans" value="<?php echo number_format($item['max_score'], 2); ?>" min="0.01" step="0.01" required>
                                <span class="input-group-text small text-muted">points</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <label for="itemWeight" class="form-label fw-medium text-dark small">Weight in Final Grade</label>
                        <div class="input-group">
                            <input type="number" name="weight" id="itemWeight" class="form-control font-sans" value="<?php echo number_format($item['weight'], 2); ?>" min="0.00" max="100.00" step="0.01" required>
                            <span class="input-group-text small text-muted">%</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1.5 px-4">
                            <i data-lucide="save" size="16"></i> Save Changes
                        </button>
                        <a href="index.php?route=admin/gradebook&action=manage&course_id=<?php echo $item['course_id']; ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1.5">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layout_footer.php';
?>
