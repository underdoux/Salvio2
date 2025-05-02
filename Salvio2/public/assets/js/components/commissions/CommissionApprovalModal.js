Vue.component('commission-approval-modal', {
    props: {
        commission: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            form: {
                notes: '',
                adjustment: 0,
                adjustment_reason: ''
            },
            loading: false,
            errors: {},
            showAdjustment: false
        };
    },
    computed: {
        finalAmount() {
            return this.commission.amount + (parseFloat(this.form.adjustment) || 0);
        },
        adjustmentType() {
            return this.form.adjustment > 0 ? 'increase' : 'decrease';
        },
        adjustmentPercentage() {
            if (!this.form.adjustment) return 0;
            return ((Math.abs(this.form.adjustment) / this.commission.amount) * 100).toFixed(2);
        }
    },
    template: `
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Review Commission</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Commission Details -->
                    <div class="commission-details">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Order Number</label>
                                <span>{{ commission.order_number }}</span>
                            </div>
                            <div class="detail-item">
                                <label>Sales Person</label>
                                <span>{{ commission.user_name }}</span>
                            </div>
                            <div class="detail-item">
                                <label>Order Total</label>
                                <span>{{ formatCurrency(commission.order_total) }}</span>
                            </div>
                            <div class="detail-item">
                                <label>Commission Rate</label>
                                <span>{{ commission.commission_rate }}%</span>
                            </div>
                            <div class="detail-item">
                                <label>Original Commission</label>
                                <span>{{ formatCurrency(commission.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Adjustment Form -->
                    <div class="adjustment-section">
                        <div class="section-header" @click="showAdjustment = !showAdjustment">
                            <h3>
                                <i class="fas" :class="showAdjustment ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                Commission Adjustment
                            </h3>
                        </div>
                        
                        <div v-show="showAdjustment" class="adjustment-form">
                            <div class="form-group">
                                <label>Adjustment Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           v-model="form.adjustment"
                                           step="0.01"
                                           :max="commission.amount"
                                           :min="-commission.amount">
                                </div>
                                <span v-if="form.adjustment" class="adjustment-info" 
                                      :class="adjustmentType">
                                    {{ adjustmentType === 'increase' ? '+' : '-' }}{{ adjustmentPercentage }}%
                                </span>
                                <span class="form-error" v-if="errors.adjustment">
                                    {{ errors.adjustment }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Adjustment Reason</label>
                                <textarea v-model="form.adjustment_reason"
                                        rows="2"
                                        :required="form.adjustment !== 0"
                                        placeholder="Explain why the commission is being adjusted"></textarea>
                                <span class="form-error" v-if="errors.adjustment_reason">
                                    {{ errors.adjustment_reason }}
                                </span>
                            </div>

                            <div class="final-amount">
                                <label>Final Commission Amount:</label>
                                <span :class="{ 
                                    'text-success': finalAmount > commission.amount,
                                    'text-danger': finalAmount < commission.amount
                                }">
                                    {{ formatCurrency(finalAmount) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label>Review Notes</label>
                        <textarea v-model="form.notes"
                                rows="3"
                                placeholder="Add any notes about this commission review"></textarea>
                        <span class="form-error" v-if="errors.notes">
                            {{ errors.notes }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="approval-actions">
                        <button type="button"
                                class="btn btn-danger"
                                @click="reject"
                                :disabled="loading">
                            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                            Reject Commission
                        </button>
                        
                        <button type="button"
                                class="btn btn-success"
                                @click="approve"
                                :disabled="loading">
                            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                            Approve Commission
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `,
    methods: {
        validateForm() {
            this.errors = {};
            let isValid = true;

            if (this.form.adjustment && !this.form.adjustment_reason) {
                this.errors.adjustment_reason = 'Please provide a reason for the adjustment';
                isValid = false;
            }

            if (this.finalAmount <= 0) {
                this.errors.adjustment = 'Final commission amount cannot be zero or negative';
                isValid = false;
            }

            return isValid;
        },

        async approve() {
            if (!this.validateForm()) return;

            this.loading = true;
            try {
                await this.$emit('approve', {
                    ...this.form,
                    final_amount: this.finalAmount
                });
            } catch (error) {
                console.error('Approval failed:', error);
            } finally {
                this.loading = false;
            }
        },

        async reject() {
            if (!this.form.notes) {
                this.errors.notes = 'Please provide a reason for rejection';
                return;
            }

            this.loading = true;
            try {
                await this.$emit('reject', this.form);
            } catch (error) {
                console.error('Rejection failed:', error);
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
    }
});
