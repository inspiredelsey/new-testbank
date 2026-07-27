<?php
$pageTitle = 'Explore Courses & Test Banks';
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
    
    if (preg_match('/code|comput|software|tech|program|web|python|java|data|ai|cyber|network/i', $searchKey)) {
        return 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80';
    } elseif (preg_match('/busin|manag|market|finan|econ|account|lead|entre/i', $searchKey)) {
        return 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80';
    } elseif (preg_match('/scien|chem|physic|bio|lab|research|matter/i', $searchKey)) {
        return 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=800&q=80';
    } elseif (preg_match('/health|medic|nurs|pharm|clini|care|anat|doctor/i', $searchKey)) {
        return 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=80';
    } elseif (preg_match('/math|calc|stati|algeb|engin|geomet/i', $searchKey)) {
        return 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=800&q=80';
    } elseif (preg_match('/law|legal|politi|gover|just|crim/i', $searchKey)) {
        return 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=800&q=80';
    } elseif (preg_match('/art|design|music|photo|media/i', $searchKey)) {
        return 'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?auto=format&fit=crop&w=800&q=80';
    }
    
    // Default high-quality education cover
    return 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80';
}

/**
 * Return a Lucide icon name matching a category name
 */
function getCategoryIcon($categoryName) {
    $name = strtolower($categoryName ?? '');
    if (preg_match('/code|comput|software|tech|it/i', $name)) return 'laptop';
    if (preg_match('/busin|manag|finan|market/i', $name)) return 'briefcase';
    if (preg_match('/scien|chem|bio/i', $name)) return 'flask-conical';
    if (preg_match('/health|medic|nurs/i', $name)) return 'activity';
    if (preg_match('/math|stat|calc/i', $name)) return 'calculator';
    if (preg_match('/law|legal/i', $name)) return 'shield-check';
    if (preg_match('/art|human|lang|hist/i', $name)) return 'book-open';
    return 'folder-open';
}

/**
 * Limit description text to ~50-70 words cleanly
 */
function getCourseWordExcerpt($description, $targetWords = 60) {
    $clean = trim(strip_tags($description ?? ''));
    if (empty($clean)) {
        return "Master this course with structured learning modules, comprehensive study materials, and interactive practice test banks. Designed by certified instructors to help you test your knowledge, track performance, and achieve exceptional exam scores.";
    }
    $words = preg_split('/\s+/', $clean);
    if (count($words) <= $targetWords) {
        return implode(' ', $words);
    }
    return implode(' ', array_slice($words, 0, $targetWords)) . '...';
}
?>

