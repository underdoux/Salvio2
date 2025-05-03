<div class="view-profit-page">
    <div class="page-header">
        <div class="title-section">
            <h2>
                Profit Details - <?= date('F Y', mktime(0, 0, 0, $profit['month'], 1, $profit['year'])) ?>
                <span class="badge <?= $profit['status'] === 'finalized' ? 'badge-success' : 'badge-warning' ?>">
                    <?= ucfirst($profit['status']) ?>
                </span>
            </h2>
            <div class="profit-meta">
                Created on <?= date('Y-m-d H:i', strtotime($profit['created_at'])) ?>
            </div>
        </div>
        <div class="actions">
            <?php if ($profit['status'] === 'draft'): ?>
                <button type="button" 
                        class="btn btn-success"
                        onclick="finalizeProfit()">
                    <i class="fas fa-check"></i> Finalize Profit
                </button>
            <?php endif; ?>

            <a href="<?= $baseUrl ?>/profit-sharing" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Profit Sharing
            </a>
        </div>
    </div>

    <div class="profit-sections">
        <!-- Summary Section -->
        <div class="profit-section">
            <h3>Profit Summary</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <label>Total Sales</label>
                    <span class="amount">
                        <?= CurrencyFormatter::getInstance()->format($profit['total_sales']) ?>
                    </span>
                </div>
                <div class="summary-item">
                    <label>Total Costs</label>
                    <span class="amount text-danger">
                        <?= CurrencyFormatter::getInstance()->format($profit['total_costs']) ?>
                    </span>
                </div>
                <div class="summary-item">
                    <label>Total Expenses</label>
                    <span class="amount text-danger">
                        <?= CurrencyFormatter::getInstance()->format($profit['total_expenses']) ?>
                    </span>
                </div>
                <div class="summary-item">
                    <label>Total Commissions</label>
                    <span class="amount text-danger">
                        <?= CurrencyFormatter::getInstance()->format($profit['total_commissions']) ?>
                    </span>
                </div>
                <div class="summary-item total">
                    <label>Net Profit</label>
                    <span class="amount <?= $profit['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= CurrencyFormatter::getInstance()->format($profit['net_profit']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Distributions Section -->
        <div class="profit-section">
            <h3>Profit Distribution</h3>
            <?php if (empty($distributions)): ?>
                <?php if ($profit['status'] === 'draft'): ?>
                    <p class="text-muted">Distributions will be calculated when profit is finalized</p>
                <?php else: ?>
                    <p class="text-muted">No distributions found</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Investor</th>
                                <th>Percentage</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distributions as $dist): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dist['investor_name']) ?></td>
                                    <td><?= $dist['percentage'] ?>%</td>
                                    <td>
                                        <?= CurrencyFormatter::getInstance()->format($dist['distribution_amount']) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $dist['status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= ucfirst($dist['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $dist['payment_date'] ? date('Y-m-d', strtotime($dist['payment_date'])) : '-' ?>
                                    </td>
                                    <td>
                                        <?php if ($dist['status'] === 'pending'): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-primary"
                                                    onclick="showPaymentModal(<?= $dist['id'] ?>)">
                                                <i class="fas fa-dollar-sign"></i> Process Payment
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Calculation Logs -->
        <div class="profit-section">
            <h3>Calculation History</h3>
            <?php if (empty($logs)): ?>
                <p class="text-muted">No calculation logs found</p>
            <?php else: ?>
                <div class="logs-timeline">
                    <?php foreach ($logs as $log): ?>
                        <div class="log-entry">
                            <div class="log-time">
                                <?= date('Y-m-d H:i', strtotime($log['created_at'])) ?>
                            </div>
                            <div class="log-message">
                                <?= nl2br(htmlspecialchars($log['log_message'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Distribution Payment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= $baseUrl ?>/profit-sharing/processPayment" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="distribution_id" name="distribution_id">

                    <div class="form-group">
                        <label for="payment_date">Payment Date</label>
                        <input type="date" 
                               id="payment_date" 
                               name="payment_date" 
                               class="form-control" 
                               required 
                               value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.view-profit-page {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
}

.title-section h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.profit-meta {
    color: #666;
    font-size: 0.9em;
}

.profit-sections {
    display: grid;
    gap: 20px;
}

.profit-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.profit-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.summary-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.summary-item.total {
    grid-column: 1 / -1;
    background: #e9ecef;
}

.summary-item label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.summary-item .amount {
    font-size: 1.2em;
    font-weight: bold;
}

.logs-timeline {
    display: grid;
    gap: 15px;
}

.log-entry {
    display: grid;
    gap: 5px;
}

.log-time {
    font-size: 0.9em;
    color: #666;
}

.log-message {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    white-space: pre-line;
}

.badge {
    padding: 5px 10px;
    border-radius: 15px;
}

.table {
    margin-bottom: 0;
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
function finalizeProfit() {
    if (!confirm('Are you sure you want to finalize this profit calculation? This action cannot be undone.')) {
        return;
    }

    fetch(`<?= $baseUrl ?>/profit-sharing/finalize/<?= $profit['id'] ?>`, {
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

function showPaymentModal(distributionId) {
    document.getElementById('distribution_id').value = distributionId;
    $('#paymentModal').modal('show');
}
</script>
