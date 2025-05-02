Vue.component('order-form-modal', {
    props: {
        order: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            form: {
                customer_name: '',
                customer_phone: '',
                customer_email: '',
                customer_type: 'pharmacy',
                items: [],
                notes: '',
                payment_type: 'cash',
                tax_percentage: 0,
                shipping_address: ''
            },
            customerTypes: [
                { value: 'pharmacy', label: 'Pharmacy' },
                { value: 'clinic', label: 'Clinic' },
                { value: 'hospital', label: 'Hospital' },
                { value: 'distributor', label: 'Distributor' },
                { value: 'other', label: 'Other' }
            ],
            paymentTypes: [
                { value: 'cash', label: 'Cash' },
                { value: 'transfer', label: 'Bank Transfer' },
                { value: 'installment', label: 'Installment' }
            ],
            products: [],
            searchQuery: '',
            searchResults: [],
            showProductSearch: false,
            loading: false,
            errors: {},
            settings: null,
            installmentOptions: [
                { value: 30, label: '30 Days' },
                { value: 60, label: '60 Days' },
                { value: 90, label: '90 Days' }
            ]
        };
    },
    computed: {
        subtotal() {
            return this.form.items.reduce((sum, item) => {
                return sum + (item.quantity * item.price);
            }, 0);
        },
        taxAmount() {
            return this.subtotal * (this.form.tax_percentage / 100);
        },
        total() {
            return this.subtotal + this.taxAmount;
        },
        isValid() {
            return this.form.customer_name &&
                   this.form.customer_phone &&
                   this.form.items.length > 0 &&
                   this.form.items.every(item => item.quantity > 0);
        }
    },
    template: `
        <div class="modal modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ order ? 'Edit' : 'New' }} Order</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form @submit.prevent="saveOrder">
                        <!-- Customer Information -->
                        <div class="section">
                            <h3>Customer Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Customer Name *</label>
                                    <input type="text" 
                                           v-model="form.customer_name" 
                                           required>
                                    <span class="form-error" v-if="errors.customer_name">
                                        {{ errors.customer_name }}
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label>Phone Number *</label>
                                    <input type="tel" 
                                           v-model="form.customer_phone" 
                                           required>
                                    <span class="form-error" v-if="errors.customer_phone">
                                        {{ errors.customer_phone }}
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" v-model="form.customer_email">
                                    <span class="form-error" v-if="errors.customer_email">
                                        {{ errors.customer_email }}
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label>Customer Type *</label>
                                    <select v-model="form.customer_type" required>
                                        <option v-for="type in customerTypes"
                                                :key="type.value"
                                                :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="section">
                            <h3>Order Items</h3>
                            
                            <!-- Product Search -->
                            <div class="product-search">
                                <div class="search-input">
                                    <input type="text"
                                           v-model="searchQuery"
                                           @input="searchProducts"
                                           placeholder="Search products...">
                                    <div v-if="showProductSearch && searchResults.length" 
                                         class="search-results">
                                        <div v-for="product in searchResults"
                                             :key="product.id"
                                             class="search-result-item"
                                             @click="addProduct(product)">
                                            <div class="product-info">
                                                <strong>{{ product.name }}</strong>
                                                <small>{{ product.category_name }}</small>
                                            </div>
                                            <div class="product-meta">
                                                <span class="price">
                                                    {{ formatCurrency(product.price) }}
                                                </span>
                                                <span :class="getStockStatusClass(product)">
                                                    {{ getStockLabel(product) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!form.items.length">
                                            <td colspan="5" class="text-center">
                                                No items added
                                            </td>
                                        </tr>
                                        <tr v-for="(item, index) in form.items" 
                                            :key="item.product_id">
                                            <td>
                                                {{ item.name }}
                                                <small v-if="item.by_order" class="badge badge-info">
                                                    By Order
                                                </small>
                                            </td>
                                            <td>{{ formatCurrency(item.price) }}</td>
                                            <td>
                                                <input type="number"
                                                       v-model.number="item.quantity"
                                                       min="1"
                                                       :max="item.by_order ? null : item.stock"
                                                       @input="validateQuantity(item)">
                                            </td>
                                            <td>{{ formatCurrency(item.price * item.quantity) }}</td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-icon btn-danger"
                                                        @click="removeItem(index)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right">Subtotal:</td>
                                            <td colspan="2">{{ formatCurrency(subtotal) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                Tax ({{ form.tax_percentage }}%):
                                            </td>
                                            <td colspan="2">{{ formatCurrency(taxAmount) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <strong>Total:</strong>
                                            </td>
                                            <td colspan="2">
                                                <strong>{{ formatCurrency(total) }}</strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="section">
                            <h3>Payment Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Payment Type *</label>
                                    <select v-model="form.payment_type" required>
                                        <option v-for="type in paymentTypes"
                                                :key="type.value"
                                                :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>

                                <div v-if="form.payment_type === 'installment'"
                                     class="form-group">
                                    <label>Installment Period *</label>
                                    <select v-model="form.installment_days" required>
                                        <option v-for="option in installmentOptions"
                                                :key="option.value"
                                                :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Tax Percentage</label>
                                    <input type="number"
                                           v-model.number="form.tax_percentage"
                                           min="0"
                                           max="100"
                                           step="0.1">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="section">
                            <h3>Additional Information</h3>
                            <div class="form-group">
                                <label>Shipping Address</label>
                                <textarea v-model="form.shipping_address" 
                                        rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Notes</label>
                                <textarea v-model="form.notes" 
                                        rows="3"
                                        placeholder="Any special instructions or notes"></textarea>
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
                                    :disabled="!isValid || loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                {{ order ? 'Update' : 'Create' }} Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
    methods: {
        async loadSettings() {
            try {
                const response = await axios.get('/api/settings/order');
                this.settings = response.data;
                this.form.tax_percentage = parseFloat(this.settings.default_tax_rate) || 0;
            } catch (error) {
                console.error('Failed to load settings:', error);
            }
        },

        searchProducts: _.debounce(async function() {
            if (!this.searchQuery || this.searchQuery.length < 2) {
                this.showProductSearch = false;
                return;
            }

            try {
                const response = await axios.get('/api/products/search', {
                    params: { query: this.searchQuery }
                });
                this.searchResults = response.data;
                this.showProductSearch = true;
            } catch (error) {
                console.error('Product search failed:', error);
            }
        }, 300),

        addProduct(product) {
            const existingItem = this.form.items.find(
                item => item.product_id === product.id
            );

            if (existingItem) {
                existingItem.quantity++;
            } else {
                this.form.items.push({
                    product_id: product.id,
                    name: product.name,
                    price: product.price,
                    quantity: 1,
                    stock: product.stock,
                    by_order: product.by_order
                });
            }

            this.searchQuery = '';
            this.showProductSearch = false;
        },

        removeItem(index) {
            this.form.items.splice(index, 1);
        },

        validateQuantity(item) {
            if (!item.by_order && item.quantity > item.stock) {
                item.quantity = item.stock;
                this.$root.showToast(
                    `Maximum available quantity is ${item.stock}`,
                    'warning'
                );
            }
        },

        getStockStatusClass(product) {
            if (product.by_order) return 'text-info';
            if (product.stock <= 0) return 'text-danger';
            if (product.stock <= product.min_stock) return 'text-warning';
            return 'text-success';
        },

        getStockLabel(product) {
            if (product.by_order) return 'By Order';
            if (product.stock <= 0) return 'Out of Stock';
            if (product.stock <= product.min_stock) return 'Low Stock';
            return `${product.stock} in stock`;
        },

        async saveOrder() {
            if (!this.isValid) return;

            this.loading = true;
            this.errors = {};

            try {
                const orderData = {
                    ...this.form,
                    total: this.total,
                    subtotal: this.subtotal,
                    tax_amount: this.taxAmount
                };

                if (this.order) {
                    await axios.put(`/api/orders/${this.order.id}`, orderData);
                    this.$root.showToast('Order updated successfully', 'success');
                } else {
                    await axios.post('/api/orders', orderData);
                    this.$root.showToast('Order created successfully', 'success');
                }

                this.$emit('save');
                this.$emit('close');
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    this.$root.showToast(
                        'Failed to save order',
                        'error'
                    );
                }
            } finally {
                this.loading = false;
            }
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        }
    },
    created() {
        if (this.order) {
            this.form = { ...this.order };
        }
        this.loadSettings();
    }
});
