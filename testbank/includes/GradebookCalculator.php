<?php
/**
 * Gradebook Calculator Helper
 */

require_once __DIR__ . '/Database.php';

class GradebookCalculator {

    /**
     * Helper to get total points for an exam
     */
    public static function getExamTotalPoints($examId) {
        $db = Database::getInstance()->getConnection();
        
        // 1. Calculate from questions inside exam_questions
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(q.points), 0) as total
            FROM exam_questions eq
            JOIN questions q ON eq.question_id = q.id
            WHERE eq.exam_id = ?
        ");
        $stmt->execute([$examId]);
        $total = floatval($stmt->fetchColumn() ?: 0.0);
        
        // 2. Fallback to questions table if exam has dynamic rule-based questions or was already attempted
        if ($total <= 0) {
            $stmt = $db->prepare("
                SELECT resolved_question_ids FROM exam_attempts 
                WHERE exam_id = ? AND resolved_question_ids IS NOT NULL 
                LIMIT 1
            ");
            $stmt->execute([$examId]);
            $json = $stmt->fetchColumn();
            if ($json) {
                $qIds = json_decode($json, true);
                if (!empty($qIds)) {
                    $placeholders = implode(',', array_fill(0, count($qIds), '?'));
                    $stmtQ = $db->prepare("SELECT SUM(points) FROM questions WHERE id IN ($placeholders)");
                    $stmtQ->execute($qIds);
                    $total = floatval($stmtQ->fetchColumn() ?: 0.0);
                }
            }
        }
        
        return $total > 0 ? $total : 100.00; // default/fallback to 100
    }

    /**
     * Ensures a gradebook_items row of type 'quiz' exists for an exam.
     */
    public static function syncQuizItem($examId) {
        $db = Database::getInstance()->getConnection();
        
        // Load exam details
        $stmt = $db->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();
        
        if (!$exam || empty($exam['course_id'])) {
            return false;
        }
        
        // Only create/sync if exam status is published
        if ($exam['status'] !== 'published') {
            return false;
        }
        
        $maxScore = self::getExamTotalPoints($examId);
        
        // Check if gradebook item already exists
        $stmtCheck = $db->prepare("SELECT * FROM gradebook_items WHERE item_type = 'quiz' AND item_id = ?");
        $stmtCheck->execute([$examId]);
        $existing = $stmtCheck->fetch();
        
        if ($existing) {
            // Update title and max_score to keep in sync. Do NOT overwrite weight!
            // Instructors can adjust weights afterward via the gradebook management UI.
            $stmtUpdate = $db->prepare("
                UPDATE gradebook_items 
                SET title = ?, max_score = ?, course_id = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$exam['title'], $maxScore, $exam['course_id'], $existing['id']]);
            return $existing['id'];
        } else {
            // Auto-create one when published using points total as max_score
            // Sensible default weight is 10.00 (validated to not exceed 100.00 total)
            $stmtSum = $db->prepare("SELECT SUM(weight) FROM gradebook_items WHERE course_id = ?");
            $stmtSum->execute([$exam['course_id']]);
            $currentWeightTotal = floatval($stmtSum->fetchColumn() ?: 0.0);
            
            $defaultWeight = 10.00;
            if ($currentWeightTotal + $defaultWeight > 100.00) {
                $defaultWeight = max(0.00, 100.00 - $currentWeightTotal);
            }
            
            $stmtInsert = $db->prepare("
                INSERT INTO gradebook_items (course_id, item_type, item_id, title, weight, max_score)
                VALUES (?, 'quiz', ?, ?, ?, ?)
            ");
            $stmtInsert->execute([
                $exam['course_id'],
                $examId,
                $exam['title'],
                $defaultWeight,
                $maxScore
            ]);
            return $db->lastInsertId();
        }
    }

