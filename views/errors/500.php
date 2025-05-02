<?php
// Set page title and body class
$title = '500 Server Error';
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
        <i class="bi bi-exclamation-triangle"></i>
    </div>
    <h1 class="display-1">500</h1>
    <h2 class="h3 mb-3">Internal Server Error</h2>
    <p class="text-muted mb-4">
        Something went wrong on our end. Please try again later or contact support if the problem persists.
        <?php if (config('debug')): ?>
            <br><small class="text-danger">Error: <?php echo htmlspecialchars($e->getMessage()); ?></small>
        <?php endif; ?>
    </p>
    <div class="d-grid gap-2 col-6 mx-auto">
        <a href="<?php echo base_url('/'); ?>" class="btn btn-primary">
            <i class="bi bi-house-door"></i> Go to Home
        </a>
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
