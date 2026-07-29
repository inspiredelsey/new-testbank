<?php
/**
 * Create/Edit Course Form - Test Bank LMS
 */
$pageTitle = $title;
include __DIR__ . '/../layout_header.php';
?>

<div class="row mb-5">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <a href="index.php?route=admin/courses&action=list" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center p-1.5 rounded-3" title="Back to Courses">
                    <i data-lucide="arrow-left" size="16"></i>
                </a>
                <h5 class="mb-0 fw-semibold"><?php echo $title; ?></h5>
            </div>
            <div class="card-body p-4">
                
                <!-- Display general DB errors -->
                <?php if (!empty($errors['db'])): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="alert-triangle" class="text-danger"></i>
                            <div class="small"><?php echo htmlspecialchars($errors['db']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo $submitUrl; ?>" enctype="multipart/form-data" class="needs-validation">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">

                    <!-- Title -->
                    <div class="mb-4">
                        <label for="courseTitle" class="form-label">Course Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="title" 
                               id="courseTitle" 
                               maxlength="200"
                               class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                               placeholder="e.g. Introduction to Nursing & Medical Terminology"
                               value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>" 
                               required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['title']); ?></div>
                        <?php endif; ?>
                        <small class="text-muted font-sans small mt-1 d-block">Max 200 characters. Keep it clear and descriptive.</small>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="courseDescription" class="form-label">Description</label>
                        <textarea name="description" 
                                  id="courseDescription" 
                                  rows="4" 
                                  class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                  placeholder="Describe the course goals, target audience, and overview..."><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['description']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <!-- Category selection -->
                        <div class="col-md-6 mb-4">
                            <label for="courseCategory" class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" 
                                    id="courseCategory" 
                                    class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" 
                                    required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($flatCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($formData['category_id']) && (int)$formData['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['indented_name'] ?? $cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['category_id']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Passing percentage -->
                        <div class="col-md-6 mb-4">
                            <label for="coursePassPercentage" class="form-label">Required Passing Score (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" 
                                       name="pass_percentage" 
                                       id="coursePassPercentage" 
                                       min="0" 
                                       max="100" 
                                       step="0.1"
                                       class="form-control <?php echo isset($errors['pass_percentage']) ? 'is-invalid' : ''; ?>" 
                                       placeholder="e.g. 75"
                                       value="<?php echo htmlspecialchars($formData['pass_percentage'] ?? '50'); ?>" 
                                       required>
                                <span class="input-group-text bg-light text-muted font-mono">%</span>
                                <?php if (isset($errors['pass_percentage'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['pass_percentage']); ?></div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted font-sans small mt-1 d-block">Required passing score for exams within this course.</small>
                        </div>

                        <!-- Price -->
                        <div class="col-md-8 mb-4">
                            <label for="coursePrice" class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                       name="price"
                                       id="coursePrice"
                                       min="0"
                                       step="0.01"
                                       class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>"
                                       placeholder="e.g. 49.99"
                                       value="<?php echo htmlspecialchars($formData['price'] ?? '0.00'); ?>"
                                       required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['price']); ?></div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted font-sans small mt-1 d-block">Students pay this amount to enroll.</small>
                        </div>

                        <!-- Currency -->
                        <div class="col-md-4 mb-4">
                            <label for="courseCurrency" class="form-label">Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="courseCurrency" class="form-select <?php echo isset($errors['currency']) ? 'is-invalid' : ''; ?>">
                                <?php
                                $currentCurrency = $formData['currency'] ?? 'USD';
                                foreach (['USD', 'EUR', 'GBP', 'NGN'] as $curr):
                                ?>
                                    <option value="<?php echo $curr; ?>" <?php echo ($currentCurrency === $curr) ? 'selected' : ''; ?>><?php echo $curr; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['currency'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['currency']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <!-- Instructor selection -->
                        <div class="col-md-6 mb-4">
                            <?php if ($currentUser['role'] === 'instructor'): ?>
                                <label class="form-label">Course Instructor</label>
                                <div class="p-2.5 bg-light rounded border text-slate-700 font-sans small d-flex align-items-center gap-2">
                                    <i data-lucide="user-check" size="16" class="text-primary"></i>
                                    <span><?php echo htmlspecialchars($currentUser['name']); ?> (You)</span>
                                </div>
                                <input type="hidden" name="instructor_id" value="<?php echo $currentUser['id']; ?>">
                            <?php else: ?>
                                <label for="courseInstructor" class="form-label">Course Instructor <span class="text-danger">*</span></label>
                                <select name="instructor_id" 
                                        id="courseInstructor" 
                                        class="form-select <?php echo isset($errors['instructor_id']) ? 'is-invalid' : ''; ?>" 
                                        required>
                                    <option value="">-- Assign Instructor --</option>
                                    <?php foreach ($instructors as $inst): ?>
                                        <option value="<?php echo $inst['id']; ?>" <?php echo (isset($formData['instructor_id']) && (int)$formData['instructor_id'] === (int)$inst['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($inst['name']); ?> (<?php echo ucfirst($inst['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['instructor_id'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['instructor_id']); ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Status selection -->
                        <div class="col-md-6 mb-4">
                            <label for="courseStatus" class="form-label">Course Status <span class="text-danger">*</span></label>
                            <select name="status" 
                                    id="courseStatus" 
                                    class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" 
                                    required>
                                <option value="draft" <?php echo (isset($formData['status']) && $formData['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo (isset($formData['status']) && $formData['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo (isset($formData['status']) && $formData['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['status']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Thumbnail upload -->
                    <div class="mb-4">
                        <label for="courseThumbnail" class="form-label">Course Cover Thumbnail</label>
                        
                        <div class="row align-items-center gap-3">
                            <?php if (!empty($formData['thumbnail'])): ?>
                                <div class="col-auto">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($formData['thumbnail']); ?>" 
                                             alt="Thumbnail preview" 
                                             referrerpolicy="no-referrer"
                                             class="rounded border bg-light shadow-sm" 
                                             style="width: 100px; height: 100px; object-fit: cover;">
                                        <span class="badge bg-dark text-white position-absolute bottom-0 start-50 translate-middle-x mb-1 small" style="font-size: 0.65rem; opacity: 0.85;">Current</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col">
                                <input type="file" 
                                       name="thumbnail" 
                                       id="courseThumbnail" 
                                       accept="image/*"
                                       class="form-control <?php echo isset($errors['thumbnail']) ? 'is-invalid' : ''; ?>">
                                <?php if (isset($errors['thumbnail'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['thumbnail']); ?></div>
                                <?php endif; ?>
                                <small class="text-muted font-sans small mt-1.5 d-block">Only JPG, PNG, GIF, or WEBP images allowed. Max file size: 2MB.</small>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <!-- Actions -->
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <a href="index.php?route=admin/courses&action=list" class="btn btn-light border px-4 font-sans">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-1.5">
                            <i data-lucide="save" size="16"></i> Save Course
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
