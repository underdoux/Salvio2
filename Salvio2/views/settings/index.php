<?php
$pageTitle = 'System Settings';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="settings-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>System Settings</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Settings</span>
            </nav>
        </div>
        
        <div class="header-right">
            <button class="btn btn-primary" @click="saveChanges" :disabled="!hasChanges || isSaving">
                <i class="fas" :class="isSaving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>
    </div>

    <!-- Settings Navigation -->
    <div class="settings-layout">
        <div class="settings-nav">
            <div class="nav-group" v-for="(group, category) in settings" :key="category">
                <div class="nav-header">{{ formatCategory(category) }}</div>
                <div class="nav-items">
                    <a href="#" 
                       :class="{ active: activeCategory === category }"
                       @click.prevent="activeCategory = category">
                        {{ formatCategory(category) }}
                        <span v-if="hasChangesInCategory(category)" class="changes-badge">*</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="settings-content">
            <!-- Category Header -->
            <div class="category-header">
                <h2>{{ formatCategory(activeCategory) }}</h2>
                <p v-if="categoryDescription" class="category-description">
                    {{ categoryDescription }}
                </p>
            </div>

            <!-- Settings Form -->
            <div class="settings-form" v-if="activeCategory">
                <div class="setting-group" v-for="(setting, name) in settings[activeCategory]" :key="name">
                    <label :for="name" class="setting-label">
                        {{ formatSettingName(name) }}
                        <span v-if="setting.requires_restart" class="restart-badge" 
                              title="Requires system restart">⟳</span>
                    </label>
                    
                    <div class="setting-input">
                        <!-- String Input -->
                        <input v-if="setting.type === 'string' && !isEmail(setting) && !isPassword(setting)"
                               type="text"
                               :id="name"
                               v-model="formData[activeCategory][name]"
                               class="form-control">

                        <!-- Email Input -->
                        <input v-if="setting.type === 'string' && isEmail(setting)"
                               type="email"
                               :id="name"
                               v-model="formData[activeCategory][name]"
                               class="form-control">

                        <!-- Password Input -->
                        <div v-if="setting.type === 'string' && isPassword(setting)" class="password-input">
                            <input type="password"
                                   :id="name"
                                   v-model="formData[activeCategory][name]"
                                   class="form-control">
                            <button class="btn btn-icon" @click="togglePassword(name)">
                                <i class="fas" :class="showPassword[name] ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>

                        <!-- Number Input -->
                        <input v-if="setting.type === 'integer' || setting.type === 'float'"
                               type="number"
                               :id="name"
                               v-model.number="formData[activeCategory][name]"
                               :step="setting.type === 'float' ? '0.01' : '1'"
                               class="form-control">

                        <!-- Boolean Input -->
                        <div v-if="setting.type === 'boolean'" class="toggle-switch">
                            <input type="checkbox"
                                   :id="name"
                                   v-model="formData[activeCategory][name]">
                            <label :for="name"></label>
                        </div>

                        <!-- JSON/Array Input -->
                        <div v-if="setting.type === 'json' || setting.type === 'array'" class="json-editor">
                            <textarea :id="name"
                                    v-model="formData[activeCategory][name]"
                                    class="form-control"
                                    rows="4"
                                    @input="validateJson($event, activeCategory, name)"></textarea>
                            <div v-if="jsonErrors[`${activeCategory}.${name}`]" class="error-message">
                                {{ jsonErrors[`${activeCategory}.${name}`] }}
                            </div>
                        </div>
                    </div>

                    <div class="setting-description" v-if="setting.description">
                        {{ setting.description }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Log -->
        <div class="audit-log" v-if="showAuditLog">
            <div class="log-header">
                <h3>Recent Changes</h3>
                <button class="btn btn-text" @click="showAuditLog = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="log-content">
                <div v-for="log in auditLogs" :key="log.id" class="log-entry">
                    <div class="log-info">
                        <span class="log-user">{{ log.changed_by }}</span>
                        <span class="log-time">{{ formatDate(log.created_at) }}</span>
                    </div>
                    <div class="log-details">
                        Changed <strong>{{ formatSettingName(log.name) }}</strong>
                        from <code>{{ log.old_value }}</code>
                        to <code>{{ log.new_value }}</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Restart Required Modal -->
    <div v-if="showRestartModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>System Restart Required</h3>
                <button class="close-btn" @click="showRestartModal = false">×</button>
            </div>
            <div class="modal-body">
                <p>Some of the changes you made require a system restart to take effect:</p>
                <ul>
                    <li v-for="setting in changedRestartSettings" :key="setting">
                        {{ formatSettingName(setting) }}
                    </li>
                </ul>
                <p>Would you like to restart the system now?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="showRestartModal = false">
                    Later
                </button>
                <button class="btn btn-primary" @click="restartSystem">
                    Restart Now
                </button>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#settings-page',
    data: {
        settings: <?= json_encode($settings) ?>,
        formData: {},
        originalData: {},
        activeCategory: Object.keys(<?= json_encode($settings) ?>)[0],
        isSaving: false,
        showRestartModal: false,
        showAuditLog: false,
        auditLogs: [],
        jsonErrors: {},
        showPassword: {},
        categoryDescriptions: {
            general: 'Basic system configuration settings',
            payment: 'Payment processing and installment settings',
            commission: 'Sales commission rules and calculations',
            stock: 'Inventory management settings',
            notification: 'Email and WhatsApp notification settings',
            security: 'Security and authentication settings',
            report: 'Report generation settings',
            api: 'API access and integration settings'
        }
    },
    computed: {
        hasChanges() {
            return JSON.stringify(this.formData) !== JSON.stringify(this.originalData);
        },
        categoryDescription() {
            return this.categoryDescriptions[this.activeCategory];
        },
        changedRestartSettings() {
            const changed = [];
            for (const category in this.formData) {
                for (const name in this.formData[category]) {
                    if (this.settings[category][name].requires_restart &&
                        this.formData[category][name] !== this.originalData[category][name]) {
                        changed.push(name);
                    }
                }
            }
            return changed;
        }
    },
    created() {
        this.initializeForm();
        this.loadAuditLog();
    },
    methods: {
        initializeForm() {
            this.formData = {};
            this.originalData = {};
            
            for (const category in this.settings) {
                this.formData[category] = {};
                this.originalData[category] = {};
                
                for (const name in this.settings[category]) {
                    const value = this.settings[category][name].value;
                    this.formData[category][name] = value;
                    this.originalData[category][name] = value;
                }
            }
        },

        async loadAuditLog() {
            try {
                const response = await axios.get('/api/settings/audit-log');
                this.auditLogs = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load audit log', 'error');
            }
        },

        async saveChanges() {
            if (!this.hasChanges) return;

            this.isSaving = true;
            try {
                await axios.post('/api/settings', this.getChangedSettings());
                this.$root.showToast('Settings saved successfully', 'success');
                
                // Update original data
                this.originalData = JSON.parse(JSON.stringify(this.formData));
                
                // Check if restart is required
                if (this.changedRestartSettings.length > 0) {
                    this.showRestartModal = true;
                }
                
                // Refresh audit log
                this.loadAuditLog();
                
            } catch (error) {
                this.$root.showToast(
                    error.response?.data?.message || 'Failed to save settings',
                    'error'
                );
            } finally {
                this.isSaving = false;
            }
        },

        getChangedSettings() {
            const changed = {};
            
            for (const category in this.formData) {
                for (const name in this.formData[category]) {
                    if (this.formData[category][name] !== this.originalData[category][name]) {
                        if (!changed[category]) changed[category] = {};
                        changed[category][name] = this.formData[category][name];
                    }
                }
            }
            
            return changed;
        },

        hasChangesInCategory(category) {
            return JSON.stringify(this.formData[category]) !== 
                   JSON.stringify(this.originalData[category]);
        },

        validateJson(event, category, name) {
            const key = `${category}.${name}`;
            try {
                JSON.parse(event.target.value);
                this.jsonErrors[key] = null;
            } catch (e) {
                this.jsonErrors[key] = 'Invalid JSON format';
            }
        },

        formatCategory(category) {
            return category.split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        formatSettingName(name) {
            return name.split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        formatDate(date) {
            return new Date(date).toLocaleString();
        },

        isEmail(setting) {
            return setting.validation_rules && 
                   JSON.parse(setting.validation_rules).type === 'email';
        },

        isPassword(setting) {
            return setting.name.toLowerCase().includes('password');
        },

        togglePassword(name) {
            this.showPassword[name] = !this.showPassword[name];
            const input = document.getElementById(name);
            input.type = this.showPassword[name] ? 'text' : 'password';
        },

        async restartSystem() {
            try {
                await axios.post('/api/system/restart');
                this.$root.showToast('System restart initiated', 'success');
                this.showRestartModal = false;
            } catch (error) {
                this.$root.showToast('Failed to restart system', 'error');
            }
        }
    }
});
</script>
