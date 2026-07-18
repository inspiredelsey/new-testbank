<?php
/**
 * Document Controller - Test Bank LMS
 * Manages uploads, editing, deletion, and order management of documents.
 */

require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class DocumentController {
    private $documentModel;
    private $courseModel;
    
    // Configurable Max File Size: 20MB
    const MAX_FILE_SIZE = 20971520; 

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->documentModel = new Document();
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
     * Action: List documents of a specific course.
     */
    private function handleList() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $documents = $this->documentModel->forCourse($courseId);
        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/documents/list.php';
    }

    /**
     * Action: Create/Upload a new document.
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
        $description = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::validateCSRF($token)) {
                header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($title)) {
                $errors['title'] = "Title is required.";
            } elseif (strlen($title) > 200) {
                $errors['title'] = "Title cannot exceed 200 characters.";
            }

            // File upload validation
            if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] === UPLOAD_ERR_NO_FILE) {
                $errors['document_file'] = "File upload is required.";
            } else {
                $file = $_FILES['document_file'];
                
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors['document_file'] = "Upload error occurred. Error code: " . $file['error'];
                } elseif ($file['size'] > self::MAX_FILE_SIZE) {
                    $errors['document_file'] = "File size exceeds the 20MB limit.";
                } else {
                    // Detect MIME type robustly using finfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowedMimes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'video/mp4',
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp'
                    ];

                    if (!in_array($mimeType, $allowedMimes)) {
                        $errors['document_file'] = "Invalid file type. Allowed: PDF, Word, PowerPoint, MP4 videos, and common images (JPG/PNG/GIF/WEBP).";
                    }
                }
            }

            if (empty($errors)) {
                // Ensure target directory exists
                $uploadDir = __DIR__ . '/../../uploads/documents/' . $courseId . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $file = $_FILES['document_file'];
                $cleanName = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($file['name']));
                $uniqueName = time() . '_' . $cleanName;
                $destination = $uploadDir . $uniqueName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Re-read destination file mime type for double-layer protection
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $destination);
                    finfo_close($finfo);

                    $fileCategory = 'file';
                    if ($mimeType === 'application/pdf') {
                        $fileCategory = 'pdf';
                    } elseif (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
                        $fileCategory = 'doc';
                    } elseif (in_array($mimeType, ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])) {
                        $fileCategory = 'presentation';
                    } elseif (strpos($mimeType, 'video/') === 0) {
                        $fileCategory = 'video';
                    } elseif (strpos($mimeType, 'image/') === 0) {
                        $fileCategory = 'image';
                    }

                    $dbFilePath = 'uploads/documents/' . $courseId . '/' . $uniqueName;

                    $success = $this->documentModel->create([
                        'course_id' => $courseId,
                        'title' => $title,
                        'file_path' => $dbFilePath,
                        'file_type' => $fileCategory,
                        'description' => $description
                    ]);

                    if ($success) {
                        header("Location: index.php?route=admin/documents&action=list&course_id=" . $courseId . "&success=" . urlencode("Document uploaded successfully."));
                        exit;
                    } else {
                        // Cleanup physical file on DB failure
                        @unlink($destination);
                        $errors['form'] = "Failed to save document record to the database.";
                    }
                } else {
                    $errors['document_file'] = "Failed to save the uploaded file to the destination folder.";
                }
            }
        }

        $csrfToken = Session::getCSRFToken();
        $actionUrl = "index.php?route=admin/documents&action=create&course_id=" . $courseId;
        $formTitle = "Add Document";

        include __DIR__ . '/../views/documents/form.php';
    }

    /**
     * Action: Edit document attributes, with optional file replacement.
     */
    private function handleEdit() {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $doc = $this->documentModel->find($id);

        if (!$doc) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Document not found."));
            exit;
        }

        $courseId = $doc['course_id'];
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $errors = [];
        $title = $doc['title'];
        $description = $doc['description'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::validateCSRF($token)) {
                header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($title)) {
                $errors['title'] = "Title is required.";
            } elseif (strlen($title) > 200) {
                $errors['title'] = "Title cannot exceed 200 characters.";
            }

            $newFileUploaded = isset($_FILES['document_file']) && $_FILES['document_file']['error'] !== UPLOAD_ERR_NO_FILE;
            $dbFilePath = $doc['file_path'];
            $fileCategory = $doc['file_type'];

            if ($newFileUploaded) {
                $file = $_FILES['document_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors['document_file'] = "Upload error occurred. Error code: " . $file['error'];
                } elseif ($file['size'] > self::MAX_FILE_SIZE) {
                    $errors['document_file'] = "File size exceeds the 20MB limit.";
                } else {
                    // Validate MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowedMimes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'video/mp4',
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp'
                    ];

                    if (!in_array($mimeType, $allowedMimes)) {
                        $errors['document_file'] = "Invalid file type. Allowed: PDF, Word, PowerPoint, MP4 videos, and common images (JPG/PNG/GIF/WEBP).";
                    }
                }
            }

            if (empty($errors)) {
                $uploadSuccess = true;
                if ($newFileUploaded) {
                    $uploadDir = __DIR__ . '/../../uploads/documents/' . $courseId . '/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $file = $_FILES['document_file'];
                    $cleanName = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($file['name']));
                    $uniqueName = time() . '_' . $cleanName;
                    $destination = $uploadDir . $uniqueName;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        // Category detection
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $destination);
                        finfo_close($finfo);

                        $fileCategory = 'file';
                        if ($mimeType === 'application/pdf') {
                            $fileCategory = 'pdf';
                        } elseif (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
                            $fileCategory = 'doc';
                        } elseif (in_array($mimeType, ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])) {
                            $fileCategory = 'presentation';
                        } elseif (strpos($mimeType, 'video/') === 0) {
                            $fileCategory = 'video';
                        } elseif (strpos($mimeType, 'image/') === 0) {
                            $fileCategory = 'image';
                        }

                        // Safely unlink the previous physical file to prevent dangling files
                        $oldPath = __DIR__ . '/../../' . ltrim($doc['file_path'], '/');
                        if (file_exists($oldPath) && is_file($oldPath)) {
                            @unlink($oldPath);
                        }

                        $dbFilePath = 'uploads/documents/' . $courseId . '/' . $uniqueName;
                    } else {
                        $uploadSuccess = false;
                        $errors['document_file'] = "Failed to save the new uploaded file.";
                    }
                }

                if ($uploadSuccess) {
                    $success = $this->documentModel->update($id, [
                        'title' => $title,
                        'file_path' => $dbFilePath,
                        'file_type' => $fileCategory,
                        'description' => $description
                    ]);

                    if ($success) {
                        header("Location: index.php?route=admin/documents&action=list&course_id=" . $courseId . "&success=" . urlencode("Document details updated successfully."));
                        exit;
                    } else {
                        $errors['form'] = "Failed to update database details.";
                    }
                }
            }
        }

        $csrfToken = Session::getCSRFToken();
        $actionUrl = "index.php?route=admin/documents&action=edit&id=" . $id;
        $formTitle = "Edit Document";

        include __DIR__ . '/../views/documents/form.php';
    }

    /**
     * Action: Remove document from DB and delete physical file.
     */
    private function handleDelete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $doc = $this->documentModel->find($id);
        if (!$doc) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Document not found."));
            exit;
        }

        $courseId = $doc['course_id'];
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        $success = $this->documentModel->delete($id);

        if ($success) {
            header("Location: index.php?route=admin/documents&action=list&course_id=" . $courseId . "&success=" . urlencode("Document successfully deleted."));
            exit;
        } else {
            header("Location: index.php?route=admin/documents&action=list&course_id=" . $courseId . "&error=" . urlencode("Failed to delete document from database."));
            exit;
        }
    }

    /**
     * Action: Change the order index of documents using simple Swapping logic.
     */
    private function handleReorder() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $direction = trim($_GET['direction'] ?? '');
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $token = $_GET['csrf_token'] ?? '';

        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/documents&action=list&course_id=" . $courseId . "&error=" . urlencode("Security token validation failed."));
            exit;
        }

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/courses&action=list&error=" . urlencode("Course not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        // Get current ordered documents
        $docs = $this->documentModel->forCourse($courseId);
        $docIds = array_column($docs, 'id');

        $targetIndex = array_search($id, $docIds);
        if ($targetIndex !== false) {
            if ($direction === 'up' && $targetIndex > 0) {
                // Swap target with previous element
                $temp = $docIds[$targetIndex];
                $docIds[$targetIndex] = $docIds[$targetIndex - 1];
                $docIds[$targetIndex - 1] = $temp;
            } elseif ($direction === 'down' && $targetIndex < count($docIds) - 1) {
                // Swap target with next element
                $temp = $docIds[$targetIndex];
                $docIds[$targetIndex] = $docIds[$targetIndex + 1];
                $docIds[$targetIndex + 1] = $temp;
            }

            $this->documentModel->reorder($courseId, $docIds);
        }

        header("Location: index.php?route=admin/documents&action=list&course_id=" . $courseId . "&success=" . urlencode("Document sequence updated successfully."));
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
            echo "<p style='color:#6c757d;'>You do not have permission to manage documents for this course because it belongs to another instructor.</p>";
            echo "<p style='margin-top:20px;'><a href='index.php?route=admin/courses' style='color:#0d6efd; text-decoration:none; font-weight:600;'>&larr; Return to My Courses</a></p>";
            echo "</div>";
            exit;
        }
    }
}
