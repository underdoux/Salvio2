<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <h2>Security Dashboard</h2>

    <!-- Security Status Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Overall Security Status</h5>
                    <div class="security-score text-center">
                        <h2 class="mb-0 <?php echo $securityScore >= 80 ? 'text-success' : ($securityScore >= 60 ? 'text-warning' : 'text-danger'); ?>">
                            <?php echo $securityScore; ?>%
                        </h2>
                        <small>Security Score</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Active Threats</h5>
                    <div class="text-center">
                        <h2 class="mb-0 <?php echo $activeThreats == 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $activeThreats; ?>
                        </h2>
                        <small>Detected Threats</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Last Scan</h5>
                    <div class="text-center">
                        <h4 class="mb-0"><?php echo $lastScanDate; ?></h4>
                        <small><?php echo $lastScanStatus; ?></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Security Tests</h5>
                    <div class="text-center">
                        <h2 class="mb-0"><?php echo $testsPassedCount; ?>/<?php echo $totalTestsCount; ?></h2>
                        <small>Tests Passed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Testing Controls -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Security Testing</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <form action="/security/penetration-test" method="POST">
                                <button type="submit" class="btn btn-primary btn-block mb-3">
                                    Run Penetration Test
                                </button>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <form action="/security/stress-test" method="POST">
                                <button type="submit" class="btn btn-info btn-block mb-3">
                                    Run Stress Test
                                </button>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <form action="/security/vulnerability-scan" method="POST">
                                <button type="submit" class="btn btn-warning btn-block mb-3">
                                    Run Vulnerability Scan
                                </button>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <form action="/security/full-audit" method="POST">
                                <button type="submit" class="btn btn-danger btn-block mb-3">
                                    Run Full Security Audit
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Results -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Latest Penetration Test Results</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Status</th>
                                    <th>Severity</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($penetrationTests as $test): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($test['name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $test['status'] == 'Passed' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $test['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $test['severity'] == 'High' ? 'bg-danger' : ($test['severity'] == 'Medium' ? 'bg-warning' : 'bg-info'); ?>">
                                            <?php echo $test['severity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#testDetail<?php echo $test['id']; ?>">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Latest Vulnerability Scan Results</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Vulnerability</th>
                                    <th>Risk Level</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vulnerabilities as $vuln): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vuln['name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $vuln['risk'] == 'High' ? 'bg-danger' : ($vuln['risk'] == 'Medium' ? 'bg-warning' : 'bg-info'); ?>">
                                            <?php echo $vuln['risk']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $vuln['status']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#vulnDetail<?php echo $vuln['id']; ?>">
                                            Fix Now
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stress Test Results -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stress Test Results</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="stressTestChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Metric</th>
                                            <th>Value</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stressTestMetrics as $metric): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($metric['name']); ?></td>
                                            <td><?php echo $metric['value']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $metric['status'] == 'Good' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $metric['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Detail Modals -->
<?php foreach ($penetrationTests as $test): ?>
<div class="modal fade" id="testDetail<?php echo $test['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Details: <?php echo htmlspecialchars($test['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Description:</strong> <?php echo htmlspecialchars($test['description']); ?></p>
                <p><strong>Impact:</strong> <?php echo htmlspecialchars($test['impact']); ?></p>
                <p><strong>Recommendation:</strong> <?php echo htmlspecialchars($test['recommendation']); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Take Action</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Vulnerability Detail Modals -->
<?php foreach ($vulnerabilities as $vuln): ?>
<div class="modal fade" id="vulnDetail<?php echo $vuln['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vulnerability: <?php echo htmlspecialchars($vuln['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Description:</strong> <?php echo htmlspecialchars($vuln['description']); ?></p>
                <p><strong>Impact:</strong> <?php echo htmlspecialchars($vuln['impact']); ?></p>
                <p><strong>Fix Steps:</strong></p>
                <ol>
                    <?php foreach ($vuln['fixSteps'] as $step): ?>
                    <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Apply Fix</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Stress Test Chart
const ctx = document.getElementById('stressTestChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($stressTestLabels); ?>,
        datasets: [{
            label: 'Response Time (ms)',
            data: <?php echo json_encode($stressTestData); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
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
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
