<?php
$pageTitle = 'API Management';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="api-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>API Management</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">API</span>
            </nav>
        </div>
        
        <div class="header-right">
            <button class="btn btn-primary" @click="showGenerateKeyModal">
                <i class="fas fa-plus"></i> Generate API Key
            </button>
            <button class="btn btn-secondary" @click="showCreateWebhookModal">
                <i class="fas fa-plug"></i> Add Webhook
            </button>
        </div>
    </div>

    <!-- API Keys Section -->
    <div class="section">
        <div class="section-header">
            <h2>API Keys</h2>
            <div class="section-actions">
                <button class="btn btn-text" @click="refreshKeys">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Scopes</th>
                        <th>Last Used</th>
                        <th>Requests</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="key in apiKeys" :key="key.id">
                        <td>{{ key.name }}</td>
                        <td>
                            <span class="badge" :class="key.is_active ? 'badge-success' : 'badge-danger'">
                                {{ key.is_active ? 'Active' : 'Revoked' }}
                            </span>
                        </td>
                        <td>
                            <div class="scope-tags">
                                <span v-for="scope in JSON.parse(key.scopes)" 
                                      :key="scope" 
                                      class="scope-tag">
                                    {{ scope }}
                                </span>
                            </div>
                        </td>
                        <td>{{ formatDate(key.last_used_at) }}</td>
                        <td>{{ key.total_requests }}</td>
                        <td>{{ formatDate(key.expires_at) }}</td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-icon" 
                                        @click="viewKeyDetails(key)"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-icon" 
                                        v-if="key.is_active"
                                        @click="revokeKey(key.id)"
                                        title="Revoke Key">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Webhooks Section -->
    <div class="section">
        <div class="section-header">
            <h2>Webhooks</h2>
            <div class="section-actions">
                <button class="btn btn-text" @click="refreshWebhooks">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Events</th>
                        <th>Status</th>
                        <th>Last Triggered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="webhook in webhooks" :key="webhook.id">
                        <td>{{ webhook.name }}</td>
                        <td class="url-cell">{{ webhook.url }}</td>
                        <td>
                            <div class="event-tags">
                                <span v-for="event in JSON.parse(webhook.events)" 
                                      :key="event" 
                                      class="event-tag">
                                    {{ event }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge" 
                                  :class="webhook.is_active ? 'badge-success' : 'badge-danger'">
                                {{ webhook.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ formatDate(webhook.last_triggered_at) }}</td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-icon" 
                                        @click="testWebhook(webhook.id)"
                                        title="Test Webhook">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="btn btn-icon" 
                                        @click="toggleWebhook(webhook)"
                                        :title="webhook.is_active ? 'Disable' : 'Enable'">
                                    <i class="fas" 
                                       :class="webhook.is_active ? 'fa-toggle-on' : 'fa-toggle-off'">
                                    </i>
                                </button>
                                <button class="btn btn-icon" 
                                        @click="deleteWebhook(webhook.id)"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Generate API Key Modal -->
    <div v-if="showKeyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate API Key</h3>
                <button class="close-btn" @click="showKeyModal = false">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Key Name</label>
                    <input type="text" v-model="newKey.name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Scopes</label>
                    <div class="scope-checkboxes">
                        <label v-for="scope in availableScopes" :key="scope">
                            <input type="checkbox" 
                                   v-model="newKey.scopes" 
                                   :value="scope">
                            {{ scope }}
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Expires In (Days)</label>
                    <input type="number" 
                           v-model="newKey.expires_days" 
                           class="form-control"
                           min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="showKeyModal = false">
                    Cancel
                </button>
                <button class="btn btn-primary" @click="generateKey">
                    Generate
                </button>
            </div>
        </div>
    </div>

    <!-- Create Webhook Modal -->
    <div v-if="showWebhookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Webhook</h3>
                <button class="close-btn" @click="showWebhookModal = false">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Webhook Name</label>
                    <input type="text" v-model="newWebhook.name" class="form-control">
                </div>
                <div class="form-group">
                    <label>URL</label>
                    <input type="url" v-model="newWebhook.url" class="form-control">
                </div>
                <div class="form-group">
                    <label>Events</label>
                    <div class="event-checkboxes">
                        <label v-for="event in availableEvents" :key="event">
                            <input type="checkbox" 
                                   v-model="newWebhook.events" 
                                   :value="event">
                            {{ event }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="showWebhookModal = false">
                    Cancel
                </button>
                <button class="btn btn-primary" @click="createWebhook">
                    Create
                </button>
            </div>
        </div>
    </div>

    <!-- Key Details Modal -->
    <div v-if="showDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>API Key Details</h3>
                <button class="close-btn" @click="showDetailsModal = false">×</button>
            </div>
            <div class="modal-body">
                <div v-if="selectedKey">
                    <div class="detail-group">
                        <label>Name:</label>
                        <span>{{ selectedKey.name }}</span>
                    </div>
                    <div class="detail-group">
                        <label>Created:</label>
                        <span>{{ formatDate(selectedKey.created_at) }}</span>
                    </div>
                    <div class="detail-group">
                        <label>Last Used:</label>
                        <span>{{ formatDate(selectedKey.last_used_at) }}</span>
                    </div>
                    <div class="detail-group">
                        <label>Total Requests:</label>
                        <span>{{ selectedKey.total_requests }}</span>
                    </div>
                    <div class="detail-group">
                        <label>Status:</label>
                        <span class="badge" 
                              :class="selectedKey.is_active ? 'badge-success' : 'badge-danger'">
                            {{ selectedKey.is_active ? 'Active' : 'Revoked' }}
                        </span>
                    </div>
                    <div class="detail-group">
                        <label>Scopes:</label>
                        <div class="scope-tags">
                            <span v-for="scope in JSON.parse(selectedKey.scopes)" 
                                  :key="scope" 
                                  class="scope-tag">
                                {{ scope }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#api-page',
    data: {
        apiKeys: <?= json_encode($apiKeys) ?>,
        webhooks: <?= json_encode($webhooks) ?>,
        showKeyModal: false,
        showWebhookModal: false,
        showDetailsModal: false,
        selectedKey: null,
        newKey: {
            name: '',
            scopes: [],
            expires_days: 365
        },
        newWebhook: {
            name: '',
            url: '',
            events: []
        },
        availableScopes: [
            'read:products',
            'write:products',
            'read:orders',
            'write:orders',
            'read:customers',
            'write:customers',
            'read:reports',
            'read:insights'
        ],
        availableEvents: [
            'order.created',
            'order.updated',
            'order.completed',
            'product.created',
            'product.updated',
            'product.deleted',
            'stock.low',
            'payment.received',
            'payment.failed'
        ]
    },
    methods: {
        async refreshKeys() {
            try {
                const response = await axios.get('/api/keys');
                this.apiKeys = response.data;
            } catch (error) {
                this.$root.showToast('Failed to refresh API keys', 'error');
            }
        },

        async refreshWebhooks() {
            try {
                const response = await axios.get('/api/webhooks');
                this.webhooks = response.data;
            } catch (error) {
                this.$root.showToast('Failed to refresh webhooks', 'error');
            }
        },

        showGenerateKeyModal() {
            this.newKey = {
                name: '',
                scopes: [],
                expires_days: 365
            };
            this.showKeyModal = true;
        },

        async generateKey() {
            try {
                const response = await axios.post('/api/keys/generate', this.newKey);
                this.apiKeys.unshift(response.data.data);
                this.showKeyModal = false;
                this.$root.showToast('API key generated successfully', 'success');
            } catch (error) {
                this.$root.showToast(
                    error.response?.data?.message || 'Failed to generate API key',
                    'error'
                );
            }
        },

        async revokeKey(keyId) {
            if (!confirm('Are you sure you want to revoke this API key?')) {
                return;
            }

            try {
                await axios.post('/api/keys/revoke', { key_id: keyId });
                const key = this.apiKeys.find(k => k.id === keyId);
                if (key) {
                    key.is_active = false;
                }
                this.$root.showToast('API key revoked successfully', 'success');
            } catch (error) {
                this.$root.showToast('Failed to revoke API key', 'error');
            }
        },

        showCreateWebhookModal() {
            this.newWebhook = {
                name: '',
                url: '',
                events: []
            };
            this.showWebhookModal = true;
        },

        async createWebhook() {
            try {
                const response = await axios.post('/api/webhooks', this.newWebhook);
                this.webhooks.unshift(response.data.data);
                this.showWebhookModal = false;
                this.$root.showToast('Webhook created successfully', 'success');
            } catch (error) {
                this.$root.showToast(
                    error.response?.data?.message || 'Failed to create webhook',
                    'error'
                );
            }
        },

        async testWebhook(webhookId) {
            try {
                await axios.post('/api/webhooks/test', { webhook_id: webhookId });
                this.$root.showToast('Test webhook triggered successfully', 'success');
            } catch (error) {
                this.$root.showToast('Failed to trigger test webhook', 'error');
            }
        },

        async toggleWebhook(webhook) {
            try {
                await axios.post('/api/webhooks/toggle', { 
                    webhook_id: webhook.id,
                    active: !webhook.is_active
                });
                webhook.is_active = !webhook.is_active;
                this.$root.showToast('Webhook status updated successfully', 'success');
            } catch (error) {
                this.$root.showToast('Failed to update webhook status', 'error');
            }
        },

        async deleteWebhook(webhookId) {
            if (!confirm('Are you sure you want to delete this webhook?')) {
                return;
            }

            try {
                await axios.delete(`/api/webhooks/${webhookId}`);
                this.webhooks = this.webhooks.filter(w => w.id !== webhookId);
                this.$root.showToast('Webhook deleted successfully', 'success');
            } catch (error) {
                this.$root.showToast('Failed to delete webhook', 'error');
            }
        },

        viewKeyDetails(key) {
            this.selectedKey = key;
            this.showDetailsModal = true;
        },

        formatDate(date) {
            if (!date) return 'Never';
            return new Date(date).toLocaleString();
        }
    }
});
</script>
