<?php
$pageTitle = 'Checkout';
$isPublicVisitor = empty($user);
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_header.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}

if (!function_exists('getCourseCoverImage')) {
    function getCourseCoverImage($course) {
        if (!empty($course['thumbnail'])) {
            return htmlspecialchars($course['thumbnail']);
        }
        $searchKey = strtolower(($course['category_name'] ?? '') . ' ' . ($course['title'] ?? ''));
        if (preg_match('/nurs|clini|health|medic|pharm|anat|doctor|patient|care|pediatr|psych/i', $searchKey)) {
            return 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=80';
        } elseif (preg_match('/bio|chem|lab|scien|research|matter/i', $searchKey)) {
            return 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=800&q=80';
        } elseif (preg_match('/code|comput|tech|software|data|ai|cyber/i', $searchKey)) {
            return 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80';
        } elseif (preg_match('/busin|manag|market|finan|lead/i', $searchKey)) {
            return 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80';
        } elseif (preg_match('/law|legal|ethic|policy|gover/i', $searchKey)) {
            return 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=800&q=80';
        }
        return 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80';
    }
}

$formattedPrice = number_format(floatval($course['price'] ?? 0), 2);
$rawCurrency = $course['currency'] ?? 'USD';
$currencySymbol = ($rawCurrency === 'USD') ? '$' : htmlspecialchars($rawCurrency) . ' ';
$priceDisplay = $currencySymbol . $formattedPrice;
$coverImg = getCourseCoverImage($course);
?>

<!-- GOOGLE FONTS FOR CLINICAL & ACCESSIBLE TYPOGRAPHY -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================================
   CLINICAL TRUST & E-COMMERCE CHECKOUT STYLING SYSTEM
   Identical tokens pulled from catalog.php & details.php
   ============================================================ */
:root {
    --clinical-navy: #0a1d37;
    --clinical-navy-light: #112a4a;
    --clinical-teal: #0891b2;
    --clinical-teal-hover: #0e7490;
    --clinical-teal-light: #ecfeff;
    --clinical-blue: #2563eb;
    --clinical-blue-light: #eff6ff;
    --clinical-mint: #059669;
    --clinical-mint-light: #ecfdf5;
    --clinical-amber: #d97706;
    --clinical-amber-light: #fffbe8;
    --clinical-indigo: #4f46e5;
    --clinical-indigo-light: #eef2ff;
    --clinical-bg: #f8fafc;
    --clinical-card-bg: #ffffff;
    --clinical-border: #e2e8f0;
    --clinical-text-main: #0f172a;
    --clinical-text-muted: #475569;
    --clinical-radius: 12px;
}

body {
    background-color: var(--clinical-bg);
    color: var(--clinical-text-main);
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
}

.heading-font {
    font-family: 'Outfit', sans-serif;
    letter-spacing: -0.02em;
}

/* Accessible focus states */
a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible,
.gateway-card:focus-within {
    outline: 2px solid var(--clinical-teal) !important;
    outline-offset: 2px !important;
    box-shadow: 0 0 0 4px rgba(8, 145, 178, 0.2) !important;
}

/* Navigation Back Link */
.checkout-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--clinical-text-muted);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: color 0.2s ease, transform 0.2s ease;
}

.checkout-back-link:hover {
    color: var(--clinical-teal);
    transform: translateX(-3px);
}

.checkout-secure-pill {
    background: #ffffff;
    border: 1px solid var(--clinical-border);
    border-radius: 9999px;
    padding: 0.4rem 0.9rem;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--clinical-text-muted);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

/* Demo Mode Notice - REMOVED */

/* Card Sections */
.checkout-card {
    background: var(--clinical-card-bg);
    border: 1px solid var(--clinical-border);
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}

.checkout-card:hover {
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
}

.checkout-card-header {
    background: #ffffff;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--clinical-border);
    display: flex;
    align-items: center;
    gap: 0.9rem;
}

.step-number {
    width: 32px;
    height: 32px;
    background: var(--clinical-navy);
    color: #ffffff;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.step-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    color: var(--clinical-text-main);
    font-size: 1.1rem;
}

