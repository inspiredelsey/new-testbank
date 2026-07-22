<?php
/**
 * Detailed Exam/Quiz Analytics Analysis
 */
$pageTitle = 'Quiz Question Analysis';
require_once __DIR__ . '/../layout_header.php';

function formatQuestionType($type) {
    switch ($type) {
        case 'multiple_choice': return 'Multiple Choice';
        case 'true_false': return 'True/False';
        case 'matching': return 'Matching';
        case 'ordering': return 'Ordering';
        case 'fill_in_blank': return 'Fill-in-the-Blank';
        case 'short_answer': return 'Short Answer';
        case 'essay': return 'Essay / Free Text';
        case 'multiple_response': return 'Multiple Response';
        case 'hotspot': return 'Hotspot Selection';
        case 'numerical': return 'Numerical Answer';
        case 'drag_drop': return 'Drag & Drop';
        case 'likert': return 'Likert Scale';
        case 'file_upload': return 'File Upload';
        case 'matrix': return 'Matrix Grid';
        default: return ucwords(str_replace('_', ' ', $type));
    }
}
?>

<div class="container-fluid px-0">
    <!-- Header Navigation Back Link -->
    <div class="mb-4">
        <a href="index.php?route=admin/analytics&action=dashboard&course_id=<?php echo (int)$exam['course_id']; ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Analytics Dashboard
        </a>
    </div>

    <!-- Exam Context Header Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary text-white" style="width: 56px; height: 56px;">
                    <i data-lucide="file-spreadsheet" style="width: 28px; height: 28px;"></i>
                </div>
                <div>
                    <h3 class="fw-bold m-0 text-slate-900 display-font"><?php echo htmlspecialchars($exam['title']); ?></h3>
                    <p class="text-muted m-0 mt-1">
                        Course: <span class="fw-semibold text-dark"><?php echo htmlspecialchars($course['title']); ?></span>
                        <?php if ($exam['description']): ?>
                             &bull; <?php echo htmlspecialchars(strip_tags($exam['description'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Analysis Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom p-4">
            <h5 class="fw-bold m-0 text-dark d-flex align-items-center gap-2">
                <i data-lucide="help-circle" class="text-primary"></i> Question-by-Question Difficulty & Performance Analysis
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($questionStats)): ?>
                <div class="p-5 text-center text-muted">
                    <i data-lucide="alert-circle" class="text-slate-300 mb-3" style="width: 48px; height: 48px;"></i>
                    <h5 class="fw-semibold text-dark">No Attempt Data Available</h5>
                    <p class="m-0 mx-auto" style="max-width: 400px;">To calculate question success rates and difficulty ranks, students must first complete and submit attempts for this quiz.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 8%;">ID</th>
                                <th style="width: 40%;">Question Text</th>
                                <th>Type</th>
                                <th>Times Answered</th>
                                <th>Success Rate</th>
                                <th>Difficulty Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questionStats as $q): ?>
                                <tr>
                                    <td>
                                        <span class="font-mono text-muted small">#<?php echo (int)$q['question_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark text-wrap" style="max-width: 550px;">
                                            <?php echo htmlspecialchars($q['question_text']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-slate-800 border" style="background-color: #f8fafc; color: #475569;">
                                            <?php echo formatQuestionType($q['question_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-slate-800"><?php echo (int)$q['times_answered']; ?></span>
                                        <span class="text-muted small d-block"><?php echo (int)$q['correct_answers']; ?> correct</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 80px; border-radius: 3px; background-color: #f1f5f9;">
                                                <div class="progress-bar <?php 
                                                    if ($q['success_rate'] < 40) echo 'bg-danger';
                                                    elseif ($q['success_rate'] > 80) echo 'bg-success';
                                                    else echo 'bg-warning';
                                                ?>" role="progressbar" style="width: <?php echo $q['success_rate']; ?>%; border-radius: 3px;" aria-valuenow="<?php echo $q['success_rate']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small fw-bold text-dark"><?php echo $q['success_rate']; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($q['difficulty'] === 'Easy'): ?>
                                            <span class="badge bg-success">Easy</span>
                                        <?php elseif ($q['difficulty'] === 'Medium'): ?>
                                            <span class="badge bg-warning">Medium</span>
                                        <?php elseif ($q['difficulty'] === 'Hard'): ?>
                                            <span class="badge bg-danger">Hard</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
