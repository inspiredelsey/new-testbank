<?php
/**
 * List Links View - Test Bank LMS
 */
$pageTitle = 'External Resources - ' . htmlspecialchars($course['title']);
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
                            <li class="breadcrumb-item active" aria-current="page">External Resources</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold text-slate-950 mb-0 d-flex align-items-center gap-2">
                        <i data-lucide="link" class="text-primary"></i>
                        <span>Course Links: <?php echo htmlspecialchars($course['title']); ?></span>
                    </h4>
                </div>
            </div>
            
            <!-- Add Link Button -->
            <a href="index.php?route=admin/links&action=create&course_id=<?php echo $course['id']; ?>" 
               class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 font-sans fw-medium shadow-sm">
                <i data-lucide="plus-circle" size="16"></i>
                <span>Add Link</span>
            </a>
        </div>
    </div>
</div>

<!-- Links List Card -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="external-link" class="text-primary"></i>
            <h5 class="mb-0 fw-semibold text-slate-800">External Web Resources</h5>
        </div>
        <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5 font-mono"><?php echo count($links); ?> Links</span>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($links)): ?>
            <div class="text-center py-5">
                <i data-lucide="link-2-off" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                <h6 class="fw-semibold text-slate-700">No external links added yet</h6>
                <p class="text-muted mb-0 small font-sans">Click on "Add Link" above to add your first web resource or reference URL.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light font-sans">
                        <tr>
                            <th class="ps-4" style="width: 80px;">Order</th>
                            <th>Resource Title & URL</th>
                            <th>Description</th>
                            <th>Added At</th>
                            <th class="text-end pe-4" style="width: 280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $index => $linkItem): ?>
                            <tr>
                                <!-- Order Indexes & Sorters -->
                                <td class="ps-4">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Move Up -->
                                        <?php if ($index > 0): ?>
                                            <a href="index.php?route=admin/links&action=reorder&id=<?php echo $linkItem['id']; ?>&direction=up&course_id=<?php echo $course['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
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
                                        <?php if ($index < count($links) - 1): ?>
                                            <a href="index.php?route=admin/links&action=reorder&id=<?php echo $linkItem['id']; ?>&direction=down&course_id=<?php echo $course['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
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
                                
                                <!-- Link Title & URL -->
                                <td>
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="p-2 bg-primary-subtle text-primary rounded-3">
                                            <i data-lucide="link-2" size="16"></i>
                                        </div>
                                        <div>
                                            <?php 
                                            $urlLower = strtolower($linkItem['url']);
                                            $isValidScheme = (strpos($urlLower, 'http://') === 0 || strpos($urlLower, 'https://') === 0);
                                            $safeUrl = $isValidScheme ? htmlspecialchars($linkItem['url']) : '#';
                                            ?>
                                            <a href="<?php echo $safeUrl; ?>" 
                                               target="_blank" 
                                               rel="noopener noreferrer"
                                               class="fw-semibold text-primary text-decoration-none hover:underline d-inline-flex align-items-center gap-1" 
                                               title="Open link in new tab">
                                                <span><?php echo htmlspecialchars($linkItem['title']); ?></span>
                                                <i data-lucide="external-link" size="12" class="text-slate-400"></i>
                                            </a>
                                            <div class="text-muted small font-mono text-truncate" style="font-size: 0.72rem; max-width: 320px;" title="<?php echo htmlspecialchars($linkItem['url']); ?>">
                                                <?php echo htmlspecialchars($linkItem['url']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Description -->
                                <td>
                                    <span class="text-slate-600 small font-sans">
                                        <?php echo !empty($linkItem['description']) ? htmlspecialchars($linkItem['description']) : '<em class="text-slate-400">No description provided</em>'; ?>
                                    </span>
                                </td>
                                
                                <!-- Added Time -->
                                <td>
                                    <span class="text-slate-600 small font-sans"><?php echo date('M d, Y', strtotime($linkItem['created_at'])); ?></span>
                                </td>
                                
                                <!-- Action Buttons -->
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <!-- View External URL -->
                                        <a href="<?php echo $safeUrl; ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 font-sans" 
                                           style="font-size: 0.8rem;">
                                            <i data-lucide="eye" size="12"></i> Visit
                                        </a>

                                        <!-- Edit button -->
                                        <a href="index.php?route=admin/links&action=edit&id=<?php echo $linkItem['id']; ?>" 
                                           class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 font-sans" 
                                           style="font-size: 0.8rem;">
                                            <i data-lucide="edit-3" size="12"></i> Edit
                                        </a>

                                        <!-- Delete button -->
                                        <a href="index.php?route=admin/links&action=delete&id=<?php echo $linkItem['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                           class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 font-sans" 
                                           style="font-size: 0.8rem;"
                                           onclick="return confirmDelete(event, '<?php echo htmlspecialchars(addslashes($linkItem['title'])); ?>')">
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
    if (!confirm('Are you sure you want to delete "' + title + '"? This will permanently remove the link from the course content.')) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php
include __DIR__ . '/../layout_footer.php';
?>
