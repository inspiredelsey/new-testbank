<?php
$pageTitle = 'Assign Questions - ' . htmlspecialchars($exam['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Assign Manual Question Pool</h5>
                    <p class="text-muted mb-0">Select specific questions from the bank that will always be included in this exam. You can also override the point weight of individual questions.</p>
                </div>
                <a href="index.php?route=admin/exams" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" size="16"></i> Back to Workspace
                </a>
            </div>
        </div>
    </div>

    <div class="col-12">
        <form action="index.php?route=admin/exams&action=questions&id=<?php echo $exam['id']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="help-circle" class="text-primary"></i>
                        <h5 class="mb-0 fw-semibold">Select Questions & Point Overrides</h5>
                    </div>
                    <span class="badge bg-primary rounded-pill py-2 px-3 font-sans" id="selected-counter">0 questions selected</span>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($allQuestions)): ?>
                        <div class="text-center py-5">
                            <i data-lucide="help-circle" class="text-muted d-block mx-auto mb-3" size="48"></i>
                            <p class="text-muted mb-3">No published questions exist in the Question Bank yet.</p>
                            <a href="index.php?route=admin/questions&action=create" class="btn btn-sm btn-primary">Create a Question</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="questionsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" style="width: 50px;">
                                            <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onclick="toggleSelectAll(this)">
                                        </th>
                                        <th>Question Text</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Difficulty</th>
                                        <th>Default Pts</th>
                                        <th style="width: 150px;">Point Override</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allQuestions as $q): ?>
                                        <?php
                                        // Check if this question is already assigned
                                        $isAssigned = in_array($q['id'], $assignedIds);
                                        
                                        // Retrieve existing override score if assigned
                                        $overridePts = $q['points'];
                                        foreach ($assignedQuestions as $aq) {
                                            if ($aq['question_id'] == $q['id'] && $aq['points_override'] !== null) {
                                                $overridePts = $aq['points_override'];
                                            }
                                        }
                                        ?>
                                        <tr class="<?php echo $isAssigned ? 'table-primary-subtle' : ''; ?>">
                                            <td class="ps-4">
                                                <input type="checkbox" name="questions[]" value="<?php echo $q['id']; ?>" 
                                                       class="form-check-input question-checkbox" 
                                                       <?php echo $isAssigned ? 'checked' : ''; ?>
                                                       onclick="updateRowHighlight(this)">
                                            </td>
                                            <td>
                                                <div class="fw-medium text-dark text-truncate" style="max-width: 400px;" title="<?php echo htmlspecialchars($q['question_text']); ?>">
                                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                                </div>
                                                <small class="text-muted font-sans">ID: <?php echo $q['id']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($q['category_name']); ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted small font-sans"><?php 
                                                    $types = ['mcq_single' => 'MCQ Single', 'mcq_multi' => 'MCQ Multi', 'true_false' => 'T/F', 'fill_blank' => 'Fill Blank', 'matching' => 'Matching', 'essay' => 'Essay'];
                                                    echo $types[$q['type']] ?? $q['type'];
                                                ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $diffClasses = ['easy' => 'bg-success-subtle text-success', 'medium' => 'bg-warning-subtle text-warning-emphasis', 'hard' => 'bg-danger-subtle text-danger'];
                                                $class = $diffClasses[$q['difficulty']] ?? 'bg-light text-dark';
                                                ?>
                                                <span class="badge border <?php echo $class; ?> text-capitalize"><?php echo $q['difficulty']; ?></span>
                                            </td>
                                            <td class="fw-medium text-muted"><?php echo floatval($q['points']); ?></td>
                                            <td>
                                                <input type="number" name="points_override[<?php echo $q['id']; ?>]" 
                                                       class="form-control form-control-sm text-center fw-semibold override-input" 
                                                       value="<?php echo floatval($overridePts); ?>" 
                                                       step="0.25" min="0.25" style="max-width: 100px;">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Checked questions will be stored statically inside this exam.</span>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i data-lucide="save" size="18"></i> Save Assigned Questions
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function updateCounters() {
    const checkedCount = document.querySelectorAll('.question-checkbox:checked').length;
    document.getElementById('selected-counter').textContent = `${checkedCount} question${checkedCount === 1 ? '' : 's'} selected`;
}

function updateRowHighlight(checkbox) {
    const row = checkbox.closest('tr');
    if (checkbox.checked) {
        row.classList.add('table-primary-subtle');
    } else {
        row.classList.remove('table-primary-subtle');
    }
    updateCounters();
}

function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.question-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
        updateRowHighlight(cb);
    });
}

// Initial count on load
updateCounters();
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
