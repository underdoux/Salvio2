<div class="commissions-page">
    <div class="page-header">
        <h2>Commission Management</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/commissions/rates" class="btn btn-primary">
                <i class="fas fa-percentage"></i> Manage Rates
            </a>
            <a href="<?= $baseUrl ?>/commissions/report" class="btn btn-secondary">
                <i class="fas fa-file-alt"></i> View Report
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/commissions" class="filters-form">
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
                    <a href="<?= $baseUrl ?>/commissions" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Commissions Table -->
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($commissions)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No commissions found</td>
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
                            <td>
                                <?= date('Y-m-d', strtotime($commission['created_at'])) ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= $baseUrl ?>/commissions/view/<?= $commission['id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($commission['status'] === 'pending'): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-success"
                                                title="Approve Commission"
                                                onclick="updateCommissionStatus(<?= $commission['id'] ?>, 'approved')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($commission['status'] === 'approved'): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                title="Process Payment"
                                                onclick="showPaymentModal(<?= $commission['id'] ?>, <?= $commission['commission_amount'] - $commission['paid_amount'] ?>)">
                                            <i class="fas fa-dollar-sign"></i>
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

    <!-- Pagination -->
    <?php if (!empty($commissions)): ?>
        <div class="pagination-section">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php
                    $totalPages = ceil(count($commissions) / 10);
                    for ($i = 1; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?= $currentPage == $i ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>/commissions?page=<?= $i ?><?= !empty($filters['user_id']) ? '&user_id=' . $filters['user_id'] : '' ?><?= !empty($filters['status']) ? '&status=' . $filters['status'] : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . $filters['date_from'] : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . $filters['date_to'] : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
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
            <form id="paymentForm" method="POST" action="<?= $baseUrl ?>/commissions/processPayment">
                <div class="modal-body">
                    <input type="hidden" id="commissionIds" name="commissions">
                    <input type="hidden" id="userId" name="user_id">

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
                                   step="0.01">
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

<style>
.commissions-page {
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

.filters-form .form-row {
    margin-bottom: 10px;
}

.filters-form .form-row:last-child {
    margin-bottom: 0;
}

.table {
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.badge {
    padding: 5px 10px;
    border-radius: 15px;
}

.badge-info {
    background-color: #17a2b8;
}

.badge-success {
    background-color: #28a745;
}

.badge-warning {
    background-color: #ffc107;
    color: #000;
}

.badge-danger {
    background-color: #dc3545;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.pagination-section {
    margin-top: 20px;
}

@media (max-width: 768px) {
    .filters-form .form-row {
        margin-right: 0;
        margin-left: 0;
    }

    .filters-form .form-group {
        padding-right: 0;
        padding-left: 0;
    }

    .table-responsive {
        margin: 0 -20px;
    }
}
</style>

<script>
function updateCommissionStatus(id, status) {
    if (!confirm('Are you sure you want to update this commission\'s status?')) {
        return;
    }

    fetch(`<?= $baseUrl ?>/commissions/updateStatus/${id}`, {
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

function showPaymentModal(commissionId, amount) {
    document.getElementById('commissionIds').value = JSON.stringify([{
        id: commissionId,
        amount: amount
    }]);
    document.getElementById('amount').value = amount;
    document.getElementById('amount').max = amount;
    $('#paymentModal').modal('show');
}

document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodSelect = document.getElementById('payment_method');
    const referenceNumberInput = document.getElementById('reference_number');

    paymentMethodSelect.addEventListener('change', function() {
        const isReferenceRequired = this.value !== 'cash';
        referenceNumberInput.required = isReferenceRequired;
    });

    document.getElementById('paymentForm').addEventListener('submit', function(e) {
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
