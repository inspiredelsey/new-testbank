<?php
/**
 * Essay Grading Controller for Instructors and Admins
 */

require_once __DIR__ . '/../models/EssayGrading.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class EssayGradingController {
    private $model;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->model = new EssayGrading();
    }

    public function handleRequest($action) {
        $user = Auth::getUser();
        $instructorId = ($user['role'] === 'instructor') ? $user['id'] : null;

        switch ($action) {
            case 'list':
            case 'queue':
                $queue = $this->model->pendingQueue($instructorId);
                include __DIR__ . '/../views/essay-grading/queue.php';
                exit;

            case 'grade':
                $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
                $answer = $this->model->getEssayAnswer($id);
                if (!$answer) {
                    $this->redirectWithError("Essay answer not found.");
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $csrfToken = $_POST['csrf_token'] ?? '';
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }

                    $points = floatval($_POST['points_awarded'] ?? 0.0);
                    $maxPoints = floatval($answer['max_points'] ?? 0.0);

                    if ($points < 0.0 || $points > $maxPoints) {
                        $this->renderError("Points awarded must be between 0 and " . $maxPoints);
                    }

                    $success = $this->model->gradeEssay($id, $points);
                    if ($success) {
                        header("Location: index.php?route=admin/essay-grading&success=Essay question graded successfully!");
                        exit;
                    } else {
                        $this->renderError("Failed to update grade.");
                    }
                } else {
                    include __DIR__ . '/../views/essay-grading/grade.php';
                    exit;
                }
                break;

            default:
                header("Location: index.php?route=admin/essay-grading");
                exit;
        }
    }

    private function redirectWithError($msg) {
        header("Location: index.php?route=admin/essay-grading&error=" . urlencode($msg));
        exit;
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
