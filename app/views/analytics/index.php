<div class="container mt-4">
    <h1><?php echo $title; ?></h1>
    <p class="lead"><?php echo $description; ?></p>

    <!-- Chart Controls -->
    <div class="row mb-4">
        <!-- Comparison Controls -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Comparative Analysis</h5>
                    <!-- Analysis Controls -->
                    <div class="row">
                        <div class="col-md-3">
                            <label for="analysisType" class="form-label">Analysis Type</label>
                            <select class="form-select" id="analysisType">
                                <option value="trend">Trend Analysis</option>
                                <option value="variance">Variance Analysis</option>
                                <option value="correlation">Correlation Analysis</option>
                                <option value="forecast">Forecast Analysis</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="comparisonType" class="form-label">Comparison Type</label>
                            <select class="form-select" id="comparisonType">
                                <option value="period">Time Period</option>
                                <option value="category">Category</option>
                                <option value="product">Product</option>
                                <option value="benchmark">Benchmark</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="aggregationType" class="form-label">Aggregation</label>
                            <select class="form-select" id="aggregationType">
                                <option value="sum">Sum</option>
                                <option value="average">Average</option>
                                <option value="median">Median</option>
                                <option value="growth">Growth Rate</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="metricType" class="form-label">Metric</label>
                            <select class="form-select" id="metricType">
                                <option value="revenue">Revenue</option>
                                <option value="units">Units Sold</option>
                                <option value="profit">Profit</option>
                                <option value="growth">Growth Rate</option>
                            </select>
                        </div>
                    </div>

                    <!-- Period Controls -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="primaryPeriod" class="form-label">Primary Period</label>
                            <select class="form-select" id="primaryPeriod">
                                <option value="current">Current Period</option>
                                <option value="previous">Previous Period</option>
                                <option value="lastYear">Last Year</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="comparisonPeriod" class="form-label">Comparison Period</label>
                            <select class="form-select" id="comparisonPeriod">
                                <option value="previousPeriod">Previous Period</option>
                                <option value="lastYear">Last Year</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button class="btn btn-primary" onclick="updateComparison()">Update Comparison</button>
                            <button class="btn btn-outline-secondary" onclick="resetComparison()">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Visualization Options</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="colorScheme" class="form-label">Color Scheme</label>
                            <select class="form-select" id="colorScheme">
                                <option value="default">Default</option>
                                <option value="warm">Warm</option>
                                <option value="cool">Cool</option>
                                <option value="monochrome">Monochrome</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="dateRange" class="form-label">Date Range</label>
                            <select class="form-select" id="dateRange">
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="180" selected>Last 6 months</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Export Options</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="exportCharts('png')">Export as PNG</button>
                        <button type="button" class="btn btn-outline-primary" onclick="exportCharts('jpg')">Export as JPG</button>
                        <button type="button" class="btn btn-outline-primary" onclick="exportCharts('pdf')">Export as PDF</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Predictive Analytics Controls -->
    <?php include 'predictive-controls.php'; ?>

    <!-- Charts and other content -->
</div>

<!-- Include Required Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="/assets/js/predictive-analytics.js"></script>

<script>
// Initialize prediction chart
let forecastChart = null;

// Update prediction when controls change
function updatePrediction() {
    const type = document.getElementById('predictionType').value;
    const period = parseInt(document.getElementById('forecastPeriod').value);
    const confidenceLevel = parseFloat(document.getElementById('confidenceLevel').value);
    const seasonality = parseInt(document.getElementById('seasonalityPeriod').value);

    // Get historical data from the sales trends chart
    const historicalData = charts.salesTrends.data.datasets[0].data;
    
    // Generate forecast
    const forecast = generateForecast(historicalData, type, period);
    
    // Update forecast chart
    updateForecastChart(forecast, historicalData);
    
    // Update metrics
    updateForecastMetrics(forecast);
}

function updateForecastChart(forecast, historicalData) {
    const labels = [...charts.salesTrends.data.labels];
    for (let i = 1; i <= forecast.predictions.length; i++) {
        labels.push(`Forecast ${i}`);
    }

    const datasets = [
        {
            label: 'Historical',
            data: [...historicalData, ...Array(forecast.predictions.length).fill(null)],
            borderColor: colorSchemes.default.borderColor[0],
            backgroundColor: colorSchemes.default.backgroundColor[0],
            type: 'line'
        },
        {
            label: 'Forecast',
            data: [...Array(historicalData.length).fill(null), ...forecast.predictions],
            borderColor: colorSchemes.default.borderColor[1],
            backgroundColor: colorSchemes.default.backgroundColor[1],
            borderDash: [5, 5],
            type: 'line'
        },
        {
            label: 'Upper Bound',
            data: [...Array(historicalData.length).fill(null), ...forecast.confidenceIntervals.upper],
            borderColor: 'rgba(200, 200, 200, 0.3)',
            backgroundColor: 'rgba(200, 200, 200, 0.1)',
            borderDash: [2, 2],
            fill: '+1',
            type: 'line'
        },
        {
            label: 'Lower Bound',
            data: [...Array(historicalData.length).fill(null), ...forecast.confidenceIntervals.lower],
            borderColor: 'rgba(200, 200, 200, 0.3)',
            backgroundColor: 'rgba(200, 200, 200, 0.1)',
            borderDash: [2, 2],
            fill: false,
            type: 'line'
        }
    ];

    if (forecastChart) {
        forecastChart.destroy();
    }

    forecastChart = new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y;
                            if (value === null) return '';
                            return `${context.dataset.label}: ${value.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function updateForecastMetrics(forecast) {
    // Update model metrics
    document.getElementById('modelMetrics').innerHTML = generateMetricsHTML(forecast.metrics);

    // Update forecast summary
    document.getElementById('forecastSummary').innerHTML = `
        <p>Next Period: ${forecast.predictions[0].toFixed(2)}</p>
        <p>Average Forecast: ${(forecast.predictions.reduce((a,b) => a + b, 0) / forecast.predictions.length).toFixed(2)}</p>
        <p>Trend: ${getTrendDescription(forecast)}</p>
    `;

    // Update confidence intervals
    document.getElementById('confidenceIntervals').innerHTML = `
        <p>Upper Bound: ${forecast.confidenceIntervals.upper[0].toFixed(2)}</p>
        <p>Lower Bound: ${forecast.confidenceIntervals.lower[0].toFixed(2)}</p>
        <p>Range: ${(forecast.confidenceIntervals.upper[0] - forecast.confidenceIntervals.lower[0]).toFixed(2)}</p>
    `;
}

// Initialize prediction on page load
document.addEventListener('DOMContentLoaded', function() {
    // Wait for charts to be initialized
    setTimeout(updatePrediction, 1000);
});
</script>
