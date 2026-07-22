<?php
/**
 * Student Gradebook Course List View
 */
$pageTitle = 'My Grades';
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="mb-4">
            <h3 class="display-font fw-bold text-dark mb-1">My Academic Grades</h3>
            <p class="text-muted small mb-0">View your overall weighted course grades and track your learning milestones.</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
                <i data-lucide="alert-circle" class="text-danger" size="18"></i>
                <div><?php echo htmlspecialchars($_GET['error']); ?></div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="award" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Enrolled Courses</h5>
                </div>
                <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5"><?php echo count($coursesWithGrades); ?> Courses</span>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($coursesWithGrades)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="graduation-cap" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">Not enrolled in any courses</h6>
                        <p class="text-muted mb-0 small">Enroll in a course from your dashboard to view your progress here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Course Details</th>
                                    <th>Instructor</th>
                                    <th>Enrolled Date</th>
                                    <th class="text-center">Academic Standing</th>
                                    <th class="text-end pe-4" style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coursesWithGrades as $cg): ?>
                                    <?php 
                                    $gradeInfo = $cg['grade_data'];
                                    $finalPct = $gradeInfo['final_grade'];
                                    $hasItems = $gradeInfo['total_items'] > 0;
                                    $isPartial = $gradeInfo['is_partial'];
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($cg['course_title']); ?></div>
                                        </td>
                                        <td>
                                            <span class="text-secondary small fw-medium"><?php echo htmlspecialchars($cg['instructor_name'] ?: 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><?php echo date('M d, Y', strtotime($cg['enrolled_at'])); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($hasItems): ?>
                                                <span class="badge <?php echo ($finalPct >= 50.0) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?> px-3 py-1.5 fw-bold font-mono">
                                                    <?php echo number_format($finalPct, 2); ?>%
                                                </span>
                                                <?php if ($isPartial): ?>
                                                    <div class="text-muted font-sans mt-1" style="font-size: 0.70rem;">
                                                        Partial Grade (<?php echo $gradeInfo['graded_items']; ?>/<?php echo $gradeInfo['total_items']; ?> components)
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-success font-sans mt-1" style="font-size: 0.70rem;">
                                                        Final Grade (All components graded)
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">— No graded items yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="index.php?route=student/gradebook&action=mygrades&course_id=<?php echo $cg['course_id']; ?>" class="btn btn-sm btn-primary d-flex align-items-center justify-content-center gap-1.5">
                                                <i data-lucide="trending-up" size="14"></i> Detailed Report
                                            </a>
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
include __DIR__ . '/../../../admin/views/layout_footer.php';
?>
