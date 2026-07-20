<?php
$pageTitle = 'Case Exhibits - ' . htmlspecialchars($case['title']);
include __DIR__ . '/../layout_header.php';
?>

<div class="container-fluid py-4">
    <!-- Header Block -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="index.php?route=admin/cases&action=list" class="btn btn-light border p-2 rounded-3 d-flex align-items-center">
            <i data-lucide="arrow-left" size="18" class="text-muted"></i>
        </a>
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Manage Exhibit Tabs</h1>
            <p class="text-muted mb-0">Case Study: <strong class="text-dark"><?php echo htmlspecialchars($case['title']); ?></strong></p>
        </div>
    </div>

    <!-- Feedback alerts -->
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

    <div class="row">
        <!-- Left Side: Existing exhibits list & reordering -->
        <div class="col-xl-7 col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold text-secondary d-flex align-items-center gap-2">
                        <i data-lucide="tabs" class="text-primary"></i> Current Exhibit Tabs
                    </h5>
                    <span class="badge bg-secondary"><?php echo count($exhibits); ?> Active Tab(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($exhibits)): ?>
                        <div class="text-center py-5">
                            <i data-lucide="layout" class="text-muted d-block mx-auto mb-3" size="48"></i>
                            <p class="text-muted">No exhibits created yet. Use the form on the right to add some.</p>
                        </div>
                    <?php else: ?>
                        <!-- List of Exhibits with controls -->
                        <div class="list-group list-group-flush" id="exhibitsOrderList">
                            <?php foreach ($exhibits as $idx => $ex): ?>
                                <div class="list-group-item p-4 d-flex gap-3 align-items-start exhibit-item-card" data-id="<?php echo $ex['id']; ?>">
                                    <!-- Reordering Arrows -->
                                    <div class="d-flex flex-column align-items-center gap-1 bg-light border p-1.5 rounded-2">
                                        <button class="btn btn-link p-0 text-secondary move-up-btn" onclick="moveUp(this)" title="Move Up" <?php echo ($idx === 0) ? 'disabled style="opacity: 0.3;"' : ''; ?>>
                                            <i data-lucide="chevron-up" size="18"></i>
                                        </button>
                                        <span class="small fw-bold text-muted font-mono"><?php echo ($idx + 1); ?></span>
                                        <button class="btn btn-link p-0 text-secondary move-down-btn" onclick="moveDown(this)" title="Move Down" <?php echo ($idx === count($exhibits) - 1) ? 'disabled style="opacity: 0.3;"' : ''; ?>>
                                            <i data-lucide="chevron-down" size="18"></i>
                                        </button>
                                    </div>

                                    <!-- Exhibit content brief -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($ex['tab_label']); ?></h6>
                                            <?php if (!empty($ex['timestamp_label'])): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-mono small">
                                                    <i data-lucide="clock" size="10" class="me-1"></i><?php echo htmlspecialchars($ex['timestamp_label']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-muted small mb-0 text-truncate" style="max-width: 450px;">
                                            <?php echo htmlspecialchars(substr($ex['content'], 0, 150)) . (strlen($ex['content']) > 150 ? '...' : ''); ?>
                                        </p>
                                    </div>

                                    <!-- Edit & Delete Buttons -->
                                    <div class="d-flex gap-1 align-self-center">
                                        <button type="button" class="btn btn-sm btn-light border px-2.5" 
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($ex)); ?>)">
                                            <i data-lucide="edit-3" size="14"></i> Edit
                                        </button>
                                        <form action="index.php?route=admin/cases&action=delete_exhibit&id=<?php echo $ex['id']; ?>" method="POST" class="d-inline mb-0"
                                              onsubmit="return confirm('Delete this exhibit tab permanently?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2.5">
                                                <i data-lucide="trash-2" size="14"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Quick Order Save button -->
                        <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><i data-lucide="info" size="14" class="me-1"></i> Reorder with buttons, then click save.</span>
                            <button id="saveOrderBtn" class="btn btn-sm btn-primary d-flex align-items-center gap-1" onclick="saveNewOrder()" disabled>
                                <i data-lucide="save" size="14"></i> Save New Order
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Create Exhibit Tab -->
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold text-secondary d-flex align-items-center gap-2">
                        <i data-lucide="plus-circle" class="text-success"></i> Add Exhibit Tab
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="index.php?route=admin/cases&action=add_exhibit" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">

                        <div class="mb-3">
                            <label for="tab_label" class="form-label fw-semibold text-muted small">Tab Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tab_label" name="tab_label" required placeholder="e.g. Nurse's Notes, Vitals, Labs">
                        </div>

                        <div class="mb-3">
                            <label for="timestamp_label" class="form-label fw-semibold text-muted small">Time/Sequence Stamp (Optional)</label>
                            <input type="text" class="form-control" id="timestamp_label" name="timestamp_label" placeholder="e.g. 08:30, Day 1, On Admission">
                            <span class="text-muted small">Useful for Trend Case Studies showing progressive timelines.</span>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label fw-semibold text-muted small">Exhibit Content (HTML or Text) <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="10" required placeholder="Enter clinical vitals chart, lab diagnostics, nurse notes narrative..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="plus" size="16"></i> Add Exhibit Tab
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Exhibit Modal -->
<div class="modal fade" id="editExhibitModal" tabindex="-1" aria-labelledby="editExhibitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="editExhibitModalLabel">Edit Exhibit Tab</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form action="index.php?route=admin/cases&action=edit_exhibit" method="POST" id="editExhibitForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="id" id="edit_ex_id">

                    <div class="mb-3">
                        <label for="edit_tab_label" class="form-label fw-semibold text-muted small">Tab Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_tab_label" name="tab_label" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_timestamp_label" class="form-label fw-semibold text-muted small">Time/Sequence Stamp (Optional)</label>
                        <input type="text" class="form-control" id="edit_timestamp_label" name="timestamp_label">
                    </div>

                    <div class="mb-4">
                        <label for="edit_content" class="form-label fw-semibold text-muted small">Exhibit Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_content" name="content" rows="10" required></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border w-100" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let hasOrderChanged = false;

