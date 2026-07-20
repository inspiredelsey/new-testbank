<?php
/**
 * Exam Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/ExamQuestion.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class ExamController {
    private $examModel;
    private $examQuestionModel;
    private $questionModel;
    private $categoryModel;
    private $courseModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->examModel = new Exam();
        $this->examQuestionModel = new ExamQuestion();
        $this->questionModel = new Question();
        $this->categoryModel = new Category();
        $this->courseModel = new Course();
    }

    /**
     * Entry point for routing requests
     */
    public function handleRequest($action = 'list') {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

        switch ($action) {
            case 'list':
            case 'index':
                $this->handleList();
                break;

            case 'create':
                $this->handleCreate($csrfToken);
                break;

            case 'edit':
                $this->handleEdit($csrfToken);
                break;

            case 'delete':
                $this->handleDelete($csrfToken);
                break;

            case 'status':
                $this->handleStatusChange($csrfToken);
                break;

            case 'build':
                $this->handleBuild($csrfToken);
                break;

            case 'add_fixed':
                $this->handleAddFixed($csrfToken);
                break;

            case 'remove_fixed':
                $this->handleRemoveFixed($csrfToken);
                break;

            case 'reorder_fixed':
                $this->handleReorderFixed($csrfToken);
                break;

            case 'points_override':
                $this->handlePointsOverride($csrfToken);
                break;

            case 'add_rule':
                $this->handleAddRule($csrfToken);
                break;

            case 'remove_rule':
                $this->handleRemoveRule($csrfToken);
                break;

            case 'preview':
                $this->handlePreview();
                break;

            default:
                header("Location: index.php?route=admin/exams&action=list");
                exit;
        }
    }

    /**
     * Action: List Exams
     */
    private function handleList() {
        $user = Auth::user();
        $filters = [
            'course_id' => isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : null,
            'status' => isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null,
            'search' => isset($_GET['search']) && $_GET['search'] !== '' ? trim($_GET['search']) : null,
            'category_id' => isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null
        ];

        // Retrieve exams based on role (instructors only see exams they can manage)
        if ($user['role'] === 'admin') {
            $exams = $this->examModel->all($filters);
            $courses = $this->courseModel->all();
        } else {
            $exams = $this->examModel->byInstructor($user['id'], $filters);
            $courses = $this->courseModel->byInstructor($user['id']);
        }

        $categories = $this->categoryModel->all();
        $csrfToken = Session::getCSRFToken();

        // Pass calculated stats (questions count) for each exam
        $examStats = [];
        foreach ($exams as $ex) {
            $fixedCount = count($this->examQuestionModel->forExam($ex['id']));
            $rules = $this->examQuestionModel->rulesForExam($ex['id']);
            $rulesCount = array_sum(array_column($rules, 'question_count'));
            $examStats[$ex['id']] = [
                'fixed_count' => $fixedCount,
                'rules_count' => $rulesCount,
                'total_count' => $fixedCount + $rulesCount
            ];
        }

        include __DIR__ . '/../views/exams/list.php';
    }

    /**
     * Action: Create Exam
     */
    private function handleCreate($csrfToken) {
        $user = Auth::user();
        $errors = [];
        $data = [
            'title' => '',
            'description' => '',
            'category_id' => '',
            'course_id' => '',
            'duration_minutes' => 60,
            'pass_percentage' => 50.00,
            'shuffle_questions' => 0,
            'shuffle_options' => 0,
            'max_attempts' => 0,
            'start_date' => '',
            'end_date' => '',
            'gradebook_category' => 'summative',
            'status' => 'draft'
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($csrfToken)) {
                $this->renderError("CSRF validation failed.");
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category_id' => $_POST['category_id'] ?? '',
                'course_id' => $_POST['course_id'] ?? '',
                'duration_minutes' => $_POST['duration_minutes'] ?? '',
                'pass_percentage' => $_POST['pass_percentage'] ?? '',
                'shuffle_questions' => isset($_POST['shuffle_questions']) ? 1 : 0,
                'shuffle_options' => isset($_POST['shuffle_options']) ? 1 : 0,
                'max_attempts' => $_POST['max_attempts'] ?? '',
                'start_date' => trim($_POST['start_date'] ?? ''),
                'end_date' => trim($_POST['end_date'] ?? ''),
                'gradebook_category' => $_POST['gradebook_category'] ?? 'summative',
                'status' => $_POST['status'] ?? 'draft'
            ];

            // Validation rules
            if (empty($data['title'])) {
                $errors[] = "Title is required.";
            }

            if ($data['duration_minutes'] === '' || intval($data['duration_minutes']) < 1) {
                $errors[] = "Duration must be a positive integer (at least 1 minute).";
            }

            if ($data['pass_percentage'] === '' || floatval($data['pass_percentage']) < 0 || floatval($data['pass_percentage']) > 100) {
                $errors[] = "Passing percentage must be between 0 and 100.";
            }

            if ($data['max_attempts'] === '' || intval($data['max_attempts']) < 0) {
                $errors[] = "Max attempts must be a positive number (0 for unlimited).";
            }

            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                if (strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                    $errors[] = "End date must be after start date.";
                }
            }

            // Verify course ownership if specified and not admin
            if ($user['role'] !== 'admin' && !empty($data['course_id'])) {
                $course = $this->courseModel->find($data['course_id']);
                if (!$course || (int)$course['instructor_id'] !== (int)$user['id']) {
                    $errors[] = "You do not own the selected course.";
                }
            }

            if (empty($errors)) {
                try {
                    $data['created_by'] = $user['id'];
                    $examId = $this->examModel->create($data);
                    if ($data['status'] === 'published') {
                        require_once __DIR__ . '/../../includes/GradebookCalculator.php';
                        GradebookCalculator::syncQuizItem($examId);
                    }
                    header("Location: index.php?route=admin/exams&action=build&id=" . $examId . "&success=" . urlencode("Exam created successfully! Now build your question set."));
                    exit;
                } catch (Exception $e) {
                    $errors[] = "Database failure: " . $e->getMessage();
                }
            }
        }

        // Get lists for select dropdowns
        $flatCategories = $this->categoryModel->getTreeFlat();
        $courses = ($user['role'] === 'admin') ? $this->courseModel->all() : $this->courseModel->byInstructor($user['id']);

        include __DIR__ . '/../views/exams/form.php';
    }

    /**
     * Action: Edit Exam
     */
    private function handleEdit($csrfToken) {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $exam = $this->examModel->find($id);

        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        $user = Auth::user();
        $errors = [];
        $data = [
            'title' => $exam['title'],
            'description' => $exam['description'],
            'category_id' => $exam['category_id'],
            'course_id' => $exam['course_id'],
            'duration_minutes' => $exam['duration_minutes'],
            'pass_percentage' => $exam['pass_percentage'],
            'shuffle_questions' => $exam['shuffle_questions'],
            'shuffle_options' => $exam['shuffle_options'],
            'max_attempts' => $exam['max_attempts'],
            'start_date' => $exam['start_date'] ? date('Y-m-d\TH:i', strtotime($exam['start_date'])) : '',
            'end_date' => $exam['end_date'] ? date('Y-m-d\TH:i', strtotime($exam['end_date'])) : '',
            'gradebook_category' => $exam['gradebook_category'],
            'status' => $exam['status']
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($csrfToken)) {
                $this->renderError("CSRF validation failed.");
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category_id' => $_POST['category_id'] ?? '',
                'course_id' => $_POST['course_id'] ?? '',
                'duration_minutes' => $_POST['duration_minutes'] ?? '',
                'pass_percentage' => $_POST['pass_percentage'] ?? '',
                'shuffle_questions' => isset($_POST['shuffle_questions']) ? 1 : 0,
                'shuffle_options' => isset($_POST['shuffle_options']) ? 1 : 0,
                'max_attempts' => $_POST['max_attempts'] ?? '',
                'start_date' => trim($_POST['start_date'] ?? ''),
                'end_date' => trim($_POST['end_date'] ?? ''),
                'gradebook_category' => $_POST['gradebook_category'] ?? 'summative',
                'status' => $_POST['status'] ?? 'draft'
            ];

            // Validation rules
            if (empty($data['title'])) {
                $errors[] = "Title is required.";
            }

            if ($data['duration_minutes'] === '' || intval($data['duration_minutes']) < 1) {
                $errors[] = "Duration must be a positive integer (at least 1 minute).";
            }

            if ($data['pass_percentage'] === '' || floatval($data['pass_percentage']) < 0 || floatval($data['pass_percentage']) > 100) {
                $errors[] = "Passing percentage must be between 0 and 100.";
            }

            if ($data['max_attempts'] === '' || intval($data['max_attempts']) < 0) {
                $errors[] = "Max attempts must be a positive number (0 for unlimited).";
            }

            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                if (strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                    $errors[] = "End date must be after start date.";
                }
            }

            // Verify course ownership if specified and not admin
            if ($user['role'] !== 'admin' && !empty($data['course_id'])) {
                $course = $this->courseModel->find($data['course_id']);
                if (!$course || (int)$course['instructor_id'] !== (int)$user['id']) {
                    $errors[] = "You do not own the selected course.";
                }
            }

            if (empty($errors)) {
                try {
                    $this->examModel->update($id, $data);
                    if ($data['status'] === 'published') {
                        require_once __DIR__ . '/../../includes/GradebookCalculator.php';
                        GradebookCalculator::syncQuizItem($id);
                    }
                    header("Location: index.php?route=admin/exams&action=list&success=" . urlencode("Exam configuration updated successfully."));
                    exit;
                } catch (Exception $e) {
                    $errors[] = "Database failure: " . $e->getMessage();
                }
            }
        }

        $flatCategories = $this->categoryModel->getTreeFlat();
        $courses = ($user['role'] === 'admin') ? $this->courseModel->all() : $this->courseModel->byInstructor($user['id']);

        include __DIR__ . '/../views/exams/form.php';
    }

    /**
     * Action: Delete Exam
     */
    private function handleDelete($csrfToken) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderError("Method not allowed.");
        }

        if (!Session::validateCSRF($csrfToken)) {
            $this->renderError("CSRF validation failed.");
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $exam = $this->examModel->find($id);

        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        try {
            $this->examModel->delete($id);
            header("Location: index.php?route=admin/exams&action=list&success=" . urlencode("Exam deleted successfully."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?route=admin/exams&action=list&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Action: Toggle or change Exam Status
     */
    private function handleStatusChange($csrfToken) {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $status = $_GET['status'] ?? '';

        if (!in_array($status, ['draft', 'published', 'archived'])) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Invalid status value."));
            exit;
        }

        $exam = $this->examModel->find($id);
        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        try {
            $this->examModel->setStatus($id, $status);
            if ($status === 'published') {
                require_once __DIR__ . '/../../includes/GradebookCalculator.php';
                GradebookCalculator::syncQuizItem($id);
            }
            header("Location: index.php?route=admin/exams&action=list&success=" . urlencode("Exam status changed to " . ucfirst($status) . "."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?route=admin/exams&action=list&error=" . urlencode("Status change failed: " . $e->getMessage()));
            exit;
        }
    }

    /**
     * Action: Build Questions Workspace
     */
    private function handleBuild() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $exam = $this->examModel->find($id);

        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        $fixedQuestions = $this->examQuestionModel->forExam($id);
        $rules = $this->examQuestionModel->rulesForExam($id);
        $flatCategories = $this->categoryModel->getTreeFlat();

        // Get filter inputs for the searchable fixed question bank picker
        $filters = [
            'category_id' => isset($_GET['q_category_id']) && $_GET['q_category_id'] !== '' ? (int)$_GET['q_category_id'] : null,
            'type' => isset($_GET['q_type']) && $_GET['q_type'] !== '' ? $_GET['q_type'] : null,
            'difficulty' => isset($_GET['q_difficulty']) && $_GET['q_difficulty'] !== '' ? $_GET['q_difficulty'] : null,
            'search' => isset($_GET['q_search']) && $_GET['q_search'] !== '' ? trim($_GET['q_search']) : null,
            'status' => 'published' // Only select published questions to pick from
        ];

        // Retrieve all available published questions
        $allQuestions = $this->questionModel->getAll($filters);

        // Filter out questions that are already added as fixed picks
        $fixedIds = array_map(function($fq) { return (int)$fq['question_id']; }, $fixedQuestions);
        $pickerQuestions = array_filter($allQuestions, function($q) use ($fixedIds) {
            return !in_array((int)$q['id'], $fixedIds);
        });

        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/exams/build.php';
    }

    /**
     * Action: Add Fixed Question
     */
    private function handleAddFixed($csrfToken) {
        $examId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $questionId = isset($_REQUEST['question_id']) ? (int)$_REQUEST['question_id'] : 0;

        $exam = $this->examModel->find($examId);
        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($csrfToken)) {
                $this->renderError("CSRF validation failed.");
            }
        }

        $question = $this->questionModel->find($questionId);
        if (!$question || $question['status'] !== 'published') {
            header("Location: index.php?route=admin/exams&action=build&id={$examId}&error=" . urlencode("Question is not available or is not published."));
            exit;
        }

        // Get max order index currently
        $fixed = $this->examQuestionModel->forExam($examId);
        $nextOrder = count($fixed);

        $success = $this->examQuestionModel->addQuestion($examId, $questionId, $nextOrder, $question['points']);

        if ($success) {
            header("Location: index.php?route=admin/exams&action=build&id={$examId}&success=" . urlencode("Question added successfully."));
        } else {
            header("Location: index.php?route=admin/exams&action=build&id={$examId}&error=" . urlencode("Question is already in this exam's fixed set."));
        }
        exit;
    }

    /**
     * Action: Remove Fixed Question
     */
    private function handleRemoveFixed($csrfToken) {
        $examId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $mappingId = isset($_REQUEST['mapping_id']) ? (int)$_REQUEST['mapping_id'] : 0;

        $exam = $this->examModel->find($examId);
        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($csrfToken)) {
                $this->renderError("CSRF validation failed.");
            }
        }

        $this->examQuestionModel->removeQuestion($mappingId);

        // Re-normalize order indexes of the remaining fixed questions
        $fixed = $this->examQuestionModel->forExam($examId);
        $orderedIds = array_map(function($fq) { return (int)$fq['id']; }, $fixed);
        $this->examQuestionModel->reorder($examId, $orderedIds);

        header("Location: index.php?route=admin/exams&action=build&id={$examId}&success=" . urlencode("Question removed from fixed set."));
        exit;
    }

    /**
     * Action: Reorder Fixed Questions
     */
    private function handleReorderFixed($csrfToken) {
        $examId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $mappingId = isset($_GET['mapping_id']) ? (int)$_GET['mapping_id'] : 0;
        $direction = $_GET['dir'] ?? ''; // 'up' or 'down'

        $exam = $this->examModel->find($examId);
        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        $fixed = $this->examQuestionModel->forExam($examId);
        $orderedIds = array_map(function($fq) { return (int)$fq['id']; }, $fixed);
        $targetIndex = array_search($mappingId, $orderedIds);

        if ($targetIndex !== false) {
            if ($direction === 'up' && $targetIndex > 0) {
                // Swap
                $temp = $orderedIds[$targetIndex];
                $orderedIds[$targetIndex] = $orderedIds[$targetIndex - 1];
                $orderedIds[$targetIndex - 1] = $temp;
            } elseif ($direction === 'down' && $targetIndex < count($orderedIds) - 1) {
                // Swap
                $temp = $orderedIds[$targetIndex];
                $orderedIds[$targetIndex] = $orderedIds[$targetIndex + 1];
                $orderedIds[$targetIndex + 1] = $temp;
            }
            $this->examQuestionModel->reorder($examId, $orderedIds);
        }

        header("Location: index.php?route=admin/exams&action=build&id={$examId}&success=" . urlencode("Question order updated."));
        exit;
    }

    /**
     * Action: Save Points Overrides
     */
    private function handlePointsOverride($csrfToken) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderError("Method not allowed.");
        }

        if (!Session::validateCSRF($csrfToken)) {
            $this->renderError("CSRF validation failed.");
        }

        $examId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $exam = $this->examModel->find($examId);

        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        $overrides = $_POST['points_override'] ?? [];
        $fixed = $this->examQuestionModel->forExam($examId);

        // Update database with custom values
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE exam_questions SET points_override = ? WHERE exam_id = ? AND id = ?");

        foreach ($fixed as $fq) {
            $fqId = $fq['id'];
            if (isset($overrides[$fqId])) {
                $val = trim($overrides[$fqId]);
                $overrideVal = ($val === '') ? null : floatval($val);
                $stmt->execute([$overrideVal, $examId, $fqId]);
            }
        }

        header("Location: index.php?route=admin/exams&action=build&id={$examId}&success=" . urlencode("Points overrides updated successfully."));
        exit;
    }

    /**
     * Action: Add Random Pull Rule
     */
    private function handleAddRule($csrfToken) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderError("Method not allowed.");
        }

        if (!Session::validateCSRF($csrfToken)) {
            $this->renderError("CSRF validation failed.");
        }

        $examId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $difficulty = $_POST['difficulty'] ?? 'any';
        $count = isset($_POST['question_count']) ? (int)$_POST['question_count'] : 0;

        $exam = $this->examModel->find($examId);
        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        if ($count <= 0) {
            header("Location: index.php?route=admin/exams&action=build&id={$examId}&error=" . urlencode("Question count must be a positive integer."));
            exit;
        }

        // Validate if category exists
        $cat = $this->categoryModel->find($categoryId);
        if (!$cat) {
            header("Location: index.php?route=admin/exams&action=build&id={$examId}&error=" . urlencode("Category not found."));
            exit;
        }

        // Validate if count exceeds available published questions
        $fixedQuestions = $this->examQuestionModel->forExam($examId);
        $excludeIds = array_map(function($fq) { return (int)$fq['question_id']; }, $fixedQuestions);

        // Let's count matching questions in that category
        $available = $this->countAvailableQuestions($categoryId, $difficulty, $excludeIds);

        if ($count > $available) {
            header("Location: index.php?route=admin/exams&action=build&id={$examId}&error=" . urlencode("Rule is unsatisfiable! Only {$available} published questions match this category/difficulty combination (excluding already chosen fixed picks)."));
            exit;
        }

        // Add the rule
        $this->examQuestionModel->addRule($examId, $categoryId, $difficulty, $count);

        header("Location: index.php?route=admin/exams&action=build&id={$examId}&success=" . urlencode("Random pull rule added successfully."));
        exit;
    }

    /**
     * Action: Remove Random Pull Rule
     */
    private function handleRemoveRule($csrfToken) {
        $examId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $ruleId = isset($_REQUEST['rule_id']) ? (int)$_REQUEST['rule_id'] : 0;

        $exam = $this->examModel->find($examId);
        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($csrfToken)) {
                $this->renderError("CSRF validation failed.");
            }
        }

        $this->examQuestionModel->removeRule($ruleId);

        header("Location: index.php?route=admin/exams&action=build&id={$examId}&success=" . urlencode("Random pull rule removed."));
        exit;
    }

    /**
     * Action: Preview Resolved Question Set
     */
    private function handlePreview() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $exam = $this->examModel->find($id);

        if (!$exam) {
            header("Location: index.php?route=admin/exams&error=" . urlencode("Exam not found."));
            exit;
        }

        $this->requireExamOwnershipOrAdmin($exam);

        // Call the model resolver to get final concrete list of question IDs
        $resolvedIds = $this->examQuestionModel->resolveQuestionSet($id);

        $resolvedQuestions = [];
        foreach ($resolvedIds as $qId) {
            $q = $this->questionModel->find($qId);
            if ($q) {
                $resolvedQuestions[] = $q;
            }
        }

        // Load renderer
        require_once __DIR__ . '/../../includes/QuestionRenderer.php';

        include __DIR__ . '/../views/exams/preview.php';
    }

    /**
     * Helper: count eligible published questions
     */
    private function countAvailableQuestions($categoryId, $difficulty, $excludeQuestionIds = []) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT COUNT(*) FROM questions WHERE (category_id = ? OR category_id IN (SELECT id FROM categories WHERE parent_id = ?)) AND status = 'published'";
        $params = [$categoryId, $categoryId];
        if ($difficulty !== 'any') {
            $query .= " AND difficulty = ?";
            $params[] = $difficulty;
        }
        if (!empty($excludeQuestionIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeQuestionIds), '?'));
            $query .= " AND id NOT IN (" . $placeholders . ")";
            $params = array_merge($params, $excludeQuestionIds);
        }
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Helper: Enforce instructor ownership check
     */
    private function requireExamOwnershipOrAdmin($exam) {
        $user = Auth::user();
        if ($user['role'] === 'admin') {
            return true;
        }

        // Created by self
        if (isset($exam['created_by']) && (int)$exam['created_by'] === (int)$user['id']) {
            return true;
        }

        // Belongs to a course owned by self
        if (!empty($exam['course_id'])) {
            $course = $this->courseModel->find($exam['course_id']);
            if ($course && (int)$course['instructor_id'] === (int)$user['id']) {
                return true;
            }
        }

        // Block access
        http_response_code(403);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h2 style='color:#dc3545; font-weight: 700;'>403 - Access Forbidden</h2>";
        echo "<p style='color:#6c757d;'>You do not have permission to manage this exam because it belongs to another instructor.</p>";
        echo "<p style='margin-top:20px;'><a href='index.php?route=admin/exams' style='color:#0d6efd; text-decoration:none; font-weight:600;'>&larr; Return to Exams</a></p>";
        echo "</div>";
        exit;
    }

    /**
     * Error render helper
     */
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