<!-- HERO LANDING BANNER -->
<section class="container my-4 my-lg-5">
    <div class="hero-banner p-4 p-md-5 p-lg-5 shadow-lg position-relative" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 27, 75, 0.93) 50%, rgba(49, 46, 129, 0.96) 100%), url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; border-radius: 1.25rem;">
        <div class="row align-items-center position-relative z-1 g-4">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-white bg-opacity-10 backdrop-blur text-white text-xs fw-semibold mb-3 border border-white border-opacity-20">
                    <span class="badge bg-primary text-white rounded-pill px-2">NEW</span>
                    <span><i data-lucide="sparkles" size="14" class="text-warning me-1"></i> Certified Test Bank LMS</span>
                </div>
                <h1 class="display-font display-5 fw-extrabold text-white mb-3" style="line-height: 1.15; letter-spacing: -0.02em;">
                    Master Skills & Pass Exams <br class="d-none d-md-block">With Complete Confidence.
                </h1>
                <p class="text-white text-opacity-85 lead fs-6 mb-4" style="max-width: 580px; line-height: 1.6;">
                    Access structured learning paths, real-time practice test banks, and certified instructor content tailored to ensure academic and professional success.
                </p>

                <!-- HERO SEARCH BAR -->
                <form method="GET" action="index.php" class="bg-white p-2 rounded-3 shadow-lg d-flex flex-column flex-sm-row gap-2" style="max-width: 620px;">
                    <input type="hidden" name="route" value="courses">
                    <?php if (!empty($selectedCategory)): ?>
                        <input type="hidden" name="category_id" value="<?php echo $selectedCategory; ?>">
                    <?php endif; ?>
                    <div class="input-group border-0">
                        <span class="input-group-text bg-transparent border-0 text-muted ps-2">
                            <i data-lucide="search" size="18"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-0 shadow-none ps-0" placeholder="Search courses, topics, or test banks..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary px-4 py-2.5 text-nowrap d-flex align-items-center justify-content-center gap-1.5 fw-bold">
                        <span>Search Catalog</span>
                        <i data-lucide="arrow-right" size="16"></i>
                    </button>
                </form>
            </div>

            <!-- HERO STATS / BADGES -->
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3.5 bg-white bg-opacity-10 backdrop-blur rounded-3 border border-white border-opacity-10 h-100">
                            <div class="d-flex align-items-center gap-2 text-warning mb-1">
                                <i data-lucide="book-open" size="20"></i>
                                <span class="fw-bold fs-4 text-white"><?php echo intval($stats['course_count'] ?? count($courses)); ?>+</span>
                            </div>
                            <div class="text-white text-opacity-75 small fw-medium">Active Courses</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3.5 bg-white bg-opacity-10 backdrop-blur rounded-3 border border-white border-opacity-10 h-100">
                            <div class="d-flex align-items-center gap-2 text-info mb-1">
                                <i data-lucide="clipboard-check" size="20"></i>
                                <span class="fw-bold fs-4 text-white"><?php echo intval($stats['exam_count'] ?? 12); ?>+</span>
                            </div>
                            <div class="text-white text-opacity-75 small fw-medium">Practice Test Banks</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3.5 bg-white bg-opacity-10 backdrop-blur rounded-3 border border-white border-opacity-10 h-100">
                            <div class="d-flex align-items-center gap-2 text-success mb-1">
                                <i data-lucide="users" size="20"></i>
                                <span class="fw-bold fs-4 text-white"><?php echo intval($stats['student_count'] ?? 150); ?>+</span>
                            </div>
                            <div class="text-white text-opacity-75 small fw-medium">Enrolled Students</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3.5 bg-white bg-opacity-10 backdrop-blur rounded-3 border border-white border-opacity-10 h-100">
                            <div class="d-flex align-items-center gap-2 mb-1" style="color: #f472b6;">
                                <i data-lucide="award" size="20"></i>
                                <span class="fw-bold fs-4 text-white">98%</span>
                            </div>
                            <div class="text-white text-opacity-75 small fw-medium">Exam Pass Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SPACIOUS EXPLORE BY CATEGORIES SECTION -->
<section class="container my-5 py-2">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-inline-flex align-items-center gap-2 text-primary fw-bold text-xs tracking-wider mb-1">
                <i data-lucide="layout-grid" size="16"></i>
                <span>CATEGORIES</span>
            </div>
            <h3 class="display-font fw-bold text-dark mb-1 fs-3">Explore Course Categories</h3>
            <p class="text-muted small mb-0">Browse specialized subjects and practice test banks tailored to your field of study.</p>
        </div>
        <?php if (!empty($selectedCategory) || !empty($search)): ?>
            <a href="index.php?route=courses" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 align-self-start align-self-md-auto">
                <i data-lucide="rotate-ccw" size="14"></i>
                <span>Clear All Filters</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- CATEGORY GRID CARDS -->
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-md-4">
        <div class="col">
            <a href="index.php?route=courses<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
               class="category-card <?php echo empty($selectedCategory) ? 'active' : ''; ?>">
                <div class="category-icon-box">
                    <i data-lucide="layers" size="22"></i>
                </div>
                <div class="fw-bold text-truncate w-100" style="font-size: 0.95rem;">All Courses</div>
                <div class="text-muted text-opacity-75 text-xs">Catalog Overview</div>
            </a>
        </div>
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <?php $catIcon = getCategoryIcon($cat['name']); ?>
                <div class="col">
                    <a href="index.php?route=courses&category_id=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-card <?php echo intval($selectedCategory) === intval($cat['id']) ? 'active' : ''; ?>">
                        <div class="category-icon-box">
                            <i data-lucide="<?php echo $catIcon; ?>" size="22"></i>
                        </div>
                        <div class="fw-bold text-truncate w-100" style="font-size: 0.95rem;"><?php echo htmlspecialchars($cat['name']); ?></div>
                        <div class="text-muted text-opacity-75 text-xs">Test Bank</div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- CATALOG FILTER BAR & COURSES LISTING -->
