<?php
/**
 * Question Controller - Test Bank LMS
 * Manages question CRUD, search, and live admin-facing preview rendering.
 */

require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/CaseStudy.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/QuestionRenderer.php';

class QuestionController {
    private $questionModel;
    private $categoryModel;
    private $caseStudyModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->questionModel = new Question();
        $this->categoryModel = new Category();
        $this->caseStudyModel = new CaseStudy();
    }

    /**
     * Route and handle action dispatching
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
            case 'preview':
                $this->handlePreview();
                break;
            default:
                header("Location: index.php?route=admin/questions&action=list");
                exit;
        }
    }

    /**
     * Action: List questions
     */
    private function handleList() {
        $filters = [
            'category_id' => isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null,
            'type' => isset($_GET['type']) && $_GET['type'] !== '' ? $_GET['type'] : null,
            'difficulty' => isset($_GET['difficulty']) && $_GET['difficulty'] !== '' ? $_GET['difficulty'] : null,
            'status' => isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null,
            'search' => isset($_GET['search']) && $_GET['search'] !== '' ? trim($_GET['search']) : null
        ];

        $questions = $this->questionModel->getAll($filters);
        $categories = $this->categoryModel->all();
        $csrfToken = Session::getCSRFToken();

        include __DIR__ . '/../views/questions/list.php';
    }

    /**
     * Action: Create question
     */
    private function handleCreate() {
        $errors = [];
        $categories = $this->categoryModel->all();
        $cases = $this->caseStudyModel->all();
        $csrfToken = Session::getCSRFToken();

        // Default variables for form
        $category_id = '';
        $case_id = '';
        $case_order = '';
        $type = 'mcq_single';
        $question_text = '';
        $difficulty = 'medium';
        $points = 1.00;
        $scoring_method = 'all_or_nothing';
        $status = 'draft';
        
        // Structured data for form re-fill
        $qData = ['options' => []];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security check failed (CSRF token mismatch).");
            }

            $category_id = (int)$_POST['category_id'];
            $case_id = !empty($_POST['case_id']) ? (int)$_POST['case_id'] : null;
            $case_order = !empty($_POST['case_order']) ? (int)$_POST['case_order'] : null;
            $type = $_POST['type'];
            $question_text = trim($_POST['question_text']);
            $difficulty = $_POST['difficulty'];
            $points = floatval($_POST['points']);
            $scoring_method = $_POST['scoring_method'] ?? 'all_or_nothing';
            $status = $_POST['status'] ?? 'draft';

            try {
                // Assemble question_data based on selected question type
                $qData = $this->assembleQuestionDataFromPOST($type);

                // Build insertion payload
                $payload = [
                    'category_id' => $category_id,
                    'case_id' => $case_id,
                    'case_order' => $case_order,
                    'type' => $type,
                    'question_text' => $question_text,
                    'question_data' => $qData,
                    'difficulty' => $difficulty,
                    'points' => $points,
                    'scoring_method' => $scoring_method,
                    'status' => $status,
                    'created_by' => Auth::user()['id']
                ];

                $this->questionModel->create($payload);

                header("Location: index.php?route=admin/questions&action=list&success=" . urlencode("Question created successfully!"));
                exit;

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        include __DIR__ . '/../views/questions/form.php';
    }

    /**
     * Action: Edit question
     */
    private function handleEdit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $question = $this->questionModel->find($id);

        if (!$question) {
            header("Location: index.php?route=admin/questions&action=list&error=" . urlencode("Question not found."));
            exit;
        }

        $this->requireQuestionOwnershipOrAdmin($question);

        $errors = [];
        $categories = $this->categoryModel->all();
        $cases = $this->caseStudyModel->all();
        $csrfToken = Session::getCSRFToken();

        // Populate variables
        $category_id = $question['category_id'];
        $case_id = $question['case_id'];
        $case_order = $question['case_order'];
        $type = $question['type'];
        $question_text = $question['question_text'];
        $difficulty = $question['difficulty'];
        $points = floatval($question['points']);
        $scoring_method = $question['scoring_method'];
        $status = $question['status'];
        $qData = $question['question_data'] ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security check failed (CSRF token mismatch).");
            }

            $category_id = (int)$_POST['category_id'];
            $case_id = !empty($_POST['case_id']) ? (int)$_POST['case_id'] : null;
            $case_order = !empty($_POST['case_order']) ? (int)$_POST['case_order'] : null;
            $type = $_POST['type'];
            $question_text = trim($_POST['question_text']);
            $difficulty = $_POST['difficulty'];
            $points = floatval($_POST['points']);
            $scoring_method = $_POST['scoring_method'] ?? 'all_or_nothing';
            $status = $_POST['status'] ?? 'draft';

            try {
                // Assemble options
                $qData = $this->assembleQuestionDataFromPOST($type);

                $payload = [
                    'category_id' => $category_id,
                    'case_id' => $case_id,
                    'case_order' => $case_order,
                    'type' => $type,
                    'question_text' => $question_text,
                    'question_data' => $qData,
                    'difficulty' => $difficulty,
                    'points' => $points,
                    'scoring_method' => $scoring_method,
                    'status' => $status
                ];

                $this->questionModel->update($id, $payload);

                header("Location: index.php?route=admin/questions&action=list&success=" . urlencode("Question updated successfully!"));
                exit;

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        include __DIR__ . '/../views/questions/form.php';
    }

    /**
     * Action: Delete question
     */
    private function handleDelete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $question = $this->questionModel->find($id);

        if (!$question) {
            header("Location: index.php?route=admin/questions&action=list&error=" . urlencode("Question not found."));
            exit;
        }

        $this->requireQuestionOwnershipOrAdmin($question);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                die("Security check failed (CSRF token mismatch).");
            }

            $this->questionModel->delete($id);
            header("Location: index.php?route=admin/questions&action=list&success=" . urlencode("Question deleted successfully."));
            exit;
        }

        header("Location: index.php?route=admin/questions&action=list");
        exit;
    }

    /**
     * Action: Preview a single question
     */
    private function handlePreview() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $question = $this->questionModel->find($id);

        if (!$question) {
            echo "<div class='alert alert-danger'>Question not found.</div>";
            exit;
        }

        include __DIR__ . '/../views/questions/preview.php';
    }

    /**
     * Build the structured PHP question_data array from POST arrays
     */
    private function assembleQuestionDataFromPOST($type) {
        if (in_array($type, ['mcq_single', 'mcq_multi_sata'])) {
            $optionsInput = $_POST['options'] ?? [];
            $options = [];
            
            foreach ($optionsInput as $idx => $optText) {
                $optText = trim($optText);
                if ($optText === '') continue;

                $isCorrect = false;
                if ($type === 'mcq_single') {
                    $isCorrect = (isset($_POST['correct_option']) && $_POST['correct_option'] == $idx);
                } elseif ($type === 'mcq_multi_sata') {
                    $isCorrect = (isset($_POST['correct_options']) && is_array($_POST['correct_options']) && in_array($idx, $_POST['correct_options']));
                }

                $options[] = [
                    'id' => 'o' . ($idx + 1),
                    'text' => $optText,
                    'is_correct' => $isCorrect
                ];
            }
            return ['options' => $options];

        } elseif ($type === 'true_false') {
            $correctOption = $_POST['correct_option'] ?? 'true';
            return [
                'options' => [
                    [
                        'id' => 'o1',
                        'text' => 'True',
                        'is_correct' => ($correctOption === 'true')
                    ],
                    [
                        'id' => 'o2',
                        'text' => 'False',
                        'is_correct' => ($correctOption === 'false')
                    ]
                ]
            ];

        } elseif ($type === 'matching') {
            $leftInput = $_POST['left_items'] ?? [];
            $rightInput = $_POST['right_items'] ?? [];
            $matchInput = $_POST['match_pair'] ?? [];

            $leftList = [];
            foreach ($leftInput as $idx => $text) {
                $text = trim($text);
                if ($text === '') continue;
                $leftList[] = [
                    'id' => 'l' . ($idx + 1),
                    'text' => $text,
                    'original_index' => $idx
                ];
            }

            $rightList = [];
            foreach ($rightInput as $idx => $text) {
                $text = trim($text);
                if ($text === '') continue;
                $rightList[] = [
                    'id' => 'r' . ($idx + 1),
                    'text' => $text,
                    'original_index' => $idx
                ];
            }

            // Correct mapping coordinates
            $correctPairs = [];
            foreach ($leftList as $lItem) {
                $lIdx = $lItem['original_index'];
                $rIdxSelected = $matchInput[$lIdx] ?? null;
                
                // Find matching right list item ID
                $matchedRightId = null;
                foreach ($rightList as $rItem) {
                    if ($rItem['original_index'] == $rIdxSelected) {
                        $matchedRightId = $rItem['id'];
                        break;
                    }
                }

                if ($matchedRightId !== null) {
                    $correctPairs[] = [$lItem['id'], $matchedRightId];
                }
            }

            // Remove internal processing trace indices
            foreach ($leftList as &$l) unset($l['original_index']);
            foreach ($rightList as &$r) unset($r['original_index']);

            return [
                'left' => $leftList,
                'right' => $rightList,
                'correct_pairs' => $correctPairs
            ];
        } elseif ($type === 'matrix_single' || $type === 'matrix_multi') {
            $rowsInput = $_POST['matrix_rows'] ?? [];
            $colsInput = $_POST['matrix_columns'] ?? [];
            
            $rows = [];
            foreach ($rowsInput as $idx => $label) {
                $label = trim($label);
                if ($label === '') continue;
                $rows[] = [
                    'id' => 'r' . ($idx + 1),
                    'label' => $label,
                    'original_index' => $idx
                ];
            }

            $columns = [];
            foreach ($colsInput as $idx => $label) {
                $label = trim($label);
                if ($label === '') continue;
                $columns[] = [
                    'id' => 'c' . ($idx + 1),
                    'label' => $label,
                    'original_index' => $idx
                ];
            }

            $correct = [];
            if ($type === 'matrix_single') {
                $correctSingle = $_POST['matrix_correct_single'] ?? [];
                foreach ($rows as $row) {
                    $rowId = $row['id'];
                    $origRowIdx = $row['original_index'];
                    
                    $selectedColIdx = $correctSingle[$origRowIdx] ?? null;
                    $selectedColId = null;
                    if ($selectedColIdx !== null) {
                        foreach ($columns as $col) {
                            if ($col['original_index'] == $selectedColIdx) {
                                $selectedColId = $col['id'];
                                break;
                            }
                        }
                    }
                    $correct[$rowId] = $selectedColId ? [$selectedColId] : [];
                }
            } else {
                // matrix_multi
                $correctMulti = $_POST['matrix_correct_multi'] ?? [];
                foreach ($rows as $row) {
                    $rowId = $row['id'];
                    $origRowIdx = $row['original_index'];
                    
                    $selectedColIdxs = $correctMulti[$origRowIdx] ?? [];
                    $selectedColIds = [];
                    foreach ($selectedColIdxs as $selColIdx) {
                        foreach ($columns as $col) {
                            if ($col['original_index'] == $selColIdx) {
                                $selectedColIds[] = $col['id'];
                                break;
                            }
                        }
                    }
                    $correct[$rowId] = $selectedColIds;
                }
            }

            foreach ($rows as &$r) unset($r['original_index']);
            foreach ($columns as &$c) unset($c['original_index']);

            return [
                'rows' => $rows,
                'columns' => $columns,
                'correct' => $correct
            ];

        } elseif ($type === 'cloze_dropdown' || $type === 'cloze_dragdrop') {
            $passage = $_POST['cloze_passage'] ?? '';
            $blankIds = $_POST['cloze_blank_id'] ?? [];
            $blankOptionsInput = $_POST['cloze_blank_options'] ?? [];
            $blankCorrectInput = $_POST['cloze_blank_correct'] ?? [];

            $blanks = [];
            foreach ($blankIds as $idx => $id) {
                $id = trim($id);
                if ($id === '') continue;

                $rawOpts = isset($blankOptionsInput[$idx]) ? explode(',', $blankOptionsInput[$idx]) : [];
                $options = [];
                foreach ($rawOpts as $opt) {
                    $opt = trim($opt);
                    if ($opt !== '') {
                        $options[] = $opt;
                    }
                }

                $correctVal = isset($blankCorrectInput[$idx]) ? trim($blankCorrectInput[$idx]) : '';

                $blanks[] = [
                    'id' => $id,
                    'options' => $options,
                    'correct' => $correctVal
                ];
            }

            return [
                'passage' => $passage,
                'blanks' => $blanks
            ];
        } elseif ($type === 'drag_drop_ordered') {
            $correctItemsInput = $_POST['drag_drop_items'] ?? [];
            $distractorItemsInput = $_POST['drag_drop_distractors'] ?? [];

            $items = [];
            $correctOrder = [];
            $distractors = [];

            $itemCount = 1;
            foreach ($correctItemsInput as $text) {
                $text = trim($text);
                if ($text === '') continue;
                $itemId = 'i' . $itemCount;
                $items[] = [
                    'id' => $itemId,
                    'text' => $text
                ];
                $correctOrder[] = $itemId;
                $itemCount++;
            }

            $distCount = 1;
            foreach ($distractorItemsInput as $text) {
                $text = trim($text);
                if ($text === '') continue;
                $distractors[] = [
                    'id' => 'd' . $distCount,
                    'text' => $text
                ];
                $distCount++;
            }

            return [
                'items' => $items,
                'correct_order' => $correctOrder,
                'distractors' => $distractors
            ];

        } elseif ($type === 'highlight') {
            $passageHtml = $_POST['highlight_passage_html'] ?? '';
            $segmentTexts = $_POST['highlight_segment_text'] ?? [];
            $correctSegmentIdxs = $_POST['highlight_segment_correct'] ?? [];

            $segments = [];
            $correctSegmentIds = [];

            $segCount = 1;
            foreach ($segmentTexts as $idx => $text) {
                $text = trim($text);
                if ($text === '') continue;

                $segId = 's' . $segCount;
                $segments[] = [
                    'id' => $segId,
                    'text' => $text
                ];

                if (in_array((string)$idx, $correctSegmentIdxs) || in_array($idx, $correctSegmentIdxs)) {
                    $correctSegmentIds[] = $segId;
                }

                $segCount++;
            }

            return [
                'passage_html' => $passageHtml,
                'segments' => $segments,
                'correct_segment_ids' => $correctSegmentIds
            ];
        }

        // Default or unhandled type fallback
        return [];
    }

    /**
     * Asserts the requesting instructor owns the given question or is an administrator.
     */
    private function requireQuestionOwnershipOrAdmin($question) {
        $user = Auth::user();
        if ($user['role'] === 'admin') {
            return;
        }

        if ((int)$question['created_by'] !== (int)$user['id']) {
            header("Location: index.php?route=admin/questions&action=list&error=" . urlencode("You are not authorized to manage this question."));
            exit;
        }
    }
}
