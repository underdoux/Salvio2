Vue.component('profit-calculation-modal', {
    data() {
        return {
            form: {
                period_start: '',
                period_end: '',
                include_pending_payments: false,
                recalculate_costs: false,
                notes: ''
            },
            preview: null,
            loading: false,
            calculating: false,
            errors: {},
            showPreview: false
        };
    },
    computed: {
        isValid() {
            return this.form.period_start && 
                   this.form.period_end && 
                   new Date(this.form.period_start) <= new Date(this.form.period_end);
        },
        previewTotal() {
            if (!this.preview) return 0;
            return this.preview.revenue - this.preview.costs - this.preview.commissions;
        }
    },
    template: `
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Calculate Profit Distribution</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form @submit.prevent="calculate">
                        <!-- Period Selection -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Period Start *</label>
                                <input type="date" 
                                       v-model="form.period_start"
                                       required>
                                <span class="form-error" v-if="errors.period_start">
                                    {{ errors.period_start }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Period End *</label>
                                <input type="date" 
                                       v-model="form.period_end"
                                       required>
                                <span class="form-error" v-if="errors.period_end">
                                    {{ errors.period_end }}
                                </span>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       v-model="form.include_pending_payments">
                                Include Pending Payments
                                <i class="fas fa-info-circle" 
                                   title="Include orders with pending payments in calculation"></i>
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       v-model="form.recalculate_costs">
                                Recalculate Operational Costs
                                <i class="fas fa-info-circle" 
                                   title="Recalculate all operational costs for the period"></i>
                            </label>
                        </div>

                        <!-- Notes -->
                        <div class="form-group">
                            <label>Calculation Notes</label>
                            <textarea v-model="form.notes"
                                    rows="2"
                                    placeholder="Add any notes about this calculation"></textarea>
                        </div>

                        <!-- Preview Section -->
                        <div v-if="showPreview" class="preview-section">
                            <div class="section-header">
                                <h3>Calculation Preview</h3>
                                <button type="button" 
                                        class="btn btn-link"
                                        @click="refreshPreview">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>

                            <div v-if="preview" class="preview-content">
                                <div class="preview-grid">
                                    <div class="preview-item">
                                        <label>Total Revenue</label>
                                        <span class="text-success">
                                            {{ formatCurrency(preview.revenue) }}
                                        </span>
                                        <small>{{ preview.order_count }} orders</small>
                                    </div>

                                    <div class="preview-item">
                                        <label>Total Costs</label>
                                        <span class="text-danger">
                                            {{ formatCurrency(preview.costs) }}
                                        </span>
                                        <small>Including operational costs</small>
                                    </div>

                                    <div class="preview-item">
                                        <label>Total Commissions</label>
                                        <span class="text-warning">
                                            {{ formatCurrency(preview.commissions) }}
                                        </span>
                                        <small>{{ preview.commission_count }} commissions</small>
                                    </div>

                                    <div class="preview-item highlight">
                                        <label>Net Profit</label>
                                        <span :class="previewTotal >= 0 ? 'text-success' : 'text-danger'">
                                            {{ formatCurrency(previewTotal) }}
                                        </span>
                                        <small>{{ formatPercentage(preview.profit_margin) }} margin</small>
                                    </div>
                                </div>

                                <!-- Investor Distribution Preview -->
                                <div class="investor-preview">
                                    <h4>Estimated Distribution</h4>
                                    <div class="investor-list">
                                        <div v-for="share in preview.shares" 
                                             :key="share.investor_id"
                                             class="investor-share">
                                            <div class="investor-info">
                                                <span class="name">{{ share.investor_name }}</span>
                                                <span class="percentage">{{ share.percentage }}%</span>
                                            </div>
                                            <div class="share-amount">
                                                {{ formatCurrency(share.amount) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="calculating" class="preview-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                Calculating preview...
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" 
                                    v-if="!showPreview"
                                    class="btn btn-secondary" 
                                    @click="previewCalculation"
                                    :disabled="!isValid || calculating">
                                Preview Calculation
                            </button>
                            
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="$emit('close')">
                                Cancel
                            </button>

                            <button type="submit" 
                                    class="btn btn-primary"
                                    :disabled="!isValid || loading || (showPreview && !preview)">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                Calculate & Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
    methods: {
        async previewCalculation() {
            if (!this.validateForm()) return;

            this.calculating = true;
            this.showPreview = true;
            try {
                const response = await axios.post('/api/profit-distributions/preview', this.form);
                this.preview = response.data;
            } catch (error) {
                this.$root.showToast('Failed to generate preview', 'error');
                this.showPreview = false;
            } finally {
                this.calculating = false;
            }
        },

        async refreshPreview() {
            this.preview = null;
            await this.previewCalculation();
        },

        async calculate() {
            if (!this.validateForm()) return;

            if (!this.showPreview) {
                await this.previewCalculation();
                return;
            }

            if (!confirm('Are you sure you want to proceed with this calculation?')) return;

            this.loading = true;
            try {
                await this.$emit('calculate', {
                    ...this.form,
                    preview_id: this.preview?.id
                });
            } catch (error) {
                console.error('Calculation failed:', error);
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.period_start) {
                this.errors.period_start = 'Start date is required';
                isValid = false;
            }

            if (!this.form.period_end) {
                this.errors.period_end = 'End date is required';
                isValid = false;
            }

            if (this.form.period_start && this.form.period_end) {
                const start = new Date(this.form.period_start);
                const end = new Date(this.form.period_end);
                
                if (start > end) {
                    this.errors.period_end = 'End date must be after start date';
                    isValid = false;
                }
            }

            return isValid;
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        formatPercentage(value) {
            return `${value.toFixed(2)}%`;
        }
    }
});
