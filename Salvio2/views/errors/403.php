<?php
// Set page title and body class
$title = '403 Forbidden';
$bodyClass = 'bg-light d-flex align-items-center';

// Define inline styles
$inlineStyles = '
    .error-page {
        width: 100%;
        max-width: 600px;
        padding: 15px;
        margin: auto;
        text-align: center;
    }
    .error-icon {
        font-size: 5rem;
        color: #dc3545;
        margin-bottom: 1rem;
    }
';

// Define content
ob_start(); 
?>
<main class="error-page">
    <div class="error-icon">
        <i class="bi bi-shield-exclamation"></i>
    </div>
    <h1 class="display-1">403</h1>
    <h2 class="h3 mb-3">Access Forbidden</h2>
    <p class="text-muted mb-4">
        You don't have permission to access this page. 
        <?php if (!isset($_SESSION['user_id'])): ?>
            Please log in with appropriate credentials.
        <?php else: ?>
            Please contact your administrator if you believe this is an error.
        <?php endif; ?>
    </p>
    <div class="d-grid gap-2 col-6 mx-auto">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="<?php echo base_url('/login'); ?>" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
        <?php else: ?>
            <a href="<?php echo base_url('/'); ?>" class="btn btn-primary">
                <i class="bi bi-house-door"></i> Go to Home
            </a>
        <?php endif; ?>
        <button onclick="history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Go Back
        </button>
    </div>
    <p class="mt-5 mb-3 text-muted">
        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(config('app_name')); ?>
    </p>
</main>
<?php 
$content = ob_get_clean();

// Include base layout
require_once __DIR__ . '/../layouts/base.php';
?>
