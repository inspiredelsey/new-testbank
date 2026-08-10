<?php
$pageTitle = 'Grading Queue & Results';
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <!-- Grading Queue Column -->
    <div class="col-xl-7 col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="check-square" class="text-warning"></i>
                <h5 class="mb-0 fw-semibold">Manual Grading Queue</h5>
                <span class="badge bg-warning text-dark font-sans"><?php echo count($gradingQueue); ?> pending essays</span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($gradingQueue)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="smile" class="text-muted d-block mx-auto mb-3" size="48"></i>
                        <p class="text-muted mb-0">Hooray! The manual grading queue is entirely empty.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($gradingQueue as $item): ?>
                            <div class="p-3 rounded border border-warning-subtle bg-warning-subtle-opacity-10 shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom border-light">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($item['exam_title']); ?></h6>
                                        <small class="text-muted font-sans">Submitted by <strong><?php echo htmlspecialchars($item['student_name']); ?></strong> on <?php echo date('M d, Y H:i', strtotime($item['started_at'])); ?></small>
                                    </div>
                                    <span class="badge bg-dark font-sans">Max: <?php echo floatval($item['max_points']); ?> pts</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block mb-1 fw-medium">Question Prompt:</span>
                                    <p class="mb-2 text-dark font-sans bg-light p-2 rounded small" style="white-space: pre-line;"><?php echo htmlspecialchars($item['question_text']); ?></p>
                                    
                                    <span class="text-muted small d-block mb-1 fw-medium">Student Answer:</span>
                                    <?php 
                                        $decodedAns = json_decode($item['answer_data'], true);
                                        if (is_array($decodedAns)) {
                                            $ansText = $decodedAns['text'] ?? ($decodedAns['answer'] ?? implode(', ', array_filter(array_map('strval', $decodedAns), 'is_scalar')));
                                        } else {
                                            $ansText = $decodedAns;
                                        }
                                        if (empty($ansText) && is_string($item['answer_data'])) {
                                            $ansText = $item['answer_data'];
                                        }
                                        if (!is_string($ansText) && !is_numeric($ansText)) {
                                            $ansText = json_encode($ansText);
                                        }
                                    ?>
                                    <p class="mb-0 text-dark font-sans bg-white border p-3 rounded" style="white-space: pre-line;"><?php echo htmlspecialchars($ansText ?: '(Empty response)'); ?></p>
                                </div>
                                <form action="index.php?route=admin/attempts&action=grade" method="POST" class="row g-2 align-items-center justify-content-end">
                                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                                    <input type="hidden" name="answer_id" value="<?php echo $item['id']; ?>">
                                    
                                    <div class="col-auto">
                                        <label class="col-form-label small text-muted fw-bold">Award Score:</label>
                                    </div>
                                    <div class="col-auto">
                                        <input type="number" name="points_awarded" class="form-control form-control-sm text-center fw-bold" value="0.00" step="0.25" min="0.00" max="<?php echo floatval($item['max_points']); ?>" style="max-width: 90px;" required>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                                            <i data-lucide="check" size="14"></i> Submit Grade
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Exam Performance Dashboards List -->
    <div class="col-xl-5 col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="trending-up" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Exam Performance Dashboards</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($examsList)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="bar-chart" class="text-muted d-block mx-auto mb-3" size="48"></i>
                        <p class="text-muted mb-0">Create and publish exams to unlock visual metrics.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($examsList as $e): ?>
                            <a href="index.php?route=admin/attempts&action=stats&exam_id=<?php echo $e['id']; ?>" class="list-group-item list-group-item-action p-4 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($e['title']); ?></h6>
                                    <span class="badge bg-light text-secondary border font-sans text-uppercase small" style="font-size: 0.75rem;"><?php echo $e['status']; ?></span>
                                    <small class="text-muted font-sans ms-2"><?php echo $e['duration_minutes']; ?> mins | Pass limit: <?php echo floatval($e['pass_percentage']); ?>%</small>
                                </div>
                                <div class="d-flex align-items-center text-primary gap-1">
                                    <span class="small fw-semibold">View Stats</span>
                                    <i data-lucide="chevron-right" size="16"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
