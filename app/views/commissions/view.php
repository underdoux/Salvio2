<div class="view-commission-page">
    <div class="page-header">
        <div class="title-section">
            <h2>
                Commission Details
                <span class="badge <?= $this->getCommissionStatusBadgeClass($commission['status']) ?>">
                    <?= ucfirst($commission['status']) ?>
                </span>
            </h2>
            <div class="commission-meta">
                Created on <?= date('Y-m-d H:i', strtotime($commission['created_at'])) ?>
            </div>
        </div>
        <div class="actions">
            <?php if ($commission['status'] === 'pending'): ?>
                <button type="button" 
                        class="btn btn-success"
                        onclick="updateStatus('approved')">
                    <i class="fas fa-check"></i> Approve Commission
                </button>
            <?php endif; ?>

            <?php if ($commission['status'] === 'approved'): ?>
                <button type="button" 
                        class="btn btn-primary"
                        onclick="showPaymentModal()">
                    <i class="fas fa-dollar-sign"></i> Process Payment
                </button>
            <?php endif; ?>

            <button type="button" 
                    class="btn btn-info"
                    onclick="showAdjustmentModal()">
                <i class="fas fa-balance-scale"></i> Add Adjustment
            </button>

            <a href="<?= $baseUrl ?>/commissions" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Commissions
            </a>
        </div>
    </div>

    <div class="commission-sections">
        <!-- Basic Information -->
        <div class="commission-section">
            <h3>Commission Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Sales Person</label>
                    <span><?= htmlspecialchars($commission['sales_person']) ?></span>
                </div>
                <div class="info-item">
                    <label>Order Number</label>
                    <span>
                        <a href="<?= $baseUrl ?>/orders/view/<?= $commission['order_id'] ?>">
                            <?= htmlspecialchars($commission['order_number']) ?>
                        </a>
                    </span>
                </div>
                <div class="info-item">
                    <label>Product</label>
                    <span>
                        <?= htmlspecialchars($commission['product_name']) ?>
                        <small class="text-muted d-block">
                            Quantity: <?= $commission['quantity'] ?>
                        </small>
                    </span>
                </div>
                <div class="info-item">
                    <label>Original Price</label>
                    <span><?= CurrencyFormatter::getInstance()->format($commission['original_price']) ?></span>
                </div>
                <div class="info-item">
                    <label>Commission Rate</label>
                    <span>
                        <?= $commission['rate_percent'] ?>%
                        <small class="text-muted d-block">
                            <?= ucfirst($commission['rate_type']) ?> Rate
                        </small>
                    </span>
                </div>
                <div class="info-item">
                    <label>Commission Amount</label>
                    <span>
                        <?= CurrencyFormatter::getInstance()->format($commission['commission_amount']) ?>
                        <?php if ($commission['paid_amount'] > 0): ?>
                            <small class="text-success d-block">
                                Paid: <?= CurrencyFormatter::getInstance()->format($commission['paid_amount']) ?>
                            </small>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Adjustments -->
        <div class="commission-section">
            <h3>Adjustments</h3>
            <?php if (empty($commission['adjustments'])): ?>
                <p class="text-muted">No adjustments have been made</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Added By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commission['adjustments'] as $adjustment): ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($adjustment['created_at'])) ?></td>
                                    <td>
                                        <span class="badge <?= $adjustment['adjustment_type'] === 'increase' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($adjustment['adjustment_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= CurrencyFormatter::getInstance()->format($adjustment['amount']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($adjustment['reason']) ?></td>
                                    <td><?= htmlspecialchars($adjustment['created_by_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment History -->
        <div class="commission-section">
            <h3>Payment History</h3>
            <?php if (empty($commission['payments'])): ?>
                <p class="text-muted">No payments have been recorded</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Processed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commission['payments'] as $payment): ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($payment['payment_date'])) ?></td>
                                    <td><?= CurrencyFormatter::getInstance()->format($payment['payment_amount']) ?></td>
                                    <td><?= ucfirst($payment['payment_method']) ?></td>
                                    <td><?= htmlspecialchars($payment['reference_number'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($payment['notes'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($payment['created_by_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                <h5 class="modal-title">Process Commission Payment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= $baseUrl ?>/commissions/processPayment" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?= $commission['user_id'] ?>">
                    <input type="hidden" name="commissions" value="<?= htmlspecialchars(json_encode([
                        ['id' => $commission['id'], 'amount' => $commission['commission_amount'] - $commission['paid_amount']]
                    ])) ?>">

                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   class="form-control" 
                                   required 
                                   step="0.01"
                                   max="<?= $commission['commission_amount'] - $commission['paid_amount'] ?>"
                                   value="<?= $commission['commission_amount'] - $commission['paid_amount'] ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_date">Payment Date</label>
                        <input type="date" 
                               id="payment_date" 
                               name="payment_date" 
                               class="form-control" 
                               required 
                               value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reference_number">Reference Number</label>
                        <input type="text" 
                               id="reference_number" 
                               name="reference_number" 
                               class="form-control">
                        <small class="form-text text-muted">
                            Required for bank transfer and check payments
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" 
                                name="notes" 
                                class="form-control" 
                                rows="2"></textarea>
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

<!-- Adjustment Modal -->
<div class="modal fade" id="adjustmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Commission Adjustment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= $baseUrl ?>/commissions/addAdjustment/<?= $commission['id'] ?>" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="adjustment_type">Adjustment Type</label>
                        <select id="adjustment_type" name="type" class="form-control" required>
                            <option value="increase">Increase</option>
                            <option value="decrease">Decrease</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="adjustment_amount">Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" 
                                   id="adjustment_amount" 
                                   name="amount" 
                                   class="form-control" 
                                   required 
                                   step="0.01"
                                   min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="adjustment_reason">Reason</label>
                        <textarea id="adjustment_reason" 
                                name="reason" 
                                class="form-control" 
                                rows="3"
                                required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.view-commission-page {
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

.commission-meta {
    color: #666;
    font-size: 0.9em;
}

.actions {
    display: flex;
    gap: 10px;
}

.commission-sections {
    display: grid;
    gap: 20px;
}

.commission-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.commission-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
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
        flex-wrap: wrap;
    }

    .actions .btn {
        flex: 1;
    }
}
</style>

<script>
function updateStatus(status) {
    if (!confirm('Are you sure you want to update this commission\'s status?')) {
        return;
    }

    fetch(`<?= $baseUrl ?>/commissions/updateStatus/<?= $commission['id'] ?>`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `status=${status}`
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

function showPaymentModal() {
    $('#paymentModal').modal('show');
}

function showAdjustmentModal() {
    $('#adjustmentModal').modal('show');
}

document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodSelect = document.getElementById('payment_method');
    const referenceNumberInput = document.getElementById('reference_number');

    paymentMethodSelect.addEventListener('change', function() {
        const isReferenceRequired = this.value !== 'cash';
        referenceNumberInput.required = isReferenceRequired;
    });

    document.getElementById('paymentModal').querySelector('form').addEventListener('submit', function(e) {
        const paymentMethod = paymentMethodSelect.value;
        const referenceNumber = referenceNumberInput.value.trim();

        if (paymentMethod !== 'cash' && !referenceNumber) {
            e.preventDefault();
            alert('Reference number is required for bank transfer and check payments');
            referenceNumberInput.focus();
        }
    });
});
</script>
