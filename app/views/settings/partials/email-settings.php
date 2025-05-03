<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Setting</th>
            <th>Value</th>
            <th width="100">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($settings as $setting): ?>
            <?php if (strpos($setting['key'], 'smtp') === 0 || strpos($setting['key'], 'mail') === 0): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($setting['key']) ?></strong>
                        <?php if ($setting['description']): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($setting['description']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (strpos($setting['key'], 'password') !== false): ?>
                            <span class="text-muted">********</span>
                        <?php elseif (is_array($setting['value'])): ?>
                            <pre class="mb-0"><?= htmlspecialchars(json_encode($setting['value'], JSON_PRETTY_PRINT)) ?></pre>
                        <?php else: ?>
                            <?= htmlspecialchars($setting['value']) ?>
                        <?php endif; ?>
                        <?php if (strpos($setting['key'], 'smtp') === 0): ?>
                            <button class="btn btn-sm btn-outline-secondary mt-2 test-smtp-btn"
                                    data-key="<?= htmlspecialchars($setting['key']) ?>"
                                    data-toggle="tooltip"
                                    title="Send test email to verify settings">
                                <i class="fas fa-paper-plane"></i> Test Connection
                            </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/settings/edit/<?= urlencode($setting['key']) ?>" 
                           class="btn btn-sm btn-primary btn-edit"
                           data-toggle="tooltip"
                           title="Edit setting">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle SMTP test button clicks
    document.querySelectorAll('.test-smtp-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const key = this.getAttribute('data-key');
            try {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
                
                const response = await fetch('/settings/test-smtp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ key })
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', 'SMTP test successful! Test email sent.');
                } else {
                    showNotification('error', 'SMTP test failed: ' + result.error);
                }
            } catch (error) {
                showNotification('error', 'Error testing SMTP connection');
            } finally {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-paper-plane"></i> Test Connection';
            }
        });
    });

    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 1050;';
        alert.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        document.body.appendChild(alert);
        setTimeout(() => $(alert).alert('close'), 5000);
    }
});
</script>
