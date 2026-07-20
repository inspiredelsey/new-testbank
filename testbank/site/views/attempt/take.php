<?php
/**
 * Student Active Exam Taking View - Test Bank LMS
 */
$pageTitle = htmlspecialchars($attempt['exam_title']) . ' - Active Exam';
include __DIR__ . '/../../../admin/views/layout_header.php';

// Prepare variables
$examId = intval($attempt['exam_id']);
$attemptId = intval($attempt['id']);
$csrfToken = Session::getCSRFToken();
?>

<!-- SortableJS for Drag and Drop types -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="container-fluid py-2">
    <!-- Top Bar: Exam Title & Timer & Submit Button -->
    <div class="row align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm border">
        <div class="col-md-6">
            <h4 class="display-font fw-bold text-dark mb-1"><?php echo htmlspecialchars($attempt['exam_title']); ?></h4>
            <p class="text-muted small mb-0 font-sans">
                Logged in as <strong><?php echo htmlspecialchars($attempt['student_name']); ?></strong>
            </p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end align-items-center gap-3 flex-wrap mt-2 mt-md-0">
            <!-- Timer Display -->
            <?php if (intval($attempt['duration_minutes']) > 0): ?>
                <div class="d-flex align-items-center gap-2 bg-light px-3 py-2 rounded-3 border" id="timer-box" style="min-width: 170px;">
                    <i data-lucide="clock" class="text-primary animate-pulse" style="width: 20px; height: 20px;"></i>
                    <div>
                        <div class="text-muted small uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Time Remaining</div>
                        <div class="font-mono fw-bold fs-5 text-dark" id="time-display">--:--</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-light px-3 py-2 rounded-3 border font-sans text-muted small fw-bold">
                    <i data-lucide="infinity" class="align-middle me-1"></i> No Time Limit
                </div>
            <?php endif; ?>

            <!-- Autosave Status Indicator -->
            <div id="save-status" class="text-muted small font-sans d-flex align-items-center gap-1.5 px-2">
                <i data-lucide="cloud-check" class="text-success" style="width: 18px; height: 18px;"></i>
                <span>Answers Saved</span>
            </div>

            <!-- Submit Button -->
            <form action="index.php?route=student/exam/submit" method="POST" onsubmit="return confirmSubmit();">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="attempt_id" value="<?php echo $attemptId; ?>">
                <button type="submit" class="btn btn-primary font-sans px-4 py-2.5 rounded-3 d-flex align-items-center gap-2 fw-semibold">
                    <i data-lucide="send" style="width: 18px; height: 18px;"></i> Submit Exam
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Main Column: Interactive Active Question Card -->
        <div class="col-lg-9 col-md-8">
            <div id="questions-viewport">
                <?php foreach ($questions as $index => $question): ?>
                    <?php 
                    $qId = intval($question['id']);
                    $existing = $savedAnswers[$qId] ?? null;
                    $isFirst = ($index === 0);
                    $displayClass = $isFirst ? '' : 'd-none';
                    ?>
                    <div class="question-wrapper <?php echo $displayClass; ?>" id="wrapper-q-<?php echo $qId; ?>" data-question-index="<?php echo $index; ?>" data-question-id="<?php echo $qId; ?>">
                        
                        <!-- Question Header / Index label -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold font-sans text-muted">Question <?php echo ($index + 1); ?> of <?php echo count($questions); ?></span>
                            <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-sans rounded-pill">
                                ID: #<?php echo $qId; ?>
                            </span>
                        </div>

                        <!-- Interactive Question Container -->
                        <?php echo QuestionRenderer::renderInteractive($question, $existing); ?>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Navigation Controls -->
            <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
                <button type="button" class="btn btn-outline-secondary font-sans px-4 py-2 rounded-3 d-flex align-items-center gap-1.5" id="btn-prev" onclick="navigatePrev()">
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Previous
                </button>

                <div class="font-sans text-muted small" id="nav-indicator">
                    Question <span id="current-index-lbl">1</span> of <?php echo count($questions); ?>
                </div>

                <button type="button" class="btn btn-primary font-sans px-4 py-2 rounded-3 d-flex align-items-center gap-1.5" id="btn-next" onclick="navigateNext()">
                    Next <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </div>

        <!-- Sidebar Panel: Question Index Grid -->
        <div class="col-lg-3 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 border mb-4 sticky-top" style="top: 20px; z-index: 10;">
                <div class="card-header bg-light py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0 font-sans"><i data-lucide="grid" class="text-primary me-2 align-middle"></i>Question Navigation</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap gap-2 justify-content-center mb-3" id="navigator-grid">
                        <?php foreach ($questions as $index => $question): ?>
                            <?php 
                            $qId = intval($question['id']);
                            $hasAns = !empty($savedAnswers[$qId]['answer_data']);
                            $btnClass = $hasAns ? 'btn-success text-white' : 'btn-outline-secondary';
                            if ($index === 0) {
                                $btnClass .= ' border-primary border-3';
                            }
                            ?>
                            <button type="button" 
                                    class="btn <?php echo $btnClass; ?> d-flex align-items-center justify-content-center font-sans fw-bold p-0 nav-tile" 
                                    id="nav-tile-<?php echo $qId; ?>"
                                    style="width: 42px; height: 42px; font-size: 0.95rem; border-radius: 8px;"
                                    onclick="navigateToIndex(<?php echo $index; ?>)"
                                    data-question-id="<?php echo $qId; ?>">
                                <?php echo ($index + 1); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-top pt-3">
                        <h6 class="small text-muted fw-semibold mb-2">Status Legends:</h6>
                        <div class="d-flex flex-column gap-2 small font-sans text-muted">
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block bg-success rounded-pill" style="width: 12px; height: 12px;"></span>
                                <span>Answered & Saved</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block bg-white border border-secondary rounded-pill" style="width: 12px; height: 12px;"></span>
                                <span>Unanswered</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block bg-white border border-primary border-3 rounded-pill" style="width: 12px; height: 12px;"></span>
                                <span>Currently Viewing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Core Take-Exam Logic Engine JavaScript -->
