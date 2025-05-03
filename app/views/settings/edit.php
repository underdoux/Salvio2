<?php $this->layout('layouts/main', ['title' => 'Edit Setting']) ?>

<div class="edit-setting-page">
    <div class="page-header">
        <div class="header-content">
            <h2>
                Edit Setting: <?= htmlspecialchars($setting['key']) ?>
                <i class="fas fa-info-circle text-info" 
                   data-toggle="tooltip" 
                   data-html="true"
                   title="<?= htmlspecialchars($setting['description'] ?? 'No description available') ?>"></i>
            </h2>
            <span class="setting-type badge badge-info">
                <?= ucfirst($setting['type']) ?>
            </span>
        </div>
        <div class="actions">
            <a href="/settings" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" 
                  action="/settings/update/<?= urlencode($setting['key']) ?>" 
                  id="settingForm" 
                  class="setting-form needs-validation" 
                  novalidate>
                
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="key">Setting Key</label>
                            <div class="input-group">
                                <input type="text" 
                                       id="key" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($setting['key']) ?>" 
                                       readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock" title="Key cannot be modified"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select id="type" 
                                    name="type" 
                                    class="form-control" 
                                    required 
                                    onchange="updateValueField()">
                                <option value="string" <?= $setting['type'] === 'string' ? 'selected' : '' ?>>String</option>
                                <option value="int" <?= $setting['type'] === 'int' ? 'selected' : '' ?>>Integer</option>
                                <option value="float" <?= $setting['type'] === 'float' ? 'selected' : '' ?>>Float</option>
                                <option value="bool" <?= $setting['type'] === 'bool' ? 'selected' : '' ?>>Boolean</option>
                                <option value="json" <?= $setting['type'] === 'json' ? 'selected' : '' ?>>JSON</option>
                            </select>
                            <div class="invalid-feedback">Please select a type</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="value">Value</label>
                    <div class="value-container">
                        <?php if (strpos($setting['key'], 'password') !== false): ?>
                            <input type="password" 
                                   id="value" 
                                   name="value" 
                                   class="form-control" 
                                   required 
                                   minlength="8"
                                   autocomplete="new-password">
                            <small class="form-text text-muted">
                                Minimum 8 characters. Leave empty to keep current value.
                            </small>
                        <?php elseif ($setting['type'] === 'json'): ?>
                            <div class="code-editor-wrapper">
                                <textarea id="value" 
                                        name="value" 
                                        class="form-control code-editor" 
                                        rows="8" 
                                        required><?= htmlspecialchars($setting['value']) ?></textarea>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-secondary format-json"
                                        onclick="formatJson()">
                                    <i class="fas fa-code"></i> Format JSON
                                </button>
                            </div>
                        <?php elseif ($setting['type'] === 'bool'): ?>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="value" 
                                       name="value" 
                                       value="1" 
                                       <?= $setting['value'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="value">
                                    Enable/Disable
                                </label>
                            </div>
                        <?php else: ?>
                            <input type="text" 
                                   id="value" 
                                   name="value" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($setting['value']) ?>" 
                                   required>
                        <?php endif; ?>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" 
                            name="description" 
                            class="form-control" 
                            rows="3"><?= htmlspecialchars($setting['description']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="/settings" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php if (strpos($setting['key'], 'smtp') === 0): ?>
                        <button type="button" 
                                class="btn btn-info test-smtp"
                                onclick="testSmtp()">
                            <i class="fas fa-paper-plane"></i> Test SMTP
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Setting History -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Setting History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Changed By</th>
                        </tr>
                    </thead>
                    <tbody id="historyTable">
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.edit-setting-page {
    padding: 20px;
    max-width: 1000px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.setting-type {
    font-size: 0.9rem;
    padding: 5px 10px;
}

.card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.code-editor-wrapper {
    position: relative;
}

.code-editor {
    font-family: monospace;
    resize: vertical;
}

.format-json {
    position: absolute;
    top: 5px;
    right: 5px;
}

.custom-switch {
    padding-left: 2.5rem;
}

.value-container {
    position: relative;
}

.test-smtp {
    margin-left: auto;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Form validation
    const form = document.getElementById('settingForm');
    form.addEventListener('submit', function(e) {
        if (!validateSetting()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Load setting history
    loadHistory();

    // Initialize code editor if needed
    if (document.querySelector('.code-editor')) {
        initializeCodeEditor();
    }
});

function validateSetting() {
    const type = document.getElementById('type').value;
    const value = document.getElementById('value').value.trim();
    const key = document.getElementById('key').value;
    let isValid = true;
    
    // Clear previous errors
    const valueField = document.getElementById('value');
    valueField.classList.remove('is-invalid');
    valueField.nextElementSibling.textContent = '';

    // Type-specific validation
    switch (type) {
        case 'int':
            if (!/^\d+$/.test(value)) {
                showError('Please enter a valid integer');
                isValid = false;
            }
            break;
        case 'float':
            if (!/^\d*\.?\d+$/.test(value)) {
                showError('Please enter a valid number');
                isValid = false;
            }
            break;
        case 'json':
            try {
                JSON.parse(value);
            } catch (e) {
                showError('Please enter valid JSON');
                isValid = false;
            }
            break;
    }

    // Sensitive settings validation
    if (key.includes('password') && value.length > 0 && value.length < 8) {
        showError('Password must be at least 8 characters long');
        isValid = false;
    }

    return isValid;
}

function showError(message) {
    const valueField = document.getElementById('value');
    valueField.classList.add('is-invalid');
    valueField.nextElementSibling.textContent = message;
}

function updateValueField() {
    const type = document.getElementById('type').value;
    const currentValue = document.getElementById('value').value;
    const container = document.querySelector('.value-container');
    
    let html = '';
    switch (type) {
        case 'bool':
            html = `
                <div class="custom-control custom-switch">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="value" 
                           name="value" 
                           value="1" 
                           ${currentValue === 'true' ? 'checked' : ''}>
                    <label class="custom-control-label" for="value">
                        Enable/Disable
                    </label>
                </div>
            `;
            break;
        case 'json':
            html = `
                <div class="code-editor-wrapper">
                    <textarea id="value" 
                            name="value" 
                            class="form-control code-editor" 
                            rows="8" 
                            required>${currentValue}</textarea>
                    <button type="button" 
                            class="btn btn-sm btn-outline-secondary format-json"
                            onclick="formatJson()">
                        <i class="fas fa-code"></i> Format JSON
                    </button>
                </div>
            `;
            break;
        default:
            html = `
                <input type="text" 
                       id="value" 
                       name="value" 
                       class="form-control" 
                       value="${currentValue}" 
                       required>
            `;
    }
    
    container.innerHTML = html + '<div class="invalid-feedback"></div>';
    
    if (type === 'json') {
        initializeCodeEditor();
    }
}

function formatJson() {
    const editor = document.querySelector('.code-editor');
    try {
        const formatted = JSON.stringify(JSON.parse(editor.value), null, 2);
        editor.value = formatted;
    } catch (e) {
        showError('Invalid JSON');
    }
}

function initializeCodeEditor() {
    const editor = document.querySelector('.code-editor');
    editor.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
            this.selectionStart = this.selectionEnd = start + 4;
        }
    });
}

async function loadHistory() {
    const key = document.getElementById('key').value;
    const tbody = document.getElementById('historyTable');
    
    try {
        const response = await fetch(`/settings/history/${encodeURIComponent(key)}`, {
            headers: {
                'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
            }
        });
        const history = await response.json();
        
        tbody.innerHTML = history.length ? history.map(entry => `
            <tr>
                <td>${new Date(entry.created_at).toLocaleString()}</td>
                <td>${entry.old_value}</td>
                <td>${entry.new_value}</td>
                <td>${entry.user_name}</td>
            </tr>
        `).join('') : '<tr><td colspan="4" class="text-center">No history available</td></tr>';
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading history</td></tr>';
    }
}

async function testSmtp() {
    const button = document.querySelector('.test-smtp');
    const originalHtml = button.innerHTML;
    
    try {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        
        const response = await fetch('/settings/test-smtp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
            }
        });
        
        const result = await response.json();
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show`;
        alert.innerHTML = `
            ${result.message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        document.querySelector('.card-body').insertBefore(alert, document.querySelector('form'));
        
    } catch (error) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            Error testing SMTP connection
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        document.querySelector('.card-body').insertBefore(alert, document.querySelector('form'));
    } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}
</script>
