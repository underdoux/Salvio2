<?php
// Set page title
$title = 'Dashboard';
$bodyClass = 'bg-light';

// Define inline styles
$inlineStyles = '
    .feature-icon {
        font-size: 2rem;
        color: #0d6efd;
        margin-bottom: 1rem;
    }
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
    }
';

// Define content
ob_start(); 
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo base_url('/'); ?>">
            <i class="bi bi-capsule"></i>
            <?php echo htmlspecialchars(config('app_name')); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo base_url('/'); ?>">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('/products'); ?>">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('/orders'); ?>">
                        <i class="bi bi-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('/commissions'); ?>">
                        <i class="bi bi-currency-dollar"></i> Commissions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('/profits'); ?>">
                        <i class="bi bi-graph-up"></i> Profits
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('/reports'); ?>">
                        <i class="bi bi-file-earmark-text"></i> Reports
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> 
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text text-muted">
                                <small>Role: <?php echo htmlspecialchars($_SESSION['role_name']); ?></small>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo base_url('/settings'); ?>">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo base_url('/logout'); ?>">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p class="text-muted">Here's an overview of your pharmacy management system.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="feature-icon">
                        <i class="bi bi-box"></i>
                    </div>
                    <h5 class="card-title">Products</h5>
                    <p class="card-text">Manage your inventory, categories, and prices.</p>
                    <a href="<?php echo base_url('/products'); ?>" class="btn btn-primary">
                        View Products
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="feature-icon">
                        <i class="bi bi-cart"></i>
                    </div>
                    <h5 class="card-title">Orders</h5>
                    <p class="card-text">Process transactions and track order status.</p>
                    <a href="<?php echo base_url('/orders'); ?>" class="btn btn-primary">
                        View Orders
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="feature-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h5 class="card-title">Reports</h5>
                    <p class="card-text">View sales, commissions, and profit reports.</p>
                    <a href="<?php echo base_url('/reports'); ?>" class="btn btn-primary">
                        View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

// Include base layout
require_once __DIR__ . '/layouts/base.php';
?>
