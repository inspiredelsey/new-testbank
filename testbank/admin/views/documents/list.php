<?php
/**
 * List Documents View - Test Bank LMS
 */
$pageTitle = 'Documents - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<!-- Header with Navigation -->
<div class="row mb-4 align-items-center">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="index.php?route=admin/courses&action=list" 
                   class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center p-2 rounded-3 border-slate-200" 
                   title="Back to Courses">
                    <i data-lucide="arrow-left" size="18"></i>
                </a>
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small font-sans text-slate-500">
                            <li class="breadcrumb-item"><a href="index.php?route=admin/courses" class="text-decoration-none">Courses</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Documents</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold text-slate-950 mb-0 d-flex align-items-center gap-2">
                        <i data-lucide="folder-open" class="text-primary"></i>
                        <span>Course Documents: <?php echo htmlspecialchars($course['title']); ?></span>
                    </h4>
                </div>
            </div>
            
            <!-- Add Document Button -->
            <a href="index.php?route=admin/documents&action=create&course_id=<?php echo $course['id']; ?>" 
               class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 font-sans fw-medium shadow-sm">
                <i data-lucide="plus-circle" size="16"></i>
                <span>Add Document</span>
            </a>
        </div>
    </div>
</div>

<!-- Documents List Card -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="file-text" class="text-primary"></i>
            <h5 class="mb-0 fw-semibold text-slate-800">Uploaded Course Contents</h5>
        </div>
        <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5 font-mono"><?php echo count($documents); ?> Documents</span>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($documents)): ?>
            <div class="text-center py-5">
                <i data-lucide="file-minus" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                <h6 class="fw-semibold text-slate-700">No documents uploaded yet</h6>
                <p class="text-muted mb-0 small font-sans">Click on "Add Document" above to upload your first course attachment.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light font-sans">
                        <tr>
                            <th class="ps-4" style="width: 80px;">Order</th>
                            <th>Document Title</th>
                            <th>File Type</th>
                            <th>Description</th>
                            <th>Uploaded At</th>
                            <th class="text-end pe-4" style="width: 280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $index => $doc): ?>
                            <tr>
                                <!-- Order Indexes & Sorters -->
                                <td class="ps-4">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Move Up -->
                                        <?php if ($index > 0): ?>
                                            <a href="index.php?route=admin/documents&action=reorder&id=<?php echo $doc['id']; ?>&direction=up&course_id=<?php echo $course['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                               class="btn btn-xs btn-light border p-1 rounded d-inline-flex align-items-center justify-content-center" 
                                               title="Move Up">
                                                <i data-lucide="arrow-up" size="13"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-xs btn-light border p-1 rounded d-inline-flex align-items-center justify-content-center" disabled style="opacity: 0.4;">
                                                <i data-lucide="arrow-up" size="13"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Move Down -->
                                        <?php if ($index < count($documents) - 1): ?>
                                            <a href="index.php?route=admin/documents&action=reorder&id=<?php echo $doc['id']; ?>&direction=down&course_id=<?php echo $course['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                               class="btn btn-xs btn-light border p-1 rounded d-inline-flex align-items-center justify-content-center" 
                                               title="Move Down">
                                                <i data-lucide="arrow-down" size="13"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-xs btn-light border p-1 rounded d-inline-flex align-items-center justify-content-center" disabled style="opacity: 0.4;">
                                                <i data-lucide="arrow-down" size="13"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <!-- Document Title and Link to actual file -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i data-lucide="file-text" class="text-secondary" size="18"></i>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                               target="_blank" 
                                               class="fw-semibold text-primary text-decoration-none hover:underline" 
                                               title="Open file in new tab">
                                                <?php echo htmlspecialchars($doc['title']); ?>
                                            </a>
                                            <div class="text-muted small font-mono" style="font-size: 0.72rem;"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- File Type Badges -->
                                <td>
                                    <?php
                                    $type = strtolower($doc['file_type']);
                                    if ($type === 'pdf'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-100 font-sans fw-medium">PDF</span>
                                    <?php elseif ($type === 'doc'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-100 font-sans fw-medium">Word Doc</span>
                                    <?php elseif ($type === 'presentation'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-200 font-sans fw-medium">Presentation</span>
                                    <?php elseif ($type === 'video'): ?>
                                        <span class="badge bg-indigo-subtle text-indigo-700 border border-indigo-100 font-sans fw-medium">Video</span>
                                    <?php elseif ($type === 'image'): ?>
                                        <span class="badge bg-success-subtle text-success-800 border border-success-100 font-sans fw-medium">Image</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-slate-700 border border-slate-200 font-sans fw-medium">Attachment</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Description -->
                                <td>
                                    <span class="text-slate-600 small font-sans">
                                        <?php echo !empty($doc['description']) ? htmlspecialchars($doc['description']) : '<em class="text-slate-400">No description provided</em>'; ?>
                                    </span>
                                </td>
                                
                                <!-- Uploaded Time -->
                                <td>
                                    <span class="text-slate-600 small font-sans"><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></span>
                                </td>
                                
                                <!-- Action Buttons -->
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <!-- View Physical File -->
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 font-sans" 
                                           style="font-size: 0.8rem;">
                                            <i data-lucide="external-link" size="12"></i> View
                                        </a>

                                        <!-- Edit button -->
                                        <a href="index.php?route=admin/documents&action=edit&id=<?php echo $doc['id']; ?>" 
                                           class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 font-sans" 
                                           style="font-size: 0.8rem;">
                                            <i data-lucide="edit-3" size="12"></i> Edit
                                        </a>

                                        <!-- Delete button -->
                                        <a href="index.php?route=admin/documents&action=delete&id=<?php echo $doc['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                           class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 font-sans" 
                                           style="font-size: 0.8rem;"
                                           onclick="return confirmDelete(event, '<?php echo htmlspecialchars(addslashes($doc['title'])); ?>')">
                                            <i data-lucide="trash-2" size="12"></i> Delete
                                        </a>
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

<script>
function confirmDelete(event, title) {
    if (!confirm('Are you sure you want to delete "' + title + '"? This will permanently remove the document from the database and delete the physical file.')) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php
include __DIR__ . '/../layout_footer.php';
?>
