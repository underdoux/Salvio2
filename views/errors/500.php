<?php
$title = '500 Server Error';
$bodyClass = 'bg-light d-flex align-items-center min-vh-100';
ob_start();
?>

<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="error-page">
                <h1 class="display-1 text-danger">500</h1>
                <h2 class="mb-4">Internal Server Error</h2>
                <p class="lead text-muted mb-4">Something went wrong on our servers. We are working to fix the problem. Please try again later.</p>
                <div class="mb-4">
                    <?php if (config('debug') && isset($error)): ?>
                        <div class="alert alert-danger text-start">
                            <h5>Error Details:</h5>
                            <pre class="mb-0"><?php echo htmlspecialchars($error); ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="<?php echo base_url(); ?>" class="btn btn-primary">
                    <i class="bi bi-house-door"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/base.php';
?>
