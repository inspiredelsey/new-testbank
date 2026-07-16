<?php
/**
 * Question Renderer class for outputting matching HTML per question type.
 */

class QuestionRenderer {
    
    /**
     * Renders a question in taking-exam mode.
     */
    public static function render($question, $options, $userAnswer = null, $disabled = false) {
        $type = $question['type'];
        $html = "<div class='card mb-4 shadow-sm border-0 rounded-3 id-question-card-" . $question['id'] . "' id='question-card-" . $question['id'] . "'>";
        $html .= "  <div class='card-header bg-light d-flex justify-content-between align-items-center py-3 border-0'>";
        $html .= "    <h5 class='mb-0 text-secondary d-flex align-items-center gap-2'>";
        $html .= "      <span class='badge bg-primary rounded-pill px-3'>" . self::getTypeLabel($type) . "</span>";
        $html .= "      <span class='text-muted fs-6'>(" . floatval($question['points']) . " pts)</span>";
        $html .= "    </h5>";
        $difficultyClass = self::getDifficultyBadgeClass($question['difficulty']);
        $html .= "    <span class='badge " . $difficultyClass . "'>" . ucfirst($question['difficulty']) . "</span>";
        $html .= "  </div>";
        $html .= "  <div class='card-body p-4'>";
        $html .= "    <div class='fs-5 mb-4 text-dark font-sans' style='white-space: pre-line;'>" . htmlspecialchars($question['question_text']) . "</div>";
        $html .= "    <div class='question-options-container'>";
        
        $disableAttr = $disabled ? "disabled" : "";

        switch ($type) {
            case 'mcq_single':
                foreach ($options as $opt) {
                    $checked = '';
                    if ($userAnswer !== null) {
                        if (is_array($userAnswer) && in_array($opt['id'], $userAnswer)) {
                            $checked = 'checked';
                        } else if ($userAnswer == $opt['id']) {
                            $checked = 'checked';
                        }
                    }
                    $html .= "
                    <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                        <input class='form-check-input ms-0 me-3' type='radio' name='q[" . $question['id'] . "]' id='opt_" . $opt['id'] . "' value='" . $opt['id'] . "' " . $checked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                        <label class='form-check-label w-full cursor-pointer mb-0' for='opt_" . $opt['id'] . "'>" . htmlspecialchars($opt['option_text']) . "</label>
                    </div>";
                }
                break;
                
            case 'mcq_multi':
                foreach ($options as $opt) {
                    $checked = '';
                    if (is_array($userAnswer) && in_array($opt['id'], $userAnswer)) {
                        $checked = 'checked';
                    }
                    $html .= "
                    <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                        <input class='form-check-input ms-0 me-3' type='checkbox' name='q[" . $question['id'] . "][]' id='opt_" . $opt['id'] . "' value='" . $opt['id'] . "' " . $checked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                        <label class='form-check-label w-full cursor-pointer mb-0' for='opt_" . $opt['id'] . "'>" . htmlspecialchars($opt['option_text']) . "</label>
                    </div>";
                }
                break;
                
            case 'true_false':
                $trueChecked = ($userAnswer === '1' || $userAnswer === 1 || $userAnswer === true || $userAnswer === 'true') ? 'checked' : '';
                $falseChecked = ($userAnswer === '0' || $userAnswer === 0 || $userAnswer === false || $userAnswer === 'false') ? 'checked' : '';
                
                $html .= "
                <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                    <input class='form-check-input ms-0 me-3' type='radio' name='q[" . $question['id'] . "]' id='tf_true_" . $question['id'] . "' value='true' " . $trueChecked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                    <label class='form-check-label w-full cursor-pointer mb-0' for='tf_true_" . $question['id'] . "'>True</label>
                </div>
                <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                    <input class='form-check-input ms-0 me-3' type='radio' name='q[" . $question['id'] . "]' id='tf_false_" . $question['id'] . "' value='false' " . $falseChecked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                    <label class='form-check-label w-full cursor-pointer mb-0' for='tf_false_" . $question['id'] . "'>False</label>
                </div>";
                break;
                
            case 'fill_blank':
                $textValue = is_string($userAnswer) ? htmlspecialchars($userAnswer) : '';
                $html .= "
                <div class='mb-3'>
                    <input type='text' class='form-control form-control-lg' name='q[" . $question['id'] . "]' placeholder='Type your answer here...' value='" . $textValue . "' " . $disableAttr . " onblur='saveAnswer(" . $question['id'] . ")' oninput='debouncedSave(" . $question['id'] . ")'>
                </div>";
                break;
                
            case 'matching':
                // For matching, $options contains the items. The left sides have option_text, the correct answers are pair_key.
                // We should display left items in fixed order, and right options as a dropdown selector containing the available shuffled pair_keys!
                $leftItems = $options;
                
                // Extract unique right sides (shuffled) for the dropdown list
                $rightItems = array_map(function($o) { return $o['pair_key']; }, $options);
                $rightItems = array_unique(array_filter($rightItems));
                shuffle($rightItems);
                
                $html .= "<div class='table-responsive'><table class='table table-bordered align-middle'>";
                $html .= "  <thead><tr class='bg-light'><th>Concept</th><th>Matching Term</th></tr></thead>";
                $html .= "  <tbody>";
                
                foreach ($leftItems as $idx => $item) {
                    $selectedPair = '';
                    if (is_array($userAnswer) && isset($userAnswer[$item['id']])) {
                        $selectedPair = $userAnswer[$item['id']];
                    }
                    
                    $html .= "<tr>";
                    $html .= "  <td class='p-3' style='width: 50%;'>" . htmlspecialchars($item['option_text']) . "</td>";
                    $html .= "  <td class='p-3'>";
                    $html .= "    <select class='form-select form-select-md matching-select' name='q[" . $question['id'] . "][" . $item['id'] . "]' " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>";
                    $html .= "      <option value=''>-- Select Match --</option>";
                    foreach ($rightItems as $right) {
                        $sel = ($selectedPair == $right) ? 'selected' : '';
                        $html .= "    <option value='" . htmlspecialchars($right) . "' " . $sel . ">" . htmlspecialchars($right) . "</option>";
                    }
                    $html .= "    </select>";
                    $html .= "  </td>";
                    $html .= "</tr>";
                }
                
                $html .= "  </tbody>";
                $html .= "</table></div>";
                break;
                
            case 'essay':
                $essayText = is_string($userAnswer) ? htmlspecialchars($userAnswer) : '';
                $html .= "
                <div class='mb-3'>
                    <textarea class='form-control font-sans text-dark' name='q[" . $question['id'] . "]' rows='6' placeholder='Type your detailed essay response here...' " . $disableAttr . " onblur='saveAnswer(" . $question['id'] . ")' oninput='debouncedSave(" . $question['id'] . ")'>" . $essayText . "</textarea>
                </div>";
                break;
        }
        
        $html .= "    </div>";
        $html .= "  </div>";
        $html .= "</div>";
        return $html;
    }
    
