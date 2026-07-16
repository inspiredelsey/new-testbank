<?php
/**
 * Document Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class Document {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByCourse($courseId, $onlyPublished = false) {
        $query = "SELECT * FROM documents WHERE course_id = ?";
        if ($onlyPublished) {
            $query .= " AND status = 'published'";
        }
        $query .= " ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO documents (course_id, title, file_name, file_path, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['course_id'],
            $data['title'],
            $data['file_name'],
            $data['file_path'],
            $data['status'] ?? 'published'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE documents 
            SET title = ?, file_name = ?, file_path = ?, status = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['file_name'],
            $data['file_path'],
            $data['status'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
