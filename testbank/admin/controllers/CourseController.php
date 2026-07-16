<?php
/**
 * Course Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Link.php';
require_once __DIR__ . '/../models/LearningPath.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class CourseController {
    private $courseModel;
    private $docModel;
    private $linkModel;
    private $lpModel;
    private $examModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->courseModel = new Course();
        $this->docModel = new Document();
        $this->linkModel = new Link();
        $this->lpModel = new LearningPath();
        $this->examModel = new Exam();
    }

    public function handleRequest($action = 'index') {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

        switch ($action) {
            case 'index':
                $filters = [];
                if (!Auth::hasRole(['admin'])) {
                    $filters['instructor_id'] = Session::get('user_id');
                }
                $courses = $this->courseModel->getAll($filters);
                include __DIR__ . '/../views/courses/index.php';
                break;

            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $data = [
                        'title' => trim($_POST['title']),
                        'description' => trim($_POST['description'] ?? ''),
                        'instructor_id' => Auth::hasRole(['admin']) ? intval($_POST['instructor_id']) : Session::get('user_id'),
                        'status' => $_POST['status'] ?? 'draft'
                    ];

                    if (empty($data['title'])) {
                        $error = "Course title is required.";
                        include __DIR__ . '/../views/courses/create.php';
                        exit;
                    }

                    $courseId = $this->courseModel->create($data);
                    header("Location: index.php?route=admin/courses&action=view&id=$courseId&success=Course created successfully");
                    exit;
                }

                // Get all instructors for admin selector
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT id, name FROM users WHERE role IN ('admin', 'instructor') AND status = 'active' ORDER BY name ASC");
                $instructors = $stmt->fetchAll();

                include __DIR__ . '/../views/courses/create.php';
                break;

            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $course = $this->courseModel->getById($id);
                if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                    header("Location: index.php?route=admin/courses&error=Course not found or unauthorized");
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $data = [
                        'title' => trim($_POST['title']),
                        'description' => trim($_POST['description'] ?? ''),
                        'instructor_id' => Auth::hasRole(['admin']) ? intval($_POST['instructor_id']) : Session::get('user_id'),
                        'status' => $_POST['status'] ?? 'draft'
                    ];

                    if (empty($data['title'])) {
                        $error = "Course title is required.";
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->query("SELECT id, name FROM users WHERE role IN ('admin', 'instructor') AND status = 'active' ORDER BY name ASC");
                        $instructors = $stmt->fetchAll();
                        include __DIR__ . '/../views/courses/edit.php';
                        exit;
                    }

                    $this->courseModel->update($id, $data);
                    header("Location: index.php?route=admin/courses&action=view&id=$id&success=Course details updated");
                    exit;
                }

                // Get instructors for selector
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT id, name FROM users WHERE role IN ('admin', 'instructor') AND status = 'active' ORDER BY name ASC");
                $instructors = $stmt->fetchAll();

                include __DIR__ . '/../views/courses/edit.php';
                break;

            case 'delete':
                $id = intval($_GET['id'] ?? 0);
                $course = $this->courseModel->getById($id);
                if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                    header("Location: index.php?route=admin/courses&error=Course not found or unauthorized");
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $this->courseModel->delete($id);
                    header("Location: index.php?route=admin/courses&success=Course deleted successfully");
                    exit;
                }
                break;

            case 'view':
                $id = intval($_GET['id'] ?? 0);
                $course = $this->courseModel->getById($id);
                if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                    header("Location: index.php?route=admin/courses&error=Course not found or unauthorized");
                    exit;
                }

                $documents = $this->docModel->getByCourse($id);
                $links = $this->linkModel->getByCourse($id);
                $lpItems = $this->lpModel->getItemsByCourse($id);
                
                // Get course enrolled and non-enrolled students
                $enrolledStudents = $this->courseModel->getEnrolledStudents($id);
                $nonEnrolledStudents = $this->courseModel->getNonEnrolledStudents($id);

                // Get course specific exams
                $stmt = Database::getInstance()->getConnection()->prepare("SELECT * FROM exams WHERE course_id = ? OR course_id IS NULL");
                $stmt->execute([$id]);
                $courseExams = $stmt->fetchAll();

                // Get general published exams that can be added to LP or course
                $allExams = $this->examModel->getAll();

                include __DIR__ . '/../views/courses/view.php';
                break;

            // Course content actions
            case 'add_document':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $courseId = intval($_POST['course_id']);
                    $title = trim($_POST['title']);
                    
                    $course = $this->courseModel->getById($courseId);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    // Handle PDF upload
                    $fileName = '';
                    $filePath = '';
                    
                    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../../uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileTmpPath = $_FILES['doc_file']['tmp_name'];
                        $fileName = basename($_FILES['doc_file']['name']);
                        $fileNameClean = preg_replace("/[^A-Za-z0-9._-]/", "_", $fileName);
                        $filePath = 'uploads/' . time() . '_' . $fileNameClean;
                        
                        move_uploaded_file($fileTmpPath, __DIR__ . '/../../' . $filePath);
                    } else {
                        // Fallback/Placeholder if no upload or simulated upload (e.g. text/URL link instead)
                        $fileName = 'Manual_' . trim($_POST['file_name_text'] ?? 'Document.pdf');
                        $filePath = 'uploads/' . time() . '_' . preg_replace("/[^A-Za-z0-9._-]/", "_", $fileName);
                        file_put_contents(__DIR__ . '/../../' . $filePath, "Placeholder Document content");
                    }

                    $this->docModel->create([
                        'course_id' => $courseId,
                        'title' => $title ?: $fileName,
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'status' => 'published'
                    ]);

                    header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=documents&success=Document uploaded successfully");
                    exit;
                }
                break;

            case 'delete_document':
                $id = intval($_GET['id'] ?? 0);
                $doc = $this->docModel->getById($id);
                if ($doc) {
                    $course = $this->courseModel->getById($doc['course_id']);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }
                    
                    // Delete physical file if exists
                    $fullPath = __DIR__ . '/../../' . $doc['file_path'];
                    if (file_exists($fullPath) && is_file($fullPath)) {
                        unlink($fullPath);
                    }

                    $this->docModel->delete($id);
                    header("Location: index.php?route=admin/courses&action=view&id={$doc['course_id']}&tab=documents&success=Document deleted");
                    exit;
                }
                break;

            case 'add_link':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $courseId = intval($_POST['course_id']);
                    $title = trim($_POST['title']);
                    $url = trim($_POST['url']);

                    $course = $this->courseModel->getById($courseId);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    $this->linkModel->create([
                        'course_id' => $courseId,
                        'title' => $title,
                        'url' => $url,
                        'status' => 'published'
                    ]);

                    header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=links&success=Link added successfully");
                    exit;
                }
                break;

            case 'delete_link':
                $id = intval($_GET['id'] ?? 0);
                $link = $this->linkModel->getById($id);
                if ($link) {
                    $course = $this->courseModel->getById($link['course_id']);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    $this->linkModel->delete($id);
                    header("Location: index.php?route=admin/courses&action=view&id={$link['course_id']}&tab=links&success=Link deleted");
                    exit;
                }
                break;

            // Learning Path item actions
            case 'add_lp_item':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $courseId = intval($_POST['course_id']);
                    $type = $_POST['type'];
                    $itemId = intval($_POST['item_id']);
                    $prereqId = !empty($_POST['prerequisite_id']) ? intval($_POST['prerequisite_id']) : null;
                    
                    $course = $this->courseModel->getById($courseId);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    // Get max order index
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT MAX(order_index) as max_order FROM learning_path_items WHERE course_id = ?");
                    $stmt->execute([$courseId]);
                    $maxOrder = intval($stmt->fetch()['max_order'] ?? 0);

                    $this->lpModel->addItem([
                        'course_id' => $courseId,
                        'type' => $type,
                        'item_id' => $itemId,
                        'prerequisite_id' => $prereqId,
                        'order_index' => $maxOrder + 1
                    ]);

                    header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=learning-path&success=Content added to Learning Path");
                    exit;
                }
                break;

            case 'update_lp_orders':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        echo json_encode(['status' => 'error', 'message' => 'CSRF failed']);
                        exit;
                    }
                    
                    $orders = $_POST['orders'] ?? []; // Array of lp_item_id => order_index
                    foreach ($orders as $itemId => $order) {
                        $lpItem = $this->lpModel->getItemById(intval($itemId));
                        if ($lpItem) {
                            $this->lpModel->updateItem($lpItem['id'], [
                                'prerequisite_id' => $lpItem['prerequisite_id'],
                                'order_index' => intval($order)
                            ]);
                        }
                    }
                    echo json_encode(['status' => 'success']);
                    exit;
                }
                break;

            case 'update_lp_prereq':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $courseId = intval($_POST['course_id']);
                    $lpItemId = intval($_POST['lp_item_id']);
                    $prereqId = !empty($_POST['prerequisite_id']) ? intval($_POST['prerequisite_id']) : null;

                    $course = $this->courseModel->getById($courseId);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    $lpItem = $this->lpModel->getItemById($lpItemId);
                    if ($lpItem) {
                        $this->lpModel->updateItem($lpItemId, [
                            'prerequisite_id' => $prereqId,
                            'order_index' => $lpItem['order_index']
                        ]);
                    }

                    header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=learning-path&success=Prerequisite updated");
                    exit;
                }
                break;

            case 'delete_lp_item':
                $id = intval($_GET['id'] ?? 0);
                $lpItem = $this->lpModel->getItemById($id);
                if ($lpItem) {
                    $course = $this->courseModel->getById($lpItem['course_id']);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    $this->lpModel->deleteItem($id);
                    header("Location: index.php?route=admin/courses&action=view&id={$lpItem['course_id']}&tab=learning-path&success=Item removed from Learning Path");
                    exit;
                }
                break;

            // Student enrollment actions
            case 'enroll_student':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $courseId = intval($_POST['course_id']);
                    $studentId = intval($_POST['student_id']);

                    $course = $this->courseModel->getById($courseId);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    $this->courseModel->enrollStudent($courseId, $studentId);
                    header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=enrollments&success=Student enrolled successfully");
                    exit;
                }
                break;

            case 'unenroll_student':
                $courseId = intval($_GET['course_id'] ?? 0);
                $studentId = intval($_GET['student_id'] ?? 0);

                $course = $this->courseModel->getById($courseId);
                if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                    header("Location: index.php?route=admin/courses&error=Unauthorized");
                    exit;
                }

                $this->courseModel->unenrollStudent($courseId, $studentId);
                header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=enrollments&success=Student unenrolled");
                exit;
                break;

            // Link exam to course
            case 'add_exam_to_course':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $courseId = intval($_POST['course_id']);
                    $examId = intval($_POST['exam_id']);

                    $course = $this->courseModel->getById($courseId);
                    if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                        header("Location: index.php?route=admin/courses&error=Unauthorized");
                        exit;
                    }

                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("UPDATE exams SET course_id = ? WHERE id = ?");
                    $stmt->execute([$courseId, $examId]);

                    header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=quizzes&success=Quiz linked to course");
                    exit;
                }
                break;

            case 'unlink_exam':
                $courseId = intval($_GET['course_id'] ?? 0);
                $examId = intval($_GET['exam_id'] ?? 0);

                $course = $this->courseModel->getById($courseId);
                if (!$course || (!Auth::hasRole(['admin']) && $course['instructor_id'] != Session::get('user_id'))) {
                    header("Location: index.php?route=admin/courses&error=Unauthorized");
                    exit;
                }

                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE exams SET course_id = NULL WHERE id = ?");
                $stmt->execute([$examId]);

                header("Location: index.php?route=admin/courses&action=view&id=$courseId&tab=quizzes&success=Quiz unlinked from course");
                exit;
                break;
        }
    }

    private function renderError($msg) {
        $pageTitle = 'Error';
        include __DIR__ . '/../views/layout_header.php';
        echo "<div class='container py-5'><div class='alert alert-danger shadow border-0 p-4 rounded-3 d-flex align-items-center gap-3'><i data-lucide='alert-octagon' size='36'></i><div><h4 class='fw-bold mb-1'>Error Occurred</h4><p class='mb-0'>$msg</p></div></div></div>";
        include __DIR__ . '/../views/layout_footer.php';
        exit;
    }
}
