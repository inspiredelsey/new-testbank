<?php
/**
 * Exam Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../../includes/Auth.php';

class ExamController {
    private $model;
    private $questionModel;
    private $categoryModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->model = new Exam();
        $this->questionModel = new Question();
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
                        $examId = $this->model->create($data);
                        header("Location: index.php?route=admin/exams&action=questions&id=" . $examId . "&success=Exam created successfully. Now assign questions!");
                        exit;
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                        $flatCategories = $this->categoryModel->getTreeFlat();
                        include __DIR__ . '/../views/exams/create.php';
                        exit;
                    }
                }
                $flatCategories = $this->categoryModel->getTreeFlat();
                include __DIR__ . '/../views/exams/create.php';
                exit;

            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $exam = $this->model->getById($id);
                if (!$exam) {
                    header("Location: index.php?route=admin/exams&error=Exam not found");
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    try {
                        $this->model->update($id, $_POST);
                        header("Location: index.php?route=admin/exams&success=Exam configuration updated successfully");
                        exit;
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                        $flatCategories = $this->categoryModel->getTreeFlat();
                        include __DIR__ . '/../views/exams/edit.php';
                        exit;
                    }
                }
                $flatCategories = $this->categoryModel->getTreeFlat();
                include __DIR__ . '/../views/exams/edit.php';
                exit;

            case 'delete':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['confirm'])) {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
                    $this->model->delete($id);
                    header("Location: index.php?route=admin/exams&success=Exam deleted successfully");
                    exit;
                }
                break;

            case 'questions':
                $id = intval($_GET['id'] ?? 0);
                $exam = $this->model->getById($id);
                if (!$exam) {
                    header("Location: index.php?route=admin/exams&error=Exam not found");
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $qIds = $_POST['questions'] ?? [];
                    $overrides = $_POST['points_override'] ?? [];
                    
                    $this->model->saveQuestions($id, $qIds, $overrides);
                    header("Location: index.php?route=admin/exams&success=Exam questions set updated successfully");
                    exit;
                }

                $assignedQuestions = $this->model->getQuestions($id);
                $assignedIds = array_map(function($q) { return $q['question_id']; }, $assignedQuestions);
                
                // Get all published questions to pick from
                $allQuestions = $this->questionModel->getAll(['status' => 'published']);
                include __DIR__ . '/../views/exams/questions.php';
                exit;

            case 'rules':
                $id = intval($_GET['id'] ?? 0);
                $exam = $this->model->getById($id);
                if (!$exam) {
                    header("Location: index.php?route=admin/exams&error=Exam not found");
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $rules = [];
                    if (!empty($_POST['rules_cat'])) {
                        foreach ($_POST['rules_cat'] as $idx => $catId) {
                            $rules[] = [
                                'category_id' => $catId,
                                'difficulty' => $_POST['rules_diff'][$idx] ?? 'any',
                                'question_count' => intval($_POST['rules_count'][$idx] ?? 0)
                            ];
                        }
                    }
                    $this->model->saveRules($id, $rules);
                    header("Location: index.php?route=admin/exams&success=Random-pull rules updated successfully");
                    exit;
                }

                $rules = $this->model->getRules($id);
                $flatCategories = $this->categoryModel->getTreeFlat();
                include __DIR__ . '/../views/exams/rules.php';
                exit;

            case 'preview':
                $id = intval($_GET['id'] ?? 0);
                $exam = $this->model->getById($id);
                if (!$exam) {
                    header("Location: index.php?route=admin/exams&error=Exam not found");
                    exit;
                }

                // Resolve the dynamic question set at this instant to preview
                $resolvedIds = $this->model->resolveQuestionSet($id);
                
                $resolvedQuestions = [];
                foreach ($resolvedIds as $qId) {
                    $q = $this->questionModel->getById($qId);
                    if ($q) {
                        $q['options'] = $this->questionModel->getOptions($qId);
                        $resolvedQuestions[] = $q;
                    }
                }

                include __DIR__ . '/../views/exams/preview.php';
                exit;

            case 'index':
            default:
                $exams = $this->model->getAll();
                include __DIR__ . '/../views/exams/index.php';
                exit;
        }
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
