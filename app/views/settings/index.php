<?php $this->layout('layouts/main', ['title' => 'System Settings']) ?>

<div class="settings-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h2>System Settings</h2>
            <div class="search-box">
                <input type="text" 
                       id="settingSearch" 
                       class="form-control" 
                       placeholder="Search settings... (Press '/' to focus)"
                       aria-label="Search settings">
                <i class="fas fa-search"></i>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-outline-secondary" id="toggleCategories">
                <i class="fas fa-th-large"></i> Toggle Categories
            </button>
            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#helpModal">
                <i class="fas fa-question-circle"></i> Help
            </button>
        </div>
    </div>

    <!-- Settings Categories -->
    <div class="settings-container">
        <div class="settings-sidebar" id="settingsSidebar">
            <div class="category-list">
                <a href="#currency" class="category-item active" data-category="currency">
                    <i class="fas fa-dollar-sign"></i> Currency & Pricing
                    <span class="badge badge-primary">4</span>
                </a>
                <a href="#product" class="category-item" data-category="product">
                    <i class="fas fa-box"></i> Product Management
                    <span class="badge badge-primary">5</span>
                </a>
                <a href="#inventory" class="category-item" data-category="inventory">
                    <i class="fas fa-warehouse"></i> Inventory Control
                    <span class="badge badge-primary">5</span>
                </a>
                <a href="#quality" class="category-item" data-category="quality">
                    <i class="fas fa-check-circle"></i> Quality Control
                    <span class="badge badge-primary">4</span>
                </a>
                <a href="#customer" class="category-item" data-category="customer">
                    <i class="fas fa-users"></i> Customer Management
                    <span class="badge badge-primary">4</span>
                </a>
                <a href="#document" class="category-item" data-category="document">
                    <i class="fas fa-file-alt"></i> Document Settings
                    <span class="badge badge-primary">4</span>
                </a>
                <a href="#compliance" class="category-item" data-category="compliance">
                    <i class="fas fa-shield-alt"></i> Compliance
                    <span class="badge badge-primary">4</span>
                </a>
            </div>
        </div>

        <div class="settings-content">
            <!-- Currency Settings -->
            <div class="settings-section" id="currency">
                <h3>Currency & Pricing Settings</h3>
                <div class="settings-grid">
                    <?php foreach ($settings['currency'] as $setting): ?>
                    <div class="setting-card" data-setting="<?= htmlspecialchars($setting['key']) ?>">
                        <div class="setting-header">
                            <h4><?= htmlspecialchars($setting['name']) ?></h4>
                            <?php if ($setting['type'] === 'bool'): ?>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="<?= htmlspecialchars($setting['key']) ?>"
                                       <?= $setting['value'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="<?= htmlspecialchars($setting['key']) ?>"></label>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="setting-body">
                            <p class="setting-description"><?= htmlspecialchars($setting['description']) ?></p>
                            <?php if ($setting['type'] !== 'bool'): ?>
                            <div class="setting-value">
                                <?php if ($setting['type'] === 'json'): ?>
                                <pre><code><?= htmlspecialchars(json_encode(json_decode($setting['value']), JSON_PRETTY_PRINT)) ?></code></pre>
                                <?php else: ?>
                                <span><?= htmlspecialchars($setting['value']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="setting-footer">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-primary edit-setting"
                                    data-key="<?= htmlspecialchars($setting['key']) ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary view-history"
                                    data-key="<?= htmlspecialchars($setting['key']) ?>">
                                <i class="fas fa-history"></i> History
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Other setting sections follow the same pattern -->
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">Settings Help</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Keyboard Shortcuts</h6>
                <div class="shortcuts-grid">
                    <div class="shortcut">
                        <kbd>/</kbd>
                        <span>Focus search</span>
                    </div>
                    <div class="shortcut">
                        <kbd>1</kbd> - <kbd>7</kbd>
                        <span>Switch categories</span>
                    </div>
                    <div class="shortcut">
                        <kbd>?</kbd>
                        <span>Show this help</span>
                    </div>
                    <div class="shortcut">
                        <kbd>Esc</kbd>
                        <span>Close modals</span>
                    </div>
                </div>

                <h6 class="mt-4">Setting Types</h6>
                <ul class="setting-types">
                    <li><i class="fas fa-toggle-on"></i> Boolean - Simple on/off settings</li>
                    <li><i class="fas fa-font"></i> Text - Basic text values</li>
                    <li><i class="fas fa-hashtag"></i> Number - Numeric values with validation</li>
                    <li><i class="fas fa-code"></i> JSON - Complex structured data</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.settings-page {
    padding: 20px;
    max-width: 1400px;
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
    gap: 20px;
}

.search-box {
    position: relative;
    width: 300px;
}

.search-box i {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.settings-container {
    display: flex;
    gap: 30px;
    min-height: calc(100vh - 200px);
}

.settings-sidebar {
    width: 250px;
    flex-shrink: 0;
}

.category-list {
    position: sticky;
    top: 20px;
}

.category-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #495057;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.2s;
}

.category-item:hover {
    background: #f8f9fa;
    color: #0056b3;
    text-decoration: none;
}

.category-item.active {
    background: #e9ecef;
    color: #0056b3;
    font-weight: 500;
}

.category-item i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.settings-content {
    flex-grow: 1;
}

.settings-section {
    margin-bottom: 40px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.setting-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
}

.setting-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.setting-header h4 {
    margin: 0;
    font-size: 1.1rem;
    color: #212529;
}

.setting-body {
    margin-bottom: 15px;
}

.setting-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.setting-value {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9rem;
}

.setting-value pre {
    margin: 0;
}

.setting-footer {
    display: flex;
    gap: 10px;
}

.shortcuts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.shortcut {
    display: flex;
    align-items: center;
    gap: 10px;
}

.setting-types {
    list-style: none;
    padding: 0;
    margin: 10px 0;
}

.setting-types li {
    margin-bottom: 8px;
}

.setting-types i {
    width: 20px;
    text-align: center;
    margin-right: 10px;
}

@media (max-width: 768px) {
    .settings-container {
        flex-direction: column;
    }

    .settings-sidebar {
        width: 100%;
    }

    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('settingSearch');
    const settingCards = document.querySelectorAll('.setting-card');

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        settingCards.forEach(card => {
            const settingName = card.querySelector('h4').textContent.toLowerCase();
            const settingDesc = card.querySelector('.setting-description').textContent.toLowerCase();
            
            if (settingName.includes(searchTerm) || settingDesc.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement !== searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
        if (e.key === '?') {
            e.preventDefault();
            $('#helpModal').modal('show');
        }
        if (e.key >= '1' && e.key <= '7') {
            e.preventDefault();
            document.querySelectorAll('.category-item')[e.key - 1].click();
        }
    });

    // Category navigation
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active state
            document.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Scroll to section
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Toggle sidebar on mobile
    document.getElementById('toggleCategories').addEventListener('click', function() {
        const sidebar = document.getElementById('settingsSidebar');
        sidebar.style.display = sidebar.style.display === 'none' ? '' : 'none';
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle boolean settings
    document.querySelectorAll('.custom-control-input').forEach(input => {
        input.addEventListener('change', function() {
            const key = this.id;
            const value = this.checked;
            
            // Show loading state
            const label = this.nextElementSibling;
            const originalContent = label.innerHTML;
            label.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Update setting
            fetch('/settings/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ key, value })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Setting updated successfully');
                } else {
                    showToast('error', data.message);
                    this.checked = !value; // Revert change
                }
            })
            .catch(error => {
                showToast('error', 'Failed to update setting');
                this.checked = !value; // Revert change
            })
            .finally(() => {
                label.innerHTML = originalContent;
            });
        });
    });
});

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>
