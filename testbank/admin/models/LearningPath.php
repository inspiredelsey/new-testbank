<?php
/**
 * Learning Path Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class LearningPath {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getItemsByCourse($courseId) {
        $stmt = $this->db->prepare("
            SELECT lpi.*,
                   lpi.item_type as type,
                   lpi.prerequisite_item_id as prerequisite_id,
                   CASE 
                       WHEN lpi.item_type = 'document' THEN d.title
                       WHEN lpi.item_type = 'link' THEN ln.title
                       WHEN lpi.item_type = 'quiz' THEN ex.title
                   END as item_title,
                   CASE 
                       WHEN lpi.item_type = 'document' THEN d.status
                       WHEN lpi.item_type = 'link' THEN 'published'
                       WHEN lpi.item_type = 'quiz' THEN ex.status
                   END as item_status,
                   p.item_type as prereq_type,
                   CASE 
                       WHEN p.item_type = 'document' THEN pd.title
                       WHEN p.item_type = 'link' THEN pln.title
                       WHEN p.item_type = 'quiz' THEN pex.title
                   END as prereq_title
            FROM learning_path_items lpi
            LEFT JOIN documents d ON lpi.item_type = 'document' AND lpi.item_id = d.id
            LEFT JOIN links ln ON lpi.item_type = 'link' AND lpi.item_id = ln.id
            LEFT JOIN exams ex ON lpi.item_type = 'quiz' AND lpi.item_id = ex.id
            LEFT JOIN learning_path_items p ON lpi.prerequisite_item_id = p.id
            LEFT JOIN documents pd ON p.item_type = 'document' AND p.item_id = pd.id
            LEFT JOIN links pln ON p.item_type = 'link' AND p.item_id = pln.id
            LEFT JOIN exams pex ON p.item_type = 'quiz' AND p.item_id = pex.id
            WHERE lpi.course_id = ?
            ORDER BY lpi.order_index ASC, lpi.id ASC
        ");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public function getItemById($id) {
        $stmt = $this->db->prepare("
            SELECT lpi.*,
                   lpi.item_type as type,
                   lpi.prerequisite_item_id as prerequisite_id,
                   CASE 
                       WHEN lpi.item_type = 'document' THEN d.title
                       WHEN lpi.item_type = 'link' THEN ln.title
                       WHEN lpi.item_type = 'quiz' THEN ex.title
                   END as item_title
            FROM learning_path_items lpi
            LEFT JOIN documents d ON lpi.item_type = 'document' AND lpi.item_id = d.id
            LEFT JOIN links ln ON lpi.item_type = 'link' AND lpi.item_id = ln.id
            LEFT JOIN exams ex ON lpi.item_type = 'quiz' AND lpi.item_id = ex.id
            WHERE lpi.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function addItem($data) {
        $stmt = $this->db->prepare("
            INSERT INTO learning_path_items (course_id, item_type, item_id, prerequisite_item_id, order_index)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['course_id'],
            $data['type'],
            $data['item_id'],
            !empty($data['prerequisite_id']) ? $data['prerequisite_id'] : null,
            $data['order_index'] ?? 0
        ]);
        return $this->db->lastInsertId();
    }

    public function updateItem($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE learning_path_items 
            SET prerequisite_item_id = ?, order_index = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            !empty($data['prerequisite_id']) ? $data['prerequisite_id'] : null,
            $data['order_index'],
            $id
        ]);
    }

    public function deleteItem($id) {
        $stmt = $this->db->prepare("DELETE FROM learning_path_items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAvailableContentsForLP($courseId, $type) {
        if ($type === 'document') {
            $stmt = $this->db->prepare("
                SELECT id, title FROM documents 
                WHERE course_id = ? AND status = 'published'
                  AND id NOT IN (SELECT item_id FROM learning_path_items WHERE course_id = ? AND item_type = 'document')
                ORDER BY title ASC
            ");
            $stmt->execute([$courseId, $courseId]);
            return $stmt->fetchAll();
        } elseif ($type === 'link') {
            $stmt = $this->db->prepare("
                SELECT id, title FROM links 
                WHERE course_id = ?
                  AND id NOT IN (SELECT item_id FROM learning_path_items WHERE course_id = ? AND item_type = 'link')
                ORDER BY title ASC
            ");
            $stmt->execute([$courseId, $courseId]);
            return $stmt->fetchAll();
        } elseif ($type === 'quiz') {
            $stmt = $this->db->prepare("
                SELECT id, title FROM exams 
                WHERE course_id = ? AND status = 'published'
                  AND id NOT IN (SELECT item_id FROM learning_path_items WHERE course_id = ? AND item_type = 'quiz')
                ORDER BY title ASC
            ");
            $stmt->execute([$courseId, $courseId]);
            return $stmt->fetchAll();
        }
        return [];
    }

    // Progress and locks
    public function hasCompletedItem($userId, $learningPathItemId) {
        $stmt = $this->db->prepare("
            SELECT status FROM learning_path_progress 
            WHERE user_id = ? AND learning_path_item_id = ?
        ");
        $stmt->execute([$userId, $learningPathItemId]);
        $res = $stmt->fetch();
        return $res ? ($res['status'] === 'completed') : false;
    }

    public function markItemCompleted($userId, $learningPathItemId) {
        try {
            require_once __DIR__ . '/LearningPathProgress.php';
            $progressModel = new LearningPathProgress();
            return $progressModel->markComplete($userId, $learningPathItemId);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUserProgress($userId, $courseId) {
        $stmt = $this->db->prepare("
            SELECT lpi.id, lpp.status, lpp.completed_at 
            FROM learning_path_items lpi
            LEFT JOIN learning_path_progress lpp ON lpi.id = lpp.learning_path_item_id AND lpp.user_id = ?
            WHERE lpi.course_id = ?
        ");
        $stmt->execute([$userId, $courseId]);
        $rows = $stmt->fetchAll();
        
        $progress = [];
        foreach ($rows as $r) {
            $progress[$r['id']] = [
                'completed' => ($r['status'] === 'completed'),
                'completed_at' => $r['completed_at']
            ];
        }
        return $progress;
    }

    public function isItemLocked($userId, $lpItem, $userProgress = null) {
        if (empty($lpItem['prerequisite_id'])) {
            return false;
        }
        
        if ($userProgress === null) {
            return !$this->hasCompletedItem($userId, $lpItem['prerequisite_id']);
        }
        
        $prereqId = $lpItem['prerequisite_id'];
        return !isset($userProgress[$prereqId]) || !$userProgress[$prereqId]['completed'];
    }
}
