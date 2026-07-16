<?php
require_once __DIR__ . '/../../../includes/QuestionRenderer.php';

$pageTitle = htmlspecialchars($attempt['exam_title']);
include __DIR__ . '/../layout_header.php';
?>

<!-- PINNED COUNTDOWN TIMER HEADER -->
<div class="row sticky-top bg-white py-3 border-bottom mb-4 z-3" style="top: 0; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);">
    <div class="col-12">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-3 py-2 font-sans" id="autosave-badge">
                    <i data-lucide="cloud-lightning" class="me-1 animate-pulse" size="14"></i> Active Exam Session
                </span>
                <span class="text-muted small d-none d-sm-inline font-sans" id="save-indicator">Answers are autosaved.</span>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted font-sans small d-none d-md-inline">Time Remaining:</span>
                <div class="px-4 py-2 bg-light text-dark border rounded-3 fw-bold font-mono fs-5" id="timer-box" style="min-width: 120px; text-align: center; border-color: #cbd5e1 !important;">
                    00:00
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- LEFT SIDEBAR: QUESTION NAVIGATION RAIL -->
    <div class="col-xl-3 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-1">
                    <i data-lucide="compass" size="16" class="text-primary"></i> Question Navigator
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2" id="navigator-grid">
                    <?php foreach ($questions as $idx => $q): ?>
                        <?php
                        // Check if answer is already saved
                        $hasAnswer = isset($savedAnswers[$q['id']]) && $savedAnswers[$q['id']]['answer_data'] !== null && trim($savedAnswers[$q['id']]['answer_data']) !== '""' && trim($savedAnswers[$q['id']]['answer_data']) !== '[]';
                        ?>
                        <div class="col-3 col-sm-2 col-md-2 col-lg-3">
                            <button type="button" 
                                    id="nav-btn-<?php echo $q['id']; ?>" 
                                    class="btn w-100 py-2.5 font-mono fw-bold rounded-3 navigator-btn <?php echo $idx === 0 ? 'btn-primary' : ($hasAnswer ? 'btn-outline-success border-success' : 'btn-outline-secondary'); ?>" 
                                    onclick="showQuestion(<?php echo $q['id']; ?>, <?php echo $idx; ?>)">
                                <?php echo ($idx + 1); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <hr class="text-muted my-3">
                <div class="small d-flex flex-column gap-1 text-muted ps-1 font-sans">
                    <div class="d-flex align-items-center gap-2"><div class="rounded-circle bg-primary" style="width: 10px; height: 10px;"></div> Active Question</div>
                    <div class="d-flex align-items-center gap-2"><div class="rounded-circle bg-success" style="width: 10px; height: 10px;"></div> Answered / Saved</div>
                    <div class="d-flex align-items-center gap-2"><div class="rounded-circle border border-secondary" style="width: 10px; height: 10px;"></div> Unanswered</div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN ACTIVE QUESTION VIEWPORT -->
    <div class="col-xl-9 col-lg-8">
        <form id="exam-taking-form" action="index.php?route=student/exam/submit" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            <input type="hidden" name="attempt_id" id="attempt_id" value="<?php echo $attempt['id']; ?>">

            <div class="questions-deck-container">
                <?php foreach ($questions as $idx => $q): ?>
                    <div class="question-slide-wrapper d-none id-question-slide-<?php echo $q['id']; ?>" id="question-slide-<?php echo $idx; ?>" data-question-id="<?php echo $q['id']; ?>">
                        <div class="text-muted fw-bold mb-2 ps-1 font-sans text-uppercase small d-flex justify-content-between">
                            <span>Question <?php echo ($idx + 1); ?> of <?php echo count($questions); ?></span>
                            <span class="badge bg-light border text-secondary text-capitalize"><?php echo $q['difficulty']; ?></span>
                        </div>
                        
                        <!-- Embed standard renderer inside a form block -->
                        <?php
                        $userAnswer = isset($savedAnswers[$q['id']]) ? json_decode($savedAnswers[$q['id']]['answer_data'], true) : null;
                        echo QuestionRenderer::render($q, $q['options'], $userAnswer, false);
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer Navigation Controls inside form -->
            <div class="card border-0 shadow-sm mt-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1" id="prev-btn" onclick="navigateQuestion(-1)">
                        <i data-lucide="chevron-left" size="18"></i> Previous Question
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-1" id="next-btn" onclick="navigateQuestion(1)">
                        Next Question <i data-lucide="chevron-right" size="18"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-success d-flex align-items-center gap-2 px-4 py-2 d-none" id="submit-btn" onclick="return confirm('Do you wish to submit your answers and complete this exam attempt?')">
                        Submit Exam <i data-lucide="check-check" size="18"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Remaining duration timer setup (seconds)
