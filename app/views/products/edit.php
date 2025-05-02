<?php require_once '../app/views/layouts/main.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Product: <?= htmlspecialchars($product['name']) ?></h3>
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

                    <form action="/Salvio2/public/products/edit/<?= $product['id'] ?>" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="bpom_id" class="form-label">BPOM ID</label>
                            <input type="text" class="form-control" id="bpom_id" name="bpom_id" value="<?= htmlspecialchars($product['bpom_id']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" value="<?= $product['purchase_price'] ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="selling_price" class="form-label">Selling Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" value="<?= $product['selling_price'] ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($product['stock_type'] === 'stocked'): ?>
                            <div class="mb-3">
                                <label for="min_stock" class="form-label">Minimum Stock Level</label>
                                <input type="number" class="form-control" id="min_stock" name="min_stock" value="<?= $product['min_stock'] ?>" min="0">
                            </div>

                            <div class="mb-3">
                                <label for="current_stock" class="form-label">Current Stock</label>
                                <input type="text" class="form-control" value="<?= $product['current_stock'] ?? 0 ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="stock_adjustment" class="form-label">Stock Adjustment</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('stock_adjustment').stepDown()">-</button>
                                    <input type="number" class="form-control text-center" id="stock_adjustment" name="stock_adjustment" value="0">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('stock_adjustment').stepUp()">+</button>
                                </div>
                                <small class="form-text text-muted">Use negative values to decrease stock</small>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Product</button>
                            <a href="/Salvio2/public/products" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
