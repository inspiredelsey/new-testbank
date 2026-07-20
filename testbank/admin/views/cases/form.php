<?php
$isEdit = !empty($case);
$pageTitle = $isEdit ? 'Edit Case Study' : 'Create Case Study';
include __DIR__ . '/../layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="index.php?route=admin/cases&action=list" class="btn btn-light border p-2 rounded-3 d-flex align-items-center">
            <i data-lucide="arrow-left" size="18" class="text-muted"></i>
        </a>
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold"><?php echo $pageTitle; ?></h1>
            <p class="text-muted mb-0"><?php echo $isEdit ? 'Update clinical overview information.' : 'Start a new NGN-aligned Case Study container.'; ?></p>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
            <i data-lucide="alert-circle" class="text-danger"></i>
            <div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-8 col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold text-secondary d-flex align-items-center gap-2">
                        <i data-lucide="book" class="text-primary"></i> Case Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold text-muted small">Case Study Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required placeholder="e.g. Clinical Nursing Judgment: Acute Chest Pain Case">
                        </div>

                        <div class="row mb-3">
                            <!-- Category -->
                            <div class="col-sm-6">
                                <label for="category_id" class="form-label fw-semibold text-muted small">Category / Domain <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="" disabled selected>-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Trend Case Checkbox -->
                            <div class="col-sm-6 d-flex align-items-center mt-3 mt-sm-0">
                                <div class="form-check form-switch bg-light p-3 border rounded-3 w-100 d-flex align-items-center">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="is_trend" name="is_trend" value="1" <?php echo !empty($is_trend) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold text-dark cursor-pointer mb-0" for="is_trend">
                                        Is Trend Case Study
                                        <span class="d-block text-muted small fw-normal">Exhibits show chronological change/progress</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Scenario Text -->
                        <div class="mb-4">
                            <label for="scenario_text" class="form-label fw-semibold text-muted small">Scenario Overview / Patient Profile <span class="text-danger">*</span></label>
                            <span class="d-block text-muted small mb-2">Write the clinical scenario or nurse summary. This background scenario is sticky and remains visible on the left alongside attached questions.</span>
                            <textarea class="form-control" id="scenario_text" name="scenario_text" rows="8" required placeholder="Describe the patient age, diagnosis, current complaints, physical presentation..."><?php echo htmlspecialchars($scenario_text); ?></textarea>
                        </div>

                        <div class="border-top pt-4 d-flex justify-content-end gap-2">
                            <a href="index.php?route=admin/cases&action=list" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                                <i data-lucide="save" size="18"></i> <?php echo $isEdit ? 'Save Changes' : 'Create Case Study'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