<script>
// State Management
const attemptId = <?php echo $attemptId; ?>;
const csrfToken = "<?php echo htmlspecialchars($csrfToken); ?>";
const totalQuestions = <?php echo count($questions); ?>;
let currentIndex = 0;
let secondsRemaining = <?php echo intval($remainingSeconds); ?>;
const questionsMap = {}; // Maps question ID to index

// Initialize maps
document.querySelectorAll('.question-wrapper').forEach((el, idx) => {
    const qId = parseInt(el.dataset.questionId);
    questionsMap[qId] = idx;
});

// UI elements
const timerDisplay = document.getElementById('time-display');
const saveStatusText = document.querySelector('#save-status span');
const saveStatusIcon = document.querySelector('#save-status i');
const currentIndexLbl = document.getElementById('current-index-lbl');
const btnPrev = document.getElementById('btn-prev');
const btnNext = document.getElementById('btn-next');

// Timer Logic
if (secondsRemaining > 0) {
    function updateTimer() {
        if (secondsRemaining <= 0) {
            timerDisplay.textContent = "00:00";
            timerDisplay.classList.add('text-danger');
            // Auto submit
            alert("Time's up! Your exam will now be automatically submitted.");
            window.location.href = "index.php?route=student/exam/review&attempt_id=" + attemptId + "&info=Time expired. Auto-submitted.";
            return;
        }

        secondsRemaining--;
        const mins = Math.floor(secondsRemaining / 60);
        const secs = secondsRemaining % 60;
        timerDisplay.textContent = 
            (mins < 10 ? "0" + mins : mins) + ":" + (secs < 10 ? "0" + secs : secs);

        // Warning colors
        const box = document.getElementById('timer-box');
        if (secondsRemaining < 60) {
            box.classList.add('bg-danger-subtle', 'border-danger');
            timerDisplay.classList.add('text-danger');
        } else if (secondsRemaining < 300) {
            box.classList.add('bg-warning-subtle', 'border-warning');
            timerDisplay.classList.add('text-warning-emphasis');
        }
    }
    
    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);

    // Sync Timer from server periodically to prevent browser lag/tab freezing drifts
    setInterval(function() {
        fetch(`api/get_time_remaining.php?attempt_id=${attemptId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && typeof data.seconds_remaining === 'number') {
                    secondsRemaining = data.seconds_remaining;
                }
            })
            .catch(err => console.error("Error syncing timer:", err));
    }, 30000);
}

// Navigation Logic
function navigateToIndex(index) {
    if (index < 0 || index >= totalQuestions) return;

    // Hide current wrapper
    const currentWrapper = document.querySelector(`.question-wrapper[data-question-index="${currentIndex}"]`);
    if (currentWrapper) currentWrapper.classList.add('d-none');

    // Show new wrapper
    currentIndex = index;
    const newWrapper = document.querySelector(`.question-wrapper[data-question-index="${currentIndex}"]`);
    if (newWrapper) {
        newWrapper.classList.remove('d-none');
        // Initialize interactive libraries for current type if needed
        initializeQuestionType(newWrapper);
    }

    // Update navigation indicators and grid highlights
    currentIndexLbl.textContent = currentIndex + 1;
    btnPrev.disabled = (currentIndex === 0);
    btnNext.disabled = (currentIndex === totalQuestions - 1);

    // Update navigator styles
    document.querySelectorAll('.nav-tile').forEach((tile, idx) => {
        tile.classList.remove('border-primary', 'border-3');
        if (idx === currentIndex) {
            tile.classList.add('border-primary', 'border-3');
        }
    });

    // Sync scroll layout
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function navigatePrev() {
    navigateToIndex(currentIndex - 1);
}

function navigateNext() {
    navigateToIndex(currentIndex + 1);
}

// Initialize on page load
navigateToIndex(0);

// Initialize special libraries on load or navigation (e.g. SortableJS for ordering)
function initializeQuestionType(wrapper) {
    const qId = parseInt(wrapper.dataset.questionId);
    const type = wrapper.dataset.type;

    if (type === 'drag_drop_ordered') {
        const sortableList = wrapper.querySelector('.sortable-list');
        if (sortableList && !sortableList.dataset.sortableInitialized) {
            Sortable.create(sortableList, {
                animation: 150,
                ghostClass: 'bg-light',
                onEnd: function() {
                    onDragDropOrderedChange(qId);
                }
            });
            sortableList.dataset.sortableInitialized = 'true';
        }
    }
}

// Global confirm on manual submit
function confirmSubmit() {
    return confirm("Are you sure you want to submit your exam now? You will not be able to change your answers after submission.");
}


// =========================================================
// ANSWER CAPTURE & AUTOSAVE CONTROLLER LAYER
// =========================================================

const saveDebounceTimers = {};

function triggerAutosave(questionId, answerData) {
    // Show saving status
    saveStatusText.textContent = "Saving answers...";
    saveStatusIcon.className = "text-warning animate-spin";
    if (window.lucide) window.lucide.createIcons();

    // Debounce saves per-question by 1 second
    if (saveDebounceTimers[questionId]) {
        clearTimeout(saveDebounceTimers[questionId]);
    }

    saveDebounceTimers[questionId] = setTimeout(() => {
        fetch('api/save_answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                csrf_token: csrfToken,
                attempt_id: attemptId,
                question_id: questionId,
                answer_data: answerData
            })
        })
        .then(res => {
            if (!res.ok) throw new Error("Save error");
            return res.json();
        })
        .then(data => {
            if (data.success) {
                // Update save status indicator
                saveStatusText.textContent = "Answers Saved";
                saveStatusIcon.className = "text-success";
                if (window.lucide) window.lucide.createIcons();

                // Mark navigator grid block as complete
                const tile = document.getElementById(`nav-tile-${questionId}`);
                if (tile) {
                    tile.classList.remove('btn-outline-secondary');
                    tile.classList.add('btn-success', 'text-white');
                }
            } else {
                saveStatusText.textContent = "Error saving";
                saveStatusIcon.className = "text-danger";
                if (window.lucide) window.lucide.createIcons();
            }
        })
        .catch(err => {
            console.error("Autosave Failed:", err);
            saveStatusText.textContent = "Connection offline";
            saveStatusIcon.className = "text-danger";
            if (window.lucide) window.lucide.createIcons();
        });
    }, 1000);
}


// =========================================================
// QUESTION TYPE INTERACTION LISTENERS
// =========================================================

// 1. MCQ Single / True False
function onMCQSingleChange(questionId, value) {
    triggerAutosave(questionId, { selected: value });
}

// 2. MCQ Multi / SATA
function onMCQMultiChange(questionId) {
    const selected = [];
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] input[type="checkbox"]:checked`).forEach(chk => {
        selected.push(chk.value);
    });
    triggerAutosave(questionId, { selected: selected });
}

// 3. MCQ Extended
function onMCQExtendedChange(questionId, selectCount) {
    const checkboxes = document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] input[type="checkbox"]`);
    const checked = Array.from(checkboxes).filter(c => c.checked);
    
    // Update live counter
    const wrapper = document.getElementById(`interactive-q-${questionId}`);
    if (wrapper) {
        const counter = wrapper.querySelector('.current-count');
        if (counter) counter.textContent = checked.length;
    }

    // If limit reached, disable other checkboxes
    checkboxes.forEach(c => {
        if (!c.checked) {
            c.disabled = (checked.length >= selectCount);
        }
    });

    const selectedIds = checked.map(c => c.value);
    triggerAutosave(questionId, { selected: selectedIds });
}

// 4. Matching
function onMatchingChange(questionId) {
    const pairs = [];
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] select`).forEach(sel => {
        const leftId = sel.dataset.leftId;
        const rightId = sel.value;
        if (rightId) {
            pairs.push([leftId, rightId]);
        }
    });
    triggerAutosave(questionId, { pairs: pairs });
}

