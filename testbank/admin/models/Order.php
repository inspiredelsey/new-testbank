<?php
/**
 * Order Model
 * Wraps the pending_checkouts table as a proper order/payment record for
 * admin and instructor visibility. Every checkout attempt (successful or
 * not) lives in pending_checkouts — this model surfaces that data in a
 * form an admin/instructor actually wants to look at.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * All orders, optionally scoped to a specific instructor's own courses
     * and filtered by status/course/gateway. Buyer identity is resolved by
     * email match against users, NOT existing_user_id — existing_user_id
     * is only populated when the buyer already had an account before
     * checking out; for brand-new signups it stays null even after the
     * order completes and their account gets created, so email is the
     * only field guaranteed to be populated in every case.
     */
    public function allOrders($filters = []) {
        $query = "
            SELECT pc.*, c.title AS course_title, c.instructor_id,
                   u.name AS buyer_name, u.id AS buyer_user_id
            FROM pending_checkouts pc
            JOIN courses c ON pc.course_id = c.id
            LEFT JOIN users u ON u.email = pc.email
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['instructor_id'])) {
            $query .= " AND c.instructor_id = ?";
            $params[] = $filters['instructor_id'];
        }
        if (!empty($filters['status'])) {
            $query .= " AND pc.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['course_id'])) {
            $query .= " AND pc.course_id = ?";
            $params[] = $filters['course_id'];
        }
        if (!empty($filters['gateway'])) {
            $query .= " AND pc.gateway = ?";
            $params[] = $filters['gateway'];
        }

        $query .= " ORDER BY pc.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Total completed revenue, optionally scoped to an instructor's own
     * courses. Only counts status='completed' — pending/failed/expired
     * orders never collected any money.
     */
    public function totalRevenue($instructorId = null) {
        $query = "
            SELECT pc.currency, SUM(pc.amount) AS total
            FROM pending_checkouts pc
            JOIN courses c ON pc.course_id = c.id
            WHERE pc.status = 'completed'
        ";
        $params = [];
        if ($instructorId) {
            $query .= " AND c.instructor_id = ?";
            $params[] = $instructorId;
        }
        $query .= " GROUP BY pc.currency";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count of completed orders, optionally scoped to an instructor.
     */
    public function completedOrderCount($instructorId = null) {
        $query = "
            SELECT COUNT(*) FROM pending_checkouts pc
            JOIN courses c ON pc.course_id = c.id
            WHERE pc.status = 'completed'
        ";
        $params = [];
        if ($instructorId) {
            $query .= " AND c.instructor_id = ?";
            $params[] = $instructorId;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return intval($stmt->fetchColumn());
    }

    /**
     * A single order by id, with the same course/buyer join as allOrders(),
     * optionally scoped to a specific instructor's own courses (returns
     * null if the order exists but belongs to a different instructor's
     * course — used to enforce access scoping, not just for display).
     */
    public function findOrder($id, $instructorId = null) {
        $query = "
            SELECT pc.*, c.title AS course_title, c.instructor_id,
                   u.name AS buyer_name, u.id AS buyer_user_id
            FROM pending_checkouts pc
            JOIN courses c ON pc.course_id = c.id
            LEFT JOIN users u ON u.email = pc.email
            WHERE pc.id = ?
        ";
        $params = [$id];
        if ($instructorId) {
            $query .= " AND c.instructor_id = ?";
            $params[] = $instructorId;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }
}
