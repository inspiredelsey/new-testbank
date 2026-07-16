<?php
$pageTitle = 'Category Management';
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <!-- Category Creation Form -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="plus-circle" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Add New Category</h5>
            </div>
            <div class="card-body p-4">
                <form action="index.php?route=admin/categories&action=create" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label fw-medium">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Organic Chemistry">
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label fw-medium">Parent Category (Optional)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">-- None (Top Level) --</option>
                            <?php foreach ($flatCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['indented_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choose a parent to build nested hierarchies.</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Brief details about questions in this category..."></textarea>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="save" size="18"></i> Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Tree View -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="folder-tree" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Category Hierarchy</h5>
                </div>
                <span class="badge bg-light text-dark border fw-medium"><?php echo count($flatCategories); ?> Total</span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($categoryTree)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="folder-open" class="text-muted d-block mx-auto mb-3" size="48"></i>
                        <p class="text-muted">No categories created yet. Create one on the left.</p>
                    </div>
                <?php else: ?>
                    <!-- Collapsible Tree Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Category Name</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th class="text-end" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                function renderTreeRows($nodes, $depth = 0) {
                                    foreach ($nodes as $node) {
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                                        $icon = $depth > 0 ? '<i data-lucide="corner-down-right" class="text-muted me-1" size="14"></i>' : '<i data-lucide="folder" class="text-primary me-1" size="16"></i>';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span><?php echo $indent; ?></span>
                                                    <span><?php echo $icon; ?></span>
                                                    <span class="fw-medium text-dark"><?php echo htmlspecialchars($node['name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-muted font-mono"><?php echo htmlspecialchars($node['slug']); ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                                    <?php echo htmlspecialchars($node['description'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <a href="index.php?route=admin/categories&action=edit&id=<?php echo $node['id']; ?>" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                                                        <i data-lucide="edit-3" size="14"></i> Edit
                                                    </a>
                                                    <a href="index.php?route=admin/categories&action=delete&id=<?php echo $node['id']; ?>&csrf_token=<?php echo Session::getCSRFToken(); ?>" 
                                                       onclick="return confirm('Are you sure you want to delete this category? All subcategories will lose their parent.')" 
                                                       class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                                                        <i data-lucide="trash-2" size="14"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                        if (!empty($node['children'])) {
                                            renderTreeRows($node['children'], $depth + 1);
                                        }
                                    }
                                }
                                renderTreeRows($categoryTree);
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
