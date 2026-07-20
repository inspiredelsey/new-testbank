<?php
/**
 * Exams List View
 */
$pageTitle = 'Manage Exams';
include __DIR__ . '/../layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-0 display-font">Exams Workspace</h1>
        <p class="text-muted mb-0">Create and manage exams, configure metadata, and build robust question sets.</p>
    </div>
    <a href="index.php?route=admin/exams&action=create" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2" id="btn-create-exam">
        <i data-lucide="plus-circle" size="18"></i>
        <span>Create Exam</span>
    </a>
</div>

<!-- Alerts -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="check-circle" class="text-success"></i>
            <div><?php echo htmlspecialchars($_GET['success']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="alert-triangle" class="text-danger"></i>
            <div><?php echo htmlspecialchars($_GET['error']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4" id="filters-card">
    <div class="card-body p-3">
        <form method="GET" action="index.php" class="row g-2">
            <input type="hidden" name="route" value="admin/exams">
            <input type="hidden" name="action" value="list">
            
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i data-lucide="search" size="18"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search exam title..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="course_id" class="form-select">
                    <option value="">-- All Courses --</option>
                    <option value="none" <?php echo (($_GET['course_id'] ?? '') === 'none') ? 'selected' : ''; ?>>None (Reusable Bank Quizzes)</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo (($_GET['course_id'] ?? '') == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- All Statuses --</option>
                    <option value="draft" <?php echo (($_GET['status'] ?? '') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo (($_GET['status'] ?? '') === 'published') ? 'selected' : ''; ?>>Published</option>
                    <option value="archived" <?php echo (($_GET['status'] ?? '') === 'archived') ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary d-inline-flex align-items-center justify-content-center gap-1.5" id="btn-filter">
                    <i data-lucide="filter" size="16"></i>
                    <span>Filter</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Exams Table -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden" id="exams-list-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light border-bottom text-muted">
                <tr>
                    <th class="ps-4 py-3 font-sans small text-uppercase fw-semibold" style="width: 30%;">Exam details</th>
                    <th class="py-3 font-sans small text-uppercase fw-semibold" style="width: 20%;">Associated course</th>
                    <th class="py-3 font-sans small text-uppercase fw-semibold" style="width: 12%;">Category</th>
                    <th class="py-3 font-sans small text-uppercase fw-semibold" style="width: 15%;">Questions & Grading</th>
                    <th class="py-3 font-sans small text-uppercase fw-semibold" style="width: 10%;">Status</th>
                    <th class="pe-4 py-3 font-sans small text-uppercase fw-semibold text-end" style="width: 13%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i data-lucide="folder-open" size="48" class="opacity-25 mb-2"></i>
                                <p class="mb-1 fw-medium">No exams found</p>
                                <p class="small text-muted mb-0">Try expanding your filters or create a new exam above.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $ex): ?>
                        <?php 
                        $stats = $examStats[$ex['id']] ?? ['fixed_count' => 0, 'rules_count' => 0, 'total_count' => 0];
                        ?>
                        <tr id="exam-row-<?php echo $ex['id']; ?>">
                            <!-- Details -->
                            <td class="ps-4 py-3.5">
                                <div class="fw-semibold text-dark fs-6"><?php echo htmlspecialchars($ex['title']); ?></div>
                                <div class="text-muted small mt-1 d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-light text-dark border font-sans text-uppercase px-2 py-1" style="font-size: 0.65rem;">
                                        <?php echo htmlspecialchars($ex['gradebook_category'] ?? 'summative'); ?>
                                    </span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i data-lucide="clock" size="12"></i>
                                        <?php echo intval($ex['duration_minutes']); ?> mins
                                    </span>
                                    <span>&bull;</span>
                                    <span>Max attempts: <?php echo $ex['max_attempts'] == 0 ? 'Unlimited' : intval($ex['max_attempts']); ?></span>
                                </div>
                                <?php if (!empty($ex['start_date']) || !empty($ex['end_date'])): ?>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        <i data-lucide="calendar" size="12" class="me-1"></i>
                                        Availability: 
                                        <?php echo !empty($ex['start_date']) ? date('M d, Y H:i', strtotime($ex['start_date'])) : 'Open'; ?>
                                        &rarr;
                                        <?php echo !empty($ex['end_date']) ? date('M d, Y H:i', strtotime($ex['end_date'])) : 'Open'; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <!-- Associated Course -->
                            <td class="py-3.5">
                                <?php if ($ex['course_id']): ?>
                                    <span class="text-dark fw-medium"><?php echo htmlspecialchars($ex['course_title'] ?? 'Course ID: ' . $ex['course_id']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted font-sans italic small">None (Reusable Bank Quiz)</span>
                                <?php endif; ?>
                            </td>
                            <!-- Category -->
                            <td class="py-3.5">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis px-2.5 py-1.5 font-sans" style="font-size: 0.75rem;">
                                    <?php echo htmlspecialchars($ex['category_name'] ?? 'General'); ?>
                                </span>
                            </td>
                            <!-- Questions & Grading -->
                            <td class="py-3.5">
                                <div class="fw-medium text-dark">
                                    <?php echo $stats['total_count']; ?> Questions
                                </div>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    (<?php echo $stats['fixed_count']; ?> fixed, <?php echo $stats['rules_count']; ?> random-pull)
                                </div>
                                <div class="mt-1 small text-muted d-flex align-items-center gap-1">
                                    <i data-lucide="award" size="12"></i>
                                    Pass: <?php echo floatval($ex['pass_percentage']); ?>%
                                </div>
                            </td>
                            <!-- Status -->
                            <td class="py-3.5">
                                <?php if ($ex['status'] === 'published'): ?>
                                    <span class="badge bg-success text-white font-sans text-uppercase px-2 py-1" style="font-size: 0.7rem;">Published</span>
                                <?php elseif ($ex['status'] === 'archived'): ?>
                                    <span class="badge bg-danger text-white font-sans text-uppercase px-2 py-1" style="font-size: 0.7rem;">Archived</span>
                                <?php else: ?>
                                    <span class="badge bg-dark-subtle text-dark font-sans text-uppercase px-2 py-1" style="font-size: 0.7rem;">Draft</span>
                                <?php endif; ?>
                            </td>
                            <!-- Actions -->
                            <td class="pe-4 py-3.5 text-end">
                                <div class="d-inline-flex gap-1">
                                    <!-- Build Questions -->
                                    <a href="index.php?route=admin/exams&action=build&id=<?php echo $ex['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm px-2.5 py-1.5 d-inline-flex align-items-center gap-1"
                                       title="Build Questions"
                                       id="btn-build-<?php echo $ex['id']; ?>">
                                        <i data-lucide="cog" size="15"></i>
                                        <span class="small">Questions</span>
                                    </a>
                                    
                                    <!-- Action dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="btn-dropdown-<?php echo $ex['id']; ?>">
                                            <i data-lucide="more-vertical" size="16"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-light" style="font-size: 0.875rem;">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="index.php?route=admin/exams&action=preview&id=<?php echo $ex['id']; ?>">
                                                    <i data-lucide="eye" size="16" class="text-muted"></i> Preview Resolved Set
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="index.php?route=admin/exams&action=edit&id=<?php echo $ex['id']; ?>">
                                                    <i data-lucide="edit-3" size="16" class="text-muted"></i> Edit Configuration
                                                </a>
                                            </li>
                                            
                                            <li class="dropdown-divider"></li>
                                            <li><h6 class="dropdown-header font-sans text-uppercase small text-muted" style="font-size: 0.65rem;">Change Status</h6></li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="index.php?route=admin/exams&action=status&id=<?php echo $ex['id']; ?>&status=draft">
                                                    <i data-lucide="file-text" size="16" class="text-muted"></i> Draft Status
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="index.php?route=admin/exams&action=status&id=<?php echo $ex['id']; ?>&status=published">
                                                    <i data-lucide="send" size="16" class="text-success"></i> Publish Status
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="index.php?route=admin/exams&action=status&id=<?php echo $ex['id']; ?>&status=archived">
                                                    <i data-lucide="archive" size="16" class="text-danger"></i> Archive Status
                                                </a>
                                            </li>
                                            
                                            <li class="dropdown-divider"></li>
                                            <li>
                                                <form action="index.php?route=admin/exams&action=delete" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this exam? All fixed associations and random pull rules will be removed.');" class="m-0">
                                                    <input type="hidden" name="id" value="<?php echo $ex['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 w-100">
                                                        <i data-lucide="trash-2" size="16" class="text-danger"></i> Delete Exam
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
