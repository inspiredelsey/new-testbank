<?php
/**
 * Category Hierarchy Tree View - Test Bank LMS
 */
$pageTitle = 'Category Hierarchy';
include __DIR__ . '/../layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="folder-tree" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Categories hierarchy</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5"><?php echo $totalCategoriesCount; ?> Total</span>
                    <a href="index.php?route=admin/categories&action=create" class="btn btn-primary btn-sm d-flex align-items-center gap-1.5">
                        <i data-lucide="plus" size="16"></i> Add Category
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                
                <?php if (empty($categoryTree)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="folder-open" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">No categories found</h6>
                        <p class="text-muted mb-4 small">Create a category to begin grouping courses and questions.</p>
                        <a href="index.php?route=admin/categories&action=create" class="btn btn-primary btn-sm">
                            <i data-lucide="plus" size="16"></i> Create First Category
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Category Name</th>
                                    <th style="width: 220px;">Slug</th>
                                    <th>Description</th>
                                    <th style="width: 180px;" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                /**
                                 * Recursive function to render tree rows with nested indentation
                                 */
                                function renderRecursiveTree($nodes, $depth = 0, $csrfToken) {
                                    foreach ($nodes as $node) {
                                        // 8 spaces per indentation level for beautiful, clear visual hierarchy
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                                        $icon = $depth > 0 
                                            ? '<i data-lucide="corner-down-right" class="text-slate-400 me-1.5" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>' 
                                            : '<i data-lucide="folder" class="text-primary me-1.5" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;"></i>';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span><?php echo $indent; ?></span>
                                                    <span><?php echo $icon; ?></span>
                                                    <span class="fw-semibold text-slate-800"><?php echo htmlspecialchars($node['name'] ?? ''); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="text-slate-500 font-mono bg-light px-1.5 py-0.5 rounded border" style="font-size: 0.8rem;"><?php echo htmlspecialchars($node['slug'] ?? ''); ?></code>
                                            </td>
                                            <td class="text-slate-600">
                                                <small class="text-truncate d-inline-block text-muted" style="max-width: 320px;" title="<?php echo htmlspecialchars($node['description'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($node['description'] ?? '—'); ?>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1.5">
                                                    <a href="index.php?route=admin/categories&action=edit&id=<?php echo $node['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Category">
                                                        <i data-lucide="edit-3" size="14" style="width: 14px; height: 14px;"></i> Edit
                                                    </a>
                                                    <a href="index.php?route=admin/categories&action=delete&id=<?php echo $node['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       title="Delete Category"
                                                       onclick="return confirmDelete(event, '<?php echo htmlspecialchars(addslashes($node['name'] ?? '')); ?>')">
                                                        <i data-lucide="trash-2" size="14" style="width: 14px; height: 14px;"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                        if (!empty($node['children'])) {
                                            renderRecursiveTree($node['children'], $depth + 1, $csrfToken);
                                        }
                                    }
                                }

                                renderRecursiveTree($categoryTree, 0, $csrfToken);
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(event, name) {
    if (!confirm('Are you sure you want to delete the category "' + name + '"? This will permanently remove it if it has no dependencies.')) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php
include __DIR__ . '/../layout_footer.php';
?>
