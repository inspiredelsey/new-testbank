<?php
/**
 * Manage Course Enrollments View - Test Bank LMS
 */
$pageTitle = 'Manage Enrollments - ' . htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';
?>

<!-- Header Section with Back Navigation -->
<div class="row mb-4 align-items-center">
    <div class="col-12">
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
                        <li class="breadcrumb-item active" aria-current="page">Manage Enrollments</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-slate-950 mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="users-round" class="text-primary"></i>
                    <span>Enrollments: <?php echo htmlspecialchars($course['title']); ?></span>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- Group Enrollment Summary Callout -->
<?php if (!empty($groupResult)): ?>
    <div class="card border-0 shadow-sm mb-4 bg-primary-subtle text-primary-emphasis border-start border-primary border-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                    <i data-lucide="sparkles" size="20"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 font-sans">Group Enrollment Completed!</h6>
                    <p class="mb-0 small text-slate-700 font-sans">
                        We successfully processed members of the selected group: 
                        <strong class="text-dark bg-white px-1.5 py-0.5 rounded border small font-mono"><?php echo $groupResult['new']; ?></strong> users newly enrolled, and 
                        <strong class="text-dark bg-white px-1.5 py-0.5 rounded border small font-mono"><?php echo $groupResult['already']; ?></strong> were already enrolled.
                        <?php if ($groupResult['errors'] > 0): ?>
                            <span class="text-danger ms-1">(<?php echo $groupResult['errors']; ?> failures occurred)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Enrollment Tools (Side-by-Side Bento Grid) -->
