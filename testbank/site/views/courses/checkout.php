<?php
$pageTitle = 'Checkout';
$isPublicVisitor = empty($user);
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_header.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}
?>

<div class="row justify-content-center py-4">
    <div class="col-lg-7">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2 shadow-sm">
                <i data-lucide="alert-circle" size="20"></i>
                <div class="small fw-semibold"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="fw-bold text-dark mb-0">Enroll in <?php echo htmlspecialchars($course['title']); ?></h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">
                        <?php echo htmlspecialchars($course['currency']); ?> <?php echo number_format($course['price'], 2); ?>
                    </span>
                </div>
                <p class="text-muted small mb-0">Complete the details below to enroll and pay in one step.</p>
            </div>
        </div>

        <form method="POST" action="index.php?route=course/checkout_submit" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">

            <?php if (empty($user)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i data-lucide="user" size="18" class="text-primary"></i>
                        Student Account Details
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg fs-6" placeholder="e.g. Jane Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-lg fs-6" placeholder="e.g. jane@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-lg fs-6" placeholder="At least 8 characters" required minlength="8">
                        <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">This will create your student portal login credential.</small>
                    </div>
                    <div class="p-3 bg-light rounded-3 mt-3 border">
                        <span class="text-muted small">
                            Already have an account? <a href="index.php?route=login" class="fw-semibold text-decoration-none">Log in here</a> before completing enrollment.
                        </span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Enrolling as</div>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</div>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-semibold">Logged In</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i data-lucide="credit-card" size="18" class="text-primary"></i>
                        Select Payment Gateway
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3">
                        <div class="form-check p-3 border rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <input class="form-check-input ms-0 me-3" type="radio" name="gateway" id="gatewayStripe" value="stripe" checked>
                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="gatewayStripe">
                                    Stripe
                                </label>
                                <div class="text-muted small ms-4 ps-1">Credit / Debit Cards (Visa, Mastercard, Amex)</div>
                            </div>
                            <i data-lucide="shield-check" size="20" class="text-muted"></i>
                        </div>

                        <div class="form-check p-3 border rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <input class="form-check-input ms-0 me-3" type="radio" name="gateway" id="gatewayPayPal" value="paypal">
                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="gatewayPayPal">
                                    PayPal
                                </label>
                                <div class="text-muted small ms-4 ps-1">Pay with your PayPal account or linked cards</div>
                            </div>
                            <i data-lucide="wallet" size="20" class="text-muted"></i>
                        </div>

                        <div class="form-check p-3 border rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <input class="form-check-input ms-0 me-3" type="radio" name="gateway" id="gatewayPaystack" value="paystack">
                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="gatewayPaystack">
                                    Paystack
                                </label>
                                <div class="text-muted small ms-4 ps-1">Cards, Bank Transfer, Mobile Money</div>
                            </div>
                            <i data-lucide="arrow-right-left" size="20" class="text-muted"></i>
                        </div>

                        <div class="form-check p-3 border rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <input class="form-check-input ms-0 me-3" type="radio" name="gateway" id="gatewayFlutterwave" value="flutterwave">
                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="gatewayFlutterwave">
                                    Flutterwave
                                </label>
                                <div class="text-muted small ms-4 ps-1">Global & African Payment Options</div>
                            </div>
                            <i data-lucide="globe" size="20" class="text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full py-3 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                <i data-lucide="lock" size="18"></i>
                Proceed to Secure Payment
            </button>

            <div class="text-center mt-3 text-muted small d-flex align-items-center justify-content-center gap-1">
                <i data-lucide="shield" size="14"></i> 256-bit SSL Encrypted Payment
            </div>
        </form>

    </div>
</div>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_footer.php';
}
?>
