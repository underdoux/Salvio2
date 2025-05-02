<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo base_url(); ?>">
            <i class="bi bi-capsule me-2"></i>
            <?php echo htmlspecialchars(config('app_name')); ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (auth()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $title === 'Dashboard' ? 'active' : ''; ?>" href="<?php echo base_url(); ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if (has_permission('view_products')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($title, 'Product') !== false ? 'active' : ''; ?>" 
                               href="<?php echo base_url('products'); ?>">
                                <i class="bi bi-box"></i> Products
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('view_orders')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($title, 'Order') !== false ? 'active' : ''; ?>" 
                               href="<?php echo base_url('orders'); ?>">
                                <i class="bi bi-cart"></i> Orders
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('view_commissions')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($title, 'Commission') !== false ? 'active' : ''; ?>" 
                               href="<?php echo base_url('commissions'); ?>">
                                <i class="bi bi-currency-dollar"></i> Commissions
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('view_reports')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('reports/sales'); ?>">
                                        Sales Report
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('reports/inventory'); ?>">
                                        Inventory Report
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('reports/commissions'); ?>">
                                        Commission Report
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('reports/profit-sharing'); ?>">
                                        Profit Sharing Report
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('manage_settings')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('settings/general'); ?>">
                                        General Settings
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('settings/users'); ?>">
                                        User Management
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('settings/roles'); ?>">
                                        Role & Permissions
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo base_url('settings/backup'); ?>">
                                        Backup & Restore
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <?php if (auth()): ?>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars(user()['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('profile'); ?>">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('change-password'); ?>">
                                    <i class="bi bi-key"></i> Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo base_url('logout'); ?>">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
