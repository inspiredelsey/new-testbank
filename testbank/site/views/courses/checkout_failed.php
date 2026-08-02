<?php
$pageTitle = 'Payment Unsuccessful';
$isPublicVisitor = empty($user);
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_header.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}
?>

<div class="row justify-content-center py-5">
    <div class="col-lg-6 text-center">
        <div class="card border-0 shadow-sm p-4 rounded-4">
            <div class="card-body">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i data-lucide="x-circle" size="32"></i>
                </div>
                
                <h4 class="fw-bold text-dark mb-2">Payment Not Completed</h4>
                
                <p class="text-muted mb-4">
                    <?php echo htmlspecialchars($error ?? 'Your payment attempt could not be verified or was cancelled. No charges were made.'); ?>
                </p>

                <div class="d-flex align-items-center justify-content-center gap-3">
                    <a href="index.php?route=course/checkout&id=<?php echo intval($courseId ?? 0); ?>" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 d-inline-flex align-items-center gap-2">
                        <i data-lucide="refresh-cw" size="16"></i> Try Again
                    </a>
                    <a href="index.php?route=courses" class="btn btn-outline-secondary px-4 py-2 fw-semibold rounded-3 d-inline-flex align-items-center gap-2">
                        <i data-lucide="book-open" size="16"></i> View Catalog
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_footer.php';
}
?>
