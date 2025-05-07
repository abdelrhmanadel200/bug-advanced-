<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
         
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($project->getName()); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                            <a href="index.php?controller=project&action=edit&id=<?php echo $project->getId(); ?>" class="btn btn-sm btn-outline-secondary">Edit Project</a>
                            <a href="index.php?controller=project&action=delete&id=<?php echo $project->getId(); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this project?');">Delete Project</a>
                        <?php endif; ?>
                        <a href="index.php?controller=project&action=list" class="btn btn-sm btn-outline-secondary">Back to Projects</a>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Project Details</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $project->getStatus() === 'active' ? 'success' : 
                                        ($project->getStatus() === 'completed' ? 'primary' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($project->getStatus()); ?>
                                </span>
                            </p>
                            <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($project->getCreatedAt())); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('M d, Y', strtotime($project->getUpdatedAt())); ?></p>
                            
                            <h6 class="mt-4">Description</h6>
                            <p><?php echo nl2br(htmlspecialchars($project->getDescription())); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bug Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Bugs:</span>
                                <span class="badge bg-secondary"><?php echo $bug_stats['total'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Open:</span>
                                <span class="badge bg-danger"><?php echo $bug_stats['open'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Assigned:</span>
                                <span class="badge bg-warning"><?php echo $bug_stats['assigned'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>In Progress:</span>
                                <span class="badge bg-info"><?php echo $bug_stats['in_progress'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Resolved:</span>
                                <span class="badge bg-success"><?php echo $bug_stats['resolved'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Closed:</span>
                                <span class="badge bg-secondary"><?php echo $bug_stats['closed'] ?? 0; ?></span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Critical:</span>
                                <span class="badge bg-danger"><?php echo $bug_stats['critical'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>High:</span>
                                <span class="badge bg-warning"><?php echo $bug_stats['high'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Medium:</span>
                                <span class="badge bg-info"><?php echo $bug_stats['medium'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Low:</span>
                                <span class="badge bg-success"><?php echo $bug_stats['low'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Project Bugs</h5>
                    <?php if ($_SESSION['user_role'] === 'customer'): ?>
                        <a href="index.php?controller=bug&action=report&project_id=<?php echo $project->getId(); ?>" class="btn btn-sm btn-primary">Report Bug</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($bugs)): ?>
                        <p class="text-center">No bugs reported for this project yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Reported By</th>
                                        <th>Assigned To</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bugs as $bug): ?>
                                        <tr>
                                            <td><?php echo $bug['ticket_number']; ?></td>
                                            <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $bug['severity'] === 'critical' ? 'danger' : 
                                                        ($bug['severity'] === 'high' ? 'warning' : 
                                                        ($bug['severity'] === 'medium' ? 'info' : 'success')); 
                                                ?>">
                                                    <?php echo ucfirst($bug['severity']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $bug['status'] === 'open' ? 'danger' : 
                                                        ($bug['status'] === 'assigned' ? 'warning' : 
                                                        ($bug['status'] === 'in-progress' ? 'info' : 
                                                        ($bug['status'] === 'resolved' ? 'success' : 'secondary'))); 
                                                ?>">
                                                    <?php echo ucfirst($bug['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($bug['reporter_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($bug['assignee_name'] ?? 'Unassigned'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($bug['created_at'])); ?></td>
                                            <td>
                                                <a href="index.php?controller=bug&action=edit&id=<?= $bug->getId() ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
