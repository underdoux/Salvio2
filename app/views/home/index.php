<div class="dashboard">
    <div class="welcome-section">
        <h2>Welcome, <?= htmlspecialchars($user['username']) ?></h2>
        <p>Role: <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
    </div>

    <div class="quick-stats">
        <div class="stat-card">
            <h3>Today's Orders</h3>
            <div class="stat-value"><?= $todayOrders ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <div class="stat-value"><?= $pendingOrders ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Products</h3>
            <div class="stat-value"><?= $totalProducts ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <h3>Monthly Revenue</h3>
            <div class="stat-value"><?= isset($monthlyRevenue) ? CurrencyFormatter::getInstance()->format($monthlyRevenue) : 'Rp 0' ?></div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="<?= $baseUrl ?>/orders/create" class="btn btn-primary">New Order</a>
        <a href="<?= $baseUrl ?>/products" class="btn btn-secondary">Manage Products</a>
        <a href="<?= $baseUrl ?>/analytics" class="btn btn-info">View Analytics</a>
    </div>

    <?php if ($user['role'] === 'admin'): ?>
    <div class="admin-section">
        <h3>Administrative Actions</h3>
        <div class="admin-buttons">
            <a href="<?= $baseUrl ?>/profit-sharing" class="btn btn-warning">Profit Sharing</a>
            <a href="<?= $baseUrl ?>/commissions/rates" class="btn btn-success">Commission Rates</a>
            <a href="<?= $baseUrl ?>/analytics/trends" class="btn btn-info">Market Trends</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.dashboard {
    padding: 20px;
}

.welcome-section {
    margin-bottom: 30px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 16px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.action-buttons, .admin-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
}

.admin-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-weight: 500;
}

.btn-primary {
    background-color: #007bff;
}

.btn-secondary {
    background-color: #6c757d;
}

.btn-info {
    background-color: #17a2b8;
}

.btn-warning {
    background-color: #ffc107;
    color: #333;
}

.btn-success {
    background-color: #28a745;
}

.btn:hover {
    opacity: 0.9;
}
</style>
