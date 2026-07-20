<?php
/**
 * Learning Path Controller - Test Bank LMS
 * Handles the configuration, creation, modification, sequencing, and deletion of learning path items.
 */

require_once __DIR__ . '/../models/LearningPathItem.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Link.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class LearningPathController {
    private $learningPathItemModel;
    private $courseModel;
    private $documentModel;
    private $linkModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->learningPathItemModel = new LearningPathItem();
        $this->courseModel = new Course();
        $this->documentModel = new Document();
        $this->linkModel = new Link();
    }

    /**
     * Entry routing endpoint
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
     * Action: List the learning path items of a specific course
     */
    private function handleList() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $items = $this->learningPathItemModel->forCourse($courseId);
        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/learning-path/manage.php';
    }

    /**
     * Action: Add an item to the course learning path
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
        $itemType = 'document';
        $itemId = 0;
        $prerequisiteItemId = null;
        $isRequired = 1;

        // Fetch available documents and links for the course
        $documents = $this->documentModel->forCourse($courseId);
        $links = $this->linkModel->forCourse($courseId);
        // Exclude items already added to the path to avoid redundant items
        $existingItems = $this->learningPathItemModel->forCourse($courseId);
        
        $existingKeys = [];
        foreach ($existingItems as $ei) {
            $existingKeys[$ei['item_type'] . '_' . $ei['item_id']] = true;
        }

        // Fetch other items currently in the path for the prerequisite dropdown
        $possiblePrerequisites = $existingItems;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::validateCSRF($token)) {
                header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&error=" . urlencode("Security token validation failed."));
                exit;
            }

            $itemType = trim($_POST['item_type'] ?? 'document');
            $itemId = (int)($_POST['item_id'] ?? 0);
            $prerequisiteItemId = !empty($_POST['prerequisite_item_id']) ? (int)$_POST['prerequisite_item_id'] : null;
            $isRequired = isset($_POST['is_required']) ? 1 : 0;

            // Validate item type and ID combination
            if (!in_array($itemType, ['document', 'link', 'quiz'])) {
                $errors['item_type'] = "Invalid content item type selected.";
            } else {
                if ($itemType === 'document') {
                    $doc = $this->documentModel->find($itemId);
                    if (!$doc || (int)$doc['course_id'] !== $courseId) {
                        $errors['item_id'] = "The selected document is invalid or belongs to another course.";
                    }
                } elseif ($itemType === 'link') {
                    $lnk = $this->linkModel->find($itemId);
                    if (!$lnk || (int)$lnk['course_id'] !== $courseId) {
                        $errors['item_id'] = "The selected link is invalid or belongs to another course.";
                    }
                } elseif ($itemType === 'quiz') {
                    // Quiz isn't built yet, so skip verify or block since none exist yet.
                    // The prompt notes: "skip this check for 'quiz' type since none exist yet, just store the value"
                    if ($itemId <= 0) {
                        $errors['item_id'] = "Please specify a valid quiz reference ID.";
                    }
                }
            }

            // Verify prerequisite validity
            if (!empty($prerequisiteItemId)) {
                $prereqItem = $this->learningPathItemModel->find($prerequisiteItemId);
                if (!$prereqItem || (int)$prereqItem['course_id'] !== $courseId) {
                    $errors['prerequisite_item_id'] = "The selected prerequisite item does not belong to this course.";
                }
            }

            // Prevent duplicate entries of the same item in the learning path
            if (empty($errors['item_id']) && isset($existingKeys[$itemType . '_' . $itemId])) {
                $errors['item_id'] = "This item is already included in the course learning path.";
            }

            if (empty($errors)) {
                $success = $this->learningPathItemModel->create([
                    'course_id' => $courseId,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'prerequisite_item_id' => $prerequisiteItemId,
                    'is_required' => $isRequired
                ]);

                if ($success) {
                    header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&success=" . urlencode("Learning path item added successfully."));
                    exit;
                } else {
                    $errors['form'] = "Failed to write learning path record to database.";
                }
            }
        }

        $csrfToken = Session::getCSRFToken();
        $actionUrl = "index.php?route=admin/learning-path&action=create&course_id=" . $courseId;
        $formTitle = "Add Path Item";
        $isEdit = false;

        include __DIR__ . '/../views/learning-path/form.php';
    }

    /**
     * Action: Edit learning path item configurations
     */
    private function handleEdit() {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $item = $this->learningPathItemModel->find($id);

        if (!$item) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Learning path item not found."));
            exit;
        }

        $courseId = $item['course_id'];
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $errors = [];
        $itemType = $item['item_type'];
        $itemId = $item['item_id'];
        $prerequisiteItemId = $item['prerequisite_item_id'];
        $isRequired = $item['is_required'];

        // Get all items in the course except the item itself for prerequisite candidates
        $allPathItems = $this->learningPathItemModel->forCourse($courseId);
        $possiblePrerequisites = array_filter($allPathItems, function($pi) use ($id) {
            return (int)$pi['id'] !== (int)$id;
        });

        // Dummy/Empty lists since we don't allow altering item_type/item_id on edit
        $documents = [];
        $links = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::validateCSRF($token)) {
                header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&error=" . urlencode("Security token validation failed."));
                exit;
            }

            $prerequisiteItemId = !empty($_POST['prerequisite_item_id']) ? (int)$_POST['prerequisite_item_id'] : null;
            $isRequired = isset($_POST['is_required']) ? 1 : 0;

            // Validate prerequisite is not itself
            if (!empty($prerequisiteItemId) && (int)$prerequisiteItemId === (int)$id) {
                $errors['prerequisite_item_id'] = "An item cannot have itself as a prerequisite.";
            }

            // Verify prerequisite belongs to the same course
            if (!empty($prerequisiteItemId) && empty($errors)) {
                $prereqItem = $this->learningPathItemModel->find($prerequisiteItemId);
                if (!$prereqItem || (int)$prereqItem['course_id'] !== $courseId) {
                    $errors['prerequisite_item_id'] = "The selected prerequisite item does not belong to this course.";
                }
            }

            if (empty($errors)) {
                $success = $this->learningPathItemModel->update($id, [
                    'prerequisite_item_id' => $prerequisiteItemId,
                    'is_required' => $isRequired,
                    'order_index' => $item['order_index']
                ]);

                if ($success) {
                    header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&success=" . urlencode("Path item updated successfully."));
                    exit;
                } else {
                    $errors['form'] = "Failed to update database record.";
                }
            }
        }

        $csrfToken = Session::getCSRFToken();
        $actionUrl = "index.php?route=admin/learning-path&action=edit&id=" . $id;
        $formTitle = "Edit Path Item";
        $isEdit = true;

        include __DIR__ . '/../views/learning-path/form.php';
    }

    /**
     * Action: Remove item from learning path
     */
    private function handleDelete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $item = $this->learningPathItemModel->find($id);
        if (!$item) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Path item not found."));
            exit;
        }

        $courseId = $item['course_id'];
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        // Check if other items depend on this first for high quality UI signaling
        if ($this->learningPathItemModel->isDependedOn($id)) {
            header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&error=" . urlencode("Cannot delete this item because another item in the path depends on it as a prerequisite. Please remove the dependency first."));
            exit;
        }

        $success = $this->learningPathItemModel->delete($id);

        if ($success) {
            header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&success=" . urlencode("Path item successfully removed."));
            exit;
        } else {
            header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&error=" . urlencode("Failed to remove item from database."));
            exit;
        }
    }

    /**
     * Action: Swap / reorder sequence using standard swapping mechanism
     */
    private function handleReorder() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $direction = trim($_GET['direction'] ?? '');
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $items = $this->learningPathItemModel->forCourse($courseId);
        $itemIds = array_column($items, 'id');

        $targetIndex = array_search($id, $itemIds);
        if ($targetIndex !== false) {
            if ($direction === 'up' && $targetIndex > 0) {
                $temp = $itemIds[$targetIndex];
                $itemIds[$targetIndex] = $itemIds[$targetIndex - 1];
                $itemIds[$targetIndex - 1] = $temp;
            } elseif ($direction === 'down' && $targetIndex < count($itemIds) - 1) {
                $temp = $itemIds[$targetIndex];
                $itemIds[$targetIndex] = $itemIds[$targetIndex + 1];
                $itemIds[$targetIndex + 1] = $temp;
            }

            $this->learningPathItemModel->reorder($courseId, $itemIds);
        }

        header("Location: index.php?route=admin/learning-path&action=list&course_id=" . $courseId . "&success=" . urlencode("Learning path sequence updated."));
        exit;
    }

    /**
     * Helper: Restrict course editing to authorized instructor or admin.
     */
    private function requireCourseOwnershipOrAdmin($course) {
        $user = Auth::user();
        if ($user['role'] !== 'admin' && (int)$course['instructor_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2 style='color:#dc3545; font-weight: 700;'>403 - Access Forbidden</h2>";
            echo "<p style='color:#6c757d;'>You do not have permission to manage the learning path for this course because it belongs to another instructor.</p>";
            echo "<p style='margin-top:20px;'><a href='index.php?route=admin/courses' style='color:#0d6efd; text-decoration:none; font-weight:600;'>&larr; Return to My Courses</a></p>";
            echo "</div>";
            exit;
        }
    }
}
