// Global Vue Components
Vue.component('data-table', {
    props: {
        columns: {
            type: Array,
            required: true
        },
        data: {
            type: Array,
            required: true
        },
        loading: {
            type: Boolean,
            default: false
        },
        sortable: {
            type: Boolean,
            default: true
        },
        filterable: {
            type: Boolean,
            default: true
        },
        pagination: {
            type: Object,
            default: () => ({
                currentPage: 1,
                totalPages: 1,
                perPage: 10,
                total: 0
            })
        }
    },
    data() {
        return {
            sortBy: '',
            sortDesc: false,
            filters: {},
            selectedItems: []
        }
    },
    template: `
        <div class="data-table">
            <!-- Table Filters -->
            <div v-if="filterable" class="table-filters">
                <div class="search-box">
                    <input type="text" 
                           v-model="filters.search" 
                           placeholder="Search..."
                           @input="onFilterChange">
                </div>
                <div class="filter-actions">
                    <slot name="filters"></slot>
                </div>
            </div>

            <!-- Table Content -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th v-if="selectable">
                                <input type="checkbox" 
                                       :checked="allSelected"
                                       @change="toggleSelectAll">
                            </th>
                            <th v-for="col in columns" 
                                :key="col.key"
                                @click="sortable ? sort(col.key) : null"
                                :class="{ 
                                    sortable: sortable,
                                    sorted: sortBy === col.key,
                                    'sort-desc': sortDesc && sortBy === col.key
                                }">
                                {{ col.label }}
                                <i v-if="sortable" class="fas fa-sort"></i>
                            </th>
                            <th v-if="$slots.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td :colspan="columns.length + (selectable ? 2 : 1)" class="loading">
                                <div class="spinner"></div>
                                Loading...
                            </td>
                        </tr>
                        <tr v-else-if="!data.length">
                            <td :colspan="columns.length + (selectable ? 2 : 1)" class="no-data">
                                No data available
                            </td>
                        </tr>
                        <tr v-else v-for="item in data" :key="item.id">
                            <td v-if="selectable">
                                <input type="checkbox" 
                                       :value="item.id"
                                       v-model="selectedItems">
                            </td>
                            <td v-for="col in columns" :key="col.key">
                                <slot :name="'cell-' + col.key" :item="item">
                                    {{ formatValue(item[col.key], col.format) }}
                                </slot>
                            </td>
                            <td v-if="$slots.actions">
                                <slot name="actions" :item="item"></slot>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination" class="table-pagination">
                <div class="pagination-info">
                    Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} 
                    of {{ pagination.total }} entries
                </div>
                <div class="pagination-controls">
                    <button class="btn btn-icon" 
                            :disabled="pagination.currentPage === 1"
                            @click="changePage(pagination.currentPage - 1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="page-numbers">
                        <template v-for="page in pageNumbers">
                            <span v-if="page === '...'" class="ellipsis">...</span>
                            <button v-else
                                    class="btn" 
                                    :class="{ 'btn-primary': page === pagination.currentPage }"
                                    @click="changePage(page)">
                                {{ page }}
                            </button>
                        </template>
                    </span>
                    <button class="btn btn-icon" 
                            :disabled="pagination.currentPage === pagination.totalPages"
                            @click="changePage(pagination.currentPage + 1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    `,
    computed: {
        allSelected() {
            return this.data.length > 0 && this.selectedItems.length === this.data.length;
        },
        paginationInfo() {
            const from = (this.pagination.currentPage - 1) * this.pagination.perPage + 1;
            const to = Math.min(from + this.pagination.perPage - 1, this.pagination.total);
            return { from, to };
        },
        pageNumbers() {
            const current = this.pagination.currentPage;
            const last = this.pagination.totalPages;
            const delta = 2;
            const range = [];
            const rangeWithDots = [];

            for (let i = 1; i <= last; i++) {
                if (i === 1 || i === last || 
                    (i >= current - delta && i <= current + delta)) {
                    range.push(i);
                }
            }

            let l;
            for (const i of range) {
                if (l) {
                    if (i - l === 2) {
                        rangeWithDots.push(l + 1);
                    } else if (i - l !== 1) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(i);
                l = i;
            }

            return rangeWithDots;
        }
    },
    methods: {
        sort(key) {
            if (this.sortBy === key) {
                this.sortDesc = !this.sortDesc;
            } else {
                this.sortBy = key;
                this.sortDesc = false;
            }
            this.$emit('sort', { key, direction: this.sortDesc ? 'desc' : 'asc' });
        },
        onFilterChange: _.debounce(function() {
            this.$emit('filter', this.filters);
        }, 300),
        toggleSelectAll() {
            if (this.allSelected) {
                this.selectedItems = [];
            } else {
                this.selectedItems = this.data.map(item => item.id);
            }
            this.$emit('selection-change', this.selectedItems);
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.totalPages) {
                this.$emit('page-change', page);
            }
        },
        formatValue(value, format) {
            if (!format) return value;
            
            switch (format) {
                case 'currency':
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR'
                    }).format(value);
                case 'date':
                    return new Date(value).toLocaleDateString();
                case 'datetime':
                    return new Date(value).toLocaleString();
                case 'number':
                    return new Intl.NumberFormat().format(value);
                case 'percentage':
                    return `${value}%`;
                default:
                    return value;
            }
        }
    },
    watch: {
        selectedItems(newVal) {
            this.$emit('selection-change', newVal);
        }
    }
});

