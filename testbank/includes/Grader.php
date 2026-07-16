<?php
/**
 * Auto Grader class for evaluating test attempts.
 */

require_once __DIR__ . '/Database.php';

class Grader {

    /**
     * Grades a single question answer.
     * Returns array: [is_correct, points_awarded, needs_manual_grading]
     */
    public static function gradeAnswer($question, $options, $userAnswer) {
        $type = $question['type'];
        $points = floatval($question['points']);
        
        if ($userAnswer === null || (is_string($userAnswer) && trim($userAnswer) === '') || (is_array($userAnswer) && empty($userAnswer))) {
            return [false, 0.00, false];
        }

        switch ($type) {
            case 'mcq_single':
                // Find correct option ID
                $correctOptId = null;
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctOptId = $opt['id'];
                        break;
                    }
                }
                $isCorrect = (intval($userAnswer) === intval($correctOptId));
                $score = $isCorrect ? $points : 0.00;
                return [$isCorrect, $score, false];

            case 'mcq_multi':
                // MCQ multi: userAnswer is array of option IDs.
                // It is correct only if the set of selected options exactly matches the set of correct options.
                $correctOptIds = [];
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctOptIds[] = intval($opt['id']);
                    }
                }
                
                $userOptIds = array_map('intval', (array)$userAnswer);
                sort($correctOptIds);
                sort($userOptIds);
                
                $isCorrect = ($correctOptIds === $userOptIds);
                $score = $isCorrect ? $points : 0.00;
                return [$isCorrect, $score, false];

            case 'true_false':
                // Find correct option text
                $correctText = 'true';
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctText = strtolower(trim($opt['option_text']));
                        break;
                    }
                }
                
                // Map boolean representation
                $userVal = strtolower(trim($userAnswer));
                if ($userVal === '1') $userVal = 'true';
                if ($userVal === '0') $userVal = 'false';
                
                $correctVal = $correctText;
                if ($correctVal === '1') $correctVal = 'true';
                if ($correctVal === '0') $correctVal = 'false';

                $isCorrect = ($userVal === $correctVal);
                $score = $isCorrect ? $points : 0.00;
                return [$isCorrect, $score, false];

            case 'fill_blank':
                // Fill blank: userAnswer is text. Options are accepted correct strings.
                // Case-insensitive exact trim comparison with any option
                $userVal = strtolower(trim($userAnswer));
                $isCorrect = false;
                foreach ($options as $opt) {
                    if (strtolower(trim($opt['option_text'])) === $userVal) {
                        $isCorrect = true;
                        break;
                    }
                }
                $score = $isCorrect ? $points : 0.00;
                return [$isCorrect, $score, false];

            case 'matching':
                // Matching: userAnswer is array of [left_option_id => matched_pair_key_string]
                // Each match is graded pro-rated
                $totalPairs = count($options);
                if ($totalPairs === 0) return [true, $points, false];
                
                $correctCount = 0;
                $userPairs = (array)$userAnswer;
                
                foreach ($options as $opt) {
                    $leftId = $opt['id'];
                    $expectedRight = trim(strtolower($opt['pair_key']));
                    $actualRight = isset($userPairs[$leftId]) ? trim(strtolower($userPairs[$leftId])) : '';
                    
                    if ($expectedRight === $actualRight && $actualRight !== '') {
                        $correctCount++;
                    }
                }
                
                $isCorrect = ($correctCount === $totalPairs);
                $score = ($correctCount / $totalPairs) * $points;
                return [$isCorrect, $score, false];

            case 'essay':
                // Essay questions require manual grading by an instructor
                return [null, 0.00, true];
        }

        return [false, 0.00, false];
    }

    /**
     * Grades all auto-gradable questions for an attempt, and flags essay questions.
     */
    public static function gradeAttempt($attemptId) {
        $db = Database::getInstance()->getConnection();

        // Get attempt and exam
        $stmt = $db->prepare("
            SELECT ea.*, e.pass_percentage 
            FROM exam_attempts ea 
            JOIN exams e ON ea.exam_id = e.id 
            WHERE ea.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        if (!$attempt) return false;

        // Get all answers saved so far
        $stmt = $db->prepare("SELECT * FROM attempt_answers WHERE attempt_id = ?");
        $stmt->execute([$attemptId]);
        $answers = $stmt->fetchAll();

        $totalScore = 0.00;
        $totalPossiblePoints = 0.00;
        $hasEssay = false;

        foreach ($answers as $ans) {
            $questionId = $ans['question_id'];
            
            // Get question text/type/points
            $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$questionId]);
            $question = $stmt->fetch();
            if (!$question) continue;

            // Get options
            $stmt = $db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY order_index ASC");
            $stmt->execute([$questionId]);
            $options = $stmt->fetchAll();

            $userAnswerData = json_decode($ans['answer_data'], true);

            list($isCorrect, $pointsAwarded, $needsManual) = self::gradeAnswer($question, $options, $userAnswerData);

            // Update answer record
            $upStmt = $db->prepare("
                UPDATE attempt_answers 
                SET is_correct = ?, points_awarded = ?, needs_manual_grading = ? 
                WHERE id = ?
            ");
            $upStmt->execute([
                $isCorrect === null ? null : ($isCorrect ? 1 : 0),
                $pointsAwarded,
                $needsManual ? 1 : 0,
                $ans['id']
            ]);

            $totalPossiblePoints += floatval($question['points']);
            if ($needsManual) {
                $hasEssay = true;
            } else {
                $totalScore += floatval($pointsAwarded);
            }
        }

        // Calculate passing and update attempt status
        $status = $hasEssay ? 'submitted' : 'graded';
        $percentage = $totalPossiblePoints > 0 ? ($totalScore / $totalPossiblePoints) * 100 : 0.00;
        $passed = ($percentage >= floatval($attempt['pass_percentage'])) ? 1 : 0;

        $upAttempt = $db->prepare("
            UPDATE exam_attempts 
            SET score = ?, percentage = ?, passed = ?, status = ?, submitted_at = NOW() 
            WHERE id = ?
        ");
        $upAttempt->execute([
            $totalScore,
            $percentage,
            $passed,
            $status,
            $attemptId
        ]);

        // Auto-complete quiz milestone on Learning Path if passed
        if ($passed && $status === 'graded') {
            self::checkAndCompleteQuizMilestone($attempt['user_id'], $attempt['exam_id']);
        }

        return true;
    }

    /**
     * Recalculates total score when an essay question gets graded manually.
     */
    public static function recalculateAttemptScore($attemptId) {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT ea.*, e.pass_percentage 
            FROM exam_attempts ea 
            JOIN exams e ON ea.exam_id = e.id 
            WHERE ea.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        if (!$attempt) return false;

        // Check if there are any remaining essays needing manual grading
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM attempt_answers WHERE attempt_id = ? AND needs_manual_grading = 1");
        $stmt->execute([$attemptId]);
        $remainingCount = $stmt->fetch()['count'];

        // Calculate sum of awarded points and sum of total points
        $stmt = $db->prepare("
            SELECT SUM(aa.points_awarded) as score, SUM(q.points) as total 
            FROM attempt_answers aa
            JOIN questions q ON aa.question_id = q.id
            WHERE aa.attempt_id = ?
        ");
        $stmt->execute([$attemptId]);
        $sums = $stmt->fetch();

        $totalScore = floatval($sums['score'] ?? 0.00);
        $totalPossible = floatval($sums['total'] ?? 1.00);

        $percentage = $totalPossible > 0 ? ($totalScore / $totalPossible) * 100 : 0.00;
        $passed = ($percentage >= floatval($attempt['pass_percentage'])) ? 1 : 0;
        $status = ($remainingCount > 0) ? 'submitted' : 'graded';

        $upAttempt = $db->prepare("
            UPDATE exam_attempts 
            SET score = ?, percentage = ?, passed = ?, status = ? 
            WHERE id = ?
        ");
        $upAttempt->execute([
            $totalScore,
            $percentage,
            $passed,
            $status,
            $attemptId
        ]);

        // Auto-complete quiz milestone on Learning Path if passed
        if ($passed && $status === 'graded') {
            self::checkAndCompleteQuizMilestone($attempt['user_id'], $attempt['exam_id']);
        }

        return true;
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
            WHERE ce.student_id = ? AND lpi.type = 'quiz' AND lpi.item_id = ?
        ");
        $stmt->execute([$userId, $examId]);
        $lpItems = $stmt->fetchAll();
        
        foreach ($lpItems as $lpItem) {
            $stmtInsert = $db->prepare("
                INSERT INTO learning_path_progress (user_id, learning_path_item_id, completed, completed_at)
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
            ");
            $stmtInsert->execute([$userId, $lpItem['id']]);
        }
    }
}
