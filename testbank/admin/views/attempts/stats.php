<?php
$pageTitle = 'Analytics Dashboard - ' . htmlspecialchars($exam['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <!-- Back Header -->
    <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
        <h5 class="text-muted mb-0 font-sans">Exam Performance and Question Item Analysis metrics.</h5>
        <a href="index.php?route=admin/attempts" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i data-lucide="arrow-left" size="16"></i> Back to Queue
        </a>
    </div>

    <!-- KPIs Row -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm bg-white p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary-subtle text-primary rounded-3">
                    <i data-lucide="users" size="24"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Graded Attempts</span>
                    <strong class="fs-4 text-dark"><?php echo $summary['total_attempts']; ?> Attempts</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm bg-white p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-success-subtle text-success rounded-3">
                    <i data-lucide="trending-up" size="24"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Class Average Score</span>
                    <strong class="fs-4 text-dark"><?php echo round($summary['average_score'], 2); ?>%</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm bg-white p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-info-subtle text-info rounded-3">
                    <i data-lucide="award" size="24"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Exam Pass Rate</span>
                    <strong class="fs-4 text-dark"><?php echo round($summary['pass_rate'], 2); ?>%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Score Distribution & Hardest Questions -->
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-1">
                    <i data-lucide="bar-chart-2" size="16" class="text-primary"></i> Score Distribution Curve
                </h6>
            </div>
            <div class="card-body p-4">
                <?php if ($summary['total_attempts'] === 0): ?>
                    <p class="text-muted text-center py-4 mb-0">No graded attempts exist yet to display score curves.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-medium text-dark">Fail (< <?php echo floatval($exam['pass_percentage']); ?>%)</span>
                                <span class="small text-muted font-sans"><?php echo $distribution['fail']; ?> student(s)</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($distribution['fail'] / $summary['total_attempts']) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-medium text-dark">Low Pass (<?php echo floatval($exam['pass_percentage']); ?>% - 75%)</span>
                                <span class="small text-muted font-sans"><?php echo $distribution['pass_low']; ?> student(s)</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($distribution['pass_low'] / $summary['total_attempts']) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-medium text-dark">Medium Pass (75% - 90%)</span>
                                <span class="small text-muted font-sans"><?php echo $distribution['pass_mid']; ?> student(s)</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($distribution['pass_mid'] / $summary['total_attempts']) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-medium text-dark">High Pass (90%+)</span>
                                <span class="small text-muted font-sans"><?php echo $distribution['pass_high']; ?> student(s)</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($distribution['pass_high'] / $summary['total_attempts']) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Attempts Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-1">
                    <i data-lucide="list-collapse" size="16" class="text-primary"></i> Attempt Details log
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($attempts)): ?>
                    <p class="text-muted text-center py-4 mb-0">No graded student attempts logged.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>Completed</th>
                                    <th>Percentage</th>
                                    <th class="text-end pe-4">Outcome</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attempts as $att): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($att['student_name']); ?></div>
                                            <small class="text-muted font-sans"><?php echo htmlspecialchars($att['student_email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="small text-muted font-sans"><?php echo date('M d, Y H:i', strtotime($att['submitted_at'])); ?></span>
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo round($att['percentage'], 2); ?>%</td>
                                        <td class="text-end pe-4">
                                            <span class="badge border <?php echo $att['passed'] ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'; ?>">
                                                <?php echo $att['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
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

    <!-- Question Item Analysis -->
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-1">
                    <i data-lucide="alert-triangle" size="16" class="text-danger"></i> Question Item Analysis
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-light py-2 px-3 border border-light-subtle rounded-3 mb-4 small d-flex align-items-center gap-2">
                    <i data-lucide="info" size="16" class="text-info"></i>
                    <span>Questions are sorted with the <strong>hardest questions</strong> at the top (highest failure rate). Use this to identify gaps in understanding.</span>
                </div>

                <?php if (empty($itemAnalysis)): ?>
                    <p class="text-muted text-center py-4 mb-0">No item analysis available yet. Requires graded student attempts.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($itemAnalysis as $idx => $item): ?>
                            <div class="pb-3 border-bottom border-light <?php echo $idx === count($itemAnalysis)-1 ? 'border-bottom-0' : ''; ?>">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="fw-medium text-dark text-truncate small" style="max-width: 80%;" title="<?php echo htmlspecialchars($item['question_text']); ?>">
                                        QID: <?php echo $item['question_id']; ?> | <?php echo htmlspecialchars($item['question_text']); ?>
                                    </div>
                                    <strong class="small <?php echo $item['success_rate'] < 50 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo round($item['success_rate'], 1); ?>% success
                                    </strong>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <?php
                                    $barColor = $item['success_rate'] < 30 ? 'bg-danger' : ($item['success_rate'] < 60 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress-bar <?php echo $barColor; ?>" role="progressbar" style="width: <?php echo $item['success_rate']; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1 text-muted small font-sans" style="font-size: 0.75rem;">
                                    <span>Type: <?php echo $item['type']; ?> | Weight: <?php echo floatval($item['points']); ?> pts</span>
                                    <span><?php echo $item['correct_count']; ?> / <?php echo $item['total_count']; ?> correct answers</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