// Main Vue Instance
new Vue({
    el: '#app',
    data: {
        isAuthenticated: false,
        currentUser: null,
        isMenuExpanded: false,
        currentPage: '',
        notifications: [],
        unreadNotifications: 0,
        showNotifications: false,
        showUserMenu: false,
        showPasswordModal: false,
        passwordForm: {
            current: '',
            new: '',
            confirm: ''
        },
        passwordError: '',
        toasts: [],
        toastIdCounter: 0
    },
    created() {
        this.checkAuth();
        this.setupNotifications();
    },
    methods: {
        async checkAuth() {
            try {
                const response = await axios.get('/api/auth/check');
                this.isAuthenticated = response.data.valid;
                if (this.isAuthenticated) {
                    this.currentUser = response.data.user;
                }
            } catch (error) {
                console.error('Auth check failed:', error);
            }
        },
        setupNotifications() {
            if (this.isAuthenticated) {
                this.loadNotifications();
                // Set up polling for new notifications
                setInterval(this.loadNotifications, 30000); // Every 30 seconds
            }
        },
        async loadNotifications() {
            try {
                const response = await axios.get('/api/notifications');
                this.notifications = response.data.notifications;
                this.unreadNotifications = this.notifications.filter(n => !n.read_at).length;
            } catch (error) {
                console.error('Failed to load notifications:', error);
            }
        },
        toggleMenu() {
            this.isMenuExpanded = !this.isMenuExpanded;
        },
        toggleNotifications() {
            this.showNotifications = !this.showNotifications;
            if (this.showNotifications) {
                this.markNotificationsAsRead();
            }
        },
        async markNotificationsAsRead() {
            try {
                await axios.post('/api/notifications/mark-read');
                this.notifications = this.notifications.map(n => ({
                    ...n,
                    read_at: n.read_at || new Date()
                }));
                this.unreadNotifications = 0;
            } catch (error) {
                console.error('Failed to mark notifications as read:', error);
            }
        },
        toggleUserMenu() {
            this.showUserMenu = !this.showUserMenu;
        },
        async logout() {
            try {
                await axios.post('/api/auth/logout');
                window.location.href = '/login';
            } catch (error) {
                this.showToast('Error logging out', 'error');
            }
        },
        changePassword() {
            this.showPasswordModal = true;
            this.passwordForm = {
                current: '',
                new: '',
                confirm: ''
            };
            this.passwordError = '';
        },
        async updatePassword() {
            if (this.passwordForm.new !== this.passwordForm.confirm) {
                this.passwordError = 'New passwords do not match';
                return;
            }

            try {
                await axios.post('/api/auth/change-password', {
                    current_password: this.passwordForm.current,
                    new_password: this.passwordForm.new
                });
                this.showPasswordModal = false;
                this.showToast('Password updated successfully', 'success');
            } catch (error) {
                this.passwordError = error.response?.data?.error || 'Failed to update password';
            }
        },
        closePasswordModal() {
            this.showPasswordModal = false;
        },
        showToast(message, type = 'info') {
            const id = ++this.toastIdCounter;
            this.toasts.push({ id, message, type });
            setTimeout(() => this.removeToast(id), 5000);
        },
        removeToast(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                this.toasts.splice(index, 1);
            }
        },
        hasPermission(permission) {
            return this.currentUser?.permissions?.includes(permission) || false;
        },
        getNotificationIcon(type) {
            const icons = {
                order: 'fa-shopping-cart',
                payment: 'fa-money-bill',
                stock: 'fa-box',
                commission: 'fa-percentage',
                profit: 'fa-chart-line'
            };
            return `fas ${icons[type] || 'fa-bell'}`;
        },
        formatDate(date) {
            return new Date(date).toLocaleString();
        }
    }
});
