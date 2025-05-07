 
<?php include 'views/layouts/header.php'; ?>

<?php
// Alias the details array
$bug = $bugDetails;

// Fetch related names since project_name, reporter_name, assignee_name
// weren't in $bugDetails
$project_name  = '';
$reporter_name = '';
$assignee_name = 'Unassigned';

global $db;

// Project name
if (!empty($bug['project_id'])) {
    $stmt = $db->prepare("SELECT name FROM projects WHERE id = ?");
    $stmt->execute([$bug['project_id']]);
    $project_name = $stmt->fetchColumn() ?: '';
}

// Reporter name
if (!empty($bug['reported_by'])) {
    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$bug['reported_by']]);
    $reporter_name = $stmt->fetchColumn() ?: '';
}

// Assignee name
if (!empty($bug['assigned_to'])) {
    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$bug['assigned_to']]);
    $assignee_name = $stmt->fetchColumn() ?: 'Unassigned';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Bug Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?controller=bug&action=list" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Bugs
        </a>

        <?php if (
            $_SESSION['user_role'] === 'administrator'
            || ($_SESSION['user_role'] === 'staff'
                && $bug['assigned_to'] == $_SESSION['user_id'])
        ): ?>
            <a href="index.php?controller=bug&action=edit&id=<?php echo $bug['id']; ?>"
               class="btn btn-sm btn-warning ms-2">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
        <?php endif; ?>

        <button id="print-bug" class="btn btn-sm btn-info ms-2">
            <i class="fas fa-print me-1"></i> Print
        </button>

        <?php if (
            isset($_SESSION['github_token'])
            && in_array($_SESSION['user_role'], ['administrator','staff'])
        ): ?>
            <?php if (!$github_issue): ?>
                <a href="index.php?controller=github&action=createIssue&bug_id=<?php echo $bug['id']; ?>"
                   class="btn btn-sm btn-dark ms-2">
                    <i class="fab fa-github me-1"></i> Create GitHub Issue
                </a>
            <?php else: ?>
                <a href="index.php?controller=github&action=viewIssue&bug_id=<?php echo $bug['id']; ?>"
                   class="btn btn-sm btn-dark ms-2">
                    <i class="fab fa-github me-1"></i> View GitHub Issue
                </a>
                <a href="index.php?controller=github&action=syncIssue&bug_id=<?php echo $bug['id']; ?>"
                   class="btn btn-sm btn-outline-dark ms-2">
                    <i class="fas fa-sync me-1"></i> Sync with GitHub
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Main content -->
    <div class="col-md-8">
        <!-- Title & Status -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($bug['title']); ?></h5>
                    <span class="badge bg-<?php
                        echo $bug['status'] === 'open'        ? 'danger'  :
                             ($bug['status'] === 'assigned'   ? 'warning' :
                             ($bug['status'] === 'in-progress'? 'primary' :
                             ($bug['status'] === 'resolved'   ? 'success':'secondary')));
                    ?>">
                        <?php echo ucfirst(htmlspecialchars($bug['status'])); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Description -->
                <div class="mb-4">
                    <h6 class="fw-bold">Description</h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($bug['description'])); ?></p>
                </div>
                <!-- Steps -->
                <?php if (!empty($bug['steps'])): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Steps to Reproduce</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($bug['steps'])); ?></p>
                    </div>
                <?php endif; ?>
                <!-- Expected -->
                <?php if (!empty($bug['expected_result'])): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Expected Result</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($bug['expected_result'])); ?></p>
                    </div>
                <?php endif; ?>
                <!-- Actual -->
                <?php if (!empty($bug['actual_result'])): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Actual Result</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($bug['actual_result'])); ?></p>
                    </div>
                <?php endif; ?>
                <!-- Screenshot -->
                <?php if (!empty($bug['screenshot'])): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Screenshot</h6>
                        <img src="uploads/screenshots/<?php echo htmlspecialchars($bug['screenshot']); ?>"
                             alt="Bug Screenshot"
                             class="img-fluid img-thumbnail">
                    </div>
                <?php endif; ?>
                <!-- GitHub Issue Link -->
                <?php if ($github_issue): ?>
                    <div class="mb-4 github-issue-link">
                        <h6 class="fw-bold">GitHub Issue</h6>
                        <p class="mb-0">
                            <i class="fab fa-github me-1"></i>
                            <a href="https://github.com/<?php echo htmlspecialchars($github_issue['repo_owner']); ?>/<?php echo htmlspecialchars($github_issue['repo_name']); ?>/issues/<?php echo (int)$github_issue['issue_number']; ?>"
                               target="_blank">
                                <?php echo htmlspecialchars($github_issue['repo_owner'].'/'.$github_issue['repo_name']); ?>#
                                <?php echo (int)$github_issue['issue_number']; ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Comments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($comments)): ?>
                    <p class="text-center">No comments yet.</p>
                <?php else: ?>
                    <div class="comment-list">
                        <?php foreach ($comments as $c): ?>
                            <div class="comment mb-3">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($c['user_name']); ?></h6>
                                    <small><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($c['content'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <button id="comment-toggle" class="btn btn-primary">Add Comment</button>
                    <form id="comment-form"
                          method="POST"
                          action="index.php?controller=bug&action=addComment&id=<?php echo $bug['id']; ?>"
                          class="mt-3 d-none">
                        <div class="mb-3">
                            <label for="comment" class="form-label">Your Comment</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Submit Comment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Bug Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Bug Information</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Ticket Number:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($bug['ticket_number']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Project:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($project_name); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Severity:</span>
                        <span class="badge bg-<?php
                            echo $bug['severity'] === 'critical' ? 'danger'  :
                                 ($bug['severity'] === 'high'     ? 'warning':
                                 ($bug['severity'] === 'medium'   ? 'primary':'info'));
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($bug['severity'])); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Priority:</span>
                        <span class="badge bg-<?php
                            echo $bug['priority'] === 'high'    ? 'danger':
                                 ($bug['priority'] === 'medium' ? 'warning':'info');
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($bug['priority'])); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Reported By:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($reporter_name); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Assigned To:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($assignee_name); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Created:</span>
                        <span><?php echo date('M d, Y H:i', strtotime($bug['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Updated:</span>
                        <span><?php echo date('M d, Y H:i', strtotime($bug['updated_at'])); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <?php if (in_array($_SESSION['user_role'], ['administrator','staff'])): ?>
        <!-- Update Status -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Update Status</h5></div>
            <div class="card-body">
                <form method="POST"
                      action="index.php?controller=bug&action=updateStatus&id=<?php echo $bug['id']; ?>">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach (['open','assigned','in-progress','resolved','closed'] as $s): ?>
                                <option value="<?php echo $s; ?>"
                                    <?php echo $bug['status'] === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="assigned-to-field" class="mb-3 <?php echo $bug['status'] !== 'assigned' ? 'd-none' : ''; ?>">
                        <label for="assigned_to" class="form-label">Assign To</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">Select Staff</option>
                            <?php foreach ($staff_members as $s): ?>
                                <option value="<?php echo $s['id']; ?>"
                                    <?php echo $bug['assigned_to'] == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status_comment" class="form-label">Comment (Optional)</label>
                        <textarea class="form-control" id="status_comment" name="status_comment" rows="2"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
```
