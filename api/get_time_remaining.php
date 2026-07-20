<?php
/**
 * API - Get Time Remaining
 */

require_once __DIR__ . '/../testbank/includes/Session.php';
require_once __DIR__ . '/../testbank/includes/Auth.php';
require_once __DIR__ . '/../testbank/site/models/AttemptModel.php';

header('Content-Type: application/json');

Session::start();

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$attemptId = intval($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid attempt ID']);
    exit;
}

$user = Auth::getUser();
$attemptModel = new AttemptModel();

$db = Database::getInstance()->getConnection();
$stmtOwner = $db->prepare("SELECT user_id FROM exam_attempts WHERE id = ?");
$stmtOwner->execute([$attemptId]);
$userId = $stmtOwner->fetchColumn();

if (!$userId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Attempt not found']);
    exit;
}

if ($userId != $user['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized attempt access']);
    exit;
}

$secondsRemaining = $attemptModel->getTimeRemaining($attemptId);

echo json_encode([
    'success' => true,
    'seconds_remaining' => $secondsRemaining
]);
