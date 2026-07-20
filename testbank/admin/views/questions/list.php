<?php
$pageTitle = 'Question Bank';
include __DIR__ . '/../layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Question Bank</h1>
            <p class="text-muted mb-0">Manage all assessment questions, standalone items, and clinical case studies.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?route=admin/questions&action=create" class="btn btn-primary d-flex align-items-center gap-2 px-3">
                <i data-lucide="plus" size="18"></i> Create Standalone Question
            </a>
            <a href="index.php?route=admin/cases&action=create" class="btn btn-outline-primary d-flex align-items-center gap-2 px-3">
                <i data-lucide="plus" size="18"></i> Create Case Study
            </a>
            <a href="index.php?route=admin/cases&action=list" class="btn btn-light border d-flex align-items-center gap-2 px-3">
                <i data-lucide="book-open" size="18"></i> View Case Studies
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
                <input type="hidden" name="route" value="admin/questions">
                <input type="hidden" name="action" value="list">

                <div class="col-md-3">
                    <label for="search" class="form-label fw-semibold text-muted small">Search Question Text</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i data-lucide="search" size="16" class="text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Type keyword...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="category_id" class="form-label fw-semibold text-muted small">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (($_GET['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="type" class="form-label fw-semibold text-muted small">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="mcq_single" <?php echo (($_GET['type'] ?? '') == 'mcq_single') ? 'selected' : ''; ?>>MCQ (Single)</option>
                        <option value="mcq_multi_sata" <?php echo (($_GET['type'] ?? '') == 'mcq_multi_sata') ? 'selected' : ''; ?>>MCQ (SATA)</option>
                        <option value="true_false" <?php echo (($_GET['type'] ?? '') == 'true_false') ? 'selected' : ''; ?>>True/False</option>
                        <option value="matching" <?php echo (($_GET['type'] ?? '') == 'matching') ? 'selected' : ''; ?>>Matching</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="difficulty" class="form-label fw-semibold text-muted small">Difficulty</label>
                    <select class="form-select" id="difficulty" name="difficulty">
                        <option value="">All Difficulty</option>
                        <option value="easy" <?php echo (($_GET['difficulty'] ?? '') == 'easy') ? 'selected' : ''; ?>>Easy</option>
                        <option value="medium" <?php echo (($_GET['difficulty'] ?? '') == 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="hard" <?php echo (($_GET['difficulty'] ?? '') == 'hard') ? 'selected' : ''; ?>>Hard</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                        <i data-lucide="filter" size="16"></i> Filter
                    </button>
                    <a href="index.php?route=admin/questions&action=list" class="btn btn-light border w-100 d-flex align-items-center justify-content-center gap-1">
                        <i data-lucide="rotate-ccw" size="16"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Questions Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($questions)): ?>
                <div class="text-center py-5">
                    <i data-lucide="help-circle" class="text-muted d-block mx-auto mb-3" size="48"></i>
                    <h5 class="text-secondary fw-semibold">No questions found</h5>
                    <p class="text-muted px-4 mb-4">Create some assessment questions or adjust your filtering criteria.</p>
                    <a href="index.php?route=admin/questions&action=create" class="btn btn-primary">Create Question</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 font-sans">
                        <thead class="table-light text-muted small uppercase">
                            <tr>
                                <th class="p-3 ps-4">Question Text</th>
                                <th class="p-3">Category</th>
                                <th class="p-3">Type</th>
                                <th class="p-3 text-center">Difficulty</th>
                                <th class="p-3 text-center">Pts</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3">Created By</th>
                                <th class="p-3 text-center">Case Study</th>
                                <th class="p-3 text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $q): ?>
                                <tr>
                                    <td class="p-3 ps-4" style="max-width: 320px;">
                                        <div class="fw-semibold text-truncate mb-0" title="<?php echo htmlspecialchars($q['question_text']); ?>">
                                            <?php echo htmlspecialchars($q['question_text']); ?>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="text-dark"><?php echo htmlspecialchars($q['category_name'] ?? 'Uncategorized'); ?></span>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(QuestionRenderer::getTypeLabel($q['type'])); ?></span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if ($q['difficulty'] === 'easy'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Easy</span>
                                        <?php elseif ($q['difficulty'] === 'medium'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Medium</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Hard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center fw-semibold text-secondary">
                                        <?php echo floatval($q['points']); ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if ($q['status'] === 'published'): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 small text-muted">
                                        <?php echo htmlspecialchars($q['creator_name'] ?? 'Unknown'); ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if (!empty($q['case_id'])): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle" title="<?php echo htmlspecialchars($q['case_title'] ?? ''); ?>">
                                                Case study
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary px-2 d-flex align-items-center gap-1" 
                                                    onclick="openPreviewModal(<?php echo $q['id']; ?>)">
                                                <i data-lucide="eye" size="14"></i> Preview
                                            </button>
                                            
                                            <?php 
                                            // Check if user is admin or they are the creator of this question
                                            $canEdit = (Auth::user()['role'] === 'admin' || (int)$q['created_by'] === (int)Auth::user()['id']);
                                            if ($canEdit): 
                                            ?>
                                                <a href="index.php?route=admin/questions&action=edit&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-light border px-2">
                                                    <i data-lucide="edit-3" size="14"></i>
                                                </a>
                                                <form action="index.php?route=admin/questions&action=delete&id=<?php echo $q['id']; ?>" method="POST" class="d-inline mb-0" 
                                                      onsubmit="return confirm('Are you sure you want to delete this question? This action is irreversible.');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2">
                                                        <i data-lucide="trash-2" size="14"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small px-2">Read-only</span>
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

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="previewModalLabel">Question Real-time Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light" id="previewModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading preview...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openPreviewModal(questionId) {
    const modalEl = document.getElementById('previewModal');
    const bodyEl = document.getElementById('previewModalBody');
    bodyEl.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading preview...</span>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // Fetch compiled preview HTML from the controller
    fetch(`index.php?route=admin/questions&action=preview&id=${questionId}`)
        .then(response => response.text())
        .then(html => {
            bodyEl.innerHTML = html;
            // Re-render any dynamically introduced Lucide icons in preview
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        })
        .catch(err => {
            bodyEl.innerHTML = `<div class="alert alert-danger">Error loading question preview: ${err}</div>`;
        });
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
