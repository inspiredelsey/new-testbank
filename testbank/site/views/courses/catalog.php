<?php
$pageTitle = 'Explore Certification Courses & Test Banks';
$isPublicVisitor = empty($user);
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_header.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_header.php';
}

/**
 * Return a high-quality relevant Unsplash cover image based on course title / category / thumbnail
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

/**
 * Limit description text to 50 words maximum cleanly
 */
function getCourseWordExcerpt($description, $targetWords = 50) {
    $clean = trim(strip_tags($description ?? ''));
    if (empty($clean)) {
        return "Comprehensive licensure examination test bank featuring Next Generation NCLEX (NGN) item types, detailed rationales, clinical judgment scenarios, and real-time diagnostic performance tracking.";
    }
    // Add space between CamelCase if concatenated (e.g. CourseNew -> Course New)
    $clean = preg_replace('/([a-z])([A-Z])/', '$1 $2', $clean);
    $words = preg_split('/\s+/', $clean);
    if (count($words) > $targetWords) {
        $clean = implode(' ', array_slice($words, 0, $targetWords)) . '...';
    } else {
        $clean = implode(' ', $words);
    }
    if (mb_strlen($clean) > 260) {
        $clean = mb_strimwidth($clean, 0, 260, '...');
    }
    return $clean;
}

/**
 * Format course price display cleanly
 */
function getFormattedPrice($course) {
    if (!empty($course['is_enrolled'])) {
        return 'Enrolled';
    }
    if (!isset($course['price']) || floatval($course['price']) <= 0) {
        return 'Included Access';
    }
    $curr = $course['currency'] ?? '$';
    if ($curr === 'USD') $curr = '$';
    return $curr . number_format(floatval($course['price']), 2);
}
?>

<!-- GOOGLE FONTS FOR CLINICAL & ACCESSIBLE TYPOGRAPHY -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================================
   CLINICAL TRUST & ACCESSIBLE COLOR SYSTEM
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
    --clinical-amber-light: #fffbe8;
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
.category-pill:focus-visible,
.clinical-card:focus-within {
    outline: 2px solid var(--clinical-teal) !important;
    outline-offset: 3px !important;
    box-shadow: 0 0 0 4px rgba(8, 145, 178, 0.25) !important;
}

/* ============================================================
   HERO CLINICAL PREP BANNER
   ============================================================ */