function moveUp(btn) {
    const item = btn.closest('.exhibit-item-card');
    const prevItem = item.previousElementSibling;
    if (prevItem) {
        item.parentNode.insertBefore(item, prevItem);
        markOrderChanged();
    }
}

function moveDown(btn) {
    const item = btn.closest('.exhibit-item-card');
    const nextItem = item.nextElementSibling;
    if (nextItem) {
        item.parentNode.insertBefore(nextItem, item);
        markOrderChanged();
    }
}

function markOrderChanged() {
    hasOrderChanged = true;
    document.getElementById('saveOrderBtn').disabled = false;
    
    // Re-index displayed index numbers & arrow states
    const items = document.querySelectorAll('#exhibitsOrderList .exhibit-item-card');
    items.forEach((item, idx) => {
        item.querySelector('.font-mono').textContent = idx + 1;
        
        const upBtn = item.querySelector('.move-up-btn');
        const downBtn = item.querySelector('.move-down-btn');
        
        upBtn.disabled = (idx === 0);
        upBtn.style.opacity = (idx === 0) ? '0.3' : '1';
        
        downBtn.disabled = (idx === items.length - 1);
        downBtn.style.opacity = (idx === items.length - 1) ? '0.3' : '1';
    });
}

function saveNewOrder() {
    const items = document.querySelectorAll('#exhibitsOrderList .exhibit-item-card');
    const orderedIds = Array.from(items).map(item => item.dataset.id);
    
    const saveBtn = document.getElementById('saveOrderBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...`;

    const formData = new FormData();
    formData.append('csrf_token', <?php echo json_encode($csrfToken); ?>);
    formData.append('case_id', <?php echo json_encode($case['id']); ?>);
    orderedIds.forEach(id => formData.append('ordered_ids[]', id));

    fetch('index.php?route=admin/cases&action=reorder_exhibits', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Exhibit order saved successfully.');
            location.reload();
        } else {
            alert('Error saving order: ' + (data.error || 'Unknown error'));
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i data-lucide="save" size="14"></i> Save New Order`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    })
    .catch(err => {
        alert('Request failed: ' + err);
        saveBtn.disabled = false;
        saveBtn.innerHTML = `<i data-lucide="save" size="14"></i> Save New Order`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
}

function openEditModal(exhibit) {
    document.getElementById('edit_ex_id').value = exhibit.id;
    document.getElementById('edit_tab_label').value = exhibit.tab_label;
    document.getElementById('edit_timestamp_label').value = exhibit.timestamp_label || '';
    document.getElementById('edit_content').value = exhibit.content;
    
    const modalEl = document.getElementById('editExhibitModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