    /**
     * Renders a question in review mode, showing correct answers, scoring, and explanation.
     */
    public static function renderReview($question, $options, $userAnswer, $isCorrect, $pointsAwarded, $needsManual) {
        $type = $question['type'];
        $cardBorder = $needsManual ? "border-warning" : ($isCorrect ? "border-success" : "border-danger");
        $headerBg = $needsManual ? "bg-warning-subtle" : ($isCorrect ? "bg-success-subtle" : "bg-danger-subtle");
        $textClass = $needsManual ? "text-warning-emphasis" : ($isCorrect ? "text-success-emphasis" : "text-danger-emphasis");
        
        $html = "<div class='card mb-4 shadow-sm border " . $cardBorder . " rounded-3' id='review-q-" . $question['id'] . "'>";
        $html .= "  <div class='card-header " . $headerBg . " " . $textClass . " d-flex justify-content-between align-items-center py-3'>";
        $html .= "    <h5 class='mb-0 font-sans d-flex align-items-center gap-2'>";
        $html .= "      <span class='badge bg-dark rounded-pill px-3'>" . self::getTypeLabel($type) . "</span>";
        $html .= "      <span>" . htmlspecialchars($question['question_text']) . "</span>";
        $html .= "    </h5>";
        $html .= "    <span class='fw-bold fs-5'>";
        if ($needsManual) {
            $html .= "      <span class='badge bg-warning text-dark'>Needs Manual Grading</span>";
        } else {
            $html .= "      " . floatval($pointsAwarded) . " / " . floatval($question['points']) . " pts";
        }
        $html .= "    </span>";
        $html .= "  </div>";
        $html .= "  <div class='card-body p-4 bg-white'>";
        
        // Show correct/incorrect badges
        if (!$needsManual) {
            if ($isCorrect) {
                $html .= "<div class='alert alert-success d-flex align-items-center gap-2 mb-4 py-2 border-0 rounded-3'><i class='lucide-check-circle-2'></i> <span>Correct Answer!</span></div>";
            } else {
                $html .= "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4 py-2 border-0 rounded-3'><i class='lucide-x-circle'></i> <span>Incorrect. Review the correct options below.</span></div>";
            }
        }
        
        $html .= "    <div class='question-options-container'>";
        
        switch ($type) {
            case 'mcq_single':
                foreach ($options as $opt) {
                    $checked = ($userAnswer == $opt['id'] || (is_array($userAnswer) && in_array($opt['id'], $userAnswer))) ? 'checked' : '';
                    $correctIcon = $opt['is_correct'] ? "<span class='badge bg-success ms-2'><i class='lucide-check'></i> Correct</span>" : "";
                    $bgClass = $opt['is_correct'] ? "bg-success-subtle border-success-subtle" : ($checked ? "bg-danger-subtle border-danger-subtle" : "border-light-subtle");
                    
                    $html .= "
                    <div class='option-item p-3 mb-2 rounded border " . $bgClass . " d-flex align-items-center justify-content-between'>
                        <div class='form-check mb-0'>
                            <input class='form-check-input ms-0 me-3' type='radio' disabled " . $checked . ">
                            <label class='form-check-label w-full cursor-default mb-0'>" . htmlspecialchars($opt['option_text']) . "</label>
                        </div>
                        " . $correctIcon . "
                    </div>";
                }
                break;
                
            case 'mcq_multi':
                foreach ($options as $opt) {
                    $checked = (is_array($userAnswer) && in_array($opt['id'], $userAnswer)) ? 'checked' : '';
                    $correctIcon = $opt['is_correct'] ? "<span class='badge bg-success ms-2'><i class='lucide-check'></i> Correct</span>" : "";
                    $bgClass = $opt['is_correct'] ? "bg-success-subtle border-success-subtle" : ($checked ? "bg-danger-subtle border-danger-subtle" : "border-light-subtle");
                    
                    $html .= "
                    <div class='option-item p-3 mb-2 rounded border " . $bgClass . " d-flex align-items-center justify-content-between'>
                        <div class='form-check mb-0'>
                            <input class='form-check-input ms-0 me-3' type='checkbox' disabled " . $checked . ">
                            <label class='form-check-label w-full cursor-default mb-0'>" . htmlspecialchars($opt['option_text']) . "</label>
                        </div>
                        " . $correctIcon . "
                    </div>";
                }
                break;
                
            case 'true_false':
                $trueChecked = ($userAnswer === 'true' || $userAnswer === true || $userAnswer === '1' || $userAnswer === 1) ? 'checked' : '';
                $falseChecked = ($userAnswer === 'false' || $userAnswer === false || $userAnswer === '0' || $userAnswer === 0) ? 'checked' : '';
                
                // Find correct value
                $correctVal = 'false';
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctVal = strtolower($opt['option_text']);
                    }
                }
                
