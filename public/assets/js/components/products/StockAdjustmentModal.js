Vue.component('stock-adjustment-modal', {
    props: {
        product: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            form: {
                quantity: 0,
                type: 'in',
                reason: '',
                notes: ''
            },
            errors: {},
            loading: false,
            reasons: {
                in: [
                    'Purchase',
                    'Return from Customer',
                    'Stock Count Adjustment',
                    'Other'
                ],
                out: [
                    'Sale',
                    'Damaged',
                    'Expired',
                    'Return to Supplier',
                    'Stock Count Adjustment',
                    'Other'
                ]
            }
        };
    },
    computed: {
        availableReasons() {
            return this.reasons[this.form.type] || [];
        },
        adjustmentTotal() {
            const quantity = parseFloat(this.form.quantity) || 0;
            return this.form.type === 'in' ? quantity : -quantity;
        },
        newStockLevel() {
            return this.product.stock + this.adjustmentTotal;
        },
        isValidAdjustment() {
            return this.newStockLevel >= 0;
        }
    },
    template: `
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Stock Adjustment</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="product-info">
                        <h3>{{ product.name }}</h3>
                        <div class="stock-info">
                            Current Stock: 
                            <span :class="getStockStatusClass(product.stock)">
                                {{ product.stock }}
                            </span>
                        </div>
                    </div>

                    <form @submit.prevent="saveAdjustment">
                        <!-- Adjustment Type -->
                        <div class="form-group">
                            <label>Adjustment Type *</label>
                            <div class="btn-group">
                                <button type="button" 
                                        class="btn"
                                        :class="form.type === 'in' ? 'btn-primary' : 'btn-secondary'"
                                        @click="form.type = 'in'">
                                    <i class="fas fa-plus"></i> Stock In
                                </button>
                                <button type="button"
                                        class="btn"
                                        :class="form.type === 'out' ? 'btn-primary' : 'btn-secondary'"
                                        @click="form.type = 'out'">
                                    <i class="fas fa-minus"></i> Stock Out
                                </button>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" 
                                   v-model="form.quantity"
                                   required
                                   min="0"
                                   step="1">
                            <div class="stock-preview" :class="{ 'invalid': !isValidAdjustment }">
                                New Stock Level: {{ newStockLevel }}
                                <span v-if="!isValidAdjustment" class="error-text">
                                    (Invalid: Stock cannot be negative)
                                </span>
                            </div>
                            <span class="form-error" v-if="errors.quantity">
                                {{ errors.quantity }}
                            </span>
                        </div>

                        <!-- Reason -->
                        <div class="form-group">
                            <label>Reason *</label>
                            <select v-model="form.reason" required>
                                <option value="">Select Reason</option>
                                <option v-for="reason in availableReasons" 
                                        :key="reason" 
                                        :value="reason">
                                    {{ reason }}
                                </option>
                            </select>
                            <span class="form-error" v-if="errors.reason">
                                {{ errors.reason }}
                            </span>
                        </div>

                        <!-- Notes -->
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea v-model="form.notes" 
                                    rows="3"
                                    placeholder="Additional details about this adjustment"></textarea>
                            <span class="form-error" v-if="errors.notes">
                                {{ errors.notes }}
                            </span>
                        </div>

                        <div class="form-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="$emit('close')">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="btn btn-primary"
                                    :disabled="loading || !isValidAdjustment">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                Save Adjustment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
    methods: {
        getStockStatusClass(stock) {
            if (stock <= 0) return 'text-danger';
            if (stock <= this.product.min_stock) return 'text-warning';
            return 'text-success';
        },

        saveAdjustment() {
            this.loading = true;
            this.errors = {};

            // Validate form
            if (!this.validateForm()) {
                this.loading = false;
                return;
            }

            // Prepare adjustment data
            const adjustmentData = {
                ...this.form,
                quantity: Math.abs(parseFloat(this.form.quantity)),
                product_id: this.product.id
            };

            this.$emit('save', adjustmentData);
        },

        validateForm() {
            let isValid = true;

            if (!this.form.quantity || this.form.quantity <= 0) {
                this.errors.quantity = 'Please enter a valid quantity';
                isValid = false;
            }

            if (!this.form.reason) {
                this.errors.reason = 'Please select a reason';
                isValid = false;
            }

            if (!this.isValidAdjustment) {
                this.errors.quantity = 'Adjustment would result in negative stock';
                isValid = false;
            }

            return isValid;
        }
    }
});
