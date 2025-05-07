<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
         
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bug Case Flow: <?php echo htmlspecialchars($bug->getTitle()); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php?controller=bug&action=view&id=<?php echo $bug->getId(); ?>" class="btn btn-sm btn-outline-secondary">Back to Bug</a>
                        <a href="index.php?controller=bug&action=list" class="btn btn-sm btn-outline-secondary">Back to Bugs</a>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Bug History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                        <p class="text-center">No history available for this bug.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($history as $index => $entry): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5 class="timeline-title">
                                            <?php echo date('M d, Y h:i A', strtotime($entry['created_at'])); ?>
                                             <small class="text-muted">by <?php echo htmlspecialchars($entry['user_name'] ?? 'System'); ?></small>
                                        </h5>
                                        <p class="timeline-text">
                                            <?php 
                                                if ($entry['field_name'] === 'status') {
                                                    echo "Status changed from <strong>" . ucfirst($entry['old_value']) . "</strong> to <strong>" . ucfirst($entry['new_value']) . "</strong>";
                                                } elseif ($entry['field_name'] === 'created') {
                                                    echo "Bug was created";
                                                } elseif ($entry['field_name'] === 'comment') {
                                                    echo "Comment added";
                                                } elseif ($entry['field_name'] === 'attachment') {
                                                    echo "Attachment added: " . $entry['new_value'];
                                                } else {
                                                    echo ucfirst($entry['field_name']) . " changed from <strong>" . $entry['old_value'] . "</strong> to <strong>" . $entry['new_value'] . "</strong>";
                                                }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
        margin-bottom: 20px;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #ddd;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -30px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #007bff;
    }
    
    .timeline-content {
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .timeline-title {
        margin-bottom: 5px;
        font-size: 1rem;
    }
    
    .timeline-text {
        margin-bottom: 0;
    }
</style>

<?php include 'views/layouts/footer.php'; ?>
