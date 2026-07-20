<?php
/**
 * Link Controller - Test Bank LMS
 * Manages links, editing, deletion, and order management of course resource URLs.
 */

require_once __DIR__ . '/../models/Link.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class LinkController {
    private $linkModel;
    private $courseModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->linkModel = new Link();
        $this->courseModel = new Course();
    }

    /**
     * Dispatch routing requests based on action.
     */
    public function handleRequest($action = 'list') {
        switch ($action) {
            case 'list':
                $this->handleList();
                break;

            case 'create':
                $this->handleCreate();
                break;

            case 'edit':
                $this->handleEdit();
                break;

            case 'delete':
                $this->handleDelete();
                break;

            case 'reorder':
                $this->handleReorder();
                break;

            default:
                header("Location: index.php?route=admin/courses&action=list");
                exit;
        }
    }

    /**
     * Action: List links of a specific course.
     */
    private function handleList() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $links = $this->linkModel->forCourse($courseId);
        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/links/list.php';
    }

    /**
     * Action: Create a new link.
     */
    private function handleCreate() {
        $courseId = isset($_REQUEST['course_id']) ? (int)$_REQUEST['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $errors = [];
        $title = '';
        $url = '';
        $description = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::validateCSRF($token)) {
                header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');

            // Title validation
            if (empty($title)) {
                $errors['title'] = "Title is required.";
            } elseif (strlen($title) > 200) {
                $errors['title'] = "Title cannot exceed 200 characters.";
            }

            // URL validation
            if (empty($url)) {
                $errors['url'] = "URL is required.";
            } elseif (strlen($url) > 500) {
                $errors['url'] = "URL cannot exceed 500 characters.";
            } else {
                // Must be valid URL format
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors['url'] = "Must be a valid URL format.";
                } else {
                    // Must start with http:// or https:// (strict schema check)
                    $lowerUrl = strtolower($url);
                    if (strpos($lowerUrl, 'http://') !== 0 && strpos($lowerUrl, 'https://') !== 0) {
                        $errors['url'] = "URL must start with http:// or https://.";
                    }
                }
            }

            if (empty($errors)) {
                $success = $this->linkModel->create([
                    'course_id' => $courseId,
                    'title' => $title,
                    'url' => $url,
                    'description' => $description
                ]);

                if ($success) {
                    header("Location: index.php?route=admin/links&action=list&course_id=" . $courseId . "&success=" . urlencode("Link added successfully."));
                    exit;
                } else {
                    $errors['form'] = "Failed to save the link record to the database.";
                }
            }
        }

        $csrfToken = Session::getCSRFToken();
        $actionUrl = "index.php?route=admin/links&action=create&course_id=" . $courseId;
        $formTitle = "Add Link";

        include __DIR__ . '/../views/links/form.php';
    }

    /**
     * Action: Edit an existing link.
     */
    private function handleEdit() {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $link = $this->linkModel->find($id);

        if (!$link) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Link not found."));
            exit;
        }

        $courseId = $link['course_id'];
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $errors = [];
        $title = $link['title'];
        $url = $link['url'];
        $description = $link['description'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::validateCSRF($token)) {
                header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');

            // Title validation
            if (empty($title)) {
                $errors['title'] = "Title is required.";
            } elseif (strlen($title) > 200) {
                $errors['title'] = "Title cannot exceed 200 characters.";
            }

            // URL validation
            if (empty($url)) {
                $errors['url'] = "URL is required.";
            } elseif (strlen($url) > 500) {
                $errors['url'] = "URL cannot exceed 500 characters.";
            } else {
                // Must be valid URL format
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors['url'] = "Must be a valid URL format.";
                } else {
                    // Must start with http:// or https:// (strict schema check)
                    $lowerUrl = strtolower($url);
                    if (strpos($lowerUrl, 'http://') !== 0 && strpos($lowerUrl, 'https://') !== 0) {
                        $errors['url'] = "URL must start with http:// or https://.";
                    }
                }
            }

            if (empty($errors)) {
                $success = $this->linkModel->update($id, [
                    'title' => $title,
                    'url' => $url,
                    'description' => $description
                ]);

                if ($success) {
                    header("Location: index.php?route=admin/links&action=list&course_id=" . $courseId . "&success=" . urlencode("Link updated successfully."));
                    exit;
                } else {
                    $errors['form'] = "Failed to update link details in the database.";
                }
            }
        }

        $csrfToken = Session::getCSRFToken();
        $actionUrl = "index.php?route=admin/links&action=edit&id=" . $id;
        $formTitle = "Edit Link";

        include __DIR__ . '/../views/links/form.php';
    }

    /**
     * Action: Remove link from DB.
     */
    private function handleDelete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $link = $this->linkModel->find($id);
        if (!$link) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Link not found."));
            exit;
        }

        $courseId = $link['course_id'];
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $success = $this->linkModel->delete($id);

        if ($success) {
            header("Location: index.php?route=admin/links&action=list&course_id=" . $courseId . "&success=" . urlencode("Link successfully deleted."));
            exit;
        } else {
            header("Location: index.php?route=admin/links&action=list&course_id=" . $courseId . "&error=" . urlencode("Failed to delete link from database."));
            exit;
        }
    }

    /**
     * Action: Change the order index of links using Swapping logic.
     */
    private function handleReorder() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $direction = trim($_GET['direction'] ?? '');
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/links&action=list&course_id=" . $courseId . "&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        // Get current ordered links
        $links = $this->linkModel->forCourse($courseId);
        $linkIds = array_column($links, 'id');

        $targetIndex = array_search($id, $linkIds);
        if ($targetIndex !== false) {
            if ($direction === 'up' && $targetIndex > 0) {
                // Swap target with previous element
                $temp = $linkIds[$targetIndex];
                $linkIds[$targetIndex] = $linkIds[$targetIndex - 1];
                $linkIds[$targetIndex - 1] = $temp;
            } elseif ($direction === 'down' && $targetIndex < count($linkIds) - 1) {
                // Swap target with next element
                $temp = $linkIds[$targetIndex];
                $linkIds[$targetIndex] = $linkIds[$targetIndex + 1];
                $linkIds[$targetIndex + 1] = $temp;
            }

            $this->linkModel->reorder($courseId, $linkIds);
        }

        header("Location: index.php?route=admin/links&action=list&course_id=" . $courseId . "&success=" . urlencode("Link sequence updated successfully."));
        exit;
    }

    /**
     * Helper: Enforce instructor scoping rules on courses
     */
    private function requireCourseOwnershipOrAdmin($course) {
        $user = Auth::user();
        if ($user['role'] !== 'admin' && (int)$course['instructor_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2 style='color:#dc3545; font-weight: 700;'>403 - Access Forbidden</h2>";
            echo "<p style='color:#6c757d;'>You do not have permission to manage links for this course because it belongs to another instructor.</p>";
            echo "<p style='margin-top:20px;'><a href='index.php?route=admin/courses' style='color:#0d6efd; text-decoration:none; font-weight:600;'>&larr; Return to My Courses</a></p>";
            echo "</div>";
            exit;
        }
    }
}
