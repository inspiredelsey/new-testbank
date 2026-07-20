<?php
/**
 * Student Submission Confirmation View - Test Bank LMS
 */
$pageTitle = htmlspecialchars($attempt['exam_title']) . ' - Submission Confirmed';
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden border">
                <!-- Decorative top bar with secondary theme colors -->
                <div class="bg-primary-subtle py-3 px-4 d-flex align-items-center gap-2 border-bottom">
                    <span class="badge bg-primary text-primary fw-bold font-sans">SUBMISSION RECEIVED</span>
                </div>
                
                <div class="card-body p-5 text-center">
                    <!-- Icon Animation container -->
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle p-4 mb-4" style="width: 80px; height: 80px;">
                        <i data-lucide="check-circle-2" class="text-success" style="width: 48px; height: 48px;"></i>
                    </div>

                    <h3 class="display-font fw-bold text-dark mb-2">Exam Successfully Captured!</h3>
                    <p class="text-muted font-sans mb-4">
                        Thank you. Your answers for <strong><?php echo htmlspecialchars($attempt['exam_title']); ?></strong> have been securely recorded and finalized in our database.
                    </p>

                    <!-- Attempt Details Bento Card -->
                    <div class="bg-light p-4 rounded-3 border mb-4 text-start">
                        <h6 class="fw-bold font-sans text-dark border-bottom pb-2 mb-3">Attempt Metadata Summary</h6>
                        <div class="row g-3 small font-sans text-muted">
                            <div class="col-sm-6">
                                <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.65rem; letter-spacing: 0.5px;">Attempt ID</div>
                                <div class="text-dark font-mono fw-bold">#<?php echo $attempt['id']; ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.65rem; letter-spacing: 0.5px;">Student Participant</div>
                                <div class="text-dark fw-semibold"><?php echo htmlspecialchars($attempt['student_name']); ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.65rem; letter-spacing: 0.5px;">Started At</div>
                                <div class="text-dark"><?php echo date('M d, Y - h:i A', strtotime($attempt['started_at'])); ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.65rem; letter-spacing: 0.5px;">Submitted At</div>
                                <div class="text-dark"><?php echo !empty($attempt['submitted_at']) ? date('M d, Y - h:i A', strtotime($attempt['submitted_at'])) : date('M d, Y - h:i A'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Grading Notice Info Panel -->
                    <div class="d-flex align-items-start gap-3 bg-light-subtle p-3 rounded-3 border border-dashed text-start mb-5">
                        <i data-lucide="info" class="text-primary flex-shrink-0 mt-0.5" style="width: 20px; height: 20px;"></i>
                        <div class="small font-sans text-muted">
                            <strong class="text-dark">Evaluation Queue Acknowledged:</strong> This exam has been dispatched to the LMS grading queue. Direct feedback and scores will be made available as soon as manual grading pipelines and rubrics are executed.
                        </div>
                    </div>

                    <!-- Navigation Action Buttons -->
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php?route=student/dashboard" class="btn btn-outline-secondary font-sans px-4 py-2.5 rounded-3 d-flex align-items-center gap-2">
                            <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i> Return to Dashboard
                        </a>
                        <a href="index.php?route=student/exam/review&attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-primary font-sans px-4 py-2.5 rounded-3 d-flex align-items-center gap-2">
                            <i data-lucide="clipboard-list" style="width: 18px; height: 18px;"></i> View Saved Responses
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
});
</script>

<?php
include __DIR__ . '/../../../admin/views/layout_footer.php';
?>
