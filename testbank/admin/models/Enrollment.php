<?php
/**
 * Enrollment Model - Test Bank LMS
 * Manages database records for course enrollments.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Enrollment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Ensure necessary additions to the course_enrollments schema are active on both MySQL and SQLite fallback
        try {
            @$this->db->exec("ALTER TABLE course_enrollments ADD COLUMN group_id INT NULL");
        } catch (Exception $e) {}
        try {
            @$this->db->exec("ALTER TABLE course_enrollments ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'active'");
        } catch (Exception $e) {}
    }

    /**
     * Find a single enrollment record by ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function find($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM course_enrollments WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Enrollment::find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve all enrollments for a course, joined with user details and group name.
     * 
     * @param int $courseId
     * @return array
     */
    public function forCourse($courseId) {
        $query = "SELECT ce.id, ce.course_id, ce.student_id AS user_id, ce.enrolled_at, ce.status, ce.group_id,
                         u.name AS user_name, u.email AS user_email,
                         g.name AS group_name
                  FROM course_enrollments ce
                  JOIN users u ON ce.student_id = u.id
                  LEFT JOIN `groups` g ON ce.group_id = g.id
                  WHERE ce.course_id = ?
                  ORDER BY u.name ASC";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$courseId]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Enrollment::forCourse error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve all course enrollments for a given user.
     * 
     * @param int $userId
     * @return array
     */
    public function forUser($userId) {
        $query = "SELECT ce.id, ce.course_id, ce.enrolled_at, ce.status, ce.group_id,
                         c.title AS course_title, c.description AS course_description,
                         u.name AS instructor_name
                  FROM course_enrollments ce
                  JOIN courses c ON ce.course_id = c.id
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE ce.student_id = ?
                  ORDER BY ce.enrolled_at DESC";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Enrollment::forUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Enroll a single user in a course with status 'active'.
     * Checks first to avoid duplicate enrollment records.
     * 
     * @param int $courseId
     * @param int $userId
     * @param int|null $groupId
     * @return bool|string Returns 'already_enrolled' if duplicate, true if successful, false otherwise.
     */
    public function enrollUser($courseId, $userId, $groupId = null) {
        try {
            // Check if enrollment already exists for this user and course
            $stmt = $this->db->prepare("SELECT id, status FROM course_enrollments WHERE course_id = ? AND student_id = ?");
            $stmt->execute([$courseId, $userId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // If they are already enrolled in some status (active, completed, dropped)
                // We return a clear 'already_enrolled' status as instructed
                return 'already_enrolled';
            }

            // Insert new enrollment row with 'active' status
            $stmt = $this->db->prepare("
                INSERT INTO course_enrollments (course_id, student_id, group_id, status)
                VALUES (?, ?, ?, 'active')
            ");
            $success = $stmt->execute([$courseId, $userId, $groupId]);
            if ($success) {
                require_once __DIR__ . '/../../includes/ActivityLogger.php';
                ActivityLogger::log($userId, 'course_enrolled', $courseId);
            }
            return $success ? true : false;
        } catch (PDOException $e) {
            error_log("Enrollment::enrollUser error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enroll all current active members of a group in a course.
     * 
     * @param int $courseId
     * @param int $groupId
     * @return array Summary of new vs already enrolled users
     */
    public function enrollGroup($courseId, $groupId) {
        require_once __DIR__ . '/Group.php';
        $members = Group::members($groupId);
        
        $newCount = 0;
        $alreadyCount = 0;
        $errors = 0;

        foreach ($members as $member) {
            // Only enroll active students/members
            if ($member['status'] !== 'active') {
                continue;
            }
            
            $result = $this->enrollUser($courseId, $member['id'], $groupId);
            if ($result === 'already_enrolled') {
                $alreadyCount++;
            } elseif ($result === true) {
                $newCount++;
            } else {
                $errors++;
            }
        }

        return [
            'new' => $newCount,
            'already' => $alreadyCount,
            'errors' => $errors,
            'total_members' => count($members)
        ];
    }

    /**
     * Update status to 'active', 'completed', or 'dropped'.
     * 
     * @param int $enrollmentId
     * @param string $status
     * @return bool
     */
    public function setStatus($enrollmentId, $status) {
        $allowedStatuses = ['active', 'completed', 'dropped'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("UPDATE course_enrollments SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $enrollmentId]);
        } catch (PDOException $e) {
            error_log("Enrollment::setStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unenroll a user.
     * We keep the record and set the status to 'dropped' rather than deleting,
     * to preserve historic data (tracking, gradebook, etc.).
     * 
     * @param int $enrollmentId
     * @return bool
     */
    public function unenroll($enrollmentId) {
        // Recommend keeping the row and setting status to 'dropped' rather than deleting,
        // since later phases — gradebook, certificates, tracking — will want history.
        return $this->setStatus($enrollmentId, 'dropped');
    }
}
