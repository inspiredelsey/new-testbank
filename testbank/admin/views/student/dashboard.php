<?php
$pageTitle = 'Student Portal';
include __DIR__ . '/../layout_header.php';
?>

<div class="row">
    <!-- Main Available Exams list -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <!-- My Enrolled Courses -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="graduation-cap" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">My Courses</h5>
            </div>
            <div class="card-body">
                <?php if (empty($enrolledCourses)): ?>
                    <div class="text-center py-4">
                        <i data-lucide="book" class="text-muted d-block mx-auto mb-2" size="32"></i>
                        <p class="text-muted small mb-0">You are not enrolled in any courses yet.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($enrolledCourses as $course): ?>
                            <div class="col-12">
                                <div class="p-3 border rounded-3 bg-light-subtle d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                        <p class="text-muted small mb-0 text-truncate" style="max-width: 400px;"><?php echo htmlspecialchars($course['description'] ?: 'No description provided.'); ?></p>
                                        <small class="text-muted font-sans" style="font-size: 0.75rem;"><i data-lucide="user" size="12" class="me-1"></i>Instructor: <?php echo htmlspecialchars($course['instructor_name'] ?? 'Unassigned'); ?></small>
                                    </div>
                                    <a href="index.php?route=student/course/view&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-1.5 px-3 py-2">
                                        Enter Course <i data-lucide="arrow-right" size="14"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="book-open" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Available Assessments</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($availableExams)): ?>
                    <div class="text-center py-5">
                        <i data-lucide="inbox" class="text-muted d-block mx-auto mb-3" size="48"></i>
                        <p class="text-muted">No exams are currently published and available for you to take.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($availableExams as $exam): ?>
                            <?php
                            // Check date bounds
                            $now = time();
                            $isStarted = empty($exam['start_date']) || strtotime($exam['start_date']) <= $now;
                            $isEnded = !empty($exam['end_date']) && strtotime($exam['end_date']) < $now;
                            $isOpen = $isStarted && !$isEnded;
                            ?>
                            <div class="list-group-item p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                        <p class="text-muted small mb-2 text-truncate" style="max-width: 450px;"><?php echo htmlspecialchars($exam['description'] ?: 'Read instructions before beginning.'); ?></p>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="badge bg-light text-secondary border font-sans small"><i data-lucide="clock" size="12" class="me-1"></i><?php echo $exam['duration_minutes']; ?> minutes</span>
                                            <span class="badge bg-light text-secondary border font-sans small"><i data-lucide="award" size="12" class="me-1"></i>Passing: <?php echo floatval($exam['pass_percentage']); ?>%</span>
                                            <?php if ($exam['max_attempts'] > 0): ?>
                                                <span class="badge bg-light text-secondary border font-sans small"><i data-lucide="refresh-cw" size="12" class="me-1"></i>Limit: <?php echo $exam['max_attempts']; ?> attempts</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border font-sans small"><i data-lucide="refresh-cw" size="12" class="me-1"></i>Unlimited Attempts</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <?php if ($isEnded): ?>
                                            <button class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" disabled>
                                                <i data-lucide="calendar-off" size="16"></i> Past Due
                                            </button>
                                        <?php elseif (!$isStarted): ?>
                                            <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" disabled>
                                                <i data-lucide="calendar" size="16"></i> Opening Soon
                                            </button>
                                        <?php else: ?>
                                            <a href="index.php?route=student/exam/instructions&exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                                                Take Exam <i data-lucide="chevron-right" size="16"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Past history logs -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="history" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">My Exam History</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($history)): ?>
                    <p class="text-muted text-center py-5 mb-0">You haven't completed any exam attempts yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($history as $att): ?>
                            <div class="list-group-item p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars($att['exam_title']); ?></h6>
                                        <small class="text-muted font-sans" style="font-size: 0.75rem;"><?php echo date('M d, Y H:i', strtotime($att['started_at'])); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($att['status'] === 'in_progress'): ?>
                                            <a href="index.php?route=student/exam/take&attempt_id=<?php echo $att['id']; ?>" class="badge bg-warning text-dark text-decoration-none border border-warning d-block mb-1 p-1.5 font-sans">
                                                Resume
                                            </a>
                                        <?php elseif ($att['status'] === 'submitted'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border d-block mb-1 p-1.5 font-sans">
                                                Grading
                                            </span>
                                            <small class="text-muted small font-sans" style="font-size: 0.7rem;">Essay pending</small>
                                        <?php else: ?>
                                            <span class="badge <?php echo $att['passed'] ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'; ?> border d-block mb-1 p-1.5 font-sans">
                                                <?php echo round($att['percentage'], 1); ?>%
                                            </span>
                                            <a href="index.php?route=student/exam/review&attempt_id=<?php echo $att['id']; ?>" class="small text-primary text-decoration-none font-sans" style="font-size: 0.75rem; font-weight: 500;">
                                                Review Answers
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
