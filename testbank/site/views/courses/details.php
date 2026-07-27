<?php
$pageTitle = htmlspecialchars($course['title'] ?? 'Course Details');
$isPublicVisitor = empty($user);
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_header.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}

$typeIcons = [
    'document' => 'file-text',
    'link' => 'link',
    'quiz' => 'clipboard-check',
];

/**
 * Return a high-quality relevant Unsplash cover image based on course title / category / thumbnail
 * Reused from catalog.php for visual consistency
 */
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
    
    // Default medical/nursing study reference cover
    return 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80';
}

/**
 * Return a Lucide icon name matching a category name
 */
function getCategoryIcon($categoryName) {
    $name = strtolower($categoryName ?? '');
    if (preg_match('/code|comput|software|tech|it|informatic/i', $name)) return 'laptop';
    if (preg_match('/busin|manag|finan|market|admin/i', $name)) return 'briefcase';
    if (preg_match('/scien|chem|bio|pharm/i', $name)) return 'flask-conical';
    if (preg_match('/health|medic|nurs|clini|anat|care|pediatr|psych/i', $name)) return 'activity';
    if (preg_match('/math|stat|calc/i', $name)) return 'calculator';
    if (preg_match('/law|legal|ethic|policy/i', $name)) return 'shield-check';
    return 'book-open';
}

$coverImg = getCourseCoverImage($course);
?>

<!-- GOOGLE FONTS FOR CLINICAL & ACCESSIBLE TYPOGRAPHY -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================================
   CLINICAL TRUST & ACCESSIBLE COLOR SYSTEM (UNIFIED WITH CATALOG)
   ============================================================ */
