<?php
/**
 * Learning Path Progress Model - Test Bank LMS
 * Tracks and manages student progress on a course's learning path.
 */

require_once __DIR__ . '/../../includes/Database.php';

class LearningPathProgress {
    private $db;

    public function __construct() {
        // Table is created once from the canonical /sql/schema.sql — no per-request schema checks here.
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all progress rows for a student in a specific course.
     * Joins with learning_path_items to pull item details and order_index.
     * 
     * @param int $userId
     * @param int $courseId
     * @return array
     */
    public function forUser($userId, $courseId) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, i.item_type, i.item_id, i.order_index, i.prerequisite_item_id, i.is_required
                FROM learning_path_progress p
                JOIN learning_path_items i ON p.learning_path_item_id = i.id
                WHERE p.user_id = ? AND p.course_id = ?
                ORDER BY i.order_index ASC, i.id ASC
            ");
            $stmt->execute([$userId, $courseId]);
            $progress = $stmt->fetchAll() ?: [];
            
            // Resolve actual human-readable titles
            require_once __DIR__ . '/LearningPathItem.php';
            $lpiModel = new LearningPathItem();

            foreach ($progress as &$row) {
                $row['title'] = $lpiModel->resolveItemTitle($row['item_type'], $row['item_id']);
                
                $row['prerequisite_title'] = null;
                if (!empty($row['prerequisite_item_id'])) {
                    $prereq = $lpiModel->find($row['prerequisite_item_id']);
                    if ($prereq) {
                        $row['prerequisite_title'] = $prereq['title'];
                    }
                }
            }
            unset($row);
            return $progress;
        } catch (PDOException $e) {
            error_log("LearningPathProgress::forUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Initialize progress tracking rows for a user in a course.
     * Safe and idempotent: ignores already-existing progress records.
     * 
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public function initializeForUser($userId, $courseId) {
        try {
            // Get all learning path items for this course
            $stmt = $this->db->prepare("SELECT * FROM learning_path_items WHERE course_id = ? ORDER BY order_index ASC, id ASC");
            $stmt->execute([$courseId]);
            $items = $stmt->fetchAll() ?: [];

            if (empty($items)) {
                return true;
            }

            // Get existing progress records to prevent duplicates
            $stmtExisting = $this->db->prepare("SELECT learning_path_item_id, status FROM learning_path_progress WHERE user_id = ? AND course_id = ?");
            $stmtExisting->execute([$userId, $courseId]);
            $existingProgress = $stmtExisting->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            $this->db->beginTransaction();
            
            $stmtInsert = $this->db->prepare("
                INSERT INTO learning_path_progress (user_id, course_id, learning_path_item_id, status)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($items as $item) {
                $itemId = $item['id'];
                if (isset($existingProgress[$itemId])) {
                    continue; // Already tracked
                }

                $status = 'locked';
                if (empty($item['prerequisite_item_id'])) {
                    // No prerequisite means item is immediately unlocked
                    $status = 'unlocked';
                } else {
                    // Check if the prerequisite item has already been completed by the student
                    $prereqId = $item['prerequisite_item_id'];
                    if (isset($existingProgress[$prereqId]) && $existingProgress[$prereqId] === 'completed') {
                        $status = 'unlocked';
                    }
                }

                $stmtInsert->execute([$userId, $courseId, $itemId, $status]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("LearningPathProgress::initializeForUser error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a learning path item as complete.
     * Triggers downstream unlocks for items whose prerequisites are now satisfied.
     * 
     * @param int $userId
     * @param int $learningPathItemId
     * @return bool
     */
    public function markComplete($userId, $learningPathItemId) {
        try {
            // Look up the item to discover the course_id
            $stmtItem = $this->db->prepare("SELECT course_id FROM learning_path_items WHERE id = ?");
            $stmtItem->execute([$learningPathItemId]);
            $courseId = $stmtItem->fetchColumn();

            if ($courseId === false) {
                return false;
            }

            $this->db->beginTransaction();

            // Set current item status to 'completed'
            $now = date('Y-m-d H:i:s');
            $stmtUpdateSelf = $this->db->prepare("
                UPDATE learning_path_progress 
                SET status = 'completed', completed_at = ? 
                WHERE user_id = ? AND learning_path_item_id = ?
            ");
            $stmtUpdateSelf->execute([$now, $userId, $learningPathItemId]);

            // Unlocks any item having this specific item as its prerequisite in this course
            $stmtUnlock = $this->db->prepare("
                UPDATE learning_path_progress 
                SET status = 'unlocked'
                WHERE user_id = ? 
                  AND status = 'locked'
                  AND learning_path_item_id IN (
                      SELECT id FROM learning_path_items 
                      WHERE course_id = ? AND prerequisite_item_id = ?
                  )
            ");
            $stmtUnlock->execute([$userId, $courseId, $learningPathItemId]);

            $this->db->commit();

            // Log completion
            require_once __DIR__ . '/../../includes/ActivityLogger.php';
            ActivityLogger::log($userId, 'learning_path_item_completed', $courseId, 'learning_path_item', $learningPathItemId);

            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("LearningPathProgress::markComplete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transition status to 'in_progress' if currently 'unlocked'.
     * Prevents regress from 'completed'.
     * 
     * @param int $userId
     * @param int $learningPathItemId
     * @return bool
     */
    public function markInProgress($userId, $learningPathItemId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE learning_path_progress 
                SET status = 'in_progress'
                WHERE user_id = ? AND learning_path_item_id = ? AND status = 'unlocked'
            ");
            return $stmt->execute([$userId, $learningPathItemId]);
        } catch (PDOException $e) {
            error_log("LearningPathProgress::markInProgress error: " . $e->getMessage());
            return false;
        }
    }
}