// 5. Drag and Drop Ordered
function onDragDropOrderedChange(questionId) {
    const order = [];
    document.querySelectorAll(`#sortable_${questionId} li`).forEach(li => {
        order.push(li.dataset.id);
    });
    triggerAutosave(questionId, { order: order });
}

// 6. Matrix Single / Multi
function onMatrixChange(questionId) {
    const answers = {};
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] tbody tr`).forEach(tr => {
        const rowId = tr.dataset.rowId;
        answers[rowId] = [];
        tr.querySelectorAll('input:checked').forEach(inp => {
            answers[rowId].push(inp.value);
        });
    });
    triggerAutosave(questionId, { answers: answers });
}

// 7. Cloze Dropdown
function onClozeDropdownChange(questionId) {
    const blanks = {};
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] select`).forEach(sel => {
        const blankId = sel.dataset.blankId;
        blanks[blankId] = sel.value;
    });
    triggerAutosave(questionId, { blanks: blanks });
}

// 8. Cloze Drag & Drop Clicking System (Mobile + Desktop friendly alternative)
let selectedOptionToken = null;

function onClozeOptionSelect(questionId, button) {
    // Deselect previous
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] .option-token`).forEach(btn => {
        btn.classList.remove('active', 'btn-primary');
        btn.classList.add('btn-outline-secondary');
    });

    selectedOptionToken = button.dataset.value;
    button.classList.add('active', 'btn-primary');
    button.classList.remove('btn-outline-secondary');
}

function onClozeTargetClick(questionId, targetSpan) {
    if (!selectedOptionToken) {
        alert("Please select an option token from the pool below first.");
        return;
    }

    targetSpan.textContent = selectedOptionToken;
    targetSpan.dataset.value = selectedOptionToken;
    targetSpan.className = "cloze-drop-target d-inline-flex align-items-center justify-content-center border rounded px-3 py-1 align-middle bg-primary-subtle border-primary text-primary-emphasis fw-bold";

    // Clear active selection in pool
    selectedOptionToken = null;
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] .option-token`).forEach(btn => {
        btn.classList.remove('active', 'btn-primary');
        btn.classList.add('btn-outline-secondary');
    });

    // Save
    saveClozeDragDropAnswers(questionId);
}

