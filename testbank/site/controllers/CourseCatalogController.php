<?php
/**
 * Course Catalog Controller - Test Bank LMS
 * Lets any logged-in user or public visitor browse published courses,
 * filter by categories/search, and view details or enroll.
 */

require_once __DIR__ . '/../../admin/models/Course.php';
require_once __DIR__ . '/../../admin/models/Category.php';
require_once __DIR__ . '/../../admin/models/LearningPathItem.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Session.php';

class CourseCatalogController {
    private $courseModel;
    private $learningPathItemModel;
    private $db;

    public function __construct() {
        $this->courseModel = new Course();
        $this->learningPathItemModel = new LearningPathItem();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleRequest($action = 'list') {
        switch ($action) {
            case 'details':
                $this->handleDetails();
                break;
            case 'checkout':
                $this->handleCheckout();
                break;
            case 'checkout_submit':
                $this->handleCheckoutSubmit();
                break;
            case 'checkout_callback':
                $this->handleCheckoutCallback();
                break;
            case 'list':
            default:
                $this->handleList();
                break;
        }
    }

    private function handleList() {
        $user = Auth::getUser(); // null if not logged in
        $search = trim($_GET['search'] ?? '');
        $selectedCategory = intval($_GET['category_id'] ?? 0);
        $sort = trim($_GET['sort'] ?? 'newest');

        $filters = ['status' => 'published'];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($selectedCategory > 0) {
            $filters['category_id'] = $selectedCategory;
        }

        $courses = $this->courseModel->getAll($filters);

        // Sorting
        if ($sort === 'popular') {
            usort($courses, function($a, $b) {
                return intval($b['enrollment_count'] ?? 0) <=> intval($a['enrollment_count'] ?? 0);
            });
        } elseif ($sort === 'title') {
            usort($courses, function($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });
        }

        // Mark enrollment
        foreach ($courses as &$course) {
            $course['is_enrolled'] = $user ? $this->courseModel->isEnrolled($course['id'], $user['id']) : false;
        }
        unset($course);

        // Categories list
        $categoryModel = new Category();
        $categories = $categoryModel->all();

        // Platform stats
        $statsStmt = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM courses WHERE status = 'published') as course_count,
                (SELECT COUNT(*) FROM exams WHERE status = 'published') as exam_count,
                (SELECT COUNT(DISTINCT student_id) FROM course_enrollments) as student_count,
                (SELECT COUNT(*) FROM questions) as question_count
        ");
        $stats = $statsStmt ? $statsStmt->fetch(PDO::FETCH_ASSOC) : [
            'course_count' => count($courses),
            'exam_count' => 0,
            'student_count' => 0,
            'question_count' => 0
        ];

        include __DIR__ . '/../views/courses/catalog.php';
    }

    private function handleDetails() {
        $user = Auth::getUser(); // null if not logged in
        $courseId = intval($_GET['id'] ?? 0);
        if ($courseId <= 0) {
            $this->renderError('Invalid course.');
            return;
        }

        $course = $this->courseModel->find($courseId);
        if (!$course || $course['status'] !== 'published') {
            $this->renderError('This course is not available.');
            return;
        }

        $isEnrolled = $user ? $this->courseModel->isEnrolled($courseId, $user['id']) : false;
        $pathItems = $this->learningPathItemModel->forCourse($courseId);

        // Count of published exams attached to this course, for a quick
        // "what's involved" summary on the details page.
        $examStmt = $this->db->prepare("SELECT COUNT(*) FROM exams WHERE course_id = ? AND status = 'published'");
        $examStmt->execute([$courseId]);
        $examCount = intval($examStmt->fetchColumn());

        // Enrolled student count, shown as light social proof.
        $enrollStmt = $this->db->prepare("SELECT COUNT(*) FROM course_enrollments WHERE course_id = ? AND status = 'active'");
        $enrollStmt->execute([$courseId]);
        $enrolledCount = intval($enrollStmt->fetchColumn());

        include __DIR__ . '/../views/courses/details.php';
    }