.step-subtitle {
    font-size: 0.83rem;
    color: var(--clinical-text-muted);
}

.checkout-card-body {
    padding: 1.5rem;
}

/* Input Fields */
.checkout-card-body .form-control {
    border-color: var(--clinical-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    color: var(--clinical-text-main);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.checkout-card-body .form-control:focus {
    border-color: var(--clinical-teal);
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15);
}

.checkout-card-body .input-group-text {
    border-color: var(--clinical-border);
    border-radius: 10px 0 0 10px;
    background-color: #f8fafc;
}

/* Logged In Box */
.logged-in-box {
    background-color: var(--clinical-bg);
    border: 1px solid var(--clinical-border);
}

.user-avatar-circle {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--clinical-teal) 0%, var(--clinical-navy) 100%);
    color: #ffffff;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 1.1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Payment Gateway Cards */
.gateway-options-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 0.85rem;
}

@media (min-width: 640px) {
    .gateway-options-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.gateway-card {
    position: relative;
    display: block;
    cursor: pointer;
    margin-bottom: 0;
}

.gateway-radio {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.gateway-card-content {
    background: #ffffff;
    border: 2px solid var(--clinical-border);
    border-radius: 14px;
    padding: 1.1rem;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.gateway-card:hover .gateway-card-content {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}

/* Checked Radio / Active State - subtle indicator handled by .radio-indicator */

.radio-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid var(--clinical-border);
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.gateway-card:has(.gateway-radio:checked) .radio-indicator,
.gateway-card.active-gateway .radio-indicator {
    border-color: var(--clinical-teal);
    background: var(--clinical-teal);
}

.radio-indicator::after {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ffffff;
    opacity: 0;
    transform: scale(0);
    transition: all 0.2s ease;
}

.gateway-card:has(.gateway-radio:checked) .radio-indicator::after,
.gateway-card.active-gateway .radio-indicator::after {
    opacity: 1;
    transform: scale(1);
}

.gateway-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.gateway-name {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    color: var(--clinical-text-main);
    font-size: 1rem;
    display: block;
    line-height: 1.2;
}

.gateway-tag {
    font-size: 0.72rem;
    color: var(--clinical-text-muted);
    font-weight: 500;
}

.gateway-desc {
    font-size: 0.8rem;
    color: var(--clinical-text-muted);
    line-height: 1.35;
    margin-top: 0.4rem;
}

/* Submit CTA */
.checkout-btn-primary {
    background: linear-gradient(135deg, var(--clinical-teal) 0%, var(--clinical-teal-hover) 100%);
    color: #ffffff;
    border: none;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 14px rgba(8, 145, 178, 0.3);
}

.checkout-btn-primary:hover {
    background: linear-gradient(135deg, var(--clinical-teal-hover) 0%, #075985 100%);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(8, 145, 178, 0.4);
}

.checkout-btn-primary:active {
    transform: translateY(0);
}

/* Order Summary Sidebar */
.order-summary-card {
    background: #ffffff;
    border: 1px solid var(--clinical-border);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.order-summary-header {
    background: #ffffff;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--clinical-border);
}

.order-summary-body {
    padding: 1.5rem;
}

.product-cover-thumb {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--clinical-border);
    flex-shrink: 0;
}

.product-title {
    font-size: 0.98rem;
    line-height: 1.3;
}

.summary-total-price {
    line-height: 1;
}

/* Utility Colors */
.text-teal { color: var(--clinical-teal) !important; }
.bg-teal-light { background-color: var(--clinical-teal-light) !important; }
.text-mint { color: var(--clinical-mint) !important; }
.bg-mint-light { background-color: var(--clinical-mint-light) !important; }
.border-mint-subtle { border-color: #a7f3d0 !important; }
.bg-blue-light { background-color: var(--clinical-blue-light) !important; }
.text-blue { color: var(--clinical-blue) !important; }
.bg-indigo-light { background-color: var(--clinical-indigo-light) !important; }
.text-indigo { color: var(--clinical-indigo) !important; }
.bg-amber-light { background-color: var(--clinical-amber-light) !important; }
.text-amber { color: var(--clinical-amber) !important; }
.bg-slate { background-color: #f8fafc !important; }

@media (prefers-reduced-motion: reduce) {
    .checkout-back-link,
    .checkout-card,
    .gateway-card-content,
    .checkout-btn-primary {
        transition: none !important;
        transform: none !important;
    }
}
</style>

<div class="checkout-wrapper py-4 py-lg-5">
    <div class="container max-w-6xl px-3 px-sm-4">
        
        <!-- Navigation Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="index.php?route=course/details&id=<?php echo $course['id']; ?>" class="checkout-back-link">
                <i data-lucide="arrow-left" size="18"></i>
                <span>Back to course details</span>
            </a>
            <div class="checkout-secure-pill d-none d-sm-flex align-items-center gap-2">
                <i data-lucide="shield-check" size="16" class="text-teal"></i>
                <span>256-bit SSL Encrypted Checkout</span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 rounded-4 p-3.5 mb-4 d-flex align-items-center gap-3 shadow-sm">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i data-lucide="alert-circle" size="22"></i>
                </div>
                <div>
                    <div class="fw-bold font-sans fs-6 mb-0.5">Checkout Notice</div>
                    <div class="small opacity-90 font-sans"><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column: Checkout Process Form -->
            <div class="col-lg-7 col-xl-8">
                <form method="POST" action="index.php?route=course/checkout_submit" id="checkoutForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">

                    <!-- STEP 1: CUSTOMER / ACCOUNT DETAILS -->
                    <div class="checkout-card mb-4">
                        <div class="checkout-card-header">
                            <div class="step-number">1</div>
                            <div>
                                <h5 class="step-title mb-0">Customer Account Details</h5>
                                <p class="step-subtitle mb-0">Enter your credentials to manage test banks and practice exams.</p>
                            </div>
                        </div>

                        <div class="checkout-card-body">
                            <?php if (empty($user)): ?>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label font-sans fw-semibold text-dark small mb-1">
                                            Full Name <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted"><i data-lucide="user" size="18"></i></span>
                                            <input type="text" name="name" class="form-control" placeholder="e.g. Jane Doe" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label font-sans fw-semibold text-dark small mb-1">
                                            Email Address <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted"><i data-lucide="mail" size="18"></i></span>
                                            <input type="email" name="email" class="form-control" placeholder="e.g. jane@example.com" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label font-sans fw-semibold text-dark small mb-1">
                                            Account Password <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted"><i data-lucide="lock" size="18"></i></span>
                                            <input type="password" name="password" class="form-control" placeholder="At least 8 characters" required minlength="8">
                                        </div>
                                        <div class="form-text text-muted mt-1.5 small d-flex align-items-center gap-1.5 font-sans">
                                            <i data-lucide="info" size="14" class="text-teal"></i>
                                            Creates your student login credential for instant exam portal access.
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 mt-3 border">
                                    <span class="small text-muted font-sans">
                                        Already registered? <a href="index.php?route=login" class="fw-bold text-teal text-decoration-none">Log in to your account</a> before completing enrollment.
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="logged-in-box p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="user-avatar-circle">
                                            <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="text-muted small font-sans">Enrolling as</div>
                                            <div class="fw-bold text-dark font-sans"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <div class="text-muted small font-sans"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                    <span class="badge bg-mint-light text-mint border border-mint-subtle px-3 py-2 rounded-pill fw-semibold font-sans">
                                        <i data-lucide="check-circle" size="14" class="me-1"></i> Logged In
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- STEP 2: SELECT PAYMENT GATEWAY -->
                    <div class="checkout-card mb-4">
                        <div class="checkout-card-header">
                            <div class="step-number">2</div>
                            <div>
                                <h5 class="step-title mb-0">Select Payment Method</h5>
                                <p class="step-subtitle mb-0">Choose your preferred payment gateway to proceed securely.</p>
                            </div>
                        </div>

                        <div class="checkout-card-body">
                            <div class="gateway-options-grid">
                                
                                <!-- Stripe -->
                                <label class="gateway-card" for="gatewayStripe">
                                    <input type="radio" name="gateway" id="gatewayStripe" value="stripe" checked required class="gateway-radio">
                                    <div class="gateway-card-content">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="gateway-icon-box bg-indigo-light text-indigo">
                                                    <i data-lucide="credit-card" size="20"></i>
                                                </div>
                                                <div>
                                                    <span class="gateway-name">Stripe</span>
                                                    <span class="gateway-tag font-sans">Cards & Apple Pay</span>
                                                </div>
                                            </div>
                                            <div class="radio-indicator"></div>
                                        </div>
                                        <div class="gateway-desc font-sans">Visa, Mastercard, American Express, Apple Pay</div>
                                    </div>
                                </label>

                                <!-- PayPal -->
                                <label class="gateway-card" for="gatewayPayPal">
                                    <input type="radio" name="gateway" id="gatewayPayPal" value="paypal" required class="gateway-radio">
                                    <div class="gateway-card-content">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="gateway-icon-box bg-blue-light text-blue">
                                                    <i data-lucide="wallet" size="20"></i>
                                                </div>
                                                <div>
                                                    <span class="gateway-name">PayPal</span>
                                                    <span class="gateway-tag font-sans">PayPal & Credit</span>
                                                </div>
                                            </div>
                                            <div class="radio-indicator"></div>
                                        </div>
                                        <div class="gateway-desc font-sans">Pay with your PayPal balance, linked cards, or Pay in 4</div>
                                    </div>
                                </label>

                                <!-- Paystack -->
                                <label class="gateway-card" for="gatewayPaystack">
                                    <input type="radio" name="gateway" id="gatewayPaystack" value="paystack" required class="gateway-radio">
                                    <div class="gateway-card-content">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="gateway-icon-box bg-teal-light text-teal">
                                                    <i data-lucide="arrow-right-left" size="20"></i>
                                                </div>
                                                <div>
                                                    <span class="gateway-name">Paystack</span>
                                                    <span class="gateway-tag font-sans">Cards & Transfer</span>
                                                </div>
                                            </div>
                                            <div class="radio-indicator"></div>
                                        </div>
                                        <div class="gateway-desc font-sans">Cards, Bank Transfer, USSD, and Mobile Money</div>
                                    </div>
                                </label>

                                <!-- Flutterwave -->
                                <label class="gateway-card" for="gatewayFlutterwave">
                                    <input type="radio" name="gateway" id="gatewayFlutterwave" value="flutterwave" required class="gateway-radio">
                                    <div class="gateway-card-content">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="gateway-icon-box bg-amber-light text-amber">
                                                    <i data-lucide="globe" size="20"></i>
                                                </div>
                                                <div>
                                                    <span class="gateway-name">Flutterwave</span>
                                                    <span class="gateway-tag font-sans">Global & African</span>
                                                </div>
                                            </div>
                                            <div class="radio-indicator"></div>
                                        </div>
                                        <div class="gateway-desc font-sans">Multi-currency cards, Bank Accounts, Mobile Money</div>
                                    </div>
                                </label>

                            </div>
                        </div>
                    </div>

                    <!-- PRIMARY SUBMIT BUTTON -->
                    <div class="checkout-submit-wrapper d-flex flex-column align-items-end">
                        <button type="submit" class="btn checkout-btn-primary py-3.5 px-5 rounded-3 fw-bold font-sans fs-5 shadow-sm d-inline-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="lock" size="20"></i>
                            <span>Complete Purchase</span>
                        </button>

                        <div class="checkout-guarantee-row mt-3 text-end text-muted small font-sans d-flex align-items-center justify-content-end gap-3 flex-wrap w-100">
                            <span class="d-inline-flex align-items-center gap-1.5"><i data-lucide="shield-check" size="15" class="text-mint"></i> 256-bit Encrypted</span>
                            <span class="d-inline-flex align-items-center gap-1.5"><i data-lucide="zap" size="15" class="text-amber"></i> Instant Portal Access</span>
                            <span class="d-inline-flex align-items-center gap-1.5"><i data-lucide="refresh-cw" size="15" class="text-blue"></i> Lifetime Updates</span>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Right Column: Order Summary Sidebar -->
            <div class="col-lg-5 col-xl-4">
                <div class="order-summary-card sticky-top" style="top: 2rem;">
                    <div class="order-summary-header">
                        <h6 class="fw-bold text-dark font-sans mb-0 d-flex align-items-center gap-2">
                            <i data-lucide="shopping-bag" size="18" class="text-teal"></i>
                            Order Summary
                        </h6>
                    </div>

                    <div class="order-summary-body">
                        <!-- Product Item Row -->
                        <div class="product-item-row d-flex gap-3 pb-3 mb-3 border-bottom">
                            <img src="<?php echo $coverImg; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="product-cover-thumb rounded-3">
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="badge bg-teal-light text-teal rounded-pill px-2.5 py-1 font-sans fw-semibold mb-1" style="font-size: 0.72rem;">
                                    <?php echo htmlspecialchars($course['category_name'] ?? 'Certification Prep'); ?>
                                </span>
                                <h6 class="product-title fw-bold text-dark mb-1 font-sans text-truncate" title="<?php echo htmlspecialchars($course['title']); ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </h6>
                                <div class="text-muted small font-sans">Full Access Test Bank & Exams</div>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="price-breakdown-list mb-3 font-sans">
                            <div class="d-flex justify-content-between py-1 text-muted small">
                                <span>Course Price</span>
                                <span class="fw-semibold text-dark"><?php echo $priceDisplay; ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-muted small">
                                <span>Platform Processing</span>
                                <span class="text-mint fw-semibold">FREE</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-muted small">
                                <span>Portal Access Fee</span>
                                <span class="text-mint fw-semibold">Included</span>
                            </div>
                        </div>

                        <!-- Total Row -->
                        <div class="summary-total-row p-3 rounded-3 bg-light d-flex align-items-center justify-content-between mb-3 border">
                            <div>
                                <span class="fw-bold text-dark font-sans d-block">Total Due</span>
                                <span class="text-muted small font-sans">Includes all exams & rationales</span>
                            </div>
                            <span class="summary-total-price heading-font text-teal fw-bold fs-3">
                                <?php echo $priceDisplay; ?>
                            </span>
                        </div>

                        <!-- Included Features -->
                        <div class="included-benefits p-3 rounded-3 bg-slate border-0">
                            <div class="fw-bold text-dark small mb-2 font-sans d-flex align-items-center gap-1.5">
                                <i data-lucide="check-circle-2" size="16" class="text-mint"></i>
                                What's included with your purchase:
                            </div>
                            <ul class="list-unstyled mb-0 small text-muted font-sans d-flex flex-column gap-1.5">
                                <li class="d-flex align-items-center gap-2">
                                    <i data-lucide="check" size="14" class="text-teal"></i>
                                    NGN case studies & practice question banks
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <i data-lucide="check" size="14" class="text-teal"></i>
                                    Timed exam simulation & score tracking
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <i data-lucide="check" size="14" class="text-teal"></i>
                                    Detailed clinical rationales for every item
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <i data-lucide="check" size="14" class="text-teal"></i>
                                    Verifiable digital certificate of completion
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gatewayCards = document.querySelectorAll('.gateway-card');
    const gatewayRadios = document.querySelectorAll('.gateway-radio');

    function updateActiveGateway() {
        gatewayCards.forEach(card => {
            const radio = card.querySelector('.gateway-radio');
            if (radio && radio.checked) {
                card.classList.add('active-gateway');
            } else {
                card.classList.remove('active-gateway');
            }
        });
    }

    gatewayRadios.forEach(radio => {
        radio.addEventListener('change', updateActiveGateway);
    });

    updateActiveGateway();
});
</script>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}
?>
