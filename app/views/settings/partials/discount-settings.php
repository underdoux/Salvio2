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
            <?php if (strpos($setting['key'], 'discount') !== false): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($setting['key']) ?></strong>
                        <?php if ($setting['description']): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($setting['description']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if (strpos($setting['key'], 'percent') !== false): ?>
                                <div class="progress flex-grow-1 mr-2" style="height: 10px;">
                                    <div class="progress-bar" 
                                         role="progressbar" 
                                         style="width: <?= (int)$setting['value'] ?>%"
                                         aria-valuenow="<?= (int)$setting['value'] ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <span><?= htmlspecialchars($setting['value']) ?>%</span>
                            <?php elseif (strpos($setting['key'], 'amount') !== false): ?>
                                <?= CurrencyFormatter::getInstance()->format($setting['value']) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($setting['value']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($setting['warning_threshold']) && $setting['value'] > $setting['warning_threshold']): ?>
                            <div class="alert alert-warning mt-2 mb-0 p-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                High discount limit may impact profits
                            </div>
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
                            <button type="button"
                                    class="btn btn-sm btn-info"
                                    data-toggle="modal"
                                    data-target="#discountHistoryModal"
                                    data-key="<?= htmlspecialchars($setting['key']) ?>"
                                    title="View history">
                                <i class="fas fa-history"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Discount History Modal -->
<div class="modal fade" id="discountHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Discount Setting History</h5>
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

    // Handle discount history modal
    $('#discountHistoryModal').on('show.bs.modal', async function(event) {
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
});
</script>
