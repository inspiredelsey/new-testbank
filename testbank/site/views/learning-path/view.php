<?php
/**
 * Student Learning Path View - Test Bank LMS
 * Renders the sequenced course curriculum with locked, unlocked, in_progress, and completed states.
 * Standardizes layout and typography in alignment with the platform's Inter / Space Grotesk guidelines.
 */
$pageTitle = htmlspecialchars($course['title']) . ' - Learning Path';
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<!-- Back Link & Header -->
<div class="mb-4">
    <a href="index.php?route=student/dashboard" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-2">
        <i data-lucide="arrow-left" size="14"></i> Back to Dashboard
    </a>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="display-font fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['title']); ?></h3>
            <p class="text-muted small mb-0">Follow the sequenced curriculum path below to unlock and complete your course milestones.</p>
        </div>
    </div>
</div>

<!-- Progress Meter Panel -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-dark fw-bold font-sans">Curriculum Milestones Completed</span>
            <span class="badge bg-primary text-primary fw-bold font-sans px-2.5 py-1.5"><?php echo $percentage; ?>% Complete</span>
        </div>
        <div class="progress rounded-pill bg-light border" style="height: 12px;">
            <div class="progress-bar rounded-pill bg-primary transition-all" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 text-muted small font-sans">
            <span><?php echo $completedCount; ?> of <?php echo $totalCount; ?> steps complete</span>
            <?php if ($percentage === 100): ?>
                <span class="text-success fw-bold d-flex align-items-center gap-1"><i data-lucide="award" size="14"></i> Congratulations! You have unlocked all steps!</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Curriculum Path Sequence -->
