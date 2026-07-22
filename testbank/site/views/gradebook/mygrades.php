<?php
/**
 * Student Individual Course Grades Report View
 */
$pageTitle = htmlspecialchars($course['title']) . ' - Grades';
include __DIR__ . '/../../../admin/views/layout_header.php';

$finalPct = $gradeData['final_grade'];
$totalComponents = $gradeData['total_items'];
$gradedComponents = $gradeData['graded_items'];
$isPartial = $gradeData['is_partial'];
$breakdown = $gradeData['breakdown'];
?>

<!-- Back Link & Header -->
<div class="mb-4">
    <a href="index.php?route=student/gradebook" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-2">
        <i data-lucide="arrow-left" size="14"></i> Back to My Grades
    </a>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="display-font fw-bold text-dark mb-1">Grade Report</h3>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($course['title']); ?></p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Grade Summary Card (Left Side) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4 h-100 d-flex flex-column justify-content-center">
            <div class="card-body py-4">
                <div class="text-muted small text-uppercase fw-semibold tracking-wider mb-2">Current Weighted Grade</div>
                
                <?php if ($totalComponents > 0 && $gradedComponents > 0): ?>
                    <div class="display-3 fw-bold font-mono mb-2 <?php echo ($finalPct >= 50.0) ? 'text-success' : 'text-danger'; ?>">
                        <?php echo number_format($finalPct, 2); ?>%
                    </div>
                    
                    <div class="mb-4">
                        <?php if ($finalPct >= 50.0): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill fw-semibold font-sans">
                                <i data-lucide="check-circle" size="14" class="me-1 align-middle"></i> Passing Standing
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill fw-semibold font-sans">
                                <i data-lucide="alert-triangle" size="14" class="me-1 align-middle"></i> Below Passing
                            </span>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4 text-muted opacity-25">

                    <div class="row text-start g-3">
                        <div class="col-6">
                            <span class="text-muted small d-block">Graded Components</span>
                            <span class="fw-bold text-dark fs-5"><?php echo $gradedComponents; ?> / <?php echo $totalComponents; ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Configured Weight</span>
                            <span class="fw-bold text-dark fs-5"><?php echo number_format($gradeData['weight_sum'], 1); ?>%</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="py-4">
                        <i data-lucide="calendar-clock" class="text-slate-300 mb-3" size="48" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">No grades available yet</h6>
                        <p class="text-muted small mb-0">Your instructor has not published or recorded any grades for this course.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Components Table (Right Side) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="list-checks" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Grading Components Breakdown</h5>
                </div>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($breakdown)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="book-open" class="text-slate-300 d-block mx-auto mb-2" size="40"></i>
                        <h6 class="fw-semibold text-slate-700">No grading structure found</h6>
                        <p class="text-muted small mb-0">This course currently has no quizzes or manual items registered in its gradebook.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Graded Component</th>
                                    <th>Type</th>
                                    <th>Component Weight</th>
                                    <th>Your Score</th>
                                    <th class="text-end pe-4">Weighted Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($breakdown as $item): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($item['title']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($item['item_type'] === 'quiz'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="help-circle" size="12"></i> Quiz
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-purple-subtle text-purple border border-purple-subtle d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="edit-3" size="12"></i> Manual Grade
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-medium"><?php echo number_format($item['weight'], 2); ?>%</span>
                                        </td>
                                        <td>
                                            <?php if ($item['is_graded']): ?>
                                                <span class="fw-bold font-mono text-dark"><?php echo number_format($item['score'], 2); ?></span>
                                                <span class="text-muted small font-mono">/ <?php echo number_format($item['max_score'], 2); ?></span>
                                                <div class="text-muted small font-mono" style="font-size: 0.70rem;">
                                                    (<?php echo number_format(($item['score'] / $item['max_score']) * 100, 1); ?>%)
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">Ungraded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4 fw-bold font-mono text-dark">
                                            <?php if ($item['is_graded']): ?>
                                                <?php echo number_format($item['weighted_points'], 2); ?> / <?php echo number_format($item['weight'], 2); ?> pts
                                            <?php else: ?>
                                                <span class="text-muted small fw-normal">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="2" class="ps-4 py-3">Running Summary</td>
                                    <td><?php echo number_format($gradeData['weight_sum'], 2); ?>%</td>
                                    <td></td>
                                    <td class="text-end pe-4 font-mono text-primary">
                                        <?php echo number_format($gradeData['weighted_score_sum'], 2); ?> / <?php echo number_format($gradeData['weight_sum'], 2); ?> pts
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../../../admin/views/layout_footer.php';
?>
