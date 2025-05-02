<?php
session_start();
require_once __DIR__ . '/../config/app.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Salvio2/Salvio2/public/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $config['app_name'] ?? 'POS Pharma'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><?php echo $config['app_name'] ?? 'POS Pharma'; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/Salvio2/Salvio2/public/">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/Salvio2/Salvio2/public/products">
                            <i class="bi bi-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/Salvio2/Salvio2/public/orders">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/Salvio2/Salvio2/public/reports">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/Salvio2/Salvio2/public/profile">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/Salvio2/Salvio2/public/logout">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
                <p>Select an option from the navigation menu to get started.</p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Products</h5>
                        <p class="card-text">Manage your product inventory</p>
                        <a href="/Salvio2/Salvio2/public/products" class="btn btn-light">View Products</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Orders</h5>
                        <p class="card-text">Process customer orders</p>
                        <a href="/Salvio2/Salvio2/public/orders" class="btn btn-light">View Orders</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Reports</h5>
                        <p class="card-text">View sales and inventory reports</p>
                        <a href="/Salvio2/Salvio2/public/reports" class="btn btn-light">View Reports</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Settings</h5>
                        <p class="card-text">Configure system settings</p>
                        <a href="/Salvio2/Salvio2/public/settings" class="btn btn-light">View Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
