<?php
/**
 * Student Controller
 */

require_once __DIR__ . '/../models/Attempt.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/LearningPath.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Grader.php';
require_once __DIR__ . '/../../includes/QuestionRenderer.php';

class StudentController {
    private $attemptModel;
    private $examModel;
    private $questionModel;
    private $courseModel;
    private $lpModel;

    public function __construct() {
        Auth::requireLogin();
        $this->attemptModel = new Attempt();
        $this->examModel = new Exam();
        $this->questionModel = new Question();
        $this->courseModel = new Course();
        $this->lpModel = new LearningPath();
    }

    public function handleRequest($action) {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        $user = Auth::getUser();

        switch ($action) {
            case 'instructions':
                $examId = intval($_GET['exam_id'] ?? 0);
                $exam = $this->examModel->getById($examId);
                if (!$exam || $exam['status'] !== 'published') {
                    header("Location: index.php?route=student/dashboard&error=Exam not found or unavailable.");
                    exit;
                }

                // Count taken attempts
                $attemptCount = $this->attemptModel->countAttempts($user['id'], $examId);
                include __DIR__ . '/../views/student/instructions.php';
                exit;

            case 'start':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF verification failed.");
                    }
                    $examId = intval($_POST['exam_id'] ?? 0);
                    try {
                        $attemptId = $this->attemptModel->startAttempt($user['id'], $examId);
                        header("Location: index.php?route=student/exam/take&attempt_id=" . $attemptId);
                        exit;
                    } catch (Exception $e) {
                        header("Location: index.php?route=student/dashboard&error=" . urlencode($e->getMessage()));
                        exit;
                    }
                }
                break;

            case 'take':
                $attemptId = intval($_GET['attempt_id'] ?? 0);
                $attempt = $this->attemptModel->getById($attemptId);
                
                // Authorize attempt owner
                if (!$attempt || $attempt['user_id'] != $user['id']) {
                    header("Location: index.php?route=student/dashboard&error=Unauthorized attempt access.");
                    exit;
                }

                // If already submitted or graded, redirect to review
                if ($attempt['status'] !== 'in_progress') {
                    header("Location: index.php?route=student/exam/review&attempt_id=" . $attemptId);
                    exit;
                }

                // Fetch remaining duration seconds
                $remainingSeconds = $this->attemptModel->getTimeRemaining($attemptId);
                if ($remainingSeconds <= 0) {
                    // Time up! Force grade submission
                    Grader::gradeAttempt($attemptId);
                    header("Location: index.php?route=student/exam/review&attempt_id=" . $attemptId . "&success=Time limit exceeded. Attempt auto-submitted.");
                    exit;
                }

                // Retrieve the resolved static questions list
                $rawResolved = $attempt['resolved_question_ids'] ?? ($attempt['resolved_questions'] ?? '[]');
                $resolvedQuestionIds = json_decode($rawResolved, true) ?: [];
                $questions = [];
                $savedAnswers = $this->attemptModel->getAnswers($attemptId);

                foreach ($resolvedQuestionIds as $qId) {
                    $q = $this->questionModel->getById($qId);
                    if ($q) {
                        $q['options'] = $this->questionModel->getOptions($qId);
                        
                        // Shuffle options if exam settings dictate
                        if ($attempt['shuffle_options'] && !empty($q['options']) && ($q['type'] === 'mcq_single' || $q['type'] === 'mcq_multi')) {
                            shuffle($q['options']);
                        }
                        
                        $questions[] = $q;
                    }
                }

                // Shuffle questions order if setting is active
                if ($attempt['shuffle_questions']) {
                    shuffle($questions);
                }

                include __DIR__ . '/../views/student/take.php';
                exit;

            case 'save_answer':
                // API autosave endpoint (triggered by JS AJAX)
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $attemptId = intval($_POST['attempt_id'] ?? 0);
                    $questionId = intval($_POST['question_id'] ?? 0);
                    $answerData = $_POST['answer'] ?? null;

                    $attempt = $this->attemptModel->getById($attemptId);
                    if ($attempt && $attempt['user_id'] == $user['id'] && $attempt['status'] === 'in_progress') {
                        $saved = $this->attemptModel->saveAnswer($attemptId, $questionId, $answerData);
                        header('Content-Type: application/json');
                        echo json_encode(['success' => $saved]);
                        exit;
                    }
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid save parameters']);
                exit;

