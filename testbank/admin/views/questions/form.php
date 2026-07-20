<?php
$isEdit = !empty($question);
$pageTitle = $isEdit ? 'Edit Question' : 'Create Standalone Question';
include __DIR__ . '/../layout_header.php';

// Prepare prefilled arrays
$optionsList = $qData['options'] ?? [];
$leftItems = $qData['left'] ?? [];
$rightItems = $qData['right'] ?? [];
$correctPairs = $qData['correct_pairs'] ?? [];

// Helper mapping for matching dropdowns
$pairMap = [];
foreach ($correctPairs as $p) {
    // Left side ID mapped to Right side ID
    $pairMap[$p[0]] = $p[1];
}
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="index.php?route=admin/questions&action=list" class="btn btn-light border p-2 rounded-3 d-flex align-items-center">
            <i data-lucide="arrow-left" size="18" class="text-muted"></i>
        </a>
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold"><?php echo $pageTitle; ?></h1>
            <p class="text-muted mb-0"><?php echo $isEdit ? 'Update clinical question contents and scoring options.' : 'Create a brand new standalone item or map to a case study.'; ?></p>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
            <i data-lucide="alert-circle" class="text-danger"></i>
            <div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form action="" method="POST" id="questionForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="row">
            <!-- Left Side: Basic Meta Configuration -->
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-semibold text-secondary d-flex align-items-center gap-2">
                            <i data-lucide="settings" class="text-primary"></i> Properties
                        </h5>
                    </div>
                    <div class="card-body p-4 d-flex flex-column gap-3">
                        <div>
                            <label for="category_id" class="form-label fw-semibold text-muted small">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="" disabled selected>-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="type" class="form-label fw-semibold text-muted small">Question Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required onchange="onTypeChanged()">
                                <optgroup label="Supported Question Types">
                                    <option value="mcq_single" <?php echo ($type === 'mcq_single') ? 'selected' : ''; ?>>Multiple Choice (Single)</option>
                                    <option value="mcq_multi_sata" <?php echo ($type === 'mcq_multi_sata') ? 'selected' : ''; ?>>Multiple Choice (SATA)</option>
                                    <option value="true_false" <?php echo ($type === 'true_false') ? 'selected' : ''; ?>>True/False</option>
                                    <option value="matching" <?php echo ($type === 'matching') ? 'selected' : ''; ?>>Matching</option>
                                    <option value="matrix_single" <?php echo ($type === 'matrix_single') ? 'selected' : ''; ?>>Matrix Single Select</option>
                                    <option value="matrix_multi" <?php echo ($type === 'matrix_multi') ? 'selected' : ''; ?>>Matrix Multiple Select</option>
                                    <option value="cloze_dropdown" <?php echo ($type === 'cloze_dropdown') ? 'selected' : ''; ?>>Cloze Dropdown</option>
                                    <option value="cloze_dragdrop" <?php echo ($type === 'cloze_dragdrop') ? 'selected' : ''; ?>>Cloze Drag and Drop</option>
                                    <option value="drag_drop_ordered" <?php echo ($type === 'drag_drop_ordered') ? 'selected' : ''; ?>>Drag and Drop Ordered</option>
                                    <option value="highlight" <?php echo ($type === 'highlight') ? 'selected' : ''; ?>>Highlight Select</option>
                                </optgroup>
                                <optgroup label="Coming Soon (NGN Complex Types)" disabled>
                                    <option value="mcq_extended">Extended MCQ (coming soon)</option>
                                    <option value="bowtie">Bowtie Scenario (coming soon)</option>
                                    <option value="fill_blank_calc">Calculated Fill Blank (coming soon)</option>
                                    <option value="essay">Essay Response (coming soon)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <label for="difficulty" class="form-label fw-semibold text-muted small">Difficulty <span class="text-danger">*</span></label>
                                <select class="form-select text-capitalize" id="difficulty" name="difficulty" required>
                                    <option value="easy" <?php echo ($difficulty === 'easy') ? 'selected' : ''; ?>>Easy</option>
                                    <option value="medium" <?php echo ($difficulty === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="hard" <?php echo ($difficulty === 'hard') ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="points" class="form-label fw-semibold text-muted small">Points <span class="text-danger">*</span></label>
                                <input type="number" step="0.25" class="form-control" id="points" name="points" value="<?php echo htmlspecialchars($points); ?>" required min="0.25">
                            </div>
                        </div>

                        <div>
                            <label for="scoring_method" class="form-label fw-semibold text-muted small">Scoring Method</label>
                            <select class="form-select" id="scoring_method" name="scoring_method">
                                <option value="all_or_nothing" <?php echo ($scoring_method === 'all_or_nothing') ? 'selected' : ''; ?>>All or Nothing (Default)</option>
                                <option value="partial_credit" <?php echo ($scoring_method === 'partial_credit') ? 'selected' : ''; ?>>Partial Credit</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <label for="case_id" class="form-label fw-semibold text-muted small">Case Study (Optional)</label>
                                <select class="form-select" id="case_id" name="case_id">
                                    <option value="">-- No Case Study --</option>
                                    <?php foreach ($cases as $cs): ?>
                                        <option value="<?php echo $cs['id']; ?>" <?php echo ($case_id == $cs['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cs['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="case_order" class="form-label fw-semibold text-muted small">Case Position / Order</label>
                                <input type="number" class="form-control" id="case_order" name="case_order" value="<?php echo htmlspecialchars($case_order ?? ''); ?>" placeholder="e.g. 1">
                            </div>
                        </div>

                        <div>
                            <label for="status" class="form-label fw-semibold text-muted small">Publishing Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo ($status === 'published') ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Text & Interactive Choices Area -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-semibold text-secondary d-flex align-items-center gap-2">
                            <i data-lucide="file-text" class="text-primary"></i> Content & Options Setup
                        </h5>
                    </div>
                    <div class="card-body p-4 d-flex flex-column gap-4">
                        <div>
                            <label for="question_text" class="form-label fw-semibold text-dark">Question Text / Instruction Prompt <span class="text-danger">*</span></label>
                            <textarea class="form-control font-sans text-dark" id="question_text" name="question_text" rows="4" placeholder="Enter clinical scenario item instructions, or general prompt..." required><?php echo htmlspecialchars($question_text); ?></textarea>
                        </div>

                        <!-- SUB FORM FOR MCQ & SATA -->
                        <div id="subFormMcq" class="type-sub-form">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-secondary">Response Options Configuration</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" onclick="addMcqRow()">
                                    <i data-lucide="plus" size="14"></i> Add Option Row
                                </button>
                            </div>
                            <div class="d-flex flex-column gap-2" id="mcqRowsContainer">
                                <!-- JS-Injected option lines go here -->
                            </div>
                        </div>

                        <!-- SUB FORM FOR TRUE FALSE -->
                        <div id="subFormTrueFalse" class="type-sub-form">
                            <h6 class="fw-bold mb-3 text-secondary">True / False Answer Key Selection</h6>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check p-3 rounded-3 border d-flex align-items-center bg-light">
                                    <input class="form-check-input ms-0 me-3" type="radio" name="correct_option" id="tf_true" value="true" checked>
                                    <label class="form-check-label fw-bold cursor-pointer mb-0" for="tf_true">TRUE</label>
                                </div>
                                <div class="form-check p-3 rounded-3 border d-flex align-items-center bg-light">
                                    <input class="form-check-input ms-0 me-3" type="radio" name="correct_option" id="tf_false" value="false">
                                    <label class="form-check-label fw-bold cursor-pointer mb-0" for="tf_false">FALSE</label>
                                </div>
                            </div>
                        </div>

                        <!-- SUB FORM FOR MATCHING -->
                        <div id="subFormMatching" class="type-sub-form">
                            <div class="row">
                                <!-- Left items list creator -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-secondary">Left Column (Concepts)</h6>
                                        <button type="button" class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1" onclick="addLeftItem()">
                                            <i data-lucide="plus" size="12"></i> Add Item
                                        </button>
                                    </div>
                                    <div id="leftItemsContainer" class="d-flex flex-column gap-2"></div>
                                </div>

                                <!-- Right items list creator -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-secondary">Right Column (Terms)</h6>
                                        <button type="button" class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1" onclick="addRightItem()">
                                            <i data-lucide="plus" size="12"></i> Add Item
                                        </button>
                                    </div>
                                    <div id="rightItemsContainer" class="d-flex flex-column gap-2"></div>
                                </div>
                            </div>

                            <!-- Interactive Correct Pairs Mapper -->
                            <div class="border-top pt-4">
                                <h6 class="fw-bold mb-3 text-secondary">Define Correct Matching Term Connections</h6>
                                <div class="alert alert-info py-2 border-0 rounded-3 small">
                                    Every left item must select its correct matching partner from the right side.
                                </div>
                                <div id="matchingPairsMapper" class="d-flex flex-column gap-2">
                                    <!-- Populated dynamically based on items currently written in left/right above -->
                                </div>
                            </div>
                        </div>

                        <!-- SUB FORM FOR MATRIX SINGLE / MULTI -->
                        <div id="subFormMatrix" class="type-sub-form d-none">
                            <div class="row">
                                <!-- Rows Configuration -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-secondary">Matrix Rows (Findings / Questions)</h6>
                                        <button type="button" class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1" onclick="addMatrixRow()">
                                            <i data-lucide="plus" size="12"></i> Add Row
                                        </button>
                                    </div>
                                    <div id="matrixRowsContainer" class="d-flex flex-column gap-2"></div>
                                </div>

                                <!-- Columns Configuration -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-secondary">Matrix Columns (Options / Headers)</h6>
                                        <button type="button" class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1" onclick="addMatrixCol()">
                                            <i data-lucide="plus" size="12"></i> Add Column
                                        </button>
                                    </div>
                                    <div id="matrixColsContainer" class="d-flex flex-column gap-2"></div>
                                </div>
                            </div>

                            <!-- Interactive Grid Mapping -->
                            <div class="border-top pt-4">
                                <h6 class="fw-bold mb-3 text-secondary">Mark Correct Cell Answers</h6>
                                <div class="alert alert-info py-2 border-0 rounded-3 small">
                                    Define which column option is correct for each row finding. For single select, choose exactly one per row. For multi-select, you can choose multiple.
                                </div>
                                <div id="matrixGridContainer">
                                    <!-- Interactive Table generated dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- SUB FORM FOR CLOZE DROPDOWN / DRAGDROP -->
                        <div id="subFormCloze" class="type-sub-form d-none">
                            <div class="mb-4">
                                <label for="cloze_passage" class="form-label fw-bold text-secondary">Interactive Cloze Passage <span class="text-danger">*</span></label>
                                <div class="alert alert-info py-2 border-0 rounded-3 small">
                                    Type your text below. Use <strong>{{blank1}}</strong>, <strong>{{blank2}}</strong>, etc., as placeholders where blanks/dropdowns should appear.
                                </div>
                                <textarea class="form-control font-sans text-dark" id="cloze_passage" name="cloze_passage" rows="6" placeholder="The patient is presenting with {{blank1}} indicative of {{blank2}}..."><?php echo htmlspecialchars($qData['passage'] ?? ''); ?></textarea>
                            </div>

                            <div class="border-top pt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-secondary">Define Options per Blank Placeholder</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" onclick="addClozeBlankRow()">
                                        <i data-lucide="plus" size="14"></i> Add Blank Definition
                                    </button>
                                </div>
                                <div id="clozeBlanksContainer" class="d-flex flex-column gap-3">
                                    <!-- Dynamic rows go here -->
                                </div>
                            </div>
                        </div>

                        <!-- SUB FORM FOR DRAG AND DROP ORDERED -->
                        <div id="subFormDragDropOrdered" class="type-sub-form d-none">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-secondary">Correct Sequence Items</h6>
                                            <small class="text-muted">Set items in their correct sequential order (top to bottom).</small>
                                        </div>
                                        <button type="button" class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1" onclick="addDragDropCorrectRow()">
                                            <i data-lucide="plus" size="12"></i> Add Item
                                        </button>
                                    </div>
                                    <div id="dragDropCorrectContainer" class="d-flex flex-column gap-2">
                                        <!-- Dynamic inputs go here -->
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-secondary">Distractor Items (Optional)</h6>
                                            <small class="text-muted">Extra decoy options that should NOT be in the sequence.</small>
                                        </div>
                                        <button type="button" class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1" onclick="addDragDropDistractorRow()">
                                            <i data-lucide="plus" size="12"></i> Add Distractor
                                        </button>
                                    </div>
                                    <div id="dragDropDistractorContainer" class="d-flex flex-column gap-2">
                                        <!-- Dynamic inputs go here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUB FORM FOR HIGHLIGHT -->
                        <div id="subFormHighlight" class="type-sub-form d-none">
                            <div class="mb-4">
                                <label for="highlight_passage_html" class="form-label fw-bold text-secondary">Passage HTML / Text <span class="text-danger">*</span></label>
                                <div class="alert alert-info py-2 border-0 rounded-3 small mb-2">
                                    Paste or type the full clinical passage below. You can use safe formatting tags like <code>&lt;em&gt;</code> and <code>&lt;strong&gt;</code> to style text.
                                </div>
                                <textarea class="form-control font-sans text-dark" id="highlight_passage_html" name="highlight_passage_html" rows="5" placeholder="The patient reports severe chest pain radiating to the left arm..."><?php echo htmlspecialchars($qData['passage_html'] ?? ''); ?></textarea>
                            </div>

                            <div class="border-top pt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-secondary">Selectable Text Segments</h6>
                                        <small class="text-muted">Define the exact substrings of the passage that students can select, and mark the correct ones.</small>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1" onclick="addHighlightSegmentRow()">
                                        <i data-lucide="plus" size="12"></i> Add Segment
                                    </button>
                                </div>
                                <div id="highlightSegmentsContainer" class="d-flex flex-column gap-3">
                                    <!-- Dynamic rows go here -->
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-4 mt-auto d-flex gap-2 justify-content-end">
                            <a href="index.php?route=admin/questions&action=list" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                                <i data-lucide="save" size="18"></i> <?php echo $isEdit ? 'Save Changes' : 'Create Question'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Prefilled PHP data loaded safely into JS
const initialType = <?php echo json_encode($type); ?>;
const initialOptions = <?php echo json_encode($optionsList); ?>;
const initialLeft = <?php echo json_encode($leftItems); ?>;
const initialRight = <?php echo json_encode($rightItems); ?>;
const initialPairsMap = <?php echo json_encode($pairMap); ?>;
const qDataCorrectMap = <?php echo ($type === 'matrix_single' || $type === 'matrix_multi') ? json_encode($qData['correct'] ?? []) : 'null'; ?>;

function onTypeChanged() {
    const selectedType = document.getElementById('type').value;
    
    // Hide all sub forms
    document.querySelectorAll('.type-sub-form').forEach(el => el.classList.add('d-none'));

    if (selectedType === 'mcq_single' || selectedType === 'mcq_multi_sata') {
        document.getElementById('subFormMcq').classList.remove('d-none');
        // Force rendering rows if empty
        if (document.getElementById('mcqRowsContainer').children.length === 0) {
            addMcqRow();
            addMcqRow();
        }
    } else if (selectedType === 'true_false') {
        document.getElementById('subFormTrueFalse').classList.remove('d-none');
    } else if (selectedType === 'matching') {
        document.getElementById('subFormMatching').classList.remove('d-none');
        if (document.getElementById('leftItemsContainer').children.length === 0) {
            addLeftItem();
            addRightItem();
        }
    } else if (selectedType === 'matrix_single' || selectedType === 'matrix_multi') {
        document.getElementById('subFormMatrix').classList.remove('d-none');
        if (document.getElementById('matrixRowsContainer').children.length === 0) {
            addMatrixRow();
            addMatrixRow();
        }
        if (document.getElementById('matrixColsContainer').children.length === 0) {
            addMatrixCol();
            addMatrixCol();
        }
    } else if (selectedType === 'cloze_dropdown' || selectedType === 'cloze_dragdrop') {
        document.getElementById('subFormCloze').classList.remove('d-none');
        if (document.getElementById('clozeBlanksContainer').children.length === 0) {
            addClozeBlankRow();
        }
    } else if (selectedType === 'drag_drop_ordered') {
        document.getElementById('subFormDragDropOrdered').classList.remove('d-none');
        if (document.getElementById('dragDropCorrectContainer').children.length === 0) {
            addDragDropCorrectRow();
            addDragDropCorrectRow();
        }
    } else if (selectedType === 'highlight') {
        document.getElementById('subFormHighlight').classList.remove('d-none');
        if (document.getElementById('highlightSegmentsContainer').children.length === 0) {
            addHighlightSegmentRow();
        }
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// MCQ Single & SATA dynamic rows
let mcqCounter = 0;
function addMcqRow(textValue = '', isCorrect = false) {
    const container = document.getElementById('mcqRowsContainer');
    const selectedType = document.getElementById('type').value;
    const inputType = (selectedType === 'mcq_single') ? 'radio' : 'checkbox';
    const inputName = (selectedType === 'mcq_single') ? 'correct_option' : 'correct_options[]';
    
    // In edit mode we can set checked if matches
    const checkedAttr = isCorrect ? 'checked' : '';
    const uniqueId = `mcq_row_${mcqCounter}`;

    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'd-flex align-items-center gap-3 p-2 border rounded-3 bg-light';
    div.innerHTML = `
        <div class="form-check mb-0">
            <input class="form-check-input ms-0 mt-0 pointer-pointer" type="${inputType}" name="${inputName}" value="${mcqCounter}" id="check_${uniqueId}" ${checkedAttr} style="transform: scale(1.15);">
        </div>
        <div class="flex-grow-1">
            <input type="text" class="form-control form-control-sm border-0 bg-transparent font-sans text-dark" name="options[${mcqCounter}]" value="${escapeHtml(textValue)}" placeholder="Enter option choice text..." required>
        </div>
        <button type="button" class="btn btn-link text-danger p-1" onclick="removeMcqRow('${uniqueId}')">
            <i data-lucide="trash-2" size="16"></i>
        </button>
    `;
    container.appendChild(div);
    mcqCounter++;
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function removeMcqRow(rowId) {
    const container = document.getElementById('mcqRowsContainer');
    if (container.children.length <= 1) {
        alert("Assessment items require at least one choice option.");
        return;
    }
    document.getElementById(rowId).remove();
}

// Matching dynamic items
let leftCounter = 0;
let rightCounter = 0;

function addLeftItem(textValue = '') {
    const container = document.getElementById('leftItemsContainer');
    const uniqueId = `left_row_${leftCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'd-flex align-items-center gap-2 border rounded p-2 bg-light';
    div.innerHTML = `
        <span class="badge bg-secondary rounded-pill font-mono">L${leftCounter + 1}</span>
        <input type="text" class="form-control form-control-sm border-0 bg-transparent" name="left_items[${leftCounter}]" value="${escapeHtml(textValue)}" oninput="updateMatchingPairsUI()" placeholder="Concept..." required>
        <button type="button" class="btn btn-link text-danger p-0 ms-auto" onclick="removeLeftItem('${uniqueId}')"><i data-lucide="x" size="14"></i></button>
    `;
    container.appendChild(div);
    leftCounter++;
    updateMatchingPairsUI();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeLeftItem(rowId) {
    document.getElementById(rowId).remove();
    updateMatchingPairsUI();
}

function addRightItem(textValue = '') {
    const container = document.getElementById('rightItemsContainer');
    const uniqueId = `right_row_${rightCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'd-flex align-items-center gap-2 border rounded p-2 bg-light';
    div.innerHTML = `
        <span class="badge bg-secondary rounded-pill font-mono">R${rightCounter + 1}</span>
        <input type="text" class="form-control form-control-sm border-0 bg-transparent" name="right_items[${rightCounter}]" value="${escapeHtml(textValue)}" oninput="updateMatchingPairsUI()" placeholder="Match Term..." required>
        <button type="button" class="btn btn-link text-danger p-0 ms-auto" onclick="removeRightItem('${uniqueId}')"><i data-lucide="x" size="14"></i></button>
    `;
    container.appendChild(div);
    rightCounter++;
    updateMatchingPairsUI();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeRightItem(rowId) {
    document.getElementById(rowId).remove();
    updateMatchingPairsUI();
}

// Render connection mapping fields between Left side and Right side
function updateMatchingPairsUI() {
    const mapper = document.getElementById('matchingPairsMapper');
    
    // Scan all left inputs
    const leftRows = document.querySelectorAll('#leftItemsContainer div');
    const rightRows = document.querySelectorAll('#rightItemsContainer div');
    
    // Save current selected values to preserve choices during typing re-draws
    const selections = {};
    document.querySelectorAll('.match-dropdown-map').forEach(select => {
        selections[select.dataset.leftIndex] = select.value;
    });

    mapper.innerHTML = '';

    if (leftRows.length === 0 || rightRows.length === 0) {
        mapper.innerHTML = '<span class="text-muted small">Add left and right options above to define connections.</span>';
        return;
    }

    leftRows.forEach(lRow => {
        const input = lRow.querySelector('input');
        const text = input.value.trim() || `(Concept ${lRow.id})`;
        const matches = lRow.id.match(/\d+$/);
        const lIndex = matches ? matches[0] : '';

        // Generate right dropdown select choices
        let optionsHtml = `<option value="">-- Select Matching Term --</option>`;
        rightRows.forEach(rRow => {
            const rInput = rRow.querySelector('input');
            const rText = rInput.value.trim() || `(Term ${rRow.id})`;
            const rMatches = rRow.id.match(/\d+$/);
            const rIndex = rMatches ? rMatches[0] : '';
            
            // Re-select if it was previously chosen
            let selectedAttr = '';
            if (selections[lIndex] == rIndex) {
                selectedAttr = 'selected';
            } else if (initialPairsMap && lRow.dataset && rRow.dataset) {
                // Initial load mapping helper
                const initialLeftId = lRow.dataset.id;
                const initialRightId = rRow.dataset.id;
                if (initialPairsMap[initialLeftId] === initialRightId) {
                    selectedAttr = 'selected';
                }
            }

            optionsHtml += `<option value="${rIndex}" ${selectedAttr}>${escapeHtml(rText)}</option>`;
        });

        const div = document.createElement('div');
        div.className = 'row align-items-center g-2 p-2 rounded border bg-white';
        div.innerHTML = `
            <div class="col-md-5">
                <span class="fw-semibold text-dark small">${escapeHtml(text)}</span>
            </div>
            <div class="col-md-2 text-center text-muted small"><i data-lucide="arrow-right-left" size="14" class="mx-1"></i> matches with</div>
            <div class="col-md-5">
                <select class="form-select form-select-sm match-dropdown-map" name="match_pair[${lIndex}]" data-left-index="${lIndex}" required>
                    ${optionsHtml}
                </select>
            </div>
        `;
        mapper.appendChild(div);
    });

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Escape helper for safe dynamic JS inserts
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

let matrixRowCounter = 0;
let matrixColCounter = 0;

function addMatrixRow(textValue = '') {
    const container = document.getElementById('matrixRowsContainer');
    const uniqueId = `matrix_row_item_${matrixRowCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'd-flex align-items-center gap-2 border rounded p-2 bg-light';
    div.innerHTML = `
        <span class="badge bg-secondary rounded-pill font-mono">Row ${matrixRowCounter + 1}</span>
        <input type="text" class="form-control form-control-sm border-0 bg-transparent" name="matrix_rows[${matrixRowCounter}]" value="${escapeHtml(textValue)}" oninput="updateMatrixGridUI()" placeholder="Finding label..." required>
        <button type="button" class="btn btn-link text-danger p-0 ms-auto" onclick="removeMatrixRow('${uniqueId}')"><i data-lucide="x" size="14"></i></button>
    `;
    container.appendChild(div);
    matrixRowCounter++;
    updateMatrixGridUI();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeMatrixRow(rowId) {
    document.getElementById(rowId).remove();
    updateMatrixGridUI();
}

function addMatrixCol(textValue = '') {
    const container = document.getElementById('matrixColsContainer');
    const uniqueId = `matrix_col_item_${matrixColCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'd-flex align-items-center gap-2 border rounded p-2 bg-light';
    div.innerHTML = `
        <span class="badge bg-secondary rounded-pill font-mono">Col ${matrixColCounter + 1}</span>
        <input type="text" class="form-control form-control-sm border-0 bg-transparent" name="matrix_columns[${matrixColCounter}]" value="${escapeHtml(textValue)}" oninput="updateMatrixGridUI()" placeholder="Header label..." required>
        <button type="button" class="btn btn-link text-danger p-0 ms-auto" onclick="removeMatrixCol('${uniqueId}')"><i data-lucide="x" size="14"></i></button>
    `;
    container.appendChild(div);
    matrixColCounter++;
    updateMatrixGridUI();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeMatrixCol(colId) {
    document.getElementById(colId).remove();
    updateMatrixGridUI();
}

function updateMatrixGridUI() {
    const gridContainer = document.getElementById('matrixGridContainer');
    const selectedType = document.getElementById('type').value;
    const isMulti = (selectedType === 'matrix_multi');
    const inputType = isMulti ? 'checkbox' : 'radio';

    const rowDivs = document.querySelectorAll('#matrixRowsContainer div');
    const colDivs = document.querySelectorAll('#matrixColsContainer div');

    const checkedStates = {};
    document.querySelectorAll('.matrix-grid-input').forEach(input => {
        if (input.checked) {
            checkedStates[`${input.dataset.rowIdx}_${input.dataset.colIdx}`] = true;
        }
    });

    gridContainer.innerHTML = '';

    if (rowDivs.length === 0 || colDivs.length === 0) {
        gridContainer.innerHTML = '<span class="text-muted small">Add both rows and columns above to view the answer grid.</span>';
        return;
    }

    let tableHtml = `<div class="table-responsive"><table class="table table-bordered align-middle text-center font-sans mb-0">`;
    tableHtml += `<thead class="table-light text-muted small uppercase"><tr><th class="text-start" style="width: 40%;">Findings / Rows</th>`;
    colDivs.forEach(cDiv => {
        const input = cDiv.querySelector('input');
        const text = input.value.trim() || `(Column ${cDiv.id})`;
        tableHtml += `<th>${escapeHtml(text)}</th>`;
    });
    tableHtml += `</tr></thead><tbody>`;

    rowDivs.forEach(rDiv => {
        const input = rDiv.querySelector('input');
        const text = input.value.trim() || `(Row ${rDiv.id})`;
        const rMatches = rDiv.id.match(/\d+$/);
        const rIdx = rMatches ? rMatches[0] : '';

        tableHtml += `<tr><td class="text-start fw-medium text-dark">${escapeHtml(text)}</td>`;
        
        colDivs.forEach(cDiv => {
            const cMatches = cDiv.id.match(/\d+$/);
            const cIdx = cMatches ? cMatches[0] : '';

            const inputName = isMulti ? `matrix_correct_multi[${rIdx}][]` : `matrix_correct_single[${rIdx}]`;
            const key = `${rIdx}_${cIdx}`;
            
            let isChecked = false;
            if (checkedStates[key]) {
                isChecked = true;
            } else if (initialType === selectedType && initialType && rDiv.dataset && cDiv.dataset) {
                const rowId = rDiv.dataset.id;
                const colId = cDiv.dataset.id;
                const correctCols = (qDataCorrectMap && qDataCorrectMap[rowId]) ? qDataCorrectMap[rowId] : [];
                if (correctCols.includes(colId)) {
                    isChecked = true;
                }
            }

            tableHtml += `<td>
                <input class="form-check-input matrix-grid-input mt-0" type="${inputType}" name="${inputName}" value="${cIdx}" data-row-idx="${rIdx}" data-col-idx="${cIdx}" ${isChecked ? 'checked' : ''} style="transform: scale(1.1); cursor: pointer;">
            </td>`;
        });
        tableHtml += `</tr>`;
    });

    tableHtml += `</tbody></table></div>`;
    gridContainer.innerHTML = tableHtml;
}

let clozeCounter = 0;

function addClozeBlankRow(idValue = '', optionsValue = '', correctValue = '') {
    const container = document.getElementById('clozeBlanksContainer');
    const uniqueId = `cloze_row_item_${clozeCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'p-3 border rounded-3 bg-light position-relative d-flex flex-column gap-2';
    div.innerHTML = `
        <button type="button" class="btn btn-link text-danger p-0 position-absolute top-0 end-0 mt-2 me-2" onclick="removeClozeBlankRow('${uniqueId}')">
            <i data-lucide="trash-2" size="16"></i>
        </button>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Placeholder ID</label>
                <input type="text" class="form-control form-control-sm cloze-blank-id-input font-mono" name="cloze_blank_id[${clozeCounter}]" value="${escapeHtml(idValue)}" placeholder="e.g. blank1" required>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted mb-1">Comma-Separated Options</label>
                <input type="text" class="form-control form-control-sm" name="cloze_blank_options[${clozeCounter}]" value="${escapeHtml(optionsValue)}" placeholder="e.g. hypokalemia, hyperkalemia, normal" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Correct Answer</label>
                <input type="text" class="form-control form-control-sm" name="cloze_blank_correct[${clozeCounter}]" value="${escapeHtml(correctValue)}" placeholder="e.g. hypokalemia" required>
            </div>
        </div>
    `;
    container.appendChild(div);
    clozeCounter++;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeClozeBlankRow(rowId) {
    document.getElementById(rowId).remove();
}

let dragDropCorrectCounter = 0;
let dragDropDistractorCounter = 0;

function addDragDropCorrectRow(textValue = '') {
    const container = document.getElementById('dragDropCorrectContainer');
    const uniqueId = `drag_drop_correct_row_${dragDropCorrectCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'd-flex align-items-center gap-2 border rounded p-2 bg-light';
    div.innerHTML = `
        <span class="badge bg-success rounded-pill font-mono">Pos ${dragDropCorrectCounter + 1}</span>
        <input type="text" class="form-control form-control-sm border-0 bg-transparent font-sans text-dark" name="drag_drop_items[${dragDropCorrectCounter}]" value="${escapeHtml(textValue)}" placeholder="Sequence step text..." required>
        <button type="button" class="btn btn-link text-danger p-0 ms-auto" onclick="removeDragDropCorrectRow('${uniqueId}')">
            <i data-lucide="x" size="14"></i>
        </button>
    `;
    container.appendChild(div);
    dragDropCorrectCounter++;
    updateDragDropCorrectNumbers();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeDragDropCorrectRow(rowId) {
    document.getElementById(rowId).remove();
    updateDragDropCorrectNumbers();
}

function updateDragDropCorrectNumbers() {
    const rows = document.querySelectorAll('#dragDropCorrectContainer > div');
    rows.forEach((row, idx) => {
        const badge = row.querySelector('.badge');
        if (badge) {
            badge.textContent = `Pos ${idx + 1}`;
        }
    });
}

function addDragDropDistractorRow(textValue = '') {
    const container = document.getElementById('dragDropDistractorContainer');
    const uniqueId = `drag_drop_distractor_row_${dragDropDistractorCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'd-flex align-items-center gap-2 border rounded p-2 bg-light';
    div.innerHTML = `
        <span class="badge bg-secondary rounded-pill font-mono">Decoy</span>
        <input type="text" class="form-control form-control-sm border-0 bg-transparent font-sans text-dark" name="drag_drop_distractors[${dragDropDistractorCounter}]" value="${escapeHtml(textValue)}" placeholder="Distractor item text..." required>
        <button type="button" class="btn btn-link text-danger p-0 ms-auto" onclick="removeDragDropDistractorRow('${uniqueId}')">
            <i data-lucide="x" size="14"></i>
        </button>
    `;
    container.appendChild(div);
    dragDropDistractorCounter++;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeDragDropDistractorRow(rowId) {
    document.getElementById(rowId).remove();
}

let highlightSegmentCounter = 0;

function addHighlightSegmentRow(textValue = '', isCorrect = false) {
    const container = document.getElementById('highlightSegmentsContainer');
    const uniqueId = `highlight_segment_row_${highlightSegmentCounter}`;
    const div = document.createElement('div');
    div.id = uniqueId;
    div.className = 'p-3 border rounded-3 bg-light position-relative d-flex flex-column gap-2';
    div.innerHTML = `
        <button type="button" class="btn btn-link text-danger p-0 position-absolute top-0 end-0 mt-2 me-2" onclick="removeHighlightSegmentRow('${uniqueId}')">
            <i data-lucide="trash-2" size="16"></i>
        </button>
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-muted mb-1">Exact Segment Text (Must match passage substring)</label>
                <input type="text" class="form-control form-control-sm highlight-segment-text-input" name="highlight_segment_text[${highlightSegmentCounter}]" value="${escapeHtml(textValue)}" placeholder="e.g. chest pain radiating to the left arm" required>
            </div>
            <div class="col-md-4">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="highlight_segment_correct[]" value="${highlightSegmentCounter}" id="correct_seg_${highlightSegmentCounter}" ${isCorrect ? 'checked' : ''}>
                    <label class="form-check-label small fw-bold text-success" for="correct_seg_${highlightSegmentCounter}">
                        Is Correct Segment
                    </label>
                </div>
            </div>
        </div>
    `;
    container.appendChild(div);
    highlightSegmentCounter++;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeHighlightSegmentRow(rowId) {
    document.getElementById(rowId).remove();
}

// Initial hydration on page load
document.addEventListener('DOMContentLoaded', () => {
    // 1. Prefill True / False radios
    if (initialType === 'true_false') {
        const isTrueCorrect = initialOptions.some(o => o.text === 'True' && o.is_correct);
        if (isTrueCorrect) {
            document.getElementById('tf_true').checked = true;
        } else {
            document.getElementById('tf_false').checked = true;
        }
    }
    
    // 2. Prefill MCQ options
    else if ((initialType === 'mcq_single' || initialType === 'mcq_multi_sata') && initialOptions.length > 0) {
        initialOptions.forEach(opt => {
            addMcqRow(opt.text, opt.is_correct);
        });
    }

    // 3. Prefill Matching items
    else if (initialType === 'matching' && initialLeft.length > 0) {
        // Hydrate Left container and stamp dataset mapping attributes
        initialLeft.forEach((l, idx) => {
            addLeftItem(l.text);
            const children = document.getElementById('leftItemsContainer').children;
            const newRow = children[children.length - 1];
            newRow.dataset.id = l.id;
        });

        // Hydrate Right container and stamp dataset mapping attributes
        initialRight.forEach((r, idx) => {
            addRightItem(r.text);
            const children = document.getElementById('rightItemsContainer').children;
            const newRow = children[children.length - 1];
            newRow.dataset.id = r.id;
        });

        // Hydrate selected options based on coordinates
        const leftRows = document.querySelectorAll('#leftItemsContainer div');
        const rightRows = document.querySelectorAll('#rightItemsContainer div');

        leftRows.forEach(lRow => {
            const lId = lRow.dataset.id;
            const correctRightId = initialPairsMap[lId];
            if (!correctRightId) return;

            // Find matching right row's numerical counter
            let rightIndex = null;
            rightRows.forEach(rRow => {
                if (rRow.dataset.id === correctRightId) {
                    const matches = rRow.id.match(/\d+$/);
                    if (matches) rightIndex = matches[0];
                }
            });

            if (rightIndex !== null) {
                const matches = lRow.id.match(/\d+$/);
                const leftIndex = matches ? matches[0] : '';
                const select = document.querySelector(`.match-dropdown-map[data-left-index="${leftIndex}"]`);
                if (select) {
                    select.value = rightIndex;
                }
            }
        });
    }

    // 4. Prefill Matrix options
    else if ((initialType === 'matrix_single' || initialType === 'matrix_multi') && <?php echo json_encode(!empty($qData['rows'])); ?>) {
        const initialRows = <?php echo json_encode($qData['rows'] ?? []); ?>;
        const initialCols = <?php echo json_encode($qData['columns'] ?? []); ?>;

        initialRows.forEach(r => {
            addMatrixRow(r.label);
            const children = document.getElementById('matrixRowsContainer').children;
            const newRow = children[children.length - 1];
            newRow.dataset.id = r.id;
        });

        initialCols.forEach(c => {
            addMatrixCol(c.label);
            const children = document.getElementById('matrixColsContainer').children;
            const newRow = children[children.length - 1];
            newRow.dataset.id = c.id;
        });

        updateMatrixGridUI();
    }

    // 5. Prefill Cloze options
    else if ((initialType === 'cloze_dropdown' || initialType === 'cloze_dragdrop') && <?php echo json_encode(!empty($qData['blanks'])); ?>) {
        const initialBlanks = <?php echo json_encode($qData['blanks'] ?? []); ?>;
        initialBlanks.forEach(b => {
            const optionsCsv = (b.options || []).join(', ');
            addClozeBlankRow(b.id, optionsCsv, b.correct);
        });
    }

    // 6. Prefill Drag & Drop Ordered options
    else if (initialType === 'drag_drop_ordered' && <?php echo json_encode(!empty($qData['items'])); ?>) {
        const initialItems = <?php echo json_encode($qData['items'] ?? []); ?>;
        const initialCorrectOrder = <?php echo json_encode($qData['correct_order'] ?? []); ?>;
        const initialDistractors = <?php echo json_encode($qData['distractors'] ?? []); ?>;

        const itemsMap = {};
        initialItems.forEach(item => {
            itemsMap[item.id] = item.text;
        });

        initialCorrectOrder.forEach(itemId => {
            if (itemsMap[itemId]) {
                addDragDropCorrectRow(itemsMap[itemId]);
            }
        });

        initialDistractors.forEach(dist => {
            addDragDropDistractorRow(dist.text);
        });
    }

    // 7. Prefill Highlight options
    else if (initialType === 'highlight' && <?php echo json_encode(!empty($qData['segments'])); ?>) {
        const initialSegments = <?php echo json_encode($qData['segments'] ?? []); ?>;
        const initialCorrectSegmentIds = <?php echo json_encode($qData['correct_segment_ids'] ?? []); ?>;

        initialSegments.forEach(seg => {
            const isCorrect = initialCorrectSegmentIds.includes(seg.id);
            addHighlightSegmentRow(seg.text, isCorrect);
        });
    }

    // Trigger initial type forms toggle
    onTypeChanged();
});

// Real-time client-side validator on submit
document.getElementById('questionForm').addEventListener('submit', function(event) {
    const selectedType = document.getElementById('type').value;

    if (selectedType === 'cloze_dropdown' || selectedType === 'cloze_dragdrop') {
        const passageText = document.getElementById('cloze_passage').value;
        
        // Extract all {{placeholder}}
        const placeholderRegex = /\{\{([^}]+)\}\}/g;
        let match;
        const passagePlaceholders = [];
        while ((match = placeholderRegex.exec(passageText)) !== null) {
            passagePlaceholders.push(match[1].trim());
        }

        // Extract all defined IDs
        const idInputs = document.querySelectorAll('.cloze-blank-id-input');
        const definedIds = [];
        idInputs.forEach(input => {
            const val = input.value.trim();
            if (val !== '') {
                definedIds.push(val);
            }
        });

        const uniqueDefinedIds = [...new Set(definedIds)];
        if (uniqueDefinedIds.length !== definedIds.length) {
            alert("Error: Duplicate placeholder IDs defined in blank options.");
            event.preventDefault();
            return false;
        }

        for (const p of passagePlaceholders) {
            if (!definedIds.includes(p)) {
                alert(`Error: Placeholder {{${p}}} is used in the passage but has no matching blank definition.`);
                event.preventDefault();
                return false;
            }
        }

        for (const d of definedIds) {
            if (!passagePlaceholders.includes(d)) {
                alert(`Error: Blank definition '${d}' is defined but is not used in the passage text via {{${d}}}.`);
                event.preventDefault();
                return false;
            }
        }
    }

    if (selectedType === 'matrix_single' || selectedType === 'matrix_multi') {
        const rowInputs = document.querySelectorAll('#matrixRowsContainer input[type="text"]');
        const colInputs = document.querySelectorAll('#matrixColsContainer input[type="text"]');

        if (rowInputs.length === 0 || colInputs.length === 0) {
            alert("Error: You must add at least one row and one column to configure the matrix.");
            event.preventDefault();
            return false;
        }

        const rowDivs = document.querySelectorAll('#matrixRowsContainer div');
        for (const rDiv of rowDivs) {
            const rMatches = rDiv.id.match(/\d+$/);
            const rIdx = rMatches ? rMatches[0] : '';
            
            const checkedCols = document.querySelectorAll(`.matrix-grid-input[data-row-idx="${rIdx}"]:checked`);
            if (checkedCols.length === 0) {
                const input = rDiv.querySelector('input');
                const text = input.value.trim() || `Row ${rIdx}`;
                alert(`Error: Row "${text}" must have at least one correct column marked.`);
                event.preventDefault();
                return false;
            }
        }
    }

    if (selectedType === 'drag_drop_ordered') {
        const correctInputs = document.querySelectorAll('#dragDropCorrectContainer input[type="text"]');
        if (correctInputs.length < 2) {
            alert("Error: You must add at least 2 correct sequence items for the order sequence to make sense.");
            event.preventDefault();
            return false;
        }
    }

    if (selectedType === 'highlight') {
        const passageText = document.getElementById('highlight_passage_html').value;
        if (passageText.trim() === '') {
            alert("Error: Passage HTML/text is required.");
            event.preventDefault();
            return false;
        }

        const segmentInputs = document.querySelectorAll('#highlightSegmentsContainer .highlight-segment-text-input');
        if (segmentInputs.length === 0) {
            alert("Error: You must define at least one text segment.");
            event.preventDefault();
            return false;
        }

        const checkedSegments = document.querySelectorAll('#highlightSegmentsContainer input[type="checkbox"]:checked');
        if (checkedSegments.length === 0) {
            alert("Error: At least one segment must be marked as correct.");
            event.preventDefault();
            return false;
        }

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = passageText;
        const plainPassage = tempDiv.textContent || tempDiv.innerText || "";

        for (const input of segmentInputs) {
            const text = input.value.trim();
            if (text === '') continue;

            const inRaw = passageText.includes(text);
            const inPlain = plainPassage.includes(text);

            if (!inRaw && !inPlain) {
                alert(`Error: Segment text "${text}" was not found in the passage.`);
                event.preventDefault();
                return false;
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
