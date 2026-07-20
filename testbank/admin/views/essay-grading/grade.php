<?php
$pageTitle = 'Evaluate Student Essay Response';
include __DIR__ . '/../layout_header.php';

// Load case study if available
$case = null;
$exhibits = [];
if (!empty($answer['case_id'])) {
    require_once __DIR__ . '/../../models/CaseStudy.php';
    $caseStudyModel = new CaseStudy();
    $case = $caseStudyModel->find($answer['case_id']);
    $exhibits = $caseStudyModel->exhibitsForCase($answer['case_id']);
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="bg-white p-4 rounded-3 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 font-sans">
                        <li class="breadcrumb-item"><a href="index.php?route=admin/courses">Admin</a></li>
                        <li class="breadcrumb-item"><a href="index.php?route=admin/essay-grading">Essay Grading</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Evaluate Response</li>
                    </ol>
                </nav>
                <h4 class="mb-0 fw-bold text-dark">Evaluate Student Essay Response</h4>
                <p class="text-muted mb-0 font-sans">Review student's answer, compare it with guidelines, and assign points.</p>
            </div>
            <a href="index.php?route=admin/essay-grading" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1.5 font-sans">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                <span>Back to Queue</span>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Context / Scenario / Question -->
    <div class="col-lg-6 mb-4">
        <!-- Clinical Case Study (If linked) -->
        <?php if ($case): ?>
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom d-flex align-items-center gap-2">
                    <i data-lucide="file-text" class="text-primary"></i>
                    <h5 class="mb-0 fw-bold text-primary">Case Study: <?php echo htmlspecialchars($case['title']); ?></h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3 font-sans" style="line-height: 1.7; font-size: 1.05rem;">
                        <span class="text-muted small d-block mb-1 fw-bold text-uppercase" style="letter-spacing: 0.5px;">Patient Scenario:</span>
                        <div class="p-3 bg-light rounded-3 text-dark border" style="white-space: pre-wrap;"><?php echo htmlspecialchars($case['scenario_text']); ?></div>
                    </div>

                    <?php if (!empty($exhibits)): ?>
                        <div class="mt-4">
                            <span class="text-muted small d-block mb-2 fw-bold text-uppercase" style="letter-spacing: 0.5px;">Exhibits / Tabs:</span>
                            <div class="accordion" id="exhibitsAccordion">
                                <?php foreach ($exhibits as $index => $ex): ?>
                                    <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                                        <h2 class="accordion-header" id="heading<?php echo $ex['id']; ?>">
                                            <button class="accordion-button collapsed py-2 px-3 bg-white font-sans fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $ex['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $ex['id']; ?>">
                                                <i data-lucide="folder-open" class="text-primary me-2" style="width: 16px; height: 16px;"></i>
                                                <?php echo htmlspecialchars($ex['tab_label']); ?>
                                                <?php if (!empty($ex['timestamp_label'])): ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary ms-2 small"><?php echo htmlspecialchars($ex['timestamp_label']); ?></span>
                                                <?php endif; ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $ex['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $ex['id']; ?>" data-bs-parent="#exhibitsAccordion">
                                            <div class="accordion-body bg-light p-3 font-sans text-dark small" style="white-space: pre-wrap; line-height: 1.6;"><?php echo htmlspecialchars($ex['content']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Question Prompt Card -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="help-circle" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold text-dark">Essay Question Prompt</h5>
            </div>
            <div class="card-body p-4">
                <div class="font-sans text-dark" style="line-height: 1.8; font-size: 1.1rem; white-space: pre-wrap;"><?php echo htmlspecialchars($answer['question_text']); ?></div>
            </div>
        </div>
    </div>

    <!-- Right Column: Student Submission & Score Assigning -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-light mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                    <div>
                        <small class="text-muted d-block font-sans">SUBMITTED BY</small>
                        <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($answer['student_name']); ?></h6>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block font-sans">SUBMISSION DATE</small>
                        <h6 class="mb-0 fw-semibold text-dark"><?php echo date('F d, Y \a\t H:i', strtotime($answer['submitted_at'])); ?></h6>
                    </div>
                </div>

                <span class="text-muted small d-block mb-2 fw-bold text-uppercase font-sans" style="letter-spacing: 0.5px;">Student's Essay Response:</span>
                <div class="bg-white p-4 rounded-3 border text-dark font-sans shadow-sm mb-4" style="min-height: 200px; white-space: pre-wrap; line-height: 1.8; font-size: 1.05rem; border-left: 4px solid var(--bs-primary) !important;">
                    <?php 
                        $decodedText = json_decode($answer['answer_data'], true);
                        $studentText = is_array($decodedText) ? ($decodedText['text'] ?? '') : $decodedText;
                        if (empty($studentText) && is_string($answer['answer_data'])) {
                            $studentText = $answer['answer_data'];
                        }
                        echo htmlspecialchars($studentText ?: '(No response submitted)');
                    ?>
                </div>

                <!-- Grading Evaluation Form -->
                <div class="bg-white p-4 rounded-3 shadow-sm border border-warning border-opacity-30">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i data-lucide="award" class="text-warning"></i>
                        <h5 class="mb-0 fw-bold text-dark">Assign Score</h5>
                    </div>

                    <form action="index.php?route=admin/essay-grading&action=grade" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                        <input type="hidden" name="id" value="<?php echo $answer['attempt_answer_id']; ?>">

                        <div class="mb-4">
                            <label for="points_awarded" class="form-label font-sans fw-semibold text-muted small uppercase">Award Points:</label>
                            <div class="input-group input-group-lg" style="max-width: 250px;">
                                <input type="number" id="points_awarded" name="points_awarded" class="form-control text-center fw-bold text-primary" value="<?php echo $answer['points_awarded'] !== null ? floatval($answer['points_awarded']) : '0.00'; ?>" step="0.25" min="0.00" max="<?php echo floatval($answer['max_points']); ?>" required style="font-size: 1.5rem;">
                                <span class="input-group-text bg-light text-muted fw-bold">/ <?php echo floatval($answer['max_points']); ?></span>
                            </div>
                            <div class="form-text font-sans text-muted mt-2">Enter any value between 0.00 and <?php echo floatval($answer['max_points']); ?> points. Decimal points like 0.25, 0.50, 0.75 are fully supported.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning text-dark font-sans fw-bold rounded-3 flex-grow-1 d-inline-flex align-items-center justify-content-center gap-2 py-2.5">
                                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                                <span>Save and Finalize Grade</span>
                            </button>
                            <a href="index.php?route=admin/essay-grading" class="btn btn-light border font-sans rounded-3 py-2.5 px-4">Cancel</a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
