<?php
/**
 * Exam Builder (Questions & Rules) View
 */
$pageTitle = 'Build Exam Questions';
include __DIR__ . '/../layout_header.php';

$csrfToken = Session::getCSRFToken();
?>

<div class="mb-4">
    <a href="index.php?route=admin/exams" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
        <i data-lucide="arrow-left" size="14"></i>
        <span>Back to Exams</span>
    </a>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-1 display-font">Exam Builder Workspace</h1>
            <p class="text-muted mb-0">Build the active question pool for <strong><?php echo htmlspecialchars($exam['title']); ?></strong> using fixed picks and dynamic pull rules.</p>
        </div>
        <div class="d-inline-flex gap-2">
            <a href="index.php?route=admin/exams&action=preview&id=<?php echo $exam['id']; ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 py-2" target="_blank" id="btn-preview-resolved">
                <i data-lucide="eye" size="18"></i>
                <span>Preview Resolved Set</span>
            </a>
            <a href="index.php?route=admin/exams&action=edit&id=<?php echo $exam['id']; ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 py-2" id="btn-edit-config">
                <i data-lucide="settings" size="18"></i>
                <span>Configure Settings</span>
            </a>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="check-circle" class="text-success"></i>
            <div><?php echo htmlspecialchars($_GET['success']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="alert-triangle" class="text-danger"></i>
            <div><?php echo htmlspecialchars($_GET['error']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Exam summary card -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4" id="exam-summary-strip">
    <div class="card-body p-3">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-3 border-end-md py-2">
                <span class="text-muted small d-block font-sans text-uppercase">Workflow Status</span>
                <?php if ($exam['status'] === 'published'): ?>
                    <span class="badge bg-success text-white font-sans text-uppercase mt-1 px-2 py-1">Published</span>
                <?php elseif ($exam['status'] === 'archived'): ?>
                    <span class="badge bg-danger text-white font-sans text-uppercase mt-1 px-2 py-1">Archived</span>
                <?php else: ?>
                    <span class="badge bg-dark-subtle text-dark font-sans text-uppercase mt-1 px-2 py-1">Draft</span>
                <?php endif; ?>
            </div>
            <div class="col-md-3 border-end-md py-2">
                <span class="text-muted small d-block font-sans text-uppercase">Time Limit</span>
                <span class="fw-semibold text-dark fs-5 d-block mt-1"><?php echo intval($exam['duration_minutes']); ?> mins</span>
            </div>
            <div class="col-md-3 border-end-md py-2">
                <span class="text-muted small d-block font-sans text-uppercase">Passing Percentage</span>
                <span class="fw-semibold text-dark fs-5 d-block mt-1"><?php echo floatval($exam['pass_percentage']); ?>%</span>
            </div>
            <div class="col-md-3 py-2">
                <span class="text-muted small d-block font-sans text-uppercase">Total Pool Count</span>
                <span class="fw-semibold text-primary fs-5 d-block mt-1">
                    <?php 
                    $rulesCount = array_sum(array_column($rules, 'question_count'));
                    echo (count($fixedQuestions) + $rulesCount) . ' Questions'; 
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left column: Fixed questions, overrides, and catalog searching picker -->
    <div class="col-xl-8">
        <!-- Part 1: Selected Fixed Questions -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" id="fixed-questions-card">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="check-square" class="text-primary" size="18"></i>
                    <span>Fixed-Pick Questions (<?php echo count($fixedQuestions); ?>)</span>
                </h5>
                <span class="badge bg-light text-dark border small">Static Ordering</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($fixedQuestions)): ?>
                    <div class="text-center py-5 text-muted">
                        <i data-lucide="list" size="36" class="opacity-25 mb-2"></i>
                        <p class="mb-0 small fw-medium">No fixed questions selected yet.</p>
                        <p class="text-muted small mb-0">Browse and add questions from the catalog picker below.</p>
                    </div>
                <?php else: ?>
                    <form action="index.php?route=admin/exams&action=points_override" method="POST">
                        <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-4 py-2.5 font-sans small text-uppercase fw-semibold" style="width: 8%;">Order</th>
                                        <th class="py-2.5 font-sans small text-uppercase fw-semibold" style="width: 52%;">Question text</th>
                                        <th class="py-2.5 font-sans small text-uppercase fw-semibold" style="width: 15%;">Type & Diff</th>
                                        <th class="py-2.5 font-sans small text-uppercase fw-semibold" style="width: 15%;">Points / Override</th>
                                        <th class="pe-4 py-2.5 font-sans small text-uppercase fw-semibold text-end" style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fixedQuestions as $idx => $fq): ?>
                                        <tr id="fixed-row-<?php echo $fq['id']; ?>">
                                            <td class="ps-4">
                                                <span class="badge bg-light text-dark border rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                                                    <?php echo $idx + 1; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-dark fw-medium small text-truncate" style="max-width: 380px;">
                                                    <?php echo htmlspecialchars(strip_tags($fq['question_text'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small text-muted font-sans text-capitalize" style="font-size: 0.75rem;">
                                                    <span class="d-block text-primary fw-semibold"><?php echo str_replace('_', ' ', $fq['type']); ?></span>
                                                    <span>Diff: <?php echo htmlspecialchars($fq['difficulty']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm" style="max-width: 100px;">
                                                    <input type="number" 
                                                           name="points_override[<?php echo $fq['id']; ?>]" 
                                                           class="form-control" 
                                                           step="0.5" 
                                                           min="0" 
                                                           placeholder="<?php echo floatval($fq['q_points']); ?>" 
                                                           value="<?php echo $fq['points_override'] !== null ? floatval($fq['points_override']) : ''; ?>">
                                                </div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Move Up -->
                                                    <a href="index.php?route=admin/exams&action=reorder_fixed&id=<?php echo $exam['id']; ?>&mapping_id=<?php echo $fq['id']; ?>&dir=up" 
                                                       class="btn btn-light btn-sm border-end <?php echo $idx === 0 ? 'disabled opacity-50' : ''; ?>" 
                                                       title="Move Up"
                                                       id="btn-move-up-<?php echo $fq['id']; ?>">
                                                        <i data-lucide="chevron-up" size="14"></i>
                                                    </a>
                                                    <!-- Move Down -->
                                                    <a href="index.php?route=admin/exams&action=reorder_fixed&id=<?php echo $exam['id']; ?>&mapping_id=<?php echo $fq['id']; ?>&dir=down" 
                                                       class="btn btn-light btn-sm border-end <?php echo $idx === count($fixedQuestions) - 1 ? 'disabled opacity-50' : ''; ?>" 
                                                       title="Move Down"
                                                       id="btn-move-down-<?php echo $fq['id']; ?>">
                                                        <i data-lucide="chevron-down" size="14"></i>
                                                    </a>
                                                    <!-- Remove -->
                                                    <a href="index.php?route=admin/exams&action=remove_fixed&id=<?php echo $exam['id']; ?>&mapping_id=<?php echo $fq['id']; ?>" 
                                                       class="btn btn-light text-danger btn-sm" 
                                                       onclick="return confirm('Remove question from exam?');" 
                                                       title="Remove Question"
                                                       id="btn-remove-fq-<?php echo $fq['id']; ?>">
                                                        <i data-lucide="trash" size="14"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white py-3 border-top text-end">
                            <button type="submit" class="btn btn-primary btn-sm px-3" id="btn-save-points">
                                Save Points Overrides
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Part 2: Searchable Question Bank Picker -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" id="question-picker-card">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="database" class="text-primary" size="18"></i>
                    <span>Browse Question Catalog</span>
                </h5>
                <p class="text-muted small mb-0 mt-1">Search and select published questions to add to your exam's fixed set.</p>
            </div>
            
            <!-- Filters inside picker -->
            <div class="card-body bg-light border-bottom p-3">
                <form method="GET" action="index.php" class="row g-2">
                    <input type="hidden" name="route" value="admin/exams">
                    <input type="hidden" name="action" value="build">
                    <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                    
                    <div class="col-md-3">
                        <select name="q_category_id" class="form-select form-select-sm">
                            <option value="">-- Categories --</option>
                            <?php foreach ($flatCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (($filters['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo str_repeat('&nbsp;&nbsp;', $cat['depth'] ?? 0) . htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select name="q_type" class="form-select form-select-sm">
                            <option value="">-- Types --</option>
                            <option value="mcq_single" <?php echo (($filters['type'] ?? '') === 'mcq_single') ? 'selected' : ''; ?>>Single MCQ</option>
                            <option value="mcq_multi_sata" <?php echo (($filters['type'] ?? '') === 'mcq_multi_sata') ? 'selected' : ''; ?>>SATA (Select All)</option>
                            <option value="true_false" <?php echo (($filters['type'] ?? '') === 'true_false') ? 'selected' : ''; ?>>True/False</option>
                            <option value="matching" <?php echo (($filters['type'] ?? '') === 'matching') ? 'selected' : ''; ?>>Matching</option>
                            <option value="matrix_single" <?php echo (($filters['type'] ?? '') === 'matrix_single') ? 'selected' : ''; ?>>Matrix Single</option>
                            <option value="matrix_multi" <?php echo (($filters['type'] ?? '') === 'matrix_multi') ? 'selected' : ''; ?>>Matrix Multi</option>
                            <option value="bowtie" <?php echo (($filters['type'] ?? '') === 'bowtie') ? 'selected' : ''; ?>>Bowtie (NGN)</option>
                            <option value="mcq_extended" <?php echo (($filters['type'] ?? '') === 'mcq_extended') ? 'selected' : ''; ?>>Extended MCQ</option>
                            <option value="fill_blank_calc" <?php echo (($filters['type'] ?? '') === 'fill_blank_calc') ? 'selected' : ''; ?>>Calculated Fill-In</option>
                            <option value="essay" <?php echo (($filters['type'] ?? '') === 'essay') ? 'selected' : ''; ?>>Essay</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="q_difficulty" class="form-select form-select-sm">
                            <option value="">-- Difficulties --</option>
                            <option value="easy" <?php echo (($filters['difficulty'] ?? '') === 'easy') ? 'selected' : ''; ?>>Easy</option>
                            <option value="medium" <?php echo (($filters['difficulty'] ?? '') === 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="hard" <?php echo (($filters['difficulty'] ?? '') === 'hard') ? 'selected' : ''; ?>>Hard</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <input type="text" name="q_search" class="form-control form-control-sm" placeholder="Search keyword..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                    </div>

                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-secondary btn-sm" title="Search Picker">
                            <i data-lucide="search" size="14"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Picker list results -->
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($pickerQuestions)): ?>
                    <div class="text-center py-5 text-muted">
                        <i data-lucide="help-circle" size="36" class="opacity-25 mb-2"></i>
                        <p class="mb-0 small fw-medium">No matching questions available</p>
                        <p class="text-muted small mb-0">Either they are already added, draft status, or filters are too restrictive.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                <?php foreach ($pickerQuestions as $q): ?>
                                    <tr id="picker-row-<?php echo $q['id']; ?>">
                                        <td class="ps-4 py-2.5" style="max-width: 450px;">
                                            <div class="text-dark small fw-medium text-truncate">
                                                <?php echo htmlspecialchars(strip_tags($q['question_text'])); ?>
                                            </div>
                                            <div class="text-muted font-sans mt-0.5 d-flex gap-2" style="font-size: 0.7rem;">
                                                <span>Cat: <?php echo htmlspecialchars($q['category_name'] ?? 'General'); ?></span>
                                                <span>&bull;</span>
                                                <span class="text-uppercase font-sans text-primary"><?php echo str_replace('_', ' ', $q['type']); ?></span>
                                                <span>&bull;</span>
                                                <span>Pts: <?php echo floatval($q['points'] ?? 1); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 pe-4 text-end">
                                            <form action="index.php?route=admin/exams&action=add_fixed" method="POST" class="m-0">
                                                <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                                                <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <button type="submit" class="btn btn-outline-primary btn-sm py-1 font-sans d-inline-flex align-items-center gap-1" id="btn-add-q-<?php echo $q['id']; ?>">
                                                    <i data-lucide="plus" size="14"></i> Add
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

    <!-- Right column: Random Pull Rules & Builder -->
    <div class="col-xl-4">
        <!-- Part 1: Current Rules List -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" id="rules-card">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="git-pull-request" class="text-primary" size="18"></i>
                    <span>Random-Pull Rules (<?php echo count($rules); ?>)</span>
                </h5>
                <p class="text-muted small mb-0 mt-1">Draw published questions dynamically upon starting each attempt.</p>
            </div>
            <div class="card-body p-0">
                <?php if (empty($rules)): ?>
                    <div class="text-center py-5 text-muted px-3">
                        <i data-lucide="shuffle" size="36" class="opacity-25 mb-2"></i>
                        <p class="mb-0 small fw-medium">No random rules defined yet.</p>
                        <p class="text-muted small mb-0">Use the form below to add dynamic pull criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="ps-3 py-2 font-sans small text-uppercase fw-semibold" style="font-size: 0.75rem;">Rule Criteria</th>
                                    <th class="py-2 font-sans small text-uppercase fw-semibold text-center" style="font-size: 0.75rem; width: 25%;">Count</th>
                                    <th class="pe-3 py-2 font-sans small text-uppercase fw-semibold text-end" style="font-size: 0.75rem; width: 15%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rules as $r): ?>
                                    <tr id="rule-row-<?php echo $r['id']; ?>">
                                        <td class="ps-3 py-2.5">
                                            <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($r['category_name']); ?></div>
                                            <div class="text-muted small text-capitalize font-sans" style="font-size: 0.7rem;">
                                                Diff: <?php echo htmlspecialchars($r['difficulty']); ?>
                                            </div>
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <span class="badge bg-primary text-white font-sans px-2 py-1"><?php echo intval($r['question_count']); ?></span>
                                        </td>
                                        <td class="pe-3 py-2.5 text-end">
                                            <a href="index.php?route=admin/exams&action=remove_rule&id=<?php echo $exam['id']; ?>&rule_id=<?php echo $r['id']; ?>" 
                                               class="btn btn-outline-danger btn-sm p-1 d-inline-flex" 
                                               onclick="return confirm('Remove random pull rule?');"
                                               title="Delete Rule"
                                               id="btn-remove-rule-<?php echo $r['id']; ?>">
                                                <i data-lucide="trash" size="14"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Part 2: Add Rule Form -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" id="add-rule-form-card">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="plus-circle" class="text-primary" size="18"></i>
                    <span>Create Dynamic Rule</span>
                </h5>
            </div>
            <div class="card-body p-3">
                <form action="index.php?route=admin/exams&action=add_rule" method="POST">
                    <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="mb-3">
                        <label for="rule-category" class="form-label font-sans small text-uppercase fw-semibold text-muted">Source Category</label>
                        <select id="rule-category" name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($flatCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $cat['depth'] ?? 0) . htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small" style="font-size: 0.725rem;">Fetches questions from category and child subcategories.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label for="rule-difficulty" class="form-label font-sans small text-uppercase fw-semibold text-muted">Difficulty Filter</label>
                            <select id="rule-difficulty" name="difficulty" class="form-select">
                                <option value="any">Any Difficulty</option>
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="rule-count" class="form-label font-sans small text-uppercase fw-semibold text-muted">Pull Count</label>
                            <input type="number" id="rule-count" name="question_count" class="form-control" min="1" placeholder="e.g. 5" required>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2" id="btn-add-rule-submit">
                            <i data-lucide="shuffle" size="16"></i>
                            <span>Add Random Rule</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
