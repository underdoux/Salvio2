Vue.component('order-list', {
    data() {
        return {
            orders: [],
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
                payment_status: '',
                date_range: 'today'
            },
            sort: {
                key: 'created_at',
                direction: 'desc'
            },
            selectedOrders: [],
            dateRanges: [
                { value: 'today', label: 'Today' },
                { value: 'yesterday', label: 'Yesterday' },
                { value: 'last7days', label: 'Last 7 Days' },
                { value: 'last30days', label: 'Last 30 Days' },
                { value: 'thisMonth', label: 'This Month' },
                { value: 'lastMonth', label: 'Last Month' },
                { value: 'custom', label: 'Custom Range' }
            ],
            customDateRange: {
                start: '',
                end: ''
            },
            showCustomDateRange: false,
            columns: [
                { key: 'order_number', label: 'Order #' },
                { key: 'customer_name', label: 'Customer' },
                { key: 'total_items', label: 'Items', format: 'number' },
                { key: 'total', label: 'Total', format: 'currency' },
                { key: 'status', label: 'Status' },
                { key: 'payment_status', label: 'Payment' },
                { key: 'created_at', label: 'Created', format: 'datetime' }
            ],
            orderStatuses: [
                { value: 'new', label: 'New', color: 'primary' },
                { value: 'processing', label: 'Processing', color: 'info' },
                { value: 'shipped', label: 'Shipped', color: 'warning' },
                { value: 'completed', label: 'Completed', color: 'success' },
                { value: 'cancelled', label: 'Cancelled', color: 'danger' }
            ],
            paymentStatuses: [
                { value: 'pending', label: 'Pending', color: 'warning' },
                { value: 'partial', label: 'Partial', color: 'info' },
                { value: 'paid', label: 'Paid', color: 'success' },
                { value: 'overdue', label: 'Overdue', color: 'danger' },
                { value: 'refunded', label: 'Refunded', color: 'secondary' }
            ],
            showCreateModal: false,
            showFilterModal: false,
            stats: {
                total_orders: 0,
                total_revenue: 0,
                average_order: 0,
                pending_orders: 0
            }
        };
    },
    computed: {
        hasFiltersApplied() {
            return this.filters.status || 
                   this.filters.payment_status || 
                   this.filters.search || 
                   this.filters.date_range !== 'today';
        }
    },
    template: `
        <div class="order-list">
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ stats.total_orders }}</h3>
                        <p>Total Orders</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.total_revenue) }}</h3>
                        <p>Total Revenue</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ formatCurrency(stats.average_order) }}</h3>
                        <p>Average Order</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ stats.pending_orders }}</h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <div class="action-bar-left">
                    <button v-if="hasPermission('create_orders')" 
                            class="btn btn-primary" 
                            @click="showCreateModal = true">
                        <i class="fas fa-plus"></i> New Order
                    </button>
                    
                    <button v-if="selectedOrders.length && hasPermission('delete_orders')" 
                            class="btn btn-danger" 
                            @click="confirmDeleteSelected">
                        Delete Selected
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

                    <button class="btn btn-secondary" @click="showFilterModal = true">
                        <i class="fas fa-filter"></i>
                        {{ hasFiltersApplied ? 'Filters Applied' : 'Filters' }}
                    </button>

                    <button class="btn btn-secondary" @click="exportOrders">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Data Table -->
            <data-table
                :columns="columns"
                :data="orders"
                :loading="loading"
                :pagination="pagination"
                @sort="handleSort"
                @page-change="handlePageChange"
                @selection-change="handleSelectionChange">
                
                <!-- Custom Cell Renderers -->
                <template #cell-status="{ item }">
                    <span class="badge" 
                          :class="'badge-' + getStatusColor(item.status)">
                        {{ getStatusLabel(item.status) }}
                    </span>
                </template>

                <template #cell-payment_status="{ item }">
                    <span class="badge" 
                          :class="'badge-' + getPaymentStatusColor(item.payment_status)">
                        {{ getPaymentStatusLabel(item.payment_status) }}
                    </span>
                </template>

                <!-- Actions Column -->
                <template #actions="{ item }">
                    <div class="action-buttons">
                        <button class="btn btn-icon btn-info" 
                                @click="viewOrder(item)"
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        
                        <button v-if="hasPermission('edit_orders')"
                                class="btn btn-icon btn-primary" 
                                @click="editOrder(item)"
                                title="Edit Order">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <button v-if="hasPermission('process_payments')"
                                class="btn btn-icon btn-success" 
                                @click="processPayment(item)"
                                title="Process Payment">
                            <i class="fas fa-money-bill"></i>
                        </button>
                        
                        <button v-if="canCancelOrder(item)"
                                class="btn btn-icon btn-danger" 
                                @click="cancelOrder(item)"
                                title="Cancel Order">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </template>
            </data-table>

            <!-- Filter Modal -->
            <div v-if="showFilterModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Filter Orders</h2>
                        <button class="btn-close" @click="showFilterModal = false">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Order Status</label>
                            <select v-model="filters.status">
                                <option value="">All Statuses</option>
                                <option v-for="status in orderStatuses"
                                        :key="status.value"
                                        :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Payment Status</label>
                            <select v-model="filters.payment_status">
                                <option value="">All Payment Statuses</option>
                                <option v-for="status in paymentStatuses"
                                        :key="status.value"
                                        :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" 
                                   v-model="filters.search"
                                   placeholder="Search orders...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" 
                                @click="resetFilters">
                            Reset Filters
                        </button>
                        <button class="btn btn-primary" 
                                @click="applyFilters">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Order Modal -->
            <order-form-modal v-if="showCreateModal"
                            @save="saveOrder"
                            @close="showCreateModal = false" />
        </div>
    `,
    methods: {
        async loadOrders() {
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

                const response = await axios.get('/api/orders', { params });
                this.orders = response.data.data;
                this.pagination = {
                    currentPage: response.data.current_page,
                    totalPages: response.data.last_page,
                    perPage: response.data.per_page,
                    total: response.data.total
                };

                await this.loadStats();
            } catch (error) {
                this.$root.showToast('Failed to load orders', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const params = this.getDateRangeParams();
                const response = await axios.get('/api/orders/stats', { params });
                this.stats = response.data;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        handleSort({ key, direction }) {
            this.sort = { key, direction };
            this.loadOrders();
        },

        handlePageChange(page) {
            this.pagination.currentPage = page;
            this.loadOrders();
        },

        handleSelectionChange(selected) {
            this.selectedOrders = selected;
        },

        handleDateRangeChange() {
            if (this.filters.date_range === 'custom') {
                this.showCustomDateRange = true;
            } else {
                this.showCustomDateRange = false;
                this.loadOrders();
            }
        },

        applyCustomDateRange() {
            if (this.customDateRange.start && this.customDateRange.end) {
                this.loadOrders();
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

        resetFilters() {
            this.filters = {
                search: '',
                status: '',
                payment_status: '',
                date_range: 'today'
            };
            this.showCustomDateRange = false;
            this.customDateRange = { start: '', end: '' };
            this.loadOrders();
            this.showFilterModal = false;
        },

        applyFilters() {
            this.loadOrders();
            this.showFilterModal = false;
        },

        async exportOrders() {
            try {
                const params = {
                    ...this.filters,
                    ...this.getDateRangeParams()
                };

                const response = await axios.get('/api/orders/export', {
                    params,
                    responseType: 'blob'
                });
                
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'orders.xlsx');
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                this.$root.showToast('Failed to export orders', 'error');
            }
        },

        getStatusColor(status) {
            const statusObj = this.orderStatuses.find(s => s.value === status);
            return statusObj ? statusObj.color : 'secondary';
        },

        getStatusLabel(status) {
            const statusObj = this.orderStatuses.find(s => s.value === status);
            return statusObj ? statusObj.label : status;
        },

        getPaymentStatusColor(status) {
            const statusObj = this.paymentStatuses.find(s => s.value === status);
            return statusObj ? statusObj.color : 'secondary';
        },

        getPaymentStatusLabel(status) {
            const statusObj = this.paymentStatuses.find(s => s.value === status);
            return statusObj ? statusObj.label : status;
        },

        viewOrder(order) {
            window.location.href = `/orders/${order.id}`;
        },

        editOrder(order) {
            // To be implemented
        },

        processPayment(order) {
            // To be implemented
        },

        canCancelOrder(order) {
            return ['new', 'processing'].includes(order.status) &&
                   this.hasPermission('cancel_orders');
        },

        async cancelOrder(order) {
            if (!confirm('Are you sure you want to cancel this order?')) return;

            try {
                await axios.post(`/api/orders/${order.id}/cancel`);
                this.$root.showToast('Order cancelled successfully', 'success');
                this.loadOrders();
            } catch (error) {
                this.$root.showToast('Failed to cancel order', 'error');
            }
        },

        async confirmDeleteSelected() {
            if (!confirm(`Are you sure you want to delete ${this.selectedOrders.length} orders?`)) return;

            try {
                await axios.post('/api/orders/bulk-delete', {
                    ids: this.selectedOrders
                });
                this.$root.showToast('Orders deleted successfully', 'success');
                this.selectedOrders = [];
                this.loadOrders();
            } catch (error) {
                this.$root.showToast('Failed to delete orders', 'error');
            }
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
        this.loadOrders();
    }
});