:root {
    --clinical-navy: #0a1d37;
    --clinical-navy-light: #112a4a;
    --clinical-teal: #0891b2;
    --clinical-teal-hover: #0e7490;
    --clinical-teal-light: #ecfeff;
    --clinical-blue: #2563eb;
    --clinical-mint: #059669;
    --clinical-mint-light: #ecfdf5;
    --clinical-amber: #d97706;
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

/* ============================================================
   ACCESSIBLE FOCUS STATES
   ============================================================ */
a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible,
.details-buy-box:focus-within {
    outline: 2px solid var(--clinical-teal) !important;
    outline-offset: 3px !important;
    box-shadow: 0 0 0 4px rgba(8, 145, 178, 0.25) !important;
}

/* ============================================================
   UDEMY-STYLE HERO BANNER
   ============================================================ */
.details-hero-container {
    background: linear-gradient(135deg, var(--clinical-navy) 0%, var(--clinical-navy-light) 65%, #0d3b66 100%);
    border-radius: var(--clinical-radius);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

.details-hero-container::before {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    background: radial-gradient(circle at 85% 15%, rgba(8, 145, 178, 0.22) 0%, transparent 60%);
    pointer-events: none;
}

.details-hero-cover-wrap {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.15);
    background-color: var(--clinical-navy-light);
    aspect-ratio: 16/10;
}

.details-hero-cover-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.back-link-btn {
    color: rgba(224, 242, 254, 0.85);
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.15s ease;
}

.back-link-btn:hover {
    color: #ffffff;
}

.category-badge-dark {
    background: rgba(8, 145, 178, 0.25);
    backdrop-filter: blur(8px);
    color: #67e8f9;
    font-size: 0.8125rem;
    font-weight: 700;
    padding: 0.35rem 0.85rem;
    border-radius: 6px;
    border: 1px solid rgba(8, 145, 178, 0.4);
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

/* ============================================================
   STATS & META BAR
   ============================================================ */
.stat-box-card {
    background: #ffffff;
    border: 1px solid var(--clinical-border);
    border-radius: 10px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    height: 100%;
}

.stat-icon-wrapper {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background-color: var(--clinical-teal-light);
    color: var(--clinical-teal);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-val {
    font-family: 'Outfit', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--clinical-navy);
    line-height: 1.2;
}

.stat-lbl {
    font-size: 0.78rem;
    color: var(--clinical-text-muted);
    font-weight: 600;
}

/* ============================================================
   STICKY ENROLLMENT BUY BOX (UDEMY STYLE)
   ============================================================ */
.details-buy-box {
    background: #ffffff;
    border: 1px solid var(--clinical-border);
    border-radius: var(--clinical-radius);
    box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08);
    position: sticky;
    top: 5rem;
    overflow: hidden;
}

.buy-box-header {
    background: var(--clinical-navy);
    color: #ffffff;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.buy-box-body {
    padding: 1.5rem;
}

.btn-cta-teal {
    background-color: var(--clinical-teal);
    color: #ffffff;
    font-weight: 700;
    padding: 0.8rem 1.25rem;
    border-radius: 8px;
    border: 1px solid var(--clinical-teal);
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-cta-teal:hover {
    background-color: var(--clinical-teal-hover);
    color: #ffffff;
    border-color: var(--clinical-teal-hover);
}

.btn-cta-mint {
    background-color: var(--clinical-mint);
    color: #ffffff;
    font-weight: 700;
    padding: 0.8rem 1.25rem;
    border-radius: 8px;
    border: 1px solid var(--clinical-mint);
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-cta-mint:hover {
    background-color: #047857;
    color: #ffffff;
}

.btn-cta-outline {
    background-color: transparent;
    color: var(--clinical-navy);
    border: 1.5px solid var(--clinical-border);
    font-weight: 700;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-cta-outline:hover {
    border-color: var(--clinical-navy);
    background-color: var(--clinical-bg);
}

.buy-box-feature-item {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-size: 0.85rem;
    color: var(--clinical-text-muted);
    padding: 0.4rem 0;
}

/* ============================================================
   CURRICULUM / SYLLABUS SECTION
   ============================================================ */
.curriculum-card {
    background: #ffffff;
    border: 1px solid var(--clinical-border);
    border-radius: var(--clinical-radius);
    overflow: hidden;
}

.curriculum-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--clinical-border);
    transition: background-color 0.15s ease;
}

.curriculum-item:last-child {
    border-bottom: none;
}

.curriculum-item:hover {
    background-color: var(--clinical-bg);
}

.curriculum-item-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background-color: var(--clinical-teal-light);
    color: var(--clinical-teal);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* ============================================================
   REDUCED MOTION SUPPORT
   ============================================================ */
@media (prefers-reduced-motion: reduce) {
    .btn-cta-teal,
    .btn-cta-mint,
    .btn-cta-outline,
    .curriculum-item {
        transition: none !important;
        transform: none !important;
    }
}
</style>

<?php if ($isPublicVisitor): ?><div class="container my-4"><?php endif; ?>

<!-- BREADCRUMB / BACK TO CATALOG BUTTON -->
<div class="mb-3">
    <a href="index.php?route=courses" class="d-inline-flex align-items-center gap-1.5 text-muted small fw-semibold text-decoration-none hover-primary py-1">
        <i data-lucide="arrow-left" size="16"></i>
        <span>Back to Course Catalog</span>
    </a>
</div>

<!-- UDEMY-STYLE HERO LANDING BANNER -->
<section class="details-hero-container p-4 p-md-5 mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <?php if (!empty($course['category_name'])): ?>
                <div class="mb-3">
                    <span class="category-badge-dark">
                        <i data-lucide="<?php echo getCategoryIcon($course['category_name']); ?>" size="14"></i>
                        <?php echo htmlspecialchars($course['category_name']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <h1 class="heading-font display-6 fw-bold text-white mb-3" style="line-height: 1.2;">
                <?php echo htmlspecialchars($course['title']); ?>
            </h1>

            <div class="d-flex flex-wrap align-items-center gap-3 pt-2 text-white text-opacity-85 small">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-info bg-opacity-20 text-info fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
                        <?php echo strtoupper(substr($course['instructor_name'] ?? 'I', 0, 1)); ?>
                    </div>
                    <span>Instructor: <strong class="text-white"><?php echo htmlspecialchars($course['instructor_name'] ?? 'Board-Certified Faculty'); ?></strong></span>
                </div>
                <div class="vr bg-white opacity-25 d-none d-sm-block" style="height: 16px;"></div>
                <div class="d-flex align-items-center gap-1.5">
                    <i data-lucide="award" size="16" class="text-warning"></i>
                    <span>Passing Standard: <strong class="text-white"><?php echo htmlspecialchars($course['pass_percentage']); ?>%</strong></span>
                </div>
            </div>
        </div>

        <!-- HERO COVER ART -->
        <div class="col-lg-5">
            <div class="details-hero-cover-wrap">
                <img src="<?php echo $coverImg; ?>" 
                     alt="Cover art for <?php echo htmlspecialchars($course['title']); ?>" 
                     class="details-hero-cover-img"
                     onerror="this.src='https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80';">
            </div>
        </div>
    </div>
</section>

<!-- MAIN CONTENT GRID (LEFT: OVERVIEW & CURRICULUM, RIGHT: STICKY BUY BOX) -->
<div class="row g-4">
    <!-- LEFT CONTENT COLUMN -->
    <div class="col-lg-8">
        <!-- STATS / META ROW -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-sm-3">
                <div class="stat-box-card">
                    <div class="stat-icon-wrapper">
                        <i data-lucide="users" size="20"></i>
                    </div>
                    <div>
                        <div class="stat-val"><?php echo $enrolledCount; ?></div>
                        <div class="stat-lbl">Candidates</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="stat-box-card">
                    <div class="stat-icon-wrapper">
                        <i data-lucide="clipboard-check" size="20"></i>
                    </div>
                    <div>
                        <div class="stat-val"><?php echo $examCount; ?></div>
                        <div class="stat-lbl">Item Banks</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="stat-box-card">
                    <div class="stat-icon-wrapper">
                        <i data-lucide="target" size="20"></i>
                    </div>
                    <div>
                        <div class="stat-val"><?php echo htmlspecialchars($course['pass_percentage']); ?>%</div>
                        <div class="stat-lbl">Target Score</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="stat-box-card">
                    <div class="stat-icon-wrapper">
                        <i data-lucide="shield-check" size="20"></i>
                    </div>
                    <div>
                        <div class="stat-val">NGN</div>
                        <div class="stat-lbl">Format Ready</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COURSE OVERVIEW / DESCRIPTION CARD -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" style="background: #ffffff; border: 1px solid var(--clinical-border) !important;">
            <div class="card-body p-4">
                <h2 class="heading-font fw-bold fs-4 text-dark mb-3">About This Course</h2>
                <div class="text-secondary fs-6" style="line-height: 1.7;">
                    <?php 
                        $rawDesc = $course['description'] ?: 'This course provides a structured clinical review and practice item bank designed to prepare candidates for licensure examination success. Review key concepts, attempt simulated practice exams, and analyze detailed explanations for all answer choices.';
                        $formattedDesc = preg_replace('/([a-z])([A-Z])/', '$1 $2', $rawDesc);
                        echo nl2br(htmlspecialchars($formattedDesc)); 
                    ?>
                </div>

                <hr class="my-4" style="border-color: var(--clinical-border);">

                <h3 class="heading-font fw-bold fs-5 text-dark mb-3">What You Will Practice</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-2.5">
                            <i data-lucide="check-circle-2" size="18" class="text-success mt-1 flex-shrink-0"></i>
                            <span class="small text-muted">Next Generation NCLEX (NGN) clinical judgment case studies.</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-2.5">
                            <i data-lucide="check-circle-2" size="18" class="text-success mt-1 flex-shrink-0"></i>
                            <span class="small text-muted">Detailed rationales for correct and incorrect response options.</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-2.5">
                            <i data-lucide="check-circle-2" size="18" class="text-success mt-1 flex-shrink-0"></i>
                            <span class="small text-muted">Automated diagnostic performance breakdown by clinical area.</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-2.5">
                            <i data-lucide="check-circle-2" size="18" class="text-success mt-1 flex-shrink-0"></i>
                            <span class="small text-muted">Unlimited retakes and timed examination simulation modes.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CURRICULUM / SYLLABUS SECTION -->
        <?php if (!empty($pathItems)): ?>
        <div class="curriculum-card shadow-sm mb-4">
            <div class="bg-white p-4 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="heading-font fw-bold text-dark fs-4 mb-1">Course Curriculum & Learning Modules</h2>
                    <p class="text-muted small mb-0"><?php echo count($pathItems); ?> learning materials and test items included in this module.</p>
                </div>
                <span class="badge bg-light text-dark border px-3 py-1.5 fw-semibold" style="font-size: 0.8rem;">
                    <?php echo count($pathItems); ?> Items
                </span>
            </div>
            
            <div class="p-0">
                <?php foreach ($pathItems as $index => $item): ?>
                    <div class="curriculum-item">
                        <div class="curriculum-item-icon">
                            <i data-lucide="<?php echo $typeIcons[$item['item_type']] ?? 'circle'; ?>" size="18"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark mb-0.5">
                                <?php echo htmlspecialchars($item['title'] ?: ucfirst($item['item_type']) . ' Module'); ?>
                            </div>
                            <div class="text-muted small text-capitalize d-flex align-items-center gap-2">
                                <span><?php echo htmlspecialchars($item['item_type']); ?> resource</span>
                                <span class="text-black-50">•</span>
                                <span>Module <?php echo ($index + 1); ?></span>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-light text-secondary border px-2.5 py-1 text-uppercase" style="font-size: 0.7rem; font-weight: 700;">
                                <?php echo htmlspecialchars($item['item_type']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT COLUMN: STICKY ENROLLMENT BUY BOX -->
    <div class="col-lg-4">
        <div class="details-buy-box">
            <div class="buy-box-header">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-uppercase fw-bold text-info fs-7 tracking-wider" style="font-size: 0.75rem; color: #67e8f9 !important;">Candidate Access</span>
                    <span class="badge bg-success text-white px-2.5 py-1" style="font-size: 0.7rem; font-weight: 700;">Included</span>
                </div>
                <h3 class="heading-font fw-bold fs-5 text-white mb-0">Module Enrollment</h3>
            </div>

            <div class="buy-box-body">
                <!-- ENROLLMENT CTA STATES -->
                <?php if ($isEnrolled): ?>
                    <!-- STATE: ALREADY ENROLLED -->
                    <div class="text-center mb-4">
                        <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-2">
                            <i data-lucide="check-circle-2" size="28"></i>
                        </div>
                        <h4 class="heading-font fw-bold text-dark fs-5 mb-1">You Are Enrolled!</h4>
                        <p class="text-muted small mb-0">Your access to this study module and test bank is active.</p>
                    </div>

                    <div class="d-flex flex-column gap-2.5 mb-4">
                        <a href="index.php?route=student/course/view&id=<?php echo $course['id']; ?>"
                           class="btn-cta-mint d-flex align-items-center justify-content-center gap-2">
                            <span>Go to Course Content</span>
                            <i data-lucide="arrow-right" size="16"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- STATE: NOT ENROLLED (UNIFIED ENROLL NOW BUTTON FOR ALL VISITORS) -->
                    <div class="text-center mb-4">
                        <div class="d-inline-flex p-3 rounded-circle bg-light text-primary mb-2" style="color: var(--clinical-teal) !important;">
                            <i data-lucide="graduation-cap" size="28"></i>
                        </div>
                        <h4 class="heading-font fw-bold text-dark fs-5 mb-1">Get Instant Access</h4>
                        <p class="text-muted small mb-0">Enroll now to unlock practice test items and diagnostic tools.</p>
                    </div>

                    <div class="d-flex flex-column gap-2.5 mb-4">
                        <a href="index.php?route=course/checkout&id=<?php echo $course['id']; ?>"
                           class="btn-cta-teal d-flex align-items-center justify-content-center gap-2">
                            <span>Enroll Now</span>
                            <i data-lucide="arrow-right" size="16"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <hr class="my-3" style="border-color: var(--clinical-border);">

                <div class="mb-2">
                    <span class="fw-bold text-dark small">This course includes:</span>
                </div>
                <div class="buy-box-feature-item">
                    <i data-lucide="file-check-2" size="16" class="text-teal" style="color: var(--clinical-teal);"></i>
                    <span>Full practice test bank & exam items</span>
                </div>
                <div class="buy-box-feature-item">
                    <i data-lucide="bar-chart-3" size="16" class="text-teal" style="color: var(--clinical-teal);"></i>
                    <span>Automated diagnostic performance feedback</span>
                </div>
                <div class="buy-box-feature-item">
                    <i data-lucide="clock" size="16" class="text-teal" style="color: var(--clinical-teal);"></i>
                    <span>Self-paced study & unlimited attempts</span>
                </div>
                <div class="buy-box-feature-item">
                    <i data-lucide="smartphone" size="16" class="text-teal" style="color: var(--clinical-teal);"></i>
                    <span>Accessible on mobile and desktop devices</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isPublicVisitor): ?></div><?php endif; ?>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_footer.php';
}
?>
