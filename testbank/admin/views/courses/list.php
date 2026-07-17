<?php
/**
 * Course List View - Test Bank LMS
 */
$pageTitle = 'Course Management';
include __DIR__ . '/../layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <!-- Filters Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="index.php" class="row g-2 align-items-center">
                    <input type="hidden" name="route" value="admin/courses">
                    <input type="hidden" name="action" value="list">
                    
                    <div class="col-md-4">
                        <label class="form-label d-none" for="categoryFilter">Category</label>
                        <select name="category_id" id="categoryFilter" class="form-select font-sans">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($selectedCategoryId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['indented_name'] ?? $cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label d-none" for="statusFilter">Status</label>
                        <select name="status" id="statusFilter" class="form-select font-sans">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo ($selectedStatus === 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($selectedStatus === 'published') ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo ($selectedStatus === 'archived') ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    
                    <div class="col-md-5 d-flex gap-2">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1 px-4">
                            <i data-lucide="filter" size="16"></i> Filter
                        </button>
                        <?php if ($selectedCategoryId !== null || !empty($selectedStatus)): ?>
                            <a href="index.php?route=admin/courses&action=list" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                                <i data-lucide="rotate-ccw" size="16"></i> Reset
                            </a>
                        <?php endif; ?>
                        <div class="ms-auto">
                            <a href="index.php?route=admin/courses&action=create" class="btn btn-primary d-flex align-items-center gap-1.5">
                                <i data-lucide="plus-circle" size="18"></i> Create Course
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Course List Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="graduation-cap" class="text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Courses</h5>
                </div>
                <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5"><?php echo count($courses); ?> Found</span>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="book-open" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                        <h6 class="fw-semibold text-slate-700">No courses found</h6>
                        <p class="text-muted mb-4 small">Try adjusting your filters or create a new course to get started.</p>
                        <a href="index.php?route=admin/courses&action=create" class="btn btn-primary btn-sm">
                            <i data-lucide="plus" size="16"></i> Create First Course
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Course Info</th>
                                    <th>Category</th>
                                    <th>Instructor</th>
                                    <th>Passing Score</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-end pe-4" style="width: 250px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <!-- Thumbnail / Cover Image -->
                                                <?php if (!empty($c['thumbnail'])): ?>
                                                    <img src="<?php echo htmlspecialchars($c['thumbnail']); ?>" 
                                                         alt="<?php echo htmlspecialchars($c['title']); ?>" 
                                                         referrerpolicy="no-referrer"
                                                         class="rounded border bg-light" 
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-primary-subtle text-primary rounded border d-flex align-items-center justify-content-center" 
                                                         style="width: 50px; height: 50px;">
                                                        <i data-lucide="image" size="20" style="opacity: 0.85;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div>
                                                    <h6 class="fw-bold text-slate-800 mb-0">
                                                        <?php echo htmlspecialchars($c['title']); ?>
                                                    </h6>
                                                    <small class="text-muted text-truncate d-block" style="max-width: 300px;" title="<?php echo htmlspecialchars($c['description'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars($c['description'] ?: 'No description provided.'); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-slate-700 small font-sans fw-medium"><?php echo htmlspecialchars($c['category_name'] ?? 'Uncategorized'); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1 text-slate-700 small">
                                                <i data-lucide="user" size="12" class="text-slate-400"></i>
                                                <span><?php echo htmlspecialchars($c['instructor_name'] ?? 'Unassigned'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark font-mono">
                                                <i data-lucide="award" size="11" class="me-1 text-slate-400" style="vertical-align: middle;"></i><?php echo number_format($c['pass_percentage'], 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($c['status'] === 'published'): ?>
                                                <span class="badge bg-success"><i data-lucide="check-circle" size="11" class="me-1" style="vertical-align: middle;"></i>Published</span>
                                            <?php elseif ($c['status'] === 'archived'): ?>
                                                <span class="badge bg-secondary"><i data-lucide="archive" size="11" class="me-1" style="vertical-align: middle;"></i>Archived</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i data-lucide="edit" size="11" class="me-1" style="vertical-align: middle;"></i>Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-slate-500 small font-sans"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex gap-1.5 align-items-center">
                                                <!-- Quick Status Dropdown -->
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-1 font-sans" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown" 
                                                            aria-expanded="false">
                                                        <i data-lucide="settings-2" size="13"></i> Status
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2 <?php echo ($c['status'] === 'draft') ? 'active' : ''; ?>" 
                                                               href="index.php?route=admin/courses&action=status&id=<?php echo $c['id']; ?>&status=draft&csrf_token=<?php echo $csrfToken; ?>">
                                                                <span class="w-2.5 h-2.5 rounded-circle bg-warning d-inline-block"></span> Draft
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2 <?php echo ($c['status'] === 'published') ? 'active' : ''; ?>" 
                                                               href="index.php?route=admin/courses&action=status&id=<?php echo $c['id']; ?>&status=published&csrf_token=<?php echo $csrfToken; ?>">
                                                                <span class="w-2.5 h-2.5 rounded-circle bg-success d-inline-block"></span> Publish
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2 <?php echo ($c['status'] === 'archived') ? 'active' : ''; ?>" 
                                                               href="index.php?route=admin/courses&action=status&id=<?php echo $c['id']; ?>&status=archived&csrf_token=<?php echo $csrfToken; ?>">
                                                                <span class="w-2.5 h-2.5 rounded-circle bg-secondary d-inline-block"></span> Archive
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- Enrollments button -->
                                                <a href="index.php?route=admin/enrollments&action=manage&course_id=<?php echo $c['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 px-2" 
                                                   title="Manage Enrollments">
                                                    <i data-lucide="users" size="13"></i> Enrollments
                                                </a>

                                                <!-- Edit button -->
                                                <a href="index.php?route=admin/courses&action=edit&id=<?php echo $c['id']; ?>" 
                                                   class="btn btn-sm btn-light border d-flex align-items-center gap-1 px-2" 
                                                   title="Edit course">
                                                    <i data-lucide="edit-3" size="13"></i> Edit
                                                </a>

                                                <!-- Delete button -->
                                                <a href="index.php?route=admin/courses&action=delete&id=<?php echo $c['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                                   class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 px-2" 
                                                   title="Delete course"
                                                   onclick="return confirmDelete(event, '<?php echo htmlspecialchars(addslashes($c['title'])); ?>')">
                                                    <i data-lucide="trash-2" size="13"></i> Delete
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
    </div>
</div>

<script>
function confirmDelete(event, title) {
    if (!confirm('Are you sure you want to permanently delete the course "' + title + '"? This will remove all associated content.')) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php
include __DIR__ . '/../layout_footer.php';
?>
