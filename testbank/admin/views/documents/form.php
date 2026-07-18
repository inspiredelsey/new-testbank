<?php
/**
 * Shared Add/Edit Document Form View - Test Bank LMS
 */
$pageTitle = $formTitle . ' - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<!-- Header with back navigation -->
<div class="row mb-4 align-items-center">
    <div class="col-12">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php?route=admin/documents&action=list&course_id=<?php echo $courseId; ?>" 
               class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center p-2 rounded-3 border-slate-200" 
               title="Back to Documents">
                <i data-lucide="arrow-left" size="18"></i>
            </a>
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small font-sans text-slate-500">
                        <li class="breadcrumb-item"><a href="index.php?route=admin/courses" class="text-decoration-none">Courses</a></li>
                        <li class="breadcrumb-item"><a href="index.php?route=admin/documents&action=list&course_id=<?php echo $courseId; ?>" class="text-decoration-none">Documents</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($formTitle); ?></li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-slate-950 mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="file-text" class="text-primary"></i>
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
                <i data-lucide="upload-cloud" class="text-secondary"></i>
                <h5 class="mb-0 fw-semibold text-slate-800">Document Specifications</h5>
            </div>
            
            <div class="card-body p-4">
                <!-- Global Form Errors -->
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 font-sans mb-4" role="alert">
                        <i data-lucide="alert-circle" size="18"></i>
                        <div><?php echo htmlspecialchars($errors['form']); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo $actionUrl; ?>" enctype="multipart/form-data" novalidate>
                    <!-- CSRF Token and Course ID -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                    
                    <!-- Document Title -->
                    <div class="mb-4">
                        <label for="document_title" class="form-label fw-semibold text-slate-700 font-sans">Document Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="title" 
                               id="document_title" 
                               class="form-control font-sans <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                               placeholder="e.g. Chapter 1 Reading Material" 
                               value="<?php echo htmlspecialchars($title); ?>" 
                               required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback font-sans"><?php echo htmlspecialchars($errors['title']); ?></div>
                        <?php else: ?>
                            <div class="form-text font-sans small text-slate-500">Provide a clear, brief title for students (maximum 200 characters).</div>
                        <?php endif; ?>
                    </div>

                    <!-- File Upload Input -->
                    <div class="mb-4">
                        <label for="document_file" class="form-label fw-semibold text-slate-700 font-sans">
                            File Upload 
                            <?php if (empty($doc)): ?>
                                <span class="text-danger">*</span>
                            <?php else: ?>
                                <span class="text-muted fw-normal">(Optional)</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php if (!empty($doc)): ?>
                            <div class="p-3 bg-light rounded-3 border border-dashed mb-3 d-flex align-items-center justify-content-between font-sans">
                                <div class="d-flex align-items-center gap-2.5">
                                    <i data-lucide="file" class="text-primary"></i>
                                    <div>
                                        <div class="fw-medium text-slate-800" style="font-size: 0.9rem;">Current File:</div>
                                        <div class="text-muted font-mono" style="font-size: 0.75rem;"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-100 px-2 py-1.5 fw-medium small">Keep Current File</span>
                            </div>
                        <?php endif; ?>

                        <input type="file" 
                               name="document_file" 
                               id="document_file" 
                               class="form-control font-sans <?php echo isset($errors['document_file']) ? 'is-invalid' : ''; ?>" 
                               <?php echo empty($doc) ? 'required' : ''; ?>>
                        
                        <?php if (isset($errors['document_file'])): ?>
                            <div class="invalid-feedback font-sans"><?php echo htmlspecialchars($errors['document_file']); ?></div>
                        <?php else: ?>
                            <div class="form-text font-sans small text-slate-500">
                                Maximum size: <strong>20MB</strong>. Allowed types: PDF, Word (doc/docx), PowerPoint (ppt/pptx), MP4 videos, and common images.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Description Textarea -->
                    <div class="mb-4">
                        <label for="document_description" class="form-label fw-semibold text-slate-700 font-sans">Description / Notes <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea name="description" 
                                  id="document_description" 
                                  class="form-control font-sans <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                  rows="4" 
                                  placeholder="Provide optional instructions or notes about this document for students..."><?php echo htmlspecialchars($description); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback font-sans"><?php echo htmlspecialchars($errors['description']); ?></div>
                        <?php else: ?>
                            <div class="form-text font-sans small text-slate-500">Add any specific context, reading guidelines, or action items.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center justify-content-end gap-2.5 border-top pt-4">
                        <a href="index.php?route=admin/documents&action=list&course_id=<?php echo $courseId; ?>" 
                           class="btn btn-light border px-4 font-sans fw-medium">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 font-sans fw-semibold">
                            <i data-lucide="check-circle" size="16" class="me-1"></i>
                            <span>Save Document</span>
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
