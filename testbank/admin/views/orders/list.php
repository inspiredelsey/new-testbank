<?php
/**
 * Orders List View - Test Bank LMS
 */
$pageTitle = 'Orders';
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

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h2 class="display-font fw-bold text-dark mb-1">Orders</h2>
        <p class="text-muted mb-0">Payment history for course enrollments.</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-success-subtle text-success"><i data-lucide="receipt" size="22"></i></div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?php echo $completedCount; ?></div>
                    <div class="text-muted small">Completed Orders</div>
                </div>
            </div>
        </div>
    </div>
    <?php if (empty($revenueByCurrency)): ?>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="p-2 rounded-3 bg-primary-subtle text-primary"><i data-lucide="banknote" size="22"></i></div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">—</div>
                        <div class="text-muted small">Total Revenue</div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($revenueByCurrency as $rev): ?>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="p-2 rounded-3 bg-primary-subtle text-primary"><i data-lucide="banknote" size="22"></i></div>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?php echo htmlspecialchars($rev['currency']); ?> <?php echo number_format($rev['total'], 2); ?></div>
                        <div class="text-muted small">Total Revenue</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/orders">
            <div class="col-md-3">
                <select name="status" class="form-select font-sans">
                    <option value="">All Statuses</option>
                    <?php foreach (['completed', 'pending', 'failed', 'expired'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo (($_GET['status'] ?? '') === $s) ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="gateway" class="form-select font-sans">
                    <option value="">All Gateways</option>
                    <?php foreach ($gatewayLabels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo (($_GET['gateway'] ?? '') === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                    <i data-lucide="filter" size="16"></i> Filter
                </button>
                <a href="index.php?route=admin/orders" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i data-lucide="receipt" class="text-muted d-block mx-auto mb-3" size="40"></i>
                <h5 class="fw-semibold text-dark">No orders found</h5>
                <p class="text-muted mb-0">Orders will appear here once students start enrolling and paying for courses.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Buyer</th>
                            <th>Course</th>
                            <th>Gateway</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold small"><?php echo htmlspecialchars($order['buyer_name'] ?? $order['name'] ?? '—'); ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($order['email']); ?></div>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($order['course_title']); ?></td>
                                <td class="small"><?php echo htmlspecialchars($gatewayLabels[$order['gateway']] ?? $order['gateway']); ?></td>
                                <td class="small fw-semibold"><?php echo htmlspecialchars($order['currency']); ?> <?php echo number_format($order['amount'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $statusBadges[$order['status']] ?? 'bg-light text-dark'; ?> fw-normal">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                <td class="text-end pe-3">
                                    <a href="index.php?route=admin/orders&action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
