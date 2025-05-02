<?php
$hideNav = true;
$bodyClass = 'bg-light';
ob_start();
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 text-center">
            <div class="error-page">
                <h1 class="display-1 text-muted">404</h1>
                <h2 class="mb-4">Page Not Found</h2>
                <p class="text-muted mb-4">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
                <a href="<?php echo base_url(); ?>" class="btn btn-primary">
                    <i class="bi bi-house-door me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
