<?php
/**
 * Course Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class CourseController {
    private $courseModel;
    private $categoryModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->courseModel = new Course();
        $this->categoryModel = new Category();
    }

    /**
     * Dispatch routing requests based on action parameter.
     */
    public function handleRequest($action = 'index') {
        switch ($action) {
            case 'index':
            case 'list':
                $this->handleList();
                break;

            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleCreatePost();
                } else {
                    $this->handleCreateGet();
                }
                break;

            case 'edit':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleEditPost();
                } else {
                    $this->handleEditGet();
                }
                break;

            case 'status':
                $this->handleStatusChange();
                break;

            case 'delete':
                $this->handleDelete();
                break;

            default:
                header("Location: index.php?route=admin/courses&action=list");
                exit;
        }
    }

    /**
     * Action: List all courses with role-based scoping and filters
     */
    private function handleList() {
        $user = Auth::user();
        
        $selectedCategoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $selectedStatus = !empty($_GET['status']) ? trim($_GET['status']) : '';

        // Build filters for getall/listing
        $filters = [];
        if ($user['role'] !== 'admin') {
            $filters['instructor_id'] = $user['id'];
        }
        if ($selectedCategoryId !== null) {
            $filters['category_id'] = $selectedCategoryId;
        }
        if (!empty($selectedStatus)) {
            $filters['status'] = $selectedStatus;
        }

        $courses = $this->courseModel->getAll($filters);
        
        // Fetch categories for filter dropdown
        $categories = $this->categoryModel->getTreeFlat();
        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/courses/list.php';
    }

    /**
     * Action: Show Create Form
     */
    private function handleCreateGet() {
        $title = "Create Course";
        $submitUrl = "index.php?route=admin/courses&action=create";
        $isEdit = false;

        $errors = Session::get('validation_errors') ?? [];
        $formData = Session::get('form_data') ?? [];

        Session::delete('validation_errors');
        Session::delete('form_data');

        // Fetch instructors for dropdown (admins can select anyone, instructors are locked)
        $instructors = $this->getInstructorsList();
        $flatCategories = $this->categoryModel->getTreeFlat();

        include __DIR__ . '/../views/courses/form.php';
    }

    /**
     * Action: Handle Course Creation (POST)
     */
    private function handleCreatePost() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/courses&action=create&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $user = Auth::user();
        
        // Prepare inputs
        $titleVal = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        
        // If current user is instructor, auto-lock instructor_id to themselves
        $instructorId = ($user['role'] === 'instructor') ? $user['id'] : (!empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null);
        
        $status = $_POST['status'] ?? 'draft';
        $passPercentage = isset($_POST['pass_percentage']) ? trim($_POST['pass_percentage']) : '';

        $errors = [];

        // Validate title
        if (empty($titleVal)) {
            $errors['title'] = "Course title is required.";
        } elseif (strlen($titleVal) > 200) {
            $errors['title'] = "Course title must not exceed 200 characters.";
        }

        // Validate category_id
        if (empty($categoryId)) {
            $errors['category_id'] = "Category is required.";
        } else {
            $categoryObj = $this->categoryModel->find($categoryId);
            if (!$categoryObj) {
                $errors['category_id'] = "Selected category does not exist.";
            }
        }

        // Validate instructor_id
        if (empty($instructorId)) {
            $errors['instructor_id'] = "Instructor is required.";
        } else {
            $instructorObj = $this->getUserById($instructorId);
            if (!$instructorObj || !in_array($instructorObj['role'], ['instructor', 'admin'])) {
                $errors['instructor_id'] = "Selected user must be an instructor or an admin.";
            }
        }

        // Validate pass_percentage
        if ($passPercentage === '') {
            $errors['pass_percentage'] = "Pass percentage is required.";
        } elseif (!is_numeric($passPercentage)) {
            $errors['pass_percentage'] = "Pass percentage must be a numeric value.";
        } else {
            $passFloat = floatval($passPercentage);
            if ($passFloat < 0 || $passFloat > 100) {
                $errors['pass_percentage'] = "Pass percentage must be between 0 and 100.";
            }
        }

        // Validate status
        if (!in_array($status, ['draft', 'published', 'archived'])) {
            $errors['status'] = "Invalid status selected.";
        }

        // Validate and upload thumbnail
        $thumbnailPath = null;
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['thumbnail']['tmp_name'];
            $fileSize = $_FILES['thumbnail']['size'];
            $fileName = $_FILES['thumbnail']['name'];

            if ($fileSize > 2 * 1024 * 1024) {
                $errors['thumbnail'] = "The thumbnail image must not exceed 2MB.";
            } else {
                $imageInfo = @getimagesize($fileTmpPath);
                if ($imageInfo === false) {
                    $errors['thumbnail'] = "Uploaded file is not a valid image.";
                } else {
                    $mimeType = $imageInfo['mime'];
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($mimeType, $allowedMimes)) {
                        $errors['thumbnail'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
                    } else {
                        $uploadDir = __DIR__ . '/../../uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $cleanName = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($fileName));
                        $uniqueName = time() . '_' . $cleanName;
                        if (move_uploaded_file($fileTmpPath, $uploadDir . $uniqueName)) {
                            $thumbnailPath = 'uploads/' . $uniqueName;
                        } else {
                            $errors['thumbnail'] = "Failed to save uploaded image.";
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            Session::set('validation_errors', $errors);
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/courses&action=create");
            exit;
        }

        try {
            $this->courseModel->create([
                'title' => $titleVal,
                'description' => $description,
                'category_id' => $categoryId,
                'instructor_id' => $instructorId,
                'thumbnail' => $thumbnailPath,
                'status' => $status,
                'pass_percentage' => floatval($passPercentage)
            ]);
            header("Location: index.php?route=admin/courses&action=list&success=" . urlencode("Course successfully created."));
            exit;
        } catch (Exception $e) {
            Session::set('validation_errors', ['db' => $e->getMessage()]);
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/courses&action=create");
            exit;
        }
    }

    /**
     * Action: Show Edit Form
     */
    private function handleEditGet() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $this->courseModel->find($id);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        // Server-side check for instructor access scope
        $this->requireCourseOwnershipOrAdmin($course);

        $title = "Edit Course";
        $submitUrl = "index.php?route=admin/courses&action=edit&id=" . $course['id'];
        $isEdit = true;

        $errors = Session::get('validation_errors') ?? [];
        $formData = Session::get('form_data') ?? $course;

        Session::delete('validation_errors');
        Session::delete('form_data');

        $instructors = $this->getInstructorsList();
        $flatCategories = $this->categoryModel->getTreeFlat();

        include __DIR__ . '/../views/courses/form.php';
    }

    /**
     * Action: Handle Course Update (POST)
     */
    private function handleEditPost() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $this->courseModel->find($id);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        // Server-side check for instructor access scope
        $this->requireCourseOwnershipOrAdmin($course);

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/courses&action=edit&id=" . $id . "&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $user = Auth::user();

        // Prepare inputs
        $titleVal = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        
        // Instructors cannot reassign the course to someone else
        $instructorId = ($user['role'] === 'instructor') ? $course['instructor_id'] : (!empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null);
        
        $status = $_POST['status'] ?? 'draft';
        $passPercentage = isset($_POST['pass_percentage']) ? trim($_POST['pass_percentage']) : '';

        $errors = [];

        // Validate title
        if (empty($titleVal)) {
            $errors['title'] = "Course title is required.";
        } elseif (strlen($titleVal) > 200) {
            $errors['title'] = "Course title must not exceed 200 characters.";
        }

        // Validate category_id
        if (empty($categoryId)) {
            $errors['category_id'] = "Category is required.";
        } else {
            $categoryObj = $this->categoryModel->find($categoryId);
            if (!$categoryObj) {
                $errors['category_id'] = "Selected category does not exist.";
            }
        }

        // Validate instructor_id
        if (empty($instructorId)) {
            $errors['instructor_id'] = "Instructor is required.";
        } else {
            $instructorObj = $this->getUserById($instructorId);
            if (!$instructorObj || !in_array($instructorObj['role'], ['instructor', 'admin'])) {
                $errors['instructor_id'] = "Selected user must be an instructor or an admin.";
            }
        }

        // Validate pass_percentage
        if ($passPercentage === '') {
            $errors['pass_percentage'] = "Pass percentage is required.";
        } elseif (!is_numeric($passPercentage)) {
            $errors['pass_percentage'] = "Pass percentage must be a numeric value.";
        } else {
            $passFloat = floatval($passPercentage);
            if ($passFloat < 0 || $passFloat > 100) {
                $errors['pass_percentage'] = "Pass percentage must be between 0 and 100.";
            }
        }

        // Validate status
        if (!in_array($status, ['draft', 'published', 'archived'])) {
            $errors['status'] = "Invalid status selected.";
        }

        // Validate and upload thumbnail
        $thumbnailPath = $course['thumbnail'] ?? null;
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['thumbnail']['tmp_name'];
            $fileSize = $_FILES['thumbnail']['size'];
            $fileName = $_FILES['thumbnail']['name'];

            if ($fileSize > 2 * 1024 * 1024) {
                $errors['thumbnail'] = "The thumbnail image must not exceed 2MB.";
            } else {
                $imageInfo = @getimagesize($fileTmpPath);
                if ($imageInfo === false) {
                    $errors['thumbnail'] = "Uploaded file is not a valid image.";
                } else {
                    $mimeType = $imageInfo['mime'];
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($mimeType, $allowedMimes)) {
                        $errors['thumbnail'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
                    } else {
                        $uploadDir = __DIR__ . '/../../uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $cleanName = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($fileName));
                        $uniqueName = time() . '_' . $cleanName;
                        if (move_uploaded_file($fileTmpPath, $uploadDir . $uniqueName)) {
                            // Optionally delete old thumbnail if exists
                            if (!empty($course['thumbnail'])) {
                                $oldFile = __DIR__ . '/../../' . $course['thumbnail'];
                                if (file_exists($oldFile) && is_file($oldFile)) {
                                    @unlink($oldFile);
                                }
                            }
                            $thumbnailPath = 'uploads/' . $uniqueName;
                        } else {
                            $errors['thumbnail'] = "Failed to save uploaded image.";
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            Session::set('validation_errors', $errors);
            $oldData = $_POST;
            $oldData['id'] = $id;
            $oldData['thumbnail'] = $course['thumbnail'] ?? null;
            Session::set('form_data', $oldData);
            header("Location: index.php?route=admin/courses&action=edit&id=" . $id);
            exit;
        }

        try {
            $this->courseModel->update($id, [
                'title' => $titleVal,
                'description' => $description,
                'category_id' => $categoryId,
                'instructor_id' => $instructorId,
                'thumbnail' => $thumbnailPath,
                'status' => $status,
                'pass_percentage' => floatval($passPercentage)
            ]);
            header("Location: index.php?route=admin/courses&action=list&success=" . urlencode("Course successfully updated."));
            exit;
        } catch (Exception $e) {
            Session::set('validation_errors', ['db' => $e->getMessage()]);
            $oldData = $_POST;
            $oldData['id'] = $id;
            $oldData['thumbnail'] = $course['thumbnail'] ?? null;
            Session::set('form_data', $oldData);
            header("Location: index.php?route=admin/courses&action=edit&id=" . $id);
            exit;
        }
    }

    /**
     * Action: Handle status change (draft/published/archived)
     */
    private function handleStatusChange() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $status = trim($_GET['status'] ?? '');

        $course = $this->courseModel->find($id);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        // Server-side check for instructor access scope
        $this->requireCourseOwnershipOrAdmin($course);

        if (!in_array($status, ['draft', 'published', 'archived'])) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Invalid status value."));
            exit;
        }

        $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $this->courseModel->setStatus($id, $status);
        header("Location: index.php?route=admin/courses&action=list&success=" . urlencode("Course status successfully changed to " . ucfirst($status) . "."));
        exit;
    }

    /**
     * Action: Handle Delete Action
     */
    private function handleDelete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $this->courseModel->find($id);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        // Server-side check for instructor access scope
        $this->requireCourseOwnershipOrAdmin($course);

        $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        try {
            $this->courseModel->delete($id);
            // Optionally delete thumbnail file if exists
            if (!empty($course['thumbnail'])) {
                $file = __DIR__ . '/../../' . $course['thumbnail'];
                if (file_exists($file) && is_file($file)) {
                    @unlink($file);
                }
            }
            header("Location: index.php?route=admin/courses&action=list&success=" . urlencode("Course successfully deleted."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode($e->getMessage()));
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
            echo "<p>You do not have permission to view, edit, or manage this course because it belongs to another instructor.</p>";
            echo "<p><a href='index.php?route=admin/courses'>Return to My Courses</a></p>";
            echo "</div>";
            exit;
        }
    }

    /**
     * Helper: Fetch list of instructors/admins for selection
     */
    private function getInstructorsList() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT id, name, role FROM users WHERE role IN ('instructor', 'admin') AND status = 'active' ORDER BY name ASC");
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Helper: Retrieve a single user by ID
     */
    private function getUserById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
