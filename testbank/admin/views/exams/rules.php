<?php
$pageTitle = 'Random Pull Rules - ' . htmlspecialchars($exam['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Dynamic Exam Assembly Rules</h5>
                    <p class="text-muted mb-0">Establish rules to automatically pull a random subset of questions from the Question Bank at the moment a student launches an attempt.</p>
                </div>
                <a href="index.php?route=admin/exams" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" size="16"></i> Back to Workspace
                </a>
            </div>
        </div>
    </div>

    <div class="col-12">
        <form action="index.php?route=admin/exams&action=rules&id=<?php echo $exam['id']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="shuffle" class="text-primary"></i>
                        <h5 class="mb-0 fw-semibold">Configure Random-Pull Rules</h5>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <div class="alert alert-info py-2 px-3 border-0 rounded-3 mb-4 small d-flex align-items-center gap-2">
                        <i data-lucide="info" size="16"></i>
                        <span>Dynamic rules are compiled <strong>in addition</strong> to any manual static question sets you have assigned.</span>
                    </div>

                    <div id="rules-container">
                        <?php if (empty($rules)): ?>
                            <!-- Empty row template if no rules exist yet -->
                            <div class="row g-3 mb-3 rule-row align-items-end" data-index="0">
                                <div class="col-md-5 col-sm-12">
                                    <label class="form-label text-muted small fw-medium">Category Pool</label>
                                    <select name="rules_cat[]" class="form-select" required>
                                        <option value="">-- Choose Category --</option>
                                        <?php foreach ($flatCategories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['indented_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label text-muted small fw-medium">Difficulty</label>
                                    <select name="rules_diff[]" class="form-select">
                                        <option value="any">Any Difficulty</option>
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label text-muted small fw-medium">Pull Count</label>
                                    <input type="number" name="rules_count[]" class="form-control" value="5" min="1" required>
                                </div>
                                <div class="col-md-1 col-sm-12 text-end">
                                    <button type="button" class="btn btn-outline-danger w-100" onclick="removeRuleRow(this)"><i data-lucide="trash-2" size="18"></i></button>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($rules as $idx => $r): ?>
                                <div class="row g-3 mb-3 rule-row align-items-end" data-index="<?php echo $idx; ?>">
                                    <div class="col-md-5 col-sm-12">
                                        <label class="form-label text-muted small fw-medium">Category Pool</label>
                                        <select name="rules_cat[]" class="form-select" required>
                                            <option value="">-- Choose Category --</option>
                                            <?php foreach ($flatCategories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $r['category_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['indented_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label text-muted small fw-medium">Difficulty</label>
                                        <select name="rules_diff[]" class="form-select">
                                            <option value="any" <?php echo $r['difficulty'] == 'any' ? 'selected' : ''; ?>>Any Difficulty</option>
                                            <option value="easy" <?php echo $r['difficulty'] == 'easy' ? 'selected' : ''; ?>>Easy</option>
                                            <option value="medium" <?php echo $r['difficulty'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="hard" <?php echo $r['difficulty'] == 'hard' ? 'selected' : ''; ?>>Hard</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label text-muted small fw-medium">Pull Count</label>
                                        <input type="number" name="rules_count[]" class="form-control" value="<?php echo intval($r['question_count']); ?>" min="1" required>
                                    </div>
                                    <div class="col-md-1 col-sm-12 text-end">
                                        <button type="button" class="btn btn-outline-danger w-100" onclick="removeRuleRow(this)"><i data-lucide="trash-2" size="18"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 mt-3" onclick="addRuleRow()">
                        <i data-lucide="plus" size="16"></i> Add Assembly Rule
                    </button>
                </div>

                <div class="card-footer bg-white py-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i data-lucide="save" size="18"></i> Save Rules Configuration
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let ruleIndex = <?php echo count($rules) ?: 1; ?>;

// List of categories formatted for options injection
const categoriesJson = <?php echo json_encode($flatCategories); ?>;

function addRuleRow() {
    const container = document.getElementById('rules-container');
    
    // Build category select options
    let categoryOptions = '<option value="">-- Choose Category --</option>';
    categoriesJson.forEach(cat => {
        categoryOptions += `<option value="${cat.id}">${escapeHtml(cat.indented_name)}</option>`;
    });

    const row = document.createElement('div');
    row.className = 'row g-3 mb-3 rule-row align-items-end';
    row.dataset.index = ruleIndex;
    row.innerHTML = `
        <div class="col-md-5 col-sm-12">
            <label class="form-label text-muted small fw-medium">Category Pool</label>
            <select name="rules_cat[]" class="form-select" required>
                ${categoryOptions}
            </select>
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label text-muted small fw-medium">Difficulty</label>
            <select name="rules_diff[]" class="form-select">
                <option value="any">Any Difficulty</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select>
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label text-muted small fw-medium">Pull Count</label>
            <input type="number" name="rules_count[]" class="form-control" value="5" min="1" required>
        </div>
        <div class="col-md-1 col-sm-12 text-end">
            <button type="button" class="btn btn-outline-danger w-100" onclick="removeRuleRow(this)"><i data-lucide="trash-2" size="18"></i></button>
        </div>
    `;
    container.appendChild(row);
    ruleIndex++;
    lucide.createIcons();
}

function removeRuleRow(btn) {
    btn.closest('.rule-row').remove();
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
