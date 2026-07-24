<?php
/**
 * Issued Certificates List View (Admin/Instructor)
 */
$pageTitle = 'Issued Certificates - ' . htmlspecialchars($course['title']);
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
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2" href="index.php?route=admin/certificates&action=template&course_id=<?php echo $course['id']; ?>">
                            <i data-lucide="award" size="16"></i> Certificate Template
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2 active" href="index.php?route=admin/certificates&action=list&course_id=<?php echo $course['id']; ?>">
                            <i data-lucide="scroll" size="16"></i> Issued Certificates
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                <i data-lucide="check-circle" class="text-success"></i>
                <div><?php echo htmlspecialchars($_GET['success']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i data-lucide="alert-triangle" class="text-danger"></i>
                <div><?php echo htmlspecialchars($_GET['error']); ?></div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="award" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Earned Certificates</h5>
                </div>
                <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5"><?php echo count($certificates); ?> Issued</span>
            </div>

            <div class="card-body p-0">
                <?php if (empty($certificates)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="award" class="text-muted d-block mx-auto mb-3" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">No certificates issued yet</h6>
                        <p class="text-muted mb-0 small">Certificates are automatically issued the moment a student completes all gradebook items and meets the passing percentage.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>Certificate ID</th>
                                    <th>Date Issued</th>
                                    <th class="text-end pe-4" style="width: 320px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $c): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($c['student_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($c['student_email']); ?></div>
                                        </td>
                                        <td>
                                            <code class="font-mono text-dark fw-medium" style="font-size: 13px;">
                                                <?php echo htmlspecialchars($c['certificate_number']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <div class="text-dark small"><?php echo date('Y-m-d H:i', strtotime($c['issued_at'])); ?></div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <!-- Download/View PDF Link -->
                                                <a href="index.php?route=admin/certificates&action=download&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" target="_blank">
                                                    <i data-lucide="file-text" size="14"></i> View PDF
                                                </a>

                                                <!-- Regenerate Form with CSRF protection -->
                                                <form action="index.php?route=admin/certificates&action=regenerate" method="POST" class="d-inline-block m-0 p-0">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $c['user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1" onclick="return confirm('Are you sure you want to regenerate this certificate? It will overwrite the existing PDF file with updated template designs, while preserving the certificate code.');">
                                                        <i data-lucide="refresh-cw" size="14"></i> Regenerate
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layout_footer.php';
?>
