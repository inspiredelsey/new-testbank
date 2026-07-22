<?php
/**
 * Mailbox Compose View
 */
$pageTitle = 'Mailbox - Compose Message';
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<div class="container-fluid px-0">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <a href="index.php?route=site/mailbox&action=inbox" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Inbox
                </a>
                <span class="text-muted small">New Message</span>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h4 class="fw-bold m-0 text-slate-900 display-font d-flex align-items-center gap-2">
                        <i data-lucide="pen-tool" class="text-primary" style="width: 20px; height: 20px;"></i> Compose Message
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 p-3 mb-4" role="alert">
                            <div class="d-flex align-items-center gap-2">
                                <i data-lucide="alert-circle" class="text-danger"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?route=site/mailbox&action=compose" method="POST" id="composeForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF::getToken()); ?>">

                        <!-- Recipient Type Selector (Person vs Group) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-slate-800">Send To <span class="text-danger">*</span></label>
                            <div class="btn-group w-100 role-selector" role="group" aria-label="Recipient type selection">
                                <input type="radio" class="btn-check" name="recipient_type" id="type_user" value="user" <?php echo ($prefillRecipientType !== 'group') ? 'checked' : ''; ?> autocomplete="off">
                                <label class="btn btn-outline-primary py-2 d-flex align-items-center justify-content-center gap-2" for="type_user">
                                    <i data-lucide="user" style="width: 16px; height: 16px;"></i> Specific Person
                                </label>

                                <input type="radio" class="btn-check" name="recipient_type" id="type_group" value="group" <?php echo ($prefillRecipientType === 'group') ? 'checked' : ''; ?> autocomplete="off">
                                <label class="btn btn-outline-primary py-2 d-flex align-items-center justify-content-center gap-2" for="type_group">
                                    <i data-lucide="users" style="width: 16px; height: 16px;"></i> Entire Group
                                </label>
                            </div>
                        </div>

                        <!-- Specific Person Select -->
                        <div class="mb-4" id="userRecipientWrapper">
                            <label for="recipient_id" class="form-label fw-bold text-slate-800">Select Recipient <span class="text-danger">*</span></label>
                            <select name="recipient_id" id="recipient_id" class="form-select form-select-lg" style="font-size: 0.95rem;">
                                <option value="">-- Choose a user --</option>
                                <?php foreach ($recipientUsers as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo ((string)$prefillRecipientId === (string)$u['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>) [<?php echo ucfirst(htmlspecialchars($u['role'])); ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Group Select -->
                        <div class="mb-4 d-none" id="groupRecipientWrapper">
                            <label for="recipient_group_id" class="form-label fw-bold text-slate-800">Select Group <span class="text-danger">*</span></label>
                            <select name="recipient_group_id" id="recipient_group_id" class="form-select form-select-lg" style="font-size: 0.95rem;">
                                <option value="">-- Choose a group --</option>
                                <?php foreach ($recipientGroups as $g): ?>
                                    <option value="<?php echo $g['id']; ?>" <?php echo ((string)$prefillRecipientGroupId === (string)$g['id']) ? 'selected' : ''; ?>>
                                        Group: <?php echo htmlspecialchars($g['name']); ?> <?php echo !empty($g['description']) ? ' - ' . htmlspecialchars($g['description']) : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Optional Course Context -->
                        <div class="mb-4">
                            <label for="course_id" class="form-label fw-semibold text-slate-700">Course Context <span class="text-muted small">(Optional)</span></label>
                            <select name="course_id" id="course_id" class="form-select">
                                <option value="">-- None / General Message --</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ((string)$prefillCourseId === (string)$c['id']) ? 'selected' : ''; ?>>
                                        Course: <?php echo htmlspecialchars($c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Subject -->
                        <div class="mb-4">
                            <label for="subject" class="form-label fw-bold text-slate-800">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject" class="form-control form-control-lg" placeholder="Enter message subject..." value="<?php echo htmlspecialchars($prefillSubject); ?>" required maxlength="200">
                        </div>

                        <!-- Body -->
                        <div class="mb-4">
                            <label for="body" class="form-label fw-bold text-slate-800">Message Body <span class="text-danger">*</span></label>
                            <textarea name="body" id="body" class="form-control" rows="8" placeholder="Write your message here..." required><?php echo htmlspecialchars($prefillBody); ?></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center justify-content-end gap-2 border-top pt-3">
                            <a href="index.php?route=site/mailbox&action=inbox" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 shadow-sm">
                                <i data-lucide="send" style="width: 16px; height: 16px;"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const typeUserRadio = document.getElementById("type_user");
        const typeGroupRadio = document.getElementById("type_group");
        const userWrapper = document.getElementById("userRecipientWrapper");
        const groupWrapper = document.getElementById("groupRecipientWrapper");
        const userSelect = document.getElementById("recipient_id");
        const groupSelect = document.getElementById("recipient_group_id");

        function toggleRecipientType() {
            if (typeGroupRadio.checked) {
                userWrapper.classList.add("d-none");
                groupWrapper.classList.remove("d-none");
                
                userSelect.disabled = true;
                userSelect.value = "";
                
                groupSelect.disabled = false;
                groupSelect.required = true;
            } else {
                groupWrapper.classList.add("d-none");
                userWrapper.classList.remove("d-none");
                
                groupSelect.disabled = true;
                groupSelect.value = "";
                
                userSelect.disabled = false;
                userSelect.required = true;
            }
        }

        typeUserRadio.addEventListener("change", toggleRecipientType);
        typeGroupRadio.addEventListener("change", toggleRecipientType);

        toggleRecipientType();

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php include __DIR__ . '/../../../admin/views/layout_footer.php'; ?>
