<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Order #<?= $order['id'] ?></h1>
        <div>
            <a href="/Salvio2/public/orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <?php if ($order['status'] !== 'paid'): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                <i class="fas fa-edit"></i> Update Status
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Order Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                    <td class="text-end">₱<?= number_format($item['unit_price'], 2) ?></td>
                                    <td class="text-end"><?= $item['discount'] ?>%</td>
                                    <td class="text-end">₱<?= number_format(
                                        $item['quantity'] * $item['unit_price'] * (1 - $item['discount']/100), 
                                        2
                                    ) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">₱<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                                <?php if ($order['discount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Order Discount (<?= $order['discount'] ?>%):</strong></td>
                                    <td class="text-end">-₱<?= number_format(
                                        $order['total_amount'] * ($order['discount']/100), 
                                        2
                                    ) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>₱<?= number_format(
                                        $order['total_amount'] * (1 - $order['discount']/100), 
                                        2
                                    ) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?= $this->getStatusBadgeClass($order['status']) ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Customer:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($order['customer_id']) ?></dd>

                        <dt class="col-sm-4">Created By:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($order['created_by_name']) ?></dd>

                        <dt class="col-sm-4">Date:</dt>
                        <dd class="col-sm-8"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></dd>

                        <dt class="col-sm-4">Payment:</dt>
                        <dd class="col-sm-8"><?= ucfirst($order['payment_type']) ?></dd>
                    </dl>
                </div>
            </div>

            <?php if ($order['status'] !== 'paid' && $order['payment_type'] === 'installment'): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Schedule</h5>
                </div>
                <div class="card-body">
                    <!-- Payment schedule implementation -->
                    <p class="text-muted">Payment schedule details will be implemented here.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/Salvio2/public/orders/updateStatus/<?= $order['id'] ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">New Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="in_progress" <?= $order['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