function clearClozeSelections(questionId) {
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] .cloze-drop-target`).forEach(span => {
        span.textContent = "Drop here";
        span.dataset.value = "";
        span.className = "cloze-drop-target d-inline-flex align-items-center justify-content-center border rounded px-3 py-1 align-middle bg-white border-dashed text-muted";
    });
    saveClozeDragDropAnswers(questionId);
}

function saveClozeDragDropAnswers(questionId) {
    const blanks = {};
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] .cloze-drop-target`).forEach(span => {
        const blankId = span.dataset.blankId;
        const val = span.dataset.value || "";
        if (val) {
            blanks[blankId] = val;
        }
    });
    triggerAutosave(questionId, { blanks: blanks });
}

// 9. Highlight Segments
function onHighlightToggle(questionId, span) {
    span.classList.toggle('bg-primary');
    span.classList.toggle('text-white');
    span.classList.toggle('border-primary');
    span.classList.toggle('fw-bold');
    span.classList.toggle('shadow-sm');

    span.classList.toggle('bg-light');
    span.classList.toggle('border-light-subtle');
    span.classList.toggle('text-dark');

    const segments = [];
    document.querySelectorAll(`.question-wrapper[data-question-id="${questionId}"] .highlight-segment`).forEach(seg => {
        if (seg.classList.contains('bg-primary')) {
            segments.push(seg.dataset.segmentId);
        }
    });

    triggerAutosave(questionId, { segments: segments });
}

