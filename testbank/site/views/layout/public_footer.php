</main>

<footer class="border-top bg-white py-5 mt-5">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <a class="display-font fw-bold d-flex align-items-center gap-2 text-dark fs-5 text-decoration-none mb-3" href="index.php?route=courses">
                    <span class="p-15 bg-primary text-white rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i data-lucide="graduation-cap" size="18"></i>
                    </span>
                    <span>Test Bank <span class="text-primary">LMS</span></span>
                </a>
                <p class="text-muted small mb-3" style="max-width: 320px;">
                    The premier learning platform for certified course materials, interactive practice exam test banks, and automated performance tracking.
                </p>
                <div class="d-flex align-items-center gap-2 text-muted">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 text-xs">
                        <i data-lucide="shield-check" size="12" class="me-1"></i> Verified Content
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 text-xs">
                        <i data-lucide="award" size="12" class="me-1"></i> LMS Certified
                    </span>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="fw-bold text-dark mb-3">Quick Links</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="index.php?route=courses" class="text-muted text-decoration-none hover-primary">Browse Courses</a></li>
                    <li><a href="index.php?route=login" class="text-muted text-decoration-none hover-primary">Student Login</a></li>
                    <li><a href="index.php?route=register" class="text-muted text-decoration-none hover-primary">Register Free</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="fw-bold text-dark mb-3">Learning Features</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li class="text-muted"><i data-lucide="check" size="14" class="text-success me-1"></i> Practice Test Banks</li>
                    <li class="text-muted"><i data-lucide="check" size="14" class="text-success me-1"></i> Instant Automated Grading</li>
                    <li class="text-muted"><i data-lucide="check" size="14" class="text-success me-1"></i> Detailed Analytics</li>
                    <li class="text-muted"><i data-lucide="check" size="14" class="text-success me-1"></i> Certificate of Completion</li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-dark mb-3">Get Started Today</h6>
                <p class="text-muted small mb-3">Join thousands of students preparing for their exams with confidence.</p>
                <a href="index.php?route=register" class="btn btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1">
                    Create Free Account <i data-lucide="arrow-right" size="14"></i>
                </a>
            </div>
        </div>
        <div class="pt-4 border-top d-flex flex-wrap align-items-center justify-content-between gap-3 text-muted small">
            <div>
                &copy; <?php echo date('Y'); ?> Test Bank LMS. All rights reserved.
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted"><i data-lucide="lock" size="13" class="me-1"></i> SSL Encrypted</span>
                <span class="text-muted"><i data-lucide="cpu" size="13" class="me-1"></i> Powered by Test Bank Core</span>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }
</script>
</body>
</html>
