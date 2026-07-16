<?php
require_once __DIR__ . '/../../../includes/QuestionRenderer.php';

$pageTitle = 'Exam Preview - ' . htmlspecialchars($exam['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Pre-Flight Student Preview</h5>
                    <p class="text-muted mb-0">Review the fully assembled question set as a student would experience it. All dynamic rules have been processed and resolved for this snapshot.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?route=admin/exams&action=preview&id=<?php echo $exam['id']; ?>" class="btn btn-outline-info d-flex align-items-center gap-1">
                        <i data-lucide="refresh-cw" size="16"></i> Regenerate Assembly
                    </a>
                    <a href="index.php?route=admin/exams" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                        <i data-lucide="arrow-left" size="16"></i> Back to Workspace
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-9 col-lg-8">
        <?php if (empty($resolvedQuestions)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i data-lucide="slash" class="text-muted d-block mx-auto mb-3" size="48"></i>
                    <h5 class="fw-semibold">No questions resolved for this exam!</h5>
                    <p class="text-muted">You must assign manual questions or configure random-pull rules in the exam workspace first.</p>
                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <a href="index.php?route=admin/exams&action=questions&id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">Assign Questions</a>
                        <a href="index.php?route=admin/exams&action=rules&id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-secondary">Add Rules</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="preview-exam-container">
                <?php foreach ($resolvedQuestions as $idx => $q): ?>
                    <div class="mb-4">
                        <div class="text-muted fw-bold mb-2 ps-1 font-sans text-uppercase small">Question <?php echo ($idx + 1); ?> of <?php echo count($resolvedQuestions); ?></div>
                        <?php echo QuestionRenderer::render($q, $q['options'], null, true); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar Exam Stats -->
    <div class="col-xl-3 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 24px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-1">
                    <i data-lucide="info" size="16" class="text-primary"></i> Exam Assembly Details
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <span class="text-muted small d-block">Duration Limit</span>
                        <strong class="fs-5 text-dark"><?php echo $exam['duration_minutes']; ?> Minutes</strong>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Questions Count</span>
                        <strong class="fs-5 text-dark"><?php echo count($resolvedQuestions); ?> Total</strong>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Points Weight</span>
                        <strong class="fs-5 text-dark"><?php 
                            $totalPoints = 0;
                            foreach ($resolvedQuestions as $q) {
                                $totalPoints += floatval($q['points']);
                            }
                            echo $totalPoints;
                        ?> pts</strong>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Passing Score</span>
                        <strong class="fs-5 text-dark"><?php echo floatval($exam['pass_percentage']); ?>% <span class="text-muted small font-sans fw-normal">(<?php echo round($totalPoints * $exam['pass_percentage'] / 100, 2); ?> pts)</span></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
