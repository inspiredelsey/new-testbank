<?php
$pageTitle = 'Question Bank';
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <!-- Filters Toolbar -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="index.php" method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="route" value="admin/questions">
                    
                    <div class="col-md-3 col-sm-6">
                        <label for="search" class="form-label fw-medium text-muted">Search Text</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i data-lucide="search" size="16"></i></span>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Keyword search...">
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label for="category_id" class="form-label fw-medium text-muted">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">-- All Categories --</option>
                            <?php foreach ($flatCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (($_GET['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['indented_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label for="type" class="form-label fw-medium text-muted">Question Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">-- All Types --</option>
                            <option value="mcq_single" <?php echo (($_GET['type'] ?? '') == 'mcq_single') ? 'selected' : ''; ?>>Multiple Choice (Single)</option>
                            <option value="mcq_multi" <?php echo (($_GET['type'] ?? '') == 'mcq_multi') ? 'selected' : ''; ?>>Multiple Choice (Multiple)</option>
                            <option value="true_false" <?php echo (($_GET['type'] ?? '') == 'true_false') ? 'selected' : ''; ?>>True/False</option>
                            <option value="fill_blank" <?php echo (($_GET['type'] ?? '') == 'fill_blank') ? 'selected' : ''; ?>>Fill in the Blank</option>
                            <option value="matching" <?php echo (($_GET['type'] ?? '') == 'matching') ? 'selected' : ''; ?>>Matching</option>
                            <option value="essay" <?php echo (($_GET['type'] ?? '') == 'essay') ? 'selected' : ''; ?>>Essay</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label for="difficulty" class="form-label fw-medium text-muted">Difficulty</label>
                        <select class="form-select" id="difficulty" name="difficulty">
                            <option value="">-- All Levels --</option>
                            <option value="easy" <?php echo (($_GET['difficulty'] ?? '') == 'easy') ? 'selected' : ''; ?>>Easy</option>
                            <option value="medium" <?php echo (($_GET['difficulty'] ?? '') == 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="hard" <?php echo (($_GET['difficulty'] ?? '') == 'hard') ? 'selected' : ''; ?>>Hard</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-12 d-flex gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1 flex-grow-1 flex-md-grow-0">
                            <i data-lucide="filter" size="16"></i> Filter
                        </button>
                        <a href="index.php?route=admin/questions" class="btn btn-light border d-flex align-items-center gap-1">
                            <i data-lucide="rotate-ccw" size="16"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main List & Actions -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="help-circle" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Questions Bank</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?route=admin/questions&action=create" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                        <i data-lucide="plus" size="16"></i> Add Question
                    </a>
                    <a href="index.php?route=admin/questions&action=export" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                        <i data-lucide="download" size="16"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($questions)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="help-circle" class="text-muted d-block mx-auto mb-3" size="48"></i>
                        <p class="text-muted">No questions found matching your filter criteria.</p>
                        <a href="index.php?route=admin/questions&action=create" class="btn btn-sm btn-primary">Create Your First Question</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Question Text</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Difficulty</th>
                                    <th>Points</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $q): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-medium text-dark text-truncate" style="max-width: 300px;">
                                                <?php echo htmlspecialchars($q['question_text']); ?>
                                            </div>
                                            <small class="text-muted font-sans">ID: <?php echo $q['id']; ?> | Status: <span class="badge bg-light text-dark"><?php echo $q['status']; ?></span></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($q['category_name']); ?></span>
                                        </td>
                                        <td>
                                            <span class="text-muted font-sans" style="font-size: 0.85rem;"><?php 
                                                $types = ['mcq_single' => 'MCQ Single', 'mcq_multi' => 'MCQ Multi', 'true_false' => 'T/F', 'fill_blank' => 'Fill Blank', 'matching' => 'Matching', 'essay' => 'Essay'];
                                                echo $types[$q['type']] ?? $q['type'];
                                            ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $diffClasses = ['easy' => 'bg-success-subtle text-success border-success-subtle', 'medium' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle', 'hard' => 'bg-danger-subtle text-danger border-danger-subtle'];
                                            $class = $diffClasses[$q['difficulty']] ?? 'bg-light text-dark';
                                            ?>
                                            <span class="badge border <?php echo $class; ?> text-capitalize"><?php echo $q['difficulty']; ?></span>
                                        </td>
                                        <td class="fw-medium"><?php echo floatval($q['points']); ?></td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="index.php?route=admin/questions&action=edit&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                                                    <i data-lucide="edit-2" size="14"></i> Edit
                                                </a>
                                                <a href="index.php?route=admin/questions&action=delete&id=<?php echo $q['id']; ?>&csrf_token=<?php echo Session::getCSRFToken(); ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this question?')" 
                                                   class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                                                    <i data-lucide="trash" size="14"></i> Delete
                                                </a>
                                            </div>
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

    <!-- CSV Bulk Import Panel -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="upload" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Bulk Import (CSV)</h5>
            </div>
            <div class="card-body p-4">
                <form action="index.php?route=admin/questions&action=import" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="csv_file" class="form-label fw-medium">Upload CSV File</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" required accept=".csv">
                        <div class="form-text">Choose file to upload questions in bulk.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="upload-cloud" size="18"></i> Import Questions
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Format Guide -->
        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark d-flex align-items-center gap-1 mb-3">
                    <i data-lucide="info" size="16" class="text-primary"></i> CSV Column Format Guide
                </h6>
                <p class="text-muted small">Your CSV must include a header row and follow this precise 7-column order:</p>
                <ol class="small text-muted ps-3 mb-0">
                    <li><strong>Category</strong>: Text name (will match or auto-create).</li>
                    <li><strong>Type</strong>: <code>mcq_single</code>, <code>mcq_multi</code>, <code>true_false</code>, <code>fill_blank</code>, <code>matching</code>, <code>essay</code></li>
                    <li><strong>Question Text</strong>: The core prompt.</li>
                    <li><strong>Difficulty</strong>: <code>easy</code>, <code>medium</code>, or <code>hard</code></li>
                    <li><strong>Points</strong>: Numeric value.</li>
                    <li><strong>Options/Answers</strong>: Format varies by type:
                        <ul class="ps-3 mt-1">
                            <li>MCQ: <code>OptionA|is_correct=1;OptionB|is_correct=0</code></li>
                            <li>T/F: <code>true</code> or <code>false</code></li>
                            <li>Fill Blank: <code>Ans1;Ans2;Ans3</code> (alternative list)</li>
                            <li>Matching: <code>ConceptA|pair_key=TermA;ConceptB|pair_key=TermB</code></li>
                        </ul>
                    </li>
                    <li><strong>Tags</strong> (Optional): Semicolon-delimited list e.g. <code>chemistry;atoms</code></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
