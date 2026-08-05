<?php
$pageTitle = 'Order Confirmed';
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="text-center mb-4">
            <div class="mb-3 d-inline-flex p-3 rounded-circle bg-success-subtle text-success">
                <i data-lucide="check-circle" size="36"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Payment Successful</h3>
            <p class="text-muted">You're enrolled in <strong><?php echo htmlspecialchars($course['title']); ?></strong>.</p>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold">Order Details</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Order Reference</span>
                    <span class="fw-semibold small"><?php echo htmlspecialchars($orderReference); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Course</span>
                    <span class="fw-semibold small"><?php echo htmlspecialchars($course['title']); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Payment Method</span>
                    <span class="fw-semibold small text-capitalize"><?php echo htmlspecialchars($gateway); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Date</span>
                    <span class="fw-semibold small"><?php echo date('F j, Y \a\t g:i A'); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 pt-3">
                    <span class="fw-bold">Total Paid</span>
                    <span class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($currency); ?> <?php echo number_format($amount, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="index.php?route=student/course/view&id=<?php echo $course['id']; ?>" class="btn btn-primary py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
                Go to Course <i data-lucide="arrow-right" size="18"></i>
            </a>
            <a href="index.php?route=student/dashboard" class="btn btn-outline-secondary py-2 fw-semibold">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../admin/views/layout_footer.php'; ?>
