<?php
/**
 * Order Detail View - Test Bank LMS
 */
$pageTitle = 'Order #' . $order['id'];
include __DIR__ . '/../layout_header.php';

$statusBadges = [
    'completed' => 'bg-success-subtle text-success',
    'pending' => 'bg-warning-subtle text-warning',
    'failed' => 'bg-danger-subtle text-danger',
    'expired' => 'bg-secondary-subtle text-secondary',
];
$gatewayLabels = [
    'stripe' => 'Stripe',
    'paypal' => 'PayPal',
    'paystack' => 'Paystack',
    'flutterwave' => 'Flutterwave',
];
?>

<a href="index.php?route=admin/orders" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3">
    <i data-lucide="arrow-left" size="14"></i> Back to Orders
</a>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h2 class="display-font fw-bold text-dark mb-1">Order #<?php echo $order['id']; ?></h2>
        <p class="text-muted mb-0">Placed <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
    </div>
    <span class="badge <?php echo $statusBadges[$order['status']] ?? 'bg-light text-dark'; ?> fs-6 fw-normal px-3 py-2">
        <?php echo ucfirst($order['status']); ?>
    </span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold">Transaction Details</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Order Reference</span>
                    <span class="fw-semibold small"><?php echo htmlspecialchars(strtoupper(substr($order['token'], 0, 10))); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Payment Gateway</span>
                    <span class="fw-semibold small"><?php echo htmlspecialchars($gatewayLabels[$order['gateway']] ?? $order['gateway']); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Gateway Transaction ID</span>
                    <span class="fw-semibold small font-monospace"><?php echo htmlspecialchars($order['gateway_session_id'] ?? '—'); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Amount</span>
                    <span class="fw-semibold small"><?php echo htmlspecialchars($order['currency']); ?> <?php echo number_format($order['amount'], 2); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Created</span>
                    <span class="fw-semibold small"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted small">Completed</span>
                    <span class="fw-semibold small"><?php echo $order['completed_at'] ? date('M j, Y g:i A', strtotime($order['completed_at'])) : '—'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold">Buyer</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="text-muted small">Name</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($order['buyer_name'] ?? $order['name'] ?? '—'); ?></div>
                </div>
                <div class="mb-2">
                    <div class="text-muted small">Email</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($order['email']); ?></div>
                </div>
                <?php if ($order['buyer_user_id']): ?>
                <div>
                    <a href="index.php?route=admin/users&action=edit&id=<?php echo $order['buyer_user_id']; ?>" class="small text-decoration-none">
                        View account <i data-lucide="arrow-right" size="12"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold">Course</h6>
            </div>
            <div class="card-body">
                <div class="fw-semibold mb-2"><?php echo htmlspecialchars($order['course_title']); ?></div>
                <a href="index.php?route=admin/courses&action=edit&id=<?php echo $order['course_id']; ?>" class="small text-decoration-none">
                    View course <i data-lucide="arrow-right" size="12"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
