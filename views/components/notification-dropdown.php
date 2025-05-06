<div class="dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <?php if ($count > 0): ?>
            <span class="badge badge-danger"><?php echo $count; ?></span>
        <?php endif; ?>
    </a>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationDropdown">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <span>Notifications (<?php echo $count; ?>)</span>
            <?php if ($count > 0): ?>
                <a href="notifications.php?action=mark_all_read" class="text-muted small">Mark all as read</a>
            <?php endif; ?>
        </div>
        <div class="dropdown-divider"></div>
        <?php if ($count > 0): ?>
            <?php foreach ($notifications as $notification): ?>
                <a class="dropdown-item" href="<?php echo $notification['link'] ?? 'notifications.php?action=mark_read&id=' . $notification['id']; ?>">
                    <div class="d-flex">
                        <div class="mr-2">
                            <?php if ($notification['type'] == 'assignment'): ?>
                                <i class="fas fa-tasks text-primary"></i>
                            <?php elseif ($notification['type'] == 'status'): ?>
                                <i class="fas fa-exchange-alt text-success"></i>
                            <?php elseif ($notification['type'] == 'comment'): ?>
                                <i class="fas fa-comment text-info"></i>
                            <?php elseif ($notification['type'] == 'security'): ?>
                                <i class="fas fa-shield-alt text-danger"></i>
                            <?php else: ?>
                                <i class="fas fa-bell text-secondary"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="small text-muted">
                                <?php echo timeAgo($notification['created_at']); ?>
                            </div>
                            <div><?php echo $notification['message']; ?></div>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="dropdown-item text-center">No new notifications</div>
            <div class="dropdown-divider"></div>
        <?php endif; ?>
        <a class="dropdown-item text-center" href="notifications.php">View all notifications</a>
    </div>
</div>
