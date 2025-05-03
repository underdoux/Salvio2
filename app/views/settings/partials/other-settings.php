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
            <?php 
            $isOtherSetting = strpos($setting['key'], 'currency') !== 0 && 
                             strpos($setting['key'], 'smtp') !== 0 && 
                             strpos($setting['key'], 'mail') !== 0 && 
                             strpos($setting['key'], 'discount') === false;
            if ($isOtherSetting):
            ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($setting['key']) ?></strong>
                        <?php if ($setting['description']): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($setting['description']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (strpos($setting['key'], 'password') !== false || strpos($setting['key'], 'secret') !== false): ?>
                            <span class="text-muted">********</span>
                        <?php elseif ($setting['type'] === 'bool'): ?>
                            <span class="badge badge-<?= $setting['value'] ? 'success' : 'secondary' ?>">
                                <?= $setting['value'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        <?php elseif ($setting['type'] === 'json'): ?>
                            <pre class="mb-0"><?= htmlspecialchars(json_encode($setting['value'], JSON_PRETTY_PRINT)) ?></pre>
                        <?php else: ?>
                            <?= htmlspecialchars($setting['value']) ?>
                        <?php endif; ?>

                        <?php if ($setting['type'] === 'bool'): ?>
                            <button class="btn btn-sm btn-outline-primary mt-2 toggle-setting"
                                    data-key="<?= htmlspecialchars($setting['key']) ?>"
                                    data-current="<?= $setting['value'] ? '1' : '0' ?>"
                                    data-toggle="tooltip"
                                    title="Toggle setting">
                                <i class="fas fa-toggle-<?= $setting['value'] ? 'on' : 'off' ?>"></i>
                                <?= $setting['value'] ? 'Disable' : 'Enable' ?>
                            </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="/settings/edit/<?= urlencode($setting['key']) ?>" 
                               class="btn btn-sm btn-primary btn-edit"
                               data-toggle="tooltip"
                               title="Edit setting">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($setting['type'] !== 'bool'): ?>
                                <button type="button"
                                        class="btn btn-sm btn-info"
                                        data-toggle="modal"
                                        data-target="#settingHistoryModal"
                                        data-key="<?= htmlspecialchars($setting['key']) ?>"
                                        title="View history">
                                    <i class="fas fa-history"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Setting History Modal -->
<div class="modal fade" id="settingHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Setting History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
                <div class="history-content" style="display: none;">
                    <!-- History content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle boolean setting toggles
    document.querySelectorAll('.toggle-setting').forEach(button => {
        button.addEventListener('click', async function() {
            const key = this.getAttribute('data-key');
            const currentValue = this.getAttribute('data-current') === '1';
            
            try {
                button.disabled = true;
                const response = await fetch('/settings/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ 
                        key,
                        value: !currentValue
                    })
                });

                const result = await response.json();
                if (result.success) {
                    // Update UI
                    const badge = button.closest('td').querySelector('.badge');
                    badge.className = `badge badge-${!currentValue ? 'success' : 'secondary'}`;
                    badge.textContent = !currentValue ? 'Enabled' : 'Disabled';
                    
                    // Update button
                    button.innerHTML = `<i class="fas fa-toggle-${!currentValue ? 'on' : 'off'}"></i> ${!currentValue ? 'Disable' : 'Enable'}`;
                    button.setAttribute('data-current', !currentValue ? '1' : '0');
                    
                    showNotification('success', 'Setting updated successfully');
                } else {
                    showNotification('error', 'Failed to update setting');
                }
            } catch (error) {
                showNotification('error', 'Error updating setting');
            } finally {
                button.disabled = false;
            }
        });
    });

    // Handle setting history modal
    $('#settingHistoryModal').on('show.bs.modal', async function(event) {
        const button = event.relatedTarget;
        const key = button.getAttribute('data-key');
        const modal = $(this);
        const spinner = modal.find('.loading-spinner');
        const content = modal.find('.history-content');

        spinner.show();
        content.hide();

        try {
            const response = await fetch(`/settings/history/${encodeURIComponent(key)}`, {
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const history = await response.json();

            let html = '<div class="table-responsive"><table class="table table-bordered">';
            html += '<thead><tr><th>Date</th><th>Old Value</th><th>New Value</th><th>Changed By</th></tr></thead><tbody>';
            
            history.forEach(entry => {
                html += `<tr>
                    <td>${new Date(entry.created_at).toLocaleString()}</td>
                    <td>${entry.old_value}</td>
                    <td>${entry.new_value}</td>
                    <td>${entry.user_name}</td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            content.html(html);
            
            spinner.hide();
            content.show();
        } catch (error) {
            content.html('<div class="alert alert-danger">Error loading history</div>');
            spinner.hide();
            content.show();
        }
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
