<?php
/**
 * Mailbox Inbox View
 */
$pageTitle = 'Mailbox - Inbox';
include __DIR__ . '/../../../admin/views/layout_header.php';
?>

<div class="container-fluid px-0">
    <!-- Header & Nav Tabs -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h3 class="fw-bold m-0 text-slate-900 display-font d-flex align-items-center gap-2">
                    <i data-lucide="mail" class="text-primary"></i> Internal Mailbox
                </h3>
                <p class="text-muted m-0 mt-1">Communicate directly with instructors, administrators, students, and groups.</p>
            </div>
            <div>
                <a href="index.php?route=site/mailbox&action=compose" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                    <i data-lucide="pen-tool" style="width: 16px; height: 16px;"></i> Compose Message
                </a>
            </div>
        </div>
        <div class="card-footer bg-white border-top p-0 px-4">
            <ul class="nav nav-tabs border-0" id="mailboxTabs">
                <li class="nav-item">
                    <a class="nav-link py-3 active fw-semibold d-flex align-items-center gap-2" href="index.php?route=site/mailbox&action=inbox">
                        <i data-lucide="inbox" style="width: 16px; height: 16px;"></i> Inbox
                        <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-3 fw-semibold text-muted d-flex align-items-center gap-2" href="index.php?route=site/mailbox&action=sent">
                        <i data-lucide="send" style="width: 16px; height: 16px;"></i> Sent Messages
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Inbox Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($messages)): ?>
                <div class="p-5 text-center text-muted">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i data-lucide="inbox" class="text-slate-300" style="width: 32px; height: 32px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Your Inbox is Empty</h5>
                    <p class="m-0 text-muted mx-auto" style="max-width: 400px;">When instructors, administrators, or group members send you messages, they will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-slate-600">
                            <tr>
                                <th style="width: 5%;"></th>
                                <th style="width: 25%;">From / Context</th>
                                <th style="width: 45%;">Subject</th>
                                <th style="width: 15%;">Received</th>
                                <th style="width: 10%; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $m): ?>
                                <?php $isUnread = empty($m['is_read']); ?>
                                <tr class="<?php echo $isUnread ? 'bg-indigo-50/30 fw-semibold' : ''; ?>" style="<?php echo $isUnread ? 'background-color: #f8fafc;' : ''; ?>">
                                    <td class="text-center">
                                        <?php if ($isUnread): ?>
                                            <span class="d-inline-block rounded-circle bg-primary" style="width: 10px; height: 10px;" title="Unread Message"></span>
                                        <?php else: ?>
                                            <i data-lucide="mail-check" class="text-muted opacity-50" style="width: 16px; height: 16px;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-bold" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($m['sender_name']); ?>
                                        </div>
                                        <?php if (!empty($m['group_name'])): ?>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.75rem;">
                                                Via Group: <?php echo htmlspecialchars($m['group_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($m['course_title'])): ?>
                                            <div class="text-muted small text-truncate" style="max-width: 200px;">
                                                <i data-lucide="book" style="width: 12px; height: 12px;" class="me-1"></i><?php echo htmlspecialchars($m['course_title']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?route=site/mailbox&action=view&id=<?php echo (int)$m['id']; ?>" class="text-decoration-none text-dark d-block">
                                            <div class="<?php echo $isUnread ? 'fw-bold text-slate-900' : 'text-slate-800'; ?>" style="font-size: 0.925rem;">
                                                <?php echo htmlspecialchars($m['subject']); ?>
                                            </div>
                                            <div class="text-muted fw-normal small text-truncate mt-1" style="max-width: 450px;">
                                                <?php echo htmlspecialchars(mb_strimwidth(strip_tags($m['body']), 0, 90, '...')); ?>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="text-muted small text-nowrap">
                                            <?php echo date('M d, Y H:i', strtotime($m['sent_at'])); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="index.php?route=site/mailbox&action=view&id=<?php echo (int)$m['id']; ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                            Open <i data-lucide="arrow-right" style="width: 12px; height: 12px;"></i>
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
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php include __DIR__ . '/../../../admin/views/layout_footer.php'; ?>
