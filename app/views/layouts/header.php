<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/Salvio2/public/">Salvio POS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/Salvio2/public/products">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Salvio2/public/orders">
                        <i class="bi bi-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Salvio2/public/commissions/rates">
                        <i class="bi bi-percent"></i> Commissions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Salvio2/public/profit-sharing">
                        <i class="bi bi-graph-up"></i> Profit Sharing
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Salvio2/public/reports">
                        <i class="bi bi-file-text"></i> Reports
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/Salvio2/public/logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
