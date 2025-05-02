<?php
// Set page title and body class
$title = 'Login';
$bodyClass = 'bg-light d-flex align-items-center min-vh-100';
$hideNav = true;

// Define inline styles
$inlineStyles = '
    .form-signin {
        width: 100%;
        max-width: 330px;
        padding: 15px;
        margin: auto;
    }
    .form-signin .form-floating:focus-within {
        z-index: 2;
    }
    .form-signin input[type="text"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }
    .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
    .brand-icon {
        font-size: 3rem;
        color: var(--bs-primary);
        margin-bottom: 1rem;
    }
';

// Start output buffering
ob_start();
?>

<main class="form-signin text-center">
    <form action="<?php echo base_url('auth/login'); ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        
        <div class="brand-icon">
            <i class="bi bi-capsule"></i>
        </div>
        <h1 class="h3 mb-3 fw-normal"><?php echo htmlspecialchars(config('app_name')); ?></h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash']['type'] ?? 'info'); ?>">
                <?php 
                    echo htmlspecialchars($_SESSION['flash']['message'] ?? ''); 
                    unset($_SESSION['flash']); 
                ?>
            </div>
        <?php endif; ?>

        <div class="form-floating mb-2">
            <input type="text" 
                   class="form-control <?php echo has_error('username') ? 'is-invalid' : ''; ?>" 
                   id="username" 
                   name="username" 
                   placeholder="Username" 
                   value="<?php echo htmlspecialchars(old('username')); ?>"
                   required 
                   autocomplete="username"
                   autofocus>
            <label for="username">Username</label>
            <?php if (has_error('username')): ?>
                <div class="invalid-feedback"><?php echo get_error('username'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-floating mb-3">
            <input type="password" 
                   class="form-control <?php echo has_error('password') ? 'is-invalid' : ''; ?>" 
                   id="password" 
                   name="password" 
                   placeholder="Password" 
                   required 
                   autocomplete="current-password">
            <label for="password">Password</label>
            <?php if (has_error('password')): ?>
                <div class="invalid-feedback"><?php echo get_error('password'); ?></div>
            <?php endif; ?>
        </div>

        <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Sign in
        </button>
        
        <p class="mt-5 mb-3 text-muted">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(config('app_name')); ?>
            <br>
            <small>Version <?php echo htmlspecialchars(config('app_version')); ?></small>
        </p>
    </form>
</main>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/base.php';
?>
