<div class="predictions-page">
    <div class="page-header">
        <h2>Predictive Analytics</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/analytics/export?type=predictions" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export Data
            </a>
            <a href="<?= $baseUrl ?>/analytics" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Analytics
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/analytics/predictions" class="filters-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <select name="product_id" class="form-control">
                        <option value="">All Products</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"
                                    <?= ($filters['product_id'] ?? '') == $product['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($product['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <input type="date" 
                           name="date_from" 
                           class="form-control"
                           value="<?= $filters['date_from'] ?? date('Y-m-d') ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group col-md-3">
                    <input type="date" 
                           name="date_to" 
                           class="form-control"
                           value="<?= $filters['date_to'] ?? date('Y-m-d', strtotime('+3 months')) ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/analytics/predictions" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="row">
            <!-- Sales Prediction Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Predictions</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="predictionsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Confidence Score Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Prediction Confidence</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="confidenceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Product Comparison Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Product Comparison</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Factors Analysis Chart -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Factors Analysis</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="factorsChart"></canvas>
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
                        <th>Product</th>
                        <th>Prediction Date</th>
                        <th>Predicted Sales</th>
                        <th>Confidence Score</th>
                        <th>Factors Considered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($predictions)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No prediction data found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($predictions as $prediction): ?>
                            <tr>
                                <td><?= htmlspecialchars($prediction['product_name']) ?></td>
                                <td><?= date('F j, Y', strtotime($prediction['prediction_date'])) ?></td>
                                <td><?= CurrencyFormatter::getInstance()->format($prediction['predicted_sales']) ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= getConfidenceClass($prediction['confidence_score']) ?>" 
                                             role="progressbar" 
                                             style="width: <?= $prediction['confidence_score'] ?>%"
                                             aria-valuenow="<?= $prediction['confidence_score'] ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <?= number_format($prediction['confidence_score'], 1) ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $factors = json_decode($prediction['factors_considered'], true);
                                    if ($factors): 
                                    ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($factors as $factor): ?>
                                                <li><i class="fas fa-check-circle text-success"></i> <?= ucwords(str_replace('_', ' ', $factor)) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.predictions-page {
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

.progress {
    height: 20px;
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
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const predictionsData = <?= json_encode($predictions) ?>;
    const products = [...new Set(predictionsData.map(d => d.product_name))];
    const dates = [...new Set(predictionsData.map(d => d.prediction_date))];

    // Sales Predictions Chart
    new Chart(document.getElementById('predictionsChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: products.map(product => ({
                label: product,
                data: dates.map(date => {
                    const data = predictionsData.find(d => 
                        d.prediction_date === date && 
                        d.product_name === product
                    );
                    return data ? data.predicted_sales : 0;
                }),
                borderColor: getRandomColor(),
                tension: 0.1
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Confidence Score Chart
    new Chart(document.getElementById('confidenceChart'), {
        type: 'bar',
        data: {
            labels: products,
            datasets: [{
                label: 'Average Confidence Score',
                data: products.map(product => {
                    const productData = predictionsData.filter(d => d.product_name === product);
                    return productData.reduce((sum, d) => sum + d.confidence_score, 0) / productData.length;
                }),
                backgroundColor: '#17a2b8'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Product Comparison Chart
    new Chart(document.getElementById('comparisonChart'), {
        type: 'radar',
        data: {
            labels: dates,
            datasets: products.map(product => ({
                label: product,
                data: dates.map(date => {
                    const data = predictionsData.find(d => 
                        d.prediction_date === date && 
                        d.product_name === product
                    );
                    return data ? data.predicted_sales : 0;
                }),
                borderColor: getRandomColor(),
                backgroundColor: 'rgba(0, 123, 255, 0.2)'
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Factors Analysis Chart
    const factorsData = analyzeFactors(predictionsData);
    new Chart(document.getElementById('factorsChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(factorsData),
            datasets: [{
                data: Object.values(factorsData),
                backgroundColor: Object.keys(factorsData).map(() => getRandomColor())
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});

function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

function analyzeFactors(data) {
    const factors = {};
    data.forEach(prediction => {
        const predictionFactors = JSON.parse(prediction.factors_considered);
        predictionFactors.forEach(factor => {
            factors[factor] = (factors[factor] || 0) + 1;
        });
    });
    return factors;
}

function getConfidenceClass(score) {
    if (score >= 80) return 'bg-success';
    if (score >= 60) return 'bg-info';
    if (score >= 40) return 'bg-warning';
    return 'bg-danger';
}
</script>
