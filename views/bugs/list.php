<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Bugs</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($_SESSION['user_role'] === 'customer' || $_SESSION['user_role'] === 'administrator'): ?>
            <a href="index.php?controller=bug&action=report" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Report Bug
            </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] === 'administrator' || $_SESSION['user_role'] === 'staff'): ?>
            <a href="index.php?controller=bug&action=track" class="btn btn-sm btn-secondary ms-2">
                <i class="fas fa-search me-1"></i> Track Bug
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="card-title mb-0">Bug List</h5>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="search-input" class="form-control" placeholder="Search bugs...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Severity</th>
                        <th>Project</th>
                        <th>Reported By</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bugs)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No bugs found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bugs as $bug): ?>
                            <tr>
                                <td><?php echo $bug['ticket_number']; ?></td>
                                <td><?php echo $bug['title']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $bug['status'] === 'open' ? 'danger' : 
                                            ($bug['status'] === 'assigned' ? 'warning' : 
                                            ($bug['status'] === 'in-progress' ? 'primary' : 
                                            ($bug['status'] === 'resolved' ? 'success' : 'secondary'))); 
                                    ?>">
                                        <?php echo ucfirst($bug['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $bug['severity'] === 'critical' ? 'danger' : 
                                            ($bug['severity'] === 'high' ? 'warning' : 
                                            ($bug['severity'] === 'medium' ? 'primary' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($bug['severity']); ?>
                                    </span>
                                </td>
                                <td><?php echo $bug['project_name']; ?></td>
                                <td><?php echo $bug['reporter_name']; ?></td>
                                <td><?php echo $bug['assignee_name'] ?? 'Unassigned'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($bug['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="index.php?controller=bug&action=view&id=<?$bug->getId() ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($_SESSION['user_role'] === 'administrator' || ($_SESSION['user_role'] === 'staff' && $bug['assigned_to'] == $_SESSION['user_id'])): ?>
                                            <a href="index.php?controller=bug&action=edit&id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                                            <a href="index.php?controller=bug&action=delete&id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this bug?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
