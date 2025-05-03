<div class="analytics-page">
    <div class="page-header">
        <h2>Analytics Dashboard</h2>
        <div class="actions">
            <button type="button" 
                    class="btn btn-primary"
                    onclick="showCalculateModal()">
                <i class="fas fa-calculator"></i> Calculate Analytics
            </button>
            <div class="btn-group">
                <button type="button" 
                        class="btn btn-success dropdown-toggle" 
                        data-toggle="dropdown">
                    <i class="fas fa-file-export"></i> Export
                </button>
                <div class="dropdown-menu">
                    <a href="<?= $baseUrl ?>/analytics/export?type=sales" class="dropdown-item">
                        Sales Trends
                    </a>
                    <a href="<?= $baseUrl ?>/analytics/export?type=market" class="dropdown-item">
                        Market Response
                    </a>
                    <a href="<?= $baseUrl ?>/analytics/export?type=performance" class="dropdown-item">
                        Performance Metrics
                    </a>
                    <a href="<?= $baseUrl ?>/analytics/export?type=predictions" class="dropdown-item">
                        Predictive Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/analytics" class="filters-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <select name="year" class="form-control">
                        <?php 
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 5; $y--): 
                        ?>
                            <option value="<?= $y ?>" 
                                    <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <select name="month" class="form-control">
                        <option value="">All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" 
                                    <?= ($filters['month'] ?? '') == $m ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/analytics" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Performance Summary -->
    <div class="summary-section">
        <div class="summary-cards">
            <?php
            $metrics = [
                'sales' => ['icon' => 'shopping-cart', 'color' => 'primary'],
                'profit' => ['icon' => 'chart-line', 'color' => 'success'],
                'commission' => ['icon' => 'percentage', 'color' => 'info'],
                'expense' => ['icon' => 'money-bill-wave', 'color' => 'danger']
            ];

            if (!empty($performanceMetrics) && is_array($performanceMetrics)):
                foreach ($performanceMetrics as $metric):
                    $config = $metrics[$metric['metric_type']] ?? ['icon' => 'chart-bar', 'color' => 'secondary'];
            ?>
                <div class="summary-card">
                    <div class="card-icon text-<?= $config['color'] ?>">
                        <i class="fas fa-<?= $config['icon'] ?>"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-title">
                            <?= ucfirst($metric['metric_type']) ?>
                        </div>
                        <div class="card-value">
                            <?= CurrencyFormatter::getInstance()->format($metric['metric_value']) ?>
                        </div>
                        <?php if ($metric['growth_rate']): ?>
                            <div class="card-trend <?= $metric['growth_rate'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <i class="fas fa-<?= $metric['growth_rate'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= abs(round($metric['growth_rate'], 1)) ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($metrics as $type => $config): ?>
                <div class="summary-card">
                    <div class="card-icon text-<?= $config['color'] ?>">
                        <i class="fas fa-<?= $config['icon'] ?>"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-title">
                            <?= ucfirst($type) ?>
                        </div>
                        <div class="card-value">
                            <?= CurrencyFormatter::getInstance()->format(0) ?>
                        </div>
                        <div class="card-trend text-muted">
                            <i class="fas fa-minus"></i>
                            No data
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="row">
            <!-- Sales Trends Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Trends</h3>
                        <a href="<?= $baseUrl ?>/analytics/trends" class="btn btn-sm btn-outline-secondary">
                            View Details
                        </a>
                    </div>
                    <div class="chart-body">
                        <canvas id="salesTrendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Market Response Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Market Response</h3>
                        <a href="<?= $baseUrl ?>/analytics/market-response" class="btn btn-sm btn-outline-secondary">
                            View Details
                        </a>
                    </div>
                    <div class="chart-body">
                        <canvas id="marketResponseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Performance Metrics Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Performance Metrics</h3>
                        <a href="<?= $baseUrl ?>/analytics/performance" class="btn btn-sm btn-outline-secondary">
                            View Details
                        </a>
                    </div>
                    <div class="chart-body">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Predictive Analytics Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Predictions</h3>
                        <a href="<?= $baseUrl ?>/analytics/predictions" class="btn btn-sm btn-outline-secondary">
                            View Details
                        </a>
                    </div>
                    <div class="chart-body">
                        <canvas id="predictionsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calculate Modal -->
<div class="modal fade" id="calculateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Calculate Analytics</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= $baseUrl ?>/analytics/calculate" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="calc_year">Year</label>
                        <select id="calc_year" name="year" class="form-control" required>
                            <?php 
                            $currentYear = date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--): 
                            ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="calc_month">Month</label>
                        <select id="calc_month" name="month" class="form-control" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Calculate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.analytics-page {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filters-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.summary-section {
    margin-bottom: 30px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.summary-card {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.card-icon {
    font-size: 2em;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 50%;
}

.card-content {
    flex: 1;
}

.card-title {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.card-value {
    font-size: 1.5em;
    font-weight: bold;
    margin-bottom: 5px;
}

.card-trend {
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 5px;
}

.charts-section .row {
    margin-bottom: 20px;
}

.chart-card {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    height: 100%;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h3 {
    margin: 0;
    font-size: 1.2em;
}

.chart-body {
    position: relative;
    height: 300px;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
    }

    .actions {
        width: 100%;
        display: flex;
        gap: 10px;
    }

    .actions .btn {
        flex: 1;
    }

    .summary-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to create chart
    function createChart(elementId, data, config) {
        const ctx = document.getElementById(elementId);
        if (!data || !Array.isArray(data) || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
        }
        new Chart(ctx, config);
    }

    // Sales Trends Chart
    const salesTrendsData = <?= json_encode($salesTrends ?? []) ?>;
    createChart('salesTrendsChart', salesTrendsData, {
        type: 'line',
        data: {
            labels: salesTrendsData.map(d => `${d.year}-${d.month}`),
            datasets: [{
                label: 'Sales Amount',
                data: salesTrendsData.map(d => d.total_amount),
                borderColor: '#007bff',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Market Response Chart
    const marketResponseData = <?= json_encode($marketResponse ?? []) ?>;
    createChart('marketResponseChart', marketResponseData, {
        type: 'bar',
        data: {
            labels: marketResponseData.map(d => d.customer_type),
            datasets: [{
                label: 'Response Score',
                data: marketResponseData.map(d => d.response_score),
                backgroundColor: '#28a745'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Performance Metrics Chart
    const performanceData = <?= json_encode($performanceMetrics ?? []) ?>;
    createChart('performanceChart', performanceData, {
        type: 'line',
        data: {
            labels: performanceData.map(d => `${d.year}-${d.month}`),
            datasets: [{
                label: 'Growth Rate',
                data: performanceData.map(d => d.growth_rate),
                borderColor: '#17a2b8',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Predictive Analytics Chart
    const predictionsData = <?= json_encode($predictiveAnalytics ?? []) ?>;
    createChart('predictionsChart', predictionsData, {
        type: 'line',
        data: {
            labels: predictionsData.map(d => d.prediction_date),
            datasets: [{
                label: 'Predicted Sales',
                data: predictionsData.map(d => d.predicted_sales),
                borderColor: '#ffc107',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});

function showCalculateModal() {
    $('#calculateModal').modal('show');
}
</script>
