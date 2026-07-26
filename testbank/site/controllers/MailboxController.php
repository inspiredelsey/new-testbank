<?php
/**
 * Mailbox Controller for Test Bank LMS
 * Handles user messaging (Inbox, Sent, Compose, Read, Reply)
 */

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/CSRF.php';
require_once __DIR__ . '/../../includes/ActivityLogger.php';
require_once __DIR__ . '/../../includes/Message.php';
require_once __DIR__ . '/../../admin/models/Course.php';

class MailboxController {

    public function handleRequest($action = 'inbox') {
        Auth::requireLogin();
        $currentUser = Auth::user();

        switch ($action) {
            case 'sent':
                $this->handleSent($currentUser);
                break;
            case 'compose':
                $this->handleCompose($currentUser);
                break;
            case 'view':
                $this->handleView($currentUser);
                break;
            case 'reply':
                $this->handleReply($currentUser);
                break;
            case 'inbox':
            default:
                $this->handleInbox($currentUser);
                break;
        }
    }

    private function handleInbox($currentUser) {
        $messages = Message::inbox($currentUser['id']);
        $unreadCount = Message::unreadCount($currentUser['id']);
        $activeRoute = 'site/mailbox';
        require_once __DIR__ . '/../views/mailbox/inbox.php';
    }

    private function handleSent($currentUser) {
        $messages = Message::sent($currentUser['id']);
        $unreadCount = Message::unreadCount($currentUser['id']);
        $activeRoute = 'site/mailbox';
        require_once __DIR__ . '/../views/mailbox/sent.php';
    }

    private function handleCompose($currentUser) {
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!CSRF::validateToken($token)) {
                $error = "CSRF validation failed. Please try submitting the form again.";
            } else {
                $recipientType = $_POST['recipient_type'] ?? 'user';
                $recipientId = ($recipientType === 'user' && !empty($_POST['recipient_id'])) ? (int)$_POST['recipient_id'] : null;
                $recipientGroupId = ($recipientType === 'group' && !empty($_POST['recipient_group_id'])) ? (int)$_POST['recipient_group_id'] : null;
                $courseId = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
                $subject = $_POST['subject'] ?? '';
                $body = $_POST['body'] ?? '';

                try {
                    $msgId = Message::send($currentUser['id'], $recipientId, $recipientGroupId, $courseId, $subject, $body);
                    ActivityLogger::log($currentUser['id'], 'message_sent', $courseId, 'message', $msgId, ['subject' => $subject]);

                    header("Location: index.php?route=site/mailbox&action=sent&success=" . urlencode("Message sent successfully!"));
                    exit;
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $recipientUsers = Message::getRecipientUsers($currentUser);
        $recipientGroups = Message::getRecipientGroups($currentUser);
        $courseModel = new Course();
        $courses = $courseModel->all();
        $unreadCount = Message::unreadCount($currentUser['id']);
        $activeRoute = 'site/mailbox';

        // Pre-fill values if returning after error or reply
        $prefillRecipientType = $_POST['recipient_type'] ?? ($_GET['type'] ?? 'user');
        $prefillRecipientId = $_POST['recipient_id'] ?? ($_GET['recipient_id'] ?? '');
        $prefillRecipientGroupId = $_POST['recipient_group_id'] ?? ($_GET['group_id'] ?? '');
        $prefillCourseId = $_POST['course_id'] ?? ($_GET['course_id'] ?? '');
        $prefillSubject = $_POST['subject'] ?? ($_GET['subject'] ?? '');
        $prefillBody = $_POST['body'] ?? ($_GET['body'] ?? '');

        require_once __DIR__ . '/../views/mailbox/compose.php';
    }

    private function handleView($currentUser) {
        $messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $message = Message::find($messageId, $currentUser['id']);

        if (!$message) {
            header("Location: index.php?route=site/mailbox&action=inbox&error=" . urlencode("Message not found or you are not authorized to view it."));
            exit;
        }

        // Mark read if current user is recipient or in recipient group
        Message::markRead($messageId, $currentUser['id']);
        $unreadCount = Message::unreadCount($currentUser['id']);
        $activeRoute = 'site/mailbox';

        require_once __DIR__ . '/../views/mailbox/view.php';
    }

    private function handleReply($currentUser) {
        $messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $original = Message::find($messageId, $currentUser['id']);

        if (!$original) {
            header("Location: index.php?route=site/mailbox&action=inbox&error=" . urlencode("Original message not found or access denied."));
            exit;
        }

        $recipientUsers = Message::getRecipientUsers($currentUser);
        $recipientGroups = Message::getRecipientGroups($currentUser);
        $courseModel = new Course();
        $courses = $courseModel->all();
        $unreadCount = Message::unreadCount($currentUser['id']);
        $activeRoute = 'site/mailbox';

        // Setup prefilled values for reply
        $prefillRecipientType = 'user';
        $prefillRecipientId = $original['sender_id'];
        $prefillRecipientGroupId = '';
        $prefillCourseId = $original['course_id'] ?? '';
        
        $subject = $original['subject'];
        if (strpos(strtolower($subject), 're:') !== 0) {
            $subject = 'Re: ' . $subject;
        }
        $prefillSubject = $subject;

        $sentDate = date('M d, Y H:i', strtotime($original['sent_at']));
        $prefillBody = "\n\n--- On {$sentDate}, {$original['sender_name']} wrote:\n> " . str_replace("\n", "\n> ", $original['body']);

        require_once __DIR__ . '/../views/mailbox/compose.php';
    }
}
