<!DOCTYPE html>
<html>
<head>
    <title>Profit Sharing Trends</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .metric-card {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .metric-label {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Profit Sharing Trends</h1>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <?php foreach ($metrics as $metric): ?>
            <div class="metric-card">
                <div class="metric-value">
                    <?= $metric['type'] === 'currency' ? '$' . number_format($metric['value'], 2) : $metric['value'] ?>
                </div>
                <div class="metric-label"><?= $metric['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Profit Trends Chart -->
        <div class="chart-container">
            <canvas id="profitTrendsChart"></canvas>
        </div>

        <!-- Distribution Metrics Chart -->
        <div class="chart-container">
            <canvas id="distributionMetricsChart"></canvas>
        </div>

        <!-- Investor Performance Chart -->
        <div class="chart-container">
            <canvas id="investorPerformanceChart"></canvas>
        </div>

        <!-- Comparative Analysis Chart -->
        <div class="chart-container">
            <canvas id="comparativeAnalysisChart"></canvas>
        </div>
    </div>

    <script>
        // Profit Trends Chart
        new Chart(document.getElementById('profitTrendsChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($profitTrends, 'period')) ?>,
                datasets: [{
                    label: 'Net Profit',
                    data: <?= json_encode(array_column($profitTrends, 'net_profit')) ?>,
                    borderColor: '#28a745',
                    fill: false
                }, {
                    label: 'Total Sales',
                    data: <?= json_encode(array_column($profitTrends, 'total_sales')) ?>,
                    borderColor: '#007bff',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Profit Trends'
                    }
                }
            }
        });

        // Distribution Metrics Chart
        new Chart(document.getElementById('distributionMetricsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($distributionMetrics, 'period')) ?>,
                datasets: [{
                    label: 'Total Distributed',
                    data: <?= json_encode(array_column($distributionMetrics, 'total_distributed')) ?>,
                    backgroundColor: '#17a2b8'
                }, {
                    label: 'Total Paid',
                    data: <?= json_encode(array_column($distributionMetrics, 'total_paid')) ?>,
                    backgroundColor: '#28a745'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribution Metrics by Period'
                    }
                }
            }
        });

        // Investor Performance Chart
        new Chart(document.getElementById('investorPerformanceChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($investorPerformance, 'investor_name')) ?>,
                datasets: [{
                    label: 'Total Amount',
                    data: <?= json_encode(array_column($investorPerformance, 'total_amount')) ?>,
                    backgroundColor: '#fd7e14'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Investor Performance'
                    }
                }
            }
        });

        // Comparative Analysis Chart
        new Chart(document.getElementById('comparativeAnalysisChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($comparativeAnalysis, 'month')) ?>,
                datasets: [{
                    label: 'Sales Growth (%)',
                    data: <?= json_encode(array_column($comparativeAnalysis, 'sales_growth')) ?>,
                    borderColor: '#6f42c1',
                    fill: false
                }, {
                    label: 'Profit Growth (%)',
                    data: <?= json_encode(array_column($comparativeAnalysis, 'profit_growth')) ?>,
                    borderColor: '#dc3545',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Growth Analysis'
                    }
                }
            }
        });
    </script>
</body>
</html>
