<div class="products-page">
    <div class="page-header">
        <h2>Products Management</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/products/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="<?= $baseUrl ?>/products" class="filters-form">
            <div class="form-group">
                <input type="text" 
                       name="search" 
                       placeholder="Search products..." 
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       class="form-control">
            </div>

            <div class="form-group">
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                            <?= ($filters['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <select name="stock_type" class="form-control">
                    <option value="">All Types</option>
                    <option value="stocked" <?= ($filters['stock_type'] ?? '') === 'stocked' ? 'selected' : '' ?>>
                        Stocked
                    </option>
                    <option value="by_order" <?= ($filters['stock_type'] ?? '') === 'by_order' ? 'selected' : '' ?>>
                        By Order
                    </option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="<?= $baseUrl ?>/products" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock Type</th>
                    <th>Current Stock</th>
                    <th>Selling Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No products found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['sku']) ?></td>
                            <td>
                                <a href="<?= $baseUrl ?>/products/view/<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                            <td>
                                <span class="badge <?= $product['stock_type'] === 'stocked' ? 'badge-primary' : 'badge-warning' ?>">
                                    <?= ucfirst($product['stock_type']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['stock_type'] === 'stocked'): ?>
                                    <span class="<?= $product['current_stock'] <= $product['min_stock'] ? 'text-danger' : '' ?>">
                                        <?= $product['current_stock'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?= CurrencyFormatter::getInstance()->format($product['selling_price']) ?></td>
                            <td>
                                <span class="badge <?= $product['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= $baseUrl ?>/products/view/<?= $product['id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= $baseUrl ?>/products/edit/<?= $product['id'] ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($products)): ?>
        <div class="pagination-section">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php
                    $totalPages = ceil(count($products) / 10);
                    for ($i = 1; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?= $currentPage == $i ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>/products?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= isset($filters['category_id']) ? '&category=' . $filters['category_id'] : '' ?><?= isset($filters['stock_type']) ? '&stock_type=' . $filters['stock_type'] : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<style>
.products-page {
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

.filters-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.form-group {
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

.badge-primary {
    background-color: #007bff;
}

.badge-warning {
    background-color: #ffc107;
    color: #000;
}

.badge-success {
    background-color: #28a745;
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
    .filters-form {
        flex-direction: column;
        gap: 15px;
    }

    .form-group {
        width: 100%;
    }

    .table-responsive {
        margin: 0 -20px;
    }
}
</style>
