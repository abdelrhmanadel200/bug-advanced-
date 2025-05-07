<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
         
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Track Bug</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php?controller=bug&action=list" class="btn btn-sm btn-outline-secondary">Back to Bugs</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Enter Bug Ticket Number</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="index.php?controller=bug&action=track">
                                <div class="mb-3">
                                    <label for="ticket_number" class="form-label">Ticket Number</label>
                                    <input type="text" class="form-control" id="ticket_number" name="ticket_number" placeholder="e.g. BUG-1234567890-1234" required>
                                    <div class="form-text">Enter the bug ticket number to track its status.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Track Bug</button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (isset($bug) && $bug): ?>
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Bug Details</h5>
                            </div>
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($bug->getTitle()); ?></h5>
                                <p><strong>Ticket Number:</strong> <?php echo $bug->getTicketNumber(); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $bug->getStatus() === 'open' ? 'danger' : 
                                            ($bug->getStatus() === 'assigned' ? 'warning' : 
                                            ($bug->getStatus() === 'in-progress' ? 'info' : 
                                            ($bug->getStatus() === 'resolved' ? 'success' : 'secondary'))); 
                                    ?>">
                                        <?php echo ucfirst($bug->getStatus()); ?>
                                    </span>
                                </p>
                                <p><strong>Severity:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $bug->getSeverity() === 'critical' ? 'danger' : 
                                            ($bug->getSeverity() === 'high' ? 'warning' : 
                                            ($bug->getSeverity() === 'medium' ? 'info' : 'success')); 
                                    ?>">
                                        <?php echo ucfirst($bug->getSeverity()); ?>
                                    </span>
                                </p>
                                <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($bug->getCreatedAt())); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M d, Y', strtotime($bug->getUpdatedAt())); ?></p>
                                
                                <a href="index.php?controller=bug&action=view&id=<?php echo $bug->getId(); ?>" class="btn btn-primary">View Full Details</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
