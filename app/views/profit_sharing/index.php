<?php require_once __DIR__ . '/../layouts/main.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Monthly Profit Sharing</h2>
        
        <!-- Calculate New Period Button -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#calculateModal">
            Calculate New Period
        </button>
    </div>

    <!-- Year Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="year" class="col-form-label">Filter by Year:</label>
                </div>
                <div class="col-auto">
                    <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Profits Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Total Sales</th>
                            <th>Product Cost</th>
                            <th>Expenses</th>
                            <th>Commissions</th>
                            <th>Net Profit</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($profits)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No profit records found for <?= $selectedYear ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($profits as $profit): ?>
                                <tr>
                                    <td><?= date('F Y', strtotime($profit['period'])) ?></td>
                                    <td><?= number_format($profit['total_sales'], 2) ?></td>
                                    <td><?= number_format($profit['total_product_cost'], 2) ?></td>
                                    <td><?= number_format($profit['total_expenses'], 2) ?></td>
                                    <td><?= number_format($profit['total_commissions'], 2) ?></td>
                                    <td class="fw-bold"><?= number_format($profit['net_profit'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $profit['status'] === 'final' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($profit['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/Salvio2/public/profit-sharing/view/<?= $profit['id'] ?>" 
                                           class="btn btn-sm btn-info">
                                            View
                                        </a>
                                        <?php if ($profit['status'] === 'final'): ?>
                                            <a href="/Salvio2/public/profit-sharing/report/<?= $profit['id'] ?>" 
                                               class="btn btn-sm btn-secondary">
                                                Report
                                            </a>
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
</div>

<!-- Calculate Modal -->
<div class="modal fade" id="calculateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/Salvio2/public/profit-sharing/calculate">
                <div class="modal-header">
                    <h5 class="modal-title">Calculate New Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="period" class="form-label">Select Period</label>
                        <input type="month" class="form-control" id="period" name="period" 
                               value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            This will calculate the profit for the selected month based on:
                            <ul class="mb-0">
                                <li>Completed and paid orders</li>
                                <li>Product costs</li>
                                <li>Operational expenses</li>
                                <li>Sales commissions</li>
                            </ul>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Calculate</button>
                </div>
            </form>
        </div>
    </div>
</div>
