<?php
/**
 * Link Model - Test Bank LMS
 * Manages database records for course content links.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Link {
    private $db;

    public function __construct() {
        // Table is created once from the canonical /sql/schema.sql — no per-request schema checks here.
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieve all links for a course ordered by order_index.
     * 
     * @param int $courseId
     * @return array
     */
    public function forCourse($courseId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM links WHERE course_id = ? ORDER BY order_index ASC, id ASC");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Link::forCourse error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Alias for forCourse to support compatibility.
     */
    public function getByCourse($courseId) {
        return $this->forCourse($courseId);
    }

    /**
     * Find a single link record by ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function find($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM links WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Link::find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Alias for find to support compatibility.
     */
    public function getById($id) {
        return $this->find($id);
    }

    /**
     * Create a new link.
     * Auto-assigns order_index if not provided.
     * 
     * @param array $data Contains keys: course_id, title, url, description (optional), order_index (optional)
     * @return int|bool Inserted ID on success, false on failure.
     */
    public function create($data) {
        try {
            // Determine next order_index if not specified
            if (!isset($data['order_index'])) {
                $stmt = $this->db->prepare("SELECT MAX(order_index) FROM links WHERE course_id = ?");
                $stmt->execute([$data['course_id']]);
                $maxOrder = $stmt->fetchColumn();
                $data['order_index'] = ($maxOrder !== false) ? (int)$maxOrder + 1 : 0;
            }

            $stmt = $this->db->prepare("
                INSERT INTO links (course_id, title, url, description, order_index)
                VALUES (?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([
                $data['course_id'],
                $data['title'],
                $data['url'],
                $data['description'] ?? null,
                $data['order_index']
            ]);

            return $success ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Link::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing link record.
     * 
     * @param int $id
     * @param array $data Contains keys: title, url, description
     * @return bool
     */
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE links 
                SET title = ?, url = ?, description = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['title'],
                $data['url'],
                $data['description'] ?? null,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Link::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a link.
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM links WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Link::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reorder a full set of links for a course in one call.
     * 
     * @param int $courseId
     * @param array $orderedIds List of link IDs in the desired order.
     * @return bool
     */
    public function reorder($courseId, array $orderedIds) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE links SET order_index = ? WHERE id = ? AND course_id = ?");
            
            foreach ($orderedIds as $index => $id) {
                $stmt->execute([$index, $id, $courseId]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Link::reorder error: " . $e->getMessage());
            return false;
        }
    }
}
