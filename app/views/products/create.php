<?php require_once '../app/views/layouts/main.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Product</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="/Salvio2/public/products/create" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="bpom_id" class="form-label">BPOM ID</label>
                            <input type="text" class="form-control" id="bpom_id" name="bpom_id" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="selling_price" class="form-label">Selling Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="stock_type" class="form-label">Stock Type</label>
                            <select class="form-select" id="stock_type" name="stock_type" required>
                                <option value="stocked">Stocked</option>
                                <option value="by_order">By Order</option>
                            </select>
                        </div>

                        <div id="stock_fields">
                            <div class="mb-3">
                                <label for="min_stock" class="form-label">Minimum Stock Level</label>
                                <input type="number" class="form-control" id="min_stock" name="min_stock" value="0" min="0">
                            </div>

                            <div class="mb-3">
                                <label for="initial_stock" class="form-label">Initial Stock</label>
                                <input type="number" class="form-control" id="initial_stock" name="initial_stock" value="0" min="0">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create Product</button>
                            <a href="/Salvio2/public/products" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('stock_type').addEventListener('change', function() {
    const stockFields = document.getElementById('stock_fields');
    stockFields.style.display = this.value === 'stocked' ? 'block' : 'none';
});
</script>
