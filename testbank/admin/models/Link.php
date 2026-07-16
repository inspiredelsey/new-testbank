<?php
/**
 * Link Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class Link {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByCourse($courseId, $onlyPublished = false) {
        $query = "SELECT * FROM links WHERE course_id = ?";
        if ($onlyPublished) {
            $query .= " AND status = 'published'";
        }
        $query .= " ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM links WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO links (course_id, title, url, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['course_id'],
            $data['title'],
            $data['url'],
            $data['status'] ?? 'published'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE links 
            SET title = ?, url = ?, status = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['url'],
            $data['status'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM links WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
