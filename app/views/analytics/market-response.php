<div class="market-response-page">
    <div class="page-header">
        <h2>Market Response Analysis</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/analytics/export?type=market" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export Data
            </a>
            <a href="<?= $baseUrl ?>/analytics" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Analytics
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/analytics/market-response" class="filters-form">
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
                    <select name="customer_type" class="form-control">
                        <option value="">All Customer Types</option>
                        <option value="pharmacy" <?= ($filters['customer_type'] ?? '') === 'pharmacy' ? 'selected' : '' ?>>
                            Pharmacy
                        </option>
                        <option value="clinic" <?= ($filters['customer_type'] ?? '') === 'clinic' ? 'selected' : '' ?>>
                            Clinic
                        </option>
                        <option value="hospital" <?= ($filters['customer_type'] ?? '') === 'hospital' ? 'selected' : '' ?>>
                            Hospital
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/analytics/market-response" class="btn btn-outline-secondary">
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
            <!-- Response Score by Customer Type -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Response Score by Customer Type</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="responseScoreChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Orders by Customer Type -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Orders by Customer Type</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Sales Amount by Customer Type -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Amount by Customer Type</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quantity by Customer Type -->
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Quantity by Customer Type</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="quantityChart"></canvas>
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
                        <th>Customer Type</th>
                        <th>Response Score</th>
                        <th>Total Orders</th>
                        <th>Total Quantity</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($response)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No response data found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($response as $data): ?>
                            <tr>
                                <td><?= date('F Y', mktime(0, 0, 0, $data['month'], 1, $data['year'])) ?></td>
                                <td><?= htmlspecialchars($data['product_name']) ?></td>
                                <td><?= ucfirst($data['customer_type']) ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" 
                                             role="progressbar" 
                                             style="width: <?= $data['response_score'] ?>%"
                                             aria-valuenow="<?= $data['response_score'] ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <?= number_format($data['response_score'], 1) ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= number_format($data['total_orders']) ?></td>
                                <td><?= number_format($data['total_quantity']) ?></td>
                                <td><?= CurrencyFormatter::getInstance()->format($data['total_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.market-response-page {
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
    const responseData = <?= json_encode($response) ?>;
    const customerTypes = [...new Set(responseData.map(d => d.customer_type))];
    const periods = [...new Set(responseData.map(d => `${d.year}-${d.month}`))];

    // Response Score Chart
    new Chart(document.getElementById('responseScoreChart'), {
        type: 'bar',
        data: {
            labels: customerTypes,
            datasets: [{
                label: 'Average Response Score',
                data: customerTypes.map(type => {
                    const typeData = responseData.filter(d => d.customer_type === type);
                    return typeData.reduce((sum, d) => sum + d.response_score, 0) / typeData.length;
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

    // Orders Chart
    new Chart(document.getElementById('ordersChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: customerTypes.map(type => ({
                label: ucfirst(type),
                data: periods.map(period => {
                    const [year, month] = period.split('-');
                    const data = responseData.find(d => 
                        d.year == year && 
                        d.month == month && 
                        d.customer_type === type
                    );
                    return data ? data.total_orders : 0;
                }),
                borderColor: getCustomerTypeColor(type),
                tension: 0.1
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Sales Chart
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: customerTypes.map(type => ({
                label: ucfirst(type),
                data: periods.map(period => {
                    const [year, month] = period.split('-');
                    const data = responseData.find(d => 
                        d.year == year && 
                        d.month == month && 
                        d.customer_type === type
                    );
                    return data ? data.total_amount : 0;
                }),
                borderColor: getCustomerTypeColor(type),
                tension: 0.1
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Quantity Chart
    new Chart(document.getElementById('quantityChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: customerTypes.map(type => ({
                label: ucfirst(type),
                data: periods.map(period => {
                    const [year, month] = period.split('-');
                    const data = responseData.find(d => 
                        d.year == year && 
                        d.month == month && 
                        d.customer_type === type
                    );
                    return data ? data.total_quantity : 0;
                }),
                borderColor: getCustomerTypeColor(type),
                tension: 0.1
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});

function getCustomerTypeColor(type) {
    const colors = {
        'pharmacy': '#007bff',
        'clinic': '#28a745',
        'hospital': '#ffc107'
    };
    return colors[type] || '#6c757d';
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
