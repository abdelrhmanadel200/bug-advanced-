<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Projects</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
            <a href="index.php?controller=project&action=add" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Add Project
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="card-title mb-0">Project List</h5>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="search-input" class="form-control" placeholder="Search projects...">
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
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Bugs</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No projects found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo $project['id']; ?></td>
                                <td><?php echo $project['name']; ?></td>
                                <td><?php echo substr($project['description'], 0, 50) . (strlen($project['description']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $project['bug_count'] ?? 0; ?></td>
                                <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="index.php?controller=project&action=view&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                                            <a href="index.php?controller=project&action=edit&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <a href="index.php?controller=project&action=delete&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this project?');">
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
