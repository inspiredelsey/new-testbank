<?php
require_once __DIR__ . '/../../../includes/QuestionRenderer.php';

$pageTitle = 'Exam Review - ' . htmlspecialchars($attempt['exam_title']);
include __DIR__ . '/../layout_header.php';

$isGraded = ($attempt['status'] === 'graded');
$passPercentage = floatval($attempt['pass_percentage']);
$scoredPercentage = floatval($attempt['percentage']);
$passed = $attempt['passed'] ? true : false;
?>

<div class="row">
    <!-- Main Header / Banner Info -->
    <div class="col-12 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark display-font mb-1">Assessment Performance Review</h4>
            <span class="text-muted small font-sans">Exam ID: <?php echo $attempt['exam_id']; ?> | Attempt ID: <?php echo $attempt['id']; ?></span>
        </div>
        <a href="index.php?route=student/dashboard" class="btn btn-outline-secondary d-flex align-items-center gap-1 font-sans">
            <i data-lucide="home" size="16"></i> Return to Portal
        </a>
    </div>

    <!-- KPI / Score Summary Card -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 24px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-1">
                    <i data-lucide="target" size="16" class="text-primary"></i> Performance Overview
                </h6>
            </div>
            
            <div class="card-body p-4 text-center">
                <?php if (!$isGraded): ?>
                    <div class="p-4 bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-3 mb-4">
                        <i data-lucide="clock" size="36" class="mb-2"></i>
                        <h6 class="fw-bold mb-1">Pending Manual Evaluation</h6>
                        <p class="small mb-0 text-muted">This exam includes essay questions that must be evaluated manually by an instructor before your final grade is finalized.</p>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-3 border mb-4 <?php echo $passed ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'; ?>">
                        <div class="d-inline-flex p-3 rounded-circle mb-3 <?php echo $passed ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                            <i data-lucide="<?php echo $passed ? 'smile-plus' : 'frown'; ?>" size="36"></i>
                        </div>
                        <h3 class="display-font fw-bold mb-1"><?php echo $passed ? 'Congratulations!' : 'Keep Practicing!'; ?></h3>
                        <p class="small mb-0 text-uppercase fw-semibold">
                            You have <?php echo $passed ? 'Passed' : 'Failed'; ?> this exam.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="row g-2 mb-3 font-sans">
                    <div class="col-6">
                        <div class="border p-2.5 rounded">
                            <span class="text-muted small d-block">Points Earned</span>
                            <strong class="fs-5 text-dark"><?php echo floatval($attempt['score']); ?> pts</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border p-2.5 rounded">
                            <span class="text-muted small d-block">Final Grade</span>
                            <strong class="fs-5 text-dark"><?php echo round($scoredPercentage, 2); ?>%</strong>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 text-start small font-sans text-muted mb-0">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Min Pass Limit:</span>
                        <strong class="text-dark"><?php echo $passPercentage; ?>%</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Time Duration:</span>
                        <strong class="text-dark"><?php echo $attempt['duration_minutes']; ?> minutes</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Started Session:</span>
                        <strong class="text-dark"><?php echo date('M d, Y H:i', strtotime($attempt['started_at'])); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Submitted Time:</span>
                        <strong class="text-dark"><?php echo $attempt['submitted_at'] ? date('M d, Y H:i', strtotime($attempt['submitted_at'])) : 'N/A'; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Question Review Slides Column -->
    <div class="col-xl-8 col-lg-7">
        <h5 class="fw-bold mb-3 d-flex align-items-center gap-1">
            <i data-lucide="check-square" class="text-primary"></i> Question Breakdown
        </h5>
        
        <?php foreach ($questions as $idx => $q): ?>
            <?php
            $ans = $savedAnswers[$q['id']] ?? null;
            $userAnsData = $ans ? json_decode($ans['answer_data'], true) : null;
            $isCorrect = $ans ? ($ans['is_correct'] ? true : false) : false;
            $ptsAwarded = $ans ? floatval($ans['points_awarded']) : 0.00;
            $needsManual = $ans ? ($ans['needs_manual_grading'] ? true : false) : false;
            ?>
            <div class="mb-4">
                <span class="text-muted fw-bold d-block small mb-1 font-sans text-uppercase">Question <?php echo ($idx + 1); ?></span>
                <?php
                echo QuestionRenderer::renderReview(
                    $q, 
                    $q['options'], 
                    $userAnsData, 
                    $isCorrect, 
                    $ptsAwarded, 
                    $needsManual
                );
                ?>
                
                <?php if (!empty($q['explanation'])): ?>
                    <div class="p-3 rounded-3 bg-light border border-light-subtle font-sans small text-muted mt-2 shadow-xs">
                        <strong class="text-dark d-block mb-1"><i data-lucide="help-circle" size="14" class="me-1"></i> Solution Explanation:</strong>
                        <p class="mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($q['explanation']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../layout_header.php'; ?>
