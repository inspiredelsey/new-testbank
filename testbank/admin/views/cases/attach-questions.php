<?php
$pageTitle = 'Attach Questions - ' . htmlspecialchars($case['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="container-fluid py-4">
    <!-- Header Block -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="index.php?route=admin/cases&action=list" class="btn btn-light border p-2 rounded-3 d-flex align-items-center">
            <i data-lucide="arrow-left" size="18" class="text-muted"></i>
        </a>
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Attach Questions to Case</h1>
            <p class="text-muted mb-0">Case Study: <strong class="text-dark"><?php echo htmlspecialchars($case['title']); ?></strong></p>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i data-lucide="check-circle" class="text-success"></i>
            <div><?php echo htmlspecialchars($_GET['success']); ?></div>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i data-lucide="alert-circle" class="text-danger"></i>
            <div><?php echo htmlspecialchars($_GET['error']); ?></div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column: Case details and Attached Questions -->
        <div class="col-xl-7 col-lg-6 mb-4">
            <!-- Patient Profile Overview -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i data-lucide="clipboard-list" class="text-primary"></i> Patient Scenario / Sticky Overview
                    </h6>
                </div>
                <div class="card-body bg-light-subtle">
                    <div class="p-3 bg-white border rounded-3 text-dark small" style="max-height: 200px; overflow-y: auto; white-space: pre-line;">
                        <?php echo htmlspecialchars($case['scenario_text']); ?>
                    </div>
                </div>
            </div>

            <!-- List of Attached Questions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold text-secondary d-flex align-items-center gap-2">
                        <i data-lucide="link-2" class="text-primary"></i> Questions in this Case Study
                    </h5>
                    <span class="badge bg-primary"><?php echo count($attachedQuestions); ?> Question(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($attachedQuestions)): ?>
                        <div class="text-center py-5">
                            <i data-lucide="help-circle" class="text-muted d-block mx-auto mb-3" size="48"></i>
                            <p class="text-muted mb-0 px-4">No questions have been sequenced inside this clinical case study yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 font-sans">
                                <thead class="table-light text-muted small uppercase">
                                    <tr>
                                        <th class="ps-4 text-center" style="width: 80px;">Seq</th>
                                        <th>Question Prompt</th>
                                        <th>Type</th>
                                        <th class="text-center">Points</th>
                                        <th class="text-end pe-4" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attachedQuestions as $q): ?>
                                        <tr>
                                            <td class="ps-4 text-center">
                                                <span class="badge bg-dark rounded-pill px-2.5 py-1.5 font-mono fs-6"><?php echo (int)$q['case_order']; ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-truncate text-dark small mb-0" style="max-width: 320px;" title="<?php echo htmlspecialchars($q['question_text']); ?>">
                                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border small"><?php echo htmlspecialchars(QuestionRenderer::getTypeLabel($q['type'])); ?></span>
                                            </td>
                                            <td class="text-center fw-semibold text-secondary small">
                                                <?php echo floatval($q['points']); ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <form action="index.php?route=admin/cases&action=detach" method="POST" class="d-inline mb-0"
                                                      onsubmit="return confirm('Are you sure you want to detach this question from this case study?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 ms-auto">
                                                        <i data-lucide="link-2-off" size="14"></i> Detach
                                                    </button>
                                                </form>
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

        <!-- Right Column: Attachment Tool -->
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold text-secondary d-flex align-items-center gap-2">
                        <i data-lucide="plus-circle" class="text-success"></i> Attach Standalone Question
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($unattachedQuestions)): ?>
                        <div class="alert alert-warning border-0 shadow-sm mb-0">
                            <i data-lucide="info" size="18" class="me-2 text-warning"></i>
                            There are no available standalone questions in this category (<?php echo htmlspecialchars($case['category_name'] ?? ''); ?>) to attach.
                            <div class="mt-3">
                                <a href="index.php?route=admin/questions&action=create&case_id=<?php echo $case['id']; ?>" class="btn btn-sm btn-warning w-100 fw-semibold text-white">
                                    Create Question Directly for this Case
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form action="index.php?route=admin/cases&action=do_attach" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">

                            <div class="mb-3">
                                <label for="question_id" class="form-label fw-semibold text-muted small">Select Available Question <span class="text-danger">*</span></label>
                                <select class="form-select text-dark font-sans" id="question_id" name="question_id" required>
                                    <option value="" disabled selected>-- Choose Question to Attach --</option>
                                    <?php foreach ($unattachedQuestions as $q): ?>
                                        <option value="<?php echo $q['id']; ?>">
                                            [<?php echo htmlspecialchars(QuestionRenderer::getTypeLabel($q['type'])); ?>] 
                                            <?php echo htmlspecialchars(substr($q['question_text'], 0, 70)) . (strlen($q['question_text']) > 70 ? '...' : ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="text-muted small mt-1 d-block">Only questions from the same category are listable to protect course domains.</span>
                            </div>

                            <div class="mb-4">
                                <label for="case_order" class="form-label fw-semibold text-muted small">Sequence / Order Index <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="case_order" name="case_order" value="<?php echo count($attachedQuestions) + 1; ?>" required min="1" placeholder="e.g. 1">
                                <span class="text-muted small">Defines the presentation order for students taking this Case Study.</span>
                            </div>

                            <button type="submit" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2">
                                <i data-lucide="link" size="16"></i> Attach and Sequence Question
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Helper Card -->
            <div class="card border-0 bg-light shadow-sm">
                <div class="card-body p-4 small">
                    <h6 class="fw-bold mb-2 text-secondary d-flex align-items-center gap-1">
                        <i data-lucide="graduation-cap" size="16"></i> NGN Nursing Judgment Guide
                    </h6>
                    <p class="mb-2 text-muted">A Case Study corresponds to an unfolding scenario where questions should be sequenced according to the <strong>Clinical Judgment Measurement Model</strong>:</p>
                    <ul class="text-muted mb-0 ps-3">
                        <li>1. Recognize Cues (e.g. Question 1)</li>
                        <li>2. Analyze Cues (e.g. Question 2)</li>
                        <li>3. Prioritize Hypotheses</li>
                        <li>4. Generate Solutions</li>
                        <li>5. Take Actions</li>
                        <li>6. Evaluate Outcomes</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