<div class="row">
    <div class="col-lg-8">
        <h5 class="fw-bold text-dark mb-3 font-sans"><i data-lucide="compass" class="text-primary me-1" size="18"></i>Course Syllabus Steps</h5>
        
        <?php if (empty($progressItems)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i data-lucide="map" class="text-muted d-block mx-auto mb-3" size="48"></i>
                    <h5 class="fw-bold">Learning Path is Empty</h5>
                    <p class="text-muted small">No learning path items have been published for this course yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($progressItems as $index => $item): ?>
                    <?php
                    $isLocked = ($item['status'] === 'locked');
                    $isCompleted = ($item['status'] === 'completed');
                    $isInProgress = ($item['status'] === 'in_progress');
                    
                    // Card background styling based on lock and completion status
                    $cardClasses = 'border-0 shadow-sm transition-all ';
                    if ($isLocked) {
                        $cardClasses .= 'opacity-75 bg-light';
                    } elseif ($isCompleted) {
                        $cardClasses .= 'border-start border-5 border-success bg-white';
                    } elseif ($isInProgress) {
                        $cardClasses .= 'border-start border-5 border-warning bg-white';
                    } else {
                        $cardClasses .= 'bg-white';
                    }
                    ?>
                    <div class="card <?php echo $cardClasses; ?>" id="lp-card-<?php echo $item['learning_path_item_id']; ?>">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <!-- Sequence Number Badge -->
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold font-sans" 
                                     style="width: 32px; height: 32px; font-size: 0.85rem; background-color: <?php echo $isCompleted ? '#10b981' : ($isLocked ? '#94a3b8' : ($isInProgress ? '#f59e0b' : 'var(--primary-indigo)')); ?>">
                                    <?php if ($isCompleted): ?>
                                        <i data-lucide="check" size="16"></i>
                                    <?php else: ?>
                                        <?php echo $index + 1; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Icon representation -->
                                <div class="p-2 border rounded-3 bg-white">
                                    <?php if ($item['item_type'] === 'document'): ?>
                                        <i data-lucide="file-text" class="text-danger" size="20"></i>
                                    <?php elseif ($item['item_type'] === 'link'): ?>
                                        <i data-lucide="link" class="text-info" size="20"></i>
                                    <?php elseif ($item['item_type'] === 'quiz'): ?>
                                        <i data-lucide="award" class="text-primary" size="20"></i>
                                    <?php endif; ?>
                                </div>

                                <!-- Item Description & Info -->
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-dark fw-bold"><?php echo htmlspecialchars($item['title']); ?></span>
                                        <span class="badge bg-light text-secondary border font-sans small text-capitalize" style="font-size: 0.7rem;"><?php echo htmlspecialchars($item['item_type']); ?></span>
                                        <?php if ($isCompleted): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle font-sans" style="font-size: 0.7rem;">Completed</span>
                                        <?php elseif ($isInProgress): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-sans" style="font-size: 0.7rem;">In Progress</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($isLocked): ?>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-1 font-sans" style="font-size: 0.75rem;">
                                            <i data-lucide="lock" size="12" class="text-danger"></i> 
                                            Prerequisite: Complete "<?php echo htmlspecialchars($item['prerequisite_title'] ?? 'previous milestones'); ?>" first to unlock.
                                        </small>
                                    <?php elseif ($isCompleted && !empty($item['completed_at'])): ?>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-1 font-sans" style="font-size: 0.75rem;">
                                            <i data-lucide="calendar" size="12"></i> Finished on <?php echo date('M d, Y H:i', strtotime($item['completed_at'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-success d-flex align-items-center gap-1 mt-1 font-sans" style="font-size: 0.75rem;">
                                            <i data-lucide="unlock" size="12"></i> Milestone Active & Unlocked
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Interactive Button -->
                            <div>
                                <?php if ($isLocked): ?>
                                    <button class="btn btn-sm btn-light border font-sans" disabled style="cursor: not-allowed;">
                                        <i data-lucide="lock" size="14" class="me-1"></i> Locked
                                    </button>
                                <?php else: ?>
                                    <!-- Clicking into this item calls markInProgress() then redirects immediately to resources -->
                                    <a href="index.php?route=student/learning-path/access&id=<?php echo $item['learning_path_item_id']; ?>" 
                                       target="_blank" 
                                       onclick="handleAccessClick(<?php echo $item['learning_path_item_id']; ?>)" 
                                       class="btn btn-sm <?php echo $isCompleted ? 'btn-light border text-secondary' : 'btn-primary'; ?> d-inline-flex align-items-center gap-1 font-sans">
                                        <?php if ($item['item_type'] === 'document'): ?>
                                            <i data-lucide="eye" size="14"></i> <?php echo $isCompleted ? 'View Again' : 'View Document'; ?>
                                        <?php elseif ($item['item_type'] === 'link'): ?>
                                            <i data-lucide="external-link" size="14"></i> <?php echo $isCompleted ? 'Visit Again' : 'Open Link'; ?>
                                        <?php elseif ($item['item_type'] === 'quiz'): ?>
                                            <i data-lucide="play" size="14"></i> <?php echo $isCompleted ? 'Review Assessment' : 'Take Quiz'; ?>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar stats summary panel -->
    <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card border-0 shadow-sm p-4 bg-light-subtle">
            <h5 class="fw-bold text-dark mb-3">Milestone Progress Summary</h5>
            <div class="d-flex flex-column gap-3 font-sans">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white border rounded">
                        <i data-lucide="file-text" class="text-danger" size="16"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">DOCUMENTS</small>
                        <span class="text-dark fw-bold small">
                            <?php 
                            $docsTotal = 0; $docsDone = 0;
                            foreach ($progressItems as $item) {
                                if ($item['item_type'] === 'document') {
                                    $docsTotal++;
                                    if ($item['status'] === 'completed') $docsDone++;
                                }
                            }
                            echo "$docsDone of $docsTotal completed";
                            ?>
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white border rounded">
                        <i data-lucide="link" class="text-info" size="16"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">RESOURCES & LINKS</small>
                        <span class="text-dark fw-bold small">
                            <?php 
                            $linksTotal = 0; $linksDone = 0;
                            foreach ($progressItems as $item) {
                                if ($item['item_type'] === 'link') {
                                    $linksTotal++;
                                    if ($item['status'] === 'completed') $linksDone++;
                                }
                            }
                            echo "$linksDone of $linksTotal completed";
                            ?>
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white border rounded">
                        <i data-lucide="award" class="text-primary" size="16"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">QUIZZES & ASSESSMENTS</small>
                        <span class="text-dark fw-bold small">
                            <?php 
                            $quizzesTotal = 0; $quizzesDone = 0;
                            foreach ($progressItems as $item) {
                                if ($item['item_type'] === 'quiz') {
                                    $quizzesTotal++;
                                    if ($item['status'] === 'completed') $quizzesDone++;
                                }
                            }
                            echo "$quizzesDone of $quizzesTotal completed";
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Click handler to reload page after opening a resource link/file.
 * Allows the student to focus back on the tab with progress immediately unlocked.
 */
function handleAccessClick(lpItemId) {
    setTimeout(function() {
        window.location.reload();
    }, 1500);
}
</script>

<?php include __DIR__ . '/../../../admin/views/layout_footer.php'; ?>
