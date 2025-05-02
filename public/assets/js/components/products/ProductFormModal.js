Vue.component('product-form-modal', {
    props: {
        product: {
            type: Object,
            default: null
        },
        categories: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            form: {
                name: '',
                category_id: '',
                price: '',
                cost_price: '',
                min_stock: 0,
                by_order: false,
                description: '',
                bpom_code: ''
            },
            errors: {},
            loading: false,
            bpomSearchResults: [],
            showBpomSearch: false
        };
    },
    created() {
        if (this.product) {
            this.form = { ...this.product };
        }
    },
    methods: {
        searchBPOM: _.debounce(function() {
            if (!this.form.name || this.form.name.length < 3) {
                this.showBpomSearch = false;
                return;
            }

            axios.get('/api/bpom/search', {
                params: { query: this.form.name }
            })
            .then(response => {
                this.bpomSearchResults = response.data;
                this.showBpomSearch = this.bpomSearchResults.length > 0;
            })
            .catch(error => {
                console.error('BPOM search failed:', error);
            });
        }, 300),

        applyBPOMData(bpomData) {
            this.form.name = bpomData.product_name;
            this.form.bpom_code = bpomData.registration_no;
            
            // Find matching category or create new one
            const category = this.categories.find(
                c => c.name.toLowerCase() === bpomData.category_name.toLowerCase()
            );
            if (category) {
                this.form.category_id = category.id;
            }
            
            this.showBpomSearch = false;
        },

        saveProduct() {
            this.loading = true;
            this.errors = {};

            // Validate form
            if (!this.validateForm()) {
                this.loading = false;
                return;
            }

            // Format data
            const productData = {
                ...this.form,
                price: parseFloat(this.form.price),
                cost_price: parseFloat(this.form.cost_price),
                min_stock: parseInt(this.form.min_stock)
            };

            this.$emit('save', productData);
            this.loading = false;
        },

        validateForm() {
            let isValid = true;

            if (!this.form.name) {
                this.errors.name = 'Product name is required';
                isValid = false;
            }

            if (!this.form.category_id) {
                this.errors.category_id = 'Category is required';
                isValid = false;
            }

            if (!this.form.price || this.form.price <= 0) {
                this.errors.price = 'Valid price is required';
                isValid = false;
            }

            if (!this.form.cost_price || this.form.cost_price <= 0) {
                this.errors.cost_price = 'Valid cost price is required';
                isValid = false;
            }

            if (parseFloat(this.form.cost_price) >= parseFloat(this.form.price)) {
                this.errors.cost_price = 'Cost price must be less than selling price';
                isValid = false;
            }

            return isValid;
        }
    },
    template: `
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ product ? 'Edit' : 'Add' }} Product</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form @submit.prevent="saveProduct">
                        <div class="form-grid">
                            <!-- Product Name -->
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" 
                                       v-model="form.name" 
                                       required
                                       @input="searchBPOM">
                                <span class="form-error" v-if="errors.name">
                                    {{ errors.name }}
                                </span>
                            </div>

                            <!-- BPOM Search Results -->
                            <div v-if="showBpomSearch && bpomSearchResults.length" 
                                 class="bpom-results">
                                <div class="bpom-results-header">
                                    BPOM Database Matches
                                </div>
                                <div class="bpom-results-list">
                                    <div v-for="result in bpomSearchResults" 
                                         :key="result.registration_no"
                                         class="bpom-result-item"
                                         @click="applyBPOMData(result)">
                                        <div class="bpom-result-name">
                                            {{ result.product_name }}
                                        </div>
                                        <div class="bpom-result-details">
                                            <span class="bpom-reg-no">
                                                {{ result.registration_no }}
                                            </span>
                                            <span class="bpom-category">
                                                {{ result.category_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="form-group">
                                <label>Category *</label>
                                <select v-model="form.category_id" required>
                                    <option value="">Select Category</option>
                                    <option v-for="category in categories" 
                                            :key="category.id" 
                                            :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <span class="form-error" v-if="errors.category_id">
                                    {{ errors.category_id }}
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="form-group">
                                <label>Selling Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" 
                                           v-model="form.price" 
                                           required
                                           min="0"
                                           step="0.01">
                                </div>
                                <span class="form-error" v-if="errors.price">
                                    {{ errors.price }}
                                </span>
                            </div>

                            <!-- Cost Price -->
                            <div class="form-group">
                                <label>Cost Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" 
                                           v-model="form.cost_price" 
                                           required
                                           min="0"
                                           step="0.01">
                                </div>
                                <span class="form-error" v-if="errors.cost_price">
                                    {{ errors.cost_price }}
                                </span>
                            </div>

                            <!-- Minimum Stock -->
                            <div class="form-group">
                                <label>Minimum Stock</label>
                                <input type="number" 
                                       v-model="form.min_stock" 
                                       min="0">
                                <span class="form-error" v-if="errors.min_stock">
                                    {{ errors.min_stock }}
                                </span>
                            </div>

                            <!-- By Order -->
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="form.by_order">
                                    By Order Product
                                </label>
                                <small class="form-help">
                                    Check this if the product is not kept in stock and is ordered on demand
                                </small>
                            </div>

                            <!-- BPOM Code -->
                            <div class="form-group">
                                <label>BPOM Registration Number</label>
                                <input type="text" v-model="form.bpom_code">
                                <span class="form-error" v-if="errors.bpom_code">
                                    {{ errors.bpom_code }}
                                </span>
                            </div>

                            <!-- Description -->
                            <div class="form-group">
                                <label>Description</label>
                                <textarea v-model="form.description" rows="3"></textarea>
                                <span class="form-error" v-if="errors.description">
                                    {{ errors.description }}
                                </span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="$emit('close')">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="btn btn-primary"
                                    :disabled="loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                {{ product ? 'Update' : 'Create' }} Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `
});
