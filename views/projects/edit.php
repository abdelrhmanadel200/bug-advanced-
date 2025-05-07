<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
         
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Project</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php?controller=project&action=view&id=<?php echo $project->getId(); ?>" class="btn btn-sm btn-outline-secondary">View Project</a>
                        <a href="index.php?controller=project&action=list" class="btn btn-sm btn-outline-secondary">Back to Projects</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="index.php?controller=project&action=edit&id=<?php echo $project->getId(); ?>">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($project->getName()); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($project->getDescription()); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $project->getStatus() === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="completed" <?php echo $project->getStatus() === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="on-hold" <?php echo $project->getStatus() === 'on-hold' ? 'selected' : ''; ?>>On Hold</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Update Project</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Project Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($project->getCreatedAt())); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('M d, Y', strtotime($project->getUpdatedAt())); ?></p>
                            
                            <hr>
                            
                            <p>When editing a project, please consider the following:</p>
                            <ul>
                                <li>Use a clear and descriptive name</li>
                                <li>Provide detailed information in the description</li>
                                <li>Update the status as the project progresses</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
