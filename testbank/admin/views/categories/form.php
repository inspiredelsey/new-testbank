<?php
/**
 * Shared Create/Edit Category Form - Test Bank LMS
 */
$pageTitle = $title;
include __DIR__ . '/../layout_header.php';

// Prepare variables
$selectedParentId = $formData['parent_id'] ?? ($isEdit ? ($category['parent_id'] ?? '') : '');
$currentName = $formData['name'] ?? ($isEdit ? ($category['name'] ?? '') : '');
$currentDescription = $formData['description'] ?? ($isEdit ? ($category['description'] ?? '') : '');
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="<?php echo $isEdit ? 'edit-3' : 'plus-circle'; ?>" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold"><?php echo htmlspecialchars($title); ?></h5>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i data-lucide="alert-circle" class="text-danger" style="width: 18px; height: 18px;"></i>
                            <span class="fw-semibold text-danger">Please resolve the following:</span>
                        </div>
                        <ul class="mb-0 ps-3 small text-danger">
                            <?php foreach ($errors as $field => $msg): ?>
                                <li><?php echo htmlspecialchars($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($submitUrl); ?>" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" name="name" 
                               value="<?php echo htmlspecialchars($currentName); ?>" 
                               maxlength="150" required 
                               placeholder="e.g. Inorganic Chemistry, Anatomy, Web Design">
                        <div class="form-text text-muted small">Unique name, maximum 150 characters.</div>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="parent_id" class="form-label fw-semibold">Parent Category</label>
                        <select class="form-select <?php echo isset($errors['parent_id']) ? 'is-invalid' : ''; ?>" id="parent_id" name="parent_id">
                            <option value="">-- None (Top Level) --</option>
                            <?php foreach ($flatCategories as $cat): ?>
                                <?php 
                                // Exclude category itself and its descendants from parent options to prevent circular reference
                                if (in_array(intval($cat['id']), $excludeIds)) {
                                    continue;
                                }
                                ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (intval($cat['id']) === intval($selectedParentId)) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['indented_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted small">Nesting can be set recursively to organize your courses and questions.</div>
                        <?php if (isset($errors['parent_id'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['parent_id']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                  id="description" name="description" 
                                  rows="4" placeholder="Describe the focus or topic of this category..."><?php echo htmlspecialchars($currentDescription); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['description']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="index.php?route=admin/categories&action=list" class="btn btn-light border d-flex align-items-center gap-1.5">
                            <i data-lucide="arrow-left" size="16"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1.5">
                            <i data-lucide="save" size="16"></i> <?php echo $isEdit ? 'Save Changes' : 'Create Category'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layout_footer.php';
?>
