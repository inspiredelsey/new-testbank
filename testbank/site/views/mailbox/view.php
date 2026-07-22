<?php
/**
 * Mailbox View Detail Page
 */
$pageTitle = 'Mailbox - ' . htmlspecialchars($message['subject']);
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<div class="container-fluid px-0">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <!-- Back Navigation -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <a href="index.php?route=site/mailbox&action=inbox" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Inbox
                </a>
                <a href="index.php?route=site/mailbox&action=reply&id=<?php echo (int)$message['id']; ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                    <i data-lucide="reply" style="width: 14px; height: 14px;"></i> Reply
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <!-- Header -->
                <div class="card-header bg-white p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <h3 class="fw-bold text-slate-900 display-font m-0">
                            <?php echo htmlspecialchars($message['subject']); ?>
                        </h3>
                        <span class="text-muted small text-nowrap mt-1">
                            <?php echo date('M d, Y H:i', strtotime($message['sent_at'])); ?>
                        </span>
                    </div>

                    <div class="mt-3 p-3 bg-light rounded-3">
                        <div class="row g-2 text-slate-700" style="font-size: 0.9rem;">
                            <div class="col-12 col-md-6">
                                <strong>From:</strong> 
                                <span class="text-dark fw-semibold"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                                <span class="text-muted small">&lt;<?php echo htmlspecialchars($message['sender_email']); ?>&gt;</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong>To:</strong> 
                                <?php if (!empty($message['recipient_group_id'])): ?>
                                    <span class="badge bg-secondary-subtle text-secondary fs-6">
                                        Group: <?php echo htmlspecialchars($message['group_name'] ?? ('Group #' . $message['recipient_group_id'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-dark fw-semibold"><?php echo htmlspecialchars($message['recipient_name'] ?? ('User #' . $message['recipient_id'])); ?></span>
                                    <?php if (!empty($message['recipient_email'])): ?>
                                        <span class="text-muted small">&lt;<?php echo htmlspecialchars($message['recipient_email']); ?>&gt;</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($message['course_title'])): ?>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <strong class="text-slate-600">Course Context:</strong> 
                                    <span class="badge bg-info-subtle text-info me-1">
                                        <i data-lucide="book" style="width: 12px; height: 12px;" class="me-1"></i><?php echo htmlspecialchars($message['course_title']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Message Body -->
                <div class="card-body p-4 text-slate-800" style="font-size: 0.975rem; line-height: 1.6; white-space: pre-wrap; font-family: inherit;">
<?php echo nl2br(htmlspecialchars($message['body'])); ?>
                </div>

                <!-- Footer Actions -->
                <div class="card-footer bg-white border-top p-3 d-flex align-items-center justify-content-between">
                    <a href="index.php?route=site/mailbox&action=inbox" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                        <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Inbox
                    </a>
                    <a href="index.php?route=site/mailbox&action=reply&id=<?php echo (int)$message['id']; ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 shadow-sm">
                        <i data-lucide="reply" style="width: 16px; height: 16px;"></i> Reply to Sender
                    </a>
                </div>
            </div>
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

<?php include __DIR__ . '/../../../admin/views/layout_footer.php'; ?>
