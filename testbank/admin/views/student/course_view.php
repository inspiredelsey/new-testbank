<?php
$pageTitle = htmlspecialchars($course['title']);
include __DIR__ . '/../layout_header.php';

// Calculate progress percentage
$totalItems = count($lpItems);
$completedCount = 0;
foreach ($lpItems as $item) {
    if (isset($progress[$item['id']]) && $progress[$item['id']]['completed']) {
        $completedCount++;
    }
}
$percentage = $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0;
?>

<!-- Back & Title -->
<div class="mb-4">
    <a href="index.php?route=student/dashboard" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-2">
        <i data-lucide="arrow-left" size="14"></i> Back to Dashboard
    </a>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="display-font fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['title']); ?></h3>
            <p class="text-muted small mb-0">Follow the sequenced Learning Path below to complete the course curriculum.</p>
        </div>
    </div>
</div>

<!-- Progress Panel -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-dark fw-bold font-sans">Course Curriculum Progress</span>
            <span class="badge bg-primary text-primary fw-bold font-sans px-2.5 py-1.5"><?php echo $percentage; ?>% Completed</span>
        </div>
        <div class="progress rounded-pill bg-light border" style="height: 12px;">
            <div class="progress-bar rounded-pill bg-primary transition-all" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 text-muted small">
            <span><?php echo $completedCount; ?> of <?php echo $totalItems; ?> milestones completed</span>
            <?php if ($percentage === 100): ?>
                <span class="text-success fw-bold d-flex align-items-center gap-1"><i data-lucide="award" size="14"></i> Outstanding! Course complete!</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Learning Path Sequencer -->
