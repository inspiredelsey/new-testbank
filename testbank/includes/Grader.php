<?php
/**
 * Auto Grader class for evaluating test attempts.
 * Implements deterministic grading for all 14 question types.
 */

require_once __DIR__ . '/Database.php';

class Grader {

    /**
     * Grades a single question answer.
     * Returns array: [is_correct, points_awarded, needs_manual_grading]
     * Supports all 14 types of questions in the LMS.
     */
    public static function gradeSingleAnswer($question, $answerData, $options = null) {
        $type = $question['type'];
        $points = floatval($question['points']);
        $scoringMethod = $question['scoring_method'] ?? 'all_or_nothing';

        // Unanswered questions (no attempt_answers row or empty/null) count as 0 points, is_correct=false
        if ($answerData === null || (is_string($answerData) && trim($answerData) === '') || (is_array($answerData) && empty($answerData))) {
            if ($type === 'essay') {
                return [null, null, true];
            }
            return [false, 0.00, false];
        }

        // Decode question_data for NGN questions
        $qData = [];
        if (is_string($question['question_data'] ?? '')) {
            $qData = json_decode($question['question_data'], true) ?: [];
        } else if (is_array($question['question_data'] ?? null)) {
            $qData = $question['question_data'];
        }

        switch ($type) {
            case 'mcq_single':
            case 'true_false':
                // userAnswer format: "opt_id" or string value (like 'true'/'false')
                if ($options === null) {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY order_index ASC");
                    $stmt->execute([$question['id']]);
                    $options = $stmt->fetchAll();
                }

                $correctOptId = null;
                $correctOptText = null;
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctOptId = $opt['id'];
                        $correctOptText = strtolower(trim($opt['option_text']));
                        break;
                    }
                }

                $selectedVal = is_array($answerData) ? ($answerData['selected'] ?? null) : $answerData;
                
                if ($selectedVal === null) {
                    return [false, 0.00, false];
                }

                $isCorrect = false;
                if ($type === 'true_false') {
                    // True/False exact string matching
                    $userValStr = strtolower(trim($selectedVal));
                    if ($userValStr === '1') $userValStr = 'true';
                    if ($userValStr === '0') $userValStr = 'false';
                    
                    $correctValStr = $correctOptText;
                    if ($correctValStr === '1') $correctValStr = 'true';
                    if ($correctValStr === '0') $correctValStr = 'false';
                    
                    $isCorrect = ($userValStr === $correctValStr);
                } else {
                    // MCQ single: option ID check
                    $isCorrect = (intval($selectedVal) === intval($correctOptId));
                }

                $score = $isCorrect ? $points : 0.00;
                return [$isCorrect, $score, false];

            case 'mcq_multi':
            case 'mcq_multi_sata':
                // SATA multi
                if ($options === null) {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY order_index ASC");
                    $stmt->execute([$question['id']]);
                    $options = $stmt->fetchAll();
                }

