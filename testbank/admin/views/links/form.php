<?php
/**
 * Shared Add/Edit Link Form View - Test Bank LMS
 */
$pageTitle = $formTitle . ' - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<!-- Header with back navigation -->
<div class="row mb-4 align-items-center">
    <div class="col-12">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php?route=admin/links&action=list&course_id=<?php echo $courseId; ?>" 
               class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center p-2 rounded-3 border-slate-200" 
               title="Back to Links">
                <i data-lucide="arrow-left" size="18"></i>
            </a>
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small font-sans text-slate-500">
                        <li class="breadcrumb-item"><a href="index.php?route=admin/courses" class="text-decoration-none">Courses</a></li>
                        <li class="breadcrumb-item"><a href="index.php?route=admin/links&action=list&course_id=<?php echo $courseId; ?>" class="text-decoration-none">External Resources</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($formTitle); ?></li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-slate-950 mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="link" class="text-primary"></i>
                    <span><?php echo htmlspecialchars($formTitle); ?></span>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- Main Form Container -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="plus-circle" class="text-secondary"></i>
                <h5 class="mb-0 fw-semibold text-slate-800">Resource Specifications</h5>
            </div>
            
            <div class="card-body p-4">
                <!-- Global Form Errors -->
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 role="alert">
                        <i data-lucide="alert-circle" size="18"></i>
                        <div><?php echo htmlspecialchars($errors['form']); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo $actionUrl; ?>" novalidate>
                    <!-- CSRF Token and Course ID -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                    
                    <!-- Link Title -->
                    <div class="mb-4">
                        <label for="link_title" class="form-label fw-semibold text-slate-700 font-sans">Resource Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="title" 
                               id="link_title" 
                               class="form-control font-sans <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                               placeholder="e.g. Chapter 1 Slide Deck Reference" 
                               value="<?php echo htmlspecialchars($title); ?>" 
                               required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback font-sans"><?php echo htmlspecialchars($errors['title']); ?></div>
                        <?php else: ?>
                            <div class="form-text font-sans small text-slate-500">Provide a clear, brief title for students (maximum 200 characters).</div>
                        <?php endif; ?>
                    </div>

                    <!-- Link URL -->
                    <div class="mb-4">
                        <label for="link_url" class="form-label fw-semibold text-slate-700 font-sans">External URL <span class="text-danger">*</span></label>
                        <input type="url" 
                               name="url" 
                               id="link_url" 
                               class="form-control font-sans <?php echo isset($errors['url']) ? 'is-invalid' : ''; ?>" 
                               placeholder="e.g. https://example.com/slides" 
                               value="<?php echo htmlspecialchars($url); ?>" 
                               required>
                        <?php if (isset($errors['url'])): ?>
                            <div class="invalid-feedback font-sans"><?php echo htmlspecialchars($errors['url']); ?></div>
                        <?php else: ?>
                            <div class="form-text font-sans small text-slate-500">
                                The external URL must start with <strong>http://</strong> or <strong>https://</strong> (maximum 500 characters).
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Description Textarea -->
                    <div class="mb-4">
                        <label for="link_description" class="form-label fw-semibold text-slate-700 font-sans">Description / Notes <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea name="description" 
                                  id="link_description" 
                                  class="form-control font-sans <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                  rows="4" 
                                  placeholder="Provide optional instructions, context, or notes about this reference URL..."><?php echo htmlspecialchars($description); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback font-sans"><?php echo htmlspecialchars($errors['description']); ?></div>
                        <?php else: ?>
                            <div class="form-text font-sans small text-slate-500">Add any context, reading guides, or tasks associated with this resource.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center justify-content-end gap-2.5 border-top pt-4">
                        <a href="index.php?route=admin/links&action=list&course_id=<?php echo $courseId; ?>" 
                           class="btn btn-light border px-4 font-sans fw-medium">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 font-sans fw-semibold">
                            <i data-lucide="check-circle" size="16" class="me-1"></i>
                            <span>Save Link</span>
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
