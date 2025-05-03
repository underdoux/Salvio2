<div class="performance-page">
    <div class="page-header">
        <h2>Performance Metrics</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/analytics/export?type=performance" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export Data
            </a>
            <a href="<?= $baseUrl ?>/analytics" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Analytics
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/analytics/performance" class="filters-form">
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
                    <select name="metric_type" class="form-control">
                        <option value="">All Metrics</option>
                        <option value="sales" <?= ($filters['metric_type'] ?? '') === 'sales' ? 'selected' : '' ?>>
                            Sales
                        </option>
                        <option value="profit" <?= ($filters['metric_type'] ?? '') === 'profit' ? 'selected' : '' ?>>
                            Profit
                        </option>
                        <option value="commission" <?= ($filters['metric_type'] ?? '') === 'commission' ? 'selected' : '' ?>>
                            Commission
                        </option>
                        <option value="expense" <?= ($filters['metric_type'] ?? '') === 'expense' ? 'selected' : '' ?>>
                            Expense
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/analytics/performance" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="summary-section">
        <div class="summary-cards">
            <?php
            $metricIcons = [
                'sales' => 'shopping-cart',
                'profit' => 'chart-line',
                'commission' => 'percentage',
                'expense' => 'money-bill-wave'
            ];

            $metricColors = [
                'sales' => 'primary',
                'profit' => 'success',
                'commission' => 'info',
                'expense' => 'danger'
            ];

            foreach ($metrics as $type => $data):
                if (!isset($data['current'])) continue;
            ?>
                <div class="summary-card">
                    <div class="card-icon text-<?= $metricColors[$type] ?>">
                        <i class="fas fa-<?= $metricIcons[$type] ?>"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-title"><?= ucfirst($type) ?></div>
                        <div class="card-value">
                            <?= CurrencyFormatter::getInstance()->format($data['current']) ?>
                        </div>
                        <?php if (isset($data['growth'])): ?>
                            <div class="card-trend <?= $data['growth'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <i class="fas fa-<?= $data['growth'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= abs(round($data['growth'], 1)) ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="row">
            <!-- Metric Values Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Metric Values Over Time</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="metricValuesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Growth Rates Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Growth Rates Over Time</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="growthRatesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Metric Comparison Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Metric Comparison</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Trend Analysis Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Trend Analysis</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Data -->
    <div class="data-section mt-4">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Metric Type</th>
                        <th>Value</th>
                        <th>Previous Value</th>
                        <th>Growth Rate</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($metrics)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No metric data found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($metrics as $metric): ?>
                            <tr>
                                <td><?= date('F Y', mktime(0, 0, 0, $metric['month'], 1, $metric['year'])) ?></td>
                                <td>
                                    <span class="badge badge-<?= $metricColors[$metric['metric_type']] ?>">
                                        <?= ucfirst($metric['metric_type']) ?>
                                    </span>
                                </td>
                                <td><?= CurrencyFormatter::getInstance()->format($metric['metric_value']) ?></td>
                                <td>
                                    <?= $metric['comparison_value'] ? 
                                        CurrencyFormatter::getInstance()->format($metric['comparison_value']) : 
                                        '-' ?>
                                </td>
                                <td>
                                    <?php if ($metric['growth_rate'] !== null): ?>
                                        <span class="<?= $metric['growth_rate'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($metric['growth_rate'], 1) ?>%
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($metric['notes'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.performance-page {
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

.data-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.table th {
    background-color: #f8f9fa;
}

.badge {
    padding: 5px 10px;
    border-radius: 15px;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const metricsData = <?= json_encode($metrics) ?>;
    const periods = [...new Set(metricsData.map(d => `${d.year}-${d.month}`))];
    const metricTypes = [...new Set(metricsData.map(d => d.metric_type))];

    // Metric Values Chart
    new Chart(document.getElementById('metricValuesChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: metricTypes.map(type => ({
                label: ucfirst(type),
                data: periods.map(period => {
                    const [year, month] = period.split('-');
                    const data = metricsData.find(d => 
                        d.year == year && 
                        d.month == month && 
                        d.metric_type === type
                    );
                    return data ? data.metric_value : 0;
                }),
                borderColor: getMetricColor(type),
                tension: 0.1
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Growth Rates Chart
    new Chart(document.getElementById('growthRatesChart'), {
        type: 'bar',
        data: {
            labels: periods,
            datasets: metricTypes.map(type => ({
                label: ucfirst(type),
                data: periods.map(period => {
                    const [year, month] = period.split('-');
                    const data = metricsData.find(d => 
                        d.year == year && 
                        d.month == month && 
                        d.metric_type === type
                    );
                    return data ? data.growth_rate : 0;
                }),
                backgroundColor: getMetricColor(type)
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Metric Comparison Chart
    new Chart(document.getElementById('comparisonChart'), {
        type: 'radar',
        data: {
            labels: metricTypes.map(ucfirst),
            datasets: [{
                label: 'Current Period',
                data: metricTypes.map(type => {
                    const data = metricsData.find(d => d.metric_type === type);
                    return data ? data.metric_value : 0;
                }),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.2)'
            }, {
                label: 'Previous Period',
                data: metricTypes.map(type => {
                    const data = metricsData.find(d => d.metric_type === type);
                    return data ? data.comparison_value : 0;
                }),
                borderColor: '#6c757d',
                backgroundColor: 'rgba(108, 117, 125, 0.2)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Trend Analysis Chart
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: metricTypes.map(type => ({
                label: ucfirst(type),
                data: calculateTrend(periods, type),
                borderColor: getMetricColor(type),
                borderDash: [5, 5],
                tension: 0.4
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});

function getMetricColor(type) {
    const colors = {
        'sales': '#007bff',
        'profit': '#28a745',
        'commission': '#17a2b8',
        'expense': '#dc3545'
    };
    return colors[type] || '#6c757d';
}

function calculateTrend(periods, type) {
    // Simple moving average
    const values = periods.map(period => {
        const [year, month] = period.split('-');
        const data = metricsData.find(d => 
            d.year == year && 
            d.month == month && 
            d.metric_type === type
        );
        return data ? data.metric_value : 0;
    });

    const windowSize = 3;
    return values.map((_, index) => {
        if (index < windowSize - 1) return null;
        const window = values.slice(index - windowSize + 1, index + 1);
        return window.reduce((sum, val) => sum + val, 0) / windowSize;
    });
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
