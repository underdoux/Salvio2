<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container mt-4">
    <h2>Account Recovery Options</h2>
    
    <!-- SMS Verification Setup -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>SMS Verification</h4>
        </div>
        <div class="card-body">
            <?php if (!isset($user->phone_verified)): ?>
                <form action="/auth/setup-sms" method="POST" class="mb-3">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="Enter phone number" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Set Up SMS Verification</button>
                </form>
            <?php else: ?>
                <p>SMS verification is set up for: <?php echo maskPhoneNumber($user->phone); ?></p>
                <form action="/auth/update-sms" method="POST" class="d-inline">
                    <button type="submit" class="btn btn-secondary">Update Phone Number</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Backup Codes -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>Backup Codes</h4>
        </div>
        <div class="card-body">
            <p>Backup codes are one-time use codes that can help you regain access to your account.</p>
            <?php if (isset($backupCodes) && !empty($backupCodes)): ?>
                <div class="alert alert-warning">
                    <strong>Important:</strong> Save these codes in a secure place. They will not be shown again.
                </div>
                <div class="backup-codes-container bg-light p-3 mb-3">
                    <?php foreach ($backupCodes as $code): ?>
                        <code class="d-block mb-2"><?php echo $code; ?></code>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-secondary" onclick="window.print()">Print Codes</button>
            <?php endif; ?>
            <form action="/auth/generate-backup-codes" method="POST" class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <?php echo isset($backupCodes) ? 'Generate New Codes' : 'Generate Backup Codes'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Trusted Devices -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>Trusted Devices</h4>
        </div>
        <div class="card-body">
            <?php if (isset($trustedDevices) && !empty($trustedDevices)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Last Used</th>
                                <th>Trusted Until</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trustedDevices as $device): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($device->device_name); ?></td>
                                    <td><?php echo formatDate($device->last_used_at); ?></td>
                                    <td><?php echo formatDate($device->trusted_until); ?></td>
                                    <td>
                                        <form action="/auth/remove-trusted-device" method="POST" class="d-inline">
                                            <input type="hidden" name="device_id" value="<?php echo $device->id; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No trusted devices found.</p>
            <?php endif; ?>
            
            <?php if (isset($currentDevice) && !$currentDevice->trusted): ?>
                <form action="/auth/trust-device" method="POST" class="mt-3">
                    <div class="form-group">
                        <label for="device_name">Device Name</label>
                        <input type="text" class="form-control" id="device_name" name="device_name" 
                               placeholder="Enter a name for this device" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Trust This Device</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recovery Process -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>Recovery Process</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h5>If you lose access to your account:</h5>
                <ol>
                    <li>Try using SMS verification first</li>
                    <li>Use one of your backup codes if SMS is not available</li>
                    <li>Access from a trusted device will bypass 2FA</li>
                    <li>Contact support if none of the above methods work</li>
                </ol>
            </div>
            
            <div class="recovery-methods-status">
                <h5>Your Recovery Methods:</h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        SMS Verification
                        <span class="badge <?php echo isset($user->phone_verified) ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo isset($user->phone_verified) ? 'Active' : 'Not Set Up'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Backup Codes
                        <span class="badge <?php echo isset($backupCodesCount) && $backupCodesCount > 0 ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo isset($backupCodesCount) ? $backupCodesCount . ' remaining' : 'Not Generated'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Trusted Devices
                        <span class="badge <?php echo isset($trustedDevices) && !empty($trustedDevices) ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo isset($trustedDevices) ? count($trustedDevices) . ' devices' : 'None'; ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number formatting
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }

    // Backup codes copy functionality
    const codes = document.querySelectorAll('.backup-codes-container code');
    codes.forEach(code => {
        code.addEventListener('click', function() {
            const range = document.createRange();
            range.selectNode(code);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            window.getSelection().removeAllRanges();
            
            // Show copied notification
            const notification = document.createElement('span');
            notification.className = 'text-success ml-2';
            notification.textContent = 'Copied!';
            code.appendChild(notification);
            setTimeout(() => notification.remove(), 2000);
        });
    });
});
</script>

<style>
.backup-codes-container code {
    cursor: pointer;
    user-select: all;
}
.backup-codes-container code:hover {
    background-color: #e9ecef;
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>
