<?php
$pageTitle = 'Billing & Orders';
include __DIR__ . '/../../../admin/views/layout_header.php';

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

<h2 class="display-font fw-bold text-dark mb-1">Billing & Orders</h2>
<p class="text-muted mb-4">Your purchase history for course enrollments.</p>

<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item"><a class="nav-link" href="index.php?route=account/profile">Profile</a></li>
    <li class="nav-item"><a class="nav-link" href="index.php?route=account/settings">Settings</a></li>
    <li class="nav-item"><a class="nav-link" href="index.php?route=account/preferences">Preferences</a></li>
    <li class="nav-item"><a class="nav-link active" href="index.php?route=account/billing">Billing &amp; Orders</a></li>
</ul>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i data-lucide="receipt" class="text-muted d-block mx-auto mb-3" size="40"></i>
                <h5 class="fw-semibold text-dark">No orders yet</h5>
                <p class="text-muted mb-3">Your purchases will appear here once you enroll in a paid course.</p>
                <a href="index.php?route=courses" class="btn btn-primary btn-sm">Browse Courses</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Course</th>
                            <th>Gateway</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="pe-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-3 small fw-semibold"><?php echo htmlspecialchars($order['course_title']); ?></td>
                                <td class="small"><?php echo htmlspecialchars($gatewayLabels[$order['gateway']] ?? $order['gateway']); ?></td>
                                <td class="small fw-semibold"><?php echo htmlspecialchars($order['currency']); ?> <?php echo number_format($order['amount'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $statusBadges[$order['status']] ?? 'bg-light text-dark'; ?> fw-normal">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="pe-3 text-muted small"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../../admin/views/layout_footer.php'; ?>
