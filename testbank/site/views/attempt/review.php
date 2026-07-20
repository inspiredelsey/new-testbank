<?php
/**
 * Student Exam Review View - Test Bank LMS
 */
$pageTitle = htmlspecialchars($attempt['exam_title']) . ' - Exam Review';
include __DIR__ . '/../../../admin/views/layout_header.php';

$attemptId = intval($attempt['id']);
?>

<div class="container py-4">
    <!-- Breadcrumb & Back button -->
    <div class="mb-4">
        <a href="index.php?route=student/dashboard" class="text-decoration-none text-muted small d-flex align-items-center gap-1">
            <i data-lucide="arrow-left" size="14"></i> Back to Dashboard
        </a>
    </div>

    <!-- Header Meta Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden border mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="badge bg-secondary text-white font-sans px-2.5 py-1 mb-2">FINAL REVIEW</span>
                    <h3 class="display-font fw-bold text-dark mb-1"><?php echo htmlspecialchars($attempt['exam_title']); ?></h3>
                    <p class="text-muted small mb-0 font-sans">
                        Attempt #<?php echo $attemptId; ?> &bull; Student: <strong><?php echo htmlspecialchars($attempt['student_name']); ?></strong>
                    </p>
                </div>
                <div class="text-md-end">
                    <div class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill font-sans fw-bold mb-2">
                        Status: Submitted
                    </div>
                    <div class="text-muted small font-sans">
                        <div>Started: <?php echo date('M d, Y h:i A', strtotime($attempt['started_at'])); ?></div>
                        <div>Submitted: <?php echo !empty($attempt['submitted_at']) ? date('M d, Y h:i A', strtotime($attempt['submitted_at'])) : '--'; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row">
        <!-- Questions Review Column -->
        <div class="col-lg-9 col-md-8">
            <h5 class="fw-bold text-dark mb-3 font-sans"><i data-lucide="file-text" class="text-primary me-2 align-middle"></i>Your Captured Responses</h5>

            <?php if (empty($questions)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i data-lucide="info" class="text-muted d-block mx-auto mb-3" size="48"></i>
                        <h5 class="fw-bold">No Questions Found</h5>
                        <p class="text-muted small">No questions are configured or resolved for this attempt review.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-4">
                    <?php foreach ($questions as $index => $question): ?>
                        <?php 
                        $qId = intval($question['id']);
                        $ansRow = $savedAnswers[$qId] ?? null;
                        $ansData = !empty($ansRow['answer_data']) ? json_decode($ansRow['answer_data'], true) : null;
                        ?>
                        <div class="card border-0 shadow-sm rounded-3 border overflow-hidden">
                            <!-- Question index title -->
                            <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
                                <span class="fw-bold font-sans text-dark">Question <?php echo ($index + 1); ?></span>
                                <span class="badge bg-secondary-subtle text-secondary font-mono small rounded">
                                    Type: <?php echo htmlspecialchars(str_replace('_', ' ', strtoupper($question['type']))); ?>
                                </span>
                            </div>

                            <div class="card-body p-4">
                                <!-- Interactive Question Container (Wrapped in non-clickable div) -->
                                <div class="pointer-events-none opacity-90 border-bottom pb-4 mb-3" style="pointer-events: none; user-select: none;">
                                    <?php echo QuestionRenderer::renderInteractive($question, $ansRow); ?>
                                </div>

                                <!-- Decoded Textual Answer Payload (for transparency/grading audit review) -->
                                <div class="bg-light p-3 rounded-3 border">
                                    <h6 class="small fw-bold text-uppercase text-secondary font-sans mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                        Recorded Answer Payload
                                    </h6>
                                    <?php if ($ansData === null): ?>
                                        <div class="text-danger small font-sans italic">
                                            <i data-lucide="alert-triangle" class="align-middle me-1" size="14"></i> No response was recorded for this question.
                                        </div>
                                    <?php else: ?>
                                        <div class="font-sans text-dark small">
                                            <div class="text-dark bg-white p-2.5 rounded border font-mono" style="word-break: break-all; font-size: 0.85rem;">
                                                <?php echo htmlspecialchars(json_encode($ansData, JSON_PRETTY_PRINT)); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Summary Stats -->
        <div class="col-lg-3 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 border sticky-top" style="top: 20px;">
                <div class="card-header bg-light py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0 font-sans"><i data-lucide="bar-chart-2" class="text-primary me-2 align-middle"></i>Review Summary</h6>
                </div>
                <div class="card-body p-3 font-sans text-muted small">
                    <div class="mb-3">
                        <div class="text-uppercase small fw-bold text-secondary mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">Questions Evaluated</div>
                        <div class="h5 fw-bold text-dark mb-0"><?php echo count($questions); ?> Total</div>
                    </div>
                    
                    <div class="mb-3 border-top pt-3">
                        <div class="text-uppercase small fw-bold text-secondary mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">Answers Saved</div>
                        <?php 
                        $answeredCount = 0;
                        foreach ($questions as $q) {
                            if (!empty($savedAnswers[$q['id']]['answer_data'])) {
                                $answeredCount++;
                            }
                        }
                        ?>
                        <div class="h5 fw-bold text-dark mb-0"><?php echo $answeredCount; ?> / <?php echo count($questions); ?></div>
                    </div>

                    <div class="border-top pt-3 text-center">
                        <a href="index.php?route=student/dashboard" class="btn btn-outline-primary font-sans w-full py-2 rounded-3 d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Return Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    if (window.lucide) window.lucide.createIcons();

    // Disable all form elements inside the pointers-events-none blocks
    document.querySelectorAll('.pointer-events-none input, .pointer-events-none select, .pointer-events-none textarea, .pointer-events-none button').forEach(el => {
        el.disabled = true;
        el.removeAttribute('onclick');
        el.style.cursor = 'default';
    });
});
</script>
