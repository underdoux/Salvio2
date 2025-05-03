<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Verify Phone Number</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-info">
                            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="/auth/verify-sms" method="POST">
                        <div class="form-group mb-3">
                            <label for="code">Verification Code</label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   placeholder="Enter 6-digit code" required 
                                   pattern="[0-9]{6}" maxlength="6">
                            <small class="form-text text-muted">
                                Enter the 6-digit code sent to your phone
                            </small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Verify Phone Number
                            </button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <p class="mb-1">Didn't receive the code?</p>
                        <form action="/auth/resend-sms" method="POST" class="d-inline">
                            <button type="submit" class="btn btn-link">Resend Code</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="/recovery" class="btn btn-link">Back to Recovery Options</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format verification code input
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substr(0, 6);
        });
    }
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