let secondsRemaining = <?php echo $remainingSeconds; ?>;
const timerBox = document.getElementById('timer-box');
const attemptId = <?php echo $attempt['id']; ?>;

function startTimer() {
    const interval = setInterval(() => {
        if (secondsRemaining <= 0) {
            clearInterval(interval);
            alert("Your exam time has expired! Your responses will now be submitted automatically.");
            document.getElementById('exam-taking-form').submit();
            return;
        }
        
        secondsRemaining--;
        
        const mins = Math.floor(secondsRemaining / 60);
        const secs = secondsRemaining % 60;
        
        timerBox.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        // Critical visual cues
        if (secondsRemaining < 300) { // < 5 mins
            timerBox.classList.remove('bg-light', 'text-dark');
            timerBox.classList.add('bg-danger-subtle', 'text-danger', 'border-danger-subtle', 'animate-pulse');
        }
    }, 1000);
}

// Active question deck index navigation
let currentDeckIndex = 0;
const totalQuestionsCount = <?php echo count($questions); ?>;
const questionIds = <?php echo json_encode(array_column($questions, 'id')); ?>;

function showQuestion(qId, index) {
    currentDeckIndex = index;
    
    // Hide all question slide containers
    document.querySelectorAll('.question-slide-wrapper').forEach(el => el.classList.add('d-none'));
    
    // Show active question slide
    const slide = document.getElementById(`question-slide-${index}`);
    if (slide) slide.classList.remove('d-none');
    
    // Manage Navigator highlight states
    document.querySelectorAll('.navigator-btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-secondary');
    });
    
    const activeNavBtn = document.getElementById(`nav-btn-${qId}`);
    if (activeNavBtn) {
        activeNavBtn.classList.remove('btn-outline-secondary', 'btn-outline-success', 'border-success');
        activeNavBtn.classList.add('btn-primary');
    }
    
    // Redraw answered statuses on non-active nodes
    questionIds.forEach((id, idx) => {
        if (idx !== currentDeckIndex) {
            const hasAns = hasQuestionValue(id);
            const btn = document.getElementById(`nav-btn-${id}`);
            if (btn) {
                if (hasAns) {
                    btn.classList.add('btn-outline-success', 'border-success');
                    btn.classList.remove('btn-outline-secondary');
                } else {
                    btn.classList.add('btn-outline-secondary');
                    btn.classList.remove('btn-outline-success', 'border-success');
                }
            }
        }
    });

    // Control navigation buttons visibility
    document.getElementById('prev-btn').disabled = (index === 0);
    
    if (index === totalQuestionsCount - 1) {
        document.getElementById('next-btn').classList.add('d-none');
        document.getElementById('submit-btn').classList.remove('d-none');
    } else {
        document.getElementById('next-btn').classList.remove('d-none');
        document.getElementById('submit-btn').classList.add('d-none');
    }
}

function navigateQuestion(offset) {
    const nextIndex = currentDeckIndex + offset;
    if (nextIndex >= 0 && nextIndex < totalQuestionsCount) {
        showQuestion(questionIds[nextIndex], nextIndex);
    }
}

