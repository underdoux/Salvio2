<div class="create-product-page">
    <div class="page-header">
        <h2>Add New Product</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/products" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
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
                           value="<?= htmlspecialchars($data['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="sku">SKU</label>
                    <input type="text" 
                           id="sku" 
                           name="sku" 
                           class="form-control"
                           placeholder="Leave empty for auto-generation"
                           value="<?= htmlspecialchars($data['sku'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                <?= ($data['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
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
                            rows="4"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
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
                               value="<?= htmlspecialchars($data['purchase_price'] ?? '') ?>">
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
                               value="<?= htmlspecialchars($data['selling_price'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Stock Information -->
            <div class="form-section">
                <h3>Stock Information</h3>
                
                <div class="form-group">
                    <label for="stock_type">Stock Type *</label>
                    <select id="stock_type" name="stock_type" class="form-control" required>
                        <option value="stocked" <?= ($data['stock_type'] ?? '') === 'stocked' ? 'selected' : '' ?>>
                            Stocked
                        </option>
                        <option value="by_order" <?= ($data['stock_type'] ?? '') === 'by_order' ? 'selected' : '' ?>>
                            By Order
                        </option>
                    </select>
                </div>

                <div class="stock-fields" id="stockFields">
                    <div class="form-group">
                        <label for="initial_stock">Initial Stock</label>
                        <input type="number" 
                               id="initial_stock" 
                               name="initial_stock" 
                               class="form-control" 
                               min="0"
                               value="<?= htmlspecialchars($data['initial_stock'] ?? '0') ?>">
                    </div>

                    <div class="form-group">
                        <label for="min_stock">Minimum Stock Level</label>
                        <input type="number" 
                               id="min_stock" 
                               name="min_stock" 
                               class="form-control" 
                               min="0"
                               value="<?= htmlspecialchars($data['min_stock'] ?? '0') ?>">
                    </div>
                </div>
            </div>

            <!-- BPOM Information -->
            <div class="form-section">
                <h3>BPOM Information</h3>
                
                <div class="form-group">
                    <label for="bpom_id">BPOM Registration Number</label>
                    <input type="text" 
                           id="bpom_id" 
                           name="bpom_id" 
                           class="form-control"
                           value="<?= htmlspecialchars($data['bpom_id'] ?? '') ?>">
                    <small class="form-text text-muted">
                        Enter the BPOM registration number to automatically categorize the product
                    </small>
                </div>
            </div>

            <!-- Product Image -->
            <div class="form-section">
                <h3>Product Image</h3>
                
                <div class="form-group">
                    <label for="product_image">Upload Image</label>
                    <input type="file" 
                           id="product_image" 
                           name="product_image" 
                           class="form-control-file"
                           accept="image/*">
                    <small class="form-text text-muted">
                        Recommended size: 800x800 pixels, Max size: 2MB
                    </small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Product
            </button>
            <a href="<?= $baseUrl ?>/products" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<style>
.create-product-page {
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

    function toggleStockFields() {
        stockFields.style.display = stockType.value === 'stocked' ? 'block' : 'none';
    }

    stockType.addEventListener('change', toggleStockFields);
    toggleStockFields();

    // Validate selling price is greater than purchase price
    const form = document.querySelector('.product-form');
    form.addEventListener('submit', function(e) {
        const purchasePrice = parseFloat(document.getElementById('purchase_price').value);
        const sellingPrice = parseFloat(document.getElementById('selling_price').value);

        if (sellingPrice <= purchasePrice) {
            e.preventDefault();
            alert('Selling price must be greater than purchase price');
        }
    });
});
</script>
