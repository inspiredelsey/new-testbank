<?php
$pageTitle = 'Edit Category';
include __DIR__ . '/../layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="edit-3" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Edit Category details</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                        <i data-lucide="alert-circle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form action="index.php?route=admin/categories&action=edit&id=<?php echo $category['id']; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label fw-medium">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label fw-medium">Parent Category</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">-- None (Top Level) --</option>
                            <?php foreach ($flatCategories as $cat): ?>
                                <?php if ($cat['id'] == $category['id']) continue; // Cannot be own parent ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $category['parent_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['indented_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choose parent folder. Setting parent categories lets you build nested trees.</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="index.php?route=admin/categories" class="btn btn-light border d-flex align-items-center gap-2">
                            <i data-lucide="arrow-left" size="18"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i data-lucide="save" size="18"></i> Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