.clinical-hero {
    background: linear-gradient(135deg, var(--clinical-navy) 0%, var(--clinical-navy-light) 60%, #0d3b66 100%);
    border-radius: var(--clinical-radius);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.clinical-hero::before {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    background: radial-gradient(circle at 80% 20%, rgba(8, 145, 178, 0.18) 0%, transparent 50%);
    pointer-events: none;
}

.clinical-hero-badge {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #e0f2fe;
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.hero-search-container {
    background: #ffffff;
    border-radius: 10px;
    padding: 0.375rem;
    box-shadow: 0 10px 25px -5px rgba(10, 29, 55, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.hero-search-input {
    border: none;
    box-shadow: none;
    font-size: 0.95rem;
    color: var(--clinical-text-main);
    padding-left: 0.5rem;
}

.hero-search-input:focus {
    box-shadow: none;
}

.hero-search-btn {
    background-color: var(--clinical-teal);
    color: #ffffff;
    font-weight: 700;
    padding: 0.65rem 1.5rem;
    border-radius: 8px;
    border: none;
    transition: background-color 0.2s ease, transform 0.15s ease;
}

.hero-search-btn:hover {
    background-color: var(--clinical-teal-hover);
    color: #ffffff;
}

.hero-stat-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    padding: 1rem 1.25rem;
    height: 100%;
}

.hero-stat-value {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.hero-stat-label {
    font-size: 0.8125rem;
    color: rgba(224, 242, 254, 0.85);
    font-weight: 500;
    margin-top: 0.25rem;
}

/* ============================================================
   CATEGORY PILL FILTER GRID
   ============================================================ */
.category-pill {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.75rem 1rem;
    background-color: var(--clinical-card-bg);
    border: 1px solid var(--clinical-border);
    border-radius: 10px;
    color: var(--clinical-text-main);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    height: 100%;
}

.category-pill:hover {
    border-color: var(--clinical-teal);
    background-color: var(--clinical-teal-light);
    color: var(--clinical-teal-hover);
    transform: translateY(-1px);
}

.category-pill.active {
    background-color: var(--clinical-teal);
    border-color: var(--clinical-teal);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.25);
}

.category-pill-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background-color: var(--clinical-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--clinical-teal);
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.category-pill.active .category-pill-icon {
    background-color: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

/* ============================================================
   CLINICAL COURSE CARDS (SIGNATURE ELEMENT)
   ============================================================ */
.clinical-card {
    background-color: var(--clinical-card-bg);
    border: 1px solid var(--clinical-border);
    border-radius: var(--clinical-radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
    transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
}

.clinical-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; bottom: 0;
    width: 4px;
    background-color: transparent;
    transition: background-color 0.25s ease;
    z-index: 2;
}

.clinical-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    transform: translateY(-3px);
}

.clinical-card:hover::before {
    background-color: var(--clinical-teal);
}

.card-thumb-container {
    height: 190px;
    position: relative;
    overflow: hidden;
    background-color: var(--clinical-navy);
}

.card-thumb-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}

.clinical-card:hover .card-thumb-image {
    transform: scale(1.04);
}

.card-category-badge {
    position: absolute;
    top: 0.85rem;
    left: 0.85rem;
    background: rgba(10, 29, 55, 0.88);
    backdrop-filter: blur(8px);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.3rem 0.7rem;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.card-enrolled-badge {
    position: absolute;
    top: 0.85rem;
    right: 0.85rem;
    background: var(--clinical-mint);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.3rem 0.75rem;
    border-radius: 50px;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.card-body-content {
    padding: 1.25rem 1.35rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.card-course-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 1.125rem;
    color: var(--clinical-text-main);
    line-height: 1.35;
    margin-bottom: 0.6rem;
}

.card-course-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.15s ease;
}

.card-course-title a:hover {
    color: var(--clinical-teal);
}

.card-excerpt-text {
    color: var(--clinical-text-muted);
    font-size: 0.875rem;
    line-height: 1.55;
    margin-bottom: 1.25rem;
    flex-grow: 1;
}

.card-meta-bar {
    border-top: 1px solid var(--clinical-border);
    padding-top: 0.9rem;
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.instructor-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--clinical-text-muted);
}

.instructor-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: var(--clinical-teal-light);
    color: var(--clinical-teal-hover);
    font-weight: 800;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(8, 145, 178, 0.2);
}

.price-tag {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 1.05rem;
    color: var(--clinical-navy);
}

.price-tag.enrolled-text {
    color: var(--clinical-mint);
    font-size: 0.9rem;
}

.card-cta-btn {
    margin-top: 1rem;
    width: 100%;
    padding: 0.65rem 1rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    text-decoration: none;
}

.card-cta-btn.btn-access {
    background-color: var(--clinical-teal);
    color: #ffffff;
    border: 1px solid var(--clinical-teal);
}

.card-cta-btn.btn-access:hover {
    background-color: var(--clinical-teal-hover);
    border-color: var(--clinical-teal-hover);
    color: #ffffff;
}

.card-cta-btn.btn-enrolled {
    background-color: var(--clinical-mint-light);
    color: var(--clinical-mint);
    border: 1px solid rgba(5, 150, 105, 0.3);
}

.card-cta-btn.btn-enrolled:hover {
    background-color: var(--clinical-mint);
    color: #ffffff;
}

/* ============================================================
   EMPTY STATE & FILTER HEADER
   ============================================================ */
.filter-bar-card {
    background-color: #ffffff;
    border: 1px solid var(--clinical-border);
    border-radius: 10px;
    padding: 0.85rem 1.25rem;
}

.empty-state-card {
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: var(--clinical-radius);
    padding: 3.5rem 1.5rem;
    text-align: center;
}

.empty-state-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background-color: var(--clinical-teal-light);
    color: var(--clinical-teal);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
}

/* ============================================================
   CLINICAL FEATURES BANNER
   ============================================================ */
.feature-box {
    background: #ffffff;
    border: 1px solid var(--clinical-border);
    border-radius: 10px;
    padding: 1.5rem;
    height: 100%;
}

.feature-box-icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background-color: var(--clinical-teal-light);
    color: var(--clinical-teal);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

/* ============================================================
   REDUCED MOTION SUPPORT
   ============================================================ */
@media (prefers-reduced-motion: reduce) {
    .clinical-card,
    .card-thumb-image,
    .category-pill,
    .hero-search-btn,
    .card-cta-btn {
        transition: none !important;
        transform: none !important;
    }
}
</style>

<!-- CLINICAL HERO BANNER SECTION -->
<section class="container my-4">
    <div class="clinical-hero p-4 p-md-5">
        <div class="row align-items-center position-relative z-1 g-4">
            <div class="col-lg-7">
                <div class="clinical-hero-badge mb-3">
                    <i data-lucide="shield-check" size="16" class="text-info"></i>
                    <span>Certified NCLEX & Professional Licensure Item Banks</span>
                </div>
                <h1 class="heading-font display-5 fw-bold text-white mb-3" style="line-height: 1.15;">
                    Prepare with Precision. <br class="d-none d-md-block">Pass Your Licensure Exam.
                </h1>
                <p class="text-white text-opacity-85 fs-6 mb-4" style="max-width: 580px; line-height: 1.65;">
                    Access Next Generation NCLEX (NGN) case studies, item bank modules, and automated diagnostic scoring designed by board-certified clinical educators.
                </p>

                <!-- HERO SEARCH FORM -->
                <form method="GET" action="index.php" class="hero-search-container d-flex flex-column flex-sm-row gap-2" style="max-width: 620px;">
                    <input type="hidden" name="route" value="courses">
                    <?php if (!empty($selectedCategory)): ?>
                        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($selectedCategory); ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0 text-muted ps-2">
                            <i data-lucide="search" size="18"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               class="form-control hero-search-input" 
                               placeholder="Search courses, NGN item banks, or clinical topics..." 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>"
                               aria-label="Search courses or test banks">
                    </div>
                    <button type="submit" class="hero-search-btn text-nowrap d-inline-flex align-items-center justify-content-center gap-2">
                        <span>Search Catalog</span>
                        <i data-lucide="arrow-right" size="16"></i>
                    </button>
                </form>
            </div>

            <!-- HERO STATS / DIAGNOSTIC HIGHLIGHTS -->
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="hero-stat-card">
                            <div class="d-flex align-items-center gap-2 text-info mb-1">
                                <i data-lucide="book-marked" size="20"></i>
                                <span class="hero-stat-value"><?php echo intval($stats['course_count'] ?? count($courses)); ?>+</span>
                            </div>
                            <div class="hero-stat-label">Active Modules</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stat-card">
                            <div class="d-flex align-items-center gap-2 text-warning mb-1">
                                <i data-lucide="file-check-2" size="20"></i>
                                <span class="hero-stat-value"><?php echo intval($stats['exam_count'] ?? 16); ?>+</span>
                            </div>
                            <div class="hero-stat-label">Practice Item Banks</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stat-card">
                            <div class="d-flex align-items-center gap-2 text-success mb-1">
                                <i data-lucide="users" size="20"></i>
                                <span class="hero-stat-value"><?php echo intval($stats['student_count'] ?? 240); ?>+</span>
                            </div>
                            <div class="hero-stat-label">Enrolled Candidates</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stat-card">
                            <div class="d-flex align-items-center gap-2 style-pink mb-1" style="color: #67e8f9;">
                                <i data-lucide="award" size="20"></i>
                                <span class="hero-stat-value">98.4%</span>
                            </div>
                            <div class="hero-stat-label">Licensure Pass Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DISCIPLINE & CATEGORY SELECTION -->
<section class="container my-4 py-2">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
        <div>
            <div class="text-uppercase fw-bold text-xs text-primary tracking-wider mb-1" style="color: var(--clinical-teal) !important;">
                <i data-lucide="grid" size="14" class="me-1"></i> Specializations
            </div>
            <h2 class="heading-font fw-bold fs-4 text-dark mb-1">Browse Clinical Categories</h2>
            <p class="text-muted small mb-0">Select a discipline to view specialized test banks and examination modules.</p>
        </div>
        <?php if (!empty($selectedCategory) || !empty($search)): ?>
            <a href="index.php?route=courses" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 align-self-start align-self-md-auto">
                <i data-lucide="rotate-ccw" size="14"></i>
                <span>Clear All Filters</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- CATEGORIES GRID -->
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
        <div class="col">
            <a href="index.php?route=courses<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
               class="category-pill <?php echo empty($selectedCategory) ? 'active' : ''; ?>">
                <div class="category-pill-icon">
                    <i data-lucide="layers" size="18"></i>
                </div>
                <div class="text-truncate">All Modules</div>
            </a>
        </div>
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <?php $catIcon = getCategoryIcon($cat['name']); ?>
                <div class="col">
                    <a href="index.php?route=courses&category_id=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-pill <?php echo intval($selectedCategory) === intval($cat['id']) ? 'active' : ''; ?>">
                        <div class="category-pill-icon">
                            <i data-lucide="<?php echo $catIcon; ?>" size="18"></i>
                        </div>
                        <div class="text-truncate"><?php echo htmlspecialchars($cat['name']); ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- FILTER & COURSE LISTING SECTION -->
<section class="container my-4">
    <!-- FILTER BAR HEADER -->
    <div class="filter-bar-card mb-4">
        <form method="GET" action="index.php" id="filterForm" class="row g-3 align-items-center">
            <input type="hidden" name="route" value="courses">
            <?php if (!empty($selectedCategory)): ?>
                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($selectedCategory); ?>">
            <?php endif; ?>
            
            <div class="col-md-6 col-lg-7">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark fs-6">
                        <?php echo count($courses); ?> <?php echo count($courses) === 1 ? 'Exam Module Available' : 'Exam Modules Available'; ?>
                    </span>
                    <?php if (!empty($search)): ?>
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 d-inline-flex align-items-center gap-1">
                            Search: "<?php echo htmlspecialchars($search); ?>"
                            <a href="index.php?route=courses<?php echo !empty($selectedCategory) ? '&category_id=' . $selectedCategory : ''; ?>" class="text-muted text-decoration-none ms-1">✕</a>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6 col-lg-5 d-flex gap-2 justify-content-md-end">
                <div class="input-group input-group-sm" style="max-width: 220px;">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i data-lucide="arrow-up-down" size="14"></i></span>
                    <select name="sort" class="form-select form-select-sm border-start-0" onchange="document.getElementById('filterForm').submit();" aria-label="Sort courses">
                        <option value="newest" <?php echo ($sort ?? '') === 'newest' ? 'selected' : ''; ?>>Newest Modules</option>
                        <option value="popular" <?php echo ($sort ?? '') === 'popular' ? 'selected' : ''; ?>>Most Enrolled</option>
                        <option value="title" <?php echo ($sort ?? '') === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                    </select>
                </div>
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- COURSE CARDS GRID -->
    <?php if (empty($courses)): ?>
        <!-- REASSURING CLINICAL EMPTY STATE -->
        <div class="empty-state-card">
            <div class="empty-state-icon">
                <i data-lucide="search-x" size="32"></i>
            </div>
            <h3 class="heading-font fw-bold text-dark fs-4 mb-2">No Examination Modules Found</h3>
            <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto;">
                We couldn't find any active test bank modules matching "<strong><?php echo htmlspecialchars($search ?? 'your filter'); ?></strong>". Try adjusting your search query or clear existing category filters.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="index.php?route=courses" class="btn btn-primary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-1.5" style="background-color: var(--clinical-teal); border-color: var(--clinical-teal);">
                    <i data-lucide="rotate-ccw" size="16"></i>
                    <span>Reset All Filters</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($courses as $index => $course): ?>
                <?php 
                    $coverImg = getCourseCoverImage($course); 
                    $priceFormatted = getFormattedPrice($course);
                ?>
                <div class="col-md-6 col-lg-4">
                    <article class="clinical-card">
                        <!-- THUMBNAIL COVER HEADER -->
                        <div class="card-thumb-container">
                            <img src="<?php echo $coverImg; ?>" 
                                 alt="Cover image for <?php echo htmlspecialchars($course['title']); ?>" 
                                 class="card-thumb-image"
                                 onerror="this.src='https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80';">
                            
                            <?php if (!empty($course['category_name'])): ?>
                                <span class="card-category-badge">
                                    <i data-lucide="<?php echo getCategoryIcon($course['category_name']); ?>" size="12"></i>
                                    <?php echo htmlspecialchars($course['category_name']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($course['is_enrolled'])): ?>
                                <span class="card-enrolled-badge">
                                    <i data-lucide="check-circle-2" size="13"></i> Enrolled
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- CARD BODY CONTENT -->
                        <div class="card-body-content">
                            <!-- TITLE -->
                            <h3 class="card-course-title">
                                <a href="index.php?route=course/details&id=<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </a>
                            </h3>

                            <!-- CLINICAL EXCERPT -->
                            <p class="card-excerpt-text">
                                <?php echo htmlspecialchars(getCourseWordExcerpt($course['description'] ?? '', 20)); ?>
                            </p>

                            <!-- INSTRUCTOR & PRICE FOOTER -->
                            <div class="card-meta-bar">
                                <div class="instructor-info">
                                    <div class="instructor-avatar">
                                        <?php echo strtoupper(substr($course['instructor_name'] ?? 'I', 0, 1)); ?>
                                    </div>
                                    <span class="text-truncate" style="max-width: 120px;">
                                        <?php echo htmlspecialchars($course['instructor_name'] ?? 'Instructor'); ?>
                                    </span>
                                </div>

                                <div class="price-tag <?php echo !empty($course['is_enrolled']) ? 'enrolled-text' : ''; ?>">
                                    <?php echo htmlspecialchars($priceFormatted); ?>
                                </div>
                            </div>

                            <!-- CALL TO ACTION BUTTON -->
                            <?php if (!empty($course['is_enrolled'])): ?>
                                <a href="index.php?route=course/details&id=<?php echo $course['id']; ?>" class="card-cta-btn btn-enrolled">
                                    <span>Access Study Module</span>
                                    <i data-lucide="arrow-right" size="16"></i>
                                </a>
                            <?php else: ?>
                                <a href="index.php?route=course/details&id=<?php echo $course['id']; ?>" class="card-cta-btn btn-access">
                                    <span>Enrol Now</span>
                                    <i data-lucide="arrow-right" size="16"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- VALUE PROPOSITION & CLINICAL RIGOR BANNER -->
<section class="container my-5 pt-3">
    <div class="text-center mb-4" style="max-width: 620px; margin: 0 auto;">
        <span class="badge bg-light text-secondary border px-3 py-1 mb-2 fw-semibold" style="font-size: 0.78rem; letter-spacing: 0.05em;">CLINICAL METHODOLOGY</span>
        <h2 class="heading-font fw-bold text-dark fs-3">Engineered for First-Time Exam Pass Rates</h2>
        <p class="text-muted small">Our test bank architecture models real clinical judgment exams with instant rationales and remedial reference materials.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <i data-lucide="file-text" size="22"></i>
                </div>
                <h3 class="heading-font fw-bold fs-5 text-dark mb-2">NGN Item Scenarios</h3>
                <p class="text-muted small mb-0">
                    Practice with case studies, matrix grids, bowtie items, and highlight tables identical to real licensure testing environments.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <i data-lucide="activity" size="22"></i>
                </div>
                <h3 class="heading-font fw-bold fs-5 text-dark mb-2">Diagnostic Performance</h3>
                <p class="text-muted small mb-0">
                    Track your topic mastery and subject confidence in real time to focus study sessions on high-yield improvement areas.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <i data-lucide="book-open-check" size="22"></i>
                </div>
                <h3 class="heading-font fw-bold fs-5 text-dark mb-2">Detailed Rationales</h3>
                <p class="text-muted small mb-0">
                    Every question includes evidence-based explanations for correct and incorrect options to reinforce critical clinical judgment.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- PUBLIC VISITOR CTA -->
<?php if ($isPublicVisitor): ?>
<section class="container my-5 pb-2">
    <div class="p-4 p-md-5 rounded-3 text-white shadow-sm text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--clinical-navy) 0%, #17375e 100%); border: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="position-relative z-1" style="max-width: 580px; margin: 0 auto;">
            <h2 class="heading-font fw-bold mb-2 fs-3">Ready to Start Your Licensure Preparation?</h2>
            <p class="text-white text-opacity-85 small mb-4">Create your candidate account today to access practice test banks, take diagnostic exams, and review detailed rationales.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="index.php?route=register" class="btn btn-light fw-bold px-4 py-2.5 text-nowrap" style="color: var(--clinical-navy); border-radius: 8px;">
                    Create Free Candidate Account <i data-lucide="arrow-right" size="16" class="ms-1"></i>
                </a>
                <a href="index.php?route=login" class="btn btn-outline-light px-4 py-2.5 text-nowrap" style="border-radius: 8px;">
                    Sign In
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
if ($isPublicVisitor) {
    include __DIR__ . '/../layout/public_footer.php';
} else {
    include __DIR__ . '/../../../admin/views/layout_footer.php';
}
?>
