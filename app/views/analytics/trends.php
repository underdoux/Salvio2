<div class="trends-page">
    <div class="page-header">
        <h2>Sales Trends Analysis</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/analytics/export?type=sales" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export Data
            </a>
            <a href="<?= $baseUrl ?>/analytics" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Analytics
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/analytics/trends" class="filters-form">
            <div class="form-row">
                <div class="form-group col-md-2">
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

                <div class="form-group col-md-2">
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
                    <select name="category_id" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"
                                    <?= ($filters['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/analytics/trends" class="btn btn-outline-secondary">
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
            <!-- Sales Amount Trend -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Amount Trend</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="salesAmountChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sales Quantity Trend -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Quantity Trend</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="salesQuantityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Average Price Trend -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Average Price Trend</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="averagePriceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Growth Rate Trend -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Growth Rate Trend</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="growthRateChart"></canvas>
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
                        <th>Product</th>
                        <th>Category</th>
                        <th>Total Sales</th>
                        <th>Total Quantity</th>
                        <th>Average Price</th>
                        <th>Growth Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trends)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No trend data found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trends as $trend): ?>
                            <tr>
                                <td><?= date('F Y', mktime(0, 0, 0, $trend['month'], 1, $trend['year'])) ?></td>
                                <td><?= htmlspecialchars($trend['product_name']) ?></td>
                                <td><?= htmlspecialchars($trend['category_name']) ?></td>
                                <td><?= CurrencyFormatter::getInstance()->format($trend['total_amount']) ?></td>
                                <td><?= number_format($trend['total_quantity']) ?></td>
                                <td><?= CurrencyFormatter::getInstance()->format($trend['average_price']) ?></td>
                                <td>
                                    <?php if ($trend['growth_rate'] !== null): ?>
                                        <span class="<?= $trend['growth_rate'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($trend['growth_rate'], 1) ?>%
                                        </span>
                                    <?php else: ?>
                                        -
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
.trends-page {
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
    const trendsData = <?= json_encode($trends) ?>;
    const labels = trendsData.map(d => `${d.year}-${d.month}`);

    // Sales Amount Chart
    new Chart(document.getElementById('salesAmountChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales Amount',
                data: trendsData.map(d => d.total_amount),
                borderColor: '#007bff',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Sales Quantity Chart
    new Chart(document.getElementById('salesQuantityChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Quantity Sold',
                data: trendsData.map(d => d.total_quantity),
                borderColor: '#28a745',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Average Price Chart
    new Chart(document.getElementById('averagePriceChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average Price',
                data: trendsData.map(d => d.average_price),
                borderColor: '#17a2b8',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Growth Rate Chart
    new Chart(document.getElementById('growthRateChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Growth Rate (%)',
                data: trendsData.map(d => d.growth_rate),
                backgroundColor: trendsData.map(d => d.growth_rate >= 0 ? '#28a745' : '#dc3545')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