    private function handleCheckout() {
        $user = Auth::getUser();
        $courseId = intval($_GET['id'] ?? 0);
        $cancelled = !empty($_GET['cancelled']);
        $priorToken = $_GET['token'] ?? null;

        // Special case: the gateway's cancel_url doesn't know the real course
        // id (id=0), only the pending_checkouts token — recover it from there
        // so the user lands back on the right course's checkout page.
        if ($cancelled && $priorToken) {
            $stmt = $this->db->prepare("SELECT course_id FROM pending_checkouts WHERE token = ?");
            $stmt->execute([$priorToken]);
            $row = $stmt->fetch();
            if ($row) {
                $courseId = intval($row['course_id']);
                $this->db->prepare("UPDATE pending_checkouts SET status = 'failed' WHERE token = ?")->execute([$priorToken]);
            }
        }

        if ($courseId <= 0) {
            $this->renderError('Invalid course.');
            return;
        }

        $course = $this->courseModel->find($courseId);
        if (!$course || $course['status'] !== 'published') {
            $this->renderError('This course is not available.');
            return;
        }

        if ($user && $this->courseModel->isEnrolled($courseId, $user['id'])) {
            header("Location: index.php?route=student/course/view&id=" . $courseId);
            exit;
        }

        $error = $_GET['error'] ?? null;

        include __DIR__ . '/../views/courses/checkout.php';
    }

    private function handleCheckoutSubmit() {
        $user = Auth::getUser();
        $courseId = intval($_POST['course_id'] ?? 0);
        $gateway = $_POST['gateway'] ?? '';

        if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
            header("Location: index.php?route=course/checkout&id=" . $courseId . "&error=" . urlencode('Security check failed, please try again.'));
            exit;
        }
        if (!in_array($gateway, ['stripe', 'paypal', 'paystack', 'flutterwave'])) {
            header("Location: index.php?route=course/checkout&id=" . $courseId . "&error=" . urlencode('Please choose a payment method.'));
            exit;
        }

        $course = $this->courseModel->find($courseId);
        if (!$course || $course['status'] !== 'published') {
            $this->renderError('This course is not available.');
            return;
        }
        if ($user && $this->courseModel->isEnrolled($courseId, $user['id'])) {
            header("Location: index.php?route=student/course/view&id=" . $courseId);
            exit;
        }

        $existingUserId = null;
        $name = null;
        $passwordHash = null;

        if ($user) {
            // Already logged in — just paying for another course, no new account needed.
            $existingUserId = $user['id'];
            $email = $user['email'];
            $name = $user['name'];
        } else {
            // Anonymous — collecting name/email/password as part of this same checkout.
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($name) || empty($email) || empty($password)) {
                header("Location: index.php?route=course/checkout&id=" . $courseId . "&error=" . urlencode('Please fill in all fields.'));
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header("Location: index.php?route=course/checkout&id=" . $courseId . "&error=" . urlencode('Invalid email address.'));
                exit;
            }
            if (strlen($password) < 8) {
                header("Location: index.php?route=course/checkout&id=" . $courseId . "&error=" . urlencode('Password must be at least 8 characters.'));
                exit;
            }

            // Does an account with this email already exist? If so, treat
            // this as that returning user paying for another course — but
            // only if the submitted password actually matches, otherwise
            // this would let someone enroll against a stranger's account.
            $existingStmt = $this->db->prepare("SELECT id, password_hash FROM users WHERE email = ?");
            $existingStmt->execute([$email]);
            $existingRow = $existingStmt->fetch();

