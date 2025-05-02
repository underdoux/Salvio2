<?php
// Set page title
$title = 'Dashboard';

// Define content
ob_start(); 
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p class="text-muted">Here's an overview of your pharmacy management system.</p>
        </div>
    </div>

    <?php if ($_SESSION['role_name'] === 'admin'): ?>
    <div class="row g-4 mb-4">
        <!-- Quick Stats -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Today's Sales</h6>
                    <h2 class="card-title mb-0">Rp 0</h2>
                    <small class="text-muted">From 0 orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Low Stock Items</h6>
                    <h2 class="card-title mb-0">0</h2>
                    <small class="text-muted">Items need restock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Pending Orders</h6>
                    <h2 class="card-title mb-0">0</h2>
                    <small class="text-muted">Orders to process</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Monthly Revenue</h6>
                    <h2 class="card-title mb-0">Rp 0</h2>
                    <small class="text-muted">This month</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                    <a href="<?php echo base_url('orders'); ?>" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center my-5">No recent orders</p>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Low Stock Products</h5>
                    <a href="<?php echo base_url('products'); ?>" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center my-5">No low stock products</p>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($_SESSION['role_name'] === 'cashier'): ?>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cart-plus display-1 text-primary mb-3"></i>
                    <h5 class="card-title">New Order</h5>
                    <p class="card-text">Create a new order for walk-in customers</p>
                    <a href="<?php echo base_url('orders/create'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create Order
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-search display-1 text-primary mb-3"></i>
                    <h5 class="card-title">Check Stock</h5>
                    <p class="card-text">View and search product inventory</p>
                    <a href="<?php echo base_url('products'); ?>" class="btn btn-primary">
                        <i class="bi bi-box-seam"></i> View Products
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($_SESSION['role_name'] === 'sales'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cart-plus display-1 text-primary mb-3"></i>
                    <h5 class="card-title">New Order</h5>
                    <p class="card-text">Create a new order for customers</p>
                    <a href="<?php echo base_url('orders/create'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create Order
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack display-1 text-primary mb-3"></i>
                    <h5 class="card-title">My Commissions</h5>
                    <p class="card-text">View your commission earnings</p>
                    <a href="<?php echo base_url('commissions'); ?>" class="btn btn-primary">
                        <i class="bi bi-cash"></i> View Commissions
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-search display-1 text-primary mb-3"></i>
                    <h5 class="card-title">Check Stock</h5>
                    <p class="card-text">View and search product inventory</p>
                    <a href="<?php echo base_url('products'); ?>" class="btn btn-primary">
                        <i class="bi bi-box-seam"></i> View Products
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php 
$content = ob_get_clean();

// Include base layout
require_once __DIR__ . '/layouts/base.php';
?>
