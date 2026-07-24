<?php
/**
 * Student My Certificates View
 */
$pageTitle = 'My Certificates';
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="display-font fw-bold text-dark mb-1">My Certificates</h3>
                <p class="text-muted small mb-0">View and download certificates you have earned for successfully completing courses.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="award" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">My Achievements</h5>
                </div>
                <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5"><?php echo count($certificates); ?> Certificates</span>
            </div>

            <div class="card-body p-0">
                <?php if (empty($certificates)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="award" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">No certificates earned yet</h6>
                        <p class="text-muted mb-0 small" style="max-width: 450px; margin: 0 auto;">
                            Complete all gradebook items for your courses and maintain a passing final grade (typically 70% or higher) to automatically receive your certificates of completion.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Course</th>
                                    <th>Certificate ID</th>
                                    <th>Date Issued</th>
                                    <th class="text-end pe-4" style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $c): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($c['course_title']); ?></div>
                                        </td>
                                        <td>
                                            <code class="font-mono text-dark fw-medium" style="font-size: 13px;">
                                                <?php echo htmlspecialchars($c['certificate_number']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <div class="text-dark small"><?php echo date('Y-m-d', strtotime($c['issued_at'])); ?></div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="index.php?route=student/certificates&action=download&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" target="_blank">
                                                <i data-lucide="download" size="14"></i> Download PDF
                                            </a>
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
include __DIR__ . '/../../../admin/views/layout_footer.php';
?>
