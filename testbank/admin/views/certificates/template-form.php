<?php
/**
 * Certificate Template Editor View
 */
$pageTitle = 'Certificate Template - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <!-- Course Title Header -->
        <div class="mb-4">
            <a href="index.php?route=admin/courses" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
                <i data-lucide="arrow-left" size="14"></i> Back to Courses
            </a>
            <h3 class="display-font fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['title']); ?></h3>
            <p class="text-muted small mb-0"><i data-lucide="user-check" size="14" class="me-1"></i>Instructor: <strong><?php echo htmlspecialchars($course['instructor_name'] ?? 'Instructor'); ?></strong></p>
        </div>

        <!-- Tabs Navigation -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white p-0 border-0">
                <ul class="nav nav-tabs border-bottom ps-3" id="courseManagementTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=documents">
                            <i data-lucide="file-text" size="16"></i> Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=links">
                            <i data-lucide="link" size="16"></i> Links
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=quizzes">
                            <i data-lucide="help-circle" size="16"></i> Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=learning-path">
                            <i data-lucide="map" size="16"></i> Learning Path
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=enrollments">
                            <i data-lucide="users" size="16"></i> Enrollments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2 active" href="index.php?route=admin/certificates&action=template&course_id=<?php echo $course['id']; ?>">
                            <i data-lucide="award" size="16"></i> Certificate Template
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2" href="index.php?route=admin/certificates&action=list&course_id=<?php echo $course['id']; ?>">
                            <i data-lucide="scroll" size="16"></i> Issued Certificates
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                <i data-lucide="check-circle" class="text-success"></i>
                <div><?php echo htmlspecialchars($successMsg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['csrf'])): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i data-lucide="alert-triangle" class="text-danger"></i>
                <div><?php echo htmlspecialchars($errors['csrf']); ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Side: Template Editor Form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-semibold">Template Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="index.php?route=admin/certificates&action=template&course_id=<?php echo $course['id']; ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                            <div class="mb-3">
                                <label for="title" class="form-label fw-semibold text-secondary small">Template Display Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($template['title'] ?? 'Certificate of Completion'); ?>" required placeholder="e.g., Certificate of Completion">
                            </div>

                            <div class="mb-3">
                                <label for="html_template" class="form-label fw-semibold text-secondary small">HTML Template Content</label>
                                <p class="text-muted small mb-2">Design your certificate layout in HTML. If left empty or deleted, a standard, beautifully styled default template will be used automatically.</p>
                                <textarea class="form-control font-mono text-xs" id="html_template" name="html_template" rows="15" placeholder="Enter certificate HTML here..."><?php echo htmlspecialchars($template['html_template'] ?? $template['content'] ?? CertificateGenerator::getDefaultTemplate()); ?></textarea>
                                <?php if (!empty($errors['html_template'])): ?>
                                    <div class="text-danger small mt-1"><?php echo htmlspecialchars($errors['html_template']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary small d-block">Background Image (Optional)</label>
                                <?php if ($template && !empty($template['background_image'])): ?>
                                    <div class="mb-3 p-3 bg-light border rounded d-flex align-items-center gap-3">
                                        <div class="position-relative bg-dark rounded overflow-hidden" style="width: 100px; height: 70px;">
                                            <img src="<?php echo htmlspecialchars($template['background_image']); ?>" class="w-100 h-100 object-fit-cover" alt="Background thumbnail" />
                                        </div>
                                        <div>
                                            <span class="d-block text-dark small fw-medium">Active Background Image</span>
                                            <span class="text-muted small font-mono d-block text-xs"><?php echo htmlspecialchars($template['background_image']); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="background_image" name="background_image" accept="image/png, image/jpeg, image/jpg">
                                <p class="text-muted small mt-1 mb-0">Recommended resolution: A4 aspect ratio (approx. 2970x2100 px), PNG or JPG format, max size 5MB.</p>
                                <?php if (!empty($errors['background_image'])): ?>
                                    <div class="text-danger small mt-1"><?php echo htmlspecialchars($errors['background_image']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php?route=admin/courses" class="btn btn-light">Cancel</a>
                                <a href="index.php?route=admin/certificates&action=preview&course_id=<?php echo $course['id']; ?>" target="_blank" class="btn btn-outline-info d-inline-flex align-items-center gap-1">
                                    <i data-lucide="eye" size="16"></i> Preview Certificate
                                </a>
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                                    <i data-lucide="save" size="16"></i> Save Template
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Side: Token Cheat Sheet / Guidelines -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="help-circle" class="text-primary"></i>
                            <h5 class="mb-0 fw-semibold">Token Guidelines</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Insert these exact double-curly-bracket placeholder tokens anywhere inside your HTML. They will be substituted with real student and course details at certificate generation time:</p>
                        
                        <div class="list-group list-group-flush border rounded mb-3">
                            <div class="list-group-item py-2.5">
                                <code class="fw-bold text-primary font-mono" style="font-size: 13px;">{{student_name}}</code>
                                <p class="text-muted small mb-0 mt-0.5">The full name of the student who earned the certificate.</p>
                            </div>
                            <div class="list-group-item py-2.5">
                                <code class="fw-bold text-primary font-mono" style="font-size: 13px;">{{course_title}}</code>
                                <p class="text-muted small mb-0 mt-0.5">The full title of the course.</p>
                            </div>
                            <div class="list-group-item py-2.5">
                                <code class="fw-bold text-primary font-mono" style="font-size: 13px;">{{completion_date}}</code>
                                <p class="text-muted small mb-0 mt-0.5">The date the certificate was generated (YYYY-MM-DD).</p>
                            </div>
                            <div class="list-group-item py-2.5">
                                <code class="fw-bold text-primary font-mono" style="font-size: 13px;">{{certificate_number}}</code>
                                <p class="text-muted small mb-0 mt-0.5">A uniquely generated code (e.g. CERT-001-0004-9AF3E4).</p>
                            </div>
                            <div class="list-group-item py-2.5">
                                <code class="fw-bold text-primary font-mono" style="font-size: 13px;">{{final_grade}}</code>
                                <p class="text-muted small mb-0 mt-0.5">The student's final weighted course percentage (e.g. 84.50%).</p>
                            </div>
                        </div>

                        <div class="p-3 bg-light border border-dashed rounded">
                            <h6 class="fw-semibold text-secondary small d-flex align-items-center gap-1.5 mb-1">
                                <i data-lucide="info" size="14"></i> TCPDF Styling Notes
                            </h6>
                            <p class="text-muted small mb-0" style="line-height: 1.4;">
                                PDF rendering uses the standard TCPDF engine. Stick to standard HTML tags (<code>&lt;div&gt;, &lt;table&gt;, &lt;h1&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;img&gt;</code>) and simple inline CSS (like colors, borders, font-size, line-height, text-align, and widths). Avoid advanced flexbox or grid layouts as they are not supported in basic PDF engines.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layout_footer.php';
?>
