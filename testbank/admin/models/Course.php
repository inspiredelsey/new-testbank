<?php
/**
 * Course Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class Course {
    private $db;

    public function __construct() {
        // Columns are defined once in the canonical /sql/schema.sql — no per-request ALTER TABLE checks here.
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all courses with category name and instructor name joined.
     */
    public function all() {
        $query = "
            SELECT c.*, cat.name as category_name, u.name as instructor_name,
                   (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrollment_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get courses for a specific instructor with category name and instructor name joined.
     */
    public function byInstructor($instructorId) {
        $query = "
            SELECT c.*, cat.name as category_name, u.name as instructor_name,
                   (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrollment_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE c.instructor_id = ?
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$instructorId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Find a course by ID with category and instructor names joined.
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, cat.name as category_name, u.name as instructor_name,
                   (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrollment_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Legacy support / alias to find()
     */
    public function getById($id) {
        return $this->find($id);
    }

    /**
     * Legacy support / alias to support filters in index/list views if needed
     */
    public function getAll($filters = []) {
        $query = "
            SELECT c.*, cat.name as category_name, u.name as instructor_name,
                   (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrollment_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['instructor_id'])) {
            $query .= " AND c.instructor_id = ?";
            $params[] = $filters['instructor_id'];
        }
        if (!empty($filters['category_id'])) {
            $query .= " AND c.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $query .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY c.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Create a new course.
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO courses (title, description, category_id, instructor_id, thumbnail, status, pass_percentage)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            !empty($data['category_id']) ? intval($data['category_id']) : null,
            !empty($data['instructor_id']) ? intval($data['instructor_id']) : null,
            $data['thumbnail'] ?? null,
            $data['status'] ?? 'draft',
            isset($data['pass_percentage']) ? floatval($data['pass_percentage']) : 50.00
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update an existing course.
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE courses 
            SET title = ?, description = ?, category_id = ?, instructor_id = ?, thumbnail = ?, status = ?, pass_percentage = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            !empty($data['category_id']) ? intval($data['category_id']) : null,
            !empty($data['instructor_id']) ? intval($data['instructor_id']) : null,
            $data['thumbnail'] ?? null,
            $data['status'] ?? 'draft',
            isset($data['pass_percentage']) ? floatval($data['pass_percentage']) : 50.00,
            $id
        ]);
    }

    /**
     * Change course status.
     */
    public function setStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE courses SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Safely delete a course if it has no course_enrollments.
     */
    public function delete($id) {
        // Check first whether the course has any course_enrollments rows
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM course_enrollments WHERE course_id = ?");
        $stmt->execute([$id]);
        $enrollmentsCount = intval($stmt->fetchColumn());

        if ($enrollmentsCount > 0) {
            throw new Exception("Cannot delete course because there are active student enrollments. Please archive the course instead.");
        }

        // Hard delete
        $stmt = $this->db->prepare("DELETE FROM courses WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- Core enrollment and management helper methods preserved from original Course model ---
    
    public function enrollStudent($courseId, $studentId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO course_enrollments (course_id, student_id)
                VALUES (?, ?)
            ");
            return $stmt->execute([$courseId, $studentId]);
        } catch (PDOException $e) {
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
        return $stmt->fetchAll() ?: [];
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
        return $stmt->fetchAll() ?: [];
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
        return $stmt->fetchAll() ?: [];
    }
}
