<?php require_once __DIR__ . '/../layouts/main.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Profit Details - <?= date('F Y', strtotime($profit['period'])) ?></h2>
        <div>
            <?php if ($profit['status'] === 'draft'): ?>
                <form method="POST" action="/Salvio2/public/profit-sharing/finalize/<?= $profit['id'] ?>" 
                      class="d-inline" onsubmit="return confirm('Are you sure? This action cannot be undone.')">
                    <button type="submit" class="btn btn-success">
                        Finalize & Distribute
                    </button>
                </form>
            <?php else: ?>
                <a href="/Salvio2/public/profit-sharing/report/<?= $profit['id'] ?>" 
                   class="btn btn-secondary">
                    View Report
                </a>
            <?php endif; ?>
            <a href="/Salvio2/public/profit-sharing" class="btn btn-outline-primary">
                Back to List
            </a>
        </div>
    </div>

    <!-- Profit Summary -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue & Costs</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <td>Total Sales</td>
                            <td class="text-end">₱ <?= number_format($profit['total_sales'], 2) ?></td>
                        </tr>
                        <tr>
                            <td>Product Costs</td>
                            <td class="text-end text-danger">
                                - ₱ <?= number_format($profit['total_product_cost'], 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Operational Expenses</td>
                            <td class="text-end text-danger">
                                - ₱ <?= number_format($profit['total_expenses'], 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Sales Commissions</td>
                            <td class="text-end text-danger">
                                - ₱ <?= number_format($profit['total_commissions'], 2) ?>
                            </td>
                        </tr>
                        <tr class="table-active fw-bold">
                            <td>Net Profit</td>
                            <td class="text-end">₱ <?= number_format($profit['net_profit'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($profit['status'] === 'final' && $distribution): ?>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profit Distribution</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Investor</th>
                                <th class="text-end">Percentage</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distribution as $share): ?>
                                <tr>
                                    <td><?= $share['investor_name'] ?></td>
                                    <td class="text-end"><?= number_format($share['percentage'], 2) ?>%</td>
                                    <td class="text-end">₱ <?= number_format($share['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php elseif ($profit['status'] === 'draft'): ?>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Current Investor Shares</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Investor</th>
                                <th class="text-end">Percentage</th>
                                <th class="text-end">Estimated Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($investors as $investor): ?>
                                <tr>
                                    <td><?= $investor['name'] ?></td>
                                    <td class="text-end"><?= number_format($investor['percentage'], 2) ?>%</td>
                                    <td class="text-end">
                                        ₱ <?= number_format(($profit['net_profit'] * $investor['percentage']) / 100, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            These are estimated shares based on current investor percentages. 
                            Finalize the profit calculation to lock in the distribution.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