// Checks if a question has any answered inputs in the DOM
function hasQuestionValue(qId) {
    const form = document.getElementById('exam-taking-form');
    // Radio buttons check
    const radios = form.querySelectorAll(`input[name="q[${qId}]"]:checked`);
    if (radios.length > 0) return true;
    
    // Checkboxes check
    const checkboxes = form.querySelectorAll(`input[name="q[${qId}][]"]:checked`);
    if (checkboxes.length > 0) return true;
    
    // Texts inputs / blanks check
    const textInput = form.querySelector(`input[name="q[${qId}]"]`);
    if (textInput && textInput.value.trim() !== '') return true;
    
    // Textarea essay check
    const textarea = form.querySelector(`textarea[name="q[${qId}]"]`);
    if (textarea && textarea.value.trim() !== '') return true;
    
    // Matching selects check
    const selects = form.querySelectorAll(`select[name^="q[${qId}]["]`);
    if (selects.length > 0) {
        let matchedOne = false;
        selects.forEach(sel => {
            if (sel.value !== '') matchedOne = true;
        });
        return matchedOne;
    }
    
    return false;
}

// AJAX Autosave Mechanism
function saveAnswer(qId) {
    const indicator = document.getElementById('save-indicator');
    const badge = document.getElementById('autosave-badge');
    
    indicator.textContent = "Autosaving answer...";
    badge.className = "badge bg-warning text-dark px-3 py-2 font-sans";
    
    // Gather value depending on DOM type
    let val = null;
    const form = document.getElementById('exam-taking-form');
    
    // 1. Radio buttons MCQ
    const radios = form.querySelectorAll(`input[name="q[${qId}]"]:checked`);
    if (radios.length > 0 && radios[0].type === 'radio' && !radios[0].name.includes('tf_')) {
        val = radios[0].value;
    }
    
    // 2. MCQ Multi Checkboxes
    const checkboxes = form.querySelectorAll(`input[name="q[${qId}][]"]:checked`);
    if (checkboxes.length > 0) {
        val = Array.from(checkboxes).map(cb => cb.value);
    }
    
    // 3. True False Radios
    const tfTrue = form.getElementById(`tf_true_${qId}`);
    const tfFalse = form.getElementById(`tf_false_${qId}`);
    if (tfTrue && tfTrue.checked) val = 'true';
    if (tfFalse && tfFalse.checked) val = 'false';
    
    // 4. Fill Blanks exact input
    const textInput = form.querySelector(`input[name="q[${qId}]"]`);
    if (textInput) val = textInput.value;
    
    // 5. Essay textarea
    const textarea = form.querySelector(`textarea[name="q[${qId}]"]`);
    if (textarea) val = textarea.value;
    
    // 6. Matching pairings
    const selects = form.querySelectorAll(`select[name^="q[${qId}]["]`);
    if (selects.length > 0) {
        val = {};
        selects.forEach(sel => {
            // Name format is q[qId][optionId]
            const optionId = sel.name.split('[')[2].split(']')[0];
            val[optionId] = sel.value;
        });
    }

    // Build FormData
    const formData = new FormData();
    formData.append('attempt_id', attemptId);
    formData.append('question_id', qId);
    
    if (val !== null && typeof val === 'object') {
        // Post subelements individually
        for (const k in val) {
            formData.append(`answer[${k}]`, val[k]);
        }
    } else {
        formData.append('answer', val || '');
    }

    fetch('index.php?route=student/exam/save_answer', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            indicator.textContent = "All changes saved to cloud.";
            badge.className = "badge bg-success px-3 py-2 font-sans";
            
            // Mark navigator node
            const btn = document.getElementById(`nav-btn-${qId}`);
            if (btn && qId !== questionIds[currentDeckIndex]) {
                btn.classList.add('btn-outline-success', 'border-success');
                btn.classList.remove('btn-outline-secondary');
            }
        } else {
            indicator.textContent = "Failed to save answer.";
            badge.className = "badge bg-danger px-3 py-2 font-sans";
        }
    })
    .catch(err => {
        indicator.textContent = "Connection issue. Retrying...";
        badge.className = "badge bg-danger px-3 py-2 font-sans";
    });
}

// Debounced helper for typing events (Blanks, Essays)
let debounceTimers = {};
function debouncedSave(qId) {
    clearTimeout(debounceTimers[qId]);
    debounceTimers[qId] = setTimeout(() => {
        saveAnswer(qId);
    }, 1000);
}

// On page load, initialize
startTimer();
if (questionIds.length > 0) {
    showQuestion(questionIds[0], 0);
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