<div class="row">
    <div class="col-lg-8">
        <h5 class="fw-bold text-dark mb-3"><i data-lucide="activity" class="text-primary me-1"></i>Curriculum Path</h5>
        
        <?php if (empty($lpItems)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i data-lucide="map" class="text-muted d-block mx-auto mb-3" size="48"></i>
                    <h5 class="fw-bold">Learning Path is empty</h5>
                    <p class="text-muted small">Your instructor hasn't populated curriculum steps for this course yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($lpItems as $index => $item): ?>
                    <?php
                    $isCompleted = isset($progress[$item['id']]) && $progress[$item['id']]['completed'];
                    $isLocked = $this->lpModel->isItemLocked($currentUser['id'], $item, $progress);
                    ?>
                    <div class="card shadow-sm border-0 lp-student-card transition-all <?php echo $isLocked ? 'opacity-75 bg-light' : ''; ?> <?php echo $isCompleted ? 'border-start border-5 border-success' : ''; ?>" id="lp-card-<?php echo $item['id']; ?>">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <!-- Order circle badge -->
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold font-sans" style="width: 32px; height: 32px; font-size: 0.85rem; background-color: <?php echo $isCompleted ? '#10b981' : ($isLocked ? '#94a3b8' : 'var(--primary-indigo)'); ?>">
                                    <?php if ($isCompleted): ?>
                                        <i data-lucide="check" size="16"></i>
                                    <?php else: ?>
                                        <?php echo $index + 1; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Icon type indicator -->
                                <div class="p-2 border rounded-3 bg-white">
                                    <?php if ($item['type'] === 'document'): ?>
                                        <i data-lucide="file-text" class="text-danger" size="20"></i>
                                    <?php elseif ($item['type'] === 'link'): ?>
                                        <i data-lucide="link" class="text-info" size="20"></i>
                                    <?php else: ?>
                                        <i data-lucide="help-circle" class="text-primary" size="20"></i>
                                    <?php endif; ?>
                                </div>

                                <!-- Details -->
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-dark fw-bold"><?php echo htmlspecialchars($item['item_title']); ?></span>
                                        <span class="badge bg-light text-secondary border font-sans small" style="font-size: 0.7rem;"><?php echo ucfirst($item['type']); ?></span>
                                        <?php if ($isCompleted): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle font-sans" style="font-size: 0.7rem;">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($isLocked): ?>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-1 font-sans" style="font-size: 0.75rem;">
                                            <i data-lucide="lock" size="12" class="text-danger"></i> Prerequisite: <?php echo htmlspecialchars($item['prereq_title'] ?? 'Previous Milestone'); ?> must be completed first.
                                        </small>
                                    <?php elseif ($isCompleted): ?>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-1 font-sans" style="font-size: 0.75rem;">
                                            <i data-lucide="calendar" size="12"></i> Finished on <?php echo date('M d, Y H:i', strtotime($progress[$item['id']]['completed_at'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-primary d-flex align-items-center gap-1 mt-1 font-sans" style="font-size: 0.75rem;">
                                            <i data-lucide="unlock" size="12" class="text-success"></i> Milestone Active & Unlocked
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div>
                                <?php if ($isLocked): ?>
                                    <button class="btn btn-sm btn-light border font-sans" disabled>
                                        <i data-lucide="lock" size="14" class="me-1"></i> Locked
                                    </button>
                                <?php else: ?>
                                    <?php if ($item['type'] === 'document'): ?>
                                        <button class="btn btn-sm <?php echo $isCompleted ? 'btn-light border text-success' : 'btn-primary'; ?> d-flex align-items-center gap-1 font-sans" onclick="accessDocument(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_id']); ?>')">
                                            <i data-lucide="eye" size="14"></i> <?php echo $isCompleted ? 'View Again' : 'View Document'; ?>
                                        </button>
                                    <?php elseif ($item['type'] === 'link'): ?>
                                        <button class="btn btn-sm <?php echo $isCompleted ? 'btn-light border text-info' : 'btn-primary'; ?> d-flex align-items-center gap-1 font-sans" onclick="accessLink(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_id']); ?>')">
                                            <i data-lucide="external-link" size="14"></i> <?php echo $isCompleted ? 'Visit Again' : 'Open Link'; ?>
                                        </button>
                                    <?php elseif ($item['type'] === 'quiz'): ?>
                                        <?php if ($isCompleted): ?>
                                            <button class="btn btn-sm btn-light border text-success font-sans" disabled>
                                                <i data-lucide="check-circle" size="14" class="me-1"></i> Quiz Passed
                                            </button>
                                        <?php else: ?>
                                            <a href="index.php?route=student/exam/instructions&exam_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-1 font-sans">
                                                <i data-lucide="award" size="14"></i> Start Assessment
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar Details -->
    <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card border-0 shadow-sm p-4 bg-light-subtle">
            <h5 class="fw-bold text-dark mb-3">Course Milestones</h5>
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
                            foreach ($lpItems as $item) {
                                if ($item['type'] === 'document') {
                                    $docsTotal++;
                                    if (isset($progress[$item['id']]) && $progress[$item['id']]['completed']) $docsDone++;
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
                            foreach ($lpItems as $item) {
                                if ($item['type'] === 'link') {
                                    $linksTotal++;
                                    if (isset($progress[$item['id']]) && $progress[$item['id']]['completed']) $linksDone++;
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
                        <small class="text-muted d-block" style="font-size: 0.7rem;">QUIZZES & EXAMS</small>
                        <span class="text-dark fw-bold small">
                            <?php 
                            $quizzesTotal = 0; $quizzesDone = 0;
                            foreach ($lpItems as $item) {
                                if ($item['type'] === 'quiz') {
                                    $quizzesTotal++;
                                    if (isset($progress[$item['id']]) && $progress[$item['id']]['completed']) $quizzesDone++;
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

<!-- Access triggering helper scripts -->
<script>
// Documents mapping to trigger completion and open file
const documentsMap = {};
<?php foreach ($lpItems as $item): ?>
    <?php if ($item['type'] === 'document'): ?>
        documentsMap[<?php echo $item['item_id']; ?>] = '<?php echo addslashes($item['file_path'] ?? ''); ?>';
    <?php endif; ?>
<?php endforeach; ?>

// Links mapping
const linksMap = {};
<?php foreach ($lpItems as $item): ?>
    <?php if ($item['type'] === 'link'): ?>
        linksMap[<?php echo $item['item_id']; ?>] = '<?php echo addslashes($item['url'] ?? ''); ?>';
    <?php endif; ?>
<?php endforeach; ?>

function accessDocument(lpItemId, docId) {
    const filePath = documentsMap[docId];
    if (filePath) {
        window.open(filePath, '_blank');
        markItemCompleted(lpItemId);
    }
}

function accessLink(lpItemId, linkId) {
    const url = linksMap[linkId];
    if (url) {
        window.open(url, '_blank');
        markItemCompleted(lpItemId);
    }
}

function markItemCompleted(lpItemId) {
    const formData = new FormData();
    formData.append('csrf_token', '<?php echo Session::getCSRFToken(); ?>');
    formData.append('lp_item_id', lpItemId);

    fetch('index.php?route=student/course/complete_lp_item', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Instantly refresh the page to update progress bar and unlock downstream prerequisites!
            window.location.reload();
        }
    })
    .catch(err => {
        console.error('Error marking completion:', err);
    });
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
