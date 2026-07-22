<?php
/**
 * Admin Gradebook Grid Spreadsheet View
 */
$pageTitle = 'Gradebook Spreadsheet - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?route=admin/gradebook" class="text-decoration-none">Gradebooks</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($course['title']); ?> Grid</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="display-font fw-bold text-dark mb-1">Grades Spreadsheet</h3>
            <p class="text-muted small mb-0">Spreadsheet-style overview of student scores. Click the edit icon in manual grade cells to enter or update marks.</p>
        </div>
        <div>
            <a href="index.php?route=admin/gradebook&action=manage&course_id=<?php echo $course['id']; ?>" class="btn btn-outline-primary d-flex align-items-center gap-2">
                <i data-lucide="sliders" size="16"></i> Configure Items & Weights
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
        <i data-lucide="check-circle" class="text-success" size="18"></i>
        <div><?php echo htmlspecialchars($_GET['success']); ?></div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
        <i data-lucide="alert-circle" class="text-danger" size="18"></i>
        <div><?php echo htmlspecialchars($_GET['error']); ?></div>
    </div>
<?php endif; ?>

<!-- Spreadsheet Grid Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="grid" class="text-primary"></i>
            <h5 class="mb-0 fw-semibold">Course Grade Grid</h5>
        </div>
        <div class="small text-muted font-sans d-flex gap-3">
            <span><i class="lucide-help-circle text-info me-1"></i>Quiz/Exam</span>
            <span><i class="lucide-edit-3 text-purple me-1"></i>Manual Grade</span>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($students)): ?>
            <div class="text-center py-5">
                <i data-lucide="users" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                <h6 class="fw-semibold text-slate-700">No students enrolled</h6>
                <p class="text-muted mb-0 small">Enroll students in this course to see them in the gradebook spreadsheet.</p>
            </div>
        <?php elseif (empty($items)): ?>
            <div class="text-center py-5">
                <i data-lucide="sliders" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                <h6 class="fw-semibold text-slate-700">No grading items configured</h6>
                <p class="text-muted mb-4 small">Configure at least one grading component (Quiz or Manual Item) to start tracking student progress.</p>
                <a href="index.php?route=admin/gradebook&action=manage&course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                    <i data-lucide="plus" size="16"></i> Configure Items
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-nowrap" style="border-collapse: collapse;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="min-width: 220px; z-index: 2; background-color: #f8f9fa;">Student Name & Email</th>
                            <?php foreach ($items as $item): ?>
                                <th class="text-center px-3" style="min-width: 140px;">
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($item['title']); ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </div>
                                    <div class="text-muted font-mono" style="font-size: 0.75rem;">
                                        <?php echo number_format($item['weight'], 1); ?>% (max: <?php echo number_format($item['max_score'], 0); ?>)
                                    </div>
                                    <div class="mt-1">
                                        <?php if ($item['item_type'] === 'quiz'): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">QUIZ</span>
                                        <?php else: ?>
                                            <span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="font-size: 0.65rem;">MANUAL</span>
                                        <?php endif; ?>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center bg-light-subtle pe-4" style="min-width: 150px; border-left: 2px solid #dee2e6;">
                                <div class="fw-bold text-primary">Weighted Final Grade</div>
                                <div class="text-muted small">Course Average</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php 
                            $sId = $student['user_id'];
                            $finalGradeInfo = $studentFinalGrades[$sId] ?? null;
                            $finalPct = $finalGradeInfo ? $finalGradeInfo['final_grade'] : 0.00;
                            $isPartial = $finalGradeInfo ? $finalGradeInfo['is_partial'] : true;
                            ?>
                            <tr>
                                <td class="ps-4 py-3 bg-white" style="position: sticky; left: 0; z-index: 1; border-right: 2px solid #dee2e6;">
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($student['user_name']); ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($student['user_email']); ?></div>
                                </td>
                                
                                <?php foreach ($items as $item): ?>
                                    <?php 
                                    $giId = $item['id'];
                                    $score = isset($scoresMatrix[$sId][$giId]) ? $scoresMatrix[$sId][$giId] : null;
                                    $maxScore = floatval($item['max_score']);
                                    ?>
                                    <td class="text-center px-3">
                                        <?php if ($item['item_type'] === 'quiz'): ?>
                                            <?php if ($score !== null): ?>
                                                <div class="fw-semibold text-dark font-mono"><?php echo number_format($score, 2); ?></div>
                                                <div class="text-muted font-mono" style="font-size: 0.70rem;"><?php echo number_format(($score / $maxScore) * 100, 1); ?>%</div>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Manual Item Cell -->
                                            <div class="d-inline-flex align-items-center justify-content-center gap-1.5">
                                                <?php if ($score !== null): ?>
                                                    <div>
                                                        <div class="fw-semibold text-dark font-mono"><?php echo number_format($score, 2); ?></div>
                                                        <div class="text-muted font-mono" style="font-size: 0.70rem;"><?php echo number_format(($score / $maxScore) * 100, 1); ?>%</div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small me-1">—</span>
                                                <?php endif; ?>
                                                
                                                <button type="button" 
                                                        class="btn btn-xs btn-outline-purple p-1 border-0 rounded-circle text-purple" 
                                                        style="line-height: 1; background: transparent; transition: color 0.2s;"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#enterScoreModal"
                                                        data-user-id="<?php echo $sId; ?>"
                                                        data-student-name="<?php echo htmlspecialchars($student['user_name']); ?>"
                                                        data-item-id="<?php echo $giId; ?>"
                                                        data-item-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                        data-max-score="<?php echo $maxScore; ?>"
                                                        data-current-score="<?php echo $score !== null ? $score : ''; ?>"
                                                        title="Edit Manual Score">
                                                    <i data-lucide="pencil-line" size="14"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <td class="text-center pe-4 fw-bold font-mono" style="border-left: 2px solid #dee2e6; background-color: #fdfdfd;">
                                    <?php if ($finalGradeInfo && $finalGradeInfo['total_items'] > 0): ?>
                                        <div class="fs-5 <?php echo ($finalPct >= 50) ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo number_format($finalPct, 2); ?>%
                                        </div>
                                        <div class="text-muted font-sans" style="font-size: 0.70rem; font-weight: normal;">
                                            <?php echo $finalGradeInfo['graded_items']; ?> / <?php echo $finalGradeInfo['total_items']; ?> graded
                                            <?php if ($isPartial): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1" style="font-size: 0.60rem;">Partial</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal to Record/Edit Manual Grades -->
