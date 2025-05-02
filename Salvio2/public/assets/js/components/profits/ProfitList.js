Vue.component('profit-list', {
    data() {
        return {
            distributions: [],
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
                investor_id: ''
            },
            sort: {
                key: 'created_at',
                direction: 'desc'
            },
            dateRanges: [
                { value: 'thisMonth', label: 'This Month' },
                { value: 'lastMonth', label: 'Last Month' },
                { value: 'lastQuarter', label: 'Last Quarter' },
                { value: 'thisYear', label: 'This Year' },
                { value: 'custom', label: 'Custom Range' }
            ],
            customDateRange: {
                start: '',
                end: ''
            },
            showCustomDateRange: false,
            investors: [],
            stats: {
                total_profit: 0,
                distributed_profit: 0,
                pending_profit: 0,
                total_revenue: 0,
                total_cost: 0,
                profit_margin: 0
            },
            columns: [
                { key: 'period', label: 'Period' },
                { key: 'revenue', label: 'Revenue', format: 'currency' },
                { key: 'costs', label: 'Costs', format: 'currency' },
                { key: 'net_profit', label: 'Net Profit', format: 'currency' },
                { key: 'distributed_amount', label: 'Distributed', format: 'currency' },
                { key: 'status', label: 'Status' }
            ],
            showDistributionModal: false,
            selectedDistribution: null,
            showCalculationModal: false
        };
    },
    template: `
        <div class="profit-list">
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.total_profit) }}</h3>
                        <p>Total Profit</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.distributed_profit) }}</h3>
                        <p>Distributed Profit</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.pending_profit) }}</h3>
                        <p>Pending Distribution</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ stats.profit_margin }}%</h3>
                        <p>Profit Margin</p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <div class="action-bar-left">
                    <button v-if="hasPermission('calculate_profits')"
                            class="btn btn-primary"
                            @click="showCalculationModal = true">
                        <i class="fas fa-calculator"></i> Calculate Profits
                    </button>

                    <button v-if="hasPermission('manage_investors')"
                            class="btn btn-secondary"
                            @click="$emit('show-investors')">
                        <i class="fas fa-users"></i> Manage Investors
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

                    <select v-if="hasPermission('view_all_profits')"
                            v-model="filters.investor_id"
                            @change="loadDistributions">
                        <option value="">All Investors</option>
                        <option v-for="investor in investors"
                                :key="investor.id"
                                :value="investor.id">
                            {{ investor.name }}
                        </option>
                    </select>

                    <select v-model="filters.status" @change="loadDistributions">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="calculated">Calculated</option>
                        <option value="distributed">Distributed</option>
                    </select>

                    <button class="btn btn-secondary" @click="exportProfits">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Data Table -->
            <data-table
                :columns="columns"
                :data="distributions"
                :loading="loading"
                :pagination="pagination"
                @sort="handleSort"
                @page-change="handlePageChange">

                <!-- Custom Cell Renderers -->
                <template #cell-period="{ item }">
                    <span>{{ formatPeriod(item.period_start, item.period_end) }}</span>
                </template>

                <template #cell-status="{ item }">
                    <span class="badge" :class="getStatusClass(item.status)">
                        {{ getStatusLabel(item.status) }}
                    </span>
                </template>

                <!-- Actions Column -->
                <template #actions="{ item }">
                    <div class="action-buttons">
                        <button v-if="canDistribute(item)"
                                class="btn btn-icon btn-success"
                                @click="distributeProfit(item)"
                                title="Distribute Profits">
                            <i class="fas fa-money-bill-wave"></i>
                        </button>

                        <button v-if="canRecalculate(item)"
                                class="btn btn-icon btn-warning"
                                @click="recalculateProfit(item)"
                                title="Recalculate">
                            <i class="fas fa-calculator"></i>
                        </button>

                        <button class="btn btn-icon btn-info"
                                @click="viewDetails(item)"
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </template>
            </data-table>

            <!-- Calculation Modal -->
            <profit-calculation-modal
                v-if="showCalculationModal"
                @calculate="handleCalculation"
                @close="showCalculationModal = false" />

            <!-- Distribution Modal -->
            <profit-distribution-modal
                v-if="showDistributionModal"
                :distribution="selectedDistribution"
                @distribute="handleDistribution"
                @close="closeDistributionModal" />
        </div>
    `,
    methods: {
        async loadDistributions() {
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

                const response = await axios.get('/api/profit-distributions', { params });
                this.distributions = response.data.data;
                this.pagination = {
                    currentPage: response.data.current_page,
                    totalPages: response.data.last_page,
                    perPage: response.data.per_page,
                    total: response.data.total
                };

                await this.loadStats();
            } catch (error) {
                this.$root.showToast('Failed to load profit distributions', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const params = this.getDateRangeParams();
                if (this.filters.investor_id) {
                    params.investor_id = this.filters.investor_id;
                }

                const response = await axios.get('/api/profit-distributions/stats', { params });
                this.stats = response.data;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async loadInvestors() {
            try {
                const response = await axios.get('/api/investors');
                this.investors = response.data;
            } catch (error) {
                console.error('Failed to load investors:', error);
            }
        },

        handleSort({ key, direction }) {
            this.sort = { key, direction };
            this.loadDistributions();
        },

        handleDateRangeChange() {
            if (this.filters.date_range === 'custom') {
                this.showCustomDateRange = true;
            } else {
                this.showCustomDateRange = false;
                this.loadDistributions();
            }
        },

        applyCustomDateRange() {
            if (this.customDateRange.start && this.customDateRange.end) {
                this.loadDistributions();
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

        async handleCalculation(data) {
            try {
                await axios.post('/api/profit-distributions/calculate', data);
                this.$root.showToast('Profit calculation initiated', 'success');
                this.showCalculationModal = false;
                this.loadDistributions();
            } catch (error) {
                this.$root.showToast('Failed to calculate profits', 'error');
            }
        },

        distributeProfit(distribution) {
            this.selectedDistribution = distribution;
            this.showDistributionModal = true;
        },

        async handleDistribution(data) {
            try {
                await axios.post(`/api/profit-distributions/${this.selectedDistribution.id}/distribute`, data);
                this.$root.showToast('Profits distributed successfully', 'success');
                this.closeDistributionModal();
                this.loadDistributions();
            } catch (error) {
                this.$root.showToast('Failed to distribute profits', 'error');
            }
        },

        closeDistributionModal() {
            this.showDistributionModal = false;
            this.selectedDistribution = null;
        },

        async recalculateProfit(distribution) {
            if (!confirm('Are you sure you want to recalculate this profit distribution?')) return;

            try {
                await axios.post(`/api/profit-distributions/${distribution.id}/recalculate`);
                this.$root.showToast('Recalculation initiated', 'success');
                this.loadDistributions();
            } catch (error) {
                this.$root.showToast('Failed to recalculate profits', 'error');
            }
        },

        viewDetails(distribution) {
            window.location.href = `/profits/${distribution.id}`;
        },

        async exportProfits() {
            try {
                const params = {
                    ...this.filters,
                    ...this.getDateRangeParams()
                };

                const response = await axios.get('/api/profit-distributions/export', {
                    params,
                    responseType: 'blob'
                });
                
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'profit-distributions.xlsx');
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                this.$root.showToast('Failed to export profit distributions', 'error');
            }
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

        canDistribute(distribution) {
            return this.hasPermission('distribute_profits') && 
                   distribution.status === 'calculated';
        },

        canRecalculate(distribution) {
            return this.hasPermission('calculate_profits') && 
                   distribution.status !== 'distributed';
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
                month: 'short'
            }).format(new Date(date));
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
        this.loadDistributions();
        if (this.hasPermission('view_all_profits')) {
            this.loadInvestors();
        }
    }
});
