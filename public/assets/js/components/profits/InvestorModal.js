Vue.component('investor-modal', {
    data() {
        return {
            investors: [],
            loading: false,
            showForm: false,
            form: {
                name: '',
                email: '',
                phone: '',
                bank_name: '',
                bank_account: '',
                percentage: '',
                active: true,
                notes: ''
            },
            selectedInvestor: null,
            errors: {},
            totalPercentage: 0,
            showConfirmation: false,
            confirmationMessage: ''
        };
    },
    computed: {
        remainingPercentage() {
            const current = this.selectedInvestor ? 
                this.investors.find(i => i.id === this.selectedInvestor.id)?.percentage || 0 : 
                0;
            return 100 - (this.totalPercentage - current);
        },
        canAddInvestor() {
            return this.remainingPercentage > 0;
        },
        maxPercentage() {
            return this.selectedInvestor ? 
                this.remainingPercentage + this.selectedInvestor.percentage :
                this.remainingPercentage;
        }
    },
    template: `
        <div class="modal modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Manage Investors</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Investors List -->
                    <div v-if="!showForm" class="investors-section">
                        <div class="section-header">
                            <h3>Current Investors</h3>
                            <button v-if="canAddInvestor"
                                    class="btn btn-primary"
                                    @click="showAddForm">
                                <i class="fas fa-plus"></i> Add Investor
                            </button>
                        </div>

                        <div class="percentage-bar">
                            <div class="percentage-info">
                                <span>Total Allocated: {{ totalPercentage }}%</span>
                                <span>Remaining: {{ remainingPercentage }}%</span>
                            </div>
                            <div class="progress-bar">
                                <div v-for="investor in investors"
                                     :key="investor.id"
                                     class="progress-segment"
                                     :style="{ 
                                         width: investor.percentage + '%',
                                         backgroundColor: getInvestorColor(investor)
                                     }"
                                     :title="investor.name + ' - ' + investor.percentage + '%'">
                                </div>
                            </div>
                        </div>

                        <div class="investors-list">
                            <div v-for="investor in investors"
                                 :key="investor.id"
                                 class="investor-card"
                                 :class="{ 'inactive': !investor.active }">
                                <div class="investor-header">
                                    <div class="investor-info">
                                        <h4>{{ investor.name }}</h4>
                                        <span class="percentage">{{ investor.percentage }}%</span>
                                    </div>
                                    <div class="investor-actions">
                                        <button class="btn btn-icon btn-sm"
                                                @click="toggleInvestor(investor)"
                                                :title="investor.active ? 'Deactivate' : 'Activate'">
                                            <i class="fas" 
                                               :class="investor.active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                        </button>
                                        <button class="btn btn-icon btn-sm"
                                                @click="editInvestor(investor)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-icon btn-sm btn-danger"
                                                @click="confirmDelete(investor)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="investor-details">
                                    <div class="detail-item" v-if="investor.email">
                                        <i class="fas fa-envelope"></i>
                                        <span>{{ investor.email }}</span>
                                    </div>
                                    <div class="detail-item" v-if="investor.phone">
                                        <i class="fas fa-phone"></i>
                                        <span>{{ investor.phone }}</span>
                                    </div>
                                    <div class="detail-item" v-if="investor.bank_name">
                                        <i class="fas fa-university"></i>
                                        <span>{{ investor.bank_name }} - {{ investor.bank_account }}</span>
                                    </div>
                                </div>

                                <div v-if="investor.notes" class="investor-notes">
                                    {{ investor.notes }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add/Edit Form -->
                    <form v-else @submit.prevent="saveInvestor" class="investor-form">
                        <h3>{{ selectedInvestor ? 'Edit' : 'Add' }} Investor</h3>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text"
                                       v-model="form.name"
                                       required>
                                <span class="form-error" v-if="errors.name">
                                    {{ errors.name }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Profit Share Percentage *</label>
                                <input type="number"
                                       v-model.number="form.percentage"
                                       required
                                       min="0"
                                       :max="maxPercentage"
                                       step="0.01">
                                <small>Maximum available: {{ maxPercentage }}%</small>
                                <span class="form-error" v-if="errors.percentage">
                                    {{ errors.percentage }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email"
                                       v-model="form.email">
                                <span class="form-error" v-if="errors.email">
                                    {{ errors.email }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel"
                                       v-model="form.phone">
                                <span class="form-error" v-if="errors.phone">
                                    {{ errors.phone }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text"
                                       v-model="form.bank_name">
                            </div>

                            <div class="form-group">
                                <label>Bank Account</label>
                                <input type="text"
                                       v-model="form.bank_account">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea v-model="form.notes"
                                    rows="2"
                                    placeholder="Additional notes about the investor"></textarea>
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
                                {{ selectedInvestor ? 'Update' : 'Add' }} Investor
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div v-if="showConfirmation" class="modal confirmation-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Confirm Action</h2>
                        <button class="btn-close" @click="showConfirmation = false">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="confirmation-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>{{ confirmationMessage }}</p>
                        </div>

                        <div class="confirmation-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="showConfirmation = false">
                                Cancel
                            </button>
                            <button type="button" 
                                    class="btn btn-danger"
                                    @click="confirmAction"
                                    :disabled="loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    methods: {
        async loadInvestors() {
            try {
                const response = await axios.get('/api/investors');
                this.investors = response.data;
                this.calculateTotalPercentage();
            } catch (error) {
                this.$root.showToast('Failed to load investors', 'error');
            }
        },

        calculateTotalPercentage() {
            this.totalPercentage = this.investors.reduce(
                (sum, investor) => sum + investor.percentage, 
                0
            );
        },

        showAddForm() {
            this.showForm = true;
            this.selectedInvestor = null;
            this.resetForm();
        },

        editInvestor(investor) {
            this.selectedInvestor = investor;
            this.form = { ...investor };
            this.showForm = true;
        },

        async saveInvestor() {
            if (!this.validateForm()) return;

            this.loading = true;
            try {
                if (this.selectedInvestor) {
                    await axios.put(`/api/investors/${this.selectedInvestor.id}`, this.form);
                    this.$root.showToast('Investor updated successfully', 'success');
                } else {
                    await axios.post('/api/investors', this.form);
                    this.$root.showToast('Investor added successfully', 'success');
                }

                await this.loadInvestors();
                this.cancelEdit();
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    this.$root.showToast('Failed to save investor', 'error');
                }
            } finally {
                this.loading = false;
            }
        },

        async toggleInvestor(investor) {
            try {
                await axios.patch(`/api/investors/${investor.id}/toggle`);
                await this.loadInvestors();
            } catch (error) {
                this.$root.showToast('Failed to toggle investor status', 'error');
            }
        },

        confirmDelete(investor) {
            this.selectedInvestor = investor;
            this.confirmationMessage = `Are you sure you want to delete ${investor.name}? This action cannot be undone.`;
            this.showConfirmation = true;
        },

        async confirmAction() {
            this.loading = true;
            try {
                await axios.delete(`/api/investors/${this.selectedInvestor.id}`);
                this.$root.showToast('Investor deleted successfully', 'success');
                await this.loadInvestors();
                this.showConfirmation = false;
            } catch (error) {
                this.$root.showToast('Failed to delete investor', 'error');
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.name) {
                this.errors.name = 'Name is required';
                isValid = false;
            }

            if (!this.form.percentage || this.form.percentage <= 0) {
                this.errors.percentage = 'Percentage must be greater than 0';
                isValid = false;
            }

            if (this.form.percentage > this.maxPercentage) {
                this.errors.percentage = `Maximum allowed percentage is ${this.maxPercentage}%`;
                isValid = false;
            }

            if (this.form.email && !this.validateEmail(this.form.email)) {
                this.errors.email = 'Invalid email format';
                isValid = false;
            }

            return isValid;
        },

        validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        cancelEdit() {
            this.showForm = false;
            this.selectedInvestor = null;
            this.resetForm();
        },

        resetForm() {
            this.form = {
                name: '',
                email: '',
                phone: '',
                bank_name: '',
                bank_account: '',
                percentage: '',
                active: true,
                notes: ''
            };
            this.errors = {};
        },

        getInvestorColor(investor) {
            // Generate a consistent color based on investor ID
            const hue = (investor.id * 137.508) % 360;
            return `hsl(${hue}, 70%, 60%)`;
        }
    },
    created() {
        this.loadInvestors();
    }
});
