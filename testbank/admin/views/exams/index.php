<?php
$pageTitle = 'Exam Builder';
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
        <h5 class="text-muted mb-0 font-sans">Draft and publish exams for your student courses.</h5>
        <a href="index.php?route=admin/exams&action=create" class="btn btn-primary d-flex align-items-center gap-2">
            <i data-lucide="plus" size="18"></i> Create New Exam
        </a>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="file-spreadsheet" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Course Exams Workspace</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($exams)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="file-text" class="text-muted d-block mx-auto mb-3" size="48"></i>
                        <p class="text-muted">No exams created yet. Build an exam above to get started.</p>
                        <a href="index.php?route=admin/exams&action=create" class="btn btn-primary btn-sm">Create Exam</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Exam Details</th>
                                    <th>Category</th>
                                    <th>Settings</th>
                                    <th>Availability Window</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4" style="min-width: 280px;">Manage & Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $e): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($e['title']); ?></div>
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                                <?php echo htmlspecialchars($e['description'] ?? 'No description provided.'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($e['category_name'] ?? 'Uncategorized'); ?></span>
                                        </td>
                                        <td>
                                            <div class="small text-muted font-sans d-flex flex-column gap-1">
                                                <span><i data-lucide="clock" size="12" class="me-1"></i><?php echo $e['duration_minutes']; ?> mins</span>
                                                <span><i data-lucide="award" size="12" class="me-1"></i>Pass: <?php echo floatval($e['pass_percentage']); ?>%</span>
                                                <span><i data-lucide="refresh-cw" size="12" class="me-1"></i>Max Attempts: <?php echo $e['max_attempts'] > 0 ? $e['max_attempts'] : '∞'; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small text-muted font-sans d-flex flex-column gap-1">
                                                <span>Start: <?php echo $e['start_date'] ? date('M d, Y H:i', strtotime($e['start_date'])) : 'Anytime'; ?></span>
                                                <span>End: <?php echo $e['end_date'] ? date('M d, Y H:i', strtotime($e['end_date'])) : 'Anytime'; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClasses = [
                                                'draft' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                                'published' => 'bg-success-subtle text-success border-success-subtle',
                                                'archived' => 'bg-danger-subtle text-danger border-danger-subtle'
                                            ];
                                            $class = $statusClasses[$e['status']] ?? 'bg-light text-dark';
                                            ?>
                                            <span class="badge border <?php echo $class; ?> text-capitalize"><?php echo $e['status']; ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex gap-1 justify-content-end align-items-center">
                                                <a href="index.php?route=admin/exams&action=questions&id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" title="Assign Questions">
                                                    <i data-lucide="help-circle" size="14"></i> Questions
                                                </a>
                                                <a href="index.php?route=admin/exams&action=rules&id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-info d-flex align-items-center gap-1" title="Random Pull Rules">
                                                    <i data-lucide="shuffle" size="14"></i> Rules
                                                </a>
                                                <a href="index.php?route=admin/exams&action=preview&id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1" title="Student Preview">
                                                    <i data-lucide="eye" size="14"></i> Preview
                                                </a>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        More
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="index.php?route=admin/exams&action=edit&id=<?php echo $e['id']; ?>"><i data-lucide="settings" size="14"></i> Settings</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="index.php?route=admin/exams&action=delete&id=<?php echo $e['id']; ?>&csrf_token=<?php echo Session::getCSRFToken(); ?>" 
                                                               onclick="return confirm('Are you sure you want to delete this exam? All student attempts and statistics for this exam will be deleted permanently.')">
                                                                <i data-lucide="trash" size="14"></i> Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
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

<?php include __DIR__ . '/../layout_footer.php'; ?>
