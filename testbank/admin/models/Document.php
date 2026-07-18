<?php
/**
 * Document Model - Test Bank LMS
 * Manages database records for course content documents.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Document {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Dynamic resilient schema check/creation to ensure documents table always exists
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $query = "CREATE TABLE IF NOT EXISTS documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                file_path TEXT NOT NULL,
                file_type TEXT NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'published',
                order_index INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            )";
        } else {
            $query = "CREATE TABLE IF NOT EXISTS documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_type VARCHAR(50) NOT NULL,
                description TEXT NULL,
                status VARCHAR(50) DEFAULT 'published',
                order_index INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                INDEX idx_document_course (course_id),
                INDEX idx_document_order (order_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        try {
            @$this->db->exec($query);
        } catch (Exception $e) {
            error_log("Document::__construct schema init warning: " . $e->getMessage());
        }
    }

    /**
     * Retrieve all documents for a course ordered by order_index.
     * 
     * @param int $courseId
     * @return array
     */
    public function forCourse($courseId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM documents WHERE course_id = ? ORDER BY order_index ASC, id ASC");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Document::forCourse error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a single document record by ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function find($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Document::find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new document.
     * Auto-assigns order_index if not provided.
     * 
     * @param array $data Contains keys: course_id, title, file_path, file_type, description (optional), order_index (optional)
     * @return int|bool Inserted ID on success, false on failure.
     */
    public function create($data) {
        try {
            // Determine next order_index if not specified
            if (!isset($data['order_index'])) {
                $stmt = $this->db->prepare("SELECT MAX(order_index) FROM documents WHERE course_id = ?");
                $stmt->execute([$data['course_id']]);
                $maxOrder = $stmt->fetchColumn();
                $data['order_index'] = ($maxOrder !== false) ? (int)$maxOrder + 1 : 0;
            }

            $stmt = $this->db->prepare("
                INSERT INTO documents (course_id, title, file_path, file_type, description, order_index)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([
                $data['course_id'],
                $data['title'],
                $data['file_path'],
                $data['file_type'],
                $data['description'] ?? null,
                $data['order_index']
            ]);

            return $success ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Document::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing document record.
     * 
     * @param int $id
     * @param array $data Contains keys: title, file_path, file_type, description
     * @return bool
     */
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE documents 
                SET title = ?, file_path = ?, file_type = ?, description = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['title'],
                $data['file_path'],
                $data['file_type'],
                $data['description'] ?? null,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Document::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a document row from DB and delete its physical file.
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $doc = $this->find($id);
        if (!$doc) {
            return false;
        }

        try {
            // Delete DB row
            $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
            $success = $stmt->execute([$id]);

            if ($success && !empty($doc['file_path'])) {
                // Delete physical file from uploads folder
                $filePath = $doc['file_path'];
                
                // 1. Check relative to root directory (since it is saved as e.g. "uploads/...")
                $fullPath = __DIR__ . '/../../' . ltrim($filePath, '/');
                if (file_exists($fullPath) && is_file($fullPath)) {
                    @unlink($fullPath);
                } else {
                    // 2. Check as absolute path
                    if (file_exists($filePath) && is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Document::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reorder a full set of documents for a course in one call.
     * 
     * @param int $courseId
     * @param array $orderedIds List of document IDs in the desired order.
     * @return bool
     */
    public function reorder($courseId, array $orderedIds) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE documents SET order_index = ? WHERE id = ? AND course_id = ?");
            
            foreach ($orderedIds as $index => $id) {
                $stmt->execute([$index, $id, $courseId]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Document::reorder error: " . $e->getMessage());
            return false;
        }
    }
}
