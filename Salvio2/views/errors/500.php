<?php
$hideNav = true;
$bodyClass = 'bg-light';
ob_start();
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 text-center">
            <div class="error-page">
                <h1 class="display-1 text-muted">500</h1>
                <h2 class="mb-4">Internal Server Error</h2>
                <p class="text-muted mb-4">Something went wrong on our servers. We are working to fix the problem. Please try again later.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo base_url(); ?>" class="btn btn-primary">
                        <i class="bi bi-house-door me-2"></i>Back to Home
                    </a>
                    <button onclick="window.location.reload()" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                    </button>
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
