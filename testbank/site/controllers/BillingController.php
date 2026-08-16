<?php
/**
 * Billing Controller (Student/Site)
 * Shows a logged-in user their own purchase/order history.
 */

require_once __DIR__ . '/../../admin/models/Order.php';
require_once __DIR__ . '/../../includes/Auth.php';

class BillingController {
    private $orderModel;

    public function __construct() {
        Auth::requireLogin();
        $this->orderModel = new Order();
    }

    public function handleRequest($action = 'index') {
        $user = Auth::getUser();
        $orders = $this->orderModel->forStudent($user['email']);
        include __DIR__ . '/../views/account/billing.php';
    }
}
