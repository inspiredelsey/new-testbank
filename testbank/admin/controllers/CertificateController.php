<?php
/**
 * Certificate Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/CertificateTemplate.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/CertificateGenerator.php';

class CertificateController {
    private $courseModel;
    private $templateModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->courseModel = new Course();
        $this->templateModel = new CertificateTemplate();
    }

    /**
     * Dispatch routing requests based on action parameter.
     */
    public function handleRequest($action = 'list') {
        switch ($action) {
            case 'template':
                $this->handleTemplate();
                break;

            case 'list':
                $this->handleList();
                break;

            case 'regenerate':
                $this->handleRegenerate();
                break;

            case 'download':
                $this->handleDownload();
                break;

            case 'preview':
                $this->handlePreview();
                break;

            default:
                header("Location: index.php?route=admin/courses");
                break;
        }
    }

    /**
     * Preview Certificate Template for a Course
     */
    private function handlePreview() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            http_response_code(404);
            echo "Course not found.";
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        // Generate the preview PDF, which streams the response inline and exits.
        CertificateGenerator::previewPdf($courseId);
        exit;
    }

    /**
     * Show/Edit Certificate Template for a Course
     */
    private function handleTemplate() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            header("Location: index.php?route=admin/courses&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $template = $this->templateModel->find($courseId);
        $errors = [];
        $successMsg = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::validateCSRF($token)) {
                $errors['csrf'] = "Security token validation failed.";
            }

            $htmlTemplate = trim($_POST['html_template'] ?? '');
            if (empty($htmlTemplate)) {
                $errors['html_template'] = "Template HTML is required.";
            }

            // Handle background image upload
            $backgroundImagePath = $template ? $template['background_image'] : null;
            if (!empty($_FILES['background_image']['name']) && empty($errors)) {
                $fileTmpPath = $_FILES['background_image']['tmp_name'];
                $fileName = $_FILES['background_image']['name'];
                $fileSize = $_FILES['background_image']['size'];
                $fileType = $_FILES['background_image']['type'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $errors['background_image'] = "Only JPG, JPEG, and PNG images are allowed.";
                } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                    $errors['background_image'] = "Background image cannot exceed 5MB.";
                } else {
                    $uploadDir = __DIR__ . '/../../uploads/certificates/backgrounds/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $uniqueName = 'bg_' . $courseId . '_' . uniqid() . '.' . $fileExtension;
                    $destination = $uploadDir . $uniqueName;
                    if (move_uploaded_file($fileTmpPath, $destination)) {
                        $backgroundImagePath = 'uploads/certificates/backgrounds/' . $uniqueName;
                    } else {
                        $errors['background_image'] = "Failed to save uploaded image.";
                    }
                }
            }

            if (empty($errors)) {
                $data = [
                    'title' => $_POST['title'] ?? 'Certificate of Completion',
                    'html_template' => $htmlTemplate,
                    'content' => $htmlTemplate,
                    'background_image' => $backgroundImagePath
                ];

                $this->templateModel->createOrUpdate($courseId, $data);
                $successMsg = "Certificate template saved successfully.";
                // Reload template
                $template = $this->templateModel->find($courseId);
            }
        }

        $csrfToken = Session::getCSRFToken();
        include __DIR__ . '/../views/certificates/template-form.php';
    }

    /**
     * List Issued Certificates for a Course
     */
    private function handleList() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            header("Location: index.php?route=admin/courses&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        // Fetch all issued certificates with student details
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT c.*, u.name as student_name, u.email as student_email
            FROM certificates c
            JOIN users u ON (c.student_id = u.id OR c.user_id = u.id)
            WHERE c.course_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$courseId]);
        $certificates = $stmt->fetchAll() ?: [];

        $csrfToken = Session::getCSRFToken();
        include __DIR__ . '/../views/certificates/list.php';
    }

    /**
     * Manually Regenerate a Certificate for a Student
     */
    private function handleRegenerate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/courses");
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $cert = CertificateGenerator::generatePdf($courseId, $userId);

        if ($cert) {
            header("Location: index.php?route=admin/certificates&action=list&course_id={$courseId}&success=" . urlencode("Certificate successfully regenerated and updated."));
        } else {
            header("Location: index.php?route=admin/certificates&action=list&course_id={$courseId}&error=" . urlencode("Failed to regenerate certificate. Ensure the student meets completion requirements."));
        }
        exit;
    }

    /**
     * Download certificate PDF securely (checking course ownership/admin role)
     */
    private function handleDownload() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM certificates WHERE id = ?");
        $stmt->execute([$id]);
        $cert = $stmt->fetch();

        if (!$cert) {
            http_response_code(404);
            echo "Certificate not found.";
            exit;
        }

        $course = $this->courseModel->find($cert['course_id']);
        if (!$course) {
            http_response_code(404);
            echo "Course not found.";
            exit;
        }

        // Check ownership
        $this->requireCourseOwnershipOrAdmin($course);

        $fullPath = __DIR__ . '/../../' . $cert['pdf_path'];
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Certificate PDF file not found on disk.";
            exit;
        }

        // Stream file
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="certificate_' . $id . '.pdf"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    /**
     * Helper: Enforce instructor course ownership or admin role
     */
    private function requireCourseOwnershipOrAdmin($course) {
        $user = Auth::user();
        if ($user['role'] !== 'admin' && (int)$course['instructor_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2 style='color:#dc3545;'>403 - Access Forbidden</h2>";
            echo "<p>You do not have permission to access certificates for this course because it belongs to another instructor.</p>";
            echo "<p><a href='index.php?route=admin/courses'>Return to My Courses</a></p>";
            echo "</div>";
            exit;
        }
    }
}