                $trueCorrect = ($correctVal === 'true' || $correctVal === '1');
                $falseCorrect = ($correctVal === 'false' || $correctVal === '0');
                
                $html .= "
                <div class='option-item p-3 mb-2 rounded border " . ($trueCorrect ? 'bg-success-subtle border-success-subtle' : ($trueChecked ? 'bg-danger-subtle border-danger-subtle' : 'border-light-subtle')) . " d-flex align-items-center justify-content-between'>
                    <div class='form-check mb-0'>
                        <input class='form-check-input ms-0 me-3' type='radio' disabled " . $trueChecked . ">
                        <label class='form-check-label mb-0'>True</label>
                    </div>
                    " . ($trueCorrect ? "<span class='badge bg-success'><i class='lucide-check'></i> Correct Answer</span>" : "") . "
                </div>
                <div class='option-item p-3 mb-2 rounded border " . ($falseCorrect ? 'bg-success-subtle border-success-subtle' : ($falseChecked ? 'bg-danger-subtle border-danger-subtle' : 'border-light-subtle')) . " d-flex align-items-center justify-content-between'>
                    <div class='form-check mb-0'>
                        <input class='form-check-input ms-0 me-3' type='radio' disabled " . $falseChecked . ">
                        <label class='form-check-label mb-0'>False</label>
                    </div>
                    " . ($falseCorrect ? "<span class='badge bg-success'><i class='lucide-check'></i> Correct Answer</span>" : "") . "
                </div>";
                break;
                
