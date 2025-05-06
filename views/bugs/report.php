<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Report Bug</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?controller=bug&action=list" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Bugs
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Bug Report Form</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="index.php?controller=bug&action=report" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="title" class="form-label">Bug Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                    <div class="invalid-feedback">
                        Please provide a bug title.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo $project['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">
                        Please select a project.
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                    <select class="form-select" id="severity" name="severity" required>
                        <option value="">Select Severity</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                    <div class="invalid-feedback">
                        Please select a severity level.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                <div class="invalid-feedback">
                    Please provide a description.
                </div>
                <small class="text-muted">Provide detailed information about the bug, including steps to reproduce.</small>
            </div>
            
            <div class="mb-3">
                <label for="screenshot" class="form-label">Screenshot (Optional)</label>
                <input class="form-control" type="file" id="screenshot" name="screenshot" accept="image/*">
                <div id="file-preview"></div>
              name="screenshot" accept="image/*">
                <div id="file-preview"></div>
            </div>
            
            <div class="mb-3">
                <label for="steps" class="form-label">Steps to Reproduce</label>
                <textarea class="form-control" id="steps" name="steps" rows="3"></textarea>
                <small class="text-muted">List the steps needed to reproduce this bug.</small>
            </div>
            
            <div class="mb-3">
                <label for="expected_result" class="form-label">Expected Result</label>
                <textarea class="form-control" id="expected_result" name="expected_result" rows="2"></textarea>
            </div>
            
            <div class="mb-3">
                <label for="actual_result" class="form-label">Actual Result</label>
                <textarea class="form-control" id="actual_result" name="actual_result" rows="2"></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                <button type="submit" class="btn btn-primary">Submit Bug Report</button>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
