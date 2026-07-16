<?php
$pageTitle = htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';

$activeTab = $_GET['tab'] ?? 'documents';
?>

<!-- Header -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <a href="index.php?route=admin/courses" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-2">
                <i data-lucide="arrow-left" size="14"></i> Back to Courses
            </a>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h3 class="display-font fw-bold text-dark mb-0"><?php echo htmlspecialchars($course['title']); ?></h3>
                <?php if ($course['status'] === 'published'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-sans small px-2 py-1 rounded-pill"><i data-lucide="check-circle" size="12" class="me-1"></i>Published</span>
                <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-sans small px-2 py-1 rounded-pill"><i data-lucide="edit" size="12" class="me-1"></i>Draft</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0"><i data-lucide="user-check" size="14" class="me-1"></i>Instructor: <strong><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unassigned'); ?></strong></p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="index.php?route=admin/courses&action=edit&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-light border d-flex align-items-center gap-1.5 py-2 px-3 fw-medium">
                <i data-lucide="edit-3" size="16"></i> Edit Details
            </a>
        </div>
    </div>
    
    <?php if (!empty($course['description'])): ?>
        <div class="mt-3 p-3 bg-white border rounded-3 small text-muted">
            <h6 class="text-dark mb-1 fw-bold"><i data-lucide="info" size="14" class="me-1 text-primary"></i>About this Course</h6>
            <?php echo nl2br(htmlspecialchars($course['description'])); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Tabs Navigation -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white p-0 border-0">
        <ul class="nav nav-tabs border-bottom ps-3" id="courseManagementTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2 <?php echo ($activeTab === 'documents') ? 'active' : ''; ?>" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=documents">
                    <i data-lucide="file-text" size="16"></i> Documents
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2 <?php echo ($activeTab === 'links') ? 'active' : ''; ?>" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=links">
                    <i data-lucide="link" size="16"></i> Links
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2 <?php echo ($activeTab === 'quizzes') ? 'active' : ''; ?>" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=quizzes">
                    <i data-lucide="help-circle" size="16"></i> Quizzes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2 <?php echo ($activeTab === 'learning-path') ? 'active' : ''; ?>" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=learning-path">
                    <i data-lucide="map" size="16"></i> Learning Path
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 px-4 fw-medium d-flex align-items-center gap-2 <?php echo ($activeTab === 'enrollments') ? 'active' : ''; ?>" href="index.php?route=admin/courses&action=view&id=<?php echo $course['id']; ?>&tab=enrollments">
                    <i data-lucide="users" size="16"></i> Enrollments
                </a>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-4 bg-white">
        <!-- 1. Documents Tab -->
        <?php if ($activeTab === 'documents'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Documents Repository</h5>
                <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                    <i data-lucide="upload" size="14"></i> Upload Document
                </button>
            </div>
            
            <?php if (empty($documents)): ?>
                <div class="text-center py-5 border rounded-3 border-dashed bg-light-subtle">
                    <i data-lucide="file" class="text-muted mb-3" size="36"></i>
                    <p class="text-muted mb-0">No documents have been uploaded to this course yet.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <?php foreach ($documents as $doc): ?>
                        <div class="col">
                            <div class="p-3 border rounded-3 d-flex justify-content-between align-items-center bg-light-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="p-2 bg-white border rounded text-danger">
                                        <i data-lucide="file-text" size="24"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($doc['title']); ?></h6>
                                        <small class="text-muted font-sans" style="font-size: 0.75rem;"><?php echo htmlspecialchars($doc['file_name']); ?> | uploaded <?php echo date('M d, Y', strtotime($doc['created_at'])); ?></small>
                                    </div>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-light border" title="Download">
                                        <i data-lucide="download" size="14"></i>
                                    </a>
                                    <a href="index.php?route=admin/courses&action=delete_document&id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Delete this document?');" title="Delete">
                                        <i data-lucide="trash-2" size="14"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- 2. Links Tab -->
        <?php elseif ($activeTab === 'links'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">External Resource Links</h5>
                <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addLinkModal">
                    <i data-lucide="plus" size="14"></i> Add Link
                </button>
            </div>
            
            <?php if (empty($links)): ?>
                <div class="text-center py-5 border rounded-3 border-dashed bg-light-subtle">
                    <i data-lucide="link-2" class="text-muted mb-3" size="36"></i>
                    <p class="text-muted mb-0">No external resources have been linked yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($links as $lnk): ?>
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 mb-2 rounded border border-light-subtle">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 bg-light-subtle border rounded text-info">
                                    <i data-lucide="external-link" size="20"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($lnk['title']); ?></h6>
                                    <a href="<?php echo htmlspecialchars($lnk['url']); ?>" target="_blank" class="text-muted font-sans small text-decoration-none hover-text-primary text-truncate d-block" style="max-width: 450px;">
                                        <?php echo htmlspecialchars($lnk['url']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="<?php echo htmlspecialchars($lnk['url']); ?>" target="_blank" class="btn btn-sm btn-light border" title="Go to Link">
                                    <i data-lucide="external-link" size="14"></i>
                                </a>
                                <a href="index.php?route=admin/courses&action=delete_link&id=<?php echo $lnk['id']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Delete this link?');" title="Delete">
                                    <i data-lucide="trash-2" size="14"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- 3. Quizzes Tab -->
        <?php elseif ($activeTab === 'quizzes'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Linked Exams & Quizzes</h5>
                <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#linkQuizModal">
                    <i data-lucide="plus" size="14"></i> Link Existing Quiz
                </button>
            </div>
            
            <?php if (empty($courseExams)): ?>
                <div class="text-center py-5 border rounded-3 border-dashed bg-light-subtle">
                    <i data-lucide="help-circle" class="text-muted mb-3" size="36"></i>
                    <p class="text-muted mb-0">No quizzes are linked to this course yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Pass Target</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseExams as $exm): ?>
                                <tr>
                                    <td>
                                        <span class="text-dark fw-bold"><?php echo htmlspecialchars($exm['title']); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?php echo htmlspecialchars($exm['category_name'] ?? 'General'); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-dark small"><?php echo $exm['duration_minutes']; ?> mins</span>
                                    </td>
                                    <td>
                                        <span class="text-dark small"><?php echo floatval($exm['pass_percentage']); ?>%</span>
                                    </td>
                                    <td>
                                        <?php if ($exm['status'] === 'published'): ?>
                                            <span class="badge bg-success-subtle text-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="index.php?route=admin/courses&action=unlink_exam&course_id=<?php echo $course['id']; ?>&exam_id=<?php echo $exm['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Unlink this exam from the course?');">
                                            Unlink
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <!-- 4. Learning Path Tab -->
        <?php elseif ($activeTab === 'learning-path'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Learning Path Sequence</h5>
                    <p class="text-muted small mb-0"><i data-lucide="info" size="14" class="me-1"></i> Drag and drop cards to set sequence order. Complete each item to unlock the next.</p>
                </div>
                <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addToPathModal">
                    <i data-lucide="plus-square" size="14"></i> Add Content to Path
                </button>
            </div>
            
            <?php if (empty($lpItems)): ?>
                <div class="text-center py-5 border rounded-3 border-dashed bg-light-subtle">
                    <i data-lucide="map" class="text-muted mb-3" size="36"></i>
                    <p class="text-muted mb-0">Learning Path is empty. Add documents, links, or quizzes to structure your path.</p>
                </div>
            <?php else: ?>
                <div id="learningPathList" class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ($lpItems as $index => $item): ?>
                        <div class="card shadow-sm border border-light-subtle lp-item-card" data-id="<?php echo $item['id']; ?>">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="drag-handle text-muted cursor-move p-1 rounded hover-bg-light" style="cursor: grab;">
                                        <i data-lucide="grip-vertical"></i>
                                    </div>
                                    <div class="p-2 bg-light border rounded">
                                        <?php if ($item['type'] === 'document'): ?>
                                            <i data-lucide="file-text" class="text-danger" size="20"></i>
                                        <?php elseif ($item['type'] === 'link'): ?>
                                            <i data-lucide="link" class="text-info" size="20"></i>
                                        <?php else: ?>
                                            <i data-lucide="help-circle" class="text-primary" size="20"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-dark fw-bold"><?php echo htmlspecialchars($item['item_title']); ?></span>
                                            <span class="badge bg-light text-secondary border font-sans small"><?php echo ucfirst($item['type']); ?></span>
                                        </div>
                                        <?php if (!empty($item['prerequisite_id'])): ?>
                                            <small class="text-warning-emphasis d-flex align-items-center gap-1 mt-1 font-sans" style="font-size: 0.75rem;">
                                                <i data-lucide="lock" size="12"></i> Prerequisite: <?php echo htmlspecialchars($item['prereq_title'] ?? 'Previous Item'); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted font-sans" style="font-size: 0.75rem;">No prerequisite (Unlocked initially)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-light border px-2 py-1" onclick="openPrereqModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_title']); ?>', <?php echo $item['prerequisite_id'] ?: 'null'; ?>)">
                                        Set Prerequisite
                                    </button>
                                    <a href="index.php?route=admin/courses&action=delete_lp_item&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Remove from path?');" title="Remove">
                                        <i data-lucide="trash-2" size="14"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- 5. Enrollments Tab -->
        <?php elseif ($activeTab === 'enrollments'): ?>
            <div class="row">
                <div class="col-lg-8">
                    <h5 class="fw-bold mb-3">Enrolled Students</h5>
                    <?php if (empty($enrolledStudents)): ?>
                        <div class="text-center py-5 border rounded-3 border-dashed bg-light-subtle">
                            <i data-lucide="users" class="text-muted mb-3" size="36"></i>
                            <p class="text-muted mb-0">No students enrolled in this course yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Email Address</th>
                                        <th>Enrolled At</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrolledStudents as $std): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($std['name']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($std['email']); ?></td>
                                            <td class="small"><?php echo date('M d, Y H:i', strtotime($std['enrolled_at'])); ?></td>
                                            <td class="text-end">
                                                <a href="index.php?route=admin/courses&action=unenroll_student&course_id=<?php echo $course['id']; ?>&student_id=<?php echo $std['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove student from course?');">
                                                    Unenroll
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card p-3 border rounded-3 bg-light-subtle">
                        <h6 class="fw-bold mb-3 text-dark"><i data-lucide="plus-circle" class="me-1 text-primary"></i>Enroll New Student</h6>
                        <form action="index.php?route=admin/courses&action=enroll_student" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="student_id" class="form-label text-muted small fw-semibold">Select Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">-- Choose Student --</option>
                                    <?php foreach ($nonEnrolledStudents as $std): ?>
                                        <option value="<?php echo $std['id']; ?>"><?php echo htmlspecialchars($std['name']); ?> (<?php echo htmlspecialchars($std['email']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <i data-lucide="user-plus" size="16"></i> Enroll Student
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?route=admin/courses&action=add_document" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">Upload Course Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="doc_title" class="form-label fw-semibold text-muted small">Display Title</label>
                    <input type="text" class="form-control" id="doc_title" name="title" required placeholder="e.g. Acid-Base Imbalances Lecture Slide">
                </div>
                <div class="mb-3">
                    <label for="doc_file" class="form-label fw-semibold text-muted small">Choose PDF/Document File</label>
                    <input type="file" class="form-control" id="doc_file" name="doc_file" accept=".pdf,.doc,.docx,.txt">
                    <div class="form-text small">Upload actual lecture notes, guide outlines, or slides.</div>
                </div>
                <div class="p-3 bg-light rounded-3 mt-3">
                    <label for="file_name_text" class="form-label fw-semibold text-muted small mb-1">Or Generate Sandbox File Mock</label>
                    <input type="text" class="form-control form-control-sm" id="file_name_text" name="file_name_text" placeholder="e.g. AcidBaseCheatSheet.pdf">
                    <div class="form-text small">If real uploading is bypassed in sandbox, we'll write a mock template file under this name.</div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Document</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Link Modal -->
<div class="modal fade" id="addLinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?route=admin/courses&action=add_link" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">Add External Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="link_title" class="form-label fw-semibold text-muted small">Link Title</label>
                    <input type="text" class="form-control" id="link_title" name="title" required placeholder="e.g. NCLEX Drug Calculation Practice Video">
                </div>
                <div class="mb-3">
                    <label for="link_url" class="form-label fw-semibold text-muted small">URL Address</label>
                    <input type="url" class="form-control" id="link_url" name="url" required placeholder="https://example.com/lecture-video">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Resource</button>
            </div>
        </form>
    </div>
</div>

<!-- Link Quiz Modal -->
<div class="modal fade" id="linkQuizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?route=admin/courses&action=add_exam_to_course" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">Link Quiz/Exam to Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="exam_id" class="form-label fw-semibold text-muted small">Select Quiz/Exam</label>
                    <select class="form-select" id="exam_id" name="exam_id" required>
                        <option value="">-- Choose Quiz --</option>
                        <?php foreach ($allExams as $exm): ?>
                            <option value="<?php echo $exm['id']; ?>"><?php echo htmlspecialchars($exm['title']); ?> (<?php echo htmlspecialchars($exm['category_name'] ?? 'General'); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Link to Course</button>
            </div>
        </form>
    </div>
</div>

<!-- Add LP Item Modal -->
<div class="modal fade" id="addToPathModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?route=admin/courses&action=add_lp_item" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">Add Course Content to Path</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="lp_type" class="form-label fw-semibold text-muted small">Content Type</label>
                    <select class="form-select" id="lp_type" name="type" required onchange="loadAvailableLPContents()">
                        <option value="">-- Select Content Type --</option>
                        <option value="document">Course Document</option>
                        <option value="link">Resource Link</option>
                        <option value="quiz">Quiz/Exam</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="lp_item_id" class="form-label fw-semibold text-muted small">Select Item</label>
                    <select class="form-select" id="lp_item_id" name="item_id" required disabled>
                        <option value="">-- Choose Type First --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="lp_prereq" class="form-label fw-semibold text-muted small">Unlock Prerequisite (Optional)</label>
                    <select class="form-select" id="lp_prereq" name="prerequisite_id">
                        <option value="">-- Unlock Initially (No prerequisite) --</option>
                        <?php foreach ($lpItems as $lpItem): ?>
                            <option value="<?php echo $lpItem['id']; ?>">
                                <?php echo htmlspecialchars($lpItem['item_title']); ?> (<?php echo ucfirst($lpItem['type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add to Learning Path</button>
            </div>
        </form>
    </div>
</div>

<!-- Prerequisite Settings Modal -->
<div class="modal fade" id="setPrereqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?route=admin/courses&action=update_lp_prereq" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            <input type="hidden" id="prereq_lp_item_id" name="lp_item_id" value="">
            
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="prereqTitleLabel">Set Prerequisite</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small">Specify what item the student must complete before this content becomes accessible.</p>
                
                <div class="mb-3">
                    <label for="prereq_select" class="form-label fw-semibold text-muted small">Choose Prerequisite Item</label>
                    <select class="form-select" id="prereq_select" name="prerequisite_id">
                        <option value="">-- No Prerequisite (Unlock initially) --</option>
                        <?php foreach ($lpItems as $lpItem): ?>
                            <option value="<?php echo $lpItem['id']; ?>" class="prereq-option" data-id="<?php echo $lpItem['id']; ?>">
                                <?php echo htmlspecialchars($lpItem['item_title']); ?> (<?php echo ucfirst($lpItem['type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Prerequisite</button>
            </div>
        </form>
    </div>
</div>

<!-- Dynamic data lists for LP dropdown -->
<script>
const docsList = <?php echo json_encode($this->lpModel->getAvailableContentsForLP($course['id'], 'document')); ?>;
const linksList = <?php echo json_encode($this->lpModel->getAvailableContentsForLP($course['id'], 'link')); ?>;
const quizzesList = <?php echo json_encode($this->lpModel->getAvailableContentsForLP($course['id'], 'quiz')); ?>;

function loadAvailableLPContents() {
    const type = document.getElementById('lp_type').value;
    const itemSelect = document.getElementById('lp_item_id');
    itemSelect.innerHTML = '<option value="">-- Select Item --</option>';
    
    if (!type) {
        itemSelect.disabled = true;
        return;
    }
    
    let list = [];
    if (type === 'document') list = docsList;
    else if (type === 'link') list = linksList;
    else if (type === 'quiz') list = quizzesList;
    
    if (list.length === 0) {
        itemSelect.innerHTML = '<option value="">No available unpublished resources of this type</option>';
        itemSelect.disabled = true;
    } else {
        list.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.title;
            itemSelect.appendChild(opt);
        });
        itemSelect.disabled = false;
    }
}

function openPrereqModal(lpItemId, itemTitle, currentPrereqId) {
    document.getElementById('prereq_lp_item_id').value = lpItemId;
    document.getElementById('prereqTitleLabel').textContent = "Set Prerequisite: " + itemTitle;
    
    // Hide own item from the choices to prevent self-prerequisite loop
    const options = document.querySelectorAll('.prereq-option');
    options.forEach(opt => {
        if (opt.getAttribute('data-id') == lpItemId) {
            opt.style.display = 'none';
        } else {
            opt.style.display = 'block';
        }
    });
    
    document.getElementById('prereq_select').value = currentPrereqId || '';
    
    const modal = new bootstrap.Modal(document.getElementById('setPrereqModal'));
    modal.show();
}
</script>

<!-- SortableJS integration for interactive drag and drop Learning Path ordering -->
<?php if ($activeTab === 'learning-path' && !empty($lpItems)): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('learningPathList');
    if (el) {
        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                // Re-calculate order and submit order list via Ajax POST
                const cards = document.querySelectorAll('.lp-item-card');
                const orders = {};
                cards.forEach((card, idx) => {
                    orders[card.getAttribute('data-id')] = idx + 1;
                });
                
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo Session::getCSRFToken(); ?>');
                for (let key in orders) {
                    formData.append('orders[' + key + ']', orders[key]);
                }
                
                fetch('index.php?route=admin/courses&action=update_lp_orders', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Successfully ordered!
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '5000';
                        toast.innerHTML = `
                            <div class="toast show align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body small fw-semibold">
                                        Learning path sequence updated successfully!
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                    } else {
                        console.error('Failed to save LP orders:', data.message);
                    }
                })
                .catch(err => {
                    console.error('Ajax error:', err);
                });
            }
        });
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../layout_footer.php'; ?>
