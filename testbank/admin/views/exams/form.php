<?php
/**
 * Exam Create/Edit Form View
 */
$isEdit = isset($id) && $id > 0;
$pageTitle = $isEdit ? 'Edit Exam Configuration' : 'Create Exam';
include __DIR__ . '/../layout_header.php';

$csrfToken = Session::getCSRFToken();
?>

<div class="mb-4">
    <a href="index.php?route=admin/exams" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
        <i data-lucide="arrow-left" size="14"></i>
        <span>Back to Exams</span>
    </a>
    <h1 class="h2 mb-1 display-font"><?php echo $isEdit ? 'Configure Exam Settings' : 'Create New Exam'; ?></h1>
    <p class="text-muted mb-0"><?php echo $isEdit ? 'Update exam timing, rules, and course linkage' : 'Define your exam metadata and general settings'; ?></p>
</div>

<!-- Errors Alert -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i data-lucide="alert-circle" class="text-danger" size="18"></i>
            <span class="fw-semibold">Please correct the following issues:</span>
        </div>
        <ul class="mb-0 ps-4 small">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-9 col-xl-8">
        <form action="<?php echo $isEdit ? 'index.php?route=admin/exams&action=edit&id=' . $id : 'index.php?route=admin/exams&action=create'; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <!-- Main configuration card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="exam-config-card">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i data-lucide="settings" class="text-primary" size="18"></i>
                        <span>General Metadata</span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label for="title" class="form-label fw-semibold">Exam Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control form-control-lg" placeholder="e.g., Midterm Clinical Assessment" value="<?php echo htmlspecialchars($data['title']); ?>" required>
                        <div class="form-text">Choose a descriptive and clear title for students.</div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Briefly describe the purpose of the exam, topics covered, or specific instructions..."><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
                        <div class="form-text">Supports general plain text instructions displayed to students before they start.</div>
                    </div>

                    <div class="row g-3 mb-2">
                        <!-- Associated Course -->
                        <div class="col-md-6">
                            <label for="course_id" class="form-label fw-semibold">Link to Course</label>
                            <select id="course_id" name="course_id" class="form-select">
                                <option value="">None (Reusable Bank Quiz)</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo (($data['course_id'] ?? '') == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Choose which course this exam is presented in, or "None" to make it a general bank template.</div>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-semibold">Exam Subject Category</label>
                            <select id="category_id" name="category_id" class="form-select">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($flatCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo (($data['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $cat['depth'] ?? 0) . htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Helps organize the exam in the test bank catalog.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parameters and rules card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="exam-params-card">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i data-lucide="sliders" class="text-primary" size="18"></i>
                        <span>Exam Parameters</span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <!-- Duration -->
                        <div class="col-md-4">
                            <label for="duration_minutes" class="form-label fw-semibold">Duration (Minutes) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" min="1" value="<?php echo intval($data['duration_minutes']); ?>" required>
                                <span class="input-group-text">mins</span>
                            </div>
                            <div class="form-text">Time limit allowed for students.</div>
                        </div>

                        <!-- Passing percentage -->
                        <div class="col-md-4">
                            <label for="pass_percentage" class="form-label fw-semibold">Passing Grade (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="pass_percentage" name="pass_percentage" class="form-control" step="0.5" min="0" max="100" value="<?php echo floatval($data['pass_percentage']); ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Percentage required to pass.</div>
                        </div>

                        <!-- Max Attempts -->
                        <div class="col-md-4">
                            <label for="max_attempts" class="form-label fw-semibold">Max Attempts <span class="text-danger">*</span></label>
                            <input type="number" id="max_attempts" name="max_attempts" class="form-control" min="0" value="<?php echo intval($data['max_attempts']); ?>" required>
                            <div class="form-text">0 for unlimited student attempts.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Availability Start -->
                        <div class="col-md-6">
                            <label for="start_date" class="form-label fw-semibold">Availability Start</label>
                            <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($data['start_date'] ?? ''); ?>">
                            <div class="form-text">When students can start taking the exam. Leave empty for immediate.</div>
                        </div>

                        <!-- Availability End -->
                        <div class="col-md-6">
                            <label for="end_date" class="form-label fw-semibold">Availability End</label>
                            <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($data['end_date'] ?? ''); ?>">
                            <div class="form-text">Deadline for taking the exam. Leave empty for no deadline.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Gradebook Category -->
                        <div class="col-md-6">
                            <label for="gradebook_category" class="form-label fw-semibold">Gradebook Category <span class="text-danger">*</span></label>
                            <select id="gradebook_category" name="gradebook_category" class="form-select">
                                <option value="summative" <?php echo ($data['gradebook_category'] === 'summative') ? 'selected' : ''; ?>>Summative (Affects course final grade)</option>
                                <option value="formative" <?php echo ($data['gradebook_category'] === 'formative') ? 'selected' : ''; ?>>Formative (Practice / self-assessment quiz)</option>
                            </select>
                            <div class="form-text">Determines weight and scoring behavior in student grade reports.</div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Workflow Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select">
                                <option value="draft" <?php echo ($data['status'] === 'draft') ? 'selected' : ''; ?>>Draft (Hidden from students)</option>
                                <option value="published" <?php echo ($data['status'] === 'published') ? 'selected' : ''; ?>>Published (Available if dates match)</option>
                                <option value="archived" <?php echo ($data['status'] === 'archived') ? 'selected' : ''; ?>>Archived (Retired / read-only)</option>
                            </select>
                            <div class="form-text">Students cannot see or attempt "Draft" exams.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Shuffling options -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                                <div class="pe-3">
                                    <label class="form-check-label fw-semibold text-dark" for="shuffle_questions">Shuffle Questions</label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Randomizes question presentation sequence for each student attempt.</div>
                                </div>
                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="shuffle_questions" name="shuffle_questions" value="1" <?php echo !empty($data['shuffle_questions']) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                                <div class="pe-3">
                                    <label class="form-check-label fw-semibold text-dark" for="shuffle_options">Shuffle Options</label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Randomizes MCQ and SATA choices presentation order on each page load.</div>
                                </div>
                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="shuffle_options" name="shuffle_options" value="1" <?php echo !empty($data['shuffle_options']) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="d-flex align-items-center justify-content-between gap-3 p-4 bg-white border rounded-3 shadow-sm mb-5">
                <a href="index.php?route=admin/exams" class="btn btn-outline-secondary px-4 py-2" id="btn-cancel-form">
                    Cancel & Return
                </a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2" id="btn-save-exam">
                    <i data-lucide="check-circle" size="18"></i>
                    <span><?php echo $isEdit ? 'Save Settings' : 'Create & Proceed'; ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
