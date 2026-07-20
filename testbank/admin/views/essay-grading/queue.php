<?php
$pageTitle = 'Manual Essay Grading Queue';
include __DIR__ . '/../layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-3 shadow-sm">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 font-sans">
                        <li class="breadcrumb-item"><a href="index.php?route=admin/courses">Admin</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Essay Grading</li>
                    </ol>
                </nav>
                <h4 class="mb-0 fw-bold text-dark">Manual Essay Grading Queue</h4>
                <p class="text-muted mb-0 font-sans">Review, critique, and grade essay questions submitted by students.</p>
            </div>
            <div class="bg-warning bg-opacity-10 text-warning px-4 py-3 rounded-3 d-flex align-items-center gap-3 border border-warning border-opacity-20">
                <i data-lucide="award" class="text-warning" style="width: 32px; height: 32px;"></i>
                <div>
                    <h5 class="mb-0 fw-bold"><?php echo count($queue); ?></h5>
                    <small class="text-muted d-block uppercase fw-bold font-sans" style="font-size: 0.7rem; letter-spacing: 0.5px;">Pending Evaluation</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
        <i data-lucide="check-circle" class="text-success"></i>
        <span><?php echo htmlspecialchars($_GET['success']); ?></span>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
        <i data-lucide="alert-circle" class="text-danger"></i>
        <span><?php echo htmlspecialchars($_GET['error']); ?></span>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="list" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold text-dark">Submitted Essay Answers</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($queue)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="sparkles" class="text-muted d-block mx-auto mb-3" style="width: 48px; height: 48px;"></i>
                        <h5 class="fw-bold text-dark mb-1">Inbox Zero!</h5>
                        <p class="text-muted mb-0 font-sans">No essay responses currently require manual grading. Excellent job!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 font-sans">
                            <thead class="table-light text-muted small uppercase">
                                <tr>
                                    <th style="width: 25%;">Student & Course</th>
                                    <th style="width: 25%;">Exam & Date</th>
                                    <th style="width: 35%;">Essay Question Prompt</th>
                                    <th class="text-center" style="width: 15%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($queue as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['student_name']); ?></div>
                                            <small class="text-muted d-flex align-items-center gap-1">
                                                <i data-lucide="book" style="width: 12px; height: 12px;"></i>
                                                <?php echo htmlspecialchars($item['course_title'] ?: 'General Course'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?php echo htmlspecialchars($item['exam_title']); ?></div>
                                            <small class="text-muted d-flex align-items-center gap-1">
                                                <i data-lucide="clock" style="width: 12px; height: 12px;"></i>
                                                <?php echo date('M d, Y H:i', strtotime($item['submitted_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-medium mb-1" style="max-height: 40px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars($item['question_text']); ?>
                                            </div>
                                            <span class="badge bg-light text-secondary border font-mono">Max Points: <?php echo floatval($item['max_points']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="index.php?route=admin/essay-grading&action=grade&id=<?php echo $item['attempt_answer_id']; ?>" class="btn btn-sm btn-primary rounded-3 d-inline-flex align-items-center gap-1.5 px-3 py-1.5">
                                                <i data-lucide="pencil" style="width: 14px; height: 14px;"></i>
                                                <span>Grade Response</span>
                                            </a>
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

<?php include __DIR__ . '/../layout_footer.php'; ?>
