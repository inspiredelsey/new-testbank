<?php
$pageTitle = 'Edit Question';
include __DIR__ . '/../layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="edit-2" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Edit Question details</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                        <i data-lucide="alert-circle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form action="index.php?route=admin/questions&action=edit&id=<?php echo $question['id']; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                    
                    <div class="row">
                        <!-- Category Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($flatCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $question['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['indented_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Difficulty Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="difficulty" class="form-label fw-medium">Difficulty Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="difficulty" name="difficulty" required>
                                <option value="easy" <?php echo $question['difficulty'] == 'easy' ? 'selected' : ''; ?>>Easy</option>
                                <option value="medium" <?php echo $question['difficulty'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="hard" <?php echo $question['difficulty'] == 'hard' ? 'selected' : ''; ?>>Hard</option>
                            </select>
                        </div>

                        <!-- Question Type Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label fw-medium">Question Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required onchange="toggleTypeEditor(this.value)">
                                <option value="mcq_single" <?php echo $question['type'] == 'mcq_single' ? 'selected' : ''; ?>>Multiple Choice (Single Answer)</option>
                                <option value="mcq_multi" <?php echo $question['type'] == 'mcq_multi' ? 'selected' : ''; ?>>Multiple Choice (Multiple Answers)</option>
                                <option value="true_false" <?php echo $question['type'] == 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                <option value="fill_blank" <?php echo $question['type'] == 'fill_blank' ? 'selected' : ''; ?>>Fill in the Blank</option>
                                <option value="matching" <?php echo $question['type'] == 'matching' ? 'selected' : ''; ?>>Matching Pairs</option>
                                <option value="essay" <?php echo $question['type'] == 'essay' ? 'selected' : ''; ?>>Essay / Free Text</option>
                            </select>
                        </div>

                        <!-- Default Points -->
                        <div class="col-md-6 mb-3">
                            <label for="points" class="form-label fw-medium">Default Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="points" name="points" value="<?php echo floatval($question['points']); ?>" step="0.25" min="0.25" required>
                        </div>

                        <!-- Question Text -->
                        <div class="col-12 mb-3">
                            <label for="question_text" class="form-label fw-medium">Question Prompt / Text <span class="text-danger">*</span></label>
                            <textarea class="form-control font-sans" id="question_text" name="question_text" rows="5" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                        </div>

                        <!-- Question Image Attachment -->
                        <div class="col-md-6 mb-4">
                            <label for="question_image" class="form-label fw-medium">Attachment Image (Optional)</label>
                            <input class="form-control" type="file" id="question_image" name="question_image" accept="image/*">
                            <div class="form-text">Max size 2MB. Permitted: JPG, PNG, GIF. Leave empty to retain current attachment.</div>
                        </div>

                        <!-- Tags -->
                        <div class="col-md-6 mb-4">
                            <label for="tags" class="form-label fw-medium">Tags (Optional)</label>
                            <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($tagsString ?? ''); ?>" placeholder="biology, cells, genetics">
                            <div class="form-text">Comma separated list of keyword tags.</div>
                        </div>
                    </div>

                    <hr class="my-4 text-muted">

                    <!-- Options Editor Areas -->
                    <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-1">
                        <i data-lucide="list-checks" class="text-primary"></i> Answers & Options Editor
                    </h5>

                    <!-- MCQ Section (Single and Multi) -->
                    <div id="mcq-editor-section" class="options-sub-editor mb-4 d-none">
                        <div class="alert alert-info py-2 px-3 border-0 rounded-3 mb-3 small d-flex align-items-center gap-2">
                            <i data-lucide="info" size="16"></i>
                            <span id="mcq-instruction">Mark the radio button of the single correct answer.</span>
                        </div>
                        
                        <div id="mcq-options-container">
                            <?php 
                            $isMcq = ($question['type'] === 'mcq_single' || $question['type'] === 'mcq_multi');
                            $mcqOptions = $isMcq ? $options : [];
                            if (empty($mcqOptions)) {
                                // Default placeholders if type was changed
                                $mcqOptions = [
                                    ['id' => 0, 'option_text' => '', 'is_correct' => 1],
                                    ['id' => 1, 'option_text' => '', 'is_correct' => 0]
                                ];
                            }
                            ?>
                            <?php foreach ($mcqOptions as $idx => $opt): ?>
                                <div class="row align-items-center mb-3 option-row" data-index="<?php echo $idx; ?>">
                                    <div class="col-auto">
                                        <input type="radio" name="mcq_correct_single" value="<?php echo $idx; ?>" class="form-check-input correct-marker-single" <?php echo $opt['is_correct'] ? 'checked' : ''; ?>>
                                        <input type="checkbox" name="mcq_correct_multi[<?php echo $idx; ?>]" class="form-check-input correct-marker-multi d-none" <?php echo $opt['is_correct'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="options[<?php echo $idx; ?>]" class="form-control" value="<?php echo htmlspecialchars($opt['option_text']); ?>" placeholder="Option Answer text..." required>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeOptionRow(this)"><i data-lucide="x"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 mt-2" onclick="addOptionRow()">
                            <i data-lucide="plus" size="14"></i> Add Choice Option
                        </button>
                    </div>

                    <!-- True False Section -->
                    <div id="tf-editor-section" class="options-sub-editor mb-4 d-none">
                        <label class="form-label fw-medium d-block">Correct True/False Answer</label>
                        <?php
                        $tfCorrectVal = 'true';
                        if ($question['type'] === 'true_false') {
                            foreach ($options as $opt) {
                                if ($opt['is_correct']) {
                                    $tfCorrectVal = strtolower($opt['option_text']);
                                }
                            }
                        }
                        ?>
                        <div class="form-check form-check-inline p-3 border rounded border-light-subtle px-4">
                            <input class="form-check-input" type="radio" name="tf_correct" id="tf_correct_true" value="true" <?php echo ($tfCorrectVal === 'true' || $tfCorrectVal === '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium" for="tf_correct_true">True</label>
                        </div>
                        <div class="form-check form-check-inline p-3 border rounded border-light-subtle px-4">
                            <input class="form-check-input" type="radio" name="tf_correct" id="tf_correct_false" value="false" <?php echo ($tfCorrectVal === 'false' || $tfCorrectVal === '0') ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium" for="tf_correct_false">False</label>
                        </div>
                    </div>

                    <!-- Fill in the Blank Section -->
                    <div id="blank-editor-section" class="options-sub-editor mb-4 d-none">
                        <div class="alert alert-info py-2 px-3 border-0 rounded-3 mb-3 small d-flex align-items-center gap-2">
                            <i data-lucide="info" size="16"></i>
                            <span>Specify acceptable correct blank text values (evaluation is case-insensitive).</span>
                        </div>
                        <div id="blank-options-container">
                            <?php 
                            $isBlank = ($question['type'] === 'fill_blank');
                            $blankOptions = $isBlank ? $options : [];
                            if (empty($blankOptions)) {
                                $blankOptions = [['option_text' => '']];
                            }
                            ?>
                            <?php foreach ($blankOptions as $opt): ?>
                                <div class="row mb-2">
                                    <div class="col">
                                        <input type="text" name="blank_answers[]" class="form-control" value="<?php echo htmlspecialchars($opt['option_text']); ?>" placeholder="Accepted exact correct phrase...">
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeBlankRow(this)"><i data-lucide="x"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 mt-2" onclick="addBlankRow()">
                            <i data-lucide="plus" size="14"></i> Add Alternative Correct Phrase
                        </button>
                    </div>

                    <!-- Matching Pairs Section -->
                    <div id="matching-editor-section" class="options-sub-editor mb-4 d-none">
                        <div class="alert alert-info py-2 px-3 border-0 rounded-3 mb-3 small d-flex align-items-center gap-2">
                            <i data-lucide="info" size="16"></i>
                            <span>Enter matching items. Users will match the concept/left item with the term/right item.</span>
                        </div>
                        <div id="matching-options-container">
                            <?php 
                            $isMatching = ($question['type'] === 'matching');
                            $matchingOptions = $isMatching ? $options : [];
                            if (empty($matchingOptions)) {
                                $matchingOptions = [['option_text' => '', 'pair_key' => '']];
                            }
                            ?>
                            <?php foreach ($matchingOptions as $opt): ?>
                                <div class="row g-2 mb-2 matching-row">
                                    <div class="col-md-5">
                                        <input type="text" name="match_left[]" class="form-control" value="<?php echo htmlspecialchars($opt['option_text']); ?>" placeholder="Concept / Left term">
                                    </div>
                                    <div class="col-md-1 text-center align-self-center">
                                        <i data-lucide="arrow-right-left" class="text-muted" size="16"></i>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="match_right[]" class="form-control" value="<?php echo htmlspecialchars($opt['pair_key'] ?? ''); ?>" placeholder="Matching answer / Right term">
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeMatchingRow(this)"><i data-lucide="x"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 mt-2" onclick="addMatchingRow()">
                            <i data-lucide="plus" size="14"></i> Add Matching Pair
                        </button>
                    </div>

                    <!-- Essay Section -->
                    <div id="essay-editor-section" class="options-sub-editor mb-4 d-none">
                        <div class="alert alert-warning py-3 px-3 border-0 rounded-3 mb-0 d-flex align-items-center gap-3">
                            <i data-lucide="check-square" size="24" class="text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Manual Grading Required</h6>
                                <p class="text-muted mb-0 small">Essay questions cannot be auto-graded. They will be routed to the Grading Queue upon submission, and students won't see their final score until you grade it.</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5 border-top pt-4">
                        <a href="index.php?route=admin/questions" class="btn btn-light border d-flex align-items-center gap-2">
                            <i data-lucide="arrow-left" size="18"></i> Back to Bank
                        </a>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i data-lucide="save" size="18"></i> Save Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTypeEditor(type) {
    // Hide all
    document.querySelectorAll('.options-sub-editor').forEach(el => el.classList.add('d-none'));
    
    // Reset option form elements state
    document.querySelectorAll('.correct-marker-single').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.correct-marker-multi').forEach(el => el.classList.add('d-none'));
    
    if (type === 'mcq_single') {
        document.getElementById('mcq-editor-section').classList.remove('d-none');
        document.getElementById('mcq-instruction').textContent = "Mark the radio button of the single correct answer.";
        document.querySelectorAll('.correct-marker-single').forEach(el => el.classList.remove('d-none'));
    } else if (type === 'mcq_multi') {
        document.getElementById('mcq-editor-section').classList.remove('d-none');
        document.getElementById('mcq-instruction').textContent = "Mark checkboxes for all correct answers.";
        document.querySelectorAll('.correct-marker-multi').forEach(el => el.classList.remove('d-none'));
    } else if (type === 'true_false') {
        document.getElementById('tf-editor-section').classList.remove('d-none');
    } else if (type === 'fill_blank') {
        document.getElementById('blank-editor-section').classList.remove('d-none');
    } else if (type === 'matching') {
        document.getElementById('matching-editor-section').classList.remove('d-none');
    } else if (type === 'essay') {
        document.getElementById('essay-editor-section').classList.remove('d-none');
    }
    
    lucide.createIcons();
}

// MCQ options handlers
let mcqIndex = <?php echo count($mcqOptions); ?>;
function addOptionRow() {
    const container = document.getElementById('mcq-options-container');
    const isMulti = document.getElementById('type').value === 'mcq_multi';
    
    const row = document.createElement('div');
    row.className = 'row align-items-center mb-3 option-row';
    row.dataset.index = mcqIndex;
    row.innerHTML = `
        <div class="col-auto">
            <input type="radio" name="mcq_correct_single" value="${mcqIndex}" class="form-check-input correct-marker-single ${isMulti ? 'd-none' : ''}">
            <input type="checkbox" name="mcq_correct_multi[${mcqIndex}]" class="form-check-input correct-marker-multi ${isMulti ? '' : 'd-none'}">
        </div>
        <div class="col">
            <input type="text" name="options[${mcqIndex}]" class="form-control" placeholder="Option Answer text..." required>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeOptionRow(this)"><i data-lucide="x"></i></button>
        </div>
    `;
    container.appendChild(row);
    mcqIndex++;
    lucide.createIcons();
}
function removeOptionRow(btn) {
    const rows = document.querySelectorAll('.option-row');
    if (rows.length <= 2) {
        alert("A multiple choice question must have at least 2 options.");
        return;
    }
    btn.closest('.option-row').remove();
}

// Fill Blank handlers
function addBlankRow() {
    const container = document.getElementById('blank-options-container');
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.innerHTML = `
        <div class="col">
            <input type="text" name="blank_answers[]" class="form-control" placeholder="Accepted exact correct phrase...">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeBlankRow(this)"><i data-lucide="x"></i></button>
        </div>
    `;
    container.appendChild(row);
    lucide.createIcons();
}
function removeBlankRow(btn) {
    btn.closest('.row').remove();
}

// Matching Pairs handlers
function addMatchingRow() {
    const container = document.getElementById('matching-options-container');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 matching-row';
    row.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="match_left[]" class="form-control" placeholder="Concept / Left term">
        </div>
        <div class="col-md-1 text-center align-self-center">
            <i data-lucide="arrow-right-left" class="text-muted" size="16"></i>
        </div>
        <div class="col-md-5">
            <input type="text" name="match_right[]" class="form-control" placeholder="Matching answer / Right term">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeMatchingRow(this)"><i data-lucide="x"></i></button>
        </div>
    `;
    container.appendChild(row);
    lucide.createIcons();
}
function removeMatchingRow(btn) {
    btn.closest('.matching-row').remove();
}

// Initial Call to match selected index
toggleTypeEditor(document.getElementById('type').value);
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