            case 'submit':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF verification failed.");
                    }
                    $attemptId = intval($_POST['attempt_id'] ?? 0);
                    $attempt = $this->attemptModel->getById($attemptId);

                    if (!$attempt || $attempt['user_id'] != $user['id'] || $attempt['status'] !== 'in_progress') {
                        header("Location: index.php?route=student/dashboard&error=Attempt already finalized.");
                        exit;
                    }

                    // Save final answers sent in the POST form
                    $submittedAnswers = $_POST['q'] ?? [];
                    foreach ($submittedAnswers as $qId => $ans) {
                        $this->attemptModel->saveAnswer($attemptId, $qId, $ans);
                    }

                    // Run the auto-grader
                    Grader::gradeAttempt($attemptId);
                    header("Location: index.php?route=student/exam/review&attempt_id=" . $attemptId . "&success=Exam completed and graded successfully.");
                    exit;
                }
                break;

            case 'review':
                $attemptId = intval($_GET['attempt_id'] ?? 0);
                $attempt = $this->attemptModel->getById($attemptId);

                if (!$attempt || $attempt['user_id'] != $user['id']) {
                    header("Location: index.php?route=student/dashboard&error=Unauthorized access.");
                    exit;
                }

                // If attempt is still in progress, redirect back to taking
                if ($attempt['status'] === 'in_progress') {
                    header("Location: index.php?route=student/exam/take&attempt_id=" . $attemptId);
                    exit;
                }

                // Re-fetch resolved questions & responses
                $rawResolved = $attempt['resolved_question_ids'] ?? ($attempt['resolved_questions'] ?? '[]');
                $resolvedQuestionIds = json_decode($rawResolved, true) ?: [];
                $savedAnswers = $this->attemptModel->getAnswers($attemptId);
                $questions = [];

                foreach ($resolvedQuestionIds as $qId) {
                    $q = $this->questionModel->getById($qId);
                    if ($q) {
                        $q['options'] = $this->questionModel->getOptions($qId);
                        $questions[] = $q;
                    }
                }

                include __DIR__ . '/../views/student/review.php';
                exit;

            case 'course_view':
                $courseId = intval($_GET['id'] ?? 0);
                $course = $this->courseModel->getById($courseId);
                if (!$course || $course['status'] !== 'published') {
                    header("Location: index.php?route=student/dashboard&error=Course not found or unavailable.");
                    exit;
                }
                
                // Verify enrollment
                if (!$this->courseModel->isEnrolled($courseId, $user['id'])) {
                    header("Location: index.php?route=student/dashboard&error=You are not enrolled in this course.");
                    exit;
                }

                $lpItems = $this->lpModel->getItemsByCourse($courseId);
                $progress = $this->lpModel->getUserProgress($user['id'], $courseId);

                include __DIR__ . '/../views/student/course_view.php';
                exit;

            case 'complete_lp_item':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'CSRF verification failed']);
                        exit;
                    }

                    $lpItemId = intval($_POST['lp_item_id'] ?? 0);
                    $lpItem = $this->lpModel->getItemById($lpItemId);
                    if ($lpItem) {
                        // Verify student is enrolled in the item's course
                        if ($this->courseModel->isEnrolled($lpItem['course_id'], $user['id'])) {
                            // Check lock status first
                            $userProgress = $this->lpModel->getUserProgress($user['id'], $lpItem['course_id']);
                            if ($this->lpModel->isItemLocked($user['id'], $lpItem, $userProgress)) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'error' => 'Prerequisite not met']);
                                exit;
                            }

                            $this->lpModel->markItemCompleted($user['id'], $lpItemId);
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true]);
                            exit;
                        }
                    }
                }
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
                exit;

            case 'dashboard':
            default:
                // Get enrolled courses
                $enrolledCourses = $this->courseModel->getEnrolledCourses($user['id']);

                // Available exams — SCOPED to courses this student is actually
                // enrolled in (previously this used getAll(['status'=>'published']),
                // which returned every published exam system-wide regardless of
                // enrollment — a real access-scoping bug, now fixed).
                $availableExams = $this->examModel->forEnrolledStudent($user['id']);

                // Student history
                $history = $this->attemptModel->getStudentHistory($user['id']);

                // Certificates earned count
                $db = Database::getInstance()->getConnection();
                $certStmt = $db->prepare("SELECT COUNT(*) FROM certificates WHERE user_id = ?");
                $certStmt->execute([$user['id']]);
                $certificateCount = intval($certStmt->fetchColumn());

                // Per-course grade + learning path completion summary
                require_once __DIR__ . '/../../includes/GradebookCalculator.php';
                require_once __DIR__ . '/../models/LearningPathProgress.php';
                $lpProgressModel = new LearningPathProgress();

                $courseSummaries = [];
                foreach ($enrolledCourses as $course) {
                    $grade = GradebookCalculator::finalGrade($user['id'], $course['id']);

                    $progressRows = $lpProgressModel->forUser($user['id'], $course['id']);
                    $totalItems = count($progressRows);
                    $completedItems = count(array_filter($progressRows, fn($r) => $r['status'] === 'completed'));
                    $pathPercent = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : null;

                    $courseSummaries[$course['id']] = [
                        'grade' => $grade,
                        'path_percent' => $pathPercent,
                        'path_completed' => $completedItems,
                        'path_total' => $totalItems,
                    ];
                }

                include __DIR__ . '/../views/student/dashboard.php';
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
