<?php
$pageTitle = 'Exam Instructions - ' . htmlspecialchars($exam['title']);
include __DIR__ . '/../layout_header.php';

$attemptsLeft = intval($exam['max_attempts']) > 0 ? (intval($exam['max_attempts']) - $attemptCount) : null;
$canStart = $attemptsLeft === null || $attemptsLeft > 0;
?>

<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-4 border-bottom d-flex align-items-center justify-content-center gap-2">
                <div class="p-2 bg-primary-subtle text-primary rounded-3">
                    <i data-lucide="info" size="24"></i>
                </div>
                <h4 class="mb-0 fw-bold text-dark display-font">Assessment Instructions</h4>
            </div>
            
            <div class="card-body p-4">
                <h5 class="fw-bold text-dark mb-3 text-center"><?php echo htmlspecialchars($exam['title']); ?></h5>
                
                <?php if ($exam['description']): ?>
                    <div class="p-3 bg-light rounded-3 text-muted small mb-4 font-sans" style="white-space: pre-line;">
                        <?php echo htmlspecialchars($exam['description']); ?>
                    </div>
                <?php endif; ?>

                <h6 class="fw-bold text-dark mb-3">Please Read Prior to Commencing:</h6>
                <div class="d-flex flex-column gap-3 mb-4 font-sans text-muted">
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 bg-light text-dark rounded-circle"><i data-lucide="clock" size="16"></i></div>
                        <div>
                            <strong class="text-dark d-block">Time Limit</strong>
                            <span>You have exactly <strong><?php echo $exam['duration_minutes']; ?> minutes</strong> to complete this exam. A dynamic countdown clock will remain pinned. If your time expires, your answers will auto-submit.</span>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 bg-light text-dark rounded-circle"><i data-lucide="award" size="16"></i></div>
                        <div>
                            <strong class="text-dark d-block">Passing Threshold</strong>
                            <span>You must score a minimum of <strong><?php echo floatval($exam['pass_percentage']); ?>%</strong> to pass this assessment.</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 bg-light text-dark rounded-circle"><i data-lucide="save" size="16"></i></div>
                        <div>
                            <strong class="text-dark d-block">Automatic Backup Autosave</strong>
                            <span>All your answers are backed up automatically in the background using secure AJAX. Feel free to navigate between questions.</span>
                        </div>
                    </div>
                </div>

                <!-- Attempt Statistics Card -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3 d-flex justify-content-around text-center">
                        <div>
                            <span class="text-muted small d-block">Attempts Logged</span>
                            <strong class="text-dark fs-5"><?php echo $attemptCount; ?></strong>
                        </div>
                        <div class="border-start"></div>
                        <div>
                            <span class="text-muted small d-block">Maximum Allowed</span>
                            <strong class="text-dark fs-5"><?php echo $exam['max_attempts'] > 0 ? $exam['max_attempts'] : '∞'; ?></strong>
                        </div>
                        <div class="border-start"></div>
                        <div>
                            <span class="text-muted small d-block">Attempts Remaining</span>
                            <?php if ($attemptsLeft === null): ?>
                                <strong class="text-success fs-5">∞</strong>
                            <?php else: ?>
                                <strong class="<?php echo $attemptsLeft > 0 ? 'text-success' : 'text-danger'; ?> fs-5"><?php echo $attemptsLeft; ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!$canStart): ?>
                    <div class="alert alert-danger border-0 rounded-3 p-3 text-center d-flex align-items-center justify-content-center gap-2 mb-0">
                        <i data-lucide="alert-triangle"></i>
                        <span class="fw-bold">You have depleted all available attempts for this exam.</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center px-4">
                <a href="index.php?route=student/dashboard" class="btn btn-light border d-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" size="18"></i> Cancel & Exit
                </a>
                
                <?php if ($canStart): ?>
                    <form action="index.php?route=student/exam/start" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                        
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2" onclick="return confirm('Do you wish to start the timer and begin your exam attempt now?')">
                            Begin Exam <i data-lucide="play" size="18"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
