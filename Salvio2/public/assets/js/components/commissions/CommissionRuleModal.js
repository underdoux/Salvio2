Vue.component('commission-rule-modal', {
    data() {
        return {
            rules: [],
            loading: false,
            showAddForm: false,
            form: {
                type: 'global',
                reference_id: null,
                rate: '',
                min_amount: '',
                max_amount: '',
                active: true
            },
            selectedRule: null,
            errors: {},
            categories: [],
            products: [],
            ruleTypes: [
                { value: 'global', label: 'Global Rate' },
                { value: 'category', label: 'Category Rate' },
                { value: 'product', label: 'Product Rate' }
            ],
            searchQuery: '',
            searchResults: [],
            showSearch: false
        };
    },
    template: `
        <div class="modal modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Commission Rules</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Rules List -->
                    <div v-if="!showAddForm" class="rules-list">
                        <div class="rules-header">
                            <h3>Active Rules</h3>
                            <button class="btn btn-primary" @click="showAddForm = true">
                                <i class="fas fa-plus"></i> Add Rule
                            </button>
                        </div>

                        <!-- Global Rules -->
                        <div class="rule-section">
                            <h4>Global Rate</h4>
                            <div class="rule-cards">
                                <div v-for="rule in globalRules" 
                                     :key="rule.id"
                                     class="rule-card"
                                     :class="{ 'inactive': !rule.active }">
                                    <div class="rule-header">
                                        <span class="rule-type">Global</span>
                                        <div class="rule-actions">
                                            <button class="btn btn-icon btn-sm"
                                                    @click="toggleRule(rule)"
                                                    :title="rule.active ? 'Deactivate' : 'Activate'">
                                                <i class="fas" 
                                                   :class="rule.active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm"
                                                    @click="editRule(rule)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm btn-danger"
                                                    @click="deleteRule(rule)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="rule-content">
                                        <div class="rate">{{ rule.rate }}%</div>
                                        <div class="limits" v-if="rule.min_amount || rule.max_amount">
                                            <small>
                                                {{ formatLimits(rule.min_amount, rule.max_amount) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Category Rules -->
                        <div class="rule-section">
                            <h4>Category Rates</h4>
                            <div class="rule-cards">
                                <div v-for="rule in categoryRules" 
                                     :key="rule.id"
                                     class="rule-card"
                                     :class="{ 'inactive': !rule.active }">
                                    <div class="rule-header">
                                        <span class="rule-type">{{ rule.category_name }}</span>
                                        <div class="rule-actions">
                                            <button class="btn btn-icon btn-sm"
                                                    @click="toggleRule(rule)"
                                                    :title="rule.active ? 'Deactivate' : 'Activate'">
                                                <i class="fas" 
                                                   :class="rule.active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm"
                                                    @click="editRule(rule)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm btn-danger"
                                                    @click="deleteRule(rule)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="rule-content">
                                        <div class="rate">{{ rule.rate }}%</div>
                                        <div class="limits" v-if="rule.min_amount || rule.max_amount">
                                            <small>
                                                {{ formatLimits(rule.min_amount, rule.max_amount) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Rules -->
                        <div class="rule-section">
                            <h4>Product Rates</h4>
                            <div class="rule-cards">
                                <div v-for="rule in productRules" 
                                     :key="rule.id"
                                     class="rule-card"
                                     :class="{ 'inactive': !rule.active }">
                                    <div class="rule-header">
                                        <span class="rule-type">{{ rule.product_name }}</span>
                                        <div class="rule-actions">
                                            <button class="btn btn-icon btn-sm"
                                                    @click="toggleRule(rule)"
                                                    :title="rule.active ? 'Deactivate' : 'Activate'">
                                                <i class="fas" 
                                                   :class="rule.active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm"
                                                    @click="editRule(rule)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm btn-danger"
                                                    @click="deleteRule(rule)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="rule-content">
                                        <div class="rate">{{ rule.rate }}%</div>
                                        <div class="limits" v-if="rule.min_amount || rule.max_amount">
                                            <small>
                                                {{ formatLimits(rule.min_amount, rule.max_amount) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add/Edit Form -->
                    <form v-else @submit.prevent="saveRule" class="rule-form">
                        <h3>{{ selectedRule ? 'Edit' : 'Add' }} Commission Rule</h3>

                        <div class="form-group">
                            <label>Rule Type *</label>
                            <select v-model="form.type" required>
                                <option v-for="type in ruleTypes"
                                        :key="type.value"
                                        :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <span class="form-error" v-if="errors.type">
                                {{ errors.type }}
                            </span>
                        </div>

                        <!-- Category/Product Selection -->
                        <div v-if="form.type !== 'global'" class="form-group">
                            <label>{{ form.type === 'category' ? 'Category' : 'Product' }} *</label>
                            <div class="search-input">
                                <input type="text"
                                       v-model="searchQuery"
                                       @input="searchItems"
                                       :placeholder="'Search ' + form.type + '...'">
                                
                                <div v-if="showSearch && searchResults.length" 
                                     class="search-results">
                                    <div v-for="item in searchResults"
                                         :key="item.id"
                                         class="search-result-item"
                                         @click="selectItem(item)">
                                        {{ item.name }}
                                    </div>
                                </div>
                            </div>
                            <span class="form-error" v-if="errors.reference_id">
                                {{ errors.reference_id }}
                            </span>
                        </div>

                        <div class="form-group">
                            <label>Commission Rate (%) *</label>
                            <input type="number"
                                   v-model="form.rate"
                                   required
                                   min="0"
                                   max="100"
                                   step="0.01">
                            <span class="form-error" v-if="errors.rate">
                                {{ errors.rate }}
                            </span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Minimum Amount</label>
                                <input type="number"
                                       v-model="form.min_amount"
                                       min="0"
                                       step="0.01">
                                <span class="form-error" v-if="errors.min_amount">
                                    {{ errors.min_amount }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Maximum Amount</label>
                                <input type="number"
                                       v-model="form.max_amount"
                                       min="0"
                                       step="0.01">
                                <span class="form-error" v-if="errors.max_amount">
                                    {{ errors.max_amount }}
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="form.active">
                                Active
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="cancelEdit">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="btn btn-primary"
                                    :disabled="loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                {{ selectedRule ? 'Update' : 'Add' }} Rule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
    computed: {
        globalRules() {
            return this.rules.filter(r => r.type === 'global');
        },
        categoryRules() {
            return this.rules.filter(r => r.type === 'category');
        },
        productRules() {
            return this.rules.filter(r => r.type === 'product');
        }
    },
    methods: {
        async loadRules() {
            try {
                const response = await axios.get('/api/commission-rules');
                this.rules = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load commission rules', 'error');
            }
        },

        async loadCategories() {
            try {
                const response = await axios.get('/api/categories');
                this.categories = response.data;
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },

        searchItems: _.debounce(async function() {
            if (!this.searchQuery || this.searchQuery.length < 2) {
                this.showSearch = false;
                return;
            }

            try {
                const endpoint = this.form.type === 'category' ? 
                    '/api/categories/search' : '/api/products/search';
                
                const response = await axios.get(endpoint, {
                    params: { query: this.searchQuery }
                });
                this.searchResults = response.data;
                this.showSearch = true;
            } catch (error) {
                console.error('Search failed:', error);
            }
        }, 300),

        selectItem(item) {
            this.form.reference_id = item.id;
            this.searchQuery = item.name;
            this.showSearch = false;
        },

        editRule(rule) {
            this.selectedRule = rule;
            this.form = { ...rule };
            this.showAddForm = true;
        },

        async toggleRule(rule) {
            try {
                await axios.patch(`/api/commission-rules/${rule.id}/toggle`);
                this.loadRules();
            } catch (error) {
                this.$root.showToast('Failed to toggle rule status', 'error');
            }
        },

        async deleteRule(rule) {
            if (!confirm('Are you sure you want to delete this rule?')) return;

            try {
                await axios.delete(`/api/commission-rules/${rule.id}`);
                this.$root.showToast('Rule deleted successfully', 'success');
                this.loadRules();
            } catch (error) {
                this.$root.showToast('Failed to delete rule', 'error');
            }
        },

        async saveRule() {
            if (!this.validateForm()) return;

            this.loading = true;
            try {
                const data = { ...this.form };
                
                if (this.selectedRule) {
                    await axios.put(`/api/commission-rules/${this.selectedRule.id}`, data);
                    this.$root.showToast('Rule updated successfully', 'success');
                } else {
                    await axios.post('/api/commission-rules', data);
                    this.$root.showToast('Rule created successfully', 'success');
                }

                this.resetForm();
                this.loadRules();
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    this.$root.showToast('Failed to save rule', 'error');
                }
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.rate || this.form.rate < 0 || this.form.rate > 100) {
                this.errors.rate = 'Rate must be between 0 and 100';
                isValid = false;
            }

            if (this.form.type !== 'global' && !this.form.reference_id) {
                this.errors.reference_id = 
                    `Please select a ${this.form.type === 'category' ? 'category' : 'product'}`;
                isValid = false;
            }

            if (this.form.min_amount && this.form.max_amount && 
                parseFloat(this.form.min_amount) >= parseFloat(this.form.max_amount)) {
                this.errors.max_amount = 'Maximum amount must be greater than minimum amount';
                isValid = false;
            }

            return isValid;
        },

        cancelEdit() {
            this.resetForm();
        },

        resetForm() {
            this.form = {
                type: 'global',
                reference_id: null,
                rate: '',
                min_amount: '',
                max_amount: '',
                active: true
            };
            this.selectedRule = null;
            this.showAddForm = false;
            this.errors = {};
            this.searchQuery = '';
            this.showSearch = false;
        },

        formatLimits(min, max) {
            if (min && max) {
                return `${this.formatCurrency(min)} - ${this.formatCurrency(max)}`;
            } else if (min) {
                return `Min: ${this.formatCurrency(min)}`;
            } else if (max) {
                return `Max: ${this.formatCurrency(max)}`;
            }
            return 'No limits';
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        }
    },
    created() {
        this.loadRules();
        this.loadCategories();
    }
});