            if ($existingRow) {
                if (!password_verify($password, $existingRow['password_hash'])) {
                    header("Location: index.php?route=course/checkout&id=" . $courseId . "&error=" . urlencode('An account with this email already exists. Please enter the correct password, or log in first.'));
                    exit;
                }
                $existingUserId = $existingRow['id'];
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        // Create the pending checkout row BEFORE contacting the gateway, so
        // we have somewhere to attach the gateway's session/order id.
        $token = bin2hex(random_bytes(32));
        $insertStmt = $this->db->prepare("
            INSERT INTO pending_checkouts (token, course_id, existing_user_id, name, email, password_hash, gateway, amount, currency, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $insertStmt->execute([$token, $courseId, $existingUserId, $name, $email, $passwordHash, $gateway, $course['price'], $course['currency']]);

        require_once __DIR__ . '/../../includes/PaymentGateway.php';

        try {
            if ($gateway === 'stripe') {
                $session = PaymentGateway::createStripeSession($token, $course['title'], $course['price'], $course['currency']);
                $this->db->prepare("UPDATE pending_checkouts SET gateway_session_id = ? WHERE token = ?")
                    ->execute([$session['session_id'], $token]);
                header("Location: " . $session['redirect_url']);
                exit;
            } elseif ($gateway === 'paypal') {
                $order = PaymentGateway::createPayPalOrder($token, $course['title'], $course['price'], $course['currency']);
                $this->db->prepare("UPDATE pending_checkouts SET gateway_session_id = ? WHERE token = ?")
                    ->execute([$order['order_id'], $token]);
                header("Location: " . $order['redirect_url']);
                exit;
            } elseif ($gateway === 'paystack') {
                $session = PaymentGateway::createPaystackSession($token, $course['title'], $course['price'], $course['currency'], $email);
                $this->db->prepare("UPDATE pending_checkouts SET gateway_session_id = ? WHERE token = ?")
                    ->execute([$session['reference'], $token]);
                header("Location: " . $session['redirect_url']);
                exit;
            } else {
                $session = PaymentGateway::createFlutterwaveSession($token, $course['title'], $course['price'], $course['currency'], $email, $name);
                $this->db->prepare("UPDATE pending_checkouts SET gateway_session_id = ? WHERE token = ?")
                    ->execute([$token, $token]);
                header("Location: " . $session['redirect_url']);
                exit;
            }
        } catch (Exception $e) {
            $this->db->prepare("UPDATE pending_checkouts SET status = 'failed' WHERE token = ?")->execute([$token]);
            header("Location: index.php?route=course/checkout&id=" . $courseId . "&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    private function handleCheckoutCallback() {
        $gateway = $_GET['gateway'] ?? '';
        $token = $_GET['token'] ?? '';

        if (!in_array($gateway, ['stripe', 'paypal', 'paystack', 'flutterwave']) || empty($token)) {
            $this->renderError('Invalid payment confirmation request.');
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM pending_checkouts WHERE token = ?");
        $stmt->execute([$token]);
        $pending = $stmt->fetch();

        if (!$pending) {
            $this->renderError('This checkout session could not be found.');
            return;
        }
        if ($pending['status'] === 'completed') {
            // Already processed (e.g. user refreshed the callback URL) —
            // just send them onward rather than double-enrolling/erroring.
            header("Location: index.php?route=student/course/view&id=" . $pending['course_id']);
            exit;
        }

        require_once __DIR__ . '/../../includes/PaymentGateway.php';

        $paid = false;
        try {
            if ($gateway === 'stripe') {
                $sessionId = $_GET['session_id'] ?? $pending['gateway_session_id'];
                $verification = PaymentGateway::verifyStripeSession($sessionId);
                $paid = $verification['paid'];
            } elseif ($gateway === 'paypal') {
                $capture = PaymentGateway::capturePayPalOrder($pending['gateway_session_id']);
                $paid = $capture['paid'];
            } elseif ($gateway === 'paystack') {
                $verification = PaymentGateway::verifyPaystackTransaction($pending['gateway_session_id']);
                $paid = $verification['paid'];
            } else {
                $verification = PaymentGateway::verifyFlutterwaveTransaction($token);
                $paid = $verification['paid'];
            }
        } catch (Exception $e) {
            $paid = false;
        }

        if (!$paid) {
            $this->db->prepare("UPDATE pending_checkouts SET status = 'failed' WHERE token = ?")->execute([$token]);
            $error = 'Payment was not completed. No charge was made and you have not been enrolled.';
            $courseId = $pending['course_id'];
            include __DIR__ . '/../views/courses/checkout_failed.php';
            return;
        }

        // Payment CONFIRMED server-side — now finalize: create the account
        // if needed, enroll, and log the student in.
        $userId = $pending['existing_user_id'];
        if (!$userId) {
            $createStmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'student', 'active')");
            $createStmt->execute([$pending['name'], $pending['email'], $pending['password_hash']]);
            $userId = intval($this->db->lastInsertId());
        }

        if (!$this->courseModel->isEnrolled($pending['course_id'], $userId)) {
            $this->courseModel->enrollStudent($pending['course_id'], $userId);
        }

        $this->db->prepare("UPDATE pending_checkouts SET status = 'completed', completed_at = NOW() WHERE token = ?")->execute([$token]);

        // Log the student in (whether new or returning) so they land
        // straight in the course, not back at a login screen.
        $userRow = $this->db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $userRow->execute([$userId]);
        $freshUser = $userRow->fetch();
        Auth::loginAs($freshUser);

        header("Location: index.php?route=student/course/view&id=" . $pending['course_id'] . "&enrolled=1");
        exit;
    }

    private function renderError($msg) {
        http_response_code(404);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h2>Not Found</h2>";
        echo "<p>" . htmlspecialchars($msg) . "</p>";
        echo "<p><a href='index.php?route=courses'>Back to Course Catalog</a></p>";
        echo "</div>";
    }
}
