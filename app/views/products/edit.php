<div class="edit-product-page">
    <div class="page-header">
        <h2>Edit Product: <?= htmlspecialchars($product['name']) ?></h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/products/view/<?= $product['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Product
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="product-form">
        <div class="form-sections">
            <!-- Basic Information -->
            <div class="form-section">
                <h3>Basic Information</h3>
                
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control" 
                           required 
                           value="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <div class="form-group">
                    <label>SKU</label>
                    <p class="form-control-static"><?= htmlspecialchars($product['sku']) ?></p>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" 
                            name="description" 
                            class="form-control" 
                            rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
            </div>

            <!-- Pricing Information -->
            <div class="form-section">
                <h3>Pricing Information</h3>
                
                <div class="form-group">
                    <label for="purchase_price">Purchase Price *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp</span>
                        </div>
                        <input type="number" 
                               id="purchase_price" 
                               name="purchase_price" 
                               class="form-control" 
                               required 
                               min="0" 
                               step="0.01"
                               value="<?= htmlspecialchars($product['purchase_price']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="selling_price">Selling Price *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp</span>
                        </div>
                        <input type="number" 
                               id="selling_price" 
                               name="selling_price" 
                               class="form-control" 
                               required 
                               min="0" 
                               step="0.01"
                               value="<?= htmlspecialchars($product['selling_price']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="price_change_reason">Reason for Price Change</label>
                    <textarea id="price_change_reason" 
                            name="price_change_reason" 
                            class="form-control" 
                            rows="2"
                            placeholder="Required if prices are changed"></textarea>
                </div>
            </div>

            <!-- Stock Information -->
            <div class="form-section">
                <h3>Stock Information</h3>
                
                <div class="form-group">
                    <label for="stock_type">Stock Type *</label>
                    <select id="stock_type" name="stock_type" class="form-control" required>
                        <option value="stocked" <?= $product['stock_type'] === 'stocked' ? 'selected' : '' ?>>
                            Stocked
                        </option>
                        <option value="by_order" <?= $product['stock_type'] === 'by_order' ? 'selected' : '' ?>>
                            By Order
                        </option>
                    </select>
                </div>

                <div class="stock-fields" id="stockFields">
                    <div class="form-group">
                        <label>Current Stock</label>
                        <p class="form-control-static">
                            <?= $product['current_stock'] ?>
                            <a href="#" class="btn btn-sm btn-outline-primary ml-2" data-toggle="modal" data-target="#adjustStockModal">
                                <i class="fas fa-edit"></i> Adjust
                            </a>
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="min_stock">Minimum Stock Level</label>
                        <input type="number" 
                               id="min_stock" 
                               name="min_stock" 
                               class="form-control" 
                               min="0"
                               value="<?= htmlspecialchars($product['min_stock']) ?>">
                    </div>
                </div>
            </div>

            <!-- BPOM Information -->
            <div class="form-section">
                <h3>BPOM Information</h3>
                
                <div class="form-group">
                    <label>BPOM Registration Number</label>
                    <p class="form-control-static">
                        <?= htmlspecialchars($product['bpom_id'] ?? 'Not registered') ?>
                        <?php if (!empty($product['bpom_id'])): ?>
                            <span class="badge badge-success">Verified</span>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (!empty($product['bpom_category'])): ?>
                    <div class="form-group">
                        <label>BPOM Category</label>
                        <p class="form-control-static"><?= htmlspecialchars($product['bpom_category']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Status -->
            <div class="form-section">
                <h3>Product Status</h3>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" 
                               class="custom-control-input" 
                               id="is_active" 
                               name="is_active" 
                               value="1" 
                               <?= $product['is_active'] ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="is_active">Product is active</label>
                    </div>
                    <small class="form-text text-muted">
                        Inactive products won't appear in the product catalog
                    </small>
                </div>
            </div>

            <!-- Product Image -->
            <div class="form-section">
                <h3>Product Image</h3>
                
                <?php if (!empty($product['image'])): ?>
                    <div class="current-image mb-3">
                        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="img-thumbnail"
                             style="max-width: 200px;">
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="product_image">Upload New Image</label>
                    <input type="file" 
                           id="product_image" 
                           name="product_image" 
                           class="form-control-file"
                           accept="image/*">
                    <small class="form-text text-muted">
                        Leave empty to keep current image. Recommended size: 800x800 pixels, Max size: 2MB
                    </small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="<?= $baseUrl ?>/products/view/<?= $product['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
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
.edit-product-page {
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

.form-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.form-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control-static {
    padding: 7px 0;
    margin-bottom: 0;
}

.input-group-text {
    background-color: #f8f9fa;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 20px 0;
    border-top: 1px solid #eee;
}

.badge {
    padding: 5px 10px;
    border-radius: 15px;
    margin-left: 10px;
}

@media (max-width: 768px) {
    .form-sections {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stockType = document.getElementById('stock_type');
    const stockFields = document.getElementById('stockFields');
    const form = document.querySelector('.product-form');
    const originalPurchasePrice = parseFloat(document.getElementById('purchase_price').value);
    const originalSellingPrice = parseFloat(document.getElementById('selling_price').value);

    function toggleStockFields() {
        stockFields.style.display = stockType.value === 'stocked' ? 'block' : 'none';
    }

    stockType.addEventListener('change', toggleStockFields);
    toggleStockFields();

    form.addEventListener('submit', function(e) {
        const purchasePrice = parseFloat(document.getElementById('purchase_price').value);
        const sellingPrice = parseFloat(document.getElementById('selling_price').value);
        const priceChangeReason = document.getElementById('price_change_reason');

        if (sellingPrice <= purchasePrice) {
            e.preventDefault();
            alert('Selling price must be greater than purchase price');
            return;
        }

        if ((purchasePrice !== originalPurchasePrice || sellingPrice !== originalSellingPrice) && 
            !priceChangeReason.value.trim()) {
            e.preventDefault();
            alert('Please provide a reason for the price change');
            priceChangeReason.focus();
        }
    });
});
</script>
