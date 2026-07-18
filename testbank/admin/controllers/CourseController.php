<?php
/**
 * Course Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Link.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/LearningPath.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class CourseController {
    private $courseModel;
    private $categoryModel;
    private $documentModel;
    private $linkModel;
    private $examModel;
    private $lpModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->courseModel = new Course();
        $this->categoryModel = new Category();
        $this->documentModel = new Document();
        $this->linkModel = new Link();
        $this->examModel = new Exam();
        $this->lpModel = new LearningPath();
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

            case 'view':
                $this->handleView();
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

            case 'add_document':
                $this->handleAddDocument();
                break;

            case 'delete_document':
                $this->handleDeleteDocument();
                break;

            case 'add_link':
                $this->handleAddLink();
                break;

            case 'delete_link':
                $this->handleDeleteLink();
                break;

            case 'add_exam_to_course':
                $this->handleAddExamToCourse();
                break;

            case 'unlink_exam':
                $this->handleUnlinkExam();
                break;

            case 'add_lp_item':
                $this->handleAddLPItem();
                break;

            case 'delete_lp_item':
                $this->handleDeleteLPItem();
                break;

            case 'update_lp_prereq':
                $this->handleUpdateLPPrereq();
                break;

            case 'update_lp_orders':
                $this->handleUpdateLPOrders();
                break;

            case 'enroll_student':
                $this->handleEnrollStudent();
                break;

            case 'unenroll_student':
                $this->handleUnenrollStudent();
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
        
        $redirect = trim($_GET['redirect'] ?? '');
        if ($redirect === 'view') {
            header("Location: index.php?route=admin/courses&action=view&id={$id}&success=" . urlencode("Course status successfully changed to " . ucfirst($status) . "."));
        } else {
            header("Location: index.php?route=admin/courses&action=list&success=" . urlencode("Course status successfully changed to " . ucfirst($status) . "."));
        }
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
     * Action: View Course details and tabbed submenu management
     */
    private function handleView() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $this->courseModel->find($id);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $activeTab = $_GET['tab'] ?? 'documents';

        // Load all data to fully populate all views and modals
        $documents = $this->documentModel->forCourse($id);
        $links = $this->linkModel->getByCourse($id);
        
        // Retrieve linked exams/quizzes
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT e.*, c.name as category_name FROM exams e LEFT JOIN categories c ON e.category_id = c.id WHERE e.course_id = ? ORDER BY e.title ASC");
        $stmt->execute([$id]);
        $courseExams = $stmt->fetchAll() ?: [];

        // Retrieve unlinked exams/quizzes to offer for linking
        $stmt = $db->prepare("SELECT e.*, c.name as category_name FROM exams e LEFT JOIN categories c ON e.category_id = c.id WHERE e.course_id IS NULL OR e.course_id = 0 ORDER BY e.title ASC");
        $stmt->execute();
        $allExams = $stmt->fetchAll() ?: [];

        $lpItems = $this->lpModel->getItemsByCourse($id) ?: [];
        $enrolledStudents = $this->courseModel->getEnrolledStudents($id) ?: [];
        $nonEnrolledStudents = $this->courseModel->getNonEnrolledStudents($id) ?: [];

        include __DIR__ . '/../views/courses/view.php';
    }

    private function handleAddDocument() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=documents&error=" . urlencode("Document title is required."));
            exit;
        }

        $filePath = '';
        $fileName = '';
        $fileType = 'pdf';

        if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['doc_file']['tmp_name'];
            $fileName = $_FILES['doc_file']['name'];
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
            
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $cleanName = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($fileName));
            $uniqueName = time() . '_' . $cleanName;
            if (move_uploaded_file($fileTmpPath, $uploadDir . $uniqueName)) {
                $filePath = 'uploads/' . $uniqueName;
            } else {
                header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=documents&error=" . urlencode("Failed to save uploaded file."));
                exit;
            }
        } elseif (!empty($_POST['file_name_text'])) {
            $fileName = trim($_POST['file_name_text']);
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'pdf';
            
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $cleanName = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($fileName));
            $uniqueName = time() . '_' . $cleanName;
            
            file_put_contents($uploadDir . $uniqueName, "Mock document content for " . $title);
            $filePath = 'uploads/' . $uniqueName;
        } else {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=documents&error=" . urlencode("Please upload a file or provide a mock file name."));
            exit;
        }

        $this->documentModel->create([
            'course_id' => $courseId,
            'title' => $title,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'description' => ''
        ]);

        header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=documents&success=" . urlencode("Document uploaded successfully."));
        exit;
    }

    private function handleDeleteDocument() {
        $id = (int)($_GET['id'] ?? 0);
        $doc = $this->documentModel->find($id);
        if (!$doc) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Document not found."));
            exit;
        }

        $courseId = $doc['course_id'];
        $course = $this->courseModel->find($courseId);
        if ($course) {
            $this->requireCourseOwnershipOrAdmin($course);
        }

        if ($this->documentModel->delete($id)) {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=documents&success=" . urlencode("Document deleted successfully."));
        } else {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=documents&error=" . urlencode("Failed to delete document."));
        }
        exit;
    }

    private function handleAddLink() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if (empty($title) || empty($url)) {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=links&error=" . urlencode("Title and URL are required."));
            exit;
        }

        $this->linkModel->create([
            'course_id' => $courseId,
            'title' => $title,
            'url' => $url,
            'status' => 'published'
        ]);

        header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=links&success=" . urlencode("Link added successfully."));
        exit;
    }

    private function handleDeleteLink() {
        $id = (int)($_GET['id'] ?? 0);
        $lnk = $this->linkModel->getById($id);
        if (!$lnk) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Link not found."));
            exit;
        }

        $courseId = $lnk['course_id'];
        $course = $this->courseModel->find($courseId);
        if ($course) {
            $this->requireCourseOwnershipOrAdmin($course);
        }

        if ($this->linkModel->delete($id)) {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=links&success=" . urlencode("Link deleted successfully."));
        } else {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=links&error=" . urlencode("Failed to delete link."));
        }
        exit;
    }

    private function handleAddExamToCourse() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $examId = (int)($_POST['exam_id'] ?? 0);

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE exams SET course_id = ? WHERE id = ?");
        $stmt->execute([$courseId, $examId]);

        header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=quizzes&success=" . urlencode("Quiz linked successfully."));
        exit;
    }

    private function handleUnlinkExam() {
        $courseId = (int)($_GET['course_id'] ?? 0);
        $examId = (int)($_GET['exam_id'] ?? 0);

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE exams SET course_id = NULL WHERE id = ? AND course_id = ?");
        $stmt->execute([$examId, $courseId]);

        header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=quizzes&success=" . urlencode("Quiz unlinked successfully."));
        exit;
    }

    private function handleAddLPItem() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        $type = trim($_POST['type'] ?? '');
        $itemId = (int)($_POST['item_id'] ?? 0);
        $prerequisiteId = !empty($_POST['prerequisite_id']) ? (int)$_POST['prerequisite_id'] : null;

        if (empty($type) || empty($itemId)) {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=learning-path&error=" . urlencode("Type and Item are required."));
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT MAX(order_index) FROM learning_path_items WHERE course_id = ?");
        $stmt->execute([$courseId]);
        $maxOrder = $stmt->fetchColumn();
        $nextOrder = ($maxOrder !== false) ? (int)$maxOrder + 1 : 1;

        $this->lpModel->addItem([
            'course_id' => $courseId,
            'type' => $type,
            'item_id' => $itemId,
            'prerequisite_id' => $prerequisiteId,
            'order_index' => $nextOrder
        ]);

        header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=learning-path&success=" . urlencode("Item added to learning path."));
        exit;
    }

    private function handleDeleteLPItem() {
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->lpModel->getItemById($id);
        if (!$item) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Learning path item not found."));
            exit;
        }

        $courseId = $item['course_id'];
        $course = $this->courseModel->find($courseId);
        if ($course) {
            $this->requireCourseOwnershipOrAdmin($course);
        }

        $this->lpModel->deleteItem($id);

        header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=learning-path&success=" . urlencode("Item removed from learning path."));
        exit;
    }

    private function handleUpdateLPPrereq() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $lpItemId = (int)($_POST['lp_item_id'] ?? 0);
        $prerequisiteId = !empty($_POST['prerequisite_id']) ? (int)$_POST['prerequisite_id'] : null;

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        $item = $this->lpModel->getItemById($lpItemId);
        if ($item) {
            $this->lpModel->updateItem($lpItemId, [
                'prerequisite_id' => $prerequisiteId,
                'order_index' => $item['order_index']
            ]);
        }

        header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=learning-path&success=" . urlencode("Prerequisite updated."));
        exit;
    }

    private function handleUpdateLPOrders() {
        header('Content-Type: application/json');
        
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            echo json_encode(['status' => 'error', 'message' => 'CSRF token invalid.']);
            exit;
        }

        $orders = $_POST['orders'] ?? [];
        if (empty($orders) || !is_array($orders)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid orders parameter.']);
            exit;
        }

        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE learning_path_items SET order_index = ? WHERE id = ?");
            foreach ($orders as $id => $order) {
                $stmt->execute([intval($order), intval($id)]);
            }
            $db->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function handleEnrollStudent() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $studentId = (int)($_POST['student_id'] ?? 0);

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        if ($this->courseModel->enrollStudent($courseId, $studentId)) {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=enrollments&success=" . urlencode("Student enrolled successfully."));
        } else {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=enrollments&error=" . urlencode("Failed to enroll student. It is possible they are already enrolled."));
        }
        exit;
    }

    private function handleUnenrollStudent() {
        $courseId = (int)($_GET['course_id'] ?? 0);
        $studentId = (int)($_GET['student_id'] ?? 0);

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }
        $this->requireCourseOwnershipOrAdmin($course);

        if ($this->courseModel->unenrollStudent($courseId, $studentId)) {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=enrollments&success=" . urlencode("Student unenrolled successfully."));
        } else {
            header("Location: index.php?route=admin/courses&action=view&id={$courseId}&tab=enrollments&error=" . urlencode("Failed to unenroll student."));
        }
        exit;
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
