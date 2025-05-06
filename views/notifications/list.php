<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Notifications</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <a href="notifications.php?action=mark_all_read" class="btn btn-sm btn-outline-secondary">Mark All as Read</a>
                        <a href="notifications.php?action=delete_all" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete all notifications?');">Delete All</a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'unread' ? 'active' : ''; ?>" href="notifications.php?tab=unread">Unread</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] == 'all' ? 'active' : ''; ?>" href="notifications.php?tab=all">All Notifications</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="alert alert-info">No notifications found.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div class="d-flex">
                                            <div class="mr-3">
                                                <?php if ($notification['type'] == 'assignment'): ?>
                                                    <i class="fas fa-tasks fa-2x text-primary"></i>
                                                <?php elseif ($notification['type'] == 'status'): ?>
                                                    <i class="fas fa-exchange-alt fa-2x text-success"></i>
                                                <?php elseif ($notification['type'] == 'comment'): ?>
                                                    <i class="fas fa-comment fa-2x text-info"></i>
                                                <?php elseif ($notification['type'] == 'security'): ?>
                                                    <i class="fas fa-shield-alt fa-2x text-danger"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-bell fa-2x text-secondary"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h5 class="mb-1"><?php echo $notification['message']; ?></h5>
                                                <small class="text-muted">
                                                    <?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div>
                                            <?php if (!$notification['is_read']): ?>
                                                <a href="notifications.php?action=mark_read&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-secondary">Mark as Read</a>
                                            <?php endif; ?>
                                            <a href="notifications.php?action=delete&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                        </div>
                                    </div>
                                    <?php if ($notification['link']): ?>
                                        <a href="<?php echo $notification['link']; ?>" class="mt-2 btn btn-sm btn-primary">View Details</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