    /**
     * Records the score from a student exam attempt into gradebook_scores.
     */
    public static function recordQuizScore($examAttemptId) {
        $db = Database::getInstance()->getConnection();
        
        // Find exam attempt
        $stmtAttempt = $db->prepare("SELECT * FROM exam_attempts WHERE id = ?");
        $stmtAttempt->execute([$examAttemptId]);
        $attempt = $stmtAttempt->fetch();
        
        if (!$attempt || $attempt['status'] !== 'graded') {
            return false;
        }
        
        $examId = $attempt['exam_id'];
        $userId = $attempt['user_id'];
        
        // Find course_id
        $stmtExam = $db->prepare("SELECT course_id FROM exams WHERE id = ?");
        $stmtExam->execute([$examId]);
        $courseId = $stmtExam->fetchColumn();
        
        if (!$courseId) {
            return false;
        }
        
        // Ensure gradebook item exists
        $gradebookItemId = self::syncQuizItem($examId);
        if (!$gradebookItemId) {
            // Find gradebook item if sync returned false because it already exists but exam hasn't changed
            $stmtCheck = $db->prepare("SELECT id FROM gradebook_items WHERE item_type = 'quiz' AND item_id = ?");
            $stmtCheck->execute([$examId]);
            $gradebookItemId = $stmtCheck->fetchColumn();
        }
        
        if (!$gradebookItemId) {
            return false;
        }
        
        // Retrieve student's HIGHEST graded score on this exam to write to the gradebook
        $stmtHighest = $db->prepare("
            SELECT MAX(score) 
            FROM exam_attempts 
            WHERE exam_id = ? AND user_id = ? AND status = 'graded'
        ");
        $stmtHighest->execute([$examId, $userId]);
        $highestScore = floatval($stmtHighest->fetchColumn() ?: 0.0);
        
        // Upsert gradebook score
        $stmtScoreCheck = $db->prepare("
            SELECT id FROM gradebook_scores 
            WHERE gradebook_item_id = ? AND user_id = ?
        ");
        $stmtScoreCheck->execute([$gradebookItemId, $userId]);
        $existingScoreId = $stmtScoreCheck->fetchColumn();
        
        if ($existingScoreId) {
            $stmtUpdate = $db->prepare("
                UPDATE gradebook_scores 
                SET score = ?, recorded_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmtUpdate->execute([$highestScore, $existingScoreId]);
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO gradebook_scores (gradebook_item_id, user_id, score) 
                VALUES (?, ?, ?)
            ");
            return $stmtInsert->execute([$gradebookItemId, $userId, $highestScore]);
        }
    }

    /**
     * Computes the weighted final grade for a student in a course.
     */
    public static function finalGrade($userId, $courseId) {
        $db = Database::getInstance()->getConnection();
        
        // Load all gradebook items for the course
        $stmtItems = $db->prepare("
            SELECT * FROM gradebook_items 
            WHERE course_id = ? 
            ORDER BY id ASC
        ");
        $stmtItems->execute([$courseId]);
        $items = $stmtItems->fetchAll();
        
        $breakdown = [];
        $weightedScoreSum = 0.00;
        $weightSum = 0.00;
        $totalItemsCount = count($items);
        $gradedItemsCount = 0;
        
        foreach ($items as $item) {
            // Get score for this student on this item
            $stmtScore = $db->prepare("
                SELECT score FROM gradebook_scores 
                WHERE gradebook_item_id = ? AND user_id = ?
            ");
            $stmtScore->execute([$item['id'], $userId]);
            $scoreRow = $stmtScore->fetch();
            
            $isGraded = ($scoreRow !== false);
            $score = $isGraded ? floatval($scoreRow['score']) : null;
            $maxScore = floatval($item['max_score'] ?: 100.00);
            $weight = floatval($item['weight'] ?: 0.00);
            
            $weightedPoints = 0.00;
            if ($isGraded && $maxScore > 0) {
                $weightedPoints = ($score / $maxScore) * $weight;
                $weightedScoreSum += $weightedPoints;
                $weightSum += $weight;
                $gradedItemsCount++;
            }
            
            $breakdown[] = [
                'item_id' => $item['id'],
                'title' => $item['title'],
                'item_type' => $item['item_type'],
                'weight' => $weight,
                'max_score' => $maxScore,
                'score' => $score,
                'is_graded' => $isGraded,
                'weighted_points' => $isGraded ? $weightedPoints : 0.00
            ];
        }
        
        $finalPercentage = 0.00;
        if ($weightSum > 0) {
            $finalPercentage = ($weightedScoreSum / $weightSum) * 100;
        }
        
        $isPartial = ($gradedItemsCount < $totalItemsCount);
        $statusLabel = "complete";
        if ($totalItemsCount == 0) {
            $statusLabel = "no items";
        } elseif ($isPartial) {
            $statusLabel = "partial — {$gradedItemsCount} of {$totalItemsCount} items graded";
        }
        
        return [
            'final_grade' => round($finalPercentage, 2),
            'weight_sum' => $weightSum,
            'weighted_score_sum' => round($weightedScoreSum, 2),
            'is_partial' => $isPartial,
            'status_label' => $statusLabel,
            'breakdown' => $breakdown,
            'total_items' => $totalItemsCount,
            'graded_items' => $gradedItemsCount
        ];
    }
}
