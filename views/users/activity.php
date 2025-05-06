<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Activity Log</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?controller=user&action=list" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Activity Log for <?php echo $user->name; ?></h5>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <p class="text-center">No activity logs found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo $log['action']; ?></td>
                                <td><?php echo $log['details']; ?></td>
                                <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
