<?php
/**
 * Message Model Class for Test Bank LMS Internal Mailbox
 */

require_once __DIR__ . '/../../includes/Database.php';

class Message {

    /**
     * Get all received messages for a user (direct recipient or via group membership).
     * 
     * @param int $userId
     * @return array
     */
    public static function inbox($userId) {
        $userId = (int)$userId;
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT DISTINCT m.*, 
                       su.name AS sender_name, 
                       su.email AS sender_email, 
                       g.name AS group_name, 
                       c.title AS course_title,
                       CASE WHEN mr.read_at IS NOT NULL THEN 1 ELSE 0 END AS is_read
                FROM messages m
                INNER JOIN users su ON m.sender_id = su.id
                LEFT JOIN `groups` g ON m.recipient_group_id = g.id
                LEFT JOIN courses c ON m.course_id = c.id
                LEFT JOIN message_reads mr ON m.id = mr.message_id AND mr.user_id = :user_id
                WHERE m.recipient_id = :user_id_direct
                   OR m.recipient_group_id IN (SELECT group_id FROM group_members WHERE user_id = :user_id_group)
                ORDER BY m.sent_at DESC, m.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_id_direct' => $userId,
                'user_id_group' => $userId
            ]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Message::inbox error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all messages sent by a specific user.
     * 
     * @param int $userId
     * @return array
     */
    public static function sent($userId) {
        $userId = (int)$userId;
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT m.*, 
                       ru.name AS recipient_name, 
                       ru.email AS recipient_email, 
                       g.name AS group_name, 
                       c.title AS course_title
                FROM messages m
                LEFT JOIN users ru ON m.recipient_id = ru.id
                LEFT JOIN `groups` g ON m.recipient_group_id = g.id
                LEFT JOIN courses c ON m.course_id = c.id
                WHERE m.sender_id = :user_id
                ORDER BY m.sent_at DESC, m.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $results = $stmt->fetchAll() ?: [];

            foreach ($results as &$msg) {
                if (!empty($msg['recipient_group_id'])) {
                    $msg['recipient_display'] = "Group: " . ($msg['group_name'] ?? ('Group #' . $msg['recipient_group_id']));
                } else {
                    $msg['recipient_display'] = $msg['recipient_name'] ?? ('User #' . $msg['recipient_id']);
                }
            }
            return $results;
        } catch (PDOException $e) {
            error_log("Message::sent error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a specific message and check if user is authorized to view it.
     * 
     * @param int $messageId
     * @param int|null $userId
     * @return array|null
     */
    public static function find($messageId, $userId = null) {
        $messageId = (int)$messageId;
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT m.*, 
                       su.name AS sender_name, 
                       su.email AS sender_email, 
                       ru.name AS recipient_name, 
                       ru.email AS recipient_email, 
                       g.name AS group_name, 
                       c.title AS course_title
                FROM messages m
                INNER JOIN users su ON m.sender_id = su.id
                LEFT JOIN users ru ON m.recipient_id = ru.id
                LEFT JOIN `groups` g ON m.recipient_group_id = g.id
                LEFT JOIN courses c ON m.course_id = c.id
                WHERE m.id = :id
                LIMIT 1";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $messageId]);
            $msg = $stmt->fetch();

            if (!$msg) {
                return null;
            }

            if ($userId !== null) {
                $userId = (int)$userId;
                $isSender = ((int)$msg['sender_id'] === $userId);
                $isDirectRecipient = ((int)$msg['recipient_id'] === $userId);
                $isGroupRecipient = false;

                if (!empty($msg['recipient_group_id'])) {
                    $gStmt = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND user_id = :user_id");
                    $gStmt->execute([
                        'group_id' => $msg['recipient_group_id'],
                        'user_id' => $userId
                    ]);
                    $isGroupRecipient = ((int)$gStmt->fetchColumn() > 0);
                }

                if (!$isSender && !$isDirectRecipient && !$isGroupRecipient) {
                    return null; // Authorization failed
                }
            }

            return $msg;
        } catch (PDOException $e) {
            error_log("Message::find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a new message. Validates mutually exclusive recipient / group recipient.
     * 
     * @param int $senderId
     * @param int|null $recipientId
     * @param int|null $recipientGroupId
     * @param int|null $courseId
     * @param string $subject
     * @param string $body
     * @throws Exception if validation fails
     * @return int Inserted message ID
     */
    public static function send($senderId, $recipientId = null, $recipientGroupId = null, $courseId = null, $subject = '', $body = '') {
        $senderId = (int)$senderId;
        $recipientId = !empty($recipientId) ? (int)$recipientId : null;
        $recipientGroupId = !empty($recipientGroupId) ? (int)$recipientGroupId : null;
        $courseId = !empty($courseId) ? (int)$courseId : null;
        $subject = trim($subject);
        $body = trim($body);

        if (empty($subject)) {
            throw new Exception("Message subject is required.");
        }
        if (empty($body)) {
            throw new Exception("Message body is required.");
        }

        // Exactly one of recipient_id/recipient_group_id must be provided
        if (($recipientId === null && $recipientGroupId === null) || ($recipientId !== null && $recipientGroupId !== null)) {
            throw new Exception("Please specify either a direct recipient or a recipient group (not both and not neither).");
        }

        $db = Database::getInstance()->getConnection();

        if ($recipientGroupId !== null) {
            $chk = $db->prepare("SELECT id FROM `groups` WHERE id = :id LIMIT 1");
            $chk->execute(['id' => $recipientGroupId]);
            if (!$chk->fetch()) {
                throw new Exception("Selected group does not exist.");
            }
        }

        if ($recipientId !== null) {
            $chk = $db->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
            $chk->execute(['id' => $recipientId]);
            if (!$chk->fetch()) {
                throw new Exception("Selected user does not exist.");
            }
        }

        $stmt = $db->prepare("
            INSERT INTO messages (sender_id, recipient_id, recipient_group_id, course_id, subject, body, sent_at)
            VALUES (:sender_id, :recipient_id, :recipient_group_id, :course_id, :subject, :body, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'recipient_group_id' => $recipientGroupId,
            'course_id' => $courseId,
            'subject' => $subject,
            'body' => $body
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Mark a message as read for a specific user.
     * 
     * @param int $messageId
     * @param int $userId
     * @return void
     */
    public static function markRead($messageId, $userId) {
        $messageId = (int)$messageId;
        $userId = (int)$userId;
        $db = Database::getInstance()->getConnection();

        try {
            $chk = $db->prepare("SELECT COUNT(*) FROM message_reads WHERE message_id = :message_id AND user_id = :user_id");
            $chk->execute([
                'message_id' => $messageId,
                'user_id' => $userId
            ]);

            if ((int)$chk->fetchColumn() === 0) {
                $ins = $db->prepare("INSERT INTO message_reads (message_id, user_id, read_at) VALUES (:message_id, :user_id, CURRENT_TIMESTAMP)");
                $ins->execute([
                    'message_id' => $messageId,
                    'user_id' => $userId
                ]);
            }
        } catch (PDOException $e) {
            error_log("Message::markRead error: " . $e->getMessage());
        }
    }

    /**
     * Get count of unread messages for a user.
     * 
     * @param int $userId
     * @return int
     */
    public static function unreadCount($userId) {
        $userId = (int)$userId;
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(DISTINCT m.id) 
                FROM messages m
                LEFT JOIN message_reads mr ON m.id = mr.message_id AND mr.user_id = :user_id
                WHERE (m.recipient_id = :user_id_direct
                   OR m.recipient_group_id IN (SELECT group_id FROM group_members WHERE user_id = :user_id_group))
                  AND mr.read_at IS NULL
                  AND m.sender_id != :user_id_not_self";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_id_direct' => $userId,
                'user_id_group' => $userId,
                'user_id_not_self' => $userId
            ]);

            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Message::unreadCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get list of candidate user recipients for the compose screen.
     * 
     * @param array $currentUser
     * @return array
     */
    public static function getRecipientUsers($currentUser) {
        $db = Database::getInstance()->getConnection();
        try {
            // For admins/instructors, return all users except current user
            // For students, return all users (instructors, admins, and students)
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id != :current_id ORDER BY name ASC");
            $stmt->execute(['current_id' => $currentUser['id']]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Message::getRecipientUsers error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get list of candidate groups for recipient picker.
     * 
     * @param array $currentUser
     * @return array
     */
    public static function getRecipientGroups($currentUser) {
        $db = Database::getInstance()->getConnection();
        try {
            if ($currentUser['role'] === 'student') {
                // Students can message groups they are members of
                $sql = "SELECT g.id, g.name, g.description 
                        FROM `groups` g
                        INNER JOIN group_members gm ON g.id = gm.group_id
                        WHERE gm.user_id = :user_id
                        ORDER BY g.name ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute(['user_id' => $currentUser['id']]);
                return $stmt->fetchAll() ?: [];
            } else {
                // Instructors and admins can message any group
                $stmt = $db->query("SELECT id, name, description FROM `groups` ORDER BY name ASC");
                return $stmt->fetchAll() ?: [];
            }
        } catch (PDOException $e) {
            error_log("Message::getRecipientGroups error: " . $e->getMessage());
            return [];
        }
    }
}