                $correctOptIds = [];
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctOptIds[] = intval($opt['id']);
                    }
                }

                $userSelected = isset($answerData['selected']) ? $answerData['selected'] : $answerData;
                if (!is_array($userSelected)) {
                    $userSelected = !empty($userSelected) ? [$userSelected] : [];
                }
                $userSelected = array_map('intval', $userSelected);

                $totalCorrectCount = count($correctOptIds);
                if ($totalCorrectCount === 0) {
                    return [true, $points, false];
                }

                // Check exact match
                $userSorted = $userSelected;
                $correctSorted = $correctOptIds;
                sort($userSorted);
                sort($correctSorted);
                $isExact = ($userSorted === $correctSorted);

                if ($scoringMethod === 'all_or_nothing') {
                    $score = $isExact ? $points : 0.00;
                } else {
                    // partial credit: (correct selections made - incorrect selections made) / total correct options
                    $correctSelected = count(array_intersect($userSelected, $correctOptIds));
                    $incorrectSelected = count(array_diff($userSelected, $correctOptIds));
                    $ratio = ($correctSelected - $incorrectSelected) / $totalCorrectCount;
                    $ratio = max(0.0, $ratio);
                    $score = $ratio * $points;
                }

                return [$isExact, $score, false];

            case 'mcq_extended':
                // mcq_extended: correct only if selected set exactly equals correct set AND selected count equals select_count
                // Treat as all-or-nothing regardless of scoring_method per instructions.
                if ($options === null) {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY order_index ASC");
                    $stmt->execute([$question['id']]);
                    $options = $stmt->fetchAll();
                }

                $correctOptIds = [];
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctOptIds[] = intval($opt['id']);
                    }
                }

                $userSelected = isset($answerData['selected']) ? $answerData['selected'] : $answerData;
                if (!is_array($userSelected)) {
                    $userSelected = !empty($userSelected) ? [$userSelected] : [];
                }
                $userSelected = array_map('intval', $userSelected);

                $selectCount = intval($qData['select_count'] ?? count($correctOptIds));

                sort($userSelected);
                sort($correctOptIds);

                $isExact = ($userSelected === $correctOptIds) && (count($userSelected) === $selectCount);
                $score = $isExact ? $points : 0.00;
                return [$isExact, $score, false];

            case 'matching':
                // userAnswer format: {"pairs": [ [left_id, right_id], ... ]}
                // Correct format: $qData['correct_pairs'] => [ [left_id, right_id], ... ]
                // Left elements: $qData['left'] => [ {"id": "left1", "text": "..."} ]
                $left = $qData['left'] ?? [];
                $correctPairs = $qData['correct_pairs'] ?? [];
                $totalPairs = count($correctPairs) > 0 ? count($correctPairs) : count($left);

                if ($totalPairs === 0) {
                    return [true, $points, false];
                }

                $userPairs = $answerData['pairs'] ?? [];
                $userPairMap = [];
                foreach ($userPairs as $p) {
                    if (isset($p[0])) {
                        $userPairMap[$p[0]] = $p[1] ?? '';
                    }
                }

                $correctCount = 0;
                foreach ($correctPairs as $p) {
                    $lId = $p[0] ?? '';
                    $rId = $p[1] ?? '';
                    if ($lId !== '' && isset($userPairMap[$lId]) && $userPairMap[$lId] == $rId) {
                        $correctCount++;
                    }
                }

                $isExact = ($correctCount === $totalPairs);

                if ($scoringMethod === 'partial_credit') {
                    $score = ($correctCount / $totalPairs) * $points;
                } else {
                    $score = $isExact ? $points : 0.00;
                }

                return [$isExact, $score, false];

            case 'drag_drop_ordered':
                // userAnswer format: {"order": ["item_id_1", "item_id_2", ...]}
                // Correct format: $qData['correct_order'] => ["item_id_1", "item_id_2", ...]
                $correctOrder = $qData['correct_order'] ?? [];
                $totalItems = count($correctOrder);

                if ($totalItems === 0) {
                    return [true, $points, false];
                }

                $userOrder = $answerData['order'] ?? [];
                
                $correctCount = 0;
                for ($i = 0; $i < $totalItems; $i++) {
                    if (isset($userOrder[$i]) && $userOrder[$i] === $correctOrder[$i]) {
                        $correctCount++;
                    }
                }

                $isExact = ($correctCount === $totalItems);

                if ($scoringMethod === 'partial_credit') {
                    $score = ($correctCount / $totalItems) * $points;
                } else {
                    $score = $isExact ? $points : 0.00;
                }

                return [$isExact, $score, false];

            case 'matrix_single':
            case 'matrix_multi':
                // userAnswer format: {"answers": {"row_id_1": ["col_id_a", "col_id_b"], "row_id_2": ["col_id_c"]}}
                // Correct format: $qData['correct'] => {"row_id_1": ["col_id_a", "col_id_b"], ...}
                $rows = $qData['rows'] ?? [];
                $totalRows = count($rows);

                if ($totalRows === 0) {
                    return [true, $points, false];
                }

                $correctMap = $qData['correct'] ?? [];
                $userAnswers = $answerData['answers'] ?? [];

                $correctRowsCount = 0;
                foreach ($rows as $row) {
                    $rowId = $row['id'] ?? '';
                    $rowCorrectCols = (array)($correctMap[$rowId] ?? []);
                    $rowUserCols = (array)($userAnswers[$rowId] ?? []);

                    sort($rowCorrectCols);
                    sort($rowUserCols);

                    if ($rowCorrectCols === $rowUserCols) {
                        $correctRowsCount++;
                    }
                }

                $isExact = ($correctRowsCount === $totalRows);

                if ($scoringMethod === 'partial_credit') {
                    $score = ($correctRowsCount / $totalRows) * $points;
                } else {
                    $score = $isExact ? $points : 0.00;
                }

                return [$isExact, $score, false];

            case 'cloze_dropdown':
            case 'cloze_dragdrop':
                // userAnswer format: {"blanks": {"blank_1_id": "val", "blank_2_id": "val"}}
                // Correct format: $qData['blanks'] => [ {"id": "blank_1_id", "correct": "correct_text"}, ... ]
                $blanks = $qData['blanks'] ?? [];
                $totalBlanks = count($blanks);

                if ($totalBlanks === 0) {
                    return [true, $points, false];
                }

                $userBlanks = $answerData['blanks'] ?? [];

                $correctCount = 0;
                foreach ($blanks as $b) {
                    $bId = $b['id'] ?? '';
                    $correctText = trim(strtolower($b['correct'] ?? ''));
                    $userText = trim(strtolower($userBlanks[$bId] ?? ''));

                    if ($correctText !== '' && $userText === $correctText) {
                        $correctCount++;
                    }
                }

                $isExact = ($correctCount === $totalBlanks);

                if ($scoringMethod === 'partial_credit') {
                    $score = ($correctCount / $totalBlanks) * $points;
                } else {
                    $score = $isExact ? $points : 0.00;
                }

                return [$isExact, $score, false];

            case 'highlight':
                // userAnswer format: {"segments": ["seg_1_id", "seg_3_id"]}
                // Correct format: $qData['correct_segment_ids'] => ["seg_1_id", "seg_3_id"]
                $correctSegmentIds = $qData['correct_segment_ids'] ?? [];
                $totalCorrect = count($correctSegmentIds);

                if ($totalCorrect === 0) {
                    return [true, $points, false];
                }

                $userSelected = $answerData['segments'] ?? [];
                if (!is_array($userSelected)) { $userSelected = []; }

                // Count correct & incorrect selections
                $correctSelected = count(array_intersect($userSelected, $correctSegmentIds));
                $incorrectSelected = count(array_diff($userSelected, $correctSegmentIds));

                sort($userSelected);
                sort($correctSegmentIds);
                $isExact = ($userSelected === $correctSegmentIds);

                if ($scoringMethod === 'partial_credit') {
                    $ratio = ($correctSelected - $incorrectSelected) / $totalCorrect;
                    $ratio = max(0.0, $ratio);
                    $score = $ratio * $points;
                } else {
                    $score = $isExact ? $points : 0.00;
                }

                return [$isExact, $score, false];

            case 'bowtie':
                // userAnswer format: {"left": ["id"], "center": ["id"], "right": ["id"]}
                // Correct format: $qData['correct'] => {"left": ["id"], "center": ["id"], "right": ["id"]}
                // Bowtie defaults to partial_credit unless specified as all_or_nothing.
                $correctMap = $qData['correct'] ?? [];
                $correctLeft = (array)($correctMap['left'] ?? []);
                $correctCenter = (array)($correctMap['center'] ?? []);
                $correctRight = (array)($correctMap['right'] ?? []);

                $totalTarget = count($correctLeft) + count($correctCenter) + count($correctRight);
                if ($totalTarget === 0) {
                    return [true, $points, false];
                }

                $userLeft = (array)($answerData['left'] ?? []);
                $userCenter = (array)($answerData['center'] ?? []);
                $userRight = (array)($answerData['right'] ?? []);

                $correctLeftCount = count(array_intersect($userLeft, $correctLeft));
                $correctCenterCount = count(array_intersect($userCenter, $correctCenter));
                $correctRightCount = count(array_intersect($userRight, $correctRight));

                $totalCorrectUser = $correctLeftCount + $correctCenterCount + $correctRightCount;

                sort($userLeft); sort($correctLeft);
                sort($userCenter); sort($correctCenter);
                sort($userRight); sort($correctRight);

                $isExact = ($userLeft === $correctLeft) && ($userCenter === $correctCenter) && ($userRight === $correctRight);

                // Default to partial_credit unless explicitly all_or_nothing
                $isPartial = ($scoringMethod !== 'all_or_nothing');

                if ($isPartial) {
                    $ratio = $totalCorrectUser / $totalTarget;
                    $score = $ratio * $points;
                } else {
                    $score = $isExact ? $points : 0.00;
                }

                return [$isExact, $score, false];

            case 'fill_blank_calc':
                // userAnswer format: {"value": "numeric"}
                // Correct format: $qData['correct_value'] => numeric, $qData['tolerance'] => numeric
                $correctValue = floatval($qData['correct_value'] ?? 0);
                $tolerance = floatval($qData['tolerance'] ?? 0);

                $userValStr = $answerData['value'] ?? null;
                if ($userValStr === null || trim($userValStr) === '') {
                    return [false, 0.00, false];
                }

                $userValue = floatval($userValStr);
                $isCorrect = (abs($userValue - $correctValue) <= $tolerance);
                $score = $isCorrect ? $points : 0.00;
                return [$isCorrect, $score, false];

            case 'essay':
                // Essay is skipped here; always needs manual grading
                return [null, null, true];
        }

        return [false, 0.00, false];
    }

    /**
     * Legacy gradeAnswer to preserve signatures if any external files call it
     */
    public static function gradeAnswer($question, $options, $userAnswer) {
        return self::gradeSingleAnswer($question, $userAnswer, $options);
    }

    /**
     * Grades all auto-gradable questions for an attempt, and flags essay questions.
     */
    public static function gradeAttempt($attemptId) {
        $db = Database::getInstance()->getConnection();

        // Get attempt and exam details
        $stmt = $db->prepare("
            SELECT ea.*, e.pass_percentage 
            FROM exam_attempts ea 
            JOIN exams e ON ea.exam_id = e.id 
            WHERE ea.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        if (!$attempt) return false;

        $qIds = json_decode($attempt['resolved_question_ids'] ?? '[]', true);
        if (empty($qIds)) {
            $qIds = json_decode($attempt['resolved_questions'] ?? '[]', true) ?: [];
        }

        // Get all answers saved so far
        $stmt = $db->prepare("SELECT * FROM attempt_answers WHERE attempt_id = ?");
        $stmt->execute([$attemptId]);
        $answers = $stmt->fetchAll();
        $answersByQId = [];
        foreach ($answers as $ans) {
            $answersByQId[$ans['question_id']] = $ans;
        }

        $totalPointsAwarded = 0.00;
        $totalPossiblePoints = 0.00;
        $hasUngradedEssay = false;

        foreach ($qIds as $questionId) {
            // Get question text/type/points
            $stmtQ = $db->prepare("SELECT * FROM questions WHERE id = ?");
            $stmtQ->execute([$questionId]);
            $question = $stmtQ->fetch();
            if (!$question) continue;

            $ansRow = $answersByQId[$questionId] ?? null;
            $userAnswerData = $ansRow ? json_decode($ansRow['answer_data'], true) : null;

            // Preserve manual grades on essays if we are re-calculating
            if ($question['type'] === 'essay') {
                if ($ansRow && $ansRow['needs_manual_grading'] == 0 && $ansRow['points_awarded'] !== null) {
                    $isCorrect = $ansRow['is_correct'];
                    $pointsAwarded = floatval($ansRow['points_awarded']);
                    $needsManual = false;
                } else {
                    $isCorrect = null;
                    $pointsAwarded = null;
                    $needsManual = true;
                }
            } else {
                list($isCorrect, $pointsAwarded, $needsManual) = self::gradeSingleAnswer($question, $userAnswerData);
            }

            // Write results back to attempt_answers row
            if ($ansRow) {
                $upStmt = $db->prepare("
                    UPDATE attempt_answers 
                    SET is_correct = ?, points_awarded = ?, needs_manual_grading = ? 
                    WHERE id = ?
                ");
                $upStmt->execute([
                    $isCorrect === null ? null : ($isCorrect ? 1 : 0),
                    $pointsAwarded,
                    $needsManual ? 1 : 0,
                    $ansRow['id']
                ]);
            } else {
                $insStmt = $db->prepare("
                    INSERT INTO attempt_answers (attempt_id, question_id, answer_data, is_correct, points_awarded, needs_manual_grading)
                    VALUES (?, ?, NULL, ?, ?, ?)
                ");
                $insStmt->execute([
                    $attemptId,
                    $questionId,
                    $isCorrect === null ? null : ($isCorrect ? 1 : 0),
                    $pointsAwarded,
                    $needsManual ? 1 : 0
                ]);
            }

            $totalPossiblePoints += floatval($question['points']);
            if ($needsManual) {
                $hasUngradedEssay = true;
            } else {
                $totalPointsAwarded += floatval($pointsAwarded);
            }
        }

        // Calculate passing and update attempt status
        $status = $hasUngradedEssay ? 'submitted' : 'graded';
        $percentage = $totalPossiblePoints > 0 ? ($totalPointsAwarded / $totalPossiblePoints) * 100 : 0.00;
        $passed = ($percentage >= floatval($attempt['pass_percentage'])) ? 1 : 0;

        $upAttempt = $db->prepare("
            UPDATE exam_attempts 
            SET score = ?, percentage = ?, passed = ?, status = ?
            WHERE id = ?
        ");
        $upAttempt->execute([
            $totalPointsAwarded,
            $percentage,
            $passed,
            $status,
            $attemptId
        ]);

        // Auto-complete quiz milestone on Learning Path if passed and graded
        if ($passed && $status === 'graded') {
            self::checkAndCompleteQuizMilestone($attempt['user_id'], $attempt['exam_id']);
        }

        if ($status === 'graded') {
            require_once __DIR__ . '/GradebookCalculator.php';
            GradebookCalculator::recordQuizScore($attemptId);
        }

        return true;
    }

    /**
     * Recalculates total score when an essay question gets graded manually.
     * Delegates directly to the robust gradeAttempt logic to preserve correctness and clean updates.
     */
    public static function recalculateAttemptScore($attemptId) {
        return self::gradeAttempt($attemptId);
    }

    /**
     * Automatically completes matching quiz learning path items when a student passes an exam.
     */
    public static function checkAndCompleteQuizMilestone($userId, $examId) {
        $db = Database::getInstance()->getConnection();
        
        // Find matching learning path items in courses where user is enrolled
        $stmt = $db->prepare("
            SELECT lpi.id 
            FROM learning_path_items lpi
            JOIN course_enrollments ce ON lpi.course_id = ce.course_id
            WHERE ce.student_id = ? AND lpi.item_type = 'quiz' AND lpi.item_id = ?
        ");
        $stmt->execute([$userId, $examId]);
        $lpItems = $stmt->fetchAll();
        
        if (!empty($lpItems)) {
            require_once __DIR__ . '/../admin/models/LearningPathProgress.php';
            $progressModel = new LearningPathProgress();
            foreach ($lpItems as $lpItem) {
                $progressModel->markComplete($userId, $lpItem['id']);
            }
        }
    }
}
