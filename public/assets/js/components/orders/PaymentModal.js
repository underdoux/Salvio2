Vue.component('payment-modal', {
    props: {
        order: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            form: {
                amount: 0,
                payment_method: 'cash',
                reference_number: '',
                payment_date: new Date().toISOString().split('T')[0],
                notes: ''
            },
            paymentMethods: [
                { value: 'cash', label: 'Cash' },
                { value: 'bank_transfer', label: 'Bank Transfer' },
                { value: 'debit_card', label: 'Debit Card' },
                { value: 'credit_card', label: 'Credit Card' }
            ],
            loading: false,
            errors: {},
            paymentHistory: [],
            showHistory: false
        };
    },
    computed: {
        remainingAmount() {
            const totalPaid = this.paymentHistory.reduce(
                (sum, payment) => sum + payment.amount,
                0
            );
            return this.order.total - totalPaid;
        },
        isFullPayment() {
            return Math.abs(this.form.amount - this.remainingAmount) < 0.01;
        },
        paymentStatus() {
            if (this.remainingAmount <= 0) return 'paid';
            if (this.paymentHistory.length > 0) return 'partial';
            return 'pending';
        }
    },
    template: `
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Process Payment</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-row">
                            <label>Order #:</label>
                            <span>{{ order.order_number }}</span>
                        </div>
                        <div class="summary-row">
                            <label>Customer:</label>
                            <span>{{ order.customer_name }}</span>
                        </div>
                        <div class="summary-row">
                            <label>Total Amount:</label>
                            <span>{{ formatCurrency(order.total) }}</span>
                        </div>
                        <div class="summary-row">
                            <label>Amount Paid:</label>
                            <span>{{ formatCurrency(order.total - remainingAmount) }}</span>
                        </div>
                        <div class="summary-row highlight">
                            <label>Remaining:</label>
                            <span>{{ formatCurrency(remainingAmount) }}</span>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="payment-history">
                        <div class="section-header" @click="showHistory = !showHistory">
                            <h3>Payment History</h3>
                            <i class="fas" :class="showHistory ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </div>
                        <div v-show="showHistory" class="history-list">
                            <div v-if="!paymentHistory.length" class="empty-state">
                                No payments recorded
                            </div>
                            <div v-else v-for="payment in paymentHistory" 
                                 :key="payment.id" 
                                 class="history-item">
                                <div class="payment-info">
                                    <div class="amount">
                                        {{ formatCurrency(payment.amount) }}
                                    </div>
                                    <div class="details">
                                        <span class="method">
                                            {{ getMethodLabel(payment.payment_method) }}
                                        </span>
                                        <span class="date">
                                            {{ formatDate(payment.payment_date) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="payment-meta">
                                    <div v-if="payment.reference_number" class="reference">
                                        Ref: {{ payment.reference_number }}
                                    </div>
                                    <div v-if="payment.notes" class="notes">
                                        {{ payment.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form @submit.prevent="processPayment" class="payment-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Amount *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           v-model.number="form.amount"
                                           :max="remainingAmount"
                                           step="0.01"
                                           required>
                                </div>
                                <span class="form-error" v-if="errors.amount">
                                    {{ errors.amount }}
                                </span>
                                <div class="quick-amounts" v-if="remainingAmount > 0">
                                    <button type="button"
                                            class="btn btn-sm"
                                            @click="form.amount = remainingAmount">
                                        Full Amount ({{ formatCurrency(remainingAmount) }})
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm"
                                            @click="form.amount = remainingAmount / 2">
                                        Half ({{ formatCurrency(remainingAmount / 2) }})
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Payment Method *</label>
                                <select v-model="form.payment_method" required>
                                    <option v-for="method in paymentMethods"
                                            :key="method.value"
                                            :value="method.value">
                                        {{ method.label }}
                                    </option>
                                </select>
                                <span class="form-error" v-if="errors.payment_method">
                                    {{ errors.payment_method }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Payment Date *</label>
                                <input type="date"
                                       v-model="form.payment_date"
                                       required>
                                <span class="form-error" v-if="errors.payment_date">
                                    {{ errors.payment_date }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Reference Number</label>
                                <input type="text"
                                       v-model="form.reference_number"
                                       :placeholder="getReferencePlaceholder">
                                <span class="form-error" v-if="errors.reference_number">
                                    {{ errors.reference_number }}
                                </span>
                            </div>

                            <div class="form-group full-width">
                                <label>Notes</label>
                                <textarea v-model="form.notes"
                                        rows="2"
                                        placeholder="Any additional notes about this payment"></textarea>
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
                                    :disabled="loading || remainingAmount <= 0">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                Process Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
    methods: {
        async loadPaymentHistory() {
            try {
                const response = await axios.get(`/api/orders/${this.order.id}/payments`);
                this.paymentHistory = response.data;
            } catch (error) {
                console.error('Failed to load payment history:', error);
            }
        },

        getMethodLabel(method) {
            const found = this.paymentMethods.find(m => m.value === method);
            return found ? found.label : method;
        },

        get getReferencePlaceholder() {
            switch (this.form.payment_method) {
                case 'bank_transfer':
                    return 'Transfer Reference Number';
                case 'debit_card':
                case 'credit_card':
                    return 'Transaction ID';
                default:
                    return 'Reference Number (Optional)';
            }
        },

        async processPayment() {
            if (!this.validateForm()) return;

            this.loading = true;
            this.errors = {};

            try {
                await axios.post(`/api/orders/${this.order.id}/payments`, this.form);
                
                this.$root.showToast(
                    `Payment of ${this.formatCurrency(this.form.amount)} processed successfully`,
                    'success'
                );

                // Emit event with payment status
                this.$emit('payment-processed', {
                    amount: this.form.amount,
                    status: this.paymentStatus
                });

                this.$emit('close');
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    this.$root.showToast('Failed to process payment', 'error');
                }
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.amount || this.form.amount <= 0) {
                this.errors.amount = 'Please enter a valid amount';
                isValid = false;
            }

            if (this.form.amount > this.remainingAmount) {
                this.errors.amount = 'Amount cannot exceed remaining balance';
                isValid = false;
            }

            if (!this.form.payment_method) {
                this.errors.payment_method = 'Please select a payment method';
                isValid = false;
            }

            if (!this.form.payment_date) {
                this.errors.payment_date = 'Please select a payment date';
                isValid = false;
            }

            if (['bank_transfer', 'debit_card', 'credit_card'].includes(this.form.payment_method) 
                && !this.form.reference_number) {
                this.errors.reference_number = 'Reference number is required for this payment method';
                isValid = false;
            }

            return isValid;
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    },
    created() {
        this.form.amount = this.remainingAmount;
        this.loadPaymentHistory();
    }
});
