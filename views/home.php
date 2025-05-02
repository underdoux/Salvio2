<?php
$title = 'Dashboard';
$bodyClass = 'bg-light';
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-box fs-1 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-muted">Total Products</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($counts['products']); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-cart-check fs-1 text-success"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-muted">Total Orders</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($counts['orders']); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-hourglass-split fs-1 text-warning"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-muted">Pending Orders</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($counts['pending_orders']); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-currency-dollar fs-1 text-info"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-muted">Today's Sales</h6>
                            <h2 class="card-title mb-0"><?php echo format_money($today_sales); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                    <a href="<?php echo base_url('orders'); ?>" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo format_money($order['total']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($order['status']) {
                                                    'completed' => 'success',
                                                    'shipped' => 'info',
                                                    'in_progress' => 'warning',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo format_datetime($order['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Low Stock Alert</h5>
                    <a href="<?php echo base_url('products'); ?>" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($low_stock_products as $product): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted">Stock: <?php echo $product['stock']; ?> units</small>
                                    </div>
                                    <a href="<?php echo base_url('products/edit/' . $product['id']); ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Update Stock
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/base.php';
?>