<section class="container my-5 pt-2">
    <!-- FILTER BAR HEADER -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="index.php" id="filterForm" class="row g-3 align-items-center">
                <input type="hidden" name="route" value="courses">
                <?php if (!empty($selectedCategory)): ?>
                    <input type="hidden" name="category_id" value="<?php echo $selectedCategory; ?>">
                <?php endif; ?>
                
                <div class="col-md-6 col-lg-7">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-dark fs-6">
                            <?php echo count($courses); ?> <?php echo count($courses) === 1 ? 'Course Available' : 'Courses Available'; ?>
                        </span>
                        <?php if (!empty($search)): ?>
                            <span class="badge bg-light text-dark border px-2.5 py-1.5">
                                Search: "<?php echo htmlspecialchars($search); ?>"
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6 col-lg-5 d-flex gap-2 justify-content-md-end">
                    <div class="input-group input-group-sm" style="max-width: 220px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i data-lucide="arrow-up-down" size="14"></i></span>
                        <select name="sort" class="form-select form-select-sm border-start-0" onchange="document.getElementById('filterForm').submit();">
                            <option value="newest" <?php echo ($sort ?? '') === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="popular" <?php echo ($sort ?? '') === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            <option value="title" <?php echo ($sort ?? '') === 'title' ? 'selected' : ''; ?>>Alphabetical (A-Z)</option>
                        </select>
                    </div>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- COURSE CARDS GRID -->
    <?php if (empty($courses)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body py-4">
                <div class="p-3 bg-primary-subtle text-primary rounded-circle d-inline-flex mb-3">
                    <i data-lucide="book-open-check" size="32"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">No courses found matching your criteria</h5>
                <p class="text-muted mb-4" style="max-width: 420px; margin: 0 auto;">
                    We couldn't find any published courses matching "<?php echo htmlspecialchars($search ?? 'your selection'); ?>". Try clearing your search or browsing another category.
                </p>
                <a href="index.php?route=courses" class="btn btn-primary px-4">
                    <i data-lucide="rotate-ccw" size="16" class="me-1"></i> Reset Filters
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($courses as $index => $course): ?>
                <?php $coverImg = getCourseCoverImage($course); ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card course-card shadow-sm h-100">
                        <!-- THUMBNAIL IMAGE HEADER -->
                        <div class="course-thumb-box">
                            <img src="<?php echo $coverImg; ?>" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                 class="course-thumb-img"
                                 onerror="this.src='https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80';">
                            
                            <div class="position-absolute top-0 start-0 p-3 w-100 d-flex justify-content-between align-items-center" style="background: linear-gradient(to bottom, rgba(15, 23, 42, 0.7) 0%, rgba(15, 23, 42, 0) 100%);">
                                <?php if (!empty($course['category_name'])): ?>
                                    <span class="badge bg-white bg-opacity-95 text-dark fw-bold shadow-sm px-2.5 py-1.5 rounded-pill" style="font-size: 0.75rem;">
                                        <i data-lucide="<?php echo getCategoryIcon($course['category_name']); ?>" size="12" class="me-1 text-primary"></i>
                                        <?php echo htmlspecialchars($course['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>

                                <?php if (!empty($course['is_enrolled'])): ?>
                                    <span class="badge bg-success text-white fw-bold shadow-sm d-inline-flex align-items-center gap-1 px-2.5 py-1.5 rounded-pill" style="font-size: 0.75rem;">
                                        <i data-lucide="check-circle-2" size="12"></i> Enrolled
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- CARD BODY -->
                        <div class="card-body p-4 d-flex flex-column">
                            <!-- TITLE -->
                            <h5 class="fw-bold text-dark mb-2 display-font fs-5" style="line-height: 1.35;">
                                <a href="index.php?route=course/details&id=<?php echo $course['id']; ?>" class="text-dark text-decoration-none hover-primary">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </a>
                            </h5>

                            <!-- EXCERPT DESCRIPTION (~50-70 WORDS) -->
                            <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.6; font-size: 0.88rem;">
                                <?php echo htmlspecialchars(getCourseWordExcerpt($course['description'] ?? '', 60)); ?>
                            </p>

                            <!-- INSTRUCTOR & METRICS FOOTER -->
                            <div class="border-top pt-3 mt-auto">
                                <div class="d-flex align-items-center justify-content-between text-muted small mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 30px; height: 30px; font-size: 13px;">
                                            <?php echo strtoupper(substr($course['instructor_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <span class="fw-semibold text-dark text-truncate" style="max-width: 130px;">
                                            <?php echo htmlspecialchars($course['instructor_name'] ?? 'Instructor'); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            <i data-lucide="target" size="12" class="me-1 text-primary"></i><?php echo intval($course['pass_percentage'] ?? 50); ?>% Pass
                                        </span>
                                    </div>
                                </div>

                                <a href="index.php?route=course/details&id=<?php echo $course['id']; ?>" 
                                   class="btn <?php echo !empty($course['is_enrolled']) ? 'btn-success' : 'btn-outline-primary'; ?> w-100 d-inline-flex align-items-center justify-content-center gap-2 py-2.5 fw-semibold">
                                    <span><?php echo !empty($course['is_enrolled']) ? 'Go to Course' : 'View Course Details'; ?></span>
                                    <i data-lucide="arrow-right" size="16"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- PLATFORM FEATURES & VALUE PROPOSITION -->
<section class="container my-5 pt-4">
    <div class="text-center mb-5" style="max-width: 640px; margin: 0 auto;">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2 fw-semibold">WHY TEST BANK LMS</span>
        <h2 class="display-font fw-bold text-dark fs-2">Designed for Exceptional Exam Scores</h2>
        <p class="text-muted">Everything you need to master your coursework, practice under exam conditions, and track your learning progress.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card h-100">
                <div class="feature-icon">
                    <i data-lucide="clipboard-list" size="24"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Interactive Test Banks</h5>
                <p class="text-muted small mb-0">
                    Practice with authentic exam-style questions including multiple choice, essay, and case study items with detailed feedback.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card h-100">
                <div class="feature-icon">
                    <i data-lucide="bar-chart-2" size="24"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Automated Grading & Analytics</h5>
                <p class="text-muted small mb-0">
                    Get instant score breakdowns and track your improvement over time to pinpoint exactly where you need to focus.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card h-100">
                <div class="feature-icon">
                    <i data-lucide="award" size="24"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Structured Learning Paths</h5>
                <p class="text-muted small mb-0">
                    Follow curated step-by-step modules with reading documents, external reference links, and milestone assessments.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CALL TO ACTION BANNER -->
<?php if ($isPublicVisitor): ?>
<section class="container my-5 pb-3">
    <div class="p-4 p-md-5 rounded-4 text-white shadow-lg text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
        <div class="position-relative z-1" style="max-width: 600px; margin: 0 auto;">
            <h2 class="display-font fw-bold mb-3">Ready to Accelerate Your Learning?</h2>
            <p class="text-white text-opacity-85 mb-4">Create your free account today to enroll in courses, take practice exams, and get instant feedback.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="index.php?route=register" class="btn btn-light text-primary fw-bold px-4 py-2.5">
                    Create Free Account <i data-lucide="arrow-right" size="16" class="ms-1"></i>
                </a>
                <a href="index.php?route=login" class="btn btn-outline-light px-4 py-2.5">
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
