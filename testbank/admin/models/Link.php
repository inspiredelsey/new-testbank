<?php
/**
 * Link Model - Test Bank LMS
 * Manages database records for course content links.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Link {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Dynamic resilient schema check/creation to ensure links table always exists
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $query = "CREATE TABLE IF NOT EXISTS links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                url TEXT NOT NULL,
                description TEXT,
                order_index INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            )";
        } else {
            $query = "CREATE TABLE IF NOT EXISTS links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                url VARCHAR(500) NOT NULL,
                description TEXT NULL,
                order_index INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                INDEX idx_link_course (course_id),
                INDEX idx_link_order (order_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        try {
            @$this->db->exec($query);
        } catch (Exception $e) {
            error_log("Link::__construct schema init warning: " . $e->getMessage());
        }
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
