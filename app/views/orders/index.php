<div class="orders-page">
    <div class="page-header">
        <h2>Orders Management</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/orders/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Order
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/orders" class="filters-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <input type="text" 
                           name="search" 
                           placeholder="Search orders..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                           class="form-control">
                </div>

                <div class="form-group col-md-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="new" <?= ($filters['status'] ?? '') === 'new' ? 'selected' : '' ?>>
                            New
                        </option>
                        <option value="processing" <?= ($filters['status'] ?? '') === 'processing' ? 'selected' : '' ?>>
                            Processing
                        </option>
                        <option value="shipped" <?= ($filters['status'] ?? '') === 'shipped' ? 'selected' : '' ?>>
                            Shipped
                        </option>
                        <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>
                            Completed
                        </option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>
                            Cancelled
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <select name="payment_status" class="form-control">
                        <option value="">All Payment Status</option>
                        <option value="pending" <?= ($filters['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                            Pending
                        </option>
                        <option value="partial" <?= ($filters['payment_status'] ?? '') === 'partial' ? 'selected' : '' ?>>
                            Partial
                        </option>
                        <option value="paid" <?= ($filters['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>
                            Paid
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-2">
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
                        <option value="other" <?= ($filters['customer_type'] ?? '') === 'other' ? 'selected' : '' ?>>
                            Other
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-3">
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
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/orders" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Orders Table -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Total Amount</th>
                    <th>Items</th>
                    <th>Order Status</th>
                    <th>Payment Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No orders found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="<?= $baseUrl ?>/orders/view/<?= $order['id'] ?>">
                                    <?= htmlspecialchars($order['order_number']) ?>
                                </a>
                            </td>
                            <td>
                                <?= htmlspecialchars($order['customer_name']) ?>
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <small class="text-muted d-block">
                                        <?= htmlspecialchars($order['customer_phone']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?= ucfirst($order['customer_type']) ?>
                                </span>
                            </td>
                            <td>
                                <?= CurrencyFormatter::getInstance()->format($order['final_amount']) ?>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <small class="text-success d-block">
                                        -<?= CurrencyFormatter::getInstance()->format($order['discount_amount']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?= $order['total_items'] ?> items
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $this->getStatusBadgeClass($order['order_status']) ?>">
                                    <?= ucfirst($order['order_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $this->getPaymentStatusBadgeClass($order['payment_status']) ?>">
                                    <?= ucfirst($order['payment_status']) ?>
                                </span>
                                <?php if ($order['payment_status'] === 'partial'): ?>
                                    <small class="text-muted d-block">
                                        <?= CurrencyFormatter::getInstance()->format($order['paid_amount']) ?>
                                        paid
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                                <small class="text-muted d-block">
                                    by <?= htmlspecialchars($order['created_by_name']) ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= $baseUrl ?>/orders/view/<?= $order['id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($order['order_status'] === 'new'): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-success"
                                                title="Process Order"
                                                onclick="updateOrderStatus(<?= $order['id'] ?>, 'processing')">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($order['payment_status'] !== 'paid'): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                title="Add Payment"
                                                onclick="showPaymentModal(<?= $order['id'] ?>)">
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
    <?php if (!empty($orders)): ?>
        <div class="pagination-section">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php
                    $totalPages = ceil(count($orders) / 10);
                    for ($i = 1; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?= $currentPage == $i ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>/orders?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['status']) ? '&status=' . $filters['status'] : '' ?><?= !empty($filters['payment_status']) ? '&payment_status=' . $filters['payment_status'] : '' ?><?= !empty($filters['customer_type']) ? '&customer_type=' . $filters['customer_type'] : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . $filters['date_from'] : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . $filters['date_to'] : '' ?>">
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
                <h5 class="modal-title">Add Payment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="paymentForm" method="POST">
                <div class="modal-body">
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
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.orders-page {
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

.badge-secondary {
    background-color: #6c757d;
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
function updateOrderStatus(orderId, status) {
    if (!confirm('Are you sure you want to update this order\'s status?')) {
        return;
    }

    fetch(`<?= $baseUrl ?>/orders/updateStatus/${orderId}`, {
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

function showPaymentModal(orderId) {
    const form = document.getElementById('paymentForm');
    form.action = `<?= $baseUrl ?>/orders/addPayment/${orderId}`;
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
