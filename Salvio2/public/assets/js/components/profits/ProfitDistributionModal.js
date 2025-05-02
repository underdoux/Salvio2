Vue.component('profit-distribution-modal', {
    props: {
        distribution: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            form: {
                payment_date: new Date().toISOString().split('T')[0],
                payment_method: 'bank_transfer',
                notes: '',
                shares: []
            },
            paymentMethods: [
                { value: 'bank_transfer', label: 'Bank Transfer' },
                { value: 'check', label: 'Check' },
                { value: 'cash', label: 'Cash' }
            ],
            loading: false,
            errors: {},
            showConfirmation: false
        };
    },
    computed: {
        totalAmount() {
            return this.form.shares.reduce((sum, share) => sum + share.amount, 0);
        },
        hasAdjustments() {
            return this.form.shares.some(share => share.adjustment !== 0);
        }
    },
    template: `
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Distribute Profits</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Distribution Summary -->
                    <div class="distribution-summary">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <label>Period</label>
                                <span>{{ formatPeriod(distribution.period_start, distribution.period_end) }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Net Profit</label>
                                <span>{{ formatCurrency(distribution.net_profit) }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Status</label>
                                <span class="badge" :class="getStatusClass(distribution.status)">
                                    {{ getStatusLabel(distribution.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="confirmDistribution">
                        <!-- Payment Details -->
                        <div class="form-section">
                            <h3>Payment Details</h3>
                            <div class="form-grid">
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
                            </div>
                        </div>

                        <!-- Investor Shares -->
                        <div class="form-section">
                            <h3>Investor Shares</h3>
                            <div class="shares-list">
                                <div v-for="(share, index) in form.shares"
                                     :key="share.investor_id"
                                     class="share-item">
                                    <div class="investor-info">
                                        <div class="name">{{ share.investor_name }}</div>
                                        <div class="percentage">{{ share.percentage }}%</div>
                                    </div>

                                    <div class="share-details">
                                        <div class="amount-group">
                                            <label>Original Amount</label>
                                            <span>{{ formatCurrency(share.original_amount) }}</span>
                                        </div>

                                        <div class="adjustment-group">
                                            <label>Adjustment</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number"
                                                       v-model.number="share.adjustment"
                                                       step="0.01"
                                                       @input="updateShareAmount(index)">
                                            </div>
                                            <span v-if="share.adjustment" 
                                                  :class="getAdjustmentClass(share.adjustment)">
                                                {{ formatAdjustment(share.adjustment) }}
                                            </span>
                                        </div>

                                        <div class="final-amount">
                                            <label>Final Amount</label>
                                            <strong>{{ formatCurrency(share.amount) }}</strong>
                                        </div>
                                    </div>

                                    <div class="share-notes">
                                        <input type="text"
                                               v-model="share.notes"
                                               :placeholder="'Notes for ' + share.investor_name + ' (optional)'">
                                    </div>
                                </div>
                            </div>

                            <div class="shares-summary">
                                <div class="total-line">
                                    <label>Total Distribution:</label>
                                    <span>{{ formatCurrency(totalAmount) }}</span>
                                </div>
                                <div v-if="hasAdjustments" class="adjustments-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Adjustments have been made to the original distribution amounts
                                </div>
                            </div>
                        </div>

                        <!-- Distribution Notes -->
                        <div class="form-group">
                            <label>Distribution Notes</label>
                            <textarea v-model="form.notes"
                                    rows="3"
                                    placeholder="Add any notes about this distribution"></textarea>
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
                                Review Distribution
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div v-if="showConfirmation" class="modal confirmation-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Confirm Distribution</h2>
                        <button class="btn-close" @click="showConfirmation = false">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="confirmation-message">
                            <i class="fas fa-info-circle"></i>
                            <p>Please review the distribution details before proceeding.</p>
                        </div>

                        <div class="confirmation-details">
                            <div class="detail-item">
                                <label>Total Amount:</label>
                                <span>{{ formatCurrency(totalAmount) }}</span>
                            </div>
                            <div class="detail-item">
                                <label>Payment Method:</label>
                                <span>{{ getPaymentMethodLabel(form.payment_method) }}</span>
                            </div>
                            <div class="detail-item">
                                <label>Payment Date:</label>
                                <span>{{ formatDate(form.payment_date) }}</span>
                            </div>
                        </div>

                        <div v-if="hasAdjustments" class="confirmation-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>You have made adjustments to the original distribution amounts. 
                               Please ensure these adjustments are correct.</p>
                        </div>

                        <div class="confirmation-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="showConfirmation = false">
                                Review Again
                            </button>
                            <button type="button" 
                                    class="btn btn-primary"
                                    @click="distribute"
                                    :disabled="loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                Confirm & Distribute
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    methods: {
        initializeShares() {
            this.form.shares = this.distribution.shares.map(share => ({
                investor_id: share.investor_id,
                investor_name: share.investor_name,
                percentage: share.percentage,
                original_amount: share.amount,
                adjustment: 0,
                amount: share.amount,
                notes: ''
            }));
        },

        updateShareAmount(index) {
            const share = this.form.shares[index];
            share.amount = share.original_amount + (share.adjustment || 0);
        },

        confirmDistribution() {
            if (!this.validateForm()) return;
            this.showConfirmation = true;
        },

        async distribute() {
            this.loading = true;
            try {
                await this.$emit('distribute', this.form);
            } catch (error) {
                console.error('Distribution failed:', error);
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.payment_date) {
                this.errors.payment_date = 'Payment date is required';
                isValid = false;
            }

            if (!this.form.payment_method) {
                this.errors.payment_method = 'Payment method is required';
                isValid = false;
            }

            if (this.totalAmount <= 0) {
                this.errors.shares = 'Total distribution amount must be greater than zero';
                isValid = false;
            }

            return isValid;
        },

        getStatusClass(status) {
            const classes = {
                pending: 'badge-warning',
                calculated: 'badge-info',
                distributed: 'badge-success'
            };
            return classes[status] || 'badge-secondary';
        },

        getStatusLabel(status) {
            const labels = {
                pending: 'Pending',
                calculated: 'Calculated',
                distributed: 'Distributed'
            };
            return labels[status] || status;
        },

        getPaymentMethodLabel(method) {
            const found = this.paymentMethods.find(m => m.value === method);
            return found ? found.label : method;
        },

        getAdjustmentClass(adjustment) {
            return adjustment > 0 ? 'text-success' : 'text-danger';
        },

        formatPeriod(start, end) {
            const startDate = new Date(start);
            const endDate = new Date(end);
            
            if (startDate.getMonth() === endDate.getMonth()) {
                return new Intl.DateTimeFormat('id-ID', {
                    year: 'numeric',
                    month: 'long'
                }).format(startDate);
            }
            
            return `${this.formatDate(start)} - ${this.formatDate(end)}`;
        },

        formatDate(date) {
            return new Intl.DateTimeFormat('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(date));
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        formatAdjustment(amount) {
            return `${amount > 0 ? '+' : ''}${this.formatCurrency(amount)}`;
        }
    },
    created() {
        this.initializeShares();
    }
});
