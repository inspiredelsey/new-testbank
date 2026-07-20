<?php
/**
 * AttemptController - Student Facing Exam taking
 */

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../models/AttemptModel.php';
require_once __DIR__ . '/../../includes/QuestionRenderer.php';

class AttemptController {
    private $attemptModel;

    public function __construct() {
        Auth::requireLogin();
        $this->attemptModel = new AttemptModel();
    }

    public function handleRequest($action) {
        $user = Auth::getUser();

        switch ($action) {
            case 'start':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $csrfToken = $_POST['csrf_token'] ?? '';
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF verification failed.");
                    }
                    $examId = intval($_POST['exam_id'] ?? 0);
                    try {
                        $attempt = $this->attemptModel->start($examId, $user['id']);
                        header("Location: index.php?route=student/exam/take&attempt_id=" . $attempt['id']);
                        exit;
                    } catch (Exception $e) {
                        header("Location: index.php?route=student/dashboard&error=" . urlencode($e->getMessage()));
                        exit;
                    }
                }
                break;

            case 'take':
                $attemptId = intval($_GET['attempt_id'] ?? 0);
                $attempt = $this->attemptModel->getAttempt($attemptId);

                if (!$attempt || $attempt['user_id'] != $user['id']) {
                    header("Location: index.php?route=student/dashboard&error=Unauthorized attempt access.");
                    exit;
                }

                if ($attempt['status'] !== 'in_progress') {
                    header("Location: index.php?route=student/exam/review&attempt_id=" . $attemptId);
                    exit;
                }

                // Check remaining time
                if ($attempt['duration_minutes'] > 0) {
                    $remainingSeconds = $this->attemptModel->getTimeRemaining($attemptId);
                    if ($remainingSeconds <= 0) {
                        // Submit attempt automatically since time limit exceeded
                        $this->attemptModel->submit($attemptId);
                        header("Location: index.php?route=student/exam/review&attempt_id=" . $attemptId . "&info=Time limit has expired. Attempt auto-submitted.");
                        exit;
                    }
                } else {
                    $remainingSeconds = 0; // Unlimited
                }

                $questions = $attempt['questions'];
                $savedAnswers = $this->attemptModel->getAnswers($attemptId);

                include __DIR__ . '/../views/attempt/take.php';
                exit;

            case 'submit':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $csrfToken = $_POST['csrf_token'] ?? '';
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF verification failed.");
                    }
                    $attemptId = intval($_POST['attempt_id'] ?? 0);
                    $attempt = $this->attemptModel->getAttempt($attemptId);

                    if (!$attempt || $attempt['user_id'] != $user['id']) {
                        header("Location: index.php?route=student/dashboard&error=Unauthorized attempt access.");
                        exit;
                    }

                    if ($attempt['status'] === 'in_progress') {
                        $this->attemptModel->submit($attemptId);
                    }

                    header("Location: index.php?route=student/exam/pending&attempt_id=" . $attemptId);
                    exit;
                }
                break;

            case 'pending':
                $attemptId = intval($_GET['attempt_id'] ?? 0);
                $attempt = $this->attemptModel->getAttempt($attemptId);

                if (!$attempt || $attempt['user_id'] != $user['id']) {
                    header("Location: index.php?route=student/dashboard&error=Unauthorized access.");
                    exit;
                }

                $savedAnswers = $this->attemptModel->getAnswers($attemptId);

                include __DIR__ . '/../views/attempt/results.php';
                exit;

            case 'review':
                $attemptId = intval($_GET['attempt_id'] ?? 0);
                $attempt = $this->attemptModel->getAttempt($attemptId);

                if (!$attempt || $attempt['user_id'] != $user['id']) {
                    header("Location: index.php?route=student/dashboard&error=Unauthorized access.");
                    exit;
                }

                // If attempt is still in progress, redirect back to taking
                if ($attempt['status'] === 'in_progress') {
                    header("Location: index.php?route=student/exam/take&attempt_id=" . $attemptId);
                    exit;
                }

                $questions = $attempt['questions'];
                $savedAnswers = $this->attemptModel->getAnswers($attemptId);

                include __DIR__ . '/../views/attempt/review.php';
                exit;

            default:
                header("Location: index.php?route=student/dashboard");
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
