<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Products</h2>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="/Salvio2/public/products/create" class="btn btn-primary">Add New Product</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>BPOM ID</th>
                            <th>Category</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Stock Type</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['bpom_id']) ?></td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td>Rp <?= number_format($product['purchase_price'], 2) ?></td>
                            <td>Rp <?= number_format($product['selling_price'], 2) ?></td>
                            <td><?= ucfirst($product['stock_type']) ?></td>
                            <td>
                                <?php if ($product['stock_type'] === 'stocked'): ?>
                                    <?= $product['current_stock'] ?? 0 ?>
                                    <?php if (($product['current_stock'] ?? 0) <= $product['min_stock']): ?>
                                        <span class="badge bg-danger">Low Stock</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= $product['min_stock'] ?></td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                <a href="/Salvio2/public/products/edit/<?= $product['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
