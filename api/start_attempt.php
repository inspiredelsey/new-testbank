<?php
/**
 * API - Start Attempt
 */

require_once __DIR__ . '/../testbank/includes/Session.php';
require_once __DIR__ . '/../testbank/includes/Auth.php';
require_once __DIR__ . '/../testbank/site/models/AttemptModel.php';

header('Content-Type: application/json');

Session::start();

// Get POST content (JSON or Form)
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

$examId = intval($input['exam_id'] ?? 0);
if ($examId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid exam ID']);
    exit;
}

$user = Auth::getUser();

try {
    $attemptModel = new AttemptModel();
    $attempt = $attemptModel->start($examId, $user['id']);

    $questionsMeta = [];
    foreach ($attempt['questions'] as $q) {
        $questionsMeta[] = [
            'id' => $q['id'],
            'type' => $q['type'],
            'points' => $q['points']
        ];
    }

    echo json_encode([
        'success' => true,
        'attempt_id' => $attempt['id'],
        'questions' => $questionsMeta
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
