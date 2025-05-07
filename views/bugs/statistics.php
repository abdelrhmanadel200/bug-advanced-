<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
         
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bug Statistics</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php?controller=bug&action=list" class="btn btn-sm btn-outline-secondary">Back to Bugs</a>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bug Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="bugStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bug Severity Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="bugSeverityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bugs Per Project</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="bugsPerProjectChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bug Resolution Time (Average Days)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="bugResolutionTimeChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bugs Reported Per Month</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="bugsPerMonthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Bug Status Distribution Chart
    const statusCtx = document.getElementById('bugStatusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'],
            datasets: [{
                data: [
                    <?php echo $bugStats['status']['open'] ?? 0; ?>,
                    <?php echo $bugStats['status']['assigned'] ?? 0; ?>,
                    <?php echo $bugStats['status']['in_progress'] ?? 0; ?>,
                    <?php echo $bugStats['status']['resolved'] ?? 0; ?>,
                    <?php echo $bugStats['status']['closed'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#dc3545', // Danger (Open)
                    '#ffc107', // Warning (Assigned)
                    '#17a2b8', // Info (In Progress)
                    '#28a745', // Success (Resolved)
                    '#6c757d'  // Secondary (Closed)
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
    
    // Bug Severity Distribution Chart
    const severityCtx = document.getElementById('bugSeverityChart').getContext('2d');
    const severityChart = new Chart(severityCtx, {
        type: 'pie',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                data: [
                    <?php echo $bugStats['severity']['critical'] ?? 0; ?>,
                    <?php echo $bugStats['severity']['high'] ?? 0; ?>,
                    <?php echo $bugStats['severity']['medium'] ?? 0; ?>,
                    <?php echo $bugStats['severity']['low'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#dc3545', // Danger (Critical)
                    '#ffc107', // Warning (High)
                    '#17a2b8', // Info (Medium)
                    '#28a745'  // Success (Low)
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
    
    // Bugs Per Project Chart
    const projectCtx = document.getElementById('bugsPerProjectChart').getContext('2d');
    const projectChart = new Chart(projectCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                    foreach ($projectStats as $project) {
                        echo "'" . addslashes($project['name']) . "', ";
                    }
                ?>
            ],
            datasets: [{
                label: 'Number of Bugs',
                data: [
                    <?php 
                        foreach ($projectStats as $project) {
                            echo $project['bug_count'] . ", ";
                        }
                    ?>
                ],
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Bug Resolution Time Chart
    const resolutionCtx = document.getElementById('bugResolutionTimeChart').getContext('2d');
    const resolutionChart = new Chart(resolutionCtx, {
        type: 'bar',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                label: 'Average Days to Resolve',
                data: [
                    <?php echo $bugStats['resolution_time']['critical'] ?? 0; ?>,
                    <?php echo $bugStats['resolution_time']['high'] ?? 0; ?>,
                    <?php echo $bugStats['resolution_time']['medium'] ?? 0; ?>,
                    <?php echo $bugStats['resolution_time']['low'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#dc3545', // Danger (Critical)
                    '#ffc107', // Warning (High)
                    '#17a2b8', // Info (Medium)
                    '#28a745'  // Success (Low)
                ]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Bugs Per Month Chart
    const monthCtx = document.getElementById('bugsPerMonthChart').getContext('2d');
    const monthChart = new Chart(monthCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                    foreach ($bugStats['monthly'] as $month => $count) {
                        echo "'" . $month . "', ";
                    }
                ?>
            ],
            datasets: [{
                label: 'Bugs Reported',
                data: [
                    <?php 
                        foreach ($bugStats['monthly'] as $month => $count) {
                            echo $count . ", ";
                        }
                    ?>
                ],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>

<?php include 'views/layouts/footer.php'; ?>