            case 'fill_blank':
                $correctAnswers = array_map(function($o) { return trim(strtolower($o['option_text'])); }, $options);
                $html .= "
                <div class='mb-3'>
                    <label class='form-label text-muted'>Your Answer:</label>
                    <input type='text' class='form-control border " . ($isCorrect ? "border-success" : "border-danger") . "' disabled value='" . htmlspecialchars($userAnswer ?? '') . "'>
                </div>
                <div class='mt-2 p-3 bg-light rounded-3'>
                    <strong>Accepted Correct Answers:</strong>
                    <ul class='mb-0 mt-1 pl-3'>";
                foreach ($options as $opt) {
                    $html .= "<li>" . htmlspecialchars($opt['option_text']) . "</li>";
                }
                $html .= "</ul></div>";
                break;
                
            case 'matching':
                $html .= "<div class='table-responsive'><table class='table table-bordered align-middle'>";
                $html .= "  <thead><tr class='bg-light'><th>Concept</th><th>Your Match</th><th>Correct Match</th><th>Status</th></tr></thead>";
                $html .= "  <tbody>";
                
                foreach ($options as $item) {
                    $userMatch = isset($userAnswer[$item['id']]) ? $userAnswer[$item['id']] : '';
                    $correctMatch = $item['pair_key'];
                    $matchCorrect = (strtolower(trim($userMatch)) === strtolower(trim($correctMatch)));
                    
                    $html .= "<tr>";
                    $html .= "  <td class='p-3'>" . htmlspecialchars($item['option_text']) . "</td>";
                    $html .= "  <td class='p-3 " . ($matchCorrect ? 'text-success fw-medium' : 'text-danger') . "'>" . htmlspecialchars($userMatch ?: '(Unanswered)') . "</td>";
                    $html .= "  <td class='p-3 text-success fw-medium'>" . htmlspecialchars($correctMatch) . "</td>";
                    $html .= "  <td class='p-3 text-center'>";
                    $html .= $matchCorrect ? "<span class='badge bg-success'><i class='lucide-check'></i></span>" : "<span class='badge bg-danger'><i class='lucide-x'></i></span>";
                    $html .= "  </td>";
                    $html .= "</tr>";
                }
                
                $html .= "  </tbody>";
                $html .= "</table></div>";
                break;
                
            case 'essay':
                $html .= "
                <div class='mb-3'>
                    <label class='form-label text-muted'>Your Written Response:</label>
                    <div class='p-3 border rounded bg-light-subtle' style='white-space: pre-wrap; font-family: sans-serif;'>" . htmlspecialchars($userAnswer ?? '') . "</div>
                </div>";
                
                if ($needsManual) {
                    $html .= "<div class='alert alert-warning border-0 rounded-3 py-2'><i class='lucide-info me-2'></i> Waiting for an instructor to review and grade this question.</div>";
                } else {
                    $html .= "<div class='alert alert-success border-0 rounded-3 py-2'><i class='lucide-check-circle me-2'></i> Graded: " . floatval($pointsAwarded) . " / " . floatval($question['points']) . " points awarded.</div>";
                }
                break;
        }
        
        $html .= "    </div>";
        $html .= "  </div>";
        $html .= "</div>";
        return $html;
    }

    private static function getTypeLabel($type) {
        $labels = [
            'mcq_single' => 'Multiple Choice (Single)',
            'mcq_multi' => 'Multiple Choice (Multiple)',
            'true_false' => 'True/False',
            'fill_blank' => 'Fill in the Blank',
            'matching' => 'Matching',
            'essay' => 'Essay'
        ];
        return $labels[$type] ?? 'Question';
    }

    private static function getDifficultyBadgeClass($difficulty) {
        $classes = [
            'easy' => 'bg-success text-white',
            'medium' => 'bg-warning text-dark',
            'hard' => 'bg-danger text-white'
        ];
        return $classes[$difficulty] ?? 'bg-secondary text-white';
    }
}
