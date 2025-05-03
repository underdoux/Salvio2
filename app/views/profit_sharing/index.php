<div class="profit-sharing-page">
    <div class="page-header">
        <h2>Profit Sharing</h2>
        <div class="actions">
            <button type="button" 
                    class="btn btn-primary"
                    onclick="showCalculateModal()">
                <i class="fas fa-calculator"></i> Calculate Monthly Profit
            </button>
            <a href="<?= $baseUrl ?>/profit-sharing/report" class="btn btn-secondary">
                <i class="fas fa-file-alt"></i> View Report
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/profit-sharing" class="filters-form">
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
                    <a href="<?= $baseUrl ?>/profit-sharing" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($profits)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No profit data found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($profits as $profit): ?>
                            <tr>
                                <td>
                                    <?= date('F Y', mktime(0, 0, 0, $profit['month'], 1, $profit['year'])) ?>
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
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= $baseUrl ?>/profit-sharing/view/<?= $profit['id'] ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($profit['status'] === 'draft'): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-success"
                                                    title="Finalize Profit"
                                                    onclick="finalizeProfit(<?= $profit['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if (!empty($profits)): ?>
        <div class="pagination-section">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php
                    $totalPages = ceil(count($profits) / 10);
                    for ($i = 1; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?= $currentPage == $i ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>/profit-sharing?page=<?= $i ?><?= !empty($filters['year']) ? '&year=' . $filters['year'] : '' ?><?= !empty($filters['month']) ? '&month=' . $filters['month'] : '' ?><?= !empty($filters['status']) ? '&status=' . $filters['status'] : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Calculate Modal -->
<div class="modal fade" id="calculateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Calculate Monthly Profit</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= $baseUrl ?>/profit-sharing/calculate" method="POST">
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
.profit-sharing-page {
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

.btn-group {
    display: flex;
    gap: 5px;
}

.pagination-section {
    margin-top: 20px;
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
function showCalculateModal() {
    $('#calculateModal').modal('show');
}

function finalizeProfit(id) {
    if (!confirm('Are you sure you want to finalize this profit calculation? This action cannot be undone.')) {
        return;
    }

    fetch(`<?= $baseUrl ?>/profit-sharing/finalize/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
