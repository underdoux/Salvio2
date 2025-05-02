Vue.component('commission-list', {
    data() {
        return {
            commissions: [],
            loading: false,
            pagination: {
                currentPage: 1,
                totalPages: 1,
                perPage: 10,
                total: 0
            },
            filters: {
                search: '',
                status: '',
                date_range: 'thisMonth',
                user_id: ''
            },
            sort: {
                key: 'created_at',
                direction: 'desc'
            },
            dateRanges: [
                { value: 'today', label: 'Today' },
                { value: 'thisWeek', label: 'This Week' },
                { value: 'thisMonth', label: 'This Month' },
                { value: 'lastMonth', label: 'Last Month' },
                { value: 'custom', label: 'Custom Range' }
            ],
            customDateRange: {
                start: '',
                end: ''
            },
            showCustomDateRange: false,
            users: [],
            stats: {
                total_commission: 0,
                pending_commission: 0,
                paid_commission: 0,
                commission_rate: 0
            },
            columns: [
                { key: 'order_number', label: 'Order #' },
                { key: 'user_name', label: 'Sales Person' },
                { key: 'order_total', label: 'Order Total', format: 'currency' },
                { key: 'commission_rate', label: 'Rate', format: 'percentage' },
                { key: 'amount', label: 'Commission', format: 'currency' },
                { key: 'status', label: 'Status' },
                { key: 'created_at', label: 'Date', format: 'datetime' }
            ],
            showRuleModal: false,
            selectedCommission: null,
            showApprovalModal: false
        };
    },
    template: `
        <div class="commission-list">
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.total_commission) }}</h3>
                        <p>Total Commission</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.pending_commission) }}</h3>
                        <p>Pending Commission</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.paid_commission) }}</h3>
                        <p>Paid Commission</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ stats.commission_rate }}%</h3>
                        <p>Average Rate</p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <div class="action-bar-left">
                    <button v-if="hasPermission('manage_commission_rules')"
                            class="btn btn-primary"
                            @click="showRuleModal = true">
                        <i class="fas fa-cog"></i> Commission Rules
                    </button>
                </div>

                <div class="action-bar-right">
                    <div class="filter-group">
                        <select v-model="filters.date_range" @change="handleDateRangeChange">
                            <option v-for="range in dateRanges" 
                                    :key="range.value" 
                                    :value="range.value">
                                {{ range.label }}
                            </option>
                        </select>

                        <div v-if="showCustomDateRange" class="custom-date-range">
                            <input type="date" 
                                   v-model="customDateRange.start"
                                   @change="applyCustomDateRange">
                            <span>to</span>
                            <input type="date" 
                                   v-model="customDateRange.end"
                                   @change="applyCustomDateRange">
                        </div>
                    </div>

                    <select v-if="hasPermission('view_all_commissions')"
                            v-model="filters.user_id"
                            @change="loadCommissions">
                        <option value="">All Sales Persons</option>
                        <option v-for="user in users"
                                :key="user.id"
                                :value="user.id">
                            {{ user.name }}
                        </option>
                    </select>

                    <select v-model="filters.status" @change="loadCommissions">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <button class="btn btn-secondary" @click="exportCommissions">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Data Table -->
            <data-table
                :columns="columns"
                :data="commissions"
                :loading="loading"
                :pagination="pagination"
                @sort="handleSort"
                @page-change="handlePageChange">

                <!-- Custom Cell Renderers -->
                <template #cell-order_number="{ item }">
                    <a :href="'/orders/' + item.order_id">{{ item.order_number }}</a>
                </template>

                <template #cell-status="{ item }">
                    <span class="badge" :class="getStatusClass(item.status)">
                        {{ getStatusLabel(item.status) }}
                    </span>
                </template>

                <!-- Actions Column -->
                <template #actions="{ item }">
                    <div class="action-buttons">
                        <button v-if="canApprove(item)"
                                class="btn btn-icon btn-success"
                                @click="approveCommission(item)"
                                title="Approve Commission">
                            <i class="fas fa-check"></i>
                        </button>

                        <button v-if="canReject(item)"
                                class="btn btn-icon btn-danger"
                                @click="rejectCommission(item)"
                                title="Reject Commission">
                            <i class="fas fa-times"></i>
                        </button>

                        <button v-if="canPay(item)"
                                class="btn btn-icon btn-primary"
                                @click="payCommission(item)"
                                title="Process Payment">
                            <i class="fas fa-money-bill"></i>
                        </button>

                        <button class="btn btn-icon btn-info"
                                @click="viewDetails(item)"
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </template>
            </data-table>

            <!-- Commission Rule Modal -->
            <commission-rule-modal
                v-if="showRuleModal"
                @save="handleRuleSave"
                @close="showRuleModal = false" />

            <!-- Commission Approval Modal -->
            <commission-approval-modal
                v-if="showApprovalModal"
                :commission="selectedCommission"
                @approve="handleApproval"
                @reject="handleRejection"
                @close="closeApprovalModal" />
        </div>
    `,
    methods: {
        async loadCommissions() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.currentPage,
                    per_page: this.pagination.perPage,
                    sort_by: this.sort.key,
                    sort_direction: this.sort.direction,
                    ...this.filters,
                    ...this.getDateRangeParams()
                };

                const response = await axios.get('/api/commissions', { params });
                this.commissions = response.data.data;
                this.pagination = {
                    currentPage: response.data.current_page,
                    totalPages: response.data.last_page,
                    perPage: response.data.per_page,
                    total: response.data.total
                };

                await this.loadStats();
            } catch (error) {
                this.$root.showToast('Failed to load commissions', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const params = this.getDateRangeParams();
                if (this.filters.user_id) {
                    params.user_id = this.filters.user_id;
                }

                const response = await axios.get('/api/commissions/stats', { params });
                this.stats = response.data;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async loadUsers() {
            try {
                const response = await axios.get('/api/users', {
                    params: { role: 'sales' }
                });
                this.users = response.data;
            } catch (error) {
                console.error('Failed to load users:', error);
            }
        },

        handleSort({ key, direction }) {
            this.sort = { key, direction };
            this.loadCommissions();
        },

        handleDateRangeChange() {
            if (this.filters.date_range === 'custom') {
                this.showCustomDateRange = true;
            } else {
                this.showCustomDateRange = false;
                this.loadCommissions();
            }
        },

        applyCustomDateRange() {
            if (this.customDateRange.start && this.customDateRange.end) {
                this.loadCommissions();
            }
        },

        getDateRangeParams() {
            if (this.filters.date_range === 'custom') {
                return {
                    start_date: this.customDateRange.start,
                    end_date: this.customDateRange.end
                };
            }
            return { date_range: this.filters.date_range };
        },

        handleRuleSave() {
            this.showRuleModal = false;
            this.loadCommissions();
        },

        approveCommission(commission) {
            this.selectedCommission = commission;
            this.showApprovalModal = true;
        },

        async handleApproval(data) {
            try {
                await axios.post(`/api/commissions/${this.selectedCommission.id}/approve`, data);
                this.$root.showToast('Commission approved successfully', 'success');
                this.closeApprovalModal();
                this.loadCommissions();
            } catch (error) {
                this.$root.showToast('Failed to approve commission', 'error');
            }
        },

        async handleRejection(data) {
            try {
                await axios.post(`/api/commissions/${this.selectedCommission.id}/reject`, data);
                this.$root.showToast('Commission rejected successfully', 'success');
                this.closeApprovalModal();
                this.loadCommissions();
            } catch (error) {
                this.$root.showToast('Failed to reject commission', 'error');
            }
        },

        closeApprovalModal() {
            this.showApprovalModal = false;
            this.selectedCommission = null;
        },

        async payCommission(commission) {
            if (!confirm('Are you sure you want to mark this commission as paid?')) return;

            try {
                await axios.post(`/api/commissions/${commission.id}/pay`);
                this.$root.showToast('Commission marked as paid', 'success');
                this.loadCommissions();
            } catch (error) {
                this.$root.showToast('Failed to process payment', 'error');
            }
        },

        viewDetails(commission) {
            window.location.href = `/commissions/${commission.id}`;
        },

        async exportCommissions() {
            try {
                const params = {
                    ...this.filters,
                    ...this.getDateRangeParams()
                };

                const response = await axios.get('/api/commissions/export', {
                    params,
                    responseType: 'blob'
                });
                
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'commissions.xlsx');
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                this.$root.showToast('Failed to export commissions', 'error');
            }
        },

        getStatusClass(status) {
            const classes = {
                pending: 'badge-warning',
                approved: 'badge-info',
                paid: 'badge-success',
                rejected: 'badge-danger'
            };
            return classes[status] || 'badge-secondary';
        },

        getStatusLabel(status) {
            const labels = {
                pending: 'Pending',
                approved: 'Approved',
                paid: 'Paid',
                rejected: 'Rejected'
            };
            return labels[status] || status;
        },

        canApprove(commission) {
            return this.hasPermission('approve_commissions') && 
                   commission.status === 'pending';
        },

        canReject(commission) {
            return this.hasPermission('approve_commissions') && 
                   commission.status === 'pending';
        },

        canPay(commission) {
            return this.hasPermission('process_commission_payments') && 
                   commission.status === 'approved';
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        hasPermission(permission) {
            return this.$root.hasPermission(permission);
        }
    },
    created() {
        this.loadCommissions();
        if (this.hasPermission('view_all_commissions')) {
            this.loadUsers();
        }
    }
});
