<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Bugs</h6>
                        <h2 class="card-text"><?php echo $bug_stats['total'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-bug fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <span>View Details</span>
                <a href="index.php?controller=bug&action=list" class="text-white"><i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Open Bugs</h6>
                        <h2 class="card-text"><?php echo $bug_stats['open'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-exclamation-circle fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <span>View Details</span>
                <a href="index.php?controller=bug&action=list&status=open" class="text-white"><i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Resolved Bugs</h6>
                        <h2 class="card-text"><?php echo $bug_stats['resolved'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-check-circle fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <span>View Details</span>
                <a href="index.php?controller=bug&action=list&status=resolved" class="text-white"><i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Projects</h6>
                        <h2 class="card-text"><?php echo $project_stats['total'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-project-diagram fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <span>View Details</span>
                <a href="index.php?controller=project&action=list" class="text-white"><i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Bugs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Severity</th>
                                <th>Project</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_bugs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No bugs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_bugs as $bug): ?>
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
                                        <td><?php echo $bug['project_name'] ?? 'N/A'; ?></td>
                                        <td>
                                            <a href="index.php?controller=bug&action=edit&id=<?= $bug->getId() ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="index.php?controller=bug&action=list" class="btn btn-primary btn-sm">View All Bugs</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recent_activity)): ?>
                        <li class="list-group-item">No recent activity</li>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <li class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo $activity['action']; ?></h6>
                                    <small><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo $activity['details']; ?></p>
                                <small>By <?php echo $activity['user_name']; ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrator'): ?>
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bug Status Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="bugStatusChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bug Severity Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="bugSeverityChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    // Bug Status Chart
    var statusCtx = document.getElementById('bugStatusChart').getContext('2d');
    var statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'],
            datasets: [{
                data: [
                    <?php echo $bug_stats['open'] ?? 0; ?>,
                    <?php echo $bug_stats['assigned'] ?? 0; ?>,
                    <?php echo $bug_stats['in_progress'] ?? 0; ?>,
                    <?php echo $bug_stats['resolved'] ?? 0; ?>,
                    <?php echo $bug_stats['closed'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#dc3545',
                    '#ffc107',
                    '#0d6efd',
                    '#198754',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Bug Severity Chart
    var severityCtx = document.getElementById('bugSeverityChart').getContext('2d');
    var severityChart = new Chart(severityCtx, {
        type: 'pie',
        data: {
            labels: ['Low', 'Medium', 'High', 'Critical'],
            datasets: [{
                data: [
                    <?php echo $bug_stats['low'] ?? 0; ?>,
                    <?php echo $bug_stats['medium'] ?? 0; ?>,
                    <?php echo $bug_stats['high'] ?? 0; ?>,
                    <?php echo $bug_stats['critical'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#0dcaf0',
                    '#0d6efd',
                    '#ffc107',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
<?php endif; ?>

<?php include 'views/layouts/footer.php'; ?>
