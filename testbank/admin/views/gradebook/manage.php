<?php
/**
 * Admin Gradebook Items Management View
 */
$pageTitle = 'Manage Gradebook - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?route=admin/gradebook" class="text-decoration-none">Gradebooks</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($course['title']); ?></li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="display-font fw-bold text-dark mb-1">Gradebook Setup</h3>
            <p class="text-muted small mb-0">Define evaluation components (quizzes and manual grades) and configure their weight percentages.</p>
        </div>
        <div>
            <a href="index.php?route=admin/gradebook&action=grid&course_id=<?php echo $course['id']; ?>" class="btn btn-primary d-flex align-items-center gap-2">
                <i data-lucide="grid" size="16"></i> View Gradebook Grid
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
        <i data-lucide="check-circle" class="text-success" size="18"></i>
        <div><?php echo htmlspecialchars($_GET['success']); ?></div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
        <i data-lucide="alert-circle" class="text-danger" size="18"></i>
        <div><?php echo htmlspecialchars($_GET['error']); ?></div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Gradebook Items List (Left Column) -->
    <div class="col-lg-8">
        <!-- Weight Summary Panel -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark font-sans">Total Running Weight</span>
                    <span class="badge <?php echo (abs($weightSum - 100.00) < 0.01) ? 'bg-success' : 'bg-warning text-dark'; ?> px-3 py-1.5 fw-semibold font-sans">
                        <?php echo number_format($weightSum, 2); ?>% / 100.00%
                    </span>
                </div>
                
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar <?php echo (abs($weightSum - 100.00) < 0.01) ? 'bg-success' : 'bg-warning'; ?>" 
                         role="progressbar" 
                         style="width: <?php echo min(100, $weightSum); ?>%" 
                         aria-valuenow="<?php echo $weightSum; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>

                <?php if (abs($weightSum - 100.00) < 0.01): ?>
                    <div class="text-success small d-flex align-items-center gap-1.5">
                        <i data-lucide="check-circle-2" size="16"></i>
                        <span>Perfect! The total weights sum to exactly 100%. Final course grades will compute accurately.</span>
                    </div>
                <?php else: ?>
                    <div class="text-warning-dark small d-flex align-items-start gap-1.5" style="color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; padding: 12px; border-radius: 8px;">
                        <i data-lucide="alert-triangle" size="18" class="flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong>Attention:</strong> <?php echo htmlspecialchars($warning); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="list" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Graded Items</h5>
                </div>
                <span class="badge bg-light text-dark border"><?php echo count($items); ?> Items</span>
            </div>

            <div class="card-body p-0">
                <?php if (empty($items)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="file-text" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">No grading items yet</h6>
                        <p class="text-muted mb-0 small">Create a manual grading task on the right, or publish a course quiz to add one.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Item Name</th>
                                    <th>Type</th>
                                    <th>Max Score</th>
                                    <th>Weight</th>
                                    <th class="text-end pe-4" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($item['title']); ?></span>
                                            <?php if ($item['item_type'] === 'quiz' && !empty($item['exam_title'])): ?>
                                                <div class="text-muted small">Linked exam: <?php echo htmlspecialchars($item['exam_title']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($item['item_type'] === 'quiz'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="help-circle" size="12"></i> Quiz / Exam
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-purple-subtle text-purple border border-purple-subtle d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="edit-3" size="12"></i> Manual Grade
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark"><?php echo number_format($item['max_score'], 2); ?> pts</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-semibold px-2.5 py-1.5"><?php echo number_format($item['weight'], 2); ?>%</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="index.php?route=admin/gradebook&action=edit_item&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                                                    <i data-lucide="pencil" size="13"></i> Edit
                                                </a>
                                                <?php if ($item['item_type'] === 'manual'): ?>
                                                    <form method="POST" action="index.php?route=admin/gradebook&action=delete_item" onsubmit="return confirm('Are you sure you want to delete this manual grade item? This will permanently delete all student marks entered for this item.');" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                                                            <i data-lucide="trash-2" size="13"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-light text-muted d-flex align-items-center gap-1" disabled title="Quiz items cannot be deleted directly. Change quiz publication status to sync automatically.">
                                                        <i data-lucide="lock" size="13"></i> Locked
                                                    </button>
                                                <?php endif; ?>
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

    <!-- Add Manual Gradebook Item (Right Column) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="plus-circle" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Add Manual Item</h5>
                </div>
            </div>
            
            <div class="card-body p-4">
                <p class="text-muted small mb-4">Create offline evaluation targets (e.g., "Homework 1", "Classroom Participation", "Final Project Presentation") to mark students directly.</p>
                
                <form method="POST" action="index.php?route=admin/gradebook&action=add_item">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">

                    <div class="mb-3">
                        <label for="itemTitle" class="form-label fw-medium text-dark small">Item Title</label>
                        <input type="text" name="title" id="itemTitle" class="form-control font-sans" placeholder="e.g. Midterm Project" required>
                    </div>

                    <div class="mb-3">
                        <label for="itemMaxScore" class="form-label fw-medium text-dark small">Maximum Score</label>
                        <div class="input-group">
                            <input type="number" name="max_score" id="itemMaxScore" class="form-control font-sans" value="100.00" min="0.01" step="0.01" required>
                            <span class="input-group-text small text-muted">points</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="itemWeight" class="form-label fw-medium text-dark small">Weight in Final Grade</label>
                        <div class="input-group">
                            <input type="number" name="weight" id="itemWeight" class="form-control font-sans" value="10.00" min="0.00" max="100.00" step="0.01" required>
                            <span class="input-group-text small text-muted">%</span>
                        </div>
                        <div class="form-text small text-muted">Remaining weight: <?php echo number_format(max(0, 100 - $weightSum), 2); ?>%</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full d-flex align-items-center justify-content-center gap-2">
                        <i data-lucide="save" size="16"></i> Save Grading Item
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layout_footer.php';
?>
