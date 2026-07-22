<?php
/**
 * Course Analytics Dashboard View
 */
$pageTitle = 'Course Tracking & Analytics';
require_once __DIR__ . '/../layout_header.php';

function formatDuration($secs) {
    if ($secs <= 0) return '0s';
    $h = floor($secs / 3600);
    $m = floor(($secs % 3600) / 60);
    $s = $secs % 60;
    $parts = [];
    if ($h > 0) $parts[] = "{$h}h";
    if ($m > 0 || $h > 0) $parts[] = "{$m}m";
    $parts[] = "{$s}s";
    return implode(' ', $parts);
}
?>

<div class="container-fluid px-0">
    <!-- Course Selector Header -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h3 class="fw-bold m-0 text-slate-900 display-font">Course Dashboard Analytics</h3>
                <p class="text-muted m-0 mt-1">Select a course to analyze user progress, exam metrics, and live interactions.</p>
            </div>
            <div>
                <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="route" value="admin/analytics">
                    <input type="hidden" name="action" value="dashboard">
                    <label for="course_select" class="form-label text-nowrap fw-semibold mb-0" style="font-size: 0.85rem;">Active Course:</label>
                    <select id="course_select" name="course_id" class="form-select shadow-sm" style="min-width: 250px; border-color: #cbd5e1;" onchange="this.form.submit()">
                        <?php if (empty($courses)): ?>
                            <option value="">No courses available</option>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" <?php echo $courseId === (int)$c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <?php if (!$activeCourse): ?>
        <!-- Empty State -->
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body p-5">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                    <i data-lucide="line-chart" class="text-muted" style="width: 40px; height: 40px;"></i>
                </div>
                <h4 class="fw-bold text-dark">No Course Selected</h4>
                <p class="text-muted mx-auto" style="max-width: 450px;">Please create a course or make sure you are enrolled as an instructor to start viewing real-time analytics dashboards.</p>
                <a href="index.php?route=admin/courses&action=create" class="btn btn-primary mt-2">
                    <i data-lucide="plus" class="me-1" style="width: 18px; height: 18px;"></i> Create First Course
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- KPI Cards Grid -->
        <div class="row g-4 mb-4">
            <!-- Card 1: Total Enrolled -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100 p-3" id="kpi_enrolled">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background-color: #eef2ff; color: #4f46e5;">
                            <i data-lucide="users" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Total Enrolled</span>
                            <h2 class="fw-extrabold m-0 text-slate-950 display-font">
                                <?php echo (int)$completionRate['enrollment_summary']['total_enrolled']; ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Completed Course Count -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100 p-3" id="kpi_completions">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background-color: #ecfdf5; color: #10b981;">
                            <i data-lucide="check-circle" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Course Completions</span>
                            <h2 class="fw-extrabold m-0 text-slate-950 display-font">
                                <?php echo (int)$completionRate['enrollment_summary']['completed_count']; ?>
                                <span class="text-muted fw-normal" style="font-size: 0.9rem;">
                                    (<?php 
                                        $total = (int)$completionRate['enrollment_summary']['total_enrolled'];
                                        $comp = (int)$completionRate['enrollment_summary']['completed_count'];
                                        echo $total > 0 ? round(($comp / $total) * 100) : 0;
                                    ?>%)
                                </span>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Avg LP Progress -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100 p-3" id="kpi_progress">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background-color: #fffbeb; color: #f59e0b;">
                            <i data-lucide="milestone" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Avg. Learning Path Progress</span>
                            <h2 class="fw-extrabold m-0 text-slate-950 display-font">
                                <?php echo (int)$completionRate['average_progress_percent']; ?>%
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Side: Interactive Metrics & Progress -->
            <div class="col-12 col-lg-8">
                <!-- Section 1: Learning Path Progress -->
                <div class="card border-0 shadow-sm mb-4" id="section_progress">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold m-0 text-dark d-flex align-items-center gap-2">
                            <i data-lucide="milestone" class="text-primary"></i> Learning Path Completion Stats
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($completionRate['students_progress'])): ?>
                            <div class="p-4 text-center text-muted">No students enrolled or no learning path items exist for this course yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Completed Items</th>
                                            <th style="width: 40%;">Progress %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($completionRate['students_progress'] as $sp): ?>
                                            <?php 
                                                $total = intval($sp['total_items']);
                                                $completed = intval($sp['completed_items']);
                                                $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($sp['student_name']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo "{$completed} / {$total}"; ?> items</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 8px; border-radius: 4px; background-color: #f1f5f9;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $pct; ?>%; border-radius: 4px;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <span class="small fw-bold text-dark" style="min-width: 35px; text-align: right;"><?php echo $pct; ?>%</span>
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

                <!-- Section 2: Quizzes Performance & Ranges -->
                <div class="card border-0 shadow-sm mb-4" id="section_quizzes">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold m-0 text-dark d-flex align-items-center gap-2">
                            <i data-lucide="file-spreadsheet" class="text-primary"></i> Quiz Results & Distribution Analysis
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($quizScores)): ?>
                            <div class="p-4 text-center text-muted">No quizzes exist or no attempts have been submitted for this course yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Quiz Title</th>
                                            <th>Total Attempts</th>
                                            <th>Avg. Score</th>
                                            <th>Score Ranges (F / D / C / B / A)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($quizScores as $qs): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($qs['exam_title']); ?></div>
                                                    <div class="text-muted small">Min: <?php echo $qs['min_percentage'] !== null ? round($qs['min_percentage'], 1) . '%' : 'N/A'; ?> | Max: <?php echo $qs['max_percentage'] !== null ? round($qs['max_percentage'], 1) . '%' : 'N/A'; ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo (int)$qs['total_attempts']; ?> attempts</span>
                                                </td>
                                                <td>
                                                    <strong class="text-indigo-600"><?php echo $qs['average_percentage'] !== null ? round($qs['average_percentage'], 1) . '%' : 'N/A'; ?></strong>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <span class="badge bg-danger" title="Failing (<60%)"><?php echo (int)$qs['range_f']; ?> F</span>
                                                        <span class="badge bg-warning" title="D (60-69%)"><?php echo (int)$qs['range_d']; ?> D</span>
                                                        <span class="badge bg-warning" style="background-color: #fef3c7 !important; color: #d97706 !important; border-color: #fde68a !important;" title="C (70-79%)"><?php echo (int)$qs['range_c']; ?> C</span>
                                                        <span class="badge bg-success" style="background-color: #d1fae5 !important; color: #065f46 !important; border-color: #a7f3d0 !important;" title="B (80-89%)"><?php echo (int)$qs['range_b']; ?> B</span>
                                                        <span class="badge bg-success" title="A (90-100%)"><?php echo (int)$qs['range_a']; ?> A</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="index.php?route=admin/analytics&action=exam-detail&exam_id=<?php echo (int)$qs['exam_id']; ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                                        <i data-lucide="bar-chart-2" style="width: 14px; height: 14px;"></i> Question Stats
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

                <!-- Section 3: Student Time spent / Engagement -->
                <div class="card border-0 shadow-sm" id="section_engagement">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold m-0 text-dark d-flex align-items-center gap-2">
                            <i data-lucide="clock" class="text-primary"></i> Student Engagement & Time Spent
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($timeSpent)): ?>
                            <div class="p-4 text-center text-muted">No student engagement records found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Email Address</th>
                                            <th>Logged Activities</th>
                                            <th>Total Exam Time Spent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($timeSpent as $ts): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($ts['student_name']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($ts['student_email']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-slate-800 border" style="background-color: #f8fafc; color: #334155;">
                                                        <?php echo (int)$ts['activity_count']; ?> actions
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark"><i data-lucide="clock" class="me-1 d-inline-block text-muted" style="width: 14px; height: 14px;"></i> <?php echo formatDuration(intval($ts['exam_time_spent'])); ?></span>
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

            <!-- Right Side: Live Activity Stream Feed -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100" id="section_activity_feed">
                    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold m-0 text-dark d-flex align-items-center gap-2">
                            <i data-lucide="activity" class="text-primary"></i> Live Activity Feed
                        </h5>
                        <span class="badge bg-primary">Live Updates</span>
                    </div>
                    <div class="card-body p-4" style="max-height: 850px; overflow-y: auto;">
                        <?php if (empty($recentActivity)): ?>
                            <div class="text-center text-muted py-5">
                                <i data-lucide="refresh-cw" class="mb-3 text-slate-300" style="width: 40px; height: 40px;"></i>
                                <p class="m-0">No course activities recorded yet.</p>
                                <small class="text-muted">Actions like logins, content views, quiz attempts, and certificates will appear here.</small>
                            </div>
                        <?php else: ?>
                            <div class="position-relative ps-3" style="border-left: 2px solid #e2e8f0;">
                                <?php foreach ($recentActivity as $act): ?>
                                    <div class="mb-4 position-relative">
                                        <!-- Dot Indicator -->
                                        <div class="position-absolute rounded-circle" style="width: 10px; height: 10px; left: -21px; top: 6px; border: 2px solid #ffffff; 
                                            <?php 
                                                if ($act['action'] === 'certificate_issued') {
                                                    echo 'background-color: #10b981;';
                                                } elseif ($act['action'] === 'quiz_submitted') {
                                                    echo 'background-color: #10b981;';
                                                } elseif ($act['action'] === 'quiz_started') {
                                                    echo 'background-color: #6366f1;';
                                                } elseif ($act['action'] === 'login') {
                                                    echo 'background-color: #4f46e5;';
                                                } else {
                                                    echo 'background-color: #0ea5e9;';
                                                }
                                            ?>">
                                        </div>
                                        
                                        <!-- Log Details -->
                                        <div class="ps-2">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <span class="fw-bold text-dark text-truncate" style="font-size: 0.875rem;" title="<?php echo htmlspecialchars($act['student_name']); ?>">
                                                    <?php echo htmlspecialchars($act['student_name']); ?>
                                                </span>
                                                <small class="text-muted text-nowrap" style="font-size: 0.75rem;">
                                                    <?php echo date('M d, H:i', strtotime($act['created_at'])); ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mt-1">
                                                <?php if ($act['action'] === 'login'): ?>
                                                    <span class="badge bg-primary-subtle text-primary">Logged In</span>
                                                <?php elseif ($act['action'] === 'document_viewed'): ?>
                                                    <span class="badge bg-info-subtle text-info">Viewed Document</span>
                                                <?php elseif ($act['action'] === 'link_opened'): ?>
                                                    <span class="badge bg-warning-subtle text-warning font-medium">Opened Link</span>
                                                <?php elseif ($act['action'] === 'quiz_started'): ?>
                                                    <span class="badge bg-secondary">Started Quiz</span>
                                                <?php elseif ($act['action'] === 'quiz_submitted'): ?>
                                                    <span class="badge bg-success-subtle text-success">Submitted Quiz</span>
                                                    <?php if ($act['meta']): ?>
                                                        <div class="small text-muted mt-1 bg-light p-2 rounded">
                                                            Score: <?php echo htmlspecialchars($act['meta']); ?>%
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($act['action'] === 'learning_path_item_completed'): ?>
                                                    <span class="badge bg-success">Completed Path Item</span>
                                                <?php elseif ($act['action'] === 'certificate_issued'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Issued Certificate</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($act['action']); ?></span>
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

    <?php endif; ?>
</div>

<script>
    // Initialize any tooltips or icons
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
