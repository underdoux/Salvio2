<div class="commission-report-page">
    <div class="page-header">
        <h2>Commission Report</h2>
        <div class="actions">
            <button type="button" 
                    class="btn btn-success"
                    onclick="exportReport()">
                <i class="fas fa-file-excel"></i> Export to CSV
            </button>
            <a href="<?= $baseUrl ?>/commissions" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Commissions
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/commissions/report" class="filters-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <select name="user_id" class="form-control">
                        <option value="">All Sales Persons</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" 
                                    <?= ($filters['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                            Pending
                        </option>
                        <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>
                            Approved
                        </option>
                        <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>
                            Paid
                        </option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>
                            Cancelled
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <div class="input-group">
                        <input type="date" 
                               name="date_from" 
                               class="form-control" 
                               value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                               placeholder="From Date">
                        <div class="input-group-prepend input-group-append">
                            <span class="input-group-text">to</span>
                        </div>
                        <input type="date" 
                               name="date_to" 
                               class="form-control" 
                               value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                               placeholder="To Date">
                    </div>
                </div>

                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/commissions/report" class="btn btn-outline-secondary">
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
                <div class="card-title">Total Commissions</div>
                <div class="card-value">
                    <?= CurrencyFormatter::getInstance()->format($summary['total_amount'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    <?= $summary['total_count'] ?? 0 ?> commissions
                </div>
            </div>

            <div class="summary-card">
                <div class="card-title">Paid Commissions</div>
                <div class="card-value">
                    <?= CurrencyFormatter::getInstance()->format($summary['paid_amount'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    <?= $summary['paid_count'] ?? 0 ?> commissions
                </div>
            </div>

            <div class="summary-card">
                <div class="card-title">Pending Commissions</div>
                <div class="card-value">
                    <?= CurrencyFormatter::getInstance()->format($summary['pending_amount'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    <?= $summary['pending_count'] ?? 0 ?> commissions
                </div>
            </div>

            <div class="summary-card">
                <div class="card-title">Average Commission</div>
                <div class="card-value">
                    <?= CurrencyFormatter::getInstance()->format($summary['average_amount'] ?? 0) ?>
                </div>
                <div class="card-subtitle">
                    per commission
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Data -->
    <div class="data-section">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Sales Person</th>
                        <th>Product</th>
                        <th>Original Price</th>
                        <th>Commission Rate</th>
                        <th>Commission Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commissions)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No commissions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commissions as $commission): ?>
                            <tr>
                                <td>
                                    <a href="<?= $baseUrl ?>/orders/view/<?= $commission['order_id'] ?>">
                                        <?= htmlspecialchars($commission['order_number']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($commission['sales_person']) ?></td>
                                <td>
                                    <?= htmlspecialchars($commission['product_name']) ?>
                                    <small class="text-muted d-block">
                                        Qty: <?= $commission['quantity'] ?>
                                    </small>
                                </td>
                                <td>
                                    <?= CurrencyFormatter::getInstance()->format($commission['original_price']) ?>
                                </td>
                                <td>
                                    <?= $commission['rate_percent'] ?>%
                                    <small class="text-muted d-block">
                                        <?= ucfirst($commission['rate_type']) ?>
                                    </small>
                                </td>
                                <td>
                                    <?= CurrencyFormatter::getInstance()->format($commission['commission_amount']) ?>
                                    <?php if ($commission['paid_amount'] > 0): ?>
                                        <small class="text-success d-block">
                                            Paid: <?= CurrencyFormatter::getInstance()->format($commission['paid_amount']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $this->getCommissionStatusBadgeClass($commission['status']) ?>">
                                        <?= ucfirst($commission['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d', strtotime($commission['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.commission-report-page {
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
