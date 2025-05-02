<?php
$hideNav = true;
$bodyClass = 'bg-light';
ob_start();
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-capsule text-primary" style="font-size: 3rem;"></i>
                        <h4 class="mt-2"><?php echo config('app_name'); ?></h4>
                        <p class="text-muted">Please sign in to continue</p>
                    </div>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo base_url('login'); ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" 
                                       class="form-control <?php echo has_error('username') ? 'is-invalid' : ''; ?>" 
                                       id="username" 
                                       name="username" 
                                       value="<?php echo old('username'); ?>"
                                       required 
                                       autofocus>
                                <?php if (has_error('username')): ?>
                                    <div class="invalid-feedback"><?php echo get_error('username'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" 
                                       class="form-control <?php echo has_error('password') ? 'is-invalid' : ''; ?>" 
                                       id="password" 
                                       name="password" 
                                       required>
                                <?php if (has_error('password')): ?>
                                    <div class="invalid-feedback"><?php echo get_error('password'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <small class="text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo config('app_name'); ?>
                    <br>
                    Version <?php echo config('app_version'); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
