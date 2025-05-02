<?php
$hideNav = true;
$bodyClass = 'bg-light';
ob_start();
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 text-center">
            <div class="error-page">
                <h1 class="display-1 text-muted">403</h1>
                <h2 class="mb-4">Access Denied</h2>
                <p class="text-muted mb-4">You don't have permission to access this resource. Please contact your administrator if you believe this is a mistake.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo base_url(); ?>" class="btn btn-primary">
                        <i class="bi bi-house-door me-2"></i>Back to Home
                    </a>
                    <?php if (!auth()): ?>
                        <a href="<?php echo base_url('login'); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (config('debug') && isset($error)): ?>
                    <div class="alert alert-danger mt-4 text-start">
                        <h5>Debug Information:</h5>
                        <pre class="mb-0"><?php echo htmlspecialchars($error); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
