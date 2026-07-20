<?php
/**
 * Learning Path Item Model - Test Bank LMS
 * Manages sequencing of course content items (documents, links, quizzes).
 */

require_once __DIR__ . '/../../includes/Database.php';

class LearningPathItem {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Dynamic resilient schema check/creation to ensure learning path tables always exist
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $queryItems = "CREATE TABLE IF NOT EXISTS learning_path_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                item_type TEXT NOT NULL,
                item_id INTEGER NOT NULL,
                order_index INTEGER DEFAULT 0,
                prerequisite_item_id INTEGER NULL,
                is_required INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (prerequisite_item_id) REFERENCES learning_path_items(id) ON DELETE SET NULL
            )";
        } else {
            $queryItems = "CREATE TABLE IF NOT EXISTS learning_path_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                item_type ENUM('document', 'link', 'quiz') NOT NULL,
                item_id INT NOT NULL,
                order_index INT DEFAULT 0,
                prerequisite_item_id INT NULL,
                is_required BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (prerequisite_item_id) REFERENCES learning_path_items(id) ON DELETE SET NULL,
                INDEX idx_lpi_course (course_id),
                INDEX idx_lpi_order (order_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        try {
            @$this->db->exec($queryItems);
        } catch (Exception $e) {
            error_log("LearningPathItem::__construct schema init warning: " . $e->getMessage());
        }
    }

    /**
     * Helper to resolve the human-readable title of a course item.
     * 
     * @param string $type
     * @param int $id
     * @return string
     */
    public function resolveItemTitle($type, $id) {
        try {
            if ($type === 'document') {
                $stmt = $this->db->prepare("SELECT title FROM documents WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetchColumn() ?: '[Unknown Document]';
            } elseif ($type === 'link') {
                $stmt = $this->db->prepare("SELECT title FROM links WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetchColumn() ?: '[Unknown Link]';
            } elseif ($type === 'quiz') {
                // Since Quizzes/Exams are not yet implemented in Phase 2, we return a placeholder.
                // In Phase 3, this will join on/lookup the exams table.
                return "[Quiz - not yet built]";
            }
        } catch (PDOException $e) {
            error_log("LearningPathItem::resolveItemTitle error: " . $e->getMessage());
        }
        return '[Unknown Item]';
    }

    /**
     * Retrieve all learning path items for a course, ordered by order_index.
     * 
     * @param int $courseId
     * @return array
     */
    public function forCourse($courseId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM learning_path_items WHERE course_id = ? ORDER BY order_index ASC, id ASC");
            $stmt->execute([$courseId]);
            $items = $stmt->fetchAll() ?: [];

            // Resolve actual title and prerequisite titles for list rendering
            foreach ($items as &$item) {
                $item['title'] = $this->resolveItemTitle($item['item_type'], $item['item_id']);
                
                $item['prerequisite_title'] = null;
                if (!empty($item['prerequisite_item_id'])) {
                    $prereq = $this->findPrereqRaw($item['prerequisite_item_id']);
                    if ($prereq) {
                        $item['prerequisite_title'] = $this->resolveItemTitle($prereq['item_type'], $prereq['item_id']);
                    }
                }
            }
            unset($item);
            return $items;
        } catch (PDOException $e) {
            error_log("LearningPathItem::forCourse error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Internal raw lookup helper to avoid recursive title resolution loop.
     */
    private function findPrereqRaw($id) {
        try {
            $stmt = $this->db->prepare("SELECT item_type, item_id FROM learning_path_items WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Find a single learning path item record.
     * 
     * @param int $id
     * @return array|null
     */
    public function find($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM learning_path_items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch() ?: null;
            if ($item) {
                $item['title'] = $this->resolveItemTitle($item['item_type'], $item['item_id']);
            }
            return $item;
        } catch (PDOException $e) {
            error_log("LearningPathItem::find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new learning path item.
     * 
     * @param array $data keys: course_id, item_type, item_id, order_index (optional), prerequisite_item_id (optional), is_required
     * @return int|bool
     */
    public function create($data) {
        try {
            if (!isset($data['order_index'])) {
                $stmt = $this->db->prepare("SELECT MAX(order_index) FROM learning_path_items WHERE course_id = ?");
                $stmt->execute([$data['course_id']]);
                $maxOrder = $stmt->fetchColumn();
                $data['order_index'] = ($maxOrder !== false) ? (int)$maxOrder + 1 : 0;
            }

            // Verify prerequisite belongs to the same course if set
            if (!empty($data['prerequisite_item_id'])) {
                $stmt = $this->db->prepare("SELECT course_id FROM learning_path_items WHERE id = ?");
                $stmt->execute([$data['prerequisite_item_id']]);
                $prereqCourseId = $stmt->fetchColumn();
                if ($prereqCourseId !== false && (int)$prereqCourseId !== (int)$data['course_id']) {
                    $data['prerequisite_item_id'] = null;
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO learning_path_items (course_id, item_type, item_id, order_index, prerequisite_item_id, is_required)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([
                $data['course_id'],
                $data['item_type'],
                $data['item_id'],
                $data['order_index'],
                !empty($data['prerequisite_item_id']) ? $data['prerequisite_item_id'] : null,
                isset($data['is_required']) ? (int)$data['is_required'] : 1
            ]);

            return $success ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("LearningPathItem::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing learning path item.
     * 
     * @param int $id
     * @param array $data keys: prerequisite_item_id (optional), is_required, order_index
     * @return bool
     */
    public function update($id, $data) {
        try {
            // Prevent self-referential prerequisite
            if (!empty($data['prerequisite_item_id']) && (int)$data['prerequisite_item_id'] === (int)$id) {
                $data['prerequisite_item_id'] = null;
            }

            // Verify prerequisite belongs to the same course
            if (!empty($data['prerequisite_item_id'])) {
                $stmt = $this->db->prepare("SELECT course_id FROM learning_path_items WHERE id = ?");
                $stmt->execute([$data['prerequisite_item_id']]);
                $prereqCourseId = $stmt->fetchColumn();

                $stmtSelf = $this->db->prepare("SELECT course_id FROM learning_path_items WHERE id = ?");
                $stmtSelf->execute([$id]);
                $selfCourseId = $stmtSelf->fetchColumn();

                if ($prereqCourseId !== false && $selfCourseId !== false && (int)$prereqCourseId !== (int)$selfCourseId) {
                    $data['prerequisite_item_id'] = null;
                }
            }

            $stmt = $this->db->prepare("
                UPDATE learning_path_items 
                SET prerequisite_item_id = ?, is_required = ?, order_index = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                !empty($data['prerequisite_item_id']) ? $data['prerequisite_item_id'] : null,
                isset($data['is_required']) ? (int)$data['is_required'] : 1,
                (int)$data['order_index'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("LearningPathItem::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if any other item in the database depends on this item as a prerequisite.
     * 
     * @param int $id
     * @return bool
     */
    public function isDependedOn($id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM learning_path_items WHERE prerequisite_item_id = ?");
            $stmt->execute([$id]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a learning path item.
     * Choice: Block deletion if another item depends on this one as a prerequisite.
     * This provides robust guidance to the user and avoids broken dependencies.
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            if ($this->isDependedOn($id)) {
                return false; // Blocked due to dependents
            }

            // Clear any progress entries first to avoid foreign key violations
            $stmtProgress = $this->db->prepare("DELETE FROM learning_path_progress WHERE learning_path_item_id = ?");
            $stmtProgress->execute([$id]);

            $stmtDelete = $this->db->prepare("DELETE FROM learning_path_items WHERE id = ?");
            return $stmtDelete->execute([$id]);
        } catch (PDOException $e) {
            error_log("LearningPathItem::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reorder an array of item IDs for a course.
     * 
     * @param int $courseId
     * @param array $orderedIds
     * @return bool
     */
    public function reorder($courseId, array $orderedIds) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE learning_path_items SET order_index = ? WHERE id = ? AND course_id = ?");
            
            foreach ($orderedIds as $index => $id) {
                $stmt->execute([$index, $id, $courseId]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("LearningPathItem::reorder error: " . $e->getMessage());
            return false;
        }
    }
}
