<?php
/**
 * API - Submit Attempt
 */

require_once __DIR__ . '/../testbank/includes/Session.php';
require_once __DIR__ . '/../testbank/includes/Auth.php';
require_once __DIR__ . '/../testbank/site/models/AttemptModel.php';

header('Content-Type: application/json');

Session::start();

$input = [];
if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
    $input = $_POST;
}

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!Session::validateCSRF($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF verification failed']);
    exit;
}

$attemptId = intval($input['attempt_id'] ?? 0);
if ($attemptId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid attempt ID']);
    exit;
}

$user = Auth::getUser();
$attemptModel = new AttemptModel();

$db = Database::getInstance()->getConnection();
$stmtOwner = $db->prepare("SELECT user_id, status FROM exam_attempts WHERE id = ?");
$stmtOwner->execute([$attemptId]);
$attemptRow = $stmtOwner->fetch();

if (!$attemptRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Attempt not found']);
    exit;
}

if ($attemptRow['user_id'] != $user['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized attempt access']);
    exit;
}

if ($attemptRow['status'] !== 'in_progress') {
    echo json_encode(['success' => true, 'message' => 'Attempt already submitted']);
    exit;
}

$success = $attemptModel->submit($attemptId);

if ($success) {
    require_once __DIR__ . '/../testbank/includes/Grader.php';
    Grader::gradeAttempt($attemptId);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to submit attempt']);
}
