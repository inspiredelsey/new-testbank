<?php
/**
 * Order Controller
 * Admin/instructor-facing order (payment) history and revenue visibility.
 */

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../models/Order.php';

class OrderController {
    private $orderModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->orderModel = new Order();
    }

    public function handleRequest($action = 'list') {
        switch ($action) {
            case 'view':
                $this->handleView();
                break;
            case 'list':
            default:
                $this->handleList();
                break;
        }
    }

    private function handleList() {
        $user = Auth::getUser();
        // Instructors only see orders for their own courses; admins see everything.
        $instructorScope = ($user['role'] === 'instructor') ? $user['id'] : null;

        $filters = [
            'instructor_id' => $instructorScope,
            'status' => $_GET['status'] ?? null,
            'course_id' => $_GET['course_id'] ?? null,
            'gateway' => $_GET['gateway'] ?? null,
        ];

        $orders = $this->orderModel->allOrders($filters);
        $revenueByCurrency = $this->orderModel->totalRevenue($instructorScope);
        $completedCount = $this->orderModel->completedOrderCount($instructorScope);

        include __DIR__ . '/../views/orders/list.php';
    }

    private function handleView() {
        $user = Auth::getUser();
        $instructorScope = ($user['role'] === 'instructor') ? $user['id'] : null;
        $orderId = intval($_GET['id'] ?? 0);

        $order = $this->orderModel->findOrder($orderId, $instructorScope);
        if (!$order) {
            // Either the order genuinely doesn't exist, or it belongs to a
            // course this instructor doesn't own — same message either way,
            // so we don't leak which orders exist to someone probing IDs.
            http_response_code(404);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2>Order Not Found</h2>";
            echo "<p><a href='index.php?route=admin/orders'>Back to Orders</a></p>";
            echo "</div>";
            return;
        }

        include __DIR__ . '/../views/orders/view.php';
    }
}