// 10. Bowtie Toggles
function onBowtieToggle(questionId, column, optionId) {
    const wrapper = document.getElementById(`interactive-q-${questionId}`);
    if (!wrapper) return;

    const list = wrapper.querySelector(`.bowtie-list[data-col="${column}"]`);
    const optEl = list.querySelector(`.bowtie-option[data-id="${optionId}"]`);
    
    // Toggle active state
    optEl.classList.toggle('bg-primary');
    optEl.classList.toggle('text-white');
    optEl.classList.toggle('border-primary');
    optEl.classList.toggle('fw-bold');
    optEl.classList.toggle('shadow-sm');

    optEl.classList.toggle('bg-white');
    optEl.classList.toggle('text-dark');
    optEl.classList.toggle('border-light-subtle');

    // Count targets
    const targetCount = parseInt(wrapper.dataset[`${column}Target`] || 1);
    const selected = Array.from(list.querySelectorAll('.bowtie-option.bg-primary'));

    if (selected.length > targetCount) {
        // Exceeded: remove first selected besides current or deselect current
        optEl.classList.remove('bg-primary', 'text-white', 'border-primary', 'fw-bold', 'shadow-sm');
        optEl.classList.add('bg-white', 'text-dark', 'border-light-subtle');
        alert(`You can only select up to ${targetCount} option(s) for the '${column}' column.`);
        return;
    }

    // Update column counters
    const counter = wrapper.querySelector(`.bowtie-counter-${column}`);
    if (counter) counter.textContent = `${selected.length} / ${targetCount}`;

    // Collect all selected bowtie items
    const bowtieAnswer = {
        left: Array.from(wrapper.querySelectorAll('.bowtie-list[data-col="left"] .bowtie-option.bg-primary')).map(el => el.dataset.id),
        center: Array.from(wrapper.querySelectorAll('.bowtie-list[data-col="center"] .bowtie-option.bg-primary')).map(el => el.dataset.id),
        right: Array.from(wrapper.querySelectorAll('.bowtie-list[data-col="right"] .bowtie-option.bg-primary')).map(el => el.dataset.id),
    };

    triggerAutosave(questionId, bowtieAnswer);
}

// 11. Fill in the Blank Calculated
function onFillBlankCalcChange(questionId, value) {
    triggerAutosave(questionId, { value: value });
}

// 12. Essay Textarea
function onEssayChange(questionId, value) {
    triggerAutosave(questionId, { text: value });
}

// Run initial vector Lucide icon loader
document.addEventListener("DOMContentLoaded", () => {
    if (window.lucide) window.lucide.createIcons();
});
</script>

<?php
include __DIR__ . '/../../../admin/views/layout_footer.php';
?>
