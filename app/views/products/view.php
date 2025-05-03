<div class="view-product-page">
    <div class="page-header">
        <div class="title-section">
            <h2><?= htmlspecialchars($product['name']) ?></h2>
            <span class="badge <?= $product['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </div>
        <div class="actions">
            <a href="<?= $baseUrl ?>/products/edit/<?= $product['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Product
            </a>
            <a href="<?= $baseUrl ?>/products" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>

    <div class="product-sections">
        <!-- Basic Information -->
        <div class="product-section">
            <h3>Basic Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>SKU</label>
                    <span><?= htmlspecialchars($product['sku']) ?></span>
                </div>
                <div class="info-item">
                    <label>Category</label>
                    <span><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                </div>
                <div class="info-item full-width">
                    <label>Description</label>
                    <span><?= nl2br(htmlspecialchars($product['description'] ?? 'No description available')) ?></span>
                </div>
            </div>
        </div>

        <!-- Pricing Information -->
        <div class="product-section">
            <h3>Pricing Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Purchase Price</label>
                    <span><?= CurrencyFormatter::getInstance()->format($product['purchase_price']) ?></span>
                </div>
                <div class="info-item">
                    <label>Selling Price</label>
                    <span><?= CurrencyFormatter::getInstance()->format($product['selling_price']) ?></span>
                </div>
                <div class="info-item">
                    <label>Profit Margin</label>
                    <span class="<?= ($product['selling_price'] - $product['purchase_price']) > 0 ? 'text-success' : 'text-danger' ?>">
                        <?= CurrencyFormatter::getInstance()->format($product['selling_price'] - $product['purchase_price']) ?>
                        (<?= round((($product['selling_price'] - $product['purchase_price']) / $product['purchase_price']) * 100, 2) ?>%)
                    </span>
                </div>
            </div>
        </div>

        <!-- Stock Information -->
        <div class="product-section">
            <h3>Stock Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Stock Type</label>
                    <span class="badge <?= $product['stock_type'] === 'stocked' ? 'badge-primary' : 'badge-warning' ?>">
                        <?= ucfirst($product['stock_type']) ?>
                    </span>
                </div>
                <?php if ($product['stock_type'] === 'stocked'): ?>
                    <div class="info-item">
                        <label>Current Stock</label>
                        <span class="<?= $product['current_stock'] <= $product['min_stock'] ? 'text-danger' : '' ?>">
                            <?= $product['current_stock'] ?>
                            <?php if ($product['current_stock'] <= $product['min_stock']): ?>
                                <i class="fas fa-exclamation-triangle text-warning" title="Below minimum stock level"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Minimum Stock</label>
                        <span><?= $product['min_stock'] ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($product['stock_type'] === 'stocked'): ?>
                <div class="stock-actions mt-3">
                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#adjustStockModal">
                        <i class="fas fa-plus-minus"></i> Adjust Stock
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- BPOM Information -->
        <div class="product-section">
            <h3>BPOM Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Registration Number</label>
                    <span>
                        <?= htmlspecialchars($product['bpom_id'] ?? 'Not registered') ?>
                        <?php if (!empty($product['bpom_id'])): ?>
                            <span class="badge badge-success">Verified</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!empty($product['bpom_category'])): ?>
                    <div class="info-item">
                        <label>BPOM Category</label>
                        <span><?= htmlspecialchars($product['bpom_category']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stock Movement History -->
        <?php if ($product['stock_type'] === 'stocked' && !empty($stockMovements)): ?>
            <div class="product-section">
                <h3>Stock Movement History</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Updated By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockMovements as $movement): ?>
                                <tr>
                                    <td><?= date('Y-m-d H:i', strtotime($movement['created_at'])) ?></td>
                                    <td>
                                        <span class="badge <?= $movement['movement_type'] === 'in' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($movement['movement_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= $movement['quantity'] ?></td>
                                    <td><?= htmlspecialchars($movement['reference'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($movement['username'] ?? 'System') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Price History -->
        <?php if (!empty($priceHistory)): ?>
            <div class="product-section">
                <h3>Price History</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Reason</th>
                                <th>Updated By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($priceHistory as $price): ?>
                                <tr>
                                    <td><?= date('Y-m-d H:i', strtotime($price['created_at'])) ?></td>
                                    <td><?= CurrencyFormatter::getInstance()->format($price['purchase_price']) ?></td>
                                    <td><?= CurrencyFormatter::getInstance()->format($price['selling_price']) ?></td>
                                    <td><?= htmlspecialchars($price['change_reason'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($price['username'] ?? 'System') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= $baseUrl ?>/products/updateStock/<?= $product['id'] ?>" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               class="form-control" 
                               required>
                        <small class="form-text text-muted">
                            Use positive numbers to add stock, negative to remove
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Adjustment Type</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="purchase">Purchase</option>
                            <option value="return">Return</option>
                            <option value="loss">Loss/Damage</option>
                            <option value="correction">Correction</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reference">Reference/Notes</label>
                        <textarea id="reference" 
                                name="reference" 
                                class="form-control" 
                                rows="2"></textarea>
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
.view-product-page {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.title-section {
    display: flex;
    align-items: center;
    gap: 15px;
}

.actions {
    display: flex;
    gap: 10px;
}

.product-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.product-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.product-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    margin-bottom: 15px;
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

.info-item span {
    font-size: 1.1em;
    color: #333;
}

.badge {
    padding: 5px 10px;
    border-radius: 15px;
}

.badge-success {
    background-color: #28a745;
    color: white;
}

.badge-danger {
    background-color: #dc3545;
    color: white;
}

.badge-primary {
    background-color: #007bff;
    color: white;
}

.badge-warning {
    background-color: #ffc107;
    color: #000;
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
        align-items: flex-start;
    }

    .actions {
        width: 100%;
    }

    .actions .btn {
        flex: 1;
    }

    .product-sections {
        grid-template-columns: 1fr;
    }
}
</style>
