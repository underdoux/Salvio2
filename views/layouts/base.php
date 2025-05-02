<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo htmlspecialchars($title ?? config('app_name')); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link href="<?php echo asset($style); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineStyles)): ?>
        <style><?php echo $inlineStyles; ?></style>
    <?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass ?? ''); ?>">
    <?php if (!isset($hideNav) || !$hideNav): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo base_url(); ?>">
                <i class="bi bi-capsule me-2"></i>
                <?php echo htmlspecialchars(config('app_name')); ?>
            </a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo is_active('') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    
                    <?php if (has_permission('view_products')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo is_active('products') ? 'active' : ''; ?>" href="<?php echo base_url('products'); ?>">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('view_orders')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo is_active('orders') ? 'active' : ''; ?>" href="<?php echo base_url('orders'); ?>">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('view_commissions')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo is_active('commissions') ? 'active' : ''; ?>" href="<?php echo base_url('commissions'); ?>">
                            <i class="bi bi-cash"></i> Commissions
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('view_reports')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo is_active('reports') ? 'active' : ''; ?>" href="<?php echo base_url('reports'); ?>">
                            <i class="bi bi-file-earmark-text"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('view_insights')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo is_active('insights') ? 'active' : ''; ?>" href="<?php echo base_url('insights'); ?>">
                            <i class="bi bi-graph-up"></i> Insights
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (has_permission('manage_settings')): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('settings'); ?>">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key"></i> Change Password
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('auth/logout'); ?>">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>

    <?php echo $content; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="changePasswordForm" action="<?php echo base_url('auth/change-password'); ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo asset('js/app.js'); ?>"></script>
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo asset($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
