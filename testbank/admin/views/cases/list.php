<?php
$pageTitle = 'Clinical Case Studies';
include __DIR__ . '/../layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Case Studies</h1>
            <p class="text-muted mb-0">Develop multi-tab clinical scenarios mimicking real-world NGN environments.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?route=admin/cases&action=create" class="btn btn-primary d-flex align-items-center gap-2 px-3">
                <i data-lucide="plus" size="18"></i> Create Case Study
            </a>
            <a href="index.php?route=admin/questions&action=list" class="btn btn-light border d-flex align-items-center gap-2 px-3">
                <i data-lucide="help-circle" size="18"></i> View All Questions
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i data-lucide="check-circle" class="text-success"></i>
            <div><?php echo htmlspecialchars($_GET['success']); ?></div>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i data-lucide="alert-circle" class="text-danger"></i>
            <div><?php echo htmlspecialchars($_GET['error']); ?></div>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="index.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="route" value="admin/cases">
                <input type="hidden" name="action" value="list">

                <div class="col-md-8 col-sm-12">
                    <label for="category_id" class="form-label fw-semibold text-muted small">Filter by Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (($_GET['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                        <i data-lucide="filter" size="16"></i> Filter
                    </button>
                    <a href="index.php?route=admin/cases&action=list" class="btn btn-light border w-100 d-flex align-items-center justify-content-center gap-1">
                        <i data-lucide="rotate-ccw" size="16"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Cases List Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($cases)): ?>
                <div class="text-center py-5">
                    <i data-lucide="book-open" class="text-muted d-block mx-auto mb-3" size="48"></i>
                    <h5 class="text-secondary fw-semibold">No Case Studies found</h5>
                    <p class="text-muted px-4 mb-4">Case studies are containers holding background scenarios and clinical exhibits.</p>
                    <a href="index.php?route=admin/cases&action=create" class="btn btn-primary">Create Your First Case</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 font-sans">
                        <thead class="table-light text-muted small uppercase">
                            <tr>
                                <th class="p-3 ps-4">Case Title</th>
                                <th class="p-3">Category</th>
                                <th class="p-3 text-center">Trend Case</th>
                                <th class="p-3 text-center">Exhibits (Tabs)</th>
                                <th class="p-3 text-center">Questions Attached</th>
                                <th class="p-3">Created By</th>
                                <th class="p-3">Created At</th>
                                <th class="p-3 text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cases as $c): ?>
                                <tr>
                                    <td class="p-3 ps-4 fw-semibold text-dark">
                                        <?php echo htmlspecialchars($c['title']); ?>
                                    </td>
                                    <td class="p-3 text-secondary">
                                        <?php echo htmlspecialchars($c['category_name'] ?? 'Uncategorized'); ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if (!empty($c['is_trend'])): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i data-lucide="trending-up" size="12" class="me-1"></i> Yes (Trend)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-6 px-2.5">
                                            <?php echo (int)$c['exhibit_count']; ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-2.5">
                                            <?php echo (int)$c['question_count']; ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-muted small">
                                        <?php echo htmlspecialchars($c['creator_name'] ?? 'Unknown'); ?>
                                    </td>
                                    <td class="p-3 text-muted small">
                                        <?php echo date('Y-m-d H:i', strtotime($c['created_at'])); ?>
                                    </td>
                                    <td class="p-3 text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="index.php?route=admin/cases&action=exhibits&case_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                                                <i data-lucide="folder-open" size="14"></i> Exhibits
                                            </a>
                                            <a href="index.php?route=admin/cases&action=attach&case_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-info d-flex align-items-center gap-1">
                                                <i data-lucide="link" size="14"></i> Attach Qs
                                            </a>
                                            
                                            <?php 
                                            $canManage = (Auth::user()['role'] === 'admin' || (int)$c['created_by'] === (int)Auth::user()['id']);
                                            if ($canManage): 
                                            ?>
                                                <a href="index.php?route=admin/cases&action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-light border">
                                                    <i data-lucide="edit-3" size="14"></i>
                                                </a>
                                                <form action="index.php?route=admin/cases&action=delete&id=<?php echo $c['id']; ?>" method="POST" class="d-inline mb-0" 
                                                      onsubmit="return confirm('Are you sure you want to delete this Case Study? This will succeed only if no questions remain attached.');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i data-lucide="trash-2" size="14"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

<?php include __DIR__ . '/../layout_footer.php'; ?>
