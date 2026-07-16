<?php
/**
 * Course Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class Course {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = []) {
        $query = "
            SELECT c.*, u.name as instructor_name,
                   (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrollment_count
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['instructor_id'])) {
            $query .= " AND c.instructor_id = ?";
            $params[] = $filters['instructor_id'];
        }
        if (!empty($filters['status'])) {
            $query .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $query .= " AND c.title LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY c.id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name as instructor_name 
            FROM courses c 
            LEFT JOIN users u ON c.instructor_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO courses (title, description, instructor_id, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['instructor_id'] ?? null,
            $data['status'] ?? 'draft'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE courses 
            SET title = ?, description = ?, instructor_id = ?, status = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['instructor_id'] ?? null,
            $data['status'] ?? 'draft',
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function enrollStudent($courseId, $studentId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO course_enrollments (course_id, student_id)
                VALUES (?, ?)
            ");
            return $stmt->execute([$courseId, $studentId]);
        } catch (PDOException $e) {
            // Already enrolled or other error
            return false;
        }
    }

    public function unenrollStudent($courseId, $studentId) {
        $stmt = $this->db->prepare("
            DELETE FROM course_enrollments 
            WHERE course_id = ? AND student_id = ?
        ");
        return $stmt->execute([$courseId, $studentId]);
    }

    public function isEnrolled($courseId, $studentId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM course_enrollments 
            WHERE course_id = ? AND student_id = ?
        ");
        $stmt->execute([$courseId, $studentId]);
        return intval($stmt->fetchColumn()) > 0;
    }

    public function getEnrolledCourses($studentId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name as instructor_name, ce.enrolled_at 
            FROM courses c
            JOIN course_enrollments ce ON c.id = ce.course_id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE ce.student_id = ? AND c.status = 'published'
            ORDER BY ce.enrolled_at DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function getEnrolledStudents($courseId) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.email, ce.enrolled_at 
            FROM users u
            JOIN course_enrollments ce ON u.id = ce.student_id
            WHERE ce.course_id = ?
            ORDER BY u.name ASC
        ");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public function getNonEnrolledStudents($courseId) {
        $stmt = $this->db->prepare("
            SELECT id, name, email 
            FROM users 
            WHERE role = 'student' 
              AND status = 'active'
              AND id NOT IN (SELECT student_id FROM course_enrollments WHERE course_id = ?)
            ORDER BY name ASC
        ");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }
}
