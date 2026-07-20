<?php
/**
 * Case Study Controller - Test Bank LMS
 * Manages clinical case studies, tab exhibits (nurse notes, vitals, labs), and question attachments.
 */

require_once __DIR__ . '/../models/CaseStudy.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class CaseStudyController {
    private $caseStudyModel;
    private $questionModel;
    private $categoryModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->caseStudyModel = new CaseStudy();
        $this->questionModel = new Question();
        $this->categoryModel = new Category();
    }

    /**
     * Dispatch routing action
     */
    public function handleRequest($action = 'list') {
        switch ($action) {
            case 'list':
            case 'index':
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
            case 'exhibits':
                $this->handleExhibits();
                break;
            case 'add_exhibit':
                $this->handleAddExhibit();
                break;
            case 'edit_exhibit':
                $this->handleEditExhibit();
                break;
            case 'delete_exhibit':
                $this->handleDeleteExhibit();
                break;
            case 'reorder_exhibits':
                $this->handleReorderExhibits();
                break;
            case 'attach':
                $this->handleAttach();
                break;
            case 'do_attach':
                $this->handleDoAttach();
                break;
            case 'detach':
                $this->handleDetach();
                break;
            default:
                header("Location: index.php?route=admin/cases&action=list");
                exit;
        }
    }

    /**
     * Action: List cases
     */
    private function handleList() {
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
        $cases = $this->caseStudyModel->all($categoryId);
        $categories = $this->categoryModel->all();
        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/cases/list.php';
    }

    /**
     * Action: Create case
     */
    private function handleCreate() {
        $errors = [];
        $categories = $this->categoryModel->all();
        $csrfToken = Session::getCSRFToken();

        $title = '';
        $scenario_text = '';
        $category_id = '';
        $is_trend = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security token validation failed (CSRF).");
            }

            $title = trim($_POST['title']);
            $scenario_text = trim($_POST['scenario_text']);
            $category_id = (int)$_POST['category_id'];
            $is_trend = isset($_POST['is_trend']) ? 1 : 0;

            if (empty($title)) {
                $errors[] = "Title is required.";
            }
            if (empty($scenario_text)) {
                $errors[] = "Scenario text is required.";
            }
            if (!$category_id) {
                $errors[] = "Category is required.";
            }

            if (empty($errors)) {
                try {
                    $caseId = $this->caseStudyModel->create([
                        'title' => $title,
                        'scenario_text' => $scenario_text,
                        'category_id' => $category_id,
                        'is_trend' => $is_trend,
                        'created_by' => Auth::user()['id']
                    ]);
                    header("Location: index.php?route=admin/cases&action=exhibits&case_id=" . $caseId . "&success=" . urlencode("Case created successfully! Now manage exhibits."));
                    exit;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        include __DIR__ . '/../views/cases/form.php';
    }

    /**
     * Action: Edit case
     */
    private function handleEdit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $case = $this->caseStudyModel->find($id);

        if (!$case) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Case Study not found."));
            exit;
        }

        $this->requireCaseOwnershipOrAdmin($case);

        $errors = [];
        $categories = $this->categoryModel->all();
        $csrfToken = Session::getCSRFToken();

        $title = $case['title'];
        $scenario_text = $case['scenario_text'];
        $category_id = $case['category_id'];
        $is_trend = $case['is_trend'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security token validation failed (CSRF).");
            }

            $title = trim($_POST['title']);
            $scenario_text = trim($_POST['scenario_text']);
            $category_id = (int)$_POST['category_id'];
            $is_trend = isset($_POST['is_trend']) ? 1 : 0;

            if (empty($title)) {
                $errors[] = "Title is required.";
            }
            if (empty($scenario_text)) {
                $errors[] = "Scenario text is required.";
            }
            if (!$category_id) {
                $errors[] = "Category is required.";
            }

            if (empty($errors)) {
                try {
                    $this->caseStudyModel->update($id, [
                        'title' => $title,
                        'scenario_text' => $scenario_text,
                        'category_id' => $category_id,
                        'is_trend' => $is_trend
                    ]);
                    header("Location: index.php?route=admin/cases&action=list&success=" . urlencode("Case Study updated successfully!"));
                    exit;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        include __DIR__ . '/../views/cases/form.php';
    }

    /**
     * Action: Delete case
     */
    private function handleDelete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $case = $this->caseStudyModel->find($id);

        if (!$case) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Case study not found."));
            exit;
        }

        $this->requireCaseOwnershipOrAdmin($case);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security validation failed (CSRF).");
            }

            try {
                $this->caseStudyModel->delete($id);
                header("Location: index.php?route=admin/cases&action=list&success=" . urlencode("Case study deleted successfully."));
                exit;
            } catch (Exception $e) {
                header("Location: index.php?route=admin/cases&action=list&error=" . urlencode($e->getMessage()));
                exit;
            }
        }

        header("Location: index.php?route=admin/cases&action=list");
        exit;
    }

    /**
     * Action: Manage exhibits list for a case
     */
    private function handleExhibits() {
        $caseId = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;
        $case = $this->caseStudyModel->find($caseId);

        if (!$case) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Case study not found."));
            exit;
        }

        $this->requireCaseOwnershipOrAdmin($case);

        $exhibits = $this->caseStudyModel->exhibitsForCase($caseId);
        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/cases/exhibits.php';
    }

    /**
     * Action: Add an exhibit tab
     */
    private function handleAddExhibit() {
        $caseId = isset($_REQUEST['case_id']) ? (int)$_REQUEST['case_id'] : 0;
        $case = $this->caseStudyModel->find($caseId);

        if (!$case) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Case Study not found."));
            exit;
        }

        $this->requireCaseOwnershipOrAdmin($case);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security validation failed.");
            }

            $tab_label = trim($_POST['tab_label']);
            $content = trim($_POST['content']);
            $timestamp_label = trim($_POST['timestamp_label'] ?? '');

            if (empty($tab_label) || empty($content)) {
                header("Location: index.php?route=admin/cases&action=exhibits&case_id=" . $caseId . "&error=" . urlencode("Tab label and content are required."));
                exit;
            }

            $this->caseStudyModel->addExhibit($caseId, [
                'tab_label' => $tab_label,
                'content' => $content,
                'timestamp_label' => $timestamp_label !== '' ? $timestamp_label : null
            ]);

            header("Location: index.php?route=admin/cases&action=exhibits&case_id=" . $caseId . "&success=" . urlencode("Exhibit tab added successfully."));
            exit;
        }
    }

    /**
     * Action: Edit an exhibit tab (JSON/Direct Form POST depending on interface, we support form POST)
     */
    private function handleEditExhibit() {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $exhibit = $this->caseStudyModel->findExhibit($id);

        if (!$exhibit) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Exhibit not found."));
            exit;
        }

        $case = $this->caseStudyModel->find($exhibit['case_id']);
        $this->requireCaseOwnershipOrAdmin($case);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security validation failed.");
            }

            $tab_label = trim($_POST['tab_label']);
            $content = trim($_POST['content']);
            $timestamp_label = trim($_POST['timestamp_label'] ?? '');

            if (empty($tab_label) || empty($content)) {
                header("Location: index.php?route=admin/cases&action=exhibits&case_id=" . $case['id'] . "&error=" . urlencode("Tab label and content are required."));
                exit;
            }

            $this->caseStudyModel->updateExhibit($id, [
                'tab_label' => $tab_label,
                'content' => $content,
                'timestamp_label' => $timestamp_label !== '' ? $timestamp_label : null
            ]);

            header("Location: index.php?route=admin/cases&action=exhibits&case_id=" . $case['id'] . "&success=" . urlencode("Exhibit tab updated successfully."));
            exit;
        }
    }

    /**
     * Action: Delete exhibit
     */
    private function handleDeleteExhibit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $exhibit = $this->caseStudyModel->findExhibit($id);

        if (!$exhibit) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Exhibit not found."));
            exit;
        }

        $case = $this->caseStudyModel->find($exhibit['case_id']);
        $this->requireCaseOwnershipOrAdmin($case);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security validation failed.");
            }

            $this->caseStudyModel->deleteExhibit($id);
            header("Location: index.php?route=admin/cases&action=exhibits&case_id=" . $case['id'] . "&success=" . urlencode("Exhibit tab deleted."));
            exit;
        }
    }

    /**
     * Action: Reorder exhibit tabs
     */
    private function handleReorderExhibits() {
        $caseId = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
        $case = $this->caseStudyModel->find($caseId);

        if (!$case) {
            echo json_encode(['success' => false, 'error' => 'Case Study not found']);
            exit;
        }

        $this->requireCaseOwnershipOrAdmin($case);

        if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'CSRF verification failed']);
            exit;
        }

        $orderedIds = $_POST['ordered_ids'] ?? [];
        if (!is_array($orderedIds) || empty($orderedIds)) {
            echo json_encode(['success' => false, 'error' => 'Invalid order array']);
            exit;
        }

        try {
            $this->caseStudyModel->reorderExhibits($caseId, $orderedIds);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Action: Show interactive layout to attach questions
     */
    private function handleAttach() {
        $caseId = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;
        $case = $this->caseStudyModel->find($caseId);

        if (!$case) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Case study not found."));
            exit;
        }

        $this->requireCaseOwnershipOrAdmin($case);

        // Fetch attached questions
        $attachedQuestions = $this->questionModel->forCase($caseId);

        // Fetch all standalone questions (no case) or from this category, to attach
        $allQuestions = $this->questionModel->getAll(['category_id' => $case['category_id']]);
        $unattachedQuestions = [];
        foreach ($allQuestions as $q) {
            if (empty($q['case_id'])) {
                $unattachedQuestions[] = $q;
            }
        }

        $csrfToken = Session::getCSRFToken();
        include __DIR__ . '/../views/cases/attach-questions.php';
    }

    /**
     * Action: Handle POST to attach a question
     */
    private function handleDoAttach() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/cases&action=list");
            exit;
        }

        if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
            die("Security validation failed (CSRF).");
        }

        $caseId = (int)$_POST['case_id'];
        $case = $this->caseStudyModel->find($caseId);

        if (!$case) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Case study not found."));
            exit;
        }

        $this->requireCaseOwnershipOrAdmin($case);

        $questionId = (int)$_POST['question_id'];
        $case_order = isset($_POST['case_order']) && $_POST['case_order'] !== '' ? (int)$_POST['case_order'] : 0;

        $this->questionModel->attachToCase($questionId, $caseId, $case_order);

        header("Location: index.php?route=admin/cases&action=attach&case_id=" . $caseId . "&success=" . urlencode("Question attached successfully!"));
        exit;
    }

    /**
     * Action: Handle POST to detach a question
     */
    private function handleDetach() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/cases&action=list");
            exit;
        }

        if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
            die("Security validation failed (CSRF).");
        }

        $questionId = (int)$_POST['question_id'];
        $question = $this->questionModel->find($questionId);

        if (!$question) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("Question not found."));
            exit;
        }

        $case = $this->caseStudyModel->find($question['case_id']);
        if ($case) {
            $this->requireCaseOwnershipOrAdmin($case);
        }

        $this->questionModel->detachFromCase($questionId);

        header("Location: index.php?route=admin/cases&action=attach&case_id=" . $case['id'] . "&success=" . urlencode("Question detached successfully."));
        exit;
    }

    /**
     * Helper: assert case owner/instructor, or administrator
     */
    private function requireCaseOwnershipOrAdmin($case) {
        $user = Auth::user();
        if ($user['role'] === 'admin') {
            return;
        }

        if ((int)$case['created_by'] !== (int)$user['id']) {
            header("Location: index.php?route=admin/cases&action=list&error=" . urlencode("You are not authorized to manage this Case Study."));
            exit;
        }
    }
}
