<?php
$baseUrl = '/Salvio2/public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Settings Management - Salvio POS</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .setting-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .setting-card.dependent {
            border-left: 4px solid #007bff;
        }

        .setting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .setting-title {
            font-weight: bold;
            color: #333;
        }

        .setting-type {
            font-size: 0.8em;
            color: #666;
        }

        .setting-value {
            margin-top: 10px;
        }

        .setting-value input[type="text"],
        .setting-value input[type="number"],
        .setting-value select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .setting-dependencies {
            margin-top: 10px;
            font-size: 0.9em;
            color: #666;
        }

        .dependency-item {
            display: inline-block;
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            margin: 2px;
        }

        .actions-panel {
            position: sticky;
            bottom: 0;
            background: #fff;
            padding: 15px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .import-export {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .actions-panel {
                flex-direction: column;
                align-items: stretch;
            }

            .import-export {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
        }

        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Toast Notifications */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .toast.success {
            background: #28a745;
        }

        .toast.error {
            background: #dc3545;
        }

        .toast.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <h1>Bulk Settings Management</h1>
        
        <div class="settings-grid">
            <?php foreach ($settings as $key => $setting): ?>
            <div class="setting-card <?= !empty($setting['dependencies']) ? 'dependent' : '' ?>">
                <div class="setting-header">
                    <div class="setting-title"><?= htmlspecialchars($setting['title']) ?></div>
                    <div class="setting-type"><?= htmlspecialchars($setting['type']) ?></div>
                </div>
                
                <div class="setting-value">
                    <?php switch($setting['type']): 
                        case 'boolean': ?>
                            <select name="<?= $key ?>" data-key="<?= $key ?>">
                                <option value="1" <?= $setting['value'] ? 'selected' : '' ?>>Enabled</option>
                                <option value="0" <?= !$setting['value'] ? 'selected' : '' ?>>Disabled</option>
                            </select>
                            <?php break;
                        
                        case 'number': ?>
                            <input type="number" name="<?= $key ?>" value="<?= htmlspecialchars($setting['value']) ?>" 
                                   data-key="<?= $key ?>" <?= isset($setting['min']) ? "min=\"{$setting['min']}\"" : '' ?> 
                                   <?= isset($setting['max']) ? "max=\"{$setting['max']}\"" : '' ?>>
                            <?php break;
                        
                        default: ?>
                            <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($setting['value']) ?>" 
                                   data-key="<?= $key ?>">
                    <?php endswitch; ?>
                </div>

                <?php if (!empty($setting['dependencies'])): ?>
                <div class="setting-dependencies">
                    Dependencies:
                    <?php foreach ($setting['dependencies'] as $dep): ?>
                        <span class="dependency-item"><?= htmlspecialchars($dep) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="actions-panel">
            <div class="import-export">
                <button class="btn btn-secondary" onclick="exportSettings('json')">Export JSON</button>
                <button class="btn btn-secondary" onclick="exportSettings('csv')">Export CSV</button>
                <input type="file" id="importFile" style="display: none" accept=".json,.csv" 
                       onchange="handleImport(this)">
                <button class="btn btn-secondary" onclick="document.getElementById('importFile').click()">
                    Import Settings
                </button>
            </div>
            <button class="btn btn-primary" onclick="saveAllSettings()">Save All Changes</button>
        </div>
    </div>

    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // Show loading overlay
        function showLoading() {
            document.querySelector('.loading-overlay').style.display = 'flex';
        }

        // Hide loading overlay
        function hideLoading() {
            document.querySelector('.loading-overlay').style.display = 'none';
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type} show`;
            
            setTimeout(() => {
                toast.className = 'toast';
            }, 3000);
        }

        // Save all settings
        async function saveAllSettings() {
            showLoading();
            
            const settings = {};
            document.querySelectorAll('[data-key]').forEach(input => {
                settings[input.dataset.key] = input.value;
            });

            try {
                const response = await fetch('<?= $baseUrl ?>/settings/bulk-update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(settings)
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Settings saved successfully');
                } else {
                    showToast(result.error || 'Failed to save settings', 'error');
                }
            } catch (error) {
                showToast('An error occurred while saving settings', 'error');
            } finally {
                hideLoading();
            }
        }

        // Export settings
        async function exportSettings(format) {
            showLoading();
            
            try {
                const response = await fetch(`<?= $baseUrl ?>/settings/export?format=${format}`);
                const blob = await response.blob();
                
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `settings.${format}`;
                a.click();
                
                window.URL.revokeObjectURL(url);
                showToast(`Settings exported as ${format.toUpperCase()}`);
            } catch (error) {
                showToast('Failed to export settings', 'error');
            } finally {
                hideLoading();
            }
        }

        // Handle import
        async function handleImport(input) {
            if (!input.files.length) return;
            
            const file = input.files[0];
            const format = file.name.split('.').pop().toLowerCase();
            
            if (!['json', 'csv'].includes(format)) {
                showToast('Invalid file format. Please use JSON or CSV.', 'error');
                return;
            }

            showLoading();
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('format', format);

                const response = await fetch('<?= $baseUrl ?>/settings/import', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Settings imported successfully');
                    location.reload(); // Reload to show new settings
                } else {
                    showToast(result.error || 'Failed to import settings', 'error');
                }
            } catch (error) {
                showToast('An error occurred while importing settings', 'error');
            } finally {
                hideLoading();
                input.value = ''; // Reset file input
            }
        }
    </script>
</body>
</html>