<div class="row g-4 mb-4">
    <!-- Enroll Single User Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-3 d-inline-flex">
                            <i data-lucide="user-plus" size="18"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold text-slate-800">Enroll Individual Student</h6>
                    </div>
                    <p class="text-muted small mb-4 font-sans">Select any active student currently not registered in this course to enroll them immediately with "active" status.</p>
                </div>
                
                <form method="POST" action="index.php?route=admin/enrollments&action=enroll_single">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="enroll_user_select" class="form-label d-none">Select Student</label>
                        <?php if (empty($eligibleStudents)): ?>
                            <select id="enroll_user_select" class="form-select font-sans bg-light" disabled>
                                <option>-- All active students are already enrolled --</option>
                            </select>
                        <?php else: ?>
                            <select name="user_id" id="enroll_user_select" class="form-select font-sans" required>
                                <option value="">-- Choose Student --</option>
                                <?php foreach ($eligibleStudents as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full d-flex align-items-center justify-content-center gap-1.5 py-2 font-sans" <?php echo empty($eligibleStudents) ? 'disabled' : ''; ?>>
                        <i data-lucide="plus-circle" size="16"></i> Enroll Student
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Enroll Group Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="p-2 bg-emerald-50 text-emerald-600 rounded-3 d-inline-flex">
                            <i data-lucide="users" size="18"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold text-slate-800">Enroll Student Group</h6>
                    </div>
                    <p class="text-muted small mb-4 font-sans">Register all active students of a pre-configured group instantly. Duplicates are auto-detected and skipped safely.</p>
                </div>
                
                <form method="POST" action="index.php?route=admin/enrollments&action=enroll_group">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="enroll_group_select" class="form-label d-none">Select Group</label>
                        <?php if (empty($groups)): ?>
                            <select id="enroll_group_select" class="form-select font-sans bg-light" disabled>
                                <option>-- No groups available --</option>
                            </select>
                        <?php else: ?>
                            <select name="group_id" id="enroll_group_select" class="form-select font-sans" required>
                                <option value="">-- Choose Group --</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo $g['id']; ?>">
                                        <?php echo htmlspecialchars($g['name']); ?> (<?php echo $g['member_count']; ?> members)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-emerald w-full d-flex align-items-center justify-content-center gap-1.5 py-2 text-white font-sans" <?php echo empty($groups) ? 'disabled' : ''; ?> style="background-color: var(--bs-emerald, #198754);">
                        <i data-lucide="shield-alert" size="16"></i> Enroll Group Members
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enrolled Students List Card -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="contact-2" class="text-primary"></i>
            <h5 class="mb-0 fw-semibold text-slate-800">Enrolled Students</h5>
        </div>
        <span class="badge bg-light text-dark border fw-medium px-2.5 py-1.5 font-mono"><?php echo count($enrollments); ?> Enrolled</span>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($enrollments)): ?>
            <div class="text-center py-5">
                <i data-lucide="user-minus" class="text-slate-300 d-block mx-auto mb-3" size="48" style="width: 48px; height: 48px;"></i>
                <h6 class="fw-semibold text-slate-700">No student enrolled yet</h6>
                <p class="text-muted mb-0 small font-sans">Use the enrollment tools above to register students individually or as a group.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="font-sans">
                            <th class="ps-4" style="width: 300px;">Student Info</th>
                            <th>Enrolled At</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 250px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $e): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="bg-light text-slate-600 rounded-circle d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px; font-weight: 600;">
                                            <?php echo strtoupper(substr($e['user_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-slate-800 mb-0"><?php echo htmlspecialchars($e['user_name']); ?></div>
                                            <div class="text-muted small font-mono" style="font-size: 0.75rem;"><?php echo htmlspecialchars($e['user_email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-slate-600 small font-sans"><?php echo date('M d, Y H:i', strtotime($e['enrolled_at'])); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($e['group_name'])): ?>
                                        <span class="badge bg-emerald-subtle text-emerald-800 border border-emerald-100 font-sans fw-medium px-2 py-1">
                                            <i data-lucide="users" size="11" class="me-1" style="vertical-align: middle;"></i><?php echo htmlspecialchars($e['group_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border font-sans fw-medium px-2 py-1">
                                            <i data-lucide="user" size="11" class="me-1" style="vertical-align: middle;"></i>Individual
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($e['status'] === 'active'): ?>
                                        <span class="badge bg-success"><i data-lucide="play-circle" size="11" class="me-1" style="vertical-align: middle;"></i>Active</span>
                                    <?php elseif ($e['status'] === 'completed'): ?>
                                        <span class="badge bg-primary"><i data-lucide="award" size="11" class="me-1" style="vertical-align: middle;"></i>Completed</span>
                                    <?php elseif ($e['status'] === 'dropped'): ?>
                                        <span class="badge bg-secondary"><i data-lucide="x-circle" size="11" class="me-1" style="vertical-align: middle;"></i>Dropped</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst($e['status'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1.5 align-items-center">
                                        <!-- Quick Status Dropdown -->
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-1 font-sans" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false"
                                                    style="font-size: 0.8rem;">
                                                <i data-lucide="settings-2" size="12"></i> Status
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 font-sans" style="font-size: 0.85rem;">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 <?php echo ($e['status'] === 'active') ? 'active' : ''; ?>" 
                                                       href="index.php?route=admin/enrollments&action=status&id=<?php echo $e['id']; ?>&status=active&csrf_token=<?php echo $csrfToken; ?>">
                                                        <span class="w-2 h-2 rounded-circle bg-success d-inline-block"></span> Active
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 <?php echo ($e['status'] === 'completed') ? 'active' : ''; ?>" 
                                                       href="index.php?route=admin/enrollments&action=status&id=<?php echo $e['id']; ?>&status=completed&csrf_token=<?php echo $csrfToken; ?>">
                                                        <span class="w-2 h-2 rounded-circle bg-primary d-inline-block"></span> Completed
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 <?php echo ($e['status'] === 'dropped') ? 'active' : ''; ?>" 
                                                       href="index.php?route=admin/enrollments&action=status&id=<?php echo $e['id']; ?>&status=dropped&csrf_token=<?php echo $csrfToken; ?>">
                                                        <span class="w-2 h-2 rounded-circle bg-secondary d-inline-block"></span> Dropped
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Unenroll Button -->
                                        <?php if ($e['status'] !== 'dropped'): ?>
                                            <a href="index.php?route=admin/enrollments&action=unenroll&id=<?php echo $e['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                               class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 px-2.5 font-sans" 
                                               title="Unenroll student"
                                               style="font-size: 0.8rem;"
                                               onclick="return confirmUnenroll(event, '<?php echo htmlspecialchars(addslashes($e['user_name'])); ?>')">
                                                <i data-lucide="user-minus" size="12"></i> Unenroll
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light border d-flex align-items-center gap-1 px-2.5 font-sans" 
                                                    style="font-size: 0.8rem;" 
                                                    disabled>
                                                <i data-lucide="user-minus" size="12" class="text-slate-300"></i> Unenrolled
                                            </button>
                                        <?php endif; ?>
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
function confirmUnenroll(event, studentName) {
    if (!confirm('Are you sure you want to unenroll "' + studentName + '"? This will mark their enrollment status as "dropped" to preserve historical tracking/gradebook data.')) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php
include __DIR__ . '/../layout_footer.php';
?>
