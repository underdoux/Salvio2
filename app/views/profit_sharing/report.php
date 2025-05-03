<div class="profit-report-page">
    <div class="page-header">
        <h2>Profit Sharing Report</h2>
        <div class="actions">
            <button type="button" 
                    class="btn btn-success"
                    onclick="exportReport()">
                <i class="fas fa-file-excel"></i> Export to CSV
            </button>
            <a href="<?= $baseUrl ?>/profit-sharing" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Profit Sharing
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/profit-sharing/report" class="filters-form">
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
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>
                            Draft
                        </option>
                        <option value="finalized" <?= ($filters['status'] ?? '') === 'finalized' ? 'selected' : '' ?>>
                            Finalized
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/profit-sharing/report" class="btn btn-outline-secondary">
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
            <div class="summary-card">
                <div class="card-title">Total Sales</div>
                <div class="card-value">
                    <?= CurrencyFormatter::getInstance()->format($summary['total_sales'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    for selected period
                </div>
            </div>

            <div class="summary-card">
                <div class="card-title">Total Costs</div>
                <div class="card-value text-danger">
                    <?= CurrencyFormatter::getInstance()->format($summary['total_costs'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    including expenses
                </div>
            </div>

            <div class="summary-card">
                <div class="card-title">Net Profit</div>
                <div class="card-value <?= ($summary['net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= CurrencyFormatter::getInstance()->format($summary['net_profit'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    total net profit
                </div>
            </div>

            <div class="summary-card">
                <div class="card-title">Distributed Amount</div>
                <div class="card-value">
                    <?= CurrencyFormatter::getInstance()->format($summary['distributed_amount'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    <?= $summary['distribution_count'] ?? 0 ?> distributions
                </div>
            </div>
        </div>
    </div>

    <!-- Profit Data -->
    <div class="data-section">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Total Sales</th>
                        <th>Total Costs</th>
                        <th>Total Expenses</th>
                        <th>Total Commissions</th>
                        <th>Net Profit</th>
                        <th>Status</th>
                        <th>Distributions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($profits)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No profit data found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($profits as $profit): ?>
                            <tr>
                                <td>
                                    <a href="<?= $baseUrl ?>/profit-sharing/view/<?= $profit['id'] ?>">
                                        <?= date('F Y', mktime(0, 0, 0, $profit['month'], 1, $profit['year'])) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= CurrencyFormatter::getInstance()->format($profit['total_sales']) ?>
                                </td>
                                <td>
                                    <?= CurrencyFormatter::getInstance()->format($profit['total_costs']) ?>
                                </td>
                                <td>
                                    <?= CurrencyFormatter::getInstance()->format($profit['total_expenses']) ?>
                                </td>
                                <td>
                                    <?= CurrencyFormatter::getInstance()->format($profit['total_commissions']) ?>
                                </td>
                                <td>
                                    <?= CurrencyFormatter::getInstance()->format($profit['net_profit']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $profit['status'] === 'finalized' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= ucfirst($profit['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($profit['distribution_count'] > 0): ?>
                                        <span class="text-success">
                                            <?= $profit['paid_count'] ?>/<?= $profit['distribution_count'] ?> Paid
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
.profit-report-page {
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
    text-align: center;
}

.card-title {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.card-value {
    font-size: 1.5em;
    font-weight: bold;
    margin-bottom: 5px;
}

.card-subtitle {
    color: #666;
    font-size: 0.8em;
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
function exportReport() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('format', 'csv');
    window.location.href = currentUrl.toString();
}
</script>