<div class="modal fade" id="enterScoreModal" tabindex="-1" aria-labelledby="enterScoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-purple text-white py-3">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="enterScoreModalLabel">
                    <i data-lucide="edit-3" size="20"></i> Enter Manual Score
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="index.php?route=admin/gradebook&action=enter_score">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                    <input type="hidden" name="gradebook_item_id" id="modalItemId">
                    <input type="hidden" name="user_id" id="modalUserId">

                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Student</label>
                        <div class="fw-bold text-dark fs-5" id="modalStudentName">John Doe</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Grading Component</label>
                        <div class="fw-semibold text-dark" id="modalItemTitle">Class Participation</div>
                    </div>

                    <hr class="my-3 text-muted opacity-25">

                    <div class="mb-3">
                        <label for="modalScoreInput" class="form-label fw-semibold text-dark">Awarded Score</label>
                        <div class="input-group input-group-lg">
                            <input type="number" 
                                   name="score" 
                                   id="modalScoreInput" 
                                   class="form-control font-sans fw-bold text-center" 
                                   placeholder="0.00" 
                                   min="0.00" 
                                   step="0.01" 
                                   required>
                            <span class="input-group-text font-sans small text-muted" id="modalMaxLabel">/ 100.00 pts</span>
                        </div>
                        <div class="form-text small text-muted mt-2" id="modalValidationHint">Score must not exceed the maximum point value.</div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-purple px-4 d-flex align-items-center gap-1.5" id="modalSubmitBtn">
                        <i data-lucide="check" size="16"></i> Save Score
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const enterScoreModal = document.getElementById('enterScoreModal');
    if (enterScoreModal) {
        enterScoreModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract data attributes
            const userId = button.getAttribute('data-user-id');
            const studentName = button.getAttribute('data-student-name');
            const itemId = button.getAttribute('data-item-id');
            const itemTitle = button.getAttribute('data-item-title');
            const maxScore = parseFloat(button.getAttribute('data-max-score'));
            const currentScore = button.getAttribute('data-current-score');
            
            // Populate form fields
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalItemId').value = itemId;
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalItemTitle').textContent = itemTitle;
            
            const scoreInput = document.getElementById('modalScoreInput');
            scoreInput.value = currentScore;
            scoreInput.setAttribute('max', maxScore);
            
            document.getElementById('modalMaxLabel').textContent = '/ ' + maxScore.toFixed(2) + ' pts';
            document.getElementById('modalValidationHint').textContent = 'Enter a numeric score between 0.00 and ' + maxScore.toFixed(2) + ' pts.';
        });
        
        // Auto-focus on score input when modal opens
        enterScoreModal.addEventListener('shown.bs.modal', function() {
            const scoreInput = document.getElementById('modalScoreInput');
            if (scoreInput) {
                scoreInput.focus();
                scoreInput.select();
            }
        });
    }
});
</script>

<style>
.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
}
.btn-outline-purple:hover {
    color: #fff;
    background-color: #6f42c1;
    border-color: #6f42c1;
}
.btn-purple {
    color: #fff;
    background-color: #6f42c1;
    border-color: #6f42c1;
}
.btn-purple:hover {
    color: #fff;
    background-color: #59359a;
    border-color: #59359a;
}
.bg-purple {
    background-color: #6f42c1 !important;
}
.btn-xs {
    padding: 0.15rem 0.3rem;
    font-size: 0.75rem;
    line-height: 1;
}
</style>

<?php
include __DIR__ . '/../layout_footer.php';
?>
