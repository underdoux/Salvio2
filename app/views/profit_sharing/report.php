<?php require_once __DIR__ . '/../layouts/main.php'; ?>

<div class="container mt-4">
    <div class="card">
        <div class="card-body">
            <!-- Report Header -->
            <div class="text-center mb-4">
                <h2 class="mb-1">Profit Distribution Report</h2>
                <h4 class="text-muted"><?= date('F Y', strtotime($profit['period'])) ?></h4>
            </div>

            <!-- Financial Summary -->
            <div class="row mb-4">
                <div class="col-md-8 offset-md-2">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr class="table-light">
                                <th colspan="2" class="text-center">Financial Summary</th>
                            </tr>
                            <tr>
                                <td width="60%">Total Sales Revenue</td>
                                <td class="text-end">₱ <?= number_format($profit['total_sales'], 2) ?></td>
                            </tr>
                            <tr>
                                <td>Less: Product Costs</td>
                                <td class="text-end text-danger">
                                    (₱ <?= number_format($profit['total_product_cost'], 2) ?>)
                                </td>
                            </tr>
                            <tr>
                                <td>Less: Operational Expenses</td>
                                <td class="text-end text-danger">
                                    (₱ <?= number_format($profit['total_expenses'], 2) ?>)
                                </td>
                            </tr>
                            <tr>
                                <td>Less: Sales Commissions</td>
                                <td class="text-end text-danger">
                                    (₱ <?= number_format($profit['total_commissions'], 2) ?>)
                                </td>
                            </tr>
                            <tr class="table-success fw-bold">
                                <td>Net Profit for Distribution</td>
                                <td class="text-end">₱ <?= number_format($profit['net_profit'], 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Profit Distribution -->
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr class="table-light">
                                <th colspan="3" class="text-center">Profit Distribution</th>
                            </tr>
                            <tr>
                                <th>Investor</th>
                                <th class="text-end">Share Percentage</th>
                                <th class="text-end">Amount</th>
                            </tr>
                            <?php foreach ($distribution as $share): ?>
                                <tr>
                                    <td><?= $share['investor_name'] ?></td>
                                    <td class="text-end"><?= number_format($share['percentage'], 2) ?>%</td>
                                    <td class="text-end">₱ <?= number_format($share['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Report Footer -->
            <div class="row mt-5">
                <div class="col-md-8 offset-md-2">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-5">Prepared by:</p>
                            <div class="border-top border-dark" style="width: 200px;">
                                <p class="mt-1 mb-0"><?= $_SESSION['user']['username'] ?></p>
                                <small class="text-muted">Administrator</small>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-0 mt-5">
                                <small class="text-muted">
                                    Generated on: <?= date('F j, Y g:i A') ?>
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Print Button -->
            <div class="row mt-4">
                <div class="col-md-8 offset-md-2 d-flex justify-content-between">
                    <a href="/Salvio2/public/profit-sharing" class="btn btn-outline-primary">
                        Back to List
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .btn, .navbar, footer {
        display: none !important;
    }
    .card {
        border: none !important;
    }
    .container {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
</style>
