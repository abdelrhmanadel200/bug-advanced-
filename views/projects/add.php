<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
         
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Project</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php?controller=project&action=list" class="btn btn-sm btn-outline-secondary">Back to Projects</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="index.php?controller=project&action=add">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="on-hold">On Hold</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Add Project</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Project Guidelines</h5>
                        </div>
                        <div class="card-body">
                            <p>When creating a new project, please consider the following:</p>
                            <ul>
                                <li>Use a clear and descriptive name</li>
                                <li>Provide detailed information in the description</li>
                                <li>Set the appropriate status for the project</li>
                            </ul>
                            <p>Once created, you can add bugs and assign team members to this project.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
