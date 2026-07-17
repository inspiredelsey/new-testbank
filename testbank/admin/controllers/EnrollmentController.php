<?php
/**
 * Enrollment Controller - Test Bank LMS
 * Manages courses enrollments, individual student enrollments, and group enrollments.
 */

require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Group.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class EnrollmentController {
    private $enrollmentModel;
    private $courseModel;
    private $userModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->enrollmentModel = new Enrollment();
        $this->courseModel = new Course();
        $this->userModel = new User();
    }

    /**
     * Dispatch routing requests based on action parameter.
     */
    public function handleRequest($action = 'manage') {
        switch ($action) {
            case 'manage':
                $this->handleManage();
                break;

            case 'enroll_single':
                $this->handleEnrollSingle();
                break;

            case 'enroll_group':
                $this->handleEnrollGroup();
                break;

            case 'status':
                $this->handleStatusChange();
                break;

            case 'unenroll':
                $this->handleUnenroll();
                break;

            default:
                header("Location: index.php?route=admin/courses&action=list");
                exit;
        }
    }

    /**
     * Action: Show the Enrollment Management interface for a specific course.
     */
    private function handleManage() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        // Enforce instructor scope
        $this->requireCourseOwnershipOrAdmin($course);

        // Fetch current enrollments
        $enrollments = $this->enrollmentModel->forCourse($courseId);

        // Fetch candidates for individual enrollment (non-enrolled students)
        $eligibleStudents = $this->courseModel->getNonEnrolledStudents($courseId);

        // Fetch all groups for the group enrollment dropdown
        $groups = Group::all();

        // Check for any session-based group enrollment results
        $groupResult = Session::get('group_enroll_result');
        if ($groupResult) {
            Session::remove('group_enroll_result');
        }

        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/enrollments/manage.php';
    }

    /**
     * Action: Handle POST to enroll a single user.
     */
    private function handleEnrollSingle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/courses&action=list");
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        // Enforce instructor scope
        $this->requireCourseOwnershipOrAdmin($course);

        // Validate student exists and is active
        $user = $this->userModel->find($userId);
        if (!$user || $user['status'] !== 'active') {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $courseId . "&error=" . urlencode("Selected user does not exist or is inactive."));
            exit;
        }

        // Call model
        $result = $this->enrollmentModel->enrollUser($courseId, $userId);

        if ($result === 'already_enrolled') {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $courseId . "&error=" . urlencode("User is already enrolled in this course."));
            exit;
        } elseif ($result === true) {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $courseId . "&success=" . urlencode("Student " . $user['name'] . " enrolled successfully."));
            exit;
        } else {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $courseId . "&error=" . urlencode("Failed to enroll user due to a system error."));
            exit;
        }
    }

    /**
     * Action: Handle POST to enroll an entire group.
     */
    private function handleEnrollGroup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/courses&action=list");
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        // Enforce instructor scope
        $this->requireCourseOwnershipOrAdmin($course);

        // Validate group exists
        $group = Group::find($groupId);
        if (!$group) {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $courseId . "&error=" . urlencode("Selected group does not exist."));
            exit;
        }

        // Get members to check if empty
        $members = Group::members($groupId);
        if (empty($members)) {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $courseId . "&error=" . urlencode("Group \"" . $group['name'] . "\" has no members."));
            exit;
        }

        // Enroll group members
        $summary = $this->enrollmentModel->enrollGroup($courseId, $groupId);

        // Save result in session for display
        $msg = "Group \"" . $group['name'] . "\" processed: " . $summary['new'] . " users newly enrolled, " . $summary['already'] . " were already enrolled.";
        if ($summary['errors'] > 0) {
            $msg .= " (" . $summary['errors'] . " errors encountered)";
        }

        Session::set('group_enroll_result', $summary);

        header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $courseId . "&success=" . urlencode($msg));
        exit;
    }

    /**
     * Action: Handle Status Change (active/completed/dropped).
     */
    private function handleStatusChange() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $status = trim($_GET['status'] ?? '');
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $enrollment = $this->enrollmentModel->find($id);
        if (!$enrollment) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Enrollment record not found."));
            exit;
        }

        $course = $this->courseModel->find($enrollment['course_id']);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Associated course not found."));
            exit;
        }

        // Enforce instructor scope
        $this->requireCourseOwnershipOrAdmin($course);

        if (!in_array($status, ['active', 'completed', 'dropped'])) {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $course['id'] . "&error=" . urlencode("Invalid status value requested."));
            exit;
        }

        $success = $this->enrollmentModel->setStatus($id, $status);

        if ($success) {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $course['id'] . "&success=" . urlencode("Enrollment status successfully changed to " . ucfirst($status) . "."));
            exit;
        } else {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $course['id'] . "&error=" . urlencode("Failed to update enrollment status."));
            exit;
        }
    }

    /**
     * Action: Handle unenroll (sets status to 'dropped' to preserve history).
     */
    private function handleUnenroll() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $enrollment = $this->enrollmentModel->find($id);
        if (!$enrollment) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Enrollment record not found."));
            exit;
        }

        $course = $this->courseModel->find($enrollment['course_id']);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Associated course not found."));
            exit;
        }

        // Enforce instructor scope
        $this->requireCourseOwnershipOrAdmin($course);

        // Perform soft unenrollment (update status to 'dropped')
        $success = $this->enrollmentModel->unenroll($id);

        if ($success) {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $course['id'] . "&success=" . urlencode("User successfully unenrolled (status set to dropped to preserve history)."));
            exit;
        } else {
            header("Location: index.php?route=admin/enrollments&action=manage&course_id=" . $course['id'] . "&error=" . urlencode("Failed to unenroll user."));
            exit;
        }
    }

    /**
     * Helper: Enforce instructor server-side course scoping
     */
    private function requireCourseOwnershipOrAdmin($course) {
        $user = Auth::user();
        if ($user['role'] !== 'admin' && (int)$course['instructor_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2 style='color:#dc3545;'>403 - Access Forbidden</h2>";
            echo "<p>You do not have permission to manage enrollments for this course because it belongs to another instructor.</p>";
            echo "<p><a href='index.php?route=admin/courses'>Return to My Courses</a></p>";
            echo "</div>";
            exit;
        }
    }
}
