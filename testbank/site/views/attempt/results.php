<?php
/**
 * Student Results / Post-Submission View - Test Bank LMS
 */
$pageTitle = htmlspecialchars($attempt['exam_title']) . ' - Results';
include __DIR__ . '/../../../admin/views/layout_header.php';

// Calculate total possible points
$totalPossible = 0.00;
foreach ($attempt['questions'] as $q) {
    $totalPossible += floatval($q['points']);
}

$score = floatval($attempt['score'] ?? 0.00);
$percentage = floatval($attempt['percentage'] ?? 0.00);
$passPercentage = floatval($attempt['pass_percentage'] ?? 0.00);
$status = $attempt['status']; // 'submitted' (pending essay) or 'graded'
$passed = !empty($attempt['passed']);
?>

<div class="container py-4">
    <!-- Header Summary Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white p-4 rounded-3 shadow-sm border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="badge bg-primary text-white font-sans mb-1 uppercase fw-bold" style="letter-spacing: 0.5px;">Exam Completed</span>
                    <h3 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($attempt['exam_title']); ?></h3>
                    <p class="text-muted mb-0 font-sans">Attempt #<?php echo $attempt['id']; ?> • Submitted by <?php echo htmlspecialchars($attempt['student_name']); ?> on <?php echo date('M d, Y - h:i A', strtotime($attempt['submitted_at'] ?? 'now')); ?></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?route=student/dashboard" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1.5 font-sans">
                        <i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Side: Grading Status & Score Cards (Bento style) -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden border">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                    <i data-lucide="bar-chart-3" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold text-dark">Performance Card</h5>
                </div>
                <div class="card-body p-4 text-center">
                    
                    <!-- Grading Status Badge -->
                    <?php if ($status === 'submitted'): ?>
                        <div class="bg-warning bg-opacity-10 text-warning px-3 py-3 rounded-3 border border-warning border-opacity-25 mb-4 text-start d-flex gap-3 align-items-center">
                            <i data-lucide="alert-circle" class="text-warning flex-shrink-0" style="width: 28px; height: 28px;"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Pending Instructor Review</h6>
                                <p class="text-muted small mb-0 font-sans" style="line-height: 1.4;">This exam contains essay questions that require manual evaluation. Your current score represents the auto-graded portion.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($passed): ?>
                            <div class="bg-success bg-opacity-10 text-success px-3 py-3 rounded-3 border border-success border-opacity-25 mb-4 text-start d-flex gap-3 align-items-center">
                                <i data-lucide="check-circle" class="text-success flex-shrink-0" style="width: 28px; height: 28px;"></i>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Congratulations, You Passed!</h6>
                                    <p class="text-muted small mb-0 font-sans" style="line-height: 1.4;">You met or exceeded the passing threshold of <?php echo $passPercentage; ?>% on this exam.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-danger bg-opacity-10 text-danger px-3 py-3 rounded-3 border border-danger border-opacity-25 mb-4 text-start d-flex gap-3 align-items-center">
                                <i data-lucide="x-circle" class="text-danger flex-shrink-0" style="width: 28px; height: 28px;"></i>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Passing Threshold Not Met</h6>
                                    <p class="text-muted small mb-0 font-sans" style="line-height: 1.4;">The required passing score is <?php echo $passPercentage; ?>%. Review your incorrect answers and try again.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Radial/Numeric Display -->
                    <div class="py-3">
                        <small class="text-muted uppercase fw-bold font-sans d-block mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Overall Score</small>
                        <h1 class="display-4 fw-bold text-primary mb-1"><?php echo number_format($percentage, 2); ?>%</h1>
                        <span class="fs-5 fw-semibold text-dark font-mono"><?php echo number_format($score, 2); ?> <span class="text-muted">/ <?php echo number_format($totalPossible, 2); ?> pts</span></span>
                    </div>

                    <hr class="my-4 opacity-50">

                    <!-- Score Breakdown Details -->
                    <div class="d-flex flex-column gap-3 text-start small font-sans">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Passing Percentage Req:</span>
                            <span class="fw-bold text-dark"><?php echo $passPercentage; ?>%</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Auto-Grader Status:</span>
                            <span class="badge <?php echo $status === 'graded' ? 'bg-success text-white' : 'bg-warning text-dark'; ?> font-sans">
                                <?php echo $status === 'graded' ? 'Fully Evaluated' : 'Awaiting Essays'; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($status === 'graded'): ?>
                        <div class="mt-4">
                            <a href="index.php?route=student/exam/review&attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-primary rounded-3 w-full d-inline-flex align-items-center justify-content-center gap-2 py-2.5 font-sans">
                                <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                                <span>Review Question Explanations</span>
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Right Side: Question-by-Question Grading List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 border">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                    <i data-lucide="check-square" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold text-dark">Question Scoring Summary</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($attempt['questions'] as $index => $q): 
                            $qId = $q['id'];
                            $ans = $savedAnswers[$qId] ?? null;
                            $ptsAwarded = $ans ? $ans['points_awarded'] : null;
                            $isCorrect = $ans ? $ans['is_correct'] : null;
                            $needsManual = $ans ? $ans['needs_manual_grading'] : false;
                            
                            // Visual properties based on correctness
                            $cardBorderClass = 'border-light';
                            $iconColorClass = 'text-muted';
                            $iconName = 'circle';
                            $badgeClass = 'bg-secondary bg-opacity-10 text-secondary';
                            $badgeLabel = 'No Answer';

                            if ($q['type'] === 'essay') {
                                if ($needsManual) {
                                    $cardBorderClass = 'border-warning border-opacity-30';
                                    $iconColorClass = 'text-warning';
                                    $iconName = 'hourglass';
                                    $badgeClass = 'bg-warning bg-opacity-10 text-warning-emphasis';
                                    $badgeLabel = 'Pending Essay Grading';
                                } else {
                                    $cardBorderClass = 'border-success border-opacity-30';
                                    $iconColorClass = 'text-success';
                                    $iconName = 'check-circle';
                                    $badgeClass = 'bg-success bg-opacity-10 text-success';
                                    $badgeLabel = 'Evaluated';
                                }
                            } else if ($ans) {
                                $maxPts = floatval($q['points']);
                                $awardedFloat = floatval($ptsAwarded);
                                
                                if ($isCorrect == 1 && $awardedFloat === $maxPts) {
                                    $cardBorderClass = 'border-success border-opacity-30';
                                    $iconColorClass = 'text-success';
                                    $iconName = 'check-circle';
                                    $badgeClass = 'bg-success bg-opacity-10 text-success';
                                    $badgeLabel = 'Correct';
                                } else if ($awardedFloat > 0 && $awardedFloat < $maxPts) {
                                    $cardBorderClass = 'border-info border-opacity-30';
                                    $iconColorClass = 'text-info';
                                    $iconName = 'help-circle';
                                    $badgeClass = 'bg-info bg-opacity-10 text-info-emphasis';
                                    $badgeLabel = 'Partial Credit';
                                } else {
                                    $cardBorderClass = 'border-danger border-opacity-30';
                                    $iconColorClass = 'text-danger';
                                    $iconName = 'x-circle';
                                    $badgeClass = 'bg-danger bg-opacity-10 text-danger';
                                    $badgeLabel = 'Incorrect';
                                }
                            }
                        ?>
                            <div class="p-4 rounded-3 border <?php echo $cardBorderClass; ?> bg-white shadow-sm d-flex gap-3">
                                <!-- Status Icon -->
                                <div class="flex-shrink-0">
                                    <i data-lucide="<?php echo $iconName; ?>" class="<?php echo $iconColorClass; ?>" style="width: 24px; height: 24px;"></i>
                                </div>
                                
                                <!-- Details -->
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-2 gap-2">
                                        <div>
                                            <span class="text-muted small uppercase fw-bold font-sans">Question <?php echo $index + 1; ?></span>
                                            <span class="badge bg-secondary text-secondary bg-opacity-10 ms-1 text-uppercase font-sans" style="font-size: 0.65rem;"><?php echo str_replace('_', ' ', htmlspecialchars($q['type'])); ?></span>
                                        </div>
                                        <div class="text-md-end">
                                            <?php if ($q['type'] === 'essay' && $needsManual): ?>
                                                <span class="fw-bold font-mono text-warning">Awaiting Evaluation</span>
                                            <?php else: ?>
                                                <span class="fw-bold font-mono text-dark" style="font-size: 1.05rem;">
                                                    <?php echo $ptsAwarded !== null ? number_format(floatval($ptsAwarded), 2) : '0.00'; ?> 
                                                    <span class="text-muted" style="font-size: 0.85rem;">/ <?php echo number_format(floatval($q['points']), 2); ?> pts</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Question prompt -->
                                    <div class="font-sans text-dark fw-semibold mb-3" style="font-size: 1.05rem; line-height: 1.6;"><?php echo htmlspecialchars($q['question_text']); ?></div>

                                    <!-- Submitted Response (if any) -->
                                    <?php if ($ans): ?>
                                        <div class="p-3 bg-light rounded-3 font-sans small text-muted border">
                                            <span class="fw-bold text-secondary d-block uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Your Answer:</span>
                                            <div class="text-dark" style="white-space: pre-wrap; word-break: break-all;">
                                                <?php 
                                                    $decoded = json_decode($ans['answer_data'], true);
                                                    if ($q['type'] === 'essay') {
                                                        $ansText = is_array($decoded) ? ($decoded['text'] ?? '') : $decoded;
                                                        if (empty($ansText) && is_string($ans['answer_data'])) {
                                                            $ansText = $ans['answer_data'];
                                                        }
                                                        echo htmlspecialchars($ansText ?: '(No answer response entered)');
                                                    } else {
                                                        // Non-essay structured JSON display placeholder
                                                        if (is_array($decoded)) {
                                                            if (isset($decoded['selected'])) {
                                                                if (is_array($decoded['selected'])) {
                                                                    echo "Selected options ID(s): [" . implode(', ', array_map('htmlspecialchars', $decoded['selected'])) . "]";
                                                                } else {
                                                                    echo "Selected option ID: " . htmlspecialchars($decoded['selected']);
                                                                }
                                                            } else if (isset($decoded['blanks'])) {
                                                                echo "Completed blanks: " . htmlspecialchars(json_encode($decoded['blanks']));
                                                            } else if (isset($decoded['pairs'])) {
                                                                echo "Matched pairs: " . htmlspecialchars(json_encode($decoded['pairs']));
                                                            } else if (isset($decoded['order'])) {
                                                                echo "Configured sequence: " . htmlspecialchars(json_encode($decoded['order']));
                                                            } else if (isset($decoded['answers'])) {
                                                                echo "Grid selections: " . htmlspecialchars(json_encode($decoded['answers']));
                                                            } else if (isset($decoded['segments'])) {
                                                                echo "Selected passage highlights: " . htmlspecialchars(json_encode($decoded['segments']));
                                                            } else if (isset($decoded['left']) || isset($decoded['center']) || isset($decoded['right'])) {
                                                                echo "Bowtie setup: Left: " . json_encode($decoded['left'] ?? []) . ", Center: " . json_encode($decoded['center'] ?? []) . ", Right: " . json_encode($decoded['right'] ?? []);
                                                            } else if (isset($decoded['value'])) {
                                                                echo "Numeric response: " . htmlspecialchars($decoded['value']);
                                                            } else {
                                                                echo htmlspecialchars(json_encode($decoded));
                                                            }
                                                        } else {
                                                            echo htmlspecialchars($ans['answer_data']);
                                                        }
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-3 bg-light rounded-3 font-sans small text-muted border text-center font-bold">Unanswered Question</div>
                                    <?php endif; ?>

                                    <!-- Feedback Status badge -->
                                    <div class="mt-2 text-start">
                                        <span class="badge <?php echo $badgeClass; ?> px-2 py-1 small font-sans"><?php echo $badgeLabel; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    if (window.lucide) window.lucide.createIcons();
});
</script>

<?php include __DIR__ . '/../../../admin/views/layout_footer.php'; ?>
