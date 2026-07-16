<?php
/**
 * Admin Attempt / Grading Controller
 */

require_once __DIR__ . '/../models/Attempt.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../../includes/Auth.php';

class AdminAttemptController {
    private $attemptModel;
    private $examModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->attemptModel = new Attempt();
        $this->examModel = new Exam();
    }

    public function handleRequest($action) {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

        switch ($action) {
            case 'grade':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $answerId = intval($_POST['answer_id'] ?? 0);
                    $score = floatval($_POST['points_awarded'] ?? 0.00);

                    $this->attemptModel->gradeEssayAnswer($answerId, $score);
                    header("Location: index.php?route=admin/attempts&success=Essay question graded successfully!");
                    exit;
                }
                break;

            case 'stats':
                $examId = intval($_GET['exam_id'] ?? 0);
                $exam = $this->examModel->getById($examId);
                if (!$exam) {
                    header("Location: index.php?route=admin/attempts&error=Exam not found");
                    exit;
                }

                $statsData = $this->attemptModel->getExamResultsDashboard($examId);
                $attempts = $statsData['attempts'];
                $summary = $statsData['summary'];
                $distribution = $statsData['distribution'];
                
                // Item Analysis (Hardest questions first)
                $itemAnalysis = $this->attemptModel->getQuestionDifficultyAnalysis($examId);

                include __DIR__ . '/../views/attempts/stats.php';
                exit;

            case 'index':
            default:
                // Filter grading queue for instructor
                $user = Auth::getUser();
                $instructorId = ($user['role'] === 'instructor') ? $user['id'] : null;
                
                $gradingQueue = $this->attemptModel->getGradingQueue($instructorId);
                $examsList = $this->examModel->getAll();

                include __DIR__ . '/../views/attempts/index.php';
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
