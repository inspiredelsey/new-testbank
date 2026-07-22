<?php
/**
 * Student Learning Path Controller - Test Bank LMS
 * Manages rendering the student's sequenced curriculum progress, lock/unlock mechanics,
 * and transitioning progress status as items are accessed.
 */

require_once __DIR__ . '/../../admin/models/LearningPathItem.php';
require_once __DIR__ . '/../../admin/models/LearningPathProgress.php';
require_once __DIR__ . '/../../admin/models/Course.php';
require_once __DIR__ . '/../../admin/models/Document.php';
require_once __DIR__ . '/../../admin/models/Link.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class LearningPathController {
    private $learningPathItemModel;
    private $learningPathProgressModel;
    private $courseModel;
    private $documentModel;
    private $linkModel;

    public function __construct() {
        Auth::requireLogin();
        $this->learningPathItemModel = new LearningPathItem();
        $this->learningPathProgressModel = new LearningPathProgress();
        $this->courseModel = new Course();
        $this->documentModel = new Document();
        $this->linkModel = new Link();
    }

    /**
     * Route handler for student path activities
     */
    public function handleRequest($action = 'view') {
        switch ($action) {
            case 'view':
                $this->handleView();
                break;
            case 'access':
                $this->handleAccess();
                break;
            case 'complete_lp_item':
                $this->handleCompleteLpItem();
                break;
            default:
                header("Location: index.php?route=student/dashboard");
                exit;
        }
    }

    /**
     * Action: View student course learning path with lock states and progress bar
     */
    private function handleView() {
        $courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $this->courseModel->find($courseId);
        $user = Auth::user();

        if (!$course || $course['status'] !== 'published') {
            header("Location: index.php?route=student/dashboard&error=" . urlencode("Course not found or unavailable."));
            exit;
        }

        // Verify student is actually enrolled in this course
        if (!$this->courseModel->isEnrolled($courseId, $user['id'])) {
            header("Location: index.php?route=student/dashboard&error=" . urlencode("You are not enrolled in this course."));
            exit;
        }

        // Idempotent initialization of learning path progress tracker rows for this student
        $this->learningPathProgressModel->initializeForUser($user['id'], $courseId);

        // Fetch ordered progress tracking list
        $progressItems = $this->learningPathProgressModel->forUser($user['id'], $courseId);

        // Calculate curriculum progress statistics
        $totalCount = count($progressItems);
        $completedCount = 0;
        foreach ($progressItems as $item) {
            if ($item['status'] === 'completed') {
                $completedCount++;
            }
        }
        $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        include __DIR__ . '/../../site/views/learning-path/view.php';
    }

    /**
     * Action: Handle clicks/access to unlocked learning path items.
     * Transition state to in_progress then completed, and redirect to target document, link, or quiz.
     */
    private function handleAccess() {
        $lpItemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = Auth::user();

        $progressRows = $this->learningPathProgressModel->forUser($user['id'], 0); // retrieve overall list
        $targetItem = null;
        
        // Find matching item
        foreach ($progressRows as $row) {
            if ((int)$row['learning_path_item_id'] === $lpItemId) {
                $targetItem = $row;
                break;
            }
        }

        // Fallback: search raw if course context wasn't loaded in forUser
        if (!$targetItem) {
            $rawItem = $this->learningPathItemModel->find($lpItemId);
            if ($rawItem) {
                // Initialize in case they bypassed view
                $this->learningPathProgressModel->initializeForUser($user['id'], $rawItem['course_id']);
                $pRows = $this->learningPathProgressModel->forUser($user['id'], $rawItem['course_id']);
                foreach ($pRows as $row) {
                    if ((int)$row['learning_path_item_id'] === $lpItemId) {
                        $targetItem = $row;
                        break;
                    }
                }
            }
        }

        if (!$targetItem) {
            header("Location: index.php?route=student/dashboard&error=" . urlencode("Content item not found."));
            exit;
        }

        // Verify student is enrolled in the item's course
        if (!$this->courseModel->isEnrolled($targetItem['course_id'], $user['id'])) {
            header("Location: index.php?route=student/dashboard&error=" . urlencode("Access unauthorized."));
            exit;
        }

        // Verify item is unlocked (meaning its status isn't 'locked')
        if ($targetItem['status'] === 'locked') {
            header("Location: index.php?route=student/course/view&id=" . $targetItem['course_id'] . "&error=" . urlencode("This item is locked until prerequisites are met."));
            exit;
        }

        // 1. Mark In Progress
        $this->learningPathProgressModel->markInProgress($user['id'], $lpItemId);

        // 2. Mark Complete (simplest approach: complete immediately on open since there is no separate full page viewer)
        $this->learningPathProgressModel->markComplete($user['id'], $lpItemId);

        // 3. Resolve target resource URL and redirect the tab
        require_once __DIR__ . '/../../includes/ActivityLogger.php';
        if ($targetItem['item_type'] === 'document') {
            $doc = $this->documentModel->find($targetItem['item_id']);
            if ($doc && !empty($doc['file_path'])) {
                ActivityLogger::log($user['id'], 'document_viewed', $targetItem['course_id'], 'document', $targetItem['item_id']);
                header("Location: " . $doc['file_path']);
                exit;
            }
        } elseif ($targetItem['item_type'] === 'link') {
            $lnk = $this->linkModel->find($targetItem['item_id']);
            if ($lnk && !empty($lnk['url'])) {
                ActivityLogger::log($user['id'], 'link_opened', $targetItem['course_id'], 'link', $targetItem['item_id']);
                header("Location: " . $lnk['url']);
                exit;
            }
        } elseif ($targetItem['item_type'] === 'quiz') {
            // Once quiz features exist in Phase 3/4, this will route the student to the test taking view
            header("Location: index.php?route=student/exam/instructions&exam_id=" . $targetItem['item_id']);
            exit;
        }

        // Fallback redirection back to course learning path
        header("Location: index.php?route=student/course/view&id=" . $targetItem['course_id']);
        exit;
    }

    /**
     * Action: POST endpoint to support asynchronous completions
     */
    private function handleCompleteLpItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Security token mismatch']);
            exit;
        }

        $lpItemId = isset($_POST['lp_item_id']) ? (int)$_POST['lp_item_id'] : 0;
        $user = Auth::user();

        $rawItem = $this->learningPathItemModel->find($lpItemId);
        if (!$rawItem) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Learning path item not found']);
            exit;
        }

        if (!$this->courseModel->isEnrolled($rawItem['course_id'], $user['id'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized enrollment check']);
            exit;
        }

        // Complete the item and unlock subordinates
        $success = $this->learningPathProgressModel->markComplete($user['id'], $lpItemId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }
}
