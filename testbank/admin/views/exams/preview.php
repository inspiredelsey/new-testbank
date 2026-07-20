<?php
/**
 * Exam Resolved Question Set Preview View
 */
$pageTitle = 'Exam Preview Snapshot';
include __DIR__ . '/../layout_header.php';
?>

<div class="mb-4">
    <a href="index.php?route=admin/exams&action=build&id=<?php echo $exam['id']; ?>" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
        <i data-lucide="arrow-left" size="14"></i>
        <span>Back to Exam Builder</span>
    </a>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-1 display-font">Resolved Question Set Preview</h1>
            <p class="text-muted mb-0">Validation snapshot for exam: <strong><?php echo htmlspecialchars($exam['title']); ?></strong></p>
        </div>
        <button class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2" onclick="window.location.reload();" id="btn-refresh-preview">
            <i data-lucide="rotate-cw" size="18"></i>
            <span>Generate New Snapshot</span>
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Left Panel: Explanatory summary and metadata of this specific snapshot -->
    <div class="col-lg-4 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 mb-4 sticky-top" style="top: 20px;" id="preview-summary-card">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="info" class="text-primary" size="18"></i>
                    <span>Snapshot Details</span>
                </h5>
            </div>
            <div class="card-body p-3">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <h6 class="fw-semibold text-dark font-sans small text-uppercase mb-2" style="font-size: 0.65rem;">Simulation Notice</h6>
                    <p class="small text-muted mb-0" style="line-height: 1.4;">
                        This preview runs a full resolution simulation of the exam rules in real time. 
                        <strong>Refreshing this page</strong> will run the pull rules again, generating a fresh, randomized sequence from your question banks.
                    </p>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total Questions</span>
                        <span class="fw-semibold text-dark"><?php echo count($resolvedQuestions); ?> items</span>
                    </li>
                    <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Pass Mark</span>
                        <span class="fw-semibold text-dark"><?php echo floatval($exam['pass_percentage']); ?>%</span>
                    </li>
                    <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Duration Limit</span>
                        <span class="fw-semibold text-dark"><?php echo intval($exam['duration_minutes']); ?> mins</span>
                    </li>
                </ul>

                <?php if (!empty($exam['description'])): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-semibold text-dark font-sans small text-uppercase mb-2" style="font-size: 0.65rem;">Student Instructions</h6>
                        <div class="text-muted small italic" style="white-space: pre-wrap; line-height: 1.4;"><?php echo htmlspecialchars($exam['description']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel: Loop and render all resolved questions using QuestionRenderer -->
    <div class="col-lg-8 col-xl-9">
        <?php if (empty($resolvedQuestions)): ?>
            <div class="card border-0 shadow-sm rounded-3 p-5 text-center text-muted" id="empty-resolved-card">
                <i data-lucide="help-circle" size="48" class="opacity-25 mb-3"></i>
                <h4 class="text-dark">No questions resolved</h4>
                <p class="small text-muted mb-0">The current rules and fixed picks produced an empty question set. Please add questions or random pull rules first.</p>
                <div class="mt-4">
                    <a href="index.php?route=admin/exams&action=build&id=<?php echo $exam['id']; ?>" class="btn btn-outline-primary font-sans small">
                        Go to Exam Builder &rarr;
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4" id="resolved-questions-loop">
                <?php foreach ($resolvedQuestions as $idx => $q): ?>
                    <div class="position-relative" id="resolved-q-wrapper-<?php echo $q['id']; ?>">
                        <!-- Header counter overlay -->
                        <div class="position-absolute translate-middle-y ms-4" style="top: 0; z-index: 10;">
                            <span class="badge bg-dark text-white shadow-sm font-mono" style="padding: 0.4rem 0.8rem; border-radius: 20px;">
                                Question <?php echo $idx + 1; ?>
                            </span>
                        </div>
                        
                        <!-- Dynamic rendered question -->
                        <div class="pt-3">
                            <?php echo QuestionRenderer::render($q); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
