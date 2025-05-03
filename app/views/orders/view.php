<div class="view-order-page">
    <div class="page-header">
        <div class="title-section">
            <h2>
                Order #<?= htmlspecialchars($order['order_number']) ?>
                <span class="badge <?= $this->getStatusBadgeClass($order['order_status']) ?>">
                    <?= ucfirst($order['order_status']) ?>
                </span>
            </h2>
            <div class="order-meta">
                Created on <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                by <?= htmlspecialchars($order['created_by_name']) ?>
            </div>
        </div>
        <div class="actions">
            <?php if ($order['order_status'] === 'new'): ?>
                <button type="button" 
                        class="btn btn-success"
                        onclick="updateOrderStatus('processing')">
                    <i class="fas fa-play"></i> Process Order
                </button>
            <?php endif; ?>

            <?php if ($order['order_status'] === 'processing'): ?>
                <button type="button" 
                        class="btn btn-primary"
                        onclick="updateOrderStatus('shipped')">
                    <i class="fas fa-truck"></i> Mark as Shipped
                </button>
            <?php endif; ?>

            <?php if ($order['order_status'] === 'shipped'): ?>
                <button type="button" 
                        class="btn btn-success"
                        onclick="updateOrderStatus('completed')">
                    <i class="fas fa-check"></i> Complete Order
                </button>
            <?php endif; ?>

            <?php if ($order['payment_status'] !== 'paid'): ?>
                <button type="button" 
                        class="btn btn-primary"
                        onclick="showPaymentModal()">
                    <i class="fas fa-dollar-sign"></i> Add Payment
                </button>
            <?php endif; ?>

            <a href="<?= $baseUrl ?>/orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>

    <div class="order-sections">
        <!-- Customer Information -->
        <div class="order-section">
            <h3>Customer Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Name</label>
                    <span><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
                <div class="info-item">
                    <label>Type</label>
                    <span class="badge badge-info">
                        <?= ucfirst($order['customer_type']) ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <span><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item full-width">
                    <label>Address</label>
                    <span><?= nl2br(htmlspecialchars($order['customer_address'] ?? 'N/A')) ?></span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-section">
            <h3>Order Items</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= htmlspecialchars($item['sku']) ?></td>
                                <td class="text-right"><?= $item['quantity'] ?></td>
                                <td class="text-right">
                                    <?= CurrencyFormatter::getInstance()->format($item['unit_price']) ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($item['discount_percent'] > 0): ?>
                                        <?= $item['discount_percent'] ?>%
                                        <small class="text-muted d-block">
                                            <?= htmlspecialchars($item['discount_reason']) ?>
                                        </small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <?= CurrencyFormatter::getInstance()->format($item['final_price']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Subtotal:</th>
                            <td class="text-right">
                                <?= CurrencyFormatter::getInstance()->format($order['total_amount']) ?>
                            </td>
                        </tr>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <tr>
                                <th colspan="5" class="text-right">
                                    Order Discount:
                                    <small class="text-muted d-block">
                                        <?= htmlspecialchars($order['discount_reason']) ?>
                                    </small>
                                </th>
                                <td class="text-right text-danger">
                                    -<?= CurrencyFormatter::getInstance()->format($order['discount_amount']) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th colspan="5" class="text-right">Total:</th>
                            <td class="text-right">
                                <strong><?= CurrencyFormatter::getInstance()->format($order['final_amount']) ?></strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="order-section">
            <h3>
                Payment Information
                <span class="badge <?= $this->getPaymentStatusBadgeClass($order['payment_status']) ?>">
                    <?= ucfirst($order['payment_status']) ?>
                </span>
            </h3>
            
            <div class="info-grid mb-4">
                <div class="info-item">
                    <label>Payment Type</label>
                    <span><?= ucfirst($order['payment_type']) ?></span>
                </div>
                <div class="info-item">
                    <label>Total Amount</label>
                    <span><?= CurrencyFormatter::getInstance()->format($order['final_amount']) ?></span>
                </div>
                <div class="info-item">
                    <label>Amount Paid</label>
                    <span>
                        <?= CurrencyFormatter::getInstance()->format($order['paid_amount'] ?? 0) ?>
                        <?php if ($order['payment_status'] === 'partial'): ?>
                            <small class="text-muted d-block">
                                Remaining: <?= CurrencyFormatter::getInstance()->format($order['final_amount'] - ($order['paid_amount'] ?? 0)) ?>
                            </small>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($order['payments'])): ?>
                <h4>Payment History</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['payments'] as $payment): ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($payment['payment_date'])) ?></td>
                                    <td><?= CurrencyFormatter::getInstance()->format($payment['amount']) ?></td>
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

        <!-- Shipping Information -->
        <div class="order-section">
            <h3>Shipping Information</h3>
            <?php if (!empty($order['shipping'])): ?>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Method</label>
                        <span><?= ucfirst($order['shipping']['shipping_method']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Cost</label>
                        <span><?= CurrencyFormatter::getInstance()->format($order['shipping']['shipping_cost']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Tracking Number</label>
                        <span><?= htmlspecialchars($order['shipping']['tracking_number'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Shipping Date</label>
                        <span>
                            <?= $order['shipping']['shipping_date'] ? 
                                date('Y-m-d', strtotime($order['shipping']['shipping_date'])) : 
                                'Not shipped yet' ?>
                        </span>
                    </div>
                    <?php if (!empty($order['shipping']['notes'])): ?>
                        <div class="info-item full-width">
                            <label>Notes</label>
                            <span><?= nl2br(htmlspecialchars($order['shipping']['notes'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($order['order_status'] === 'processing'): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary" onclick="showShippingModal()">
                            <i class="fas fa-edit"></i> Update Shipping Details
                        </button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">No shipping information available</p>
            <?php endif; ?>
        </div>

        <!-- Order History -->
        <div class="order-section">
            <h3>Order History</h3>
            <div class="timeline">
                <?php foreach ($order['history'] as $history): ?>
                    <div class="timeline-item">
                        <div class="timeline-badge">
                            <i class="fas <?= $this->getStatusIcon($history['status']) ?>"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="badge <?= $this->getStatusBadgeClass($history['status']) ?>">
                                    <?= ucfirst($history['status']) ?>
                                </span>
                                <small class="text-muted">
                                    <?= date('Y-m-d H:i', strtotime($history['created_at'])) ?>
                                    by <?= htmlspecialchars($history['created_by_name']) ?>
                                </small>
                            </div>
                            <?php if (!empty($history['notes'])): ?>
                                <div class="timeline-body">
                                    <?= nl2br(htmlspecialchars($history['notes'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Additional Notes -->
        <?php if (!empty($order['notes'])): ?>
            <div class="order-section">
                <h3>Additional Notes</h3>
                <p><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
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
            <form action="<?= $baseUrl ?>/orders/addPayment/<?= $order['id'] ?>" method="POST">
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
                                   step="0.01"
                                   max="<?= $order['final_amount'] - ($order['paid_amount'] ?? 0) ?>">
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

<!-- Shipping Modal -->
<div class="modal fade" id="shippingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Shipping Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= $baseUrl ?>/orders/updateShipping/<?= $order['id'] ?>" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="shipping_date">Shipping Date</label>
                        <input type="date" 
                               id="shipping_date" 
                               name="shipping_date" 
                               class="form-control" 
                               value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="tracking_number">Tracking Number</label>
                        <input type="text" 
                               id="tracking_number" 
                               name="tracking_number" 
                               class="form-control"
                               value="<?= htmlspecialchars($order['shipping']['tracking_number'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="shipping_method">Shipping Method</label>
                        <select id="shipping_method" name="shipping_method" class="form-control" required>
                            <option value="pickup" <?= ($order['shipping']['shipping_method'] ?? '') === 'pickup' ? 'selected' : '' ?>>
                                Customer Pickup
                            </option>
                            <option value="delivery" <?= ($order['shipping']['shipping_method'] ?? '') === 'delivery' ? 'selected' : '' ?>>
                                Delivery Service
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="shipping_cost">Shipping Cost</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" 
                                   id="shipping_cost" 
                                   name="shipping_cost" 
                                   class="form-control" 
                                   value="<?= $order['shipping']['shipping_cost'] ?? 0 ?>" 
                                   min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shipping_notes">Notes</label>
                        <textarea id="shipping_notes" 
                                name="notes" 
                                class="form-control" 
                                rows="2"><?= htmlspecialchars($order['shipping']['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.view-order-page {
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

.order-meta {
    color: #666;
    font-size: 0.9em;
}

.actions {
    display: flex;
    gap: 10px;
}

.order-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.order-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.order-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-item label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 20px;
    width: 2px;
    background: #eee;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 50px;
}

.timeline-badge {
    position: absolute;
    left: 10px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #007bff;
    text-align: center;
    line-height: 17px;
    color: #007bff;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.timeline-body {
    color: #666;
}

.badge {
    padding: 5px 10px;
    border-radius: 15px;
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

    .order-sections {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function updateOrderStatus(status) {
    if (!confirm('Are you sure you want to update the order status?')) {
        return;
    }

    fetch(`<?= $baseUrl ?>/orders/updateStatus/<?= $order['id'] ?>`, {
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

function showShippingModal() {
    $('#shippingModal').modal('show');
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
