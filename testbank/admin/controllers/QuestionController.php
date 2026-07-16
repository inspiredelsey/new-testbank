<?php
/**
 * Question Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../../includes/Auth.php';

class QuestionController {
    private $model;
    private $categoryModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->model = new Question();
        $this->categoryModel = new Category();
    }

    public function handleRequest($action = 'index') {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        switch ($action) {
            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }

                    try {
                        $data = $_POST;
                        $data['created_by'] = Auth::getUser()['id'];

                        // Handle image upload
                        $imagePath = $this->handleImageUpload();
                        if ($imagePath) {
                            $data['question_text'] .= "\n\n[IMAGE: " . $imagePath . "]";
                        }

                        // Parse options depending on question type
                        $options = $this->parseOptionsFromPost();
                        $tags = !empty($_POST['tags']) ? explode(',', $_POST['tags']) : [];

                        $this->model->create($data, $options, $tags);
                        header("Location: index.php?route=admin/questions&success=Question created successfully");
                        exit;
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                        $flatCategories = $this->categoryModel->getTreeFlat();
                        include __DIR__ . '/../views/questions/create.php';
                        exit;
                    }
                }
                $flatCategories = $this->categoryModel->getTreeFlat();
                include __DIR__ . '/../views/questions/create.php';
                exit;

            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $question = $this->model->getById($id);
                if (!$question) {
                    header("Location: index.php?route=admin/questions&error=Question not found");
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }

                    try {
                        $data = $_POST;
                        
                        // Handle image upload
                        $imagePath = $this->handleImageUpload();
                        if ($imagePath) {
                            $data['question_text'] .= "\n\n[IMAGE: " . $imagePath . "]";
                        }

                        $options = $this->parseOptionsFromPost();
                        $tags = !empty($_POST['tags']) ? explode(',', $_POST['tags']) : [];

                        $this->model->update($id, $data, $options, $tags);
                        header("Location: index.php?route=admin/questions&success=Question updated successfully");
                        exit;
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                        $flatCategories = $this->categoryModel->getTreeFlat();
                        $options = $this->model->getOptions($id);
                        $tagsList = $this->model->getTags($id);
                        $tagsString = implode(',', array_map(function($t) { return $t['name']; }, $tagsList));
                        include __DIR__ . '/../views/questions/edit.php';
                        exit;
                    }
                }

                $flatCategories = $this->categoryModel->getTreeFlat();
                $options = $this->model->getOptions($id);
                $tagsList = $this->model->getTags($id);
                $tagsString = implode(',', array_map(function($t) { return $t['name']; }, $tagsList));
                include __DIR__ . '/../views/questions/edit.php';
                exit;

            case 'delete':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['confirm'])) {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
                    $this->model->delete($id);
                    header("Location: index.php?route=admin/questions&success=Question deleted successfully");
                    exit;
                }
                break;

            case 'import':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }

                    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
                        $fileName = $_FILES['csv_file']['name'];
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                        if ($fileExtension !== 'csv') {
                            header("Location: index.php?route=admin/questions&error=Invalid file extension. Please upload a CSV file.");
                            exit;
                        }

                        try {
                            $userId = Auth::getUser()['id'];
                            $imported = $this->model->importCSV($fileTmpPath, $userId);
                            header("Location: index.php?route=admin/questions&success=" . $imported . " questions imported successfully.");
                            exit;
                        } catch (Exception $e) {
                            header("Location: index.php?route=admin/questions&error=" . urlencode("Failed to import CSV: " . $e->getMessage()));
                            exit;
                        }
                    } else {
                        header("Location: index.php?route=admin/questions&error=Please select a valid CSV file to upload.");
                        exit;
                    }
                }
                break;

            case 'export':
                $csvData = $this->model->exportCSV();
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=question_bank_export_' . date('Y-m-d') . '.csv');
                echo "\xEF\xBB\xBF"; // UTF-8 BOM
                echo $csvData;
                exit;

            case 'index':
            default:
                $filters = [
                    'category_id' => $_GET['category_id'] ?? null,
                    'type' => $_GET['type'] ?? null,
                    'difficulty' => $_GET['difficulty'] ?? null,
                    'status' => $_GET['status'] ?? null,
                    'search' => $_GET['search'] ?? null,
                    'tag' => $_GET['tag'] ?? null
                ];

                $questions = $this->model->getAll($filters);
                $flatCategories = $this->categoryModel->getTreeFlat();
                include __DIR__ . '/../views/questions/index.php';
                exit;
        }
    }

    private function handleImageUpload() {
        if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['question_image']['tmp_name'];
            $fileName = $_FILES['question_image']['name'];
            $fileSize = $_FILES['question_image']['size'];
            $fileType = $_FILES['question_image']['type'];
            
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

            // Validate file size (limit to 2MB)
            if ($fileSize > 2 * 1024 * 1024) {
                throw new Exception("Uploaded file is too large. Maximum size is 2MB.");
            }

            // Validate extension and mime type
            if (!in_array($fileExtension, $allowedExtensions) || !in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }

            // Sanitized file name
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                return 'uploads/' . $newFileName;
            } else {
                throw new Exception("Error moving uploaded file to destination directory.");
            }
        }
        return null;
    }

    private function parseOptionsFromPost() {
        $type = $_POST['type'] ?? 'mcq_single';
        $options = [];

        if ($type === 'true_false') {
            // True/False correct answer is stored directly
            $options = [$_POST['tf_correct'] ?? 'true'];
        } else if ($type === 'fill_blank') {
            if (!empty($_POST['blank_answers'])) {
                foreach ($_POST['blank_answers'] as $text) {
                    if (trim($text) !== '') {
                        $options[] = [
                            'option_text' => trim($text),
                            'is_correct' => 1
                        ];
                    }
                }
            }
        } else if ($type === 'matching') {
            if (!empty($_POST['match_left'])) {
                foreach ($_POST['match_left'] as $idx => $leftText) {
                    $rightText = $_POST['match_right'][$idx] ?? '';
                    if (trim($leftText) !== '' || trim($rightText) !== '') {
                        $options[] = [
                            'option_text' => trim($leftText),
                            'pair_key' => trim($rightText),
                            'is_correct' => 1
                        ];
                    }
                }
            }
        } else if ($type === 'mcq_single' || $type === 'mcq_multi') {
            if (!empty($_POST['options'])) {
                foreach ($_POST['options'] as $idx => $text) {
                    if (trim($text) !== '') {
                        $isCorrect = 0;
                        if ($type === 'mcq_single') {
                            $isCorrect = (intval($_POST['mcq_correct_single'] ?? -1) === $idx) ? 1 : 0;
                        } else {
                            $isCorrect = isset($_POST['mcq_correct_multi'][$idx]) ? 1 : 0;
                        }
                        $options[] = [
                            'option_text' => trim($text),
                            'is_correct' => $isCorrect
                        ];
                    }
                }
            }
        }

        return $options;
    }

    private function renderError($msg) {
        http_response_code(400);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h2>Error</h2>";
        echo "<p>" . htmlspecialchars($msg) . "</p>";
        echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
        echo "</div>";
        exit;
    }
}
