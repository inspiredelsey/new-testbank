<?php
/**
 * ActivityLogger - Activity Logging Helper Class
 */

class ActivityLogger {
    /**
     * Log an action in the system activity log.
     * Guaranteed never to throw an exception or block execution.
     */
    public static function log($userId, $action, $courseId = null, $itemType = null, $itemId = null, $meta = null) {
        if (empty($userId)) {
            return;
        }

        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();

            // Prepare meta JSON string
            $metaJson = null;
            if ($meta !== null) {
                if (is_array($meta) || is_object($meta)) {
                    $metaJson = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else if (is_string($meta)) {
                    $metaJson = $meta;
                }
            }

            // Insert into activity_log
            $stmt = $db->prepare("INSERT INTO activity_log (user_id, course_id, action, item_type, item_id, meta, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)$userId,
                $courseId !== null ? (int)$courseId : null,
                $action,
                $itemType,
                $itemId !== null ? (int)$itemId : null,
                $metaJson,
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Fail silently so as not to disrupt user flow
            error_log("ActivityLogger failed: " . $e->getMessage());
        }
    }
}
