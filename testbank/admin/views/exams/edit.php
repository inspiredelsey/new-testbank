<?php
$pageTitle = 'Edit Exam';
include __DIR__ . '/../layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="settings" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">Edit Exam Configurations</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                        <i data-lucide="alert-circle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form action="index.php?route=admin/exams&action=edit&id=<?php echo $exam['id']; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCSRFToken(); ?>">
                    
                    <div class="row">
                        <!-- Title -->
                        <div class="col-12 mb-3">
                            <label for="title" class="form-label fw-medium">Exam Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                        </div>

                        <!-- Description -->
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label fw-medium">Instructions / Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($exam['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label fw-medium">Exam Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">-- Choose Category (Optional) --</option>
                                <?php foreach ($flatCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $exam['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['indented_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label fw-medium">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?php echo $exam['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $exam['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $exam['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>

                        <!-- Duration -->
                        <div class="col-md-4 col-sm-6 mb-3">
                            <label for="duration_minutes" class="form-label fw-medium">Duration (Minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" value="<?php echo $exam['duration_minutes']; ?>" min="5" required>
                        </div>

                        <!-- Pass Percentage -->
                        <div class="col-md-4 col-sm-6 mb-3">
                            <label for="pass_percentage" class="form-label fw-medium">Passing Percentage (%) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="pass_percentage" name="pass_percentage" value="<?php echo floatval($exam['pass_percentage']); ?>" min="1" max="100" step="1" required>
                        </div>

                        <!-- Max Attempts -->
                        <div class="col-md-4 col-sm-12 mb-3">
                            <label for="max_attempts" class="form-label fw-medium">Maximum Attempts Allowed</label>
                            <input type="number" class="form-control" id="max_attempts" name="max_attempts" value="<?php echo $exam['max_attempts']; ?>" min="0">
                            <div class="form-text">0 indicates unlimited attempts.</div>
                        </div>

                        <!-- Date Window Start -->
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label fw-medium">Availability Start Date/Time</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="<?php echo $exam['start_date'] ? date('Y-m-T\H:i', strtotime($exam['start_date'])) : ''; ?>">
                        </div>

                        <!-- Date Window End -->
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label fw-medium">Availability End Date/Time</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="<?php echo $exam['end_date'] ? date('Y-m-T\H:i', strtotime($exam['end_date'])) : ''; ?>">
                        </div>

                        <!-- Shuffle Settings -->
                        <div class="col-12 mt-3 mb-2">
                            <label class="form-label fw-bold">Test Randomization Controls</label>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="shuffle_questions" name="shuffle_questions" value="1" <?php echo $exam['shuffle_questions'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-medium" for="shuffle_questions">Shuffle questions order per student attempt</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="shuffle_options" name="shuffle_options" value="1" <?php echo $exam['shuffle_options'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-medium" for="shuffle_options">Shuffle multiple-choice answer choices per question</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5 border-top pt-4">
                        <a href="index.php?route=admin/exams" class="btn btn-light border d-flex align-items-center gap-2">
                            <i data-lucide="arrow-left" size="18"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i data-lucide="save" size="18"></i> Update Exam Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
