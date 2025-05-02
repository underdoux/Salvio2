<?php
$title = '403 Forbidden';
$bodyClass = 'bg-light d-flex align-items-center min-vh-100';
ob_start();
?>

<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="error-page">
                <h1 class="display-1 text-warning">403</h1>
                <h2 class="mb-4">Access Denied</h2>
                <p class="lead text-muted mb-4">You do not have permission to access this page. Please contact your administrator if you believe this is a mistake.</p>
                <?php if (isset($_SESSION['flash']['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash']['type'] ?? 'warning'; ?> mb-4">
                        <?php 
                            echo htmlspecialchars($_SESSION['flash']['message']); 
                            unset($_SESSION['flash']);
                        ?>
                    </div>
                <?php endif; ?>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="<?php echo base_url(); ?>" class="btn btn-primary">
                        <i class="bi bi-house-door"></i> Back to Home
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo base_url('login'); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/base.php';
?>
