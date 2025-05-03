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
            <?php if (strpos($setting['key'], 'currency') === 0): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($setting['key']) ?></strong>
                        <?php if ($setting['description']): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($setting['description']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (is_array($setting['value'])): ?>
                            <pre class="mb-0"><?= htmlspecialchars(json_encode($setting['value'], JSON_PRETTY_PRINT)) ?></pre>
                        <?php else: ?>
                            <?= htmlspecialchars($setting['value']) ?>
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
